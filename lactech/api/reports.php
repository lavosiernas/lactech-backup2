<?php
/**
 * API: Relatórios
 * Endpoint para gerar relatórios em Excel e PDF
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

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // Obter dados de entrada
    $input = [];
    if ($method === 'POST') {
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
        // LISTAR TIPOS DE RELATÓRIOS
        // ==========================================
        case 'list_types':
            $types = [
                [
                    'id' => 'production',
                    'name' => 'Produção de Leite',
                    'description' => 'Relatório de produção diária, semanal e mensal',
                    'icon' => 'milk'
                ],
                [
                    'id' => 'animals',
                    'name' => 'Rebanho',
                    'description' => 'Listagem completa de animais com status e informações',
                    'icon' => 'cow'
                ],
                [
                    'id' => 'health',
                    'name' => 'Sanitário',
                    'description' => 'Registros de vacinações, medicamentos e tratamentos',
                    'icon' => 'health'
                ],
                [
                    'id' => 'reproduction',
                    'name' => 'Reprodutivo',
                    'description' => 'Inseminações, prenhezes, partos e cios',
                    'icon' => 'reproduction'
                ],
                [
                    'id' => 'feeding',
                    'name' => 'Alimentação',
                    'description' => 'Registros de alimentação e custos',
                    'icon' => 'feeding'
                ],
                [
                    'id' => 'financial',
                    'name' => 'Financeiro',
                    'description' => 'Custos, receitas e análise financeira',
                    'icon' => 'financial'
                ],
                [
                    'id' => 'summary',
                    'name' => 'Resumo Geral',
                    'description' => 'Visão geral de todos os indicadores',
                    'icon' => 'summary'
                ]
            ];
            
            sendResponse($types);
            break;
            
        // ==========================================
        // OBTER DADOS DO RELATÓRIO
        // ==========================================
        case 'get_data':
            $report_type = $input['report_type'] ?? null;
            $date_from = $input['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
            $date_to = $input['date_to'] ?? date('Y-m-d');
            $filters = $input['filters'] ?? [];
            
            if (!$report_type) {
                sendResponse(null, 'Tipo de relatório não especificado', 400);
            }
            
            $data = [];
            
            switch ($report_type) {
                case 'production':
                    $stmt = $conn->prepare("
                        SELECT 
                            DATE(mp.production_date) as date,
                            COUNT(DISTINCT mp.animal_id) as animals_count,
                            SUM(mp.volume) as total_volume,
                            AVG(mp.volume) as avg_volume,
                            AVG(mp.fat_content) as avg_fat,
                            AVG(mp.protein_content) as avg_protein,
                            AVG(mp.somatic_cells) as avg_somatic_cells
                        FROM milk_production mp
                        WHERE mp.farm_id = ? 
                        AND DATE(mp.production_date) BETWEEN ? AND ?
                        GROUP BY DATE(mp.production_date)
                        ORDER BY date DESC
                    ");
                    $stmt->execute([$farm_id, $date_from, $date_to]);
                    $data['daily'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Resumo
                    $stmt = $conn->prepare("
                        SELECT 
                            COUNT(DISTINCT mp.animal_id) as total_animals,
                            SUM(mp.volume) as total_volume,
                            AVG(mp.volume) as avg_volume,
                            AVG(mp.fat_content) as avg_fat,
                            AVG(mp.protein_content) as avg_protein
                        FROM milk_production mp
                        WHERE mp.farm_id = ? 
                        AND DATE(mp.production_date) BETWEEN ? AND ?
                    ");
                    $stmt->execute([$farm_id, $date_from, $date_to]);
                    $data['summary'] = $stmt->fetch(PDO::FETCH_ASSOC);
                    break;
                    
                case 'animals':
                    $where = ["a.farm_id = ?"];
                    $params = [$farm_id];
                    
                    if (!empty($filters['status'])) {
                        $where[] = "a.status = ?";
                        $params[] = $filters['status'];
                    }
                    
                    if (!empty($filters['breed'])) {
                        $where[] = "a.breed = ?";
                        $params[] = $filters['breed'];
                    }
                    
                    $whereClause = implode(' AND ', $where);
                    
                    $stmt = $conn->prepare("
                        SELECT 
                            a.*,
                            ag.group_name,
                            ag.group_code,
                            (SELECT COUNT(*) FROM milk_production mp WHERE mp.animal_id = a.id AND mp.production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as production_records_30d,
                            (SELECT AVG(volume) FROM milk_production mp WHERE mp.animal_id = a.id AND mp.production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as avg_production_30d
                        FROM animals a
                        LEFT JOIN animal_groups ag ON a.current_group_id = ag.id
                        WHERE $whereClause
                        ORDER BY a.animal_number ASC
                    ");
                    $stmt->execute($params);
                    $data['animals'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                    
                case 'health':
                    $stmt = $conn->prepare("
                        SELECT 
                            hr.*,
                            a.animal_number,
                            a.name as animal_name
                        FROM health_records hr
                        LEFT JOIN animals a ON hr.animal_id = a.id
                        WHERE hr.farm_id = ? 
                        AND hr.record_date BETWEEN ? AND ?
                        ORDER BY hr.record_date DESC
                    ");
                    $stmt->execute([$farm_id, $date_from, $date_to]);
                    $data['records'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Estatísticas
                    $stmt = $conn->prepare("
                        SELECT 
                            record_type,
                            COUNT(*) as count,
                            SUM(cost) as total_cost
                        FROM health_records
                        WHERE farm_id = ? 
                        AND record_date BETWEEN ? AND ?
                        GROUP BY record_type
                    ");
                    $stmt->execute([$farm_id, $date_from, $date_to]);
                    $data['stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                    
                case 'reproduction':
                    // Inseminações
                    $stmt = $conn->prepare("
                        SELECT 
                            i.*,
                            a.animal_number,
                            a.name as animal_name,
                            b.name as bull_name
                        FROM inseminations i
                        LEFT JOIN animals a ON i.animal_id = a.id
                        LEFT JOIN bulls b ON i.bull_id = b.id
                        WHERE i.farm_id = ? 
                        AND i.insemination_date BETWEEN ? AND ?
                        ORDER BY i.insemination_date DESC
                    ");
                    $stmt->execute([$farm_id, $date_from, $date_to]);
                    $data['inseminations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Prenhezes
                    $stmt = $conn->prepare("
                        SELECT 
                            pc.*,
                            a.animal_number,
                            a.name as animal_name
                        FROM pregnancy_controls pc
                        LEFT JOIN animals a ON pc.animal_id = a.id
                        WHERE pc.farm_id = ? 
                        AND pc.test_date BETWEEN ? AND ?
                        ORDER BY pc.test_date DESC
                    ");
                    $stmt->execute([$farm_id, $date_from, $date_to]);
                    $data['pregnancies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Partos
                    $stmt = $conn->prepare("
                        SELECT 
                            b.*,
                            a.animal_number,
                            a.name as animal_name
                        FROM births b
                        LEFT JOIN animals a ON b.animal_id = a.id
                        WHERE b.farm_id = ? 
                        AND b.birth_date BETWEEN ? AND ?
                        ORDER BY b.birth_date DESC
                    ");
                    $stmt->execute([$farm_id, $date_from, $date_to]);
                    $data['births'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                    
                case 'feeding':
                    $stmt = $conn->prepare("
                        SELECT 
                            fr.*,
                            a.animal_number,
                            a.name as animal_name
                        FROM feed_records fr
                        LEFT JOIN animals a ON fr.animal_id = a.id
                        WHERE fr.farm_id = ? 
                        AND fr.feed_date BETWEEN ? AND ?
                        ORDER BY fr.feed_date DESC
                    ");
                    $stmt->execute([$farm_id, $date_from, $date_to]);
                    $data['records'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Resumo
                    $stmt = $conn->prepare("
                        SELECT 
                            SUM(concentrate_kg) as total_concentrate,
                            SUM(roughage_kg) as total_roughage,
                            SUM(silage_kg) as total_silage,
                            SUM(hay_kg) as total_hay,
                            SUM(total_cost) as total_cost,
                            COUNT(DISTINCT animal_id) as animals_count
                        FROM feed_records
                        WHERE farm_id = ? 
                        AND feed_date BETWEEN ? AND ?
                    ");
                    $stmt->execute([$farm_id, $date_from, $date_to]);
                    $data['summary'] = $stmt->fetch(PDO::FETCH_ASSOC);
                    break;
                    
                case 'summary':
                    // Produção
                    $stmt = $conn->prepare("
                        SELECT 
                            SUM(volume) as total_volume,
                            AVG(volume) as avg_volume,
                            COUNT(DISTINCT animal_id) as animals_count
                        FROM milk_production
                        WHERE farm_id = ? 
                        AND production_date BETWEEN ? AND ?
                    ");
                    $stmt->execute([$farm_id, $date_from, $date_to]);
                    $data['production'] = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Animais
                    $stmt = $conn->prepare("
                        SELECT 
                            COUNT(*) as total,
                            COUNT(CASE WHEN status = 'Lactante' THEN 1 END) as lactating,
                            COUNT(CASE WHEN status = 'Seca' THEN 1 END) as dry,
                            COUNT(CASE WHEN health_status = 'Doente' THEN 1 END) as sick
                        FROM animals
                        WHERE farm_id = ? AND (is_active = 1 OR is_active IS NULL)
                    ");
                    $stmt->execute([$farm_id]);
                    $data['animals'] = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Saúde
                    $stmt = $conn->prepare("
                        SELECT 
                            COUNT(*) as total_records,
                            SUM(cost) as total_cost
                        FROM health_records
                        WHERE farm_id = ? 
                        AND record_date BETWEEN ? AND ?
                    ");
                    $stmt->execute([$farm_id, $date_from, $date_to]);
                    $data['health'] = $stmt->fetch(PDO::FETCH_ASSOC);
                    break;
            }
            
            sendResponse($data);
            break;
            
        // ==========================================
        // EXPORTAR PARA EXCEL
        // ==========================================
        case 'export_excel':
            $report_type = $input['report_type'] ?? null;
            $date_from = $input['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
            $date_to = $input['date_to'] ?? date('Y-m-d');
            $filters = is_array($input['filters'] ?? []) ? $input['filters'] : json_decode($input['filters'] ?? '[]', true);
            
            if (!$report_type) {
                sendResponse(null, 'Tipo de relatório não especificado', 400);
            }
            
            // Passar dados para o script de exportação
            $_GET['report_type'] = $report_type;
            $_GET['date_from'] = $date_from;
            $_GET['date_to'] = $date_to;
            $_GET['filters'] = json_encode($filters);
            
            include __DIR__ . '/reports_export_excel.php';
            exit;
            
        // ==========================================
        // EXPORTAR PARA PDF
        // ==========================================
        case 'export_pdf':
            $report_type = $input['report_type'] ?? null;
            $date_from = $input['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
            $date_to = $input['date_to'] ?? date('Y-m-d');
            $filters = is_array($input['filters'] ?? []) ? $input['filters'] : json_decode($input['filters'] ?? '[]', true);
            
            if (!$report_type) {
                sendResponse(null, 'Tipo de relatório não especificado', 400);
            }
            
            // Passar dados para o script de exportação
            $_GET['report_type'] = $report_type;
            $_GET['date_from'] = $date_from;
            $_GET['date_to'] = $date_to;
            $_GET['filters'] = json_encode($filters);
            
            include __DIR__ . '/reports_export_pdf.php';
            exit;
            
        default:
            sendResponse(null, 'Ação não reconhecida', 400);
            break;
    }
    
} catch (Exception $e) {
    error_log("Reports API Error: " . $e->getMessage());
    sendResponse(null, 'Erro interno do servidor: ' . $e->getMessage(), 500);
}

