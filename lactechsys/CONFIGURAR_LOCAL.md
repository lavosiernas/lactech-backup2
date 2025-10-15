# 🔧 GUIA: CONFIGURAR LACTECH LOCALMENTE (XAMPP)

## 📋 PRÉ-REQUISITOS

- ✅ XAMPP instalado e funcionando
- ✅ Apache e MySQL rodando
- ✅ Navegador moderno (Chrome/Edge recomendado)

---

## 🚀 PASSO A PASSO - CONFIGURAÇÃO LOCAL

### 1️⃣ PREPARAR O BANCO DE DADOS

#### Opção A: Criar banco novo (Recomendado)
```sql
-- Acesse phpMyAdmin (http://localhost/phpmyadmin)
-- Execute os comandos:

CREATE DATABASE IF NOT EXISTS lactech_lagoa_mato CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lactech_lagoa_mato;

-- Importe o arquivo: banco_mysql_completo.sql
-- (Use a opção "Importar" no phpMyAdmin)
```

#### Opção B: Usar banco existente
- Se você já tem o banco, apenas certifique-se que o nome é: `lactech_lagoa_mato`

---

### 2️⃣ VERIFICAR CONFIGURAÇÕES DO PHP

Seu sistema já está configurado com detecção automática de ambiente!

**Arquivo principal:** `includes/config_mysql.php`

O sistema detecta automaticamente se está em **localhost** e usa:
- **Host:** localhost  
- **Banco:** lactech_lagoa_mato  
- **Usuário:** root  
- **Senha:** (vazio)

✅ **Não precisa alterar nada!** O sistema já está configurado.

---

### 3️⃣ CONFIGURAR O CAMINHO BASE

Verifique se o caminho está correto:

**Seu projeto está em:**
```
C:\xampp1\htdocs\GitHub\lactech-backup2\lactechsys\
```

**URL esperada:**
```
http://localhost/GitHub/lactech-backup2/lactechsys/
```

✅ O sistema detecta isso automaticamente!

---

### 4️⃣ TESTAR A CONEXÃO

1. Abra o navegador e acesse:
   ```
   http://localhost/GitHub/lactech-backup2/lactechsys/testar_conexao.php
   ```

2. Você deve ver:
   ```
   ✅ Conexão com banco de dados: OK
   ✅ Ambiente detectado: LOCAL
   ✅ Banco: lactech_lagoa_mato
   ```

3. Se der erro, verifique:
   - ☑️ MySQL está rodando no XAMPP?
   - ☑️ O banco `lactech_lagoa_mato` existe?
   - ☑️ Porta 3306 está livre?

---

### 5️⃣ CRIAR USUÁRIOS DE TESTE

Execute o script para resetar senhas e criar usuários de teste:

```
http://localhost/GitHub/lactech-backup2/lactechsys/resetar_senhas.php
```

Isso criará usuários com senhas simples para teste:
- **Gerente:** gerente@lagoamato.com / senha: 123456
- **Funcionário:** funcionario@lagoamato.com / senha: 123456
- **Veterinário:** vet@lagoamato.com / senha: 123456

---

### 6️⃣ FAZER LOGIN

1. Acesse:
   ```
   http://localhost/GitHub/lactech-backup2/lactechsys/login.php
   ```

2. Entre com qualquer usuário criado acima

3. Se funcionar, parabéns! 🎉

---

## 🐛 PROBLEMAS COMUNS E SOLUÇÕES

### ❌ Erro: "Access denied for user 'root'@'localhost'"
**Solução:**
- Verifique se o MySQL está rodando no XAMPP
- Verifique se a senha do root é realmente vazia
- Tente definir uma senha no phpMyAdmin e atualizar o `config_mysql.php`

### ❌ Erro: "Database does not exist"
**Solução:**
- Crie o banco manualmente no phpMyAdmin:
  ```sql
  CREATE DATABASE lactech_lagoa_mato;
  ```

### ❌ Erro: "Cannot connect to database"
**Solução:**
1. Abra o XAMPP Control Panel
2. Clique em "Start" para MySQL
3. Verifique se a porta 3306 está verde

