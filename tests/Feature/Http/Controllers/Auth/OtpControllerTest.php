<?php declare(strict_types = 1);

use App\Jobs\Auth\SendOtpEmailJob;
use App\Models\Auth\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use OTPHP\TOTP;

use function Pest\Laravel\{actingAs, postJson};

it('sends OTP email code', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    postJson(route('auth.otp.request'), [
        'email' => $user->email,
    ])
        ->assertOk()
        ->assertJson(['message' => 'Código enviado por e-mail.']);

    Queue::assertPushed(SendOtpEmailJob::class);
});

it('verifies valid email OTP code', function (): void {
    $otp = OtpCode::factory()->create([
        'used'       => false,
        'expires_at' => now()->addMinutes(5),
    ]);

    postJson(route('auth.otp.verify'), [
        'email' => $otp->email,
        'code'  => $otp->code,
    ])
        ->assertOk()
        ->assertJson(['valid' => true]);

    expect($otp->fresh()->used)->toBeTrue();
});

it('fails with invalid code', function (): void {
    postJson(route('auth.otp.verify'), [
        'email' => 'invalid@example.com',
        'code'  => '123456',
    ])
        ->assertStatus(422)
        ->assertJson(['valid' => false]);
});

it('generates TOTP secret and QR', function (): void {
    $user = User::factory()->create();

    actingAs($user);

    postJson(route('auth.otp.totp.setup'))
        ->assertOk()
        ->assertJsonStructure(['secret', 'qr']);
});

it('verifies valid TOTP code', function (): void {
    $totp = TOTP::create();
    $user = User::factory()->create([
        'totp_secret' => $totp->getSecret(),
    ]);

    actingAs($user);

    postJson(route('auth.otp.totp.verify'), [
        'code' => $totp->now(),
    ])
        ->assertOk()
        ->assertJson(['valid' => true]);
});

it('fails with invalid TOTP code', function (): void {
    $user = User::factory()->create([
        'totp_secret' => TOTP::create()->getSecret(),
    ]);

    actingAs($user);

    postJson(route('auth.otp.totp.verify'), [
        'code' => '000000',
    ])
        ->assertStatus(422)
        ->assertJson(['valid' => false]);
});

it('determines if code is expired', function (): void {
    $otp = OtpCode::factory()->make(['expires_at' => now()->subMinute()]);
    expect($otp->isExpired())->toBeTrue();

    $otp = OtpCode::factory()->make(['expires_at' => now()->addMinute()]);
    expect($otp->isExpired())->toBeFalse();
});
