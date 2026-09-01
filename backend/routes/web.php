<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/register', [AuthController::class, 'register'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
/*
 * Session-auth fallback for local CodeForge.
 *
 * Sanctum normally registers GET /sanctum/csrf-cookie itself. Some local
 * Laravel/Sanctum installations can fail to expose that package route even
 * though stateful authentication is configured. Keeping this endpoint inside
 * routes/web.php means the normal web/session/CSRF middleware still runs and
 * Laravel can issue the XSRF-TOKEN cookie required by the SvelteKit client.
 */
Route::get('/sanctum/csrf-cookie', function () {
    return response()->noContent();
})->name('codeforge.csrf-cookie');
