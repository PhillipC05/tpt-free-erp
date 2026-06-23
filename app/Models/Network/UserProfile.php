<?php

namespace App\Models\Network;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserProfile extends Model
{
    protected $table = 'network_user_profiles';

    protected $fillable = [
        'user_id', 'headline', 'bio', 'company', 'job_title', 'website',
        'location', 'avatar_path', 'is_discoverable', 'open_to',
        'opted_in_at', 'opted_out_at',
    ];

    protected $casts = [
        'is_discoverable' => 'boolean',
        'open_to' => 'array',
        'opted_in_at' => 'datetime',
        'opted_out_at' => 'datetime',
        'profile_views' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interests(): HasMany
    {
        return $this->hasMany(UserProfileInterest::class, 'user_profile_id');
    }
}
