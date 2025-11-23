-- =====================================================
-- CREATE SESSIONS AND ACTIVITY TABLES
-- =====================================================
-- Tabelas para gerenciar sessões ativas e histórico de atividades
-- =====================================================

-- Tabela de Sessões Ativas
CREATE TABLE IF NOT EXISTS `safenode_user_sessions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `session_token` VARCHAR(64) NOT NULL UNIQUE,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT,
  `device_type` VARCHAR(50) DEFAULT 'unknown' COMMENT 'desktop, mobile, tablet',
  `browser` VARCHAR(100),
  `os` VARCHAR(100),
  `country` VARCHAR(100),
  `city` VARCHAR(100),
  `is_current` TINYINT(1) DEFAULT 0,
  `last_activity` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_session_token` (`session_token`),
  INDEX `idx_last_activity` (`last_activity`),
  FOREIGN KEY (`user_id`) REFERENCES `safenode_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Histórico de Atividades
CREATE TABLE IF NOT EXISTS `safenode_activity_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `action` VARCHAR(100) NOT NULL COMMENT 'login, logout, password_change, profile_update, etc',
  `description` TEXT,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `device_type` VARCHAR(50) DEFAULT 'unknown',
  `browser` VARCHAR(100),
  `os` VARCHAR(100),
  `metadata` JSON DEFAULT NULL COMMENT 'Dados adicionais da ação',
  `status` VARCHAR(20) DEFAULT 'success' COMMENT 'success, failed, warning',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_user_date` (`user_id`, `created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `safenode_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

