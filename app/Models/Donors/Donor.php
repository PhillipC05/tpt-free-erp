<?php

namespace App\Models\Donors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'email', 'phone', 'address',
        'contact_person', 'total_contributed', 'status', 'notes',
    ];

    protected $casts = [
        'total_contributed' => 'decimal:2',
    ];

    public function grants(): HasMany
    {
        return $this->hasMany(Grant::class);
    }
}
