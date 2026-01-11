<?php
/**
 * KRON - Sistema de RBAC (Role-Based Access Control)
 * Gerencia permissões hierárquicas
 */

require_once __DIR__ . '/config.php';

class KronRBAC
{
    private $pdo;
    
    public function __construct()
    {
        $this->pdo = getKronDatabase();
    }
    
    /**
     * Verifica se usuário tem permissão
     */
    public function hasPermission($userId, $permissionName)
    {
        if (!$this->pdo) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM kron_user_roles ur
                INNER JOIN kron_role_permissions rp ON ur.role_id = rp.role_id
                INNER JOIN kron_permissions p ON rp.permission_id = p.id
                WHERE ur.user_id = ? AND p.name = ?
            ");
            
            $stmt->execute([$userId, $permissionName]);
            $result = $stmt->fetch();
            
            return ($result['count'] ?? 0) > 0;
            
        } catch (PDOException $e) {
            error_log("KRON RBAC Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se usuário tem acesso a sistema+setor
     */
    public function hasSystemSectorAccess($userId, $systemId, $sectorId = null)
    {
        if (!$this->pdo) {
            return false;
        }
        
        try {
            // CEO tem acesso a tudo
            if ($this->isCEO($userId)) {
                return true;
            }
            
            // Verificar acesso específico
            $sql = "
                SELECT COUNT(*) as count
                FROM kron_user_system_sector
                WHERE user_id = ? AND system_id = ? AND is_active = 1
            ";
            
            $params = [$userId, $systemId];
            
            if ($sectorId !== null) {
                $sql .= " AND (sector_id = ? OR sector_id IS NULL)";
                $params[] = $sectorId;
            } else {
                $sql .= " AND sector_id IS NULL";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return ($result['count'] ?? 0) > 0;
            
        } catch (PDOException $e) {
            error_log("KRON RBAC Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se usuário é CEO
     */
    public function isCEO($userId)
    {
        if (!$this->pdo) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM kron_user_roles ur
                INNER JOIN kron_roles r ON ur.role_id = r.id
                WHERE ur.user_id = ? AND r.name = 'ceo'
            ");
            
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return ($result['count'] ?? 0) > 0;
            
        } catch (PDOException $e) {
            error_log("KRON RBAC Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se usuário pode criar Gerente Central
     */
    public function canCreateGerenteCentral($userId)
    {
        return $this->isCEO($userId);
    }
    
    /**
     * Verifica se usuário pode criar Gerente de Setor
     */
    public function canCreateGerenteSetor($userId)
    {
        if (!$this->pdo) {
            return false;
        }
        
        try {
            // CEO e Gerente Central podem criar Gerente de Setor
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM kron_user_roles ur
                INNER JOIN kron_roles r ON ur.role_id = r.id
                WHERE ur.user_id = ? AND r.name IN ('ceo', 'gerente_central')
            ");
            
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return ($result['count'] ?? 0) > 0;
            
        } catch (PDOException $e) {
            error_log("KRON RBAC Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém todas as permissões do usuário
     */
    public function getUserPermissions($userId)
    {
        if (!$this->pdo) {
            return [];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT p.name, p.display_name, p.category
                FROM kron_user_roles ur
                INNER JOIN kron_role_permissions rp ON ur.role_id = rp.role_id
                INNER JOIN kron_permissions p ON rp.permission_id = p.id
                WHERE ur.user_id = ?
                ORDER BY p.category, p.name
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("KRON RBAC Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém roles do usuário
     */
    public function getUserRoles($userId)
    {
        if (!$this->pdo) {
            return [];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.id, r.name, r.display_name, r.level
                FROM kron_user_roles ur
                INNER JOIN kron_roles r ON ur.role_id = r.id
                WHERE ur.user_id = ?
                ORDER BY r.level ASC
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("KRON RBAC Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém nível hierárquico mais alto do usuário
     */
    public function getUserHighestLevel($userId)
    {
        if (!$this->pdo) {
            return 999; // Nível alto = sem acesso
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT MIN(r.level) as min_level
                FROM kron_user_roles ur
                INNER JOIN kron_roles r ON ur.role_id = r.id
                WHERE ur.user_id = ?
            ");
            
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return $result['min_level'] ?? 999;
            
        } catch (PDOException $e) {
            error_log("KRON RBAC Error: " . $e->getMessage());
            return 999;
        }
    }
    
    /**
     * Atribui role a usuário
     */
    public function assignRole($userId, $roleId, $assignedBy)
    {
        if (!$this->pdo) {
            return false;
        }
        
        try {
            // Verificar se já existe
            $stmt = $this->pdo->prepare("
                SELECT id FROM kron_user_roles 
                WHERE user_id = ? AND role_id = ?
            ");
            $stmt->execute([$userId, $roleId]);
            
            if ($stmt->fetch()) {
                return true; // Já existe
            }
            
            // Inserir
            $stmt = $this->pdo->prepare("
                INSERT INTO kron_user_roles (user_id, role_id, assigned_by)
                VALUES (?, ?, ?)
            ");
            
            return $stmt->execute([$userId, $roleId, $assignedBy]);
            
        } catch (PDOException $e) {
            error_log("KRON RBAC Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Concede acesso sistema+setor
     */
    public function grantSystemSectorAccess($userId, $systemId, $sectorId, $grantedBy)
    {
        if (!$this->pdo) {
            return false;
        }
        
        try {
            // Verificar se já existe
            $stmt = $this->pdo->prepare("
                SELECT id FROM kron_user_system_sector 
                WHERE user_id = ? AND system_id = ? AND sector_id <=> ?
            ");
            $stmt->execute([$userId, $systemId, $sectorId]);
            
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Atualizar
                $stmt = $this->pdo->prepare("
                    UPDATE kron_user_system_sector 
                    SET is_active = 1, granted_by = ?, granted_at = NOW()
                    WHERE id = ?
                ");
                return $stmt->execute([$grantedBy, $existing['id']]);
            } else {
                // Inserir
                $stmt = $this->pdo->prepare("
                    INSERT INTO kron_user_system_sector (user_id, system_id, sector_id, granted_by)
                    VALUES (?, ?, ?, ?)
                ");
                return $stmt->execute([$userId, $systemId, $sectorId, $grantedBy]);
            }
            
        } catch (PDOException $e) {
            error_log("KRON RBAC Error: " . $e->getMessage());
            return false;
        }
    }
}



