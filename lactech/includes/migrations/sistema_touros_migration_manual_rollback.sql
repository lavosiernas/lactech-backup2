-- ============================================================
-- ROLLBACK MANUAL - Sistema de Touros
-- Use este arquivo APENAS se a migração falhar e você
-- precisar reverter as alterações manualmente
-- ============================================================

-- ATENÇÃO: Este script remove apenas as estruturas criadas
-- pela migração. Ele NÃO remove dados existentes das tabelas originais.

SET AUTOCOMMIT = 0;
START TRANSACTION;

-- Remover triggers
DROP TRIGGER IF EXISTS `tr_add_offspring_on_birth`;
DROP TRIGGER IF EXISTS `tr_update_bull_weight_score`;
DROP TRIGGER IF EXISTS `tr_update_semen_stock_on_use`;

-- Remover views
DROP VIEW IF EXISTS `v_bull_efficiency_ranking`;
DROP VIEW IF EXISTS `v_bull_statistics_complete`;

-- Remover índices criados
DROP INDEX IF EXISTS `idx_coatings_bull_date` ON `bull_coatings`;
DROP INDEX IF EXISTS `idx_semen_expiry` ON `semen_catalog`;
DROP INDEX IF EXISTS `idx_bulls_active_breeding` ON `bulls`;
DROP INDEX IF EXISTS `idx_bulls_search` ON `bulls`;

-- Remover tabelas criadas (CUIDADO: Remove dados também!)
DROP TABLE IF EXISTS `bull_offspring`;
DROP TABLE IF EXISTS `semen_movements`;
DROP TABLE IF EXISTS `bull_documents`;
DROP TABLE IF EXISTS `bull_body_condition`;
DROP TABLE IF EXISTS `bull_health_records`;
DROP TABLE IF EXISTS `bull_coatings`;

-- Reverter alterações na tabela semen_catalog
-- (Nota: Não é possível remover colunas automaticamente sem perder dados)
-- Se precisar remover campos específicos, faça manualmente:
-- ALTER TABLE semen_catalog DROP COLUMN alert_sent;
-- ALTER TABLE semen_catalog DROP COLUMN destination;
-- ALTER TABLE semen_catalog DROP COLUMN concentration;
-- ALTER TABLE semen_catalog DROP COLUMN volume;
-- ALTER TABLE semen_catalog DROP COLUMN motility;
-- ALTER TABLE semen_catalog DROP COLUMN collection_date;
-- ALTER TABLE semen_catalog DROP COLUMN straw_code;

-- Reverter alterações na tabela bulls
-- (Nota: Não é possível remover colunas automaticamente sem perder dados)
-- Se precisar remover campos específicos, faça manualmente:
-- ALTER TABLE bulls DROP COLUMN is_breeding_active;
-- ALTER TABLE bulls DROP COLUMN location;
-- ALTER TABLE bulls DROP COLUMN aptitude_notes;
-- ALTER TABLE bulls DROP COLUMN behavior_notes;
-- ALTER TABLE bulls DROP COLUMN genetic_evaluation;
-- ALTER TABLE bulls DROP COLUMN granddam_mother;
-- ALTER TABLE bulls DROP COLUMN grandsire_mother;
-- ALTER TABLE bulls DROP COLUMN granddam_father;
-- ALTER TABLE bulls DROP COLUMN grandsire_father;
-- ALTER TABLE bulls DROP COLUMN body_score;
-- ALTER TABLE bulls DROP COLUMN weight;
-- ALTER TABLE bulls DROP COLUMN earring_number;
-- ALTER TABLE bulls DROP COLUMN rfid_code;

COMMIT;
SET AUTOCOMMIT = 1;

-- ============================================================
-- FIM DO ROLLBACK
-- ============================================================






