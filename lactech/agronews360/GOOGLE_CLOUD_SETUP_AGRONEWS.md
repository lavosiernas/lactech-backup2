# Configuração do Google Cloud Console - Cliente OAuth para AgroNews360

Este guia explica como configurar o cliente OAuth no Google Cloud Console especificamente para o portal AgroNews360.

## 📋 Pré-requisitos

- Conta Google com acesso ao Google Cloud Console
- Projeto existente no Google Cloud (ou criar um novo)
- Acesso ao domínio onde o AgroNews360 está hospedado

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
2. **Nome do aplicativo**: `AgroNews360`
3. **Email de suporte do usuário**: Seu email
4. **Domínios autorizados**: Adicione seu domínio (ex: `agronews360.online` ou `lactechsys.com`)
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
- Digite: `agronews360` (ou outro nome de sua preferência)

#### Origens JavaScript autorizadas
Adicione as URLs onde o login será iniciado:

**Para produção:**
```
https://lactechsys.com
https://www.lactechsys.com
https://lactechsys.com/agronews360
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
https://lactechsys.com/agronews360/api/auth.php?action=google_callback
https://www.lactechsys.com/agronews360/api/auth.php?action=google_callback
```

**Para desenvolvimento local (se usar HTTPS):**
```
https://localhost/agronews360/api/auth.php?action=google_callback
https://127.0.0.1/agronews360/api/auth.php?action=google_callback
```

### 5. Obter Credenciais

Após criar o cliente:

1. **ID do cliente**: Copie o Client ID (formato: `xxxxx-xxxxx.apps.googleusercontent.com`)
2. **Segredo do cliente**: Clique em **Mostrar** e copie o Client Secret (formato: `GOCSPX-xxxxx`)

⚠️ **IMPORTANTE**: Guarde essas credenciais com segurança!

### 6. Configurar no Sistema

#### Opção 1: Arquivo de Configuração

Crie ou edite o arquivo: `lactech/agronews360/includes/config_google.php`

```php
<?php
/**
 * Configuração Google OAuth - AGRONEWS360
 * Cliente OAuth específico para login no portal AgroNews360
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
$googleClientId = getEnvValue('AGRONEWS_GOOGLE_CLIENT_ID');
$googleClientSecret = getEnvValue('AGRONEWS_GOOGLE_CLIENT_SECRET');

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
define('GOOGLE_REDIRECT_URI', $protocol . '://' . $host . '/agronews360/api/auth.php?action=google_callback');

// Escopos OAuth
define('GOOGLE_SCOPES', 'email profile openid');
```

#### Opção 2: Variáveis de Ambiente (Recomendado)

Adicione ao arquivo `.env`:

```env
# Google OAuth - AgroNews360
AGRONEWS_GOOGLE_CLIENT_ID=seu_client_id_aqui.apps.googleusercontent.com
AGRONEWS_GOOGLE_CLIENT_SECRET=GOCSPX-seu_client_secret_aqui
```

### 7. Verificar Configuração

1. Acesse a página de login: `https://seu-dominio.com/agronews360/login.php`
2. Clique em **Entrar com Google** (botão principal)
3. Deve redirecionar para o Google e depois voltar ao portal AgroNews360 logado

## 🔒 Segurança

- **NUNCA** commite credenciais no Git
- Use variáveis de ambiente em produção
- Mantenha o Client Secret seguro
- Revise periodicamente os domínios autorizados

## 📝 Notas Importantes

1. **URLs de callback**: Certifique-se de que todas as URLs de callback estão configuradas corretamente no Google Cloud Console
2. **Tempo de propagação**: Mudanças no Google Cloud podem levar alguns minutos para entrar em vigor
3. **Ambiente local**: OAuth do Google requer HTTPS. Para desenvolvimento local, use ferramentas como ngrok ou configure SSL local
4. **Domínio**: Se o AgroNews360 estiver em um subdiretório (ex: `/agronews360`), certifique-se de incluir o caminho completo nas URLs de callback

## 🆘 Solução de Problemas

### Erro: "redirect_uri_mismatch"
- Verifique se a URL de callback está exatamente como configurada no Google Cloud Console
- Certifique-se de incluir `http://` ou `https://`
- Verifique se não há barras extras no final
- Certifique-se de incluir o caminho completo: `/agronews360/api/auth.php?action=google_callback`

### Erro: "invalid_client"
- Verifique se o Client ID e Client Secret estão corretos
- Certifique-se de que está usando as credenciais do cliente correto (AgroNews360)

### Login não redireciona
- Verifique se o arquivo `agronews360/includes/config_google.php` existe e está sendo carregado corretamente
- Verifique os logs do servidor para erros PHP
- Certifique-se de que a sessão está funcionando corretamente
- Verifique se a URL de callback no código corresponde exatamente à configurada no Google Cloud

### Botão Google não funciona
- Abra o console do navegador (F12) e verifique se há erros JavaScript
- Verifique se a API `api/auth.php?action=get_google_auth_url&type=agronews` está retornando a URL correta
- Verifique se as credenciais estão configuradas corretamente

## 📚 Recursos Adicionais

- [Documentação OAuth 2.0 do Google](https://developers.google.com/identity/protocols/oauth2)
- [Google Cloud Console](https://console.cloud.google.com/)
- [Troubleshooting OAuth](https://developers.google.com/identity/protocols/oauth2/policies#troubleshooting)

## ✅ Checklist de Configuração

- [ ] Criar cliente OAuth no Google Cloud Console
- [ ] Configurar tela de consentimento OAuth
- [ ] Adicionar origens JavaScript autorizadas
- [ ] Adicionar URIs de redirecionamento autorizados
- [ ] Copiar Client ID e Client Secret
- [ ] Criar arquivo `agronews360/includes/config_google.php`
- [ ] Configurar credenciais no arquivo ou variáveis de ambiente
- [ ] Testar login com Google
- [ ] Verificar redirecionamento após login



