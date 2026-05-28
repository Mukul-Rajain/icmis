<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SPA Catch-all — all web traffic served by React
|--------------------------------------------------------------------------
| The React app (app.jsx) handles its own routing via react-router-dom.
| Laravel only needs to return the SPA shell for every non-API URL.
|--------------------------------------------------------------------------
*/
Route::get('/{any}', fn () => view('spa'))->where('any', '.*')->name('spa');
