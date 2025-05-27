# Modelo de Domínio do SaaS de Pedidos e Licitações

Este documento descreve a estrutura de domínio refinada do sistema, incluindo entidades, atributos, relacionamentos e hierarquias, seguindo a modelagem proposta com Tenant → Holding → Branch.

## Diagrama de Entidades Principais

```
+-------------+      +------------+      +-----------+
|   Tenant    |------| Holding    |------| Branch    |
+-------------+      +------------+      +-----------+
       |                   |                  |
       |                   |                  |
       v                   v                  v
+-------------+      +------------+      +-----------+
|    User     |      | Product    |      | Inventory |
+-------------+      +------------+      +-----------+
                           |                  |
                           v                  v
                     +------------+      +-----------+
                     |   Order    |------|OrderItem  |
                     +------------+      +-----------+
                           |
                           |
        +------------------+------------------+
        |                  |                  |
        v                  v                  v
+---------------+  +---------------+  +---------------+
| PublicOrder   |  | PurchaseOrder |  | TransferOrder |
+---------------+  +---------------+  +---------------+
```

## Estrutura Organizacional

### Tenant

Representa a unidade máxima de isolamento no sistema SaaS, separando completamente os dados entre diferentes clientes.

**Atributos**:
- `id` (ULID): Identificador único
- `name` (string): Nome do tenant
- `domain` (string): Domínio personalizado (opcional)
- `status` (enum): Ativo, Inativo, Suspenso
- `settings` (JSON): Configurações específicas do tenant
- `created_at` (datetime): Data de criação
- `updated_at` (datetime): Data de última atualização

**Relacionamentos**:
- Tem muitas Holdings (`one-to-many`)
- Tem muitos Usuários (`one-to-many`)

**Regras de Negócio**:
- Cada tenant opera de forma completamente isolada
- Configurações no nível do tenant afetam todas as holdings subordinadas
- O domínio personalizado é opcional para acesso white-label

### Holding

Representa uma empresa controladora ou grupo empresarial dentro de um tenant, que gerencia múltiplas filiais (branches).

**Atributos**:
- `id` (ULID): Identificador único
- `tenant_id` (ULID): Referência ao tenant pai
- `name` (string): Nome comercial da holding
- `legal_name` (string): Razão social
- `document` (string): CNPJ da holding
- `logo` (string): Caminho para o logo
- `settings` (JSON): Configurações específicas da holding
- `created_at` (datetime): Data de criação
- `updated_at` (datetime): Data de última atualização

**Relacionamentos**:
- Pertence a um Tenant (`belongs-to`)
- Tem muitas Branches/Filiais (`one-to-many`)
- Tem muitos Usuários (`one-to-many`)
- Tem muitos Produtos (`one-to-many`)

**Regras de Negócio**:
- Uma holding só pode pertencer a um único tenant
- Configurações no nível da holding afetam todas as branches subordinadas
- Usuários podem pertencer a uma holding específica ou ao tenant
- Produtos são definidos no nível da holding para compartilhamento entre branches

### Branch (Filial)

Representa uma unidade operacional específica de uma holding, com sua própria estrutura fiscal e operacional.

**Atributos**:
- `id` (ULID): Identificador único
- `holding_id` (ULID): Referência à holding pai
- `name` (string): Nome comercial da filial
- `legal_name` (string): Razão social
- `document` (string): CNPJ da filial
- `main_state` (string): UF principal da filial
- `address` (string): Endereço completo
- `city` (string): Cidade
- `zip_code` (string): CEP
- `tax_regime` (enum): Regime tributário (Normal, Simples Nacional, etc.)
- `is_active` (boolean): Status ativo/inativo
- `created_at` (datetime): Data de criação
- `updated_at` (datetime): Data de última atualização

**Relacionamentos**:
- Pertence a uma Holding (`belongs-to`)
- Tem muitas Inscrições Estaduais (`one-to-many`)
- Tem muitos Usuários (`one-to-many`)
- Tem muitos Pedidos (`one-to-many`)
- Tem muitos Inventários (`one-to-many`, um por produto)
- Tem muitas Regras Fiscais (`one-to-many`)

**Regras de Negócio**:
- Uma branch só pode pertencer a uma única holding
- Cada branch pode ter múltiplas inscrições estaduais por UF, para diferentes finalidades
- Cada branch opera seu próprio estoque, mas com visibilidade e consolidação em nível de holding
- Regras fiscais são configuradas por branch
- Documentos fiscais são emitidos no nível da branch

