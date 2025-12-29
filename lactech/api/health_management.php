<?php
/**
 * API: Gestão Sanitária
 * Endpoint para gerenciar registros de saúde, vacinações, medicamentos, etc.
 */

require_once __DIR__ . '/../includes/config_login.php';
require_once __DIR__ . '/../includes/Database.class.php';

session_start();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$farm_id = $_SESSION['farm_id'] ?? 1;

function sendResponse($data = null, $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    
    ob_clean();
    echo json_encode([
        'success' => $statusCode < 400,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // Obter dados de entrada
    $input = [];
    if ($method === 'POST' || $method === 'PUT') {
        if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $input = $_POST;
        }
    } else {
        $input = $_GET;
    }
    
    switch ($action) {
        // ==========================================
        // DASHBOARD
        // ==========================================
        case 'dashboard':
            // Estatísticas gerais
            $stats = [];
            
            // Total de registros sanitários
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM health_records WHERE farm_id = ?");
            $stmt->execute([$farm_id]);
            $stats['total_records'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Registros por tipo
            $stmt = $conn->prepare("
                SELECT record_type, COUNT(*) as count 
                FROM health_records 
                WHERE farm_id = ? 
                GROUP BY record_type
            ");
            $stmt->execute([$farm_id]);
            $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Vacinações pendentes (próximas 30 dias)
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM health_records 
                WHERE farm_id = ? 
                AND record_type = 'Vacinação' 
                AND next_date IS NOT NULL 
                AND next_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$farm_id]);
            $stats['pending_vaccinations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Vermifugações pendentes
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM health_records 
                WHERE farm_id = ? 
                AND record_type = 'Vermifugação' 
                AND next_date IS NOT NULL 
                AND next_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$farm_id]);
            $stats['pending_dewormings'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Animais doentes (com registros recentes de medicamento)
            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT animal_id) as count 
                FROM health_records 
                WHERE farm_id = ? 
                AND record_type = 'Medicamento' 
                AND record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$farm_id]);
            $stats['sick_animals'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Total de alertas não resolvidos
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM health_alerts 
                WHERE farm_id = ? 
                AND is_resolved = 0
            ");
            $stmt->execute([$farm_id]);
            $stats['active_alerts'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Custo total dos últimos 30 dias
            $stmt = $conn->prepare("
                SELECT COALESCE(SUM(cost), 0) as total 
                FROM health_records 
                WHERE farm_id = ? 
                AND record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$farm_id]);
            $stats['total_cost_30days'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            sendResponse($stats);
            break;
            
        // ==========================================
        // LISTAR REGISTROS
        // ==========================================
        case 'list':
            $record_type = $input['record_type'] ?? null;
            $animal_id = $input['animal_id'] ?? null;
            $date_from = $input['date_from'] ?? null;
            $date_to = $input['date_to'] ?? null;
            $limit = (int)($input['limit'] ?? 100);
            $offset = (int)($input['offset'] ?? 0);
            
            $where = ["hr.farm_id = ?"];
            $params = [$farm_id];
            
            if ($record_type) {
                $where[] = "hr.record_type = ?";
                $params[] = $record_type;
            }
            
            if ($animal_id) {
                $where[] = "hr.animal_id = ?";
                $params[] = (int)$animal_id;
            }
            
            if ($date_from) {
                $where[] = "hr.record_date >= ?";
                $params[] = $date_from;
            }
            
            if ($date_to) {
                $where[] = "hr.record_date <= ?";
                $params[] = $date_to;
            }
            
            $whereClause = implode(' AND ', $where);
            
            $stmt = $conn->prepare("
                SELECT 
                    hr.*,
                    a.animal_number,
                    a.name as animal_name,
                    u.name as recorded_by_name
                FROM health_records hr
                LEFT JOIN animals a ON hr.animal_id = a.id
                LEFT JOIN users u ON hr.recorded_by = u.id
                WHERE $whereClause
                ORDER BY hr.record_date DESC, hr.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM health_records hr
                WHERE $whereClause
            ");
            $stmt->execute(array_slice($params, 0, -2));
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            sendResponse([
                'records' => $records,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]);
            break;
            
        // ==========================================
        // OBTER REGISTRO
        // ==========================================
        case 'get':
            $id = $input['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido', 400);
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    hr.*,
                    a.animal_number,
                    a.name as animal_name,
                    u.name as recorded_by_name
                FROM health_records hr
                LEFT JOIN animals a ON hr.animal_id = a.id
                LEFT JOIN users u ON hr.recorded_by = u.id
                WHERE hr.id = ? AND hr.farm_id = ?
            ");
            $stmt->execute([$id, $farm_id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                sendResponse(null, 'Registro não encontrado', 404);
            }
            
            sendResponse($record);
            break;
            
        // ==========================================
        // CRIAR REGISTRO
        // ==========================================
        case 'create':
            if ($method !== 'POST') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            $required = ['animal_id', 'record_date', 'record_type', 'description'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    sendResponse(null, "Campo obrigatório: $field", 400);
                }
            }
            
            $stmt = $conn->prepare("
                INSERT INTO health_records (
                    animal_id, record_date, record_type, description, medication, dosage,
                    cost, next_date, veterinarian, recorded_by, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                (int)$input['animal_id'],
                $input['record_date'],
                $input['record_type'],
                sanitizeInput($input['description']),
                sanitizeInput($input['medication'] ?? null),
                sanitizeInput($input['dosage'] ?? null),
                !empty($input['cost']) ? (float)$input['cost'] : null,
                $input['next_date'] ?? null,
                sanitizeInput($input['veterinarian'] ?? null),
                $user_id,
                $farm_id
            ]);
            
            $id = $conn->lastInsertId();
            
            // Atualizar health_status do animal se necessário
            if (isset($input['update_health_status'])) {
                $newStatus = $input['update_health_status'];
                $stmt = $conn->prepare("UPDATE animals SET health_status = ? WHERE id = ? AND farm_id = ?");
                $stmt->execute([$newStatus, $input['animal_id'], $farm_id]);
            }
            
            sendResponse(['id' => $id, 'message' => 'Registro criado com sucesso']);
            break;
            
        // ==========================================
        // ATUALIZAR REGISTRO
        // ==========================================
        case 'update':
            if ($method !== 'POST' && $method !== 'PUT') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            $id = $input['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido', 400);
            }
            
            // Verificar se existe
            $stmt = $conn->prepare("SELECT id FROM health_records WHERE id = ? AND farm_id = ?");
            $stmt->execute([$id, $farm_id]);
            if (!$stmt->fetch()) {
                sendResponse(null, 'Registro não encontrado', 404);
            }
            
            $fields = [];
            $params = [];
            
            $allowedFields = ['animal_id', 'record_date', 'record_type', 'description', 'medication', 'dosage', 'cost', 'next_date', 'veterinarian'];
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $fields[] = "$field = ?";
                    if ($field === 'animal_id' || $field === 'cost') {
                        $params[] = $field === 'animal_id' ? (int)$input[$field] : (float)$input[$field];
                    } else {
                        $params[] = $field === 'description' || $field === 'medication' || $field === 'dosage' || $field === 'veterinarian' 
                            ? sanitizeInput($input[$field]) 
                            : $input[$field];
                    }
                }
            }
            
            if (empty($fields)) {
                sendResponse(null, 'Nenhum campo para atualizar', 400);
            }
            
            $params[] = $id;
            $params[] = $farm_id;
            
            $sql = "UPDATE health_records SET " . implode(', ', $fields) . " WHERE id = ? AND farm_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            sendResponse(['id' => $id, 'message' => 'Registro atualizado com sucesso']);
            break;
            
        // ==========================================
        // DELETAR REGISTRO
        // ==========================================
        case 'delete':
            if ($method !== 'POST' && $method !== 'DELETE') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            $id = $input['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido', 400);
            }
            
            $stmt = $conn->prepare("DELETE FROM health_records WHERE id = ? AND farm_id = ?");
            $stmt->execute([$id, $farm_id]);
            
            if ($stmt->rowCount() === 0) {
                sendResponse(null, 'Registro não encontrado', 404);
            }
            
            sendResponse(['id' => $id, 'message' => 'Registro deletado com sucesso']);
            break;
            
        // ==========================================
        // VACINAÇÕES PENDENTES
        // ==========================================
        case 'pending_vaccinations':
            $days = (int)($input['days'] ?? 30);
            
            $stmt = $conn->prepare("
                SELECT 
                    hr.*,
                    a.animal_number,
                    a.name as animal_name,
                    DATEDIFF(hr.next_date, CURDATE()) as days_until
                FROM health_records hr
                LEFT JOIN animals a ON hr.animal_id = a.id
                WHERE hr.farm_id = ? 
                AND hr.record_type = 'Vacinação' 
                AND hr.next_date IS NOT NULL 
                AND hr.next_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY hr.next_date ASC
            ");
            $stmt->execute([$farm_id, $days]);
            $vaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($vaccinations);
            break;
            
        // ==========================================
        // VERMIFUGAÇÕES PENDENTES
        // ==========================================
        case 'pending_dewormings':
            $days = (int)($input['days'] ?? 30);
            
            $stmt = $conn->prepare("
                SELECT 
                    hr.*,
                    a.animal_number,
                    a.name as animal_name,
                    DATEDIFF(hr.next_date, CURDATE()) as days_until
                FROM health_records hr
                LEFT JOIN animals a ON hr.animal_id = a.id
                WHERE hr.farm_id = ? 
                AND hr.record_type = 'Vermifugação' 
                AND hr.next_date IS NOT NULL 
                AND hr.next_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY hr.next_date ASC
            ");
            $stmt->execute([$farm_id, $days]);
            $dewormings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($dewormings);
            break;
            
        // ==========================================
        // ALERTAS SANITÁRIOS
        // ==========================================
        case 'alerts':
            $resolved = $input['resolved'] ?? null;
            
            $where = ["ha.farm_id = ?"];
            $params = [$farm_id];
            
            if ($resolved !== null) {
                $where[] = "ha.is_resolved = ?";
                $params[] = (int)$resolved;
            }
            
            $whereClause = implode(' AND ', $where);
            
            $stmt = $conn->prepare("
                SELECT 
                    ha.*,
                    a.animal_number,
                    a.name as animal_name,
                    u.name as created_by_name,
                    ur.name as resolved_by_name
                FROM health_alerts ha
                LEFT JOIN animals a ON ha.animal_id = a.id
                LEFT JOIN users u ON ha.created_by = u.id
                LEFT JOIN users ur ON ha.resolved_by = ur.id
                WHERE $whereClause
                ORDER BY ha.alert_date ASC, ha.created_at DESC
            ");
            $stmt->execute($params);
            $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($alerts);
            break;
            
        // ==========================================
        // RESOLVER ALERTA
        // ==========================================
        case 'resolve_alert':
            if ($method !== 'POST') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            $id = $input['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido', 400);
            }
            
            $stmt = $conn->prepare("
                UPDATE health_alerts 
                SET is_resolved = 1, 
                    resolved_date = CURDATE(), 
                    resolved_by = ?
                WHERE id = ? AND farm_id = ?
            ");
            $stmt->execute([$user_id, $id, $farm_id]);
            
            if ($stmt->rowCount() === 0) {
                sendResponse(null, 'Alerta não encontrado', 404);
            }
            
            sendResponse(['id' => $id, 'message' => 'Alerta resolvido com sucesso']);
            break;
            
        // ==========================================
        // LISTAR ANIMAIS
        // ==========================================
        case 'animals':
            $stmt = $conn->prepare("
                SELECT id, animal_number, name, status, health_status
                FROM animals
                WHERE farm_id = ? AND (is_active = 1 OR is_active IS NULL)
                ORDER BY animal_number ASC, name ASC
            ");
            $stmt->execute([$farm_id]);
            $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($animals);
            break;
            
        // ==========================================
        // ESTATÍSTICAS POR PERÍODO
        // ==========================================
        case 'stats':
            $date_from = $input['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
            $date_to = $input['date_to'] ?? date('Y-m-d');
            
            $stmt = $conn->prepare("
                SELECT 
                    record_type,
                    COUNT(*) as count,
                    COUNT(DISTINCT animal_id) as animals_count,
                    COALESCE(SUM(cost), 0) as total_cost,
                    COALESCE(AVG(cost), 0) as avg_cost
                FROM health_records
                WHERE farm_id = ? 
                AND record_date BETWEEN ? AND ?
                GROUP BY record_type
                ORDER BY count DESC
            ");
            $stmt->execute([$farm_id, $date_from, $date_to]);
            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($stats);
            break;
            
        default:
            sendResponse(null, 'Ação não reconhecida', 400);
            break;
    }
    
} catch (Exception $e) {
    error_log("Health Management API Error: " . $e->getMessage());
    sendResponse(null, 'Erro interno do servidor: ' . $e->getMessage(), 500);
}



