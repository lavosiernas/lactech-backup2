-- ============================================================
-- ALTERAÇÕES PARA SUPORTAR ALIMENTAÇÃO POR LOTE
-- Execute este arquivo APENAS se já rodou o create_feeding_intelligence_tables.sql
-- ============================================================

-- --------------------------------------------------------
-- 1. MODIFICAR animal_weights PARA SUPORTAR PESO DO LOTE
-- --------------------------------------------------------
-- Adicionar campos para peso do lote (verificar se já existem)

-- Verificar e adicionar group_id se não existir
SET @dbname = DATABASE();
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'animal_weights' 
    AND COLUMN_NAME = 'group_id'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `animal_weights` ADD COLUMN `group_id` int(11) DEFAULT NULL COMMENT ''ID do grupo/lote (se peso do lote)'' AFTER `animal_id`',
    'SELECT ''Coluna group_id já existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar group_avg_weight_kg se não existir
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'animal_weights' 
    AND COLUMN_NAME = 'group_avg_weight_kg'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `animal_weights` ADD COLUMN `group_avg_weight_kg` decimal(7,2) DEFAULT NULL COMMENT ''Peso médio do lote em kg'' AFTER `weight_kg`',
    'SELECT ''Coluna group_avg_weight_kg já existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar animal_count se não existir
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'animal_weights' 
    AND COLUMN_NAME = 'animal_count'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `animal_weights` ADD COLUMN `animal_count` int(11) DEFAULT NULL COMMENT ''Número de animais do lote (quando peso do lote)'' AFTER `group_avg_weight_kg`',
    'SELECT ''Coluna animal_count já existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Modificar weight_kg para permitir NULL (pois pode ser só peso do lote)
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'animal_weights' 
    AND COLUMN_NAME = 'weight_kg'
    AND IS_NULLABLE = 'NO'
);

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE `animal_weights` MODIFY COLUMN `weight_kg` decimal(7,2) DEFAULT NULL COMMENT ''Peso individual em kg''',
    'SELECT ''Coluna weight_kg já permite NULL'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice para group_id se não existir
SET @idx_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'animal_weights' 
    AND INDEX_NAME = 'idx_group_id'
);

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `animal_weights` ADD KEY `idx_group_id` (`group_id`)',
    'SELECT ''Índice idx_group_id já existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice para group_date se não existir
SET @idx_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'animal_weights' 
    AND INDEX_NAME = 'idx_group_date'
);

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `animal_weights` ADD KEY `idx_group_date` (`group_id`, `weighing_date`)',
    'SELECT ''Índice idx_group_date já existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar foreign key para group_id se não existir
SET @fk_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'animal_weights' 
    AND CONSTRAINT_NAME = 'fk_animal_weights_group'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE `animal_weights` ADD CONSTRAINT `fk_animal_weights_group` FOREIGN KEY (`group_id`) REFERENCES `animal_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT ''Foreign key fk_animal_weights_group já existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------
-- 2. MODIFICAR feed_records PARA SUPORTAR LOTE
-- --------------------------------------------------------
-- Adicionar campos para suportar registro por lote

-- Modificar animal_id para permitir NULL (para registros por lote)
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'feed_records' 
    AND COLUMN_NAME = 'animal_id'
    AND IS_NULLABLE = 'NO'
);

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE `feed_records` MODIFY COLUMN `animal_id` int(11) DEFAULT NULL COMMENT ''ID do animal (se registro individual)''',
    'SELECT ''Coluna animal_id já permite NULL'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar group_id se não existir
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'feed_records' 
    AND COLUMN_NAME = 'group_id'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `feed_records` ADD COLUMN `group_id` int(11) DEFAULT NULL COMMENT ''ID do grupo/lote (se registro coletivo)'' AFTER `animal_id`',
    'SELECT ''Coluna group_id já existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar record_type se não existir
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'feed_records' 
    AND COLUMN_NAME = 'record_type'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `feed_records` ADD COLUMN `record_type` enum(''individual'',''group'') NOT NULL DEFAULT ''individual'' COMMENT ''Tipo: individual ou coletivo'' AFTER `group_id`',
    'SELECT ''Coluna record_type já existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar animal_count se não existir
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'feed_records' 
    AND COLUMN_NAME = 'animal_count'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `feed_records` ADD COLUMN `animal_count` int(11) DEFAULT NULL COMMENT ''Número de animais (para registros coletivos)'' AFTER `record_type`',
    'SELECT ''Coluna animal_count já existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice para group_id se não existir
SET @idx_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'feed_records' 
    AND INDEX_NAME = 'idx_group_id'
);

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `feed_records` ADD KEY `idx_group_id` (`group_id`)',
    'SELECT ''Índice idx_group_id já existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice para record_type se não existir
SET @idx_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'feed_records' 
    AND INDEX_NAME = 'idx_record_type'
);

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `feed_records` ADD KEY `idx_record_type` (`record_type`)',
    'SELECT ''Índice idx_record_type já existe'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Alterações concluídas!' AS resultado;

