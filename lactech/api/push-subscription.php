<?php
/**
 * API para gerenciar Push Notifications Subscriptions
 * Salva e remove subscriptions de usuários para envio de notificações push
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/Database.class.php';

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'];
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode([
            'success' => false,
            'error' => 'Usuário não autenticado'
        ]);
        exit;
    }
    
    // Obter dados do body
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($method) {
        case 'POST':
            // Salvar subscription
            if (!isset($input['subscription'])) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Subscription não fornecida'
                ]);
                exit;
            }
            
            $subscription = $input['subscription'];
            $endpoint = $subscription['endpoint'] ?? '';
            $keys = $subscription['keys'] ?? [];
            $p256dh = $keys['p256dh'] ?? '';
            $auth = $keys['auth'] ?? '';
            
            // Verificar se já existe subscription para este usuário
            $checkSql = "SELECT id FROM push_subscriptions WHERE user_id = :user_id AND endpoint = :endpoint";
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->execute([
                ':user_id' => $userId,
                ':endpoint' => $endpoint
            ]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Atualizar subscription existente
                $updateSql = "UPDATE push_subscriptions 
                             SET p256dh = :p256dh, auth = :auth, updated_at = NOW() 
                             WHERE id = :id";
                $updateStmt = $db->prepare($updateSql);
                $updateStmt->execute([
                    ':p256dh' => $p256dh,
                    ':auth' => $auth,
                    ':id' => $existing['id']
                ]);
            } else {
                // Criar nova subscription
                $insertSql = "INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth, created_at, updated_at) 
                             VALUES (:user_id, :endpoint, :p256dh, :auth, NOW(), NOW())";
                $insertStmt = $db->prepare($insertSql);
                $insertStmt->execute([
                    ':user_id' => $userId,
                    ':endpoint' => $endpoint,
                    ':p256dh' => $p256dh,
                    ':auth' => $auth
                ]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Subscription salva com sucesso'
            ]);
            break;
            
        case 'DELETE':
            // Remover subscription
            if (isset($input['endpoint'])) {
                $deleteSql = "DELETE FROM push_subscriptions WHERE user_id = :user_id AND endpoint = :endpoint";
                $deleteStmt = $db->prepare($deleteSql);
                $deleteStmt->execute([
                    ':user_id' => $userId,
                    ':endpoint' => $input['endpoint']
                ]);
            } else {
                // Remover todas as subscriptions do usuário
                $deleteSql = "DELETE FROM push_subscriptions WHERE user_id = :user_id";
                $deleteStmt = $db->prepare($deleteSql);
                $deleteStmt->execute([
                    ':user_id' => $userId
                ]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Subscription removida com sucesso'
            ]);
            break;
            
        case 'GET':
            // Obter subscriptions do usuário
            $getSql = "SELECT id, endpoint, created_at, updated_at FROM push_subscriptions WHERE user_id = :user_id";
            $getStmt = $db->prepare($getSql);
            $getStmt->execute([
                ':user_id' => $userId
            ]);
            $subscriptions = $getStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'subscriptions' => $subscriptions
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Método não suportado'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno: ' . $e->getMessage()
    ]);
}



















