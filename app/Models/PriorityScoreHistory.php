<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriorityScoreHistory extends Model
{
    use HasFactory;

    protected $table = 'priority_score_history';

    public $timestamps = false;

    protected $fillable = [
        'case_id', 'score', 'factors', 'computed_by', 'computed_at',
    ];

    protected $casts = [
        'factors' => 'array',
        'computed_at' => 'datetime',
        'score' => 'decimal:2',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }
}
