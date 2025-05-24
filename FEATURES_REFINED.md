# SaaS de Controle de Pedidos Públicos, Privados e Licitações (Refinado)

## Visão Geral Refinada

Sistema SaaS multi-tenant para gerenciamento completo de pedidos públicos (licitações), pedidos privados, compras e regras fiscais, com uma hierarquia clara de Tenant → Holding → Branch (Filial), permitindo uma estrutura organizacional mais precisa e completa.

## Estrutura Organizacional Refinada

### Tenant
- Representa a unidade lógica de isolamento no SaaS
- Contém configurações gerais da instância
- Pode ter múltiplas Holdings
- Exemplo: Instância para "Grupo ABC" no SaaS

### Holding
- Representa uma empresa controladora ou grupo empresarial
- Pertence a um Tenant específico
- Controla múltiplas Branches (filiais)
- Possui configurações globais para todas as filiais
- Exemplo: "ABC Participações S.A."

### Branch (Filial)
- Representa uma unidade operacional específica
- Pertence a uma Holding
- Pode ter múltiplas inscrições estaduais (até 27, uma por UF)
- Opera com autonomia fiscal e operacional
- Emite documentos fiscais próprios
- Exemplo: "ABC São Paulo", "ABC Rio de Janeiro"

## Tipos de Pedidos e Documentos

### Pedidos Públicos (Licitações)
- **Características** (mantidas conforme original)
- **Fluxo** (mantido conforme original)
- **Rastreabilidade**: 
  - Conexão direta com nota fiscal de saída
  - Vinculação com empenhos e documentos oficiais
  - Acompanhamento de prazos de atendimento

### Pedidos Privados
- **Características** (mantidas conforme original)
- **Fluxo** (mantido conforme original)
- **Rastreabilidade**:
  - Conexão com nota fiscal de saída
  - Possibilidade de vinculação com pedido de compra do cliente

### Pedidos de Compra
- **Características**:
  - Registra compras realizadas pela empresa
  - Permite acompanhamento do recebimento
  - Vincula-se com nota fiscal de entrada
  - Base para geração de contas a pagar

- **Fluxo**:
  1. Registro da necessidade de compra
  2. Cotação com fornecedores (opcional)
  3. Emissão do pedido de compra
  4. Acompanhamento de entrega
  5. Recebimento e conferência
  6. Vinculação com nota fiscal de entrada
  7. Atualização de estoque

### Pedidos de Transferência
- **Características**:
  - Movimentação entre filiais da mesma holding
  - Documentação fiscal específica (nota de transferência)
  - Controle de estoque em trânsito
  - Regras fiscais específicas

- **Fluxo**:
  1. Solicitação de transferência pela filial destino
  2. Aprovação pela filial origem
  3. Emissão da nota fiscal de transferência
  4. Rastreamento do trânsito
  5. Recebimento e conferência pela filial destino
  6. Atualização de estoques

## Rastreabilidade e Interconexão de Documentos

### Cadeia de Documentos
- **Pedido de Compra → Nota Fiscal de Entrada → Estoque → Pedido de Venda → Nota Fiscal de Saída**
  - Rastreabilidade completa do ciclo de produtos
  - Possibilidade de consultar origem/destino de cada item
  - Histórico completo de movimentações

### Referências Cruzadas
- Cada documento mantém referências a documentos relacionados
- Pedidos de venda podem referenciar lotes recebidos em compras específicas
- Possibilidade de rastrear lotes e séries de produtos

## Regras Fiscais (conforme documento original)

## Arquitetura do Sistema Refinada

### Entidades Principais Refinadas

#### Tenant
```
- id (ULID)
- name
- domain
- status
- settings (JSON)
- created_at
- updated_at
```

#### Holding
```
- id (ULID)
- tenant_id
- name
- legal_name
- document (CNPJ)
- logo
- settings (JSON)
- created_at
- updated_at
```

