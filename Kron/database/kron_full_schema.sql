-- =====================================================
-- KRON - ESQUEMA COMPLETO DO BANCO (UNIFICADO)
-- Contém todas as tabelas de autenticação, governança, métricas,
-- logs, comandos, auditoria, views, procedures e eventos.
-- =====================================================

-- =====================================================
-- BANCO DE DADOS KRON SERVER
-- Sistema completo de governança e orquestração
-- =====================================================

-- Criar banco de dados se não existir
CREATE DATABASE IF NOT EXISTS `kronserver` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usar banco de dados
USE `kronserver`;

-- =====================================================
-- TABELAS BASE (USUÁRIOS, SESSÕES E CONEXÕES)
-- =====================================================

-- Tabela de usuários KRON
CREATE TABLE IF NOT EXISTS `kron_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NULL COMMENT 'NULL se login apenas via Google',
  `name` VARCHAR(255) NOT NULL,
  `google_id` VARCHAR(255) NULL UNIQUE COMMENT 'ID único do Google OAuth',
  `avatar_url` VARCHAR(500) NULL COMMENT 'URL da foto de perfil do Google',
  `email_verified` TINYINT(1) DEFAULT 0,
  `email_verified_at` TIMESTAMP NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de sessões de usuários
CREATE TABLE IF NOT EXISTS `kron_user_sessions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `session_token` VARCHAR(64) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `device_type` VARCHAR(50) DEFAULT 'unknown' COMMENT 'desktop, mobile, tablet',
  `browser` VARCHAR(100) DEFAULT NULL,
  `os` VARCHAR(100) DEFAULT NULL,
  `is_current` TINYINT(1) DEFAULT 0,
  `last_activity` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_last_activity` (`last_activity`),
  FOREIGN KEY (`user_id`) REFERENCES `kron_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tokens temporários de conexão (QR/Token)
CREATE TABLE IF NOT EXISTS `kron_connection_tokens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `kron_user_id` INT(11) NOT NULL,
  `system_name` ENUM('safenode', 'lactech') NOT NULL,
  `status` ENUM('pending', 'used', 'expired') DEFAULT 'pending',
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `used_at` TIMESTAMP NULL,
  `system_user_id` INT(11) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_user` (`kron_user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expires` (`expires_at`),
  FOREIGN KEY (`kron_user_id`) REFERENCES `kron_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conexões estabelecidas (usuário KRON ↔ sistema)
CREATE TABLE IF NOT EXISTS `kron_user_connections` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kron_user_id` INT(11) NOT NULL,
  `system_name` ENUM('safenode', 'lactech') NOT NULL,
  `system_user_id` INT(11) NOT NULL,
  `system_user_email` VARCHAR(255) NOT NULL,
  `connection_token` VARCHAR(255) NOT NULL COMMENT 'Token permanente (JWT/opaque)',
  `connected_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_sync_at` TIMESTAMP NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_connection` (`kron_user_id`, `system_name`, `system_user_id`),
  KEY `idx_kron_user` (`kron_user_id`),
  KEY `idx_system` (`system_name`, `system_user_id`),
  FOREIGN KEY (`kron_user_id`) REFERENCES `kron_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs de tentativas de conexão
CREATE TABLE IF NOT EXISTS `kron_connection_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(64) NULL,
  `system_name` ENUM('safenode', 'lactech') NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `status` ENUM('success', 'failed', 'expired', 'invalid') NOT NULL,
  `error_message` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notificações unificadas
CREATE TABLE IF NOT EXISTS `kron_notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kron_user_id` INT(11) NOT NULL,
  `system_name` ENUM('safenode', 'lactech', 'kron') DEFAULT 'kron',
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` TIMESTAMP NULL,
  `action_url` VARCHAR(500) NULL,
  `metadata` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`kron_user_id`),
  KEY `idx_read` (`is_read`),
  KEY `idx_created` (`created_at`),
  KEY `idx_user_read` (`kron_user_id`, `is_read`),
  FOREIGN KEY (`kron_user_id`) REFERENCES `kron_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (Opcional) Analytics agregados legados
CREATE TABLE IF NOT EXISTS `kron_analytics` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kron_user_id` INT(11) NULL,
  `system_name` ENUM('safenode', 'lactech', 'all') NOT NULL,
  `metric_date` DATE NOT NULL,
  `metric_type` VARCHAR(50) NOT NULL,
  `metric_value` DECIMAL(15,2) DEFAULT 0,
  `metadata` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_metric` (`kron_user_id`, `system_name`, `metric_date`, `metric_type`),
  KEY `idx_user` (`kron_user_id`),
  KEY `idx_system` (`system_name`),
  KEY `idx_date` (`metric_date`),
  KEY `idx_type` (`metric_type`),
  FOREIGN KEY (`kron_user_id`) REFERENCES `kron_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- GOVERNAÇA (SISTEMAS, SETORES, RBAC E TOKENS DE SISTEMA)
