# SafeCode IDE - Banco de Dados

## Instalação

1. **Criar o banco de dados:**
   ```sql
   mysql -u root -p < database/schema.sql
   ```

   Ou execute o arquivo `schema.sql` no phpMyAdmin ou cliente MySQL.

2. **Configurar conexão:**
   Edite o arquivo `api/config.php` e ajuste as credenciais do banco:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'safecode_ide');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

3. **Configurar JWT Secret:**
   No arquivo `api/config.php`, altere o `JWT_SECRET` para uma chave segura em produção:
   ```php
   define('JWT_SECRET', 'sua-chave-secreta-aqui');
   ```

## Estrutura

- **users**: Tabela de usuários
- **user_sessions**: Sessões de usuário (opcional)
- **user_projects**: Projetos salvos por usuário (opcional)

## API Endpoints

- `POST /api/auth.php?action=register` - Registrar novo usuário
- `POST /api/auth.php?action=login` - Fazer login
- `GET /api/auth.php?action=me` - Obter dados do usuário atual (requer token)
- `POST /api/auth.php?action=logout` - Logout

