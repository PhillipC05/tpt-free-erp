<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillMarketplaceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name', 'description', 'category', 'author',
        'github_url', 'version', 'downloads_count', 'is_featured',
        'rating', 'tags', 'installed_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'downloads_count' => 'integer',
        'rating' => 'decimal:2',
        'tags' => 'array',
        'installed_at' => 'datetime',
    ];

    public function installs(): HasMany
    {
        return $this->hasMany(SkillMarketplaceInstall::class, 'marketplace_item_id');
    }
}
