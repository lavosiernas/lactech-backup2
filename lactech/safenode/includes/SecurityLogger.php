<?php
/**
 * SafeNode - Security Logger
 * Sistema de registro de eventos de segurança
 */

class SecurityLogger {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Registra um evento de segurança
     */
    public function log($ipAddress, $requestUri, $requestMethod, $actionTaken, $threatType = null, $threatScore = 0, $userAgent = null, $referer = null, $siteId = null, $responseTime = null) {
        if (!$this->db) return false;
        
        try {
            // Verificar se a coluna response_time existe
            $columns = "ip_address, request_uri, request_method, action_taken, threat_type, threat_score, user_agent, referer, site_id, created_at";
            $values = "?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()";
            $params = [$ipAddress, $requestUri, $requestMethod, $actionTaken, $threatType, $threatScore, $userAgent, $referer, $siteId];
            
            if ($responseTime !== null) {
                try {
                    // Tentar inserir com response_time
                    $stmt = $this->db->prepare("
                        INSERT INTO safenode_security_logs 
                        ($columns, response_time) 
                        VALUES ($values, ?)
                    ");
                    $params[] = $responseTime;
                    $stmt->execute($params);
                } catch (PDOException $e) {
                    // Se a coluna não existir, inserir sem ela
                    $stmt = $this->db->prepare("
                        INSERT INTO safenode_security_logs 
                        ($columns) 
                        VALUES ($values)
                    ");
                    $stmt->execute(array_slice($params, 0, -1));
                }
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO safenode_security_logs 
                    ($columns) 
                    VALUES ($values)
                ");
                $stmt->execute($params);
            }
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("SafeNode SecurityLogger Error: " . $e->getMessage());
            return false;
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
}

