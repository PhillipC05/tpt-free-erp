<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Sales\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends BaseApiController
{
    protected array $validationRules = [
        'invoice_number' => 'required|string|max:50|unique:sales_invoices,invoice_number',
        'order_id' => 'nullable|exists:sales_orders,id',
        'customer_id' => 'required|exists:sales_customers,id',
        'invoice_date' => 'required|date',
        'due_date' => 'required|date|after_or_equal:invoice_date',
        'subtotal' => 'required|numeric|min:0',
        'tax_amount' => 'nullable|numeric|min:0',
        'total_amount' => 'required|numeric|min:0',
        'paid_amount' => 'nullable|numeric|min:0',
        'balance_due' => 'nullable|numeric|min:0',
        'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled,partially_paid',
    ];

    protected array $validationMessages = [
        'invoice_number.required' => 'Invoice number is required.',
        'invoice_number.unique' => 'This invoice number is already in use.',
        'customer_id.required' => 'Customer is required.',
        'invoice_date.required' => 'Invoice date is required.',
        'due_date.required' => 'Due date is required.',
        'total_amount.required' => 'Total amount is required.',
    ];

    public function __construct()
    {
        parent::__construct(new Invoice);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'invoice_number' => 'required|string|max:50|unique:sales_invoices,invoice_number',
        ]));
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'draft';
        $data['balance_due'] = $data['balance_due'] ?? $data['total_amount'];

        $invoice = Invoice::create($data);

        return $this->respondCreated($invoice, 'Invoice created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::find($id);
        if (! $invoice) {
            return $this->respondNotFound();
        }

        if ($invoice->status === 'paid') {
            return $this->respondError('Cannot update a paid invoice', 422);
        }

        $error = $this->validate($request->all(), [
            'invoice_number' => 'required|string|max:50|unique:sales_invoices,invoice_number,'.$id,
            'order_id' => 'nullable|exists:sales_orders,id',
            'customer_id' => 'required|exists:sales_customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'balance_due' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled,partially_paid',
        ]);
        if ($error) {
            return $error;
        }

        $invoice->update($request->all());

        return $this->respondSuccess('Invoice updated', $invoice->fresh());
    }

    public function send(int $id): JsonResponse
    {
        $invoice = Invoice::find($id);
        if (! $invoice) {
            return $this->respondNotFound();
        }

        if ($invoice->status !== 'draft') {
            return $this->respondError('Only draft invoices can be sent', 422);
        }

        $invoice->update(['status' => 'sent']);

        return $this->respondSuccess('Invoice sent', $invoice->fresh());
    }

    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::find($id);
        if (! $invoice) {
            return $this->respondNotFound();
        }

        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return $this->respondError('Cannot record payment for this invoice', 422);
        }

        $error = $this->validate($request->all(), [
            'amount' => 'required|numeric|min:0.01',
        ]);
        if ($error) {
            return $error;
        }

        $amount = (float) $request->query('amount');
        $totalPaid = (float) $invoice->paid_amount + $amount;
        $totalAmount = (float) $invoice->total_amount;
        $newBalance = $totalAmount - $totalPaid;

        $status = $newBalance <= 0 ? 'paid' : 'partially_paid';

        $invoice->update([
            'paid_amount' => $totalPaid,
            'balance_due' => max($newBalance, 0),
            'status' => $status,
        ]);

        return $this->respondSuccess('Payment recorded', $invoice->fresh());
    }

    public function cancel(int $id): JsonResponse
    {
        $invoice = Invoice::find($id);
        if (! $invoice) {
            return $this->respondNotFound();
        }

        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return $this->respondError('Invoice cannot be cancelled', 422);
        }

        $invoice->update(['status' => 'cancelled']);

        return $this->respondSuccess('Invoice cancelled', $invoice->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()->with(['customer', 'order']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->query('customer_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('start_date')) {
            $query->where('invoice_date', '>=', $request->query('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('invoice_date', '<=', $request->query('end_date'));
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('invoice_date', 'desc')->paginate(min($perPage, 100));

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

    public function overdue(Request $request): JsonResponse
    {
        $query = Invoice::where('status', 'sent')
            ->orWhere('status', 'partially_paid')
            ->where('due_date', '<', now()->toDateString());

        $perPage = $request->query('per_page', 15);
        $items = $query->with('customer')->orderBy('due_date', 'asc')->paginate(min($perPage, 100));

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
}
