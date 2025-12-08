<?php
/**
 * SafeNode - Cache Manager
 * Sistema de cache em memória com suporte a Redis e fallback para memória local
 * 
 * Reduz drasticamente queries ao banco de dados e melhora performance
 */

class CacheManager {
    private static $instance = null;
    private $redis = null;
    private $memoryCache = []; // Fallback quando Redis não está disponível
    private $useRedis = false;
    private $redisConnected = false;
    
    // TTLs padrão (em segundos)
    const TTL_BLOCKED_IPS = 300;        // 5 minutos
    const TTL_RATE_LIMIT = 60;           // 1 minuto (ajustável por janela)
    const TTL_IP_REPUTATION = 900;       // 15 minutos
    const TTL_SITE_CONFIG = 1800;        // 30 minutos
    const TTL_THREAT_PATTERNS = 3600;    // 1 hora
    
    private function __construct() {
        $this->initRedis();
    }
    
    /**
     * Singleton pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializa conexão com Redis
     */
    private function initRedis() {
        // Verificar se extensão Redis está disponível
        if (!extension_loaded('redis')) {
            error_log("SafeNode Cache: Extensão Redis não disponível, usando cache em memória");
            $this->useRedis = false;
            return;
        }
        
        // Tentar conectar ao Redis
        try {
            $this->redis = new Redis();
            
            // Configurações do Redis (pode ser via variáveis de ambiente)
            $redisHost = getenv('REDIS_HOST') ?: '127.0.0.1';
            $redisPort = (int)(getenv('REDIS_PORT') ?: 6379);
            $redisPassword = getenv('REDIS_PASSWORD') ?: null;
            $redisDatabase = (int)(getenv('REDIS_DATABASE') ?: 0);
            
            // Tentar conectar
            $connected = $this->redis->connect($redisHost, $redisPort, 1.0); // 1 segundo timeout
            
            if ($connected) {
                // Autenticar se necessário
                if ($redisPassword) {
                    $this->redis->auth($redisPassword);
                }
                
                // Selecionar database
                $this->redis->select($redisDatabase);
                
                // Testar conexão
                $this->redis->ping();
                
                $this->useRedis = true;
                $this->redisConnected = true;
                
                error_log("SafeNode Cache: Conectado ao Redis em $redisHost:$redisPort");
            } else {
                throw new Exception("Não foi possível conectar ao Redis");
            }
        } catch (Exception $e) {
            error_log("SafeNode Cache: Erro ao conectar Redis - " . $e->getMessage() . ". Usando cache em memória.");
            $this->useRedis = false;
            $this->redis = null;
        }
    }
    
    /**
     * Obtém valor do cache
     * 
     * @param string $key Chave do cache
     * @return mixed|null Valor do cache ou null se não existir/expirado
     */
    public function get($key) {
        $fullKey = $this->getFullKey($key);
        
        if ($this->useRedis && $this->redisConnected) {
            try {
                $value = $this->redis->get($fullKey);
                if ($value !== false) {
                    return unserialize($value);
                }
            } catch (Exception $e) {
                error_log("SafeNode Cache: Erro ao ler do Redis - " . $e->getMessage());
                // Fallback para memória
                return $this->getFromMemory($fullKey);
            }
        }
        
        return $this->getFromMemory($fullKey);
    }
    
    /**
     * Define valor no cache
     * 
     * @param string $key Chave do cache
     * @param mixed $value Valor a ser cacheado
     * @param int|null $ttl Tempo de vida em segundos (null = usar TTL padrão)
     * @return bool Sucesso da operação
     */
    public function set($key, $value, $ttl = null) {
        $fullKey = $this->getFullKey($key);
        
        // Serializar valor
        $serialized = serialize($value);
        
        if ($this->useRedis && $this->redisConnected) {
            try {
                if ($ttl === null) {
                    $ttl = self::TTL_BLOCKED_IPS; // TTL padrão
                }
                
                $result = $this->redis->setex($fullKey, $ttl, $serialized);
                
                // Também salvar em memória como backup
                $this->setInMemory($fullKey, $value, $ttl);
                
                return $result;
            } catch (Exception $e) {
                error_log("SafeNode Cache: Erro ao escrever no Redis - " . $e->getMessage());
                // Fallback para memória
                return $this->setInMemory($fullKey, $value, $ttl);
            }
        }
        
        return $this->setInMemory($fullKey, $value, $ttl);
    }
    
    /**
     * Remove valor do cache
     * 
     * @param string $key Chave do cache
     * @return bool Sucesso da operação
     */
    public function delete($key) {
        $fullKey = $this->getFullKey($key);
        
        if ($this->useRedis && $this->redisConnected) {
            try {
                $this->redis->del($fullKey);
            } catch (Exception $e) {
                error_log("SafeNode Cache: Erro ao deletar do Redis - " . $e->getMessage());
            }
        }
        
        // Remover da memória também
        unset($this->memoryCache[$fullKey]);
        
        return true;
    }
    