### BranchStateRegistration (Inscrição Estadual da Filial)

Armazena as múltiplas inscrições estaduais que uma filial pode ter em diferentes estados, podendo ter mais de uma por estado.

**Atributos**:
- `id` (ULID): Identificador único
- `branch_id` (ULID): Referência à filial
- `state` (string): UF da inscrição
- `state_registration` (string): Número da inscrição estadual
- `registration_type` (enum): Tipo de inscrição (Normal, ST, Rural, Isento, etc.)
- `is_main` (boolean): Indica se é a IE principal da filial para este estado
- `is_active` (boolean): Status ativo/inativo
- `created_at` (datetime): Data de criação
- `updated_at` (datetime): Data de última atualização

**Relacionamentos**:
- Pertence a uma Branch (`belongs-to`)

**Regras de Negócio**:
- Uma branch pode ter múltiplas inscrições estaduais por UF, diferenciadas pelo tipo
- Apenas uma inscrição pode ser marcada como principal para cada estado
- A inscrição principal do estado principal deve corresponder ao estado principal da filial
- Diferentes tipos de inscrição estadual podem ser necessários para regimes especiais, ST, etc.

## Gestão de Usuários

### User

Representa um usuário do sistema, que pode estar associado a um tenant, holding ou branch específico.

**Atributos**:
- `id` (ULID): Identificador único
- `tenant_id` (ULID): Referência ao tenant
- `holding_id` (ULID, nullable): Referência à holding (opcional)
- `branch_id` (ULID, nullable): Referência à branch (opcional)
- `name` (string): Nome completo
- `email` (string): Email (único)
- `password` (string): Senha criptografada
- `role_id` (ULID): Referência ao papel/função
- `status` (enum): Ativo, Inativo, Bloqueado
- `last_login_at` (datetime): Data/hora do último login
- `created_at` (datetime): Data de criação
- `updated_at` (datetime): Data de última atualização

**Relacionamentos**:
- Pertence a um Tenant (`belongs-to`)
- Pode pertencer a uma Holding (`belongs-to`, opcional)
- Pode pertencer a uma Branch (`belongs-to`, opcional)
- Pertence a um Role/Papel (`belongs-to`)
- Tem muitas Permissões através do Role (`many-to-many` via Role)

**Regras de Negócio**:
- Todo usuário deve pertencer a um tenant
- Um usuário pode pertencer a uma holding específica, a uma branch específica, ou apenas ao tenant
- Usuários de nível tenant podem acessar todas as holdings e branches
- Usuários de nível holding podem acessar todas as branches da holding
- Usuários de nível branch são restritos à branch específica
- Permissões são herdadas através do papel (role) atribuído

## Gestão de Produtos e Estoques

### Product

Representa um produto ou serviço comercializado, definido no nível da holding e compartilhado entre branches.

**Atributos**:
- `id` (ULID): Identificador único
- `holding_id` (ULID): Referência à holding
- `prefix` (string): Prefixo de categorização (P, S, etc.)
- `sku` (string): Código do produto (único por holding)
- `name` (string): Nome do produto
- `description` (text): Descrição detalhada
- `price` (decimal): Preço de venda padrão
- `cost` (decimal): Custo médio
- `ncm` (string): Código NCM (para tributação)
- `origin` (enum): Nacional, Importado
- `unit` (string): Unidade de medida (UN, KG, etc.)
- `weight` (decimal): Peso em kg
- `dimensions` (JSON): Dimensões (altura, largura, comprimento)
- `is_active` (boolean): Status ativo/inativo
- `created_at` (datetime): Data de criação
- `updated_at` (datetime): Data de última atualização

**Relacionamentos**:
- Pertence a uma Holding (`belongs-to`)
- Tem muitos Inventários (`one-to-many`, um por branch)
- Tem muitos OrderItems (`one-to-many`)

**Regras de Negócio**:
- Produtos são definidos no nível da holding
- O SKU deve ser único dentro da holding
- A combinação de prefix + número forma o SKU completo
- Cada branch mantém seu próprio inventário para o produto
- Informações fiscais (NCM, origem) são obrigatórias para produtos físicos

### Inventory

Representa o estoque de um produto específico em uma branch específica, com consolidação em nível de holding.

