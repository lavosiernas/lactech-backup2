# GUIA DE INSTALAÇÃO - BANCOS DE DADOS KRON

## 📋 ORDEM DE EXECUÇÃO

Execute os scripts na seguinte ordem:

1. **KRON** (primeiro)
2. **SafeNode** (segundo)
3. **LacTech** (terceiro)

---

## 🔵 1. KRON - O QUE EXECUTAR

### Arquivo: `create_kron_ecosystem.sql`

**Onde executar:** No servidor do KRON (kron.sbs)

**O que faz:**
- Cria o banco de dados `kron_ecosystem`
- Cria 5 tabelas:
  - `kron_users` - Usuários do sistema KRON (com suporte a Google OAuth)
  - `kron_user_sessions` - Sessões ativas dos usuários
  - `kron_connection_tokens` - Tokens temporários para conexão
  - `kron_user_connections` - Conexões estabelecidas entre sistemas
  - `kron_connection_logs` - Logs de tentativas de conexão

**Sistema completo incluído:**
- ✅ Login com email/senha
- ✅ Login com Google OAuth
- ✅ Registro com email/senha
- ✅ Registro com Google OAuth
- ✅ Gerenciamento de sessões

**Como executar:**
```sql
-- Via phpMyAdmin ou MySQL CLI
SOURCE lactech/kron/database/create_kron_ecosystem.sql;
```

**OU copie e cole todo o conteúdo do arquivo no phpMyAdmin**

---

## 🟢 2. SAFENODE - O QUE EXECUTAR

### Arquivo: `add_kron_columns_safenode.sql`

**Onde executar:** No servidor do SafeNode (safenode.cloud)

**Banco de dados:** `safend`

**O que faz:**
- Adiciona 3 colunas na tabela `safenode_users`:
  - `kron_user_id` - ID do usuário no KRON
  - `kron_connection_token` - Token permanente de conexão
  - `kron_connected_at` - Data/hora da conexão

**Como executar:**
```sql
-- Via phpMyAdmin ou MySQL CLI
USE `safend`;
SOURCE lactech/kron/database/add_kron_columns_safenode.sql;
```

**OU copie e cole o conteúdo do arquivo no phpMyAdmin**

**Verificação (opcional):**
```sql
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'safend' AND TABLE_NAME = 'safenode_users' 
AND COLUMN_NAME LIKE 'kron%';
```

---

## 🟡 3. LACTECH - O QUE EXECUTAR

### Arquivo: `add_kron_columns_lactech.sql`

**Onde executar:** No servidor do LacTech (lactechsys.com)

**Banco de dados:** `lactech_lgmato` (ou o nome do seu banco)

**⚠️ IMPORTANTE:** Antes de executar, verifique o nome do banco e ajuste no script se necessário!

**O que faz:**
- Adiciona 3 colunas na tabela `users`:
  - `kron_user_id` - ID do usuário no KRON
  - `kron_connection_token` - Token permanente de conexão
  - `kron_connected_at` - Data/hora da conexão

**Como executar:**
```sql
-- Via phpMyAdmin ou MySQL CLI
-- PRIMEIRO: Verifique o nome do banco
SHOW DATABASES;

-- DEPOIS: Execute o script (ajuste o nome do banco se necessário)
USE `lactech_lgmato`;  -- Substitua pelo nome real do seu banco
SOURCE lactech/kron/database/add_kron_columns_lactech.sql;
```

**OU copie e cole o conteúdo do arquivo no phpMyAdmin**

**Verificação (opcional):**
```sql
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'lactech_lgmato' AND TABLE_NAME = 'users' 
AND COLUMN_NAME LIKE 'kron%';
```

---

## ✅ CHECKLIST DE INSTALAÇÃO

- [ ] **KRON:** Banco `kron_ecosystem` criado com 4 tabelas
- [ ] **SafeNode:** Colunas `kron_*` adicionadas em `safenode_users`
- [ ] **LacTech:** Colunas `kron_*` adicionadas em `users`

---

## 🔍 VERIFICAÇÃO FINAL

### Verificar KRON:
```sql
USE `kron_ecosystem`;
SHOW TABLES;
-- Deve mostrar: kron_users, kron_connection_tokens, kron_user_connections, kron_connection_logs
```

### Verificar SafeNode:
```sql
USE `safend`;
DESCRIBE `safenode_users`;
-- Deve mostrar as colunas: kron_user_id, kron_connection_token, kron_connected_at
```

### Verificar LacTech:
```sql
USE `lactech_lgmato`;  -- Ajuste o nome do banco
DESCRIBE `users`;
-- Deve mostrar as colunas: kron_user_id, kron_connection_token, kron_connected_at
```

---

## ⚠️ OBSERVAÇÕES IMPORTANTES

1. **Execute na ordem:** KRON → SafeNode → LacTech
2. **Backup:** Faça backup dos bancos antes de executar os scripts
3. **Nome do banco LacTech:** Verifique o nome correto antes de executar
4. **Permissões:** Certifique-se de ter permissões para criar/alterar tabelas
5. **Foreign Keys:** O banco KRON usa foreign keys, então execute primeiro

---

## 🆘 PROBLEMAS COMUNS

### Erro: "Column already exists"
**Solução:** As colunas já existem. Execute:
```sql
-- Para SafeNode
ALTER TABLE `safenode_users` DROP COLUMN IF EXISTS `kron_user_id`;
ALTER TABLE `safenode_users` DROP COLUMN IF EXISTS `kron_connection_token`;
ALTER TABLE `safenode_users` DROP COLUMN IF EXISTS `kron_connected_at`;
-- Depois execute o script novamente
```

### Erro: "Database doesn't exist"
**Solução:** Crie o banco primeiro:
```sql
CREATE DATABASE `kron_ecosystem` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Erro: "Access denied"
**Solução:** Verifique as permissões do usuário MySQL

