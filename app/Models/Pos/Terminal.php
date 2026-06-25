<?php

namespace App\Models\Pos;

use App\Models\Inventory\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Terminal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pos_terminals';

    protected $fillable = [
        'terminal_code', 'name', 'warehouse_id', 'assigned_to', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
