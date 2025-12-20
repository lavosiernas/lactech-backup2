-- =====================================================
-- REMOVER COLUNAS KRON DO BANCO SAFENODE
-- Versão simples (sem IF EXISTS)
-- Banco: safend (ajuste se necessário)
-- Tabela: safenode_users
-- =====================================================

-- IMPORTANTE: Altere o nome do banco se necessário
USE `safend`;

-- Remover índice primeiro
ALTER TABLE `safenode_users` 
DROP INDEX `idx_kron_user`;

-- Remover colunas de conexão KRON
ALTER TABLE `safenode_users` 
DROP COLUMN `kron_connected_at`,
DROP COLUMN `kron_connection_token`,
DROP COLUMN `kron_user_id`;


