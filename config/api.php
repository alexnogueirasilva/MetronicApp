<?php declare(strict_types = 1);

return [
    /*
    |--------------------------------------------------------------------------
    | Versão Padrão da API
    |--------------------------------------------------------------------------
    |
    | Define a versão padrão que será usada quando nenhuma versão
    | é especificada na requisição.
    |
    */
    'default_version' => 'v1',

    /*
    |--------------------------------------------------------------------------
    | Versões Disponíveis da API
    |--------------------------------------------------------------------------
    |
    | Lista todas as versões ativas da API. Versões removidas desta lista
    | não serão mais acessíveis, exceto se estiverem em depreciação.
    |
    */
    'versions' => [
        'v1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Versões Depreciadas da API
    |--------------------------------------------------------------------------
    |
    | Versões listadas aqui estão marcadas como depreciadas, mas ainda
    | funcionam. Elas emitirão um header de depreciação, permitindo
    | que clientes saibam que precisam atualizar.
    |
    | Formato: 'versão' => ['data_fim' => 'YYYY-MM-DD', 'mensagem' => '...']
    |
    */
    'deprecated_versions' => [
        // 'v1' => [
        //     'end_date' => '2025-12-31',
        //     'message' => 'Esta versão será descontinuada em 31/12/2025. Por favor, migre para v2.'
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Domínio da API
    |--------------------------------------------------------------------------
    |
    | Este é o domínio onde a API será hospedada.
    | O sistema está configurado para usar um subdomínio específico para a API.
    |
    */
    'domain' => env('API_DOMAIN', 'api.devaction.com.br'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting por Versão
    |--------------------------------------------------------------------------
    |
    | Configurações de rate limiting específicas por versão da API,
    | sobrescrevendo as configurações globais quando necessário.
    |
    */
    'rate_limits' => [
        'v1' => [
            'max_attempts'  => null, // Usa configuração global
            'decay_minutes' => null, // Usa configuração global
        ],
    ],
];
