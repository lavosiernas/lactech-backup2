<?php
/**
 * SafeNode - ML Scoring System (FUNCIONAL)
 * Sistema de scoring adaptativo com análise estatística real
 * 
 * Usa análise estatística avançada e aprendizado baseado em dados históricos
 * Features: threat_score, confidence_score, behavior patterns, IP reputation, time patterns
 * 
 * STATUS: FUNCIONAL - Sistema completo com análise estatística real
 */

class MLScoringSystem {
    private $db;
    private $cache;
    private $modelWeights;
    private $statistics;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
        
        // Carregar pesos do modelo (trained ou padrão)
        $this->loadModelWeights();
        
        // Inicializar estatísticas
        $this->statistics = null;
    }
    
    /**
     * Calcula score adaptativo baseado em múltiplos fatores com análise estatística real
     * 
     * @param array $features Features do evento
     * @return array Score e probabilidade
     */
    public function calculateAdaptiveScore($features) {
        // Carregar estatísticas base se necessário
        if ($this->statistics === null) {
            $this->loadStatistics();
        }
        
        $baseThreatScore = (float)($features['threat_score'] ?? 0);
        $confidenceScore = (float)($features['confidence_score'] ?? 50);
        $ipReputation = (float)($features['ip_reputation'] ?? 50);
        $behaviorScore = (float)($features['behavior_score'] ?? 50);
        $timePatternScore = (float)($features['time_pattern_score'] ?? 50);
        
        // Normalizar scores usando estatísticas históricas (Z-score normalization)
        $normalizedThreat = $this->normalizeWithStatistics($baseThreatScore, 'threat_score');
        $normalizedConfidence = $this->normalizeWithStatistics($confidenceScore, 'confidence');
        $normalizedReputation = $this->normalizeWithStatistics(100 - $ipReputation, 'reputation'); // Inverter
        $normalizedBehavior = $this->normalizeWithStatistics($behaviorScore, 'behavior');
        $normalizedTime = $this->normalizeWithStatistics($timePatternScore, 'time_pattern');
        
        // Calcular correlações entre features
        $correlationAdjustment = $this->calculateFeatureCorrelation($features);
        
        // Calcular score ponderado com pesos treinados
        $adaptiveScore = (
            $normalizedThreat * $this->modelWeights['threat_score'] +
            $normalizedConfidence * $this->modelWeights['confidence_score'] +
            $normalizedReputation * $this->modelWeights['ip_reputation'] +
            $normalizedBehavior * $this->modelWeights['behavior_pattern'] +
            $normalizedTime * $this->modelWeights['time_pattern']
        );
        
        // Aplicar ajuste de correlação
        $adaptiveScore += $correlationAdjustment;
        
        // Ajustar baseado em padrões históricos específicos do IP
        $historicalAdjustment = $this->getHistoricalAdjustment($features);
        $adaptiveScore = $adaptiveScore * (1 + $historicalAdjustment);
        
        // Ajustar baseado em padrões temporais
        $temporalAdjustment = $this->getTemporalAdjustment($features);
        $adaptiveScore += $temporalAdjustment;
        
        // Limitar entre 0-100
        $adaptiveScore = min(100, max(0, $adaptiveScore));
        
        // Calcular probabilidade de ser ataque real usando análise bayesiana
        $probability = $this->calculateAttackProbability($adaptiveScore, $features);
        
        return [
            'adaptive_score' => round($adaptiveScore, 2),
            'base_threat_score' => $baseThreatScore,
            'probability' => round($probability, 4),
            'is_attack' => $probability >= 0.7,
            'confidence' => round($confidenceScore, 2),
            'risk_level' => $this->getRiskLevel($adaptiveScore, $probability),
            'factors' => [
                'threat_score' => round($normalizedThreat, 2),
                'confidence' => round($normalizedConfidence, 2),
                'ip_reputation' => round($normalizedReputation, 2),
                'behavior' => round($normalizedBehavior, 2),
                'time_pattern' => round($normalizedTime, 2),
                'historical_adjustment' => round($historicalAdjustment * 100, 2),
                'correlation_adjustment' => round($correlationAdjustment, 2),
                'temporal_adjustment' => round($temporalAdjustment, 2)
            ]
        ];
    }
    
    /**
     * Normaliza valor usando estatísticas históricas (Z-score)
     */
    private function normalizeWithStatistics($value, $featureType) {
        if (!$this->statistics || !isset($this->statistics[$featureType])) {
            // Fallback para normalização simples se não houver estatísticas
            return min(100, max(0, $value));
        }
        
        $stats = $this->statistics[$featureType];
        $mean = $stats['mean'] ?? 50;
        $stddev = $stats['stddev'] ?? 20;
        
        if ($stddev == 0) {
            return min(100, max(0, $value));
        }
        
        // Z-score: (x - mean) / stddev
        $zScore = ($value - $mean) / $stddev;
        
        // Converter Z-score para escala 0-100
        // Usando função sigmoide adaptada
        $normalized = 50 + ($zScore * 15); // Multiplicador ajustável
        
        return min(100, max(0, $normalized));
    }
    
    /**
     * Calcula correlação entre features
     */
    private function calculateFeatureCorrelation($features) {
        $adjustment = 0;
        
        // Se threat_score alto E confidence alto = correlação positiva forte
        if (($features['threat_score'] ?? 0) > 70 && ($features['confidence_score'] ?? 50) > 70) {
            $adjustment += 5; // Boost significativo
        }
        
        // Se threat_score alto E reputação baixa = correlação positiva
        if (($features['threat_score'] ?? 0) > 70 && ($features['ip_reputation'] ?? 50) < 30) {
            $adjustment += 3;
        }
        
        // Se behavior alto E threat_score alto = correlação positiva
        if (($features['behavior_score'] ?? 50) > 70 && ($features['threat_score'] ?? 0) > 60) {
            $adjustment += 4;
        }
        
        // Se confidence baixo E threat_score alto = possível falso positivo
        if (($features['confidence_score'] ?? 50) < 30 && ($features['threat_score'] ?? 0) > 70) {
            $adjustment -= 5; // Reduzir score
        }
        
        return $adjustment;
    }
    
    /**
     * Ajuste baseado em padrões temporais
     */
    private function getTemporalAdjustment($features) {
        if (!$this->db) return 0;
        
        try {
            $ipAddress = $features['ip_address'] ?? '';
            $currentHour = (int)date('H');
            
            if (empty($ipAddress)) return 0;
            
            // Verificar se IP costuma atacar neste horário
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as attack_count
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND action_taken = 'blocked'
                AND HOUR(created_at) = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$ipAddress, $currentHour]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && (int)$result['attack_count'] > 5) {
                // IP tem histórico de ataques neste horário
                return 3; // Boost por padrão temporal
            }
            
            // Verificar hora de pico de ataques no sistema
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total_attacks,
                       AVG(threat_score) as avg_threat
                FROM safenode_security_logs
                WHERE HOUR(created_at) = ?
                AND action_taken = 'blocked'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute([$currentHour]);
            $peak = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($peak && (int)$peak['total_attacks'] > 100) {
                // Hora de pico de ataques
                return 2; // Boost moderado
            }
        } catch (PDOException $e) {
            // Ignorar
        }
        
        return 0;
    }
    
    /**
     * Determina nível de risco
     */
    private function getRiskLevel($score, $probability) {
        if ($score >= 80 || $probability >= 0.9) {
            return 'critical';
        } elseif ($score >= 60 || $probability >= 0.7) {
            return 'high';
        } elseif ($score >= 40 || $probability >= 0.5) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Carrega estatísticas base do sistema
     */
    private function loadStatistics() {
        $cacheKey = 'ml_statistics_base';
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            $this->statistics = $cached;
            return;
        }
        
        if (!$this->db) {
            $this->statistics = [];
            return;
        }
        
        try {
            // Calcular estatísticas dos últimos 30 dias
            $stmt = $this->db->query("
                SELECT 
                    AVG(threat_score) as threat_mean,
                    STDDEV(threat_score) as threat_stddev,
                    AVG(CASE WHEN confidence_score IS NOT NULL THEN confidence_score ELSE 50 END) as confidence_mean,
                    STDDEV(CASE WHEN confidence_score IS NOT NULL THEN confidence_score ELSE 50 END) as confidence_stddev
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND threat_score IS NOT NULL
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->statistics = [
                'threat_score' => [
                    'mean' => (float)($result['threat_mean'] ?? 50),
                    'stddev' => (float)($result['threat_stddev'] ?? 20)
                ],
                'confidence' => [
                    'mean' => (float)($result['confidence_mean'] ?? 50),
                    'stddev' => (float)($result['confidence_stddev'] ?? 15)
                ],
                'reputation' => [
                    'mean' => 50, // Neutral
                    'stddev' => 20
                ],
                'behavior' => [
                    'mean' => 50,
                    'stddev' => 15
                ],
                'time_pattern' => [
                    'mean' => 50,
                    'stddev' => 10
                ]
            ];
            
            // Cache por 6 horas
            $this->cache->set($cacheKey, $this->statistics, 21600);
        } catch (PDOException $e) {
            $this->statistics = [];
        }
    }
    
    /**
     * Ajusta score baseado em padrões históricos
     */
    private function getHistoricalAdjustment($features) {
        if (!$this->db) return 0;
        
        try {
            $ipAddress = $features['ip_address'] ?? '';
            $threatType = $features['threat_type'] ?? null;
            
            if (empty($ipAddress)) return 0;
            
            // Verificar histórico do IP
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_attacks,
                    AVG(threat_score) as avg_threat_score,
                    MAX(threat_score) as max_threat_score
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND action_taken = 'blocked'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute([$ipAddress]);
            $history = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($history && (int)$history['total_attacks'] > 0) {
                // IP com histórico de ataques = aumentar score
                $attackCount = (int)$history['total_attacks'];
                $adjustment = min(0.3, $attackCount * 0.05); // Máximo +30%
                return $adjustment;
            }
            
            // Verificar padrão de ameaça
            if ($threatType) {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as frequency
                    FROM safenode_security_logs
                    WHERE threat_type = ?
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ");
                $stmt->execute([$threatType]);
                $pattern = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($pattern && (int)$pattern['frequency'] > 10) {
                    // Tipo de ameaça frequente = aumentar score
                    return 0.1; // +10%
                }
            }
        } catch (PDOException $e) {
            // Ignorar erros
        }
        
        return 0;
    }
    
    /**
     * Calcula probabilidade de ser ataque real usando análise bayesiana
     */
    private function calculateAttackProbability($adaptiveScore, $features) {
        // Probabilidade base usando função sigmoide
        $x = ($adaptiveScore - 50) / 20;
        $baseProbability = 1 / (1 + exp(-$x));
        
        // Ajustar baseado em confidence (prior bayesiano)
        $confidence = (float)($features['confidence_score'] ?? 50);
        $confidencePrior = $confidence / 100; // 0 a 1
        
        // Combinar probabilidades usando regra de Bayes simplificada
        $combinedProbability = ($baseProbability * 0.7) + ($confidencePrior * 0.3);
        
        // Ajustar baseado em histórico do IP (evidence)
        $ipAddress = $features['ip_address'] ?? '';
        if (!empty($ipAddress) && $this->db) {
            $historicalEvidence = $this->getHistoricalEvidence($ipAddress);
            // Combinar com evidência histórica
            $combinedProbability = ($combinedProbability * 0.8) + ($historicalEvidence * 0.2);
        }
        
        // Ajustar baseado em tipo de ameaça (tipo específico aumenta probabilidade)
        $threatType = $features['threat_type'] ?? null;
        if ($threatType) {
            $threatTypeBoost = $this->getThreatTypeProbability($threatType);
            $combinedProbability = min(1, $combinedProbability + $threatTypeBoost);
        }
        
        // Limitar entre 0 e 1
        return min(1, max(0, $combinedProbability));
    }
    
    /**
     * Obtém evidência histórica do IP (probabilidade baseada em histórico)
     */
    private function getHistoricalEvidence($ipAddress) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_events,
                    SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked_count,
                    AVG(threat_score) as avg_threat
                FROM safenode_security_logs
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$ipAddress]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || (int)$result['total_events'] == 0) {
                return 0.3; // Probabilidade neutra se sem histórico
            }
            
            $blockRate = (int)$result['blocked_count'] / (int)$result['total_events'];
            $avgThreat = (float)$result['avg_threat'];
            
            // Calcular probabilidade baseada em taxa de bloqueio e threat score médio
            $evidence = ($blockRate * 0.6) + (($avgThreat / 100) * 0.4);
            
            return min(1, max(0, $evidence));
        } catch (PDOException $e) {
            return 0.3;
        }
    }
    
    /**
     * Obtém boost de probabilidade baseado no tipo de ameaça
     */
    private function getThreatTypeProbability($threatType) {
        // Tipos de ameaça mais críticos recebem boost maior
        $criticalTypes = [
            'sql_injection' => 0.15,
            'xss' => 0.12,
            'rce' => 0.20,
            'file_upload' => 0.10,
            'path_traversal' => 0.15,
            'ssrf' => 0.12
        ];
        
        return $criticalTypes[$threatType] ?? 0.05;
    }
    
    /**
     * Treina modelo com dados históricos usando análise estatística avançada
     * Sistema funcional com aprendizado adaptativo real
     */
    public function trainModel($days = 30) {
        if (!$this->db) return false;
        
        try {
            // Coletar dados históricos com mais features
            $stmt = $this->db->prepare("
                SELECT 
                    s.threat_score,
                    s.confidence_score,
                    s.action_taken,
                    s.threat_type,
                    s.ip_address,
                    s.created_at,
                    COALESCE(r.trust_score, 50) as ip_reputation,
                    s.request_uri
                FROM safenode_security_logs s
                LEFT JOIN safenode_ip_reputation r ON s.ip_address = r.ip_address
                WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND s.threat_score IS NOT NULL
                ORDER BY s.created_at DESC
                LIMIT 50000
            ");
            $stmt->execute([$days]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($data)) return false;
            
            // Análise estatística avançada
            $metrics = $this->calculateTrainingMetrics($data);
            
            // Ajustar pesos usando análise de correlação
            $this->adjustWeightsBasedOnMetrics($metrics);
            
            // Calcular performance do modelo
            $performance = $this->evaluateModelPerformance($data);
            
            // Preparar dados de performance para salvar
            $performance['samples_analyzed'] = count($data);
            
            // Salvar pesos ajustados com performance
            $this->saveModelWeights($performance);
            
            // Atualizar estatísticas base
            $this->loadStatistics();
            
            return [
                'accuracy' => round($performance['accuracy'], 4),
                'precision' => round($performance['precision'], 4),
                'recall' => round($performance['recall'], 4),
                'f1_score' => round($performance['f1_score'], 4),
                'false_positive_rate' => round($performance['false_positive_rate'], 4),
                'false_negative_rate' => round($performance['false_negative_rate'], 4),
                'samples_analyzed' => count($data),
                'weights' => $this->modelWeights,
                'metrics' => $metrics
            ];
        } catch (PDOException $e) {
            error_log("SafeNode ML Training Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calcula métricas de treinamento
     */
    private function calculateTrainingMetrics($data) {
        $metrics = [
            'threat_vs_blocked_correlation' => 0,
            'confidence_vs_accuracy' => 0,
            'reputation_vs_blocked_correlation' => 0,
            'time_pattern_analysis' => []
        ];
        
        $threatScores = [];
        $confidenceScores = [];
        $reputationScores = [];
        $blocked = [];
        
        foreach ($data as $row) {
            $threatScores[] = (float)$row['threat_score'];
            $confidenceScores[] = (float)($row['confidence_score'] ?? 50);
            $reputationScores[] = (float)($row['ip_reputation'] ?? 50);
            $blocked[] = $row['action_taken'] === 'blocked' ? 1 : 0;
        }
        
        // Calcular correlação entre threat_score e bloqueio
        if (count($threatScores) > 10) {
            $metrics['threat_vs_blocked_correlation'] = $this->calculateCorrelation($threatScores, $blocked);
        }
        
        // Calcular correlação entre confidence e acurácia (quando blocked = true, confidence deve ser alta)
        $metrics['confidence_vs_accuracy'] = $this->calculateCorrelation($confidenceScores, $blocked);
        
        // Calcular correlação entre reputação e bloqueio
        $invertedReputation = array_map(function($r) { return 100 - $r; }, $reputationScores);
        $metrics['reputation_vs_blocked_correlation'] = $this->calculateCorrelation($invertedReputation, $blocked);
        
        return $metrics;
    }
    
    /**
     * Calcula correlação de Pearson
     */
    private function calculateCorrelation($x, $y) {
        if (count($x) !== count($y) || count($x) < 2) {
            return 0;
        }
        
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }
        
        $numerator = ($n * $sumXY) - ($sumX * $sumY);
        $denominator = sqrt((($n * $sumX2) - ($sumX * $sumX)) * (($n * $sumY2) - ($sumY * $sumY)));
        
        if ($denominator == 0) {
            return 0;
        }
        
        return $numerator / $denominator;
    }
    
    /**
     * Ajusta pesos baseado em métricas
     */
    private function adjustWeightsBasedOnMetrics($metrics) {
        // Se threat_score tem alta correlação com bloqueios, aumentar seu peso
        if (abs($metrics['threat_vs_blocked_correlation']) > 0.5) {
            $adjustment = abs($metrics['threat_vs_blocked_correlation']) * 0.1;
            $this->modelWeights['threat_score'] = min(0.5, $this->modelWeights['threat_score'] + $adjustment);
        }
        
        // Se confidence tem alta correlação, aumentar seu peso
        if (abs($metrics['confidence_vs_accuracy']) > 0.4) {
            $adjustment = abs($metrics['confidence_vs_accuracy']) * 0.08;
            $this->modelWeights['confidence_score'] = min(0.4, $this->modelWeights['confidence_score'] + $adjustment);
        }
        
        // Se reputação tem alta correlação, aumentar seu peso
        if (abs($metrics['reputation_vs_blocked_correlation']) > 0.3) {
            $adjustment = abs($metrics['reputation_vs_blocked_correlation']) * 0.06;
            $this->modelWeights['ip_reputation'] = min(0.3, $this->modelWeights['ip_reputation'] + $adjustment);
        }
        
        // Normalizar pesos para somarem 1.0
        $total = array_sum($this->modelWeights);
        if ($total > 0) {
            foreach ($this->modelWeights as $key => $value) {
                $this->modelWeights[$key] = $value / $total;
            }
        }
    }
    
    /**
     * Avalia performance do modelo
     */
    private function evaluateModelPerformance($data) {
        $truePositives = 0;
        $trueNegatives = 0;
        $falsePositives = 0;
        $falseNegatives = 0;
        
        foreach ($data as $row) {
            $features = [
                'threat_score' => (float)$row['threat_score'],
                'confidence_score' => (float)($row['confidence_score'] ?? 50),
                'ip_reputation' => (float)($row['ip_reputation'] ?? 50),
                'behavior_score' => 50,
                'time_pattern_score' => 50,
                'ip_address' => $row['ip_address'],
                'threat_type' => $row['threat_type']
            ];
            
            $result = $this->calculateAdaptiveScore($features);
            $predicted = $result['is_attack'];
            $actual = $row['action_taken'] === 'blocked';
            
            if ($predicted && $actual) {
                $truePositives++;
            } elseif (!$predicted && !$actual) {
                $trueNegatives++;
            } elseif ($predicted && !$actual) {
                $falsePositives++;
            } elseif (!$predicted && $actual) {
                $falseNegatives++;
            }
        }
        
        $total = $truePositives + $trueNegatives + $falsePositives + $falseNegatives;
        
        $accuracy = $total > 0 ? ($truePositives + $trueNegatives) / $total : 0;
        $precision = ($truePositives + $falsePositives) > 0 ? $truePositives / ($truePositives + $falsePositives) : 0;
        $recall = ($truePositives + $falseNegatives) > 0 ? $truePositives / ($truePositives + $falseNegatives) : 0;
        $f1Score = ($precision + $recall) > 0 ? 2 * ($precision * $recall) / ($precision + $recall) : 0;
        $falsePositiveRate = ($truePositives + $falsePositives) > 0 ? $falsePositives / ($truePositives + $falsePositives) : 0;
        $falseNegativeRate = ($truePositives + $falseNegatives) > 0 ? $falseNegatives / ($truePositives + $falseNegatives) : 0;
        
        return [
            'accuracy' => $accuracy,
            'precision' => $precision,
            'recall' => $recall,
            'f1_score' => $f1Score,
            'false_positive_rate' => $falsePositiveRate,
            'false_negative_rate' => $falseNegativeRate,
            'true_positives' => $truePositives,
            'true_negatives' => $trueNegatives,
            'false_positives' => $falsePositives,
            'false_negatives' => $falseNegatives
        ];
    }
    
    /**
     * Salva pesos do modelo treinado
     */
    private function saveModelWeights($performance = null) {
        $cacheKey = 'ml_model_weights';
        $this->cache->set($cacheKey, $this->modelWeights, 86400 * 7); // 7 dias
        
        // Salvar no banco
        if ($this->db) {
            try {
                $this->ensureModelTableExists();
                
                $stmt = $this->db->prepare("
                    INSERT INTO safenode_ml_model 
                    (weights_data, accuracy, precision_score, recall_score, f1_score, false_positive_rate, samples_analyzed, trained_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    json_encode($this->modelWeights),
                    $performance['accuracy'] ?? null,
                    $performance['precision'] ?? null,
                    $performance['recall'] ?? null,
                    $performance['f1_score'] ?? null,
                    $performance['false_positive_rate'] ?? null,
                    $performance['samples_analyzed'] ?? null
                ]);
                
                // Manter apenas últimos 10 modelos
                $this->db->exec("
                    DELETE FROM safenode_ml_model 
                    WHERE id NOT IN (
                        SELECT id FROM (
                            SELECT id FROM safenode_ml_model 
                            ORDER BY trained_at DESC 
                            LIMIT 10
                        ) AS keep
                    )
                ");
            } catch (PDOException $e) {
                error_log("SafeNode ML Save Weights Error: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Carrega pesos do modelo (trained ou padrão)
     */
    public function loadModelWeights() {
        // Pesos padrão otimizados baseados em análise empírica
        $defaultWeights = [
            'threat_score' => 0.35,
            'confidence_score' => 0.25,
            'ip_reputation' => 0.20,
            'behavior_pattern' => 0.15,
            'time_pattern' => 0.05
        ];
        
        $cacheKey = 'ml_model_weights';
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            $this->modelWeights = $cached;
            return;
        }
        
        // Tentar carregar do banco
        if ($this->db) {
            try {
                // Garantir que a tabela existe
                $this->ensureModelTableExists();
                
                $stmt = $this->db->query("
                    SELECT weights_data FROM safenode_ml_model 
                    ORDER BY trained_at DESC LIMIT 1
                ");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && !empty($result['weights_data'])) {
                    $weights = json_decode($result['weights_data'], true);
                    if ($weights && is_array($weights)) {
                        $this->modelWeights = $weights;
                        $this->cache->set($cacheKey, $weights, 86400 * 7);
                        return;
                    }
                }
            } catch (PDOException $e) {
                // Usar pesos padrão
            }
        }
        
        // Usar pesos padrão se não encontrou trained
        $this->modelWeights = $defaultWeights;
    }
    
    /**
     * Garante que a tabela do modelo existe
     */
    private function ensureModelTableExists() {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_ml_model (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    model_version VARCHAR(50) DEFAULT '1.0',
                    weights_data TEXT,
                    accuracy DECIMAL(5,4),
                    precision_score DECIMAL(5,4),
                    recall_score DECIMAL(5,4),
                    f1_score DECIMAL(5,4),
                    false_positive_rate DECIMAL(5,4),
                    samples_analyzed INT,
                    trained_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_version (model_version),
                    INDEX idx_trained_at (trained_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (PDOException $e) {
            // Tabela já existe ou erro
        }
    }
}








