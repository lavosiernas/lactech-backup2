-- =====================================================
-- REMOVER COLUNAS KRON DO BANCO LACTECH
-- Versão simples (sem IF EXISTS)
-- Banco: lactech (ajuste se necessário)
-- Tabela: users
-- =====================================================

-- IMPORTANTE: Altere o nome do banco se necessário
USE `lactech`;

-- Remover índice primeiro
ALTER TABLE `users` 
DROP INDEX `idx_kron_user`;

-- Remover colunas de conexão KRON
ALTER TABLE `users` 
DROP COLUMN `kron_connected_at`,
DROP COLUMN `kron_connection_token`,
DROP COLUMN `kron_user_id`;


