<?php declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

class VersionController extends ApiController
{
    /**
     * Retorna informações sobre a versão atual da API
     */
    public function index(): JsonResponse
    {
        return $this->respondSuccess([
            'version'  => $this->apiVersion,
            'name'     => 'Versão 1',
            'features' => [
                'User Management',
                'Authentication',
                'Authorization',
                'Impersonation',
                'OTP & TOTP',
                'Magic Links',
                'Tenant Management',
                'Rate Limiting',
            ],
            'release_date' => '2025-05-15',
            'deprecation'  => null,
        ], 'API v1 Info');
    }
}
