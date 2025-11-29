<?php
/**
 * SafeNode - Security Analytics
 * Análise avançada de padrões de segurança usando DADOS PRÓPRIOS
 * Sistema independente - não depende da Cloudflare
 */

class SecurityAnalytics {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Analisa padrões de ataque por horário
     * Retorna quais horários têm mais ataques
     */
    public function getAttackPatternsByTime($siteId = null, $userId = null, $days = 30) {
        if (!$this->db) return [];
        
        $params = [$days];
        $siteFilter = "";
        
        if ($siteId) {
            $siteFilter = " AND site_id = ?";
            $params = [$siteId, $days];
        } elseif ($userId) {
            $siteFilter = " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params = [$userId, $days];
        }
        
        $sql = "
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as attack_count,
                COUNT(DISTINCT ip_address) as unique_attackers,
                COALESCE(AVG(threat_score), 0) as avg_threat_score,
                COALESCE(MAX(threat_score), 0) as max_threat_score,
                SUM(CASE WHEN threat_type = 'sql_injection' THEN 1 ELSE 0 END) as sql_attacks,
                SUM(CASE WHEN threat_type = 'xss' THEN 1 ELSE 0 END) as xss_attacks,
                SUM(CASE WHEN threat_type = 'brute_force' THEN 1 ELSE 0 END) as brute_force_attacks,
                SUM(CASE WHEN threat_type = 'ddos' THEN 1 ELSE 0 END) as ddos_attacks
            FROM safenode_security_logs
            WHERE action_taken = 'blocked'
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            $siteFilter
            GROUP BY HOUR(created_at)
            ORDER BY attack_count DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Analisa padrões de ataque por dia da semana
     */
    public function getAttackPatternsByDay($siteId = null, $userId = null, $days = 30) {
        if (!$this->db) return [];
        
        $params = [$days];
        $siteFilter = "";
        
        if ($siteId) {
            $siteFilter = " AND site_id = ?";
            $params = [$siteId, $days];
        } elseif ($userId) {
            $siteFilter = " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params = [$userId, $days];
        }
        
        $sql = "
            SELECT 
                DAYNAME(created_at) as day_name,
                DAYOFWEEK(created_at) as day_number,
                COUNT(*) as attack_count,
                COUNT(DISTINCT ip_address) as unique_attackers,
                COALESCE(AVG(threat_score), 0) as avg_threat_score,
                COALESCE(MAX(threat_score), 0) as max_threat_score
            FROM safenode_security_logs
            WHERE action_taken = 'blocked'
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            $siteFilter
            GROUP BY DAYOFWEEK(created_at), DAYNAME(created_at)
            ORDER BY day_number
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Identifica IPs com padrões suspeitos (múltiplos tipos de ataque)
     */
    public function getSuspiciousIPs($siteId = null, $userId = null, $days = 7, $limit = 20) {
        if (!$this->db) return [];
        
        $params = [$days, $limit];
        $siteFilter = "";
        
        if ($siteId) {
            $siteFilter = " AND site_id = ?";
            $params = [$siteId, $days, $limit];
        } elseif ($userId) {
            $siteFilter = " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params = [$userId, $days, $limit];
        }
        
        $sql = "
            SELECT 
                ip_address,
                COUNT(*) as total_attacks,
                COUNT(DISTINCT threat_type) as attack_types_count,
                COUNT(DISTINCT DATE(created_at)) as active_days,
                COALESCE(MAX(threat_score), 0) as max_threat_score,
                COALESCE(AVG(threat_score), 0) as avg_threat_score,
                SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT threat_type ORDER BY threat_type ASC SEPARATOR ','), ',', 10) as threat_types,
                MIN(created_at) as first_seen,
                MAX(created_at) as last_seen,
                country_code
            FROM safenode_security_logs
            WHERE action_taken = 'blocked'
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            $siteFilter
            GROUP BY ip_address, country_code
            HAVING total_attacks >= 3 OR attack_types_count >= 2
            ORDER BY total_attacks DESC, attack_types_count DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular score de suspeição
        foreach ($ips as &$ip) {
            $ip['suspicion_score'] = $this->calculateSuspicionScore($ip);
        }
        
        return $ips ?: [];
    }
    
    /**
     * Calcula score de suspeição de um IP
     */
    private function calculateSuspicionScore($ipData) {
        $score = 0;
        
        // Mais ataques = mais suspeito
        $score += min(40, $ipData['total_attacks'] * 2);
        
        // Múltiplos tipos de ataque = mais suspeito
        $score += min(30, $ipData['attack_types_count'] * 10);
        
        // Ativo em múltiplos dias = mais suspeito
        $score += min(20, $ipData['active_days'] * 3);
        
        // Alta ameaça = mais suspeito
        $score += min(10, ($ipData['avg_threat_score'] / 10));
        
        return min(100, $score);
    }
    
