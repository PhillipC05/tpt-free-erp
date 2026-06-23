<?php

namespace App\Models\Marketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'marketing_leads';

    protected $fillable = [
        'campaign_id', 'first_name', 'last_name', 'email', 'phone',
        'company', 'job_title', 'source', 'status', 'interest_score',
        'tags', 'notes', 'converted_to_customer_id', 'converted_at', 'assigned_to',
    ];

    protected $casts = [
        'tags' => 'array',
        'interest_score' => 'integer',
        'converted_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
