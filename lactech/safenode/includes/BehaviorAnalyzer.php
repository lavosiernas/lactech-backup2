<?php
/**
 * SafeNode - Behavior Analyzer
 * Sistema de análise comportamental de IPs e sessões
 * Detecta padrões suspeitos baseado em comportamento histórico
 */

class BehaviorAnalyzer {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Analisa comportamento de um IP
     */
    public function analyzeIPBehavior($ipAddress, $timeWindow = 3600) {
        if (!$this->db) {
            return [
                'risk_level' => 'unknown',
                'risk_score' => 0,
                'behaviors' => [],
                'anomalies' => []
            ];
        }
        
        try {
            $behaviors = [];
            $anomalies = [];
            $riskScore = 0;
            
            // 1. Análise de frequência de requisições
            $frequencyAnalysis = $this->analyzeRequestFrequency($ipAddress, $timeWindow);
            if ($frequencyAnalysis['is_anomaly']) {
                $anomalies[] = $frequencyAnalysis;
                $riskScore += $frequencyAnalysis['severity'];
            }
            $behaviors['frequency'] = $frequencyAnalysis;
            
            // 2. Análise de padrão de URIs
            $uriPatternAnalysis = $this->analyzeURIPatterns($ipAddress, $timeWindow);
            if ($uriPatternAnalysis['is_anomaly']) {
                $anomalies[] = $uriPatternAnalysis;
                $riskScore += $uriPatternAnalysis['severity'];
            }
            $behaviors['uri_patterns'] = $uriPatternAnalysis;
            
            // 3. Análise de User-Agents
            $userAgentAnalysis = $this->analyzeUserAgents($ipAddress, $timeWindow);
            if ($userAgentAnalysis['is_anomaly']) {
                $anomalies[] = $userAgentAnalysis;
                $riskScore += $userAgentAnalysis['severity'];
            }
            $behaviors['user_agents'] = $userAgentAnalysis;
            
            // 4. Análise de horários de acesso
            $timePatternAnalysis = $this->analyzeTimePatterns($ipAddress);
            if ($timePatternAnalysis['is_anomaly']) {
                $anomalies[] = $timePatternAnalysis;
                $riskScore += $timePatternAnalysis['severity'];
            }
            $behaviors['time_patterns'] = $timePatternAnalysis;
            
            // 5. Análise de taxa de erro
            $errorRateAnalysis = $this->analyzeErrorRate($ipAddress, $timeWindow);
            if ($errorRateAnalysis['is_anomaly']) {
                $anomalies[] = $errorRateAnalysis;
                $riskScore += $errorRateAnalysis['severity'];
            }
            $behaviors['error_rate'] = $errorRateAnalysis;
            
            // 6. Análise de progressão de ameaças
            $threatProgression = $this->analyzeThreatProgression($ipAddress, $timeWindow);
            if ($threatProgression['is_anomaly']) {
                $anomalies[] = $threatProgression;
                $riskScore += $threatProgression['severity'];
            }
            $behaviors['threat_progression'] = $threatProgression;
            
            // Determinar nível de risco
            $riskLevel = 'low';
            if ($riskScore >= 70) {
                $riskLevel = 'critical';
            } elseif ($riskScore >= 50) {
                $riskLevel = 'high';
            } elseif ($riskScore >= 30) {
                $riskLevel = 'medium';
            }
            
            return [
                'risk_level' => $riskLevel,
                'risk_score' => min(100, $riskScore),
                'behaviors' => $behaviors,
                'anomalies' => $anomalies,
                'anomaly_count' => count($anomalies)
            ];
        } catch (PDOException $e) {
            error_log("SafeNode BehaviorAnalyzer Error: " . $e->getMessage());
            return [
                'risk_level' => 'unknown',
                'risk_score' => 0,
                'behaviors' => [],
                'anomalies' => []
            ];
        }
    }
    
    /**
     * Analisa frequência de requisições
     */
    private function analyzeRequestFrequency($ipAddress, $timeWindow) {
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
            
            return [
                'is_anomaly' => $isAnomaly,
                'severity' => $severity,
                'total_requests' => $totalRequests,
                'requests_per_minute' => round($requestsPerMinute, 2),
                'avg_interval' => $result['avg_interval'] ? round($result['avg_interval'], 2) : null
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
     * Analisa padrões de URIs acessadas
     */
    private function analyzeURIPatterns($ipAddress, $timeWindow) {
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
            
            return [
                'is_anomaly' => $isAnomaly,
                'severity' => min(40, $severity),
                'unique_uris' => count($uris),
                'suspicious_patterns' => $suspiciousPatterns
            ];
        } catch (PDOException $e) {
            return ['is_anomaly' => false, 'severity' => 0];
        }
    }
    
    /**
     * Analisa User-Agents
     */
    private function analyzeUserAgents($ipAddress, $timeWindow) {
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
            
            return [
                'is_anomaly' => $isAnomaly,
                'severity' => min(40, $severity),
                'unique_user_agents' => count($userAgents),
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
     * Analisa padrões de horário
     */
    private function analyzeTimePatterns($ipAddress) {
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
            
            return [
                'is_anomaly' => $isAnomaly,
                'severity' => $severity,
                'active_hours' => array_map(function($h) {
                    return [
                        'hour' => $h['hour'],
                        'count' => $h['count']
                    ];
                }, $hours)
            ];
        } catch (PDOException $e) {
            return ['is_anomaly' => false, 'severity' => 0];
        }
    }
    
    /**
     * Analisa taxa de erro/bloqueio
     */
    private function analyzeErrorRate($ipAddress, $timeWindow) {
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
            
            return [
                'is_anomaly' => $isAnomaly,
                'severity' => $severity,
                'total_requests' => $total,
                'blocked_requests' => $blocked,
                'block_rate' => round($blockRate, 2)
            ];
        } catch (PDOException $e) {
            return ['is_anomaly' => false, 'severity' => 0];
        }
    }
    
    /**
     * Analisa progressão de ameaças (escalada)
     */
    private function analyzeThreatProgression($ipAddress, $timeWindow) {
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
            
            return [
                'is_anomaly' => $isAnomaly,
                'severity' => min(40, $severity),
                'threat_count' => count($threats),
                'unique_threat_types' => count($threatTypes),
                'is_escalating' => $isEscalating
            ];
        } catch (PDOException $e) {
            return ['is_anomaly' => false, 'severity' => 0];
        }
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





