<?php
/**
 * SafeNode - Security Test Controller
 * Sistema de testes de segurança controlados e autorizados
 * Executa testes reais de segurança com requisições HTTP reais
 */

class SecurityTestSimulator {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Cria um novo teste de segurança
     */
    public function createTest($siteId, $userId, $testName, $testType, $targetUrl, $testConfig = []) {
        if (!$this->db) return false;
        
        try {
            // Gerar token de autorização único
            $authToken = bin2hex(random_bytes(32));
            
            $stmt = $this->db->prepare("
                INSERT INTO safenode_security_tests
                (site_id, user_id, test_name, test_type, target_url, status,
                 authorization_token, test_config, created_at)
                VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, NOW())
            ");
            $stmt->execute([
                $siteId,
                $userId,
                $testName,
                $testType,
                $targetUrl,
                $authToken,
                json_encode($testConfig)
            ]);
            
            $testId = $this->db->lastInsertId();
            
            return [
                'test_id' => $testId,
                'authorization_token' => $authToken
            ];
        } catch (PDOException $e) {
            error_log("SecurityTestSimulator CreateTest Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Autoriza um teste (aceita termos e verifica domínio)
     */
    public function authorizeTest($testId, $authToken, $termsAccepted = false, $domainVerified = false) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, s.domain, s.verification_token, s.verification_status
                FROM safenode_security_tests t
                JOIN safenode_sites s ON t.site_id = s.id
                WHERE t.id = ? AND t.authorization_token = ? AND t.status = 'pending'
            ");
            $stmt->execute([$testId, $authToken]);
            $test = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$test) {
                return ['success' => false, 'message' => 'Teste não encontrado ou já autorizado'];
            }
            
            if (!$termsAccepted) {
                return ['success' => false, 'message' => 'Termos devem ser aceitos'];
            }
            
            // Verificar propriedade do domínio REAL
            $verified = false;
            $verificationMethod = null;
            
            if ($test['verification_status'] === 'verified') {
                // Site já verificado anteriormente
                $verified = true;
                $verificationMethod = 'previously_verified';
            } else {
                // Tentar verificar agora
                $domain = $test['domain'];
                $token = $test['verification_token'];
                
                // Método 1: DNS TXT
                $dnsRecords = @dns_get_record($domain, DNS_TXT);
                if ($dnsRecords !== false && is_array($dnsRecords)) {
                    foreach ($dnsRecords as $record) {
                        $txtValue = $record['txt'] ?? '';
                        $expectedValue = "safenode-verification=$token";
                        
                        if (strpos($txtValue, $expectedValue) !== false || 
                            trim($txtValue) === $expectedValue || 
                            trim($txtValue) === $token) {
                            $verified = true;
                            $verificationMethod = 'dns';
                            break;
                        }
                    }
                }
                
                // Método 2: Arquivo HTTP (se DNS não funcionou)
                if (!$verified) {
                    $urls = [
                        "http://$domain/safenode-verification.txt",
                        "https://$domain/safenode-verification.txt"
                    ];
                    
                    foreach ($urls as $url) {
                        $context = stream_context_create([
                            'http' => [
                                'timeout' => 5,
                                'user_agent' => 'SafeNode-Verification/1.0',
                                'follow_location' => true,
                                'max_redirects' => 3
                            ]
                        ]);
                        
                        $content = @file_get_contents($url, false, $context);
                        if ($content !== false) {
                            $content = trim($content);
                            if ($content === $token || $content === "safenode-verification=$token") {
                                $verified = true;
                                $verificationMethod = 'file';
                                break;
                            }
                        }
                    }
                }
            }
            
            $stmt = $this->db->prepare("
                UPDATE safenode_security_tests
                SET status = 'authorized',
                    terms_accepted = 1,
                    domain_verified = ?,
                    domain_verification_method = ?,
                    domain_verified_at = ?,
                    authorization_accepted_at = NOW(),
                    authorization_ip = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $verified ? 1 : 0,
                $verificationMethod,
                $verified ? date('Y-m-d H:i:s') : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $testId
            ]);
            
            if (!$verified) {
                return ['success' => false, 'message' => 'Domínio não verificado. Configure o token de verificação no DNS ou arquivo.'];
            }
            
            return ['success' => true, 'message' => 'Teste autorizado com sucesso', 'verification_method' => $verificationMethod];
        } catch (PDOException $e) {
            error_log("SecurityTestSimulator AuthorizeTest Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao autorizar teste'];
        }
    }
    
