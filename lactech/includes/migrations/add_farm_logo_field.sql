-- ============================================================
-- ADICIONAR CAMPO LOGO NA TABELA FARMS
-- ============================================================

-- Adicionar coluna logo_path na tabela farms
SET @dbname = DATABASE();
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'farms' 
    AND COLUMN_NAME = 'logo_path'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `farms` ADD COLUMN `logo_path` varchar(255) DEFAULT NULL COMMENT ''Caminho da logo da fazenda para relatórios'' AFTER `email`',
    'SELECT ''Coluna logo_path já existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Campo logo_path adicionado com sucesso!' AS resultado;

