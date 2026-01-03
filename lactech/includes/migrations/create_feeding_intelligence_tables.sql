-- ============================================================
-- SISTEMA DE INTELIGÊNCIA DE ALIMENTAÇÃO
-- Evolução do módulo de alimentação para sistema de manejo alimentar
-- ============================================================

-- --------------------------------------------------------
-- 1. TABELA DE PESOS DOS ANIMAIS (histórico + peso atual)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `animal_weights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) DEFAULT NULL COMMENT 'ID do animal (se peso individual)',
  `group_id` int(11) DEFAULT NULL COMMENT 'ID do grupo/lote (se peso do lote)',
  `weight_kg` decimal(7,2) DEFAULT NULL COMMENT 'Peso individual em kg',
  `group_avg_weight_kg` decimal(7,2) DEFAULT NULL COMMENT 'Peso médio do lote em kg',
  `animal_count` int(11) DEFAULT NULL COMMENT 'Número de animais do lote (quando peso do lote)',
  `weighing_date` date NOT NULL COMMENT 'Data da pesagem',
  `weighing_type` enum('real','estimated','calculated') NOT NULL DEFAULT 'real' COMMENT 'Tipo: real, estimado ou calculado',
  `notes` text DEFAULT NULL COMMENT 'Observações',
  `recorded_by` int(11) DEFAULT NULL COMMENT 'ID do usuário que registrou',
  `farm_id` int(11) NOT NULL DEFAULT 1 COMMENT 'ID da fazenda',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização',
  PRIMARY KEY (`id`),
  KEY `idx_animal_id` (`animal_id`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_weighing_date` (`weighing_date`),
  KEY `idx_farm_id` (`farm_id`),
  KEY `idx_animal_date` (`animal_id`, `weighing_date`),
  KEY `idx_group_date` (`group_id`, `weighing_date`),
  CONSTRAINT `fk_animal_weights_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_animal_weights_group` FOREIGN KEY (`group_id`) REFERENCES `animal_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_animal_weights_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_animal_weights_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de pesos (individual ou do lote)';

-- --------------------------------------------------------
-- 2. TABELA DE COMPOSIÇÃO DE ALIMENTOS (MS, proteína, etc)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `feed_compositions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feed_name` varchar(100) NOT NULL COMMENT 'Nome do alimento',
  `feed_type` enum('concentrate','roughage','silage','hay','mineral','other') NOT NULL COMMENT 'Tipo de alimento',
  `dry_matter_pct` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Matéria Seca (%)',
  `protein_pct` decimal(5,2) DEFAULT NULL COMMENT 'Proteína Bruta (%)',
  `energy_mcal_kg` decimal(6,2) DEFAULT NULL COMMENT 'Energia (Mcal/kg MS)',
  `ndf_pct` decimal(5,2) DEFAULT NULL COMMENT 'NDF (%)',
  `adf_pct` decimal(5,2) DEFAULT NULL COMMENT 'ADF (%)',
  `calcium_pct` decimal(5,2) DEFAULT NULL COMMENT 'Cálcio (%)',
  `phosphorus_pct` decimal(5,2) DEFAULT NULL COMMENT 'Fósforo (%)',
  `cost_per_kg` decimal(10,2) DEFAULT NULL COMMENT 'Custo por kg (padrão)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Ativo',
  `farm_id` int(11) NOT NULL DEFAULT 1 COMMENT 'ID da fazenda',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização',
  PRIMARY KEY (`id`),
  KEY `idx_feed_type` (`feed_type`),
  KEY `idx_farm_id` (`farm_id`),
  KEY `idx_feed_name` (`feed_name`),
  CONSTRAINT `fk_feed_compositions_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Composição nutricional dos alimentos';

-- Inserir alguns alimentos padrão
INSERT INTO `feed_compositions` (`feed_name`, `feed_type`, `dry_matter_pct`, `protein_pct`, `energy_mcal_kg`, `cost_per_kg`, `farm_id`) VALUES
('Ração Concentrada 18%', 'concentrate', 88.00, 18.00, 3.20, NULL, 1),
('Ração Concentrada 20%', 'concentrate', 88.00, 20.00, 3.30, NULL, 1),
('Silagem de Milho', 'silage', 35.00, 8.00, 2.70, NULL, 1),
('Feno de Capim', 'hay', 85.00, 10.00, 2.10, NULL, 1),
('Volumoso (Pastagem)', 'roughage', 25.00, 12.00, 2.50, NULL, 1)
ON DUPLICATE KEY UPDATE `feed_name`=`feed_name`;

