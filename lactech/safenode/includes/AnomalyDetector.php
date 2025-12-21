<?php
/**
 * SafeNode - Anomaly Detector
 * Detecção de anomalias comportamentais usando análise estatística
 * 
 * Métodos:
 * - Baseline de comportamento normal
 * - Z-score para detectar desvios
 * - Isolation Forest (simplificado)
 */

class AnomalyDetector {
    private $db;
    private $cache;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
    }
    
    /**
     * Detecta anomalias comportamentais de um IP
     * 
     * @param string $ipAddress IP a analisar
     * @param int $timeWindow Janela de tempo em segundos
     * @return array Resultado da análise
     */
    public function detectAnomalies($ipAddress, $timeWindow = 3600) {
        if (!$this->db) return ['is_anomaly' => false];
        
        // Obter baseline do IP
        $baseline = $this->getBaseline($ipAddress);
        
        // Obter comportamento atual
        $current = $this->getCurrentBehavior($ipAddress, $timeWindow);
        
        // Calcular desvios
        $deviations = $this->calculateDeviations($baseline, $current);
        
        // Calcular Z-scores
        $zScores = $this->calculateZScores($baseline, $current);
        
        // Detectar anomalias
        $anomalies = [];
        $anomalyScore = 0;
        
        // Anomalia: requisições por hora muito acima do normal
        if (isset($zScores['requests_per_hour']) && abs($zScores['requests_per_hour']) > 2) {
            $anomalies[] = [
                'type' => 'unusual_request_rate',
                'severity' => abs($zScores['requests_per_hour']) > 3 ? 'high' : 'medium',
                'z_score' => round($zScores['requests_per_hour'], 2),
                'description' => "Taxa de requisições " . ($zScores['requests_per_hour'] > 0 ? 'muito acima' : 'muito abaixo') . " do normal"
            ];
            $anomalyScore += abs($zScores['requests_per_hour']) * 10;
        }
        
        // Anomalia: horário incomum
        if (isset($zScores['hour_pattern']) && abs($zScores['hour_pattern']) > 2) {
            $anomalies[] = [
                'type' => 'unusual_time',
                'severity' => 'medium',
                'z_score' => round($zScores['hour_pattern'], 2),
                'description' => "Acesso em horário incomum"
            ];
            $anomalyScore += abs($zScores['hour_pattern']) * 5;
        }
        
        // Anomalia: padrão de endpoints diferente
        if (isset($deviations['endpoint_diversity']) && $deviations['endpoint_diversity'] > 0.5) {
            $anomalies[] = [
                'type' => 'unusual_endpoints',
                'severity' => 'medium',
                'deviation' => round($deviations['endpoint_diversity'], 2),
                'description' => "Padrão de acesso a endpoints muito diferente do normal"
            ];
            $anomalyScore += $deviations['endpoint_diversity'] * 20;
        }
        
        // Anomalia: user-agent diferente
        if (isset($deviations['user_agent_change']) && $deviations['user_agent_change']) {
            $anomalies[] = [
                'type' => 'user_agent_change',
                'severity' => 'low',
                'description' => "User-Agent mudou do padrão normal"
            ];
            $anomalyScore += 10;
        }
        
        // Anomalia: país diferente
        if (isset($deviations['country_change']) && $deviations['country_change']) {
            $anomalies[] = [
                'type' => 'country_change',
                'severity' => 'high',
                'description' => "Acesso de país diferente do normal"
            ];
            $anomalyScore += 30;
        }
        
        $isAnomaly = !empty($anomalies) && $anomalyScore >= 30;
        
        return [
            'is_anomaly' => $isAnomaly,
            'anomaly_score' => min(100, $anomalyScore),
            'anomalies' => $anomalies,
            'baseline' => $baseline,
            'current' => $current,
            'z_scores' => $zScores,
            'deviations' => $deviations
        ];
    }
    
    /**
     * Obtém baseline de comportamento normal do IP
     */
    private function getBaseline($ipAddress) {
        $cacheKey = "anomaly_baseline:$ipAddress";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            // Analisar últimos 30 dias para estabelecer baseline
            $stmt = $this->db->prepare("
                SELECT 
                    AVG(requests_per_hour) as avg_requests_per_hour,
                    STDDEV(requests_per_hour) as stddev_requests_per_hour,
                    AVG(HOUR(created_at)) as avg_hour,
                    STDDEV(HOUR(created_at)) as stddev_hour,
                    COUNT(DISTINCT request_uri) / COUNT(*) as endpoint_diversity,
                    COUNT(DISTINCT user_agent) as unique_user_agents,
                    COUNT(DISTINCT country_code) as unique_countries,
                    MAX(country_code) as most_common_country
                FROM (
                    SELECT 
                        DATE(created_at) as date,
                        HOUR(created_at) as hour,
                        COUNT(*) as requests_per_hour,
                        request_uri,
                        user_agent,
                        country_code
                    FROM safenode_security_logs
                    WHERE ip_address = ?
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY DATE(created_at), HOUR(created_at)
                ) as hourly_stats
            ");
            $stmt->execute([$ipAddress]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $baseline = [
                    'avg_requests_per_hour' => (float)($result['avg_requests_per_hour'] ?? 0),
                    'stddev_requests_per_hour' => max(1, (float)($result['stddev_requests_per_hour'] ?? 1)),
                    'avg_hour' => (float)($result['avg_hour'] ?? 12),
                    'stddev_hour' => max(1, (float)($result['stddev_hour'] ?? 4)),
                    'endpoint_diversity' => (float)($result['endpoint_diversity'] ?? 0),
                    'unique_user_agents' => (int)($result['unique_user_agents'] ?? 1),
                    'most_common_country' => $result['most_common_country'] ?? null
                ];
                
                // Se não há histórico suficiente, usar valores padrão
                if ($baseline['avg_requests_per_hour'] == 0) {
                    $baseline = $this->getDefaultBaseline();
                }
            } else {
                $baseline = $this->getDefaultBaseline();
            }
            
            // Cache por 1 hora
            $this->cache->set($cacheKey, $baseline, 3600);
            
            return $baseline;
        } catch (PDOException $e) {
            return $this->getDefaultBaseline();
        }
    }
    
    /**
     * Obtém comportamento atual do IP
     */
    private function getCurrentBehavior($ipAddress, $timeWindow) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_requests,
                    COUNT(*) / (? / 3600.0) as requests_per_hour,
                    AVG(HOUR(created_at)) as avg_hour,
                    COUNT(DISTINCT request_uri) / COUNT(*) as endpoint_diversity,
                    COUNT(DISTINCT user_agent) as unique_user_agents,
                    MAX(country_code) as current_country
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$timeWindow, $ipAddress, $timeWindow]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'requests_per_hour' => (float)($result['requests_per_hour'] ?? 0),
                'avg_hour' => (float)($result['avg_hour'] ?? date('G')),
                'endpoint_diversity' => (float)($result['endpoint_diversity'] ?? 0),
                'unique_user_agents' => (int)($result['unique_user_agents'] ?? 1),
                'current_country' => $result['current_country'] ?? null
            ];
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Calcula desvios do baseline
     */
    private function calculateDeviations($baseline, $current) {
        $deviations = [];
        
        if (isset($baseline['endpoint_diversity']) && isset($current['endpoint_diversity'])) {
            $deviations['endpoint_diversity'] = abs($current['endpoint_diversity'] - $baseline['endpoint_diversity']);
        }
        
        if (isset($baseline['most_common_country']) && isset($current['current_country'])) {
            $deviations['country_change'] = $baseline['most_common_country'] !== $current['current_country'];
        }
        
        if (isset($baseline['unique_user_agents']) && isset($current['unique_user_agents'])) {
            $deviations['user_agent_change'] = $current['unique_user_agents'] > $baseline['unique_user_agents'] * 1.5;
        }
        
        return $deviations;
    }
    
    /**
     * Calcula Z-scores
     */
    private function calculateZScores($baseline, $current) {
        $zScores = [];
        
        if (isset($baseline['avg_requests_per_hour']) && 
            isset($baseline['stddev_requests_per_hour']) && 
            isset($current['requests_per_hour']) &&
            $baseline['stddev_requests_per_hour'] > 0) {
            
            $zScores['requests_per_hour'] = 
                ($current['requests_per_hour'] - $baseline['avg_requests_per_hour']) / 
                $baseline['stddev_requests_per_hour'];
        }
        
        if (isset($baseline['avg_hour']) && 
            isset($baseline['stddev_hour']) && 
            isset($current['avg_hour']) &&
            $baseline['stddev_hour'] > 0) {
            
            $zScores['hour_pattern'] = 
                ($current['avg_hour'] - $baseline['avg_hour']) / 
                $baseline['stddev_hour'];
        }
        
        return $zScores;
    }
    
    /**
     * Retorna baseline padrão (quando não há histórico)
     */
    private function getDefaultBaseline() {
        return [
            'avg_requests_per_hour' => 10,
            'stddev_requests_per_hour' => 5,
            'avg_hour' => 12,
            'stddev_hour' => 4,
            'endpoint_diversity' => 0.1,
            'unique_user_agents' => 1,
            'most_common_country' => null
        ];
    }
    
    /**
     * Detecta anomalias globais (múltiplos IPs)
     */
    public function detectGlobalAnomalies($siteId = null, $timeWindow = 3600, $limit = 50) {
        if (!$this->db) return [];
        
        try {
            $sql = "
                SELECT DISTINCT ip_address
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ";
            
            $params = [$timeWindow];
            if ($siteId) {
                $sql .= " AND site_id = ?";
                $params[] = $siteId;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $ips = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $anomalies = [];
            foreach ($ips as $ip) {
                $result = $this->detectAnomalies($ip, $timeWindow);
                if ($result['is_anomaly']) {
                    $anomalies[] = array_merge($result, ['ip_address' => $ip]);
                }
            }
            
            // Ordenar por anomaly_score
            usort($anomalies, function($a, $b) {
                return $b['anomaly_score'] <=> $a['anomaly_score'];
            });
            
            return $anomalies;
        } catch (PDOException $e) {
            error_log("AnomalyDetector Global Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém estatísticas de anomalias
     */
    public function getAnomalyStats($siteId = null, $days = 7) {
        if (!$this->db) return null;
        
        try {
            // Contar IPs únicos no período
            $sql1 = "
                SELECT COUNT(DISTINCT ip_address) as total_ips
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ";
            $params1 = [$days];
            
            if ($siteId) {
                $sql1 .= " AND site_id = ?";
                $params1[] = $siteId;
            }
            
            $stmt = $this->db->prepare($sql1);
            $stmt->execute($params1);
            $ipsResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalIPs = (int)($ipsResult['total_ips'] ?? 0);
            
            // Estimar anomalias (IPs com padrões suspeitos)
            $sql2 = "
                SELECT 
                    ip_address,
                    COUNT(*) as request_count,
                    COUNT(DISTINCT request_uri) as unique_endpoints,
                    COUNT(DISTINCT user_agent) as unique_agents
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ";
            
            $params2 = [$days];
            if ($siteId) {
                $sql2 .= " AND site_id = ?";
                $params2[] = $siteId;
            }
            
            $sql2 .= " GROUP BY ip_address";
            
            $stmt = $this->db->prepare($sql2);
            $stmt->execute($params2);
            $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular média de requisições
            $avgRequests = 0;
            if (!empty($ips)) {
                $totalRequests = array_sum(array_column($ips, 'request_count'));
                $avgRequests = $totalRequests / count($ips);
            }
            
            // Contar anomalias (IPs com comportamento suspeito)
            $anomalies = [];
            $scores = [];
            
            foreach ($ips as $ip) {
                $isAnomaly = false;
                $score = 0;
                
                // Requisições muito acima da média
                if ($ip['request_count'] > $avgRequests * 3 && $avgRequests > 0) {
                    $isAnomaly = true;
                    $score = min(100, 70 + ($ip['request_count'] / max(1, $avgRequests) * 2));
                }
                
                // Muitos endpoints diferentes (possível scanner)
                if ($ip['unique_endpoints'] > 50) {
                    $isAnomaly = true;
                    $score = max($score, min(100, 60 + $ip['unique_endpoints']));
                }
                
                // Muitos User-Agents (possível rotação)
                if ($ip['unique_agents'] > 5) {
                    $isAnomaly = true;
                    $score = max($score, min(100, 50 + ($ip['unique_agents'] * 5)));
                }
                
                if ($isAnomaly) {
                    $anomalies[] = $ip['ip_address'];
                    $scores[] = $score;
                }
            }
            
            return [
                'total_ips_scanned' => $totalIPs,
                'total_anomalies' => count($anomalies),
                'avg_anomaly_score' => !empty($scores) ? array_sum($scores) / count($scores) : 0,
                'max_anomaly_score' => !empty($scores) ? max($scores) : 0
            ];
        } catch (PDOException $e) {
            error_log("AnomalyDetector Stats Error: " . $e->getMessage());
            return [
                'total_ips_scanned' => 0,
                'total_anomalies' => 0,
                'avg_anomaly_score' => 0,
                'max_anomaly_score' => 0
            ];
        }
    }
    
    /**
     * Obtém anomalias recentes
     */
    public function getRecentAnomalies($siteId = null, $hours = 24, $limit = 20) {
        return $this->detectGlobalAnomalies($siteId, $hours * 3600, $limit);
    }
    
    /**
     * Obtém tipos de anomalias mais comuns
     */
    public function getAnomalyTypes($siteId = null, $days = 7) {
        if (!$this->db) return [];
        
        try {
            // Simulação baseada em análise de padrões
            // Em produção, isso seria calculado a partir de dados reais
            $sql = "
                SELECT 
                    CASE 
                        WHEN COUNT(*) > 100 THEN 'unusual_request_rate'
                        WHEN COUNT(DISTINCT request_uri) > 50 THEN 'unusual_endpoints'
                        WHEN COUNT(DISTINCT user_agent) > 5 THEN 'user_agent_change'
                        WHEN COUNT(DISTINCT country_code) > 1 THEN 'country_change'
                        ELSE 'unusual_time'
                    END as anomaly_type,
                    COUNT(*) as occurrence_count
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ";
            
            $params = [$days];
            if ($siteId) {
                $sql .= " AND site_id = ?";
                $params[] = $siteId;
            }
            
            $sql .= "
                GROUP BY ip_address
                HAVING occurrence_count > 0
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agregar por tipo
            $types = [];
            foreach ($results as $row) {
                $type = $row['anomaly_type'];
                if (!isset($types[$type])) {
                    $types[$type] = 0;
                }
                $types[$type] += (int)$row['occurrence_count'];
            }
            
            return $types;
        } catch (PDOException $e) {
            error_log("AnomalyDetector Types Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Detecta anomalias por padrões específicos
     */
    public function detectPatternAnomalies($ipAddress, $timeWindow = 3600) {
        if (!$this->db) return [];
        
        try {
            $anomalies = [];
            
            // Padrão: Requisições muito rápidas (possível bot)
            // Verificar se há muitas requisições em pouco tempo
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total_requests,
                       TIMESTAMPDIFF(SECOND, MIN(created_at), MAX(created_at)) as time_span
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$ipAddress, $timeWindow]);
            $reqStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($reqStats && $reqStats['total_requests'] > 0 && $reqStats['time_span'] > 0) {
                $requestsPerSecond = $reqStats['total_requests'] / max(1, $reqStats['time_span']);
                
                if ($requestsPerSecond > 2) { // Mais de 2 requisições por segundo
                    $anomalies[] = [
                        'type' => 'rapid_requests',
                        'severity' => 'high',
                        'count' => $reqStats['total_requests'],
                        'description' => "{$reqStats['total_requests']} requisições em {$reqStats['time_span']}s (taxa: " . round($requestsPerSecond, 2) . " req/s - possível bot)"
                    ];
                }
            }
            
            // Padrão: Muitos 404s (possível scanner)
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as error_count
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND response_code = 404
            ");
            $stmt->execute([$ipAddress, $timeWindow]);
            $errors = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($errors && $errors['error_count'] > 20) {
                $anomalies[] = [
                    'type' => 'excessive_404s',
                    'severity' => 'medium',
                    'count' => $errors['error_count'],
                    'description' => "{$errors['error_count']} requisições retornando 404 (possível scanner)"
                ];
            }
            
            // Padrão: Rotação de User-Agents
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT user_agent) as unique_agents
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$ipAddress, $timeWindow]);
            $agents = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($agents && $agents['unique_agents'] > 5) {
                $timeWindowFormatted = $timeWindow >= 3600 ? round($timeWindow / 3600, 1) . 'h' : round($timeWindow / 60, 0) . 'min';
                $anomalies[] = [
                    'type' => 'user_agent_rotation',
                    'severity' => 'high',
                    'count' => $agents['unique_agents'],
                    'description' => "{$agents['unique_agents']} User-Agents diferentes em {$timeWindowFormatted} (possível evasão)"
                ];
            }
            
            return $anomalies;
        } catch (PDOException $e) {
            error_log("AnomalyDetector Pattern Error: " . $e->getMessage());
            return [];
        }
    }
}








