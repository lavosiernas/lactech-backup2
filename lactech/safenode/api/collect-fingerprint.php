<?php
/**
 * SafeNode - Collect Browser Fingerprint
 * Endpoint para receber fingerprints do navegador
 */

session_start();

header('Content-Type: application/json');

// Carregar configuração
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/init.php';

$db = getSafeNodeDatabase();

if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

try {
    require_once __DIR__ . '/../includes/BrowserFingerprint.php';
    
    // Obter dados do POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['fingerprint'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Fingerprint missing']);
        exit;
    }
    
    $fingerprint = $input['fingerprint'];
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Identificar site
    $siteId = null;
    $domain = $_SERVER['HTTP_HOST'] ?? '';
    if ($domain) {
        $domain = preg_replace('/^www\./', '', $domain);
        $stmt = $db->prepare("SELECT id FROM safenode_sites WHERE domain = ? AND is_active = 1");
        $stmt->execute([$domain]);
        $site = $stmt->fetch();
        if ($site) {
            $siteId = $site['id'];
        }
    }
    
    // Analisar fingerprint
    $fingerprintManager = new BrowserFingerprint($db);
    $analysis = $fingerprintManager->analyzeFingerprint($fingerprint);
    
    // Salvar no banco
    $fingerprintManager->saveFingerprint($ipAddress, $fingerprint, $analysis, $siteId);
    
    // Retornar sucesso (não retornar análise para não expor lógica)
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("SafeNode Fingerprint Collection Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal error']);
}








