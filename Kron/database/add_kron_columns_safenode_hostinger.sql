-- =====================================================
-- ADICIONAR COLUNAS KRON NO BANCO SAFENODE
-- Versão compatível com Hostinger
-- Banco: u311882628_safend (ajuste se necessário)
-- Tabela: safenode_users
-- =====================================================

-- IMPORTANTE: Altere o nome do banco se necessário
USE `u311882628_safend`;

-- Adicionar colunas de conexão KRON na tabela safenode_users
-- Execute cada ALTER TABLE separadamente
-- Se der erro "Duplicate column name", significa que a coluna já existe (pode ignorar)

ALTER TABLE `safenode_users` 
ADD COLUMN `kron_user_id` INT(11) NULL COMMENT 'ID do usuário no sistema KRON' AFTER `id`;

ALTER TABLE `safenode_users` 
ADD COLUMN `kron_connection_token` VARCHAR(255) NULL COMMENT 'Token permanente de conexão com KRON' AFTER `kron_user_id`;

ALTER TABLE `safenode_users` 
ADD COLUMN `kron_connected_at` TIMESTAMP NULL COMMENT 'Data de conexão com KRON' AFTER `kron_connection_token`;

-- Adicionar índice
ALTER TABLE `safenode_users` 
ADD KEY `idx_kron_user` (`kron_user_id`);

