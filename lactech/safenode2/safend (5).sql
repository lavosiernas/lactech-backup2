-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 02/12/2025 às 22:59
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
-- Estrutura para tabela `safenode_2fa_attempts`
--

CREATE TABLE `safenode_2fa_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_code` varchar(6) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_activity_log`
--

CREATE TABLE `safenode_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL COMMENT 'login, logout, password_change, profile_update, etc',
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` varchar(50) DEFAULT 'unknown',
  `browser` varchar(100) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dados adicionais da ação em formato JSON',
  `status` varchar(20) DEFAULT 'success' COMMENT 'success, failed, warning',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `safenode_activity_log`
--

INSERT INTO `safenode_activity_log` (`id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `device_type`, `browser`, `os`, `metadata`, `status`, `created_at`) VALUES
(5, 10, '2fa_enabled', 'Autenticação de dois fatores ativada', '170.84.77.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop', 'Chrome', 'Windows', NULL, 'success', '2025-11-22 21:57:21');

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
-- Estrutura para tabela `safenode_incidents`
--

CREATE TABLE `safenode_incidents` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `threat_type` varchar(50) DEFAULT NULL,
  `site_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'open',
  `first_seen` timestamp NULL DEFAULT current_timestamp(),
  `last_seen` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_events` int(11) DEFAULT 1,
  `critical_events` int(11) DEFAULT 0,
  `highest_score` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_ip_reputation`
--

CREATE TABLE `safenode_ip_reputation` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `trust_score` int(11) DEFAULT 50 COMMENT '0-100, 0=muito suspeito, 100=muito confiável',
  `total_requests` int(11) DEFAULT 0,
  `blocked_requests` int(11) DEFAULT 0,
  `allowed_requests` int(11) DEFAULT 0,
  `challenged_requests` int(11) DEFAULT 0,
  `first_seen` datetime DEFAULT current_timestamp(),
  `last_seen` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `country_code` char(2) DEFAULT NULL,
  `is_whitelisted` tinyint(1) DEFAULT 0,
  `is_blacklisted` tinyint(1) DEFAULT 0,
  `threat_score_avg` decimal(5,2) DEFAULT 0.00,
  `threat_score_max` int(11) DEFAULT 0,
  `last_threat_type` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL
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

--
-- Despejando dados para a tabela `safenode_otp_codes`
--

INSERT INTO `safenode_otp_codes` (`id`, `user_id`, `email`, `otp_code`, `action`, `expires_at`, `verified`, `verified_at`, `attempts`, `created_at`) VALUES
(3, 4, 'joselucenadev@gmail.com', '306238', 'email_verification', '2025-11-21 14:31:58', 1, '2025-11-21 14:31:58', 0, '2025-11-21 14:31:28'),
(10, NULL, 'lavosiersilva02@gmail.com', '343528', 'email_verification', '2025-11-24 15:21:02', 0, NULL, 0, '2025-11-24 15:11:02');

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_rate_limits`
--

