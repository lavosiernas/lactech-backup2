<?php
/**
 * API: Heifer Management System - COMPATÍVEL COM BANCO ATUAL
 * Sistema completo de controle de custos de novilhas
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Limpar qualquer saída anterior
while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se os arquivos existem
$configMysqlPath = __DIR__ . '/../includes/config_mysql.php';
$dbPath = __DIR__ . '/../includes/database.php';

// SEMPRE carregar config_mysql.php PRIMEIRO (tem detecção de ambiente)
if (!file_exists($configMysqlPath)) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Arquivo config_mysql.php não encontrado']);
    exit;
}

if (!file_exists($dbPath)) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Arquivo database.php não encontrado']);
    exit;
}

// Carregar config_mysql.php PRIMEIRO (detecta ambiente e define constantes)
require_once $configMysqlPath;

// Depois carregar database.php (usa as constantes já definidas)
require_once $dbPath;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('Erro ao conectar ao banco de dados');
    }
    
    $action = $_GET['action'] ?? $_POST['action'] ?? 'get_dashboard';
    $response = ['success' => false];
    
    switch ($action) {
        
        // ==========================================
        // DASHBOARD - Visão geral do sistema
        // ==========================================
        case 'get_dashboard':
            $farm_id = $_SESSION['farm_id'] ?? 1;
            
            if (!$farm_id) {
                $farm_id = 1;
            }
            
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
                    COALESCE(SUM(hc.cost_amount), 0) as total_invested,
                    COALESCE(AVG(hc.cost_amount), 0) as avg_cost_per_record
                FROM animals a
                LEFT JOIN heifer_costs hc ON a.id = hc.animal_id
                WHERE a.farm_id = ? 
                AND (a.status = 'Novilha' OR a.status = 'Bezerra' OR a.status = 'Bezerro')
                AND a.is_active = 1
            ");
            
            $stmt->execute([$farm_id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Custos por categoria
            $stmt = $conn->prepare("
                SELECT 
                    hc.cost_category as category_type,
                    hc.cost_category as category_name,
                    COALESCE(SUM(hc.cost_amount), 0) as total_cost,
                    COUNT(hc.id) as total_records
                FROM heifer_costs hc
                WHERE hc.farm_id = ?
                GROUP BY hc.cost_category
                ORDER BY total_cost DESC
            ");
            $stmt->execute([$farm_id]);
            $costs_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Novilhas mais caras (top 10)
            $stmt = $conn->prepare("
                SELECT 
                    a.id,
                    a.animal_number AS ear_tag,
                    a.name,
                    a.birth_date,
                    DATEDIFF(CURDATE(), a.birth_date) as age_days,
                    FLOOR(DATEDIFF(CURDATE(), a.birth_date) / 30) as age_months,
                    COALESCE(SUM(hc.cost_amount), 0) as total_cost
                FROM animals a
                LEFT JOIN heifer_costs hc ON a.id = hc.animal_id
                WHERE a.farm_id = ? 
                AND (a.status = 'Novilha' OR a.status = 'Bezerra' OR a.status = 'Bezerro')
                AND a.is_active = 1
                GROUP BY a.id, a.animal_number, a.name, a.birth_date
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
            $farm_id = $_SESSION['farm_id'] ?? 1;
            
            $stmt = $conn->prepare("
                SELECT 
                    a.id,
                    a.animal_number AS ear_tag,
                    a.name,
                    a.birth_date,
                    a.status AS category,
                    DATEDIFF(CURDATE(), a.birth_date) as age_days,
                    FLOOR(DATEDIFF(CURDATE(), a.birth_date) / 30) as age_months,
                    CASE 
                        WHEN DATEDIFF(CURDATE(), a.birth_date) <= 60 THEN 'Aleitamento'
                        WHEN DATEDIFF(CURDATE(), a.birth_date) BETWEEN 61 AND 90 THEN 'Transição/Desmame'
                        WHEN DATEDIFF(CURDATE(), a.birth_date) BETWEEN 91 AND 180 THEN 'Recria Inicial'
                        WHEN DATEDIFF(CURDATE(), a.birth_date) BETWEEN 181 AND 365 THEN 'Recria Intermediária'
                        WHEN DATEDIFF(CURDATE(), a.birth_date) BETWEEN 366 AND 540 THEN 'Crescimento/Desenvolvimento'
                        WHEN DATEDIFF(CURDATE(), a.birth_date) BETWEEN 541 AND 780 THEN 'Pré-parto'
                        ELSE 'Sem fase definida'
                    END AS current_phase,
                    COALESCE(SUM(hc.cost_amount), 0) as total_cost,
                    COUNT(hc.id) as total_records,
                    MAX(hc.cost_date) as last_cost_date
                FROM animals a
                LEFT JOIN heifer_costs hc ON a.id = hc.animal_id
                WHERE a.farm_id = ? 
                AND (a.status = 'Novilha' OR a.status = 'Bezerra' OR a.status = 'Bezerro')
                AND a.is_active = 1
                GROUP BY a.id, a.animal_number, a.name, a.birth_date, a.status
                ORDER BY a.birth_date DESC
            ");
            
            $stmt->execute([$farm_id]);
            $heifers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'data' => [
                    'heifers' => $heifers
                ]
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
                    CASE 
                        WHEN DATEDIFF(CURDATE(), a.birth_date) <= 60 THEN 'Aleitamento'
                        WHEN DATEDIFF(CURDATE(), a.birth_date) BETWEEN 61 AND 90 THEN 'Transição/Desmame'
                        WHEN DATEDIFF(CURDATE(), a.birth_date) BETWEEN 91 AND 180 THEN 'Recria Inicial'
                        WHEN DATEDIFF(CURDATE(), a.birth_date) BETWEEN 181 AND 365 THEN 'Recria Intermediária'
                        WHEN DATEDIFF(CURDATE(), a.birth_date) BETWEEN 366 AND 540 THEN 'Crescimento/Desenvolvimento'
                        WHEN DATEDIFF(CURDATE(), a.birth_date) BETWEEN 541 AND 780 THEN 'Pré-parto'
                        ELSE 'Sem fase definida'
                    END AS current_phase
                FROM animals a
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
                    hc.cost_category AS category_type,
                    hc.cost_category AS category_name,
                    SUM(hc.cost_amount) as total_cost,
                    COUNT(hc.id) as total_records
                FROM heifer_costs hc
                WHERE hc.animal_id = ?
                GROUP BY hc.cost_category
                ORDER BY total_cost DESC
            ");
            $stmt->execute([$animal_id]);
            $costs_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Custos por fase (baseado em data real)
            $stmt = $conn->prepare("
                SELECT 
                    CASE 
                        WHEN DATEDIFF(hc.cost_date, a.birth_date) <= 60 THEN 'Aleitamento'
                        WHEN DATEDIFF(hc.cost_date, a.birth_date) BETWEEN 61 AND 90 THEN 'Transição/Desmame'
                        WHEN DATEDIFF(hc.cost_date, a.birth_date) BETWEEN 91 AND 180 THEN 'Recria Inicial'
                        WHEN DATEDIFF(hc.cost_date, a.birth_date) BETWEEN 181 AND 365 THEN 'Recria Intermediária'
                        WHEN DATEDIFF(hc.cost_date, a.birth_date) BETWEEN 366 AND 540 THEN 'Crescimento/Desenvolvimento'
                        WHEN DATEDIFF(hc.cost_date, a.birth_date) BETWEEN 541 AND 780 THEN 'Pré-parto'
                        ELSE 'Sem fase'
                    END AS phase_name,
                    SUM(hc.cost_amount) as phase_total_cost,
                    COUNT(hc.id) as phase_records
                FROM heifer_costs hc
                INNER JOIN animals a ON hc.animal_id = a.id
                WHERE hc.animal_id = ?
                GROUP BY phase_name
                ORDER BY MIN(hc.cost_date)
            ");
            $stmt->execute([$animal_id]);
            $costs_by_phase = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Últimos registros de custos
            $stmt = $conn->prepare("
                SELECT 
                    hc.*,
                    hc.cost_category AS category_name,
                    hc.cost_category AS category_type,
                    u.name as recorded_by_name
                FROM heifer_costs hc
                LEFT JOIN users u ON hc.recorded_by = u.id
                WHERE hc.animal_id = ?
                ORDER BY hc.cost_date DESC, hc.created_at DESC
                LIMIT 50
            ");
            $stmt->execute([$animal_id]);
            $recent_costs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular custo total
            $total_cost = array_sum(array_column($costs_by_category, 'total_cost'));
            $total_records = array_sum(array_column($costs_by_category, 'total_records'));
            
            // Calcular médias e projeção
            $age_days = (int)$animal['age_days'];
            $age_months = (int)$animal['age_months'];
            $avg_daily_cost = $age_days > 0 ? $total_cost / $age_days : 0;
            $avg_monthly_cost = $age_months > 0 ? $total_cost / $age_months : 0;
            
            // Projeção até 26 meses (780 dias)
            $target_days = 780;
            $remaining_days = max(0, $target_days - $age_days);
            $projected_total = $total_cost + ($avg_daily_cost * $remaining_days);
            
            $response = [
                'success' => true,
                'data' => [
                    'animal' => $animal,
                    'total_cost' => round($total_cost, 2),
                    'total_records' => $total_records,
                    'avg_daily_cost' => round($avg_daily_cost, 2),
                    'avg_monthly_cost' => round($avg_monthly_cost, 2),
                    'projection' => [
                        'age_days' => $age_days,
                        'age_months' => $age_months,
                        'target_days' => $target_days,
                        'remaining_days' => $remaining_days,
                        'projected_total_26_months' => round($projected_total, 2),
                        'projected_remaining_cost' => round($avg_daily_cost * $remaining_days, 2)
                    ],
                    'costs_by_category' => $costs_by_category,
                    'costs_by_phase' => $costs_by_phase,
                    'recent_costs' => $recent_costs
                ]
            ];
            break;
        
        // ==========================================
        // LISTAR CATEGORIAS DE CUSTOS
        // ==========================================
        case 'get_cost_categories':
            $farm_id = $_SESSION['farm_id'] ?? 1;
            
            // Buscar categorias do banco
            $stmt = $conn->prepare("
                SELECT 
                    id,
                    category_name,
                    category_type,
                    description,
                    active
                FROM heifer_cost_categories
                WHERE active = 1
                ORDER BY category_type, category_name
            ");
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Para cada categoria, buscar preço atual
            foreach ($categories as &$category) {
                $stmt = $conn->prepare("
                    SELECT unit_price, unit, price_date
                    FROM heifer_price_history
                    WHERE category_id = ? AND farm_id = ?
                    ORDER BY price_date DESC
                    LIMIT 1
                ");
                $stmt->execute([$category['id'], $farm_id]);
                $current_price = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($current_price) {
                    $category['current_price'] = $current_price['unit_price'];
                    $category['current_unit'] = $current_price['unit'];
                    $category['price_date'] = $current_price['price_date'];
                } else {
                    $category['current_price'] = null;
                    $category['current_unit'] = null;
                    $category['price_date'] = null;
                }
            }
            
            $response = [
                'success' => true,
                'data' => $categories
            ];
            break;
        
        // ==========================================
        // ADICIONAR CUSTO (compatível com estrutura antiga)
        // ==========================================
        case 'add_cost':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $required = ['animal_id', 'cost_date', 'cost_category', 'cost_amount'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    throw new Exception("Campo obrigatório: {$field}");
                }
            }
            
            $farm_id = $_SESSION['farm_id'] ?? 1;
            $user_id = $_SESSION['user_id'] ?? 1;
            
            // Usar cost_category diretamente do formulário, ou calcular se não fornecido
            $cost_category = $data['cost_category'] ?? 'Outros';
            
            // Se cost_amount não foi fornecido, calcular
            $cost_amount = $data['cost_amount'] ?? 0;
            if ($cost_amount == 0 && isset($data['quantity']) && isset($data['unit_price'])) {
                $quantity = floatval($data['quantity'] ?? 1);
                $unit_price = floatval($data['unit_price'] ?? 0);
                $cost_amount = $quantity * $unit_price;
            }
            
            if ($cost_amount <= 0) {
                throw new Exception('Valor do custo deve ser maior que zero');
            }
            
            $stmt = $conn->prepare("
                INSERT INTO heifer_costs 
                (animal_id, cost_date, cost_category, cost_amount, description, recorded_by, farm_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['animal_id'],
                $data['cost_date'],
                $cost_category,
                $cost_amount,
                $data['description'] ?? null,
                $user_id,
                $farm_id
            ]);
            
            $cost_id = $conn->lastInsertId();
            
            $response = [
                'success' => true,
                'message' => 'Custo registrado com sucesso!',
                'cost_id' => $cost_id,
                'total_cost' => $cost_amount
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
            
            $stmt = $conn->prepare("DELETE FROM heifer_costs WHERE id = ?");
            $stmt->execute([$cost_id]);
            
            $response = [
                'success' => true,
                'message' => 'Custo excluído com sucesso!'
            ];
            break;
        
        // ==========================================
        // ADICIONAR CONSUMO DIÁRIO
        // ==========================================
        case 'add_daily_consumption':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $required = ['animal_id', 'consumption_date'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    throw new Exception("Campo obrigatório: {$field}");
                }
            }
            
            $farm_id = $_SESSION['farm_id'] ?? 1;
            $user_id = $_SESSION['user_id'] ?? 1;
            
            // Calcular idade em dias
            $stmt = $conn->prepare("SELECT birth_date FROM animals WHERE id = ?");
            $stmt->execute([$data['animal_id']]);
            $animal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$animal) {
                throw new Exception('Animal não encontrado');
            }
            
            $age_days = floor((strtotime($data['consumption_date']) - strtotime($animal['birth_date'])) / 86400);
            
            // Determinar fase baseada na idade
            $stmt = $conn->prepare("
                SELECT id FROM heifer_phases
                WHERE ? BETWEEN start_day AND end_day
                AND active = 1
                LIMIT 1
            ");
            $stmt->execute([$age_days]);
            $phase = $stmt->fetch(PDO::FETCH_ASSOC);
            $phase_id = $phase ? $phase['id'] : null;
            
            $stmt = $conn->prepare("
                INSERT INTO heifer_daily_consumption 
                (animal_id, consumption_date, age_days, phase_id, milk_liters, concentrate_kg, roughage_kg, weight_kg, notes, recorded_by, farm_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['animal_id'],
                $data['consumption_date'],
                $age_days,
                $phase_id,
                $data['milk_liters'] ?? 0,
                $data['concentrate_kg'] ?? 0,
                $data['roughage_kg'] ?? 0,
                $data['weight_kg'] ?? null,
                $data['notes'] ?? null,
                $user_id,
                $farm_id
            ]);
            
            $consumption_id = $conn->lastInsertId();
            
            $response = [
                'success' => true,
                'message' => 'Consumo diário registrado com sucesso!',
                'consumption_id' => $consumption_id
            ];
            break;
        
        // ==========================================
        // PREÇOS DIÁRIOS - Buscar preço atual
        // ==========================================
        case 'get_current_price':
            $category_id = $_GET['category_id'] ?? null;
            $date = $_GET['date'] ?? date('Y-m-d');
            $farm_id = $_SESSION['farm_id'] ?? 1;
            
            if (!$category_id) {
                throw new Exception('ID da categoria não fornecido');
            }
            
            // Buscar preço mais recente até a data especificada
            $stmt = $conn->prepare("
                SELECT 
                    id,
                    category_id,
                    price_date,
                    unit_price,
                    unit,
                    notes
                FROM heifer_price_history
                WHERE category_id = ? 
                AND farm_id = ?
                AND price_date <= ?
                ORDER BY price_date DESC
                LIMIT 1
            ");
            $stmt->execute([$category_id, $farm_id, $date]);
            $price = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$price) {
                // Se não encontrou, buscar o mais recente de qualquer data
                $stmt = $conn->prepare("
                    SELECT 
                        id,
                        category_id,
                        price_date,
                        unit_price,
                        unit,
                        notes
                    FROM heifer_price_history
                    WHERE category_id = ? 
                    AND farm_id = ?
                    ORDER BY price_date DESC
                    LIMIT 1
                ");
                $stmt->execute([$category_id, $farm_id]);
                $price = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            $response = [
                'success' => true,
                'data' => $price
            ];
            break;
        
        // ==========================================
        // PREÇOS DIÁRIOS - Atualizar preço do dia
        // ==========================================
        case 'update_daily_price':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $required = ['category_id', 'price_date', 'unit_price', 'unit'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    throw new Exception("Campo obrigatório: {$field}");
                }
            }
            
            $farm_id = $_SESSION['farm_id'] ?? 1;
            $user_id = $_SESSION['user_id'] ?? 1;
            
            // Verificar se já existe preço para esta data e categoria
            $stmt = $conn->prepare("
                SELECT id FROM heifer_price_history
                WHERE category_id = ? 
                AND price_date = ? 
                AND farm_id = ?
            ");
            $stmt->execute([$data['category_id'], $data['price_date'], $farm_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Atualizar existente
                $stmt = $conn->prepare("
                    UPDATE heifer_price_history
                    SET unit_price = ?, unit = ?, notes = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['unit_price'],
                    $data['unit'],
                    $data['notes'] ?? null,
                    $existing['id']
                ]);
                $price_id = $existing['id'];
            } else {
                // Criar novo
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
                $price_id = $conn->lastInsertId();
            }
            
            $response = [
                'success' => true,
                'message' => 'Preço atualizado com sucesso!',
                'price_id' => $price_id
            ];
            break;
        
        // ==========================================
        // PREÇOS DIÁRIOS - Histórico de preços
        // ==========================================
        case 'get_price_history':
            $category_id = $_GET['category_id'] ?? null;
            $farm_id = $_SESSION['farm_id'] ?? 1;
            
            if (!$category_id) {
                throw new Exception('ID da categoria não fornecido');
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    id,
                    category_id,
                    price_date,
                    unit_price,
                    unit,
                    notes,
                    created_at
                FROM heifer_price_history
                WHERE category_id = ? 
                AND farm_id = ?
                ORDER BY price_date DESC
                LIMIT 100
            ");
            $stmt->execute([$category_id, $farm_id]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'data' => $history
            ];
            break;
        
        // ==========================================
        // CÁLCULO AUTOMÁTICO - Calcular custos diários
        // ==========================================
        case 'calculate_daily_costs':
            $animal_id = $_GET['animal_id'] ?? $_POST['animal_id'] ?? null;
            $date = $_GET['date'] ?? $_POST['date'] ?? date('Y-m-d');
            $farm_id = $_SESSION['farm_id'] ?? 1;
            $user_id = $_SESSION['user_id'] ?? 1;
            
            // Se animal_id não fornecido, calcular para todas as novilhas ativas
            if ($animal_id) {
                $animals = [['id' => $animal_id]];
            } else {
                $stmt = $conn->prepare("
                    SELECT id FROM animals
                    WHERE farm_id = ?
                    AND (status = 'Novilha' OR status = 'Bezerra' OR status = 'Bezerro')
                    AND is_active = 1
                ");
                $stmt->execute([$farm_id]);
                $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $calculated = [];
            
            foreach ($animals as $animal) {
                $a_id = $animal['id'];
                
                // Buscar consumo do dia
                $stmt = $conn->prepare("
                    SELECT * FROM heifer_daily_consumption
                    WHERE animal_id = ? AND consumption_date = ?
                ");
                $stmt->execute([$a_id, $date]);
                $consumption = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$consumption) {
                    // Se não há consumo registrado, usar médias da fase
                    $stmt = $conn->prepare("
                        SELECT 
                            DATEDIFF(?, birth_date) as age_days
                        FROM animals WHERE id = ?
                    ");
                    $stmt->execute([$date, $a_id]);
                    $age = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($age && $age['age_days'] >= 0) {
                        // Buscar fase baseada na idade
                        $stmt = $conn->prepare("
                            SELECT * FROM heifer_phases
                            WHERE ? BETWEEN start_day AND end_day
                            AND active = 1
                            LIMIT 1
                        ");
                        $stmt->execute([$age['age_days']]);
                        $phase = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($phase) {
                            $consumption = [
                                'milk_liters' => $phase['avg_daily_milk_liters'] ?? 0,
                                'concentrate_kg' => $phase['avg_daily_concentrate_kg'] ?? 0,
                                'roughage_kg' => $phase['avg_daily_roughage_kg'] ?? 0,
                                'phase_id' => $phase['id']
                            ];
                        }
                    }
                }
                
                if ($consumption) {
                    $costs_created = [];
                    
                    // Calcular custo de leite/sucedâneo
                    if (($consumption['milk_liters'] ?? 0) > 0) {
                        // Buscar preço do sucedâneo (categoria 2)
                        $stmt = $conn->prepare("
                            SELECT unit_price FROM heifer_price_history
                            WHERE category_id = 2 AND farm_id = ? AND price_date <= ?
                            ORDER BY price_date DESC LIMIT 1
                        ");
                        $stmt->execute([$farm_id, $date]);
                        $price = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($price) {
                            $cost_amount = $consumption['milk_liters'] * $price['unit_price'];
                            
                            // Verificar se já existe custo para este dia
                            $stmt = $conn->prepare("
                                SELECT id FROM heifer_costs
                                WHERE animal_id = ? 
                                AND cost_date = ? 
                                AND category_id = 2
                                AND is_automatic = 1
                            ");
                            $stmt->execute([$a_id, $date]);
                            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (!$existing) {
                                $stmt = $conn->prepare("
                                    INSERT INTO heifer_costs
                                    (animal_id, phase_id, category_id, cost_date, cost_category, 
                                     quantity, unit, unit_price, total_cost, cost_amount, 
                                     description, is_automatic, recorded_by, farm_id)
                                    VALUES (?, ?, 2, ?, 'Alimentação', ?, 'Litros', ?, ?, ?, 
                                            'Sucedâneo diário automático', 1, ?, ?)
                                ");
                                $stmt->execute([
                                    $a_id,
                                    $consumption['phase_id'] ?? null,
                                    $date,
                                    $consumption['milk_liters'],
                                    $price['unit_price'],
                                    $cost_amount,
                                    $cost_amount,
                                    $user_id,
                                    $farm_id
                                ]);
                                $costs_created[] = $conn->lastInsertId();
                            }
                        }
                    }
                    
                    // Calcular custo de concentrado
                    if (($consumption['concentrate_kg'] ?? 0) > 0) {
                        // Determinar categoria (3 = inicial, 4 = crescimento)
                        $category_id = ($consumption['phase_id'] ?? 0) <= 2 ? 3 : 4;
                        
                        $stmt = $conn->prepare("
                            SELECT unit_price FROM heifer_price_history
                            WHERE category_id = ? AND farm_id = ? AND price_date <= ?
                            ORDER BY price_date DESC LIMIT 1
                        ");
                        $stmt->execute([$category_id, $farm_id, $date]);
                        $price = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($price) {
                            $cost_amount = $consumption['concentrate_kg'] * $price['unit_price'];
                            
                            $stmt = $conn->prepare("
                                SELECT id FROM heifer_costs
                                WHERE animal_id = ? 
                                AND cost_date = ? 
                                AND category_id = ?
                                AND is_automatic = 1
                            ");
                            $stmt->execute([$a_id, $date, $category_id]);
                            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (!$existing) {
                                $stmt = $conn->prepare("
                                    INSERT INTO heifer_costs
                                    (animal_id, phase_id, category_id, cost_date, cost_category, 
                                     quantity, unit, unit_price, total_cost, cost_amount, 
                                     description, is_automatic, recorded_by, farm_id)
                                    VALUES (?, ?, ?, ?, 'Alimentação', ?, 'Kg', ?, ?, ?, 
                                            'Concentrado diário automático', 1, ?, ?)
                                ");
                                $stmt->execute([
                                    $a_id,
                                    $consumption['phase_id'] ?? null,
                                    $category_id,
                                    $date,
                                    $consumption['concentrate_kg'],
                                    $price['unit_price'],
                                    $cost_amount,
                                    $cost_amount,
                                    $user_id,
                                    $farm_id
                                ]);
                                $costs_created[] = $conn->lastInsertId();
                            }
                        }
                    }
                    
                    // Calcular custo de volumoso (silagem - categoria 5)
                    if (($consumption['roughage_kg'] ?? 0) > 0) {
                        $stmt = $conn->prepare("
                            SELECT unit_price FROM heifer_price_history
                            WHERE category_id = 5 AND farm_id = ? AND price_date <= ?
                            ORDER BY price_date DESC LIMIT 1
                        ");
                        $stmt->execute([$farm_id, $date]);
                        $price = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($price) {
                            $cost_amount = $consumption['roughage_kg'] * $price['unit_price'];
                            
                            $stmt = $conn->prepare("
                                SELECT id FROM heifer_costs
                                WHERE animal_id = ? 
                                AND cost_date = ? 
                                AND category_id = 5
                                AND is_automatic = 1
                            ");
                            $stmt->execute([$a_id, $date]);
                            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (!$existing) {
                                $stmt = $conn->prepare("
                                    INSERT INTO heifer_costs
                                    (animal_id, phase_id, category_id, cost_date, cost_category, 
                                     quantity, unit, unit_price, total_cost, cost_amount, 
                                     description, is_automatic, recorded_by, farm_id)
                                    VALUES (?, ?, 5, ?, 'Alimentação', ?, 'Kg', ?, ?, ?, 
                                            'Volumoso diário automático', 1, ?, ?)
                                ");
                                $stmt->execute([
                                    $a_id,
                                    $consumption['phase_id'] ?? null,
                                    $date,
                                    $consumption['roughage_kg'],
                                    $price['unit_price'],
                                    $cost_amount,
                                    $cost_amount,
                                    $user_id,
                                    $farm_id
                                ]);
                                $costs_created[] = $conn->lastInsertId();
                            }
                        }
                    }
                    
                    $calculated[] = [
                        'animal_id' => $a_id,
                        'date' => $date,
                        'costs_created' => $costs_created
                    ];
                }
            }
            
            $response = [
                'success' => true,
                'message' => 'Custos calculados com sucesso!',
                'calculated' => $calculated
            ];
            break;
        
        // ==========================================
        // PROJEÇÃO - Projeção até 26 meses
        // ==========================================
        case 'get_projection':
            $animal_id = $_GET['animal_id'] ?? null;
            $farm_id = $_SESSION['farm_id'] ?? 1;
            
            if (!$animal_id) {
                throw new Exception('ID do animal não fornecido');
            }
            
            // Buscar dados do animal
            $stmt = $conn->prepare("
                SELECT 
                    id,
                    birth_date,
                    DATEDIFF(CURDATE(), birth_date) as age_days,
                    FLOOR(DATEDIFF(CURDATE(), birth_date) / 30) as age_months
                FROM animals
                WHERE id = ?
            ");
            $stmt->execute([$animal_id]);
            $animal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$animal) {
                throw new Exception('Animal não encontrado');
            }
            
            // Calcular custo acumulado até hoje
            $stmt = $conn->prepare("
                SELECT 
                    COALESCE(SUM(cost_amount), 0) as total_cost,
                    COUNT(*) as total_records
                FROM heifer_costs
                WHERE animal_id = ?
            ");
            $stmt->execute([$animal_id]);
            $costs = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $total_cost = (float)$costs['total_cost'];
            $age_days = (int)$animal['age_days'];
            $age_months = (int)$animal['age_months'];
            
            // Calcular médias
            $avg_daily_cost = $age_days > 0 ? $total_cost / $age_days : 0;
            $avg_monthly_cost = $age_months > 0 ? $total_cost / $age_months : 0;
            
            // Projeção até 26 meses (780 dias)
            $target_days = 780;
            $remaining_days = max(0, $target_days - $age_days);
            $projected_total = $total_cost + ($avg_daily_cost * $remaining_days);
            
            $response = [
                'success' => true,
                'data' => [
                    'animal_id' => $animal_id,
                    'age_days' => $age_days,
                    'age_months' => $age_months,
                    'total_cost' => $total_cost,
                    'avg_daily_cost' => round($avg_daily_cost, 2),
                    'avg_monthly_cost' => round($avg_monthly_cost, 2),
                    'projected_total_26_months' => round($projected_total, 2),
                    'remaining_days' => $remaining_days,
                    'projected_remaining_cost' => round($avg_daily_cost * $remaining_days, 2)
                ]
            ];
            break;
        
        // ==========================================
        // REGISTRO AUTOMÁTICO - Registrar consumo automático
        // ==========================================
        case 'auto_register_consumption':
            $animal_id = $_GET['animal_id'] ?? $_POST['animal_id'] ?? null;
            $date = $_GET['date'] ?? $_POST['date'] ?? date('Y-m-d');
            $farm_id = $_SESSION['farm_id'] ?? 1;
            $user_id = $_SESSION['user_id'] ?? 1;
            
            // Se animal_id não fornecido, processar todas as novilhas
            if ($animal_id) {
                $animals = [['id' => $animal_id]];
            } else {
                $stmt = $conn->prepare("
                    SELECT id, birth_date FROM animals
                    WHERE farm_id = ?
                    AND (status = 'Novilha' OR status = 'Bezerra' OR status = 'Bezerro')
                    AND is_active = 1
                ");
                $stmt->execute([$farm_id]);
                $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $registered = [];
            
            foreach ($animals as $animal) {
                $a_id = $animal['id'];
                
                // Verificar se já existe consumo para este dia
                $stmt = $conn->prepare("
                    SELECT id FROM heifer_daily_consumption
                    WHERE animal_id = ? AND consumption_date = ?
                ");
                $stmt->execute([$a_id, $date]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    continue; // Já existe, pular
                }
                
                // Calcular idade em dias
                $stmt = $conn->prepare("
                    SELECT DATEDIFF(?, birth_date) as age_days
                    FROM animals WHERE id = ?
                ");
                $stmt->execute([$date, $a_id]);
                $age = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$age || $age['age_days'] < 0) {
                    continue; // Animal ainda não nasceu nesta data
                }
                
                // Buscar fase baseada na idade
                $stmt = $conn->prepare("
                    SELECT * FROM heifer_phases
                    WHERE ? BETWEEN start_day AND end_day
                    AND active = 1
                    LIMIT 1
                ");
                $stmt->execute([$age['age_days']]);
                $phase = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($phase) {
                    // Registrar consumo baseado na fase
                    $stmt = $conn->prepare("
                        INSERT INTO heifer_daily_consumption
                        (animal_id, consumption_date, age_days, phase_id, 
                         milk_liters, concentrate_kg, roughage_kg, 
                         recorded_by, farm_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $a_id,
                        $date,
                        $age['age_days'],
                        $phase['id'],
                        $phase['avg_daily_milk_liters'] ?? 0,
                        $phase['avg_daily_concentrate_kg'] ?? 0,
                        $phase['avg_daily_roughage_kg'] ?? 0,
                        $user_id,
                        $farm_id
                    ]);
                    
                    $registered[] = [
                        'animal_id' => $a_id,
                        'date' => $date,
                        'phase' => $phase['phase_name'],
                        'consumption_id' => $conn->lastInsertId()
                    ];
                }
            }
            
            $response = [
                'success' => true,
                'message' => 'Consumo automático registrado com sucesso!',
                'registered' => $registered
            ];
            break;
        
        default:
            throw new Exception('Ação não reconhecida: ' . $action);
    }
    
} catch (Exception $e) {
    error_log("Erro na API heifer_management.php: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ];
} catch (Error $e) {
    error_log("Erro fatal na API heifer_management.php: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ];
}

// Limpar qualquer saída anterior e enviar JSON
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
?>

