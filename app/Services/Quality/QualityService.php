<?php

namespace App\Services\Quality;

use App\Models\Quality\QualityCheck;
use App\Models\Quality\QualityCheckItem;
use App\Models\Quality\NonConformance;
use Illuminate\Support\Collection;

class QualityService
{
    public function createCheck(array $data): QualityCheck
    {
        $data['inspected_at'] = $data['inspected_at'] ?? now();
        return QualityCheck::create($data);
    }

    public function recordResult(QualityCheck $check, string $result): QualityCheck
    {
        $check->update(['result' => $result]);
        return $check->fresh();
    }

    public function addCheckItem(QualityCheck $check, array $data): QualityCheckItem
    {
        return $check->items()->create($data);
    }

    public function createNonConformance(array $data): NonConformance
    {
        return NonConformance::create($data);
    }

    public function resolveNonConformance(NonConformance $nc, string $rootCause, string $correctiveAction): NonConformance
    {
        $nc->update([
            'root_cause' => $rootCause,
            'corrective_action' => $correctiveAction,
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
        return $nc->fresh();
    }

    public function closeNonConformance(NonConformance $nc): NonConformance
    {
        $nc->update(['status' => 'closed']);
        return $nc->fresh();
    }
}