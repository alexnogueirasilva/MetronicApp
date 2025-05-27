# Tarefas de Desenvolvimento do SaaS de Pedidos e Licitações

Este documento define as tarefas específicas para implementação do sistema de gerenciamento de pedidos públicos,
privados e regras fiscais, seguindo o roadmap estabelecido no FEATURES.md.

## Fase 1: Estrutura Base

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

### 1.2 Gestão de Empresas (Filiais)

- [ ] **Modelo de Dados para Empresas**
    - Criar migração para tabela companies
    - Implementar modelo Company com relacionamentos
    - Desenvolver seed para empresas de teste

- [ ] **Interface Administrativa para Empresas**
    - Desenvolver tela de listagem de empresas
    - Implementar formulário de criação/edição
    - Adicionar validações específicas (CNPJ, IE)
    - Desenvolver painel de detalhes da empresa

### 1.3 Gestão de Usuários e Permissões

- [ ] **Expansão do Sistema de Usuários**
    - Adaptar modelo User para suporte multi-tenant
    - Adicionar relacionamento com Company
    - Implementar soft deletes e logs de atividade

- [ ] **Sistema de Papéis e Permissões**
    - Implementar modelos Role e Permission
    - Configurar permissões específicas para diferentes fluxos
    - Desenvolver interface de atribuição de papéis
    - Implementar middleware de verificação de permissões

### 1.4 Estrutura de Produtos (SKUs)

- [ ] **Modelo de Dados para Produtos**
    - Criar migração para tabela products
    - Implementar modelo Product com suporte a multi-tenant
    - Adicionar campos específicos para tributação (NCM, origem)

- [ ] **Interface de Gerenciamento de Produtos**
    - Desenvolver tela de listagem com filtros
    - Implementar formulário de criação/edição
    - Adicionar suporte a importação em massa (CSV)
    - Desenvolver interface de visualização detalhada

## Fase 2: Pedidos Privados

### 2.1 Estrutura Base de Pedidos

- [ ] **Modelo de Dados para Pedidos**
    - Criar migração para tabela orders
    - Implementar modelo Order com relacionamentos
    - Adicionar campo type para diferenciar públicos/privados
    - Criar estados e transições de pedidos

- [ ] **Modelo de Dados para Itens de Pedido**
    - Criar migração para tabela order_items
    - Implementar modelo OrderItem com relacionamentos
    - Adicionar campos para valores e quantidades
    - Preparar estrutura para regras fiscais (JSON)

### 2.2 Interface de Pedidos Privados

- [ ] **Fluxo de Criação de Pedido**
    - Desenvolver wizard de criação de pedido
    - Implementar seleção de cliente
    - Criar interface de adição de produtos
    - Adicionar cálculos automáticos de valores

- [ ] **Gestão de Pedidos Privados**
    - Criar dashboard de pedidos
    - Implementar filtros por status, data, cliente
    - Desenvolver visualização detalhada de pedido
    - Adicionar ações de edição e cancelamento

### 2.3 Upload e Gestão de Arquivos

- [ ] **Configuração de Armazenamento S3**
    - Configurar conexão com Amazon S3
    - Implementar drivers de disco no Laravel
    - Criar estrutura de pastas organizada

- [ ] **Sistema de Upload de Arquivos**
    - Criar migração para tabela files
    - Implementar modelo File com relacionamentos
    - Desenvolver componente de upload com preview
    - Adicionar validações de tipos e tamanhos

- [ ] **Interface de Gestão de Arquivos**
    - Criar visualização de arquivos por pedido
    - Implementar download e visualização
    - Adicionar categorização de arquivos
    - Desenvolver controle de permissões para arquivos

### 2.4 Processamento de Pedidos

- [ ] **Fluxo de Processamento**
    - Implementar estados de processamento
    - Criar sistema de notificações por email
    - Desenvolver logs de mudanças de estado
    - Adicionar funcionalidade de aprovação

- [ ] **Relatórios Básicos**
    - Criar relatório de pedidos por período
    - Implementar exportação para Excel/PDF
    - Desenvolver métricas básicas de desempenho
    - Adicionar gráficos de acompanhamento

