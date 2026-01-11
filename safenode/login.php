<?php
/**
 * SafeNode - Página de Login
 */

session_start();

// SEGURANÇA: Carregar helpers e aplicar headers de segurança
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

// Processar logout
if (isset($_GET['logout'])) {
    session_destroy();
    $logoutMessage = 'Você foi desconectado com sucesso.';
}

// Verificar se senha foi redefinida com sucesso
$passwordResetMessage = '';
if (isset($_SESSION['password_reset_success']) && $_SESSION['password_reset_success'] === true) {
    $passwordResetMessage = 'Senha redefinida com sucesso! Faça login com sua nova senha.';
    unset($_SESSION['password_reset_success']);
}

// Verificar se já está logado
if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/HumanVerification.php';
require_once __DIR__ . '/includes/Settings.php';
// SecurityLogger removido - não é core
// 2FA removido - require_once __DIR__ . '/includes/TwoFactorAuth.php';

// Inicializar desafio de verificação humana SafeNode
$safenodeHvToken = SafeNodeHumanVerification::initChallenge();

// Processar login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // SEGURANÇA: Validar CSRF token
    if (!CSRFProtection::validate()) {
        $error = 'Token de segurança inválido. Recarregue a página e tente novamente.';
    } else {
        $email = XSSProtection::sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
    }
    
    if (!$error) {
        $email = trim($email);
        $password = $password;

    // Configurações de rate limit de login
    $loginMaxAttempts = (int) SafeNodeSettings::get('login_max_attempts', 5);
    $loginWindow      = (int) SafeNodeSettings::get('login_window', 300); // segundos
$clientIp         = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$clientCountry    = (function () {
    $keys = ['HTTP_CF_IPCOUNTRY', 'HTTP_X_COUNTRY_CODE', 'HTTP_GEOIP_COUNTRY_CODE', 'GEOIP_COUNTRY_CODE'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $code = strtoupper(substr(trim($_SERVER[$key]), 0, 2));
            if (preg_match('/^[A-Z]{2}$/', $code)) {
                return $code;
            }
        }
    }
    return null;
})();

    // Validar verificação humana SafeNode antes de qualquer coisa
    $hvError = '';
    if (!SafeNodeHumanVerification::validateRequest($_POST, $hvError)) {
        $error = $hvError ?: 'Falha na verificação de segurança.';
    }
    // Validação básica de campos
    elseif (empty($email) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um email válido.';
    } else {
        try {
            $pdo = getSafeNodeDatabase();
            
            if (!$pdo) {
                $error = 'Erro ao conectar ao banco de dados. Tente novamente.';
            } else {
                // Verificar se já excedeu o limite de tentativas no intervalo
                // Usar tabela de verificação humana para contar falhas
                if ($loginMaxAttempts > 0 && $loginWindow > 0) {
                    try {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) 
                            FROM safenode_human_verification_logs 
                        WHERE ip_address = ? 
                              AND event_type = 'bot_blocked' 
                          AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                    ");
                    $stmt->execute([$clientIp, $loginWindow]);
                    $failedAttempts = (int) $stmt->fetchColumn();
                    } catch (PDOException $e) {
                        // Se tabela não existir, ignorar rate limit
                        error_log("SafeNode Login Rate Limit Check Error: " . $e->getMessage());
                        $failedAttempts = 0;
                    }

                    if ($failedAttempts >= $loginMaxAttempts) {
                        // Registrar evento de rate limit (log simples)
                        error_log("SafeNode Login Rate Limit: IP $clientIp excedeu $loginMaxAttempts tentativas em $loginWindow segundos");

                        $error = 'Muitas tentativas de login. Tente novamente em alguns minutos.';
                        // Não prosseguir com validação de usuário/senha
                        throw new Exception('Login rate limited');
                    }
                }

                // Buscar usuário apenas por email
                $stmt = $pdo->prepare("SELECT id, username, email, password_hash, full_name, role, is_active, email_verified FROM safenode_users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    $error = 'Email não encontrado. Verifique seu email e tente novamente.';
                } else {
                    // Limpar possíveis espaços no hash
                    $passwordHash = trim($user['password_hash']);
                    
                    // Debug detalhado (apenas em desenvolvimento)
                    if (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL') {
                        error_log("SafeNode Login Debug:");
                        error_log("  Email: $email");
                        error_log("  Hash length: " . strlen($passwordHash));
                        error_log("  Hash preview: " . substr($passwordHash, 0, 30) . "...");
                        error_log("  Password length: " . strlen($password));
                    }
                    
                    if (!password_verify($password, $passwordHash)) {
                        // Log do erro para debug (apenas em desenvolvimento)
                        if (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL') {
                            error_log("SafeNode Login: Senha incorreta para email: $email");
                            error_log("Hash no banco: " . substr($passwordHash, 0, 30) . "...");
                            error_log("Tentando verificar novamente com hash limpo...");
                        }
                        // Registrar tentativa de login falha (log simples)
                        error_log("SafeNode Login Failed: Email $email, IP $clientIp");

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
                        
                        // 2FA removido - criar sessão normalmente
                        $_SESSION['safenode_logged_in'] = true;
                        $_SESSION['safenode_username'] = $user['username'];
                        $_SESSION['safenode_user_id'] = $user['id'];
                        $_SESSION['safenode_user_email'] = $user['email'];
                        $_SESSION['safenode_user_full_name'] = $user['full_name'];
                        $_SESSION['safenode_user_role'] = $user['role'];
                        $_SESSION['show_update_modal'] = true; // Mostrar modal de atualização

                        // Desafio usado com sucesso - resetar para próxima página
                        SafeNodeHumanVerification::reset();
                        
                        // Redirecionar para dashboard
                        header('Location: dashboard.php');
                        exit;
                    }
                }
            }
        } catch (Exception $e) {
            if ($e instanceof PDOException) {
                error_log("SafeNode Login DB Error: " . $e->getMessage());
                if (!$error) {
                    $error = 'Erro ao processar login. Tente novamente.';
                }
            } else {
                // Exceções de rate limit já definiram a mensagem em $error
                if (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL') {
                    error_log("SafeNode Login Error: " . $e->getMessage());
                }
            }
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
    <title>Entrar &mdash; SafeNode</title>
    
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

        /* Toggle Switch */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #000000;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #000000;
        }
    </style>
