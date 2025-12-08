<?php
/**
 * SafeNode - Attack Predictor
 * Sistema de predição de ataques (Early Warning System)
 * 
 * Analisa padrões históricos para prever ataques futuros
 */

class AttackPredictor {
    private $db;
    private $cache;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
    }
    
    /**
     * Gera alertas preditivos baseados em padrões históricos
     * 
     * @param int $hours Horas para analisar (padrão: 24)
     * @return array Alertas preditivos
     */
    public function generatePredictiveAlerts($hours = 24) {
        if (!$this->db) return [];
        
        $alerts = [];
        
        // 1. Detectar aumento súbito de ataques
        $attackSpike = $this->detectAttackSpike($hours);
        if ($attackSpike) {
            $alerts[] = $attackSpike;
        }
        
        // 2. Detectar padrão similar a DDoS
        $ddosPattern = $this->detectDDoSPattern($hours);
        if ($ddosPattern) {
            $alerts[] = $ddosPattern;
        }
        
        // 3. Detectar horário de pico de ataques
        $peakTime = $this->detectAttackPeakTime($hours);
        if ($peakTime) {
            $alerts[] = $peakTime;
        }
        
        // 4. Detectar correlação com eventos externos
        $externalEvent = $this->checkExternalEvents($hours);
        if ($externalEvent) {
            $alerts[] = $externalEvent;
        }
        
        // 5. Detectar tendência de aumento
        $trend = $this->detectAttackTrend($hours);
        if ($trend) {
            $alerts[] = $trend;
        }
        
        return $alerts;
    }
    
    /**
     * Detecta aumento súbito de ataques
     */
    private function detectAttackSpike($hours) {
        try {
            // Comparar últimas 2 horas com 2 horas anteriores
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 1 ELSE 0 END) as recent_attacks,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 4 HOUR) 
                             AND created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 1 ELSE 0 END) as previous_attacks
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND threat_score >= 50
                AND created_at >= DATE_SUB(NOW(), INTERVAL 4 HOUR)
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $recent = (int)($result['recent_attacks'] ?? 0);
            $previous = (int)($result['previous_attacks'] ?? 0);
            
            if ($previous > 0 && $recent > 0) {
                $increase = (($recent - $previous) / $previous) * 100;
                
                if ($increase >= 100) { // Aumento de 100% ou mais
                    return [
                        'type' => 'attack_spike',
                        'severity' => $increase >= 200 ? 'high' : 'medium',
                        'message' => "Ataques aumentaram " . round($increase) . "% nas últimas 2 horas",
                        'recent_attacks' => $recent,
                        'previous_attacks' => $previous,
                        'increase_percent' => round($increase, 1),
                        'recommendation' => "Aumentar nível de segurança e monitorar de perto"
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPredictor Spike Error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Detecta padrão similar a DDoS
     */
    private function detectDDoSPattern($hours) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT ip_address) as unique_ips,
                    COUNT(*) as total_requests,
                    AVG(threat_score) as avg_threat_score
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:%i')
                HAVING unique_ips >= 10 AND total_requests >= 50
                ORDER BY total_requests DESC
                LIMIT 1
            ");
            $stmt->execute([$hours]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && (int)$result['total_requests'] > 0) {
                $requestsPerMinute = (int)$result['total_requests'];
                $uniqueIPs = (int)$result['unique_ips'];
                
                if ($requestsPerMinute >= 100 && $uniqueIPs >= 20) {
                    return [
                        'type' => 'ddos_pattern',
                        'severity' => 'high',
                        'message' => "Padrão similar a ataque DDoS detectado",
                        'unique_ips' => $uniqueIPs,
                        'requests_per_minute' => $requestsPerMinute,
                        'recommendation' => "Ativar modo 'Under Attack' e preparar defesas DDoS"
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPredictor DDoS Error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Detecta horário de pico de ataques
     */
    private function detectAttackPeakTime($hours) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    HOUR(created_at) as hour,
                    COUNT(*) as attack_count,
                    AVG(threat_score) as avg_threat_score
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                GROUP BY HOUR(created_at)
                ORDER BY attack_count DESC
                LIMIT 1
            ");
            $stmt->execute([$hours]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $currentHour = (int)date('G');
                $peakHour = (int)$result['hour'];
                $attackCount = (int)$result['attack_count'];
                
                // Se estamos próximos do horário de pico
                if (abs($currentHour - $peakHour) <= 1 && $attackCount >= 20) {
                    return [
                        'type' => 'peak_time_warning',
                        'severity' => 'medium',
                        'message' => "Horário de pico de ataques detectado (hora $peakHour)",
                        'peak_hour' => $peakHour,
                        'attack_count' => $attackCount,
                        'recommendation' => "Aumentar vigilância durante este horário"
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPredictor Peak Error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Verifica correlação com eventos externos
     */
    private function checkExternalEvents($hours) {
        // Em produção, integrar com APIs de vulnerabilidades conhecidas
        // Por enquanto, verificar padrões de tipos de ataque
        
        try {
            // Verificar se SQL injection aumentou (pode indicar vulnerabilidade divulgada)
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as sql_injection_count,
                    COUNT(*) / (SELECT COUNT(*) FROM safenode_security_logs 
                               WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)) * 100 as percentage
                FROM safenode_security_logs
                WHERE threat_type = 'sql_injection'
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            ");
            $stmt->execute([$hours * 24, $hours]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && (float)$result['percentage'] > 30) {
                return [
                    'type' => 'external_event_correlation',
                    'severity' => 'medium',
                    'message' => "Ataques de SQL injection aumentaram significativamente",
                    'sql_injection_percentage' => round((float)$result['percentage'], 1),
                    'recommendation' => "Verificar se há vulnerabilidades conhecidas divulgadas recentemente"
                ];
            }
        } catch (PDOException $e) {
            // Ignorar
        }
        
        return null;
    }
    
    /**
     * Detecta tendência de aumento de ataques
     */
    private function detectAttackTrend($hours) {
        try {
            // Analisar tendência nas últimas horas
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
                    COUNT(*) as attack_count
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')
                ORDER BY hour DESC
                LIMIT 6
            ");
            $stmt->execute([$hours]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($results) >= 3) {
                // Calcular tendência (linear regression simples)
                $counts = array_reverse(array_column($results, 'attack_count'));
                $trend = $this->calculateTrend($counts);
                
                if ($trend > 0.2) { // Tendência de aumento
                    return [
                        'type' => 'increasing_trend',
                        'severity' => 'medium',
                        'message' => "Tendência de aumento de ataques detectada",
                        'trend' => round($trend, 2),
                        'recommendation' => "Preparar defesas para possível aumento de ataques"
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPredictor Trend Error: " . $e->getMessage());
        }
        
        return null;
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
     * Obtém estatísticas preditivas
     */
    public function getPredictiveStats($days = 7) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DAYNAME(created_at) as day_name,
                    HOUR(created_at) as hour,
                    COUNT(*) as attack_count,
                    AVG(threat_score) as avg_threat_score
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DAYNAME(created_at), HOUR(created_at)
                ORDER BY attack_count DESC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
}



