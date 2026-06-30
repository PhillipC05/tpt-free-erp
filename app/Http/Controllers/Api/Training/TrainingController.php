<?php

namespace App\Http\Controllers\Api\Training;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Training\Certification;
use App\Models\Training\Enrollment;
use App\Models\Training\Program;
use App\Models\Training\Session;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainingController extends BaseApiController
{
    // ── PROGRAMS ──────────────────────────────────────────────────────────

    public function storeProgram(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'code' => 'required|string|max:20|unique:training_programs,code',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'course_id' => 'nullable|exists:lms_courses,id',
            'type' => 'required|in:onboarding,compliance,skill,safety,leadership,other',
            'duration_hours' => 'nullable|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'is_mandatory' => 'nullable|boolean',
        ]);
        if ($error) {
            return $error;
        }

        $program = Program::create($request->all());

        return $this->respondCreated($program->fresh(['course']), 'Program created');
    }

    public function updateProgram(Request $request, int $id): JsonResponse
    {
        $program = Program::find($id);
        if (! $program) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'code' => 'required|string|max:20|unique:training_programs,code,'.$id,
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'course_id' => 'nullable|exists:lms_courses,id',
            'type' => 'sometimes|in:onboarding,compliance,skill,safety,leadership,other',
            'duration_hours' => 'nullable|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'is_mandatory' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);
        if ($error) {
            return $error;
        }

        $program->update($request->all());

        return $this->respondSuccess('Program updated', $program->fresh(['course']));
    }

    public function listPrograms(Request $request): JsonResponse
    {
        $query = Program::query()->withCount(['sessions', 'enrollments']);

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->has('is_mandatory')) {
            $query->where('is_mandatory', $request->boolean('is_mandatory'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('name')->paginate(min($perPage, 100));

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

    public function showProgram(int $id): JsonResponse
    {
        $program = Program::with(['course', 'sessions.instructor', 'enrollments.employee'])->find($id);
        if (! $program) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $program]);
    }

    public function destroyProgram(int $id): JsonResponse
    {
        $program = Program::find($id);
        if (! $program) {
            return $this->respondNotFound();
        }

        if ($program->sessions()->count() > 0) {
            return $this->respondError('Cannot delete program with existing sessions', 422);
        }

        $program->delete();

        return $this->respondSuccess('Program deleted');
    }

    // ── SESSIONS ──────────────────────────────────────────────────────────

    public function storeSession(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'program_id' => 'required|exists:training_programs,id',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'location' => 'nullable|string|max:200',
            'instructor_id' => 'nullable|exists:hr_employees,id',
            'max_participants' => 'nullable|integer|min:1',
        ]);
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['status'] = 'scheduled';

        $session = Session::create($data);

        return $this->respondCreated($session->fresh(['program', 'instructor']), 'Session created');
    }

    public function listSessions(Request $request): JsonResponse
    {
        $query = Session::query()->with(['program', 'instructor', 'enrollments']);

        if ($request->has('program_id')) {
            $query->where('program_id', $request->query('program_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('starts_at', 'desc')->paginate(min($perPage, 100));

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

    public function showSession(int $id): JsonResponse
    {
        $session = Session::with(['program', 'instructor', 'enrollments.employee'])->find($id);
        if (! $session) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $session]);
    }

    public function startSession(int $id): JsonResponse
    {
        $session = Session::find($id);
        if (! $session) {
            return $this->respondNotFound();
        }

        if ($session->status !== 'scheduled') {
            return $this->respondError('Only scheduled sessions can be started', 422);
        }

        $session->update(['status' => 'in_progress']);

        return $this->respondSuccess('Session started', $session->fresh());
    }

    public function completeSession(int $id): JsonResponse
    {
        $session = Session::find($id);
        if (! $session) {
            return $this->respondNotFound();
        }

        if ($session->status !== 'in_progress') {
            return $this->respondError('Only in-progress sessions can be completed', 422);
        }

        $session->update(['status' => 'completed']);

        $session->enrollments()->where('status', 'enrolled')
            ->update(['status' => 'attended', 'completed_at' => now()]);

        return $this->respondSuccess('Session completed', $session->fresh());
    }

    public function enrollEmployee(Request $request, int $sessionId): JsonResponse
    {
        $session = Session::find($sessionId);
        if (! $session) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'employee_id' => 'required|exists:hr_employees,id',
        ]);
        if ($error) {
            return $error;
        }

        $existing = Enrollment::where('session_id', $sessionId)
            ->where('employee_id', $request->employee_id)
            ->exists();

        if ($existing) {
            return $this->respondError('Employee already enrolled in this session', 422);
        }

        if ($session->max_participants) {
            $currentCount = $session->enrollments()->count();
            if ($currentCount >= $session->max_participants) {
                return $this->respondError('Session is full ('.$session->max_participants.' max)', 422);
            }
        }

        $enrollment = Enrollment::create([
            'session_id' => $sessionId,
            'employee_id' => $request->employee_id,
            'status' => 'enrolled',
        ]);

        return $this->respondCreated($enrollment->fresh(['employee']), 'Employee enrolled');
    }

    public function completeEnrollment(Request $request, int $enrollmentId): JsonResponse
    {
        $enrollment = Enrollment::find($enrollmentId);
        if (! $enrollment) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'score' => 'nullable|numeric|min:0|max:10',
            'feedback' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $enrollment->update([
            'status' => 'completed',
            'score' => $request->input('score'),
            'feedback' => $request->input('feedback'),
            'completed_at' => now(),
        ]);

        return $this->respondSuccess('Enrollment completed', $enrollment->fresh(['employee']));
    }

    // ── CERTIFICATIONS ────────────────────────────────────────────────────

    public function storeCertification(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'employee_id' => 'required|exists:hr_employees,id',
            'program_id' => 'nullable|exists:training_programs,id',
            'certification_name' => 'required|string|max:200',
            'issuing_body' => 'nullable|string|max:200',
            'certificate_number' => 'nullable|string|max:100',
            'issued_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issued_date',
            'notes' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['status'] = 'active';

        $cert = Certification::create($data);

        return $this->respondCreated($cert->fresh(['employee', 'program']), 'Certification recorded');
    }

    public function listCertifications(Request $request): JsonResponse
    {
        $query = Certification::query()->with(['employee', 'program']);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->query('employee_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('expiring_soon')) {
            $days = (int) $request->query('expiring_soon', 30);
            $query->where('expiry_date', '<=', now()->addDays($days))
                ->where('expiry_date', '>=', now())
                ->where('status', 'active');
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('expiry_date')->paginate(min($perPage, 100));

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

    public function renewCertification(int $id): JsonResponse
    {
        $cert = Certification::find($id);
        if (! $cert) {
            return $this->respondNotFound();
        }

        $cert->update([
            'status' => 'active',
            'expiry_date' => now()->addYear()->toDateString(),
        ]);

        return $this->respondSuccess('Certification renewed', $cert->fresh(['employee', 'program']));
    }

    // ── COMPLETION REPORT ─────────────────────────────────────────────────

    public function completionReport(): JsonResponse
    {
        $totalEnrollments = Enrollment::count();
        $completedEnrollments = Enrollment::where('status', 'completed')->count();
        $completionRate = $totalEnrollments > 0
            ? round(($completedEnrollments / $totalEnrollments) * 100, 1)
            : 0;

        $avgCompletionTime = Enrollment::where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('created_at')
            ->selectRaw('AVG(julianday(completed_at) - julianday(created_at)) as avg_days')
            ->value('avg_days');

        $topPrograms = Program::withCount(['enrollments' => function ($q) {
            $q->where('status', 'completed');
        }])
            ->withCount('enrollments')
            ->orderByDesc('enrollments_count')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'total_enrollments' => $p->enrollments_count,
                'completed_enrollments' => $p->enrollments_count,
            ]);

        return $this->respond([
            'success' => true,
            'data' => [
                'total_enrollments' => $totalEnrollments,
                'completed_enrollments' => $completedEnrollments,
                'completion_rate' => $completionRate,
                'average_completion_time_days' => $avgCompletionTime !== null ? round((float) $avgCompletionTime, 1) : null,
                'top_programs' => $topPrograms,
            ],
        ]);
    }

    // ── DASHBOARD ─────────────────────────────────────────────────────────

    public function dashboard(): JsonResponse
    {
        $totalPrograms = Program::where('is_active', true)->count();
        $mandatoryPrograms = Program::where('is_active', true)->where('is_mandatory', true)->count();
        $totalSessions = Session::count();
        $upcomingSessions = Session::where('status', 'scheduled')
            ->where('starts_at', '>=', now())
            ->count();
        $totalEnrollments = Enrollment::count();
        $completedEnrollments = Enrollment::where('status', 'completed')->count();
        $totalCertifications = Certification::where('status', 'active')->count();
        $expiringCertifications = Certification::where('status', 'active')
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>=', now())
            ->count();
        $expiredCertifications = Certification::where('expiry_date', '<', now()->toDateString())
            ->where('status', 'active')
            ->count();

        $completionRate = $totalEnrollments > 0
            ? round(($completedEnrollments / $totalEnrollments) * 100, 1)
            : 0;

        $upcomingList = Session::with(['program', 'instructor'])
            ->where('status', 'scheduled')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(10)
            ->get();

        $expiringCerts = Certification::with(['employee', 'program'])
            ->where('status', 'active')
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>=', now())
            ->orderBy('expiry_date')
            ->limit(10)
            ->get();

        $byType = Program::where('is_active', true)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'total_programs' => $totalPrograms,
                'mandatory_programs' => $mandatoryPrograms,
                'total_sessions' => $totalSessions,
                'upcoming_sessions' => $upcomingSessions,
                'total_enrollments' => $totalEnrollments,
                'completed_enrollments' => $completedEnrollments,
                'completion_rate' => $completionRate,
                'active_certifications' => $totalCertifications,
                'expiring_certifications' => $expiringCertifications,
                'expired_certifications' => $expiredCertifications,
                'upcoming_sessions_list' => $upcomingList,
                'expiring_certifications_list' => $expiringCerts,
                'programs_by_type' => $byType,
            ],
        ]);
    }
}
