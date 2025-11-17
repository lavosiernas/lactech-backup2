<?php
/**
 * AgroNews360 - Portal de Notícias do Agronegócio
 * Sistema informativo com notícias, cotações e previsões climáticas
 */

// Incluir configuração e iniciar sessão
require_once __DIR__ . '/includes/config_login.php';

// Verificar autenticação (opcional - pode ser público ou requerer login)
$requireAuth = false; // Alterar para true se quiser que apenas usuários logados vejam

if ($requireAuth && !isLoggedIn()) {
    safeRedirect('inicio-login.php');
    exit;
}

// Obter informações do usuário se estiver logado
$user = null;
if (isLoggedIn()) {
    $user = $_SESSION['user'] ?? null;
}

?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroNews360 - Notícias do Agronegócio | LacTech</title>
    <meta name="description" content="Portal de notícias do agronegócio com atualizações sobre pecuária, agricultura, cotações de mercado, previsões climáticas e muito mais.">
    <link rel="icon" href="./assets/img/lactech-logo.png" type="image/png">
    
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
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .news-card {
            transition: all 0.2s ease;
        }
        .news-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .banner-slide {
            display: none;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        .banner-slide.active {
            display: block;
            opacity: 1;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .sidebar-card {
            transition: box-shadow 0.2s ease;
        }
        .sidebar-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .gradient-text {
            color: #22c55e;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s ease-in-out infinite;
        }
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .reading-time {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .scroll-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 2.5rem;
            height: 2.5rem;
            background: #1f2937;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease;
            z-index: 1000;
        }
        .scroll-to-top.visible {
            opacity: 1;
            visibility: visible;
        }
        .scroll-to-top:hover {
            background: #111827;
        }
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
        }
        .video-container iframe,
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        @media (max-width: 768px) {
            .scroll-to-top {
                bottom: 1rem;
                right: 1rem;
                width: 3rem;
                height: 3rem;
            }
            .news-card {
                margin-bottom: 1rem;
            }
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        img[loading="lazy"] {
            opacity: 0;
            transition: opacity 0.3s;
        }
        img[loading="lazy"].loaded {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo e Nome -->
                <div class="flex items-center space-x-3">
                    <a href="gerente-completo.php" class="flex items-center space-x-2">
                        <img src="assets/img/lactech-logo.png" alt="LacTech" class="h-8 w-8">
                        <span class="text-lg font-semibold text-gray-900">LacTech</span>
                    </a>
                    <span class="text-gray-300">|</span>
                    <h1 class="text-lg font-semibold text-gray-900">AgroNews360</h1>
                </div>
                
                <!-- Navegação -->
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="#inicio" class="text-sm text-gray-600 hover:text-gray-900 font-medium">Início</a>
                    <a href="#noticias" class="text-sm text-gray-600 hover:text-gray-900 font-medium">Notícias</a>
                    <a href="#cotacoes" class="text-sm text-gray-600 hover:text-gray-900 font-medium">Cotações</a>
                    <a href="#clima" class="text-sm text-gray-600 hover:text-gray-900 font-medium">Clima</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="gerente-completo.php" class="text-sm text-gray-600 hover:text-gray-900 font-medium">Dashboard</a>
                    <?php endif; ?>
                </nav>
                
                <!-- Menu Mobile -->
                <button id="mobileMenuBtn" class="md:hidden p-2 text-gray-700 hover:text-agro-green transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                
                <!-- Busca -->
                <div class="flex items-center space-x-4">
                    <div class="relative hidden md:block">
                        <input type="text" id="searchInput" placeholder="Buscar notícias..." 
                               class="pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-400 focus:border-gray-400 w-64 text-sm">
                        <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <?php if (isLoggedIn()): ?>
                        <a href="logout.php" class="text-sm text-gray-600 hover:text-gray-900 font-medium">Sair</a>
                    <?php else: ?>
                        <a href="inicio-login.php" class="px-4 py-2 bg-gray-900 text-white rounded-md hover:bg-gray-800 text-sm font-medium transition-colors">Entrar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Menu Mobile -->
    <div id="mobileMenu" class="hidden md:hidden fixed inset-0 z-40 bg-black/50 backdrop-blur-sm" onclick="closeMobileMenu()">
        <div class="bg-white w-64 h-full shadow-2xl p-6" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-xl font-bold gradient-text">Menu</h2>
                <button onclick="closeMobileMenu()" class="p-2 text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <nav class="space-y-2">
                <a href="#inicio" onclick="closeMobileMenu()" class="block px-4 py-3 text-gray-700 hover:text-agro-green hover:bg-green-50 rounded-lg font-medium transition-all duration-200">Início</a>
                <a href="#noticias" onclick="closeMobileMenu()" class="block px-4 py-3 text-gray-700 hover:text-agro-green hover:bg-green-50 rounded-lg font-medium transition-all duration-200">Notícias</a>
                <a href="#cotacoes" onclick="closeMobileMenu()" class="block px-4 py-3 text-gray-700 hover:text-agro-green hover:bg-green-50 rounded-lg font-medium transition-all duration-200">Cotações</a>
                <a href="#clima" onclick="closeMobileMenu()" class="block px-4 py-3 text-gray-700 hover:text-agro-green hover:bg-green-50 rounded-lg font-medium transition-all duration-200">Clima</a>
                <?php if (isLoggedIn()): ?>
                    <a href="gerente-completo.php" class="block px-4 py-3 text-gray-700 hover:text-agro-green hover:bg-green-50 rounded-lg font-medium transition-all duration-200">Dashboard</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
    
    <!-- Conteúdo Principal -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Banner de Destaques (Rotativo) -->
        <section id="inicio" class="mb-12">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-semibold text-gray-900">Destaques</h2>
                <div class="hidden md:flex items-center space-x-2" id="bannerIndicators">
                    <!-- Indicadores serão adicionados via JS -->
                </div>
            </div>
            <div id="featuredBanner" class="relative bg-gray-100 rounded-lg overflow-hidden h-96">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <div class="inline-block w-8 h-8 border-2 border-gray-400 border-t-transparent rounded-full animate-spin"></div>
                        <p class="mt-3 text-sm text-gray-500">Carregando destaques...</p>
                    </div>
                </div>
                <!-- Botões de navegação -->
                <button id="prevBanner" class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/80 hover:bg-white rounded-full shadow-sm flex items-center justify-center transition-colors z-10">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <button id="nextBanner" class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/80 hover:bg-white rounded-full shadow-sm flex items-center justify-center transition-colors z-10">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </section>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Coluna Principal: Notícias -->
            <div class="lg:col-span-2">
                
                <!-- Filtros de Categoria -->
                <section class="mb-8">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-4">
                        <h2 id="noticias" class="text-2xl font-semibold text-gray-900">Notícias</h2>
                        <select id="categoryFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-400 focus:border-gray-400 text-sm">
                            <option value="">Todas as categorias</option>
                        </select>
                    </div>
                    
                    <!-- Filtros rápidos de categoria -->
                    <div id="categoryFilters" class="flex flex-wrap gap-2 mb-6">
                        <!-- Será preenchido via JavaScript -->
                    </div>
                    
                    <!-- Lista de Notícias -->
                    <div id="newsList" class="space-y-6">
                        <!-- Skeleton Loaders -->
                        <div id="skeletonLoaders" class="space-y-6">
                            <div class="bg-white rounded-2xl shadow-md p-6">
                                <div class="flex flex-col md:flex-row gap-6">
                                    <div class="md:w-1/3">
                                        <div class="skeleton w-full h-48 rounded-xl"></div>
                                    </div>
                                    <div class="md:w-2/3 space-y-4">
                                        <div class="flex gap-2">
                                            <div class="skeleton w-20 h-6 rounded-full"></div>
                                            <div class="skeleton w-24 h-6 rounded-full"></div>
                                        </div>
                                        <div class="skeleton w-3/4 h-8 rounded"></div>
                                        <div class="skeleton w-full h-4 rounded"></div>
                                        <div class="skeleton w-2/3 h-4 rounded"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white rounded-2xl shadow-md p-6">
                                <div class="flex flex-col md:flex-row gap-6">
                                    <div class="md:w-1/3">
                                        <div class="skeleton w-full h-48 rounded-xl"></div>
                                    </div>
                                    <div class="md:w-2/3 space-y-4">
                                        <div class="flex gap-2">
                                            <div class="skeleton w-20 h-6 rounded-full"></div>
                                            <div class="skeleton w-24 h-6 rounded-full"></div>
                                        </div>
                                        <div class="skeleton w-3/4 h-8 rounded"></div>
                                        <div class="skeleton w-full h-4 rounded"></div>
                                        <div class="skeleton w-2/3 h-4 rounded"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white rounded-2xl shadow-md p-6">
                                <div class="flex flex-col md:flex-row gap-6">
                                    <div class="md:w-1/3">
                                        <div class="skeleton w-full h-48 rounded-xl"></div>
                                    </div>
                                    <div class="md:w-2/3 space-y-4">
                                        <div class="flex gap-2">
                                            <div class="skeleton w-20 h-6 rounded-full"></div>
                                            <div class="skeleton w-24 h-6 rounded-full"></div>
                                        </div>
                                        <div class="skeleton w-3/4 h-8 rounded"></div>
                                        <div class="skeleton w-full h-4 rounded"></div>
                                        <div class="skeleton w-2/3 h-4 rounded"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Paginação -->
                    <div id="pagination" class="mt-8 flex justify-center space-x-2 hidden">
                        <!-- Será preenchido via JavaScript -->
                    </div>
                </section>
            </div>
            
            <!-- Sidebar: Painel Agro -->
            <aside class="lg:col-span-1 space-y-6">
                
                <!-- Cotações -->
                <section id="cotacoes" class="sidebar-card bg-white border border-gray-200 rounded-lg p-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Cotações</h3>
                    <div id="quotationsPanel" class="space-y-3">
                        <div class="text-center py-6">
                            <div class="inline-block w-8 h-8 border-4 border-agro-yellow border-t-transparent rounded-full animate-spin"></div>
                            <p class="mt-3 text-sm text-gray-500 font-medium">Carregando cotações...</p>
                        </div>
                    </div>
                </section>
                
                <!-- Clima -->
                <section id="clima" class="sidebar-card bg-white border border-gray-200 rounded-lg p-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Clima</h3>
                    <div id="weatherPanel">
                        <div class="text-center py-6">
                            <div class="inline-block w-8 h-8 border-4 border-agro-blue border-t-transparent rounded-full animate-spin"></div>
                            <p class="mt-3 text-sm text-gray-500 font-medium">Carregando previsão...</p>
                        </div>
                    </div>
                </section>
                
                <!-- Dólar e Câmbio -->
                <section class="sidebar-card bg-white border border-gray-200 rounded-lg p-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Câmbio</h3>
                    <div id="currencyPanel" class="space-y-3">
                        <div class="text-center py-6">
                            <div class="inline-block w-8 h-8 border-4 border-agro-green border-t-transparent rounded-full animate-spin"></div>
                            <p class="mt-3 text-sm text-gray-500 font-medium">Carregando câmbio...</p>
                        </div>
                    </div>
                </section>
                
                <!-- Newsletter -->
                <section class="sidebar-card bg-gray-900 text-white border border-gray-800 rounded-lg p-5">
                    <h3 class="text-lg font-semibold mb-3">Newsletter</h3>
                    <p class="text-sm text-gray-300 mb-4">Receba as principais notícias do agronegócio por e-mail</p>
                    <form id="newsletterForm" class="space-y-3">
                        <input type="email" id="newsletterEmail" placeholder="Seu e-mail" required
                               class="w-full px-3 py-2 rounded-md text-gray-900 text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <input type="text" id="newsletterName" placeholder="Seu nome (opcional)"
                               class="w-full px-3 py-2 rounded-md text-gray-900 text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <button type="submit" class="w-full px-4 py-2 bg-white text-gray-900 rounded-md text-sm font-medium hover:bg-gray-100 transition-colors">
                            Cadastrar
                        </button>
                    </form>
                </section>
            </aside>
        </div>
    </main>
    
    <!-- Botão Scroll to Top -->
    <button id="scrollToTop" class="scroll-to-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" aria-label="Voltar ao topo">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
        </svg>
    </button>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-16 border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-2 mb-3">
                        <img src="assets/img/lactech-logo.png" alt="LacTech" class="h-6 w-6">
                        <h4 class="font-semibold text-lg">AgroNews360</h4>
                    </div>
                    <p class="text-gray-400 text-sm">Portal de notícias do agronegócio com informações atualizadas sobre mercado, clima e tecnologia.</p>
                </div>
                <div>
                    <h4 class="font-semibold text-sm mb-3 text-white">Categorias</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#noticias" onclick="filterByCategory('')" class="hover:text-white transition-colors">Todas</a></li>
                        <li><a href="#noticias?category=pecuaria" class="hover:text-white transition-colors">Pecuária</a></li>
                        <li><a href="#noticias?category=agricultura" class="hover:text-white transition-colors">Agricultura</a></li>
                        <li><a href="#noticias?category=mercado-economia" class="hover:text-white transition-colors">Mercado</a></li>
                        <li><a href="#noticias?category=tecnologia-inovacao" class="hover:text-white transition-colors">Tecnologia</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-sm mb-3 text-white">Links</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="gerente-completo.php" class="hover:text-white transition-colors">Dashboard</a></li>
                        <li><a href="#cotacoes" class="hover:text-white transition-colors">Cotações</a></li>
                        <li><a href="#clima" class="hover:text-white transition-colors">Clima</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-sm mb-3 text-white">Contato</h4>
                    <p class="text-sm text-gray-400">suporte@lactechsys.com</p>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-6 text-center">
                <p class="text-sm text-gray-400">&copy; <?php echo date('Y'); ?> LacTech. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // API Base
        const AGRO_NEWS_API = 'api/agronews.php';
        
        // Variáveis globais
        let currentPage = 1;
        let currentCategory = '';
        let currentSearch = '';
        let categories = [];
        
        // Lazy load images
        function lazyLoadImages() {
            const images = document.querySelectorAll('img[loading="lazy"]');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                });
            });
            images.forEach(img => imageObserver.observe(img));
        }
        
        // Carregar dados iniciais
        document.addEventListener('DOMContentLoaded', function() {
            loadCategories();
            loadFeaturedNews();
            loadNews();
            loadQuotations();
            loadWeather();
            loadCurrency();
            
            // Inicializar lazy loading
            setTimeout(lazyLoadImages, 500);
            
            // Event listeners
            document.getElementById('categoryFilter').addEventListener('change', function() {
                currentCategory = this.value;
                currentPage = 1;
                loadNews();
            });
            
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    currentSearch = this.value;
                    currentPage = 1;
                    loadNews();
                }
            });
            
            document.getElementById('newsletterForm').addEventListener('submit', function(e) {
                e.preventDefault();
                subscribeNewsletter();
            });
            
            // Scroll to top button
            const scrollToTopBtn = document.getElementById('scrollToTop');
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    scrollToTopBtn.classList.add('visible');
                } else {
                    scrollToTopBtn.classList.remove('visible');
                }
            });
            
            // Smooth scroll para âncoras
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    const href = this.getAttribute('href');
                    if (href !== '#' && href.length > 1) {
                        e.preventDefault();
                        const target = document.querySelector(href);
                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }
                });
            });
            
            // Menu mobile
            document.getElementById('mobileMenuBtn').addEventListener('click', function() {
                document.getElementById('mobileMenu').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            });
        });
        
        function closeMobileMenu() {
            document.getElementById('mobileMenu').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        // Carregar categorias
        async function loadCategories() {
            try {
                const response = await fetch(`${AGRO_NEWS_API}?action=get_categories`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    categories = result.data;
                    const select = document.getElementById('categoryFilter');
                    select.innerHTML = '<option value="">Todas as categorias</option>' +
                        result.data.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('');
                    
                    // Renderizar filtros rápidos
                    renderCategoryFilters(result.data);
                }
            } catch (error) {
                console.error('Erro ao carregar categorias:', error);
            }
        }
        
        // Renderizar filtros rápidos de categoria
        function renderCategoryFilters(cats) {
            const container = document.getElementById('categoryFilters');
            if (!container || !cats || cats.length === 0) return;
            
            container.innerHTML = `
                <button onclick="filterByCategory('')" class="category-filter-btn px-3 py-1.5 bg-gray-900 text-white rounded-md text-sm font-medium hover:bg-gray-800 transition-colors" data-category="">
                    Todas
                </button>
                ${cats.map(cat => `
                    <button onclick="filterByCategory('${cat.id}')" class="category-filter-btn px-3 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-50 hover:border-gray-400 transition-colors" data-category="${cat.id}">
                        ${cat.name}
                    </button>
                `).join('')}
            `;
        }
        
        function filterByCategory(categoryId) {
            currentCategory = categoryId;
            currentPage = 1;
            document.getElementById('categoryFilter').value = categoryId;
            
            // Atualizar botões ativos
            document.querySelectorAll('.category-filter-btn').forEach(btn => {
                if (btn.dataset.category === categoryId) {
                    btn.classList.remove('bg-white', 'border-gray-300', 'text-gray-700');
                    btn.classList.add('bg-gray-900', 'text-white', 'border-gray-900');
                } else {
                    btn.classList.remove('bg-gray-900', 'text-white', 'border-gray-900');
                    btn.classList.add('bg-white', 'border-gray-300', 'text-gray-700');
                }
            });
            
            loadNews();
            document.getElementById('noticias').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        // Carregar notícias em destaque
        async function loadFeaturedNews() {
            try {
                const response = await fetch(`${AGRO_NEWS_API}?action=get_featured&limit=4`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.length > 0) {
                    renderFeaturedBanner(result.data);
                } else {
                    document.getElementById('featuredBanner').innerHTML = '<div class="p-12 text-center text-gray-500">Nenhuma notícia em destaque no momento.</div>';
                }
            } catch (error) {
                console.error('Erro ao carregar destaques:', error);
                document.getElementById('featuredBanner').innerHTML = '<div class="p-12 text-center text-red-500">Erro ao carregar destaques.</div>';
            }
        }
        
        // Renderizar banner de destaques
        let currentBannerIndex = 0;
        let bannerInterval = null;
        
        function renderFeaturedBanner(news) {
            const container = document.getElementById('featuredBanner');
            container.innerHTML = news.map((item, index) => {
                const hasVideo = item.video_url || item.video_embed;
                return `
                <div class="banner-slide ${index === 0 ? 'active' : ''}" data-index="${index}">
                    <div class="relative h-full bg-cover bg-center" style="background-image: linear-gradient(to bottom, rgba(0,0,0,0.4), rgba(0,0,0,0.6)), url('${item.featured_image || 'assets/img/default-news.jpg'}')">
                        ${hasVideo ? `
                            <div class="absolute top-4 right-4">
                                <span class="px-3 py-1 bg-black/60 text-white rounded text-xs font-medium">Vídeo</span>
                            </div>
                        ` : ''}
                        <div class="absolute inset-0 flex items-end p-6 md:p-8">
                            <div class="text-white max-w-3xl">
                                <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-sm rounded text-xs font-medium mb-2">${item.category_name || 'Notícia'}</span>
                                <h3 class="text-2xl md:text-3xl font-semibold mb-2 leading-tight">${item.title}</h3>
                                <p class="text-sm md:text-base mb-4 text-gray-200 line-clamp-2">${item.summary || ''}</p>
                                <a href="agronews-detalhe.php?id=${item.id}" class="inline-flex items-center space-x-2 px-4 py-2 bg-white text-gray-900 rounded-md text-sm font-medium hover:bg-gray-100 transition-colors">
                                    <span>Ler mais</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            }).join('');
            
            // Renderizar indicadores
            const indicatorsContainer = document.getElementById('bannerIndicators');
            if (news.length > 1 && indicatorsContainer) {
                indicatorsContainer.innerHTML = news.map((_, index) => `
                    <button onclick="goToBanner(${index})" class="w-2 h-2 rounded-full transition-all duration-200 ${index === 0 ? 'bg-gray-900 w-6' : 'bg-gray-300 hover:bg-gray-400'}" data-indicator="${index}"></button>
                `).join('');
            }
            
            // Configurar navegação
            if (news.length > 1) {
                document.getElementById('prevBanner').onclick = () => changeBanner(-1, news.length);
                document.getElementById('nextBanner').onclick = () => changeBanner(1, news.length);
                
                // Auto-rotacionar banner
                startBannerRotation(news.length);
            }
        }
        
        function changeBanner(direction, total) {
            if (bannerInterval) clearInterval(bannerInterval);
            
            currentBannerIndex = (currentBannerIndex + direction + total) % total;
            goToBanner(currentBannerIndex);
            startBannerRotation(total);
        }
        
        function goToBanner(index) {
            const slides = document.querySelectorAll('.banner-slide');
            const indicators = document.querySelectorAll('[data-indicator]');
            
            slides.forEach(slide => slide.classList.remove('active'));
            indicators.forEach(ind => {
                ind.classList.remove('bg-agro-green', 'w-8');
                ind.classList.add('bg-gray-300');
            });
            
            slides[index].classList.add('active');
            if (indicators[index]) {
                indicators[index].classList.remove('bg-gray-300');
                indicators[index].classList.add('bg-gray-900', 'w-6');
            }
            
            currentBannerIndex = index;
        }
        
        function startBannerRotation(total) {
            if (bannerInterval) clearInterval(bannerInterval);
            bannerInterval = setInterval(() => {
                currentBannerIndex = (currentBannerIndex + 1) % total;
                goToBanner(currentBannerIndex);
            }, 6000);
        }
        
        // Calcular tempo de leitura
        function calculateReadingTime(text) {
            const wordsPerMinute = 200;
            const words = text.split(/\s+/).length;
            const minutes = Math.ceil(words / wordsPerMinute);
            return minutes;
        }
        
        // Carregar lista de notícias
        async function loadNews() {
            const container = document.getElementById('newsList');
            const skeletonLoaders = document.getElementById('skeletonLoaders');
            
            // Mostrar skeleton loaders
            if (skeletonLoaders) {
                skeletonLoaders.style.display = 'block';
            }
            
            try {
                const params = new URLSearchParams({
                    action: 'get_articles',
                    page: currentPage,
                    limit: 10
                });
                if (currentCategory) params.append('category_id', currentCategory);
                if (currentSearch) params.append('search', currentSearch);
                
                const response = await fetch(`${AGRO_NEWS_API}?${params}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    // Esconder skeleton loaders
                    if (skeletonLoaders) {
                        skeletonLoaders.style.display = 'none';
                    }
                    renderNewsList(result.data.articles || []);
                    renderPagination(result.data.pagination || {});
                } else {
                    if (skeletonLoaders) {
                        skeletonLoaders.style.display = 'none';
                    }
                    container.innerHTML = '<div class="text-center py-12 text-gray-500">Nenhuma notícia encontrada.</div>';
                }
            } catch (error) {
                console.error('Erro ao carregar notícias:', error);
                if (skeletonLoaders) {
                    skeletonLoaders.style.display = 'none';
                }
                container.innerHTML = '<div class="text-center py-12 text-red-500">Erro ao carregar notícias.</div>';
            }
        }
        
        // Renderizar lista de notícias
        function renderNewsList(news) {
            const container = document.getElementById('newsList');
            
            if (news.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-16">
                        <div class="inline-block p-4 bg-gray-100 rounded-full mb-4">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p class="text-xl text-gray-500 font-medium">Nenhuma notícia encontrada</p>
                        <p class="text-gray-400 mt-2">Tente ajustar os filtros de busca</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = news.map(item => {
                const category = categories.find(c => c.id === item.category_id);
                const readingTime = calculateReadingTime(item.content || item.summary || '');
                const fullText = item.content || item.summary || '';
                const summary = item.summary || fullText.substring(0, 400) + (fullText.length > 400 ? '...' : '');
                
                // Verificar se tem vídeo
                const hasVideo = item.video_url || item.video_embed;
                let mediaContent = '';
                
                if (hasVideo) {
                    if (item.video_embed) {
                        mediaContent = `<div class="video-container rounded">${item.video_embed}</div>`;
                    } else if (item.video_url) {
                        // Detectar tipo de vídeo
                        let embedUrl = '';
                        if (item.video_url.includes('youtube.com/watch') || item.video_url.includes('youtu.be/')) {
                            const videoId = item.video_url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/)?.[1];
                            if (videoId) {
                                embedUrl = `https://www.youtube.com/embed/${videoId}`;
                            }
                        } else if (item.video_url.includes('vimeo.com/')) {
                            const videoId = item.video_url.match(/vimeo\.com\/(\d+)/)?.[1];
                            if (videoId) {
                                embedUrl = `https://player.vimeo.com/video/${videoId}`;
                            }
                        }
                        
                        if (embedUrl) {
                            mediaContent = `
                                <div class="video-container rounded">
                                    <iframe src="${embedUrl}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                                </div>
                            `;
                        } else {
                            mediaContent = `
                                <div class="relative rounded overflow-hidden">
                                    <img src="${item.featured_image || 'assets/img/default-news.jpg'}" alt="Vídeo" class="w-full h-48 md:h-64 object-cover" loading="lazy">
                                    <a href="${item.video_url}" target="_blank" class="absolute inset-0 flex items-center justify-center bg-black/30 hover:bg-black/40 transition-colors">
                                        <div class="w-12 h-12 bg-white/90 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-900 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                        </div>
                                    </a>
                                </div>
                            `;
                        }
                    }
                } else {
                    mediaContent = `
                        <img src="${item.featured_image || 'assets/img/default-news.jpg'}" 
                             alt="${item.title}" 
                             class="w-full h-48 md:h-64 object-cover rounded"
                             loading="lazy">
                    `;
                }
                
                return `
                    <article class="news-card bg-white border border-gray-200 rounded-lg p-6">
                        <div class="flex flex-col md:flex-row gap-6">
                            <div class="md:w-2/5">
                                ${mediaContent}
                            </div>
                            <div class="md:w-3/5 flex flex-col">
                                <div class="mb-3">
                                    <span class="inline-block px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium mb-2">
                                        ${category?.name || 'Notícia'}
                                    </span>
                                    <div class="flex items-center gap-3 text-xs text-gray-500 mb-3">
                                        <span>${formatDate(item.published_at || item.created_at)}</span>
                                        ${readingTime > 0 ? `<span>• ${readingTime} min</span>` : ''}
                                    </div>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 mb-3 leading-snug">
                                    <a href="agronews-detalhe.php?id=${item.id}" class="hover:text-gray-700">
                                        ${item.title}
                                    </a>
                                </h3>
                                <p class="text-gray-600 text-sm leading-relaxed mb-4 flex-1">
                                    ${summary}
                                </p>
                                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                    <a href="agronews-detalhe.php?id=${item.id}" 
                                       class="text-sm text-gray-900 font-medium hover:underline inline-flex items-center gap-1">
                                        Ler mais
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                `;
            }).join('');
        }
        
        // Renderizar vídeo
        function renderVideo(videoUrl, videoEmbed, fallbackImage) {
            if (videoEmbed) {
                // Se já tem embed code
                return `<div class="video-container rounded">${videoEmbed}</div>`;
            } else if (videoUrl) {
                // Detectar tipo de vídeo e gerar embed
                let embedUrl = '';
                
                // YouTube
                if (videoUrl.includes('youtube.com/watch') || videoUrl.includes('youtu.be/')) {
                    const videoId = videoUrl.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/)?.[1];
                    if (videoId) {
                        embedUrl = `https://www.youtube.com/embed/${videoId}`;
                    }
                }
                // Vimeo
                else if (videoUrl.includes('vimeo.com/')) {
                    const videoId = videoUrl.match(/vimeo\.com\/(\d+)/)?.[1];
                    if (videoId) {
                        embedUrl = `https://player.vimeo.com/video/${videoId}`;
                    }
                }
                
                if (embedUrl) {
                    const isVimeo = embedUrl.includes('vimeo.com');
                    return `
                        <div class="video-container rounded">
                            <iframe 
                                src="${embedUrl}" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen
                                loading="lazy">
                            </iframe>
                        </div>
                    `;
                } else {
                    // Se não conseguir detectar, mostrar imagem com play button
                    return `
                        <div class="relative rounded overflow-hidden">
                            <img src="${fallbackImage || 'assets/img/default-news.jpg'}" 
                                 alt="Vídeo" 
                                 class="w-full h-48 md:h-64 object-cover"
                                 loading="lazy">
                            <a href="${videoUrl}" target="_blank" class="absolute inset-0 flex items-center justify-center bg-black/30 hover:bg-black/40 transition-colors">
                                <div class="w-16 h-16 bg-white/90 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-900 ml-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                            </a>
                        </div>
                    `;
                }
            }
        
        // Renderizar paginação
        function renderPagination(pagination) {
            const container = document.getElementById('pagination');
            if (!pagination || pagination.total_pages <= 1) {
                container.classList.add('hidden');
                return;
            }
            
            container.classList.remove('hidden');
            container.innerHTML = '';
            
            const totalPages = pagination.total_pages;
            const maxVisible = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let endPage = Math.min(totalPages, startPage + maxVisible - 1);
            
            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }
            
            // Botão anterior
            if (currentPage > 1) {
                const prevBtn = document.createElement('button');
                prevBtn.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                `;
                prevBtn.className = 'px-3 py-1.5 rounded-md bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 transition-colors flex items-center justify-center text-sm';
                prevBtn.onclick = () => {
                    currentPage--;
                    loadNews();
                    document.getElementById('noticias').scrollIntoView({ behavior: 'smooth', block: 'start' });
                };
                container.appendChild(prevBtn);
            }
            
            // Primeira página
            if (startPage > 1) {
                const firstBtn = document.createElement('button');
                firstBtn.textContent = '1';
                firstBtn.className = 'px-3 py-1.5 rounded-md bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 transition-colors text-sm';
                firstBtn.onclick = () => {
                    currentPage = 1;
                    loadNews();
                    document.getElementById('noticias').scrollIntoView({ behavior: 'smooth', block: 'start' });
                };
                container.appendChild(firstBtn);
                if (startPage > 2) {
                    const dots = document.createElement('span');
                    dots.textContent = '...';
                    dots.className = 'px-2 text-gray-500';
                    container.appendChild(dots);
                }
            }
            
            // Páginas visíveis
            for (let i = startPage; i <= endPage; i++) {
                const button = document.createElement('button');
                button.textContent = i;
                button.className = `px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${
                    i === currentPage 
                        ? 'bg-gray-900 text-white' 
                        : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                }`;
                button.onclick = () => {
                    currentPage = i;
                    loadNews();
                    document.getElementById('noticias').scrollIntoView({ behavior: 'smooth', block: 'start' });
                };
                container.appendChild(button);
            }
            
            // Última página
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const dots = document.createElement('span');
                    dots.textContent = '...';
                    dots.className = 'px-2 text-gray-500';
                    container.appendChild(dots);
                }
                const lastBtn = document.createElement('button');
                lastBtn.textContent = totalPages;
                lastBtn.className = 'px-3 py-1.5 rounded-md bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 transition-colors text-sm';
                lastBtn.onclick = () => {
                    currentPage = totalPages;
                    loadNews();
                    document.getElementById('noticias').scrollIntoView({ behavior: 'smooth', block: 'start' });
                };
                container.appendChild(lastBtn);
            }
            
            // Botão próximo
            if (currentPage < totalPages) {
                const nextBtn = document.createElement('button');
                nextBtn.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                `;
                nextBtn.className = 'px-3 py-1.5 rounded-md bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 transition-colors flex items-center justify-center text-sm';
                nextBtn.onclick = () => {
                    currentPage++;
                    loadNews();
                    document.getElementById('noticias').scrollIntoView({ behavior: 'smooth', block: 'start' });
                };
                container.appendChild(nextBtn);
            }
        }
        
        // Compartilhar artigo
        function shareArticle(articleId, title) {
            const url = `${window.location.origin}${window.location.pathname.replace('agronews360.php', 'agronews-detalhe.php')}?id=${articleId}`;
            
            if (navigator.share) {
                navigator.share({
                    title: title,
                    text: `Confira esta notícia: ${title}`,
                    url: url
                }).catch(() => {
                    copyToClipboard(url);
                });
            } else {
                copyToClipboard(url);
            }
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('Link copiado para a área de transferência!', 'success');
            });
        }
        
        function showNotification(message, type = 'info') {
            // Remover notificações anteriores
            document.querySelectorAll('.notification-toast').forEach(n => n.remove());
            
            const notification = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
            notification.className = `notification-toast fixed top-24 right-4 px-6 py-4 rounded-xl shadow-2xl z-50 transform transition-all duration-300 ${bgColor} text-white flex items-center space-x-3 max-w-md`;
            notification.innerHTML = `
                <div class="flex-shrink-0">
                    ${type === 'success' ? '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' : 
                      type === 'error' ? '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>' :
                      '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'}
                </div>
                <p class="flex-1 font-medium">${message}</p>
            `;
            document.body.appendChild(notification);
            
            // Animação de entrada
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
                notification.style.opacity = '1';
            }, 10);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }
        
        // Carregar cotações
        async function loadQuotations() {
            try {
                const response = await fetch(`${AGRO_NEWS_API}?action=get_quotations&limit=5`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    renderQuotations(result.data);
                }
            } catch (error) {
                console.error('Erro ao carregar cotações:', error);
            }
        }
        
        // Renderizar cotações
        function renderQuotations(quotations) {
            const container = document.getElementById('quotationsPanel');
            
            if (quotations.length === 0) {
                container.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Nenhuma cotação disponível.</p>';
                return;
            }
            
            container.innerHTML = quotations.map(q => {
                const variationClass = q.variation_type === 'up' ? 'text-red-600' : q.variation_type === 'down' ? 'text-green-600' : 'text-gray-600';
                const variationIcon = q.variation_type === 'up' ? '↑' : q.variation_type === 'down' ? '↓' : '→';
                
                return `
                    <div class="flex items-center justify-between p-3 border-b border-gray-100 last:border-0">
                        <div class="flex-1">
                            <p class="font-medium text-sm text-gray-900">${q.product_name}</p>
                            <p class="text-xs text-gray-500 mt-0.5">${q.market || ''} ${q.region ? '• ' + q.region : ''}</p>
                        </div>
                        <div class="text-right ml-4">
                            <p class="font-semibold text-sm text-gray-900">R$ ${parseFloat(q.price).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                            <p class="text-xs ${variationClass} mt-0.5">${variationIcon} ${Math.abs(q.variation)}%</p>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        // Carregar clima
        async function loadWeather() {
            try {
                const response = await fetch(`${AGRO_NEWS_API}?action=get_weather`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    renderWeather(result.data);
                }
            } catch (error) {
                console.error('Erro ao carregar clima:', error);
            }
        }
        
        // Renderizar clima
        function renderWeather(weather) {
            const container = document.getElementById('weatherPanel');
            
            if (!weather || weather.length === 0) {
                container.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Previsão não disponível.</p>';
                return;
            }
            
            const today = weather[0];
            const getWeatherIcon = (condition) => {
                const cond = (condition || '').toLowerCase();
                if (cond.includes('sol') || cond.includes('clear')) return '☀️';
                if (cond.includes('nuvem') || cond.includes('cloud')) return '☁️';
                if (cond.includes('chuva') || cond.includes('rain')) return '🌧️';
                if (cond.includes('tempestade') || cond.includes('storm')) return '⛈️';
                return '🌤️';
            };
            
            container.innerHTML = `
                <div class="text-center mb-4 pb-4 border-b border-gray-200">
                    <div class="text-4xl mb-2">${getWeatherIcon(today.condition)}</div>
                    <p class="text-3xl font-semibold text-gray-900 mb-1">${today.temperature}°C</p>
                    <p class="text-sm text-gray-600">${today.condition || 'Nublado'}</p>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Mín/Máx</p>
                        <p class="font-medium text-gray-900">${today.min_temperature}° / ${today.max_temperature}°</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Chuva</p>
                        <p class="font-medium text-gray-900">${today.rain_probability}%</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Umidade</p>
                        <p class="font-medium text-gray-900">${today.humidity}%</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Vento</p>
                        <p class="font-medium text-gray-900">${today.wind_speed} km/h</p>
                    </div>
                </div>
            `;
        }
        
        // Carregar câmbio
        async function loadCurrency() {
            try {
                const response = await fetch(`${AGRO_NEWS_API}?action=get_currency`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    renderCurrency(result.data);
                }
            } catch (error) {
                console.error('Erro ao carregar câmbio:', error);
            }
        }
        
        // Renderizar câmbio
        function renderCurrency(currency) {
            const container = document.getElementById('currencyPanel');
            
            if (!currency) {
                container.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Dados não disponíveis.</p>';
                return;
            }
            
            container.innerHTML = `
                <div class="flex items-center justify-between p-3 border-b border-gray-100">
                    <div>
                        <p class="font-medium text-sm text-gray-900">Dólar (USD)</p>
                        <p class="text-xs text-gray-500 mt-0.5">${new Date().toLocaleDateString('pt-BR')}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-sm text-gray-900">R$ ${parseFloat(currency.usd || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                        ${currency.usd_variation ? `
                            <p class="text-xs ${currency.usd_variation > 0 ? 'text-red-600' : 'text-green-600'} mt-0.5">
                                ${currency.usd_variation > 0 ? '↑' : '↓'} ${Math.abs(currency.usd_variation)}%
                            </p>
                        ` : ''}
                    </div>
                </div>
            `;
        }
        
        // Inscrever no newsletter
        async function subscribeNewsletter() {
            const email = document.getElementById('newsletterEmail').value;
            const name = document.getElementById('newsletterName').value;
            const submitBtn = document.querySelector('#newsletterForm button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="w-5 h-5 border-2 border-agro-green border-t-transparent rounded-full animate-spin"></div>';
            
            try {
                const response = await fetch(`${AGRO_NEWS_API}?action=subscribe_newsletter`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, name })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('✅ Cadastro realizado com sucesso! Você receberá nossas notícias por email.', 'success');
                    document.getElementById('newsletterForm').reset();
                } else {
                    showNotification('❌ ' + (result.error || 'Não foi possível realizar o cadastro.'), 'error');
                }
            } catch (error) {
                console.error('Erro ao cadastrar:', error);
                showNotification('❌ Erro ao cadastrar. Tente novamente.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
        
        // Formatar data
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
        }
    </script>
</body>
</html>











