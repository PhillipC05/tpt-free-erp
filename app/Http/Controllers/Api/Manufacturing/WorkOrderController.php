<?php

namespace App\Http\Controllers\Api\Manufacturing;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Manufacturing\WorkOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkOrderController extends BaseApiController
{
    protected array $validationRules = [
        'wo_number' => 'required|string|max:50|unique:manufacturing_work_orders,wo_number',
        'product_id' => 'required|exists:inventory_products,id',
        'bom_id' => 'nullable|exists:manufacturing_boms,id',
        'planned_quantity' => 'required|numeric|min:1',
        'produced_quantity' => 'nullable|numeric|min:0',
        'start_date' => 'required|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'status' => 'sometimes|in:planned,in_progress,completed,cancelled,on_hold',
        'notes' => 'nullable|string',
        'assigned_to' => 'nullable|exists:hr_employees,id',
    ];

    protected array $validationMessages = [
        'wo_number.required' => 'Work order number is required.',
        'wo_number.unique' => 'This work order number is already in use.',
        'product_id.required' => 'Product is required.',
        'planned_quantity.required' => 'Planned quantity is required.',
        'start_date.required' => 'Start date is required.',
    ];

    public function __construct()
    {
        parent::__construct(new WorkOrder());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'wo_number' => 'required|string|max:50|unique:manufacturing_work_orders,wo_number',
        ]));
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'planned';

        $wo = WorkOrder::create($data);
        return $this->respondCreated($wo, 'Work order created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $wo = WorkOrder::find($id);
        if (!$wo) return $this->respondNotFound();

        if (in_array($wo->status, ['completed', 'cancelled'])) {
            return $this->respondError('Cannot update a completed or cancelled work order', 422);
        }

        $error = $this->validate($request->all(), [
            'wo_number' => 'required|string|max:50|unique:manufacturing_work_orders,wo_number,' . $id,
            'product_id' => 'required|exists:inventory_products,id',
            'bom_id' => 'nullable|exists:manufacturing_boms,id',
            'planned_quantity' => 'required|numeric|min:1',
            'produced_quantity' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'sometimes|in:planned,in_progress,completed,cancelled,on_hold',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:hr_employees,id',
        ]);
        if ($error) return $error;

        $wo->update($request->all());
        return $this->respondSuccess('Work order updated', $wo->fresh());
    }

    public function start(int $id): JsonResponse
    {
        $wo = WorkOrder::find($id);
        if (!$wo) return $this->respondNotFound();

        if ($wo->status !== 'planned') {
            return $this->respondError('Only planned work orders can be started', 422);
        }

        $wo->update(['status' => 'in_progress']);
        return $this->respondSuccess('Work order started', $wo->fresh());
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $wo = WorkOrder::find($id);
        if (!$wo) return $this->respondNotFound();

        if ($wo->status !== 'in_progress') {
            return $this->respondError('Only in-progress work orders can be completed', 422);
        }

        $data = $request->only(['produced_quantity', 'notes']);
        $data['status'] = 'completed';
        $data['end_date'] = $data['end_date'] ?? now()->toDateString();

        $wo->update($data);
        return $this->respondSuccess('Work order completed', $wo->fresh());
    }

    public function cancel(int $id): JsonResponse
    {
        $wo = WorkOrder::find($id);
        if (!$wo) return $this->respondNotFound();

        if (in_array($wo->status, ['completed', 'cancelled'])) {
            return $this->respondError('Work order cannot be cancelled', 422);
        }

        $wo->update(['status' => 'cancelled']);
        return $this->respondSuccess('Work order cancelled', $wo->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = WorkOrder::query()->with(['product', 'bom', 'assignedTo']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->get('assigned_to'));
        }

        $perPage = $request->get('per_page', 15);
        $items = $query->orderBy('start_date', 'desc')->paginate(min($perPage, 100));

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
        $wo = WorkOrder::with(['product', 'bom', 'assignedTo'])->find($id);
        if (!$wo) return $this->respondNotFound();

        return $this->respond(['success' => true, 'data' => $wo]);
    }
}