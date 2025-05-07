# NfeEngine - Engine de Faturamento NF-e Open Source

## Objetivo

Construir uma engine robusta para faturamento, validação e transmissão de Notas Fiscais Eletrônicas (NF-e), open source, que opere de forma síncrona e assíncrona, priorizando integridade de dados, performance, tratativa detalhada de erros e facilidade de integração.

---

## Requisitos Funcionais

1. **Entradas Aceitas**
    - Receber requisições contendo:
        - XML da NF-e (formato base64) **ou**
        - Dados estruturados para gerar o XML.
    - Parâmetro informando o tipo de entrada (`xml` ou `dados`).

2. **Armazenamento**
    - Persistir toda requisição (dados/XML) no Postgres com status.
    - Associar metadados: status SEFAZ, protocolo, erros, tipo de falha, etc.
    - Salvar todos os XMLs em Bucket, independente do status final.

3. **Processamento**
    - Operação síncrona e assíncrona configurável via parâmetro (ex: `sync/async`).
    - Síncrono: resposta SEFAZ aguardada na API; Assíncrono: execução delegada a fila (Laravel Horizon).
    - Garantir que ações críticas rodem em jobs assíncronos onde aplicável (Octane).

4. **Comunicação SEFAZ**
    - Utilizar a biblioteca [NFPHP](https://github.com/nfephp-org/nfephp).
    - Implementar as duas formas de comunicação SEFAZ:
        - Síncrona: recebe NF autorizada direto.
        - Assíncrona: recebe protocolo, precisa consultar posteriormente.
    - Nunca perder protocolos SEFAZ (falhas críticas devem ser logadas e marcadas para reprocesso).

5. **Validação**
    - Antes do envio: validar alíquotas e campos obrigatórios.
    - Se inválido: não enviar para SEFAZ, salvar erro, status "erro de validação", devolver mensagem para consumidor.

6. **Tratativa de Erros**
    - Catalogar e mapear códigos de erros (vide NTs da SEFAZ).
    - Marcar erros temporários para reprocesso automático (DLQ, ex: timeout).
    - Marcar erros definitivos (cadastro, campos fiscais): não reprocessar; devolução imediata ao chamador, log completo.

7. **Bucket Storage**
    - Todo XML gerado/recebido deve ser salvo/atualizado no bucket.
    - Salvar versão em caso de mudanças de status (ex: rejeitada, autorizada).

8. **Logs e Observabilidade**
    - Registrar logs estruturados incluindo: request, response, código de erro/correção, tentativas, tempo de processamento.
    - Integração futura com monitoração/alertas.

---

## APIs e Parâmetros Esperados

- **Endpoint**: `/v1/nfe`
- **Métodos**: `POST`
- **Body Example**:
    ```json
    {
      "tipo": "xml|dados",
      "conteudo": "<xml_base64_ou_dados>",
      "sync": true|false
    }
    ```

---

## Pontos Técnicos

- Laravel + Horizon + Octane para filas e processamento de alta performance.
- Upload/download XMLs em buckets com drivers Laravel.
- Base de dados Postgres.
- Tratativa dos erros baseada na última Nota Técnica SEFAZ para NF-e.
- Jobs, events, listeners para garantir idempotência e nunca perder protocolos.

---

## Considerações Fiscais / Técnica

- Só gerar/consultar após validação completa dos impostos obrigatórios.
- Interação com NFPHP para assinatura/envio/sefaz.

---

## Pontos a Refinar (Checklist)

- Mapear erros das NT SEFAZ ativamente.
- Mapear regras das alíquotas fiscais/automações mínimas.
- Definir estrutura tabela de banco.
- Definir modelo de bucket por NF/cnpj/data.
- Definir contratos de resposta para erros e sucessos.
- Definir resiliência na fila/reprocesso/DLQ (padrão Horizon/Laravel).
- Definir extensão do controle de status (ex: `aguardando`, `autorizada`, `rejeitada`, `erro fiscal`, `erro temporário`).

---

## Referências e Links Importantes

- [NFPHP](https://github.com/nfephp-org/nfephp)
- [Notas Técnicas SEFAZ](https://www.nfe.fazenda.gov.br/portal/exibirArquivo.aspx?conteudo=A2s5osvQX7s=)
- [Laravel Horizon](https://laravel.com/docs/10.x/horizon)
- [Laravel Octane](https://laravel.com/docs/10.x/octane)
