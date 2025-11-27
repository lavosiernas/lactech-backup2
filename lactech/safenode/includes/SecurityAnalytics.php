<?php
/**
 * SafeNode - Security Analytics
 * Análise avançada de padrões de segurança que a Cloudflare não mostra
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
    public function getAttackPatternsByTime($siteId = null, $days = 30) {
        if (!$this->db) return null;
        
        $siteFilter = $siteId ? " AND site_id = $siteId " : "";
        
        $sql = "
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as attack_count,
                COUNT(DISTINCT ip_address) as unique_attackers,
                AVG(threat_score) as avg_threat_score,
                SUM(CASE WHEN threat_type = 'sql_injection' THEN 1 ELSE 0 END) as sql_attacks,
                SUM(CASE WHEN threat_type = 'xss' THEN 1 ELSE 0 END) as xss_attacks
            FROM safenode_security_logs
            WHERE action_taken = 'blocked'
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            $siteFilter
            GROUP BY HOUR(created_at)
            ORDER BY attack_count DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Analisa padrões de ataque por dia da semana
     */
    public function getAttackPatternsByDay($siteId = null, $days = 30) {
        if (!$this->db) return null;
        
        $siteFilter = $siteId ? " AND site_id = $siteId " : "";
        
        $sql = "
            SELECT 
                DAYNAME(created_at) as day_name,
                DAYOFWEEK(created_at) as day_number,
                COUNT(*) as attack_count,
                COUNT(DISTINCT ip_address) as unique_attackers,
                AVG(threat_score) as avg_threat_score
            FROM safenode_security_logs
            WHERE action_taken = 'blocked'
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            $siteFilter
            GROUP BY DAYOFWEEK(created_at), DAYNAME(created_at)
            ORDER BY day_number
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Identifica IPs com padrões suspeitos (múltiplos tipos de ataque)
     */
    public function getSuspiciousIPs($siteId = null, $days = 7, $limit = 20) {
        if (!$this->db) return null;
        
        $siteFilter = $siteId ? " AND site_id = $siteId " : "";
        
        $sql = "
            SELECT 
                ip_address,
                COUNT(*) as total_attacks,
                COUNT(DISTINCT threat_type) as attack_types_count,
                COUNT(DISTINCT DATE(created_at)) as active_days,
                MAX(threat_score) as max_threat_score,
                AVG(threat_score) as avg_threat_score,
                GROUP_CONCAT(DISTINCT threat_type) as threat_types,
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
        $stmt->execute([$days, $limit]);
        $ips = $stmt->fetchAll();
        
        // Calcular score de suspeição
        foreach ($ips as &$ip) {
            $ip['suspicion_score'] = $this->calculateSuspicionScore($ip);
        }
        
        return $ips;
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
    public function getAttackCountries($siteId = null, $days = 30, $limit = 10) {
        if (!$this->db) return null;
        
        $siteFilter = $siteId ? " AND site_id = $siteId " : "";
        
        $sql = "
            SELECT 
                country_code,
                COUNT(*) as attack_count,
                COUNT(DISTINCT ip_address) as unique_ips,
                AVG(threat_score) as avg_threat_score,
                SUM(CASE WHEN threat_type = 'sql_injection' THEN 1 ELSE 0 END) as sql_attacks,
                SUM(CASE WHEN threat_type = 'xss' THEN 1 ELSE 0 END) as xss_attacks
            FROM safenode_security_logs
            WHERE action_taken = 'blocked'
            AND country_code IS NOT NULL
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            $siteFilter
            GROUP BY country_code
            ORDER BY attack_count DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Identifica tendências de ameaças (aumento/diminuição)
     */
    public function getThreatTrends($siteId = null, $days = 30) {
        if (!$this->db) return null;
        
        $siteFilter = $siteId ? " AND site_id = $siteId " : "";
        
        // Dividir em períodos para comparar
        $halfDays = ceil($days / 2);
        
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
        $stmt->execute([$days, $halfDays, $halfDays, $days]);
        $trends = $stmt->fetchAll();
        
        // Calcular tendência (aumento/diminuição)
        foreach ($trends as &$trend) {
            $period1 = $trend['period1_attacks'];
            $period2 = $trend['period2_attacks'];
            
            if ($period1 > 0) {
                $change = (($period2 - $period1) / $period1) * 100;
                $trend['trend_percentage'] = round($change, 1);
                $trend['trend'] = $change > 10 ? 'increasing' : ($change < -10 ? 'decreasing' : 'stable');
            } else {
                $trend['trend_percentage'] = $period2 > 0 ? 100 : 0;
                $trend['trend'] = $period2 > 0 ? 'increasing' : 'stable';
            }
        }
        
        return $trends;
    }
    
    /**
     * Identifica alvos mais atacados (URIs)
     */
    public function getMostAttackedTargets($siteId = null, $days = 30, $limit = 10) {
        if (!$this->db) return null;
        
        $siteFilter = $siteId ? " AND site_id = $siteId " : "";
        
        $sql = "
            SELECT 
                request_uri,
                COUNT(*) as attack_count,
                COUNT(DISTINCT ip_address) as unique_attackers,
                COUNT(DISTINCT threat_type) as threat_types_count,
                AVG(threat_score) as avg_threat_score,
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
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Correlaciona ataques (IPs que atacam múltiplos alvos)
     */
    public function getCorrelatedAttacks($siteId = null, $days = 7, $limit = 10) {
        if (!$this->db) return null;
        
        $siteFilter = $siteId ? " AND site_id = $siteId " : "";
        
        $sql = "
            SELECT 
                ip_address,
                COUNT(DISTINCT request_uri) as targets_count,
                COUNT(*) as total_attacks,
                GROUP_CONCAT(DISTINCT request_uri SEPARATOR ', ') as targets,
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
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Gera insights automáticos baseados nos dados
     */
    public function generateInsights($siteId = null, $days = 30) {
        if (!$this->db) return [];
        
        $insights = [];
        
        // Insight 1: Horário de pico de ataques
        $timePatterns = $this->getAttackPatternsByTime($siteId, $days);
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
        $suspiciousIPs = $this->getSuspiciousIPs($siteId, min($days, 7), 5);
        if (!empty($suspiciousIPs)) {
            $topSuspicious = $suspiciousIPs[0];
            $insights[] = [
                'type' => 'suspicious_ip',
                'severity' => $topSuspicious['suspicion_score'] > 70 ? 'high' : 'medium',
                'title' => 'IP Altamente Suspeito Detectado',
                'message' => "IP {$topSuspicious['ip_address']} realizou {$topSuspicious['total_attacks']} ataques de {$topSuspicious['attack_types_count']} tipos diferentes",
                'data' => $topSuspicious
            ];
        }
        
        // Insight 3: Tendências
        $trends = $this->getThreatTrends($siteId, $days);
        foreach ($trends as $trend) {
            if ($trend['trend'] === 'increasing' && $trend['trend_percentage'] > 50) {
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
        $targets = $this->getMostAttackedTargets($siteId, $days, 1);
        if (!empty($targets) && $targets[0]['attack_count'] > 10) {
            $insights[] = [
                'type' => 'target',
                'severity' => 'medium',
                'title' => 'Alvo Frequente de Ataques',
                'message' => "O caminho {$targets[0]['request_uri']} foi atacado {$targets[0]['attack_count']} vezes",
                'data' => $targets[0]
            ];
        }
        
        return $insights;
    }
    
    /**
     * Estatísticas resumidas para dashboard
     */
    public function getSummaryStats($siteId = null, $days = 30) {
        if (!$this->db) return null;
        
        $siteFilter = $siteId ? " AND site_id = $siteId " : "";
        
        $sql = "
            SELECT 
                COUNT(*) as total_attacks,
                COUNT(DISTINCT ip_address) as unique_attackers,
                COUNT(DISTINCT threat_type) as threat_types,
                AVG(threat_score) as avg_threat_score,
                MAX(threat_score) as max_threat_score,
                COUNT(DISTINCT DATE(created_at)) as active_days,
                COUNT(DISTINCT country_code) as countries
            FROM safenode_security_logs
            WHERE action_taken = 'blocked'
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            $siteFilter
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetch();
    }
}


