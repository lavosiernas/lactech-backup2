<?php
/**
 * SafeNode - Sincronização de Logs da Cloudflare (OPCIONAL)
 * Busca dados da Cloudflare e cria logs próprios no SafeNode
 * 
 * NOTA: Esta sincronização é OPCIONAL. O sistema funciona sem Cloudflare.
 * Se não tiver Cloudflare configurado, apenas retorna sucesso vazio.
 * 
 * Executar via cron: */5 * * * * (a cada 5 minutos) - OPCIONAL
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/CloudflareAPI.php';
require_once __DIR__ . '/../includes/SecurityLogger.php';
require_once __DIR__ . '/../includes/Settings.php';

$db = getSafeNodeDatabase();

if (!$db) {
    error_log("SafeNode Sync: Database connection failed");
    exit(1);
}

$cloudflareToken = SafeNodeSettings::get('cloudflare_api_token', '');
if (!$cloudflareToken) {
    // OPCIONAL: Se não tiver Cloudflare, apenas retornar sucesso vazio
    if (php_sapi_name() === 'cli') {
        echo "AVISO: Cloudflare não configurado. Sincronização opcional.\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Cloudflare não configurado. Sincronização opcional.', 'processed' => 0]);
    }
    exit(0);
}

$cloudflareAPI = new CloudflareAPI($cloudflareToken);
$logger = new SecurityLogger($db);

// Buscar todos os sites ativos com Cloudflare Zone ID (OPCIONAL)
$stmt = $db->query("SELECT id, domain, cloudflare_zone_id FROM safenode_sites WHERE is_active = 1 AND cloudflare_zone_id IS NOT NULL AND cloudflare_zone_id != ''");
$sites = $stmt->fetchAll();

if (empty($sites)) {
    // OPCIONAL: Se não tiver sites com Cloudflare, apenas retornar sucesso vazio
    if (php_sapi_name() === 'cli') {
        echo "AVISO: Nenhum site com Cloudflare configurado. Sincronização opcional.\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Nenhum site com Cloudflare configurado. Sincronização opcional.', 'processed' => 0]);
    }
    exit(0);
}

$totalProcessed = 0;
$totalErrors = 0;

foreach ($sites as $site) {
    try {
        $zoneId = $site['cloudflare_zone_id'];
        $siteId = $site['id'];
        
        // Buscar eventos de firewall das últimas 24 horas
        $since = date('c', strtotime('-24 hours'));
        $until = date('c');
        
        $firewallEvents = $cloudflareAPI->getFirewallEvents($zoneId, $since, $until);
        
        if ($firewallEvents['success'] && isset($firewallEvents['data']['result'])) {
            $events = $firewallEvents['data']['result'];
            
            foreach ($events as $event) {
                // Verificar se já existe este log (evitar duplicatas)
                $eventId = $event['id'] ?? $event['rayId'] ?? null;
                if ($eventId) {
                    $checkStmt = $db->prepare("SELECT id FROM safenode_security_logs WHERE metadata LIKE ? LIMIT 1");
                    $checkStmt->execute(['%"cloudflare_event_id":"' . $eventId . '"%']);
                    if ($checkStmt->fetch()) {
                        continue; // Já existe, pular
                    }
                }
                
                $ipAddress = $event['clientIP'] ?? $event['ip'] ?? $event['client']['ip'] ?? null;
                $action = $event['action'] ?? 'allowed';
                $ruleName = $event['ruleName'] ?? $event['rule']['name'] ?? null;
                $requestUri = $event['request']['uri'] ?? $event['request']['url'] ?? '/';
                $requestMethod = $event['request']['method'] ?? 'GET';
                $userAgent = $event['request']['headers']['user-agent'] ?? $event['request']['headers']['User-Agent'] ?? null;
                $countryCode = $event['clientCountryCode'] ?? $event['client']['country'] ?? null;
                
                if ($ipAddress && $ipAddress !== '0.0.0.0') {
                    $threatType = null;
                    $threatScore = 0;
                    
                    // Determinar tipo de ameaça baseado na regra
                    if ($action === 'block' || $action === 'challenge') {
                        $threatScore = 50;
                        if (stripos($ruleName, 'sql') !== false) {
                            $threatType = 'sql_injection';
                            $threatScore = 80;
                        } elseif (stripos($ruleName, 'xss') !== false) {
                            $threatType = 'xss';
                            $threatScore = 70;
                        } elseif (stripos($ruleName, 'rate') !== false) {
                            $threatType = 'rate_limit';
                            $threatScore = 40;
                        } elseif (stripos($ruleName, 'bot') !== false) {
                            $threatType = 'bot';
                            $threatScore = 30;
                        } else {
                            $threatType = 'cloudflare_block';
                        }
                    }
                    
                    // Criar log
                    $logId = $logger->log(
                        $ipAddress,
                        $requestUri,
                        $requestMethod,
                        $action === 'block' || $action === 'challenge' ? 'blocked' : 'allowed',
                        $threatType,
                        $threatScore,
                        $userAgent,
                        $event['request']['headers']['referer'] ?? $event['request']['headers']['Referer'] ?? null,
                        $siteId,
                        isset($event['responseTime']) ? (int)$event['responseTime'] : null,
                        $countryCode
                    );
                    
                    if ($logId && $eventId) {
                        // Salvar ID do evento Cloudflare para evitar duplicatas
                        $metadata = json_encode(['cloudflare_event_id' => $eventId]);
                        $db->exec("UPDATE safenode_security_logs SET metadata = '$metadata' WHERE id = $logId");
                    }
                    
                    if ($logId) {
                        $totalProcessed++;
                    } else {
                        $totalErrors++;
                    }
                }
            }
        } else {
            // Log de erro se não conseguir buscar
            if (isset($firewallEvents['error'])) {
                error_log("SafeNode Sync: Cloudflare API Error - " . json_encode($firewallEvents['error']));
            }
        }
        
    } catch (Exception $e) {
        error_log("SafeNode Sync Error for site {$site['domain']}: " . $e->getMessage());
        $totalErrors++;
    }
}

// Log do resultado
error_log("SafeNode Sync: Processed $totalProcessed logs, $totalErrors errors");

// Retornar resultado
if (php_sapi_name() === 'cli') {
    echo "SafeNode Sync completed: $totalProcessed logs processed, $totalErrors errors\n";
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'processed' => $totalProcessed,
        'errors' => $totalErrors,
        'message' => "Sincronização concluída: $totalProcessed logs processados"
    ]);
}
?>
