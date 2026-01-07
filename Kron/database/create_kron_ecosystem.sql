-- =====================================================
-- BANCO DE DADOS KRON ECOSYSTEM
-- Sistema de conexão cross-domain entre sistemas
-- =====================================================

-- Usar banco de dados existente
-- IMPORTANTE: Altere o nome do banco se necessário
-- Para produção: USE `u311882628_kron`;
-- Para local: USE `kron`;
USE `u311882628_kron`;

-- =====================================================
-- TABELAS PRINCIPAIS
-- =====================================================

-- Tabela de usuários KRON
CREATE TABLE IF NOT EXISTS `kron_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NULL COMMENT 'NULL se login apenas via Google',
  `name` VARCHAR(255) NOT NULL,
  `google_id` VARCHAR(255) NULL UNIQUE COMMENT 'ID único do Google OAuth',
  `avatar_url` VARCHAR(500) NULL COMMENT 'URL da foto de perfil do Google',
  `email_verified` TINYINT(1) DEFAULT 0,
  `email_verified_at` TIMESTAMP NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de sessões de usuários
CREATE TABLE IF NOT EXISTS `kron_user_sessions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `session_token` VARCHAR(64) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `device_type` VARCHAR(50) DEFAULT 'unknown' COMMENT 'desktop, mobile, tablet',
  `browser` VARCHAR(100) DEFAULT NULL,
  `os` VARCHAR(100) DEFAULT NULL,
  `is_current` TINYINT(1) DEFAULT 0,
  `last_activity` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_last_activity` (`last_activity`),
  FOREIGN KEY (`user_id`) REFERENCES `kron_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de tokens temporários de conexão
CREATE TABLE IF NOT EXISTS `kron_connection_tokens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `kron_user_id` INT(11) NOT NULL,
  `system_name` ENUM('safenode', 'lactech') NOT NULL,
  `status` ENUM('pending', 'used', 'expired') DEFAULT 'pending',
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `used_at` TIMESTAMP NULL,
  `system_user_id` INT(11) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_user` (`kron_user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expires` (`expires_at`),
  FOREIGN KEY (`kron_user_id`) REFERENCES `kron_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de conexões estabelecidas
CREATE TABLE IF NOT EXISTS `kron_user_connections` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kron_user_id` INT(11) NOT NULL,
  `system_name` ENUM('safenode', 'lactech') NOT NULL,
  `system_user_id` INT(11) NOT NULL,
  `system_user_email` VARCHAR(255) NOT NULL,
  `connection_token` VARCHAR(255) NOT NULL COMMENT 'Token permanente JWT',
  `connected_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_sync_at` TIMESTAMP NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_connection` (`kron_user_id`, `system_name`, `system_user_id`),
  KEY `idx_kron_user` (`kron_user_id`),
  KEY `idx_system` (`system_name`, `system_user_id`),
  FOREIGN KEY (`kron_user_id`) REFERENCES `kron_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs de tentativas de conexão
CREATE TABLE IF NOT EXISTS `kron_connection_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(64) NULL,
  `system_name` ENUM('safenode', 'lactech') NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `status` ENUM('success', 'failed', 'expired', 'invalid') NOT NULL,
  `error_message` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de notificações unificadas
CREATE TABLE IF NOT EXISTS `kron_notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kron_user_id` INT(11) NOT NULL,
  `system_name` ENUM('safenode', 'lactech', 'kron') DEFAULT 'kron',
  `type` VARCHAR(50) NOT NULL COMMENT 'connection_success, connection_failed, system_alert, etc',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` TIMESTAMP NULL,
  `action_url` VARCHAR(500) NULL COMMENT 'URL para ação relacionada',
  `metadata` TEXT NULL COMMENT 'Dados adicionais em JSON',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`kron_user_id`),
  KEY `idx_read` (`is_read`),
  KEY `idx_created` (`created_at`),
  KEY `idx_user_read` (`kron_user_id`, `is_read`),
  FOREIGN KEY (`kron_user_id`) REFERENCES `kron_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de analytics agregados
CREATE TABLE IF NOT EXISTS `kron_analytics` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kron_user_id` INT(11) NULL COMMENT 'NULL para estatísticas globais',
  `system_name` ENUM('safenode', 'lactech', 'all') NOT NULL,
  `metric_date` DATE NOT NULL,
  `metric_type` VARCHAR(50) NOT NULL COMMENT 'requests, production, threats_blocked, etc',
  `metric_value` DECIMAL(15,2) DEFAULT 0,
  `metadata` TEXT NULL COMMENT 'Dados adicionais em JSON',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_metric` (`kron_user_id`, `system_name`, `metric_date`, `metric_type`),
  KEY `idx_user` (`kron_user_id`),
  KEY `idx_system` (`system_name`),
  KEY `idx_date` (`metric_date`),
  KEY `idx_type` (`metric_type`),
  FOREIGN KEY (`kron_user_id`) REFERENCES `kron_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TRIGGERS
