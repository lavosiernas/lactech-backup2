<?php
/**
 * AgroNews360 - Portal de Notícias do Agronegócio
 * Sistema informativo com notícias, cotações e previsões climáticas
 * Domínio: agronews360.online
 */

// Iniciar sessão ANTES de qualquer output (para login opcional)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sistema público - não requer autenticação
$articleId = intval($_GET['id'] ?? 0);
$isLoggedIn = isset($_SESSION['agronews_logged_in']) && $_SESSION['agronews_logged_in'];
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $articleId ? 'Notícia | ' : ''; ?>AgroNews360 - Notícias do Agronegócio</title>
    <meta name="description" content="Portal de notícias do agronegócio com atualizações sobre pecuária, agricultura, cotações de mercado, previsões climáticas e muito mais.">
    <link rel="icon" href="assets/img/agro360.png" type="image/png">
    
    <!-- Google Fonts - Tipografia Profissional para Jornais -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;900&family=Lato:wght@300;400;700;900&family=Merriweather:wght@300;400;700;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'agro-green': '#22c55e',
                        'agro-blue': '#3b82f6',
                        'agro-yellow': '#eab308',
                        'agro-orange': '#f97316',
                    },
                    fontFamily: {
                        'display': ['Playfair Display', 'serif'],
                        'body': ['Lato', 'sans-serif'],
                        'serif': ['Merriweather', 'serif'],
                    }
                }
            }
        }
    </script>
    
    <style>
        :root {
            --agro-green: #22c55e;
            --agro-orange: #f97316;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Lato', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-weight: 400;
            line-height: 1.7;
            color: #1a1a1a;
            background: #fafbfc;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            scroll-behavior: smooth;
        }
        
        /* Melhorar performance de scroll */
        html {
            scroll-behavior: smooth;
        }
        
        /* Otimizar renderização */
        .premium-card,
        .news-card {
            contain: layout style paint;
        }
        
        /* Tipografia Minimalista */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            line-height: 1.3;
            letter-spacing: -0.01em;
        }
        
        .headline {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            line-height: 1.25;
            letter-spacing: -0.02em;
        }
        
        .article-title {
            font-family: 'Playfair Display', serif;
            font-weight: 600;
            line-height: 1.4;
        }
        
        .body-text {
            font-family: 'Lato', sans-serif;
            font-weight: 400;
            line-height: 1.7;
        }
        
        .serif-text {
            font-family: 'Merriweather', serif;
            line-height: 1.8;
        }
        
        /* Modo Leitura */
        body.reading-mode {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
        }
        
        body.reading-mode header,
        body.reading-mode aside,
        body.reading-mode footer {
            display: none;
        }
        
        body.reading-mode main {
            padding: 3rem 2rem;
        }
        
        /* Cards Minimalistas */
        .news-card {
            cursor: pointer;
        }
        
        /* Hero Section Minimalista */
        .hero-overlay {
            background: rgba(0, 0, 0, 0.4);
        }
        
        /* Badge de Categoria Minimalista Melhorado */
        .category-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            background: #f3f4f6;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #374151;
            transition: all 0.2s ease;
        }
        
        .trending-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }
        
        /* Botão Minimalista */
        .read-more-btn {
            /* Sem efeitos */
        }
        
        /* Toggle Button Minimalista */
        .reading-toggle {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            background: #22c55e;
        }
        
        /* Scrollbar Minimalista */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
        
        /* Article Content */
        .article-content {
            font-family: 'Merriweather', serif;
            font-size: 1.125rem;
            line-height: 1.9;
            color: #374151;
        }
        
        .article-content p {
            margin-bottom: 1.5rem;
            text-align: justify;
        }
        
        .article-content p:first-of-type {
            font-size: 1.25rem;
            font-weight: 400;
            line-height: 1.8;
            color: #4b5563;
            font-style: italic;
        }
        
        .article-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.875rem;
            font-weight: 700;
            margin-top: 2.5rem;
            margin-bottom: 1.25rem;
            color: #111827;
            line-height: 1.3;
        }
        
        .article-content h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .article-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 2rem 0;
        }
        
        .article-content blockquote {
            border-left: 3px solid #22c55e;
            padding-left: 1.25rem;
            margin: 1.5rem 0;
            font-style: italic;
            color: #4b5563;
            font-size: 1.125rem;
        }
        
        /* Loading States Minimalistas */
        .loading {
            opacity: 0.6;
        }
        
        /* Author Card Minimalista */
        .author-card {
            background: #f9fafb;
            border-left: 3px solid #22c55e;
        }
        
        /* Share Buttons Minimalistas */
        .share-btn {
            /* Sem efeitos */
        }
        
        /* Trending Badge Minimalista */
        .trending-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #f97316;
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            z-index: 10;
        }
        
        /* Time Badge Minimalista */
        .time-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            background: #f3f4f6;
            border-radius: 4px;
            font-size: 0.8125rem;
            color: #6b7280;
        }
        
        /* Card Minimalista Melhorado */
        .premium-card {
            background: white;
            border-radius: 12px;
            border: 1px solid rgba(229, 231, 235, 0.6);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        
        .premium-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }
        
        .news-card {
            transition: transform 0.2s ease;
        }
        
        .news-card:active {
            transform: scale(0.98);
        }
        
        /* Section Title Minimalista */
        .section-title {
            position: relative;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 48px;
            height: 2px;
            background: #22c55e;
        }
        
        /* Header Minimalista */
        header {
            /* Sem efeitos */
        }
        
        /* Estados de Focus Minimalistas */
        *:focus-visible {
            outline: 2px solid #22c55e;
            outline-offset: 2px;
        }
        
        /* Loading Spinner Minimalista */
        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid #e5e7eb;
            border-top-color: #22c55e;
            border-radius: 50%;
        }
        
        /* Transições Suaves */
        * {
            transition-property: color, background-color, border-color, transform, box-shadow;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
        
        /* Melhorias de Performance */
        img {
            will-change: transform;
            image-rendering: -webkit-optimize-contrast;
        }
        
        /* Responsive Typography e Mobile */
        @media (max-width: 768px) {
            .headline {
                font-size: 2rem;
            }
            
            .article-content {
                font-size: 1rem;
            }
            
            .premium-card {
                border-radius: 10px;
            }
            
            .premium-card:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
            
            .category-badge {
                font-size: 0.625rem;
                padding: 0.25rem 0.5rem;
            }
            
            /* Garantir que cards em grid fiquem bem espaçados */
            .grid {
                gap: 0.75rem;
            }
            
            /* Melhorar legibilidade em mobile */
            body {
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }
        }
        
        /* Ajustes para telas muito pequenas */
        @media (max-width: 480px) {
            .premium-card {
                border-radius: 8px;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
            
            .premium-card:hover {
                transform: none;
            }
        }
        
        /* Animações de entrada suaves */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.4s ease-out;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Header Minimalista -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="index.php" class="flex items-center space-x-3">
                    <img src="assets/img/agro360.png" alt="AgroNews360" class="h-10 w-10 object-contain">
                    <span class="text-xl font-display font-bold text-gray-900">AgroNews360</span>
                </a>
                
                <!-- Navegação Desktop -->
                <nav class="hidden lg:flex items-center space-x-8">
                    <a href="index.php" class="text-sm font-body font-medium text-gray-700 hover:text-gray-900">Home</a>
                    <a href="#noticias" class="text-sm font-body font-medium text-gray-700 hover:text-gray-900">Notícias</a>
                    <a href="#cotacoes" class="text-sm font-body font-medium text-gray-700 hover:text-gray-900">Cotações</a>
                    <a href="#clima" class="text-sm font-body font-medium text-gray-700 hover:text-gray-900">Clima</a>
                    <a href="#sobre" class="text-sm font-body font-medium text-gray-700 hover:text-gray-900">Sobre</a>
                </nav>
                
                <!-- Menu Mobile -->
                <nav id="mobileMenu" class="lg:hidden hidden absolute top-full left-0 right-0 bg-white border-b border-gray-200 shadow-lg z-50">
                    <div class="px-4 py-4 space-y-0">
                        <a href="index.php" class="block text-sm font-body font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 py-3 px-2 border-b border-gray-100 transition-colors">Home</a>
                        <a href="#noticias" class="block text-sm font-body font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 py-3 px-2 border-b border-gray-100 transition-colors">Notícias</a>
                        <a href="#cotacoes" class="block text-sm font-body font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 py-3 px-2 border-b border-gray-100 transition-colors">Cotações</a>
                        <a href="#clima" class="block text-sm font-body font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 py-3 px-2 border-b border-gray-100 transition-colors">Clima</a>
                        <a href="#sobre" class="block text-sm font-body font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 py-3 px-2 transition-colors">Sobre</a>
                    </div>
                </nav>
                
                <!-- Controles -->
                <div class="flex items-center space-x-3">
                    <?php
                    if ($isLoggedIn):
                    ?>
                        <div class="flex items-center space-x-3">
                            <span class="text-sm font-body text-gray-700">Olá, <?php echo htmlspecialchars($_SESSION['agronews_user_name'] ?? 'Usuário'); ?></span>
                            <a href="api/auth.php?action=logout" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-900 font-body font-medium text-sm">
                                Sair
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 rounded-md text-white font-body font-medium text-sm">
                            Entrar
                        </a>
                        <button class="px-5 py-2 bg-agro-green hover:bg-green-600 rounded-md text-white font-body font-medium text-sm">
                            Assinar
                        </button>
                    <?php endif; ?>
                    <button id="mobileMenuToggle" class="lg:hidden p-2 hover:bg-gray-100 rounded-md transition-colors" aria-label="Menu">
                        <svg id="menuIcon" class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg id="closeIcon" class="w-6 h-6 text-gray-700 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Barra Secundária Minimalista -->
    <div class="bg-gray-50 border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-center h-12">
                <span class="text-xs font-body text-gray-600">Cadastre-se e receba as principais notícias do agronegócio</span>
            </div>
        </div>
    </div>
    
    <!-- Conteúdo Principal -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- Seção de Artigo Individual (quando há ID) -->
        <div id="articleDetailSection" class="hidden">
            <div class="mb-8">
                <button onclick="showMainContent()" class="inline-flex items-center space-x-2 text-agro-green hover:text-green-600 font-body font-semibold transition-colors group">
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Voltar para notícias</span>
                </button>
            </div>
            
            <div id="articleDetailContent">
                <!-- Será preenchido via JavaScript -->
            </div>
        </div>
        
        <!-- Seção Principal de Notícias -->
        <div id="mainContentSection">
            
            <!-- Layout Principal: Desktop com Grid 3 Colunas, Mobile Otimizado -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8 mb-12">
                
                <!-- Artigo Principal (Esquerda - 2 colunas no desktop) -->
                <div class="lg:col-span-2">
                    <div id="mainArticle" class="premium-card overflow-hidden">
                        <div class="text-center py-12 md:py-16">
                            <div class="inline-block w-8 h-8 md:w-10 md:h-10 border-4 border-agro-green border-t-transparent rounded-full animate-spin"></div>
                            <p class="mt-3 md:mt-4 text-sm md:text-base text-gray-600 font-body">Carregando artigo principal...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Coluna Lateral (Direita - 1 coluna no desktop) -->
                <div class="lg:col-span-1">
                    <div id="sidebarArticles" class="space-y-6">
                        <!-- Mobile: Grid de 2 colunas -->
                        <div class="grid grid-cols-2 lg:grid-cols-1 gap-3 lg:gap-6">
                            <div class="text-center py-8 md:py-12 col-span-2 lg:col-span-1">
                                <div class="inline-block w-6 h-6 border-4 border-agro-green border-t-transparent rounded-full animate-spin"></div>
                                <p class="mt-2 text-xs md:text-sm text-gray-500 font-body">Carregando artigos...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Artigos em Destaque (2 colunas desktop, 2 mobile) -->
            <div class="mb-12">
                <h2 class="section-title text-2xl lg:text-3xl font-display font-bold text-gray-900 mb-6 lg:mb-8">Artigos em Destaque</h2>
                <div id="featuredArticles" class="grid grid-cols-2 lg:grid-cols-2 gap-4 lg:gap-8">
                    <div class="text-center py-12">
                        <div class="inline-block w-6 h-6 border-4 border-agro-green border-t-transparent rounded-full animate-spin"></div>
                        <p class="mt-2 text-sm text-gray-500 font-body">Carregando destaques...</p>
                    </div>
                </div>
            </div>
            
            <!-- Seção de Informações do Lactech e Gráficos -->
            <div class="mb-12">
                <h2 class="section-title text-2xl font-display font-bold text-gray-900 mb-6">Informações do Lactech</h2>
                
                <div class="mb-8">
                    <!-- Gráfico de Preço do Leite -->
                    <div class="premium-card max-w-4xl mx-auto">
                        <div class="p-6">
                            <h3 class="text-lg font-display font-bold text-gray-900 mb-4">Preço do Leite (Últimos 30 dias)</h3>
                            <div class="h-64">
                                <canvas id="milkPriceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Card de Preço do Leite -->
                <div class="mb-8">
                    <div class="premium-card p-6 max-w-md mx-auto">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm text-gray-600 font-body mb-1">Preço Atual do Leite</p>
                                <p class="text-3xl font-display font-bold text-agro-green" id="currentMilkPrice">Carregando...</p>
                                <p class="text-xs text-gray-500 mt-1" id="milkPriceChange">-</p>
                            </div>
                            <div class="w-16 h-16 rounded-full bg-agro-green/10 flex items-center justify-center ml-4">
                                <svg class="w-8 h-8 text-agro-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Últimas Notícias -->
            <div class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="section-title text-2xl font-display font-bold text-gray-900">Últimas Notícias</h2>
                    <button id="showMoreBtn" class="hidden text-agro-orange hover:text-orange-600 font-body font-bold text-sm uppercase tracking-wide transition-colors">
                        Ver Mais →
                    </button>
                </div>
                
                <!-- Filtros Minimalistas -->
                <div class="mb-8 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <select id="categoryFilter" class="px-4 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-agro-green focus:border-agro-green bg-white text-gray-900 font-body font-medium text-sm">
                        <option value="">Todas as categorias</option>
                    </select>
                    <div class="relative flex-1 max-w-md">
                        <input type="text" id="searchInput" placeholder="Buscar notícias..." 
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-agro-green focus:border-agro-green bg-white text-gray-900 placeholder-gray-400 font-body text-sm">
                        <svg class="absolute left-3 top-3 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
                
                <!-- Grid de Notícias -->
                <div id="latestArticles" class="grid grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
                    <div class="text-center py-16 col-span-full">
                        <div class="spinner mx-auto"></div>
                        <p class="mt-4 text-gray-600 font-body">Carregando notícias...</p>
                    </div>
                </div>
                
                <!-- Paginação -->
                <div id="pagination" class="mt-12 flex justify-center items-center space-x-3 hidden">
                    <!-- Será preenchido via JavaScript -->
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer Minimalista -->
    <footer class="bg-white border-t border-gray-200 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-8">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <img src="assets/img/agro360.png" alt="AgroNews360" class="h-10 w-10 object-contain">
                        <span class="text-xl font-display font-bold text-gray-900">AgroNews360</span>
                    </div>
                    <p class="text-gray-600 text-sm font-body leading-relaxed">Portal de notícias do agronegócio com informações atualizadas sobre mercado, clima e tecnologia.</p>
                </div>
                <div>
                    <h4 class="font-display font-bold text-base mb-4 text-gray-900">Categorias</h4>
                    <ul class="space-y-2 text-sm font-body">
                        <li><a href="#noticias" class="text-gray-600 hover:text-gray-900">Pecuária</a></li>
                        <li><a href="#noticias" class="text-gray-600 hover:text-gray-900">Agricultura</a></li>
                        <li><a href="#noticias" class="text-gray-600 hover:text-gray-900">Mercado</a></li>
                        <li><a href="#noticias" class="text-gray-600 hover:text-gray-900">Tecnologia</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-display font-bold text-base mb-4 text-gray-900">Links</h4>
                    <ul class="space-y-2 text-sm font-body">
                        <li><a href="#cotacoes" class="text-gray-600 hover:text-gray-900">Cotações</a></li>
                        <li><a href="#clima" class="text-gray-600 hover:text-gray-900">Clima</a></li>
                        <li><a href="#sobre" class="text-gray-600 hover:text-gray-900">Sobre Nós</a></li>
                        <li><a href="#contato" class="text-gray-600 hover:text-gray-900">Contato</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-display font-bold text-base mb-4 text-gray-900">Newsletter</h4>
                    <p class="text-gray-600 text-sm font-body mb-3">Receba notícias exclusivas</p>
                    <form class="space-y-2">
                        <input type="email" placeholder="Seu e-mail" 
                               class="w-full px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-agro-green focus:border-transparent font-body text-sm">
                        <button type="submit" class="w-full px-4 py-2 bg-agro-green hover:bg-green-600 rounded-md text-white font-body font-medium text-sm">
                            Inscrever-se
                        </button>
                    </form>
                </div>
            </div>
            <div class="border-t border-gray-200 mt-8 pt-6 text-center">
                <p class="text-gray-500 text-sm font-body">&copy; <?php echo date('Y'); ?> AgroNews360. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    
    <!-- Botão Flutuante Modo Leitura -->
    <button id="readingModeToggle" class="reading-toggle" title="Modo Leitura">
        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </svg>
    </button>
    
    <script>
        const AGRO_NEWS_API = 'api/agronews.php';
        const articleId = <?php echo $articleId; ?>;
        
        // Variáveis globais
        let currentPage = 1;
        let currentCategory = '';
        let currentSearch = '';
        let categories = [];
        
        // Aguardar DOM estar pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
        
        function init() {
            // Verificar se deve mostrar artigo individual
            if (articleId) {
                showArticleDetail(articleId);
            } else {
                loadMainContent();
            }
            
            // Inicializar event listeners
            initEventListeners();
            
            // Carregar dados do Lactech e criar gráficos
            loadLactechData();
            
            // Sincronizar dados na primeira carga (apenas se não houver artigos)
            checkAndSyncData();
        }
        
        // Não precisa mais verificar/sincronizar - dados vêm direto da web
        function checkAndSyncData() {
            // Dados vêm direto da web, não precisa sincronizar
        }
        
        // Carregar conteúdo principal
        async function loadMainContent() {
            document.getElementById('articleDetailSection').classList.add('hidden');
            document.getElementById('mainContentSection').classList.remove('hidden');
            
            // Carregar categorias primeiro (rápido)
            await loadCategories();
            
            // Carregar em paralelo para melhor performance
            await Promise.all([
                loadMainArticle(),
                loadSidebarArticles(),
                loadFeaturedArticles()
            ]);
            
            // Carregar últimas notícias por último
            loadLatestArticles();
        }
        
        // Mostrar conteúdo principal
        function showMainContent() {
            window.history.pushState({}, '', 'index.php');
            loadMainContent();
        }
        
        // Mostrar detalhe do artigo
        function showArticleDetail(id) {
            document.getElementById('mainContentSection').classList.add('hidden');
            document.getElementById('articleDetailSection').classList.remove('hidden');
            
            loadArticleDetail(id);
        }
        
        // Carregar categorias
        async function loadCategories() {
            try {
                const response = await fetch(`${AGRO_NEWS_API}?action=get_categories`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    categories = result.data;
                    const select = document.getElementById('categoryFilter');
                    if (select) {
                        select.innerHTML = '<option value="">Todas as categorias</option>' +
                            result.data.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('');
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar categorias:', error);
            }
        }
        
        // Carregar artigo principal
        async function loadMainArticle() {
            try {
                // Usar timeout menor para não travar
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 segundos
                
                const response = await fetch(`${AGRO_NEWS_API}?action=get_featured&limit=1`, {
                    signal: controller.signal
                });
                clearTimeout(timeoutId);
                
                const result = await response.json();
                
                if (result.success && result.data && result.data.length > 0) {
                    const article = result.data[0];
                    const category = categories.find(c => c.id === article.category_id);
                    
                    // Layout mobile otimizado - card horizontal compacto
                    const isMobile = window.innerWidth < 768;
                    
                    if (isMobile) {
                        // Layout mobile: card horizontal compacto
                        document.getElementById('mainArticle').innerHTML = `
                            <div class="flex flex-col sm:flex-row gap-0 sm:gap-4">
                                <div class="relative w-full sm:w-2/5 h-48 sm:h-auto overflow-hidden rounded-t-lg sm:rounded-l-lg sm:rounded-tr-none">
                                    <img src="${article.featured_image || 'assets/img/default-news.jpg'}" 
                                         alt="${article.title}" 
                                         class="w-full h-full object-cover">
                                    <div class="absolute top-2 left-2">
                                        <span class="category-badge text-[10px] px-2 py-1 bg-white/90 text-agro-green backdrop-blur-sm">${category?.name || 'Notícia'}</span>
                                    </div>
                                </div>
                                <div class="flex-1 p-4 sm:p-5">
                                    <h1 class="headline text-lg sm:text-xl mb-2 leading-tight line-clamp-3 text-gray-900">${article.title}</h1>
                                    <p class="body-text text-xs sm:text-sm text-gray-600 mb-3 line-clamp-2 hidden sm:block">${article.summary || article.content.substring(0, 120) + '...'}</p>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2 text-[10px] sm:text-xs text-gray-500">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>${calculateReadTime(article.content)} min</span>
                                        </div>
                                        <button onclick="showArticleDetail('${article.id}')" class="text-agro-green hover:text-green-600 font-semibold text-xs sm:text-sm transition-colors">
                                            Ler →
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        // Layout desktop: card hero grande e elegante
                        document.getElementById('mainArticle').innerHTML = `
                            <div class="relative h-[500px] lg:h-[600px] overflow-hidden rounded-t-lg group">
                                <img src="${article.featured_image || 'assets/img/default-news.jpg'}" 
                                     alt="${article.title}" 
                                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                <div class="hero-overlay absolute inset-0 bg-gradient-to-t from-black/80 via-black/50 to-transparent"></div>
                                <div class="absolute inset-0 flex flex-col justify-end p-8 lg:p-12 text-white">
                                    <div class="mb-6">
                                        <span class="category-badge text-sm px-4 py-2 bg-agro-green text-white mb-4 inline-block">${category?.name || 'Notícia'}</span>
                                        <h1 class="headline text-3xl lg:text-5xl mb-4 leading-tight font-bold max-w-4xl drop-shadow-lg">${article.title}</h1>
                                        <div class="flex items-center space-x-6 mt-6">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center border-2 border-white/30">
                                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="font-body font-bold text-sm">${article.author_name || 'AgroNews360'}</p>
                                                    <p class="font-body text-xs text-white/80">Autor</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2 text-sm text-white/90">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span>${calculateReadTime(article.content)} min de leitura</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-8 lg:p-10 bg-white">
                                <p class="serif-text text-lg text-gray-700 leading-relaxed mb-6 max-w-3xl">${article.summary || article.content.substring(0, 300) + '...'}</p>
                                <button onclick="showArticleDetail('${article.id}')" class="read-more-btn inline-flex items-center space-x-3 px-6 py-3 bg-agro-green hover:bg-green-600 text-white rounded-lg font-body font-semibold text-base transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                    <span>Ler artigo completo</span>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                </button>
                            </div>
                        `;
                    }
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    console.log('Timeout ao carregar artigo principal');
                    const mainArticleEl = document.getElementById('mainArticle');
                    if (mainArticleEl) {
                        mainArticleEl.innerHTML = '<div class="text-center py-16 text-gray-500 font-body">Timeout ao carregar artigo. Tente novamente.</div>';
                    }
                } else {
                    console.error('Erro ao carregar artigo principal:', error);
                }
            }
        }
        
        // Carregar artigos da sidebar
        async function loadSidebarArticles() {
            try {
                // Usar timeout menor para não travar
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 segundos
                
                const response = await fetch(`${AGRO_NEWS_API}?action=get_articles&limit=3&page=1`, {
                    signal: controller.signal
                });
                clearTimeout(timeoutId);
                
                const result = await response.json();
                
                if (result.success && result.data && result.data.articles) {
                    const articles = result.data.articles.slice(1, 4);
                    
                    const sidebarEl = document.getElementById('sidebarArticles');
                    if (!sidebarEl) return;
                    
                    const isMobile = window.innerWidth < 1024;
                    
                    if (isMobile) {
                        // Mobile: grid de 2 colunas
                        sidebarEl.innerHTML = `<div class="grid grid-cols-2 lg:grid-cols-1 gap-3 lg:gap-6">${articles.map((item, index) => {
                            const category = categories.find(c => c.id === item.category_id);
                            return `
                                <article class="premium-card news-card cursor-pointer group" onclick="showArticleDetail('${item.id}')">
                                    ${item.featured_image ? `
                                        <div class="relative h-32 overflow-hidden rounded-t-lg">
                                            <img src="${item.featured_image}" alt="${item.title}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                            ${index === 0 ? '<span class="trending-badge absolute top-2 right-2 text-[9px] px-1.5 py-0.5 bg-red-500 text-white rounded">Em Alta</span>' : ''}
                                            <div class="absolute bottom-2 left-2">
                                                <span class="category-badge text-[9px] px-1.5 py-0.5 bg-white/90 backdrop-blur-sm" style="color: ${category?.color === 'yellow' ? '#000' : '#374151'}">${category?.name || 'Notícia'}</span>
                                            </div>
                                        </div>
                                    ` : ''}
                                    <div class="p-3">
                                        <h3 class="article-title text-xs font-semibold mb-1.5 line-clamp-2 group-hover:text-agro-green transition-colors leading-tight text-gray-900">${item.title}</h3>
                                        <div class="flex items-center justify-between text-[9px] text-gray-500">
                                            <span>${formatDate(item.published_at || item.created_at)}</span>
                                            <span>${calculateReadTime(item.content)} min</span>
                                        </div>
                                    </div>
                                </article>
                            `;
                        }).join('')}</div>`;
                    } else {
                        // Desktop: coluna vertical com cards completos
                        sidebarEl.innerHTML = articles.map((item, index) => {
                            const category = categories.find(c => c.id === item.category_id);
                            return `
                                <article class="premium-card news-card cursor-pointer group" onclick="showArticleDetail('${item.id}')">
                                    ${item.featured_image ? `
                                        <div class="relative h-48 overflow-hidden rounded-t-lg">
                                            <img src="${item.featured_image}" alt="${item.title}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                            ${index === 0 ? '<span class="trending-badge text-xs px-2 py-1">Em Alta</span>' : ''}
                                        </div>
                                    ` : ''}
                                    <div class="p-5">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="category-badge text-xs px-2 py-1" style="background: ${category?.color ? `var(--${category.color})` : '#f1f5f9'}; color: ${category?.color === 'yellow' ? '#000' : '#374151'}">${category?.name || 'Notícia'}</span>
                                            <span class="time-badge text-xs text-gray-500">${calculateReadTime(item.content)} min</span>
                                        </div>
                                        <h3 class="article-title text-lg mb-3 line-clamp-2 group-hover:text-agro-green transition-colors leading-tight font-semibold">${item.title}</h3>
                                        <p class="body-text text-sm text-gray-600 mb-4 line-clamp-3">${item.summary || item.content.substring(0, 120) + '...'}</p>
                                        <div class="flex items-center text-xs text-gray-500">
                                            <span>${formatDate(item.published_at || item.created_at)}</span>
                                        </div>
                                    </div>
                                </article>
                            `;
                        }).join('');
                    }
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    console.log('Timeout ao carregar artigos da sidebar');
                    const sidebarEl = document.getElementById('sidebarArticles');
                    if (sidebarEl) {
                        sidebarEl.innerHTML = '<div class="text-center py-12 text-gray-500 font-body text-sm">Timeout ao carregar artigos.</div>';
                    }
                } else {
                    console.error('Erro ao carregar artigos da sidebar:', error);
                }
            }
        }
        
        // Carregar artigos em destaque
        async function loadFeaturedArticles() {
            try {
                // Usar timeout menor para não travar
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 segundos
                
                const response = await fetch(`${AGRO_NEWS_API}?action=get_featured&limit=2`, {
                    signal: controller.signal
                });
                clearTimeout(timeoutId);
                
                const result = await response.json();
                
                if (result.success && result.data) {
                    const featuredEl = document.getElementById('featuredArticles');
                    if (!featuredEl) return;
                    
                    const isDesktop = window.innerWidth >= 1024;
                    featuredEl.innerHTML = result.data.map(item => {
                        const category = categories.find(c => c.id === item.category_id);
                        if (isDesktop) {
                            // Desktop: cards grandes e elegantes
                            return `
                                <article class="premium-card news-card cursor-pointer group overflow-hidden" onclick="showArticleDetail('${item.id}')">
                                    <div class="relative h-64 lg:h-80 overflow-hidden">
                                        <img src="${item.featured_image || 'assets/img/default-news.jpg'}" 
                                             alt="${item.title}" 
                                             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                        <div class="absolute top-4 left-4">
                                            <span class="category-badge text-xs px-3 py-1.5 bg-white/95 text-gray-900 backdrop-blur-sm">${category?.name || 'Notícia'}</span>
                                        </div>
                                    </div>
                                    <div class="p-6 lg:p-8">
                                        <h3 class="article-title text-xl lg:text-2xl mb-3 line-clamp-2 group-hover:text-agro-green transition-colors leading-tight font-bold">${item.title}</h3>
                                        <p class="body-text text-sm lg:text-base text-gray-600 mb-4 line-clamp-3 leading-relaxed">${item.summary || item.content.substring(0, 150) + '...'}</p>
                                        <div class="flex items-center justify-between text-xs text-gray-500">
                                            <span>${formatDate(item.published_at || item.created_at)}</span>
                                            <span class="flex items-center space-x-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span>${calculateReadTime(item.content)} min</span>
                                            </span>
                                        </div>
                                    </div>
                                </article>
                            `;
                        } else {
                            // Mobile: cards compactos
                            return `
                                <article class="premium-card news-card cursor-pointer" onclick="showArticleDetail('${item.id}')">
                                    <div class="relative h-32 sm:h-48 overflow-hidden rounded-t-lg">
                                        <img src="${item.featured_image || 'assets/img/default-news.jpg'}" 
                                             alt="${item.title}" 
                                             class="w-full h-full object-cover">
                                        <div class="absolute top-2 left-2">
                                            <span class="category-badge text-[9px] sm:text-[10px] px-1.5 sm:px-2 py-0.5 sm:py-1 bg-white/95 backdrop-blur-sm">${category?.name || 'Notícia'}</span>
                                        </div>
                                    </div>
                                    <div class="p-3 sm:p-4">
                                        <h3 class="article-title text-xs sm:text-sm mb-2 line-clamp-2 hover:text-agro-green transition-colors leading-tight font-semibold">${item.title}</h3>
                                        <div class="flex items-center justify-between text-[9px] sm:text-[10px] text-gray-500">
                                            <span>${formatDate(item.published_at || item.created_at)}</span>
                                            <span>${calculateReadTime(item.content)} min</span>
                                        </div>
                                    </div>
                                </article>
                            `;
                        }
                    }).join('');
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    console.log('Timeout ao carregar artigos em destaque');
                    const featuredEl = document.getElementById('featuredArticles');
                    if (featuredEl) {
                        featuredEl.innerHTML = '<div class="text-center py-12 text-gray-500 font-body text-sm col-span-full">Timeout ao carregar destaques.</div>';
                    }
                } else {
                    console.error('Erro ao carregar artigos em destaque:', error);
                }
            }
        }
        
        // Cache simples para melhorar performance
        const articlesCache = new Map();
        
        // Carregar últimas notícias
        async function loadLatestArticles() {
            const container = document.getElementById('latestArticles');
            if (!container) return;
            
            // Criar chave de cache
            const cacheKey = `${currentPage}-${currentCategory || 'all'}-${currentSearch || ''}`;
            
            // Verificar cache (válido por 2 minutos)
            const cached = articlesCache.get(cacheKey);
            if (cached && Date.now() - cached.timestamp < 120000) {
                renderLatestArticles(cached.articles);
                renderPagination(cached.pagination);
                return;
            }
            
            // Mostrar skeleton loader apenas se não houver conteúdo anterior
            if (container.children.length === 0 || container.querySelector('.premium-card') === null) {
                container.innerHTML = createSkeletonGrid(9);
            } else {
                // Manter conteúdo anterior visível enquanto carrega
                container.style.opacity = '0.6';
            }
            
            try {
                const params = new URLSearchParams({
                    action: 'get_articles',
                    page: currentPage,
                    limit: 9
                });
                if (currentCategory) params.append('category_id', currentCategory);
                if (currentSearch) params.append('search', currentSearch);
                
                // Usar timeout para não travar
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 12000); // 12 segundos
                
                const startTime = performance.now();
                const response = await fetch(`${AGRO_NEWS_API}?${params}`, {
                    cache: 'no-cache',
                    headers: {
                        'Accept': 'application/json'
                    },
                    signal: controller.signal
                });
                clearTimeout(timeoutId);
                
                const result = await response.json();
                const loadTime = performance.now() - startTime;
                
                if (result.success && result.data) {
                    // Salvar no cache
                    articlesCache.set(cacheKey, {
                        articles: result.data.articles || [],
                        pagination: result.data.pagination || {},
                        timestamp: Date.now()
                    });
                    
                    // Limpar cache antigo (manter apenas últimos 10)
                    if (articlesCache.size > 10) {
                        const firstKey = articlesCache.keys().next().value;
                        articlesCache.delete(firstKey);
                    }
                    
                    container.style.opacity = '1';
                    renderLatestArticles(result.data.articles || []);
                    renderPagination(result.data.pagination || {});
                } else {
                    container.style.opacity = '1';
                    container.innerHTML = '<div class="text-center py-16 col-span-full text-gray-500 font-body">Nenhuma notícia encontrada.</div>';
                }
            } catch (error) {
                container.style.opacity = '1';
                if (error.name === 'AbortError') {
                    console.log('Timeout ao carregar notícias');
                    container.innerHTML = '<div class="text-center py-16 col-span-full text-gray-500 font-body">Timeout ao carregar notícias. Tente novamente.</div>';
                } else {
                    console.error('Erro ao carregar notícias:', error);
                    container.innerHTML = '<div class="text-center py-16 col-span-full text-red-500 font-body">Erro ao carregar notícias. Tente novamente.</div>';
                }
            }
        }
        
        // Criar skeleton grid para loading
        function createSkeletonGrid(count = 9) {
            return Array(count).fill(0).map(() => `
                <div class="premium-card animate-pulse">
                    <div class="h-32 sm:h-40 md:h-56 bg-gray-200 rounded-t-lg"></div>
                    <div class="p-3 sm:p-4 md:p-6">
                        <div class="h-3 sm:h-4 bg-gray-200 rounded w-16 sm:w-24 mb-2 sm:mb-3"></div>
                        <div class="h-4 sm:h-5 md:h-6 bg-gray-200 rounded w-full mb-1 sm:mb-2"></div>
                        <div class="h-4 sm:h-5 md:h-6 bg-gray-200 rounded w-3/4 mb-2 sm:mb-4 hidden sm:block"></div>
                        <div class="h-3 sm:h-4 bg-gray-200 rounded w-full mb-1 sm:mb-2 hidden sm:block"></div>
                        <div class="h-3 sm:h-4 bg-gray-200 rounded w-5/6 hidden sm:block"></div>
                    </div>
                </div>
            `).join('');
        }
        
        // Renderizar últimas notícias
        function renderLatestArticles(articles) {
            const container = document.getElementById('latestArticles');
            if (!container) return;
            
            if (articles.length === 0) {
                container.innerHTML = '<div class="text-center py-16 col-span-full text-gray-500 font-body fade-in">Nenhuma notícia encontrada.</div>';
                return;
            }
            
            const isDesktop = window.innerWidth >= 1024;
            
            container.innerHTML = articles.map((item, index) => {
                const category = categories.find(c => c.id === item.category_id);
                const imageUrl = item.featured_image && item.featured_image !== '#' 
                    ? item.featured_image 
                    : `https://source.unsplash.com/800x600/?agriculture&sig=${Date.now()}-${index}`;
                
                if (isDesktop) {
                    // Desktop: cards maiores e mais elegantes
                    return `
                        <article class="premium-card news-card cursor-pointer group fade-in-up overflow-hidden" onclick="showArticleDetail('${item.id}')" style="animation-delay: ${index * 0.05}s;" data-article-id="${item.id}">
                            <div class="relative h-56 lg:h-64 overflow-hidden bg-gray-100 rounded-t-lg">
                                <img src="${imageUrl}" 
                                     alt="${item.title}" 
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                     loading="lazy"
                                     onerror="this.src='https://source.unsplash.com/800x600/?farm&sig=${Date.now()}'">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                <div class="absolute top-4 left-4">
                                    <span class="category-badge text-xs px-3 py-1.5 bg-white/95 backdrop-blur-sm font-semibold" style="color: ${category?.color === 'yellow' ? '#000' : '#374151'}">${category?.name || 'Notícia'}</span>
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="article-title text-lg lg:text-xl mb-3 line-clamp-2 group-hover:text-agro-green transition-colors leading-tight font-bold text-gray-900">${item.title}</h3>
                                <p class="body-text text-sm lg:text-base text-gray-600 line-clamp-3 mb-4 leading-relaxed">${item.summary || item.content.substring(0, 150) + '...'}</p>
                                <div class="flex items-center justify-between text-xs text-gray-500 pt-4 border-t border-gray-100">
                                    <span>${formatDate(item.published_at || item.created_at)}</span>
                                    <span class="flex items-center space-x-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>${calculateReadTime(item.content)} min</span>
                                    </span>
                                </div>
                            </div>
                        </article>
                    `;
                } else {
                    // Mobile: cards compactos
                    return `
                        <article class="premium-card news-card cursor-pointer group fade-in-up" onclick="showArticleDetail('${item.id}')" style="animation-delay: ${index * 0.05}s;" data-article-id="${item.id}">
                            <div class="relative h-32 sm:h-40 overflow-hidden bg-gray-100 rounded-t-lg">
                                <img src="${imageUrl}" 
                                     alt="${item.title}" 
                                     class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                     loading="lazy"
                                     onerror="this.src='https://source.unsplash.com/800x600/?farm&sig=${Date.now()}'">
                                <div class="absolute top-2 left-2">
                                    <span class="category-badge text-[9px] sm:text-[10px] px-1.5 sm:px-2 py-0.5 sm:py-1 bg-white/95 backdrop-blur-sm" style="color: ${category?.color === 'yellow' ? '#000' : '#374151'}">${category?.name || 'Notícia'}</span>
                                </div>
                            </div>
                            <div class="p-3 sm:p-4">
                                <h3 class="article-title text-xs sm:text-sm mb-2 line-clamp-2 group-hover:text-agro-green transition-colors leading-tight font-semibold text-gray-900">${item.title}</h3>
                                <p class="body-text text-xs text-gray-600 line-clamp-2 mb-2 hidden sm:block">${item.summary || item.content.substring(0, 120) + '...'}</p>
                                <div class="flex items-center justify-between text-[10px] sm:text-xs text-gray-500">
                                    <span>${formatDate(item.published_at || item.created_at)}</span>
                                    <span class="flex items-center space-x-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>${calculateReadTime(item.content)} min</span>
                                    </span>
                                </div>
                            </div>
                        </article>
                    `;
                }
            }).join('');
        }
        
        // Renderizar paginação
        function renderPagination(pagination) {
            const container = document.getElementById('pagination');
            if (!container) return;
            
            if (!pagination || pagination.total_pages <= 1) {
                container.classList.add('hidden');
                return;
            }
            
            container.classList.remove('hidden');
            container.innerHTML = '';
            
            // Botão anterior
            const prevButton = document.createElement('button');
            prevButton.textContent = '← Anterior';
            prevButton.className = `px-4 py-2 rounded-md font-body font-medium text-sm ${
                currentPage === 1 
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                    : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
            }`;
            prevButton.disabled = currentPage === 1;
            prevButton.onclick = () => {
                if (currentPage > 1) {
                    currentPage--;
                    loadLatestArticles();
                }
            };
            container.appendChild(prevButton);
            
            // Botões de página
            const maxPages = Math.min(pagination.total_pages, 10);
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(pagination.total_pages, startPage + 4);
            
            for (let i = startPage; i <= endPage; i++) {
                const button = document.createElement('button');
                button.textContent = i;
                button.className = `px-4 py-2 rounded-md font-body font-medium text-sm transition-all ${
                    i === currentPage 
                        ? 'bg-agro-green text-white shadow-md' 
                        : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                }`;
                button.onclick = (e) => {
                    e.preventDefault();
                    currentPage = i;
                    loadLatestArticles();
                };
                container.appendChild(button);
            }
            
            // Botão próximo
            const nextButton = document.createElement('button');
            nextButton.textContent = 'Próximo →';
            nextButton.className = `px-4 py-2 rounded-md font-body font-medium text-sm ${
                currentPage === pagination.total_pages 
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                    : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
            }`;
            nextButton.disabled = currentPage === pagination.total_pages;
            nextButton.onclick = () => {
                if (currentPage < pagination.total_pages) {
                    currentPage++;
                    loadLatestArticles();
                }
            };
            container.appendChild(nextButton);
        }
        
        // Carregar detalhe do artigo
        async function loadArticleDetail(id) {
            const container = document.getElementById('articleDetailContent');
            if (!container) return;
            
            container.innerHTML = '<div class="text-center py-16"><div class="inline-block w-10 h-10 border-4 border-agro-green border-t-transparent rounded-full animate-spin"></div><p class="mt-4 text-gray-600 font-body">Carregando notícia...</p></div>';
            
            try {
                // Carregar artigo (dados vêm direto da web)
                const response = await fetch(`${AGRO_NEWS_API}?action=get_article&id=${id}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    renderArticleDetail(result.data.article, result.data.related || []);
                    // Atualizar URL sem recarregar
                    window.history.pushState({ articleId: id }, '', `index.php?id=${id}`);
                } else {
                    container.innerHTML = '<div class="text-center py-16 text-red-500 font-body">Artigo não encontrado.</div>';
                }
            } catch (error) {
                console.error('Erro ao carregar artigo:', error);
                container.innerHTML = '<div class="text-center py-16 text-red-500 font-body">Erro ao carregar artigo.</div>';
            }
        }
        
        // Renderizar detalhe do artigo
        function renderArticleDetail(article, related) {
            const category = categories.find(c => c.id === article.category_id);
            const container = document.getElementById('articleDetailContent');
            if (!container) return;
            
            container.innerHTML = `
                <div class="mb-12">
                    <div class="flex items-center space-x-3 mb-6 flex-wrap">
                        <span class="category-badge text-sm">${category?.name || 'Notícia'}</span>
                        <span class="time-badge">${formatDate(article.published_at || article.created_at)}</span>
                    </div>
                    <h1 class="headline text-4xl mb-6 leading-tight text-gray-900">${article.title}</h1>
                    <div class="author-card p-5 rounded-lg mb-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-full bg-agro-green flex items-center justify-center text-white text-lg font-semibold">
                                ${(article.author_name || 'A')[0].toUpperCase()}
                            </div>
                            <div>
                                <p class="font-body font-bold text-gray-900">${article.author_name || 'AgroNews360'}</p>
                                <p class="font-body text-sm text-gray-600">Autor</p>
                            </div>
                            <div class="ml-auto flex items-center space-x-4 text-sm text-gray-600">
                                <span class="flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <span>${article.views_count || 0}</span>
                                </span>
                                <span class="flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>${calculateReadTime(article.content)} min</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                ${article.featured_image ? `
                    <div class="mb-8 rounded-lg overflow-hidden">
                        <img src="${article.featured_image}" alt="${article.title}" class="w-full h-[500px] object-cover">
                    </div>
                ` : ''}
                
                <div class="premium-card p-8 mb-8">
                    ${article.summary ? `
                        <div class="serif-text text-xl text-gray-700 font-medium mb-8 pb-6 border-b border-gray-200 leading-relaxed">${article.summary}</div>
                    ` : ''}
                    <div class="article-content">${formatContent(article.content)}</div>
                </div>
                
                <div class="premium-card p-6 mb-12">
                    <h3 class="font-display font-bold text-lg text-gray-900 mb-4">Compartilhar</h3>
                    <div class="flex items-center space-x-3 flex-wrap">
                        <button onclick="shareOnWhatsApp(event)" class="share-btn flex items-center space-x-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md font-body font-medium text-sm">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                            <span>WhatsApp</span>
                        </button>
                        <button onclick="shareOnFacebook(event)" class="share-btn flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-body font-medium text-sm">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            <span>Facebook</span>
                        </button>
                        <button onclick="copyLink(event)" class="share-btn flex items-center space-x-2 px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded-md font-body font-medium text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span>Copiar Link</span>
                        </button>
                    </div>
                </div>
                
                ${related && related.length > 0 ? `
                    <div class="mb-8">
                        <h3 class="section-title font-display font-bold text-2xl text-gray-900 mb-6">Notícias Relacionadas</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-6">
                            ${related.map(item => `
                                <article class="premium-card news-card cursor-pointer" onclick="showArticleDetail('${item.id}')">
                                    ${item.featured_image ? `
                                        <div class="relative h-32 sm:h-40 md:h-48 overflow-hidden">
                                            <img src="${item.featured_image}" alt="${item.title}" class="w-full h-full object-cover">
                                        </div>
                                    ` : ''}
                                    <div class="p-3 sm:p-4 md:p-6">
                                        <h4 class="article-title text-sm sm:text-base md:text-lg mb-2 md:mb-3 line-clamp-2 hover:text-agro-green transition-colors leading-tight">${item.title}</h4>
                                        <p class="body-text text-xs sm:text-sm text-gray-600 line-clamp-2 md:line-clamp-3 hidden sm:block">${item.summary || item.content.substring(0, 100) + '...'}</p>
                                    </div>
                                </article>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            `;
        }
        
        // Formatar conteúdo
        function formatContent(content) {
            if (!content) return '';
            const div = document.createElement('div');
            div.textContent = content;
            let html = div.innerHTML;
            html = html.split('\n\n').map(para => {
                if (para.trim()) {
                    return `<p>${para.trim().replace(/\n/g, '<br>')}</p>`;
                }
                return '';
            }).join('');
            return html;
        }
        
        // Calcular tempo de leitura
        function calculateReadTime(content) {
            if (!content) return 1;
            const wordsPerMinute = 200;
            const wordCount = content.split(/\s+/).length;
            return Math.ceil(wordCount / wordsPerMinute) || 1;
        }
        
        // Formatar data
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(diff / 3600000);
            const days = Math.floor(diff / 86400000);
            
            if (minutes < 60) return `Há ${minutes} min`;
            if (hours < 24) return `Há ${hours}h`;
            if (days < 7) return `Há ${days} dias`;
            
            return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
        }
        
        // Compartilhar
        function shareOnWhatsApp(e) {
            e?.preventDefault();
            const url = encodeURIComponent(window.location.href);
            const title = document.querySelector('h1')?.textContent || document.querySelector('.headline')?.textContent || '';
            const text = encodeURIComponent(title);
            window.open(`https://wa.me/?text=${text}%20${url}`, '_blank');
        }
        
        function shareOnFacebook(e) {
            e?.preventDefault();
            const url = encodeURIComponent(window.location.href);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
        }
        
        // Carregar dados do Lactech e criar gráficos
        async function loadLactechData() {
            try {
                // Buscar preço do leite
                const milkPriceData = await fetchMilkPrice();
                
                // Criar gráfico apenas se houver dados
                if (milkPriceData && milkPriceData.data && milkPriceData.data.length > 0) {
                    createMilkPriceChart(milkPriceData);
                    updateMilkPriceCard(milkPriceData);
                } else {
                    showNoDataMessage('milkPriceChart', 'Preço do Leite');
                    updateMilkPriceCard(null);
                }
            } catch (error) {
                console.error('Erro ao carregar dados do Lactech:', error);
                showNoDataMessage('milkPriceChart', 'Preço do Leite');
                updateMilkPriceCard(null);
            }
        }
        
        // Buscar preço do leite
        async function fetchMilkPrice() {
            // Primeiro tentar buscar do Lactech (se autenticado)
            const lactechData = await fetchLactechMilkPrice();
            if (lactechData) return lactechData;
            
            // Se não conseguir, buscar de API pública de cotações
            return await fetchPublicMilkPrice();
        }
        
        // Buscar preço do leite do Lactech
        async function fetchLactechMilkPrice() {
            try {
                // Tentar buscar da API financeira do Lactech
                const response = await fetch('../lactech/api/financial.php?action=get_milk_price', {
                    credentials: 'include'
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.success && result.data) {
                        return formatMilkPriceData(result.data);
                    }
                }
                
                // Tentar buscar de volume_records com preço
                const volumeResponse = await fetch('../lactech/api/volume.php?action=get_stats', {
                    credentials: 'include'
                });
                
                if (volumeResponse.ok) {
                    const volumeResult = await volumeResponse.json();
                    if (volumeResult.success && volumeResult.data) {
                        // Processar dados se houver preço
                    }
                }
            } catch (error) {
                console.log('API do Lactech não disponível ou requer autenticação');
            }
            return null;
        }
        
        // Buscar preço público do leite (CEPEA ou outra fonte)
        async function fetchPublicMilkPrice() {
            try {
                // Usar API do AgroNews360 para buscar preço do leite
                const response = await fetch(`${AGRO_NEWS_API}?action=get_milk_price`);
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.success && result.data) {
                        const price = parseFloat(result.data.price);
                        return {
                            labels: [new Date().toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })],
                            data: [price],
                            current: price,
                            source: result.data.source || 'Referência'
                        };
                    }
                }
            } catch (error) {
                console.log('Erro ao buscar preço público:', error);
            }
            return null;
        }
        
        // Formatar dados de preço do leite
        function formatMilkPriceData(data) {
            if (!data) return null;
            
            // Se for um único valor
            if (typeof data === 'number' || data.price) {
                const price = typeof data === 'number' ? data : parseFloat(data.price);
                return {
                    labels: [new Date().toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })],
                    data: [price],
                    current: price
                };
            }
            
            // Se for um array de dados históricos
            if (Array.isArray(data) && data.length > 0) {
                const labels = [];
                const prices = [];
                
                data.forEach(item => {
                    labels.push(new Date(item.date || item.created_at).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }));
                    prices.push(parseFloat(item.price || item.amount || 0));
                });
                
                return {
                    labels,
                    data: prices,
                    current: prices[prices.length - 1] || 0
                };
            }
            
            return null;
        }
        
        // Atualizar card de preço do leite
        function updateMilkPriceCard(milkData) {
            const milkPriceEl = document.getElementById('currentMilkPrice');
            const milkChangeEl = document.getElementById('milkPriceChange');
            
            if (milkPriceEl) {
                if (milkData && milkData.current) {
                    milkPriceEl.textContent = `R$ ${milkData.current.toFixed(2)}`;
                    milkPriceEl.className = 'text-3xl font-display font-bold text-agro-green';
                    
                    if (milkChangeEl && milkData.data && milkData.data.length > 1) {
                        const change = ((milkData.current - milkData.data[milkData.data.length - 2]) / milkData.data[milkData.data.length - 2] * 100).toFixed(1);
                        milkChangeEl.textContent = `${change >= 0 ? '+' : ''}${change}% hoje`;
                        milkChangeEl.className = `text-xs mt-1 ${change >= 0 ? 'text-green-600' : 'text-red-600'}`;
                    } else if (milkChangeEl) {
                        milkChangeEl.textContent = 'Preço atualizado';
                        milkChangeEl.className = 'text-xs mt-1 text-gray-500';
                    }
                } else {
                    // Tentar buscar de API pública
                    fetchPublicMilkPrice().then(priceData => {
                        if (priceData && priceData.current) {
                            milkPriceEl.textContent = `R$ ${priceData.current.toFixed(2)}`;
                            milkPriceEl.className = 'text-3xl font-display font-bold text-agro-green';
                            if (milkChangeEl) {
                                const source = priceData.source || 'Referência de mercado';
                                milkChangeEl.textContent = `Fonte: ${source}`;
                                milkChangeEl.className = 'text-xs mt-1 text-gray-500';
                            }
                        } else {
                            milkPriceEl.textContent = 'N/A';
                            milkPriceEl.className = 'text-3xl font-display font-bold text-gray-400';
                            if (milkChangeEl) {
                                milkChangeEl.textContent = 'Dados não disponíveis';
                                milkChangeEl.className = 'text-xs mt-1 text-gray-400';
                            }
                        }
                    });
                }
            }
        }
        
        // Mostrar mensagem quando não houver dados
        function showNoDataMessage(canvasId, title) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            
            const parent = canvas.parentElement;
            parent.innerHTML = `
                <div class="flex flex-col items-center justify-center h-64 text-gray-500">
                    <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="font-body text-sm">Dados de ${title} não disponíveis</p>
                    <p class="font-body text-xs text-gray-400 mt-1">Faça login no Lactech para ver os dados</p>
                </div>
            `;
        }
        
        // Criar gráfico de preço do leite
        function createMilkPriceChart(priceData) {
            const ctx = document.getElementById('milkPriceChart');
            if (!ctx) return;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: priceData.labels,
                    datasets: [{
                        label: 'Preço do Leite (R$/L)',
                        data: priceData.data,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#22c55e',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                family: 'Lato',
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                family: 'Lato',
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    return 'R$ ' + context.parsed.y.toFixed(2) + '/L';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toFixed(2);
                                },
                                font: {
                                    family: 'Lato',
                                    size: 11
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45,
                                font: {
                                    family: 'Lato',
                                    size: 10
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
        
        // Newsletter
        function showNewsletterModal() {
            const email = prompt('Digite seu e-mail para receber notícias:');
            if (email && email.includes('@')) {
                subscribeNewsletter(email, null);
            }
        }
        
        async function subscribeNewsletter(email, name) {
            try {
                const response = await fetch(`${AGRO_NEWS_API}?action=subscribe_newsletter`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, name })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessMessage('Cadastro realizado com sucesso! Você receberá nossas notícias por email.');
                } else {
                    showErrorMessage('Erro: ' + (result.error || 'Não foi possível realizar o cadastro.'));
                }
            } catch (error) {
                console.error('Erro ao cadastrar:', error);
                showErrorMessage('Erro ao cadastrar. Tente novamente.');
            }
        }
        
        // Mensagens de feedback
        function showSuccessMessage(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-xl z-50 font-body font-semibold fade-in-up';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100px)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        function showErrorMessage(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-xl z-50 font-body font-semibold fade-in-up';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100px)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        function copyLink(e) {
            const btn = e ? e.target.closest('button') : document.querySelector('button[onclick*="copyLink"]');
            navigator.clipboard.writeText(window.location.href).then(() => {
                if (btn) {
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span>Copiado!</span>';
                    btn.classList.add('bg-green-600');
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.classList.remove('bg-green-600');
                    }, 2000);
                } else {
                    alert('Link copiado para a área de transferência!');
                }
            }).catch(() => {
                alert('Erro ao copiar link');
            });
        }
        
        // Inicializar Event Listeners
        function initEventListeners() {
            // Filtro de categoria
            const categoryFilter = document.getElementById('categoryFilter');
            if (categoryFilter) {
                categoryFilter.addEventListener('change', function() {
                    currentCategory = this.value;
                    currentPage = 1;
                    loadLatestArticles();
                });
            }
            
            // Busca
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        currentSearch = this.value;
                        currentPage = 1;
                        loadLatestArticles();
                    }
                });
            }
            
            // Botão de busca na barra secundária
            const searchButton = document.getElementById('searchButton');
            if (searchButton) {
                searchButton.addEventListener('click', function() {
                    const searchInputMain = document.getElementById('searchInput');
                    if (searchInputMain) {
                        searchInputMain.focus();
                        searchInputMain.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            }
            
            // Botão Assinar
            const subscribeButtons = document.querySelectorAll('button');
            subscribeButtons.forEach(btn => {
                if (btn.textContent.includes('Assinar') || btn.textContent.includes('Inscrever')) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        showNewsletterModal();
                    });
                }
            });
            
            // Newsletter form no footer
            const newsletterForm = document.querySelector('footer form');
            if (newsletterForm) {
                newsletterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const email = this.querySelector('input[type="email"]').value;
                    const name = this.querySelector('input[type="text"]')?.value || null;
                    subscribeNewsletter(email, name);
                });
            }
        }
        
        // ==========================================
        // MODO LEITURA
        // ==========================================
        const readingModeToggle = document.getElementById('readingModeToggle');
        let readingMode = localStorage.getItem('readingMode') === 'true';
        
        if (readingMode) {
            document.body.classList.add('reading-mode');
            readingModeToggle.style.background = '#2563eb';
        }
        
        readingModeToggle.addEventListener('click', function() {
            readingMode = !readingMode;
            document.body.classList.toggle('reading-mode', readingMode);
            localStorage.setItem('readingMode', readingMode);
            
            if (readingMode) {
                readingModeToggle.style.background = '#2563eb';
            } else {
                readingModeToggle.style.background = '#22c55e';
            }
        });
        
        // Menu Mobile Toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        const menuIcon = document.getElementById('menuIcon');
        const closeIcon = document.getElementById('closeIcon');
        
        if (mobileMenuToggle && mobileMenu) {
            mobileMenuToggle.addEventListener('click', function() {
                const isOpen = !mobileMenu.classList.contains('hidden');
                
                if (isOpen) {
                    // Fechar menu
                    mobileMenu.classList.add('hidden');
                    menuIcon.classList.remove('hidden');
                    closeIcon.classList.add('hidden');
                } else {
                    // Abrir menu
                    mobileMenu.classList.remove('hidden');
                    menuIcon.classList.add('hidden');
                    closeIcon.classList.remove('hidden');
                }
            });
            
            // Fechar menu ao clicar em um link
            mobileMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function() {
                    mobileMenu.classList.add('hidden');
                    menuIcon.classList.remove('hidden');
                    closeIcon.classList.add('hidden');
                });
            });
            
            // Fechar menu ao clicar fora
            document.addEventListener('click', function(e) {
                if (!mobileMenuToggle.contains(e.target) && !mobileMenu.contains(e.target)) {
                    mobileMenu.classList.add('hidden');
                    menuIcon.classList.remove('hidden');
                    closeIcon.classList.add('hidden');
                }
            });
        }
        
        // Smooth Scroll para links internos
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
        
    </script>
</body>
</html>
