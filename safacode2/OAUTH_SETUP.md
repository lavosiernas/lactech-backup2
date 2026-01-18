# Configuração OAuth - Google e GitHub

Para usar login com Google e GitHub, você precisa configurar as credenciais OAuth.

## 1. Atualizar Banco de Dados

Execute o migration para adicionar campos OAuth:

```sql
-- Execute o arquivo: database/migration_oauth.sql
```

Ou execute manualmente:

```sql
USE safecode;
ALTER TABLE users 
  MODIFY COLUMN password_hash VARCHAR(255) NULL,
  ADD COLUMN provider VARCHAR(50) NULL COMMENT 'google, github, email',
  ADD COLUMN provider_id VARCHAR(255) NULL COMMENT 'ID do usuário no provider',
  ADD INDEX idx_provider (provider, provider_id);
```

## 2. Configurar Google OAuth

1. Acesse [Google Cloud Console](https://console.cloud.google.com/)
2. Crie um novo projeto ou selecione um existente
3. Ative a API "Google+ API"
4. Vá em "Credenciais" > "Criar credenciais" > "ID do cliente OAuth"
5. Configure:
   - Tipo: Aplicativo da Web
   - URLs de redirecionamento autorizados: 
     - Desenvolvimento: `http://localhost/safecode/api/oauth.php?action=callback&provider=google`
     - Produção: `https://safenode.cloud/safecode/api/oauth.php?action=callback&provider=google`
6. Copie o **Client ID** e **Client Secret**

## 3. Configurar GitHub OAuth

1. Acesse [GitHub Developer Settings](https://github.com/settings/developers)
2. Clique em "New OAuth App"
3. Configure:
   - Application name: `SafeCode IDE`
   - Homepage URL: `https://safenode.cloud/safecode`
   - Authorization callback URL: `https://safenode.cloud/safecode/api/oauth.php?action=callback&provider=github`
4. Copie o **Client ID** e gere um **Client Secret**

## 4. Configurar Variáveis de Ambiente

No servidor PHP, configure as variáveis de ambiente:

### Opção 1: Arquivo .env (recomendado para produção)

Crie um arquivo `.env` na pasta `api/`:

```env
GOOGLE_CLIENT_ID=seu-google-client-id
GOOGLE_CLIENT_SECRET=seu-google-client-secret
GITHUB_CLIENT_ID=seu-github-client-id
GITHUB_CLIENT_SECRET=seu-github-client-secret
```

E adicione ao início de `api/oauth.php`:

```php
// Carregar .env (use uma biblioteca como vlucas/phpdotenv)
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
```

### Opção 2: Direto no Apache/PHP

No arquivo `.htaccess` ou configuração do Apache:

```apache
SetEnv GOOGLE_CLIENT_ID "seu-google-client-id"
SetEnv GOOGLE_CLIENT_SECRET "seu-google-client-secret"
SetEnv GITHUB_CLIENT_ID "seu-github-client-id"
SetEnv GITHUB_CLIENT_SECRET "seu-github-client-secret"
```

### Opção 3: PHP.ini (não recomendado para produção)

No `php.ini`:

```ini
[PHP]
GOOGLE_CLIENT_ID=seu-google-client-id
GOOGLE_CLIENT_SECRET=seu-google-client-secret
GITHUB_CLIENT_ID=seu-github-client-id
GITHUB_CLIENT_SECRET=seu-github-client-secret
```

### Opção 4: Temporário para testes (edite api/oauth.php)

Como fallback temporário, você pode definir diretamente no código (não recomendado para produção):

```php
// api/oauth.php - apenas para testes locais
if (!getenv('GOOGLE_CLIENT_ID')) {
    putenv('GOOGLE_CLIENT_ID=seu-client-id-aqui');
    putenv('GOOGLE_CLIENT_SECRET=seu-secret-aqui');
    putenv('GITHUB_CLIENT_ID=seu-client-id-aqui');
    putenv('GITHUB_CLIENT_SECRET=seu-secret-aqui');
}
```

## 5. Testar

1. Acesse a página de login
2. Clique em "Google" ou "GitHub"
3. Você será redirecionado para o provedor
4. Após autorizar, será redirecionado de volta e autenticado automaticamente

## Notas Importantes

- **URLs de callback**: Certifique-se de que as URLs de callback configuradas nos provedores OAuth correspondem exatamente às URLs do seu servidor
- **HTTPS**: Em produção, use HTTPS. Os provedores OAuth podem não funcionar em HTTP
- **Segurança**: Nunca commite credenciais OAuth no repositório. Use variáveis de ambiente ou arquivos de configuração fora do controle de versão

