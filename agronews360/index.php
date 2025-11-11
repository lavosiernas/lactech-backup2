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
            background: #fafafa;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Tipografia Profissional */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }
        
        .headline {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -0.03em;
        }
        
        .article-title {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            line-height: 1.2;
        }
        
        .body-text {
            font-family: 'Lato', sans-serif;
            font-weight: 400;
            line-height: 1.8;
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
        
        /* Cards com Profundidade */
        .news-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .news-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.1) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        
        .news-card:hover::before {
            opacity: 1;
        }
        
        .news-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        
        .news-card img {
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .news-card:hover img {
            transform: scale(1.1);
        }
        
        /* Hero Section com Overlay */
        .hero-overlay {
            background: linear-gradient(180deg, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.7) 100%);
        }
        
        /* Badge de Categoria */
        .category-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.875rem;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Botão de Leitura */
        .read-more-btn {
            position: relative;
            overflow: hidden;
        }
        
        .read-more-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .read-more-btn:hover::after {
            width: 300px;
            height: 300px;
        }
        
        /* Toggle Button */
        .reading-toggle {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }
        
        .reading-toggle:hover {
            transform: scale(1.15) rotate(5deg);
            box-shadow: 0 12px 32px rgba(34, 197, 94, 0.4);
        }
        
        /* Scrollbar Personalizada */
        ::-webkit-scrollbar {
            width: 12px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #22c55e 0%, #16a34a 100%);
            border-radius: 6px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #16a34a 0%, #15803d 100%);
        }
        
        /* Article Content - Tipografia de Jornal */
        .article-content {
            font-family: 'Merriweather', serif;
            font-size: 1.125rem;
            line-height: 1.9;
            color: #2d3748;
        }
        
        .article-content p {
            margin-bottom: 1.75rem;
            text-align: justify;
        }
        
        .article-content p:first-of-type {
            font-size: 1.25rem;
            font-weight: 400;
            line-height: 1.8;
            color: #4a5568;
            font-style: italic;
        }
        
        .article-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            margin-top: 3rem;
            margin-bottom: 1.5rem;
            color: #1a202c;
            line-height: 1.3;
        }
        
        .article-content h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 2.5rem;
            margin-bottom: 1.25rem;
            color: #2d3748;
        }
        
        .article-content img {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            margin: 2.5rem 0;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .article-content blockquote {
            border-left: 4px solid #22c55e;
            padding-left: 1.5rem;
            margin: 2rem 0;
            font-style: italic;
            color: #4a5568;
            font-size: 1.125rem;
        }
        
        /* Loading Animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .loading {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Hover Effects */
        .hover-lift {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .hover-lift:hover {
            transform: translateY(-4px);
        }
        
        /* Section Dividers */
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, #e2e8f0 50%, transparent 100%);
            margin: 4rem 0;
        }
        
        /* Newsletter Box */
        .newsletter-box {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            position: relative;
            overflow: hidden;
        }
        
        .newsletter-box::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Author Card */
        .author-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-left: 4px solid #22c55e;
        }
        
        /* Share Buttons */
        .share-btn {
            transition: all 0.3s ease;
            position: relative;
        }
        
        .share-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: currentColor;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .share-btn:hover::before {
            opacity: 0.1;
        }
        
        .share-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        /* Trending Badge */
        .trending-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }
        
        /* Time Badge */
        .time-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            background: rgba(0,0,0,0.05);
            border-radius: 9999px;
            font-size: 0.875rem;
            color: #64748b;
        }
        
        /* Smooth Fade In */
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
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Image Overlay Effect */
        .image-overlay {
            position: relative;
            overflow: hidden;
        }
        
        .image-overlay::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.4) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        
        .image-overlay:hover::after {
            opacity: 1;
        }
        
        /* Premium Card Style */
        .premium-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .premium-card:hover {
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
            transform: translateY(-6px);
        }
        
        /* Section Title */
        .section-title {
            position: relative;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%);
            border-radius: 2px;
        }
        
        /* Responsive Typography */
        @media (max-width: 768px) {
            .headline {
                font-size: 2rem;
            }
            
            .article-content {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Header Principal (Preto Premium) -->
    <header class="bg-black text-white sticky top-0 z-50 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo com Imagem -->
                <a href="index.php" class="flex items-center space-x-3 group">
                    <img src="assets/img/agro360.png" alt="AgroNews360" class="h-10 w-10 object-contain group-hover:scale-110 transition-transform duration-300">
                    <span class="text-2xl font-display font-bold tracking-tight">AgroNews360</span>
                </a>
                
                <!-- Navegação -->
                <nav class="hidden lg:flex items-center space-x-8">
                    <a href="index.php" class="text-sm font-body font-semibold uppercase tracking-wide hover:text-agro-green transition-colors duration-200">Home</a>
                    <a href="#noticias" class="text-sm font-body font-semibold uppercase tracking-wide hover:text-agro-green transition-colors duration-200">Notícias</a>
                    <a href="#cotacoes" class="text-sm font-body font-semibold uppercase tracking-wide hover:text-agro-green transition-colors duration-200">Cotações</a>
                    <a href="#clima" class="text-sm font-body font-semibold uppercase tracking-wide hover:text-agro-green transition-colors duration-200">Clima</a>
                    <a href="#sobre" class="text-sm font-body font-semibold uppercase tracking-wide hover:text-agro-green transition-colors duration-200">Sobre</a>
                </nav>
                
                <!-- Controles -->
                <div class="flex items-center space-x-4">
                    <?php
                    if ($isLoggedIn):
                    ?>
                        <div class="flex items-center space-x-3">
                            <span class="text-sm font-body text-white/90">Olá, <?php echo htmlspecialchars($_SESSION['agronews_user_name'] ?? 'Usuário'); ?></span>
                            <a href="api/auth.php?action=logout" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-white font-body font-semibold text-sm transition-colors">
                                Sair
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="px-6 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 rounded-lg text-white font-body font-bold text-sm uppercase tracking-wide transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                            Entrar
                        </a>
                        <button class="px-6 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 rounded-lg text-white font-body font-bold text-sm uppercase tracking-wide transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                            Assinar
                        </button>
                    <?php endif; ?>
                    <button class="lg:hidden p-2 hover:bg-gray-800 rounded-lg transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Barra Secundária (Newsletter) -->
    <div class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <button class="lg:hidden p-2 hover:bg-gray-200 rounded-lg transition-colors">
                    <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="flex-1 text-center">
                    <span class="text-sm font-body text-gray-700 font-medium">📧 Cadastre-se e receba as principais notícias do agronegócio diariamente</span>
                </div>
                <button id="searchButton" class="p-2 hover:bg-gray-200 rounded-lg transition-colors group">
                    <svg class="w-5 h-5 text-gray-700 group-hover:text-agro-green transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Conteúdo Principal -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
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
            
            <div id="articleDetailContent" class="fade-in-up">
                <!-- Será preenchido via JavaScript -->
            </div>
        </div>
        
        <!-- Seção Principal de Notícias -->
        <div id="mainContentSection">
            
            <!-- Layout Principal: Artigo Grande + Coluna Lateral -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 mb-16">
                
                <!-- Artigo Principal (Esquerda) -->
                <div class="lg:col-span-2">
                    <div id="mainArticle" class="premium-card overflow-hidden">
                        <div class="text-center py-16">
                            <div class="inline-block w-10 h-10 border-4 border-agro-green border-t-transparent rounded-full animate-spin"></div>
                            <p class="mt-4 text-gray-600 font-body">Carregando artigo principal...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Coluna Lateral (Direita) -->
                <div class="lg:col-span-1 space-y-6">
                    <div id="sidebarArticles">
                        <div class="text-center py-12">
                            <div class="inline-block w-6 h-6 border-4 border-agro-green border-t-transparent rounded-full animate-spin"></div>
                            <p class="mt-2 text-sm text-gray-500 font-body">Carregando artigos...</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Artigos em Destaque (2 colunas) -->
            <div class="mb-16">
                <h2 class="section-title text-3xl font-display font-bold text-gray-900">Artigos em Destaque</h2>
                <div id="featuredArticles" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="text-center py-12">
                        <div class="inline-block w-6 h-6 border-4 border-agro-green border-t-transparent rounded-full animate-spin"></div>
                        <p class="mt-2 text-sm text-gray-500 font-body">Carregando destaques...</p>
                    </div>
                </div>
            </div>
            
            <!-- Últimas Notícias -->
            <div class="mb-12">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="section-title text-3xl font-display font-bold text-gray-900">Últimas Notícias</h2>
                    <button id="showMoreBtn" class="hidden text-agro-orange hover:text-orange-600 font-body font-bold text-sm uppercase tracking-wide transition-colors">
                        Ver Mais →
                    </button>
                </div>
                
                <!-- Filtros Premium -->
                <div class="mb-8 flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                    <select id="categoryFilter" class="px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-agro-green focus:border-agro-green bg-white text-gray-900 font-body font-medium shadow-sm hover:border-gray-300 transition-all">
                        <option value="">Todas as categorias</option>
                    </select>
                    <div class="relative flex-1 max-w-md">
                        <input type="text" id="searchInput" placeholder="Buscar notícias..." 
                               class="w-full pl-12 pr-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-agro-green focus:border-agro-green bg-white text-gray-900 placeholder-gray-400 font-body shadow-sm hover:border-gray-300 transition-all">
                        <svg class="absolute left-4 top-3.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
                
                <!-- Grid de Notícias -->
                <div id="latestArticles" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="text-center py-16 col-span-full">
                        <div class="inline-block w-10 h-10 border-4 border-agro-green border-t-transparent rounded-full animate-spin"></div>
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
    
    <!-- Footer Premium -->
    <footer class="bg-black text-white mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <img src="assets/img/agro360.png" alt="AgroNews360" class="h-12 w-12 object-contain">
                        <span class="text-2xl font-display font-bold">AgroNews360</span>
                    </div>
                    <p class="text-gray-400 text-sm font-body leading-relaxed">Portal de notícias do agronegócio com informações atualizadas sobre mercado, clima e tecnologia.</p>
                </div>
                <div>
                    <h4 class="font-display font-bold text-lg mb-6">Categorias</h4>
                    <ul class="space-y-3 text-sm font-body">
                        <li><a href="#noticias" class="text-gray-400 hover:text-white transition-colors">Pecuária</a></li>
                        <li><a href="#noticias" class="text-gray-400 hover:text-white transition-colors">Agricultura</a></li>
                        <li><a href="#noticias" class="text-gray-400 hover:text-white transition-colors">Mercado</a></li>
                        <li><a href="#noticias" class="text-gray-400 hover:text-white transition-colors">Tecnologia</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-display font-bold text-lg mb-6">Links</h4>
                    <ul class="space-y-3 text-sm font-body">
                        <li><a href="#cotacoes" class="text-gray-400 hover:text-white transition-colors">Cotações</a></li>
                        <li><a href="#clima" class="text-gray-400 hover:text-white transition-colors">Clima</a></li>
                        <li><a href="#sobre" class="text-gray-400 hover:text-white transition-colors">Sobre Nós</a></li>
                        <li><a href="#contato" class="text-gray-400 hover:text-white transition-colors">Contato</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-display font-bold text-lg mb-6">Newsletter</h4>
                    <p class="text-gray-400 text-sm font-body mb-4">Receba notícias exclusivas</p>
                    <form class="space-y-3">
                        <input type="email" placeholder="Seu e-mail" 
                               class="w-full px-4 py-2.5 bg-gray-900 border border-gray-800 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-agro-green focus:border-transparent font-body">
                        <button type="submit" class="w-full px-4 py-2.5 bg-gradient-to-r from-agro-green to-green-600 hover:from-green-600 hover:to-green-700 rounded-lg text-white font-body font-bold text-sm uppercase tracking-wide transition-all shadow-lg hover:shadow-xl">
                            Inscrever-se
                        </button>
                    </form>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-12 pt-8 text-center">
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
            
            // Sincronizar dados na primeira carga (apenas se não houver artigos)
            checkAndSyncData();
        }
        
        // Não precisa mais verificar/sincronizar - dados vêm direto da web
        function checkAndSyncData() {
            // Dados vêm direto da web, não precisa sincronizar
        }
        
        // Carregar conteúdo principal
        function loadMainContent() {
            document.getElementById('articleDetailSection').classList.add('hidden');
            document.getElementById('mainContentSection').classList.remove('hidden');
            
            loadCategories();
            loadMainArticle();
            loadSidebarArticles();
            loadFeaturedArticles();
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
                const response = await fetch(`${AGRO_NEWS_API}?action=get_featured&limit=1`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.length > 0) {
                    const article = result.data[0];
                    const category = categories.find(c => c.id === article.category_id);
                    
                    document.getElementById('mainArticle').innerHTML = `
                        <div class="relative h-[500px] overflow-hidden image-overlay">
                            <img src="${article.featured_image || 'assets/img/default-news.jpg'}" 
                                 alt="${article.title}" 
                                 class="w-full h-full object-cover">
                            <div class="hero-overlay absolute inset-0"></div>
                            <div class="absolute inset-0 flex flex-col justify-end p-10 text-white">
                                <div class="flex items-center space-x-4 mb-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center border-2 border-white/30">
                                            <span class="text-2xl">${category?.icon || '📰'}</span>
                                        </div>
                                        <div>
                                            <p class="font-body font-bold text-sm">${article.author_name || 'AgroNews360'}</p>
                                            <p class="font-body text-xs text-white/80">Autor</p>
                                        </div>
                                    </div>
                                </div>
                                <h1 class="headline text-5xl mb-4 leading-tight max-w-4xl">${article.title}</h1>
                                <div class="flex items-center space-x-4 flex-wrap">
                                    <span class="category-badge text-agro-green">${category?.name || 'Notícia'}</span>
                                    <span class="time-badge text-white bg-white/20 backdrop-blur-md">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        ${calculateReadTime(article.content)} min de leitura
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="p-8">
                            <p class="serif-text text-lg text-gray-700 leading-relaxed mb-6">${article.summary || article.content.substring(0, 250) + '...'}</p>
                            <button onclick="showArticleDetail(${article.id})" class="read-more-btn inline-flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-agro-green to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg font-body font-bold text-sm uppercase tracking-wide transition-all shadow-lg hover:shadow-xl relative overflow-hidden">
                                <span>Ler mais</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Erro ao carregar artigo principal:', error);
            }
        }
        
        // Carregar artigos da sidebar
        async function loadSidebarArticles() {
            try {
                const response = await fetch(`${AGRO_NEWS_API}?action=get_articles&limit=3&page=1`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.articles) {
                    const articles = result.data.articles.slice(1, 4);
                    
                    const sidebarEl = document.getElementById('sidebarArticles');
                    if (!sidebarEl) return;
                    
                    sidebarEl.innerHTML = articles.map((item, index) => {
                        const category = categories.find(c => c.id === item.category_id);
                        return `
                            <article class="premium-card news-card cursor-pointer group" onclick="showArticleDetail(${item.id})">
                                ${item.featured_image ? `
                                    <div class="relative h-48 overflow-hidden">
                                        <img src="${item.featured_image}" alt="${item.title}" class="w-full h-full object-cover">
                                        ${index === 0 ? '<span class="trending-badge">🔥 Em Alta</span>' : ''}
                                    </div>
                                ` : ''}
                                <div class="p-6">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="category-badge text-xs" style="background: ${category?.color ? `var(--${category.color})` : '#f1f5f9'}; color: ${category?.color === 'yellow' ? '#000' : '#374151'}">${category?.name || 'Notícia'}</span>
                                        <span class="time-badge text-xs">${calculateReadTime(item.content)} min</span>
                                    </div>
                                    <h3 class="article-title text-xl mb-3 line-clamp-2 group-hover:text-agro-green transition-colors">${item.title}</h3>
                                    <p class="body-text text-sm text-gray-600 mb-4 line-clamp-3">${item.summary || item.content.substring(0, 120) + '...'}</p>
                                    <div class="flex items-center text-xs text-gray-500">
                                        <span>${formatDate(item.published_at || item.created_at)}</span>
                                    </div>
                                </div>
                            </article>
                        `;
                    }).join('');
                }
            } catch (error) {
                console.error('Erro ao carregar artigos da sidebar:', error);
            }
        }
        
        // Carregar artigos em destaque
        async function loadFeaturedArticles() {
            try {
                const response = await fetch(`${AGRO_NEWS_API}?action=get_featured&limit=2`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const featuredEl = document.getElementById('featuredArticles');
                    if (!featuredEl) return;
                    
                    featuredEl.innerHTML = result.data.map(item => {
                        const category = categories.find(c => c.id === item.category_id);
                        return `
                            <article class="premium-card news-card cursor-pointer" onclick="showArticleDetail(${item.id})">
                                <div class="relative h-72 overflow-hidden">
                                    <img src="${item.featured_image || 'assets/img/default-news.jpg'}" 
                                         alt="${item.title}" 
                                         class="w-full h-full object-cover">
                                </div>
                                <div class="p-8">
                                    <div class="flex items-center justify-between mb-4">
                                        <span class="category-badge">${category?.name || 'Notícia'}</span>
                                        <span class="time-badge text-xs">Há 24 horas</span>
                                    </div>
                                    <h3 class="article-title text-2xl mb-3 hover:text-agro-green transition-colors">${item.title}</h3>
                                    <p class="body-text text-gray-600 line-clamp-2">${item.summary || item.content.substring(0, 100) + '...'}</p>
                                </div>
                            </article>
                        `;
                    }).join('');
                }
            } catch (error) {
                console.error('Erro ao carregar artigos em destaque:', error);
            }
        }
        
        // Carregar últimas notícias
        async function loadLatestArticles() {
            const container = document.getElementById('latestArticles');
            if (!container) return;
            
            container.innerHTML = '<div class="text-center py-16 col-span-full"><div class="inline-block w-10 h-10 border-4 border-agro-green border-t-transparent rounded-full animate-spin"></div><p class="mt-4 text-gray-600 font-body">Carregando notícias...</p></div>';
            
            try {
                const params = new URLSearchParams({
                    action: 'get_articles',
                    page: currentPage,
                    limit: 9
                });
                if (currentCategory) params.append('category_id', currentCategory);
                if (currentSearch) params.append('search', currentSearch);
                
                const response = await fetch(`${AGRO_NEWS_API}?${params}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    renderLatestArticles(result.data.articles || []);
                    renderPagination(result.data.pagination || {});
                }
            } catch (error) {
                console.error('Erro ao carregar notícias:', error);
                container.innerHTML = '<div class="text-center py-16 col-span-full text-red-500 font-body">Erro ao carregar notícias.</div>';
            }
        }
        
        // Renderizar últimas notícias
        function renderLatestArticles(articles) {
            const container = document.getElementById('latestArticles');
            if (!container) return;
            
            if (articles.length === 0) {
                container.innerHTML = '<div class="text-center py-16 col-span-full text-gray-500 font-body">Nenhuma notícia encontrada.</div>';
                return;
            }
            
            container.innerHTML = articles.map((item, index) => {
                const category = categories.find(c => c.id === item.category_id);
                return `
                    <article class="premium-card news-card cursor-pointer group" onclick="showArticleDetail(${item.id})">
                        ${item.featured_image ? `
                            <div class="relative h-56 overflow-hidden">
                                <img src="${item.featured_image}" alt="${item.title}" class="w-full h-full object-cover">
                            </div>
                        ` : ''}
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <span class="category-badge text-xs" style="background: ${category?.color ? `var(--${category.color})` : '#f1f5f9'}; color: ${category?.color === 'yellow' ? '#000' : '#374151'}">${category?.name || 'Notícia'}</span>
                                <span class="time-badge text-xs">${calculateReadTime(item.content)} min</span>
                            </div>
                            <h3 class="article-title text-xl mb-3 line-clamp-2 group-hover:text-agro-green transition-colors">${item.title}</h3>
                            <p class="body-text text-sm text-gray-600 line-clamp-3 mb-4">${item.summary || item.content.substring(0, 120) + '...'}</p>
                            <div class="flex items-center text-xs text-gray-500">
                                <span>${formatDate(item.published_at || item.created_at)}</span>
                            </div>
                        </div>
                    </article>
                `;
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
            
            for (let i = 1; i <= pagination.total_pages; i++) {
                const button = document.createElement('button');
                button.textContent = i;
                button.className = `px-5 py-2.5 rounded-lg transition-all font-body font-semibold ${
                    i === currentPage 
                        ? 'bg-gradient-to-r from-agro-green to-green-600 text-white shadow-lg' 
                        : 'bg-white text-gray-700 hover:bg-gray-50 border-2 border-gray-200 hover:border-agro-green'
                }`;
                button.onclick = () => {
                    currentPage = i;
                    loadLatestArticles();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                };
                container.appendChild(button);
            }
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
                        <span class="text-3xl">${category?.icon || '📰'}</span>
                        <span class="category-badge text-sm">${category?.name || 'Notícia'}</span>
                        <span class="time-badge">${formatDate(article.published_at || article.created_at)}</span>
                    </div>
                    <h1 class="headline text-5xl mb-6 leading-tight text-gray-900">${article.title}</h1>
                    <div class="author-card p-6 rounded-xl mb-8">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-agro-green to-green-600 flex items-center justify-center text-white text-2xl font-bold">
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
                    <div class="mb-12 rounded-2xl overflow-hidden shadow-2xl">
                        <img src="${article.featured_image}" alt="${article.title}" class="w-full h-[600px] object-cover">
                    </div>
                ` : ''}
                
                <div class="premium-card p-12 mb-12">
                    ${article.summary ? `
                        <div class="serif-text text-2xl text-gray-700 font-medium mb-10 pb-8 border-b-2 border-gray-200 leading-relaxed">${article.summary}</div>
                    ` : ''}
                    <div class="article-content">${formatContent(article.content)}</div>
                </div>
                
                <div class="premium-card p-8 mb-12">
                    <h3 class="font-display font-bold text-2xl text-gray-900 mb-6">Compartilhar</h3>
                    <div class="flex items-center space-x-4 flex-wrap">
                        <button onclick="shareOnWhatsApp(event)" class="share-btn flex items-center space-x-2 px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-xl font-body font-bold text-sm transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                            <span>WhatsApp</span>
                        </button>
                        <button onclick="shareOnFacebook(event)" class="share-btn flex items-center space-x-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-body font-bold text-sm transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            <span>Facebook</span>
                        </button>
                        <button onclick="copyLink(event)" class="share-btn flex items-center space-x-2 px-6 py-3 bg-gray-700 hover:bg-gray-800 text-white rounded-xl font-body font-bold text-sm transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span>Copiar Link</span>
                        </button>
                    </div>
                </div>
                
                ${related && related.length > 0 ? `
                    <div class="mb-12">
                        <h3 class="section-title font-display font-bold text-3xl text-gray-900 mb-8">Notícias Relacionadas</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            ${related.map(item => `
                                <article class="premium-card news-card cursor-pointer" onclick="showArticleDetail(${item.id})">
                                    ${item.featured_image ? `
                                        <div class="relative h-48 overflow-hidden">
                                            <img src="${item.featured_image}" alt="${item.title}" class="w-full h-full object-cover">
                                        </div>
                                    ` : ''}
                                    <div class="p-6">
                                        <h4 class="article-title text-lg mb-3 line-clamp-2 hover:text-agro-green transition-colors">${item.title}</h4>
                                        <p class="body-text text-sm text-gray-600 line-clamp-3">${item.summary || item.content.substring(0, 100) + '...'}</p>
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
            readingModeToggle.classList.add('bg-blue-600');
            readingModeToggle.style.background = 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)';
        }
        
        readingModeToggle.addEventListener('click', function() {
            readingMode = !readingMode;
            document.body.classList.toggle('reading-mode', readingMode);
            localStorage.setItem('readingMode', readingMode);
            
            if (readingMode) {
                readingModeToggle.style.background = 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)';
            } else {
                readingModeToggle.style.background = 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)';
            }
        });
        
        // Intersection Observer para animações ao scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                }
            });
        }, observerOptions);
        
        // Observar cards quando carregados
        function observeCards() {
            setTimeout(() => {
                document.querySelectorAll('.premium-card, .news-card').forEach(card => {
                    if (!card.classList.contains('observed')) {
                        observer.observe(card);
                        card.classList.add('observed');
                    }
                });
            }, 500);
        }
        
        // Observar mudanças no DOM para novos cards
        const cardObserver = new MutationObserver(() => {
            observeCards();
        });
        
        const mainContent = document.getElementById('mainContentSection');
        if (mainContent) {
            cardObserver.observe(mainContent, { childList: true, subtree: true });
        }
        
        // Observar cards iniciais
        observeCards();
    </script>
</body>
</html>
