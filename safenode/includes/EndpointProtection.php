<?php
/**
 * SafeNode - Endpoint Protection
 * Sistema de proteção inteligente por endpoint e contexto
 */

class EndpointProtection {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Verifica se uma requisição deve ser bloqueada baseado em regras de endpoint
     */
    public function checkEndpoint($siteId, $requestUri, $requestMethod = 'GET') {
        if (!$this->db) return ['allowed' => true, 'rule' => null];
        
        try {
            // Buscar regras ativas para o site, ordenadas por prioridade
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_endpoint_rules
                WHERE site_id = ? AND is_active = 1
                ORDER BY priority DESC, id ASC
            ");
            $stmt->execute([$siteId]);
            $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rules as $rule) {
                if ($this->matchesPattern($requestUri, $rule['endpoint_pattern'], $rule['endpoint_type'])) {
                    return [
                        'allowed' => false, // Será determinado pelas verificações
                        'rule' => $rule,
                        'security_level' => $rule['security_level'],
                        'threat_detection' => $rule['threat_detection_enabled'],
                        'rate_limit' => $rule['rate_limit_enabled'],
                        'rate_limit_requests' => $rule['rate_limit_requests'],
                        'rate_limit_window' => $rule['rate_limit_window'],
                        'waf_enabled' => $rule['waf_enabled'],
                        'waf_strict' => $rule['waf_strict_mode'],
                        'require_auth' => $rule['require_authentication'],
                        'require_hv' => $rule['require_human_verification']
                    ];
                }
            }
            
            // Nenhuma regra específica encontrada, usar configuração padrão do site
            return ['allowed' => true, 'rule' => null];
        } catch (PDOException $e) {
            error_log("EndpointProtection Check Error: " . $e->getMessage());
            return ['allowed' => true, 'rule' => null];
        }
    }
    
    /**
     * Verifica se uma URL corresponde a um padrão
     */
    private function matchesPattern($url, $pattern, $type) {
        switch ($type) {
            case 'path':
                // Match exato ou prefixo
                return $url === $pattern || strpos($url, $pattern) === 0;
                
            case 'regex':
                // Match regex
                return preg_match($pattern, $url) === 1;
                
            case 'api':
                // Match para APIs (geralmente /api/*)
                return strpos($url, $pattern) === 0;
                
            case 'static':
                // Match para arquivos estáticos
                $extensions = ['.css', '.js', '.jpg', '.png', '.gif', '.ico', '.svg'];
                foreach ($extensions as $ext) {
                    if (strpos($url, $ext) !== false) {
                        return strpos($url, $pattern) === 0;
                    }
                }
                return false;
                
            default:
                return false;
        }
    }
    
    /**
     * Cria ou atualiza uma regra de endpoint
     */
    public function createRule($siteId, $endpointPattern, $endpointType, $config) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO safenode_endpoint_rules
                (site_id, endpoint_pattern, endpoint_type, security_level,
                 threat_detection_enabled, rate_limit_enabled, rate_limit_requests,
                 rate_limit_window, waf_enabled, waf_strict_mode, geo_blocking_enabled,
                 require_authentication, require_human_verification, priority, is_active,
                 custom_rules, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)
            ");
            $stmt->execute([
                $siteId,
                $endpointPattern,
                $endpointType,
                $config['security_level'] ?? 'medium',
                $config['threat_detection'] ?? 1,
                $config['rate_limit'] ?? 1,
                $config['rate_limit_requests'] ?? 60,
                $config['rate_limit_window'] ?? 60,
                $config['waf_enabled'] ?? 1,
                $config['waf_strict'] ?? 0,
                $config['geo_blocking'] ?? 0,
                $config['require_auth'] ?? 0,
                $config['require_hv'] ?? 0,
                $config['priority'] ?? 0,
                json_encode($config['custom_rules'] ?? []),
                $config['description'] ?? null
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("EndpointProtection CreateRule Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Detecta comportamento anômalo em um endpoint
     */
    public function detectAnomaly($siteId, $endpointPattern, $ipAddress, $anomalyType, $baselineValue, $currentValue) {
        if (!$this->db) return false;
        
        try {
            // Calcular desvio percentual
            $deviation = $baselineValue > 0 
                ? abs((($currentValue - $baselineValue) / $baselineValue) * 100)
                : 100;
            
            // Determinar severidade baseada no desvio
            $severity = 'low';
            if ($deviation >= 200) $severity = 'critical';
            elseif ($deviation >= 100) $severity = 'high';
            elseif ($deviation >= 50) $severity = 'medium';
            
            $stmt = $this->db->prepare("
                INSERT INTO safenode_endpoint_anomalies
                (site_id, endpoint_pattern, ip_address, anomaly_type, severity,
                 baseline_value, current_value, deviation_percentage, detected_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $siteId,
                $endpointPattern,
                $ipAddress,
                $anomalyType,
                $severity,
                $baselineValue,
                $currentValue,
                $deviation
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("EndpointProtection DetectAnomaly Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém estatísticas de um endpoint
     */
    public function getEndpointStats($siteId, $endpointPattern, $days = 7) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(total_requests) as total_requests,
                    SUM(blocked_requests) as blocked_requests,
                    SUM(allowed_requests) as allowed_requests,
                    AVG(avg_response_time) as avg_response_time,
                    SUM(threat_count) as threat_count,
                    SUM(rate_limit_hits) as rate_limit_hits,
                    COUNT(DISTINCT stat_date) as days_active
                FROM safenode_endpoint_stats
                WHERE site_id = ? AND endpoint_pattern = ?
                  AND stat_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ");
            $stmt->execute([$siteId, $endpointPattern, $days]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EndpointProtection GetStats Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém anomalias não resolvidas
     */
    public function getUnresolvedAnomalies($siteId, $limit = 50) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_endpoint_anomalies
                WHERE site_id = ? AND is_resolved = 0
                ORDER BY 
                    CASE severity
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                    END,
                    detected_at DESC
                LIMIT ?
            ");
            $stmt->execute([$siteId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EndpointProtection GetAnomalies Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Registra estatísticas de endpoint (chamado periodicamente)
     */
    public function recordStats($siteId, $endpointPattern, $totalRequests, $blockedRequests, 
                                $allowedRequests, $uniqueIPs, $avgResponseTime, $threatCount, $rateLimitHits) {
        if (!$this->db) return false;
        
        try {
            $date = date('Y-m-d');
            $hour = (int)date('H');
            
            $stmt = $this->db->prepare("
                INSERT INTO safenode_endpoint_stats
                (site_id, endpoint_pattern, stat_date, stat_hour, total_requests,
                 blocked_requests, allowed_requests, unique_ips, avg_response_time,
                 threat_count, rate_limit_hits)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    total_requests = total_requests + VALUES(total_requests),
                    blocked_requests = blocked_requests + VALUES(blocked_requests),
                    allowed_requests = allowed_requests + VALUES(allowed_requests),
                    unique_ips = GREATEST(unique_ips, VALUES(unique_ips)),
                    avg_response_time = (avg_response_time + VALUES(avg_response_time)) / 2,
                    threat_count = threat_count + VALUES(threat_count),
                    rate_limit_hits = rate_limit_hits + VALUES(rate_limit_hits)
            ");
            $stmt->execute([
                $siteId,
                $endpointPattern,
                $date,
                $hour,
                $totalRequests,
                $blockedRequests,
                $allowedRequests,
                $uniqueIPs,
                $avgResponseTime,
                $threatCount,
                $rateLimitHits
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("EndpointProtection RecordStats Error: " . $e->getMessage());
            return false;
        }
    }
}

