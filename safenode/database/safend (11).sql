-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 07/01/2026 às 03:54
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `safend`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_blocked_ips`
--

CREATE TABLE `safenode_blocked_ips` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `threat_type` varchar(50) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `blocked_by` varchar(50) DEFAULT 'system',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_firewall_rules`
--

CREATE TABLE `safenode_firewall_rules` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `priority` int(11) DEFAULT 0,
  `match_type` varchar(32) NOT NULL,
  `match_value` varchar(255) NOT NULL,
  `action` enum('block','allow','log') DEFAULT 'block',
  `is_active` tinyint(1) DEFAULT 1,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_human_verification_logs`
--

CREATE TABLE `safenode_human_verification_logs` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `event_type` enum('human_validated','bot_blocked','access_allowed','challenge_shown') NOT NULL,
  `request_uri` text DEFAULT NULL,
  `request_method` varchar(10) DEFAULT 'GET',
  `user_agent` text DEFAULT NULL,
  `referer` text DEFAULT NULL,
  `country_code` char(2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_hv_api_keys`
--

CREATE TABLE `safenode_hv_api_keys` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `api_secret` varchar(64) NOT NULL,
  `name` varchar(255) DEFAULT 'Verificação Humana',
  `allowed_domains` text DEFAULT NULL COMMENT 'Domínios permitidos separados por vírgula',
  `rate_limit_per_minute` int(11) DEFAULT 60 COMMENT 'Limite de requisições por minuto',
  `max_token_age` int(11) DEFAULT 3600 COMMENT 'Idade máxima do token em segundos (padrão: 1 hora)',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_used_at` timestamp NULL DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_hv_attempts`
--

