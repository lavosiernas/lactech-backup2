<?php
/**
 * API para Gestão de Inseminações - LacTech
 * Sistema completo de inseminações e controle de prenhez
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
            listInseminations($db);
            break;
        case 'get':
            getInsemination($db);
            break;
        case 'create':
            createInsemination($db);
            break;
        case 'update':
            updateInsemination($db);
            break;
        case 'delete':
            deleteInsemination($db);
            break;
        case 'pregnancy_check':
            updatePregnancyCheck($db);
            break;
        case 'recent':
            getRecentInseminations($db);
            break;
        case 'pending_checks':
            getPendingPregnancyChecks($db);
            break;
        case 'statistics':
            getInseminationStatistics($db);
            break;
        case 'calendar':
            getInseminationCalendar($db);
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
 * Listar inseminações com filtros
 */
function listInseminations($db) {
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $search = $_GET['search'] ?? '';
    $bullId = $_GET['bull_id'] ?? '';
    $animalId = $_GET['animal_id'] ?? '';
    $pregnancyResult = $_GET['pregnancy_result'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $sort = $_GET['sort'] ?? 'insemination_date';
    $order = $_GET['order'] ?? 'DESC';
    
    $offset = ($page - 1) * $limit;
    
    // Construir query
    $where = ['i.farm_id = ?'];
    $params = [1];
    
    if ($search) {
        $where[] = "(a.animal_name LIKE ? OR b.bull_name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($bullId) {
        $where[] = "i.bull_id = ?";
        $params[] = $bullId;
    }
    
    if ($animalId) {
        $where[] = "i.animal_id = ?";
        $params[] = $animalId;
    }
    
    if ($pregnancyResult) {
        $where[] = "i.pregnancy_result = ?";
        $params[] = $pregnancyResult;
    }
    
    if ($dateFrom) {
        $where[] = "i.insemination_date >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $where[] = "i.insemination_date <= ?";
        $params[] = $dateTo;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Query principal
    $sql = "
        SELECT 
            i.*,
            a.animal_name,
            a.breed as animal_breed,
            b.bull_name,
            b.breed as bull_breed,
            DATEDIFF(CURDATE(), i.insemination_date) as days_since_insemination,
            CASE 
                WHEN i.pregnancy_result = 'pendente' AND DATEDIFF(CURDATE(), i.insemination_date) >= 21 THEN 'Pronto para teste'
                WHEN i.pregnancy_result = 'pendente' AND DATEDIFF(CURDATE(), i.insemination_date) < 21 THEN 'Aguardando'
                WHEN i.pregnancy_result = 'prenha' THEN 'Confirmada'
                WHEN i.pregnancy_result = 'vazia' THEN 'Vazia'
                ELSE 'Indefinido'
            END as status_description
        FROM inseminations i
        JOIN animals a ON i.animal_id = a.id
        JOIN bulls b ON i.bull_id = b.id
        WHERE $whereClause
        ORDER BY $sort $order
        LIMIT $limit OFFSET $offset
    ";
    
    $inseminations = $db->query($sql, $params);
    
    // Contar total
    $countSql = "
        SELECT COUNT(*) as total
        FROM inseminations i
        JOIN animals a ON i.animal_id = a.id
        JOIN bulls b ON i.bull_id = b.id
        WHERE $whereClause
    ";
    $total = $db->query($countSql, $params)[0]['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'inseminations' => $inseminations,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]
    ]);
}

/**
 * Obter inseminação específica
 */
function getInsemination($db) {
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('ID da inseminação é obrigatório');
    }
    
    $sql = "
        SELECT 
            i.*,
            a.animal_name,
            a.breed as animal_breed,
            a.birth_date as animal_birth_date,
            b.bull_name,
            b.breed as bull_breed,
            b.genetic_merit,
            b.fertility_index,
            DATEDIFF(CURDATE(), i.insemination_date) as days_since_insemination,
            DATEDIFF(i.expected_calving_date, CURDATE()) as days_to_calving
        FROM inseminations i
        JOIN animals a ON i.animal_id = a.id
        JOIN bulls b ON i.bull_id = b.id
        WHERE i.id = ? AND i.farm_id = ?
    ";
    
    $insemination = $db->query($sql, [$id, 1]);
    
    if (empty($insemination)) {
        throw new Exception('Inseminação não encontrada');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $insemination[0]
    ]);
}

/**
 * Criar nova inseminação
 */
