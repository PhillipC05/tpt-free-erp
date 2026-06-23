<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanExpiredReports extends Command
{
    protected $signature = 'reports:clean-expired';
    protected $description = 'Delete generated reports past their expiry date';

    public function handle(): int
    {
        $expired = DB::table('generated_reports')
            ->where('expires_at', '<', now())
            ->get(['id', 'result_path']);

        foreach ($expired as $report) {
            if ($report->result_path && Storage::exists($report->result_path)) {
                Storage::delete($report->result_path);
            }
        }

        DB::table('generated_reports')
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("Cleaned {$expired->count()} expired reports.");
        return 0;
    }
}
