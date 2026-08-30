<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DelegateRosterController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PlayerPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicTournamentController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInviteController;
use App\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/torneos/{tournament}/reglamento', [TournamentController::class, 'rules'])->name('tournaments.rules');

Route::prefix('t')->name('public.tournaments.')->group(function () {
    Route::get('{slug}', [PublicTournamentController::class, 'show'])->name('show');
    Route::get('{slug}/fixture', [PublicTournamentController::class, 'fixture'])->name('fixture');
    Route::get('{slug}/tabla', [PublicTournamentController::class, 'standings'])->name('standings');
    Route::get('{slug}/goleadores', [PublicTournamentController::class, 'scorers'])->name('scorers');
    Route::get('{slug}/reglamento', [PublicTournamentController::class, 'rules'])->name('rules');
});

Route::get('/invitar/{token}', [TeamInviteController::class, 'show'])->name('invites.show');
Route::post('/invitar/{token}/aceptar', [TeamInviteController::class, 'accept'])->name('invites.accept');

Route::get('/jugador/entrar', [PlayerPortalController::class, 'create'])->name('player.login');
Route::post('/jugador/entrar', [PlayerPortalController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('player.login.store');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/jugador', [PlayerPortalController::class, 'home'])->name('player.home');

    Route::get('/delegado', [DelegateRosterController::class, 'index'])->name('delegate.index');
    Route::get('/delegado/equipos/{team}/plantilla', [DelegateRosterController::class, 'roster'])->name('delegate.roster');
    Route::post('/delegado/equipos/{team}/jugadores', [DelegateRosterController::class, 'storePlayer'])->name('delegate.players.store');
    Route::put('/delegado/equipos/{team}/jugadores/{player}', [DelegateRosterController::class, 'updatePlayer'])->name('delegate.players.update');

    Route::middleware('role:admin,organizer')->group(function () {
        Route::resource('tournaments', TournamentController::class);
        Route::post('tournaments/{tournament}/teams', [TournamentController::class, 'enrollTeam'])->name('tournaments.enroll');
        Route::post('tournaments/{tournament}/invites', [TeamInviteController::class, 'create'])->name('tournaments.invites.create');
        Route::post('tournaments/{tournament}/fixture', [TournamentController::class, 'generateFixture'])->name('tournaments.fixture');
        Route::delete('tournaments/{tournament}/fixture', [TournamentController::class, 'resetFixture'])->name('tournaments.fixture.reset');
        Route::post('tournaments/{tournament}/postpone-matchday', [TournamentController::class, 'postponeMatchday'])->name('tournaments.postpone-matchday');

        Route::resource('teams', TeamController::class);
        Route::resource('players', PlayerController::class);

        Route::get('games/{game}', [GameController::class, 'show'])->name('games.show');
        Route::patch('games/{game}', [GameController::class, 'updateScore'])->name('games.score');
        Route::post('games/{game}/events', [GameController::class, 'storeEvent'])->name('games.events.store');
        Route::delete('games/{game}/events/{event}', [GameController::class, 'destroyEvent'])->name('games.events.destroy');
        Route::post('games/{game}/attendance', [GameController::class, 'saveAttendance'])->name('games.attendance');
        Route::patch('games/{game}/reschedule', [GameController::class, 'reschedule'])->name('games.reschedule');
        Route::post('games/{game}/walkover', [GameController::class, 'walkover'])->name('games.walkover');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
