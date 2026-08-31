<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DelegateApiController;
use App\Http\Controllers\Api\PlayerAuthController;
use App\Http\Controllers\Api\PublicTournamentApiController;
use App\Http\Controllers\Api\TournamentApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/player/login', [PlayerAuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/player/home', [PlayerAuthController::class, 'home']);

    Route::get('/delegate/teams', [DelegateApiController::class, 'teams']);
    Route::get('/delegate/tournaments', [DelegateApiController::class, 'tournaments']);
    Route::get('/delegate/teams/{team}/roster', [DelegateApiController::class, 'roster']);
    Route::post('/delegate/teams/{team}/players', [DelegateApiController::class, 'storePlayer']);
    Route::put('/delegate/teams/{team}/players/{player}', [DelegateApiController::class, 'updatePlayer']);
    Route::post('/delegate/teams/{team}/players/{player}/photos', [DelegateApiController::class, 'uploadPhotos']);
    Route::post('/delegate/tournaments/{tournament}/exceptions', [DelegateApiController::class, 'requestException']);
    Route::get('/delegate/tournaments/{tournament}/suspensions', [DelegateApiController::class, 'suspensions']);
    Route::post('/delegate/tournaments/{tournament}/sentences', [DelegateApiController::class, 'storeSentence']);

    // Vista de torneo por slug (requiere token)
    Route::prefix('t')->group(function () {
        Route::get('{slug}', [PublicTournamentApiController::class, 'show']);
        Route::get('{slug}/fixture', [PublicTournamentApiController::class, 'fixture']);
        Route::get('{slug}/standings', [PublicTournamentApiController::class, 'standings']);
        Route::get('{slug}/scorers', [PublicTournamentApiController::class, 'scorers']);
        Route::get('{slug}/rules', [PublicTournamentApiController::class, 'rules']);
    });

    Route::get('/tournaments', [TournamentApiController::class, 'index']);
    Route::get('/tournaments/{tournament}', [TournamentApiController::class, 'show']);
    Route::get('/games/{game}', [TournamentApiController::class, 'game']);
    Route::get('/players/{player}', [TournamentApiController::class, 'player']);
});
