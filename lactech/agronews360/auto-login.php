<?php
/**
 * Auto-Login do AgroNews360
 * Login automático quando vem da página do gerente (Lactech)
 */

session_start();

// Verificar se já está logado no AgroNews
if (isset($_SESSION['agronews_logged_in']) && $_SESSION['agronews_logged_in']) {
    header('Location: index.php');
    exit;
}

// Verificar se está logado no Lactech
// A sessão do Lactech pode usar diferentes variáveis
$isLactechLoggedIn = (
    (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) ||
    (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']))
);

if (!$isLactechLoggedIn) {
    // Se não estiver logado no Lactech, redirecionar para login
    header('Location: login.php');
    exit;
}

// Usuário está logado no Lactech, fazer login automático no AgroNews
require_once __DIR__ . '/includes/Database.class.php';
require_once __DIR__ . '/includes/LactechIntegration.class.php';

try {
    $integration = new LactechIntegration();
    
    if (!$integration->isLactechConnected()) {
        // Se não conseguir conectar ao Lactech, redirecionar para login normal
        header('Location: login.php?error_message=' . urlencode('Sistema Lactech não está disponível'));
        exit;
    }
    
    $lactechDb = $integration->getLactechConnection();
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        header('Location: login.php');
        exit;
    }
    
    // Buscar dados do usuário no Lactech
    $stmt = $lactechDb->prepare("
        SELECT u.*, f.name as farm_name 
        FROM users u
        LEFT JOIN farms f ON u.farm_id = f.id
        WHERE u.id = ? AND u.is_active = 1
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: login.php?error_message=' . urlencode('Usuário não encontrado no Lactech'));
        exit;
    }
    
    // Criar/atualizar usuário no AgroNews
    $agronewsDb = Database::getInstance();
    $pdo = $agronewsDb->getConnection();
    
    // Todos os usuários são tratados igualmente (sem distinção de admin)
    // O AgroNews é alimentado pela web, então não precisa de roles diferentes
    $defaultRole = 'viewer'; // Role padrão para todos
    
    // Verificar se já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE lactech_user_id = ? OR email = ?");
    $stmt->execute([$user['id'], $user['email']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        // Criar usuário no AgroNews
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, is_active, lactech_user_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user['name'],
            $user['email'],
            $user['password'] ?? null,
            $defaultRole,
            $user['is_active'],
            $user['id']
        ]);
        
        $agronewsUserId = $pdo->lastInsertId();
    } else {
        // Atualizar usuário
        $agronewsUserId = $existing['id'];
        $stmt = $pdo->prepare("
            UPDATE users 
            SET name = ?, email = ?, role = ?, is_active = ?, lactech_user_id = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $user['name'],
            $user['email'],
            $defaultRole,
            $user['is_active'],
            $user['id'],
            $agronewsUserId
        ]);
    }
    
    // Criar sessão do AgroNews
    $_SESSION['agronews_user_id'] = $agronewsUserId;
    $_SESSION['agronews_user_email'] = $user['email'];
    $_SESSION['agronews_user_name'] = $user['name'];
    $_SESSION['agronews_user_role'] = $defaultRole;
    $_SESSION['agronews_lactech_user_id'] = $user['id'];
    $_SESSION['agronews_farm_id'] = $user['farm_id'] ?? null;
    $_SESSION['agronews_farm_name'] = $user['farm_name'] ?? null;
    $_SESSION['agronews_logged_in'] = true;
    
    // Redirecionar para o index
    header('Location: index.php?login_success=1');
    exit;
    
} catch (Exception $e) {
    error_log("Erro no auto-login: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $errorMsg = 'Erro ao fazer login automático: ' . $e->getMessage();
    header('Location: login.php?error_message=' . urlencode($errorMsg));
    exit;
} catch (Throwable $e) {
    error_log("Erro fatal no auto-login: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $errorMsg = 'Erro ao fazer login automático: ' . $e->getMessage();
    header('Location: login.php?error_message=' . urlencode($errorMsg));
    exit;
}

