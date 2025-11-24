<?php
/**
 * SafeNode - Verificação de OTP
 */

session_start();

// Verificar se há registro em andamento
if (!isset($_SESSION['safenode_register_user_id']) || !isset($_SESSION['safenode_register_email'])) {
    header('Location: register.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';

// Tentar carregar HumanVerification
$safenodeHvToken = '';
try {
    if (file_exists(__DIR__ . '/includes/HumanVerification.php')) {
        require_once __DIR__ . '/includes/HumanVerification.php';
        $safenodeHvToken = SafeNodeHumanVerification::initChallenge();
    }
} catch (Exception $e) {
    error_log("SafeNode OTP HV Error: " . $e->getMessage());
}

$userId = $_SESSION['safenode_register_user_id'];
$userEmail = $_SESSION['safenode_register_email'];
$error = '';
$success = '';

// Processar verificação de OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $otpCode = trim($_POST['otp_code'] ?? '');
    
    // Validar verificação humana SafeNode antes de qualquer coisa
    $hvError = '';
    $hvValidated = true;
    if (class_exists('SafeNodeHumanVerification')) {
        $hvValidated = SafeNodeHumanVerification::validateRequest($_POST, $hvError);
    }
    
    if (!$hvValidated) {
        $error = $hvError ?: 'Falha na verificação de segurança.';
    }
    // Validação básica
    elseif (empty($otpCode) || strlen($otpCode) !== 6) {
        $error = 'Por favor, digite o código de 6 dígitos.';
    } else {
        try {
            $pdo = getSafeNodeDatabase();
            
            if (!$pdo) {
                $error = 'Erro ao conectar ao banco de dados. Tente novamente.';
            } else {
                // Buscar código OTP válido
                $stmt = $pdo->prepare("
                    SELECT id, otp_code, expires_at, attempts, verified 
                    FROM safenode_otp_codes 
                    WHERE user_id = ? AND email = ? AND action = 'email_verification' AND verified = 0
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$userId, $userEmail]);
                $otpRecord = $stmt->fetch();
                
                if (!$otpRecord) {
                    $error = 'Código não encontrado ou já utilizado. Solicite um novo código.';
                } elseif ($otpRecord['attempts'] >= 5) {
                    $error = 'Muitas tentativas incorretas. Solicite um novo código.';
                } elseif (strtotime($otpRecord['expires_at']) < time()) {
                    $error = 'Código expirado. Solicite um novo código.';
                } elseif ($otpRecord['otp_code'] !== $otpCode) {
                    // Incrementar tentativas
                    $stmt = $pdo->prepare("UPDATE safenode_otp_codes SET attempts = attempts + 1 WHERE id = ?");
                    $stmt->execute([$otpRecord['id']]);
                    
                    $remainingAttempts = 5 - ($otpRecord['attempts'] + 1);
                    $error = "Código incorreto. Você tem {$remainingAttempts} tentativa(s) restante(s).";
                } else {
                    // Código válido - verificar email e ativar conta
                    $pdo->beginTransaction();
                    
                    try {
                        // Marcar OTP como verificado
                        $stmt = $pdo->prepare("
                            UPDATE safenode_otp_codes 
                            SET verified = 1, verified_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$otpRecord['id']]);
                        
                        // Ativar conta e marcar email como verificado
                        $stmt = $pdo->prepare("
                            UPDATE safenode_users 
                            SET is_active = 1, email_verified = 1, email_verified_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$userId]);
                        
                        $pdo->commit();
                        
                        // Desafio usado com sucesso - resetar para próxima página
                        if (class_exists('SafeNodeHumanVerification')) {
                            SafeNodeHumanVerification::reset();
                        }
                        
                        // Verificar se há plano selecionado
                        $selectedPlan = $_SESSION['safenode_register_plan'] ?? null;
                        
                        // Criar sessão de login automaticamente
                        $_SESSION['safenode_logged_in'] = true;
                        $_SESSION['safenode_username'] = $user['username'];
                        $_SESSION['safenode_user_id'] = $user['id'];
                        $_SESSION['safenode_user_email'] = $user['email'];
                        $_SESSION['safenode_user_full_name'] = $user['full_name'];
                        $_SESSION['safenode_user_role'] = $user['role'];
                        
                        // Limpar sessão de registro
                        unset($_SESSION['safenode_register_user_id'], $_SESSION['safenode_register_email'], $_SESSION['safenode_register_plan']);
                        
                        // Redirecionar para checkout se houver plano, senão para dashboard
                        if ($selectedPlan) {
                            header('Location: checkout.php?plan=' . urlencode($selectedPlan));
                        } else {
                            header('Location: dashboard.php');
                        }
                        exit;
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("SafeNode OTP Verify Error: " . $e->getMessage());
            $error = 'Erro ao verificar código. Tente novamente.';
        }
    }
}

// Processar reenvio de código
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    // Validar verificação humana SafeNode antes de qualquer coisa
    $hvError = '';
    $hvValidated = true;
    if (class_exists('SafeNodeHumanVerification')) {
        $hvValidated = SafeNodeHumanVerification::validateRequest($_POST, $hvError);
    }
    
    if (!$hvValidated) {
        $error = $hvError ?: 'Falha na verificação de segurança.';
    } else {
        try {
            require_once __DIR__ . '/includes/EmailService.php';
            
            $pdo = getSafeNodeDatabase();
        
        // Buscar dados do usuário
        $stmt = $pdo->prepare("SELECT id, username, email, full_name FROM safenode_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Gerar novo código OTP
            $otpCode = str_pad(strval(rand(100000, 999999)), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Invalidar códigos anteriores
            $stmt = $pdo->prepare("UPDATE safenode_otp_codes SET verified = 1 WHERE user_id = ? AND action = 'email_verification' AND verified = 0");
            $stmt->execute([$userId]);
            
            // Salvar novo código
            $stmt = $pdo->prepare("
                INSERT INTO safenode_otp_codes (user_id, email, otp_code, action, expires_at) 
                VALUES (?, ?, ?, 'email_verification', ?)
            ");
            $stmt->execute([$userId, $userEmail, $otpCode, $expiresAt]);
            
            // Enviar email
            $emailService = SafeNodeEmailService::getInstance();
            $emailResult = $emailService->sendRegistrationOTP($userEmail, $otpCode, $user['full_name'] ?: $user['username']);
            
            if ($emailResult['success']) {
                $success = 'Novo código enviado para seu email!';
            } else {
                $error = 'Erro ao enviar código. Tente novamente.';
            }
        }
        } catch (PDOException $e) {
            error_log("SafeNode OTP Resend Error: " . $e->getMessage());
            $error = 'Erro ao reenviar código. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeNode - Verificar Email</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="apple-touch-icon" href="assets/img/logos (6).png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    animation: {
                        'shake': 'shake 0.5s cubic-bezier(.36,.07,.19,.97) both',
                    },
                    keyframes: {
                        shake: {
                            '10%, 90%': { transform: 'translate3d(-1px, 0, 0)' },
                            '20%, 80%': { transform: 'translate3d(2px, 0, 0)' },
                            '30%, 50%, 70%': { transform: 'translate3d(-4px, 0, 0)' },
                            '40%, 60%': { transform: 'translate3d(4px, 0, 0)' }
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .otp-input {
            transition: all 0.2s ease-in-out;
        }
        
        .otp-input:focus {
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.05);
        }

        /* Hide numbers arrows/spinners */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }
        input[type=number] {
            -moz-appearance: textfield;
        }

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
<body class="bg-slate-50 min-h-screen flex flex-col justify-center items-center relative overflow-hidden">
    
    <!-- Background decorations -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10">
        <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-blue-100/40 blur-3xl"></div>
        <div class="absolute top-[40%] -right-[10%] w-[40%] h-[40%] rounded-full bg-purple-100/30 blur-3xl"></div>
        <div class="absolute -bottom-[10%] left-[20%] w-[30%] h-[30%] rounded-full bg-emerald-50/50 blur-3xl"></div>
    </div>

    <div class="w-full max-w-md bg-white md:rounded-2xl shadow-none md:shadow-xl p-6 md:p-10 min-h-screen md:min-h-0 flex flex-col justify-center">
        
        <!-- Logo and Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-700 shadow-lg mb-6">
                <img src="assets/img/logos (5).png" alt="SafeNode" class="w-10 h-10 object-contain drop-shadow-md filter brightness-0 invert">
            </div>
            
            <h1 class="text-2xl font-bold text-slate-900 mb-2 tracking-tight">Verifique seu email</h1>
            <p class="text-slate-500 text-sm leading-relaxed">
                Enviamos um código de 6 dígitos para<br>
                <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($userEmail); ?></span>
            </p>
        </div>

        <!-- Alerts -->
        <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-sm flex items-center mb-6 animate-shake">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center mb-6">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" class="space-y-8" id="otpForm">
            <input type="hidden" name="verify" value="1">
            <input type="hidden" name="otp_code" id="otp_code">
            
            <!-- SafeNode Hidden Verification -->
            <input type="hidden" name="safenode_hv_token" value="<?php echo htmlspecialchars($safenodeHvToken); ?>">
            <input type="hidden" name="safenode_hv_js" id="safenode_hv_js" value="">

            <div class="flex justify-between gap-2 px-2">
                <?php for($i = 0; $i < 6; $i++): ?>
                <input type="text" 
                       class="otp-input w-12 h-14 text-center text-2xl font-bold text-slate-900 border border-slate-200 rounded-xl focus:border-slate-900 focus:ring-0 outline-none bg-slate-50/50"
                       maxlength="1" 
                       pattern="[0-9]" 
                       inputmode="numeric" 
                       autocomplete="one-time-code"
                       data-index="<?php echo $i; ?>"
                       required>
                <?php endfor; ?>
            </div>

            <!-- Verificação Humana SafeNode -->
            <div class="p-3 rounded-2xl border border-slate-200 bg-slate-50 flex items-center gap-3 shadow-sm" id="hv-box">
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

            <button type="submit" 
                    class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3.5 rounded-xl shadow-lg shadow-slate-900/20 transition-all transform active:scale-[0.98] flex justify-center items-center group">
                <span>Verificar Código</span>
                <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </form>

        <!-- Resend Link -->
        <div class="mt-6 text-center">
            <p class="text-slate-500 text-sm mb-3">Não recebeu o código?</p>
            <form method="POST" id="resendForm">
                <input type="hidden" name="resend" value="1">
                
                <!-- SafeNode Hidden Verification -->
                <input type="hidden" name="safenode_hv_token" value="<?php echo htmlspecialchars($safenodeHvToken); ?>">
                <input type="hidden" name="safenode_hv_js" id="safenode_hv_js_resend" value="">
                
                <button type="submit" class="text-slate-900 font-semibold text-sm hover:underline decoration-2 underline-offset-4 transition-all">
                    Reenviar código
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="mt-auto md:mt-8 pt-6 text-center border-t border-slate-100">
            <div class="flex items-center justify-center gap-2 text-xs text-slate-400">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
                Seguro por <span class="font-semibold text-slate-600">SafeNode</span>
            </div>
        </div>
    </div>

    <script>
        // Inicializar verificação humana SafeNode
        function initSafeNodeHumanVerification() {
            const hvJs = document.getElementById('safenode_hv_js');
            const hvJsResend = document.getElementById('safenode_hv_js_resend');
            const hvSpinner = document.getElementById('hv-spinner');
            const hvCheck = document.getElementById('hv-check');
            const hvText = document.getElementById('hv-text');

            // Marcar imediatamente como verificado
            if (hvJs) hvJs.value = '1';
            if (hvJsResend) hvJsResend.value = '1';

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
            
            const form = document.getElementById('otpForm');
            const inputs = form.querySelectorAll('.otp-input');
            const hiddenInput = document.getElementById('otp_code');

            // Focus first input on load
            inputs[0].focus();

            const updateHiddenInput = () => {
                const code = Array.from(inputs).map(input => input.value).join('');
                hiddenInput.value = code;
                return code;
            };

            inputs.forEach((input, index) => {
                // Handle typing
                input.addEventListener('input', (e) => {
                    // Allow only numbers
                    e.target.value = e.target.value.replace(/[^0-9]/g, '');
                    
                    const val = e.target.value;
                    
                    if (val) {
                        updateHiddenInput();
                        // Move to next input if available
                        if (index < inputs.length - 1) {
                            inputs[index + 1].focus();
                        } else {
                            // If last input is filled, blur or auto-submit
                            // Optional: form.submit();
                            input.blur();
                        }
                    }
                });

                // Handle Backspace
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace') {
                        if (!input.value && index > 0) {
                            inputs[index - 1].focus();
                            // Optional: clear previous input on backspace
                            // inputs[index - 1].value = '';
                            // updateHiddenInput();
                        }
                    }
                });

                // Handle Paste
                input.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pasteData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
                    
                    if (pasteData) {
                        const chars = pasteData.split('');
                        let lastIndex = index;
                        
                        chars.forEach((char, i) => {
                            if (index + i < inputs.length) {
                                inputs[index + i].value = char;
                                lastIndex = index + i;
                            }
                        });
                        
                        updateHiddenInput();
                        
                        // Focus the input after the last filled one, or the last one
                        if (lastIndex < inputs.length - 1) {
                            inputs[lastIndex + 1].focus();
                        } else {
                            inputs[inputs.length - 1].focus();
                        }
                        
                        // Auto submit if full code is pasted
                        if (updateHiddenInput().length === 6) {
                            form.submit();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
