# Versionamento de API

Este documento descreve a implementação do sistema de versionamento da API do MetronicApp, que permite manter a compatibilidade com clientes mais antigos enquanto evolui a API com novos recursos.

## Visão Geral

O sistema de versionamento da API suporta:

1. **Múltiplas Versões Simultâneas** - Múltiplas versões da API podem coexistir, permitindo transição gradual entre versões.
2. **Depreciação Controlada** - Versões antigas podem ser marcadas como depreciadas antes de serem removidas.
3. **Múltiplos Métodos de Especificação** - Suporte para especificar a versão da API de várias maneiras.
4. **Redirecionamento Automático** - Requisições sem versão são automaticamente redirecionadas para a versão apropriada.

## Especificando a Versão

A versão da API pode ser especificada de várias maneiras, em ordem de precedência:

1. **Header Dedicado**
   ```
   X-API-Version: v1
   ```

2. **Accept Header**
   ```
   Accept: application/vnd.api.v1+json
   ```

3. **Query Parameter**
   ```
   /api/users?version=v1
   ```

4. **URL Path**
   ```
   /api/v1/users
   ```

5. **Padrão** - Quando nenhuma versão é especificada, a versão configurada como padrão é usada.

## Estrutura de Arquivos

O sistema de versionamento organiza o código da seguinte forma:

```
/routes
  /api
    /v1.php      # Rotas da API v1
    /v2.php      # Rotas da API v2 (quando adicionada)
  /api.php       # Rotas da API sem versão explícita

/app
  /Http
    /Controllers
      /Api
        /V1      # Controladores específicos da API v1
        /V2      # Controladores específicos da API v2 (quando adicionada)
```

## Uso com Subdomínio Específico

A API está configurada para ser hospedada em um subdomínio dedicado (`api.devaction.com.br`), o que proporciona as seguintes vantagens:

1. **Isolamento Claro**: Separação clara entre a API e a aplicação web principal
2. **Gerenciamento de DNS Simplificado**: Facilita a configuração de DNS e certificados SSL
3. **URLs Mais Limpas**: Não é necessário incluir `/api` no path das URLs

Com esta configuração, as URLs da API ficam no formato:
```
https://api.devaction.com.br/v1/users
```

Ao invés de:
```
https://devaction.com.br/api/v1/users
```

## Configuração

A configuração do versionamento da API está em `config/api.php`:

```php
return [
    // Versão padrão da API
    'default_version' => 'v1',
    
    // Versões disponíveis
    'versions' => [
        'v1',
        // 'v2', (quando adicionada)
    ],
    
    // Versões depreciadas
    'deprecated_versions' => [
        // 'v1' => [
        //     'end_date' => '2025-12-31',
        //     'message' => 'Esta versão será descontinuada em 31/12/2025. Por favor, migre para v2.'
        // ],
    ],
    
    // Domínio da API
    'domain' => env('API_DOMAIN', 'api.devaction.com.br'),
    
    // Configurações de rate limiting por versão
    'rate_limits' => [
        'v1' => [
            'max_attempts' => null,  // Usa configuração global
            'decay_minutes' => null, // Usa configuração global
        ],
    ],
];
```

## Headers de Resposta

Todas as respostas da API incluem o header `X-API-Version` para indicar a versão utilizada:

```
X-API-Version: v1
```

Para versões depreciadas, um header adicional `X-API-Deprecated` é incluído:

```
X-API-Deprecated: Esta versão será descontinuada em 31/12/2025. Por favor, migre para v2.
```

## Implementando Novas Versões

Para adicionar uma nova versão da API:

1. Adicione a nova versão em `config/api.versions`.
2. Crie um novo arquivo de rotas em `routes/api/vX.php`.
3. Crie um novo diretório de controladores em `app/Http/Controllers/Api/VX`.
4. Implemente os novos controladores na versão atualizada.
5. Atualize a documentação da API para cada versão.

### Considerações sobre Nomenclatura de Rotas

Por razões de compatibilidade com os testes existentes e para evitar quebrar clientes da API, os nomes das rotas **não** são prefixados com a versão. Isso significa que:

- A rota `auth.me` permanece com este nome em todas as versões da API
- Ao criar novas versões, evite duplicar nomes de rotas existentes
- Use os controladores específicos da versão para implementar comportamentos diferentes

Se você precisar alterar esse comportamento para adicionar prefixo de versão aos nomes das rotas, adicione `.name("{$version}.")` ao registrar as rotas em `bootstrap/app.php` e atualize todos os testes existentes.

## Depreciando Versões

Para depreciar uma versão da API:

1. Adicione a versão em `config/api.deprecated_versions` com uma data de término e mensagem.
2. Comunique aos usuários sobre a depreciação, fornecendo instruções para a migração.
3. Após a data de término, você pode remover a versão de `config/api.versions` para desativá-la completamente.

## Melhores Práticas

1. **Alterações Compatíveis** - Quando possível, faça alterações compatíveis com versões anteriores.
2. **Rotas Independentes** - Mantenha as rotas de cada versão em arquivos separados.
3. **Controladores Específicos** - Use controladores específicos para cada versão da API.
4. **Documentação Clara** - Documente claramente as mudanças entre versões.
5. **Período de Transição** - Permita um período de transição adequado entre versões.
6. **Avisos Antecipados** - Avise os usuários com antecedência sobre depreciações.

## Exemplo de Uso

### Cliente (Frontend)

```javascript
// Configuração para API v1
const apiClient = axios.create({
  baseURL: 'https://api.example.com/api',
  headers: {
    'X-API-Version': 'v1',
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
});

// Alternativa: usar URL explícita
const apiClientAlt = axios.create({
  baseURL: 'https://api.example.com/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
});
```

### Servidor (Laravel)

```php
// Controlador específico da API v1
namespace App\Http\Controllers\Api\V1;

class UserController extends ApiController
{
    public function index()
    {
        // Implementação específica da v1
    }
}

// Controlador específico da API v2 (quando existir)
namespace App\Http\Controllers\Api\V2;

class UserController extends ApiController
{
    public function index()
    {
        // Implementação atualizada para v2
    }
}
```

## Conclusão

O sistema de versionamento da API permite:

1. Evolução controlada da API ao longo do tempo
2. Retrocompatibilidade para clientes existentes
3. Processo claro para adição e depreciação de versões
4. Flexibilidade para clientes especificarem a versão preferida