<?php
/**
 * API para Gestão de Touros - LacTech
 * Sistema completo de touros e inseminação
 */

// Headers para evitar cache e garantir JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Limpar qualquer output anterior
ob_start();
ob_clean();

// Desabilitar erros para evitar HTML no JSON
ini_set('display_errors', 0);
error_reporting(0);

// Incluir configuração
require_once __DIR__ . '/../includes/config.php';

// Verificar autenticação
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Verificar método
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = new Database();
    
    switch ($action) {
        case 'list':
            listBulls($db);
            break;
        case 'get':
            getBull($db);
            break;
        case 'create':
            createBull($db);
            break;
        case 'update':
            updateBull($db);
            break;
        case 'delete':
            deleteBull($db);
            break;
        case 'statistics':
            getBullStatistics($db);
            break;
        case 'performance':
            getBullPerformance($db);
            break;
        case 'search':
            searchBulls($db);
            break;
        default:
            throw new Exception('Ação não encontrada');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno: ' . $e->getMessage()
    ]);
}

/**
 * Listar touros com filtros
 */
function listBulls($db) {
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $search = $_GET['search'] ?? '';
    $breed = $_GET['breed'] ?? '';
    $status = $_GET['status'] ?? '';
    $sort = $_GET['sort'] ?? 'bull_name';
    $order = $_GET['order'] ?? 'ASC';
    
    $offset = ($page - 1) * $limit;
    
    // Construir query
    $where = ['b.farm_id = ?'];
    $params = [1];
    
    if ($search) {
        $where[] = "(b.bull_name LIKE ? OR b.bull_code LIKE ? OR b.genetic_code LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($breed) {
        $where[] = "b.breed = ?";
        $params[] = $breed;
    }
    
    if ($status) {
        $where[] = "b.status = ?";
        $params[] = $status;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Query principal
    $sql = "
        SELECT 
            b.id,
            b.bull_name,
            b.breed,
            b.birth_date,
            b.status,
            b.genetic_merit,
            b.fertility_index,
            b.photo_url,
            b.purchase_date,
            b.purchase_price,
            b.notes,
            COUNT(i.id) as total_inseminations,
            SUM(CASE WHEN i.pregnancy_result = 'prenha' THEN 1 ELSE 0 END) as successful_inseminations,
            ROUND(
                (SUM(CASE WHEN i.pregnancy_result = 'prenha' THEN 1 ELSE 0 END) / COUNT(i.id)) * 100, 2
            ) as pregnancy_rate,
            MAX(i.insemination_date) as last_insemination
        FROM bulls b
        LEFT JOIN inseminations i ON b.id = i.bull_id
        WHERE $whereClause
        GROUP BY b.id, b.bull_name, b.breed, b.birth_date, b.status, b.genetic_merit, b.fertility_index, b.photo_url, b.purchase_date, b.purchase_price, b.notes
        ORDER BY $sort $order
        LIMIT $limit OFFSET $offset
    ";
    
    $bulls = $db->query($sql, $params);
    
    // Contar total
    $countSql = "
        SELECT COUNT(DISTINCT b.id) as total
        FROM bulls b
        WHERE $whereClause
    ";
    $total = $db->query($countSql, $params)[0]['total'] ?? 0;
    
    // Obter raças disponíveis
    $breeds = $db->query("SELECT DISTINCT breed FROM bulls WHERE farm_id = ? ORDER BY breed", [1]);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'bulls' => $bulls,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ],
            'filters' => [
                'breeds' => array_column($breeds, 'breed'),
                'statuses' => ['ativo', 'inativo', 'vendido', 'morto']
            ]
        ]
    ]);
}

/**
 * Obter touro específico
 */
