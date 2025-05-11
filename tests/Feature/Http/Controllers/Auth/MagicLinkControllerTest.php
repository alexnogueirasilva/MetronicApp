<?php declare(strict_types = 1);

use App\Enums\TypeOtp;
use App\Jobs\Auth\SendMagicLinkEmailJob;
use App\Models\Auth\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\{Queue};

use function Pest\Laravel\postJson;

beforeEach(function (): void {
    Queue::fake();
});

it('successfully requests a magic link and dispatches job for existing user', function (): void {
    $user = User::factory()->create();

    postJson(route('auth.magic-link.request'), [
        'email' => $user->email,
    ])
        ->assertOk()
        ->assertJson([
            'message' => 'Se o e-mail estiver cadastrado, você receberá um link de acesso em breve.',
        ]);

    Queue::assertPushed(SendMagicLinkEmailJob::class, function ($job) use ($user): bool {
        return $job->email === $user->email;
    });

    $this->assertDatabaseHas('otp_codes', [
        'email' => $user->email,
        'type'  => TypeOtp::MAGIC_LINK->value,
    ]);
});

it('returns same response for non-existent user but does not create OTP', function (): void {
    $email = 'nonexistent@example.com';

    postJson(route('auth.magic-link.request'), [
        'email' => $email,
    ])
        ->assertOk()
        ->assertJson([
            'message' => 'Se o e-mail estiver cadastrado, você receberá um link de acesso em breve.',
        ]);

    // Não podemos usar assertNothingPushed() pois é criado um link falso

    $this->assertDatabaseMissing('otp_codes', [
        'email' => $email,
    ]);
});

it('successfully verifies magic link and returns auth token', function (): void {
    $user = User::factory()->create();
    $code = 'valid_token_123';

    // Create OTP code
    OtpCode::create([
        'email'      => $user->email,
        'code'       => $code,
        'type'       => TypeOtp::MAGIC_LINK,
        'expires_at' => now()->addMinutes(30),
    ]);

    // Criamos os parâmetros com a assinatura correta
    $expires   = now()->addMinutes(30)->timestamp;
    $signature = hash_hmac('sha256', $user->email . $code . $expires, config('app.key'));

    $params = [
        'token'     => $code,
        'email'     => $user->email,
        'expires'   => $expires,
        'signature' => $signature,
    ];

    // Make request with signature params
    $this->get(route('auth.magic-link.verify', $params))
        ->assertOk()
        ->assertJsonStructure([
            'token',
            'message',
            'user',
        ]);

    $this->assertDatabaseMissing('otp_codes', [
        'email' => $user->email,
        'code'  => $code,
    ]);
});

it('rejects invalid signature', function (): void {
    $this->get(route('auth.magic-link.verify', [
        'token'     => 'any_token',
        'email'     => 'user@example.com',
        'expires'   => now()->addMinutes(30)->timestamp,
        'signature' => 'invalid_signature',
    ]))
        ->assertStatus(401)
        ->assertJson([
            'message' => 'Link inválido ou expirado',
        ]);
});

it('rejects expired or invalid token', function (): void {
    $user = User::factory()->create();

    // Criamos params com signature válida mas token que não existe no banco
    $expires   = now()->addMinutes(30)->timestamp;
    $token     = 'token_not_in_db';
    $signature = hash_hmac('sha256', $user->email . $token . $expires, config('app.key'));

    $params = [
        'token'     => $token,
        'email'     => $user->email,
        'expires'   => $expires,
        'signature' => $signature,
    ];

    $this->get(route('auth.magic-link.verify', $params))
        ->assertStatus(401)
        ->assertJson([
            'message' => 'Link inválido ou expirado',
        ]);
});
