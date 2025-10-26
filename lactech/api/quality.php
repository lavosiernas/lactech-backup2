<?php
/**
 * API de Qualidade - Lactech
 * Endpoint para testes de qualidade
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
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if ($action === 'select') {
                // Listar testes de qualidade
                $limit = $_GET['limit'] ?? 50;
                $dateFrom = $_GET['date_from'] ?? null;
                $dateTo = $_GET['date_to'] ?? null;
                
                $query = "SELECT 
                    qt.id,
                    qt.test_date,
                    qt.fat_percentage,
                    qt.protein_percentage,
                    qt.lactose_percentage,
                    qt.ccs,
                    qt.cbt,
                    qt.temperature,
                    qt.ph,
                    qt.status,
                    qt.created_at,
                    u.name as tested_by_name,
                    p.name as producer_name
                    FROM quality_tests qt
                    LEFT JOIN users u ON qt.tested_by = u.id
                    LEFT JOIN producers p ON qt.producer_id = p.id
                    WHERE 1=1";
                
                $params = [];
                
                if ($dateFrom) {
                    $query .= " AND DATE(qt.test_date) >= ?";
                    $params[] = $dateFrom;
                }
                
                if ($dateTo) {
                    $query .= " AND DATE(qt.test_date) <= ?";
                    $params[] = $dateTo;
                }
                
                $query .= " ORDER BY qt.test_date DESC LIMIT ?";
                $params[] = (int)$limit;
                
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $tests
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Ação inválida']);
            }
            break;
            
        case 'POST':
            // Criar teste de qualidade
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            $stmt = $db->prepare("INSERT INTO quality_tests (producer_id, test_date, fat_percentage, protein_percentage, lactose_percentage, ccs, cbt, temperature, ph, status, tested_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $data['producer_id'] ?? null,
                $data['test_date'],
                $data['fat_percentage'] ?? null,
                $data['protein_percentage'] ?? null,
                $data['lactose_percentage'] ?? null,
                $data['ccs'] ?? null,
                $data['cbt'] ?? null,
                $data['temperature'] ?? null,
                $data['ph'] ?? null,
                $data['status'] ?? 'pending',
                $_SESSION['user_id']
            ]);
            
            $testId = $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $testId,
                    'test_date' => $data['test_date']
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
?>

