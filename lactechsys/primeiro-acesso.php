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

// Processar primeiro acesso
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $name = sanitizeInput($_POST['name'] ?? '');
    $cnpj = sanitizeInput($_POST['cnpj'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($email) || empty($name) || empty($cnpj) || empty($phone) || empty($password) || empty($confirmPassword)) {
        $error = 'Por favor, preencha todos os campos';
    } elseif (!validateEmail($email)) {
        $error = 'Email inválido';
    } elseif (!validateCNPJ($cnpj)) {
        $error = 'CNPJ inválido';
    } elseif ($password !== $confirmPassword) {
        $error = 'As senhas não coincidem';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres';
    } else {
        try {
            $db = Database::getInstance();
            
            // Verificar se email já existe
            $existingUsers = $db->select('users', ['email' => $email]);
            if (!empty($existingUsers)) {
                $error = 'Este email já está cadastrado';
            } else {
                // Criar usuário
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $userData = [
                    'email' => $email,
                    'name' => $name,
                    'password' => $hashedPassword,
                    'cnpj' => $cnpj,
                    'phone' => $phone,
                    'role' => 'proprietario',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $result = $db->insert('users', $userData);
                
                if ($result) {
                    $success = 'Conta criada com sucesso! Você pode fazer login agora.';
                    // Redirecionar para login após 2 segundos
                    echo '<script>setTimeout(function() { window.location.href = "login.php"; }, 2000);</script>';
                } else {
                    $error = 'Erro ao criar conta. Tente novamente.';
                }
            }
        } catch (Exception $e) {
            $error = 'Erro no sistema: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Primeiro Acesso - LacTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dark-theme-fixes.css?v=2.0" rel="stylesheet">
    <link href="assets/css/loading-screen.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        .gradient-forest { 
            background: linear-gradient(135deg, #1a431a 0%, #236523 50%, #2a7f2a 100%); 
        }
        
        .setup-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
<body class="gradient-forest min-h-screen">
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
                <h1 class="text-2xl font-bold text-white">Primeiro Acesso</h1>
                <p class="text-green-100 mt-2">Configure sua conta no LacTech</p>
            </div>

            <!-- Card -->
            <div class="setup-card rounded-2xl p-8 shadow-xl">
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
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nome Completo
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                               placeholder="Seu nome completo"
                               required>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                               placeholder="seu@email.com"
                               required>
                    </div>

                    <div>
                        <label for="cnpj" class="block text-sm font-medium text-gray-700 mb-2">
                            CNPJ da Fazenda
                        </label>
                        <input type="text" 
                               id="cnpj" 
                               name="cnpj" 
                               value="<?php echo htmlspecialchars($_POST['cnpj'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                               placeholder="00.000.000/0000-00"
                               maxlength="18"
                               required>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Telefone
                        </label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                               placeholder="(84) 99999-9999"
                               required>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Senha
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                               placeholder="Mínimo 6 caracteres"
                               minlength="6"
                               required>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirmar Senha
                        </label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                               placeholder="Confirme sua senha"
                               minlength="6"
                               required>
                    </div>

                    <button type="submit" 
                            class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-200">
                        Criar Conta
                    </button>
                </form>

                <!-- Links -->
                <div class="mt-6 text-center">
                    <a href="login.php" class="text-green-600 hover:text-green-800 text-sm">
                        Já tem uma conta? Faça login
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-green-100 text-sm">
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
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('As senhas não coincidem');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('A senha deve ter pelo menos 6 caracteres');
                return false;
            }
        });

        // CNPJ mask
        document.getElementById('cnpj').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = value;
        });

        // Phone mask
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
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
