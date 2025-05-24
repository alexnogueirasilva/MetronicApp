# Tarefas de Desenvolvimento Refinadas do SaaS de Pedidos e Licitações

Este documento define as tarefas específicas para implementação do sistema de gerenciamento de pedidos públicos,
privados, compras, transferências e regras fiscais, seguindo a estrutura refinada com Tenant → Holding → Branch.

## Fase 1: Estrutura Base Refinada

### 1.1 Configuração do Projeto

- [X] **Inicialização do Projeto Laravel**
    - Configurar Laravel 10+
    - Configurar banco de dados PostgreSQL
    - Implementar sistema de autenticação
    - Configurar interfaces administrativas base

- [X] **Implementação da Estrutura Multi-tenant**
    - Criar modelos e migrações para Tenant
    - Implementar middleware de separação de tenants
    - Configurar conexões de banco específicas por tenant
    - Implementar escopo global para isolamento de dados

### 1.2 Estrutura Organizacional

- [ ] **Modelo de Dados para Holdings**
    - Criar migração para tabela holdings
    - Implementar modelo Holding com relacionamentos
    - Adicionar suporte a configurações específicas por holding
    - Desenvolver seed para holdings de teste

- [ ] **Modelo de Dados para Branches (Filiais)**
    - Criar migração para tabela branches
    - Implementar modelo Branch com relacionamentos
    - Criar migração para branch_state_registrations (inscrições estaduais)
    - Desenvolver seed para branches e inscrições estaduais de teste

- [ ] **Interface Administrativa para Holdings e Branches**
    - Desenvolver tela de listagem de holdings
    - Implementar formulário de criação/edição de holding
    - Desenvolver tela de listagem de branches por holding
    - Implementar formulário de branch com suporte a múltiplas inscrições estaduais
    - Adicionar validações específicas (CNPJ, IE)
    - Desenvolver painel de detalhes da branch

### 1.3 Gestão de Usuários e Permissões

- [ ] **Expansão do Sistema de Usuários**
    - Adaptar modelo User para suporte multi-tenant
    - Adicionar relacionamentos com Holding e Branch
    - Implementar soft deletes e logs de atividade

- [ ] **Sistema de Papéis e Permissões**
    - Implementar modelos Role e Permission
    - Configurar permissões específicas para diferentes fluxos
    - Desenvolver interface de atribuição de papéis
    - Implementar middleware de verificação de permissões
    - Adicionar suporte a permissões por holding/branch

### 1.4 Estrutura de Produtos (SKUs)

- [ ] **Modelo de Dados para Produtos**
    - Criar migração para tabela products
    - Implementar modelo Product com suporte a holdings
    - Adicionar sistema de prefixos para categorização
    - Adicionar campos específicos para tributação (NCM, origem)

- [ ] **Interface de Gerenciamento de Produtos**
    - Desenvolver tela de listagem com filtros
    - Implementar formulário de criação/edição
    - Adicionar suporte a importação em massa (CSV)
    - Desenvolver interface de visualização detalhada
    - Implementar sistema de busca avançada

## Fase 2: Sistema de Estoque e Movimentações

### 2.1 Estrutura de Estoque

- [ ] **Modelo de Dados para Inventário**
    - Criar migração para tabela inventories
    - Implementar modelo Inventory com relacionamentos
    - Criar migração para inventory_movements
    - Implementar modelo InventoryMovement

- [ ] **Serviço de Gerenciamento de Estoque**
    - Desenvolver service para operações de estoque
    - Implementar lógica de reserva de estoque
    - Criar sistema de alertas para estoque mínimo
    - Desenvolver lógica de rastreamento de lotes/séries

- [ ] **Interface de Gerenciamento de Estoque**
    - Criar dashboard de estoque por branch
    - Implementar visualização de movimentações
    - Desenvolver interface para ajustes manuais
    - Adicionar relatórios de posição de estoque

### 2.2 Rastreabilidade

- [ ] **Sistema de Lotes e Séries**
    - Expandir modelo de produto para suporte a lotes/séries
    - Implementar regras de rastreabilidade
    - Criar interface de consulta de histórico de lote/série
    - Desenvolver relatórios de rastreabilidade

- [ ] **Integração com Pedidos e Notas Fiscais**
    - Implementar serviço de vínculo entre documentos
    - Criar interface de visualização de documentos relacionados
    - Desenvolver sistema de referências cruzadas

## Fase 3: Pedidos e Documentos

### 3.1 Estrutura Base de Pedidos

- [ ] **Modelo de Dados para Pedidos**
    - Criar migração para tabela orders
    - Implementar modelo Order com sistema de prefixos
    - Adicionar campo type para diferenciar tipos (público, privado, compra, transferência)
    - Criar estados e transições de pedidos
    - Implementar sistema de numeração sequencial por branch e tipo

- [ ] **Modelo de Dados para Itens de Pedido**
    - Criar migração para tabela order_items
    - Implementar modelo OrderItem com relacionamentos
    - Adicionar campos para rastreabilidade (lote, série)
    - Preparar estrutura para regras fiscais (JSON)

