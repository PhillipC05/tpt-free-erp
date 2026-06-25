<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Fleet\Part;
use App\Models\Fleet\PartUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PartUsageController extends BaseApiController
{
    protected string $cacheTag = 'fleet_part_usages';

    public function __construct()
    {
        parent::__construct(new PartUsage);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'part_id' => 'required|exists:fleet_parts,id',
            'vehicle_id' => 'required|exists:fleet_vehicles,id',
            'maintenance_id' => 'nullable|exists:fleet_maintenance_records,id',
            'trip_id' => 'nullable|exists:fleet_trips,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'nullable|numeric|min:0',
            'used_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $part = Part::findOrFail($data['part_id']);

        if ($data['quantity'] > $part->quantity_on_hand) {
            return $this->respondError(
                "Insufficient stock. Available: {$part->quantity_on_hand}, requested: {$data['quantity']}",
                422
            );
        }

        $data['unit_cost'] = $data['unit_cost'] ?? $part->unit_cost;
        $data['total_cost'] = $data['quantity'] * $data['unit_cost'];
        $data['used_by'] = Auth::id();

        return DB::transaction(function () use ($data, $part) {
            $usage = PartUsage::create($data);

            $part->update([
                'quantity_on_hand' => $part->quantity_on_hand - $data['quantity'],
            ]);

            return $this->respondCreated($usage->fresh(['part', 'vehicle']), 'Part usage recorded');
        });
    }

    public function index(Request $request): JsonResponse
    {
        $query = PartUsage::query()->with(['part', 'vehicle', 'maintenance', 'user']);

        if ($request->has('part_id')) {
            $query->where('part_id', $request->query('part_id'));
        }

        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->query('vehicle_id'));
        }

        if ($request->has('start_date')) {
            $query->where('used_date', '>=', $request->query('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('used_date', '<=', $request->query('end_date'));
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('used_date', 'desc')->paginate(min($perPage, 100));

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
        $usage = PartUsage::with(['part', 'vehicle', 'maintenance', 'trip', 'user'])->find($id);
        if (! $usage) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $usage]);
    }

    public function summary(Request $request): JsonResponse
    {
        $query = PartUsage::query();

        if ($request->has('start_date')) {
            $query->where('used_date', '>=', $request->query('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('used_date', '<=', $request->query('end_date'));
        }

        $totalCost = (clone $query)->sum('total_cost');
        $totalQuantity = (clone $query)->sum('quantity');
        $usageCount = (clone $query)->count();

        $topParts = (clone $query)
            ->select('part_id', DB::raw('sum(quantity) as total_qty, sum(total_cost) as total_cost'))
            ->groupBy('part_id')
            ->orderByDesc('total_cost')
            ->limit(10)
            ->with('part:id,part_number,name')
            ->get();

        $costByVehicle = (clone $query)
            ->select('vehicle_id', DB::raw('sum(total_cost) as total_cost'))
            ->groupBy('vehicle_id')
            ->orderByDesc('total_cost')
            ->limit(10)
            ->with('vehicle:id,vehicle_code,make,model')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'total_cost' => $totalCost,
                'total_quantity' => $totalQuantity,
                'usage_count' => $usageCount,
                'top_parts' => $topParts,
                'cost_by_vehicle' => $costByVehicle,
            ],
        ]);
    }
}
