# 📝 Instruções para Configurar o Arquivo .env

## ⚠️ IMPORTANTE

Para que o sistema funcione após as atualizações de segurança, você **DEVE** criar um arquivo `.env` na raiz do projeto com suas credenciais.

## 📍 Localização do Arquivo

Crie o arquivo em: `lactech/.env`

## 📋 Conteúdo do Arquivo .env

Copie e cole o seguinte conteúdo no arquivo `.env`, substituindo pelos seus dados reais:

```env
# =====================================================
# BANCO DE DADOS - AMBIENTE LOCAL
# =====================================================
DB_HOST_LOCAL=localhost
DB_NAME_LOCAL=lactech_lgmato
DB_USER_LOCAL=root
DB_PASS_LOCAL=

# =====================================================
# BANCO DE DADOS - AMBIENTE DE PRODUÇÃO
# =====================================================
DB_HOST_PROD=localhost
DB_NAME_PROD=u311882628_lactech_lgmato
DB_USER_PROD=u311882628_xandriaAgro
DB_PASS_PROD=Lavosier0012!

# =====================================================
# CONFIGURAÇÕES GOOGLE OAUTH
# =====================================================
GOOGLE_CLIENT_ID=563053705449-hurd35dp6n644skh4qocmaf8i82u1u1f.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=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
GOOGLE_REDIRECT_URI=https://lactechsys.com/google-callback.php
GOOGLE_LOGIN_REDIRECT_URI=https://lactechsys.com/google-login-callback.php
GOOGLE_SCOPES=email profile

# =====================================================
# URL BASE - PRODUÇÃO
# =====================================================
BASE_URL_PROD=https://lactechsys.com/
```

## ✅ Após Criar o Arquivo

1. **Verifique as permissões** do arquivo `.env` (deve ser legível pelo servidor web)
2. **Nunca commite** este arquivo no repositório Git
3. **Teste o sistema** para garantir que está funcionando

## 🔒 Segurança

- O arquivo `.env` já está no `.gitignore`
- As credenciais foram removidas do código
- O sistema não funcionará sem o arquivo `.env` configurado

## 📞 Problemas?

Se encontrar erros sobre configuração não encontrada:
1. Verifique se o arquivo `.env` existe na pasta `lactech/`
2. Verifique se todas as variáveis estão preenchidas
3. Verifique as permissões do arquivo

