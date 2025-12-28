<?php
/**
 * SafeNode - Behavior Analyzer (FUNCIONAL)
 * Sistema de análise comportamental avançado de IPs e sessões
 * 
 * STATUS: FUNCIONAL - Análise comportamental completa com estatísticas e padrões
 * 
 * Funcionalidades:
 * - Análise estatística comparativa com comportamento "normal"
 * - Detecção de anomalias baseada em desvios padrão
 * - Análise de sequências de ações
 * - Padrões de navegação suspeitos
 * - Machine learning básico para classificação comportamental
 * - Perfil comportamental por IP
 */

class BehaviorAnalyzer {
    private $db;
    private $cache;
    private $baselineBehavior;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
        $this->baselineBehavior = null;
    }
    
    /**
     * Analisa comportamento de um IP com análise estatística avançada
     */
    public function analyzeIPBehavior($ipAddress, $timeWindow = 3600) {
        if (!$this->db) {
            return [
                'risk_level' => 'unknown',
                'risk_score' => 0,
                'behaviors' => [],
                'anomalies' => [],
                'confidence' => 0
            ];
        }
        
        // Verificar cache
        $cacheKey = "behavior_analysis:$ipAddress:$timeWindow:" . date('H');
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            // Carregar baseline de comportamento normal se necessário
            if ($this->baselineBehavior === null) {
                $this->loadBaselineBehavior();
            }
            
            $behaviors = [];
            $anomalies = [];
            $riskScore = 0;
            $confidenceScores = [];
            
            // 1. Análise de frequência de requisições (estatística avançada)
            $frequencyAnalysis = $this->analyzeRequestFrequencyAdvanced($ipAddress, $timeWindow);
            if ($frequencyAnalysis['is_anomaly']) {
                $anomalies[] = $frequencyAnalysis;
                $riskScore += $frequencyAnalysis['severity'];
            }
            $behaviors['frequency'] = $frequencyAnalysis;
            $confidenceScores[] = $frequencyAnalysis['confidence'] ?? 0.5;
            
            // 2. Análise de padrão de URIs (melhorada)
            $uriPatternAnalysis = $this->analyzeURIPatternsAdvanced($ipAddress, $timeWindow);
            if ($uriPatternAnalysis['is_anomaly']) {
                $anomalies[] = $uriPatternAnalysis;
                $riskScore += $uriPatternAnalysis['severity'];
            }
            $behaviors['uri_patterns'] = $uriPatternAnalysis;
            $confidenceScores[] = $uriPatternAnalysis['confidence'] ?? 0.5;
            
            // 3. Análise de User-Agents (melhorada)
            $userAgentAnalysis = $this->analyzeUserAgentsAdvanced($ipAddress, $timeWindow);
            if ($userAgentAnalysis['is_anomaly']) {
                $anomalies[] = $userAgentAnalysis;
                $riskScore += $userAgentAnalysis['severity'];
            }
            $behaviors['user_agents'] = $userAgentAnalysis;
            $confidenceScores[] = $userAgentAnalysis['confidence'] ?? 0.5;
            
            // 4. Análise de horários de acesso (melhorada)
            $timePatternAnalysis = $this->analyzeTimePatternsAdvanced($ipAddress);
            if ($timePatternAnalysis['is_anomaly']) {
                $anomalies[] = $timePatternAnalysis;
                $riskScore += $timePatternAnalysis['severity'];
            }
            $behaviors['time_patterns'] = $timePatternAnalysis;
            $confidenceScores[] = $timePatternAnalysis['confidence'] ?? 0.5;
            
            // 5. Análise de taxa de erro (melhorada)
            $errorRateAnalysis = $this->analyzeErrorRateAdvanced($ipAddress, $timeWindow);
            if ($errorRateAnalysis['is_anomaly']) {
                $anomalies[] = $errorRateAnalysis;
                $riskScore += $errorRateAnalysis['severity'];
            }
            $behaviors['error_rate'] = $errorRateAnalysis;
            $confidenceScores[] = $errorRateAnalysis['confidence'] ?? 0.5;
            
            // 6. Análise de progressão de ameaças (melhorada)
            $threatProgression = $this->analyzeThreatProgressionAdvanced($ipAddress, $timeWindow);
            if ($threatProgression['is_anomaly']) {
                $anomalies[] = $threatProgression;
                $riskScore += $threatProgression['severity'];
            }
            $behaviors['threat_progression'] = $threatProgression;
            $confidenceScores[] = $threatProgression['confidence'] ?? 0.5;
            
            // 7. Análise de sequências de ações (NOVO)
            $sequenceAnalysis = $this->analyzeActionSequences($ipAddress, $timeWindow);
            if ($sequenceAnalysis['is_anomaly']) {
                $anomalies[] = $sequenceAnalysis;
                $riskScore += $sequenceAnalysis['severity'];
            }
            $behaviors['action_sequences'] = $sequenceAnalysis;
            $confidenceScores[] = $sequenceAnalysis['confidence'] ?? 0.5;
            
            // 8. Análise de padrão de navegação (NOVO)
            $navigationAnalysis = $this->analyzeNavigationPattern($ipAddress, $timeWindow);
            if ($navigationAnalysis['is_anomaly']) {
                $anomalies[] = $navigationAnalysis;
                $riskScore += $navigationAnalysis['severity'];
            }
            $behaviors['navigation_pattern'] = $navigationAnalysis;
            $confidenceScores[] = $navigationAnalysis['confidence'] ?? 0.5;
            
            // 9. Comparação com baseline comportamental (NOVO)
            $baselineComparison = $this->compareWithBaseline($behaviors);
            if ($baselineComparison['deviation'] > 2.0) { // Mais de 2 desvios padrão
                $anomalies[] = $baselineComparison;
                $riskScore += min(30, $baselineComparison['deviation'] * 10);
            }
            $behaviors['baseline_comparison'] = $baselineComparison;
            
            // Calcular confiança geral (média das confianças individuais)
            $overallConfidence = count($confidenceScores) > 0 
                ? array_sum($confidenceScores) / count($confidenceScores) 
                : 0.5;
            
            // Ajustar score baseado em confiança
            $adjustedRiskScore = $riskScore * $overallConfidence;
            
            // Determinar nível de risco
            $riskLevel = 'low';
            if ($adjustedRiskScore >= 70) {
                $riskLevel = 'critical';
            } elseif ($adjustedRiskScore >= 50) {
                $riskLevel = 'high';
            } elseif ($adjustedRiskScore >= 30) {
                $riskLevel = 'medium';
            }
            
            $result = [
                'risk_level' => $riskLevel,
                'risk_score' => round(min(100, $adjustedRiskScore), 2),
                'raw_risk_score' => round(min(100, $riskScore), 2),
                'confidence' => round($overallConfidence, 3),
                'behaviors' => $behaviors,
                'anomalies' => $anomalies,
                'anomaly_count' => count($anomalies),
                'analysis_timestamp' => date('Y-m-d H:i:s')
            ];
            
            // Cache por 5 minutos
            $this->cache->set($cacheKey, $result, 300);
            
            return $result;
        } catch (PDOException $e) {
            error_log("SafeNode BehaviorAnalyzer Error: " . $e->getMessage());
            return [
                'risk_level' => 'unknown',
                'risk_score' => 0,
                'behaviors' => [],
                'anomalies' => [],
                'confidence' => 0
            ];
        }
    }
    
    /**
     * Carrega baseline de comportamento normal (estatísticas globais)
     */
    private function loadBaselineBehavior() {
        $cacheKey = 'behavior_baseline';
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            $this->baselineBehavior = $cached;
            return;
        }
        
        if (!$this->db) {
            $this->baselineBehavior = [];
            return;
        }
        
        try {
            // Calcular estatísticas de comportamento normal nos últimos 30 dias
            $stmt = $this->db->query("
                SELECT 
                    AVG(request_count) as avg_requests_per_hour,
                    STDDEV(request_count) as stddev_requests_per_hour,
                    AVG(unique_uris) as avg_unique_uris,
                    STDDEV(unique_uris) as stddev_unique_uris,
                    AVG(unique_user_agents) as avg_user_agents,
                    STDDEV(unique_user_agents) as stddev_user_agents,
                    AVG(block_rate) as avg_block_rate,
                    STDDEV(block_rate) as stddev_block_rate
                FROM (
                    SELECT 
                        ip_address,
                        COUNT(*) as request_count,
                        COUNT(DISTINCT request_uri) as unique_uris,
                        COUNT(DISTINCT user_agent) as unique_user_agents,
                        (SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) / COUNT(*)) * 100 as block_rate
                    FROM safenode_security_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    AND action_taken = 'allowed'
                    GROUP BY ip_address, DATE_FORMAT(created_at, '%Y-%m-%d %H')
                ) as hourly_stats
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->baselineBehavior = [
                'requests_per_hour' => [
                    'mean' => (float)($result['avg_requests_per_hour'] ?? 10),
                    'stddev' => (float)($result['stddev_requests_per_hour'] ?? 5)
                ],
                'unique_uris' => [
                    'mean' => (float)($result['avg_unique_uris'] ?? 5),
                    'stddev' => (float)($result['stddev_unique_uris'] ?? 3)
                ],
                'unique_user_agents' => [
                    'mean' => (float)($result['avg_user_agents'] ?? 1.5),
                    'stddev' => (float)($result['stddev_user_agents'] ?? 0.5)
                ],
                'block_rate' => [
                    'mean' => (float)($result['avg_block_rate'] ?? 5),
                    'stddev' => (float)($result['stddev_block_rate'] ?? 10)
                ]
            ];
            
            // Cache por 6 horas
            $this->cache->set($cacheKey, $this->baselineBehavior, 21600);
        } catch (PDOException $e) {
            $this->baselineBehavior = [];
        }
    }
    
    /**
     * Compara comportamento atual com baseline
     */
    private function compareWithBaseline($behaviors) {
        if (empty($this->baselineBehavior)) {
            return [
                'is_anomaly' => false,
                'deviation' => 0,
                'comparisons' => []
            ];
        }
        
        $deviations = [];
        $maxDeviation = 0;
        
        // Comparar frequência
        if (isset($behaviors['frequency']['requests_per_minute'])) {
            $current = $behaviors['frequency']['requests_per_minute'] * 60; // Por hora
            $mean = $this->baselineBehavior['requests_per_hour']['mean'];
            $stddev = $this->baselineBehavior['requests_per_hour']['stddev'];
            
            if ($stddev > 0) {
                $zScore = ($current - $mean) / $stddev;
                $deviations['requests_per_hour'] = $zScore;
                $maxDeviation = max($maxDeviation, abs($zScore));
            }
        }
        
        // Comparar URIs únicas
        if (isset($behaviors['uri_patterns']['unique_uris'])) {
            $current = $behaviors['uri_patterns']['unique_uris'];
            $mean = $this->baselineBehavior['unique_uris']['mean'];
            $stddev = $this->baselineBehavior['unique_uris']['stddev'];
            
            if ($stddev > 0) {
                $zScore = ($current - $mean) / $stddev;
                $deviations['unique_uris'] = $zScore;
                $maxDeviation = max($maxDeviation, abs($zScore));
            }
        }
        
        // Comparar User-Agents
        if (isset($behaviors['user_agents']['unique_user_agents'])) {
            $current = $behaviors['user_agents']['unique_user_agents'];
            $mean = $this->baselineBehavior['unique_user_agents']['mean'];
            $stddev = $this->baselineBehavior['unique_user_agents']['stddev'];
            
            if ($stddev > 0) {
                $zScore = ($current - $mean) / $stddev;
                $deviations['unique_user_agents'] = $zScore;
                $maxDeviation = max($maxDeviation, abs($zScore));
            }
        }
        
        return [
            'is_anomaly' => $maxDeviation > 2.0,
            'deviation' => $maxDeviation,
            'comparisons' => $deviations,
            'baseline' => $this->baselineBehavior
        ];
    }
    
    /**
     * Analisa frequência de requisições com análise estatística avançada
     */
    private function analyzeRequestFrequencyAdvanced($ipAddress, $timeWindow) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_requests,
                    COUNT(DISTINCT DATE_FORMAT(created_at, '%Y-%m-%d %H:%i')) as unique_minutes,
                    AVG(UNIX_TIMESTAMP(created_at) - UNIX_TIMESTAMP(LAG(created_at) OVER (ORDER BY created_at))) as avg_interval
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$ipAddress, $timeWindow]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || $result['total_requests'] == 0) {
                return ['is_anomaly' => false, 'severity' => 0];
            }
            
            $totalRequests = (int)$result['total_requests'];
            $uniqueMinutes = (int)$result['unique_minutes'];
            $requestsPerMinute = $uniqueMinutes > 0 ? $totalRequests / $uniqueMinutes : 0;
            
            $isAnomaly = false;
            $severity = 0;
            
            // Muitas requisições por minuto (possível bot/script)
            if ($requestsPerMinute > 10) {
                $isAnomaly = true;
                $severity = min(30, $requestsPerMinute * 2);
            }
            
            // Requisições muito rápidas (intervalo médio < 1 segundo)
            if ($result['avg_interval'] && $result['avg_interval'] < 1) {
                $isAnomaly = true;
                $severity += 20;
            }
            
            // Calcular intervalos entre requisições manualmente se LAG não funcionar
            if (!isset($result['avg_interval']) || $result['avg_interval'] === null) {
                $stmt2 = $this->db->prepare("
                    SELECT UNIX_TIMESTAMP(created_at) as timestamp
                    FROM safenode_security_logs
                    WHERE ip_address = ?
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                    ORDER BY created_at ASC
                    LIMIT 100
                ");
                $stmt2->execute([$ipAddress, $timeWindow]);
                $timestamps = $stmt2->fetchAll(PDO::FETCH_COLUMN);
                
                if (count($timestamps) > 1) {
                    $intervals = [];
                    for ($i = 1; $i < count($timestamps); $i++) {
                        $intervals[] = $timestamps[$i] - $timestamps[$i-1];
                    }
                    $avgInterval = count($intervals) > 0 ? array_sum($intervals) / count($intervals) : null;
                } else {
                    $avgInterval = null;
                }
            } else {
                $avgInterval = $result['avg_interval'];
            }
            
            // Calcular confiança baseada em quantidade de dados
            $confidence = min(1.0, $totalRequests / 50); // Mais dados = mais confiança
            
            // Comparar com baseline se disponível
            $baselineDeviation = 0;
            if (!empty($this->baselineBehavior)) {
                $baselineMean = $this->baselineBehavior['requests_per_hour']['mean'] ?? 10;
                $baselineStddev = $this->baselineBehavior['requests_per_hour']['stddev'] ?? 5;
                $currentPerHour = $requestsPerMinute * 60;
                
                if ($baselineStddev > 0) {
                    $baselineDeviation = ($currentPerHour - $baselineMean) / $baselineStddev;
                    // Se desvio > 2, aumentar severidade
                    if (abs($baselineDeviation) > 2) {
                        $severity += abs($baselineDeviation) * 5;
                        $isAnomaly = true;
                    }
                }
            }
            
            return [
                'is_anomaly' => $isAnomaly,
                'severity' => min(50, $severity),
                'confidence' => $confidence,
                'total_requests' => $totalRequests,
                'requests_per_minute' => round($requestsPerMinute, 2),
                'avg_interval' => $avgInterval ? round($avgInterval, 2) : null,
                'baseline_deviation' => round($baselineDeviation, 2)
            ];
        } catch (PDOException $e) {
            // Se LAG não funcionar (MySQL < 8.0), usar método alternativo
            try {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as total_requests
                    FROM safenode_security_logs
                    WHERE ip_address = ?
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                ");
                $stmt->execute([$ipAddress, $timeWindow]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $totalRequests = (int)($result['total_requests'] ?? 0);
                
                $isAnomaly = $totalRequests > 100;
                $severity = $isAnomaly ? min(30, ($totalRequests / 100) * 10) : 0;
                
                return [
                    'is_anomaly' => $isAnomaly,
                    'severity' => $severity,
                    'total_requests' => $totalRequests
                ];
            } catch (PDOException $e2) {
                return ['is_anomaly' => false, 'severity' => 0];
            }
        }
    }
    
    /**
     * Analisa padrões de URIs acessadas (versão avançada)
     */
    private function analyzeURIPatternsAdvanced($ipAddress, $timeWindow) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    request_uri,
                    COUNT(*) as access_count,
                    COUNT(DISTINCT DATE_FORMAT(created_at, '%Y-%m-%d %H:%i')) as unique_times
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                GROUP BY request_uri
                ORDER BY access_count DESC
                LIMIT 20
            ");
            $stmt->execute([$ipAddress, $timeWindow]);
            $uris = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $isAnomaly = false;
            $severity = 0;
            $suspiciousPatterns = [];
            
            foreach ($uris as $uri) {
                $uriPath = parse_url($uri['request_uri'], PHP_URL_PATH);
                
                // Muitas tentativas na mesma URI (possível brute force)
                if ($uri['access_count'] > 20) {
                    $isAnomaly = true;
                    $severity += 15;
                    $suspiciousPatterns[] = [
                        'uri' => substr($uriPath, 0, 100),
                        'count' => $uri['access_count'],
                        'reason' => 'Múltiplas tentativas na mesma URI'
                    ];
                }
                
                // URIs suspeitas (admin, config, etc)
                $suspiciousPaths = ['/admin', '/wp-admin', '/phpmyadmin', '/.env', '/config', '/.git'];
                foreach ($suspiciousPaths as $path) {
                    if (stripos($uriPath, $path) !== false) {
                        $isAnomaly = true;
                        $severity += 20;
                        $suspiciousPatterns[] = [
                            'uri' => substr($uriPath, 0, 100),
                            'count' => $uri['access_count'],
                            'reason' => 'Acesso a rota suspeita'
                        ];
                        break;
                    }
                }
            }
            
            // Análise de entropia de URIs (diversidade)
            $entropy = $this->calculateEntropy(array_column($uris, 'access_count'));
            
            // Calcular confiança
            $confidence = min(1.0, count($uris) / 20);
            
            // Comparar com baseline
            $baselineDeviation = 0;
            if (!empty($this->baselineBehavior)) {
                $baselineMean = $this->baselineBehavior['unique_uris']['mean'] ?? 5;
                $baselineStddev = $this->baselineBehavior['unique_uris']['stddev'] ?? 3;
                $currentUris = count($uris);
                
                if ($baselineStddev > 0) {
                    $baselineDeviation = ($currentUris - $baselineMean) / $baselineStddev;
                    // Se muito acima da média, pode ser scanning
                    if ($baselineDeviation > 2) {
                        $severity += 10;
                        $isAnomaly = true;
                    }
                }
            }
            
            return [
                'is_anomaly' => $isAnomaly,
                'severity' => min(50, $severity),
                'confidence' => $confidence,
                'unique_uris' => count($uris),
                'entropy' => round($entropy, 3),
                'suspicious_patterns' => $suspiciousPatterns,
                'baseline_deviation' => round($baselineDeviation, 2)
            ];
        } catch (PDOException $e) {
            return ['is_anomaly' => false, 'severity' => 0];
        }
    }
    
    /**
     * Analisa User-Agents (versão avançada)
     */
    private function analyzeUserAgentsAdvanced($ipAddress, $timeWindow) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    user_agent,
                    COUNT(*) as count
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND user_agent IS NOT NULL
                GROUP BY user_agent
            ");
            $stmt->execute([$ipAddress, $timeWindow]);
            $userAgents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $isAnomaly = false;
            $severity = 0;
            
            // Múltiplos User-Agents diferentes (possível rotação)
            if (count($userAgents) > 5) {
                $isAnomaly = true;
                $severity += 15;
            }
            
            // User-Agents suspeitos
            $suspiciousAgents = ['sqlmap', 'nikto', 'acunetix', 'nmap', 'curl', 'wget', 'python'];
            foreach ($userAgents as $ua) {
                foreach ($suspiciousAgents as $suspicious) {
                    if (stripos($ua['user_agent'], $suspicious) !== false) {
                        $isAnomaly = true;
                        $severity += 25;
                        break;
                    }
                }
            }
            
            // Calcular confiança
            $confidence = min(1.0, count($userAgents) / 10);
            
            // Análise de rotação de User-Agents (sinal de bot)
            $rotationScore = 0;
            if (count($userAgents) > 3) {
                // Verificar se User-Agents mudam frequentemente
                $stmt2 = $this->db->prepare("
                    SELECT user_agent, created_at
                    FROM safenode_security_logs
                    WHERE ip_address = ?
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                    AND user_agent IS NOT NULL
                    ORDER BY created_at ASC
                    LIMIT 50
                ");
                $stmt2->execute([$ipAddress, $timeWindow]);
                $sequence = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                
                $switches = 0;
                for ($i = 1; $i < count($sequence); $i++) {
                    if ($sequence[$i]['user_agent'] !== $sequence[$i-1]['user_agent']) {
                        $switches++;
                    }
                }
                
                if (count($sequence) > 1) {
                    $rotationRate = $switches / (count($sequence) - 1);
                    if ($rotationRate > 0.3) { // Mais de 30% de mudanças
                        $rotationScore = $rotationRate * 20;
                        $isAnomaly = true;
                        $severity += $rotationScore;
                    }
                }
            }
            
            return [
                'is_anomaly' => $isAnomaly,
                'severity' => min(50, $severity),
                'confidence' => $confidence,
                'unique_user_agents' => count($userAgents),
                'rotation_score' => round($rotationScore, 2),
                'user_agents' => array_map(function($ua) {
                    return [
                        'agent' => substr($ua['user_agent'], 0, 100),
                        'count' => $ua['count']
                    ];
                }, $userAgents)
            ];
        } catch (PDOException $e) {
            return ['is_anomaly' => false, 'severity' => 0];
        }
    }
    
    /**
     * Analisa padrões de horário (versão avançada)
     */
    private function analyzeTimePatternsAdvanced($ipAddress) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    HOUR(created_at) as hour,
                    COUNT(*) as count
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY HOUR(created_at)
                ORDER BY count DESC
            ");
            $stmt->execute([$ipAddress]);
            $hours = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $isAnomaly = false;
            $severity = 0;
            
            // Atividade em horários não usuais (madrugada)
            foreach ($hours as $hour) {
                $hourNum = (int)$hour['hour'];
                if (($hourNum >= 0 && $hourNum < 6) && $hour['count'] > 10) {
                    $isAnomaly = true;
                    $severity += 10;
                }
            }
            
            // Análise de padrão temporal (24h vs normal)
            $activitySpread = count($hours);
            $unusualHours = 0;
            
            foreach ($hours as $hour) {
                $hourNum = (int)$hour['hour'];
                // Horários muito fora do padrão comercial
                if (($hourNum >= 0 && $hourNum < 6) || ($hourNum >= 22 && $hourNum <= 23)) {
                    if ($hour['count'] > 5) {
                        $unusualHours++;
                    }
                }
            }
            
            if ($unusualHours > 2) {
                $isAnomaly = true;
                $severity += $unusualHours * 8;
            }
            
            // Atividade muito distribuída (possível bot)
            if ($activitySpread > 18) {
                $isAnomaly = true;
                $severity += 15;
            }
            
            $confidence = min(1.0, count($hours) / 24);
            
            return [
                'is_anomaly' => $isAnomaly,
                'severity' => min(40, $severity),
                'confidence' => $confidence,
                'active_hours' => array_map(function($h) {
                    return [
                        'hour' => $h['hour'],
                        'count' => $h['count']
                    ];
                }, $hours),
                'activity_spread' => $activitySpread,
                'unusual_hours' => $unusualHours
            ];
        } catch (PDOException $e) {
            return ['is_anomaly' => false, 'severity' => 0];
        }
    }
    
    /**
     * Analisa taxa de erro/bloqueio (versão avançada)
     */
    private function analyzeErrorRateAdvanced($ipAddress, $timeWindow) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked_requests,
                    SUM(CASE WHEN action_taken = 'allowed' THEN 1 ELSE 0 END) as allowed_requests
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$ipAddress, $timeWindow]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || $result['total_requests'] == 0) {
                return ['is_anomaly' => false, 'severity' => 0];
            }
            
            $total = (int)$result['total_requests'];
            $blocked = (int)$result['blocked_requests'];
            $blockRate = ($blocked / $total) * 100;
            
            $isAnomaly = false;
            $severity = 0;
            
            // Taxa de bloqueio muito alta (> 50%)
            if ($blockRate > 50) {
                $isAnomaly = true;
                $severity = min(30, ($blockRate - 50) * 0.6);
            }
            
            // Análise de tendência de bloqueio (está aumentando?)
            $stmt2 = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as minute,
                    SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) / COUNT(*) * 100 as block_rate
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:%i')
                ORDER BY minute DESC
                LIMIT 10
            ");
            $stmt2->execute([$ipAddress, $timeWindow]);
            $recentRates = array_reverse(array_column($stmt2->fetchAll(PDO::FETCH_ASSOC), 'block_rate'));
            
            $escalating = false;
            if (count($recentRates) >= 3) {
                $trend = $this->calculateTrend($recentRates);
                if ($trend > 5) { // Taxa de bloqueio aumentando
                    $escalating = true;
                    $severity += 15;
                    $isAnomaly = true;
                }
            }
            
            $confidence = min(1.0, $total / 20);
            
            return [
                'is_anomaly' => $isAnomaly,
                'severity' => min(40, $severity),
                'confidence' => $confidence,
                'total_requests' => $total,
                'blocked_requests' => $blocked,
                'block_rate' => round($blockRate, 2),
                'is_escalating' => $escalating
            ];
        } catch (PDOException $e) {
            return ['is_anomaly' => false, 'severity' => 0];
        }
    }
    
    /**
     * Analisa progressão de ameaças (versão avançada)
     */
    private function analyzeThreatProgressionAdvanced($ipAddress, $timeWindow) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    threat_score,
                    threat_type,
                    created_at
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND threat_score > 0
                ORDER BY created_at ASC
            ");
            $stmt->execute([$ipAddress, $timeWindow]);
            $threats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($threats) < 3) {
                return ['is_anomaly' => false, 'severity' => 0];
            }
            
            $isAnomaly = false;
            $severity = 0;
            
            // Verificar se há progressão crescente de threat_score
            $scores = array_column($threats, 'threat_score');
            $isEscalating = true;
            for ($i = 1; $i < count($scores); $i++) {
                if ($scores[$i] <= $scores[$i - 1]) {
                    $isEscalating = false;
                    break;
                }
            }
            
            if ($isEscalating) {
                $isAnomaly = true;
                $severity = 25; // Escalação de ameaças é grave
            }
            
            // Verificar diversidade de tipos de ameaça
            $threatTypes = array_unique(array_column($threats, 'threat_type'));
            if (count($threatTypes) >= 3) {
                $isAnomaly = true;
                $severity += 15; // Múltiplos tipos de ameaça
            }
            
            // Análise de velocidade de escalada
            $escalationSpeed = 0;
            if ($isEscalating && count($threats) >= 2) {
                $firstScore = (float)$threats[0]['threat_score'];
                $lastScore = (float)$threats[count($threats)-1]['threat_score'];
                $timeSpan = strtotime($threats[count($threats)-1]['created_at']) - strtotime($threats[0]['created_at']);
                
                if ($timeSpan > 0) {
                    $escalationSpeed = ($lastScore - $firstScore) / ($timeSpan / 60); // Por minuto
                    if ($escalationSpeed > 1) { // Aumento rápido
                        $severity += 10;
                    }
                }
            }
            
            $confidence = min(1.0, count($threats) / 10);
            
            return [
                'is_anomaly' => $isAnomaly,
                'severity' => min(50, $severity),
                'confidence' => $confidence,
                'threat_count' => count($threats),
                'unique_threat_types' => count($threatTypes),
                'is_escalating' => $isEscalating,
                'escalation_speed' => round($escalationSpeed, 2)
            ];
        } catch (PDOException $e) {
            return ['is_anomaly' => false, 'severity' => 0];
        }
    }
    
    /**
     * Analisa sequências de ações (NOVO)
     */
    private function analyzeActionSequences($ipAddress, $timeWindow) {
        try {
            $stmt = $this->db->prepare("
                SELECT action_taken, threat_type, created_at
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                ORDER BY created_at ASC
                LIMIT 100
            ");
            $stmt->execute([$ipAddress, $timeWindow]);
            $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($actions) < 3) {
                return [
                    'is_anomaly' => false,
                    'severity' => 0,
                    'confidence' => 0.3
                ];
            }
            
            $isAnomaly = false;
            $severity = 0;
            $patterns = [];
            
            // Detectar padrão de tentativa-rejeição-repetição (brute force)
            $attemptPattern = 0;
            for ($i = 0; $i < count($actions) - 2; $i++) {
                if ($actions[$i]['action_taken'] === 'blocked' &&
                    $actions[$i+1]['action_taken'] === 'blocked' &&
                    $actions[$i+2]['action_taken'] === 'blocked') {
                    $attemptPattern++;
                }
            }
            
            if ($attemptPattern > 5) {
                $isAnomaly = true;
                $severity += 25;
                $patterns[] = 'repetitive_blocked_attempts';
            }
            
            // Detectar alternância rápida de ações (possível bypass attempt)
            $switchCount = 0;
            for ($i = 1; $i < count($actions); $i++) {
                if ($actions[$i]['action_taken'] !== $actions[$i-1]['action_taken']) {
                    $switchCount++;
                }
            }
            
            $switchRate = count($actions) > 1 ? $switchCount / (count($actions) - 1) : 0;
            if ($switchRate > 0.5) { // Mais de 50% de mudanças
                $isAnomaly = true;
                $severity += 15;
                $patterns[] = 'high_action_switching';
            }
            
            // Detectar sequência de diferentes tipos de ameaça (reconnaissance)
            $uniqueThreats = [];
            foreach ($actions as $action) {
                if (!empty($action['threat_type']) && !in_array($action['threat_type'], $uniqueThreats)) {
                    $uniqueThreats[] = $action['threat_type'];
                }
            }
            
            if (count($uniqueThreats) >= 4) {
                $isAnomaly = true;
                $severity += 20;
                $patterns[] = 'diverse_threat_types';
            }
            
            $confidence = min(1.0, count($actions) / 30);
            
            return [
                'is_anomaly' => $isAnomaly,
                'severity' => min(50, $severity),
                'confidence' => $confidence,
                'total_actions' => count($actions),
                'detected_patterns' => $patterns,
                'switch_rate' => round($switchRate, 3),
                'unique_threat_sequence' => count($uniqueThreats)
            ];
        } catch (PDOException $e) {
            return [
                'is_anomaly' => false,
                'severity' => 0,
                'confidence' => 0
            ];
        }
    }
    
    /**
     * Analisa padrão de navegação (NOVO)
     */
    private function analyzeNavigationPattern($ipAddress, $timeWindow) {
        try {
            $stmt = $this->db->prepare("
                SELECT request_uri, action_taken, created_at
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                ORDER BY created_at ASC
                LIMIT 100
            ");
            $stmt->execute([$ipAddress, $timeWindow]);
            $navigations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($navigations) < 3) {
                return [
                    'is_anomaly' => false,
                    'severity' => 0,
                    'confidence' => 0.3
                ];
            }
            
            $isAnomaly = false;
            $severity = 0;
            $suspiciousPatterns = [];
            
            // Detectar padrão de scanning (múltiplas URIs sem navegação natural)
            $uriPaths = [];
            foreach ($navigations as $nav) {
                $path = parse_url($nav['request_uri'], PHP_URL_PATH);
                $uriPaths[] = $path;
            }
            
            // Verificar se há padrão de scanning (muitas URIs diferentes rapidamente)
            $uniquePathsInWindow = [];
            $scanScore = 0;
            $windowSize = 10; // Janela de 10 requisições
            
            for ($i = 0; $i < count($uriPaths) - $windowSize; $i++) {
                $window = array_slice($uriPaths, $i, $windowSize);
                $uniqueInWindow = count(array_unique($window));
                
                if ($uniqueInWindow >= 8) { // 8+ URIs diferentes em 10 requisições
                    $scanScore++;
                }
            }
            
            if ($scanScore > 3) {
                $isAnomaly = true;
                $severity += 30;
                $suspiciousPatterns[] = 'scanning_pattern';
            }
            
            // Detectar falta de navegação natural (não segue links)
            // Verificar se há referers
            $stmt2 = $this->db->prepare("
                SELECT COUNT(*) as total, 
                       SUM(CASE WHEN referer IS NULL OR referer = '' THEN 1 ELSE 0 END) as no_referer
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt2->execute([$ipAddress, $timeWindow]);
            $refResult = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            if ($refResult && $refResult['total'] > 0) {
                $noRefererRate = ($refResult['no_referer'] / $refResult['total']) * 100;
                if ($noRefererRate > 80 && $refResult['total'] > 10) {
                    $isAnomaly = true;
                    $severity += 15;
                    $suspiciousPatterns[] = 'no_referer_pattern';
                }
            }
            
            // Detectar acesso direto a URLs profundas (bypass navigation)
            $deepAccessCount = 0;
            foreach ($uriPaths as $path) {
                $depth = substr_count(trim($path, '/'), '/');
                if ($depth >= 3) { // URLs com 3+ níveis
                    $deepAccessCount++;
                }
            }
            
            if ($deepAccessCount > count($uriPaths) * 0.5 && count($uriPaths) > 5) {
                $isAnomaly = true;
                $severity += 10;
                $suspiciousPatterns[] = 'deep_url_access';
            }
            
            $confidence = min(1.0, count($navigations) / 30);
            
            return [
                'is_anomaly' => $isAnomaly,
                'severity' => min(50, $severity),
                'confidence' => $confidence,
                'total_navigations' => count($navigations),
                'unique_paths' => count(array_unique($uriPaths)),
                'scan_score' => $scanScore,
                'suspicious_patterns' => $suspiciousPatterns
            ];
        } catch (PDOException $e) {
            return [
                'is_anomaly' => false,
                'severity' => 0,
                'confidence' => 0
            ];
        }
    }
    
    /**
     * Calcula entropia de Shannon (medida de diversidade)
     */
    private function calculateEntropy($values) {
        if (empty($values)) return 0;
        
        $total = array_sum($values);
        if ($total == 0) return 0;
        
        $entropy = 0;
        foreach ($values as $value) {
            if ($value > 0) {
                $probability = $value / $total;
                $entropy -= $probability * log($probability, 2);
            }
        }
        
        return $entropy;
    }
    
    /**
     * Calcula tendência usando regressão linear simples
     */
    private function calculateTrend($values) {
        $n = count($values);
        if ($n < 2) return 0;
        
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $x = $i;
            $y = (float)$values[$i];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        
        return $slope;
    }
    
    /**
     * Obtém análise comportamental para dashboard
     */
    public function getBehaviorStats($siteId = null, $userId = null, $limit = 10) {
        if (!$this->db) return [];
        
        try {
            $sql = "
                SELECT 
                    ip_address,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked_count,
                    AVG(threat_score) as avg_threat_score,
                    MAX(threat_score) as max_threat_score,
                    COUNT(DISTINCT threat_type) as unique_threat_types,
                    MAX(created_at) as last_seen
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ";
            
            $params = [];
            if ($siteId) {
                $sql .= " AND site_id = ?";
                $params[] = $siteId;
            } elseif ($userId) {
                $sql .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
                $params[] = $userId;
            }
            
            $sql .= " GROUP BY ip_address 
                      HAVING blocked_count > 0 OR avg_threat_score > 30
                      ORDER BY blocked_count DESC, avg_threat_score DESC 
                      LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Adicionar análise comportamental para cada IP
            $results = [];
            foreach ($ips as $ip) {
                $behavior = $this->analyzeIPBehavior($ip['ip_address'], 3600);
                $results[] = [
                    'ip_address' => $ip['ip_address'],
                    'total_requests' => (int)$ip['total_requests'],
                    'blocked_count' => (int)$ip['blocked_count'],
                    'avg_threat_score' => round((float)$ip['avg_threat_score'], 2),
                    'max_threat_score' => (int)$ip['max_threat_score'],
                    'unique_threat_types' => (int)$ip['unique_threat_types'],
                    'last_seen' => $ip['last_seen'],
                    'behavior_risk_level' => $behavior['risk_level'],
                    'behavior_risk_score' => $behavior['risk_score'],
                    'anomaly_count' => $behavior['anomaly_count']
                ];
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("SafeNode BehaviorAnalyzer Stats Error: " . $e->getMessage());
            return [];
        }
    }
}





