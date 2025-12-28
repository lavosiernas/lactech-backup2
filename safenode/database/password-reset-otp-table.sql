-- =====================================================
-- SafeNode: Migração de Token para OTP
-- =====================================================
-- Data: 2025-12-13
-- Descrição: Remove tabela antiga de tokens e cria nova tabela para OTP
-- =====================================================

-- =====================================================
-- 1. REMOVER TABELA ANTIGA (TOKENS)
-- =====================================================

-- Desabilitar verificação de foreign keys temporariamente
SET FOREIGN_KEY_CHECKS = 0;

-- Remover tabela antiga (isso remove automaticamente foreign keys e índices)
DROP TABLE IF EXISTS `safenode_password_resets`;

-- Reabilitar verificação de foreign keys
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 2. CRIAR NOVA TABELA PARA OTP
-- =====================================================

CREATE TABLE IF NOT EXISTS `safenode_password_reset_otp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL COMMENT 'Código OTP de 6 dígitos',
  `expires_at` timestamp NOT NULL COMMENT 'Data de expiração do OTP',
  `used_at` timestamp NULL DEFAULT NULL COMMENT 'Data em que o OTP foi usado',
  `attempts` int(11) DEFAULT 0 COMMENT 'Número de tentativas de validação',
  `max_attempts` int(11) DEFAULT 5 COMMENT 'Máximo de tentativas permitidas',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP que solicitou o reset',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_otp_code` (`otp_code`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_used` (`used_at`),
  KEY `idx_otp_valid` (`otp_code`, `expires_at`, `used_at`, `attempts`),
  CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================

-- Índice composto para busca rápida de OTPs válidos
CREATE INDEX IF NOT EXISTS `idx_otp_lookup` ON `safenode_password_reset_otp` (`email`, `otp_code`, `expires_at`, `used_at`, `attempts`);

-- =====================================================
-- NOTAS
-- =====================================================
-- A tabela antiga safenode_password_resets foi removida
-- A nova tabela safenode_password_reset_otp usa códigos de 6 dígitos
-- OTP expira em 10 minutos por padrão
-- Máximo de 5 tentativas de validação por OTP
-- OTP é invalidado após uso ou expiração

