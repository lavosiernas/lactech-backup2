<?php
/**
 * API: Heifer Management System - COMPATÍVEL COM BANCO ATUAL
 * Sistema completo de controle de custos de novilhas
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

ob_start();
ob_clean();

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
            $farm_id = $_SESSION['farm_id'] ?? 1;
            
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
        // LISTAR CATEGORIAS DE CUSTOS (antigas)
        // ==========================================
        case 'get_cost_categories':
            $categories = [
                ['id' => 1, 'category_name' => 'Alimentação', 'category_type' => 'Alimentação'],
                ['id' => 2, 'category_name' => 'Medicamentos', 'category_type' => 'Sanidade'],
                ['id' => 3, 'category_name' => 'Vacinas', 'category_type' => 'Sanidade'],
                ['id' => 4, 'category_name' => 'Manejo', 'category_type' => 'Manejo'],
                ['id' => 5, 'category_name' => 'Transporte', 'category_type' => 'Manejo'],
                ['id' => 6, 'category_name' => 'Outros', 'category_type' => 'Outros']
            ];
            
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
            
            $required = ['animal_id', 'cost_date', 'unit_price'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    throw new Exception("Campo obrigatório: {$field}");
                }
            }
            
            $farm_id = $_SESSION['farm_id'] ?? 1;
            $user_id = $_SESSION['user_id'] ?? 1;
            
            // Determinar categoria
            $cost_category = 'Outros';
            if (isset($data['category_id'])) {
                $categories_map = [
                    1 => 'Alimentação', 2 => 'Alimentação', 3 => 'Alimentação', 4 => 'Alimentação',
                    5 => 'Alimentação', 6 => 'Alimentação', 7 => 'Alimentação', 8 => 'Manejo',
                    9 => 'Medicamentos', 10 => 'Vacinas', 11 => 'Medicamentos', 12 => 'Medicamentos',
                    13 => 'Manejo', 14 => 'Manejo', 15 => 'Transporte', 16 => 'Outros', 17 => 'Outros', 18 => 'Outros'
                ];
                $cost_category = $categories_map[$data['category_id']] ?? 'Outros';
            }
            
            // Calcular custo total
            $quantity = $data['quantity'] ?? 1;
            $cost_amount = $quantity * $data['unit_price'];
            
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
        
        default:
            throw new Exception('Ação não reconhecida: ' . $action);
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

