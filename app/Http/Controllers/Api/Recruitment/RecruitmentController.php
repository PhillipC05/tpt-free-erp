<?php

namespace App\Http\Controllers\Api\Recruitment;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\ESignature\ESignature;
use App\Models\Recruitment\Application;
use App\Models\Recruitment\Interview;
use App\Models\Recruitment\Job;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecruitmentController extends BaseApiController
{
    // ── JOBS ──────────────────────────────────────────────────────────────

    public function storeJob(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'job_code' => 'required|string|max:20|unique:recruitment_jobs,job_code',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'department_id' => 'nullable|exists:hr_departments,id',
            'location' => 'nullable|string|max:200',
            'employment_type' => 'nullable|string|max:50',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'positions' => 'nullable|integer|min:1',
            'closing_date' => 'nullable|date|after:today',
        ]);
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['status'] = 'draft';
        $data['created_by'] = Auth::id();

        $job = Job::create($data);

        return $this->respondCreated($job->fresh(['department']), 'Job created');
    }

    public function updateJob(Request $request, int $id): JsonResponse
    {
        $job = Job::find($id);
        if (! $job) {
            return $this->respondNotFound();
        }

        if (in_array($job->status, ['filled', 'closed'])) {
            return $this->respondError('Cannot update a filled or closed job', 422);
        }

        $error = $this->validate($request->all(), [
            'job_code' => 'required|string|max:20|unique:recruitment_jobs,job_code,'.$id,
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'department_id' => 'nullable|exists:hr_departments,id',
            'location' => 'nullable|string|max:200',
            'employment_type' => 'nullable|string|max:50',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'positions' => 'nullable|integer|min:1',
            'closing_date' => 'nullable|date',
        ]);
        if ($error) {
            return $error;
        }

        $job->update($request->all());

        return $this->respondSuccess('Job updated', $job->fresh(['department']));
    }

    public function publishJob(int $id): JsonResponse
    {
        $job = Job::find($id);
        if (! $job) {
            return $this->respondNotFound();
        }

        if (! in_array($job->status, ['draft', 'on_hold'])) {
            return $this->respondError('Only draft or on-hold jobs can be published', 422);
        }

        $job->update(['status' => 'open', 'posted_date' => now()->toDateString()]);

        return $this->respondSuccess('Job published', $job->fresh());
    }

    public function closeJob(int $id): JsonResponse
    {
        $job = Job::find($id);
        if (! $job) {
            return $this->respondNotFound();
        }

        $job->update(['status' => 'closed']);

        return $this->respondSuccess('Job closed', $job->fresh());
    }

    public function listJobs(Request $request): JsonResponse
    {
        $query = Job::query()->with(['department', 'applications']);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('department_id')) {
            $query->where('department_id', $request->query('department_id'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('job_code', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderByDesc('created_at')->paginate(min($perPage, 100));

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

    public function showJob(int $id): JsonResponse
    {
        $job = Job::with(['department', 'applications.interviews', 'applications.reviewer'])->find($id);
        if (! $job) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $job]);
    }

    public function destroyJob(int $id): JsonResponse
    {
        $job = Job::find($id);
        if (! $job) {
            return $this->respondNotFound();
        }

        if ($job->applications()->count() > 0) {
            return $this->respondError('Cannot delete job with existing applications', 422);
        }

        $job->delete();

        return $this->respondSuccess('Job deleted');
    }

    // ── APPLICATIONS ──────────────────────────────────────────────────────

    public function storeApplication(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'job_id' => 'required|exists:recruitment_jobs,id',
            'candidate_name' => 'required|string|max:200',
            'candidate_email' => 'required|email|max:200',
            'candidate_phone' => 'nullable|string|max:30',
            'cover_letter' => 'nullable|string',
            'expected_salary' => 'nullable|numeric|min:0',
        ]);
        if ($error) {
            return $error;
        }

        $job = Job::find($request->job_id);
        if ($job->status !== 'open') {
            return $this->respondError('This job is not currently accepting applications', 422);
        }

        $data = $request->all();
        $data['application_number'] = Application::generateNumber();
        $data['status'] = 'new';

        $application = Application::create($data);

        return $this->respondCreated($application->fresh(['job']), 'Application submitted');
    }

    public function listApplications(Request $request): JsonResponse
    {
        $query = Application::query()->with(['job', 'reviewer']);

        if ($request->has('job_id')) {
            $query->where('job_id', $request->query('job_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('candidate_name', 'like', "%{$search}%")
                    ->orWhere('candidate_email', 'like', "%{$search}%")
                    ->orWhere('application_number', 'like', "%{$search}%");
            });
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderByDesc('created_at')->paginate(min($perPage, 100));

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

    public function showApplication(int $id): JsonResponse
    {
        $application = Application::with(['job', 'reviewer', 'interviews.interviewer'])->find($id);
        if (! $application) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $application]);
    }

    public function updateApplicationStatus(Request $request, int $id): JsonResponse
    {
        $application = Application::find($id);
        if (! $application) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'status' => 'required|in:new,screening,interview,offer,hired,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:500',
        ]);
        if ($error) {
            return $error;
        }

        $data = $request->only(['status', 'rejection_reason']);
        $data['reviewed_by'] = Auth::id();
        $data['reviewed_at'] = now();

        $application->update($data);

        if ($request->input('status') === 'hired') {
            $job = $application->job;
            $filledCount = Application::where('job_id', $job->id)->where('status', 'hired')->count();
            if ($filledCount >= $job->positions) {
                $job->update(['status' => 'filled']);
            }
        }

        return $this->respondSuccess('Application status updated', $application->fresh(['job', 'reviewer']));
    }

    // ── OFFER LETTERS ─────────────────────────────────────────────────────

    public function generateOfferLetter(Request $request, int $id): JsonResponse
    {
        $application = Application::with(['job.department'])->find($id);
        if (! $application) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'salary' => 'required|numeric|min:0',
            'start_date' => 'required|date|after:today',
            'notes' => 'nullable|string|max:2000',
        ]);
        if ($error) {
            return $error;
        }

        if ($application->offer_letter_content) {
            return $this->respondError('Offer letter already exists for this application. Use the GET endpoint to retrieve it.', 422);
        }

        $job = $application->job;
        $department = $job->department;
        $salary = number_format((float) $request->input('salary'), 2);
        $startDate = Carbon::parse($request->input('start_date'))->format('F j, Y');
        $companyName = config('app.name', 'TPT ERP');
        $candidateName = $application->candidate_name;
        $jobTitle = $job->title;
        $deptName = $department->name ?? 'N/A';
        $employmentType = str_replace('_', ' ', ucfirst($job->employment_type));

        $offerHtml = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 700px; margin: 0 auto; padding: 40px; color: #333;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #1e40af; margin: 0;">{$companyName}</h1>
        <p style="color: #666; margin: 5px 0 0 0;">Offer of Employment</p>
    </div>

    <p>Date: {$startDate}</p>
    <p>Dear {$candidateName},</p>

    <p>We are pleased to extend to you an offer of employment for the position of <strong>{$jobTitle}</strong> in the <strong>{$deptName}</strong> department at {$companyName}.</p>

    <h3 style="color: #374151; margin-top: 24px;">Position Details</h3>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        <tr>
            <td style="padding: 8px; border: 1px solid #e5e7eb; background: #f9fafb; width: 200px;">Position</td>
            <td style="padding: 8px; border: 1px solid #e5e7eb;">{$jobTitle}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #e5e7eb; background: #f9fafb;">Department</td>
            <td style="padding: 8px; border: 1px solid #e5e7eb;">{$deptName}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #e5e7eb; background: #f9fafb;">Employment Type</td>
            <td style="padding: 8px; border: 1px solid #e5e7eb;">{$employmentType}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #e5e7eb; background: #f9fafb;">Location</td>
            <td style="padding: 8px; border: 1px solid #e5e7eb;">{$job->location}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #e5e7eb; background: #f9fafb;">Annual Salary</td>
            <td style="padding: 8px; border: 1px solid #e5e7eb;">\${$salary}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #e5e7eb; background: #f9fafb;">Start Date</td>
            <td style="padding: 8px; border: 1px solid #e5e7eb;">{$startDate}</td>
        </tr>
    </table>

    <p>This offer is contingent upon successful completion of background verification and your signed agreement to the company's policies and terms of employment.</p>

    {$this->buildNotesHtml($request->input('notes'))}

    <p>We are excited about the possibility of you joining our team and look forward to your positive response.</p>

    <p>Sincerely,<br>
    <strong>{$companyName} Human Resources</strong></p>

    <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0 15px;">
    <p style="color: #999; font-size: 11px;">Application #{$application->application_number}</p>
