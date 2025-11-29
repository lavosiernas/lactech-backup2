-- Script para corrigir a view v_safenode_active_blocks
-- Remove o definer problemático e recria a view

-- Remover a view se existir
DROP VIEW IF EXISTS `v_safenode_active_blocks`;

-- Recriar a view sem definer específico (usará o usuário atual)
CREATE VIEW `v_safenode_active_blocks` AS 
SELECT 
    `safenode_blocked_ips`.`ip_address` AS `ip_address`, 
    `safenode_blocked_ips`.`reason` AS `reason`, 
    `safenode_blocked_ips`.`threat_type` AS `threat_type`, 
    `safenode_blocked_ips`.`created_at` AS `blocked_at`, 
    `safenode_blocked_ips`.`expires_at` AS `expires_at`, 
    TIMESTAMPDIFF(SECOND, CURRENT_TIMESTAMP(), `safenode_blocked_ips`.`expires_at`) AS `seconds_remaining` 
FROM `safenode_blocked_ips` 
WHERE `safenode_blocked_ips`.`is_active` = 1 
    AND (`safenode_blocked_ips`.`expires_at` IS NULL OR `safenode_blocked_ips`.`expires_at` > CURRENT_TIMESTAMP());
