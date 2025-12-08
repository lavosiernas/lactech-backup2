<?php
/**
 * SafeNode - API IPs Controller
 * Endpoints para gerenciar IPs bloqueados/whitelist
 */

require_once __DIR__ . '/BaseController.php';

class IPsController extends BaseController {
    
    /**
     * GET /api/v1/ips
     * Lista IPs bloqueados
     */
    public function index() {
        try {
            $sql = "SELECT * FROM safenode_blocked_ips WHERE is_active = 1";
            $params = [];
            
            if ($this->siteId) {
                // Filtrar por site se necessário
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT 100";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->sendResponse([
                'success' => true,
                'data' => $ips
            ]);
        } catch (PDOException $e) {
            $this->sendError('Erro ao buscar IPs', 500);
        }
    }
    
    /**
     * POST /api/v1/ips
     * Bloqueia IP
     */
    public function block() {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        $this->validateRequired($data, ['ip_address']);
        
        require_once __DIR__ . '/../../includes/IPBlocker.php';
        $ipBlocker = new IPBlocker($this->db);
        
        $result = $ipBlocker->blockIP(
            $data['ip_address'],
            $data['reason'] ?? 'Bloqueio manual via API',
            $data['threat_type'] ?? null,
            $data['duration'] ?? 3600
        );
        
        if ($result) {
            $this->sendResponse([
                'success' => true,
                'message' => 'IP bloqueado com sucesso'
            ], 201);
        } else {
            $this->sendError('Erro ao bloquear IP', 500);
        }
    }
    
    /**
     * DELETE /api/v1/ips/:ip
     * Desbloqueia IP
     */
    public function unblock($ipAddress) {
        require_once __DIR__ . '/../../includes/IPBlocker.php';
        $ipBlocker = new IPBlocker($this->db);
        
        $result = $ipBlocker->unblockIP($ipAddress);
        
        if ($result) {
            $this->sendResponse([
                'success' => true,
                'message' => 'IP desbloqueado com sucesso'
            ]);
        } else {
            $this->sendError('Erro ao desbloquear IP', 500);
        }
    }
}



