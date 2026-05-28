<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Sales\CrmPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmController extends BaseApiController
{
    protected array $validationRules = [
        'code' => 'required|string|max:20|unique:sales_crm_pipelines,code',
        'name' => 'required|string|max:200',
        'customer_id' => 'required|exists:sales_customers,id',
        'contact_name' => 'nullable|string|max:200',
        'contact_email' => 'nullable|email|max:200',
        'contact_phone' => 'nullable|string|max:20',
        'stage' => 'required|in:lead,qualified,proposal,negotiation,closed_won,closed_lost',
        'value' => 'nullable|numeric|min:0',
        'probability' => 'nullable|integer|min:0|max:100',
        'expected_close_date' => 'nullable|date',
        'notes' => 'nullable|string',
        'assigned_to' => 'nullable|exists:users,id',
        'status' => 'sometimes|in:active,inactive',
    ];

    protected array $validationMessages = [
        'code.required' => 'Pipeline code is required.',
        'code.unique' => 'This pipeline code is already in use.',
        'name.required' => 'Opportunity name is required.',
        'customer_id.required' => 'Customer is required.',
        'stage.required' => 'Stage is required.',
    ];

    public function __construct()
    {
        parent::__construct(new CrmPipeline());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'code' => 'required|string|max:20|unique:sales_crm_pipelines,code',
        ]));
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'active';

        $pipeline = CrmPipeline::create($data);
        return $this->respondCreated($pipeline, 'Pipeline entry created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $pipeline = CrmPipeline::find($id);
        if (!$pipeline) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'code' => 'required|string|max:20|unique:sales_crm_pipelines,code,' . $id,
            'name' => 'required|string|max:200',
            'customer_id' => 'required|exists:sales_customers,id',
            'contact_name' => 'nullable|string|max:200',
            'contact_email' => 'nullable|email|max:200',
            'contact_phone' => 'nullable|string|max:20',
            'stage' => 'required|in:lead,qualified,proposal,negotiation,closed_won,closed_lost',
            'value' => 'nullable|numeric|min:0',
            'probability' => 'nullable|integer|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'actual_close_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'sometimes|in:active,inactive',
        ]);
        if ($error) return $error;

        $pipeline->update($request->all());
        return $this->respondSuccess('Pipeline entry updated', $pipeline->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = CrmPipeline::query()->with(['customer', 'assignedTo']);

        if ($request->has('stage')) {
            $query->where('stage', $request->get('stage'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->get('assigned_to'));
        }

        $perPage = $request->get('per_page', 15);
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

    public function pipelineSummary(): JsonResponse
    {
        $stages = ['lead', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];
        $summary = [];

        foreach ($stages as $stage) {
            $items = CrmPipeline::where('stage', $stage)->where('status', 'active')->get();
            $summary[] = [
                'stage' => $stage,
                'count' => $items->count(),
                'total_value' => (float) $items->sum('value'),
            ];
        }

        return $this->respond([
            'success' => true,
            'data' => $summary,
        ]);
    }
}