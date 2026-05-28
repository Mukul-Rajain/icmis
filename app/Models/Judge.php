<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Judge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'court_id', 'courtroom_number', 'specializations',
        'max_daily_cases', 'current_pending_count', 'appointment_date',
        'is_available', 'unavailable_until',
    ];

    protected $casts = [
        'specializations' => 'array',
        'is_available' => 'boolean',
        'appointment_date' => 'date',
        'unavailable_until' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function assignedCases(): HasMany
    {
        return $this->hasMany(CourtCase::class, 'assigned_judge_id');
    }

    public function hearings(): HasMany
    {
        return $this->hasMany(Hearing::class);
    }

    public function causeLists(): HasMany
    {
        return $this->hasMany(CauseList::class);
    }

    /**
     * Check if the judge is available on a specific date.
     */
    public function isAvailableOn(\Carbon\Carbon $date): bool
    {
        if (! $this->is_available) {
            return false;
        }

        if ($this->unavailable_until && $date->lte($this->unavailable_until)) {
            return false;
        }

        return true;
    }
}
