# Sistema de Impersonation (Personificação)

Este documento descreve em detalhes o sistema de impersonation (personificação) implementado no MetronicApp. O sistema permite que administradores assumam temporariamente a identidade de outros usuários para fins de suporte, diagnóstico e teste, mantendo um registro completo de todas as ações realizadas durante a personificação.

## Visão Geral

O sistema de impersonation consiste em:

1. **Autenticação Temporária** - Administradores podem obter um token temporário para acessar a conta de outro usuário
2. **Registro de Auditoria** - Todas as ações realizadas durante a personificação são registradas
3. **Controle de Permissões** - Apenas usuários com permissão específica podem usar esta funcionalidade
4. **Histórico Completo** - Registro histórico de todas as personificações realizadas

## Restrições de Segurança

O sistema implementa várias restrições de segurança:

- Apenas usuários com a permissão `impersonate-users` podem iniciar personificações
- Um usuário não pode personificar a si mesmo
- Um usuário não pode personificar múltiplos usuários simultaneamente
- Todas as ações realizadas durante a personificação são registradas em log de auditoria
- Os tokens de personificação são identificados de forma única com o escopo 'impersonated'

## Tabelas do Banco de Dados

### Tabela `impersonations`

Esta tabela registra todas as sessões de personificação:

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | ULID | Identificador único da personificação |
| impersonator_id | bigint | ID do usuário que está personificando |
| impersonated_id | bigint | ID do usuário que está sendo personificado |
| ended_at | timestamp | Momento em que a personificação foi encerrada (null se ainda ativa) |
| created_at | timestamp | Momento em que a personificação foi iniciada |
| updated_at | timestamp | Momento da última atualização do registro |

### Tabela `audits`

Registra todas as ações realizadas durante a personificação:

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | ULID | Identificador único do registro de auditoria |
| user_type | string | Tipo do usuário (geralmente App\Models\User) |
| user_id | bigint | ID do usuário personificado |
| event | string | Tipo de evento (ex: 'impersonated-action') |
| auditable_type | string | Tipo do objeto auditado |
| auditable_id | string | ID do objeto auditado |
| old_values | json | Valores antigos (se aplicável) |
| new_values | json | Novos valores, incluindo detalhes da ação |
| ip_address | string | Endereço IP de onde a ação foi realizada |
| user_agent | string | User-Agent do navegador |
| created_at | timestamp | Momento em que a ação foi registrada |
| updated_at | timestamp | Momento da última atualização do registro |

## Endpoints da API

### Iniciar Personificação

```
POST /auth/impersonate/{user}
```

Inicia uma sessão de personificação do usuário especificado.

**Permissões Necessárias**: `impersonate-users`

**Parâmetros**:
- `user` (path): ID do usuário a ser personificado

**Resposta (200 OK)**:
```json
{
    "message": "Você está agora impersonando [nome do usuário].",
    "token": "1|abcdef123456...",
    "user": {
        "id": 123,
        "name": "Nome do Usuário",
        "email": "usuario@exemplo.com",
        ...
    },
    "impersonation_id": "01H8G..."
}
```

### Encerrar Personificação

```
POST /auth/impersonate/stop
```

Encerra a sessão de personificação atual.

**Permissões Necessárias**: Usuário deve estar ativamente personificando alguém

**Resposta (200 OK)**:
```json
{
    "message": "Sessão de personificação encerrada com sucesso."
}
```

### Histórico de Personificações

```
GET /auth/impersonate/history
```

Retorna o histórico de personificações iniciadas pelo usuário autenticado.

**Permissões Necessárias**: `impersonate-users`

**Resposta (200 OK)**:
```json
{
    "impersonations": [
        {
            "id": "01H8G...",
            "impersonated_id": 123,
            "created_at": "2025-05-15T15:30:00Z",
            "ended_at": "2025-05-15T16:45:00Z",
            "impersonated": {
                "id": 123,
                "name": "Nome do Usuário",
                "email": "usuario@exemplo.com"
            }
        },
        ...
    ]
}
```

## Implementação Técnica

### Middleware de Personificação

O middleware `ImpersonationMiddleware` é responsável por:

1. Detectar se uma requisição está sendo feita com um token de personificação
2. Verificar se a personificação ainda está ativa
3. Registrar a ação no log de auditoria
4. Adicionar informações de personificação ao request para uso posterior

