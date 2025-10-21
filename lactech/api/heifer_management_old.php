<?php
/**
 * API: Heifer Management System
 * Sistema completo de controle de custos de novilhas
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

ob_start();
ob_clean();

// Suprimir avisos de sessão
@session_start();

require_once '../includes/config.php';
require_once '../includes/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $action = $_GET['action'] ?? $_POST['action'] ?? 'get_dashboard';
    $response = ['success' => false];
    
    switch ($action) {
        
        // ==========================================
        // DASHBOARD - Visão geral do sistema
        // ==========================================
        case 'get_dashboard':
            // Estatísticas gerais
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(DISTINCT a.id) as total_heifers,
                    COUNT(DISTINCT CASE WHEN DATEDIFF(CURDATE(), a.birth_date) <= 60 THEN a.id END) as phase_aleitamento,
                    COUNT(DISTINCT CASE WHEN DATEDIFF(CURDATE(), a.birth_date) BETWEEN 61 AND 90 THEN a.id END) as phase_transicao,
                    COUNT(DISTINCT CASE WHEN DATEDIFF(CURDATE(), a.birth_date) BETWEEN 91 AND 180 THEN a.id END) as phase_recria1,
                    COUNT(DISTINCT CASE WHEN DATEDIFF(CURDATE(), a.birth_date) BETWEEN 181 AND 365 THEN a.id END) as phase_recria2,
                    COUNT(DISTINCT CASE WHEN DATEDIFF(CURDATE(), a.birth_date) BETWEEN 366 AND 540 THEN a.id END) as phase_crescimento,
                    COUNT(DISTINCT CASE WHEN DATEDIFF(CURDATE(), a.birth_date) BETWEEN 541 AND 780 THEN a.id END) as phase_preparto,
                    COALESCE(SUM(hcr.total_cost), 0) as total_invested,
                    COALESCE(AVG(hcr.total_cost), 0) as avg_cost_per_record
                FROM animals a
                LEFT JOIN heifer_cost_records hcr ON a.id = hcr.animal_id
                WHERE a.farm_id = ? 
                AND (a.category = 'Novilha' OR a.category = 'Bezerro')
                AND a.status = 'Ativo'
            ");
            
            $farm_id = $_SESSION['farm_id'] ?? 1;
            $stmt->execute([$farm_id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Custos por categoria
            $stmt = $conn->prepare("
                SELECT 
                    hcc.category_type,
                    COALESCE(SUM(hcr.total_cost), 0) as total_cost,
                    COUNT(hcr.id) as total_records
                FROM heifer_cost_categories hcc
                LEFT JOIN heifer_cost_records hcr ON hcc.id = hcr.category_id AND hcr.farm_id = ?
                WHERE hcc.active = 1
                GROUP BY hcc.category_type
                ORDER BY total_cost DESC
            ");
            $stmt->execute([$farm_id]);
            $costs_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Novilhas mais caras (top 10)
            $stmt = $conn->prepare("
                SELECT 
                    a.id,
                    a.ear_tag,
                    a.name,
                    a.birth_date,
                    DATEDIFF(CURDATE(), a.birth_date) as age_days,
                    FLOOR(DATEDIFF(CURDATE(), a.birth_date) / 30) as age_months,
                    COALESCE(SUM(hcr.total_cost), 0) as total_cost
                FROM animals a
                LEFT JOIN heifer_cost_records hcr ON a.id = hcr.animal_id
                WHERE a.farm_id = ? 
                AND (a.category = 'Novilha' OR a.category = 'Bezerro')
                AND a.status = 'Ativo'
                GROUP BY a.id, a.ear_tag, a.name, a.birth_date
                ORDER BY total_cost DESC
                LIMIT 10
            ");
            $stmt->execute([$farm_id]);
            $top_expensive = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'costs_by_category' => $costs_by_category,
                    'top_expensive_heifers' => $top_expensive
                ]
            ];
            break;
        
        // ==========================================
        // LISTAR NOVILHAS COM CUSTOS
        // ==========================================
        case 'get_heifers_list':
            $stmt = $conn->prepare("
                SELECT 
                    a.id,
                    a.ear_tag,
                    a.name,
                    a.birth_date,
                    a.category,
                    DATEDIFF(CURDATE(), a.birth_date) as age_days,
                    FLOOR(DATEDIFF(CURDATE(), a.birth_date) / 30) as age_months,
                    hp.phase_name as current_phase,
                    COALESCE(SUM(hcr.total_cost), 0) as total_cost,
                    COUNT(hcr.id) as total_records,
                    MAX(hcr.cost_date) as last_cost_date
                FROM animals a
                LEFT JOIN heifer_cost_records hcr ON a.id = hcr.animal_id
                LEFT JOIN heifer_phases hp ON DATEDIFF(CURDATE(), a.birth_date) BETWEEN hp.start_day AND hp.end_day
                WHERE a.farm_id = ? 
                AND (a.category = 'Novilha' OR a.category = 'Bezerro')
                AND a.status = 'Ativo'
                GROUP BY a.id, a.ear_tag, a.name, a.birth_date, a.category, hp.phase_name
                ORDER BY a.birth_date DESC
            ");
            
            $farm_id = $_SESSION['farm_id'] ?? 1;
            $stmt->execute([$farm_id]);
            $heifers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'data' => $heifers
            ];
            break;
        
        // ==========================================
        // DETALHES DE UMA NOVILHA
        // ==========================================
        case 'get_heifer_details':
            $animal_id = $_GET['animal_id'] ?? $_POST['animal_id'] ?? null;
            
            if (!$animal_id) {
                throw new Exception('ID do animal não fornecido');
            }
            
            // Informações básicas
            $stmt = $conn->prepare("
                SELECT 
                    a.*,
                    DATEDIFF(CURDATE(), a.birth_date) as age_days,
                    FLOOR(DATEDIFF(CURDATE(), a.birth_date) / 30) as age_months,
                    hp.id as current_phase_id,
                    hp.phase_name as current_phase,
                    hp.start_day as phase_start_day,
                    hp.end_day as phase_end_day,
                    hp.avg_daily_milk_liters,
                    hp.avg_daily_concentrate_kg,
                    hp.avg_daily_roughage_kg
                FROM animals a
                LEFT JOIN heifer_phases hp ON DATEDIFF(CURDATE(), a.birth_date) BETWEEN hp.start_day AND hp.end_day
                WHERE a.id = ?
            ");
            $stmt->execute([$animal_id]);
            $animal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$animal) {
                throw new Exception('Animal não encontrado');
            }
            
            // Custos totais por categoria
            $stmt = $conn->prepare("
                SELECT 
                    hcc.category_type,
                    hcc.category_name,
                    SUM(hcr.total_cost) as total_cost,
                    COUNT(hcr.id) as total_records
                FROM heifer_cost_records hcr
                INNER JOIN heifer_cost_categories hcc ON hcr.category_id = hcc.id
                WHERE hcr.animal_id = ?
                GROUP BY hcc.category_type, hcc.category_name
                ORDER BY total_cost DESC
            ");
            $stmt->execute([$animal_id]);
            $costs_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Custos por fase
            $stmt = $conn->prepare("
                SELECT 
                    hp.phase_name,
                    hp.start_day,
                    hp.end_day,
                    SUM(hcr.total_cost) as phase_total_cost,
                    COUNT(hcr.id) as phase_records
                FROM heifer_cost_records hcr
                LEFT JOIN heifer_phases hp ON hcr.phase_id = hp.id
                WHERE hcr.animal_id = ?
                GROUP BY hp.phase_name, hp.start_day, hp.end_day
                ORDER BY hp.start_day
            ");
            $stmt->execute([$animal_id]);
            $costs_by_phase = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Últimos registros de custos
            $stmt = $conn->prepare("
                SELECT 
                    hcr.*,
                    hcc.category_name,
                    hcc.category_type,
                    hp.phase_name,
                    u.name as recorded_by_name
                FROM heifer_cost_records hcr
                INNER JOIN heifer_cost_categories hcc ON hcr.category_id = hcc.id
                LEFT JOIN heifer_phases hp ON hcr.phase_id = hp.id
                LEFT JOIN users u ON hcr.recorded_by = u.id
                WHERE hcr.animal_id = ?
                ORDER BY hcr.cost_date DESC, hcr.created_at DESC
                LIMIT 50
            ");
            $stmt->execute([$animal_id]);
            $recent_costs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular custo total
            $total_cost = array_sum(array_column($costs_by_category, 'total_cost'));
            $total_records = array_sum(array_column($costs_by_category, 'total_records'));
            
            $response = [
                'success' => true,
                'data' => [
                    'animal' => $animal,
                    'total_cost' => $total_cost,
                    'total_records' => $total_records,
                    'avg_daily_cost' => $animal['age_days'] > 0 ? $total_cost / $animal['age_days'] : 0,
                    'costs_by_category' => $costs_by_category,
                    'costs_by_phase' => $costs_by_phase,
                    'recent_costs' => $recent_costs
                ]
            ];
            break;
        
        // ==========================================
        // LISTAR FASES DE CRIAÇÃO
        // ==========================================
        case 'get_phases':
            $stmt = $conn->query("SELECT * FROM heifer_phases WHERE active = 1 ORDER BY start_day");
            $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'data' => $phases
            ];
            break;
        
        // ==========================================
        // LISTAR CATEGORIAS DE CUSTOS
        // ==========================================
        case 'get_cost_categories':
            $type = $_GET['type'] ?? null;
            
            $sql = "SELECT * FROM heifer_cost_categories WHERE active = 1";
            if ($type) {
                $sql .= " AND category_type = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$type]);
            } else {
                $sql .= " ORDER BY category_type, category_name";
                $stmt = $conn->query($sql);
            }
            
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'data' => $categories
            ];
            break;
        
        // ==========================================
        // ADICIONAR CUSTO
        // ==========================================
        case 'add_cost':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $required = ['animal_id', 'category_id', 'cost_date', 'unit_price'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    throw new Exception("Campo obrigatório: {$field}");
                }
            }
            
            $farm_id = $_SESSION['farm_id'] ?? 1;
            $user_id = $_SESSION['user_id'] ?? 1;
            
            // Calcular total_cost
            $quantity = $data['quantity'] ?? 1;
            $total_cost = $quantity * $data['unit_price'];
            
            $stmt = $conn->prepare("
                INSERT INTO heifer_cost_records 
                (animal_id, phase_id, category_id, cost_date, quantity, unit, unit_price, total_cost, description, recorded_by, farm_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['animal_id'],
                $data['phase_id'] ?? null,
                $data['category_id'],
                $data['cost_date'],
                $quantity,
                $data['unit'] ?? 'Unidade',
                $data['unit_price'],
                $total_cost,
                $data['description'] ?? null,
                $user_id,
                $farm_id
            ]);
            
            $cost_id = $conn->lastInsertId();
            
            $response = [
                'success' => true,
                'message' => 'Custo registrado com sucesso!',
                'cost_id' => $cost_id,
                'total_cost' => $total_cost
            ];
            break;
        
        // ==========================================
        // ADICIONAR MÚLTIPLOS CUSTOS (EM LOTE)
        // ==========================================
        case 'add_bulk_costs':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['costs']) || !is_array($data['costs'])) {
                throw new Exception('Array de custos não fornecido');
            }
            
            $farm_id = $_SESSION['farm_id'] ?? 1;
            $user_id = $_SESSION['user_id'] ?? 1;
            
            $conn->beginTransaction();
            
            $inserted = 0;
            $total_amount = 0;
            
            foreach ($data['costs'] as $cost) {
                $quantity = $cost['quantity'] ?? 1;
                $total_cost = $quantity * $cost['unit_price'];
                
                $stmt = $conn->prepare("
                    INSERT INTO heifer_cost_records 
                    (animal_id, phase_id, category_id, cost_date, quantity, unit, unit_price, total_cost, description, is_automatic, recorded_by, farm_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $cost['animal_id'],
                    $cost['phase_id'] ?? null,
                    $cost['category_id'],
                    $cost['cost_date'],
                    $quantity,
                    $cost['unit'] ?? 'Unidade',
                    $cost['unit_price'],
                    $total_cost,
                    $cost['description'] ?? null,
                    $cost['is_automatic'] ?? 0,
                    $user_id,
                    $farm_id
                ]);
                
                $inserted++;
                $total_amount += $total_cost;
            }
            
            $conn->commit();
            
            $response = [
                'success' => true,
                'message' => "{$inserted} custos registrados com sucesso!",
                'inserted' => $inserted,
                'total_amount' => $total_amount
            ];
            break;
        
        // ==========================================
        // ATUALIZAR CUSTO
        // ==========================================
        case 'update_cost':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'])) {
                throw new Exception('ID do custo não fornecido');
            }
            
            // Recalcular total_cost se quantidade ou preço mudaram
            if (isset($data['quantity']) && isset($data['unit_price'])) {
                $data['total_cost'] = $data['quantity'] * $data['unit_price'];
            }
            
            $updates = [];
            $values = [];
            
            $allowed_fields = ['category_id', 'cost_date', 'quantity', 'unit', 'unit_price', 'total_cost', 'description'];
            
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "{$field} = ?";
                    $values[] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                throw new Exception('Nenhum campo para atualizar');
            }
            
            $values[] = $data['id'];
            
            $sql = "UPDATE heifer_cost_records SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($values);
            
            $response = [
                'success' => true,
                'message' => 'Custo atualizado com sucesso!'
            ];
            break;
        
        // ==========================================
        // DELETAR CUSTO
        // ==========================================
        case 'delete_cost':
            $cost_id = $_GET['id'] ?? $_POST['id'] ?? null;
            
            if (!$cost_id) {
                throw new Exception('ID do custo não fornecido');
            }
            
            $stmt = $conn->prepare("DELETE FROM heifer_cost_records WHERE id = ?");
            $stmt->execute([$cost_id]);
            
            $response = [
                'success' => true,
                'message' => 'Custo excluído com sucesso!'
            ];
            break;
        
        // ==========================================
        // CALCULAR CUSTOS AUTOMÁTICOS
        // ==========================================
        case 'calculate_automatic_costs':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $animal_id = $data['animal_id'] ?? null;
            $start_date = $data['start_date'] ?? null;
            $end_date = $data['end_date'] ?? date('Y-m-d');
            
            if (!$animal_id || !$start_date) {
                throw new Exception('Parâmetros insuficientes');
            }
            
            // Buscar informações do animal
            $stmt = $conn->prepare("SELECT birth_date FROM animals WHERE id = ?");
            $stmt->execute([$animal_id]);
            $animal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$animal) {
                throw new Exception('Animal não encontrado');
            }
            
            // Buscar preços padrão (última entrada de cada categoria)
            $prices = [];
            $stmt = $conn->query("
                SELECT DISTINCT 
                    category_id,
                    (SELECT unit_price FROM heifer_price_history WHERE category_id = hph.category_id ORDER BY price_date DESC LIMIT 1) as current_price,
                    (SELECT unit FROM heifer_price_history WHERE category_id = hph.category_id ORDER BY price_date DESC LIMIT 1) as unit
                FROM heifer_price_history hph
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $prices[$row['category_id']] = [
                    'price' => $row['current_price'],
                    'unit' => $row['unit']
                ];
            }
            
            $response = [
                'success' => true,
                'message' => 'Cálculo automático em desenvolvimento',
                'animal' => $animal,
                'prices' => $prices
            ];
            break;
        
        // ==========================================
        // ADICIONAR/ATUALIZAR PREÇO DE INSUMO
        // ==========================================
        case 'update_price':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $required = ['category_id', 'price_date', 'unit_price', 'unit'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Campo obrigatório: {$field}");
                }
            }
            
            $farm_id = $_SESSION['farm_id'] ?? 1;
            $user_id = $_SESSION['user_id'] ?? 1;
            
            $stmt = $conn->prepare("
                INSERT INTO heifer_price_history 
                (category_id, price_date, unit_price, unit, notes, farm_id, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['category_id'],
                $data['price_date'],
                $data['unit_price'],
                $data['unit'],
                $data['notes'] ?? null,
                $farm_id,
                $user_id
            ]);
            
            $response = [
                'success' => true,
                'message' => 'Preço atualizado com sucesso!'
            ];
            break;
        
        // ==========================================
        // RELATÓRIO COMPLETO DE NOVILHA
        // ==========================================
        case 'generate_report':
            $animal_id = $_GET['animal_id'] ?? $_POST['animal_id'] ?? null;
            
            if (!$animal_id) {
                throw new Exception('ID do animal não fornecido');
            }
            
            // Buscar todos os dados da novilha
            $stmt = $conn->prepare("
                SELECT 
                    a.*,
                    DATEDIFF(CURDATE(), a.birth_date) as age_days,
                    FLOOR(DATEDIFF(CURDATE(), a.birth_date) / 30) as age_months,
                    hp.phase_name as current_phase
                FROM animals a
                LEFT JOIN heifer_phases hp ON DATEDIFF(CURDATE(), a.birth_date) BETWEEN hp.start_day AND hp.end_day
                WHERE a.id = ?
            ");
            $stmt->execute([$animal_id]);
            $animal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Custos detalhados
            $stmt = $conn->prepare("
                SELECT 
                    hcr.*,
                    hcc.category_name,
                    hcc.category_type,
                    hp.phase_name
                FROM heifer_cost_records hcr
                INNER JOIN heifer_cost_categories hcc ON hcr.category_id = hcc.id
                LEFT JOIN heifer_phases hp ON hcr.phase_id = hp.id
                WHERE hcr.animal_id = ?
                ORDER BY hcr.cost_date, hcr.created_at
            ");
            $stmt->execute([$animal_id]);
            $all_costs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'data' => [
                    'animal' => $animal,
                    'costs' => $all_costs,
                    'total_cost' => array_sum(array_column($all_costs, 'total_cost')),
                    'generated_at' => date('Y-m-d H:i:s')
                ]
            ];
            break;
        
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getTrace()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>

