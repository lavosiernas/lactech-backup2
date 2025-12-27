-- ============================================================
-- Script para corrigir a tabela safenode_mail_projects
-- Adiciona a coluna email_function que está faltando
-- ============================================================

-- IMPORTANTE: Execute este script no seu banco de dados MySQL/MariaDB

-- 1. Adicionar coluna email_function se não existir
-- Para MySQL 8.0.19+, use:
ALTER TABLE `safenode_mail_projects` 
ADD COLUMN IF NOT EXISTS `email_function` varchar(50) DEFAULT NULL 
COMMENT 'Função do e-mail (confirm_signup, invite_user, magic_link, etc)' 
AFTER `rate_limit_per_minute`;

-- Para versões anteriores do MySQL, remova o IF NOT EXISTS e execute manualmente:
-- ALTER TABLE `safenode_mail_projects` 
-- ADD COLUMN `email_function` varchar(50) DEFAULT NULL 
-- COMMENT 'Função do e-mail (confirm_signup, invite_user, magic_link, etc)' 
-- AFTER `rate_limit_per_minute`;

-- 2. Garantir que a coluna id tem AUTO_INCREMENT
ALTER TABLE `safenode_mail_projects` 
MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT;

-- ============================================================
-- Verificação da estrutura (execute separadamente para verificar)
-- ============================================================
-- DESCRIBE safenode_mail_projects;
-- SHOW CREATE TABLE safenode_mail_projects;

-- ============================================================
-- Verificar se todas as tabelas necessárias existem:
-- ============================================================
-- SELECT TABLE_NAME 
-- FROM information_schema.TABLES 
-- WHERE TABLE_SCHEMA = DATABASE() 
-- AND TABLE_NAME LIKE 'safenode_mail_%';

-- Tabelas esperadas:
-- - safenode_mail_projects ✓
-- - safenode_mail_templates ✓
-- - safenode_mail_logs ✓
-- - safenode_mail_rate_limits ✓
-- - safenode_mail_webhooks ✓
-- - safenode_mail_analytics ✓
