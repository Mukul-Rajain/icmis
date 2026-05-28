<?php

namespace App\Models;

use Carbon\Carbon;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

/**
 * Central case document. Hearings, documents, and mentioning requests
 * are embedded arrays — read atomically with the case.
 */
class CourtCase extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $table      = 'cases';
    protected $guarded    = ['_id'];

    // ─── Status state machine ──────────────────────────────────
    public const STATUS_DRAFT           = 'draft';
    public const STATUS_SUBMITTED       = 'submitted';
    public const STATUS_UNDER_SCRUTINY  = 'under_scrutiny';
    public const STATUS_DEFECTIVE       = 'defective';
    public const STATUS_ACCEPTED        = 'accepted';
    public const STATUS_SCHEDULED       = 'scheduled';
    public const STATUS_ADJOURNED       = 'adjourned';
    public const STATUS_HEARD           = 'heard';
    public const STATUS_RESERVED        = 'reserved';
    public const STATUS_DISPOSED        = 'disposed';

    public const TRACK_REGULAR = 'regular';
    public const TRACK_FAST    = 'fast';
    public const TRACK_URGENT  = 'urgent';

    protected $casts = [
        'filing_date'                => 'datetime',
        'next_hearing_date'          => 'datetime',
        'last_hearing_date'          => 'datetime',
        'disposed_on'                => 'datetime',
        'expected_disposal_date'     => 'datetime',
        'priority_score'             => 'float',
        'is_high_priority'           => 'boolean',
        'has_interim_relief_pending' => 'boolean',
        'hearing_count'              => 'integer',
        'adjournment_count'          => 'integer',
        'cause_list_position'        => 'integer',
        // Embedded arrays stored as raw MongoDB documents
        'parties'              => 'array',
        'lawyers'              => 'array',
        'documents'            => 'array',
        'hearings'             => 'array',
        'mentioning_requests'  => 'array',
        'scrutiny'             => 'array',
    ];

    // ─── Relationships ─────────────────────────────────────────
    public function court()
    {
        return $this->belongsTo(Court::class, 'court_id');
    }

    public function filedBy()
    {
        return $this->belongsTo(User::class, 'filed_by');
    }

    public function assignedJudge()
    {
        return $this->belongsTo(User::class, 'assigned_judge_id');
    }

    // ─── Scopes ────────────────────────────────────────────────
    public function scopeForJudge($query, string $judgeId)
    {
        return $query->where('assigned_judge_id', $judgeId);
    }

    public function scopeForFiler($query, string $userId)
    {
        return $query->where('filed_by', $userId);
    }

    public function scopeForCourt($query, string $courtId)
    {
        return $query->where('court_id', $courtId);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_DISPOSED, self::STATUS_DRAFT]);
    }

    public function scopeScheduledToday($query)
    {
        return $query
            ->where('next_hearing_date', '>=', Carbon::today()->startOfDay())
            ->where('next_hearing_date', '<=', Carbon::today()->endOfDay());
    }

    public function scopeUnderScrutiny($query)
    {
        return $query->where('status', self::STATUS_UNDER_SCRUTINY);
    }

    // ─── Computed helpers ──────────────────────────────────────
    public function getAgeInDaysAttribute(): int
    {
        return $this->filing_date
            ? $this->filing_date->diffInDays(Carbon::today())
            : 0;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->expected_disposal_date
            && $this->status !== self::STATUS_DISPOSED
            && Carbon::today()->gt($this->expected_disposal_date);
    }

    /**
     * Auto-generate case number: COURT_CODE/CASE_TYPE/YYYY/NNNNN
     */
    public static function generateCaseNumber(string $courtCode, string $caseType): string
    {
        $year  = now()->year;
        $count = static::where('court_id', $courtCode)
                       ->whereYear('filing_date', $year)
                       ->count() + 1;

        return strtoupper("{$courtCode}/{$caseType}/{$year}/" . str_pad($count, 5, '0', STR_PAD_LEFT));
    }
}
