<?php
/**
 * SafeNode - Site Model
 * Model para gerenciamento de sites
 */

namespace SafeNode\Models;

class SiteModel extends BaseModel
{
    protected $table = 'safenode_sites';
    
    /**
     * Busca sites de um usuário
     */
    public function findByUserId(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("SiteModel::findByUserId Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Busca site por domínio
     */
    public function findByDomain(string $domain): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE domain = ?");
            $stmt->execute([$domain]);
            return $stmt->fetch() ?: null;
        } catch (\PDOException $e) {
            error_log("SiteModel::findByDomain Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Atualiza nível de segurança
     */
    public function updateSecurityLevel(int $siteId, string $level): bool
    {
        return $this->update($siteId, ['security_level' => $level]);
    }
}









