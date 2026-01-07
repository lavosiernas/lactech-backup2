-- =====================================================
-- ADICIONAR COLUNAS KRON NO BANCO LACTECH
-- Banco: lactech_lgmato (ou u311882628_lactech_lgmato na Hostinger)
-- Tabela: users
-- =====================================================

-- IMPORTANTE: Altere o nome do banco se necessário
-- Para produção Hostinger: USE `u311882628_lactech_lgmato`;
-- Para local: USE `lactech_lgmato`;
USE `lactech_lgmato`;

-- Adicionar colunas de conexão KRON na tabela users
-- Se a coluna já existir, o erro será ignorado (pode executar múltiplas vezes)

-- Adicionar coluna kron_user_id
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `kron_user_id` INT(11) NULL COMMENT 'ID do usuário no sistema KRON' AFTER `id`;

-- Adicionar coluna kron_connection_token
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `kron_connection_token` VARCHAR(255) NULL COMMENT 'Token permanente de conexão com KRON' AFTER `kron_user_id`;

-- Adicionar coluna kron_connected_at
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `kron_connected_at` TIMESTAMP NULL COMMENT 'Data de conexão com KRON' AFTER `kron_connection_token`;

-- Adicionar índice (se não existir)
CREATE INDEX IF NOT EXISTS `idx_kron_user` ON `users` (`kron_user_id`);

