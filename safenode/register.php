<?php
/**
 * SafeNode - Página de Cadastro
 */

// Iniciar buffer de saída para evitar problemas com header()
ob_start();

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
// EmailService removido - não é core
require_once __DIR__ . '/includes/HumanVerification.php';

// Inicializar desafio de verificação humana SafeNode
$safenodeHvToken = SafeNodeHumanVerification::initChallenge();

// Garantir que o token CSRF existe na sessão
CSRFProtection::getToken();

// Processar cadastro
$error = '';
$success = '';
$userId = null;
$userEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    error_log("=== SafeNode Register: Iniciando processamento ===");
    
    // 1. Validar CSRF token
    $csrfToken = $_POST['safenode_csrf_token'] ?? '';
    error_log("SafeNode Register: Token recebido: " . substr($csrfToken, 0, 10) . "...");
    error_log("SafeNode Register: Token na sessão: " . (isset($_SESSION['safenode_csrf_token']) ? substr($_SESSION['safenode_csrf_token'], 0, 10) . "..." : 'NÃO EXISTE'));
    
    if (!CSRFProtection::validate()) {
        $error = 'Token de segurança inválido. Recarregue a página e tente novamente.';
        error_log("SafeNode Register: CSRF inválido - Token recebido: " . ($csrfToken ? 'SIM' : 'NÃO') . ", Token na sessão: " . (isset($_SESSION['safenode_csrf_token']) ? 'SIM' : 'NÃO'));
    } else {
        error_log("SafeNode Register: CSRF válido");
    }
    
    // 2. Se CSRF válido, processar dados
    if (empty($error)) {
        $username = XSSProtection::sanitize(trim($_POST['username'] ?? ''));
        $email = XSSProtection::sanitize(trim($_POST['email'] ?? ''));
        $fullName = XSSProtection::sanitize(trim($_POST['full_name'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        error_log("SafeNode Register: Dados recebidos - Username: " . $username . ", Email: " . $email);
        
        // 3. Validar verificação humana
        $hvError = '';
        if (!SafeNodeHumanVerification::validateRequest($_POST, $hvError)) {
            $error = $hvError ?: 'Falha na verificação de segurança.';
            error_log("SafeNode Register: Verificação humana falhou - " . $error);
        }
        
        // 4. Validações de campos
        if (empty($error) && (empty($username) || empty($email) || empty($password) || empty($confirmPassword))) {
            $error = 'Por favor, preencha todos os campos obrigatórios.';
            error_log("SafeNode Register: Campos vazios");
        }
        
        if (empty($error) && !InputValidator::username($username)) {
            $error = 'Usuário deve ter entre 3-30 caracteres (letras, números, _ ou -)';
            error_log("SafeNode Register: Username inválido");
        }
        
        if (empty($error) && !InputValidator::email($email)) {
            $error = 'Email inválido';
            error_log("SafeNode Register: Email inválido");
        }
        
        // Validar se email não é temporário/descartável - PROTEÇÃO RIGOROSA
        if (empty($error)) {
            error_log("SafeNode Register: Verificando se email é temporário: " . $email);
            $isTemporary = InputValidator::isTemporaryEmail($email);
            error_log("SafeNode Register: Resultado da verificação de email temporário: " . ($isTemporary ? 'SIM - BLOQUEADO' : 'NÃO - PERMITIDO'));
            
            if ($isTemporary) {
                $error = 'Boa tentativa, mas somos SafeNode! Emails temporários não são permitidos aqui. Use um email válido e permanente.';
                error_log("SafeNode Register: Email temporário BLOQUEADO: " . $email);
            }
        }
        
        if (empty($error) && !InputValidator::strongPassword($password)) {
            $error = 'Senha deve ter no mínimo 8 caracteres, incluindo letras maiúsculas, minúsculas, números e símbolos';
            error_log("SafeNode Register: Senha fraca");
        }
        
        if (empty($error) && $password !== $confirmPassword) {
            $error = 'As senhas não coincidem.';
            error_log("SafeNode Register: Senhas não coincidem");
        }
        
        // Verificar números repetidos na senha
        if (empty($error)) {
        preg_match_all('/[0-9]/', $password, $matches);
        if (count($matches[0]) > 1) {
            $numberArray = $matches[0];
            $uniqueNumbers = array_unique($numberArray);
            if (count($uniqueNumbers) < count($numberArray)) {
                $error = 'A senha não pode conter números repetidos.';
                    error_log("SafeNode Register: Senha com números repetidos");
                }
            }
        }
        
        // Verificar se senha é igual ao username
        if (empty($error) && strtolower($password) === strtolower($username)) {
            $error = 'A senha não pode ser igual ao nome de usuário.';
            error_log("SafeNode Register: Senha igual ao username");
        }
        
        // 5. Se passou todas as validações, processar cadastro
        if (empty($error)) {
            error_log("SafeNode Register: Todas as validações passaram, processando...");
    
        try {
            $pdo = getSafeNodeDatabase();
            
            if (!$pdo) {
                $error = 'Erro ao conectar ao banco de dados. Tente novamente.';
                    error_log("SafeNode Register: Erro de conexão com banco");
            } else {
                    // Verificar se email já existe
                $stmt = $pdo->prepare("SELECT id FROM safenode_users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                        $error = 'Este email já está vinculado a uma conta.';
                        error_log("SafeNode Register: Email já existe");
                } else {
                        error_log("SafeNode Register: Email disponível, criando registro temporário...");
                        
                        // Criar hash da senha
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    
                        // Salvar dados temporários na sessão
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
                        $otpCode = str_pad(strval(random_int(100000, 999999)), 6, '0', STR_PAD_LEFT);
                        error_log("SafeNode Register: OTP gerado: " . $otpCode);
                    
                    // Expira em 10 minutos
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    
                        // Salvar código OTP no banco
                    $stmt = $pdo->prepare("
                        INSERT INTO safenode_otp_codes (user_id, email, otp_code, action, expires_at) 
                        VALUES (NULL, ?, ?, 'email_verification', ?)
                    ");
                        $insertResult = $stmt->execute([$email, $otpCode, $expiresAt]);
                        
                        if ($insertResult) {
                            error_log("SafeNode Register: OTP salvo no banco com sucesso");
                        } else {
                            error_log("SafeNode Register: ERRO ao salvar OTP no banco");
                        }
                    
                    // Enviar email com código OTP
                        try {
                            error_log("=== SafeNode Register: Enviando Email OTP ===");
                            error_log("SafeNode Register: Email: " . $email);
                            error_log("SafeNode Register: OTP Code: " . $otpCode);
                            error_log("SafeNode Register: Username: " . ($fullName ?: $username));
                            
                    // EmailService removido - não é core
                    // $emailService = SafeNodeEmailService::getInstance();
                    // $emailResult = $emailService->sendRegistrationOTP($email, $otpCode, $fullName ?: $username);
                    $emailResult = ['success' => false, 'error' => 'Email service não disponível'];
                    
                            error_log("SafeNode Register: Resultado do envio:");
                            error_log("SafeNode Register: - Success: " . ($emailResult['success'] ?? 'N/A'));
                            error_log("SafeNode Register: - Message: " . ($emailResult['message'] ?? 'N/A'));
                            error_log("SafeNode Register: - Error: " . ($emailResult['error'] ?? 'N/A'));
                            
                            if (!isset($emailResult['success']) || !$emailResult['success']) {
                                error_log("SafeNode Register: ERRO - Email não foi enviado com sucesso!");
                                error_log("SafeNode Register: Detalhes do erro: " . ($emailResult['error'] ?? 'Erro desconhecido'));
                            } else {
                                error_log("SafeNode Register: SUCESSO - Email enviado com sucesso!");
                            }
                        } catch (Exception $emailEx) {
                            error_log("=== SafeNode Register: EXCEÇÃO ao enviar email OTP ===");
                            error_log("SafeNode Register: Tipo da exceção: " . get_class($emailEx));
                            error_log("SafeNode Register: Mensagem: " . $emailEx->getMessage());
                            error_log("SafeNode Register: Código: " . $emailEx->getCode());
                            error_log("SafeNode Register: Arquivo: " . $emailEx->getFile());
                            error_log("SafeNode Register: Linha: " . $emailEx->getLine());
                            error_log("SafeNode Register: Stack trace completo:");
                            error_log($emailEx->getTraceAsString());
                            // Não bloquear o fluxo, OTP já está no banco
                        }
                        
                        // Resetar verificação humana
                        SafeNodeHumanVerification::reset();
                        
                        error_log("SafeNode Register: Redirecionando para verify-otp.php");
                        
                        // Limpar buffer e redirecionar
                        ob_clean();
                        header('Location: verify-otp.php');
                        exit();
                    }
                }
            } catch (PDOException $e) {
                error_log("=== SafeNode Register: PDOException CAPTURADA ===");
                error_log("SafeNode Register: PDOException Message: " . $e->getMessage());
                error_log("SafeNode Register: PDOException Code: " . $e->getCode());
                error_log("SafeNode Register: PDOException File: " . $e->getFile());
                error_log("SafeNode Register: PDOException Line: " . $e->getLine());
                error_log("SafeNode Register: PDOException Trace completo:");
                error_log($e->getTraceAsString());
                $error = 'Erro ao processar cadastro. Tente novamente.';
            } catch (Exception $e) {
                error_log("=== SafeNode Register: Exception CAPTURADA ===");
                error_log("SafeNode Register: Exception Type: " . get_class($e));
                error_log("SafeNode Register: Exception Message: " . $e->getMessage());
                error_log("SafeNode Register: Exception Code: " . $e->getCode());
                error_log("SafeNode Register: Exception File: " . $e->getFile());
                error_log("SafeNode Register: Exception Line: " . $e->getLine());
                error_log("SafeNode Register: Exception Trace completo:");
                error_log($e->getTraceAsString());
                error_log("SafeNode Register Exception: " . $e->getMessage());
                error_log("SafeNode Register Exception Trace: " . $e->getTraceAsString());
                $error = 'Erro inesperado ao processar cadastro. Tente novamente.';
            }
        } else {
            error_log("SafeNode Register: Validação falhou - " . $error);
        }
    }
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
<body class="h-full flex flex-col md:flex-row overflow-hidden bg-white">
    
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
                    <img src="assets/img/safe-claro.png" alt="SafeNode" class="w-6 h-6 dark:hidden">
                    <img src="assets/img/logos (6).png" alt="SafeNode" class="w-6 h-6 brightness-0 invert hidden dark:block">
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
            
            <form method="POST" class="space-y-6" id="registerForm">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="register" value="1">
                
                <!-- Username -->
                <div class="space-y-1.5">
                    <label for="username" class="block text-sm font-medium text-slate-700">Nome de usuário</label>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        required 
                        autocomplete="username"
                        class="block w-full px-4 py-3 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
                        placeholder="nomeusuario"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    >
                </div>

                <!-- Email -->
                <div class="space-y-1.5">
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        required 
                        autocomplete="email"
                        class="block w-full px-4 py-3 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
                        placeholder="exemplo@email.com"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    >
                </div>

                <!-- Nome completo (opcional) -->
                <div class="space-y-1.5">
                    <label for="full_name" class="block text-sm font-medium text-slate-700">Nome completo <span class="text-slate-400">(opcional)</span></label>
                    <input 
                        type="text" 
                        name="full_name" 
                        id="full_name" 
                        autocomplete="name"
                        class="block w-full px-4 py-3 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
                        placeholder="Seu nome completo"
                        value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                    >
                </div>

                <!-- Senha -->
                <div class="space-y-1.5">
                    <label for="password" class="block text-sm font-medium text-slate-700">Senha</label>
                    <div class="relative">
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            required 
                            autocomplete="new-password"
                            class="block w-full px-4 py-3 pr-12 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
                            placeholder="••••••••"
                        >
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

                <!-- Confirmar Senha -->
                <div class="space-y-1.5">
                    <label for="confirm_password" class="block text-sm font-medium text-slate-700">Confirmar senha</label>
                    <div class="relative">
                        <input 
                            type="password" 
                            name="confirm_password" 
                            id="confirm_password" 
                            required 
                            autocomplete="new-password"
                            class="block w-full px-4 py-3 pr-12 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
                            placeholder="••••••••"
                        >
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility('confirm_password', 'eyeIcon2')" 
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                        >
                            <svg id="eyeIcon2" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
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

                <!-- Submit Button -->
                <button type="submit" class="w-full flex justify-center items-center py-3 px-4 rounded-lg shadow-sm text-sm font-semibold text-white bg-black hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 transition-all duration-200" id="registerBtn">
                    <span id="loadingSpinner" class="loading-spinner mr-2 hidden"></span>
                    <span id="registerText">Criar conta</span>
                </button>

                <!-- Divider -->
                <div class="mt-6 mb-4">
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-slate-500 uppercase text-xs font-medium">Ou</span>
                    </div>
                </div>

                <!-- Google OAuth Button -->
                <a href="google-auth.php?action=register" class="w-full flex justify-center items-center gap-3 px-4 py-3 border border-slate-200 rounded-lg shadow-sm bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors no-underline">
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
<?php ob_end_flush(); ?>
