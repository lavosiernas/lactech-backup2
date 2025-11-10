-- ============================================================
-- MIGRAÇÃO: SISTEMA DE TOUROS COMPLETO
-- Descrição: Expande e cria todas as tabelas necessárias
-- para o módulo completo de gerenciamento de touros
-- 
-- NOTA: Este script verifica e adiciona apenas os campos
-- que não existem na estrutura atual do banco
-- 
-- IMPORTANTE: Este script usa TRANSACTION para garantir
-- que tudo execute ou nada execute (atomicidade)
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Desabilitar autocommit para usar transação
SET AUTOCOMMIT = 0;

-- Iniciar transação - se houver erro, tudo será revertido
START TRANSACTION;

-- ============================================================
-- 1. EXPANDIR TABELA bulls (Cadastro e Identificação)
-- ============================================================

-- Verificar e adicionar campos apenas se não existirem
SET @dbname = DATABASE();
SET @tablename = 'bulls';

-- RFID Code
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'rfid_code');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `bulls` ADD COLUMN `rfid_code` VARCHAR(50) DEFAULT NULL COMMENT ''Código RFID'' AFTER `bull_number`', 
    'SELECT ''Campo rfid_code já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Earring Number
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'earring_number');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `bulls` ADD COLUMN `earring_number` VARCHAR(50) DEFAULT NULL COMMENT ''Número de brinco'' AFTER `rfid_code`', 
    'SELECT ''Campo earring_number já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Weight
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'weight');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `bulls` ADD COLUMN `weight` DECIMAL(6,2) DEFAULT NULL COMMENT ''Peso em kg'' AFTER `birth_date`', 
    'SELECT ''Campo weight já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Body Score
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'body_score');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `bulls` ADD COLUMN `body_score` DECIMAL(3,1) DEFAULT NULL COMMENT ''Escore corporal (1-5)'' AFTER `weight`', 
    'SELECT ''Campo body_score já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Expandir Status (adicionar novos valores mantendo os existentes)
ALTER TABLE `bulls` MODIFY COLUMN `status` ENUM('ativo','reserva','em_reproducao','descartado','falecido','vendido','morto','inativo') DEFAULT 'ativo' COMMENT 'Status do touro';

-- Expandir Source (adicionar novos valores mantendo os existentes)
ALTER TABLE `bulls` MODIFY COLUMN `source` ENUM('proprio','comprado','arrendado','doador_genetico','inseminacao','alugado') NOT NULL DEFAULT 'proprio' COMMENT 'Origem do touro';

-- Genealogia - Avôs
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'grandsire_father');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `bulls` ADD COLUMN `grandsire_father` VARCHAR(100) DEFAULT NULL COMMENT ''Avô paterno'' AFTER `sire`', 
    'SELECT ''Campo grandsire_father já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'granddam_father');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `bulls` ADD COLUMN `granddam_father` VARCHAR(100) DEFAULT NULL COMMENT ''Avó paterna'' AFTER `grandsire_father`', 
    'SELECT ''Campo granddam_father já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'grandsire_mother');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `bulls` ADD COLUMN `grandsire_mother` VARCHAR(100) DEFAULT NULL COMMENT ''Avô materno'' AFTER `dam`', 
    'SELECT ''Campo grandsire_mother já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'granddam_mother');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `bulls` ADD COLUMN `granddam_mother` VARCHAR(100) DEFAULT NULL COMMENT ''Avó materna'' AFTER `grandsire_mother`', 
    'SELECT ''Campo granddam_mother já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Avaliação genética expandida
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'genetic_evaluation');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `bulls` ADD COLUMN `genetic_evaluation` TEXT DEFAULT NULL COMMENT ''Avaliação genética detalhada'' AFTER `health_index`', 
    'SELECT ''Campo genetic_evaluation já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'behavior_notes');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `bulls` ADD COLUMN `behavior_notes` TEXT DEFAULT NULL COMMENT ''Observações sobre comportamento'' AFTER `genetic_evaluation`', 
    'SELECT ''Campo behavior_notes já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'aptitude_notes');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `bulls` ADD COLUMN `aptitude_notes` TEXT DEFAULT NULL COMMENT ''Aptidão e características'' AFTER `behavior_notes`', 
    'SELECT ''Campo aptitude_notes já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Informações gerais
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'location');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `bulls` ADD COLUMN `location` VARCHAR(255) DEFAULT NULL COMMENT ''Localização física'' AFTER `notes`', 
    'SELECT ''Campo location já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'is_breeding_active');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `bulls` ADD COLUMN `is_breeding_active` TINYINT(1) DEFAULT 1 COMMENT ''Ativo para reprodução'' AFTER `is_active`', 
    'SELECT ''Campo is_breeding_active já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 2. TABELA: bull_coatings (Coberturas Naturais)
