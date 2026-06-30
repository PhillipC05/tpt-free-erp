<?php

namespace App\Http\Controllers\Api\Quality;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Quality\QualityCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QualityCheckController extends BaseApiController
{
    protected array $validationRules = [
        'check_code' => 'required|string|max:50|unique:quality_checks,check_code',
        'product_id' => 'required|exists:inventory_products,id',
        'reference_type' => 'nullable|string|max:50',
        'reference_id' => 'nullable|integer',
        'type' => 'required|in:incoming,in_process,finished,dispatch',
        'result' => 'required|in:pass,fail,conditional',
        'notes' => 'nullable|string',
        'inspected_by' => 'nullable|exists:hr_employees,id',
        'inspected_at' => 'nullable|date',
    ];

    protected array $validationMessages = [
        'check_code.required' => 'Check code is required.',
        'check_code.unique' => 'This check code is already in use.',
        'product_id.required' => 'Product is required.',
        'type.required' => 'Check type is required.',
        'result.required' => 'Result is required.',
    ];

    public function __construct()
    {
        parent::__construct(new QualityCheck);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'check_code' => 'required|string|max:50|unique:quality_checks,check_code',
        ]));
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['inspected_at'] = $data['inspected_at'] ?? now();

        $check = QualityCheck::create($data);

        return $this->respondCreated($check, 'Quality check created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $check = QualityCheck::find($id);
        if (! $check) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'check_code' => 'required|string|max:50|unique:quality_checks,check_code,'.$id,
            'product_id' => 'required|exists:inventory_products,id',
            'reference_type' => 'nullable|string|max:50',
            'reference_id' => 'nullable|integer',
            'type' => 'required|in:incoming,in_process,finished,dispatch',
            'result' => 'required|in:pass,fail,conditional',
            'notes' => 'nullable|string',
            'inspected_by' => 'nullable|exists:hr_employees,id',
            'inspected_at' => 'nullable|date',
        ]);
        if ($error) {
            return $error;
        }

        $check->update($request->all());

        return $this->respondSuccess('Quality check updated', $check->fresh());
    }

    public function show(int $id): JsonResponse
    {
        $check = QualityCheck::with(['product', 'inspector', 'items', 'nonConformances'])->find($id);
        if (! $check) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $check]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = QualityCheck::query()->with(['product', 'inspector']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->query('product_id'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->has('result')) {
            $query->where('result', $request->query('result'));
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
}
