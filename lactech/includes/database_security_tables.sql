-- =====================================================
-- TABELAS DE SEGURANÇA - LACTECH
-- Sistema completo de segurança para alteração de senha e vinculação de contas
-- =====================================================

-- Tabela de verificação de e-mail
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `verification_token` varchar(255) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `verification_token` (`verification_token`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de códigos OTP (One-Time Password)
CREATE TABLE IF NOT EXISTS `otp_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'password_change, email_change, google_unlink, 2fa_setup, etc',
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `code` (`code`),
  KEY `action` (`action`),
  KEY `is_used` (`is_used`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de vinculação Google
CREATE TABLE IF NOT EXISTS `google_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `google_id` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `picture` varchar(500) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `linked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unlinked_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `google_id` (`google_id`),
  KEY `user_id` (`user_id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de auditoria de segurança
CREATE TABLE IF NOT EXISTS `security_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL COMMENT 'password_changed, email_verified, google_linked, google_unlinked, 2fa_enabled, etc',
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 1,
  `error_message` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Metadados em formato JSON',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  KEY `success` (`success`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de autenticação de dois fatores (2FA)
CREATE TABLE IF NOT EXISTS `two_factor_auth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `secret_key` varchar(255) NOT NULL COMMENT 'Chave secreta TOTP',
  `backup_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Códigos de backup JSON array',
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar campos necessários na tabela users (se não existirem)
-- Nota: Usar procedure ou verificações manuais, pois ADD COLUMN IF NOT EXISTS pode não funcionar em versões antigas do MySQL

-- Verificar e adicionar campos na tabela users (se não existirem)
-- Nota: Usar verificações dinâmicas para compatibilidade com versões antigas do MySQL/MariaDB

SET @dbname = DATABASE();
SET @tablename = 'users';

-- Verificar e adicionar email_verified
SET @columnname = 'email_verified';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` tinyint(1) NOT NULL DEFAULT 0 AFTER `email`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar e adicionar email_verified_at
SET @columnname = 'email_verified_at';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` timestamp NULL DEFAULT NULL AFTER `email_verified`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar e adicionar password_changed_at
SET @columnname = 'password_changed_at';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` timestamp NULL DEFAULT NULL AFTER `password`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar e adicionar password_change_required
SET @columnname = 'password_change_required';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` tinyint(1) NOT NULL DEFAULT 0 AFTER `password_changed_at`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar e adicionar last_security_check
SET @columnname = 'last_security_check';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` timestamp NULL DEFAULT NULL AFTER `password_change_required`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar e adicionar failed_login_attempts
SET @columnname = 'failed_login_attempts';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` int(11) NOT NULL DEFAULT 0 AFTER `last_security_check`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar e adicionar account_locked_until
SET @columnname = 'account_locked_until';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` timestamp NULL DEFAULT NULL AFTER `failed_login_attempts`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Índices adicionais para segurança
-- Verificar e criar índice email_verified
SET @indexname = 'idx_users_email_verified';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (INDEX_NAME = @indexname)
  ) > 0,
  'SELECT 1',
  CONCAT('CREATE INDEX `', @indexname, '` ON `', @tablename, '` (`email_verified`)')
));
PREPARE createIndexIfNotExists FROM @preparedStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- Verificar e criar índice failed_attempts
SET @indexname = 'idx_users_failed_attempts';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (INDEX_NAME = @indexname)
  ) > 0,
  'SELECT 1',
  CONCAT('CREATE INDEX `', @indexname, '` ON `', @tablename, '` (`failed_login_attempts`)')
));
PREPARE createIndexIfNotExists FROM @preparedStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- Adicionar Foreign Keys (após garantir que a tabela users existe e tem PRIMARY KEY)
-- IMPORTANTE: Se as tabelas já existirem com dados, limpe dados órfãos antes de executar este script
-- DELETE FROM email_verifications WHERE user_id NOT IN (SELECT id FROM users);
-- DELETE FROM otp_codes WHERE user_id NOT IN (SELECT id FROM users);
-- DELETE FROM google_accounts WHERE user_id NOT IN (SELECT id FROM users);
-- DELETE FROM security_audit_log WHERE user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM users);
-- DELETE FROM two_factor_auth WHERE user_id NOT IN (SELECT id FROM users);

-- Primeiro, garantir que a tabela users tem PRIMARY KEY
SET @hasPrimaryKey = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = @dbname
  AND TABLE_NAME = 'users'
  AND CONSTRAINT_TYPE = 'PRIMARY KEY');

