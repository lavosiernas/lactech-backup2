<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$security = Security::getInstance();

// Se não estiver em processo de 2FA, redirecionar
if (!isset($_SESSION['pending_2fa'])) {
    redirect(LOGIN_URL);
}

$error = '';
$success = '';

// Processar verificação 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = sanitizeInput($_POST['code'] ?? '');
    
    if (empty($code)) {
        $error = 'Por favor, digite o código 2FA';
    } elseif (strlen($code) !== 6 || !is_numeric($code)) {
        $error = 'Código deve ter 6 dígitos numéricos';
    } else {
        $result = $auth->verify2FA($code);
        
        if ($result['success']) {
            $success = 'Autenticação 2FA realizada com sucesso!';
            echo '<script>setTimeout(function() { window.location.href = "dashboard.php"; }, 1000);</script>';
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação 2FA - LacTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/x-icon">
    <style>
        .error-message {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .success-message {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <!-- Logo -->
            <div class="text-center mb-8">
                <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="LacTech" class="h-16 mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Verificação 2FA</h1>
                <p class="text-gray-600 mt-2">Digite o código do seu aplicativo autenticador</p>
            </div>

            <!-- Mensagens -->
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Formulário 2FA -->
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                        Código de Verificação
                    </label>
                    <input type="text" 
                           id="code" 
                           name="code" 
                           maxlength="6"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 text-center text-2xl tracking-widest"
                           placeholder="000000"
                           required>
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                    Verificar Código
                </button>
            </form>

            <!-- Links -->
            <div class="mt-6 text-center">
                <a href="login.php" class="text-blue-600 hover:text-blue-800 text-sm">
                    ← Voltar ao Login
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-500 text-sm">
            <p>&copy; 2024 LacTech. Todos os direitos reservados.</p>
        </div>
    </div>

    <script>
        // Auto-focus no campo de código
        document.getElementById('code').focus();
        
        // Auto-submit quando 6 dígitos forem digitados
        document.getElementById('code').addEventListener('input', function(e) {
            const value = e.target.value.replace(/\D/g, '');
            e.target.value = value;
            
            if (value.length === 6) {
                document.querySelector('form').submit();
            }
        });
        
        // Auto-hide success message
        <?php if ($success): ?>
        setTimeout(function() {
            const successMsg = document.querySelector('.success-message');
            if (successMsg) {
                successMsg.style.display = 'none';
            }
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>
