<?php
/**
 * API SIMPLES - Controle de Novilhas
 * Usa apenas queries básicas do banco atual
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

@session_start();

require_once '../includes/config.php';
require_once '../includes/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $action = $_GET['action'] ?? 'get_heifers_list';
    $farm_id = $_SESSION['farm_id'] ?? 1;
    
    if ($action === 'get_heifers_list') {
        // Buscar APENAS novilhas dos animais
        $sql = "SELECT 
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
            END AS current_phase
        FROM animals a
        WHERE a.farm_id = :farm_id 
        AND (a.status = 'Novilha' OR a.status = 'Bezerra' OR a.status = 'Bezerro')
        AND a.is_active = 1
        ORDER BY a.birth_date DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute(['farm_id' => $farm_id]);
        $heifers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Adicionar custos MANUALMENTE (se tabela existir)
        foreach ($heifers as &$heifer) {
            try {
                $costSql = "SELECT COALESCE(SUM(cost_amount), 0) as total_cost, COUNT(*) as total_records
                           FROM heifer_costs 
                           WHERE animal_id = :animal_id";
                $costStmt = $conn->prepare($costSql);
                $costStmt->execute(['animal_id' => $heifer['id']]);
                $costs = $costStmt->fetch(PDO::FETCH_ASSOC);
                
                $heifer['total_cost'] = $costs['total_cost'] ?? 0;
                $heifer['total_records'] = $costs['total_records'] ?? 0;
            } catch (Exception $e) {
                // Se tabela não existir, deixa zero
                $heifer['total_cost'] = 0;
                $heifer['total_records'] = 0;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $heifers,
            'count' => count($heifers)
        ]);
        
    } else if ($action === 'get_heifer_details') {
        $animal_id = $_GET['animal_id'] ?? null;
        
        if (!$animal_id) {
            throw new Exception('ID do animal não fornecido');
        }
        
        // Buscar animal
        $sql = "SELECT 
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
                ELSE 'Sem fase'
            END AS current_phase
        FROM animals a
        WHERE a.id = :animal_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute(['animal_id' => $animal_id]);
        $animal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$animal) {
            throw new Exception('Animal não encontrado');
        }
        
        // Buscar custos por categoria
        $costs_by_category = [];
        $recent_costs = [];
        $total_cost = 0;
        $total_records = 0;
        
        try {
            $costSql = "SELECT 
                cost_category AS category_type,
                cost_category AS category_name,
                SUM(cost_amount) as total_cost,
                COUNT(*) as total_records
            FROM heifer_costs
            WHERE animal_id = :animal_id
            GROUP BY cost_category";
            
            $stmt = $conn->prepare($costSql);
            $stmt->execute(['animal_id' => $animal_id]);
            $costs_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $total_cost = array_sum(array_column($costs_by_category, 'total_cost'));
            $total_records = array_sum(array_column($costs_by_category, 'total_records'));
            
            // Buscar histórico
            $historySql = "SELECT * FROM heifer_costs WHERE animal_id = :animal_id ORDER BY cost_date DESC LIMIT 20";
            $stmt = $conn->prepare($historySql);
            $stmt->execute(['animal_id' => $animal_id]);
            $recent_costs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            // Tabela não existe ainda
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'animal' => $animal,
                'total_cost' => $total_cost,
                'total_records' => $total_records,
                'avg_daily_cost' => $animal['age_days'] > 0 ? $total_cost / $animal['age_days'] : 0,
                'costs_by_category' => $costs_by_category,
                'recent_costs' => $recent_costs
            ]
        ]);
        
    } else {
        throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ]);
}
?>

