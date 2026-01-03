<?php
/**
 * API de Alimentação - Lactech
 * Sistema de controle de alimentação completo conectado ao banco de dados
 */

// Iniciar output buffering
ob_start();

// Configurações de segurança
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Acesso negado'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Carregar Database
$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Database class não encontrada'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $dbPath;
require_once __DIR__ . '/../includes/config_mysql.php';

function sendResponse($data = null, $error = null) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    $response = ['success' => $error === null];
    if ($data !== null) {
        $response['data'] = $data;
    }
    if ($error !== null) {
        $response['error'] = $error;
    }
    
    header_remove();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];
    $farm_id = $_SESSION['farm_id'] ?? 1;
    
    // Ler dados JSON se for POST/PUT
    $input = [];
    if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $input = json_decode($rawInput, true) ?? [];
        }
        // Mesclar com $_POST
        $input = array_merge($_POST, $input);
    }
    
    switch ($action) {
        case 'list':
            // Listar registros de alimentação
            $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
            $date_to = $_GET['date_to'] ?? date('Y-m-d');
            $animal_id = $_GET['animal_id'] ?? null;
            
            $sql = "
                SELECT 
                    fr.*,
                    a.animal_number,
                    a.name as animal_name,
                    g.group_name,
                    u.name as recorded_by_name
                FROM feed_records fr
                LEFT JOIN animals a ON fr.animal_id = a.id
                LEFT JOIN animal_groups g ON fr.group_id = g.id
                LEFT JOIN users u ON fr.recorded_by = u.id
                WHERE fr.farm_id = ? 
                AND fr.feed_date BETWEEN ? AND ?
            ";
            $params = [$farm_id, $date_from, $date_to];
            
            if ($animal_id) {
                $sql .= " AND fr.animal_id = ?";
                $params[] = $animal_id;
            }
            
            $sql .= " ORDER BY fr.feed_date DESC, fr.shift, COALESCE(g.group_name, a.animal_number)";
            
            $results = $db->query($sql, $params);
            sendResponse($results ?? []);
            break;
            
        case 'get':
            // Obter um registro específico
            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido');
            }
            
            $results = $db->query("
                SELECT 
                    fr.*,
                    a.animal_number,
                    a.name as animal_name,
                    g.group_name,
                    u.name as recorded_by_name
                FROM feed_records fr
                LEFT JOIN animals a ON fr.animal_id = a.id
                LEFT JOIN animal_groups g ON fr.group_id = g.id
                LEFT JOIN users u ON fr.recorded_by = u.id
                WHERE fr.id = ? AND fr.farm_id = ?
            ", [$id, $farm_id]);
            
            if (empty($results)) {
                sendResponse(null, 'Registro não encontrado');
            }
            
            sendResponse($results[0] ?? null);
            break;
            
        case 'create':
            // Criar novo registro (suporta individual ou lote)
            $record_type = $input['record_type'] ?? 'individual';
            $animal_id = $input['animal_id'] ?? null;
            $group_id = $input['group_id'] ?? null;
            $feed_date = $input['feed_date'] ?? date('Y-m-d');
            $shift = $input['shift'] ?? 'unico';
            $concentrate_kg = floatval($input['concentrate_kg'] ?? 0);
            $roughage_kg = floatval($input['roughage_kg'] ?? 0);
            $silage_kg = floatval($input['silage_kg'] ?? 0);
            $hay_kg = floatval($input['hay_kg'] ?? 0);
            $feed_type = $input['feed_type'] ?? null;
            $feed_brand = $input['feed_brand'] ?? null;
            $protein_percentage = $input['protein_percentage'] ? floatval($input['protein_percentage']) : null;
            $cost_per_kg = $input['cost_per_kg'] ? floatval($input['cost_per_kg']) : null;
            $notes = $input['notes'] ?? null;
            $automatic = intval($input['automatic'] ?? 0);
            $animal_count = isset($input['animal_count']) ? intval($input['animal_count']) : null;
            
            // Validação baseada no tipo de registro
            if ($record_type === 'group') {
                if (!$group_id) {
                    sendResponse(null, 'Lote é obrigatório para registro por lote');
                }
            } else {
            if (!$animal_id) {
                    sendResponse(null, 'Animal é obrigatório para registro individual');
                }
            }
            
            // Calcular custo total
            $total_cost = null;
            if ($cost_per_kg !== null) {
                $total_kg = $concentrate_kg + $roughage_kg + $silage_kg + $hay_kg;
                $total_cost = $total_kg * $cost_per_kg;
            }
            
            $sql = "
                INSERT INTO feed_records (
                    animal_id, group_id, record_type, animal_count, feed_date, shift, concentrate_kg, roughage_kg, 
                    silage_kg, hay_kg, feed_type, feed_brand, protein_percentage,
                    cost_per_kg, total_cost, automatic, notes, recorded_by, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $params = [
                $animal_id, $group_id, $record_type, $animal_count, $feed_date, $shift, $concentrate_kg, $roughage_kg,
                $silage_kg, $hay_kg, $feed_type, $feed_brand, $protein_percentage,
                $cost_per_kg, $total_cost, $automatic, $notes, $user_id, $farm_id
            ];
            
            $pdo = $db->getConnection();
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                $newId = $pdo->lastInsertId();
                // Buscar o registro criado
                $results = $db->query("
                    SELECT 
                        fr.*,
                        a.animal_number,
                        a.name as animal_name,
                        g.group_name,
                        u.name as recorded_by_name
                    FROM feed_records fr
                    LEFT JOIN animals a ON fr.animal_id = a.id
                    LEFT JOIN animal_groups g ON fr.group_id = g.id
                    LEFT JOIN users u ON fr.recorded_by = u.id
                    WHERE fr.id = ?
                ", [$newId]);
                
                sendResponse($results[0] ?? ['id' => $newId]);
            } else {
                sendResponse(null, 'Erro ao criar registro');
            }
            break;
            
        case 'update':
            // Atualizar registro
            $id = $input['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido');
            }
            
            // Verificar se o registro existe e pertence à fazenda
            $check = $db->query("SELECT id FROM feed_records WHERE id = ? AND farm_id = ?", [$id, $farm_id]);
            if (empty($check)) {
                sendResponse(null, 'Registro não encontrado');
            }
            
            $record_type = $input['record_type'] ?? null;
            $animal_id = $input['animal_id'] ?? null;
            $group_id = $input['group_id'] ?? null;
            $animal_count = isset($input['animal_count']) ? intval($input['animal_count']) : null;
            $feed_date = $input['feed_date'] ?? null;
            $shift = $input['shift'] ?? null;
            $concentrate_kg = isset($input['concentrate_kg']) ? floatval($input['concentrate_kg']) : null;
            $roughage_kg = isset($input['roughage_kg']) ? floatval($input['roughage_kg']) : null;
            $silage_kg = isset($input['silage_kg']) ? floatval($input['silage_kg']) : null;
            $hay_kg = isset($input['hay_kg']) ? floatval($input['hay_kg']) : null;
            $feed_type = $input['feed_type'] ?? null;
            $feed_brand = $input['feed_brand'] ?? null;
            $protein_percentage = isset($input['protein_percentage']) ? floatval($input['protein_percentage']) : null;
            $cost_per_kg = isset($input['cost_per_kg']) ? floatval($input['cost_per_kg']) : null;
            $notes = $input['notes'] ?? null;
            $automatic = isset($input['automatic']) ? intval($input['automatic']) : null;
            
            // Construir query dinamicamente
            $updates = [];
            $params = [];
            
            if ($record_type !== null) {
                $updates[] = "record_type = ?";
                $params[] = $record_type;
            }
            if ($animal_id !== null) {
                $updates[] = "animal_id = ?";
                $params[] = $animal_id;
            }
            if ($group_id !== null) {
                $updates[] = "group_id = ?";
                $params[] = $group_id;
            }
            if ($animal_count !== null) {
                $updates[] = "animal_count = ?";
                $params[] = $animal_count;
            }
            if ($feed_date !== null) {
                $updates[] = "feed_date = ?";
                $params[] = $feed_date;
            }
            if ($shift !== null) {
                $updates[] = "shift = ?";
                $params[] = $shift;
            }
            if ($concentrate_kg !== null) {
                $updates[] = "concentrate_kg = ?";
                $params[] = $concentrate_kg;
            }
            if ($roughage_kg !== null) {
                $updates[] = "roughage_kg = ?";
                $params[] = $roughage_kg;
            }
            if ($silage_kg !== null) {
                $updates[] = "silage_kg = ?";
                $params[] = $silage_kg;
            }
            if ($hay_kg !== null) {
                $updates[] = "hay_kg = ?";
                $params[] = $hay_kg;
            }
            if ($feed_type !== null) {
                $updates[] = "feed_type = ?";
                $params[] = $feed_type;
            }
            if ($feed_brand !== null) {
                $updates[] = "feed_brand = ?";
                $params[] = $feed_brand;
            }
            if ($protein_percentage !== null) {
                $updates[] = "protein_percentage = ?";
                $params[] = $protein_percentage;
            }
            if ($cost_per_kg !== null) {
                $updates[] = "cost_per_kg = ?";
                $params[] = $cost_per_kg;
            }
            if ($notes !== null) {
                $updates[] = "notes = ?";
                $params[] = $notes;
            }
            if ($automatic !== null) {
                $updates[] = "automatic = ?";
                $params[] = $automatic;
            }
            
            if (empty($updates)) {
                sendResponse(null, 'Nenhum campo para atualizar');
            }
            
            // Recalcular custo total se necessário
            if ($cost_per_kg !== null) {
                // Buscar valores atuais
                $current = $db->query("SELECT concentrate_kg, roughage_kg, silage_kg, hay_kg FROM feed_records WHERE id = ?", [$id]);
                if (!empty($current)) {
                    $c = $current[0];
                    $total_kg = ($concentrate_kg ?? $c['concentrate_kg']) + 
                                ($roughage_kg ?? $c['roughage_kg']) + 
                                ($silage_kg ?? $c['silage_kg']) + 
                                ($hay_kg ?? $c['hay_kg']);
                    $updates[] = "total_cost = ?";
                    $params[] = $total_kg * $cost_per_kg;
                }
            }
            
            $params[] = $id;
            $params[] = $farm_id;
            
            $sql = "UPDATE feed_records SET " . implode(', ', $updates) . " WHERE id = ? AND farm_id = ?";
            $pdo = $db->getConnection();
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                // Buscar registro atualizado
                $results = $db->query("
                    SELECT 
                        fr.*,
                        a.animal_number,
                        a.name as animal_name,
                        g.group_name,
                        u.name as recorded_by_name
                    FROM feed_records fr
                    LEFT JOIN animals a ON fr.animal_id = a.id
                    LEFT JOIN animal_groups g ON fr.group_id = g.id
                    LEFT JOIN users u ON fr.recorded_by = u.id
                    WHERE fr.id = ?
                ", [$id]);
                
                sendResponse($results[0] ?? null);
            } else {
                sendResponse(null, 'Erro ao atualizar registro');
            }
            break;
            
        case 'delete':
            // Deletar registro
            $id = $input['id'] ?? $_GET['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido');
            }
            
            $pdo = $db->getConnection();
            $stmt = $pdo->prepare("DELETE FROM feed_records WHERE id = ? AND farm_id = ?");
            $result = $stmt->execute([$id, $farm_id]);
            
            if ($result) {
                sendResponse(['id' => $id, 'deleted' => true]);
            } else {
                sendResponse(null, 'Erro ao deletar registro');
            }
            break;
            
        case 'daily_summary':
            // Resumo diário
            $date = $_GET['date'] ?? date('Y-m-d');
            
            $results = $db->query("
                SELECT 
                    COUNT(DISTINCT animal_id) as total_animals_fed,
                    SUM(concentrate_kg) as total_concentrate,
                    SUM(roughage_kg) as total_roughage,
                    SUM(silage_kg) as total_silage,
                    SUM(hay_kg) as total_hay,
                    SUM(total_cost) as total_cost,
                    AVG(concentrate_kg) as avg_concentrate_per_animal
                FROM feed_records
                WHERE feed_date = ? AND farm_id = ?
            ", [$date, $farm_id]);
            
            $summary = $results[0] ?? [];
            $summary['date'] = $date;
            $summary['total_concentrate'] = floatval($summary['total_concentrate'] ?? 0);
            $summary['total_roughage'] = floatval($summary['total_roughage'] ?? 0);
            $summary['total_silage'] = floatval($summary['total_silage'] ?? 0);
            $summary['total_hay'] = floatval($summary['total_hay'] ?? 0);
            $summary['total_cost'] = floatval($summary['total_cost'] ?? 0);
            $summary['total_animals_fed'] = intval($summary['total_animals_fed'] ?? 0);
            $summary['avg_concentrate_per_animal'] = floatval($summary['avg_concentrate_per_animal'] ?? 0);
            
            sendResponse($summary);
            break;
            
        case 'stats':
            // Estatísticas do período
            $days = intval($_GET['days'] ?? 30);
            $date_from = date('Y-m-d', strtotime("-{$days} days"));
            $date_to = date('Y-m-d');
            
            $results = $db->query("
                SELECT 
                    COUNT(DISTINCT feed_date) as days_with_records,
                    COUNT(*) as total_records,
                    COUNT(DISTINCT animal_id) as total_animals,
                    SUM(concentrate_kg) as total_concentrate,
                    SUM(roughage_kg) as total_roughage,
                    SUM(silage_kg) as total_silage,
                    SUM(hay_kg) as total_hay,
                    SUM(total_cost) as total_cost,
                    AVG(concentrate_kg) as avg_concentrate,
                    AVG(roughage_kg) as avg_roughage
                FROM feed_records
                WHERE feed_date BETWEEN ? AND ? AND farm_id = ?
            ", [$date_from, $date_to, $farm_id]);
            
            $stats = $results[0] ?? [];
            $stats['period_days'] = $days;
            $stats['date_from'] = $date_from;
            $stats['date_to'] = $date_to;
            $stats['total_concentrate'] = floatval($stats['total_concentrate'] ?? 0);
            $stats['total_roughage'] = floatval($stats['total_roughage'] ?? 0);
            $stats['total_silage'] = floatval($stats['total_silage'] ?? 0);
            $stats['total_hay'] = floatval($stats['total_hay'] ?? 0);
            $stats['total_cost'] = floatval($stats['total_cost'] ?? 0);
            $stats['avg_daily_concentrate'] = $stats['days_with_records'] > 0 
                ? floatval($stats['total_concentrate']) / intval($stats['days_with_records']) 
                : 0;
            $stats['avg_daily_roughage'] = $stats['days_with_records'] > 0 
                ? floatval($stats['total_roughage']) / intval($stats['days_with_records']) 
                : 0;
            
            // Calcular custo médio por kg (se houver registros com custo)
            $costStats = $db->query("
                SELECT 
                    AVG(cost_per_kg) as avg_cost_per_kg_concentrate
                FROM feed_records
                WHERE feed_date BETWEEN ? AND ? 
                AND farm_id = ? 
                AND cost_per_kg IS NOT NULL 
                AND cost_per_kg > 0
            ", [$date_from, $date_to, $farm_id]);
            
            $stats['cost_per_kg_concentrate'] = floatval($costStats[0]['avg_cost_per_kg_concentrate'] ?? 0);
            
            sendResponse($stats);
            break;
            
        case 'animals':
            // Listar TODOS os animais da tabela animals
            try {
                error_log("Feed API - Buscando TODOS os animais para farm_id: " . $farm_id);
                
                // Query simplificada - buscar todos os animais ativos da fazenda
                $sql = "
                    SELECT 
                        a.id,
                        a.animal_number,
                        a.name,
                        a.breed,
                        a.gender,
                        a.birth_date,
                        a.status,
                        a.farm_id,
                        a.is_active,
                        DATEDIFF(CURDATE(), a.birth_date) as age_days
                    FROM animals a
                    WHERE a.farm_id = ? AND (a.is_active = 1 OR a.is_active IS NULL)
                    ORDER BY a.animal_number ASC
                ";
                
                $animals = $db->query($sql, [$farm_id]);
                
                // Se não encontrar, tentar sem filtro de is_active
                if (empty($animals) || !is_array($animals) || count($animals) === 0) {
                    error_log("Feed API - Tentando sem filtro is_active...");
                    $sql = "
                        SELECT 
                            a.id,
                            a.animal_number,
                            a.name,
                            a.breed,
                            a.gender,
                            a.birth_date,
                            a.status,
                            a.farm_id,
                            a.is_active,
                            DATEDIFF(CURDATE(), a.birth_date) as age_days
                        FROM animals a
                        WHERE a.farm_id = ?
                        ORDER BY a.animal_number ASC
                    ";
                    $animals = $db->query($sql, [$farm_id]);
                }
                
                // Se ainda não encontrar, buscar todos (fallback)
                if (empty($animals) || !is_array($animals) || count($animals) === 0) {
                    error_log("Feed API - Buscando TODOS os animais (fallback)...");
                    $sql = "
                        SELECT 
                            a.id,
                            a.animal_number,
                            a.name,
                            a.breed,
                            a.gender,
                            a.birth_date,
                            a.status,
                            a.farm_id,
                            a.is_active,
                            DATEDIFF(CURDATE(), a.birth_date) as age_days
                        FROM animals a
                        ORDER BY a.animal_number ASC
                        LIMIT 200
                    ";
                    $animals = $db->query($sql);
                }
                
                $animalsArray = is_array($animals) ? $animals : [];
                error_log("Feed API - Total de animais encontrados: " . count($animalsArray));
                
                // Retornar sempre um array, mesmo que vazio
                sendResponse($animalsArray);
                
            } catch (Exception $e) {
                error_log("Feed API - ERRO: " . $e->getMessage());
                error_log("Feed API - Stack: " . $e->getTraceAsString());
                sendResponse([], 'Erro ao buscar animais: ' . $e->getMessage());
            }
            break;
            
        default:
            sendResponse(null, 'Ação não encontrada');
    }
    
} catch (Exception $e) {
    error_log("Erro na API de alimentação: " . $e->getMessage());
    sendResponse(null, 'Erro interno: ' . $e->getMessage());
}
?>
