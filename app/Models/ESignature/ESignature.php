<?php

namespace App\Models\ESignature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ESignature extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'e_signatures';

    protected $fillable = [
        'signable_type', 'signable_id', 'token', 'status',
        'signer_name', 'signer_email', 'signer_ip', 'signer_user_agent',
        'signature_data', 'signature_type', 'document_hash', 'signed_hash',
        'audit_log', 'message', 'expires_at', 'signed_at',
        'requested_by', 'signed_by_user_id',
    ];

    protected $casts = [
        'audit_log' => 'array',
        'expires_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    // Hide raw signature data from list responses
    protected $hidden = ['signature_data', 'signer_user_agent'];

    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function signedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    public function appendAudit(string $event, array $context = []): void
    {
        $log = $this->audit_log ?? [];
        $log[] = array_merge(['event' => $event, 'at' => now()->toIso8601String()], $context);
        $this->update(['audit_log' => $log]);
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function hashSignable(array $data): string
    {
        return hash('sha256', json_encode($data));
    }
}
