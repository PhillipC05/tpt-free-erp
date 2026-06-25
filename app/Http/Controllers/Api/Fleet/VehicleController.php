<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends BaseApiController
{
    protected string $cacheTag = 'fleet_vehicles';

    protected array $validationRules = [
        'vehicle_code' => 'required|string|max:20|unique:fleet_vehicles,vehicle_code',
        'make' => 'required|string|max:100',
        'model' => 'required|string|max:100',
        'year' => 'required|integer|min:1900|max:2030',
        'vin' => 'nullable|string|max:17|unique:fleet_vehicles,vin',
        'license_plate' => 'required|string|max:20|unique:fleet_vehicles,license_plate',
        'color' => 'nullable|string|max:50',
        'type' => 'sometimes|in:car,truck,van,motorcycle,bus,trailer,other',
        'fuel_type' => 'sometimes|in:gasoline,diesel,electric,hybrid,other',
        'current_odometer' => 'nullable|numeric|min:0',
        'fuel_capacity' => 'nullable|numeric|min:0',
        'fuel_level' => 'nullable|numeric|min:0|max:100',
        'status' => 'sometimes|in:active,inactive,maintenance,retired',
        'assigned_driver_id' => 'nullable|exists:hr_employees,id',
        'warehouse_id' => 'nullable|exists:inventory_warehouses,id',
        'registration_expiry' => 'nullable|date',
        'insurance_expiry' => 'nullable|date',
        'notes' => 'nullable|string',
    ];

    protected array $validationMessages = [
        'vehicle_code.required' => 'Vehicle code is required.',
        'vehicle_code.unique' => 'This vehicle code is already in use.',
        'make.required' => 'Vehicle make is required.',
        'model.required' => 'Vehicle model is required.',
        'year.required' => 'Vehicle year is required.',
        'license_plate.required' => 'License plate is required.',
        'license_plate.unique' => 'This license plate is already registered.',
    ];

    public function __construct()
    {
        parent::__construct(new Vehicle);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'active';

        $vehicle = Vehicle::create($data);

        return $this->respondCreated($vehicle, 'Vehicle created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vehicle = Vehicle::find($id);
        if (! $vehicle) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'vehicle_code' => 'required|string|max:20|unique:fleet_vehicles,vehicle_code,'.$id,
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:2030',
            'vin' => 'nullable|string|max:17|unique:fleet_vehicles,vin,'.$id,
            'license_plate' => 'required|string|max:20|unique:fleet_vehicles,license_plate,'.$id,
            'color' => 'nullable|string|max:50',
            'type' => 'sometimes|in:car,truck,van,motorcycle,bus,trailer,other',
            'fuel_type' => 'sometimes|in:gasoline,diesel,electric,hybrid,other',
            'current_odometer' => 'nullable|numeric|min:0',
            'fuel_capacity' => 'nullable|numeric|min:0',
            'fuel_level' => 'nullable|numeric|min:0|max:100',
            'status' => 'sometimes|in:active,inactive,maintenance,retired',
            'assigned_driver_id' => 'nullable|exists:hr_employees,id',
            'warehouse_id' => 'nullable|exists:inventory_warehouses,id',
            'registration_expiry' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $vehicle->update($request->all());

        return $this->respondSuccess('Vehicle updated', $vehicle->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::query()->with(['assignedDriver', 'warehouse']);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('make', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('vehicle_code', 'like', "%{$search}%")
                    ->orWhere('license_plate', 'like', "%{$search}%");
            });
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->paginate(min($perPage, 100));

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
        $vehicle = Vehicle::with(['assignedDriver', 'warehouse', 'trips', 'fuelLogs', 'maintenanceRecords'])->find($id);
        if (! $vehicle) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $vehicle]);
    }
}
