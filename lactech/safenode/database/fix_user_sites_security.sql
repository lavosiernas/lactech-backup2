-- ============================================================
-- SafeNode - Correção Crítica de Segurança
-- Adicionar user_id à tabela safenode_sites
-- ============================================================

-- Passo 1: Adicionar coluna user_id
ALTER TABLE `safenode_sites` 
ADD COLUMN `user_id` int(11) NOT NULL AFTER `id`;

-- Passo 2: Adicionar índice para performance
ALTER TABLE `safenode_sites`
ADD KEY `idx_user_id` (`user_id`);

-- Passo 3: Adicionar foreign key (opcional, comentado por segurança)
-- ALTER TABLE `safenode_sites`
-- ADD CONSTRAINT `fk_sites_user` 
-- FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE;

-- ============================================================
-- IMPORTANTE: Depois de executar este SQL, você precisa:
-- 1. Atualizar manualmente os sites existentes com o user_id correto
-- 2. Atualizar os arquivos PHP para sempre filtrar por user_id
-- ============================================================

-- Exemplo para atualizar sites existentes (ajuste conforme necessário):
-- UPDATE safenode_sites SET user_id = 2 WHERE id = 2; -- denfy.vercel.app pertence ao user 2


