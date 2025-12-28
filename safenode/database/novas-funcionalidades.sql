-- =====================================================
-- SafeNode: Novas Funcionalidades - Estrutura de Banco
-- =====================================================
-- Data: 2025-12-13
-- Descrição: Tabelas para as 4 novas funcionalidades
-- =====================================================

-- =====================================================
-- 1. THREAT INTELLIGENCE PROPRIETÁRIA
-- =====================================================

-- Tabela principal de inteligência de ameaças colaborativa
CREATE TABLE IF NOT EXISTS `safenode_threat_intelligence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ip_threat` (`ip_address`, `threat_type`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_threat_type` (`threat_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_last_seen` (`last_seen`),
  KEY `idx_global_block` (`is_global_block`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de correlação de ataques entre clientes (anonimizado)
CREATE TABLE IF NOT EXISTS `safenode_threat_correlations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `threat_intelligence_id` int(11) NOT NULL,
  `site_id_hash` varchar(64) NOT NULL COMMENT 'Hash do site_id para anonimização',
  `occurrence_count` int(11) DEFAULT 1,
  `first_seen` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_threat_intel` (`threat_intelligence_id`),
  KEY `idx_site_hash` (`site_id_hash`),
  CONSTRAINT `fk_corr_threat_intel` FOREIGN KEY (`threat_intelligence_id`) REFERENCES `safenode_threat_intelligence` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de padrões de ataque identificados
CREATE TABLE IF NOT EXISTS `safenode_attack_patterns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pattern_name` varchar(100) NOT NULL,
  `pattern_signature` text NOT NULL COMMENT 'Assinatura do padrão (regex ou hash)',
  `threat_type` varchar(50) NOT NULL,
  `severity` int(11) DEFAULT 50,
  `description` text DEFAULT NULL,
  `detection_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pattern` (`pattern_signature`(255), `threat_type`),
  KEY `idx_threat_type` (`threat_type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. AUTO-HARDENING E SECURITY ADVISOR
-- =====================================================

-- Tabela de auditorias de segurança
CREATE TABLE IF NOT EXISTS `safenode_security_audits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site` (`site_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_audit_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de resultados de auditoria (checkpoints)
CREATE TABLE IF NOT EXISTS `safenode_audit_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit` (`audit_id`),
  KEY `idx_status` (`status`),
  KEY `idx_severity` (`severity`),
  CONSTRAINT `fk_result_audit` FOREIGN KEY (`audit_id`) REFERENCES `safenode_security_audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de recomendações de segurança
CREATE TABLE IF NOT EXISTS `safenode_security_recommendations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site` (`site_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_type` (`recommendation_type`),
  CONSTRAINT `fk_recommendation_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de histórico de maturidade de segurança
CREATE TABLE IF NOT EXISTS `safenode_security_maturity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site` (`site_id`),
  KEY `idx_measured` (`measured_at`),
  CONSTRAINT `fk_maturity_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. PROTEÇÃO INTELIGENTE POR ENDPOINT E CONTEXTO
-- =====================================================

-- Tabela de regras de segurança por endpoint
CREATE TABLE IF NOT EXISTS `safenode_endpoint_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site` (`site_id`),
  KEY `idx_pattern` (`endpoint_pattern`(100)),
  KEY `idx_active` (`is_active`),
  KEY `idx_priority` (`priority`),
  CONSTRAINT `fk_endpoint_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de comportamento anômalo por endpoint
CREATE TABLE IF NOT EXISTS `safenode_endpoint_anomalies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_endpoint` (`site_id`, `endpoint_pattern`(100)),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_anomaly_type` (`anomaly_type`),
  KEY `idx_detected` (`detected_at`),
  KEY `idx_resolved` (`is_resolved`),
  CONSTRAINT `fk_anomaly_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de estatísticas por endpoint
CREATE TABLE IF NOT EXISTS `safenode_endpoint_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_site_endpoint_hour` (`site_id`, `endpoint_pattern`(100), `stat_date`, `stat_hour`),
  KEY `idx_site` (`site_id`),
  KEY `idx_date` (`stat_date`, `stat_hour`),
  CONSTRAINT `fk_endpoint_stats_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. TESTES DE SEGURANÇA CONTROLADOS
-- =====================================================

-- Tabela de testes de segurança autorizados e controlados
CREATE TABLE IF NOT EXISTS `safenode_security_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_auth_token` (`authorization_token`),
  KEY `idx_site` (`site_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`test_type`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_test_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_test_user` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de resultados detalhados dos testes
CREATE TABLE IF NOT EXISTS `safenode_test_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_test` (`test_id`),
  KEY `idx_request_number` (`request_number`),
  KEY `idx_was_blocked` (`was_blocked`),
  KEY `idx_is_correct` (`is_correct`),
  CONSTRAINT `fk_result_test` FOREIGN KEY (`test_id`) REFERENCES `safenode_security_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de templates de testes
CREATE TABLE IF NOT EXISTS `safenode_test_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(100) NOT NULL,
  `test_type` enum('brute_force','rate_limit','sql_injection','xss','csrf','bot_detection','ddos_simulation','custom') NOT NULL,
  `description` text DEFAULT NULL,
  `test_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Configuração padrão em JSON',
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_type` (`test_type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================

-- Índices compostos para queries frequentes
CREATE INDEX IF NOT EXISTS `idx_threat_intel_lookup` ON `safenode_threat_intelligence` (`ip_address`, `is_global_block`, `severity`);
CREATE INDEX IF NOT EXISTS `idx_audit_site_status` ON `safenode_security_audits` (`site_id`, `status`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_endpoint_rules_lookup` ON `safenode_endpoint_rules` (`site_id`, `is_active`, `priority`);
CREATE INDEX IF NOT EXISTS `idx_test_site_status` ON `safenode_security_tests` (`site_id`, `status`, `created_at`);

-- =====================================================
-- DADOS INICIAIS (Templates de Testes)
-- =====================================================

-- Inserir templates padrão de testes
INSERT INTO `safenode_test_templates` (`template_name`, `test_type`, `description`, `test_config`, `is_default`, `is_active`) VALUES
('Brute Force Básico', 'brute_force', 'Teste de força bruta em endpoint de login', '{"max_attempts": 10, "delay_ms": 100, "payloads": ["admin", "password", "123456"]}', 1, 1),
('Rate Limit Test', 'rate_limit', 'Teste de limite de taxa de requisições', '{"requests_per_second": 100, "duration_seconds": 60, "endpoint": "/api"}', 1, 1),
('SQL Injection Básico', 'sql_injection', 'Teste de injeção SQL comum', '{"payloads": ["1=1", "UNION SELECT", "DROP TABLE", "OR 1=1"]}', 1, 1),
('XSS Básico', 'xss', 'Teste de Cross-Site Scripting', '{"payloads": ["<script>alert(1)<\/script>", "<img src=x onerror=alert(1)>"]}', 1, 1),
('Bot Detection', 'bot_detection', 'Teste de detecção de bots', '{"user_agents": ["bot", "crawler", "spider"], "no_js": true}', 1, 1)
ON DUPLICATE KEY UPDATE `test_config` = VALUES(`test_config`);

