<?php
/**
 * Email Service - LACTECH
 * Classe para envio de e-mails
 * Preparada para integração com SMTP, SendGrid, Mailgun, etc.
 */

class EmailService {
    private static $instance = null;
    private $fromEmail = 'noreply@lactechsys.com';
    private $fromName = 'LacTech Sistema';
    
    // Configurações SMTP (pode ser movido para config_mysql.php)
    private $smtpHost = 'smtp.gmail.com'; // Configurar conforme necessário
    private $smtpPort = 587;
    private $smtpUser = '';
    private $smtpPass = '';
    private $smtpSecure = 'tls';
    
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
     * Enviar e-mail de verificação
     */
    public function sendVerificationEmail($to, $token, $userName = '') {
        $subject = 'Verifique seu e-mail - LacTech';
        $verificationUrl = $this->getBaseUrl() . '/verify-email.php?token=' . urlencode($token);
        
        $body = $this->getVerificationEmailTemplate($userName, $verificationUrl);
        
        return $this->sendEmail($to, $subject, $body);
    }
    
    /**
     * Enviar código OTP por e-mail
     */
    public function sendOTPEmail($to, $code, $action, $userName = '') {
        $actionNames = [
            'password_change' => 'Alteração de Senha',
            'email_change' => 'Alteração de E-mail',
            'google_unlink' => 'Desvinculação de Conta Google',
            '2fa_setup' => 'Configuração de Autenticação de Dois Fatores'
        ];
        
        $subject = 'Código de Verificação - ' . ($actionNames[$action] ?? 'Ação Segura');
        $body = $this->getOTPEmailTemplate($userName, $code, $actionNames[$action] ?? 'Ação Segura');
        
        return $this->sendEmail($to, $subject, $body);
    }
    
    /**
     * Enviar notificação de segurança
     */
    public function sendSecurityNotification($to, $action, $description, $ipAddress = null) {
        $actionNames = [
            'password_changed' => 'Senha Alterada',
            'email_verified' => 'E-mail Verificado',
            'google_linked' => 'Conta Google Vinculada',
            'google_unlinked' => 'Conta Google Desvinculada',
            '2fa_enabled' => 'Autenticação de Dois Fatores Ativada',
            '2fa_disabled' => 'Autenticação de Dois Fatores Desativada'
        ];
        
        $subject = 'Notificação de Segurança - ' . ($actionNames[$action] ?? 'Ação Realizada');
        $body = $this->getSecurityNotificationTemplate($actionNames[$action] ?? 'Ação Realizada', $description, $ipAddress);
        
        return $this->sendEmail($to, $subject, $body);
    }
    
    /**
     * Enviar e-mail genérico
     */
    private function sendEmail($to, $subject, $body) {
        // Por enquanto, usar mail() nativo do PHP
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
                error_log("Erro ao enviar e-mail para: {$to}");
                return ['success' => false, 'error' => 'Erro ao enviar e-mail'];
            }
            
