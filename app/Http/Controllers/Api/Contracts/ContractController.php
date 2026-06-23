<?php

namespace App\Http\Controllers\Api\Contracts;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Contracts\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends BaseApiController
{
    protected string $cacheTag = 'contracts';

    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $query = Contract::query();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->query('customer_id'));
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->query('vendor_id'));
        }

        $perPage = (int) $request->query('per_page', 15);
        $contracts = $query->orderBy('created_at', 'desc')->paginate(min($perPage, 100));

        return $this->respond([
            'success' => true,
            'data' => $contracts->items(),
            'meta' => [
                'current_page' => $contracts->currentPage(),
                'last_page' => $contracts->lastPage(),
                'per_page' => $contracts->perPage(),
                'total' => $contracts->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'title' => 'required|string|max:255',
            'contract_number' => 'required|string|max:100|unique:contracts,contract_number',
            'type' => 'required|string|in:sale,purchase,service,nda',
            'status' => 'nullable|string|in:draft,review,signed,active,expired,terminated',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'customer_id' => 'nullable|exists:sales_customers,id',
            'vendor_id' => 'nullable|exists:procurement_vendors,id',
            'project_id' => 'nullable|integer',
        ]);

        if ($error) {
            return $error;
        }

        $contract = Contract::create(array_merge($request->all(), [
            'created_by' => $request->user()->id,
        ]));

        $this->cacheFlush();

        return $this->respondCreated($contract);
    }

    public function show(int $id): JsonResponse
    {
        $contract = Contract::with('milestones')->find($id);

        if (!$contract) {
            return $this->respondNotFound('Contract not found');
        }

        return $this->respond(['success' => true, 'data' => $contract]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $contract = Contract::find($id);

        if (!$contract) {
            return $this->respondNotFound('Contract not found');
        }

        $error = $this->validate($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'contract_number' => 'sometimes|required|string|max:100|unique:contracts,contract_number,' . $id,
            'type' => 'sometimes|required|string|in:sale,purchase,service,nda',
            'status' => 'nullable|string|in:draft,review,signed,active,expired,terminated',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
        ]);

        if ($error) {
            return $error;
        }

        $contract->update($request->all());
        $this->cacheFlush();

        return $this->respondSuccess('Contract updated successfully', $contract);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $contract = Contract::find($id);

        if (!$contract) {
            return $this->respondNotFound('Contract not found');
        }

        $error = $this->validate($request->all(), [
            'status' => 'required|string|in:draft,review,signed,active,expired,terminated',
        ]);

        if ($error) {
            return $error;
        }

        $contract->update(['status' => $request->input('status')]);
        $this->cacheFlush();

        return $this->respondSuccess('Contract status updated', $contract);
    }

    public function sign(int $id): JsonResponse
    {
        $contract = Contract::find($id);

        if (!$contract) {
            return $this->respondNotFound('Contract not found');
        }

        $contract->update([
            'status' => 'signed',
            'signed_by' => request()->user()->id,
            'signed_at' => now(),
        ]);

        $this->cacheFlush();

        return $this->respondSuccess('Contract signed successfully', $contract);
    }

    public function destroy(int $id): JsonResponse
    {
        $contract = Contract::find($id);

        if (!$contract) {
            return $this->respondNotFound('Contract not found');
        }

        $contract->delete();
        $this->cacheFlush();

        return $this->respondSuccess('Contract deleted successfully');
    }
}