Para cada requisição feita com um token de personificação, o middleware adiciona os seguintes atributos ao request:

- `is_impersonated`: true
- `impersonator_id`: ID do usuário que está personificando
- `impersonation_id`: ID da sessão de personificação

### Modelo User

O modelo `User` foi estendido com os seguintes métodos:

- `impersonations()`: Retorna as personificações iniciadas pelo usuário
- `beingImpersonated()`: Retorna as personificações onde o usuário está sendo personificado
- `isImpersonating()`: Verifica se o usuário está atualmente personificando alguém
- `activeImpersonation()`: Retorna a personificação ativa do usuário
- `isBeingImpersonated()`: Verifica se o usuário está sendo personificado
- `activeBeingImpersonated()`: Retorna a personificação ativa onde o usuário está sendo personificado

### Modelo Impersonation

O modelo `Impersonation` representa uma sessão de personificação e inclui:

- Relacionamentos com os usuários impersonator e impersonated
- Método `isActive()` para verificar se a personificação está ativa
- Método `end()` para encerrar a personificação
- Scope `active()` para consultar apenas personificações ativas

## Auditoria de Ações

Todas as ações realizadas durante uma personificação são registradas no log de auditoria com o evento 'impersonated-action'. Cada registro inclui:

- ID do usuário personificado
- ID do usuário que está personificando
- ID da sessão de personificação
- Método HTTP (GET, POST, etc.)
- URL completa acessada
- Endereço IP
- User-Agent do navegador

## Testes

O sistema é coberto por testes automatizados que verificam:

- Iniciar personificação como administrador
- Tentar personificar a si mesmo (deve falhar)
- Tentar personificar como usuário sem permissão (deve falhar)
- Encerrar personificação
- Visualizar histórico de personificações
- Verificar que ações são registradas durante personificação

Para executar os testes:

```bash
php artisan test --filter=ImpersonationTest
```

## Exemplo de Uso

### Frontend (JavaScript)

```javascript
// Iniciar personificação
async function startImpersonation(userId) {
  const response = await api.post(`/auth/impersonate/${userId}`);
  
  if (response.status === 200) {
    // Armazenar token de personificação
    localStorage.setItem('impersonation_token', response.data.token);
    
    // Atualizar headers para usar o novo token
    api.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
    
    // Atualizar UI para mostrar que está personificando
    showImpersonationBanner(response.data.user.name);
  }
}

// Encerrar personificação
async function stopImpersonation() {
  const response = await api.post('/auth/impersonate/stop');
  
  if (response.status === 200) {
    // Remover token de personificação
    localStorage.removeItem('impersonation_token');
    
    // Restaurar token original
    const originalToken = localStorage.getItem('auth_token');
    api.defaults.headers.common['Authorization'] = `Bearer ${originalToken}`;
    
    // Atualizar UI para remover banner de personificação
    hideImpersonationBanner();
  }
}
```

### Backend (PHP)

```php
// Em um serviço ou controller
public function doSomethingAsUser(int $userId)
{
    // Obter usuário a ser personificado
    $user = User::findOrFail($userId);
    
    // Iniciar personificação
    $impersonation = new Impersonation([
        'impersonator_id' => auth()->id(),
        'impersonated_id' => $user->id
    ]);
    $impersonation->save();
    
    try {
        // Fazer algo como o usuário
        // ...
        
        // Encerrar personificação
        $impersonation->end();
        
        return response()->json(['message' => 'Operação realizada com sucesso']);
    } catch (\Exception $e) {
        // Garantir que a personificação seja encerrada mesmo em caso de erro
        $impersonation->end();
        throw $e;
    }
}
```

## Boas Práticas

1. **Visibilidade para o Usuário** - Sempre exiba claramente no frontend quando um administrador está personificando um usuário
2. **Limitação de Escopo** - Considere restringir certas ações sensíveis durante a personificação
3. **Auditoria Regular** - Revise regularmente os logs de personificação para garantir uso adequado
4. **Treinamento** - Treine os administradores sobre o uso ético e adequado da funcionalidade de personificação
5. **Notificação** - Considere notificar o usuário por e-mail quando sua conta é personificada

## Conclusão

A funcionalidade de personificação é uma ferramenta poderosa para suporte ao usuário e diagnóstico de problemas, mas deve ser usada com responsabilidade. O sistema implementa salvaguardas adequadas para garantir que a funcionalidade seja usada de maneira segura e auditável.