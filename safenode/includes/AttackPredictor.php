<?php
/**
 * SafeNode - Attack Predictor (FUNCIONAL)
 * Sistema de predição de ataques baseado em análise estatística real
 * 
 * STATUS: FUNCIONAL - Sistema completo com análise de séries temporais e predição estatística
 * 
 * Funcionalidades:
 * - Análise de séries temporais para identificar padrões
 * - Regressão linear para predição de tendências
 * - Análise de sazonalidade (horários, dias da semana)
 * - Detecção de anomalias preditivas
 * - Early warning system baseado em múltiplos fatores
 */

class AttackPredictor {
    private $db;
    private $cache;
    private $predictionModel;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
        $this->predictionModel = null;
    }
    
    /**
     * Gera alertas preditivos baseados em análise estatística avançada
     * 
     * @param int $hours Horas para analisar (padrão: 24)
     * @return array Alertas preditivos com probabilidades e confiança
     */
    public function generatePredictiveAlerts($hours = 24) {
        if (!$this->db) return [];
        
        $cacheKey = "predictive_alerts:$hours:" . date('H');
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $alerts = [];
        
        // 1. Predição baseada em análise de séries temporais
        $timeSeriesPrediction = $this->predictUsingTimeSeries($hours);
        if ($timeSeriesPrediction) {
            $alerts[] = $timeSeriesPrediction;
        }
        
        // 2. Detectar aumento súbito de ataques (análise aprimorada)
        $attackSpike = $this->detectAttackSpike($hours);
        if ($attackSpike) {
            $alerts[] = $attackSpike;
        }
        
        // 3. Predição de DDoS baseada em padrões
        $ddosPrediction = $this->predictDDoSAttack($hours);
        if ($ddosPrediction) {
            $alerts[] = $ddosPrediction;
        }
        
        // 4. Predição de horário de ataque baseada em sazonalidade
        $peakTimePrediction = $this->predictAttackPeakTime($hours);
        if ($peakTimePrediction) {
            $alerts[] = $peakTimePrediction;
        }
        
        // 5. Análise de tendência com regressão linear
        $trendPrediction = $this->predictAttackTrend($hours);
        if ($trendPrediction) {
            $alerts[] = $trendPrediction;
        }
        
        // 6. Detecção de anomalias preditivas
        $anomalyPrediction = $this->predictAnomalies($hours);
        if ($anomalyPrediction) {
            $alerts[] = $anomalyPrediction;
        }
        
        // 7. Correlação com eventos externos
        $externalEvent = $this->checkExternalEvents($hours);
        if ($externalEvent) {
            $alerts[] = $externalEvent;
        }
        
        // Ordenar por probabilidade e severidade
        usort($alerts, function($a, $b) {
            $priorityA = ($a['probability'] ?? 0.5) * 10 + ($a['severity_score'] ?? 0);
            $priorityB = ($b['probability'] ?? 0.5) * 10 + ($b['severity_score'] ?? 0);
            return $priorityB <=> $priorityA;
        });
        
        // Cache por 5 minutos
        $this->cache->set($cacheKey, $alerts, 300);
        
        return $alerts;
    }
    
    /**
     * Predição usando análise de séries temporais
     */
    private function predictUsingTimeSeries($hours) {
        try {
            // Coletar dados históricos por hora
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
                    COUNT(*) as attack_count,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    AVG(threat_score) as avg_threat_score,
                    MAX(threat_score) as max_threat_score
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')
                ORDER BY hour DESC
                LIMIT 168
            ");
            $stmt->execute([7]); // 7 dias = 168 horas
            $historicalData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($historicalData) < 24) {
                return null; // Dados insuficientes
            }
            
            // Reverter para ordem cronológica
            $historicalData = array_reverse($historicalData);
            
            // Extrair séries temporais
            $attackCounts = array_column($historicalData, 'attack_count');
            $avgThreatScores = array_column($historicalData, 'avg_threat_score');
            
            // Analisar padrão de sazonalidade (ciclos de 24 horas)
            $seasonalPattern = $this->analyzeSeasonality($attackCounts, 24);
            
            // Predição usando média móvel exponencial
            $ema = $this->calculateEMA($attackCounts, 0.3);
            $predictedNext = $this->predictNextValue($attackCounts, $ema, $seasonalPattern);
            
            // Calcular desvio padrão para intervalo de confiança
            $recentCounts = array_slice($attackCounts, -24);
            $stddev = $this->calculateStdDev($recentCounts);
            $mean = array_sum($recentCounts) / count($recentCounts);
            
            // Se predição está significativamente acima da média
            if ($predictedNext > ($mean + 1.5 * $stddev)) {
                $probability = min(0.95, 0.5 + (($predictedNext - $mean) / ($stddev * 2)));
                
                return [
                    'type' => 'time_series_prediction',
                    'severity' => $predictedNext > ($mean + 2 * $stddev) ? 'high' : 'medium',
                    'severity_score' => min(100, ($predictedNext / max(1, $mean)) * 50),
                    'probability' => round($probability, 3),
                    'confidence' => count($historicalData) >= 72 ? 'high' : 'medium',
                    'message' => "Análise de séries temporais indica possível aumento de ataques nas próximas horas",
                    'predicted_attacks' => round($predictedNext),
                    'current_average' => round($mean, 1),
                    'expected_range' => [
                        'min' => round(max(0, $predictedNext - $stddev)),
                        'max' => round($predictedNext + $stddev)
                    ],
                    'recommendation' => "Prepare defesas e aumente monitoramento nas próximas " . round($hours) . " horas"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPredictor TimeSeries Error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Analisa sazonalidade em série temporal
     */
    private function analyzeSeasonality($data, $period) {
        if (count($data) < $period * 2) {
            return null;
        }
        
        $seasonal = [];
        for ($i = 0; $i < $period; $i++) {
            $sum = 0;
            $count = 0;
            
            for ($j = $i; $j < count($data); $j += $period) {
                $sum += $data[$j];
                $count++;
            }
            
            $seasonal[$i] = $count > 0 ? $sum / $count : 0;
        }
        
        return $seasonal;
    }
    
    /**
     * Calcula média móvel exponencial (EMA)
     */
    private function calculateEMA($data, $alpha = 0.3) {
        if (empty($data)) return [];
        
        $ema = [];
        $ema[0] = $data[0];
        
        for ($i = 1; $i < count($data); $i++) {
            $ema[$i] = $alpha * $data[$i] + (1 - $alpha) * $ema[$i - 1];
        }
        
        return $ema;
    }
    
    /**
     * Prediz próximo valor usando EMA e sazonalidade
     */
    private function predictNextValue($data, $ema, $seasonalPattern) {
        if (empty($data) || empty($ema)) {
            return 0;
        }
        
        // Valor baseado em EMA
        $emaValue = end($ema);
        
        // Ajustar para sazonalidade se disponível
        if ($seasonalPattern && count($seasonalPattern) > 0) {
            $nextIndex = count($data) % count($seasonalPattern);
            $seasonalFactor = $seasonalPattern[$nextIndex] ?? 1;
            $overallMean = array_sum($seasonalPattern) / count($seasonalPattern);
            
            if ($overallMean > 0) {
                $seasonalAdjustment = ($seasonalFactor / $overallMean);
                return $emaValue * $seasonalAdjustment;
            }
        }
        
        return $emaValue;
    }
    
    /**
     * Calcula desvio padrão
     */
    private function calculateStdDev($values) {
        if (count($values) < 2) return 0;
        
        $mean = array_sum($values) / count($values);
        $variance = 0;
        
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        $variance = $variance / count($values);
        return sqrt($variance);
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
     * Predição de ataque DDoS baseada em análise de padrões
     */
    private function predictDDoSAttack($hours) {
        try {
            // Analisar padrões nas últimas horas
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:00') as minute,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    COUNT(*) as total_requests,
                    AVG(threat_score) as avg_threat_score
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:00')
                ORDER BY minute DESC
            ");
            $stmt->execute([$hours]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($results) < 10) {
                return null;
            }
            
            // Analisar tendência de aumento
            $recentMinutes = array_slice($results, 0, 30);
            $uniqueIPs = array_column($recentMinutes, 'unique_ips');
            $requests = array_column($recentMinutes, 'total_requests');
            
            // Calcular tendências
            $ipTrend = $this->calculateTrend($uniqueIPs);
            $requestTrend = $this->calculateTrend($requests);
            
            // Verificar padrões de DDoS
            $avgUniqueIPs = array_sum($uniqueIPs) / count($uniqueIPs);
            $avgRequests = array_sum($requests) / count($requests);
            
            // Critérios de predição DDoS
            $ddosProbability = 0;
            $severity = 'low';
            
            if ($avgUniqueIPs >= 20 && $avgRequests >= 100) {
                $ddosProbability += 0.3;
            }
            
            if ($ipTrend > 2) { // Crescimento de IPs
                $ddosProbability += 0.3;
            }
            
            if ($requestTrend > 5) { // Crescimento de requisições
                $ddosProbability += 0.4;
            }
            
            // Verificar se está acelerando
            $recentAvg = array_sum(array_slice($requests, 0, 10)) / 10;
            $previousAvg = array_sum(array_slice($requests, 10, 10)) / 10;
            
            if ($previousAvg > 0 && ($recentAvg / $previousAvg) > 1.5) {
                $ddosProbability += 0.2;
                $severity = 'high';
            }
            
            if ($ddosProbability >= 0.5) {
                $predictedRequests = $avgRequests * (1 + ($requestTrend / 10));
                $predictedIPs = $avgUniqueIPs * (1 + ($ipTrend / 10));
                
                return [
                    'type' => 'ddos_prediction',
                    'severity' => $severity,
                    'severity_score' => min(100, $ddosProbability * 100),
                    'probability' => round(min(0.95, $ddosProbability), 3),
                    'confidence' => count($results) >= 30 ? 'high' : 'medium',
                    'message' => "Padrões indicam possível ataque DDoS em desenvolvimento",
                    'current_metrics' => [
                        'unique_ips_per_min' => round($avgUniqueIPs, 1),
                        'requests_per_min' => round($avgRequests, 1)
                    ],
                    'predicted_metrics' => [
                        'unique_ips_per_min' => round($predictedIPs, 1),
                        'requests_per_min' => round($predictedRequests, 1)
                    ],
                    'growth_rate' => [
                        'ips' => round($ipTrend, 2),
                        'requests' => round($requestTrend, 2)
                    ],
                    'recommendation' => "Ativar modo 'Under Attack', preparar defesas DDoS e aumentar rate limiting"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPredictor DDoS Prediction Error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Predição de horário de pico baseada em análise de sazonalidade
     */
    private function predictAttackPeakTime($hours) {
        try {
            // Analisar padrões históricos de ataques por hora
            $stmt = $this->db->prepare("
                SELECT 
                    HOUR(created_at) as hour,
                    COUNT(*) as attack_count,
                    AVG(threat_score) as avg_threat_score,
                    COUNT(DISTINCT DATE(created_at)) as days_with_attacks
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY HOUR(created_at)
                ORDER BY attack_count DESC
            ");
            $stmt->execute();
            $hourlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($hourlyData)) {
                return null;
            }
            
            // Identificar horários de pico estatisticamente significativos
            $attackCounts = array_column($hourlyData, 'attack_count');
            $mean = array_sum($attackCounts) / count($attackCounts);
            $stddev = $this->calculateStdDev($attackCounts);
            
            $peakHours = [];
            foreach ($hourlyData as $data) {
                $count = (int)$data['attack_count'];
                $zScore = ($count - $mean) / ($stddev > 0 ? $stddev : 1);
                
                // Horários com Z-score > 1.5 são considerados picos
                if ($zScore > 1.5) {
                    $peakHours[] = [
                        'hour' => (int)$data['hour'],
                        'attack_count' => $count,
                        'z_score' => $zScore,
                        'probability' => min(0.95, 0.5 + ($zScore / 10)),
                        'days_with_attacks' => (int)$data['days_with_attacks']
                    ];
                }
            }
            
            if (empty($peakHours)) {
                return null;
            }
            
            // Verificar se estamos próximos de um horário de pico
            $currentHour = (int)date('G');
            $nextHour = ($currentHour + 1) % 24;
            
            foreach ($peakHours as $peak) {
                $hoursUntilPeak = ($peak['hour'] - $currentHour + 24) % 24;
                
                // Se o pico está nas próximas 2 horas
                if ($hoursUntilPeak <= 2 && $hoursUntilPeak > 0) {
                    $probability = $peak['probability'] * (1 - ($hoursUntilPeak / 3));
                    
                    return [
                        'type' => 'peak_time_prediction',
                        'severity' => $peak['z_score'] > 2 ? 'high' : 'medium',
                        'severity_score' => min(100, $peak['z_score'] * 20),
                        'probability' => round($probability, 3),
                        'confidence' => $peak['days_with_attacks'] >= 10 ? 'high' : 'medium',
                        'message' => "Horário de pico histórico de ataques aproximando-se (hora {$peak['hour']})",
                        'peak_hour' => $peak['hour'],
                        'hours_until_peak' => $hoursUntilPeak,
                        'historical_avg_attacks' => $peak['attack_count'],
                        'days_observed' => $peak['days_with_attacks'],
                        'recommendation' => "Aumentar vigilância e preparar defesas nas próximas " . ($hoursUntilPeak) . " hora(s)"
                    ];
                }
                
                // Se estamos no horário de pico
                if ($currentHour == $peak['hour']) {
                    return [
                        'type' => 'peak_time_active',
                        'severity' => $peak['z_score'] > 2 ? 'high' : 'medium',
                        'severity_score' => min(100, $peak['z_score'] * 20),
                        'probability' => round($peak['probability'], 3),
                        'confidence' => $peak['days_with_attacks'] >= 10 ? 'high' : 'medium',
                        'message' => "Atualmente no horário de pico histórico de ataques (hora {$peak['hour']})",
                        'peak_hour' => $peak['hour'],
                        'historical_avg_attacks' => $peak['attack_count'],
                        'days_observed' => $peak['days_with_attacks'],
                        'recommendation' => "Vigilância máxima - este é um horário crítico baseado em padrões históricos"
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPredictor Peak Prediction Error: " . $e->getMessage());
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
     * Predição de tendência usando regressão linear avançada
     */
    private function predictAttackTrend($hours) {
        try {
            // Coletar mais dados para análise mais precisa
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
                    COUNT(*) as attack_count,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    AVG(threat_score) as avg_threat_score
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')
                ORDER BY hour DESC
                LIMIT 24
            ");
            $stmt->execute([$hours]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($results) < 6) {
                return null;
            }
            
            // Reverter para ordem cronológica
            $results = array_reverse($results);
            
            $attackCounts = array_column($results, 'attack_count');
            $uniqueIPs = array_column($results, 'unique_ips');
            
            // Calcular regressão linear para ambos
            $attackTrend = $this->calculateLinearRegression($attackCounts);
            $ipTrend = $this->calculateLinearRegression($uniqueIPs);
            
            // Predizer próximo valor
            $nextIndex = count($attackCounts);
            $predictedAttacks = $attackTrend['slope'] * $nextIndex + $attackTrend['intercept'];
            $predictedIPs = $ipTrend['slope'] * $nextIndex + $ipTrend['intercept'];
            
            $currentAttacks = end($attackCounts);
            $currentIPs = end($uniqueIPs);
            
            // Calcular probabilidade baseada na força da tendência
            $rSquared = $attackTrend['r_squared'];
            $increasePercent = $currentAttacks > 0 ? (($predictedAttacks - $currentAttacks) / $currentAttacks) * 100 : 0;
            
            if ($attackTrend['slope'] > 0 && $rSquared > 0.3 && $increasePercent > 20) {
                $probability = min(0.9, 0.5 + ($rSquared * 0.5));
                
                return [
                    'type' => 'trend_prediction',
                    'severity' => $increasePercent > 50 ? 'high' : 'medium',
                    'severity_score' => min(100, abs($increasePercent) * 0.8),
                    'probability' => round($probability, 3),
                    'confidence' => $rSquared > 0.6 ? 'high' : ($rSquared > 0.4 ? 'medium' : 'low'),
                    'message' => "Análise de tendência indica aumento de ataques nas próximas horas",
                    'current_metrics' => [
                        'attacks_per_hour' => round($currentAttacks, 1),
                        'unique_ips' => round($currentIPs, 1)
                    ],
                    'predicted_metrics' => [
                        'attacks_per_hour' => round(max(0, $predictedAttacks), 1),
                        'unique_ips' => round(max(0, $predictedIPs), 1)
                    ],
                    'trend_analysis' => [
                        'slope' => round($attackTrend['slope'], 2),
                        'r_squared' => round($rSquared, 3),
                        'increase_percent' => round($increasePercent, 1)
                    ],
                    'recommendation' => "Prepare defesas para aumento esperado de " . round($increasePercent, 1) . "% nos próximos períodos"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPredictor Trend Prediction Error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Calcula regressão linear completa (incluindo R²)
     */
    private function calculateLinearRegression($values) {
        $n = count($values);
        if ($n < 2) {
            return ['slope' => 0, 'intercept' => 0, 'r_squared' => 0];
        }
        
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $x = $i;
            $y = (float)$values[$i];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
            $sumY2 += $y * $y;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        // Calcular R²
        $yMean = $sumY / $n;
        $ssRes = 0;
        $ssTot = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $y = (float)$values[$i];
            $yPred = $slope * $i + $intercept;
            $ssRes += pow($y - $yPred, 2);
            $ssTot += pow($y - $yMean, 2);
        }
        
        $rSquared = $ssTot > 0 ? 1 - ($ssRes / $ssTot) : 0;
        
        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'r_squared' => max(0, min(1, $rSquared))
        ];
    }
    
    /**
     * Predição de anomalias usando detecção estatística
     */
    private function predictAnomalies($hours) {
        try {
            // Analisar padrões anômalos recentes
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
                    COUNT(*) as attack_count,
                    COUNT(DISTINCT threat_type) as unique_threat_types,
                    COUNT(DISTINCT ip_address) as unique_ips
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')
                ORDER BY hour DESC
                LIMIT 12
            ");
            $stmt->execute([$hours]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($results) < 6) {
                return null;
            }
            
            $recent = array_slice($results, 0, 3);
            $previous = array_slice($results, 3, 3);
            
            $recentAvgAttacks = array_sum(array_column($recent, 'attack_count')) / count($recent);
            $previousAvgAttacks = array_sum(array_column($previous, 'attack_count')) / count($previous);
            $recentAvgThreatTypes = array_sum(array_column($recent, 'unique_threat_types')) / count($recent);
            
            // Detectar anomalias
            if ($previousAvgAttacks > 0) {
                $deviation = (($recentAvgAttacks - $previousAvgAttacks) / $previousAvgAttacks) * 100;
                
                // Anomalia: aumento súbito + diversidade de tipos de ameaça
                if ($deviation > 100 && $recentAvgThreatTypes >= 3) {
                    $probability = min(0.9, 0.4 + ($deviation / 500));
                    
                    return [
                        'type' => 'anomaly_prediction',
                        'severity' => $deviation > 200 ? 'high' : 'medium',
                        'severity_score' => min(100, ($deviation / 2)),
                        'probability' => round($probability, 3),
                        'confidence' => 'medium',
                        'message' => "Padrão anômalo detectado: aumento súbito com múltiplos tipos de ameaça",
                        'anomaly_metrics' => [
                            'deviation_percent' => round($deviation, 1),
                            'recent_avg_attacks' => round($recentAvgAttacks, 1),
                            'previous_avg_attacks' => round($previousAvgAttacks, 1),
                            'unique_threat_types' => round($recentAvgThreatTypes, 1)
                        ],
                        'recommendation' => "Aumento anômalo detectado - investigar padrão e preparar para possível escalada"
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPredictor Anomaly Error: " . $e->getMessage());
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








