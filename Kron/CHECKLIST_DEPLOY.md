# ✅ CHECKLIST DE DEPLOY - KRON

## 📋 ANTES DE SUBIR PARA HOSPEDAGEM

### 1. **Configurar Banco de Dados** ⚠️ OBRIGATÓRIO

Edite o arquivo `includes/config.php` e ajuste as credenciais de produção:

```php
// PRODUÇÃO (kronx.sbs)
define('KRON_DB_HOST', 'localhost');
define('KRON_DB_NAME', 'kron');
define('KRON_DB_USER', 'SEU_USUARIO_AQUI');  // ⚠️ ALTERAR
define('KRON_DB_PASS', 'SUA_SENHA_AQUI');    // ⚠️ ALTERAR
```

### 2. **Executar Script SQL** ⚠️ OBRIGATÓRIO

Execute o script no banco de dados de produção:

```sql
-- Via phpMyAdmin ou MySQL CLI
SOURCE lactech/kron/database/create_kron_ecosystem.sql;
```

OU copie e cole todo o conteúdo do arquivo `database/create_kron_ecystem.sql` no phpMyAdmin.

### 3. **Configurar Google OAuth** ⚠️ OBRIGATÓRIO

No Google Cloud Console, adicione a URL de callback de produção:

- **URL de Callback:** `https://kronx.sbs/google-callback.php`

O código já detecta automaticamente o ambiente, então não precisa alterar nada no código.

### 4. **Chave Secreta (Opcional mas Recomendado)** 🔒

Por segurança, altere a chave secreta em produção:

**Arquivo:** `includes/KronConnectionManager.php` (linha 17)
```php
$this->secretKey = 'SUA_CHAVE_SECRETA_FORTE_AQUI';
```

**Arquivo:** `api/generate-connection-token.php` (linha 77)
```php
$secretKey = 'SUA_CHAVE_SECRETA_FORTE_AQUI';
```

Use uma chave forte e única, por exemplo:
```php
$this->secretKey = bin2hex(random_bytes(32)); // Gera chave aleatória de 64 caracteres
```

### 5. **Verificar Permissões de Arquivos** 📁

Certifique-se de que os diretórios têm permissões corretas:
- Diretórios: `755` ou `775`
- Arquivos: `644` ou `664`

### 6. **Verificar Extensões PHP Necessárias** 🔧

Certifique-se de que o servidor tem habilitado:
- ✅ PDO MySQL
- ✅ cURL
- ✅ GD Library (opcional, para QR Code com logo)
- ✅ OpenSSL (para Google OAuth)

### 7. **Testar Após Deploy** 🧪

Após subir, teste:
1. ✅ Acessar `https://kronx.sbs/login.php`
2. ✅ Fazer login com email/senha
3. ✅ Fazer login com Google OAuth
4. ✅ Acessar dashboard
5. ✅ Gerar token de conexão
6. ✅ Gerar QR Code

---

## 📦 ARQUIVOS PARA UPLOAD

Faça upload de TODA a pasta `lactech/kron/` para o servidor, incluindo:

```
kron/
├── api/
│   ├── cancel-connection-token.php
│   ├── generate-connection-token.php
│   ├── get-pending-token.php
│   ├── user-connections.php
│   └── verify-connection-token.php
├── asset/
│   ├── brasil.png
│   ├── chile.png
│   ├── kron.png
│   └── telenode.png
├── dashboard/
│   ├── .htaccess
│   ├── index.php
│   └── profile.php
├── database/
│   ├── create_kron_ecosystem.sql
│   ├── add_kron_columns_safenode.sql
│   └── add_kron_columns_lactech.sql
├── includes/
│   ├── config.php ⚠️ AJUSTAR CREDENCIAIS
│   ├── GoogleOAuth.php
│   ├── KronConnectionManager.php
│   └── KronQRGenerator.php
├── google-auth.php
├── google-callback.php
├── landing.php
├── login.php
├── logout.php
└── register.php
```

---

## ⚠️ IMPORTANTE

1. **NÃO** faça upload do arquivo `test-connection.php` (se existir)
2. **NÃO** faça upload de arquivos `.md` ou `.txt` de documentação (opcional)
3. **AJUSTE** as credenciais do banco em `includes/config.php` ANTES de fazer upload
4. **EXECUTE** o script SQL no banco de dados de produção

---

## 🚀 ORDEM DE EXECUÇÃO

1. ✅ Ajustar `includes/config.php` com credenciais de produção
2. ✅ Fazer upload de todos os arquivos
3. ✅ Executar script SQL no banco de dados
4. ✅ Configurar callback do Google OAuth
5. ✅ Testar login e funcionalidades
6. ✅ (Opcional) Alterar chave secreta

---

## ✅ PRONTO PARA DEPLOY!

Após seguir este checklist, o sistema estará pronto para produção.