    /**
     * Remove múltiplas chaves (padrão)
     * 
     * @param string $pattern Padrão das chaves (ex: "blocked_ip:*")
     * @return int Número de chaves removidas
     */
    public function deletePattern($pattern) {
        $fullPattern = $this->getFullKey($pattern);
        $deleted = 0;
        
        if ($this->useRedis && $this->redisConnected) {
            try {
                $keys = $this->redis->keys($fullPattern);
                if (!empty($keys)) {
                    $deleted = $this->redis->del($keys);
                }
            } catch (Exception $e) {
                error_log("SafeNode Cache: Erro ao deletar padrão do Redis - " . $e->getMessage());
            }
        }
        
        // Limpar da memória também
        foreach ($this->memoryCache as $key => $data) {
            if (fnmatch($fullPattern, $key)) {
                unset($this->memoryCache[$key]);
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * Incrementa valor numérico (útil para rate limiting)
     * 
     * @param string $key Chave do cache
     * @param int $increment Valor a incrementar (padrão: 1)
     * @param int|null $ttl TTL em segundos (se a chave não existir)
     * @return int Novo valor
     */
    public function increment($key, $increment = 1, $ttl = null) {
        $fullKey = $this->getFullKey($key);
        
        if ($this->useRedis && $this->redisConnected) {
            try {
                $newValue = $this->redis->incrBy($fullKey, $increment);
                
                // Se a chave foi criada agora, definir TTL
                if ($ttl !== null && $this->redis->ttl($fullKey) === -1) {
                    $this->redis->expire($fullKey, $ttl);
                }
                
                // Atualizar memória também
                $this->setInMemory($fullKey, $newValue, $ttl);
                
                return $newValue;
            } catch (Exception $e) {
                error_log("SafeNode Cache: Erro ao incrementar no Redis - " . $e->getMessage());
                // Fallback para memória
                return $this->incrementInMemory($fullKey, $increment, $ttl);
            }
        }
        
        return $this->incrementInMemory($fullKey, $increment, $ttl);
    }
    
    /**
     * Limpa todo o cache (cuidado!)
     * 
     * @return bool Sucesso da operação
     */
    public function flush() {
        if ($this->useRedis && $this->redisConnected) {
            try {
                $this->redis->flushDB();
            } catch (Exception $e) {
                error_log("SafeNode Cache: Erro ao limpar Redis - " . $e->getMessage());
            }
        }
        
        $this->memoryCache = [];
        
        return true;
    }
    
    /**
     * Verifica se chave existe no cache
     * 
     * @param string $key Chave do cache
     * @return bool True se existe e não expirou
     */
    public function exists($key) {
        $fullKey = $this->getFullKey($key);
        
        if ($this->useRedis && $this->redisConnected) {
            try {
                return $this->redis->exists($fullKey) > 0;
            } catch (Exception $e) {
                error_log("SafeNode Cache: Erro ao verificar existência no Redis - " . $e->getMessage());
                return isset($this->memoryCache[$fullKey]);
            }
        }
        
        return isset($this->memoryCache[$fullKey]);
    }
    
    /**
     * Obtém TTL de uma chave
     * 
     * @param string $key Chave do cache
     * @return int TTL em segundos (-1 se não expira, -2 se não existe)
     */
    public function getTTL($key) {
        $fullKey = $this->getFullKey($key);
        
        if ($this->useRedis && $this->redisConnected) {
            try {
                return $this->redis->ttl($fullKey);
            } catch (Exception $e) {
                error_log("SafeNode Cache: Erro ao obter TTL do Redis - " . $e->getMessage());
            }
        }
        
        // Para memória, verificar se existe e retornar TTL restante
        if (isset($this->memoryCache[$fullKey])) {
            $data = $this->memoryCache[$fullKey];
            $remaining = $data['expires_at'] - time();
            return $remaining > 0 ? $remaining : -2;
        }
        
        return -2;
    }
    
    /**
     * Adiciona item ao início de uma lista (LPUSH)
     * 
     * @param string $key Chave da lista
     * @param mixed $value Valor a adicionar
     * @return int|bool Tamanho da lista após adicionar ou false em erro
     */
    public function lpush($key, $value) {
        $fullKey = $this->getFullKey($key);
        
        if ($this->useRedis && $this->redisConnected) {
            try {
                return $this->redis->lpush($fullKey, serialize($value));
            } catch (Exception $e) {
                error_log("SafeNode Cache: Erro ao fazer LPUSH - " . $e->getMessage());
                return $this->lpushMemory($fullKey, $value);
            }
        }
        
        return $this->lpushMemory($fullKey, $value);
    }
    
    /**
     * Remove e retorna último item de uma lista (RPOP)
     * 
     * @param string $key Chave da lista
     * @return mixed|null Valor removido ou null se lista vazia
     */
    public function rpop($key) {
        $fullKey = $this->getFullKey($key);
        
        if ($this->useRedis && $this->redisConnected) {
            try {
                $value = $this->redis->rpop($fullKey);
                return $value !== false ? unserialize($value) : null;
            } catch (Exception $e) {
                error_log("SafeNode Cache: Erro ao fazer RPOP - " . $e->getMessage());
                return $this->rpopMemory($fullKey);
            }
        }
        
        return $this->rpopMemory($fullKey);
    }
    
    /**
     * Retorna tamanho de uma lista (LLEN)
     * 
     * @param string $key Chave da lista
     * @return int Tamanho da lista
     */
    public function llen($key) {
        $fullKey = $this->getFullKey($key);
        
        if ($this->useRedis && $this->redisConnected) {
            try {
                return $this->redis->llen($fullKey) ?: 0;
            } catch (Exception $e) {
                error_log("SafeNode Cache: Erro ao fazer LLEN - " . $e->getMessage());
                return $this->llenMemory($fullKey);
            }
        }
        
        return $this->llenMemory($fullKey);
    }
    
    /**
     * Limita tamanho de uma lista (LTRIM)
     * 
     * @param string $key Chave da lista
     * @param int $start Índice inicial
     * @param int $stop Índice final
     * @return bool Sucesso
     */
    public function ltrim($key, $start, $stop) {
        $fullKey = $this->getFullKey($key);
        
        if ($this->useRedis && $this->redisConnected) {
            try {
                return $this->redis->ltrim($fullKey, $start, $stop);
            } catch (Exception $e) {
                error_log("SafeNode Cache: Erro ao fazer LTRIM - " . $e->getMessage());
                return $this->ltrimMemory($fullKey, $start, $stop);
            }
        }
        
        return $this->ltrimMemory($fullKey, $start, $stop);
    }
    
    /**
     * Obtém estatísticas do cache
     * 
     * @return array Estatísticas
     */
    public function getStats() {
        $stats = [
            'redis_connected' => $this->redisConnected,
            'using_redis' => $this->useRedis,
            'memory_items' => count($this->memoryCache),
        ];
        
        if ($this->useRedis && $this->redisConnected) {
            try {
                $info = $this->redis->info();
                $stats['redis_info'] = [
                    'used_memory' => $info['used_memory_human'] ?? 'N/A',
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                    'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                ];
            } catch (Exception $e) {
                $stats['redis_error'] = $e->getMessage();
            }
        }
        
        return $stats;
    }
    
    // ========== MÉTODOS PRIVADOS ==========
    
    /**
     * Gera chave completa com prefixo
     */
    private function getFullKey($key) {
        return 'safenode:' . $key;
    }
    
    /**
     * Obtém valor da memória local
     */
    private function getFromMemory($fullKey) {
        if (!isset($this->memoryCache[$fullKey])) {
            return null;
        }
        
        $data = $this->memoryCache[$fullKey];
        
        // Verificar se expirou
        if ($data['expires_at'] !== null && $data['expires_at'] < time()) {
            unset($this->memoryCache[$fullKey]);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * Define valor na memória local
     */
    private function setInMemory($fullKey, $value, $ttl = null) {
        $expiresAt = null;
        if ($ttl !== null) {
            $expiresAt = time() + $ttl;
        }
        
        $this->memoryCache[$fullKey] = [
            'value' => $value,
            'expires_at' => $expiresAt,
            'created_at' => time()
        ];
        
        return true;
    }
    
    /**
     * Incrementa valor na memória local
     */
    private function incrementInMemory($fullKey, $increment, $ttl = null) {
        $current = $this->getFromMemory($fullKey);
        $newValue = ($current !== null ? (int)$current : 0) + $increment;
        $this->setInMemory($fullKey, $newValue, $ttl);
        return $newValue;
    }
    
    /**
     * LPUSH em memória local
     */
    private function lpushMemory($fullKey, $value) {
        if (!isset($this->memoryCache[$fullKey])) {
            $this->memoryCache[$fullKey] = ['type' => 'list', 'items' => []];
        }
        
        if (!isset($this->memoryCache[$fullKey]['items']) || !is_array($this->memoryCache[$fullKey]['items'])) {
            $this->memoryCache[$fullKey] = ['type' => 'list', 'items' => []];
        }
        
        array_unshift($this->memoryCache[$fullKey]['items'], $value);
        return count($this->memoryCache[$fullKey]['items']);
    }
    
    /**
     * RPOP em memória local
     */
    private function rpopMemory($fullKey) {
        if (!isset($this->memoryCache[$fullKey]) || !isset($this->memoryCache[$fullKey]['items'])) {
            return null;
        }
        
        $items = &$this->memoryCache[$fullKey]['items'];
        if (empty($items)) {
            return null;
        }
        
        return array_pop($items);
    }
    
    /**
     * LLEN em memória local
     */
    private function llenMemory($fullKey) {
        if (!isset($this->memoryCache[$fullKey]) || !isset($this->memoryCache[$fullKey]['items'])) {
            return 0;
        }
        
        return count($this->memoryCache[$fullKey]['items']);
    }
    
    /**
     * LTRIM em memória local
     */
    private function ltrimMemory($fullKey, $start, $stop) {
        if (!isset($this->memoryCache[$fullKey]) || !isset($this->memoryCache[$fullKey]['items'])) {
            return true;
        }
        
        $items = &$this->memoryCache[$fullKey]['items'];
        $items = array_slice($items, $start, $stop - $start + 1);
        return true;
    }
}

