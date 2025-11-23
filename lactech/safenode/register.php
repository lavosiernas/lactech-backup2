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
            $error = 'Senha deve ter no mínimo 8 caracteres, com letras e números';
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
                        // Desafio usado com sucesso - resetar para próxima página
                        SafeNodeHumanVerification::reset();
                        
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
    } // Fim do if (!$error)
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="md:h-full bg-white">
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
<body class="min-h-screen md:h-full flex flex-col md:flex-row md:overflow-hidden bg-white">
    
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
    <div class="w-full md:w-1/2 lg:w-[45%] flex flex-col md:justify-center overflow-y-auto">
        <div class="w-full max-w-md mx-auto px-6 py-8 md:py-12 md:px-10 lg:px-12">
            
            <!-- Header (Mobile Logo) -->
            <div class="md:hidden mb-6 flex items-center gap-2">
                <img src="assets/img/logos (6).png" alt="SafeNode" class="w-8 h-8">
                <span class="text-xl font-bold text-slate-900">SafeNode</span>
            </div>

            <div class="mb-6 md:mb-10">
                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2">Criar sua conta</h1>
                <p class="text-sm md:text-base text-slate-500">Preencha os dados para começar.</p>
            </div>

            <?php if(!empty($error)): ?>
                <div class="mb-4 md:mb-6 p-3 md:p-4 rounded-lg bg-red-50 border border-red-100 flex items-start gap-2 md:gap-3 animate-fade-in">
                    <i data-lucide="alert-circle" class="w-4 h-4 md:w-5 md:h-5 text-red-600 shrink-0 mt-0.5"></i>
                    <p class="text-xs md:text-sm text-red-600 font-medium"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['google_error'])): ?>
                <div class="mb-4 md:mb-6 p-3 md:p-4 rounded-lg bg-red-50 border border-red-100 flex items-start gap-2 md:gap-3 animate-fade-in">
                    <i data-lucide="alert-circle" class="w-4 h-4 md:w-5 md:h-5 text-red-600 shrink-0 mt-0.5"></i>
                    <p class="text-xs md:text-sm text-red-600 font-medium"><?php echo htmlspecialchars($_SESSION['google_error']); unset($_SESSION['google_error']); ?></p>
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
                            placeholder="Mínimo 6 caracteres">
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
                    <p class="text-xs text-slate-500">Mínimo de 6 caracteres</p>
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

        document.addEventListener('DOMContentLoaded', function() {
            // Iniciar verificação humana
            initSafeNodeHumanVerification();
            
            // Register button loading state
            const form = document.getElementById('registerForm');
            form.addEventListener('submit', function() {
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

