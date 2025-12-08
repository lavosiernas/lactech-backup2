-- =====================================================
-- SafeNode - Script de Arquivo SEM Particionamento
-- Versão para Hostinger quando particionamento não funciona
-- 
-- Esta é uma alternativa simples e compatível
-- =====================================================

-- IMPORTANTE: Execute este script no banco de dados u311882628_safend
-- USE u311882628_safend;

-- =====================================================
-- 1. CRIAR TABELA DE ARQUIVO (SEM PARTICIONAMENTO)
-- =====================================================

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
    KEY `idx_archive_threat_type` (`threat_type`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. STORED PROCEDURE PARA ARQUIVAR LOGS ANTIGOS
-- =====================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_archive_old_logs$$

CREATE PROCEDURE sp_archive_old_logs(IN days_to_keep INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE rows_archived INT DEFAULT 0;
    DECLARE rows_deleted INT DEFAULT 0;
    DECLARE batch_size INT DEFAULT 10000;
    DECLARE continue HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Mover logs antigos para tabela de arquivo (em lotes)
    INSERT INTO safenode_security_logs_archive
    SELECT * FROM safenode_security_logs
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY)
    LIMIT batch_size;
    
    SET rows_archived = ROW_COUNT();
    
    -- Deletar logs antigos da tabela principal
    IF rows_archived > 0 THEN
        DELETE FROM safenode_security_logs
        WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY)
        AND id IN (
            SELECT id FROM safenode_security_logs_archive
            WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY)
            LIMIT batch_size
        );
        
        SET rows_deleted = ROW_COUNT();
    END IF;
    
    COMMIT;
    
    SELECT 
        rows_archived AS 'Registros arquivados',
        rows_deleted AS 'Registros deletados',
        CONCAT('Processados ', rows_archived, ' registros') AS resultado;
END$$

DELIMITER ;

-- =====================================================
-- 3. STORED PROCEDURE PARA ARQUIVAR POR LOTE (SAFE)
-- =====================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_archive_old_logs_safe$$

CREATE PROCEDURE sp_archive_old_logs_safe(IN days_to_keep INT, IN max_rows INT)
BEGIN
    DECLARE rows_archived INT DEFAULT 0;
    DECLARE total_archived INT DEFAULT 0;
    DECLARE continue HANDLER FOR SQLEXCEPTION
    BEGIN
        SELECT CONCAT('Erro ao arquivar. Total processado antes do erro: ', total_archived) AS erro;
        ROLLBACK;
    END;
    
    REPEAT
        START TRANSACTION;
        
        -- Mover lote de logs antigos
        INSERT INTO safenode_security_logs_archive
        SELECT * FROM safenode_security_logs
        WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY)
        LIMIT 1000;
        
        SET rows_archived = ROW_COUNT();
        
        IF rows_archived > 0 THEN
            -- Deletar do original
            DELETE FROM safenode_security_logs
            WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY)
            LIMIT 1000;
        END IF;
        
        COMMIT;
        
        SET total_archived = total_archived + rows_archived;
        
    UNTIL rows_archived = 0 OR total_archived >= max_rows
    END REPEAT;
    
    SELECT 
        total_archived AS 'Total arquivado',
        CONCAT('Processamento concluído. ', total_archived, ' registros arquivados.') AS resultado;
END$$

DELIMITER ;

-- =====================================================
-- 4. VERIFICAÇÕES
-- =====================================================

-- Verificar tabela de arquivo criada
SELECT 
    'safenode_security_logs_archive' AS tabela,
    COUNT(*) AS total_registros,
    MIN(created_at) AS registro_mais_antigo,
    MAX(created_at) AS registro_mais_recente,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS tamanho_mb
FROM safenode_security_logs_archive,
     (SELECT data_length, index_length 
      FROM information_schema.tables 
      WHERE table_schema = DATABASE() 
      AND table_name = 'safenode_security_logs_archive') as tbl_info;

-- =====================================================
-- 5. INSTRUÇÕES DE USO
-- =====================================================

-- Para arquivar logs antigos (manter últimos 90 dias):
-- CALL sp_archive_old_logs(90);

-- Para arquivar em lotes seguros (máximo 50k registros por vez):
-- CALL sp_archive_old_logs_safe(90, 50000);

-- Verificar quantos registros serão arquivados:
-- SELECT COUNT(*) FROM safenode_security_logs 
-- WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- =====================================================
-- 6. SCRIPT PARA EXECUTAR VIA CRON (PHP)
-- =====================================================

-- Use o arquivo api/archive-old-logs.php que já existe
-- Ele faz a mesma coisa mas via PHP, mais seguro para cron

-- =====================================================
-- NOTAS
-- =====================================================

-- 1. Esta versão NÃO usa particionamento
-- 2. Funciona em qualquer plano da Hostinger
-- 3. Índices são suficientes para boa performance
-- 4. Execute arquivamento regularmente (diário/semanal)
-- 5. Monitore o tamanho da tabela de arquivo



