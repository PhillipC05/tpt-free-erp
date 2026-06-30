<?php

namespace App\Models\Donors;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'donor_id', 'title', 'grant_number', 'amount', 'status',
        'start_date', 'end_date', 'purpose', 'requirements',
        'funded_amount', 'spent_amount', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'funded_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(GrantDisbursement::class);
    }

    public function remainingAmount(): float
    {
        return (float) $this->funded_amount - (float) $this->spent_amount;
    }
}
