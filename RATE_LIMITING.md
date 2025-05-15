# Sistema de Limitação de Taxa (Rate Limiting)

Este documento descreve em detalhes o sistema de limitação de taxa (rate limiting) implementado no MetronicApp. O sistema fornece proteção contra abusos e garante uma alocação justa de recursos com base nos planos de assinatura dos tenants.

## Visão Geral

A aplicação implementa duas estratégias complementares de limitação de taxa:

1. **Limitação Baseada em Tenant** - Limita requisições com base no plano de assinatura do tenant
2. **Limitação Baseada em Endpoint** - Aplica diferentes limites para endpoints específicos da API com base na intensidade de recursos

Ambas as estratégias trabalham juntas para fornecer proteção abrangente enquanto permitem flexibilidade nos padrões de uso da API.

## Limitação Baseada em Tenant

A limitação baseada em tenant é implementada através do middleware `TenantRateLimiter` e restringe o número total de requisições que um tenant pode fazer por minuto com base em seu plano de assinatura.

### Funcionalidades

- **Limites Baseados em Plano** - Diferentes limites de taxa com base no nível do plano de assinatura (Free, Basic, Professional, Enterprise, Unlimited)
- **Limites Personalizados** - Substituição de limites baseados em plano com configurações personalizadas por tenant
- **Suporte para Plano Ilimitado** - Tenants com plano Unlimited ignoram completamente a limitação de taxa

### Limites de Taxa por Plano

| Plano         | Requisições/Minuto | Requisições/Segundo | Máximo Concorrente |
|---------------|-------------------|---------------------|-------------------|
| FREE          | 30                | 0,5                 | 5                 |
| BASIC         | 60                | 1                   | 10                |
| PROFESSIONAL  | 300               | 5                   | 25                |
| ENTERPRISE    | 1.200             | 20                  | 50                |
| UNLIMITED     | Sem limite        | Sem limite          | 100               |

### Como Utilizar

Aplique o middleware às rotas que devem estar sujeitas à limitação de taxa baseada em tenant:

```php
// Aplicar a uma rota específica
Route::post('/recurso', ResourceController::class)
    ->middleware('tenant.ratelimit');

// Aplicar a um grupo de rotas
Route::middleware(['auth:sanctum', 'tenant.ratelimit'])
    ->group(function () {
        // Rotas protegidas aqui
    });
```

### Personalizando Limites de Taxa

Você pode personalizar os limites de taxa por tenant atualizando a coluna `settings` na tabela `tenants`:

```php
// Definir um limite de taxa personalizado para um tenant
$tenant->settings = [
    'custom_rate_limit' => 100, // 100 requisições por minuto
    'max_concurrent_requests' => 15
];
$tenant->save();
```

## Limitação Baseada em Endpoint

A limitação baseada em endpoint é implementada através do middleware `EndpointRateLimiter` e aplica diferentes limites de taxa a endpoints específicos da API com base na intensidade de recursos ou sensibilidade.

### Funcionalidades

- **Limites Específicos por Endpoint** - Diferentes limites para diferentes endpoints
- **Correspondência de Padrões** - Suporte para padrões curinga (wildcard) para corresponder a grupos de endpoints
- **Sistema de Multiplicadores** - Os limites de taxa são calculados como uma porcentagem do limite de taxa base do usuário

### Configurações Padrão de Endpoint

| Padrão de Endpoint         | Multiplicador | Decaimento (minutos) | Descrição                        |
|----------------------------|--------------|---------------------|---------------------------------|
| `api/auth/login`           | 0,2          | 5                   | Limites mais rigorosos para login |
| `api/auth/forgot-password` | 0,1          | 15                  | Muito rigoroso para redefinição de senha |
| `api/auth/reset-password`  | 0,1          | 15                  | Muito rigoroso para redefinição de senha |
| `api/auth/otp/*`           | 0,2          | 5                   | Mais rigoroso para endpoints relacionados a OTP |
| `api/reports/*`            | 0,5          | 1                   | Reduzido para endpoints com uso intensivo de dados |
| `api/exports/*`            | 0,3          | 5                   | Reduzido para operações de exportação |
| `*`                        | 1,0          | 1                   | Padrão - limite de taxa completo |

### Como Utilizar

Aplique o middleware às rotas que devem estar sujeitas à limitação de taxa baseada em endpoint:

```php
// Aplicar a todas as rotas da API
Route::middleware(['endpoint.ratelimit'])->group(function () {
    // Rotas da API aqui
});
```

### Personalizando Limites de Endpoint

Para personalizar os limites de taxa de endpoint, modifique a propriedade `$endpointLimits` na classe `EndpointRateLimiter`:

