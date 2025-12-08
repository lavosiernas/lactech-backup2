-- =====================================================
-- SafeNode - Script de Particionamento de Tabelas
-- Melhoria #4: Particionamento de Tabelas de Logs
-- 
-- Este script particiona a tabela safenode_security_logs
-- por data para melhorar performance e facilitar arquivamento
-- =====================================================

-- IMPORTANTE: Execute este script no banco de dados safend
-- USE safend;

-- =====================================================
-- 1. VERIFICAR SE A TABELA JÁ ESTÁ PARTICIONADA
-- =====================================================

-- Execute primeiro para verificar:
-- SELECT 
--     TABLE_NAME,
--     PARTITION_NAME,
--     PARTITION_EXPRESSION,
--     PARTITION_DESCRIPTION
-- FROM INFORMATION_SCHEMA.PARTITIONS
-- WHERE TABLE_SCHEMA = 'safend'
-- AND TABLE_NAME = 'safenode_security_logs'
-- AND PARTITION_NAME IS NOT NULL;

-- =====================================================
-- 2. BACKUP DA TABELA ANTES DE PARTICIONAR
-- =====================================================

-- IMPORTANTE: Faça backup antes de executar!
-- CREATE TABLE safenode_security_logs_backup AS 
-- SELECT * FROM safenode_security_logs;

-- =====================================================
-- 3. REMOVER PARTICIONAMENTO EXISTENTE (SE HOUVER)
-- =====================================================

-- Se a tabela já estiver particionada, remova primeiro:
-- ALTER TABLE safenode_security_logs REMOVE PARTITIONING;

-- =====================================================
-- 4. CRIAR PARTICIONAMENTO POR RANGE (MENSAL)
-- =====================================================

-- Particionar por mês (cada partição contém 1 mês de dados)
-- Partições são criadas para os próximos 12 meses

