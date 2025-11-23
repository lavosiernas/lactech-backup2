-- =====================================================
-- CREATE 2FA TABLE
-- =====================================================
-- Tabela para armazenar configurações de 2FA dos usuários
-- =====================================================

CREATE TABLE IF NOT EXISTS `safenode_user_2fa` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL UNIQUE,
  `secret_key` VARCHAR(32) NOT NULL COMMENT 'Chave secreta para geração de códigos TOTP',
  `is_enabled` TINYINT(1) DEFAULT 0 COMMENT '2FA está ativado?',
  `backup_codes` TEXT DEFAULT NULL COMMENT 'Códigos de backup em JSON',
  `qr_code_setup_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Quando foi configurado',
  `last_used_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Último uso do 2FA',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `safenode_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para tentativas de verificação 2FA (rate limiting)
CREATE TABLE IF NOT EXISTS `safenode_2fa_attempts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempt_code` VARCHAR(6) DEFAULT NULL,
  `success` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `safenode_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


