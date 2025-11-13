# 🚀 Guia de Instalação - AgroNews360

## 📋 Pré-requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior (ou MariaDB 10.3+)
- Servidor Web (Apache ou Nginx)
- Extensões PHP: PDO, PDO_MySQL, mbstring

## 🔧 Instalação

### 1. Criar Banco de Dados

Execute o script SQL para criar o banco de dados e as tabelas:

```bash
mysql -u root -p < includes/migrations/create_agronews_tables.sql
```

Ou execute manualmente no MySQL:

```sql
-- Conectar ao MySQL
mysql -u root -p

-- Executar o script
source includes/migrations/create_agronews_tables.sql;
```

### 2. Configurar Banco de Dados

Edite o arquivo `includes/config_mysql.php`:

**Local (Desenvolvimento):**
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'agronews');
define('DB_USER', 'root');
define('DB_PASS', '');
```

**Produção (agronews360.online):**
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'agronews');
define('DB_USER', 'agronews_user');
define('DB_PASS', 'sua_senha_segura_aqui');
```

### 3. Configurar Domínio

#### Apache (.htaccess)
O arquivo `.htaccess` já está configurado. Certifique-se de que o mod_rewrite está habilitado:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Nginx
Adicione a configuração do servidor:

```nginx
server {
    listen 80;
    server_name agronews360.online www.agronews360.online;
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

    # Bloquear acesso a arquivos sensíveis
    location ~ /(includes|api|\.htaccess) {
        deny all;
    }
}
```

### 4. Configurar Permissões

```bash
# Dar permissão de escrita para uploads (se necessário)
chmod 755 assets/img
chmod 644 .htaccess
chmod 644 includes/config_mysql.php
```

### 5. Configurar SSL (HTTPS)

Em produção, configure SSL para o domínio `agronews360.online`:

1. Obter certificado SSL (Let's Encrypt recomendado)
2. Configurar no servidor web
3. Descomentar as regras de redirecionamento HTTPS no `.htaccess`

## 🔐 Segurança

### Alterar Senha do Admin

⚠️ **IMPORTANTE:** Alterar a senha do usuário administrador padrão:

```sql
-- Conectar ao banco
USE agronews;

-- Alterar senha (substituir 'nova_senha_segura' pela senha desejada)
UPDATE users 
SET password = '$2y$10$...' -- Gerar hash com password_hash('nova_senha_segura', PASSWORD_DEFAULT)
WHERE email = 'admin@agronews360.online';
```

Ou usar PHP para gerar o hash:

```php
<?php
echo password_hash('sua_nova_senha_aqui', PASSWORD_DEFAULT);
?>
```

### Configurações de Segurança

1. **Não expor credenciais** em repositórios públicos
2. **Habilitar HTTPS** em produção
3. **Configurar firewall** no servidor
4. **Fazer backups regulares** do banco de dados
5. **Atualizar senhas** regularmente

## 📁 Estrutura de Arquivos

```
agronews360/
├── api/
│   └── agronews.php          # API REST
├── assets/
│   └── img/                  # Imagens
├── includes/
│   ├── config_mysql.php      # Configuração do banco
│   ├── Database.class.php    # Classe de banco de dados
│   └── migrations/
│       └── create_agronews_tables.sql
├── index.php                 # Página principal
├── noticia.php               # Página de detalhe
├── .htaccess                 # Configuração Apache
├── README.md                 # Documentação
└── INSTALACAO.md             # Este arquivo
```

## 🧪 Testar Instalação

1. Acesse: `http://localhost/agronews360/` ou `https://agronews360.online/`
2. Verifique se a página carrega sem erros
3. Teste a API: `http://localhost/agronews360/api/agronews.php?action=get_categories`

## 📝 Próximos Passos

1. **Adicionar conteúdo:** Cadastrar notícias, cotações e dados climáticos
2. **Configurar integrações:** APIs de cotações e clima (opcional)
3. **Personalizar design:** Ajustar cores, logo e layout
4. **Criar painel admin:** Interface para gerenciar conteúdo (futuro)

## 🆘 Suporte

Para suporte, entre em contato: contato@agronews360.online

## 📄 Licença

Proprietário - AgroNews360






