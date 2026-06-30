<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ReportGenerationJob;
use App\Models\HR\Attendance;
use App\Models\HR\Payroll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    // POST /api/v1/reports/generate
    public function generate(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'report_type' => 'required|string|in:trial_balance,income_statement,balance_sheet,cash_flow,hr_attendance,hr_payroll,sales_summary,procurement',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'date' => 'nullable|date',
            'format' => 'nullable|string|in:json,csv,pdf',
        ]);
        if ($error) {
            return $error;
        }

        $parameters = array_filter([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'date' => $request->date,
        ]);

        $reportId = DB::table('generated_reports')->insertGetId([
            'user_id' => $request->user()->id,
            'report_type' => $request->report_type,
            'parameters' => json_encode($parameters),
            'format' => $request->format ?? 'json',
            'status' => 'queued',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ReportGenerationJob::dispatch(
            $request->user()->id,
            $request->report_type,
            $parameters,
            $request->format ?? 'json',
            null,
            null,
            $reportId
        );

        return $this->respondCreated([
            'report_id' => $reportId,
            'status' => 'queued',
            'message' => 'Report generation queued. Poll GET /api/v1/reports/'.$reportId.' for status.',
        ]);
    }

    // GET /api/v1/reports/{id}
    public function show(int $id): JsonResponse
    {
        $report = DB::table('generated_reports')
            ->where('id', $id)
            ->where('user_id', request()->user()->id)
            ->first();

        if (! $report) {
            return $this->respondNotFound();
        }

        $data = (array) $report;
        if ($data['result_data']) {
            $data['result_data'] = json_decode($data['result_data'], true);
        }

        return $this->respondSuccess('Report retrieved', $data);
    }

    // GET /api/v1/reports/{id}/download
    public function download(Request $request, int $id): JsonResponse|Response
    {
        $report = DB::table('generated_reports')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $report) {
            return $this->respondNotFound();
        }
        if ($report->status !== 'completed') {
            return $this->respondError('Report not ready yet', 422);
        }

        $data = json_decode($report->result_data, true);
        $format = $report->format ?? 'json';

        if ($format === 'csv') {
            $rows = $data['rows'] ?? [];
            $csv = $this->toCsv($rows);

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"report_{$id}.csv\"",
            ]);
        }

        if ($format === 'pdf') {
            $path = $report->result_path;
            if (! $path || ! Storage::exists($path)) {
                return $this->respondError('PDF file not found', 404);
            }

            return response(Storage::get($path), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"report_{$id}.pdf\"",
            ]);
        }

        return $this->respondSuccess('Report download', $data);
    }

    // GET /api/v1/reports/scheduled
    public function scheduledIndex(Request $request): JsonResponse
    {
        $scheduled = DB::table('scheduled_reports')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return $this->respondSuccess('Scheduled reports retrieved', $scheduled);
    }

    // POST /api/v1/reports/scheduled
    public function scheduledStore(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'name' => 'required|string|max:200',
            'report_type' => 'required|string|in:trial_balance,income_statement,balance_sheet,cash_flow,hr_attendance,hr_payroll,sales_summary,procurement',
            'frequency' => 'required|string|in:hourly,daily,weekly,monthly',
            'format' => 'nullable|string|in:json,csv,pdf',
            'delivery_email' => 'nullable|email',
            'parameters' => 'nullable|array',
        ]);
        if ($error) {
            return $error;
        }

        $nextRun = match ($request->frequency) {
            'hourly' => now()->addHour(),
            'daily' => now()->addDay()->startOfDay(),
            'weekly' => now()->addWeek()->startOfDay(),
            'monthly' => now()->addMonth()->startOfDay(),
        };

        $id = DB::table('scheduled_reports')->insertGetId([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'report_type' => $request->report_type,
            'parameters' => json_encode($request->parameters ?? []),
            'format' => $request->format ?? 'json',
            'frequency' => $request->frequency,
            'next_run_at' => $nextRun,
            'delivery_email' => $request->delivery_email,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->respondCreated(['id' => $id, 'next_run_at' => $nextRun]);
    }

    // DELETE /api/v1/reports/scheduled/{id}
    public function scheduledDestroy(Request $request, int $id): JsonResponse
    {
        $deleted = DB::table('scheduled_reports')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        if (! $deleted) {
            return $this->respondNotFound();
        }

        return $this->respondSuccess('Scheduled report deleted');
    }

    // HR reports (used in routes)
    public function attendance(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $records = Attendance::with('employee:id,first_name,last_name,employee_number')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'records' => $records,
                'total' => $records->count(),
            ],
        ]);
    }

    public function payroll(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $records = Payroll::with('employee:id,first_name,last_name')
            ->whereBetween('period_start', [$startDate, $endDate])
            ->orderByDesc('period_start')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'records' => $records,
                'total_gross' => $records->sum('gross_pay'),
                'total_net' => $records->sum('net_pay'),
            ],
        ]);
    }

    private function toCsv(array $rows): string
    {
        if (empty($rows)) {
            return '';
        }
        $header = array_keys((array) (is_array($rows[0]) ? $rows[0] : (array) $rows[0]));
        $lines = [implode(',', array_map(fn ($h) => '"'.$h.'"', $header))];
        foreach ($rows as $row) {
            $row = (array) $row;
            $lines[] = implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $row));
        }

        return implode("\n", $lines);
    }
}
