<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\HR\Employee;
use App\Models\HR\Payroll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollController extends BaseApiController
{
    protected array $validationRules = [
        'payroll_number' => 'required|string|max:50|unique:hr_payroll,payroll_number',
        'employee_id' => 'required|exists:hr_employees,id',
        'period_start' => 'required|date',
        'period_end' => 'required|date|after_or_equal:period_start',
        'basic_salary' => 'required|numeric|min:0',
        'allowances' => 'nullable|numeric|min:0',
        'overtime' => 'nullable|numeric|min:0',
        'deductions' => 'nullable|numeric|min:0',
        'tax_amount' => 'nullable|numeric|min:0',
        'net_salary' => 'required|numeric|min:0',
        'currency' => 'nullable|string|size:3',
        'payment_date' => 'nullable|date',
        'payment_method' => 'nullable|string|max:50',
        'status' => 'sometimes|in:draft,processed,approved,paid,cancelled',
        'notes' => 'nullable|string',
    ];

    protected array $validationMessages = [
        'payroll_number.required' => 'Payroll number is required.',
        'payroll_number.unique' => 'This payroll number is already in use.',
        'employee_id.required' => 'Employee is required.',
        'period_start.required' => 'Period start date is required.',
        'period_end.required' => 'Period end date is required.',
        'basic_salary.required' => 'Basic salary is required.',
        'net_salary.required' => 'Net salary is required.',
    ];

    public function __construct()
    {
        parent::__construct(new Payroll);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'draft';
        $data['processed_by'] = Auth::id();

        $payroll = Payroll::create($data);

        return $this->respondCreated($payroll->fresh(['employee']), 'Payroll created');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $payroll = Payroll::find($id);
        if (! $payroll) {
            return $this->respondNotFound();
        }

        if (in_array($payroll->status, ['approved', 'paid'])) {
            return $this->respondError('Cannot update a payroll that is already approved or paid', 422);
        }

        $error = $this->validate($request->all(), [
            'payroll_number' => 'required|string|max:50|unique:hr_payroll,payroll_number,'.$id,
            'employee_id' => 'required|exists:hr_employees,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'overtime' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'net_salary' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'payment_date' => 'nullable|date',
            'payment_method' => 'nullable|string|max:50',
        'status' => 'sometimes|in:draft,approved,paid,cancelled',
        'notes' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $payroll->update($request->all());

        return $this->respondSuccess('Payroll updated', $payroll->fresh(['employee']));
    }

    public function show(int $id): JsonResponse
    {
        $payroll = Payroll::with(['employee', 'processedBy', 'approvedBy'])->find($id);
        if (! $payroll) {
            return $this->respondNotFound();
        }

        $grossPay = $payroll->gross_pay;
        $netPay = (float) $payroll->net_salary;

        return $this->respond([
            'success' => true,
            'data' => array_merge($payroll->toArray(), [
                'gross_pay' => $grossPay,
                'net_pay' => $netPay,
            ]),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Payroll::query()->with(['employee', 'processedBy', 'approvedBy']);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->query('employee_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('period_start')) {
            $query->where('period_start', '>=', $request->query('period_start'));
        }

        if ($request->has('period_end')) {
            $query->where('period_end', '<=', $request->query('period_end'));
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

    public function process(int $id): JsonResponse
    {
        $payroll = Payroll::find($id);
        if (!$payroll) return $this->respondNotFound();

        if ($payroll->status !== 'draft') {
            return $this->respondError('Only draft payrolls can be processed', 422);
        }

        $payroll->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $this->respondSuccess('Payroll processed and approved', $payroll->fresh(['employee']));
    }

    public function approve(int $id): JsonResponse
    {
        return $this->process($id);
    }

    public function markPaid(Request $request, int $id): JsonResponse
    {
        $payroll = Payroll::find($id);
        if (! $payroll) {
            return $this->respondNotFound();
        }

        if ($payroll->status !== 'approved') {
            return $this->respondError('Only approved payrolls can be marked as paid', 422);
        }

        $data = $request->only(['payment_date', 'payment_method']);
        $data['status'] = 'paid';
        $data['payment_date'] = $data['payment_date'] ?? now()->toDateString();

        $payroll->update($data);

        return $this->respondSuccess('Payroll marked as paid', $payroll->fresh(['employee']));
    }

    public function batchGenerate(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:hr_employees,id',
        ]);
        if ($error) {
            return $error;
        }

        $query = Employee::where('status', 'active');
        if ($request->has('employee_ids') && $request->input('employee_ids')) {
            $query->whereIn('id', $request->input('employee_ids'));
        }

        $employees = $query->get();
        $generated = [];
        $skipped = 0;

        foreach ($employees as $employee) {
            $existing = Payroll::where('employee_id', $employee->id)
                ->where('period_start', $request->period_start)
                ->where('period_end', $request->period_end)
                ->first();

            if ($existing) {
                $skipped++;

                continue;
            }

            $salary = (float) $employee->salary;
            $attendanceOvertime = DB::table('hr_attendance')
                ->where('employee_id', $employee->id)
                ->where('date', '>=', $request->period_start)
                ->where('date', '<=', $request->period_end)
                ->sum('overtime_hours');

            $overtimePay = round($attendanceOvertime * ($salary / 160) * 1.5, 2);
            $grossPay = $salary + $overtimePay;
            $taxAmount = round($grossPay * 0.15, 2);
            $netSalary = round($grossPay - $taxAmount, 2);

            $payroll = Payroll::create([
                'payroll_number' => 'PAY-'.date('Ymd').'-'.str_pad($employee->id, 4, '0', STR_PAD_LEFT),
                'employee_id' => $employee->id,
                'period_start' => $request->period_start,
                'period_end' => $request->period_end,
                'basic_salary' => $salary,
                'allowances' => 0,
                'overtime' => $overtimePay,
                'deductions' => 0,
                'tax_amount' => $taxAmount,
                'net_salary' => $netSalary,
                'currency' => $employee->currency ?? 'USD',
                'status' => 'draft',
                'processed_by' => Auth::id(),
            ]);

            $generated[] = $payroll->fresh(['employee']);
        }

        return $this->respondSuccess(
            count($generated).' payrolls generated, '.$skipped.' skipped (already exist)',
            ['generated' => $generated, 'skipped' => $skipped]
        );
    }

    public function summary(Request $request): JsonResponse
    {
        $startDate = $request->query('period_start', now()->subMonths(3)->toDateString());
        $endDate = $request->query('period_end', now()->toDateString());

        $query = Payroll::where('period_start', '>=', $startDate)->where('period_end', '<=', $endDate);

        $totalBasic = (clone $query)->sum('basic_salary');
        $totalAllowances = (clone $query)->sum('allowances');
        $totalOvertime = (clone $query)->sum('overtime');
        $totalDeductions = (clone $query)->sum('deductions');
        $totalTax = (clone $query)->sum('tax_amount');
        $totalNet = (clone $query)->sum('net_salary');
        $totalCount = (clone $query)->count();

        $paidCount = (clone $query)->where('status', 'paid')->count();
        $pendingCount = (clone $query)->whereIn('status', ['draft', 'processed', 'approved'])->count();

        $monthlyTrend = (clone $query)
            ->selectRaw("strftime('%Y-%m', period_start) as month, sum(net_salary) as total, count(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $byDepartment = (clone $query)
            ->join('hr_employees', 'hr_payroll.employee_id', '=', 'hr_employees.id')
            ->leftJoin('hr_departments', 'hr_employees.department_id', '=', 'hr_departments.id')
            ->select('hr_departments.name as department_name', DB::raw('count(*) as count'), DB::raw('sum(hr_payroll.net_salary) as total_salary'))
            ->groupBy('hr_departments.name')
            ->orderByDesc('total_salary')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'total_employees' => $totalCount,
                'paid' => $paidCount,
                'pending' => $pendingCount,
                'total_basic_salary' => round($totalBasic, 2),
                'total_allowances' => round($totalAllowances, 2),
                'total_overtime' => round($totalOvertime, 2),
                'total_deductions' => round($totalDeductions, 2),
                'total_tax' => round($totalTax, 2),
                'total_net_salary' => round($totalNet, 2),
                'monthly_trend' => $monthlyTrend,
                'by_department' => $byDepartment,
            ],
        ]);
    }
}
