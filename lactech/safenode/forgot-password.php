<?php
/**
 * SafeNode - Esqueci a Senha
 * Sistema de recuperação de senha via e-mail
 */

session_start();

// SEGURANÇA: Carregar helpers e aplicar headers
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/EmailService.php';

$db = getSafeNodeDatabase();

$message = '';
$messageType = '';
$step = $_GET['step'] ?? 'request'; // request, verify, reset

// STEP 1: Solicitar código de recuperação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_code'])) {
    if (!CSRFProtection::validate()) {
        $message = 'Token de segurança inválido. Recarregue a página e tente novamente.';
        $messageType = 'error';
    } else {
        $email = XSSProtection::sanitize($_POST['email'] ?? '');
        
        if (!InputValidator::email($email)) {
            $message = 'Por favor, insira um e-mail válido.';
            $messageType = 'error';
        } else {
            if ($db) {
                try {
                    // Verificar se o e-mail existe
                    $stmt = $db->prepare("SELECT id, username FROM safenode_users WHERE email = ? AND is_active = 1");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        // Gerar código OTP
                        $otpCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        
                        // Salvar OTP no banco
                        $stmt = $db->prepare("INSERT INTO safenode_otp_codes (user_id, email, otp_code, action, expires_at, created_at) VALUES (?, ?, ?, 'password_reset', ?, NOW())");
                        $stmt->execute([$user['id'], $email, $otpCode, $expiresAt]);
                        
                        // Enviar e-mail
                        $emailService = new EmailService();
                        $emailSent = $emailService->sendPasswordResetOTP($email, $otpCode);
                        
                        if ($emailSent) {
                            $_SESSION['reset_email'] = $email;
                            $_SESSION['reset_user_id'] = $user['id'];
                            header('Location: forgot-password.php?step=verify');
                            exit;
                        } else {
                            $message = 'Erro ao enviar e-mail. Tente novamente.';
                            $messageType = 'error';
                        }
                    } else {
                        // Por segurança, não informar se o e-mail existe ou não
                        $_SESSION['reset_email'] = $email;
                        header('Location: forgot-password.php?step=verify');
                        exit;
                    }
                } catch (PDOException $e) {
                    error_log("SafeNode Forgot Password Error: " . $e->getMessage());
                    $message = 'Erro ao processar solicitação. Tente novamente.';
                    $messageType = 'error';
                }
            }
        }
    }
}

// STEP 2: Verificar código OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    if (!CSRFProtection::validate()) {
        $message = 'Token de segurança inválido. Recarregue a página e tente novamente.';
        $messageType = 'error';
    } else {
        $otpCode = $_POST['otp_code'] ?? '';
        $email = $_SESSION['reset_email'] ?? '';
        
        if (empty($otpCode) || empty($email)) {
            $message = 'Código inválido.';
            $messageType = 'error';
        } else {
            if ($db) {
                try {
                    $stmt = $db->prepare("SELECT id, user_id FROM safenode_otp_codes WHERE email = ? AND otp_code = ? AND action = 'password_reset' AND verified = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
                    $stmt->execute([$email, $otpCode]);
                    $otpRecord = $stmt->fetch();
                    
                    if ($otpRecord) {
                        // Marcar OTP como verificado
                        $stmt = $db->prepare("UPDATE safenode_otp_codes SET verified = 1, verified_at = NOW() WHERE id = ?");
                        $stmt->execute([$otpRecord['id']]);
                        
                        $_SESSION['reset_verified'] = true;
                        $_SESSION['reset_user_id'] = $otpRecord['user_id'];
                        header('Location: forgot-password.php?step=reset');
                        exit;
                    } else {
                        $message = 'Código inválido ou expirado.';
                        $messageType = 'error';
                    }
                } catch (PDOException $e) {
                    error_log("SafeNode Verify OTP Error: " . $e->getMessage());
                    $message = 'Erro ao verificar código. Tente novamente.';
                    $messageType = 'error';
                }
            }
        }
    }
}

