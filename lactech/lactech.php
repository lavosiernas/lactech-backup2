<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Otimizado -->
    <title>LacTech - Sistema Completo de Gestão para Fazendas Leiteiras | Software de Pecuária</title>
    <meta name="description" content="LacTech: O melhor sistema de gestão de fazendas leiteiras do Brasil. Controle de rebanho, produção de leite, saúde animal, reprodução, financeiro e relatórios completos. Software especializado em pecuária leiteira.">
    <meta name="keywords" content="lactech, lac tech, sistema leiteiro, gestão fazenda leiteira, controle rebanho bovino, produção leite, software pecuária, gestão gado leiteiro, controle ordenha, saúde animal, reprodução bovina, inseminação artificial, controle financeiro rural">
    <meta name="author" content="Xandria - LacTech">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://lactechsys.com/lactech.php">
    
    <!-- Open Graph -->
    <meta property="og:title" content="LacTech - Sistema de Gestão de Fazendas Leiteiras">
    <meta property="og:description" content="Sistema completo para gestão de pecuária leiteira. Controle de rebanho, produção, saúde animal e muito mais.">
    <meta property="og:image" content="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <meta property="og:url" content="https://lactechsys.com/lactech.php">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="LacTech - Sistema de Gestão Leiteira">
    <meta name="twitter:description" content="Sistema completo para gestão de fazendas leiteiras">
    <meta name="twitter:image" content="https://i.postimg.cc/vmrkgDcB/lactech.png">
    
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/png">
    <link rel="apple-touch-icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    
    <!-- Google Analytics 4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-Y1DPSZ8DP0"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-Y1DPSZ8DP0', {
        'page_title': 'LacTech - Página do Produto',
        'send_page_view': true
      });
    </script>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#22c55e',
                        'primary-dark': '#16a34a'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
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
            background: #ffffff;
            color: #1a1a1a;
            font-size: 16px;
            line-height: 1.6;
            letter-spacing: -0.01em;
        }
        
        h1, h2, h3 {
            letter-spacing: -0.03em;
            font-weight: 700;
            line-height: 1.2;
        }
        
        h1 { font-size: clamp(2.5rem, 5vw, 4rem); }
        h2 { font-size: clamp(2rem, 4vw, 3rem); }
        h3 { font-size: clamp(1.25rem, 2vw, 1.5rem); }
        
        .btn {
            padding: 14px 32px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #22c55e;
            color: #fff;
            box-shadow: 0 4px 14px rgba(34, 197, 94, 0.25);
        }
        
        .btn-primary:hover {
            background: #16a34a;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.35);
        }
        
        .btn-secondary {
            background: transparent;
            color: #1a1a1a;
            border: 2px solid #e5e5e5;
        }
        
        .btn-secondary:hover {
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.05);
            transform: translateY(-1px);
        }
        
        .card {
            background: #ffffff;
            border: 1px solid #f0f0f0;
            border-radius: 20px;
            padding: 32px;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .card:hover {
            border-color: rgba(34, 197, 94, 0.3);
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
        }
        
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .animate-on-scroll.animate {
            opacity: 1;
            transform: translateY(0);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .feature-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            box-shadow: 0 4px 14px rgba(34, 197, 94, 0.25);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .feature-icon:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.35);
        }
        
        .stat-card {
            background: #ffffff;
            border: 1px solid #f0f0f0;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
        }
        
        .stat-card:hover {
            border-color: rgba(34, 197, 94, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }
        
        .project-card {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .project-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .project-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .project-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            color: white;
            padding: 20px;
        }
        
        .testimonial-card {
            background: #ffffff;
            border: 1px solid #f0f0f0;
            border-radius: 16px;
            padding: 24px;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .testimonial-card:hover {
            border-color: rgba(34, 197, 94, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }
        
        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            font-weight: 600;
        }
        
        .pricing-card {
            background: #ffffff;
            border: 1px solid #f0f0f0;
            border-radius: 20px;
            padding: 32px;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            position: relative;
        }
        
        .pricing-card:hover {
            border-color: rgba(34, 197, 94, 0.3);
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
        }
        
        .pricing-card.featured {
            border-color: #22c55e;
            box-shadow: 0 8px 32px rgba(34, 197, 94, 0.15);
        }
        
        .pricing-card.featured::before {
            content: "Mais Popular";
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #22c55e;
            color: white;
            padding: 8px 24px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        /* Video Scroll Effect - OTIMIZADO MAS MANTENDO ANIMAÇÃO ORIGINAL */
        .video-container {
            width: 100vw;
            height: 80vh;
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            transition: width 0.5s ease-out, height 0.5s ease-out, 
                        margin 0.5s ease-out, border-radius 0.5s ease-out,
                        box-shadow 0.5s ease-out;
            border-radius: 0;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            will-change: width, height, border-radius;
            transform: translateZ(0); /* GPU acceleration */
        }
        
        .video-container.scrolled {
            width: calc(100% - 2rem);
            height: 60vh;
            margin-left: 1rem;
            margin-right: 1rem;
            border-radius: 20px;
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1);
        }
        
        /* Video Control Button */
        .video-control-btn {
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: transparent;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid rgba(34, 197, 94, 0.8);
        }
        
        .video-control-btn:hover {
            border-color: rgba(34, 197, 94, 1);
            transform: scale(1.1);
        }
        
        .video-control-btn svg {
            transition: all 0.3s ease;
        }
        
        .video-control-btn .hidden {
            display: none;
        }
        
        /* Hide scrollbar for mobile horizontal scroll */
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        
        /* Infinite scroll animation */
        @keyframes infiniteScroll {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-50%);
            }
        }
        
        .animate-scroll {
            animation: infiniteScroll 20s linear infinite;
            width: 200%; /* Dobra a largura para scroll infinito */
            display: flex;
            flex-direction: row;
        }
        
        /* Mobile carousel specific styles */
        @media (max-width: 640px) {
            .animate-scroll {
                gap: 1rem;
                padding-left: 1rem;
                padding-right: 1rem;
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
            }
            
            .animate-scroll > div {
                margin-right: 0;
                flex-shrink: 0 !important;
            }
            
            /* Remove margins from container */
            .relative.-mx-6 {
                margin-left: -1.5rem;
                margin-right: -1.5rem;
            }
            
            /* Ensure cards have proper spacing */
            .animate-scroll > div:first-child {
                margin-left: 0;
            }
        }
        
        /* Ensure desktop cards are visible */
        @media (min-width: 640px) {
            .hidden.sm\\:grid {
                display: grid !important;
            }
        }
        
        /* Pause animation on hover/touch */
        #featuresCarousel:hover .animate-scroll,
        #featuresCarousel:active .animate-scroll {
            animation-play-state: paused;
        }
        
        /* Mobile responsive for trust section icons */
        @media (max-width: 768px) {
            .trust-icons-grid {
                grid-template-columns: 1fr !important;
                gap: 1.5rem;
            }
        }
        
        /* Mobile Menu Animations */
        #mobileMenu {
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            transform: translateY(-10px);
        }
        
        #mobileMenu.show {
            max-height: 300px;
            opacity: 1;
            transform: translateY(0);
        }
        
        #mobileMenu .menu-item {
            opacity: 0;
            transform: translateX(-20px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        #mobileMenu.show .menu-item {
            opacity: 1;
            transform: translateX(0);
        }
        
        #mobileMenu.show .menu-item:nth-child(1) { transition-delay: 0.1s; }
        #mobileMenu.show .menu-item:nth-child(2) { transition-delay: 0.2s; }
        #mobileMenu.show .menu-item:nth-child(3) { transition-delay: 0.3s; }
        #mobileMenu.show .menu-item:nth-child(4) { transition-delay: 0.4s; }
        
        /* Hamburger Menu Animation */
        .hamburger-icon svg {
            transition: all 0.3s ease;
        }
        
        .hamburger-icon.active svg path:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        
        .hamburger-icon.active svg path:nth-child(2) {
            opacity: 0;
            transform: scale(0);
        }
        
        .hamburger-icon.active svg path:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }
    </style>
