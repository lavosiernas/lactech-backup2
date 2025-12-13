<?php
/**
 * Teste do SafeNode reCAPTCHA
 * Use para diagnosticar problemas na hospedagem
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

$tests = [];
$allPassed = true;

// Test 1: Verificar se arquivos existem
$tests['files'] = [
    'config.php' => file_exists(__DIR__ . '/../../includes/config.php'),
    'SafeNodeReCAPTCHA.php' => file_exists(__DIR__ . '/../../includes/SafeNodeReCAPTCHA.php'),
    'Settings.php' => file_exists(__DIR__ . '/../../includes/Settings.php'),
    'HVAPIKeyManager.php' => file_exists(__DIR__ . '/../../includes/HVAPIKeyManager.php'),
];

// Test 2: Tentar carregar classes
try {
    require_once __DIR__ . '/../../includes/config.php';
    $tests['config_loaded'] = true;
} catch (Exception $e) {
    $tests['config_loaded'] = false;
    $tests['config_error'] = $e->getMessage();
    $allPassed = false;
}

try {
    require_once __DIR__ . '/../../includes/SafeNodeReCAPTCHA.php';
    $tests['class_loaded'] = true;
} catch (Exception $e) {
    $tests['class_loaded'] = false;
    $tests['class_error'] = $e->getMessage();
    $allPassed = false;
}

// Test 3: Verificar banco de dados
try {
    if (function_exists('getSafeNodeDatabase')) {
        $db = getSafeNodeDatabase();
        $tests['database_connection'] = ($db !== null);
        
        if ($db) {
            // Verificar se tabela existe
            $stmt = $db->query("SHOW TABLES LIKE 'safenode_recaptcha_challenges'");
            $tests['table_exists'] = ($stmt->rowCount() > 0);
            
            // Verificar configurações
            $stmt = $db->query("SELECT setting_key, setting_value FROM safenode_settings WHERE setting_key LIKE 'safenode_recaptcha%'");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $tests['settings'] = $settings;
        }
    } else {
        $tests['database_connection'] = false;
        $tests['database_error'] = 'Função getSafeNodeDatabase não existe';
        $allPassed = false;
    }
} catch (Exception $e) {
    $tests['database_error'] = $e->getMessage();
    $allPassed = false;
}

// Test 4: Testar inicialização
try {
    SafeNodeReCAPTCHA::init();
    $tests['init_success'] = true;
    $tests['enabled'] = SafeNodeReCAPTCHA::isEnabled();
    $tests['version'] = SafeNodeReCAPTCHA::getVersion();
} catch (Exception $e) {
    $tests['init_success'] = false;
    $tests['init_error'] = $e->getMessage();
    $allPassed = false;
}

// Test 5: Verificar sessão
try {
    $tests['session_support'] = function_exists('session_start');
    if ($tests['session_support']) {
        $testSessionId = 'test_' . time();
        session_id($testSessionId);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $tests['session_started'] = (session_status() === PHP_SESSION_ACTIVE);
        session_write_close();
    }
} catch (Exception $e) {
    $tests['session_error'] = $e->getMessage();
    $allPassed = false;
}

$result = [
    'success' => $allPassed,
    'tests' => $tests,
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