</body>
</html>
HTML;

        $application->update([
            'offer_letter_content' => $offerHtml,
            'offer_letter_generated_at' => now(),
            'status' => 'offer',
        ]);

        $signature = ESignature::create([
            'signable_type' => Application::class,
            'signable_id' => $application->id,
            'token' => ESignature::generateToken(),
            'status' => 'pending',
            'signer_name' => $application->candidate_name,
            'signer_email' => $application->candidate_email,
            'document_hash' => ESignature::hashSignable($application->fresh()->toArray()),
            'message' => 'Offer letter for '.$jobTitle.' position',
            'expires_at' => now()->addDays(14),
            'requested_by' => Auth::id(),
            'audit_log' => [[
                'event' => 'offer_letter_created',
                'at' => now()->toIso8601String(),
                'by' => Auth::user()->email,
            ]],
        ]);

        return $this->respondCreated([
            'esignature_id' => $signature->id,
            'signing_token' => $signature->token,
            'expires_at' => $signature->expires_at,
        ], 'Offer letter generated and E-Signature request created');
    }

    public function showOfferLetter(int $id): JsonResponse
    {
        $application = Application::with(['job.department'])->find($id);
        if (! $application) {
            return $this->respondNotFound();
        }

        if (! $application->offer_letter_content) {
            return $this->respondError('No offer letter exists for this application', 404);
        }

        $signature = ESignature::where('signable_type', Application::class)
            ->where('signable_id', $application->id)
            ->latest()
            ->first();

        return $this->respond([
            'success' => true,
            'data' => [
                'offer_letter_content' => $application->offer_letter_content,
                'generated_at' => $application->offer_letter_generated_at,
                'signature_status' => $signature?->status,
                'signed_at' => $signature?->signed_at,
                'esignature_id' => $signature?->id,
            ],
        ]);
    }

    private function buildNotesHtml(?string $notes): string
    {
        if (! $notes) {
            return '';
        }

        return <<<HTML
        <h3 style="color: #374151; margin-top: 24px;">Additional Notes</h3>
        <p>{$notes}</p>
        HTML;
    }

    // ── INTERVIEWS ────────────────────────────────────────────────────────

    public function scheduleInterview(Request $request, int $applicationId): JsonResponse
    {
        $application = Application::find($applicationId);
        if (! $application) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'interview_type' => 'required|in:phone,video,onsite,panel',
            'scheduled_at' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:15|max:240',
            'location' => 'nullable|string|max:200',
            'interviewer_id' => 'nullable|exists:hr_employees,id',
        ]);
        if ($error) {
            return $error;
        }

        if (! in_array($application->status, ['new', 'screening', 'interview'])) {
            return $this->respondError('Cannot schedule interview for an application in '.$application->status.' status', 422);
        }

        $data = $request->all();
        $data['application_id'] = $applicationId;
        $data['status'] = 'scheduled';

        $interview = Interview::create($data);
        $application->update(['status' => 'interview']);

        return $this->respondCreated($interview->fresh(['application', 'interviewer']), 'Interview scheduled');
    }

    public function updateInterview(Request $request, int $id): JsonResponse
    {
        $interview = Interview::find($id);
        if (! $interview) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'interview_type' => 'sometimes|in:phone,video,onsite,panel',
            'scheduled_at' => 'sometimes|date',
            'duration_minutes' => 'nullable|integer|min:15|max:240',
            'location' => 'nullable|string|max:200',
            'interviewer_id' => 'nullable|exists:hr_employees,id',
            'status' => 'sometimes|in:scheduled,completed,cancelled,no_show',
            'score' => 'nullable|numeric|min:0|max:10',
            'feedback' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $interview->update($request->all());

        if ($request->input('status') === 'completed') {
            $application = $interview->application;
            $avgScore = $application->interviews()->where('status', 'completed')->avg('score');
            if ($avgScore && $avgScore >= 7) {
                $application->update(['status' => 'offer']);
            }
        }

        return $this->respondSuccess('Interview updated', $interview->fresh(['application', 'interviewer']));
    }

    // ── PIPELINE ──────────────────────────────────────────────────────────

    public function pipeline(Request $request): JsonResponse
    {
        $jobId = $request->query('job_id');
        $query = Application::with(['job', 'interviews']);

        if ($jobId) {
            $query->where('job_id', $jobId);
        }

        $applications = $query->get();

        $pipeline = [
            'new' => $applications->where('status', 'new'),
            'screening' => $applications->where('status', 'screening'),
            'interview' => $applications->where('status', 'interview'),
            'offer' => $applications->where('status', 'offer'),
            'hired' => $applications->where('status', 'hired'),
            'rejected' => $applications->where('status', 'rejected'),
        ];

        return $this->respond([
            'success' => true,
            'data' => [
                'pipeline' => collect($pipeline)->map(fn ($apps) => [
                    'count' => $apps->count(),
                    'applications' => $apps->values(),
                ])->toArray(),
                'total' => $applications->count(),
            ],
        ]);
    }

    public function dashboard(): JsonResponse
    {
        $openJobs = Job::where('status', 'open')->count();
        $totalJobs = Job::count();
        $totalApplications = Application::count();
        $newApplications = Application::where('status', 'new')->count();
        $inInterview = Application::where('status', 'interview')->count();
        $hired = Application::where('status', 'hired')->count();
        $rejected = Application::where('status', 'rejected')->count();

        $upcomingInterviews = Interview::with(['application.job', 'interviewer'])
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        $recentApplications = Application::with(['job'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $applicationsByJob = Job::withCount('applications')
            ->where('status', 'open')
            ->orderByDesc('applications_count')
            ->limit(10)
            ->get()
            ->map(fn ($job) => [
                'id' => $job->id,
                'title' => $job->title,
                'applications_count' => $job->applications_count,
            ]);

        return $this->respond([
            'success' => true,
            'data' => [
                'open_jobs' => $openJobs,
                'total_jobs' => $totalJobs,
                'total_applications' => $totalApplications,
                'new_applications' => $newApplications,
                'in_interview' => $inInterview,
                'hired' => $hired,
                'rejected' => $rejected,
                'conversion_rate' => $totalApplications > 0 ? round(($hired / $totalApplications) * 100, 1) : 0,
                'upcoming_interviews' => $upcomingInterviews,
                'recent_applications' => $recentApplications,
                'applications_by_job' => $applicationsByJob,
            ],
        ]);
    }
}
