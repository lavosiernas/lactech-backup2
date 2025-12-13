<?php
/**
 * SafeNode - Sistema reCAPTCHA Próprio (100% SafeNode)
 * 
 * Sistema completo de verificação humana sem dependência de serviços externos.
 * Usa análise comportamental, scoring ML e verificação de interação real.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Settings.php';
require_once __DIR__ . '/HVAPIKeyManager.php';
require_once __DIR__ . '/BehaviorAnalyzer.php';
require_once __DIR__ . '/MLScoringSystem.php';
require_once __DIR__ . '/IPReputationManager.php';

class SafeNodeReCAPTCHA
{
    private static $version = null; // v2 (checkbox) ou v3 (invisível)
    private static $scoreThreshold = null; // Para v3 (0.0 a 1.0)
    private static $action = null;
    private static $enabled = null;
    private static $initialized = false;
    
    /**
     * Inicializa as configurações do reCAPTCHA SafeNode
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        
        self::$version = SafeNodeSettings::get('safenode_recaptcha_version', 'v2');
        self::$action = SafeNodeSettings::get('safenode_recaptcha_action', 'submit');
        self::$scoreThreshold = (float) SafeNodeSettings::get('safenode_recaptcha_score_threshold', 0.5);
        self::$enabled = SafeNodeSettings::get('safenode_recaptcha_enabled', '0') === '1';
        self::$initialized = true;
    }
    
    /**
     * Verifica se o reCAPTCHA está habilitado
     */
    public static function isEnabled(): bool
    {
        if (!self::$initialized) {
            self::init();
        }
        return self::$enabled === true;
    }
    
    /**
     * Retorna a versão configurada
     */
    public static function getVersion(): string
    {
        if (!self::$initialized) {
            self::init();
        }
        return self::$version ?? 'v2';
    }
    
    /**
     * Gera um token de desafio para o cliente
     * 
     * @param string $apiKey API Key do SafeNode
     * @param string|null $remoteIp IP do cliente
     * @return array ['success' => bool, 'token' => string, 'challenge_id' => string, 'error' => string|null]
     */
    public static function generateChallenge(string $apiKey, ?string $remoteIp = null): array
    {
        if (!self::isEnabled()) {
            return [
                'success' => false,
                'token' => '',
                'challenge_id' => '',
                'error' => 'reCAPTCHA SafeNode não está habilitado'
            ];
        }
        
        // Validar API key
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        $keyData = HVAPIKeyManager::validateKey($apiKey, $origin);
        if (!$keyData) {
            return [
                'success' => false,
                'token' => '',
                'challenge_id' => '',
                'error' => 'API key inválida'
            ];
        }
        
        $remoteIp = $remoteIp ?? ($_SERVER['REMOTE_ADDR'] ?? '');
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Criar sessão específica para esta API key
        $sessionId = 'safenode_recaptcha_' . md5($apiKey . $remoteIp);
        session_id($sessionId);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Gerar token e challenge ID
        $token = bin2hex(random_bytes(32));
        $challengeId = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));
        
        // Armazenar na sessão
        $_SESSION['safenode_recaptcha_token'] = $token;
        $_SESSION['safenode_recaptcha_challenge_id'] = $challengeId;
        $_SESSION['safenode_recaptcha_nonce'] = $nonce;
        $_SESSION['safenode_recaptcha_time'] = time();
        $_SESSION['safenode_recaptcha_ip'] = $remoteIp;
        $_SESSION['safenode_recaptcha_user_agent'] = $userAgent;
        $_SESSION['safenode_recaptcha_api_key'] = $apiKey;
        $_SESSION['safenode_recaptcha_action'] = self::$action;
        
        // Armazenar no banco para análise comportamental
        $db = getSafeNodeDatabase();
        if ($db) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO safenode_recaptcha_challenges 
                    (challenge_id, api_key_id, ip_address, user_agent, action, created_at, expires_at)
                    VALUES (?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR))
                ");
                $stmt->execute([
                    $challengeId,
                    $keyData['id'],
                    $remoteIp,
                    $userAgent,
                    self::$action
                ]);
            } catch (PDOException $e) {
                // Log mas não falha
                error_log("SafeNode reCAPTCHA: Erro ao salvar challenge: " . $e->getMessage());
            }
        }
        
        return [
            'success' => true,
            'token' => $token,
            'challenge_id' => $challengeId,
            'nonce' => $nonce,
            'version' => self::getVersion(),
            'action' => self::$action
        ];
    }
    
    /**
     * Valida a resposta do reCAPTCHA SafeNode
     * 
     * @param string $response Token do reCAPTCHA recebido do frontend
     * @param string $apiKey API Key do SafeNode
     * @param string|null $remoteIp IP do usuário
     * @param array $behaviorData Dados comportamentais (mouse movements, clicks, etc)
     * @return array ['success' => bool, 'score' => float|null, 'error' => string|null, 'details' => array]
     */
    public static function verify(
        string $response, 
        string $apiKey,
        ?string $remoteIp = null,
        array $behaviorData = []
    ): array {
        if (!self::isEnabled()) {
            return [
                'success' => false,
                'score' => null,
                'error' => 'reCAPTCHA SafeNode não está habilitado',
                'details' => []
            ];
        }
        
        if (empty($response)) {
            return [
                'success' => false,
                'score' => null,
                'error' => 'Token reCAPTCHA não fornecido',
                'details' => []
            ];
        }
        
        $remoteIp = $remoteIp ?? ($_SERVER['REMOTE_ADDR'] ?? '');
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        
        // Validar API key
        $keyData = HVAPIKeyManager::validateKey($apiKey, $origin);
        if (!$keyData) {
            return [
                'success' => false,
                'score' => null,
                'error' => 'API key inválida',
                'details' => []
            ];
        }
        
        // Verificar rate limit
        $rateLimit = HVAPIKeyManager::checkRateLimit($keyData['id'], $remoteIp);
        if (!$rateLimit['allowed']) {
            return [
                'success' => false,
                'score' => null,
                'error' => 'Limite de requisições excedido',
                'details' => ['rate_limit' => $rateLimit]
            ];
        }
        
        // Recuperar sessão
        $sessionId = 'safenode_recaptcha_' . md5($apiKey . $remoteIp);
        session_id($sessionId);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $tokenSession = $_SESSION['safenode_recaptcha_token'] ?? null;
        $challengeIdSession = $_SESSION['safenode_recaptcha_challenge_id'] ?? null;
        $nonceSession = $_SESSION['safenode_recaptcha_nonce'] ?? null;
        $timeSession = $_SESSION['safenode_recaptcha_time'] ?? 0;
        $ipSession = $_SESSION['safenode_recaptcha_ip'] ?? '';
        $userAgentSession = $_SESSION['safenode_recaptcha_user_agent'] ?? '';
        
        // Verificar token
        if (!$tokenSession || !hash_equals($tokenSession, $response)) {
            return [
                'success' => false,
                'score' => null,
                'error' => 'Token inválido ou expirado',
                'details' => []
            ];
        }
        
        // Verificar tempo mínimo (1 segundo)
        $elapsed = time() - (int)$timeSession;
        if ($elapsed < 1) {
            return [
                'success' => false,
                'score' => null,
                'error' => 'Verificação muito rápida',
                'details' => ['elapsed' => $elapsed]
            ];
        }
        
        // Verificar expiração (1 hora)
        if ($elapsed > 3600) {
            return [
                'success' => false,
                'score' => null,
                'error' => 'Token expirado',
                'details' => ['elapsed' => $elapsed]
            ];
        }
        
        // Verificar IP
        if ($ipSession && $ipSession !== $remoteIp) {
            return [
                'success' => false,
                'score' => null,
                'error' => 'Mudança de IP detectada',
                'details' => []
            ];
        }
        
        // Calcular score baseado em análise comportamental
        $db = null;
        try {
            $db = getSafeNodeDatabase();
        } catch (Exception $e) {
            error_log("SafeNode reCAPTCHA: Erro ao obter banco: " . $e->getMessage());
        }
        
        $score = 0.5; // Score padrão (neutro)
        $details = [];
        
        if ($db) {
            try {
                // Análise comportamental do IP
                $behaviorAnalyzer = new BehaviorAnalyzer($db);
                $behaviorAnalysis = $behaviorAnalyzer->analyzeIPBehavior($remoteIp, 3600);
                
                // Análise de reputação do IP
                $ipReputation = new IPReputationManager($db);
                $reputation = $ipReputation->getReputation($remoteIp);
                
                // Análise de comportamento da sessão (se fornecido)
                $sessionBehaviorScore = self::analyzeSessionBehavior($behaviorData);
                
                // Calcular score usando ML
                $mlScoring = new MLScoringSystem($db);
                $features = [
                    'threat_score' => 100 - ($behaviorAnalysis['risk_score'] ?? 50),
                    'confidence_score' => 50,
                    'ip_reputation' => $reputation['score'] ?? 50,
                    'behavior_score' => $sessionBehaviorScore,
                    'time_pattern_score' => 50
                ];
                
                $mlResult = $mlScoring->calculateAdaptiveScore($features);
                $score = $mlResult['adaptive_score'] / 100; // Converter para 0.0-1.0
                
                // Ajustar score baseado em análise comportamental
                $riskLevel = $behaviorAnalysis['risk_level'] ?? 'low';
                if ($riskLevel === 'critical') {
                    $score = max(0.0, $score - 0.4);
                } elseif ($riskLevel === 'high') {
                    $score = max(0.0, $score - 0.2);
                } elseif ($riskLevel === 'low') {
                    $score = min(1.0, $score + 0.1);
                }
                
                $details = [
                    'behavior_analysis' => $behaviorAnalysis,
                    'ip_reputation' => $reputation,
                    'session_behavior' => $sessionBehaviorScore,
                    'ml_score' => $mlResult['adaptive_score'],
                    'risk_level' => $riskLevel
                ];
                
            } catch (PDOException $e) {
                error_log("SafeNode reCAPTCHA: Erro na análise: " . $e->getMessage());
            }
        }
        
        // Para v3, verificar score threshold
        if (self::getVersion() === 'v3') {
            $success = $score >= self::$scoreThreshold;
        } else {
            // Para v2, apenas verificar se foi resolvido (checkbox marcado)
            $success = true; // Se chegou aqui, o checkbox foi marcado
        }
        
        // Registrar resultado
        if ($db && isset($challengeIdSession)) {
            try {
                $stmt = $db->prepare("
                    UPDATE safenode_recaptcha_challenges 
                    SET verified = 1, score = ?, success = ?, verified_at = NOW()
                    WHERE challenge_id = ?
                ");
                $stmt->execute([$score, $success ? 1 : 0, $challengeIdSession]);
            } catch (PDOException $e) {
                error_log("SafeNode reCAPTCHA: Erro ao registrar resultado: " . $e->getMessage());
            }
        }
        
        // Limpar sessão após uso
        unset(
            $_SESSION['safenode_recaptcha_token'],
            $_SESSION['safenode_recaptcha_challenge_id'],
            $_SESSION['safenode_recaptcha_nonce']
        );
        
        return [
            'success' => $success,
            'score' => round($score, 2),
            'error' => $success ? null : "Score muito baixo: {$score} (mínimo: " . self::$scoreThreshold . ")",
            'details' => $details,
            'action' => self::$action
        ];
    }
    
    /**
     * Analisa comportamento da sessão baseado em dados do frontend
     */
    private static function analyzeSessionBehavior(array $behaviorData): float
    {
        $score = 50.0; // Score padrão
        
        // Verificar movimentos do mouse (indica interação humana)
        if (isset($behaviorData['mouse_movements']) && $behaviorData['mouse_movements'] > 0) {
            $score += min(20, $behaviorData['mouse_movements'] * 2);
        }
        
        // Verificar cliques (indica interação)
        if (isset($behaviorData['clicks']) && $behaviorData['clicks'] > 0) {
            $score += min(15, $behaviorData['clicks'] * 3);
        }
        
        // Verificar tempo na página (muito rápido = bot)
        if (isset($behaviorData['time_on_page'])) {
            $timeOnPage = (float)$behaviorData['time_on_page'];
            if ($timeOnPage < 1) {
                $score -= 30; // Muito rápido
            } elseif ($timeOnPage >= 3) {
                $score += 10; // Tempo adequado
            }
        }
        
        // Verificar scroll (indica interação)
        if (isset($behaviorData['scroll_events']) && $behaviorData['scroll_events'] > 0) {
            $score += min(10, $behaviorData['scroll_events'] * 2);
        }
        
        // Verificar teclas pressionadas (indica digitação)
        if (isset($behaviorData['key_events']) && $behaviorData['key_events'] > 0) {
            $score += min(5, $behaviorData['key_events']);
        }
        
        return min(100, max(0, $score));
    }
    
    /**
     * Valida reCAPTCHA e retorna bool simples (para uso rápido)
     */
    public static function validate(string $response, string $apiKey, ?string $remoteIp = null): bool
    {
        $result = self::verify($response, $apiKey, $remoteIp);
        return $result['success'] === true;
    }
}

