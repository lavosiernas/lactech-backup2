-- =====================================================
-- ADICIONAR COLUNAS KRON NO BANCO SAFENODE
-- Banco: safend (ou u311882628_safend na Hostinger)
-- Tabela: safenode_users
-- =====================================================

-- IMPORTANTE: Altere o nome do banco se necessário
-- Para produção Hostinger: USE `u311882628_safend`;
-- Para local: USE `safend`;
USE `safend`;

-- Adicionar colunas de conexão KRON na tabela safenode_users
-- Se a coluna já existir, o erro será ignorado (pode executar múltiplas vezes)

-- Adicionar coluna kron_user_id
ALTER TABLE `safenode_users` 
ADD COLUMN IF NOT EXISTS `kron_user_id` INT(11) NULL COMMENT 'ID do usuário no sistema KRON' AFTER `id`;

-- Adicionar coluna kron_connection_token
ALTER TABLE `safenode_users` 
ADD COLUMN IF NOT EXISTS `kron_connection_token` VARCHAR(255) NULL COMMENT 'Token permanente de conexão com KRON' AFTER `kron_user_id`;

-- Adicionar coluna kron_connected_at
ALTER TABLE `safenode_users` 
ADD COLUMN IF NOT EXISTS `kron_connected_at` TIMESTAMP NULL COMMENT 'Data de conexão com KRON' AFTER `kron_connection_token`;

-- Adicionar índice (se não existir)
CREATE INDEX IF NOT EXISTS `idx_kron_user` ON `safenode_users` (`kron_user_id`);

