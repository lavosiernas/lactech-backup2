<?php
/**
 * AgroNews360 - Detalhe da Notícia
 * Página para exibir notícia completa com informações detalhadas
 */

require_once __DIR__ . '/includes/config_login.php';

// Verificar autenticação (opcional)
$requireAuth = false;

if ($requireAuth && !isLoggedIn()) {
    safeRedirect('inicio-login.php');
    exit;
}

$user = null;
if (isLoggedIn()) {
    $user = $_SESSION['user'] ?? null;
}

$articleId = intval($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notícia | AgroNews360 - LacTech</title>
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
        .article-content {
            line-height: 1.8;
        }
        .article-content p {
            margin-bottom: 1.5rem;
        }
        .article-content h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .article-content h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .article-content img {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            margin: 1.5rem 0;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-4">
                    <a href="gerente-completo.php" class="flex items-center space-x-2">
                        <img src="assets/img/lactech-logo.png" alt="LacTech" class="h-10 w-10">
                        <span class="text-xl font-bold text-gray-900">LacTech</span>
                    </a>
                    <span class="text-gray-400">|</span>
                    <a href="agronews360.php" class="text-xl font-bold text-agro-green">AgroNews360</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="agronews360.php" class="text-gray-700 hover:text-agro-green font-medium">← Voltar</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="gerente-completo.php" class="text-gray-700 hover:text-agro-green font-medium">Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Conteúdo Principal -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Loading -->
        <div id="loadingState" class="text-center py-12">
            <div class="inline-block w-8 h-8 border-4 border-agro-green border-t-transparent rounded-full animate-spin"></div>
            <p class="mt-4 text-gray-600">Carregando notícia...</p>
        </div>
        
        <!-- Artigo -->
        <article id="articleContent" class="hidden">
            <!-- Header do Artigo -->
            <div class="mb-8">
                <div class="flex items-center space-x-2 mb-4">
                    <span id="articleCategoryIcon" class="text-2xl">📰</span>
                    <span id="articleCategory" class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium"></span>
                    <span id="articleDate" class="text-sm text-gray-500"></span>
                </div>
                <h1 id="articleTitle" class="text-4xl font-bold text-gray-900 mb-4"></h1>
                <div class="flex items-center space-x-4 text-sm text-gray-600">
                    <span id="articleAuthor"></span>
                    <span>•</span>
                    <span id="articleViews">0 visualizações</span>
                </div>
            </div>
            
            <!-- Imagem de Destaque -->
            <div id="articleImage" class="mb-8 rounded-xl overflow-hidden shadow-lg">
                <!-- Será preenchido via JavaScript -->
            </div>
            
            <!-- Conteúdo -->
            <div class="bg-white rounded-xl shadow-sm p-8 mb-8">
                <div id="articleSummary" class="text-xl text-gray-700 font-medium mb-6 pb-6 border-b border-gray-200"></div>
                <div id="articleBody" class="article-content text-gray-800"></div>
            </div>
            
            <!-- Compartilhamento -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Compartilhar</h3>
                <div class="flex items-center space-x-4">
                    <button onclick="shareOnWhatsApp()" class="flex items-center space-x-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                        <span>WhatsApp</span>
                    </button>
                    <button onclick="shareOnFacebook()" class="flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        <span>Facebook</span>
                    </button>
                    <button onclick="copyLink()" class="flex items-center space-x-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span>Copiar Link</span>
                    </button>
                </div>
            </div>
            
            <!-- Notícias Relacionadas -->
            <div id="relatedNews" class="mb-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Notícias Relacionadas</h3>
                <div id="relatedNewsList" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Será preenchido via JavaScript -->
                </div>
            </div>
        </article>
        
        <!-- Erro -->
        <div id="errorState" class="hidden text-center py-12">
            <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                <p class="text-red-600 font-semibold">Erro ao carregar notícia</p>
                <p class="text-red-500 text-sm mt-2" id="errorMessage"></p>
                <a href="agronews360.php" class="inline-block mt-4 px-6 py-2 bg-agro-green text-white rounded-lg hover:bg-green-600 transition-colors">
                    Voltar para Notícias
                </a>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center text-sm text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> LacTech. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    
    <script>
        const AGRO_NEWS_API = 'api/agronews.php';
        const articleId = <?php echo $articleId; ?>;
        
        // Carregar artigo ao carregar página
        document.addEventListener('DOMContentLoaded', function() {
            if (articleId) {
                loadArticle(articleId);
            } else {
                showError('ID da notícia não fornecido');
            }
        });
        
        // Carregar artigo
        async function loadArticle(id) {
            try {
                const response = await fetch(`${AGRO_NEWS_API}?action=get_article&id=${id}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    renderArticle(result.data.article);
                    renderRelatedNews(result.data.related || []);
                } else {
                    showError(result.error || 'Artigo não encontrado');
                }
            } catch (error) {
                console.error('Erro ao carregar artigo:', error);
                showError('Erro ao carregar artigo');
            }
        }
        
        // Renderizar artigo
        function renderArticle(article) {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('articleContent').classList.remove('hidden');
            
            // Título
            document.getElementById('articleTitle').textContent = article.title;
            
            // Categoria
            if (article.category_icon) {
                document.getElementById('articleCategoryIcon').textContent = article.category_icon;
            }
            if (article.category_name) {
                document.getElementById('articleCategory').textContent = article.category_name;
            }
            
            // Data
            if (article.published_at || article.created_at) {
                const date = new Date(article.published_at || article.created_at);
                document.getElementById('articleDate').textContent = date.toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                });
            }
            
            // Autor
            if (article.author_name) {
                document.getElementById('articleAuthor').textContent = `Por ${article.author_name}`;
            }
            
            // Visualizações
            document.getElementById('articleViews').textContent = `${article.views_count || 0} visualizações`;
            
            // Imagem
            if (article.featured_image) {
                document.getElementById('articleImage').innerHTML = `
                    <img src="${article.featured_image}" alt="${article.title}" class="w-full h-96 object-cover">
                `;
            } else {
                document.getElementById('articleImage').classList.add('hidden');
            }
            
            // Resumo
            if (article.summary) {
                document.getElementById('articleSummary').textContent = article.summary;
            } else {
                document.getElementById('articleSummary').classList.add('hidden');
            }
            
            // Conteúdo
            document.getElementById('articleBody').innerHTML = formatContent(article.content);
            
            // Atualizar título da página
            document.title = `${article.title} | AgroNews360`;
        }
        
        // Formatar conteúdo (suporta HTML básico)
        function formatContent(content) {
            if (!content) return '';
            
            // Escapar HTML perigoso mas permitir tags básicas
            const div = document.createElement('div');
            div.textContent = content;
            let html = div.innerHTML;
            
            // Converter quebras de linha em parágrafos
            html = html.split('\n\n').map(para => {
                if (para.trim()) {
                    return `<p>${para.trim().replace(/\n/g, '<br>')}</p>`;
                }
                return '';
            }).join('');
            
            return html;
        }
        
        // Renderizar notícias relacionadas
        function renderRelatedNews(related) {
            const container = document.getElementById('relatedNewsList');
            
            if (!related || related.length === 0) {
                document.getElementById('relatedNews').classList.add('hidden');
                return;
            }
            
            container.innerHTML = related.map(item => `
                <article class="bg-white rounded-xl shadow-sm overflow-hidden news-card cursor-pointer" onclick="window.location.href='agronews-detalhe.php?id=${item.id}'">
                    ${item.featured_image ? `
                        <img src="${item.featured_image}" alt="${item.title}" class="w-full h-48 object-cover">
                    ` : ''}
                    <div class="p-4">
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="text-sm">${item.category_icon || '📰'}</span>
                            <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium">${item.category_name || 'Notícia'}</span>
                        </div>
                        <h4 class="font-bold text-gray-900 mb-2 line-clamp-2">${item.title}</h4>
                        <p class="text-sm text-gray-600 line-clamp-2">${item.summary || item.content.substring(0, 100) + '...'}</p>
                    </div>
                </article>
            `).join('');
        }
        
        // Mostrar erro
        function showError(message) {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('errorState').classList.remove('hidden');
            document.getElementById('errorMessage').textContent = message;
        }
        
        // Compartilhar no WhatsApp
        function shareOnWhatsApp() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent(document.getElementById('articleTitle').textContent);
            window.open(`https://wa.me/?text=${text}%20${url}`, '_blank');
        }
        
        // Compartilhar no Facebook
        function shareOnFacebook() {
            const url = encodeURIComponent(window.location.href);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
        }
        
        // Copiar link
        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('Link copiado para a área de transferência!');
            }).catch(() => {
                alert('Erro ao copiar link');
            });
        }
    </script>
</body>
</html>

