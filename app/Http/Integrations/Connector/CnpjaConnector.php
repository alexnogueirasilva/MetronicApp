<?php declare(strict_types = 1);

namespace App\Http\Integrations\Connector;

use JsonException;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\RequestProperties\HasHeaders;

class CnpjaConnector extends Connector
{
    use AcceptsJson;
    use HasHeaders;

    /**
     * The Base URL of the API
     * @throws JsonException
     */
    public function resolveBaseUrl(): string
    {
        return toString(config('services.cnpja.url_base'));
    }

    /**
     * Default headers for every request
     */
    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => config('services.cnpja.token'),
        ];
    }

    /**
     * Default HTTP client options
     */
    protected function defaultConfig(): array
    {
        return [];
    }

}
