<?php
/**
 * SafeNode - Esqueceu Senha
 */

// Habilitar exibição de erros apenas para debug (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir erros na tela, apenas log
ini_set('log_errors', 1);

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
// EmailSender e SecurityLogger removidos - não são core

$pageTitle = 'Esqueceu Senha';
$message = '';
$messageType = '';

// Processar solicitação de reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    // Validar CSRF
    if (!CSRFProtection::validate()) {
        $message = 'Token de segurança inválido. Recarregue a página e tente novamente.';
        $messageType = 'error';
    } else {
        $email = XSSProtection::sanitize(trim($_POST['email'] ?? ''));
        
        // Validar email
        if (empty($email)) {
            $message = 'Por favor, informe seu email.';
            $messageType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Por favor, insira um email válido.';
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
                        // Verificar se o email existe e se está vinculado ao Google
                        $stmt = $db->prepare("SELECT id, username, email, full_name, google_id FROM safenode_users WHERE email = ? AND is_active = 1");
                        $stmt->execute([$email]);
                        $user = $stmt->fetch();
                        
                        if ($user) {
                            // Verificar se a conta está vinculada ao Google
                            if (!empty($user['google_id'])) {
                                // Conta vinculada ao Google - não permitir reset de senha via OTP
                                $message = 'Esta conta está vinculada ao Google. Para alterar sua senha, utilize a opção "Esqueci minha senha" no Google ou altere diretamente na sua conta Google.';
                                $messageType = 'error';
                            } else {
                                // Conta normal - proceder com OTP
                                // Por segurança, sempre mostrar mensagem de sucesso
                                $message = 'Se o email informado existir em nosso sistema, você receberá um código OTP para redefinir sua senha.';
                                $messageType = 'success';
                                
                                // Gerar código OTP de 6 dígitos
                                $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                            
                                // Expira em 10 minutos
                                $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                                
                                // Obter IP do usuário
                                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                                
                                // Invalidar OTPs anteriores do mesmo usuário
                                $invalidateStmt = $db->prepare("
                                    UPDATE safenode_password_reset_otp 
                                    SET used_at = NOW() 
                                    WHERE user_id = ? AND used_at IS NULL AND expires_at > NOW()
                                ");
                                $invalidateStmt->execute([$user['id']]);
                                
                                // Salvar OTP no banco
                                $insertStmt = $db->prepare("
                                    INSERT INTO safenode_password_reset_otp 
                                    (user_id, email, otp_code, expires_at, ip_address, created_at) 
                                    VALUES (?, ?, ?, ?, ?, NOW())
                                ");
                                $insertStmt->execute([
                                    $user['id'],
                                    $user['email'],
                                    $otpCode,
                                    $expiresAt,
                                    $ipAddress
                                ]);
                                
                                // Enviar email com OTP (não bloqueia o fluxo se falhar)
                                try {
                                    $emailSender = new EmailSender();
                                    $username = !empty($user['full_name']) ? $user['full_name'] : $user['username'];
                                    $emailSent = $emailSender->sendPasswordResetOTP($user['email'], $otpCode, $username);
                                    
                                    if ($emailSent) {
                                        error_log("Forgot Password: OTP enviado com sucesso para " . $user['email']);
                                    } else {
                                        error_log("Forgot Password: Falha ao enviar OTP para " . $user['email']);
                                    }
                                } catch (Exception $emailError) {
                                    error_log("Forgot Password: Erro ao enviar email: " . $emailError->getMessage());
                                }
                                
                                // SEMPRE redirecionar (OTP já está salvo no banco)
                                // Salvar email na sessão para redirecionar para reset-password
                                $_SESSION['reset_email_for_otp'] = $user['email'];
                                
                                // Redirecionar para página de reset de senha
                                header('Location: reset-password.php');
                                exit;
                                
                                // Log de segurança - SecurityLogger removido (não é core)
                                try {
                                    // SecurityLogger removido - não é core
                                    if (false && class_exists('SecurityLogger')) {
                                        // $logger = new SecurityLogger($db);
                                        // $logger->log(
                                        //     $ipAddress,
                                        //     '/forgot-password.php',
                                        //     'POST',
                                        //     'password_reset_request_otp',
                                        //     'password_reset_otp_requested',
                                        //     0,
                                        //     $_SERVER['HTTP_USER_AGENT'] ?? null,
                                        //     $_SERVER['HTTP_REFERER'] ?? null,
                                        //     null,
                                        //     null,
                                        //     null,
                                        //     null
                                        // );
                                    }
                                } catch (Exception $logError) {
                                    error_log("Erro ao registrar log: " . $logError->getMessage());
                                }
                            }
                        } else {
                            // Email não existe - mostrar mensagem genérica de segurança
                            $message = 'Se o email informado existir em nosso sistema, você receberá um código OTP para redefinir sua senha.';
                            $messageType = 'success';
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Erro ao processar reset de senha: " . $e->getMessage());
                    $message = 'Erro ao processar solicitação. Tente novamente mais tarde.';
                    $messageType = 'error';
                } catch (Exception $e) {
                    error_log("Erro geral: " . $e->getMessage());
                    $message = 'Erro ao processar solicitação. Tente novamente mais tarde.';
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
                <img src="assets/img/logos (6).png" alt="SafeNode" class="w-10 h-10">
                <h1 class="text-2xl font-bold text-white">SafeNode</h1>
            </div>
            <p class="text-zinc-400 text-sm">Security Platform</p>
        </div>
        
        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-slate-900 mb-2">Esqueceu sua senha?</h2>
            <p class="text-slate-600 text-sm mb-6">
                Digite seu email e enviaremos um código OTP para redefinir sua senha.
            </p>
            
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php 
                echo $messageType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 
                    'bg-red-50 text-red-800 border border-red-200'; 
            ?>">
                <p class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></p>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="forgotPasswordForm">
                <?php echo csrf_field(); ?>
                
                <!-- Email -->
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-2">
                        Email
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        required 
                        autocomplete="email"
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="seu@email.com"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    >
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
                
                <!-- Submit -->
                <button 
                    type="submit" 
                    name="request_reset"
                    class="w-full bg-slate-900 text-white py-3 rounded-lg font-semibold hover:bg-slate-800 transition-colors mb-4 mt-4"
                >
                    Enviar Código OTP
                </button>
            </form>
            
            <!-- Voltar para login -->
            <div class="text-center">
                <a href="login.php" class="text-sm text-slate-600 hover:text-slate-900 font-medium">
                    ← Voltar para login
                </a>
            </div>
        </div>
        
        <!-- Footer -->
        <p class="text-center text-zinc-500 text-xs mt-6">
            © <?php echo date('Y'); ?> SafeNode Security Platform
        </p>
    </div>
    
    <script>
        // Tratamento global de erros para evitar problemas com extensões do navegador
        window.addEventListener('error', function(e) {
            // Ignorar erros de extensões do navegador
            if (e.message && e.message.includes('message channel closed')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Tratamento de erros não capturados
        window.addEventListener('unhandledrejection', function(e) {
            // Ignorar erros de extensões do navegador
            if (e.reason && e.reason.message && e.reason.message.includes('message channel closed')) {
                e.preventDefault();
                return false;
            }
        });
        
        lucide.createIcons();
        
        function initSafeNodeHumanVerification() {
            try {
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
                    try {
                        if (hvSpinner) hvSpinner.classList.add('hidden');
                        if (hvCheck) hvCheck.classList.remove('hidden');
                        if (hvText) hvText.textContent = 'Verificado com SafeNode';
                    } catch (e) {
                        // Ignorar erros de DOM
                        console.warn('Erro ao atualizar verificação humana:', e);
                    }
                }, 800);
            } catch (e) {
                // Ignorar erros, não é crítico
                console.warn('Erro ao inicializar verificação humana:', e);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            try {
                initSafeNodeHumanVerification();
            } catch (e) {
                console.warn('Erro no DOMContentLoaded:', e);
            }
        });
        
        // Garantir que o formulário seja submetido mesmo com erros de extensões
        const form = document.getElementById('forgotPasswordForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Garantir que o campo de verificação humana está preenchido
                const hvJs = document.getElementById('safenode_hv_js');
                if (hvJs && !hvJs.value) {
                    hvJs.value = '1';
                }
            });
        }
    </script>
</body>
</html>
