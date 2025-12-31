-- ============================================
-- SafeNode Survey - Adicionar coluna thanked_at
-- Versão segura que verifica se já existe
-- ============================================

-- Adicionar coluna thanked_at (ignora erro se já existir)
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN IF NOT EXISTS `thanked_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data/hora em que o email de agradecimento foi enviado' AFTER `created_at`;

-- Adicionar índice na coluna thanked_at (ignora erro se já existir)
ALTER TABLE `safenode_survey_responses` 
ADD INDEX IF NOT EXISTS `idx_thanked_at` (`thanked_at`);

-- Nota: Se o MySQL não suportar IF NOT EXISTS, execute o script add-survey-thanked-at.sql
-- e ignore qualquer erro de "Duplicate column name" ou "Duplicate key name"

