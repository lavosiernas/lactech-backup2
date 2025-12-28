-- SafeNode Mail - Estrutura de Banco de Dados
-- Tabelas para o sistema de envio de e-mails

-- Tabela de projetos de e-mail
CREATE TABLE IF NOT EXISTS `safenode_mail_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Nome do projeto',
  `sender_email` varchar(255) NOT NULL COMMENT 'E-mail remetente',
  `sender_name` varchar(255) DEFAULT NULL COMMENT 'Nome do remetente',
  `token` varchar(64) NOT NULL COMMENT 'Token de autenticação',
  `monthly_limit` int(11) DEFAULT 500 COMMENT 'Limite mensal de e-mails',
  `emails_sent_this_month` int(11) DEFAULT 0 COMMENT 'E-mails enviados este mês',
  `last_reset_date` date DEFAULT NULL COMMENT 'Data do último reset mensal',
  `rate_limit_per_minute` int(11) DEFAULT 5 COMMENT 'Rate limit por minuto',
  `email_function` varchar(50) DEFAULT NULL COMMENT 'Função do e-mail (confirm_signup, invite_user, magic_link, etc)',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Projeto ativo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `safenode_mail_projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs de envio
CREATE TABLE IF NOT EXISTS `safenode_mail_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `to_email` varchar(255) NOT NULL COMMENT 'E-mail destinatário',
  `subject` varchar(500) NOT NULL COMMENT 'Assunto do e-mail',
  `template_name` varchar(100) DEFAULT NULL COMMENT 'Nome do template usado',
  `status` enum('sent','error','pending') DEFAULT 'pending' COMMENT 'Status do envio',
  `error_message` text DEFAULT NULL COMMENT 'Mensagem de erro (se houver)',
  `sent_at` timestamp NULL DEFAULT NULL COMMENT 'Data/hora do envio',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  KEY `to_email` (`to_email`),
  CONSTRAINT `safenode_mail_logs_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `safenode_mail_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de templates
CREATE TABLE IF NOT EXISTS `safenode_mail_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Nome do template',
  `subject` varchar(500) NOT NULL COMMENT 'Assunto padrão',
  `html_content` text NOT NULL COMMENT 'Conteúdo HTML do template',
  `text_content` text DEFAULT NULL COMMENT 'Conteúdo texto alternativo',
  `variables` text DEFAULT NULL COMMENT 'JSON com variáveis disponíveis',
  `is_default` tinyint(1) DEFAULT 0 COMMENT 'Template padrão do projeto',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `name` (`name`),
  CONSTRAINT `safenode_mail_templates_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `safenode_mail_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de rate limiting (controle de envios por minuto)
CREATE TABLE IF NOT EXISTS `safenode_mail_rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `minute_window` timestamp NOT NULL COMMENT 'Janela de 1 minuto',
  `emails_count` int(11) DEFAULT 0 COMMENT 'Contador de e-mails neste minuto',
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_minute` (`project_id`, `minute_window`),
  KEY `minute_window` (`minute_window`),
  CONSTRAINT `safenode_mail_rate_limits_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `safenode_mail_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de webhooks
CREATE TABLE IF NOT EXISTS `safenode_mail_webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `url` varchar(500) NOT NULL COMMENT 'URL do webhook',
  `events` text DEFAULT NULL COMMENT 'JSON com eventos a escutar (sent, error, etc)',
  `secret` varchar(64) DEFAULT NULL COMMENT 'Secret para assinatura do webhook',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Webhook ativo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `safenode_mail_webhooks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `safenode_mail_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de analytics (métricas detalhadas)
CREATE TABLE IF NOT EXISTS `safenode_mail_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `date` date NOT NULL COMMENT 'Data da métrica',
  `emails_sent` int(11) DEFAULT 0 COMMENT 'E-mails enviados neste dia',
  `emails_delivered` int(11) DEFAULT 0 COMMENT 'E-mails entregues',
  `emails_opened` int(11) DEFAULT 0 COMMENT 'E-mails abertos',
  `emails_clicked` int(11) DEFAULT 0 COMMENT 'E-mails com cliques',
  `emails_bounced` int(11) DEFAULT 0 COMMENT 'E-mails com bounce',
  `emails_complained` int(11) DEFAULT 0 COMMENT 'E-mails marcados como spam',
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_date` (`project_id`, `date`),
  KEY `date` (`date`),
  CONSTRAINT `safenode_mail_analytics_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `safenode_mail_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir templates padrão (serão criados por projeto via código)
-- Templates serão criados dinamicamente quando um projeto for criado


