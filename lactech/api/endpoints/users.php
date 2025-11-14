<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once '../../includes/Database.class.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Estatísticas de usuários
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users
        FROM users 
        WHERE farm_id = 1
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Lista de usuários
    $stmt = $pdo->prepare("
        SELECT 
            id,
            name,
            email,
            role,
            phone,
            is_active,
            last_login,
            created_at
        FROM users 
        WHERE farm_id = 1
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $usersData = [
        'stats' => $stats,
        'users' => $users
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $usersData
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>