</head>
<body class="h-full flex flex-col md:flex-row overflow-hidden bg-white">
    
    <!-- Left Side: Image & Branding (Desktop Only) -->
    <div class="hidden md:flex md:w-1/2 lg:w-[55%] relative bg-black text-white overflow-hidden">
        <!-- Background Image -->
        <img src="https://i.postimg.cc/6pqLFX9H/emailotp-(10).jpg" 
             alt="Office Workspace" 
             class="absolute inset-0 w-full h-full object-cover opacity-50">
        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/20"></div>
        
        <!-- Content Overlay -->
        <div class="relative z-10 flex flex-col justify-between w-full p-12 lg:p-16">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <div class="bg-white/10 p-2 rounded-lg backdrop-blur-md">
                    <img src="assets/img/safe-claro.png" alt="SafeNode" class="w-6 h-6 dark:hidden">
                    <img src="assets/img/logos (6).png" alt="SafeNode" class="w-6 h-6 brightness-0 invert hidden dark:block">
                </div>
                <span class="text-xl font-bold tracking-tight">SafeNode</span>
            </div>

            <!-- Quote -->
            <div class="max-w-md">
                <blockquote class="text-2xl font-medium leading-snug mb-6">
                    "A segurança não é apenas uma barreira, é a fundação que permite que sua equipe inove sem medo."
                </blockquote>
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold">JS</div>
                    <div>
                        <div class="font-semibold">João Silva</div>
                        <div class="text-sm text-slate-400">Diretor de Segurança da Informação</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side: Login Form -->
    <div class="w-full md:w-1/2 lg:w-[45%] flex flex-col justify-center overflow-y-auto">
        <div class="w-full max-w-md mx-auto px-6 py-12 md:px-10 lg:px-12">
            
            <!-- Header (Mobile Logo) -->
            <div class="md:hidden mb-8 flex items-center gap-2">
                <img src="assets/img/logos (5).png" alt="SafeNode" class="w-8 h-8">
                <span class="text-xl font-bold text-slate-900">SafeNode</span>
            </div>

            <div class="mb-10">
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Bem-vindo de volta</h1>
                <p class="text-slate-500">Acesse seu painel e gerencie seus sistemas.</p>
            </div>

            <?php if(!empty($error)): ?>
                <div class="mb-6 p-5 rounded-xl bg-red-500 border-2 border-red-600 shadow-lg shadow-red-500/20 flex items-start gap-4 animate-fade-in">
                    <div class="flex-shrink-0 w-7 h-7 rounded-full bg-red-600 flex items-center justify-center">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-white"></i>
                    </div>
                    <p class="text-base text-white font-bold leading-relaxed"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['google_error'])): ?>
                <div class="mb-6 p-5 rounded-xl bg-red-500 border-2 border-red-600 shadow-lg shadow-red-500/20 flex items-start gap-4 animate-fade-in">
                    <div class="flex-shrink-0 w-7 h-7 rounded-full bg-red-600 flex items-center justify-center">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-white"></i>
                    </div>
                    <p class="text-base text-white font-bold leading-relaxed"><?php echo htmlspecialchars($_SESSION['google_error']); unset($_SESSION['google_error']); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if(isset($logoutMessage)): ?>
                <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-100 flex items-start gap-3 animate-fade-in">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5"></i>
                    <p class="text-sm text-emerald-600 font-medium"><?php echo htmlspecialchars($logoutMessage); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if($passwordResetMessage): ?>
                <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-100 flex items-start gap-3 animate-fade-in">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5"></i>
                    <p class="text-sm text-emerald-600 font-medium"><?php echo htmlspecialchars($passwordResetMessage); ?></p>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-6" id="loginForm">
                <input type="hidden" name="login" value="1">
                <?php echo csrf_field(); ?>
                
                <!-- Email -->
                <div class="space-y-1.5">
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" id="email" required 
                        class="block w-full px-4 py-3 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
                        placeholder="exemplo@email.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <!-- Password -->
                <div class="space-y-1.5">
                    <label for="password" class="block text-sm font-medium text-slate-700">Senha</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required 
                            class="block w-full px-4 py-3 pr-12 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
                            placeholder="••••••••">
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility('password', 'eyeIcon')" 
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                        >
                            <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <!-- Remember Me Toggle -->
                    <div class="flex items-center">
                        <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                            <input type="checkbox" name="remember" id="remember" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300"/>
                            <label for="remember" class="toggle-label block overflow-hidden h-5 rounded-full bg-slate-300 cursor-pointer"></label>
                        </div>
                        <label for="remember" class="text-sm text-slate-600 cursor-pointer">Lembrar-me</label>
                    </div>
                    
                    <!-- Esqueceu Senha -->
                    <div class="flex items-center justify-between">
                        <a href="forgot-password.php" class="text-sm font-semibold text-black hover:underline transition-colors">
                            Esqueceu a senha?
                        </a>
                    </div>
                    
                    <!-- ESC local (Dev Only) -->
                    <div class="flex items-center mt-2">
                        <a href="dev-reset-password.php" class="text-[10px] font-medium text-slate-400 hover:text-black transition-colors uppercase tracking-wider">
                            ESC local
                        </a>
                    </div>
                </div>


                <!-- Verificação Humana SafeNode -->
                <div class="mt-3 p-3 rounded-2xl border border-slate-200 bg-slate-50 flex items-center gap-3 shadow-sm" id="hv-box">
                    <div class="relative flex items-center justify-center w-9 h-9">
                        <div class="absolute inset-0 rounded-2xl border-2 border-slate-200 border-t-black animate-spin" id="hv-spinner"></div>
                        <div class="relative z-10 w-7 h-7 rounded-2xl bg-black dark:bg-black flex items-center justify-center">
                            <img src="assets/img/safe-claro.png" alt="SafeNode" class="w-4 h-4 object-contain dark:hidden">
                            <img src="assets/img/logos (6).png" alt="SafeNode" class="w-4 h-4 object-contain hidden dark:block">
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-semibold text-slate-900 flex items-center gap-1">
                            SafeNode <span class="text-[10px] font-normal text-slate-500">verificação humana</span>
                        </p>
                        <p class="text-[11px] text-slate-500" id="hv-text">Validando interação do navegador…</p>
                    </div>
                    <svg id="hv-check" class="w-4 h-4 text-emerald-500 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <input type="hidden" name="safenode_hv_token" value="<?php echo htmlspecialchars($safenodeHvToken); ?>">
                <input type="hidden" name="safenode_hv_js" id="safenode_hv_js" value="">

                <!-- Submit Button -->
                <button type="submit" class="w-full flex justify-center items-center py-3 px-4 rounded-lg shadow-sm text-sm font-semibold text-white bg-black hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 transition-all duration-200" id="loginBtn">
                    <span id="loadingSpinner" class="loading-spinner mr-2 hidden"></span>
                    <span id="loginText">Entrar</span>
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
                <a href="google-auth.php?action=login" class="w-full flex justify-center items-center gap-3 px-4 py-3 border border-slate-200 rounded-lg shadow-sm bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors no-underline">
                    <svg class="h-5 w-5" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span>Continuar com Google</span>
                </a>
            </form>

            <p class="mt-8 text-center text-sm text-slate-600">
                Não tem uma conta? 
                <a href="register.php" class="font-semibold text-black hover:underline">Cadastre-se</a>
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
            
            // Login button loading state
            const form = document.getElementById('loginForm');
            form.addEventListener('submit', function() {
                const btn = document.getElementById('loginBtn');
                const spinner = document.getElementById('loadingSpinner');
                const text = document.getElementById('loginText');
                
                btn.disabled = true;
                btn.classList.add('opacity-75');
                spinner.classList.remove('hidden');
                text.textContent = 'Autenticando...';
            });
        });
    </script>
</body>
</html>
