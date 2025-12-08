<?php
/**
 * SafeNode - Real-time Stats API
 * Endpoint para métricas em tempo real (polling otimizado)
 * 
 * Retorna estatísticas atualizadas para dashboard em tempo real
 * Otimizado para polling frequente (1-5 segundos)
 */

session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');

// Verificar autenticação
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/init.php';

$db = getSafeNodeDatabase();
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;

if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao conectar ao banco']);
    exit;
}

try {
    require_once __DIR__ . '/../includes/CacheManager.php';
    $cache = CacheManager::getInstance();
    
    // Parâmetros
    $timeWindow = (int)($_GET['window'] ?? 60); // Janela de tempo em segundos (padrão: 60s)
    $lastTimestamp = (int)($_GET['since'] ?? 0); // Timestamp da última atualização
    
    // Cache key baseado em parâmetros
    $cacheKey = "realtime_stats:site:$currentSiteId:window:$timeWindow";
    
    // Se não há timestamp anterior, buscar do cache
    if ($lastTimestamp === 0) {
        $cached = $cache->get($cacheKey);
        if ($cached !== null) {
            echo json_encode($cached);
            exit;
        }
    }
    
    // Estatísticas do último minuto (ou janela especificada)
    $stats = [
        'timestamp' => time(),
        'window' => $timeWindow,
        'requests' => [
            'total' => 0,
            'blocked' => 0,
            'allowed' => 0,
            'challenged' => 0,
            'per_second' => 0
        ],
        'threats' => [
            'total' => 0,
            'by_type' => []
        ],
        'top_ips' => [],
        'recent_events' => []
    ];
    
    // Query otimizada para última janela de tempo
    $sql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked,
            SUM(CASE WHEN action_taken = 'allowed' THEN 1 ELSE 0 END) as allowed,
            SUM(CASE WHEN action_taken = 'challenged' THEN 1 ELSE 0 END) as challenged,
            SUM(CASE WHEN threat_type IS NOT NULL THEN 1 ELSE 0 END) as threats
        FROM safenode_security_logs
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
    ";
    
    $params = [$timeWindow];
    if ($currentSiteId > 0) {
        $sql .= " AND site_id = ?";
        $params[] = $currentSiteId;
    } elseif ($userId) {
        $sql .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
        $params[] = $userId;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $stats['requests'] = [
            'total' => (int)($result['total'] ?? 0),
            'blocked' => (int)($result['blocked'] ?? 0),
            'allowed' => (int)($result['allowed'] ?? 0),
            'challenged' => (int)($result['challenged'] ?? 0),
            'per_second' => $timeWindow > 0 ? round((int)($result['total'] ?? 0) / $timeWindow, 2) : 0
        ];
        $stats['threats']['total'] = (int)($result['threats'] ?? 0);
    }
    
    // Top tipos de ameaça
    $sqlThreats = "
        SELECT threat_type, COUNT(*) as count
        FROM safenode_security_logs
        WHERE threat_type IS NOT NULL
        AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
    ";
    $paramsThreats = [$timeWindow];
    if ($currentSiteId > 0) {
        $sqlThreats .= " AND site_id = ?";
        $paramsThreats[] = $currentSiteId;
    } elseif ($userId) {
        $sqlThreats .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
        $paramsThreats[] = $userId;
    }
    $sqlThreats .= " GROUP BY threat_type ORDER BY count DESC LIMIT 5";
    
    $stmt = $db->prepare($sqlThreats);
    $stmt->execute($paramsThreats);
    $threatTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($threatTypes as $threat) {
        $stats['threats']['by_type'][$threat['threat_type']] = (int)$threat['count'];
    }
    
    // Top IPs (últimos 5 minutos)
    $sqlIPs = "
        SELECT ip_address, COUNT(*) as count, MAX(threat_score) as max_threat
        FROM safenode_security_logs
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 300 SECOND)
    ";
    $paramsIPs = [];
    if ($currentSiteId > 0) {
        $sqlIPs .= " AND site_id = ?";
        $paramsIPs[] = $currentSiteId;
    } elseif ($userId) {
        $sqlIPs .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
        $paramsIPs[] = $userId;
    }
    $sqlIPs .= " GROUP BY ip_address ORDER BY count DESC LIMIT 10";
    
    $stmt = !empty($paramsIPs) ? $db->prepare($sqlIPs) : $db->query($sqlIPs);
    if (!empty($paramsIPs)) {
        $stmt->execute($paramsIPs);
    }
    $topIPs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($topIPs as $ip) {
        $stats['top_ips'][] = [
            'ip_address' => $ip['ip_address'],
            'requests' => (int)$ip['count'],
            'max_threat_score' => (int)($ip['max_threat'] ?? 0)
        ];
    }
    
    // Eventos recentes (apenas novos desde lastTimestamp)
    if ($lastTimestamp > 0) {
        $sqlEvents = "
            SELECT id, ip_address, request_uri, action_taken, threat_type, threat_score, created_at
            FROM safenode_security_logs
            WHERE UNIX_TIMESTAMP(created_at) > ?
        ";
        $paramsEvents = [$lastTimestamp];
        if ($currentSiteId > 0) {
            $sqlEvents .= " AND site_id = ?";
            $paramsEvents[] = $currentSiteId;
        } elseif ($userId) {
            $sqlEvents .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $paramsEvents[] = $userId;
        }
        $sqlEvents .= " ORDER BY created_at DESC LIMIT 20";
        
        $stmt = $db->prepare($sqlEvents);
        $stmt->execute($paramsEvents);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($events as $event) {
            $stats['recent_events'][] = [
                'id' => (int)$event['id'],
                'ip_address' => $event['ip_address'],
                'request_uri' => substr($event['request_uri'], 0, 100),
                'action_taken' => $event['action_taken'],
                'threat_type' => $event['threat_type'],
                'threat_score' => (int)($event['threat_score'] ?? 0),
                'created_at' => $event['created_at'],
                'timestamp' => strtotime($event['created_at'])
            ];
        }
    }
    
    // Salvar no cache (TTL: 5 segundos para dados em tempo real)
    $cache->set($cacheKey, $stats, 5);
    
    echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    error_log("SafeNode Real-time Stats Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao processar requisição']);
}



