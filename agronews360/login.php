<?php
/**
 * Página de Login - AgroNews360
 * Login integrado com Lactech (credenciais ou Google)
 */

session_start();

// Se já estiver logado, redirecionar
if (isset($_SESSION['agronews_user_id']) && $_SESSION['agronews_logged_in']) {
    header('Location: index.php');
    exit;
}

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$success = $_SESSION['login_success'] ?? null;
unset($_SESSION['login_success']);
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AgroNews360</title>
    <link rel="icon" href="assets/img/agro360.png" type="image/png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'agro-green': '#22c55e',
                        'agro-green-dark': '#16a34a',
                    },
                    fontFamily: {
                        'sans': ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #e2e8f0 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animações de fundo sutis */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 15% 40%, rgba(34, 197, 94, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 85% 60%, rgba(34, 197, 94, 0.04) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
        
        .login-container {
            position: relative;
            z-index: 1;
        }
        
        .login-card {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 
                0 1px 3px rgba(0, 0, 0, 0.05),
                0 20px 60px rgba(0, 0, 0, 0.08);
        }
        
        .google-btn {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e2e8f0;
            background: #ffffff;
        }
        
        .google-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }
        
        .google-btn:active {
            transform: translateY(0);
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-field {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e2e8f0;
            background: #ffffff;
        }
        
        .input-field:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.08);
            outline: none;
        }
        
        .input-field:focus + .input-icon {
            color: #22c55e;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.2s;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .password-toggle:hover {
            color: #22c55e;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(34, 197, 94, 0.25);
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.35);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .error-message {
            animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .success-message {
            animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-enter {
            animation: formEnter 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes formEnter {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .divider {
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e2e8f0;
        }
        
        .logo-glow {
            filter: drop-shadow(0 2px 8px rgba(34, 197, 94, 0.15));
        }
        
        .toggle-icon {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .toggle-icon.rotated {
            transform: rotate(180deg);
        }
        
        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            transition: color 0.2s;
        }
        
        .input-field.has-icon {
            padding-left: 42px;
        }
        
        .input-field.has-icon.has-toggle {
            padding-right: 44px;
        }
    </style>
</head>
<body>
    <div class="login-container min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Card de Login -->
            <div class="login-card rounded-2xl p-8 md:p-10">
                <!-- Logo e Header -->
                <div class="text-center mb-10">
                    <div class="flex items-center justify-center mb-5">
                        <img src="assets/img/agro360.png" alt="AgroNews360" class="h-24 w-24 object-contain logo-glow">
                    </div>
                    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-2 tracking-tight">
                        AgroNews360
                    </h1>
                    <p class="text-gray-500 text-sm md:text-base font-medium">
                        Portal de Notícias do Agronegócio
                    </p>
                </div>
                
                <!-- Mensagens -->
                <?php if ($error): ?>
                    <div class="error-message mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                        <p class="text-red-700 text-sm font-medium"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message mb-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                        <p class="text-green-700 text-sm font-medium"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Login com Google (Principal) -->
                <button id="googleLoginBtn" class="google-btn w-full mb-5 px-6 py-3.5 rounded-xl flex items-center justify-center space-x-3 font-semibold text-gray-700 text-[15px]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    <span>Entrar com Google</span>
                </button>
                
                <!-- Divisor -->
                <div class="divider my-6">
                    <div class="relative flex justify-center">
                        <span class="px-4 bg-white text-gray-400 text-xs font-medium uppercase tracking-wider">ou</span>
                    </div>
                </div>
                
                <!-- Botão Acessar com Lactech -->
                <button id="toggleLactechBtn" class="btn-primary w-full mb-4 px-6 py-3.5 text-white rounded-xl font-semibold text-sm uppercase tracking-wide flex items-center justify-center space-x-2">
                    <span id="toggleLactechText">Acessar com Lactech</span>
                    <svg id="toggleLactechIcon" class="toggle-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                
                <!-- Login com Email/Senha (Oculto por padrão) -->
                <form id="loginForm" class="space-y-4 hidden" style="display: none;">
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                            Email
                        </label>
                        <div class="input-wrapper">
                            <svg class="input-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                            </svg>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                required
                                class="input-field has-icon w-full px-4 py-3 rounded-xl text-gray-900 placeholder-gray-400 font-medium text-[15px]"
                                placeholder="seu@email.com"
                                autocomplete="email"
                            >
                        </div>
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                            Senha
                        </label>
                        <div class="input-wrapper">
                            <svg class="input-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                class="input-field has-icon has-toggle w-full px-4 py-3 rounded-xl text-gray-900 placeholder-gray-400 font-medium text-[15px]"
                                placeholder="••••••••"
                                autocomplete="current-password"
                            >
                            <button 
                                type="button" 
                                id="togglePassword"
                                class="password-toggle"
                                aria-label="Mostrar senha"
                            >
                                <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: block;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg id="eyeOffIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <button 
                        type="submit" 
                        id="loginBtn"
                        class="btn-primary w-full px-6 py-3.5 text-white rounded-xl font-bold text-sm uppercase tracking-wide flex items-center justify-center space-x-2 mt-6"
                    >
                        <span id="loginBtnText">Entrar</span>
                        <span id="loginBtnLoader" class="hidden">
                            <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </span>
                    </button>
                </form>
                
                <!-- Footer -->
                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-500">
                        Não tem conta? 
                        <a href="index.php" class="text-agro-green font-semibold hover:text-agro-green-dark transition-colors">
                            Acesse como visitante
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const API_BASE = 'api/auth.php';
        
        // Toggle mostrar/ocultar senha
        const togglePasswordBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        const eyeOffIcon = document.getElementById('eyeOffIcon');
        
        togglePasswordBtn.addEventListener('click', function() {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            
            if (isPassword) {
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
                togglePasswordBtn.setAttribute('aria-label', 'Ocultar senha');
            } else {
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
                togglePasswordBtn.setAttribute('aria-label', 'Mostrar senha');
            }
        });
        
        // Toggle formulário Lactech
        let lactechFormVisible = false;
        const toggleLactechBtn = document.getElementById('toggleLactechBtn');
        const toggleLactechText = document.getElementById('toggleLactechText');
        const toggleLactechIcon = document.getElementById('toggleLactechIcon');
        const loginForm = document.getElementById('loginForm');
        
        toggleLactechBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            lactechFormVisible = !lactechFormVisible;
            
            if (lactechFormVisible) {
                // Mostrar formulário
                loginForm.classList.remove('hidden');
                loginForm.style.display = 'block';
                loginForm.style.opacity = '0';
                loginForm.style.transform = 'translateY(8px)';
                loginForm.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                
                // Forçar reflow para garantir que a animação funcione
                void loginForm.offsetHeight;
                
                // Animar entrada
                requestAnimationFrame(() => {
                    loginForm.style.opacity = '1';
                    loginForm.style.transform = 'translateY(0)';
                });
                
                toggleLactechText.textContent = 'Ocultar formulário';
                toggleLactechIcon.classList.add('rotated');
                
                // Focar no primeiro campo
                setTimeout(() => {
                    const emailField = document.getElementById('email');
                    if (emailField) {
                        emailField.focus();
                    }
                }, 300);
            } else {
                // Ocultar formulário
                loginForm.style.opacity = '0';
                loginForm.style.transform = 'translateY(-10px)';
                
                setTimeout(() => {
                    loginForm.classList.add('hidden');
                    loginForm.style.display = 'none';
                    loginForm.style.opacity = '';
                    loginForm.style.transform = '';
                    loginForm.style.transition = '';
                }, 300);
                
                toggleLactechText.textContent = 'Acessar com Lactech';
                toggleLactechIcon.classList.remove('rotated');
            }
        });
        
        // Login com Email/Senha
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('loginBtn');
            const btnText = document.getElementById('loginBtnText');
            const btnLoader = document.getElementById('loginBtnLoader');
            
            btn.disabled = true;
            btnText.classList.add('hidden');
            btnLoader.classList.remove('hidden');
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            try {
                const response = await fetch(API_BASE + '?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: email,
                        password: password
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = result.redirect || 'index.php?success_message=' + encodeURIComponent('Login realizado com sucesso!');
                } else {
                    showError(result.error || 'Erro ao fazer login');
                    btn.disabled = false;
                    btnText.classList.remove('hidden');
                    btnLoader.classList.add('hidden');
                }
            } catch (error) {
                console.error('Erro:', error);
                showError('Erro de conexão. Tente novamente.');
                btn.disabled = false;
                btnText.classList.remove('hidden');
                btnLoader.classList.add('hidden');
            }
        });
        
        // Login com Google
        document.getElementById('googleLoginBtn').addEventListener('click', async function() {
            const btn = this;
            btn.disabled = true;
            btn.style.opacity = '0.7';
            
            try {
                const response = await fetch('api/auth.php?action=get_google_auth_url');
                const result = await response.json();
                
                if (result.success && result.auth_url) {
                    window.location.href = result.auth_url;
                } else {
                    showError(result.error || 'Erro ao iniciar login com Google');
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }
            } catch (error) {
                console.error('Erro:', error);
                showError('Erro ao conectar com Google. Verifique se o Lactech está configurado.');
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        });
        
        // Mostrar erro
        function showError(message) {
            const existingError = document.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message mb-6 p-4 bg-red-50 border border-red-200 rounded-xl';
            errorDiv.innerHTML = `<p class="text-red-700 text-sm font-medium">${message}</p>`;
            
            const form = document.getElementById('loginForm');
            if (form && !form.classList.contains('hidden')) {
                form.parentElement.insertBefore(errorDiv, form);
            } else {
                const toggleBtn = document.getElementById('toggleLactechBtn');
                toggleBtn.parentElement.insertBefore(errorDiv, toggleBtn);
            }
            
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>
