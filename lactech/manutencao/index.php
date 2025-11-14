<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Otimizado -->
    <title>LacTech - Sistema em Manutenção | Melhorias em Andamento</title>
    <meta name="description" content="LacTech está temporariamente em manutenção para implementar melhorias significativas. Em breve retornaremos com uma experiência ainda melhor para gestão de fazendas leiteiras.">
    <meta name="keywords" content="lactech, lac tech, sistema leiteiro, gestão fazenda leiteira, controle rebanho bovino, produção leite, software pecuária, gestão gado leiteiro, controle ordenha, saúde animal, reprodução bovina, inseminação artificial, controle financeiro rural">
    <meta name="author" content="Xandria - LacTech">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://lactechsys.com/index.php">
    
    <!-- Open Graph -->
    <meta property="og:title" content="LacTech - Sistema em Manutenção">
    <meta property="og:description" content="LacTech está temporariamente em manutenção para implementar melhorias significativas. Em breve retornaremos com uma experiência ainda melhor.">
    <meta property="og:image" content="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <meta property="og:url" content="https://lactechsys.com/index.php">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="LacTech - Sistema em Manutenção">
    <meta name="twitter:description" content="LacTech está temporariamente em manutenção para implementar melhorias significativas.">
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
        
        /* Mobile horizontal scroll styling */
        .overflow-x-auto {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .overflow-x-auto::-webkit-scrollbar {
            display: none;
        }
        
        /* Smooth scroll for mobile */
        .overflow-x-auto {
            scroll-behavior: smooth;
        }
        
        /* Mobile feature cards */
        @media (max-width: 640px) {
            .project-card {
                min-width: 320px;
                height: 200px;
            }
            
            .pricing-card {
                min-width: 320px;
            }
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
                        <a href="#maintenance" class="menu-item text-gray-700 hover:text-gray-900 transition-colors py-2 border-b border-gray-100">Status</a>
                    </div>
                </div>
            </div>

            <!-- Mobile Text Content (above image) -->
            <div class="w-full px-4 sm:px-6 mb-6 lg:hidden">
                <!-- Main Headline -->
                <h1 class="text-3xl sm:text-4xl font-bold text-gray-800 leading-tight mb-4 text-center">
                    Sistema em
                    <br>
                    <span class="text-green-600">Manutenção</span>
                </h1>

                <!-- Status Badge -->
                <div class="flex items-center justify-center mb-6">
                    <div class="bg-green-100 text-green-800 px-4 py-2 rounded-full text-sm font-medium flex items-center">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
                        Melhorias em Andamento
                    </div>
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
                    Estamos implementando melhorias significativas no sistema para oferecer uma experiência ainda melhor. Em breve retornaremos com funcionalidades aprimoradas.
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4 justify-center">
                    <button onclick="requestNotificationPermission()" class="px-6 sm:px-8 py-3 sm:py-4 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all shadow-lg text-sm sm:text-base">
                        Ativar Notificações
                    </button>
                    <a href="#features" class="px-6 sm:px-8 py-3 sm:py-4 border-2 border-gray-300 text-gray-800 rounded-xl hover:bg-gray-100 transition-all text-sm sm:text-base text-center">
                        Ver Funcionalidades
                    </a>
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
                        <a href="#maintenance" class="text-gray-700 hover:text-gray-900 transition-colors mr-8">Status</a>
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
                    Sistema em
                    <br>
                    <span class="text-green-600">Manutenção</span>
                </h1>

                <!-- Status Badge -->
                <div class="flex items-center mb-6">
                    <div class="bg-green-100 text-green-800 px-6 py-3 rounded-full text-base font-medium flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-3 animate-pulse"></div>
                        Melhorias em Andamento
                    </div>
                </div>

                <!-- Description -->
                <p class="text-lg text-gray-600 mb-8 max-w-lg mt-8">
                    Estamos implementando melhorias significativas no sistema para oferecer uma experiência ainda melhor. Em breve retornaremos com funcionalidades aprimoradas.
                </p>

                <!-- CTA Buttons -->
                <div class="flex space-x-4">
                    <button onclick="requestNotificationPermission()" class="px-8 py-4 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all shadow-lg">
                        Ativar Notificações
                    </button>
                    <a href="#features" class="px-8 py-4 border-2 border-gray-300 text-gray-800 rounded-xl hover:bg-gray-100 transition-all text-center">
                        Ver Funcionalidades
                    </a>
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

            </script>
        </div>
    </section>


    <!-- Features Grid Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-6">
                    Funcionalidades do Sistema
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Descubra as principais funcionalidades que estarão disponíveis quando o sistema retornar
                </p>
            </div>
            
            <!-- Features Grid - Mobile Horizontal Scroll and Desktop Grid -->
            <div class="block sm:hidden">
                <!-- Mobile: Horizontal Scroll -->
                <div class="overflow-x-auto pb-4">
                    <div class="flex space-x-6 min-w-max">
                        <!-- Feature 1 -->
                        <div class="w-80 flex-shrink-0">
                            <div class="project-card">
                                <div class="project-image" style="background-image: url('https://www.abcz.org.br/thumb/blog/1/1180/663/d1aa6411acca5ba6ee4355decce0e080.jpeg');">
                                    <div class="project-overlay">
                                        <h3 class="text-lg font-bold mb-2 text-white">Gestão de Rebanho</h3>
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
                        <div class="w-80 flex-shrink-0">
                            <div class="project-card">
                                <div class="project-image" style="background-image: url('https://www.universodasaudeanimal.com.br/wp-content/uploads/sites/57/2023/09/Monitoreo-de-animales_-Conoce-las-herramientas-MSD-scaled.jpg?w=1024');">
                                    <div class="project-overlay">
                                        <div class="text-right">
                                            <span class="text-2xl font-bold text-white">02</span>
                                            <h3 class="text-lg font-bold mt-2 text-white">Controle de Produção</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Feature 3 -->
                        <div class="w-80 flex-shrink-0">
                            <div class="project-card">
                                <div class="project-image" style="background-image: url('https://agromogiana.com.br/wp-content/uploads/2022/01/manejo-sanitario.jpg');">
                                    <div class="project-overlay">
                                        <div class="text-right">
                                            <span class="text-2xl font-bold text-white">03</span>
                                            <h3 class="text-lg font-bold mt-2 text-white">Monitoramento Sanitário</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Feature 4 -->
                        <div class="w-80 flex-shrink-0">
                            <div class="project-card">
                                <div class="project-image" style="background-image: url('https://www.bimeda.com.br/images/easyblog_articles/122/iatf-e-excelente-estrategia-para-reproducao-de-bovinos-de-corte-e-leite-mas-produtor-precisa-estar-atento-para-ter-sucesso-capa.jpg');">
                                    <div class="project-overlay">
                                        <div class="text-right">
                                            <span class="text-2xl font-bold text-white">04</span>
                                            <h3 class="text-lg font-bold mt-2 text-white">Análise de Reprodução</h3>
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

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-6">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-800 mb-6">
                    Planos e Preços
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Escolha o plano ideal para sua fazenda leiteira
                </p>
            </div>
            
            <!-- Pricing Cards -->
            <div class="block md:hidden">
                <!-- Mobile: Horizontal Scroll -->
                <div class="overflow-x-auto pb-4">
                    <div class="flex space-x-6 min-w-max">
                        <!-- Basic Plan -->
                        <div class="w-80 flex-shrink-0">
                            <div class="pricing-card">
                                <div class="text-center mb-8">
                                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Básico</h3>
                                    <div class="text-4xl font-bold text-gray-800 mb-2">R$ 199</div>
                                    <p class="text-gray-600">por mês</p>
                                </div>
                                <ul class="space-y-4 mb-8">
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Até 50 animais
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Controle de produção
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Relatórios básicos
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Suporte por email
                                    </li>
                                </ul>
                                <button class="w-full px-6 py-3 border-2 border-gray-300 text-gray-800 rounded-xl hover:bg-gray-100 transition-all">
                                    Aguardando Retorno
                                </button>
                            </div>
                        </div>
                        
                        <!-- Professional Plan -->
                        <div class="w-80 flex-shrink-0">
                            <div class="pricing-card featured">
                                <div class="text-center mb-8">
                                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Profissional</h3>
                                    <div class="text-4xl font-bold text-gray-800 mb-2">R$ 399</div>
                                    <p class="text-gray-600">por mês</p>
                                </div>
                                <ul class="space-y-4 mb-8">
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Até 200 animais
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Todas as funcionalidades
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Relatórios avançados
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Suporte prioritário
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        App mobile
                                    </li>
                                </ul>
                                <button class="w-full px-6 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all">
                                    Aguardando Retorno
                                </button>
                            </div>
                        </div>
                        
                        <!-- Enterprise Plan -->
                        <div class="w-80 flex-shrink-0">
                            <div class="pricing-card">
                                <div class="text-center mb-8">
                                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Empresarial</h3>
                                    <div class="text-4xl font-bold text-gray-800 mb-2">R$ 799</div>
                                    <p class="text-gray-600">por mês</p>
                                </div>
                                <ul class="space-y-4 mb-8">
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Animais ilimitados
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Múltiplas fazendas
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        API personalizada
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Suporte 24/7
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Consultoria incluída
                                    </li>
                                </ul>
                                <button class="w-full px-6 py-3 border-2 border-gray-300 text-gray-800 rounded-xl hover:bg-gray-100 transition-all">
                                    Aguardando Retorno
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Desktop: Grid Layout -->
            <div class="hidden md:grid md:grid-cols-3 gap-8">
                <!-- Basic Plan -->
                <div class="pricing-card animate-on-scroll">
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Básico</h3>
                        <div class="text-4xl font-bold text-gray-800 mb-2">R$ 199</div>
                        <p class="text-gray-600">por mês</p>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Até 50 animais
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Controle de produção
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Relatórios básicos
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Suporte por email
                        </li>
                    </ul>
                    <button class="w-full px-6 py-3 border-2 border-gray-300 text-gray-800 rounded-xl hover:bg-gray-100 transition-all">
                        Aguardando Retorno
                    </button>
                </div>
                
                <!-- Professional Plan -->
                <div class="pricing-card featured animate-on-scroll">
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Profissional</h3>
                        <div class="text-4xl font-bold text-gray-800 mb-2">R$ 399</div>
                        <p class="text-gray-600">por mês</p>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Até 200 animais
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Todas as funcionalidades
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Relatórios avançados
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Suporte prioritário
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            App mobile
                        </li>
                    </ul>
                    <button class="w-full px-6 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all">
                        Aguardando Retorno
                    </button>
                </div>
                
                <!-- Enterprise Plan -->
                <div class="pricing-card animate-on-scroll">
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Empresarial</h3>
                        <div class="text-4xl font-bold text-gray-800 mb-2">R$ 799</div>
                        <p class="text-gray-600">por mês</p>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Animais ilimitados
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Múltiplas fazendas
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            API personalizada
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Suporte 24/7
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Consultoria incluída
                        </li>
                    </ul>
                    <button class="w-full px-6 py-3 border-2 border-gray-300 text-gray-800 rounded-xl hover:bg-gray-100 transition-all">
                        Aguardando Retorno
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <!-- Left Content -->
                <div class="animate-on-scroll">
                    <h2 class="text-4xl font-bold text-gray-800 mb-6">
                        Sobre o LacTech
                    </h2>
                    <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                        O LacTech é uma solução completa de gestão para fazendas leiteiras, desenvolvida com tecnologia de ponta para revolucionar a pecuária leiteira no Brasil. Nossa missão é aumentar a produtividade e eficiência dos produtores através de ferramentas inteligentes e análises precisas.
                    </p>
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-800 mb-2">Tecnologia Avançada</h3>
                                <p class="text-gray-600">Utilizamos inteligência artificial e análise de dados para otimizar cada aspecto da gestão leiteira.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                                <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-800 mb-2">Experiência Comprovada</h3>
                                <p class="text-gray-600">Mais de 10 anos de experiência no mercado agropecuário, com centenas de fazendas atendidas.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                                <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-800 mb-2">Suporte Especializado</h3>
                                <p class="text-gray-600">Equipe técnica especializada em pecuária leiteira, pronta para auxiliar em qualquer desafio.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Content - Stats -->
                <div class="grid grid-cols-2 gap-8 animate-on-scroll">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-600 mb-2">500+</div>
                        <p class="text-gray-600">Fazendas Atendidas</p>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-600 mb-2">50K+</div>
                        <p class="text-gray-600">Animais Monitorados</p>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-600 mb-2">10+</div>
                        <p class="text-gray-600">Anos de Experiência</p>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-green-600 mb-2">99%</div>
                        <p class="text-gray-600">Satisfação dos Clientes</p>
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

    <!-- Maintenance Status Section -->
    <section id="maintenance" class="py-20 bg-gray-50">
        <div class="max-w-2xl mx-auto px-6 text-center">
            <div class="animate-on-scroll">
                <!-- Status Icon -->
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-8">
                    <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                
                <!-- Title -->
                <h2 class="text-3xl font-bold text-gray-800 mb-12">
                    Sistema em Manutenção
                </h2>
                
                <!-- Timer Card -->
                <div class="bg-gradient-to-br from-white to-gray-50 rounded-2xl p-12 shadow-xl border border-gray-100 mb-8 relative overflow-hidden">
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 opacity-5">
                        <div class="absolute top-4 right-4 w-32 h-32 bg-green-500 rounded-full blur-3xl"></div>
                        <div class="absolute bottom-4 left-4 w-24 h-24 bg-blue-500 rounded-full blur-2xl"></div>
                    </div>
                    
                    <!-- Countdown Display -->
                    <div class="relative z-10">
                        <div class="text-8xl font-black text-transparent bg-clip-text bg-gradient-to-r from-gray-800 via-gray-700 to-gray-800 mb-6 font-mono tracking-wider drop-shadow-sm" id="countdown">19:00:00</div>
                        <p class="text-xl text-gray-600 mb-10 font-medium">Tempo estimado para o fim da manutenção</p>
                        
                        <!-- Progress Bar Container -->
                        <div class="relative mb-8">
                            <div class="w-full bg-gray-200 rounded-full h-3 shadow-inner">
                                <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-1000 shadow-lg" id="progressBar" style="width: 0%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 mt-2">
                                <span>Início</span>
                                <span class="font-medium">Progresso</span>
                                <span>Conclusão</span>
                            </div>
                        </div>
                        
                        <!-- Status Info -->
                        <div class="flex flex-wrap justify-center gap-6 text-sm">
                            <div class="flex items-center space-x-2 bg-green-50 px-4 py-2 rounded-full">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-green-700 font-medium">Sistema Ativo</span>
                            </div>
                            <div class="flex items-center space-x-2 bg-blue-50 px-4 py-2 rounded-full">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                <span class="text-blue-700 font-medium">19h Duração</span>
                            </div>
                            <div class="flex items-center space-x-2 bg-purple-50 px-4 py-2 rounded-full">
                                <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                                <span class="text-purple-700 font-medium">Timer Persistente</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Notification Button -->
                <div class="flex justify-center">
                    <button id="notificationBtn" onclick="requestNotificationPermission()" class="px-8 py-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium text-lg shadow-lg hover:shadow-xl">
                        🔔 Ativar Notificações Web Push
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-20 bg-white relative overflow-hidden">
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
                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Produtos Column -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-6">Produtos</h3>
                        <ul class="space-y-3">
                            <li><a href="#features" class="text-white hover:text-gray-400 transition-colors">Funcionalidades</a></li>
                            <li><a href="#pricing" class="text-white hover:text-gray-400 transition-colors">Planos e Preços</a></li>
                            <li><a href="#about" class="text-white hover:text-gray-400 transition-colors">Sobre o LacTech</a></li>
                        </ul>
                    </div>
                    
                    <!-- Suporte Column -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-6">Suporte</h3>
                        <ul class="space-y-3">
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Central de Ajuda</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Status do Sistema</a></li>
                            <li><a href="#" class="text-white hover:text-gray-400 transition-colors">Contato</a></li>
                        </ul>
                    </div>
                    
                    <!-- Legal Column -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-6">Legal</h3>
                        <ul class="space-y-3">
                            <li><a href="politica-privacidade.php" class="text-white hover:text-gray-400 transition-colors">Política de Privacidade</a></li>
                            <li><a href="termos-condicoes.php" class="text-white hover:text-gray-400 transition-colors">Termos e Condições</a></li>
                            <li><a href="cookies.php" class="text-white hover:text-gray-400 transition-colors">Cookies</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bottom Copyright Line -->
        <div class="bg-black border-t border-gray-800 py-6">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-2xl">🇧🇷</span>
                        <p class="text-gray-400 text-sm">
                            © 2025 LacTech. Todos os direitos reservados.
                        </p>
                    </div>
                    <div class="flex items-center space-x-4 text-sm">
                        <a href="politica-privacidade.php" class="text-gray-400 hover:text-white transition-colors">Política de Privacidade</a>
                        <span class="text-gray-600">|</span>
                        <a href="termos-condicoes.php" class="text-gray-400 hover:text-white transition-colors">Termos e Condições</a>
                        <span class="text-gray-600">|</span>
                        <a href="cookies.php" class="text-gray-400 hover:text-white transition-colors">Cookies</a>
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

        // Countdown Timer - 19 horas com persistência
        const MAINTENANCE_DURATION = 19 * 60 * 60; // 19 horas em segundos
        const STORAGE_KEY = 'lactech_maintenance_timer';
        
        // Inicializar timer com persistência
        function initializeTimer() {
            const now = Math.floor(Date.now() / 1000);
            const startTime = localStorage.getItem(STORAGE_KEY);
            
            if (!startTime) {
                // Primeira vez - definir tempo de início
                localStorage.setItem(STORAGE_KEY, now.toString());
                return MAINTENANCE_DURATION;
            }
            
            const elapsed = now - parseInt(startTime);
            const remaining = MAINTENANCE_DURATION - elapsed;
            
            return Math.max(remaining, 0); // Não pode ser negativo
        }
        
        let totalSeconds = initializeTimer();
        const maxSeconds = MAINTENANCE_DURATION;
        
        function updateCountdown() {
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            
            const countdownElement = document.getElementById('countdown');
            const progressBar = document.getElementById('progressBar');
            
            if (countdownElement) {
                countdownElement.textContent = 
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
            
            // Atualizar barra de progresso
            if (progressBar) {
                const progress = ((maxSeconds - totalSeconds) / maxSeconds) * 100;
                progressBar.style.width = `${Math.min(progress, 100)}%`;
            }
            
            if (totalSeconds > 0) {
                totalSeconds--;
            } else {
                // Manutenção concluída
                if (countdownElement) {
                    countdownElement.textContent = "00:00:00";
                    countdownElement.className = "text-7xl font-bold text-green-600 mb-4 font-mono tracking-wider";
                }
                if (progressBar) {
                    progressBar.style.width = "100%";
                    progressBar.className = "bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-1000 shadow-lg";
                }
                
                // Limpar localStorage quando concluído
                localStorage.removeItem(STORAGE_KEY);
            }
        }
        
        // Atualizar countdown a cada segundo
        setInterval(updateCountdown, 1000);
        
        // Função para resetar o timer (para debug/admin)
        function resetMaintenanceTimer() {
            localStorage.removeItem(STORAGE_KEY);
            totalSeconds = MAINTENANCE_DURATION;
            console.log('🔄 Timer de manutenção resetado para 19 horas');
        }
        
        // Função para verificar status do timer
        function getTimerStatus() {
            const startTime = localStorage.getItem(STORAGE_KEY);
            if (!startTime) {
                return 'Timer não iniciado';
            }
            
            const now = Math.floor(Date.now() / 1000);
            const elapsed = now - parseInt(startTime);
            const remaining = MAINTENANCE_DURATION - elapsed;
            
            const hours = Math.floor(remaining / 3600);
            const minutes = Math.floor((remaining % 3600) / 60);
            
            return `Tempo restante: ${hours}h ${minutes}min`;
        }
        
        // Log do status do timer no console
        console.log('⏰ Status do timer:', getTimerStatus());
        
        // WEB PUSH NOTIFICATIONS NATIVAS
        let notificationInterval = null;
        let registration = null;
        
        // Registrar Service Worker
        async function registerServiceWorker() {
            if ('serviceWorker' in navigator) {
                try {
                    registration = await navigator.serviceWorker.register('./sw.js');
                    console.log('Service Worker registrado:', registration);
                    return registration;
                } catch (error) {
                    console.error('Erro ao registrar Service Worker:', error);
                    return null;
                }
            }
            return null;
        }
        
        // Solicitar permissão para notificações web push
        async function requestNotificationPermission() {
            console.log('🔔 Solicitando permissão para Web Push...');
            
            // Atualizar botão imediatamente para mostrar que foi clicado
            updateButtonToActivating();
            
            // Aguardar um pouco para mostrar o estado "Ativando"
            await new Promise(resolve => setTimeout(resolve, 500));
            
            // Registrar Service Worker primeiro
            if (!registration) {
                registration = await registerServiceWorker();
                if (!registration) {
                    alert('❌ Erro ao registrar Service Worker. Use Chrome, Firefox ou Edge para notificações nativas.');
                    resetButton();
                    return;
                }
            }
            
            if (!('Notification' in window)) {
                alert('❌ Seu navegador não suporta notificações web push. Use Chrome, Firefox ou Edge.');
                resetButton();
                return;
            }
            
            if (Notification.permission === 'granted') {
                showWebPushNotification('✅ Notificações Web Push Ativadas!', 'Você receberá notificações nativas sobre a manutenção.');
                startMaintenanceNotifications();
                updateButtonToActivated();
                return;
            }
            
            if (Notification.permission === 'denied') {
                alert('🔒 Notificações foram bloqueadas. Para ativar:\n\n1. Clique no ícone de notificação (🔔) na barra de endereços\n2. Selecione "Permitir notificações"\n3. Recarregue a página\n\nOu acesse as configurações do navegador.');
                resetButton();
                return;
            }
            
            try {
                const permission = await Notification.requestPermission();
                console.log('📱 Permissão de notificação:', permission);
                
                if (permission === 'granted') {
                    showWebPushNotification('🎉 Notificações Web Push Ativadas!', 'Você receberá notificações nativas sobre a manutenção a cada hora.');
                    startMaintenanceNotifications();
                    updateButtonToActivated();
                    
                    // Teste automático após 2 segundos
                    setTimeout(() => {
                        showWebPushNotification('🧪 Teste de Notificação', 'Se você está vendo esta notificação nativa, o sistema está funcionando perfeitamente!');
                    }, 2000);
                } else {
                    alert('🔒 Notificações bloqueadas. Para ativar:\n\n1. Clique no ícone de notificação (🔔) na barra de endereços\n2. Selecione "Permitir notificações"\n3. Recarregue a página\n\nOu acesse as configurações do navegador.');
                    resetButton();
                }
            } catch (error) {
                console.error('❌ Erro ao solicitar permissão:', error);
                alert('❌ Erro ao ativar notificações web push. Tente novamente ou use um navegador compatível.');
                resetButton();
            }
        }
        
        // Enviar notificação web push nativa
        function showWebPushNotification(title, body) {
            console.log('🔔 Enviando notificação web push:', title, body);
            
            if (Notification.permission !== 'granted') {
                console.log('❌ Permissão de notificação não concedida');
                return;
            }
            
            // Usar Service Worker para notificação nativa
            if (registration && registration.active) {
                console.log('📱 Enviando via Service Worker...');
                registration.active.postMessage({
                    type: 'SHOW_NOTIFICATION',
                    title: title,
                    body: body,
                    icon: 'https://i.postimg.cc/vmrkgDcB/lactech.png',
                    badge: 'https://i.postimg.cc/vmrkgDcB/lactech.png',
                    tag: 'lactech-maintenance',
                    requireInteraction: true,
                    actions: [
                        {
                            action: 'open',
                            title: 'Abrir Sistema'
                        },
                        {
                            action: 'close',
                            title: 'Fechar'
                        }
                    ]
                });
            } else {
                console.log('⚠️ Service Worker não ativo, usando fallback...');
                // Fallback para notificação simples
                try {
                    const notification = new Notification(title, {
                        body: body,
                        icon: 'https://i.postimg.cc/vmrkgDcB/lactech.png',
                        badge: 'https://i.postimg.cc/vmrkgDcB/lactech.png',
                        tag: 'lactech-maintenance',
                        requireInteraction: true
                    });
                    
                    notification.onclick = function() {
                        window.focus();
                        notification.close();
                    };
                    
                    console.log('✅ Notificação fallback criada');
                } catch (error) {
                    console.error('❌ Erro ao criar notificação fallback:', error);
                }
            }
        }
        
        // Iniciar notificações de manutenção
        function startMaintenanceNotifications() {
            console.log('Iniciando notificações web push de manutenção...');
            
            // Limpar intervalo anterior se existir
            if (notificationInterval) {
                clearInterval(notificationInterval);
            }
            
            // Notificação a cada hora (3600000ms) - para teste, usar 30 segundos
            notificationInterval = setInterval(() => {
                const hoursLeft = Math.floor(totalSeconds / 3600);
                const minutesLeft = Math.floor((totalSeconds % 3600) / 60);
                
                if (totalSeconds > 0) {
                    showWebPushNotification(
                        'LacTech - Status da Manutenção',
                        `Sistema ainda em manutenção. Tempo restante: ${hoursLeft}h ${minutesLeft}min. Continue acompanhando!`
                    );
                } else {
                    showWebPushNotification(
                        'LacTech - Manutenção Concluída!',
                        'O sistema está de volta! Acesse agora e aproveite as melhorias implementadas.'
                    );
                    clearInterval(notificationInterval);
                }
            }, 30000); // 30 segundos para teste (mude para 3600000 para produção)
        }
        
        // Função de teste de notificação web push (integrada)
        function testNotification() {
            console.log('🧪 Testando notificação web push...');
            
            if (Notification.permission === 'granted') {
                showWebPushNotification(
                    '🧪 Teste de Notificação Web Push',
                    'Se você está vendo esta notificação nativa, o sistema está funcionando perfeitamente!'
                );
            } else {
                alert('🔔 Primeiro ative as notificações clicando em "Ativar Notificações Web Push"');
            }
        }
        
        // Atualizar botão para estado "Ativando"
        function updateButtonToActivating() {
            console.log('🔄 Atualizando botão para "Ativando"...');
            const button = document.getElementById('notificationBtn');
            console.log('🔍 Botão encontrado:', button);
            if (button) {
                button.innerHTML = '⏳ Ativando Notificações...';
                button.className = 'px-8 py-4 bg-yellow-600 text-white rounded-lg font-medium text-lg shadow-lg cursor-default';
                button.onclick = null; // Desabilita cliques durante ativação
                console.log('✅ Botão atualizado para "Ativando"');
            } else {
                console.error('❌ Botão não encontrado!');
            }
        }
        
        // Atualizar botão para estado "Ativado"
        function updateButtonToActivated() {
            console.log('✅ Atualizando botão para "Ativado"...');
            const button = document.getElementById('notificationBtn');
            console.log('🔍 Botão encontrado para ativar:', button);
            if (button) {
                button.innerHTML = '✅ Notificações Web Push Ativas';
                button.className = 'px-8 py-4 bg-green-700 text-white rounded-lg font-medium text-lg shadow-lg cursor-default';
                button.onclick = testNotification; // Permite testar notificações
                console.log('✅ Botão atualizado para "Ativado"');
            } else {
                console.error('❌ Botão não encontrado para ativar!');
            }
        }
        
        // Resetar botão para estado original
        function resetButton() {
            const button = document.getElementById('notificationBtn');
            if (button) {
                button.innerHTML = '🔔 Ativar Notificações Web Push';
                button.className = 'px-8 py-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium text-lg shadow-lg hover:shadow-xl';
                button.onclick = requestNotificationPermission;
            }
        }
        
        // Atualizar botão (função original mantida para compatibilidade)
        function updateButton() {
            updateButtonToActivated();
        }
        
        // Inicializar ao carregar a página
        document.addEventListener('DOMContentLoaded', async function() {
            console.log('Inicializando Web Push Notifications...');
            
            // Registrar Service Worker
            registration = await registerServiceWorker();
            
            console.log('Status inicial da notificação:', Notification.permission);
            
            if (Notification.permission === 'granted') {
                startMaintenanceNotifications();
                updateButtonToActivated();
            } else {
                // Garantir que o botão está no estado inicial
                resetButton();
            }
        });
    </script>
</body>
</html>