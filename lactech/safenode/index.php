<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeNode - Plataforma Avançada de Segurança Cibernética e Proteção Web</title>
    <meta name="description" content="SafeNode é a plataforma mais completa para segurança cibernética. Proteção DDoS, WAF, monitoramento de ameaças em tempo real, firewall avançado e muito mais. Proteja sua infraestrutura digital com tecnologia de ponta.">
    <meta name="keywords" content="safenode, segurança cibernética, proteção DDoS, WAF, firewall web, segurança digital, proteção contra ataques, monitoramento de ameaças, segurança de aplicações, proteção de infraestrutura, segurança cloud, proteção de borda">
    <meta name="author" content="SafeNode">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://safenode.cloud/index.php">
    <meta property="og:title" content="SafeNode - Plataforma Avançada de Segurança Cibernética">
    <meta property="og:description" content="SafeNode oferece proteção avançada contra ameaças cibernéticas. Detecção de ameaças em tempo real, proteção contra ataques e monitoramento contínuo.">
    <meta property="og:image" content="https://safenode.cloud/assets/img/logos (5).png">
    <meta property="og:url" content="https://safenode.cloud/index.php">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SafeNode">
    <meta property="og:locale" content="pt_BR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SafeNode - Plataforma Avançada de Segurança Cibernética">
    <meta name="twitter:description" content="SafeNode oferece proteção avançada contra ameaças cibernéticas. Detecção de ameaças em tempo real e proteção contra ataques.">
    <meta name="twitter:image" content="https://safenode.cloud/assets/img/logos (5).png">
    <meta name="apple-mobile-web-app-title" content="SafeNode">
    <meta name="application-name" content="SafeNode">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="apple-touch-icon" href="assets/img/logos (6).png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        border: "hsl(var(--border))",
                        input: "hsl(var(--input))",
                        ring: "hsl(var(--ring))",
                        background: "hsl(var(--background))",
                        foreground: "hsl(var(--foreground))",
                    },
                    backgroundImage: {
                        'grid-white': "linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px)",
                        'radial-fade': "radial-gradient(circle at center, rgba(0,0,0,0) 0%, #000000 100%)",
                    }
                }
            }
        }
    </script>

    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #000; 
        }
        ::-webkit-scrollbar-thumb {
            background: #333; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555; 
        }
        
        .glass-nav {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .text-gradient {
            background: linear-gradient(to bottom right, #ffffff 0%, #9ca3af 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Grid Background Animation */
        .bg-grid-pattern {
            background-size: 40px 40px;
            mask-image: linear-gradient(to bottom, transparent, 10%, white, 90%, transparent);
        }

        /* Added advanced animations for premium feel */
        @keyframes pulse-slow {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 0.7; }
        }
        .animate-pulse-slow {
            animation: pulse-slow 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes scan {
            0% { top: 0%; opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }
        .animate-scan {
            animation: scan 3s linear infinite;
        }

        /* Added parallax and scroll-triggered animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }
        
        .parallax-slow {
            transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        /* 3D Globe Animation */
        @keyframes rotate-globe {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .globe-container {
            perspective: 1000px;
        }
        
        .globe {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.1), transparent 50%),
                        radial-gradient(circle at center, #0a0a0a, #000);
            border: 1px solid rgba(255,255,255,0.1);
            position: relative;
            transform-style: preserve-3d;
            box-shadow: 
                inset 0 0 40px rgba(0,0,0,0.8),
                0 0 60px rgba(255,255,255,0.05);
        }
        
        .globe::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: 
                repeating-linear-gradient(
                    0deg,
                    transparent,
                    transparent 19px,
                    rgba(255,255,255,0.03) 19px,
                    rgba(255,255,255,0.03) 20px
                ),
                repeating-linear-gradient(
                    90deg,
                    transparent,
                    transparent 19px,
                    rgba(255,255,255,0.03) 19px,
                    rgba(255,255,255,0.03) 20px
                );
            animation: rotate-globe 60s linear infinite;
        }
        
        .globe::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: radial-gradient(circle at 70% 70%, transparent 40%, rgba(0,0,0,0.6));
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.2);
        }
        
        .glow-text {
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
        }
        
        .code-syntax-keyword { color: #c678dd; }
        .code-syntax-string { color: #98c379; }
        .code-syntax-function { color: #61afef; }
        .code-syntax-comment { color: #5c6370; font-style: italic; }
        
        /* Added smooth hover transitions for all interactive elements */
        a, button {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Custom keyframes for network grid */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        @keyframes dash {
            to { stroke-dashoffset: -1000; }
        }

        @keyframes networkFloat {
            0%, 100% { transform: translateY(0) scale(0.8); }
            50% { transform: translateY(-20px) scale(0.9); }
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Added new animations for the traffic flow */
        @keyframes traffic-flow {
            0% { transform: translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateX(100%); opacity: 0; }
        }
        
        @keyframes traffic-blocked {
            0% { transform: translateX(0); opacity: 0; }
            10% { opacity: 1; }
            50% { opacity: 1; }
            100% { transform: translateX(50%); opacity: 0; } /* Stops halfway */
        }

        .traffic-dot {
            position: absolute;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            filter: blur(1px);
        }
        /* Added smooth scroll behavior for header */
        .glass-nav-scrolled {
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* Added logo marquee animation for social proof */
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        
        .animate-marquee {
            animation: marquee 30s linear infinite;
        }
        
        .animate-marquee:hover {
            animation-play-state: paused;
        }
        
        /* Logo Carousel - Desktop */
        .logos-carousel-infinite {
            overflow: hidden;
            width: 100%;
            position: relative;
            mask-image: linear-gradient(to right, transparent 0%, black 5%, black 95%, transparent 100%);
            -webkit-mask-image: linear-gradient(to right, transparent 0%, black 5%, black 95%, transparent 100%);
        }
        
        .logos-track {
            display: flex;
            gap: 3rem;
            width: fit-content;
            animation: scroll-logos 30s linear infinite;
            opacity: 0.4;
            will-change: transform;
        }
        
        .logos-track:hover {
            animation-play-state: paused;
            opacity: 0.7;
        }
        
        .logo-item {
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .logo-item > div {
            cursor: pointer;
        }
        
        .logo-item > div:hover {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
        }
        
        @keyframes scroll-logos {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(calc(-100% / 2));
            }
        }
        
        /* Logo Carousel - Mobile */
        .logos-carousel-infinite-mobile {
            overflow: hidden;
            width: 100%;
            position: relative;
            mask-image: linear-gradient(to right, transparent 0%, black 5%, black 95%, transparent 100%);
            -webkit-mask-image: linear-gradient(to right, transparent 0%, black 5%, black 95%, transparent 100%);
        }
        
        .logos-track-mobile {
            display: flex;
            gap: 1.5rem;
            width: fit-content;
            animation: scroll-logos-mobile 25s linear infinite;
            opacity: 0.4;
            will-change: transform;
        }
        
        .logos-track-mobile:hover {
            animation-play-state: paused;
            opacity: 0.7;
        }
        
        .logo-item-mobile {
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .logo-item-mobile > div {
            cursor: pointer;
        }
        
        .logo-item-mobile > div:hover {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
        }
        
        @keyframes scroll-logos-mobile {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(calc(-100% / 2));
            }
        }
    </style>
</head>
<body class="bg-black text-white font-sans antialiased selection:bg-white selection:text-black">

    <!-- Enhanced Navigation with scroll detection and better styling -->
    <nav x-data="{ mobileMenuOpen: false, scrolled: false }" 
         @scroll.window="scrolled = (window.pageYOffset > 50)"
         class="fixed w-full z-50 glass-nav transition-all duration-500"
         :class="{ 'glass-nav-scrolled': scrolled }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <a href="index.php" class="flex-shrink-0 flex items-center gap-3 cursor-pointer group">
                    <div class="relative">
                        <img src="assets/img/logos (6).png" alt="SafeNode" class="h-9 w-auto transition-transform duration-300 group-hover:scale-110">
                        <div class="absolute inset-0 bg-white/20 blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <span class="font-bold text-xl tracking-tight group-hover:text-white transition-colors">SafeNode</span>
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-1">
                    <a href="#features" class="px-4 py-2 text-sm text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">Recursos</a>
                    <a href="#network" class="px-4 py-2 text-sm text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">Rede Global</a>
                    <a href="#pricing" class="px-4 py-2 text-sm text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">Planos</a>
                    <a href="#contact" class="px-4 py-2 text-sm text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">Contato</a>
                </div>

                <!-- CTA Button -->
                <div class="hidden md:flex items-center gap-3">
                    <a href="login.php" class="text-sm text-zinc-400 hover:text-white transition-colors">Login</a>
                    <a href="register.php" class="group relative inline-flex h-11 items-center justify-center overflow-hidden rounded-full bg-white px-7 font-semibold text-black transition-all duration-300 hover:bg-zinc-100 hover:shadow-[0_0_25px_rgba(255,255,255,0.3)]">
                        <span class="mr-2">Começar Grátis</span>
                        <i data-lucide="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-1"></i>
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-zinc-400 hover:text-white focus:outline-none transition-colors">
                        <i data-lucide="menu" class="w-6 h-6" x-show="!mobileMenuOpen"></i>
                        <i data-lucide="x" class="w-6 h-6" x-show="mobileMenuOpen" x-cloak></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden bg-black/95 backdrop-blur-xl border-b border-zinc-800" x-cloak>
            <div class="px-4 pt-2 pb-6 space-y-2">
                <a href="#features" class="block px-4 py-3 rounded-xl text-base font-medium text-zinc-300 hover:text-white hover:bg-zinc-900 transition-all">Recursos</a>
                <a href="#network" class="block px-4 py-3 rounded-xl text-base font-medium text-zinc-300 hover:text-white hover:bg-zinc-900 transition-all">Rede Global</a>
                <a href="#pricing" class="block px-4 py-3 rounded-xl text-base font-medium text-zinc-300 hover:text-white hover:bg-zinc-900 transition-all">Planos</a>
                <a href="#contact" class="block px-4 py-3 rounded-xl text-base font-medium text-zinc-300 hover:text-white hover:bg-zinc-900 transition-all">Contato</a>
                <a href="login.php" class="block px-4 py-3 rounded-xl text-base font-medium text-zinc-300 hover:text-white hover:bg-zinc-900 transition-all">Login</a>
                <a href="register.php" class="block px-4 py-3 mt-4 text-center rounded-full bg-white text-black font-bold hover:bg-zinc-100 transition-all">Começar Grátis</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <!-- Background Image -->
        <div class="absolute inset-0 z-0">
            <img src="assets/img/fundoo.png" alt="Background" class="w-full h-full object-cover opacity-30">
            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-black/70 to-black"></div>
        </div>
        
        <!-- Background Grid -->
        <div class="absolute inset-0 z-0 bg-grid-pattern bg-grid-white opacity-10 pointer-events-none"></div>
        
        <!-- Added ambient glow effects -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[500px] bg-white/5 rounded-full blur-[120px] pointer-events-none z-0"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <!-- Improved status badge with 3D globe -->
            <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-zinc-900/50 border border-zinc-800 mb-8 backdrop-blur-sm hover:border-zinc-600 transition-colors cursor-default">
                <div class="globe-container">
                    <div class="globe" style="width: 24px; height: 24px;">
                        <div class="absolute inset-0 rounded-full">
                            <div class="absolute top-1/4 left-1/4 w-1 h-1 bg-green-400 rounded-full animate-pulse"></div>
                            <div class="absolute top-1/2 right-1/3 w-1 h-1 bg-green-400 rounded-full animate-pulse" style="animation-delay: 0.5s"></div>
                            <div class="absolute bottom-1/3 left-1/2 w-1 h-1 bg-green-400 rounded-full animate-pulse" style="animation-delay: 1s"></div>
                        </div>
                    </div>
                </div>
                <span class="text-xs font-medium text-zinc-300">Sistemas Operacionais: 100%</span>
            </div>
            
            <h1 class="text-5xl md:text-7xl font-bold tracking-tight mb-6 text-gradient max-w-5xl mx-auto leading-tight glow-text relative z-10" style="text-shadow: 0 0 30px rgba(255, 255, 255, 0.3);">
                Segurança que Protege<br class="hidden md:block" /> Sem Interromper.
            </h1>
            
            <p class="mt-6 text-xl text-zinc-400 max-w-2xl mx-auto mb-10 font-light leading-relaxed">
                Proteja suas aplicações, APIs e infraestrutura com nossa rede global de borda. 
                Implementação em segundos, proteção para sempre.
            </p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="register.php" class="w-full sm:w-auto px-8 py-4 bg-white text-black rounded-full font-semibold hover:bg-zinc-200 transition-all transform hover:scale-105 flex items-center justify-center gap-2 shadow-[0_0_20px_rgba(255,255,255,0.3)] hover:shadow-[0_0_30px_rgba(255,255,255,0.5)]">
                    Começar Gratuitamente
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
                <a href="#features" class="w-full sm:w-auto px-8 py-4 bg-zinc-900/50 text-white border border-zinc-800 rounded-full font-medium hover:bg-zinc-800 transition-all flex items-center justify-center gap-2 backdrop-blur-sm hover:border-zinc-600">
                    <i data-lucide="play-circle" class="w-4 h-4"></i>
                    Ver Demo
                </a>
            </div>

            <!-- Dashboard Preview (mobile simplificado + desktop completo) -->
            <!-- Mobile: versão compacta em coluna única -->
            <div class="mt-16 max-w-md mx-auto w-full md:hidden">
                <div class="relative rounded-3xl border border-zinc-800 bg-black/90 backdrop-blur-xl shadow-2xl overflow-hidden p-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="text-[10px] uppercase tracking-wider text-zinc-500 font-semibold">Conectado</span>
                        </div>
                        <span class="text-[10px] text-zinc-500 font-mono">safenode.cloud</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-left">
                        <div class="p-3 rounded-xl bg-zinc-900/60 border border-zinc-800">
                            <p class="text-[10px] text-zinc-500 mb-1">TOTAL DE REQUISIÇÕES</p>
                            <p class="text-xl font-bold">2.4M</p>
                            <p class="text-[10px] text-zinc-500 mt-1">Últimas 24h</p>
                        </div>
                        <div class="p-3 rounded-xl bg-zinc-900/60 border border-zinc-800">
                            <p class="text-[10px] text-zinc-500 mb-1">AMEAÇAS MITIGADAS</p>
                            <p class="text-xl font-bold text-red-400">14.2k</p>
                            <p class="text-[10px] text-zinc-500 mt-1">Bloqueado por IA</p>
                        </div>
                        <div class="p-3 rounded-xl bg-zinc-900/60 border border-zinc-800">
                            <p class="text-[10px] text-zinc-500 mb-1">LATÊNCIA GLOBAL</p>
                            <p class="text-xl font-bold">34ms</p>
                            <p class="text-[10px] text-zinc-500 mt-1">P99</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3">
                        <div class="rounded-xl bg-[#050505] border border-zinc-800/60 p-3">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] text-zinc-300">Mapa de Tráfego</span>
                                <span class="text-[10px] text-zinc-500">1H</span>
                            </div>
                            <div class="relative h-32 bg-[linear-gradient(to_right,#80808008_1px,transparent_1px),linear-gradient(to_bottom,#80808008_1px,transparent_1px)] bg-[size:20px_20px] rounded-lg flex items-center justify-center overflow-hidden">
                                <div class="relative">
                                    <div class="w-10 h-10 rounded-full bg-zinc-900 border border-zinc-700 flex items-center justify-center shadow-[0_0_20px_rgba(16,185,129,0.4)]">
                                        <i data-lucide="server" class="w-4 h-4 text-green-500"></i>
                                    </div>
                                    <div class="absolute inset-0 -m-4 border border-green-500/10 rounded-full animate-[ping_3s_linear_infinite]"></div>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl bg-zinc-950 border border-zinc-800/60 p-3">
                            <p class="text-[11px] text-zinc-300 mb-2">Registro de Eventos</p>
                            <div class="space-y-1 font-mono text-[10px] max-h-24 overflow-hidden">
                                <div class="flex gap-2 opacity-60">
                                    <span class="text-zinc-500">10:42:01</span>
                                    <span class="text-emerald-400">PERMITIR</span>
                                    <span class="text-zinc-400">192.168.1.42</span>
                                </div>
                                <div class="flex gap-2 opacity-60">
                                    <span class="text-zinc-500">10:42:05</span>
                                    <span class="text-red-400">BLOQUEAR</span>
                                    <span class="text-zinc-400">Injeção SQL</span>
                                </div>
                                <div class="flex gap-2 border-l-2 border-red-500 pl-2 bg-red-500/5">
                                    <span class="text-zinc-500">10:42:12</span>
                                    <span class="text-red-500 font-bold">MITIGADO</span>
                                    <span class="text-white">Ataque DDoS L7</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop: preview completo -->
            <div class="mt-24 relative max-w-6xl mx-auto hidden md:block">
                <div class="absolute -inset-1 bg-gradient-to-r from-zinc-700 via-zinc-500 to-zinc-700 rounded-2xl blur opacity-20 animate-pulse-slow"></div>
                <div class="relative rounded-2xl border border-zinc-800 bg-black/90 backdrop-blur-xl shadow-2xl overflow-hidden">
                    <!-- Window Controls -->
                    <div class="h-10 border-b border-zinc-800 flex items-center px-4 gap-2 bg-zinc-900/80">
                        <div class="flex gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500/20 border border-red-500/50 hover:bg-red-500 transition-colors"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/20 border border-yellow-500/50 hover:bg-yellow-500 transition-colors"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/20 border border-green-500/50 hover:bg-green-500 transition-colors"></div>
                        </div>
                        <div class="ml-auto flex items-center gap-2 px-3 py-1 rounded-full bg-black/50 border border-zinc-800">
                            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="text-[10px] text-zinc-400 font-mono tracking-wider">CONECTADO</span>
                        </div>
                    </div>
                    
                    <!-- Dashboard Content -->
                    <div class="flex h-[500px]">
                        <!-- Sidebar -->
                        <div class="w-64 hidden md:flex flex-col border-r border-zinc-800/50 bg-zinc-900/20 p-4">
                            <div class="flex items-center gap-3 px-2 mb-8">
                                <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
                                    <img src="assets/img/logos (6).png" alt="SafeNode" class="h-5 w-auto">
                                </div>
                                <span class="font-bold text-sm">SafeNode</span>
                            </div>
                            
                            <div class="space-y-1">
                                <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-white/10 text-white text-sm font-medium border border-white/5">
                                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                                    Dashboard
                                </div>
                                <div class="flex items-center gap-3 px-3 py-2 rounded-lg text-zinc-500 hover:text-zinc-300 hover:bg-white/5 transition-colors text-sm font-medium cursor-pointer">
                                    <i data-lucide="globe" class="w-4 h-4"></i>
                                    Sites
                                </div>
                                <div class="flex items-center gap-3 px-3 py-2 rounded-lg text-zinc-500 hover:text-zinc-300 hover:bg-white/5 transition-colors text-sm font-medium cursor-pointer">
                                    <i data-lucide="shield-alert" class="w-4 h-4"></i>
                                    Logs de Segurança
                                </div>
                                <div class="flex items-center gap-3 px-3 py-2 rounded-lg text-zinc-500 hover:text-zinc-300 hover:bg-white/5 transition-colors text-sm font-medium cursor-pointer">
                                    <i data-lucide="ban" class="w-4 h-4"></i>
                                    IPs Bloqueados
                                </div>
                                <div class="flex items-center gap-3 px-3 py-2 rounded-lg text-zinc-500 hover:text-zinc-300 hover:bg-white/5 transition-colors text-sm font-medium cursor-pointer">
                                    <i data-lucide="settings" class="w-4 h-4"></i>
                                    Configurações
                                </div>
                            </div>

                            <div class="mt-auto">
                                <div class="p-3 rounded-xl bg-gradient-to-br from-zinc-900 to-black border border-zinc-800">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-[10px] text-zinc-500 uppercase font-bold">Carga do Sistema</span>
                                        <span class="text-xs text-green-400 font-mono">12%</span>
                                    </div>
                                    <div class="h-1 w-full bg-zinc-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-green-500 w-[12%]"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Main Area -->
                        <div class="flex-1 p-6 overflow-hidden flex flex-col gap-6 bg-black/40">
                            <!-- Stats Row -->
                            <div class="grid grid-cols-3 gap-4">
                                <div class="p-4 rounded-xl bg-zinc-900/40 border border-zinc-800/50 hover:border-zinc-700 transition-colors group">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-xs text-zinc-500 uppercase font-medium">Total de Requisições</div>
                                        <span class="text-[10px] text-green-400 bg-green-900/20 px-1.5 py-0.5 rounded border border-green-900/30 flex items-center gap-1">
                                            <i data-lucide="arrow-up-right" class="w-3 h-3"></i> 12%
                                        </span>
                                    </div>
                                    <div class="text-2xl font-bold text-white group-hover:text-green-400 transition-colors">2.4M</div>
                                    <div class="text-xs text-zinc-600 mt-1">Últimas 24 horas</div>
                                </div>
                                <div class="p-4 rounded-xl bg-zinc-900/40 border border-zinc-800/50 hover:border-zinc-700 transition-colors group">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-xs text-zinc-500 uppercase font-medium">Ameaças Mitigadas</div>
                                        <span class="text-[10px] text-red-400 bg-red-900/20 px-1.5 py-0.5 rounded border border-red-900/30 flex items-center gap-1">
                                            <i data-lucide="shield-alert" class="w-3 h-3"></i> 842
                                        </span>
                                    </div>
                                    <div class="text-2xl font-bold text-white group-hover:text-red-400 transition-colors">14.2k</div>
                                    <div class="text-xs text-zinc-600 mt-1">Bloqueado automaticamente por IA</div>
                                </div>
                                <div class="p-4 rounded-xl bg-zinc-900/40 border border-zinc-800/50 hover:border-zinc-700 transition-colors group">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-xs text-zinc-500 uppercase font-medium">Latência Global</div>
                                        <span class="text-[10px] text-blue-400 bg-blue-900/20 px-1.5 py-0.5 rounded border border-blue-900/30 flex items-center gap-1">
                                            <i data-lucide="zap" class="w-3 h-3"></i> -4ms
                                        </span>
                                    </div>
                                    <div class="text-2xl font-bold text-white group-hover:text-blue-400 transition-colors">34ms</div>
                                    <div class="text-xs text-zinc-600 mt-1">Tempo de Resposta P99</div>
                                </div>
                            </div>
                            
                            <!-- Visualization Area -->
                            <div class="flex-1 grid grid-cols-3 gap-6 min-h-0">
                                <!-- Map/Graph -->
                                <div class="col-span-2 rounded-xl bg-[#050505] border border-zinc-800/50 relative overflow-hidden flex flex-col">
                                    <div class="absolute inset-0 bg-[linear-gradient(to_right,#80808008_1px,transparent_1px),linear-gradient(to_bottom,#80808008_1px,transparent_1px)] bg-[size:24px_24px]"></div>
                                    
                                    <div class="p-4 border-b border-zinc-800/50 flex justify-between items-center relative z-10 bg-black/20 backdrop-blur-sm">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                                            <span class="text-xs font-medium text-zinc-300">Mapa de Tráfego em Tempo Real</span>
                                        </div>
                                        <div class="flex gap-2">
                                            <span class="text-[10px] px-2 py-1 rounded bg-zinc-800 text-zinc-400">1H</span>
                                            <span class="text-[10px] px-2 py-1 rounded bg-zinc-900 text-zinc-600 hover:text-zinc-400 cursor-pointer">24H</span>
                                        </div>
                                    </div>

                                    <div class="flex-1 relative flex items-center justify-center">
                                        <!-- Central Node -->
                                        <div class="relative z-10">
                                            <div class="w-16 h-16 rounded-full bg-zinc-900 border border-zinc-700 flex items-center justify-center shadow-[0_0_30px_rgba(16,185,129,0.1)]">
                                                <i data-lucide="server" class="w-6 h-6 text-green-500"></i>
                                            </div>
                                            <!-- Ripples -->
                                            <div class="absolute inset-0 -m-8 border border-green-500/10 rounded-full animate-[ping_3s_linear_infinite]"></div>
                                            <div class="absolute inset-0 -m-16 border border-green-500/5 rounded-full animate-[ping_3s_linear_infinite]" style="animation-delay: 1s"></div>
                                        </div>

                                        <!-- Incoming Traffic Particles -->
                                        <div class="absolute inset-0 overflow-hidden">
                                            <div class="absolute top-1/4 left-10 w-1 h-1 bg-white rounded-full animate-[traffic-flow_2s_linear_infinite]"></div>
                                            <div class="absolute bottom-1/3 right-10 w-1 h-1 bg-white rounded-full animate-[traffic-flow_3s_linear_infinite_reverse]"></div>
                                            <div class="absolute top-10 right-1/3 w-1 h-1 bg-red-500 rounded-full animate-[traffic-blocked_2s_linear_infinite]"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Live Logs -->
                                <div class="col-span-1 rounded-xl bg-zinc-950 border border-zinc-800/50 flex flex-col overflow-hidden">
                                    <div class="p-3 border-b border-zinc-800/50 bg-zinc-900/30">
                                        <span class="text-xs font-medium text-zinc-400">Registro de Eventos</span>
                                    </div>
                                    <div class="flex-1 p-3 space-y-3 overflow-hidden font-mono text-[10px]">
                                        <div class="flex gap-2 opacity-50">
                                            <span class="text-zinc-500">10:42:01</span>
                                            <span class="text-green-500">PERMITIR</span>
                                            <span class="text-zinc-400">192.168.1.42</span>
                                        </div>
                                        <div class="flex gap-2 opacity-70">
                                            <span class="text-zinc-500">10:42:03</span>
                                            <span class="text-green-500">PERMITIR</span>
                                            <span class="text-zinc-400">10.0.0.15</span>
                                        </div>
                                        <div class="flex gap-2">
                                            <span class="text-zinc-500">10:42:05</span>
                                            <span class="text-red-500">BLOQUEAR</span>
                                            <span class="text-zinc-400">Injeção SQL</span>
                                        </div>
                                        <div class="flex gap-2 opacity-80">
                                            <span class="text-zinc-500">10:42:08</span>
                                            <span class="text-green-500">PERMITIR</span>
                                            <span class="text-zinc-400">172.16.0.8</span>
                                        </div>
                                        <div class="flex gap-2 border-l-2 border-red-500 pl-2 bg-red-500/5 py-1">
                                            <span class="text-zinc-500">10:42:12</span>
                                            <span class="text-red-500 font-bold">MITIGADO</span>
                                            <span class="text-white">Ataque DDoS L7</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Proof -->
    <section class="py-16 border-y border-zinc-900 bg-black relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-black via-transparent to-black pointer-events-none z-10"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-xs text-zinc-500 mb-10 font-semibold uppercase tracking-[0.2em]">Confiado por empresas inovadoras</p>
            
            <div class="relative overflow-hidden">
                <!-- Desktop Carousel -->
                <div class="hidden md:block">
                    <div class="logos-carousel-infinite">
                        <div class="logos-track">
                            <!-- Primeira série de logos -->
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">ACME</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Globex</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Soylent</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Lactech</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Umbrella</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Nordpetro</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Denfy</span>
                                </div>
                            </div>
                            <!-- Duplicar para scroll infinito -->
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">ACME</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Globex</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Soylent</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Lactech</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Umbrella</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Nordpetro</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Denfy</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile Carousel -->
                <div class="md:hidden">
                    <div class="logos-carousel-infinite-mobile">
                        <div class="logos-track-mobile">
                            <!-- Primeira série de logos (mobile) -->
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">ACME</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Globex</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Soylent</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Lactech</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Umbrella</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Nordpetro</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Denfy</span>
                                </div>
                            </div>
                            <!-- Duplicar para scroll infinito (mobile) -->
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">ACME</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Globex</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Soylent</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Lactech</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Umbrella</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Nordpetro</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Denfy</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Architecture Visualization with better animations and depth -->
    <section class="py-32 bg-black relative overflow-hidden" x-data="{ inView: false }" x-intersect="inView = true">
        <!-- Added a subtle background gradient for depth -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-blue-900/10 via-black to-black pointer-events-none"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-20" :class="{ 'fade-in-up': inView }">
                <h2 class="text-3xl md:text-5xl font-bold mb-6 tracking-tight flex items-center justify-center gap-3 flex-wrap">
                    Como o <span class="flex items-center gap-2"><img src="assets/img/logos (6).png" alt="SafeNode" class="h-12 md:h-16 w-auto">SafeNode</span> protege você
                </h2>
                <p class="text-zinc-400 max-w-2xl mx-auto text-lg">Nossa arquitetura de proxy reverso intercepta todo o tráfego antes que ele chegue ao seu servidor.</p>
            </div>

            <div class="relative max-w-5xl mx-auto">
                <!-- Redesigned the flow to be a clean, organized linear process with explicit connecting lines </CHANGE> -->
                <div class="flex flex-col md:flex-row items-center justify-between relative z-10 gap-12 md:gap-0">
                    
                    <!-- Step 1: Visitors -->
                    <div class="flex flex-col items-center text-center group relative z-20 w-full md:w-auto" :class="{ 'fade-in-up': inView }" style="animation-delay: 0.1s">
                        <div class="w-24 h-24 rounded-2xl bg-zinc-900 border border-zinc-800 flex items-center justify-center mb-4 shadow-lg group-hover:border-zinc-600 transition-all duration-300 relative">
                            <i data-lucide="users" class="w-10 h-10 text-zinc-400 group-hover:text-white transition-colors"></i>
                            <!-- Badge -->
                            <div class="absolute -top-3 -right-3 bg-zinc-800 border border-zinc-700 text-zinc-400 text-[10px] px-2 py-1 rounded-full">
                                Internet
                            </div>
                        </div>
                        <h3 class="text-lg font-bold text-white">Visitantes</h3>
                        <p class="text-sm text-zinc-500 mt-1">Tráfego Misto</p>
                    </div>

                    <!-- Connector 1 (Visitors -> SafeNode) -->
                    <div class="hidden md:flex flex-1 items-center justify-center relative px-4 h-24">
                        <!-- Line -->
                        <div class="w-full h-[2px] bg-zinc-800 relative overflow-hidden rounded-full">
                            <!-- Moving particles (Red/Green mix representing mixed traffic) -->
                            <div class="absolute top-0 left-0 w-1/3 h-full bg-gradient-to-r from-transparent via-zinc-500 to-transparent animate-[traffic-flow_1.5s_linear_infinite]"></div>
                        </div>
                        <!-- Arrow Head -->
                        <i data-lucide="chevron-right" class="absolute right-4 text-zinc-600 w-6 h-6"></i>
                    </div>

                    <!-- Mobile Connector (Down Arrow) -->
                    <div class="md:hidden flex flex-col items-center justify-center h-16 -my-4">
                        <div class="h-full w-[2px] bg-zinc-800 relative overflow-hidden">
                             <div class="absolute top-0 left-0 w-full h-1/3 bg-gradient-to-b from-transparent via-zinc-500 to-transparent animate-[scan_1.5s_linear_infinite]"></div>
                        </div>
                        <i data-lucide="chevron-down" class="text-zinc-600 w-6 h-6 -mt-1"></i>
                    </div>

                    <!-- Step 2: SafeNode Edge (Centerpiece) -->
                    <div class="flex flex-col items-center text-center relative z-20 w-full md:w-auto" :class="{ 'fade-in-up': inView }" style="animation-delay: 0.3s">
                        <!-- Glow effect behind -->
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-40 h-40 bg-blue-500/20 rounded-full blur-3xl animate-pulse-slow"></div>
                        
                        <div class="w-32 h-32 rounded-full bg-black border-2 border-blue-500/50 flex items-center justify-center mb-4 shadow-[0_0_30px_rgba(59,130,246,0.3)] relative z-10">
                            <!-- Rotating ring -->
                            <div class="absolute inset-1 rounded-full border border-dashed border-blue-500/30 animate-[spin_10s_linear_infinite]"></div>
                            
                            <div class="bg-zinc-900 p-4 rounded-xl border border-zinc-800">
                                <img src="assets/img/logos (6).png" alt="SafeNode" class="w-12 h-12 object-contain">
                            </div>

                            <!-- Shield Badge -->
                            <div class="absolute -bottom-2 bg-blue-600 text-white text-[10px] font-bold px-3 py-1 rounded-full shadow-lg border border-blue-400">
                                FILTERING
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-white mt-2">SafeNode Edge</h3>
                        <p class="text-sm text-blue-400 mt-1">Ameaças Bloqueadas</p>
                    </div>

                    <!-- Connector 2 (SafeNode -> Server) -->
                    <div class="hidden md:flex flex-1 items-center justify-center relative px-4 h-24">
                        <!-- Line -->
                        <div class="w-full h-[2px] bg-zinc-800 relative overflow-hidden rounded-full">
                            <!-- Moving particles (Green only representing clean traffic) -->
                            <div class="absolute top-0 left-0 w-1/3 h-full bg-gradient-to-r from-transparent via-green-500 to-transparent animate-[traffic-flow_1.5s_linear_infinite]"></div>
                        </div>
                        <!-- Arrow Head -->
                        <i data-lucide="chevron-right" class="absolute right-4 text-green-500 w-6 h-6"></i>
                    </div>

                    <!-- Mobile Connector (Down Arrow) -->
                    <div class="md:hidden flex flex-col items-center justify-center h-16 -my-4">
                        <div class="h-full w-[2px] bg-zinc-800 relative overflow-hidden">
                             <div class="absolute top-0 left-0 w-full h-1/3 bg-gradient-to-b from-transparent via-green-500 to-transparent animate-[scan_1.5s_linear_infinite]"></div>
                        </div>
                        <i data-lucide="chevron-down" class="text-green-500 w-6 h-6 -mt-1"></i>
                    </div>

                    <!-- Step 3: Your Server -->
                    <div class="flex flex-col items-center text-center group relative z-20 w-full md:w-auto" :class="{ 'fade-in-up': inView }" style="animation-delay: 0.5s">
                        <div class="w-24 h-24 rounded-2xl bg-zinc-900 border border-zinc-800 flex items-center justify-center mb-4 shadow-lg group-hover:border-green-500/50 transition-all duration-300 relative">
                            <i data-lucide="server" class="w-10 h-10 text-zinc-400 group-hover:text-green-500 transition-colors"></i>
                            <!-- Badge -->
                            <div class="absolute -top-3 -right-3 bg-green-900/20 border border-green-900/50 text-green-400 text-[10px] px-2 py-1 rounded-full flex items-center gap-1">
                                <i data-lucide="check" class="w-3 h-3"></i> Clean
                            </div>
                        </div>
                        <h3 class="text-lg font-bold text-white">Seu Servidor</h3>
                        <p class="text-sm text-zinc-500 mt-1">Seguro & Otimizado</p>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- Features (Bento Grid) -->
    <section id="features" class="py-24 bg-black relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-5xl font-bold mb-4">Segurança em camadas.</h2>
                <p class="text-zinc-400 max-w-2xl mx-auto">Uma plataforma unificada para proteger, acelerar e construir na web.</p>
                <div class="mt-6 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-zinc-900/50 border border-zinc-800 backdrop-blur-sm">
                    <i data-lucide="link" class="w-4 h-4 text-blue-400 self-center"></i>
                    <span class="text-sm text-zinc-300 inline-flex items-center gap-1.5">Integração nativa com <span class="text-white font-semibold inline-flex items-center gap-1.5"><img src="assets/img/cloudflare_icon_130969-removebg-preview.png" alt="Cloudflare" class="w-4 h-4 object-contain self-center">Cloudflare</span> para proteção em camadas</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Large Card -->
                <div class="md:col-span-2 glass-card rounded-3xl p-8 hover:border-zinc-600 transition-colors group relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i data-lucide="globe" class="w-64 h-64 text-white"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="w-12 h-12 bg-zinc-800 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform border border-zinc-700">
                            <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-3">Proteção DDoS Global</h3>
                        <p class="text-zinc-400 max-w-md">Mitigação instantânea de ataques em qualquer camada. Nossa rede de 100Tbps absorve as maiores ameaças sem afetar sua performance.</p>
                        
                        <div class="mt-8 flex gap-2">
                            <span class="px-3 py-1 rounded-full bg-zinc-800/50 text-xs text-zinc-300 border border-zinc-700 backdrop-blur-sm">L3/L4</span>
                            <span class="px-3 py-1 rounded-full bg-zinc-800/50 text-xs text-zinc-300 border border-zinc-700 backdrop-blur-sm">L7 Application</span>
                        </div>
                    </div>
                </div>

                <!-- Tall Card -->
                <div class="glass-card rounded-3xl p-8 hover:border-zinc-600 transition-colors group">
                    <div class="w-12 h-12 bg-zinc-800 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform border border-zinc-700">
                        <i data-lucide="zap" class="w-6 h-6 text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-3">Edge Compute</h3>
                    <p class="text-zinc-400 mb-6">Execute código em milissegundos de seus usuários. Sem servidores para gerenciar.</p>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-sm text-zinc-300">
                            <div class="w-5 h-5 rounded-full bg-green-500/20 flex items-center justify-center">
                                <i data-lucide="check" class="w-3 h-3 text-green-500"></i>
                            </div>
                            0ms Cold Starts
                        </div>
                        <div class="flex items-center gap-3 text-sm text-zinc-300">
                            <div class="w-5 h-5 rounded-full bg-green-500/20 flex items-center justify-center">
                                <i data-lucide="check" class="w-3 h-3 text-green-500"></i>
                            </div>
                            V8 Isolate
                        </div>
                        <div class="flex items-center gap-3 text-sm text-zinc-300">
                            <div class="w-5 h-5 rounded-full bg-green-500/20 flex items-center justify-center">
                                <i data-lucide="check" class="w-3 h-3 text-green-500"></i>
                            </div>
                            Global Deploy
                        </div>
                    </div>
                </div>

                <!-- Wide Card -->
                <div class="md:col-span-3 glass-card rounded-3xl p-8 hover:border-zinc-600 transition-colors flex flex-col md:flex-row items-center gap-8 group">
                    <div class="flex-1">
                        <div class="w-12 h-12 bg-zinc-800 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform border border-zinc-700">
                            <i data-lucide="lock" class="w-6 h-6 text-white"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-3">Zero Trust Access</h3>
                        <p class="text-zinc-400">Substitua VPNs corporativas por políticas de acesso baseadas em identidade. Conecte usuários a recursos privados de forma segura e rápida.</p>
                    </div>
                    <div class="flex-1 w-full bg-black/50 rounded-xl border border-zinc-800 p-4 backdrop-blur-md">
                        <div class="flex items-center gap-2 mb-4 border-b border-zinc-800 pb-2">
                            <div class="w-2 h-2 rounded-full bg-red-500"></div>
                            <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                            <span class="text-xs text-zinc-500 ml-2">Access Policy</span>
                        </div>
                        <div class="space-y-2 font-mono text-xs">
                            <div class="flex justify-between text-zinc-400 p-2 hover:bg-zinc-800/50 rounded transition-colors">
                                <span>Rule: <span class="text-white">Engineering Team</span></span>
                                <span class="text-green-400 bg-green-900/20 px-2 py-0.5 rounded border border-green-900/30">Allow</span>
                            </div>
                            <div class="flex justify-between text-zinc-400 p-2 hover:bg-zinc-800/50 rounded transition-colors">
                                <span>Auth: <span class="text-white">SSO / MFA</span></span>
                                <span class="text-green-400 bg-green-900/20 px-2 py-0.5 rounded border border-green-900/30">Verified</span>
                            </div>
                            <div class="flex justify-between text-zinc-400 p-2 hover:bg-zinc-800/50 rounded transition-colors">
                                <span>Device: <span class="text-white">Managed</span></span>
                                <span class="text-green-400 bg-green-900/20 px-2 py-0.5 rounded border border-green-900/30">Compliant</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Developer Experience Section with Code Block -->
    <section class="py-24 bg-black relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-zinc-900 border border-zinc-800 mb-6">
                        <i data-lucide="code-2" class="w-4 h-4 text-white"></i>
                        <span class="text-xs font-medium text-zinc-300">Developer First</span>
                    </div>
                    <h2 class="text-3xl md:text-5xl font-bold mb-6 tracking-tight">Integração em minutos, <br/>não meses.</h2>
                    <p class="text-zinc-400 text-lg mb-8 leading-relaxed">
                        Nossa API robusta e CLI intuitiva permitem que você configure regras de firewall, gerencie cache e implante edge functions diretamente do seu fluxo de trabalho existente. Integração nativa com <span class="text-white font-semibold inline-flex items-center gap-1.5"><img src="assets/img/cloudflare_icon_130969-removebg-preview.png" alt="Cloudflare" class="w-4 h-4 object-contain self-center">Cloudflare</span> para sincronização automática de bloqueios e regras de segurança.
                    </p>
                    
                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-zinc-900 flex items-center justify-center border border-zinc-800 shrink-0">
                                <span class="font-bold text-white">1</span>
                            </div>
                            <div>
                                <h4 class="text-white font-bold mb-1">Instale o CLI</h4>
                                <p class="text-zinc-500 text-sm">Disponível via npm, brew ou curl.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-zinc-900 flex items-center justify-center border border-zinc-800 shrink-0">
                                <span class="font-bold text-white">2</span>
                            </div>
                            <div>
                                <h4 class="text-white font-bold mb-1">Autentique seu projeto</h4>
                                <p class="text-zinc-500 text-sm">Login seguro via browser ou token.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-zinc-900 flex items-center justify-center border border-zinc-800 shrink-0">
                                <span class="font-bold text-white">3</span>
                            </div>
                            <div>
                                <h4 class="text-white font-bold mb-1">Deploy Global</h4>
                                <p class="text-zinc-500 text-sm">Suas configurações propagadas em segundos.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Code Window -->
                <div class="relative group">
                    <div class="absolute -inset-1 bg-gradient-to-r from-zinc-700 to-zinc-800 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
                    <div class="relative rounded-2xl bg-[#0d1117] border border-zinc-800 shadow-2xl overflow-hidden">
                        <div class="flex items-center px-4 py-3 border-b border-zinc-800 bg-zinc-900/50">
                            <div class="flex space-x-2">
                                <div class="w-3 h-3 rounded-full bg-red-500/20 border border-red-500/50"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500/20 border border-yellow-500/50"></div>
                                <div class="w-3 h-3 rounded-full bg-green-500/20 border border-green-500/50"></div>
                            </div>
                            <div class="ml-4 text-xs text-zinc-500 font-mono">safenode-config.js</div>
                        </div>
                        <div class="p-6 overflow-x-auto">
                            <pre class="font-mono text-sm leading-relaxed"><code><span class="code-syntax-keyword">import</span> { SafeNode } <span class="code-syntax-keyword">from</span> <span class="code-syntax-string">'@safenode/sdk'</span>;

<span class="code-syntax-keyword">const</span> client = <span class="code-syntax-keyword">new</span> SafeNode(process.env.API_KEY);

<span class="code-syntax-comment">// Configurar regras de firewall</span>
<span class="code-syntax-keyword">await</span> client.firewall.create({
  name: <span class="code-syntax-string">'Block Suspicious Traffic'</span>,
  rules: [
    {
      action: <span class="code-syntax-string">'block'</span>,
      target: <span class="code-syntax-string">'ip.reputation'</span>,
      operator: <span class="code-syntax-string">'gt'</span>,
      value: <span class="text-zinc-300">80</span>
    }
  ]
});

<span class="code-syntax-function">console</span>.log(<span class="code-syntax-string">'Security rules deployed globally 🚀'</span>);</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-20 border-y border-zinc-900 bg-black">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center divide-y md:divide-y-0 md:divide-x divide-zinc-800">
                <div class="p-4">
                    <div class="text-4xl md:text-5xl font-bold text-white mb-2">120ms</div>
                    <div class="text-zinc-500 uppercase tracking-wider text-sm">Latência Global Média</div>
                </div>
                <div class="p-4">
                    <div class="text-4xl md:text-5xl font-bold text-white mb-2">50M+</div>
                    <div class="text-zinc-500 uppercase tracking-wider text-sm">Ameaças Bloqueadas/Dia</div>
                </div>
                <div class="p-4">
                    <div class="text-4xl md:text-5xl font-bold text-white mb-2">99.99%</div>
                    <div class="text-zinc-500 uppercase tracking-wider text-sm">Uptime Garantido</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="py-24 bg-black">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-5xl font-bold mb-4">Preços transparentes.</h2>
                <p class="text-zinc-400">Comece pequeno, escale globalmente.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Free -->
                <div class="bg-zinc-900/10 border border-zinc-800 rounded-3xl p-8 flex flex-col">
                    <div class="mb-4">
                        <span class="text-lg font-medium text-zinc-300">Hobby</span>
                        <div class="text-4xl font-bold mt-2 text-white">R$0<span class="text-lg font-normal text-zinc-500">/mês</span></div>
                    </div>
                    <p class="text-zinc-400 text-sm mb-8">Para projetos pessoais e testes.</p>
                    <ul class="space-y-4 mb-8 flex-1">
                        <li class="flex items-center gap-3 text-sm text-zinc-300"><i data-lucide="check" class="w-4 h-4 text-white"></i> Proteção DDoS Básica</li>
                        <li class="flex items-center gap-3 text-sm text-zinc-300"><i data-lucide="check" class="w-4 h-4 text-white"></i> CDN Global</li>
                        <li class="flex items-center gap-3 text-sm text-zinc-300"><i data-lucide="check" class="w-4 h-4 text-white"></i> SSL Gratuito</li>
                    </ul>
                    <?php if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true): ?>
                        <a href="checkout.php?plan=hobby" class="w-full py-3 rounded-full border border-zinc-700 text-white font-medium hover:bg-zinc-800 transition-colors text-center">Ativar Plano</a>
                    <?php else: ?>
                        <a href="register.php" class="w-full py-3 rounded-full border border-zinc-700 text-white font-medium hover:bg-zinc-800 transition-colors text-center">Começar</a>
                    <?php endif; ?>
                </div>

                <!-- Pro (Featured) -->
                <div class="bg-zinc-900/30 border border-white/20 rounded-3xl p-8 flex flex-col relative transform md:-translate-y-4 shadow-2xl shadow-white/5">
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white text-black px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wide">Mais Popular</div>
                    <div class="mb-4">
                        <span class="text-lg font-medium text-white">Pro</span>
                        <div class="text-4xl font-bold mt-2 text-white">R$99<span class="text-lg font-normal text-zinc-500">/mês</span></div>
                    </div>
                    <p class="text-zinc-400 text-sm mb-8">Para aplicações em produção.</p>
                    <ul class="space-y-4 mb-8 flex-1">
                        <li class="flex items-center gap-3 text-sm text-white"><i data-lucide="check" class="w-4 h-4 text-white"></i> Tudo do Hobby</li>
                        <li class="flex items-center gap-3 text-sm text-white"><i data-lucide="check" class="w-4 h-4 text-white"></i> WAF Avançado</li>
                        <li class="flex items-center gap-3 text-sm text-white"><i data-lucide="check" class="w-4 h-4 text-white"></i> Otimização de Imagens</li>
                        <li class="flex items-center gap-3 text-sm text-white"><i data-lucide="check" class="w-4 h-4 text-white"></i> Analytics em Tempo Real</li>
                    </ul>
                    <?php if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true): ?>
                        <a href="checkout.php?plan=pro" class="w-full py-3 rounded-full bg-white text-black font-bold hover:bg-zinc-200 transition-colors text-center">Assinar Pro</a>
                    <?php else: ?>
                        <a href="register.php?plan=pro" class="w-full py-3 rounded-full bg-white text-black font-bold hover:bg-zinc-200 transition-colors text-center">Assinar Pro</a>
                    <?php endif; ?>
                </div>

                <!-- Enterprise -->
                <div class="bg-zinc-900/10 border border-zinc-800 rounded-3xl p-8 flex flex-col">
                    <div class="mb-4">
                        <span class="text-lg font-medium text-zinc-300">Enterprise</span>
                        <div class="text-4xl font-bold mt-2 text-white">Custom</div>
                    </div>
                    <p class="text-zinc-400 text-sm mb-8">Para missão crítica e escala massiva.</p>
                    <ul class="space-y-4 mb-8 flex-1">
                        <li class="flex items-center gap-3 text-sm text-zinc-300"><i data-lucide="check" class="w-4 h-4 text-white"></i> SLA de 100%</li>
                        <li class="flex items-center gap-3 text-sm text-zinc-300"><i data-lucide="check" class="w-4 h-4 text-white"></i> Suporte 24/7 Dedicado</li>
                        <li class="flex items-center gap-3 text-sm text-zinc-300"><i data-lucide="check" class="w-4 h-4 text-white"></i> Logs Raw</li>
                        <li class="flex items-center gap-3 text-sm text-zinc-300"><i data-lucide="check" class="w-4 h-4 text-white"></i> Single Sign-On (SSO)</li>
                    </ul>
                    <a href="#" class="w-full py-3 rounded-full border border-zinc-700 text-white font-medium hover:bg-zinc-800 transition-colors text-center">Falar com Vendas</a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-24 bg-black border-t border-zinc-900">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold mb-12 text-center">Perguntas Frequentes</h2>
            
            <div class="space-y-4" x-data="{ active: null }">
                <!-- FAQ Item 1 -->
                <div class="border border-zinc-800 rounded-2xl bg-zinc-900/20 overflow-hidden">
                    <button @click="active = active === 1 ? null : 1" class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none">
                        <span class="font-medium text-zinc-200">Como funciona a proteção DDoS?</span>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-zinc-500 transition-transform duration-300" :class="{ 'rotate-180': active === 1 }"></i>
                    </button>
                    <div x-show="active === 1" x-collapse class="px-6 pb-4 text-zinc-400 text-sm leading-relaxed">
                        Nossa rede global analisa o tráfego em tempo real e filtra requisições maliciosas na borda, antes que elas atinjam seu servidor. Utilize machine learning para identificar padrões de ataque complexos.
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="border border-zinc-800 rounded-2xl bg-zinc-900/20 overflow-hidden">
                    <button @click="active = active === 2 ? null : 2" class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none">
                        <span class="font-medium text-zinc-200">Posso usar com qualquer provedor de hospedagem?</span>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-zinc-500 transition-transform duration-300" :class="{ 'rotate-180': active === 2 }"></i>
                    </button>
                    <div x-show="active === 2" x-collapse class="px-6 pb-4 text-zinc-400 text-sm leading-relaxed">
                        Sim! O SafeNode funciona como um proxy reverso. Você só precisa alterar seus apontamentos DNS para nossa rede, e nós cuidamos do resto, independente de onde seu servidor esteja (AWS, DigitalOcean, On-premise, etc).
                    </div>
                </div>

                <!-- FAQ Item 3 -->
                <div class="border border-zinc-800 rounded-2xl bg-zinc-900/20 overflow-hidden">
                    <button @click="active = active === 3 ? null : 3" class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none">
                        <span class="font-medium text-zinc-200">O SSL é realmente gratuito?</span>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-zinc-500 transition-transform duration-300" :class="{ 'rotate-180': active === 3 }"></i>
                    </button>
                    <div x-show="active === 3" x-collapse class="px-6 pb-4 text-zinc-400 text-sm leading-relaxed">
                        Sim, emitimos e renovamos automaticamente certificados SSL universais para todos os domínios ativos em nossa plataforma, garantindo criptografia de ponta a ponta sem custo extra.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Final -->
    <section class="py-24 relative overflow-hidden">
        <div class="absolute inset-0 bg-zinc-900/50"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-black via-transparent to-black"></div>
        
        <div class="relative z-10 max-w-4xl mx-auto px-4 text-center">
            <h2 class="text-4xl md:text-5xl font-bold mb-6 tracking-tight">Pronto para proteger sua infraestrutura?</h2>
            <p class="text-xl text-zinc-400 mb-10 flex items-center justify-center gap-2 flex-wrap">
                Junte-se a milhares de desenvolvedores que confiam no <span class="flex items-center gap-2"><img src="assets/img/logos (6).png" alt="SafeNode" class="h-6 w-auto">SafeNode</span>.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="register.php" class="px-8 py-4 bg-white text-black rounded-full font-bold hover:bg-zinc-200 transition-all transform hover:scale-105">Criar Conta Grátis</a>
                <a href="#" class="px-8 py-4 bg-transparent border border-zinc-700 text-white rounded-full font-medium hover:bg-zinc-900 transition-all">Falar com Especialista</a>
            </div>
        </div>
    </section>

    <!-- Redesigned professional footer with better organization -->
    <footer class="bg-black border-t border-zinc-900 pt-20 pb-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Newsletter Section -->
            <div class="mb-16 pb-16 border-b border-zinc-900">
                <div class="max-w-2xl mx-auto text-center">
                    <h3 class="text-2xl md:text-3xl font-bold mb-3">Fique por dentro das novidades</h3>
                    <p class="text-zinc-400 mb-8">Receba atualizações sobre novos recursos, dicas de segurança e insights do setor.</p>
                    <form class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
                        <input 
                            type="email" 
                            placeholder="seu@email.com" 
                            class="flex-1 px-5 py-3 bg-zinc-900 border border-zinc-800 rounded-full text-white placeholder:text-zinc-500 focus:outline-none focus:border-zinc-600 transition-colors"
                        >
                        <button 
                            type="submit" 
                            class="px-8 py-3 bg-white text-black rounded-full font-semibold hover:bg-zinc-100 transition-all hover:shadow-[0_0_20px_rgba(255,255,255,0.2)] whitespace-nowrap"
                        >
                            Inscrever-se
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Main Footer Content -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-12 mb-16">
                <!-- Brand Column (Spans 2 columns) -->
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-3 mb-6 group cursor-pointer">
                        <img src="assets/img/logos (6).png" alt="SafeNode" class="h-10 w-auto group-hover:scale-110 transition-transform">
                        <span class="font-bold text-xl">SafeNode</span>
                    </div>
                    <p class="text-zinc-400 text-sm leading-relaxed mb-6 max-w-sm">
                        Tornando a internet mais segura, rápida e confiável para todos. Protegendo milhões de aplicações em todo o mundo.
                    </p>
                    <div class="flex gap-3">
                        <a href="#" class="w-10 h-10 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 hover:text-white hover:border-zinc-600 hover:bg-zinc-800 transition-all">
                            <i data-lucide="twitter" class="w-4 h-4"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 hover:text-white hover:border-zinc-600 hover:bg-zinc-800 transition-all">
                            <i data-lucide="github" class="w-4 h-4"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 hover:text-white hover:border-zinc-600 hover:bg-zinc-800 transition-all">
                            <i data-lucide="linkedin" class="w-4 h-4"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 hover:text-white hover:border-zinc-600 hover:bg-zinc-800 transition-all">
                            <i data-lucide="youtube" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Product Column -->
                <div>
                    <h4 class="font-semibold text-white mb-5 text-sm uppercase tracking-wider">Produto</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="shield" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            DDoS Protection
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="shield" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            WAF
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="shield" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            CDN
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="shield" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Edge Compute
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="shield" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Zero Trust
                        </a></li>
                    </ul>
                </div>
                
                <!-- Developers Column -->
                <div>
                    <h4 class="font-semibold text-white mb-5 text-sm uppercase tracking-wider">Desenvolvedores</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="book-open" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Documentação
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="book-open" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            API Reference
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="book-open" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            CLI
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="book-open" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            SDKs
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="book-open" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Status
                        </a></li>
                    </ul>
                </div>
                
                <!-- Company Column -->
                <div>
                    <h4 class="font-semibold text-white mb-5 text-sm uppercase tracking-wider">Empresa</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="building" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Sobre
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="building" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Carreiras
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="building" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Blog
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="building" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Imprensa
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="building" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Contato
                        </a></li>
                    </ul>
                </div>
                
                <!-- Resources Column -->
                <div>
                    <h4 class="font-semibold text-white mb-5 text-sm uppercase tracking-wider">Recursos</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="users" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Comunidade
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="users" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Suporte
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="users" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Webinars
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="users" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Case Studies
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="users" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Whitepapers
                        </a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Bottom Bar -->
            <div class="border-t border-zinc-900 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex flex-col md:flex-row items-center gap-4 text-sm text-zinc-600">
                    <p class="flex items-center gap-2">© 2025 <span class="flex items-center gap-1.5"><img src="assets/img/logos (6).png" alt="SafeNode" class="h-5 w-auto">SafeNode</span>. Todos os direitos reservados.</p>
                    <div class="flex gap-6">
                        <a href="#" class="hover:text-zinc-400 transition-colors">Privacidade</a>
                        <a href="#" class="hover:text-zinc-400 transition-colors">Termos</a>
                        <a href="#" class="hover:text-zinc-400 transition-colors">Cookies</a>
                        <a href="#" class="hover:text-zinc-400 transition-colors">Segurança</a>
                        <a href="admin-emails.php" class="hover:text-zinc-400 transition-colors opacity-50 hover:opacity-100">Admin</a>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-xs text-zinc-600">
                    <i data-lucide="globe" class="w-4 h-4"></i>
                    <select class="bg-transparent border border-zinc-800 rounded-lg px-3 py-1.5 text-zinc-400 hover:border-zinc-600 transition-colors focus:outline-none focus:border-zinc-500 cursor-pointer">
                        <option>Português (BR)</option>
                        <option>English (US)</option>
                        <option>Español</option>
                    </select>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <div x-data="{ show: false }" 
         @scroll.window="show = (window.pageYOffset > 300)" 
         class="fixed bottom-8 right-8 z-50">
        <button 
            x-show="show" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            @click="window.scrollTo({top: 0, behavior: 'smooth'})" 
            class="bg-white text-black p-3 rounded-full shadow-lg hover:bg-zinc-200 transition-colors focus:outline-none"
        >
            <i data-lucide="arrow-up" class="w-5 h-5"></i>
        </button>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
    </script>
</body>
</html>
