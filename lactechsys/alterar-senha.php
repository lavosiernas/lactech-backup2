<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();

// Verificar se está logado
$auth->requireLogin();

$user = $auth->getCurrentUser();
$error = '';
$success = '';

// Processar alteração de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Por favor, preencha todos os campos';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'As senhas não coincidem';
    } elseif (strlen($newPassword) < 6) {
        $error = 'A nova senha deve ter pelo menos 6 caracteres';
    } else {
        $result = $auth->changePassword($user['id'], $currentPassword, $newPassword);
        
        if ($result['success']) {
            $success = $result['message'];
            // Redirecionar após 2 segundos
            echo '<script>setTimeout(function() { window.location.href = "dashboard.php"; }, 2000);</script>';
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
    <title>Alterar Senha - LacTech</title>
    
    <!-- PWA Meta Tags -->
    <meta name="description" content="Alterar senha - Sistema LacTech">
    <meta name="theme-color" content="#166534">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="LacTech">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="#166534">
    
    <!-- PWA Icons -->
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/x-icon">
    <link rel="apple-touch-icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- CSS Files -->
    <link href="assets/css/dark-theme-fixes.css?v=2.0" rel="stylesheet">
    <link href="assets/css/loading-screen.css" rel="stylesheet">
    
    <style>
        .gradient-mesh {
            background: linear-gradient(135deg, #f0f9f0 0%, #dcf2dc 25%, #bce5bc 50%, #8dd18d 75%, #5bb85b 100%);
            min-height: 100vh;
        }
        
        .gradient-forest {
            background: linear-gradient(135deg, #166534 0%, #15803d 50%, #16a34a 100%);
        }
        
        .card-shadow {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #166534 0%, #15803d 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(22, 101, 52, 0.3);
        }
        
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
<body class="gradient-mesh">
    <!-- Loading Screen -->
    <div id="loadingScreen" class="fixed inset-0 bg-white z-50 flex items-center justify-center">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto mb-4"></div>
            <p class="text-gray-600">Carregando...</p>
        </div>
    </div>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full">
            <!-- Header -->
            <div class="text-center mb-8">
                <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="LacTech" class="h-16 mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Alterar Senha</h1>
                <p class="text-gray-600 mt-2">Atualize sua senha de acesso</p>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl card-shadow p-8">
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

                <!-- Formulário -->
                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Senha Atual
                        </label>
                        <input type="password" 
                               id="current_password" 
                               name="current_password" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                               placeholder="Digite sua senha atual"
                               required>
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Nova Senha
                        </label>
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                               placeholder="Digite sua nova senha"
                               minlength="6"
                               required>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirmar Nova Senha
                        </label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                               placeholder="Confirme sua nova senha"
                               minlength="6"
                               required>
                    </div>

                    <button type="submit" 
                            class="w-full btn-primary text-white py-3 px-4 rounded-lg font-medium">
                        Alterar Senha
                    </button>
                </form>

                <!-- Links -->
                <div class="mt-6 text-center">
                    <a href="dashboard.php" class="text-green-600 hover:text-green-800 text-sm">
                        ← Voltar ao Dashboard
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-gray-500 text-sm">
                <p>&copy; 2024 LacTech. Todos os direitos reservados.</p>
            </div>
        </div>
    </div>

    <script>
        // Hide loading screen
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('loadingScreen').style.display = 'none';
            }, 500);
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('As senhas não coincidem');
                return false;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('A nova senha deve ter pelo menos 6 caracteres');
                return false;
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
