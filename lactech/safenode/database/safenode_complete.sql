-- =====================================================
-- SafeNode - Banco de Dados Completo
-- Versão: 1.0
-- Data: 2025-11-19
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =====================================================
-- TABELAS
-- =====================================================

-- --------------------------------------------------------
-- Tabela: safenode_users
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `safenode_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_username` (`username`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_active` (`is_active`),
  KEY `idx_email_verified` (`email_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: safenode_otp_codes
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `safenode_otp_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `action` varchar(50) DEFAULT 'email_verification',
  `expires_at` timestamp NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `attempts` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_otp_code` (`otp_code`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_verified` (`verified`),
  CONSTRAINT `safenode_otp_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: safenode_sites
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `safenode_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_domain` (`domain`),
  KEY `idx_domain` (`domain`),
  KEY `idx_active` (`is_active`),
  KEY `idx_cloudflare_zone` (`cloudflare_zone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: safenode_security_logs
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `safenode_security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `referer` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_threat_type` (`threat_type`),
  KEY `idx_action` (`action_taken`),
  KEY `idx_created` (`created_at`),
  KEY `idx_user` (`user_id`),
  KEY `idx_threat_score` (`threat_score`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_response_time` (`response_time`, `created_at`),
  KEY `idx_logs_ip_date` (`ip_address`,`created_at`),
  KEY `idx_logs_threat_date` (`threat_type`,`created_at`),
  CONSTRAINT `safenode_security_logs_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: safenode_blocked_ips
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `safenode_blocked_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `threat_type` varchar(50) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `blocked_by` varchar(50) DEFAULT 'system',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_active` (`is_active`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_threat_type` (`threat_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: safenode_whitelist
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `safenode_whitelist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `added_by` varchar(50) DEFAULT 'admin',
  `added_at` timestamp NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ip` (`ip_address`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: safenode_rate_limits
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `safenode_rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) DEFAULT '*' COMMENT 'Identificador (IP, user_id, etc) ou * para todos',
  `identifier_type` varchar(20) DEFAULT 'ip',
  `endpoint` varchar(255) DEFAULT '*' COMMENT 'Endpoint específico ou * para todos',
  `max_requests` int(11) DEFAULT 100 COMMENT 'Máximo de requisições permitidas',
  `time_window` int(11) DEFAULT 60 COMMENT 'Janela de tempo em segundos',
  `priority` int(11) DEFAULT 0 COMMENT 'Prioridade (maior = mais importante)',
  `is_active` tinyint(1) DEFAULT 1,
  `action` varchar(50) DEFAULT 'block' COMMENT 'Ação a tomar: block, log, warn',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_identifier` (`identifier`),
  KEY `idx_endpoint` (`endpoint`),
  KEY `idx_active` (`is_active`),
  KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: safenode_rate_limits_violations
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `safenode_rate_limits_violations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `rate_limit_id` int(11) DEFAULT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `request_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_rate_limit` (`rate_limit_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `safenode_rate_limits_violations_ibfk_1` FOREIGN KEY (`rate_limit_id`) REFERENCES `safenode_rate_limits` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: safenode_threat_patterns
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `safenode_threat_patterns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pattern_name` varchar(100) NOT NULL,
  `pattern` text NOT NULL COMMENT 'Padrão regex para detecção',
  `pattern_regex` text DEFAULT NULL COMMENT 'Alias para pattern (compatibilidade)',
  `threat_type` varchar(50) NOT NULL,
  `severity` int(11) DEFAULT 50 COMMENT 'Severidade de 0 a 100',
  `is_active` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_type` (`threat_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: safenode_statistics
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `safenode_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date_hour` (`stat_date`,`stat_hour`),
  KEY `idx_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: safenode_alerts
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `safenode_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_type` varchar(50) NOT NULL,
  `alert_level` varchar(20) DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `threat_count` int(11) DEFAULT 1,
  `is_read` tinyint(1) DEFAULT 0,
  `is_resolved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`alert_type`),
  KEY `idx_level` (`alert_level`),
  KEY `idx_read` (`is_read`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela: safenode_settings
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `safenode_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(20) DEFAULT 'string',
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general',
  `is_editable` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_key` (`setting_key`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- --------------------------------------------------------
-- Usuário Admin Padrão
-- Senha: admin123
-- Hash gerado com password_hash('admin123', PASSWORD_DEFAULT)
-- --------------------------------------------------------

INSERT INTO `safenode_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `is_active`, `email_verified`, `email_verified_at`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@safenode.cloud', '$2y$10$ya9uwD0EkE0WhYZu0EhKm.PrRsa/46dt4bGsJtNeHdN04peKAPL0K', 'Administrador SafeNode', 'admin', 1, 1, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE 
  `password_hash` = VALUES(`password_hash`),
  `is_active` = 1,
  `email_verified` = 1;

-- =====================================================
-- VIEWS
-- =====================================================

-- --------------------------------------------------------
-- View: v_safenode_active_blocks
-- --------------------------------------------------------

DROP VIEW IF EXISTS `v_safenode_active_blocks`;

CREATE VIEW `v_safenode_active_blocks` AS
SELECT 
    `ip_address`,
    `reason`,
    `threat_type`,
    `created_at` AS `blocked_at`,
    `expires_at`,
    TIMESTAMPDIFF(SECOND, NOW(), `expires_at`) AS `seconds_remaining`
FROM `safenode_blocked_ips`
WHERE `is_active` = 1 
  AND (`expires_at` IS NULL OR `expires_at` > NOW());

-- --------------------------------------------------------
-- View: v_safenode_today_stats
-- --------------------------------------------------------

DROP VIEW IF EXISTS `v_safenode_today_stats`;

CREATE VIEW `v_safenode_today_stats` AS
SELECT 
    COUNT(*) AS `total_requests`,
    SUM(CASE WHEN `action_taken` = 'blocked' THEN 1 ELSE 0 END) AS `blocked_requests`,
    SUM(CASE WHEN `action_taken` = 'allowed' THEN 1 ELSE 0 END) AS `allowed_requests`,
    COUNT(DISTINCT `ip_address`) AS `unique_ips`,
    SUM(CASE WHEN `threat_type` = 'sql_injection' THEN 1 ELSE 0 END) AS `sql_injection_count`,
    SUM(CASE WHEN `threat_type` = 'xss' THEN 1 ELSE 0 END) AS `xss_count`,
    SUM(CASE WHEN `threat_type` = 'brute_force' THEN 1 ELSE 0 END) AS `brute_force_count`,
    SUM(CASE WHEN `threat_type` = 'rate_limit' THEN 1 ELSE 0 END) AS `rate_limit_count`,
    SUM(CASE WHEN `threat_type` = 'path_traversal' THEN 1 ELSE 0 END) AS `path_traversal_count`,
    SUM(CASE WHEN `threat_type` = 'command_injection' THEN 1 ELSE 0 END) AS `command_injection_count`,
    SUM(CASE WHEN `threat_type` = 'ddos' THEN 1 ELSE 0 END) AS `ddos_count`
FROM `safenode_security_logs`
WHERE CAST(`created_at` AS DATE) = CURDATE();

-- --------------------------------------------------------
-- View: v_safenode_top_blocked_ips
-- --------------------------------------------------------

DROP VIEW IF EXISTS `v_safenode_top_blocked_ips`;

CREATE VIEW `v_safenode_top_blocked_ips` AS
SELECT 
    `ip_address`,
    COUNT(*) AS `block_count`,
    MAX(`created_at`) AS `last_blocked`,
    SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT `threat_type` ORDER BY `threat_type` SEPARATOR ','), ',', 10) AS `threat_types`
FROM `safenode_security_logs`
WHERE `action_taken` = 'blocked' 
  AND `created_at` >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY `ip_address`
ORDER BY COUNT(*) DESC
LIMIT 100;

-- =====================================================
-- FINALIZAÇÃO
-- =====================================================

SET FOREIGN_KEY_CHECKS = 1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

