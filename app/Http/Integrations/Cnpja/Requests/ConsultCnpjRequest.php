<?php declare(strict_types = 1);

namespace App\Http\Integrations\Cnpja\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class ConsultCnpjRequest extends Request
{
    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    public function __construct(
        protected string $cnpj
    ) {}

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return "/office/{$this->cnpj}?registrations=BR";
    }
}
