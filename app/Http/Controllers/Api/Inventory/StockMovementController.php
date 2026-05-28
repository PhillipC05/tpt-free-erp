<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Inventory\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends BaseApiController
{
    protected array $validationRules = [
        'product_id' => 'required|exists:inventory_products,id',
        'warehouse_id' => 'required|exists:inventory_warehouses,id',
        'type' => 'required|in:in,out,transfer,adjustment',
        'quantity' => 'required|numeric|min:0.01',
        'unit_cost' => 'nullable|numeric|min:0',
        'total_cost' => 'nullable|numeric|min:0',
        'reference_type' => 'nullable|string|max:50',
        'reference_id' => 'nullable|integer',
        'description' => 'nullable|string',
        'created_by' => 'nullable|exists:users,id',
        'movement_date' => 'required|date',
    ];

    public function __construct()
    {
        parent::__construct(new StockMovement());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) return $error;

        $data = $request->all();
        $data['created_by'] = $data['created_by'] ?? auth()->id();

        $movement = StockMovement::create($data);
        return $this->respondCreated($movement, 'Stock movement recorded successfully');
    }

    public function index(Request $request): JsonResponse
    {
        $query = StockMovement::query()->with(['product', 'warehouse']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->get('warehouse_id'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->has('start_date')) {
            $query->where('movement_date', '>=', $request->get('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('movement_date', '<=', $request->get('end_date'));
        }

        $perPage = $request->get('per_page', 15);
        $items = $query->orderBy('movement_date', 'desc')->paginate(min($perPage, 100));

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

    public function byProduct(Request $request, int $productId): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $items = StockMovement::where('product_id', $productId)
            ->with('warehouse')
            ->orderBy('movement_date', 'desc')
            ->paginate(min($perPage, 100));

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