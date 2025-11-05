# 🔒 Segurança de Configurações - LacTech

## ⚠️ IMPORTANTE: Proteção de Dados Sensíveis

Este projeto utiliza um sistema de proteção para arquivos de configuração sensíveis. **NUNCA** commite arquivos com dados sensíveis no repositório.

## 📁 Arquivos Protegidos

Os seguintes arquivos contêm informações sensíveis e **NÃO** devem ser commitados:

- `includes/config_google.php` - Credenciais do Google OAuth
- `includes/config_mysql.php` - Credenciais do banco de dados
- `includes/config_login.php` - Credenciais do banco de dados
- `.env` - Variáveis de ambiente com todas as senhas
- `api/client_secret*.json` - Arquivos JSON do Google

## 🚀 Como Configurar o Projeto

### 1. Configurar Variáveis de Ambiente (Recomendado)

1. Copie o arquivo `.env.example` para `.env` na raiz do projeto:
   ```bash
   cp .env.example .env
   ```

2. Edite o arquivo `.env` e preencha com seus dados reais:
   ```
   DB_HOST_PROD=localhost
   DB_NAME_PROD=seu_banco
   DB_USER_PROD=seu_usuario
   DB_PASS_PROD=sua_senha
   GOOGLE_CLIENT_ID=seu_client_id
   GOOGLE_CLIENT_SECRET=seu_client_secret
   ```

3. Os arquivos de configuração irão ler automaticamente do `.env`

### 2. Configurar Arquivos de Configuração (Alternativa)

1. Copie os arquivos `.example` para os arquivos reais:
   ```bash
   cp includes/config_google.example.php includes/config_google.php
   cp includes/config_mysql.example.php includes/config_mysql.php
   cp includes/config_login.example.php includes/config_login.php
   ```

2. Edite os arquivos e preencha com seus dados reais

## 🔐 Proteções Implementadas

### 1. `.gitignore`
- Todos os arquivos sensíveis estão no `.gitignore`
- O arquivo `.env` nunca será commitado
- Arquivos de backup também estão protegidos

### 2. `.htaccess` na pasta `includes/`
- Bloqueia acesso direto a arquivos de configuração via URL
- Arquivos podem ser incluídos via PHP, mas não acessados diretamente
- Bloqueia listagem de diretório

### 3. Variáveis de Ambiente
- Sistema de carregamento de variáveis de ambiente
- Fallback para valores padrão quando variáveis não estão definidas

## 📝 Checklist de Segurança

Antes de fazer commit, verifique:

- [ ] Nenhum arquivo `.env` está sendo commitado
- [ ] Nenhum arquivo `config_*.php` com dados reais está sendo commitado
- [ ] Nenhum arquivo `client_secret*.json` está sendo commitado
- [ ] Apenas arquivos `.example` estão no repositório
- [ ] O `.gitignore` está atualizado

## 🆘 Se Você Commitou Dados Sensíveis

Se você acidentalmente commitou dados sensíveis:

1. **IMEDIATAMENTE** altere todas as senhas e credenciais
2. Remova o arquivo do histórico do Git:
   ```bash
   git rm --cached includes/config_google.php
   git commit -m "Remove arquivo sensível"
   ```
3. Adicione ao `.gitignore` se ainda não estiver
4. Force push (após confirmar que não há dados sensíveis):
   ```bash
   git push --force
   ```

## 📚 Documentação Adicional

- Arquivos `.example` servem como template
- Arquivos `.env` são carregados automaticamente pelo `env_loader.php`
- O sistema detecta automaticamente se está em ambiente local ou produção

---

**Lembre-se:** Segurança é responsabilidade de todos. Mantenha suas credenciais seguras! 🔒


