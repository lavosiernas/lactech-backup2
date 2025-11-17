<?php
/**
 * API AgroNews360
 * Endpoint para gerenciar notícias, cotações, clima e dados do agronegócio
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/Database.class.php';

// Obter ação
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    switch ($action) {
        case 'get_categories':
            getCategories($db);
            break;
            
        case 'get_articles':
            getArticles($db);
            break;
            
        case 'get_featured':
            getFeaturedArticles($db);
            break;
            
        case 'get_article':
            getArticle($db);
            break;
            
        case 'fetch_full_content':
            fetchFullContent($db);
            break;
            
        case 'create_article':
            createArticle($db);
            break;
            
        case 'update_article':
            updateArticle($db);
            break;
            
        case 'delete_article':
            deleteArticle($db);
            break;
            
        case 'get_quotations':
            getQuotations($db);
            break;
            
        case 'create_quotation':
            createQuotation($db);
            break;
            
        case 'get_weather':
            getWeather($db);
            break;
            
        case 'create_weather':
            createWeather($db);
            break;
            
        case 'get_currency':
            getCurrency($db);
            break;
            
        case 'subscribe_newsletter':
            subscribeNewsletter($db);
            break;
            
        case 'increment_views':
            incrementViews($db);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Ação não encontrada']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ==========================================
// FUNÇÕES DE CATEGORIAS
// ==========================================

function getCategories($db) {
    try {
        $categories = $db->query("SELECT * FROM agronews_categories WHERE is_active = 1 ORDER BY name ASC");
        
        echo json_encode([
            'success' => true,
            'data' => $categories
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ==========================================
// FUNÇÕES DE ARTIGOS
// ==========================================

function getArticles($db) {
    try {
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        $categoryId = $_GET['category_id'] ?? null;
        $search = $_GET['search'] ?? null;
        
        $where = ["is_published = 1"];
        $params = [];
        
        if ($categoryId) {
            $where[] = "category_id = ?";
            $params[] = $categoryId;
        }
        
        if ($search) {
            $where[] = "(title LIKE ? OR summary LIKE ? OR content LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Contar total
        $countResult = $db->query("SELECT COUNT(*) as total FROM agronews_articles WHERE {$whereClause}", $params);
        $total = $countResult[0]['total'] ?? 0;
        
        // Verificar se campos de vídeo existem
        $hasVideoFields = false;
        try {
            $db->query("SELECT video_url, video_embed FROM agronews_articles LIMIT 1");
            $hasVideoFields = true;
        } catch (Exception $e) {
            // Campos não existem ainda
        }
        
        // Buscar artigos
        $sql = "SELECT a.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
                       u.name as author_name" . 
                       ($hasVideoFields ? ", a.video_url, a.video_embed" : "") . "
                FROM agronews_articles a
                LEFT JOIN agronews_categories c ON a.category_id = c.id
                LEFT JOIN users u ON a.author_id = u.id
                WHERE {$whereClause}
                ORDER BY a.published_at DESC, a.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $articles = $db->query($sql, $params);
        
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
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function getFeaturedArticles($db) {
    try {
        $limit = intval($_GET['limit'] ?? 4);
        
        // Verificar se campos de vídeo existem
        $hasVideoFields = false;
        try {
            $db->query("SELECT video_url, video_embed FROM agronews_articles LIMIT 1");
            $hasVideoFields = true;
        } catch (Exception $e) {
            // Campos não existem ainda
        }
        
        $articles = $db->query("SELECT a.*, c.name as category_name, c.icon as category_icon" . 
                               ($hasVideoFields ? ", a.video_url, a.video_embed" : "") . "
                           FROM agronews_articles a
                           LEFT JOIN agronews_categories c ON a.category_id = c.id
                           WHERE a.is_published = 1 AND a.is_featured = 1
                           ORDER BY a.published_at DESC, a.created_at DESC
                           LIMIT ?", [$limit]);
        
        echo json_encode([
            'success' => true,
            'data' => $articles
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function getArticle($db) {
    try {
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID não fornecido']);
            return;
        }
        
        // Verificar se campos de vídeo existem
        $hasVideoFields = false;
        try {
            $db->query("SELECT video_url, video_embed FROM agronews_articles LIMIT 1");
            $hasVideoFields = true;
        } catch (Exception $e) {
            // Campos não existem ainda
        }
        
        $articleResult = $db->query("SELECT a.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
                                   u.name as author_name, u.email as author_email" . 
                                   ($hasVideoFields ? ", a.video_url, a.video_embed" : "") . "
                           FROM agronews_articles a
                           LEFT JOIN agronews_categories c ON a.category_id = c.id
                           LEFT JOIN users u ON a.author_id = u.id
                           WHERE a.id = ? AND a.is_published = 1", [$id]);
        
        $article = $articleResult[0] ?? null;
        
        if (!$article) {
            echo json_encode(['success' => false, 'error' => 'Artigo não encontrado']);
            return;
        }
        
        // Incrementar visualizações
        incrementViews($db, $id);
        
        // Buscar artigos relacionados
        $related = $db->query("SELECT a.*, c.name as category_name" . 
                              ($hasVideoFields ? ", a.video_url, a.video_embed" : "") . "
                                  FROM agronews_articles a
                                  LEFT JOIN agronews_categories c ON a.category_id = c.id
                                  WHERE a.category_id = ? AND a.id != ? AND a.is_published = 1
                                  ORDER BY a.published_at DESC
                                  LIMIT 3", [$article['category_id'], $id]);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'article' => $article,
                'related' => $related
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function createArticle($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $title = $data['title'] ?? '';
        $slug = createSlug($title);
        $summary = $data['summary'] ?? null;
        $content = $data['content'] ?? '';
        $featuredImage = $data['featured_image'] ?? null;
        $categoryId = $data['category_id'] ?? null;
        $authorId = $data['author_id'] ?? null;
        $source = $data['source'] ?? null;
        $sourceUrl = $data['source_url'] ?? null;
        $isFeatured = intval($data['is_featured'] ?? 0);
        $isPublished = intval($data['is_published'] ?? 1);
        $publishedAt = $data['published_at'] ?? date('Y-m-d H:i:s');
        
        if (empty($title) || empty($content)) {
            echo json_encode(['success' => false, 'error' => 'Título e conteúdo são obrigatórios']);
            return;
        }
        
        $sql = "INSERT INTO agronews_articles 
                (title, slug, summary, content, featured_image, category_id, author_id, source, source_url, 
                 is_featured, is_published, published_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $title, $slug, $summary, $content, $featuredImage, $categoryId, $authorId,
            $source, $sourceUrl, $isFeatured, $isPublished, $publishedAt
        ];
        
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $articleId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'data' => ['id' => $articleId, 'slug' => $slug]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function updateArticle($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID não fornecido']);
            return;
        }
        
        $updates = [];
        $params = [];
        
        if (isset($data['title'])) {
            $updates[] = "title = ?";
            $params[] = $data['title'];
            $updates[] = "slug = ?";
            $params[] = createSlug($data['title']);
        }
        if (isset($data['summary'])) {
            $updates[] = "summary = ?";
            $params[] = $data['summary'];
        }
        if (isset($data['content'])) {
            $updates[] = "content = ?";
            $params[] = $data['content'];
        }
        if (isset($data['featured_image'])) {
            $updates[] = "featured_image = ?";
            $params[] = $data['featured_image'];
        }
        if (isset($data['category_id'])) {
            $updates[] = "category_id = ?";
            $params[] = $data['category_id'];
        }
        if (isset($data['is_featured'])) {
            $updates[] = "is_featured = ?";
            $params[] = intval($data['is_featured']);
        }
        if (isset($data['is_published'])) {
            $updates[] = "is_published = ?";
            $params[] = intval($data['is_published']);
        }
        
        if (empty($updates)) {
            echo json_encode(['success' => false, 'error' => 'Nenhum campo para atualizar']);
            return;
        }
        
        $params[] = $id;
        $sql = "UPDATE agronews_articles SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function deleteArticle($db) {
    try {
        $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID não fornecido']);
            return;
        }
        
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("DELETE FROM agronews_articles WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function incrementViews($db, $articleId = null) {
    try {
        $id = $articleId ?? intval($_GET['id'] ?? $_POST['id'] ?? 0);
        
        if (!$id) {
            return;
        }
        
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("UPDATE agronews_articles SET views_count = views_count + 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        if (!$articleId) {
            echo json_encode(['success' => true]);
        }
    } catch (Exception $e) {
        if (!$articleId) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

// ==========================================
// FUNÇÕES DE COTAÇÕES
// ==========================================

function getQuotations($db) {
    try {
        $limit = intval($_GET['limit'] ?? 10);
        $productType = $_GET['product_type'] ?? null;
        
        $where = ["quotation_date = CURDATE()"];
        $params = [];
        
        if ($productType) {
            $where[] = "product_type = ?";
            $params[] = $productType;
        }
        
        $whereClause = implode(' AND ', $where);
        $params[] = $limit;
        
        $quotations = $db->query("SELECT * FROM agronews_quotations 
                           WHERE {$whereClause}
                           ORDER BY product_name ASC
                           LIMIT ?", $params);
        
        echo json_encode([
            'success' => true,
            'data' => $quotations
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function createQuotation($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $sql = "INSERT INTO agronews_quotations 
                (product_name, product_type, unit, price, variation, variation_type, market, region, quotation_date, source)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['product_name'] ?? '',
            $data['product_type'] ?? 'outros',
            $data['unit'] ?? 'kg',
            $data['price'] ?? 0,
            $data['variation'] ?? 0,
            $data['variation_type'] ?? 'stable',
            $data['market'] ?? null,
            $data['region'] ?? null,
            $data['quotation_date'] ?? date('Y-m-d'),
            $data['source'] ?? null
        ];
        
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ==========================================
// FUNÇÕES DE CLIMA
// ==========================================

function getWeather($db) {
    try {
        $region = $_GET['region'] ?? 'Brasil';
        $limit = intval($_GET['limit'] ?? 7);
        
        $weather = $db->query("SELECT * FROM agronews_weather 
                           WHERE region = ? AND forecast_date >= CURDATE()
                           ORDER BY forecast_date ASC
                           LIMIT ?", [$region, $limit]);
        
        echo json_encode([
            'success' => true,
            'data' => $weather
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function createWeather($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $sql = "INSERT INTO agronews_weather 
                (region, temperature, min_temperature, max_temperature, humidity, rain_probability, 
                 rain_forecast, wind_speed, condition, forecast_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['region'] ?? 'Brasil',
            $data['temperature'] ?? null,
            $data['min_temperature'] ?? null,
            $data['max_temperature'] ?? null,
            $data['humidity'] ?? null,
            $data['rain_probability'] ?? null,
            $data['rain_forecast'] ?? null,
            $data['wind_speed'] ?? null,
            $data['condition'] ?? null,
            $data['forecast_date'] ?? date('Y-m-d')
        ];
        
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ==========================================
// FUNÇÕES DE CÂMBIO
// ==========================================

function getCurrency($db) {
    try {
        // Por enquanto, retornar dados mockados
        // Em produção, integrar com API de câmbio (ex: API do Banco Central)
        $usd = 5.20; // Valor mockado - substituir por API real
        
        echo json_encode([
            'success' => true,
            'data' => [
                'usd' => $usd,
                'last_update' => date('Y-m-d H:i:s')
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ==========================================
// FUNÇÕES DE NEWSLETTER
// ==========================================

function subscribeNewsletter($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $email = $data['email'] ?? '';
        $name = $data['name'] ?? null;
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Email inválido']);
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
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ==========================================
// BUSCAR CONTEÚDO COMPLETO
// ==========================================

function fetchFullContent($db) {
    try {
        $articleId = intval($_GET['id'] ?? 0);
        
        if (!$articleId) {
            echo json_encode(['success' => false, 'error' => 'ID não fornecido']);
            return;
        }
        
        // Buscar artigo
        $articleResult = $db->query("SELECT id, source_url, content FROM agronews_articles WHERE id = ?", [$articleId]);
        $article = $articleResult[0] ?? null;
        
        if (!$article) {
            echo json_encode(['success' => false, 'error' => 'Artigo não encontrado']);
            return;
        }
        
        // Se já tem conteúdo completo (mais de 1000 caracteres), retornar
        if (strlen($article['content']) > 1000) {
            echo json_encode([
                'success' => true,
                'data' => ['content' => $article['content'], 'already_complete' => true]
            ]);
            return;
        }
        
        // Se não tem source_url, não pode buscar
        if (empty($article['source_url'])) {
            echo json_encode(['success' => false, 'error' => 'URL da fonte não disponível']);
            return;
        }
        
        // Tentar buscar conteúdo da URL (usando cURL)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $article['source_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$html) {
            echo json_encode(['success' => false, 'error' => 'Não foi possível buscar o conteúdo da fonte']);
            return;
        }
        
        // Extrair conteúdo do HTML (tentativa básica)
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);
        
        // Tentar encontrar o conteúdo principal (article, main, .content, etc.)
        $contentNodes = $xpath->query("//article//p | //main//p | //div[contains(@class, 'content')]//p | //div[contains(@class, 'article')]//p");
        
        $fullContent = '';
        if ($contentNodes->length > 0) {
            foreach ($contentNodes as $node) {
                $text = trim($node->textContent);
                if (strlen($text) > 50) { // Ignorar parágrafos muito curtos
                    $fullContent .= $text . "\n\n";
                }
            }
        }
        
        // Se não encontrou, usar todo o texto da página
        if (empty($fullContent)) {
            $fullContent = strip_tags($html);
            $fullContent = preg_replace('/\s+/', ' ', $fullContent);
            $fullContent = trim($fullContent);
        }
        
        // Limitar tamanho (máximo 50KB)
        if (strlen($fullContent) > 50000) {
            $fullContent = substr($fullContent, 0, 50000) . '...';
        }
        
        // Atualizar no banco se encontrou conteúdo
        if (!empty($fullContent) && strlen($fullContent) > strlen($article['content'])) {
            $pdo = $db->getConnection();
            $stmt = $pdo->prepare("UPDATE agronews_articles SET content = ? WHERE id = ?");
            $stmt->execute([$fullContent, $articleId]);
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'content' => $fullContent ?: $article['content'],
                'fetched' => !empty($fullContent)
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ==========================================
// FUNÇÕES AUXILIARES
// ==========================================

function createSlug($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

