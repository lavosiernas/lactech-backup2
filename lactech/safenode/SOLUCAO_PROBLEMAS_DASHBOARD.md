# Solução dos Problemas do Dashboard

## Problemas Identificados pelo Diagnóstico

### ✅ 1. View `v_safenode_active_blocks` com erro de definer
**Erro:** `The user specified as a definer ('u311882628_xandria'@'127.0.0.1') does not exist`

**Solução aplicada:**
- Criado script SQL para recriar a view sem definer específico
- **Arquivo:** `database/FIX_VIEW_ACTIVE_BLOCKS.sql`

**Como corrigir:**
1. Acesse o phpMyAdmin ou cliente MySQL
2. Selecione o banco de dados `u311882628_safend` (ou o nome do seu banco)
3. Execute o script SQL em `database/FIX_VIEW_ACTIVE_BLOCKS.sql`

### ✅ 2. Valores NULL em queries
**Problema:** Quando não há dados, `SUM()` retorna `NULL` ao invés de `0`, causando erros no JavaScript

**Solução aplicada:**
- Adicionado `COALESCE()` em todas as queries `SUM()` no arquivo `api/dashboard-stats.php`
- Adicionado tratamento para garantir que valores NULL sejam convertidos para 0

### ⚠️ 3. Tabela `safenode_security_logs` vazia
**Status:** A tabela existe mas está sem dados (0 registros)

**Causa provável:**
- O middleware SafeNodeMiddleware não está sendo executado
- Não há requisições sendo processadas ainda
- O sistema ainda não começou a registrar logs

**O que fazer:**
1. Verificar se o middleware está incluído no projeto que será protegido
2. Fazer algumas requisições ao site protegido para gerar logs
3. Verificar se o `site_id` está sendo passado corretamente

### ⚠️ 4. Usuário sem sites cadastrados
**Status:** O usuário não possui sites cadastrados

**O que fazer:**
1. Acesse a página de gerenciamento de sites: `sites.php`
2. Cadastre pelo menos um site
3. Após cadastrar, o dashboard poderá filtrar dados por site

## Correções Aplicadas no Código

### `api/dashboard-stats.php`
✅ Adicionado `COALESCE()` em todas as queries `SUM()`:
- Query de estatísticas do dia
- Query das últimas 24 horas
- Query de ontem

✅ Tratamento adicional para garantir valores nunca sejam NULL

### `database/FIX_VIEW_ACTIVE_BLOCKS.sql`
✅ Script SQL criado para recriar a view sem problemas de definer

## Instruções para Resolver

### Passo 1: Executar Script SQL da View
```sql
-- Execute este script no seu banco de dados:
-- Arquivo: database/FIX_VIEW_ACTIVE_BLOCKS.sql

DROP VIEW IF EXISTS `v_safenode_active_blocks`;

CREATE VIEW `v_safenode_active_blocks` AS 
SELECT 
    `safenode_blocked_ips`.`ip_address` AS `ip_address`, 
    `safenode_blocked_ips`.`reason` AS `reason`, 
    `safenode_blocked_ips`.`threat_type` AS `threat_type`, 
    `safenode_blocked_ips`.`created_at` AS `blocked_at`, 
    `safenode_blocked_ips`.`expires_at` AS `expires_at`, 
    TIMESTAMPDIFF(SECOND, CURRENT_TIMESTAMP(), `safenode_blocked_ips`.`expires_at`) AS `seconds_remaining` 
FROM `safenode_blocked_ips` 
WHERE `safenode_blocked_ips`.`is_active` = 1 
    AND (`safenode_blocked_ips`.`expires_at` IS NULL OR `safenode_blocked_ips`.`expires_at` > CURRENT_TIMESTAMP());
```

### Passo 2: Cadastrar um Site
1. Acesse: `http://seu-dominio/lactech/safenode/sites.php`
2. Clique em "Adicionar Site" ou similar
3. Preencha os dados do site (domínio, etc.)
4. Salve o site

### Passo 3: Verificar se o Middleware está Ativo
O middleware precisa estar interceptando requisições para gerar logs. Verifique:
- Se o arquivo `includes/SafeNodeMiddleware.php` existe
- Se está sendo incluído no projeto que será protegido
- Se está processando requisições

### Passo 4: Gerar Dados de Teste (Opcional)
Para ver o dashboard funcionando, você pode:
1. Fazer requisições ao site protegido (se o middleware estiver ativo)
2. Ou inserir dados de teste manualmente no banco

**Exemplo de inserção de dados de teste:**
```sql
INSERT INTO safenode_security_logs 
(ip_address, request_uri, request_method, threat_type, threat_score, action_taken, site_id, created_at) 
VALUES 
('192.168.1.100', '/admin/login', 'POST', 'brute_force', 75, 'blocked', 1, NOW()),
('10.0.0.50', '/wp-admin', 'GET', 'sql_injection', 85, 'blocked', 1, NOW()),
('203.0.113.1', '/index.php', 'GET', NULL, 10, 'allowed', 1, NOW());
```

**Nota:** Ajuste o `site_id` (1) para o ID do site que você cadastrou.

## Verificação Pós-Correção

Após aplicar as correções, execute o diagnóstico novamente:

```
http://seu-dominio/lactech/safenode/api/diagnostic-dashboard.php
```

**Resultado esperado:**
- ✅ View `v_safenode_active_blocks` deve estar OK
- ✅ Valores NULL devem ser 0
- ⚠️ Tabela vazia é normal se não há requisições ainda
- ⚠️ Sites precisam ser cadastrados manualmente

## Status Final

| Item | Status | Ação Necessária |
|------|--------|----------------|
| Conexão com banco | ✅ OK | Nenhuma |
| Tabelas existem | ✅ OK | Nenhuma |
| Colunas corretas | ✅ OK | Nenhuma |
| View corrigida | ⚠️ PENDENTE | Executar script SQL |
| Valores NULL | ✅ CORRIGIDO | Nenhuma |
| Tabela vazia | ⚠️ NORMAL | Ativar middleware ou gerar dados |
| Sites cadastrados | ⚠️ PENDENTE | Cadastrar sites manualmente |

## Próximos Passos

1. **Execute o script SQL** da view (Passo 1)
2. **Cadastre pelo menos um site** (Passo 2)
3. **Verifique o diagnóstico novamente** para confirmar que os erros foram resolvidos
4. **Ative o middleware** para começar a registrar logs automaticamente

Após esses passos, o dashboard deve funcionar corretamente! 🚀


