<?php
/**
 * SafeNode - Script de Envio de E-mails em Massa
 * Backend para processar envios em lote
 */

session_start();

// Verificar autenticação admin
if (!isset($_SESSION['safenode_admin_auth'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Aceitar apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/EmailService.php';

// Tipo de e-mail a enviar
$type = $_POST['type'] ?? '';

if (!in_array($type, ['maintenance', 'online'])) {
    echo json_encode(['success' => false, 'error' => 'Tipo de e-mail inválido']);
    exit;
}

try {
    $pdo = getSafeNodeDatabase();
    
    // Buscar todos os usuários com e-mail verificado
    $stmt = $pdo->prepare("
        SELECT id, full_name, email 
        FROM safenode_users 
        WHERE email_verified = 1 
        AND email IS NOT NULL 
        AND email != ''
        ORDER BY id ASC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo json_encode([
            'success' => false, 
            'error' => 'Nenhum usuário encontrado',
            'total' => 0,
            'sent' => 0
        ]);
        exit;
    }
    
    $totalUsers = count($users);
    $emailService = SafeNodeEmailService::getInstance();
    $sentCount = 0;
    $failedCount = 0;
    $errors = [];
    
    // Enviar e-mails
    foreach ($users as $user) {
        try {
            $result = false;
            
            if ($type === 'maintenance') {
                $result = $emailService->sendMaintenanceNotification(
                    $user['email'], 
                    $user['full_name']
                );
            } elseif ($type === 'online') {
                $result = $emailService->sendSystemOnlineNotification(
                    $user['email'], 
                    $user['full_name']
                );
            }
            
            if ($result && isset($result['success']) && $result['success']) {
                $sentCount++;
            } else {
                $failedCount++;
                $errorMsg = isset($result['error']) ? $result['error'] : 'Erro desconhecido';
                $errors[] = "Falha ao enviar para {$user['email']}: {$errorMsg}";
            }
            
            // Pequeno delay para não sobrecarregar o servidor de e-mail
            usleep(100000); // 100ms
            
        } catch (Exception $e) {
            $failedCount++;
            $errors[] = "Exceção ao enviar para {$user['email']}: " . $e->getMessage();
            error_log("Erro ao enviar e-mail para {$user['email']}: " . $e->getMessage());
        }
    }
    
    // Log dos erros se houver
    if (!empty($errors)) {
        error_log("SafeNode Mass Email - Erros de envio:\n" . implode("\n", $errors));
    }
    
    // Retornar resultado
    echo json_encode([
        'success' => true,
        'total' => $totalUsers,
        'sent' => $sentCount,
        'failed' => $failedCount,
        'message' => "E-mails enviados: {$sentCount}/{$totalUsers}"
    ]);
    
} catch (PDOException $e) {
    error_log("Erro no banco ao enviar e-mails em massa: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao acessar banco de dados: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erro geral ao enviar e-mails em massa: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao enviar e-mails: ' . $e->getMessage()
    ]);
}

