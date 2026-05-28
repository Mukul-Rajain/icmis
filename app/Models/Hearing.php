<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hearing extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ADJOURNED = 'adjourned';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'case_id', 'judge_id', 'court_id',
        'scheduled_date', 'scheduled_time', 'courtroom_number',
        'estimated_duration_minutes', 'status', 'stage_at_hearing',
        'outcome', 'next_action', 'next_hearing_date',
        'actual_start_time', 'actual_end_time',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'next_hearing_date' => 'date',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function adjournments(): HasMany
    {
        return $this->hasMany(Adjournment::class);
    }
}
