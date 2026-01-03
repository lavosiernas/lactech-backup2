-- ============================================================
-- ADICIONAR CAMPO LOGO NA TABELA FARMS (Versão Simples)
-- Execute este comando SQL diretamente no seu banco de dados
-- ============================================================

ALTER TABLE `farms` ADD COLUMN `logo_path` varchar(255) DEFAULT NULL COMMENT 'Caminho da logo da fazenda para relatórios' AFTER `email`;