**Atributos**:
- `id` (ULID): Identificador único
- `holding_id` (ULID): Referência à holding
- `branch_id` (ULID): Referência à branch
- `product_id` (ULID): Referência ao produto
- `quantity` (decimal): Quantidade total em estoque
- `reserved_quantity` (decimal): Quantidade reservada para pedidos
- `available_quantity` (decimal): Quantidade disponível (calculada)
- `min_quantity` (decimal): Estoque mínimo
- `max_quantity` (decimal): Estoque máximo
- `last_purchase_date` (date): Data da última compra
- `last_purchase_price` (decimal): Preço da última compra
- `last_sale_date` (date): Data da última venda
- `updated_at` (datetime): Data de última atualização

**Relacionamentos**:
- Pertence a uma Holding (`belongs-to`)
- Pertence a uma Branch (`belongs-to`)
- Pertence a um Product (`belongs-to`)
- Tem muitos InventoryMovements (`one-to-many`)

**Regras de Negócio**:
- Cada combinação branch + product tem exatamente um registro de inventory
- A holding_id é incluída para permitir consultas consolidadas em nível de holding
- available_quantity = quantity - reserved_quantity
- Alterações no estoque são realizadas através de movimentações
- Alertas são gerados quando o estoque atinge mínimo ou máximo
- Relatórios consolidados por holding mostram a situação de estoque em todas as branches

### InventoryMovement

Registra cada movimentação de estoque para garantir rastreabilidade completa, com associação à holding para consolidação.

**Atributos**:
- `id` (ULID): Identificador único
- `holding_id` (ULID): Referência à holding
- `branch_id` (ULID): Referência à branch
- `product_id` (ULID): Referência ao produto
- `movement_type` (enum): Entrada, Saída, Transferência, Ajuste
- `quantity` (decimal): Quantidade movimentada
- `reference_type` (string): Tipo de documento de referência
- `reference_id` (ULID): ID do documento de referência
- `lot_number` (string, nullable): Número do lote
- `serial_number` (string, nullable): Número de série
- `unit_cost` (decimal): Custo unitário da movimentação
- `user_id` (ULID): Usuário responsável
- `notes` (text): Observações
- `created_at` (datetime): Data de criação

**Relacionamentos**:
- Pertence a uma Holding (`belongs-to`)
- Pertence a uma Branch (`belongs-to`)
- Pertence a um Product (`belongs-to`)
- Pertence a um User (`belongs-to`)
- Pode pertencer a vários tipos de documentos (`polymorphic`)

**Regras de Negócio**:
- Toda alteração de estoque deve gerar um registro de movimentação
- A holding_id é incluída para permitir relatórios consolidados e rastreamento em nível de holding
- Movimentações não podem ser alteradas após criação (imutáveis)
- Para estornos, uma nova movimentação inversa deve ser criada
- Movimentações podem ser rastreadas até seu documento de origem
- Consolidações de movimentação por holding permitem análises de fluxo de produtos entre filiais

## Gestão de Pedidos e Documentos

### Order (Pedido Base)

Entidade base para todos os tipos de pedidos (venda privada, venda pública, compra, transferência).

**Atributos**:
- `id` (ULID): Identificador único
- `prefix` (string): Prefixo do tipo de pedido (PV, PC, PT)
- `order_number` (string): Número sequencial do pedido
- `branch_id` (ULID): Filial emissora
- `type` (enum): Tipo de pedido (public, private, purchase, transfer)
- `entity_type` (enum): Tipo de entidade relacionada (customer, supplier, branch)
- `entity_id` (ULID): ID da entidade relacionada
- `creator_user_id` (ULID): Usuário que criou o pedido
- `assignee_user_id` (ULID, nullable): Usuário responsável pelo atendimento
- `status` (enum): Status do pedido
- `order_date` (date): Data do pedido
- `delivery_date` (date, nullable): Data prevista de entrega
- `payment_terms` (string): Condições de pagamento
- `shipping_method` (string): Método de envio
- `notes` (text): Observações
- `total_amount` (decimal): Valor total
- `total_tax` (decimal): Total de impostos
- `related_order_id` (ULID, nullable): Pedido relacionado
- `created_at` (datetime): Data de criação
- `updated_at` (datetime): Data de última atualização

