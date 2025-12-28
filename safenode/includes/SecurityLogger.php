<?php
/**
 * SafeNode - Security Logger
 * Sistema de registro de eventos de segurança
 */

class SecurityLogger {
    private $db;
    private static $countryColumnReady = false;
    private $structuredLogger;
    
    public function __construct($database) {
        $this->db = $database;
        $this->ensureCountryColumn();
        
        // Inicializar structured logger se disponível
        if (class_exists('StructuredLogger')) {
            require_once __DIR__ . '/StructuredLogger.php';
            $this->structuredLogger = new StructuredLogger($database);
        }
    }
    
    /**
     * Registra um evento de segurança (com confidence_score opcional)
     */
    public function log($ipAddress, $requestUri, $requestMethod, $actionTaken, $threatType = null, $threatScore = 0, $userAgent = null, $referer = null, $siteId = null, $responseTime = null, $countryCode = null, $confidenceScore = null) {
        if (!$this->db) return false;
        
        try {
            // Colunas base
            $columns = "ip_address, request_uri, request_method, action_taken, threat_type, threat_score, user_agent, referer, site_id, country_code, created_at";
            $values = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()";
            $params = [$ipAddress, $requestUri, $requestMethod, $actionTaken, $threatType, $threatScore, $userAgent, $referer, $siteId, $countryCode];
            
            // Tentar adicionar confidence_score se fornecido
            $hasConfidenceScore = false;
            if ($confidenceScore !== null) {
                try {
                    // Verificar se a coluna existe
                    $this->db->query("SELECT confidence_score FROM safenode_security_logs LIMIT 1");
                    $hasConfidenceScore = true;
                    $columns .= ", confidence_score";
                    $values .= ", ?";
                    $params[] = $confidenceScore;
                } catch (PDOException $e) {
                    // Coluna não existe, continuar sem ela
                    $hasConfidenceScore = false;
                }
            }
            
            // Tentar adicionar response_time se fornecido
            if ($responseTime !== null) {
                try {
                    $columns .= ", response_time";
                    $values .= ", ?";
                    $params[] = $responseTime;
                } catch (PDOException $e) {
                    // Se a coluna não existir, ignorar
                }
            }
            
            // Inserir log
            $stmt = $this->db->prepare("
                INSERT INTO safenode_security_logs 
                ($columns) 
                VALUES ($values)
            ");
            $stmt->execute($params);
            
            $logId = $this->db->lastInsertId();

            // Atualizar/incorporar em incidente, se for ameaça relevante
            if ($actionTaken === 'blocked' && !empty($threatType)) {
                $this->updateIncident($ipAddress, $threatType, $threatScore, $siteId);
            }
            
            // Registrar log estruturado também
            if ($this->structuredLogger) {
                $level = $threatScore >= 90 ? 'critical' : ($threatScore >= 70 ? 'error' : ($threatScore >= 50 ? 'warning' : 'info'));
                $this->structuredLogger->log($level, "Security event: $actionTaken", [
                    'ip_address' => $ipAddress,
                    'threat_type' => $threatType,
                    'threat_score' => $threatScore,
                    'action_taken' => $actionTaken,
                    'request_uri' => $requestUri,
                    'response_time' => $responseTime,
                    'site_id' => $siteId
                ]);
            }

            return $logId;
        } catch (PDOException $e) {
            error_log("SafeNode SecurityLogger Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function ensureCountryColumn() {
        if (self::$countryColumnReady || !$this->db) {
            return;
        }
        try {
            $this->db->query("SELECT country_code FROM safenode_security_logs LIMIT 1");
            self::$countryColumnReady = true;
        } catch (PDOException $e) {
            try {
                $this->db->exec("ALTER TABLE safenode_security_logs ADD COLUMN country_code CHAR(2) DEFAULT NULL AFTER site_id");
            } catch (PDOException $alterErr) {
                error_log("SafeNode Geo Column Error: " . $alterErr->getMessage());
            }
            self::$countryColumnReady = true;
        }
    }
    
    /**
     * Calcula latência média
     */
    public function calculateLatency($siteId = null, $timeWindow = 3600) {
        if (!$this->db) return null;
        
        try {
            // Calcular média (compatível com MySQL 5.7+)
            $sql = "
                SELECT 
                    AVG(response_time) as avg_latency,
                    MAX(response_time) as max_latency
                FROM safenode_security_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND response_time IS NOT NULL
            ";
            
            $params = [$timeWindow];
            
            if ($siteId) {
                $sql .= " AND site_id = ?";
                $params[] = $siteId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            $avgLatency = round($result['avg_latency'] ?? 0, 2);
            $maxLatency = round($result['max_latency'] ?? 0, 2);
            
            // Calcular P99 aproximado usando método compatível com MySQL 5.7
            // P99 ≈ 99% dos valores estão abaixo deste valor
            // Usamos uma aproximação: pegar o valor que está no percentil 99
            $sqlCount = "
                SELECT COUNT(*) as total
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND response_time IS NOT NULL
            ";
            
            $paramsCount = [$timeWindow];
            if ($siteId) {
                $sqlCount .= " AND site_id = ?";
                $paramsCount[] = $siteId;
            }
            
            $stmtCount = $this->db->prepare($sqlCount);
            $stmtCount->execute($paramsCount);
            $countResult = $stmtCount->fetch();
            $total = (int)($countResult['total'] ?? 0);
            
            if ($total > 0) {
                // Calcular offset para P99 (1% dos valores mais altos)
                $offset = max(0, floor($total * 0.01));
                
                $sqlP99 = "
                    SELECT response_time as p99_latency
                    FROM safenode_security_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                    AND response_time IS NOT NULL
                ";
                
                $paramsP99 = [$timeWindow];
                if ($siteId) {
                    $sqlP99 .= " AND site_id = ?";
                    $paramsP99[] = $siteId;
                }
                
                $sqlP99 .= "
                    ORDER BY response_time DESC
                    LIMIT 1 OFFSET ?
                ";
                $paramsP99[] = $offset;
                
                try {
                    $stmtP99 = $this->db->prepare($sqlP99);
                    $stmtP99->execute($paramsP99);
                    $resultP99 = $stmtP99->fetch();
                    $p99Latency = round($resultP99['p99_latency'] ?? $maxLatency, 2);
                } catch (PDOException $e) {
                    // Se falhar, usar máximo como aproximação
                    $p99Latency = $maxLatency;
                }
            } else {
                $p99Latency = 0;
            }
            
            return [
                'avg' => $avgLatency,
                'p99' => $p99Latency
            ];
        } catch (PDOException $e) {
            error_log("SafeNode Latency Calculation Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Atualiza ou cria um incidente agregado a partir de um log bloqueado.
     */
    private function updateIncident(string $ipAddress, ?string $threatType, int $threatScore, ?int $siteId = null): void
    {
        if (!$this->db) return;

        $threatType = $threatType ?: 'unknown';
        $nowWindowMinutes = 10; // janela para agrupar eventos em um mesmo incidente

        try {
            // Procurar incidente aberto recente para esse IP + tipo
            $stmt = $this->db->prepare("
                SELECT id, total_events, critical_events, highest_score
                FROM safenode_incidents
                WHERE ip_address = ?
                  AND threat_type = ?
                  AND status = 'open'
                  AND last_seen >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
                ORDER BY last_seen DESC
                LIMIT 1
            ");
            $stmt->execute([$ipAddress, $threatType, $nowWindowMinutes]);
            $incident = $stmt->fetch(PDO::FETCH_ASSOC);

            $isCritical = $threatScore >= 80;

            if ($incident) {
                $update = $this->db->prepare("
                    UPDATE safenode_incidents
                    SET 
                        total_events = total_events + 1,
                        critical_events = critical_events + ?,
                        highest_score = GREATEST(highest_score, ?),
                        last_seen = NOW()
                    WHERE id = ?
                ");
                $update->execute([$isCritical ? 1 : 0, $threatScore, $incident['id']]);
            } else {
                $insert = $this->db->prepare("
                    INSERT INTO safenode_incidents 
                        (ip_address, threat_type, site_id, status, first_seen, last_seen, total_events, critical_events, highest_score)
                    VALUES 
                        (?, ?, ?, 'open', NOW(), NOW(), 1, ?, ?)
                ");
                $insert->execute([
                    $ipAddress,
                    $threatType,
                    $siteId,
                    $isCritical ? 1 : 0,
                    $threatScore
                ]);
            }
        } catch (PDOException $e) {
            error_log("SafeNode Incident Update Error: " . $e->getMessage());
        }
    }
}

