-- SafeNode - Tabelas Faltantes
-- Este arquivo contém as tabelas que são criadas dinamicamente pelo código
-- mas que não estão no dump SQL principal
-- Execute este SQL no banco de dados para garantir que todas as funcionalidades funcionem
-- Compatível com Hostinger e formato do banco atual

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_protection_streaks`
-- Sequência de proteção (foguinho do TikTok)
--

CREATE TABLE IF NOT EXISTS `safenode_protection_streaks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL DEFAULT 0,
  `current_streak` int(11) NOT NULL DEFAULT 0,
  `longest_streak` int(11) NOT NULL DEFAULT 0,
  `last_protected_date` date NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_site` (`user_id`, `site_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_last_protected` (`last_protected_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_threat_intelligence_external`
-- Dados de Threat Intelligence de fontes externas (AbuseIPDB, VirusTotal)
--

CREATE TABLE IF NOT EXISTS `safenode_threat_intelligence_external` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `is_malicious` tinyint(1) DEFAULT 0,
  `confidence` int(11) DEFAULT 0,
  `reputation_score` int(11) DEFAULT 50,
  `sources_data` text DEFAULT NULL,
  `available_sources` text DEFAULT NULL,
  `combined_confidence` decimal(5,2) DEFAULT NULL,
  `last_checked` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_malicious` (`is_malicious`, `reputation_score`),
  KEY `idx_checked` (`last_checked`),
  KEY `idx_confidence` (`confidence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_vulnerability_scans`
-- Resultados de scans de vulnerabilidade
--

CREATE TABLE IF NOT EXISTS `safenode_vulnerability_scans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT NULL,
  `scan_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dados do scan em formato JSON',
  `overall_score` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_score` (`overall_score`),
  KEY `idx_created` (`created_at`),
  KEY `idx_site` (`site_id`),
  CONSTRAINT `fk_vuln_scan_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_ml_model`
-- Modelo de Machine Learning para scoring
--

CREATE TABLE IF NOT EXISTS `safenode_ml_model` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model_version` varchar(50) DEFAULT '1.0',
  `weights_data` text DEFAULT NULL,
  `accuracy` decimal(5,4) DEFAULT NULL,
  `precision_score` decimal(5,4) DEFAULT NULL,
  `recall_score` decimal(5,4) DEFAULT NULL,
  `f1_score` decimal(5,4) DEFAULT NULL,
  `false_positive_rate` decimal(5,4) DEFAULT NULL,
  `samples_analyzed` int(11) DEFAULT NULL,
  `trained_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_version` (`model_version`),
  KEY `idx_trained_at` (`trained_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_system_logs`
-- Logs do sistema (erros, warnings, etc)
--

CREATE TABLE IF NOT EXISTS `safenode_system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_level` (`level`),
  KEY `idx_created` (`created_at`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_message` (`message`(100)),
  KEY `idx_file` (`file`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_structured_logs`
-- Logs estruturados (JSON)
--

CREATE TABLE IF NOT EXISTS `safenode_structured_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` varchar(20) NOT NULL,
  `message` varchar(500) DEFAULT NULL,
  `log_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dados do log em formato JSON',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_level` (`level`),
  KEY `idx_created` (`created_at`),
  KEY `idx_message` (`message`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_quarantine`
-- Sistema de quarentena de IPs
--

CREATE TABLE IF NOT EXISTS `safenode_quarantine` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `threat_score` int(11) DEFAULT 0,
  `threat_type` varchar(50) DEFAULT NULL,
  `violation_count` int(11) DEFAULT 0,
  `status` enum('active', 'released', 'blocked') DEFAULT 'active',
  `release_reason` varchar(100) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `released_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_status` (`status`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_quarantine_activity`
-- Atividades de IPs em quarentena
--

CREATE TABLE IF NOT EXISTS `safenode_quarantine_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quarantine_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `request_uri` varchar(500) DEFAULT NULL,
  `threat_score` int(11) DEFAULT 0,
  `threat_type` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_quarantine` (`quarantine_id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_quarantine_activity` FOREIGN KEY (`quarantine_id`) REFERENCES `safenode_quarantine` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_fingerprints`
-- Fingerprints de navegadores/dispositivos
--

CREATE TABLE IF NOT EXISTS `safenode_fingerprints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `fingerprint_hash` varchar(64) NOT NULL,
  `fingerprint_data` text DEFAULT NULL,
  `suspicion_score` int(11) DEFAULT 0,
  `is_bot` tinyint(1) DEFAULT 0,
  `site_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_seen` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ip_hash` (`ip_address`, `fingerprint_hash`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_hash` (`fingerprint_hash`),
  KEY `idx_bot` (`is_bot`, `suspicion_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_alert_configs`
-- Configurações de alertas (email, webhook, etc)
--

CREATE TABLE IF NOT EXISTS `safenode_alert_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `site_id` int(11) DEFAULT NULL,
  `channel` enum('email', 'webhook', 'sms', 'telegram') NOT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `webhook_url` varchar(500) DEFAULT NULL,
  `event_types` varchar(500) NOT NULL COMMENT 'Comma-separated list',
  `min_severity` int(11) DEFAULT 3,
  `priority` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_site` (`site_id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_waf_rules`
-- Regras customizadas do WAF
--

CREATE TABLE IF NOT EXISTS `safenode_waf_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `action` enum('block', 'allow', 'challenge', 'log', 'redirect') DEFAULT 'block',
  `severity` int(11) DEFAULT 50,
  `operator` enum('AND', 'OR') DEFAULT 'AND',
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Condições em formato JSON',
  `priority` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `message` varchar(500) DEFAULT NULL,
  `redirect_url` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_active` (`is_active`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_honeypots`
-- Honeypots (armadilhas para bots)
--

CREATE TABLE IF NOT EXISTS `safenode_honeypots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `honeypot_id` varchar(16) NOT NULL,
  `url` varchar(500) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `honeypot_id` (`honeypot_id`),
  KEY `idx_id` (`honeypot_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `safenode_honeypot_access`
-- Acessos a honeypots (tentativas de bots)
--

CREATE TABLE IF NOT EXISTS `safenode_honeypot_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `honeypot_id` varchar(16) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `request_uri` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_honeypot` (`honeypot_id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Restrições (Foreign Keys)
-- Adicionar após criar todas as tabelas
--

--
-- Restrições para tabela `safenode_vulnerability_scans`
--
ALTER TABLE `safenode_vulnerability_scans`
  ADD CONSTRAINT `fk_vuln_scan_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabela `safenode_quarantine_activity`
--
ALTER TABLE `safenode_quarantine_activity`
  ADD CONSTRAINT `fk_quarantine_activity` FOREIGN KEY (`quarantine_id`) REFERENCES `safenode_quarantine` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------

-- FIM DO ARQUIVO
-- Execute este SQL no banco de dados para adicionar todas as tabelas faltantes
-- Compatível com Hostinger e formato do banco atual
