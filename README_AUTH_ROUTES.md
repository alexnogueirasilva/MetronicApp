# Autenticação API - Documentação para Frontend

Este documento fornece uma visão geral das rotas de autenticação disponíveis para uso pelo frontend. Todas as rotas
utilizam autenticação via Laravel Sanctum e esperam headers de requisição em formato JSON.

## Importante: Versionamento da API

A API utiliza um sistema de versionamento que permite múltiplas versões simultâneas. Para evitar loops de
redirecionamento ao acessar via navegador, sempre especifique explicitamente a versão de uma destas formas:

1. **URL Path**: Acesse sempre uma rota específica como `/v1/status` ou `/v1/version` (em produção)
    - Em ambientes de desenvolvimento pode ser `/api/v1/status` dependendo da configuração
2. **Header**: Adicione o header `X-API-Version: v1` em suas requisições
3. **Accept Header**: Utilize `Accept: application/vnd.api.v1+json`
4. **Query Parameter**: Adicione `?version=v1` à URL

O prefixo da URL pode variar dependendo do ambiente:

- Em produção com subdomínio: `https://api.exemplo.com.br/v1/status` (sem prefixo 'api/')
- Em desenvolvimento local: `http://exemplo.test/api/status`

A configuração é controlada pela variável de ambiente `API_PREFIX`. Para mais detalhes sobre o versionamento, consulte o
arquivo [API_VERSIONING.md](API_VERSIONING.md).

## Configuração Sanctum

O Sanctum está configurado para funcionar com domínios stateful, o que permite autenticação via cookies:

```php
'stateful' => [
    'localhost',
    'localhost:3000',
    '127.0.0.1',
    '127.0.0.1:8000',
    '::1'
    // Inclui também a URL da aplicação atual
]
```

- Guard: 'web'
- Tempo de expiração: null (sem expiração)

## Rota de Cookie do Sanctum

Esta rota é essencial para aplicações SPA que utilizam autenticação via cookies:

```
GET /sanctum/csrf-cookie
```

**Propósito**: Gera um token CSRF e estabelece cookies necessários para autenticação.

**Uso**: Esta rota deve ser chamada antes de enviar requisições de login quando estiver usando autenticação baseada em
cookies.

## Login Padrão

### Rota de Login

```
POST /api/v1/auth/login
```

**Parâmetros**:

- `email`: string, obrigatório, formato de email válido
- `password`: string, obrigatório, mínimo 8 caracteres, máximo 255 caracteres
- `device`: string, opcional, identificador do dispositivo para token

**Headers**:

- `Content-Type`: application/json
- `Accept`: application/json
- `X-Idempotence-Key`: UUID v4 (obrigatório para garantir idempotência)

**Resposta de Sucesso (200 OK)**:

```json
{
    "token": "string",
    "user": {
        "id": "uuid",
        "name": "string",
        "email": "string",
        "email_verified_at": "datetime",
        "created_at": "datetime",
        "updated_at": "datetime",
        "roles": [
            {
                "id": "integer",
                "name": "string",
                "permissions": [
                    {
                        "id": "integer",
                        "name": "string"
                    }
                ]
            }
        ]
    }
}
```

**Resposta de Erro (401 Unauthorized)**:

```json
{
    "message": "Credentials do not match"
}
```

