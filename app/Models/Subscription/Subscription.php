<?php

namespace App\Models\Subscription;

use App\Models\Sales\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'subscriptions';

    protected $fillable = [
        'subscription_number', 'customer_id', 'plan_id', 'status',
        'trial_ends_at', 'current_period_start', 'current_period_end',
        'cancelled_at', 'cancellation_reason', 'billing_anchor_day',
        'quantity', 'discount_percent', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'current_period_start' => 'date',
            'current_period_end' => 'date',
            'quantity' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    public function planChanges(): HasMany
    {
        return $this->hasMany(PlanChange::class);
    }

    public static function generateNumber(): string
    {
        $prefix = 'SUB-'.date('Ymd').'-';
        $last = static::where('subscription_number', 'like', $prefix.'%')
            ->orderByDesc('subscription_number')
            ->value('subscription_number');

        $seq = $last ? (int) substr($last, -5) + 1 : 1;

        return $prefix.str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing']);
    }

    public function currentUsage(string $usageType): float
    {
        return $this->usageRecords()
            ->where('usage_type', $usageType)
            ->where('period_start', '>=', $this->current_period_start)
            ->where('period_end', '<=', $this->current_period_end)
            ->sum('quantity');
    }
}
