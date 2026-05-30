<?php

namespace App\Http\Controllers\Api\Quality;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Quality\NonConformance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NonConformanceController extends BaseApiController
{
    protected array $validationRules = [
        'nc_number' => 'required|string|max:50|unique:quality_non_conformances,nc_number',
        'check_id' => 'nullable|exists:quality_checks,id',
        'description' => 'required|string',
        'severity' => 'required|in:minor,major,critical',
        'status' => 'sometimes|in:open,under_review,resolved,closed',
        'root_cause' => 'nullable|string',
        'corrective_action' => 'nullable|string',
        'assigned_to' => 'nullable|exists:hr_employees,id',
        'target_resolution_date' => 'nullable|date',
    ];

    public function __construct()
    {
        parent::__construct(new NonConformance());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'open';

        $nc = NonConformance::create($data);
        return $this->respondCreated($nc, 'Non-conformance created successfully');
    }

    public function index(Request $request): JsonResponse
    {
        $query = NonConformance::query()->with(['assignedTo']);

        if ($request->has('severity')) {
            $query->where('severity', $request->query('severity'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('check_id')) {
            $query->where('check_id', $request->query('check_id'));
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

    public function updateStatus(Request $request, int $nc): JsonResponse
    {
        $record = NonConformance::find($nc);
        if (!$record) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'status' => 'required|in:open,under_review,resolved,closed',
        ]);
        if ($error) return $error;

        $updates = ['status' => $request->query('status')];
        if ($request->query('status') === 'resolved') {
            $updates['resolved_at'] = now();
        }

        $record->update($updates);
        return $this->respondSuccess('Status updated', $record->fresh());
    }
}