-- =====================================================

DELIMITER $$

-- Trigger para atualizar last_sync_at quando conexão é reativada
CREATE TRIGGER IF NOT EXISTS `tr_update_connection_sync` 
BEFORE UPDATE ON `kron_user_connections`
FOR EACH ROW
BEGIN
    IF NEW.is_active = 1 AND (OLD.is_active = 0 OR OLD.last_sync_at IS NULL) THEN
        SET NEW.last_sync_at = NOW();
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- VIEWS ÚTEIS
-- =====================================================

-- View de usuários com estatísticas de conexões
CREATE OR REPLACE VIEW `v_kron_users_stats` AS
SELECT 
    u.id,
    u.email,
    u.name,
    u.is_active,
    u.created_at,
    u.last_login,
    COUNT(DISTINCT c.id) as total_connections,
    COUNT(DISTINCT CASE WHEN c.is_active = 1 THEN c.id END) as active_connections,
    COUNT(DISTINCT s.id) as active_sessions
FROM `kron_users` u
LEFT JOIN `kron_user_connections` c ON u.id = c.kron_user_id
LEFT JOIN `kron_user_sessions` s ON u.id = s.user_id AND s.expires_at > NOW()
GROUP BY u.id;

-- View de tokens pendentes válidos
CREATE OR REPLACE VIEW `v_kron_valid_tokens` AS
SELECT 
    t.id,
    t.token,
    t.kron_user_id,
    u.email as kron_user_email,
    u.name as kron_user_name,
    t.system_name,
    t.expires_at,
    TIMESTAMPDIFF(SECOND, NOW(), t.expires_at) as seconds_remaining
FROM `kron_connection_tokens` t
INNER JOIN `kron_users` u ON t.kron_user_id = u.id
WHERE t.status = 'pending'
AND t.expires_at > NOW();

-- View de conexões ativas por sistema
CREATE OR REPLACE VIEW `v_kron_active_connections` AS
SELECT 
    c.id,
    c.kron_user_id,
    u.email as kron_user_email,
    u.name as kron_user_name,
    c.system_name,
    c.system_user_id,
    c.system_user_email,
    c.connected_at,
    c.last_sync_at,
    DATEDIFF(NOW(), c.connected_at) as days_connected
FROM `kron_user_connections` c
INNER JOIN `kron_users` u ON c.kron_user_id = u.id
WHERE c.is_active = 1
ORDER BY c.connected_at DESC;

-- View de notificações não lidas por usuário
CREATE OR REPLACE VIEW `v_kron_unread_notifications` AS
SELECT 
    n.id,
    n.kron_user_id,
    n.system_name,
    n.type,
    n.title,
    n.message,
    n.action_url,
    n.created_at,
    TIMESTAMPDIFF(HOUR, n.created_at, NOW()) as hours_ago
FROM `kron_notifications` n
WHERE n.is_read = 0
ORDER BY n.created_at DESC;

-- View de analytics agregados por sistema
CREATE OR REPLACE VIEW `v_kron_analytics_summary` AS
SELECT 
    system_name,
    metric_type,
    DATE(metric_date) as date,
    SUM(metric_value) as total_value,
    AVG(metric_value) as avg_value,
    MAX(metric_value) as max_value,
    MIN(metric_value) as min_value,
    COUNT(*) as record_count
FROM `kron_analytics`
WHERE metric_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY system_name, metric_type, DATE(metric_date)
ORDER BY metric_date DESC, system_name, metric_type;

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

DELIMITER $$

-- Procedure para limpar tokens expirados
CREATE PROCEDURE IF NOT EXISTS `sp_cleanup_expired_tokens`()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    -- Marcar tokens expirados
    UPDATE `kron_connection_tokens`
    SET `status` = 'expired'
    WHERE `status` = 'pending'
    AND `expires_at` < NOW();
    
    -- Deletar tokens expirados com mais de 7 dias
    DELETE FROM `kron_connection_tokens`
    WHERE `status` = 'expired'
    AND `expires_at` < DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    COMMIT;
    
    SELECT CONCAT('Limpeza concluída. ', ROW_COUNT(), ' tokens processados.') AS resultado;
END$$

-- Procedure para limpar sessões expiradas
CREATE PROCEDURE IF NOT EXISTS `sp_cleanup_expired_sessions`()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    -- Deletar sessões expiradas
    DELETE FROM `kron_user_sessions`
    WHERE `expires_at` < NOW();
    
    COMMIT;
    
    SELECT CONCAT('Limpeza concluída. ', ROW_COUNT(), ' sessões removidas.') AS resultado;
