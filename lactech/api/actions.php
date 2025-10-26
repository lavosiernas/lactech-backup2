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

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

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
            $stmt = $db->prepare("SELECT COALESCE(SUM(volume), 0) as total FROM volume_records WHERE DATE(collection_date) = CURDATE()");
            $stmt->execute();
            $stats['volume_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Volume de leite este mês
            $stmt = $db->prepare("SELECT COALESCE(SUM(volume), 0) as total FROM volume_records WHERE MONTH(collection_date) = MONTH(CURDATE()) AND YEAR(collection_date) = YEAR(CURDATE())");
            $stmt->execute();
            $stats['volume_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Média de gordura
            $stmt = $db->prepare("SELECT COALESCE(AVG(fat_percentage), 0) as avg FROM quality_tests WHERE MONTH(test_date) = MONTH(CURDATE()) AND YEAR(test_date) = YEAR(CURDATE())");
            $stmt->execute();
            $stats['avg_fat'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg'], 2);
            
            // Média de proteína
            $stmt = $db->prepare("SELECT COALESCE(AVG(protein_percentage), 0) as avg FROM quality_tests WHERE MONTH(test_date) = MONTH(CURDATE()) AND YEAR(test_date) = YEAR(CURDATE())");
            $stmt->execute();
            $stats['avg_protein'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg'], 2);
            
            // Pagamentos pendentes
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM financial_records WHERE type = 'income' AND status = 'pending'");
            $stmt->execute();
            $stats['pending_payments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Usuários ativos
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE active = 1");
            $stmt->execute();
            $stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total de animais
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM animals WHERE status = 'active'");
            $stmt->execute();
            $stats['total_animals'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Gestações ativas
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM animals WHERE status = 'pregnant'");
            $stmt->execute();
            $stats['active_pregnancies'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Alertas ativos
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE is_read = 0");
            $stmt->execute();
            $stats['active_alerts'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo json_encode([
                'success' => true,
                'data' => $stats,
                'farm_name' => 'Lagoa Do Mato'
            ]);
            break;
            
        case 'urgent_actions':
            // Ações urgentes
            $urgentActions = [];
            
            // Solicitações de senha pendentes
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM password_requests WHERE status = 'pending'");
            $stmt->execute();
            $passwordRequests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($passwordRequests > 0) {
                $urgentActions[] = [
                    'type' => 'password_request',
                    'message' => "$passwordRequests solicitação(ões) de senha pendente(s)",
                    'priority' => 'high'
                ];
            }
            
            // Testes de qualidade pendentes
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM quality_tests WHERE status = 'pending'");
            $stmt->execute();
            $qualityTests = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
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

