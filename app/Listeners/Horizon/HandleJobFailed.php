<?php declare(strict_types = 1);

namespace App\Listeners\Horizon;

use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Events\JobFailed;

class HandleJobFailed
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
    public function handle(JobFailed $event): void
    {
        // Se for a última tentativa do job, registrar erro crítico
        $attempts = 1;

        if (method_exists($event->job, 'attempts')) {
            $attemptValue = $event->job->attempts();
            $attempts     = is_numeric($attemptValue) ? (int) $attemptValue : 1;
        }

        $maxTries = 1;

        if (method_exists($event->job, 'maxTries')) {
            $maxTriesValue = $event->job->maxTries();
            $maxTries      = is_numeric($maxTriesValue) ? (int) $maxTriesValue : 1;
        }

        if ($attempts >= $maxTries) {
            Log::error('Horizon: job falhou todas as tentativas', [
                'job'       => method_exists($event->job, 'resolveName') ? $event->job->resolveName() : 'unknown',
                'exception' => $event->exception->getMessage(),
                'attempts'  => $attempts,
                'maxTries'  => $maxTries,
                'queue'     => method_exists($event->job, 'getQueue') ? $event->job->getQueue() : 'unknown',
                'payload'   => method_exists($event->job, 'payload') ? $event->job->payload() : [],
            ]);
        } else {
            // Se for uma tentativa intermediária, registrar como aviso
            Log::warning(sprintf('Horizon: falha em job (tentativa %d/%d)', $attempts, $maxTries), [
                'job'       => method_exists($event->job, 'resolveName') ? $event->job->resolveName() : 'unknown',
                'exception' => $event->exception->getMessage(),
                'queue'     => method_exists($event->job, 'getQueue') ? $event->job->getQueue() : 'unknown',
            ]);
        }
    }
}
