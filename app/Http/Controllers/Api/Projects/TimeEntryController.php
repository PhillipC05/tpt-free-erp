<?php

namespace App\Http\Controllers\Api\Projects;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Projects\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeEntryController extends BaseApiController
{
    protected array $validationRules = [
        'task_id' => 'required|exists:project_tasks,id',
        'user_id' => 'required|exists:users,id',
        'date' => 'required|date',
        'hours' => 'required|numeric|min:0.25|max:24',
        'description' => 'nullable|string',
        'is_billable' => 'boolean',
    ];

    public function __construct()
    {
        parent::__construct(new TimeEntry());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) return $error;

        $data = $request->all();
        $data['is_billable'] = $data['is_billable'] ?? true;

        $entry = TimeEntry::create($data);
        return $this->respondCreated($entry->load(['task', 'user']), 'Time entry created successfully');
    }

    public function index(Request $request): JsonResponse
    {
        $query = TimeEntry::query()->with(['task', 'user']);

        if ($request->has('task_id')) {
            $query->where('task_id', $request->query('task_id'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->query('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->query('end_date'));
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
}
