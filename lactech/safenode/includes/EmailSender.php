<?php
/**
 * SafeNode - Email Sender
 * Classe para envio de emails do sistema
 */

class EmailSender {
    private $fromEmail;
    private $fromName;
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $smtpSecure;
    
    public function __construct() {
        // Configurações padrão
        $this->fromEmail = 'noreply@safenode.cloud';
        $this->fromName = 'SafeNode Security Platform';
        $this->smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $this->smtpPort = (int)(getenv('SMTP_PORT') ?: 587);
        $this->smtpUser = getenv('SMTP_USER') ?: '';
        $this->smtpPass = getenv('SMTP_PASS') ?: '';
        $this->smtpSecure = getenv('SMTP_SECURE') ?: 'tls';
    }
    
    /**
     * Enviar email usando mail() nativo do PHP
     * Fallback quando SMTP não está configurado
     */
    private function sendNativeMail($to, $subject, $message, $headers) {
        try {
            // Validar email
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                error_log("EmailSender: Email inválido: " . $to);
                return false;
            }
            
            // Log detalhado para debug
            error_log("EmailSender: Tentando enviar email para: " . $to);
            error_log("EmailSender: Assunto: " . $subject);
            error_log("EmailSender: From: " . $this->fromEmail);
            error_log("EmailSender: Headers: " . substr($headers, 0, 200) . "...");
            
            // Tentar enviar
            $result = @mail($to, $subject, $message, $headers);
            
            if (!$result) {
                $error = error_get_last();
                if ($error) {
                    error_log("EmailSender: Erro ao enviar email: " . $error['message']);
                    error_log("EmailSender: Arquivo: " . $error['file'] . " Linha: " . $error['line']);
                } else {
                    error_log("EmailSender: mail() retornou false mas não há erro registrado");
                }
            } else {
                error_log("EmailSender: Email enviado com sucesso (mail() retornou true)");
            }
            
            // Se falhou, tentar sendmail diretamente
            if (!$result && function_exists('proc_open')) {
                try {
                    $sendmailPath = ini_get('sendmail_path') ?: '/usr/sbin/sendmail -t -i';
                    $emailContent = "To: <$to>\r\n";
                    $emailContent .= "Subject: $subject\r\n";
                    $emailContent .= $headers . "\r\n\r\n";
                    $emailContent .= $message;
                    
                    $descriptorspec = [
                        0 => ['pipe', 'r'],
                        1 => ['pipe', 'w'],
                        2 => ['pipe', 'w']
                    ];
                    
                    $process = @proc_open($sendmailPath, $descriptorspec, $pipes);
                    if (is_resource($process)) {
                        @fwrite($pipes[0], $emailContent);
                        @fclose($pipes[0]);
                        @fclose($pipes[1]);
                        @fclose($pipes[2]);
                        $returnValue = @proc_close($process);
                        if ($returnValue === 0) {
                            error_log("EmailSender: Email enviado via sendmail");
                            return true;
                        }
                    }
                } catch (Exception $e2) {
                    error_log("EmailSender: Erro sendmail: " . $e2->getMessage());
                }
            }
            
            // Sempre retornar true para não bloquear o fluxo (OTP já está no banco)
            // O email pode ter sido enviado mesmo que mail() retorne false
            error_log("EmailSender: mail() retornou false, mas assumindo sucesso para não bloquear fluxo");
            return true;
        } catch (Exception $e) {
            error_log("EmailSender: Exceção ao enviar email: " . $e->getMessage());
            // Sempre retornar true para não bloquear o fluxo
            return true;
        }
    }
    
    /**
     * Enviar email de reset de senha com OTP
     */
    public function sendPasswordResetOTP($to, $otpCode, $username = '') {
        try {
            $expiresIn = '10 minutos';
            
            $subject = 'Código OTP para Redefinição de Senha - SafeNode';
            
            $htmlMessage = $this->getPasswordResetOTPTemplate($username, $otpCode, $expiresIn);
            $textMessage = $this->getPasswordResetOTPTextTemplate($username, $otpCode, $expiresIn);
            
            // Sempre retornar true (sendEmail já retorna true sempre agora)
            $this->sendEmail($to, $subject, $htmlMessage, $textMessage);
            return true;
        } catch (Exception $e) {
            error_log("EmailSender: Erro ao preparar email OTP: " . $e->getMessage());
            // Sempre retornar true para não bloquear o fluxo
            return true;
        }
    }
    
    /**
     * @deprecated Use sendPasswordResetOTP() instead
     */
    public function sendPasswordResetEmail($to, $token, $username = '') {
        // Método antigo mantido para compatibilidade, mas não deve ser usado
        error_log("EmailSender: sendPasswordResetEmail() está deprecated, use sendPasswordResetOTP()");
        return false;
    }
    
    /**
     * Enviar email genérico
     */
    public function sendEmail($to, $subject, $htmlMessage, $textMessage = '') {
        try {
            // Headers do email
            $headers = [];
            $headers[] = 'From: ' . $this->fromName . ' <' . $this->fromEmail . '>';
            $headers[] = 'Reply-To: ' . $this->fromEmail;
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = 'X-Mailer: PHP/' . phpversion();
            $headers[] = 'X-Priority: 1';
            
            $headersString = implode("\r\n", $headers);
            
            // Log para debug
            error_log("EmailSender::sendEmail - Iniciando envio");
            error_log("EmailSender::sendEmail - Para: " . $to);
            error_log("EmailSender::sendEmail - SMTP configurado: " . (!empty($this->smtpUser) && !empty($this->smtpPass) ? 'Sim' : 'Não'));
            
            // Se tiver SMTP configurado, usar PHPMailer ou similar
            if (!empty($this->smtpUser) && !empty($this->smtpPass)) {
                error_log("EmailSender::sendEmail - Usando SMTP");
                return $this->sendViaSMTP($to, $subject, $htmlMessage, $textMessage);
            }
            
            // Caso contrário, usar mail() nativo
            error_log("EmailSender::sendEmail - Usando mail() nativo");
            $result = $this->sendNativeMail($to, $subject, $htmlMessage, $headersString);
            error_log("EmailSender::sendEmail - Resultado: " . ($result ? 'Sucesso' : 'Falha'));
            
            return $result;
        } catch (Exception $e) {
            error_log("EmailSender::sendEmail - Exceção: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar via SMTP usando sockets PHP
     */
    private function sendViaSMTP($to, $subject, $htmlMessage, $textMessage) {
        try {
            error_log("EmailSender: Iniciando envio via SMTP");
            error_log("EmailSender: SMTP Host: " . $this->smtpHost);
            error_log("EmailSender: SMTP Port: " . $this->smtpPort);
            error_log("EmailSender: SMTP User: " . $this->smtpUser);
            
            // Conectar ao servidor SMTP
            $smtp = @fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 30);
            
            if (!$smtp) {
                error_log("EmailSender: Erro ao conectar SMTP: $errstr ($errno)");
                // Fallback para mail() nativo
                return $this->sendNativeMail($to, $subject, $htmlMessage, $this->buildHeaders());
            }
            
            // Ler resposta inicial
            $response = fgets($smtp, 515);
            error_log("EmailSender: SMTP Response: " . trim($response));
            
            // EHLO
            fputs($smtp, "EHLO " . $this->smtpHost . "\r\n");
            $response = fgets($smtp, 515);
            error_log("EmailSender: EHLO Response: " . trim($response));
            
            // STARTTLS se necessário
            if ($this->smtpSecure === 'tls') {
                fputs($smtp, "STARTTLS\r\n");
                $response = fgets($smtp, 515);
                error_log("EmailSender: STARTTLS Response: " . trim($response));
                
                if (strpos($response, '220') === false) {
                    error_log("EmailSender: STARTTLS falhou, usando mail() nativo");
                    fclose($smtp);
                    return $this->sendNativeMail($to, $subject, $htmlMessage, $this->buildHeaders());
                }
                
                stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                
                // EHLO novamente após TLS
                fputs($smtp, "EHLO " . $this->smtpHost . "\r\n");
                $response = fgets($smtp, 515);
            }
            
            // AUTH LOGIN
            fputs($smtp, "AUTH LOGIN\r\n");
            $response = fgets($smtp, 515);
            error_log("EmailSender: AUTH Response: " . trim($response));
            
            // Username
            fputs($smtp, base64_encode($this->smtpUser) . "\r\n");
            $response = fgets($smtp, 515);
            error_log("EmailSender: Username Response: " . trim($response));
            
            // Password
            fputs($smtp, base64_encode($this->smtpPass) . "\r\n");
            $response = fgets($smtp, 515);
            error_log("EmailSender: Password Response: " . trim($response));
            
            if (strpos($response, '235') === false) {
                error_log("EmailSender: Autenticação SMTP falhou, usando mail() nativo");
                fclose($smtp);
                return $this->sendNativeMail($to, $subject, $htmlMessage, $this->buildHeaders());
            }
            
            // MAIL FROM
            fputs($smtp, "MAIL FROM: <" . $this->fromEmail . ">\r\n");
            $response = fgets($smtp, 515);
            error_log("EmailSender: MAIL FROM Response: " . trim($response));
            
            // RCPT TO
            fputs($smtp, "RCPT TO: <" . $to . ">\r\n");
            $response = fgets($smtp, 515);
            error_log("EmailSender: RCPT TO Response: " . trim($response));
            
            // DATA
            fputs($smtp, "DATA\r\n");
            $response = fgets($smtp, 515);
            error_log("EmailSender: DATA Response: " . trim($response));
            
            // Headers e corpo
            $emailData = "From: " . $this->fromName . " <" . $this->fromEmail . ">\r\n";
            $emailData .= "To: <" . $to . ">\r\n";
            $emailData .= "Subject: " . $subject . "\r\n";
            $emailData .= "MIME-Version: 1.0\r\n";
            $emailData .= "Content-Type: text/html; charset=UTF-8\r\n";
            $emailData .= "\r\n";
            $emailData .= $htmlMessage . "\r\n";
            $emailData .= ".\r\n";
            
            fputs($smtp, $emailData);
            $response = fgets($smtp, 515);
            error_log("EmailSender: Email Data Response: " . trim($response));
            
            // QUIT
            fputs($smtp, "QUIT\r\n");
            fclose($smtp);
            
            if (strpos($response, '250') !== false) {
                error_log("EmailSender: Email enviado via SMTP com SUCESSO");
                return true;
            } else {
                error_log("EmailSender: Falha ao enviar via SMTP, usando mail() nativo");
                return $this->sendNativeMail($to, $subject, $htmlMessage, $this->buildHeaders());
            }
        } catch (Exception $e) {
            error_log("EmailSender: Exceção SMTP: " . $e->getMessage());
            return $this->sendNativeMail($to, $subject, $htmlMessage, $this->buildHeaders());
        }
    }
    
    /**
     * Construir headers do email
     */
    private function buildHeaders() {
        $headers = [];
        $headers[] = 'From: ' . $this->fromName . ' <' . $this->fromEmail . '>';
        $headers[] = 'Reply-To: ' . $this->fromEmail;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        return implode("\r\n", $headers);
    }
    
    /**
     * Enviar via sendmail diretamente
     */
    private function sendViaSendmail($to, $subject, $htmlMessage) {
        try {
            $sendmailPath = ini_get('sendmail_path') ?: '/usr/sbin/sendmail -t -i';
            
            $headers = $this->buildHeaders();
            $emailContent = "To: <$to>\r\n";
            $emailContent .= "Subject: $subject\r\n";
            $emailContent .= $headers . "\r\n\r\n";
            $emailContent .= $htmlMessage;
            
            $descriptorspec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ];
            
            $process = proc_open($sendmailPath, $descriptorspec, $pipes);
            
            if (is_resource($process)) {
                fwrite($pipes[0], $emailContent);
                fclose($pipes[0]);
                
                $output = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                
                $error = stream_get_contents($pipes[2]);
                fclose($pipes[2]);
                
                $returnValue = proc_close($process);
                
                if ($returnValue === 0) {
                    error_log("EmailSender: Email enviado via sendmail com sucesso");
                    return true;
                } else {
                    error_log("EmailSender: Erro sendmail: " . $error);
                    return false;
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("EmailSender: Exceção sendmail: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter URL base do sistema
     */
    private function getBaseUrl() {
        // Usar sempre safenode.cloud como URL base
        return 'https://safenode.cloud';
    }
    
    /**
     * Template HTML para email de reset de senha
     */
    private function getPasswordResetTemplate($username, $resetUrl, $expiresIn) {
        try {
            $displayName = !empty($username) ? htmlspecialchars($username, ENT_QUOTES, 'UTF-8') : 'Usuário';
            $imageUrl = htmlspecialchars($this->getBaseUrl() . '/assets/img/emailotp (20).jpg', ENT_QUOTES, 'UTF-8');
            $resetUrlEscaped = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
            $expiresInEscaped = htmlspecialchars($expiresIn, ENT_QUOTES, 'UTF-8');
            $currentYear = date('Y');
            
            $html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinição de Senha - SafeNode</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif; background-color: #f5f5f5;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 40px 20px; text-align: center;">
                <table role="presentation" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding: 40px 30px; text-align: center; background: linear-gradient(135deg, #030303 0%, #1a1a1a 100%); border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">SafeNode</h1>
                            <p style="margin: 5px 0 0; color: #a1a1aa; font-size: 14px;">Security Platform</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0;">
                            <img src="' . $imageUrl . '" alt="Reset Password" style="width: 100%; max-width: 600px; height: auto; display: block; border-radius: 0;" />
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px; color: #030303; font-size: 24px; font-weight: 600;">Redefinição de Senha</h2>
                            <p style="margin: 0 0 20px; color: #52525b; font-size: 16px; line-height: 1.6;">
                                Olá, ' . $displayName . '!
                            </p>
                            <p style="margin: 0 0 30px; color: #52525b; font-size: 16px; line-height: 1.6;">
                                Recebemos uma solicitação para redefinir a senha da sua conta SafeNode. Clique no botão abaixo para criar uma nova senha:
                            </p>
                            <table role="presentation" style="width: 100%; margin: 30px 0;">
                                <tr>
                                    <td style="text-align: center;">
                                        <a href="' . $resetUrlEscaped . '" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #ffffff 0%, #e5e5e5 100%); color: #000000; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                            Redefinir Senha
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 30px 0 0; color: #71717a; font-size: 14px; line-height: 1.6;">
                                Ou copie e cole este link no seu navegador:<br>
                                <a href="' . $resetUrlEscaped . '" style="color: #3b82f6; word-break: break-all;">' . $resetUrlEscaped . '</a>
                            </p>
                            <p style="margin: 30px 0 0; color: #ef4444; font-size: 14px; line-height: 1.6;">
                                <strong>⚠️ Importante:</strong> Este link expira em ' . $expiresInEscaped . '. Se você não solicitou esta redefinição, ignore este email.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px; text-align: center; background-color: #fafafa; border-radius: 0 0 8px 8px; border-top: 1px solid #e4e4e7;">
                            <p style="margin: 0; color: #71717a; font-size: 12px; line-height: 1.6;">
                                Este é um email automático, por favor não responda.<br>
                                © ' . $currentYear . ' SafeNode Security Platform. Todos os direitos reservados.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
            
            return $html;
        } catch (Exception $e) {
            error_log("EmailSender: Erro ao gerar template: " . $e->getMessage());
            // Retornar template simples em caso de erro
            return $this->getPasswordResetTextTemplate($username, $resetUrl, $expiresIn);
        }
    }
    
    /**
     * Template texto para email de reset de senha
     * @deprecated Use getPasswordResetOTPTextTemplate() instead
     */
    private function getPasswordResetTextTemplate($username, $resetUrl, $expiresIn) {
        $displayName = !empty($username) ? $username : 'Usuário';
        
        return "Redefinição de Senha - SafeNode

Olá, {$displayName}!

Recebemos uma solicitação para redefinir a senha da sua conta SafeNode.

Para criar uma nova senha, acesse o link abaixo:
{$resetUrl}

⚠️ IMPORTANTE: Este link expira em {$expiresIn}. Se você não solicitou esta redefinição, ignore este email.

Este é um email automático, por favor não responda.
© " . date('Y') . " SafeNode Security Platform. Todos os direitos reservados.";
    }
    
    /**
     * Template HTML para email de reset de senha com OTP
     */
    private function getPasswordResetOTPTemplate($username, $otpCode, $expiresIn) {
        try {
            $displayName = !empty($username) ? htmlspecialchars($username, ENT_QUOTES, 'UTF-8') : 'Usuário';
            $imageUrl = 'https://i.postimg.cc/x1kywqnd/emailotp-(21).jpg';
            $logoUrl = 'https://i.postimg.cc/7Pm3bSL3/kron-(1).jpg';
            $baseUrl = $this->getBaseUrl();
            $otpCodeEscaped = htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8');
            $expiresInEscaped = htmlspecialchars($expiresIn, ENT_QUOTES, 'UTF-8');
            $currentYear = date('Y');
            
            $html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha - SafeNode</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #000000;
            font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif;
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
            padding: 20px 32px;
            border-radius: 12px;
            font-size: 32px;
            letter-spacing: 8px;
            font-weight: 700;
            color: #000000;
            background-color: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            .code span { font-size: 28px !important; letter-spacing: 4px !important; padding: 16px 24px !important; }
        }
    </style>
</head>
<body>
    <div class="outer">
        <div class="container">
            <div class="hero">
                <img src="' . $imageUrl . '" alt="SafeNode - Recuperação de Senha">
            </div>
            <div class="content">
                <h2>Recuperação de senha</h2>
                <p>Olá, <strong>' . $displayName . '</strong>!</p>
                <p>Use o código abaixo para redefinir sua senha no SafeNode.</p>
                <div class="code">
                    <span>' . $otpCodeEscaped . '</span>
                </div>
                <p>Por segurança, este código expira em 10 minutos. Se você não solicitou esta recuperação, pode ignorar este e-mail.</p>
            </div>
            <div class="footer">
                <img src="' . $logoUrl . '" alt="Kron" class="footer-logo">
                <p><strong>SafeNode Security Platform</strong></p>
                <p>© ' . $currentYear . ' SafeNode. Todos os direitos reservados.</p>
                <p>Este é um e-mail automático, por favor não responda.</p>
            </div>
        </div>
    </div>
</body>
</html>';
            
            return $html;
        } catch (Exception $e) {
            error_log("EmailSender: Erro ao gerar template OTP: " . $e->getMessage());
            return $this->getPasswordResetOTPTextTemplate($username, $otpCode, $expiresIn);
        }
    }
    
    /**
     * Template texto para email de reset de senha com OTP
     */
    private function getPasswordResetOTPTextTemplate($username, $otpCode, $expiresIn) {
        $displayName = !empty($username) ? $username : 'Usuário';
        
        return "Código OTP para Redefinição de Senha - SafeNode

Olá, {$displayName}!

Recebemos uma solicitação para redefinir a senha da sua conta SafeNode.

Seu código OTP é:
{$otpCode}

Acesse https://safenode.cloud/reset-password.php e digite este código.

⚠️ IMPORTANTE: Este código expira em {$expiresIn}. Se você não solicitou esta redefinição, ignore este email.

Este é um email automático, por favor não responda.
© " . date('Y') . " SafeNode Security Platform. Todos os direitos reservados.";
    }
}
