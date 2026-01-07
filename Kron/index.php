<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KRON - Kernel for Resilient Operating Nodes</title>
    <meta name="description" content="Construímos software que resolve problemas reais. SafeNode para segurança web. LacTech para gestão rural.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="asset/kron.png">
    <link rel="apple-touch-icon" href="asset/kron.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            background: #000000;
            color: #f5f5f7;
        }
        
        /* Smooth transitions */
        a, button {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Nav scroll effect */
        nav.scrolled {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: saturate(180%) blur(20px);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Product card hover */
        .product-card {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .product-card:hover {
            transform: translateY(-4px);
        }
        
        /* Button styles */
        .btn-primary {
            background: #ffffff;
            color: #000000;
            border-radius: 22px;
            padding: 8px 20px;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: -0.01em;
        }
        
        .btn-primary:hover {
            background: #f5f5f7;
            transform: scale(1.02);
        }
        
        .btn-secondary {
            color: #ffffff;
            font-size: 14px;
            font-weight: 400;
        }
        
        .btn-secondary:hover {
            text-decoration: underline;
            color: #f5f5f7;
        }
        
        /* Mobile menu */
        .mobile-menu {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.98);
            backdrop-filter: blur(20px);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .mobile-menu.active {
            display: flex;
            opacity: 1;
        }
        
        /* Smooth scroll reveal */
        @media (prefers-reduced-motion: no-preference) {
            .reveal {
                opacity: 0;
                transform: translateY(30px);
                transition: opacity 0.8s ease-out, transform 0.8s ease-out;
            }
            
            .reveal.revealed {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Hero phone container - cut off bottom */
        .hero-phone-container {
            overflow: hidden;
            height: 650px;
            max-height: 75vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            position: relative;
        }
        
        .hero-phone-container::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 150px;
            background: linear-gradient(to bottom, transparent, #000000);
            pointer-events: none;
            z-index: 1;
        }
        
        .hero-phone-container img {
            max-width: 100%;
            width: auto;
            max-height: 800px;
            height: auto;
            object-fit: contain;
            object-position: top center;
            display: block;
            position: relative;
            z-index: 0;
        }
        
        @media (min-width: 1024px) {
            .hero-phone-container {
                justify-content: flex-end;
            }
            
            .hero-phone-container img {
                max-width: 450px;
            }
        }
        
        /* Infinite carousel */
        @keyframes scroll {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-50%);
            }
        }
        
        .carousel-container {
            overflow: hidden;
            position: relative;
            width: 100%;
            mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
            -webkit-mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
        }
        
        .carousel-wrapper {
            display: flex;
            width: fit-content;
        }
        
        .carousel-track {
            animation: scroll 40s linear infinite;
            display: flex;
            gap: 4rem;
            will-change: transform;
            width: fit-content;
        }
        
        .carousel-track:hover {
            animation-play-state: paused;
        }
        
        .carousel-item {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 2rem;
            height: 40px;
            white-space: nowrap;
        }
        
        .carousel-item img {
            max-height: 40px;
            max-width: 150px;
            object-fit: contain;
        }
        
        /* Map Styles */
        #presence-map {
            background: #000000;
        }
        
        .leaflet-container {
            background: #000000 !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
        }
        
        .leaflet-popup-content-wrapper {
            background: #1a1a1a;
            border-radius: 12px;
            padding: 12px 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .leaflet-popup-content {
            margin: 0;
            font-size: 14px;
            font-weight: 500;
            color: #f5f5f7;
        }
        
        .leaflet-popup-tip {
            background: #1a1a1a;
        }
        
        /* Custom Marker with Flag */
        .custom-marker {
            background: transparent;
            border: none;
        }
        
        .marker-pin {
            width: 50px;
            height: 50px;
            background: white;
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            padding: 4px;
        }
        
        .marker-pin img {
            width: 32px;
            height: 24px;
            object-fit: cover;
            border-radius: 2px;
            display: block;
        }
        
        .marker-pin:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }
        
        /* Hide Leaflet controls */
        .leaflet-control-container {
            display: none !important;
        }
        
        .leaflet-control-attribution {
            display: none !important;
        }
    </style>
</head>
<body class="bg-black text-[#f5f5f7] antialiased">

    <!-- Navigation -->
    <nav id="main-nav" class="fixed top-0 w-full z-50 transition-all duration-300">
        <div class="max-w-6xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-1.5 md:-ml-8">
                    <img src="asset/kron.png" alt="KRON" class="h-6 md:h-7 w-auto">
                    <span class="text-[14px] md:text-[15px] font-medium tracking-tight text-white">KRON</span>
                </a>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="#produtos" class="text-[13px] font-normal text-white/70 hover:text-white transition-colors">Produtos</a>
                    <a href="#sobre" class="text-[13px] font-normal text-white/70 hover:text-white transition-colors">Sobre</a>
                    <a href="#contato" class="text-[13px] font-normal text-white/70 hover:text-white transition-colors">Contato</a>
                    <a href="login.php" class="text-[13px] font-normal text-white/70 hover:text-white transition-colors">Entrar</a>
                    <a href="login.php" class="btn-primary">Fale Conosco</a>
                </div>
                
                <!-- Mobile Menu Button -->
                <button id="menu-btn" class="md:hidden w-8 h-8 flex items-center justify-center" aria-label="Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Menu -->
    <div id="mobile-menu" class="mobile-menu flex-col items-center justify-center">
        <nav class="flex flex-col items-center gap-8 text-center">
            <a href="#produtos" class="text-2xl font-light">Produtos</a>
            <a href="#sobre" class="text-2xl font-light">Sobre</a>
            <a href="#contato" class="text-2xl font-light">Contato</a>
            <a href="login.php" class="text-2xl font-light">Entrar</a>
            <a href="login.php" class="btn-primary mt-4">Fale Conosco</a>
        </nav>
        <button id="close-menu" class="absolute top-6 right-6 w-8 h-8 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Hero Section -->
    <section class="pt-32 pb-0 lg:pt-40 lg:pb-0 px-6 relative">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-start">
                
                <!-- Left - Text Content -->
                <div class="reveal">
                    <h1 class="text-5xl lg:text-6xl xl:text-7xl font-light tracking-tight mb-6 leading-[1.1]">
                        Estamos<br>
                        revolucionando<br>
                        a tecnologia<br>
                        <span class="font-medium">empresarial</span>
                    </h1>
                    <p class="text-lg lg:text-xl text-white/60 font-light mb-10 leading-relaxed">
                        Nossa missão é fornecer as ferramentas que você precisa para transformar seu negócio com tecnologia de ponta.
                    </p>
                    <div class="flex flex-col sm:flex-row items-start gap-4">
                        <a href="#produtos" class="btn-primary">Conheça Nossos Produtos</a>
                        <a href="login.php" class="btn-secondary">Entrar</a>
                    </div>
                </div>
                
                <!-- Right - Mobile Image (cut off) -->
                <div class="hero-phone-container reveal" style="transition-delay: 0.2s">
                    <img src="asset/telenode.png" alt="App Mobile KRON" class="drop-shadow-2xl">
                </div>
                
            </div>
        </div>
        
        <!-- Divider -->
        <div class="border-t border-white/10 mt-16 lg:mt-20"></div>
    </section>
    
    <!-- Partners Carousel -->
    <section class="py-12 lg:py-16 px-6 bg-black border-t border-white/10">
        <div class="carousel-container">
            <div class="carousel-wrapper">
                <div class="carousel-track">
                    <?php 
                    // Array de parceiros: ['nome' => 'Nome do Parceiro', 'logo' => 'caminho/para/logo.png']
                    // Se não tiver logo, pode usar apenas o nome como string
                    $partners = [
                        ['name' => 'Cloudflare', 'logo' => 'asset/partners/cloudflare.png'],
                        ['name' => 'LacTech', 'logo' => 'asset/partners/lactech.png'],
                        ['name' => 'SafeNode', 'logo' => 'asset/partners/safenode.png'],
                        ['name' => 'Prefeitura de Maranguape', 'logo' => 'asset/partners/maranguape.png'],
                        ['name' => 'KRON', 'logo' => 'asset/partners/kron.png'],
                    ];
                    // Duplicar múltiplas vezes para criar loop infinito perfeito
                    for($i = 0; $i < 4; $i++): 
                        foreach($partners as $partner):
                            $name = is_array($partner) ? $partner['name'] : $partner;
                            $logo = is_array($partner) && isset($partner['logo']) ? $partner['logo'] : null;
                    ?>
                    <div class="carousel-item">
                        <?php if($logo): ?>
                            <img src="<?= $logo ?>" alt="<?= $name ?>" class="h-8 w-auto opacity-40 hover:opacity-70 transition-opacity" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                            <span class="text-white/40 text-sm font-light whitespace-nowrap" style="display: none;"><?= $name ?></span>
                        <?php else: ?>
                            <span class="text-white/40 text-sm font-light whitespace-nowrap"><?= $name ?></span>
                        <?php endif; ?>
                    </div>
                    <?php 
                        endforeach;
                    endfor; 
                    ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="produtos" class="py-20 lg:py-32 px-6">
        <div class="max-w-6xl mx-auto">
            
            <!-- Section Header -->
            <div class="text-center mb-16 lg:mb-24">
                <h2 class="text-4xl lg:text-6xl font-light tracking-tight mb-4 reveal">
                    Dois produtos.<br>
                    <span class="font-medium">Mercados distintos.</span>
                </h2>
                <p class="text-lg lg:text-xl text-white/60 font-light max-w-2xl mx-auto reveal" style="transition-delay: 0.1s">
                    Soluções especializadas para diferentes necessidades. Tecnologia de ponta, simplicidade e eficiência em cada produto.
                </p>
            </div>
            
            <!-- Products Grid -->
            <div class="grid md:grid-cols-2 gap-8 lg:gap-12">
                
                <!-- SafeNode Card -->
                <div class="product-card bg-white/5 border border-white/10 rounded-3xl p-8 lg:p-10 reveal" style="transition-delay: 0.2s">
                    <div class="mb-6">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-400 text-xs font-medium mb-4 border border-blue-500/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                            Segurança Web
                        </div>
                        <h3 class="text-3xl lg:text-4xl font-medium tracking-tight mb-3 text-white">SafeNode</h3>
                        <p class="text-white/60 text-[15px] leading-relaxed mb-6">
                            Plataforma completa de segurança web integrada com Cloudflare. Proteção enterprise contra ataques DDoS, firewall de aplicação web, detecção de ameaças em tempo real e bloqueio automático de IPs maliciosos.
                        </p>
                    </div>
                    
                    <div class="space-y-3 mb-8">
                        <div class="flex items-center gap-3 text-sm text-white/70">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Integração nativa com Cloudflare</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-white/70">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Bloqueio automático de ameaças</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-white/70">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Dashboard de segurança em tempo real</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between pt-6 border-t border-white/10">
                        <a href="#safenode" class="text-sm text-white/70 hover:text-white transition-colors">Saiba mais</a>
                        <svg class="w-4 h-4 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
                
                <!-- LacTech Card -->
                <div class="product-card bg-white/5 border border-white/10 rounded-3xl p-8 lg:p-10 reveal" style="transition-delay: 0.3s">
                    <div class="mb-6">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-500/20 text-green-400 text-xs font-medium mb-4 border border-green-500/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                            Gestão Rural
                        </div>
                        <h3 class="text-3xl lg:text-4xl font-medium tracking-tight mb-3 text-white">LacTech</h3>
                        <p class="text-white/60 text-[15px] leading-relaxed mb-6">
                            Sistema completo de gestão para fazendas leiteiras. Controle de rebanho, produção de leite, monitoramento sanitário, gestão financeira e relatórios inteligentes para aumentar a produtividade.
                        </p>
                    </div>
                    
                    <div class="space-y-3 mb-8">
                        <div class="flex items-center gap-3 text-sm text-white/70">
                            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Gestão completa de rebanho</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-white/70">
                            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Controle de produção e qualidade</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-white/70">
                            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Relatórios e analytics avançados</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between pt-6 border-t border-white/10">
                        <a href="#lactech" class="text-sm text-white/70 hover:text-white transition-colors">Saiba mais</a>
                        <svg class="w-4 h-4 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
                
            </div>
        </div>
    </section>

    <!-- SafeNode Detail -->
    <section id="safenode" class="py-20 lg:py-32 px-6 border-t border-white/10">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-16 lg:gap-24 items-start">
                
                <!-- Left Content -->
                <div class="lg:sticky lg:top-24">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-400 text-xs font-medium mb-6 reveal border border-blue-500/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                        01 / Segurança
                    </div>
                    <h2 class="text-4xl lg:text-6xl font-light tracking-tight mb-6 reveal" style="transition-delay: 0.1s">
                        <span class="font-medium text-white">SafeNode</span>
                    </h2>
                    <p class="text-lg text-white/60 font-light leading-relaxed mb-8 reveal" style="transition-delay: 0.2s">
                        Plataforma de segurança web integrada com Cloudflare. Proteção enterprise para suas aplicações com firewall inteligente, detecção de ameaças em tempo real e resposta automatizada a ataques.
                    </p>
                    <div class="flex flex-wrap gap-4 reveal" style="transition-delay: 0.3s">
                        <a href="login.php" class="btn-primary">Acessar SafeNode</a>
                        <a href="#produtos" class="btn-secondary">Ver todos os produtos</a>
                    </div>
                </div>
                
                <!-- Right Features -->
                <div class="space-y-4">
                    <?php
                    $safenode_features = [
                        ['title' => 'Integração Cloudflare', 'desc' => 'Sincronização automática para proteção DDoS, WAF e gestão de DNS com monitoramento em tempo real de logs e eventos de segurança.'],
                        ['title' => 'Bloqueio Automático de IPs', 'desc' => 'Sistema inteligente de detecção e bloqueio automático de IPs maliciosos. Análise de padrões de ataque e resposta imediata a ameaças.'],
                        ['title' => 'Dashboard de Segurança', 'desc' => 'Painel completo com métricas de segurança, logs de incidentes, alertas em tempo real e histórico completo de eventos. Visualização clara de ameaças.'],
                        ['title' => 'Modo Sob Ataque', 'desc' => 'Ativação manual ou automática do modo de proteção máxima durante ataques. Níveis de segurança configuráveis por site com resposta adaptativa.'],
                        ['title' => 'Multi-Site Management', 'desc' => 'Gerencie múltiplos sites e domínios em uma única plataforma. Visão global ou individual por site com configurações personalizadas.'],
                    ];
                    
                    $delay = 0.4;
                    foreach($safenode_features as $feature):
                    ?>
                    <div class="p-6 rounded-2xl bg-white/5 border border-white/10 reveal" style="transition-delay: <?= $delay ?>s">
                        <h4 class="text-lg font-medium mb-2 text-white"><?= $feature['title'] ?></h4>
                        <p class="text-sm text-white/60 leading-relaxed"><?= $feature['desc'] ?></p>
                    </div>
                    <?php 
                    $delay += 0.1;
                    endforeach; 
                    ?>
                </div>
                
            </div>
        </div>
    </section>

    <!-- LacTech Detail -->
    <section id="lactech" class="py-20 lg:py-32 px-6 border-t border-white/10">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-16 lg:gap-24 items-start">
                
                <!-- Left Content -->
                <div class="lg:sticky lg:top-24">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-500/20 text-green-400 text-xs font-medium mb-6 reveal border border-green-500/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                        02 / Agro
                    </div>
                    <h2 class="text-4xl lg:text-6xl font-light tracking-tight mb-6 reveal" style="transition-delay: 0.1s">
                        <span class="font-medium text-white">LacTech</span>
                    </h2>
                    <p class="text-lg text-white/60 font-light leading-relaxed mb-8 reveal" style="transition-delay: 0.2s">
                        Sistema completo de gestão para fazendas leiteiras desenvolvido com tecnologia de ponta. Aumente a produtividade e eficiência através de ferramentas inteligentes e análises precisas do seu rebanho.
                    </p>
                    <div class="flex flex-wrap gap-4 reveal" style="transition-delay: 0.3s">
                        <a href="login.php" class="btn-primary">Acessar LacTech</a>
                        <a href="#produtos" class="btn-secondary">Ver todos os produtos</a>
                    </div>
                </div>
                
                <!-- Right Features -->
                <div class="space-y-4">
                    <?php
                    $lactech_features = [
                        ['title' => 'Gestão Completa de Rebanho', 'desc' => 'Cadastro detalhado de animais com genealogia, histórico reprodutivo, eventos sanitários, vacinações e controle de ciclo estral. Rastreabilidade completa de cada animal.'],
                        ['title' => 'Controle de Produção de Leite', 'desc' => 'Registro diário de ordenhas com volume, qualidade do leite (gordura, proteína, CCS), acompanhamento de metas de produção e análise de produtividade por animal ou lote.'],
                        ['title' => 'Monitoramento Sanitário', 'desc' => 'Controle de saúde animal com registro de tratamentos, diagnósticos, prevenção de doenças e alertas para vacinações e exames periódicos. Histórico médico completo.'],
                        ['title' => 'Gestão Financeira Integrada', 'desc' => 'Controle completo de custos operacionais, receitas, fluxo de caixa, rentabilidade por animal e análise de viabilidade econômica. Relatórios financeiros detalhados.'],
                        ['title' => 'Relatórios e Dashboards Inteligentes', 'desc' => 'Dashboards personalizados com métricas em tempo real, relatórios automáticos de produção, análises preditivas e insights para tomada de decisão estratégica.'],
                    ];
                    
                    $delay = 0.4;
                    foreach($lactech_features as $feature):
                    ?>
                    <div class="p-6 rounded-2xl bg-white/5 border border-white/10 reveal" style="transition-delay: <?= $delay ?>s">
                        <h4 class="text-lg font-medium mb-2 text-white"><?= $feature['title'] ?></h4>
                        <p class="text-sm text-white/60 leading-relaxed"><?= $feature['desc'] ?></p>
                    </div>
                    <?php 
                    $delay += 0.1;
                    endforeach; 
                    ?>
                </div>
                
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="sobre" class="py-20 lg:py-32 px-6 border-t border-white/10">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-4xl lg:text-6xl font-light tracking-tight mb-6 reveal text-white">
                Acreditamos que software<br>
                <span class="font-medium">deve ser invisível.</span>
            </h2>
            <p class="text-xl text-white/60 font-light leading-relaxed mb-16 reveal" style="transition-delay: 0.1s">
                Não construímos features por construir. Cada linha de código existe para resolver um problema real. Essa obsessão por simplicidade e eficiência guia tudo que fazemos na KRON.
            </p>
            
            <!-- Principles -->
            <div class="grid md:grid-cols-3 gap-8 text-left">
                <div class="reveal" style="transition-delay: 0.2s">
                    <h3 class="text-xl font-medium mb-3 text-white">Simplicidade</h3>
                    <p class="text-white/60 text-sm leading-relaxed">Interfaces intuitivas que não precisam de manual. Software que funciona sem complicação.</p>
                </div>
                <div class="reveal" style="transition-delay: 0.3s">
                    <h3 class="text-xl font-medium mb-3 text-white">Performance</h3>
                    <p class="text-white/60 text-sm leading-relaxed">Código otimizado para velocidade e eficiência. Resultados que fazem a diferença.</p>
                </div>
                <div class="reveal" style="transition-delay: 0.4s">
                    <h3 class="text-xl font-medium mb-3 text-white">Confiabilidade</h3>
                    <p class="text-white/60 text-sm leading-relaxed">Sistemas robustos e seguros. Você pode confiar que tudo vai funcionar quando precisar.</p>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mt-20 pt-16 border-t border-white/10">
                <?php
                $stats = [
                    ['value' => '2', 'label' => 'Produtos'],
                    ['value' => '500', 'label' => 'Clientes', 'suffix' => '+'],
                    ['value' => '99.9', 'label' => 'Uptime', 'suffix' => '%'],
                    ['value' => '24/7', 'label' => 'Suporte'],
                ];
                
                $delay = 0.5;
                foreach($stats as $stat):
                ?>
                <div class="reveal" style="transition-delay: <?= $delay ?>s">
                    <div class="text-3xl lg:text-4xl font-light mb-2 text-white">
                        <?= $stat['value'] ?><?= isset($stat['suffix']) ? $stat['suffix'] : '' ?>
                    </div>
                    <div class="text-sm text-white/60"><?= $stat['label'] ?></div>
                </div>
                <?php 
                $delay += 0.1;
                endforeach; 
                ?>
            </div>
        </div>
    </section>

    <!-- International Presence Section -->
    <section id="contato" class="py-20 lg:py-32 px-6 border-t border-white/10">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 lg:mb-20">
                <h2 class="text-4xl lg:text-6xl font-light tracking-tight mb-6 reveal text-white">
                    <span class="font-medium">Presença Internacional</span>
                </h2>
                <p class="text-xl text-white/60 font-light leading-relaxed max-w-2xl mx-auto reveal" style="transition-delay: 0.1s">
                    Atendemos clientes em diversos países ao redor do mundo
                </p>
            </div>
            
            <!-- Map Container -->
            <div class="relative w-full h-[500px] lg:h-[600px] rounded-3xl overflow-hidden bg-white/5 border border-white/10 reveal" style="transition-delay: 0.2s">
                <div id="presence-map" class="w-full h-full"></div>
            </div>
        </div>
    </section>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Footer -->
    <footer class="py-12 lg:py-16 px-6 border-t border-white/10">
        <div class="max-w-6xl mx-auto">
            <div class="grid md:grid-cols-4 gap-12 mb-12">
                <div>
                    <a href="index.php" class="flex items-center gap-2.5 mb-4">
                        <img src="asset/kron.png" alt="KRON" class="h-6 w-auto">
                        <span class="text-[15px] font-medium">KRON</span>
                    </a>
                    <p class="text-sm text-white/60 leading-relaxed">
                        Kernel for Resilient Operating Nodes. Construímos software que resolve problemas reais.
                    </p>
                </div>
                <div>
                    <h4 class="text-xs font-medium text-white/40 mb-4 uppercase tracking-wider">Produtos</h4>
                    <ul class="space-y-2">
                        <li><a href="#safenode" class="text-sm text-white/60 hover:text-white transition-colors">SafeNode</a></li>
                        <li><a href="#lactech" class="text-sm text-white/60 hover:text-white transition-colors">LacTech</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-xs font-medium text-white/40 mb-4 uppercase tracking-wider">Empresa</h4>
                    <ul class="space-y-2">
                        <li><a href="#sobre" class="text-sm text-white/60 hover:text-white transition-colors">Sobre</a></li>
                        <li><a href="#contato" class="text-sm text-white/60 hover:text-white transition-colors">Contato</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-xs font-medium text-white/40 mb-4 uppercase tracking-wider">Legal</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-sm text-white/60 hover:text-white transition-colors">Privacidade</a></li>
                        <li><a href="#" class="text-sm text-white/60 hover:text-white transition-colors">Termos</a></li>
                    </ul>
                </div>
            </div>
            <div class="pt-8 border-t border-white/10 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-xs text-white/40">© <?= date('Y') ?> KRON. Todos os direitos reservados.</p>
                <div class="flex items-center gap-4">
                    <a href="https://www.instagram.com/safenode/" target="_blank" rel="noopener noreferrer" class="text-white/40 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
                            <path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37zM17.5 6.5h.01" />
                        </svg>
                    </a>
                    <a href="https://github.com" target="_blank" rel="noopener noreferrer" class="text-white/40 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 00-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0020 4.77 5.07 5.07 0 0019.91 1S18.73.65 16 2.48a13.38 13.38 0 00-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 005 4.77a5.44 5.44 0 00-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 009 18.13V22" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Nav scroll effect
        const mainNav = document.getElementById('main-nav');
        function handleScroll() {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            mainNav.classList.toggle('scrolled', currentScroll > 20);
        }
        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();
        
        // Mobile menu
        const menuBtn = document.getElementById('menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const closeMenu = document.getElementById('close-menu');
        
        menuBtn?.addEventListener('click', () => {
            mobileMenu.classList.add('active');
        });
        
        closeMenu?.addEventListener('click', () => {
            mobileMenu.classList.remove('active');
        });
        
        mobileMenu?.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('active');
            });
        });
        
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offsetTop = target.offsetTop - 60;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Reveal on scroll
        const reveals = document.querySelectorAll('.reveal');
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        reveals.forEach(el => revealObserver.observe(el));
        
        // Image loaded check
        const mobileImg = document.querySelector('img[src="asset/telenode.png"]');
        
        if (mobileImg) {
            mobileImg.addEventListener('error', () => {
                console.warn('Imagem do celular não encontrada: asset/telenode.png');
            });
        }
        
        // Initialize Map
        document.addEventListener('DOMContentLoaded', function() {
            const mapElement = document.getElementById('presence-map');
            if (!mapElement) return;
            
            // Create map centered on South America
            const map = L.map('presence-map', {
                zoomControl: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false,
                dragging: false,
                touchZoom: false
            }).setView([-20, -55], 4);
            
            // Add dark tile layer
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: false,
                subdomains: 'abcd',
                maxZoom: 19
            }).addTo(map);
            
            // Countries data
            const locations = [
                { 
                    name: 'Brasil', 
                    lat: -14.2350, 
                    lng: -51.9253,
                    flag: 'asset/brasil.png'
                },
                { 
                    name: 'Chile', 
                    lat: -35.6751, 
                    lng: -71.5430,
                    flag: 'asset/chile.png'
                }
            ];
            
            // Custom marker icon with flag
            locations.forEach(location => {
                const markerIcon = L.divIcon({
                    className: 'custom-marker',
                    html: `<div class="marker-pin"><img src="${location.flag}" alt="${location.name}"></div>`,
                    iconSize: [50, 50],
                    iconAnchor: [25, 50]
                });
                
                const marker = L.marker([location.lat, location.lng], { 
                    icon: markerIcon 
                }).addTo(map);
                
                marker.bindPopup(`<div style="text-align: center;"><div style="margin-bottom: 8px;"><img src="${location.flag}" alt="${location.name}" style="width: 48px; height: 36px; object-fit: cover; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div><div style="font-weight: 500; font-size: 15px;">${location.name}</div></div>`);
            });
        });
    </script>

</body>
</html>
