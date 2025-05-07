# NFeEngine - Engine de Faturamento NF-e Open Source

## 1. Objetivo

Construir uma engine robusta, modular e open source para geração, validação e transmissão de Notas Fiscais Eletrônicas (NF-e), suportando:

* Entradas em XML (base64) ou dados estruturados.
* Modo **síncrono** (retorna NF autorizada) ou **assíncrono** (retorna protocolo e consulta posterior).
* Persistência em Postgres e bucket de objetos.
* Alta performance com Laravel Octane e filas Horizon.
* Tratativa completa de erros conforme Nota Técnica SEFAZ.

---

## 2. Visão Geral do Fluxo

1. **Recepção da Requisição**: POST `/v1/nfe` com payload:

   ```json
   {
     "tipo": "xml" | "dados",
     "conteudo": "<xml_base64>" | { /* dados */ },
     "modo": "sync" | "async",
     "idempotency_key": "string_opcional"
   }
   ```
2. **Persistência Inicial**:

    * Gravar em `nfe_requests`:

        * id, tipo, payload\_raw, xml\_base64 (se houver), modo, status `received`, idempotency\_key, timestamps.
3. **Geração ou Validação de XML**:

    * Se `tipo: dados`, chamar `GenerateNfeXmlAction` para montar o XML e assinar via NFPHP.
    * Validar campos fiscais (alíquotas, CNPJ, CFOP) em `ValidateNfeAction`, usando Laravel Form Requests ou DTOs.
    * Em caso de erro de validação, marcar status `validation_error`, salvar mensagens e retornar 400.
4. **Envio à SEFAZ**:

    * Se `modo: sync`: `SendToSefazSyncJob` envia e aguarda retorno.
    * Se `modo: async`: `SendToSefazAsyncJob` enfileira e retorna protocolo imediato.
5. **Tratamento de Resposta**:

    * **Sync**: atualizar `nfe_requests` com `status = authorized` ou `rejected`, protocolo, xml\_autorizado e chave de acesso.
    * **Async**: após callback ou polling, atualizar `nfe_requests` conforme retorno.
6. **Armazenamento em Bucket**:

    * Salvar todas as versões do XML no bucket (S3/GCS) em `{cnpj}/{ano}/{mes}/{chave}.xml`.
7. **Reprocessamento e DLQ**:

    * Configurar retries em Horizon com backoff.
    * Em falhas temporárias (timeout, instabilidade), reenqueue; após limite, mover para `failed_nfe_jobs` e status `dlq`.
8. **Logs e Eventos**:

    * Registrar em `nfe_events` cada mudança de status com payload detalhado.
    * Integrar com Laravel Telescope ou Sentry para monitoramento em produção.

---

## 3. Modelagem de Banco de Dados

### Tabela `nfe_requests`

| Campo            | Tipo          | Descrição                                       |
| ---------------- | ------------- | ----------------------------------------------- |
| id               | UUID          | PK                                              |
| tipo             | varchar(10)   | `xml` ou `dados`                                |
| payload\_raw     | jsonb         | Dados brutos da requisição                      |
| xml\_base64      | text nullable | XML em base64 (se fornecido)                    |
| modo             | varchar(6)    | `sync` ou `async`                               |
| status           | varchar(20)   | `received`, `validation_error`, `pending`, etc. |
| protocolo        | varchar(50)   | Protocolo SEFAZ (async) ou nulo                 |
| xml\_autorizado  | text nullable | XML autorizado em base64                        |
| chave\_acesso    | varchar(44)   | Chave de acesso extraída do XML autorizado      |
| tentativas       | integer       | Número de tentativas                            |
| error\_code      | varchar(10)   | Código de erro SEFAZ ou interno                 |
| error\_message   | text nullable | Descrição detalhada do erro                     |
| idempotency\_key | varchar(100)  | Chave de idempotência                           |
| created\_at      | timestamp     |                                                 |
| updated\_at      | timestamp     |                                                 |

### Tabela `nfe_events`

| Campo            | Tipo        | Descrição                            |
| ---------------- | ----------- | ------------------------------------ |
| id               | UUID        | PK                                   |
| nfe\_request\_id | UUID FK     | Referência a `nfe_requests`          |
| evento           | varchar(50) | Ex: `xml_generated`, `sent`, `error` |
| detalhes         | jsonb       | Payload de resposta ou erro          |
| created\_at      | timestamp   |                                      |

---

## 4. Tratativa de Erros

1. **Configuração**: `config/nfe_errors.php` mapeia códigos SEFAZ:

   ```php
   return [
     '100' => ['tipo' => 'autorizada', 'reprocesso' => false],
     '150' => ['tipo' => 'rejeicao',   'reprocesso' => false],
     '999' => ['tipo' => 'temporario', 'reprocesso' => true],
     // ... NTs adicionais
   ];
   ```
2. **Exceções Customizadas**:

    * `ValidationException`, `SefazTemporaryException`, `SefazPermanentException`.
