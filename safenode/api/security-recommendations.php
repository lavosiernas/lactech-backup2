<?php
/**
 * SafeNode - API de Recomendações de Segurança
 * Analisa padrões de ataques e sugere correções práticas
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/init.php';
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    error_log("SafeNode Security Recommendations Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao carregar configurações'
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
        'data' => []
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    exit;
}

// Verificar que o site pertence ao usuário
if ($currentSiteId > 0) {
    try {
        $stmt = $db->prepare("SELECT id FROM safenode_sites WHERE id = ? AND user_id = ?");
        $stmt->execute([$currentSiteId, $userId]);
        if (!$stmt->fetch()) {
            $currentSiteId = 0;
        }
    } catch (PDOException $e) {
        $currentSiteId = 0;
    }
}

// Parâmetros
$timeframe = $_GET['timeframe'] ?? '7d'; // 7d, 30d

// Calcular período
$whereTime = '';
switch ($timeframe) {
    case '30d':
        $whereTime = "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    default: // 7d
        $whereTime = "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
}

try {
    // Preparar filtro de site/usuário
    $siteFilter = '';
    $params = [];
    
    if ($currentSiteId > 0) {
        $siteFilter = "AND site_id = ?";
        $params[] = $currentSiteId;
    } elseif ($userId) {
        $siteFilter = "AND (
            site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)
            OR api_key_id IN (SELECT id FROM safenode_hv_api_keys WHERE user_id = ?)
        )";
        $params[] = $userId;
        $params[] = $userId;
    }
    
    // Função para detectar tipo de ameaça
    if (!function_exists('detectThreatType')) {
        function detectThreatType($reason, $requestUri) {
        $reason = strtolower($reason ?? '');
        $uri = strtolower($requestUri ?? '');
        $data = $reason . ' ' . $uri;
        
        if (preg_match('/(sql|union|select|information_schema)/i', $data)) {
            return 'sql_injection';
        }
        if (preg_match('/(<script|javascript:|onerror=|eval\s*\()/i', $data)) {
            return 'xss';
        }
        if (preg_match('/(;\s*(ping|whoami|ls|cat)\b|\$\(|\`)/i', $data)) {
            return 'command_injection';
        }
        if (preg_match('/(\.\.\/|\/etc\/passwd)/i', $data)) {
            return 'path_traversal';
        }
        if (preg_match('/(php:\/\/|eval\s*\(|exec\s*\()/i', $data)) {
            return 'rce_php';
        }
        if (preg_match('/(brute|force|login|password)/i', $reason)) {
            return 'brute_force';
        }
        
        return 'unknown';
        }
    }
    
    // Buscar ataques bloqueados
    $sqlAttacks = "SELECT 
        SUBSTRING_INDEX(SUBSTRING_INDEX(request_uri, '?', 1), '#', 1) as endpoint,
        reason,
        request_uri,
        ip_address,
        created_at
        FROM safenode_human_verification_logs 
        WHERE event_type = 'bot_blocked'
        " . (!empty($whereTime) ? $whereTime : '') . "
        " . (!empty($siteFilter) ? $siteFilter : '') . "
        ORDER BY created_at DESC";
    
    $stmtAttacks = $db->prepare($sqlAttacks);
    if ($stmtAttacks === false) {
        throw new Exception("Erro ao preparar query: " . implode(", ", $db->errorInfo()));
    }
    if (!empty($params)) {
        $stmtAttacks->execute($params);
    } else {
        $stmtAttacks->execute();
    }
    $attacks = $stmtAttacks->fetchAll(PDO::FETCH_ASSOC);
    
    // Analisar padrões e gerar recomendações
    $recommendations = [];
    $endpointThreats = []; // endpoint => [tipo => count]
    $threatTypeCounts = [];
    
    foreach ($attacks as $attack) {
        $reason = $attack['reason'] ?? '';
        $requestUri = $attack['request_uri'] ?? '';
        // Se não há reason, tentar detectar pelo request_uri
        if (empty($reason) && !empty($requestUri)) {
            $reason = $requestUri; // Usar URI para detecção se não houver reason
        }
        $threatType = detectThreatType($reason, $requestUri);
        $endpoint = $attack['endpoint'] ?? '/';
        
        if (!isset($endpointThreats[$endpoint])) {
            $endpointThreats[$endpoint] = [];
        }
        if (!isset($endpointThreats[$endpoint][$threatType])) {
            $endpointThreats[$endpoint][$threatType] = 0;
        }
        $endpointThreats[$endpoint][$threatType]++;
        
        if (!isset($threatTypeCounts[$threatType])) {
            $threatTypeCounts[$threatType] = 0;
        }
        $threatTypeCounts[$threatType]++;
    }
    
    // Gerar recomendações baseadas em padrões
    $recommendationId = 1;
    
    // 1. SQL Injection em endpoints específicos
    foreach ($endpointThreats as $endpoint => $threats) {
        if (isset($threats['sql_injection']) && $threats['sql_injection'] >= 5) {
            $count = $threats['sql_injection'];
            $recommendations[] = [
                'id' => $recommendationId++,
                'type' => 'sql_injection',
                'severity' => 'high',
                'title' => "Vulnerabilidade SQL Injection detectada em $endpoint",
                'description' => "Você recebeu $count tentativas de SQL injection no endpoint $endpoint. Seu código pode estar vulnerável.",
                'suggestion' => "Use prepared statements ao invés de concatenação de strings. Exemplo: `$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?'); $stmt->execute([$id]);`",
                'affected_endpoints' => [$endpoint],
                'attack_count' => $count,
                'recommended_action' => 'Implementar prepared statements',
                'impact' => 'Alto - Risco de vazamento de dados'
            ];
        }
    }
    
    // 2. XSS em endpoints específicos
    foreach ($endpointThreats as $endpoint => $threats) {
        if (isset($threats['xss']) && $threats['xss'] >= 5) {
            $count = $threats['xss'];
            $recommendations[] = [
                'id' => $recommendationId++,
                'type' => 'xss',
                'severity' => 'high',
                'title' => "Vulnerabilidade XSS detectada em $endpoint",
                'description' => "Você recebeu $count tentativas de XSS (Cross-Site Scripting) no endpoint $endpoint. Seus formulários podem estar vulneráveis.",
                'suggestion' => "Use htmlspecialchars() ou filter_var() ao exibir dados do usuário. Exemplo: `echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');`",
                'affected_endpoints' => [$endpoint],
                'attack_count' => $count,
                'recommended_action' => 'Sanitizar saída de dados',
                'impact' => 'Alto - Risco de roubo de sessão/cookies'
            ];
        }
    }
    
    // 3. Command Injection
    if (isset($threatTypeCounts['command_injection']) && $threatTypeCounts['command_injection'] >= 3) {
        $count = $threatTypeCounts['command_injection'];
        $recommendations[] = [
            'id' => $recommendationId++,
            'type' => 'command_injection',
            'severity' => 'critical',
            'title' => 'Tentativas de Command Injection detectadas',
            'description' => "Você recebeu $count tentativas de command injection. Seu código pode estar executando comandos do sistema sem validação.",
            'suggestion' => 'NUNCA use exec(), system(), shell_exec() com entrada do usuário. Use funções nativas do PHP quando possível. Se precisar executar comandos, valide e escape completamente.',
            'affected_endpoints' => array_keys(array_filter($endpointThreats, fn($t) => isset($t['command_injection']))),
            'attack_count' => $count,
            'recommended_action' => 'Remover execução de comandos ou validar rigorosamente',
            'impact' => 'Crítico - Risco de controle total do servidor'
        ];
    }
    
    // 4. Path Traversal
    if (isset($threatTypeCounts['path_traversal']) && $threatTypeCounts['path_traversal'] >= 3) {
        $count = $threatTypeCounts['path_traversal'];
        $recommendations[] = [
            'id' => $recommendationId++,
            'type' => 'path_traversal',
            'severity' => 'high',
            'title' => 'Tentativas de Path Traversal detectadas',
            'description' => "Você recebeu $count tentativas de path traversal. Seu código pode estar lendo arquivos sem validação de caminho.",
            'suggestion' => 'Valide caminhos de arquivos. Use basename() ou realpath() e verifique se o arquivo está dentro do diretório permitido antes de ler.',
            'affected_endpoints' => array_keys(array_filter($endpointThreats, fn($t) => isset($t['path_traversal']))),
            'attack_count' => $count,
            'recommended_action' => 'Validar e sanitizar caminhos de arquivos',
            'impact' => 'Alto - Risco de vazamento de arquivos do servidor'
        ];
    }
    
    // 5. Brute Force em login
    if (isset($threatTypeCounts['brute_force']) && $threatTypeCounts['brute_force'] >= 10) {
        $count = $threatTypeCounts['brute_force'];
        $loginEndpoints = array_filter($endpointThreats, fn($endpoint, $threats) => 
            (stripos($endpoint, '/login') !== false || stripos($endpoint, '/auth') !== false) &&
            isset($threats['brute_force']),
            ARRAY_FILTER_USE_BOTH
        );
        
        if (!empty($loginEndpoints)) {
            $recommendations[] = [
                'id' => $recommendationId++,
                'type' => 'brute_force',
                'severity' => 'medium',
                'title' => 'Tentativas de Brute Force em páginas de login',
                'description' => "Você recebeu $count tentativas de brute force em páginas de login. Considere implementar rate limiting mais agressivo.",
                'suggestion' => 'Implemente: (1) Rate limiting por IP (máx 5 tentativas/minuto), (2) CAPTCHA após 3 tentativas, (3) Bloqueio temporário após múltiplas falhas.',
                'affected_endpoints' => array_keys($loginEndpoints),
                'attack_count' => $count,
                'recommended_action' => 'Implementar rate limiting e CAPTCHA',
                'impact' => 'Médio - Risco de comprometimento de contas'
            ];
        }
    }
    
    // 6. Múltiplos tipos de ataque em mesmo endpoint (endpoint vulnerável)
    foreach ($endpointThreats as $endpoint => $threats) {
        $totalThreats = array_sum($threats);
        $uniqueThreatTypes = count($threats);
        
        if ($totalThreats >= 10 && $uniqueThreatTypes >= 2) {
            $recommendations[] = [
                'id' => $recommendationId++,
                'type' => 'multiple_vulnerabilities',
                'severity' => 'high',
                'title' => "Endpoint $endpoint altamente visado",
                'description' => "O endpoint $endpoint recebeu $totalThreats tentativas de ataque de $uniqueThreatTypes tipos diferentes. Este endpoint pode ter múltiplas vulnerabilidades.",
                'suggestion' => 'Realize uma auditoria completa deste endpoint. Revise: validação de entrada, sanitização de saída, autenticação, autorização e configurações de segurança.',
                'affected_endpoints' => [$endpoint],
                'attack_count' => $totalThreats,
                'threat_types' => array_keys($threats),
                'recommended_action' => 'Auditoria completa de segurança',
                'impact' => 'Alto - Múltiplos vetores de ataque'
            ];
        }
    }
    
    // Ordenar por severidade e contagem
    usort($recommendations, function($a, $b) {
        $severityOrder = ['critical' => 3, 'high' => 2, 'medium' => 1, 'low' => 0];
        $severityDiff = ($severityOrder[$b['severity']] ?? 0) - ($severityOrder[$a['severity']] ?? 0);
        if ($severityDiff !== 0) {
            return $severityDiff;
        }
        return ($b['attack_count'] ?? 0) - ($a['attack_count'] ?? 0);
    });
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'timestamp' => time(),
        'timeframe' => $timeframe,
        'data' => [
            'recommendations' => $recommendations,
            'stats' => [
                'total_recommendations' => count($recommendations),
                'by_severity' => [
                    'critical' => count(array_filter($recommendations, fn($r) => $r['severity'] === 'critical')),
                    'high' => count(array_filter($recommendations, fn($r) => $r['severity'] === 'high')),
                    'medium' => count(array_filter($recommendations, fn($r) => $r['severity'] === 'medium')),
                    'low' => count(array_filter($recommendations, fn($r) => $r['severity'] === 'low'))
                ],
                'threat_type_counts' => $threatTypeCounts,
                'affected_endpoints_count' => count(array_unique(array_merge(...array_map(fn($r) => $r['affected_endpoints'], $recommendations))))
            ]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    
} catch (PDOException $e) {
    ob_clean();
    error_log("SafeNode Security Recommendations DB Error: " . $e->getMessage());
    error_log("SQL: " . ($sqlAttacks ?? 'N/A'));
    error_log("Params: " . print_r($params ?? [], true));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
} catch (Exception $e) {
    ob_clean();
    error_log("SafeNode Security Recommendations Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
}

