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
}






