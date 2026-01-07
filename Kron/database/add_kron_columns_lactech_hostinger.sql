-- =====================================================
-- ADICIONAR COLUNAS KRON NO BANCO LACTECH
-- Versão compatível com Hostinger
-- Banco: u311882628_lactech_lgmato (ajuste se necessário)
-- Tabela: users
-- =====================================================

-- IMPORTANTE: Altere o nome do banco se necessário
USE `u311882628_lactech_lgmato`;

-- Adicionar colunas de conexão KRON na tabela users
-- Execute cada ALTER TABLE separadamente
-- Se der erro "Duplicate column name", significa que a coluna já existe (pode ignorar)

ALTER TABLE `users` 
ADD COLUMN `kron_user_id` INT(11) NULL COMMENT 'ID do usuário no sistema KRON' AFTER `id`;

ALTER TABLE `users` 
ADD COLUMN `kron_connection_token` VARCHAR(255) NULL COMMENT 'Token permanente de conexão com KRON' AFTER `kron_user_id`;

ALTER TABLE `users` 
ADD COLUMN `kron_connected_at` TIMESTAMP NULL COMMENT 'Data de conexão com KRON' AFTER `kron_connection_token`;

-- Adicionar índice
ALTER TABLE `users` 
ADD KEY `idx_kron_user` (`kron_user_id`);