ALTER TABLE safenode_security_logs
PARTITION BY RANGE (TO_DAYS(created_at)) (
    PARTITION p_202401 VALUES LESS THAN (TO_DAYS('2024-02-01')),
    PARTITION p_202402 VALUES LESS THAN (TO_DAYS('2024-03-01')),
    PARTITION p_202403 VALUES LESS THAN (TO_DAYS('2024-04-01')),
    PARTITION p_202404 VALUES LESS THAN (TO_DAYS('2024-05-01')),
    PARTITION p_202405 VALUES LESS THAN (TO_DAYS('2024-06-01')),
    PARTITION p_202406 VALUES LESS THAN (TO_DAYS('2024-07-01')),
    PARTITION p_202407 VALUES LESS THAN (TO_DAYS('2024-08-01')),
    PARTITION p_202408 VALUES LESS THAN (TO_DAYS('2024-09-01')),
    PARTITION p_202409 VALUES LESS THAN (TO_DAYS('2024-10-01')),
    PARTITION p_202410 VALUES LESS THAN (TO_DAYS('2024-11-01')),
    PARTITION p_202411 VALUES LESS THAN (TO_DAYS('2024-12-01')),
    PARTITION p_202412 VALUES LESS THAN (TO_DAYS('2025-01-01')),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- =====================================================
-- 5. SCRIPT PARA ADICIONAR NOVA PARTIÇÃO MENSALMENTE
-- =====================================================

-- Execute este script mensalmente (via cron) para adicionar nova partição:
-- 
-- SET @next_month = DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01');
-- SET @partition_name = CONCAT('p_', DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y%m'));
-- SET @sql = CONCAT('ALTER TABLE safenode_security_logs REORGANIZE PARTITION p_future INTO (PARTITION ', @partition_name, ' VALUES LESS THAN (TO_DAYS(''', @next_month, ''')), PARTITION p_future VALUES LESS THAN MAXVALUE)');
-- PREPARE stmt FROM @sql;
-- EXECUTE stmt;
-- DEALLOCATE PREPARE stmt;

-- =====================================================
-- 6. SCRIPT PARA ARQUIVAR PARTIÇÕES ANTIGAS (>90 DIAS)
-- =====================================================

-- Criar tabela de arquivo para logs antigos
CREATE TABLE IF NOT EXISTS safenode_security_logs_archive (
    LIKE safenode_security_logs
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Script para arquivar partição antiga (executar via cron mensalmente):
-- 
-- -- 1. Copiar dados da partição antiga para tabela de arquivo
-- INSERT INTO safenode_security_logs_archive
-- SELECT * FROM safenode_security_logs
-- WHERE created_at < DATE_SUB(CURDATE(), INTERVAL 90 DAY);
-- 
-- -- 2. Deletar dados da partição antiga
-- DELETE FROM safenode_security_logs
-- WHERE created_at < DATE_SUB(CURDATE(), INTERVAL 90 DAY);
-- 
-- -- 3. Otimizar tabela após deletar
-- OPTIMIZE TABLE safenode_security_logs;

-- =====================================================
-- 7. ALTERNATIVA: PARTICIONAMENTO SEMANAL (PARA ALTO TRÁFEGO)
-- =====================================================

-- Se você tem muito tráfego, pode particionar por semana:
-- 
-- ALTER TABLE safenode_security_logs
-- PARTITION BY RANGE (TO_DAYS(created_at)) (
--     PARTITION p_w202401 VALUES LESS THAN (TO_DAYS('2024-01-08')),
--     PARTITION p_w202402 VALUES LESS THAN (TO_DAYS('2024-01-15')),
--     PARTITION p_w202403 VALUES LESS THAN (TO_DAYS('2024-01-22')),
--     PARTITION p_w202404 VALUES LESS THAN (TO_DAYS('2024-01-29')),
--     PARTITION p_w202405 VALUES LESS THAN (TO_DAYS('2024-02-05')),
--     -- ... adicionar mais semanas
--     PARTITION p_future VALUES LESS THAN MAXVALUE
-- );

-- =====================================================
-- 8. VERIFICAR PARTICIONAMENTO
-- =====================================================

-- Para verificar partições e estatísticas:
-- SELECT 
--     PARTITION_NAME,
--     TABLE_ROWS,
--     AVG_ROW_LENGTH,
--     DATA_LENGTH,
--     INDEX_LENGTH
-- FROM INFORMATION_SCHEMA.PARTITIONS
-- WHERE TABLE_SCHEMA = 'safend'
-- AND TABLE_NAME = 'safenode_security_logs'
-- AND PARTITION_NAME IS NOT NULL
-- ORDER BY PARTITION_ORDINAL_POSITION;

-- =====================================================
-- 9. BENEFÍCIOS DO PARTICIONAMENTO
-- =====================================================

-- 1. Queries mais rápidas: MySQL só precisa verificar partições relevantes
-- 2. Manutenção mais fácil: pode deletar partições antigas inteiras
-- 3. Backup mais eficiente: pode fazer backup de partições individuais
-- 4. Melhor uso de índices: índices são menores por partição
-- 5. Paralelização: MySQL pode processar múltiplas partições em paralelo

-- =====================================================
-- 10. NOTAS IMPORTANTES
-- =====================================================

-- 1. Particionamento requer que a coluna de particionamento (created_at)
--    seja parte de TODAS as chaves primárias/únicas
-- 
-- 2. Se a tabela já tem muitos dados, o particionamento pode demorar
--    Considere fazer em horário de baixo tráfego
-- 
-- 3. Após particionar, execute ANALYZE TABLE para atualizar estatísticas
--    ANALYZE TABLE safenode_security_logs;
-- 
-- 4. Monitore o uso de espaço: partições antigas podem ser movidas para
--    storage mais barato (cold storage)
-- 
-- 5. Para desfazer particionamento:
--    ALTER TABLE safenode_security_logs REMOVE PARTITIONING;



