<?php

namespace App\Models\Recruitment;

use App\Models\ESignature\ESignature;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Application extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'recruitment_applications';

    protected $fillable = [
        'application_number', 'job_id', 'candidate_name', 'candidate_email',
        'candidate_phone', 'resume_path', 'cover_letter', 'expected_salary',
        'status', 'rejection_reason', 'reviewed_by', 'reviewed_at',
        'tracking_token', 'offer_letter_content', 'offer_letter_generated_at',
    ];

    protected $casts = [
        'expected_salary' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'offer_letter_generated_at' => 'datetime',
    ];

    public function offerLetterSignature()
    {
        return $this->morphOne(ESignature::class, 'signable');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class, 'application_id');
    }

    public static function generateNumber(): string
    {
        $prefix = 'APP-'.date('Ymd-His').'-';
        $last = static::where('application_number', 'like', $prefix.'%')
            ->orderByDesc('application_number')
            ->value('application_number');

        $seq = $last ? (int) substr($last, -5) + 1 : 1;

        return $prefix.str_pad($seq, 5, '0', STR_PAD_LEFT);
    }
}
