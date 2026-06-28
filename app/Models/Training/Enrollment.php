<?php

namespace App\Models\Training;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory;

    protected $table = 'training_enrollments';

    protected $fillable = [
        'session_id', 'employee_id', 'status', 'score', 'feedback', 'completed_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
