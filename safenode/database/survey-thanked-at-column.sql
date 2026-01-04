-- ============================================
-- SafeNode Survey - Adicionar coluna thanked_at
-- ============================================
-- Este script adiciona a coluna thanked_at na tabela safenode_survey_responses
-- para rastrear quando um email de agradecimento foi enviado
-- ============================================

-- Verificar se a coluna já existe antes de adicionar
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'safenode_survey_responses'
    AND COLUMN_NAME = 'thanked_at'
);

-- Adicionar coluna thanked_at se não existir
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `safenode_survey_responses` 
     ADD COLUMN `thanked_at` TIMESTAMP NULL DEFAULT NULL AFTER `created_at`',
    'SELECT "Coluna thanked_at já existe" AS mensagem'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice na coluna thanked_at se não existir
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'safenode_survey_responses'
    AND INDEX_NAME = 'idx_thanked_at'
);

SET @sql_idx = IF(@idx_exists = 0,
    'ALTER TABLE `safenode_survey_responses` 
     ADD INDEX `idx_thanked_at` (`thanked_at`)',
    'SELECT "Índice idx_thanked_at já existe" AS mensagem'
);

PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

-- Verificar estrutura final
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'safenode_survey_responses'
ORDER BY ORDINAL_POSITION;






