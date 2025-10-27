<?php
/**
 * API de Ações - Lactech
 * Endpoint para ações específicas do sistema
 */

// Configurações de segurança
error_reporting(0);
ini_set('display_errors', 0);

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

// Verificar autenticação (modo teste - permitir acesso)
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['success' => false, 'error' => 'Acesso negado']);
//     exit;
// }

// Verificar se Database.class.php existe
$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Erro no servidor: Database.class.php não encontrado']);
    exit;
}

require_once $dbPath;

try {
    $db = Database::getInstance();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'dashboard':
            // Estatísticas do dashboard
            $stats = [];
            
            // Volume de leite hoje
            $results = $db->query("SELECT COALESCE(SUM(total_volume), 0) as total FROM volume_records WHERE DATE(record_date) = CURDATE() AND farm_id = 1");
            $stats['volume_today'] = $results[0]['total'] ?? 0;
            
            // Volume de leite este mês
            $results = $db->query("SELECT COALESCE(SUM(total_volume), 0) as total FROM volume_records WHERE MONTH(record_date) = MONTH(CURDATE()) AND YEAR(record_date) = YEAR(CURDATE()) AND farm_id = 1");
            $stats['volume_month'] = $results[0]['total'] ?? 0;
            
            // Média de gordura
            $results = $db->query("SELECT COALESCE(AVG(fat_content), 0) as avg FROM quality_tests WHERE MONTH(test_date) = MONTH(CURDATE()) AND YEAR(test_date) = YEAR(CURDATE()) AND farm_id = 1");
            $stats['avg_fat'] = round($results[0]['avg'] ?? 0, 2);
            
            // Média de proteína
            $results = $db->query("SELECT COALESCE(AVG(protein_content), 0) as avg FROM quality_tests WHERE MONTH(test_date) = MONTH(CURDATE()) AND YEAR(test_date) = YEAR(CURDATE()) AND farm_id = 1");
            $stats['avg_protein'] = round($results[0]['avg'] ?? 0, 2);
            
            // Pagamentos pendentes
            $results = $db->query("SELECT COUNT(*) as total FROM financial_records WHERE type = 'receita' AND farm_id = 1");
            $stats['pending_payments'] = $results[0]['total'] ?? 0;
            
            // Usuários ativos
            $results = $db->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1 AND farm_id = 1");
            $stats['active_users'] = $results[0]['total'] ?? 0;
            
            // Total de animais
            $results = $db->query("SELECT COUNT(*) as total FROM animals WHERE is_active = 1 AND farm_id = 1");
            $stats['total_animals'] = $results[0]['total'] ?? 0;
            
            // Gestações ativas
            $results = $db->query("SELECT COUNT(*) as total FROM pregnancy_controls WHERE expected_birth >= CURDATE() AND farm_id = 1");
            $stats['active_pregnancies'] = $results[0]['total'] ?? 0;
            
            // Alertas ativos
            $results = $db->query("SELECT COUNT(*) as total FROM health_alerts WHERE is_resolved = 0 AND farm_id = 1");
            $stats['active_alerts'] = $results[0]['total'] ?? 0;
            
            // Buscar nome da fazenda do banco
            $results = $db->query("SELECT name FROM farms WHERE id = 1");
            $farmName = $results[0]['name'] ?? 'Fazenda';
            
            echo json_encode([
                'success' => true,
                'data' => $stats,
                'farm_name' => $farmName
            ]);
            break;
            
        case 'urgent_actions':
            // Ações urgentes
            $urgentActions = [];
            
            // Solicitações de senha pendentes
            $results = $db->query("SELECT COUNT(*) as total FROM password_requests WHERE is_used = 0 AND expires_at > NOW()");
            $passwordRequests = $results[0]['total'] ?? 0;
            
            if ($passwordRequests > 0) {
                $urgentActions[] = [
                    'type' => 'password_request',
                    'message' => "$passwordRequests solicitação(ões) de senha pendente(s)",
                    'priority' => 'high'
                ];
            }
            
            // Testes de qualidade pendentes
            $results = $db->query("SELECT COUNT(*) as total FROM quality_tests WHERE test_type = 'qualidade_leite' AND farm_id = 1");
            $qualityTests = $results[0]['total'] ?? 0;
            
            if ($qualityTests > 0) {
                $urgentActions[] = [
                    'type' => 'quality_test',
                    'message' => "$qualityTests teste(s) de qualidade pendente(s)",
                    'priority' => 'medium'
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $urgentActions
            ]);
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

