<?php declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Controlador base para API v1
 *
 * @OA\Info(
 *     title="MetronicApp API",
 *     version="1.0.0",
 *     description="API documentation for MetronicApp",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Tag(
 *     name="Auth",
 *     description="Endpoints for user authentication"
 * )
 *
 * @OA\Tag(
 *     name="Users",
 *     description="User management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Tenants",
 *     description="Tenant management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="ACL",
 *     description="Access Control List management endpoints"
 * )
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
                'idempotency' => [
                    'enabled'     => config('idempotency.enabled', true),
                    'header_name' => config('idempotency.header_name', 'Idempotency-Key'),
                    'format'      => 'UUID (e.g., 123e4567-e89b-12d3-a456-426614174000)',
                ],
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
                'idempotency' => [
                    'enabled'     => config('idempotency.enabled', true),
                    'header_name' => config('idempotency.header_name', 'Idempotency-Key'),
                    'format'      => 'UUID (e.g., 123e4567-e89b-12d3-a456-426614174000)',
                ],
            ],
        ], $status);
    }
}