    /**
     * Analisa países de origem dos ataques
     */
    public function getAttackCountries($siteId = null, $userId = null, $days = 30, $limit = 10) {
        if (!$this->db) return [];
        
        $params = [$days, $limit];
        $siteFilter = "";
        
        if ($siteId) {
            $siteFilter = " AND site_id = ?";
            $params = [$siteId, $days, $limit];
        } elseif ($userId) {
            $siteFilter = " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params = [$userId, $days, $limit];
        }
        
        $sql = "
            SELECT 
                COALESCE(country_code, '??') as country_code,
                COUNT(*) as attack_count,
                COUNT(DISTINCT ip_address) as unique_ips,
                COALESCE(AVG(threat_score), 0) as avg_threat_score,
                COALESCE(MAX(threat_score), 0) as max_threat_score,
                SUM(CASE WHEN threat_type = 'sql_injection' THEN 1 ELSE 0 END) as sql_attacks,
                SUM(CASE WHEN threat_type = 'xss' THEN 1 ELSE 0 END) as xss_attacks,
                SUM(CASE WHEN threat_type = 'brute_force' THEN 1 ELSE 0 END) as brute_force_attacks
            FROM safenode_security_logs
            WHERE action_taken = 'blocked'
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            $siteFilter
            GROUP BY country_code
            ORDER BY attack_count DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Identifica tendências de ameaças (aumento/diminuição)
     */
    public function getThreatTrends($siteId = null, $userId = null, $days = 30) {
        if (!$this->db) return [];
        
        $params = [];
        $siteFilter = "";
        
        if ($siteId) {
            $siteFilter = " AND site_id = ?";
            $params[] = $siteId;
        } elseif ($userId) {
            $siteFilter = " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params[] = $userId;
        }
        
        // Dividir em períodos para comparar
        $halfDays = ceil($days / 2);
        $params = array_merge($params, [$days, $halfDays, $halfDays, $days]);
        
        $sql = "
            SELECT 
                threat_type,
                COUNT(*) as total_attacks,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) 
                    AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY) THEN 1 ELSE 0 END) as period1_attacks,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) THEN 1 ELSE 0 END) as period2_attacks
            FROM safenode_security_logs
            WHERE action_taken = 'blocked'
            AND threat_type IS NOT NULL
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            $siteFilter
            GROUP BY threat_type
            HAVING total_attacks > 0
            ORDER BY total_attacks DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular tendência (aumento/diminuição)
        foreach ($trends as &$trend) {
            $period1 = (int)$trend['period1_attacks'];
            $period2 = (int)$trend['period2_attacks'];
            
            if ($period1 > 0) {
                $change = (($period2 - $period1) / $period1) * 100;
                $trend['trend_percentage'] = round($change, 1);
                $trend['trend'] = $change > 10 ? 'increasing' : ($change < -10 ? 'decreasing' : 'stable');
            } else {
                $trend['trend_percentage'] = $period2 > 0 ? 100 : 0;
                $trend['trend'] = $period2 > 0 ? 'increasing' : 'stable';
            }
        }
        
        return $trends ?: [];
    }
    
    /**
     * Identifica alvos mais atacados (URIs)
     */
    public function getMostAttackedTargets($siteId = null, $userId = null, $days = 30, $limit = 10) {
        if (!$this->db) return [];
        
        $params = [$days, $limit];
        $siteFilter = "";
        
        if ($siteId) {
            $siteFilter = " AND site_id = ?";
            $params = [$siteId, $days, $limit];
        } elseif ($userId) {
            $siteFilter = " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params = [$userId, $days, $limit];
        }
        
        $sql = "
            SELECT 
                request_uri,
                COUNT(*) as attack_count,
                COUNT(DISTINCT ip_address) as unique_attackers,
                COUNT(DISTINCT threat_type) as threat_types_count,
                COALESCE(AVG(threat_score), 0) as avg_threat_score,
                COALESCE(MAX(threat_score), 0) as max_threat_score,
                MAX(created_at) as last_attack
            FROM safenode_security_logs
            WHERE action_taken = 'blocked'
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            $siteFilter
            GROUP BY request_uri
            ORDER BY attack_count DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Correlaciona ataques (IPs que atacam múltiplos alvos)
     */
    public function getCorrelatedAttacks($siteId = null, $userId = null, $days = 7, $limit = 10) {
        if (!$this->db) return [];
        
        $params = [$days, $limit];
        $siteFilter = "";
        
        if ($siteId) {
            $siteFilter = " AND site_id = ?";
            $params = [$siteId, $days, $limit];
        } elseif ($userId) {
            $siteFilter = " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params = [$userId, $days, $limit];
        }
        
        $sql = "
            SELECT 
                ip_address,
                COUNT(DISTINCT request_uri) as targets_count,
                COUNT(*) as total_attacks,
                SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT request_uri ORDER BY request_uri ASC SEPARATOR ', '), ', ', 5) as targets,
                country_code,
                MAX(created_at) as last_attack
            FROM safenode_security_logs
            WHERE action_taken = 'blocked'
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            $siteFilter
            GROUP BY ip_address, country_code
            HAVING targets_count >= 3
            ORDER BY targets_count DESC, total_attacks DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Gera insights automáticos baseados nos dados
     */
    public function generateInsights($siteId = null, $userId = null, $days = 30) {
        if (!$this->db) return [];
        
        $insights = [];
        
        // Insight 1: Horário de pico de ataques
        $timePatterns = $this->getAttackPatternsByTime($siteId, $userId, $days);
        if (!empty($timePatterns)) {
            $peakHour = $timePatterns[0];
            $insights[] = [
                'type' => 'peak_time',
                'severity' => 'info',
                'title' => 'Horário de Pico de Ataques',
                'message' => "A maioria dos ataques ocorre entre {$peakHour['hour']}:00 e " . ($peakHour['hour'] + 1) . ":00 ({$peakHour['attack_count']} ataques)",
                'data' => $peakHour
            ];
        }
        
        // Insight 2: IPs mais suspeitos
        $suspiciousIPs = $this->getSuspiciousIPs($siteId, $userId, min($days, 7), 5);
        if (!empty($suspiciousIPs)) {
            $topSuspicious = $suspiciousIPs[0];
            $insights[] = [
                'type' => 'suspicious_ip',
                'severity' => ($topSuspicious['suspicion_score'] ?? 0) > 70 ? 'high' : 'medium',
                'title' => 'IP Altamente Suspeito Detectado',
                'message' => "IP {$topSuspicious['ip_address']} realizou {$topSuspicious['total_attacks']} ataques de {$topSuspicious['attack_types_count']} tipos diferentes",
                'data' => $topSuspicious
            ];
        }
        
        // Insight 3: Tendências
        $trends = $this->getThreatTrends($siteId, $userId, $days);
        foreach ($trends as $trend) {
            if (($trend['trend'] ?? '') === 'increasing' && ($trend['trend_percentage'] ?? 0) > 50) {
                $insights[] = [
                    'type' => 'trend',
                    'severity' => 'warning',
                    'title' => 'Aumento Significativo de Ataques',
                    'message' => "Ataques do tipo {$trend['threat_type']} aumentaram {$trend['trend_percentage']}% no período",
                    'data' => $trend
                ];
            }
        }
        
        // Insight 4: Alvos mais atacados
        $targets = $this->getMostAttackedTargets($siteId, $userId, $days, 1);
        if (!empty($targets) && ($targets[0]['attack_count'] ?? 0) > 10) {
            $insights[] = [
                'type' => 'target',
                'severity' => 'medium',
                'title' => 'Alvo Frequente de Ataques',
                'message' => "O caminho " . substr($targets[0]['request_uri'], 0, 50) . " foi atacado {$targets[0]['attack_count']} vezes",
                'data' => $targets[0]
            ];
        }
        
        return $insights;
    }
    
    /**
     * Estatísticas resumidas para dashboard
     */
    public function getSummaryStats($siteId = null, $userId = null, $days = 30) {
        if (!$this->db) return [];
        
        $params = [$days];
        $siteFilter = "";
        
        if ($siteId) {
            $siteFilter = " AND site_id = ?";
            $params = [$siteId, $days];
        } elseif ($userId) {
            $siteFilter = " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params = [$userId, $days];
        }
        
        $sql = "
            SELECT 
                COUNT(*) as total_attacks,
                COUNT(DISTINCT ip_address) as unique_attackers,
                COUNT(DISTINCT threat_type) as threat_types,
                COALESCE(AVG(threat_score), 0) as avg_threat_score,
                COALESCE(MAX(threat_score), 0) as max_threat_score,
                COUNT(DISTINCT DATE(created_at)) as active_days,
                COUNT(DISTINCT country_code) as countries
            FROM safenode_security_logs
            WHERE action_taken = 'blocked'
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            $siteFilter
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: [];
    }
}



