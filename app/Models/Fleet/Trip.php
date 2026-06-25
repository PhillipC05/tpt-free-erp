<?php

namespace App\Models\Fleet;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fleet_trips';

    protected $fillable = [
        'trip_number', 'vehicle_id', 'driver_id', 'start_location',
        'end_location', 'start_odometer', 'end_odometer', 'distance',
        'start_time', 'end_time', 'status', 'purpose', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_odometer' => 'decimal:1',
            'end_odometer' => 'decimal:1',
            'distance' => 'decimal:1',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class, 'trip_id');
    }

    public static function generateTripNumber(): string
    {
        $prefix = 'TRIP-'.date('Ymd').'-';
        $last = static::where('trip_number', 'like', $prefix.'%')
            ->orderByDesc('trip_number')
            ->value('trip_number');

        if ($last) {
            $seq = (int) substr($last, -5) + 1;
        } else {
            $seq = 1;
        }

        return $prefix.str_pad($seq, 5, '0', STR_PAD_LEFT);
    }
}
