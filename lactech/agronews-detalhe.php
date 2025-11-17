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
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .article-content {
            line-height: 1.8;
            font-size: 1rem;
            color: #374151;
        }
        .article-content p {
            margin-bottom: 1.25rem;
            line-height: 1.8;
            color: #374151;
        }
        .article-content h1,
        .article-content h2 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #111827;
            line-height: 1.3;
        }
        .article-content h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: #111827;
            line-height: 1.3;
        }
        .article-content h4,
        .article-content h5,
        .article-content h6 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 1.25rem;
            margin-bottom: 0.5rem;
            color: #111827;
        }
        .article-content img {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            margin: 1.5rem 0;
            display: block;
        }
        .article-content a {
            color: #2563eb;
            text-decoration: underline;
        }
        .article-content a:hover {
            color: #1d4ed8;
        }
        .article-content ul, 
        .article-content ol {
            margin: 1rem 0;
            padding-left: 2rem;
        }
        .article-content li {
            margin-bottom: 0.5rem;
            line-height: 1.7;
        }
        .article-content blockquote {
            border-left: 4px solid #e5e7eb;
            padding-left: 1.5rem;
            margin: 1.5rem 0;
            font-style: italic;
            color: #6b7280;
        }
        .article-content hr {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 2rem 0;
        }
        .article-content strong,
        .article-content b {
            font-weight: 600;
            color: #111827;
        }
        .article-content em,
        .article-content i {
            font-style: italic;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .gradient-text {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <a href="gerente-completo.php" class="flex items-center space-x-2">
                        <img src="assets/img/lactech-logo.png" alt="LacTech" class="h-8 w-8">
                        <span class="text-lg font-semibold text-gray-900">LacTech</span>
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="agronews360.php" class="text-lg font-semibold text-gray-900 hover:text-gray-700">AgroNews360</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="agronews360.php" class="text-sm text-gray-600 hover:text-gray-900 font-medium">Voltar</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="gerente-completo.php" class="text-sm text-gray-600 hover:text-gray-900 font-medium">Dashboard</a>
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
            <div class="mb-6">
                <div class="flex items-center flex-wrap gap-2 mb-4">
                    <span id="articleCategory" class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium"></span>
                    <span id="articleDate" class="text-xs text-gray-500"></span>
                    <span id="articleReadingTime" class="text-xs text-gray-500 reading-time"></span>
                </div>
                <h1 id="articleTitle" class="text-3xl md:text-4xl font-semibold text-gray-900 mb-4 leading-tight"></h1>
                <div class="flex items-center gap-4 text-sm text-gray-600 pb-4 border-b border-gray-200">
                    <span id="articleAuthor" class="font-medium"></span>
                    <span class="text-gray-300">•</span>
                    <span id="articleViews" class="text-gray-500">0 visualizações</span>
                </div>
            </div>
            
            <!-- Imagem/Vídeo de Destaque -->
            <div id="articleMedia" class="mb-6">
                <!-- Será preenchido via JavaScript -->
            </div>
            
            <!-- Conteúdo -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 md:p-8 mb-6">
                <div id="articleBody" class="article-content text-gray-800"></div>
            </div>
            
            <!-- Compartilhamento -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Compartilhar</h3>
                <div class="flex flex-wrap items-center gap-2">
                    <button onclick="shareOnWhatsApp()" class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium transition-colors">
                        WhatsApp
                    </button>
                    <button onclick="shareOnFacebook()" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium transition-colors">
                        Facebook
                    </button>
                    <button onclick="copyLink()" class="px-3 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded-md text-sm font-medium transition-colors">
                        Copiar Link
                    </button>
                </div>
            </div>
            
            <!-- Notícias Relacionadas -->
            <div id="relatedNews" class="mb-8">
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Notícias Relacionadas</h3>
                <div id="relatedNewsList" class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
        let currentArticle = null; // Armazenar artigo atual
        
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
            // Armazenar artigo atual
            currentArticle = article;
            
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('articleContent').classList.remove('hidden');
            
            // Título
            document.getElementById('articleTitle').textContent = article.title;
            
            // Categoria
            if (article.category_name) {
                document.getElementById('articleCategory').textContent = article.category_name;
            }
            
            // Data
            if (article.published_at || article.created_at) {
                const date = new Date(article.published_at || article.created_at);
                document.getElementById('articleDate').textContent = date.toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }
            
            // Tempo de leitura
            const readingTime = calculateReadingTime(article.content || '');
            if (readingTime > 0) {
                document.getElementById('articleReadingTime').textContent = `• ${readingTime} min`;
            } else {
                document.getElementById('articleReadingTime').style.display = 'none';
            }
            
            // Autor
            if (article.author_name) {
                document.getElementById('articleAuthor').textContent = article.author_name;
            } else {
                document.getElementById('articleAuthor').textContent = 'Redação AgroNews360';
            }
            
            // Visualizações
            document.getElementById('articleViews').textContent = `${article.views_count || 0} visualizações`;
            
            // Imagem ou Vídeo
            const mediaContainer = document.getElementById('articleMedia');
            const hasVideo = article.video_url || article.video_embed;
            
            if (hasVideo) {
                if (article.video_embed) {
                    mediaContainer.innerHTML = `<div class="video-container rounded-lg overflow-hidden">${article.video_embed}</div>`;
                } else if (article.video_url) {
                    let embedUrl = '';
                    if (article.video_url.includes('youtube.com/watch') || article.video_url.includes('youtu.be/')) {
                        const videoId = article.video_url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/)?.[1];
                        if (videoId) {
                            embedUrl = `https://www.youtube.com/embed/${videoId}`;
                        }
                    } else if (article.video_url.includes('vimeo.com/')) {
                        const videoId = article.video_url.match(/vimeo\.com\/(\d+)/)?.[1];
                        if (videoId) {
                            embedUrl = `https://player.vimeo.com/video/${videoId}`;
                        }
                    }
                    
                    if (embedUrl) {
                        mediaContainer.innerHTML = `
                            <div class="video-container rounded-lg overflow-hidden">
                                <iframe src="${embedUrl}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                        `;
                    } else {
                        mediaContainer.innerHTML = `
                            <div class="relative rounded-lg overflow-hidden">
                                <img src="${article.featured_image || 'assets/img/default-news.jpg'}" alt="Vídeo" class="w-full h-96 object-cover">
                                <a href="${article.video_url}" target="_blank" class="absolute inset-0 flex items-center justify-center bg-black/40 hover:bg-black/50 transition-colors">
                                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-900 ml-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                </a>
                            </div>
                        `;
                    }
                }
            } else if (article.featured_image) {
                mediaContainer.innerHTML = `
                    <img src="${article.featured_image}" alt="${article.title}" class="w-full h-96 object-cover rounded-lg">
                `;
            } else {
                mediaContainer.style.display = 'none';
            }
            
            // Conteúdo COMPLETO - garantir que está vindo completo
            const hasContent = article.content && article.content.trim();
            
            if (!hasContent) {
                document.getElementById('articleBody').innerHTML = '<p class="text-gray-500 italic">Conteúdo não disponível.</p>';
            } else {
                const contentTrimmed = article.content.trim();
                
                // Verificar se o conteúdo parece estar completo (mais de 500 caracteres)
                if (contentTrimmed.length < 500) {
                    // Conteúdo muito curto - tentar buscar conteúdo completo
                    if (article.source_url) {
                        document.getElementById('articleBody').innerHTML = `
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="text-yellow-800 text-sm mb-3">
                                            <strong>⚠️ Artigo incompleto:</strong> Este artigo parece estar incompleto no nosso banco de dados.
                                        </p>
                                        <button onclick="fetchFullContent(${article.id})" 
                                                class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md text-sm font-medium transition-colors mb-2">
                                            🔄 Buscar conteúdo completo
                                        </button>
                                    </div>
                                </div>
                                <p class="text-yellow-700 text-xs mt-3">
                                    Ou <a href="${article.source_url}" target="_blank" rel="noopener noreferrer" class="underline font-semibold">acesse a fonte original</a>.
                                </p>
                            </div>
                            <div class="article-content mt-4">
                                ${formatContent(contentTrimmed)}
                            </div>
                        `;
                    } else {
                        // Sem source_url, apenas mostrar o que tem
                        document.getElementById('articleBody').innerHTML = `
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                                <p class="text-yellow-800 text-sm">
                                    <strong>⚠️ Artigo incompleto:</strong> O conteúdo deste artigo parece estar incompleto no nosso banco de dados.
                                </p>
                            </div>
                            <div class="article-content mt-4">
                                ${formatContent(contentTrimmed)}
                            </div>
                        `;
                    }
                } else {
                    // Conteúdo parece completo, exibir normalmente
                    const formattedContent = formatContent(contentTrimmed);
                    document.getElementById('articleBody').innerHTML = formattedContent;
                }
                
                // Debug
                console.log('Conteúdo do artigo:', {
                    id: article.id,
                    title: article.title,
                    contentLength: contentTrimmed.length,
                    hasSourceUrl: !!article.source_url,
                    sourceUrl: article.source_url,
                    contentPreview: contentTrimmed.substring(0, 300) + '...'
                });
            }
            
            // Atualizar título da página
            document.title = `${article.title} | AgroNews360`;
        }
        
        // Calcular tempo de leitura
        function calculateReadingTime(text) {
            const wordsPerMinute = 200;
            const words = text.split(/\s+/).length;
            const minutes = Math.ceil(words / wordsPerMinute);
            return minutes;
        }
        
        // Formatar conteúdo (suporta HTML básico)
        function formatContent(content) {
            if (!content || content.trim() === '') return '<p class="text-gray-500 italic">Conteúdo não disponível.</p>';
            
            // Remover espaços em branco excessivos no início e fim
            content = content.trim();
            
            // Verificar se o conteúdo já contém HTML
            const hasHTML = /<[a-z][\s\S]*>/i.test(content);
            
            if (hasHTML) {
                // Se já tem HTML, criar um elemento temporário para sanitizar
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = content;
                
                // Permitir apenas tags seguras
                const allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 
                                   'ul', 'ol', 'li', 'a', 'blockquote', 'img', 'div', 'span', 'hr'];
                const allowedAttributes = {
                    'a': ['href', 'target', 'rel'],
                    'img': ['src', 'alt', 'title', 'width', 'height', 'class'],
                    'div': ['class'],
                    'span': ['class'],
                    'p': ['class']
                };
                
                // Função para sanitizar
                function sanitizeNode(node) {
                    if (node.nodeType === Node.TEXT_NODE) {
                        const text = node.textContent.trim();
                        return text ? document.createTextNode(text) : null;
                    }
                    
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        const tagName = node.tagName.toLowerCase();
                        
                        if (!allowedTags.includes(tagName)) {
                            // Se a tag não é permitida, retornar apenas o conteúdo dos filhos
                            const fragment = document.createDocumentFragment();
                            Array.from(node.childNodes).forEach(child => {
                                const sanitized = sanitizeNode(child);
                                if (sanitized) {
                                    if (sanitized.nodeType === Node.DOCUMENT_FRAGMENT_NODE) {
                                        fragment.appendChild(sanitized);
                                    } else {
                                        fragment.appendChild(sanitized);
                                    }
                                }
                            });
                            return fragment.childNodes.length > 0 ? fragment : null;
                        }
                        
                        // Criar novo elemento com tag permitida
                        const newNode = document.createElement(tagName);
                        
                        // Copiar atributos permitidos
                        if (allowedAttributes[tagName]) {
                            Array.from(node.attributes).forEach(attr => {
                                if (allowedAttributes[tagName].includes(attr.name.toLowerCase())) {
                                    newNode.setAttribute(attr.name, attr.value);
                                }
                            });
                        }
                        
                        // Sanitizar filhos
                        Array.from(node.childNodes).forEach(child => {
                            const sanitized = sanitizeNode(child);
                            if (sanitized) {
                                if (sanitized.nodeType === Node.DOCUMENT_FRAGMENT_NODE) {
                                    Array.from(sanitized.childNodes).forEach(n => newNode.appendChild(n));
                                } else {
                                    newNode.appendChild(sanitized);
                                }
                            }
                        });
                        
                        // Se o elemento ficou vazio e não é um elemento que pode ser vazio, retornar null
                        if (newNode.childNodes.length === 0 && !['br', 'hr', 'img'].includes(tagName)) {
                            return null;
                        }
                        
                        return newNode;
                    }
                    
                    return null;
                }
                
                // Sanitizar todo o conteúdo
                const sanitizedDiv = document.createElement('div');
                Array.from(tempDiv.childNodes).forEach(child => {
                    const sanitized = sanitizeNode(child);
                    if (sanitized) {
                        if (sanitized.nodeType === Node.DOCUMENT_FRAGMENT_NODE) {
                            Array.from(sanitized.childNodes).forEach(n => sanitizedDiv.appendChild(n));
                        } else {
                            sanitizedDiv.appendChild(sanitized);
                        }
                    }
                });
                
                const result = sanitizedDiv.innerHTML.trim();
                return result || '<p class="text-gray-500 italic">Conteúdo não disponível.</p>';
            } else {
                // Se é texto puro, formatar adequadamente
                // Converter quebras de linha duplas em parágrafos
                const paragraphs = content.split(/\n\s*\n/).filter(p => p.trim().length > 0);
                
                if (paragraphs.length > 1) {
                    // Múltiplos parágrafos
                    return paragraphs.map(para => {
                        const formatted = para.trim().replace(/\n/g, '<br>');
                        return `<p>${formatted}</p>`;
                    }).join('');
                } else {
                    // Um único parágrafo ou texto sem quebras duplas
                    const formatted = content.replace(/\n\n+/g, '\n\n').replace(/\n/g, '<br>');
                    return `<p>${formatted}</p>`;
                }
            }
        }
        
        // Renderizar notícias relacionadas
        function renderRelatedNews(related) {
            const container = document.getElementById('relatedNewsList');
            
            if (!related || related.length === 0) {
                document.getElementById('relatedNews').classList.add('hidden');
                return;
            }
            
            container.innerHTML = related.map(item => `
                <article class="bg-white border border-gray-200 rounded-lg overflow-hidden cursor-pointer hover:border-gray-300 transition-colors" onclick="window.location.href='agronews-detalhe.php?id=${item.id}'">
                    ${item.featured_image ? `
                        <img src="${item.featured_image}" alt="${item.title}" class="w-full h-40 object-cover">
                    ` : ''}
                    <div class="p-4">
                        <span class="inline-block px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium mb-2">${item.category_name || 'Notícia'}</span>
                        <h4 class="font-semibold text-gray-900 mb-2 line-clamp-2 text-sm leading-tight">${item.title}</h4>
                        <p class="text-xs text-gray-600 line-clamp-2">${item.summary || item.content.substring(0, 120) + '...'}</p>
                    </div>
                </article>
            `).join('');
        }
        
        // Buscar conteúdo completo
        async function fetchFullContent(articleId) {
            const articleBody = document.getElementById('articleBody');
            const originalContent = articleBody.innerHTML;
            
            // Mostrar loading
            articleBody.innerHTML = `
                <div class="text-center py-8">
                    <div class="inline-block w-8 h-8 border-4 border-yellow-600 border-t-transparent rounded-full animate-spin"></div>
                    <p class="mt-4 text-gray-600">Buscando conteúdo completo...</p>
                </div>
            `;
            
            try {
                const response = await fetch(`${AGRO_NEWS_API}?action=fetch_full_content&id=${articleId}`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.content) {
                    const newContent = result.data.content.trim();
                    if (newContent.length > 500) {
                        // Conteúdo completo encontrado
                        articleBody.innerHTML = formatContent(newContent);
                    } else {
                        // Ainda incompleto
                        articleBody.innerHTML = `
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                                <p class="text-yellow-800 text-sm mb-3">
                                    Não foi possível buscar o conteúdo completo automaticamente.
                                </p>
                                <a href="${currentArticle?.source_url || '#'}" target="_blank" rel="noopener noreferrer" 
                                   class="inline-block px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md text-sm font-medium transition-colors">
                                    Acessar fonte original
                                </a>
                            </div>
                            <div class="article-content mt-4">
                                ${formatContent(newContent)}
                            </div>
                        `;
                    }
                } else {
                    // Erro ao buscar
                    articleBody.innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <p class="text-red-800 text-sm mb-3">
                                Erro ao buscar conteúdo completo: ${result.error || 'Erro desconhecido'}
                            </p>
                            <button onclick="location.reload()" 
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium transition-colors">
                                Recarregar página
                            </button>
                        </div>
                        ${originalContent}
                    `;
                }
            } catch (error) {
                console.error('Erro ao buscar conteúdo:', error);
                articleBody.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <p class="text-red-800 text-sm">
                            Erro ao buscar conteúdo completo. Tente novamente mais tarde.
                        </p>
                    </div>
                    ${originalContent}
                `;
            }
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
                // Mostrar notificação visual
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span>Copiado!</span>';
                btn.classList.add('bg-green-600');
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.remove('bg-green-600');
                }, 2000);
            }).catch(() => {
                alert('Erro ao copiar link');
            });
        }
    </script>
</body>
</html>











