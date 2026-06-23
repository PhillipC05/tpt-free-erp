<?php

namespace App\Jobs;

use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use App\Models\HR\Employee;
use App\Models\HR\Attendance;
use App\Models\HR\Payroll;
use App\Models\Sales\Customer;
use App\Models\Sales\Order;
use App\Models\Procurement\Vendor;
use App\Models\Procurement\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ReportGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        private readonly int $userId,
        private readonly string $reportType,
        private readonly array $parameters = [],
        private readonly string $format = 'json',
        private readonly ?string $deliveryEmail = null,
        private readonly ?int $scheduledReportId = null,
        private readonly ?int $generatedReportId = null,
    ) {}

    public function handle(): void
    {
        // Find or create the generated_reports record
        $reportId = $this->generatedReportId ?? DB::table('generated_reports')->insertGetId([
            'user_id'     => $this->userId,
            'report_type' => $this->reportType,
            'parameters'  => json_encode($this->parameters),
            'format'      => $this->format,
            'status'      => 'running',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('generated_reports')->where('id', $reportId)->update(['status' => 'running', 'updated_at' => now()]);

        try {
            $data = $this->executeReport();
            $rowCount = is_array($data) ? count($data['rows'] ?? $data) : 0;
            $resultJson = json_encode($data);

            DB::table('generated_reports')->where('id', $reportId)->update([
                'status'       => 'completed',
                'result_data'  => $resultJson,
                'row_count'    => $rowCount,
                'completed_at' => now(),
                'expires_at'   => now()->addDays(7),
                'updated_at'   => now(),
            ]);

            if ($this->deliveryEmail) {
                $this->sendEmailDelivery($this->deliveryEmail, $data, $reportId);
            }
        } catch (\Throwable $e) {
            Log::error("ReportGenerationJob failed for type={$this->reportType}: " . $e->getMessage());
            DB::table('generated_reports')->where('id', $reportId)->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'updated_at'    => now(),
            ]);
        }
    }

    private function executeReport(): array
    {
        $params = $this->parameters;
        $startDate = $params['start_date'] ?? now()->startOfYear()->toDateString();
        $endDate   = $params['end_date'] ?? now()->toDateString();

        return match ($this->reportType) {
            'trial_balance'    => $this->trialBalance($params['date'] ?? $endDate),
            'income_statement' => $this->incomeStatement($startDate, $endDate),
            'balance_sheet'    => $this->balanceSheet($params['date'] ?? $endDate),
            'cash_flow'        => $this->cashFlow($startDate, $endDate),
            'hr_attendance'    => $this->hrAttendance($startDate, $endDate),
            'hr_payroll'       => $this->hrPayroll($startDate, $endDate),
            'sales_summary'    => $this->salesSummary($startDate, $endDate),
            'procurement'      => $this->procurementSummary($startDate, $endDate),
            default            => throw new \InvalidArgumentException("Unknown report type: {$this->reportType}"),
        };
    }

    private function trialBalance(string $date): array
    {
        $accounts = Account::where('is_active', true)->get();
        $rows = [];
        foreach ($accounts as $account) {
            $debits = Transaction::where('account_id', $account->id)->where('type', 'debit')
                ->where('status', 'posted')->where('transaction_date', '<=', $date)->sum('amount');
            $credits = Transaction::where('account_id', $account->id)->where('type', 'credit')
                ->where('status', 'posted')->where('transaction_date', '<=', $date)->sum('amount');
            $rows[] = [
                'account_code'  => $account->code,
                'account_name'  => $account->name,
                'type'          => $account->type,
                'total_debits'  => (float) $debits,
                'total_credits' => (float) $credits,
                'balance'       => (float) ($debits - $credits),
            ];
        }
        return ['report' => 'trial_balance', 'date' => $date, 'rows' => $rows];
    }

    private function incomeStatement(string $startDate, string $endDate): array
    {
        $revenues = Account::where('type', 'revenue')->where('is_active', true)->get()
            ->map(fn($a) => ['code' => $a->code, 'name' => $a->name, 'balance' => $this->getBalance($a->id, $startDate, $endDate)]);
        $expenses = Account::where('type', 'expense')->where('is_active', true)->get()
            ->map(fn($a) => ['code' => $a->code, 'name' => $a->name, 'balance' => $this->getBalance($a->id, $startDate, $endDate)]);
        return [
            'report'        => 'income_statement',
            'period'        => ['start' => $startDate, 'end' => $endDate],
            'revenues'      => $revenues,
            'expenses'      => $expenses,
            'total_revenue' => $revenues->sum('balance'),
            'total_expense' => $expenses->sum('balance'),
            'net_income'    => $revenues->sum('balance') - $expenses->sum('balance'),
            'rows'          => $revenues->concat($expenses)->toArray(),
        ];
    }

    private function balanceSheet(string $date): array
    {
        $assets      = Account::where('type', 'asset')->where('is_active', true)->get()->map(fn($a) => ['code' => $a->code, 'name' => $a->name, 'balance' => $this->getBalance($a->id, null, $date)]);
        $liabilities = Account::where('type', 'liability')->where('is_active', true)->get()->map(fn($a) => ['code' => $a->code, 'name' => $a->name, 'balance' => $this->getBalance($a->id, null, $date)]);
        $equity      = Account::where('type', 'equity')->where('is_active', true)->get()->map(fn($a) => ['code' => $a->code, 'name' => $a->name, 'balance' => $this->getBalance($a->id, null, $date)]);
        return [
            'report'      => 'balance_sheet',
            'date'        => $date,
            'assets'      => $assets,
            'liabilities' => $liabilities,
            'equity'      => $equity,
            'rows'        => $assets->concat($liabilities)->concat($equity)->toArray(),
        ];
    }

    private function cashFlow(string $startDate, string $endDate): array
    {
        $rows = Transaction::where('status', 'posted')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->join('finance_accounts', 'finance_transactions.account_id', '=', 'finance_accounts.id')
            ->select('finance_transactions.*', 'finance_accounts.type as account_type', 'finance_accounts.name as account_name')
            ->get()
            ->toArray();
        return ['report' => 'cash_flow', 'period' => ['start' => $startDate, 'end' => $endDate], 'rows' => $rows];
    }

    private function hrAttendance(string $startDate, string $endDate): array
    {
        $rows = Attendance::with('employee:id,first_name,last_name,employee_number')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get()
            ->toArray();
        return ['report' => 'hr_attendance', 'period' => ['start' => $startDate, 'end' => $endDate], 'rows' => $rows];
    }

    private function hrPayroll(string $startDate, string $endDate): array
    {
        $rows = Payroll::with('employee:id,first_name,last_name')
            ->whereBetween('period_start', [$startDate, $endDate])
            ->orderBy('period_start')
            ->get()
            ->toArray();
        return ['report' => 'hr_payroll', 'period' => ['start' => $startDate, 'end' => $endDate], 'rows' => $rows];
    }

    private function salesSummary(string $startDate, string $endDate): array
    {
        $rows = Order::with('customer:id,name')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->orderBy('order_date')
            ->get()
            ->toArray();
        return ['report' => 'sales_summary', 'period' => ['start' => $startDate, 'end' => $endDate], 'rows' => $rows];
    }

    private function procurementSummary(string $startDate, string $endDate): array
    {
        $rows = PurchaseOrder::with('vendor:id,name')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->orderBy('order_date')
            ->get()
            ->toArray();
        return ['report' => 'procurement', 'period' => ['start' => $startDate, 'end' => $endDate], 'rows' => $rows];
    }

    private function getBalance(int $accountId, ?string $startDate, ?string $endDate): float
    {
        $q = Transaction::where('account_id', $accountId)->where('status', 'posted');
        if ($startDate) $q->where('transaction_date', '>=', $startDate);
        if ($endDate) $q->where('transaction_date', '<=', $endDate);
        $debits = (float) (clone $q)->where('type', 'debit')->sum('amount');
        $credits = (float) (clone $q)->where('type', 'credit')->sum('amount');
        return $debits - $credits;
    }

    private function sendEmailDelivery(string $email, array $data, int $reportId): void
    {
        // Email delivery placeholder — wire to Laravel Mail when mail is configured
        Log::info("Report #{$reportId} ready for delivery to {$email}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("ReportGenerationJob permanently failed: " . $e->getMessage());
        if ($this->generatedReportId) {
            DB::table('generated_reports')->where('id', $this->generatedReportId)->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'updated_at'    => now(),
            ]);
        }
    }
}
