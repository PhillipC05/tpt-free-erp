<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'hr_attendance';

    protected $fillable = [
        'employee_id', 'date', 'clock_in', 'clock_out', 'break_start', 'break_end',
        'total_hours', 'status', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime:H:i:s',
        'clock_out' => 'datetime:H:i:s',
        'total_hours' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}