-- ============================================================
-- SafeNode - Adicionar user_id à tabela safenode_sites
-- IMPORTANTE: Execute este SQL no banco u311882628_safend
-- ============================================================

-- Adicionar coluna user_id (permite NULL temporariamente)
ALTER TABLE `safenode_sites` 
ADD COLUMN `user_id` INT(11) NULL AFTER `id`;

-- Adicionar índice para melhor performance
ALTER TABLE `safenode_sites`
ADD INDEX `idx_user_id` (`user_id`);

-- ============================================================
-- DEPOIS DE EXECUTAR, VOCÊ PRECISA:
-- 1. Associar cada site existente ao seu dono correto
-- 2. Depois que todos os sites tiverem user_id, execute:
--    ALTER TABLE safenode_sites MODIFY user_id INT(11) NOT NULL;
-- ============================================================

-- Para verificar os sites existentes:
-- SELECT id, domain, display_name, user_id FROM safenode_sites;

-- Para verificar os usuários:
-- SELECT id, username, email FROM safenode_users;

-- Exemplo de como associar um site ao usuário:
-- UPDATE safenode_sites SET user_id = 2 WHERE id = 2;

