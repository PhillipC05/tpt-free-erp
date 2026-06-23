<?php

namespace App\Models\Network;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NetworkPostComment extends Model
{
    use SoftDeletes;

    protected $table = 'network_post_comments';

    protected $fillable = ['post_id', 'user_id', 'parent_id', 'body'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(NetworkPost::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
