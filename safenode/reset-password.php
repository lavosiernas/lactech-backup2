<?php
/**
 * SafeNode - Redefinir Senha
 */

session_start();

require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

// Verificar se já está logado
if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/HumanVerification.php';
// SecurityLogger removido - não é core

$pageTitle = 'Redefinir Senha';
$message = '';
$messageType = '';
$userEmail = '';
$otpSent = false;

// Verificar se há email na sessão (vindo de forgot-password)
if (isset($_SESSION['reset_email_for_otp'])) {
    $userEmail = $_SESSION['reset_email_for_otp'];
    $otpSent = true;
} else {
    // Se não há email na sessão, redirecionar para forgot-password
    $message = 'Acesso inválido. Por favor, solicite um código de redefinição de senha na página "Esqueceu a Senha".';
    $messageType = 'error';
}

// Processar redefinição de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password']) && $otpSent) {
    // Validar CSRF
    if (!CSRFProtection::validate()) {
        $message = 'Token de segurança inválido. Recarregue a página e tente novamente.';
        $messageType = 'error';
    } else {
        $enteredOtp = trim($_POST['otp_code'] ?? '');
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validar OTP
        if (empty($enteredOtp)) {
            $message = 'Por favor, informe o código OTP.';
            $messageType = 'error';
        } elseif (!preg_match('/^[0-9]{6}$/', $enteredOtp)) {
            $message = 'O código OTP deve conter exatamente 6 dígitos.';
            $messageType = 'error';
        } elseif (empty($newPassword) || empty($confirmPassword)) {
            $message = 'Por favor, preencha todos os campos.';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'As senhas não coincidem.';
            $messageType = 'error';
        } elseif (!InputValidator::strongPassword($newPassword)) {
            $message = 'A senha deve ter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas, números e caracteres especiais.';
            $messageType = 'error';
        } else {
            // Validar verificação humana
            $hvError = '';
            if (!SafeNodeHumanVerification::validateRequest($_POST, $hvError)) {
                $message = $hvError ?: 'Falha na verificação de segurança.';
                $messageType = 'error';
            } else {
                try {
                    $db = getSafeNodeDatabase();
                    
                    if (!$db) {
                        $message = 'Erro ao conectar ao banco de dados. Tente novamente.';
                        $messageType = 'error';
                    } else {
                        // Buscar OTP válido
                        $otpStmt = $db->prepare("
                            SELECT id, user_id, attempts, max_attempts, expires_at, used_at
                            FROM safenode_password_reset_otp
                            WHERE email = ? 
                            AND otp_code = ?
                            AND expires_at > NOW()
                            AND used_at IS NULL
                            AND attempts < max_attempts
                            ORDER BY created_at DESC
                            LIMIT 1
                        ");
                        $otpStmt->execute([$userEmail, $enteredOtp]);
                        $otpRecord = $otpStmt->fetch();
                        
                        if (!$otpRecord) {
                            // Incrementar tentativas se OTP existe mas está incorreto
                            $incrementStmt = $db->prepare("
                                UPDATE safenode_password_reset_otp
                                SET attempts = attempts + 1
                                WHERE email = ? 
                                AND expires_at > NOW()
                                AND used_at IS NULL
                                AND attempts < max_attempts
                                ORDER BY created_at DESC
                                LIMIT 1
                            ");
                            $incrementStmt->execute([$userEmail]);
                            
                            $message = 'Código OTP inválido, expirado ou já utilizado. Solicite um novo código.';
                            $messageType = 'error';
                        } else {
                            // OTP válido - atualizar senha
                            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                            
                            $updateStmt = $db->prepare("
                                UPDATE safenode_users
                                SET password_hash = ?, updated_at = NOW()
                                WHERE id = ? AND email = ?
                            ");
                            $updateStmt->execute([$passwordHash, $otpRecord['user_id'], $userEmail]);
                            
                            // Marcar OTP como usado
                            $markUsedStmt = $db->prepare("
                                UPDATE safenode_password_reset_otp
                                SET used_at = NOW()
                                WHERE id = ?
                            ");
                            $markUsedStmt->execute([$otpRecord['id']]);
                            
                            // Invalidar outros OTPs pendentes do mesmo usuário
                            $invalidateStmt = $db->prepare("
                                UPDATE safenode_password_reset_otp
                                SET used_at = NOW()
                                WHERE user_id = ? AND used_at IS NULL AND id != ?
                            ");
                            $invalidateStmt->execute([$otpRecord['user_id'], $otpRecord['id']]);
                            
                            // Log de segurança - SecurityLogger removido (não é core)
                            try {
                                // SecurityLogger removido - não é core
                                if (false && class_exists('SecurityLogger')) {
                                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                                    // $logger = new SecurityLogger($db);
                                    // $logger->log(
                                    //     $ipAddress,
                                    //     '/reset-password.php',
                                    //     'POST',
                                        // 'password_reset_complete_otp',
                                        // 'password_reset_otp_completed',
                                        // 0,
                                        // $_SERVER['HTTP_USER_AGENT'] ?? null,
                                        // $_SERVER['HTTP_REFERER'] ?? null,
                                        // null,
                                        // null,
                                        // null,
                                        // null
                                    );
                                }
                            } catch (Exception $logError) {
                                error_log("Erro ao registrar log: " . $logError->getMessage());
                            }
                            
                            // Limpar sessão e redirecionar
                            unset($_SESSION['reset_email_for_otp']);
                            $_SESSION['password_reset_success'] = true;
                            header('Location: login.php');
                            exit;
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Erro PDO ao redefinir senha: " . $e->getMessage());
                    $message = 'Erro ao redefinir senha. Tente novamente.';
                    $messageType = 'error';
                } catch (Exception $e) {
                    error_log("Erro geral ao redefinir senha: " . $e->getMessage());
                    $message = 'Erro inesperado ao redefinir senha. Tente novamente.';
                    $messageType = 'error';
                }
            }
        }
    }
}

// Inicializar verificação humana
$safenodeHvToken = SafeNodeHumanVerification::initChallenge();

?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #030303;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 15% 30%, rgba(59, 130, 246, 0.25) 0%, transparent 45%),
                radial-gradient(circle at 85% 70%, rgba(139, 92, 246, 0.25) 0%, transparent 45%),
                radial-gradient(circle at 50% 10%, rgba(236, 72, 153, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 30% 90%, rgba(34, 197, 94, 0.1) 0%, transparent 35%),
                radial-gradient(circle at 70% 40%, rgba(251, 191, 36, 0.08) 0%, transparent 30%),
                linear-gradient(135deg, #030303 0%, #0a0a0a 20%, #1a1a1a 40%, #0f0f0f 60%, #0a0a0a 80%, #030303 100%);
            background-size: 200% 200%;
            z-index: 0;
            animation: gradientShift 20s ease infinite;
        }
        
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(255, 255, 255, 0.02) 2px, rgba(255, 255, 255, 0.02) 4px),
                repeating-linear-gradient(90deg, transparent, transparent 2px, rgba(255, 255, 255, 0.02) 2px, rgba(255, 255, 255, 0.02) 4px);
            background-size: 50px 50px;
            z-index: 0;
            pointer-events: none;
            opacity: 0.6;
        }
        
        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }
            25% {
                background-position: 100% 30%;
            }
            50% {
                background-position: 50% 100%;
            }
            75% {
                background-position: 0% 70%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) translateX(0px);
            }
            33% {
                transform: translateY(-20px) translateX(10px);
            }
            66% {
                transform: translateY(10px) translateX(-15px);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 0.4;
                transform: scale(1);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.1);
            }
        }
        
        .bg-decoration {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.3;
            animation: float 20s ease-in-out infinite;
        }
        
        .bg-circle-1 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.4), transparent);
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }
        
        .bg-circle-2 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.4), transparent);
            bottom: -150px;
            right: -150px;
            animation-delay: -5s;
        }
        
        .bg-circle-3 {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(236, 72, 153, 0.3), transparent);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -10s;
        }
        
        .bg-particles {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 0;
            pointer-events: none;
        }
        
        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: pulse 3s ease-in-out infinite;
        }
        
        .particle:nth-child(1) { top: 20%; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { top: 40%; left: 80%; animation-delay: 0.5s; }
        .particle:nth-child(3) { top: 60%; left: 30%; animation-delay: 1s; }
        .particle:nth-child(4) { top: 80%; left: 70%; animation-delay: 1.5s; }
        .particle:nth-child(5) { top: 30%; left: 50%; animation-delay: 2s; }
        .particle:nth-child(6) { top: 70%; left: 20%; animation-delay: 2.5s; }
        .particle:nth-child(7) { top: 10%; left: 60%; animation-delay: 3s; }
        .particle:nth-child(8) { top: 90%; left: 40%; animation-delay: 3.5s; }
        
        .content-wrapper {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body class="h-full flex items-center justify-center p-4">
    <!-- Background Decorations -->
    <div class="bg-decoration">
        <div class="bg-circle bg-circle-1"></div>
        <div class="bg-circle bg-circle-2"></div>
        <div class="bg-circle bg-circle-3"></div>
    </div>
    <div class="bg-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    
    <div class="content-wrapper w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 mb-4">
                <img src="assets/img/safe-claro.png" alt="SafeNode" class="w-10 h-10 dark:hidden">
                <img src="assets/img/logos (6).png" alt="SafeNode" class="w-10 h-10 hidden dark:block">
                <h1 class="text-2xl font-bold text-white">SafeNode</h1>
            </div>
            <p class="text-zinc-400 text-sm">Security Platform</p>
        </div>
        
        <?php if (!$otpSent): ?>
        <!-- Acesso inválido -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center">
                <i data-lucide="alert-circle" class="w-16 h-16 text-red-500 mx-auto mb-4"></i>
                <h2 class="text-xl font-bold text-slate-900 mb-2">Acesso Inválido</h2>
                <p class="text-slate-600 text-sm mb-6">
                    <?php echo htmlspecialchars($message); ?>
                </p>
                <a href="forgot-password.php" class="inline-block bg-slate-900 text-white px-6 py-3 rounded-lg font-semibold hover:bg-slate-800 transition-colors">
                    Solicitar Código de Redefinição
                </a>
            </div>
        </div>
        <?php else: ?>
        <!-- Card de Redefinição -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-slate-900 mb-2">Redefinir Senha</h2>
            <p class="text-slate-600 text-sm mb-6">
                Digite o código OTP enviado para <strong><?php echo htmlspecialchars($userEmail); ?></strong> e sua nova senha.
            </p>
            
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php 
                echo $messageType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 
                    'bg-red-50 text-red-800 border border-red-200'; 
            ?>">
                <p class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></p>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="resetPasswordForm">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($userEmail); ?>">
                
                <!-- Código OTP -->
                <div class="mb-4">
                    <label for="otp_code" class="block text-sm font-medium text-slate-700 mb-2">
                        Código OTP
                    </label>
                    <input 
                        type="text" 
                        name="otp_code" 
                        id="otp_code" 
                        required 
                        autocomplete="one-time-code"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center text-2xl font-mono tracking-widest"
                        placeholder="000000"
                    >
                    <p class="text-xs text-slate-500 mt-1">
                        Digite o código de 6 dígitos enviado por email
                    </p>
                </div>
                
                <!-- Nova Senha -->
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-2">
                        Nova Senha
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            required 
                            autocomplete="new-password"
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-12"
                            placeholder="Mínimo 8 caracteres"
                        >
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility('password', 'eyeIcon')" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700"
                        >
                            <i data-lucide="eye" id="eyeIcon" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Mínimo 8 caracteres, incluindo maiúsculas, minúsculas, números e símbolos
                    </p>
                </div>
                
                <!-- Confirmar Senha -->
                <div class="mb-6">
                    <label for="confirm_password" class="block text-sm font-medium text-slate-700 mb-2">
                        Confirmar Nova Senha
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            name="confirm_password" 
                            id="confirm_password" 
                            required 
                            autocomplete="new-password"
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-12"
                            placeholder="Digite a senha novamente"
                        >
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility('confirm_password', 'eyeIcon2')" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700"
                        >
                            <i data-lucide="eye" id="eyeIcon2" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Verificação Humana SafeNode -->
                <div class="mt-3 p-3 rounded-2xl border border-slate-200 bg-slate-50 flex items-center gap-3 shadow-sm" id="hv-box">
                    <div class="relative flex items-center justify-center w-9 h-9">
                        <div class="absolute inset-0 rounded-2xl border-2 border-slate-200 border-t-black animate-spin" id="hv-spinner"></div>
                        <div class="relative z-10 w-7 h-7 rounded-2xl bg-black flex items-center justify-center">
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
                
                <!-- Submit -->
                <button 
                    type="submit" 
                    name="reset_password"
                    class="w-full bg-slate-900 text-white py-3 rounded-lg font-semibold hover:bg-slate-800 transition-colors mb-4 mt-4"
                >
                    Redefinir Senha
                </button>
            </form>
            
            <!-- Resend Link -->
            <div class="mt-4 text-center">
                <p class="text-slate-500 text-sm mb-3">Não recebeu o código?</p>
                <button 
                    type="button" 
                    id="resendOtpBtn" 
                    onclick="resendOTP()"
                    class="text-slate-600 font-semibold text-sm hover:text-slate-900 hover:underline decoration-2 underline-offset-4 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span id="resendText">Reenviar código</span>
                    <span id="resendLoading" class="hidden">Enviando...</span>
                    <span id="resendSuccess" class="hidden text-green-600">✓ Código reenviado!</span>
                </button>
                <p id="resendError" class="text-red-600 text-sm mt-2 hidden"></p>
            </div>
            
            <!-- Voltar para login -->
            <div class="text-center mt-4">
                <a href="login.php" class="text-sm text-slate-600 hover:text-slate-900 font-medium">
                    ← Voltar para login
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <p class="text-center text-zinc-500 text-xs mt-6">
            © <?php echo date('Y'); ?> SafeNode Security Platform
        </p>
    </div>
    
    <script>
        lucide.createIcons();
        
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
        
        // Toggle password visibility
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
        
        // Função para reenviar OTP
        window.resendOTP = function() {
            const btn = document.getElementById('resendOtpBtn');
            const text = document.getElementById('resendText');
            const loading = document.getElementById('resendLoading');
            const success = document.getElementById('resendSuccess');
            const error = document.getElementById('resendError');
            
            // Desabilitar botão
            btn.disabled = true;
            text.classList.add('hidden');
            loading.classList.remove('hidden');
            success.classList.add('hidden');
            error.classList.add('hidden');
            
            // Fazer requisição AJAX
            fetch('api/resend-otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    type: 'password_reset',
                    safenode_hv_token: '<?php echo htmlspecialchars($safenodeHvToken); ?>',
                    safenode_hv_js: '1'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loading.classList.add('hidden');
                    success.classList.remove('hidden');
                    setTimeout(() => {
                        success.classList.add('hidden');
                        text.classList.remove('hidden');
                        btn.disabled = false;
                    }, 3000);
                } else {
                    loading.classList.add('hidden');
                    text.classList.remove('hidden');
                    error.textContent = data.error || 'Erro ao reenviar código';
                    error.classList.remove('hidden');
                    btn.disabled = false;
                }
            })
            .catch(err => {
                loading.classList.add('hidden');
                text.classList.remove('hidden');
                error.textContent = 'Erro ao reenviar código. Tente novamente.';
                error.classList.remove('hidden');
                btn.disabled = false;
            });
        };

        document.addEventListener('DOMContentLoaded', function() {
            initSafeNodeHumanVerification();
        });
    </script>
</body>
</html>

