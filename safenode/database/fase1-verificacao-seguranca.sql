-- =====================================================
-- FASE 1: VERIFICAÇÃO - APIs DE SEGURANÇA
-- SafeNode - Verificação de estrutura necessária
-- =====================================================
-- 
-- OBJETIVO: Garantir que a tabela safenode_human_verification_logs
-- tem todos os campos necessários para as APIs de segurança funcionarem
--
-- APIs criadas:
-- 1. api/threat-detection.php
-- 2. api/behavior-analysis.php  
-- 3. api/security-recommendations.php
--
-- EXECUTAR: Este script verifica e adiciona campos caso não existam
-- NÃO DESTRUTIVO: Só adiciona se não existir
--

-- Verificar e adicionar campo 'reason' se não existir
-- (Necessário para threat-detection.php e security-recommendations.php)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'safenode_human_verification_logs' 
    AND COLUMN_NAME = 'reason'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `safenode_human_verification_logs` ADD COLUMN `reason` VARCHAR(255) DEFAULT NULL AFTER `country_code`',
    'SELECT "Campo reason já existe" AS resultado'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar campo 'api_key_id' se não existir
-- (Necessário para suporte a SDK)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'safenode_human_verification_logs' 
    AND COLUMN_NAME = 'api_key_id'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `safenode_human_verification_logs` ADD COLUMN `api_key_id` INT(11) DEFAULT NULL AFTER `site_id`, ADD INDEX `idx_api_key_id` (`api_key_id`)',
    'SELECT "Campo api_key_id já existe" AS resultado'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar se site_id pode ser NULL (para suporte a SDK)
SET @col_nullable = (
    SELECT IS_NULLABLE 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'safenode_human_verification_logs' 
    AND COLUMN_NAME = 'site_id'
);

SET @sql = IF(@col_nullable = 'NO',
    'ALTER TABLE `safenode_human_verification_logs` MODIFY COLUMN `site_id` INT(11) DEFAULT NULL',
    'SELECT "Campo site_id já permite NULL" AS resultado'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar índices necessários para performance das queries
-- Estes índices melhoram performance das APIs

-- Índice para buscas por event_type + created_at (usado em todas as APIs)
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'safenode_human_verification_logs' 
    AND INDEX_NAME = 'idx_event_created'
);

SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX `idx_event_created` ON `safenode_human_verification_logs` (`event_type`, `created_at`)',
    'SELECT "Índice idx_event_created já existe" AS resultado'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para buscas por event_type + reason (usado em threat-detection.php)
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'safenode_human_verification_logs' 
    AND INDEX_NAME = 'idx_event_reason'
);

SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX `idx_event_reason` ON `safenode_human_verification_logs` (`event_type`, `reason`(50))',
    'SELECT "Índice idx_event_reason já existe" AS resultado'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para buscas por ip_address + created_at (usado em behavior-analysis.php)
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'safenode_human_verification_logs' 
    AND INDEX_NAME = 'idx_ip_created'
);

SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX `idx_ip_created` ON `safenode_human_verification_logs` (`ip_address`, `created_at`)',
    'SELECT "Índice idx_ip_created já existe" AS resultado'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice para buscas por request_uri (usado em security-recommendations.php)
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'safenode_human_verification_logs' 
    AND INDEX_NAME = 'idx_request_uri'
);

SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX `idx_request_uri` ON `safenode_human_verification_logs` (`request_uri`(255))',
    'SELECT "Índice idx_request_uri já existe" AS resultado'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificação final: Listar campos da tabela
SELECT 
    COLUMN_NAME as 'Campo',
    COLUMN_TYPE as 'Tipo',
    IS_NULLABLE as 'Permite NULL',
    COLUMN_DEFAULT as 'Valor Padrão'
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'safenode_human_verification_logs'
ORDER BY ORDINAL_POSITION;

-- Verificação de índices
SELECT 
    INDEX_NAME as 'Índice',
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ', ') as 'Colunas'
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'safenode_human_verification_logs'
GROUP BY INDEX_NAME
ORDER BY INDEX_NAME;

SELECT '✅ Verificação da Fase 1 concluída!' AS status;

