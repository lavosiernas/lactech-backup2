<?php
/**
 * SafeNode - Security Advisor e Auto-Hardening
 * Sistema de auditoria automática e recomendações de segurança
 */

class SecurityAdvisor {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Executa auditoria completa de segurança para um site
     */
    public function runSecurityAudit($siteId) {
        if (!$this->db) return false;
        
        try {
            // Criar registro de auditoria
            $stmt = $this->db->prepare("
                INSERT INTO safenode_security_audits
                (site_id, audit_type, status, started_at)
                VALUES (?, 'full', 'running', NOW())
            ");
            $stmt->execute([$siteId]);
            $auditId = $this->db->lastInsertId();
            
            $totalChecks = 0;
            $passedChecks = 0;
            $failedChecks = 0;
            $warnings = 0;
            $scores = [];
            
            // 1. Auditoria de Headers de Segurança
            $headersResult = $this->auditSecurityHeaders($siteId, $auditId);
            $totalChecks += $headersResult['total'];
            $passedChecks += $headersResult['passed'];
            $failedChecks += $headersResult['failed'];
            $warnings += $headersResult['warnings'];
            $scores['headers'] = $headersResult['score'];
            
            // 2. Auditoria de Endpoints Sensíveis
            $endpointsResult = $this->auditSensitiveEndpoints($siteId, $auditId);
            $totalChecks += $endpointsResult['total'];
            $passedChecks += $endpointsResult['passed'];
            $failedChecks += $endpointsResult['failed'];
            $warnings += $endpointsResult['warnings'];
            $scores['endpoints'] = $endpointsResult['score'];
            
            // 3. Auditoria de Regras WAF
            $wafResult = $this->auditWAFRules($siteId, $auditId);
            $totalChecks += $wafResult['total'];
            $passedChecks += $wafResult['passed'];
            $failedChecks += $wafResult['failed'];
            $warnings += $wafResult['warnings'];
            $scores['waf'] = $wafResult['score'];
            
            // 4. Auditoria de Rate Limiting
            $rateLimitResult = $this->auditRateLimiting($siteId, $auditId);
            $totalChecks += $rateLimitResult['total'];
            $passedChecks += $rateLimitResult['passed'];
            $failedChecks += $rateLimitResult['failed'];
            $warnings += $rateLimitResult['warnings'];
            $scores['rate_limit'] = $rateLimitResult['score'];
            
            // 5. Auditoria de Configurações Gerais
            $configResult = $this->auditGeneralConfig($siteId, $auditId);
            $totalChecks += $configResult['total'];
            $passedChecks += $configResult['passed'];
            $failedChecks += $configResult['failed'];
            $warnings += $configResult['warnings'];
            $scores['config'] = $configResult['score'];
            
            // Calcular score geral
            $overallScore = (int)round(array_sum($scores) / count($scores));
            
            // Atualizar auditoria
            $stmt = $this->db->prepare("
                UPDATE safenode_security_audits
                SET status = 'completed',
                    security_score = ?,
                    total_checks = ?,
                    passed_checks = ?,
                    failed_checks = ?,
                    warnings = ?,
                    completed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $overallScore,
                $totalChecks,
                $passedChecks,
                $failedChecks,
                $warnings,
                $auditId
            ]);
            
            // Salvar score de maturidade
            $this->saveMaturityScore($siteId, $overallScore, $scores);
            
            // Gerar recomendações
            $this->generateRecommendations($siteId, $auditId);
            
            return [
                'audit_id' => $auditId,
                'score' => $overallScore,
                'scores' => $scores,
                'total_checks' => $totalChecks,
                'passed' => $passedChecks,
                'failed' => $failedChecks,
                'warnings' => $warnings
            ];
        } catch (PDOException $e) {
            error_log("SecurityAdvisor Audit Error: " . $e->getMessage());
            
            // Marcar auditoria como falha
            if (isset($auditId)) {
                $stmt = $this->db->prepare("
                    UPDATE safenode_security_audits
                    SET status = 'failed', completed_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$auditId]);
            }
            
            return false;
        }
    }
    
    /**
     * Auditoria de Headers de Segurança
     */
    private function auditSecurityHeaders($siteId, $auditId) {
        $checks = [
            ['name' => 'X-Frame-Options', 'required' => true, 'recommended' => 'DENY', 'category' => 'headers'],
            ['name' => 'X-Content-Type-Options', 'required' => true, 'recommended' => 'nosniff', 'category' => 'headers'],
            ['name' => 'X-XSS-Protection', 'required' => false, 'recommended' => '1; mode=block', 'category' => 'headers'],
            ['name' => 'Strict-Transport-Security', 'required' => true, 'recommended' => 'max-age=31536000', 'category' => 'headers'],
            ['name' => 'Content-Security-Policy', 'required' => false, 'recommended' => 'default-src \'self\'', 'category' => 'headers'],
            ['name' => 'Referrer-Policy', 'required' => false, 'recommended' => 'strict-origin-when-cross-origin', 'category' => 'headers'],
            ['name' => 'Permissions-Policy', 'required' => false, 'recommended' => 'geolocation=(), microphone=()', 'category' => 'headers']
        ];
        
        // Buscar site
        $stmt = $this->db->prepare("SELECT * FROM safenode_sites WHERE id = ?");
        $stmt->execute([$siteId]);
        $site = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$site) {
            return ['total' => 0, 'passed' => 0, 'failed' => 0, 'warnings' => 0, 'score' => 0];
        }
        
        // Fazer requisição HTTP real para verificar headers
        $domain = $site['domain'];
        $url = (strpos($domain, 'http') === 0 ? $domain : 'https://' . $domain);
        
        $headers = $this->fetchHeaders($url);
        
        $passed = 0;
        $failed = 0;
        $warnings = 0;
        
        foreach ($checks as $check) {
            $headerName = $check['name'];
            $headerValue = $headers[$headerName] ?? null;
            
            $status = 'pass';
            $severity = 'low';
            $currentValue = $headerValue ?? 'Não configurado';
            
            if (!$headerValue) {
                if ($check['required']) {
                    $status = 'fail';
                    $severity = 'high';
                    $failed++;
                } else {
                    $status = 'warning';
                    $severity = 'medium';
                    $warnings++;
                }
            } else {
                $passed++;
            }
            
            $this->saveAuditResult($auditId, $check['name'], $check['category'], $status, $severity, 
                $currentValue, $check['recommended'], 
                $headerValue ? "Header {$check['name']} configurado: {$headerValue}" : "Header de segurança {$check['name']} não configurado",
                $headerValue ? null : "Configure o header {$check['name']}: {$check['recommended']}",
                false);
        }
        
        $totalChecks = count($checks);
        $score = $totalChecks > 0 ? (int)round(($passed / $totalChecks) * 100) : 0;
        
        return [
            'total' => $totalChecks,
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => $warnings,
            'score' => $score
        ];
    }
    
    /**
     * Faz requisição HTTP real para obter headers
     */
    private function fetchHeaders($url) {
        $headers = [];
        
        if (!function_exists('curl_init')) {
            error_log("SecurityAdvisor: cURL não disponível");
            return $headers;
        }
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'SafeNode-SecurityAdvisor/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 400 && $response) {
            // Parse headers da resposta
            $headerLines = explode("\r\n", $response);
            foreach ($headerLines as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Normalizar nome do header
                    $normalizedKey = str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($key))));
                    $headers[$normalizedKey] = $value;
                }
            }
        }
        
        return $headers;
    }
    
    /**
     * Auditoria de Endpoints Sensíveis
     */
    private function auditSensitiveEndpoints($siteId, $auditId) {
        $sensitiveEndpoints = [
            '/login', '/admin', '/api', '/checkout', '/payment', '/reset-password'
        ];
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM safenode_endpoint_rules
            WHERE site_id = ? AND is_active = 1
        ");
        $stmt->execute([$siteId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $protectedCount = $result['count'] ?? 0;
        
        $total = count($sensitiveEndpoints);
        $protected = min($protectedCount, $total);
        $unprotected = $total - $protected;
        
        foreach ($sensitiveEndpoints as $endpoint) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM safenode_endpoint_rules
                WHERE site_id = ? AND endpoint_pattern = ? AND is_active = 1
            ");
            $stmt->execute([$siteId, $endpoint]);
            $hasRule = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
            
            $status = $hasRule ? 'pass' : 'warning';
            $severity = $hasRule ? 'low' : 'medium';
            
            $this->saveAuditResult($auditId, "Proteção: {$endpoint}", 'endpoints', $status, $severity,
                $hasRule ? 'Protegido' : 'Não protegido',
                'Proteção configurada',
                $hasRule ? "Endpoint {$endpoint} está protegido" : "Endpoint sensível {$endpoint} não possui regras de proteção",
                $hasRule ? null : "Configure regras de segurança para o endpoint {$endpoint}",
                true);
        }
        
        $score = $total > 0 ? (int)round(($protected / $total) * 100) : 0;
        
        return [
            'total' => $total,
            'passed' => $protected,
            'failed' => 0,
            'warnings' => $unprotected,
            'score' => $score
        ];
    }
    
    /**
     * Auditoria de Regras WAF
     */
    private function auditWAFRules($siteId, $auditId) {
        $stmt = $this->db->prepare("SELECT threat_detection_enabled FROM safenode_sites WHERE id = ?");
        $stmt->execute([$siteId]);
        $site = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $wafEnabled = $site['threat_detection_enabled'] ?? 0;
        
        $status = $wafEnabled ? 'pass' : 'fail';
        $severity = $wafEnabled ? 'low' : 'high';
        
        $this->saveAuditResult($auditId, 'WAF Habilitado', 'waf', $status, $severity,
            $wafEnabled ? 'Sim' : 'Não',
            'Sim',
            $wafEnabled ? 'WAF está habilitado' : 'WAF não está habilitado',
            $wafEnabled ? null : 'Habilite a detecção de ameaças (WAF) nas configurações do site',
            true);
        
        $score = $wafEnabled ? 100 : 0;
        
        return [
            'total' => 1,
            'passed' => $wafEnabled ? 1 : 0,
            'failed' => $wafEnabled ? 0 : 1,
            'warnings' => 0,
            'score' => $score
        ];
    }
    
    /**
     * Auditoria de Rate Limiting
     */
    private function auditRateLimiting($siteId, $auditId) {
        $stmt = $this->db->prepare("SELECT rate_limit_enabled FROM safenode_sites WHERE id = ?");
        $stmt->execute([$siteId]);
        $site = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $rateLimitEnabled = $site['rate_limit_enabled'] ?? 0;
        
        $status = $rateLimitEnabled ? 'pass' : 'warning';
        $severity = $rateLimitEnabled ? 'low' : 'medium';
        
        $this->saveAuditResult($auditId, 'Rate Limiting Habilitado', 'rate_limit', $status, $severity,
            $rateLimitEnabled ? 'Sim' : 'Não',
            'Sim',
            $rateLimitEnabled ? 'Rate limiting está habilitado' : 'Rate limiting não está habilitado',
            $rateLimitEnabled ? null : 'Habilite rate limiting nas configurações do site',
            true);
        
        $score = $rateLimitEnabled ? 100 : 50;
        
        return [
            'total' => 1,
            'passed' => $rateLimitEnabled ? 1 : 0,
            'failed' => 0,
            'warnings' => $rateLimitEnabled ? 0 : 1,
            'score' => $score
        ];
    }
    
    /**
     * Auditoria de Configurações Gerais
     */
    private function auditGeneralConfig($siteId, $auditId) {
        $stmt = $this->db->prepare("SELECT * FROM safenode_sites WHERE id = ?");
        $stmt->execute([$siteId]);
        $site = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$site) {
            return ['total' => 0, 'passed' => 0, 'failed' => 0, 'warnings' => 0, 'score' => 0];
        }
        
        $checks = [];
        
        // Verificar nível de segurança
        $securityLevel = $site['security_level'] ?? 'medium';
        $checks[] = [
            'name' => 'Nível de Segurança',
            'status' => in_array($securityLevel, ['high', 'under_attack']) ? 'pass' : 'warning',
            'severity' => in_array($securityLevel, ['high', 'under_attack']) ? 'low' : 'medium',
            'current' => $securityLevel,
            'recommended' => 'high'
        ];
        
        // Verificar auto-block
        $autoBlock = $site['auto_block'] ?? 0;
        $checks[] = [
            'name' => 'Bloqueio Automático',
            'status' => $autoBlock ? 'pass' : 'warning',
            'severity' => $autoBlock ? 'low' : 'medium',
            'current' => $autoBlock ? 'Habilitado' : 'Desabilitado',
            'recommended' => 'Habilitado'
        ];
        
        $passed = 0;
        $warnings = 0;
        
        foreach ($checks as $check) {
            if ($check['status'] === 'pass') $passed++;
            else $warnings++;
            
            $this->saveAuditResult($auditId, $check['name'], 'config', $check['status'], $check['severity'],
                $check['current'], $check['recommended'],
                "Configuração: {$check['name']} = {$check['current']}",
                $check['status'] === 'pass' ? null : "Configure {$check['name']} para: {$check['recommended']}",
                true);
        }
        
        $total = count($checks);
        $score = $total > 0 ? (int)round(($passed / $total) * 100) : 0;
        
        return [
            'total' => $total,
            'passed' => $passed,
            'failed' => 0,
            'warnings' => $warnings,
            'score' => $score
        ];
    }
    
    /**
     * Salva resultado de um checkpoint de auditoria
     */
    private function saveAuditResult($auditId, $checkName, $category, $status, $severity, 
                                     $currentValue, $recommendedValue, $description, $fixInstructions, $autoFixable) {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO safenode_audit_results
                (audit_id, check_name, check_category, status, severity, current_value,
                 recommended_value, description, fix_instructions, auto_fixable)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $auditId, $checkName, $category, $status, $severity,
                $currentValue, $recommendedValue, $description, $fixInstructions, $autoFixable
            ]);
        } catch (PDOException $e) {
            error_log("SecurityAdvisor SaveResult Error: " . $e->getMessage());
        }
    }
    
    /**
     * Salva score de maturidade de segurança
     */
    private function saveMaturityScore($siteId, $overallScore, $scores) {
        if (!$this->db) return;
        
        try {
            // Determinar nível de maturidade
            $maturityLevel = 'basic';
            if ($overallScore >= 80) $maturityLevel = 'expert';
            elseif ($overallScore >= 60) $maturityLevel = 'advanced';
            elseif ($overallScore >= 40) $maturityLevel = 'intermediate';
            
            $stmt = $this->db->prepare("
                INSERT INTO safenode_security_maturity
                (site_id, overall_score, headers_score, waf_score, rate_limit_score,
                 endpoint_protection_score, monitoring_score, maturity_level, measured_at)
                VALUES (?, ?, ?, ?, ?, ?, 0, ?, NOW())
            ");
            $stmt->execute([
                $siteId,
                $overallScore,
                $scores['headers'] ?? 0,
                $scores['waf'] ?? 0,
                $scores['rate_limit'] ?? 0,
                $scores['endpoints'] ?? 0,
                $maturityLevel
            ]);
        } catch (PDOException $e) {
            error_log("SecurityAdvisor SaveMaturity Error: " . $e->getMessage());
        }
    }
    
    /**
     * Gera recomendações baseadas na auditoria
     */
    private function generateRecommendations($siteId, $auditId) {
        if (!$this->db) return;
        
        try {
            // Buscar resultados que falharam ou têm warnings
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_audit_results
                WHERE audit_id = ? AND status IN ('fail', 'warning')
                ORDER BY 
                    CASE severity
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                    END,
                    status DESC
            ");
            $stmt->execute([$auditId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $result) {
                $priority = $result['severity'];
                if ($result['status'] === 'fail' && $result['severity'] === 'high') {
                    $priority = 'critical';
                }
                
                $stmt = $this->db->prepare("
                    INSERT INTO safenode_security_recommendations
                    (site_id, recommendation_type, title, description, priority, impact, effort, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([
                    $siteId,
                    $result['check_category'],
                    $result['check_name'],
                    $result['description'] . ($result['fix_instructions'] ? "\n\n" . $result['fix_instructions'] : ''),
                    $priority,
                    'Melhoria de segurança',
                    $result['auto_fixable'] ? 'low' : 'medium',
                ]);
            }
        } catch (PDOException $e) {
            error_log("SecurityAdvisor GenerateRecommendations Error: " . $e->getMessage());
        }
    }
    
    /**
     * Obtém recomendações pendentes para um site
     */
    public function getRecommendations($siteId, $status = 'pending') {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_security_recommendations
                WHERE site_id = ? AND status = ?
                ORDER BY 
                    CASE priority
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                    END,
                    created_at DESC
            ");
            $stmt->execute([$siteId, $status]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SecurityAdvisor GetRecommendations Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Aplica uma recomendação automaticamente (se possível)
     */
    public function applyRecommendation($recommendationId) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, s.id as site_id
                FROM safenode_security_recommendations r
                JOIN safenode_sites s ON r.site_id = s.id
                WHERE r.id = ?
            ");
            $stmt->execute([$recommendationId]);
            $recommendation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$recommendation || $recommendation['status'] !== 'pending') {
                return false;
            }
            
            // Verificar se é auto-aplicável
            $stmt = $this->db->prepare("
                SELECT auto_fixable, fix_instructions
                FROM safenode_audit_results
                WHERE check_name = ? AND check_category = ?
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$recommendation['title'], $recommendation['recommendation_type']]);
            $auditResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($auditResult && $auditResult['auto_fixable']) {
                // Aplicar correção baseada no tipo
                $applied = false;
                
                switch ($recommendation['recommendation_type']) {
                    case 'waf':
                        $stmt = $this->db->prepare("
                            UPDATE safenode_sites
                            SET threat_detection_enabled = 1
                            WHERE id = ?
                        ");
                        $stmt->execute([$recommendation['site_id']]);
                        $applied = true;
                        break;
                        
                    case 'rate_limit':
                        $stmt = $this->db->prepare("
                            UPDATE safenode_sites
                            SET rate_limit_enabled = 1
                            WHERE id = ?
                        ");
                        $stmt->execute([$recommendation['site_id']]);
                        $applied = true;
                        break;
                }
                
                if ($applied) {
                    $stmt = $this->db->prepare("
                        UPDATE safenode_security_recommendations
                        SET status = 'applied', auto_applied = 1, applied_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$recommendationId]);
                    return true;
                }
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("SecurityAdvisor ApplyRecommendation Error: " . $e->getMessage());
            return false;
        }
    }
}

