<?php
/**
 * KRON - Página de Registro
 */

session_start();

// Se já estiver logado, redirecionar para dashboard
if (isset($_SESSION['kron_logged_in']) && $_SESSION['kron_logged_in'] === true) {
    header('Location: dashboard/index.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';

$error = '';
$success = '';

// Processar registro via formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Preencha todos os campos';
    } elseif ($password !== $confirmPassword) {
        $error = 'As senhas não coincidem';
    } elseif (strlen($password) < 8) {
        $error = 'A senha deve ter no mínimo 8 caracteres';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    } else {
        $pdo = getKronDatabase();
        
        if ($pdo) {
            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id FROM kron_users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Este email já está cadastrado';
            } else {
                // Criar usuário
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO kron_users (email, password, name, is_active, email_verified) 
                    VALUES (?, ?, ?, 1, 0)
                ");
                
                if ($stmt->execute([$email, $passwordHash, $name])) {
                    $_SESSION['register_success'] = 'Conta criada com sucesso! Faça login para continuar.';
                    header('Location: login.php');
                    exit;
                } else {
                    $error = 'Erro ao criar conta. Tente novamente.';
                }
            }
        } else {
            $error = 'Erro ao conectar ao banco de dados';
        }
    }
}

// Mensagens de erro
if (isset($_SESSION['google_error'])) {
    $error = $_SESSION['google_error'];
    unset($_SESSION['google_error']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastre-se &mdash; KRON</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="asset/kron.png">
    
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
        <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1920&q=80" 
             alt="Technology Innovation" 
             class="absolute inset-0 w-full h-full object-cover opacity-50">
        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/20"></div>
        
        <!-- Content Overlay -->
        <div class="relative z-10 flex flex-col justify-between w-full p-12 lg:p-16">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <div class="bg-white/10 p-2 rounded-lg backdrop-blur-md">
                    <img src="asset/kron.png" alt="KRON" class="w-6 h-6 brightness-0 invert">
                </div>
                <span class="text-xl font-bold tracking-tight">KRON</span>
            </div>

            <!-- Quote -->
            <div class="max-w-md">
                <blockquote class="text-2xl font-medium leading-snug mb-6">
                    "Junte-se ao ecossistema KRON e conecte todos os seus sistemas em uma plataforma unificada."
                </blockquote>
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold">KR</div>
                    <div>
                        <div class="font-semibold">KRON Ecosystem</div>
                        <div class="text-sm text-slate-400">Plataforma de Integração</div>
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
                <img src="asset/kron.png" alt="KRON" class="w-8 h-8">
                <span class="text-xl font-bold text-slate-900">KRON</span>
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
            
            <form method="POST" class="space-y-6" id="registerForm">
                <input type="hidden" name="register" value="1">
                
                <!-- Nome -->
                <div class="space-y-1.5">
                    <label for="name" class="block text-sm font-medium text-slate-700">Nome completo</label>
                    <input 
                        type="text" 
                        name="name" 
                        id="name" 
                        required 
                        autocomplete="name"
                        class="block w-full px-4 py-3 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
                        placeholder="Seu nome completo"
                        value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
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
                            minlength="8"
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
                    <p class="text-xs text-slate-500">Mínimo de 8 caracteres</p>
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
                            minlength="8"
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

                <!-- Submit Button -->
                <button type="submit" class="w-full flex justify-center items-center py-3 px-4 rounded-lg shadow-sm text-sm font-semibold text-white bg-black hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 transition-all duration-200" id="registerBtn">
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
                <a href="google-auth.php?action=register" class="w-full flex justify-center items-center gap-3 px-4 py-3 border border-slate-200 rounded-lg shadow-sm bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors no-underline">
                    <svg class="h-5 w-5" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span>Cadastrar com Google</span>
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

        document.addEventListener('DOMContentLoaded', function() {
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