### 3.2 Pedidos de Venda Privados

- [ ] **Fluxo de Criação de Pedido Privado**
    - Desenvolver wizard de criação de pedido
    - Implementar seleção de cliente
    - Criar interface de adição de produtos com verificação de estoque
    - Adicionar cálculos automáticos de valores
    - Implementar integração com reserva de estoque

- [ ] **Gestão de Pedidos Privados**
    - Criar dashboard de pedidos
    - Implementar filtros por status, data, cliente
    - Desenvolver visualização detalhada de pedido
    - Adicionar ações de edição, cancelamento e faturamento

### 3.3 Pedidos de Venda Públicos (Licitações)

- [ ] **Modelo de Dados para Pedidos Públicos**
    - Criar migração para tabela public_orders
    - Implementar modelo PublicOrder como extensão
    - Adicionar campos específicos de licitações
    - Configurar validações específicas

- [ ] **Fluxo de Criação Específico**
    - Adaptar wizard para pedidos públicos
    - Implementar campos específicos (processo licitatório, etc.)
    - Adicionar step de upload obrigatório
    - Criar interface de designação de responsável

- [ ] **Gestão Especializada**
    - Desenvolver dashboard específico para licitações
    - Implementar alertas de prazos
    - Criar visualização de histórico de atendimento
    - Adicionar marcadores de prioridade e criticidade

### 3.4 Pedidos de Compra

- [ ] **Modelo de Dados para Pedidos de Compra**
    - Criar migração para tabela purchase_orders
    - Implementar modelo PurchaseOrder como extensão
    - Adicionar campos específicos para compras
    - Configurar fluxo de aprovação

- [ ] **Fluxo de Compras**
    - Desenvolver wizard de criação de pedido de compra
    - Implementar seleção de fornecedor
    - Criar interface de adição de produtos com histórico de preços
    - Adicionar workflow de aprovação
    - Implementar acompanhamento de recebimento

- [ ] **Recebimento de Mercadorias**
    - Criar interface de recebimento parcial/total
    - Implementar conferência de produtos
    - Desenvolver vinculação com nota fiscal de entrada
    - Adicionar entrada automática em estoque

### 3.5 Pedidos de Transferência

- [ ] **Modelo de Dados para Transferências**
    - Criar migração para tabela transfer_orders
    - Implementar modelo TransferOrder como extensão
    - Adicionar campos específicos para transferências
    - Configurar fluxo de aprovação

- [ ] **Fluxo de Transferência**
    - Desenvolver wizard de criação de transferência
    - Implementar seleção de branch de origem/destino
    - Criar interface de seleção de produtos com verificação de estoque
    - Adicionar workflow de aprovação
    - Implementar controle de estoque em trânsito

## Fase 4: Documentos Fiscais

### 4.1 Notas Fiscais

- [ ] **Modelo de Dados para Notas Fiscais**
    - Criar migração para tabela invoices
    - Implementar modelo Invoice com relacionamentos
    - Adicionar suporte a armazenamento de XML/PDF
    - Implementar vinculação com pedidos

- [ ] **Emissão de Nota Fiscal**
    - Desenvolver interface de emissão a partir de pedido
    - Implementar preenchimento automático de dados fiscais
    - Criar visualização prévia do documento
    - Adicionar integração com API de emissão

- [ ] **Recebimento de Nota Fiscal**
    - Criar interface de entrada manual de NF
    - Implementar importação de XML
    - Desenvolver vinculação com pedido de compra
    - Adicionar validação de dados fiscais

### 4.2 Upload e Gestão de Arquivos

- [ ] **Configuração de Armazenamento S3**
    - Configurar conexão com Amazon S3
    - Implementar drivers de disco no Laravel
    - Criar estrutura de pastas organizada (holding/branch/tipo)

- [ ] **Sistema de Upload de Arquivos**
    - Criar migração para tabela files
    - Implementar modelo File com relacionamentos para holding/branch
    - Desenvolver componente de upload com preview
    - Adicionar validações de tipos e tamanhos

- [ ] **Interface de Gestão de Arquivos**
    - Criar visualização de arquivos por entidade
    - Implementar download e visualização
    - Adicionar categorização de arquivos
    - Desenvolver controle de permissões para arquivos

## Fase 5: Regras Fiscais

### 5.1 Estrutura de Dados Fiscais

- [ ] **Modelo de Regras Fiscais**
    - Criar migração para tabela tax_rules
    - Implementar modelo TaxRule com relacionamentos para holding/branch
    - Adicionar campos para todos os componentes fiscais
    - Criar seeds com regras padrão por UF

- [ ] **Cadastro de Regras**
    - Desenvolver interface de gestão de regras
    - Implementar formulário completo com validações
    - Adicionar suporte a clonagem e importação
    - Criar visualização hierárquica por tipo

### 5.2 Motor de Cálculo Fiscal