## Fase 3: Pedidos Públicos

### 3.1 Extensão para Licitações

- [ ] **Modelo de Dados para Pedidos Públicos**
    - Criar migração para tabela public_orders
    - Implementar modelo PublicOrder como extensão
    - Adicionar campos específicos de licitações
    - Configurar validações específicas

- [ ] **Documentação Obrigatória**
    - Criar regras de validação para anexos obrigatórios
    - Implementar checklist de documentos necessários
    - Desenvolver sistema de alerta para pendências
    - Adicionar histórico de documentação

### 3.2 Interface de Pedidos Públicos

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

### 3.3 Fluxo de Responsabilidade

- [ ] **Sistema de Designação**
    - Implementar seleção de responsável
    - Criar notificações de atribuição
    - Desenvolver histórico de transferências
    - Adicionar métricas de tempo de resposta

- [ ] **Interface de Acompanhamento**
    - Criar visão por responsável
    - Implementar indicadores de carga de trabalho
    - Desenvolver alertas de prazos críticos
    - Adicionar comentários e histórico de interações

## Fase 4: Regras Fiscais

### 4.1 Estrutura de Dados Fiscais

- [ ] **Modelo de Regras Fiscais**
    - Criar migração para tabela tax_rules
    - Implementar modelo TaxRule com relacionamentos
    - Adicionar campos para todos os componentes fiscais
    - Criar seeds com regras padrão por UF

- [ ] **Cadastro de Regras**
    - Desenvolver interface de gestão de regras
    - Implementar formulário completo com validações
    - Adicionar suporte a clonagem e importação
    - Criar visualização hierárquica por tipo

### 4.2 Motor de Cálculo Fiscal

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

### 4.3 Configuração por Filial

- [ ] **Perfis Fiscais por Empresa**
    - Estender modelo Company para configurações fiscais
    - Implementar padrões por UF da filial
    - Adicionar tratamento de exceções
    - Criar interface de configuração

- [ ] **Validações Específicas**
    - Implementar validação de combinações inválidas
    - Criar alertas para inconsistências
    - Desenvolver logs de alterações fiscais
    - Adicionar suporte a mudanças em lote

## Fase 5: Relatórios e Dashboards

### 5.1 Dashboards Customizados

- [ ] **Dashboard por Perfil**
    - Criar visões específicas por papel de usuário
    - Implementar widgets configuráveis
    - Adicionar gráficos de performance
    - Desenvolver alertas personalizáveis

- [ ] **Visão Consolidada por Tenant**
    - Criar dashboard para administradores de tenant
    - Implementar comparativos entre filiais
    - Adicionar métricas consolidadas
    - Desenvolver projeções e tendências

### 5.2 Relatórios Avançados

- [ ] **Gerador de Relatórios**
    - Implementar engine flexível de relatórios
    - Criar templates pré-definidos
    - Adicionar filtros avançados
    - Desenvolver agendamento de relatórios

- [ ] **Exportação de Dados**
    - Implementar exportação em múltiplos formatos
    - Criar relatórios fiscais específicos
    - Adicionar geração automática de relatórios
    - Desenvolver visualização histórica

### 5.3 Análise de Desempenho

- [ ] **Métricas de Negócio**
    - Implementar KPIs por filial e tenant
    - Criar análise de tempo de processamento
    - Adicionar métricas de eficiência
    - Desenvolver acompanhamento de metas

- [ ] **Detecção de Anomalias**
    - Criar alertas para desvios significativos
    - Implementar identificação de padrões anormais
    - Adicionar notificações proativas
    - Desenvolver sugestões de otimização

## Considerações para Desenvolvimento

### Padrões de Código

- Utilizar tipagem estrita em PHP
- Seguir PSR-12 para formatação
- Implementar testes automatizados para cada componente
- Documentar APIs com OpenAPI/Swagger

### Arquitetura

- Utilizar padrão Repository+Service para lógica de negócio
- Separar regras de negócio complexas em services dedicados
- Implementar filas para processamentos pesados
- Utilizar cache estrategicamente para dados frequentes

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
