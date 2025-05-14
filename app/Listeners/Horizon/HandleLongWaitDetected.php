<?php declare(strict_types = 1);

namespace App\Listeners\Horizon;

use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Events\LongWaitDetected;

class HandleLongWaitDetected
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        // Este construtor está vazio mas é mantido para consistência
    }

    /**
     * Handle the event.
     */
    public function handle(LongWaitDetected $event): void
    {
        Log::warning('Horizon detectou espera longa na fila', [
            'connection' => $event->connection,
            'queue'      => $event->queue,
            'wait'       => $event->seconds,
        ]);
    }
}
