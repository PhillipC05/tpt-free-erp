<?php

namespace App\Http\Controllers\Api\Asset;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Assets\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetController extends BaseApiController
{
    protected array $validationRules = [
        'asset_code' => 'required|string|max:50|unique:assets,asset_code',
        'name' => 'required|string|max:200',
        'description' => 'nullable|string',
        'type' => 'required|string|max:100',
        'serial_number' => 'nullable|string|max:100|unique:assets,serial_number',
        'purchase_date' => 'required|date',
        'purchase_cost' => 'required|numeric|min:0',
        'current_value' => 'nullable|numeric|min:0',
        'salvage_value' => 'nullable|numeric|min:0',
        'useful_life_years' => 'nullable|integer|min:1',
        'depreciation_method' => 'nullable|in:straight_line,declining_balance,sum_of_years,units_of_production',
        'status' => 'sometimes|in:active,in_use,under_maintenance,retired,disposed',
        'assigned_to' => 'nullable|exists:hr_employees,id',
        'location_id' => 'nullable|exists:inventory_warehouses,id',
    ];

    public function __construct()
    {
        parent::__construct(new Asset());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'active';
        $data['current_value'] = $data['current_value'] ?? $data['purchase_cost'];

        $asset = Asset::create($data);
        return $this->respondCreated($asset, 'Asset created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $asset = Asset::find($id);
        if (!$asset) return $this->respondNotFound();

        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'asset_code' => 'required|string|max:50|unique:assets,asset_code,' . $id,
            'serial_number' => 'nullable|string|max:100|unique:assets,serial_number,' . $id,
        ]));
        if ($error) return $error;

        $asset->update($request->all());
        return $this->respondSuccess('Asset updated', $asset->fresh());
    }

    public function show(int $id): JsonResponse
    {
        $asset = Asset::with(['assignedTo', 'location', 'maintenanceRecords'])->find($id);
        if (!$asset) return $this->respondNotFound();

        return $this->respond(['success' => true, 'data' => $asset]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Asset::query()->with(['assignedTo', 'location']);

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->query('assigned_to'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('purchase_date', 'desc')->paginate(min($perPage, 100));

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

    public function calculateDepreciation(int $id): JsonResponse
    {
        $asset = Asset::find($id);
        if (!$asset) return $this->respondNotFound();

        if (!$asset->useful_life_years || !$asset->depreciation_method) {
            return $this->respondError('Asset is missing depreciation configuration', 422);
        }

        if ($asset->depreciation_method === 'straight_line') {
            $annualDepreciation = ($asset->purchase_cost - ($asset->salvage_value ?? 0)) / $asset->useful_life_years;
        } else {
            return $this->respondError('Depreciation method not yet implemented', 501);
        }

        $newValue = max($asset->current_value - $annualDepreciation, $asset->salvage_value ?? 0);
        $asset->update(['current_value' => $newValue]);

        return $this->respondSuccess('Depreciation calculated', $asset->fresh());
    }
}
