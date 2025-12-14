-- =====================================================
-- SafeNode: Tabela para Reset de Senha
-- =====================================================
-- Data: 2025-12-13
-- Descrição: Tabela para armazenar tokens de reset de senha
-- =====================================================

CREATE TABLE IF NOT EXISTS `safenode_password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL COMMENT 'Token único para reset',
  `expires_at` timestamp NOT NULL COMMENT 'Data de expiração do token',
  `used_at` timestamp NULL DEFAULT NULL COMMENT 'Data em que o token foi usado',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP que solicitou o reset',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`token`),
  KEY `idx_user` (`user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice composto para busca rápida de tokens válidos
CREATE INDEX IF NOT EXISTS `idx_token_valid` ON `safenode_password_resets` (`token`, `expires_at`, `used_at`);

