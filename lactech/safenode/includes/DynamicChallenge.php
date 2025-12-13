<?php
/**
 * SafeNode - Dynamic Challenge System
 * Sistema de desafios progressivos baseado em risco
 * 
 * Níveis de desafio:
 * - Nível 1: Verificação JavaScript simples (atual)
 * - Nível 2: Challenge matemático simples
 * - Nível 3: CAPTCHA visual (imagens)
 * - Nível 4: reCAPTCHA v3 (Google) - se configurado
 */

class DynamicChallenge {
    private $db;
    private $sessionKey = 'safenode_challenge';
    
    public function __construct($database = null) {
        $this->db = $database;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Determina nível de challenge necessário baseado em risco
     * 
     * @param int $threatScore Score de ameaça (0-100)
     * @param int $confidenceScore Score de confiança (0-100)
     * @param array $context Fatores contextuais (IP reputation, behavior, etc)
     * @return int Nível de challenge (1-4)
     */
    public function determineChallengeLevel($threatScore, $confidenceScore = 50, $context = []) {
        // Se threat_score é muito baixo (< 20), não precisa de challenge
        if ($threatScore < 20) {
            return 0; // Sem challenge
        }
        
        // Se threat_score é muito alto (> 90), usar nível máximo
        if ($threatScore >= 90 || ($context['is_blacklisted'] ?? false)) {
            return 4; // reCAPTCHA v3 ou bloqueio direto
        }
        
        // Ajustar baseado em confidence score
        // Se confidence é baixo, pode ser falso positivo - usar challenge mais leve
        if ($confidenceScore < 30) {
            $threatScore = max(0, $threatScore - 20);
        } elseif ($confidenceScore >= 80) {
            // Se confidence é alto, aumentar severidade
            $threatScore = min(100, $threatScore + 10);
        }
        
        // Determinar nível baseado em threat_score ajustado
        if ($threatScore >= 70) {
            return 3; // CAPTCHA visual
        } elseif ($threatScore >= 50) {
            return 2; // Challenge matemático
        } elseif ($threatScore >= 30) {
            return 1; // Verificação JavaScript
        } else {
            return 0; // Sem challenge
        }
    }
    
    /**
     * Gera challenge baseado no nível
     * 
     * @param int $level Nível de challenge
     * @return array Dados do challenge
     */
    public function generateChallenge($level) {
        $challengeId = bin2hex(random_bytes(16));
        
        switch ($level) {
            case 1:
                // Challenge JavaScript simples (já implementado)
                return [
                    'level' => 1,
                    'type' => 'javascript',
                    'challenge_id' => $challengeId,
                    'token' => bin2hex(random_bytes(32)),
                    'timestamp' => time()
                ];
                
            case 2:
                // Challenge matemático
                $num1 = rand(1, 10);
                $num2 = rand(1, 10);
                $operator = ['+', '-', '*'][rand(0, 2)];
                
                $answer = match($operator) {
                    '+' => $num1 + $num2,
                    '-' => $num1 - $num2,
                    '*' => $num1 * $num2,
                    default => $num1 + $num2
                };
                
                // Salvar resposta na sessão
                $_SESSION[$this->sessionKey . '_' . $challengeId] = [
                    'answer' => $answer,
                    'expires' => time() + 300 // 5 minutos
                ];
                
                return [
                    'level' => 2,
                    'type' => 'math',
                    'challenge_id' => $challengeId,
                    'question' => "$num1 $operator $num2 = ?",
                    'timestamp' => time()
                ];
                
            case 3:
                // CAPTCHA visual (gerar código simples)
                $code = $this->generateVisualCode();
                
                $_SESSION[$this->sessionKey . '_' . $challengeId] = [
                    'code' => $code,
                    'expires' => time() + 300
                ];
                
                return [
                    'level' => 3,
                    'type' => 'visual',
                    'challenge_id' => $challengeId,
                    'image_url' => $this->generateCaptchaImage($code, $challengeId),
                    'timestamp' => time()
                ];
                
            case 4:
                // reCAPTCHA v3 (requer configuração)
                $recaptchaSiteKey = getenv('RECAPTCHA_V3_SITE_KEY') ?: '';
                
                if (empty($recaptchaSiteKey)) {
                    // Se não configurado, usar nível 3 como fallback
                    return $this->generateChallenge(3);
                }
                
                return [
                    'level' => 4,
                    'type' => 'recaptcha_v3',
                    'challenge_id' => $challengeId,
                    'site_key' => $recaptchaSiteKey,
                    'timestamp' => time()
                ];
                
            default:
                return null;
        }
    }
    
    /**
     * Valida resposta do challenge
     * 
     * @param string $challengeId ID do challenge
     * @param mixed $response Resposta do usuário
     * @return bool True se válido
     */
    public function validateChallenge($challengeId, $response) {
        $sessionKey = $this->sessionKey . '_' . $challengeId;
        
        if (!isset($_SESSION[$sessionKey])) {
            return false; // Challenge não existe ou expirou
        }
        
        $challengeData = $_SESSION[$sessionKey];
        
        // Verificar expiração
        if (isset($challengeData['expires']) && $challengeData['expires'] < time()) {
            unset($_SESSION[$sessionKey]);
            return false;
        }
        
        // Validar resposta baseado no tipo
        $valid = false;
        
        if (isset($challengeData['answer'])) {
            // Challenge matemático
            $valid = (int)$response === (int)$challengeData['answer'];
        } elseif (isset($challengeData['code'])) {
            // Challenge visual
            $valid = strtolower(trim($response)) === strtolower(trim($challengeData['code']));
        } elseif (isset($challengeData['recaptcha_token'])) {
            // reCAPTCHA v3 - validar com Google
            $valid = $this->validateRecaptchaV3($response);
        }
        
        // Limpar sessão após validação
        unset($_SESSION[$sessionKey]);
        
        return $valid;
    }
    
    /**
     * Gera código visual para CAPTCHA
     */
    private function generateVisualCode() {
        // Gerar código alfanumérico de 5 caracteres
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Remover caracteres confusos
        $code = '';
        for ($i = 0; $i < 5; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $code;
    }
    
    /**
     * Gera imagem CAPTCHA (simplificado - em produção usar biblioteca)
     */
    private function generateCaptchaImage($code, $challengeId) {
        // Em produção, usar biblioteca como GD ou ImageMagick
        // Por enquanto, retornar URL de API que gera a imagem
        $baseUrl = getSafeNodeBaseUrl();
        return $baseUrl . '/api/generate-captcha.php?code=' . urlencode($code) . '&id=' . urlencode($challengeId);
    }
    
    /**
     * Valida token reCAPTCHA v3
     */
    private function validateRecaptchaV3($token) {
        $secretKey = getenv('RECAPTCHA_V3_SECRET_KEY') ?: '';
        
        if (empty($secretKey)) {
            return false;
        }
        
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $secretKey,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $response = json_decode($result, true);
        
        return $response['success'] ?? false && ($response['score'] ?? 0) >= 0.5;
    }
    
    /**
     * Obtém HTML/JavaScript do challenge para incluir na página
     */
    public function getChallengeHTML($level, $challengeData) {
        switch ($level) {
            case 1:
                // JavaScript já está implementado no HumanVerification
                return '';
                
            case 2:
                // HTML do challenge matemático
                return "
                <div id='safenode-math-challenge' class='safenode-challenge'>
                    <p>Por favor, resolva: <strong>{$challengeData['question']}</strong></p>
                    <input type='hidden' name='safenode_challenge_id' value='{$challengeData['challenge_id']}'>
                    <input type='number' name='safenode_challenge_answer' placeholder='Resposta' required>
                </div>
                ";
                
            case 3:
                // HTML do CAPTCHA visual
                return "
                <div id='safenode-visual-challenge' class='safenode-challenge'>
                    <p>Por favor, digite o código mostrado na imagem:</p>
                    <img src='{$challengeData['image_url']}' alt='CAPTCHA' style='border: 1px solid #ccc; margin: 10px 0;'>
                    <input type='hidden' name='safenode_challenge_id' value='{$challengeData['challenge_id']}'>
                    <input type='text' name='safenode_challenge_answer' placeholder='Código' required maxlength='5' style='text-transform: uppercase;'>
                    <button type='button' onclick='this.previousElementSibling.previousElementSibling.src = this.previousElementSibling.previousElementSibling.src + \"&refresh=\" + Date.now()'>Atualizar</button>
                </div>
                ";
                
            case 4:
                // HTML do reCAPTCHA v3
                return "
                <div id='safenode-recaptcha-challenge' class='safenode-challenge'>
                    <input type='hidden' name='safenode_challenge_id' value='{$challengeData['challenge_id']}'>
                    <script src='https://www.google.com/recaptcha/api.js?render={$challengeData['site_key']}'></script>
                    <script>
                        grecaptcha.ready(function() {
                            grecaptcha.execute('{$challengeData['site_key']}', {action: 'submit'}).then(function(token) {
                                document.querySelector('input[name=\"safenode_recaptcha_token\"]').value = token;
                            });
                        });
                    </script>
                    <input type='hidden' name='safenode_recaptcha_token' value=''>
                </div>
                ";
                
            default:
                return '';
        }
    }
}






