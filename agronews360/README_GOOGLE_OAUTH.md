# Configuração Google OAuth - AgroNews360

## 🔐 Sistema Independente

O AgroNews360 possui sua **própria configuração Google OAuth**, independente do Lactech. Isso permite:

- ✅ Login Google totalmente independente
- ✅ Não depende das credenciais restritas do Lactech
- ✅ Mantém integração/ecossistema com Lactech (sincronização opcional)

## 📋 Passos para Configuração

### 1. Criar Projeto no Google Cloud Console

1. Acesse: https://console.cloud.google.com/
2. Crie um **novo projeto** ou selecione um existente
3. Nome sugerido: `AgroNews360` ou `agronews360-oauth`

### 2. Configurar OAuth 2.0

1. Vá em **APIs & Services** > **Credentials**
2. Clique em **Create Credentials** > **OAuth client ID**
3. Se solicitado, configure a **OAuth consent screen**:
   - Tipo: **External** (ou Internal se for G Suite)
   - Nome: `AgroNews360`
   - Email de suporte: seu email
   - Scopes: `email`, `profile`

### 3. Criar OAuth Client ID

1. Tipo de aplicativo: **Web application**
2. Nome: `AgroNews360 Web Client`
3. **Authorized redirect URIs**: Adicione:
   ```
   https://agronews360.online/agronews360/api/auth.php?action=google_callback
   ```
   (Ajuste conforme seu domínio)

### 4. Obter Credenciais

Após criar, você receberá:
- **Client ID**: `xxxxx.apps.googleusercontent.com`
- **Client Secret**: `xxxxx`

### 5. Configurar no AgroNews360

Edite o arquivo `agronews360/includes/config_google.php`:

```php
// Opção 1: Variáveis de ambiente (RECOMENDADO)
// Configure no servidor:
// AGRONEWS_GOOGLE_CLIENT_ID=seu_client_id
// AGRONEWS_GOOGLE_CLIENT_SECRET=seu_client_secret

// Opção 2: Direto no arquivo (NÃO RECOMENDADO para produção)
define('GOOGLE_CLIENT_ID', 'seu_client_id_aqui');
define('GOOGLE_CLIENT_SECRET', 'seu_client_secret_aqui');
```

## 🔄 Integração com Lactech (Ecossistema)

O sistema mantém integração opcional com Lactech:

1. **Login Google Independente**: Cria usuário direto no AgroNews360
2. **Sincronização Opcional**: Se o email corresponder a um usuário do Lactech, sincroniza automaticamente
3. **Campo `lactech_user_id`**: Mantém referência ao usuário do Lactech (se existir)

## 📊 Estrutura do Banco

A tabela `users` do AgroNews360 possui:

```sql
- `google_id`: ID único do Google (UNIQUE)
- `google_picture`: URL da foto de perfil
- `lactech_user_id`: ID do usuário no Lactech (opcional, para ecossistema)
```

## ✅ Teste

1. Acesse `login.php`
2. Clique em "Entrar com Google"
3. Autorize o acesso
4. Deve redirecionar e criar/login do usuário

## 🔒 Segurança

- ✅ **NUNCA** commite `config_google.php` com credenciais
- ✅ Use variáveis de ambiente em produção
- ✅ Mantenha o Client Secret seguro
- ✅ Configure redirect URIs corretamente

## 🆘 Troubleshooting

### Erro: "Credenciais do Google não configuradas"
- Verifique se `config_google.php` existe
- Verifique se as credenciais estão definidas

### Erro: "Redirect URI mismatch"
- Verifique se o redirect URI no Google Console está exatamente igual ao configurado
- Inclua o protocolo (https://) e o caminho completo

### Erro: "Estado de segurança inválido"
- Limpe cookies/sessão e tente novamente
- Verifique se a sessão está funcionando



