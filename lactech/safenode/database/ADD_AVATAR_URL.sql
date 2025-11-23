-- =====================================================
-- ADD AVATAR URL COLUMN TO SAFENODE_USERS
-- =====================================================
-- Adiciona coluna para armazenar URL do avatar/foto do usuário
-- Usado para login com Google (foto do perfil do Google)
-- =====================================================

ALTER TABLE `safenode_users` 
ADD COLUMN `avatar_url` VARCHAR(500) NULL AFTER `google_id`,
ADD COLUMN `avatar_updated_at` TIMESTAMP NULL DEFAULT NULL AFTER `avatar_url`;

-- Adicionar índice para melhorar performance em buscas
ALTER TABLE `safenode_users`
ADD INDEX `idx_avatar` (`avatar_url`(255));


