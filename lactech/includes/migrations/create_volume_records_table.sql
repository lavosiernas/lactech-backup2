-- Criação da tabela volume_records
-- Esta tabela armazena os registros de volume de leite coletado na fazenda

-- Verificar se a tabela já existe antes de criar
DROP TABLE IF EXISTS `volume_records`;

CREATE TABLE `volume_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_date` date NOT NULL COMMENT 'Data do registro',
  `shift` enum('manha','tarde','noite') NOT NULL COMMENT 'Turno da coleta',
  `total_volume` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Volume total coletado (litros)',
  `total_animals` int(11) DEFAULT 0 COMMENT 'Número de animais ordenhados',
  `average_per_animal` decimal(10,2) DEFAULT NULL COMMENT 'Média por animal (litros)',
  `notes` text DEFAULT NULL COMMENT 'Observações sobre a coleta',
  `recorded_by` int(11) DEFAULT NULL COMMENT 'ID do usuário que registrou',
  `farm_id` int(11) NOT NULL DEFAULT 1 COMMENT 'ID da fazenda',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização',
  PRIMARY KEY (`id`),
  KEY `idx_farm_id` (`farm_id`),
  KEY `idx_record_date` (`record_date`),
  KEY `idx_shift` (`shift`),
  KEY `idx_recorded_by` (`recorded_by`),
  KEY `idx_farm_date` (`farm_id`, `record_date`),
  CONSTRAINT `fk_volume_records_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_volume_records_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registros de volume de leite coletado na fazenda';

