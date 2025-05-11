<?php declare(strict_types = 1);

use App\Http\Controllers\ACL\{PermissionController, RoleController};
use App\Http\Controllers\Auth\{AuthMeController,
    ForgotPasswordController,
    LoginController,
    LogoutController,
    ResetPasswordController};
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'auth',
    'as'     => 'auth.',
], static function (): void {
    Route::post('/login', LoginController::class)->name('login');
    Route::delete('/logout', LogoutController::class)->name('logout')->middleware('auth:sanctum');
    Route::get('/me', AuthMeController::class)->name('me')->middleware('auth:sanctum');
    Route::post('/forgot-password', ForgotPasswordController::class)->name('forgot-password');
    Route::post('/reset-password', ResetPasswordController::class)->name('reset-password');
});

Route::middleware('auth:sanctum')->group(static function (): void {
    Route::group([
        'prefix' => 'acl',
        'as'     => 'acl.',
    ], static function (): void {
        Route::get('/role', [RoleController::class, 'index'])->name('role');
        Route::get('/role/{id}', [RoleController::class, 'show'])->name('role.show');
        Route::post('/role', [RoleController::class, 'store'])->name('role.store');
        Route::put('/role/{id}', [RoleController::class, 'update'])->name('role.update');
        Route::delete('/role/{id}', [RoleController::class, 'destroy'])->name('role.destroy');

        Route::get('/permission', [PermissionController::class, 'index'])->name('permission');
        Route::get('/permission/{id}', [PermissionController::class, 'show'])->name('permission.show');
        Route::post('/permission', [PermissionController::class, 'store'])->name('permission.store');
        Route::put('/permission/{id}', [PermissionController::class, 'update'])->name('permission.update');
        Route::delete('/permission/{id}', [PermissionController::class, 'destroy'])->name('permission.destroy');
    });
});
