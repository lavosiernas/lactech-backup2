-- =====================================================
-- SISTEMA COMPLETO FINAL - LACTECH
-- Banco de dados completo para o sistema
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- ESTRUTURA COMPLETA DO BANCO
-- =====================================================

-- 1. FAZENDAS
CREATE TABLE `farms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. USUÁRIOS (Proprietário, Gerente, Funcionários)
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('proprietario','gerente','funcionario','veterinario') NOT NULL DEFAULT 'funcionario',
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `cpf` varchar(14) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `password_change_required` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `farm_id` (`farm_id`),
  KEY `role` (`role`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ANIMAIS (Rebanho completo)
CREATE TABLE `animals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_number` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `breed` varchar(100) NOT NULL,
  `gender` enum('femea','macho') NOT NULL,
  `birth_date` date NOT NULL,
  `birth_weight` decimal(6,2) DEFAULT NULL,
  `father_id` int(11) DEFAULT NULL,
  `mother_id` int(11) DEFAULT NULL,
  `status` enum('Lactante','Seco','Novilha','Vaca','Bezerra','Bezerro','Touro') NOT NULL DEFAULT 'Bezerra',
  `health_status` enum('saudavel','doente','tratamento','quarentena') NOT NULL DEFAULT 'saudavel',
  `reproductive_status` enum('vazia','prenha','lactante','seca','outros') DEFAULT 'vazia',
  `entry_date` date DEFAULT NULL,
  `exit_date` date DEFAULT NULL,
  `exit_reason` varchar(255) DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `animal_number` (`animal_number`),
  KEY `farm_id` (`farm_id`),
  KEY `father_id` (`father_id`),
  KEY `mother_id` (`mother_id`),
  KEY `breed` (`breed`),
  KEY `gender` (`gender`),
  KEY `status` (`status`),
  KEY `birth_date` (`birth_date`),
  CONSTRAINT `animals_ibfk_1` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `animals_ibfk_2` FOREIGN KEY (`father_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `animals_ibfk_3` FOREIGN KEY (`mother_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TOUROS (Para reprodução)
CREATE TABLE `bulls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bull_number` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `breed` varchar(100) NOT NULL,
  `birth_date` date NOT NULL,
  `source` enum('proprio','alugado','comprado','inseminacao') NOT NULL DEFAULT 'inseminacao',
  `genetic_value` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bull_number` (`bull_number`),
  KEY `farm_id` (`farm_id`),
  KEY `breed` (`breed`),
  CONSTRAINT `bulls_ibfk_1` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. PRODUÇÃO DE LEITE
CREATE TABLE `milk_production` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `production_date` date NOT NULL,
  `shift` enum('manha','tarde','noite') NOT NULL,
  `volume` decimal(8,2) NOT NULL,
  `quality_score` decimal(3,1) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `fat_content` decimal(4,2) DEFAULT NULL,
  `protein_content` decimal(4,2) DEFAULT NULL,
  `somatic_cells` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `animal_id` (`animal_id`),
  KEY `production_date` (`production_date`),
  KEY `recorded_by` (`recorded_by`),
  KEY `farm_id` (`farm_id`),
  KEY `shift` (`shift`),
  CONSTRAINT `milk_production_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `milk_production_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `milk_production_ibfk_3` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. REGISTROS DE SAÚDE
CREATE TABLE `health_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `record_type` enum('Medicamento','Vacinação','Vermifugação','Suplementação','Cirurgia','Consulta','Outros') NOT NULL,
  `description` text NOT NULL,
  `medication` varchar(255) DEFAULT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `next_date` date DEFAULT NULL,
  `veterinarian` varchar(255) DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `animal_id` (`animal_id`),
  KEY `record_date` (`record_date`),
  KEY `record_type` (`record_type`),
  KEY `recorded_by` (`recorded_by`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `health_records_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `health_records_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `health_records_ibfk_3` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. MEDICAMENTOS
CREATE TABLE `medications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('antibiotico','antiinflamatorio','vitamina','vermifugo','vacina','outros') NOT NULL,
  `description` text DEFAULT NULL,
  `unit` enum('ml','mg','g','unidade','dose') NOT NULL DEFAULT 'ml',
  `stock_quantity` decimal(10,2) DEFAULT 0,
  `min_stock` decimal(10,2) DEFAULT 0,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `farm_id` (`farm_id`),
  KEY `type` (`type`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `medications_ibfk_1` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. APLICAÇÕES DE MEDICAMENTOS
CREATE TABLE `medication_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `medication_id` int(11) NOT NULL,
  `application_date` date NOT NULL,
  `quantity` decimal(8,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `applied_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `animal_id` (`animal_id`),
  KEY `medication_id` (`medication_id`),
  KEY `application_date` (`application_date`),
  KEY `applied_by` (`applied_by`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `medication_applications_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `medication_applications_ibfk_2` FOREIGN KEY (`medication_id`) REFERENCES `medications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `medication_applications_ibfk_3` FOREIGN KEY (`applied_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `medication_applications_ibfk_4` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. REPRODUÇÃO E INSEMINAÇÃO
CREATE TABLE `inseminations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `bull_id` int(11) DEFAULT NULL,
  `insemination_date` date NOT NULL,
  `insemination_type` enum('natural','inseminacao_artificial','transferencia_embriao') NOT NULL DEFAULT 'inseminacao_artificial',
  `technician` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `animal_id` (`animal_id`),
  KEY `bull_id` (`bull_id`),
  KEY `insemination_date` (`insemination_date`),
  KEY `recorded_by` (`recorded_by`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `inseminations_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inseminations_ibfk_2` FOREIGN KEY (`bull_id`) REFERENCES `bulls` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inseminations_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inseminations_ibfk_4` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. CONTROLE DE PREGNÊNCIA
CREATE TABLE `pregnancy_controls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `insemination_id` int(11) DEFAULT NULL,
  `pregnancy_date` date NOT NULL,
  `expected_birth` date NOT NULL,
  `pregnancy_stage` enum('inicial','meio','final','pre-parto') NOT NULL DEFAULT 'inicial',
  `ultrasound_date` date DEFAULT NULL,
  `ultrasound_result` enum('positivo','negativo','indefinido') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `animal_id` (`animal_id`),
  KEY `insemination_id` (`insemination_id`),
  KEY `pregnancy_date` (`pregnancy_date`),
  KEY `expected_birth` (`expected_birth`),
  KEY `recorded_by` (`recorded_by`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `pregnancy_controls_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pregnancy_controls_ibfk_2` FOREIGN KEY (`insemination_id`) REFERENCES `inseminations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pregnancy_controls_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pregnancy_controls_ibfk_4` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. PARTO E NASCIMENTOS
CREATE TABLE `births` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `pregnancy_id` int(11) DEFAULT NULL,
  `birth_date` date NOT NULL,
  `birth_time` time DEFAULT NULL,
  `birth_type` enum('normal','cesariana','assistido','complicado') NOT NULL DEFAULT 'normal',
  `calf_number` varchar(50) DEFAULT NULL,
  `calf_gender` enum('femea','macho') DEFAULT NULL,
  `calf_weight` decimal(6,2) DEFAULT NULL,
  `calf_breed` varchar(100) DEFAULT NULL,
  `mother_status` enum('boa','problemas','obito') NOT NULL DEFAULT 'boa',
  `calf_status` enum('vivo','morto','deformado') NOT NULL DEFAULT 'vivo',
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `animal_id` (`animal_id`),
  KEY `pregnancy_id` (`pregnancy_id`),
  KEY `birth_date` (`birth_date`),
  KEY `recorded_by` (`recorded_by`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `births_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `births_ibfk_2` FOREIGN KEY (`pregnancy_id`) REFERENCES `pregnancy_controls` (`id`) ON DELETE SET NULL,
  CONSTRAINT `births_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `births_ibfk_4` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. LACTAÇÃO
CREATE TABLE `lactations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `birth_id` int(11) DEFAULT NULL,
  `lactation_start` date NOT NULL,
  `lactation_end` date DEFAULT NULL,
  `total_volume` decimal(10,2) DEFAULT 0,
  `average_daily` decimal(8,2) DEFAULT 0,
  `peak_day` int(11) DEFAULT NULL,
  `peak_volume` decimal(8,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `animal_id` (`animal_id`),
  KEY `birth_id` (`birth_id`),
  KEY `lactation_start` (`lactation_start`),
  KEY `recorded_by` (`recorded_by`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `lactations_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lactations_ibfk_2` FOREIGN KEY (`birth_id`) REFERENCES `births` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lactations_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lactations_ibfk_4` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. CICLOS DE CIOS
CREATE TABLE `heat_cycles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `heat_date` date NOT NULL,
  `heat_intensity` enum('leve','moderado','forte') NOT NULL DEFAULT 'moderado',
  `insemination_planned` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `animal_id` (`animal_id`),
  KEY `heat_date` (`heat_date`),
  KEY `recorded_by` (`recorded_by`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `heat_cycles_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `heat_cycles_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `heat_cycles_ibfk_3` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. REGISTROS FINANCEIROS
CREATE TABLE `financial_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_date` date NOT NULL,
  `type` enum('receita','despesa') NOT NULL,
  `category` varchar(100) NOT NULL,
  `subcategory` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('dinheiro','cartao','transferencia','cheque','pix') DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `related_animal_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `record_date` (`record_date`),
  KEY `type` (`type`),
  KEY `category` (`category`),
  KEY `related_animal_id` (`related_animal_id`),
  KEY `created_by` (`created_by`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `financial_records_ibfk_1` FOREIGN KEY (`related_animal_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `financial_records_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `financial_records_ibfk_3` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. TESTES DE QUALIDADE
CREATE TABLE `quality_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_date` date NOT NULL,
  `test_type` enum('qualidade_leite','sangue','urina','fezes','outros') NOT NULL,
  `animal_id` int(11) DEFAULT NULL,
  `fat_content` decimal(4,2) DEFAULT NULL,
  `protein_content` decimal(4,2) DEFAULT NULL,
  `somatic_cells` int(11) DEFAULT NULL,
  `bacteria_count` int(11) DEFAULT NULL,
  `antibiotics` enum('negativo','positivo','indefinido') DEFAULT NULL,
  `other_results` text DEFAULT NULL,
  `laboratory` varchar(255) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `test_date` (`test_date`),
  KEY `test_type` (`test_type`),
  KEY `animal_id` (`animal_id`),
  KEY `recorded_by` (`recorded_by`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `quality_tests_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quality_tests_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quality_tests_ibfk_3` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. PROGRAMAS DE VACINAÇÃO
CREATE TABLE `vaccination_programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `target_age_min` int(11) DEFAULT NULL,
  `target_age_max` int(11) DEFAULT NULL,
  `frequency_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `farm_id` (`farm_id`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `vaccination_programs_ibfk_1` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. REGISTROS DE VOLUME (Histórico)
CREATE TABLE `volume_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_date` date NOT NULL,
  `shift` enum('manha','tarde','noite') NOT NULL,
  `total_volume` decimal(10,2) NOT NULL,
  `total_animals` int(11) NOT NULL,
  `average_per_animal` decimal(8,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `record_date` (`record_date`),
  KEY `shift` (`shift`),
  KEY `recorded_by` (`recorded_by`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `volume_records_ibfk_1` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `volume_records_ibfk_2` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. ALERTAS DE SAÚDE
CREATE TABLE `health_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `alert_type` enum('vacina','vermifugo','medicamento','consulta','parto','outros') NOT NULL,
  `alert_date` date NOT NULL,
  `alert_message` text NOT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_date` date DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `animal_id` (`animal_id`),
  KEY `alert_date` (`alert_date`),
  KEY `alert_type` (`alert_type`),
  KEY `is_resolved` (`is_resolved`),
  KEY `created_by` (`created_by`),
  KEY `resolved_by` (`resolved_by`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `health_alerts_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `health_alerts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `health_alerts_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `health_alerts_ibfk_4` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. ALERTAS DE MATERNIDADE
CREATE TABLE `maternity_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `pregnancy_id` int(11) DEFAULT NULL,
  `alert_date` date NOT NULL,
  `expected_birth` date NOT NULL,
  `days_to_birth` int(11) NOT NULL,
  `alert_message` text NOT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `animal_id` (`animal_id`),
  KEY `pregnancy_id` (`pregnancy_id`),
  KEY `alert_date` (`alert_date`),
  KEY `expected_birth` (`expected_birth`),
  KEY `is_resolved` (`is_resolved`),
  KEY `created_by` (`created_by`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `maternity_alerts_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `maternity_alerts_ibfk_2` FOREIGN KEY (`pregnancy_id`) REFERENCES `pregnancy_controls` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maternity_alerts_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `maternity_alerts_ibfk_4` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. NOTIFICAÇÕES DO SISTEMA
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','error','success') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_date` timestamp NULL DEFAULT NULL,
  `related_table` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `is_read` (`is_read`),
  KEY `related_table` (`related_table`),
  KEY `related_id` (`related_id`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. PEDIGREE E GENEALOGIA
CREATE TABLE `pedigree_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `generation` int(11) NOT NULL,
  `position` enum('pai','mae','avo_paterno','avo_materno','avo_paterno_pai','avo_paterno_mae','avo_materno_pai','avo_materno_mae') NOT NULL,
  `related_animal_id` int(11) DEFAULT NULL,
  `animal_number` varchar(50) DEFAULT NULL,
  `animal_name` varchar(255) DEFAULT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `animal_id` (`animal_id`),
  KEY `related_animal_id` (`related_animal_id`),
  KEY `generation` (`generation`),
  KEY `position` (`position`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `pedigree_records_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pedigree_records_ibfk_2` FOREIGN KEY (`related_animal_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pedigree_records_ibfk_3` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. CONTAS SECUNDÁRIAS (Para funcionários)
CREATE TABLE `secondary_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `permissions` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` date DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_by` (`created_by`),
  KEY `is_active` (`is_active`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `secondary_accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `secondary_accounts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `secondary_accounts_ibfk_3` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. SOLICITAÇÕES DE SENHA
CREATE TABLE `password_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `email` (`email`),
  KEY `expires_at` (`expires_at`),
  KEY `is_used` (`is_used`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `password_requests_ibfk_1` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Inserir fazenda
INSERT INTO `farms` (`id`, `name`, `location`, `owner_name`, `address`, `phone`, `email`) VALUES
(1, 'Lagoa do Mato', 'Aquiraz - Ceará', 'Proprietário Lagoa do Mato', 'Fazenda Lagoa do Mato, Zona Rural', '(11) 99999-9999', 'contato@lactechsys.com');

-- Inserir usuários principais (senha: 123456)
INSERT INTO `users` (`id`, `email`, `password`, `name`, `role`, `farm_id`, `phone`, `hire_date`, `profile_photo`, `password_changed_at`, `password_change_required`, `is_active`) VALUES
(1, 'Fernando@lactech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fernando Silva', 'proprietario', 1, '(11) 99999-0001', '2020-01-01', NULL, '2020-01-01 00:00:00', 0, 1),
(2, 'Junior@lactech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Junior Silva', 'gerente', 1, '(11) 99999-0002', '2020-01-01', NULL, '2020-01-01 00:00:00', 0, 1);

-- Inserir alguns animais de exemplo
INSERT INTO `animals` (`id`, `animal_number`, `name`, `breed`, `gender`, `birth_date`, `status`, `farm_id`, `notes`) VALUES
(1, 'V001', 'Bella', 'Holandesa', 'femea', '2020-03-15', 'Lactante', 1, 'Vaca produtora principal'),
(2, 'V002', 'Luna', 'Gir', 'femea', '2021-05-20', 'Lactante', 1, 'Vaca jovem em produção'),
(3, 'V003', 'Maya', 'Girolanda', 'femea', '2019-08-10', 'Seco', 1, 'Vaca experiente'),
(4, 'N001', 'Estrela', 'Holandesa', 'femea', '2022-01-15', 'Novilha', 1, 'Novilha para primeira inseminação'),
(5, 'T001', 'Touro01', 'Holandês', 'macho', '2018-12-01', 'Touro', 1, 'Touro reprodutor');

-- Inserir alguns touros
INSERT INTO `bulls` (`id`, `bull_number`, `name`, `breed`, `birth_date`, `source`, `genetic_value`, `farm_id`) VALUES
(1, 'B001', 'Touro Elite', 'Holandês', '2018-12-01', 'proprio', 'Alto valor genético', 1),
(2, 'B002', 'Inseminação Premium', 'Gir', '2017-06-15', 'inseminacao', 'Sêmen importado', 1);

-- Inserir alguns medicamentos
INSERT INTO `medications` (`id`, `name`, `type`, `description`, `unit`, `stock_quantity`, `min_stock`, `unit_price`, `supplier`, `farm_id`) VALUES
(1, 'Penicilina', 'antibiotico', 'Antibiótico de amplo espectro', 'ml', 500.00, 100.00, 15.50, 'VetCorp', 1),
(2, 'Vitamina A+D+E', 'vitamina', 'Suplemento vitamínico', 'ml', 1000.00, 200.00, 8.90, 'FarmVet', 1),
(3, 'Ivermectina', 'vermifugo', 'Antiparasitário', 'ml', 300.00, 50.00, 12.30, 'AgroVet', 1);

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================

CREATE INDEX `idx_production_date_shift` ON `milk_production` (`production_date`, `shift`);
CREATE INDEX `idx_animal_production` ON `milk_production` (`animal_id`, `production_date`);
CREATE INDEX `idx_health_animal_date` ON `health_records` (`animal_id`, `record_date`);
CREATE INDEX `idx_reproduction_animal_date` ON `inseminations` (`animal_id`, `insemination_date`);
CREATE INDEX `idx_financial_date_type` ON `financial_records` (`record_date`, `type`);
CREATE INDEX `idx_birth_animal_date` ON `births` (`animal_id`, `birth_date`);
CREATE INDEX `idx_pregnancy_animal_date` ON `pregnancy_controls` (`animal_id`, `pregnancy_date`);

-- =====================================================
-- VIEWS PARA RELATÓRIOS
-- =====================================================

-- View de produção diária resumida
CREATE OR REPLACE VIEW `v_daily_production_summary` AS
SELECT 
    mp.production_date,
    mp.shift,
    COUNT(DISTINCT mp.animal_id) as total_animals,
    SUM(mp.volume) as total_volume,
    AVG(mp.volume) as avg_volume_per_animal,
    AVG(mp.quality_score) as avg_quality,
    AVG(mp.fat_content) as avg_fat,
    AVG(mp.protein_content) as avg_protein
FROM milk_production mp
WHERE mp.farm_id = 1
GROUP BY mp.production_date, mp.shift
ORDER BY mp.production_date DESC, mp.shift;

-- View de animais com informações completas
CREATE OR REPLACE VIEW `v_animals_complete` AS
SELECT 
    a.*,
    f.name as father_name,
    m.name as mother_name,
    DATEDIFF(CURDATE(), a.birth_date) as age_days,
    FLOOR(DATEDIFF(CURDATE(), a.birth_date) / 365) as age_years,
    CASE 
        WHEN a.gender = 'femea' AND DATEDIFF(CURDATE(), a.birth_date) >= 540 AND a.status = 'Novilha' THEN 'Pronta para IA'
        WHEN a.gender = 'femea' AND DATEDIFF(CURDATE(), a.birth_date) >= 720 AND a.status = 'Novilha' THEN 'Atrasada para IA'
        WHEN a.gender = 'femea' AND a.status = 'Lactante' THEN 'Em Produção'
        WHEN a.gender = 'femea' AND a.status = 'Seco' THEN 'Período Seco'
        ELSE 'Outros'
    END as status_description
FROM animals a
LEFT JOIN animals f ON a.father_id = f.id
LEFT JOIN animals m ON a.mother_id = m.id
WHERE a.is_active = 1 AND a.farm_id = 1;

-- View de usuários com informações da fazenda
CREATE OR REPLACE VIEW `v_users_complete` AS
SELECT 
    u.*,
    f.name as farm_name,
    DATEDIFF(CURDATE(), u.created_at) as days_since_creation,
    DATEDIFF(CURDATE(), u.last_login) as days_since_login,
    CASE 
        WHEN u.role = 'proprietario' THEN 'Proprietário'
        WHEN u.role = 'gerente' THEN 'Gerente'
        WHEN u.role = 'funcionario' THEN 'Funcionário'
        WHEN u.role = 'veterinario' THEN 'Veterinário'
        ELSE u.role
    END as role_description
FROM users u
LEFT JOIN farms f ON u.farm_id = f.id
WHERE u.is_active = 1;

-- View de prenhez ativa
CREATE OR REPLACE VIEW `v_active_pregnancies` AS
SELECT 
    pc.*,
    a.animal_number,
    a.name as animal_name,
    a.breed,
    DATEDIFF(pc.expected_birth, CURDATE()) as days_to_birth,
    CASE 
        WHEN DATEDIFF(pc.expected_birth, CURDATE()) <= 0 THEN 'Vencida'
        WHEN DATEDIFF(pc.expected_birth, CURDATE()) <= 7 THEN 'Próxima'
        WHEN DATEDIFF(pc.expected_birth, CURDATE()) <= 30 THEN 'Este mês'
        ELSE 'Futuro'
    END as birth_status
FROM pregnancy_controls pc
JOIN animals a ON pc.animal_id = a.id
WHERE pc.expected_birth >= CURDATE() 
AND a.is_active = 1 
AND a.farm_id = 1
ORDER BY pc.expected_birth ASC;

-- =====================================================
-- TRIGGERS PARA AUDITORIA E AUTOMAÇÃO
-- =====================================================

DELIMITER //

-- Trigger para atualizar status reprodutivo automaticamente
CREATE TRIGGER IF NOT EXISTS `tr_update_reproductive_status_insemination`
    AFTER INSERT ON `inseminations`
    FOR EACH ROW
BEGIN
    UPDATE animals 
    SET reproductive_status = 'prenha', updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.animal_id AND farm_id = NEW.farm_id;
END//

-- Trigger para criar controle de prenhez automaticamente
CREATE TRIGGER IF NOT EXISTS `tr_create_pregnancy_control`
    AFTER INSERT ON `inseminations`
    FOR EACH ROW
BEGIN
    INSERT INTO pregnancy_controls (animal_id, insemination_id, pregnancy_date, expected_birth, pregnancy_stage, recorded_by, farm_id)
    VALUES (NEW.animal_id, NEW.id, NEW.insemination_date, DATE_ADD(NEW.insemination_date, INTERVAL 280 DAY), 'inicial', NEW.recorded_by, NEW.farm_id);
END//

-- Trigger para criar alerta de maternidade
CREATE TRIGGER IF NOT EXISTS `tr_create_maternity_alert`
    AFTER INSERT ON `pregnancy_controls`
    FOR EACH ROW
BEGIN
    INSERT INTO maternity_alerts (animal_id, pregnancy_id, alert_date, expected_birth, days_to_birth, alert_message, created_by, farm_id)
    VALUES (
        NEW.animal_id, 
        NEW.id, 
        DATE_SUB(NEW.expected_birth, INTERVAL 30 DAY),
        NEW.expected_birth,
        DATEDIFF(NEW.expected_birth, DATE_SUB(NEW.expected_birth, INTERVAL 30 DAY)),
        CONCAT('Parto previsto em ', NEW.expected_birth, ' - Preparar maternidade'),
        NEW.recorded_by,
        NEW.farm_id
    );
END//

-- Trigger para atualizar timestamp automaticamente
CREATE TRIGGER IF NOT EXISTS `tr_animals_updated`
    BEFORE UPDATE ON `animals`
    FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

CREATE TRIGGER IF NOT EXISTS `tr_users_updated`
    BEFORE UPDATE ON `users`
    FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
    
    -- Se a senha foi alterada, atualizar timestamp
    IF OLD.password != NEW.password THEN
        SET NEW.password_changed_at = CURRENT_TIMESTAMP;
        SET NEW.password_change_required = 0;
    END IF;
END//

DELIMITER ;

-- =====================================================
-- FINALIZAÇÃO
-- =====================================================

COMMIT;

-- =====================================================
-- VERIFICAÇÃO FINAL
-- =====================================================

SELECT 'SISTEMA COMPLETO INSTALADO COM SUCESSO' as status;
SELECT 'Fazenda: Lagoa do Mato - Aquiraz/CE' as location;
SELECT COUNT(*) as total_tabelas FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE();
SELECT COUNT(*) as usuarios_criados FROM users;
SELECT COUNT(*) as animais_criados FROM animals;
SELECT COUNT(*) as medicamentos_criados FROM medications;

-- =====================================================
-- CREDENCIAIS DE ACESSO
-- =====================================================
-- 
-- PROPRIETÁRIO:
-- Email: Fernando@lactech.com
-- Senha: 123456
-- Acesso: Todas as funcionalidades
-- 
-- GERENTE:
-- Email: Junior@lactech.com
-- Senha: 123456
-- Acesso: Gestão completa + criar funcionários
-- 
-- =====================================================
