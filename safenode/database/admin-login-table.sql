-- ============================================
-- SafeNode Survey Admin - Tabela de Login
-- ============================================

-- Criar tabela para admin
CREATE TABLE IF NOT EXISTS `safenode_survey_admin` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL DEFAULT 'admin',
  `password_hash` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir admin padrão com senha hashada
-- Senha: lnassfnd017852
INSERT INTO `safenode_survey_admin` (`username`, `password_hash`, `email`) 
VALUES ('admin', '$2y$10$683RpLhFQNc4Fjhhf8X0l.y1zrzHhGo8Mo6EKbHA2DwDi9jCTj1aC', 'safenodemail@safenode.cloud')
ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`);

