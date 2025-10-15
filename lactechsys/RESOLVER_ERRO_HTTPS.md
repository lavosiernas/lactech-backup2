# 🔒 RESOLVER ERRO DE PRIVACIDADE (HTTPS) NO LOCALHOST

## ❌ PROBLEMA
Quando você tenta acessar o sistema localmente, aparece:
- "Sua conexão não é particular"
- "NET::ERR_CERT_AUTHORITY_INVALID"
- "Certificado SSL inválido"

## 🎯 CAUSA
O sistema está tentando forçar HTTPS mesmo em localhost (HTTP).

---

## ✅ SOLUÇÃO DEFINITIVA

### 🎯 SOLUÇÃO MAIS RÁPIDA: Usar os arquivos criados

**Arquivos criados para você:**

1. **`abrir_local.bat`** - Clique duas vezes para abrir automaticamente
2. **`abrir_local.html`** - Abra no navegador para interface amigável

### Opção 1: Arquivo .bat (Windows)
```bash
# Clique duas vezes no arquivo:
abrir_local.bat
```

### Opção 2: Arquivo HTML
```bash
# Abra no navegador:
abrir_local.html
```

### Opção 3: Acesso direto
```
http://localhost/GitHub/lactech-backup2/lactechsys/login.php
```
⚠️ **IMPORTANTE:** Use `http://` (não `https://`)

---

### Opção 2: Usar .htaccess.local (Alternativa)

Se o problema persistir, troque os arquivos:

```bash
# No terminal ou prompt de comando, dentro da pasta lactechsys:

# Windows (PowerShell)
ren .htaccess .htaccess.production
ren .htaccess.local .htaccess

# Linux/Mac
mv .htaccess .htaccess.production
mv .htaccess.local .htaccess
```

---

### Opção 4: Usar index.php (Solução Automática)

Foi criado um `index.php` que detecta automaticamente se está em localhost e força HTTP:

```bash
# Acesse:
http://localhost/GitHub/lactech-backup2/lactechsys/
```

O sistema automaticamente:
- ✅ Detecta que está em localhost
- ✅ Redireciona de HTTPS para HTTP
- ✅ Leva para a página de login

### Opção 5: Desabilitar Rewrite (Temporário)

Se nada funcionar, edite o arquivo `.htaccess` e comente as linhas do HTTPS:

```apache
# Forçar HTTPS (SSL) - DESABILITADO TEMPORARIAMENTE
# RewriteEngine On
# RewriteCond %{HTTPS} off
# RewriteCond %{HTTP_HOST} !^localhost [NC]
# RewriteCond %{HTTP_HOST} !^127\.0\.0\.1 [NC]
# RewriteCond %{HTTP_HOST} !^::1 [NC]
# RewriteCond %{HTTP_HOST} !^192\.168\. [NC]
# RewriteCond %{HTTP_HOST} !^10\. [NC]
# RewriteCond %{HTTP_HOST} !^172\.16\. [NC]
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

Adicione `#` no início de cada linha para desabilitar.

---

## 🔧 VERIFICAÇÕES ADICIONAIS

### 1. Verificar se o Apache está lendo o .htaccess

No arquivo `httpd.conf` do XAMPP (`C:\xampp1\apache\conf\httpd.conf`):

Procure por:
```apache
<Directory "C:/xampp1/htdocs">
    AllowOverride All   # ← DEVE estar "All"
```

Se estiver `None`, mude para `All` e reinicie o Apache.

---

### 2. Verificar se mod_rewrite está ativado

No mesmo `httpd.conf`, procure:
```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

Se estiver com `#` na frente, remova o `#` e reinicie o Apache.

---

### 3. Limpar redirecionamentos do navegador

Chrome armazena redirecionamentos HTTPS. Para limpar:

**Chrome:**
1. Abra `chrome://net-internals/#hsts`
2. Em "Delete domain security policies"
3. Digite: `localhost`
4. Clique em "Delete"

**Edge:**
1. Abra `edge://net-internals/#hsts`
2. Siga os mesmos passos

**Firefox:**
1. Pressione `Ctrl + Shift + Delete`
2. Marque "Cache" e "Cookies"
3. Selecione "Tudo"
4. Limpar dados

---

## 🧪 TESTAR SE FUNCIONOU

1. Feche TODOS os navegadores
2. Reinicie o Apache no XAMPP
3. Abra um navegador em modo anônimo (Ctrl + Shift + N)
4. Acesse:
   ```
   http://localhost/GitHub/lactech-backup2/lactechsys/login.php
   ```

✅ Se funcionar, o problema era cache do navegador!

---

## 🚨 SE AINDA ESTIVER DANDO ERRO

### Verifique Headers PHP

Alguns arquivos PHP podem estar forçando HTTPS. Procure por:

```bash
# No terminal, dentro da pasta lactechsys:
grep -r "Location.*https" *.php
grep -r "header.*https" *.php
```

Se encontrar algo, comente essas linhas temporariamente.

---

### Desabilitar Service Worker

O Service Worker pode estar cacheando redirecionamentos:

1. Abra o DevTools (F12)
2. Vá para "Application" → "Service Workers"
3. Clique em "Unregister" para todos
4. Recarregue a página

---

## 📋 CHECKLIST RÁPIDO

Antes de pedir ajuda, verifique:

- [ ] Usando `http://` (não https://)
- [ ] Cache do navegador limpo
- [ ] Apache reiniciado
- [ ] Navegador em modo anônimo/privado
- [ ] `.htaccess` permite localhost
- [ ] `AllowOverride All` no httpd.conf
- [ ] `mod_rewrite` ativado

---

## 💡 DICA PRO

Crie um atalho direto para evitar erros:

**Windows:**
1. Crie um arquivo `abrir_lactech.bat`:
   ```batch
   @echo off
   start http://localhost/GitHub/lactech-backup2/lactechsys/login.php
   ```

2. Clique duas vezes nele para abrir direto com HTTP!

**Linux/Mac:**
1. Crie um script `abrir_lactech.sh`:
   ```bash
   #!/bin/bash
   xdg-open http://localhost/GitHub/lactech-backup2/lactechsys/login.php
   ```

2. Torne executável: `chmod +x abrir_lactech.sh`

---

## ✅ FUNCIONOU?

Se tudo funcionou, você deve ver a tela de login normalmente em HTTP, sem erros de certificado.

**Para fazer deploy em produção:**
- O sistema automaticamente detecta que está em produção
- Volta a usar HTTPS corretamente
- Sem alterações manuais necessárias

---

## 🆘 AINDA COM PROBLEMAS?

Se nada funcionou, tente:

1. **Desinstalar e reinstalar o XAMPP**
   - Às vezes o Apache tem configurações corrompidas

2. **Usar outro navegador**
   - Teste em Chrome, Firefox e Edge

3. **Verificar antivírus/firewall**
   - Pode estar bloqueando localhost

4. **Testar em 127.0.0.1**
   ```
   http://127.0.0.1/GitHub/lactech-backup2/lactechsys/login.php
   ```

---

**Última atualização:** Outubro 2025  
**Testado em:** XAMPP 8.2, Chrome 118, Edge 118, Firefox 119