-- =====================================================

-- Sistemas governados
CREATE TABLE IF NOT EXISTS `kron_systems` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'safenode, lactech, etc',
  `display_name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `api_url` VARCHAR(500) NULL,
  `status` ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
  `version` VARCHAR(50) NULL,
  `system_token` VARCHAR(500) NULL,
  `token_expires_at` TIMESTAMP NULL,
  `allowed_ips` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Setores (hierarquia opcional)
CREATE TABLE IF NOT EXISTS `kron_sectors` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `parent_sector_id` INT(11) NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_code` (`code`),
  KEY `idx_parent` (`parent_sector_id`),
  KEY `idx_active` (`is_active`),
  FOREIGN KEY (`parent_sector_id`) REFERENCES `kron_sectors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles
CREATE TABLE IF NOT EXISTS `kron_roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `display_name` VARCHAR(255) NOT NULL,
  `level` INT(11) NOT NULL COMMENT '1=CEO, 2=Gerente Central, ...',
  `description` TEXT NULL,
  `is_system` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`),
  KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissões
CREATE TABLE IF NOT EXISTS `kron_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `display_name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `category` VARCHAR(50) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role ↔ Permission
CREATE TABLE IF NOT EXISTS `kron_role_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_id` INT(11) NOT NULL,
  `permission_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_permission` (`role_id`, `permission_id`),
  KEY `idx_role` (`role_id`),
  KEY `idx_permission` (`permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `kron_roles` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `kron_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User ↔ Role
CREATE TABLE IF NOT EXISTS `kron_user_roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `role_id` INT(11) NOT NULL,
  `assigned_by` INT(11) NULL,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_role` (`user_id`, `role_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_role` (`role_id`),
  FOREIGN KEY (`user_id`) REFERENCES `kron_users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `kron_roles` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `kron_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Acesso usuário ↔ sistema ↔ setor
CREATE TABLE IF NOT EXISTS `kron_user_system_sector` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `system_id` INT(11) NOT NULL,
  `sector_id` INT(11) NULL,
  `granted_by` INT(11) NULL,
  `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_system_sector` (`user_id`, `system_id`, `sector_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_system` (`system_id`),
  KEY `idx_sector` (`sector_id`),
  KEY `idx_active` (`is_active`),
  FOREIGN KEY (`user_id`) REFERENCES `kron_users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`system_id`) REFERENCES `kron_systems` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sector_id`) REFERENCES `kron_sectors` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`granted_by`) REFERENCES `kron_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tokens de sistema (JWT gerenciado pelo KRON)
CREATE TABLE IF NOT EXISTS `kron_system_tokens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `system_id` INT(11) NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `scopes` TEXT NULL,
  `allowed_ips` TEXT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `last_used_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_system` (`system_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_token_hash` (`token_hash`),
  FOREIGN KEY (`system_id`) REFERENCES `kron_systems` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- LOGS, MÉTRICAS E ORQUESTRAÇÃO
-- =====================================================

-- Logs recebidos dos sistemas
CREATE TABLE IF NOT EXISTS `kron_system_logs` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `system_id` INT(11) NOT NULL,
  `level` ENUM('debug', 'info', 'warning', 'error', 'critical') NOT NULL,
  `message` TEXT NOT NULL,
  `context` TEXT NULL,
  `stack_trace` TEXT NULL,
  `received_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_system` (`system_id`),
  KEY `idx_level` (`level`),
  KEY `idx_received` (`received_at`),
  FOREIGN KEY (`system_id`) REFERENCES `kron_systems` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Métricas por sistema (agregadas por dia/hora)
CREATE TABLE IF NOT EXISTS `kron_metrics` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `system_id` INT(11) NOT NULL,
  `metric_type` VARCHAR(100) NOT NULL,
  `metric_value` DECIMAL(20,4) NOT NULL,
  `metric_date` DATE NOT NULL,
  `metric_hour` TINYINT(2) NULL,
  `metadata` TEXT NULL,
  `received_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_system` (`system_id`),
  KEY `idx_type` (`metric_type`),
  KEY `idx_date` (`metric_date`),
  KEY `idx_system_type_date` (`system_id`, `metric_type`, `metric_date`),
  FOREIGN KEY (`system_id`) REFERENCES `kron_systems` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comandos enviados aos sistemas
CREATE TABLE IF NOT EXISTS `kron_commands` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `command_id` VARCHAR(100) NOT NULL UNIQUE,
  `system_id` INT(11) NOT NULL,
  `type` VARCHAR(100) NOT NULL,
  `parameters` TEXT NULL,
  `priority` ENUM('low', 'normal', 'high', 'critical') DEFAULT 'normal',
  `status` ENUM('pending', 'queued', 'executing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
  `created_by` INT(11) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `queued_at` TIMESTAMP NULL,
  `executed_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `error_message` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_command_id` (`command_id`),
  KEY `idx_system` (`system_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created` (`created_at`),
  FOREIGN KEY (`system_id`) REFERENCES `kron_systems` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `kron_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Resultados de comandos
