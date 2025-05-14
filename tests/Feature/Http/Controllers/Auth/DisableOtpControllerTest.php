<?php declare(strict_types = 1);

use App\Enums\TypeOtp;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

it('disables OTP successfully for authenticated user', function (): void {
    $user = User::factory()->create([
        'otp_method'    => TypeOtp::TOTP,
        'totp_verified' => true,
        'totp_secret'   => 'SOMESECRET',
    ]);

    Sanctum::actingAs($user);

    postJson(route('auth.otp.disable'))
        ->assertOk()
        ->assertJson(['message' => 'Autenticação OTP desativada com sucesso.']);

    $user->refresh();

    expect($user->otp_method)->toBeNull()
        ->and($user->totp_verified)->toBeFalse()
        ->and($user->totp_secret)->toBeNull();
});

it('rejects OTP disable for unauthenticated user', function (): void {
    postJson(route('auth.otp.disable'))
        ->assertUnauthorized();
});
