# AgroNews360

Portal de notícias do agronegócio - Sistema independente

**Domínio:** agronews360.online  
**Banco de Dados:** agronews

## Estrutura do Projeto

```
agronews360/
├── api/
│   └── agronews.php          # API principal
├── includes/
│   ├── config_mysql.php      # Configuração do banco de dados
│   ├── Database.class.php    # Classe de banco de dados
│   └── migrations/
│       └── create_agronews_tables.sql  # Script de criação das tabelas
├── assets/
│   └── img/                  # Imagens e recursos
├── index.php                 # Página principal
└── noticia.php               # Página de detalhe da notícia
```

## Instalação

### 1. Criar Banco de Dados

Execute o script SQL:
```bash
mysql -u root -p < includes/migrations/create_agronews_tables.sql
```

Ou importe manualmente o arquivo `includes/migrations/create_agronews_tables.sql` no MySQL.

### 2. Configurar Banco de Dados

Edite o arquivo `includes/config_mysql.php` e configure as credenciais do banco:

```php
// Produção
define('DB_HOST', 'localhost');
define('DB_NAME', 'agronews');
define('DB_USER', 'agronews_user');
define('DB_PASS', 'sua_senha_aqui');
```

### 3. Configurar Domínio

Configure o domínio `agronews360.online` para apontar para esta pasta.

### 4. Configurar Servidor Web

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx
```nginx
server {
    listen 80;
    server_name agronews360.online;
    root /caminho/para/agronews360;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## Uso

### Acesso Público
- URL: `https://agronews360.online`
- Sistema público, não requer autenticação para visualizar notícias

### Acesso Admin (Futuro)
- Criar painel administrativo para gerenciar notícias, cotações e clima
- Usuário padrão: `admin@agronews360.online` / `admin123` (ALTERAR EM PRODUÇÃO!)

## API Endpoints

### Notícias
- `GET api/agronews.php?action=get_articles` - Listar notícias
- `GET api/agronews.php?action=get_featured` - Notícias em destaque
- `GET api/agronews.php?action=get_article&id=1` - Detalhe da notícia
- `POST api/agronews.php?action=create_article` - Criar notícia
- `POST api/agronews.php?action=update_article` - Atualizar notícia
- `DELETE api/agronews.php?action=delete_article&id=1` - Deletar notícia

### Cotações
- `GET api/agronews.php?action=get_quotations` - Listar cotações
- `POST api/agronews.php?action=create_quotation` - Criar cotação

### Clima
- `GET api/agronews.php?action=get_weather` - Previsão do tempo
- `POST api/agronews.php?action=create_weather` - Criar previsão

### Newsletter
- `POST api/agronews.php?action=subscribe_newsletter` - Cadastrar no newsletter

## Banco de Dados

### Tabelas
- `agronews_categories` - Categorias de notícias
- `agronews_articles` - Artigos/notícias
- `agronews_quotations` - Cotações de produtos
- `agronews_weather` - Dados climáticos
- `agronews_comments` - Comentários (opcional)
- `agronews_newsletter` - Newsletter
- `users` - Usuários do sistema

## Segurança

⚠️ **IMPORTANTE:**
1. Alterar senha do usuário admin padrão
2. Configurar credenciais do banco de dados corretamente
3. Habilitar HTTPS em produção
4. Configurar permissões de arquivos corretamente
5. Não expor credenciais em repositórios públicos

## Desenvolvimento

### Adicionar Nova Notícia

```php
POST api/agronews.php?action=create_article
Content-Type: application/json

{
    "title": "Título da Notícia",
    "summary": "Resumo da notícia",
    "content": "Conteúdo completo da notícia...",
    "category_id": 1,
    "is_featured": 1,
    "is_published": 1,
    "published_at": "2024-01-01 10:00:00"
}
```

### Adicionar Cotação

```php
POST api/agronews.php?action=create_quotation
Content-Type: application/json

{
    "product_name": "Leite",
    "product_type": "leite",
    "unit": "litro",
    "price": 3.50,
    "variation": 2.5,
    "variation_type": "up",
    "market": "CEASA",
    "region": "São Paulo",
    "quotation_date": "2024-01-01"
}
```

## Suporte

Para suporte, entre em contato: contato@agronews360.online

## Licença

Proprietário - AgroNews360






