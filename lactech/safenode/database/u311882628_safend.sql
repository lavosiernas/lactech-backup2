-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 19/11/2025 às 19:05
-- Versão do servidor: 11.8.3-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u311882628_safend`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_alerts`
--

CREATE TABLE `safenode_alerts` (
  `id` int(11) NOT NULL,
  `alert_type` varchar(50) NOT NULL,
  `alert_level` varchar(20) DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `threat_count` int(11) DEFAULT 1,
  `is_read` tinyint(1) DEFAULT 0,
  `is_resolved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_blocked_ips`
--

CREATE TABLE `safenode_blocked_ips` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `threat_type` varchar(50) DEFAULT 'suspicious',
  `blocked_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `blocked_by` varchar(50) DEFAULT 'system',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_otp_codes`
--

CREATE TABLE `safenode_otp_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `action` varchar(50) DEFAULT 'email_verification',
  `expires_at` timestamp NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `attempts` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_rate_limits`
--

CREATE TABLE `safenode_rate_limits` (
  `id` int(11) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `identifier_type` varchar(20) DEFAULT 'ip',
  `endpoint` varchar(255) NOT NULL,
  `request_count` int(11) DEFAULT 1,
  `window_start` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `max_requests` int(11) DEFAULT 10,
  `is_blocked` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_security_logs`
--

CREATE TABLE `safenode_security_logs` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `request_uri` text NOT NULL,
  `request_method` varchar(10) NOT NULL,
  `request_headers` text DEFAULT NULL,
  `request_body` text DEFAULT NULL,
  `threat_type` varchar(50) NOT NULL,
  `threat_details` text DEFAULT NULL,
  `threat_score` int(11) DEFAULT 0,
  `action_taken` varchar(50) NOT NULL,
  `response_code` int(11) DEFAULT 200,
  `cloudflare_ray` varchar(100) DEFAULT NULL,
  `cloudflare_country` varchar(2) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `safenode_settings`
--

INSERT INTO `safenode_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `category`, `is_editable`, `created_at`, `updated_at`) VALUES
(1, 'enabled', '1', 'boolean', 'Habilitar/Desabilitar SafeNode', 'general', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(2, 'mode', 'production', 'string', 'Modo de operação: production, development, testing', 'general', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(3, 'auto_block', '1', 'boolean', 'Bloquear IPs automaticamente quando detectar ameaças', 'detection', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(4, 'block_duration', '3600', 'integer', 'Duração do bloqueio em segundos (padrão: 1 hora)', 'detection', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(5, 'login_max_attempts', '5', 'integer', 'Máximo de tentativas de login antes de bloquear', 'rate_limit', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(6, 'login_window', '300', 'integer', 'Janela de tempo para tentativas de login em segundos (5 minutos)', 'rate_limit', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(7, 'api_rate_limit', '100', 'integer', 'Limite de requisições por minuto para API', 'rate_limit', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(8, 'api_rate_window', '60', 'integer', 'Janela de tempo para rate limit da API em segundos', 'rate_limit', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(9, 'threat_score_threshold', '70', 'integer', 'Score mínimo para considerar ameaça crítica (0-100)', 'detection', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(10, 'log_retention_days', '30', 'integer', 'Dias para manter logs antes de arquivar', 'general', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(11, 'cloudflare_sync', '1', 'boolean', 'Sincronizar bloqueios com Cloudflare', 'cloudflare', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(12, 'cloudflare_zone_id', '', 'string', 'Zone ID do Cloudflare', 'cloudflare', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(13, 'cloudflare_api_token', '', 'string', 'API Token do Cloudflare', 'cloudflare', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(14, 'alert_email', '', 'string', 'Email para receber alertas críticos', 'general', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(15, 'alert_threshold', '10', 'integer', 'Número de ameaças por hora para enviar alerta', 'general', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(16, 'enable_whitelist', '1', 'boolean', 'Habilitar sistema de whitelist', 'general', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(17, 'enable_statistics', '1', 'boolean', 'Coletar estatísticas para dashboard', 'general', 1, '2025-11-19 02:46:53', '2025-11-19 02:46:53');

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_sites`
--

CREATE TABLE `safenode_sites` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_statistics`
--

CREATE TABLE `safenode_statistics` (
  `id` int(11) NOT NULL,
  `stat_date` date NOT NULL,
  `stat_hour` int(11) NOT NULL,
  `total_requests` int(11) DEFAULT 0,
  `blocked_requests` int(11) DEFAULT 0,
  `allowed_requests` int(11) DEFAULT 0,
  `sql_injection_count` int(11) DEFAULT 0,
  `xss_count` int(11) DEFAULT 0,
  `brute_force_count` int(11) DEFAULT 0,
  `rate_limit_count` int(11) DEFAULT 0,
  `unique_ips` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_threat_patterns`
--

CREATE TABLE `safenode_threat_patterns` (
  `id` int(11) NOT NULL,
  `pattern_name` varchar(100) NOT NULL,
  `pattern_regex` text NOT NULL,
  `threat_type` varchar(50) NOT NULL,
  `severity` varchar(20) DEFAULT 'medium',
  `is_active` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `safenode_threat_patterns`
--

INSERT INTO `safenode_threat_patterns` (`id`, `pattern_name`, `pattern_regex`, `threat_type`, `severity`, `is_active`, `description`, `created_at`, `updated_at`) VALUES
(1, 'SQL Injection - UNION', '(?i)(unions+(alls+)?select)', 'sql_injection', 'critical', 1, 'Detecta tentativas de SQL Injection usando UNION SELECT', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(2, 'SQL Injection - OR 1=1', '(?i)(sors+[\'\"]?d+[\'\"]?s*=s*[\'\"]?d+)', 'sql_injection', 'critical', 1, 'Detecta padrão OR 1=1 em SQL Injection', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(3, 'SQL Injection - DROP TABLE', '(?i)(drops+table)', 'sql_injection', 'critical', 1, 'Detecta tentativas de DROP TABLE', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(4, 'SQL Injection - DELETE FROM', '(?i)(deletes+from)', 'sql_injection', 'high', 1, 'Detecta tentativas de DELETE FROM', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(5, 'SQL Injection - INSERT INTO', '(?i)(inserts+into)', 'sql_injection', 'high', 1, 'Detecta tentativas de INSERT INTO', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(6, 'SQL Injection - UPDATE SET', '(?i)(updates+w+s+set)', 'sql_injection', 'high', 1, 'Detecta tentativas de UPDATE SET', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(7, 'XSS - Script Tag', '(?i)(<script[^>]*>.*?</script>)', 'xss', 'critical', 1, 'Detecta tags <script> maliciosas', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(8, 'XSS - JavaScript Event', '(?i)(onw+s*=s*[\'\"])', 'xss', 'high', 1, 'Detecta eventos JavaScript inline', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(9, 'XSS - JavaScript Protocol', '(?i)(javascripts*:)', 'xss', 'high', 1, 'Detecta protocolo javascript:', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(10, 'XSS - Iframe', '(?i)(<iframe[^>]*>)', 'xss', 'medium', 1, 'Detecta tags <iframe> suspeitas', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(11, 'Path Traversal - ../', '(../|..\\\\)', 'path_traversal', 'high', 1, 'Detecta tentativas de path traversal', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(12, 'Path Traversal - Absolute Path', '(?i)(/etc/|/var/|/usr/|c:\\\\windows)', 'path_traversal', 'high', 1, 'Detecta caminhos absolutos suspeitos', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(13, 'Command Injection - Pipe', '(||||&|&&|;)', 'command_injection', 'high', 1, 'Detecta caracteres de pipe e comandos', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(14, 'Command Injection - System Commands', '(?i)(system|exec|shell_exec|passthru|proc_open)', 'command_injection', 'critical', 1, 'Detecta funções de sistema PHP', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(15, 'Suspicious - PHP Code', '(?i)(<?php|<?)', 'suspicious_pattern', 'high', 1, 'Detecta código PHP em requisições', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(16, 'Suspicious - Base64', '(?i)([A-Za-z0-9+/]{100,}={0,2})', 'suspicious_pattern', 'medium', 1, 'Detecta strings Base64 suspeitas', '2025-11-19 02:46:53', '2025-11-19 02:46:53'),
(17, 'Suspicious - Hex Encoding', '(?i)(\\x[0-9a-f]{2})', 'suspicious_pattern', 'medium', 1, 'Detecta codificação hexadecimal suspeita', '2025-11-19 02:46:53', '2025-11-19 02:46:53');

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
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `safenode_users`
--

INSERT INTO `safenode_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `is_active`, `email_verified`, `email_verified_at`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@safenode.cloud', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador SafeNode', 'admin', 1, 1, '2025-11-19 02:55:43', NULL, '2025-11-19 02:55:43', '2025-11-19 02:55:43');

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

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_safenode_active_blocks`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_safenode_active_blocks` (
`ip_address` varchar(45)
,`reason` varchar(255)
,`threat_type` varchar(50)
,`blocked_at` timestamp
,`expires_at` timestamp
,`seconds_remaining` bigint(21)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_safenode_today_stats`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_safenode_today_stats` (
`total_requests` bigint(21)
,`blocked_requests` decimal(22,0)
,`allowed_requests` decimal(22,0)
,`unique_ips` bigint(21)
,`sql_injection_count` decimal(22,0)
,`xss_count` decimal(22,0)
,`brute_force_count` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_safenode_top_blocked_ips`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_safenode_top_blocked_ips` (
`ip_address` varchar(45)
,`block_count` bigint(21)
,`last_blocked` timestamp
,`threat_types` longtext
);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `safenode_alerts`
--
ALTER TABLE `safenode_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`alert_type`),
  ADD KEY `idx_level` (`alert_level`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Índices de tabela `safenode_blocked_ips`
--
ALTER TABLE `safenode_blocked_ips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_threat_type` (`threat_type`);

--
-- Índices de tabela `safenode_otp_codes`
--
ALTER TABLE `safenode_otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_otp_code` (`otp_code`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_verified` (`verified`);

--
-- Índices de tabela `safenode_rate_limits`
--
ALTER TABLE `safenode_rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_identifier_endpoint` (`identifier`,`endpoint`,`window_start`),
  ADD KEY `idx_identifier` (`identifier`),
  ADD KEY `idx_endpoint` (`endpoint`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_blocked` (`is_blocked`),
  ADD KEY `idx_rate_limit_lookup` (`identifier`,`endpoint`,`expires_at`);

--
-- Índices de tabela `safenode_security_logs`
--
ALTER TABLE `safenode_security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_threat_type` (`threat_type`),
  ADD KEY `idx_action` (`action_taken`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_threat_score` (`threat_score`),
  ADD KEY `idx_logs_ip_date` (`ip_address`,`created_at`),
  ADD KEY `idx_logs_threat_date` (`threat_type`,`created_at`);

--
-- Índices de tabela `safenode_settings`
--
ALTER TABLE `safenode_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
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
  ADD KEY `idx_cloudflare_zone` (`cloudflare_zone_id`);

--
-- Índices de tabela `safenode_statistics`
--
ALTER TABLE `safenode_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date_hour` (`stat_date`,`stat_hour`),
  ADD KEY `idx_date` (`stat_date`);

--
-- Índices de tabela `safenode_threat_patterns`
--
ALTER TABLE `safenode_threat_patterns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`threat_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_active` (`is_active`);

--
-- Índices de tabela `safenode_users`
--
ALTER TABLE `safenode_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_username` (`username`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_email_verified` (`email_verified`);

--
-- Índices de tabela `safenode_whitelist`
--
ALTER TABLE `safenode_whitelist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ip` (`ip_address`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_active` (`is_active`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `safenode_alerts`
--
ALTER TABLE `safenode_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_blocked_ips`
--
ALTER TABLE `safenode_blocked_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_otp_codes`
--
ALTER TABLE `safenode_otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_rate_limits`
--
ALTER TABLE `safenode_rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_security_logs`
--
ALTER TABLE `safenode_security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_settings`
--
ALTER TABLE `safenode_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `safenode_sites`
--
ALTER TABLE `safenode_sites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_statistics`
--
ALTER TABLE `safenode_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_threat_patterns`
--
ALTER TABLE `safenode_threat_patterns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `safenode_users`
--
ALTER TABLE `safenode_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `safenode_whitelist`
--
ALTER TABLE `safenode_whitelist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Estrutura para view `v_safenode_active_blocks`
--
DROP TABLE IF EXISTS `v_safenode_active_blocks`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u311882628_xandria`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_safenode_active_blocks`  AS SELECT `safenode_blocked_ips`.`ip_address` AS `ip_address`, `safenode_blocked_ips`.`reason` AS `reason`, `safenode_blocked_ips`.`threat_type` AS `threat_type`, `safenode_blocked_ips`.`blocked_at` AS `blocked_at`, `safenode_blocked_ips`.`expires_at` AS `expires_at`, timestampdiff(SECOND,current_timestamp(),`safenode_blocked_ips`.`expires_at`) AS `seconds_remaining` FROM `safenode_blocked_ips` WHERE `safenode_blocked_ips`.`is_active` = 1 AND (`safenode_blocked_ips`.`expires_at` is null OR `safenode_blocked_ips`.`expires_at` > current_timestamp()) ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_safenode_today_stats`
--
DROP TABLE IF EXISTS `v_safenode_today_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u311882628_xandria`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_safenode_today_stats`  AS SELECT count(0) AS `total_requests`, sum(case when `safenode_security_logs`.`action_taken` = 'blocked' then 1 else 0 end) AS `blocked_requests`, sum(case when `safenode_security_logs`.`action_taken` = 'allowed' then 1 else 0 end) AS `allowed_requests`, count(distinct `safenode_security_logs`.`ip_address`) AS `unique_ips`, sum(case when `safenode_security_logs`.`threat_type` = 'sql_injection' then 1 else 0 end) AS `sql_injection_count`, sum(case when `safenode_security_logs`.`threat_type` = 'xss' then 1 else 0 end) AS `xss_count`, sum(case when `safenode_security_logs`.`threat_type` = 'brute_force' then 1 else 0 end) AS `brute_force_count` FROM `safenode_security_logs` WHERE cast(`safenode_security_logs`.`created_at` as date) = curdate() ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_safenode_top_blocked_ips`
--
DROP TABLE IF EXISTS `v_safenode_top_blocked_ips`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u311882628_xandria`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_safenode_top_blocked_ips`  AS SELECT `safenode_security_logs`.`ip_address` AS `ip_address`, count(0) AS `block_count`, max(`safenode_security_logs`.`created_at`) AS `last_blocked`, group_concat(distinct `safenode_security_logs`.`threat_type` separator ',') AS `threat_types` FROM `safenode_security_logs` WHERE `safenode_security_logs`.`action_taken` = 'blocked' AND `safenode_security_logs`.`created_at` >= current_timestamp() - interval 7 day GROUP BY `safenode_security_logs`.`ip_address` ORDER BY count(0) DESC LIMIT 0, 100 ;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `safenode_otp_codes`
--
ALTER TABLE `safenode_otp_codes`
  ADD CONSTRAINT `safenode_otp_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
