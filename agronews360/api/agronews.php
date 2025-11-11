<?php
/**
 * API AgroNews360
 * Busca notícias diretamente da web via RSS feeds e APIs públicas
 * NÃO usa banco de dados para armazenar notícias - tudo em tempo real da web
 * Apenas usuários, login, cadastro e newsletter usam banco de dados
 */

// Detectar se está em localhost para mostrar mais detalhes de erro
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
           strpos($_SERVER['SERVER_NAME'] ?? '', '192.168.') === 0 ||
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

// Desabilitar exibição de erros (para não quebrar JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers ANTES de qualquer output
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Função para retornar erro JSON de forma consistente
function returnError($message, $code = 500, $details = null) {
    http_response_code($code);
    $error = ['success' => false, 'error' => $message];
    
    // Em localhost, adicionar mais detalhes
    global $isLocal;
    if ($isLocal && $details !== null) {
        $error['details'] = $details;
    }
    
    echo json_encode($error, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Carregar banco apenas para usuários/newsletter
$configFile = __DIR__ . '/../includes/config_mysql.php';
$databaseFile = __DIR__ . '/../includes/Database.class.php';

$db = null;
if (file_exists($configFile) && file_exists($databaseFile)) {
    try {
        require_once $configFile;
        require_once $databaseFile;
        $db = Database::getInstance();
    } catch (Exception $e) {
        // Banco é opcional - apenas para usuários/newsletter
        error_log("Aviso: Banco de dados não disponível: " . $e->getMessage());
    }
}

// Obter ação
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (empty($action)) {
    returnError('Parâmetro action não fornecido', 400);
}

try {
    switch ($action) {
        case 'get_categories':
            getCategories();
            break;
            
        case 'get_articles':
            getArticles();
            break;
            
        case 'get_featured':
            getFeaturedArticles();
            break;
            
        case 'get_article':
            getArticle();
            break;
            
        case 'get_weather':
            getWeather();
            break;
            
        case 'get_currency':
            getCurrency();
            break;
            
        case 'subscribe_newsletter':
            if ($db) {
                subscribeNewsletter($db);
            } else {
                returnError('Newsletter não disponível - banco de dados não configurado', 503);
            }
            break;
            
        default:
            returnError('Ação não encontrada: ' . $action, 404);
            break;
    }
} catch (Exception $e) {
    $message = $isLocal ? $e->getMessage() : 'Erro interno do servidor';
    error_log("Erro na API AgroNews360: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
    returnError($message, 500, [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    $message = $isLocal ? $e->getMessage() : 'Erro fatal';
    error_log("Erro fatal na API AgroNews360: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
    returnError($message, 500, [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

// ==========================================
// FEEDS RSS PÚBLICOS DO AGRONEGÓCIO
// ==========================================

function getRSSFeeds() {
    return [
        [
            'url' => 'https://www.canalrural.com.br/feed/',
            'name' => 'Canal Rural',
            'category' => 'geral'
        ],
        [
            'url' => 'https://www.globorural.globo.com/rss.xml',
            'name' => 'Globo Rural',
            'category' => 'geral'
        ],
        [
            'url' => 'https://www.noticiasagricolas.com.br/rss',
            'name' => 'Notícias Agrícolas',
            'category' => 'agricultura'
        ],
        [
            'url' => 'https://www.agrolink.com.br/rss/noticias.xml',
            'name' => 'Agrolink',
            'category' => 'geral'
        ],
        [
            'url' => 'https://www.agrobrasil.com.br/feed/',
            'name' => 'Agro Brasil',
            'category' => 'geral'
        ],
        [
            'url' => 'https://feeds.feedburner.com/agropecuaria',
            'name' => 'Agropecuária',
            'category' => 'pecuaria'
        ],
        [
            'url' => 'https://www.agrosoft.com.br/feed/',
            'name' => 'Agrosoft',
            'category' => 'tecnologia-inovacao'
        ]
    ];
}

// ==========================================
// FUNÇÕES DE CATEGORIAS (FIXAS - NÃO DO BANCO)
// ==========================================

function getCategories() {
    $categories = [
        [
            'id' => 1,
            'name' => 'Pecuária',
            'slug' => 'pecuaria',
            'icon' => '🐄',
            'color' => 'blue',
            'description' => 'Notícias sobre pecuária, gado, leite e produção animal',
            'is_active' => 1
        ],
        [
            'id' => 2,
            'name' => 'Agricultura',
            'slug' => 'agricultura',
            'icon' => '🌱',
            'color' => 'green',
            'description' => 'Notícias sobre agricultura, plantio e colheita',
            'is_active' => 1
        ],
        [
            'id' => 3,
            'name' => 'Mercado e Economia',
            'slug' => 'mercado-economia',
            'icon' => '💰',
            'color' => 'yellow',
            'description' => 'Cotações, preços e análises de mercado',
            'is_active' => 1
        ],
        [
            'id' => 4,
            'name' => 'Clima e Previsões',
            'slug' => 'clima-previsoes',
            'icon' => '🌦️',
            'color' => 'cyan',
            'description' => 'Previsões climáticas e alertas meteorológicos',
            'is_active' => 1
        ],
        [
            'id' => 5,
            'name' => 'Tecnologia e Inovação',
            'slug' => 'tecnologia-inovacao',
            'icon' => '🧫',
            'color' => 'purple',
            'description' => 'Tecnologias e inovações no agronegócio',
            'is_active' => 1
        ],
        [
            'id' => 6,
            'name' => 'Notícias Gerais',
            'slug' => 'noticias-gerais',
            'icon' => '📣',
            'color' => 'red',
            'description' => 'Notícias gerais do agronegócio',
            'is_active' => 1
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $categories
    ], JSON_UNESCAPED_UNICODE);
}

// ==========================================
// FUNÇÕES DE ARTIGOS (BUSCAR DA WEB)
// ==========================================

function getArticles() {
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 10);
    
    // Aceitar category_id (pode ser ID numérico ou slug) ou category_slug
    $categoryParam = $_GET['category_id'] ?? $_GET['category_slug'] ?? null;
    $categorySlug = null;
    
    // Se for um número, converter para slug
    if ($categoryParam && is_numeric($categoryParam)) {
        $categoryMap = [
            1 => 'pecuaria',
            2 => 'agricultura',
            3 => 'mercado-economia',
            4 => 'clima-previsoes',
            5 => 'tecnologia-inovacao',
            6 => 'noticias-gerais'
        ];
        $categorySlug = $categoryMap[$categoryParam] ?? null;
    } elseif ($categoryParam) {
        $categorySlug = $categoryParam;
    }
    
    $search = $_GET['search'] ?? null;
    
    try {
        $allArticles = fetchArticlesFromRSS($categorySlug, $search);
        
        // Paginação
        $total = count($allArticles);
        $offset = ($page - 1) * $limit;
        $articles = array_slice($allArticles, $offset, $limit);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'articles' => $articles,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => $total,
                    'items_per_page' => $limit
                ]
            ]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        returnError('Erro ao buscar artigos: ' . $e->getMessage(), 500);
    }
}

function getFeaturedArticles() {
    $limit = intval($_GET['limit'] ?? 4);
    
    try {
        $articles = fetchArticlesFromRSS(null, null, $limit);
        
        echo json_encode([
            'success' => true,
            'data' => $articles
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        returnError('Erro ao buscar artigos em destaque: ' . $e->getMessage(), 500);
    }
}

function getArticle() {
    $id = $_GET['id'] ?? '';
    $slug = $_GET['slug'] ?? '';
    
    if (empty($id) && empty($slug)) {
        returnError('ID ou slug não fornecido', 400);
    }
    
    try {
        // Buscar mais artigos para ter mais chances de encontrar o específico
        $articles = fetchArticlesFromRSS(null, null, 100);
        
        $article = null;
        if (!empty($id)) {
            // Procurar por ID
            foreach ($articles as $art) {
                if ($art['id'] == $id || strpos($art['id'], $id) !== false) {
                    $article = $art;
                    break;
                }
            }
        }
        
        if (!$article && !empty($slug)) {
            // Procurar por slug
            foreach ($articles as $art) {
                if ($art['slug'] == $slug || strpos($art['slug'], $slug) !== false) {
                    $article = $art;
                    break;
                }
            }
        }
        
        if (!$article && !empty($id)) {
            // Se não encontrou, tentar buscar artigos relacionados
            // e usar o primeiro como fallback (pode ser que o ID tenha mudado)
            if (count($articles) > 0) {
                $article = $articles[0];
            }
        }
        
        if (!$article) {
            returnError('Artigo não encontrado. Os artigos são buscados em tempo real dos feeds RSS. Tente novamente.', 404);
            return;
        }
        
        // Buscar artigos relacionados (mesma categoria, excluindo o atual)
        $related = array_filter($articles, function($art) use ($article) {
            return $art['id'] != $article['id'] && 
                   $art['category_id'] == $article['category_id'];
        });
        $related = array_slice(array_values($related), 0, 3);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'article' => $article,
                'related' => $related
            ]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        returnError('Erro ao buscar artigo: ' . $e->getMessage(), 500);
    }
}

// ==========================================
// BUSCAR ARTIGOS DOS FEEDS RSS
// ==========================================

function fetchArticlesFromRSS($categorySlug = null, $search = null, $limit = 50) {
    $feeds = getRSSFeeds();
    $articles = [];
    $processed = 0;
    
    // Mapear categoria para feeds
    $categoryMap = [
        'pecuaria' => ['pecuaria', 'geral'],
        'agricultura' => ['agricultura', 'geral'],
        'tecnologia-inovacao' => ['tecnologia-inovacao', 'geral'],
        'mercado-economia' => ['geral'],
        'clima-previsoes' => ['geral']
    ];
    
    $feedsToProcess = $feeds;
    if ($categorySlug && isset($categoryMap[$categorySlug])) {
        $feedsToProcess = array_filter($feeds, function($feed) use ($categoryMap, $categorySlug) {
            return in_array($feed['category'], $categoryMap[$categorySlug]);
        });
    }
    
    foreach ($feedsToProcess as $feed) {
        if ($processed >= $limit) break;
        
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0 (compatible; AgroNews360/1.0)',
                    'follow_location' => true,
                    'max_redirects' => 5
                ]
            ]);
            
            $rss = @simplexml_load_file($feed['url'], 'SimpleXMLElement', LIBXML_NOCDATA, $context);
            
            if ($rss && isset($rss->channel->item)) {
                foreach ($rss->channel->item as $item) {
                    if ($processed >= $limit) break;
                    
                    $title = trim((string)$item->title);
                    $link = trim((string)$item->link);
                    $description = strip_tags(trim((string)$item->description));
                    $pubDate = isset($item->pubDate) ? strtotime($item->pubDate) : time();
                    
                    // Filtrar por busca
                    if ($search) {
                        $searchLower = mb_strtolower($search, 'UTF-8');
                        $titleLower = mb_strtolower($title, 'UTF-8');
                        $descLower = mb_strtolower($description, 'UTF-8');
                        if (strpos($titleLower, $searchLower) === false && strpos($descLower, $searchLower) === false) {
                            continue;
                        }
                    }
                    
                    // Extrair imagem
                    $image = '';
                    if (isset($item->enclosure) && isset($item->enclosure['type']) && strpos($item->enclosure['type'], 'image') !== false) {
                        $image = (string)$item->enclosure['url'];
                    } elseif (isset($item->children('media', true)->content)) {
                        $media = $item->children('media', true);
                        if (isset($media->content->attributes()->url)) {
                            $image = (string)$media->content->attributes()->url;
                        }
                    } elseif (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $description, $matches)) {
                        $image = $matches[1];
                    }
                    
                    // Se não tem imagem, buscar uma genérica do Unsplash
                    if (empty($image)) {
                        $image = getRandomAgroImage();
                    }
                    
                    // Determinar categoria
                    $category = determineCategoryFromContent($title, $description);
                    
                    // Criar slug único
                    $slug = createSlug($title) . '-' . substr(md5($link), 0, 8);
                    $id = 'article-' . substr(md5($link), 0, 12);
                    
                    $article = [
                        'id' => $id,
                        'title' => $title,
                        'slug' => $slug,
                        'summary' => mb_substr($description, 0, 250) . '...',
                        'content' => $description,
                        'featured_image' => $image,
                        'category_id' => $category['id'],
                        'category_name' => $category['name'],
                        'category_icon' => $category['icon'],
                        'category_color' => $category['color'],
                        'source' => $feed['name'],
                        'source_url' => $link,
                        'author_name' => $feed['name'],
                        'is_featured' => $processed < 3 ? 1 : 0,
                        'is_published' => 1,
                        'views_count' => 0,
                        'published_at' => date('Y-m-d H:i:s', $pubDate),
                        'created_at' => date('Y-m-d H:i:s', $pubDate)
                    ];
                    
                    $articles[] = $article;
                    $processed++;
                }
            }
        } catch (Exception $e) {
            error_log("Erro ao processar feed {$feed['url']}: " . $e->getMessage());
            continue;
        }
    }
    
    // Ordenar por data (mais recente primeiro)
    usort($articles, function($a, $b) {
        return strtotime($b['published_at']) - strtotime($a['published_at']);
    });
    
    return array_slice($articles, 0, $limit);
}


function determineCategoryFromContent($title, $description) {
    $text = mb_strtolower($title . ' ' . $description, 'UTF-8');
    
    $keywords = [
        'pecuaria' => ['gado', 'boi', 'vaca', 'leite', 'pecuária', 'pecuaria', 'gado de corte', 'gado leiteiro', 'bovino', 'ovino', 'suíno', 'carne'],
        'agricultura' => ['soja', 'milho', 'café', 'cana', 'trigo', 'arroz', 'feijão', 'plantio', 'colheita', 'safra', 'lavoura', 'agricultura'],
        'mercado-economia' => ['preço', 'cotação', 'mercado', 'economia', 'dólar', 'exportação', 'importação', 'comércio', 'comercio'],
        'clima-previsoes' => ['clima', 'chuva', 'temperatura', 'seca', 'previsão', 'previsao', 'tempo', 'meteorologia', 'climático'],
        'tecnologia-inovacao' => ['tecnologia', 'inovação', 'inovacao', 'digital', 'agtech', 'drones', 'agricultura 4.0', 'iot', 'ia', 'inteligência artificial']
    ];
    
    foreach ($keywords as $slug => $keys) {
        foreach ($keys as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return getCategoryBySlug($slug);
            }
        }
    }
    
    return getCategoryBySlug('noticias-gerais');
}

function getCategoryBySlug($slug) {
    $categories = [
        'pecuaria' => ['id' => 1, 'name' => 'Pecuária', 'icon' => '🐄', 'color' => 'blue'],
        'agricultura' => ['id' => 2, 'name' => 'Agricultura', 'icon' => '🌱', 'color' => 'green'],
        'mercado-economia' => ['id' => 3, 'name' => 'Mercado e Economia', 'icon' => '💰', 'color' => 'yellow'],
        'clima-previsoes' => ['id' => 4, 'name' => 'Clima e Previsões', 'icon' => '🌦️', 'color' => 'cyan'],
        'tecnologia-inovacao' => ['id' => 5, 'name' => 'Tecnologia e Inovação', 'icon' => '🧫', 'color' => 'purple'],
        'noticias-gerais' => ['id' => 6, 'name' => 'Notícias Gerais', 'icon' => '📣', 'color' => 'red']
    ];
    
    return $categories[$slug] ?? $categories['noticias-gerais'];
}

function createSlug($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    if (strlen($text) > 100) {
        $text = substr($text, 0, 100);
        $text = rtrim($text, '-');
    }
    return $text ?: 'artigo-' . time();
}

function getRandomAgroImage() {
    $keywords = ['farm', 'agriculture', 'cattle', 'crop', 'field', 'harvest', 'tractor', 'ranch'];
    $keyword = $keywords[array_rand($keywords)];
    return "https://source.unsplash.com/1200x600/?{$keyword}";
}

// ==========================================
// FUNÇÕES DE CLIMA (API PÚBLICA)
// ==========================================

function getWeather() {
    $city = $_GET['city'] ?? 'São Paulo';
    $state = $_GET['state'] ?? 'SP';
    
    try {
        $cityEncoded = urlencode($city);
        $url = "https://wttr.in/{$cityEncoded}?format=j1&lang=pt";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            
            if (isset($data['current_condition'][0])) {
                $current = $data['current_condition'][0];
                $today = $data['weather'][0];
                
                $weather = [
                    'region' => "{$city}, {$state}",
                    'temperature' => (float)$current['temp_C'],
                    'min_temperature' => (float)$today['mintempC'],
                    'max_temperature' => (float)$today['maxtempC'],
                    'humidity' => (int)$current['humidity'],
                    'rain_probability' => isset($today['hourly'][0]['chanceofrain']) ? (int)$today['hourly'][0]['chanceofrain'] : 0,
                    'condition' => $current['lang_pt'][0]['value'] ?? 'Nublado',
                    'wind_speed' => isset($current['windspeedKmph']) ? (float)$current['windspeedKmph'] : 0,
                    'forecast_date' => date('Y-m-d')
                ];
                
                echo json_encode([
                    'success' => true,
                    'data' => $weather
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        }
        
        returnError('Não foi possível buscar dados climáticos', 500);
    } catch (Exception $e) {
        returnError('Erro ao buscar clima: ' . $e->getMessage(), 500);
    }
}

// ==========================================
// FUNÇÕES DE CÂMBIO (API PÚBLICA)
// ==========================================

function getCurrency() {
    try {
        // API ExchangeRate (gratuita, sem chave)
        $url = 'https://api.exchangerate-api.com/v4/latest/USD';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            
            if (isset($data['rates']['BRL'])) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'usd' => round($data['rates']['BRL'], 2),
                        'usd_brl' => round($data['rates']['BRL'], 2),
                        'source' => 'ExchangeRate-API',
                        'last_update' => date('Y-m-d H:i:s')
                    ]
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        }
        
        // Fallback: Banco Central
        $bcUrl = 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.1/dados/ultimos/1?formato=json';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $bcUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $bcResponse = curl_exec($ch);
        curl_close($ch);
        
        if ($bcResponse) {
            $bcData = json_decode($bcResponse, true);
            if (isset($bcData[0]['valor'])) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'usd' => round($bcData[0]['valor'], 2),
                        'usd_brl' => round($bcData[0]['valor'], 2),
                        'source' => 'Banco Central do Brasil',
                        'last_update' => date('Y-m-d H:i:s')
                    ]
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        }
        
        returnError('Não foi possível buscar taxa de câmbio', 500);
    } catch (Exception $e) {
        returnError('Erro ao buscar câmbio: ' . $e->getMessage(), 500);
    }
}

// ==========================================
// FUNÇÕES DE NEWSLETTER (USA BANCO)
// ==========================================

function subscribeNewsletter($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $email = $data['email'] ?? '';
        $name = $data['name'] ?? null;
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Email inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $pdo = $db->getConnection();
        
        // Verificar se já existe
        $existing = $db->query("SELECT id FROM agronews_newsletter WHERE email = ?", [$email]);
        
        if (!empty($existing)) {
            // Reativar se estava desativado
            $stmt = $pdo->prepare("UPDATE agronews_newsletter SET is_active = 1, name = ?, unsubscribed_at = NULL WHERE email = ?");
            $stmt->execute([$name, $email]);
        } else {
            // Inserir novo
            $stmt = $pdo->prepare("INSERT INTO agronews_newsletter (email, name) VALUES (?, ?)");
            $stmt->execute([$email, $name]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Inscrição realizada com sucesso!'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}