END$$

-- Procedure para limpar logs antigos
CREATE PROCEDURE IF NOT EXISTS `sp_cleanup_old_logs`(IN `days_to_keep` INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    -- Deletar logs com mais de X dias
    DELETE FROM `kron_connection_logs`
    WHERE `created_at` < DATE_SUB(NOW(), INTERVAL `days_to_keep` DAY);
    
    COMMIT;
    
    SELECT CONCAT('Limpeza concluída. ', ROW_COUNT(), ' logs removidos.') AS resultado;
END$$

-- Procedure para obter estatísticas do sistema
CREATE PROCEDURE IF NOT EXISTS `sp_get_system_stats`()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM `kron_users`) as total_users,
        (SELECT COUNT(*) FROM `kron_users` WHERE is_active = 1) as active_users,
        (SELECT COUNT(*) FROM `kron_user_connections` WHERE is_active = 1) as active_connections,
        (SELECT COUNT(*) FROM `kron_user_sessions` WHERE expires_at > NOW()) as active_sessions,
        (SELECT COUNT(*) FROM `kron_connection_tokens` WHERE status = 'pending' AND expires_at > NOW()) as pending_tokens,
        (SELECT COUNT(*) FROM `kron_connection_logs` WHERE DATE(created_at) = CURDATE()) as today_logs,
        (SELECT COUNT(*) FROM `kron_notifications` WHERE is_read = 0) as unread_notifications;
END$$

-- Procedure para limpar notificações antigas
CREATE PROCEDURE IF NOT EXISTS `sp_cleanup_old_notifications`(IN `days_to_keep` INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    -- Deletar notificações lidas com mais de X dias
    DELETE FROM `kron_notifications`
    WHERE `is_read` = 1
    AND `read_at` < DATE_SUB(NOW(), INTERVAL `days_to_keep` DAY);
    
    COMMIT;
    
    SELECT CONCAT('Limpeza concluída. ', ROW_COUNT(), ' notificações removidas.') AS resultado;
END$$

DELIMITER ;

-- =====================================================
-- EVENTOS (LIMPEZA AUTOMÁTICA)
-- =====================================================

-- NOTA: Para habilitar eventos, execute como administrador:
-- SET GLOBAL event_scheduler = ON;
-- O usuário precisa ter privilégio SUPER para isso.

-- Evento para limpar tokens expirados a cada hora
CREATE EVENT IF NOT EXISTS `ev_cleanup_expired_tokens`
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
  CALL `sp_cleanup_expired_tokens`();

-- Evento para limpar sessões expiradas a cada hora
CREATE EVENT IF NOT EXISTS `ev_cleanup_expired_sessions`
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
  CALL `sp_cleanup_expired_sessions`();

-- Evento para limpar logs antigos (manter apenas 90 dias)
CREATE EVENT IF NOT EXISTS `ev_cleanup_old_logs`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 2 HOUR
DO
  CALL `sp_cleanup_old_logs`(90);

-- Evento para limpar notificações antigas (manter apenas 30 dias)
CREATE EVENT IF NOT EXISTS `ev_cleanup_old_notifications`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 3 HOUR
DO
  CALL `sp_cleanup_old_notifications`(30);

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================

-- Índices compostos para consultas frequentes
ALTER TABLE `kron_user_connections` 
ADD INDEX `idx_user_system_active` (`kron_user_id`, `system_name`, `is_active`);

ALTER TABLE `kron_connection_tokens` 
ADD INDEX `idx_user_system_status` (`kron_user_id`, `system_name`, `status`);

ALTER TABLE `kron_user_sessions` 
ADD INDEX `idx_user_expires` (`user_id`, `expires_at`);

ALTER TABLE `kron_notifications` 
ADD INDEX `idx_user_created` (`kron_user_id`, `created_at`);

ALTER TABLE `kron_analytics` 
ADD INDEX `idx_system_date_type` (`system_name`, `metric_date`, `metric_type`);

-- =====================================================
-- DADOS INICIAIS (OPCIONAL)
-- =====================================================

-- Usuário administrador padrão (senha: admin123)
-- IMPORTANTE: Alterar a senha após primeiro login!
INSERT IGNORE INTO `kron_users` (`email`, `password`, `name`, `is_active`, `email_verified`, `email_verified_at`) 
VALUES (
    'admin@kronx.sbs',
    '$2y$10$98zWMIufXE/lFi5t07.Wc.x0G86AaTsN9mzpMGbhUX0WIqKVtv/qi', -- senha: admin123
    'Administrador KRON',
    1,
    1,
    NOW()
);
