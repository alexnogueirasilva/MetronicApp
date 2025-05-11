<?php declare(strict_types = 1);

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Attachment, Content, Envelope};
use Illuminate\Queue\SerializesModels;
use JsonException;

class ForgotPasswordMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $email,
        public string $token,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Forgot Password Mail',
        );
    }

    /**
     * Get the message content definition.
     * @throws JsonException
     */
    public function content(): Content
    {
        $url_front    = toString(config('app.frontend_url'));
        $emailEncoded = rtrim(strtr(base64_encode($this->email), '+/', '-_'), '=');
        $url          = "{$url_front}/auth/reset-password?token={$this->token}&email={$emailEncoded}";

        return new Content(
            view: 'emails.auth.forgot-password',
            with: [
                'token' => $this->token,
                'email' => $this->email,
                'url'   => $url,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
