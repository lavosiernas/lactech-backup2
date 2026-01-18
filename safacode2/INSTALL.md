# SafeCode IDE - Instalação e Configuração

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior (ou MariaDB)
- Apache/Nginx com mod_rewrite habilitado
- Node.js 18+ e npm

## Instalação

### 1. Instalar dependências do frontend

```bash
cd safacode2
npm install
```

### 2. Configurar banco de dados

1. Crie o banco de dados executando o script SQL:
   ```bash
   mysql -u root -p < database/schema.sql
   ```

   Ou importe o arquivo `database/schema.sql` no phpMyAdmin.

2. Configure as credenciais do banco no arquivo `api/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'safecode_ide');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

3. **IMPORTANTE**: Altere o `JWT_SECRET` no arquivo `api/config.php` para uma chave segura em produção:
   ```php
   define('JWT_SECRET', 'sua-chave-secreta-aqui-mude-em-producao');
   ```

### 3. Build do frontend

```bash
npm run build
```

### 4. Configurar servidor web

#### Apache

Certifique-se de que o mod_rewrite está habilitado e que o `.htaccess` está funcionando.

#### Nginx

Adicione a configuração de rewrite para as rotas da API:

```nginx
location /safecode/api {
    try_files $uri $uri/ /safecode/api/auth.php?$query_string;
}
```

### 5. Acessar a aplicação

- URL: `http://localhost/safecode/`
- A primeira vez que acessar, será redirecionado para `/login`
- Crie uma conta ou faça login

## Estrutura de Arquivos

```
safacode2/
├── api/              # Backend PHP
│   ├── config.php    # Configurações do banco e JWT
│   └── auth.php      # Endpoints de autenticação
├── database/         # Scripts SQL
│   └── schema.sql   # Schema do banco de dados
├── src/             # Código fonte React
│   ├── stores/      # Zustand stores
│   │   └── authStore.ts
│   ├── pages/       # Páginas
│   │   └── LoginPage.tsx
│   └── components/  # Componentes React
└── dist/            # Build de produção
```

## API Endpoints

- `POST /api/auth.php?action=register` - Registrar novo usuário
- `POST /api/auth.php?action=login` - Fazer login
- `GET /api/auth.php?action=me` - Obter dados do usuário (requer token)
- `POST /api/auth.php?action=logout` - Logout

## Desenvolvimento

Para desenvolvimento com hot reload:

```bash
npm run dev
```

A aplicação estará disponível em `http://localhost:5173/safecode/`

**Nota**: Em desenvolvimento, você precisará configurar um proxy ou ajustar a URL da API no arquivo `.env`:

```env
VITE_API_BASE=http://localhost/safecode/api
```

## Troubleshooting

### Erro de conexão com banco de dados

- Verifique se o MySQL está rodando
- Confirme as credenciais em `api/config.php`
- Certifique-se de que o banco `safecode_ide` foi criado

### Erro 404 na API

- Verifique se o `.htaccess` está no diretório `api/`
- Confirme que o mod_rewrite está habilitado no Apache
- Verifique as permissões dos arquivos PHP

### CORS errors

- O arquivo `api/config.php` já configura CORS
- Verifique se o `.htaccess` está configurado corretamente

