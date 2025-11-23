# 🛡️ GUIA DE IMPLEMENTAÇÃO - Melhorias de Segurança

## 📦 ARQUIVOS CRIADOS

### ✅ `includes/SecurityHelpers.php`
Classes utilitárias para segurança:
- `CSRFProtection` - Proteção contra CSRF
- `XSSProtection` - Proteção contra XSS  
- `InputValidator` - Validação de inputs
- `SecurityHeaders` - Headers HTTP de segurança

---

## 🚀 COMO IMPLEMENTAR

### PASSO 1: Adicionar Security Headers (FÁCIL)

Em **TODOS** os arquivos PHP principais, logo após `session_start()`:

```php
<?php
session_start();

// ADICIONAR ESTAS LINHAS:
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

// Resto do código...
```

**Arquivos para atualizar:**
- `login.php`
- `register.php`
- `dashboard.php`
- `sites.php`
- `profile.php`
- `settings.php`
- `logs.php`
- `incidents.php`
- `dns_records.php`

---

### PASSO 2: Adicionar CSRF Protection nos Formulários (MÉDIO)

#### 2.1 - No Formulário (HTML)

**ANTES:**
```html
<form method="POST" action="">
    <input type="text" name="username">
    <button type="submit">Enviar</button>
</form>
```

**DEPOIS:**
```html
<form method="POST" action="">
    <?php echo csrf_field(); ?>
    <input type="text" name="username">
    <button type="submit">Enviar</button>
</form>
```

#### 2.2 - Na Validação (PHP)

**ANTES:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // processar formulário
}
```

**DEPOIS:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF token
    if (!CSRFProtection::validate()) {
        die('Token CSRF inválido. Recarregue a página.');
    }
    
    // processar formulário
}
```

**Formulários para proteger:**
- Login (`login.php`)
- Registro (`register.php`)
- OTP (`verify-otp.php`)
- Criar/Editar site (`sites.php`)
- Configurações (`settings.php`)
- Profile (`profile.php`)

---

### PASSO 3: Proteger Outputs contra XSS (MÉDIO)

**ANTES:**
```php
<p><?php echo $username; ?></p>
<input value="<?php echo $email; ?>">
```

**DEPOIS:**
```php
<p><?php echo h($username); ?></p>
<input value="<?php echo h($email); ?>">
```

**Ou use a classe completa:**
```php
<p><?php echo XSSProtection::escape($username); ?></p>
<input value="<?php echo XSSProtection::escapeAttr($email); ?>">
<script>var user = <?php echo XSSProtection::escapeJS($username); ?>;</script>
```

---

### PASSO 4: Validar Inputs (FÁCIL)

**ANTES:**
```php
$email = $_POST['email'] ?? '';
if (empty($email)) {
    $error = 'Email obrigatório';
}
```

**DEPOIS:**
```php
$email = $_POST['email'] ?? '';
if (empty($email) || !InputValidator::email($email)) {
    $error = 'Email inválido';
}
```

**Validações disponíveis:**
```php
InputValidator::email($email);
InputValidator::domain($domain);
InputValidator::username($username);
InputValidator::strongPassword($password);
InputValidator::positiveInteger($id);
InputValidator::string($text, $min, $max);
```

---

## 📋 EXEMPLO COMPLETO

### Login Seguro (login.php)

```php
<?php
session_start();
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validar CSRF
    if (!CSRFProtection::validate()) {
        die('Token CSRF inválido');
    }
    
    // 2. Validar e sanitizar inputs
    $email = XSSProtection::sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // 3. Validações
    if (!InputValidator::email($email)) {
        $error = 'Email inválido';
    }
    
    if (!$error) {
        // Processar login...
    }
}
?>
<!DOCTYPE html>
<html>
<body>
    <form method="POST">
        <?php echo csrf_field(); ?>
        
        <input type="email" name="email" value="<?php echo h($_POST['email'] ?? ''); ?>">
        <input type="password" name="password">
        
        <button>Login</button>
    </form>
</body>
</html>
```

---

## ⚠️ CUIDADOS IMPORTANTES

### ❌ NÃO FAÇA:
```php
// Não use echo direto sem escape
echo $_POST['name'];
echo $user['email'];

// Não confie em inputs do usuário
$id = $_GET['id'];
$query = "SELECT * FROM users WHERE id = $id"; // SQL Injection!
```

### ✅ FAÇA:
```php
// Sempre escape outputs
echo h($_POST['name']);
echo h($user['email']);

// Use prepared statements
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
```

---

## 🎯 PRIORIDADES DE IMPLEMENTAÇÃO

### 🔴 URGENTE (Fazer AGORA)
1. ✅ Adicionar Security Headers em todos os arquivos
2. ✅ CSRF em login e register
3. ✅ XSS escape nos outputs principais

### 🟡 IMPORTANTE (Fazer LOGO)
4. CSRF em todos os formulários
5. Validação consistente de inputs
6. XSS escape em todos os outputs

### 🟢 RECOMENDADO (Melhorias)
7. Logs de segurança
8. Rate limiting avançado
9. Alertas de atividades suspeitas

---

## ✅ CHECKLIST DE SEGURANÇA

Após implementar, verifique:

- [ ] Security headers aplicados em todos os arquivos
- [ ] CSRF tokens em todos os formulários POST
- [ ] Outputs escapados com h() ou XSSProtection
- [ ] Inputs validados com InputValidator
- [ ] Prepared statements em todas as queries
- [ ] Senhas com password_hash
- [ ] Sessions seguras (HTTPOnly, Secure, SameSite)
- [ ] user_id verificado em todas as queries de sites
- [ ] Erro handling sem vazar informações sensíveis
- [ ] HTTPS obrigatório em produção

---

## 📞 TESTE DE SEGURANÇA

Para testar se está funcionando:

### 1. Teste CSRF:
- Acesse formulário
- Copie HTML do formulário
- Cole em outro domínio
- Tente submeter → Deve falhar

### 2. Teste XSS:
- Tente inserir `<script>alert('XSS')</script>` em campos
- Deve aparecer como texto, não executar

### 3. Teste SQL Injection:
- Tente `' OR '1'='1` em campos
- Não deve afetar queries

### 4. Teste Isolamento:
- Login com User A, crie site
- Login com User B
- Tente acessar `?site_id=X` do User A
- Deve ser bloqueado

---

**🎉 COM ESTAS MELHORIAS, O SISTEMA TERÁ SEGURANÇA DE NÍVEL PROFISSIONAL!**


