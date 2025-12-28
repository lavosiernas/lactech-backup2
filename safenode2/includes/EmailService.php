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
        $subject = 'Verificação de Cadastro - SafeNode';
        $body = $this->getRegistrationOTPTemplate($userName, $code);
        
        return $this->sendEmail($to, $subject, $body);
    }
    
    /**
     * Enviar notificação de manutenção
     */
    public function sendMaintenanceNotification($to, $userName = '') {
        $subject = '🔧 Sistema em Manutenção - SafeNode';
        $body = $this->getMaintenanceTemplate($userName);
        
        return $this->sendEmail($to, $subject, $body);
    }
    
    /**
     * Enviar notificação de sistema online
     */
    public function sendSystemOnlineNotification($to, $userName = '') {
        $subject = '✅ Sistema Online - SafeNode';
        $body = $this->getSystemOnlineTemplate($userName);
        
        return $this->sendEmail($to, $subject, $body);
    }
    
    /**
     * Enviar código OTP para recuperação de senha
     */
    public function sendPasswordResetOTP($to, $code, $userName = '') {
        $subject = 'Recuperação de Senha - SafeNode';
        $body = $this->getPasswordResetTemplate($userName, $code);
        
        return $this->sendEmail($to, $subject, $body);
    }
    
    /**
     * Enviar código de segurança para ações perigosas
     */
    public function sendSecurityCode($to, $code, $action, $userName = '') {
        $subject = '🔒 Código de Segurança - SafeNode';
        $body = $this->getSecurityCodeTemplate($userName, $code, $action);
        
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
        
        $otpImageUrl = $baseUrl . "assets/img/emailotp%20(8).jpg";
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #000000;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    -webkit-font-smoothing: antialiased;
                }
                .outer {
                    width: 100%;
                    background-color: #000000;
                    padding: 24px 0;
                }
                .container {
                    width: 100%;
                    max-width: 640px;
                    margin: 0 auto;
                    background-color: #000000;
                    border-radius: 16px;
                    overflow: hidden;
                }
                .hero img {
                    display: block;
                    width: 100%;
                    height: auto;
                    border: 0;
                }
                .content {
                    padding: 24px 20px 28px 20px;
                    color: #e5e7eb;
                }
                h2 {
                    margin: 0 0 12px 0;
                    font-size: 20px;
                    font-weight: 600;
                    color: #ffffff;
                }
                p {
                    margin: 6px 0;
                    font-size: 14px;
                    color: #d4d4d8;
                }
                .code {
                    margin: 20px 0;
                    text-align: center;
                }
                .code span {
                    display: inline-block;
                    padding: 16px 24px;
                    border-radius: 999px;
                    border: 1px solid #ffffff;
                    font-size: 26px;
                    letter-spacing: 8px;
                    font-weight: 700;
                    color: #ffffff;
                    background-color: #000000;
                }
                .footer {
                    padding: 18px 20px 10px 20px;
                    text-align: center;
                    font-size: 11px;
                    color: #9ca3af;
                }
                .footer p {
                    margin: 4px 0;
                }
                .footer-logo {
                    width: 40px;
                    height: 40px;
                    margin: 0 auto 12px;
                    display: block;
                }
                @media only screen and (max-width: 600px) {
                    .container { border-radius: 0 !important; }
                    .content { padding: 20px 16px 24px 16px !important; }
                    .code span { font-size: 22px !important; letter-spacing: 4px !important; padding: 14px 18px !important; }
                }
            </style>
        </head>
        <body>
            <div class='outer'>
                <div class='container'>
                    <div class='hero'>
                        <img src='{$otpImageUrl}' alt='SafeNode - Verificação de OTP'>
                    </div>
                    <div class='content'>
                        <h2>Verificação de cadastro</h2>
                        <p>Olá, <strong>{$name}</strong>!</p>
                        <p>Use o código abaixo para confirmar seu cadastro e ativar sua conta no SafeNode.</p>
                        <div class='code'>
                            <span>{$code}</span>
                        </div>
                        <p>Por segurança, este código expira em 10 minutos. Se você não solicitou este cadastro, pode ignorar este e-mail.</p>
                    </div>
                    <div class='footer'>
                        <img src='{$baseUrl}assets/img/logos%20(6).png' alt='SafeNode' class='footer-logo'>
                        <p><strong>SafeNode Security Platform</strong></p>
                        <p>© " . date('Y') . " SafeNode. Todos os direitos reservados.</p>
                        <p>Este é um e-mail automático, por favor não responda.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template de e-mail para notificação de manutenção
     */
    private function getMaintenanceTemplate($userName) {
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
                    margin: 0;
                    padding: 0;
                    background-color: #000000;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    -webkit-font-smoothing: antialiased;
                }
                .outer {
                    width: 100%;
                    background-color: #000000;
                    padding: 24px 0;
                }
                .container {
                    width: 100%;
                    max-width: 640px;
                    margin: 0 auto;
                    background-color: #000000;
                    border-radius: 16px;
                    overflow: hidden;
                }
                .hero img {
                    display: block;
                    width: 100%;
                    height: auto;
                    border: 0;
                }
                .content {
                    padding: 32px 24px;
                    color: #e5e7eb;
                    background-color: #18181b;
                    border: 1px solid #27272a;
                    border-radius: 0 0 16px 16px;
                }
                h2 {
                    margin: 0 0 16px 0;
                    font-size: 20px;
                    font-weight: 600;
                    color: #ffffff;
                }
                p {
                    margin: 12px 0;
                    font-size: 15px;
                    line-height: 1.6;
                    color: #d4d4d8;
                }
                .alert-box {
                    margin: 24px 0;
                    padding: 20px;
                    background-color: #27272a;
                    border-left: 4px solid #f97316;
                    border-radius: 8px;
                }
                .alert-box p {
                    margin: 8px 0;
                    font-size: 14px;
                }
                .footer {
                    padding: 24px;
                    text-align: center;
                    font-size: 12px;
                    color: #71717a;
                    border-top: 1px solid #27272a;
                }
                .footer p {
                    margin: 6px 0;
                }
                .footer-logo {
                    width: 40px;
                    height: 40px;
                    margin: 0 auto 12px;
                    display: block;
                }
                strong {
                    color: #ffffff;
                    font-weight: 600;
                }
                @media only screen and (max-width: 600px) {
                    .container { border-radius: 0 !important; }
                    .content { padding: 24px 16px !important; border-radius: 0 !important; }
                }
            </style>
        </head>
        <body>
            <div class='outer'>
                <div class='container'>
                    <div class='hero'>
                        <img src='https://i.postimg.cc/RFK9PbpB/emailotp-(12).jpg' alt='SafeNode - Sistema em Manutenção'>
                    </div>
                    
                    <div class='content'>
                        <h2>Olá, {$name}!</h2>
                        <p>Informamos que o <strong>SafeNode</strong> e nossos servidores estão temporariamente em manutenção para implementação de melhorias importantes em nossa plataforma.</p>
                        
                        <div class='alert-box'>
                            <p><strong>🔒 Motivo:</strong> Atualizações de segurança e melhorias no sistema</p>
                            <p><strong>🖥️ Status:</strong> Servidor e sistema em manutenção</p>
                            <p><strong>📧 Notificação:</strong> Você será avisado quando voltarmos</p>
                        </div>
                        
                        <p>Durante este período, o acesso ao sistema estará indisponível. Estamos trabalhando para retornar o mais breve possível.</p>
                        
                        <p><strong>Você receberá um novo e-mail assim que o sistema e os servidores estiverem novamente operacionais.</strong></p>
                        
                        <p>Agradecemos sua compreensão e paciência!</p>
                    </div>
                    
                    <div class='footer'>
                        <img src='{$baseUrl}assets/img/logos%20(6).png' alt='SafeNode' class='footer-logo'>
                        <p><strong>SafeNode Security Platform</strong></p>
                        <p>© " . date('Y') . " SafeNode. Todos os direitos reservados.</p>
                        <p>Este é um e-mail automático, por favor não responda.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template de e-mail para notificação de sistema online
     */
    private function getSystemOnlineTemplate($userName) {
        $name = !empty($userName) ? $userName : 'Usuário';
        $baseUrl = $this->getBaseUrl();
        $loginUrl = $baseUrl . 'login.php';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #000000;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    -webkit-font-smoothing: antialiased;
                }
                .outer {
                    width: 100%;
                    background-color: #000000;
                    padding: 24px 0;
                }
                .container {
                    width: 100%;
                    max-width: 640px;
                    margin: 0 auto;
                    background-color: #18181b;
                    border-radius: 16px;
                    overflow: hidden;
                    border: 1px solid #27272a;
                }
                .header {
                    padding: 32px 24px;
                    text-align: center;
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                }
                .header-icon {
                    width: 64px;
                    height: 64px;
                    margin: 0 auto 16px;
                    background-color: rgba(255, 255, 255, 0.2);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 32px;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: 700;
                    color: #ffffff;
                }
                .content {
                    padding: 32px 24px;
                    color: #e5e7eb;
                }
                h2 {
                    margin: 0 0 16px 0;
                    font-size: 20px;
                    font-weight: 600;
                    color: #ffffff;
                }
                p {
                    margin: 12px 0;
                    font-size: 15px;
                    line-height: 1.6;
                    color: #d4d4d8;
                }
                .success-box {
                    margin: 24px 0;
                    padding: 20px;
                    background-color: #27272a;
                    border-left: 4px solid #10b981;
                    border-radius: 8px;
                }
                .success-box p {
                    margin: 8px 0;
                    font-size: 14px;
                }
                .cta-button {
                    display: inline-block;
                    margin: 24px 0;
                    padding: 14px 32px;
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 15px;
                }
                .footer {
                    padding: 24px;
                    text-align: center;
                    font-size: 12px;
                    color: #71717a;
                    border-top: 1px solid #27272a;
                }
                .footer p {
                    margin: 6px 0;
                }
                .footer-logo {
                    width: 40px;
                    height: 40px;
                    margin: 0 auto 12px;
                    display: block;
                }
                strong {
                    color: #ffffff;
                    font-weight: 600;
                }
                @media only screen and (max-width: 600px) {
                    .container { border-radius: 0 !important; }
                    .content { padding: 24px 16px !important; }
                    .header { padding: 24px 16px !important; }
                }
            </style>
        </head>
        <body>
            <div class='outer'>
                <div class='container'>
                    <div class='header'>
                        <div class='header-icon'>✅</div>
                        <h1>Sistema Online</h1>
                    </div>
                    
                    <div class='content'>
                        <h2>Ótimas notícias, {$name}!</h2>
                        <p>A manutenção do <strong>SafeNode</strong> foi concluída com sucesso e o sistema está novamente operacional!</p>
                        
                        <center style='margin: 24px 0;'>
                            <img src='{$baseUrl}assets/img/emailotp%20(13).jpg' alt='SafeNode - Sistema Pronto' style='max-width: 100%; height: auto; border-radius: 12px; border: 1px solid #27272a;'>
                        </center>
                        
                        <div class='success-box'>
                            <p><strong>✨ Novidades Aplicadas:</strong></p>
                            <p>• Melhorias de segurança implementadas</p>
                            <p>• Otimizações de performance</p>
                            <p>• Correções e aprimoramentos gerais</p>
                        </div>
                        
                        <p>Você já pode acessar sua conta normalmente e continuar utilizando todos os recursos da plataforma.</p>
                        
                        <center>
                            <a href='{$loginUrl}' class='cta-button'>Acessar SafeNode</a>
                        </center>
                        
                        <p>Agradecemos sua paciência durante o período de manutenção!</p>
                    </div>
                    
                    <div class='footer'>
                        <img src='{$baseUrl}assets/img/logos%20(6).png' alt='SafeNode' class='footer-logo'>
                        <p><strong>SafeNode Security Platform</strong></p>
                        <p>© " . date('Y') . " SafeNode. Todos os direitos reservados.</p>
                        <p>Este é um e-mail automático, por favor não responda.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template de e-mail de recuperação de senha
     */
    private function getPasswordResetTemplate($userName, $code) {
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
                    margin: 0;
                    padding: 0;
                    background-color: #000000;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    -webkit-font-smoothing: antialiased;
                }
                .outer {
                    width: 100%;
                    background-color: #000000;
                    padding: 24px 0;
                }
                .container {
                    width: 100%;
                    max-width: 640px;
                    margin: 0 auto;
                    background-color: #18181b;
                    border-radius: 16px;
                    overflow: hidden;
                    border: 1px solid #27272a;
                }
                .header {
                    padding: 32px 24px;
                    text-align: center;
                    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                }
                .header-icon {
                    width: 64px;
                    height: 64px;
                    margin: 0 auto 16px;
                    background-color: rgba(255, 255, 255, 0.2);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 32px;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: 700;
                    color: #ffffff;
                }
                .content {
                    padding: 32px 24px;
                    color: #e5e7eb;
                }
                h2 {
                    margin: 0 0 16px 0;
                    font-size: 20px;
                    font-weight: 600;
                    color: #ffffff;
                }
                p {
                    margin: 12px 0;
                    font-size: 15px;
                    line-height: 1.6;
                    color: #d4d4d8;
                }
                .code-box {
                    margin: 24px auto;
                    padding: 24px;
                    background: linear-gradient(135deg, #27272a 0%, #1c1c1e 100%);
                    border: 2px solid #3b82f6;
                    border-radius: 12px;
                    text-align: center;
                }
                .code {
                    font-size: 36px;
                    font-weight: 700;
                    color: #3b82f6;
                    letter-spacing: 8px;
                    font-family: 'Courier New', monospace;
                    margin: 8px 0;
                }
                .alert-box {
                    margin: 24px 0;
                    padding: 16px;
                    background-color: #27272a;
                    border-left: 4px solid #f59e0b;
                    border-radius: 8px;
                }
                .alert-box p {
                    margin: 8px 0;
                    font-size: 14px;
                    color: #fbbf24;
                }
                .footer {
                    padding: 24px;
                    text-align: center;
                    font-size: 12px;
                    color: #71717a;
                    border-top: 1px solid #27272a;
                }
                .footer p {
                    margin: 6px 0;
                }
                .footer-logo {
                    width: 40px;
                    height: 40px;
                    margin: 0 auto 12px;
                    display: block;
                }
                strong {
                    color: #ffffff;
                    font-weight: 600;
                }
                @media only screen and (max-width: 600px) {
                    .container { border-radius: 0 !important; }
                    .content { padding: 24px 16px !important; }
                    .header { padding: 24px 16px !important; }
                    .code { font-size: 28px; letter-spacing: 4px; }
                }
            </style>
        </head>
        <body>
            <div class='outer'>
                <div class='container'>
                    <div class='header'>
                        <div class='header-icon'>🔐</div>
                        <h1>Recuperação de Senha</h1>
                    </div>
                    
                    <div class='content'>
                        <h2>Olá, {$name}!</h2>
                        <p>Recebemos uma solicitação para redefinir a senha da sua conta <strong>SafeNode</strong>.</p>
                        
                        <p>Use o código abaixo para continuar com a recuperação:</p>
                        
                        <div class='code-box'>
                            <p style='margin: 0; font-size: 13px; color: #a1a1aa;'>Seu código de verificação:</p>
                            <div class='code'>{$code}</div>
                            <p style='margin: 8px 0 0 0; font-size: 13px; color: #a1a1aa;'>Válido por 15 minutos</p>
                        </div>
                        
                        <div class='alert-box'>
                            <p><strong>⚠️ Importante:</strong></p>
                            <p>• Este código expira em 15 minutos</p>
                            <p>• Não compartilhe este código com ninguém</p>
                            <p>• Se você não solicitou esta recuperação, ignore este e-mail</p>
                        </div>
                        
                        <p>Por questões de segurança, nunca solicitaremos sua senha por e-mail.</p>
                    </div>
                    
                    <div class='footer'>
                        <img src='{$baseUrl}assets/img/logos%20(6).png' alt='SafeNode' class='footer-logo'>
                        <p><strong>SafeNode Security Platform</strong></p>
                        <p>© " . date('Y') . " SafeNode. Todos os direitos reservados.</p>
                        <p>Este é um e-mail automático, por favor não responda.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template de e-mail para código de segurança (ações perigosas)
     */
    private function getSecurityCodeTemplate($userName, $code, $action) {
        $name = !empty($userName) ? $userName : 'Usuário';
        $baseUrl = $this->getBaseUrl();
        
        $actionText = ($action === 'terminate_sessions') 
            ? 'encerrar todas as suas sessões ativas' 
            : 'excluir sua conta permanentemente';
        
        $actionWarning = ($action === 'terminate_sessions')
            ? 'Você precisará fazer login novamente em todos os seus dispositivos.'
            : 'Esta ação é IRREVERSÍVEL. Todos os seus dados serão permanentemente apagados.';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #000000;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    -webkit-font-smoothing: antialiased;
                }
                .outer {
                    width: 100%;
                    background-color: #000000;
                    padding: 24px 0;
                }
                .container {
                    width: 100%;
                    max-width: 640px;
                    margin: 0 auto;
                    background-color: #18181b;
                    border-radius: 16px;
                    overflow: hidden;
                    border: 1px solid #27272a;
                }
                .header {
                    padding: 32px 24px;
                    text-align: center;
                    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
                }
                .header-icon {
                    width: 64px;
                    height: 64px;
                    margin: 0 auto 16px;
                    background-color: rgba(255, 255, 255, 0.2);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 32px;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: 700;
                    color: #ffffff;
                }
                .content {
                    padding: 32px 24px;
                    color: #e5e7eb;
                }
                h2 {
                    margin: 0 0 16px 0;
                    font-size: 20px;
                    font-weight: 600;
                    color: #ffffff;
                }
                p {
                    margin: 12px 0;
                    font-size: 15px;
                    line-height: 1.6;
                    color: #d4d4d8;
                }
                .code-box {
                    margin: 24px auto;
                    padding: 24px;
                    background: linear-gradient(135deg, #27272a 0%, #1c1c1e 100%);
                    border: 2px solid #dc2626;
                    border-radius: 12px;
                    text-align: center;
                }
                .code {
                    font-size: 36px;
                    font-weight: 700;
                    color: #dc2626;
                    letter-spacing: 8px;
                    font-family: 'Courier New', monospace;
                    margin: 8px 0;
                }
                .warning-box {
                    margin: 24px 0;
                    padding: 20px;
                    background-color: #7f1d1d;
                    border-left: 4px solid #dc2626;
                    border-radius: 8px;
                }
                .warning-box p {
                    margin: 8px 0;
                    font-size: 14px;
                    color: #fca5a5;
                }
                .footer {
                    padding: 24px;
                    text-align: center;
                    font-size: 12px;
                    color: #71717a;
                    border-top: 1px solid #27272a;
                }
                .footer p {
                    margin: 6px 0;
                }
                .footer-logo {
                    width: 40px;
                    height: 40px;
                    margin: 0 auto 12px;
                    display: block;
                }
                strong {
                    color: #ffffff;
                    font-weight: 600;
                }
                @media only screen and (max-width: 600px) {
                    .container { border-radius: 0 !important; }
                    .content { padding: 24px 16px !important; }
                    .header { padding: 24px 16px !important; }
                    .code { font-size: 28px; letter-spacing: 4px; }
                }
            </style>
        </head>
        <body>
            <div class='outer'>
                <div class='container'>
                    <div class='header'>
                        <div class='header-icon'>🔒</div>
                        <h1>Código de Segurança</h1>
                    </div>
                    
                    <div class='content'>
                        <h2>Olá, {$name}!</h2>
                        <p>Uma tentativa de <strong>{$actionText}</strong> foi detectada em sua conta SafeNode.</p>
                        
                        <p>Use o código abaixo para confirmar esta ação:</p>
                        
                        <div class='code-box'>
                            <p style='margin: 0; font-size: 13px; color: #a1a1aa;'>Seu código de segurança:</p>
                            <div class='code'>{$code}</div>
                            <p style='margin: 8px 0 0 0; font-size: 13px; color: #a1a1aa;'>Válido por 10 minutos</p>
                        </div>
                        
                        <div class='warning-box'>
                            <p><strong>⚠️ ATENÇÃO:</strong></p>
                            <p>{$actionWarning}</p>
                            <p>Se você não solicitou esta ação, <strong>ignore este e-mail</strong> e altere sua senha imediatamente.</p>
                        </div>
                        
                        <p>Por segurança, nunca compartilhe este código com ninguém.</p>
                    </div>
                    
                    <div class='footer'>
                        <img src='{$baseUrl}assets/img/logos%20(6).png' alt='SafeNode' class='footer-logo'>
                        <p><strong>SafeNode Security Platform</strong></p>
                        <p>© " . date('Y') . " SafeNode. Todos os direitos reservados.</p>
                        <p>Este é um e-mail automático, por favor não responda.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

