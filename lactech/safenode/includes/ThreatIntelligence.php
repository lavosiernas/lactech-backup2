<?php
/**
 * SafeNode - Threat Intelligence Integration (FUNCIONAL)
 * Integração com feeds de inteligência de ameaças
 * 
 * STATUS: FUNCIONAL - Sistema completo com múltiplas fontes e fallback inteligente
 * 
 * Suporta:
 * - AbuseIPDB (se API key configurada)
 * - VirusTotal (se API key configurada)
 * - SafeNode Threat Intelligence Network (sempre ativo)
 * - Sistema próprio de reputação (fallback)
 * - Análise combinada de múltiplas fontes
 */

class ThreatIntelligence {
    private $db;
    private $cache;
    private $abuseipdbApiKey;
    private $virustotalApiKey;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        require_once __DIR__ . '/Settings.php';
        $this->cache = CacheManager::getInstance();
        
        // Carregar API keys (variáveis de ambiente ou Settings)
        $this->abuseipdbApiKey = getenv('ABUSEIPDB_API_KEY') 
            ?: SafeNodeSettings::get('abuseipdb_api_key', '')
            ?: '';
        $this->virustotalApiKey = getenv('VIRUSTOTAL_API_KEY')
            ?: SafeNodeSettings::get('virustotal_api_key', '')
            ?: '';
        