function createInsemination($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validações
    $required = ['animal_id', 'bull_id', 'insemination_date'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Campo obrigatório: $field");
        }
    }
    
    // Verificar se animal existe
    $animal = $db->query("SELECT id FROM animals WHERE id = ? AND farm_id = ?", 
        [$data['animal_id'], 1]);
    if (empty($animal)) {
        throw new Exception('Animal não encontrado');
    }
    
    // Verificar se touro existe
    $bull = $db->query("SELECT id FROM bulls WHERE id = ? AND farm_id = ?", 
        [$data['bull_id'], 1]);
    if (empty($bull)) {
        throw new Exception('Touro não encontrado');
    }
    
    // Calcular data esperada de parto (283 dias)
    $expectedCalvingDate = date('Y-m-d', strtotime($data['insemination_date'] . ' + 283 days'));
    
    // Inserir inseminação
    $sql = "
        INSERT INTO inseminations (
            animal_id, bull_id, insemination_date, insemination_time,
            technician_name, technician_license, semen_batch, semen_expiry_date,
            semen_straw_number, insemination_method, pregnancy_check_date,
            pregnancy_result, pregnancy_check_method, expected_calving_date,
            cost, notes, farm_id, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $params = [
        $data['animal_id'],
        $data['bull_id'],
        $data['insemination_date'],
        $data['insemination_time'] ?? null,
        $data['technician_name'] ?? null,
        $data['technician_license'] ?? null,
        $data['semen_batch'] ?? null,
        $data['semen_expiry_date'] ?? null,
        $data['semen_straw_number'] ?? null,
        $data['insemination_method'] ?? 'IA',
        $data['pregnancy_check_date'] ?? null,
        $data['pregnancy_result'] ?? 'pendente',
        $data['pregnancy_check_method'] ?? 'palpacao',
        $expectedCalvingDate,
        $data['cost'] ?? 0,
        $data['notes'] ?? null,
        1,
        $_SESSION['user_id'] ?? 1
    ];
    
    $result = $db->execute($sql, $params);
    
    if ($result) {
        $inseminationId = $db->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Inseminação registrada com sucesso',
            'data' => [
                'id' => $inseminationId,
                'expected_calving_date' => $expectedCalvingDate
            ]
        ]);
    } else {
        throw new Exception('Erro ao registrar inseminação');
    }
}

/**
 * Atualizar inseminação
 */
function updateInsemination($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        throw new Exception('Método não permitido');
    }
    
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        throw new Exception('ID da inseminação é obrigatório');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Verificar se inseminação existe
    $existing = $db->query("SELECT id FROM inseminations WHERE id = ? AND farm_id = ?", [$id, 1]);
    if (empty($existing)) {
        throw new Exception('Inseminação não encontrada');
    }
    
    // Construir query de atualização
    $fields = [];
    $params = [];
    
    $allowedFields = [
        'animal_id', 'bull_id', 'insemination_date', 'insemination_time',
        'technician_name', 'technician_license', 'semen_batch', 'semen_expiry_date',
        'semen_straw_number', 'insemination_method', 'pregnancy_check_date',
        'pregnancy_result', 'pregnancy_check_method', 'expected_calving_date',
        'actual_calving_date', 'calving_result', 'calf_sex', 'calf_weight',
        'complications', 'cost', 'notes'
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
    
    $sql = "UPDATE inseminations SET " . implode(', ', $fields) . " WHERE id = ? AND farm_id = ?";
    $params[] = 1;
    
    $result = $db->execute($sql, $params);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Inseminação atualizada com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao atualizar inseminação');
    }
}

/**
 * Deletar inseminação
 */
function deleteInsemination($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        throw new Exception('Método não permitido');
    }
    
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        throw new Exception('ID da inseminação é obrigatório');
    }
    
    $result = $db->execute("DELETE FROM inseminations WHERE id = ? AND farm_id = ?", [$id, 1]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Inseminação deletada com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao deletar inseminação');
    }
}

/**
 * Atualizar resultado de teste de prenhez
 */
function updatePregnancyCheck($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        throw new Exception('Método não permitido');
    }
    
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        throw new Exception('ID da inseminação é obrigatório');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required = ['pregnancy_result', 'pregnancy_check_date'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Campo obrigatório: $field");
        }
    }
    
    $sql = "
        UPDATE inseminations 
        SET pregnancy_result = ?, 
            pregnancy_check_date = ?, 
            pregnancy_check_method = ?
        WHERE id = ? AND farm_id = ?
    ";
    
    $params = [
        $data['pregnancy_result'],
        $data['pregnancy_check_date'],
        $data['pregnancy_check_method'] ?? 'palpacao',
        $id,
        1
    ];
    
    $result = $db->execute($sql, $params);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Resultado do teste de prenhez atualizado com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao atualizar resultado do teste');
    }
}

/**
 * Obter inseminações recentes
 */