</head>

<body>
    <!-- Navigation -->

    <!-- Hero Section -->
    <section id="home" class="w-full min-h-screen bg-white">
        <!-- Mobile Layout -->
        <div class="block lg:hidden">
            <!-- Mobile Header -->
            <div class="px-4 sm:px-8 pt-6 pb-4 bg-white border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <!-- Logo -->
                    <span class="text-2xl sm:text-3xl font-bold">
                        <span class="text-gray-800">Lac</span><span class="text-gray-800">Tech</span>
                    </span>
                    
                    <!-- Mobile Menu Button -->
                    <button class="p-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors hamburger-icon" id="hamburgerBtn" onclick="toggleMobileMenu()">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 18h16"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Mobile Menu (Hidden by default) -->
                <div id="mobileMenu" class="mt-4 pb-4">
                    <div class="flex flex-col space-y-3">
                        <a href="#features" class="menu-item text-gray-700 hover:text-gray-900 transition-colors py-2 border-b border-gray-100">Funcionalidades</a>
                        <a href="#pricing" class="menu-item text-gray-700 hover:text-gray-900 transition-colors py-2 border-b border-gray-100">Preços</a>
                        <a href="#about" class="menu-item text-gray-700 hover:text-gray-900 transition-colors py-2 border-b border-gray-100">Sobre o LacTech</a>
                        <a href="index.php" class="menu-item px-4 py-3 border-2 border-dashed border-gray-800 text-gray-800 rounded-lg hover:bg-gray-800 hover:text-white transition-all flex items-center justify-center mt-2">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                            Login
                        </a>
                    </div>
                </div>
            </div>

            <!-- Mobile Text Content (above image) -->
            <div class="w-full px-4 sm:px-6 mb-6 lg:hidden">
                <!-- Main Headline -->
                <h1 class="text-3xl sm:text-4xl font-bold text-gray-800 leading-tight mb-4 text-center">
                    Revolucione sua
                    <br>
                    fazenda leiteira
                </h1>

                <!-- TrustPilot Rating -->
                <div class="flex items-center justify-center mb-6">
                    <svg class="w-5 h-5 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.538 1.118l-2.8-2.034a1 1 0 00-1.176 0l-2.8 2.034c-.783.57-1.838-.197-1.538-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.381-1.81.588-1.81h3.462a1 1 0 00.95-.69l1.07-3.292z"></path>
                    </svg>
                    <span class="text-gray-700 font-medium">4.9 Avaliação dos Produtores</span>
                </div>
            </div>

            <!-- Image Panel -->
            <div class="w-full relative flex items-center justify-center p-4 sm:p-6">
                <div class="relative bg-blue-400 rounded-2xl overflow-hidden shadow-2xl w-full max-w-md" style="height: 35vh; min-height: 220px;">
                    <!-- Image Slides -->
                    <div class="relative w-full h-full">
                        <img id="slide1-mobile" src="./assets/video/img12.jpg" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500 opacity-100">
                        <img id="slide2-mobile" src="./assets/video/img13.jpg" alt="Fazenda leiteira moderna" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500 opacity-0">
                        <img id="slide3-mobile" src="./assets/video/img14.jpg" alt="Tecnologia na agricultura" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500 opacity-0">
                    </div>
                
                    <!-- Chat Bubble - Bottom Left -->
                    <div class="absolute bottom-16 left-4 bg-white/95 backdrop-blur-sm rounded-full px-3 py-2 shadow-lg flex items-center space-x-2">
                        <svg class="w-4 h-4 text-gray-800" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-800 font-medium text-xs">Incrível! Vou implementar na minha fazenda</span>
                    </div>

                    <!-- Unlock New Customers Box - Bottom Left -->
                    <div class="absolute bottom-6 left-4 bg-transparent flex items-center space-x-2">
                        <div class="w-4 h-4 bg-white rounded-full flex items-center justify-center">
                            <svg class="w-3 h-3 text-gray-800" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.538 1.118l-2.8-2.034a1 1 0 00-1.176 0l-2.8 2.034c-.783.57-1.838-.197-1.538-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.381-1.81.588-1.81h3.462a1 1 0 00.95-.69l1.07-3.292z"></path>
                            </svg>
                        </div>
                        <span class="text-white font-medium text-sm">Aumente a Produtividade com IA</span>
                    </div>

                    <!-- Slide Indicators - Bottom Right -->
                    <div class="absolute bottom-3 right-3 flex space-x-1">
                        <div class="w-6 h-1 bg-white rounded-full slide-indicator-mobile active" onclick="showSlideMobile(1)"></div>
                        <div class="w-6 h-1 bg-white/30 rounded-full slide-indicator-mobile" onclick="showSlideMobile(2)"></div>
                        <div class="w-6 h-1 bg-white/30 rounded-full slide-indicator-mobile" onclick="showSlideMobile(3)"></div>
                    </div>
                </div>
            </div>

            <!-- Content Panel for Mobile -->
            <div class="w-full flex flex-col justify-center px-4 sm:px-8 pb-8">
                <!-- Description (below image on mobile) -->
                <p class="text-base sm:text-lg text-gray-600 mb-6 text-center max-w-4xl mx-auto">
                    Sistema inteligente de gestão leiteira que revoluciona sua fazenda com tecnologia avançada, monitoramento em tempo real e controle total do seu rebanho.
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4 justify-center">
                    <button class="px-6 sm:px-8 py-3 sm:py-4 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all shadow-lg text-sm sm:text-base">
                        Começar Agora
                    </button>
                    <button class="px-6 sm:px-8 py-3 sm:py-4 border-2 border-gray-300 text-gray-800 rounded-xl hover:bg-gray-100 transition-all text-sm sm:text-base">
                        Ver Funcionalidades
                    </button>
                </div>
            </div>
        </div>

        <!-- Desktop Layout -->
        <div class="hidden lg:flex h-screen">
            <!-- Left Panel: Text Content -->
            <div class="w-1/2 flex flex-col justify-start px-16 pt-20">
                <!-- Header (Logo, Nav, CTA) -->
                <div class="flex items-center justify-between mb-8">
                    <span class="text-5xl font-bold">
                        <span class="text-black">Lac</span><span class="text-black">Tech</span>
                    </span>
                    <div class="flex items-center bg-yellow-50 border border-gray-200 rounded-full px-8 py-3">
                        <a href="#features" class="text-gray-700 hover:text-gray-900 transition-colors mr-8">Funcionalidades</a>
                        <a href="#pricing" class="text-gray-700 hover:text-gray-900 transition-colors mr-8">Preços</a>
                        <a href="#about" class="text-gray-700 hover:text-gray-900 transition-colors mr-8">Sobre o LacTech</a>
                        <a href="index.php" class="px-6 py-2 border-2 border-dashed border-gray-800 text-gray-800 rounded-full hover:bg-gray-800 hover:text-white transition-all flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                            Login
                        </a>
                    </div>
                </div>

                <!-- TrustPilot Rating -->
                <div class="flex items-center mb-6">
                    <svg class="w-5 h-5 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.538 1.118l-2.8-2.034a1 1 0 00-1.176 0l-2.8 2.034c-.783.57-1.838-.197-1.538-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.381-1.81.588-1.81h3.462a1 1 0 00.95-.69l1.07-3.292z"></path>
                    </svg>
                    <span class="text-gray-700 font-medium">4.9 Avaliação dos Produtores</span>
                </div>

                <!-- Main Headline -->
                <h1 class="text-6xl lg:text-7xl font-bold text-gray-800 leading-tight mb-6">
                    Revolucione sua
                    <br>
                    fazenda leiteira
                </h1>

                <!-- Description -->
                <p class="text-lg text-gray-600 mb-8 max-w-lg mt-8">
                    Sistema inteligente de gestão leiteira que revoluciona sua fazenda com tecnologia avançada, monitoramento em tempo real e controle total do seu rebanho.
                </p>

                <!-- CTA Buttons -->
                <div class="flex space-x-4">
                    <button class="px-8 py-4 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all shadow-lg">
                        Começar Agora
                    </button>
                    <button class="px-8 py-4 border-2 border-gray-300 text-gray-800 rounded-xl hover:bg-gray-100 transition-all">
                        Ver Funcionalidades
                    </button>
                </div>
            </div>

            <!-- Right Panel: Image and Overlays -->
            <div class="w-1/2 relative flex items-center justify-center p-8">
                <div class="relative bg-blue-400 rounded-3xl overflow-hidden shadow-2xl" style="width: 90%; height: 85vh;">
                    <!-- Image Slides -->
                    <div class="relative w-full h-full">
                        <img id="slide1" src="./assets/video/img12.jpg" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500 opacity-100">
                        <img id="slide2" src="./assets/video/img13.jpg" alt="Fazenda leiteira moderna" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500 opacity-0">
                        <img id="slide3" src="./assets/video/img14.jpg" alt="Tecnologia na agricultura" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500 opacity-0">
                    </div>
                
                    <!-- Chat Bubble - Bottom Left -->
                    <div class="absolute bottom-32 left-20 bg-white/95 backdrop-blur-sm rounded-full px-6 py-3 shadow-lg flex items-center space-x-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-800 font-medium">Incrível! Vou implementar na minha fazenda</span>
                    </div>

                    <!-- Unlock New Customers Box - Bottom Left -->
                    <div class="absolute bottom-16 left-10 bg-transparent flex items-center space-x-2">
                        <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-800" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.538 1.118l-2.8-2.034a1 1 0 00-1.176 0l-2.8 2.034c-.783.57-1.838-.197-1.538-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.381-1.81.588-1.81h3.462a1 1 0 00.95-.69l1.07-3.292z"></path>
                            </svg>
                        </div>
                        <span class="text-white font-medium text-lg">Aumente a Produtividade com IA</span>
                    </div>

                    <!-- Slide Indicators - Bottom Right -->
                    <div class="absolute bottom-6 right-6 flex space-x-2">
                        <div class="w-8 h-1 bg-white rounded-full slide-indicator active" onclick="showSlide(1)"></div>
                        <div class="w-8 h-1 bg-white/30 rounded-full slide-indicator" onclick="showSlide(2)"></div>
                        <div class="w-8 h-1 bg-white/30 rounded-full slide-indicator" onclick="showSlide(3)"></div>
                    </div>
                </div>
            </div>
        </div>

            <script>
                // Desktop slides
                let currentSlide = 1;
                
                function showSlide(slideNumber) {
                    // Hide all slides
                    document.getElementById('slide1').style.opacity = '0';
                    document.getElementById('slide2').style.opacity = '0';
                    document.getElementById('slide3').style.opacity = '0';
                    
                    // Remove active class from all indicators
                    document.querySelectorAll('.slide-indicator').forEach(indicator => {
                        indicator.classList.remove('active');
                        indicator.classList.add('bg-white/30');
                        indicator.classList.remove('bg-white');
                    });
                    
                    // Show selected slide
                    document.getElementById('slide' + slideNumber).style.opacity = '1';
                    
                    // Add active class to selected indicator
                    const activeIndicator = document.querySelectorAll('.slide-indicator')[slideNumber - 1];
                    activeIndicator.classList.add('active', 'bg-white');
                    activeIndicator.classList.remove('bg-white/30');
                    
                    currentSlide = slideNumber;
                }
                
                // Auto-advance desktop slides every 5 seconds
                setInterval(() => {
                    currentSlide = currentSlide >= 3 ? 1 : currentSlide + 1;
                    showSlide(currentSlide);
                }, 5000);

                // Mobile slides
                let currentSlideMobile = 1;
                
                function showSlideMobile(slideNumber) {
                    // Hide all mobile slides
                    document.getElementById('slide1-mobile').style.opacity = '0';
                    document.getElementById('slide2-mobile').style.opacity = '0';
                    document.getElementById('slide3-mobile').style.opacity = '0';
                    
                    // Remove active class from all mobile indicators
                    document.querySelectorAll('.slide-indicator-mobile').forEach(indicator => {
                        indicator.classList.remove('active');
                        indicator.classList.add('bg-white/30');
                        indicator.classList.remove('bg-white');
                    });
                    
                    // Show selected mobile slide
                    document.getElementById('slide' + slideNumber + '-mobile').style.opacity = '1';
                    
                    // Add active class to selected mobile indicator
                    const activeIndicator = document.querySelectorAll('.slide-indicator-mobile')[slideNumber - 1];
                    activeIndicator.classList.add('active', 'bg-white');
                    activeIndicator.classList.remove('bg-white/30');
                    
                    currentSlideMobile = slideNumber;
                }
                
                // Auto-advance mobile slides every 5 seconds
                setInterval(() => {
                    currentSlideMobile = currentSlideMobile >= 3 ? 1 : currentSlideMobile + 1;
                    showSlideMobile(currentSlideMobile);
                }, 5000);

                // Toggle mobile menu with animations
                function toggleMobileMenu() {
                    const mobileMenu = document.getElementById('mobileMenu');
                    const hamburgerBtn = document.getElementById('hamburgerBtn');
                    
                    if (mobileMenu && hamburgerBtn) {
                        const isOpen = mobileMenu.classList.contains('show');
                        
                        if (isOpen) {
                            // Close menu
                            mobileMenu.classList.remove('show');
                            hamburgerBtn.classList.remove('active');
                        } else {
                            // Open menu
                            mobileMenu.classList.add('show');
                            hamburgerBtn.classList.add('active');
                        }
                    }
                }

                // Infinite scroll carousel for mobile features
                const featuresCarousel = document.getElementById('featuresCarousel');
                if (featuresCarousel) {
                    // Pause on hover
                    featuresCarousel.addEventListener('mouseenter', () => {
                        featuresCarousel.style.animationPlayState = 'paused';
                    });
                    
                    featuresCarousel.addEventListener('mouseleave', () => {
                        featuresCarousel.style.animationPlayState = 'running';
                    });
                    
                    // Pause on touch
                    featuresCarousel.addEventListener('touchstart', () => {
                        featuresCarousel.style.animationPlayState = 'paused';
                    });
                    
                    featuresCarousel.addEventListener('touchend', () => {
                        setTimeout(() => {
                            featuresCarousel.style.animationPlayState = 'running';
                        }, 2000);
                    });
                    
                    // Reset animation when it completes to avoid glitches
                    featuresCarousel.addEventListener('animationiteration', () => {
                        // Animation resets automatically due to infinite loop
                    });
                }
            </script>
        </div>
    </section>


    <!-- Features Grid Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <!-- Mobile: Carousel -->
            <div class="block sm:hidden">
                <div class="relative -mx-6">
                    <div class="overflow-hidden">
                        <div class="flex animate-scroll gap-4" id="featuresCarousel">
                            <!-- Feature 1 -->
                            <div class="w-72 flex-shrink-0">
                                <div class="project-card">
                                    <div class="project-image" style="background-image: url('https://www.abcz.org.br/thumb/blog/1/1180/663/d1aa6411acca5ba6ee4355decce0e080.jpeg');">
                                        <div class="project-overlay">
                                            <h3 class="text-lg font-bold mb-2">Gestão de Rebanho</h3>
                                            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-gray-800" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Feature 2 -->
                            <div class="w-72 flex-shrink-0">
                                <div class="project-card">
                                    <div class="project-image" style="background-image: url('https://www.universodasaudeanimal.com.br/wp-content/uploads/sites/57/2023/09/Monitoreo-de-animales_-Conoce-las-herramientas-MSD-scaled.jpg?w=1024');">
                                        <div class="project-overlay">
                                            <div class="text-right">
                                                <span class="text-2xl font-bold text-white">02</span>
                                                <h3 class="text-lg font-bold mt-2">Controle de Produção</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Feature 3 -->
                            <div class="w-72 flex-shrink-0">
                                <div class="project-card">
                                    <div class="project-image" style="background-image: url('https://agromogiana.com.br/wp-content/uploads/2022/01/manejo-sanitario.jpg');">
                                        <div class="project-overlay">
                                            <div class="text-right">
                                            <span class="text-2xl font-bold text-white">03</span>
                                            <h3 class="text-lg font-bold mt-2">Monitoramento Sanitário</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Feature 4 -->
                            <div class="w-72 flex-shrink-0">
                                <div class="project-card">
                                    <div class="project-image" style="background-image: url('https://www.bimeda.com.br/images/easyblog_articles/122/iatf-e-excelente-estrategia-para-reproducao-de-bovinos-de-corte-e-leite-mas-produtor-precisa-estar-atento-para-ter-sucesso-capa.jpg');">
                                        <div class="project-overlay">
                                            <div class="text-right">
                                                <span class="text-2xl font-bold text-white">04</span>
                                                <h3 class="text-lg font-bold mt-2">Análise de Reprodução</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Duplicate set for infinite scroll -->
                            <!-- Feature 1 -->
                            <div class="w-72 flex-shrink-0">
                                <div class="project-card">
                                    <div class="project-image" style="background-image: url('https://www.abcz.org.br/thumb/blog/1/1180/663/d1aa6411acca5ba6ee4355decce0e080.jpeg');">
                                        <div class="project-overlay">
                                            <h3 class="text-lg font-bold mb-2">Gestão de Rebanho</h3>
                                            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-gray-800" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Feature 2 -->
                            <div class="w-72 flex-shrink-0">
                                <div class="project-card">
                                    <div class="project-image" style="background-image: url('https://www.universodasaudeanimal.com.br/wp-content/uploads/sites/57/2023/09/Monitoreo-de-animales_-Conoce-las-herramientas-MSD-scaled.jpg?w=1024');">
                                        <div class="project-overlay">
                                            <div class="text-right">
                                                <span class="text-2xl font-bold text-white">02</span>
                                                <h3 class="text-lg font-bold mt-2">Controle de Produção</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Feature 3 -->
                            <div class="w-72 flex-shrink-0">
                                <div class="project-card">
                                    <div class="project-image" style="background-image: url('https://agromogiana.com.br/wp-content/uploads/2022/01/manejo-sanitario.jpg');">
                                        <div class="project-overlay">
                                            <div class="text-right">
                                            <span class="text-2xl font-bold text-white">03</span>
                                            <h3 class="text-lg font-bold mt-2">Monitoramento Sanitário</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Feature 4 -->
                            <div class="w-72 flex-shrink-0">
                                <div class="project-card">
                                    <div class="project-image" style="background-image: url('https://www.bimeda.com.br/images/easyblog_articles/122/iatf-e-excelente-estrategia-para-reproducao-de-bovinos-de-corte-e-leite-mas-produtor-precisa-estar-atento-para-ter-sucesso-capa.jpg');">
                                        <div class="project-overlay">
                                            <div class="text-right">
                                                <span class="text-2xl font-bold text-white">04</span>
                                                <h3 class="text-lg font-bold mt-2">Análise de Reprodução</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <!-- Desktop: Grid Layout -->
            <div class="hidden sm:grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1 -->
                <div class="project-card animate-on-scroll">
                    <div class="project-image" style="background-image: url('https://www.abcz.org.br/thumb/blog/1/1180/663/d1aa6411acca5ba6ee4355decce0e080.jpeg');">
                        <div class="project-overlay">
                            <h3 class="text-lg font-bold mb-2">Gestão de Rebanho</h3>
                            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-800" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Feature 2 -->
                <div class="project-card animate-on-scroll" style="animation-delay: 0.1s;">
                    <div class="project-image" style="background-image: url('https://www.universodasaudeanimal.com.br/wp-content/uploads/sites/57/2023/09/Monitoreo-de-animales_-Conoce-las-herramientas-MSD-scaled.jpg?w=1024');">
                        <div class="project-overlay">
                            <div class="text-right">
                                <span class="text-2xl font-bold text-white">02</span>
                                <h3 class="text-lg font-bold mt-2">Controle de Produção</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Feature 3 -->
                <div class="project-card animate-on-scroll" style="animation-delay: 0.2s;">
                    <div class="project-image" style="background-image: url('https://agromogiana.com.br/wp-content/uploads/2022/01/manejo-sanitario.jpg');">
                        <div class="project-overlay">
                            <div class="text-right">
                                <span class="text-2xl font-bold text-white">03</span>
                                <h3 class="text-lg font-bold mt-2">Monitoramento Sanitário</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Feature 4 -->
                <div class="project-card animate-on-scroll" style="animation-delay: 0.3s;">
                    <div class="project-image" style="background-image: url('https://www.bimeda.com.br/images/easyblog_articles/122/iatf-e-excelente-estrategia-para-reproducao-de-bovinos-de-corte-e-leite-mas-produtor-precisa-estar-atento-para-ter-sucesso-capa.jpg');">
                        <div class="project-overlay">
                            <div class="text-right">
                                <span class="text-2xl font-bold text-white">04</span>
                                <h3 class="text-lg font-bold mt-2">Análise de Reprodução</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Why Trust Us Section -->
    <section id="about" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <!-- Left Content -->
                <div class="animate-on-scroll">
                    <h2 class="text-4xl font-bold text-gray-800 mb-6">
                        Por Que Fazendeiros e Empresas Confiam em Nós
                    </h2>
                    <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                        Nossa experiência de mais de 10 anos no mercado de tecnologia agropecuária nos permite oferecer soluções que realmente fazem a diferença na produtividade e gestão das fazendas leiteiras.
                    </p>
                </div>
                
                <!-- Right Content - Features Grid -->
                <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-2 gap-6 animate-on-scroll trust-icons-grid">
                    <!-- Feature 1 -->
                    <div class="text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-800" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Designs Premiados</h3>
                    </div>
                    
                    <!-- Feature 2 -->
                    <div class="text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-800" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Ideias Inovadoras</h3>
                    </div>
                    
                    <!-- Feature 3 -->
                    <div class="text-center">
                        <div class="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Entrega no Prazo</h3>
                    </div>
                    
                    <!-- Feature 4 -->
                    <div class="text-center">
                        <div class="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Método Eco-Consciente</h3>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 bg-white relative overflow-hidden">
        <div class="relative z-10 max-w-7xl mx-auto px-6">
            <div class="text-center mb-16 animate-on-scroll">
                <h2 class="text-4xl font-bold text-gray-800 mb-6">
                    Mudando o Jogo na Pecuária com Práticas Sustentáveis e Tecnologias Avançadas
                </h2>
                <p class="text-xl text-gray-600 max-w-4xl mx-auto">
                    Moldando o futuro da agricultura através de inovação, sustentabilidade e tecnologia de ponta.
                </p>
            </div>
            
            <!-- Video Section -->
            <div class="video-container animate-on-scroll" id="videoContainer" style="transform: translateZ(0); backface-visibility: hidden;">
                <video class="w-full h-full object-cover" id="videoPlayer" loop muted playsinline preload="metadata" style="transform: translateZ(0);">
                    <source src="./assets/video/videobg12.mp4" type="video/mp4">
                    Seu navegador não suporta o elemento de vídeo.
                </video>
                
                <!-- Play/Pause Button -->
                <div class="video-control-btn" id="videoControlBtn">
                    <svg id="playIcon" class="w-6 h-6" fill="rgba(255, 255, 255, 0.7)" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path>
                    </svg>
                    <svg id="pauseIcon" class="w-6 h-6 hidden" fill="rgba(255, 255, 255, 0.7)" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black text-white rounded-t-3xl overflow-hidden">
        <!-- Image Section -->
        <div class="relative p-3 sm:p-6">
            <img src="./assets/video/vacafooter.jpg" alt="Vacas no Campo" class="w-full h-48 sm:h-64 lg:h-80 object-cover rounded-2xl sm:rounded-3xl mx-auto">
            <div class="absolute inset-3 sm:inset-6 bg-black/30 rounded-2xl sm:rounded-3xl"></div>
        </div>
        
        <!-- Top Banner Section -->
        <div class="bg-black py-8">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
                    <!-- Logo -->
                    <div class="flex items-center space-x-3">
                        <!-- Logo LacTech -->
                        <img src="./assets/video/lactechbranca.png" alt="LacTech Logo" class="w-10 h-10">
                        <span class="text-2xl font-bold">
                            <span class="text-white">Lac</span><span class="text-white">Tech</span>
                        </span>
                    </div>
                    
                    <!-- Green Banner -->
                    <div class="flex items-center bg-gray-800 px-6 py-3 rounded-xl">
                        <p class="text-white font-medium mr-4">
                            Na LacTech, revolucionamos a gestão leiteira com tecnologia avançada.
                        </p>
                        <button class="bg-white text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition-colors whitespace-nowrap">
                            Saiba Mais
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Footer Content -->
        <div class="bg-black py-16">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid lg:grid-cols-4 gap-8">
                    <!-- Gestão Column -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-6">Gestão</h3>
                        <ul class="space-y-3">
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Gerente de Fazenda</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Proprietário</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Veterinário</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Funcionário</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Consultor Técnico</a></li>
                        </ul>
                        
                        <div class="mt-8">
                            <h4 class="text-base font-bold text-white mb-4">Relatórios</h4>
                            <ul class="space-y-3">
                                <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Relatório de Produção</a></li>
                                <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Relatório Sanitário</a></li>
                                <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Relatório Financeiro</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Produtos Column -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-6">Produtos</h3>
                        <ul class="space-y-3">
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Gestão de Rebanho</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Controle de Produção</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Monitoramento Sanitário</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Análise de Reprodução</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Controle de Novilhas</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Dashboard Analítico</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Relatórios Avançados</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Alertas Inteligentes</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">API Integração</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">App Mobile</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Planos e Preços</a></li>
                        </ul>
                    </div>
                    
                    <!-- Recursos Column -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-6">Recursos</h3>
                        <ul class="space-y-3">
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Central de Ajuda</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Tutoriais</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Webinars</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Cases de Sucesso</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Parceiros Técnicos</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Blog do Agronegócio</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Documentação API</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Status do Sistema</a></li>
                        </ul>
                    </div>
                    
                    <!-- Suporte Column -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-6">Suporte</h3>
                        <ul class="space-y-3">
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Central de Atendimento</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Chat Online</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Agendar Demonstração</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Suporte Técnico</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Consultoria</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Facebook</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Instagram</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">LinkedIn</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">YouTube</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bottom Copyright Line -->
        <div class="bg-black border-t border-gray-800 py-6">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <p class="text-gray-400 text-sm">
                        © 2025 LacTech. Todos os direitos reservados.
                    </p>
                    <div class="flex items-center space-x-4 text-sm">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Política de Privacidade</a>
                        <span class="text-gray-600">|</span>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Termos e Condições</a>
                        <span class="text-gray-600">|</span>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Cookies</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Animate on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            observer.observe(el);
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add scroll effect to navigation
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            if (window.scrollY > 100) {
                nav.classList.add('bg-white/98');
            } else {
                nav.classList.remove('bg-white/98');
            }
        });

        // Video scroll effect and autoplay
        const videoContainer = document.getElementById('videoContainer');
        const videoPlayer = document.getElementById('videoPlayer');
        const videoControlBtn = document.getElementById('videoControlBtn');
        const playIcon = document.getElementById('playIcon');
        const pauseIcon = document.getElementById('pauseIcon');
        
        let isPlaying = false;
        let isUserControlled = false;
        
        // Play/Pause button functionality
        videoControlBtn.addEventListener('click', () => {
            isUserControlled = true;
            if (isPlaying) {
                videoPlayer.pause();
                playIcon.classList.remove('hidden');
                pauseIcon.classList.add('hidden');
                isPlaying = false;
            } else {
                videoPlayer.play();
                playIcon.classList.add('hidden');
                pauseIcon.classList.remove('hidden');
                isPlaying = true;
            }
        });
        
        // Video event listeners
        videoPlayer.addEventListener('play', () => {
            isPlaying = true;
            playIcon.classList.add('hidden');
            pauseIcon.classList.remove('hidden');
        });
        
        videoPlayer.addEventListener('pause', () => {
            isPlaying = false;
            playIcon.classList.remove('hidden');
            pauseIcon.classList.add('hidden');
        });
        
        // Otimização: throttle otimizado para manter animação suave
        let ticking = false;
        let lastKnownScrollPosition = 0;
        let lastUpdateTime = 0;
        const updateInterval = 16; // ~60fps
        
        function updateVideo(currentTime) {
            // Throttle: só atualiza a cada 16ms
            if (currentTime - lastUpdateTime < updateInterval) {
                ticking = false;
                return;
            }
            
            lastUpdateTime = currentTime;
            
            const rect = videoContainer.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            
            // Check if video is in viewport
            if (rect.top < windowHeight && rect.bottom > 0) {
                const scrollProgress = Math.max(0, Math.min(1, (windowHeight - rect.top) / windowHeight));
                
                // Resize effect com hysteresis para evitar flickering
                if (scrollProgress > 0.35) {
                    if (!videoContainer.classList.contains('scrolled')) {
                        videoContainer.classList.add('scrolled');
                    }
                } else if (scrollProgress < 0.25) {
                    if (videoContainer.classList.contains('scrolled')) {
                        videoContainer.classList.remove('scrolled');
                    }
                }
                
                // Autoplay effect (only if not user controlled)
                if (!isUserControlled) {
                    const videoInView = rect.top < windowHeight * 0.8 && rect.bottom > windowHeight * 0.2;
                    
                    if (videoInView && !isPlaying) {
                        videoPlayer.play().catch(e => console.log('Autoplay prevented:', e));
                    } else if (!videoInView && isPlaying) {
                        videoPlayer.pause();
                    }
                }
            } else if (!isUserControlled && isPlaying) {
                videoPlayer.pause();
            }
            
            ticking = false;
        }
        
        // Scroll effect com requestAnimationFrame otimizado
        window.addEventListener('scroll', () => {
            lastKnownScrollPosition = window.scrollY;
            
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(updateVideo);
            }
        }, { passive: true });
    </script>
</body>
</html>
