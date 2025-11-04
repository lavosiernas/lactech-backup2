# Configuração Google OAuth - LacTech (COMPLETO)

## ✅ O que você já configurou

**Origens JavaScript autorizadas:**
```
https://lactechsys.com
```

Está correto! ✅

---

## 📋 O que ainda precisa configurar

### 1. Authorized redirect URIs (MUITO IMPORTANTE!)

No Google Cloud Console, na mesma tela onde você configurou as "Origens JavaScript", você precisa adicionar:

**Authorized redirect URIs:**

Para produção:
```
https://lactechsys.com/lactech/google-callback.php
```

Para desenvolvimento local (se necessário):
```
http://localhost/lactech/google-callback.php
```

⚠️ **CRÍTICO**: O Google só redirecionará para URLs que estiverem cadastradas aqui. Se não adicionar, o OAuth não funcionará!

---

## 🔑 Passo a Passo Completo

### No Google Cloud Console:

1. **APIs & Services** > **Credentials**
2. Clique no **OAuth Client ID** que você criou (ou crie um novo)
3. Na seção **Authorized redirect URIs**, clique em **+ ADD URI**
4. Adicione exatamente:
   ```
   https://lactechsys.com/lactech/google-callback.php
   ```
5. Clique em **SAVE**

---

## 📝 Resumo das Configurações

| Campo | Valor |
|-------|-------|
| **Origens JavaScript autorizadas** | `https://lactechsys.com` ✅ |
| **Authorized redirect URIs** | `https://lactechsys.com/lactech/google-callback.php` ⚠️ Adicionar! |

---

## ⚙️ Configuração no Código

Depois de obter o **Client ID** e **Client Secret**, crie o arquivo:

**`lactech/includes/config_google.php`** (copie do `.example` e preencha):

```php
<?php
// Configurações Google OAuth - LACTECH
// ⚠️ NUNCA commite este arquivo no repositório

// Client ID do Google OAuth
define('GOOGLE_CLIENT_ID', 'SEU_CLIENT_ID_AQUI.apps.googleusercontent.com');

// Client Secret do Google OAuth
// ⚠️ MANTENHA ESTE VALOR SECRETO
define('GOOGLE_CLIENT_SECRET', 'SEU_CLIENT_SECRET_AQUI');

// URL de redirecionamento (deve ser EXATAMENTE igual ao configurado no Google Console)
define('GOOGLE_REDIRECT_URI', 'https://lactechsys.com/lactech/google-callback.php');

// Escopos necessários (geralmente não precisa alterar)
define('GOOGLE_SCOPES', 'email profile');
?>
```

---

## ✅ Checklist Final

- [ ] Origens JavaScript: `https://lactechsys.com` ✅
- [ ] Authorized redirect URIs: `https://lactechsys.com/lactech/google-callback.php` ⚠️
- [ ] Client ID copiado
- [ ] Client Secret copiado
- [ ] Arquivo `config_google.php` criado com as credenciais

---

## 🚀 Depois de Configurar

1. Crie o arquivo `lactech/includes/config_google.php` com suas credenciais
2. Teste clicando em "Vincular Conta Google" no perfil
3. O popup do Google deve abrir para autorização
4. Após autorizar, a conta será vinculada automaticamente

---

## ❓ Precisa de Ajuda?

Se você já tem o **Client ID** e **Client Secret**, me envie apenas esses valores e eu crio o arquivo `config_google.php` para você!

Ou você pode criar manualmente seguindo o exemplo acima.