function getRecentInseminations($db) {
    $limit = (int)($_GET['limit'] ?? 10);
    
    $sql = "
        SELECT 
            i.id,
            i.insemination_date,
            i.insemination_time,
            a.animal_name,
            b.bull_name,
            i.pregnancy_result,
            i.expected_calving_date,
            DATEDIFF(CURDATE(), i.insemination_date) as days_since_insemination,
            CASE 
                WHEN i.pregnancy_result = 'pendente' AND DATEDIFF(CURDATE(), i.insemination_date) >= 21 THEN 'Pronto para teste'
                WHEN i.pregnancy_result = 'pendente' AND DATEDIFF(CURDATE(), i.insemination_date) < 21 THEN 'Aguardando'
                WHEN i.pregnancy_result = 'prenha' THEN 'Confirmada'
                WHEN i.pregnancy_result = 'vazia' THEN 'Vazia'
                ELSE 'Indefinido'
            END as status_description
        FROM inseminations i
        JOIN animals a ON i.animal_id = a.id
        JOIN bulls b ON i.bull_id = b.id
        WHERE i.farm_id = 1
        ORDER BY i.insemination_date DESC, i.insemination_time DESC
        LIMIT ?
    ";
    
    $inseminations = $db->query($sql, [$limit]);
    
    echo json_encode([
        'success' => true,
        'data' => $inseminations
    ]);
}

/**
 * Obter inseminações pendentes de teste de prenhez
 */
function getPendingPregnancyChecks($db) {
    $sql = "
        SELECT 
            i.id,
            i.insemination_date,
            a.animal_name,
            b.bull_name,
            DATEDIFF(CURDATE(), i.insemination_date) as days_since_insemination,
            i.expected_calving_date
        FROM inseminations i
        JOIN animals a ON i.animal_id = a.id
        JOIN bulls b ON i.bull_id = b.id
        WHERE i.farm_id = 1 
        AND i.pregnancy_result = 'pendente'
        AND DATEDIFF(CURDATE(), i.insemination_date) >= 21
        ORDER BY i.insemination_date ASC
    ";
    
    $pending = $db->query($sql);
    
    echo json_encode([
        'success' => true,
        'data' => $pending
    ]);
}

/**
 * Obter estatísticas de inseminações
 */
function getInseminationStatistics($db) {
    $period = $_GET['period'] ?? 'year';
    
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
            COUNT(i.id) as total_inseminations,
            SUM(CASE WHEN i.pregnancy_result = 'prenha' THEN 1 ELSE 0 END) as successful_inseminations,
            SUM(CASE WHEN i.pregnancy_result = 'vazia' THEN 1 ELSE 0 END) as failed_inseminations,
            SUM(CASE WHEN i.pregnancy_result = 'pendente' THEN 1 ELSE 0 END) as pending_inseminations,
            ROUND(
                (SUM(CASE WHEN i.pregnancy_result = 'prenha' THEN 1 ELSE 0 END) / COUNT(i.id)) * 100, 2
            ) as pregnancy_rate,
            SUM(i.cost) as total_cost,
            ROUND(AVG(i.cost), 2) as avg_cost_per_insemination,
            COUNT(DISTINCT i.animal_id) as animals_inseminated,
            COUNT(DISTINCT i.bull_id) as bulls_used
        FROM inseminations i
        WHERE i.farm_id = 1 $dateCondition
    ";
    
    $stats = $db->query($sql)[0];
    
    // Estatísticas por touro
    $bullStats = $db->query("
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
        WHERE b.farm_id = 1
        GROUP BY b.id, b.bull_name, b.breed
        HAVING total_inseminations > 0
        ORDER BY pregnancy_rate DESC, total_inseminations DESC
        LIMIT 10
    ");
    
    echo json_encode([
        'success' => true,
        'data' => [
            'statistics' => $stats,
            'bull_statistics' => $bullStats,
            'period' => $period
        ]
    ]);
}

/**
 * Obter calendário de inseminações
 */
function getInseminationCalendar($db) {
    $month = $_GET['month'] ?? date('Y-m');
    $startDate = $month . '-01';
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $sql = "
        SELECT 
            i.id,
            i.insemination_date,
            i.insemination_time,
            a.animal_name,
            b.bull_name,
            i.pregnancy_result,
            i.expected_calving_date,
            CASE 
                WHEN i.pregnancy_result = 'prenha' THEN 'success'
                WHEN i.pregnancy_result = 'vazia' THEN 'danger'
                WHEN i.pregnancy_result = 'pendente' THEN 'warning'
                ELSE 'secondary'
            END as status_class
        FROM inseminations i
        JOIN animals a ON i.animal_id = a.id
        JOIN bulls b ON i.bull_id = b.id
        WHERE i.farm_id = 1 
        AND i.insemination_date BETWEEN ? AND ?
        ORDER BY i.insemination_date, i.insemination_time
    ";
    
    $inseminations = $db->query($sql, [$startDate, $endDate]);
    
    // Agrupar por data
    $calendar = [];
    foreach ($inseminations as $insemination) {
        $date = $insemination['insemination_date'];
        if (!isset($calendar[$date])) {
            $calendar[$date] = [];
        }
        $calendar[$date][] = $insemination;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'calendar' => $calendar,
            'month' => $month
        ]
    ]);
}
?>