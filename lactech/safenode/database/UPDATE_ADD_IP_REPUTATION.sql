-- =====================================================
-- SafeNode - Atualização do Banco de Dados
-- Adiciona tabela de Reputação de IPs (Sistema Independente)
-- Mantém todos os dados existentes
-- =====================================================

-- Verificar se a tabela já existe antes de criar
CREATE TABLE IF NOT EXISTS `safenode_ip_reputation` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL UNIQUE,
  `trust_score` INT(11) DEFAULT 50 COMMENT '0-100, 0=muito suspeito, 100=muito confiável',
  `total_requests` INT(11) DEFAULT 0,
  `blocked_requests` INT(11) DEFAULT 0,
  `allowed_requests` INT(11) DEFAULT 0,
  `challenged_requests` INT(11) DEFAULT 0,
  `first_seen` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `last_seen` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `country_code` CHAR(2) DEFAULT NULL,
  `is_whitelisted` TINYINT(1) DEFAULT 0,
  `is_blacklisted` TINYINT(1) DEFAULT 0,
  `threat_score_avg` DECIMAL(5,2) DEFAULT 0.00,
  `threat_score_max` INT(11) DEFAULT 0,
  `last_threat_type` VARCHAR(50) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ip` (`ip_address`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_trust_score` (`trust_score`),
  KEY `idx_last_seen` (`last_seen`),
  KEY `idx_country` (`country_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar coluna confidence_score na tabela safenode_security_logs se não existir
-- (Para futuras melhorias no sistema de scoring)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'safenode_security_logs' 
    AND COLUMN_NAME = 'confidence_score'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `safenode_security_logs` ADD COLUMN `confidence_score` INT(11) DEFAULT NULL COMMENT ''Score de confiança (0-100)'' AFTER `threat_score`',
    'SELECT "Coluna confidence_score já existe" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice para melhor performance nas consultas de reputação
-- Verificar se o índice já existe
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'safenode_security_logs' 
    AND INDEX_NAME = 'idx_ip_created'
);

SET @sql = IF(@index_exists = 0,
    'ALTER TABLE `safenode_security_logs` ADD INDEX `idx_ip_created` (`ip_address`, `created_at`)',
    'SELECT "Índice idx_ip_created já existe" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- NOTA: Todos os dados existentes são preservados
-- Usuários, sites, logs, configurações - tudo mantido
-- =====================================================


