<?php declare(strict_types = 1);

namespace App\Jobs\Auth;

use App\Enums\QueueEnum;
use App\Mail\Auth\MagicLinkMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\Mail;

class SendMagicLinkEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $email,
        private readonly string $magicLink,
    ) {}

    public function handle(): void
    {
        Mail::to($this->email)->send(new MagicLinkMail(
            $this->magicLink,
        ));
    }

    /**
     * Define as tags para o Horizon monitorar este job
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'magic-link',
            'email:' . $this->email,
            QueueEnum::AUTH_DEFAULT->value,
        ];
    }
}
