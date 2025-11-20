# Configuração de OAuth - AgroNews360 e Lactech

Este documento explica a estrutura de autenticação OAuth com Google para o sistema AgroNews360 e Lactech.

## 📊 Visão Geral

O sistema possui **dois clientes OAuth separados**:

1. **Cliente AgroNews** - Para login público no portal de notícias
2. **Cliente Lactech** - Para login no sistema de gestão (requer conta)

## 🔄 Fluxo de Autenticação

### Login no AgroNews (Público)

1. Usuário acessa `/agronews360/login.php`
2. Clica em **"Entrar com Google"** (botão principal)
3. Usa credenciais do **Cliente OAuth do AgroNews**
4. Após autenticação, acessa o portal como visitante ou usuário logado

### Login no Lactech (Restrito)

1. Usuário acessa `/agronews360/login.php`
2. Clica em **"Acessar com Lactech"**
3. Aparece formulário de email/senha + botão **"Entrar com Google"** (Lactech)
4. Clica em **"Entrar com Google"** (dentro da seção Lactech)
5. Usa credenciais do **Cliente OAuth do Lactech**
6. Após autenticação, redireciona para o sistema Lactech

## 📁 Estrutura de Arquivos

```
lactech/
├── includes/
│   └── config_google.php          # Configuração OAuth do Lactech
├── agronews360/
│   ├── includes/
│   │   └── config_google.php      # Configuração OAuth do AgroNews
│   ├── api/
│   │   └── auth.php                # API de autenticação (suporta ambos)
│   └── login.php                   # Página de login (dois botões)
├── google-login-callback.php        # Callback do Lactech
└── GOOGLE_CLOUD_SETUP_LACTECH.md   # Guia de configuração
```

## ⚙️ Configuração

### Cliente OAuth do AgroNews

**Arquivo**: `lactech/agronews360/includes/config_google.php`

```php
define('GOOGLE_CLIENT_ID', 'agronews-client-id.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'GOCSPX-agronews-secret');
define('GOOGLE_REDIRECT_URI', 'https://seu-dominio.com/agronews360/api/auth.php?action=google_callback');
```

### Cliente OAuth do Lactech

**Arquivo**: `lactech/includes/config_google.php`

```php
define('GOOGLE_CLIENT_ID', 'lactech-client-id.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'GOCSPX-lactech-secret');
define('GOOGLE_REDIRECT_URI', 'https://seu-dominio.com/google-login-callback.php');
```

## 🔑 Diferenças entre os Clientes

| Aspecto | AgroNews | Lactech |
|---------|----------|---------|
| **Uso** | Login público | Login restrito |
| **Acesso** | Qualquer pessoa | Apenas usuários cadastrados |
| **Callback** | `/agronews360/api/auth.php?action=google_callback` | `/google-login-callback.php` |
| **Redirecionamento** | Portal AgroNews | Sistema Lactech |
| **Configuração** | `agronews360/includes/config_google.php` | `includes/config_google.php` |

## 🛠️ Como Funciona no Código

### 1. Página de Login (`login.php`)

```javascript
// Botão Google do AgroNews
googleLoginBtnAgronews → api/auth.php?action=get_google_auth_url&type=agronews

// Botão Google do Lactech (aparece quando clica em "Acessar com Lactech")
googleLoginBtnLactech → api/auth.php?action=get_google_auth_url&type=lactech
```

### 2. API de Autenticação (`api/auth.php`)

A função `getGoogleAuthUrl()` detecta o tipo:

```php
$type = $_GET['type'] ?? 'agronews';

if ($type === 'lactech') {
    // Carrega config do Lactech
    $googleConfigPath = __DIR__ . '/../../includes/config_google.php';
    $redirectUri = '.../google-login-callback.php';
} else {
    // Carrega config do AgroNews
    $googleConfigPath = __DIR__ . '/../includes/config_google.php';
    $redirectUri = '.../api/auth.php?action=google_callback';
}
```

### 3. Callbacks

- **AgroNews**: `handleGoogleCallback()` - Cria sessão no AgroNews
- **Lactech**: `handleGoogleCallbackLactech()` - Redireciona para callback do Lactech

## 📝 Checklist de Configuração

- [ ] Criar cliente OAuth no Google Cloud para AgroNews
- [ ] Criar cliente OAuth no Google Cloud para Lactech
- [ ] Configurar `agronews360/includes/config_google.php`
- [ ] Configurar `includes/config_google.php`
- [ ] Adicionar URLs de callback no Google Cloud Console
- [ ] Testar login do AgroNews
- [ ] Testar login do Lactech
- [ ] Verificar redirecionamentos

## 🆘 Troubleshooting

### Botão Google não aparece
- Verifique se o JavaScript está carregando
- Verifique console do navegador para erros

### Erro ao clicar em "Entrar com Google"
- Verifique se as credenciais estão configuradas
- Verifique se as URLs de callback estão corretas no Google Cloud
- Verifique logs do servidor

### Login do Lactech não redireciona
- Verifique se `google-login-callback.php` existe
- Verifique se a URL de callback está configurada corretamente
- Verifique se a sessão está funcionando

## 📚 Documentação Relacionada

- [GOOGLE_CLOUD_SETUP_LACTECH.md](./GOOGLE_CLOUD_SETUP_LACTECH.md) - Guia detalhado de configuração do Google Cloud
- [README_GOOGLE_OAUTH.md](./README_GOOGLE_OAUTH.md) - Documentação geral do OAuth








