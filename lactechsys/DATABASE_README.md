# Banco de Dados Completo - Sistema LacTech

## 📋 Visão Geral

Este repositório contém o esquema completo do banco de dados para o Sistema LacTech, um sistema de gestão de fazendas leiteiras. O banco foi projetado com foco em:

- **Segurança**: Row Level Security (RLS) em todas as tabelas
- **Integridade**: Verificações de dependências circulares e recursão infinita
- **Performance**: Índices otimizados para consultas frequentes
- **Escalabilidade**: Estrutura modular e extensível

## 🗂️ Arquivos Principais

### `database_complete.sql`
Script principal contendo:
- 15 tabelas do sistema
- Políticas RLS completas
- 11 funções RPC personalizadas
- Triggers e índices otimizados
- Verificações de integridade

### `database_validation.sql`
Script de validação que verifica:
- Dependências circulares
- Integridade referencial
- Políticas RLS
- Triggers recursivos
- Índices em foreign keys

## 🏗️ Estrutura do Banco de Dados

### Tabelas Principais (Core System)

1. **`farms`** - Fazendas cadastradas
   - Base do sistema, todas as outras tabelas referenciam
   - Campos: nome, proprietário, CNPJ, localização, etc.

2. **`users`** - Usuários do sistema
   - Referencia `auth.users` do Supabase
   - Roles: proprietario, gerente, funcionario, veterinario

3. **`milk_production`** - Produção diária de leite
   - Registros por turno (manhã, tarde, noite)
   - Controle de volume e temperatura

4. **`quality_tests`** - Análises de qualidade
   - Percentual de gordura e proteína
   - Contagem de células somáticas (SCC)
   - Contagem bacteriana total (CBT)
   - Cálculo automático de nota de qualidade

5. **`financial_records`** - Registros financeiros (receitas e despesas)
   - Controle de vendas de leite
   - Status de pagamento
   - Cálculo automático do valor total

6. **`animals`** - Rebanho
   - Cadastro individual dos animais
   - Status de saúde

7. **`notifications`** - Sistema de notificações
   - Alertas para usuários
   - Tipos: info, warning, error, success

8. **`secondary_accounts`** - Contas secundárias
   - Relacionamento entre contas principais e secundárias

### Módulo Veterinário

9. **`treatments`** - Tratamentos veterinários
   - Medicações e dosagens
   - Agendamento de próximos tratamentos

10. **`animal_health_records`** - Histórico de saúde
    - Registros detalhados de saúde dos animais
    - Peso, temperatura, observações

11. **`artificial_inseminations`** - Inseminação Artificial (IA)
    - Registro detalhado de cada inseminação
    - Controle de lotes de sêmen e origem
    - Identificação do touro e técnico responsável
    - Métodos de detecção de cio
    - Escore de condição corporal
    - Data prevista de parto (calculada automaticamente)
    - Confirmação de gravidez e método utilizado
    - Histórico completo para planejamento reprodutivo

### Sistema de Pagamentos PIX

12. **`pix_payments`** - Pagamentos PIX
    - Integração com sistema de pagamentos
    - Controle de status e expiração

13. **`subscriptions`** - Assinaturas
    - Planos: basic, premium, enterprise
    - Controle de validade

### Configuração e Gestão

14. **`financial_records`** - Registros financeiros
    - Receitas e despesas da fazenda
    - Categorização de transações

15. **`farm_settings`** - Configurações da fazenda
    - Configurações específicas por fazenda
    - Sistema chave-valor flexível

## 🔒 Segurança (Row Level Security)

### Princípios de Segurança

1. **Isolamento por Fazenda**: Usuários só acessam dados de sua fazenda
2. **Controle de Roles**: Diferentes permissões por tipo de usuário
3. **Proteção de Dados Pessoais**: Usuários só editam seu próprio perfil
4. **Auditoria**: Timestamps automáticos em todas as operações

### Políticas RLS Implementadas

- **Farms**: Acesso restrito à fazenda do usuário
- **Users**: Visualização de membros da mesma fazenda
- **Dados Operacionais**: Filtro automático por `farm_id`
- **Pagamentos PIX**: Acesso restrito ao próprio usuário
- **Contas Secundárias**: Acesso para contas primárias e secundárias

## 🚀 Funções RPC Disponíveis