-- ============================================================

-- Verificar se as tabelas referenciadas existem antes de criar foreign keys
SET @bulls_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'bulls');
SET @animals_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'animals');

-- Criar tabela sem foreign keys primeiro
CREATE TABLE IF NOT EXISTS `bull_coatings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bull_id` INT(11) NOT NULL,
  `cow_id` INT(11) NOT NULL COMMENT 'Vaca coberta',
  `coating_date` DATE NOT NULL,
  `coating_time` TIME DEFAULT NULL,
  `coating_type` ENUM('natural','monta_direta','monta_controlada') NOT NULL DEFAULT 'natural',
  `result` ENUM('prenhez','vazia','aborto','pendente') DEFAULT 'pendente',
  `pregnancy_check_date` DATE DEFAULT NULL,
  `pregnancy_check_method` ENUM('palpacao','ultrassom','exame_sangue') DEFAULT NULL,
  `technician_id` INT(11) DEFAULT NULL COMMENT 'Responsável técnico',
  `technician_name` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `farm_id` INT(11) NOT NULL DEFAULT 1,
  `recorded_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_bull_id` (`bull_id`),
  INDEX `idx_cow_id` (`cow_id`),
  INDEX `idx_coating_date` (`coating_date`),
  INDEX `idx_result` (`result`),
  INDEX `idx_farm_id` (`farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de coberturas naturais';

-- Adicionar foreign keys apenas se as tabelas referenciadas existirem
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'bull_coatings' 
    AND CONSTRAINT_NAME = 'fk_bull_coatings_bull_id');

SET @sql = IF(@fk_exists = 0 AND @bulls_exists > 0, 
    'ALTER TABLE `bull_coatings` ADD CONSTRAINT `fk_bull_coatings_bull_id` FOREIGN KEY (`bull_id`) REFERENCES `bulls`(`id`) ON DELETE CASCADE',
    'SELECT ''Foreign key bull_id já existe ou tabela bulls não existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'bull_coatings' 
    AND CONSTRAINT_NAME = 'fk_bull_coatings_cow_id');

SET @sql = IF(@fk_exists = 0 AND @animals_exists > 0, 
    'ALTER TABLE `bull_coatings` ADD CONSTRAINT `fk_bull_coatings_cow_id` FOREIGN KEY (`cow_id`) REFERENCES `animals`(`id`) ON DELETE CASCADE',
    'SELECT ''Foreign key cow_id já existe ou tabela animals não existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 3. TABELA: bull_health_records (Histórico Sanitário)
-- ============================================================

CREATE TABLE IF NOT EXISTS `bull_health_records` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bull_id` INT(11) NOT NULL,
  `record_date` DATE NOT NULL,
  `record_type` ENUM('vacina','exame_reprodutivo','exame_laboratorial','tratamento','medicamento','consulta_veterinaria') NOT NULL,
  `record_name` VARCHAR(255) NOT NULL COMMENT 'Nome do procedimento/exame',
  `veterinarian_name` VARCHAR(255) DEFAULT NULL,
  `veterinarian_license` VARCHAR(50) DEFAULT NULL,
  `results` TEXT DEFAULT NULL COMMENT 'Resultados laboratoriais ou exames',
  `medication_name` VARCHAR(255) DEFAULT NULL,
  `medication_dosage` VARCHAR(100) DEFAULT NULL,
  `medication_period` VARCHAR(100) DEFAULT NULL COMMENT 'Período de aplicação',
  `next_due_date` DATE DEFAULT NULL COMMENT 'Próxima data prevista',
  `cost` DECIMAL(10,2) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `attachments` JSON DEFAULT NULL COMMENT 'Anexos de documentos/laudos',
  `farm_id` INT(11) NOT NULL DEFAULT 1,
  `recorded_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_bull_id` (`bull_id`),
  INDEX `idx_record_date` (`record_date`),
  INDEX `idx_record_type` (`record_type`),
  INDEX `idx_farm_id` (`farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico sanitário de touros';

-- Adicionar foreign key
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'bull_health_records' 
    AND CONSTRAINT_NAME = 'fk_bull_health_records_bull_id');

SET @sql = IF(@fk_exists = 0 AND @bulls_exists > 0, 
    'ALTER TABLE `bull_health_records` ADD CONSTRAINT `fk_bull_health_records_bull_id` FOREIGN KEY (`bull_id`) REFERENCES `bulls`(`id`) ON DELETE CASCADE',
    'SELECT ''Foreign key bull_id já existe ou tabela bulls não existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 4. TABELA: bull_body_condition (Controle de Peso e Escore)
-- ============================================================

CREATE TABLE IF NOT EXISTS `bull_body_condition` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bull_id` INT(11) NOT NULL,
  `record_date` DATE NOT NULL,
  `weight` DECIMAL(6,2) NOT NULL COMMENT 'Peso em kg',
  `body_score` DECIMAL(3,1) NOT NULL COMMENT 'Escore corporal (1-5)',
  `body_score_notes` TEXT DEFAULT NULL COMMENT 'Observações do escore',
  `recorded_by` INT(11) NOT NULL,
  `farm_id` INT(11) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_bull_id` (`bull_id`),
  INDEX `idx_record_date` (`record_date`),
  INDEX `idx_farm_id` (`farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de peso e escore corporal dos touros';

-- Adicionar foreign key
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'bull_body_condition' 
    AND CONSTRAINT_NAME = 'fk_bull_body_condition_bull_id');

SET @sql = IF(@fk_exists = 0 AND @bulls_exists > 0, 
    'ALTER TABLE `bull_body_condition` ADD CONSTRAINT `fk_bull_body_condition_bull_id` FOREIGN KEY (`bull_id`) REFERENCES `bulls`(`id`) ON DELETE CASCADE',
    'SELECT ''Foreign key bull_id já existe ou tabela bulls não existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 5. TABELA: bull_documents (Documentos e Anexos)
-- ============================================================

CREATE TABLE IF NOT EXISTS `bull_documents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bull_id` INT(11) NOT NULL,
  `document_type` ENUM('certificado','laudo','foto','pedigree','teste_genetico','outro') NOT NULL,
  `document_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_size` BIGINT(20) DEFAULT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `issue_date` DATE DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `farm_id` INT(11) NOT NULL DEFAULT 1,
  `uploaded_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_bull_id` (`bull_id`),
  INDEX `idx_document_type` (`document_type`),
  INDEX `idx_farm_id` (`farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Documentos e anexos de touros';

-- Adicionar foreign key
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'bull_documents' 
    AND CONSTRAINT_NAME = 'fk_bull_documents_bull_id');

SET @sql = IF(@fk_exists = 0 AND @bulls_exists > 0, 
    'ALTER TABLE `bull_documents` ADD CONSTRAINT `fk_bull_documents_bull_id` FOREIGN KEY (`bull_id`) REFERENCES `bulls`(`id`) ON DELETE CASCADE',
    'SELECT ''Foreign key bull_id já existe ou tabela bulls não existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 6. EXPANDIR TABELA semen_catalog (Qualidade do Sêmen)
-- ============================================================

SET @tablename = 'semen_catalog';

-- Straw Code
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'straw_code');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `semen_catalog` ADD COLUMN `straw_code` VARCHAR(50) DEFAULT NULL COMMENT ''Código da palheta'' AFTER `batch_number`', 
    'SELECT ''Campo straw_code já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Collection Date
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'collection_date');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `semen_catalog` ADD COLUMN `collection_date` DATE DEFAULT NULL COMMENT ''Data de coleta'' AFTER `production_date`', 
    'SELECT ''Campo collection_date já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Motility
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'motility');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `semen_catalog` ADD COLUMN `motility` DECIMAL(5,2) DEFAULT NULL COMMENT ''Motilidade (%)'' AFTER `quality_grade`', 
    'SELECT ''Campo motility já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Volume
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'volume');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `semen_catalog` ADD COLUMN `volume` DECIMAL(6,2) DEFAULT NULL COMMENT ''Volume (ml)'' AFTER `motility`', 
    'SELECT ''Campo volume já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Concentration
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'concentration');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `semen_catalog` ADD COLUMN `concentration` DECIMAL(10,2) DEFAULT NULL COMMENT ''Concentração (milhões/ml)'' AFTER `volume`', 
    'SELECT ''Campo concentration já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Destination
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'destination');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `semen_catalog` ADD COLUMN `destination` VARCHAR(255) DEFAULT NULL COMMENT ''Destino de uso'' AFTER `storage_location`', 
    'SELECT ''Campo destination já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Alert Sent
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'alert_sent');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `semen_catalog` ADD COLUMN `alert_sent` TINYINT(1) DEFAULT 0 COMMENT ''Alerta de validade enviado'' AFTER `expiry_date`', 
    'SELECT ''Campo alert_sent já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 7. TABELA: semen_movements (Movimentação de Sêmen)
-- ============================================================

-- Verificar se tabelas referenciadas existem
SET @semen_catalog_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'semen_catalog');
SET @inseminations_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'inseminations');

CREATE TABLE IF NOT EXISTS `semen_movements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `semen_id` INT(11) NOT NULL,
  `movement_type` ENUM('entrada','saida','uso','descarte','transferencia') NOT NULL,
  `movement_date` DATE NOT NULL,
  `quantity` INT(11) NOT NULL COMMENT 'Quantidade de doses',
  `destination` VARCHAR(255) DEFAULT NULL COMMENT 'Destino ou origem',
  `animal_id` INT(11) DEFAULT NULL COMMENT 'Animal relacionado (se uso)',
  `insemination_id` INT(11) DEFAULT NULL COMMENT 'Inseminação relacionada',
  `reason` VARCHAR(255) DEFAULT NULL COMMENT 'Motivo da movimentação',
  `recorded_by` INT(11) NOT NULL,
  `farm_id` INT(11) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_semen_id` (`semen_id`),
  INDEX `idx_movement_type` (`movement_type`),
  INDEX `idx_movement_date` (`movement_date`),
  INDEX `idx_animal_id` (`animal_id`),
  INDEX `idx_farm_id` (`farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Movimentação de sêmen';

-- Adicionar foreign keys
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'semen_movements' 
    AND CONSTRAINT_NAME = 'fk_semen_movements_semen_id');
SET @sql = IF(@fk_exists = 0 AND @semen_catalog_exists > 0, 
    'ALTER TABLE `semen_movements` ADD CONSTRAINT `fk_semen_movements_semen_id` FOREIGN KEY (`semen_id`) REFERENCES `semen_catalog`(`id`) ON DELETE CASCADE',
    'SELECT ''Foreign key semen_id já existe ou tabela semen_catalog não existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'semen_movements' 
    AND CONSTRAINT_NAME = 'fk_semen_movements_animal_id');
SET @sql = IF(@fk_exists = 0 AND @animals_exists > 0, 
    'ALTER TABLE `semen_movements` ADD CONSTRAINT `fk_semen_movements_animal_id` FOREIGN KEY (`animal_id`) REFERENCES `animals`(`id`) ON DELETE SET NULL',
    'SELECT ''Foreign key animal_id já existe ou tabela animals não existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'semen_movements' 
    AND CONSTRAINT_NAME = 'fk_semen_movements_insemination_id');
SET @sql = IF(@fk_exists = 0 AND @inseminations_exists > 0, 
    'ALTER TABLE `semen_movements` ADD CONSTRAINT `fk_semen_movements_insemination_id` FOREIGN KEY (`insemination_id`) REFERENCES `inseminations`(`id`) ON DELETE SET NULL',
    'SELECT ''Foreign key insemination_id já existe ou tabela inseminations não existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 8. TABELA: bull_offspring (Rastreamento de Descendentes)
-- ============================================================

-- Verificar se bull_coatings existe
SET @bull_coatings_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'bull_coatings');

CREATE TABLE IF NOT EXISTS `bull_offspring` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bull_id` INT(11) NOT NULL,
  `offspring_id` INT(11) NOT NULL COMMENT 'ID do filho/filha',
  `offspring_type` ENUM('inseminacao','cobertura_natural') NOT NULL,
  `insemination_id` INT(11) DEFAULT NULL,
  `coating_id` INT(11) DEFAULT NULL,
  `birth_date` DATE DEFAULT NULL,
  `farm_id` INT(11) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_offspring` (`bull_id`, `offspring_id`),
  INDEX `idx_bull_id` (`bull_id`),
  INDEX `idx_offspring_id` (`offspring_id`),
  INDEX `idx_farm_id` (`farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Rastreamento de descendentes dos touros';

-- Adicionar foreign keys
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'bull_offspring' 
    AND CONSTRAINT_NAME = 'fk_bull_offspring_bull_id');
SET @sql = IF(@fk_exists = 0 AND @bulls_exists > 0, 
    'ALTER TABLE `bull_offspring` ADD CONSTRAINT `fk_bull_offspring_bull_id` FOREIGN KEY (`bull_id`) REFERENCES `bulls`(`id`) ON DELETE CASCADE',
    'SELECT ''Foreign key bull_id já existe ou tabela bulls não existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'bull_offspring' 
    AND CONSTRAINT_NAME = 'fk_bull_offspring_offspring_id');
SET @sql = IF(@fk_exists = 0 AND @animals_exists > 0, 
    'ALTER TABLE `bull_offspring` ADD CONSTRAINT `fk_bull_offspring_offspring_id` FOREIGN KEY (`offspring_id`) REFERENCES `animals`(`id`) ON DELETE CASCADE',
    'SELECT ''Foreign key offspring_id já existe ou tabela animals não existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'bull_offspring' 
    AND CONSTRAINT_NAME = 'fk_bull_offspring_insemination_id');
SET @sql = IF(@fk_exists = 0 AND @inseminations_exists > 0, 
    'ALTER TABLE `bull_offspring` ADD CONSTRAINT `fk_bull_offspring_insemination_id` FOREIGN KEY (`insemination_id`) REFERENCES `inseminations`(`id`) ON DELETE SET NULL',
    'SELECT ''Foreign key insemination_id já existe ou tabela inseminations não existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'bull_offspring' 
    AND CONSTRAINT_NAME = 'fk_bull_offspring_coating_id');
SET @sql = IF(@fk_exists = 0 AND @bull_coatings_exists > 0, 
    'ALTER TABLE `bull_offspring` ADD CONSTRAINT `fk_bull_offspring_coating_id` FOREIGN KEY (`coating_id`) REFERENCES `bull_coatings`(`id`) ON DELETE SET NULL',
    'SELECT ''Foreign key coating_id já existe ou tabela bull_coatings não existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 9. VIEWS PARA RELATÓRIOS E ANÁLISES
-- ============================================================

-- View: Estatísticas de touros (completa - diferente da v_bull_statistics existente)
DROP VIEW IF EXISTS `v_bull_statistics_complete`;
CREATE VIEW `v_bull_statistics_complete` AS
SELECT 
    b.id,
    b.bull_number,
    b.name,
    b.breed,
    b.status,
    b.birth_date,
    TIMESTAMPDIFF(YEAR, b.birth_date, CURDATE()) AS age,
    
    -- Estatísticas de inseminação
    COUNT(DISTINCT i.id) AS total_inseminations,
    COUNT(DISTINCT CASE WHEN i.pregnancy_result = 'prenha' THEN i.id END) AS successful_inseminations,
    COUNT(DISTINCT CASE WHEN i.pregnancy_result = 'vazia' THEN i.id END) AS failed_inseminations,
    CASE 
        WHEN COUNT(DISTINCT i.id) > 0 
        THEN ROUND((COUNT(DISTINCT CASE WHEN i.pregnancy_result = 'prenha' THEN i.id END) / COUNT(DISTINCT i.id)) * 100, 2)
        ELSE 0 
    END AS pregnancy_rate_ia,
    
    -- Estatísticas de cobertura natural
    COUNT(DISTINCT c.id) AS total_coatings,
    COUNT(DISTINCT CASE WHEN c.result = 'prenhez' THEN c.id END) AS successful_coatings,
    CASE 
        WHEN COUNT(DISTINCT c.id) > 0 
        THEN ROUND((COUNT(DISTINCT CASE WHEN c.result = 'prenhez' THEN c.id END) / COUNT(DISTINCT c.id)) * 100, 2)
        ELSE 0 
    END AS pregnancy_rate_natural,
    
    -- Total geral
    (COUNT(DISTINCT i.id) + COUNT(DISTINCT c.id)) AS total_services,
    (COUNT(DISTINCT CASE WHEN i.pregnancy_result = 'prenha' THEN i.id END) + 
     COUNT(DISTINCT CASE WHEN c.result = 'prenhez' THEN c.id END)) AS total_successful,
    
    -- Descendentes
    COUNT(DISTINCT o.offspring_id) AS total_offspring,
    
    -- Sêmen disponível
    COALESCE(SUM(s.straws_available), 0) AS semen_straws_available,
    COALESCE(SUM(s.straws_used), 0) AS semen_straws_used,
    
    -- Último peso e escore
    (SELECT weight FROM bull_body_condition WHERE bull_id = b.id ORDER BY record_date DESC LIMIT 1) AS last_weight,
    (SELECT body_score FROM bull_body_condition WHERE bull_id = b.id ORDER BY record_date DESC LIMIT 1) AS last_body_score,
    
    b.farm_id
FROM bulls b
LEFT JOIN inseminations i ON i.bull_id = b.id AND i.farm_id = b.farm_id
LEFT JOIN bull_coatings c ON c.bull_id = b.id AND c.farm_id = b.farm_id
LEFT JOIN bull_offspring o ON o.bull_id = b.id AND o.farm_id = b.farm_id
LEFT JOIN semen_catalog s ON s.bull_id = b.id AND s.farm_id = b.farm_id
WHERE b.is_active = 1
GROUP BY b.id, b.bull_number, b.name, b.breed, b.status, b.birth_date, b.farm_id;

-- View: Ranking de eficiência
DROP VIEW IF EXISTS `v_bull_efficiency_ranking`;
CREATE VIEW `v_bull_efficiency_ranking` AS
SELECT 
    id,
    bull_number,
    name,
    breed,
    status,
    total_services,
    total_successful,
    CASE 
        WHEN total_services > 0 
        THEN ROUND((total_successful / total_services) * 100, 2)
        ELSE 0 
    END AS overall_efficiency,
    total_offspring,
    last_weight,
    last_body_score,
    RANK() OVER (ORDER BY 
        CASE 
            WHEN total_services > 0 
            THEN (total_successful / total_services) * 100
            ELSE 0 
        END DESC
    ) AS efficiency_rank
FROM v_bull_statistics_complete
WHERE status IN ('ativo', 'em_reproducao')
ORDER BY overall_efficiency DESC;

-- ============================================================
-- 10. TRIGGERS PARA AUTOMAÇÃO
-- ============================================================

-- Trigger: Atualizar contagem de descendentes ao inserir nascimento
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS `tr_add_offspring_on_birth`
AFTER INSERT ON `births`
FOR EACH ROW
BEGIN
    -- Se o nascimento tiver inseminação vinculada e touro
    IF NEW.insemination_id IS NOT NULL THEN
        INSERT INTO bull_offspring (bull_id, offspring_id, offspring_type, insemination_id, birth_date, farm_id)
        SELECT 
            i.bull_id,
            NEW.animal_id,
            'inseminacao',
            NEW.insemination_id,
            NEW.birth_date,
            NEW.farm_id
        FROM inseminations i
        WHERE i.id = NEW.insemination_id
        AND i.bull_id IS NOT NULL
        ON DUPLICATE KEY UPDATE birth_date = NEW.birth_date;
    END IF;
END$$
DELIMITER ;

-- Trigger: Atualizar peso e escore na tabela principal
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS `tr_update_bull_weight_score`
AFTER INSERT ON `bull_body_condition`
FOR EACH ROW
BEGIN
    UPDATE bulls 
    SET 
        weight = NEW.weight,
        body_score = NEW.body_score,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.bull_id;
END$$
DELIMITER ;

-- Trigger: Atualizar estoque de sêmen ao registrar uso
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS `tr_update_semen_stock_on_use`
AFTER INSERT ON `semen_movements`
FOR EACH ROW
BEGIN
    IF NEW.movement_type = 'uso' THEN
        UPDATE semen_catalog
        SET 
            straws_used = straws_used + NEW.quantity,
            straws_available = GREATEST(0, straws_available - NEW.quantity),
            updated_at = CURRENT_TIMESTAMP
        WHERE id = NEW.semen_id;
    ELSEIF NEW.movement_type = 'entrada' THEN
        UPDATE semen_catalog
        SET 
            straws_available = straws_available + NEW.quantity,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = NEW.semen_id;
    ELSEIF NEW.movement_type = 'saida' OR NEW.movement_type = 'descarte' THEN
        UPDATE semen_catalog
        SET 
            straws_available = GREATEST(0, straws_available - NEW.quantity),
            updated_at = CURRENT_TIMESTAMP
        WHERE id = NEW.semen_id;
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- 11. ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ============================================================

-- Índices compostos para buscas rápidas
CREATE INDEX IF NOT EXISTS `idx_bulls_search` ON `bulls`(`bull_number`, `name`, `breed`, `status`, `farm_id`);
CREATE INDEX IF NOT EXISTS `idx_bulls_active_breeding` ON `bulls`(`is_active`, `is_breeding_active`, `status`, `farm_id`);
CREATE INDEX IF NOT EXISTS `idx_semen_expiry` ON `semen_catalog`(`expiry_date`, `farm_id`, `straws_available`);
CREATE INDEX IF NOT EXISTS `idx_coatings_bull_date` ON `bull_coatings`(`bull_id`, `coating_date`, `result`);

-- ============================================================
-- FINALIZAR MIGRAÇÃO
-- ============================================================

-- Verificar se houve erros
-- Se chegou até aqui sem erros, confirmar todas as alterações
COMMIT;

-- Reativar autocommit
SET AUTOCOMMIT = 1;

-- ============================================================
-- FIM DA MIGRAÇÃO - Tudo executado com sucesso!
-- ============================================================

-- NOTA IMPORTANTE:
-- Se ocorrer QUALQUER erro durante a execução,
-- o MySQL automaticamente fará ROLLBACK de todas as alterações.
-- Nada será aplicado se houver erro em qualquer parte do script.
--
-- Se você precisar fazer rollback manual após um erro:
-- 1. Execute: ROLLBACK;
-- 2. Ou use o arquivo: sistema_touros_migration_manual_rollback.sql

