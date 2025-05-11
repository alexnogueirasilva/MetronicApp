<?php declare(strict_types = 1);

use App\Actions\Auth\VerifyMagicLinkAction;
use App\Enums\TypeOtp;
use App\Models\Auth\OtpCode;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

beforeEach(function (): void {
    $this->action = new VerifyMagicLinkAction();
});

it('verifies a valid signature', function (): void {
    $email   = 'user@example.com';
    $token   = 'valid_token';
    $expires = now()->addMinutes(30)->timestamp;

    // Generate signature with same method as the action
    $signature = hash_hmac('sha256', $email . $token . $expires, config('app.key'));

    $result = $this->action->verifySignature($email, $token, $expires, $signature);

    expect($result)->toBeTrue();
});

it('rejects expired signatures', function (): void {
    $email   = 'user@example.com';
    $token   = 'valid_token';
    $expires = now()->subMinutes(5)->timestamp; // Expired 5 minutes ago

    // Generate signature
    $signature = hash_hmac('sha256', $email . $token . $expires, config('app.key'));

    $result = $this->action->verifySignature($email, $token, $expires, $signature);

    expect($result)->toBeFalse();
});

it('rejects invalid signatures', function (): void {
    $email     = 'user@example.com';
    $token     = 'valid_token';
    $expires   = now()->addMinutes(30)->timestamp;
    $signature = 'invalid_signature';

    $result = $this->action->verifySignature($email, $token, $expires, $signature);

    expect($result)->toBeFalse();
});

it('verifies valid OTP token and authenticates user', function (): void {
    // Arrange
    $user  = User::factory()->create();
    $token = 'valid_token';

    // Create valid OTP code
    OtpCode::create([
        'email'      => $user->email,
        'code'       => $token,
        'type'       => TypeOtp::MAGIC_LINK,
        'expires_at' => now()->addMinutes(30),
    ]);

    // Set up the Auth facade mock for this test only
    Auth::partialMock();
    Auth::shouldReceive('login')->once()->with(\Mockery::type(User::class));

    // Act
    $result = $this->action->verifyTokenAndAuthenticate($user->email, $token);

    // Assert
    expect($result)->toBeInstanceOf(User::class)
        ->and($result->id)->toBe($user->id);

    // Verify OTP code was deleted
    $this->assertDatabaseMissing('otp_codes', [
        'email' => $user->email,
        'code'  => $token,
    ]);
});

it('throws exception for invalid OTP token', function (): void {
    $user  = User::factory()->create();
    $token = 'invalid_token';

    $this->expectException(AuthenticationException::class);
    $this->expectExceptionMessage('Link inválido ou expirado');

    $this->action->verifyTokenAndAuthenticate($user->email, $token);
});

it('throws exception for nonexistent user', function (): void {
    $email = 'nonexistent@example.com';
    $token = 'valid_token';

    // Create valid OTP code even though user doesn't exist
    OtpCode::create([
        'email'      => $email,
        'code'       => $token,
        'type'       => TypeOtp::MAGIC_LINK,
        'expires_at' => now()->addMinutes(30),
    ]);

    $this->expectException(AuthenticationException::class);
    $this->expectExceptionMessage('Usuário não encontrado');

    $this->action->verifyTokenAndAuthenticate($email, $token);
});

it('throws exception for expired token', function (): void {
    $user  = User::factory()->create();
    $token = 'expired_token';

    // Create expired OTP code
    OtpCode::create([
        'email'      => $user->email,
        'code'       => $token,
        'type'       => TypeOtp::MAGIC_LINK,
        'expires_at' => now()->subMinutes(5),
    ]);

    $this->expectException(AuthenticationException::class);
    $this->expectExceptionMessage('Link inválido ou expirado');

    $this->action->verifyTokenAndAuthenticate($user->email, $token);
});