CREATE TABLE IF NOT EXISTS `kron_command_results` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `command_id` INT(11) NOT NULL,
  `status` ENUM('success', 'failed', 'partial') NOT NULL,
  `result_data` TEXT NULL,
  `error` TEXT NULL,
  `execution_time_ms` INT(11) NULL,
  `received_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_command` (`command_id`),
  FOREIGN KEY (`command_id`) REFERENCES `kron_commands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- AUDITORIA, TRIGGERS, VIEWS E ÍNDICES
-- =====================================================

-- Logs de auditoria
CREATE TABLE IF NOT EXISTS `kron_audit_logs` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) NULL,
  `entity_id` INT(11) NULL,
  `old_values` TEXT NULL,
  `new_values` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `metadata` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `kron_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger: atualizar last_sync_at quando reativar conexão
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS `tr_update_connection_sync`
BEFORE UPDATE ON `kron_user_connections`
FOR EACH ROW
BEGIN
    IF NEW.is_active = 1 AND (OLD.is_active = 0 OR OLD.last_sync_at IS NULL) THEN
        SET NEW.last_sync_at = NOW();
    END IF;
END$$
DELIMITER ;

-- Views úteis
CREATE OR REPLACE VIEW `v_kron_users_stats` AS
SELECT 
    u.id,
    u.email,
    u.name,
    u.is_active,
    u.created_at,
    u.last_login,
    COUNT(DISTINCT c.id) as total_connections,
    COUNT(DISTINCT CASE WHEN c.is_active = 1 THEN c.id END) as active_connections,
    COUNT(DISTINCT s.id) as active_sessions
FROM `kron_users` u
LEFT JOIN `kron_user_connections` c ON u.id = c.kron_user_id
LEFT JOIN `kron_user_sessions` s ON u.id = s.user_id AND s.expires_at > NOW()
GROUP BY u.id;

CREATE OR REPLACE VIEW `v_kron_valid_tokens` AS
SELECT 
    t.id,
    t.token,
    t.kron_user_id,
    u.email as kron_user_email,
    u.name as kron_user_name,
    t.system_name,
    t.expires_at,
    TIMESTAMPDIFF(SECOND, NOW(), t.expires_at) as seconds_remaining
FROM `kron_connection_tokens` t
INNER JOIN `kron_users` u ON t.kron_user_id = u.id
WHERE t.status = 'pending'
AND t.expires_at > NOW();

CREATE OR REPLACE VIEW `v_kron_active_connections` AS
SELECT 
    c.id,
    c.kron_user_id,
    u.email as kron_user_email,
    u.name as kron_user_name,
    c.system_name,
    c.system_user_id,
    c.system_user_email,
    c.connected_at,
    c.last_sync_at,
    DATEDIFF(NOW(), c.connected_at) as days_connected
FROM `kron_user_connections` c
INNER JOIN `kron_users` u ON c.kron_user_id = u.id
WHERE c.is_active = 1
ORDER BY c.connected_at DESC;

CREATE OR REPLACE VIEW `v_kron_unread_notifications` AS
SELECT 
    n.id,
    n.kron_user_id,
    n.system_name,
    n.type,
    n.title,
    n.message,
    n.action_url,
    n.created_at,
    TIMESTAMPDIFF(HOUR, n.created_at, NOW()) as hours_ago
FROM `kron_notifications` n
WHERE n.is_read = 0
ORDER BY n.created_at DESC;

CREATE OR REPLACE VIEW `v_kron_analytics_summary` AS
SELECT 
    system_name,
    metric_type,
    DATE(metric_date) as date,
    SUM(metric_value) as total_value,
    AVG(metric_value) as avg_value,
    MAX(metric_value) as max_value,
    MIN(metric_value) as min_value,
    COUNT(*) as record_count