#### Branch (Filial)
```
- id (ULID)
- holding_id
- name
- legal_name
- document (CNPJ)
- main_state (UF principal)
- address
- city
- zip_code
- tax_regime (Normal, Simples, etc)
- is_active
- created_at
- updated_at
```

#### BranchStateRegistration
```
- id (ULID)
- branch_id
- state (UF)
- state_registration (IE)
- is_main (boolean - indica se é a IE principal da filial)
- is_active
- created_at
- updated_at
```

#### User
```
- id (ULID)
- tenant_id
- holding_id (nullable)
- branch_id (nullable)
- name
- email
- password
- role_id
- status
- created_at
- updated_at
```

#### Order (Pedido Base)
```
- id (ULID)
- prefix (PV, PC, PT - identificador do tipo)
- order_number (sequencial por prefixo e branch)
- branch_id (filial emissora)
- type (public, private, purchase, transfer)
- entity_type (customer, supplier, branch)
- entity_id (id do cliente, fornecedor ou filial)
- creator_user_id
- assignee_user_id
- status
- order_date
- delivery_date
- payment_terms
- shipping_method
- notes
- total_amount
- total_tax
- related_order_id (para pedidos relacionados)
- created_at
- updated_at
```

#### PublicOrder (Extensão para Pedidos Públicos)
```
- order_id
- bidding_process_number
- bidding_type
- contract_number
- government_entity
- requisition_number
- empenho_number
- has_required_attachments
```

#### PurchaseOrder (Extensão para Pedidos de Compra)
```
- order_id
- supplier_reference
- expected_delivery_date
- delivery_address
- payment_condition
- buyer_user_id
- approved_by_user_id
- approval_date
```

#### TransferOrder (Extensão para Pedidos de Transferência)
```
- order_id
- source_branch_id
- destination_branch_id
- reason
- transfer_type (reposição, movimentação, etc)
- authorized_by_user_id
- authorization_date
```

#### OrderItem
```
- id (ULID)
- order_id
- product_id
- quantity
- unit_price
- discount
- tax_amount
- tax_rules (JSON)
- subtotal
- purchase_order_item_id (para rastreabilidade)
- lot_number
- serial_number
- created_at
- updated_at
```

#### File
```
- id (ULID)
- holding_id
- branch_id
- entity_type (order, invoice, purchase, etc)
- entity_id
- user_id
- file_name
- original_name
- file_type
- file_size
- file_path (S3 path)
- description
- category (ordem_fornecimento, empenho, invoice, etc)
- created_at
- updated_at
```

#### TaxRule
```
- id (ULID)
- holding_id
- branch_id
- name
- description
- contributor_type (contribuinte, nao_contribuinte, remessa)
- operation_type (venda, transferencia, demonstracao, etc)
- origin_state
- destination_state
- cfop
- icms_cst
- icms_rate
- icms_reduction
- icms_st_rate
- ipi_cst
- ipi_rate
- pis_cst
- pis_rate
- cofins_cst
- cofins_rate
- fcp_rate
- is_active
- created_at
- updated_at
```

#### Product (SKU)
```
- id (ULID)
- holding_id
- prefix (P, S, etc - para categorização)
- sku
- name
- description
- price
- cost
- ncm
- origin (nacional, importado)
- unit
- weight
- dimensions
- is_active
- created_at
- updated_at
```

#### Inventory
```
- id (ULID)
- branch_id
- product_id
- quantity
- reserved_quantity
- available_quantity
- min_quantity
- max_quantity
- last_purchase_date
- last_purchase_price
- last_sale_date
- updated_at
```

#### InventoryMovement
```
- id (ULID)
- branch_id
- product_id
- movement_type (entrada, saída, transferência, ajuste)
- quantity
- reference_type (order, purchase, transfer, adjustment)
- reference_id
- lot_number
- serial_number
- unit_cost
- user_id
- notes
- created_at
```

