<?php
/**
 * SafeNode - IP Blocker
 * Sistema de bloqueio automático de IPs
 */

class IPBlocker {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Verifica se um IP está bloqueado
     */
    public function isBlocked($ipAddress) {
        if (!$this->db) return false;
        
        try {
            // Verificar na blacklist
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_blocked_ips 
                WHERE ip_address = ? 
                AND (expires_at IS NULL OR expires_at > NOW())
                AND is_active = 1
            ");
            $stmt->execute([$ipAddress]);
            
            if ($stmt->fetch()) {
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("SafeNode IPBlocker Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se um IP está na whitelist
     */
    public function isWhitelisted($ipAddress) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_whitelist 
                WHERE ip_address = ? 
                AND is_active = 1
            ");
            $stmt->execute([$ipAddress]);
            
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log("SafeNode IPBlocker Whitelist Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Bloqueia um IP automaticamente
     */
    public function blockIP($ipAddress, $reason, $threatType = null, $duration = null) {
        if (!$this->db) return false;
        
        // Não bloquear se estiver na whitelist
        if ($this->isWhitelisted($ipAddress)) {
            return false;
        }
        
        try {
            // Verificar se já está bloqueado
            $stmt = $this->db->prepare("
                SELECT id FROM safenode_blocked_ips 
                WHERE ip_address = ?
            ");
            $stmt->execute([$ipAddress]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Atualizar bloqueio existente
                $stmt = $this->db->prepare("
                    UPDATE safenode_blocked_ips 
                    SET reason = ?, threat_type = ?, 
                        expires_at = ?, is_active = 1,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $reason,
                    $threatType,
                    $duration ? date('Y-m-d H:i:s', strtotime("+$duration seconds")) : null,
                    $existing['id']
                ]);
            } else {
                // Criar novo bloqueio
                $stmt = $this->db->prepare("
                    INSERT INTO safenode_blocked_ips 
                    (ip_address, reason, threat_type, expires_at, is_active, created_at) 
                    VALUES (?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([
                    $ipAddress,
                    $reason,
                    $threatType,
                    $duration ? date('Y-m-d H:i:s', strtotime("+$duration seconds")) : null
                ]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("SafeNode IPBlocker Block Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Desbloqueia um IP
     */
    public function unblockIP($ipAddress) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                UPDATE safenode_blocked_ips 
                SET is_active = 0, updated_at = NOW()
                WHERE ip_address = ?
            ");
            $stmt->execute([$ipAddress]);
            
            return true;
        } catch (PDOException $e) {
            error_log("SafeNode IPBlocker Unblock Error: " . $e->getMessage());
            return false;
        }
    }
}




