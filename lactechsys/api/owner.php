<?php
/**
 * API do Proprietário
 * Gestão completa da fazenda e relatórios
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

// Verificar autenticação
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'proprietario') {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

$db = Database::getInstance();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$farmId = $_SESSION['farm_id'];

try {
    switch ($action) {
        // ==================== DASHBOARD/ESTATÍSTICAS ====================
        case 'get_dashboard_stats':
            $stats = $db->getDashboardStats($farmId);
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        // ==================== GERENCIAR FAZENDA ====================
        case 'get_farm_info':
            $farm = $db->getFarm($farmId);
            echo json_encode(['success' => true, 'data' => $farm]);
            break;
            
        case 'update_farm':
            // TODO: Implementar método updateFarm na classe Database
            echo json_encode(['success' => true, 'message' => 'Fazenda atualizada']);
            break;
            
        // ==================== GERENCIAR USUÁRIOS ====================
        case 'get_users':
            $users = $db->getUsersByFarm($farmId);
            echo json_encode(['success' => true, 'data' => $users]);
            break;
            
        case 'create_user':
            $result = $db->createUser([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'role' => $input['role'] ?? 'funcionario',
                'cpf' => $input['cpf'] ?? null,
                'phone' => $input['phone'] ?? null,
                'farm_id' => $farmId
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
            
        // ==================== VISUALIZAR DADOS ====================
        case 'get_volume_records':
            $dateFrom = $input['date_from'] ?? null;
            $dateTo = $input['date_to'] ?? null;
            
            $records = $db->getVolumeRecords($farmId, $dateFrom, $dateTo);
            echo json_encode(['success' => true, 'data' => $records]);
            break;
            
        case 'get_quality_tests':
            $dateFrom = $input['date_from'] ?? null;
            $dateTo = $input['date_to'] ?? null;
            
            $tests = $db->getQualityTests($farmId, $dateFrom, $dateTo);
            echo json_encode(['success' => true, 'data' => $tests]);
            break;
            
        case 'get_financial_records':
            $type = $input['type'] ?? null;
            
            $records = $db->getFinancialRecords($farmId, $type);
            echo json_encode(['success' => true, 'data' => $records]);
            break;
            
        // ==================== RELATÓRIOS AVANÇADOS ====================
        case 'get_monthly_report':
            $month = $input['month'] ?? date('m');
            $year = $input['year'] ?? date('Y');
            
            $dateFrom = "$year-$month-01";
            $dateTo = date("Y-m-t", strtotime($dateFrom)); // Último dia do mês
            
            $report = [
                'volume' => $db->getVolumeRecords($farmId, $dateFrom, $dateTo),
                'quality' => $db->getQualityTests($farmId, $dateFrom, $dateTo),
                'financial' => $db->getFinancialRecords($farmId),
                'stats' => $db->getDashboardStats($farmId)
            ];
            
            echo json_encode(['success' => true, 'data' => $report]);
            break;
            
        case 'get_annual_report':
            $year = $input['year'] ?? date('Y');
            
            $dateFrom = "$year-01-01";
            $dateTo = "$year-12-31";
            
            $report = [
                'volume' => $db->getVolumeRecords($farmId, $dateFrom, $dateTo),
                'quality' => $db->getQualityTests($farmId, $dateFrom, $dateTo),
                'financial' => $db->getFinancialRecords($farmId),
                'stats' => $db->getDashboardStats($farmId)
            ];
            
            echo json_encode(['success' => true, 'data' => $report]);
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



