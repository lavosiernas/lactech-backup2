# Setup do Login Admin - SafeNode Survey

## Passos para configurar o login administrativo

### 1. Criar tabela e usuário admin

Execute o arquivo SQL no banco de dados:

```sql
-- Execute no phpMyAdmin ou linha de comando
source safenode/database/admin-login-table.sql
```

Ou copie e cole o conteúdo do arquivo `admin-login-table.sql` no phpMyAdmin.

### 2. Verificar se foi criado

Execute no banco:
```sql
SELECT * FROM safenode_survey_admin;
```

Você deve ver um registro com:
- username: `admin`
- password_hash: (hash da senha)
- email: `safenodemail@safenode.cloud`

### 3. Credenciais de acesso

- **Senha**: `lnassfnd017852`

## Gerar novo hash (se necessário)

Se precisar mudar a senha ou gerar um novo hash:

```bash
cd safenode/database
php generate-admin-hash.php
```

O script vai mostrar o hash gerado. Use o SQL fornecido para atualizar no banco.

## Segurança

- A senha está armazenada como hash (bcrypt) no banco de dados
- O código PHP usa `password_verify()` para validar a senha
- Não há senha em texto plano no código
- A sessão PHP é usada para manter o usuário logado






