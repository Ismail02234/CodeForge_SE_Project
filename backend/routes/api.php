<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\GamificationController;
use App\Http\Controllers\GhostRaceController;
use App\Http\Controllers\PerformanceProfileController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RivalryController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SqlBattleController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/public/stats', [PublicController::class, 'stats']);
Route::get('/universities/options', [PublicController::class, 'universities']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', fn (Request $request) => $request->user());
    Route::get('/users/options', [UserController::class, 'options']);
    Route::get('/dashboard', [DashboardController::class, 'show']);

    Route::get('/problems', [ProblemController::class, 'index']);
    Route::get('/problems/{id}', [ProblemController::class, 'show']);
    Route::post('/problems/{id}/session', [ProblemController::class, 'start']);
    Route::post('/problems/{id}/submit', [ProblemController::class, 'submit']);

    Route::get('/performance-profile', [PerformanceProfileController::class, 'me']);
    Route::get('/performance-profile/{userId}', [PerformanceProfileController::class, 'show']);
    Route::get('/profiles/{id}', [ProfileController::class, 'show']);
    Route::get('/gamification', [GamificationController::class, 'me']);
    Route::get('/rivalry', [RivalryController::class, 'compare']);

    Route::get('/universities', [UniversityController::class, 'index']);
    Route::get('/universities/compare', [UniversityController::class, 'compare']);
    Route::get('/search', [SearchController::class, 'index']);

    Route::get('/contests', [ContestController::class, 'index']);
    Route::post('/contests', [ContestController::class, 'store'])->middleware('admin');
    Route::get('/contests/{id}', [ContestController::class, 'show']);
    Route::post('/contests/{id}/join', [ContestController::class, 'join']);
    Route::post('/contests/{id}/close', [ContestController::class, 'close'])->middleware('admin');
    Route::post('/duels', [ContestController::class, 'duel']);

    Route::get('/ghost-races/options', [GhostRaceController::class, 'options']);
    Route::get('/ghost-races/history', [GhostRaceController::class, 'history']);
    Route::post('/ghost-races', [GhostRaceController::class, 'store']);
    Route::get('/ghost-races/{id}', [GhostRaceController::class, 'show']);
    Route::post('/ghost-races/{id}/submit', [GhostRaceController::class, 'submit']);
    Route::post('/ghost-races/{id}/forfeit', [GhostRaceController::class, 'forfeit']);

    Route::get('/sql/challenges', [SqlBattleController::class, 'challenges']);
    Route::get('/sql/opponents', [SqlBattleController::class, 'opponents']);
    Route::get('/sql/leaderboard', [SqlBattleController::class, 'leaderboard']);
    Route::get('/sql/battles', [SqlBattleController::class, 'recent']);
    Route::post('/sql/battles', [SqlBattleController::class, 'createBattle']);
    Route::get('/sql/battles/{id}', [SqlBattleController::class, 'battle']);
    Route::post('/sql/challenges/{id}/submit', [SqlBattleController::class, 'submit']);

    Route::get('/database/users', [DatabaseController::class, 'users']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/tables', [AdminController::class, 'tables']);
        Route::get('/tables/{table}', [AdminController::class, 'rows']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::patch('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        Route::post('/sql-lab', [AdminController::class, 'sqlLab']);
    });
});
