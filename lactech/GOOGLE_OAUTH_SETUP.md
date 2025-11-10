# Configuração Google OAuth - LacTech

## 🔑 O que você precisa do Google Cloud Console

### 1. Credenciais OAuth 2.0

Você precisa criar um **Client ID** e **Client Secret** no Google Cloud Console.

**Valores necessários:**
- **Client ID** - Identificador público do seu app
- **Client Secret** - Chave secreta para autenticação
- **Redirect URI** - URL de retorno após autorização

---

## 📋 Passo a Passo no Google Cloud Console

### Passo 1: Acessar Google Cloud Console

1. Acesse: https://console.cloud.google.com/
2. Faça login com sua conta Google
3. Crie um novo projeto ou selecione um existente

### Passo 2: Ativar Google+ API

1. Vá em **APIs & Services** > **Library**
2. Procure por **"Google+ API"** ou **"People API"**
3. Clique em **Enable**

### Passo 3: Criar Credenciais OAuth 2.0

1. Vá em **APIs & Services** > **Credentials**
2. Clique em **Create Credentials** > **OAuth client ID**
3. Se for a primeira vez, configure a tela de consentimento:
   - Escolha **External** (para testes) ou **Internal** (para Google Workspace)
   - Preencha as informações do app
   - Adicione seu e-mail como test user (se necessário)

4. Configure o OAuth Client:
   - **Application type**: `Web application`
   - **Name**: `LacTech - Sistema de Gestão`
   
5. **Authorized redirect URIs** (MUITO IMPORTANTE):
   ```
   http://localhost/lactech/google-callback.php
   http://localhost/lactech/api/google-callback.php
   https://seudominio.com/lactech/google-callback.php
   ```
   ⚠️ **Adicione todas as URLs onde seu sistema estará hospedado**

6. Clique em **Create**
7. **Copie** o **Client ID** e **Client Secret** que aparecerem

---

## 📝 O que você precisa fornecer

Depois de criar as credenciais, você terá:

```
Client ID: xxxxxxx-xxxxxxx.apps.googleusercontent.com
Client Secret: GOCSPX-xxxxxxxxxxxxxx
```

### Onde adicionar no código

Você pode me fornecer esses valores e eu adiciono no arquivo de configuração, ou você pode adicionar manualmente:

**Arquivo:** `lactech/includes/config_google.php` (vou criar este arquivo)

```php
<?php
// Configurações Google OAuth
define('GOOGLE_CLIENT_ID', 'SEU_CLIENT_ID_AQUI');
define('GOOGLE_CLIENT_SECRET', 'SEU_CLIENT_SECRET_AQUI');
define('GOOGLE_REDIRECT_URI', 'http://localhost/lactech/google-callback.php');
?>
```

---

## 🔐 Segurança

⚠️ **NUNCA compartilhe publicamente:**
- Client Secret
- Códigos de autorização
- Tokens de acesso

✅ **Pode compartilhar:**
- Client ID (é público mesmo)
- URLs de redirect

---

## 📋 Resumo - O que você precisa me enviar

1. **Client ID** (xxxxx-xxxxx.apps.googleusercontent.com)
2. **Client Secret** (GOCSPX-xxxxx)
3. **URL base do seu sistema** (ex: http://localhost/lactech ou https://seudominio.com)

Ou você pode criar o arquivo `config_google.php` com essas informações e não precisa me enviar.

---

## 🚀 Depois de configurar

Após adicionar as credenciais:
1. O botão "Vincular Conta Google" abrirá o popup do Google
2. Usuário autorizará o acesso
3. Conta será vinculada automaticamente
4. OTPs serão enviados para o e-mail Google vinculado



