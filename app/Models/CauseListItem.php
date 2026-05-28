<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CauseListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cause_list_id', 'case_id', 'hearing_id',
        'serial_number', 'estimated_time_slot',
        'estimated_duration_minutes',
        'priority_score_at_listing', 'track_at_listing',
    ];

    protected $casts = [
        'priority_score_at_listing' => 'decimal:2',
    ];

    public function causeList(): BelongsTo
    {
        return $this->belongsTo(CauseList::class);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    public function hearing(): BelongsTo
    {
        return $this->belongsTo(Hearing::class);
    }
}
