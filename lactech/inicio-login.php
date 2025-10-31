<?php
/**
 * Página Inicial - LacTech
 * Verifica sessão PHP e redireciona IMEDIATAMENTE ou mostra tela de login
 */

// Incluir configuração e iniciar sessão
require_once __DIR__ . '/includes/config_login.php';

// VERIFICAÇÃO IMEDIATA - Se está logado, redireciona ANTES de qualquer output
if (isLoggedIn() && isset($_SESSION['user_role'])) {
    $role = $_SESSION['user_role'];
    
    // Redirecionar IMEDIATAMENTE para o dashboard correto
    switch ($role) {
        case 'manager':
        case 'gerente':
            header("Location: gerente-completo.php", true, 302);
            exit();
            
        case 'owner':
        case 'proprietario':
            header("Location: proprietario.php", true, 302);
            exit();
            
        case 'employee':
        case 'funcionario':
            header("Location: funcionario.php", true, 302);
            exit();
            
        default:
            // Papel não reconhecido, destruir sessão e continuar para login
            session_destroy();
            session_start();
            break;
    }
}

// Se chegou aqui, usuário NÃO está logado - mostrar tela de login
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LacTech - Sistema de Gestão Leiteira</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
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
    </style>
    <script>
        // ==================== CACHE SYSTEM ====================
        const CacheManager = {
            cache: new Map(),
            userData: null,
            farmData: null,
            lastUserFetch: 0,
            lastFarmFetch: 0,
            CACHE_DURATION: 5 * 60 * 1000, // 5 minutos
            
            // Cache de dados do usuário
            async getUserData(forceRefresh = false) {
                const now = Date.now();
                if (!forceRefresh && this.userData && (now - this.lastUserFetch) < this.CACHE_DURATION) {
                    console.log('📋 Usando dados do usuário do cache');
                    return this.userData;
                }
                
                console.log('🔄 Buscando dados do usuário');
                const userData = localStorage.getItem('user_data');
                
                if (userData) {
                    this.userData = JSON.parse(userData);
                    this.lastUserFetch = now;
                    console.log('✅ Dados do usuário cacheados');
                }
                
                return this.userData;
            },
            
            // Cache de dados da fazenda
            async getFarmData(forceRefresh = false) {
                const now = Date.now();
                if (!forceRefresh && this.farmData && (now - this.lastFarmFetch) < this.CACHE_DURATION) {
                    console.log('📋 Usando dados da fazenda do cache');
                    return this.farmData;
                }
                
                console.log('🔄 Buscando dados da fazenda');
                const userData = await this.getUserData();
                if (userData?.farm_id) {
                    // Buscar dados da fazenda via API se necessário
                    this.farmData = { id: userData.farm_id, name: userData.farm_name };
                    this.lastFarmFetch = now;
                    console.log('✅ Dados da fazenda cacheados');
                }
                
                return this.farmData;
            },
            
            // Cache genérico
            set(key, data, ttl = this.CACHE_DURATION) {
                this.cache.set(key, {
                    data,
                    timestamp: Date.now(),
                    ttl
                });
            },
            
            get(key) {
                const item = this.cache.get(key);
                if (!item) return null;
                
                const now = Date.now();
                if (now - item.timestamp > item.ttl) {
                    this.cache.delete(key);
                    return null;
                }
                
                return item.data;
            },
            
            // Limpar cache específico
            clear(key) {
                if (key) {
                    this.cache.delete(key);
                } else {
                    this.cache.clear();
                    this.userData = null;
                    this.farmData = null;
                }
            },
            
            // Invalidar cache de dados críticos
            invalidateUserData() {
                this.userData = null;
                this.farmData = null;
                this.lastUserFetch = 0;
                this.lastFarmFetch = 0;
            }
        };
        
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'forest': {
                            50: '#f0f9f0', 100: '#dcf2dc', 200: '#bce5bc', 300: '#8dd18d',
                            400: '#5bb85b', 500: '#369e36', 600: '#2a7f2a', 700: '#236523',
                            800: '#1f511f', 900: '#1a431a',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="gradient-mesh min-h-screen">
    <!-- Mobile Layout -->
    <div class="md:hidden min-h-screen flex flex-col">
        <div class="flex-1 bg-white p-6 pt-12">
            <!-- Botão Voltar -->
            <a href="index.php" class="inline-flex items-center text-slate-600 hover:text-slate-900 mb-4 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="text-sm font-medium">Voltar</span>
            </a>
            
            <!-- Logo e título no topo -->
            <div class="text-center mb-8">
                <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="Logo Fazenda" class="w-16 h-16 rounded-2xl shadow-lg object-cover mx-auto mb-4">
                <h1 class="text-2xl font-bold text-slate-900 mb-1">LacTech</h1>
                <p class="text-slate-600 text-sm mb-6">Sistema de Gestão Leiteira</p>
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Bem-vindo de volta!</h2>
                <p class="text-slate-600">Entre na sua conta da fazenda</p>
            </div>

            <!-- Error/Success Messages -->
            <div id="errorMessage" class="error-message">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span id="errorText">Erro no login</span>
                </div>
            </div>

            <div id="successMessage" class="success-message">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span id="successText">Login realizado com sucesso!</span>
                </div>
            </div>

            <form id="loginForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                    <input type="email" required name="email" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="seu@email.com">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Senha</label>
                    <div class="relative">
                        <input type="password" required name="password" id="password" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none pr-12" placeholder="Sua senha">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a 3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-forest-600 border-slate-300 rounded focus:ring-forest-500">
                        <span class="ml-2 text-sm text-slate-600">Lembrar de mim</span>
                    </label>
                    <a href="solicitar-alteracao-senha.php" class="text-sm text-forest-600 hover:text-forest-700 font-medium">Esqueceu a senha?</a>
                </div>

                <button type="submit" id="loginBtn" class="w-full gradient-forest text-white py-3 px-4 rounded-xl font-semibold hover:shadow-lg transition-all">
                    <span class="loading-spinner" id="loadingSpinner" style="display: none;"></span>
                    <span id="loginText">Entrar</span>
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-slate-600 text-sm">
                    Fazenda Lagoa do Mato - Entre em contato com o administrador para criar sua conta.
                </p>
            </div>
        </div>
    </div>

    <div class="hidden md:flex min-h-screen">
        <div class="flex-1 relative bg-cover bg-center" style="background-image: url('https://nutrimosaic.com.br/wp-content/uploads/2024/11/vaca-holandesa-comendo-pasto-verde.jpg');">
            <div class="absolute inset-0 bg-black bg-opacity-40"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-center text-white p-8">
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-forest-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold mb-4">LacTech</h1>
                    <p class="text-xl text-white/90">Sistema de Controle Leiteiro</p>
                </div>
            </div>
        </div>

        <!-- Right Side - Form -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Botão Voltar -->
                <a href="index.php" class="inline-flex items-center text-slate-600 hover:text-slate-900 mb-6 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span class="text-sm font-medium">Voltar</span>
                </a>
                
                <!-- Logo acima do bem-vindo -->
                <div class="text-center mb-8">
                    <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="Logo Fazenda" class="w-16 h-16 rounded-2xl shadow-lg object-cover mx-auto mb-4">
                    <h2 class="text-3xl font-bold text-slate-900 mb-2">Bem-vindo de volta!</h2>
                    <p class="text-slate-600">Entre na sua conta da fazenda</p>
                </div>

                <!-- Error/Success Messages Desktop -->
                <div id="errorMessageDesktop" class="error-message">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span id="errorTextDesktop">Erro no login</span>
                    </div>
                </div>

                <div id="successMessageDesktop" class="success-message">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span id="successTextDesktop">Login realizado com sucesso!</span>
                    </div>
                </div>

                <form id="loginFormDesktop" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                        <input type="email" required name="email" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="seu@email.com">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Senha</label>
                        <div class="relative">
                            <input type="password" required name="password" id="passwordDesktop" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none pr-12" placeholder="Sua senha">
                            <button type="button" onclick="togglePasswordDesktop()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg id="eyeIconDesktop" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-forest-600 border-slate-300 rounded focus:ring-forest-500">
                            <span class="ml-2 text-sm text-slate-600">Lembrar de mim</span>
                        </label>
                        <a href="solicitar-alteracao-senha.php" class="text-sm text-forest-600 hover:text-forest-700 font-medium">Esqueceu a senha?</a>
                    </div>

                    <button type="submit" id="loginBtnDesktop" class="w-full gradient-forest text-white py-3 px-4 rounded-xl font-semibold hover:shadow-lg transition-all">
                        <span class="loading-spinner" id="loadingSpinnerDesktop" style="display: none;"></span>
                        <span id="loginTextDesktop">Entrar</span>
                    </button>
                </form>

                <div class="mt-8 text-center">
                    <p class="text-slate-600 text-sm">
                        Fazenda Lagoa do Mato - Entre em contato com o administrador para criar sua conta.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>

        // Global variables for authentication state
        let isAuthenticating = false;

        // Sistema MySQL - Conexão direta com banco de dados

        // Password toggle functions
        function togglePassword() {
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                password.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        function togglePasswordDesktop() {
            const password = document.getElementById('passwordDesktop');
            const eyeIcon = document.getElementById('eyeIconDesktop');
            
            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                password.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        // Show/hide messages
        function showError(message, isDesktop = false) {
            const errorDiv = document.getElementById(isDesktop ? 'errorMessageDesktop' : 'errorMessage');
            const errorText = document.getElementById(isDesktop ? 'errorTextDesktop' : 'errorText');
            const successDiv = document.getElementById(isDesktop ? 'successMessageDesktop' : 'successMessage');
            
            errorText.textContent = message;
            errorDiv.style.display = 'block';
            successDiv.style.display = 'none';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }

        function showSuccess(message, isDesktop = false) {
            const successDiv = document.getElementById(isDesktop ? 'successMessageDesktop' : 'successMessage');
            const successText = document.getElementById(isDesktop ? 'successTextDesktop' : 'successText');
            const errorDiv = document.getElementById(isDesktop ? 'errorMessageDesktop' : 'errorMessage');
            
            successText.textContent = message;
            successDiv.style.display = 'block';
            errorDiv.style.display = 'none';
        }

        function hideMessages(isDesktop = false) {
            const errorDiv = document.getElementById(isDesktop ? 'errorMessageDesktop' : 'errorMessage');
            const successDiv = document.getElementById(isDesktop ? 'successMessageDesktop' : 'successMessage');
            
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
        }

        // Set loading state
        function setLoadingState(isLoading, isDesktop = false) {
            const loginBtn = document.getElementById(isDesktop ? 'loginBtnDesktop' : 'loginBtn');
            const loadingSpinner = document.getElementById(isDesktop ? 'loadingSpinnerDesktop' : 'loadingSpinner');
            const loginText = document.getElementById(isDesktop ? 'loginTextDesktop' : 'loginText');
            
            if (isLoading) {
                loginBtn.disabled = true;
                loadingSpinner.style.display = 'inline-block';
                loginText.textContent = 'Entrando...';
                isAuthenticating = true;
            } else {
                loginBtn.disabled = false;
                loadingSpinner.style.display = 'none';
                loginText.textContent = 'Entrar';
                isAuthenticating = false;
            }
        }

        // Authenticate user with direct database login
        async function authenticateUser(email, password) {
            try {
                console.log('🔐 Tentando login MySQL:', email);
                
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                
                if (data.success && data.user) {
                    console.log('✅ Login MySQL realizado com sucesso:', data.user.name);
                    
                    // Adicionar nome da fazenda
                    data.user.farm_name = 'Lagoa do Mato';
                    data.user.farm_id = 1;
                    
                    // Salvar dados do usuário no localStorage
                    localStorage.setItem('user_data', JSON.stringify(data.user));
                    
                    // Retornar no formato esperado pela interface original
                    return {
                        user: {
                            id: data.user.id,
                            email: data.user.email,
                            email_confirmed_at: new Date().toISOString()
                        },
                        profile: {
                            id: data.user.id,
                            email: data.user.email,
                            name: data.user.name || data.user.email.split('@')[0],
                            user_type: data.user.role || 'gerente',
                            farm_id: 1,
                            farm_name: 'Lagoa do Mato',
                            status: 'active'
                        },
                        redirect: data.redirect // Usar redirect da API
                    };
                } else {
                    throw new Error(data.error || 'Email ou senha incorretos');
                }
                
            } catch (error) {
                console.error('❌ Erro na autenticação MySQL:', error);
                throw error;
            }
        }

        // Store user session
        function storeUserSession(userData, remember) {
            const storage = remember ? localStorage : sessionStorage;
            
            // Store user data
            storage.setItem('userData', JSON.stringify({
                id: userData.user.id,
                email: userData.user.email,
                userType: userData.profile.user_type,
                name: userData.profile.name,
                farmId: userData.profile.farm_id,
                loginTime: new Date().toISOString()
            }));
        }

        // Get redirect URL based on user type
        function getRedirectUrl(userType) {
            const redirectMap = {
                'proprietario': 'proprietario.php',
                'gerente': 'gerente-completo.php',
                'funcionario': 'funcionario.php'
            };
            
            return redirectMap[userType] || 'gerente-completo.php'; // Default fallback
        }

        // Handle form submission
        document.getElementById('loginForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            handleLogin(this, false);
        });

        document.getElementById('loginFormDesktop')?.addEventListener('submit', function(e) {
            e.preventDefault();
            handleLogin(this, true);
        });

        async function handleLogin(form, isDesktop) {
            if (isAuthenticating) return;
            
            const formData = new FormData(form);
            const email = formData.get('email');
            const password = formData.get('password');
            const remember = formData.get('remember') === 'on';
            
            // Validate inputs
            if (!email || !password) {
                showError('Por favor, preencha todos os campos.', isDesktop);
                return;
            }
            
            // Hide previous messages and show loading
            hideMessages(isDesktop);
            setLoadingState(true, isDesktop);
            
            try {
                // Attempt authentication
                const userData = await authenticateUser(email, password);
                
                // Store session data
                storeUserSession(userData, remember);
                
                // Show success message
                showSuccess('Login realizado com sucesso! Redirecionando...', isDesktop);
                
                // Redirect after short delay
                setTimeout(() => {
                    const redirectUrl = userData.redirect || getRedirectUrl(userData.profile.user_type);
                    console.log('🔀 Redirecionando para:', redirectUrl);
                    window.location.href = redirectUrl;
                }, 1500);
                
            } catch (error) {
                console.error('Login error:', error);
                setLoadingState(false, isDesktop);
                
                // Show specific error messages
                if (error.message.includes('Invalid login credentials')) {
                    showError('Email ou senha incorretos. Tente novamente.', isDesktop);
                } else if (error.message.includes('Email not confirmed')) {
                    showError('Conta criada recentemente. Tentando acesso direto...', isDesktop);
                    // Try again after a short delay for newly created accounts
                    setTimeout(() => {
                        handleLogin(form, isDesktop);
                    }, 2000);
                    return;
                } else {
                    showError(error.message || 'Erro no servidor. Tente novamente em alguns minutos.', isDesktop);
                }
            }
        }

        // Check if user is already logged in via localStorage/sessionStorage
        function checkExistingSession() {
            const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
            
            if (userData) {
                try {
                    const user = JSON.parse(userData);
                    
                    // Verificar se os dados são válidos
                    if (!user || !user.id || !user.userType) {
                        localStorage.removeItem('userData');
                        sessionStorage.removeItem('userData');
                        return;
                    }
                    
                    // SESSÃO PERMANENTE - Desabilitado para permitir acesso ao login
                    // const redirectUrl = getRedirectUrl(user.userType);
                    // console.log('🔀 Sessão válida encontrada, redirecionando para:', redirectUrl);
                    // window.location.replace(redirectUrl);
                    // return;
                    
                } catch (error) {
                    // Clear corrupted session data
                    console.error('❌ Erro ao processar sessão:', error);
                    localStorage.removeItem('userData');
                    sessionStorage.removeItem('userData');
                }
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', async function() {
            // Sistema MySQL - Conexão direta com banco de dados
            try {
                console.log('🚀 Inicializando sistema de login LacTech');
                
                // DESABILITADO: Permite acesso direto ao login
                // checkExistingSession();
            } catch (error) {
                console.error('Error initializing page:', error);
            }
        });
        
        // Verificar sessão IMEDIATAMENTE (antes do DOM carregar)
        // DESABILITADO - Permite acesso direto ao login mesmo com sessão ativa
        /*
        (function() {
            const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
            if (userData) {
                try {
                    const user = JSON.parse(userData);
                    if (user && user.id && user.userType) {
                        console.log('🔀 Sessão permanente detectada, redirecionando...');
                        const redirectUrl = getRedirectUrl(user.userType);
                        window.location.replace(redirectUrl);
                    }
                } catch (e) {
                    console.log('❌ Erro ao verificar sessão:', e);
                    localStorage.removeItem('userData');
                    sessionStorage.removeItem('userData');
                }
            }
        })();
        */
    </script>
</body>
</html>