**Relacionamentos**:
- Pertence a uma Branch (`belongs-to`)
- Pertence a um User criador (`belongs-to`)
- Pertence a um User responsável (`belongs-to`, opcional)
- Tem muitos OrderItems (`one-to-many`)
- Tem muitos Files (`one-to-many`)
- Tem muitas Invoices (`one-to-many`)
- Pode ter uma extensão específica (`one-to-one`, polimórfico)
- Pode se relacionar com outro Order (`self-referential`)

**Regras de Negócio**:
- Cada tipo de pedido tem seu próprio prefixo (PV, PC, PT)
- A numeração é sequencial por branch e prefixo
- Status segue uma máquina de estados específica por tipo
- Valores totais são calculados a partir dos itens
- O responsável pode ser diferente do criador em pedidos públicos

### PublicOrder (Extensão para Pedidos Públicos)

Extensão específica para pedidos públicos (licitações), com informações adicionais.

**Atributos**:
- `order_id` (ULID): Referência ao pedido base
- `bidding_process_number` (string): Número do processo licitatório
- `bidding_type` (enum): Tipo de licitação
- `contract_number` (string): Número do contrato
- `government_entity` (string): Órgão público
- `requisition_number` (string): Número da requisição
- `empenho_number` (string): Número do empenho
- `has_required_attachments` (boolean): Indica se tem anexos obrigatórios

**Relacionamentos**:
- Pertence a um Order (`belongs-to`)

**Regras de Negócio**:
- Todo pedido público requer anexos obrigatórios
- Precisa ter um responsável designado diferente do criador
- Validações específicas para cada tipo de licitação
- Prazos de entrega são rigorosos

### PurchaseOrder (Extensão para Pedidos de Compra)

Extensão específica para pedidos de compra, com informações adicionais.

**Atributos**:
- `order_id` (ULID): Referência ao pedido base
- `supplier_reference` (string): Referência no sistema do fornecedor
- `expected_delivery_date` (date): Data esperada de entrega
- `delivery_address` (text): Endereço de entrega
- `payment_condition` (string): Condição de pagamento
- `buyer_user_id` (ULID): Usuário comprador
- `approved_by_user_id` (ULID, nullable): Usuário que aprovou
- `approval_date` (datetime, nullable): Data da aprovação

**Relacionamentos**:
- Pertence a um Order (`belongs-to`)
- Pertence a um User comprador (`belongs-to`)
- Pode pertencer a um User aprovador (`belongs-to`, opcional)

**Regras de Negócio**:
- Pedidos acima de um valor definido requerem aprovação
- Recebimento pode ser parcial ou total
- Cada recebimento gera entrada no estoque
- Pode ser vinculado a múltiplas notas fiscais de entrada

### TransferOrder (Extensão para Pedidos de Transferência)

Extensão específica para pedidos de transferência entre filiais.

**Atributos**:
- `order_id` (ULID): Referência ao pedido base
- `source_branch_id` (ULID): Filial de origem
- `destination_branch_id` (ULID): Filial de destino
- `reason` (string): Motivo da transferência
- `transfer_type` (enum): Tipo de transferência
- `authorized_by_user_id` (ULID, nullable): Usuário que autorizou
- `authorization_date` (datetime, nullable): Data da autorização

**Relacionamentos**:
- Pertence a um Order (`belongs-to`)
- Pertence a uma Branch de origem (`belongs-to`)
- Pertence a uma Branch de destino (`belongs-to`)
- Pode pertencer a um User autorizador (`belongs-to`, opcional)

**Regras de Negócio**:
- Transferências entre filiais diferentes requerem nota fiscal
- Estoque é reservado na origem ao criar o pedido
- Estoque em trânsito é rastreado separadamente
- Recebimento na filial destino confirma a transferência

### OrderItem

Representa um item específico em um pedido, com quantidades e valores.

**Atributos**:
- `id` (ULID): Identificador único
- `order_id` (ULID): Referência ao pedido
- `product_id` (ULID): Referência ao produto
- `quantity` (decimal): Quantidade
- `unit_price` (decimal): Preço unitário
- `discount` (decimal): Desconto
- `tax_amount` (decimal): Valor de impostos
- `tax_rules` (JSON): Regras fiscais aplicadas
- `subtotal` (decimal): Subtotal (calculado)
- `purchase_order_item_id` (ULID, nullable): Item de pedido de compra relacionado
- `lot_number` (string, nullable): Número do lote
- `serial_number` (string, nullable): Número de série
- `created_at` (datetime): Data de criação
- `updated_at` (datetime): Data de última atualização

