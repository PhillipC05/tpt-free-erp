<?php

namespace App\Models\Recruitment;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    use HasFactory;

    protected $table = 'recruitment_interviews';

    protected $fillable = [
        'application_id', 'interview_type', 'scheduled_at', 'duration_minutes',
        'location', 'interviewer_id', 'status', 'score', 'feedback', 'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'score' => 'decimal:2',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'interviewer_id');
    }
}