-- --------------------------------------------------------
-- 3. TABELA DE PARÂMETROS NUTRICIONAIS (para cálculos)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `nutritional_parameters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` enum('lactante','seco','novilha','bezerra','touro') NOT NULL COMMENT 'Categoria do animal',
  `days_in_milk_min` int(11) DEFAULT NULL COMMENT 'Dias em lactação mínimo (para lactantes)',
  `days_in_milk_max` int(11) DEFAULT NULL COMMENT 'Dias em lactação máximo (para lactantes)',
  `ms_consumption_pct` decimal(5,2) NOT NULL COMMENT 'Consumo de MS esperado (% do peso vivo)',
  `min_ms_pct` decimal(5,2) DEFAULT NULL COMMENT 'MS mínima (%)',
  `max_ms_pct` decimal(5,2) DEFAULT NULL COMMENT 'MS máxima (%)',
  `protein_requirement_pct` decimal(5,2) DEFAULT NULL COMMENT 'Requisito de proteína (%)',
  `description` text DEFAULT NULL COMMENT 'Descrição do parâmetro',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Ativo',
  `farm_id` int(11) NOT NULL DEFAULT 1 COMMENT 'ID da fazenda',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização',
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_farm_id` (`farm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Parâmetros nutricionais por categoria';

-- Inserir parâmetros padrão
INSERT INTO `nutritional_parameters` (`category`, `ms_consumption_pct`, `protein_requirement_pct`, `description`, `farm_id`) VALUES
('lactante', 3.50, 16.00, 'Vaca em lactação - consumo padrão 3,5% do PV em MS', 1),
('seco', 2.00, 12.00, 'Vaca seca - consumo padrão 2% do PV em MS', 1),
('novilha', 2.50, 14.00, 'Novilha - consumo padrão 2,5% do PV em MS', 1),
('bezerra', 3.00, 18.00, 'Bezerra - consumo padrão 3% do PV em MS', 1),
('touro', 2.00, 12.00, 'Touro - consumo padrão 2% do PV em MS', 1)
ON DUPLICATE KEY UPDATE `category`=`category`;

-- --------------------------------------------------------
-- 4. MODIFICAR feed_records PARA SUPORTAR LOTE
-- --------------------------------------------------------
-- Adicionar campos para suportar registro por lote
-- NOTA: Execute estas queries apenas se as colunas não existirem
-- (Verifique manualmente ou use um script PHP para verificar antes)

-- ALTER TABLE `feed_records` 
-- ADD COLUMN `group_id` int(11) DEFAULT NULL COMMENT 'ID do grupo/lote (se registro coletivo)' AFTER `animal_id`,
-- ADD COLUMN `record_type` enum('individual','group') NOT NULL DEFAULT 'individual' COMMENT 'Tipo: individual ou coletivo' AFTER `group_id`,
-- ADD COLUMN `animal_count` int(11) DEFAULT NULL COMMENT 'Número de animais (para registros coletivos)' AFTER `record_type`,
-- ADD KEY `idx_group_id` (`group_id`),
-- ADD KEY `idx_record_type` (`record_type`);

