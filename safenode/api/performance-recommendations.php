<?php
/**
 * SafeNode - API de Recomendações de Performance
 * Analisa dados de performance e sugere otimizações
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
    error_log("SafeNode Performance Recommendations Error: " . $e->getMessage());
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
$timeframe = $_GET['timeframe'] ?? '7d';

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
        $siteFilter = "AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
        $params[] = $userId;
    }
    
    // Verificar se tabela existe (tentando uma query simples)
    $tableExists = false;
    try {
        $stmt = $db->query("SELECT 1 FROM safenode_performance_logs LIMIT 1");
        $tableExists = true;
    } catch (PDOException $e) {
        // Tabela não existe ou erro de acesso
        $tableExists = false;
    }
    
    if (!$tableExists) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'timestamp' => time(),
            'timeframe' => $timeframe,
            'data' => [
                'recommendations' => [],
                'message' => 'Tabela de performance ainda não foi criada. Execute o SQL da Fase 2.'
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ob_end_flush();
        exit;
    }
    
    // Buscar dados de performance para análise
    $sqlPerformance = "SELECT 
        endpoint,
        AVG(response_time) as avg_response_time,
        MAX(response_time) as max_response_time,
        COUNT(*) as request_count,
        AVG(memory_usage) as avg_memory_usage,
        SUM(CASE WHEN response_time > 2000 THEN 1 ELSE 0 END) as very_slow_count
        FROM safenode_performance_logs 
        WHERE 1=1
        " . (!empty($whereTime) ? $whereTime : '') . "
        " . (!empty($siteFilter) ? $siteFilter : '') . "
        GROUP BY endpoint
        HAVING request_count >= 5";
    
    $stmtPerformance = $db->prepare($sqlPerformance);
    if ($stmtPerformance === false) {
        throw new Exception("Erro ao preparar query: " . implode(", ", $db->errorInfo()));
    }
    if (!empty($params)) {
        $stmtPerformance->execute($params);
    } else {
        $stmtPerformance->execute();
    }
    $performanceData = $stmtPerformance->fetchAll(PDO::FETCH_ASSOC);
    
    // Gerar recomendações
    $recommendations = [];
    $recommendationId = 1;
    
    foreach ($performanceData as $data) {
        $avgTime = (float)$data['avg_response_time'];
        $maxTime = (int)$data['max_response_time'];
        $requestCount = (int)$data['request_count'];
        $verySlowCount = (int)$data['very_slow_count'];
        $avgMemoryMB = round((float)($data['avg_memory_usage'] ?? 0) / 1048576, 2);
        $endpoint = $data['endpoint'];
        
        // Recomendação 1: Endpoint muito lento (> 2s em média)
        if ($avgTime > 2000) {
            $recommendations[] = [
                'id' => $recommendationId++,
                'type' => 'slow_endpoint',
                'severity' => 'high',
                'title' => "Endpoint {$endpoint} muito lento",
                'description' => "O endpoint {$endpoint} está demorando em média " . round($avgTime) . "ms para responder. Isso impacta a experiência do usuário.",
                'suggestion' => "Revise o código deste endpoint. Considere: (1) Otimizar queries do banco de dados, (2) Adicionar cache, (3) Reduzir processamento pesado, (4) Usar índices no banco se necessário.",
                'affected_endpoints' => [$endpoint],
                'metrics' => [
                    'avg_response_time' => round($avgTime, 2),
                    'max_response_time' => $maxTime,
                    'request_count' => $requestCount
                ],
                'recommended_action' => 'Otimizar endpoint',
                'impact' => 'Alto - Impacta experiência do usuário'
            ];
        }
        
        // Recomendação 2: Endpoint com picos de lentidão
        if ($maxTime > 5000 && $avgTime < 2000) {
            $recommendations[] = [
                'id' => $recommendationId++,
                'type' => 'spike_performance',
                'severity' => 'medium',
                'title' => "Endpoint {$endpoint} com picos de lentidão",
                'description' => "O endpoint {$endpoint} tem picos de até {$maxTime}ms, mesmo com média de " . round($avgTime) . "ms. Isso indica inconsistência de performance.",
                'suggestion' => "Investigue o que causa os picos. Pode ser: (1) Queries sem índice em casos específicos, (2) Processamento condicional pesado, (3) Falta de cache para dados específicos, (4) Recursos externos lentos.",
                'affected_endpoints' => [$endpoint],
                'metrics' => [
                    'avg_response_time' => round($avgTime, 2),
                    'max_response_time' => $maxTime,
                    'variance' => round($maxTime - $avgTime, 2)
                ],
                'recommended_action' => 'Investigar inconsistências',
                'impact' => 'Médio - Pode afetar alguns usuários'
            ];
        }
        
        // Recomendação 3: Endpoint com muitas requisições lentas
        $slowRatio = $verySlowCount / max($requestCount, 1);
        if ($slowRatio > 0.3 && $requestCount >= 10) {
            $recommendations[] = [
                'id' => $recommendationId++,
                'type' => 'high_slow_ratio',
                'severity' => 'high',
                'title' => "Endpoint {$endpoint} tem muitas requisições lentas",
                'description' => "O endpoint {$endpoint} tem " . round($slowRatio * 100) . "% das requisições acima de 2 segundos. Isso é um problema crônico de performance.",
                'suggestion' => "Este endpoint precisa de otimização urgente. Ações recomendadas: (1) Profiling do código para identificar gargalos, (2) Implementar cache agressivo, (3) Revisar queries de banco de dados, (4) Considerar otimização de algoritmos ou refatoração.",
                'affected_endpoints' => [$endpoint],
                'metrics' => [
                    'slow_ratio' => round($slowRatio * 100, 2),
                    'slow_count' => $verySlowCount,
                    'total_count' => $requestCount
                ],
                'recommended_action' => 'Otimização urgente',
                'impact' => 'Alto - Muitos usuários afetados'
            ];
        }
        
        // Recomendação 4: Uso excessivo de memória
        if ($avgMemoryMB > 50) {
            $recommendations[] = [
                'id' => $recommendationId++,
                'type' => 'high_memory_usage',
                'severity' => 'medium',
                'title' => "Endpoint {$endpoint} usa muita memória",
                'description' => "O endpoint {$endpoint} está usando em média {$avgMemoryMB}MB de memória por requisição. Isso pode causar problemas de escalabilidade.",
                'suggestion' => "Reduza o uso de memória: (1) Processar dados em chunks ao invés de carregar tudo na memória, (2) Usar generators ao invés de arrays grandes, (3) Limpar variáveis grandes após uso, (4) Revisar se há memory leaks.",
                'affected_endpoints' => [$endpoint],
                'metrics' => [
                    'avg_memory_mb' => $avgMemoryMB
                ],
                'recommended_action' => 'Otimizar uso de memória',
                'impact' => 'Médio - Pode limitar escalabilidade'
            ];
        }
    }
    
    // Ordenar por severidade
    $severityOrder = ['high' => 2, 'medium' => 1, 'low' => 0];
    usort($recommendations, function($a, $b) use ($severityOrder) {
        $severityDiff = ($severityOrder[$b['severity']] ?? 0) - ($severityOrder[$a['severity']] ?? 0);
        if ($severityDiff !== 0) {
            return $severityDiff;
        }
        return ($b['metrics']['avg_response_time'] ?? 0) - ($a['metrics']['avg_response_time'] ?? 0);
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
                    'high' => count(array_filter($recommendations, fn($r) => $r['severity'] === 'high')),
                    'medium' => count(array_filter($recommendations, fn($r) => $r['severity'] === 'medium')),
                    'low' => count(array_filter($recommendations, fn($r) => $r['severity'] === 'low'))
                ]
            ]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    
} catch (PDOException $e) {
    ob_clean();
    error_log("SafeNode Performance Recommendations DB Error: " . $e->getMessage());
    error_log("SQL: " . ($sqlPerformance ?? 'N/A'));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
} catch (Exception $e) {
    ob_clean();
    error_log("SafeNode Performance Recommendations Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
}

