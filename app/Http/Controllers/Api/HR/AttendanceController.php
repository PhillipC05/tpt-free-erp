<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        parent::__construct(new Attendance);
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

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
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

    public function show(int $id): JsonResponse
    {
        $record = Attendance::with(['employee'])->find($id);
        if (! $record) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $record]);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) {
            return $error;
        }

        $existing = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('date', $request->date)
            ->first();

        if ($existing) {
            return $this->respondError('Attendance record already exists for this date', 422);
        }

        $record = Attendance::create($request->all());

        return $this->respondCreated($record->fresh(['employee']), 'Attendance recorded');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $record = Attendance::find($id);
        if (! $record) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'employee_id' => 'required|exists:hr_employees,id',
            'date' => 'required|date',
            'clock_in' => 'nullable|date_format:H:i:s',
            'clock_out' => 'nullable|date_format:H:i:s',
            'status' => 'sometimes|in:present,absent,late,half_day,holiday',
            'notes' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $data = $request->all();
        if (isset($data['clock_in']) && isset($data['clock_out'])) {
            $data['total_hours'] = round(
                (strtotime($data['clock_out']) - strtotime($data['clock_in'])) / 3600, 2
            );
        }

        $record->update($data);

        return $this->respondSuccess('Attendance updated', $record->fresh(['employee']));
    }

    public function destroy(int $id): JsonResponse
    {
        $record = Attendance::find($id);
        if (! $record) {
            return $this->respondNotFound();
        }

        $record->delete();

        return $this->respondSuccess('Attendance record deleted');
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
        if ($error) {
            return $error;
        }

        $today = now()->toDateString();
        $existing = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('date', $today)
            ->first();

        if ($existing && $existing->clock_in) {
            return $this->respondError('Employee has already clocked in today', 422);
        }

        $clockInTime = now()->toTimeString();
        $isLate = now()->hour >= 9 && now()->minute > 0;

        if ($existing) {
            $existing->update(['clock_in' => $clockInTime, 'status' => $isLate ? 'late' : 'present']);
            $record = $existing->fresh();
        } else {
            $record = Attendance::create([
                'employee_id' => $request->employee_id,
                'date' => $today,
                'clock_in' => $clockInTime,
                'status' => $isLate ? 'late' : 'present',
            ]);
        }

        return $this->respondSuccess('Clocked in at '.$clockInTime, $record);
    }

    public function clockOut(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'employee_id' => 'required|exists:hr_employees,id',
        ]);
        if ($error) {
            return $error;
        }

        $today = now()->toDateString();
        $record = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('date', $today)
            ->first();

        if (! $record || ! $record->clock_in) {
            return $this->respondError('No clock-in record found for today', 422);
        }

        if ($record->clock_out) {
            return $this->respondError('Employee has already clocked out today', 422);
        }

        $clockOut = now()->toTimeString();
        $totalHours = round(
            (strtotime($clockOut) - strtotime($record->clock_in)) / 3600, 2
        );

        $regularHours = min($totalHours, 8);
        $overtimeHours = max(0, $totalHours - 8);

        $record->update([
            'clock_out' => $clockOut,
            'total_hours' => $totalHours,
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
        ]);

        $msg = 'Clocked out at '.$clockOut.' ('.$totalHours.'h';
        if ($overtimeHours > 0) {
            $msg .= ', '.$overtimeHours.'h OT';
        }
        $msg .= ')';

        return $this->respondSuccess($msg, $record->fresh());
    }

    public function dailyLog(Request $request): JsonResponse
    {
        $date = $request->query('date', now()->toDateString());

        $employees = Employee::where('status', 'active')->get();
        $attendance = Attendance::whereDate('date', $date)
            ->get()
            ->keyBy('employee_id');

        $log = $employees->map(function ($emp) use ($attendance, $date) {
            $record = $attendance->get($emp->id);

            return [
                'employee_id' => $emp->id,
                'employee_code' => $emp->employee_code,
                'first_name' => $emp->first_name,
                'last_name' => $emp->last_name,
                'date' => $date,
                'clock_in' => $record?->clock_in,
                'clock_out' => $record?->clock_out,
                'total_hours' => $record?->total_hours,
                'status' => $record?->status ?? 'absent',
                'notes' => $record?->notes,
                'record_id' => $record?->id,
            ];
        });

        $present = $log->where('status', 'present')->count();
        $late = $log->where('status', 'late')->count();
        $absent = $log->where('status', 'absent')->count();
        $halfDay = $log->where('status', 'half_day')->count();
        $total = $log->count();

        return $this->respond([
            'success' => true,
            'data' => [
                'date' => $date,
                'summary' => [
                    'total' => $total,
                    'present' => $present,
                    'late' => $late,
                    'absent' => $absent,
                    'half_day' => $halfDay,
                    'attendance_rate' => $total > 0 ? round(($present + $late) / $total * 100, 1) : 0,
                ],
                'records' => $log->values(),
            ],
        ]);
    }

    public function todayStatus(): JsonResponse
    {
        $today = now()->toDateString();
        $employees = Employee::where('status', 'active')->get();
        $attendance = Attendance::whereDate('date', $today)
            ->get()
            ->keyBy('employee_id');

        $log = $employees->map(function ($emp) use ($attendance) {
            $record = $attendance->get($emp->id);

            return [
                'employee_id' => $emp->id,
                'employee_code' => $emp->employee_code,
                'first_name' => $emp->first_name,
                'last_name' => $emp->last_name,
                'clock_in' => $record?->clock_in,
                'clock_out' => $record?->clock_out,
                'total_hours' => $record?->total_hours,
                'status' => $record?->status ?? 'absent',
                'record_id' => $record?->id,
            ];
        });

        $present = $log->where('status', 'present')->count();
        $late = $log->where('status', 'late')->count();
        $total = $log->count();

        return $this->respond([
            'success' => true,
            'data' => [
                'present' => $present,
                'late' => $late,
                'absent' => $total - $present - $late,
                'total' => $total,
                'attendance_rate' => $total > 0 ? round(($present + $late) / $total * 100, 1) : 0,
                'records' => $log->values(),
            ],
        ]);
    }

    public function markAbsent(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());
        $employeeIds = $request->input('employee_ids');

        $query = Employee::where('status', 'active');
        if ($employeeIds) {
            $query->whereIn('id', $employeeIds);
        }

        $employees = $query->get();
        $marked = 0;

        foreach ($employees as $emp) {
            $exists = Attendance::where('employee_id', $emp->id)->whereDate('date', $date)->exists();
            if (! $exists) {
                Attendance::create([
                    'employee_id' => $emp->id,
                    'date' => $date,
                    'status' => 'absent',
                ]);
                $marked++;
            }
        }

        return $this->respondSuccess("{$marked} employees marked absent for {$date}");
    }

    public function summary(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->subMonths(1)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        $employeeId = $request->query('employee_id');

        $query = Attendance::where('date', '>=', $startDate)->where('date', '<=', $endDate);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $totalDays = (clone $query)->count();
        $present = (clone $query)->where('status', 'present')->count();
        $late = (clone $query)->where('status', 'late')->count();
        $absent = (clone $query)->where('status', 'absent')->count();
        $halfDay = (clone $query)->where('status', 'half_day')->count();
        $totalHours = (clone $query)->sum('total_hours');

        $byEmployee = (clone $query)
            ->select('employee_id', DB::raw('count(*) as days'), DB::raw("sum(case when status = 'present' then 1 else 0 end) as present_days"), DB::raw("sum(case when status = 'late' then 1 else 0 end) as late_days"), DB::raw("sum(case when status = 'absent' then 1 else 0 end) as absent_days"), DB::raw('sum(total_hours) as hours'))
            ->groupBy('employee_id')
            ->with('employee:id,first_name,last_name,employee_code')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_days' => $totalDays,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'half_day' => $halfDay,
                'total_hours' => round($totalHours, 2),
                'total_overtime' => round((clone $query)->sum('overtime_hours'), 2),
                'total_regular' => round((clone $query)->sum('regular_hours'), 2),
                'attendance_rate' => $totalDays > 0 ? round(($present + $late) / $totalDays * 100, 1) : 0,
                'by_employee' => $byEmployee,
            ],
        ]);
    }

    public function overtimeReport(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $query = Attendance::where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->where('overtime_hours', '>', 0);

        $totalOvertime = (clone $query)->sum('overtime_hours');
        $overtimeDays = (clone $query)->count();

        $byEmployee = (clone $query)
            ->select('employee_id', DB::raw('count(*) as overtime_days'), DB::raw('sum(overtime_hours) as total_overtime'), DB::raw('avg(overtime_hours) as avg_overtime'), DB::raw('max(overtime_hours) as max_overtime'))
            ->groupBy('employee_id')
            ->with('employee:id,first_name,last_name,employee_code')
            ->orderByDesc('total_overtime')
            ->get();

        $dailyOvertime = (clone $query)
            ->selectRaw('date, sum(overtime_hours) as total_overtime, count(*) as employees_with_ot')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_overtime_hours' => round($totalOvertime, 2),
                'total_overtime_days' => $overtimeDays,
                'by_employee' => $byEmployee,
                'daily_overtime' => $dailyOvertime,
            ],
        ]);
    }
}
