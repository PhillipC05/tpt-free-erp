<?php

namespace App\Models\Training;

use App\Models\Lms\Course;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'training_programs';

    protected $fillable = [
        'code', 'name', 'description', 'course_id', 'type',
        'duration_hours', 'cost', 'is_mandatory', 'is_active',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'program_id');
    }

    public function enrollments(): HasManyThrough
    {
        return $this->hasManyThrough(Enrollment::class, Session::class, 'program_id', 'session_id');
    }
}
