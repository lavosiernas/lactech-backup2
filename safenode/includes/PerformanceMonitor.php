<?php
/**
 * SafeNode - Performance Monitor
 * Sistema de monitoramento de performance de requisições
 */

class PerformanceMonitor {
    private $db;
    private $startTime;
    private $startMemory;
    private $siteId;
    private $endpoint;
    private $requestMethod;
    
    public function __construct($database, $siteId = null) {
        $this->db = $database;
        $this->siteId = $siteId;
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }
    
    /**
     * Inicia medição de performance
     */
    public function start($endpoint = null, $requestMethod = 'GET') {
        $this->endpoint = $endpoint ?? ($_SERVER['REQUEST_URI'] ?? '/');
        $this->requestMethod = $requestMethod ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }
    
    /**
     * Finaliza medição e salva log de performance
     */
    public function end() {
        if (!$this->db || !$this->siteId) {
            return; // Não salvar se não há DB ou site
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $responseTime = round(($endTime - $this->startTime) * 1000, 2); // ms
        $memoryUsage = $endMemory - $this->startMemory; // bytes
        
        // Extrair endpoint limpo (sem query string)
        $cleanEndpoint = $this->endpoint;
        if (strpos($cleanEndpoint, '?') !== false) {
            $cleanEndpoint = substr($cleanEndpoint, 0, strpos($cleanEndpoint, '?'));
        }
        if (strpos($cleanEndpoint, '#') !== false) {
            $cleanEndpoint = substr($cleanEndpoint, 0, strpos($cleanEndpoint, '#'));
        }
        $cleanEndpoint = $cleanEndpoint ?: '/';
        
        // Salvar log de performance (apenas se resposta > 500ms ou memória > 1MB)
        // Isso evita poluir o banco com dados desnecessários
        if ($responseTime > 500 || $memoryUsage > 1048576) {
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO safenode_performance_logs 
                    (site_id, endpoint, response_time, memory_usage, request_method, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $this->siteId,
                    $cleanEndpoint,
                    (int)$responseTime,
                    (int)$memoryUsage,
                    $this->requestMethod
                ]);
            } catch (PDOException $e) {
                // Não quebrar a requisição se falhar ao salvar performance
                error_log("SafeNode PerformanceMonitor Error: " . $e->getMessage());
            }
        }
        
        // Se resposta muito lenta (> 3s), logar como warning
        if ($responseTime > 3000) {
            error_log("SafeNode Performance Warning: Endpoint {$cleanEndpoint} demorou {$responseTime}ms");
        }
    }
    
    /**
     * Mede tempo de uma operação específica
     */
    public function measure($operation, callable $callback) {
        $start = microtime(true);
        $result = $callback();
        $duration = round((microtime(true) - $start) * 1000, 2);
        
        // Logar operações lentas
        if ($duration > 100) {
            error_log("SafeNode Performance: Operação '{$operation}' demorou {$duration}ms");
        }
        
        return $result;
    }
    
    /**
     * Retorna tempo de resposta atual (sem finalizar)
     */
    public function getCurrentResponseTime() {
        return round((microtime(true) - $this->startTime) * 1000, 2);
    }
    
    /**
     * Retorna uso de memória atual
     */
    public function getCurrentMemoryUsage() {
        return memory_get_usage(true) - $this->startMemory;
    }
}

