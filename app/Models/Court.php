<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Court extends Model
{
    protected $connection = 'mongodb';
    protected $table      = 'courts';
    protected $guarded    = ['_id'];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
    ];

    public function cases()
    {
        return $this->hasMany(CourtCase::class, 'court_id');
    }
}
