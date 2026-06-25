<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Fleet\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TripController extends BaseApiController
{
    protected string $cacheTag = 'fleet_trips';

    public function __construct()
    {
        parent::__construct(new Trip);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'vehicle_id' => 'required|exists:fleet_vehicles,id',
            'driver_id' => 'required|exists:fleet_drivers,id',
            'start_location' => 'required|string|max:300',
            'start_odometer' => 'required|numeric|min:0',
            'start_time' => 'required|date',
            'purpose' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['trip_number'] = Trip::generateTripNumber();
        $data['created_by'] = Auth::id();
        $data['status'] = 'scheduled';

        $trip = Trip::create($data);

        return $this->respondCreated($trip->fresh(['vehicle', 'driver']), 'Trip created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $trip = Trip::find($id);
        if (! $trip) {
            return $this->respondNotFound();
        }

        if (in_array($trip->status, ['completed', 'cancelled'])) {
            return $this->respondError('Cannot update a completed or cancelled trip', 422);
        }

        $error = $this->validate($request->all(), [
            'vehicle_id' => 'required|exists:fleet_vehicles,id',
            'driver_id' => 'required|exists:fleet_drivers,id',
            'start_location' => 'required|string|max:300',
            'end_location' => 'nullable|string|max:300',
            'start_odometer' => 'required|numeric|min:0',
            'end_odometer' => 'nullable|numeric|min:0',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date',
            'status' => 'sometimes|in:scheduled,in_progress,completed,cancelled',
            'purpose' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $data = $request->all();
        if (isset($data['end_odometer']) && isset($data['start_odometer'])) {
            $data['distance'] = max(0, $data['end_odometer'] - $data['start_odometer']);
        }
        if ($data['status'] ?? null === 'completed') {
            $data['end_time'] = $data['end_time'] ?? now();
        }

        $trip->update($data);

        return $this->respondSuccess('Trip updated', $trip->fresh());
    }

    public function start(int $id): JsonResponse
    {
        $trip = Trip::find($id);
        if (! $trip) {
            return $this->respondNotFound();
        }

        if ($trip->status !== 'scheduled') {
            return $this->respondError('Only scheduled trips can be started', 422);
        }

        $trip->update(['status' => 'in_progress', 'start_time' => now()]);

        return $this->respondSuccess('Trip started', $trip->fresh());
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $trip = Trip::find($id);
        if (! $trip) {
            return $this->respondNotFound();
        }

        if ($trip->status !== 'in_progress') {
            return $this->respondError('Only in-progress trips can be completed', 422);
        }

        $error = $this->validate($request->all(), [
            'end_location' => 'required|string|max:300',
            'end_odometer' => 'required|numeric|gt:'.$trip->start_odometer,
        ]);
        if ($error) {
            return $error;
        }

        $distance = $request->input('end_odometer') - $trip->start_odometer;

        $trip->update([
            'status' => 'completed',
            'end_location' => $request->input('end_location'),
            'end_odometer' => $request->input('end_odometer'),
            'distance' => $distance,
            'end_time' => now(),
        ]);

        $vehicle = $trip->vehicle;
        if ($vehicle) {
            $vehicle->update(['current_odometer' => $request->input('end_odometer')]);
        }

        return $this->respondSuccess('Trip completed', $trip->fresh());
    }

    public function cancel(int $id): JsonResponse
    {
        $trip = Trip::find($id);
        if (! $trip) {
            return $this->respondNotFound();
        }

        if (in_array($trip->status, ['completed', 'cancelled'])) {
            return $this->respondError('Cannot cancel a completed or already cancelled trip', 422);
        }

        $trip->update(['status' => 'cancelled']);

        return $this->respondSuccess('Trip cancelled', $trip->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Trip::query()->with(['vehicle', 'driver.employee']);

        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->query('vehicle_id'));
        }

        if ($request->has('driver_id')) {
            $query->where('driver_id', $request->query('driver_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('start_date')) {
            $query->where('start_time', '>=', $request->query('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('start_time', '<=', $request->query('end_date').' 23:59:59');
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('start_time', 'desc')->paginate(min($perPage, 100));

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
        $trip = Trip::with(['vehicle', 'driver.employee', 'fuelLogs', 'creator'])->find($id);
        if (! $trip) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $trip]);
    }
}