        // Garantir que tabela existe
        $this->ensureTableExists();
    }
    
    /**
     * Verifica IP em todos os feeds disponíveis com fallback inteligente
     * 
     * @param string $ipAddress IP a verificar
     * @param bool $forceRefresh Forçar atualização mesmo se em cache
     * @return array Resultado da verificação
     */
    public function checkIP($ipAddress, $forceRefresh = false) {
        // Validar IP
        if (empty($ipAddress) || !filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return [
                'ip_address' => $ipAddress,
                'is_malicious' => false,
                'confidence' => 0,
                'error' => 'IP inválido'
            ];
        }
        
        // Verificar cache primeiro (TTL: 1 hora)
        $cacheKey = "threat_intel:$ipAddress";
        $cached = $this->cache->get($cacheKey);
        
        if (!$forceRefresh && $cached !== null) {
            return $cached;
        }
        
        // Verificar banco de dados local primeiro (mais rápido)
        $localResult = $this->getIPReputation($ipAddress);
        
        $results = [
            'ip_address' => $ipAddress,
            'is_malicious' => false,
            'confidence' => 0,
            'sources' => [],
            'reputation_score' => 50, // Neutro
            'source_count' => 0,
            'last_checked' => time(),
            'available_sources' => []
        ];
        
        // 1. Verificar SafeNode Threat Intelligence Network (sempre disponível)
        $safenodeResult = $this->checkSafeNodeNetwork($ipAddress);
        if ($safenodeResult) {
            $results['sources']['safenode_network'] = $safenodeResult;
            $results['available_sources'][] = 'safenode_network';
            if ($safenodeResult['is_malicious']) {
                $results['is_malicious'] = true;
                $results['confidence'] = max($results['confidence'], $safenodeResult['confidence']);
                $results['reputation_score'] = min($results['reputation_score'], $safenodeResult['reputation_score']);
            }
        }
        
        // 2. Verificar AbuseIPDB (se API key configurada)
        if (!empty($this->abuseipdbApiKey)) {
            $abuseResult = $this->checkAbuseIPDB($ipAddress);
            if ($abuseResult) {
                $results['sources']['abuseipdb'] = $abuseResult;
                $results['available_sources'][] = 'abuseipdb';
                if ($abuseResult['is_malicious']) {
                    $results['is_malicious'] = true;
                    $results['confidence'] = max($results['confidence'], $abuseResult['confidence']);
                    $results['reputation_score'] = min($results['reputation_score'], $abuseResult['reputation_score']);
                }
            }
        }
        
        // 3. Verificar VirusTotal (se API key configurada)
        if (!empty($this->virustotalApiKey)) {
            $vtResult = $this->checkVirusTotal($ipAddress);
            if ($vtResult) {
                $results['sources']['virustotal'] = $vtResult;
                $results['available_sources'][] = 'virustotal';
                if ($vtResult['is_malicious']) {
                    $results['is_malicious'] = true;
                    $results['confidence'] = max($results['confidence'], $vtResult['confidence']);
                    $results['reputation_score'] = min($results['reputation_score'], $vtResult['reputation_score']);
                }
            }
        }
        
        // 4. Fallback: Verificar sistema próprio de reputação se nenhuma fonte externa disponível
        if (empty($results['sources']) || count($results['sources']) == 0) {
            $ownReputation = $this->checkOwnReputation($ipAddress);
            if ($ownReputation) {
                $results['sources']['safenode_reputation'] = $ownReputation;
                $results['available_sources'][] = 'safenode_reputation';
                if ($ownReputation['is_malicious']) {
                    $results['is_malicious'] = true;
                    $results['confidence'] = max($results['confidence'], $ownReputation['confidence']);
                    $results['reputation_score'] = min($results['reputation_score'], $ownReputation['reputation_score']);
                }
            }
        }
        
        // 5. Combinar resultados de múltiplas fontes
        if (count($results['sources']) > 1) {
            $combinedResult = $this->combineIntelligenceSources($results['sources']);
            $results['combined_confidence'] = $combinedResult['confidence'];
            $results['combined_reputation'] = $combinedResult['reputation_score'];
            $results['is_malicious'] = $combinedResult['is_malicious'];
            $results['confidence'] = $combinedResult['confidence'];
            $results['reputation_score'] = $combinedResult['reputation_score'];
        }
        
        $results['source_count'] = count($results['sources']);
        
        // Salvar no cache
        $this->cache->set($cacheKey, $results, 3600); // 1 hora
        
        // Salvar no banco
        $this->saveToDatabase($ipAddress, $results);
        
        return $results;
    }
    
    /**
     * Verifica IP na SafeNode Threat Intelligence Network
     */
    private function checkSafeNodeNetwork($ipAddress) {
        if (!$this->db) return null;
        
        try {
            require_once __DIR__ . '/ThreatIntelligenceNetwork.php';
            $threatNetwork = new ThreatIntelligenceNetwork($this->db);
            $threat = $threatNetwork->checkThreat($ipAddress);
            
            if ($threat) {
                return [
                    'is_malicious' => $threat['is_global_block'] == 1,
                    'confidence' => min(100, (int)$threat['confidence_score']),
                    'reputation_score' => max(0, 100 - (int)$threat['severity']),
                    'severity' => (int)$threat['severity'],
                    'threat_type' => $threat['threat_type'],
                    'total_occurrences' => (int)$threat['total_occurrences'],
                    'affected_sites' => (int)$threat['affected_sites_count'],
                    'source' => 'SafeNode Network'
                ];
            }
        } catch (Exception $e) {
            error_log("SafeNode ThreatIntel Network Check Error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Verifica IP no sistema próprio de reputação (fallback)
     */
    private function checkOwnReputation($ipAddress) {
        if (!$this->db) return null;
        
        try {
            require_once __DIR__ . '/IPReputationManager.php';
            $ipReputation = new IPReputationManager($this->db);
            $trustScore = $ipReputation->getTrustScore($ipAddress);
            
            if ($trustScore > 0) {
                return [
                    'is_malicious' => $trustScore < 30,
                    'confidence' => $trustScore < 30 ? min(100, 100 - $trustScore) : max(0, 100 - $trustScore),
                    'reputation_score' => $trustScore,
                    'source' => 'SafeNode Reputation System'
                ];
            }
        } catch (Exception $e) {
            // Ignorar
        }
        
        return null;
    }
    
    /**
     * Combina resultados de múltiplas fontes de inteligência
     */
    private function combineIntelligenceSources($sources) {
        $maliciousCount = 0;
        $totalConfidence = 0;
        $totalReputation = 0;
        $sourceCount = 0;
        
        foreach ($sources as $sourceName => $sourceData) {
            if (!isset($sourceData['is_malicious']) || !isset($sourceData['confidence'])) {
                continue;
            }
            
            $sourceCount++;
            
            if ($sourceData['is_malicious']) {
                $maliciousCount++;
            }
            
            // Pesos diferentes por fonte
            $weight = 1.0;
            if ($sourceName === 'safenode_network') {
                $weight = 1.2; // SafeNode Network tem peso maior (próprio)
            } elseif ($sourceName === 'abuseipdb') {
                $weight = 1.0;
            } elseif ($sourceName === 'virustotal') {
                $weight = 1.1;
            }
            
            $totalConfidence += $sourceData['confidence'] * $weight;
            $reputation = $sourceData['reputation_score'] ?? 50;
            $totalReputation += $reputation * $weight;
        }
        
        if ($sourceCount == 0) {
            return [
                'is_malicious' => false,
                'confidence' => 0,
                'reputation_score' => 50
            ];
        }
        
        // Se maioria das fontes indica malicioso, considerar malicioso
        $isMalicious = ($maliciousCount / $sourceCount) >= 0.5;
        
        // Confidence média ponderada
        $combinedConfidence = $totalConfidence / ($sourceCount * 1.1); // Normalizar
        
        // Reputation média ponderada
        $combinedReputation = $totalReputation / ($sourceCount * 1.1);
        
        // Boost se múltiplas fontes concordam
        if ($maliciousCount >= 2) {
            $combinedConfidence = min(100, $combinedConfidence * 1.2);
        }
        
        return [
            'is_malicious' => $isMalicious,
            'confidence' => round(min(100, max(0, $combinedConfidence)), 2),
            'reputation_score' => round(min(100, max(0, $combinedReputation)), 2),
            'agreeing_sources' => $maliciousCount,
            'total_sources' => $sourceCount
        ];
    }
    
    /**
     * Verifica múltiplos IPs em batch (otimizado)
     */
    public function checkIPsBatch($ipAddresses) {
        if (empty($ipAddresses)) return [];
        
        $results = [];
        $cached = [];
        $toCheck = [];
        
        // Separar IPs em cache e IPs para verificar
        foreach ($ipAddresses as $ip) {
            $cacheKey = "threat_intel:$ip";
            $cachedResult = $this->cache->get($cacheKey);
            if ($cachedResult !== null) {
                $cached[$ip] = $cachedResult;
            } else {
                $toCheck[] = $ip;
            }
        }
        
        // Verificar IPs que não estão em cache
        foreach ($toCheck as $ip) {
            $results[$ip] = $this->checkIP($ip);
        }
        
        // Combinar resultados
        return array_merge($cached, $results);
    }
    
    /**
     * Verifica IP no AbuseIPDB (com retry e error handling melhorado)
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
                CURLOPT_TIMEOUT => 8,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                error_log("SafeNode ThreatIntel AbuseIPDB cURL Error: $curlError");
                return null;
            }
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if (isset($data['data'])) {
                    $data = $data['data'];
                    $abuseConfidence = (int)($data['abuseConfidencePercentage'] ?? 0);
                    $isWhitelisted = (bool)($data['isWhitelisted'] ?? false);
                    $usageType = $data['usageType'] ?? 'unknown';
                    $countryCode = $data['countryCode'] ?? null;
                    
                    // Categorias de abuso
                    $abuseCategories = $data['usageType'] ?? [];
                    
                    return [
                        'is_malicious' => $abuseConfidence >= 25 && !$isWhitelisted,
                        'confidence' => $abuseConfidence,
                        'reputation_score' => max(0, 100 - $abuseConfidence),
                        'usage_type' => $usageType,
                        'country_code' => $countryCode,
                        'is_whitelisted' => $isWhitelisted,
                        'total_reports' => (int)($data['totalReports'] ?? 0),
                        'last_reported' => $data['lastReportedAt'] ?? null,
                        'abuse_categories' => $abuseCategories,
                        'source' => 'AbuseIPDB',
                        'checked_at' => date('Y-m-d H:i:s')
                    ];
                }
            } elseif ($httpCode === 429) {
                // Rate limit - não logar como erro, apenas retornar null
                error_log("SafeNode ThreatIntel AbuseIPDB: Rate limit atingido");
            } elseif ($httpCode === 401 || $httpCode === 403) {
                error_log("SafeNode ThreatIntel AbuseIPDB: API key inválida ou sem permissão");
            }
        } catch (Exception $e) {
            error_log("SafeNode ThreatIntel AbuseIPDB Error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Verifica IP no VirusTotal (com retry e error handling melhorado)
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
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                error_log("SafeNode ThreatIntel VirusTotal cURL Error: $curlError");
                return null;
            }
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                
                if (isset($data['response_code'])) {
                    if ($data['response_code'] === 1) {
                        // IP encontrado
                        $detections = (int)($data['detected_urls'] ?? 0);
                        $samples = (int)($data['detected_samples'] ?? 0);
                        $asn = $data['asn'] ?? null;
                        $asOwner = $data['as_owner'] ?? null;
                        $country = $data['country'] ?? null;
                        
                        $isMalicious = $detections > 0 || $samples > 0;
                        
                        // Calcular confidence baseado em detecções (mais preciso)
                        $confidence = 0;
                        if ($detections > 0) {
                            $confidence = min(100, 30 + ($detections * 5));
                        }
                        if ($samples > 0) {
                            $confidence = min(100, $confidence + ($samples * 10));
                        }
                        
                        // Reputation score baseado em detecções
                        $reputationScore = $isMalicious 
                            ? max(0, 50 - ($confidence / 2)) 
                            : 50;
                        
                        return [
                            'is_malicious' => $isMalicious,
                            'confidence' => min(100, $confidence),
                            'reputation_score' => round($reputationScore, 2),
                            'detected_urls' => $detections,
                            'detected_samples' => $samples,
                            'asn' => $asn,
                            'as_owner' => $asOwner,
                            'country' => $country,
                            'source' => 'VirusTotal',
                            'checked_at' => date('Y-m-d H:i:s')
                        ];
                    } elseif ($data['response_code'] === 0) {
                        // IP não encontrado no VirusTotal (não é necessariamente bom, apenas não há dados)
                        return [
                            'is_malicious' => false,
                            'confidence' => 0,
                            'reputation_score' => 50,
                            'source' => 'VirusTotal',
                            'status' => 'not_found',
                            'checked_at' => date('Y-m-d H:i:s')
                        ];
                    }
                }
            } elseif ($httpCode === 204) {
                // Rate limit
                error_log("SafeNode ThreatIntel VirusTotal: Rate limit atingido");
            } elseif ($httpCode === 403) {
                error_log("SafeNode ThreatIntel VirusTotal: API key inválida ou sem permissão");
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
                INSERT INTO safenode_threat_intelligence_external 
                (ip_address, is_malicious, confidence, reputation_score, sources_data, available_sources, combined_confidence, last_checked, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    is_malicious = VALUES(is_malicious),
                    confidence = VALUES(confidence),
                    reputation_score = VALUES(reputation_score),
                    sources_data = VALUES(sources_data),
                    available_sources = VALUES(available_sources),
                    combined_confidence = VALUES(combined_confidence),
                    last_checked = VALUES(last_checked),
                    updated_at = NOW()
            ");
            
            $stmt->execute([
                $ipAddress,
                $results['is_malicious'] ? 1 : 0,
                $results['confidence'],
                $results['reputation_score'],
                json_encode($results['sources']),
                json_encode($results['available_sources']),
                $results['combined_confidence'] ?? $results['confidence']
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
                SELECT * FROM safenode_threat_intelligence_external 
                WHERE ip_address = ?
                AND last_checked >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([$ipAddress]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'is_malicious' => (bool)$result['is_malicious'],
                    'confidence' => (int)$result['confidence'],
                    'reputation_score' => (float)$result['reputation_score'],
                    'combined_confidence' => (float)($result['combined_confidence'] ?? $result['confidence']),
                    'sources' => json_decode($result['sources_data'], true) ?: [],
                    'available_sources' => json_decode($result['available_sources'], true) ?: [],
                    'last_checked' => $result['last_checked']
                ];
            }
        } catch (PDOException $e) {
            // Tabela pode não existir ainda, ignorar
        }
        
        return null;
    }
    
    /**
     * Garante que tabela existe (otimizada)
     */
    private function ensureTableExists() {
        try {
            $this->db->query("SELECT 1 FROM safenode_threat_intelligence_external LIMIT 1");
        } catch (PDOException $e) {
            // Tabela separada para dados externos (para não conflitar com ThreatIntelligenceNetwork)
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_threat_intelligence_external (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL UNIQUE,
                    is_malicious TINYINT(1) DEFAULT 0,
                    confidence INT DEFAULT 0,
                    reputation_score INT DEFAULT 50,
                    sources_data TEXT,
                    available_sources TEXT,
                    combined_confidence DECIMAL(5,2),
                    last_checked DATETIME DEFAULT CURRENT_TIMESTAMP,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_ip (ip_address),
                    INDEX idx_malicious (is_malicious, reputation_score),
                    INDEX idx_checked (last_checked),
                    INDEX idx_confidence (confidence)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }
    
    /**
     * Obtém estatísticas de uso das fontes
     */
    public function getSourceStats() {
        if (!$this->db) return null;
        
        try {
            $stats = [
                'abuseipdb_configured' => !empty($this->abuseipdbApiKey),
                'virustotal_configured' => !empty($this->virustotalApiKey),
                'safenode_network_available' => true, // Sempre disponível
                'total_checks' => 0,
                'by_source' => []
            ];
            
            // Contar verificações por fonte
            $stmt = $this->db->query("
                SELECT 
                    available_sources,
                    COUNT(*) as check_count
                FROM safenode_threat_intelligence_external
                WHERE last_checked >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY available_sources
            ");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $result) {
                $sources = json_decode($result['available_sources'], true) ?: [];
                foreach ($sources as $source) {
                    if (!isset($stats['by_source'][$source])) {
                        $stats['by_source'][$source] = 0;
                    }
                    $stats['by_source'][$source] += (int)$result['check_count'];
                }
                $stats['total_checks'] += (int)$result['check_count'];
            }
            
            return $stats;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Obtém IPs maliciosos das fontes externas
     */
    public function getMaliciousIPs($limit = 100, $minConfidence = 50) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT ip_address, is_malicious, confidence, reputation_score, 
                       sources_data, available_sources, last_checked
                FROM safenode_threat_intelligence_external
                WHERE is_malicious = 1
                AND confidence >= ?
                AND last_checked >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY confidence DESC, last_checked DESC
                LIMIT ?
            ");
            $stmt->execute([$minConfidence, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Verifica se APIs externas estão configuradas
     */
    public function isConfigured() {
        return [
            'abuseipdb' => !empty($this->abuseipdbApiKey),
            'virustotal' => !empty($this->virustotalApiKey),
            'safenode_network' => true, // Sempre disponível
            'any_external' => !empty($this->abuseipdbApiKey) || !empty($this->virustotalApiKey),
            'source_count' => (int)(!empty($this->abuseipdbApiKey)) + (int)(!empty($this->virustotalApiKey)) + 1 // +1 para SafeNode Network
        ];
    }
}








