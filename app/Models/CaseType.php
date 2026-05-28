<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseType extends Model
{
    use HasFactory;

    public const TRACK_FAST = 'fast';
    public const TRACK_STANDARD = 'standard';
    public const TRACK_COMPLEX = 'complex';

    protected $fillable = [
        'name', 'code', 'category', 'default_track',
        'base_priority', 'typical_duration_days',
        'is_time_sensitive', 'description', 'is_active',
    ];

    protected $casts = [
        'is_time_sensitive' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function cases(): HasMany
    {
        return $this->hasMany(CourtCase::class);
    }
}