#### Invoice (Nota Fiscal)
```
- id (ULID)
- branch_id
- invoice_type (entrada, saída)
- invoice_number
- invoice_series
- invoice_key (chave da NF-e)
- entity_type (customer, supplier, branch)
- entity_id
- order_id (pedido relacionado)
- issue_date
- operation_date
- total_amount
- total_products
- total_tax
- status
- xml_path (S3)
- pdf_path (S3)
- created_at
- updated_at
```

### Relacionamentos Principais Refinados

1. **Tenant -> Holdings**: One-to-Many
2. **Holding -> Branches**: One-to-Many
3. **Branch -> BranchStateRegistrations**: One-to-Many
4. **Holding -> Users**: One-to-Many
5. **Branch -> Users**: One-to-Many
6. **Branch -> Orders**: One-to-Many
7. **Order -> OrderItems**: One-to-Many
8. **Order -> Files**: One-to-Many
9. **Order -> PublicOrder/PurchaseOrder/TransferOrder**: One-to-One (polimórfico)
10. **Branch -> TaxRules**: One-to-Many
11. **Holding -> Products**: One-to-Many
12. **Branch -> Inventory**: One-to-Many (por produto)
13. **Branch -> InventoryMovements**: One-to-Many
14. **Order -> Invoice**: One-to-Many
15. **OrderItem -> OrderItem**: Relacionamento próprio (para rastreabilidade)

## Fluxos de Trabalho Refinados

### Ciclo Completo de Produto

1. **Aquisição de Produtos**
   - Criação de pedido de compra
   - Recebimento com nota fiscal de entrada
   - Atualização de estoque (entrada)
   - Registro de lote/série

2. **Venda de Produtos**
   - Criação de pedido de venda (público ou privado)
   - Reserva de estoque
   - Faturamento com nota fiscal de saída
   - Atualização de estoque (saída)
   - Referência ao lote/série de origem

3. **Transferência entre Filiais**
   - Criação de pedido de transferência
   - Emissão de nota fiscal de transferência
   - Baixa no estoque da filial origem
   - Estoque em trânsito
   - Entrada no estoque da filial destino

### Rastreabilidade Fiscal

1. **Nota Fiscal de Entrada**
   - Vinculação ao pedido de compra
   - Registro de informações fiscais
   - Armazenamento de XML/PDF
   - Cálculo e validação de impostos

2. **Nota Fiscal de Saída**
   - Vinculação ao pedido de venda
   - Aplicação automática de regras fiscais
   - Geração de XML para autorização
   - Armazenamento de XML/PDF
   - Histórico de transmissão

## Aprimoramentos no Gerenciamento de Estoque

### Controle por Filial
- Cada filial possui seu próprio estoque
- Visibilidade do estoque de outras filiais
- Transferências entre filiais rastreáveis

### Rastreabilidade por Lote/Série
- Produtos podem ser controlados por lote ou número de série
- Rastreabilidade desde a compra até a venda
- Histórico completo de movimentação
- Suporte a recall e garantias

### Reservas de Estoque
- Pedidos pendentes reservam automaticamente o estoque
- Priorização de pedidos por criticidade
- Alerta quando estoque disponível não é suficiente
- Sugestão de transferência entre filiais

## Considerações para Implementação

### Prefixos e Numeração
- Pedidos de Venda: PV + sequencial por filial
- Pedidos de Compra: PC + sequencial por filial
- Pedidos de Transferência: PT + sequencial por filial
- Produtos: Prefixo por categoria + sequencial

### Transações e Integridade
- Operações de estoque em transações atômicas
- Consistência entre documentos relacionados
- Log detalhado de todas as operações

### Arquitetura de Serviços
- Serviço de Estoque separado do serviço de Pedidos
- Serviço Fiscal independente
- Integração via eventos para desacoplamento

### Considerações de Performance
- Otimização para grandes volumes de dados
- Índices específicos para consultas frequentes
- Estratégia de particionamento para histórico
- Cache de regras fiscais e configurações