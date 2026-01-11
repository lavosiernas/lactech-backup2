# 🚀 COMO ACESSAR O KRON SERVER

## 📍 URLs de Acesso

### Local (XAMPP)
```
http://localhost/Kron/login.php
```

### Produção
```
https://kronx.sbs/login.php
```

---

## 🔐 Credenciais Padrão

**Email:** `admin@kronx.sbs`  
**Senha:** `admin123`

⚠️ **IMPORTANTE:** Altere a senha após o primeiro login!

---

## 📋 Passos para Acessar

### 1. Executar Script SQL
Primeiro, execute o script do banco de dados:

```sql
-- Via phpMyAdmin ou MySQL CLI
SOURCE database/kron_full_schema.sql;
```

OU importe o arquivo `database/kron_full_schema.sql` no phpMyAdmin.

### 2. Verificar Configuração
Verifique se o arquivo `includes/config.php` está configurado corretamente:

```php
// LOCAL
define('KRON_DB_NAME', 'kronserver');
define('KRON_DB_USER', 'root');
define('KRON_DB_PASS', '');

// PRODUÇÃO
define('KRON_DB_NAME', 'kronserver');
define('KRON_DB_USER', 'seu_usuario');
define('KRON_DB_PASS', 'sua_senha');
```

### 3. Acessar Login
Acesse a URL de login conforme seu ambiente:

- **Local:** `http://localhost/Kron/login.php`
- **Produção:** `https://kronx.sbs/login.php`

### 4. Fazer Login
- Digite: `admin@kronx.sbs`
- Digite: `admin123`
- Clique em "Entrar"

### 5. Dashboard
Após o login, você será redirecionado para o dashboard:

- **Local:** `http://localhost/Kron/dashboard/`
- **Produção:** `https://kronx.sbs/dashboard/`

---

## 🔧 Solução de Problemas

### Erro 404 (Not Found)

**Problema:** URL não encontrada

**Soluções:**
1. Verifique se está acessando a URL correta:
   - Local: `http://localhost/Kron/login.php`
   - Não use: `http://localhost/dashboard/` (sem o /Kron/)

2. Verifique se o Apache está rodando

3. Verifique se o módulo `mod_rewrite` está habilitado no Apache

4. Verifique se o arquivo `.htaccess` existe na pasta `Kron/`

### Erro de Conexão com Banco

**Problema:** Não consegue conectar ao banco de dados

**Soluções:**
1. Verifique se o banco `kronserver` foi criado
2. Verifique as credenciais em `includes/config.php`
3. Execute o script SQL novamente

### Erro de Login

**Problema:** Email ou senha incorretos

**Soluções:**
1. Use as credenciais padrão:
   - Email: `admin@kronx.sbs`
   - Senha: `admin123`

2. Se não funcionar, execute o script de correção:
   ```sql
   SOURCE database/fix_admin_password.sql;
   ```

---

## 📁 Estrutura de Arquivos

```
Kron/
├── login.php          ← Acesse aqui primeiro
├── dashboard/         ← Dashboard após login
│   ├── index.php      ← Dashboard principal
│   ├── systems.php    ← Gestão de sistemas
│   ├── users.php      ← Gestão de usuários
│   ├── metrics.php    ← Métricas
│   ├── logs.php       ← Logs
│   ├── commands.php   ← Comandos
│   └── notifications.php ← Notificações
├── includes/
│   ├── config.php     ← Configuração do banco
│   └── auth.php       ← Autenticação
└── database/
    └── kron_full_schema.sql ← Script do banco
```

---

## ✅ Checklist de Instalação

- [ ] Banco de dados `kronserver` criado
- [ ] Script SQL executado com sucesso
- [ ] Configuração do banco em `includes/config.php` ajustada
- [ ] Apache rodando
- [ ] Módulo `mod_rewrite` habilitado
- [ ] Arquivo `.htaccess` presente
- [ ] Acesso a `login.php` funcionando
- [ ] Login com credenciais padrão funcionando

---

**Última atualização:** Dezembro 2024



