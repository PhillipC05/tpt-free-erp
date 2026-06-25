<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Fleet\MaintenanceRecord;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceTrackingController extends BaseApiController
{
    public function dashboard(Request $request): JsonResponse
    {
        $overdueCount = MaintenanceRecord::where('status', 'scheduled')
            ->where('scheduled_date', '<', now()->toDateString())
            ->count();

        $upcomingCount = MaintenanceRecord::where('status', 'scheduled')
            ->where('scheduled_date', '>=', now()->toDateString())
            ->where('scheduled_date', '<=', now()->addDays(30)->toDateString())
            ->count();

        $inProgressCount = MaintenanceRecord::where('status', 'in_progress')->count();

        $totalSpent = MaintenanceRecord::where('status', 'completed')
            ->where('completed_date', '>=', now()->subYear()->toDateString())
            ->sum('cost');

        $overdueRecords = MaintenanceRecord::with(['vehicle'])
            ->where('status', 'scheduled')
            ->where('scheduled_date', '<', now()->toDateString())
            ->orderBy('scheduled_date')
            ->limit(10)
            ->get();

        $upcomingRecords = MaintenanceRecord::with(['vehicle'])
            ->where('status', 'scheduled')
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->limit(10)
            ->get();

        $recentCompleted = MaintenanceRecord::with(['vehicle'])
            ->where('status', 'completed')
            ->orderByDesc('completed_date')
            ->limit(10)
            ->get();

        $costByType = MaintenanceRecord::where('status', 'completed')
            ->where('completed_date', '>=', now()->subYear()->toDateString())
            ->select('type', DB::raw('count(*) as count'), DB::raw('sum(cost) as total_cost'), DB::raw('avg(cost) as avg_cost'))
            ->groupBy('type')
            ->get();

        $costByVehicle = MaintenanceRecord::where('status', 'completed')
            ->where('completed_date', '>=', now()->subYear()->toDateString())
            ->select('vehicle_id', DB::raw('count(*) as count'), DB::raw('sum(cost) as total_cost'))
            ->groupBy('vehicle_id')
            ->with('vehicle:id,vehicle_code,make,model')
            ->orderByDesc('total_cost')
            ->limit(10)
            ->get();

        $monthlyCost = MaintenanceRecord::where('status', 'completed')
            ->where('completed_date', '>=', now()->subMonths(12)->toDateString())
            ->selectRaw("strftime('%Y-%m', completed_date) as month, sum(cost) as cost, count(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'summary' => [
                    'overdue_count' => $overdueCount,
                    'upcoming_count' => $upcomingCount,
                    'in_progress_count' => $inProgressCount,
                    'total_spent_year' => round($totalSpent, 2),
                ],
                'overdue_records' => $overdueRecords,
                'upcoming_records' => $upcomingRecords,
                'recent_completed' => $recentCompleted,
                'cost_by_type' => $costByType,
                'cost_by_vehicle' => $costByVehicle,
                'monthly_cost' => $monthlyCost,
            ],
        ]);
    }

    public function vehicleHistory(Request $request): JsonResponse
    {
        $vehicleId = $request->query('vehicle_id');
        if (! $vehicleId) {
            return $this->respondError('vehicle_id is required', 422);
        }

        $vehicle = Vehicle::find($vehicleId);
        if (! $vehicle) {
            return $this->respondNotFound('Vehicle not found');
        }

        $records = MaintenanceRecord::where('vehicle_id', $vehicleId)
            ->orderByDesc('scheduled_date')
            ->get();

        $totalCost = $records->where('status', 'completed')->sum('cost');
        $lastService = $records->where('status', 'completed')->first();

        $typeBreakdown = $records->groupBy('type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_cost' => round($group->where('status', 'completed')->sum('cost'), 2),
            ];
        });

        $avgIntervalDays = null;
        $completedDates = $records->where('status', 'completed')
            ->pluck('completed_date')
            ->filter()
            ->sort()
            ->values();

        if ($completedDates->count() >= 2) {
            $intervals = [];
            for ($i = 1; $i < $completedDates->count(); $i++) {
                $intervals[] = $completedDates[$i]->diffInDays($completedDates[$i - 1]);
            }
            $avgIntervalDays = round(array_sum($intervals) / count($intervals));
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'vehicle' => $vehicle,
                'records' => $records,
                'total_cost' => round($totalCost, 2),
                'last_service_date' => $lastService?->completed_date,
                'last_service_odometer' => $lastService?->odometer_at_service,
                'avg_interval_days' => $avgIntervalDays,
                'type_breakdown' => $typeBreakdown,
            ],
        ]);
    }

    public function overdue(): JsonResponse
    {
        $records = MaintenanceRecord::with(['vehicle'])
            ->where('status', 'scheduled')
            ->where('scheduled_date', '<', now()->toDateString())
            ->orderBy('scheduled_date')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => $records,
        ]);
    }

    public function costReport(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->subYear()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $query = MaintenanceRecord::where('status', 'completed')
            ->where('completed_date', '>=', $startDate)
            ->where('completed_date', '<=', $endDate);

        $totalCost = (clone $query)->sum('cost');
        $totalCount = (clone $query)->count();
        $avgCost = $totalCount > 0 ? round($totalCost / $totalCount, 2) : 0;

        $byType = (clone $query)
            ->select('type', DB::raw('count(*) as count'), DB::raw('sum(cost) as total_cost'), DB::raw('avg(cost) as avg_cost'))
            ->groupBy('type')
            ->get();

        $byVehicle = (clone $query)
            ->select('vehicle_id', DB::raw('count(*) as count'), DB::raw('sum(cost) as total_cost'))
            ->groupBy('vehicle_id')
            ->with('vehicle:id,vehicle_code,make,model')
            ->orderByDesc('total_cost')
            ->get();

        $byProvider = (clone $query)
            ->whereNotNull('service_provider')
            ->select('service_provider', DB::raw('count(*) as count'), DB::raw('sum(cost) as total_cost'))
            ->groupBy('service_provider')
            ->orderByDesc('total_cost')
            ->get();

        $byMonth = (clone $query)
            ->selectRaw("strftime('%Y-%m', completed_date) as month, sum(cost) as cost, count(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_cost' => round($totalCost, 2),
                    'total_records' => $totalCount,
                    'avg_cost_per_service' => $avgCost,
                    'period' => ['start' => $startDate, 'end' => $endDate],
                ],
                'by_type' => $byType,
                'by_vehicle' => $byVehicle,
                'by_provider' => $byProvider,
                'by_month' => $byMonth,
            ],
        ]);
    }
}