-- --------------------------------------------------------
-- 5. TABELA DE CÁLCULOS IDEAIS (armazena cálculos realizados)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ideal_feed_calculations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `calculation_date` date NOT NULL COMMENT 'Data do cálculo',
  `calculation_type` enum('individual','group') NOT NULL COMMENT 'Tipo: individual ou coletivo',
  `animal_id` int(11) DEFAULT NULL COMMENT 'ID do animal (se individual)',
  `group_id` int(11) DEFAULT NULL COMMENT 'ID do grupo (se coletivo)',
  `category` enum('lactante','seco','novilha','bezerra','touro') NOT NULL COMMENT 'Categoria do animal/lote',
  `avg_weight_kg` decimal(7,2) NOT NULL COMMENT 'Peso médio (kg)',
  `animal_count` int(11) NOT NULL DEFAULT 1 COMMENT 'Número de animais',
  `ms_consumption_pct` decimal(5,2) NOT NULL COMMENT 'Consumo MS (% do PV)',
  `ideal_ms_total_kg` decimal(10,2) NOT NULL COMMENT 'MS ideal total (kg)',
  `ideal_concentrate_kg` decimal(10,2) DEFAULT NULL COMMENT 'Concentrado ideal (kg)',
  `ideal_roughage_kg` decimal(10,2) DEFAULT NULL COMMENT 'Volumoso ideal (kg)',
  `ideal_silage_kg` decimal(10,2) DEFAULT NULL COMMENT 'Silagem ideal (kg)',
  `ideal_hay_kg` decimal(10,2) DEFAULT NULL COMMENT 'Feno ideal (kg)',
  `calculation_params` text DEFAULT NULL COMMENT 'Parâmetros do cálculo (JSON)',
  `farm_id` int(11) NOT NULL DEFAULT 1 COMMENT 'ID da fazenda',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização',
  PRIMARY KEY (`id`),
  KEY `idx_animal_id` (`animal_id`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_calculation_date` (`calculation_date`),
  KEY `idx_farm_id` (`farm_id`),
  KEY `idx_calculation_type` (`calculation_type`),
  CONSTRAINT `fk_ideal_calc_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ideal_calc_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cálculos de alimentação ideal';

-- --------------------------------------------------------
-- 6. TABELA DE COMPARAÇÃO REAL VS IDEAL (resultados)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `feed_comparisons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feed_record_id` int(11) NOT NULL COMMENT 'ID do registro real (feed_records)',
  `ideal_calculation_id` int(11) DEFAULT NULL COMMENT 'ID do cálculo ideal usado',
  `comparison_date` date NOT NULL COMMENT 'Data da comparação',
  `record_type` enum('individual','group') NOT NULL COMMENT 'Tipo do registro',
  `animal_id` int(11) DEFAULT NULL COMMENT 'ID do animal (se individual)',
  `group_id` int(11) DEFAULT NULL COMMENT 'ID do grupo (se coletivo)',
  `real_concentrate_kg` decimal(10,2) DEFAULT NULL COMMENT 'Concentrado real (kg)',
  `ideal_concentrate_kg` decimal(10,2) DEFAULT NULL COMMENT 'Concentrado ideal (kg)',
  `diff_concentrate_kg` decimal(10,2) DEFAULT NULL COMMENT 'Diferença concentrado (kg)',
  `diff_concentrate_pct` decimal(5,2) DEFAULT NULL COMMENT 'Diferença concentrado (%)',
  `real_roughage_kg` decimal(10,2) DEFAULT NULL COMMENT 'Volumoso real (kg)',
  `ideal_roughage_kg` decimal(10,2) DEFAULT NULL COMMENT 'Volumoso ideal (kg)',
  `diff_roughage_kg` decimal(10,2) DEFAULT NULL COMMENT 'Diferença volumoso (kg)',
  `diff_roughage_pct` decimal(5,2) DEFAULT NULL COMMENT 'Diferença volumoso (%)',
  `real_silage_kg` decimal(10,2) DEFAULT NULL COMMENT 'Silagem real (kg)',
  `ideal_silage_kg` decimal(10,2) DEFAULT NULL COMMENT 'Silagem ideal (kg)',
  `diff_silage_kg` decimal(10,2) DEFAULT NULL COMMENT 'Diferença silagem (kg)',
  `diff_silage_pct` decimal(5,2) DEFAULT NULL COMMENT 'Diferença silagem (%)',
  `real_hay_kg` decimal(10,2) DEFAULT NULL COMMENT 'Feno real (kg)',
  `ideal_hay_kg` decimal(10,2) DEFAULT NULL COMMENT 'Feno ideal (kg)',
  `diff_hay_kg` decimal(10,2) DEFAULT NULL COMMENT 'Diferença feno (kg)',
  `diff_hay_pct` decimal(5,2) DEFAULT NULL COMMENT 'Diferença feno (%)',
  `real_ms_total_kg` decimal(10,2) DEFAULT NULL COMMENT 'MS total real (kg)',
  `ideal_ms_total_kg` decimal(10,2) DEFAULT NULL COMMENT 'MS total ideal (kg)',
  `diff_ms_kg` decimal(10,2) DEFAULT NULL COMMENT 'Diferença MS (kg)',
  `diff_ms_pct` decimal(5,2) DEFAULT NULL COMMENT 'Diferença MS (%)',
  `status` enum('ok','below','above','warning') DEFAULT NULL COMMENT 'Status geral: ok, abaixo, acima, alerta',
  `alert_message` text DEFAULT NULL COMMENT 'Mensagem de alerta/sugestão',
  `farm_id` int(11) NOT NULL DEFAULT 1 COMMENT 'ID da fazenda',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização',
  PRIMARY KEY (`id`),
  KEY `idx_feed_record_id` (`feed_record_id`),
  KEY `idx_ideal_calc_id` (`ideal_calculation_id`),
  KEY `idx_comparison_date` (`comparison_date`),
  KEY `idx_animal_id` (`animal_id`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_status` (`status`),
  KEY `idx_farm_id` (`farm_id`),
  CONSTRAINT `fk_feed_comparison_record` FOREIGN KEY (`feed_record_id`) REFERENCES `feed_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_feed_comparison_ideal` FOREIGN KEY (`ideal_calculation_id`) REFERENCES `ideal_feed_calculations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_feed_comparison_farm` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comparação entre alimentação real e ideal';

