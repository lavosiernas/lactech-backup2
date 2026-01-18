# Configuração GitHub OAuth - safenode.cloud

## URLs para configurar no GitHub OAuth App

Ao criar/editar seu OAuth App no GitHub, use estas URLs:

### Homepage URL
```
https://safenode.cloud/safecode
```

### Authorization callback URL
```
https://safenode.cloud/safecode/api/oauth.php?action=callback&provider=github
```

## Passos rápidos:

1. Acesse: https://github.com/settings/developers
2. Clique em "OAuth Apps" → "New OAuth App" (ou edite existente)
3. Cole as URLs acima
4. Clique em "Register application"
5. Copie o **Client ID** e gere o **Client Secret**
6. Configure as variáveis de ambiente no servidor:

```apache
# No arquivo safacode2/api/.htaccess ou configuração do Apache
SetEnv GITHUB_CLIENT_ID "seu-client-id-aqui"
SetEnv GITHUB_CLIENT_SECRET "seu-client-secret-aqui"
```

## Para Google OAuth:

Use as mesmas URLs, mas no Google Cloud Console:

- **URL de redirecionamento**: `https://safenode.cloud/safecode/api/oauth.php?action=callback&provider=google`

