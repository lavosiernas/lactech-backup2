# Troubleshooting - Erro de Conexão com API

## Erro: "Erro de conexão. Verifique se o servidor está rodando"

### 1. Verificar se o servidor está rodando

**XAMPP:**
- Abra o XAMPP Control Panel
- Verifique se **Apache** está rodando (botão verde)
- Se não estiver, clique em **Start**

**Verificar manualmente:**
```
http://localhost/safecode/api/test_connection.php
```

Você deve ver um JSON com informações da API.

### 2. Verificar URL da API

A URL padrão é: `/safecode/api`

No código, isso é definido em:
- `src/stores/authStore.ts`: `const API_BASE = import.meta.env.VITE_API_BASE || '/safecode/api';`

**Se estiver usando outro base path**, crie um arquivo `.env` na raiz do projeto:

```env
VITE_API_BASE=/seu/path/api
```

### 3. Verificar se os arquivos PHP existem

Os arquivos devem estar em:
```
safacode2/
  api/
    config.php
    auth.php
    test_connection.php
```

### 4. Verificar permissões do banco de dados

Execute o teste de conexão:
```
http://localhost/safecode/api/test_connection.php
```

Se mostrar erro de banco:
- Verifique se o MySQL está rodando no XAMPP
- Verifique as credenciais em `api/config.php`:
  ```php
  define('DB_HOST', 'localhost');
  define('DB_NAME', 'safecode');
  define('DB_USER', 'root');
  define('DB_PASS', ''); // Vazio por padrão no XAMPP
  ```

### 5. Verificar logs de erro

**Apache Error Log (XAMPP):**
```
C:\xampp\apache\logs\error.log
```

**PHP Error Log:**
```
C:\xampp\php\logs\php_error_log
```

### 6. Testar API manualmente

**Teste de Login via cURL:**
```bash
curl -X POST http://localhost/safecode/api/auth.php?action=login \
  -H "Content-Type: application/json" \
  -d '{"email":"teste@safecode.test","password":"teste123"}'
```

**Ou via navegador (teste de conexão):**
```
http://localhost/safecode/api/test_connection.php
```

### 7. Problemas comuns

#### Erro 404 - Arquivo não encontrado
- Verifique se a pasta `api/` está em `safacode2/api/`
- Verifique a configuração do Apache para servir PHP

#### Erro 500 - Erro interno do servidor
- Verifique os logs do Apache
- Verifique se o PHP está habilitado
- Verifique sintaxe PHP dos arquivos

#### Erro CORS
- Os headers CORS já estão configurados em `config.php`
- Se ainda houver problemas, verifique o `.htaccess` em `api/`

#### Banco de dados não conecta
- Verifique se o banco `safecode` existe
- Execute o schema: `database/complete_schema.sql`
- Verifique credenciais em `api/config.php`

### 8. Checklist rápido

- [ ] Apache está rodando no XAMPP
- [ ] MySQL está rodando no XAMPP
- [ ] Banco `safecode` existe
- [ ] Arquivos em `safacode2/api/` estão acessíveis
- [ ] URL correta: `http://localhost/safecode/api/test_connection.php` funciona
- [ ] Sem erros no console do navegador (F12)

### 9. Se ainda não funcionar

1. Abra o **Console do Navegador** (F12)
2. Vá na aba **Network**
3. Tente fazer login
4. Veja qual requisição falhou e o erro exato
5. Compartilhe o erro para debug

### URLs importantes para teste

- Teste de conexão: `http://localhost/safecode/api/test_connection.php`
- Criar usuário teste: `http://localhost/safecode/database/create_test_user_php.php`
- Login page: `http://localhost/safecode/login`

