<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Fleet\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends BaseApiController
{
    protected string $cacheTag = 'fleet_drivers';

    protected array $validationRules = [
        'employee_id' => 'required|exists:hr_employees,id|unique:fleet_drivers,employee_id',
        'license_number' => 'required|string|max:50|unique:fleet_drivers,license_number',
        'license_class' => 'nullable|string|max:10',
        'license_expiry' => 'required|date|after:today',
        'license_fee' => 'nullable|numeric|min:0',
        'certifications' => 'nullable|string',
        'status' => 'sometimes|in:active,inactive,suspended',
    ];

    protected array $validationMessages = [
        'employee_id.required' => 'Employee is required.',
        'employee_id.unique' => 'This employee is already registered as a driver.',
        'license_number.required' => 'License number is required.',
        'license_number.unique' => 'This license number is already registered.',
        'license_expiry.required' => 'License expiry date is required.',
        'license_expiry.after' => 'License expiry date must be in the future.',
    ];

    public function __construct()
    {
        parent::__construct(new Driver);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'active';

        $driver = Driver::create($data);

        return $this->respondCreated($driver->fresh(['employee']), 'Driver registered successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $driver = Driver::find($id);
        if (! $driver) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'employee_id' => 'required|exists:hr_employees,id|unique:fleet_drivers,employee_id,'.$id,
            'license_number' => 'required|string|max:50|unique:fleet_drivers,license_number,'.$id,
            'license_class' => 'nullable|string|max:10',
            'license_expiry' => 'required|date',
            'license_fee' => 'nullable|numeric|min:0',
            'certifications' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,suspended',
        ]);
        if ($error) {
            return $error;
        }

        $driver->update($request->all());

        return $this->respondSuccess('Driver updated', $driver->fresh(['employee']));
    }

    public function index(Request $request): JsonResponse
    {
        $query = Driver::query()->with(['employee']);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('license_number', 'like', "%{$search}%")
                    ->orWhere('license_class', 'like', "%{$search}%");
            });
        }

        if ($request->has('expiring_soon')) {
            $days = (int) $request->query('expiring_soon', 30);
            $query->where('license_expiry', '<=', now()->addDays($days))
                ->where('license_expiry', '>=', now());
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
        $driver = Driver::with(['employee', 'trips.vehicle'])->find($id);
        if (! $driver) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $driver]);
    }
}
