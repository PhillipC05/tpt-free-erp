<?php

namespace App\Models\Fleet;

use App\Models\HR\Employee;
use App\Models\Inventory\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fleet_vehicles';

    protected $fillable = [
        'vehicle_code', 'make', 'model', 'year', 'vin', 'license_plate',
        'color', 'type', 'fuel_type', 'current_odometer', 'fuel_capacity',
        'fuel_level', 'status', 'assigned_driver_id', 'warehouse_id',
        'registration_expiry', 'insurance_expiry', 'notes',
    ];

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_driver_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'vehicle_id');
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class, 'vehicle_id');
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class, 'vehicle_id');
    }

    public function driver(): HasMany
    {
        return $this->hasMany(Driver::class);
    }
}
