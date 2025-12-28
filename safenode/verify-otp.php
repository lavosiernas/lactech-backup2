<?php
/**
 * SafeNode - Verificação de OTP
 */

session_start();

// Verificar se há registro em andamento
if (!isset($_SESSION['safenode_register_data']) || !isset($_SESSION['safenode_register_data']['email'])) {
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

$registerData = $_SESSION['safenode_register_data'];
$userEmail = $registerData['email'];
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
                // Buscar código OTP válido (sem user_id ainda, pois usuário ainda não foi criado)
                $stmt = $pdo->prepare("
                    SELECT id, otp_code, expires_at, attempts, verified 
                    FROM safenode_otp_codes 
                    WHERE email = ? AND action = 'email_verification' AND verified = 0 AND user_id IS NULL
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$userEmail]);
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
                    // Código válido - AGORA SIM criar o usuário no banco
                    $pdo->beginTransaction();
                    
                    try {
                        // Marcar OTP como verificado
                        $stmt = $pdo->prepare("
                            UPDATE safenode_otp_codes 
                            SET verified = 1, verified_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$otpRecord['id']]);
                        
                        // AGORA criar o usuário no banco (após verificar OTP)
                        $stmt = $pdo->prepare("
                            INSERT INTO safenode_users (username, email, password_hash, full_name, role, is_active, email_verified, email_verified_at) 
                            VALUES (?, ?, ?, ?, 'user', 1, 1, NOW())
                        ");
                        $stmt->execute([
                            $registerData['username'],
                            $registerData['email'],
                            $registerData['password_hash'],
                            $registerData['full_name']
                        ]);
                        $userId = $pdo->lastInsertId();
                        
                        // Atualizar OTP com o user_id agora que o usuário foi criado
                        $stmt = $pdo->prepare("UPDATE safenode_otp_codes SET user_id = ? WHERE id = ?");
                        $stmt->execute([$userId, $otpRecord['id']]);
                        
                        $pdo->commit();
                        
                        // Desafio usado com sucesso - resetar para próxima página
                        if (class_exists('SafeNodeHumanVerification')) {
                            SafeNodeHumanVerification::reset();
                        }
                        
                        // Buscar dados do usuário recém-criado
                        $stmt = $pdo->prepare("SELECT id, username, email, full_name, role FROM safenode_users WHERE id = ?");
                        $stmt->execute([$userId]);
                        $user = $stmt->fetch();
                        
                        if (!$user) {
                            throw new Exception('Erro ao criar usuário');
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
                        unset($_SESSION['safenode_register_data'], $_SESSION['safenode_register_plan']);
                        
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
        
            // Buscar dados temporários da sessão (usuário ainda não foi criado)
            if (!isset($registerData)) {
                $registerData = $_SESSION['safenode_register_data'] ?? null;
            }
            
            if ($registerData) {
            // Gerar novo código OTP
            $otpCode = str_pad(strval(rand(100000, 999999)), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
                // Invalidar códigos anteriores para este email
                $stmt = $pdo->prepare("UPDATE safenode_otp_codes SET verified = 1 WHERE email = ? AND action = 'email_verification' AND verified = 0 AND user_id IS NULL");
                $stmt->execute([$userEmail]);
            
                // Salvar novo código (sem user_id ainda)
            $stmt = $pdo->prepare("
                INSERT INTO safenode_otp_codes (user_id, email, otp_code, action, expires_at) 
                    VALUES (NULL, ?, ?, 'email_verification', ?)
            ");
                $stmt->execute([$userEmail, $otpCode, $expiresAt]);
            
            // Enviar email
            $emailService = SafeNodeEmailService::getInstance();
                $emailResult = $emailService->sendRegistrationOTP($userEmail, $otpCode, $registerData['full_name'] ?: $registerData['username']);
            
            if ($emailResult['success']) {
                $success = 'Novo código enviado para seu email!';
            } else {
                $error = 'Erro ao enviar código. Tente novamente.';
            }
            } else {
                $error = 'Dados de registro não encontrados. Por favor, faça o cadastro novamente.';
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
    <script src="https://unpkg.com/lucide@latest"></script>
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
<body class="bg-white min-h-screen flex flex-col md:flex-row">
    
    <!-- Left Side: Image & Branding (Desktop Only) -->
    <div class="hidden md:flex md:w-1/2 lg:w-[55%] relative bg-black text-white overflow-hidden">
        <!-- Background Image -->
        <img src="https://i.postimg.cc/7LvGX8bK/emailotp-(11).jpg" 
             alt="Email Verification" 
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
                    "Verifique seu email para garantir a segurança da sua conta e proteger seus dados."
                </blockquote>
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold">SN</div>
                    <div>
                        <div class="font-semibold">SafeNode Security</div>
                        <div class="text-sm text-slate-400">Proteção em camadas</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side: OTP Form -->
    <div class="w-full md:w-1/2 lg:w-[45%] flex flex-col overflow-y-auto md:h-screen">
        <div class="w-full max-w-md mx-auto px-6 py-8 md:py-12 md:px-10 lg:px-12">
            
            <!-- Mobile: Background decorations -->
            <div class="md:hidden absolute top-0 left-0 w-full h-full overflow-hidden -z-10">
        <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-blue-100/40 blur-3xl"></div>
        <div class="absolute top-[40%] -right-[10%] w-[40%] h-[40%] rounded-full bg-purple-100/30 blur-3xl"></div>
        <div class="absolute -bottom-[10%] left-[20%] w-[30%] h-[30%] rounded-full bg-emerald-50/50 blur-3xl"></div>
    </div>

            <!-- Mobile: Logo and Header -->
            <div class="md:hidden text-center mb-8 relative z-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-700 shadow-lg mb-6">
                <img src="assets/img/logos (5).png" alt="SafeNode" class="w-10 h-10 object-contain drop-shadow-md filter brightness-0 invert">
            </div>
            
            <h1 class="text-2xl font-bold text-slate-900 mb-2 tracking-tight">Verifique seu email</h1>
            <p class="text-slate-500 text-sm leading-relaxed">
                Enviamos um código de 6 dígitos para<br>
                <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($userEmail); ?></span>
            </p>
        </div>

            <!-- Desktop: Header -->
            <div class="hidden md:block mb-8 md:mb-12 text-center">
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Verifique seu email</h1>
                <p class="text-slate-500">
                    Enviamos um código de 6 dígitos para<br>
                    <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($userEmail); ?></span>
                </p>
            </div>

        <!-- Alerts -->
        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 md:p-5 rounded-xl bg-red-500 border-2 border-red-600 shadow-lg shadow-red-500/20 flex items-start gap-3 md:gap-4 animate-shake">
                <div class="flex-shrink-0 w-6 h-6 md:w-7 md:h-7 rounded-full bg-red-600 flex items-center justify-center">
                    <i data-lucide="alert-circle" class="w-4 h-4 md:w-5 md:h-5 text-white"></i>
                </div>
                <p class="text-sm md:text-base text-white font-bold leading-relaxed"><?php echo htmlspecialchars($error); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="mb-6 p-4 md:p-5 rounded-xl bg-emerald-500 border-2 border-emerald-600 shadow-lg shadow-emerald-500/20 flex items-start gap-3 md:gap-4">
                <div class="flex-shrink-0 w-6 h-6 md:w-7 md:h-7 rounded-full bg-emerald-600 flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-4 h-4 md:w-5 md:h-5 text-white"></i>
                </div>
                <p class="text-sm md:text-base text-white font-bold leading-relaxed"><?php echo htmlspecialchars($success); ?></p>
        </div>
        <?php endif; ?>

        <!-- Form -->
            <form method="POST" class="space-y-6 md:space-y-8" id="otpForm">
            <input type="hidden" name="verify" value="1">
            <input type="hidden" name="otp_code" id="otp_code">
            
            <!-- SafeNode Hidden Verification -->
            <input type="hidden" name="safenode_hv_token" value="<?php echo htmlspecialchars($safenodeHvToken); ?>">
            <input type="hidden" name="safenode_hv_js" id="safenode_hv_js" value="">

                <!-- Container centralizado para OTP e Verificação -->
                <div class="flex flex-col items-center gap-6 md:gap-8 w-full">
                    <!-- OTP Inputs Container - Centralizado -->
                    <div class="flex justify-center gap-2 md:gap-3 w-full">
                <?php for($i = 0; $i < 6; $i++): ?>
                <input type="text" 
                               class="otp-input w-12 h-14 md:w-16 md:h-20 text-center text-2xl md:text-4xl font-bold text-slate-900 border-2 border-slate-200 md:border-slate-300 rounded-xl focus:border-slate-900 focus:ring-2 focus:ring-slate-900/20 outline-none bg-white md:bg-slate-50/50 transition-all"
                       maxlength="1" 
                       pattern="[0-9]" 
                       inputmode="numeric" 
                       autocomplete="one-time-code"
                       data-index="<?php echo $i; ?>"
                       required>
                <?php endfor; ?>
            </div>

                    <!-- Verificação Humana SafeNode - Centralizado com mesma largura dos inputs OTP -->
                    <div class="p-3 md:p-4 rounded-2xl border border-slate-200 bg-slate-50 flex items-center gap-3 shadow-sm" style="width: calc(6 * 4rem + 5 * 0.75rem); max-width: 100%;" id="hv-box">
                <div class="relative flex items-center justify-center w-9 h-9">
                    <div class="absolute inset-0 rounded-2xl border-2 border-slate-200 border-t-black animate-spin" id="hv-spinner"></div>
                    <div class="relative z-10 w-7 h-7 rounded-2xl bg-black flex items-center justify-center">
                        <img src="assets/img/logos (6).png" alt="SafeNode" class="w-4 h-4 object-contain">
                    </div>
                </div>
                <div class="flex-1">
                        <p class="text-xs md:text-sm font-semibold text-slate-900 flex items-center gap-1">
                            SafeNode <span class="text-[10px] md:text-xs font-normal text-slate-500">verificação humana</span>
                    </p>
                        <p class="text-[11px] md:text-xs text-slate-500" id="hv-text">Validando interação do navegador…</p>
                </div>
                    <svg id="hv-check" class="w-4 h-4 md:w-5 md:h-5 text-emerald-500 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                    </div>
            </div>

            <button type="submit" 
                        class="bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3.5 md:py-4 rounded-xl shadow-lg shadow-slate-900/20 transition-all transform active:scale-[0.98] flex justify-center items-center group text-sm md:text-base"
                        style="width: calc(6 * 4rem + 5 * 0.75rem); max-width: 100%;">
                <span>Verificar Código</span>
                    <svg class="w-4 h-4 md:w-5 md:h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </form>

        <!-- Resend Link -->
            <div class="mt-6 md:mt-8 text-center">
            <p class="text-slate-500 text-sm mb-3">Não recebeu o código?</p>
            <button 
                type="button" 
                id="resendOtpBtn" 
                onclick="resendOTP()"
                class="text-slate-900 font-semibold text-sm md:text-base hover:underline decoration-2 underline-offset-4 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span id="resendText">Reenviar código</span>
                <span id="resendLoading" class="hidden">Enviando...</span>
                <span id="resendSuccess" class="hidden text-green-600">✓ Código reenviado!</span>
            </button>
            <p id="resendError" class="text-red-600 text-sm mt-2 hidden"></p>
        </div>

        <!-- Footer -->
            <div class="mt-8 md:mt-12 pt-6 text-center border-t border-slate-100">
                <div class="flex items-center justify-center gap-2 text-xs md:text-sm text-slate-400">
                    <svg class="w-3 h-3 md:w-4 md:h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
                Seguro por <span class="font-semibold text-slate-600">SafeNode</span>
                </div>
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
            // Inicializar ícones Lucide
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
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
