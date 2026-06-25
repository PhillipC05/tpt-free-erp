<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Pos\Payment;
use App\Models\Pos\Transaction;
use App\Models\Pos\TransactionItem;
use App\Services\Pos\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends BaseApiController
{
    protected string $cacheTag = 'pos_transactions';

    public function __construct()
    {
        parent::__construct(new Transaction);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Transaction::query()->with(['terminal', 'customer', 'employee', 'items', 'payments']);

        if ($request->has('terminal_id')) {
            $query->where('terminal_id', $request->query('terminal_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->query('customer_id'));
        }

        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->query('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->query('end_date').' 23:59:59');
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('created_at', 'desc')->paginate(min($perPage, 100));

        return $this->respond([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = Transaction::with(['terminal', 'customer', 'employee', 'items.product', 'payments', 'creator'])->find($id);
        if (! $transaction) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $transaction]);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'terminal_id' => 'required|exists:pos_terminals,id',
            'customer_id' => 'nullable|exists:sales_customers,id',
            'employee_id' => 'nullable|exists:hr_employees,id',
            'currency' => 'nullable|string|max:3',
        ]);
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['transaction_number'] = Transaction::generateTransactionNumber();
        $data['created_by'] = Auth::id();
        $data['status'] = 'open';

        $transaction = Transaction::create($data);

        return $this->respondCreated($transaction->fresh(), 'Transaction opened successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::find($id);
        if (! $transaction) {
            return $this->respondNotFound();
        }

        if ($transaction->status !== 'open') {
            return $this->respondError('Only open transactions can be updated', 422);
        }

        $error = $this->validate($request->all(), [
            'customer_id' => 'nullable|exists:sales_customers,id',
            'employee_id' => 'nullable|exists:hr_employees,id',
            'currency' => 'nullable|string|max:3',
            'notes' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $transaction->update($request->only(['customer_id', 'employee_id', 'currency', 'notes']));

        return $this->respondSuccess('Transaction updated', $transaction->fresh());
    }

    public function addItem(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::find($id);
        if (! $transaction) {
            return $this->respondNotFound();
        }

        if ($transaction->status !== 'open') {
            return $this->respondError('Cannot add items to a non-open transaction', 422);
        }

        $error = $this->validate($request->all(), [
            'product_id' => 'nullable|exists:inventory_products,id',
            'description' => 'required|string|max:500',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $quantity = $data['quantity'];
        $unitPrice = $data['unit_price'];
        $discountPercent = $data['discount_percent'] ?? 0;
        $taxPercent = $data['tax_percent'] ?? 0;

        $discountAmount = ($quantity * $unitPrice) * ($discountPercent / 100);
        $lineSubtotal = ($quantity * $unitPrice) - $discountAmount;
        $lineTax = $lineSubtotal * ($taxPercent / 100);
        $data['line_total'] = $lineSubtotal + $lineTax;
        $data['transaction_id'] = $id;

        $item = TransactionItem::create($data);

        $this->recalculateTotals($transaction);

        return $this->respondCreated($item->fresh(['product']), 'Item added to transaction');
    }

    public function removeItem(Request $request, int $id, int $itemId): JsonResponse
    {
        $transaction = Transaction::find($id);
        if (! $transaction) {
            return $this->respondNotFound();
        }

        if ($transaction->status !== 'open') {
            return $this->respondError('Cannot modify a non-open transaction', 422);
        }

        $item = TransactionItem::where('id', $itemId)->where('transaction_id', $id)->first();
        if (! $item) {
            return $this->respondNotFound('Item not found');
        }

        $item->delete();
        $this->recalculateTotals($transaction);

        return $this->respondSuccess('Item removed');
    }

    public function checkout(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::find($id);
        if (! $transaction) {
            return $this->respondNotFound();
        }

        if ($transaction->status !== 'open') {
            return $this->respondError('Transaction is not open', 422);
        }

        if ($transaction->items()->count() === 0) {
            return $this->respondError('Cannot checkout an empty transaction', 422);
        }

        $error = $this->validate($request->all(), [
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|in:cash,card,bank_transfer,digital_wallet,other',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.reference' => 'nullable|string|max:100',
        ]);
        if ($error) {
            return $error;
        }

        $totalPaid = array_sum(array_column($request->input('payments'), 'amount'));
        if ($totalPaid < $transaction->total_amount) {
            return $this->respondError('Total payments ('.number_format($totalPaid, 2).') is less than transaction total ('.number_format($transaction->total_amount, 2).')', 422);
        }

        try {
            $service = new PosService;
            $result = $service->processCheckout($transaction, $request->input('payments', []), Auth::id());

            return $this->respondSuccess('Checkout completed', $result);
        } catch (\Exception $e) {
            return $this->respondError('Checkout failed: '.$e->getMessage(), 422);
        }
    }

    public function void(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::find($id);
        if (! $transaction) {
            return $this->respondNotFound();
        }

        if ($transaction->status !== 'completed') {
            return $this->respondError('Only completed transactions can be voided', 422);
        }

        $error = $this->validate($request->all(), [
            'reason' => 'required|string|max:500',
        ]);
        if ($error) {
            return $error;
        }

        $service = new PosService;
        $result = $service->voidTransaction($transaction, $request->input('reason'), Auth::id());

        return $this->respondSuccess('Transaction voided', $result);
    }

    public function refund(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::find($id);
        if (! $transaction) {
            return $this->respondNotFound();
        }

        if ($transaction->status !== 'completed') {
            return $this->respondError('Only completed transactions can be refunded', 422);
        }

        $error = $this->validate($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,card,bank_transfer,digital_wallet,other',
            'reason' => 'required|string|max:500',
        ]);
        if ($error) {
            return $error;
        }

        $service = new PosService;
        $result = $service->refundTransaction($transaction, $request->all(), Auth::id());

        return $this->respondSuccess('Refund processed', $result);
    }

    public function summary(Request $request): JsonResponse
    {
        $query = Transaction::query();

        if ($request->has('terminal_id')) {
            $query->where('terminal_id', $request->query('terminal_id'));
        }

        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->query('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->query('end_date').' 23:59:59');
        }

        $completed = (clone $query)->where('status', 'completed')->count();
        $voided = (clone $query)->where('status', 'voided')->count();
        $totalRevenue = (clone $query)->where('status', 'completed')->sum('total_amount');
        $totalTax = (clone $query)->where('status', 'completed')->sum('tax_amount');
        $totalDiscounts = (clone $query)->where('status', 'completed')->sum('discount_amount');

        $paymentBreakdown = Payment::whereHas('transaction', function ($q) use ($request) {
            $q->where('status', 'completed');
            if ($request->has('terminal_id')) {
                $q->where('terminal_id', $request->query('terminal_id'));
            }
            if ($request->has('start_date')) {
                $q->where('created_at', '>=', $request->query('start_date'));
            }
            if ($request->has('end_date')) {
                $q->where('created_at', '<=', $request->query('end_date').' 23:59:59');
            }
        })->selectRaw('method, count(*) as count, sum(amount) as total')
            ->groupBy('method')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'completed_transactions' => $completed,
                'voided_transactions' => $voided,
                'total_revenue' => $totalRevenue,
                'total_tax' => $totalTax,
                'total_discounts' => $totalDiscounts,
                'payment_breakdown' => $paymentBreakdown,
            ],
        ]);
    }

    private function recalculateTotals(Transaction $transaction): void
    {
        $items = $transaction->items;
        $subtotal = $items->sum('line_total');
        $taxAmount = 0;
        foreach ($items as $item) {
            $discountAmount = ($item->quantity * $item->unit_price) * ($item->discount_percent / 100);
            $lineSubtotal = ($item->quantity * $item->unit_price) - $discountAmount;
            $taxAmount += $lineSubtotal * ($item->tax_percent / 100);
        }

        $transaction->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal,
        ]);
    }
}
