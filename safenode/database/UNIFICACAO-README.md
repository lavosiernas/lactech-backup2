# Unificação de Tabelas - SafeNode

## 📋 Resumo

Este documento descreve a unificação das tabelas `safenode_human_verification_logs` e `safenode_hv_attempts` em uma única tabela unificada (`safenode_human_verification_logs`).

## 🎯 Objetivo

Resolver o problema arquitetural de ter duas tabelas para a mesma funcionalidade:
- **Antes:** Dados fragmentados entre `safenode_human_verification_logs` (middleware) e `safenode_hv_attempts` (SDK)
- **Depois:** Uma única tabela unificada que armazena todos os eventos de verificação humana

## 📝 Mudanças no Banco de Dados

### 1. Estrutura da Tabela Unificada

A tabela `safenode_human_verification_logs` agora possui:
- `api_key_id` (INT, NULL) - Para eventos do SDK
- `site_id` (INT, NULL) - Para eventos do middleware (agora opcional)
- `reason` (VARCHAR(255), NULL) - Motivo do bloqueio/falha
- Todos os outros campos existentes

### 2. Script de Migração

Execute o script `unify-verification-logs.sql` que:
1. Adiciona `api_key_id` e `reason` à tabela
2. Torna `site_id` opcional
3. Migra dados de `safenode_hv_attempts` para `safenode_human_verification_logs`
4. Cria view de compatibilidade temporária

## 🔧 Mudanças no Código

### Arquivos Modificados:

1. **`safenode/includes/HVAPIKeyManager.php`**
   - `logAttempt()` agora salva diretamente em `safenode_human_verification_logs`
   - Tenta descobrir `site_id` através do `referer` quando possível
   - Mapeia `attempt_type` para `event_type` automaticamente

2. **`safenode/api/dashboard-stats.php`**
   - Todas as queries agora usam apenas `safenode_human_verification_logs`
   - Filtros incluem tanto `site_id` quanto `api_key_id` quando necessário
   - Removida lógica de combinação de duas tabelas

3. **`safenode/logs.php`**
   - Query simplificada para usar apenas a tabela unificada
   - Filtros atualizados para incluir `api_key_id`
   - Removida lógica de combinação de duas tabelas

## ✅ Benefícios

1. **Arquitetura Limpa:** Uma única fonte de verdade
2. **Queries Simples:** Sem necessidade de combinar dados
3. **Performance:** Menos joins e queries duplicadas
4. **Manutenibilidade:** Código mais simples e fácil de entender
5. **Consistência:** Dados sempre sincronizados

## ⚠️ Importante

- A tabela `safenode_hv_attempts` será mantida como backup por 30 dias
- Não remova `safenode_hv_attempts` imediatamente após a migração
- A view `safenode_hv_attempts_view` permite compatibilidade temporária com código antigo

## 🚀 Próximos Passos

1. Executar script SQL em desenvolvimento
2. Testar todas as funcionalidades
3. Executar script SQL em produção
4. Monitorar por 30 dias
5. Remover tabela `safenode_hv_attempts` após validação