**Resposta de Erro (422 Unprocessable Entity)**:

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": [
            "The email field is required."
        ],
        "password": [
            "The password field is required."
        ]
    }
}
```

## Login com Magic Link

### Solicitar Magic Link

```
POST /api/v1/auth/magic-link
```

**Parâmetros**:

- `email`: string, obrigatório, formato de email válido

**Headers**:

- `Content-Type`: application/json
- `Accept`: application/json
- `X-Idempotence-Key`: UUID v4 (obrigatório)

**Resposta de Sucesso (200 OK)**:

```json
{
    "message": "Magic link sent to your email"
}
```

### Verificar Magic Link

```
POST /api/v1/auth/magic-link/verify
```

**Parâmetros**:

- `email`: string, obrigatório, formato de email válido
- `token`: string, obrigatório

**Headers**:

- `Content-Type`: application/json
- `Accept`: application/json
- `X-Idempotence-Key`: UUID v4 (obrigatório)

**Resposta de Sucesso (200 OK)**:

```json
{
    "token": "string",
    "user": {
        "id": "uuid",
        "name": "string",
        "email": "string",
        "email_verified_at": "datetime",
        "created_at": "datetime",
        "updated_at": "datetime",
        "roles": [
            {
                "id": "integer",
                "name": "string",
                "permissions": [
                    {
                        "id": "integer",
                        "name": "string"
                    }
                ]
            }
        ]
    }
}
```

**Resposta de Erro (401 Unauthorized)**:

```json
{
    "message": "Invalid or expired magic link"
}
```

## Autenticação via OTP (One-Time Password)

### Solicitar Código OTP

```
POST /api/v1/auth/otp/request
```

**Parâmetros**:

- `email`: string, obrigatório, formato de email válido

**Headers**:

- `Content-Type`: application/json
- `Accept`: application/json
- `X-Idempotence-Key`: UUID v4 (obrigatório)

**Resposta de Sucesso (200 OK)**:

```json
{
    "message": "OTP code sent to your email"
}
```

### Verificar Código OTP

```
POST /api/v1/auth/otp/verify
```

**Parâmetros**:

- `email`: string, obrigatório, formato de email válido
- `code`: string, obrigatório

**Headers**:

- `Content-Type`: application/json
- `Accept`: application/json
- `X-Idempotence-Key`: UUID v4 (obrigatório)

**Resposta de Sucesso (200 OK)**:

```json
{
    "token": "string",
    "user": {
        "id": "uuid",
        "name": "string",
        "email": "string",
        "email_verified_at": "datetime",
        "created_at": "datetime",
        "updated_at": "datetime",
        "roles": [
            {
                "id": "integer",
                "name": "string",
                "permissions": [
                    {
                        "id": "integer",
                        "name": "string"
                    }
                ]
            }
        ]
    }
}
```

**Resposta de Erro (422 Unprocessable Entity)**:

```json
{
    "message": "Invalid OTP code"
}
```

## Logout

```
DELETE /api/v1/auth/logout
```

**Headers**:

- `Authorization`: Bearer {token}
- `Accept`: application/json
- `X-Idempotence-Key`: UUID v4 (obrigatório)

**Resposta de Sucesso (200 OK)**:

```json
{
    "message": "Successfully logged out"
}
```

## Obter Usuário Autenticado

```
GET /api/v1/auth/me
```

**Headers**:

- `Authorization`: Bearer {token}
- `Accept`: application/json

**Resposta de Sucesso (200 OK)**:

```json
{
    "id": "uuid",
    "name": "string",
    "email": "string",
    "email_verified_at": "datetime",
    "created_at": "datetime",
    "updated_at": "datetime",
    "roles": [
        {
            "id": "integer",
            "name": "string",
            "permissions": [
                {
                    "id": "integer",
                    "name": "string"
                }
            ]
        }
    ]
}
```

## Autenticação Social

### Redirecionar para Provedor Social

```
GET /api/v1/auth/social/{provider}
```

**Parâmetros de URL**:

- `provider`: string, obrigatório, provedor de autenticação (atualmente suporta apenas 'google')

**Headers**:

- `Accept`: application/json

**Resposta de Sucesso (200 OK)**:

```json
{
    "status": "success",
    "message": "URL de redirecionamento para autenticação.",
    "data": {
        "url": "https://accounts.google.com/o/oauth2/auth?client_id=..."
    },
    "meta": {
        "api_version": "v1"
    }
}
```

**Resposta de Erro (400 Bad Request)**:

```json
{
    "status": "error",
    "message": "Provedor de autenticação não suportado."
}
```

### Callback de Autenticação Social

```
GET /api/v1/auth/social/{provider}/callback
```

**Parâmetros de URL**:

- `provider`: string, obrigatório, provedor de autenticação (atualmente suporta apenas 'google')

**Resposta de Sucesso (200 OK)**:

```json
{
    "user": {
        "id": "uuid",
        "name": "string",
        "email": "string",
        "email_verified_at": "datetime",
        "created_at": "datetime",
        "updated_at": "datetime"
    },
    "token": "string"
}
```

**Resposta de Erro (500 Internal Server Error)**:

```json
{
    "message": "Falha na autenticação social. Ocorreu um erro ao processar a requisição."
}
```

## Autenticação TOTP (Time-based One-Time Password)

### Confirmar Código TOTP

```
POST /api/v1/auth/totp/confirm
```

**Parâmetros**:

- `code`: string, obrigatório, código TOTP gerado pelo aplicativo autenticador

**Headers**:

- `Content-Type`: application/json
- `Accept`: application/json
- `Authorization`: Bearer {token}
- `X-Idempotence-Key`: UUID v4 (obrigatório)

**Resposta de Sucesso (200 OK)**:

```json
{
    "message": "TOTP ativado com sucesso."
}
```

### Desativar OTP

```
DELETE /api/v1/auth/otp/disable
```

**Headers**:

- `Accept`: application/json
- `Authorization`: Bearer {token}
- `X-Idempotence-Key`: UUID v4 (obrigatório)

**Resposta de Sucesso (200 OK)**:

```json
{
    "message": "Autenticação OTP desativada com sucesso."
}
```

## Redefinição de Senha

### Solicitar Redefinição de Senha

```
POST /api/v1/auth/forgot-password
```

**Parâmetros**:

- `email`: string, obrigatório, formato de email válido

**Headers**:

- `Content-Type`: application/json
- `Accept`: application/json
- `X-Idempotence-Key`: UUID v4 (obrigatório)

**Resposta de Sucesso (200 OK)**:

```json
{
    "message": "If your email is registered, you will receive a password reset link."
}
```

### Redefinir Senha

```
POST /api/v1/auth/reset-password
```

**Parâmetros**:

- `email`: string, obrigatório, email codificado em Base64 URL-safe
- `token`: string, obrigatório, token de redefinição de senha enviado por email
- `password`: string, obrigatório, mínimo 8 caracteres
- `password_confirmation`: string, obrigatório, deve corresponder ao campo password

**Headers**:

- `Content-Type`: application/json
- `Accept`: application/json
- `X-Idempotence-Key`: UUID v4 (obrigatório)

**Resposta de Sucesso (200 OK)**:

```json
{
    "message": "Your password has been reset!"
}
```

**Resposta de Erro (422 Unprocessable Entity)**:

```json
{
    "message": "E-mail inválido ou corrompido."
}
```

ou

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "token": ["This password reset token is invalid."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

## Impersonação de Usuário

### Iniciar Impersonação

```
POST /api/v1/auth/impersonate/{user}
```

**Parâmetros de URL**:

- `user`: string, obrigatório, ID do usuário a ser impersonado

**Headers**:

- `Accept`: application/json
- `Authorization`: Bearer {token}
- `X-Idempotence-Key`: UUID v4 (obrigatório)

**Resposta de Sucesso (200 OK)**:

```json
{
    "message": "Você está agora impersonando John Doe.",
    "token": "string",
    "user": {
        "id": "uuid",
        "name": "string",
        "email": "string",
        "email_verified_at": "datetime",
        "created_at": "datetime",
        "updated_at": "datetime"
    },
    "impersonation_id": "string"
}
```

**Resposta de Erro (403 Forbidden)**:

```json
{
    "message": "Você não tem permissão para impersonar outros usuários."
}
```

### Encerrar Impersonação

```
DELETE /api/v1/auth/impersonate
```

**Headers**:

- `Accept`: application/json
- `Authorization`: Bearer {token}
- `X-Idempotence-Key`: UUID v4 (obrigatório)

**Resposta de Sucesso (200 OK)**:

```json
{
    "message": "Sessão de impersonation encerrada com sucesso."
}
```

### Histórico de Impersonação

```
GET /api/v1/auth/impersonate/history
```

**Headers**:

- `Accept`: application/json
- `Authorization`: Bearer {token}

**Resposta de Sucesso (200 OK)**:

```json
{
    "impersonations": [
        {
            "id": "string",
            "impersonated_id": "uuid",
            "created_at": "datetime",
            "ended_at": "datetime",
            "impersonated": {
                "id": "uuid",
                "name": "string",
                "email": "string"
            }
        }
    ]
}
```

## Importante: Idempotência

Todas as requisições POST e DELETE devem incluir um header `X-Idempotence-Key` com um UUID v4 único. Isso garante que a
mesma operação não seja processada múltiplas vezes se o frontend enviar requisições duplicadas acidentalmente. O backend
armazenará a primeira resposta e retornará o mesmo resultado para requisições subsequentes com o mesmo chave de
idempotência.

## Segurança CORS

A API está configurada para aceitar requisições de domínios específicos. Certifique-se de que seu frontend esteja em um
dos domínios configurados para CORS ou solicite que sua origem seja adicionada.
