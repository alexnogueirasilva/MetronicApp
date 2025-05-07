<?php declare(strict_types = 1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomNotFoundException extends Exception
{
    public function __construct(
        public string $userMessage,
        public string $developerMessage,
        protected ?NotFoundHttpException $originalException = null
    ) {
        parent::__construct($userMessage);
    }

    public static function fromNotFoundHttpException(NotFoundHttpException $e): self
    {
        $originalMessage = $e->getMessage();
        $response        = self::parseOriginalMessage($originalMessage);

        return new self($response['userMessage'], $response['developerMessage'], $e);
    }

    /**
     * Analisa a mensagem original e retorna as mensagens formatadas
     *
     * @return array{userMessage: string, developerMessage: string}
     */
    private static function parseOriginalMessage(string $originalMessage): array
    {
        $defaultResponse = [
            'userMessage'      => 'Recurso não encontrado',
            'developerMessage' => $originalMessage,
        ];

        $matchResult = preg_match('/No query results for model \[([^]]+)] (.+)/', $originalMessage, $matches);

        if ($matchResult !== 1) {
            return $defaultResponse;
        }

        return [
            'userMessage'      => 'Recurso não encontrado',
            'developerMessage' => "Nenhum resultado encontrado para o modelo {$matches[1]} com ID {$matches[2]}",
        ];
    }

    public function render(Request $request): JsonResponse
    {
        $responseData = [
            'message' => $this->userMessage,
        ];

        if ($this->originalException instanceof NotFoundHttpException && app()->environment('production')) {
            $errorDetails = [
                'developer_message' => $this->developerMessage,
                'exception'         => $this->originalException::class,
                'file'              => $this->originalException->getFile(),
                'line'              => $this->originalException->getLine(),
            ];

            Log::warning('Recurso não encontrado', $errorDetails);
        }

        if ($this->originalException instanceof NotFoundHttpException && app()->environment('local')) {
            $responseData = array_merge($responseData, [
                'developer_message' => $this->developerMessage,
                'exception'         => $this->originalException::class,
                'file'              => $this->originalException->getFile(),
                'line'              => $this->originalException->getLine(),
                'trace'             => $this->originalException->getTrace(),
            ]);
        }

        return response()->json(
            $responseData,
            SymfonyResponse::HTTP_NOT_FOUND
        );
    }
}
