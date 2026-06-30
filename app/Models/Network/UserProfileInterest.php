<?php

namespace App\Models\Network;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfileInterest extends Model
{
    protected $table = 'user_profile_interests';

    protected $fillable = ['user_profile_id', 'type', 'value'];

    public function userProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class);
    }
}
