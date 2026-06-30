<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillMarketplaceInstall extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_item_id', 'installed_by', 'installed_at', 'uninstalled_at',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'uninstalled_at' => 'datetime',
    ];

    public function marketplaceItem(): BelongsTo
    {
        return $this->belongsTo(SkillMarketplaceItem::class, 'marketplace_item_id');
    }

    public function installer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by');
    }
}
