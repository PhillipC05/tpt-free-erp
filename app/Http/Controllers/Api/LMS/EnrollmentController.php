<?php

namespace App\Http\Controllers\Api\LMS;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Lms\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends BaseApiController
{
    protected array $validationRules = [
        'course_id' => 'required|exists:lms_courses,id',
        'employee_id' => 'required|exists:hr_employees,id',
        'enrollment_date' => 'required|date',
        'status' => 'sometimes|in:enrolled,in_progress,completed,dropped',
        'score' => 'nullable|numeric|min:0|max:100',
    ];

    public function __construct()
    {
        parent::__construct(new Enrollment());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) return $error;

        $existing = Enrollment::where('course_id', $request->course_id)
            ->where('employee_id', $request->employee_id)
            ->whereNotIn('status', ['dropped'])
            ->first();

        if ($existing) {
            return $this->respondError('Employee is already enrolled in this course', 422);
        }

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'enrolled';

        $enrollment = Enrollment::create($data);
        return $this->respondCreated($enrollment->load(['course', 'employee']), 'Enrolled successfully');
    }

    public function index(Request $request): JsonResponse
    {
        $query = Enrollment::query()->with(['course', 'employee']);

        if ($request->has('course_id')) {
            $query->where('course_id', $request->query('course_id'));
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->query('employee_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('enrollment_date', 'desc')->paginate(min($perPage, 100));

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

    public function enroll(Request $request, int $course): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'employee_id' => 'required|exists:hr_employees,id',
        ]);
        if ($error) return $error;

        $existing = Enrollment::where('course_id', $course)
            ->where('employee_id', $request->employee_id)
            ->whereNotIn('status', ['dropped'])
            ->first();

        if ($existing) {
            return $this->respondError('Employee is already enrolled in this course', 422);
        }

        $enrollment = Enrollment::create([
            'course_id' => $course,
            'employee_id' => $request->employee_id,
            'enrollment_date' => now()->toDateString(),
            'status' => 'enrolled',
        ]);

        return $this->respondCreated($enrollment->load(['course', 'employee']), 'Enrolled successfully');
    }

    public function complete(Request $request, int $enrollment): JsonResponse
    {
        $record = Enrollment::find($enrollment);
        if (!$record) return $this->respondNotFound();

        if ($record->status === 'completed') {
            return $this->respondError('Enrollment is already completed', 422);
        }

        $record->update([
            'status' => 'completed',
            'completion_date' => now()->toDateString(),
            'score' => $request->query('score'),
        ]);

        return $this->respondSuccess('Enrollment completed', $record->fresh());
    }
}