### Autenticação e Setup
- `check_farm_exists(name, cnpj)` - Verificar existência de fazenda
- `check_user_exists(email)` - Verificar existência de usuário
- `create_initial_farm(...)` - Criar fazenda inicial
- `create_initial_user(...)` - Criar usuário inicial
- `complete_farm_setup(farm_id)` - Finalizar configuração

### Dados e Estatísticas
- `get_user_profile()` - Obter perfil completo do usuário
- `get_farm_statistics()` - Estatísticas da fazenda
- `register_milk_production(...)` - Registrar produção
- `update_user_report_settings(...)` - Atualizar configurações de relatório

### Sistema de Pagamentos
- `get_user_subscriptions()` - Obter assinaturas do usuário
- `get_user_payments()` - Obter histórico de pagamentos

### Módulo Veterinário
- `register_artificial_insemination(...)` - Registrar inseminação artificial
- `confirm_pregnancy(...)` - Confirmar gravidez de inseminação

## 📊 Recursos Avançados

### Triggers Automáticos
- **Updated At**: Atualização automática de timestamps
- **Quality Score**: Cálculo automático da nota de qualidade do leite

### Índices Otimizados
- Índices compostos para consultas por fazenda e data
- Índices em foreign keys para performance
- Índices em campos de busca frequente

### Prevenção de Recursão
- Verificação de dependências circulares
- Limitação de profundidade em consultas recursivas
- Validação de integridade referencial

## 🛠️ Como Usar

### 1. Instalação

```sql
-- Executar o script principal
\i database_complete.sql

-- Executar validações (opcional)
\i database_validation.sql
```

### 2. Verificação de Integridade

```sql
-- Verificar se todas as tabelas foram criadas
SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename;

-- Verificar políticas RLS
SELECT tablename, policyname FROM pg_policies WHERE schemaname = 'public';

-- Executar validação completa
\i database_validation.sql
```

### 3. Primeiro Uso

```sql
-- Criar fazenda inicial
SELECT create_initial_farm(
    'Fazenda Exemplo',
    'João Silva',
    '12.345.678/0001-90',
    'São Paulo',
    'SP'
);

-- Criar usuário inicial
SELECT create_initial_user(
    'user-uuid-here',
    'farm-uuid-here',
    'João Silva',
    'joao@fazenda.com',
    'proprietario'
);
```

## 🔍 Monitoramento e Manutenção

### Consultas de Monitoramento

```sql
-- Verificar registros órfãos
SELECT * FROM check_referential_integrity();

-- Verificar dependências circulares
SELECT * FROM check_circular_dependencies();

-- Verificar políticas RLS
SELECT * FROM check_rls_policies();

-- Verificar triggers recursivos
SELECT * FROM check_trigger_recursion();
```

### Estatísticas de Uso

```sql
-- Contagem de registros por tabela
SELECT 
    schemaname,
    tablename,
    n_tup_ins as inserts,
    n_tup_upd as updates,
    n_tup_del as deletes
FROM pg_stat_user_tables 
WHERE schemaname = 'public'
ORDER BY n_tup_ins DESC;
```

## ⚠️ Considerações Importantes

### Backup e Recuperação
- Fazer backup regular das tabelas principais
- Testar procedimentos de recuperação
- Manter logs de auditoria

### Performance
- Monitorar consultas lentas
- Atualizar estatísticas regularmente
- Considerar particionamento para tabelas grandes

### Segurança
- Revisar políticas RLS periodicamente
- Auditar acessos e permissões
- Manter extensões atualizadas

## 🐛 Solução de Problemas

### Erros Comuns

1. **Erro de Foreign Key**: Verificar ordem de inserção
2. **RLS Negando Acesso**: Verificar se usuário está autenticado
3. **Trigger Recursivo**: Verificar lógica dos triggers
4. **Performance Lenta**: Verificar índices e estatísticas

### Logs e Debug

```sql
-- Habilitar logs detalhados
SET log_statement = 'all';
SET log_min_duration_statement = 0;

-- Verificar locks
SELECT * FROM pg_locks WHERE NOT granted;

-- Verificar consultas ativas
SELECT * FROM pg_stat_activity WHERE state = 'active';
```

## 📞 Suporte

Para dúvidas ou problemas:
1. Verificar logs do PostgreSQL
2. Executar script de validação
3. Consultar documentação do Supabase
4. Verificar políticas RLS

---

**Versão**: 1.0  
**Última Atualização**: 2024  
**Compatibilidade**: PostgreSQL 13+, Supabase  
**Licença**: Proprietária - Sistema LacTech