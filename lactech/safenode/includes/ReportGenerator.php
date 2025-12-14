<?php
/**
 * SafeNode - Report Generator
 * Sistema de relatórios automatizados
 * 
 * Tipos:
 * - Diário
 * - Semanal
 * - Mensal
 * Formatos: HTML, PDF, Email
 */

class ReportGenerator {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Gera relatório diário
     */
    public function generateDailyReport($siteId = null, $userId = null) {
        $date = date('Y-m-d', strtotime('-1 day')); // Ontem
        
        $data = [
            'period' => 'daily',
            'date' => $date,
            'summary' => $this->getSummary($date, $date, $siteId, $userId),
            'threats' => $this->getThreats($date, $date, $siteId, $userId),
            'top_ips' => $this->getTopIPs($date, $date, $siteId, $userId),
            'recommendations' => $this->getRecommendations($date, $date, $siteId, $userId)
        ];
        
        return $data;
    }
    
    /**
     * Gera relatório semanal
     */
    public function generateWeeklyReport($siteId = null, $userId = null) {
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = date('Y-m-d');
        
        $data = [
            'period' => 'weekly',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'summary' => $this->getSummary($startDate, $endDate, $siteId, $userId),
            'threats' => $this->getThreats($startDate, $endDate, $siteId, $userId),
            'trends' => $this->getTrends($startDate, $endDate, $siteId, $userId),
            'top_ips' => $this->getTopIPs($startDate, $endDate, $siteId, $userId),
            'recommendations' => $this->getRecommendations($startDate, $endDate, $siteId, $userId)
        ];
        
        return $data;
    }
    
    /**
     * Gera relatório mensal
     */
    public function generateMonthlyReport($siteId = null, $userId = null) {
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');
        
        $data = [
            'period' => 'monthly',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'summary' => $this->getSummary($startDate, $endDate, $siteId, $userId),
            'threats' => $this->getThreats($startDate, $endDate, $siteId, $userId),
            'trends' => $this->getTrends($startDate, $endDate, $siteId, $userId),
            'top_ips' => $this->getTopIPs($startDate, $endDate, $siteId, $userId),
            'performance' => $this->getPerformance($startDate, $endDate, $siteId, $userId),
            'recommendations' => $this->getRecommendations($startDate, $endDate, $siteId, $userId)
        ];
        
        return $data;
    }
    
