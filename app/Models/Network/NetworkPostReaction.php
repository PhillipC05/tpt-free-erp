<?php

namespace App\Models\Network;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkPostReaction extends Model
{
    protected $table = 'network_post_reactions';

    protected $fillable = ['post_id', 'user_id', 'type'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(NetworkPost::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
