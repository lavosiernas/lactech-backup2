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
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .news-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .banner-slide {
            display: none;
        }
        .banner-slide.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo e Nome -->
                <div class="flex items-center space-x-4">
                    <a href="gerente-completo.php" class="flex items-center space-x-2">
                        <img src="assets/img/lactech-logo.png" alt="LacTech" class="h-10 w-10">
                        <span class="text-xl font-bold text-gray-900">LacTech</span>
                    </a>
                    <span class="text-gray-400">|</span>
                    <h1 class="text-xl font-bold text-agro-green">AgroNews360</h1>
                </div>
                
                <!-- Navegação -->
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="#inicio" class="text-gray-700 hover:text-agro-green font-medium">Início</a>
                    <a href="#noticias" class="text-gray-700 hover:text-agro-green font-medium">Notícias</a>
                    <a href="#cotacoes" class="text-gray-700 hover:text-agro-green font-medium">Cotações</a>
                    <a href="#clima" class="text-gray-700 hover:text-agro-green font-medium">Clima</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="gerente-completo.php" class="text-gray-700 hover:text-agro-green font-medium">Dashboard</a>
                    <?php endif; ?>
                </nav>
                
                <!-- Busca -->
                <div class="flex items-center space-x-4">
                    <div class="relative hidden md:block">
                        <input type="text" id="searchInput" placeholder="Buscar notícias..." 
                               class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-agro-green focus:border-transparent w-64">
                        <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <?php if (isLoggedIn()): ?>
                        <a href="logout.php" class="px-4 py-2 text-gray-700 hover:text-agro-green font-medium">Sair</a>
                    <?php else: ?>
                        <a href="inicio-login.php" class="px-4 py-2 bg-agro-green text-white rounded-lg hover:bg-green-600 font-medium">Entrar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Conteúdo Principal -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Banner de Destaques (Rotativo) -->
        <section id="inicio" class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Destaques do Dia</h2>
            <div id="featuredBanner" class="relative bg-white rounded-xl shadow-lg overflow-hidden h-96">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <div class="inline-block w-8 h-8 border-4 border-agro-green border-t-transparent rounded-full animate-spin"></div>
                        <p class="mt-4 text-gray-600">Carregando destaques...</p>
                    </div>
                </div>
            </div>
        </section>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Coluna Principal: Notícias -->
            <div class="lg:col-span-2">
                
                <!-- Filtros de Categoria -->
                <section class="mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h2 id="noticias" class="text-2xl font-bold text-gray-900">Notícias Recentes</h2>
                        <div class="flex items-center space-x-2">
                            <select id="categoryFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-agro-green focus:border-transparent">
                                <option value="">Todas as categorias</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Lista de Notícias -->
                    <div id="newsList" class="space-y-6">
                        <div class="text-center py-12">
                            <div class="inline-block w-8 h-8 border-4 border-agro-green border-t-transparent rounded-full animate-spin"></div>
                            <p class="mt-4 text-gray-600">Carregando notícias...</p>
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
                <section id="cotacoes" class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <span class="mr-2">💰</span>
                        Cotações do Dia
                    </h3>
                    <div id="quotationsPanel" class="space-y-3">
                        <div class="text-center py-4">
                            <div class="inline-block w-6 h-6 border-4 border-agro-yellow border-t-transparent rounded-full animate-spin"></div>
                            <p class="mt-2 text-sm text-gray-500">Carregando cotações...</p>
                        </div>
                    </div>
                </section>
                
                <!-- Clima -->
                <section id="clima" class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <span class="mr-2">🌦️</span>
                        Previsão do Tempo
                    </h3>
                    <div id="weatherPanel">
                        <div class="text-center py-4">
                            <div class="inline-block w-6 h-6 border-4 border-agro-blue border-t-transparent rounded-full animate-spin"></div>
                            <p class="mt-2 text-sm text-gray-500">Carregando previsão...</p>
                        </div>
                    </div>
                </section>
                
                <!-- Dólar e Câmbio -->
                <section class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <span class="mr-2">💵</span>
                        Dólar e Câmbio
                    </h3>
                    <div id="currencyPanel" class="space-y-3">
                        <div class="text-center py-4">
                            <div class="inline-block w-6 h-6 border-4 border-agro-green border-t-transparent rounded-full animate-spin"></div>
                            <p class="mt-2 text-sm text-gray-500">Carregando câmbio...</p>
                        </div>
                    </div>
                </section>
                
                <!-- Newsletter -->
                <section class="bg-gradient-to-br from-agro-green to-green-600 rounded-xl shadow-sm p-6 text-white">
                    <h3 class="text-lg font-bold mb-2">📧 Receba Notícias por Email</h3>
                    <p class="text-sm text-green-100 mb-4">Cadastre-se e receba as principais notícias do agronegócio diariamente</p>
                    <form id="newsletterForm" class="space-y-3">
                        <input type="email" id="newsletterEmail" placeholder="Seu e-mail" required
                               class="w-full px-4 py-2 rounded-lg text-gray-900 focus:ring-2 focus:ring-white">
                        <input type="text" id="newsletterName" placeholder="Seu nome (opcional)"
                               class="w-full px-4 py-2 rounded-lg text-gray-900 focus:ring-2 focus:ring-white">
                        <button type="submit" class="w-full px-4 py-2 bg-white text-agro-green rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                            Cadastrar
                        </button>
                    </form>
                </section>
            </aside>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h4 class="font-bold text-lg mb-4">AgroNews360</h4>
                    <p class="text-gray-400 text-sm">Portal de notícias do agronegócio com informações atualizadas sobre mercado, clima e tecnologia.</p>
                </div>
                <div>
                    <h4 class="font-bold text-lg mb-4">Categorias</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#noticias?category=pecuaria" class="hover:text-white">Pecuária</a></li>
                        <li><a href="#noticias?category=agricultura" class="hover:text-white">Agricultura</a></li>
                        <li><a href="#noticias?category=mercado-economia" class="hover:text-white">Mercado</a></li>
                        <li><a href="#noticias?category=tecnologia-inovacao" class="hover:text-white">Tecnologia</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-lg mb-4">Links</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="gerente-completo.php" class="hover:text-white">Dashboard</a></li>
                        <li><a href="#cotacoes" class="hover:text-white">Cotações</a></li>
                        <li><a href="#clima" class="hover:text-white">Clima</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-lg mb-4">Contato</h4>
                    <p class="text-gray-400 text-sm">Sistema LacTech</p>
                    <p class="text-gray-400 text-sm">suporte@lactechsys.com</p>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> LacTech. Todos os direitos reservados.</p>
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
        
        // Carregar dados iniciais
        document.addEventListener('DOMContentLoaded', function() {
            loadCategories();
            loadFeaturedNews();
            loadNews();
            loadQuotations();
            loadWeather();
            loadCurrency();
            
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
        });
        
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
                }
            } catch (error) {
                console.error('Erro ao carregar categorias:', error);
            }
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
        function renderFeaturedBanner(news) {
            const container = document.getElementById('featuredBanner');
            container.innerHTML = news.map((item, index) => `
                <div class="banner-slide ${index === 0 ? 'active' : ''}" data-index="${index}">
                    <div class="relative h-96 bg-cover bg-center" style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('${item.featured_image || 'assets/img/default-news.jpg'}')">
                        <div class="absolute inset-0 flex items-end p-8">
                            <div class="text-white">
                                <span class="inline-block px-3 py-1 bg-agro-green rounded-full text-sm font-semibold mb-2">${item.category_name || 'Notícia'}</span>
                                <h3 class="text-3xl font-bold mb-2">${item.title}</h3>
                                <p class="text-lg mb-4">${item.summary || ''}</p>
                                <a href="agronews-detalhe.php?id=${item.id}" class="inline-block px-6 py-2 bg-agro-green hover:bg-green-600 rounded-lg font-semibold transition-colors">
                                    Ler mais →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            // Auto-rotacionar banner
            if (news.length > 1) {
                let currentIndex = 0;
                setInterval(() => {
                    document.querySelectorAll('.banner-slide').forEach(slide => slide.classList.remove('active'));
                    currentIndex = (currentIndex + 1) % news.length;
                    document.querySelector(`.banner-slide[data-index="${currentIndex}"]`).classList.add('active');
                }, 5000);
            }
        }
        
        // Carregar lista de notícias
        async function loadNews() {
            const container = document.getElementById('newsList');
            container.innerHTML = '<div class="text-center py-12"><div class="inline-block w-8 h-8 border-4 border-agro-green border-t-transparent rounded-full animate-spin"></div><p class="mt-4 text-gray-600">Carregando notícias...</p></div>';
            
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
                    renderNewsList(result.data.articles || []);
                    renderPagination(result.data.pagination || {});
                } else {
                    container.innerHTML = '<div class="text-center py-12 text-gray-500">Nenhuma notícia encontrada.</div>';
                }
            } catch (error) {
                console.error('Erro ao carregar notícias:', error);
                container.innerHTML = '<div class="text-center py-12 text-red-500">Erro ao carregar notícias.</div>';
            }
        }
        
        // Renderizar lista de notícias
        function renderNewsList(news) {
            const container = document.getElementById('newsList');
            
            if (news.length === 0) {
                container.innerHTML = '<div class="text-center py-12 text-gray-500">Nenhuma notícia encontrada.</div>';
                return;
            }
            
            container.innerHTML = news.map(item => {
                const category = categories.find(c => c.id === item.category_id);
                return `
                    <article class="news-card bg-white rounded-xl shadow-sm p-6">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="md:w-1/3">
                                <img src="${item.featured_image || 'assets/img/default-news.jpg'}" 
                                     alt="${item.title}" 
                                     class="w-full h-48 object-cover rounded-lg">
                            </div>
                            <div class="md:w-2/3">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="text-2xl">${category?.icon || '📰'}</span>
                                    <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">${category?.name || 'Notícia'}</span>
                                    <span class="text-sm text-gray-500">${formatDate(item.published_at || item.created_at)}</span>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">
                                    <a href="agronews-detalhe.php?id=${item.id}" class="hover:text-agro-green transition-colors">
                                        ${item.title}
                                    </a>
                                </h3>
                                <p class="text-gray-600 mb-4">${item.summary || item.content.substring(0, 150) + '...'}</p>
                                <a href="agronews-detalhe.php?id=${item.id}" 
                                   class="inline-flex items-center text-agro-green font-semibold hover:underline">
                                    Ler mais →
                                </a>
                            </div>
                        </div>
                    </article>
                `;
            }).join('');
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
            
            for (let i = 1; i <= pagination.total_pages; i++) {
                const button = document.createElement('button');
                button.textContent = i;
                button.className = `px-4 py-2 rounded-lg ${i === currentPage ? 'bg-agro-green text-white' : 'bg-white text-gray-700 hover:bg-gray-100'}`;
                button.onclick = () => {
                    currentPage = i;
                    loadNews();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                };
                container.appendChild(button);
            }
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
                container.innerHTML = '<p class="text-sm text-gray-500 text-center">Nenhuma cotação disponível.</p>';
                return;
            }
            
            container.innerHTML = quotations.map(q => {
                const variationClass = q.variation_type === 'up' ? 'text-red-600' : q.variation_type === 'down' ? 'text-green-600' : 'text-gray-600';
                const variationIcon = q.variation_type === 'up' ? '↑' : q.variation_type === 'down' ? '↓' : '→';
                
                return `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-semibold text-gray-900">${q.product_name}</p>
                            <p class="text-xs text-gray-500">${q.market || ''} ${q.region ? '- ' + q.region : ''}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-900">R$ ${parseFloat(q.price).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                            <p class="text-xs ${variationClass}">${variationIcon} ${Math.abs(q.variation)}%</p>
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
                container.innerHTML = '<p class="text-sm text-gray-500 text-center">Previsão não disponível.</p>';
                return;
            }
            
            const today = weather[0];
            container.innerHTML = `
                <div class="text-center mb-4">
                    <p class="text-4xl font-bold text-gray-900">${today.temperature}°C</p>
                    <p class="text-sm text-gray-600">${today.condition || 'Nublado'}</p>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-gray-500">Mín/Máx</p>
                        <p class="font-semibold">${today.min_temperature}° / ${today.max_temperature}°</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Chuva</p>
                        <p class="font-semibold">${today.rain_probability}%</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Umidade</p>
                        <p class="font-semibold">${today.humidity}%</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Vento</p>
                        <p class="font-semibold">${today.wind_speed} km/h</p>
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
                container.innerHTML = '<p class="text-sm text-gray-500 text-center">Dados não disponíveis.</p>';
                return;
            }
            
            container.innerHTML = `
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-semibold text-gray-900">Dólar (USD)</p>
                            <p class="text-xs text-gray-500">${new Date().toLocaleDateString('pt-BR')}</p>
                        </div>
                        <p class="text-lg font-bold text-gray-900">R$ ${parseFloat(currency.usd || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                    </div>
                </div>
            `;
        }
        
        // Inscrever no newsletter
        async function subscribeNewsletter() {
            const email = document.getElementById('newsletterEmail').value;
            const name = document.getElementById('newsletterName').value;
            
            try {
                const response = await fetch(`${AGRO_NEWS_API}?action=subscribe_newsletter`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, name })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Cadastro realizado com sucesso! Você receberá nossas notícias por email.');
                    document.getElementById('newsletterForm').reset();
                } else {
                    alert('Erro: ' + (result.error || 'Não foi possível realizar o cadastro.'));
                }
            } catch (error) {
                console.error('Erro ao cadastrar:', error);
                alert('Erro ao cadastrar. Tente novamente.');
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



