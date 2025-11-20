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

$userId = $_SESSION['safenode_register_user_id'];
$userEmail = $_SESSION['safenode_register_email'];
$error = '';
$success = '';

// Processar verificação de OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $otpCode = trim($_POST['otp_code'] ?? '');
    
    if (empty($otpCode) || strlen($otpCode) !== 6) {
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
                        
                        // Limpar sessão de registro
                        unset($_SESSION['safenode_register_user_id']);
                        unset($_SESSION['safenode_register_email']);
                        
                        $success = 'Email verificado com sucesso! Você pode fazer login agora.';
                        
                        // Redirecionar para login após 2 segundos
                        header('Refresh: 2; url=login.php');
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
        
        .otp-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .otp-input:focus {
            outline: none;
            border-color: #000000;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }
        
        .otp-input:invalid {
            border-color: #fecaca;
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
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Verificar Email</h2>
                <p class="text-slate-600">Digite o código enviado para</p>
                <p class="text-slate-900 font-semibold"><?php echo htmlspecialchars($userEmail); ?></p>
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

            <form id="verifyForm" method="POST" class="space-y-6">
                <input type="hidden" name="verify" value="1">
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-4 text-center">Código de Verificação</label>
                    <div class="flex justify-center gap-2" id="otp-container">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" data-index="0">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" data-index="1">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" data-index="2">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" data-index="3">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" data-index="4">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" data-index="5">
                    </div>
                    <input type="hidden" name="otp_code" id="otp_code" required>
                    <p class="text-xs text-slate-500 text-center mt-3">Digite o código de 6 dígitos enviado para seu email</p>
                </div>

                <button type="submit" id="verifyBtn" class="w-full bg-black text-white py-3 px-4 rounded-xl font-semibold hover:bg-slate-800 hover:shadow-lg transition-all">
                    Verificar
                </button>
            </form>

            <form method="POST" class="mt-4">
                <input type="hidden" name="resend" value="1">
                <button type="submit" class="w-full text-slate-600 py-2 text-sm hover:text-black transition-colors">
                    Não recebeu o código? <span class="font-semibold underline">Reenviar</span>
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-slate-600 text-sm">
                    Sistema protegido por <span class="font-semibold text-slate-900">SafeNode</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Desktop Layout -->
    <div class="hidden md:flex min-h-screen">
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Logo acima do bem-vindo -->
                <div class="text-center mb-8">
                    <img src="assets/img/logos (5).png" alt="Logo SafeNode" class="w-16 h-16 rounded-2xl shadow-lg object-contain mx-auto mb-4" loading="lazy" width="64" height="64">
                    <h2 class="text-3xl font-bold text-slate-900 mb-2">Verificar Email</h2>
                    <p class="text-slate-600">Digite o código enviado para</p>
                    <p class="text-slate-900 font-semibold mt-1"><?php echo htmlspecialchars($userEmail); ?></p>
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

                <form id="verifyFormDesktop" method="POST" class="space-y-6">
                    <input type="hidden" name="verify" value="1">
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-4 text-center">Código de Verificação</label>
                        <div class="flex justify-center gap-2" id="otp-container-desktop">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" data-index="0">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" data-index="1">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" data-index="2">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" data-index="3">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" data-index="4">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="off" data-index="5">
                        </div>
                        <input type="hidden" name="otp_code" id="otp_codeDesktop" required>
                        <p class="text-xs text-slate-500 text-center mt-3">Digite o código de 6 dígitos enviado para seu email</p>
                    </div>

                    <button type="submit" id="verifyBtnDesktop" class="w-full bg-black text-white py-3 px-4 rounded-xl font-semibold hover:bg-slate-800 hover:shadow-lg transition-all">
                        Verificar
                    </button>
                </form>

                <form method="POST" class="mt-4">
                    <input type="hidden" name="resend" value="1">
                    <button type="submit" class="w-full text-slate-600 py-2 text-sm hover:text-black transition-colors">
                        Não recebeu o código? <span class="font-semibold underline">Reenviar</span>
                    </button>
                </form>

                <div class="mt-8 text-center">
                    <p class="text-slate-600 text-sm">
                        Sistema protegido por <span class="font-semibold text-slate-900">SafeNode</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gerenciar inputs OTP separados
        function setupOTPInputs(containerId, hiddenInputId) {
            const container = document.getElementById(containerId);
            const hiddenInput = document.getElementById(hiddenInputId);
            const inputs = container.querySelectorAll('.otp-input');
            
            // Atualizar campo hidden quando qualquer input mudar
            function updateHiddenInput() {
                const code = Array.from(inputs).map(input => input.value).join('');
                hiddenInput.value = code;
            }
            
            inputs.forEach((input, index) => {
                // Permitir apenas números
                input.addEventListener('input', function(e) {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    updateHiddenInput();
                    
                    // Mover para próximo campo se digitou um número
                    if (this.value && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                });
                
                // Voltar para campo anterior ao apagar
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !this.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });
                
                // Colar código completo
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                    pastedData.split('').forEach((char, i) => {
                        if (inputs[index + i]) {
                            inputs[index + i].value = char;
                        }
                    });
                    updateHiddenInput();
                    if (inputs[index + pastedData.length - 1]) {
                        inputs[index + pastedData.length - 1].focus();
                    } else {
                        inputs[inputs.length - 1].focus();
                    }
                });
            });
            
            // Focar no primeiro campo ao carregar
            inputs[0].focus();
        }
        
        // Configurar ambos os layouts
        document.addEventListener('DOMContentLoaded', function() {
            setupOTPInputs('otp-container', 'otp_code');
            setupOTPInputs('otp-container-desktop', 'otp_codeDesktop');
        });

        // Show/hide messages
        function showError(message, isDesktop = false) {
            const errorDiv = document.getElementById(isDesktop ? 'errorMessageDesktop' : 'errorMessage');
            const errorText = document.getElementById(isDesktop ? 'errorTextDesktop' : 'errorText');
            const successDiv = document.getElementById(isDesktop ? 'successMessageDesktop' : 'successMessage');
            
            if (message) {
                errorText.textContent = message;
                errorDiv.style.display = 'block';
                successDiv.style.display = 'none';
                
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
            }
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

