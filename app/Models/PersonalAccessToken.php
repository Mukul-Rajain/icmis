<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumToken;
use MongoDB\Laravel\Eloquent\DocumentModel;

/**
 * Extends Sanctum's own PersonalAccessToken so the NewAccessToken
 * type-hint is satisfied, while DocumentModel redirects all queries
 * to MongoDB instead of a SQL PDO connection.
 */
class PersonalAccessToken extends SanctumToken
{
    use DocumentModel;

    protected $connection  = 'mongodb';
    protected $table       = 'personal_access_tokens';
    protected $primaryKey  = '_id';
    protected $keyType     = 'string';
    protected $guarded     = ['_id'];
}
