<?php
/**
 * API do Funcionário
 * Operações diárias: registrar coleta, testes de qualidade
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
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['funcionario'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

$db = Database::getInstance();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$farmId = $_SESSION['farm_id'];
$userId = $_SESSION['user_id'];

try {
    switch ($action) {
        // ==================== REGISTRAR COLETA DE LEITE ====================
        case 'add_volume':
            $result = $db->addVolumeRecord([
                'farm_id' => $farmId,
                'producer_id' => $input['producer_id'] ?? null,
                'volume' => $input['volume'],
                'collection_date' => $input['collection_date'] ?? date('Y-m-d'),
                'period' => $input['period'] ?? 'manha',
                'temperature' => $input['temperature'] ?? null,
                'recorded_by' => $userId
            ]);
            echo json_encode($result);
            break;
            
        case 'get_my_volume_records':
            $dateFrom = $input['date_from'] ?? date('Y-m-01'); // Primeiro dia do mês
            $dateTo = $input['date_to'] ?? date('Y-m-d');
            
            $records = $db->getVolumeRecords($farmId, $dateFrom, $dateTo);
            echo json_encode(['success' => true, 'data' => $records]);
            break;
            
        // ==================== REGISTRAR TESTE DE QUALIDADE ====================
        case 'add_quality_test':
            // Funcionários podem adicionar testes de qualidade
            if ($_SESSION['user_role'] !== 'funcionario') {
                echo json_encode(['success' => false, 'error' => 'Apenas funcionários podem registrar testes de qualidade']);
                exit;
            }
            
            $result = $db->addQualityTest([
                'farm_id' => $farmId,
                'producer_id' => $input['producer_id'] ?? null,
                'test_date' => $input['test_date'] ?? date('Y-m-d'),
                'fat_percentage' => $input['fat_percentage'] ?? null,
                'protein_percentage' => $input['protein_percentage'] ?? null,
                'lactose_percentage' => $input['lactose_percentage'] ?? null,
                'ccs' => $input['ccs'] ?? null,
                'cbt' => $input['cbt'] ?? null,
                'temperature' => $input['temperature'] ?? null,
                'ph' => $input['ph'] ?? null,
                'tested_by' => $userId
            ]);
            echo json_encode($result);
            break;
            
        case 'get_my_quality_tests':
            $dateFrom = $input['date_from'] ?? date('Y-m-01');
            $dateTo = $input['date_to'] ?? date('Y-m-d');
            
            $tests = $db->getQualityTests($farmId, $dateFrom, $dateTo);
            echo json_encode(['success' => true, 'data' => $tests]);
            break;
            
        // ==================== VISUALIZAR ESTATÍSTICAS SIMPLES ====================
        case 'get_today_stats':
            $today = date('Y-m-d');
            
            $volumeRecords = $db->getVolumeRecords($farmId, $today, $today);
            $totalVolume = array_sum(array_column($volumeRecords, 'volume'));
            
            $stats = [
                'volume_today' => $totalVolume,
                'records_count' => count($volumeRecords)
            ];
            
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        // ==================== PERFIL DO USUÁRIO ====================
        case 'get_my_profile':
            $user = $db->getUser($userId);
            echo json_encode(['success' => true, 'data' => $user]);
            break;
            
        case 'update_my_profile':
            // Remover campos que não podem ser alterados
            unset($input['role'], $input['farm_id'], $input['email']);
            
            $result = $db->updateUser($userId, $input);
            echo json_encode($result);
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



