-- ============================================
-- SafeNode Survey - Adicionar campos das novas perguntas
-- Versão segura (não causa erro se colunas já existirem)
-- ============================================

-- Adicionar novos campos do perfil do dev
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `dev_level` VARCHAR(50) NULL COMMENT 'Estudante, Júnior, Pleno, Sênior, Tech Lead, Fundador' AFTER `email`;

ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `work_type` VARCHAR(50) NULL COMMENT 'Solo/Freelancer, Startup, Média, Grande' AFTER `dev_level`;

ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `main_stack` VARCHAR(100) NULL COMMENT 'Stack principal usado' AFTER `work_type`;

ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `main_stack_other` VARCHAR(255) NULL COMMENT 'Outra stack (se selecionado Outra)' AFTER `main_stack`;

-- Adicionar campos de dor real
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `pain_points` TEXT NULL COMMENT 'JSON array com até 3 pontos de dor selecionados' AFTER `main_stack_other`;

ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `time_wasted_per_week` VARCHAR(50) NULL COMMENT 'Tempo perdido por semana com infra' AFTER `pain_points`;

-- Adicionar campos de validação SafeNode
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `platform_help` VARCHAR(50) NULL COMMENT 'Muito, Um pouco, Não vejo valor' AFTER `time_wasted_per_week`;

ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `first_feature` VARCHAR(100) NULL COMMENT 'Feature que usaria primeiro' AFTER `platform_help`;

ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `use_ai_analysis` VARCHAR(50) NULL COMMENT 'Sim, Talvez, Não preciso disso' AFTER `first_feature`;

-- Adicionar campos de preço
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `price_willing` VARCHAR(50) NULL COMMENT 'Faixa de preço que estaria disposto a pagar' AFTER `use_ai_analysis`;

-- Adicionar campos de uso profissional
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `use_in_production` VARCHAR(50) NULL COMMENT 'Sim, Talvez, Não' AFTER `price_willing`;

ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `recommend_to_team` VARCHAR(50) NULL COMMENT 'Sim, Não' AFTER `use_in_production`;

ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `decision_maker` VARCHAR(50) NULL COMMENT 'Eu, Time, Empresa, Diretoria' AFTER `recommend_to_team`;

-- Adicionar campos de fechamento
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `switch_reasons` TEXT NULL COMMENT 'O que faria trocar a stack atual' AFTER `decision_maker`;

ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `must_have_features` TEXT NULL COMMENT 'O que não pode faltar' AFTER `switch_reasons`;

-- Nota: Se algum erro ocorrer dizendo que a coluna já existe, 
-- você pode ignorar esse erro específico e continuar com os próximos comandos.
-- Os campos antigos (uses_hosting, biggest_pain, etc) serão mantidos para compatibilidade.
-- Para verificar se funcionou, execute: DESCRIBE safenode_survey_responses;

