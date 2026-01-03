<?php
/**
 * API para verificar novas respostas de pesquisa
 * Retorna JSON com contagem de novas respostas desde última verificação
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/SecurityHelpers.php';

// Headers de segurança
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Verificar autenticação
define('ADMIN_SESSION_KEY', 'safenode_survey_admin_logged_in');

if (!isset($_SESSION[ADMIN_SESSION_KEY]) || !$_SESSION[ADMIN_SESSION_KEY]) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

try {
    $db = getSafeNodeDatabase();
    if (!$db) {
        throw new Exception('Erro ao conectar ao banco de dados');
    }
    
    // Obter timestamp da última verificação (da sessão ou parâmetro)
    $lastCheck = $_SESSION['survey_last_check'] ?? null;
    $lastCheckTimestamp = $lastCheck ? date('Y-m-d H:i:s', $lastCheck) : date('Y-m-d H:i:s', strtotime('-1 hour'));
    
    // Buscar novas respostas desde última verificação
    $stmt = $db->prepare("
        SELECT COUNT(*) as new_count, MAX(created_at) as latest_date
        FROM safenode_survey_responses
        WHERE created_at > ?
    ");
    $stmt->execute([$lastCheckTimestamp]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $newCount = (int)($result['new_count'] ?? 0);
    $latestDate = $result['latest_date'] ?? null;
    
    // Buscar informações das últimas respostas
    $recentResponses = [];
    if ($newCount > 0) {
        $stmt = $db->prepare("
            SELECT id, email, created_at, biggest_pain
            FROM safenode_survey_responses
            WHERE created_at > ?
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$lastCheckTimestamp]);
        $recentResponses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Sanitizar dados
        foreach ($recentResponses as &$response) {
            $response['id'] = (int)$response['id'];
            $response['email'] = htmlspecialchars($response['email'], ENT_QUOTES, 'UTF-8');
            $response['biggest_pain'] = htmlspecialchars(substr($response['biggest_pain'], 0, 100), ENT_QUOTES, 'UTF-8');
        }
    }
    
    // Atualizar timestamp da última verificação
    $_SESSION['survey_last_check'] = time();
    
    echo json_encode([
        'success' => true,
        'new_count' => $newCount,
        'latest_date' => $latestDate,
        'recent_responses' => $recentResponses,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log("Survey Check New Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao verificar novas respostas']);
}




