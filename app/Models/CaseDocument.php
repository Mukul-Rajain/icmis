<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id', 'uploaded_by_user_id', 'original_filename',
        'stored_path', 'mime_type', 'file_size_bytes',
        'document_type', 'version', 'description',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
