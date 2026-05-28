<?php

namespace App\Models\Finance;

use App\Models\HR\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use SoftDeletes;

    protected $table = 'finance_budgets';

    protected $fillable = [
        'code', 'name', 'account_id', 'department_id', 'fiscal_year', 'period',
        'period_number', 'budgeted_amount', 'actual_amount', 'status',
        'start_date', 'end_date', 'notes', 'created_by',
    ];

    protected $casts = [
        'budgeted_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getVarianceAttribute(): float
    {
        return (float) $this->budgeted_amount - (float) $this->actual_amount;
    }

    public function getUtilizationPercentAttribute(): float
    {
        if ($this->budgeted_amount == 0) {
            return 0;
        }

        return round((float) $this->actual_amount / (float) $this->budgeted_amount * 100, 2);
    }
}
