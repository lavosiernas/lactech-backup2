<?php
/**
 * SafeNode - Log Queue
 * Sistema de fila para processamento assíncrono de logs
 * 
 * Reduz latência ao não bloquear requisições esperando logs serem escritos
 */

class LogQueue {
    private $cache;
    private $db;
    private $queueName = 'safenode:log_queue';
    private $maxQueueSize = 10000; // Máximo de logs na fila
    private $batchSize = 100; // Processar em lotes de 100
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
    }
    
    /**
     * Adiciona log à fila (assíncrono)
     * 
     * @param array $logData Dados do log
     * @return bool Sucesso
     */
    public function enqueue($logData) {
        try {
            // Serializar dados do log
            $serialized = json_encode($logData);
            
            // Adicionar à fila (usar lista do Redis ou array em memória)
            if (method_exists($this->cache, 'lpush')) {
                // Se Redis está disponível, usar LPUSH (mais eficiente)
                $this->cache->lpush($this->queueName, $serialized);
                
                // Limitar tamanho da fila
                $this->cache->ltrim($this->queueName, 0, $this->maxQueueSize - 1);
            } else {
                // Fallback: usar método simples de cache
                $queue = $this->cache->get($this->queueName) ?: [];
                array_unshift($queue, $serialized);
                
                // Limitar tamanho
                $queue = array_slice($queue, 0, $this->maxQueueSize);
                
                $this->cache->set($this->queueName, $queue, 3600); // TTL de 1 hora
            }
            
            return true;
        } catch (Exception $e) {
            error_log("SafeNode LogQueue Enqueue Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Processa logs da fila (chamado por worker/cron)
     * 
     * @param int $maxItems Máximo de itens a processar
     * @return int Número de logs processados
     */
    public function process($maxItems = null) {
        if (!$this->db) return 0;
        
        $processed = 0;
        $maxItems = $maxItems ?: $this->batchSize;
        
        try {
            require_once __DIR__ . '/SecurityLogger.php';
            $logger = new SecurityLogger($this->db);
            
            // Obter logs da fila
            $logs = $this->dequeue($maxItems);
            
            foreach ($logs as $logData) {
                try {
                    // Deserializar
                    $data = json_decode($logData, true);
                    if (!$data) continue;
                    
                    // Escrever log no banco
                    $logger->log(
                        $data['ip_address'] ?? '0.0.0.0',
                        $data['request_uri'] ?? '/',
                        $data['request_method'] ?? 'GET',
                        $data['action_taken'] ?? 'allowed',
                        $data['threat_type'] ?? null,
                        $data['threat_score'] ?? 0,
                        $data['user_agent'] ?? null,
                        $data['referer'] ?? null,
                        $data['site_id'] ?? null,
                        $data['response_time'] ?? null,
                        $data['country_code'] ?? null,
                        $data['confidence_score'] ?? null
                    );
                    
                    $processed++;
                } catch (Exception $e) {
                    error_log("SafeNode LogQueue Process Error: " . $e->getMessage());
                    // Continuar processando outros logs mesmo se um falhar
                }
            }
            
            return $processed;
        } catch (Exception $e) {
            error_log("SafeNode LogQueue Process Error: " . $e->getMessage());
            return $processed;
        }
    }
    
    /**
     * Remove logs da fila
     * 
     * @param int $count Número de logs a remover
     * @return array Array de logs removidos
     */
    private function dequeue($count) {
        $logs = [];
        
        try {
            if (method_exists($this->cache, 'rpop')) {
                // Se Redis está disponível, usar RPOP (FIFO)
                for ($i = 0; $i < $count; $i++) {
                    $log = $this->cache->rpop($this->queueName);
                    if ($log === null || $log === false) break;
                    $logs[] = $log;
                }
            } else {
                // Fallback: usar método simples de cache
                $queue = $this->cache->get($this->queueName) ?: [];
                
                // Remover últimos N itens (FIFO)
                $logs = array_slice($queue, -$count);
                $queue = array_slice($queue, 0, -$count);
                
                if (empty($queue)) {
                    $this->cache->delete($this->queueName);
                } else {
                    $this->cache->set($this->queueName, $queue, 3600);
                }
            }
        } catch (Exception $e) {
            error_log("SafeNode LogQueue Dequeue Error: " . $e->getMessage());
        }
        
        return $logs;
    }
    
    /**
     * Obtém tamanho da fila
     * 
     * @return int Número de logs na fila
     */
    public function getQueueSize() {
        try {
            if (method_exists($this->cache, 'llen')) {
                return $this->cache->llen($this->queueName) ?: 0;
            } else {
                $queue = $this->cache->get($this->queueName) ?: [];
                return count($queue);
            }
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Limpa a fila (cuidado!)
     * 
     * @return bool Sucesso
     */
    public function clear() {
        return $this->cache->delete($this->queueName);
    }
}






