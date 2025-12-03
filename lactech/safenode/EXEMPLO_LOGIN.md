# Como Integrar o SDK SafeNode em uma Página de Login

## Passo 1: Obter o Código de Integração

1. Acesse o dashboard do SafeNode
2. Vá em **Verificação Humana**
3. Gere uma nova API Key ou use uma existente
4. Clique em **Copiar Código** na API Key ativa
5. O código já vem com sua API Key única configurada

## Passo 2: Adicionar na Página de Login (Frontend)

### Exemplo HTML/PHP - Página de Login

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Meu Site</title>
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        
        <!-- Formulário de Login -->
        <form id="loginForm" method="POST" action="processar-login.php">
            <div>
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            
            <div>
                <label>Senha:</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit">Entrar</button>
        </form>
    </div>

    <!-- COLE O CÓDIGO DE INTEGRAÇÃO AQUI (antes do </body>) -->
    <!-- O código que você copiou do dashboard já inclui tudo necessário -->
    
</body>
</html>
```

### O código de integração que você copia do dashboard é assim:

```html
<!-- SafeNode Human Verification -->
<script src="https://safenode.cloud/sdk/safenode-hv.js"></script>
<script>
(function() {
    const apiKey = 'SUA_API_KEY_AQUI';
    const apiUrl = 'https://safenode.cloud/api/sdk';
    const hv = new SafeNodeHV(apiUrl, apiKey);
    
    // Inicializar
    hv.init().then(() => {
        // Adicionar campos aos formulários
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            // Adicionar campos hidden
            if (form.id) {
                hv.attachToForm('#' + form.id);
            } else {
                // Se não tem ID, criar um temporário
                const tempId = 'safenode_form_' + Math.random().toString(36).substr(2, 9);
                form.id = tempId;
                hv.attachToForm('#' + tempId);
            }
            
            // Validar antes de enviar
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    await hv.validateForm('#' + form.id);
                    form.submit();
                } catch (error) {
                    alert('Verificação de segurança falhou. Tente novamente.');
                }
            });
        });
    }).catch((error) => {
        console.error('Erro ao inicializar SafeNode HV:', error);
    });
})();
</script>
```

## Passo 3: Validar no Backend (PHP)

### Exemplo: processar-login.php

```php
<?php
/**
 * processar-login.php
 * Processa o login e valida a verificação humana
 */

// 1. PRIMEIRO: Validar a verificação humana
require_once 'path/to/safenode/includes/config.php';
require_once 'path/to/safenode/includes/HVAPIKeyManager.php';

$token = $_POST['safenode_hv_token'] ?? '';
$jsFlag = $_POST['safenode_hv_js'] ?? '';
$apiKey = $_POST['safenode_api_key'] ?? '';

// Validar a verificação humana
$validationResult = HVAPIKeyManager::validateToken(
    $token,
    $apiKey,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT'] ?? '',
    $_SERVER['HTTP_REFERER'] ?? ''
);

if (!$validationResult['valid']) {
    // Verificação falhou - bloquear login
    http_response_code(403);
    die(json_encode([
        'success' => false,
        'error' => 'Verificação de segurança falhou. Tente novamente.'
    ]));
}

// 2. SE A VERIFICAÇÃO PASSOU: Processar o login normalmente
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Aqui você faz a validação normal do login
// Exemplo:
if (empty($email) || empty($password)) {
    die(json_encode(['success' => false, 'error' => 'Email e senha são obrigatórios']));
}

// Validar credenciais no banco de dados
// ... seu código de autenticação aqui ...

// Se tudo estiver OK:
session_start();
$_SESSION['user_id'] = $user_id;
$_SESSION['email'] = $email;

echo json_encode(['success' => true, 'message' => 'Login realizado com sucesso']);
?>
```

## Passo 4: Integração Manual (Alternativa)

Se preferir integrar manualmente sem usar o código gerado:

### Frontend:

```html
<script src="https://safenode.cloud/sdk/safenode-hv.js"></script>
<script>
// Substitua 'SUA_API_KEY' pela sua API Key do dashboard
const hv = new SafeNodeHV('https://safenode.cloud/api/sdk', 'SUA_API_KEY');

// Inicializar quando a página carregar
document.addEventListener('DOMContentLoaded', async () => {
    try {
        await hv.init();
        console.log('SafeNode HV inicializado com sucesso');
    } catch (error) {
        console.error('Erro ao inicializar SafeNode HV:', error);
    }
});

// Quando o formulário for enviado
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    try {
        // Validar verificação humana
        const isValid = await hv.validate();
        
        if (!isValid) {
            alert('Verificação de segurança falhou');
            return;
        }
        
        // Adicionar campos hidden ao formulário
        hv.attachToForm('#loginForm');
        
        // Enviar formulário
        e.target.submit();
    } catch (error) {
        console.error('Erro na validação:', error);
        alert('Erro ao validar verificação. Tente novamente.');
    }
});
</script>
```

## Resumo

1. **Frontend**: Cole o código de integração antes do `</body>` na sua página de login
2. **Backend**: Valide o token usando `HVAPIKeyManager::validateToken()` antes de processar o login
3. **Segurança**: O SDK automaticamente:
   - Detecta se JavaScript está habilitado
   - Valida o tempo de resposta
   - Verifica o IP
   - Protege contra ataques automatizados

## Importante

- ✅ O código gerado no dashboard já vem com sua API Key configurada
- ✅ Funciona automaticamente em todos os formulários da página
- ✅ Valida antes de enviar o formulário
- ✅ Você só precisa validar no backend usando `HVAPIKeyManager::validateToken()`

