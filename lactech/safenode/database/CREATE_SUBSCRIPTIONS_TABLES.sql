-- Tabela de Planos disponíveis
CREATE TABLE IF NOT EXISTS `safenode_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_key` varchar(50) NOT NULL COMMENT 'hobby, pro, enterprise',
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `billing_cycle` varchar(20) NOT NULL DEFAULT 'monthly' COMMENT 'monthly, yearly',
  `features` json DEFAULT NULL COMMENT 'Lista de features em JSON',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_key` (`plan_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir planos padrão
INSERT INTO `safenode_plans` (`plan_key`, `name`, `price`, `billing_cycle`, `features`, `is_active`) VALUES
('hobby', 'Hobby', 0.00, 'monthly', '["Proteção DDoS Básica", "CDN Global", "SSL Gratuito"]', 1),
('pro', 'Pro', 99.00, 'monthly', '["Tudo do Hobby", "WAF Avançado", "Otimização de Imagens", "Analytics em Tempo Real"]', 1),
('enterprise', 'Enterprise', 0.00, 'monthly', '["SLA de 100%", "Suporte 24/7 Dedicado", "Logs Raw", "Single Sign-On (SSO)"]', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `price` = VALUES(`price`);

-- Tabela de Assinaturas dos usuários
CREATE TABLE IF NOT EXISTS `safenode_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `asaas_subscription_id` varchar(100) DEFAULT NULL COMMENT 'ID da assinatura na Asaas',
  `status` varchar(50) NOT NULL DEFAULT 'pending' COMMENT 'pending, active, cancelled, expired, suspended',
  `billing_cycle` varchar(20) NOT NULL DEFAULT 'monthly',
  `current_period_start` date DEFAULT NULL,
  `current_period_end` date DEFAULT NULL,
  `cancel_at_period_end` tinyint(1) NOT NULL DEFAULT 0,
  `cancelled_at` datetime DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `plan_id` (`plan_id`),
  KEY `status` (`status`),
  KEY `asaas_subscription_id` (`asaas_subscription_id`),
  CONSTRAINT `fk_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_subscriptions_plan` FOREIGN KEY (`plan_id`) REFERENCES `safenode_plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_key` varchar(50) NOT NULL COMMENT 'hobby, pro, enterprise',
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `billing_cycle` varchar(20) NOT NULL DEFAULT 'monthly' COMMENT 'monthly, yearly',
  `features` json DEFAULT NULL COMMENT 'Lista de features em JSON',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_key` (`plan_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir planos padrão
INSERT INTO `safenode_plans` (`plan_key`, `name`, `price`, `billing_cycle`, `features`, `is_active`) VALUES
('hobby', 'Hobby', 0.00, 'monthly', '["Proteção DDoS Básica", "CDN Global", "SSL Gratuito"]', 1),
('pro', 'Pro', 99.00, 'monthly', '["Tudo do Hobby", "WAF Avançado", "Otimização de Imagens", "Analytics em Tempo Real"]', 1),
('enterprise', 'Enterprise', 0.00, 'monthly', '["SLA de 100%", "Suporte 24/7 Dedicado", "Logs Raw", "Single Sign-On (SSO)"]', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `price` = VALUES(`price`);

-- Tabela de Assinaturas dos usuários
CREATE TABLE IF NOT EXISTS `safenode_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `asaas_subscription_id` varchar(100) DEFAULT NULL COMMENT 'ID da assinatura na Asaas',
  `status` varchar(50) NOT NULL DEFAULT 'pending' COMMENT 'pending, active, cancelled, expired, suspended',
  `billing_cycle` varchar(20) NOT NULL DEFAULT 'monthly',
  `current_period_start` date DEFAULT NULL,
  `current_period_end` date DEFAULT NULL,
  `cancel_at_period_end` tinyint(1) NOT NULL DEFAULT 0,
  `cancelled_at` datetime DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `plan_id` (`plan_id`),
  KEY `status` (`status`),
  KEY `asaas_subscription_id` (`asaas_subscription_id`),
  CONSTRAINT `fk_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_subscriptions_plan` FOREIGN KEY (`plan_id`) REFERENCES `safenode_plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

