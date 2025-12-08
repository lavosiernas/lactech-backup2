<?php
/**
 * SafeNode - Página de Cadastro
 */

session_start();

// SEGURANÇA: Carregar helpers e aplicar headers de segurança
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

// Verificar se já está logado
if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/EmailService.php';
require_once __DIR__ . '/includes/HumanVerification.php';

// Inicializar desafio de verificação humana SafeNode
$safenodeHvToken = SafeNodeHumanVerification::initChallenge();

// Processar cadastro
$error = '';
$success = '';
$userId = null;
$userEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // SEGURANÇA: Validar CSRF token
    if (!CSRFProtection::validate()) {
        $error = 'Token de segurança inválido. Recarregue a página e tente novamente.';
    } else {
        $username = XSSProtection::sanitize($_POST['username'] ?? '');
        $email = XSSProtection::sanitize($_POST['email'] ?? '');
        $fullName = XSSProtection::sanitize($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // SEGURANÇA: Validações adicionais
        if (!InputValidator::username($username)) {
            $error = 'Usuário deve ter entre 3-30 caracteres (letras, números, _ ou -)';
        } elseif (!InputValidator::email($email)) {
            $error = 'Email inválido';
        } elseif (!InputValidator::strongPassword($password)) {
            $error = 'Senha deve ter no mínimo 8 caracteres, incluindo letras maiúsculas, minúsculas, números e símbolos';
        } elseif ($password !== $confirmPassword) {
            $error = 'As senhas não coincidem';
        }
    }
    
    if (!$error) {
    // Validar verificação humana SafeNode antes de qualquer coisa
    $hvError = '';
    if (!SafeNodeHumanVerification::validateRequest($_POST, $hvError)) {
        $error = $hvError ?: 'Falha na verificação de segurança.';
    }
    // Validação básica
    elseif (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um email válido.';
    } elseif (strlen($password) < 8) {
        $error = 'A senha deve ter no mínimo 8 caracteres.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'A senha deve conter pelo menos uma letra maiúscula.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'A senha deve conter pelo menos uma letra minúscula.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'A senha deve conter pelo menos um número.';
    } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        $error = 'A senha deve conter pelo menos um caractere especial (!@#$%^&*).';
    } elseif ($password !== $confirmPassword) {
        $error = 'As senhas não coincidem.';
    } elseif (strlen($username) < 3) {
        $error = 'O usuário deve ter no mínimo 3 caracteres.';
    } else {
        // Verificar números repetidos na senha
        preg_match_all('/[0-9]/', $password, $matches);
        if (count($matches[0]) > 1) {
            $numberArray = $matches[0];
            $uniqueNumbers = array_unique($numberArray);
            if (count($uniqueNumbers) < count($numberArray)) {
                $error = 'A senha não pode conter números repetidos.';
            }
        }
        
        // Verificar se senha é igual ao username (case insensitive)
        if (!$error && strtolower($password) === strtolower($username)) {
            $error = 'A senha não pode ser igual ao nome de usuário.';
        }
    }
    
    if (!$error) {
        try {
            $pdo = getSafeNodeDatabase();
            
            if (!$pdo) {
                $error = 'Erro ao conectar ao banco de dados. Tente novamente.';
            } else {
                // Verificar se email já existe (username pode ser duplicado)
                $stmt = $pdo->prepare("SELECT id FROM safenode_users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $error = 'Este email ja esta vinculado a uma conta';
                } else {
                    // NÃO criar usuário ainda - apenas salvar dados temporariamente na sessão
                    // Criar hash da senha para salvar temporariamente
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Salvar dados temporários na sessão (será criado no banco apenas após verificar OTP)
                    $_SESSION['safenode_register_data'] = [
                        'username' => $username,
                        'email' => $email,
                        'password_hash' => $passwordHash,
                        'full_name' => $fullName ?: null
                    ];
                    
                    // Verificar se há plano selecionado
                    $selectedPlan = $_GET['plan'] ?? null;
                    if ($selectedPlan) {
                        $_SESSION['safenode_register_plan'] = $selectedPlan;
                    }
                    
                    // Gerar código OTP de 6 dígitos
                    $otpCode = str_pad(strval(rand(100000, 999999)), 6, '0', STR_PAD_LEFT);
                    
                    // Expira em 10 minutos
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    
                    // Salvar código OTP no banco (sem user_id ainda, usaremos email como identificador)
                    $stmt = $pdo->prepare("
                        INSERT INTO safenode_otp_codes (user_id, email, otp_code, action, expires_at) 
                        VALUES (NULL, ?, ?, 'email_verification', ?)
                    ");
                    $stmt->execute([$email, $otpCode, $expiresAt]);
                    
                    // Enviar email com código OTP
                    $emailService = SafeNodeEmailService::getInstance();
                    $emailResult = $emailService->sendRegistrationOTP($email, $otpCode, $fullName ?: $username);
                    
                    if ($emailResult['success']) {
                        // Desafio usado com sucesso - resetar para próxima página
                        SafeNodeHumanVerification::reset();
                        
                        // Redirecionar para página de verificação
                        header('Location: verify-otp.php');
                        exit;
                    } else {
                        // Se falhar ao enviar email, limpar dados temporários
                        unset($_SESSION['safenode_register_data'], $_SESSION['safenode_register_plan']);
                        
                        $error = 'Erro ao enviar código de verificação. Tente novamente.';
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("SafeNode Register Error: " . $e->getMessage());
            $error = 'Erro ao processar cadastro. Tente novamente.';
        }
    }
    } // Fim do if (!$error)
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastre-se &mdash; SafeNode</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        .loading-spinner {
            width: 20px; height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Fade-in animation */
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in {
            animation: fade-in 0.5s ease-out;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row bg-white">
    
    <!-- Left Side: Image & Branding (Desktop Only) -->
    <div class="hidden md:flex md:w-1/2 lg:w-[55%] relative bg-black text-white overflow-hidden">
        <!-- Background Image -->
        <img src="https://i.postimg.cc/7LvGX8bK/emailotp-(11).jpg" 
             alt="Team Collaboration" 
             class="absolute inset-0 w-full h-full object-cover opacity-50">
        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/20"></div>
        
        <!-- Content Overlay -->
        <div class="relative z-10 flex flex-col justify-between w-full p-12 lg:p-16">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <div class="bg-white/10 p-2 rounded-lg backdrop-blur-md">
                    <img src="assets/img/logos (6).png" alt="SafeNode" class="w-6 h-6 brightness-0 invert">
                </div>
                <span class="text-xl font-bold tracking-tight">SafeNode</span>
            </div>

            <!-- Quote -->
            <div class="max-w-md">
                <blockquote class="text-2xl font-medium leading-snug mb-6">
                    "Junte-se à nossa comunidade e tenha acesso a ferramentas de segurança de nível empresarial."
                </blockquote>
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold">MC</div>
                    <div>
                        <div class="font-semibold">Maria Costa</div>
                        <div class="text-sm text-slate-400">Gerente de TI</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side: Register Form -->
    <div class="w-full md:w-1/2 lg:w-[45%] flex flex-col overflow-y-auto md:h-screen">
        <div class="w-full max-w-md mx-auto px-6 py-8 md:py-12 md:px-10 lg:px-12">
            
            <!-- Header (Mobile Logo) -->
            <div class="md:hidden mb-6 flex items-center gap-2">
                <img src="assets/img/logos (5).png" alt="SafeNode" class="w-8 h-8">
                <span class="text-xl font-bold text-slate-900">SafeNode</span>
            </div>

            <div class="mb-6 md:mb-10">
                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2">Criar sua conta</h1>
                <p class="text-sm md:text-base text-slate-500">Preencha os dados para começar.</p>
            </div>

            <?php if(!empty($error)): ?>
                <div class="mb-4 md:mb-6 p-4 md:p-5 rounded-xl bg-red-500 border-2 border-red-600 shadow-lg shadow-red-500/20 flex items-start gap-3 md:gap-4 animate-fade-in">
                    <div class="flex-shrink-0 w-6 h-6 md:w-7 md:h-7 rounded-full bg-red-600 flex items-center justify-center">
                        <i data-lucide="alert-circle" class="w-4 h-4 md:w-5 md:h-5 text-white"></i>
                    </div>
                    <p class="text-sm md:text-base text-white font-bold leading-relaxed"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['google_error'])): ?>
                <div class="mb-4 md:mb-6 p-4 md:p-5 rounded-xl bg-red-500 border-2 border-red-600 shadow-lg shadow-red-500/20 flex items-start gap-3 md:gap-4 animate-fade-in">
                    <div class="flex-shrink-0 w-6 h-6 md:w-7 md:h-7 rounded-full bg-red-600 flex items-center justify-center">
                        <i data-lucide="alert-circle" class="w-4 h-4 md:w-5 md:h-5 text-white"></i>
                    </div>
                    <p class="text-sm md:text-base text-white font-bold leading-relaxed"><?php echo htmlspecialchars($_SESSION['google_error']); unset($_SESSION['google_error']); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="mb-4 md:mb-6 p-3 md:p-4 rounded-lg bg-emerald-50 border border-emerald-100 flex items-start gap-2 md:gap-3 animate-fade-in">
                    <i data-lucide="check-circle-2" class="w-4 h-4 md:w-5 md:h-5 text-emerald-600 shrink-0 mt-0.5"></i>
                    <p class="text-xs md:text-sm text-emerald-600 font-medium"><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-4 md:space-y-5" id="registerForm">
                <input type="hidden" name="register" value="1">
                <?php echo csrf_field(); ?>
                
                <!-- Nome Completo -->
                <div class="space-y-1.5">
                    <label for="full_name" class="block text-xs md:text-sm font-medium text-slate-700">Nome Completo (Opcional)</label>
                    <input type="text" name="full_name" id="full_name" 
                        class="block w-full px-3 md:px-4 py-2.5 md:py-3 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
                        placeholder="Seu nome completo"
                        value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>

                <!-- Usuário -->
                <div class="space-y-1.5">
                    <label for="username" class="block text-xs md:text-sm font-medium text-slate-700">Usuário</label>
                    <input type="text" name="username" id="username" required 
                        class="block w-full px-3 md:px-4 py-2.5 md:py-3 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
                        placeholder="seu_usuario"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <!-- Email -->
                <div class="space-y-1.5">
                    <label for="email" class="block text-xs md:text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" id="email" required 
                        class="block w-full px-3 md:px-4 py-2.5 md:py-3 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
                        placeholder="seu@email.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <!-- Senha -->
                <div class="space-y-1.5">
                    <label for="password" class="block text-xs md:text-sm font-medium text-slate-700">Senha</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required 
                            class="block w-full px-3 md:px-4 py-2.5 md:py-3 pr-10 md:pr-12 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
                            placeholder="Digite sua senha"
                            oninput="checkPasswordStrength(this.value)">
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility('password', 'eyeIconPassword')" 
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                        >
                            <svg id="eyeIconPassword" class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Password Strength Indicator -->
                    <div id="passwordStrength" class="hidden">
                        <!-- Progress Bar -->
                        <div class="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden mb-2">
                            <div id="strengthBar" class="h-full transition-all duration-300 rounded-full" style="width: 0%"></div>
                        </div>
                        
                        <!-- Strength Text -->
                        <div class="flex items-center gap-2 mb-2">
                            <span id="strengthText" class="text-xs font-medium"></span>
                            <i id="strengthIcon" class="w-4 h-4"></i>
                        </div>
                        
                        <!-- Requirements List -->
                        <ul id="passwordRequirements" class="space-y-1.5 text-xs">
                            <li id="req-length" class="flex items-center gap-2 text-slate-500">
                                <i class="w-3 h-3"></i>
                                <span>Mínimo 8 caracteres</span>
                            </li>
                            <li id="req-uppercase" class="flex items-center gap-2 text-slate-500">
                                <i class="w-3 h-3"></i>
                                <span>Uma letra maiúscula</span>
                            </li>
                            <li id="req-lowercase" class="flex items-center gap-2 text-slate-500">
                                <i class="w-3 h-3"></i>
                                <span>Uma letra minúscula</span>
                            </li>
                            <li id="req-number" class="flex items-center gap-2 text-slate-500">
                                <i class="w-3 h-3"></i>
                                <span>Um número (sem repetir)</span>
                            </li>
                            <li id="req-special" class="flex items-center gap-2 text-slate-500">
                                <i class="w-3 h-3"></i>
                                <span>Um caractere especial (!@#$%^&*)</span>
                            </li>
                            <li id="req-username" class="flex items-center gap-2 text-slate-500">
                                <i class="w-3 h-3"></i>
                                <span>Diferente do nome de usuário</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Confirmar Senha -->
                <div class="space-y-1.5">
                    <label for="confirm_password" class="block text-xs md:text-sm font-medium text-slate-700">Confirmar Senha</label>
                    <input type="password" name="confirm_password" id="confirm_password" required 
                        class="block w-full px-3 md:px-4 py-2.5 md:py-3 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
                        placeholder="••••••••">
                </div>

                <!-- Verificação Humana SafeNode -->
                <div class="mt-2 md:mt-3 p-2.5 md:p-3 rounded-2xl border border-slate-200 bg-slate-50 flex items-center gap-2 md:gap-3 shadow-sm" id="hv-box">
                    <div class="relative flex items-center justify-center w-8 h-8 md:w-9 md:h-9">
                        <div class="absolute inset-0 rounded-2xl border-2 border-slate-200 border-t-black animate-spin" id="hv-spinner"></div>
                        <div class="relative z-10 w-6 h-6 md:w-7 md:h-7 rounded-2xl bg-black flex items-center justify-center">
                            <img src="assets/img/logos (6).png" alt="SafeNode" class="w-3.5 h-3.5 md:w-4 md:h-4 object-contain">
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-[11px] md:text-xs font-semibold text-slate-900 flex items-center gap-1">
                            SafeNode <span class="text-[9px] md:text-[10px] font-normal text-slate-500">verificação humana</span>
                        </p>
                        <p class="text-[10px] md:text-[11px] text-slate-500" id="hv-text">Validando interação do navegador…</p>
                    </div>
                    <svg id="hv-check" class="w-3.5 h-3.5 md:w-4 md:h-4 text-emerald-500 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <input type="hidden" name="safenode_hv_token" value="<?php echo htmlspecialchars($safenodeHvToken); ?>">
                <input type="hidden" name="safenode_hv_js" id="safenode_hv_js" value="">

                <!-- Submit Button -->
                <button type="submit" class="w-full flex justify-center items-center py-2.5 md:py-3 px-4 rounded-lg shadow-sm text-sm font-semibold text-white bg-black hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 transition-all duration-200 mt-4 md:mt-6" id="registerBtn">
                    <span id="loadingSpinner" class="loading-spinner mr-2 hidden"></span>
                    <span id="registerText">Criar conta</span>
                </button>

                <!-- Divider -->
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-slate-500 uppercase text-xs font-medium">Ou</span>
                    </div>
                </div>

                <!-- Google OAuth Button -->
                <a href="google-auth.php?action=register" class="w-full flex justify-center items-center gap-2 md:gap-3 px-4 py-2.5 md:py-3 border border-slate-200 rounded-lg shadow-sm bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors no-underline">
                    <svg class="h-4 w-4 md:h-5 md:w-5" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span class="text-xs md:text-sm">Continuar com Google</span>
                </a>
            </form>

            <p class="mt-6 md:mt-8 text-center text-xs md:text-sm text-slate-600">
                Já tem uma conta? 
                <a href="login.php" class="font-semibold text-black hover:underline">Fazer login</a>
            </p>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Toggle password visibility
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                input.type = 'password';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        // SafeNode Verification Logic
        // Inicializar verificação humana SafeNode
        function initSafeNodeHumanVerification() {
            const hvJs = document.getElementById('safenode_hv_js');
            const hvSpinner = document.getElementById('hv-spinner');
            const hvCheck = document.getElementById('hv-check');
            const hvText = document.getElementById('hv-text');

            // Marcar imediatamente como verificado
            if (hvJs) {
                hvJs.value = '1';
            }

            // Após um pequeno atraso, mostrar visual de verificado
            setTimeout(() => {
                if (hvSpinner) hvSpinner.classList.add('hidden');
                if (hvCheck) hvCheck.classList.remove('hidden');
                if (hvText) hvText.textContent = 'Verificado com SafeNode';
            }, 800);
        }

        // Password Strength Checker
        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('passwordStrength');
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            const strengthIcon = document.getElementById('strengthIcon');
            const passwordInput = document.getElementById('password');
            
            if (!password) {
                strengthDiv.classList.add('hidden');
                passwordInput.classList.remove('border-red-500');
                return;
            }
            
            strengthDiv.classList.remove('hidden');
            
            // Get username for comparison
            const username = document.getElementById('username')?.value || '';
            
            // Check requirements
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
            
            // Check for repeated numbers
            const numbers = password.match(/[0-9]/g) || [];
            const hasRepeatedNumbers = numbers.length > 1 && new Set(numbers).size < numbers.length;
            const validNumber = hasNumber && !hasRepeatedNumbers;
            
            // Check if password equals username (case insensitive)
            const equalsUsername = username && password.toLowerCase() === username.toLowerCase();
            const notEqualsUsername = !equalsUsername;
            
            // Update requirement icons (red if not met)
            updateRequirement('req-length', hasLength);
            updateRequirement('req-uppercase', hasUppercase);
            updateRequirement('req-lowercase', hasLowercase);
            updateRequirement('req-number', validNumber);
            updateRequirement('req-special', hasSpecial);
            updateRequirement('req-username', notEqualsUsername);
            
            // Check if ALL requirements are met
            const allRequirementsMet = hasLength && hasUppercase && hasLowercase && validNumber && hasSpecial && notEqualsUsername;
            
            // Update password input border color
            if (allRequirementsMet) {
                passwordInput.classList.remove('border-red-500');
                passwordInput.classList.add('border-emerald-500');
            } else {
                passwordInput.classList.remove('border-emerald-500');
                passwordInput.classList.add('border-red-500');
            }
            
            // Determine strength level - só fica forte se TODOS os requisitos forem atendidos
            let strength = 'Fraca';
            let color = 'bg-red-500';
            let textColor = 'text-red-600';
            let icon = '<i data-lucide="x-circle" class="w-4 h-4 text-red-500"></i>';
            let percentage = 0;
            
            if (allRequirementsMet) {
                strength = 'Forte';
                color = 'bg-emerald-500';
                textColor = 'text-emerald-600';
                icon = '<i data-lucide="shield-check" class="w-4 h-4 text-emerald-500"></i>';
                percentage = 100;
            } else {
                // Count how many requirements are met
                let metCount = 0;
                if (hasLength) metCount++;
                if (hasUppercase) metCount++;
                if (hasLowercase) metCount++;
                if (validNumber) metCount++;
                if (hasSpecial) metCount++;
                if (notEqualsUsername) metCount++;
                
                percentage = (metCount / 6) * 60; // Max 60% if not all met
                
                if (equalsUsername) {
                    strength = 'Não pode ser igual ao usuário';
                    percentage = 0;
                } else if (hasRepeatedNumbers) {
                    strength = 'Não pode ter números repetidos';
                    percentage = Math.min(percentage, 30);
                } else {
                    strength = 'Falta requisito';
                }
            }
            
            // Update UI
            strengthBar.className = `h-full transition-all duration-300 rounded-full ${color}`;
            strengthBar.style.width = percentage + '%';
            strengthText.className = `text-xs font-bold ${textColor}`;
            strengthText.textContent = `Força: ${strength}`;
            strengthIcon.innerHTML = icon;
            
            // Reinitialize Lucide icons for new icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
        
        function updateRequirement(id, met) {
            const req = document.getElementById(id);
            const icon = req.querySelector('i');
            
            if (met) {
                req.classList.remove('text-red-600', 'text-slate-500');
                req.classList.add('text-emerald-600');
                icon.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            } else {
                req.classList.remove('text-emerald-600', 'text-slate-500');
                req.classList.add('text-red-600');
                icon.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Iniciar verificação humana
            initSafeNodeHumanVerification();
            
            // Re-validate password when username changes
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            if (usernameInput && passwordInput) {
                usernameInput.addEventListener('input', function() {
                    if (passwordInput.value) {
                        checkPasswordStrength(passwordInput.value);
                    }
                });
            }
            
            // Register button loading state
            const form = document.getElementById('registerForm');
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const username = document.getElementById('username').value;
                
                // Validate password strength
                const hasLength = password.length >= 8;
                const hasUppercase = /[A-Z]/.test(password);
                const hasLowercase = /[a-z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
                
                // Check for repeated numbers
                const numbers = password.match(/[0-9]/g) || [];
                const hasRepeatedNumbers = numbers.length > 1 && new Set(numbers).size < numbers.length;
                
                // Check if password equals username
                const equalsUsername = username && password.toLowerCase() === username.toLowerCase();
                
                if (!hasLength || !hasUppercase || !hasLowercase || !hasNumber || !hasSpecial || hasRepeatedNumbers || equalsUsername) {
                    e.preventDefault();
                    let errorMsg = 'Por favor, crie uma senha mais forte:\n';
                    if (!hasLength) errorMsg += '• Mínimo 8 caracteres\n';
                    if (!hasUppercase) errorMsg += '• Uma letra maiúscula\n';
                    if (!hasLowercase) errorMsg += '• Uma letra minúscula\n';
                    if (!hasNumber) errorMsg += '• Um número\n';
                    if (hasRepeatedNumbers) errorMsg += '• Não pode ter números repetidos\n';
                    if (!hasSpecial) errorMsg += '• Um caractere especial\n';
                    if (equalsUsername) errorMsg += '• Não pode ser igual ao nome de usuário\n';
                    alert(errorMsg);
                    return false;
                }
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('As senhas não coincidem.');
                    return false;
                }
                
                const btn = document.getElementById('registerBtn');
                const spinner = document.getElementById('loadingSpinner');
                const text = document.getElementById('registerText');
                
                btn.disabled = true;
                btn.classList.add('opacity-75');
                spinner.classList.remove('hidden');
                text.textContent = 'Criando conta...';
            });
        });
    </script>
</body>
</html>


            <p class="mt-6 md:mt-8 text-center text-xs md:text-sm text-slate-600">
                Já tem uma conta? 
                <a href="login.php" class="font-semibold text-black hover:underline">Fazer login</a>
            </p>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Toggle password visibility
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                input.type = 'password';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        // SafeNode Verification Logic
        // Inicializar verificação humana SafeNode
        function initSafeNodeHumanVerification() {
            const hvJs = document.getElementById('safenode_hv_js');
            const hvSpinner = document.getElementById('hv-spinner');
            const hvCheck = document.getElementById('hv-check');
            const hvText = document.getElementById('hv-text');

            // Marcar imediatamente como verificado
            if (hvJs) {
                hvJs.value = '1';
            }

            // Após um pequeno atraso, mostrar visual de verificado
            setTimeout(() => {
                if (hvSpinner) hvSpinner.classList.add('hidden');
                if (hvCheck) hvCheck.classList.remove('hidden');
                if (hvText) hvText.textContent = 'Verificado com SafeNode';
            }, 800);
        }

        // Password Strength Checker
        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('passwordStrength');
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            const strengthIcon = document.getElementById('strengthIcon');
            const passwordInput = document.getElementById('password');
            
            if (!password) {
                strengthDiv.classList.add('hidden');
                passwordInput.classList.remove('border-red-500');
                return;
            }
            
            strengthDiv.classList.remove('hidden');
            
            // Get username for comparison
            const username = document.getElementById('username')?.value || '';
            
            // Check requirements
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
            
            // Check for repeated numbers
            const numbers = password.match(/[0-9]/g) || [];
            const hasRepeatedNumbers = numbers.length > 1 && new Set(numbers).size < numbers.length;
            const validNumber = hasNumber && !hasRepeatedNumbers;
            
            // Check if password equals username (case insensitive)
            const equalsUsername = username && password.toLowerCase() === username.toLowerCase();
            const notEqualsUsername = !equalsUsername;
            
            // Update requirement icons (red if not met)
            updateRequirement('req-length', hasLength);
            updateRequirement('req-uppercase', hasUppercase);
            updateRequirement('req-lowercase', hasLowercase);
            updateRequirement('req-number', validNumber);
            updateRequirement('req-special', hasSpecial);
            updateRequirement('req-username', notEqualsUsername);
            
            // Check if ALL requirements are met
            const allRequirementsMet = hasLength && hasUppercase && hasLowercase && validNumber && hasSpecial && notEqualsUsername;
            
            // Update password input border color
            if (allRequirementsMet) {
                passwordInput.classList.remove('border-red-500');
                passwordInput.classList.add('border-emerald-500');
            } else {
                passwordInput.classList.remove('border-emerald-500');
                passwordInput.classList.add('border-red-500');
            }
            
            // Determine strength level - só fica forte se TODOS os requisitos forem atendidos
            let strength = 'Fraca';
            let color = 'bg-red-500';
            let textColor = 'text-red-600';
            let icon = '<i data-lucide="x-circle" class="w-4 h-4 text-red-500"></i>';
            let percentage = 0;
            
            if (allRequirementsMet) {
                strength = 'Forte';
                color = 'bg-emerald-500';
                textColor = 'text-emerald-600';
                icon = '<i data-lucide="shield-check" class="w-4 h-4 text-emerald-500"></i>';
                percentage = 100;
            } else {
                // Count how many requirements are met
                let metCount = 0;
                if (hasLength) metCount++;
                if (hasUppercase) metCount++;
                if (hasLowercase) metCount++;
                if (validNumber) metCount++;
                if (hasSpecial) metCount++;
                if (notEqualsUsername) metCount++;
                
                percentage = (metCount / 6) * 60; // Max 60% if not all met
                
                if (equalsUsername) {
                    strength = 'Não pode ser igual ao usuário';
                    percentage = 0;
                } else if (hasRepeatedNumbers) {
                    strength = 'Não pode ter números repetidos';
                    percentage = Math.min(percentage, 30);
                } else {
                    strength = 'Falta requisito';
                }
            }
            
            // Update UI
            strengthBar.className = `h-full transition-all duration-300 rounded-full ${color}`;
            strengthBar.style.width = percentage + '%';
            strengthText.className = `text-xs font-bold ${textColor}`;
            strengthText.textContent = `Força: ${strength}`;
            strengthIcon.innerHTML = icon;
            
            // Reinitialize Lucide icons for new icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
        
        function updateRequirement(id, met) {
            const req = document.getElementById(id);
            const icon = req.querySelector('i');
            
            if (met) {
                req.classList.remove('text-red-600', 'text-slate-500');
                req.classList.add('text-emerald-600');
                icon.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            } else {
                req.classList.remove('text-emerald-600', 'text-slate-500');
                req.classList.add('text-red-600');
                icon.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Iniciar verificação humana
            initSafeNodeHumanVerification();
            
            // Re-validate password when username changes
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            if (usernameInput && passwordInput) {
                usernameInput.addEventListener('input', function() {
                    if (passwordInput.value) {
                        checkPasswordStrength(passwordInput.value);
                    }
                });
            }
            
            // Register button loading state
            const form = document.getElementById('registerForm');
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const username = document.getElementById('username').value;
                
                // Validate password strength
                const hasLength = password.length >= 8;
                const hasUppercase = /[A-Z]/.test(password);
                const hasLowercase = /[a-z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
                
                // Check for repeated numbers
                const numbers = password.match(/[0-9]/g) || [];
                const hasRepeatedNumbers = numbers.length > 1 && new Set(numbers).size < numbers.length;
                
                // Check if password equals username
                const equalsUsername = username && password.toLowerCase() === username.toLowerCase();
                
                if (!hasLength || !hasUppercase || !hasLowercase || !hasNumber || !hasSpecial || hasRepeatedNumbers || equalsUsername) {
                    e.preventDefault();
                    let errorMsg = 'Por favor, crie uma senha mais forte:\n';
                    if (!hasLength) errorMsg += '• Mínimo 8 caracteres\n';
                    if (!hasUppercase) errorMsg += '• Uma letra maiúscula\n';
                    if (!hasLowercase) errorMsg += '• Uma letra minúscula\n';
                    if (!hasNumber) errorMsg += '• Um número\n';
                    if (hasRepeatedNumbers) errorMsg += '• Não pode ter números repetidos\n';
                    if (!hasSpecial) errorMsg += '• Um caractere especial\n';
                    if (equalsUsername) errorMsg += '• Não pode ser igual ao nome de usuário\n';
                    alert(errorMsg);
                    return false;
                }
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('As senhas não coincidem.');
                    return false;
                }
                
                const btn = document.getElementById('registerBtn');
                const spinner = document.getElementById('loadingSpinner');
                const text = document.getElementById('registerText');
                
                btn.disabled = true;
                btn.classList.add('opacity-75');
                spinner.classList.remove('hidden');
                text.textContent = 'Criando conta...';
            });
        });
    </script>
</body>
</html>

