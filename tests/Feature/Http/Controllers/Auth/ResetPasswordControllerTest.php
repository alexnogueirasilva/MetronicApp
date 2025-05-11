<?php declare(strict_types = 1);

use App\Jobs\Auth\SendPasswordChangedNotificationJob;
use App\Models\User;
use Illuminate\Support\Facades\{Password, Queue};
use Illuminate\Support\Str;

use function Pest\Laravel\postJson;

beforeEach(function (): void {
    Queue::fake();
});

it('resets password successfully and dispatches job', function (): void {
    $user         = User::factory()->create();
    $token        = Password::broker()->createToken($user);
    $encodedEmail = rtrim(strtr(base64_encode($user->email), '+/', '-_'), '=');

    $response = postJson(route('auth.reset-password'), [
        'email'                 => $encodedEmail,
        'password'              => 'new-secret123',
        'password_confirmation' => 'new-secret123',
        'token'                 => $token,
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => trans(Password::PASSWORD_RESET),
        ]);

    Queue::assertPushed(SendPasswordChangedNotificationJob::class, fn ($job) => $job->user->is($user));
});

it('fails with invalid encoded email', function (): void {
    $response = postJson(route('auth.reset-password'), [
        'email'                 => '@email-inválido',
        'password'              => 'anything123',
        'password_confirmation' => 'anything123',
        'token'                 => Str::random(64),
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'E-mail inválido ou corrompido.',
        ]);

    Queue::assertNothingPushed();
});
