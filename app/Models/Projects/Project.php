<?php

namespace App\Models\Projects;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'code', 'name', 'description', 'start_date', 'end_date', 'status',
        'priority', 'project_manager_id', 'budget', 'actual_cost',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'project_manager_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}