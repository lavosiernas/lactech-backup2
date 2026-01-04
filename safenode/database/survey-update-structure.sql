-- ============================================
-- SafeNode Survey - Atualizar estrutura para novas perguntas
-- ============================================
-- Este script atualiza a tabela safenode_survey_responses
-- para incluir os novos campos das perguntas detalhadas
-- ============================================

-- Adicionar novos campos do perfil do dev
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN IF NOT EXISTS `dev_level` VARCHAR(50) NULL COMMENT 'Estudante, Júnior, Pleno, Sênior, Tech Lead, Fundador' AFTER `email`,
ADD COLUMN IF NOT EXISTS `work_type` VARCHAR(50) NULL COMMENT 'Solo/Freelancer, Startup, Média, Grande' AFTER `dev_level`,
ADD COLUMN IF NOT EXISTS `main_stack` VARCHAR(100) NULL COMMENT 'Stack principal usado' AFTER `work_type`,
ADD COLUMN IF NOT EXISTS `main_stack_other` VARCHAR(255) NULL COMMENT 'Outra stack (se selecionado Outra)' AFTER `main_stack`;

-- Adicionar campos de dor real
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN IF NOT EXISTS `pain_points` TEXT NULL COMMENT 'JSON array com até 3 pontos de dor selecionados' AFTER `main_stack_other`,
ADD COLUMN IF NOT EXISTS `time_wasted_per_week` VARCHAR(50) NULL COMMENT 'Tempo perdido por semana com infra' AFTER `pain_points`;

-- Adicionar campos de validação SafeNode
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN IF NOT EXISTS `platform_help` VARCHAR(50) NULL COMMENT 'Muito, Um pouco, Não vejo valor' AFTER `time_wasted_per_week`,
ADD COLUMN IF NOT EXISTS `first_feature` VARCHAR(100) NULL COMMENT 'Feature que usaria primeiro' AFTER `platform_help`,
ADD COLUMN IF NOT EXISTS `use_ai_analysis` VARCHAR(50) NULL COMMENT 'Sim, Talvez, Não confio' AFTER `first_feature`;

-- Adicionar campos de preço
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN IF NOT EXISTS `price_willing` VARCHAR(50) NULL COMMENT 'Faixa de preço que estaria disposto a pagar' AFTER `use_ai_analysis`;

-- Adicionar campos de uso profissional
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN IF NOT EXISTS `use_in_production` VARCHAR(50) NULL COMMENT 'Sim, Talvez, Não' AFTER `price_willing`,
ADD COLUMN IF NOT EXISTS `recommend_to_team` VARCHAR(50) NULL COMMENT 'Sim, Não' AFTER `use_in_production`,
ADD COLUMN IF NOT EXISTS `decision_maker` VARCHAR(50) NULL COMMENT 'Eu, Time, Empresa, Diretoria' AFTER `recommend_to_team`;

-- Adicionar campos de fechamento
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN IF NOT EXISTS `switch_reasons` TEXT NULL COMMENT 'O que faria trocar a stack atual' AFTER `decision_maker`,
ADD COLUMN IF NOT EXISTS `must_have_features` TEXT NULL COMMENT 'O que não pode faltar' AFTER `switch_reasons`;

-- Remover campos antigos que não são mais usados (comentados para segurança)
-- ALTER TABLE `safenode_survey_responses` DROP COLUMN `uses_hosting`;
-- ALTER TABLE `safenode_survey_responses` DROP COLUMN `hosting_type`;
-- ALTER TABLE `safenode_survey_responses` DROP COLUMN `biggest_pain`;
-- ALTER TABLE `safenode_survey_responses` DROP COLUMN `pays_for_email`;
-- ALTER TABLE `safenode_survey_responses` DROP COLUMN `would_pay_integration`;
-- ALTER TABLE `safenode_survey_responses` DROP COLUMN `wants_beta`;
-- ALTER TABLE `safenode_survey_responses` DROP COLUMN `additional_info`;

-- Ou renomear para manter compatibilidade (melhor opção)
ALTER TABLE `safenode_survey_responses` 
CHANGE COLUMN `uses_hosting` `uses_hosting_old` VARCHAR(50) NULL COMMENT 'DEPRECATED',
CHANGE COLUMN `biggest_pain` `biggest_pain_old` TEXT NULL COMMENT 'DEPRECATED',
CHANGE COLUMN `pays_for_email` `pays_for_email_old` VARCHAR(50) NULL COMMENT 'DEPRECATED',
CHANGE COLUMN `would_pay_integration` `would_pay_integration_old` VARCHAR(50) NULL COMMENT 'DEPRECATED',
CHANGE COLUMN `wants_beta` `wants_beta_old` TINYINT(1) NULL COMMENT 'DEPRECATED',
CHANGE COLUMN `additional_info` `additional_info_old` TEXT NULL COMMENT 'DEPRECATED';






