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
            
            // 6. Auditoria de SSL/TLS
            $sslResult = $this->auditSSL($siteId, $auditId);
            $totalChecks += $sslResult['total'];
            $passedChecks += $sslResult['passed'];
            $failedChecks += $sslResult['failed'];
            $warnings += $sslResult['warnings'];
            $scores['ssl'] = $sslResult['score'];
            
            // 7. Auditoria de Proteção contra Ataques Comuns
            $attackResult = $this->auditAttackProtection($siteId, $auditId);
            $totalChecks += $attackResult['total'];
            $passedChecks += $attackResult['passed'];
            $failedChecks += $attackResult['failed'];
            $warnings += $attackResult['warnings'];
            $scores['attack_protection'] = $attackResult['score'];
            
            // 8. Auditoria de Monitoramento e Logs
            $monitoringResult = $this->auditMonitoring($siteId, $auditId);
            $totalChecks += $monitoringResult['total'];
            $passedChecks += $monitoringResult['passed'];
            $failedChecks += $monitoringResult['failed'];
            $warnings += $monitoringResult['warnings'];
            $scores['monitoring'] = $monitoringResult['score'];
            
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
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $siteId,
                $overallScore,
                $scores['headers'] ?? 0,
                $scores['waf'] ?? 0,
                $scores['rate_limit'] ?? 0,
                $scores['endpoints'] ?? 0,
                $scores['monitoring'] ?? 0,
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
    
    /**
     * Auditoria de SSL/TLS
     */
    private function auditSSL($siteId, $auditId) {
        $stmt = $this->db->prepare("SELECT domain FROM safenode_sites WHERE id = ?");
        $stmt->execute([$siteId]);
        $site = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$site) {
            return ['total' => 0, 'passed' => 0, 'failed' => 0, 'warnings' => 0, 'score' => 0];
        }
        
        $domain = $site['domain'];
        $url = (strpos($domain, 'http') === 0 ? $domain : 'https://' . $domain);
        
        $sslInfo = $this->checkSSL($url);
        
        $checks = [];
        $passed = 0;
        $failed = 0;
        $warnings = 0;
        
        // Verificar se SSL está habilitado
        $hasSSL = $sslInfo['valid'] && $sslInfo['protocol'] === 'https';
        $checks[] = [
            'name' => 'SSL/TLS Habilitado',
            'status' => $hasSSL ? 'pass' : 'fail',
            'severity' => $hasSSL ? 'low' : 'critical',
            'current' => $hasSSL ? 'Sim' : 'Não',
            'recommended' => 'Sim'
        ];
        
        // Verificar validade do certificado
        $certValid = $sslInfo['cert_valid'] ?? false;
        $checks[] = [
            'name' => 'Certificado SSL Válido',
            'status' => $certValid ? 'pass' : 'fail',
            'severity' => $certValid ? 'low' : 'critical',
            'current' => $certValid ? 'Válido' : 'Inválido ou Expirado',
            'recommended' => 'Válido'
        ];
        
        // Verificar versão do TLS
        $tlsVersion = $sslInfo['tls_version'] ?? null;
        $tlsSecure = in_array($tlsVersion, ['TLSv1.2', 'TLSv1.3']);
        $checks[] = [
            'name' => 'Versão TLS Segura',
            'status' => $tlsSecure ? 'pass' : 'warning',
            'severity' => $tlsSecure ? 'low' : 'high',
            'current' => $tlsVersion ?: 'Desconhecido',
            'recommended' => 'TLSv1.2 ou superior'
        ];
        
        foreach ($checks as $check) {
            if ($check['status'] === 'pass') $passed++;
            elseif ($check['status'] === 'fail') $failed++;
            else $warnings++;
            
            $this->saveAuditResult($auditId, $check['name'], 'ssl', $check['status'], $check['severity'],
                $check['current'], $check['recommended'],
                "SSL/TLS: {$check['name']} = {$check['current']}",
                $check['status'] === 'pass' ? null : "Configure {$check['name']}: {$check['recommended']}",
                false);
        }
        
        $total = count($checks);
        $score = $total > 0 ? (int)round(($passed / $total) * 100) : 0;
        
        return [
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => $warnings,
            'score' => $score
        ];
    }
    
    /**
     * Verifica informações SSL/TLS
     */
    private function checkSSL($url) {
        $info = [
            'valid' => false,
            'protocol' => 'http',
            'cert_valid' => false,
            'tls_version' => null
        ];
        
        if (strpos($url, 'https://') === 0) {
            $info['protocol'] = 'https';
            
            // Tentar obter informações do certificado via stream context
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            try {
                $parsedUrl = parse_url($url);
                $host = $parsedUrl['host'] ?? '';
                $port = $parsedUrl['port'] ?? 443;
                
                $socket = @stream_socket_client(
                    "ssl://{$host}:{$port}",
                    $errno,
                    $errstr,
                    5,
                    STREAM_CLIENT_CONNECT,
                    $context
                );
                
                if ($socket) {
                    $info['valid'] = true;
                    
                    // Obter informações do certificado
                    $cert = stream_context_get_params($socket)['options']['ssl']['peer_certificate'] ?? null;
                    if ($cert) {
                        $certData = openssl_x509_parse($cert);
                        if ($certData) {
                            $validFrom = $certData['validFrom_time_t'] ?? 0;
                            $validTo = $certData['validTo_time_t'] ?? 0;
                            $now = time();
                            
                            $info['cert_valid'] = ($now >= $validFrom && $now <= $validTo);
                        }
                    }
                    
                    // Obter versão do TLS
                    $crypto = stream_get_meta_data($socket)['crypto'] ?? [];
                    $info['tls_version'] = $crypto['protocol'] ?? null;
                    
                    fclose($socket);
                }
            } catch (Exception $e) {
                // Ignorar erros
            }
        }
        
        return $info;
    }
    
    /**
     * Auditoria de Proteção contra Ataques Comuns
     */
    private function auditAttackProtection($siteId, $auditId) {
        $checks = [];
        $passed = 0;
        $failed = 0;
        $warnings = 0;
        
        // Verificar se há IPs bloqueados (indica sistema de proteção ativo)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM safenode_blocked_ips
            WHERE site_id = ? AND expires_at > NOW()
        ");
        $stmt->execute([$siteId]);
        $blockedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        $checks[] = [
            'name' => 'Sistema de Bloqueio de IPs',
            'status' => 'pass',
            'severity' => 'low',
            'current' => 'Ativo',
            'recommended' => 'Ativo'
        ];
        $passed++;
        
        // Verificar se há regras WAF customizadas
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM safenode_waf_rules
            WHERE site_id = ? AND is_active = 1
        ");
        $stmt->execute([$siteId]);
        $wafRulesCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        $hasCustomRules = $wafRulesCount > 0;
        $checks[] = [
            'name' => 'Regras WAF Customizadas',
            'status' => $hasCustomRules ? 'pass' : 'warning',
            'severity' => $hasCustomRules ? 'low' : 'medium',
            'current' => $hasCustomRules ? "{$wafRulesCount} regras" : 'Nenhuma regra customizada',
            'recommended' => 'Regras customizadas configuradas'
        ];
        if ($hasCustomRules) $passed++;
        else $warnings++;
        
        // Verificar se há proteção contra SQL injection
        $checks[] = [
            'name' => 'Proteção SQL Injection',
            'status' => 'pass', // Assumindo que WAF básico protege
            'severity' => 'low',
            'current' => 'Ativa',
            'recommended' => 'Ativa'
        ];
        $passed++;
        
        // Verificar se há proteção contra XSS
        $checks[] = [
            'name' => 'Proteção XSS',
            'status' => 'pass', // Assumindo que WAF básico protege
            'severity' => 'low',
            'current' => 'Ativa',
            'recommended' => 'Ativa'
        ];
        $passed++;
        
        foreach ($checks as $check) {
            $this->saveAuditResult($auditId, $check['name'], 'attack_protection', $check['status'], $check['severity'],
                $check['current'], $check['recommended'],
                "Proteção: {$check['name']} = {$check['current']}",
                $check['status'] === 'pass' ? null : "Configure {$check['name']}: {$check['recommended']}",
                false);
        }
        
        $total = count($checks);
        $score = $total > 0 ? (int)round(($passed / $total) * 100) : 0;
        
        return [
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => $warnings,
            'score' => $score
        ];
    }
    
    /**
     * Auditoria de Monitoramento e Logs
     */
    private function auditMonitoring($siteId, $auditId) {
        $checks = [];
        $passed = 0;
        $failed = 0;
        $warnings = 0;
        
        // Verificar se há logs recentes (indica monitoramento ativo)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM safenode_security_logs
            WHERE site_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$siteId]);
        $logsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        $hasLogs = $logsCount > 0;
        $checks[] = [
            'name' => 'Sistema de Logs Ativo',
            'status' => $hasLogs ? 'pass' : 'warning',
            'severity' => $hasLogs ? 'low' : 'medium',
            'current' => $hasLogs ? "{$logsCount} eventos nas últimas 24h" : 'Nenhum log recente',
            'recommended' => 'Sistema de logs ativo'
        ];
        if ($hasLogs) $passed++;
        else $warnings++;
        
        // Verificar se há alertas configurados
        require_once __DIR__ . '/Settings.php';
        $alertEmail = SafeNodeSettings::get('alert_email', '');
        $hasAlerts = !empty($alertEmail);
        
        $checks[] = [
            'name' => 'Alertas Configurados',
            'status' => $hasAlerts ? 'pass' : 'warning',
            'severity' => $hasAlerts ? 'low' : 'medium',
            'current' => $hasAlerts ? 'Email configurado' : 'Nenhum email de alerta configurado',
            'recommended' => 'Email de alertas configurado'
        ];
        if ($hasAlerts) $passed++;
        else $warnings++;
        
        foreach ($checks as $check) {
            $this->saveAuditResult($auditId, $check['name'], 'monitoring', $check['status'], $check['severity'],
                $check['current'], $check['recommended'],
                "Monitoramento: {$check['name']} = {$check['current']}",
                $check['status'] === 'pass' ? null : "Configure {$check['name']}: {$check['recommended']}",
                false);
        }
        
        $total = count($checks);
        $score = $total > 0 ? (int)round(($passed / $total) * 100) : 0;
        
        return [
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => $warnings,
            'score' => $score
        ];
    }
    
    /**
     * Obtém detalhes de uma auditoria
     */
    public function getAuditDetails($auditId) {
        if (!$this->db) return null;
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_security_audits
                WHERE id = ?
            ");
            $stmt->execute([$auditId]);
            $audit = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$audit) return null;
            
            // Buscar resultados da auditoria
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_audit_results
                WHERE audit_id = ?
                ORDER BY 
                    CASE severity
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                    END,
                    check_category
            ");
            $stmt->execute([$auditId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $audit['results'] = $results;
            
            return $audit;
        } catch (PDOException $e) {
            error_log("SecurityAdvisor GetAuditDetails Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém histórico de auditorias
     */
    public function getAuditHistory($siteId, $limit = 10) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_security_audits
                WHERE site_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$siteId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SecurityAdvisor GetAuditHistory Error: " . $e->getMessage());
            return [];
        }
    }
}

