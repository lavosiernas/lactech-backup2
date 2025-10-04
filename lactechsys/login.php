<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();

// Se já estiver logado, redirecionar para dashboard
if ($auth->isLoggedIn()) {
    redirect(DASHBOARD_URL);
}

$error = '';
$success = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, preencha todos os campos';
    } elseif (!validateEmail($email)) {
        $error = 'Email inválido';
    } else {
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            if (isset($result['requires_2fa']) && $result['requires_2fa']) {
                // Redirecionar para verificação 2FA
                header('Location: 2fa-verify.php');
                exit;
            } else {
                $success = 'Login realizado com sucesso!';
                // Redirecionar após 1 segundo
                echo '<script>setTimeout(function() { window.location.href = "dashboard.php"; }, 1000);</script>';
            }
        } else {
            $error = $result['message'];
        }
    }
}

// Verificar notificação
$notification = getNotification();
if ($notification) {
    if ($notification['type'] === 'success') {
        $success = $notification['message'];
    } else {
        $error = $notification['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LacTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
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
                <h1 class="text-2xl font-bold text-gray-900">LacTech</h1>
                <p class="text-gray-600 mt-2">Sistema de Gestão Agropecuária</p>
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

            <!-- Formulário de Login -->
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                           placeholder="seu@email.com"
                           required>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Senha
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                           placeholder="Sua senha"
                           required>
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                    Entrar
                </button>
            </form>

            <!-- Links -->
            <div class="mt-6 text-center space-y-2">
                <a href="solicitar-alteracao.php" class="text-blue-600 hover:text-blue-800 text-sm">
                    Esqueceu sua senha?
                </a>
                <br>
                <a href="primeiro-acesso.php" class="text-blue-600 hover:text-blue-800 text-sm">
                    Primeiro acesso
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-500 text-sm">
            <p>&copy; 2024 LacTech. Todos os direitos reservados.</p>
        </div>
    </div>

    <script>
        // Auto-hide success message
        <?php if ($success): ?>
        setTimeout(function() {
            const successMsg = document.querySelector('.success-message');
            if (successMsg) {
                successMsg.style.display = 'none';
            }
        }, 3000);
        <?php endif; ?>

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos');
                return false;
            }
        });
    </script>
</body>
</html>
