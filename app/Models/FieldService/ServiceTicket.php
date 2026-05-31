<?php

namespace App\Models\FieldService;

use App\Models\HR\Employee;
use App\Models\Sales\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTicket extends Model
{
    use HasFactory;
    protected $table = 'field_service_tickets';

    protected $fillable = [
        'ticket_number', 'customer_id', 'title', 'description',
        'priority', 'status', 'assigned_to', 'scheduled_date',
        'resolved_at', 'resolution_notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'resolved_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }
}
