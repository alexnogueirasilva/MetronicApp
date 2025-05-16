<?php

declare(strict_types = 1);

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obter a versão da API a partir dos diferentes possíveis lugares
        $version = $this->getVersionFromRequest($request);

        // Armazenar a versão no container para uso posterior
        app()->instance('api.version', $version);

        // Adicionar a versão como atributo da requisição
        $request->attributes->set('api_version', $version);

        // Adicionar o header de resposta com a versão
        $response = $next($request);
        $response->headers->set('X-API-Version', $version);

        return $response;
    }

    /**
     * Obter a versão da API a partir da requisição.
     * Prioridade: Header > Accept Header > Query Param > URL Path > Default
     */
    protected function getVersionFromRequest(Request $request): string
    {
        // Verificar se está no header dedicado
        if ($request->hasHeader('X-API-Version')) {
            $headerVersion = $request->header('X-API-Version');
            // Ensure $headerVersion is a string
            $headerVersion = is_string($headerVersion) ? $headerVersion : '';

            return $this->normalizeVersion($headerVersion);
        }

        // Verificar no Accept header (Accept: application/vnd.api.v1+json)
        $accept = $request->header('Accept');

        if ($accept && preg_match('/application\/vnd\.api\.v(\d+)\+json/', $accept, $matches)) {
            return 'v' . $matches[1];
        }

        // Verificar no query param (?version=v1)
        if ($request->has('version')) {
            $queryVersion = $request->query('version');
            // Ensure $queryVersion is a string
            $queryVersion = is_string($queryVersion) ? $queryVersion : '';

            return $this->normalizeVersion($queryVersion);
        }

        // Verificar no path da URL
        $pathParts = explode('/', trim($request->getPathInfo(), '/'));

        if (preg_match('/^v\d+$/', $pathParts[0])) {
            return $pathParts[0];
        }

        // Versão padrão (configurável)
        $defaultVersion = config('api.default_version', 'v1');

        // Ensure $defaultVersion is a string
        return is_string($defaultVersion) ? $defaultVersion : 'v1';
    }

    /**
     * Normaliza o formato da versão (ex: "1" => "v1", "V2" => "v2")
     */
    protected function normalizeVersion(string $version): string
    {
        // Remover 'v' ou 'V' se presente
        $version = ltrim($version, 'vV');

        // Adicionar 'v' minúsculo no início
        return 'v' . $version;
    }
}
