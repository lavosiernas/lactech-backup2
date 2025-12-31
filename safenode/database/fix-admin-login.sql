-- ============================================
-- SafeNode Survey Admin - FIX Login
-- Execute este SQL no banco de dados
-- ============================================

-- 1. Criar tabela se não existir
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

-- 2. Deletar admin antigo se existir (para garantir)
DELETE FROM `safenode_survey_admin` WHERE `username` = 'admin';

-- 3. Inserir admin com senha hashada
-- Senha: lnassfnd017852
-- Hash gerado com PASSWORD_BCRYPT (novo hash)
INSERT INTO `safenode_survey_admin` (`username`, `password_hash`, `email`) 
VALUES ('admin', '$2y$10$aSK39LJXWFGz1GxQVR.a2OwOZAvU/veo3aqi0qru5.dJROeRTiosq', 'safenodemail@safenode.cloud')
ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`);

-- 4. Verificar se foi inserido
SELECT id, username, email, LEFT(password_hash, 30) as hash_preview FROM safenode_survey_admin WHERE username = 'admin';

