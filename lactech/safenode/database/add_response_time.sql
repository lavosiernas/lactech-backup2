-- Adicionar coluna response_time na tabela safenode_security_logs
-- Execute este script se a coluna não existir

ALTER TABLE `safenode_security_logs` 
ADD COLUMN IF NOT EXISTS `response_time` DECIMAL(10,2) NULL COMMENT 'Tempo de resposta em milissegundos' AFTER `referer`;

-- Criar índice para melhor performance nas consultas de latência
CREATE INDEX IF NOT EXISTS `idx_response_time` ON `safenode_security_logs` (`response_time`, `created_at`);

-- Criar tabela para violações de rate limit (se não existir)
CREATE TABLE IF NOT EXISTS `safenode_rate_limits_violations` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `ip_address` VARCHAR(45) NOT NULL,
    `rate_limit_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip` (`ip_address`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