CREATE TABLE `safenode_rate_limits` (
  `id` int(11) NOT NULL,
  `identifier` varchar(255) DEFAULT '*' COMMENT 'Identificador (IP, user_id, etc) ou * para todos',
  `identifier_type` varchar(20) DEFAULT 'ip',
  `endpoint` varchar(255) DEFAULT '*' COMMENT 'Endpoint específico ou * para todos',
  `max_requests` int(11) DEFAULT 100 COMMENT 'Máximo de requisições permitidas',
  `time_window` int(11) DEFAULT 60 COMMENT 'Janela de tempo em segundos',
  `priority` int(11) DEFAULT 0 COMMENT 'Prioridade (maior = mais importante)',
  `is_active` tinyint(1) DEFAULT 1,
  `action` varchar(50) DEFAULT 'block' COMMENT 'Ação a tomar: block, log, warn',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_rate_limits_violations`
--

CREATE TABLE `safenode_rate_limits_violations` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `rate_limit_id` int(11) DEFAULT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `request_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
  `threat_type` varchar(50) DEFAULT NULL,
  `threat_details` text DEFAULT NULL,
  `threat_score` int(11) DEFAULT 0,
  `action_taken` varchar(50) NOT NULL,
  `response_code` int(11) DEFAULT 200,
  `response_time` decimal(10,2) DEFAULT NULL COMMENT 'Tempo de resposta em milissegundos',
  `cloudflare_ray` varchar(100) DEFAULT NULL,
  `cloudflare_country` varchar(2) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `site_id` int(11) DEFAULT NULL COMMENT 'ID do site relacionado',
  `country_code` char(2) DEFAULT NULL,
  `referer` text DEFAULT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `safenode_settings`
--

INSERT INTO `safenode_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `category`, `is_editable`, `created_at`, `updated_at`) VALUES
(1, 'enabled', '1', 'boolean', 'Habilitar/Desabilitar SafeNode', 'general', 1, '2025-11-20 22:47:10', '2025-11-20 22:47:10'),
(2, 'mode', 'production', 'string', 'Modo de operação: production, development, testing', 'general', 1, '2025-11-20 22:47:10', '2025-11-20 22:47:10'),
(3, 'log_retention_days', '30', 'integer', 'Dias para manter logs antes de arquivar', 'general', 1, '2025-11-20 22:47:10', '2025-11-20 22:47:10'),
(4, 'alert_email', '', 'string', 'Email para receber alertas críticos', 'general', 1, '2025-11-20 22:47:10', '2025-11-20 22:47:10'),
(5, 'alert_threshold', '10', 'integer', 'Número de ameaças por hora para enviar alerta', 'general', 1, '2025-11-20 22:47:10', '2025-11-20 22:47:10'),
(6, 'enable_whitelist', '1', 'boolean', 'Habilitar sistema de whitelist', 'general', 1, '2025-11-20 22:47:10', '2025-11-20 22:47:10'),
(7, 'enable_statistics', '1', 'boolean', 'Coletar estatísticas para dashboard', 'general', 1, '2025-11-20 22:47:10', '2025-11-20 22:47:10'),
(8, 'auto_block', '1', 'boolean', 'Bloquear IPs automaticamente quando detectar ameaças', 'detection', 1, '2025-11-20 22:47:10', '2025-11-20 22:47:10'),
(9, 'block_duration', '3600', 'integer', 'Duração do bloqueio em segundos (padrão: 1 hora)', 'detection', 1, '2025-11-20 22:47:10', '2025-11-20 22:47:10'),
(10, 'threat_score_threshold', '70', 'integer', 'Score mínimo para considerar ameaça crítica (0-100)', 'detection', 1, '2025-11-20 22:47:10', '2025-11-20 22:47:10'),
(11, 'login_max_attempts', '5', 'integer', 'Máximo de tentativas de login antes de bloquear', 'rate_limit', 1, '2025-11-20 22:47:10', '2025-11-20 22:47:10'),
(12, 'login_window', '300', 'integer', 'Janela de tempo para tentativas de login em segundos (5 minutos)', 'rate_limit', 1, '2025-11-20 22:47:10', '2025-11-20 22:47:10'),
(13, 'api_rate_limit', '100', 'integer', 'Limite de requisições por minuto para API', 'rate_limit', 1, '2025-11-20 22:47:10', '2025-11-20 22:47:10'),
(14, 'api_rate_window', '60', 'integer', 'Janela de tempo para rate limit da API em segundos', 'rate_limit', 1, '2025-11-20 22:47:10', '2025-11-20 22:47:10'),
(15, 'cloudflare_sync', '1', 'boolean', 'Sincronizar bloqueios com Cloudflare', 'cloudflare', 1, '2025-11-20 22:47:10', '2025-11-20 22:47:10'),
(16, 'cloudflare_zone_id', '2e1eb9127f2d34761d4626b5e71aaaab', 'string', 'Zone ID do Cloudflare', 'cloudflare', 1, '2025-11-20 22:47:10', '2025-11-22 22:59:49'),
(17, 'cloudflare_api_token', 'B4ExJBwzVAIZHjzEFjA3lEp2kyxBgmYE5pAEmCfA', 'string', 'API Token do Cloudflare', 'cloudflare', 1, '2025-11-20 22:47:10', '2025-11-22 22:59:49');

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

--
-- Despejando dados para a tabela `safenode_sites`
--

INSERT INTO `safenode_sites` (`id`, `user_id`, `domain`, `display_name`, `cloudflare_zone_id`, `cloudflare_status`, `ssl_status`, `security_level`, `auto_block`, `rate_limit_enabled`, `threat_detection_enabled`, `is_active`, `notes`, `verification_token`, `verification_status`, `geo_allow_only`, `created_at`, `updated_at`) VALUES
(2, 4, 'denfy.vercel.app', 'denfy', NULL, 'active', 'pending', 'high', 1, 1, 1, 1, NULL, 'bb2f697e6d5943ed3b7f248e4ee52cb95c5d0bea5a5beec74a015f5752c665b4', 'pending', 0, '2025-11-21 14:34:36', '2025-11-22 04:59:09'),
(5, 10, 'lactechsys.com', 'Lactech', '2e1eb9127f2d34761d4626b5e71aaaab', 'active', 'pending', 'medium', 1, 1, 1, 1, NULL, 'df113b7e1414049d0330bd55a367a6838ae7b56e0d0c8ae3948416889d7b3be3', 'pending', 0, '2025-11-25 18:14:06', '2025-11-25 18:36:19');

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
  `pattern` text NOT NULL COMMENT 'Padrão regex para detecção',
  `pattern_regex` text DEFAULT NULL COMMENT 'Alias para pattern (compatibilidade)',
  `threat_type` varchar(50) NOT NULL,
  `severity` int(11) DEFAULT 50 COMMENT 'Severidade de 0 a 100',
  `is_active` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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

--
-- Despejando dados para a tabela `safenode_users`
--

INSERT INTO `safenode_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `is_active`, `email_verified`, `email_verified_at`, `google_id`, `avatar_url`, `avatar_updated_at`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@safenode.cloud', '$2y$10$ya9uwD0EkE0WhYZu0EhKm.PrRsa/46dt4bGsJtNeHdN04peKAPL0K', 'Administrador SafeNode', 'admin', 1, 1, '2025-11-20 01:48:07', NULL, NULL, NULL, '2025-12-02 17:36:06', '2025-11-20 01:48:07', '2025-12-02 17:36:06'),
(4, 'lucenadev', 'joselucenadev@gmail.com', '$2y$10$AO6cXWR4Fo1HqSkxW4KY5.lVuEEXh.FT/hyyzSmgYzP.acKvwkDKW', 'José Kleiton Sinesio de Lucena Alves', 'user', 1, 1, '2025-11-21 14:31:58', NULL, NULL, NULL, '2025-11-21 14:32:24', '2025-11-21 14:31:28', '2025-11-21 14:32:24'),
(10, 'slavosier298', 'slavosier298@gmail.com', '$2y$10$YwzsJuCguE9CD.F6mxZJfODE4K5AxH9xMsM/e0lLFPyUgXcSzMNwe', 'Lavosier Silva', 'user', 1, 1, '2025-11-22 21:55:58', '115943975533213801187', 'https://lh3.googleusercontent.com/a/ACg8ocLmIAT_o4DY75p14SM6C4-nMnGPsJtc1-v3XL7JAkQNCHvmG36V=s96-c', '2025-11-25 14:33:29', '2025-11-25 14:33:29', '2025-11-22 21:55:58', '2025-11-25 14:33:29');

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_user_2fa`
--

CREATE TABLE `safenode_user_2fa` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `secret_key` varchar(32) NOT NULL COMMENT 'Chave secreta para geração de códigos TOTP',
  `is_enabled` tinyint(1) DEFAULT 0 COMMENT '2FA está ativado?',
  `backup_codes` text DEFAULT NULL COMMENT 'Códigos de backup em JSON',
  `qr_code_setup_at` timestamp NULL DEFAULT NULL COMMENT 'Quando foi configurado',
  `last_used_at` timestamp NULL DEFAULT NULL COMMENT 'Último uso do 2FA',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `safenode_user_2fa`
--

INSERT INTO `safenode_user_2fa` (`id`, `user_id`, `secret_key`, `is_enabled`, `backup_codes`, `qr_code_setup_at`, `last_used_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'H5ZATURJLCYLZWLGBYBN6FXPYMZWVMBI', 0, NULL, NULL, NULL, '2025-11-22 06:25:28', '2025-11-22 06:30:13'),
(7, 10, 'X6ECLYQSB556DHHD6O6FZLSYD2J6O65L', 1, '[\"16239993\",\"46079156\",\"62638836\",\"36234941\",\"58772076\",\"77582773\",\"98798609\",\"23323552\",\"04530952\",\"73847738\"]', '2025-11-22 21:57:21', NULL, '2025-11-22 21:56:58', '2025-11-22 21:57:21');

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
,`rate_limit_count` decimal(22,0)
,`path_traversal_count` decimal(22,0)
,`command_injection_count` decimal(22,0)
,`ddos_count` decimal(22,0)
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
,`threat_types` mediumtext
);