```php
protected $endpointLimits = [
    // Formato: 'padrão_endpoint' => [multiplicador_limite, minutos_decaimento]
    'api/endpoint/personalizado' => [0.4, 10],
    // Adicione mais padrões de endpoint personalizados conforme necessário
];
```

## Cabeçalhos de Resposta

Ambos os middlewares de limitação de taxa adicionam os seguintes cabeçalhos às respostas:

- **X-RateLimit-Limit** - Número máximo de requisições permitidas por minuto
- **X-RateLimit-Remaining** - Número de requisições restantes na janela atual
- **Retry-After** - Número de segundos para aguardar até que o limite de taxa seja redefinido (apenas quando o limite é excedido)
- **X-RateLimit-Reset** - Timestamp de quando o limite de taxa será redefinido (apenas quando o limite é excedido)

## Resposta de Limite Excedido

Quando os limites de taxa são excedidos, a API retorna:

- **Status HTTP**: 429 Too Many Requests
- **Resposta JSON**:
  ```json
  {
      "message": "Too Many Requests",
      "retry_after": 30 // Segundos até que o limite de taxa seja redefinido
  }
  ```

## Detalhes de Implementação

### Modelo Tenant

O modelo `Tenant` fornece métodos para determinar os limites de taxa:

- `getRateLimitPerMinute()` - Retorna o limite de taxa do tenant, personalizado ou baseado no plano
- `getMaxConcurrentRequests()` - Retorna o número máximo de requisições concorrentes permitidas
- `getRateLimitCacheKey()` - Retorna uma chave de cache única para limitar a taxa deste tenant

```php
// Implementação do método getRateLimitPerMinute()
public function getRateLimitPerMinute(): int
{
    // Verificar se há um limite personalizado nas configurações
    if (isset($this->settings['custom_rate_limit'])) {
        /** @var int|string|mixed $value */
        $value = $this->settings['custom_rate_limit'];
        return toInteger($value);
    }
    
    // Caso contrário, usar o limite baseado no plano
    return $this->plan->requestsPerMinute();
}
```

### Modelo User

O modelo `User` também fornece métodos de limitação de taxa:

- `getRateLimitPerMinute()` - Retorna o limite de taxa do usuário (delegado ao tenant, se disponível)
- `getRateLimitCacheKey()` - Retorna uma chave de cache única para limitar a taxa deste usuário

```php
// Implementação do método getRateLimitPerMinute() no modelo User
public function getRateLimitPerMinute(): int
{
    // Delegar ao limite do tenant ou usar o padrão
    return $this->tenant?->getRateLimitPerMinute() ?? 30;
}

// Implementação do método getRateLimitCacheKey()
public function getRateLimitCacheKey(): string
{
    return "user:{$this->id}:ratelimit";
}
```

### Enum PlanType

O enum `PlanType` define os limites para cada nível de assinatura:

- `requestsPerMinute()` - Retorna o máximo de requisições por minuto para o plano
- `maxConcurrentRequests()` - Retorna o máximo de requisições concorrentes para o plano

```php
// Implementação do método requestsPerMinute() no enum PlanType
public function requestsPerMinute(): int
{
    return match($this) {
        self::FREE         => 30,         // 30 req/min (0,5 req/s)
        self::BASIC        => 60,         // 60 req/min (1 req/s)
        self::PROFESSIONAL => 300,        // 300 req/min (5 req/s)
        self::ENTERPRISE   => 1200,       // 1200 req/min (20 req/s)
        self::UNLIMITED    => 0,          // Sem limite (0 significa ilimitado)
    };
}
```

## Ciclo de Vida da Solicitação

Vamos acompanhar o ciclo de vida completo de uma solicitação através do sistema de rate limiting:

1. **Solicitação Recebida** - Uma requisição HTTP chega ao aplicativo Laravel
2. **Middleware EndpointRateLimiter Acionado** - Aplica limites específicos do endpoint
   - Determina qual padrão de endpoint corresponde ao caminho da requisição
   - Calcula o limite ajustado com base no multiplicador do endpoint
   - Incrementa o contador de requisições para este endpoint/usuário
   - Adiciona cabeçalhos de limite de taxa à resposta
3. **Middleware TenantRateLimiter Acionado** - Aplica limites baseados no tenant
   - Verifica se o tenant está no plano Unlimited (ignora limite se estiver)
   - Obtém o limite de taxa do tenant (configuração personalizada ou baseada no plano)
   - Incrementa o contador de requisições para este tenant/usuário
   - Adiciona cabeçalhos de limite de taxa à resposta
