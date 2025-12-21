<?php
/**
 * SafeNode - Protection Streak Manager
 * Gerencia a sequência de dias consecutivos de proteção (similar ao foguinho do TikTok)
 */

class ProtectionStreak {
    private $db;
    
    public function __construct($database = null) {
        $this->db = $database ?: getSafeNodeDatabase();
        $this->ensureTableExists();
    }
    
    /**
     * Cria a tabela se não existir
     */
    private function ensureTableExists() {
        if (!$this->db) {
            return;
        }
        
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_protection_streaks (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    site_id INT NOT NULL DEFAULT 0,
                    current_streak INT NOT NULL DEFAULT 0,
                    longest_streak INT NOT NULL DEFAULT 0,
                    last_protected_date DATE NOT NULL,
                    enabled TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_user_site (user_id, site_id),
                    INDEX idx_user_id (user_id),
                    INDEX idx_site_id (site_id),
                    INDEX idx_last_protected (last_protected_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (PDOException $e) {
            error_log("Erro ao criar tabela de sequências: " . $e->getMessage());
        }
    }
    
    /**
     * Registra proteção do dia atual
     */
    public function recordProtection($userId, $siteId = 0) {
        if (!$this->db || !$userId) {
            return false;
        }
        
        try {
            $today = date('Y-m-d');
            
            // Buscar sequência atual
            $stmt = $this->db->prepare("
                SELECT current_streak, longest_streak, last_protected_date, enabled 
                FROM safenode_protection_streaks 
                WHERE user_id = ? AND site_id = ?
            ");
            $stmt->execute([$userId, $siteId]);
            $streak = $stmt->fetch();
            
            if (!$streak) {
                // Criar nova sequência
                $stmt = $this->db->prepare("
                    INSERT INTO safenode_protection_streaks 
                    (user_id, site_id, current_streak, longest_streak, last_protected_date, enabled)
                    VALUES (?, ?, 1, 1, ?, 0)
                ");
                $stmt->execute([$userId, $siteId, $today]);
                return true;
            }
            
            // Se não está habilitado, não atualizar
            if (!$streak['enabled']) {
                return false;
            }
            
            $lastDate = $streak['last_protected_date'];
            $currentStreak = (int)$streak['current_streak'];
            $longestStreak = (int)$streak['longest_streak'];
            
            // Se já foi registrado hoje, não fazer nada
            if ($lastDate === $today) {
                return true;
            }
            
            // Calcular dias de diferença
            $lastTimestamp = strtotime($lastDate);
            $todayTimestamp = strtotime($today);
            $daysDiff = floor(($todayTimestamp - $lastTimestamp) / (60 * 60 * 24));
            
            if ($daysDiff === 1) {
                // Dia consecutivo - incrementar sequência
                $newStreak = $currentStreak + 1;
                $newLongest = max($longestStreak, $newStreak);
            } elseif ($daysDiff > 1) {
                // Quebrou a sequência - reiniciar
                $newStreak = 1;
                $newLongest = $longestStreak; // Manter o recorde
            } else {
                // Mesmo dia - não fazer nada
                return true;
            }
            
            // Atualizar sequência
            $stmt = $this->db->prepare("
                UPDATE safenode_protection_streaks 
                SET current_streak = ?, 
                    longest_streak = ?, 
                    last_protected_date = ?,
                    updated_at = NOW()
                WHERE user_id = ? AND site_id = ?
            ");
            $stmt->execute([$newStreak, $newLongest, $today, $userId, $siteId]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Erro ao registrar proteção: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém informações da sequência atual
     */
    public function getStreak($userId, $siteId = 0) {
        if (!$this->db || !$userId) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT current_streak, longest_streak, last_protected_date, enabled
                FROM safenode_protection_streaks 
                WHERE user_id = ? AND site_id = ?
            ");
            $stmt->execute([$userId, $siteId]);
            $streak = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$streak) {
                return [
                    'current_streak' => 0,
                    'longest_streak' => 0,
                    'last_protected_date' => null,
                    'enabled' => false,
                    'is_active' => false
                ];
            }
            
            // Converter enabled para boolean corretamente
            $enabled = (int)$streak['enabled'] === 1;
            
            // Verificar se a sequência está ativa (último dia foi hoje ou ontem)
            $lastDate = $streak['last_protected_date'];
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            
            $isActive = $enabled && 
                       ($lastDate === $today || $lastDate === $yesterday) &&
                       (int)$streak['current_streak'] > 0;
            
            return [
                'current_streak' => (int)$streak['current_streak'],
                'longest_streak' => (int)$streak['longest_streak'],
                'last_protected_date' => $streak['last_protected_date'],
                'enabled' => $enabled,
                'is_active' => $isActive
            ];
            
        } catch (PDOException $e) {
            error_log("Erro ao obter sequência: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Ativa ou desativa a sequência para o usuário
     */
    public function setEnabled($userId, $siteId = 0, $enabled = true) {
        error_log("ProtectionStreak::setEnabled called - userId: $userId, siteId: $siteId, enabled: " . ($enabled ? 'true' : 'false'));
        
        if (!$this->db || !$userId) {
            error_log("ProtectionStreak::setEnabled - Missing DB or userId");
            return false;
        }
        
        try {
            // Verificar se existe registro
            $stmt = $this->db->prepare("
                SELECT id, enabled FROM safenode_protection_streaks 
                WHERE user_id = ? AND site_id = ?
            ");
            $stmt->execute([$userId, $siteId]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $enabledInt = $enabled ? 1 : 0;
            error_log("ProtectionStreak::setEnabled - Record exists: " . ($exists ? 'yes' : 'no'));
            if ($exists) {
                error_log("ProtectionStreak::setEnabled - Current enabled value: " . $exists['enabled']);
            }
            
            if ($exists) {
                // Atualizar sempre (garantir que está correto)
                $stmt = $this->db->prepare("
                    UPDATE safenode_protection_streaks 
                    SET enabled = ? 
                    WHERE user_id = ? AND site_id = ?
                ");
                $stmt->execute([$enabledInt, $userId, $siteId]);
                $rowsAffected = $stmt->rowCount();
                error_log("ProtectionStreak::setEnabled - UPDATE executed, rows affected: $rowsAffected");
                
                // Verificar se realmente atualizou
                $stmt = $this->db->prepare("
                    SELECT enabled FROM safenode_protection_streaks 
                    WHERE user_id = ? AND site_id = ?
                ");
                $stmt->execute([$userId, $siteId]);
                $updatedValue = (int)$stmt->fetchColumn();
                error_log("ProtectionStreak::setEnabled - Value after update: $updatedValue (expected: $enabledInt)");
                
                $result = ($updatedValue === $enabledInt);
                error_log("ProtectionStreak::setEnabled - Update successful: " . ($result ? 'yes' : 'no'));
                return $result;
            } else {
                // Criar novo registro
                $today = date('Y-m-d');
                error_log("ProtectionStreak::setEnabled - Creating new record with enabled: $enabledInt");
                $stmt = $this->db->prepare("
                    INSERT INTO safenode_protection_streaks 
                    (user_id, site_id, current_streak, longest_streak, last_protected_date, enabled)
                    VALUES (?, ?, 0, 0, ?, ?)
                ");
                $stmt->execute([$userId, $siteId, $today, $enabledInt]);
                $rowsAffected = $stmt->rowCount();
                error_log("ProtectionStreak::setEnabled - INSERT executed, rows affected: $rowsAffected");
                return $rowsAffected > 0;
            }
            
        } catch (PDOException $e) {
            error_log("ProtectionStreak::setEnabled - PDOException: " . $e->getMessage());
            error_log("ProtectionStreak::setEnabled - Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Verifica se a sequência está habilitada
     */
    public function isEnabled($userId, $siteId = 0) {
        $streak = $this->getStreak($userId, $siteId);
        return $streak && $streak['enabled'];
    }
}

