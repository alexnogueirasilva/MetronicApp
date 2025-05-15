# MetronicApp - API Boilerplate

[![Laravel](https://img.shields.io/badge/Laravel-12.0-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Laravel Octane](https://img.shields.io/badge/Octane-2.9-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/docs/octane)
[![Laravel Horizon](https://img.shields.io/badge/Horizon-5.31-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/docs/horizon)
[![Redis](https://img.shields.io/badge/Redis-Support-DC382D?style=for-the-badge&logo=redis&logoColor=white)](https://redis.io)
[![PestPHP](https://img.shields.io/badge/PestPHP-3.8-8A2BE2?style=for-the-badge&logo=php&logoColor=white)](https://pestphp.com)

<p align="center">
  <img src="./public/img/overview/architecture.svg" alt="MetronicApp Architecture" width="800">
</p>

## 📊 Análise Técnica do Projeto

### Visão Geral da Arquitetura
O MetronicApp é um boilerplate avançado para APIs RESTful em Laravel, seguindo uma arquitetura moderna com camadas bem definidas e componentes isolados. O sistema utiliza padrões como Actions, DTOs, e Resources para manter o código limpo e testável.

### Sistema de Rate Limiting Avançado
O projeto implementa um sistema sofisticado de rate limiting através de dois middlewares principais:

#### EndpointRateLimiter
- Aplica limites específicos por tipo de endpoint
- Define multiplicadores para endpoints sensíveis (login: 0.2x o limite normal)
- Adapta o tempo de decaimento conforme a sensibilidade
- Usa pattern matching para identificar tipos de endpoints
- Adiciona cabeçalhos de rate limit nas respostas HTTP

#### TenantRateLimiter
- Gerencia limites baseados no plano de assinatura do tenant
- Permite configuração de limites personalizados
- Ignora limites para tenants com plano ilimitado
- Implementa limites para usuários anônimos baseados em IP

### Sistema de Autenticação Multi-método
O sistema oferece múltiplos métodos de autenticação com alta segurança:

#### Login Tradicional
- Autenticação com email/senha
- Tokens API via Laravel Sanctum

#### OTP (One-Time Password)
- Códigos numéricos de 6 dígitos
- Envio por email via filas prioritárias
- Expiração em 10 minutos
- Verificação de uso único

#### Magic Links
- Login sem senha via links seguros
- Tokens com expiração
- Envio por email

#### TOTP (Time-based OTP)
- Implementação de 2FA com biblioteca spomky-labs/otphp
- QR codes para Google Authenticator/Authy
- Middleware EnsureTotpVerified para rotas protegidas

### Sistema de Controle de Acesso (RBAC)
- Modelo Role com relacionamento many-to-many com Permission
- Trait HasRole para gerenciar permissões
- Cache otimizado para verificações
- Observer para manter o sistema de cache atualizado
- Controllers para gerenciamento de roles e permissões

### Multi-tenancy
- Associação de usuários a um tenant (relação one-to-many)
- Planos de assinatura via enum PlanType
- Limites de API baseados no plano
- Configurações específicas por tenant
- Funcionalidades como períodos de trial

### Geolocalização
- Serviço que consulta o ip-api.com para obter localização
- DTO imutável com propriedades ip, city e country
- Tratamento de erros e valores inválidos
- Timeout configurado para evitar atrasos

### Sistema de Filas com Horizon
- Filas separadas por domínio (auth, notificações, processamento)
- Supervisores dedicados para cada tipo de tarefa
- Monitoramento e métricas via dashboard

## 🚀 Visão Geral

MetronicApp é um boilerplate completo para desenvolvimento de APIs RESTful com Laravel. Focado em alta performance, segurança e escalabilidade, ele fornece uma estrutura de base sólida para projetos profissionais.

## ✨ Funcionalidades Principais

### 🔐 Sistema de Autenticação Multi-Método
- Login tradicional com email/senha
- Autenticação de dois fatores (TOTP)
- One-Time Password por email (OTP)
- Magic Links (login sem senha)
- Tokens de API via Laravel Sanctum
- Verificação de email

### 👮‍♂️ Controle de Acesso Avançado
- Controle de acesso baseado em funções (RBAC)
- Sistema granular de permissões
- Suporte a múltiplos perfis de usuário
- Cache otimizado para verificações de permissão

### 🚄 Performance Otimizada
- Laravel Octane com Swoole para alto desempenho
- Queue workers gerenciados pelo Laravel Horizon
- Filas separadas por domínio (auth, notificações, processamento)
- Supervisores dedicados para cada tipo de tarefa

### 📦 Estrutura Arquitetural
- Design API-first com resposta consistente
- Padrão de Actions para lógica de negócio
- DTOs para transferência de dados
- Services para lógica compartilhada
- Uso de Enums para valores estáticos

### 🧪 Qualidade de Código
- Testes de feature e unidade com PestPHP
- Análise estática com PHPStan (nível 8)
- Formatação de código com Laravel Pint
- Git hooks via CaptainHook

### 📝 Documentação
- Documentação de API com Scribe
- Documentação de código com PHPDoc
- Swagger/OpenAPI disponível

## 🧩 Stack Técnica

- **Framework**: Laravel 12.0
- **PHP Version**: 8.2+
- **Performance**: Laravel Octane 2.9 com Swoole
- **Queue**: Laravel Horizon 5.31 com Redis
- **Database**: Suporte para MySQL, PostgreSQL, SQLite
- **Cache**: Redis
- **Autenticação**: Sanctum 4.0
- **2FA/OTP**: spomky-labs/otphp 11.3
- **Testes**: PestPHP 3.8
- **Docs**: knuckleswtf/scribe 5.2

## 📋 Lista de APIs

### Autenticação
- `POST /api/auth/login` - Login tradicional
- `POST /api/auth/magic-link` - Solicitar magic link
- `GET|POST /api/auth/magic-link/verify` - Verificar magic link
- `POST /api/auth/otp/request` - Solicitar código OTP por email
- `POST /api/auth/otp/verify` - Verificar código OTP
- `POST /api/auth/otp/totp/setup` - Configurar TOTP (2FA)
- `POST /api/auth/otp/totp/verify` - Verificar código TOTP
- `POST /api/auth/otp/totp/confirm` - Confirmar configuração TOTP
- `POST /api/auth/otp/disable` - Desativar OTP
- `GET /api/auth/me` - Obter dados do usuário autenticado
- `DELETE /api/auth/logout` - Logout (revoga token)
- `POST /api/auth/forgot-password` - Solicitar recuperação de senha
- `POST /api/auth/reset-password` - Redefinir senha

### Controle de Acesso
- `GET /api/acl/role` - Listar funções
- `GET /api/acl/role/{id}` - Detalhes da função
- `POST /api/acl/role` - Criar função
- `PUT /api/acl/role/{id}` - Atualizar função
- `DELETE /api/acl/role/{id}` - Remover função
- `GET /api/acl/permission` - Listar permissões
- `GET /api/acl/permission/{id}` - Detalhes da permissão
- `POST /api/acl/permission` - Criar permissão
- `PUT /api/acl/permission/{id}` - Atualizar permissão
- `DELETE /api/acl/permission/{id}` - Remover permissão

## 🛠️ Instalação e Configuração

### Requisitos
- PHP 8.2+
- Composer
- Redis
- MySQL 8.0+ / PostgreSQL 13+
- Extensão PHP Swoole (opcional para Octane)

### Passos para Instalação

1. Clone o repositório
   ```bash
   git clone https://github.com/seu-usuario/metronic-app.git
   cd metronic-app
   ```

2. Instale as dependências
   ```bash
   composer install
   ```

3. Configure o ambiente
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure o banco de dados no arquivo `.env`
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=metronic_app
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Configure o Redis para Horizon e cache
   ```
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   REDIS_DB=0
   REDIS_CACHE_DB=1
   REDIS_HORIZON_DB=2
   REDIS_QUEUE_DB=3
   ```

6. Execute as migrações
   ```bash
   php artisan migrate
   ```

7. Execute os seeders (opcional)
   ```bash
   php artisan db:seed
   ```

8. Inicie o servidor
   ```bash
   # Desenvolvimento
   php artisan serve
   
   # Alta performance (produção)
   php artisan octane:start --workers=4 --task-workers=2
   ```

9. Inicie o Horizon (processamento de filas)
   ```bash
   php artisan horizon
   ```

## 🔧 Customização

### Configurando novas filas

1. Adicione um novo tipo de fila no Enum `QueueEnum`:
   ```php
   enum QueueEnum: string
   {
       case CUSTOM_QUEUE = 'custom-queue';
       // ...
   }
   ```

2. Configure o supervisor no arquivo `config/horizon.php`:
   ```php
   'custom-supervisor' => [
       'connection' => env('HORIZON_QUEUE_CONNECTION', 'redis'),
       'queue' => [QueueEnum::CUSTOM_QUEUE->value],
       'balance' => 'auto',
       'maxProcesses' => 5,
       'tries' => 3,
   ],
   ```

3. Use a fila em seus jobs:
   ```php
   YourJob::dispatch($data)->onQueue(QueueEnum::CUSTOM_QUEUE->value);
   ```

### Adicionando novas funcionalidades

1. Criar uma Action para nova funcionalidade:
   ```bash
   php artisan make:class App\\Actions\\Domain\\YourAction
   ```

2. Criar um Controller:
   ```bash
   php artisan make:controller Domain/YourController
   ```

3. Criar DTO (se necessário):
   ```bash
   php artisan make:class App\\DTO\\Domain\\YourDTO
   ```

4. Adicionar rota em `routes/api.php`

## 🛡️ Recursos de Segurança

- Proteção CSRF para rotas web
- Rate limiting avançado baseado em:
  - Tenant e plano de assinatura
  - Tipo de endpoint (maior proteção para endpoints sensíveis)
  - Ajuste automático de limites conforme o contexto
- Sanitização de inputs
- Validação de dados robusta
- Proteção contra ataques comuns:
  - SQL Injection
  - XSS
  - CSRF
  - Clickjacking
  - Força bruta

## 📊 Monitoramento e Métricas

- Dashboard Horizon para monitoramento de filas
- Telemetria via tags de jobs
- Notificações para falhas e eventos críticos
- Suporte a logs estruturados

## 🤝 Contribuição

Contribuições são bem-vindas! Por favor, siga estes passos:

1. Fork o repositório
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Faça commit das mudanças (`git commit -am 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## 📚 Recursos Adicionais

- [Documentação do Laravel](https://laravel.com/docs)
- [Documentação do Horizon](https://laravel.com/docs/horizon)
- [Documentação do Octane](https://laravel.com/docs/octane)
- [Guia de PestPHP](https://pestphp.com/docs)

## 📋 Itens para Melhorar

- [ ] Implementar sistema de notificações em tempo real
- [ ] Integrar login via redes sociais (OAuth)
- [ ] Adicionar módulo de auditoria para ações de usuários
- [ ] Implementar cache de resposta para endpoints públicos
- [x] Criar sistema de rate limit por plano/usuário
- [ ] Adicionar suporte a GraphQL
- [ ] Implementar versionamento de API
- [ ] Documentação mais detalhada para cada módulo

## 📝 Licença

Este projeto está licenciado sob a [Licença MIT](LICENSE.md).