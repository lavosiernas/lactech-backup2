# SafeNode - Guia de Deploy

Este documento explica como fazer deploy das melhorias para a hospedagem.

## ✅ Arquivos que DEVEM ir para produção

### Estrutura MVC
- ✅ `src/` - Toda a estrutura MVC (Controllers, Models, Services)
- ✅ `views/` - Views (se existirem)

### Documentação
- ✅ `CODE_STYLE.md`
- ✅ `README_TESTS.md`
- ✅ `REFACTORING_GUIDE.md`
- ✅ `IMPLEMENTATION_SUMMARY.md`
- ✅ `DEPLOY.md` (este arquivo)

### Configuração
- ✅ `composer.json` - Dependências do projeto
- ✅ `.php-cs-fixer.php` - Configuração do code style
- ✅ `phpstan.neon` - Configuração do PHPStan

### CI/CD
- ✅ `.github/workflows/ci.yml` - Pipeline do GitHub Actions

## ❌ Arquivos que NÃO devem ir para produção

### Testes
- ❌ `tests/` - Pasta completa de testes
- ❌ `phpunit.xml` - Configuração de testes
- ❌ `coverage/` - Relatórios de cobertura

### Desenvolvimento
- ❌ `vendor/` - Será instalado no servidor via Composer
- ❌ `composer.lock` - Pode ir, mas será regenerado
- ❌ `.phpunit.result.cache` - Cache de testes
- ❌ `setup-path.ps1` - Script local

## 📋 Checklist de Deploy

### 1. Antes de Enviar

- [ ] Executar testes localmente: `composer test`
- [ ] Verificar se não há erros: `composer phpstan`
- [ ] Verificar `.gitignore` está correto
- [ ] Fazer backup do servidor atual

### 2. No Servidor (Hospedagem)

```bash
# 1. Fazer backup
# (faça backup do banco de dados e arquivos)

# 2. Enviar arquivos via FTP/SFTP ou Git
# (não envie vendor/, tests/, coverage/)

# 3. Conectar via SSH (se disponível)
cd /caminho/do/safenode

# 4. Instalar dependências de produção
composer install --no-dev --optimize-autoloader

# 5. Verificar permissões
chmod 755 -R .
chmod 644 -R *.php
```

### 3. Se não tiver SSH (apenas FTP)

1. **Envie os arquivos** (exceto os listados em ❌)
2. **No servidor**, se tiver acesso ao terminal/cPanel:
   - Instale o Composer (se não tiver)
   - Execute: `composer install --no-dev`

### 4. Verificações Pós-Deploy

- [ ] Site carrega normalmente
- [ ] Funcionalidades principais funcionam
- [ ] Sem erros no log do servidor
- [ ] Banco de dados conecta corretamente

## 🔧 Configuração do Servidor

### Requisitos Mínimos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Extensões PHP: `pdo_mysql`, `mbstring`, `json`, `zip`

### Composer no Servidor

Se o servidor não tiver Composer instalado:

```bash
# Instalar Composer globalmente
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer
```

## ⚠️ Importante

1. **Nunca envie `vendor/`** - Instale no servidor
2. **Nunca envie `tests/`** - Apenas para desenvolvimento
3. **Use `--no-dev`** em produção - Não instala dependências de desenvolvimento
4. **Backup sempre** - Antes de qualquer deploy

## 🚀 Deploy Automático (GitHub Actions)

Se configurar o deploy automático no `.github/workflows/ci.yml`:

1. Configure as secrets no GitHub:
   - `DEPLOY_HOST` - IP/hostname do servidor
   - `DEPLOY_USER` - Usuário SSH
   - `DEPLOY_KEY` - Chave SSH privada

2. O pipeline fará deploy automático quando:
   - Código for enviado para `main`
   - Todos os testes passarem
   - Análise de código passar

## 📞 Suporte

Em caso de problemas:
1. Verifique os logs do servidor
2. Verifique permissões de arquivos
3. Verifique se o Composer está instalado
4. Verifique se as extensões PHP estão habilitadas




