<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseParty extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id', 'user_id', 'party_type', 'name',
        'phone', 'email', 'address',
        'is_in_custody', 'is_senior_citizen',
    ];

    protected $casts = [
        'is_in_custody' => 'boolean',
        'is_senior_citizen' => 'boolean',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