4. **Verificação de Limite Excedido** - Se o limite for excedido:
   - Retorna resposta 429 com informações sobre quando tentar novamente
   - Inclui cabeçalhos Retry-After e X-RateLimit-Reset
5. **Roteamento Normal** - Se os limites não forem excedidos, a requisição continua para os controladores normais

## Boas Práticas

1. **Aplicar Ambos os Middlewares** - Use ambos os limitadores para proteção abrangente
2. **A Ordem Importa** - Aplique a limitação específica do endpoint primeiro, depois a limitação específica do tenant
3. **Configurações Personalizadas** - Use limites de taxa personalizados para tenants especiais ou durante testes/depuração
4. **Monitorar Uso** - Implemente logging para monitorar padrões de uso do limite de taxa

Exemplo de ordem correta de aplicação dos middlewares:

```php
// Em routes/api.php
Route::middleware(['endpoint.ratelimit'])->group(function () {
    // Primeiro aplicamos endpoint.ratelimit para todas as rotas
    
    Route::middleware(['auth:sanctum', 'tenant.ratelimit'])->group(function () {
        // Depois aplicamos tenant.ratelimit para rotas autenticadas
        // Rotas protegidas aqui
    });
});
```

## Testes

O sistema inclui testes abrangentes para ambas as estratégias de limitação de taxa:

- `TenantRateLimiterTest` - Testa a limitação de taxa baseada em tenant
- `EndpointRateLimiterTest` - Testa a limitação de taxa baseada em endpoint

Para executar os testes:

```bash
php artisan test --filter=TenantRateLimiterTest
php artisan test --filter=EndpointRateLimiterTest
```

### Exemplo de Teste:

```php
// Teste do TenantRateLimiter
it('applies rate limiting based on tenant plan', function () {
    // Criar tenants com diferentes planos
    $freeTenant = Tenant::factory()->free()->create();
    $unlimitedTenant = Tenant::factory()->unlimited()->create();

    // Criar usuários para cada tenant
    $freeUser = User::factory()->create(['tenant_id' => $freeTenant->id]);
    $unlimitedUser = User::factory()->create(['tenant_id' => $unlimitedTenant->id]);

    // Testar com plano gratuito (30 req/min)
    $response = actingAs($freeUser)
        ->getJson('/api/test-rate-limit');

    $response->assertOk();
    $response->assertHeader('X-RateLimit-Limit', '30');

    // Testar com plano ilimitado
    $response = actingAs($unlimitedUser)
        ->getJson('/api/test-rate-limit');

    $response->assertOk();
    // Plano ilimitado não deve ter cabeçalhos de limite de taxa
    $response->assertHeaderMissing('X-RateLimit-Limit');
});
```

## Estendendo o Sistema

Para estender o sistema de limitação de taxa:

1. **Fontes de Limite Personalizado** - Implemente lógica personalizada nos métodos `getRateLimitPerMinute()`
2. **Middlewares Adicionais** - Crie middlewares especializados para casos de uso específicos
3. **Limites de Endpoint Dinâmicos** - Armazene limites de endpoint no banco de dados para configuração administrativa

### Exemplo de Extensão:

```php
// Adicionar uma nova fonte de limites de taxa no modelo Tenant
public function getRateLimitPerMinute(): int
{
    // Verificar limites baseados em hora do dia
    $currentHour = (int) now()->format('H');
    
    // Reduzir limites durante horários de pico (9h-18h)
    if ($currentHour >= 9 && $currentHour <= 18) {
        $peakHourReduction = 0.7; // 70% do limite normal
        
        // Aplicar redução ao limite personalizado ou ao limite do plano
        if (isset($this->settings['custom_rate_limit'])) {
            return (int)(toInteger($this->settings['custom_rate_limit']) * $peakHourReduction);
        }
        
        return (int)($this->plan->requestsPerMinute() * $peakHourReduction);
    }
    
    // Fora do horário de pico, usar lógica normal
    if (isset($this->settings['custom_rate_limit'])) {
        return toInteger($this->settings['custom_rate_limit']);
    }
    
    return $this->plan->requestsPerMinute();
}
```

## Solução de Problemas

### Problemas Comuns e Soluções

- **Verificar Cabeçalhos** - Verifique os cabeçalhos de resposta para entender o status atual do limite de taxa
  ```
  X-RateLimit-Limit: 60
  X-RateLimit-Remaining: 58
  ```

- **Chaves de Cache** - Inspecione as chaves de cache se a limitação de taxa não estiver funcionando conforme o esperado
  ```php
  // Para visualizar os contadores de cache do rate limit
  php artisan tinker
  > Cache::get('tenant:1:ratelimit:user:123');
  ```

