# KRON - Sistema de Conexão Cross-Domain

## 📋 Estrutura

### Banco de Dados
- `database/governance_structure.sql` - Script de estrutura de governança

### Sistema de Autenticação
- `includes/config.php` - Configuração do banco de dados
- `includes/GoogleOAuth.php` - Integração com Google OAuth
- `google-auth.php` - Inicia autenticação Google
- `google-callback.php` - Callback do Google OAuth
- `logout.php` - Encerra sessão

### APIs
- `api/v1/kron/` - Endpoints de API para sistemas governados

### Classes Core
- `includes/KronJWT.php` - Gerenciador de tokens JWT
- `includes/KronRBAC.php` - Sistema de RBAC hierárquico
- `includes/KronSystemManager.php` - Gerenciador de sistemas
- `includes/KronCommandManager.php` - Gerenciador de comandos

