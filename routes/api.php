<?php declare(strict_types = 1);

use App\Http\Controllers\Auth\{AuthMeController, LoginController, LogoutController};
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'auth',
    'as'     => 'auth.',
], static function (): void {
    Route::post('/login', LoginController::class)->name('login');
    Route::delete('/logout', LogoutController::class)->name('logout')->middleware('auth:sanctum');
    Route::get('/me', AuthMeController::class)->name('me')->middleware('auth:sanctum');
});
