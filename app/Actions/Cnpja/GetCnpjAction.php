<?php declare(strict_types = 1);

namespace App\Actions\Cnpja;

use App\DTO\Cnpja\{CnpjDataDTO};
use App\Helpers\CnpjHelper;
use App\Http\Integrations\Cnpja\Requests\ConsultCnpjRequest;
use App\Http\Integrations\Connector\CnpjaConnector;
use JsonException;
use Saloon\Exceptions\Request\{FatalRequestException, RequestException};

class GetCnpjAction
{
    /**
     * @throws FatalRequestException
     * @throws RequestException
     * @throws JsonException
     */
    public function execute(string $cnpj): CnpjDataDTO
    {
        $sanitizedCnpj = CnpjHelper::sanitize($cnpj);

        $request  = new ConsultCnpjRequest($sanitizedCnpj);
        $response = (new CnpjaConnector()->send($request)->json());

        return CnpjDataDTO::fromArray($response);
    }
}
