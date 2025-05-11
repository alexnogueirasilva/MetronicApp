<?php declare(strict_types = 1);

use App\Jobs\Auth\SendForgotPasswordEmailJob;
use App\Mail\Auth\ForgotPasswordMail;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\postJson;

it('dispatches SendForgotPasswordEmailJob if email exists', function (): void {
    Queue::fake();

    $user = User::factory()->create([
        'email' => 'alex@devaction.com.br',
    ]);

    postJson(route('auth.forgot-password'), [
        'email' => $user->email,
    ])
        ->assertOk()
        ->assertJson([
            'message' => 'If your email is registered, you will receive a password reset link.',
        ]);

    Queue::assertPushed(SendForgotPasswordEmailJob::class, fn ($job) => $job->user->is($user));
});

it('sends forgot password email with token', function (): void {
    Mail::fake();
    $user = User::factory()->create();

    $job = new SendForgotPasswordEmailJob($user);
    $job->handle();

    Mail::assertSent(ForgotPasswordMail::class, fn ($mail): bool => $mail->hasTo($user->email) &&
        filled($mail->token) &&
        $mail->email === $user->email);
});
