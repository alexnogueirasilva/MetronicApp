<?php declare(strict_types = 1);

use App\Actions\Auth\GenerateMagicLinkAction;
use App\Enums\TypeOtp;
use App\Models\Auth\OtpCode;
use App\Models\User;

beforeEach(function (): void {
    $this->action = new GenerateMagicLinkAction();
});

it('generates a valid magic link for existing user', function (): void {
    $user = User::factory()->create();

    $link = $this->action->execute($user->email);

    // Verify link structure
    expect($link)->toBeString()
        ->toContain(route('auth.magic-link.verify.get'))
        ->toContain('token=')
        ->toContain('email=' . urlencode($user->email))
        ->toContain('expires=')
        ->toContain('signature=');

    // Verify OTP record was created
    $this->assertDatabaseHas('otp_codes', [
        'email' => $user->email,
        'type'  => TypeOtp::MAGIC_LINK->value,
    ]);
});

it('deletes existing magic link OTPs before creating a new one', function (): void {
    $user = User::factory()->create();

    // Create existing OTP
    OtpCode::create([
        'email'      => $user->email,
        'code'       => 'old_token',
        'type'       => TypeOtp::MAGIC_LINK,
        'expires_at' => now()->addMinutes(30),
    ]);

    // Generate new link
    $this->action->execute($user->email);

    // Verify old token was deleted
    $this->assertDatabaseMissing('otp_codes', [
        'email' => $user->email,
        'code'  => 'old_token',
    ]);

    // Verify new token exists
    $otpCount = OtpCode::where('email', $user->email)
        ->where('type', TypeOtp::MAGIC_LINK)
        ->count();

    expect($otpCount)->toBe(1);
});

it('generates a fake link for non-existent users', function (): void {
    $email = 'nonexistent@example.com';

    $link = $this->action->execute($email);

    // Verify link structure
    expect($link)->toBeString()
        ->toContain(route('auth.magic-link.verify.get'))
        ->toContain('token=')
        ->toContain('email=') // Should contain a fake email, not the requested one
        ->toContain('expires=')
        ->toContain('signature=');

    // Verify no OTP record was created
    $this->assertDatabaseMissing('otp_codes', [
        'email' => $email,
        'type'  => TypeOtp::MAGIC_LINK->value,
    ]);
});

it('uses HMAC-SHA256 for signature generation', function (): void {
    $user = User::factory()->create();

    $link = $this->action->execute($user->email);

    // Extract parameters from URL
    $parsedUrl = parse_url($link);
    parse_str($parsedUrl['query'] ?? '', $params);

    // Verify signature using the same method as the action
    $expectedSignature = hash_hmac(
        'sha256',
        $params['email'] . $params['token'] . $params['expires'],
        config('app.key')
    );

    expect($params['signature'])->toBe($expectedSignature);
});