function getBull($db) {
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('ID do touro é obrigatório');
    }
    
    $sql = "
        SELECT 
            b.id,
            b.bull_name,
            b.breed,
            b.birth_date,
            b.status,
            b.genetic_merit,
            b.fertility_index,
            b.photo_url,
            b.purchase_date,
            b.purchase_price,
            b.notes,
            COUNT(i.id) as total_inseminations,
            SUM(CASE WHEN i.pregnancy_result = 'prenha' THEN 1 ELSE 0 END) as successful_inseminations,
            ROUND(
                (SUM(CASE WHEN i.pregnancy_result = 'prenha' THEN 1 ELSE 0 END) / COUNT(i.id)) * 100, 2
            ) as pregnancy_rate,
            SUM(i.cost) as total_cost,
            MAX(i.insemination_date) as last_insemination,
            MIN(i.insemination_date) as first_insemination
        FROM bulls b
        LEFT JOIN inseminations i ON b.id = i.bull_id
        WHERE b.id = ? AND b.farm_id = ?
        GROUP BY b.id, b.bull_name, b.breed, b.birth_date, b.status, b.genetic_merit, b.fertility_index, b.photo_url, b.purchase_date, b.purchase_price, b.notes
    ";
    
    $bull = $db->query($sql, [$id, 1]);
    
    if (empty($bull)) {
        throw new Exception('Touro não encontrado');
    }
    
    // Obter inseminações recentes
    $recentInseminations = $db->query("
        SELECT 
            i.*,
            a.animal_name,
            DATEDIFF(CURDATE(), i.insemination_date) as days_since_insemination
        FROM inseminations i
        JOIN animals a ON i.animal_id = a.id
        WHERE i.bull_id = ?
        ORDER BY i.insemination_date DESC
        LIMIT 10
    ", [$id]);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'bull' => $bull[0],
            'recent_inseminations' => $recentInseminations
        ]
    ]);
}

/**
 * Criar novo touro
 */
function createBull($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validações
    $required = ['bull_code', 'bull_name', 'breed', 'birth_date'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Campo obrigatório: $field");
        }
    }
    
    // Verificar se código já existe
    $existing = $db->query("SELECT id FROM bulls WHERE bull_code = ? AND farm_id = ?", 
        [$data['bull_code'], 1]);
    if (!empty($existing)) {
        throw new Exception('Código do touro já existe');
    }
    
    // Inserir touro
    $sql = "
                    INSERT INTO bulls (
            bull_code, bull_name, breed, birth_date, genetic_code,
            sire, dam, genetic_merit, milk_production_index, fat_production_index,
            protein_production_index, fertility_index, health_index,
            photo_url, status, purchase_date, purchase_price, notes, farm_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $params = [
        $data['bull_code'],
        $data['bull_name'],
        $data['breed'],
        $data['birth_date'],
        $data['genetic_code'] ?? null,
        $data['sire'] ?? null,
        $data['dam'] ?? null,
        $data['genetic_merit'] ?? null,
        $data['milk_production_index'] ?? null,
        $data['fat_production_index'] ?? null,
        $data['protein_production_index'] ?? null,
        $data['fertility_index'] ?? null,
        $data['health_index'] ?? null,
        $data['photo_url'] ?? null,
        $data['status'] ?? 'ativo',
        $data['purchase_date'] ?? null,
        $data['purchase_price'] ?? null,
        $data['notes'] ?? null,
        1
    ];
    
    $result = $db->execute($sql, $params);
    
    if ($result) {
        $bullId = $db->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Touro criado com sucesso',
            'data' => ['id' => $bullId]
        ]);
    } else {
        throw new Exception('Erro ao criar touro');
    }
}

/**
 * Atualizar touro
 */
