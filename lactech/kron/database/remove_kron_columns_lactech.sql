-- =====================================================
-- REMOVER COLUNAS KRON DO BANCO LACTECH
-- Versão compatível com Hostinger
-- Banco: u311882628_lactech_lgmato (ajuste se necessário)
-- Tabela: users
-- =====================================================

-- IMPORTANTE: Altere o nome do banco se necessário
USE `u311882628_lactech_lgmato`;

-- Remover índice primeiro (se existir)
ALTER TABLE `users` 
DROP INDEX IF EXISTS `idx_kron_user`;

-- Remover colunas de conexão KRON
-- Execute cada ALTER TABLE separadamente
-- Se der erro "Unknown column", significa que a coluna não existe (pode ignorar)

ALTER TABLE `users` 
DROP COLUMN IF EXISTS `kron_connected_at`;

ALTER TABLE `users` 
DROP COLUMN IF EXISTS `kron_connection_token`;

ALTER TABLE `users` 
DROP COLUMN IF EXISTS `kron_user_id`;


