<?php

namespace App\Console\Commands;

use App\Models\Training\Certification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckCertificationExpiry extends Command
{
    protected $signature = 'certifications:check-expiry';

    protected $description = 'Create notifications for certifications expiring within 30 days';

    public function handle(): int
    {
        $certifications = Certification::where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->with('employee')
            ->get();

        $created = 0;

        foreach ($certifications as $cert) {
            $daysUntilExpiry = (int) now()->diffInDays($cert->expiry_date, false);

            if ($daysUntilExpiry < 0) {
                continue;
            }

            $alreadyNotified = DB::table('notifications')
                ->where('type', 'certification_expiry_warning')
                ->where('created_at', '>=', now()->startOfDay())
                ->whereJsonContains('data', ['certification_id' => $cert->id])
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            $employeeId = $cert->employee_id;

            DB::table('notifications')->insert([
                'id' => Str::uuid(),
                'type' => 'certification_expiry_warning',
                'notifiable_type' => App\Models\User::class,
                'notifiable_id' => $cert->employee?->user_id ?? 1,
                'data' => json_encode([
                    'certification_id' => $cert->id,
                    'certification_name' => $cert->certification_name,
                    'employee_id' => $employeeId,
                    'employee_name' => $cert->employee?->first_name.' '.$cert->employee?->last_name,
                    'days_until_expiry' => $daysUntilExpiry,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $created++;
            $this->info("Notification created for certification \"{$cert->certification_name}\" (expires in {$daysUntilExpiry} days)");
        }

        $this->info("Certification expiry check complete. {$created} notification(s) created.");

        return 0;
    }
}
