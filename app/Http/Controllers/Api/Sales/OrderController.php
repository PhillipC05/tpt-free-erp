<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Sales\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends BaseApiController
{
    protected array $validationRules = [
        'order_number' => 'required|string|max:50|unique:sales_orders,order_number',
        'customer_id' => 'required|exists:sales_customers,id',
        'order_date' => 'required|date',
        'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
        'status' => 'sometimes|in:draft,confirmed,processing,shipped,delivered,cancelled',
        'subtotal' => 'required|numeric|min:0',
        'tax_amount' => 'nullable|numeric|min:0',
        'discount_amount' => 'nullable|numeric|min:0',
        'total_amount' => 'required|numeric|min:0',
        'notes' => 'nullable|string',
        'created_by' => 'nullable|exists:users,id',
    ];

    protected array $validationMessages = [
        'order_number.required' => 'Order number is required.',
        'order_number.unique' => 'This order number is already in use.',
        'customer_id.required' => 'Customer is required.',
        'subtotal.required' => 'Subtotal is required.',
        'total_amount.required' => 'Total amount is required.',
    ];

    public function __construct()
    {
        parent::__construct(new Order());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'order_number' => 'required|string|max:50|unique:sales_orders,order_number',
        ]));
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'draft';
        $data['created_by'] = $data['created_by'] ?? auth()->id();

        $order = Order::create($data);
        return $this->respondCreated($order, 'Order created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $order = Order::find($id);
        if (!$order) return $this->respondNotFound();

        if (!in_array($order->status, ['draft', 'confirmed'])) {
            return $this->respondError('Cannot update an order that is already in progress', 422);
        }

        $error = $this->validate($request->all(), [
            'order_number' => 'required|string|max:50|unique:sales_orders,order_number,' . $id,
            'customer_id' => 'required|exists:sales_customers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'status' => 'sometimes|in:draft,confirmed,processing,shipped,delivered,cancelled',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        if ($error) return $error;

        $order->update($request->all());
        return $this->respondSuccess('Order updated', $order->fresh());
    }

    public function confirm(int $id): JsonResponse
    {
        $order = Order::find($id);
        if (!$order) return $this->respondNotFound();

        if ($order->status !== 'draft') {
            return $this->respondError('Only draft orders can be confirmed', 422);
        }

        $order->update(['status' => 'confirmed']);
        return $this->respondSuccess('Order confirmed', $order->fresh());
    }

    public function cancel(int $id): JsonResponse
    {
        $order = Order::find($id);
        if (!$order) return $this->respondNotFound();

        if (in_array($order->status, ['delivered', 'cancelled'])) {
            return $this->respondError('Order cannot be cancelled', 422);
        }

        $order->update(['status' => 'cancelled']);
        return $this->respondSuccess('Order cancelled', $order->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Order::query()->with(['customer', 'items.product']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->get('customer_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('start_date')) {
            $query->where('order_date', '>=', $request->get('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('order_date', '<=', $request->get('end_date'));
        }

        $perPage = $request->get('per_page', 15);
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
        $order = Order::with(['customer', 'items.product', 'invoices'])->find($id);
        if (!$order) return $this->respondNotFound();

        return $this->respond(['success' => true, 'data' => $order]);
    }
}