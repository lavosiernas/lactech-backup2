<?php
/**
 * API do Gerente
 * Todas as operações administrativas da fazenda
 */

// Desabilitar exibição de erros em produção
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sempre retornar JSON
header('Content-Type: application/json');

// Verificar se o arquivo existe
$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Erro no servidor: Database.class.php não encontrado em: ' . $dbPath]);
    exit;
}

require_once $dbPath;

// Verificar autenticação (modo teste - permitir acesso)
// if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'gerente') {
//     echo json_encode(['success' => false, 'error' => 'Acesso negado']);
//     exit;
// }

try {
    $db = Database::getInstance();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    // Sistema para fazenda única: Lagoa Do Mato
    // Não precisa mais passar farm_id - sempre será ID = 1
    switch ($action) {
        // ==================== DASHBOARD ====================
        case 'get_dashboard_stats':
            $stats = $db->getDashboardStats(); // Não precisa mais de farmId
            echo json_encode(['success' => true, 'data' => $stats, 'farm_name' => Database::FARM_NAME]);
            break;
            
        // ==================== USUÁRIOS ====================
        case 'get_users':
            $users = $db->getUsersByFarm(Database::FARM_ID);
            echo json_encode(['success' => true, 'data' => $users]);
            break;
            
        case 'get_user':
            $userId = $input['user_id'] ?? null;
            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'ID do usuário não fornecido']);
                exit;
            }
            
            $user = $db->getUser($userId);
            echo json_encode(['success' => true, 'data' => $user]);
            break;
            
        case 'create_user':
            $result = $db->createUser([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'role' => $input['role'] ?? 'funcionario',
                'cpf' => $input['cpf'] ?? null,
                'phone' => $input['phone'] ?? null
                // farm_id não é mais necessário - sempre Lagoa Do Mato
            ]);
            echo json_encode($result);
            break;
            
        case 'update_user':
            $userId = $input['user_id'] ?? null;
            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'ID do usuário não fornecido']);
                exit;
            }
            
            unset($input['user_id'], $input['action']);
            $result = $db->updateUser($userId, $input);
            echo json_encode($result);
            break;
            
        case 'deactivate_user':
            $userId = $input['user_id'] ?? null;
            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'ID do usuário não fornecido']);
                exit;
            }
            
            $result = $db->deactivateUser($userId);
            echo json_encode($result);
            break;
            
        // ==================== COLETA DE LEITE ====================
        case 'add_volume':
            $result = $db->addVolumeRecord([
                // farm_id não é mais necessário - sempre Lagoa Do Mato
                'producer_id' => $input['producer_id'] ?? null,
                'volume' => $input['volume'],
                'collection_date' => $input['collection_date'],
                'period' => $input['period'] ?? 'manha',
                'temperature' => $input['temperature'] ?? null,
                'recorded_by' => $_SESSION['user_id']
            ]);
            echo json_encode($result);
            break;
            
        case 'get_volume_records':
            $dateFrom = $input['date_from'] ?? null;
            $dateTo = $input['date_to'] ?? null;
            
            $records = $db->getVolumeRecords($dateFrom, $dateTo); // Sem farmId
            echo json_encode(['success' => true, 'data' => $records]);
            break;
            
        // ==================== TESTES DE QUALIDADE ====================
        case 'add_quality_test':
            $result = $db->addQualityTest([
                // farm_id não é mais necessário - sempre Lagoa Do Mato
                'producer_id' => $input['producer_id'] ?? null,
                'test_date' => $input['test_date'],
                'fat_percentage' => $input['fat_percentage'] ?? null,
                'protein_percentage' => $input['protein_percentage'] ?? null,
                'lactose_percentage' => $input['lactose_percentage'] ?? null,
                'ccs' => $input['ccs'] ?? null,
                'cbt' => $input['cbt'] ?? null,
                'temperature' => $input['temperature'] ?? null,
                'ph' => $input['ph'] ?? null,
                'tested_by' => $_SESSION['user_id']
            ]);
            echo json_encode($result);
            break;
            
        case 'get_quality_tests':
            $dateFrom = $input['date_from'] ?? null;
            $dateTo = $input['date_to'] ?? null;
            
            $tests = $db->getQualityTests($dateFrom, $dateTo); // Sem farmId
            echo json_encode(['success' => true, 'data' => $tests]);
            break;
            
        // ==================== FINANCEIRO ====================
        case 'add_financial_record':
            $result = $db->addFinancialRecord([
                // farm_id não é mais necessário - sempre Lagoa Do Mato
                'type' => $input['type'], // 'income' ou 'expense'
                'amount' => $input['amount'],
                'description' => $input['description'] ?? null,
                'due_date' => $input['due_date'] ?? null,
                'payment_date' => $input['payment_date'] ?? null,
                'status' => $input['status'] ?? 'pending',
                'created_by' => $_SESSION['user_id']
            ]);
            echo json_encode($result);
            break;
            
        case 'get_financial_records':
            $type = $input['type'] ?? null; // 'income', 'expense' ou null (todos)
            
            $records = $db->getFinancialRecords($type); // Sem farmId
            echo json_encode(['success' => true, 'data' => $records]);
            break;
            
        // ==================== RELATÓRIOS ====================
        case 'generate_report':
            $reportType = $input['report_type'] ?? 'volume';
            $dateFrom = $input['date_from'] ?? date('Y-m-01');
            $dateTo = $input['date_to'] ?? date('Y-m-d');
            
            $reportData = [];
            
            switch ($reportType) {
                case 'volume':
                    $reportData = $db->getVolumeRecords($farmId, $dateFrom, $dateTo);
                    break;
                case 'quality':
                    $reportData = $db->getQualityTests($farmId, $dateFrom, $dateTo);
                    break;
                case 'financial':
                    $reportData = $db->getFinancialRecords($farmId);
                    break;
            }
            
            echo json_encode(['success' => true, 'data' => $reportData, 'report_type' => $reportType]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
?>