FROM `kron_analytics`
WHERE metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY system_name, metric_type, DATE(metric_date)
ORDER BY metric_date DESC, system_name, metric_type;

-- Índices adicionais
ALTER TABLE `kron_user_connections` 
ADD INDEX `idx_user_system_active` (`kron_user_id`, `system_name`, `is_active`);

ALTER TABLE `kron_connection_tokens` 
ADD INDEX `idx_user_system_status` (`kron_user_id`, `system_name`, `status`);

ALTER TABLE `kron_user_sessions` 
ADD INDEX `idx_user_expires` (`user_id`, `expires_at`);

ALTER TABLE `kron_notifications` 
ADD INDEX `idx_user_created` (`kron_user_id`, `created_at`);

ALTER TABLE `kron_analytics` 
ADD INDEX `idx_system_date_type` (`system_name`, `metric_date`, `metric_type`);

-- =====================================================
-- PROCEDURES E EVENTOS DE LIMPEZA
-- =====================================================

DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS `sp_cleanup_expired_tokens`()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; END;
    START TRANSACTION;
    UPDATE `kron_connection_tokens`
    SET `status` = 'expired'
    WHERE `status` = 'pending' AND `expires_at` < NOW();
    DELETE FROM `kron_connection_tokens`
    WHERE `status` = 'expired' AND `expires_at` < DATE_SUB(NOW(), INTERVAL 7 DAY);
    COMMIT;
    SELECT CONCAT('Limpeza concluída. ', ROW_COUNT(), ' tokens processados.') AS resultado;
END$$

CREATE PROCEDURE IF NOT EXISTS `sp_cleanup_expired_sessions`()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; END;
    START TRANSACTION;
    DELETE FROM `kron_user_sessions` WHERE `expires_at` < NOW();
    COMMIT;
    SELECT CONCAT('Limpeza concluída. ', ROW_COUNT(), ' sessões removidas.') AS resultado;
END$$

CREATE PROCEDURE IF NOT EXISTS `sp_cleanup_old_logs`(IN `days_to_keep` INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; END;
    START TRANSACTION;
    DELETE FROM `kron_connection_logs`
    WHERE `created_at` < DATE_SUB(NOW(), INTERVAL `days_to_keep` DAY);
    COMMIT;
    SELECT CONCAT('Limpeza concluída. ', ROW_COUNT(), ' logs removidos.') AS resultado;
END$$

CREATE PROCEDURE IF NOT EXISTS `sp_get_system_stats`()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM `kron_users`) as total_users,
        (SELECT COUNT(*) FROM `kron_users` WHERE is_active = 1) as active_users,
        (SELECT COUNT(*) FROM `kron_user_connections` WHERE is_active = 1) as active_connections,
        (SELECT COUNT(*) FROM `kron_user_sessions` WHERE expires_at > NOW()) as active_sessions,
        (SELECT COUNT(*) FROM `kron_connection_tokens` WHERE status = 'pending' AND expires_at > NOW()) as pending_tokens,
        (SELECT COUNT(*) FROM `kron_connection_logs` WHERE DATE(created_at) = CURDATE()) as today_logs,
        (SELECT COUNT(*) FROM `kron_notifications` WHERE is_read = 0) as unread_notifications;
END$$

