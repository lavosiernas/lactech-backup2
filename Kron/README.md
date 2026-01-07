# KRON - Sistema de Conexão Cross-Domain

## 📋 Estrutura Criada

### Banco de Dados
- `database/create_kron_ecosystem.sql` - Script completo do banco

### Sistema de Autenticação
- `includes/config.php` - Configuração do banco de dados
- `includes/GoogleOAuth.php` - Integração com Google OAuth
- `login.php` - Página de login (email/senha + Google)
- `register.php` - Página de registro (email/senha + Google)
- `google-auth.php` - Inicia autenticação Google
- `google-callback.php` - Callback do Google OAuth
- `logout.php` - Encerra sessão

## 🚀 Como Usar

### 1. Configurar Banco de Dados
1. Execute o script `database/create_kron_ecosystem.sql` no servidor
2. Ajuste as credenciais em `includes/config.php` se necessário

### 2. Configurar Google OAuth
1. Adicione a URL de callback no Google Console:
   - **Produção:** `https://kronx.sbs/google-callback.php`
   - **Local:** `http://localhost/lactech/kron/google-callback.php`

### 3. Acessar
- **Login:** `login.php`
- **Registro:** `register.php`
- **Dashboard:** `dashboard/index.php` (a ser criado)

## 🔐 Funcionalidades

### Login
- Login com email e senha
- Login com Google OAuth
- Validação de conta ativa
- Gerenciamento de sessões

### Registro
- Registro com email e senha
- Registro com Google OAuth
- Validação de email único
- Criação automática de sessão

### Segurança
- Senhas hashadas com `password_hash()`
- Tokens de sessão únicos
- Sessões expiram em 30 dias
- Logs de atividades

## 📝 Próximos Passos

1. Criar dashboard (`dashboard/index.php`)
2. Implementar sistema de conexão com SafeNode/LacTech
3. Criar APIs de conexão
4. Implementar QR Code para conexão

