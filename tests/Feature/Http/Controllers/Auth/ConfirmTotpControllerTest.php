<?php declare(strict_types = 1);

use App\Actions\Auth\TotpManagerAction;
use App\Enums\TypeOtp;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Laravel\Sanctum\Sanctum;
use OTPHP\TOTP;

use function Pest\Laravel\postJson;

it('successfully confirms a valid TOTP code', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $manager = App::make(TotpManagerAction::class);
    $secret  = $manager->generate($user->email);

    $user->update(['totp_secret' => $secret->secret]);

    $totp = TOTP::create($secret->secret);
    $code = $totp->now();

    postJson(route('auth.otp.totp.confirm'), [
        'code' => $code,
    ])
        ->assertOk()
        ->assertJson(['message' => 'TOTP ativado com sucesso.']);

    $user->refresh();

    expect($user->otp_method)->toEqual(TypeOtp::TOTP)
        ->and($user->totp_verified)->toBeTrue();
});

it('fails when the TOTP code is missing', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    postJson(route('auth.otp.totp.confirm'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

it('fails when the TOTP code is invalid', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    postJson(route('auth.otp.totp.confirm'), [
        'code' => '999999',
    ])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Código TOTP inválido.']);
});

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
