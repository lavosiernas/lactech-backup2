<?php

require_once __DIR__ . '/includes/config_login.php';

if (isLoggedIn() && isset($_SESSION['user_role'])) {

    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $cameFromGerente = strpos($referer, 'gerente-completo.php') !== false;
    $isLoopRedirect = (
        strpos($referer, 'proprietario.php') !== false ||
        strpos($referer, 'funcionario.php') !== false
    ) && !$cameFromGerente;
        if ($isLoopRedirect) {
        session_destroy();
        session_start();
    } else {
        $role = $_SESSION['user_role'];
        switch ($role) {
            case 'manager':
            case 'gerente':
                safeRedirect('gerente-completo.php');
                break;
                
            case 'owner':
            case 'proprietario':
                safeRedirect('proprietario.php');
                break;
                
            case 'employee':
            case 'funcionario':
                safeRedirect('funcionario.php');
                break;
                
            default:
                session_destroy();
                session_start();
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LacTech - Sistema de Gestão Leiteira</title>
    
    <!-- Preconnect para recursos externos (melhora velocidade) -->
    <link rel="preconnect" href="https://i.postimg.cc">
    <link rel="preconnect" href="https://nutrimosaic.com.br">
    <link rel="dns-prefetch" href="https://i.postimg.cc">
    <link rel="dns-prefetch" href="https://nutrimosaic.com.br">
    
    <!-- Favicon -->
    <link rel="icon" href="./assets/img/lactech-logo.png" type="image/x-icon">
    
    <!-- Tailwind CSS otimizado - usar build local se disponível -->
    <?php if (file_exists(__DIR__ . '/assets/css/tailwind.min.css')): ?>
        <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <?php else: ?>
        <!-- Fallback: CDN com configuração otimizada -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            // Otimizar Tailwind: desabilitar plugins não usados
            tailwind.config = {
                corePlugins: {
                    preflight: true,
                },
                // Desabilitar recursos não usados
                theme: {
                    extend: {}
                }
            }
        </script>
    <?php endif; ?>
    
    <!-- CSS customizado --> 
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Preload de recursos críticos -->
    <link rel="preload" href="assets/css/style.css" as="style">
    
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
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

        /* Modal de conta não vinculada */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease-in-out;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: #ffffff !important;
            border-radius: 16px;
            padding: 24px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.3s ease-out;
        }

        .modal-content h3 {
            color: #1e293b !important;
        }

        .modal-content p {
            color: #475569 !important;
        }

        /* Remover modo escuro do modal - sempre usar cores claras */
        @media (prefers-color-scheme: dark) {
            .modal-content {
                background: #ffffff !important;
            }
            
            .modal-content h3 {
                color: #1e293b !important;
            }
            
            .modal-content p {
                color: #475569 !important;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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
                <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="Logo Fazenda" class="w-16 h-16 rounded-2xl shadow-lg object-cover mx-auto mb-4" loading="eager" width="64" height="64">
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
                    <a href="esqueceu-senha.php" class="text-sm text-forest-600 hover:text-forest-700 font-medium">Esqueceu a senha?</a>
                </div>

                <!-- Cloudflare Turnstile Widget -->
                <?php if (defined('TURNSTILE_SITE_KEY') && !empty(TURNSTILE_SITE_KEY)): ?>
                <div class="cf-turnstile" 
                     data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>" 
                     data-theme="light" 
                     data-callback="onTurnstileSuccess"
                     data-error-callback="onTurnstileError"
                     id="turnstile-widget"></div>
                <?php endif; ?>

                <button type="submit" id="loginBtn" class="w-full gradient-forest text-white py-3 px-4 rounded-xl font-semibold hover:shadow-lg transition-all">
                    <span class="loading-spinner" id="loadingSpinner" style="display: none;"></span>
                    <span id="loginText">Entrar</span>
                </button>
            </form>

            <!-- Divisor -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 text-slate-500 bg-transparent">ou</span>
                </div>
            </div>

            <!-- Botão Login com Google -->
            <button type="button" onclick="loginWithGoogle()" id="googleLoginBtn" class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl font-semibold hover:bg-slate-50 transition-all flex items-center justify-center gap-3">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                <span>Entrar com Google</span>
            </button>

            <div class="mt-8 text-center">
                <p class="text-slate-600 text-sm">
                    Fazenda Lagoa do Mato - Entre em contato com o administrador para criar sua conta.
                </p>
            </div>
        </div>
    </div>

    <div class="hidden md:flex min-h-screen">
        <div class="flex-1 relative bg-cover bg-center" style="background-image: url('https://nutrimosaic.com.br/wp-content/uploads/2024/11/vaca-holandesa-comendo-pasto-verde.jpg');" loading="lazy">
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
        <div class="flex-1 flex items-center justify-center p-8 relative">
            <!-- Botão Voltar - Canto superior direito -->
            <a href="index.php" class="absolute top-8 right-8 inline-flex items-center text-slate-600 hover:text-slate-900 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span class="text-sm font-medium">Voltar</span>
                </a>
            
            <div class="w-full max-w-md">
                <!-- Logo acima do bem-vindo -->
                <div class="text-center mb-8">
                    <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="Logo Fazenda" class="w-16 h-16 rounded-2xl shadow-lg object-cover mx-auto mb-4" loading="lazy" width="64" height="64">
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
                        <a href="esqueceu-senha.php" class="text-sm text-forest-600 hover:text-forest-700 font-medium">Esqueceu a senha?</a>
                    </div>

                    <!-- Cloudflare Turnstile Widget -->
                    <?php if (defined('TURNSTILE_SITE_KEY') && !empty(TURNSTILE_SITE_KEY)): ?>
                    <div class="cf-turnstile" 
                         data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>" 
                         data-theme="light" 
                         data-callback="onTurnstileSuccessDesktop"
                         data-error-callback="onTurnstileErrorDesktop"
                         id="turnstile-widget-desktop"></div>
                    <?php endif; ?>

                    <button type="submit" id="loginBtnDesktop" class="w-full gradient-forest text-white py-3 px-4 rounded-xl font-semibold hover:shadow-lg transition-all">
                        <span class="loading-spinner" id="loadingSpinnerDesktop" style="display: none;"></span>
                        <span id="loginTextDesktop">Entrar</span>
                    </button>
                </form>

                <!-- Divisor -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 text-slate-500 bg-transparent">ou</span>
                    </div>
                </div>

                <!-- Botão Login com Google -->
                <button type="button" onclick="loginWithGoogle()" id="googleLoginBtnDesktop" class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl font-semibold hover:bg-slate-50 transition-all flex items-center justify-center gap-3">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span>Entrar com Google</span>
                </button>

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
        
        // Variáveis para armazenar tokens do Turnstile
        let turnstileToken = '';
        let turnstileTokenDesktop = '';
        
        // Callbacks do Turnstile para mobile
        function onTurnstileSuccess(token) {
            turnstileToken = token;
            console.log('✅ Turnstile verificado (mobile)');
        }
        
        function onTurnstileError(error) {
            turnstileToken = '';
            console.error('❌ Erro no Turnstile (mobile):', error);
        }
        
        // Callbacks do Turnstile para desktop
        function onTurnstileSuccessDesktop(token) {
            turnstileTokenDesktop = token;
            console.log('✅ Turnstile verificado (desktop)');
        }
        
        function onTurnstileErrorDesktop(error) {
            turnstileTokenDesktop = '';
            console.error('❌ Erro no Turnstile (desktop):', error);
        }

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
        async function authenticateUser(email, password, turnstileToken = '') {
            try {
                console.log('🔐 Tentando login MySQL:', email);
                
                const requestBody = { email, password };
                if (turnstileToken) {
                    requestBody.turnstile_token = turnstileToken;
                }
                
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestBody)
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
            
            // Verificar se o Turnstile está configurado e obter o token
            const currentToken = isDesktop ? turnstileTokenDesktop : turnstileToken;
            const turnstileWidgetId = isDesktop ? 'turnstile-widget-desktop' : 'turnstile-widget';
            const turnstileWidget = document.getElementById(turnstileWidgetId);
            let tokenToSend = currentToken;
            
            // Se o widget existe e está visível, verificar se precisa do token
            if (turnstileWidget) {
                const siteKey = turnstileWidget.getAttribute('data-sitekey');
                
                // Se o Turnstile está configurado mas não temos token, verificar se foi resolvido
                if (siteKey && !tokenToSend) {
                    // Tentar obter o token diretamente do widget
                    if (window.turnstile) {
                        const widgetElement = turnstileWidget.querySelector('.cf-turnstile');
                        if (widgetElement) {
                            const widgetId = widgetElement.getAttribute('data-widget-id');
                            if (widgetId) {
                                tokenToSend = window.turnstile.getResponse(widgetId);
                            }
                        }
                    }
                    
                    // Se ainda não tiver token, mostrar erro
                    if (!tokenToSend) {
                        showError('Por favor, complete a verificação de segurança.', isDesktop);
                        return;
                    }
                }
            }
            
            // Hide previous messages and show loading
            hideMessages(isDesktop);
            setLoadingState(true, isDesktop);
            
            try {
                // Attempt authentication
                const userData = await authenticateUser(email, password, tokenToSend);
                
                // Store session data
                storeUserSession(userData, remember);
                
                // Reset Turnstile após login bem-sucedido
                if (turnstileWidget && window.turnstile) {
                    const widgetElement = turnstileWidget.querySelector('.cf-turnstile');
                    if (widgetElement) {
                        const widgetId = widgetElement.getAttribute('data-widget-id');
                        if (widgetId) {
                            window.turnstile.reset(widgetId);
                        }
                    }
                }
                
                // Limpar tokens
                if (isDesktop) {
                    turnstileTokenDesktop = '';
                } else {
                    turnstileToken = '';
                }
                
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
                
                // Reset Turnstile em caso de erro
                if (turnstileWidget && window.turnstile) {
                    const widgetElement = turnstileWidget.querySelector('.cf-turnstile');
                    if (widgetElement) {
                        const widgetId = widgetElement.getAttribute('data-widget-id');
                        if (widgetId) {
                            window.turnstile.reset(widgetId);
                        }
                    }
                }
                
                // Limpar tokens
                if (isDesktop) {
                    turnstileTokenDesktop = '';
                } else {
                    turnstileToken = '';
                }
                
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
                } else if (error.message.includes('verificação') || error.message.includes('Turnstile')) {
                    showError(error.message, isDesktop);
                } else {
                    showError(error.message || 'Erro no servidor. Tente novamente em alguns minutos.', isDesktop);
                }
            }
        }

        // Função para login com Google
        async function loginWithGoogle() {
            try {
                // Obter URL de autorização do Google para login
                const response = await fetch('api/google-login.php?action=get_auth_url');
                const result = await response.json();
                
                if (!result.success) {
                    // Se for erro de ambiente local, mostrar mensagem mais detalhada
                    if (result.local_detected) {
                        let errorMsg = result.error + '\n\n';
                        if (result.solutions) {
                            errorMsg += 'Soluções:\n' + result.solutions.join('\n');
                        }
                        alert(errorMsg);
                    } else {
                        alert('Erro ao iniciar login com Google: ' + (result.error || 'Erro desconhecido'));
                    }
                    return;
                }
                
                // Debug: mostrar redirect_uri no console
                if (result.debug) {
                    console.log('🔍 Google Login - redirect_uri:', result.debug.redirect_uri);
                    console.log('🔍 Google Login - expected_uri:', result.debug.expected_uri);
                    if (result.debug.redirect_uri !== result.debug.expected_uri) {
                        console.warn('⚠️ AVISO: redirect_uri diferente do esperado!');
                        console.warn('⚠️ Isso pode causar erro no Google OAuth.');
                        console.warn('⚠️ Certifique-se de adicionar no Google Console:', result.debug.redirect_uri);
                    }
                }
                
                // Abrir popup do Google OAuth
                const width = 500;
                const height = 600;
                const left = (window.innerWidth - width) / 2;
                const top = (window.innerHeight - height) / 2;
                
                const popup = window.open(
                    result.auth_url,
                    'google-login',
                    `width=${width},height=${height},left=${left},top=${top},toolbar=no,location=no,menubar=no`
                );
                
                // Escutar mensagens do popup (quando callback retorna)
                const messageHandler = async function(event) {
                    // Verificar origem por segurança
                    if (event.origin !== window.location.origin && event.origin !== 'https://lactechsys.com') {
                        return;
                    }
                    
                    // Verificar se tem dados e tipo
                    if (!event.data || !event.data.type) {
                        return;
                    }
                    
                    if (event.data.type === 'google_login_success') {
                        // Remover listener
                        window.removeEventListener('message', messageHandler);
                        
                        // Fechar popup se ainda estiver aberto
                        if (popup && !popup.closed) {
                            try {
                                popup.close();
                            } catch (e) {
                                // Ignorar erro se popup já foi fechado
                            }
                        }
                        
                        // Mostrar sucesso
                        showSuccess('Login com Google realizado com sucesso! Redirecionando...', false);
                        showSuccess('Login com Google realizado com sucesso! Redirecionando...', true);
                        
                        // Redirecionar imediatamente (sem delay)
                        const redirectUrl = event.data.redirect || 'gerente-completo.php';
                        window.location.href = redirectUrl;
                        
                    } else if (event.data.type === 'google_login_error') {
                        // Remover listener
                        window.removeEventListener('message', messageHandler);
                        
                        // Fechar popup se ainda estiver aberto
                        if (popup && !popup.closed) {
                            try {
                                popup.close();
                            } catch (e) {
                                // Ignorar erro se popup já foi fechado
                            }
                        }
                        
                        // Mensagem de erro mais detalhada
                        let errorMsg = event.data.message || 'Erro ao fazer login com Google.';
                        
                        // Se for erro de conta desvinculada, mostrar modal
                        if (event.data.error_code === 'account_unlinked') {
                            showGoogleAccountModal('Esta conta Google foi desvinculada do sistema.');
                        } else if (event.data.error_code === 'account_not_linked' || event.data.requires_linking) {
                            // Se for erro de conta não vinculada, mostrar modal
                            showGoogleAccountModal('Esta conta Google não está vinculada ao sistema.');
                        } else if (event.data.error_code === 'invalid_request' || event.data.error_code === 'unauthorized_client') {
                            // Se for erro de redirect_uri, adicionar instruções
                            errorMsg += '\n\n⚠️ Certifique-se de adicionar este URI no Google Console:\nhttps://lactechsys.com/google-login-callback.php';
                            showError(errorMsg, false);
                            showError(errorMsg, true);
                        } else {
                            // Outros erros
                            showError(errorMsg, false);
                            showError(errorMsg, true);
                        }
                        
                        // Log no console para debug
                        console.error('❌ Erro no login Google:', event.data);
                    }
                };
                
                window.addEventListener('message', messageHandler);
                
                // Fallback: verificar se popup foi fechado manualmente
                const checkPopupInterval = setInterval(() => {
                    try {
                        if (popup.closed) {
                            clearInterval(checkPopupInterval);
                            window.removeEventListener('message', messageHandler);
                        }
                    } catch (e) {
                        // Ignorar erro de cross-origin
                        clearInterval(checkPopupInterval);
                    }
                }, 1000);
                
            } catch (error) {
                console.error('Erro ao iniciar login com Google:', error);
                alert('Erro ao iniciar login com Google. Tente novamente.');
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

        // Função para mostrar modal de conta Google não vinculada
        function showGoogleAccountModal(message) {
            const modal = document.getElementById('googleAccountModal');
            const modalMessage = document.getElementById('googleAccountModalMessage');
            
            if (modal && modalMessage) {
                modalMessage.textContent = message;
                modal.classList.add('active');
            }
        }

        // Função para fechar modal
        function closeGoogleAccountModal() {
            const modal = document.getElementById('googleAccountModal');
            if (modal) {
                modal.classList.remove('active');
            }
        }

        // Fechar modal ao clicar fora dele
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('googleAccountModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeGoogleAccountModal();
                    }
                });
            }

            // Verificar parâmetros de URL para mostrar modal
            const urlParams = new URLSearchParams(window.location.search);
            const googleError = urlParams.get('google_error');
            
            if (googleError === 'account_not_linked' || googleError === 'account_unlinked') {
                const message = googleError === 'account_unlinked' 
                    ? 'Esta conta Google foi desvinculada do sistema.'
                    : 'Esta conta Google não está vinculada ao sistema.';
                showGoogleAccountModal(message);
                
                // Limpar parâmetros da URL
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }
        });
    </script>

    <!-- Modal de Conta Google Não Vinculada -->
    <div id="googleAccountModal" class="modal-overlay">
        <div class="modal-content">
            <div class="text-center">
                <!-- Ícone -->
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full mb-3" style="background-color: #fee2e2;">
                    <svg class="h-6 w-6" style="color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                
                <!-- Título -->
                <h3 class="text-lg font-bold mb-2" style="color: #1e293b;">Conta não vinculada</h3>
                
                <!-- Mensagem -->
                <p id="googleAccountModalMessage" class="mb-4 text-sm" style="color: #475569;">
                    Esta conta Google não está vinculada ao sistema.
                </p>
                
                <!-- Botão -->
                <button onclick="closeGoogleAccountModal()" class="w-full px-4 py-2.5 text-sm font-semibold rounded-xl transition-colors" style="background-color: #1e293b; color: #ffffff;">
                    Entendi
                </button>
            </div>
        </div>
    </div>
</body>
</html>
