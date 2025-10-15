<?php
/**
 * Página inicial - Redirecionamento inteligente
 * Detecta automaticamente se está em localhost ou produção
 */
<<<<<<< HEAD
=======
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xandria - Sistemas para o Agronegócio</title>
    <meta name="description" content="Sistemas completos para gestão do agronegócio brasileiro">
    <link rel="icon" href="https://i.postimg.cc/W17q41wM/lactechpreta.png" type="image/png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
     <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
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
                
                console.log('🔄 Buscando dados do usuário no Supabase');
                const supabase = createSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (user) {
                    const { data: userData } = await supabase
                        .from('users')
                        .select('id, name, email, role, farm_id, profile_photo')
                        .eq('id', user.id)
                        .single();
                    
                    this.userData = { ...user, ...userData };
=======
                console.log('🔄 Buscando dados do usuário');
                const userData = localStorage.getItem('user_data');
                
                if (userData) {
                    this.userData = JSON.parse(userData);
>>>>>>> parent of 15a3155 (.)
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
                
                console.log('🔄 Buscando dados da fazenda no Supabase');
                const userData = await this.getUserData();
                if (userData?.farm_id) {
                    const supabase = createSupabaseClient();
                    const { data: farmData } = await supabase
                        .from('farms')
                        .select('id, name, location')
                        .eq('id', userData.farm_id)
                        .single();
                    
                    this.farmData = farmData;
=======
                console.log('🔄 Buscando dados da fazenda');
                const userData = await this.getUserData();
                if (userData?.farm_id) {
                    // Dados fixos da fazenda Lagoa do Mato
                    this.farmData = {
                        id: 1,
                        name: 'Lagoa do Mato',
                        location: 'MG'
                    };
>>>>>>> parent of 15a3155 (.)
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
             darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#22c55e',
                         dark: {
                             bg: '#000000',
                             card: '#111111',
                             border: '#333333',
                             text: '#ffffff',
                             'text-secondary': '#cccccc',
                             'text-muted': '#999999'
                         }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    animation: {
                        'scroll': 'scroll 25s linear infinite',
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                        'pulse-subtle': 'pulseSubtle 2s ease-in-out infinite',
                        'float': 'float 3s ease-in-out infinite',
                    },
                    keyframes: {
                        scroll: {
                            '0%': { transform: 'translateX(0)' },
                            '100%': { transform: 'translateX(-100%)' },
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        pulseSubtle: {
                            '0%, 100%': { opacity: '0.5' },
                            '50%': { opacity: '1' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-5px)' },
                        },
                    },
                }
            }
        }
    </script>
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: #ffffff;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
             transition: background-color 0.3s ease, color 0.3s ease;
         }
         
         /* Modo Escuro */
         body.dark {
             background: #000000;
             color: #ffffff;
         }
         
         /* Estilos adicionais para modo escuro */
         body.dark nav {
             background: rgba(0, 0, 0, 0.9) !important;
             border-color: #333333 !important;
         }
         
         body.dark .text-gray-800 {
             color: #ffffff !important;
         }
         
         body.dark .text-gray-600 {
             color: #cccccc !important;
         }
         
         body.dark .text-gray-500 {
             color: #999999 !important;
         }
         
         body.dark .text-gray-700 {
             color: #cccccc !important;
         }
         
         body.dark .bg-white {
             background: #111111 !important;
         }
         
         body.dark .border-gray-100 {
             border-color: #333333 !important;
         }
         
         body.dark .bg-gray-50 {
             background: #111111 !important;
         }
         
         body.dark .bg-gray-100 {
             background: #333333 !important;
         }
         
         body.dark .bg-green-50 {
             background: #111111 !important;
         }
         
         body.dark .border-green-100 {
             border-color: #333333 !important;
         }
         
<<<<<<< HEAD
>>>>>>> parent of 0eb3d2f (.)
=======
>>>>>>> parent of 15a3155 (.)
=======
>>>>>>> parent of 9abc566 (Revert ".")

        
        ::-webkit-scrollbar {
            width: 1px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #ddd;
        }
        
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease;
        }
        
        .animate-on-scroll.animate {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Carrossel das logos */
        .carousel-container {
            overflow: hidden;
            width: 100%;
            position: relative;
        }
        
        .logo-carousel {
            display: flex;
            animation: scroll 25s linear infinite;
            width: calc(200% + 32px); /* Largura dupla para loop seamless */
        }
        
        .logo-carousel:hover {
            animation-play-state: paused;
        }
        
        @keyframes scroll {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-50%);
            }
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #22c55e;
            color: #fff;
        }
        
        .btn-primary:hover {
            background: #16a34a;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: transparent;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.05);
        }
         
         body.dark .btn-secondary {
             background: transparent;
             color: #ffffff;
             border: 1px solid #555;
         }
         
         body.dark .btn-secondary:hover {
             border-color: #22c55e;
             background: rgba(34, 197, 94, 0.1);
         }
        
        .card {
            background: #ffffff;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 24px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .card:hover {
            border-color: #22c55e;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.05);
        }
         
         body.dark .card {
             background: #111111;
             border-color: #333333;
             color: #ffffff;
             box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
         }
         
         body.dark .card:hover {
             box-shadow: 0 10px 15px rgba(0, 0, 0, 0.5);
         }
        
        .video-container {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%;
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #eee;
        }
        
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Botão de play/pause do vídeo */
        .video-play-btn {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(34, 197, 94, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .video-play-btn:hover {
            background: #ffffff;
            border-color: #22c55e;
            transform: scale(1.1);
        }
        
        .video-play-btn svg {
            width: 20px;
            height: 20px;
            color: #22c55e;
        }
        
        /* Scroll horizontal customizado */
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, transparent 50%);
        }
        
        .mesh-bg {
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(34, 197, 94, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(34, 197, 94, 0.05) 0%, transparent 50%);
        }
        
        .grid-pattern {
            background-image: 
                linear-gradient(rgba(34, 197, 94, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(34, 197, 94, 0.05) 1px, transparent 1px);
            background-size: 40px 40px;
        }
        
        .feature-icon {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 8px;
            padding: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .stat-card {
            background: #ffffff;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
        }
        
        .stat-card:hover {
            border-color: rgba(34, 197, 94, 0.3);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
         
         body.dark .stat-card {
             background: #111111;
             border-color: #333333;
             color: #ffffff;
             box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
         }
         
         body.dark .stat-card:hover {
             box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
         }
        
        .logo-item {
            flex-shrink: 0;
            width: 120px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.6;
            transition: opacity 0.3s ease;
            margin-right: 64px; /* Espaçamento entre logos */
        }
        
        .logo-item:hover {
            opacity: 1;
        }
        
        .logo-item img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            filter: grayscale(100%) brightness(0.7);
            transition: filter 0.3s ease;
        }
        
        .logo-item:hover img {
            filter: grayscale(0%) brightness(1);
        }

        /* Bandeira do Brasil */
        .brazil-flag {
            width: 24px;
            height: 16px;
            display: inline-block;
            background: linear-gradient(to bottom, #009739 33%, #FEDD00 33%, #FEDD00 66%, #009739 66%);
            border-radius: 2px;
            position: relative;
            margin-right: 8px;
        }

        .brazil-flag::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 12px;
            height: 8px;
            background: #012169;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 bg-white/90 backdrop-blur-sm border-b border-gray-100 shadow-sm">
        <div class="max-w-6xl mx-auto px-6">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded flex items-center justify-center">
                        <img id="headerLogo" src="assets/img/xandria-preta.png" alt="Xandria Logo" class="w-full h-full object-contain">
                    </div>
                    <span class="font-medium text-gray-800">Xandria</span>
                </div>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#platform" class="text-gray-600 hover:text-primary text-sm transition-colors">Sistemas</a>
                    <a href="#features" class="text-gray-600 hover:text-primary text-sm transition-colors">Soluções</a>
                    <a href="#modules" class="text-gray-600 hover:text-primary text-sm transition-colors">Produtos</a>
                    <a href="#demo" class="text-gray-600 hover:text-primary text-sm transition-colors">Demo</a>
                    <a href="#pricing" class="text-gray-600 hover:text-primary text-sm transition-colors">Preços</a>
                </div>
                
                <div class="flex items-center space-x-3">
                    <a href="inicio.php" class="btn btn-secondary text-xs">Entrar</a>
                     <a href="xandria-store.php" class="btn btn-primary text-xs">Xandria Store</a>
                </div>
                
                <!-- Mobile Menu -->
                <button class="md:hidden text-gray-800" id="mobile-menu-btn">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <div class="md:hidden hidden bg-white border-t border-gray-100" id="mobile-menu">
            <div class="px-6 py-4 space-y-4">
                <a href="#platform" class="block text-gray-600 text-sm">Sistemas</a>
                <a href="#features" class="block text-gray-600 text-sm">Soluções</a>
                <a href="#modules" class="block text-gray-600 text-sm">Produtos</a>
                <a href="#demo" class="block text-gray-600 text-sm">Demo</a>
                <a href="#pricing" class="block text-gray-600 text-sm">Preços</a>
                <div class="pt-4 border-t border-gray-100 space-y-2">
                    <a href="inicio.php" class="btn btn-secondary w-full text-xs">Entrar</a>
                    <a href="xandria-store.php" class="btn btn-primary w-full text-xs">Xandria Store</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 relative">
        <div class="max-w-6xl mx-auto px-6">
            <!-- Badge antes do banner -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center px-4 py-2 bg-green-50 border border-green-100 rounded-full">
                    <div class="w-2 h-2 bg-primary rounded-full mr-2 animate-pulse-subtle"></div>
                    <span class="text-sm text-gray-600">Sistemas para o Agronegócio</span>
                </div>
                </div>
                
            <!-- Banner Principal -->
            <div class="px-6 py-6 md:py-0 md:mb-16">
                <div class="max-w-4xl mx-auto">
                    <div class="rounded-3xl overflow-hidden shadow-xl cursor-pointer">
                        <img src="assets/img/agro.jpg" 
                             alt="O Agro move o mundo" 
                             class="w-full h-48 sm:h-56 md:h-64 lg:h-72 object-cover">                
                    </div>
                </div>
            </div>
            
            <!-- Seção de introdução -->
            <div class="text-center mb-16">
                <h2 class="text-2xl md:text-3xl font-semibold mb-6 leading-tight text-gray-800">
                    Tecnologia que transforma<br>o campo brasileiro
                </h2>
                <p class="text-base md:text-lg text-gray-600 mb-8 max-w-3xl mx-auto">
                    A Xandria desenvolve soluções completas para gestão do agronegócio, 
                    desde pecuária leiteira até agricultura de precisão.
                </p>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 border-t border-gray-100">
        <div class="max-w-6xl mx-auto px-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="animate-on-scroll stat-card">
                    <div class="text-2xl font-semibold mb-1 text-gray-800">+500</div>
                    <div class="text-xs text-gray-500">Fazendas Atendidas</div>
                </div>
                <div class="animate-on-scroll stat-card" style="animation-delay: 0.1s;">
                    <div class="text-2xl font-semibold mb-1 text-gray-800">+50.000</div>
                    <div class="text-xs text-gray-500">Animais Gerenciados</div>
                </div>
                <div class="animate-on-scroll stat-card" style="animation-delay: 0.2s;">
                    <div class="text-2xl font-semibold mb-1 text-gray-800">99,9%</div>
                    <div class="text-xs text-gray-500">Disponibilidade</div>
                </div>
                <div class="animate-on-scroll stat-card" style="animation-delay: 0.3s;">
                    <div class="text-2xl font-semibold mb-1 text-gray-800">+25%</div>
                    <div class="text-xs text-gray-500">Aumento de Produtividade</div>
                </div>
            </div>
        </div>
    </section>

    <section id="platform" class="py-20 border-t border-gray-100 relative gradient-bg">
        <div class="max-w-6xl mx-auto px-6">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="animate-on-scroll">
                    <h2 class="text-3xl font-semibold mb-4 text-gray-800">Sistemas Xandria</h2>
                    <p class="text-gray-600 mb-8">
                        A Xandria desenvolve sistemas especializados para o agronegócio brasileiro, 
                        oferecendo soluções completas que otimizam processos, aumentam a produtividade 
                        e melhoram a rentabilidade do seu negócio.
                    </p>
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="feature-icon">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span class="text-sm text-gray-700">LacTech - Gestão de Pecuária Leiteira</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="feature-icon">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <span class="text-sm text-gray-700">AgroSmart - Agricultura de Precisão</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="feature-icon">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                            </div>
                            <span class="text-sm text-gray-700">Xandria Store - Hub de Aplicações</span>
                        </div>
                    </div>
                </div>
                
                <div class="animate-on-scroll">
                    <div class="card">
                        <h3 class="font-semibold mb-6 text-gray-800">Benefícios do Sistema</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Aumento de produtividade</span>
                                <span class="text-sm font-medium text-gray-800">+25%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1">
                                <div class="bg-primary h-1 rounded-full" style="width: 95%"></div>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Redução de custos</span>
                                <span class="text-sm font-medium text-gray-800">-15%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1">
                                <div class="bg-primary h-1 rounded-full" style="width: 85%"></div>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Melhoria na saúde animal</span>
                                <span class="text-sm font-medium text-gray-800">+30%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1">
                                <div class="bg-primary h-1 rounded-full" style="width: 90%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 border-t border-gray-100">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16 animate-on-scroll">
                <h2 class="text-3xl font-semibold mb-4 text-gray-800">Soluções</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    Conheça as principais soluções da Xandria para otimizar a gestão do seu agronegócio
                </p>
            </div>
            
            <div class="flex overflow-x-auto gap-6 pb-4 scrollbar-hide">
                <div class="animate-on-scroll card flex-shrink-0 w-80">
                    <div class="feature-icon mb-4">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-2 text-gray-800">LacTech</h3>
                    <p class="text-sm text-gray-600 mb-6">
                        Sistema completo para gestão de fazendas leiteiras, com controle de rebanho, 
                        produção de leite, reprodução e saúde animal.
                    </p>
                    <div class="space-y-2 mb-6">
                        <div class="flex items-center space-x-2">
                            <div class="w-1 h-1 bg-primary rounded-full"></div>
                            <span class="text-xs text-gray-500">Gestão de rebanho</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-1 h-1 bg-primary rounded-full"></div>
                            <span class="text-xs text-gray-500">Controle de produção</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-1 h-1 bg-primary rounded-full"></div>
                            <span class="text-xs text-gray-500">Saúde animal</span>
                        </div>
                    </div>
                </div>
                
                <div class="animate-on-scroll card flex-shrink-0 w-80" style="animation-delay: 0.1s;">
                    <div class="feature-icon mb-4">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-2 text-gray-800">AgroSmart</h3>
                    <p class="text-sm text-gray-600 mb-6">
                        Sistema de agricultura de precisão com monitoramento de culturas, 
                        análise de solo e otimização de recursos agrícolas.
                    </p>
                    <div class="space-y-2 mb-6">
                        <div class="flex items-center space-x-2">
                            <div class="w-1 h-1 bg-primary rounded-full"></div>
                            <span class="text-xs text-gray-500">Monitoramento de culturas</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-1 h-1 bg-primary rounded-full"></div>
                            <span class="text-xs text-gray-500">Análise de solo</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-1 h-1 bg-primary rounded-full"></div>
                            <span class="text-xs text-gray-500">Otimização de recursos</span>
                        </div>
                    </div>
                </div>
                
                <div class="animate-on-scroll card flex-shrink-0 w-80" style="animation-delay: 0.2s;">
                    <div class="feature-icon mb-4">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-2 text-gray-800">Xandria Store</h3>
                    <p class="text-sm text-gray-600 mb-6">
                        Hub centralizado de aplicações da Xandria, oferecendo acesso 
                        a todos os sistemas e ferramentas em um só lugar.
                    </p>
                    <div class="space-y-2 mb-6">
                        <div class="flex items-center space-x-2">
                            <div class="w-1 h-1 bg-primary rounded-full"></div>
                            <span class="text-xs text-gray-500">Acesso unificado</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-1 h-1 bg-primary rounded-full"></div>
                            <span class="text-xs text-gray-500">Instalação simplificada</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-1 h-1 bg-primary rounded-full"></div>
                            <span class="text-xs text-gray-500">Atualizações automáticas</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modules Section -->
    <section id="modules" class="py-20 border-t border-gray-100 relative gradient-bg">
        <div class="max-w-4xl mx-auto px-6">
            <div class="text-center mb-12 animate-on-scroll">
                <h2 class="text-3xl font-semibold mb-4 text-gray-800">Produtos Xandria</h2>
                <p class="text-gray-600">
                    Conheça os produtos especializados da Xandria para cada segmento do agronegócio
                </p>
            </div>
            
            <div class="flex overflow-x-auto gap-6 pb-4 scrollbar-hide">
                <div class="animate-on-scroll card flex-shrink-0 w-80">
                    <div class="flex items-start space-x-4">
                        <div class="feature-icon">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-2 text-gray-800">Gerente</h3>
                            <p class="text-sm text-gray-600">
                                Módulo para administradores e proprietários, com visão geral da fazenda, 
                                relatórios gerenciais e controle de usuários.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="animate-on-scroll card flex-shrink-0 w-80" style="animation-delay: 0.1s;">
                    <div class="flex items-start space-x-4">
                        <div class="feature-icon">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-2 text-gray-800">Veterinário</h3>
                            <p class="text-sm text-gray-600">
                                Módulo para profissionais de saúde animal, com prontuários, 
                                histórico clínico e controle de medicamentos.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="animate-on-scroll card flex-shrink-0 w-80" style="animation-delay: 0.2s;">
                    <div class="flex items-start space-x-4">
                        <div class="feature-icon">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-2 text-gray-800">Funcionário</h3>
                            <p class="text-sm text-gray-600">
                                Módulo para colaboradores da fazenda, com registro de atividades diárias, 
                                produção e ocorrências.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="animate-on-scroll card flex-shrink-0 w-80" style="animation-delay: 0.3s;">
                    <div class="flex items-start space-x-4">
                        <div class="feature-icon">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-2 text-gray-800">Proprietário</h3>
                            <p class="text-sm text-gray-600">
                                Módulo para donos de fazenda, com visão financeira, 
                                indicadores de desempenho e tomada de decisão.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Demo Section -->
    <section id="demo" class="py-20 border-t border-gray-100 relative gradient-bg">
        <div class="max-w-4xl mx-auto px-6">
            <div class="text-center mb-12 animate-on-scroll">
                <h2 class="text-3xl font-semibold mb-4 text-gray-800">Veja em ação</h2>
                <p class="text-gray-600">
                    Assista como o LacTech transforma a gestão da sua fazenda leiteira
                </p>
            </div>
            
            <div class="animate-on-scroll">
                <div class="video-container">
                    <!-- Botão de play/pause -->
                    <button class="video-play-btn" id="video-toggle">
                        <svg id="play-icon" class="hidden" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        <svg id="pause-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                        </svg>
                    </button>
                    
                    <video 
                        id="demo-video"
                        muted 
                        loop 
                        playsinline
                        poster="assets/img/demo-poster.svg"
                        style="background-color: #f3f4f6;"
                    >
                        <source src="assets/video/demo.mp4" type="video/mp4">
                    </video>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 border-t border-gray-100">
        <div class="max-w-4xl mx-auto px-6">
            <div class="text-center mb-16 animate-on-scroll">
                <h2 class="text-3xl font-semibold mb-4 text-gray-800">Planos e Preços</h2>
                <p class="text-gray-600">
                    Escolha o plano ideal para o tamanho da sua fazenda
                </p>
            </div>
            
            <div class="flex overflow-x-auto gap-8 pb-4 scrollbar-hide">
                <!-- Plano Mensal -->
                <div class="animate-on-scroll card flex-shrink-0 w-80">
                    <div class="text-center mb-6">
                        <h3 class="font-semibold mb-2 text-gray-800">Plano Mensal</h3>
                        <div class="mb-4">
                            <span class="text-3xl font-bold text-gray-800">R$ 1,00</span>
                            <span class="text-sm text-gray-500">/mês</span>
                        </div>
                        <p class="text-xs text-gray-500 mb-4">Pagamento recorrente mensal</p>
                    </div>
                    
                    <ul class="space-y-3 mb-8 text-sm text-gray-600">
                        <li class="flex items-center space-x-3">
                            <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Acesso completo ao sistema</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Gestão ilimitada de animais</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Relatórios e análises</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Suporte por e-mail</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Atualizações automáticas</span>
                        </li>
                    </ul>
                    
                    <button onclick="window.location.href='payment.php?plan=monthly'" 
                            class="btn btn-secondary w-full text-sm py-3 font-medium">
                        Assinar Mensal
                    </button>
                </div>
                
                <!-- Plano Anual -->
                <div class="animate-on-scroll card border-2 border-primary relative flex-shrink-0 w-80" style="animation-delay: 0.1s;">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                        <span class="bg-primary text-white text-xs px-4 py-1 rounded-full font-medium">
                            Mais Popular
                        </span>
                    </div>
                    
                    <div class="text-center mb-6">
                        <h3 class="font-semibold mb-2 text-gray-800">Plano Anual</h3>
                        <div class="mb-4">
                            <span class="text-3xl font-bold text-gray-800">R$ 2,00</span>
                            <span class="text-sm text-gray-500">/ano</span>
                        </div>
                        <div class="mb-2">
                            <span class="text-lg font-semibold text-gray-600 line-through">R$ 12,00</span>
                            <span class="text-sm text-gray-500 ml-2">(economia de R$ 10,00)</span>
                        </div>
                        <p class="text-xs text-gray-500 mb-4">Pagamento único anual</p>
                    </div>
                    
                    <ul class="space-y-3 mb-8 text-sm text-gray-600">
                        <li class="flex items-center space-x-3">
                            <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Todos os benefícios do plano mensal</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>17% de desconto</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Suporte prioritário</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Funcionalidades exclusivas</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Relatórios avançados</span>
                        </li>
                    </ul>
                    
                    <button onclick="window.location.href='payment.php?plan=yearly'" 
                            class="btn btn-primary w-full text-sm py-3 font-medium">
                        Assinar Anual
                    </button>
                </div>
            </div>
            
            <!-- Informações adicionais -->
            <div class="text-center mt-12 animate-on-scroll">
                <div class="bg-gray-50 rounded-lg p-6 max-w-2xl mx-auto">
                    <h4 class="font-medium text-gray-800 mb-3">Informações Importantes</h4>
                    <div class="grid md:grid-cols-3 gap-4 text-sm text-gray-600">
                        <div class="flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Pagamento via Pix</span>
                        </div>
                        <div class="flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Cancelamento a qualquer momento</span>
                        </div>
                        <div class="flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>7 dias de teste grátis</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- Footer -->
    <footer class="py-16 border-t border-gray-100">
        <div class="max-w-6xl mx-auto px-6">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-8 h-8 rounded flex items-center justify-center">
                            <img src="https://i.postimg.cc/W17q41wM/lactechpreta.png" alt="Xandria Logo" class="w-full h-full object-contain">
                        </div>
                        <span class="font-medium text-gray-800">Xandria</span>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Sistemas completos para o agronegócio brasileiro.
                    </p>
                    <div class="flex items-center mb-4">
                        <div class="brazil-flag"></div>
                        <span class="text-xs text-gray-500">Feito no Brasil</span>
                    </div>
                    <p class="text-xs text-gray-500">
                        © 2024 Xandria. Todos os direitos reservados.
                    </p>
                </div>
                
                <div>
                    <h4 class="font-medium mb-3 text-sm text-gray-800">Produto</h4>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li><a href="#platform" class="hover:text-primary transition-colors">Plataforma</a></li>
                        <li><a href="#features" class="hover:text-primary transition-colors">Funcionalidades</a></li>
                        <li><a href="#modules" class="hover:text-primary transition-colors">Módulos</a></li>
                        <li><a href="#demo" class="hover:text-primary transition-colors">Demo</a></li>
                        <li><a href="#pricing" class="hover:text-primary transition-colors">Preços</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-medium mb-3 text-sm text-gray-800">Empresa</h4>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li><a href="#" class="hover:text-primary transition-colors">Sobre</a></li>
                        <li><a href="#" class="hover:text-primary transition-colors">Blog</a></li>
                        <li><a href="#" class="hover:text-primary transition-colors">Carreiras</a></li>

                    </ul>
                </div>
                
                <div>
                    <h4 class="font-medium mb-3 text-sm text-gray-800">Legal</h4>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li><a href="#" class="hover:text-primary transition-colors">Privacidade</a></li>
                        <li><a href="#" class="hover:text-primary transition-colors">Termos</a></li>
                        <li><a href="#" class="hover:text-primary transition-colors">Segurança</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                    mobileMenu.classList.add('hidden');
                }
            });
        });

        // Scroll animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            observer.observe(el);
        });

        // Video controls
        const video = document.getElementById('demo-video');
        const videoToggle = document.getElementById('video-toggle');
        const playIcon = document.getElementById('play-icon');
        const pauseIcon = document.getElementById('pause-icon');

        // Video autoplay on scroll
        const videoObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    video.play().catch(() => {});
                    playIcon.classList.add('hidden');
                    pauseIcon.classList.remove('hidden');
                } else {
                    video.pause();
                    playIcon.classList.remove('hidden');
                    pauseIcon.classList.add('hidden');
                }
            });
        }, { threshold: 0.5 });

        if (video) {
            videoObserver.observe(video);
        }

        // Video toggle button
        videoToggle.addEventListener('click', () => {
            if (video.paused) {
                video.play();
                playIcon.classList.add('hidden');
                pauseIcon.classList.remove('hidden');
            } else {
                video.pause();
                playIcon.classList.remove('hidden');
                pauseIcon.classList.add('hidden');
            }
        });

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            if (window.scrollY > 50) {
                nav.classList.add('bg-white/95');
                nav.classList.remove('bg-white/90');
                nav.classList.add('shadow-md');
            } else {
                nav.classList.add('bg-white/90');
                nav.classList.remove('bg-white/95');
                nav.classList.remove('shadow-md');
            }
        });

                 // Sistema de tema automático baseado no dispositivo
         let isDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;

         // Função para aplicar tema
         function applyTheme() {
             if (isDarkMode) {
                 document.documentElement.classList.add('dark');
                 document.body.classList.add('dark');
             } else {
                 document.documentElement.classList.remove('dark');
                 document.body.classList.remove('dark');
             }
         }

         // Função para atualizar logo baseado no tema
         function updateLogo() {
             const headerLogo = document.getElementById('headerLogo');
             if (headerLogo) {
                 if (isDarkMode) {
                     headerLogo.src = 'https://i.postimg.cc/jjsq36Nf/lactechbranca-1.png';
                 } else {
                     headerLogo.src = 'https://i.postimg.cc/W17q41wM/lactechpreta.png';
                 }
             }
         }

         // Aplicar tema inicial
         applyTheme();
         updateLogo();

         // Atualizar quando o tema do sistema mudar
         window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
             isDarkMode = e.matches;
             applyTheme();
             updateLogo();
        });
    </script>
</body>
</html>