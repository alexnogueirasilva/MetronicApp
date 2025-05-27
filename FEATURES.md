# SaaS de Controle de Pedidos Públicos, Privados e Licitações

## Visão Geral

Sistema SaaS para gerenciamento completo de pedidos públicos (licitações), pedidos privados e regras fiscais, com
suporte a múltiplos tenants (holdings) e filiais, com regras fiscais específicas por unidade.

## Estrutura de Tenants

### Tenant (Holding)

- Cada tenant representa uma holding/empresa principal
- Um tenant pode ter múltiplas filiais (companies)
- Administração centralizada com visão geral de todas as filiais

### Companies (Filiais)

- Cada filial está vinculada a um tenant
- Possui configurações fiscais próprias baseadas na sua localização (UF)
- Pode operar com pedidos públicos e/ou privados
- Possui equipe própria para atendimento de pedidos

## Tipos de Pedidos

### Pedidos Públicos (Licitações)

- **Características**:
    - Vinculados a processos licitatórios
    - Requerem documentação oficial (ordem de fornecimento)
    - Precisam de usuário designado para atendimento (diferente do cadastrante)
    - Valores e condições pré-estabelecidos conforme edital
    - Prazos de entrega rigorosos com penalidades por atraso
    - Múltiplos anexos obrigatórios (ordem de fornecimento, empenho, etc.)

- **Fluxo**:
    1. Cadastro inicial do pedido com dados da licitação
    2. Upload da ordem de fornecimento (obrigatório)
    3. Designação de responsável pelo atendimento
    4. Adição de produtos (SKUs) conforme edital
    5. Aplicação automática de regras fiscais baseadas na filial emissora
    6. Geração de documentos fiscais
    7. Acompanhamento de entrega
    8. Arquivamento com toda documentação

### Pedidos Privados

- **Características**:
    - Processo simplificado
    - O cadastrante é automaticamente o responsável pelo atendimento
    - Anexos opcionais
    - Maior flexibilidade em condições comerciais

- **Fluxo**:
    1. Cadastro do pedido com dados do cliente
    2. Adição de produtos (SKUs)
    3. Aplicação automática de regras fiscais baseadas na filial emissora
    4. Geração de documentos fiscais
    5. Acompanhamento de entrega
    6. Arquivamento

## Regras Fiscais

### Categorias de Tributação

1. **Contribuintes de ICMS**
    - Empresas com inscrição estadual ativa
    - Aplicação de ICMS conforme regras interestaduais
    - CSTs específicos

2. **Não Contribuintes**
    - Pessoas físicas
    - Empresas isentas
    - Entidades governamentais sem IE
    - Aplicação de ICMS interno (destino)

3. **Remessas**
    - Transferências entre filiais
    - Remessas para demonstração
    - Remessas para conserto
    - CFOPs específicos por tipo de operação

### Componentes Fiscais

- **ICMS**
    - Alíquotas por estado (27 UFs diferentes)
    - Regras de origem/destino
    - Reduções de base de cálculo
    - Diferencial de alíquota (DIFAL)
    - Substituição tributária

- **IPI**
    - Alíquotas por NCM
    - Isenções
    - Suspensões

- **PIS/COFINS**
    - Regimes cumulativo e não-cumulativo
    - Alíquotas padrão e diferenciadas

- **CFOP**
    - Códigos específicos para cada operação
    - Operações internas vs. interestaduais
    - Vendas vs. remessas

- **FCP (Fundo de Combate à Pobreza)**
    - Percentuais adicionais por UF

## Arquitetura do Sistema

### Entidades Principais

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

#### Company (Filial)

```
- id (ULID)
- tenant_id
- name
- cnpj
- state_registration
- state (UF)
- city
- address
- tax_regime (Normal, Simples, etc)
- created_at
- updated_at
```

#### User

```
- id (ULID)
- tenant_id
- company_id (nullable)
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
- tenant_id
- company_id
- type (public, private)
- customer_id
- creator_user_id
- assignee_user_id
- status
- order_number
- order_date
- delivery_date
- payment_terms
- shipping_method
- notes
- total_amount
- total_tax
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
- created_at
- updated_at
```

#### File

