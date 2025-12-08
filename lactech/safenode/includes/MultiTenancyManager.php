<?php
/**
 * SafeNode - Multi-Tenancy Manager
 * Sistema de multi-tenancy melhorado com isolamento completo
 * 
 * Funcionalidades:
 * - Namespace de cache por site_id
 * - Isolamento de dados no banco
 * - Rate limits independentes
 * - Configurações isoladas
 */

class MultiTenancyManager {
    private $db;
    private $cache;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
    }
    
    /**
     * Obtém namespace de cache para site
     */
    public function getCacheNamespace($siteId) {
        return "site:$siteId:";
    }
    
    /**
     * Aplica isolamento de cache
     */
    public function applyCacheIsolation($siteId, $key) {
        return $this->getCacheNamespace($siteId) . $key;
    }
    
    /**
     * Cria view isolada para site
     */
    public function createSiteView($siteId, $viewName) {
        if (!$this->db) return false;
        
        try {
            $fullViewName = "v_site_{$siteId}_{$viewName}";
            
            // Exemplo: view de logs do site
            $this->db->exec("
                CREATE OR REPLACE VIEW $fullViewName AS
                SELECT * FROM safenode_security_logs
                WHERE site_id = $siteId
            ");
            
            return true;
        } catch (PDOException $e) {
            error_log("SafeNode MultiTenancy View Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém configurações isoladas do site
     */
    public function getSiteConfig($siteId) {
        $cacheKey = $this->applyCacheIsolation($siteId, 'config');
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_sites 
                WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([$siteId]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($config) {
                // Cache por 30 minutos
                $this->cache->set($cacheKey, $config, 1800);
                return $config;
            }
        } catch (PDOException $e) {
            // Ignorar
        }
        
        return [];
    }
    
    /**
     * Verifica se usuário tem acesso ao site
     */
    public function hasAccess($userId, $siteId) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM safenode_sites 
                WHERE id = ? AND user_id = ? AND is_active = 1
            ");
            $stmt->execute([$siteId, $userId]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Obtém sites do usuário
     */
    public function getUserSites($userId) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT id, domain, security_level, is_active 
                FROM safenode_sites 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
}



