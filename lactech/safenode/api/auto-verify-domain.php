<?php
/**
 * SafeNode - Verificação Automática de Domínio
 * Verifica automaticamente se o domínio foi verificado
 */

session_start();
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$db = getSafeNodeDatabase();
if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao conectar ao banco']);
    exit;
}

$siteId = intval($_GET['site_id'] ?? 0);
if ($siteId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID do site inválido']);
    exit;
}

// Verificar que o site pertence ao usuário
$userId = $_SESSION['safenode_user_id'] ?? null;
$stmt = $db->prepare("SELECT * FROM safenode_sites WHERE id = ? AND user_id = ?");
$stmt->execute([$siteId, $userId]);
$site = $stmt->fetch();

if (!$site) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Site não encontrado']);
    exit;
}

// Se já está verificado, retornar sucesso
if ($site['verification_status'] === 'verified') {
    echo json_encode([
        'success' => true,
        'verified' => true,
        'message' => 'Domínio já verificado'
    ]);
    exit;
}

$domain = $site['domain'];
$token = $site['verification_token'];
$verified = false;
$method = '';

// Método 1: DNS TXT
$dnsRecords = @dns_get_record($domain, DNS_TXT);
if ($dnsRecords !== false && is_array($dnsRecords)) {
    foreach ($dnsRecords as $record) {
        $txtValue = $record['txt'] ?? '';
        $expectedValue = "safenode-verification=$token";
        
        if (strpos($txtValue, $expectedValue) !== false || 
            trim($txtValue) === $expectedValue || 
            trim($txtValue) === $token) {
            $verified = true;
            $method = 'dns';
            break;
        }
    }
}

// Método 2: Arquivo HTTP (se DNS não funcionou)
if (!$verified) {
    $urls = [
        "http://$domain/safenode-verification.txt",
        "https://$domain/safenode-verification.txt"
    ];
    
    foreach ($urls as $url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'SafeNode-Verification/1.0',
                'follow_location' => true,
                'max_redirects' => 3
            ]
        ]);
        
        $content = @file_get_contents($url, false, $context);
        if ($content !== false) {
            $content = trim($content);
            if ($content === $token || $content === "safenode-verification=$token") {
                $verified = true;
                $method = 'file';
                break;
            }
        }
    }
}

// Atualizar status no banco se verificado
if ($verified) {
    try {
        $stmt = $db->prepare("
            UPDATE safenode_sites 
            SET verification_status = 'verified', 
                verification_verified_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$siteId]);
        
        echo json_encode([
            'success' => true,
            'verified' => true,
            'method' => $method,
            'message' => 'Domínio verificado com sucesso!'
        ]);
    } catch (PDOException $e) {
        error_log("SafeNode Auto-Verify Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao atualizar status'
        ]);
    }
} else {
    echo json_encode([
        'success' => true,
        'verified' => false,
        'message' => 'Domínio ainda não verificado'
    ]);
}
?>


/**
 * SafeNode - Verificação Automática de Domínio
 * Verifica automaticamente se o domínio foi verificado
 */

session_start();
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$db = getSafeNodeDatabase();
if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao conectar ao banco']);
    exit;
}

$siteId = intval($_GET['site_id'] ?? 0);
if ($siteId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID do site inválido']);
    exit;
}

// Verificar que o site pertence ao usuário
$userId = $_SESSION['safenode_user_id'] ?? null;
$stmt = $db->prepare("SELECT * FROM safenode_sites WHERE id = ? AND user_id = ?");
$stmt->execute([$siteId, $userId]);
$site = $stmt->fetch();

if (!$site) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Site não encontrado']);
    exit;
}

// Se já está verificado, retornar sucesso
if ($site['verification_status'] === 'verified') {
    echo json_encode([
        'success' => true,
        'verified' => true,
        'message' => 'Domínio já verificado'
    ]);
    exit;
}

$domain = $site['domain'];
$token = $site['verification_token'];
$verified = false;
$method = '';

// Método 1: DNS TXT
$dnsRecords = @dns_get_record($domain, DNS_TXT);
if ($dnsRecords !== false && is_array($dnsRecords)) {
    foreach ($dnsRecords as $record) {
        $txtValue = $record['txt'] ?? '';
        $expectedValue = "safenode-verification=$token";
        
        if (strpos($txtValue, $expectedValue) !== false || 
            trim($txtValue) === $expectedValue || 
            trim($txtValue) === $token) {
            $verified = true;
            $method = 'dns';
            break;
        }
    }
}

// Método 2: Arquivo HTTP (se DNS não funcionou)
if (!$verified) {
    $urls = [
        "http://$domain/safenode-verification.txt",
        "https://$domain/safenode-verification.txt"
    ];
    
    foreach ($urls as $url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'SafeNode-Verification/1.0',
                'follow_location' => true,
                'max_redirects' => 3
            ]
        ]);
        
        $content = @file_get_contents($url, false, $context);
        if ($content !== false) {
            $content = trim($content);
            if ($content === $token || $content === "safenode-verification=$token") {
                $verified = true;
                $method = 'file';
                break;
            }
        }
    }
}

// Atualizar status no banco se verificado
if ($verified) {
    try {
        $stmt = $db->prepare("
            UPDATE safenode_sites 
            SET verification_status = 'verified', 
                verification_verified_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$siteId]);
        
        echo json_encode([
            'success' => true,
            'verified' => true,
            'method' => $method,
            'message' => 'Domínio verificado com sucesso!'
        ]);
    } catch (PDOException $e) {
        error_log("SafeNode Auto-Verify Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao atualizar status'
        ]);
    }
} else {
    echo json_encode([
        'success' => true,
        'verified' => false,
        'message' => 'Domínio ainda não verificado'
    ]);
}
?>







