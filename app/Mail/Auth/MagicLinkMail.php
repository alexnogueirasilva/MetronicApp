<?php declare(strict_types = 1);

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $magicLink,
    ) {
        // Não há necessidade de modificar o magicLink agora, pois não estamos usando URLs assinadas
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Link de acesso à sua conta',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.magic-link',
            with: [
                'magicLink'  => $this->magicLink,
                'expiration' => '30 minutos',
            ],
        );
    }
}