    /**
     * Executa um teste de segurança
     */
    public function runTest($testId) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_security_tests
                WHERE id = ? AND status = 'authorized'
            ");
            $stmt->execute([$testId]);
            $test = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$test) {
                return ['success' => false, 'message' => 'Teste não encontrado ou não autorizado'];
            }
            
            // Atualizar status para running
            $stmt = $this->db->prepare("
                UPDATE safenode_security_tests
                SET status = 'running', started_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$testId]);
            
            $testConfig = json_decode($test['test_config'], true);
            $startTime = microtime(true);
            
            // Executar teste baseado no tipo
            $results = $this->executeTestType($test['test_type'], $test['target_url'], $testConfig);
            
            $endTime = microtime(true);
            $duration = (int)($endTime - $startTime);
            
            // Calcular estatísticas
            $totalRequests = count($results);
            $blockedRequests = count(array_filter($results, function($r) { return $r['was_blocked']; }));
            $allowedRequests = $totalRequests - $blockedRequests;
            $falsePositives = count(array_filter($results, function($r) { 
                return $r['expected_result'] === 'allow' && $r['was_blocked']; 
            }));
            $falseNegatives = count(array_filter($results, function($r) { 
                return $r['expected_result'] === 'block' && !$r['was_blocked']; 
            }));
            
            // Calcular score (0-100)
            $correctDetections = $totalRequests - $falsePositives - $falseNegatives;
            $testScore = $totalRequests > 0 
                ? (int)round(($correctDetections / $totalRequests) * 100)
                : 0;
            
            // Salvar resultados
            foreach ($results as $index => $result) {
                $this->saveTestResult($testId, $index + 1, $result);
            }
            
            // Atualizar teste
            $stmt = $this->db->prepare("
                UPDATE safenode_security_tests
                SET status = 'completed',
                    completed_at = NOW(),
                    duration_seconds = ?,
                    total_requests = ?,
                    blocked_requests = ?,
                    allowed_requests = ?,
                    false_positives = ?,
                    false_negatives = ?,
                    test_score = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $duration,
                $totalRequests,
                $blockedRequests,
                $allowedRequests,
                $falsePositives,
                $falseNegatives,
                $testScore,
                $testId
            ]);
            
            return [
                'success' => true,
                'test_id' => $testId,
                'score' => $testScore,
                'total_requests' => $totalRequests,
                'blocked' => $blockedRequests,
                'allowed' => $allowedRequests,
                'false_positives' => $falsePositives,
                'false_negatives' => $falseNegatives
            ];
        } catch (PDOException $e) {
            error_log("SecurityTestSimulator RunTest Error: " . $e->getMessage());
            
            // Marcar teste como falha
            if (isset($testId)) {
                $stmt = $this->db->prepare("
                    UPDATE safenode_security_tests
                    SET status = 'failed', completed_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$testId]);
            }
            
            return ['success' => false, 'message' => 'Erro ao executar teste'];
        }
    }
    
    /**
     * Executa teste baseado no tipo
     */
    private function executeTestType($testType, $targetUrl, $config) {
        $results = [];
        
        switch ($testType) {
            case 'brute_force':
                $results = $this->runBruteForceTest($targetUrl, $config);
                break;
                
            case 'rate_limit':
                $results = $this->runRateLimitTest($targetUrl, $config);
                break;
                
            case 'sql_injection':
                $results = $this->runSQLInjectionTest($targetUrl, $config);
                break;
                
            case 'xss':
                $results = $this->runXSSTest($targetUrl, $config);
                break;
                
            case 'bot_detection':
                $results = $this->runBotDetectionTest($targetUrl, $config);
                break;
                
            default:
                $results = [];
        }
        
        return $results;
    }
    
    /**
     * Teste de força bruta
     */
    private function runBruteForceTest($targetUrl, $config) {
        $results = [];
        $payloads = $config['payloads'] ?? ['admin', 'password', '123456'];
        $maxAttempts = $config['max_attempts'] ?? 10;
        $delay = $config['delay_ms'] ?? 100;
        
        for ($i = 0; $i < min(count($payloads), $maxAttempts); $i++) {
            $payload = $payloads[$i];
            $result = $this->makeTestRequest($targetUrl, 'POST', ['username' => $payload, 'password' => $payload]);
            
            $results[] = [
                'request_type' => 'attack',
                'request_url' => $targetUrl,
                'request_method' => 'POST',
                'request_payload' => json_encode(['username' => $payload, 'password' => $payload]),
                'response_code' => $result['code'],
                'response_time_ms' => $result['time'],
                'was_blocked' => $result['blocked'],
                'block_reason' => $result['block_reason'],
                'threat_detected' => $result['threat_detected'],
                'threat_type' => $result['threat_type'],
                'threat_score' => $result['threat_score'],
                'expected_result' => 'block',
                'actual_result' => $result['blocked'] ? 'blocked' : 'allowed',
                'is_correct' => $result['blocked'] ? 1 : 0
            ];
            
            usleep($delay * 1000); // Delay entre requisições
        }
        
        return $results;
    }
    
    /**
     * Teste de rate limit
     */
    private function runRateLimitTest($targetUrl, $config) {
        $results = [];
        $requestsPerSecond = $config['requests_per_second'] ?? 100;
        $duration = $config['duration_seconds'] ?? 60;
        $totalRequests = $requestsPerSecond * $duration;
        
        // Limitar para não sobrecarregar
        $totalRequests = min($totalRequests, 1000);
        
        for ($i = 0; $i < $totalRequests; $i++) {
            $result = $this->makeTestRequest($targetUrl, 'GET');
            
            $results[] = [
                'request_type' => 'rate_test',
                'request_url' => $targetUrl,
                'request_method' => 'GET',
                'request_payload' => null,
                'response_code' => $result['code'],
                'response_time_ms' => $result['time'],
                'was_blocked' => $result['blocked'],
                'block_reason' => $result['block_reason'],
                'threat_detected' => false,
                'threat_type' => null,
                'threat_score' => 0,
                'expected_result' => $i < ($requestsPerSecond * 1) ? 'allow' : 'block', // Espera bloqueio após 1 segundo
                'actual_result' => $result['blocked'] ? 'blocked' : 'allowed',
                'is_correct' => null // Rate limit é contextual
            ];
            
            usleep(1000000 / $requestsPerSecond); // Delay para manter taxa
        }
        
        return $results;
    }
    
    /**
     * Teste de SQL Injection
     */
    private function runSQLInjectionTest($targetUrl, $config) {
        $results = [];
        $payloads = $config['payloads'] ?? ["' OR '1'='1", "UNION SELECT", "DROP TABLE"];
        
        foreach ($payloads as $payload) {
            $result = $this->makeTestRequest($targetUrl, 'GET', ['id' => $payload]);
            
            $results[] = [
                'request_type' => 'attack',
                'request_url' => $targetUrl . '?id=' . urlencode($payload),
                'request_method' => 'GET',
                'request_payload' => json_encode(['id' => $payload]),
                'response_code' => $result['code'],
                'response_time_ms' => $result['time'],
                'was_blocked' => $result['blocked'],
                'block_reason' => $result['block_reason'],
                'threat_detected' => $result['threat_detected'],
                'threat_type' => $result['threat_type'] ?? 'sql_injection',
                'threat_score' => $result['threat_score'],
                'expected_result' => 'block',
                'actual_result' => $result['blocked'] ? 'blocked' : 'allowed',
                'is_correct' => $result['blocked'] ? 1 : 0
            ];
        }
        
        return $results;
    }
    
    /**
     * Teste de XSS
     */
    private function runXSSTest($targetUrl, $config) {
        $results = [];
        $payloads = $config['payloads'] ?? ['<script>alert(1)</script>', '<img src=x onerror=alert(1)>'];
        
        foreach ($payloads as $payload) {
            $result = $this->makeTestRequest($targetUrl, 'GET', ['q' => $payload]);
            
            $results[] = [
                'request_type' => 'attack',
                'request_url' => $targetUrl . '?q=' . urlencode($payload),
                'request_method' => 'GET',
                'request_payload' => json_encode(['q' => $payload]),
                'response_code' => $result['code'],
                'response_time_ms' => $result['time'],
                'was_blocked' => $result['blocked'],
                'block_reason' => $result['block_reason'],
                'threat_detected' => $result['threat_detected'],
                'threat_type' => $result['threat_type'] ?? 'xss',
                'threat_score' => $result['threat_score'],
                'expected_result' => 'block',
                'actual_result' => $result['blocked'] ? 'blocked' : 'allowed',
                'is_correct' => $result['blocked'] ? 1 : 0
            ];
        }
        
        return $results;
    }
    
    /**
     * Teste de detecção de bots
     */
    private function runBotDetectionTest($targetUrl, $config) {
        $results = [];
        $userAgents = $config['user_agents'] ?? ['bot', 'crawler', 'spider'];
        
        foreach ($userAgents as $ua) {
            $result = $this->makeTestRequest($targetUrl, 'GET', [], ['User-Agent' => $ua]);
            
            $results[] = [
                'request_type' => 'probe',
                'request_url' => $targetUrl,
                'request_method' => 'GET',
                'request_payload' => null,
                'response_code' => $result['code'],
                'response_time_ms' => $result['time'],
                'was_blocked' => $result['blocked'],
                'block_reason' => $result['block_reason'],
                'threat_detected' => $result['threat_detected'],
                'threat_type' => $result['threat_type'] ?? 'bot',
                'threat_score' => $result['threat_score'],
                'expected_result' => 'block',
                'actual_result' => $result['blocked'] ? 'blocked' : 'allowed',
                'is_correct' => $result['blocked'] ? 1 : 0
            ];
        }
        
        return $results;
    }
    
    /**
     * Faz uma requisição de teste real via HTTP
     */
    private function makeTestRequest($url, $method = 'GET', $params = [], $customHeaders = []) {
        if (!function_exists('curl_init')) {
            error_log("SecurityTestSimulator: cURL não disponível");
            return [
                'code' => 0,
                'time' => 0,
                'blocked' => false,
                'block_reason' => 'cURL não disponível',
                'threat_detected' => false,
                'threat_type' => null,
                'threat_score' => 0
            ];
        }
        
        $startTime = microtime(true);
        
        // Construir URL com parâmetros se GET
        if ($method === 'GET' && !empty($params)) {
            $queryString = http_build_query($params);
            $url .= (strpos($url, '?') !== false ? '&' : '?') . $queryString;
        }
        
        $ch = curl_init($url);
        
        // Headers padrão
        $headers = array_merge([
            'User-Agent: SafeNode-SecurityTest/1.0',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8'
        ], $customHeaders);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method
        ]);
        
        // Adicionar body se POST
        if ($method === 'POST' && !empty($params)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // ms
        
        // Determinar se foi bloqueado baseado no código HTTP
        $blocked = false;
        $blockReason = null;
        $threatDetected = false;
        $threatType = null;
        $threatScore = 0;
        
        // Códigos que indicam bloqueio
        if ($httpCode === 403 || $httpCode === 429) {
            $blocked = true;
            $blockReason = $httpCode === 403 ? 'Acesso negado (403)' : 'Rate limit (429)';
            $threatDetected = true;
            $threatScore = 80;
        } elseif ($httpCode >= 400 && $httpCode < 500) {
            $blocked = true;
            $blockReason = "Erro HTTP {$httpCode}";
        }
        
        // Analisar resposta para detectar ameaças (se não foi bloqueado)
        if (!$blocked && $response) {
            // Verificar se há indicadores de bloqueio na resposta
            $responseLower = strtolower($response);
            if (stripos($responseLower, 'blocked') !== false || 
                stripos($responseLower, 'forbidden') !== false ||
                stripos($responseLower, 'access denied') !== false) {
                $blocked = true;
                $blockReason = 'Conteúdo indica bloqueio';
                $threatDetected = true;
                $threatScore = 70;
            }
        }
        
        // Analisar payload para determinar tipo de ameaça esperado
        $allParams = json_encode($params);
        if (stripos($allParams, 'script') !== false || stripos($allParams, '<') !== false) {
            $threatType = 'xss';
        } elseif (stripos($allParams, 'union') !== false || stripos($allParams, 'drop') !== false || stripos($allParams, 'select') !== false) {
            $threatType = 'sql_injection';
        }
        
        return [
            'code' => $httpCode,
            'time' => round($responseTime, 2),
            'blocked' => $blocked,
            'block_reason' => $blockReason,
            'threat_detected' => $threatDetected,
            'threat_type' => $threatType,
            'threat_score' => $threatScore
        ];
    }
    
    /**
     * Salva resultado de uma requisição de teste
     */
    private function saveTestResult($testId, $requestNumber, $result) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO safenode_test_results
                (test_id, request_number, request_type, request_url, request_method,
                 request_payload, response_code, response_time_ms, was_blocked,
                 block_reason, threat_detected, threat_type, threat_score,
                 expected_result, actual_result, is_correct, details)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $testId,
                $requestNumber,
                $result['request_type'],
                $result['request_url'],
                $result['request_method'],
                $result['request_payload'],
                $result['response_code'],
                $result['response_time_ms'],
                $result['was_blocked'] ? 1 : 0,
                $result['block_reason'],
                $result['threat_detected'] ? 1 : 0,
                $result['threat_type'],
                $result['threat_score'],
                $result['expected_result'],
                $result['actual_result'],
                $result['is_correct'],
                json_encode($result)
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("SecurityTestSimulator SaveResult Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém resultados de um teste
     */
    public function getTestResults($testId) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_test_results
                WHERE test_id = ?
                ORDER BY request_number ASC
            ");
            $stmt->execute([$testId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SecurityTestSimulator GetResults Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Gera relatório de teste
     */
    public function generateReport($testId) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_security_tests
                WHERE id = ?
            ");
            $stmt->execute([$testId]);
            $test = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$test) return false;
            
            $results = $this->getTestResults($testId);
            
            // Gerar relatório HTML/PDF (implementar conforme necessário)
            $report = [
                'test' => $test,
                'results' => $results,
                'summary' => [
                    'total_requests' => $test['total_requests'],
                    'blocked' => $test['blocked_requests'],
                    'allowed' => $test['allowed_requests'],
                    'false_positives' => $test['false_positives'],
                    'false_negatives' => $test['false_negatives'],
                    'score' => $test['test_score']
                ]
            ];
            
            // Salvar caminho do relatório (implementar geração real)
            $reportPath = "reports/test_{$testId}_" . date('Y-m-d_H-i-s') . ".json";
            
            $stmt = $this->db->prepare("
                UPDATE safenode_security_tests
                SET report_generated = 1, report_path = ?
                WHERE id = ?
            ");
            $stmt->execute([$reportPath, $testId]);
            
            return $report;
        } catch (PDOException $e) {
            error_log("SecurityTestSimulator GenerateReport Error: " . $e->getMessage());
            return false;
        }
    }
}

