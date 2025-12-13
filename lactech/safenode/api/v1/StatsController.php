<?php
/**
 * SafeNode - API Stats Controller
 * Endpoints para estatísticas
 */

require_once __DIR__ . '/BaseController.php';

class StatsController extends BaseController {
    
    /**
     * GET /api/v1/stats
     * Obtém estatísticas
     */
    public function index() {
        $params = $_GET;
        $timeWindow = (int)($params['window'] ?? 24); // horas
        
        try {
            require_once __DIR__ . '/../../api/dashboard-stats.php';
            // Reutilizar lógica do dashboard-stats.php
            // Por enquanto, retornar estrutura básica
            
            $sql = "
                SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked,
                    SUM(CASE WHEN action_taken = 'allowed' THEN 1 ELSE 0 END) as allowed,
                    AVG(threat_score) as avg_threat_score
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            ";
            
            $sqlParams = [$timeWindow];
            if ($this->siteId) {
                $sql .= " AND site_id = ?";
                $sqlParams[] = $this->siteId;
            } elseif ($this->userId) {
                $sql .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
                $sqlParams[] = $this->userId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($sqlParams);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->sendResponse([
                'success' => true,
                'data' => [
                    'time_window_hours' => $timeWindow,
                    'total_requests' => (int)($stats['total_requests'] ?? 0),
                    'blocked' => (int)($stats['blocked'] ?? 0),
                    'allowed' => (int)($stats['allowed'] ?? 0),
                    'avg_threat_score' => round((float)($stats['avg_threat_score'] ?? 0), 2)
                ]
            ]);
        } catch (PDOException $e) {
            $this->sendError('Erro ao buscar estatísticas', 500);
        }
    }
}






