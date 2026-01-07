<?php
/**
 * SafeNode - API de Estatísticas da Dashboard em Tempo Real
 * Retorna estatísticas atualizadas para atualização em tempo real da dashboard
 */

// Desabilitar exibição de erros e warnings ANTES de qualquer output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Iniciar output buffering para capturar qualquer output inesperado
ob_start();

session_start();

// Enviar headers JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Carregar includes com tratamento de erro
try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/init.php';
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    error_log("SafeNode Includes Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao carregar configurações',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    exit;
} catch (Error $e) {
    ob_clean();
    http_response_code(500);
    error_log("SafeNode Includes Fatal Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Erro fatal ao carregar configurações',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    exit;
}

$db = getSafeNodeDatabase();
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;

if (!$db) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao conectar ao banco de dados',
        'data' => [
            'today' => ['total_requests' => 0, 'blocked' => 0, 'threats' => []],
            'last24h' => ['unique_ips' => 0],
            'active_blocks' => 0,
            'top_blocked_ips' => [],
            'top_countries' => [],
            'event_logs' => [],
            'behavior_analysis' => [],
            'analytics' => []
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    exit;
}

// Verificar que o site pertence ao usuário logado se houver site selecionado
if ($currentSiteId > 0) {
    try {
        $stmt = $db->prepare("SELECT id FROM safenode_sites WHERE id = ? AND user_id = ?");
        $stmt->execute([$currentSiteId, $userId]);
        if (!$stmt->fetch()) {
            $currentSiteId = 0; // Resetar se não pertencer ao usuário
        }
    } catch (PDOException $e) {
        $currentSiteId = 0;
    }
}

// Função helper para preparar filtro de site
$prepareSiteFilter = function($sql) use ($db, $currentSiteId, $userId) {
    $params = [];
    if ($currentSiteId > 0) {
        $sql .= " AND site_id = ?";
        $params[] = $currentSiteId;
    } elseif ($userId) {
        // Se não há site selecionado, filtrar apenas por sites do usuário
        $sql .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
        $params[] = $userId;
    }
    try {
        if (!empty($params)) {
            $stmt = $db->prepare($sql);
            if ($stmt) {
                $stmt->execute($params);
                return $stmt;
            }
        } else {
            $stmt = $db->query($sql);
            if ($stmt) {
                return $stmt;
            }
        }
        return null;
    } catch (PDOException $e) {
        error_log("SafeNode Query Error: " . $e->getMessage() . " SQL: " . $sql);
        return null;
    }
};

try {
    // Estatísticas do dia (DADOS PRÓPRIOS - FONTE PRIMÁRIA)
    $todayStats = [
        'total_requests' => 0,
        'blocked_requests' => 0,
        'allowed_requests' => 0,
        'challenged_requests' => 0,
        'rate_limited_requests' => 0,
        'unique_ips' => 0,
        'avg_threat_score' => 0,
        'max_threat_score' => 0,
        'critical_threats' => 0,
        'sql_injection_count' => 0,
        'xss_count' => 0,
        'brute_force_count' => 0,
        'rate_limit_count' => 0,
        'ddos_count' => 0,
        'path_traversal_count' => 0,
        'command_injection_count' => 0,
        'rce_php_count' => 0
    ];
    
    try {
        // Buscar desafios da tabela de verificação humana
        $challengeCount = 0;
        try {
            $sqlChallenge = "SELECT COUNT(*) as challenge_count 
                FROM safenode_human_verification_logs 
                WHERE event_type = 'challenge_shown' 
                AND DATE(created_at) = CURDATE()";
            $paramsChallenge = [];
            if ($currentSiteId > 0) {
                $sqlChallenge .= " AND site_id = ?";
                $paramsChallenge[] = $currentSiteId;
            } elseif ($userId) {
                $sqlChallenge .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
                $paramsChallenge[] = $userId;
            }
            $stmtChallenge = $db->prepare($sqlChallenge);
            if (!empty($paramsChallenge)) {
                $stmtChallenge->execute($paramsChallenge);
            } else {
                $stmtChallenge->execute();
            }
            $challengeResult = $stmtChallenge->fetch();
            if ($challengeResult) {
                $challengeCount = (int)($challengeResult['challenge_count'] ?? 0);
            }
        } catch (PDOException $e) {
            error_log("SafeNode Challenge Count Error: " . $e->getMessage());
        }
        
        $sqlToday = "SELECT 
            COUNT(*) as total_requests,
            COALESCE(SUM(CASE WHEN event_type = 'bot_blocked' THEN 1 ELSE 0 END), 0) as blocked_requests,
            COALESCE(SUM(CASE WHEN event_type IN ('access_allowed', 'human_validated') THEN 1 ELSE 0 END), 0) as allowed_requests,
            COUNT(DISTINCT ip_address) as unique_ips
            FROM safenode_human_verification_logs 
            WHERE DATE(created_at) = CURDATE()";
            
        // Adicionar filtro de site se necessário
        $params = [];
        if ($currentSiteId > 0) {
            $sqlToday .= " AND site_id = ?";
            $params[] = $currentSiteId;
        } elseif ($userId) {
            $sqlToday .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params[] = $userId;
        }
            
        $stmt = $db->prepare($sqlToday);
        if ($stmt) {
            if (!empty($params)) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $todayStats = [
                    'total_requests' => (int)($result['total_requests'] ?? 0),
                    'blocked_requests' => (int)($result['blocked_requests'] ?? 0) ?: 0,
                    'allowed_requests' => (int)($result['allowed_requests'] ?? 0) ?: 0,
                    'challenged_requests' => $challengeCount,
                    'rate_limited_requests' => 0,
                    'unique_ips' => (int)($result['unique_ips'] ?? 0),
                    'avg_threat_score' => 0,
                    'max_threat_score' => 0,
                    'critical_threats' => 0,
                    'sql_injection_count' => 0,
                    'xss_count' => 0,
                    'brute_force_count' => 0,
                    'rate_limit_count' => 0,
                    'ddos_count' => 0,
                    'path_traversal_count' => 0,
                    'command_injection_count' => 0,
                    'rce_php_count' => 0
                ];
            }
        }
    } catch (PDOException $e) {
        error_log("SafeNode Today Stats Query Error: " . $e->getMessage());
        // Manter valores padrão
    } catch (Exception $e) {
        error_log("SafeNode Today Stats General Error: " . $e->getMessage());
        // Manter valores padrão
    }
    
    // Estatísticas das últimas 24 horas
    $last24hStats = ['total_requests' => 0, 'blocked_requests' => 0, 'unique_ips' => 0];
    try {
        $sql24h = "SELECT 
            COUNT(*) as total_requests,
            COALESCE(SUM(CASE WHEN event_type = 'bot_blocked' THEN 1 ELSE 0 END), 0) as blocked_requests,
            COUNT(DISTINCT ip_address) as unique_ips
            FROM safenode_human_verification_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        
        $params = [];
        if ($currentSiteId > 0) {
            $sql24h .= " AND site_id = ?";
            $params[] = $currentSiteId;
        } elseif ($userId) {
            $sql24h .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params[] = $userId;
        }
        
        $stmt = $db->prepare($sql24h);
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $last24hStats = [
                'total_requests' => (int)($result['total_requests'] ?? 0),
                'blocked_requests' => (int)($result['blocked_requests'] ?? 0) ?: 0,
                'unique_ips' => (int)($result['unique_ips'] ?? 0)
            ];
        }
    } catch (PDOException $e) {
        error_log("SafeNode 24h Stats Query Error: " . $e->getMessage());
        // Manter valores padrão
    }
    
    // Estatísticas de ontem para comparação
    $yesterdayStats = ['total_requests' => 0, 'blocked_requests' => 0, 'unique_ips' => 0];
    try {
        $sqlYesterday = "SELECT 
            COUNT(*) as total_requests,
            COALESCE(SUM(CASE WHEN event_type = 'bot_blocked' THEN 1 ELSE 0 END), 0) as blocked_requests,
            COUNT(DISTINCT ip_address) as unique_ips
            FROM safenode_human_verification_logs 
            WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        
        $params = [];
        if ($currentSiteId > 0) {
            $sqlYesterday .= " AND site_id = ?";
            $params[] = $currentSiteId;
        } elseif ($userId) {
            $sqlYesterday .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params[] = $userId;
        }
        
        $stmt = $db->prepare($sqlYesterday);
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $yesterdayStats = [
                'total_requests' => (int)($result['total_requests'] ?? 0),
                'blocked_requests' => (int)($result['blocked_requests'] ?? 0) ?: 0,
                'unique_ips' => (int)($result['unique_ips'] ?? 0)
            ];
        }
    } catch (PDOException $e) {
        error_log("SafeNode Yesterday Stats Query Error: " . $e->getMessage());
        // Manter valores padrão
    }
    
    // IPs bloqueados ativos
    $activeBlocks = ['total' => 0];
    try {
        $stmt = $db->query("SELECT COUNT(DISTINCT ip_address) as total FROM safenode_human_verification_logs WHERE event_type = 'bot_blocked' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $activeBlocks = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0];
    } catch (PDOException $e) {
        error_log("SafeNode Active Blocks Error: " . $e->getMessage());
    }
    
    // Calcular latência - removido (não é core)
    $latencyData = null;
    
    // Últimos logs (últimos 10)
    $recentLogs = [];
    try {
        $sqlRecent = "SELECT * FROM safenode_human_verification_logs WHERE 1=1";
        
        $params = [];
        if ($currentSiteId > 0) {
            $sqlRecent .= " AND site_id = ?";
            $params[] = $currentSiteId;
        } elseif ($userId) {
            $sqlRecent .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params[] = $userId;
        }
        
        $sqlRecent .= " ORDER BY created_at DESC LIMIT 10";
        
        $stmt = $db->prepare($sqlRecent);
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("SafeNode Recent Logs Query Error: " . $e->getMessage());
        $recentLogs = [];
    }
    
    // Top IPs bloqueados (últimos 7 dias)
    $topBlockedIPs = [];
    try {
        $sqlTopIPs = "SELECT 
                      ip_address, 
                      COUNT(*) AS block_count, 
                      MAX(created_at) AS last_blocked,
                      COUNT(DISTINCT event_type) AS threat_types_count,
                      SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT event_type ORDER BY event_type ASC SEPARATOR ','), ',', 10) AS threat_types 
                      FROM safenode_human_verification_logs 
                      WHERE event_type = 'bot_blocked' 
                      AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $params = [];
        if ($currentSiteId > 0) {
            $sqlTopIPs .= " AND site_id = ?";
            $params[] = $currentSiteId;
        } elseif ($userId) {
            $sqlTopIPs .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params[] = $userId;
        }
        $sqlTopIPs .= " GROUP BY ip_address ORDER BY COUNT(*) DESC LIMIT 10";
        $stmt = !empty($params) ? $db->prepare($sqlTopIPs) : $db->query($sqlTopIPs);
        if (!empty($params)) {
            $stmt->execute($params);
        }
        $topBlockedIPs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("SafeNode Top IPs Query Error: " . $e->getMessage());
        $topBlockedIPs = [];
    }
    
    // Top Tipos de Evento (últimos 7 dias)
    $topThreatTypes = [];
    try {
        $sqlThreatTypes = "SELECT 
                          event_type as threat_type,
                          COUNT(*) AS total_occurrences,
                          COUNT(DISTINCT ip_address) AS unique_ips
                          FROM safenode_human_verification_logs 
                          WHERE event_type IS NOT NULL 
                          AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $params = [];
        if ($currentSiteId > 0) {
            $sqlThreatTypes .= " AND site_id = ?";
            $params[] = $currentSiteId;
        } elseif ($userId) {
            $sqlThreatTypes .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params[] = $userId;
        }
        $sqlThreatTypes .= " GROUP BY threat_type ORDER BY COUNT(*) DESC LIMIT 10";
        $stmt = !empty($params) ? $db->prepare($sqlThreatTypes) : $db->query($sqlThreatTypes);
        if (!empty($params)) {
            $stmt->execute($params);
        }
        $topThreatTypes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("SafeNode Top Threat Types Query Error: " . $e->getMessage());
        $topThreatTypes = [];
    }
    
    // Top Países (últimos 7 dias)
    $topCountries = [];
    try {
        $sqlCountries = "SELECT 
            COALESCE(country_code, '??') as country_code,
            COUNT(*) as total_requests,
            SUM(CASE WHEN event_type = 'bot_blocked' THEN 1 ELSE 0 END) as blocked_requests
            FROM safenode_human_verification_logs
            WHERE country_code IS NOT NULL
              AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $params = [];
        if ($currentSiteId > 0) {
            $sqlCountries .= " AND site_id = ?";
            $params[] = $currentSiteId;
        } elseif ($userId) {
            $sqlCountries .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params[] = $userId;
        }
        $sqlCountries .= " GROUP BY country_code ORDER BY total_requests DESC LIMIT 5";
        $stmt = !empty($params) ? $db->prepare($sqlCountries) : $db->query($sqlCountries);
        if (!empty($params)) {
            $stmt->execute($params);
        }
        $topCountries = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("SafeNode Top Countries Query Error: " . $e->getMessage());
        $topCountries = [];
    }
    
    // Incidentes recentes (usando logs de verificação humana)
    $recentIncidents = [];
    try {
        // Não temos tabela de incidentes, então retornamos array vazio
        $recentIncidents = [];
    } catch (PDOException $e) {
        $recentIncidents = [];
    }
    
    // Dados para gráfico de linha (últimas 24 horas por hora)
    $hourlyData = [];
    try {
        $sqlHourly = "SELECT 
            HOUR(created_at) as hour,
            COUNT(*) as requests,
            SUM(CASE WHEN event_type = 'bot_blocked' THEN 1 ELSE 0 END) as blocked
            FROM safenode_human_verification_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $params = [];
        if ($currentSiteId > 0) {
            $sqlHourly .= " AND site_id = ?";
            $params[] = $currentSiteId;
        } elseif ($userId) {
            $sqlHourly .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params[] = $userId;
        }
        $stmt = $db->prepare($sqlHourly);
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        $hourlyData = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("SafeNode Hourly Data Query Error: " . $e->getMessage());
        $hourlyData = [];
    }
    
    // Processar dados horários (últimas 7 horas)
    $hourlyStats = [];
    for ($i = 6; $i >= 0; $i--) {
        $hour = date('H', strtotime("-$i hours"));
        $hourlyStats[$hour] = ['requests' => 0, 'blocked' => 0];
    }
    foreach ($hourlyData as $data) {
        $hour = str_pad($data['hour'], 2, '0', STR_PAD_LEFT);
        if (isset($hourlyStats[$hour])) {
            $hourlyStats[$hour]['requests'] = (int)$data['requests'];
            $hourlyStats[$hour]['blocked'] = (int)$data['blocked'];
        }
    }
    
    // Calcular variações percentuais
    $requests24h = (int)($last24hStats['total_requests'] ?? 0);
    $blocked24h = (int)($last24hStats['blocked_requests'] ?? 0);
    $uniqueIps24h = (int)($last24hStats['unique_ips'] ?? 0);
    
    $yesterdayRequests = (int)($yesterdayStats['total_requests'] ?? 0);
    $yesterdayBlocked = (int)($yesterdayStats['blocked_requests'] ?? 0);
    $yesterdayIps = (int)($yesterdayStats['unique_ips'] ?? 0);
    
    $requestsChange = $yesterdayRequests > 0 
        ? round((($requests24h - $yesterdayRequests) / $yesterdayRequests) * 100, 1)
        : ($requests24h > 0 ? 100 : 0);
    
    $blockedChange = $yesterdayBlocked > 0 
        ? round((($blocked24h - $yesterdayBlocked) / $yesterdayBlocked) * 100, 1)
        : ($blocked24h > 0 ? 100 : 0);
    
    $ipsChange = $yesterdayIps > 0 
        ? round((($uniqueIps24h - $yesterdayIps) / $yesterdayIps) * 100, 1)
        : ($uniqueIps24h > 0 ? 100 : 0);
    
    // Preparar resposta
    $response = [
        'success' => true,
        'timestamp' => time(),
        'data' => [
            'today' => [
                'total_requests' => (int)($todayStats['total_requests'] ?? 0),
                'blocked' => (int)($todayStats['blocked_requests'] ?? 0),
                'allowed' => (int)($todayStats['allowed_requests'] ?? 0),
                'challenged' => (int)($todayStats['challenged_requests'] ?? 0),
                'rate_limited' => (int)($todayStats['rate_limited_requests'] ?? 0),
                'unique_ips' => (int)($todayStats['unique_ips'] ?? 0),
                'threat_analysis' => [
                    'avg_threat_score' => round((float)($todayStats['avg_threat_score'] ?? 0), 2),
                    'max_threat_score' => (int)($todayStats['max_threat_score'] ?? 0),
                    'critical_threats' => (int)($todayStats['critical_threats'] ?? 0),
                    'block_rate' => ($todayStats['total_requests'] ?? 0) > 0 
                        ? round((($todayStats['blocked_requests'] ?? 0) / $todayStats['total_requests']) * 100, 2)
                        : 0
                ],
                'threats' => [
                    'sql_injection' => (int)($todayStats['sql_injection_count'] ?? 0),
                    'xss' => (int)($todayStats['xss_count'] ?? 0),
                    'brute_force' => (int)($todayStats['brute_force_count'] ?? 0),
                    'rate_limit' => (int)($todayStats['rate_limit_count'] ?? 0),
                    'ddos' => (int)($todayStats['ddos_count'] ?? 0),
                    'path_traversal' => (int)($todayStats['path_traversal_count'] ?? 0),
                    'command_injection' => (int)($todayStats['command_injection_count'] ?? 0),
                    'rce_php' => (int)($todayStats['rce_php_count'] ?? 0)
                ]
            ],
            'last24h' => [
                'total_requests' => $requests24h,
                'blocked' => $blocked24h,
                'unique_ips' => $uniqueIps24h
            ],
            'changes' => [
                'requests' => $requestsChange,
                'blocked' => $blockedChange,
                'unique_ips' => $ipsChange
            ],
            'active_blocks' => (int)($activeBlocks['total'] ?? 0),
            'latency' => [
                'global' => $latencyData ? (int)$latencyData['p99'] : null,
                'avg' => $latencyData ? (float)$latencyData['avg'] : null
            ],
            'hourly_stats' => $hourlyStats,
            'recent_logs' => array_map(function($log) {
                $eventType = $log['event_type'] ?? 'access_allowed';
                $isBlocked = $eventType === 'bot_blocked';
                return [
                    'id' => (int)($log['id'] ?? 0),
                    'ip_address' => $log['ip_address'] ?? '0.0.0.0',
                    'request_uri' => $log['request_uri'] ?? '/',
                    'action_taken' => $isBlocked ? 'blocked' : 'allowed',
                    'threat_type' => $eventType,
                    'threat_score' => 0,
                    'created_at' => $log['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }, $recentLogs),
            'top_countries' => array_map(function($country) {
                $total = (int)($country['total_requests'] ?? 0);
                $blocked = (int)($country['blocked_requests'] ?? 0);
                return [
                    'country_code' => strtoupper($country['country_code'] ?? '??'),
                    'total_requests' => $total,
                    'blocked_requests' => $blocked,
                    'blocked_percent' => $total > 0 ? round(($blocked / $total) * 100) : 0
                ];
            }, $topCountries),
            'recent_incidents' => [],
            'top_blocked_ips' => array_map(function($ip) {
                return [
                    'ip_address' => $ip['ip_address'] ?? '',
                    'block_count' => (int)($ip['block_count'] ?? 0),
                    'threat_types' => $ip['threat_types'] ?? '',
                    'threat_types_count' => (int)($ip['threat_types_count'] ?? 0),
                    'avg_threat_score' => 0,
                    'max_threat_score' => 0,
                    'last_blocked' => $ip['last_blocked'] ?? null
                ];
            }, $topBlockedIPs),
            'top_threat_types' => array_map(function($threat) {
                return [
                    'threat_type' => $threat['threat_type'] ?? '',
                    'total_occurrences' => (int)($threat['total_occurrences'] ?? 0),
                    'unique_ips' => (int)($threat['unique_ips'] ?? 0),
                    'avg_threat_score' => 0,
                    'max_threat_score' => 0
                ];
            }, $topThreatTypes),
            'event_logs' => array_map(function($log) {
                $eventType = $log['event_type'] ?? 'access_allowed';
                $isBlocked = $eventType === 'bot_blocked';
                $isCritical = $isBlocked;
                return [
                    'id' => (int)($log['id'] ?? 0),
                    'ip_address' => $log['ip_address'] ?? '0.0.0.0',
                    'request_uri' => substr($log['request_uri'] ?? '/', 0, 200),
                    'action_taken' => $isBlocked ? 'blocked' : 'allowed',
                    'threat_type' => $eventType,
                    'threat_score' => 0,
                    'created_at' => $log['created_at'] ?? date('Y-m-d H:i:s'),
                    'is_critical' => $isCritical,
                    'show_mitigated' => $isCritical && $isBlocked
                ];
            }, array_slice($recentLogs ?: [], 0, 5))
        ]
    ];
    
    // Garantir que todos os valores estejam definidos
    if (!isset($response['data']['top_countries'])) {
        $response['data']['top_countries'] = [];
    }
    if (!isset($response['data']['recent_incidents'])) {
        $response['data']['recent_incidents'] = [];
    }
    if (!isset($response['data']['top_blocked_ips'])) {
        $response['data']['top_blocked_ips'] = [];
    }
    if (!isset($response['data']['top_threat_types'])) {
        $response['data']['top_threat_types'] = [];
    }
    if (!isset($response['data']['event_logs'])) {
        $response['data']['event_logs'] = [];
    }
    
    // Análise Comportamental (BehaviorAnalyzer) - Opcional
    $behaviorStats = [];
    $behaviorAnalyzerPath = __DIR__ . '/../includes/BehaviorAnalyzer.php';
    if (file_exists($behaviorAnalyzerPath)) {
        try {
            require_once $behaviorAnalyzerPath;
            if (class_exists('BehaviorAnalyzer')) {
                $behaviorAnalyzer = new BehaviorAnalyzer($db);
                $behaviorStats = $behaviorAnalyzer->getBehaviorStats($currentSiteId, $userId, 10);
            }
        } catch (Exception $e) {
            error_log("SafeNode BehaviorAnalyzer Error: " . $e->getMessage());
            $behaviorStats = [];
        } catch (Error $e) {
            error_log("SafeNode BehaviorAnalyzer Fatal Error: " . $e->getMessage());
            $behaviorStats = [];
        }
    }
    
    // Security Analytics (análises avançadas) - Opcional
    $analytics = [];
    $securityAnalyticsPath = __DIR__ . '/../includes/SecurityAnalytics.php';
    if (file_exists($securityAnalyticsPath)) {
        try {
            require_once $securityAnalyticsPath;
            if (class_exists('SecurityAnalytics')) {
                $securityAnalytics = new SecurityAnalytics($db);
                
                // Insights automáticos
                $insights = $securityAnalytics->generateInsights($currentSiteId, $userId, 7);
                
                // IPs suspeitos
                $suspiciousIPs = $securityAnalytics->getSuspiciousIPs($currentSiteId, $userId, 7, 5);
                
                // Alvos mais atacados
                $attackedTargets = $securityAnalytics->getMostAttackedTargets($currentSiteId, $userId, 7, 5);
                
                // Ataques correlacionados
                $correlatedAttacks = $securityAnalytics->getCorrelatedAttacks($currentSiteId, $userId, 7, 5);
                
                // Padrões por horário
                $timePatterns = $securityAnalytics->getAttackPatternsByTime($currentSiteId, $userId, 7);
                
                $analytics = [
                    'insights' => $insights ?? [],
                    'suspicious_ips' => array_map(function($ip) {
                        return [
                            'ip_address' => $ip['ip_address'] ?? '',
                            'total_attacks' => (int)($ip['total_attacks'] ?? 0),
                            'attack_types_count' => (int)($ip['attack_types_count'] ?? 0),
                            'suspicion_score' => (int)($ip['suspicion_score'] ?? 0),
                            'threat_types' => $ip['threat_types'] ?? '',
                            'country_code' => $ip['country_code'] ?? null,
                            'last_seen' => $ip['last_seen'] ?? null
                        ];
                    }, $suspiciousIPs ?? []),
                    'attacked_targets' => array_map(function($target) {
                        return [
                            'request_uri' => substr($target['request_uri'] ?? '', 0, 200),
                            'attack_count' => (int)($target['attack_count'] ?? 0),
                            'unique_attackers' => (int)($target['unique_attackers'] ?? 0),
                            'threat_types_count' => (int)($target['threat_types_count'] ?? 0),
                            'avg_threat_score' => round((float)($target['avg_threat_score'] ?? 0), 2)
                        ];
                    }, $attackedTargets ?? []),
                    'correlated_attacks' => array_map(function($attack) {
                        return [
                            'ip_address' => $attack['ip_address'] ?? '',
                            'targets_count' => (int)($attack['targets_count'] ?? 0),
                            'total_attacks' => (int)($attack['total_attacks'] ?? 0),
                            'targets' => substr($attack['targets'] ?? '', 0, 200),
                            'country_code' => $attack['country_code'] ?? null
                        ];
                    }, $correlatedAttacks ?? []),
                    'time_patterns' => array_map(function($pattern) {
                        return [
                            'hour' => (int)($pattern['hour'] ?? 0),
                            'attack_count' => (int)($pattern['attack_count'] ?? 0),
                            'unique_attackers' => (int)($pattern['unique_attackers'] ?? 0),
                            'avg_threat_score' => round((float)($pattern['avg_threat_score'] ?? 0), 2)
                        ];
                    }, $timePatterns ?? [])
                ];
            }
        } catch (Exception $e) {
            error_log("SafeNode SecurityAnalytics Error: " . $e->getMessage());
            $analytics = [];
        } catch (Error $e) {
            error_log("SafeNode SecurityAnalytics Fatal Error: " . $e->getMessage());
            $analytics = [];
        }
    }
    
    // Adicionar metadados sobre fonte de dados
    $response['data_source'] = 'safenode_own'; // Indica que são dados próprios
    $response['data_source_info'] = [
        'primary' => 'safenode_security_logs',
        'cloudflare_sync' => false, // Será true quando CloudflareSync estiver ativo
        'independence' => true
    ];
    
    // Adicionar análises comportamentais e analytics
    $response['data']['behavior_analysis'] = $behaviorStats;
    $response['data']['analytics'] = $analytics;
    
    // Limpar qualquer output buffer inesperado
    ob_clean();
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (PDOException $e) {
    // Limpar output buffer
    ob_clean();
    
    http_response_code(500);
    error_log("SafeNode Dashboard Stats API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao buscar estatísticas',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    // Limpar output buffer
    ob_clean();
    
    http_response_code(500);
    error_log("SafeNode Dashboard Stats API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao processar requisição',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Error $e) {
    // Limpar output buffer
    ob_clean();
    
    http_response_code(500);
    error_log("SafeNode Dashboard Stats API Fatal Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Erro fatal ao processar requisição',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Finalizar output buffer
ob_end_flush();
?>

