<?php declare(strict_types = 1);

use App\Http\Controllers\ACL\{PermissionController, RoleController};
use App\Http\Controllers\Auth\{AuthMeController,
    ConfirmTotpController,
    DisableOtpController,
    ForgotPasswordController,
    LoginController,
    LogoutController,
    MagicLinkController,
    OtpController,
    ResetPasswordController};
use App\Http\Controllers\TenantController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use Infinitypaul\Idempotency\Middleware\EnsureIdempotency;

Route::middleware(['endpoint.ratelimit'])->group(static function (): void {

    Route::group([
        'prefix' => 'auth',
        'as'     => 'auth.',
    ], static function (): void {
        Route::post('/login', LoginController::class)->name('login');
        Route::delete('/logout', LogoutController::class)->name('logout')->middleware('auth:sanctum');
        Route::get('/me', AuthMeController::class)->name('me')->middleware(['auth:sanctum', 'totp.verify']);
        Route::post('/forgot-password', ForgotPasswordController::class)->name('forgot-password');
        Route::post('/reset-password', ResetPasswordController::class)->name('reset-password');

        Route::post('/magic-link', [MagicLinkController::class, 'request'])->name('magic-link.request');
        Route::post('/magic-link/verify', [MagicLinkController::class, 'verify'])->name('magic-link.verify');
        Route::get('/magic-link/verify', [MagicLinkController::class, 'verify'])->name('magic-link.verify.get');

        Route::post('/otp/request', [OtpController::class, 'requestEmailCode'])->name('otp.request');
        Route::post('/otp/verify', [OtpController::class, 'verifyEmailCode'])->name('otp.verify');
        Route::post('/otp/totp/setup', [OtpController::class, 'setupTotp'])->name('otp.totp.setup')->middleware('auth:sanctum');
        Route::post('/otp/totp/verify', [OtpController::class, 'verifyTotp'])->name('otp.totp.verify')->middleware('auth:sanctum');
        Route::post('/otp/totp/confirm', ConfirmTotpController::class)->name('otp.totp.confirm')->middleware('auth:sanctum');
        Route::post('/otp/disable', DisableOtpController::class)->name('otp.disable')->middleware('auth:sanctum');

    });

    Route::middleware(['auth:sanctum', 'totp.verify', 'tenant.ratelimit', EnsureIdempotency::class])->group(static function (): void {
        Route::apiResource('tenant', TenantController::class);

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

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

});
