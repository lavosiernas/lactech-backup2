# 🔴 CORREÇÃO CRÍTICA DE SEGURANÇA - SafeNode

## ⚠️ PROBLEMA IDENTIFICADO

A tabela `safenode_sites` **NÃO possui coluna `user_id`**, permitindo que:
- ❌ Usuários vejam sites de outros usuários
- ❌ Usuários editem/deletem sites de outros usuários
- ❌ Dados sejam misturados entre contas diferentes

## 📋 PASSO A PASSO PARA CORREÇÃO

### 1️⃣ Executar SQL no Banco de Dados

Execute o arquivo: `database/fix_user_sites_security.sql`

```bash
# No phpMyAdmin ou terminal MySQL:
mysql -u SEU_USUARIO -p u311882628_safend < database/fix_user_sites_security.sql
```

### 2️⃣ Atualizar Sites Existentes

Você precisa associar cada site ao seu dono correto:

```sql
-- Exemplo: Atualizar o site "denfy.vercel.app" para pertencer ao user ID 2
UPDATE safenode_sites SET user_id = 2 WHERE id = 2;

-- Liste os sites atuais:
SELECT id, domain, display_name FROM safenode_sites;

-- Liste os usuários:
SELECT id, username, email FROM safenode_users;

-- Depois associe cada site ao usuário correto
```

### 3️⃣ Arquivos PHP que PRECISAM ser Atualizados

Os seguintes arquivos acessam `safenode_sites` e DEVEM filtrar por `user_id`:

1. **`sites.php`** - Listagem e criação de sites
2. **`dashboard.php`** - Seleção de site ativo
3. **`includes/sidebar.php`** - Menu lateral com sites
4. **`includes/init.php`** - Inicialização de site
5. **`profile.php`** - Perfil do usuário
6. **`dns_records.php`** - Registros DNS
7. **`includes/SafeNodeMiddleware.php`** - Middleware de segurança

### 4️⃣ Padrão de Correção

**❌ ANTES (INSEGURO):**
```php
$stmt = $db->prepare("SELECT * FROM safenode_sites WHERE id = ?");
$stmt->execute([$siteId]);
```

**✅ DEPOIS (SEGURO):**
```php
$userId = $_SESSION['safenode_user_id'];
$stmt = $db->prepare("SELECT * FROM safenode_sites WHERE id = ? AND user_id = ?");
$stmt->execute([$siteId, $userId]);
```

### 5️⃣ Ao Criar Novo Site

**✅ Sempre incluir user_id:**
```php
$userId = $_SESSION['safenode_user_id'];
$stmt = $db->prepare("
    INSERT INTO safenode_sites (user_id, domain, display_name, ...) 
    VALUES (?, ?, ?, ...)
");
$stmt->execute([$userId, $domain, $displayName, ...]);
```

## 🔒 VERIFICAÇÃO FINAL

Após as correções, teste:

1. ✅ Criar um site com User A
2. ✅ Fazer logout
3. ✅ Login com User B
4. ✅ Verificar que User B NÃO vê o site de User A
5. ✅ User B tenta acessar `?site_id=X` (site do User A)
6. ✅ Deve dar erro ou redirecionar

## 📝 STATUS

- [ ] SQL executado
- [ ] Sites existentes atualizados com user_id
- [ ] sites.php corrigido
- [ ] dashboard.php corrigido  
- [ ] sidebar.php corrigido
- [ ] init.php corrigido
- [ ] profile.php corrigido
- [ ] dns_records.php corrigido
- [ ] SafeNodeMiddleware.php corrigido
- [ ] Testes realizados

---

**⚠️ NÃO suba o sistema de volta até completar TODAS as correções acima!**


