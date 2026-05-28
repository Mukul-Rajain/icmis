<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Adjournment extends Model
{
    use HasFactory;

    protected $fillable = [
        'hearing_id', 'case_id', 'requested_by_user_id', 'requested_by_role',
        'reason', 'reason_category', 'granted', 'new_date',
        'judge_remarks', 'decided_by_judge_id',
    ];

    protected $casts = [
        'granted' => 'boolean',
        'new_date' => 'date',
    ];

    public function hearing(): BelongsTo
    {
        return $this->belongsTo(Hearing::class);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function decidedByJudge(): BelongsTo
    {
        return $this->belongsTo(Judge::class, 'decided_by_judge_id');
    }
}
