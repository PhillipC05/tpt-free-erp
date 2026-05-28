<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\HR\Payroll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        'processed_by' => 'nullable|exists:users,id',
        'approved_by' => 'nullable|exists:users,id',
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
        parent::__construct(new Payroll());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'payroll_number' => 'required|string|max:50|unique:hr_payroll,payroll_number',
        ]));
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'draft';

        $payroll = Payroll::create($data);
        return $this->respondCreated($payroll, 'Payroll created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $payroll = Payroll::find($id);
        if (!$payroll) return $this->respondNotFound();

        if (in_array($payroll->status, ['approved', 'paid'])) {
            return $this->respondError('Cannot update a payroll that is already approved or paid', 422);
        }

        $error = $this->validate($request->all(), [
            'payroll_number' => 'required|string|max:50|unique:hr_payroll,payroll_number,' . $id,
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
            'processed_by' => 'nullable|exists:users,id',
            'approved_by' => 'nullable|exists:users,id',
        ]);
        if ($error) return $error;

        $payroll->update($request->all());
        return $this->respondSuccess('Payroll updated', $payroll->fresh());
    }

    public function process(int $id): JsonResponse
    {
        $payroll = Payroll::find($id);
        if (!$payroll) return $this->respondNotFound();

        if ($payroll->status !== 'draft') {
            return $this->respondError('Only draft payrolls can be processed', 422);
        }

        $payroll->update([
            'status' => 'processed',
            'processed_by' => auth()->id(),
        ]);

        return $this->respondSuccess('Payroll processed', $payroll->fresh());
    }

    public function approve(int $id): JsonResponse
    {
        $payroll = Payroll::find($id);
        if (!$payroll) return $this->respondNotFound();

        if ($payroll->status !== 'processed') {
            return $this->respondError('Only processed payrolls can be approved', 422);
        }

        $payroll->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $this->respondSuccess('Payroll approved', $payroll->fresh());
    }

    public function markPaid(Request $request, int $id): JsonResponse
    {
        $payroll = Payroll::find($id);
        if (!$payroll) return $this->respondNotFound();

        if ($payroll->status !== 'approved') {
            return $this->respondError('Only approved payrolls can be marked as paid', 422);
        }

        $data = $request->only(['payment_date', 'payment_method']);
        $data['status'] = 'paid';

        $payroll->update($data);
        return $this->respondSuccess('Payroll marked as paid', $payroll->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Payroll::query()->with(['employee', 'processedBy', 'approvedBy']);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->get('employee_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('period_start')) {
            $query->where('period_start', '>=', $request->get('period_start'));
        }

        if ($request->has('period_end')) {
            $query->where('period_end', '<=', $request->get('period_end'));
        }

        $perPage = $request->get('per_page', 15);
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

    public function summary(Request $request): JsonResponse
    {
        $query = Payroll::query();

        if ($request->has('period_start')) {
            $query->where('period_start', '>=', $request->get('period_start'));
        }

        if ($request->has('period_end')) {
            $query->where('period_end', '<=', $request->get('period_end'));
        }

        $payrolls = $query->whereIn('status', ['processed', 'approved', 'paid'])->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'total_employees' => $payrolls->count(),
                'total_basic_salary' => (float) $payrolls->sum('basic_salary'),
                'total_allowances' => (float) $payrolls->sum('allowances'),
                'total_overtime' => (float) $payrolls->sum('overtime'),
                'total_deductions' => (float) $payrolls->sum('deductions'),
                'total_tax' => (float) $payrolls->sum('tax_amount'),
                'total_net_salary' => (float) $payrolls->sum('net_salary'),
            ],
        ]);
    }
}