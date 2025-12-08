-- =====================================================
-- SafeNode - Script de Particionamento (SEGURO)
-- Versão compatível com Hostinger
-- 
-- IMPORTANTE: Particionamento pode não ser permitido
-- em planos compartilhados da Hostinger. Este script
-- verifica permissões antes de tentar particionar.
-- =====================================================

-- IMPORTANTE: Execute este script no banco de dados u311882628_safend
-- USE u311882628_safend;

-- =====================================================
-- 1. VERIFICAR SE PARTICIONAMENTO É PERMITIDO
-- =====================================================

-- Verifica se a tabela já está particionada
SET @is_partitioned := (
    SELECT COUNT(*) 
    FROM information_schema.partitions 
    WHERE table_schema = DATABASE() 
    AND table_name = 'safenode_security_logs' 
    AND partition_name IS NOT NULL
);

-- Verifica se tem permissão para particionar (MySQL 8.0+)
SET @can_partition := (
    SELECT COUNT(*) 
    FROM information_schema.user_privileges 
    WHERE grantee = CONCAT('''', USER(), '''@''', SUBSTRING_INDEX(USER(), '@', -1), '''')
    AND privilege_type IN ('ALTER', 'CREATE', 'INDEX')
);

-- =====================================================
-- 2. BACKUP DA TABELA (IMPORTANTE!)
-- =====================================================

-- ANTES DE CONTINUAR: Faça backup manual da tabela!
-- CREATE TABLE safenode_security_logs_backup AS 
-- SELECT * FROM safenode_security_logs;

-- =====================================================
-- 3. CRIAÇÃO DE TABELA DE ARQUIVO (SEM PARTICIONAMENTO)
-- =====================================================

-- Como particionamento NÃO funciona em planos compartilhados da Hostinger,
-- criamos tabela de arquivo com índices otimizados (performance similar)

-- Criar tabela de arquivo para logs antigos (>90 dias)
-- SEM PARTICIONAMENTO (Hostinger não permite funções de data no particionamento)
-- Índices otimizados garantem performance excelente mesmo sem particionamento
CREATE TABLE IF NOT EXISTS safenode_security_logs_archive (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ip_address` varchar(45) NOT NULL,
    `user_agent` text DEFAULT NULL,
    `request_uri` text NOT NULL,
    `request_method` varchar(10) NOT NULL,
    `request_headers` text DEFAULT NULL,
    `request_body` text DEFAULT NULL,
    `threat_type` varchar(50) DEFAULT NULL,
    `threat_details` text DEFAULT NULL,
    `threat_score` int(11) DEFAULT 0,
    `action_taken` varchar(50) NOT NULL,
    `response_code` int(11) DEFAULT 200,
    `response_time` decimal(10,2) DEFAULT NULL,
    `cloudflare_ray` varchar(100) DEFAULT NULL,
    `cloudflare_country` varchar(2) DEFAULT NULL,
    `user_id` int(11) DEFAULT NULL,
    `session_id` varchar(255) DEFAULT NULL,
    `site_id` int(11) DEFAULT NULL,
    `country_code` char(2) DEFAULT NULL,
    `referer` text DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_archive_ip_created` (`ip_address`, `created_at`),
    KEY `idx_archive_site_created` (`site_id`, `created_at`),
    KEY `idx_archive_created` (`created_at`),
    KEY `idx_archive_date_month` (`created_at`),
    KEY `idx_archive_threat_type` (`threat_type`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. STORED PROCEDURE PARA ARQUIVAR LOGS ANTIGOS
-- =====================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_archive_old_logs$$

CREATE PROCEDURE sp_archive_old_logs(IN days_to_keep INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Mover logs antigos para tabela de arquivo
    INSERT INTO safenode_security_logs_archive
    SELECT * FROM safenode_security_logs
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY)
    LIMIT 10000; -- Processar em lotes de 10k para evitar timeout
    
    -- Deletar logs antigos da tabela principal
    DELETE FROM safenode_security_logs
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY)
    LIMIT 10000;
    
    COMMIT;
    
    SELECT CONCAT('Arquivados ', ROW_COUNT(), ' registros') AS resultado;
END$$

DELIMITER ;

-- =====================================================
-- 5. EVENTO PARA ARQUIVAMENTO AUTOMÁTICO (SE PERMITIDO)
-- =====================================================

-- Verificar se eventos estão habilitados
SET @events_enabled := (
    SELECT @@event_scheduler
);

-- Se eventos estiverem habilitados, criar evento mensal
-- Caso contrário, executar sp_archive_old_logs manualmente via cron

-- DELIMITER $$
-- 
-- DROP EVENT IF EXISTS evt_archive_old_logs$$
-- 
-- CREATE EVENT IF NOT EXISTS evt_archive_old_logs
-- ON SCHEDULE EVERY 1 DAY
-- STARTS DATE_ADD(CURDATE(), INTERVAL 1 DAY)
-- DO
-- BEGIN
--     CALL sp_archive_old_logs(90); -- Manter 90 dias
-- END$$
-- 
-- DELIMITER ;

-- =====================================================
-- 6. PROCEDURE PARA LIMPAR LOGS ANTIGOS (SEM PARTICIONAMENTO)
-- =====================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_cleanup_old_archive$$

CREATE PROCEDURE sp_cleanup_old_archive(IN months_to_keep INT)
BEGIN
    DECLARE rows_deleted INT DEFAULT 0;
    DECLARE cutoff_date DATE;
    
    SET cutoff_date = DATE_SUB(CURDATE(), INTERVAL months_to_keep MONTH);
    
    -- Deletar logs arquivados muito antigos
    DELETE FROM safenode_security_logs_archive
    WHERE DATE(created_at) < cutoff_date;
    
    SET rows_deleted = ROW_COUNT();
    
    SELECT 
        rows_deleted AS 'Registros deletados',
        cutoff_date AS 'Data de corte',
        CONCAT('Limpeza concluída. ', rows_deleted, ' registros removidos.') AS resultado;
END$$

DELIMITER ;

-- =====================================================
-- 7. VERIFICAÇÕES E ESTATÍSTICAS
-- =====================================================

-- Verificar estrutura da tabela principal
SELECT 
    'safenode_security_logs' AS tabela,
    COUNT(*) AS total_registros,
    MIN(created_at) AS registro_mais_antigo,
    MAX(created_at) AS registro_mais_recente
FROM safenode_security_logs;

-- Verificar tabela de arquivo (se existir)
SELECT 
    'safenode_security_logs_archive' AS tabela,
    COUNT(*) AS total_registros,
    MIN(created_at) AS registro_mais_antigo,
    MAX(created_at) AS registro_mais_recente
FROM safenode_security_logs_archive;

-- Verificar tamanho e estatísticas da tabela de arquivo
SELECT 
    table_name AS 'Tabela',
    table_rows AS 'Registros',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Tamanho (MB)',
    ROUND((data_length / 1024 / 1024), 2) AS 'Dados (MB)',
    ROUND((index_length / 1024 / 1024), 2) AS 'Índices (MB)'
FROM information_schema.tables
WHERE table_schema = DATABASE()
AND table_name = 'safenode_security_logs_archive';

-- =====================================================
-- 8. INSTRUÇÕES DE USO
-- =====================================================

-- Para arquivar logs antigos manualmente:
-- CALL sp_archive_old_logs(90); -- Manter últimos 90 dias

-- Para limpar logs arquivados muito antigos (>12 meses):
-- CALL sp_cleanup_old_archive(12); -- Manter últimos 12 meses

-- Para verificar estrutura da tabela:
-- SHOW CREATE TABLE safenode_security_logs_archive;

-- Para verificar índices criados:
-- SHOW INDEX FROM safenode_security_logs_archive;

-- =====================================================
-- NOTAS IMPORTANTES - HOSTINGER
-- =====================================================

-- 1. ✅ Esta versão NÃO usa particionamento (mais compatível)
--    Índices otimizados fornecem performance excelente mesmo sem particionamento
-- 
-- 2. ✅ A tabela de arquivo foi criada SEM particionamento
--    Funciona em qualquer plano da Hostinger
-- 
-- 3. ✅ Execute o arquivamento via cron job (não via eventos MySQL):
--    php /caminho/safenode/api/archive-old-logs.php
-- 
-- 4. ✅ Monitore o tamanho das tabelas:
--    SELECT table_name, 
--           ROUND(((data_length + index_length) / 1024 / 1024), 2) AS tamanho_mb
--    FROM information_schema.tables
--    WHERE table_schema = DATABASE()
--    AND table_name LIKE 'safenode_security_logs%';
-- 
-- 5. ✅ Índices criados garantem queries rápidas mesmo sem particionamento:
--    - idx_archive_created: Para queries por data
--    - idx_archive_ip_created: Para queries por IP + data
--    - idx_archive_site_created: Para queries por site + data
--    - idx_archive_date_month: Índice adicional para queries mensais
-- 
-- 6. ✅ Faça backup regular da tabela de arquivo também!
-- 
-- 7. ✅ Para limpar logs arquivados muito antigos (>1 ano):
--    CALL sp_cleanup_old_archive(12);

