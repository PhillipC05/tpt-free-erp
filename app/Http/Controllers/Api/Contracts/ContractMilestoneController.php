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

    public function listMilestones(int $contract): JsonResponse
    {
        $contractModel = Contract::find($contract);
        if (!$contractModel) {
            return $this->respondNotFound('Contract not found');
        }

        $milestones = $contractModel->milestones()->orderBy('due_date')->get();

        return $this->respond(['success' => true, 'data' => $milestones]);
    }

    public function createMilestone(Request $request, int $contract): JsonResponse
    {
        $contractModel = Contract::find($contract);
        if (!$contractModel) {
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

        $milestone = $contractModel->milestones()->create($request->only([
            'title', 'description', 'due_date', 'payment_amount', 'is_completed',
        ]));

        $this->cacheFlush();

        return $this->respondCreated($milestone);
    }

    public function getMilestone(int $contract, int $milestone): JsonResponse
    {
        $record = ContractMilestone::where('contract_id', $contract)->find($milestone);
        if (!$record) {
            return $this->respondNotFound('Milestone not found');
        }

        return $this->respond(['success' => true, 'data' => $record]);
    }

    public function updateMilestone(Request $request, int $contract, int $milestone): JsonResponse
    {
        $record = ContractMilestone::where('contract_id', $contract)->find($milestone);
        if (!$record) {
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

        $record->update($request->only([
            'title', 'description', 'due_date', 'payment_amount', 'is_completed',
        ]));

        if ($request->boolean('is_completed') && !$record->completed_at) {
            $record->update(['completed_at' => now()]);
        }

        $this->cacheFlush();

        return $this->respondSuccess('Milestone updated successfully', $record->fresh());
    }

    public function completeMilestone(int $contract, int $milestone): JsonResponse
    {
        $record = ContractMilestone::where('contract_id', $contract)->find($milestone);
        if (!$record) {
            return $this->respondNotFound('Milestone not found');
        }

        $record->update([
            'is_completed' => true,
            'completed_at' => $record->completed_at ?? now(),
        ]);

        $this->cacheFlush();

        return $this->respondSuccess('Milestone marked as complete', $record->fresh());
    }

    public function deleteMilestone(int $contract, int $milestone): JsonResponse
    {
        $record = ContractMilestone::where('contract_id', $contract)->find($milestone);
        if (!$record) {
            return $this->respondNotFound('Milestone not found');
        }

        $record->delete();
        $this->cacheFlush();

        return $this->respondSuccess('Milestone deleted successfully');
    }
}
