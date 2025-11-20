<?php
/**
 * SafeNode - Página de Login
 */

session_start();

// Processar logout
if (isset($_GET['logout'])) {
    session_destroy();
    $logoutMessage = 'Você foi desconectado com sucesso.';
}

// Verificar se já está logado
if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';

// Processar login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validação básica
    if (empty($username) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $pdo = getSafeNodeDatabase();
            
            if (!$pdo) {
                $error = 'Erro ao conectar ao banco de dados. Tente novamente.';
            } else {
                // Buscar usuário
                $stmt = $pdo->prepare("SELECT id, username, email, password_hash, full_name, role, is_active, email_verified FROM safenode_users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    $error = 'Usuário não encontrado. Verifique o nome de usuário ou email.';
                } else {
                    // Limpar possíveis espaços no hash
                    $passwordHash = trim($user['password_hash']);
                    
                    // Debug detalhado (apenas em desenvolvimento)
                    if (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL') {
                        error_log("SafeNode Login Debug:");
                        error_log("  Username: $username");
                        error_log("  Hash length: " . strlen($passwordHash));
                        error_log("  Hash preview: " . substr($passwordHash, 0, 30) . "...");
                        error_log("  Password length: " . strlen($password));
                    }
                    
                    if (!password_verify($password, $passwordHash)) {
                        // Log do erro para debug (apenas em desenvolvimento)
                        if (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL') {
                            error_log("SafeNode Login: Senha incorreta para usuário: $username");
                            error_log("Hash no banco: " . substr($passwordHash, 0, 30) . "...");
                            error_log("Tentando verificar novamente com hash limpo...");
                        }
                        $error = 'Senha incorreta. Verifique sua senha e tente novamente.';
                    } elseif (!$user['is_active']) {
                        $error = 'Sua conta está inativa. Entre em contato com o administrador.';
                    } elseif (!$user['email_verified'] && $user['role'] !== 'admin') {
                        // Apenas usuários não-admin precisam verificar email
                        $error = 'Por favor, verifique seu email antes de fazer login. Verifique sua caixa de entrada.';
                    } else {
                        // Se for admin sem verificação, marcar como verificado automaticamente
                        if (!$user['email_verified'] && $user['role'] === 'admin') {
                            $verifyStmt = $pdo->prepare("UPDATE safenode_users SET email_verified = 1, email_verified_at = NOW() WHERE id = ?");
                            $verifyStmt->execute([$user['id']]);
                        }
                        // Atualizar último login
                        $updateStmt = $pdo->prepare("UPDATE safenode_users SET last_login = NOW() WHERE id = ?");
                        $updateStmt->execute([$user['id']]);
                        
                        $_SESSION['safenode_logged_in'] = true;
                        $_SESSION['safenode_username'] = $user['username'];
                        $_SESSION['safenode_user_id'] = $user['id'];
                        $_SESSION['safenode_user_email'] = $user['email'];
                        $_SESSION['safenode_user_full_name'] = $user['full_name'];
                        $_SESSION['safenode_user_role'] = $user['role'];
                        
                        header('Location: dashboard.php');
                        exit;
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("SafeNode Login Error: " . $e->getMessage());
            $error = 'Erro ao processar login. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeNode - Login</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="apple-touch-icon" href="assets/img/logos (6).png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .gradient-mesh {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }
        
        .error-message {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }

        .success-message {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="gradient-mesh min-h-screen">
    <!-- Mobile Layout -->
    <div class="md:hidden min-h-screen flex flex-col">
        <div class="flex-1 bg-white p-6 pt-12">
            <!-- Logo e título no topo -->
            <div class="text-center mb-8">
                <img src="assets/img/logos (5).png" alt="Logo SafeNode" class="w-16 h-16 rounded-2xl shadow-lg object-contain mx-auto mb-4" loading="eager" width="64" height="64">
                <h1 class="text-2xl font-bold text-slate-900 mb-1">SafeNode</h1>
                <p class="text-slate-600 text-sm mb-6">Sistema de Segurança</p>
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Bem-vindo de volta!</h2>
                <p class="text-slate-600">Entre na sua conta</p>
            </div>

            <!-- Error/Success Messages -->
            <div id="errorMessage" class="error-message">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span id="errorText"><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>

            <div id="successMessage" class="success-message">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span id="successText"><?php echo isset($logoutMessage) ? htmlspecialchars($logoutMessage) : 'Login realizado com sucesso!'; ?></span>
                </div>
            </div>

            <form id="loginForm" method="POST" class="space-y-6">
                <input type="hidden" name="login" value="1">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nome ou Email</label>
                    <input 
                        type="text" 
                        required 
                        name="username" 
                        autocomplete="username"
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-black focus:ring-2 focus:ring-black/10 focus:outline-none" 
                        placeholder="Digite seu nome ou email"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    >
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Senha</label>
                    <div class="relative">
                        <input 
                            type="password" 
                            required 
                            name="password" 
                            id="password" 
                            autocomplete="current-password"
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-black focus:ring-2 focus:ring-black/10 focus:outline-none pr-12" 
                            placeholder="Sua senha"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword()" 
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        >
                            <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-black border-slate-300 rounded focus:ring-black">
                        <span class="ml-2 text-sm text-slate-600">Lembrar de mim</span>
                    </label>
                </div>

                <button type="submit" id="loginBtn" class="w-full bg-black text-white py-3 px-4 rounded-xl font-semibold hover:bg-slate-800 hover:shadow-lg transition-all">
                    <span class="loading-spinner" id="loadingSpinner" style="display: none;"></span>
                    <span id="loginText">Entrar</span>
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-slate-600 text-sm">
                    Não tem uma conta? 
                    <a href="register.php" class="font-semibold text-black hover:underline">Criar conta</a>
                </p>
            </div>

            <div class="mt-8 text-center">
                <p class="text-slate-600 text-sm">
                    Sistema protegido por <span class="font-semibold text-slate-900">SafeNode</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Desktop Layout -->
    <div class="hidden md:flex min-h-screen">
        <!-- Form -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Logo acima do bem-vindo -->
                <div class="text-center mb-8">
                    <img src="assets/img/logos (5).png" alt="Logo SafeNode" class="w-16 h-16 rounded-2xl shadow-lg object-contain mx-auto mb-4" loading="lazy" width="64" height="64">
                    <h2 class="text-3xl font-bold text-slate-900 mb-2">Bem-vindo de volta!</h2>
                    <p class="text-slate-600">Entre na sua conta</p>
                </div>

                <!-- Error/Success Messages Desktop -->
                <div id="errorMessageDesktop" class="error-message">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span id="errorTextDesktop"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>

                <div id="successMessageDesktop" class="success-message">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span id="successTextDesktop"><?php echo isset($logoutMessage) ? htmlspecialchars($logoutMessage) : 'Login realizado com sucesso!'; ?></span>
                    </div>
                </div>

                <form id="loginFormDesktop" method="POST" class="space-y-6">
                    <input type="hidden" name="login" value="1">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nome ou Email</label>
                    <input 
                        type="text" 
                        required 
                        name="username" 
                        autocomplete="username"
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-black focus:ring-2 focus:ring-black/10 focus:outline-none" 
                        placeholder="Digite seu nome ou email"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    >
                </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Senha</label>
                        <div class="relative">
                            <input 
                                type="password" 
                                required 
                                name="password" 
                                id="passwordDesktop" 
                                autocomplete="current-password"
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-black focus:ring-2 focus:ring-black/10 focus:outline-none pr-12" 
                                placeholder="Sua senha"
                            >
                            <button 
                                type="button" 
                                onclick="togglePasswordDesktop()" 
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            >
                                <svg id="eyeIconDesktop" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-black border-slate-300 rounded focus:ring-black">
                            <span class="ml-2 text-sm text-slate-600">Lembrar de mim</span>
                        </label>
                    </div>

                    <button type="submit" id="loginBtnDesktop" class="w-full bg-black text-white py-3 px-4 rounded-xl font-semibold hover:bg-slate-800 hover:shadow-lg transition-all">
                        <span class="loading-spinner" id="loadingSpinnerDesktop" style="display: none;"></span>
                        <span id="loginTextDesktop">Entrar</span>
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-slate-600 text-sm">
                        Não tem uma conta? 
                        <a href="register.php" class="font-semibold text-black hover:underline">Criar conta</a>
                    </p>
                </div>

                <div class="mt-8 text-center">
                    <p class="text-slate-600 text-sm">
                        Sistema protegido por <span class="font-semibold text-slate-900">SafeNode</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password toggle functions
        function togglePassword() {
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                password.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        function togglePasswordDesktop() {
            const password = document.getElementById('passwordDesktop');
            const eyeIcon = document.getElementById('eyeIconDesktop');
            
            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                password.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        // Show/hide messages
        function showError(message, isDesktop = false) {
            const errorDiv = document.getElementById(isDesktop ? 'errorMessageDesktop' : 'errorMessage');
            const errorText = document.getElementById(isDesktop ? 'errorTextDesktop' : 'errorText');
            const successDiv = document.getElementById(isDesktop ? 'successMessageDesktop' : 'successMessage');
            
            if (message) {
                errorText.textContent = message;
                errorDiv.style.display = 'block';
                successDiv.style.display = 'none';
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    errorDiv.style.display = 'none';
                }, 5000);
            }
        }

        function showSuccess(message, isDesktop = false) {
            const successDiv = document.getElementById(isDesktop ? 'successMessageDesktop' : 'successMessage');
            const successText = document.getElementById(isDesktop ? 'successTextDesktop' : 'successText');
            const errorDiv = document.getElementById(isDesktop ? 'errorMessageDesktop' : 'errorMessage');
            
            if (message) {
                successText.textContent = message;
                successDiv.style.display = 'block';
                errorDiv.style.display = 'none';
            }
        }

        function hideMessages(isDesktop = false) {
            const errorDiv = document.getElementById(isDesktop ? 'errorMessageDesktop' : 'errorMessage');
            const successDiv = document.getElementById(isDesktop ? 'successMessageDesktop' : 'successMessage');
            
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
        }

        // Set loading state
        function setLoadingState(isLoading, isDesktop = false) {
            const loginBtn = document.getElementById(isDesktop ? 'loginBtnDesktop' : 'loginBtn');
            const loadingSpinner = document.getElementById(isDesktop ? 'loadingSpinnerDesktop' : 'loadingSpinner');
            const loginText = document.getElementById(isDesktop ? 'loginTextDesktop' : 'loginText');
            
            if (isLoading) {
                loginBtn.disabled = true;
                loadingSpinner.style.display = 'inline-block';
                loginText.textContent = 'Entrando...';
            } else {
                loginBtn.disabled = false;
                loadingSpinner.style.display = 'none';
                loginText.textContent = 'Entrar';
            }
        }

        // Handle form submission
        document.getElementById('loginForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            handleLogin(this, false);
        });

        document.getElementById('loginFormDesktop')?.addEventListener('submit', function(e) {
            e.preventDefault();
            handleLogin(this, true);
        });

        function handleLogin(form, isDesktop) {
            const formData = new FormData(form);
            const username = formData.get('username');
            const password = formData.get('password');
            
            // Validate inputs
            if (!username || !password) {
                showError('Por favor, preencha todos os campos.', isDesktop);
                return;
            }
            
            // Hide previous messages and show loading
            hideMessages(isDesktop);
            setLoadingState(true, isDesktop);
            
            // Submit form
            setTimeout(() => {
                form.submit();
            }, 500);
        }

        // Show error/success messages on page load
        <?php if ($error): ?>
            showError('<?php echo addslashes($error); ?>', false);
            showError('<?php echo addslashes($error); ?>', true);
        <?php endif; ?>
        
        <?php if (isset($logoutMessage)): ?>
            showSuccess('<?php echo addslashes($logoutMessage); ?>', false);
            showSuccess('<?php echo addslashes($logoutMessage); ?>', true);
        <?php endif; ?>
    </script>
</body>
</html>
