<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\Filer\CaseController as FilerCaseController;
use App\Http\Controllers\Api\Registry\RegistryController;
use App\Http\Controllers\Api\Judge\JudgeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC — No authentication required
|--------------------------------------------------------------------------
*/
Route::prefix('public')->group(function () {
    Route::get('stats',        [PublicController::class, 'stats']);
    Route::get('leaderboard',  [PublicController::class, 'leaderboard']);
    Route::get('case-status',  [PublicController::class, 'caseStatus']);
    Route::get('courts',       [PublicController::class, 'courts']);
});

/*
|--------------------------------------------------------------------------
| AUTH — Register / Login / Logout
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me',      [AuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| FILER — Lawyers & Litigants
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:filer'])
    ->prefix('cases')
    ->group(function () {
        Route::get('/',    [FilerCaseController::class, 'index']);
        Route::post('/',   [FilerCaseController::class, 'store']);
        Route::get('/{id}',    [FilerCaseController::class, 'show']);
        Route::patch('/{id}',  [FilerCaseController::class, 'update']);
        Route::post('/{id}/submit',                 [FilerCaseController::class, 'submit']);
        Route::post('/{id}/documents',              [FilerCaseController::class, 'uploadDocument']);
        Route::delete('/{id}/documents/{docId}',    [FilerCaseController::class, 'deleteDocument']);
        Route::get('/{id}/documents/{docId}/view',  [FilerCaseController::class, 'serveDocument']);
        Route::post('/{id}/mention',                [FilerCaseController::class, 'requestMention']);
    });

Route::middleware(['auth:sanctum', 'role:filer'])
    ->prefix('filer')
    ->group(function () {
        Route::get('inbox',    [FilerCaseController::class, 'inbox']);
        Route::get('schedule', [FilerCaseController::class, 'schedule']);
    });

/*
|--------------------------------------------------------------------------
| REGISTRY — Scrutiny & Scheduling
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:registry'])
    ->prefix('registry')
    ->group(function () {
        Route::get('queue',                              [RegistryController::class, 'queue']);
        Route::get('queue/{id}',                         [RegistryController::class, 'show']);
        Route::get('judges',                             [RegistryController::class, 'judges']);
        Route::patch('cases/{id}/approve',               [RegistryController::class, 'approve']);
        Route::patch('cases/{id}/reject',                [RegistryController::class, 'reject']);
        Route::get('cases/{id}/documents/{docId}/view', [RegistryController::class, 'serveDocument']);
    });

/*
|--------------------------------------------------------------------------
| JUDGE — Cause List & Mentions
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:judge'])
    ->prefix('judge')
    ->group(function () {
        Route::get('causelist',                     [JudgeController::class, 'causeList']);
        Route::patch('causelist/reorder',           [JudgeController::class, 'reorder']);
        Route::patch('cases/{id}/priority',         [JudgeController::class, 'togglePriority']);
        Route::get('cases/{id}/preview',                        [JudgeController::class, 'preview']);
        Route::get('cases/{id}/documents/{docId}/view',         [JudgeController::class, 'serveDocument']);
        Route::post('hearings/{id}/record',         [JudgeController::class, 'recordHearing']);
        Route::get('mentions',                      [JudgeController::class, 'mentionQueue']);
        Route::patch('mentions/{caseId}/{reqId}/accept', [JudgeController::class, 'acceptMention']);
        Route::patch('mentions/{caseId}/{reqId}/reject', [JudgeController::class, 'rejectMention']);
    });
