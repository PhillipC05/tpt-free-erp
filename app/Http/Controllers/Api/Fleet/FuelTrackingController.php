<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Fleet\FuelLog;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FuelTrackingController extends BaseApiController
{
    public function dashboard(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->subMonths(3)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $baseQuery = FuelLog::query()
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate);

        $totalCost = (clone $baseQuery)->sum('total_cost');
        $totalQuantity = (clone $baseQuery)->sum('quantity');
        $totalRefuels = (clone $baseQuery)->count();
        $avgCostPerLiter = $totalQuantity > 0 ? round($totalCost / $totalQuantity, 4) : 0;

        $monthlyTrend = (clone $baseQuery)
            ->selectRaw("strftime('%Y-%m', date) as month, sum(total_cost) as cost, sum(quantity) as quantity, count(*) as refuels")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $costByFuelType = (clone $baseQuery)
            ->select('fuel_type', DB::raw('sum(total_cost) as cost'), DB::raw('sum(quantity) as quantity'))
            ->groupBy('fuel_type')
            ->get();

        $topStations = (clone $baseQuery)
            ->whereNotNull('station')
            ->select('station', DB::raw('count(*) as visits'), DB::raw('sum(total_cost) as total_spent'), DB::raw('avg(unit_cost) as avg_price'))
            ->groupBy('station')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        $recentLogs = FuelLog::query()
            ->with(['vehicle:id,vehicle_code,make,model'])
            ->where('date', '>=', $startDate)
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_cost' => $totalCost,
                    'total_quantity' => round($totalQuantity, 2),
                    'total_refuels' => $totalRefuels,
                    'avg_cost_per_liter' => $avgCostPerLiter,
                    'period' => ['start' => $startDate, 'end' => $endDate],
                ],
                'monthly_trend' => $monthlyTrend,
                'cost_by_fuel_type' => $costByFuelType,
                'top_stations' => $topStations,
                'recent_logs' => $recentLogs,
            ],
        ]);
    }

    public function vehicleEfficiency(Request $request): JsonResponse
    {
        $vehicleId = $request->query('vehicle_id');
        if (! $vehicleId) {
            return $this->respondError('vehicle_id is required', 422);
        }

        $vehicle = Vehicle::find($vehicleId);
        if (! $vehicle) {
            return $this->respondNotFound('Vehicle not found');
        }

        $logs = FuelLog::where('vehicle_id', $vehicleId)
            ->orderBy('date')
            ->orderBy('odometer')
            ->get();

        if ($logs->count() < 2) {
            return $this->respond([
                'success' => true,
                'data' => [
                    'vehicle' => $vehicle,
                    'efficiency_records' => collect(),
                    'average_efficiency' => null,
                    'total_fuel_cost' => $logs->sum('total_cost'),
                    'total_fuel_quantity' => round($logs->sum('quantity'), 2),
                    'total_distance_km' => 0,
                ],
            ]);
        }

        $efficiencyRecords = collect();
        $prevOdometer = null;
        $fuelSinceLast = 0;
        $fuelSinceLastCost = 0;

        foreach ($logs as $log) {
            if ($prevOdometer !== null && $log->odometer > $prevOdometer) {
                $distance = $log->odometer - $prevOdometer;
                $fuelSinceLast += $log->quantity;
                $fuelSinceLastCost += $log->total_cost;

                if ($fuelSinceLast > 0) {
                    $kmPerLiter = round($distance / $fuelSinceLast, 2);
                    $litersPer100km = round(($fuelSinceLast / $distance) * 100, 2);
                    $costPerKm = round($fuelSinceLastCost / $distance, 4);

                    $efficiencyRecords->push([
                        'date' => $log->date,
                        'odometer' => $log->odometer,
                        'distance_km' => round($distance, 1),
                        'fuel_used' => round($fuelSinceLast, 2),
                        'cost' => round($fuelSinceLastCost, 2),
                        'km_per_liter' => $kmPerLiter,
                        'liters_per_100km' => $litersPer100km,
                        'cost_per_km' => $costPerKm,
                    ]);

                    $fuelSinceLast = 0;
                    $fuelSinceLastCost = 0;
                }
            }

            $prevOdometer = $log->odometer;
        }

        $totalDistance = $logs->last()->odometer - $logs->first()->odometer;
        $totalFuel = $logs->sum('quantity');
        $totalCost = $logs->sum('total_cost');

        return $this->respond([
            'success' => true,
            'data' => [
                'vehicle' => $vehicle,
                'efficiency_records' => $efficiencyRecords,
                'average_efficiency' => [
                    'km_per_liter' => $totalFuel > 0 ? round($totalDistance / $totalFuel, 2) : null,
                    'liters_per_100km' => $totalDistance > 0 ? round(($totalFuel / $totalDistance) * 100, 2) : null,
                    'cost_per_km' => $totalDistance > 0 ? round($totalCost / $totalDistance, 4) : null,
                ],
                'total_fuel_cost' => round($totalCost, 2),
                'total_fuel_quantity' => round($totalFuel, 2),
                'total_distance_km' => round($totalDistance, 1),
            ],
        ]);
    }

    public function consumptionByVehicle(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->subMonths(6)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $data = FuelLog::where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->select('vehicle_id', DB::raw('sum(quantity) as total_fuel'), DB::raw('sum(total_cost) as total_cost'), DB::raw('count(*) as refuel_count'))
            ->groupBy('vehicle_id')
            ->with('vehicle:id,vehicle_code,make,model')
            ->orderByDesc('total_cost')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function priceHistory(Request $request): JsonResponse
    {
        $fuelType = $request->query('fuel_type', 'gasoline');
        $startDate = $request->query('start_date', now()->subMonths(6)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $data = FuelLog::where('fuel_type', $fuelType)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->selectRaw('date, avg(unit_cost) as avg_price, min(unit_cost) as min_price, max(unit_cost) as max_price, count(*) as samples')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $overall = FuelLog::where('fuel_type', $fuelType)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->selectRaw('avg(unit_cost) as avg_price, min(unit_cost) as min_price, max(unit_cost) as max_price')
            ->first();

        return $this->respond([
            'success' => true,
            'data' => [
                'fuel_type' => $fuelType,
                'overall' => $overall,
                'daily_prices' => $data,
            ],
        ]);
    }
}
