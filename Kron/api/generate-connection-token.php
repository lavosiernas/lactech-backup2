<?php
/**
 * KRON API - Gerar Token de Conexão
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/KronConnectionManager.php';
require_once __DIR__ . '/../includes/KronQRGenerator.php';

// Verificar se está logado
if (!isset($_SESSION['kron_logged_in']) || $_SESSION['kron_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$kronUserId = $_SESSION['kron_user_id'] ?? null;

if (!$kronUserId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Usuário não identificado']);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Obter sistema
$systemName = $_POST['system_name'] ?? $_GET['system_name'] ?? '';

if (!in_array($systemName, ['safenode', 'lactech'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Sistema inválido']);
    exit;
}

try {
    $connectionManager = new KronConnectionManager();
    
    // Verificar se precisa gerar QR Code de um token existente
    $generateQR = isset($_POST['generate_qr']) && $_POST['generate_qr'] === '1';
    $existingToken = $_POST['token'] ?? null;
    
    if ($generateQR && $existingToken) {
        // Buscar token existente no banco
        $pdo = getKronDatabase();
        $stmt = $pdo->prepare("
            SELECT t.*, u.id as kron_user_id
            FROM kron_connection_tokens t
            INNER JOIN kron_users u ON t.kron_user_id = u.id
            WHERE t.token = ? AND t.system_name = ? AND t.status = 'pending'
        ");
        $stmt->execute([$existingToken, $systemName]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tokenData) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Token não encontrado']);
            exit;
        }
        
        // Verificar expiração
        if (strtotime($tokenData['expires_at']) < time()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Token expirado']);
            exit;
        }
        
        // Calcular hash
        $secretKey = 'kron_secret_key_change_in_production_' . date('Y');
        $hash = hash_hmac('sha256', $existingToken . $tokenData['kron_user_id'], $secretKey);
        
        // Gerar dados do QR Code
        $qrData = json_encode([
            'token' => $existingToken,
            'kron_user_id' => $tokenData['kron_user_id'],
            'timestamp' => time(),
            'hash' => $hash
        ]);
        
        // Gerar QR Code (padrão, sem logo)
        try {
            $qrGenerator = new KronQRGenerator();
            $qrCodeUrl = $qrGenerator->generateWithLogo($qrData, 400);
            
            if (empty($qrCodeUrl)) {
                throw new Exception('Erro ao gerar QR Code');
            }
            
            $expiresIn = max(0, strtotime($tokenData['expires_at']) - time());
            
            echo json_encode([
                'success' => true,
                'token' => $existingToken,
                'qr_code_url' => $qrCodeUrl,
                'qr_data' => $qrData,
                'expires_at' => $tokenData['expires_at'],
                'expires_in' => $expiresIn
            ]);
        } catch (Exception $qrError) {
            error_log("KRON QR Generation Error: " . $qrError->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'error' => 'Erro ao gerar QR Code: ' . $qrError->getMessage()
            ]);
        }
    } else {
        // Gerar novo token (sem QR Code)
        $tokenData = $connectionManager->generateConnectionToken($kronUserId, $systemName);
        
        if (!$tokenData) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro ao gerar token']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'token' => $tokenData['token'],
            'expires_at' => $tokenData['expires_at'],
            'expires_in' => $tokenData['expires_in']
        ]);
    }
    
} catch (Exception $e) {
    error_log("KRON Generate Token Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno']);
}

