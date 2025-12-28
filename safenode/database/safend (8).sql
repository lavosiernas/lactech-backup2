-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 26/12/2025 às 00:32
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

DELIMITER $$
--
-- Procedimentos
--
CREATE DEFINER=`u311882628_Kron`@`127.0.0.1` PROCEDURE `sp_archive_old_logs` (IN `days_to_keep` INT)   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Mover logs antigos para tabela de arquivo
    INSERT INTO safenode_security_logs_archive
    SELECT * FROM safenode_security_logs
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY)
    LIMIT 10000; -- Processar em lotes de 10k para evitar timeout
    
    -- Deletar logs antigos da tabela principal
    DELETE FROM safenode_security_logs
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY)
    LIMIT 10000;
    
    COMMIT;
    
    SELECT CONCAT('Arquivados ', ROW_COUNT(), ' registros') AS resultado;
END$$

CREATE DEFINER=`u311882628_Kron`@`127.0.0.1` PROCEDURE `sp_cleanup_old_archive` (IN `months_to_keep` INT)   BEGIN
    DECLARE rows_deleted INT DEFAULT 0;
    DECLARE cutoff_date DATE;
    
    SET cutoff_date = DATE_SUB(CURDATE(), INTERVAL months_to_keep MONTH);
    
    -- Deletar logs arquivados muito antigos
    DELETE FROM safenode_security_logs_archive
    WHERE DATE(created_at) < cutoff_date;
    
    SET rows_deleted = ROW_COUNT();
    
    SELECT 
        rows_deleted AS 'Registros deletados',
        cutoff_date AS 'Data de corte',
        CONCAT('Limpeza concluída. ', rows_deleted, ' registros removidos.') AS resultado;
END$$

DELIMITER ;

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
-- Estrutura para tabela `safenode_alert_configs`
--

CREATE TABLE `safenode_alert_configs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `site_id` int(11) DEFAULT NULL,
  `channel` enum('email','webhook','sms','telegram') NOT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `webhook_url` varchar(500) DEFAULT NULL,
  `event_types` varchar(500) NOT NULL COMMENT 'Comma-separated list',
  `min_severity` int(11) DEFAULT 3,
  `priority` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_attack_patterns`
--