CREATE TABLE `safenode_hv_attempts` (
  `id` int(11) NOT NULL,
  `api_key_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `referer` text DEFAULT NULL,
  `attempt_type` enum('init','validate','failed','suspicious') NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_hv_rate_limits`
--

CREATE TABLE `safenode_hv_rate_limits` (
  `id` int(11) NOT NULL,
  `api_key_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `request_count` int(11) DEFAULT 1,
  `window_start` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_otp_codes`
--

CREATE TABLE `safenode_otp_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `action` varchar(50) DEFAULT 'email_verification',
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verified` tinyint(1) DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `attempts` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_password_reset_otp`
--

CREATE TABLE `safenode_password_reset_otp` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL COMMENT 'Código OTP de 6 dígitos',
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Data de expiração do OTP',
  `used_at` timestamp NULL DEFAULT NULL COMMENT 'Data em que o OTP foi usado',
  `attempts` int(11) DEFAULT 0 COMMENT 'Número de tentativas de validação',
  `max_attempts` int(11) DEFAULT 5 COMMENT 'Máximo de tentativas permitidas',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP que solicitou o reset',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_settings`
--

CREATE TABLE `safenode_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(20) DEFAULT 'string',
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general',
  `is_editable` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_sites`
--

CREATE TABLE `safenode_sites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `domain` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `cloudflare_zone_id` varchar(100) DEFAULT NULL,
  `cloudflare_status` varchar(50) DEFAULT 'active',
  `ssl_status` varchar(50) DEFAULT 'pending',
  `security_level` varchar(50) DEFAULT 'medium',
  `auto_block` tinyint(1) DEFAULT 1,
  `rate_limit_enabled` tinyint(1) DEFAULT 1,
  `threat_detection_enabled` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `verification_token` varchar(64) DEFAULT NULL,
  `verification_status` varchar(20) DEFAULT 'pending',
  `geo_allow_only` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_site_geo_rules`
--

CREATE TABLE `safenode_site_geo_rules` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `country_code` char(2) NOT NULL,
  `action` enum('block','allow') DEFAULT 'block',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_users`
--

CREATE TABLE `safenode_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'user',
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `avatar_updated_at` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_user_sessions`
--

CREATE TABLE `safenode_user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(64) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` varchar(50) DEFAULT 'unknown' COMMENT 'desktop, mobile, tablet',
  `browser` varchar(100) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_subscriptions`
--

CREATE TABLE `safenode_subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_type` enum('free_trial','paid') NOT NULL DEFAULT 'free_trial',
  `events_limit` int(11) NOT NULL DEFAULT 10000,
  `events_used` int(11) NOT NULL DEFAULT 0,
  `billing_cycle_start` date NOT NULL,
  `billing_cycle_end` date NOT NULL,
  `status` enum('active','cancelled','expired','trial_expired') NOT NULL DEFAULT 'active',
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_whitelist`
--

CREATE TABLE `safenode_whitelist` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `added_by` varchar(50) DEFAULT 'admin',
  `added_at` timestamp NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `safenode_blocked_ips`
--
ALTER TABLE `safenode_blocked_ips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_threat_type` (`threat_type`),
  ADD KEY `idx_blocked_ip_expires` (`ip_address`,`expires_at`,`is_active`),
  ADD KEY `idx_blocked_threat_type` (`threat_type`,`is_active`);

--
-- Índices de tabela `safenode_firewall_rules`
--
ALTER TABLE `safenode_firewall_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_site_priority` (`site_id`,`priority`),
  ADD KEY `idx_firewall_site_active` (`site_id`,`is_active`,`priority`);

--
-- Índices de tabela `safenode_human_verification_logs`
--
ALTER TABLE `safenode_human_verification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_site_id` (`site_id`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_site_event_created` (`site_id`,`event_type`,`created_at`),
  ADD KEY `idx_ip_created` (`ip_address`,`created_at`);

--
-- Índices de tabela `safenode_hv_api_keys`
--
ALTER TABLE `safenode_hv_api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_key` (`api_key`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_active` (`is_active`);

--
-- Índices de tabela `safenode_hv_attempts`
--
ALTER TABLE `safenode_hv_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `api_key_id` (`api_key_id`),
  ADD KEY `ip_address` (`ip_address`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `attempt_type` (`attempt_type`);

--
-- Índices de tabela `safenode_hv_rate_limits`
--
ALTER TABLE `safenode_hv_rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_key_ip_window` (`api_key_id`,`ip_address`,`window_start`),
  ADD KEY `api_key_id` (`api_key_id`),
  ADD KEY `ip_address` (`ip_address`),
  ADD KEY `window_start` (`window_start`);

--
-- Índices de tabela `safenode_otp_codes`
--
ALTER TABLE `safenode_otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_otp_code` (`otp_code`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_verified` (`verified`),
  ADD KEY `idx_email_action` (`email`,`action`);

--
-- Índices de tabela `safenode_password_reset_otp`
--
ALTER TABLE `safenode_password_reset_otp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_otp_code` (`otp_code`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_used` (`used_at`),
  ADD KEY `idx_otp_valid` (`otp_code`,`expires_at`,`used_at`,`attempts`),
  ADD KEY `idx_otp_lookup` (`email`,`otp_code`,`expires_at`,`used_at`,`attempts`);

--
-- Índices de tabela `safenode_settings`
--
ALTER TABLE `safenode_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_key` (`setting_key`),
  ADD KEY `idx_category` (`category`);

--
-- Índices de tabela `safenode_sites`
--
ALTER TABLE `safenode_sites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_domain` (`domain`),
  ADD KEY `idx_domain` (`domain`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_cloudflare_zone` (`cloudflare_zone_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_sites_domain_active` (`domain`,`is_active`);

--
-- Índices de tabela `safenode_site_geo_rules`
--
ALTER TABLE `safenode_site_geo_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `site_country` (`site_id`,`country_code`);

--
-- Índices de tabela `safenode_users`
--
ALTER TABLE `safenode_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD UNIQUE KEY `idx_google_id` (`google_id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_email_verified` (`email_verified`);

--
-- Índices de tabela `safenode_user_sessions`
--
ALTER TABLE `safenode_user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- Índices de tabela `safenode_subscriptions`
--
ALTER TABLE `safenode_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `billing_cycle` (`billing_cycle_start`,`billing_cycle_end`);

--
-- Índices de tabela `safenode_whitelist`
--
ALTER TABLE `safenode_whitelist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ip` (`ip_address`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_whitelist_ip_active` (`ip_address`,`is_active`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `safenode_blocked_ips`
--
ALTER TABLE `safenode_blocked_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_firewall_rules`
--
ALTER TABLE `safenode_firewall_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_human_verification_logs`
--
ALTER TABLE `safenode_human_verification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_hv_api_keys`
--
ALTER TABLE `safenode_hv_api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_hv_attempts`
--
ALTER TABLE `safenode_hv_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_hv_rate_limits`
--
ALTER TABLE `safenode_hv_rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_otp_codes`
--
ALTER TABLE `safenode_otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_password_reset_otp`
--
ALTER TABLE `safenode_password_reset_otp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_settings`
--
ALTER TABLE `safenode_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_sites`
--
ALTER TABLE `safenode_sites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_site_geo_rules`
--
ALTER TABLE `safenode_site_geo_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_users`
--
ALTER TABLE `safenode_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_user_sessions`
--
ALTER TABLE `safenode_user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_subscriptions`
--
ALTER TABLE `safenode_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_whitelist`
--
ALTER TABLE `safenode_whitelist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `safenode_firewall_rules`
--
ALTER TABLE `safenode_firewall_rules`
  ADD CONSTRAINT `safenode_firewall_rules_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_human_verification_logs`
--
ALTER TABLE `safenode_human_verification_logs`
  ADD CONSTRAINT `safenode_human_verification_logs_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_hv_api_keys`
--
ALTER TABLE `safenode_hv_api_keys`
  ADD CONSTRAINT `safenode_hv_api_keys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_otp_codes`
--
ALTER TABLE `safenode_otp_codes`
  ADD CONSTRAINT `safenode_otp_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_password_reset_otp`
--
ALTER TABLE `safenode_password_reset_otp`
  ADD CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_sites`
--
ALTER TABLE `safenode_sites`
  ADD CONSTRAINT `safenode_sites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `safenode_site_geo_rules`
--
ALTER TABLE `safenode_site_geo_rules`
  ADD CONSTRAINT `safenode_site_geo_rules_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_user_sessions`
--
ALTER TABLE `safenode_user_sessions`
  ADD CONSTRAINT `safenode_user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_subscriptions`
--
ALTER TABLE `safenode_subscriptions`
  ADD CONSTRAINT `safenode_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------

--
-- Dados de desenvolvimento (usuário de teste)
--

-- Usuário de desenvolvimento
-- Email: dev@safenode.local
-- Senha: dev123456
-- Hash gerado com password_hash('dev123456', PASSWORD_DEFAULT)
INSERT INTO `safenode_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `is_active`, `email_verified`, `email_verified_at`, `created_at`) VALUES
(1, 'dev', 'dev@safenode.local', '$2y$10$1yrihh9u05UKvbyidf6LWuxmR0FKCpvG1QfeHIlIzq44PDUeDxTre', 'Desenvolvedor', 'user', 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`), `email` = VALUES(`email`);

-- Subscription de desenvolvimento (free trial de 14 dias)
INSERT INTO `safenode_subscriptions` (`user_id`, `plan_type`, `events_limit`, `events_used`, `billing_cycle_start`, `billing_cycle_end`, `status`, `created_at`) VALUES
(1, 'free_trial', 10000, 0, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'active', NOW())
ON DUPLICATE KEY UPDATE `plan_type` = VALUES(`plan_type`), `events_limit` = VALUES(`events_limit`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
