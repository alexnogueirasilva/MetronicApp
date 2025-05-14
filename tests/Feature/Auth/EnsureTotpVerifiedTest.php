<?php declare(strict_types = 1);

use App\Enums\TypeOtp;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

it('allows access to auth.me when TOTP is verified', function (): void {
    $user = User::factory()->create([
        'otp_method'    => TypeOtp::TOTP,
        'totp_verified' => true,
    ]);

    Sanctum::actingAs($user);

    getJson(route('auth.me'))
        ->assertOk()
        ->assertJsonFragment([
            'id'    => $user->id,
            'email' => $user->email,
        ]);
});

it('blocks access to auth.me when TOTP is not verified', function (): void {
    $user = User::factory()->create([
        'otp_method'    => TypeOtp::TOTP,
        'totp_verified' => false,
    ]);

    Sanctum::actingAs($user);

    getJson(route('auth.me'))
        ->assertStatus(403)
        ->assertJson([
            'message' => 'Two-factor authentication is required.',
        ]);
});
