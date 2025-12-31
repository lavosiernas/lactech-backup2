-- ============================================
-- SafeNode Survey - Adicionar Novas Perguntas
-- Perguntas adicionais para resultados mais ricos
-- ============================================

-- 1. Motivo da escolha de preço
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `price_reason` TEXT NULL COMMENT 'Por que escolheu essa faixa de preço' AFTER `price_willing`;

-- 2. Prioridade/Trade-off (opções: redução_tempo, automacao_total, menos_custo, facilidade_uso)
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `priority_choice` VARCHAR(100) NULL COMMENT 'Prioridade principal escolhida' AFTER `price_reason`;

-- 3. NPS - Net Promoter Score (0-10)
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `nps_score` INT(2) NULL COMMENT 'NPS - quanto recomendaria de 0 a 10' AFTER `priority_choice`;

-- 4. Ferramentas de infra/deploy atuais
ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `current_tools` TEXT NULL COMMENT 'Ferramentas que usa hoje para infra e deploy' AFTER `nps_score`;

-- Verificar se funcionou
-- DESCRIBE safenode_survey_responses;