-- Se não tiver PRIMARY KEY, adicionar (só funciona se não houver valores duplicados/nulos em id)
SET @preparedStatement = (SELECT IF(
  @hasPrimaryKey > 0,
  'SELECT 1',
  'ALTER TABLE `users` ADD PRIMARY KEY (`id`)'
));
PREPARE addPrimaryKeyIfNotExists FROM @preparedStatement;
EXECUTE addPrimaryKeyIfNotExists;
DEALLOCATE PREPARE addPrimaryKeyIfNotExists;

-- Verificar e adicionar foreign key para email_verifications
-- Primeiro, remover foreign key existente se estiver mal formada
SET @fkname = 'fk_email_verifications_user_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'email_verifications')
      AND (CONSTRAINT_NAME = @fkname)
  ) > 0,
  CONCAT('ALTER TABLE `email_verifications` DROP FOREIGN KEY `', @fkname, '`'),
  'SELECT 1'
));
PREPARE dropFKIfExists FROM @preparedStatement;
EXECUTE dropFKIfExists;
DEALLOCATE PREPARE dropFKIfExists;

-- Agora criar a foreign key
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'email_verifications')
      AND (CONSTRAINT_NAME = @fkname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `email_verifications` ADD CONSTRAINT `', @fkname, '` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE')
));
PREPARE alterFKIfNotExists FROM @preparedStatement;
EXECUTE alterFKIfNotExists;
DEALLOCATE PREPARE alterFKIfNotExists;

-- Verificar e adicionar foreign key para otp_codes
SET @fkname = 'fk_otp_codes_user_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'otp_codes')
      AND (CONSTRAINT_NAME = @fkname)
  ) > 0,
  CONCAT('ALTER TABLE `otp_codes` DROP FOREIGN KEY `', @fkname, '`'),
  'SELECT 1'
));
PREPARE dropFKIfExists FROM @preparedStatement;
EXECUTE dropFKIfExists;
DEALLOCATE PREPARE dropFKIfExists;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'otp_codes')
      AND (CONSTRAINT_NAME = @fkname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `otp_codes` ADD CONSTRAINT `', @fkname, '` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE')
));
PREPARE alterFKIfNotExists FROM @preparedStatement;
EXECUTE alterFKIfNotExists;
DEALLOCATE PREPARE alterFKIfNotExists;

-- Verificar e adicionar foreign key para google_accounts
SET @fkname = 'fk_google_accounts_user_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'google_accounts')
      AND (CONSTRAINT_NAME = @fkname)
  ) > 0,
  CONCAT('ALTER TABLE `google_accounts` DROP FOREIGN KEY `', @fkname, '`'),
  'SELECT 1'
));
PREPARE dropFKIfExists FROM @preparedStatement;
EXECUTE dropFKIfExists;
DEALLOCATE PREPARE dropFKIfExists;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'google_accounts')
      AND (CONSTRAINT_NAME = @fkname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `google_accounts` ADD CONSTRAINT `', @fkname, '` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE')
));
PREPARE alterFKIfNotExists FROM @preparedStatement;
EXECUTE alterFKIfNotExists;
DEALLOCATE PREPARE alterFKIfNotExists;

-- Verificar e adicionar foreign key para security_audit_log
SET @fkname = 'fk_security_audit_log_user_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'security_audit_log')
      AND (CONSTRAINT_NAME = @fkname)
  ) > 0,
  CONCAT('ALTER TABLE `security_audit_log` DROP FOREIGN KEY `', @fkname, '`'),
  'SELECT 1'
));
PREPARE dropFKIfExists FROM @preparedStatement;
EXECUTE dropFKIfExists;
DEALLOCATE PREPARE dropFKIfExists;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'security_audit_log')
      AND (CONSTRAINT_NAME = @fkname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `security_audit_log` ADD CONSTRAINT `', @fkname, '` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE')
));
PREPARE alterFKIfNotExists FROM @preparedStatement;
EXECUTE alterFKIfNotExists;
DEALLOCATE PREPARE alterFKIfNotExists;

-- Verificar e adicionar foreign key para two_factor_auth
SET @fkname = 'fk_two_factor_auth_user_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'two_factor_auth')
      AND (CONSTRAINT_NAME = @fkname)
  ) > 0,
  CONCAT('ALTER TABLE `two_factor_auth` DROP FOREIGN KEY `', @fkname, '`'),
  'SELECT 1'
));
PREPARE dropFKIfExists FROM @preparedStatement;
EXECUTE dropFKIfExists;
DEALLOCATE PREPARE dropFKIfExists;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'two_factor_auth')
      AND (CONSTRAINT_NAME = @fkname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `two_factor_auth` ADD CONSTRAINT `', @fkname, '` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE')
));
PREPARE alterFKIfNotExists FROM @preparedStatement;
EXECUTE alterFKIfNotExists;
DEALLOCATE PREPARE alterFKIfNotExists;

