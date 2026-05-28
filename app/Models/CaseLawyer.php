<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseLawyer extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id', 'lawyer_id', 'representing_party_id',
        'role', 'engaged_on', 'disengaged_on', 'is_active',
    ];

    protected $casts = [
        'engaged_on' => 'date',
        'disengaged_on' => 'date',
        'is_active' => 'boolean',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lawyer_id');
    }

    public function representingParty(): BelongsTo
    {
        return $this->belongsTo(CaseParty::class, 'representing_party_id');
    }
}
