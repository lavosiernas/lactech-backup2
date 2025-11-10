# 🔒 Guia de Migração para Variáveis de Ambiente

## 📋 Por que migrar?

Os arquivos de configuração estão com dados sensíveis hardcoded (senhas, secrets, etc). Isso é um risco de segurança, especialmente se esses arquivos estiverem no GitHub ou acessíveis publicamente.

## ✅ Solução Implementada

Foi criado um sistema que permite usar variáveis de ambiente com fallback para valores padrão. Isso significa que:

1. **Prioridade**: Variáveis de ambiente (mais seguro)
2. **Fallback**: Valores hardcoded (mantém compatibilidade)

## 🚀 Como Migrar (Passo a Passo)

### Opção 1: Usar Arquivo .env (Recomendado)

1. **Criar arquivo `.env` na raiz do projeto:**
   ```bash
   # Na raiz do projeto (lactech/)
   touch .env
   ```

2. **Editar o arquivo `.env` e adicionar suas configurações:**
   ```env
   # Configurações do Banco de Dados
   # Ambiente Local
   DB_HOST_LOCAL=localhost
   DB_NAME_LOCAL=lactech_lgmato
   DB_USER_LOCAL=root
   DB_PASS_LOCAL=

   # Ambiente de Produção
   DB_HOST_PROD=localhost
   DB_NAME_PROD=u311882628_lactech_lgmato
   DB_USER_PROD=u311882628_xandriaAgro
   DB_PASS_PROD=SuaSenhaAqui

   # Configurações Google OAuth
   GOOGLE_CLIENT_ID=seu_client_id_aqui
   GOOGLE_CLIENT_SECRET=seu_client_secret_aqui
   GOOGLE_REDIRECT_URI=https://lactechsys.com/google-callback.php
   GOOGLE_LOGIN_REDIRECT_URI=https://lactechsys.com/google-login-callback.php
   ```

3. **O arquivo `.env` já está no `.gitignore`** - não será commitado

### Opção 2: Usar Variáveis de Ambiente do Servidor

Se você não quiser usar arquivo `.env`, pode definir variáveis de ambiente diretamente no servidor:

**Apache (.htaccess ou httpd.conf):**
```apache
SetEnv DB_HOST_PROD localhost
SetEnv DB_NAME_PROD u311882628_lactech_lgmato
SetEnv DB_USER_PROD u311882628_xandriaAgro
SetEnv DB_PASS_PROD sua_senha_aqui
```

**Nginx:**
```nginx
fastcgi_param DB_HOST_PROD localhost;
fastcgi_param DB_NAME_PROD u311882628_lactech_lgmato;
fastcgi_param DB_USER_PROD u311882628_xandriaAgro;
fastcgi_param DB_PASS_PROD sua_senha_aqui;
```

**PHP-FPM:**
```php
env[DB_HOST_PROD] = localhost
env[DB_NAME_PROD] = u311882628_lactech_lgmato
env[DB_USER_PROD] = u311882628_xandriaAgro
env[DB_PASS_PROD] = sua_senha_aqui
```

## 🔄 Como Funciona Agora

Os arquivos de configuração agora:

1. **Tentam carregar variáveis de ambiente** (do arquivo `.env` ou do servidor)
2. **Se não encontrar, usam os valores padrão** (hardcoded) como fallback
3. **Sistema continua funcionando** mesmo sem arquivo `.env`

## 📝 Removendo Valores Hardcoded (Opcional)

Depois de configurar o `.env` e testar, você pode **opcionalmente** remover os valores hardcoded dos arquivos de configuração:

1. Editar `includes/config_mysql.php`
2. Editar `includes/config_login.php`
3. Editar `includes/config_google.php`

Substituir valores como `'Lavosier0012!'` por `''` (string vazia) ou remover completamente o fallback.

**⚠️ IMPORTANTE**: Só faça isso se tiver certeza que as variáveis de ambiente estão configuradas corretamente!

## 🔐 Proteções Implementadas

### 1. `.gitignore` Atualizado
- `.env` está ignorado
- Arquivos de backup estão ignorados
- Arquivos de log estão ignorados

### 2. `.htaccess` na pasta `includes/`
- Bloqueia acesso direto a arquivos de configuração via URL
- Arquivos podem ser incluídos via PHP, mas não acessados diretamente

### 3. Arquivos `.example` Criados
- `config_google.example.php`
- `config_mysql.example.php`
- `config_login.example.php`

## ✅ Checklist de Migração

- [ ] Arquivo `.env` criado na raiz do projeto
- [ ] Variáveis de ambiente preenchidas no `.env`
- [ ] Sistema testado e funcionando
- [ ] Arquivo `.env` não está sendo commitado (verificar `.gitignore`)
- [ ] Arquivos sensíveis removidos do histórico do Git (se necessário)

## 🆘 Troubleshooting

### Sistema não está funcionando após migração

1. Verifique se o arquivo `.env` está na raiz do projeto
2. Verifique se as variáveis estão com os nomes corretos
3. Verifique se o arquivo `includes/env_loader.php` existe
4. Verifique os logs de erro do PHP

### Variáveis de ambiente não estão sendo carregadas

1. Verifique se o arquivo `.env` existe e está acessível
2. Verifique permissões do arquivo `.env` (deve ser 644 ou 600)
3. Verifique se o `env_loader.php` está sendo carregado

### Ainda funciona sem .env?

**Sim!** O sistema usa fallback para valores padrão. Se não encontrar variáveis de ambiente, usa os valores hardcoded.

## 📚 Documentação Adicional

- Veja `README_SECURITY.md` para mais informações sobre segurança
- Veja os arquivos `.example` para exemplos de configuração

---

**Lembre-se**: A segurança é responsabilidade de todos. Mantenha suas credenciais seguras! 🔒