-- --------------------------------------------------------

--
-- Estrutura para view `v_safenode_active_blocks`
--
DROP TABLE IF EXISTS `v_safenode_active_blocks`;

CREATE OR REPLACE VIEW `v_safenode_active_blocks` AS SELECT `safenode_blocked_ips`.`ip_address` AS `ip_address`, `safenode_blocked_ips`.`reason` AS `reason`, `safenode_blocked_ips`.`threat_type` AS `threat_type`, `safenode_blocked_ips`.`created_at` AS `blocked_at`, `safenode_blocked_ips`.`expires_at` AS `expires_at`, timestampdiff(SECOND,current_timestamp(),`safenode_blocked_ips`.`expires_at`) AS `seconds_remaining` FROM `safenode_blocked_ips` WHERE `safenode_blocked_ips`.`is_active` = 1 AND (`safenode_blocked_ips`.`expires_at` is null OR `safenode_blocked_ips`.`expires_at` > current_timestamp()) ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_safenode_today_stats`
--
DROP TABLE IF EXISTS `v_safenode_today_stats`;

CREATE OR REPLACE VIEW `v_safenode_today_stats` AS SELECT count(0) AS `total_requests`, sum(case when `safenode_security_logs`.`action_taken` = 'blocked' then 1 else 0 end) AS `blocked_requests`, sum(case when `safenode_security_logs`.`action_taken` = 'allowed' then 1 else 0 end) AS `allowed_requests`, count(distinct `safenode_security_logs`.`ip_address`) AS `unique_ips`, sum(case when `safenode_security_logs`.`threat_type` = 'sql_injection' then 1 else 0 end) AS `sql_injection_count`, sum(case when `safenode_security_logs`.`threat_type` = 'xss' then 1 else 0 end) AS `xss_count`, sum(case when `safenode_security_logs`.`threat_type` = 'brute_force' then 1 else 0 end) AS `brute_force_count`, sum(case when `safenode_security_logs`.`threat_type` = 'rate_limit' then 1 else 0 end) AS `rate_limit_count`, sum(case when `safenode_security_logs`.`threat_type` = 'path_traversal' then 1 else 0 end) AS `path_traversal_count`, sum(case when `safenode_security_logs`.`threat_type` = 'command_injection' then 1 else 0 end) AS `command_injection_count`, sum(case when `safenode_security_logs`.`threat_type` = 'ddos' then 1 else 0 end) AS `ddos_count` FROM `safenode_security_logs` WHERE cast(`safenode_security_logs`.`created_at` as date) = curdate() ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_safenode_top_blocked_ips`
--
DROP TABLE IF EXISTS `v_safenode_top_blocked_ips`;