    /**
     * Gera HTML do relatório
     */
    public function generateHTML($reportData) {
        $html = "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <title>Relatório SafeNode - {$reportData['period']}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
                .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                h1 { color: #333; border-bottom: 3px solid #dc2626; padding-bottom: 10px; }
                h2 { color: #555; margin-top: 30px; }
                .summary { background: #f9fafb; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .stat { display: inline-block; margin: 10px 20px 10px 0; }
                .stat-value { font-size: 24px; font-weight: bold; color: #dc2626; }
                .stat-label { font-size: 12px; color: #666; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background: #f3f4f6; font-weight: bold; }
                .recommendation { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1>Relatório SafeNode - " . ucfirst($reportData['period']) . "</h1>
                <p><strong>Período:</strong> {$reportData['start_date']} a {$reportData['end_date']}</p>
                
                <div class='summary'>
                    <h2>Resumo</h2>
                    <div class='stat'>
                        <div class='stat-value'>{$reportData['summary']['total_requests']}</div>
                        <div class='stat-label'>Total de Requisições</div>
                    </div>
                    <div class='stat'>
                        <div class='stat-value'>{$reportData['summary']['blocked']}</div>
                        <div class='stat-label'>Bloqueios</div>
                    </div>
                    <div class='stat'>
                        <div class='stat-value'>{$reportData['summary']['unique_ips']}</div>
                        <div class='stat-label'>IPs Únicos</div>
                    </div>
                </div>
        ";
        
        // Top ameaças
        if (isset($reportData['threats'])) {
            $html .= "<h2>Top Ameaças</h2><table><tr><th>Tipo</th><th>Ocorrências</th><th>Score Médio</th></tr>";
            foreach ($reportData['threats'] as $threat) {
                $html .= "<tr><td>{$threat['type']}</td><td>{$threat['count']}</td><td>{$threat['avg_score']}</td></tr>";
            }
            $html .= "</table>";
        }
        
        // Recomendações
        if (isset($reportData['recommendations'])) {
            $html .= "<h2>Recomendações</h2>";
            foreach ($reportData['recommendations'] as $rec) {
                $html .= "<div class='recommendation'><strong>{$rec['title']}</strong><br>{$rec['description']}</div>";
            }
        }
        
        $html .= "
            </div>
        </body>
        </html>
        ";
        
        return $html;
    }
    
    /**
     * Envia relatório por email
     */
    public function sendReportByEmail($reportData, $email) {
        $html = $this->generateHTML($reportData);
        $subject = "Relatório SafeNode - " . ucfirst($reportData['period']);
        
        $headers = [
            'From: SafeNode <noreply@safenode.cloud>',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        return mail($email, $subject, $html, implode("\r\n", $headers));
    }
    
    /**
     * Obtém resumo
     */
    private function getSummary($startDate, $endDate, $siteId, $userId) {
        if (!$this->db) return [];
        
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked,
                    COUNT(DISTINCT ip_address) as unique_ips
                FROM safenode_security_logs
                WHERE DATE(created_at) BETWEEN ? AND ?
            ";
            
            $params = [$startDate, $endDate];
            if ($siteId) {
                $sql .= " AND site_id = ?";
                $params[] = $siteId;
            } elseif ($userId) {
                $sql .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
                $params[] = $userId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obtém ameaças
     */
    private function getThreats($startDate, $endDate, $siteId, $userId) {
        if (!$this->db) return [];
        
        try {
            $sql = "
                SELECT 
                    threat_type as type,
                    COUNT(*) as count,
                    AVG(threat_score) as avg_score
                FROM safenode_security_logs
                WHERE threat_type IS NOT NULL
                AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY threat_type
                ORDER BY count DESC
                LIMIT 10
            ";
            
            $params = [$startDate, $endDate];
            if ($siteId) {
                $sql .= " AND site_id = ?";
                $params[] = $siteId;
            } elseif ($userId) {
                $sql .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
                $params[] = $userId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obtém top IPs
     */
    private function getTopIPs($startDate, $endDate, $siteId, $userId) {
        if (!$this->db) return [];
        
        try {
            $sql = "
                SELECT 
                    ip_address,
                    COUNT(*) as attacks,
                    MAX(threat_score) as max_score
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY ip_address
                ORDER BY attacks DESC
                LIMIT 10
            ";
            
            $params = [$startDate, $endDate];
            if ($siteId) {
                $sql .= " AND site_id = ?";
                $params[] = $siteId;
            } elseif ($userId) {
                $sql .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
                $params[] = $userId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Obtém tendências
     */
    private function getTrends($startDate, $endDate, $siteId, $userId) {
        // Implementar análise de tendências
        return [];
    }
    
    /**
     * Obtém performance
     */
    private function getPerformance($startDate, $endDate, $siteId, $userId) {
        // Implementar métricas de performance
        return [];
    }
    
    /**
     * Gera recomendações
     */
    private function getRecommendations($startDate, $endDate, $siteId, $userId) {
        $recommendations = [];
        
        $summary = $this->getSummary($startDate, $endDate, $siteId, $userId);
        $blockRate = $summary['total_requests'] > 0 
            ? ($summary['blocked'] / $summary['total_requests']) * 100 
            : 0;
        
        if ($blockRate > 20) {
            $recommendations[] = [
                'title' => 'Taxa de bloqueio alta',
                'description' => "Taxa de bloqueio de {$blockRate}% é alta. Considere revisar regras de segurança."
            ];
        }
        
        return $recommendations;
    }
}








