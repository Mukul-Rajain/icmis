<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Point Sanctum at the MongoDB-backed token model instead of
        // the default SQL one, which calls PDO::prepare() on a null connection.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
