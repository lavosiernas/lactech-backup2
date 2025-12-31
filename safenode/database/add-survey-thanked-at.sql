-- ============================================
-- SafeNode Survey - Adicionar coluna thanked_at
-- Versão simples para executar diretamente
-- ============================================

-- Adicionar coluna thanked_at
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `thanked_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data/hora em que o email de agradecimento foi enviado' AFTER `created_at`;

-- Adicionar índice na coluna thanked_at
ALTER TABLE `safenode_survey_responses` 
ADD INDEX `idx_thanked_at` (`thanked_at`);

-- Para verificar se funcionou, execute: DESCRIBE safenode_survey_responses;

