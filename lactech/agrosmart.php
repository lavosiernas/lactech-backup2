<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LacTech - Sistema de Gestão para Pecuária Leiteira</title>
    <meta name="description" content="Sistema completo para gestão de fazendas leiteiras">
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/png">
    
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
        
        /* Video Scroll Effect */
        .video-container {
            width: 100vw;
            height: 80vh;
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            transition: all 0.3s ease;
            border-radius: 0;
            overflow: hidden;
        }
        
        .video-container.scrolled {
            width: calc(100% - 2rem);
            height: 60vh;
            margin-left: 1rem;
            margin-right: 1rem;
            border-radius: 20px;
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
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-sm border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-gray-800">LacTech</span>
                </div>
                
                <!-- Navigation Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#home" class="text-gray-600 hover:text-green-600 transition-colors">Home</a>
                    <a href="#about" class="text-gray-600 hover:text-green-600 transition-colors">Sobre Nós</a>
                    <a href="#features" class="text-gray-600 hover:text-green-600 transition-colors">Funcionalidades</a>
                    <a href="#portfolio" class="text-gray-600 hover:text-green-600 transition-colors">Portfolio</a>
                    <a href="#contact" class="text-gray-600 hover:text-green-600 transition-colors">Contato</a>
                </div>
                
                <!-- CTA Buttons -->
                <div class="flex items-center space-x-4">
                    <button class="px-4 py-2 text-gray-600 hover:text-green-600 transition-colors">Entrar</button>
                    <button class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Teste Grátis</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="relative min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 pt-20">
        <div class="max-w-7xl mx-auto px-6 py-16">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div class="animate-on-scroll">
                    <h1 class="text-5xl lg:text-6xl font-bold text-gray-800 mb-6 leading-tight">
                        Transforme Sua Fazenda em Uma
                        <span class="gradient-text">Obra-Prima Digital</span>
                    </h1>
                    <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                        Revolucione a gestão leiteira com tecnologia avançada, monitoramento inteligente e controle total do seu rebanho.
                    </p>
                    <button class="inline-flex items-center px-8 py-4 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all transform hover:scale-105 shadow-lg">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path>
                        </svg>
                        Agende Sua Consultoria Gratuita
                    </button>
                </div>
                
                <!-- Right Content - Image Layout -->
                <div class="relative animate-on-scroll">
                    <!-- Main Image -->
                    <div class="relative">
                        <img src="https://picsum.photos/800/400" alt="Gestão Leiteira Inteligente" class="w-full h-80 object-cover rounded-3xl shadow-2xl">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent rounded-3xl"></div>
                        <div class="absolute bottom-6 left-6 text-white">
                            <h3 class="text-xl font-bold mb-2">Soluções Personalizadas de Gestão</h3>
                            <p class="text-sm opacity-90">Tecnologia adaptada para sua fazenda</p>
                        </div>
                    </div>
                    
                    <!-- Overlapping Images -->
                    <div class="absolute -top-8 -right-8 animate-on-scroll" style="animation-delay: 0.2s;">
                        <img src="https://picsum.photos/300/200" alt="Monitoramento Inteligente" class="w-64 h-48 object-cover rounded-2xl shadow-lg">
                    </div>
                    
                    <div class="absolute -bottom-8 -left-8 animate-on-scroll" style="animation-delay: 0.4s;">
                        <img src="https://picsum.photos/250/180" alt="Análise de Dados" class="w-56 h-40 object-cover rounded-2xl shadow-lg">
                    </div>
                    
                    <!-- Portfolio Button -->
                    <div class="absolute bottom-6 right-6">
                        <button class="bg-white/90 backdrop-blur-sm text-gray-800 px-4 py-2 rounded-xl shadow-lg hover:bg-white transition-all">
                            Ver Nosso Portfolio
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Section -->
        <div class="max-w-7xl mx-auto px-6 pb-16">
            <div class="bg-white rounded-3xl shadow-xl p-8 animate-on-scroll">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium mb-4">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                        </svg>
                        2025
                    </div>
                    <div class="flex flex-wrap justify-center gap-4 mb-6">
                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Fazenda Orgânica</span>
                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Fazenda Automatizada</span>
                        <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">Fazenda Bio-Médica</span>
                    </div>
                    <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                        Avanços Tecnológicos na Pecuária Leiteira
                    </h2>
                    <p class="text-lg text-gray-600 max-w-4xl mx-auto">
                        Apesar dos avanços tecnológicos, a pecuária leiteira tradicional ainda revela ineficiências persistentes que podem ser resolvidas com nossa solução inteligente.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Grid Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1 -->
                <div class="project-card animate-on-scroll">
                    <div class="project-image" style="background-image: url('https://picsum.photos/400/300');">
                        <div class="project-overlay">
                            <h3 class="text-lg font-bold mb-2">Comece Agora Mesmo</h3>
                            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-800" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Feature 2 -->
                <div class="project-card animate-on-scroll" style="animation-delay: 0.1s;">
                    <div class="project-image" style="background-image: url('https://picsum.photos/400/300');">
                        <div class="project-overlay">
                            <div class="text-right">
                                <span class="text-2xl font-bold text-white">02</span>
                                <h3 class="text-lg font-bold mt-2">Irrigação Assistida por Tecnologia</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Feature 3 -->
                <div class="project-card animate-on-scroll" style="animation-delay: 0.2s;">
                    <div class="project-image" style="background-image: url('https://picsum.photos/400/300');">
                        <div class="project-overlay">
                            <div class="text-right">
                                <span class="text-2xl font-bold text-white">03</span>
                                <h3 class="text-lg font-bold mt-2">Monitoramento Agrícola</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Feature 4 -->
                <div class="project-card animate-on-scroll" style="animation-delay: 0.3s;">
                    <div class="project-image" style="background-image: url('https://picsum.photos/400/300');">
                        <div class="project-overlay">
                            <div class="text-right">
                                <span class="text-2xl font-bold text-white">04</span>
                                <h3 class="text-lg font-bold mt-2">Análise de Produtividade</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Work Section -->
    <section id="portfolio" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16 animate-on-scroll">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Veja Nosso Trabalho em Plena Floração</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Projetos que transformaram fazendas leiteiras em referências de excelência e produtividade
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Project 1 -->
                <div class="project-card animate-on-scroll">
                    <div class="project-image" style="background-image: url('https://picsum.photos/400/300');">
                        <div class="project-overlay">
                            <h3 class="text-xl font-bold mb-2">Fazenda Johnson</h3>
                            <p class="text-sm opacity-90">Fazenda com Design Minimalista</p>
                            <div class="flex items-center mt-2">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-xs">Localização</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Project 2 -->
                <div class="project-card animate-on-scroll" style="animation-delay: 0.1s;">
                    <div class="project-image" style="background-image: url('https://picsum.photos/400/300');">
                        <div class="project-overlay">
                            <h3 class="text-xl font-bold mb-2">Santuário Aspen</h3>
                            <p class="text-sm opacity-90">Sistema de Gestão Avançado</p>
                            <div class="flex items-center mt-2">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-xs">Localização</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Project 3 -->
                <div class="project-card animate-on-scroll" style="animation-delay: 0.2s;">
                    <div class="project-image" style="background-image: url('https://picsum.photos/400/300');">
                        <div class="project-overlay">
                            <h3 class="text-xl font-bold mb-2">Refúgio Willow</h3>
                            <p class="text-sm opacity-90">Monitoramento Inteligente</p>
                            <div class="flex items-center mt-2">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-xs">Localização</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Project 4 -->
                <div class="project-card animate-on-scroll" style="animation-delay: 0.3s;">
                    <div class="project-image" style="background-image: url('https://picsum.photos/400/300');">
                        <div class="project-overlay">
                            <h3 class="text-xl font-bold mb-2">Retiro Rosewood</h3>
                            <p class="text-sm opacity-90">Automação Completa</p>
                            <div class="flex items-center mt-2">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-xs">Localização</span>
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
                <div class="grid grid-cols-2 gap-6 animate-on-scroll">
                    <!-- Feature 1 -->
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Designs Premiados</h3>
                    </div>
                    
                    <!-- Feature 2 -->
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
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
    <section id="contact" class="py-20 bg-gray-50 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-green-50 to-emerald-50"></div>
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
            <div class="video-container animate-on-scroll" id="videoContainer">
                <video class="w-full h-full object-cover" id="videoPlayer" loop muted>
                    <source src="https://www.w3schools.com/html/mov_bbb.mp4" type="video/mp4">
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
        <div class="relative p-6">
            <img src="https://picsum.photos/1200/400" alt="Vacas no Campo" class="w-full h-64 object-cover rounded-3xl mx-auto">
            <div class="absolute inset-6 bg-black/30 rounded-3xl"></div>
        </div>
        
        <!-- Top Banner Section -->
        <div class="bg-black py-8">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
                    <!-- Logo -->
                    <div class="flex items-center space-x-3">
                        <!-- Ícone de cabeça de boi -->
                        <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.94-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                        </svg>
                        <span class="text-2xl font-bold text-white">Cultivo</span>
                    </div>
                    
                    <!-- Green Banner -->
                    <div class="flex items-center bg-green-600 px-6 py-3 rounded-xl">
                        <p class="text-white font-medium mr-4">
                            Na Cultivo, dedicamos 1% da nossa receita para eliminar dióxido de carbono.
                        </p>
                        <button class="bg-white text-green-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
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
                    <!-- Team Column -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-6">Equipe</h3>
                        <ul class="space-y-3">
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Equipes Jurídicas</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Partes Interessadas</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Equipes de Compras</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Consultores</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Equipes Operacionais</a></li>
                        </ul>
                        
                        <div class="mt-8">
                            <h4 class="text-base font-bold text-white mb-4">Funções do Usuário</h4>
                            <ul class="space-y-3">
                                <li><a href="#" class="text-white hover:text-green-400 transition-colors">Equipes Transacionais</a></li>
                                <li><a href="#" class="text-white hover:text-green-400 transition-colors">Equipes Jurídicas Colaborativas</a></li>
                                <li><a href="#" class="text-white hover:text-green-400 transition-colors">Não-equidade e Equidade</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Product Column -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-6">Produto</h3>
                        <ul class="space-y-3">
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Geração de Contratos</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Modelos Dinâmicos</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Fluxos de Aprovação</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Automação de Contratos</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Negociação de Contratos</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Assinatura Eletrônica</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Arquivamento de Contratos</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Monitoramento de Contratos</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Análise de Contratos</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Segurança</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Preços</a></li>
                        </ul>
                    </div>
                    
                    <!-- Resources Column -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-6">Recursos</h3>
                        <ul class="space-y-3">
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Leitura de Aprendizagem</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Depoimentos de Clientes</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Webinar</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Modelos Personalizados</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Parceiros</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Notícias</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Documentação</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Status</a></li>
                        </ul>
                    </div>
                    
                    <!-- Contact Column -->
                    <div>
                        <h3 class="text-lg font-bold text-white mb-6">Contato</h3>
                        <ul class="space-y-3">
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Email</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Agendar Reunião</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Serviços Jurídicos</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Imprensa</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Facebook</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">Twitter</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">LinkedIn</a></li>
                            <li><a href="#" class="text-white hover:text-green-400 transition-colors">YouTube</a></li>
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
                        © 2023 Cultivo. Todos os direitos reservados.
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
        
        // Scroll effect with autoplay
        window.addEventListener('scroll', () => {
            const rect = videoContainer.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            
            // Check if video is in viewport
            if (rect.top < windowHeight && rect.bottom > 0) {
                const scrollProgress = Math.max(0, Math.min(1, (windowHeight - rect.top) / windowHeight));
                
                // Resize effect
                if (scrollProgress > 0.3) {
                    videoContainer.classList.add('scrolled');
                } else {
                    videoContainer.classList.remove('scrolled');
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
                // Pause when completely out of view
                videoPlayer.pause();
            }
        });
    </script>
</body>
</html>
