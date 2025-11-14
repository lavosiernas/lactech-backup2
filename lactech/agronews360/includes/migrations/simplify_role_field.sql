-- ==========================================
-- MIGRATION: Simplificar campo role
-- ==========================================
-- Execute este script se já rodou o create_agronews_tables_clean.sql
-- Como todos os usuários são tratados igualmente no AgroNews,
-- vamos simplificar o campo role para apenas 'viewer'

-- Primeiro, atualizar todos os registros existentes para 'viewer'
UPDATE `users` SET `role` = 'viewer' WHERE `role` != 'viewer';

-- Converter ENUM para VARCHAR (mais flexível e simples)
ALTER TABLE `users` MODIFY `role` VARCHAR(20) DEFAULT 'viewer';

-- ==========================================
-- FIM DA MIGRATION
-- ==========================================

