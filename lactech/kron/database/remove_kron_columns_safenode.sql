-- =====================================================
-- REMOVER COLUNAS KRON DO BANCO SAFENODE
-- Versão compatível com Hostinger
-- Banco: u311882628_safend (ajuste se necessário)
-- Tabela: safenode_users
-- =====================================================

-- IMPORTANTE: Altere o nome do banco se necessário
USE `u311882628_safend`;

-- Remover índice primeiro (se existir)
ALTER TABLE `safenode_users` 
DROP INDEX IF EXISTS `idx_kron_user`;

-- Remover colunas de conexão KRON
-- Execute cada ALTER TABLE separadamente
-- Se der erro "Unknown column", significa que a coluna não existe (pode ignorar)

ALTER TABLE `safenode_users` 
DROP COLUMN IF EXISTS `kron_connected_at`;

ALTER TABLE `safenode_users` 
DROP COLUMN IF EXISTS `kron_connection_token`;

ALTER TABLE `safenode_users` 
DROP COLUMN IF EXISTS `kron_user_id`;


