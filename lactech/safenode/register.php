<?php
/**
 * SafeNode - Página de Cadastro
 */

session_start();

// Verificar se já está logado
if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/EmailService.php';

// Processar cadastro
$error = '';
$success = '';
$userId = null;
$userEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validação básica
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um email válido.';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($password !== $confirmPassword) {
        $error = 'As senhas não coincidem.';
    } elseif (strlen($username) < 3) {
        $error = 'O usuário deve ter no mínimo 3 caracteres.';
    } else {
        try {
            $pdo = getSafeNodeDatabase();
            
            if (!$pdo) {
                $error = 'Erro ao conectar ao banco de dados. Tente novamente.';
            } else {
                // Verificar se usuário já existe
                $stmt = $pdo->prepare("SELECT id FROM safenode_users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                
                if ($stmt->fetch()) {
                    $error = 'Usuário ou email já cadastrado.';
                } else {
                    // Criar hash da senha
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Inserir usuário (inativo até verificar email)
                    $stmt = $pdo->prepare("
                        INSERT INTO safenode_users (username, email, password_hash, full_name, role, is_active, email_verified) 
                        VALUES (?, ?, ?, ?, 'user', 0, 0)
                    ");
                    
                    $stmt->execute([$username, $email, $passwordHash, $fullName ?: null]);
                    $userId = $pdo->lastInsertId();
                    $userEmail = $email;
                    
                    // Gerar código OTP de 6 dígitos
                    $otpCode = str_pad(strval(rand(100000, 999999)), 6, '0', STR_PAD_LEFT);
                    
                    // Expira em 10 minutos
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    
                    // Salvar código OTP no banco
                    $stmt = $pdo->prepare("
                        INSERT INTO safenode_otp_codes (user_id, email, otp_code, action, expires_at) 
                        VALUES (?, ?, ?, 'email_verification', ?)
                    ");
                    $stmt->execute([$userId, $email, $otpCode, $expiresAt]);
                    
                    // Enviar email com código OTP
                    $emailService = SafeNodeEmailService::getInstance();
                    $emailResult = $emailService->sendRegistrationOTP($email, $otpCode, $fullName ?: $username);
                    
                    if ($emailResult['success']) {
                        // Redirecionar para página de verificação
                        $_SESSION['safenode_register_user_id'] = $userId;
                        $_SESSION['safenode_register_email'] = $email;
                        header('Location: verify-otp.php');
                        exit;
                    } else {
                        // Se falhar ao enviar email, deletar usuário criado
                        $stmt = $pdo->prepare("DELETE FROM safenode_users WHERE id = ?");
                        $stmt->execute([$userId]);
                        
                        $error = 'Erro ao enviar código de verificação. Tente novamente.';
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("SafeNode Register Error: " . $e->getMessage());
            $error = 'Erro ao processar cadastro. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeNode - Cadastro</title>
    
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
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Criar conta</h2>
                <p class="text-slate-600">Preencha os dados para se cadastrar</p>
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
                    <span id="successText"><?php echo htmlspecialchars($success); ?></span>
                </div>
            </div>

            <form id="registerForm" method="POST" class="space-y-6">
                <input type="hidden" name="register" value="1">
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nome Completo</label>
                    <input 
                        type="text" 
                        name="full_name" 
                        autocomplete="name"
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-black focus:ring-2 focus:ring-black/10 focus:outline-none" 
                        placeholder="Seu nome completo"
                        value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Usuário <span class="text-red-500">*</span></label>
                    <input 
                        type="text" 
                        required 
                        name="username" 
                        autocomplete="username"
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-black focus:ring-2 focus:ring-black/10 focus:outline-none" 
                        placeholder="Digite seu usuário"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Email <span class="text-red-500">*</span></label>
                    <input 
                        type="email" 
                        required 
                        name="email" 
                        autocomplete="email"
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-black focus:ring-2 focus:ring-black/10 focus:outline-none" 
                        placeholder="seu@email.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    >
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Senha <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input 
                            type="password" 
                            required 
                            name="password" 
                            id="password" 
                            autocomplete="new-password"
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-black focus:ring-2 focus:ring-black/10 focus:outline-none pr-12" 
                            placeholder="Mínimo 6 caracteres"
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

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Confirmar Senha <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input 
                            type="password" 
                            required 
                            name="confirm_password" 
                            id="confirmPassword" 
                            autocomplete="new-password"
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-black focus:ring-2 focus:ring-black/10 focus:outline-none pr-12" 
                            placeholder="Digite a senha novamente"
                        >
                        <button 
                            type="button" 
                            onclick="toggleConfirmPassword()" 
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        >
                            <svg id="eyeIconConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" id="registerBtn" class="w-full bg-black text-white py-3 px-4 rounded-xl font-semibold hover:bg-slate-800 hover:shadow-lg transition-all">
                    <span class="loading-spinner" id="loadingSpinner" style="display: none;"></span>
                    <span id="registerText">Criar conta</span>
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-slate-600 text-sm">
                    Já tem uma conta? 
                    <a href="login.php" class="font-semibold text-black hover:underline">Fazer login</a>
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
                    <h2 class="text-3xl font-bold text-slate-900 mb-2">Criar conta</h2>
                    <p class="text-slate-600">Preencha os dados para se cadastrar</p>
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
                        <span id="successTextDesktop"><?php echo htmlspecialchars($success); ?></span>
                    </div>
                </div>

                <form id="registerFormDesktop" method="POST" class="space-y-6">
                    <input type="hidden" name="register" value="1">
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nome Completo</label>
                        <input 
                            type="text" 
                            name="full_name" 
                            autocomplete="name"
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-black focus:ring-2 focus:ring-black/10 focus:outline-none" 
                            placeholder="Seu nome completo"
                            value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Usuário <span class="text-red-500">*</span></label>
                        <input 
                            type="text" 
                            required 
                            name="username" 
                            autocomplete="username"
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-black focus:ring-2 focus:ring-black/10 focus:outline-none" 
                            placeholder="Digite seu usuário"
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email <span class="text-red-500">*</span></label>
                        <input 
                            type="email" 
                            required 
                            name="email" 
                            autocomplete="email"
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-black focus:ring-2 focus:ring-black/10 focus:outline-none" 
                            placeholder="seu@email.com"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Senha <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input 
                                type="password" 
                                required 
                                name="password" 
                                id="passwordDesktop" 
                                autocomplete="new-password"
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-black focus:ring-2 focus:ring-black/10 focus:outline-none pr-12" 
                                placeholder="Mínimo 6 caracteres"
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

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Confirmar Senha <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input 
                                type="password" 
                                required 
                                name="confirm_password" 
                                id="confirmPasswordDesktop" 
                                autocomplete="new-password"
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-black focus:ring-2 focus:ring-black/10 focus:outline-none pr-12" 
                                placeholder="Digite a senha novamente"
                            >
                            <button 
                                type="button" 
                                onclick="toggleConfirmPasswordDesktop()" 
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            >
                                <svg id="eyeIconConfirmDesktop" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" id="registerBtnDesktop" class="w-full bg-black text-white py-3 px-4 rounded-xl font-semibold hover:bg-slate-800 hover:shadow-lg transition-all">
                        <span class="loading-spinner" id="loadingSpinnerDesktop" style="display: none;"></span>
                        <span id="registerTextDesktop">Criar conta</span>
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-slate-600 text-sm">
                        Já tem uma conta? 
                        <a href="login.php" class="font-semibold text-black hover:underline">Fazer login</a>
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

        function toggleConfirmPassword() {
            const password = document.getElementById('confirmPassword');
            const eyeIcon = document.getElementById('eyeIconConfirm');
            
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

        function toggleConfirmPasswordDesktop() {
            const password = document.getElementById('confirmPasswordDesktop');
            const eyeIcon = document.getElementById('eyeIconConfirmDesktop');
            
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
                
                // Redirect to login after 3 seconds if successful
                if (message.includes('sucesso')) {
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 3000);
                }
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
            const registerBtn = document.getElementById(isDesktop ? 'registerBtnDesktop' : 'registerBtn');
            const loadingSpinner = document.getElementById(isDesktop ? 'loadingSpinnerDesktop' : 'loadingSpinner');
            const registerText = document.getElementById(isDesktop ? 'registerTextDesktop' : 'registerText');
            
            if (isLoading) {
                registerBtn.disabled = true;
                loadingSpinner.style.display = 'inline-block';
                registerText.textContent = 'Criando conta...';
            } else {
                registerBtn.disabled = false;
                loadingSpinner.style.display = 'none';
                registerText.textContent = 'Criar conta';
            }
        }

        // Handle form submission
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            handleRegister(this, false);
        });

        document.getElementById('registerFormDesktop')?.addEventListener('submit', function(e) {
            e.preventDefault();
            handleRegister(this, true);
        });

        function handleRegister(form, isDesktop) {
            const formData = new FormData(form);
            const username = formData.get('username');
            const email = formData.get('email');
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            
            // Validate inputs
            if (!username || !email || !password || !confirmPassword) {
                showError('Por favor, preencha todos os campos obrigatórios.', isDesktop);
                return;
            }

            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                showError('Por favor, insira um email válido.', isDesktop);
                return;
            }

            if (password.length < 6) {
                showError('A senha deve ter no mínimo 6 caracteres.', isDesktop);
                return;
            }

            if (password !== confirmPassword) {
                showError('As senhas não coincidem.', isDesktop);
                return;
            }

            if (username.length < 3) {
                showError('O usuário deve ter no mínimo 3 caracteres.', isDesktop);
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
        
        <?php if ($success): ?>
            showSuccess('<?php echo addslashes($success); ?>', false);
            showSuccess('<?php echo addslashes($success); ?>', true);
        <?php endif; ?>
    </script>
</body>
</html>