CREATE TABLE `safenode_attack_patterns` (
  `id` int(11) NOT NULL,
  `pattern_name` varchar(100) NOT NULL,
  `pattern_signature` text NOT NULL COMMENT 'Assinatura do padrão (regex ou hash)',
  `threat_type` varchar(50) NOT NULL,
  `severity` int(11) DEFAULT 50,
  `description` text DEFAULT NULL,
  `detection_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_audit_results`
--

CREATE TABLE `safenode_audit_results` (
  `id` int(11) NOT NULL,
  `audit_id` int(11) NOT NULL,
  `check_name` varchar(100) NOT NULL,
  `check_category` varchar(50) NOT NULL COMMENT 'headers, endpoints, waf, rate_limit, etc',
  `status` enum('pass','fail','warning','info') NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `current_value` text DEFAULT NULL,
  `recommended_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `fix_instructions` text DEFAULT NULL,
  `auto_fixable` tinyint(1) DEFAULT 0,
  `fixed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `safenode_audit_results`
--

INSERT INTO `safenode_audit_results` (`id`, `audit_id`, `check_name`, `check_category`, `status`, `severity`, `current_value`, `recommended_value`, `description`, `fix_instructions`, `auto_fixable`, `fixed_at`, `created_at`) VALUES
(1, 1, 'X-Frame-Options', 'headers', 'fail', 'high', 'Não configurado', 'DENY', 'Header de segurança X-Frame-Options não configurado', 'Configure o header X-Frame-Options: DENY', 0, NULL, '2025-12-17 17:38:37'),
(2, 1, 'X-Content-Type-Options', 'headers', 'fail', 'high', 'Não configurado', 'nosniff', 'Header de segurança X-Content-Type-Options não configurado', 'Configure o header X-Content-Type-Options: nosniff', 0, NULL, '2025-12-17 17:38:37'),
(3, 1, 'X-XSS-Protection', 'headers', 'warning', 'medium', 'Não configurado', '1; mode=block', 'Header de segurança X-XSS-Protection não configurado', 'Configure o header X-XSS-Protection: 1; mode=block', 0, NULL, '2025-12-17 17:38:37'),
(4, 1, 'Strict-Transport-Security', 'headers', 'fail', 'high', 'Não configurado', 'max-age=31536000', 'Header de segurança Strict-Transport-Security não configurado', 'Configure o header Strict-Transport-Security: max-age=31536000', 0, NULL, '2025-12-17 17:38:37'),
(5, 1, 'Content-Security-Policy', 'headers', 'warning', 'medium', 'Não configurado', 'default-src \'self\'', 'Header de segurança Content-Security-Policy não configurado', 'Configure o header Content-Security-Policy: default-src \'self\'', 0, NULL, '2025-12-17 17:38:37'),
(6, 1, 'Referrer-Policy', 'headers', 'warning', 'medium', 'Não configurado', 'strict-origin-when-cross-origin', 'Header de segurança Referrer-Policy não configurado', 'Configure o header Referrer-Policy: strict-origin-when-cross-origin', 0, NULL, '2025-12-17 17:38:37'),
(7, 1, 'Permissions-Policy', 'headers', 'warning', 'medium', 'Não configurado', 'geolocation=(), microphone=()', 'Header de segurança Permissions-Policy não configurado', 'Configure o header Permissions-Policy: geolocation=(), microphone=()', 0, NULL, '2025-12-17 17:38:37'),
(8, 1, 'Proteção: /login', 'endpoints', 'warning', 'medium', 'Não protegido', 'Proteção configurada', 'Endpoint sensível /login não possui regras de proteção', 'Configure regras de segurança para o endpoint /login', 1, NULL, '2025-12-17 17:38:37'),
(9, 1, 'Proteção: /admin', 'endpoints', 'warning', 'medium', 'Não protegido', 'Proteção configurada', 'Endpoint sensível /admin não possui regras de proteção', 'Configure regras de segurança para o endpoint /admin', 1, NULL, '2025-12-17 17:38:37'),
(10, 1, 'Proteção: /api', 'endpoints', 'warning', 'medium', 'Não protegido', 'Proteção configurada', 'Endpoint sensível /api não possui regras de proteção', 'Configure regras de segurança para o endpoint /api', 1, NULL, '2025-12-17 17:38:37'),
(11, 1, 'Proteção: /checkout', 'endpoints', 'warning', 'medium', 'Não protegido', 'Proteção configurada', 'Endpoint sensível /checkout não possui regras de proteção', 'Configure regras de segurança para o endpoint /checkout', 1, NULL, '2025-12-17 17:38:37'),
(12, 1, 'Proteção: /payment', 'endpoints', 'warning', 'medium', 'Não protegido', 'Proteção configurada', 'Endpoint sensível /payment não possui regras de proteção', 'Configure regras de segurança para o endpoint /payment', 1, NULL, '2025-12-17 17:38:37'),
(13, 1, 'Proteção: /reset-password', 'endpoints', 'warning', 'medium', 'Não protegido', 'Proteção configurada', 'Endpoint sensível /reset-password não possui regras de proteção', 'Configure regras de segurança para o endpoint /reset-password', 1, NULL, '2025-12-17 17:38:37'),
(14, 1, 'WAF Habilitado', 'waf', 'pass', 'low', 'Sim', 'Sim', 'WAF está habilitado', NULL, 1, NULL, '2025-12-17 17:38:37'),
(15, 1, 'Rate Limiting Habilitado', 'rate_limit', 'pass', 'low', 'Sim', 'Sim', 'Rate limiting está habilitado', NULL, 1, NULL, '2025-12-17 17:38:37'),
(16, 1, 'Nível de Segurança', 'config', 'warning', 'medium', 'medium', 'high', 'Configuração: Nível de Segurança = medium', 'Configure Nível de Segurança para: high', 1, NULL, '2025-12-17 17:38:37'),
(17, 1, 'Bloqueio Automático', 'config', 'pass', 'low', 'Habilitado', 'Habilitado', 'Configuração: Bloqueio Automático = Habilitado', NULL, 1, NULL, '2025-12-17 17:38:37');

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
-- Estrutura para tabela `safenode_endpoint_anomalies`
--

CREATE TABLE `safenode_endpoint_anomalies` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `endpoint_pattern` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `anomaly_type` varchar(50) NOT NULL COMMENT 'rate_spike, unusual_pattern, suspicious_behavior, etc',
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `baseline_value` decimal(10,2) DEFAULT NULL,
  `current_value` decimal(10,2) DEFAULT NULL,
  `deviation_percentage` decimal(5,2) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Detalhes em JSON',
  `detected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_resolved` tinyint(1) DEFAULT 0,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_endpoint_rules`
--

CREATE TABLE `safenode_endpoint_rules` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `endpoint_pattern` varchar(255) NOT NULL COMMENT 'Padrão de URL (regex ou path)',
  `endpoint_type` enum('path','regex','api','static') DEFAULT 'path',
  `security_level` enum('low','medium','high','critical','custom') DEFAULT 'medium',
  `threat_detection_enabled` tinyint(1) DEFAULT 1,
  `rate_limit_enabled` tinyint(1) DEFAULT 1,
  `rate_limit_requests` int(11) DEFAULT 60 COMMENT 'Requisições por minuto',
  `rate_limit_window` int(11) DEFAULT 60 COMMENT 'Janela em segundos',
  `waf_enabled` tinyint(1) DEFAULT 1,
  `waf_strict_mode` tinyint(1) DEFAULT 0,
  `geo_blocking_enabled` tinyint(1) DEFAULT 0,
  `require_authentication` tinyint(1) DEFAULT 0,
  `require_human_verification` tinyint(1) DEFAULT 0,
  `custom_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Regras customizadas em JSON',
  `priority` int(11) DEFAULT 0 COMMENT 'Prioridade (maior = mais importante)',
  `is_active` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_endpoint_stats`
--

CREATE TABLE `safenode_endpoint_stats` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `endpoint_pattern` varchar(255) NOT NULL,
  `stat_date` date NOT NULL,
  `stat_hour` int(11) NOT NULL,
  `total_requests` int(11) DEFAULT 0,
  `blocked_requests` int(11) DEFAULT 0,
  `allowed_requests` int(11) DEFAULT 0,
  `unique_ips` int(11) DEFAULT 0,
  `avg_response_time` decimal(10,2) DEFAULT NULL,
  `threat_count` int(11) DEFAULT 0,
  `rate_limit_hits` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_fingerprints`
--

CREATE TABLE `safenode_fingerprints` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `fingerprint_hash` varchar(64) NOT NULL,
  `fingerprint_data` text DEFAULT NULL,
  `suspicion_score` int(11) DEFAULT 0,
  `is_bot` tinyint(1) DEFAULT 0,
  `site_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_seen` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Estrutura para tabela `safenode_honeypots`
--

CREATE TABLE `safenode_honeypots` (
  `id` int(11) NOT NULL,
  `honeypot_id` varchar(16) NOT NULL,
  `url` varchar(500) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_honeypot_access`
--

CREATE TABLE `safenode_honeypot_access` (
  `id` int(11) NOT NULL,
  `honeypot_id` varchar(16) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `request_uri` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
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

--
-- Despejando dados para a tabela `safenode_hv_api_keys`
--

INSERT INTO `safenode_hv_api_keys` (`id`, `user_id`, `api_key`, `api_secret`, `name`, `allowed_domains`, `rate_limit_per_minute`, `max_token_age`, `is_active`, `created_at`, `last_used_at`, `usage_count`) VALUES
(24, 10, 'sk_cbb49645b0b332ea151ff6679f6f1588', '7f134090c9c0f89bf1b6c114e638a27a3b6eec89a3216b1fcc7d84955ba1a5d2', 'Verificação Humana', NULL, 60, 3600, 1, '2025-12-03 16:19:29', '2025-12-20 14:47:30', 1750);

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

--
-- Despejando dados para a tabela `safenode_hv_attempts`
--

INSERT INTO `safenode_hv_attempts` (`id`, `api_key_id`, `ip_address`, `user_agent`, `referer`, `attempt_type`, `reason`, `created_at`) VALUES
(1, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 02:17:47'),
(2, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 02:19:21'),
(3, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 02:33:06'),
(4, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 02:33:40'),
(5, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 02:37:53'),
(6, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 02:41:01'),
(7, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 02:41:59'),
(8, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 03:18:27'),
(9, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 03:19:07'),
(10, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 03:20:59'),
(11, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 03:21:02'),
(12, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 03:23:28'),
(13, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 03:24:58'),
(14, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 03:25:16'),
(15, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 03:31:54'),
(16, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 03:32:03'),
(17, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 03:32:43'),
(18, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 03:40:07'),
(19, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 03:41:22'),
(20, NULL, '170.84.77.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 04:02:24'),
(21, NULL, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 14:09:50'),
(22, NULL, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'suspicious', 'Nonce inválido', '2025-12-03 14:43:37'),
(23, NULL, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 14:43:37'),
(24, NULL, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'suspicious', 'Nonce inválido', '2025-12-03 14:43:37'),
(25, NULL, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'suspicious', 'Nonce inválido', '2025-12-03 14:43:43'),
(26, NULL, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 14:43:43'),
(27, NULL, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'suspicious', 'Nonce inválido', '2025-12-03 14:43:43'),
(28, 24, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 16:19:54'),
(29, 24, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'suspicious', 'Nonce inválido', '2025-12-03 16:20:01'),
(30, 24, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 16:20:01'),
(31, 24, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'suspicious', 'Nonce inválido', '2025-12-03 16:20:01'),
(32, 24, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'suspicious', 'Nonce inválido', '2025-12-03 16:21:39'),
(33, 24, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 16:21:39'),
(34, 24, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'suspicious', 'Nonce inválido', '2025-12-03 16:21:39'),
(35, 24, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-03 16:24:26'),
(36, 24, '138.204.186.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-03 16:24:43'),
(37, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-04 14:02:02'),
(38, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-04 14:04:51'),
(39, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-04 14:52:22'),
(40, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-04 14:53:28'),
(41, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-04 17:44:38'),
(42, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-04 17:44:47'),
(43, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 12:01:05'),
(44, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 12:19:29'),
(45, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 12:19:54'),
(46, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 12:55:17'),
(47, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:10:44'),
(48, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:13:38'),
(49, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:17:46'),
(50, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:17:53'),
(51, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:28:43'),
(52, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:28:50'),
(53, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:28:50'),
(54, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:29:08'),
(55, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:29:08'),
(56, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:31:11'),
(57, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:31:11'),
(58, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:32:09'),
(59, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:32:10'),
(60, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:32:14'),
(61, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:32:15'),
(62, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:32:46'),
(63, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:32:47'),
(64, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:32:50'),
(65, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:32:50'),
(66, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:32:55'),
(67, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:32:55'),
(68, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:32:58'),
(69, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:32:59'),
(70, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:33:02'),
(71, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:33:02'),
(72, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:33:37'),
(73, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:33:38'),
(74, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:33:59'),
(75, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:34:00'),
(76, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:34:42'),
(77, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:34:55'),
(78, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:35:42'),
(79, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-05 13:35:43'),
(80, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:36:28'),
(81, 24, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-05 13:37:39'),
(82, NULL, '138.204.187.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 'http://localhost', 'failed', 'API key inválida', '2025-12-05 14:39:53'),
(83, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:07:52'),
(84, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 11:07:58'),
(85, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:12:48'),
(86, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 11:13:11'),
(87, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:23:47'),
(88, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 11:23:52'),
(89, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:51:22'),
(90, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 11:53:37'),
(91, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:54:07'),
(92, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 11:54:10'),
(93, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:54:10'),
(94, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 11:54:22'),
(95, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:54:23'),
(96, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 11:54:25'),
(97, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:54:25'),
(98, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 11:54:27'),
(99, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:54:27'),
(100, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 11:54:29'),
(101, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:54:29'),
(102, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 11:54:31'),
(103, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:54:31'),
(104, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 11:54:33'),
(105, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:54:34'),
(106, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 11:54:50'),
(107, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:54:50'),
(108, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:56:06'),
(109, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 11:56:09'),
(110, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:56:29'),
(111, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 11:57:17'),
(112, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 11:57:47'),
(113, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 11:57:49'),
(114, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 12:30:22'),
(115, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 12:33:49'),
(116, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 12:35:43'),
(117, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 12:35:44'),
(118, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 12:36:04'),
(119, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 12:36:04'),
(120, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 12:36:33'),
(121, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 12:36:33'),
(122, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 12:36:57'),
(123, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 12:40:56'),
(124, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 12:45:58'),
(125, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 12:46:07'),
(126, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 12:52:32'),
(127, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 12:52:48'),
(128, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 12:54:06'),
(129, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 12:54:17'),
(130, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 13:01:11'),
(131, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 13:01:12'),
(132, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 13:04:28'),
(133, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 13:04:33'),
(134, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 13:04:56'),
(135, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 13:05:02'),
(136, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 13:07:22'),
(137, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 13:14:01'),
(138, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 13:14:02'),
(139, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 13:14:07'),
(140, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 13:14:11'),
(141, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 13:14:15'),
(142, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 13:14:15'),
(143, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 13:14:49'),
(144, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 13:14:53'),
(145, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 14:29:48'),
(146, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 14:29:54'),
(147, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 14:35:28'),
(148, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 14:35:31'),
(149, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 14:40:54'),
(150, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 14:45:59'),
(151, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 14:47:17'),
(152, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 14:47:17'),
(153, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 14:47:42'),
(154, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 14:47:47'),
(155, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 14:48:31'),
(156, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 14:49:28'),
(157, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 14:49:56'),
(158, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 14:49:56'),
(159, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 14:50:11'),
(160, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 14:50:11'),
(161, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 14:52:57'),
(162, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 14:53:06'),
(163, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 14:53:06'),
(164, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 14:53:11'),
(165, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 14:53:17'),
(166, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 14:53:17'),
(167, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 14:55:57'),
(168, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 16:21:15'),
(169, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 16:21:19'),
(170, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 16:38:48'),
(171, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 16:38:53'),
(172, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 16:39:13'),
(173, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 16:39:18'),
(174, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 16:51:04'),
(175, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 16:58:16'),
(176, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 16:58:29'),
(177, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 17:04:15'),
(178, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 17:04:22'),
(179, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 17:04:34'),
(180, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 17:04:43'),
(181, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 17:04:51'),
(182, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 17:04:56'),
(183, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 17:06:31'),
(184, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 17:18:03'),
(185, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 17:18:42'),
(186, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 17:24:38'),
(187, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 17:24:40'),
(188, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 17:24:40'),
(189, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 17:25:45'),
(190, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 17:25:45'),
(191, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 17:25:53'),
(192, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 17:25:53'),
(193, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 17:25:55'),
(194, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 17:25:56'),
(195, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 17:25:58'),
(196, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 17:25:58'),
(197, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 17:26:00'),
(198, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 17:26:01'),
(199, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 17:26:03'),
(200, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 17:26:03'),
(201, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 17:26:14'),
(202, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:08:26'),
(203, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:08:28'),
(204, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:08:28'),
(205, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:08:31'),
(206, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:08:32'),
(207, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:08:34'),
(208, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:08:34'),
(209, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:08:36'),
(210, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:08:36'),
(211, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:08:38'),
(212, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:08:38'),
(213, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:08:40'),
(214, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:08:40'),
(215, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:11:13'),
(216, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:11:16'),
(217, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:11:16'),
(218, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:11:18'),
(219, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:11:18'),
(220, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:11:20'),
(221, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:11:20'),
(222, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:11:22'),
(223, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:11:22'),
(224, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:11:24'),
(225, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:11:24'),
(226, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:11:26'),
(227, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:11:26'),
(228, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:12:26'),
(229, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:12:35'),
(230, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:12:35');
INSERT INTO `safenode_hv_attempts` (`id`, `api_key_id`, `ip_address`, `user_agent`, `referer`, `attempt_type`, `reason`, `created_at`) VALUES
(231, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:12:38'),
(232, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:12:38'),
(233, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:12:40'),
(234, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:12:40'),
(235, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:13:07'),
(236, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:13:08'),
(237, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:13:14'),
(238, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:13:14'),
(239, 24, '2804:2788:c1c2:5500:3d73:3ea4:d16:37d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:14:06'),
(240, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:29:31'),
(241, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:29:35'),
(242, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:29:35'),
(243, 24, '2804:2788:c165:3900:3833:b258:e5c9:569c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:29:40'),
(244, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:41:20'),
(245, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-08 18:41:40'),
(246, 24, '2804:2788:c1c2:5500:4c34:ecc7:d9c0:a85f', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-08 18:56:08'),
(247, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 11:19:42'),
(248, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 11:19:43'),
(249, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 11:19:51'),
(250, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 11:19:52'),
(251, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 11:20:28'),
(252, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 11:20:34'),
(253, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 11:53:34'),
(254, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 11:53:40'),
(255, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 11:53:41'),
(256, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 11:54:33'),
(257, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 11:54:54'),
(258, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 11:54:57'),
(259, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 11:54:58'),
(260, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 11:55:47'),
(261, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 11:56:38'),
(262, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 11:56:49'),
(263, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 11:58:18'),
(264, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 11:58:29'),
(265, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 11:58:29'),
(266, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 11:59:05'),
(267, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 11:59:06'),
(268, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 11:59:42'),
(269, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 11:59:42'),
(270, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:00:13'),
(271, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:00:24'),
(272, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:00:25'),
(273, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:00:32'),
(274, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:00:32'),
(275, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:00:36'),
(276, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:00:41'),
(277, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:00:41'),
(278, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:00:55'),
(279, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:01:09'),
(280, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:01:14'),
(281, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:01:15'),
(282, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:01:21'),
(283, 24, '2804:2788:c1c2:5500:f854:10a5:ed4f:687c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:01:23'),
(284, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:02:43'),
(285, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:03:32'),
(286, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:03:32'),
(287, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:03:49'),
(288, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:03:56'),
(289, 24, '2804:2788:c165:3900:5022:edab:b580:5819', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:04:23'),
(290, 24, '2804:2788:c165:3900:5022:edab:b580:5819', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:05:25'),
(291, 24, '2804:2788:c165:3900:5022:edab:b580:5819', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:05:25'),
(292, 24, '2804:2788:c165:3900:5022:edab:b580:5819', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:05:32'),
(293, 24, '2804:2788:c165:3900:5022:edab:b580:5819', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:05:32'),
(294, 24, '2804:2788:c165:3900:5022:edab:b580:5819', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:05:46'),
(295, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:42:51'),
(296, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:42:58'),
(297, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:42:58'),
(298, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:43:06'),
(299, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:54:29'),
(300, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:54:36'),
(301, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:55:05'),
(302, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:55:11'),
(303, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:55:11'),
(304, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 12:55:36'),
(305, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 12:55:46'),
(306, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 13:21:32'),
(307, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 13:21:35'),
(308, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 13:21:36'),
(309, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 13:21:54'),
(310, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 13:22:07'),
(311, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 13:45:40'),
(312, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 13:45:49'),
(313, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 13:46:24'),
(314, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 13:46:28'),
(315, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 13:46:28'),
(316, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 13:46:37'),
(317, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 13:46:37'),
(318, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 13:46:47'),
(319, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 13:46:54'),
(320, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 13:46:54'),
(321, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 13:47:02'),
(322, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 13:47:28'),
(323, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 13:47:51'),
(324, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 13:47:55'),
(325, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 13:50:51'),
(326, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 13:50:58'),
(327, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 14:14:27'),
(328, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 14:14:32'),
(329, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 14:32:34'),
(330, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 14:44:49'),
(331, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 14:45:03'),
(332, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:13:58'),
(333, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:14:02'),
(334, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:14:16'),
(335, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:14:34'),
(336, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:15:48'),
(337, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:15:54'),
(338, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:18:50'),
(339, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:19:00'),
(340, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:19:01'),
(341, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:19:17'),
(342, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:22:51'),
(343, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:22:52'),
(344, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:22:57'),
(345, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:24:13'),
(346, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:24:24'),
(347, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:33:04'),
(348, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:33:12'),
(349, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:40:19'),
(350, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:41:28'),
(351, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:41:29'),
(352, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:41:38'),
(353, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:42:28'),
(354, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:42:32'),
(355, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:42:33'),
(356, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:42:40'),
(357, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:43:07'),
(358, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:43:18'),
(359, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:43:30'),
(360, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:43:38'),
(361, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:43:38'),
(362, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:43:45'),
(363, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:44:40'),
(364, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:44:56'),
(365, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:45:23'),
(366, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:45:38'),
(367, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:45:38'),
(368, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:45:47'),
(369, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:45:47'),
(370, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:45:55'),
(371, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:46:06'),
(372, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:46:14'),
(373, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:48:40'),
(374, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:48:49'),
(375, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:48:49'),
(376, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:49:29'),
(377, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:49:37'),
(378, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 16:50:22'),
(379, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 16:50:28'),
(380, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 17:07:33'),
(381, 24, '2804:2788:c165:3900:ec46:59dd:a299:a1e5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 17:08:06'),
(382, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 18:03:36'),
(383, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 18:03:44'),
(384, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 18:32:51'),
(385, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 18:32:58'),
(386, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 18:37:19'),
(387, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 18:37:23'),
(388, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 18:37:33'),
(389, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 18:39:57'),
(390, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 18:40:01'),
(391, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 18:40:02'),
(392, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 18:40:21'),
(393, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 18:40:27'),
(394, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 18:40:45'),
(395, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 18:41:04'),
(396, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 18:46:41'),
(397, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 18:46:49'),
(398, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 18:46:49'),
(399, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 18:46:56'),
(400, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 18:46:58'),
(401, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 18:47:22'),
(402, 24, '2804:2788:c1c2:5500:800c:1c03:c96f:af26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 18:47:29'),
(403, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 18:48:43'),
(404, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 18:49:31'),
(405, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 18:49:42'),
(406, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 19:00:13'),
(407, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 19:00:18'),
(408, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 19:00:18'),
(409, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 19:00:30'),
(410, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 19:00:30'),
(411, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 19:00:36'),
(412, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 19:00:36'),
(413, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 19:04:45'),
(414, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 19:04:47'),
(415, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 19:04:47'),
(416, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 19:04:51'),
(417, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 19:04:51'),
(418, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 19:04:56'),
(419, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 19:04:56'),
(420, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 19:05:38'),
(421, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 19:05:58'),
(422, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 19:07:52'),
(423, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 19:07:57'),
(424, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 19:07:57'),
(425, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 19:08:00'),
(426, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 19:08:00'),
(427, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 19:08:16'),
(428, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-09 19:08:17'),
(429, 24, '2804:2788:c1c2:5500:ac8e:58b3:322f:93bf', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-09 19:08:30'),
(430, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 10:58:33'),
(431, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 11:03:31'),
(432, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 11:03:46'),
(433, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 11:10:22'),
(434, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 11:35:00'),
(435, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 11:35:43'),
(436, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 11:38:39'),
(437, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 11:45:02'),
(438, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 11:45:06'),
(439, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 11:45:20'),
(440, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 11:45:28'),
(441, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 11:45:28'),
(442, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 11:45:43'),
(443, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 11:45:43'),
(444, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 11:45:53'),
(445, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 11:52:06'),
(446, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 11:52:31'),
(447, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 11:52:31'),
(448, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 11:52:38'),
(449, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 11:52:39'),
(450, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 11:53:50');
INSERT INTO `safenode_hv_attempts` (`id`, `api_key_id`, `ip_address`, `user_agent`, `referer`, `attempt_type`, `reason`, `created_at`) VALUES
(451, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 11:53:59'),
(452, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 12:24:11'),
(453, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 12:24:12'),
(454, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 12:28:56'),
(455, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 12:29:57'),
(456, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 12:44:21'),
(457, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 12:45:15'),
(458, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 12:45:33'),
(459, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 12:46:03'),
(460, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 12:47:53'),
(461, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 12:48:07'),
(462, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 12:49:02'),
(463, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 12:51:35'),
(464, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 12:51:36'),
(465, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 12:51:40'),
(466, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 12:51:44'),
(467, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 12:51:50'),
(468, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 12:51:50'),
(469, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 12:51:55'),
(470, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 12:51:59'),
(471, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 12:51:59'),
(472, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 12:52:23'),
(473, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 12:52:23'),
(474, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 12:52:34'),
(475, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 12:52:34'),
(476, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 12:53:05'),
(477, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 12:53:21'),
(478, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 12:54:16'),
(479, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 12:54:26'),
(480, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 12:55:59'),
(481, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 12:56:11'),
(482, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:02:30'),
(483, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:02:36'),
(484, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:06:32'),
(485, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:06:39'),
(486, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:07:11'),
(487, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:07:16'),
(488, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:17:53'),
(489, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:18:00'),
(490, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:23:28'),
(491, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:23:29'),
(492, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:23:52'),
(493, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:24:06'),
(494, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:24:56'),
(495, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:26:02'),
(496, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:27:06'),
(497, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:27:16'),
(498, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:33:01'),
(499, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:33:04'),
(500, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:34:55'),
(501, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:34:58'),
(502, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:34:59'),
(503, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:35:11'),
(504, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:35:12'),
(505, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:35:54'),
(506, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:37:31'),
(507, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:38:02'),
(508, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:38:03'),
(509, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:38:07'),
(510, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:38:07'),
(511, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:38:15'),
(512, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:39:53'),
(513, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:44:09'),
(514, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:44:49'),
(515, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:50:53'),
(516, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:50:54'),
(517, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:51:02'),
(518, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:54:47'),
(519, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:55:23'),
(520, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:55:25'),
(521, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:55:48'),
(522, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:55:49'),
(523, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:55:55'),
(524, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:55:56'),
(525, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 13:56:22'),
(526, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 13:56:31'),
(527, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:03:55'),
(528, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:04:14'),
(529, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:19:13'),
(530, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:20:19'),
(531, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:20:19'),
(532, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:20:51'),
(533, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:20:56'),
(534, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:32:25'),
(535, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:32:32'),
(536, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:32:42'),
(537, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:32:45'),
(538, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:32:45'),
(539, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:33:12'),
(540, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:34:21'),
(541, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:34:30'),
(542, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:34:48'),
(543, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:35:06'),
(544, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:37:41'),
(545, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:37:47'),
(546, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:37:48'),
(547, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:37:56'),
(548, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:38:07'),
(549, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:38:14'),
(550, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:38:14'),
(551, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:38:22'),
(552, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:38:31'),
(553, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:38:31'),
(554, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:38:42'),
(555, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:38:42'),
(556, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:38:46'),
(557, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:38:46'),
(558, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:38:54'),
(559, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:38:54'),
(560, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:39:05'),
(561, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:39:10'),
(562, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:39:14'),
(563, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:42:39'),
(564, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:42:45'),
(565, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:42:54'),
(566, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:43:02'),
(567, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 14:46:01'),
(568, 24, '2804:2788:c165:3900:8dee:a156:1cd6:ec14', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 14:46:06'),
(569, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 15:47:04'),
(570, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 15:47:08'),
(571, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 15:47:09'),
(572, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 15:47:12'),
(573, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 15:47:12'),
(574, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 15:47:18'),
(575, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 15:47:19'),
(576, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 15:47:51'),
(577, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 15:48:02'),
(578, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 15:48:02'),
(579, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 15:48:20'),
(580, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 15:48:39'),
(581, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 16:12:01'),
(582, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 16:12:12'),
(583, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 16:33:07'),
(584, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 16:33:20'),
(585, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 16:34:15'),
(586, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 16:34:32'),
(587, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 16:34:33'),
(588, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 16:34:43'),
(589, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 16:34:44'),
(590, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 16:34:49'),
(591, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 16:34:50'),
(592, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 16:34:56'),
(593, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 16:35:00'),
(594, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 16:36:35'),
(595, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 16:36:52'),
(596, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 16:37:54'),
(597, 24, '2804:2788:c1c2:5500:4cfe:ded8:a8de:f171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 16:38:29'),
(598, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 16:38:37'),
(599, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 16:39:32'),
(600, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 16:41:08'),
(601, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 16:41:08'),
(602, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 16:41:22'),
(603, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 16:41:22'),
(604, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 16:41:42'),
(605, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 16:42:09'),
(606, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 17:09:59'),
(607, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 17:10:11'),
(608, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 17:10:59'),
(609, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 17:11:03'),
(610, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 17:18:12'),
(611, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 17:18:23'),
(612, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 17:18:36'),
(613, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 17:18:44'),
(614, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 17:25:50'),
(615, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 17:26:18'),
(616, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 17:26:52'),
(617, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 17:26:57'),
(618, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 17:39:55'),
(619, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 17:39:59'),
(620, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 17:39:59'),
(621, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 17:40:06'),
(622, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 17:40:06'),
(623, 24, '2804:2788:c165:3900:d500:a29a:18b8:7f95', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 17:40:11'),
(624, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 18:28:20'),
(625, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-10 18:28:34'),
(626, 24, '2804:2788:c1c2:5500:801d:6629:192:f8ba', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-10 18:43:33'),
(627, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 10:56:06'),
(628, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 11:00:02'),
(629, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 11:10:24'),
(630, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 11:11:09'),
(631, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 11:12:34'),
(632, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 11:14:29'),
(633, NULL, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'failed', 'API key inválida', '2025-12-11 11:20:38'),
(634, NULL, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'failed', 'API key inválida', '2025-12-11 11:20:39'),
(635, NULL, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'failed', 'API key inválida', '2025-12-11 11:20:40'),
(636, NULL, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'failed', 'API key inválida', '2025-12-11 11:20:41'),
(637, NULL, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'failed', 'API key inválida', '2025-12-11 11:20:41'),
(638, NULL, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'failed', 'API key inválida', '2025-12-11 11:20:42'),
(639, NULL, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'failed', 'API key inválida', '2025-12-11 11:20:42'),
(640, NULL, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'failed', 'API key inválida', '2025-12-11 11:20:43'),
(641, NULL, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'failed', 'API key inválida', '2025-12-11 11:20:44'),
(642, NULL, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'failed', 'API key inválida', '2025-12-11 11:20:44'),
(643, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 11:20:53'),
(644, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 11:26:36'),
(645, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 11:26:40'),
(646, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 11:27:17'),
(647, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 11:37:25'),
(648, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 11:38:06'),
(649, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 11:46:55'),
(650, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 11:47:35'),
(651, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 12:15:07'),
(652, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 12:15:30'),
(653, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 12:16:24'),
(654, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 12:16:31'),
(655, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 12:42:24'),
(656, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 12:42:31'),
(657, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:08:25'),
(658, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 13:08:35'),
(659, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:10:58'),
(660, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 13:11:04'),
(661, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:11:04'),
(662, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 13:11:08'),
(663, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:11:09'),
(664, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:11:58'),
(665, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 13:12:03'),
(666, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:27:45'),
(667, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 13:27:49'),
(668, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:32:23'),
(669, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 13:32:30');
INSERT INTO `safenode_hv_attempts` (`id`, `api_key_id`, `ip_address`, `user_agent`, `referer`, `attempt_type`, `reason`, `created_at`) VALUES
(670, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:32:30'),
(671, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 13:33:14'),
(672, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:36:29'),
(673, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 13:36:33'),
(674, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:43:01'),
(675, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 13:43:05'),
(676, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:45:10'),
(677, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:45:35'),
(678, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 13:45:45'),
(679, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:46:38'),
(680, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 13:46:42'),
(681, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:50:02'),
(682, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 13:50:08'),
(683, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:50:17'),
(684, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:52:46'),
(685, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 13:52:53'),
(686, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:54:14'),
(687, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 13:54:20'),
(688, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:58:58'),
(689, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 13:58:58'),
(690, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 13:59:03'),
(691, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:01:46'),
(692, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:03:31'),
(693, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:05:43'),
(694, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:07:16'),
(695, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:14:32'),
(696, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:14:37'),
(697, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:14:52'),
(698, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:15:32'),
(699, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:15:40'),
(700, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:15:49'),
(701, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:15:55'),
(702, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:16:37'),
(703, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:17:21'),
(704, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:17:22'),
(705, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:17:42'),
(706, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:18:31'),
(707, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:21:41'),
(708, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:25:17'),
(709, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:25:22'),
(710, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:25:25'),
(711, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:25:56'),
(712, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:26:00'),
(713, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:26:07'),
(714, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:29:44'),
(715, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:29:46'),
(716, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:30:12'),
(717, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:30:15'),
(718, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:30:16'),
(719, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:30:21'),
(720, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:31:28'),
(721, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:31:37'),
(722, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:31:41'),
(723, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:31:44'),
(724, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:31:44'),
(725, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:31:47'),
(726, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:31:48'),
(727, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:31:50'),
(728, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:32:00'),
(729, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:32:04'),
(730, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:37:36'),
(731, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:37:40'),
(732, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:37:48'),
(733, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:37:53'),
(734, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:39:59'),
(735, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:40:03'),
(736, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:40:43'),
(737, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:40:48'),
(738, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:40:50'),
(739, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:40:54'),
(740, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:41:01'),
(741, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:41:03'),
(742, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:41:59'),
(743, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:42:06'),
(744, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:44:15'),
(745, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:44:22'),
(746, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:45:20'),
(747, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:45:24'),
(748, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:45:30'),
(749, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:47:22'),
(750, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:47:30'),
(751, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:47:36'),
(752, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:47:39'),
(753, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:47:39'),
(754, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:47:42'),
(755, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:47:42'),
(756, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:47:51'),
(757, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:47:53'),
(758, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:48:18'),
(759, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:48:34'),
(760, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:49:02'),
(761, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:49:14'),
(762, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:55:31'),
(763, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:55:37'),
(764, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 14:58:43'),
(765, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 14:58:48'),
(766, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 15:02:47'),
(767, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 15:02:52'),
(768, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 15:10:23'),
(769, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 15:56:16'),
(770, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 15:59:00'),
(771, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 15:59:20'),
(772, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 15:59:20'),
(773, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 15:59:25'),
(774, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:02:41'),
(775, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:02:45'),
(776, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:02:50'),
(777, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:05:40'),
(778, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:05:42'),
(779, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:06:29'),
(780, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:06:34'),
(781, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:08:08'),
(782, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:08:09'),
(783, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:08:13'),
(784, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:09:47'),
(785, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:10:04'),
(786, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:10:05'),
(787, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:10:21'),
(788, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:10:22'),
(789, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:10:33'),
(790, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:17:32'),
(791, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:17:39'),
(792, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:17:39'),
(793, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:17:43'),
(794, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:23:38'),
(795, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:23:59'),
(796, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:24:32'),
(797, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:24:36'),
(798, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:31:46'),
(799, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:32:13'),
(800, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:32:14'),
(801, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:32:20'),
(802, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:32:20'),
(803, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:32:56'),
(804, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:33:00'),
(805, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:41:08'),
(806, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:41:29'),
(807, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:43:31'),
(808, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:43:34'),
(809, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:48:26'),
(810, 24, '2804:2788:c1c2:5500:25bc:8f04:dc86:6fae', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:48:29'),
(811, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:53:35'),
(812, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:54:44'),
(813, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:54:56'),
(814, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:55:45'),
(815, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:55:50'),
(816, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 16:55:50'),
(817, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 16:56:01'),
(818, 24, '2804:2788:c1c2:5500:385b:e59e:eea9:91c4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 17:09:17'),
(819, 24, '2804:2788:c1c2:5500:1967:464f:9dcd:31d8', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 17:31:17'),
(820, 24, '2804:2788:c1c2:5500:2430:4ddd:4e61:47de', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 17:33:54'),
(821, 24, '2804:2788:c1c2:5500:2430:4ddd:4e61:47de', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 17:33:59'),
(822, 24, '2804:2788:c1c2:5500:1967:464f:9dcd:31d8', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 17:34:32'),
(823, 24, '2804:2788:c1c2:5500:1967:464f:9dcd:31d8', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 17:34:40'),
(824, 24, '2804:2788:c1c2:5500:1967:464f:9dcd:31d8', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 17:34:55'),
(825, 24, '2804:2788:c1c2:5500:2430:4ddd:4e61:47de', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 17:37:43'),
(826, 24, '2804:2788:c1c2:5500:2430:4ddd:4e61:47de', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 17:38:45'),
(827, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 17:44:39'),
(828, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 17:44:45'),
(829, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 17:48:16'),
(830, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 17:48:45'),
(831, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 17:48:48'),
(832, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 17:53:12'),
(833, 24, '2804:2788:c165:3900:a8e2:4713:f31d:13dd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 17:53:15'),
(834, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 22:31:05'),
(835, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 22:31:34'),
(836, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 22:31:51'),
(837, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 22:31:55'),
(838, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 22:45:44'),
(839, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 22:46:02'),
(840, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 22:49:25'),
(841, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 22:49:29'),
(842, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 23:02:50'),
(843, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 23:02:55'),
(844, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 23:03:50'),
(845, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 23:04:34'),
(846, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 23:07:50'),
(847, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 23:07:54'),
(848, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 23:07:55'),
(849, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 23:08:29'),
(850, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 23:12:22'),
(851, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 23:12:28'),
(852, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 23:16:19'),
(853, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 23:16:23'),
(854, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 23:21:28'),
(855, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 23:21:31'),
(856, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-11 23:22:34'),
(857, 24, '179.0.112.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-11 23:22:39'),
(858, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 02:03:14'),
(859, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 02:03:32'),
(860, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 02:03:34'),
(861, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 02:03:47'),
(862, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 02:03:49'),
(863, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 02:04:47'),
(864, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 02:05:06'),
(865, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'http://localhost', 'init', NULL, '2025-12-12 09:47:42'),
(866, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 11:05:29'),
(867, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 11:05:33'),
(868, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 12:10:31'),
(869, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 12:10:39'),
(870, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 12:12:10'),
(871, 24, '2804:2788:c1c2:5500:58cf:b6ee:e400:4d3a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 12:14:48'),
(872, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 12:59:35'),
(873, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 12:59:38'),
(874, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:08:59'),
(875, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:09:05'),
(876, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:09:06'),
(877, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:09:10'),
(878, 24, '2804:2788:c1c2:5500:58cf:b6ee:e400:4d3a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:09:31'),
(879, 24, '2804:2788:c1c2:5500:58cf:b6ee:e400:4d3a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:09:33'),
(880, 24, '2804:2788:c1c2:5500:58cf:b6ee:e400:4d3a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:09:48'),
(881, 24, '2804:2788:c1c2:5500:58cf:b6ee:e400:4d3a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:09:48'),
(882, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:09:59'),
(883, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:10:05'),
(884, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:10:39'),
(885, 24, '2804:2788:c1c2:5500:58cf:b6ee:e400:4d3a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:10:40'),
(886, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:10:47'),
(887, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:11:06'),
(888, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:11:12'),
(889, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:11:12'),
(890, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:11:20'),
(891, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:12:11'),
(892, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:12:20'),
(893, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:12:30');
INSERT INTO `safenode_hv_attempts` (`id`, `api_key_id`, `ip_address`, `user_agent`, `referer`, `attempt_type`, `reason`, `created_at`) VALUES
(894, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:12:40'),
(895, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:21:52'),
(896, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:22:09'),
(897, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:23:01'),
(898, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:23:09'),
(899, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:26:52'),
(900, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:26:57'),
(901, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:30:19'),
(902, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:30:19'),
(903, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:31:27'),
(904, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:36:01'),
(905, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:36:25'),
(906, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:37:36'),
(907, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:37:37'),
(908, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:37:43'),
(909, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:43:43'),
(910, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:43:43'),
(911, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:43:51'),
(912, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:45:51'),
(913, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:45:53'),
(914, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:48:04'),
(915, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:48:04'),
(916, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:48:05'),
(917, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:48:12'),
(918, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:48:22'),
(919, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:48:26'),
(920, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:51:48'),
(921, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:51:56'),
(922, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:56:13'),
(923, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:56:25'),
(924, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:58:04'),
(925, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:58:11'),
(926, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:58:18'),
(927, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 13:58:20'),
(928, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 13:58:25'),
(929, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:09:51'),
(930, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:09:52'),
(931, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:10:03'),
(932, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:12:07'),
(933, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:12:13'),
(934, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:16:26'),
(935, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:16:33'),
(936, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:30:37'),
(937, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:30:54'),
(938, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:33:24'),
(939, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:33:32'),
(940, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:34:14'),
(941, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:34:24'),
(942, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:38:01'),
(943, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:38:38'),
(944, 24, '2804:2788:c1c2:5500:58cf:b6ee:e400:4d3a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:39:18'),
(945, 24, '2804:2788:c1c2:5500:58cf:b6ee:e400:4d3a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:39:33'),
(946, 24, '2804:2788:c1c2:5500:58cf:b6ee:e400:4d3a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:39:54'),
(947, 24, '2804:2788:c1c2:5500:58cf:b6ee:e400:4d3a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:39:59'),
(948, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:40:53'),
(949, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:41:02'),
(950, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:50:55'),
(951, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:51:00'),
(952, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:52:08'),
(953, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:52:19'),
(954, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:52:36'),
(955, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:52:42'),
(956, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:55:34'),
(957, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:55:40'),
(958, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:55:41'),
(959, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:55:47'),
(960, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:55:47'),
(961, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:56:14'),
(962, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 14:56:15'),
(963, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 14:56:32'),
(964, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 15:01:23'),
(965, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 15:01:34'),
(966, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 15:39:57'),
(967, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 15:40:59'),
(968, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 15:41:00'),
(969, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 15:41:03'),
(970, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 15:41:03'),
(971, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 15:41:11'),
(972, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 15:41:11'),
(973, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 15:41:25'),
(974, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 15:42:05'),
(975, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 15:42:21'),
(976, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 15:42:21'),
(977, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 15:42:26'),
(978, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 15:59:17'),
(979, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 15:59:30'),
(980, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 15:59:47'),
(981, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 15:59:50'),
(982, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:07:09'),
(983, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 16:07:12'),
(984, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:10:53'),
(985, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 16:10:57'),
(986, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:10:57'),
(987, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 16:11:02'),
(988, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:14:17'),
(989, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 16:15:55'),
(990, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:19:15'),
(991, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 16:19:23'),
(992, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:20:26'),
(993, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 16:20:34'),
(994, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:21:07'),
(995, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 16:21:15'),
(996, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:22:24'),
(997, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:23:46'),
(998, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 16:34:45'),
(999, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:42:11'),
(1000, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 16:42:17'),
(1001, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:44:07'),
(1002, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 16:44:10'),
(1003, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:46:30'),
(1004, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 16:52:51'),
(1005, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:55:38'),
(1006, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 16:55:45'),
(1007, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:57:46'),
(1008, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 16:57:53'),
(1009, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:57:53'),
(1010, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 16:58:10'),
(1011, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 16:58:13'),
(1012, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 16:58:23'),
(1013, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 17:01:59'),
(1014, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 17:02:01'),
(1015, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 17:11:16'),
(1016, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 17:11:16'),
(1017, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 17:11:28'),
(1018, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 17:12:14'),
(1019, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 17:35:01'),
(1020, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 17:35:16'),
(1021, 24, '2804:2788:c1c2:5500:58cf:b6ee:e400:4d3a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 17:37:11'),
(1022, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 17:37:45'),
(1023, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 17:37:55'),
(1024, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 17:39:19'),
(1025, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 17:39:44'),
(1026, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 17:39:50'),
(1027, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 17:40:00'),
(1028, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 17:42:29'),
(1029, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 17:42:36'),
(1030, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 17:51:58'),
(1031, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 17:52:05'),
(1032, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 17:52:05'),
(1033, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 17:52:15'),
(1034, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 18:05:07'),
(1035, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 18:05:22'),
(1036, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 18:06:10'),
(1037, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 18:06:17'),
(1038, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 18:06:18'),
(1039, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 18:06:33'),
(1040, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 18:06:34'),
(1041, 24, '2804:2788:c1c2:5500:6cc2:3348:101e:5000', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 18:06:36'),
(1042, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 18:06:46'),
(1043, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 18:06:53'),
(1044, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 18:17:11'),
(1045, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 18:17:19'),
(1046, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 18:17:19'),
(1047, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 18:17:32'),
(1048, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 18:17:32'),
(1049, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 18:17:38'),
(1050, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 18:18:36'),
(1051, 24, '2804:2788:c165:3900:c405:752e:f7af:6eec', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-12 18:18:44'),
(1052, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-12 22:39:23'),
(1053, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-14 04:13:37'),
(1054, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-14 05:07:41'),
(1055, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-14 05:07:49'),
(1056, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-14 05:25:38'),
(1057, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-14 05:28:12'),
(1058, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-14 05:28:19'),
(1059, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-14 12:08:08'),
(1060, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-14 12:15:28'),
(1061, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-14 13:32:26'),
(1062, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-14 18:12:03'),
(1063, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-14 18:12:11'),
(1064, 24, '179.0.112.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-14 18:15:41'),
(1065, 24, '179.0.112.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-14 18:18:39'),
(1066, 24, '179.0.112.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-14 19:12:40'),
(1067, 24, '179.0.112.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-14 19:12:45'),
(1068, 24, '179.0.112.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-14 20:48:35'),
(1069, 24, '179.0.112.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-14 20:48:39'),
(1070, 24, '179.0.112.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-14 20:53:21'),
(1071, 24, '179.0.112.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-14 20:53:23'),
(1072, 24, '179.0.112.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-14 20:53:46'),
(1073, 24, '179.0.112.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-14 20:53:51'),
(1074, 24, '179.0.112.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-14 21:11:54'),
(1075, 24, '179.0.112.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-14 21:11:58'),
(1076, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-14 23:49:21'),
(1077, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-14 23:50:44'),
(1078, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 00:10:40'),
(1079, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 00:10:52'),
(1080, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 00:10:52'),
(1081, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 00:10:59'),
(1082, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 00:11:13'),
(1083, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 00:11:13'),
(1084, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 00:11:14'),
(1085, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 00:11:20'),
(1086, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 00:11:21'),
(1087, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 00:11:22'),
(1088, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 00:11:34'),
(1089, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 01:09:27'),
(1090, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 01:09:32'),
(1091, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 03:53:34'),
(1092, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 03:53:39'),
(1093, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 03:55:13'),
(1094, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 03:55:19'),
(1095, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 11:17:01'),
(1096, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 11:17:04'),
(1097, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 11:26:44'),
(1098, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 11:27:26'),
(1099, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 11:31:08'),
(1100, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 11:31:13'),
(1101, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 11:31:13'),
(1102, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 11:31:21'),
(1103, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 11:33:28'),
(1104, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 11:33:31'),
(1105, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 11:34:02'),
(1106, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 11:34:05'),
(1107, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 11:34:05'),
(1108, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 11:34:09'),
(1109, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 11:36:43'),
(1110, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 11:36:47'),
(1111, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 11:51:10'),
(1112, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 11:51:18'),
(1113, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 11:52:53'),
(1114, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 11:52:57'),
(1115, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 12:12:41'),
(1116, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 12:13:51'),
(1117, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 12:15:42'),
(1118, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 12:15:49');
INSERT INTO `safenode_hv_attempts` (`id`, `api_key_id`, `ip_address`, `user_agent`, `referer`, `attempt_type`, `reason`, `created_at`) VALUES
(1119, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 12:41:11'),
(1120, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 12:45:44'),
(1121, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 12:45:47'),
(1122, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 12:47:34'),
(1123, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:10:04'),
(1124, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:10:25'),
(1125, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:10:25'),
(1126, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:10:28'),
(1127, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:10:28'),
(1128, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:10:35'),
(1129, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:17:15'),
(1130, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:17:18'),
(1131, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:32:26'),
(1132, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:32:30'),
(1133, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:34:06'),
(1134, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:34:17'),
(1135, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:36:23'),
(1136, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:36:28'),
(1137, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:36:29'),
(1138, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:36:32'),
(1139, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:40:22'),
(1140, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:40:28'),
(1141, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:41:38'),
(1142, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:41:42'),
(1143, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:42:30'),
(1144, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:42:40'),
(1145, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:42:57'),
(1146, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:43:00'),
(1147, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:43:30'),
(1148, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:43:35'),
(1149, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:44:17'),
(1150, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:44:25'),
(1151, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:45:47'),
(1152, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:46:08'),
(1153, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:48:32'),
(1154, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:48:43'),
(1155, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:48:55'),
(1156, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:49:00'),
(1157, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:49:30'),
(1158, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:49:41'),
(1159, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:51:42'),
(1160, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:51:59'),
(1161, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:53:49'),
(1162, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:54:31'),
(1163, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 13:57:27'),
(1164, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 13:57:51'),
(1165, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 14:06:09'),
(1166, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 14:06:12'),
(1167, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 14:06:58'),
(1168, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 14:07:11'),
(1169, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 14:07:15'),
(1170, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 14:07:32'),
(1171, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 14:22:07'),
(1172, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 14:22:14'),
(1173, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 14:25:01'),
(1174, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 14:25:06'),
(1175, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 14:27:03'),
(1176, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 14:27:03'),
(1177, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 14:27:08'),
(1178, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 14:27:08'),
(1179, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 14:27:14'),
(1180, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 14:35:35'),
(1181, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 14:35:38'),
(1182, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 14:36:33'),
(1183, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 14:36:37'),
(1184, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 14:36:37'),
(1185, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 14:36:41'),
(1186, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 15:06:11'),
(1187, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 15:07:18'),
(1188, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 15:07:19'),
(1189, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 15:07:22'),
(1190, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 15:07:30'),
(1191, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 15:07:31'),
(1192, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 15:07:32'),
(1193, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 15:07:40'),
(1194, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 15:07:40'),
(1195, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 15:07:41'),
(1196, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 15:07:51'),
(1197, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 15:08:13'),
(1198, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 15:08:21'),
(1199, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 15:08:39'),
(1200, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 15:09:51'),
(1201, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 15:09:58'),
(1202, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 15:09:58'),
(1203, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 15:10:00'),
(1204, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 15:10:06'),
(1205, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 15:10:06'),
(1206, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 15:10:07'),
(1207, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 15:10:19'),
(1208, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 15:54:13'),
(1209, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 15:54:21'),
(1210, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 16:00:19'),
(1211, 24, '2804:2788:c165:3900:4458:905:ea12:3f6a', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 16:00:37'),
(1212, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 16:06:41'),
(1213, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 16:06:43'),
(1214, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 16:16:29'),
(1215, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 16:16:34'),
(1216, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 16:53:50'),
(1217, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 16:55:59'),
(1218, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 16:56:08'),
(1219, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 16:56:35'),
(1220, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 17:11:27'),
(1221, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 17:25:10'),
(1222, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 17:26:15'),
(1223, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 17:32:48'),
(1224, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 17:32:57'),
(1225, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 17:33:11'),
(1226, 24, '2804:2788:c1c2:5500:3431:6321:7974:969b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 17:33:39'),
(1227, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 18:11:03'),
(1228, 24, '2804:2788:c1c2:5500:3183:156f:99b0:8bf3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 18:11:05'),
(1229, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 20:28:48'),
(1230, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 20:30:04'),
(1231, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 23:11:16'),
(1232, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-15 23:11:29'),
(1233, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 23:54:27'),
(1234, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-15 23:55:13'),
(1235, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 00:05:44'),
(1236, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 00:07:28'),
(1237, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 00:07:29'),
(1238, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 00:11:56'),
(1239, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 00:12:13'),
(1240, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 00:12:15'),
(1241, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 00:12:39'),
(1242, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 00:12:41'),
(1243, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 00:12:49'),
(1244, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 00:12:53'),
(1245, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 00:13:02'),
(1246, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 00:13:06'),
(1247, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 00:13:54'),
(1248, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 00:42:48'),
(1249, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 00:42:55'),
(1250, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 00:47:28'),
(1251, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 00:47:48'),
(1252, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 00:48:55'),
(1253, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 00:49:04'),
(1254, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 00:57:38'),
(1255, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 00:57:43'),
(1256, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:03:49'),
(1257, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:04:56'),
(1258, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:14:00'),
(1259, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:14:02'),
(1260, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:15:05'),
(1261, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:17:52'),
(1262, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:18:22'),
(1263, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:18:29'),
(1264, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:18:29'),
(1265, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:18:37'),
(1266, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:20:45'),
(1267, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:22:31'),
(1268, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:24:20'),
(1269, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:24:30'),
(1270, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:24:43'),
(1271, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:24:57'),
(1272, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:25:17'),
(1273, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:25:20'),
(1274, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:25:50'),
(1275, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:25:54'),
(1276, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:26:31'),
(1277, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:26:36'),
(1278, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:27:25'),
(1279, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:27:27'),
(1280, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:27:28'),
(1281, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:27:32'),
(1282, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:30:03'),
(1283, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:30:28'),
(1284, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:32:12'),
(1285, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:32:14'),
(1286, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:33:37'),
(1287, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:40:13'),
(1288, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 01:54:26'),
(1289, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 01:54:50'),
(1290, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 02:05:25'),
(1291, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 02:05:28'),
(1292, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 02:11:35'),
(1293, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 02:26:52'),
(1294, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 02:26:53'),
(1295, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 02:27:25'),
(1296, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 02:27:37'),
(1297, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 02:35:38'),
(1298, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 02:35:43'),
(1299, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 02:35:43'),
(1300, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 02:35:51'),
(1301, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 02:36:28'),
(1302, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 02:36:42'),
(1303, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 02:48:01'),
(1304, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 02:48:39'),
(1305, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 02:48:48'),
(1306, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 02:49:00'),
(1307, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 02:57:50'),
(1308, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 02:58:11'),
(1309, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 03:03:40'),
(1310, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 03:04:05'),
(1311, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 03:25:29'),
(1312, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 03:25:45'),
(1313, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 03:32:10'),
(1314, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 03:32:14'),
(1315, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 03:37:03'),
(1316, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 03:37:09'),
(1317, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 03:37:24'),
(1318, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 03:37:30'),
(1319, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 04:04:55'),
(1320, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 04:05:07'),
(1321, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 04:05:41'),
(1322, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 04:05:47'),
(1323, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 04:10:55'),
(1324, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 04:10:59'),
(1325, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 04:11:00'),
(1326, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 04:11:02'),
(1327, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 04:55:36'),
(1328, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 04:55:50'),
(1329, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 04:55:51'),
(1330, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 04:55:55'),
(1331, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 04:56:16'),
(1332, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 04:56:19'),
(1333, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 04:56:35'),
(1334, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 04:56:40'),
(1335, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 05:03:58'),
(1336, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 05:04:01'),
(1337, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 05:04:01'),
(1338, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 05:04:04'),
(1339, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 05:04:54'),
(1340, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 05:05:05'),
(1341, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 05:53:22'),
(1342, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 05:53:31'),
(1343, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 05:56:43'),
(1344, 24, '177.23.18.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 05:56:47'),
(1345, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 11:07:43'),
(1346, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 11:07:48'),
(1347, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 11:10:31'),
(1348, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 11:10:34'),
(1349, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 11:10:35'),
(1350, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 11:10:39');
INSERT INTO `safenode_hv_attempts` (`id`, `api_key_id`, `ip_address`, `user_agent`, `referer`, `attempt_type`, `reason`, `created_at`) VALUES
(1351, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 11:23:45'),
(1352, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 11:25:40'),
(1353, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 11:26:03'),
(1354, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 11:26:22'),
(1355, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 11:34:54'),
(1356, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 11:34:59'),
(1357, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 11:44:37'),
(1358, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 11:54:15'),
(1359, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 11:54:17'),
(1360, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 11:54:17'),
(1361, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 11:54:24'),
(1362, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 11:59:08'),
(1363, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 11:59:34'),
(1364, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 11:59:41'),
(1365, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 12:29:33'),
(1366, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 12:29:36'),
(1367, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 12:34:23'),
(1368, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 12:34:28'),
(1369, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 12:34:39'),
(1370, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 12:34:44'),
(1371, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 12:34:53'),
(1372, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 12:35:13'),
(1373, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 13:12:22'),
(1374, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 13:17:15'),
(1375, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 13:17:20'),
(1376, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 13:17:52'),
(1377, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 13:18:02'),
(1378, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 13:18:04'),
(1379, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 13:18:31'),
(1380, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 13:42:39'),
(1381, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 13:47:31'),
(1382, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 13:47:34'),
(1383, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 14:08:38'),
(1384, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 14:08:54'),
(1385, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 14:11:07'),
(1386, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 14:11:13'),
(1387, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 14:15:34'),
(1388, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 14:15:41'),
(1389, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 14:20:08'),
(1390, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 14:20:14'),
(1391, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 14:22:12'),
(1392, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 14:22:19'),
(1393, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 14:22:19'),
(1394, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 14:22:37'),
(1395, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 14:23:09'),
(1396, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 14:23:13'),
(1397, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 14:24:52'),
(1398, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 14:29:29'),
(1399, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 14:29:39'),
(1400, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 14:29:56'),
(1401, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 14:30:24'),
(1402, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 14:33:44'),
(1403, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 14:34:00'),
(1404, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 14:34:40'),
(1405, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 14:35:24'),
(1406, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 14:35:43'),
(1407, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 14:39:44'),
(1408, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 14:39:46'),
(1409, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 14:43:28'),
(1410, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 14:44:40'),
(1411, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 14:45:43'),
(1412, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 15:19:46'),
(1413, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 15:20:07'),
(1414, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 15:20:07'),
(1415, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 15:20:10'),
(1416, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 15:21:01'),
(1417, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 15:22:48'),
(1418, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 15:39:21'),
(1419, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 15:39:29'),
(1420, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 15:56:24'),
(1421, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 15:56:34'),
(1422, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 16:19:26'),
(1423, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 16:19:29'),
(1424, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 16:19:49'),
(1425, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 16:19:53'),
(1426, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 16:20:03'),
(1427, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 16:20:06'),
(1428, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 16:27:09'),
(1429, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 16:27:17'),
(1430, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 16:27:38'),
(1431, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 16:27:41'),
(1432, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 16:27:41'),
(1433, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 16:27:46'),
(1434, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 16:27:46'),
(1435, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 16:28:04'),
(1436, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 17:40:04'),
(1437, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 17:40:08'),
(1438, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 17:50:16'),
(1439, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 17:50:44'),
(1440, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 17:50:44'),
(1441, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 17:52:02'),
(1442, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 17:52:02'),
(1443, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 17:52:59'),
(1444, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 17:52:59'),
(1445, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 17:53:03'),
(1446, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 17:53:03'),
(1447, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 17:53:56'),
(1448, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:20:48'),
(1449, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:20:53'),
(1450, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:21:47'),
(1451, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:21:52'),
(1452, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:23:06'),
(1453, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:23:09'),
(1454, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:24:17'),
(1455, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:24:29'),
(1456, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:25:28'),
(1457, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:25:32'),
(1458, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:33:30'),
(1459, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:33:33'),
(1460, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:33:34'),
(1461, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:34:17'),
(1462, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:34:41'),
(1463, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:35:51'),
(1464, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:35:52'),
(1465, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:36:08'),
(1466, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:36:08'),
(1467, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:36:19'),
(1468, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:36:19'),
(1469, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:36:26'),
(1470, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:37:08'),
(1471, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:37:22'),
(1472, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:37:23'),
(1473, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:39:15'),
(1474, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:39:44'),
(1475, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:40:02'),
(1476, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:42:57'),
(1477, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:43:25'),
(1478, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:45:17'),
(1479, 24, '2804:2788:c1c2:5500:c9f7:f55c:a6a1:5176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:45:41'),
(1480, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:51:06'),
(1481, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:51:21'),
(1482, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:53:13'),
(1483, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:53:32'),
(1484, 24, '2804:2788:c1c2:5500:5484:1a89:d41a:ab82', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:53:43'),
(1485, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:53:51'),
(1486, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:53:53'),
(1487, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:53:53'),
(1488, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:53:55'),
(1489, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:53:55'),
(1490, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:54:11'),
(1491, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:54:11'),
(1492, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:54:14'),
(1493, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:54:14'),
(1494, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:54:32'),
(1495, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:55:05'),
(1496, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:55:08'),
(1497, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:55:08'),
(1498, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:55:12'),
(1499, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:55:24'),
(1500, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:55:26'),
(1501, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:55:26'),
(1502, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:55:52'),
(1503, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:55:52'),
(1504, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 18:57:15'),
(1505, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 18:58:02'),
(1506, 24, '2804:2788:c165:3900:5c4d:fef5:9ab5:47e1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 19:00:40'),
(1507, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 22:26:32'),
(1508, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 22:26:36'),
(1509, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 22:26:36'),
(1510, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 22:26:39'),
(1511, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 22:26:39'),
(1512, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 22:26:51'),
(1513, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 22:26:51'),
(1514, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 22:27:06'),
(1515, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 22:27:06'),
(1516, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 22:37:44'),
(1517, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 23:27:52'),
(1518, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 23:29:18'),
(1519, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 23:37:24'),
(1520, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 23:37:29'),
(1521, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 23:59:05'),
(1522, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 23:59:09'),
(1523, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-16 23:59:09'),
(1524, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-16 23:59:24'),
(1525, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 00:25:33'),
(1526, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 00:26:34'),
(1527, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 00:26:34'),
(1528, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 00:26:37'),
(1529, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 00:26:37'),
(1530, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 00:26:41'),
(1531, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 00:26:41'),
(1532, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 00:26:55'),
(1533, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 00:27:00'),
(1534, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 00:27:04'),
(1535, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 00:27:27'),
(1536, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 00:27:58'),
(1537, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 00:27:58'),
(1538, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 00:28:15'),
(1539, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 00:32:21'),
(1540, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 00:32:24'),
(1541, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 00:33:13'),
(1542, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 00:33:20'),
(1543, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 00:37:47'),
(1544, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 00:40:45'),
(1545, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 00:40:46'),
(1546, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 00:41:02'),
(1547, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 00:50:12'),
(1548, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 00:50:43'),
(1549, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 00:52:28'),
(1550, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 00:52:37'),
(1551, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 00:52:42'),
(1552, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 00:53:28'),
(1553, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 01:12:04'),
(1554, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 01:12:12'),
(1555, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 01:18:58'),
(1556, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 01:19:44'),
(1557, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 01:19:53'),
(1558, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 01:20:54'),
(1559, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 01:21:07'),
(1560, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 01:21:11'),
(1561, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 01:21:25'),
(1562, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 01:22:25'),
(1563, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 01:22:34'),
(1564, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 01:31:01'),
(1565, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 01:34:34'),
(1566, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 01:35:08'),
(1567, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 01:35:12'),
(1568, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 01:36:18'),
(1569, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 01:38:50'),
(1570, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 01:44:41'),
(1571, 24, '179.0.112.119', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 01:45:08'),
(1572, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 09:55:32'),
(1573, 24, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 09:55:36'),
(1574, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:13:01');
INSERT INTO `safenode_hv_attempts` (`id`, `api_key_id`, `ip_address`, `user_agent`, `referer`, `attempt_type`, `reason`, `created_at`) VALUES
(1575, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:14:52'),
(1576, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:14:52'),
(1577, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:15:06'),
(1578, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:15:07'),
(1579, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 11:15:31'),
(1580, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:16:13'),
(1581, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 11:16:25'),
(1582, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:16:38'),
(1583, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 11:16:50'),
(1584, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:35:23'),
(1585, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 11:36:53'),
(1586, 24, '2804:2788:c1c2:5500:8123:246c:b0de:5738', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:37:01'),
(1587, 24, '2804:2788:c1c2:5500:8123:246c:b0de:5738', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 11:38:08'),
(1588, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:39:19'),
(1589, 24, '2804:2788:c165:3900:3dcc:38e2:861:f316', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:40:18'),
(1590, 24, '2804:2788:c1c2:5500:8123:246c:b0de:5738', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:41:36'),
(1591, 24, '2804:2788:c165:3900:3dcc:38e2:861:f316', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 11:41:44'),
(1592, 24, '2804:2788:c1c2:5500:8123:246c:b0de:5738', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 11:41:47'),
(1593, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 11:47:19'),
(1594, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:47:19'),
(1595, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 11:47:26'),
(1596, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:48:52'),
(1597, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 11:49:00'),
(1598, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:50:24'),
(1599, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 11:50:32'),
(1600, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 11:52:00'),
(1601, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-17 11:52:12'),
(1602, 24, '2804:18:606f:cc52:e061:27ff:fe8e:bbcc', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'https://kronx.sbs', 'init', NULL, '2025-12-17 13:02:59'),
(1603, 24, '2804:18:606f:cc52:e061:27ff:fe8e:bbcc', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'https://kronx.sbs', 'validate', NULL, '2025-12-17 13:03:23'),
(1604, 24, '2804:18:606f:cc52:e061:27ff:fe8e:bbcc', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'https://kronx.sbs', 'init', NULL, '2025-12-17 13:03:24'),
(1605, 24, '2804:18:6865:9ccf:1881:e12f:6446:f801', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'https://kronx.sbs', 'init', NULL, '2025-12-17 13:03:44'),
(1606, 24, '2804:18:606f:cc52:e061:27ff:fe8e:bbcc', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'https://kronx.sbs', 'validate', NULL, '2025-12-17 13:03:51'),
(1607, 24, '2804:18:6865:9ccf:1881:e12f:6446:f801', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'https://kronx.sbs', 'validate', NULL, '2025-12-17 13:04:48'),
(1608, 24, '2804:18:6865:9ccf:1881:e12f:6446:f801', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'https://kronx.sbs', 'init', NULL, '2025-12-17 13:08:16'),
(1609, 24, '2804:18:6865:9ccf:1881:e12f:6446:f801', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'https://kronx.sbs', 'validate', NULL, '2025-12-17 13:08:35'),
(1610, 24, '2804:2788:c1c2:5500:7410:dfb6:c206:f62c', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'https://kronx.sbs', 'init', NULL, '2025-12-17 13:24:36'),
(1611, 24, '138.204.185.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', 'https://kronx.sbs', 'init', NULL, '2025-12-17 13:29:04'),
(1612, 24, '2804:18:6867:ef5f:2ca7:69ff:fedf:1f7c', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'https://kronx.sbs', 'init', NULL, '2025-12-17 14:15:48'),
(1613, 24, '2804:18:6867:ef5f:2ca7:69ff:fedf:1f7c', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'https://kronx.sbs', 'validate', NULL, '2025-12-17 14:16:04'),
(1614, 24, '2804:2788:c1c2:5500:8123:246c:b0de:5738', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'https://kronx.sbs', 'init', NULL, '2025-12-17 14:33:45'),
(1615, 24, '2804:2788:c1c2:5500:8123:246c:b0de:5738', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'https://kronx.sbs', 'validate', NULL, '2025-12-17 14:38:02'),
(1616, 24, '2804:2788:c1c2:5500:8123:246c:b0de:5738', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'https://kronx.sbs', 'init', NULL, '2025-12-17 14:38:03'),
(1617, 24, '2804:2788:c1c2:5500:8123:246c:b0de:5738', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'https://kronx.sbs', 'validate', NULL, '2025-12-17 14:38:10'),
(1618, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-17 16:45:10'),
(1619, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'https://kronx.sbs', 'init', NULL, '2025-12-17 16:45:23'),
(1620, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'https://kronx.sbs', 'init', NULL, '2025-12-17 16:45:32'),
(1621, 24, '2804:2788:c1c2:5500:41bf:6781:684:ba28', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'https://kronx.sbs', 'init', NULL, '2025-12-17 16:45:34'),
(1622, 24, '2804:18:6000:2269:c46f:88ff:fe76:eee1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'https://kronx.sbs', 'init', NULL, '2025-12-18 10:14:48'),
(1623, 24, '2804:18:6000:2269:c46f:88ff:fe76:eee1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', 'https://kronx.sbs', 'validate', NULL, '2025-12-18 10:15:10'),
(1624, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 11:18:17'),
(1625, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 11:21:18'),
(1626, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 11:22:22'),
(1627, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 11:22:35'),
(1628, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 11:26:32'),
(1629, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 11:26:36'),
(1630, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 11:26:36'),
(1631, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 11:26:41'),
(1632, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 11:26:41'),
(1633, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 11:26:51'),
(1634, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 11:26:51'),
(1635, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 11:26:56'),
(1636, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 11:26:56'),
(1637, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 11:26:58'),
(1638, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 11:26:58'),
(1639, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 11:30:51'),
(1640, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 12:26:19'),
(1641, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 12:26:22'),
(1642, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 12:30:45'),
(1643, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 12:30:48'),
(1644, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 12:31:13'),
(1645, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 12:31:16'),
(1646, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 12:31:16'),
(1647, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 12:31:20'),
(1648, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 12:31:20'),
(1649, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 12:31:29'),
(1650, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 12:35:49'),
(1651, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 12:36:02'),
(1652, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 12:36:36'),
(1653, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 12:36:39'),
(1654, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 12:37:07'),
(1655, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 12:37:24'),
(1656, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 12:40:39'),
(1657, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 12:40:53'),
(1658, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 12:41:19'),
(1659, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 12:41:22'),
(1660, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 13:02:54'),
(1661, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 13:03:59'),
(1662, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 13:19:12'),
(1663, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 13:19:19'),
(1664, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 13:20:25'),
(1665, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 13:20:34'),
(1666, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 13:33:40'),
(1667, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 13:33:49'),
(1668, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 13:41:32'),
(1669, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 13:41:39'),
(1670, 24, '69.160.160.53', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/80.0.3987.132 Safari/537.36', 'https://kronx.sbs/app/main/Views/auth/login.php', 'init', NULL, '2025-12-18 14:00:18'),
(1671, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 14:18:46'),
(1672, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 14:19:04'),
(1673, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 14:19:26'),
(1674, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 14:19:40'),
(1675, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 14:20:15'),
(1676, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 14:20:27'),
(1677, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 14:21:15'),
(1678, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 14:22:32'),
(1679, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-18 14:26:30'),
(1680, 24, '2804:2788:c1c2:5500:10fe:67f1:5fb5:e66c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-18 14:26:46'),
(1681, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 11:16:18'),
(1682, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 11:18:09'),
(1683, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 11:23:14'),
(1684, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 11:24:15'),
(1685, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 11:24:36'),
(1686, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 11:24:39'),
(1687, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 11:26:45'),
(1688, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 11:26:50'),
(1689, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 11:28:08'),
(1690, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 11:28:20'),
(1691, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 11:28:28'),
(1692, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 11:28:38'),
(1693, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 11:33:15'),
(1694, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 11:33:20'),
(1695, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 11:34:25'),
(1696, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 11:39:26'),
(1697, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 11:40:37'),
(1698, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 11:40:54'),
(1699, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 11:44:53'),
(1700, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 11:45:02'),
(1701, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 11:56:26'),
(1702, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 11:56:35'),
(1703, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 11:56:36'),
(1704, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 11:56:39'),
(1705, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 11:56:39'),
(1706, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 11:56:46'),
(1707, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 11:56:46'),
(1708, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 11:57:00'),
(1709, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 12:38:03'),
(1710, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 12:38:16'),
(1711, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 12:43:06'),
(1712, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 12:43:12'),
(1713, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 12:44:19'),
(1714, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 12:44:40'),
(1715, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 12:46:03'),
(1716, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 12:46:19'),
(1717, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 12:50:17'),
(1718, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 12:50:24'),
(1719, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 12:50:57'),
(1720, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 12:51:04'),
(1721, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 12:51:54'),
(1722, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 12:52:03'),
(1723, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 12:59:00'),
(1724, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 12:59:27'),
(1725, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 13:03:47'),
(1726, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 13:03:50'),
(1727, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 13:07:11'),
(1728, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 13:07:18'),
(1729, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 13:07:34'),
(1730, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 13:07:43'),
(1731, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 13:07:54'),
(1732, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 13:08:03'),
(1733, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 13:08:28'),
(1734, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 13:08:39'),
(1735, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 13:09:25'),
(1736, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 13:09:36'),
(1737, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 13:23:40'),
(1738, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 13:26:18'),
(1739, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 13:57:48'),
(1740, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 13:57:57'),
(1741, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 14:01:01'),
(1742, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 14:01:31'),
(1743, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 14:12:11'),
(1744, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 14:13:24'),
(1745, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 14:15:47'),
(1746, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 14:15:55'),
(1747, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 14:20:30'),
(1748, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 14:20:39'),
(1749, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 14:21:39'),
(1750, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 14:22:02'),
(1751, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 14:51:09'),
(1752, 24, '2804:2788:c1c2:5500:6de7:25ff:e52c:6fc1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 14:51:19'),
(1753, 24, '170.84.77.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 21:35:35'),
(1754, 24, '170.84.77.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 21:44:34'),
(1755, 24, '170.84.77.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 21:44:36'),
(1756, 24, '170.84.77.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 21:44:38'),
(1757, 24, '170.84.77.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-19 21:44:40'),
(1758, 24, '170.84.77.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-19 21:51:32'),
(1759, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:32:45'),
(1760, 24, '45.189.167.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:34:39'),
(1761, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-20 14:35:12'),
(1762, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:35:12'),
(1763, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:35:58'),
(1764, 24, '45.189.167.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-20 14:36:29'),
(1765, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-20 14:36:54'),
(1766, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:36:54'),
(1767, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-20 14:38:38'),
(1768, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:38:38'),
(1769, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-20 14:39:40'),
(1770, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:39:40'),
(1771, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-20 14:40:00'),
(1772, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:40:00'),
(1773, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-20 14:40:04'),
(1774, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:40:04'),
(1775, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-20 14:41:57'),
(1776, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:41:58'),
(1777, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-20 14:43:51'),
(1778, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:43:52'),
(1779, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:45:28'),
(1780, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-20 14:45:34'),
(1781, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:45:34'),
(1782, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-20 14:45:47'),
(1783, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:45:47'),
(1784, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:46:46'),
(1785, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-20 14:46:51'),
(1786, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:46:51'),
(1787, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'validate', NULL, '2025-12-20 14:47:30'),
(1788, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost', 'init', NULL, '2025-12-20 14:47:30');

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

--
-- Despejando dados para a tabela `safenode_hv_rate_limits`
--

INSERT INTO `safenode_hv_rate_limits` (`id`, `api_key_id`, `ip_address`, `request_count`, `window_start`, `created_at`) VALUES
(1771, 24, '2804:3130:8105:ae00:adca:4509:c836:8cc6', 1, '2025-12-20 11:47:00', '2025-12-20 14:47:30');

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
-- Estrutura para tabela `safenode_mail_analytics`
--

CREATE TABLE `safenode_mail_analytics` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `date` date NOT NULL COMMENT 'Data da métrica',
  `emails_sent` int(11) DEFAULT 0 COMMENT 'E-mails enviados neste dia',
  `emails_delivered` int(11) DEFAULT 0 COMMENT 'E-mails entregues',
  `emails_opened` int(11) DEFAULT 0 COMMENT 'E-mails abertos',
  `emails_clicked` int(11) DEFAULT 0 COMMENT 'E-mails com cliques',
  `emails_bounced` int(11) DEFAULT 0 COMMENT 'E-mails com bounce',
  `emails_complained` int(11) DEFAULT 0 COMMENT 'E-mails marcados como spam'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_mail_logs`
--

CREATE TABLE `safenode_mail_logs` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `to_email` varchar(255) NOT NULL COMMENT 'E-mail destinatário',
  `subject` varchar(500) NOT NULL COMMENT 'Assunto do e-mail',
  `template_name` varchar(100) DEFAULT NULL COMMENT 'Nome do template usado',
  `status` enum('sent','error','pending') DEFAULT 'pending' COMMENT 'Status do envio',
  `error_message` text DEFAULT NULL COMMENT 'Mensagem de erro (se houver)',
  `sent_at` timestamp NULL DEFAULT NULL COMMENT 'Data/hora do envio',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_mail_projects`
--

CREATE TABLE `safenode_mail_projects` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Nome do projeto',
  `sender_email` varchar(255) NOT NULL COMMENT 'E-mail remetente',
  `sender_name` varchar(255) DEFAULT NULL COMMENT 'Nome do remetente',
  `token` varchar(64) NOT NULL COMMENT 'Token de autenticação',
  `monthly_limit` int(11) DEFAULT 500 COMMENT 'Limite mensal de e-mails',
  `emails_sent_this_month` int(11) DEFAULT 0 COMMENT 'E-mails enviados este mês',
  `last_reset_date` date DEFAULT NULL COMMENT 'Data do último reset mensal',
  `rate_limit_per_minute` int(11) DEFAULT 5 COMMENT 'Rate limit por minuto',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Projeto ativo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_mail_rate_limits`
--

CREATE TABLE `safenode_mail_rate_limits` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `minute_window` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Janela de 1 minuto',
  `emails_count` int(11) DEFAULT 0 COMMENT 'Contador de e-mails neste minuto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_mail_templates`
--

CREATE TABLE `safenode_mail_templates` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Nome do template',
  `subject` varchar(500) NOT NULL COMMENT 'Assunto padrão',
  `html_content` text NOT NULL COMMENT 'Conteúdo HTML do template',
  `text_content` text DEFAULT NULL COMMENT 'Conteúdo texto alternativo',
  `variables` text DEFAULT NULL COMMENT 'JSON com variáveis disponíveis',
  `is_default` tinyint(1) DEFAULT 0 COMMENT 'Template padrão do projeto',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_mail_webhooks`
--

CREATE TABLE `safenode_mail_webhooks` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `url` varchar(500) NOT NULL COMMENT 'URL do webhook',
  `events` text DEFAULT NULL COMMENT 'JSON com eventos a escutar (sent, error, etc)',
  `secret` varchar(64) DEFAULT NULL COMMENT 'Secret para assinatura do webhook',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Webhook ativo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_ml_model`
--

CREATE TABLE `safenode_ml_model` (
  `id` int(11) NOT NULL,
  `model_version` varchar(50) DEFAULT '1.0',
  `weights_data` text DEFAULT NULL,
  `accuracy` decimal(5,4) DEFAULT NULL,
  `precision_score` decimal(5,4) DEFAULT NULL,
  `recall_score` decimal(5,4) DEFAULT NULL,
  `f1_score` decimal(5,4) DEFAULT NULL,
  `false_positive_rate` decimal(5,4) DEFAULT NULL,
  `samples_analyzed` int(11) DEFAULT NULL,
  `trained_at` datetime DEFAULT current_timestamp()
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
(11, 17, 'lavosiersilva02@gmail.com', '434127', 'email_verification', '2025-12-14 00:18:06', 1, '2025-12-14 00:18:06', 0, '2025-12-14 00:11:26');

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

--
-- Despejando dados para a tabela `safenode_password_reset_otp`
--

INSERT INTO `safenode_password_reset_otp` (`id`, `user_id`, `email`, `otp_code`, `expires_at`, `used_at`, `attempts`, `max_attempts`, `ip_address`, `created_at`) VALUES
(2, 10, 'slavosier298@gmail.com', '188522', '2025-12-13 19:34:56', NULL, 0, 5, '170.84.77.243', '2025-12-13 22:24:56'),
(3, 17, 'lavosiersilva02@gmail.com', '809274', '2025-12-13 21:33:45', NULL, 0, 5, '170.84.77.243', '2025-12-14 00:23:45'),
(4, 10, 'slavosier298@gmail.com', '449868', '2025-12-13 22:52:24', NULL, 0, 5, '170.84.77.243', '2025-12-14 01:42:24'),
(5, 10, 'slavosier298@gmail.com', '558770', '2025-12-13 22:55:47', NULL, 0, 5, '170.84.77.243', '2025-12-14 01:45:47'),
(6, 17, 'lavosiersilva02@gmail.com', '847598', '2025-12-13 22:56:56', NULL, 0, 5, '170.84.77.243', '2025-12-14 01:46:56'),
(7, 17, 'lavosiersilva02@gmail.com', '855331', '2025-12-13 23:17:29', NULL, 0, 5, '170.84.77.243', '2025-12-14 02:07:29'),
(8, 17, 'lavosiersilva02@gmail.com', '655505', '2025-12-13 23:32:28', NULL, 0, 5, '170.84.77.243', '2025-12-14 02:22:28'),
(9, 17, 'lavosiersilva02@gmail.com', '813028', '2025-12-13 23:49:24', NULL, 0, 5, '170.84.77.243', '2025-12-14 02:39:24'),
(10, 17, 'lavosiersilva02@gmail.com', '871278', '2025-12-14 00:06:30', NULL, 0, 5, '170.84.77.243', '2025-12-14 02:56:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_protection_streaks`
--

CREATE TABLE `safenode_protection_streaks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL DEFAULT 0,
  `current_streak` int(11) NOT NULL DEFAULT 0,
  `longest_streak` int(11) NOT NULL DEFAULT 0,
  `last_protected_date` date NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `safenode_protection_streaks`
--

INSERT INTO `safenode_protection_streaks` (`id`, `user_id`, `site_id`, `current_streak`, `longest_streak`, `last_protected_date`, `enabled`, `created_at`, `updated_at`) VALUES
(1, 1, 0, 1, 0, '2025-12-25', 1, '2025-12-21 19:28:21', '2025-12-25 15:19:06');

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_quarantine`
--

CREATE TABLE `safenode_quarantine` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `threat_score` int(11) DEFAULT 0,
  `threat_type` varchar(50) DEFAULT NULL,
  `violation_count` int(11) DEFAULT 0,
  `status` enum('active','released','blocked') DEFAULT 'active',
  `release_reason` varchar(100) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `released_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_quarantine_activity`
--

CREATE TABLE `safenode_quarantine_activity` (
  `id` int(11) NOT NULL,
  `quarantine_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `request_uri` varchar(500) DEFAULT NULL,
  `threat_score` int(11) DEFAULT 0,
  `threat_type` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Estrutura para tabela `safenode_security_audits`
--

CREATE TABLE `safenode_security_audits` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `audit_type` varchar(50) NOT NULL COMMENT 'headers, endpoints, waf, rate_limit, etc',
  `status` enum('pending','running','completed','failed') DEFAULT 'pending',
  `security_score` int(11) DEFAULT 0 COMMENT 'Score de 0 a 100',
  `total_checks` int(11) DEFAULT 0,
  `passed_checks` int(11) DEFAULT 0,
  `failed_checks` int(11) DEFAULT 0,
  `warnings` int(11) DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `safenode_security_audits`
--

INSERT INTO `safenode_security_audits` (`id`, `site_id`, `audit_type`, `status`, `security_score`, `total_checks`, `passed_checks`, `failed_checks`, `warnings`, `started_at`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 5, 'full', 'completed', 50, 17, 3, 3, 11, '2025-12-17 17:38:37', '2025-12-17 17:38:37', '2025-12-17 17:38:37', '2025-12-17 17:38:37');

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

--
-- Despejando dados para a tabela `safenode_security_logs`
--

INSERT INTO `safenode_security_logs` (`id`, `ip_address`, `user_agent`, `request_uri`, `request_method`, `request_headers`, `request_body`, `threat_type`, `threat_details`, `threat_score`, `action_taken`, `response_code`, `response_time`, `cloudflare_ray`, `cloudflare_country`, `user_id`, `session_id`, `site_id`, `country_code`, `referer`, `created_at`) VALUES
(1, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/forgot-password.php', 'POST', NULL, NULL, 'password_reset_requested', NULL, 0, 'password_reset_request', 200, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://safenode.cloud/forgot-password', '2025-12-13 21:14:41'),
(2, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/forgot-password.php', 'POST', NULL, NULL, 'password_reset_requested', NULL, 0, 'password_reset_request', 200, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://safenode.cloud/forgot-password', '2025-12-13 21:22:51'),
(3, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/forgot-password.php', 'POST', NULL, NULL, 'password_reset_requested', NULL, 0, 'password_reset_request', 200, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://safenode.cloud/forgot-password', '2025-12-13 21:23:06'),
(4, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/forgot-password.php', 'POST', NULL, NULL, 'password_reset_requested', NULL, 0, 'password_reset_request', 200, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://safenode.cloud/forgot-password', '2025-12-13 21:30:38'),
(5, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/forgot-password.php', 'POST', NULL, NULL, 'password_reset_requested', NULL, 0, 'password_reset_request', 200, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://safenode.cloud/forgot-password', '2025-12-13 21:30:46'),
(6, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/forgot-password.php', 'POST', NULL, NULL, 'password_reset_requested', NULL, 0, 'password_reset_request', 200, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://safenode.cloud/forgot-password', '2025-12-13 21:37:48'),
(7, '170.84.77.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/forgot-password.php', 'POST', NULL, NULL, 'password_reset_otp_requested', NULL, 0, 'password_reset_request_otp', 200, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://safenode.cloud/forgot-password', '2025-12-13 21:55:20');

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_security_logs_archive`
--

CREATE TABLE `safenode_security_logs_archive` (
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
  `response_time` decimal(10,2) DEFAULT NULL,
  `cloudflare_ray` varchar(100) DEFAULT NULL,
  `cloudflare_country` varchar(2) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `site_id` int(11) DEFAULT NULL,
  `country_code` char(2) DEFAULT NULL,
  `referer` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_security_maturity`
--

CREATE TABLE `safenode_security_maturity` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `overall_score` int(11) NOT NULL COMMENT 'Score geral de 0 a 100',
  `headers_score` int(11) DEFAULT 0,
  `waf_score` int(11) DEFAULT 0,
  `rate_limit_score` int(11) DEFAULT 0,
  `endpoint_protection_score` int(11) DEFAULT 0,
  `monitoring_score` int(11) DEFAULT 0,
  `maturity_level` enum('basic','intermediate','advanced','expert') DEFAULT 'basic',
  `total_recommendations` int(11) DEFAULT 0,
  `applied_recommendations` int(11) DEFAULT 0,
  `measured_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `safenode_security_maturity`
--

INSERT INTO `safenode_security_maturity` (`id`, `site_id`, `overall_score`, `headers_score`, `waf_score`, `rate_limit_score`, `endpoint_protection_score`, `monitoring_score`, `maturity_level`, `total_recommendations`, `applied_recommendations`, `measured_at`, `created_at`) VALUES
(1, 5, 50, 0, 100, 100, 0, 0, 'intermediate', 0, 0, '2025-12-17 17:38:37', '2025-12-17 17:38:37');

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_security_recommendations`
--

CREATE TABLE `safenode_security_recommendations` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `recommendation_type` varchar(50) NOT NULL COMMENT 'header, waf_rule, rate_limit, endpoint_protection, etc',
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `impact` varchar(100) DEFAULT NULL COMMENT 'Impacto esperado da implementação',
  `effort` enum('low','medium','high') DEFAULT 'medium' COMMENT 'Esforço para implementar',
  `status` enum('pending','in_progress','applied','dismissed') DEFAULT 'pending',
  `auto_applied` tinyint(1) DEFAULT 0,
  `applied_at` timestamp NULL DEFAULT NULL,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  `dismissed_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `safenode_security_recommendations`
--

INSERT INTO `safenode_security_recommendations` (`id`, `site_id`, `recommendation_type`, `title`, `description`, `priority`, `impact`, `effort`, `status`, `auto_applied`, `applied_at`, `dismissed_at`, `dismissed_reason`, `created_at`, `updated_at`) VALUES
(1, 5, 'headers', 'X-Frame-Options', 'Header de segurança X-Frame-Options não configurado\n\nConfigure o header X-Frame-Options: DENY', 'critical', 'Melhoria de segurança', 'medium', 'pending', 0, NULL, NULL, NULL, '2025-12-17 17:38:37', '2025-12-17 17:38:37'),
(2, 5, 'headers', 'X-Content-Type-Options', 'Header de segurança X-Content-Type-Options não configurado\n\nConfigure o header X-Content-Type-Options: nosniff', 'critical', 'Melhoria de segurança', 'medium', 'pending', 0, NULL, NULL, NULL, '2025-12-17 17:38:37', '2025-12-17 17:38:37'),
(3, 5, 'headers', 'Strict-Transport-Security', 'Header de segurança Strict-Transport-Security não configurado\n\nConfigure o header Strict-Transport-Security: max-age=31536000', 'critical', 'Melhoria de segurança', 'medium', 'pending', 0, NULL, NULL, NULL, '2025-12-17 17:38:37', '2025-12-17 17:38:37'),
(4, 5, 'headers', 'X-XSS-Protection', 'Header de segurança X-XSS-Protection não configurado\n\nConfigure o header X-XSS-Protection: 1; mode=block', 'medium', 'Melhoria de segurança', 'medium', 'pending', 0, NULL, NULL, NULL, '2025-12-17 17:38:37', '2025-12-17 17:38:37'),
(5, 5, 'headers', 'Content-Security-Policy', 'Header de segurança Content-Security-Policy não configurado\n\nConfigure o header Content-Security-Policy: default-src \'self\'', 'medium', 'Melhoria de segurança', 'medium', 'pending', 0, NULL, NULL, NULL, '2025-12-17 17:38:37', '2025-12-17 17:38:37'),
(6, 5, 'headers', 'Referrer-Policy', 'Header de segurança Referrer-Policy não configurado\n\nConfigure o header Referrer-Policy: strict-origin-when-cross-origin', 'medium', 'Melhoria de segurança', 'medium', 'pending', 0, NULL, NULL, NULL, '2025-12-17 17:38:37', '2025-12-17 17:38:37'),
(7, 5, 'headers', 'Permissions-Policy', 'Header de segurança Permissions-Policy não configurado\n\nConfigure o header Permissions-Policy: geolocation=(), microphone=()', 'medium', 'Melhoria de segurança', 'medium', 'pending', 0, NULL, NULL, NULL, '2025-12-17 17:38:37', '2025-12-17 17:38:37'),
(8, 5, 'endpoints', 'Proteção: /login', 'Endpoint sensível /login não possui regras de proteção\n\nConfigure regras de segurança para o endpoint /login', 'medium', 'Melhoria de segurança', 'low', 'pending', 0, NULL, NULL, NULL, '2025-12-17 17:38:37', '2025-12-17 17:38:37'),
(9, 5, 'endpoints', 'Proteção: /admin', 'Endpoint sensível /admin não possui regras de proteção\n\nConfigure regras de segurança para o endpoint /admin', 'medium', 'Melhoria de segurança', 'low', 'pending', 0, NULL, NULL, NULL, '2025-12-17 17:38:37', '2025-12-17 17:38:37'),
(10, 5, 'endpoints', 'Proteção: /api', 'Endpoint sensível /api não possui regras de proteção\n\nConfigure regras de segurança para o endpoint /api', 'medium', 'Melhoria de segurança', 'low', 'pending', 0, NULL, NULL, NULL, '2025-12-17 17:38:37', '2025-12-17 17:38:37'),
(11, 5, 'endpoints', 'Proteção: /checkout', 'Endpoint sensível /checkout não possui regras de proteção\n\nConfigure regras de segurança para o endpoint /checkout', 'medium', 'Melhoria de segurança', 'low', 'pending', 0, NULL, NULL, NULL, '2025-12-17 17:38:37', '2025-12-17 17:38:37'),
(12, 5, 'endpoints', 'Proteção: /payment', 'Endpoint sensível /payment não possui regras de proteção\n\nConfigure regras de segurança para o endpoint /payment', 'medium', 'Melhoria de segurança', 'low', 'pending', 0, NULL, NULL, NULL, '2025-12-17 17:38:37', '2025-12-17 17:38:37'),
(13, 5, 'endpoints', 'Proteção: /reset-password', 'Endpoint sensível /reset-password não possui regras de proteção\n\nConfigure regras de segurança para o endpoint /reset-password', 'medium', 'Melhoria de segurança', 'low', 'pending', 0, NULL, NULL, NULL, '2025-12-17 17:38:37', '2025-12-17 17:38:37'),
(14, 5, 'config', 'Nível de Segurança', 'Configuração: Nível de Segurança = medium\n\nConfigure Nível de Segurança para: high', 'medium', 'Melhoria de segurança', 'low', 'pending', 0, NULL, NULL, NULL, '2025-12-17 17:38:37', '2025-12-17 17:38:37');

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_security_tests`
--

CREATE TABLE `safenode_security_tests` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `test_type` enum('brute_force','rate_limit','sql_injection','xss','csrf','bot_detection','ddos_simulation','custom') NOT NULL,
  `target_url` varchar(500) NOT NULL,
  `status` enum('pending','authorized','running','completed','failed','cancelled') DEFAULT 'pending',
  `authorization_token` varchar(64) NOT NULL COMMENT 'Token único para autorização',
  `authorization_ip` varchar(45) DEFAULT NULL COMMENT 'IP que autorizou o teste',
  `authorization_accepted_at` timestamp NULL DEFAULT NULL,
  `terms_accepted` tinyint(1) DEFAULT 0,
  `domain_verified` tinyint(1) DEFAULT 0,
  `domain_verification_method` varchar(50) DEFAULT NULL COMMENT 'dns, file, meta_tag',
  `domain_verification_token` varchar(64) DEFAULT NULL,
  `domain_verified_at` timestamp NULL DEFAULT NULL,
  `test_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Configuração do teste em JSON',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `total_requests` int(11) DEFAULT 0,
  `blocked_requests` int(11) DEFAULT 0,
  `allowed_requests` int(11) DEFAULT 0,
  `false_positives` int(11) DEFAULT 0,
  `false_negatives` int(11) DEFAULT 0,
  `test_score` int(11) DEFAULT 0 COMMENT 'Score de 0 a 100',
  `report_generated` tinyint(1) DEFAULT 0,
  `report_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(5, 10, 'lactechsys.com', 'Lactech', '2e1eb9127f2d34761d4626b5e71aaaab', 'active', 'pending', 'medium', 1, 1, 1, 1, NULL, 'df113b7e1414049d0330bd55a367a6838ae7b56e0d0c8ae3948416889d7b3be3', 'pending', 0, '2025-11-25 18:14:06', '2025-12-03 04:32:59');

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
-- Estrutura para tabela `safenode_structured_logs`
--

CREATE TABLE `safenode_structured_logs` (
  `id` int(11) NOT NULL,
  `level` varchar(20) NOT NULL,
  `message` varchar(500) DEFAULT NULL,
  `log_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`log_data`)),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_system_logs`
--

CREATE TABLE `safenode_system_logs` (
  `id` int(11) NOT NULL,
  `level` enum('info','warning','error','critical','debug') DEFAULT 'error',
  `message` text NOT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `file` varchar(500) DEFAULT NULL,
  `line` int(11) DEFAULT NULL,
  `function` varchar(255) DEFAULT NULL,
  `trace` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `request_uri` varchar(500) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_test_results`
--

CREATE TABLE `safenode_test_results` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `request_number` int(11) NOT NULL,
  `request_type` varchar(50) NOT NULL COMMENT 'attack, normal, probe, etc',
  `request_url` varchar(500) NOT NULL,
  `request_method` varchar(10) NOT NULL,
  `request_payload` text DEFAULT NULL,
  `response_code` int(11) DEFAULT NULL,
  `response_time_ms` decimal(10,2) DEFAULT NULL,
  `was_blocked` tinyint(1) DEFAULT 0,
  `block_reason` varchar(255) DEFAULT NULL,
  `threat_detected` tinyint(1) DEFAULT 0,
  `threat_type` varchar(50) DEFAULT NULL,
  `threat_score` int(11) DEFAULT 0,
  `expected_result` enum('block','allow') DEFAULT NULL,
  `actual_result` enum('blocked','allowed') DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL COMMENT 'NULL = não aplicável, 1 = correto, 0 = incorreto',
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Detalhes em JSON',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_test_templates`
--

CREATE TABLE `safenode_test_templates` (
  `id` int(11) NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `test_type` enum('brute_force','rate_limit','sql_injection','xss','csrf','bot_detection','ddos_simulation','custom') NOT NULL,
  `description` text DEFAULT NULL,
  `test_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Configuração padrão em JSON',
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `safenode_test_templates`
--

INSERT INTO `safenode_test_templates` (`id`, `template_name`, `test_type`, `description`, `test_config`, `is_default`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Brute Force Básico', 'brute_force', 'Teste de força bruta em endpoint de login', '{\"max_attempts\": 10, \"delay_ms\": 100, \"payloads\": [\"admin\", \"password\", \"123456\"]}', 1, 1, '2025-12-13 19:34:59', '2025-12-13 19:34:59'),
(2, 'Rate Limit Test', 'rate_limit', 'Teste de limite de taxa de requisições', '{\"requests_per_second\": 100, \"duration_seconds\": 60, \"endpoint\": \"/api\"}', 1, 1, '2025-12-13 19:34:59', '2025-12-13 19:34:59'),
(3, 'SQL Injection Básico', 'sql_injection', 'Teste de injeção SQL comum', '{\"payloads\": [\"1=1\", \"UNION SELECT\", \"DROP TABLE\", \"OR 1=1\"]}', 1, 1, '2025-12-13 19:34:59', '2025-12-13 19:34:59'),
(4, 'XSS Básico', 'xss', 'Teste de Cross-Site Scripting', '{\"payloads\": [\"<script>alert(1)</script>\", \"<img src=x onerror=alert(1)>\"]}', 1, 1, '2025-12-13 19:34:59', '2025-12-13 19:34:59'),
(5, 'Bot Detection', 'bot_detection', 'Teste de detecção de bots', '{\"user_agents\": [\"bot\", \"crawler\", \"spider\"], \"no_js\": true}', 1, 1, '2025-12-13 19:34:59', '2025-12-13 19:34:59');

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_threat_correlations`
--

CREATE TABLE `safenode_threat_correlations` (
  `id` int(11) NOT NULL,
  `threat_intelligence_id` int(11) NOT NULL,
  `site_id_hash` varchar(64) NOT NULL COMMENT 'Hash do site_id para anonimização',
  `occurrence_count` int(11) DEFAULT 1,
  `first_seen` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_threat_intelligence`
--

CREATE TABLE `safenode_threat_intelligence` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `threat_type` varchar(50) NOT NULL,
  `severity` int(11) DEFAULT 50 COMMENT 'Severidade de 0 a 100',
  `attack_pattern` text DEFAULT NULL COMMENT 'Padrão de ataque identificado (JSON)',
  `first_seen` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_occurrences` int(11) DEFAULT 1 COMMENT 'Total de ocorrências em todos os clientes',
  `affected_sites_count` int(11) DEFAULT 1 COMMENT 'Número de sites diferentes afetados',
  `confidence_score` int(11) DEFAULT 50 COMMENT 'Confiança na detecção (0-100)',
  `is_verified` tinyint(1) DEFAULT 0 COMMENT 'Ameaça verificada e confirmada',
  `is_global_block` tinyint(1) DEFAULT 0 COMMENT 'Bloqueio global ativo',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dados adicionais em JSON',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_threat_intelligence_external`
--

CREATE TABLE `safenode_threat_intelligence_external` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `is_malicious` tinyint(1) DEFAULT 0,
  `confidence` int(11) DEFAULT 0,
  `reputation_score` int(11) DEFAULT 50,
  `sources_data` text DEFAULT NULL,
  `available_sources` text DEFAULT NULL,
  `combined_confidence` decimal(5,2) DEFAULT NULL,
  `last_checked` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(1, 'admin', 'admin@safenode.cloud', '$2y$10$ya9uwD0EkE0WhYZu0EhKm.PrRsa/46dt4bGsJtNeHdN04peKAPL0K', 'Administrador SafeNode', 'admin', 1, 1, '2025-11-20 01:48:07', NULL, NULL, NULL, '2025-12-13 19:14:50', '2025-11-20 01:48:07', '2025-12-13 19:14:50'),
(4, 'lucenadev', 'joselucenadev@gmail.com', '$2y$10$AO6cXWR4Fo1HqSkxW4KY5.lVuEEXh.FT/hyyzSmgYzP.acKvwkDKW', 'José Kleiton Sinesio de Lucena Alves', 'user', 1, 1, '2025-11-21 14:31:58', NULL, NULL, NULL, '2025-11-21 14:32:24', '2025-11-21 14:31:28', '2025-11-21 14:32:24'),
(10, 'slavosier298', 'slavosier298@gmail.com', '$2y$10$YwzsJuCguE9CD.F6mxZJfODE4K5AxH9xMsM/e0lLFPyUgXcSzMNwe', 'Lavosier Silva', 'user', 1, 1, '2025-11-22 21:55:58', '115943975533213801187', 'https://lh3.googleusercontent.com/a/ACg8ocK1ZMZoALtFmICEetKlPquvc4M-VnzCI0NgHCxq-k3xUkFhIVLfWw=s96-c', '2025-12-19 22:43:02', '2025-12-19 22:43:02', '2025-11-22 21:55:58', '2025-12-19 22:43:02'),
(17, 'josue', 'lavosiersilva02@gmail.com', '$2y$10$hnRK.W9hC0zgQtNKYhz6NeUnGNjT/XIWVe3PsbGZ3jQEfFF9V66/e', NULL, 'user', 1, 1, '2025-12-14 00:18:06', NULL, NULL, NULL, NULL, '2025-12-14 00:18:06', '2025-12-14 00:18:06');

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
-- Estrutura para tabela `safenode_vulnerability_scans`
--

CREATE TABLE `safenode_vulnerability_scans` (
  `id` int(11) NOT NULL,
  `site_id` int(11) DEFAULT NULL,
  `scan_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`scan_data`)),
  `overall_score` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_waf_rules`
--

CREATE TABLE `safenode_waf_rules` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `action` enum('block','allow','challenge','log','redirect') DEFAULT 'block',
  `severity` int(11) DEFAULT 50,
  `operator` enum('AND','OR') DEFAULT 'AND',
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `priority` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `message` varchar(500) DEFAULT NULL,
  `redirect_url` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
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

CREATE ALGORITHM=UNDEFINED DEFINER=`u311882628_Kron`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_safenode_active_blocks`  AS SELECT `safenode_blocked_ips`.`ip_address` AS `ip_address`, `safenode_blocked_ips`.`reason` AS `reason`, `safenode_blocked_ips`.`threat_type` AS `threat_type`, `safenode_blocked_ips`.`created_at` AS `blocked_at`, `safenode_blocked_ips`.`expires_at` AS `expires_at`, timestampdiff(SECOND,current_timestamp(),`safenode_blocked_ips`.`expires_at`) AS `seconds_remaining` FROM `safenode_blocked_ips` WHERE `safenode_blocked_ips`.`is_active` = 1 AND (`safenode_blocked_ips`.`expires_at` is null OR `safenode_blocked_ips`.`expires_at` > current_timestamp()) ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_safenode_today_stats`
--
DROP TABLE IF EXISTS `v_safenode_today_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u311882628_Kron`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_safenode_today_stats`  AS SELECT count(0) AS `total_requests`, sum(case when `safenode_security_logs`.`action_taken` = 'blocked' then 1 else 0 end) AS `blocked_requests`, sum(case when `safenode_security_logs`.`action_taken` = 'allowed' then 1 else 0 end) AS `allowed_requests`, count(distinct `safenode_security_logs`.`ip_address`) AS `unique_ips`, sum(case when `safenode_security_logs`.`threat_type` = 'sql_injection' then 1 else 0 end) AS `sql_injection_count`, sum(case when `safenode_security_logs`.`threat_type` = 'xss' then 1 else 0 end) AS `xss_count`, sum(case when `safenode_security_logs`.`threat_type` = 'brute_force' then 1 else 0 end) AS `brute_force_count`, sum(case when `safenode_security_logs`.`threat_type` = 'rate_limit' then 1 else 0 end) AS `rate_limit_count`, sum(case when `safenode_security_logs`.`threat_type` = 'path_traversal' then 1 else 0 end) AS `path_traversal_count`, sum(case when `safenode_security_logs`.`threat_type` = 'command_injection' then 1 else 0 end) AS `command_injection_count`, sum(case when `safenode_security_logs`.`threat_type` = 'ddos' then 1 else 0 end) AS `ddos_count` FROM `safenode_security_logs` WHERE cast(`safenode_security_logs`.`created_at` as date) = curdate() ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_safenode_top_blocked_ips`
--
DROP TABLE IF EXISTS `v_safenode_top_blocked_ips`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u311882628_Kron`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_safenode_top_blocked_ips`  AS SELECT `safenode_security_logs`.`ip_address` AS `ip_address`, count(0) AS `block_count`, max(`safenode_security_logs`.`created_at`) AS `last_blocked`, substring_index(group_concat(distinct `safenode_security_logs`.`threat_type` order by `safenode_security_logs`.`threat_type` ASC separator ','),',',10) AS `threat_types` FROM `safenode_security_logs` WHERE `safenode_security_logs`.`action_taken` = 'blocked' AND `safenode_security_logs`.`created_at` >= current_timestamp() - interval 7 day GROUP BY `safenode_security_logs`.`ip_address` ORDER BY count(0) DESC LIMIT 0, 100 ;

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
-- Índices de tabela `safenode_alert_configs`
--
ALTER TABLE `safenode_alert_configs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_site` (`site_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Índices de tabela `safenode_attack_patterns`
--
ALTER TABLE `safenode_attack_patterns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pattern` (`pattern_signature`(255),`threat_type`),
  ADD KEY `idx_threat_type` (`threat_type`),
  ADD KEY `idx_active` (`is_active`);

--
-- Índices de tabela `safenode_audit_results`
--
ALTER TABLE `safenode_audit_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit` (`audit_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_severity` (`severity`);

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
-- Índices de tabela `safenode_endpoint_anomalies`
--
ALTER TABLE `safenode_endpoint_anomalies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_site_endpoint` (`site_id`,`endpoint_pattern`(100)),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_anomaly_type` (`anomaly_type`),
  ADD KEY `idx_detected` (`detected_at`),
  ADD KEY `idx_resolved` (`is_resolved`);

--
-- Índices de tabela `safenode_endpoint_rules`
--
ALTER TABLE `safenode_endpoint_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_site` (`site_id`),
  ADD KEY `idx_pattern` (`endpoint_pattern`(100)),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_endpoint_rules_lookup` (`site_id`,`is_active`,`priority`);

--
-- Índices de tabela `safenode_endpoint_stats`
--
ALTER TABLE `safenode_endpoint_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_site_endpoint_hour` (`site_id`,`endpoint_pattern`(100),`stat_date`,`stat_hour`),
  ADD KEY `idx_site` (`site_id`),
  ADD KEY `idx_date` (`stat_date`,`stat_hour`);

--
-- Índices de tabela `safenode_fingerprints`
--
ALTER TABLE `safenode_fingerprints`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ip_hash` (`ip_address`,`fingerprint_hash`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_hash` (`fingerprint_hash`),
  ADD KEY `idx_bot` (`is_bot`,`suspicion_score`);

--
-- Índices de tabela `safenode_firewall_rules`
--
ALTER TABLE `safenode_firewall_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_site_priority` (`site_id`,`priority`),
  ADD KEY `idx_firewall_site_active` (`site_id`,`is_active`,`priority`);

--
-- Índices de tabela `safenode_honeypots`
--
ALTER TABLE `safenode_honeypots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `honeypot_id` (`honeypot_id`),
  ADD KEY `idx_id` (`honeypot_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Índices de tabela `safenode_honeypot_access`
--
ALTER TABLE `safenode_honeypot_access`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_honeypot` (`honeypot_id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_created` (`created_at`);

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
  ADD KEY `idx_inc_last_seen` (`last_seen`),
  ADD KEY `idx_incidents_site_status` (`site_id`,`status`,`last_seen`);

--
-- Índices de tabela `safenode_ip_reputation`
--
ALTER TABLE `safenode_ip_reputation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ip` (`ip_address`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_trust_score` (`trust_score`),
  ADD KEY `idx_last_seen` (`last_seen`),
  ADD KEY `idx_country` (`country_code`),
  ADD KEY `idx_reputation_trust_score` (`trust_score`,`last_seen`),
  ADD KEY `idx_reputation_low_trust` (`trust_score`,`is_blacklisted`,`last_seen`);

--
-- Índices de tabela `safenode_mail_analytics`
--
ALTER TABLE `safenode_mail_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_date` (`project_id`,`date`),
  ADD KEY `date` (`date`);

--
-- Índices de tabela `safenode_mail_logs`
--
ALTER TABLE `safenode_mail_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `to_email` (`to_email`);

--
-- Índices de tabela `safenode_mail_projects`
--
ALTER TABLE `safenode_mail_projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_active` (`is_active`);

--
-- Índices de tabela `safenode_mail_rate_limits`
--
ALTER TABLE `safenode_mail_rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_minute` (`project_id`,`minute_window`),
  ADD KEY `minute_window` (`minute_window`);

--
-- Índices de tabela `safenode_mail_templates`
--
ALTER TABLE `safenode_mail_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `name` (`name`);

--
-- Índices de tabela `safenode_mail_webhooks`
--
ALTER TABLE `safenode_mail_webhooks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `is_active` (`is_active`);

--
-- Índices de tabela `safenode_ml_model`
--
ALTER TABLE `safenode_ml_model`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_version` (`model_version`),
  ADD KEY `idx_trained_at` (`trained_at`);

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
-- Índices de tabela `safenode_protection_streaks`
--
ALTER TABLE `safenode_protection_streaks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_site` (`user_id`,`site_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_site_id` (`site_id`),
  ADD KEY `idx_last_protected` (`last_protected_date`);

--
-- Índices de tabela `safenode_quarantine`
--
ALTER TABLE `safenode_quarantine`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Índices de tabela `safenode_quarantine_activity`
--
ALTER TABLE `safenode_quarantine_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quarantine` (`quarantine_id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_created` (`created_at`);

--
-- Índices de tabela `safenode_rate_limits`
--
ALTER TABLE `safenode_rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_identifier` (`identifier`),
  ADD KEY `idx_endpoint` (`endpoint`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_rate_limits_active` (`is_active`,`priority`);

--
-- Índices de tabela `safenode_rate_limits_violations`
--
ALTER TABLE `safenode_rate_limits_violations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_rate_limit` (`rate_limit_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_violations_ip_created` (`ip_address`,`created_at`);

--
-- Índices de tabela `safenode_security_audits`
--
ALTER TABLE `safenode_security_audits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_site` (`site_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_audit_site_status` (`site_id`,`status`,`created_at`);

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
  ADD KEY `idx_logs_threat_date` (`threat_type`,`created_at`),
  ADD KEY `idx_ip_created` (`ip_address`,`created_at`),
  ADD KEY `idx_site_created` (`site_id`,`created_at`),
  ADD KEY `idx_action_created` (`action_taken`,`created_at`),
  ADD KEY `idx_threat_created` (`threat_type`,`created_at`,`threat_score`),
  ADD KEY `idx_ip_site_created` (`ip_address`,`site_id`,`created_at`),
  ADD KEY `idx_country_created` (`country_code`,`created_at`),
  ADD KEY `idx_threat_score_created` (`threat_score`,`created_at`),
  ADD KEY `idx_request_uri_prefix` (`request_uri`(200));

--
-- Índices de tabela `safenode_security_logs_archive`
--
ALTER TABLE `safenode_security_logs_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_archive_ip_created` (`ip_address`,`created_at`),
  ADD KEY `idx_archive_site_created` (`site_id`,`created_at`),
  ADD KEY `idx_archive_created` (`created_at`),
  ADD KEY `idx_archive_date_month` (`created_at`),
  ADD KEY `idx_archive_threat_type` (`threat_type`,`created_at`);

--
-- Índices de tabela `safenode_security_maturity`
--
ALTER TABLE `safenode_security_maturity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_site` (`site_id`),
  ADD KEY `idx_measured` (`measured_at`);

--
-- Índices de tabela `safenode_security_recommendations`
--
ALTER TABLE `safenode_security_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_site` (`site_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_type` (`recommendation_type`);

--
-- Índices de tabela `safenode_security_tests`
--
ALTER TABLE `safenode_security_tests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_auth_token` (`authorization_token`),
  ADD KEY `idx_site` (`site_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`test_type`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_test_site_status` (`site_id`,`status`,`created_at`);

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
-- Índices de tabela `safenode_statistics`
--
ALTER TABLE `safenode_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date_hour` (`stat_date`,`stat_hour`),
  ADD KEY `idx_date` (`stat_date`);

--
-- Índices de tabela `safenode_structured_logs`
--
ALTER TABLE `safenode_structured_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_level` (`level`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_message` (`message`(100));

--
-- Índices de tabela `safenode_system_logs`
--
ALTER TABLE `safenode_system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_level` (`level`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_message` (`message`(100)),
  ADD KEY `idx_file` (`file`(100));

--
-- Índices de tabela `safenode_test_results`
--
ALTER TABLE `safenode_test_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_test` (`test_id`),
  ADD KEY `idx_request_number` (`request_number`),
  ADD KEY `idx_was_blocked` (`was_blocked`),
  ADD KEY `idx_is_correct` (`is_correct`);

--
-- Índices de tabela `safenode_test_templates`
--
ALTER TABLE `safenode_test_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`test_type`),
  ADD KEY `idx_active` (`is_active`);

--
-- Índices de tabela `safenode_threat_correlations`
--
ALTER TABLE `safenode_threat_correlations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_threat_intel` (`threat_intelligence_id`),
  ADD KEY `idx_site_hash` (`site_id_hash`);

--
-- Índices de tabela `safenode_threat_intelligence`
--
ALTER TABLE `safenode_threat_intelligence`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ip_threat` (`ip_address`,`threat_type`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_threat_type` (`threat_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_last_seen` (`last_seen`),
  ADD KEY `idx_global_block` (`is_global_block`),
  ADD KEY `idx_threat_intel_lookup` (`ip_address`,`is_global_block`,`severity`);

--
-- Índices de tabela `safenode_threat_intelligence_external`
--
ALTER TABLE `safenode_threat_intelligence_external`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_malicious` (`is_malicious`,`reputation_score`),
  ADD KEY `idx_checked` (`last_checked`),
  ADD KEY `idx_confidence` (`confidence`);

--
-- Índices de tabela `safenode_threat_patterns`
--
ALTER TABLE `safenode_threat_patterns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`threat_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_threat_patterns_active` (`is_active`,`threat_type`);

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
-- Índices de tabela `safenode_vulnerability_scans`
--
ALTER TABLE `safenode_vulnerability_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_score` (`overall_score`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_site` (`site_id`);

--
-- Índices de tabela `safenode_waf_rules`
--
ALTER TABLE `safenode_waf_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`,`priority`);

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
-- AUTO_INCREMENT de tabela `safenode_alert_configs`
--
ALTER TABLE `safenode_alert_configs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_attack_patterns`
--
ALTER TABLE `safenode_attack_patterns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_audit_results`
--
ALTER TABLE `safenode_audit_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `safenode_blocked_ips`
--
ALTER TABLE `safenode_blocked_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_endpoint_anomalies`
--
ALTER TABLE `safenode_endpoint_anomalies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_endpoint_rules`
--
ALTER TABLE `safenode_endpoint_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_endpoint_stats`
--
ALTER TABLE `safenode_endpoint_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_fingerprints`
--
ALTER TABLE `safenode_fingerprints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_firewall_rules`
--
ALTER TABLE `safenode_firewall_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_honeypots`
--
ALTER TABLE `safenode_honeypots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_honeypot_access`
--
ALTER TABLE `safenode_honeypot_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_hv_api_keys`
--
ALTER TABLE `safenode_hv_api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de tabela `safenode_hv_attempts`
--
ALTER TABLE `safenode_hv_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1789;

--
-- AUTO_INCREMENT de tabela `safenode_hv_rate_limits`
--
ALTER TABLE `safenode_hv_rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1772;

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
-- AUTO_INCREMENT de tabela `safenode_mail_analytics`
--
ALTER TABLE `safenode_mail_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_mail_logs`
--
ALTER TABLE `safenode_mail_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `safenode_mail_projects`
--
ALTER TABLE `safenode_mail_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `safenode_mail_rate_limits`
--
ALTER TABLE `safenode_mail_rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_mail_templates`
--
ALTER TABLE `safenode_mail_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `safenode_mail_webhooks`
--
ALTER TABLE `safenode_mail_webhooks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_ml_model`
--
ALTER TABLE `safenode_ml_model`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_otp_codes`
--
ALTER TABLE `safenode_otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `safenode_password_reset_otp`
--
ALTER TABLE `safenode_password_reset_otp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `safenode_protection_streaks`
--
ALTER TABLE `safenode_protection_streaks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `safenode_quarantine`
--
ALTER TABLE `safenode_quarantine`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_quarantine_activity`
--
ALTER TABLE `safenode_quarantine_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT de tabela `safenode_security_audits`
--
ALTER TABLE `safenode_security_audits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `safenode_security_logs`
--
ALTER TABLE `safenode_security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `safenode_security_logs_archive`
--
ALTER TABLE `safenode_security_logs_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_security_maturity`
--
ALTER TABLE `safenode_security_maturity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `safenode_security_recommendations`
--
ALTER TABLE `safenode_security_recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `safenode_security_tests`
--
ALTER TABLE `safenode_security_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `safenode_settings`
--
ALTER TABLE `safenode_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

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
-- AUTO_INCREMENT de tabela `safenode_structured_logs`
--
ALTER TABLE `safenode_structured_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_system_logs`
--
ALTER TABLE `safenode_system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_test_results`
--
ALTER TABLE `safenode_test_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_test_templates`
--
ALTER TABLE `safenode_test_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `safenode_threat_correlations`
--
ALTER TABLE `safenode_threat_correlations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_threat_intelligence`
--
ALTER TABLE `safenode_threat_intelligence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_threat_intelligence_external`
--
ALTER TABLE `safenode_threat_intelligence_external`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
-- AUTO_INCREMENT de tabela `safenode_vulnerability_scans`
--
ALTER TABLE `safenode_vulnerability_scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `safenode_waf_rules`
--
ALTER TABLE `safenode_waf_rules`
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
-- Restrições para tabelas `safenode_audit_results`
--
ALTER TABLE `safenode_audit_results`
  ADD CONSTRAINT `fk_result_audit` FOREIGN KEY (`audit_id`) REFERENCES `safenode_security_audits` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_endpoint_anomalies`
--
ALTER TABLE `safenode_endpoint_anomalies`
  ADD CONSTRAINT `fk_anomaly_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_endpoint_rules`
--
ALTER TABLE `safenode_endpoint_rules`
  ADD CONSTRAINT `fk_endpoint_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_endpoint_stats`
--
ALTER TABLE `safenode_endpoint_stats`
  ADD CONSTRAINT `fk_endpoint_stats_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE;

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
-- Restrições para tabelas `safenode_mail_analytics`
--
ALTER TABLE `safenode_mail_analytics`
  ADD CONSTRAINT `safenode_mail_analytics_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `safenode_mail_projects` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_mail_logs`
--
ALTER TABLE `safenode_mail_logs`
  ADD CONSTRAINT `safenode_mail_logs_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `safenode_mail_projects` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_mail_projects`
--
ALTER TABLE `safenode_mail_projects`
  ADD CONSTRAINT `safenode_mail_projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_mail_rate_limits`
--
ALTER TABLE `safenode_mail_rate_limits`
  ADD CONSTRAINT `safenode_mail_rate_limits_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `safenode_mail_projects` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_mail_templates`
--
ALTER TABLE `safenode_mail_templates`
  ADD CONSTRAINT `safenode_mail_templates_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `safenode_mail_projects` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_mail_webhooks`
--
ALTER TABLE `safenode_mail_webhooks`
  ADD CONSTRAINT `safenode_mail_webhooks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `safenode_mail_projects` (`id`) ON DELETE CASCADE;

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
-- Restrições para tabelas `safenode_quarantine_activity`
--
ALTER TABLE `safenode_quarantine_activity`
  ADD CONSTRAINT `safenode_quarantine_activity_ibfk_1` FOREIGN KEY (`quarantine_id`) REFERENCES `safenode_quarantine` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_rate_limits_violations`
--
ALTER TABLE `safenode_rate_limits_violations`
  ADD CONSTRAINT `safenode_rate_limits_violations_ibfk_1` FOREIGN KEY (`rate_limit_id`) REFERENCES `safenode_rate_limits` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `safenode_security_audits`
--
ALTER TABLE `safenode_security_audits`
  ADD CONSTRAINT `fk_audit_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_security_logs`
--
ALTER TABLE `safenode_security_logs`
  ADD CONSTRAINT `safenode_security_logs_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `safenode_security_maturity`
--
ALTER TABLE `safenode_security_maturity`
  ADD CONSTRAINT `fk_maturity_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_security_recommendations`
--
ALTER TABLE `safenode_security_recommendations`
  ADD CONSTRAINT `fk_recommendation_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_security_tests`
--
ALTER TABLE `safenode_security_tests`
  ADD CONSTRAINT `fk_test_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_test_user` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_site_geo_rules`
--
ALTER TABLE `safenode_site_geo_rules`
  ADD CONSTRAINT `safenode_site_geo_rules_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_test_results`
--
ALTER TABLE `safenode_test_results`
  ADD CONSTRAINT `fk_result_test` FOREIGN KEY (`test_id`) REFERENCES `safenode_security_tests` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `safenode_threat_correlations`
--
ALTER TABLE `safenode_threat_correlations`
  ADD CONSTRAINT `fk_corr_threat_intel` FOREIGN KEY (`threat_intelligence_id`) REFERENCES `safenode_threat_intelligence` (`id`) ON DELETE CASCADE;

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

--
-- Restrições para tabelas `safenode_vulnerability_scans`
--
ALTER TABLE `safenode_vulnerability_scans`
  ADD CONSTRAINT `safenode_vulnerability_scans_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