### ❌ Erro: "404 Not Found"
**Solução:**
- Verifique se o caminho está correto
- Certifique-se que os arquivos estão em `C:\xampp1\htdocs\GitHub\lactech-backup2\lactechsys\`
- Acesse com a URL completa: `http://localhost/GitHub/lactech-backup2/lactechsys/login.php`

### ❌ Tela em branco / Página não carrega
**Solução:**
1. Ative exibição de erros no PHP:
   - Edite `C:\xampp1\php\php.ini`
   - Procure por `display_errors`
   - Mude para `display_errors = On`
   - Reinicie o Apache

2. Veja os erros no console do navegador (F12)

### ❌ "Headers already sent"
**Solução:**
- Certifique-se que não há espaços em branco antes de `<?php`
- Verifique se todos os arquivos estão salvos em UTF-8 sem BOM

---

## 🔄 ALTERNANDO ENTRE LOCAL E PRODUÇÃO

### Para TESTAR LOCALMENTE (Já configurado! ✅)
O sistema detecta automaticamente quando está em localhost.

### Para fazer DEPLOY para HOSTINGER
1. Apenas faça upload dos arquivos
2. O sistema detecta automaticamente que está em produção
3. Usa as credenciais corretas automaticamente

✅ **Nenhuma alteração manual necessária!**

---

## 📊 VERIFICAR O AMBIENTE ATUAL

Crie um arquivo `verificar_ambiente.php`:

```php
<?php
require_once 'includes/config_mysql.php';

echo "<h1>Configuração Atual</h1>";
echo "<p><strong>Ambiente:</strong> " . ENVIRONMENT . "</p>";
echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
echo "<p><strong>Banco:</strong> " . DB_NAME . "</p>";
echo "<p><strong>Usuário:</strong> " . DB_USER . "</p>";
echo "<p><strong>URL Base:</strong> " . BASE_URL . "</p>";
?>
```

---

## 🎯 CHECKLIST RÁPIDO

Antes de começar a desenvolver, verifique:

- [ ] XAMPP rodando (Apache + MySQL)
- [ ] Banco `lactech_lagoa_mato` criado
- [ ] Tabelas importadas do SQL
- [ ] `testar_conexao.php` retorna OK
- [ ] Usuários de teste criados
- [ ] Login funciona
- [ ] Console do navegador (F12) sem erros críticos

---

## 💡 DICAS DE DESENVOLVIMENTO

### Ver erros do PHP
```php
// Adicione no topo dos arquivos durante desenvolvimento:
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Ver erros do JavaScript
- Pressione F12 no navegador
- Vá para a aba "Console"
- Veja mensagens de erro em vermelho

### Limpar cache
- Pressione Ctrl + Shift + Delete
- Ou Ctrl + F5 para recarregar sem cache

### Ver queries SQL
- Verifique o log do MySQL em: `C:\xampp1\mysql\data\mysql_error.log`

---

## 📞 AINDA COM PROBLEMAS?

Se ainda estiver tendo problemas:

1. **Verifique logs de erro:**
   - PHP: `C:\xampp1\apache\logs\error.log`
   - MySQL: `C:\xampp1\mysql\data\mysql_error.log`

2. **Teste conexão básica:**
   ```php
   <?php
   $conn = new mysqli('localhost', 'root', '', 'lactech_lagoa_mato');
   if ($conn->connect_error) {
       die("Erro: " . $conn->connect_error);
   }
   echo "Conexão OK!";
   ?>
   ```

3. **Verifique permissões:**
   - Certifique-se que os arquivos não estão read-only
   - Verifique permissões da pasta

---

## ✅ TUDO FUNCIONANDO?

Se tudo estiver funcionando corretamente, você verá:
- ✅ Login carrega normalmente
- ✅ Dashboard do gerente abre
- ✅ Sem erros no console (F12)
- ✅ Dados aparecem corretamente

**Agora você pode desenvolver localmente e fazer upload apenas quando estiver pronto!** 🚀

---

**Última atualização:** Outubro 2025  
**Versão do sistema:** 2.0.0


