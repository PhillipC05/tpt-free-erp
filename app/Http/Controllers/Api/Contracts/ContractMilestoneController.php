<?php

namespace App\Http\Controllers\Api\Contracts;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Contracts\Contract;
use App\Models\Contracts\ContractMilestone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractMilestoneController extends BaseApiController
{
    protected string $cacheTag = 'contracts';

    public function __construct()
    {
        parent::__construct();
    }

    public function index(int $contractId): JsonResponse
    {
        $contract = Contract::find($contractId);
        if (!$contract) {
            return $this->respondNotFound('Contract not found');
        }

        $milestones = $contract->milestones()->orderBy('due_date')->get();

        return $this->respond(['success' => true, 'data' => $milestones]);
    }

    public function store(Request $request, int $contractId): JsonResponse
    {
        $contract = Contract::find($contractId);
        if (!$contract) {
            return $this->respondNotFound('Contract not found');
        }

        $error = $this->validate($request->all(), [
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'due_date'       => 'nullable|date',
            'payment_amount' => 'nullable|numeric|min:0',
            'is_completed'   => 'nullable|boolean',
        ]);

        if ($error) {
            return $error;
        }

        $milestone = $contract->milestones()->create($request->only([
            'title', 'description', 'due_date', 'payment_amount', 'is_completed',
        ]));

        $this->cacheFlush();

        return $this->respondCreated($milestone);
    }

    public function show(int $contractId, int $milestoneId): JsonResponse
    {
        $milestone = ContractMilestone::where('contract_id', $contractId)->find($milestoneId);
        if (!$milestone) {
            return $this->respondNotFound('Milestone not found');
        }

        return $this->respond(['success' => true, 'data' => $milestone]);
    }

    public function update(Request $request, int $contractId, int $milestoneId): JsonResponse
    {
        $milestone = ContractMilestone::where('contract_id', $contractId)->find($milestoneId);
        if (!$milestone) {
            return $this->respondNotFound('Milestone not found');
        }

        $error = $this->validate($request->all(), [
            'title'          => 'sometimes|required|string|max:255',
            'description'    => 'nullable|string',
            'due_date'       => 'nullable|date',
            'payment_amount' => 'nullable|numeric|min:0',
            'is_completed'   => 'nullable|boolean',
        ]);

        if ($error) {
            return $error;
        }

        $milestone->update($request->only([
            'title', 'description', 'due_date', 'payment_amount', 'is_completed',
        ]));

        if ($request->boolean('is_completed') && !$milestone->wasChanged('completed_at')) {
            $milestone->update(['completed_at' => now()]);
        }

        $this->cacheFlush();

        return $this->respondSuccess('Milestone updated successfully', $milestone->fresh());
    }

    public function complete(int $contractId, int $milestoneId): JsonResponse
    {
        $milestone = ContractMilestone::where('contract_id', $contractId)->find($milestoneId);
        if (!$milestone) {
            return $this->respondNotFound('Milestone not found');
        }

        $milestone->update([
            'is_completed' => true,
            'completed_at' => $milestone->completed_at ?? now(),
        ]);

        $this->cacheFlush();

        return $this->respondSuccess('Milestone marked as complete', $milestone->fresh());
    }

    public function destroy(int $contractId, int $milestoneId): JsonResponse
    {
        $milestone = ContractMilestone::where('contract_id', $contractId)->find($milestoneId);
        if (!$milestone) {
            return $this->respondNotFound('Milestone not found');
        }

        $milestone->delete();
        $this->cacheFlush();

        return $this->respondSuccess('Milestone deleted successfully');
    }
}