3. **Fluxo de Reprocesso**:

    * Temporários → `retryAfter()` com backoff incremental.
    * Permanentes → lançar `SefazPermanentException` para falhar sem reprocessar.
4. **Timeout**:

    * Guzzle timeouts devem gerar `SefazTemporaryException`.

---

## 5. APIs

### POST `/v1/nfe`

* **Request**:

  ```json
  {
    "tipo": "xml" | "dados",
    "conteudo": string | object,
    "modo": "sync" | "async",
    "idempotency_key": "string_opcional"
  }
  ```
* **Response Sync** (200):

  ```json
  {
    "status": "authorized",
    "protocolo": "123456789",
    "xml": "<xml_base64_autorizado>",
    "chave_acesso": "43210987654321..."
  }
  ```
* **Response Erro** (4xx):

  ```json
  { "error_code": "150", "message": "Alíquota inválida" }
  ```
* **Response Async** (202):

  ```json
  { "status": "pending", "protocolo": "123456789" }
  ```

---

## 6. Pacotes & Ferramentas Sugeridos

| Finalidade                   | Pacote / Biblioteca                              |
| ---------------------------- | ------------------------------------------------ |
| NF-e (NFPHP)                 | `nfephp-org/nfephp`, `eduardokum/laravel-nfephp` |
| DTOs e validação estruturada | `spatie/data-transfer-object`                    |
| Filas e DLQ                  | Laravel Horizon (Redis/SQS)                      |
| Alta performance             | Laravel Octane, RoadRunner                       |
| Observabilidade              | Laravel Telescope, Sentry                        |
| Auditoria de eventos         | `spatie/laravel-activitylog`                     |
| Idempotência e Throttling    | Middleware próprio, `graham-campbell/throttle`   |

> **Nota**: Para enums use nativos do PHP 8.4, ex.:
>
> ```php
> enum NfeStatus: string {
>     case RECEIVED = 'received';
>     case VALIDATION_ERROR = 'validation_error';
>     case PENDING = 'pending';
>     case AUTHORIZED = 'authorized';
>     case REJECTED = 'rejected';
>     case DLQ = 'dlq';
> }
> ```

---

## 7. Checklist & Próximos Passos

* [ ] Definir DTOs e contratos de request/response
* [ ] Criar migrations para `nfe_requests` e `nfe_events`
* [ ] Implementar Actions/Jobs para geração, validação e envio (sync/async)
* [ ] Configurar Horizon e DLQ (Redis/SQS)
* [ ] Popular `config/nfe_errors.php` com NT SEFAZ
* [ ] Adicionar testes unitários e de integração (sucesso, rejeição, timeout)
* [ ] Testar homologação com SEFAZ via `laravel-nfephp`

---

## 8. Status SEFAZ e Mensagens Amigáveis

Consultar o Manual de Emissão de NF-e (Nota Técnica atualizada) para listar todos os códigos de retorno possíveis. Abaixo, mapeamento dos principais:

| Código | Status Técnico          | Mensagem Amigável                          |
| ------ | ----------------------- | ------------------------------------------ |
| 100    | Autorizado              | Nota fiscal autorizada com sucesso.        |
| 101    | Cancelamento            | Nota fiscal cancelada.                     |
| 102    | Inutilização            | Número de NF-e inutilizado.                |
| 135    | Evento registrado       | Evento registrado com sucesso.             |
| 138    | Cancelamento homologado | Cancelamento homologado pela SEFAZ.        |
| 204    | Lote recebido           | Lote de NF-e recebido pela SEFAZ.          |
| 224    | Lote processado         | Lote de NF-e processado com sucesso.       |
| 539    | Uso Denegado            | Uso denegado: verifique a legislação.      |
| 592    | Duplicidade de NF-e     | Nota fiscal já existente no sistema.       |
| 598    | Rejeição por duplicação | NF-e rejeitada por duplicidade.            |
| 609    | Falha de schema XML     | Estrutura do XML inválida.                 |
| 611    | Conteúdo divergente     | Dados do contribuinte divergentes.         |
| 614    | CFOP inválido           | Código fiscal de operação inválido.        |
| 615    | Inscrição estadual      | IE do destinatário inválida.               |
| 616    | Serie inválida          | Série informada não permitida.             |
| 617    | Chave inválida          | Chave de acesso inválida ou inconsistente. |
| 631    | NF-e não localizada     | Nota fiscal não localizada para consulta.  |
| 302    | Serviço indisponível    | SEFAZ temporariamente indisponível.        |
| 999    | Erro interno            | Erro interno: tente novamente mais tarde.  |

> **Obs.**: Para catalogar todos os códigos, consulte a Nota Técnica NF-e mais recente (disponível no portal da NF-e) e preencha `config/nfe_errors.php` com descrições e flags de reprocesso correspondentes.

---

Com isso, teremos um mapeamento completo para exibir mensagens claras ao usuário final e definir quais códigos devem reprocessar ou falhar imediatamente.