**Relacionamentos**:
- Pertence a um Order (`belongs-to`)
- Pertence a um Product (`belongs-to`)
- Pode pertencer a um OrderItem de compra (`belongs-to`, opcional)

**Regras de Negócio**:
- Subtotal = (quantity * unit_price) - discount
- Regras fiscais são armazenadas no momento da adição (snapshot)
- Itens em pedidos de venda podem rastrear até o item de compra original
- Lote/série são obrigatórios para produtos que exigem rastreabilidade

### Invoice (Nota Fiscal)

Representa um documento fiscal (nota fiscal de entrada ou saída).

**Atributos**:
- `id` (ULID): Identificador único
- `branch_id` (ULID): Referência à filial emissora/receptora
- `invoice_type` (enum): Tipo (entrada, saída)
- `invoice_number` (string): Número da nota
- `invoice_series` (string): Série da nota
- `invoice_key` (string): Chave da NF-e
- `entity_type` (enum): Tipo de entidade relacionada
- `entity_id` (ULID): ID da entidade relacionada
- `order_id` (ULID, nullable): Pedido relacionado
- `issue_date` (date): Data de emissão
- `operation_date` (date): Data da operação
- `total_amount` (decimal): Valor total
- `total_products` (decimal): Valor total dos produtos
- `total_tax` (decimal): Valor total de impostos
- `status` (enum): Status da nota
- `xml_path` (string): Caminho para o XML no S3
- `pdf_path` (string): Caminho para o PDF no S3
- `created_at` (datetime): Data de criação
- `updated_at` (datetime): Data de última atualização

**Relacionamentos**:
- Pertence a uma Branch (`belongs-to`)
- Pode pertencer a um Order (`belongs-to`, opcional)
- Tem muitos InvoiceItems (`one-to-many`)
- Tem muitos InvoiceTaxes (`one-to-many`)

**Regras de Negócio**:
- Notas fiscais de saída são geradas a partir de pedidos de venda
- Notas fiscais de entrada são vinculadas a pedidos de compra
- XML e PDF são armazenados no S3
- Validações fiscais são aplicadas antes da emissão

### File

Representa um arquivo armazenado no sistema, associado a diferentes entidades.

**Atributos**:
- `id` (ULID): Identificador único
- `holding_id` (ULID): Referência à holding
- `branch_id` (ULID): Referência à branch
- `entity_type` (string): Tipo de entidade relacionada
- `entity_id` (ULID): ID da entidade relacionada
- `user_id` (ULID): Usuário que fez upload
- `file_name` (string): Nome do arquivo no sistema
- `original_name` (string): Nome original do arquivo
- `file_type` (string): Tipo MIME
- `file_size` (integer): Tamanho em bytes
- `file_path` (string): Caminho no S3
- `description` (text): Descrição do arquivo
- `category` (enum): Categoria do arquivo
- `created_at` (datetime): Data de criação
- `updated_at` (datetime): Data de última atualização

**Relacionamentos**:
- Pertence a uma Holding (`belongs-to`)
- Pertence a uma Branch (`belongs-to`)
- Pertence a um User (`belongs-to`)
- Pode pertencer a vários tipos de entidades (`polymorphic`)

**Regras de Negócio**:
- Arquivos são organizados por holding/branch
- Caminho no S3 segue estrutura: `holdings/{holding_id}/branches/{branch_id}/{entity_type}/{entity_id}/`
- Categorias específicas para documentos fiscais e licitações
- Permissões de acesso baseadas na hierarquia organizacional

## Regras Fiscais

### TaxRule

Define uma regra fiscal específica para uma combinação de parâmetros.

**Atributos**:
- `id` (ULID): Identificador único
- `holding_id` (ULID): Referência à holding
- `branch_id` (ULID): Referência à branch
- `name` (string): Nome da regra
- `description` (text): Descrição detalhada
- `contributor_type` (enum): Tipo de contribuinte
- `operation_type` (enum): Tipo de operação
- `origin_state` (string): UF de origem
- `destination_state` (string): UF de destino
- `cfop` (string): Código CFOP
- `icms_cst` (string): CST do ICMS
- `icms_rate` (decimal): Alíquota de ICMS
- `icms_reduction` (decimal): Redução de base de ICMS
- `icms_st_rate` (decimal): Alíquota de ICMS-ST
- `ipi_cst` (string): CST do IPI
- `ipi_rate` (decimal): Alíquota de IPI
- `pis_cst` (string): CST do PIS
- `pis_rate` (decimal): Alíquota de PIS
- `cofins_cst` (string): CST do COFINS
- `cofins_rate` (decimal): Alíquota de COFINS
- `fcp_rate` (decimal): Alíquota do FCP
- `is_active` (boolean): Status ativo/inativo
- `created_at` (datetime): Data de criação
- `updated_at` (datetime): Data de última atualização

