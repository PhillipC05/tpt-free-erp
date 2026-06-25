<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Fleet\MaintenanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends BaseApiController
{
    protected string $cacheTag = 'fleet_maintenance';

    protected array $validationRules = [
        'vehicle_id' => 'required|exists:fleet_vehicles,id',
        'type' => 'required|in:preventive,corrective,emergency,inspection',
        'title' => 'required|string|max:200',
        'description' => 'nullable|string',
        'scheduled_date' => 'nullable|date',
        'completed_date' => 'nullable|date',
        'cost' => 'nullable|numeric|min:0',
        'service_provider' => 'nullable|string|max:200',
        'odometer_at_service' => 'nullable|numeric|min:0',
        'status' => 'sometimes|in:scheduled,in_progress,completed,cancelled',
        'notes' => 'nullable|string',
    ];

    protected array $validationMessages = [
        'vehicle_id.required' => 'Vehicle is required.',
        'type.required' => 'Maintenance type is required.',
        'title.required' => 'Title is required.',
    ];

    public function __construct()
    {
        parent::__construct(new MaintenanceRecord);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'scheduled';

        $record = MaintenanceRecord::create($data);

        return $this->respondCreated($record->fresh(['vehicle']), 'Maintenance record created');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $record = MaintenanceRecord::find($id);
        if (! $record) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'vehicle_id' => 'required|exists:fleet_vehicles,id',
            'type' => 'required|in:preventive,corrective,emergency,inspection',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'scheduled_date' => 'nullable|date',
            'completed_date' => 'nullable|date',
            'cost' => 'nullable|numeric|min:0',
            'service_provider' => 'nullable|string|max:200',
            'odometer_at_service' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:scheduled,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $record->update($request->all());

        return $this->respondSuccess('Maintenance record updated', $record->fresh());
    }

    public function complete(int $id): JsonResponse
    {
        $record = MaintenanceRecord::find($id);
        if (! $record) {
            return $this->respondNotFound();
        }

        if ($record->status === 'completed') {
            return $this->respondError('Maintenance is already completed', 422);
        }

        $record->update([
            'status' => 'completed',
            'completed_date' => now()->toDateString(),
        ]);

        return $this->respondSuccess('Maintenance marked complete', $record->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = MaintenanceRecord::query()->with(['vehicle']);

        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->query('vehicle_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('created_at', 'desc')->paginate(min($perPage, 100));

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
        $record = MaintenanceRecord::with(['vehicle'])->find($id);
        if (! $record) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $record]);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = MaintenanceRecord::find($id);
        if (! $record) {
            return $this->respondNotFound();
        }

        $record->delete();

        return $this->respondSuccess('Maintenance record deleted');
    }
}
