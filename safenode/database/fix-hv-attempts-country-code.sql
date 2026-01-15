-- Adicionar campo country_code à tabela safenode_hv_attempts
-- Este campo é usado para estatísticas geográficas

ALTER TABLE `safenode_hv_attempts` 
ADD COLUMN `country_code` CHAR(2) DEFAULT NULL AFTER `referer`;

-- Adicionar índice para melhorar performance em consultas por país
ALTER TABLE `safenode_hv_attempts`
ADD KEY `idx_country_code` (`country_code`);

