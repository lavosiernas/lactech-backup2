# Diagnóstico de Erros do Dashboard SafeNode

## Arquivo de Diagnóstico Criado

Foi criado um arquivo de diagnóstico em `api/diagnostic-dashboard.php` que verifica:
- Conexão com o banco de dados
- Existência das tabelas necessárias
- Colunas essenciais das tabelas
- Quantidade de dados disponíveis
- Sites do usuário
- Teste de queries específicas do dashboard

**Para usar o diagnóstico:**
1. Acesse: `http://seu-dominio/lactech/safenode/api/diagnostic-dashboard.php`
2. Verá um JSON com todos os diagnósticos e erros encontrados

## Possíveis Problemas Encontrados

### 1. Tabela `safenode_security_logs` vazia
**Sintoma:** Dashboard mostra zeros ou dados vazios
**Causa:** Não há dados de segurança registrados ainda
**Solução:** O middleware precisa estar ativo e processando requisições

### 2. Coluna `site_id` NULL em muitos registros
**Sintoma:** Filtros de site não funcionam corretamente
**Causa:** Registros antigos ou middleware não está passando site_id
**Solução:** Verificar se o middleware está capturando e salvando o site_id

### 3. View `v_safenode_active_blocks` não existe
**Sintoma:** Erro ao buscar IPs bloqueados ativos
**Causa:** View não foi criada ou foi removida
**Solução:** O código já tem fallback, mas a view deveria existir

### 4. Usuário sem sites cadastrados
**Sintoma:** Dashboard não mostra dados mesmo tendo logs
**Causa:** Usuário não tem sites cadastrados ou site_id não está sendo setado
**Solução:** Verificar se há sites cadastrados e se o site_id está sendo usado corretamente

### 5. Problemas de conexão com banco
**Sintoma:** Erro 500 ou timeout ao acessar a API
**Causa:** Credenciais incorretas, banco offline, ou configuração incorreta
**Solução:** Verificar `includes/config.php` e conexão com MySQL/MariaDB

## Melhorias Implementadas

### 1. Tratamento de Erros Melhorado no JavaScript
- Agora verifica se a resposta HTTP é OK
- Faz parse seguro do JSON com tratamento de erros
- Exibe mensagens de erro mais detalhadas no console
- Mostra quando a resposta não é JSON válido

### 2. Tratamento de Erros Melhorado na API
- Output buffering para evitar HTML em respostas JSON
- Try-catch em todas as queries
- Valores padrão quando queries falham
- Logging de erros para debug

## Tabelas Necessárias

O dashboard precisa das seguintes tabelas:
- ✅ `safenode_security_logs` - Logs de segurança (principal)
- ✅ `safenode_sites` - Sites cadastrados
- ✅ `safenode_incidents` - Incidentes de segurança
- ✅ `safenode_users` - Usuários
- ✅ `safenode_ip_reputation` - Reputação de IPs

## Colunas Essenciais em `safenode_security_logs`

O dashboard usa estas colunas:
- `id`
- `ip_address`
- `request_uri`
- `threat_type`
- `threat_score`
- `action_taken`
- `site_id` ⚠️ IMPORTANTE para filtros
- `country_code`
- `created_at`

## Próximos Passos para Debug

1. **Executar o diagnóstico:**
   ```
   Acesse: lactech/safenode/api/diagnostic-dashboard.php
   ```

2. **Verificar logs do PHP:**
   - Erros serão logados em `error_log` do PHP
   - Verificar logs do servidor web (Apache/Nginx)

3. **Testar a API diretamente:**
   ```
   Acesse: lactech/safenode/api/dashboard-stats.php
   ```
   Deve retornar JSON válido

4. **Verificar console do navegador:**
   - Abra DevTools (F12)
   - Vá em Console
   - Veja se há erros de JavaScript
   - Vá em Network e veja a resposta da API

5. **Verificar dados no banco:**
   ```sql
   -- Verificar se há dados
   SELECT COUNT(*) FROM safenode_security_logs;
   
   -- Verificar dados de hoje
   SELECT COUNT(*) FROM safenode_security_logs 
   WHERE DATE(created_at) = CURDATE();
   
   -- Verificar sites do usuário
   SELECT * FROM safenode_sites WHERE user_id = ?;
   ```

## Verificações Manuais

1. ✅ Tabelas existem? → Verificar com `SHOW TABLES;`
2. ✅ Colunas existem? → Verificar com `DESCRIBE safenode_security_logs;`
3. ✅ Há dados? → Verificar com `SELECT COUNT(*) FROM safenode_security_logs;`
4. ✅ Middleware está ativo? → Verificar se está interceptando requisições
5. ✅ site_id está sendo salvo? → Verificar logs recentes





