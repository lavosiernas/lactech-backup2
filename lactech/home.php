<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KRON - Kernel for Resilient Operating Nodes</title>
    <meta name="description" content="Construimos software que resolve problemas reais. SafeNode para seguranca web. LacTech para gestao rural.">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                    },
                    letterSpacing: {
                        'tightest': '-0.04em',
                        'tighter': '-0.02em',
                    },
                    transitionTimingFunction: {
                        'apple': 'cubic-bezier(0.25, 0.1, 0.25, 1)',
                        'apple-bounce': 'cubic-bezier(0.34, 1.56, 0.64, 1)',
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                    }
                }
            }
        }
    </script>
    
    <style>
        * {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }
        
        ::selection {
            background: rgba(255, 255, 255, 0.99);
            color: #000;
        }
        
        html {
            scroll-behavior: smooth;
            background: #000;
        }
        
        body {
            background: #000;
            color: #f5f5f7;
            overflow-x: hidden;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Text gradient */
        .text-gradient {
            background: linear-gradient(180deg, #ffffff 0%, rgba(255, 255, 255, 0.7) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .text-gradient-shine {
            background: linear-gradient(90deg, #ffffff 0%, rgba(255, 255, 255, 0.5) 50%, #ffffff 100%);
            background-size: 200% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shine 3s ease-in-out infinite;
        }
        
        @keyframes shine {
            0%, 100% { background-position: 200% center; }
            50% { background-position: 0% center; }
        }
        
        /* Floating animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }
        
        /* Glow animation */
        @keyframes glow {
            from { box-shadow: 0 0 20px rgba(255, 255, 255, 0.1); }
            to { box-shadow: 0 0 40px rgba(255, 255, 255, 0.2); }
        }
        
        /* Reveal animations */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 1s cubic-bezier(0.25, 0.1, 0.25, 1), 
                        transform 1s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }
        
        .reveal-left {
            opacity: 0;
            transform: translateX(-60px);
            transition: opacity 1s cubic-bezier(0.25, 0.1, 0.25, 1), 
                        transform 1s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        .reveal-left.active {
            opacity: 1;
            transform: translateX(0);
        }
        
        .reveal-right {
            opacity: 0;
            transform: translateX(60px);
            transition: opacity 1s cubic-bezier(0.25, 0.1, 0.25, 1), 
                        transform 1s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        .reveal-right.active {
            opacity: 1;
            transform: translateX(0);
        }
        
        .reveal-scale {
            opacity: 0;
            transform: scale(0.9);
            transition: opacity 0.8s cubic-bezier(0.25, 0.1, 0.25, 1), 
                        transform 0.8s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        .reveal-scale.active {
            opacity: 1;
            transform: scale(1);
        }
        
        /* Stagger children */
        .stagger-children > * {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s cubic-bezier(0.25, 0.1, 0.25, 1), 
                        transform 0.6s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        .stagger-children.active > *:nth-child(1) { transition-delay: 0ms; opacity: 1; transform: translateY(0); }
        .stagger-children.active > *:nth-child(2) { transition-delay: 100ms; opacity: 1; transform: translateY(0); }
        .stagger-children.active > *:nth-child(3) { transition-delay: 200ms; opacity: 1; transform: translateY(0); }
        .stagger-children.active > *:nth-child(4) { transition-delay: 300ms; opacity: 1; transform: translateY(0); }
        .stagger-children.active > *:nth-child(5) { transition-delay: 400ms; opacity: 1; transform: translateY(0); }
        .stagger-children.active > *:nth-child(6) { transition-delay: 500ms; opacity: 1; transform: translateY(0); }
        
        /* Glass morphism */
        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        
        .glass-card {
            background: linear-gradient(
                135deg,
                rgba(255, 255, 255, 0.08) 0%,
                rgba(255, 255, 255, 0.02) 50%,
                rgba(255, 255, 255, 0.05) 100%
            );
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        }
        
        /* 3D Card hover effect */
        .card-3d {
            transition: transform 0.6s cubic-bezier(0.25, 0.1, 0.25, 1),
                        box-shadow 0.6s cubic-bezier(0.25, 0.1, 0.25, 1);
            transform-style: preserve-3d;
            perspective: 1000px;
        }
        
        .card-3d:hover {
            transform: translateY(-12px) rotateX(2deg) rotateY(-2deg);
            box-shadow: 
                0 40px 80px -20px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 255, 255, 0.1),
                0 0 60px -10px rgba(255, 255, 255, 0.1);
        }
        
        .card-3d:hover .card-icon {
            transform: scale(1.15) translateZ(20px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .card-3d:hover .card-arrow {
            transform: translate(6px, -6px);
            opacity: 1;
        }
        
        .card-3d:hover .card-shine {
            opacity: 1;
            transform: translateX(100%);
        }
        
        .card-shine {
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            opacity: 0;
            transition: transform 0.8s, opacity 0.3s;
            pointer-events: none;
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(180deg, #ffffff 0%, #e8e8ed 100%);
            color: #0a0a0a;
            box-shadow: 
                0 2px 4px rgba(0, 0, 0, 0.3),
                0 8px 16px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
            transition: all 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: transform 0.6s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 
                0 4px 8px rgba(0, 0, 0, 0.4),
                0 16px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
        }
        
        .btn-primary:hover::before {
            transform: translateX(200%);
        }
        
        .btn-primary:active {
            transform: translateY(0) scale(0.98);
        }
        
        .btn-outline {
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: transparent;
            transition: all 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-outline::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.1);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        
        .btn-outline:hover {
            border-color: rgba(255, 255, 255, 0.4);
        }
        
        .btn-outline:hover::before {
            transform: scaleX(1);
        }
        
        /* Link underline animation */
        .link-hover {
            position: relative;
        }
        .link-hover::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: currentColor;
            transition: width 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        .link-hover:hover::after {
            width: 100%;
        }
        
        /* Magnetic button effect */
        .magnetic {
            transition: transform 0.3s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        
        /* Marquee */
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .marquee-track {
            animation: marquee 35s linear infinite;
        }
        .marquee-track:hover {
            animation-play-state: paused;
        }
        
        /* Feature item hover */
        .feature-item {
            transition: all 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
            position: relative;
        }
        
        .feature-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, transparent, rgba(255,255,255,0.3), transparent);
            transform: scaleY(0);
            transition: transform 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        
        .feature-item:hover {
            background: rgba(255, 255, 255, 0.02);
            padding-left: 1.5rem;
        }
        
        .feature-item:hover::before {
            transform: scaleY(1);
        }
        
        .feature-item:hover .feature-number {
            color: rgba(255, 255, 255, 0.6);
            transform: scale(1.1);
        }
        
        .feature-item:hover .feature-icon {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 0.1);
        }
        
        /* Hero backgrounds */
        .hero-gradient {
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(ellipse 100% 80% at 50% -30%, rgba(100, 100, 120, 0.15), transparent 70%),
                radial-gradient(ellipse 80% 60% at 80% 60%, rgba(60, 60, 80, 0.1), transparent 60%),
                radial-gradient(ellipse 60% 40% at 20% 80%, rgba(80, 80, 100, 0.08), transparent 50%);
        }
        
        .hero-image {
            position: absolute;
            inset: 0;
            background-image: url('https://i.postimg.cc/s2HP9BH0/emailotp-(19).jpg');
            background-size: cover;
            background-position: center;
            opacity: 0.2;
            filter: brightness(0.7) contrast(1.1) saturate(0.9);
        }
        
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                180deg,
                rgba(0, 0, 0, 0.2) 0%,
                rgba(0, 0, 0, 0.4) 40%,
                rgba(0, 0, 0, 0.85) 80%,
                rgba(0, 0, 0, 1) 100%
            );
        }
        
        /* Particles */
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            pointer-events: none;
        }
        
        @keyframes particle-float {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) translateX(50px); opacity: 0; }
        }
        
        /* Nav states */
        nav {
            transition: all 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        
        nav.scrolled {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: saturate(180%) blur(20px);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        /* Mobile menu */
        .mobile-menu {
            clip-path: circle(0% at calc(100% - 2rem) 2rem);
            transition: clip-path 0.6s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        
        .mobile-menu.active {
            clip-path: circle(150% at calc(100% - 2rem) 2rem);
        }
        
        /* Scroll indicator */
        @keyframes scroll-bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(8px); }
        }
        
        .scroll-indicator {
            animation: scroll-bounce 2s ease-in-out infinite;
        }
        
        /* Pulse line */
        @keyframes pulse-line {
            0%, 100% { transform: scaleX(0.3); opacity: 0.3; }
            50% { transform: scaleX(1); opacity: 1; }
        }
        
        .pulse-line {
            animation: pulse-line 2s cubic-bezier(0.25, 0.1, 0.25, 1) infinite;
            transform-origin: left;
        }
        
        /* Stats counter */
        .stat-number {
            font-feature-settings: 'tnum' on, 'lnum' on;
        }
        
        /* Social icons */
        .social-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.02);
            transition: all 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        
        .social-icon:hover {
            background: #ffffff;
            border-color: #ffffff;
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 10px 30px rgba(255, 255, 255, 0.2);
        }
        
        .social-icon:hover svg {
            stroke: #0a0a0a;
        }
        
        /* Noise texture */
        .noise::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
            opacity: 0.015;
            pointer-events: none;
            z-index: 9998;
        }
        
        /* Grid lines background */
        .grid-bg {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 80px 80px;
            mask-image: radial-gradient(ellipse 80% 50% at 50% 50%, black, transparent);
        }
        
        /* Scrollbar hide */
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        
        /* Cursor glow effect */
        .cursor-glow {
            position: fixed;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.03) 0%, transparent 70%);
            pointer-events: none;
            z-index: 9997;
            transform: translate(-50%, -50%);
            transition: opacity 0.3s;
        }
    </style>