- **Contagens de Requisições** - Monitore o número real de requisições sendo contadas

- **Limites Muito Restritivos** - Se os usuários estiverem encontrando limites muito restritivos:
  1. Verifique as configurações do plano no enum `PlanType`
  2. Verifique se há configurações personalizadas na coluna `settings` do tenant
  3. Considere ajustar os multiplicadores de endpoint ou adicionar exceções para endpoints específicos

- **Limites Não Aplicados** - Se os limites não estiverem sendo aplicados:
  1. Verifique se os middlewares estão registrados corretamente em `app/Http/Kernel.php`
  2. Verifique se os middlewares estão sendo aplicados nas rotas corretas
  3. Verifique se não há conflitos com outros middlewares
  4. Verifique se o driver de cache está funcionando corretamente

## Configuração Avançada

### Configuração de Cache

O sistema de limitação de taxa usa o sistema de cache do Laravel para rastrear as contagens de requisições. Você pode personalizar o driver de cache usado para limitação de taxa no seu arquivo `config/cache.php`:

```php
'stores' => [
    'rate_limit' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'prefix' => 'rate_limit:'
    ],
    // outras configurações de cache...
],
```

Em seguida, configure o limitador de taxa para usar este armazenamento de cache:

```php
// Em um service provider
$this->app->singleton('Illuminate\Cache\RateLimiter', function ($app) {
    return new RateLimiter($app->make('cache')->store('rate_limit'));
});
```

### Lidando com Aplicações de Alto Tráfego

Para aplicações de alto tráfego, considere estas otimizações:

1. **Use Redis** - Redis é mais eficiente para limitação de taxa do que caches de arquivo ou banco de dados
2. **Implemente Janelas Deslizantes** - Considere implementar um algoritmo de janela deslizante para limitação de taxa mais precisa
3. **Limitação de Taxa Distribuída** - Para configurações de múltiplos servidores, garanta que seu cache seja centralizado
4. **Listas de Banimento/Permissão** - Implemente listas de banimento ou permissão baseadas em IP para abusadores conhecidos ou parceiros confiáveis

### Atribuição de Requisição

Por padrão, os limites de taxa são atribuídos a usuários autenticados ou endereços IP. Você pode personalizar esse comportamento estendendo o middleware do limitador de taxa:

```php
protected function getRequestIdentifier(Request $request)
{
    // Lógica de atribuição personalizada
    if ($apiKey = $request->header('X-API-Key')) {
        return 'api:' . $apiKey;
    }
    
    if ($user = $request->user()) {
        return 'user:' . $user->id;
    }
    
    return 'ip:' . $request->ip();
}
```

## Exemplos de Integração

### Usando com Laravel Sanctum

O sistema de limitação de taxa integra-se perfeitamente com o Laravel Sanctum para autenticação de API:

```php
Route::middleware(['auth:sanctum', 'endpoint.ratelimit', 'tenant.ratelimit'])
    ->group(function () {
        // Rotas de API protegidas
    });
```

### Usando com APIs GraphQL

Para APIs GraphQL, aplique a limitação de taxa ao endpoint GraphQL:

```php
Route::post('/graphql', [GraphQLController::class, 'handle'])
    ->middleware(['auth:sanctum', 'endpoint.ratelimit', 'tenant.ratelimit']);
```

Considere usar a análise de complexidade de operação para limitação de taxa mais granular no GraphQL.

### Usando com APIs RESTful

A configuração padrão é ideal para APIs RESTful tradicionais. Use os middlewares em grupos de rotas para limites consistentes:

```php
// API pública com limites básicos
Route::prefix('api/public')->middleware(['endpoint.ratelimit'])->group(function () {
    // Rotas públicas aqui
});

// API privada com ambos os limitadores
Route::prefix('api/v1')->middleware([
    'auth:sanctum',
    'endpoint.ratelimit',
    'tenant.ratelimit'
])->group(function () {
    // Rotas privadas aqui
});
```

## Conclusão

O sistema de limitação de taxa implementado no MetronicApp fornece proteção abrangente contra abusos de API enquanto permite flexibilidade e escalabilidade. A abordagem em camadas com limitação baseada em tenant e endpoint garante que diferentes tipos de endpoints possam ter limites apropriados, mantendo o uso de API sustentável para todos os usuários.

Ao combinar estas estratégias, o sistema equilibra a necessidade de proteção contra abusos com o desejo de proporcionar uma experiência de API responsiva e confiável para usuários legítimos, especialmente aqueles em planos premium.