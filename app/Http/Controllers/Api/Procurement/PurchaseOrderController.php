<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Procurement\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends BaseApiController
{
    protected array $validationRules = [
        'po_number' => 'required|string|max:50|unique:procurement_purchase_orders,po_number',
        'vendor_id' => 'required|exists:procurement_vendors,id',
        'order_date' => 'required|date',
        'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
        'status' => 'sometimes|in:draft,sent,confirmed,received,cancelled',
        'subtotal' => 'required|numeric|min:0',
        'tax_amount' => 'nullable|numeric|min:0',
        'total_amount' => 'required|numeric|min:0',
        'notes' => 'nullable|string',
        'created_by' => 'nullable|exists:users,id',
        'approved_by' => 'nullable|exists:users,id',
    ];

    protected array $validationMessages = [
        'po_number.required' => 'Purchase order number is required.',
        'po_number.unique' => 'This PO number is already in use.',
        'vendor_id.required' => 'Vendor is required.',
        'order_date.required' => 'Order date is required.',
        'subtotal.required' => 'Subtotal is required.',
        'total_amount.required' => 'Total amount is required.',
    ];

    public function __construct()
    {
        parent::__construct(new PurchaseOrder());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'po_number' => 'required|string|max:50|unique:procurement_purchase_orders,po_number',
        ]));
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'draft';
        $data['created_by'] = $data['created_by'] ?? Auth::id();

        $po = PurchaseOrder::create($data);
        return $this->respondCreated($po, 'Purchase order created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $po = PurchaseOrder::find($id);
        if (!$po) return $this->respondNotFound();

        if (!in_array($po->status, ['draft', 'sent'])) {
            return $this->respondError('Cannot update a purchase order that is confirmed or received', 422);
        }

        $error = $this->validate($request->all(), [
            'po_number' => 'required|string|max:50|unique:procurement_purchase_orders,po_number,' . $id,
            'vendor_id' => 'required|exists:procurement_vendors,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'status' => 'sometimes|in:draft,sent,confirmed,received,cancelled',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        if ($error) return $error;

        $po->update($request->all());
        return $this->respondSuccess('Purchase order updated', $po->fresh());
    }

    public function send(int $id): JsonResponse
    {
        $po = PurchaseOrder::find($id);
        if (!$po) return $this->respondNotFound();

        if ($po->status !== 'draft') {
            return $this->respondError('Only draft purchase orders can be sent', 422);
        }

        $po->update(['status' => 'sent']);
        return $this->respondSuccess('Purchase order sent to vendor', $po->fresh());
    }

    public function confirm(int $id): JsonResponse
    {
        $po = PurchaseOrder::find($id);
        if (!$po) return $this->respondNotFound();

        if ($po->status !== 'sent') {
            return $this->respondError('Only sent purchase orders can be confirmed', 422);
        }

        $po->update([
            'status' => 'confirmed',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $this->respondSuccess('Purchase order confirmed', $po->fresh());
    }

    public function receive(int $id): JsonResponse
    {
        $po = PurchaseOrder::find($id);
        if (!$po) return $this->respondNotFound();

        if ($po->status !== 'confirmed') {
            return $this->respondError('Only confirmed purchase orders can be received', 422);
        }

        $po->update(['status' => 'received']);
        return $this->respondSuccess('Purchase order marked as received', $po->fresh());
    }

    public function cancel(int $id): JsonResponse
    {
        $po = PurchaseOrder::find($id);
        if (!$po) return $this->respondNotFound();

        if (in_array($po->status, ['received', 'cancelled'])) {
            return $this->respondError('Purchase order cannot be cancelled', 422);
        }

        $po->update(['status' => 'cancelled']);
        return $this->respondSuccess('Purchase order cancelled', $po->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = PurchaseOrder::query()->with(['vendor', 'items.product']);

        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->query('vendor_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('start_date')) {
            $query->where('order_date', '>=', $request->query('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('order_date', '<=', $request->query('end_date'));
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('order_date', 'desc')->paginate(min($perPage, 100));

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
        $po = PurchaseOrder::with(['vendor', 'items.product'])->find($id);
        if (!$po) return $this->respondNotFound();

        return $this->respond(['success' => true, 'data' => $po]);
    }
}