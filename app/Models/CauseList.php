<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CauseList extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'list_date', 'court_id', 'judge_id', 'status',
        'total_cases', 'generated_by_user_id',
        'generated_at', 'published_at',
    ];

    protected $casts = [
        'list_date' => 'date',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CauseListItem::class)->orderBy('serial_number');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }
}
