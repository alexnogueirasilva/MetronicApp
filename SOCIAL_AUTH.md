# Autenticação Social com Google

Esta documentação descreve a implementação da autenticação social via Google no MetronicApp. A implementação permite aos usuários fazer login usando suas contas Google, tanto através de clientes web quanto mobile/API.

## Visão Geral

A autenticação social foi implementada de duas maneiras:

1. **Autenticação Web** - Usa o fluxo padrão de redirecionamento do OAuth2
2. **Autenticação API** - Usa um fluxo baseado em JSON para integrações API/mobile

Ambas as implementações usam o Laravel Socialite para autenticação via provedores OAuth e o Laravel Sanctum para autenticação por token.

## Requisitos

Para utilizar a autenticação social, você precisa configurar:

1. **Credenciais do Google**:
   - Criar um projeto no [Google Cloud Console](https://console.cloud.google.com)
   - Configurar a tela de consentimento OAuth
   - Criar credenciais OAuth 2.0 para web (origem e callback)
   - Adicionar as credenciais ao arquivo `.env`

2. **Variáveis de Ambiente**:
   ```env
   GOOGLE_CLIENT_ID=seu-client-id
   GOOGLE_CLIENT_SECRET=seu-client-secret
   GOOGLE_REDIRECT_URI=https://api.devaction.com.br/v1/auth/social/google/callback
   ```

## Fluxo de Autenticação Web

O fluxo web utiliza redirecionamentos completos e é adequado para aplicativos web:

1. O usuário é redirecionado para `GET /auth/social/google`
2. O usuário é redirecionado para a página de consentimento do Google
3. Após autorizar, o Google redireciona para o callback da aplicação
4. O usuário é autenticado e redirecionado para o dashboard

### Endpoints Web

```
GET /v1/auth/social/google
GET /v1/auth/social/google/callback
```

## Fluxo de Autenticação API

O fluxo API/mobile é baseado em JSON e não depende de redirecionamentos completos:

1. O cliente obtém a URL de autorização do Google via `GET /auth/social/google`
2. O cliente redireciona o usuário para autorização (web view ou navegador)
3. Ao receber o código de autorização, envia para `POST /auth/social/google/callback`
4. A API retorna o token e informações do usuário

### Endpoints API

```
GET /v1/auth/social/{provider} - Retorna URL de autorização
POST /v1/auth/social/{provider}/callback - Processa o código de autorização
```

Exemplo de resposta:

```json
{
  "status": "success",
  "message": "Autenticação social realizada com sucesso.",
  "data": {
    "user": {
      "id": 123,
      "name": "Nome do Usuário",
      "email": "usuario@gmail.com",
      "avatar": "https://lh3.googleusercontent.com/..."
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890",
    "requires_otp": false
  },
  "meta": {
    "api_version": "v1"
  }
}
```

## Modelo de Dados

O modelo `User` foi estendido para suportar autenticação social:

```php
/**
 * @property string $id
 * @property ?string $password (agora pode ser nulo)
 * @property ?string $provider (e.g., "google")
 * @property ?string $provider_id (ID único do usuário no provedor)
 * ...
 */
```

## Lógica de Conexão de Contas

Quando um usuário faz login via Google, o sistema:

1. **Verifica se o usuário existe** - Procurando por `provider` + `provider_id`
2. **Procura pela correspondência de email** - Se encontrar, vincula a conta social
3. **Cria um novo usuário** - Se nenhuma correspondência for encontrada

Isso permite que usuários existentes conectem suas contas sociais sem criar duplicatas.

## Autenticação Multi-Fator

Se o usuário tiver 2FA/TOTP configurado:

1. A autenticação social ocorre normalmente
2. O campo `requires_otp` na resposta indicará que verificação adicional é necessária
3. O cliente deve redirecionar para a verificação de TOTP

## Implementação Frontend

### Web

```javascript
// Redirecionar para login do Google
function loginWithGoogle() {
  window.location.href = "/v1/auth/social/google";
}
```

### Mobile/SPA

```javascript
// Obter URL de autorização
async function getGoogleAuthUrl() {
  const response = await api.get('/v1/auth/social/google');
  return response.data.data.url;
}

// Abrir webview para login
function loginWithGoogle() {
  getGoogleAuthUrl().then(url => {
    // Abre webview ou navegador com URL
    openWebView(url); 
  });
}

// Processar callback após autorização
async function handleGoogleCallback(authCode) {
  const response = await api.post('/v1/auth/social/google/callback', {
    code: authCode
  });
  
  if (response.data.data.requires_otp) {
    // Redirecionar para verificação TOTP
    redirectToTOTPVerification();
  } else {
    // Login completo
    saveToken(response.data.data.token);
    redirectToDashboard();
  }
}
```

## Segurança

Esta implementação segue as melhores práticas:

1. **Verificação de Email** - Contas sociais são consideradas verificadas
2. **Stateless no API Flow** - Não depende de sessão para fluxo API
3. **Rate Limiting** - Endpoints protegidos por rate limiting
4. **CSRF Protection** - Proteção automática no fluxo web

## Suporte Multi-Provedor

Embora atualmente apenas o Google esteja implementado, a arquitetura foi projetada para permitir facilmente a adição de outros provedores (Facebook, GitHub, etc.) no futuro.