</head>
<body class="bg-black text-[#f5f5f7] antialiased">

    <!-- Cursor glow effect -->
    <div id="cursor-glow" class="cursor-glow hidden lg:block opacity-0"></div>

    <!-- Navigation -->
    <nav id="main-nav" class="fixed top-0 w-full z-50">
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20">
            <div class="flex justify-between items-center h-16 lg:h-[68px]">
                
                <!-- Logo -->
                <a href="#" class="relative z-50 flex items-center gap-3 group magnetic">
                    <div class="relative">
                        <img src="https://i.postimg.cc/25v7C99J/kron.jpg" alt="KRON" class="h-8 w-auto rounded-lg transition-all duration-500 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-white/10">
                        <div class="absolute inset-0 rounded-lg bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <span class="text-[16px] font-semibold tracking-tight transition-all duration-300">KRON</span>
                </a>
                
                <!-- Desktop Menu -->
                <div class="hidden lg:flex items-center gap-10">
                    <a href="#produtos" class="text-[14px] font-medium text-white/60 hover:text-white transition-colors duration-300 link-hover">Produtos</a>
                    <a href="#sobre" class="text-[14px] font-medium text-white/60 hover:text-white transition-colors duration-300 link-hover">Sobre</a>
                    <a href="#contato" class="text-[14px] font-medium text-white/60 hover:text-white transition-colors duration-300 link-hover">Contato</a>
                </div>
                
                <!-- CTA Button -->
                <div class="hidden lg:block">
                    <a href="#contato" class="btn-primary px-5 py-2.5 rounded-full text-[13px] font-semibold inline-flex items-center gap-2">
                        <span>Fale Conosco</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3"/>
                        </svg>
                    </a>
                </div>
                
                <!-- Mobile Button -->
                <button id="menu-btn" class="lg:hidden relative z-50 w-12 h-12 flex flex-col justify-center items-center gap-1.5 rounded-xl hover:bg-white/5 transition-colors" aria-label="Menu">
                    <span class="w-5 h-[2px] bg-white/90 rounded-full transition-all duration-400" id="line1"></span>
                    <span class="w-5 h-[2px] bg-white/90 rounded-full transition-all duration-400" id="line2"></span>
                </button>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Menu -->
    <div id="mobile-menu" class="mobile-menu fixed inset-0 z-40 bg-black lg:hidden">
        <div class="h-full flex flex-col justify-center px-8">
            <nav class="space-y-4 stagger-children" id="mobile-nav">
                <a href="#produtos" class="block text-[48px] font-bold tracking-tight text-white/90 hover:text-white transition-colors">Produtos</a>
                <a href="#sobre" class="block text-[48px] font-bold tracking-tight text-white/90 hover:text-white transition-colors">Sobre</a>
                <a href="#contato" class="block text-[48px] font-bold tracking-tight text-white/90 hover:text-white transition-colors">Contato</a>
            </nav>
            <div class="absolute bottom-20 left-8 right-8">
                <div class="h-px bg-gradient-to-r from-white/20 via-white/10 to-transparent mb-6"></div>
                <a href="mailto:contato@kron.com.br" class="text-[14px] text-white/50 hover:text-white transition-colors">contato@kron.com.br</a>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="min-h-screen flex flex-col justify-end pb-16 lg:pb-24 relative overflow-hidden">
        
        <!-- Backgrounds -->
        <div class="hero-image"></div>
        <div class="hero-overlay"></div>
        <div class="hero-gradient"></div>
        <div class="grid-bg"></div>
        
        <!-- Floating particles -->
        <div id="particles" class="absolute inset-0 pointer-events-none overflow-hidden"></div>
        
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20 w-full relative z-10">
            
            <!-- Badge -->
            <div class="mb-8 lg:mb-10 reveal">
                <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full glass">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-[12px] tracking-[0.15em] uppercase text-white/60 font-medium">Software Studio</span>
                </div>
            </div>
            
            <!-- Main Headline -->
            <div class="mb-12 lg:mb-16">
                <h1 class="text-[12vw] sm:text-[10vw] lg:text-[8vw] xl:text-[110px] font-bold leading-[0.9] tracking-tightest">
                    <span class="block reveal text-gradient-shine">Construímos</span>
                    <span class="block reveal text-white/20" style="transition-delay: 100ms">software que</span>
                    <span class="block reveal text-gradient" style="transition-delay: 200ms">resolve.</span>
                </h1>
            </div>
            
            <!-- Bottom row -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-12 lg:gap-8">
                
                <!-- Description -->
                <div class="max-w-md reveal" style="transition-delay: 300ms">
                    <p class="text-[16px] lg:text-[18px] text-white/50 leading-[1.7] font-normal mb-8">
                        Duas soluções. Dois mercados diferentes.<br>
                        Uma mesma obsessão por simplicidade.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <a href="#produtos" class="btn-primary px-6 py-3 rounded-full text-[14px] font-semibold inline-flex items-center gap-2">
                            <span>Ver Produtos</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </a>
                        <a href="#sobre" class="btn-outline px-6 py-3 rounded-full text-[14px] font-semibold text-white/80 hover:text-white relative z-10">
                            <span class="relative z-10">Sobre Nós</span>
                        </a>
                    </div>
                </div>
                
                <!-- Scroll indicator -->
                <div class="flex items-center gap-4 reveal" style="transition-delay: 400ms">
                    <div class="scroll-indicator flex flex-col items-center gap-2">
                        <span class="text-[11px] tracking-[0.2em] uppercase text-white/30 font-medium">Scroll</span>
                        <svg class="w-5 h-5 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                    </div>
                </div>
            </div>
            
        </div>
    </section>
    
    <!-- Marquee -->
    <div class="border-y border-white/[0.05] py-6 overflow-hidden bg-gradient-to-r from-black via-white/[0.02] to-black">
        <div class="marquee-track flex whitespace-nowrap">
            <?php for($i = 0; $i < 4; $i++): ?>
            <span class="text-[9vw] lg:text-[6vw] font-bold text-white/[0.04] mx-8 lg:mx-12 tracking-tight">SafeNode</span>
            <span class="text-[9vw] lg:text-[6vw] font-bold text-white/[0.04] mx-8 lg:mx-12">·</span>
            <span class="text-[9vw] lg:text-[6vw] font-bold text-white/[0.04] mx-8 lg:mx-12 tracking-tight">LacTech</span>
            <span class="text-[9vw] lg:text-[6vw] font-bold text-white/[0.04] mx-8 lg:mx-12">·</span>
            <span class="text-[9vw] lg:text-[6vw] font-bold text-white/[0.04] mx-8 lg:mx-12 tracking-tight">KRON</span>
            <span class="text-[9vw] lg:text-[6vw] font-bold text-white/[0.04] mx-8 lg:mx-12">·</span>
            <?php endfor; ?>
        </div>
    </div>
    
    <!-- Products Section -->
    <section id="produtos" class="py-28 lg:py-40 relative">
        <div class="grid-bg opacity-50"></div>
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20 relative">
            
            <!-- Section Header -->
            <div class="mb-16 lg:mb-24">
                <div class="flex items-center gap-4 mb-6 reveal">
                    <div class="w-12 h-px bg-gradient-to-r from-white/30 to-transparent"></div>
                    <span class="text-[11px] tracking-[0.2em] uppercase text-white/50 font-semibold">Produtos</span>
                </div>
                <h2 class="text-[36px] sm:text-[44px] lg:text-[60px] xl:text-[72px] font-bold leading-[1.05] tracking-tight">
                    <span class="block reveal text-gradient">Dois produtos.</span>
                    <span class="block reveal text-white/20" style="transition-delay: 100ms">Mercados distintos.</span>
                </h2>
            </div>
            
            <!-- Products Grid -->
            <div class="flex lg:grid lg:grid-cols-2 gap-6 overflow-x-auto lg:overflow-visible pb-6 lg:pb-0 -mx-6 lg:mx-0 px-6 lg:px-0 scrollbar-hide">
                
                <!-- SafeNode Card -->
                <a href="#safenode" class="group block flex-shrink-0 w-[85vw] sm:w-[75vw] lg:w-auto reveal-scale">
                    <div class="glass-card rounded-3xl p-8 lg:p-10 h-full card-3d">
                        <div class="card-shine"></div>
                        <div class="flex flex-col h-full min-h-[420px] lg:min-h-[480px] relative z-10">
                            
                            <!-- Icon -->
                            <div class="card-icon w-14 h-14 rounded-2xl bg-gradient-to-br from-white/10 to-white/[0.02] border border-white/10 flex items-center justify-center mb-8 transition-all duration-500">
                                <svg class="w-6 h-6 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-grow">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="text-[10px] tracking-[0.2em] uppercase text-emerald-400/80 font-semibold">Segurança Web</span>
                                    <span class="w-2 h-2 rounded-full bg-emerald-400/50"></span>
                                </div>
                                <h3 class="text-[32px] lg:text-[38px] font-bold mb-5 tracking-tight text-gradient">SafeNode</h3>
                                <p class="text-[15px] lg:text-[16px] text-white/45 leading-[1.7] max-w-sm">
                                    Plataforma completa de segurança web integrada com Cloudflare. Proteção contra ataques DDoS, firewall de aplicação web e bloqueio automático de IPs maliciosos.
                                </p>
                            </div>
                            
                            <!-- Features tags -->
                            <div class="flex flex-wrap gap-2 mb-8">
                                <span class="px-3 py-1.5 rounded-full text-[11px] font-medium bg-white/5 text-white/50 border border-white/5">DDoS Protection</span>
                                <span class="px-3 py-1.5 rounded-full text-[11px] font-medium bg-white/5 text-white/50 border border-white/5">WAF</span>
                                <span class="px-3 py-1.5 rounded-full text-[11px] font-medium bg-white/5 text-white/50 border border-white/5">Cloudflare</span>
                            </div>
                            
                            <!-- Footer -->
                            <div class="flex items-center justify-between pt-6 border-t border-white/[0.08]">
                                <span class="text-[14px] text-white/50 font-medium group-hover:text-white transition-colors duration-300">Explorar produto</span>
                                <svg class="card-arrow w-5 h-5 text-white/40 transition-all duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                                </svg>
                            </div>
                            
                        </div>
                    </div>
                </a>
                
                <!-- LacTech Card -->
                <a href="#lactech" class="group block flex-shrink-0 w-[85vw] sm:w-[75vw] lg:w-auto reveal-scale" style="transition-delay: 150ms">
                    <div class="glass-card rounded-3xl p-8 lg:p-10 h-full card-3d">
                        <div class="card-shine"></div>
                        <div class="flex flex-col h-full min-h-[420px] lg:min-h-[480px] relative z-10">
                            
                            <!-- Icon -->
                            <div class="card-icon w-14 h-14 rounded-2xl bg-gradient-to-br from-white/10 to-white/[0.02] border border-white/10 flex items-center justify-center mb-8 transition-all duration-500">
                                <svg class="w-6 h-6 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                                </svg>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-grow">
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="text-[10px] tracking-[0.2em] uppercase text-blue-400/80 font-semibold">Gestão Rural</span>
                                    <span class="w-2 h-2 rounded-full bg-blue-400/50"></span>
                                </div>
                                <h3 class="text-[32px] lg:text-[38px] font-bold mb-5 tracking-tight text-gradient">LacTech</h3>
                                <p class="text-[15px] lg:text-[16px] text-white/45 leading-[1.7] max-w-sm">
                                    Sistema completo de gestão para fazendas leiteiras. Controle de rebanho, produção de leite, monitoramento sanitário e relatórios inteligentes.
                                </p>
                            </div>
                            
                            <!-- Features tags -->
                            <div class="flex flex-wrap gap-2 mb-8">
                                <span class="px-3 py-1.5 rounded-full text-[11px] font-medium bg-white/5 text-white/50 border border-white/5">Rebanho</span>
                                <span class="px-3 py-1.5 rounded-full text-[11px] font-medium bg-white/5 text-white/50 border border-white/5">Produção</span>
                                <span class="px-3 py-1.5 rounded-full text-[11px] font-medium bg-white/5 text-white/50 border border-white/5">Analytics</span>
                            </div>
                            
                            <!-- Footer -->
                            <div class="flex items-center justify-between pt-6 border-t border-white/[0.08]">
                                <span class="text-[14px] text-white/50 font-medium group-hover:text-white transition-colors duration-300">Explorar produto</span>
                                <svg class="card-arrow w-5 h-5 text-white/40 transition-all duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                                </svg>
                            </div>
                            
                        </div>
                    </div>
                </a>
                
            </div>
            
        </div>
    </section>
    
    <!-- SafeNode Detail -->
    <section id="safenode" class="py-28 lg:py-40 border-t border-white/[0.05] relative overflow-hidden">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-emerald-500/5 rounded-full blur-[150px] pointer-events-none"></div>
        
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20 relative">
            
            <div class="grid lg:grid-cols-2 gap-16 lg:gap-24">
                
                <!-- Left - Sticky -->
                <div class="lg:sticky lg:top-32 lg:self-start">
                    <span class="inline-flex items-center gap-2 text-[11px] tracking-[0.2em] uppercase text-emerald-400/80 mb-5 font-semibold reveal">
                        <span class="w-6 h-px bg-emerald-400/50"></span>
                        01 / Segurança
                    </span>
                    <h2 class="text-[48px] lg:text-[64px] xl:text-[76px] font-bold mb-6 tracking-tight leading-[0.95] reveal">
                        <span class="text-gradient">SafeNode</span>
                    </h2>
                    <p class="text-[17px] lg:text-[19px] text-white/50 leading-[1.7] mb-10 max-w-lg reveal" style="transition-delay: 100ms">
                        Plataforma de segurança web integrada com Cloudflare. Proteção enterprise para suas aplicações com firewall inteligente e resposta automatizada.
                    </p>
                    <a href="#contato" class="inline-flex items-center gap-3 text-[14px] font-semibold text-white/70 hover:text-white transition-all duration-300 group reveal" style="transition-delay: 200ms">
                        <span class="btn-outline px-5 py-2.5 rounded-full relative z-10">
                            <span class="relative z-10">Solicitar demo</span>
                        </span>
                        <span class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center group-hover:bg-white/10 transition-all duration-300">
                            <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                            </svg>
                        </span>
                    </a>
                </div>
                
                <!-- Right - Features -->
                <div class="space-y-1 stagger-children" id="safenode-features">
                    
                    <?php
                    $safenode_features = [
                        ['num' => '01', 'title' => 'Integração Cloudflare', 'desc' => 'Sincronização automática para proteção DDoS, WAF e gestão de DNS com monitoramento em tempo real.'],
                        ['num' => '02', 'title' => 'Bloqueio Automático', 'desc' => 'Sistema inteligente de detecção e bloqueio de IPs maliciosos com análise de padrões de ataque.'],
                        ['num' => '03', 'title' => 'Dashboard de Segurança', 'desc' => 'Painel com métricas, logs de incidentes, alertas em tempo real e histórico completo de eventos.'],
                        ['num' => '04', 'title' => 'Modo Sob Ataque', 'desc' => 'Proteção máxima durante ataques com níveis de segurança configuráveis e resposta adaptativa.'],
                        ['num' => '05', 'title' => 'Multi-Site Management', 'desc' => 'Gerencie múltiplos sites em uma plataforma com visão global e configurações personalizadas.'],
                    ];
                    
                    foreach($safenode_features as $feature):
                    ?>
                    <div class="feature-item border-b border-white/[0.06] py-7 pl-0 rounded-lg transition-all duration-500">
                        <div class="flex items-start gap-6">
                            <span class="feature-number text-[12px] text-white/25 mt-1.5 font-bold transition-all duration-300 min-w-[24px]"><?= $feature['num'] ?></span>
                            <div class="flex-1">
                                <h4 class="text-[18px] lg:text-[20px] font-semibold mb-2.5 tracking-tight"><?= $feature['title'] ?></h4>
                                <p class="text-[14px] lg:text-[15px] text-white/40 leading-[1.7]"><?= $feature['desc'] ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                </div>
                
            </div>
            
        </div>
    </section>
    
    <!-- LacTech Detail -->
    <section id="lactech" class="py-28 lg:py-40 border-t border-white/[0.05] relative overflow-hidden">
        <div class="absolute top-0 left-0 w-[600px] h-[600px] bg-blue-500/5 rounded-full blur-[150px] pointer-events-none"></div>
        
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20 relative">
            
            <div class="grid lg:grid-cols-2 gap-16 lg:gap-24">
                
                <!-- Left - Sticky -->
                <div class="lg:sticky lg:top-32 lg:self-start">
                    <span class="inline-flex items-center gap-2 text-[11px] tracking-[0.2em] uppercase text-blue-400/80 mb-5 font-semibold reveal">
                        <span class="w-6 h-px bg-blue-400/50"></span>
                        02 / Agro
                    </span>
                    <h2 class="text-[48px] lg:text-[64px] xl:text-[76px] font-bold mb-6 tracking-tight leading-[0.95] reveal">
                        <span class="text-gradient">LacTech</span>
                    </h2>
                    <p class="text-[17px] lg:text-[19px] text-white/50 leading-[1.7] mb-10 max-w-lg reveal" style="transition-delay: 100ms">
                        Sistema completo de gestão para fazendas leiteiras. Aumente a produtividade através de ferramentas inteligentes e análises precisas.
                    </p>
                    <a href="#contato" class="inline-flex items-center gap-3 text-[14px] font-semibold text-white/70 hover:text-white transition-all duration-300 group reveal" style="transition-delay: 200ms">
                        <span class="btn-outline px-5 py-2.5 rounded-full relative z-10">
                            <span class="relative z-10">Solicitar demo</span>
                        </span>
                        <span class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center group-hover:bg-white/10 transition-all duration-300">
                            <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                            </svg>
                        </span>
                    </a>
                </div>
                
                <!-- Right - Features -->
                <div class="space-y-1 stagger-children" id="lactech-features">
                    
                    <?php
                    $lactech_features = [
                        ['num' => '01', 'title' => 'Gestão de Rebanho', 'desc' => 'Cadastro detalhado com genealogia, histórico reprodutivo, eventos sanitários e rastreabilidade completa.'],
                        ['num' => '02', 'title' => 'Controle de Produção', 'desc' => 'Registro de ordenhas, qualidade do leite, metas de produção e análise de produtividade por animal.'],
                        ['num' => '03', 'title' => 'Monitoramento Sanitário', 'desc' => 'Controle de saúde animal com tratamentos, diagnósticos, prevenção e alertas automáticos.'],
                        ['num' => '04', 'title' => 'Gestão Financeira', 'desc' => 'Custos operacionais, receitas, fluxo de caixa e análise de viabilidade econômica detalhada.'],
                        ['num' => '05', 'title' => 'Relatórios Inteligentes', 'desc' => 'Dashboards personalizados, métricas em tempo real e insights para decisões estratégicas.'],
                    ];
                    
                    foreach($lactech_features as $feature):
                    ?>
                    <div class="feature-item border-b border-white/[0.06] py-7 pl-0 rounded-lg transition-all duration-500">
                        <div class="flex items-start gap-6">
                            <span class="feature-number text-[12px] text-white/25 mt-1.5 font-bold transition-all duration-300 min-w-[24px]"><?= $feature['num'] ?></span>
                            <div class="flex-1">
                                <h4 class="text-[18px] lg:text-[20px] font-semibold mb-2.5 tracking-tight"><?= $feature['title'] ?></h4>
                                <p class="text-[14px] lg:text-[15px] text-white/40 leading-[1.7]"><?= $feature['desc'] ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                </div>
                
            </div>
            
        </div>
    </section>
    
    <!-- About/Philosophy -->
    <section id="sobre" class="py-28 lg:py-40 border-t border-white/[0.05] relative overflow-hidden">
        <div class="grid-bg opacity-30"></div>
        
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20 relative">
            
            <div class="max-w-4xl">
                
                <div class="flex items-center gap-4 mb-10 reveal">
                    <div class="w-12 h-px bg-gradient-to-r from-white/30 to-transparent"></div>
                    <span class="text-[11px] tracking-[0.2em] uppercase text-white/50 font-semibold">Filosofia</span>
                </div>
                
                <h2 class="text-[32px] sm:text-[40px] lg:text-[56px] xl:text-[68px] font-bold leading-[1.1] tracking-tight mb-12">
                    <span class="block reveal text-gradient">Acreditamos que software</span>
                    <span class="block reveal text-white/20" style="transition-delay: 100ms">deve ser <span class="text-gradient">invisível.</span></span>
                    <span class="block reveal text-gradient" style="transition-delay: 200ms">Deve simplesmente <span class="text-white/20">funcionar.</span></span>
                </h2>
                
                <p class="text-[17px] lg:text-[19px] text-white/45 leading-[1.7] max-w-2xl reveal" style="transition-delay: 300ms">
                    Não construímos features por construir. Cada linha de código existe para resolver um problema real. Essa obsessão por simplicidade guia tudo que fazemos na KRON.
                </p>
                
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12 mt-24 lg:mt-32 pt-16 lg:pt-20 border-t border-white/[0.08]">
                
                <?php
                $stats = [
                    ['value' => '2', 'label' => 'Produtos', 'suffix' => ''],
                    ['value' => '500', 'label' => 'Clientes', 'suffix' => '+'],
                    ['value' => '99.9', 'label' => 'Uptime', 'suffix' => '%'],
                    ['value' => '24/7', 'label' => 'Suporte', 'suffix' => ''],
                ];
                
                $delay = 0;
                foreach($stats as $stat):
                ?>
                <div class="reveal group" style="transition-delay: <?= $delay ?>ms">
                    <div class="flex items-baseline gap-1">
                        <span class="stat-number text-[48px] lg:text-[64px] font-bold tracking-tight text-gradient group-hover:text-gradient-shine transition-all duration-500" data-value="<?= $stat['value'] ?>"><?= $stat['value'] ?></span>
                        <span class="text-[28px] lg:text-[36px] font-bold text-white/40"><?= $stat['suffix'] ?></span>
                    </div>
                    <p class="text-[13px] lg:text-[14px] text-white/40 mt-2 font-medium tracking-wide"><?= $stat['label'] ?></p>
                </div>
                <?php 
                $delay += 100;
                endforeach; 
                ?>
                
            </div>
            
        </div>
    </section>
    
    <!-- Contact CTA -->
    <section id="contato" class="py-28 lg:py-40 border-t border-white/[0.05] relative overflow-hidden">
        
        <!-- Background effects -->
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-white/[0.01] to-transparent pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-white/[0.02] rounded-full blur-[200px] pointer-events-none"></div>
        
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20 relative">
            
            <div class="text-center max-w-3xl mx-auto">
                
                <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full glass mb-8 reveal">
                    <span class="w-2 h-2 rounded-full bg-white/50 animate-pulse"></span>
                    <span class="text-[12px] tracking-[0.15em] uppercase text-white/60 font-medium">Disponível para projetos</span>
                </div>
                
                <h2 class="text-[40px] sm:text-[52px] lg:text-[68px] xl:text-[80px] font-bold mb-8 tracking-tight reveal">
                    <span class="text-gradient">Vamos conversar?</span>
                </h2>
                
                <p class="text-[17px] lg:text-[19px] text-white/45 mb-12 reveal" style="transition-delay: 100ms">
                    Entre em contato para saber mais sobre nossas soluções<br class="hidden sm:block"> e como podemos ajudar seu negócio.
                </p>
                
                <a href="mailto:contato@kron.com.br" class="group inline-flex flex-col sm:flex-row items-center gap-4 sm:gap-6 reveal" style="transition-delay: 200ms">
                    <span class="text-[24px] sm:text-[32px] lg:text-[40px] font-bold text-white/70 group-hover:text-white transition-colors duration-300">contato@kron.com.br</span>
                    <span class="w-14 h-14 rounded-full bg-white/5 border border-white/10 flex items-center justify-center group-hover:bg-white group-hover:border-white transition-all duration-500">
                        <svg class="w-6 h-6 text-white/50 group-hover:text-black group-hover:translate-x-1 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                        </svg>
                    </span>
                </a>
                
            </div>
            
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="py-16 lg:py-24 border-t border-white/[0.05] bg-gradient-to-b from-black to-black/95 relative">
        <div class="grid-bg opacity-20"></div>
        
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20 relative">
            
            <!-- Top Section -->
            <div class="grid lg:grid-cols-12 gap-12 lg:gap-8 mb-16 lg:mb-20">
                
                <!-- Brand -->
                <div class="lg:col-span-5">
                    <a href="#" class="inline-flex items-center gap-3 mb-6 group">
                        <img src="https://i.postimg.cc/25v7C99J/kron.jpg" alt="KRON" class="h-10 w-auto rounded-xl group-hover:scale-105 transition-transform duration-500 shadow-lg">
                        <span class="text-2xl font-bold tracking-tight">KRON</span>
                    </a>
                    <p class="text-[15px] text-white/45 leading-[1.7] max-w-sm mb-6">
                        Kernel for Resilient Operating Nodes. Construímos software que resolve problemas reais com simplicidade e eficiência.
                    </p>
                    <a href="mailto:contato@kron.com.br" class="inline-flex items-center gap-3 text-[14px] text-white/50 hover:text-white transition-colors duration-300 group">
                        <span class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center group-hover:bg-white/10 transition-all duration-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <span>contato@kron.com.br</span>
                    </a>
                </div>
                
                <!-- Products -->
                <div class="lg:col-span-2 lg:col-start-7">
                    <h4 class="text-[11px] tracking-[0.2em] uppercase text-white/40 mb-6 font-bold">Produtos</h4>
                    <ul class="space-y-4">
                        <li><a href="#safenode" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">SafeNode</a></li>
                        <li><a href="#lactech" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">LacTech</a></li>
                    </ul>
                </div>
                
                <!-- Company -->
                <div class="lg:col-span-2">
                    <h4 class="text-[11px] tracking-[0.2em] uppercase text-white/40 mb-6 font-bold">Empresa</h4>
                    <ul class="space-y-4">
                        <li><a href="#sobre" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">Sobre</a></li>
                        <li><a href="#contato" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">Contato</a></li>
                        <li><a href="#produtos" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">Produtos</a></li>
                    </ul>
                </div>
                
                <!-- Legal -->
                <div class="lg:col-span-2">
                    <h4 class="text-[11px] tracking-[0.2em] uppercase text-white/40 mb-6 font-bold">Legal</h4>
                    <ul class="space-y-4">
                        <li><a href="#" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">Privacidade</a></li>
                        <li><a href="#" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">Termos de Uso</a></li>
                        <li><a href="#" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">Cookies</a></li>
                    </ul>
                </div>
                
            </div>
            
            <!-- Divider -->
            <div class="h-px bg-gradient-to-r from-transparent via-white/15 to-transparent mb-10"></div>
            
            <!-- Bottom Section -->
            <div class="flex flex-col lg:flex-row justify-between items-center gap-6">
                
                <!-- Copyright -->
                <div class="flex items-center gap-3 text-[13px] text-white/35">
                    <span>© <?= date('Y') ?> KRON</span>
                    <span class="w-1 h-1 rounded-full bg-white/20"></span>
                    <span>Todos os direitos reservados</span>
                </div>
                
                <!-- Social Icons -->
                <div class="flex items-center gap-3">
                    <a href="https://www.instagram.com/safenode/" target="_blank" rel="noopener noreferrer" class="social-icon" aria-label="Instagram SafeNode">
                        <svg class="w-5 h-5 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke-width="1.5" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37zM17.5 6.5h.01" />
                        </svg>
                    </a>
                    <a href="https://www.instagram.com/lvnas._/" target="_blank" rel="noopener noreferrer" class="social-icon" aria-label="Instagram Lvnas">
                        <svg class="w-5 h-5 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke-width="1.5" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37zM17.5 6.5h.01" />
                        </svg>
                    </a>
                    <a href="https://github.com" target="_blank" rel="noopener noreferrer" class="social-icon" aria-label="GitHub">
                        <svg class="w-5 h-5 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 00-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0020 4.77 5.07 5.07 0 0019.91 1S18.73.65 16 2.48a13.38 13.38 0 00-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 005 4.77a5.44 5.44 0 00-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 009 18.13V22" />
                        </svg>
                    </a>
                </div>
                
            </div>
            
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Cursor glow effect
        const cursorGlow = document.getElementById('cursor-glow');
        let mouseX = 0, mouseY = 0;
        let glowX = 0, glowY = 0;
        
        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
            cursorGlow.style.opacity = '1';
        });
        
        document.addEventListener('mouseleave', () => {
            cursorGlow.style.opacity = '0';
        });
        
        function animateGlow() {
            glowX += (mouseX - glowX) * 0.1;
            glowY += (mouseY - glowY) * 0.1;
            cursorGlow.style.left = glowX + 'px';
            cursorGlow.style.top = glowY + 'px';
            requestAnimationFrame(animateGlow);
        }
        animateGlow();
        
        // Floating particles
        const particlesContainer = document.getElementById('particles');
        
        function createParticle() {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = '100%';
            particle.style.width = (Math.random() * 3 + 2) + 'px';
            particle.style.height = particle.style.width;
            particle.style.opacity = Math.random() * 0.5 + 0.2;
            particle.style.animation = `particle-float ${Math.random() * 10 + 15}s linear forwards`;
            particlesContainer.appendChild(particle);
            
            setTimeout(() => particle.remove(), 25000);
        }
        
        // Create particles periodically
        setInterval(createParticle, 2000);
        for(let i = 0; i < 10; i++) {
            setTimeout(createParticle, i * 500);
        }
        
        // Header scroll effect
        const mainNav = document.getElementById('main-nav');
        
        function handleScroll() {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            mainNav.classList.toggle('scrolled', currentScroll > 80);
        }
        
        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();
        
        // Mobile menu
        const menuBtn = document.getElementById('menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileNav = document.getElementById('mobile-nav');
        const line1 = document.getElementById('line1');
        const line2 = document.getElementById('line2');
        let menuOpen = false;
        
        menuBtn?.addEventListener('click', () => {
            menuOpen = !menuOpen;
            mobileMenu.classList.toggle('active');
            
            if (menuOpen) {
                line1.style.transform = 'rotate(45deg) translate(2px, 2px)';
                line2.style.transform = 'rotate(-45deg) translate(2px, -2px)';
                setTimeout(() => mobileNav.classList.add('active'), 300);
            } else {
                line1.style.transform = '';
                line2.style.transform = '';
                mobileNav.classList.remove('active');
            }
        });
        
        // Close mobile menu on link click
        mobileMenu?.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                menuOpen = false;
                mobileMenu.classList.remove('active');
                mobileNav.classList.remove('active');
                line1.style.transform = '';
                line2.style.transform = '';
            });
        });
        
        // Reveal on scroll
        const reveals = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale, .stagger-children');
        
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '-30px'
        });
        
        reveals.forEach(el => revealObserver.observe(el));
        
        // Magnetic button effect
        document.querySelectorAll('.magnetic').forEach(btn => {
            btn.addEventListener('mousemove', (e) => {
                const rect = btn.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                btn.style.transform = `translate(${x * 0.2}px, ${y * 0.2}px)`;
            });
            
            btn.addEventListener('mouseleave', () => {
                btn.style.transform = '';
            });
        });
        
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
        
        // Counter animation for stats
        const statNumbers = document.querySelectorAll('.stat-number[data-value]');
        
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const value = el.dataset.value;
                    
                    if (value.includes('/') || value.includes('.')) {
                        return; // Skip non-numeric values
                    }
                    
                    let current = 0;
                    const target = parseInt(value);
                    const duration = 2000;
                    const step = target / (duration / 16);
                    
                    const updateCounter = () => {
                        current += step;
                        if (current < target) {
                            el.textContent = Math.floor(current);
                            requestAnimationFrame(updateCounter);
                        } else {
                            el.textContent = target;
                        }
                    };
                    
                    updateCounter();
                    counterObserver.unobserve(el);
                }
            });
        }, { threshold: 0.5 });
        
        statNumbers.forEach(el => counterObserver.observe(el));
    </script>
    
    <div class="noise"></div>

</body>
</html>