            return ['success' => true, 'message' => 'E-mail enviado com sucesso'];
        } catch (Exception $e) {
            error_log("Exceção ao enviar e-mail: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obter URL base do sistema
     */
    private function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'lactechsys.com';
        
        // Se estiver em localhost, detectar porta
        if (strpos($host, 'localhost') !== false) {
            $port = $_SERVER['SERVER_PORT'] ?? '';
            if ($port && $port != '80' && $port != '443') {
                $host .= ':' . $port;
            }
        }
        
        // Remover barra final se houver
        $host = rtrim($host, '/');
        
        return $protocol . $host;
    }
    
    /**
     * Obter URL completa da logo
     */
    private function getLogoUrl() {
        $baseUrl = $this->getBaseUrl();
        return $baseUrl . '/assets/img/lactech-logo.png';
    }
    
    /**
     * Template de e-mail de verificação
     */
    private function getVerificationEmailTemplate($userName, $verificationUrl) {
        $name = !empty($userName) ? $userName : 'Usuário';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { background: #f9fafb; padding: 30px; }
                .button { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>LacTech Sistema</h1>
                </div>
                <div class='content'>
                    <h2>Verifique seu e-mail</h2>
                    <p>Olá, {$name}!</p>
                    <p>Clique no botão abaixo para verificar seu endereço de e-mail:</p>
                    <p style='text-align: center;'>
                        <a href='{$verificationUrl}' class='button'>Verificar E-mail</a>
                    </p>
                    <p>Ou copie e cole este link no seu navegador:</p>
                    <p style='word-break: break-all; color: #2563eb;'>{$verificationUrl}</p>
                    <p><strong>Este link expira em 24 horas.</strong></p>
                    <p>Se você não solicitou esta verificação, ignore este e-mail.</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " LacTech Sistema. Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template de e-mail OTP
     */
    private function getOTPEmailTemplate($userName, $code, $actionName) {
        $name = !empty($userName) ? $userName : 'Usuário';
        $baseUrl = $this->getBaseUrl();
        $bannerUrl = $baseUrl . '/assets/video/emailotp.jpg';
        $footerLogoUrl = $baseUrl . '/assets/img/lactech-logo.png';
        
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
                    padding: 0;
                    margin: 0;
                }
                .header img {
                    width: 100%;
                    max-width: 100%;
                    height: auto;
                    display: block;
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
                    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); 
                    padding: 25px 30px; 
                    margin: 20px auto; 
                    border: 2px solid #10b981; 
                    border-radius: 12px; 
                    letter-spacing: 8px; 
                    color: #000000;
                    display: inline-block;
                    min-width: 200px;
                    box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);
                }
                .warning { 
                    background: #f0fdf4; 
                    border-left: 4px solid #10b981; 
                    padding: 20px; 
                    margin: 30px 0; 
                    border-radius: 8px;
                }
                .warning strong {
                    color: #059669;
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
                    position: relative;
                }
                .footer p {
                    margin: 5px 0;
                }
                .footer-logo {
                    position: absolute;
                    right: 20px;
                    bottom: 20px;
                    width: 60px;
                    height: auto;
                    opacity: 0.7;
                }
                @media only screen and (max-width: 600px) {
                    .container { width: 100% !important; }
                    .content { padding: 30px 20px !important; }
                    .code { font-size: 28px !important; letter-spacing: 4px !important; padding: 20px !important; }
                    .footer-logo {
                        position: static;
                        margin: 10px auto 0;
                        display: block;
                    }
                }
            </style>
        </head>
        <body>
            <div style='padding: 20px 0;'>
                <div class='container'>
                    <div class='header'>
                        <img src='{$bannerUrl}' alt='LacTech Sistema - Verificação OTP'>
                    </div>
                    <div class='content'>
                        <h2>Código de Verificação</h2>
                        <p>Olá, <strong>{$name}</strong>!</p>
                        <p>Você solicitou realizar a seguinte ação: <strong>{$actionName}</strong></p>
                        <p>Use o código abaixo para confirmar:</p>
                        <div class='code-container'>
                            <div class='code'>{$code}</div>
                        </div>
                        <div class='warning'>
                            <strong>⚠️ Importante:</strong>
                            <ul>
                                <li>Este código expira em 5 minutos</li>
                                <li>Nunca compartilhe este código com ninguém</li>
                                <li>Se você não solicitou esta ação, ignore este e-mail</li>
                            </ul>
                        </div>
                    </div>
                    <div class='footer'>
                        <p><strong>LacTech Sistema</strong></p>
                        <p>© " . date('Y') . " LacTech Sistema. Todos os direitos reservados.</p>
                        <img src='{$footerLogoUrl}' alt='LacTech Logo' class='footer-logo'>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template de notificação de segurança
     */
    private function getSecurityNotificationTemplate($actionName, $description, $ipAddress) {
        $ipInfo = $ipAddress ? "<p><strong>IP:</strong> {$ipAddress}</p>" : '';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { background: #f9fafb; padding: 30px; }
                .alert { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>LacTech Sistema</h1>
                </div>
                <div class='content'>
                    <h2>Notificação de Segurança</h2>
                    <p>Uma ação importante foi realizada na sua conta:</p>
                    <div class='alert'>
                        <strong>{$actionName}</strong>
                        <p>{$description}</p>
                        {$ipInfo}
                        <p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>
                    </div>
                    <p>Se você não realizou esta ação, entre em contato conosco imediatamente.</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " LacTech Sistema. Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}



