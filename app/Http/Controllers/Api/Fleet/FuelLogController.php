<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Fleet\FuelLog;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FuelLogController extends BaseApiController
{
    protected string $cacheTag = 'fleet_fuel_logs';

    public function __construct()
    {
        parent::__construct(new FuelLog);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'vehicle_id' => 'required|exists:fleet_vehicles,id',
            'trip_id' => 'nullable|exists:fleet_trips,id',
            'date' => 'required|date',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'required|numeric|min:0',
            'fuel_type' => 'required|in:gasoline,diesel,electric,hybrid,other',
            'odometer' => 'required|numeric|min:0',
            'station' => 'nullable|string|max:200',
            'receipt_number' => 'nullable|string|max:100',
        ]);
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['total_cost'] = $data['quantity'] * $data['unit_cost'];
        $data['logged_by'] = Auth::id();

        $fuelLog = FuelLog::create($data);

        $vehicle = Vehicle::find($data['vehicle_id']);
        if ($vehicle && $data['odometer'] > $vehicle->current_odometer) {
            $vehicle->update(['current_odometer' => $data['odometer']]);
        }

        return $this->respondCreated($fuelLog->fresh(['vehicle', 'trip']), 'Fuel log recorded');
    }

    public function index(Request $request): JsonResponse
    {
        $query = FuelLog::query()->with(['vehicle', 'trip']);

        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->query('vehicle_id'));
        }

        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->query('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->query('end_date'));
        }

        if ($request->has('fuel_type')) {
            $query->where('fuel_type', $request->query('fuel_type'));
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('date', 'desc')->paginate(min($perPage, 100));

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
        $fuelLog = FuelLog::with(['vehicle', 'trip', 'logger'])->find($id);
        if (! $fuelLog) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $fuelLog]);
    }

    public function destroy(int $id): JsonResponse
    {
        $fuelLog = FuelLog::find($id);
        if (! $fuelLog) {
            return $this->respondNotFound();
        }

        $fuelLog->delete();

        return $this->respondSuccess('Fuel log deleted');
    }
}
