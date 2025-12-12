<?php
/**
 * SafeNode - ML Scoring System
 * Sistema de scoring adaptativo usando Machine Learning
 * 
 * Usa modelo simples baseado em regras (pode ser substituído por modelo ML real)
 * Features: threat_score, confidence_score, behavior patterns, IP reputation, time patterns
 */

class MLScoringSystem {
    private $db;
    private $cache;
    private $modelWeights;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
        
        // Pesos do modelo (em produção, viriam de treinamento ML)
        // Estes são valores iniciais que podem ser ajustados com dados históricos
        $this->modelWeights = [
            'threat_score' => 0.35,
            'confidence_score' => 0.25,
            'ip_reputation' => 0.20,
            'behavior_pattern' => 0.15,
            'time_pattern' => 0.05
        ];
    }
    
    /**
     * Calcula score adaptativo baseado em múltiplos fatores
     * 
     * @param array $features Features do evento
     * @return array Score e probabilidade
     */
    public function calculateAdaptiveScore($features) {
        $baseThreatScore = (float)($features['threat_score'] ?? 0);
        $confidenceScore = (float)($features['confidence_score'] ?? 50);
        $ipReputation = (float)($features['ip_reputation'] ?? 50);
        $behaviorScore = (float)($features['behavior_score'] ?? 50);
        $timePatternScore = (float)($features['time_pattern_score'] ?? 50);
        
        // Normalizar scores para 0-100
        $normalizedThreat = min(100, max(0, $baseThreatScore));
        $normalizedConfidence = min(100, max(0, $confidenceScore));
        $normalizedReputation = min(100, max(0, 100 - $ipReputation)); // Inverter (reputação baixa = score alto)
        $normalizedBehavior = min(100, max(0, $behaviorScore));
        $normalizedTime = min(100, max(0, $timePatternScore));
        
        // Calcular score ponderado
        $adaptiveScore = (
            $normalizedThreat * $this->modelWeights['threat_score'] +
            $normalizedConfidence * $this->modelWeights['confidence_score'] +
            $normalizedReputation * $this->modelWeights['ip_reputation'] +
            $normalizedBehavior * $this->modelWeights['behavior_pattern'] +
            $normalizedTime * $this->modelWeights['time_pattern']
        );
        
        // Ajustar baseado em padrões históricos
        $historicalAdjustment = $this->getHistoricalAdjustment($features);
        $adaptiveScore = $adaptiveScore * (1 + $historicalAdjustment);
        
        // Limitar entre 0-100
        $adaptiveScore = min(100, max(0, $adaptiveScore));
        
        // Calcular probabilidade de ser ataque real
        $probability = $this->calculateAttackProbability($adaptiveScore, $features);
        
        return [
            'adaptive_score' => round($adaptiveScore, 2),
            'base_threat_score' => $baseThreatScore,
            'probability' => round($probability, 4),
            'is_attack' => $probability >= 0.7,
            'confidence' => round($confidenceScore, 2),
            'factors' => [
                'threat_score' => round($normalizedThreat, 2),
                'confidence' => round($normalizedConfidence, 2),
                'ip_reputation' => round($normalizedReputation, 2),
                'behavior' => round($normalizedBehavior, 2),
                'time_pattern' => round($normalizedTime, 2),
                'historical_adjustment' => round($historicalAdjustment * 100, 2)
            ]
        ];
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
     * Calcula probabilidade de ser ataque real
     */
    private function calculateAttackProbability($adaptiveScore, $features) {
        // Função sigmoide para converter score em probabilidade
        // Ajustada para threshold de 50 = 0.5 probabilidade
        $x = ($adaptiveScore - 50) / 20; // Normalizar
        $probability = 1 / (1 + exp(-$x));
        
        // Ajustar baseado em confidence
        $confidence = (float)($features['confidence_score'] ?? 50);
        $confidenceFactor = ($confidence - 50) / 100; // -0.5 a +0.5
        $probability = $probability + ($confidenceFactor * 0.2); // Ajuste de até ±10%
        
        // Limitar entre 0 e 1
        return min(1, max(0, $probability));
    }
    
    /**
     * Treina modelo com dados históricos (simplificado)
     * Em produção, usar biblioteca ML real (TensorFlow, scikit-learn, etc)
     */
    public function trainModel($days = 30) {
        if (!$this->db) return false;
        
        try {
            // Coletar dados históricos
            $stmt = $this->db->prepare("
                SELECT 
                    threat_score,
                    confidence_score,
                    action_taken,
                    threat_type,
                    ip_address
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND threat_score IS NOT NULL
                LIMIT 10000
            ");
            $stmt->execute([$days]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($data)) return false;
            
            // Análise simples: ajustar pesos baseado em taxa de acerto
            // Em produção, usar algoritmo ML real (regressão, neural network, etc)
            
            $truePositives = 0;
            $falsePositives = 0;
            $total = count($data);
            
            foreach ($data as $row) {
                $isBlocked = $row['action_taken'] === 'blocked';
                $threatScore = (float)$row['threat_score'];
                
                // Se foi bloqueado e threat_score > 70 = verdadeiro positivo
                if ($isBlocked && $threatScore > 70) {
                    $truePositives++;
                } elseif (!$isBlocked && $threatScore > 70) {
                    $falsePositives++;
                }
            }
            
            $accuracy = $total > 0 ? ($truePositives / $total) : 0;
            $falsePositiveRate = ($truePositives + $falsePositives) > 0 
                ? ($falsePositives / ($truePositives + $falsePositives)) 
                : 0;
            
            // Ajustar pesos baseado em performance
            // Se muitos falsos positivos, aumentar peso de confidence
            if ($falsePositiveRate > 0.3) {
                $this->modelWeights['confidence_score'] = min(0.4, $this->modelWeights['confidence_score'] + 0.05);
                $this->modelWeights['threat_score'] = max(0.25, $this->modelWeights['threat_score'] - 0.05);
            }
            
            // Salvar pesos ajustados
            $this->saveModelWeights();
            
            return [
                'accuracy' => round($accuracy, 4),
                'false_positive_rate' => round($falsePositiveRate, 4),
                'weights' => $this->modelWeights
            ];
        } catch (PDOException $e) {
            error_log("SafeNode ML Training Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Salva pesos do modelo
     */
    private function saveModelWeights() {
        $cacheKey = 'ml_model_weights';
        $this->cache->set($cacheKey, $this->modelWeights, 86400 * 7); // 7 dias
        
        // Também salvar no banco se necessário
        if ($this->db) {
            try {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS safenode_ml_model (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        model_version VARCHAR(50) DEFAULT '1.0',
                        weights_data TEXT,
                        accuracy DECIMAL(5,4),
                        false_positive_rate DECIMAL(5,4),
                        trained_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_version (model_version)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                $stmt = $this->db->prepare("
                    INSERT INTO safenode_ml_model 
                    (weights_data, trained_at) 
                    VALUES (?, NOW())
                    ON DUPLICATE KEY UPDATE
                        weights_data = VALUES(weights_data),
                        trained_at = VALUES(trained_at)
                ");
                $stmt->execute([json_encode($this->modelWeights)]);
            } catch (PDOException $e) {
                // Ignorar
            }
        }
    }
    
    /**
     * Carrega pesos do modelo
     */
    public function loadModelWeights() {
        $cacheKey = 'ml_model_weights';
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            $this->modelWeights = $cached;
            return;
        }
        
        // Tentar carregar do banco
        if ($this->db) {
            try {
                $stmt = $this->db->query("
                    SELECT weights_data FROM safenode_ml_model 
                    ORDER BY trained_at DESC LIMIT 1
                ");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && !empty($result['weights_data'])) {
                    $weights = json_decode($result['weights_data'], true);
                    if ($weights) {
                        $this->modelWeights = $weights;
                        $this->cache->set($cacheKey, $weights, 86400 * 7);
                    }
                }
            } catch (PDOException $e) {
                // Usar pesos padrão
            }
        }
    }
}





