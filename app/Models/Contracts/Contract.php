<?php

namespace App\Models\Contracts;

use App\Models\Procurement\Vendor;
use App\Models\Sales\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contracts';

    protected $fillable = [
        'title', 'contract_number', 'type', 'status', 'start_date', 'end_date',
        'value', 'currency', 'description', 'customer_id', 'vendor_id',
        'project_id', 'created_by', 'signed_by', 'signed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'value' => 'decimal:2',
        'signed_at' => 'datetime',
    ];

    public function milestones(): HasMany
    {
        return $this->hasMany(ContractMilestone::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
