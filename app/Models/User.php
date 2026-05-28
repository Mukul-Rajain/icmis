<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Laravel\Sanctum\HasApiTokens;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class User extends Model implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens, Notifiable;

    protected $connection = 'mongodb';
    protected $table      = 'users';
    protected $guarded    = ['_id'];

    public const ROLE_FILER    = 'filer';
    public const ROLE_REGISTRY = 'registry';
    public const ROLE_JUDGE    = 'judge';
    public const ROLE_ADMIN    = 'admin';

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'         => 'boolean',
        'filer_profile'     => 'array',
        'judge_profile'     => 'array',
        'registry_profile'  => 'array',
        'password'          => 'hashed',
    ];

    // ─── Helpers ──────────────────────────────────────────────
    public function isFiler(): bool    { return $this->role === self::ROLE_FILER; }
    public function isRegistry(): bool { return $this->role === self::ROLE_REGISTRY; }
    public function isJudge(): bool    { return $this->role === self::ROLE_JUDGE; }
    public function isAdmin(): bool    { return $this->role === self::ROLE_ADMIN; }

    // ─── Scopes ───────────────────────────────────────────────
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Relationships ────────────────────────────────────────
    public function filedCases()
    {
        return $this->hasMany(CourtCase::class, 'filed_by');
    }

    public function assignedCases()
    {
        return $this->hasMany(CourtCase::class, 'assigned_judge_id');
    }
}
