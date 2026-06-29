<?php

namespace App\Models\Documents;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    use HasFactory;

    protected $table = 'document_versions';

    protected $fillable = [
        'document_id', 'version_number', 'name', 'original_filename', 'storage_path',
        'mime_type', 'file_size', 'description', 'tags', 'uploaded_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'file_size' => 'integer',
        'version_number' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
