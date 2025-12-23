<?php
/**
 * SafeNode Mail - Serviço de Envio de E-mails
 * Sistema simples e previsível de envio de e-mails
 */

class MailService {
    private $db;
    private $projectId;
    private $project;
    
    public function __construct($db, $projectId = null) {
        $this->db = $db;
        $this->projectId = $projectId;
        
        if ($projectId) {
            $this->loadProject($projectId);
        }
    }
    
    /**
     * Carrega projeto pelo ID
     */
    public function loadProject($projectId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM safenode_mail_projects WHERE id = ? AND is_active = 1");
            $stmt->execute([$projectId]);
            $this->project = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->projectId = $projectId;
            return $this->project !== false;
        } catch (PDOException $e) {
            error_log("MailService Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Carrega projeto pelo token
     */
    public function loadProjectByToken($token) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM safenode_mail_projects WHERE token = ? AND is_active = 1");
            $stmt->execute([$token]);
            $this->project = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($this->project) {
                $this->projectId = $this->project['id'];
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("MailService Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica limites diários e rate limit
     */
    public function checkLimits() {
        if (!$this->project) {
            return ['allowed' => false, 'reason' => 'Projeto não encontrado'];
        }
        
        // Verificar limite diário (500 por dia no plano básico)
        $dailyLimit = 500; // Limite diário global
        $todaySent = $this->getTodaySentCount();
        
        if ($todaySent >= $dailyLimit) {
            return ['allowed' => false, 'reason' => 'Limite diário atingido (500 e-mails/dia)'];
        }
        
        // Verificar rate limit
        $rateLimitCheck = $this->checkRateLimit();
        if (!$rateLimitCheck['allowed']) {
            return $rateLimitCheck;
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Obtém contagem de e-mails enviados hoje (todos os projetos do usuário)
     */
    private function getTodaySentCount() {
        try {
            // Buscar todos os projetos do mesmo usuário
            $stmt = $this->db->prepare("
                SELECT user_id FROM safenode_mail_projects WHERE id = ?
            ");
            $stmt->execute([$this->projectId]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$project) {
                return 0;
            }
            
            // Contar envios de hoje de todos os projetos do usuário
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total
                FROM safenode_mail_logs l
                INNER JOIN safenode_mail_projects p ON l.project_id = p.id
                WHERE p.user_id = ?
                  AND DATE(l.created_at) = CURDATE()
                  AND l.status = 'sent'
            ");
            $stmt->execute([$project['user_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("MailService getTodaySentCount Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Verifica e faz reset mensal se necessário
     */
    private function checkMonthlyReset() {
        $lastReset = $this->project['last_reset_date'];
        $today = date('Y-m-d');
        
        // Se nunca resetou ou se passou um mês
        if (!$lastReset || $this->isNewMonth($lastReset, $today)) {
            try {
                $stmt = $this->db->prepare("
                    UPDATE safenode_mail_projects 
                    SET emails_sent_this_month = 0, last_reset_date = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$today, $this->projectId]);
                $this->project['emails_sent_this_month'] = 0;
                $this->project['last_reset_date'] = $today;
            } catch (PDOException $e) {
                error_log("MailService Reset Error: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Verifica se é um novo mês
     */
    private function isNewMonth($date1, $date2) {
        $d1 = new DateTime($date1);
        $d2 = new DateTime($date2);
        return $d1->format('Y-m') !== $d2->format('Y-m');
    }
    
    /**
     * Verifica rate limit por minuto
     */
    private function checkRateLimit() {
        $currentMinute = date('Y-m-d H:i:00');
        $rateLimit = $this->project['rate_limit_per_minute'] ?? 5;
        
        try {
            // Buscar ou criar registro para este minuto
            $stmt = $this->db->prepare("
                SELECT emails_count FROM safenode_mail_rate_limits 
                WHERE project_id = ? AND minute_window = ?
            ");
            $stmt->execute([$this->projectId, $currentMinute]);
            $rateLimitData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($rateLimitData) {
                if ($rateLimitData['emails_count'] >= $rateLimit) {
                    return ['allowed' => false, 'reason' => 'Rate limit atingido (máximo ' . $rateLimit . ' e-mails/minuto)'];
                }
            }
            
            return ['allowed' => true];
        } catch (PDOException $e) {
            error_log("MailService RateLimit Error: " . $e->getMessage());
            return ['allowed' => true]; // Em caso de erro, permite (fail open)
        }
    }
    
    /**
     * Incrementa contador de rate limit
     */
    private function incrementRateLimit() {
        $currentMinute = date('Y-m-d H:i:00');
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO safenode_mail_rate_limits (project_id, minute_window, emails_count)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE emails_count = emails_count + 1
            ");
            $stmt->execute([$this->projectId, $currentMinute]);
        } catch (PDOException $e) {
            error_log("MailService RateLimit Increment Error: " . $e->getMessage());
        }
    }
    
    /**
     * Incrementa contador mensal
     */
    private function incrementMonthlyCount() {
        try {
            $stmt = $this->db->prepare("
                UPDATE safenode_mail_projects 
                SET emails_sent_this_month = emails_sent_this_month + 1 
                WHERE id = ?
            ");
            $stmt->execute([$this->projectId]);
            $this->project['emails_sent_this_month']++;
        } catch (PDOException $e) {
            error_log("MailService Monthly Increment Error: " . $e->getMessage());
        }
    }
    
    /**
     * Processa template com variáveis
     */
    public function processTemplate($content, $variables = []) {
        $processed = $content;
        
        foreach ($variables as $key => $value) {
            $processed = str_replace('{{' . $key . '}}', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $processed);
            $processed = str_replace('{{{' . $key . '}}}', $value, $processed); // Sem escape HTML
        }
        
        return $processed;
    }
    
    /**
     * Carrega template por nome
     */
    public function loadTemplate($templateName) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_mail_templates 
                WHERE project_id = ? AND name = ?
            ");
            $stmt->execute([$this->projectId, $templateName]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MailService Template Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia e-mail
     */
    public function send($to, $subject, $htmlContent, $textContent = null, $templateName = null) {
        // Validar e-mail
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'error' => 'E-mail inválido',
                'error_code' => 'INVALID_EMAIL'
            ];
        }
        
        // Verificar limites
        $limitsCheck = $this->checkLimits();
        if (!$limitsCheck['allowed']) {
            $this->logEmail($to, $subject, $templateName, 'error', $limitsCheck['reason']);
            return [
                'success' => false,
                'error' => $limitsCheck['reason'],
                'error_code' => 'LIMIT_EXCEEDED'
            ];
        }
        
        // Preparar headers
        $headers = [
            'From: ' . ($this->project['sender_name'] ?? 'SafeNode') . ' <' . $this->project['sender_email'] . '>',
            'Reply-To: ' . $this->project['sender_email'],
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: SafeNode Mail'
        ];
        
        // Enviar e-mail
        $mailSent = @mail($to, $subject, $htmlContent, implode("\r\n", $headers));
        
        if ($mailSent) {
            // Incrementar rate limit (limite diário é verificado via getTodaySentCount)
            $this->incrementRateLimit();
            
            // Log de sucesso
            $this->logEmail($to, $subject, $templateName, 'sent');
            
            return [
                'success' => true,
                'message' => 'E-mail enviado com sucesso'
            ];
        } else {
            $errorMsg = 'Falha ao enviar e-mail. Verifique a configuração do servidor.';
            $this->logEmail($to, $subject, $templateName, 'error', $errorMsg);
            
            return [
                'success' => false,
                'error' => $errorMsg,
                'error_code' => 'SEND_FAILED'
            ];
        }
    }
    
    /**
     * Registra log de envio
     */
    private function logEmail($to, $subject, $templateName, $status, $errorMessage = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO safenode_mail_logs 
                (project_id, to_email, subject, template_name, status, error_message, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $sentAt = $status === 'sent' ? date('Y-m-d H:i:s') : null;
            $stmt->execute([
                $this->projectId,
                $to,
                $subject,
                $templateName,
                $status,
                $errorMessage,
                $sentAt
            ]);
        } catch (PDOException $e) {
            error_log("MailService Log Error: " . $e->getMessage());
        }
    }
    
    /**
     * Cria projeto de e-mail
     */
    public static function createProject($db, $userId, $name, $senderEmail, $senderName = null, $emailFunction = null, $htmlTemplate = null) {
        try {
            // Gerar token único
            $token = bin2hex(random_bytes(32));
            
            $stmt = $db->prepare("
                INSERT INTO safenode_mail_projects 
                (user_id, name, sender_email, sender_name, token, monthly_limit, last_reset_date, email_function)
                VALUES (?, ?, ?, ?, ?, 500, CURDATE(), ?)
            ");
            $stmt->execute([$userId, $name, $senderEmail, $senderName, $token, $emailFunction]);
            
            $projectId = $db->lastInsertId();
            
            // Criar template com HTML fornecido
            if ($htmlTemplate && $emailFunction) {
                $functionNames = [
                    'confirm_signup' => 'Confirmar Cadastro',
                    'invite_user' => 'Convidar Usuário',
                    'magic_link' => 'Link Mágico',
                    'change_email' => 'Alterar E-mail',
                    'reset_password' => 'Redefinir Senha',
                    'reauthentication' => 'Reautenticação'
                ];
                
                $subject = $functionNames[$emailFunction] ?? 'E-mail do Sistema';
                
                $stmt = $db->prepare("
                    INSERT INTO safenode_mail_templates 
                    (project_id, name, subject, html_content, is_default)
                    VALUES (?, ?, ?, ?, 1)
                ");
                $stmt->execute([$projectId, $emailFunction, $subject, $htmlTemplate]);
            } else {
                // Criar templates padrão se não fornecido
                self::createDefaultTemplates($db, $projectId);
            }
            
            return [
                'success' => true,
                'project_id' => $projectId,
                'token' => $token
            ];
        } catch (PDOException $e) {
            error_log("MailService CreateProject Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao criar projeto: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cria templates padrão para um projeto
     */
    private static function createDefaultTemplates($db, $projectId) {
        $templates = [
            [
                'name' => 'verificacao-conta',
                'subject' => 'Verifique sua conta',
                'html_content' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .code { background: #f4f4f4; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; margin: 20px 0; }
        .button { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verificação de Conta</h2>
        <p>Olá {{nome}},</p>
        <p>Use o código abaixo para verificar sua conta:</p>
        <div class="code">{{codigo}}</div>
        <p>Ou clique no link abaixo:</p>
        <a href="{{link}}" class="button">Verificar Conta</a>
        <p>Este código expira em 10 minutos.</p>
    </div>
</body>
</html>',
                'variables' => json_encode(['nome', 'codigo', 'link'])
            ],
            [
                'name' => 'recuperacao-senha',
                'subject' => 'Recuperação de senha',
                'html_content' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { display: inline-block; padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Recuperação de Senha</h2>
        <p>Olá {{nome}},</p>
        <p>Você solicitou a recuperação de senha. Clique no botão abaixo para redefinir:</p>
        <a href="{{link}}" class="button">Redefinir Senha</a>
        <p>Este link expira em 1 hora.</p>
        <p>Se você não solicitou isso, ignore este e-mail.</p>
    </div>
</body>
</html>',
                'variables' => json_encode(['nome', 'link'])
            ],
            [
                'name' => 'aviso',
                'subject' => '{{assunto}}',
                'html_content' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>{{titulo}}</h2>
        <p>{{mensagem}}</p>
    </div>
</body>
</html>',
                'variables' => json_encode(['assunto', 'titulo', 'mensagem'])
            ]
        ];
        
        foreach ($templates as $template) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO safenode_mail_templates 
                    (project_id, name, subject, html_content, variables)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $projectId,
                    $template['name'],
                    $template['subject'],
                    $template['html_content'],
                    $template['variables']
                ]);
            } catch (PDOException $e) {
                error_log("MailService CreateTemplate Error: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Busca estatísticas do projeto
     */
    public function getStats() {
        if (!$this->project) {
            return null;
        }
        
        try {
            // Estatísticas de envios
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
                FROM safenode_mail_logs
                WHERE project_id = ?
            ");
            $stmt->execute([$this->projectId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Buscar envios de hoje
            $todaySent = $this->getTodaySentCount();
            $dailyLimit = 500;
            
            return [
                'monthly_limit' => $dailyLimit, // Mantido para compatibilidade, mas agora é diário
                'emails_sent_this_month' => $todaySent, // Mantido para compatibilidade, mas agora é diário
                'emails_remaining' => max(0, $dailyLimit - $todaySent),
                'total_sent' => $stats['sent'] ?? 0,
                'total_errors' => $stats['errors'] ?? 0,
                'total_logs' => $stats['total'] ?? 0
            ];
        } catch (PDOException $e) {
            error_log("MailService Stats Error: " . $e->getMessage());
            return null;
        }
    }
}


