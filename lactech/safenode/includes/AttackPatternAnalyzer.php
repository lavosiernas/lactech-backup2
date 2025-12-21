<?php
/**
 * SafeNode - Attack Pattern Analyzer (FUNCIONAL)
 * Análise avançada de padrões de ataque em tempo real
 * 
 * STATUS: FUNCIONAL - Sistema completo de detecção e análise de padrões de ataque
 * 
 * Funcionalidades:
 * - Detecção de ataques coordenados e campanhas
 * - Análise de assinaturas de ataque específicas
 * - Correlação entre diferentes tipos de ameaças
 * - Identificação de sequências de ataque
 * - Learning de padrões baseado em histórico
 * - Scoring de padrões com probabilidade
 */

class AttackPatternAnalyzer {
    private $db;
    private $cache;
    private $knownPatterns;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
        $this->knownPatterns = null;
        
        // Garantir que tabela de padrões existe
        $this->ensurePatternsTableExists();
    }
    
    /**
     * Garante que tabela de padrões existe
     */
    private function ensurePatternsTableExists() {
        if (!$this->db) return;
        
        try {
            $this->db->query("SELECT 1 FROM safenode_attack_patterns LIMIT 1");
        } catch (PDOException $e) {
            try {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS safenode_attack_patterns (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        pattern_name VARCHAR(100) NOT NULL,
                        pattern_type VARCHAR(50) NOT NULL,
                        threat_type VARCHAR(50),
                        pattern_signature TEXT,
                        detection_count INT DEFAULT 0,
                        severity INT DEFAULT 50,
                        confidence DECIMAL(5,2) DEFAULT 0.00,
                        is_active TINYINT(1) DEFAULT 1,
                        first_detected DATETIME DEFAULT CURRENT_TIMESTAMP,
                        last_detected DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_type (pattern_type),
                        INDEX idx_threat_type (threat_type),
                        INDEX idx_active (is_active),
                        INDEX idx_severity (severity)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            } catch (PDOException $createErr) {
                error_log("AttackPatternAnalyzer Table Creation Error: " . $createErr->getMessage());
            }
        }
    }
    
    /**
     * Analisa padrões de ataque em tempo real com análise avançada
     * 
     * @param int $timeWindow Janela de tempo em segundos (padrão: 300 = 5 minutos)
     * @param int $siteId ID do site (opcional, para análise específica)
     * @return array Padrões detectados com probabilidades e confiança
     */
    public function analyzeAttackPatterns($timeWindow = 300, $siteId = null) {
        if (!$this->db) return [];
        
        $cacheKey = "attack_patterns:$timeWindow:" . ($siteId ?? 'all') . ":" . date('H:i');
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $patterns = [];
        
        // 1. Padrões básicos melhorados
        $coordinated = $this->detectCoordinatedAttackAdvanced($timeWindow, $siteId);
        if ($coordinated && $coordinated['detected']) {
            $patterns['coordinated_attack'] = $coordinated;
        }
        
        $reconnaissance = $this->detectReconnaissanceAdvanced($timeWindow, $siteId);
        if ($reconnaissance && $reconnaissance['detected']) {
            $patterns['reconnaissance'] = $reconnaissance;
        }
        
        $ddos = $this->detectDistributedDDoSAdvanced($timeWindow, $siteId);
        if ($ddos && $ddos['detected']) {
            $patterns['distributed_ddos'] = $ddos;
        }
        
        $escalation = $this->detectPrivilegeEscalationAdvanced($timeWindow, $siteId);
        if ($escalation && $escalation['detected']) {
            $patterns['privilege_escalation'] = $escalation;
        }
        
        $targeted = $this->detectTargetedAttackAdvanced($timeWindow, $siteId);
        if ($targeted && $targeted['detected']) {
            $patterns['targeted_attack'] = $targeted;
        }
        
        // 2. Novos padrões avançados
        $campaign = $this->detectAttackCampaign($timeWindow, $siteId);
        if ($campaign && $campaign['detected']) {
            $patterns['attack_campaign'] = $campaign;
        }
        
        $sequence = $this->detectAttackSequence($timeWindow, $siteId);
        if ($sequence && $sequence['detected']) {
            $patterns['attack_sequence'] = $sequence;
        }
        
        $lateral = $this->detectLateralMovement($timeWindow, $siteId);
        if ($lateral && $lateral['detected']) {
            $patterns['lateral_movement'] = $lateral;
        }
        
        $signature = $this->detectKnownSignatures($timeWindow, $siteId);
        if ($signature && $signature['detected']) {
            $patterns['known_signature'] = $signature;
        }
        
        // 3. Correlação de padrões
        $correlation = $this->correlatePatterns($patterns);
        if (!empty($correlation)) {
            $patterns['pattern_correlation'] = $correlation;
        }
        
        // Ordenar por severidade e probabilidade
        usort($patterns, function($a, $b) {
            $priorityA = ($a['severity_score'] ?? 0) * 10 + ($a['probability'] ?? 0.5) * 100;
            $priorityB = ($b['severity_score'] ?? 0) * 10 + ($b['probability'] ?? 0.5) * 100;
            return $priorityB <=> $priorityA;
        });
        
        // Cache por 2 minutos
        $this->cache->set($cacheKey, $patterns, 120);
        
        return $patterns;
    }
    
    /**
     * Detecta ataque coordenado (versão avançada)
     */
    private function detectCoordinatedAttackAdvanced($timeWindow, $siteId = null) {
        try {
            $siteFilter = $siteId ? "AND site_id = ?" : "";
            $params = $siteId ? [$siteId, $timeWindow] : [$timeWindow];
            
            $stmt = $this->db->prepare("
                SELECT 
                    request_uri,
                    COUNT(DISTINCT ip_address) as unique_attackers,
                    COUNT(*) as total_attacks,
                    AVG(threat_score) as avg_threat_score,
                    MAX(threat_score) as max_threat_score,
                    COUNT(DISTINCT threat_type) as threat_types_count,
                    COUNT(DISTINCT country_code) as countries_count,
                    MIN(created_at) as first_attack,
                    MAX(created_at) as last_attack
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND threat_score >= 50
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                $siteFilter
                GROUP BY request_uri
                HAVING unique_attackers >= 3 AND total_attacks >= 10
                ORDER BY unique_attackers DESC, total_attacks DESC
                LIMIT 20
            ");
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                $mostAttacked = $results[0];
                $uniqueAttackers = (int)$mostAttacked['unique_attackers'];
                $totalAttacks = (int)$mostAttacked['total_attacks'];
                $countriesCount = (int)$mostAttacked['countries_count'];
                
                // Calcular probabilidade baseada em múltiplos fatores
                $probability = 0.5;
                $severityScore = 50;
                
                // Múltiplos IPs = maior probabilidade
                if ($uniqueAttackers >= 10) {
                    $probability = 0.9;
                    $severityScore = 90;
                } elseif ($uniqueAttackers >= 5) {
                    $probability = 0.75;
                    $severityScore = 75;
                } elseif ($uniqueAttackers >= 3) {
                    $probability = 0.6;
                    $severityScore = 60;
                }
                
                // Múltiplos países = possível botnet
                if ($countriesCount >= 5) {
                    $probability = min(0.95, $probability + 0.15);
                    $severityScore = min(100, $severityScore + 15);
                }
                
                // Alta concentração de ataques em pouco tempo
                $attackRate = $totalAttacks / ($timeWindow / 60); // Por minuto
                if ($attackRate > 5) {
                    $probability = min(0.95, $probability + 0.1);
                    $severityScore = min(100, $severityScore + 10);
                }
                
                // Tempo de duração do ataque
                $firstAttack = strtotime($mostAttacked['first_attack']);
                $lastAttack = strtotime($mostAttacked['last_attack']);
                $duration = $lastAttack - $firstAttack;
                
                // Se ataque durou pouco tempo com muitos ataques = coordenado
                if ($duration > 0 && ($totalAttacks / $duration) > 0.1) {
                    $probability = min(0.95, $probability + 0.1);
                }
                
                return [
                    'detected' => true,
                    'type' => 'coordinated_attack',
                    'severity' => $severityScore >= 80 ? 'critical' : ($severityScore >= 60 ? 'high' : 'medium'),
                    'severity_score' => $severityScore,
                    'probability' => round($probability, 3),
                    'confidence' => count($results) > 1 ? 'high' : 'medium',
                    'target' => $mostAttacked['request_uri'],
                    'unique_attackers' => $uniqueAttackers,
                    'total_attacks' => $totalAttacks,
                    'countries_involved' => $countriesCount,
                    'avg_threat_score' => round((float)$mostAttacked['avg_threat_score'], 2),
                    'max_threat_score' => (int)$mostAttacked['max_threat_score'],
                    'threat_types_count' => (int)$mostAttacked['threat_types_count'],
                    'attack_rate_per_minute' => round($attackRate, 2),
                    'duration_seconds' => $duration,
                    'first_attack' => $mostAttacked['first_attack'],
                    'last_attack' => $mostAttacked['last_attack'],
                    'description' => "Ataque coordenado: {$uniqueAttackers} IPs de {$countriesCount} países atacando {$mostAttacked['request_uri']}"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPattern Coordinated Advanced Error: " . $e->getMessage());
        }
        
        return ['detected' => false];
    }
    
    /**
     * Detecta reconhecimento (versão avançada)
     */
    private function detectReconnaissanceAdvanced($timeWindow, $siteId = null) {
        try {
            $siteFilter = $siteId ? "AND site_id = ?" : "";
            $params = $siteId ? [$siteId, $timeWindow] : [$timeWindow];
            
            // Padrões de reconhecimento: múltiplas requisições a endpoints diferentes
            $stmt = $this->db->prepare("
                SELECT 
                    ip_address,
                    COUNT(DISTINCT request_uri) as unique_endpoints,
                    COUNT(*) as total_requests,
                    COUNT(DISTINCT threat_type) as threat_types,
                    SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked_count,
                    AVG(threat_score) as avg_threat_score,
                    MIN(created_at) as first_request,
                    MAX(created_at) as last_request,
                    GROUP_CONCAT(DISTINCT request_uri ORDER BY request_uri SEPARATOR ', ') as endpoints
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND request_uri NOT LIKE '%/api/%'
                AND request_uri NOT LIKE '%.css'
                AND request_uri NOT LIKE '%.js'
                AND request_uri NOT LIKE '%.png'
                AND request_uri NOT LIKE '%.jpg'
                AND request_uri NOT LIKE '%.gif'
                AND request_uri NOT LIKE '%.svg'
                AND request_uri NOT LIKE '%.ico'
                $siteFilter
                GROUP BY ip_address
                HAVING unique_endpoints >= 8 AND total_requests >= 12
                ORDER BY unique_endpoints DESC, total_requests DESC
                LIMIT 20
            ");
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                $recon = $results[0];
                $uniqueEndpoints = (int)$recon['unique_endpoints'];
                $totalRequests = (int)$recon['total_requests'];
                $blockedCount = (int)$recon['blocked_count'];
                
                // Calcular padrões de scanning
                $endpoints = explode(', ', $recon['endpoints']);
                $suspiciousEndpoints = 0;
                $suspiciousPaths = ['/admin', '/wp-admin', '/phpmyadmin', '/.env', '/config', '/.git', '/backup', '/login', '/setup'];
                
                foreach ($endpoints as $endpoint) {
                    foreach ($suspiciousPaths as $path) {
                        if (stripos($endpoint, $path) !== false) {
                            $suspiciousEndpoints++;
                            break;
                        }
                    }
                }
                
                // Taxa de endpoints suspeitos
                $suspiciousRate = count($endpoints) > 0 ? ($suspiciousEndpoints / count($endpoints)) * 100 : 0;
                
                // Calcular probabilidade
                $probability = 0.5;
                $severityScore = 50;
                
                if ($uniqueEndpoints >= 20) {
                    $probability = 0.9;
                    $severityScore = 85;
                } elseif ($uniqueEndpoints >= 15) {
                    $probability = 0.75;
                    $severityScore = 70;
                } elseif ($uniqueEndpoints >= 10) {
                    $probability = 0.6;
                    $severityScore = 60;
                }
                
                // Boost se muitos endpoints são suspeitos
                if ($suspiciousRate > 50) {
                    $probability = min(0.95, $probability + 0.15);
                    $severityScore = min(100, $severityScore + 15);
                }
                
                // Boost se muitas requisições foram bloqueadas
                $blockRate = $totalRequests > 0 ? ($blockedCount / $totalRequests) * 100 : 0;
                if ($blockRate > 70) {
                    $probability = min(0.95, $probability + 0.1);
                }
                
                // Velocidade de scanning
                $firstReq = strtotime($recon['first_request']);
                $lastReq = strtotime($recon['last_request']);
                $duration = max(1, $lastReq - $firstReq);
                $scanSpeed = $uniqueEndpoints / ($duration / 60); // Endpoints por minuto
                
                if ($scanSpeed > 2) {
                    $probability = min(0.95, $probability + 0.1);
                    $severityScore = min(100, $severityScore + 10);
                }
                
                return [
                    'detected' => true,
                    'type' => 'reconnaissance',
                    'severity' => $severityScore >= 75 ? 'high' : ($severityScore >= 60 ? 'medium' : 'low'),
                    'severity_score' => $severityScore,
                    'probability' => round($probability, 3),
                    'confidence' => count($results) > 1 ? 'high' : 'medium',
                    'ip_address' => $recon['ip_address'],
                    'unique_endpoints' => $uniqueEndpoints,
                    'total_requests' => $totalRequests,
                    'blocked_count' => $blockedCount,
                    'block_rate' => round($blockRate, 2),
                    'suspicious_endpoints' => $suspiciousEndpoints,
                    'suspicious_rate' => round($suspiciousRate, 2),
                    'scan_speed_per_minute' => round($scanSpeed, 2),
                    'threat_types' => (int)$recon['threat_types'],
                    'duration_seconds' => $duration,
                    'description' => "Reconhecimento detectado: IP {$recon['ip_address']} escaneou {$uniqueEndpoints} endpoints diferentes ({$suspiciousEndpoints} suspeitos)"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPattern Reconnaissance Advanced Error: " . $e->getMessage());
        }
        
        return ['detected' => false];
    }
    
    /**
     * Detecta DDoS distribuído (versão avançada)
     */
    private function detectDistributedDDoSAdvanced($timeWindow, $siteId = null) {
        try {
            $siteFilter = $siteId ? "AND site_id = ?" : "";
            $params = $siteId ? [$siteId, $timeWindow] : [$timeWindow];
            
            // Análise por janelas de tempo menores (mais preciso)
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as second_window,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    COUNT(*) as total_requests,
                    COUNT(DISTINCT request_uri) as unique_endpoints,
                    COUNT(DISTINCT country_code) as countries,
                    AVG(threat_score) as avg_threat_score,
                    SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked_count
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                $siteFilter
                GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s')
                HAVING unique_ips >= 10 AND total_requests >= 50
                ORDER BY total_requests DESC, unique_ips DESC
                LIMIT 10
            ");
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                $peak = $results[0];
                $uniqueIPs = (int)$peak['unique_ips'];
                $totalRequests = (int)$peak['total_requests'];
                $countries = (int)$peak['countries'];
                
                // Calcular taxa de requisições
                $requestsPerSecond = $totalRequests; // Por segundo na janela
                $requestsPerMinute = $totalRequests * 60; // Projeção por minuto
                
                // Análise de padrão DDoS
                $probability = 0.5;
                $severityScore = 60;
                
                // Critérios para DDoS
                if ($uniqueIPs >= 50 && $totalRequests >= 500) {
                    $probability = 0.95;
                    $severityScore = 95;
                } elseif ($uniqueIPs >= 30 && $totalRequests >= 300) {
                    $probability = 0.85;
                    $severityScore = 85;
                } elseif ($uniqueIPs >= 20 && $totalRequests >= 200) {
                    $probability = 0.75;
                    $severityScore = 75;
                } elseif ($uniqueIPs >= 10 && $totalRequests >= 100) {
                    $probability = 0.65;
                    $severityScore = 65;
                }
                
                // Múltiplos países = possível botnet DDoS
                if ($countries >= 10) {
                    $probability = min(0.98, $probability + 0.15);
                    $severityScore = min(100, $severityScore + 15);
                }
                
                // Muitos endpoints diferentes = DDoS de aplicação
                $uniqueEndpoints = (int)$peak['unique_endpoints'];
                if ($uniqueEndpoints > 50) {
                    $probability = min(0.95, $probability + 0.1);
                    $severityScore = min(100, $severityScore + 10);
                }
                
                // Verificar se há múltiplas janelas com padrão similar (sustentado)
                $sustained = count($results) >= 3;
                if ($sustained) {
                    $probability = min(0.98, $probability + 0.1);
                    $severityScore = min(100, $severityScore + 10);
                }
                
                // Taxa de bloqueio
                $blockRate = $totalRequests > 0 ? (($peak['blocked_count'] / $totalRequests) * 100) : 0;
                
                return [
                    'detected' => true,
                    'type' => 'distributed_ddos',
                    'severity' => $severityScore >= 85 ? 'critical' : ($severityScore >= 70 ? 'high' : 'medium'),
                    'severity_score' => $severityScore,
                    'probability' => round($probability, 3),
                    'confidence' => $sustained ? 'high' : 'medium',
                    'unique_ips' => $uniqueIPs,
                    'total_requests' => $totalRequests,
                    'requests_per_second' => round($requestsPerSecond, 2),
                    'requests_per_minute_projected' => round($requestsPerMinute, 2),
                    'countries_involved' => $countries,
                    'unique_endpoints' => $uniqueEndpoints,
                    'block_rate' => round($blockRate, 2),
                    'is_sustained' => $sustained,
                    'peak_window' => $peak['second_window'],
                    'description' => "DDoS distribuído detectado: {$uniqueIPs} IPs de {$countries} países, {$totalRequests} req/s"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPattern DDoS Advanced Error: " . $e->getMessage());
        }
        
        return ['detected' => false];
    }
    
    /**
     * Detecta escalação de privilégios (versão avançada)
     */
    private function detectPrivilegeEscalationAdvanced($timeWindow, $siteId = null) {
        try {
            $siteFilter = $siteId ? "AND site_id = ?" : "";
            $params = $siteId ? [$siteId, $timeWindow] : [$timeWindow];
            
            // Endpoints administrativos e sensíveis (lista expandida)
            $stmt = $this->db->prepare("
                SELECT 
                    ip_address,
                    COUNT(DISTINCT CASE 
                        WHEN request_uri LIKE '%/admin%' OR 
                             request_uri LIKE '%/login%' OR 
                             request_uri LIKE '%/wp-admin%' OR
                             request_uri LIKE '%/phpmyadmin%' OR
                             request_uri LIKE '%/config%' OR
                             request_uri LIKE '%/backup%' OR
                             request_uri LIKE '%/setup%' OR
                             request_uri LIKE '%/.env%' OR
                             request_uri LIKE '%/.git%' OR
                             request_uri LIKE '%/cpanel%' OR
                             request_uri LIKE '%/root%'
                        THEN request_uri 
                    END) as admin_endpoints_attempted,
                    COUNT(*) as total_attempts,
                    COUNT(DISTINCT threat_type) as threat_types,
                    MAX(threat_score) as max_threat_score,
                    AVG(threat_score) as avg_threat_score,
                    MIN(created_at) as first_attempt,
                    MAX(created_at) as last_attempt,
                    GROUP_CONCAT(DISTINCT request_uri ORDER BY created_at SEPARATOR ' -> ') as attempt_sequence
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND (request_uri LIKE '%/admin%' OR 
                     request_uri LIKE '%/login%' OR 
                     request_uri LIKE '%/wp-admin%' OR
                     request_uri LIKE '%/phpmyadmin%' OR
                     request_uri LIKE '%/config%' OR
                     request_uri LIKE '%/backup%' OR
                     request_uri LIKE '%/setup%' OR
                     request_uri LIKE '%/.env%' OR
                     request_uri LIKE '%/.git%')
                $siteFilter
                GROUP BY ip_address
                HAVING admin_endpoints_attempted >= 2 AND total_attempts >= 3
                ORDER BY admin_endpoints_attempted DESC, total_attempts DESC
                LIMIT 20
            ");
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                $escalation = $results[0];
                $adminEndpoints = (int)$escalation['admin_endpoints_attempted'];
                $totalAttempts = (int)$escalation['total_attempts'];
                $threatTypes = (int)$escalation['threat_types'];
                
                // Verificar se há progressão (sequência de tentativas)
                $sequence = $escalation['attempt_sequence'];
                $hasProgression = false;
                if (!empty($sequence)) {
                    $endpoints = explode(' -> ', $sequence);
                    // Se há progressão de endpoints menos sensíveis para mais sensíveis
                    $hasProgression = count($endpoints) >= 3;
                }
                
                // Calcular probabilidade e severidade
                $probability = 0.6;
                $severityScore = 70;
                
                if ($adminEndpoints >= 5) {
                    $probability = 0.95;
                    $severityScore = 95;
                } elseif ($adminEndpoints >= 4) {
                    $probability = 0.85;
                    $severityScore = 85;
                } elseif ($adminEndpoints >= 3) {
                    $probability = 0.75;
                    $severityScore = 75;
                }
                
                // Boost se há progressão clara
                if ($hasProgression) {
                    $probability = min(0.98, $probability + 0.15);
                    $severityScore = min(100, $severityScore + 15);
                }
                
                // Boost se múltiplos tipos de ameaça
                if ($threatTypes >= 3) {
                    $probability = min(0.95, $probability + 0.1);
                    $severityScore = min(100, $severityScore + 10);
                }
                
                // Velocidade de tentativas
                $firstAttempt = strtotime($escalation['first_attempt']);
                $lastAttempt = strtotime($escalation['last_attempt']);
                $duration = max(1, $lastAttempt - $firstAttempt);
                $attemptRate = $totalAttempts / ($duration / 60); // Tentativas por minuto
                
                if ($attemptRate > 2) {
                    $probability = min(0.95, $probability + 0.1);
                }
                
                return [
                    'detected' => true,
                    'type' => 'privilege_escalation',
                    'severity' => $severityScore >= 85 ? 'critical' : ($severityScore >= 70 ? 'high' : 'medium'),
                    'severity_score' => $severityScore,
                    'probability' => round($probability, 3),
                    'confidence' => $hasProgression ? 'high' : 'medium',
                    'ip_address' => $escalation['ip_address'],
                    'admin_endpoints' => $adminEndpoints,
                    'total_attempts' => $totalAttempts,
                    'threat_types' => $threatTypes,
                    'max_threat_score' => (int)$escalation['max_threat_score'],
                    'avg_threat_score' => round((float)$escalation['avg_threat_score'], 2),
                    'has_progression' => $hasProgression,
                    'attempt_sequence' => $sequence,
                    'attempt_rate_per_minute' => round($attemptRate, 2),
                    'duration_seconds' => $duration,
                    'description' => "Escalação de privilégios: IP {$escalation['ip_address']} tentou {$adminEndpoints} endpoints administrativos diferentes"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPattern Escalation Advanced Error: " . $e->getMessage());
        }
        
        return ['detected' => false];
    }
    
    /**
     * Detecta ataque direcionado (versão avançada)
     */
    private function detectTargetedAttackAdvanced($timeWindow, $siteId = null) {
        try {
            $siteFilter = $siteId ? "AND site_id = ?" : "";
            $params = $siteId ? [$siteId, $timeWindow] : [$timeWindow];
            
            $stmt = $this->db->prepare("
                SELECT 
                    ip_address,
                    COUNT(DISTINCT threat_type) as threat_types_count,
                    COUNT(*) as total_attacks,
                    COUNT(DISTINCT request_uri) as unique_targets,
                    COUNT(DISTINCT country_code) as countries,
                    GROUP_CONCAT(DISTINCT threat_type ORDER BY threat_type SEPARATOR ', ') as threat_types,
                    AVG(threat_score) as avg_threat_score,
                    MAX(threat_score) as max_threat_score,
                    MIN(created_at) as first_attack,
                    MAX(created_at) as last_attack
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND threat_type IS NOT NULL
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                $siteFilter
                GROUP BY ip_address
                HAVING threat_types_count >= 2 AND total_attacks >= 5
                ORDER BY threat_types_count DESC, total_attacks DESC
                LIMIT 20
            ");
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                $targeted = $results[0];
                $threatTypesCount = (int)$targeted['threat_types_count'];
                $totalAttacks = (int)$targeted['total_attacks'];
                $uniqueTargets = (int)$targeted['unique_targets'];
                
                // Analisar tipos de ameaça (alguns são mais críticos juntos)
                $criticalCombinations = [
                    'sql_xss' => [['sql_injection', 'xss'], 0.9],
                    'sql_file' => [['sql_injection', 'file_upload'], 0.95],
                    'xss_rce' => [['xss', 'rce'], 0.9],
                    'sql_xss_rce' => [['sql_injection', 'xss', 'rce'], 0.98]
                ];
                
                $threatTypesArray = explode(', ', $targeted['threat_types']);
                $probability = 0.6;
                $severityScore = 70;
                
                // Verificar combinações críticas
                foreach ($criticalCombinations as $key => $data) {
                    list($combination, $boost) = $data;
                    $matches = 0;
                    foreach ($combination as $type) {
                        if (in_array($type, $threatTypesArray)) {
                            $matches++;
                        }
                    }
                    if ($matches == count($combination)) {
                        $probability = max($probability, $boost);
                        $severityScore = max($severityScore, 95);
                    }
                }
                
                // Ajustar baseado em quantidade
                if ($threatTypesCount >= 5) {
                    $probability = max($probability, 0.95);
                    $severityScore = max($severityScore, 95);
                } elseif ($threatTypesCount >= 4) {
                    $probability = max($probability, 0.85);
                    $severityScore = max($severityScore, 85);
                } elseif ($threatTypesCount >= 3) {
                    $probability = max($probability, 0.75);
                    $severityScore = max($severityScore, 75);
                }
                
                // Foco em poucos alvos = mais direcionado
                if ($uniqueTargets < 5 && $totalAttacks > 10) {
                    $probability = min(0.98, $probability + 0.1);
                    $severityScore = min(100, $severityScore + 10);
                }
                
                // Duração e intensidade
                $firstAttack = strtotime($targeted['first_attack']);
                $lastAttack = strtotime($targeted['last_attack']);
                $duration = max(1, $lastAttack - $firstAttack);
                $intensity = $totalAttacks / ($duration / 60); // Ataques por minuto
                
                if ($intensity > 5) {
                    $probability = min(0.95, $probability + 0.1);
                }
                
                return [
                    'detected' => true,
                    'type' => 'targeted_attack',
                    'severity' => $severityScore >= 85 ? 'critical' : ($severityScore >= 70 ? 'high' : 'medium'),
                    'severity_score' => $severityScore,
                    'probability' => round($probability, 3),
                    'confidence' => $threatTypesCount >= 4 ? 'high' : 'medium',
                    'ip_address' => $targeted['ip_address'],
                    'threat_types_count' => $threatTypesCount,
                    'total_attacks' => $totalAttacks,
                    'unique_targets' => $uniqueTargets,
                    'threat_types' => $targeted['threat_types'],
                    'avg_threat_score' => round((float)$targeted['avg_threat_score'], 2),
                    'max_threat_score' => (int)$targeted['max_threat_score'],
                    'intensity_per_minute' => round($intensity, 2),
                    'duration_seconds' => $duration,
                    'countries' => (int)$targeted['countries'],
                    'description' => "Ataque direcionado: IP {$targeted['ip_address']} executou {$threatTypesCount} tipos diferentes de ataque ({$totalAttacks} tentativas)"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPattern Targeted Advanced Error: " . $e->getMessage());
        }
        
        return ['detected' => false];
    }
    
    /**
     * Detecta campanha de ataque (múltiplos IPs, padrão similar)
     */
    private function detectAttackCampaign($timeWindow, $siteId = null) {
        try {
            $siteFilter = $siteId ? "AND site_id = ?" : "";
            $params = $siteId ? [$siteId, $timeWindow] : [$timeWindow];
            
            // Campanha: múltiplos IPs executando mesmo tipo de ataque em sequência
            $stmt = $this->db->prepare("
                SELECT 
                    threat_type,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    COUNT(*) as total_attacks,
                    COUNT(DISTINCT request_uri) as unique_targets,
                    AVG(threat_score) as avg_score,
                    MIN(created_at) as campaign_start,
                    MAX(created_at) as campaign_end
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND threat_type IS NOT NULL
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                $siteFilter
                GROUP BY threat_type
                HAVING unique_ips >= 5 AND total_attacks >= 15
                ORDER BY unique_ips DESC, total_attacks DESC
                LIMIT 10
            ");
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                $campaign = $results[0];
                $uniqueIPs = (int)$campaign['unique_ips'];
                $totalAttacks = (int)$campaign['total_attacks'];
                
                $probability = 0.7;
                $severityScore = 75;
                
                if ($uniqueIPs >= 20) {
                    $probability = 0.95;
                    $severityScore = 95;
                } elseif ($uniqueIPs >= 10) {
                    $probability = 0.85;
                    $severityScore = 85;
                }
                
                $start = strtotime($campaign['campaign_start']);
                $end = strtotime($campaign['campaign_end']);
                $duration = $end - $start;
                
                return [
                    'detected' => true,
                    'type' => 'attack_campaign',
                    'severity' => $severityScore >= 85 ? 'high' : 'medium',
                    'severity_score' => $severityScore,
                    'probability' => round($probability, 3),
                    'confidence' => 'high',
                    'threat_type' => $campaign['threat_type'],
                    'unique_ips' => $uniqueIPs,
                    'total_attacks' => $totalAttacks,
                    'unique_targets' => (int)$campaign['unique_targets'],
                    'avg_score' => round((float)$campaign['avg_score'], 2),
                    'duration_seconds' => $duration,
                    'description' => "Campanha de ataque detectada: {$uniqueIPs} IPs executando {$campaign['threat_type']}"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPattern Campaign Error: " . $e->getMessage());
        }
        
        return ['detected' => false];
    }
    
    /**
     * Detecta sequência de ataque (progressão lógica)
     */
    private function detectAttackSequence($timeWindow, $siteId = null) {
        try {
            $siteFilter = $siteId ? "AND site_id = ?" : "";
            $params = $siteId ? [$siteId, $timeWindow] : [$timeWindow];
            
            // Sequência: mesmo IP, tipos de ataque em ordem lógica (ex: recon -> exploit -> privilege)
            $stmt = $this->db->prepare("
                SELECT 
                    ip_address,
                    COUNT(DISTINCT threat_type) as sequence_length,
                    COUNT(*) as total_attacks,
                    GROUP_CONCAT(DISTINCT threat_type ORDER BY MIN(created_at) SEPARATOR ' -> ') as sequence,
                    MIN(created_at) as sequence_start,
                    MAX(created_at) as sequence_end,
                    AVG(threat_score) as avg_score
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND threat_type IS NOT NULL
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                $siteFilter
                GROUP BY ip_address
                HAVING sequence_length >= 3 AND total_attacks >= 5
                ORDER BY sequence_length DESC
                LIMIT 20
            ");
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                $sequence = $results[0];
                $sequenceLength = (int)$sequence['sequence_length'];
                
                // Verificar se sequência faz sentido (recon -> exploit -> escalate)
                $seq = $sequence['sequence'];
                $hasLogicalProgression = false;
                if (stripos($seq, 'reconnaissance') !== false || stripos($seq, 'scanning') !== false) {
                    if (stripos($seq, 'sql_injection') !== false || stripos($seq, 'xss') !== false || stripos($seq, 'rce') !== false) {
                        $hasLogicalProgression = true;
                    }
                }
                
                $probability = 0.65;
                $severityScore = 70;
                
                if ($hasLogicalProgression) {
                    $probability = 0.9;
                    $severityScore = 90;
                }
                
                if ($sequenceLength >= 5) {
                    $probability = min(0.95, $probability + 0.1);
                    $severityScore = min(100, $severityScore + 10);
                }
                
                $start = strtotime($sequence['sequence_start']);
                $end = strtotime($sequence['sequence_end']);
                $duration = $end - $start;
                
                return [
                    'detected' => true,
                    'type' => 'attack_sequence',
                    'severity' => $severityScore >= 85 ? 'critical' : ($severityScore >= 70 ? 'high' : 'medium'),
                    'severity_score' => $severityScore,
                    'probability' => round($probability, 3),
                    'confidence' => $hasLogicalProgression ? 'high' : 'medium',
                    'ip_address' => $sequence['ip_address'],
                    'sequence_length' => $sequenceLength,
                    'sequence' => $seq,
                    'total_attacks' => (int)$sequence['total_attacks'],
                    'has_logical_progression' => $hasLogicalProgression,
                    'duration_seconds' => $duration,
                    'avg_score' => round((float)$sequence['avg_score'], 2),
                    'description' => "Sequência de ataque detectada: {$seq}"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPattern Sequence Error: " . $e->getMessage());
        }
        
        return ['detected' => false];
    }
    
    /**
     * Detecta movimento lateral (múltiplos endpoints do mesmo IP)
     */
    private function detectLateralMovement($timeWindow, $siteId = null) {
        try {
            $siteFilter = $siteId ? "AND site_id = ?" : "";
            $params = $siteId ? [$siteId, $timeWindow] : [$timeWindow];
            
            // Movimento lateral: mesmo IP acessando muitos endpoints diferentes rapidamente
            $stmt = $this->db->prepare("
                SELECT 
                    ip_address,
                    COUNT(DISTINCT request_uri) as unique_endpoints,
                    COUNT(*) as total_requests,
                    COUNT(DISTINCT threat_type) as threat_types,
                    MIN(created_at) as first_access,
                    MAX(created_at) as last_access
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                $siteFilter
                GROUP BY ip_address
                HAVING unique_endpoints >= 15 AND total_requests >= 20
                ORDER BY unique_endpoints DESC
                LIMIT 20
            ");
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                $movement = $results[0];
                $uniqueEndpoints = (int)$movement['unique_endpoints'];
                $totalRequests = (int)$movement['total_requests'];
                
                $first = strtotime($movement['first_access']);
                $last = strtotime($movement['last_access']);
                $duration = max(1, $last - $first);
                $speed = $uniqueEndpoints / ($duration / 60); // Endpoints por minuto
                
                $probability = 0.6;
                $severityScore = 65;
                
                if ($speed > 5) {
                    $probability = 0.9;
                    $severityScore = 90;
                } elseif ($speed > 3) {
                    $probability = 0.75;
                    $severityScore = 75;
                }
                
                return [
                    'detected' => true,
                    'type' => 'lateral_movement',
                    'severity' => $severityScore >= 85 ? 'high' : 'medium',
                    'severity_score' => $severityScore,
                    'probability' => round($probability, 3),
                    'confidence' => 'medium',
                    'ip_address' => $movement['ip_address'],
                    'unique_endpoints' => $uniqueEndpoints,
                    'total_requests' => $totalRequests,
                    'threat_types' => (int)$movement['threat_types'],
                    'movement_speed_per_minute' => round($speed, 2),
                    'duration_seconds' => $duration,
                    'description' => "Movimento lateral detectado: IP {$movement['ip_address']} acessou {$uniqueEndpoints} endpoints em " . round($duration / 60, 1) . " minutos"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPattern Lateral Movement Error: " . $e->getMessage());
        }
        
        return ['detected' => false];
    }
    
    /**
     * Detecta assinaturas conhecidas de ataque
     */
    private function detectKnownSignatures($timeWindow, $siteId = null) {
        try {
            // Carregar padrões conhecidos
            if ($this->knownPatterns === null) {
                $this->loadKnownPatterns();
            }
            
            if (empty($this->knownPatterns)) {
                return ['detected' => false];
            }
            
            $siteFilter = $siteId ? "AND site_id = ?" : "";
            $params = $siteId ? [$siteId, $timeWindow] : [$timeWindow];
            
            // Verificar se algum padrão conhecido foi detectado
            $signatureMatches = [];
            
            foreach ($this->knownPatterns as $pattern) {
                if (!$pattern['is_active']) continue;
                
                $signature = $pattern['pattern_signature'];
                $decoded = json_decode($signature, true);
                
                if (!$decoded) continue;
                
                // Buscar logs que correspondem ao padrão
                $query = $this->buildSignatureQuery($decoded, $siteFilter);
                if ($query) {
                    $stmt = $this->db->prepare($query);
                    $stmt->execute($params);
                    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($matches) >= ($decoded['min_occurrences'] ?? 3)) {
                        $signatureMatches[] = [
                            'pattern_name' => $pattern['pattern_name'],
                            'pattern_type' => $pattern['pattern_type'],
                            'matches' => count($matches),
                            'severity' => (int)$pattern['severity'],
                            'confidence' => (float)$pattern['confidence']
                        ];
                    }
                }
            }
            
            if (!empty($signatureMatches)) {
                $bestMatch = $signatureMatches[0];
                return [
                    'detected' => true,
                    'type' => 'known_signature',
                    'severity' => $bestMatch['severity'] >= 80 ? 'critical' : ($bestMatch['severity'] >= 60 ? 'high' : 'medium'),
                    'severity_score' => $bestMatch['severity'],
                    'probability' => round($bestMatch['confidence'] / 100, 3),
                    'confidence' => 'high',
                    'pattern_name' => $bestMatch['pattern_name'],
                    'pattern_type' => $bestMatch['pattern_type'],
                    'matches' => $bestMatch['matches'],
                    'total_signatures_matched' => count($signatureMatches),
                    'description' => "Assinatura conhecida detectada: {$bestMatch['pattern_name']} ({$bestMatch['matches']} ocorrências)"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPattern Known Signature Error: " . $e->getMessage());
        }
        
        return ['detected' => false];
    }
    
    /**
     * Carrega padrões conhecidos do banco
     */
    private function loadKnownPatterns() {
        if (!$this->db) {
            $this->knownPatterns = [];
            return;
        }
        
        try {
            $stmt = $this->db->query("
                SELECT * FROM safenode_attack_patterns 
                WHERE is_active = 1 
                ORDER BY severity DESC, confidence DESC
            ");
            $this->knownPatterns = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            $this->knownPatterns = [];
        }
    }
    
    /**
     * Constrói query SQL baseado em assinatura de padrão
     */
    private function buildSignatureQuery($signature, $siteFilter) {
        $conditions = [];
        
        if (isset($signature['threat_types']) && is_array($signature['threat_types'])) {
            $types = array_map(function($t) {
                return "'" . addslashes($t) . "'";
            }, $signature['threat_types']);
            $conditions[] = "threat_type IN (" . implode(',', $types) . ")";
        }
        
        if (isset($signature['min_threat_score'])) {
            $conditions[] = "threat_score >= " . (int)$signature['min_threat_score'];
        }
        
        if (isset($signature['uri_patterns']) && is_array($signature['uri_patterns'])) {
            $uriConditions = [];
            foreach ($signature['uri_patterns'] as $pattern) {
                $uriConditions[] = "request_uri LIKE '" . addslashes($pattern) . "'";
            }
            if (!empty($uriConditions)) {
                $conditions[] = "(" . implode(' OR ', $uriConditions) . ")";
            }
        }
        
        if (empty($conditions)) {
            return null;
        }
        
        $where = implode(' AND ', $conditions);
        
        return "
            SELECT ip_address, COUNT(*) as match_count
            FROM safenode_security_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            AND {$where}
            $siteFilter
            GROUP BY ip_address
            HAVING match_count >= " . (int)($signature['min_occurrences'] ?? 3) . "
        ";
    }
    
    /**
     * Correlaciona padrões detectados para encontrar relações
     */
    private function correlatePatterns($patterns) {
        if (empty($patterns) || count($patterns) < 2) {
            return null;
        }
        
        $correlations = [];
        
        // Verificar se múltiplos padrões envolvem mesmos IPs
        $ipsByPattern = [];
        foreach ($patterns as $type => $pattern) {
            if (isset($pattern['ip_address'])) {
                $ipsByPattern[$type] = [$pattern['ip_address']];
            } elseif (isset($pattern['unique_attackers']) && isset($pattern['target'])) {
                // Para padrões coordenados, precisamos buscar IPs
                $ipsByPattern[$type] = 'coordinated';
            }
        }
        
        // Se há sobreposição de IPs entre padrões diferentes
        $overlap = 0;
        $patternTypes = array_keys($ipsByPattern);
        for ($i = 0; $i < count($patternTypes) - 1; $i++) {
            for ($j = $i + 1; $j < count($patternTypes); $j++) {
                $type1 = $patternTypes[$i];
                $type2 = $patternTypes[$j];
                
                if ($ipsByPattern[$type1] !== 'coordinated' && $ipsByPattern[$type2] !== 'coordinated') {
                    if (!empty(array_intersect($ipsByPattern[$type1], $ipsByPattern[$type2]))) {
                        $overlap++;
                        $correlations[] = [
                            'pattern1' => $type1,
                            'pattern2' => $type2,
                            'correlation_type' => 'shared_ips'
                        ];
                    }
                }
            }
        }
        
        if (!empty($correlations)) {
            return [
                'detected' => true,
                'type' => 'pattern_correlation',
                'severity' => 'high',
                'severity_score' => min(100, 70 + ($overlap * 10)),
                'probability' => round(min(0.95, 0.6 + ($overlap * 0.1)), 3),
                'correlations' => $correlations,
                'total_patterns' => count($patterns),
                'overlapping_patterns' => $overlap,
                'description' => "Correlação detectada: {$overlap} padrões relacionados"
            ];
        }
        
        return null;
    }
    
    /**
     * Obtém estatísticas de padrões de ataque
     */
    public function getPatternStats($days = 7, $siteId = null) {
        if (!$this->db) return [];
        
        try {
            $siteFilter = $siteId ? "AND site_id = ?" : "";
            $params = $siteId ? [$days] : [$days];
            if ($siteId) {
                array_unshift($params, $siteId);
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(DISTINCT ip_address) as unique_attackers,
                    COUNT(*) as total_attacks,
                    COUNT(DISTINCT threat_type) as unique_threat_types
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                $siteFilter
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Registra novo padrão de ataque conhecido
     */
    public function registerPattern($patternName, $patternType, $signature, $severity = 50, $confidence = 50) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO safenode_attack_patterns 
                (pattern_name, pattern_type, pattern_signature, severity, confidence, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    pattern_signature = VALUES(pattern_signature),
                    severity = VALUES(severity),
                    confidence = VALUES(confidence),
                    updated_at = NOW()
            ");
            
            return $stmt->execute([
                $patternName,
                $patternType,
                json_encode($signature),
                $severity,
                $confidence
            ]);
        } catch (PDOException $e) {
            error_log("AttackPatternAnalyzer Register Pattern Error: " . $e->getMessage());
            return false;
        }
    }
}








