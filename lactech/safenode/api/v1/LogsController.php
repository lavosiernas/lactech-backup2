<?php
/**
 * SafeNode - API Logs Controller
 * Endpoints para consultar logs
 */

require_once __DIR__ . '/BaseController.php';

class LogsController extends BaseController {
    
    /**
     * GET /api/v1/logs
     * Lista logs com filtros
     */
    public function index() {
        $params = array_merge($_GET, $_POST);
        
        $page = max(1, (int)($params['page'] ?? 1));
        $limit = min(100, max(1, (int)($params['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM safenode_security_logs WHERE 1=1";
        $where = [];
        $params_sql = [];
        
        // Filtros
        if (isset($params['site_id']) && $params['site_id']) {
            $where[] = "site_id = ?";
            $params_sql[] = $params['site_id'];
        } elseif ($this->siteId) {
            $where[] = "site_id = ?";
            $params_sql[] = $this->siteId;
        } elseif ($this->userId) {
            $where[] = "site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params_sql[] = $this->userId;
        }
        
        if (isset($params['ip_address']) && $params['ip_address']) {
            $where[] = "ip_address = ?";
            $params_sql[] = $params['ip_address'];
        }
        
        if (isset($params['action_taken']) && $params['action_taken']) {
            $where[] = "action_taken = ?";
            $params_sql[] = $params['action_taken'];
        }
        
        if (isset($params['threat_type']) && $params['threat_type']) {
            $where[] = "threat_type = ?";
            $params_sql[] = $params['threat_type'];
        }
        
        if (isset($params['min_threat_score']) && $params['min_threat_score']) {
            $where[] = "threat_score >= ?";
            $params_sql[] = $params['min_threat_score'];
        }
        
        if (isset($params['start_date']) && $params['start_date']) {
            $where[] = "created_at >= ?";
            $params_sql[] = $params['start_date'];
        }
        
        if (isset($params['end_date']) && $params['end_date']) {
            $where[] = "created_at <= ?";
            $params_sql[] = $params['end_date'];
        }
        
        if (!empty($where)) {
            $sql .= " AND " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params_sql[] = $limit;
        $params_sql[] = $offset;
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params_sql);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total
            $countSql = "SELECT COUNT(*) as total FROM safenode_security_logs WHERE 1=1";
            if (!empty($where)) {
                $countSql .= " AND " . implode(" AND ", $where);
            }
            $countParams = array_slice($params_sql, 0, -2); // Remover limit e offset
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($countParams);
            $total = (int)$countStmt->fetch()['total'];
            
            $this->sendResponse([
                'success' => true,
                'data' => $logs,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (PDOException $e) {
            $this->sendError('Erro ao buscar logs: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/v1/logs/:id
     * Obtém log específico
     */
    public function show($id) {
        try {
            $sql = "SELECT * FROM safenode_security_logs WHERE id = ?";
            $params = [$id];
            
            if ($this->siteId) {
                $sql .= " AND site_id = ?";
                $params[] = $this->siteId;
            } elseif ($this->userId) {
                $sql .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
                $params[] = $this->userId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $log = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$log) {
                $this->sendError('Log não encontrado', 404);
            }
            
            $this->sendResponse([
                'success' => true,
                'data' => $log
            ]);
        } catch (PDOException $e) {
            $this->sendError('Erro ao buscar log', 500);
        }
    }
}


