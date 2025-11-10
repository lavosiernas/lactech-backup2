# 🔒 Configuração de Segurança - LacTech

## ⚠️ IMPORTANTE: Configuração Inicial

Este sistema agora usa variáveis de ambiente para proteger credenciais sensíveis. **SEMPRE** configure o arquivo `.env` antes de usar o sistema.

## 📋 Passos para Configuração

### 1. Criar arquivo `.env`

Crie um arquivo chamado `.env` na raiz do projeto (`lactech/.env`) com o seguinte conteúdo:

```env
# =====================================================
# BANCO DE DADOS - AMBIENTE LOCAL
# =====================================================
DB_HOST_LOCAL=localhost
DB_NAME_LOCAL=lactech_lgmato
DB_USER_LOCAL=root
DB_PASS_LOCAL=

# =====================================================
# BANCO DE DADOS - AMBIENTE DE PRODUÇÃO
# =====================================================
DB_HOST_PROD=seu_host_producao
DB_NAME_PROD=seu_banco_producao
DB_USER_PROD=seu_usuario_producao
DB_PASS_PROD=sua_senha_producao

# =====================================================
# CONFIGURAÇÕES GOOGLE OAUTH
# =====================================================
GOOGLE_CLIENT_ID=seu_google_client_id
GOOGLE_CLIENT_SECRET=seu_google_client_secret
GOOGLE_REDIRECT_URI=https://seu-dominio.com/google-callback.php
GOOGLE_LOGIN_REDIRECT_URI=https://seu-dominio.com/google-login-callback.php
GOOGLE_SCOPES=email profile

# =====================================================
# URL BASE - PRODUÇÃO
# =====================================================
BASE_URL_PROD=https://seu-dominio.com/
```

### 2. Preencher com seus dados reais

Substitua os valores de exemplo pelos seus dados reais:
- **Banco de dados**: Credenciais do seu banco MySQL
- **Google OAuth**: Credenciais do Google Console
- **URLs**: URLs do seu domínio

### 3. Proteger o arquivo `.env`

O arquivo `.env` já está configurado no `.gitignore` e **NUNCA** deve ser commitado no repositório.

## 🔐 Segurança Implementada

### ✅ Proteção de Credenciais
- Todas as credenciais foram removidas dos arquivos de código
- Uso de variáveis de ambiente via arquivo `.env`
- Arquivo `.env` protegido pelo `.gitignore`

### ✅ Prepared Statements
- Todos os queries usam prepared statements com placeholders (`?`)
- Previne SQL Injection

### ✅ Proteção CSRF
- Sistema de tokens CSRF implementado
- Use `csrfField()` em formulários HTML
- Use `validateCsrfToken()` ou `requireCsrfToken()` em processamento

**Exemplo de uso em formulário:**
```php
<?php require_once 'includes/csrf.php'; ?>
<form method="POST">
    <?= csrfField() ?>
    <!-- outros campos -->
</form>
```

**Exemplo de validação:**
```php
<?php 
require_once 'includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken(); // Valida e bloqueia se inválido
    // processar formulário
}
?>
```

### ✅ Proteção XSS
- Use `htmlspecialchars()` ou a função `sanitize()` antes de exibir dados do usuário
- Função `sanitize()` disponível em `includes/functions.php`

**Exemplo:**
```php
<?php
require_once 'includes/functions.php';
echo sanitize($userInput); // Protege contra XSS
?>
```

## 📝 Notas Importantes

1. **Nunca commite** arquivos com credenciais reais
2. **Sempre use** prepared statements para queries SQL
3. **Sempre valide** tokens CSRF em formulários críticos
4. **Sempre use** `htmlspecialchars()` ou `sanitize()` ao exibir dados do usuário

## 🚨 Se o sistema não funcionar

Se você receber erros sobre configuração não encontrada:
1. Verifique se o arquivo `.env` existe na raiz do projeto
2. Verifique se todas as variáveis necessárias estão preenchidas
3. Verifique se o arquivo `.env` tem permissões de leitura corretas