function updateBull($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        throw new Exception('Método não permitido');
    }
    
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        throw new Exception('ID do touro é obrigatório');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Verificar se touro existe
    $existing = $db->query("SELECT id FROM bulls WHERE id = ? AND farm_id = ?", [$id, 1]);
    if (empty($existing)) {
        throw new Exception('Touro não encontrado');
    }
    
    // Verificar se código já existe (se mudou)
    if (isset($data['bull_code'])) {
        $codeExists = $db->query("SELECT id FROM bulls WHERE bull_code = ? AND id != ? AND farm_id = ?", 
            [$data['bull_code'], $id, 1]);
        if (!empty($codeExists)) {
            throw new Exception('Código do touro já existe');
        }
    }
    
    // Construir query de atualização
    $fields = [];
    $params = [];
    
    $allowedFields = [
        'bull_code', 'bull_name', 'breed', 'birth_date', 'genetic_code',
        'sire', 'dam', 'genetic_merit', 'milk_production_index', 'fat_production_index',
        'protein_production_index', 'fertility_index', 'health_index',
        'photo_url', 'status', 'purchase_date', 'purchase_price', 
        'sale_date', 'sale_price', 'notes'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        throw new Exception('Nenhum campo para atualizar');
    }
    
    $params[] = $id;
    
    $sql = "UPDATE bulls SET " . implode(', ', $fields) . " WHERE id = ? AND farm_id = ?";
    $params[] = 1;
    
    $result = $db->execute($sql, $params);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Touro atualizado com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao atualizar touro');
    }
}

/**
 * Deletar touro
 */
function deleteBull($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        throw new Exception('Método não permitido');
    }
    
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        throw new Exception('ID do touro é obrigatório');
    }
    
    // Verificar se tem inseminações
    $inseminations = $db->query("SELECT COUNT(*) as count FROM inseminations WHERE bull_id = ?", [$id]);
    if ($inseminations[0]['count'] > 0) {
        throw new Exception('Não é possível deletar touro com inseminações registradas');
    }
    
    $result = $db->execute("DELETE FROM bulls WHERE id = ? AND farm_id = ?", [$id, 1]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Touro deletado com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao deletar touro');
    }
}

/**
 * Obter estatísticas dos touros
 */
