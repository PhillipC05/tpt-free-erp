<?php

namespace App\Models\Network;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NetworkPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'network_posts';

    protected $fillable = [
        'user_id', 'body', 'type', 'visibility', 'likes_count', 'comments_count',
    ];

    protected $casts = [
        'likes_count' => 'integer',
        'comments_count' => 'integer',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(NetworkPostReaction::class, 'post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(NetworkPostComment::class, 'post_id');
    }
}
