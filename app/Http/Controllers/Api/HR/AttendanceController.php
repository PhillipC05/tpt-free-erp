<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\HR\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends BaseApiController
{
    protected array $validationRules = [
        'employee_id' => 'required|exists:hr_employees,id',
        'date' => 'required|date',
        'clock_in' => 'nullable|date_format:H:i:s',
        'clock_out' => 'nullable|date_format:H:i:s',
        'status' => 'sometimes|in:present,absent,late,half_day,holiday',
        'notes' => 'nullable|string',
    ];

    public function __construct()
    {
        parent::__construct(new Attendance());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Attendance::query()->with(['employee']);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->query('employee_id'));
        }

        if ($request->has('date')) {
            $query->whereDate('date', $request->query('date'));
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

    public function byEmployee(int $employee): JsonResponse
    {
        $records = Attendance::where('employee_id', $employee)
            ->orderBy('date', 'desc')
            ->get();

        return $this->respond(['success' => true, 'data' => $records]);
    }

    public function clockIn(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'employee_id' => 'required|exists:hr_employees,id',
        ]);
        if ($error) return $error;

        $today = now()->toDateString();
        $existing = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('date', $today)
            ->first();

        if ($existing && $existing->clock_in) {
            return $this->respondError('Employee has already clocked in today', 422);
        }

        $record = $existing
            ? tap($existing)->update(['clock_in' => now()->toTimeString(), 'status' => 'present'])
            : Attendance::create([
                'employee_id' => $request->employee_id,
                'date' => $today,
                'clock_in' => now()->toTimeString(),
                'status' => 'present',
            ]);

        return $this->respondSuccess('Clocked in successfully', $record->fresh());
    }

    public function clockOut(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'employee_id' => 'required|exists:hr_employees,id',
        ]);
        if ($error) return $error;

        $today = now()->toDateString();
        $record = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('date', $today)
            ->first();

        if (!$record || !$record->clock_in) {
            return $this->respondError('No clock-in record found for today', 422);
        }

        if ($record->clock_out) {
            return $this->respondError('Employee has already clocked out today', 422);
        }

        $clockOut = now()->toTimeString();
        $totalHours = round(
            (strtotime($clockOut) - strtotime($record->clock_in)) / 3600,
            2
        );

        $record->update(['clock_out' => $clockOut, 'total_hours' => $totalHours]);

        return $this->respondSuccess('Clocked out successfully', $record->fresh());
    }
}
