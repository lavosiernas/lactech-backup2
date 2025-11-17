# Configuração do Google Cloud Console - Cliente OAuth para Lactech

Este guia explica como configurar um segundo cliente OAuth no Google Cloud Console especificamente para o sistema Lactech.

## 📋 Pré-requisitos

- Conta Google com acesso ao Google Cloud Console
- Projeto existente no Google Cloud (ou criar um novo)
- Acesso ao domínio onde o Lactech está hospedado

## 🔧 Passo a Passo

### 1. Acessar o Google Cloud Console

1. Acesse: https://console.cloud.google.com/
2. Selecione o projeto desejado (ou crie um novo)
3. No menu lateral, vá em **APIs e Serviços** > **Credenciais**

### 2. Criar Novo Cliente OAuth 2.0

1. Clique em **+ CRIAR CREDENCIAIS** > **ID do cliente do OAuth**
2. Se a tela de consentimento OAuth ainda não estiver configurada, você será solicitado a configurá-la primeiro

### 3. Configurar Tela de Consentimento OAuth (se necessário)

1. **Tipo de usuário**: Escolha **Externo** (para usuários fora da organização)
2. **Nome do aplicativo**: `Lactech - Sistema de Gestão`
3. **Email de suporte do usuário**: Seu email
4. **Domínios autorizados**: Adicione seu domínio (ex: `lactechsys.com`)
5. Clique em **Salvar e continuar**
6. **Escopos**: Adicione:
   - `email`
   - `profile`
   - `openid`
7. Clique em **Salvar e continuar**
8. **Usuários de teste**: Adicione emails de teste (opcional)
9. Clique em **Salvar e continuar**
10. Revise e clique em **Voltar ao painel**

### 4. Configurar o Cliente OAuth

Na tela **Criar ID do cliente do OAuth**:

#### Tipo de aplicativo
- Selecione: **Aplicativo da Web**

#### Nome
- Digite: `lactech-oauth-client` (ou outro nome de sua preferência)

#### Origens JavaScript autorizadas
Adicione as URLs onde o login será iniciado:

**Para produção:**
```
https://lactechsys.com
https://www.lactechsys.com
```

**Para desenvolvimento local (se usar HTTPS):**
```
https://localhost
https://127.0.0.1
```

#### URIs de redirecionamento autorizados
Adicione as URLs de callback:

**Para produção:**
```
https://lactechsys.com/google-login-callback.php
https://www.lactechsys.com/google-login-callback.php
https://lactechsys.com/agronews360/api/auth.php?action=google_callback_lactech
https://www.lactechsys.com/agronews360/api/auth.php?action=google_callback_lactech
```

**Para desenvolvimento local (se usar HTTPS):**
```
https://localhost/google-login-callback.php
https://127.0.0.1/google-login-callback.php
```

### 5. Obter Credenciais

Após criar o cliente:

1. **ID do cliente**: Copie o Client ID (formato: `xxxxx-xxxxx.apps.googleusercontent.com`)
2. **Segredo do cliente**: Clique em **Mostrar** e copie o Client Secret (formato: `GOCSPX-xxxxx`)

⚠️ **IMPORTANTE**: Guarde essas credenciais com segurança!

### 6. Configurar no Sistema

#### Opção 1: Arquivo de Configuração

Crie ou edite o arquivo: `lactech/includes/config_google.php`

```php
<?php
/**
 * Configuração Google OAuth - LACTECH
 * Cliente OAuth específico para login no sistema Lactech
 */

// Carregar variáveis de ambiente se disponível
$envLoaderPath = __DIR__ . '/env_loader.php';
if (file_exists($envLoaderPath)) {
    require_once $envLoaderPath;
}

// Função auxiliar para obter variável de ambiente
function getEnvValue($key, $default = null) {
    if (function_exists('env')) {
        return env($key, $default);
    }
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }
    return $value !== null ? $value : $default;
}

// Obter credenciais do ambiente ou definir diretamente
$googleClientId = getEnvValue('LACTECH_GOOGLE_CLIENT_ID');
$googleClientSecret = getEnvValue('LACTECH_GOOGLE_CLIENT_SECRET');

// Se não estiver no ambiente, definir diretamente (NÃO RECOMENDADO PARA PRODUÇÃO)
if (empty($googleClientId)) {
    // SUBSTITUA PELO SEU CLIENT ID
    define('GOOGLE_CLIENT_ID', 'SEU_CLIENT_ID_AQUI.apps.googleusercontent.com');
} else {
    define('GOOGLE_CLIENT_ID', $googleClientId);
}

if (empty($googleClientSecret)) {
    // SUBSTITUA PELO SEU CLIENT SECRET
    define('GOOGLE_CLIENT_SECRET', 'SEU_CLIENT_SECRET_AQUI');
} else {
    define('GOOGLE_CLIENT_SECRET', $googleClientSecret);
}

// URL de redirecionamento (será detectada automaticamente)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('GOOGLE_REDIRECT_URI', $protocol . '://' . $host . '/google-login-callback.php');

// Escopos OAuth
define('GOOGLE_SCOPES', 'email profile openid');
```

#### Opção 2: Variáveis de Ambiente (Recomendado)

Adicione ao arquivo `.env`:

```env
# Google OAuth - Lactech
LACTECH_GOOGLE_CLIENT_ID=seu_client_id_aqui.apps.googleusercontent.com
LACTECH_GOOGLE_CLIENT_SECRET=GOCSPX-seu_client_secret_aqui
```

### 7. Verificar Configuração

1. Acesse a página de login do AgroNews: `https://seu-dominio.com/agronews360/login.php`
2. Clique em **Acessar com Lactech**
3. Clique em **Entrar com Google** (dentro da seção do Lactech)
4. Deve redirecionar para o Google e depois para o sistema Lactech

## 🔒 Segurança

- **NUNCA** commite credenciais no Git
- Use variáveis de ambiente em produção
- Mantenha o Client Secret seguro
- Revise periodicamente os domínios autorizados

## 📝 Notas Importantes

1. **Domínios diferentes**: O cliente OAuth do Lactech é separado do cliente do AgroNews
2. **URLs de callback**: Certifique-se de que todas as URLs de callback estão configuradas corretamente
3. **Tempo de propagação**: Mudanças no Google Cloud podem levar alguns minutos para entrar em vigor
4. **Ambiente local**: OAuth do Google requer HTTPS. Para desenvolvimento local, use ferramentas como ngrok ou configure SSL local

## 🆘 Solução de Problemas

### Erro: "redirect_uri_mismatch"
- Verifique se a URL de callback está exatamente como configurada no Google Cloud
- Certifique-se de incluir `http://` ou `https://`
- Verifique se não há barras extras no final

### Erro: "invalid_client"
- Verifique se o Client ID e Client Secret estão corretos
- Certifique-se de que está usando as credenciais do cliente correto (Lactech, não AgroNews)

### Login não redireciona
- Verifique se o arquivo `config_google.php` está sendo carregado corretamente
- Verifique os logs do servidor para erros PHP
- Certifique-se de que a sessão está funcionando corretamente

## 📚 Recursos Adicionais

- [Documentação OAuth 2.0 do Google](https://developers.google.com/identity/protocols/oauth2)
- [Google Cloud Console](https://console.cloud.google.com/)
- [Troubleshooting OAuth](https://developers.google.com/identity/protocols/oauth2/policies#troubleshooting)