CREATE PROCEDURE IF NOT EXISTS `sp_cleanup_old_notifications`(IN `days_to_keep` INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; END;
    START TRANSACTION;
    DELETE FROM `kron_notifications`
    WHERE `is_read` = 1 AND `read_at` < DATE_SUB(NOW(), INTERVAL `days_to_keep` DAY);
    COMMIT;
    SELECT CONCAT('Limpeza concluída. ', ROW_COUNT(), ' notificações removidas.') AS resultado;
END$$
DELIMITER ;

-- Eventos (necessário event_scheduler = ON)
CREATE EVENT IF NOT EXISTS `ev_cleanup_expired_tokens`
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO CALL `sp_cleanup_expired_tokens`();

CREATE EVENT IF NOT EXISTS `ev_cleanup_expired_sessions`
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO CALL `sp_cleanup_expired_sessions`();

CREATE EVENT IF NOT EXISTS `ev_cleanup_old_logs`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 2 HOUR
DO CALL `sp_cleanup_old_logs`(90);

CREATE EVENT IF NOT EXISTS `ev_cleanup_old_notifications`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 3 HOUR
DO CALL `sp_cleanup_old_notifications`(30);

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Inserir sistemas padrão
INSERT IGNORE INTO `kron_systems` (`name`, `display_name`, `description`, `status`) VALUES
('safenode', 'SafeNode', 'Sistema de segurança e proteção web', 'active'),
('lactech', 'LacTech', 'Sistema de gestão de produção leiteira', 'active');

-- Inserir roles padrão
INSERT IGNORE INTO `kron_roles` (`name`, `display_name`, `level`, `description`, `is_system`) VALUES
('ceo', 'CEO (Super Admin Global)', 1, 'Acesso total ao sistema, pode criar Gerentes Centrais', 1),
('gerente_central', 'Gerente Central', 2, 'Pode criar Gerentes de Setor e gerenciar múltiplos setores', 1),
('gerente_setor', 'Gerente de Setor', 3, 'Gerencia um setor específico dentro de um sistema', 1),
('funcionario', 'Funcionário', 4, 'Acesso básico conforme permissões atribuídas', 1);

-- Inserir permissões padrão
INSERT IGNORE INTO `kron_permissions` (`name`, `display_name`, `description`, `category`) VALUES
('system.create', 'Criar Sistema', 'Criar novos sistemas governados', 'system'),
('system.read', 'Ver Sistema', 'Visualizar informações de sistemas', 'system'),
('system.update', 'Atualizar Sistema', 'Atualizar configurações de sistemas', 'system'),
('system.delete', 'Deletar Sistema', 'Remover sistemas do Kron', 'system'),
('user.create', 'Criar Usuário', 'Criar novos usuários', 'user'),
('user.read', 'Ver Usuário', 'Visualizar informações de usuários', 'user'),
('user.update', 'Atualizar Usuário', 'Atualizar dados de usuários', 'user'),
('user.delete', 'Deletar Usuário', 'Remover usuários', 'user'),
('sector.create', 'Criar Setor', 'Criar novos setores', 'sector'),
('sector.read', 'Ver Setor', 'Visualizar informações de setores', 'sector'),
('sector.update', 'Atualizar Setor', 'Atualizar configurações de setores', 'sector'),
('sector.delete', 'Deletar Setor', 'Remover setores', 'sector'),
('role.create', 'Criar Role', 'Criar novos papéis', 'role'),
('role.read', 'Ver Role', 'Visualizar informações de papéis', 'role'),
('role.update', 'Atualizar Role', 'Atualizar configurações de papéis', 'role'),
('role.delete', 'Deletar Papéis', 'Remover papéis', 'role'),
('command.create', 'Criar Comando', 'Enviar comandos aos sistemas', 'command'),
('command.read', 'Ver Comando', 'Visualizar comandos e resultados', 'command'),
('command.execute', 'Executar Comando', 'Executar comandos nos sistemas', 'command'),
('audit.read', 'Ver Auditoria', 'Visualizar logs de auditoria', 'audit'),
('audit.export', 'Exportar Auditoria', 'Exportar logs de auditoria', 'audit'),
('metrics.read', 'Ver Métricas', 'Visualizar métricas dos sistemas', 'metrics'),
('metrics.export', 'Exportar Métricas', 'Exportar métricas dos sistemas', 'metrics');

-- Atribuir permissões por role (CEO = todas)
INSERT IGNORE INTO `kron_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `kron_roles` r CROSS JOIN `kron_permissions` p WHERE r.name = 'ceo';

-- Gerente Central
INSERT IGNORE INTO `kron_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `kron_roles` r
CROSS JOIN `kron_permissions` p
WHERE r.name = 'gerente_central'
AND p.name IN ('system.read','user.create','user.read','user.update','sector.create','sector.read','sector.update','role.read','command.create','command.read','audit.read','metrics.read');

-- Gerente de Setor
INSERT IGNORE INTO `kron_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `kron_roles` r
CROSS JOIN `kron_permissions` p
WHERE r.name = 'gerente_setor'
AND p.name IN ('system.read','user.read','user.update','sector.read','command.read','audit.read','metrics.read');

-- Funcionário
INSERT IGNORE INTO `kron_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `kron_roles` r
CROSS JOIN `kron_permissions` p
WHERE r.name = 'funcionario'
AND p.name IN ('system.read','sector.read','metrics.read');

-- Usuário administrador padrão (senha: admin123) — altere após o primeiro login!
INSERT IGNORE INTO `kron_users` (`email`, `password`, `name`, `is_active`, `email_verified`, `email_verified_at`) 
VALUES ('admin@kronx.sbs', '$2y$10$98zWMIufXE/lFi5t07.Wc.x0G86AaTsN9mzpMGbhUX0WIqKVtv/qi', 'Administrador KRON', 1, 1, NOW());

-- FIM

