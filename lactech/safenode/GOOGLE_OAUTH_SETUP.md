# 🔐 Configuração do Google OAuth - SafeNode

## 📋 Pré-requisitos

Você precisa ter criado um projeto no Google Cloud Console e configurado as credenciais OAuth 2.0.

## 🌐 Estrutura do Domínio

- **Domínio:** `safenode.cloud`
- **Arquivos na raiz:** A pasta `safenode` vai para a raiz do domínio (não em subpasta)
- **Exemplo:** `https://safenode.cloud/login.php` (não `https://safenode.cloud/safenode/login.php`)
- **Repositório:** Separado da LacTech, deploy independente

## 🚀 Passo a Passo

### 1. Atualizar o Banco de Dados

Execute o SQL para adicionar a coluna `google_id`:

```bash
mysql -u seu_usuario -p nome_do_banco < database/add_google_oauth.sql
```

Ou execute manualmente no phpMyAdmin/MySQL:

```sql
ALTER TABLE `safenode_users` 
ADD COLUMN `google_id` VARCHAR(255) NULL DEFAULT NULL AFTER `email_verified_at`,
ADD UNIQUE INDEX `idx_google_id` (`google_id`);
```

### 2. Configurar Credenciais do Google

Abra o arquivo `includes/GoogleOAuth.php` e substitua:

```php
$this->clientId = 'SEU_CLIENT_ID_AQUI.apps.googleusercontent.com';
$this->clientSecret = 'SEU_CLIENT_SECRET_AQUI';
```

Pelos seus valores reais obtidos no Google Cloud Console.

### 3. Configurar URIs no Google Cloud Console

No Google Cloud Console (APIs & Services → Credentials → Seu OAuth 2.0 Client), configure:

**Origens JavaScript autorizadas:**
```
https://safenode.cloud
```

**URIs de redirecionamento autorizados:**
```
https://safenode.cloud/google-callback.php
http://localhost/google-callback.php
```

**⚠️ IMPORTANTE:** 
- Use exatamente `https://safenode.cloud/google-callback.php` para produção
- Adicione `http://localhost/google-callback.php` se quiser testar localmente
- NÃO adicione barra no final das URLs
- Em produção o protocolo DEVE ser HTTPS
- O código detecta automaticamente se está em localhost ou produção

### 4. Testar

1. Acesse a página de login ou registro
2. Clique em "Continuar com Google"
3. Faça login com sua conta Google
4. Você será redirecionado ao dashboard

## ✅ Funcionalidades

- ✅ Login automático com conta Google existente
- ✅ Cadastro automático de novos usuários via Google
- ✅ Email automaticamente verificado
- ✅ Não precisa de senha (usa Google OAuth)
- ✅ Username gerado automaticamente do email
- ✅ Integração com sistema de sessões SafeNode

## 🔒 Segurança

- Token de acesso não é armazenado
- Apenas o `google_id` é salvo no banco
- Email é verificado automaticamente pelo Google
- Suporte a contas existentes (vincula google_id ao cadastro)

## 📝 Notas

- Usuários cadastrados via Google recebem uma senha aleatória (não usada)
- Se o email já existir no banco, apenas vincula o `google_id`
- Username é gerado a partir do email (parte antes do @)
- Se username já existir, adiciona número sequencial

## 🐛 Troubleshooting

**Erro: "redirect_uri_mismatch"**
- Verifique se a URI de callback está corretamente configurada no Google Cloud Console
- Certifique-se que o protocolo (http/https) está correto

**Erro: "Sessão expirada"**
- Limpe cookies e sessões
- Tente novamente

**Usuário não consegue logar**
- Verifique se `is_active = 1` no banco de dados
- Verifique se o email está correto

## ⚡ Configuração Rápida (Checklist)

- [ ] 1. Executar SQL para adicionar coluna `google_id`
- [ ] 2. Abrir `includes/GoogleOAuth.php` e colar Client ID e Client Secret
- [ ] 3. No Google Console, adicionar origem: `https://safenode.cloud`
- [ ] 4. No Google Console, adicionar callback: `https://safenode.cloud/google-callback.php`
- [ ] 5. Testar: Ir em `https://safenode.cloud/login.php` e clicar em "Continuar com Google"

**Pronto!** Deve funcionar imediatamente após configurar. 🎉

## 📍 URLs do Sistema

- **Login:** `https://safenode.cloud/login.php`
- **Registro:** `https://safenode.cloud/register.php`
- **Callback:** `https://safenode.cloud/google-callback.php` (automático)
- **Dashboard:** `https://safenode.cloud/dashboard.php` (após login)