```
- id (ULID)
- tenant_id
- company_id
- order_id (nullable)
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
- tenant_id
- company_id
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
- tenant_id
- company_id (nullable - pode ser compartilhado entre filiais)
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

### Relacionamentos Principais

1. **Tenant -> Companies**: One-to-Many
2. **Tenant -> Users**: One-to-Many
3. **Company -> Users**: One-to-Many
4. **Company -> Orders**: One-to-Many
5. **Order -> OrderItems**: One-to-Many
6. **Order -> Files**: One-to-Many
7. **Order -> PublicOrder**: One-to-One (para pedidos públicos)
8. **Company -> TaxRules**: One-to-Many
9. **Company -> Products**: One-to-Many (ou Many-to-Many se produtos compartilhados)

## Fluxos de Trabalho

### Criação de Pedido Público

1. Usuário seleciona "Novo Pedido Público"
2. Preenche dados básicos do cliente/órgão público
3. Uploads obrigatórios (ordem de fornecimento)
4. Designa responsável pelo atendimento
5. Adiciona produtos ao pedido
    - Para cada produto, aplicar regras fiscais automaticamente baseadas em:
        - UF da filial emissora
        - UF do destinatário
        - Tipo de contribuinte
        - NCM do produto
6. Sistema calcula totais, impostos e gera documentos
7. Pedido segue para processamento/faturamento

### Criação de Pedido Privado

1. Usuário seleciona "Novo Pedido Privado"
2. Preenche dados do cliente
3. Uploads opcionais
4. Adiciona produtos ao pedido
    - Aplicação automática de regras fiscais
5. Sistema calcula totais, impostos e gera documentos
6. Pedido segue para processamento/faturamento

### Aplicação de Regras Fiscais

1. Ao adicionar um produto ao pedido:
    - Identifica filial emissora
    - Identifica UF de destino
    - Verifica tipo de cliente (contribuinte, não contribuinte)
    - Obtém NCM do produto
2. Consulta tabela de regras fiscais para encontrar regra aplicável
3. Aplica CFOP correto
4. Calcula ICMS conforme alíquota da regra
    - Aplica redução de base se houver
    - Calcula DIFAL se aplicável
    - Adiciona FCP se aplicável
5. Calcula IPI conforme NCM/tabela
6. Calcula PIS/COFINS conforme regime

## Requisitos Técnicos

### Armazenamento de Arquivos

- Todos os arquivos (ordens de fornecimento, empenhos, notas fiscais) serão armazenados no Amazon S3
- Estrutura de pastas organizada por tenant/company/order
- Metadados armazenados no banco de dados

### Segurança

- Separação completa de dados entre tenants
- Permissões granulares por empresa e função
- Auditoria de todas as ações (log de atividades)

### Interface

- Dashboard adaptado para cada tipo de usuário
- Visualizações específicas para pedidos públicos vs. privados
- Filtros avançados por status, tipo, data, cliente, etc.
- Relatórios consolidados e por filial

## Roadmap de Desenvolvimento

### Fase 1: Estrutura Base

- Implementação do sistema multi-tenant
- Gerenciamento de empresas (filiais)
- Gerenciamento de usuários e permissões
- Estrutura básica de produtos (SKUs)

### Fase 2: Pedidos Privados

- Criação e gestão de pedidos privados
- Upload de arquivos (integração S3)
- Fluxo básico de processamento

### Fase 3: Pedidos Públicos

- Extensão para suporte a licitações
- Fluxo de designação de responsáveis
- Documentação obrigatória
- Validações específicas

### Fase 4: Regras Fiscais

- Implementação do motor de regras fiscais
- Suporte a diferentes UFs
- Cálculos automáticos de impostos
- Configuração por filial

### Fase 5: Relatórios e Dashboards

- Visão consolidada por tenant
- Relatórios por filial
- Análises de desempenho
- Exportação de dados

## Considerações Importantes

1. **Complexidade Fiscal**
    - O sistema deve ser flexível para acomodar mudanças frequentes na legislação fiscal
    - Permitir cadastro/atualização fácil de regras fiscais

2. **Escalabilidade**
    - Projetar para suportar muitos tenants com múltiplas filiais
    - Otimizar consultas de banco de dados para grandes volumes

3. **Experiência do Usuário**
    - Fluxos claros e diferentes para pedidos públicos vs. privados
    - Minimizar etapas para tarefas comuns
    - Validações em tempo real para reduzir erros

4. **Integrações Futuras**
    - Preparar APIs para integração com sistemas contábeis/ERP
    - Possibilidade de integração com sistemas governamentais (para licitações)
    - Integração com sistemas de emissão de NF-e
