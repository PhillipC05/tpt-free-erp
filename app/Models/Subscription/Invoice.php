<?php

namespace App\Models\Subscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'subscription_invoices';

    protected $fillable = [
        'invoice_number', 'subscription_id', 'amount', 'tax_amount',
        'discount_amount', 'total_amount', 'status', 'period_start',
        'period_end', 'due_date', 'paid_at', 'payment_method', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public static function generateNumber(): string
    {
        $prefix = 'SINV-'.date('Ymd').'-';
        $last = static::where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $seq = $last ? (int) substr($last, -5) + 1 : 1;

        return $prefix.str_pad($seq, 5, '0', STR_PAD_LEFT);
    }
}
