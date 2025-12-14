<?php
/**
 * SafeNode - Threat Intelligence Integration
 * Integração com feeds de inteligência de ameaças
 * 
 * Suporta:
 * - AbuseIPDB
 * - VirusTotal
 * - AlienVault OTX (futuro)
 * - Spamhaus DROP (futuro)
 */

class ThreatIntelligence {
    private $db;
    private $cache;
    private $abuseipdbApiKey;
    private $virustotalApiKey;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
        
        // Carregar API keys de variáveis de ambiente
        $this->abuseipdbApiKey = getenv('ABUSEIPDB_API_KEY') ?: '';
        $this->virustotalApiKey = getenv('VIRUSTOTAL_API_KEY') ?: '';
    }
    
    /**
     * Verifica IP em todos os feeds disponíveis
     * 
     * @param string $ipAddress IP a verificar
     * @return array Resultado da verificação
     */
    public function checkIP($ipAddress) {
        // Verificar cache primeiro (TTL: 1 hora)
        $cacheKey = "threat_intel:$ipAddress";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $results = [
            'ip_address' => $ipAddress,
            'is_malicious' => false,
            'confidence' => 0,
            'sources' => [],
            'reputation_score' => 50, // Neutro
            'last_checked' => time()
        ];
        
        // Verificar AbuseIPDB
        if (!empty($this->abuseipdbApiKey)) {
            $abuseResult = $this->checkAbuseIPDB($ipAddress);
            if ($abuseResult) {
                $results['sources']['abuseipdb'] = $abuseResult;
                if ($abuseResult['is_malicious']) {
                    $results['is_malicious'] = true;
                    $results['confidence'] = max($results['confidence'], $abuseResult['confidence']);
                    $results['reputation_score'] = min($results['reputation_score'], $abuseResult['reputation_score']);
                }
            }
        }
        
        // Verificar VirusTotal
        if (!empty($this->virustotalApiKey)) {
            $vtResult = $this->checkVirusTotal($ipAddress);
            if ($vtResult) {
                $results['sources']['virustotal'] = $vtResult;
                if ($vtResult['is_malicious']) {
                    $results['is_malicious'] = true;
                    $results['confidence'] = max($results['confidence'], $vtResult['confidence']);
                    $results['reputation_score'] = min($results['reputation_score'], $vtResult['reputation_score']);
                }
            }
        }
        
        // Salvar no cache
        $this->cache->set($cacheKey, $results, 3600); // 1 hora
        
        // Salvar no banco
        $this->saveToDatabase($ipAddress, $results);
        
        return $results;
    }
    
    /**
     * Verifica IP no AbuseIPDB
     */
    private function checkAbuseIPDB($ipAddress) {
        if (empty($this->abuseipdbApiKey)) return null;
        
        try {
            $url = "https://api.abuseipdb.com/api/v2/check";
            $params = [
                'ipAddress' => $ipAddress,
                'maxAgeInDays' => 90,
                'verbose' => ''
            ];
            
            $ch = curl_init($url . '?' . http_build_query($params));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Key: ' . $this->abuseipdbApiKey,
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 3
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if (isset($data['data'])) {
                    $data = $data['data'];
                    $abuseConfidence = (int)($data['abuseConfidencePercentage'] ?? 0);
                    $isWhitelisted = (bool)($data['isWhitelisted'] ?? false);
                    $usageType = $data['usageType'] ?? 'unknown';
                    
                    return [
                        'is_malicious' => $abuseConfidence >= 25 && !$isWhitelisted,
                        'confidence' => $abuseConfidence,
                        'reputation_score' => 100 - $abuseConfidence,
                        'usage_type' => $usageType,
                        'is_whitelisted' => $isWhitelisted,
                        'total_reports' => (int)($data['totalReports'] ?? 0),
                        'last_reported' => $data['lastReportedAt'] ?? null
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("SafeNode ThreatIntel AbuseIPDB Error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Verifica IP no VirusTotal
     */
    private function checkVirusTotal($ipAddress) {
        if (empty($this->virustotalApiKey)) return null;
        
        try {
            $url = "https://www.virustotal.com/vtapi/v2/ip-address/report";
            $params = [
                'apikey' => $this->virustotalApiKey,
                'ip' => $ipAddress
            ];
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 3
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                
                if (isset($data['response_code']) && $data['response_code'] === 1) {
                    $detections = (int)($data['detected_urls'] ?? 0);
                    $samples = (int)($data['detected_samples'] ?? 0);
                    $isMalicious = $detections > 0 || $samples > 0;
                    
                    // Calcular confidence baseado em detecções
                    $confidence = min(100, ($detections + $samples) * 10);
                    
                    return [
                        'is_malicious' => $isMalicious,
                        'confidence' => $confidence,
                        'reputation_score' => $isMalicious ? max(0, 50 - $confidence) : 50,
                        'detected_urls' => $detections,
                        'detected_samples' => $samples,
                        'as_owner' => $data['as_owner'] ?? null,
                        'country' => $data['country'] ?? null
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("SafeNode ThreatIntel VirusTotal Error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Salva resultado no banco de dados
     */
    private function saveToDatabase($ipAddress, $results) {
        if (!$this->db) return;
        
        try {
            $this->ensureTableExists();
            
            $stmt = $this->db->prepare("
                INSERT INTO safenode_threat_intelligence 
                (ip_address, is_malicious, confidence, reputation_score, sources_data, last_checked, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    is_malicious = VALUES(is_malicious),
                    confidence = VALUES(confidence),
                    reputation_score = VALUES(reputation_score),
                    sources_data = VALUES(sources_data),
                    last_checked = VALUES(last_checked),
                    updated_at = NOW()
            ");
            
            $stmt->execute([
                $ipAddress,
                $results['is_malicious'] ? 1 : 0,
                $results['confidence'],
                $results['reputation_score'],
                json_encode($results['sources'])
            ]);
        } catch (PDOException $e) {
            error_log("SafeNode ThreatIntel Save Error: " . $e->getMessage());
        }
    }
    
    /**
     * Obtém reputação de IP do banco (cache local)
     */
    public function getIPReputation($ipAddress) {
        if (!$this->db) return null;
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_threat_intelligence 
                WHERE ip_address = ?
                AND last_checked >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([$ipAddress]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'is_malicious' => (bool)$result['is_malicious'],
                    'confidence' => (int)$result['confidence'],
                    'reputation_score' => (int)$result['reputation_score'],
                    'sources' => json_decode($result['sources_data'], true) ?: []
                ];
            }
        } catch (PDOException $e) {
            // Ignorar
        }
        
        return null;
    }
    
    /**
     * Garante que tabela existe
     */
    private function ensureTableExists() {
        try {
            $this->db->query("SELECT 1 FROM safenode_threat_intelligence LIMIT 1");
        } catch (PDOException $e) {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_threat_intelligence (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL UNIQUE,
                    is_malicious TINYINT(1) DEFAULT 0,
                    confidence INT DEFAULT 0,
                    reputation_score INT DEFAULT 50,
                    sources_data TEXT,
                    last_checked DATETIME DEFAULT CURRENT_TIMESTAMP,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_ip (ip_address),
                    INDEX idx_malicious (is_malicious, reputation_score),
                    INDEX idx_checked (last_checked)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }
}








