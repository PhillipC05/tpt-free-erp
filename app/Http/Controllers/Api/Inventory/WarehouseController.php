<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends BaseApiController
{
    protected array $validationRules = [
        'code' => 'required|string|max:20|unique:inventory_warehouses,code',
        'name' => 'required|string|max:200',
        'address' => 'nullable|string',
        'city' => 'nullable|string|max:100',
        'country' => 'nullable|string|max:100',
        'is_active' => 'boolean',
    ];

    public function __construct()
    {
        parent::__construct(new Warehouse());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'code' => 'required|string|max:20|unique:inventory_warehouses,code',
        ]));
        if ($error) return $error;

        $warehouse = Warehouse::create($request->all());
        return $this->respondCreated($warehouse, 'Warehouse created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'code' => 'required|string|max:20|unique:inventory_warehouses,code,' . $id,
            'name' => 'required|string|max:200',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);
        if ($error) return $error;

        $warehouse->update($request->all());
        return $this->respondSuccess('Warehouse updated', $warehouse->fresh());
    }

    public function stock(int $id): JsonResponse
    {
        $warehouse = Warehouse::with('stock.product')->find($id);
        if (!$warehouse) return $this->respondNotFound();

        return $this->respond([
            'success' => true,
            'data' => [
                'warehouse' => $warehouse,
                'stock_items' => $warehouse->stock,
            ],
        ]);
    }
}