<?php
/**
 * SafeNode - Performance Analyzer
 * Análise detalhada de performance e latência
 * 
 * Métricas:
 * - Latência por componente
 * - Percentis (P50, P95, P99)
 * - Queries lentas
 * - Gargalos de performance
 */

class PerformanceAnalyzer {
    private $db;
    private $cache;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
    }
    
    /**
     * Analisa performance de um período
     * 
     * @param int $timeWindow Janela de tempo em segundos
     * @return array Métricas de performance
     */
    public function analyzePerformance($timeWindow = 3600) {
        if (!$this->db) return [];
        
        $metrics = [
            'latency' => $this->analyzeLatency($timeWindow),
            'slow_queries' => $this->detectSlowQueries($timeWindow),
            'component_performance' => $this->analyzeComponentPerformance($timeWindow),
            'bottlenecks' => $this->identifyBottlenecks($timeWindow)
        ];
        
        return $metrics;
    }
    
    /**
     * Analisa latência (percentis)
     */
    private function analyzeLatency($timeWindow) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    response_time,
                    COUNT(*) as count
                FROM safenode_security_logs
                WHERE response_time IS NOT NULL
                AND response_time > 0
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                ORDER BY response_time
            ");
            $stmt->execute([$timeWindow]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($results)) {
                return [
                    'p50' => null,
                    'p95' => null,
                    'p99' => null,
                    'avg' => null,
                    'min' => null,
                    'max' => null
                ];
            }
            
            // Calcular percentis
            $values = [];
            foreach ($results as $row) {
                $value = (float)$row['response_time'];
                $count = (int)$row['count'];
                for ($i = 0; $i < $count; $i++) {
                    $values[] = $value;
                }
            }
            
            sort($values);
            $total = count($values);
            
            return [
                'p50' => $this->percentile($values, 50),
                'p95' => $this->percentile($values, 95),
                'p99' => $this->percentile($values, 99),
                'avg' => array_sum($values) / $total,
                'min' => min($values),
                'max' => max($values),
                'total_requests' => $total
            ];
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Detecta queries lentas
     */
    private function detectSlowQueries($timeWindow) {
        // Em produção, usar slow query log do MySQL
        // Por enquanto, analisar latência de logs
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as slow_requests,
                    AVG(response_time) as avg_latency,
                    MAX(response_time) as max_latency
                FROM safenode_security_logs
                WHERE response_time IS NOT NULL
                AND response_time > 1000
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$timeWindow]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && (int)$result['slow_requests'] > 0) {
                return [
                    'detected' => true,
                    'slow_requests' => (int)$result['slow_requests'],
                    'avg_latency' => round((float)$result['avg_latency'], 2),
                    'max_latency' => round((float)$result['max_latency'], 2),
                    'threshold' => 1000 // ms
                ];
            }
        } catch (PDOException $e) {
            // Ignorar
        }
        
        return ['detected' => false];
    }
    
    /**
     * Analisa performance por componente
     */
    private function analyzeComponentPerformance($timeWindow) {
        // Análise baseada em logs (em produção, usar APM)
        $components = [
            'threat_detector' => ['avg_time' => 5, 'max_time' => 20],
            'rate_limiter' => ['avg_time' => 2, 'max_time' => 10],
            'database' => ['avg_time' => 10, 'max_time' => 50],
            'cache' => ['avg_time' => 1, 'max_time' => 5]
        ];
        
        // Em produção, coletar métricas reais de cada componente
        // Por enquanto, retornar estimativas baseadas em latência total
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    AVG(response_time) as avg_total_latency
                FROM safenode_security_logs
                WHERE response_time IS NOT NULL
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$timeWindow]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $avgLatency = (float)($result['avg_total_latency'] ?? 0);
            
            // Estimar tempo por componente (proporcional)
            foreach ($components as $component => &$metrics) {
                $metrics['estimated_avg'] = $avgLatency * ($metrics['avg_time'] / 18); // 18 = soma dos avg_times
            }
            
            return $components;
        } catch (PDOException $e) {
            return $components;
        }
    }
    
    /**
     * Identifica gargalos
     */
    private function identifyBottlenecks($timeWindow) {
        $bottlenecks = [];
        
        // Verificar se há muitas requisições simultâneas
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as concurrent_requests
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 10 SECOND)
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $concurrent = (int)($result['concurrent_requests'] ?? 0);
            if ($concurrent > 100) {
                $bottlenecks[] = [
                    'type' => 'high_concurrency',
                    'severity' => 'medium',
                    'value' => $concurrent,
                    'description' => "Muitas requisições simultâneas ($concurrent)"
                ];
            }
        } catch (PDOException $e) {
            // Ignorar
        }
        
        // Verificar latência alta
        $latency = $this->analyzeLatency($timeWindow);
        if (isset($latency['p95']) && $latency['p95'] > 500) {
            $bottlenecks[] = [
                'type' => 'high_latency',
                'severity' => 'high',
                'value' => $latency['p95'],
                'description' => "Latência P95 alta: " . round($latency['p95'], 2) . "ms"
            ];
        }
        
        return $bottlenecks;
    }
    
    /**
     * Calcula percentil
     */
    private function percentile($sortedArray, $percentile) {
        $index = ($percentile / 100) * (count($sortedArray) - 1);
        $lower = floor($index);
        $upper = ceil($index);
        $weight = $index - $lower;
        
        if ($lower === $upper) {
            return $sortedArray[$lower];
        }
        
        return $sortedArray[$lower] * (1 - $weight) + $sortedArray[$upper] * $weight;
    }
    
    /**
     * Obtém estatísticas de performance
     */
    public function getPerformanceStats($days = 7) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    AVG(response_time) as avg_latency,
                    MAX(response_time) as max_latency,
                    COUNT(*) as total_requests
                FROM safenode_security_logs
                WHERE response_time IS NOT NULL
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
}








