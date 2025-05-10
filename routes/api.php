<?php declare(strict_types = 1);

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'auth',
    'as'     => 'auth.',
], static function (): void {
    Route::post('/login', LoginController::class)->name('login');
});
