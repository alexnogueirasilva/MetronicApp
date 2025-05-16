<?php declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Controlador base para API v1
 */
class ApiController extends Controller
{
    /**
     * Versão atual da API
     */
    protected string $apiVersion = 'v1';

    /**
     * Responder com sucesso
     *
     * @param mixed|null $data
     */
    protected function respondSuccess(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
            'meta'    => [
                'api_version' => $this->apiVersion,
            ],
        ], $status);
    }

    /**
     * Responder com erro
     *
     * @param mixed|null $errors
     */
    protected function respondError(string $message = 'Error', mixed $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
            'meta'    => [
                'api_version' => $this->apiVersion,
            ],
        ], $status);
    }
}