CREATE OR REPLACE VIEW `v_safenode_top_blocked_ips` AS SELECT `safenode_security_logs`.`ip_address` AS `ip_address`, count(0) AS `block_count`, max(`safenode_security_logs`.`created_at`) AS `last_blocked`, substring_index(group_concat(distinct `safenode_security_logs`.`threat_type` order by `safenode_security_logs`.`threat_type` ASC separator ','),',',10) AS `threat_types` FROM `safenode_security_logs` WHERE `safenode_security_logs`.`action_taken` = 'blocked' AND `safenode_security_logs`.`created_at` >= current_timestamp() - interval 7 day GROUP BY `safenode_security_logs`.`ip_address` ORDER BY count(0) DESC LIMIT 0, 100 ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `safenode_2fa_attempts`
--
ALTER TABLE `safenode_2fa_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Índices de tabela `safenode_activity_log`
--
ALTER TABLE `safenode_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_date` (`user_id`,`created_at`);

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
-- Índices de tabela `safenode_firewall_rules`
--
ALTER TABLE `safenode_firewall_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_site_priority` (`site_id`,`priority`);

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
-- Índices de tabela `safenode_incidents`
--
ALTER TABLE `safenode_incidents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inc_ip` (`ip_address`),
  ADD KEY `idx_inc_status` (`status`),
  ADD KEY `idx_inc_type` (`threat_type`),
  ADD KEY `idx_inc_site` (`site_id`),
  ADD KEY `idx_inc_last_seen` (`last_seen`);

--
-- Índices de tabela `safenode_ip_reputation`
--
ALTER TABLE `safenode_ip_reputation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ip` (`ip_address`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_trust_score` (`trust_score`),
  ADD KEY `idx_last_seen` (`last_seen`),
  ADD KEY `idx_country` (`country_code`);

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
-- Índices de tabela `safenode_rate_limits`
--
ALTER TABLE `safenode_rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_identifier` (`identifier`),
  ADD KEY `idx_endpoint` (`endpoint`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_priority` (`priority`);

--
-- Índices de tabela `safenode_rate_limits_violations`
--
ALTER TABLE `safenode_rate_limits_violations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_rate_limit` (`rate_limit_id`),
  ADD KEY `idx_created` (`created_at`);

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
  ADD KEY `idx_site_id` (`site_id`),
  ADD KEY `idx_country_code` (`country_code`),
  ADD KEY `idx_response_time` (`response_time`,`created_at`),
  ADD KEY `idx_logs_ip_date` (`ip_address`,`created_at`),
  ADD KEY `idx_logs_threat_date` (`threat_type`,`created_at`);

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
  ADD KEY `idx_user_id` (`user_id`);

--
-- Índices de tabela `safenode_site_geo_rules`
--
ALTER TABLE `safenode_site_geo_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `site_country` (`site_id`,`country_code`);

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
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD UNIQUE KEY `idx_google_id` (`google_id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_email_verified` (`email_verified`);

--
-- Índices de tabela `safenode_user_2fa`
--
ALTER TABLE `safenode_user_2fa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `unique_user_id` (`user_id`);

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
-- AUTO_INCREMENT de tabela `safenode_2fa_attempts`
--
ALTER TABLE `safenode_2fa_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_activity_log`
--
ALTER TABLE `safenode_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT de tabela `safenode_firewall_rules`
--
ALTER TABLE `safenode_firewall_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_hv_api_keys`
--
ALTER TABLE `safenode_hv_api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT de tabela `safenode_incidents`
--
ALTER TABLE `safenode_incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_ip_reputation`
--
ALTER TABLE `safenode_ip_reputation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_otp_codes`
--
ALTER TABLE `safenode_otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `safenode_rate_limits`
--
ALTER TABLE `safenode_rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_rate_limits_violations`
--
ALTER TABLE `safenode_rate_limits_violations`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `safenode_site_geo_rules`
--
ALTER TABLE `safenode_site_geo_rules`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_users`
--
ALTER TABLE `safenode_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `safenode_user_2fa`
--
ALTER TABLE `safenode_user_2fa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `safenode_user_sessions`
--
ALTER TABLE `safenode_user_sessions`
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
-- Restrições para tabelas `safenode_2fa_attempts`
--
ALTER TABLE `safenode_2fa_attempts`
  ADD CONSTRAINT `safenode_2fa_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_activity_log`
--
ALTER TABLE `safenode_activity_log`
  ADD CONSTRAINT `safenode_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_firewall_rules`
--
ALTER TABLE `safenode_firewall_rules`
  ADD CONSTRAINT `safenode_firewall_rules_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_hv_api_keys`
--
ALTER TABLE `safenode_hv_api_keys`
  ADD CONSTRAINT `fk_hv_api_keys_user` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_hv_attempts`
--
ALTER TABLE `safenode_hv_attempts`
  ADD CONSTRAINT `fk_hv_attempts_api_key` FOREIGN KEY (`api_key_id`) REFERENCES `safenode_hv_api_keys` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `safenode_hv_rate_limits`
--
ALTER TABLE `safenode_hv_rate_limits`
  ADD CONSTRAINT `fk_hv_rate_limits_api_key` FOREIGN KEY (`api_key_id`) REFERENCES `safenode_hv_api_keys` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_otp_codes`
--
ALTER TABLE `safenode_otp_codes`
  ADD CONSTRAINT `safenode_otp_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_rate_limits_violations`
--
ALTER TABLE `safenode_rate_limits_violations`
  ADD CONSTRAINT `safenode_rate_limits_violations_ibfk_1` FOREIGN KEY (`rate_limit_id`) REFERENCES `safenode_rate_limits` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `safenode_security_logs`
--
ALTER TABLE `safenode_security_logs`
  ADD CONSTRAINT `safenode_security_logs_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `safenode_site_geo_rules`
--
ALTER TABLE `safenode_site_geo_rules`
  ADD CONSTRAINT `safenode_site_geo_rules_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_user_2fa`
--
ALTER TABLE `safenode_user_2fa`
  ADD CONSTRAINT `safenode_user_2fa_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_user_sessions`
--
ALTER TABLE `safenode_user_sessions`
  ADD CONSTRAINT `safenode_user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