function getBullStatistics($db) {
    $period = $_GET['period'] ?? 'year'; // year, month, quarter
    
    $dateCondition = '';
    switch ($period) {
        case 'month':
            $dateCondition = "AND i.insemination_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            break;
        case 'quarter':
            $dateCondition = "AND i.insemination_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
            break;
        case 'year':
        default:
            $dateCondition = "AND i.insemination_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            break;
    }
    
    $sql = "
        SELECT 
            COUNT(DISTINCT b.id) as total_bulls,
            COUNT(DISTINCT CASE WHEN b.status = 'ativo' THEN b.id END) as active_bulls,
            COUNT(i.id) as total_inseminations,
            SUM(CASE WHEN i.pregnancy_result = 'prenha' THEN 1 ELSE 0 END) as successful_inseminations,
            ROUND(
                (SUM(CASE WHEN i.pregnancy_result = 'prenha' THEN 1 ELSE 0 END) / COUNT(i.id)) * 100, 2
            ) as overall_pregnancy_rate,
            SUM(i.cost) as total_cost,
            ROUND(AVG(i.cost), 2) as avg_cost_per_insemination,
            COUNT(DISTINCT i.animal_id) as animals_inseminated
        FROM bulls b
        LEFT JOIN inseminations i ON b.id = i.bull_id $dateCondition
        WHERE b.farm_id = 1
    ";
    
    $stats = $db->query($sql)[0];
    
    // Top 5 touros por performance
    $topBulls = $db->query("
        SELECT 
            b.bull_name,
            b.breed,
            COUNT(i.id) as total_inseminations,
            SUM(CASE WHEN i.pregnancy_result = 'prenha' THEN 1 ELSE 0 END) as successful_inseminations,
            ROUND(
                (SUM(CASE WHEN i.pregnancy_result = 'prenha' THEN 1 ELSE 0 END) / COUNT(i.id)) * 100, 2
            ) as pregnancy_rate
        FROM bulls b
        LEFT JOIN inseminations i ON b.id = i.bull_id $dateCondition
        WHERE b.farm_id = 1 AND b.status = 'ativo'
        GROUP BY b.id, b.bull_name, b.breed
        HAVING total_inseminations > 0
        ORDER BY pregnancy_rate DESC, total_inseminations DESC
        LIMIT 5
    ");
    
    echo json_encode([
        'success' => true,
        'data' => [
            'statistics' => $stats,
            'top_bulls' => $topBulls,
            'period' => $period
        ]
    ]);
}

/**
 * Obter performance de um touro específico
 */
function getBullPerformance($db) {
    $bullId = (int)($_GET['bull_id'] ?? 0);
    $period = $_GET['period'] ?? 'year';
    
    if (!$bullId) {
        throw new Exception('ID do touro é obrigatório');
    }
    
    $dateCondition = '';
    switch ($period) {
        case 'month':
            $dateCondition = "AND i.insemination_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            break;
        case 'quarter':
            $dateCondition = "AND i.insemination_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
            break;
        case 'year':
        default:
            $dateCondition = "AND i.insemination_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            break;
    }
    
    $sql = "
        SELECT 
            b.bull_name,
            b.breed,
            COUNT(i.id) as total_inseminations,
            SUM(CASE WHEN i.pregnancy_result = 'prenha' THEN 1 ELSE 0 END) as successful_inseminations,
            ROUND(
                (SUM(CASE WHEN i.pregnancy_result = 'prenha' THEN 1 ELSE 0 END) / COUNT(i.id)) * 100, 2
            ) as pregnancy_rate,
            SUM(i.cost) as total_cost,
            ROUND(SUM(i.cost) / NULLIF(SUM(CASE WHEN i.pregnancy_result = 'prenha' THEN 1 ELSE 0 END), 0), 2) as cost_per_pregnancy,
            MAX(i.insemination_date) as last_insemination,
            MIN(i.insemination_date) as first_insemination
        FROM bulls b
        LEFT JOIN inseminations i ON b.id = i.bull_id $dateCondition
        WHERE b.id = ? AND b.farm_id = 1
        GROUP BY b.id, b.bull_name, b.breed
    ";
    
    $performance = $db->query($sql, [$bullId]);
    
    if (empty($performance)) {
        throw new Exception('Touro não encontrado');
    }
    
    // Histórico mensal
    $monthlyHistory = $db->query("
        SELECT 
            DATE_FORMAT(i.insemination_date, '%Y-%m') as month,
            COUNT(i.id) as inseminations,
            SUM(CASE WHEN i.pregnancy_result = 'prenha' THEN 1 ELSE 0 END) as successful,
            ROUND(
                (SUM(CASE WHEN i.pregnancy_result = 'prenha' THEN 1 ELSE 0 END) / COUNT(i.id)) * 100, 2
            ) as pregnancy_rate
        FROM inseminations i
        WHERE i.bull_id = ? $dateCondition
        GROUP BY DATE_FORMAT(i.insemination_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ", [$bullId]);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'performance' => $performance[0],
            'monthly_history' => $monthlyHistory,
            'period' => $period
        ]
    ]);
}

/**
 * Buscar touros
 */
function searchBulls($db) {
    $query = $_GET['q'] ?? '';
    $limit = (int)($_GET['limit'] ?? 10);
    
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }
    
    $sql = "
        SELECT 
            id, bull_name, breed, status,
            genetic_merit, fertility_index
        FROM bulls 
        WHERE farm_id = 1 
        AND (bull_name LIKE ? OR breed LIKE ?)
        AND status = 'ativo'
        ORDER BY 
            CASE 
                WHEN bull_name LIKE ? THEN 1
                ELSE 2
            END,
            bull_name
        LIMIT ?
    ";
    
    $searchTerm = "%$query%";
    $exactTerm = "$query%";
    
    $results = $db->query($sql, [
        $searchTerm, $searchTerm,
        $exactTerm, $limit
    ]);
    
    echo json_encode([
        'success' => true,
        'data' => $results
    ]);
}
?>