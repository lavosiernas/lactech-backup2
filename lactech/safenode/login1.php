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
require_once __DIR__ . '/includes/HumanVerification.php';
require_once __DIR__ . '/includes/Settings.php';
require_once __DIR__ . '/includes/SecurityLogger.php';

// Inicializar desafio de verificação humana SafeNode
$safenodeHvToken = SafeNodeHumanVerification::initChallenge();

// Processar login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

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
    elseif (empty($username) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $pdo = getSafeNodeDatabase();
            
            if (!$pdo) {
                $error = 'Erro ao conectar ao banco de dados. Tente novamente.';
            } else {
                // Verificar se já excedeu o limite de tentativas no intervalo
                if ($loginMaxAttempts > 0 && $loginWindow > 0) {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM safenode_security_logs 
                        WHERE ip_address = ? 
                          AND threat_type = 'login_failed' 
                          AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                    ");
                    $stmt->execute([$clientIp, $loginWindow]);
                    $failedAttempts = (int) $stmt->fetchColumn();

                    if ($failedAttempts >= $loginMaxAttempts) {
                        // Registrar evento de rate limit
                        $logger = new SecurityLogger($pdo);
                        $logger->log(
                            $clientIp,
                            '/safenode/login.php',
                            'POST',
                            'blocked',
                            'rate_limit_login',
                            80,
                            $_SERVER['HTTP_USER_AGENT'] ?? null,
                            $_SERVER['HTTP_REFERER'] ?? null,
                            null,
                            null,
                            $clientCountry
                        );

                        $error = 'Muitas tentativas de login. Tente novamente em alguns minutos.';
                        // Não prosseguir com validação de usuário/senha
                        throw new Exception('Login rate limited');
                    }
                }

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
                        // Registrar tentativa de login falha no logger
                        $logger = new SecurityLogger($pdo);
                        $logger->log(
                            $clientIp,
                            '/safenode/login.php',
                            'POST',
                            'blocked',
                            'login_failed',
                            40,
                            $_SERVER['HTTP_USER_AGENT'] ?? null,
                            $_SERVER['HTTP_REFERER'] ?? null,
                            null,
                            null,
                            $clientCountry
                        );

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

                        // Desafio usado com sucesso - resetar para próxima página
                        SafeNodeHumanVerification::reset();
                        
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
            border-color: #4F46E5;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #4F46E5;
        }
    </style>
</head>
<body class="h-full flex flex-col md:flex-row overflow-hidden bg-white">
    
    <!-- Left Side: Image & Branding (Desktop Only) -->
    <div class="hidden md:flex md:w-1/2 lg:w-[55%] relative bg-slate-900 text-white overflow-hidden">
        <!-- Background Image -->
        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=2576&auto=format&fit=crop" 
             alt="Office Workspace" 
             class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/40 to-slate-900/20"></div>
        
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
                <img src="assets/img/logos (6).png" alt="SafeNode" class="w-8 h-8">
                <span class="text-xl font-bold text-slate-900">SafeNode</span>
            </div>

            <div class="mb-10">
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Bem-vindo de volta</h1>
                <p class="text-slate-500">Acesse seu painel e gerencie seus sistemas.</p>
            </div>

            <?php if(!empty($error)): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-100 flex items-start gap-3 animate-fade-in">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 shrink-0 mt-0.5"></i>
                    <p class="text-sm text-red-600 font-medium"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if(isset($logoutMessage)): ?>
                <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-100 flex items-start gap-3 animate-fade-in">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5"></i>
                    <p class="text-sm text-emerald-600 font-medium"><?php echo htmlspecialchars($logoutMessage); ?></p>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-6" id="loginForm">
                <input type="hidden" name="login" value="1">
                
                <!-- Email/Username -->
                <div class="space-y-1.5">
                    <label for="username" class="block text-sm font-medium text-slate-700">Email ou Usuário</label>
                    <input type="text" name="username" id="username" required 
                        class="block w-full px-4 py-3 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all text-sm"
                        placeholder="exemplo@email.com"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <!-- Password -->
                <div class="space-y-1.5">
                    <label for="password" class="block text-sm font-medium text-slate-700">Senha</label>
                    <input type="password" name="password" id="password" required 
                        class="block w-full px-4 py-3 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all text-sm"
                        placeholder="••••••••">
                </div>

                <div class="flex items-center justify-between">
                    <!-- Remember Me Toggle -->
                    <div class="flex items-center">
                        <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                            <input type="checkbox" name="remember" id="remember" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300"/>
                            <label for="remember" class="toggle-label block overflow-hidden h-5 rounded-full bg-slate-300 cursor-pointer"></label>
                        </div>
                        <label for="remember" class="text-sm text-slate-600 cursor-pointer select-none">Lembrar-me</label>
                    </div>
                    
                    <a href="#" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Esqueceu a senha?</a>
                </div>

                <!-- Verificação Humana SafeNode -->
                <div class="mt-3 p-3 rounded-2xl border border-slate-200 bg-slate-50 flex items-center gap-3 shadow-sm" id="hv-box">
                    <div class="relative flex items-center justify-center w-9 h-9">
                        <div class="absolute inset-0 rounded-2xl border-2 border-slate-200 border-t-black animate-spin" id="hv-spinner"></div>
                        <div class="relative z-10 w-7 h-7 rounded-2xl bg-black flex items-center justify-center">
                            <img src="assets/img/logos (6).png" alt="SafeNode" class="w-4 h-4 object-contain">
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
                <button type="submit" class="w-full flex justify-center items-center py-3 px-4 rounded-lg shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600 transition-all duration-200" id="loginBtn">
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

                <!-- Dummy Google Button (Visual Only) -->
                <button type="button" class="w-full flex justify-center items-center gap-3 px-4 py-3 border border-slate-200 rounded-lg shadow-sm bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24">
                        <path d="M12.0003 20.45c-4.6667 0-8.45004-3.7833-8.45004-8.45 0-4.6666 3.78334-8.44997 8.45004-8.44997 2.0814 0 4.0222.74315 5.564 2.11963l-2.0566 2.48834c-.6518-.7989-1.9607-1.5547-3.5074-1.5547-2.8967 0-5.2533 2.35667-5.2533 5.2534 0 2.8966 2.3566 5.2533 5.2533 5.2533 1.8093 0 3.2493-.8817 3.9807-2.24H12.0003v-3.0533h7.3234c.0853.5266.1333 1.06.1333 1.6066 0 4.63-3.0734 8.45-7.4567 8.45z" fill="currentColor" />
                    </svg>
                    <span>Continuar com Google</span>
                </button>
            </form>

            <p class="mt-8 text-center text-sm text-slate-600">
                Não tem uma conta? 
                <a href="register.php" class="font-semibold text-indigo-600 hover:text-indigo-500">Cadastre-se</a>
            </p>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Inicializar verificação humana SafeNode
        function initSafeNodeHumanVerification() {
            const hvJs = document.getElementById('safenode_hv_js');
            const hvSpinner = document.getElementById('hv-spinner');
            const hvCheck = document.getElementById('hv-check');
            const hvText = document.getElementById('hv-text');

            // Após um pequeno atraso, marcar como verificado (simula interação real)
            setTimeout(() => {
                if (hvJs) {
                    hvJs.value = '1';
                    if (hvSpinner) hvSpinner.classList.add('hidden');
                    if (hvCheck) hvCheck.classList.remove('hidden');
                    if (hvText) hvText.textContent = 'Verificado com SafeNode';
                }
            }, 1200);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Iniciar verificação humana
            initSafeNodeHumanVerification();

            const hvData = {
                ua: navigator.userAgent,
                screen: `${window.screen.width}x${window.screen.height}`,
                tz: Intl.DateTimeFormat().resolvedOptions().timeZone,
                ts: Date.now()
            };
            document.getElementById('safenode_hv_js').value = btoa(JSON.stringify(hvData));
            
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
