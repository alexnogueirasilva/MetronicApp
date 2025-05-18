# Autenticação API - Documentação para Frontend

Este documento fornece uma visão geral das rotas de autenticação disponíveis para uso pelo frontend. Todas as rotas utilizam autenticação via Laravel Sanctum e esperam headers de requisição em formato JSON.

## Importante: Versionamento da API

A API utiliza um sistema de versionamento que permite múltiplas versões simultâneas. Para evitar loops de redirecionamento ao acessar via navegador, sempre especifique explicitamente a versão de uma destas formas:

1. **URL Path**: Acesse sempre uma rota específica como `/api/v1/status` ou `/api/v1/version`
2. **Header**: Adicione o header `X-API-Version: v1` em suas requisições
3. **Accept Header**: Utilize `Accept: application/vnd.api.v1+json` 
4. **Query Parameter**: Adicione `?version=v1` à URL

Para mais detalhes sobre o versionamento, consulte o arquivo [API_VERSIONING.md](API_VERSIONING.md).

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

**Uso**: Esta rota deve ser chamada antes de enviar requisições de login quando estiver usando autenticação baseada em cookies.

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
    "email": ["The email field is required."],
    "password": ["The password field is required."]
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
    "roles": [...]
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
    "roles": [...]
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

## Importante: Idempotência

Todas as requisições POST e DELETE devem incluir um header `X-Idempotence-Key` com um UUID v4 único. Isso garante que a mesma operação não seja processada múltiplas vezes se o frontend enviar requisições duplicadas acidentalmente. O backend armazenará a primeira resposta e retornará o mesmo resultado para requisições subsequentes com o mesmo chave de idempotência.

## Segurança CORS

A API está configurada para aceitar requisições de domínios específicos. Certifique-se de que seu frontend esteja em um dos domínios configurados para CORS ou solicite que sua origem seja adicionada.