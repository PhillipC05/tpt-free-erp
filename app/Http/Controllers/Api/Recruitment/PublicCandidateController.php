<?php

namespace App\Http\Controllers\Api\Recruitment;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Recruitment\Application;
use App\Models\Recruitment\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicCandidateController extends BaseApiController
{
    public function listJobs(): JsonResponse
    {
        $jobs = Job::with('department')
            ->where('status', 'open')
            ->orderByDesc('posted_date')
            ->get();

        return $this->respond(['success' => true, 'data' => $jobs]);
    }

    public function showJob(int $id): JsonResponse
    {
        $job = Job::with('department')->find($id);

        if (! $job || $job->status !== 'open') {
            return $this->respondNotFound('Job posting not found');
        }

        return $this->respond(['success' => true, 'data' => $job]);
    }

    public function apply(Request $request, int $id): JsonResponse
    {
        $job = Job::find($id);

        if (! $job || $job->status !== 'open') {
            return $this->respondNotFound('Job posting not found');
        }

        $error = $this->validate($request->all(), [
            'name' => 'required|string|max:200',
            'email' => 'required|email|max:200',
            'phone' => 'nullable|string|max:30',
            'resume' => 'nullable|file|max:10240|mimes:pdf,doc,docx',
            'cover_letter' => 'nullable|string|max:10000',
        ]);
        if ($error) {
            return $error;
        }

        $resumePath = null;
        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('resumes', 'public');
        }

        $nameParts = explode(' ', $request->input('name'), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $application = Application::create([
            'application_number' => Application::generateNumber(),
            'job_id' => $id,
            'candidate_name' => $request->input('name'),
            'candidate_email' => $request->input('email'),
            'candidate_phone' => $request->input('phone'),
            'resume_path' => $resumePath,
            'cover_letter' => $request->input('cover_letter'),
            'status' => 'new',
            'tracking_token' => Str::random(40),
        ]);

        return $this->respondCreated([
            'application_number' => $application->application_number,
            'tracking_token' => $application->tracking_token,
            'status' => $application->status,
        ], 'Application submitted successfully');
    }

    public function trackApplication(string $token): JsonResponse
    {
        $application = Application::with('job:id,title,location,employment_type')
            ->where('tracking_token', $token)
            ->first();

        if (! $application) {
            return $this->respondNotFound('Application not found');
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'application_number' => $application->application_number,
                'candidate_name' => $application->candidate_name,
                'job_title' => $application->job->title ?? null,
                'status' => $application->status,
                'applied_at' => $application->created_at,
                'last_updated' => $application->updated_at,
            ],
        ]);
    }
}
