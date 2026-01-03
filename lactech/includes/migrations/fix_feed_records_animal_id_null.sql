-- ============================================================
-- CORREÇÃO: Permitir NULL na coluna animal_id da tabela feed_records
-- Execute este arquivo para corrigir o erro ao salvar registros por lote
-- ============================================================

ALTER TABLE `feed_records` MODIFY COLUMN `animal_id` int(11) DEFAULT NULL COMMENT 'ID do animal (se registro individual)';

