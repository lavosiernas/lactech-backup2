<?php
/**
 * Email Service - SafeNode
 * Classe para envio de e-mails do SafeNode
 */

class SafeNodeEmailService {
    private static $instance = null;
    private $fromEmail = 'noreply@safenode.com';
    private $fromName = 'SafeNode Security';
    
    private function __construct() {
        // Configurações podem ser lidas de arquivo de config
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Enviar código OTP por e-mail para verificação de cadastro
     */
    public function sendRegistrationOTP($to, $code, $userName = '') {
        $subject = 'Código de Verificação - SafeNode';
        $body = $this->getRegistrationOTPTemplate($userName, $code);
        
        return $this->sendEmail($to, $subject, $body);
    }
    
    /**
     * Enviar e-mail genérico
     */
    private function sendEmail($to, $subject, $body) {
        // Usar mail() nativo do PHP
        // Em produção, integrar com SMTP, SendGrid, Mailgun, etc.
        
        $headers = [
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        try {
            $result = mail($to, $subject, $body, implode("\r\n", $headers));
            
            if (!$result) {
                error_log("SafeNode - Erro ao enviar e-mail para: {$to}");
                return ['success' => false, 'error' => 'Erro ao enviar e-mail'];
            }
            
            return ['success' => true, 'message' => 'E-mail enviado com sucesso'];
        } catch (Exception $e) {
            error_log("SafeNode - Exceção ao enviar e-mail: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obter URL base do sistema
     */
    private function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Se estiver em localhost, detectar porta
        if (strpos($host, 'localhost') !== false) {
            $port = $_SERVER['SERVER_PORT'] ?? '';
            if ($port && $port != '80' && $port != '443') {
                $host .= ':' . $port;
            }
        }
        
        // Remover barra final se houver
        $host = rtrim($host, '/');
        
        // Se o domínio for safenode.cloud, não precisa de subpasta
        if (strpos($host, 'safenode.cloud') !== false) {
            return $protocol . $host . '/';
        }
        
        // Caso contrário, detectar caminho do SafeNode
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $path = str_replace('\\', '/', dirname(dirname($script)));
        $path = rtrim($path, '/') . '/safenode/';
        
        return $protocol . $host . $path;
    }
    
    /**
     * Template de e-mail OTP para cadastro
     */
    private function getRegistrationOTPTemplate($userName, $code) {
        $name = !empty($userName) ? $userName : 'Usuário';
        $baseUrl = $this->getBaseUrl();
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0; 
                    padding: 0; 
                    background-color: #f5f5f5;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background-color: #ffffff;
                }
                .header { 
                    background: #000000;
                    color: white;
                    padding: 30px 20px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: 700;
                }
                .content { 
                    padding: 40px 30px; 
                    background: #ffffff;
                }
                .content h2 {
                    color: #1f2937;
                    font-size: 22px;
                    font-weight: 600;
                    margin: 0 0 20px 0;
                }
                .content p {
                    color: #4b5563;
                    margin: 15px 0;
                    font-size: 15px;
                }
                .code-container {
                    text-align: center;
                    margin: 30px 0;
                }
                .code { 
                    font-size: 36px; 
                    font-weight: 700; 
                    text-align: center; 
                    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); 
                    padding: 25px 30px; 
                    margin: 20px auto; 
                    border: 2px solid #000000; 
                    border-radius: 12px; 
                    letter-spacing: 8px; 
                    color: #000000;
                    display: inline-block;
                    min-width: 200px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .warning { 
                    background: #fef2f2; 
                    border-left: 4px solid #dc2626; 
                    padding: 20px; 
                    margin: 30px 0; 
                    border-radius: 8px;
                }
                .warning strong {
                    color: #dc2626;
                    font-size: 15px;
                    display: block;
                    margin-bottom: 12px;
                }
                .warning ul {
                    margin: 10px 0;
                    padding-left: 20px;
                    color: #4b5563;
                }
                .warning li {
                    margin: 8px 0;
                    font-size: 14px;
                }
                .footer { 
                    text-align: center; 
                    padding: 25px 20px; 
                    color: #6b7280; 
                    font-size: 12px; 
                    background: #f9fafb;
                    border-top: 1px solid #e5e7eb;
                }
                .footer p {
                    margin: 5px 0;
                }
                @media only screen and (max-width: 600px) {
                    .container { width: 100% !important; }
                    .content { padding: 30px 20px !important; }
                    .code { font-size: 28px !important; letter-spacing: 4px !important; padding: 20px !important; }
                }
            </style>
        </head>
        <body>
            <div style='padding: 20px 0;'>
                <div class='container'>
                    <div class='header'>
                        <h1>SafeNode Security</h1>
                    </div>
                    <div class='content'>
                        <h2>Código de Verificação</h2>
                        <p>Olá, <strong>{$name}</strong>!</p>
                        <p>Obrigado por se cadastrar no SafeNode. Para ativar sua conta, use o código de verificação abaixo:</p>
                        <div class='code-container'>
                            <div class='code'>{$code}</div>
                        </div>
                        <p style='text-align: center; color: #6b5563; font-size: 14px;'>Digite este código na página de verificação para completar seu cadastro.</p>
                        <div class='warning'>
                            <strong>⚠️ Importante:</strong>
                            <ul>
                                <li>Este código expira em 10 minutos</li>
                                <li>Nunca compartilhe este código com ninguém</li>
                                <li>Se você não solicitou este cadastro, ignore este e-mail</li>
                            </ul>
                        </div>
                    </div>
                    <div class='footer'>
                        <p><strong>SafeNode Security Platform</strong></p>
                        <p>© " . date('Y') . " SafeNode. Todos os direitos reservados.</p>
                        <p style='margin-top: 10px; font-size: 11px; color: #9ca3af;'>Este é um e-mail automático, por favor não responda.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