**Relacionamentos**:
- Pertence a uma Holding (`belongs-to`)
- Pertence a uma Branch (`belongs-to`)

**Regras de Negócio**:
- Regras são específicas por branch
- A combinação de parâmetros deve ser única
- Validações específicas para cada tipo de imposto
- Regras podem ser clonadas e modificadas

## Diagramas Adicionais

### Hierarquia Organizacional
```
Tenant
  ├── Holding 1
  │     ├── Branch 1.1 (SP)
  │     │     └── IE: SP (Normal, ST), RJ (Normal), MG (Normal)
  │     ├── Branch 1.2 (RJ)
  │     │     └── IE: RJ (Normal, ST), SP (Normal)
  │     └── Branch 1.3 (MG)
  │           └── IE: MG (Normal, Rural)
  └── Holding 2
        ├── Branch 2.1 (BA)
        │     └── IE: BA (Normal, ST)
        └── Branch 2.2 (PE)
              └── IE: PE (Normal), AL (Normal, ST)
```

### Fluxo de Pedidos
```
                  ┌────────────────────┐
                  │    Pedido Base     │
                  └────────────────────┘
                           │
          ┌────────────────┼────────────────┐
          │                │                │
┌─────────▼─────────┐ ┌────▼─────┐ ┌────────▼─────────┐
│ Pedido de Venda   │ │ Compra   │ │ Transferência    │
└─────────┬─────────┘ └────┬─────┘ └────────┬─────────┘
          │                │                │
    ┌─────▼─────┐    ┌─────▼─────┐    ┌─────▼─────┐
    │ Privado   │    │ Estoque   │    │ Nota de   │
    └─────┬─────┘    │  (+)      │    │ Transf.   │
          │          └───────────┘    └─────┬─────┘
    ┌─────▼─────┐                     ┌─────▼─────┐
    │ Público   │                     │ Estoque   │
    │(Licitação)│                     │ Origem(-) │
    └─────┬─────┘                     │ Destino(+)│
          │                           └───────────┘
    ┌─────▼─────┐
    │  Nota     │
    │  Fiscal   │
    └─────┬─────┘
          │
    ┌─────▼─────┐
    │ Estoque   │
    │   (-)     │
    └───────────┘
```

### Rastreabilidade de Produto
```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ Pedido de   │     │ Nota Fiscal │     │ Entrada no  │
│ Compra      │────>│ de Entrada  │────>│ Estoque     │
└─────────────┘     └─────────────┘     └──────┬──────┘
                                               │
                                         ┌─────▼─────┐
                                         │ Holding   │
                                         │Consolidação│
                                         └─────┬─────┘
                                               │
┌─────────────┐     ┌─────────────┐     ┌──────▼──────┐
│ Baixa no    │     │ Nota Fiscal │     │ Pedido de   │
│ Estoque     │<────│ de Saída    │<────│ Venda       │
└─────────────┘     └─────────────┘     └─────────────┘
```

## Considerações de Implementação

### Prefixos e Identificadores
- Tenant: TN + número sequencial
- Holding: HL + número sequencial
- Branch: BR + número sequencial
- Pedidos:
  - Venda Privada: PV + código da branch + sequencial
  - Venda Pública: PP + código da branch + sequencial
  - Compra: PC + código da branch + sequencial
  - Transferência: PT + código da branch + sequencial
- Produtos: Prefixo da categoria + sequencial

### Bancos de Dados
- Tabelas principais: PostgreSQL
- Cache: Redis
- Pesquisa: Elasticsearch (opcional para bases grandes)

### Armazenamento
- Documentos e anexos: Amazon S3
- Estrutura de pastas:
  ```
  holdings/{holding_id}/
    branches/{branch_id}/
      orders/{order_id}/
      invoices/{invoice_id}/
      products/{product_id}/
  ```

### Interfaces
- Dashboard principal adaptativo por nível (tenant, holding, branch)
- Visões específicas por tipo de pedido
- Interfaces de configuração fiscal separadas por escopo
- Relatórios customizáveis por nível