- [ ] **Implementação do Core de Cálculos**
    - Desenvolver serviço de cálculo de impostos
    - Implementar regras para ICMS por UF
    - Adicionar cálculos de IPI, PIS, COFINS
    - Criar tratamento para FCP e DIFAL

- [ ] **Integração com Pedidos**
    - Adaptar OrderItem para incluir regras fiscais
    - Implementar seleção automática de regra aplicável
    - Adicionar visualização detalhada de tributação
    - Criar resumo fiscal no pedido

### 5.3 Configuração por Branch

- [ ] **Perfis Fiscais por Branch**
    - Estender modelo Branch para configurações fiscais
    - Implementar padrões por UF da filial
    - Adicionar tratamento de exceções
    - Criar interface de configuração

- [ ] **Validações Específicas**
    - Implementar validação de combinações inválidas
    - Criar alertas para inconsistências
    - Desenvolver logs de alterações fiscais
    - Adicionar suporte a mudanças em lote

## Fase 6: Dashboards e Relatórios

### 6.1 Dashboards por Nível

- [ ] **Dashboard para Tenant**
    - Criar visão consolidada de todas as holdings
    - Implementar gráficos comparativos
    - Adicionar indicadores de desempenho global
    - Desenvolver alertas de nível tenant

- [ ] **Dashboard para Holding**
    - Criar visão consolidada de todas as branches
    - Implementar gráficos comparativos entre branches
    - Adicionar indicadores de desempenho por branch
    - Desenvolver alertas de nível holding

- [ ] **Dashboard para Branch**
    - Criar visão detalhada da operação da branch
    - Implementar gráficos de vendas, compras e estoque
    - Adicionar indicadores de desempenho operacional
    - Desenvolver alertas específicos de branch

### 6.2 Dashboards por Função

- [ ] **Dashboard para Vendas**
    - Criar visão focada em pedidos e faturamento
    - Implementar gráficos de performance de vendas
    - Adicionar acompanhamento de metas
    - Desenvolver alertas de oportunidades

- [ ] **Dashboard para Compras**
    - Criar visão focada em pedidos de compra e recebimentos
    - Implementar gráficos de performance de fornecedores
    - Adicionar análise de custos
    - Desenvolver alertas de prazos de entrega

- [ ] **Dashboard para Licitações**
    - Criar visão específica para pedidos públicos
    - Implementar acompanhamento de prazos críticos
    - Adicionar indicadores de conformidade documental
    - Desenvolver alertas de vencimento de contratos

### 6.3 Relatórios Avançados

- [ ] **Gerador de Relatórios**
    - Implementar engine flexível de relatórios
    - Criar templates pré-definidos por área
    - Adicionar filtros avançados
    - Desenvolver agendamento de relatórios

- [ ] **Relatórios Fiscais**
    - Criar relatórios específicos por regime tributário
    - Implementar exportação para integração contábil
    - Adicionar validações fiscais
    - Desenvolver visualização de inconsistências

## Fase 7: Integrações

### 7.1 Integração com Sistemas Externos

- [ ] **API para Integração Contábil/ERP**
    - Desenvolver endpoints para sincronização de documentos fiscais
    - Implementar autenticação e segurança
    - Criar logs de integração
    - Adicionar tratamento de erros e reconciliação

- [ ] **Integração com Sistemas de Emissão de NF-e**
    - Implementar comunicação com provedores de NF-e
    - Desenvolver monitoramento de status
    - Adicionar tratamento de rejeições
    - Criar rotina de contingência

### 7.2 Webhooks e Notificações

- [ ] **Sistema de Webhooks**
    - Implementar mecanismo de registro de webhooks
    - Desenvolver disparo de eventos
    - Adicionar monitoramento de entregas
    - Criar interface de configuração

- [ ] **Notificações Multicanal**
    - Implementar notificações por email
    - Desenvolver notificações in-app
    - Adicionar suporte a SMS (opcional)
    - Criar preferências de notificação por usuário

## Considerações para Implementação

### Padrões de Código

- Utilizar tipagem estrita em PHP
- Seguir PSR-12 para formatação
- Implementar testes automatizados para cada componente
- Documentar APIs com OpenAPI/Swagger

### Arquitetura

- Utilizar padrão Repository+Service para lógica de negócio
- Implementar Command Pattern para operações complexas
- Utilizar Event Sourcing para rastreabilidade de mudanças de estado
- Separar lógica de negócio em services especializados

### Interface

- Utilizar Mary UI para componentes
- Implementar componentes Livewire para interatividade
- Garantir responsividade em todos os dispositivos
- Otimizar carregamento para conexões lentas

### Infraestrutura

- Configurar CI/CD para ambiente de desenvolvimento
- Implementar monitoramento de erros e performance
- Configurar backups automatizados
- Preparar documentação de deploy e manutenção

### Estratégia de Banco de Dados

- Utilizar índices apropriados para consultas frequentes
- Implementar particionamento para tabelas de histórico
- Utilizar stored procedures para operações críticas
- Configurar réplicas para leitura de relatórios