// STEP 3: Redefinir senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!CSRFProtection::validate()) {
        $message = 'Token de segurança inválido. Recarregue a página e tente novamente.';
        $messageType = 'error';
    } else {
        if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified']) {
            header('Location: forgot-password.php?step=request');
            exit;
        }
        
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $userId = $_SESSION['reset_user_id'] ?? null;
        
        if (empty($newPassword) || empty($confirmPassword)) {
            $message = 'Por favor, preencha todos os campos.';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'As senhas não coincidem.';
            $messageType = 'error';
        } elseif (!InputValidator::strongPassword($newPassword)) {
            $message = 'A senha deve ter no mínimo 8 caracteres, incluindo letras maiúsculas, minúsculas, números e símbolos.';
            $messageType = 'error';
        } else {
            if ($db && $userId) {
                try {
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE safenode_users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$newPasswordHash, $userId]);
                    
                    // Limpar sessão de reset
                    unset($_SESSION['reset_email']);
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_verified']);
                    
                    $_SESSION['password_reset_success'] = true;
                    header('Location: login.php');
                    exit;
                } catch (PDOException $e) {
                    error_log("SafeNode Reset Password Error: " . $e->getMessage());
                    $message = 'Erro ao redefinir senha. Tente novamente.';
                    $messageType = 'error';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci a Senha - SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 3px; }
        .glass-card {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="bg-black text-white min-h-screen flex items-center justify-center p-4 font-sans">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 mb-4">
                <img src="assets/img/logos (6).png" alt="SafeNode" class="w-12 h-12">
                <span class="text-3xl font-bold">SafeNode</span>
            </div>
        </div>

        <!-- Card Principal -->
        <div class="glass-card rounded-2xl p-8">
            <?php if ($step === 'request'): ?>
                <!-- STEP 1: Solicitar Código -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold mb-2">Esqueceu sua senha?</h2>
                    <p class="text-sm text-zinc-400">Digite seu e-mail para receber um código de recuperação</p>
                </div>

                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-xl <?php echo $messageType === 'error' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20'; ?> text-sm flex items-start gap-2">
                        <i data-lucide="<?php echo $messageType === 'error' ? 'alert-circle' : 'info'; ?>" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <?php echo csrf_field(); ?>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">E-mail</label>
                        <div class="relative">
                            <i data-lucide="mail" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-500"></i>
                            <input type="email" name="email" required 
                                   class="w-full pl-11 pr-4 py-3 border border-white/10 rounded-xl bg-zinc-900/30 text-white placeholder:text-zinc-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all" 
                                   placeholder="seu@email.com">
                        </div>
                    </div>

                    <button type="submit" name="request_code" 
                            class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2">
                        <i data-lucide="send" class="w-5 h-5"></i>
                        Enviar Código
                    </button>
                </form>

            <?php elseif ($step === 'verify'): ?>
                <!-- STEP 2: Verificar Código -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold mb-2">Verifique seu e-mail</h2>
                    <p class="text-sm text-zinc-400">
                        Enviamos um código de 6 dígitos para<br>
                        <span class="font-semibold text-white"><?php echo htmlspecialchars($_SESSION['reset_email'] ?? ''); ?></span>
                    </p>
                </div>

                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 text-sm flex items-start gap-2">
                        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <?php echo csrf_field(); ?>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Código de Verificação</label>
                        <input type="text" name="otp_code" required maxlength="6" pattern="[0-9]{6}"
                               class="w-full px-4 py-3 border border-white/10 rounded-xl bg-zinc-900/30 text-white text-center text-2xl tracking-widest font-mono placeholder:text-zinc-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all" 
                               placeholder="000000">
                    </div>

                    <button type="submit" name="verify_code" 
                            class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2">
                        <i data-lucide="check" class="w-5 h-5"></i>
                        Verificar Código
                    </button>
                </form>

            <?php elseif ($step === 'reset'): ?>
                <!-- STEP 3: Redefinir Senha -->
                <?php if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified']): ?>
                    <?php header('Location: forgot-password.php?step=request'); exit; ?>
                <?php endif; ?>

                <div class="mb-6">
                    <h2 class="text-2xl font-bold mb-2">Nova Senha</h2>
                    <p class="text-sm text-zinc-400">Defina uma nova senha forte para sua conta</p>
                </div>

                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 text-sm flex items-start gap-2">
                        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="resetForm" class="space-y-5">
                    <?php echo csrf_field(); ?>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Nova Senha</label>
                        <div class="relative">
                            <input type="password" id="new_password" name="new_password" required 
                                   class="w-full px-4 py-3 pr-12 border border-white/10 rounded-xl bg-zinc-900/30 text-white placeholder:text-zinc-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all" 
                                   placeholder="Digite sua nova senha">
                            <button type="button" onclick="togglePassword('new_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-white transition-colors">
                                <i data-lucide="eye" class="w-5 h-5"></i>
                            </button>
                        </div>
                        <div class="mt-3 space-y-2">
                            <p class="text-xs text-zinc-500 flex items-center gap-2">
                                <span id="length-check" class="w-2 h-2 rounded-full bg-zinc-700"></span>
                                Mínimo de 8 caracteres
                            </p>
                            <p class="text-xs text-zinc-500 flex items-center gap-2">
                                <span id="upper-check" class="w-2 h-2 rounded-full bg-zinc-700"></span>
                                Letras maiúsculas e minúsculas
                            </p>
                            <p class="text-xs text-zinc-500 flex items-center gap-2">
                                <span id="number-check" class="w-2 h-2 rounded-full bg-zinc-700"></span>
                                Números e símbolos
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Confirmar Senha</label>
                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   class="w-full px-4 py-3 pr-12 border border-white/10 rounded-xl bg-zinc-900/30 text-white placeholder:text-zinc-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all" 
                                   placeholder="Confirme sua nova senha">
                            <button type="button" onclick="togglePassword('confirm_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-white transition-colors">
                                <i data-lucide="eye" class="w-5 h-5"></i>
                            </button>
                        </div>
                        <p id="match-message" class="mt-2 text-xs hidden"></p>
                    </div>

                    <button type="submit" name="reset_password" 
                            class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        Redefinir Senha
                    </button>
                </form>
            <?php endif; ?>

            <!-- Link para voltar ao login -->
            <div class="mt-6 text-center">
                <a href="login.php" class="text-sm text-zinc-400 hover:text-blue-400 transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Voltar para o login
                </a>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            
            lucide.createIcons();
        }

        // Password validation (se estiver na step reset)
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        if (newPassword && confirmPassword) {
            const matchMessage = document.getElementById('match-message');
            const lengthCheck = document.getElementById('length-check');
            const upperCheck = document.getElementById('upper-check');
            const numberCheck = document.getElementById('number-check');

            newPassword.addEventListener('input', function() {
                const password = this.value;
                
                if (password.length >= 8) {
                    lengthCheck.classList.remove('bg-zinc-700');
                    lengthCheck.classList.add('bg-emerald-500');
                } else {
                    lengthCheck.classList.remove('bg-emerald-500');
                    lengthCheck.classList.add('bg-zinc-700');
                }
                
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) {
                    upperCheck.classList.remove('bg-zinc-700');
                    upperCheck.classList.add('bg-emerald-500');
                } else {
                    upperCheck.classList.remove('bg-emerald-500');
                    upperCheck.classList.add('bg-zinc-700');
                }
                
                if (/\d/.test(password) && /[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                    numberCheck.classList.remove('bg-zinc-700');
                    numberCheck.classList.add('bg-emerald-500');
                } else {
                    numberCheck.classList.remove('bg-emerald-500');
                    numberCheck.classList.add('bg-zinc-700');
                }
                
                checkMatch();
            });

            confirmPassword.addEventListener('input', checkMatch);

            function checkMatch() {
                if (confirmPassword.value && newPassword.value) {
                    if (confirmPassword.value === newPassword.value) {
                        matchMessage.textContent = '✓ As senhas coincidem';
                        matchMessage.className = 'mt-2 text-xs text-emerald-400';
                        matchMessage.classList.remove('hidden');
                    } else {
                        matchMessage.textContent = '✗ As senhas não coincidem';
                        matchMessage.className = 'mt-2 text-xs text-red-400';
                        matchMessage.classList.remove('hidden');
                    }
                } else {
                    matchMessage.classList.add('hidden');
                }
            }
        }
    </script>
</body>
</html>


