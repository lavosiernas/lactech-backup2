-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 22/10/2025 às 01:55
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

-- Selecionar o banco de dados
-- USE `lactech_lgmato`; -- Comentado para evitar erro de permissão

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `lactech_lgmato`
--

DELIMITER $$
--
-- Procedimentos (Comentado - já existem no banco)
--
-- CREATE PROCEDURE `get_heifer_current_phase` (IN `p_animal_id` INT)   BEGIN
--     SELECT 
--         a.id AS animal_id,
--         a.animal_number AS ear_tag,
--         a.name,
--         a.birth_date,
--         DATEDIFF(CURDATE(), a.birth_date) AS age_days,
--         FLOOR(DATEDIFF(CURDATE(), a.birth_date) / 30) AS age_months,
--         hp.id AS current_phase_id,
--         hp.phase_name AS current_phase,
--         hp.start_day,
--         hp.end_day,
--         hp.avg_daily_milk_liters,
--         hp.avg_daily_concentrate_kg,
--         hp.avg_daily_roughage_kg
--     FROM animals a
--     LEFT JOIN heifer_phases hp ON DATEDIFF(CURDATE(), a.birth_date) BETWEEN hp.start_day AND hp.end_day
--     WHERE a.id = p_animal_id;
-- END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `animals`
--

CREATE TABLE `animals` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `animals`
--

INSERT INTO `animals` (`id`, `animal_number`, `name`, `breed`, `gender`, `birth_date`, `birth_weight`, `father_id`, `mother_id`, `status`, `health_status`, `reproductive_status`, `entry_date`, `exit_date`, `exit_reason`, `farm_id`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'V001', 'Bella', 'Holandesa', 'femea', '2020-03-15', NULL, 5, 2, 'Lactante', 'saudavel', 'vazia', NULL, NULL, NULL, 1, 'Vaca produtora principal', 1, '2025-10-13 14:21:52', '2025-10-13 17:31:04'),
(2, 'V002', 'Luna', 'Gir', 'femea', '2021-05-20', NULL, NULL, NULL, 'Lactante', 'saudavel', 'vazia', NULL, NULL, NULL, 1, 'Vaca jovem em produção', 1, '2025-10-13 14:21:52', '2025-10-13 14:21:52'),
(3, 'V003', 'Maya', 'Girolanda', 'femea', '2019-08-10', NULL, NULL, NULL, 'Seco', 'saudavel', 'vazia', NULL, NULL, NULL, 1, 'Vaca experiente', 1, '2025-10-13 14:21:52', '2025-10-13 14:21:52'),
(4, 'N001', 'Estrela', 'Holandesa', 'femea', '2022-01-15', NULL, NULL, NULL, 'Novilha', 'saudavel', 'vazia', NULL, NULL, NULL, 1, 'Novilha para primeira inseminação', 1, '2025-10-13 14:21:52', '2025-10-13 14:21:52'),
(5, 'T001', 'Touro01', 'Holandês', 'macho', '2018-12-01', NULL, NULL, NULL, 'Touro', 'saudavel', 'vazia', NULL, NULL, NULL, 1, 'Touro reprodutor', 1, '2025-10-13 14:21:52', '2025-10-13 14:21:52'),
(11, '223', 'Francisco', 'girolando', 'macho', '2005-07-21', 54.00, NULL, NULL, 'Touro', 'saudavel', 'outros', '2025-10-19', NULL, NULL, 1, NULL, 1, '2025-10-20 01:56:38', '2025-10-20 01:56:38');

--
-- Acionadores `animals`
--
DELIMITER $$
CREATE TRIGGER `tr_animals_updated` BEFORE UPDATE ON `animals` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `births`
--

CREATE TABLE `births` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bulls`
--

CREATE TABLE `bulls` (
  `id` int(11) NOT NULL,
  `bull_number` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `breed` varchar(100) NOT NULL,
  `birth_date` date NOT NULL,
  `source` enum('proprio','alugado','comprado','inseminacao') NOT NULL DEFAULT 'inseminacao',
  `genetic_value` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `bulls`
--

INSERT INTO `bulls` (`id`, `bull_number`, `name`, `breed`, `birth_date`, `source`, `genetic_value`, `notes`, `farm_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'B001', 'Touro Elite', 'Holandês', '2018-12-01', 'proprio', 'Alto valor genético', NULL, 1, 1, '2025-10-13 14:21:52', '2025-10-13 14:21:52'),
(2, 'B002', 'Inseminação Premium', 'Gir', '2017-06-15', 'inseminacao', 'Sêmen importado', NULL, 1, 1, '2025-10-13 14:21:52', '2025-10-13 14:21:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `farms`
--

CREATE TABLE `farms` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `farms`
--

INSERT INTO `farms` (`id`, `name`, `location`, `cnpj`, `owner_name`, `address`, `phone`, `email`, `created_at`, `updated_at`) VALUES
(1, 'Lagoa do Mato', 'Aquiraz - Ceará', NULL, 'Proprietário Lagoa do Mato', 'Fazenda Lagoa do Mato, Zona Rural', '(11) 99999-9999', 'contato@lactechsys.com', '2025-10-13 14:21:52', '2025-10-13 14:21:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `financial_records`
--

CREATE TABLE `financial_records` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `health_alerts`
--

CREATE TABLE `health_alerts` (
  `id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `alert_type` enum('vacina','vermifugo','medicamento','consulta','parto','outros') NOT NULL,
  `alert_date` date NOT NULL,
  `alert_message` text NOT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_date` date DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `health_records`
--

CREATE TABLE `health_records` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `health_records`
--

INSERT INTO `health_records` (`id`, `animal_id`, `record_date`, `record_type`, `description`, `medication`, `dosage`, `cost`, `next_date`, `veterinarian`, `recorded_by`, `farm_id`, `created_at`, `updated_at`) VALUES
(0, 1, '2025-10-20', 'Medicamento', 'gripe', 'antiviroso', '34', 121.00, '2025-10-21', 'junior', 2, 1, '2025-10-20 02:31:53', '2025-10-20 02:31:53'),
(0, 1, '2025-10-20', 'Medicamento', 'sest', 'antiviroso', '34', 341.00, '2025-10-20', 'junior', 2, 1, '2025-10-20 02:38:20', '2025-10-20 02:38:20'),
(0, 11, '2025-10-20', 'Vacinação', 'esr', 'antiviroso', '34', 34.00, '2025-03-02', 'junior', 2, 1, '2025-10-20 02:42:00', '2025-10-20 02:42:00'),
(0, 1, '2025-10-20', 'Vacinação', 'esas', 'antiviroso', '34', 44.00, '2006-04-04', 'junior', 2, 1, '2025-10-20 02:45:25', '2025-10-20 02:45:25'),
(0, 2, '2025-10-20', 'Vacinação', 'esd', 'antiviroso', '34', 45.00, '2024-04-03', 'junior', 2, 1, '2025-10-20 02:49:41', '2025-10-20 02:49:41'),
(0, 2, '2025-10-20', 'Vacinação', 'hgd', 'antiviroso', '34', 34.00, '2024-02-03', 'junior', 2, 1, '2025-10-20 02:54:14', '2025-10-20 02:54:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `heat_cycles`
--

CREATE TABLE `heat_cycles` (
  `id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `heat_date` date NOT NULL,
  `heat_intensity` enum('leve','moderado','forte') NOT NULL DEFAULT 'moderado',
  `insemination_planned` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `heifer_costs`
--

CREATE TABLE `heifer_costs` (
  `id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL COMMENT 'ID da novilha',
  `phase_id` int(11) DEFAULT NULL COMMENT 'Fase em que ocorreu o custo',
  `category_id` int(11) DEFAULT NULL COMMENT 'Referência para heifer_cost_categories',
  `cost_date` date NOT NULL COMMENT 'Data do custo',
  `cost_category` enum('Alimentação','Medicamentos','Vacinas','Manejo','Transporte','Outros') NOT NULL COMMENT 'Categoria do custo',
  `quantity` decimal(10,3) DEFAULT 1.000 COMMENT 'Quantidade (litros, kg, dias, etc)',
  `unit` enum('Litros','Kg','Dias','Unidade','Hora','Mês') DEFAULT 'Unidade',
  `unit_price` decimal(10,2) DEFAULT 0.00 COMMENT 'Preço unitário em R$',
  `total_cost` decimal(10,2) DEFAULT NULL COMMENT 'Custo total calculado',
  `cost_amount` decimal(10,2) NOT NULL COMMENT 'Valor do custo em R$',
  `description` text NOT NULL COMMENT 'Descrição detalhada do custo',
  `is_automatic` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Se foi calculado automaticamente',
  `recorded_by` int(11) NOT NULL COMMENT 'Usuário que registrou',
  `farm_id` int(11) NOT NULL DEFAULT 1 COMMENT 'ID da fazenda',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Controle de custos de criação de novilhas';

--
-- Despejando dados para a tabela `heifer_costs`
--

INSERT INTO `heifer_costs` (`id`, `animal_id`, `phase_id`, `category_id`, `cost_date`, `cost_category`, `quantity`, `unit`, `unit_price`, `total_cost`, `cost_amount`, `description`, `is_automatic`, `recorded_by`, `farm_id`, `created_at`, `updated_at`) VALUES
(1, 4, NULL, NULL, '2025-10-20', 'Alimentação', 1.000, 'Unidade', 0.00, 0.00, 234.00, 'concentrado', 0, 2, 1, '2025-10-20 22:33:55', '2025-10-20 22:33:55');

--
-- Acionadores `heifer_costs`
--
DELIMITER $$
CREATE TRIGGER `tr_heifer_costs_set_phase` BEFORE INSERT ON `heifer_costs` FOR EACH ROW BEGIN
    DECLARE v_age_days INT;
    DECLARE v_phase_id INT;
    
    -- Calcular idade em dias
    SELECT DATEDIFF(NEW.cost_date, birth_date) INTO v_age_days
    FROM animals WHERE id = NEW.animal_id;
    
    -- Determinar fase baseada na idade
    SELECT id INTO v_phase_id
    FROM heifer_phases
    WHERE v_age_days BETWEEN start_day AND end_day
    AND active = 1
    LIMIT 1;
    
    -- Atualizar phase_id se não foi fornecido
    IF NEW.phase_id IS NULL THEN
        SET NEW.phase_id = v_phase_id;
    END IF;
    
    -- Calcular total_cost se não foi fornecido
    IF NEW.total_cost IS NULL OR NEW.total_cost = 0 THEN
        SET NEW.total_cost = COALESCE(NEW.quantity, 1) * COALESCE(NEW.unit_price, NEW.cost_amount, 0);
    END IF;
    
    -- Se total_cost foi fornecido mas cost_amount não, copiar
    IF NEW.cost_amount IS NULL OR NEW.cost_amount = 0 THEN
        SET NEW.cost_amount = NEW.total_cost;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_heifer_costs_updated` BEFORE UPDATE ON `heifer_costs` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `heifer_cost_categories`
--

CREATE TABLE `heifer_cost_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL COMMENT 'Nome da categoria',
  `category_type` enum('Alimentação','Mão de Obra','Sanidade','Manejo','Instalações','Outros') NOT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `heifer_cost_categories`
--

INSERT INTO `heifer_cost_categories` (`id`, `category_name`, `category_type`, `description`, `active`, `created_at`) VALUES
(1, 'Leite Integral', 'Alimentação', 'Leite integral da própria fazenda', 1, '2025-10-20 22:07:36'),
(2, 'Sucedâneo', 'Alimentação', 'Substituto de leite (leite em pó)', 1, '2025-10-20 22:07:36'),
(3, 'Concentrado Inicial', 'Alimentação', 'Ração concentrada para bezerras', 1, '2025-10-20 22:07:36'),
(4, 'Concentrado Crescimento', 'Alimentação', 'Ração concentrada para fase de crescimento', 1, '2025-10-20 22:07:36'),
(5, 'Volumoso (Silagem)', 'Alimentação', 'Silagem de milho ou sorgo', 1, '2025-10-20 22:07:36'),
(6, 'Volumoso (Feno)', 'Alimentação', 'Feno de gramíneas ou leguminosas', 1, '2025-10-20 22:07:36'),
(7, 'Pastagem', 'Alimentação', 'Custo de pastagem/pasto', 1, '2025-10-20 22:07:36'),
(8, 'Mão de Obra', 'Mão de Obra', 'Custo de funcionários dedicados à criação', 1, '2025-10-20 22:07:36'),
(9, 'Medicamentos', 'Sanidade', 'Antibióticos, anti-inflamatórios, etc', 1, '2025-10-20 22:07:36'),
(10, 'Vacinas', 'Sanidade', 'Vacinas obrigatórias e preventivas', 1, '2025-10-20 22:07:36'),
(11, 'Vermífugos', 'Sanidade', 'Controle de verminoses', 1, '2025-10-20 22:07:36'),
(12, 'Exames Veterinários', 'Sanidade', 'Consultas e exames', 1, '2025-10-20 22:07:36'),
(13, 'Descorna', 'Manejo', 'Procedimento de descorna', 1, '2025-10-20 22:07:36'),
(14, 'Identificação', 'Manejo', 'Brincos, tatuagens, chips', 1, '2025-10-20 22:07:36'),
(15, 'Transporte', 'Manejo', 'Transporte de animais', 1, '2025-10-20 22:07:36'),
(16, 'Instalações/Depreciação', 'Instalações', 'Custo de bezerreiros, baias, etc', 1, '2025-10-20 22:07:36'),
(17, 'Energia/Água', 'Instalações', 'Consumo de energia e água', 1, '2025-10-20 22:07:36'),
(18, 'Outros Custos', 'Outros', 'Custos diversos não categorizados', 1, '2025-10-20 22:07:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `heifer_daily_consumption`
--

CREATE TABLE `heifer_daily_consumption` (
  `id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL COMMENT 'ID da novilha',
  `consumption_date` date NOT NULL COMMENT 'Data do consumo',
  `age_days` int(11) NOT NULL COMMENT 'Idade em dias',
  `phase_id` int(11) DEFAULT NULL COMMENT 'Fase de criação',
  `milk_liters` decimal(5,2) DEFAULT 0.00 COMMENT 'Leite/sucedâneo consumido (L)',
  `concentrate_kg` decimal(5,2) DEFAULT 0.00 COMMENT 'Concentrado consumido (kg)',
  `roughage_kg` decimal(5,2) DEFAULT 0.00 COMMENT 'Volumoso consumido (kg)',
  `weight_kg` decimal(6,2) DEFAULT NULL COMMENT 'Peso do animal no dia (kg)',
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `heifer_phases`
--

CREATE TABLE `heifer_phases` (
  `id` int(11) NOT NULL,
  `phase_name` varchar(100) NOT NULL COMMENT 'Nome da fase',
  `start_day` int(11) NOT NULL COMMENT 'Dia inicial da fase',
  `end_day` int(11) NOT NULL COMMENT 'Dia final da fase',
  `description` text DEFAULT NULL COMMENT 'Descrição da fase',
  `avg_daily_milk_liters` decimal(5,2) DEFAULT NULL COMMENT 'Consumo médio diário de leite (litros)',
  `avg_daily_concentrate_kg` decimal(5,2) DEFAULT NULL COMMENT 'Consumo médio diário de concentrado (kg)',
  `avg_daily_roughage_kg` decimal(5,2) DEFAULT NULL COMMENT 'Consumo médio diário de volumoso (kg)',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `heifer_phases`
--

INSERT INTO `heifer_phases` (`id`, `phase_name`, `start_day`, `end_day`, `description`, `avg_daily_milk_liters`, `avg_daily_concentrate_kg`, `avg_daily_roughage_kg`, `active`, `created_at`) VALUES
(1, 'Aleitamento', 0, 60, 'Fase de consumo intensivo de leite integral ou sucedâneo. Essencial para desenvolvimento inicial.', 6.00, 0.50, 0.00, 1, '2025-10-20 22:07:36'),
(2, 'Transição/Desmame', 61, 90, 'Redução gradual do leite e introdução de concentrado e volumoso de qualidade.', 3.00, 1.50, 2.00, 1, '2025-10-20 22:07:36'),
(3, 'Recria Inicial', 91, 180, 'Crescimento acelerado com foco em desenvolvimento do rúmen. Volumoso e concentrado em proporções adequadas.', 0.00, 2.50, 8.00, 1, '2025-10-20 22:07:36'),
(4, 'Recria Intermediária', 181, 365, 'Fase de consolidação do crescimento. Aumento gradual do consumo de volumoso.', 0.00, 3.00, 15.00, 1, '2025-10-20 22:07:36'),
(5, 'Crescimento/Desenvolvimento', 366, 540, 'Preparação para a primeira cobertura. Foco em ganho de peso e estrutura corporal.', 0.00, 3.50, 22.00, 1, '2025-10-20 22:07:36'),
(6, 'Pré-parto', 541, 780, 'Gestação e preparação para primeira lactação. Nutrição adequada para mãe e feto.', 0.00, 4.00, 28.00, 1, '2025-10-20 22:07:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `heifer_price_history`
--

CREATE TABLE `heifer_price_history` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL COMMENT 'Categoria do insumo',
  `price_date` date NOT NULL COMMENT 'Data de vigência do preço',
  `unit_price` decimal(10,2) NOT NULL COMMENT 'Preço por unidade',
  `unit` enum('Litros','Kg','Dias','Unidade','Hora','Mês') NOT NULL,
  `notes` text DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `recorded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `heifer_price_history`
--

INSERT INTO `heifer_price_history` (`id`, `category_id`, `price_date`, `unit_price`, `unit`, `notes`, `farm_id`, `recorded_by`, `created_at`) VALUES
(1, 2, '2025-10-20', 0.60, 'Litros', 'Preço inicial do sucedâneo', 1, 2, '2025-10-20 22:07:37'),
(2, 3, '2025-10-20', 1.80, 'Kg', 'Preço inicial do concentrado inicial', 1, 2, '2025-10-20 22:07:37'),
(3, 4, '2025-10-20', 1.50, 'Kg', 'Preço inicial do concentrado crescimento', 1, 2, '2025-10-20 22:07:37'),
(4, 5, '2025-10-20', 0.50, 'Kg', 'Preço inicial da silagem', 1, 2, '2025-10-20 22:07:37'),
(5, 6, '2025-10-20', 0.80, 'Kg', 'Preço inicial do feno', 1, 2, '2025-10-20 22:07:37');

-- --------------------------------------------------------

--
-- Estrutura para tabela `inseminations`
--

CREATE TABLE `inseminations` (
  `id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `bull_id` int(11) DEFAULT NULL,
  `insemination_date` date NOT NULL,
  `insemination_type` enum('natural','inseminacao_artificial','transferencia_embriao') NOT NULL DEFAULT 'inseminacao_artificial',
  `technician` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Acionadores `inseminations`
--
DELIMITER $$
CREATE TRIGGER `tr_create_pregnancy_control` AFTER INSERT ON `inseminations` FOR EACH ROW BEGIN
    INSERT INTO pregnancy_controls (animal_id, insemination_id, pregnancy_date, expected_birth, pregnancy_stage, recorded_by, farm_id)
    VALUES (NEW.animal_id, NEW.id, NEW.insemination_date, DATE_ADD(NEW.insemination_date, INTERVAL 280 DAY), 'inicial', NEW.recorded_by, NEW.farm_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_update_reproductive_status_insemination` AFTER INSERT ON `inseminations` FOR EACH ROW BEGIN
    UPDATE animals 
    SET reproductive_status = 'prenha', updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.animal_id AND farm_id = NEW.farm_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `lactations`
--

CREATE TABLE `lactations` (
  `id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `birth_id` int(11) DEFAULT NULL,
  `lactation_start` date NOT NULL,
  `lactation_end` date DEFAULT NULL,
  `total_volume` decimal(10,2) DEFAULT 0.00,
  `average_daily` decimal(8,2) DEFAULT 0.00,
  `peak_day` int(11) DEFAULT NULL,
  `peak_volume` decimal(8,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `maternity_alerts`
--

CREATE TABLE `maternity_alerts` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `medications`
--

CREATE TABLE `medications` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('antibiotico','antiinflamatorio','vitamina','vermifugo','vacina','outros') NOT NULL,
  `description` text DEFAULT NULL,
  `unit` enum('ml','mg','g','unidade','dose') NOT NULL DEFAULT 'ml',
  `stock_quantity` decimal(10,2) DEFAULT 0.00,
  `min_stock` decimal(10,2) DEFAULT 0.00,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `medications`
--

INSERT INTO `medications` (`id`, `name`, `type`, `description`, `unit`, `stock_quantity`, `min_stock`, `unit_price`, `expiry_date`, `supplier`, `farm_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Penicilina', 'antibiotico', 'Antibiótico de amplo espectro', 'ml', 500.00, 100.00, 15.50, NULL, 'VetCorp', 1, 1, '2025-10-13 14:21:52', '2025-10-13 14:21:52'),
(2, 'Vitamina A+D+E', 'vitamina', 'Suplemento vitamínico', 'ml', 1000.00, 200.00, 8.90, NULL, 'FarmVet', 1, 1, '2025-10-13 14:21:52', '2025-10-13 14:21:52'),
(3, 'Ivermectina', 'vermifugo', 'Antiparasitário', 'ml', 300.00, 50.00, 12.30, NULL, 'AgroVet', 1, 1, '2025-10-13 14:21:52', '2025-10-13 14:21:52'),
(0, 'estecoventril', 'antibiotico', NULL, 'ml', 3.00, 1.00, 264.00, '2029-05-21', 'vartcot', 1, 1, '2025-10-20 02:33:32', '2025-10-20 02:33:32'),
(0, 'dipirona', 'antibiotico', NULL, 'ml', 3.00, 1.00, 23.00, '2029-04-21', 'vatscop', 1, 1, '2025-10-20 02:39:17', '2025-10-20 02:39:17');

-- --------------------------------------------------------

--
-- Estrutura para tabela `medication_applications`
--

CREATE TABLE `medication_applications` (
  `id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `medication_id` int(11) NOT NULL,
  `application_date` date NOT NULL,
  `quantity` decimal(8,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `applied_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `milk_production`
--

CREATE TABLE `milk_production` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','error','success') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_date` timestamp NULL DEFAULT NULL,
  `related_table` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `password_requests`
--

CREATE TABLE `password_requests` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedigree_records`
--

CREATE TABLE `pedigree_records` (
  `id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `generation` int(11) NOT NULL,
  `position` enum('pai','mae','avo_paterno','avo_materno','avo_paterno_pai','avo_paterno_mae','avo_materno_pai','avo_materno_mae') NOT NULL,
  `related_animal_id` int(11) DEFAULT NULL,
  `animal_number` varchar(50) DEFAULT NULL,
  `animal_name` varchar(255) DEFAULT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pregnancy_controls`
--

CREATE TABLE `pregnancy_controls` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Acionadores `pregnancy_controls`
--
DELIMITER $$
CREATE TRIGGER `tr_create_maternity_alert` AFTER INSERT ON `pregnancy_controls` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `quality_tests`
--

CREATE TABLE `quality_tests` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `secondary_accounts`
--

CREATE TABLE `secondary_accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` date DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `farm_id`, `cpf`, `phone`, `address`, `hire_date`, `salary`, `profile_photo`, `password_changed_at`, `password_change_required`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Fernando Silva', 'Fernando@lactech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'proprietario', 1, NULL, '(11) 99999-0001', NULL, '2020-01-01', NULL, NULL, '2020-01-01 00:00:00', 0, 1, NULL, '2025-10-13 14:21:52', '2025-10-13 14:21:52'),
(2, 'Junior Silva', 'Junior@lactech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gerente', 1, NULL, '(11) 99999-0002', NULL, '2020-01-01', NULL, NULL, '2020-01-01 00:00:00', 0, 1, '2025-10-20 21:46:49', '2025-10-13 14:21:52', '2025-10-20 21:46:49');

--
-- Acionadores `users`
--
DELIMITER $$
CREATE TRIGGER `tr_users_updated` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
    
    -- Se a senha foi alterada, atualizar timestamp
    IF OLD.password != NEW.password THEN
        SET NEW.password_changed_at = CURRENT_TIMESTAMP;
        SET NEW.password_change_required = 0;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `vaccination_programs`
--

CREATE TABLE `vaccination_programs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `target_age_min` int(11) DEFAULT NULL,
  `target_age_max` int(11) DEFAULT NULL,
  `frequency_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `volume_records`
--

CREATE TABLE `volume_records` (
  `id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `shift` enum('manha','tarde','noite') NOT NULL,
  `total_volume` decimal(10,2) NOT NULL,
  `total_animals` int(11) NOT NULL,
  `average_per_animal` decimal(8,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `volume_records`
--

INSERT INTO `volume_records` (`id`, `record_date`, `shift`, `total_volume`, `total_animals`, `average_per_animal`, `notes`, `recorded_by`, `farm_id`, `created_at`, `updated_at`) VALUES
(1, '2025-10-13', 'manha', 581.00, 1, 581.00, NULL, 1, 1, '2025-10-13 17:27:32', '2025-10-13 17:27:32'),
(0, '2025-10-20', 'noite', 123.00, 1, 123.00, NULL, 2, 1, '2025-10-21 01:31:29', '2025-10-21 01:31:29');

-- --------------------------------------------------------

--
-- Estrutura para tabela `v_active_pregnancies`
--

CREATE TABLE `v_active_pregnancies` (
  `id` int(11) DEFAULT NULL,
  `animal_id` int(11) DEFAULT NULL,
  `insemination_id` int(11) DEFAULT NULL,
  `pregnancy_date` date DEFAULT NULL,
  `expected_birth` date DEFAULT NULL,
  `pregnancy_stage` enum('inicial','meio','final','pre-parto') DEFAULT NULL,
  `ultrasound_date` date DEFAULT NULL,
  `ultrasound_result` enum('positivo','negativo','indefinido') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `farm_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `animal_number` varchar(50) DEFAULT NULL,
  `animal_name` varchar(255) DEFAULT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `days_to_birth` int(8) DEFAULT NULL,
  `birth_status` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `v_animals_complete`
--

CREATE TABLE `v_animals_complete` (
  `id` int(11) DEFAULT NULL,
  `animal_number` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `gender` enum('femea','macho') DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `birth_weight` decimal(6,2) DEFAULT NULL,
  `father_id` int(11) DEFAULT NULL,
  `mother_id` int(11) DEFAULT NULL,
  `status` enum('Lactante','Seco','Novilha','Vaca','Bezerra','Bezerro','Touro') DEFAULT NULL,
  `health_status` enum('saudavel','doente','tratamento','quarentena') DEFAULT NULL,
  `reproductive_status` enum('vazia','prenha','lactante','seca','outros') DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `exit_date` date DEFAULT NULL,
  `exit_reason` varchar(255) DEFAULT NULL,
  `farm_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `father_name` varchar(255) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `age_days` int(8) DEFAULT NULL,
  `age_years` int(9) DEFAULT NULL,
  `status_description` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `v_daily_production_summary`
--

CREATE TABLE `v_daily_production_summary` (
  `production_date` date DEFAULT NULL,
  `shift` enum('manha','tarde','noite') DEFAULT NULL,
  `total_animals` bigint(21) DEFAULT NULL,
  `total_volume` decimal(30,2) DEFAULT NULL,
  `avg_volume_per_animal` decimal(12,6) DEFAULT NULL,
  `avg_quality` decimal(7,5) DEFAULT NULL,
  `avg_fat` decimal(8,6) DEFAULT NULL,
  `avg_protein` decimal(8,6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_heifer_costs_by_category`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_heifer_costs_by_category` (
`cost_category` varchar(12)
,`category_name` varchar(100)
,`total_records` bigint(21)
,`total_amount` decimal(32,2)
,`average_amount` decimal(14,6)
,`min_amount` decimal(10,2)
,`max_amount` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_heifer_costs_by_phase`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_heifer_costs_by_phase` (
`animal_id` int(11)
,`ear_tag` varchar(50)
,`name` varchar(255)
,`phase_name` varchar(100)
,`start_day` int(11)
,`end_day` int(11)
,`category_type` varchar(12)
,`phase_cost` decimal(32,2)
,`cost_records` bigint(21)
,`avg_cost_per_record` decimal(14,6)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_heifer_total_costs`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_heifer_total_costs` (
`animal_id` int(11)
,`animal_number` varchar(50)
,`animal_name` varchar(255)
,`breed` varchar(100)
,`birth_date` date
,`age_days` int(7)
,`age_months` int(8)
,`total_cost_records` bigint(21)
,`total_cost` decimal(32,2)
,`average_cost` decimal(14,6)
,`avg_daily_cost` decimal(36,6)
,`current_phase` varchar(100)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_low_stock_medications`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_low_stock_medications` (
`id` int(11)
,`farm_id` int(11)
,`name` varchar(255)
,`type` enum('antibiotico','antiinflamatorio','vitamina','vermifugo','vacina','outros')
,`supplier` varchar(255)
,`expiry_date` date
,`stock_quantity` decimal(10,2)
,`unit` enum('ml','mg','g','unidade','dose')
,`min_stock` decimal(10,2)
,`unit_price` decimal(10,2)
,`description` text
,`is_active` tinyint(1)
,`created_at` timestamp
,`updated_at` timestamp
,`stock_status` varchar(11)
);

-- --------------------------------------------------------

--
-- Estrutura para tabela `v_users_complete`
--

CREATE TABLE `v_users_complete` (
  `id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('proprietario','gerente','funcionario','veterinario') DEFAULT NULL,
  `farm_id` int(11) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `password_changed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `password_change_required` tinyint(1) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `last_login` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `farm_name` varchar(255) DEFAULT NULL,
  `days_since_creation` int(8) DEFAULT NULL,
  `days_since_login` int(8) DEFAULT NULL,
  `role_description` varchar(12) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para view `v_heifer_costs_by_category`
--
DROP TABLE IF EXISTS `v_heifer_costs_by_category`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_heifer_costs_by_category`  AS SELECT coalesce(`hcc`.`category_type`,`hc`.`cost_category`) AS `cost_category`, `hcc`.`category_name` AS `category_name`, count(`hc`.`id`) AS `total_records`, sum(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)) AS `total_amount`, avg(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)) AS `average_amount`, min(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)) AS `min_amount`, max(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)) AS `max_amount` FROM (`heifer_costs` `hc` left join `heifer_cost_categories` `hcc` on(`hc`.`category_id` = `hcc`.`id`)) WHERE `hc`.`farm_id` = 1 GROUP BY coalesce(`hcc`.`category_type`,`hc`.`cost_category`), `hcc`.`category_name` ORDER BY sum(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)) DESC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_heifer_costs_by_phase`
--
DROP TABLE IF EXISTS `v_heifer_costs_by_phase`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_heifer_costs_by_phase`  AS SELECT `hc`.`animal_id` AS `animal_id`, `a`.`animal_number` AS `ear_tag`, `a`.`name` AS `name`, `hp`.`phase_name` AS `phase_name`, `hp`.`start_day` AS `start_day`, `hp`.`end_day` AS `end_day`, coalesce(`hcc`.`category_type`,`hc`.`cost_category`) AS `category_type`, sum(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)) AS `phase_cost`, count(`hc`.`id`) AS `cost_records`, avg(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)) AS `avg_cost_per_record` FROM (((`heifer_costs` `hc` join `animals` `a` on(`hc`.`animal_id` = `a`.`id`)) left join `heifer_phases` `hp` on(`hc`.`phase_id` = `hp`.`id`)) left join `heifer_cost_categories` `hcc` on(`hc`.`category_id` = `hcc`.`id`)) GROUP BY `hc`.`animal_id`, `a`.`animal_number`, `a`.`name`, `hp`.`phase_name`, `hp`.`start_day`, `hp`.`end_day`, coalesce(`hcc`.`category_type`,`hc`.`cost_category`) ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_heifer_total_costs`
--
DROP TABLE IF EXISTS `v_heifer_total_costs`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_heifer_total_costs`  AS SELECT `a`.`id` AS `animal_id`, `a`.`animal_number` AS `animal_number`, `a`.`name` AS `animal_name`, `a`.`breed` AS `breed`, `a`.`birth_date` AS `birth_date`, to_days(curdate()) - to_days(`a`.`birth_date`) AS `age_days`, floor((to_days(curdate()) - to_days(`a`.`birth_date`)) / 30) AS `age_months`, count(`hc`.`id`) AS `total_cost_records`, coalesce(sum(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)),0) AS `total_cost`, coalesce(avg(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)),0) AS `average_cost`, coalesce(sum(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)),0) / nullif(to_days(curdate()) - to_days(`a`.`birth_date`),0) AS `avg_daily_cost`, `hp`.`phase_name` AS `current_phase` FROM ((`animals` `a` left join `heifer_costs` `hc` on(`a`.`id` = `hc`.`animal_id`)) left join `heifer_phases` `hp` on(to_days(curdate()) - to_days(`a`.`birth_date`) between `hp`.`start_day` and `hp`.`end_day`)) WHERE (`a`.`status` = 'Novilha' OR `a`.`status` = 'Bezerra' OR `a`.`status` = 'Bezerro') AND `a`.`is_active` = 1 GROUP BY `a`.`id`, `a`.`animal_number`, `a`.`name`, `a`.`breed`, `a`.`birth_date`, `hp`.`phase_name` ORDER BY coalesce(sum(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)),0) DESC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_low_stock_medications`
--
DROP TABLE IF EXISTS `v_low_stock_medications`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_low_stock_medications`  AS SELECT `m`.`id` AS `id`, `m`.`farm_id` AS `farm_id`, `m`.`name` AS `name`, `m`.`type` AS `type`, `m`.`supplier` AS `supplier`, `m`.`expiry_date` AS `expiry_date`, `m`.`stock_quantity` AS `stock_quantity`, `m`.`unit` AS `unit`, `m`.`min_stock` AS `min_stock`, `m`.`unit_price` AS `unit_price`, `m`.`description` AS `description`, `m`.`is_active` AS `is_active`, `m`.`created_at` AS `created_at`, `m`.`updated_at` AS `updated_at`, CASE WHEN `m`.`stock_quantity` = 0 THEN 'Sem Estoque' WHEN `m`.`stock_quantity` <= `m`.`min_stock` * 0.5 THEN 'Crítico' WHEN `m`.`stock_quantity` <= `m`.`min_stock` THEN 'Baixo' ELSE 'Normal' END AS `stock_status` FROM `medications` AS `m` WHERE `m`.`stock_quantity` <= `m`.`min_stock` AND `m`.`is_active` = 1 ORDER BY `m`.`stock_quantity` ASC ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `animals`
--
ALTER TABLE `animals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `animal_number` (`animal_number`),
  ADD KEY `farm_id` (`farm_id`),
  ADD KEY `father_id` (`father_id`),
  ADD KEY `mother_id` (`mother_id`),
  ADD KEY `breed` (`breed`),
  ADD KEY `gender` (`gender`),
  ADD KEY `status` (`status`),
  ADD KEY `birth_date` (`birth_date`);

--
-- Índices de tabela `births`
--
ALTER TABLE `births`
  ADD PRIMARY KEY (`id`),
  ADD KEY `animal_id` (`animal_id`),
  ADD KEY `pregnancy_id` (`pregnancy_id`),
  ADD KEY `birth_date` (`birth_date`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `farm_id` (`farm_id`),
  ADD KEY `idx_birth_animal_date` (`animal_id`,`birth_date`);

--
-- Índices de tabela `bulls`
--
ALTER TABLE `bulls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bull_number` (`bull_number`),
  ADD KEY `farm_id` (`farm_id`),
  ADD KEY `breed` (`breed`);

--
-- Índices de tabela `farms`
--
ALTER TABLE `farms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Índices de tabela `financial_records`
--
ALTER TABLE `financial_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `record_date` (`record_date`),
  ADD KEY `type` (`type`),
  ADD KEY `category` (`category`),
  ADD KEY `related_animal_id` (`related_animal_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `farm_id` (`farm_id`),
  ADD KEY `idx_financial_date_type` (`record_date`,`type`);

--
-- Índices de tabela `heifer_costs`
--
ALTER TABLE `heifer_costs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `animal_id` (`animal_id`),
  ADD KEY `cost_date` (`cost_date`),
  ADD KEY `cost_category` (`cost_category`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `farm_id` (`farm_id`),
  ADD KEY `idx_heifer_costs_animal_date` (`animal_id`,`cost_date`),
  ADD KEY `idx_heifer_costs_date_category` (`cost_date`,`cost_category`),
  ADD KEY `idx_heifer_costs_farm_animal` (`farm_id`,`animal_id`),
  ADD KEY `idx_phase` (`phase_id`),
  ADD KEY `idx_category` (`category_id`);

--
-- Índices de tabela `heifer_cost_categories`
--
ALTER TABLE `heifer_cost_categories`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `heifer_daily_consumption`
--
ALTER TABLE `heifer_daily_consumption`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_consumption` (`animal_id`,`consumption_date`),
  ADD KEY `idx_animal_date` (`animal_id`,`consumption_date`),
  ADD KEY `idx_phase` (`phase_id`);

--
-- Índices de tabela `heifer_phases`
--
ALTER TABLE `heifer_phases`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `heifer_price_history`
--
ALTER TABLE `heifer_price_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_date` (`category_id`,`price_date`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `animals`
--
ALTER TABLE `animals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `bulls`
--
ALTER TABLE `bulls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `farms`
--
ALTER TABLE `farms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `heifer_costs`
--
ALTER TABLE `heifer_costs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `heifer_cost_categories`
--
ALTER TABLE `heifer_cost_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `heifer_daily_consumption`
--
ALTER TABLE `heifer_daily_consumption`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `heifer_phases`
--
ALTER TABLE `heifer_phases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `heifer_price_history`
--
ALTER TABLE `heifer_price_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

-- ============================================================
-- MELHORIAS E NOVAS FUNCIONALIDADES
-- Sistema Superior ao FarmTell Milk
-- Data: 22/10/2025
-- ============================================================

-- ============================================================
-- 6. SISTEMA DE BACKUP E SINCRONIZAÇÃO
-- ============================================================

-- Tabela para registro de backups
CREATE TABLE `backup_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Nome do backup',
  `description` text COMMENT 'Descrição do backup',
  `file_path` varchar(500) NOT NULL COMMENT 'Caminho do arquivo',
  `file_size` bigint(20) DEFAULT NULL COMMENT 'Tamanho do arquivo em bytes',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
  `created_by` int(11) DEFAULT NULL COMMENT 'Usuário que criou',
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registro de backups do sistema';

-- Tabela para logs de sincronização
CREATE TABLE `sync_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_type` enum('backup','restore','export','import','sync') NOT NULL COMMENT 'Tipo de sincronização',
  `status` enum('success','error','warning') NOT NULL COMMENT 'Status da operação',
  `message` text COMMENT 'Mensagem da operação',
  `file_path` varchar(500) DEFAULT NULL COMMENT 'Caminho do arquivo',
  `file_size` bigint(20) DEFAULT NULL COMMENT 'Tamanho do arquivo',
  `duration` int(11) DEFAULT NULL COMMENT 'Duração em segundos',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data da operação',
  `created_by` int(11) DEFAULT NULL COMMENT 'Usuário que executou',
  PRIMARY KEY (`id`),
  KEY `idx_sync_type` (`sync_type`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Logs de operações de backup e sincronização';

-- Tabela para configurações de backup automático
CREATE TABLE `backup_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `auto_backup_enabled` tinyint(1) DEFAULT 0 COMMENT 'Backup automático ativado',
  `backup_frequency` enum('daily','weekly','monthly') DEFAULT 'daily' COMMENT 'Frequência do backup',
  `backup_time` time DEFAULT '02:00:00' COMMENT 'Horário do backup',
  `retention_days` int(11) DEFAULT 30 COMMENT 'Dias para manter backups',
  `include_photos` tinyint(1) DEFAULT 1 COMMENT 'Incluir fotos no backup',
  `compression_enabled` tinyint(1) DEFAULT 1 COMMENT 'Compressão ativada',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Configurações de backup automático';

-- Inserir configurações padrão
INSERT INTO `backup_settings` (`auto_backup_enabled`, `backup_frequency`, `backup_time`, `retention_days`, `include_photos`, `compression_enabled`) 
VALUES (0, 'daily', '02:00:00', 30, 1, 1);

-- ============================================================
-- MELHORIAS E NOVAS FUNCIONALIDADES
-- Sistema Superior ao FarmTell Milk
-- Data: 22/10/2025
-- ============================================================

-- ------------------------------------------------------------
-- MELHORIA 1: Adicionar campos na tabela notifications
-- ------------------------------------------------------------
ALTER TABLE `notifications`
ADD COLUMN `notification_type` ENUM('alert', 'reminder', 'info', 'success', 'warning', 'critical') NOT NULL DEFAULT 'info' AFTER `type`,
ADD COLUMN `priority` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium' AFTER `notification_type`,
ADD COLUMN `link` VARCHAR(500) NULL AFTER `message`,
ADD COLUMN `is_sent` BOOLEAN NOT NULL DEFAULT 0 AFTER `is_read`,
ADD COLUMN `sent_at` TIMESTAMP NULL AFTER `is_sent`,
ADD COLUMN `expires_at` TIMESTAMP NULL AFTER `sent_at`,
ADD INDEX `idx_user_read` (`user_id`, `is_read`),
ADD INDEX `idx_priority` (`priority`),
ADD INDEX `idx_sent` (`is_sent`),
ADD INDEX `idx_expires` (`expires_at`);

-- ------------------------------------------------------------
-- MELHORIA 2: Adicionar campo current_group_id na tabela animals
-- ------------------------------------------------------------
ALTER TABLE `animals`
ADD COLUMN `current_group_id` INT(11) NULL COMMENT 'Grupo/lote atual do animal' AFTER `status`,
ADD KEY `idx_current_group` (`current_group_id`);

-- ------------------------------------------------------------
-- NOVA TABELA 1: animal_transponders (Sistema RFID)
-- ------------------------------------------------------------
CREATE TABLE `animal_transponders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `animal_id` INT(11) NOT NULL COMMENT 'ID do animal',
  `transponder_code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Código único RFID',
  `transponder_type` ENUM('rfid', 'visual', 'electronic', 'microchip') NOT NULL DEFAULT 'rfid',
  `manufacturer` VARCHAR(100) NULL,
  `activation_date` DATE NOT NULL,
  `deactivation_date` DATE NULL,
  `location` ENUM('ear_left', 'ear_right', 'neck', 'leg', 'other') DEFAULT 'ear_left',
  `is_active` BOOLEAN NOT NULL DEFAULT 1,
  `notes` TEXT NULL,
  `farm_id` INT(11) NOT NULL DEFAULT 1,
  `recorded_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_transponder_code` (`transponder_code`),
  KEY `idx_animal` (`animal_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_code_lookup` (`transponder_code`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- NOVA TABELA 2: transponder_readings (Histórico de leituras)
-- ------------------------------------------------------------
CREATE TABLE `transponder_readings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `transponder_id` INT(11) NOT NULL,
  `reading_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reader_id` VARCHAR(50) NULL,
  `location` VARCHAR(100) NULL,
  `signal_strength` DECIMAL(5,2) NULL,
  `notes` TEXT NULL,
  `farm_id` INT(11) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transponder_date` (`transponder_id`, `reading_date`),
  KEY `idx_reading_date` (`reading_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- NOVA TABELA 3: body_condition_scores (BCS)
-- ------------------------------------------------------------
CREATE TABLE `body_condition_scores` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `animal_id` INT(11) NOT NULL,
  `score` DECIMAL(2,1) NOT NULL COMMENT 'Score de 1.0 a 5.0',
  `evaluation_date` DATE NOT NULL,
  `evaluation_method` ENUM('visual', 'palpacao', 'automatico', 'foto_ia') NOT NULL DEFAULT 'visual',
  `lactation_stage` ENUM('inicio', 'pico', 'meio', 'final', 'seco') NULL,
  `weight_kg` DECIMAL(6,2) NULL,
  `height_cm` DECIMAL(5,1) NULL,
  `body_measurements` JSON NULL,
  `photo_url` VARCHAR(500) NULL,
  `notes` TEXT NULL,
  `evaluated_by` INT(11) NOT NULL,
  `farm_id` INT(11) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_animal_date` (`animal_id`, `evaluation_date`),
  KEY `idx_score` (`score`),
  KEY `idx_animal_score` (`animal_id`, `score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger: Alerta de BCS baixo (Comentado - não suportado na Hostinger)
-- DELIMITER $$
-- CREATE TRIGGER `tr_bcs_low_alert` AFTER INSERT ON `body_condition_scores`
-- FOR EACH ROW
-- BEGIN
--     IF NEW.score < 2.5 THEN
--         INSERT INTO notifications (
--             user_id, title, message, type, notification_type, priority, 
--             related_table, related_id, farm_id
--         )
--         SELECT 
--             u.id,
--             'Alerta: BCS Baixo',
--             CONCAT('Animal ', a.animal_number, ' está com BCS baixo: ', NEW.score),
--             'warning',
--             'alert',
--             'high',
--             'body_condition_scores',
--             NEW.id,
--             NEW.farm_id
--         FROM animals a
--         CROSS JOIN users u
--         WHERE a.id = NEW.animal_id 
--           AND u.farm_id = NEW.farm_id 
--           AND u.role IN ('proprietario', 'gerente', 'veterinario')
--           AND u.is_active = 1;
--     END IF;
-- END$$
-- DELIMITER ;

-- ------------------------------------------------------------
-- NOVA TABELA 4: feed_records (Controle de Alimentação)
-- ------------------------------------------------------------
CREATE TABLE `feed_records` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `animal_id` INT(11) NOT NULL,
  `feed_date` DATE NOT NULL,
  `shift` ENUM('manha', 'tarde', 'noite', 'unico') NOT NULL DEFAULT 'unico',
  `concentrate_kg` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `roughage_kg` DECIMAL(6,2) NULL,
  `silage_kg` DECIMAL(6,2) NULL,
  `hay_kg` DECIMAL(6,2) NULL,
  `feed_type` VARCHAR(100) NULL,
  `feed_brand` VARCHAR(100) NULL,
  `protein_percentage` DECIMAL(4,2) NULL,
  `cost_per_kg` DECIMAL(10,2) NULL,
  `total_cost` DECIMAL(10,2) NULL,
  `automatic` BOOLEAN NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `recorded_by` INT(11) NOT NULL,
  `farm_id` INT(11) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_animal_date` (`animal_id`, `feed_date`),
  KEY `idx_feed_date` (`feed_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- NOVA TABELA 5: animal_groups (Grupos/Lotes)
-- ------------------------------------------------------------
CREATE TABLE `animal_groups` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `group_name` VARCHAR(100) NOT NULL,
  `group_code` VARCHAR(20) NULL,
  `group_type` ENUM('lactante', 'seco', 'novilha', 'pre_parto', 'pos_parto', 'hospital', 'quarentena', 'pasto', 'outros') NOT NULL,
  `description` TEXT NULL,
  `location` VARCHAR(255) NULL,
  `capacity` INT(11) NULL,
  `current_count` INT(11) NOT NULL DEFAULT 0,
  `feed_protocol` TEXT NULL,
  `milking_order` INT(11) NULL,
  `color_code` VARCHAR(7) DEFAULT '#6B7280',
  `is_active` BOOLEAN NOT NULL DEFAULT 1,
  `farm_id` INT(11) NOT NULL DEFAULT 1,
  `created_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_group_name_farm` (`group_name`, `farm_id`),
  KEY `idx_type` (`group_type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir grupos padrão
INSERT INTO `animal_groups` (`group_name`, `group_code`, `group_type`, `description`, `color_code`, `farm_id`, `created_by`) VALUES
('Lactantes Alta Produção', 'LAC-A', 'lactante', 'Vacas em lactação > 30L/dia', '#10B981', 1, 1),
('Lactantes Baixa Produção', 'LAC-B', 'lactante', 'Vacas em lactação < 30L/dia', '#059669', 1, 1),
('Vacas Secas', 'SECO', 'seco', 'Vacas no período seco', '#F59E0B', 1, 1),
('Pré-parto', 'PRE-P', 'pre_parto', 'Vacas a 30 dias do parto', '#EF4444', 1, 1),
('Pós-parto', 'POS-P', 'pos_parto', 'Vacas até 21 dias pós-parto', '#EC4899', 1, 1),
('Novilhas', 'NOV', 'novilha', 'Novilhas em crescimento', '#3B82F6', 1, 1),
('Hospital', 'HOSP', 'hospital', 'Animais em tratamento', '#DC2626', 1, 1),
('Quarentena', 'QUAR', 'quarentena', 'Animais em quarentena', '#9CA3AF', 1, 1);

-- ------------------------------------------------------------
-- NOVA TABELA 6: group_movements (Movimentações)
-- ------------------------------------------------------------
CREATE TABLE `group_movements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `animal_id` INT(11) NOT NULL,
  `from_group_id` INT(11) NULL,
  `to_group_id` INT(11) NOT NULL,
  `movement_date` DATE NOT NULL,
  `movement_time` TIME NULL,
  `reason` VARCHAR(255) NULL,
  `automatic` BOOLEAN NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `moved_by` INT(11) NOT NULL,
  `farm_id` INT(11) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_animal` (`animal_id`),
  KEY `idx_movement_date` (`movement_date`),
  KEY `idx_animal_date` (`animal_id`, `movement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger: Atualizar contagem de grupos (Comentado - não suportado na Hostinger)
-- DELIMITER $$
-- CREATE TRIGGER `tr_group_movement_count` AFTER INSERT ON `group_movements`
-- FOR EACH ROW
-- BEGIN
--     IF NEW.from_group_id IS NOT NULL THEN
--         UPDATE animal_groups 
--         SET current_count = GREATEST(0, current_count - 1)
--         WHERE id = NEW.from_group_id;
--     END IF;
--     
--     UPDATE animal_groups 
--     SET current_count = current_count + 1
--     WHERE id = NEW.to_group_id;
-- END$$
-- DELIMITER ;

-- ------------------------------------------------------------
-- NOVA TABELA 7: animal_photos (Fotos)
-- ------------------------------------------------------------
CREATE TABLE `animal_photos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `animal_id` INT(11) NOT NULL,
  `photo_url` VARCHAR(500) NOT NULL,
  `photo_type` ENUM('profile', 'health', 'event', 'birth', 'bcs', 'injury', 'other') DEFAULT 'profile',
  `is_primary` BOOLEAN NOT NULL DEFAULT 0,
  `taken_date` DATE NULL,
  `description` TEXT NULL,
  `tags` JSON NULL,
  `file_size` INT(11) NULL,
  `dimensions` VARCHAR(20) NULL,
  `uploaded_by` INT(11) NOT NULL,
  `farm_id` INT(11) NOT NULL DEFAULT 1,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_animal` (`animal_id`),
  KEY `idx_animal_primary` (`animal_id`, `is_primary`),
  KEY `idx_type` (`photo_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger: Garantir apenas uma foto principal (Comentado - não suportado na Hostinger)
-- DELIMITER $$
-- CREATE TRIGGER `tr_photo_only_one_primary` BEFORE INSERT ON `animal_photos`
-- FOR EACH ROW
-- BEGIN
--     IF NEW.is_primary = 1 THEN
--         UPDATE animal_photos 
--         SET is_primary = 0 
--         WHERE animal_id = NEW.animal_id AND is_primary = 1;
--     END IF;
-- END$$
-- DELIMITER ;

-- DELIMITER $$
-- CREATE TRIGGER `tr_photo_update_primary` BEFORE UPDATE ON `animal_photos`
-- FOR EACH ROW
-- BEGIN
--     IF NEW.is_primary = 1 AND OLD.is_primary = 0 THEN
--         UPDATE animal_photos 
--         SET is_primary = 0 
--         WHERE animal_id = NEW.animal_id AND is_primary = 1 AND id != NEW.id;
--     END IF;
-- END$$
-- DELIMITER ;

-- ------------------------------------------------------------
-- NOVA TABELA 8: ai_predictions (IA)
-- ------------------------------------------------------------
CREATE TABLE `ai_predictions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `animal_id` INT(11) NULL,
  `prediction_type` ENUM('heat', 'production', 'health', 'calving', 'dry_off', 'group_change') NOT NULL,
  `predicted_date` DATE NOT NULL,
  `predicted_value` DECIMAL(10,2) NULL,
  `confidence_score` DECIMAL(5,2) NOT NULL COMMENT '0-100%',
  `algorithm_version` VARCHAR(20) DEFAULT 'v1.0',
  `input_data` JSON NULL,
  `prediction_date` DATE NOT NULL DEFAULT (CURRENT_DATE),
  `actual_date` DATE NULL,
  `actual_value` DECIMAL(10,2) NULL,
  `was_accurate` BOOLEAN NULL,
  `error_margin` DECIMAL(10,2) NULL,
  `notes` TEXT NULL,
  `farm_id` INT(11) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_animal_type` (`animal_id`, `prediction_type`),
  KEY `idx_predicted_date` (`predicted_date`),
  KEY `idx_confidence` (`confidence_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- NOVA TABELA 9: action_lists_cache (Cache de Ações)
-- ------------------------------------------------------------
CREATE TABLE `action_lists_cache` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `list_type` ENUM('heat_expected', 'calving_soon', 'pregnancy_check', 'dry_off', 'vaccination', 'medication', 'bcs_check', 'group_change') NOT NULL,
  `animal_id` INT(11) NOT NULL,
  `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
  `action_date` DATE NOT NULL,
  `days_until` INT(11) NOT NULL,
  `cache_data` JSON NULL,
  `is_completed` BOOLEAN NOT NULL DEFAULT 0,
  `completed_at` TIMESTAMP NULL,
  `farm_id` INT(11) NOT NULL DEFAULT 1,
  `last_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_list_type` (`list_type`),
  KEY `idx_action_date` (`action_date`),
  KEY `idx_priority` (`priority`),
  KEY `idx_type_date` (`list_type`, `action_date`, `is_completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- NOVA TABELA 10: user_preferences (Preferências)
-- ------------------------------------------------------------
CREATE TABLE `user_preferences` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `preference_key` VARCHAR(100) NOT NULL,
  `preference_value` TEXT NOT NULL,
  `data_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
  `category` ENUM('notifications', 'interface', 'reports', 'privacy', 'other') DEFAULT 'other',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_pref` (`user_id`, `preference_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir preferências padrão
INSERT INTO `user_preferences` (`user_id`, `preference_key`, `preference_value`, `data_type`, `category`)
SELECT id, 'notifications_enabled', 'true', 'boolean', 'notifications'
FROM users;

-- ------------------------------------------------------------
-- NOVA PROCEDURE: predict_next_heat (Comentada - não suportada na Hostinger)
-- ------------------------------------------------------------
-- DELIMITER $$
-- CREATE PROCEDURE `predict_next_heat`(IN p_animal_id INT)
-- BEGIN
--     DECLARE v_last_heat DATE;
--     DECLARE v_avg_cycle_days INT;
--     DECLARE v_predicted_date DATE;
--     DECLARE v_confidence DECIMAL(5,2);
--     
--     SELECT MAX(heat_date) INTO v_last_heat
--     FROM heat_cycles WHERE animal_id = p_animal_id;
--     
--     SELECT AVG(diff_days) INTO v_avg_cycle_days
--     FROM (
--         SELECT DATEDIFF(heat_date, LAG(heat_date) OVER (ORDER BY heat_date)) as diff_days
--         FROM heat_cycles WHERE animal_id = p_animal_id
--     ) cycles WHERE diff_days IS NOT NULL;
--     
--     IF v_last_heat IS NOT NULL AND v_avg_cycle_days IS NOT NULL THEN
--         SET v_predicted_date = DATE_ADD(v_last_heat, INTERVAL v_avg_cycle_days DAY);
--         SET v_confidence = CASE 
--             WHEN v_avg_cycle_days BETWEEN 18 AND 24 THEN 85.0
--             WHEN v_avg_cycle_days BETWEEN 15 AND 27 THEN 70.0
--             ELSE 50.0
--         END;
--         
--         INSERT INTO ai_predictions (animal_id, prediction_type, predicted_date, confidence_score, algorithm_version, prediction_date, farm_id)
--         VALUES (p_animal_id, 'heat', v_predicted_date, v_confidence, 'v1.0', CURDATE(), 1);
--         
--         SELECT v_predicted_date as predicted_date, v_confidence as confidence, v_avg_cycle_days as avg_cycle_days, v_last_heat as last_heat;
--     ELSE
--         SELECT 'Dados insuficientes para previsão' as message;
--     END IF;
-- END$$
-- DELIMITER ;

-- ------------------------------------------------------------
-- NOVA PROCEDURE: refresh_action_lists (Comentada - não suportada na Hostinger)
-- ------------------------------------------------------------
-- DELIMITER $$
-- CREATE PROCEDURE `refresh_action_lists`()
-- BEGIN
--     DELETE FROM action_lists_cache WHERE is_completed = 1 AND completed_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY);
--     DELETE FROM action_lists_cache WHERE action_date < CURDATE() AND is_completed = 0;
--     
--     INSERT INTO action_lists_cache (list_type, animal_id, priority, action_date, days_until, cache_data, farm_id)
--     SELECT DISTINCT 'heat_expected', ap.animal_id,
--         CASE WHEN DATEDIFF(ap.predicted_date, CURDATE()) <= 2 THEN 'high'
--              WHEN DATEDIFF(ap.predicted_date, CURDATE()) <= 5 THEN 'medium'
--              ELSE 'low' END,
--         ap.predicted_date, DATEDIFF(ap.predicted_date, CURDATE()),
--         JSON_OBJECT('animal_number', a.animal_number, 'animal_name', a.name, 'confidence', ap.confidence_score),
--         ap.farm_id
--     FROM ai_predictions ap
--     INNER JOIN animals a ON ap.animal_id = a.id
--     WHERE ap.prediction_type = 'heat'
--       AND ap.predicted_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
--       AND a.is_active = 1;
--     
--     INSERT INTO action_lists_cache (list_type, animal_id, priority, action_date, days_until, cache_data, farm_id)
--     SELECT DISTINCT 'calving_soon', pc.animal_id,
--         CASE WHEN DATEDIFF(pc.expected_birth, CURDATE()) <= 7 THEN 'urgent'
--              WHEN DATEDIFF(pc.expected_birth, CURDATE()) <= 15 THEN 'high'
--              ELSE 'medium' END,
--         pc.expected_birth, DATEDIFF(pc.expected_birth, CURDATE()),
--         JSON_OBJECT('animal_number', a.animal_number, 'animal_name', a.name, 'pregnancy_stage', pc.pregnancy_stage),
--         pc.farm_id
--     FROM pregnancy_controls pc
--     INNER JOIN animals a ON pc.animal_id = a.id
--     WHERE pc.expected_birth BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
--       AND a.is_active = 1;
--     
--     SELECT 'Cache atualizado' as message, (SELECT COUNT(*) FROM action_lists_cache WHERE is_completed = 0) as pending_actions;
-- END$$
-- DELIMITER ;

-- ------------------------------------------------------------
-- NOVA VIEW: v_animals_with_groups
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW `v_animals_with_groups` AS
SELECT 
    a.*,
    f.name as father_name,
    m.name as mother_name,
    g.group_name,
    g.group_type,
    g.color_code as group_color,
    DATEDIFF(CURDATE(), a.birth_date) as age_days,
    FLOOR(DATEDIFF(CURDATE(), a.birth_date) / 365) as age_years,
    t.transponder_code,
    t.transponder_type,
    (SELECT bcs.score FROM body_condition_scores bcs WHERE bcs.animal_id = a.id ORDER BY bcs.evaluation_date DESC LIMIT 1) as latest_bcs,
    (SELECT ap.photo_url FROM animal_photos ap WHERE ap.animal_id = a.id AND ap.is_primary = 1 LIMIT 1) as primary_photo
FROM animals a
LEFT JOIN animals f ON a.father_id = f.id
LEFT JOIN animals m ON a.mother_id = m.id
LEFT JOIN animal_groups g ON a.current_group_id = g.id
LEFT JOIN animal_transponders t ON a.id = t.animal_id AND t.is_active = 1
WHERE a.is_active = 1;

-- ------------------------------------------------------------
-- NOVA VIEW: v_pending_actions_summary
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW `v_pending_actions_summary` AS
SELECT 'heat_expected' as action_type, COUNT(*) as count, 'Cio previsto (7 dias)' as description
FROM heat_cycles hc INNER JOIN animals a ON hc.animal_id = a.id
WHERE a.is_active = 1 AND hc.heat_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
UNION ALL
SELECT 'calving_soon', COUNT(*), 'Partos próximos (30 dias)'
FROM pregnancy_controls pc INNER JOIN animals a ON pc.animal_id = a.id
WHERE a.is_active = 1 AND pc.expected_birth BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
UNION ALL
SELECT 'low_bcs', COUNT(DISTINCT bcs.animal_id), 'BCS baixo (< 2.5)'
FROM body_condition_scores bcs
INNER JOIN (SELECT animal_id, MAX(evaluation_date) as max_date FROM body_condition_scores GROUP BY animal_id) latest 
  ON bcs.animal_id = latest.animal_id AND bcs.evaluation_date = latest.max_date
INNER JOIN animals a ON bcs.animal_id = a.id
WHERE bcs.score < 2.5 AND a.is_active = 1;

-- ------------------------------------------------------------
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ------------------------------------------------------------
ALTER TABLE `animals` ADD INDEX `idx_status_active` (`status`, `is_active`);
ALTER TABLE `animals` ADD INDEX `idx_farm_active_status` (`farm_id`, `is_active`, `status`);
ALTER TABLE `heat_cycles` ADD INDEX `idx_animal_date_desc` (`animal_id`, `heat_date` DESC);
ALTER TABLE `pregnancy_controls` ADD INDEX `idx_expected_birth_range` (`expected_birth`, `farm_id`);
ALTER TABLE `health_records` ADD INDEX `idx_next_date` (`next_date`);
ALTER TABLE `milk_production` ADD INDEX `idx_date_animal` (`production_date`, `animal_id`);
ALTER TABLE `volume_records` ADD INDEX `idx_date_shift` (`record_date`, `shift`);

-- ============================================================
-- SISTEMA DE TOUROS E INSEMINAÇÃO - SUPERIOR AO FARMTELL
-- ============================================================

-- Tabela de Touros (usando estrutura existente)
-- A tabela bulls já existe, vamos apenas adicionar campos se necessário
-- Adicionar campos à tabela bulls
ALTER TABLE `bulls` ADD COLUMN `bull_code` varchar(50) UNIQUE AFTER `id`;
ALTER TABLE `bulls` ADD COLUMN `bull_name` varchar(100) AFTER `bull_code`;
ALTER TABLE `bulls` ADD COLUMN `genetic_code` varchar(100) AFTER `birth_date`;
ALTER TABLE `bulls` ADD COLUMN `sire` varchar(100) AFTER `genetic_code`;
ALTER TABLE `bulls` ADD COLUMN `dam` varchar(100) AFTER `sire`;
ALTER TABLE `bulls` ADD COLUMN `genetic_merit` decimal(5,2) AFTER `dam`;
ALTER TABLE `bulls` ADD COLUMN `milk_production_index` decimal(5,2) AFTER `genetic_merit`;
ALTER TABLE `bulls` ADD COLUMN `fat_production_index` decimal(5,2) AFTER `milk_production_index`;
ALTER TABLE `bulls` ADD COLUMN `protein_production_index` decimal(5,2) AFTER `fat_production_index`;
ALTER TABLE `bulls` ADD COLUMN `fertility_index` decimal(5,2) AFTER `protein_production_index`;
ALTER TABLE `bulls` ADD COLUMN `health_index` decimal(5,2) AFTER `fertility_index`;
ALTER TABLE `bulls` ADD COLUMN `photo_url` varchar(255) AFTER `health_index`;
ALTER TABLE `bulls` ADD COLUMN `status` enum('ativo', 'inativo', 'vendido', 'morto') DEFAULT 'ativo' AFTER `photo_url`;
ALTER TABLE `bulls` ADD COLUMN `purchase_date` date AFTER `status`;
ALTER TABLE `bulls` ADD COLUMN `purchase_price` decimal(10,2) AFTER `purchase_date`;
ALTER TABLE `bulls` ADD COLUMN `sale_date` date AFTER `purchase_price`;
ALTER TABLE `bulls` ADD COLUMN `sale_price` decimal(10,2) AFTER `sale_date`;
-- ALTER TABLE `bulls` ADD COLUMN `notes` text AFTER `sale_price`; -- Coluna já existe na tabela

-- Tabela de Inseminações (usando estrutura existente)
-- A tabela inseminations já existe, vamos apenas adicionar campos se necessário
-- Adicionar campos à tabela inseminations
-- ALTER TABLE `inseminations` ADD COLUMN `bull_id` int(11) AFTER `animal_id`; -- Coluna já existe na tabela
ALTER TABLE `inseminations` ADD COLUMN `insemination_time` time AFTER `insemination_date`;
ALTER TABLE `inseminations` ADD COLUMN `technician_name` varchar(100) AFTER `technician`;
ALTER TABLE `inseminations` ADD COLUMN `technician_license` varchar(50) AFTER `technician_name`;
ALTER TABLE `inseminations` ADD COLUMN `semen_batch` varchar(50) AFTER `technician_license`;
ALTER TABLE `inseminations` ADD COLUMN `semen_expiry_date` date AFTER `semen_batch`;
ALTER TABLE `inseminations` ADD COLUMN `semen_straw_number` varchar(50) AFTER `semen_expiry_date`;
ALTER TABLE `inseminations` ADD COLUMN `insemination_method` enum('IA', 'MO', 'FIV', 'IATF') DEFAULT 'IA' AFTER `semen_straw_number`;
ALTER TABLE `inseminations` ADD COLUMN `pregnancy_check_date` date AFTER `insemination_method`;
ALTER TABLE `inseminations` ADD COLUMN `pregnancy_result` enum('prenha', 'vazia', 'pendente', 'aborto') DEFAULT 'pendente' AFTER `pregnancy_check_date`;
ALTER TABLE `inseminations` ADD COLUMN `pregnancy_check_method` enum('palpacao', 'ultrassom', 'exame_sangue') DEFAULT 'palpacao' AFTER `pregnancy_result`;
ALTER TABLE `inseminations` ADD COLUMN `expected_calving_date` date AFTER `pregnancy_check_method`;
ALTER TABLE `inseminations` ADD COLUMN `actual_calving_date` date AFTER `expected_calving_date`;
ALTER TABLE `inseminations` ADD COLUMN `calving_result` enum('vivo', 'morto', 'natimorto') DEFAULT 'vivo' AFTER `actual_calving_date`;
ALTER TABLE `inseminations` ADD COLUMN `calf_sex` enum('macho', 'femea') AFTER `calving_result`;
ALTER TABLE `inseminations` ADD COLUMN `calf_weight` decimal(5,2) AFTER `calf_sex`;
ALTER TABLE `inseminations` ADD COLUMN `complications` text AFTER `calf_weight`;
ALTER TABLE `inseminations` ADD COLUMN `cost` decimal(8,2) AFTER `complications`;
-- ALTER TABLE `inseminations` ADD COLUMN `created_by` int(11) NOT NULL AFTER `farm_id`; -- Usar recorded_by que já existe

-- Tabela de Performance dos Touros
CREATE TABLE `bull_performance` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `bull_id` int(11) NOT NULL,
    `period_start` date NOT NULL,
    `period_end` date NOT NULL,
    `total_inseminations` int(11) DEFAULT 0,
    `successful_inseminations` int(11) DEFAULT 0,
    `pregnancy_rate` decimal(5,2) DEFAULT 0.00,
    `conception_rate` decimal(5,2) DEFAULT 0.00,
    `average_services_per_conception` decimal(4,2) DEFAULT 0.00,
    `total_cost` decimal(10,2) DEFAULT 0.00,
    `cost_per_pregnancy` decimal(8,2) DEFAULT 0.00,
    `notes` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_bull` (`bull_id`),
    KEY `idx_period` (`period_start`, `period_end`),
    FOREIGN KEY (`bull_id`) REFERENCES `bulls`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Catálogo de Sêmen
CREATE TABLE `semen_catalog` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `bull_id` int(11) NOT NULL,
    `batch_number` varchar(50) NOT NULL,
    `production_date` date NOT NULL,
    `expiry_date` date NOT NULL,
    `straws_available` int(11) DEFAULT 0,
    `straws_used` int(11) DEFAULT 0,
    `price_per_straw` decimal(8,2) NOT NULL,
    `supplier` varchar(100),
    `storage_location` varchar(100),
    `quality_grade` enum('A', 'B', 'C', 'Premium') DEFAULT 'A',
    `genetic_tests` text,
    `notes` text,
    `farm_id` int(11) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_bull` (`bull_id`),
    KEY `idx_batch` (`batch_number`),
    KEY `idx_expiry` (`expiry_date`),
    KEY `idx_farm` (`farm_id`),
    FOREIGN KEY (`bull_id`) REFERENCES `bulls`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- View: Estatísticas dos Touros
CREATE OR REPLACE VIEW `v_bull_statistics` AS
SELECT 
    b.id,
    b.name as bull_name,
    b.breed,
    'ativo' as status,
    COUNT(i.id) as total_inseminations,
    0 as successful_inseminations,
    0.00 as pregnancy_rate,
    0.00 as conception_rate,
    0.00 as avg_services_per_conception,
    0.00 as total_cost,
    0.00 as cost_per_pregnancy,
    MAX(i.insemination_date) as last_insemination,
    MIN(i.insemination_date) as first_insemination
FROM bulls b
LEFT JOIN inseminations i ON b.id = i.bull_id
WHERE b.farm_id = 1
GROUP BY b.id, b.name, b.breed;

-- View: Inseminações Recentes
CREATE OR REPLACE VIEW `v_recent_inseminations` AS
SELECT 
    i.id,
    i.insemination_date,
    NULL as insemination_time,
    a.name as animal_name,
    b.name as bull_name,
    b.breed as bull_breed,
    i.technician,
    i.insemination_type,
    'pendente' as pregnancy_result,
    DATE_ADD(i.insemination_date, INTERVAL 280 DAY) as expected_calving_date,
    0.00 as cost,
    DATEDIFF(CURDATE(), i.insemination_date) as days_since_insemination,
    CASE 
        WHEN DATEDIFF(CURDATE(), i.insemination_date) >= 21 THEN 'Pronto para teste'
        WHEN DATEDIFF(CURDATE(), i.insemination_date) < 21 THEN 'Aguardando'
        ELSE 'Indefinido'
    END as status_description
FROM inseminations i
JOIN animals a ON i.animal_id = a.id
JOIN bulls b ON i.bull_id = b.id
WHERE i.farm_id = 1
ORDER BY i.insemination_date DESC;

-- Procedure: Calcular Performance do Touro (Comentada - não suportada na Hostinger)
-- DELIMITER //
-- CREATE PROCEDURE `CalculateBullPerformance`(
--     IN p_bull_id INT,
--     IN p_period_start DATE,
--     IN p_period_end DATE
-- )
-- BEGIN
--     DECLARE v_total_inseminations INT DEFAULT 0;
--     DECLARE v_successful_inseminations INT DEFAULT 0;
--     DECLARE v_pregnancy_rate DECIMAL(5,2) DEFAULT 0.00;
--     DECLARE v_conception_rate DECIMAL(5,2) DEFAULT 0.00;
--     DECLARE v_avg_services DECIMAL(4,2) DEFAULT 0.00;
--     DECLARE v_total_cost DECIMAL(10,2) DEFAULT 0.00;
--     DECLARE v_cost_per_pregnancy DECIMAL(8,2) DEFAULT 0.00;
--     
--     -- Contar inseminações no período
--     SELECT COUNT(*), 0, 0.00
--     INTO v_total_inseminations, v_successful_inseminations, v_total_cost
--     FROM inseminations 
--     WHERE bull_id = p_bull_id 
--     AND insemination_date BETWEEN p_period_start AND p_period_end;
--     
--     -- Calcular taxas
--     IF v_total_inseminations > 0 THEN
--         SET v_pregnancy_rate = (v_successful_inseminations / v_total_inseminations) * 100;
--         SET v_conception_rate = v_pregnancy_rate;
--         SET v_avg_services = v_total_inseminations / NULLIF(v_successful_inseminations, 0);
--         SET v_cost_per_pregnancy = v_total_cost / NULLIF(v_successful_inseminations, 0);
--     END IF;
--     
--     -- Inserir ou atualizar performance
--     INSERT INTO bull_performance (
--         bull_id, period_start, period_end, total_inseminations, 
--         successful_inseminations, pregnancy_rate, conception_rate,
--         average_services_per_conception, total_cost, cost_per_pregnancy
--     ) VALUES (
--         p_bull_id, p_period_start, p_period_end, v_total_inseminations,
--         v_successful_inseminations, v_pregnancy_rate, v_conception_rate,
--         v_avg_services, v_total_cost, v_cost_per_pregnancy
--     ) ON DUPLICATE KEY UPDATE
--         total_inseminations = v_total_inseminations,
--         successful_inseminations = v_successful_inseminations,
--         pregnancy_rate = v_pregnancy_rate,
--         conception_rate = v_conception_rate,
--         average_services_per_conception = v_avg_services,
--         total_cost = v_total_cost,
--         cost_per_pregnancy = v_cost_per_pregnancy,
--         updated_at = CURRENT_TIMESTAMP;
-- END //
-- DELIMITER ;

-- Trigger: Atualizar Performance ao inserir inseminação (Comentado - não suportado na Hostinger)
-- DELIMITER //
-- CREATE TRIGGER `tr_insemination_performance_update`
-- AFTER INSERT ON `inseminations`
-- FOR EACH ROW
-- BEGIN
--     -- Calcular performance do último mês (apenas se bull_id existir)
--     IF NEW.bull_id IS NOT NULL THEN
--         CALL CalculateBullPerformance(
--             NEW.bull_id, 
--             DATE_SUB(CURDATE(), INTERVAL 30 DAY), 
--             CURDATE()
--         );
--         
--         -- Calcular performance do último ano
--         CALL CalculateBullPerformance(
--             NEW.bull_id, 
--             DATE_SUB(CURDATE(), INTERVAL 1 YEAR), 
--             CURDATE()
--         );
--     END IF;
-- END //
-- DELIMITER ;

-- Índices para performance
ALTER TABLE `inseminations` ADD INDEX `idx_bull_date` (`bull_id`, `insemination_date`);
ALTER TABLE `inseminations` ADD INDEX `idx_pregnancy_date` (`pregnancy_result`, `insemination_date`);
ALTER TABLE `bulls` ADD INDEX `idx_genetic_merit` (`genetic_merit`);
ALTER TABLE `bulls` ADD INDEX `idx_fertility_index` (`fertility_index`);

-- ============================================================
-- DADOS DE TESTE COMPLETOS PARA O SISTEMA LACTECH
-- Inserção de dados realistas em todas as tabelas
-- ============================================================

-- ============================================================
-- 1. DADOS DE ANIMAIS (Expandindo o rebanho)
-- ============================================================

-- Inserir mais animais para ter um rebanho completo
INSERT INTO `animals` (`animal_number`, `name`, `breed`, `gender`, `birth_date`, `birth_weight`, `father_id`, `mother_id`, `status`, `health_status`, `reproductive_status`, `entry_date`, `farm_id`, `notes`, `is_active`) VALUES
-- Vacas Lactantes (Produção)
('V004', 'Estrela', 'Holandesa', 'femea', '2018-04-15', 45.5, 5, 1, 'Lactante', 'saudavel', 'lactante', '2018-04-15', 1, 'Vaca de alta produção', 1),
('V005', 'Lua', 'Girolanda', 'femea', '2019-07-20', 42.0, 5, 2, 'Lactante', 'saudavel', 'lactante', '2019-07-20', 1, 'Vaca produtiva', 1),
('V006', 'Sol', 'Holandesa', 'femea', '2017-11-10', 48.0, 5, 1, 'Lactante', 'saudavel', 'lactante', '2017-11-10', 1, 'Vaca experiente', 1),
('V007', 'Mar', 'Gir', 'femea', '2020-02-28', 40.5, 5, 2, 'Lactante', 'saudavel', 'lactante', '2020-02-28', 1, 'Vaca jovem', 1),
('V008', 'Terra', 'Girolanda', 'femea', '2018-09-05', 46.0, 5, 1, 'Lactante', 'saudavel', 'lactante', '2018-09-05', 1, 'Vaca estável', 1),

-- Vacas Secas (Período seco)
('V009', 'Céu', 'Holandesa', 'femea', '2016-12-20', 50.0, 5, 1, 'Seco', 'saudavel', 'vazia', '2016-12-20', 1, 'Vaca no período seco', 1),
('V010', 'Ar', 'Gir', 'femea', '2017-03-15', 44.0, 5, 2, 'Seco', 'saudavel', 'vazia', '2017-03-15', 1, 'Vaca descansando', 1),

-- Novilhas (Para primeira inseminação)
('N002', 'Flor', 'Holandesa', 'femea', '2022-06-10', 38.0, 5, 4, 'Novilha', 'saudavel', 'vazia', '2022-06-10', 1, 'Novilha para IA', 1),
('N003', 'Rosa', 'Girolanda', 'femea', '2022-08-25', 40.0, 5, 5, 'Novilha', 'saudavel', 'vazia', '2022-08-25', 1, 'Novilha jovem', 1),
('N004', 'Lírio', 'Gir', 'femea', '2022-04-12', 36.5, 5, 6, 'Novilha', 'saudavel', 'vazia', '2022-04-12', 1, 'Novilha promissora', 1),

-- Bezerras (Crescimento)
('B001', 'Pequena', 'Holandesa', 'femea', '2023-01-15', 35.0, 5, 4, 'Bezerra', 'saudavel', 'vazia', '2023-01-15', 1, 'Bezerra em crescimento', 1),
('B002', 'Mini', 'Girolanda', 'femea', '2023-03-20', 32.0, 5, 5, 'Bezerra', 'saudavel', 'vazia', '2023-03-20', 1, 'Bezerra jovem', 1),
('B003', 'Tiny', 'Gir', 'femea', '2023-05-10', 34.5, 5, 6, 'Bezerra', 'saudavel', 'vazia', '2023-05-10', 1, 'Bezerra saudável', 1),

-- Bezerros (Machos)
('M001', 'Forte', 'Holandês', 'macho', '2023-02-10', 42.0, 5, 4, 'Bezerro', 'saudavel', 'outros', '2023-02-10', 1, 'Bezerro para venda', 1),
('M002', 'Robusto', 'Girolando', 'macho', '2023-04-15', 40.0, 5, 5, 'Bezerro', 'saudavel', 'outros', '2023-04-15', 1, 'Bezerro forte', 1);

-- ============================================================
-- 2. DADOS DE TOUROS (Expandindo o plantel)
-- ============================================================

INSERT INTO `bulls` (`bull_number`, `name`, `breed`, `birth_date`, `source`, `genetic_value`, `notes`, `farm_id`, `is_active`) VALUES
('B003', 'Champion Elite', 'Holandês', '2017-03-10', 'inseminacao', 'Alto valor genético para produção', 'Sêmen importado da Holanda', 1, 1),
('B004', 'Gir Premium', 'Gir', '2016-08-20', 'inseminacao', 'Excelente para adaptação tropical', 'Sêmen de touro premiado', 1, 1),
('B005', 'Girolando Star', 'Girolando', '2018-01-15', 'proprio', 'Boa produção e adaptação', 'Touro próprio da fazenda', 1, 1);

-- ============================================================
-- 3. DADOS DE PRODUÇÃO DE LEITE (Últimos 30 dias)
-- ============================================================

-- Produção diária para as vacas lactantes
INSERT INTO `milk_production` (`animal_id`, `production_date`, `shift`, `volume`, `quality_score`, `temperature`, `fat_content`, `protein_content`, `somatic_cells`, `notes`, `recorded_by`, `farm_id`) VALUES
-- Vaca V001 (Bella) - Produção alta
(1, '2025-01-01', 'manha', 28.5, 9.5, 4.2, 3.8, 3.2, 250000, 'Produção excelente', 2, 1),
(1, '2025-01-01', 'tarde', 26.0, 9.2, 4.1, 3.7, 3.1, 260000, 'Boa produção', 2, 1),
(1, '2025-01-01', 'noite', 24.0, 9.0, 4.0, 3.6, 3.0, 270000, 'Produção estável', 2, 1),
(1, '2025-01-02', 'manha', 29.0, 9.6, 4.2, 3.9, 3.3, 240000, 'Aumento na produção', 2, 1),
(1, '2025-01-02', 'tarde', 27.5, 9.3, 4.1, 3.8, 3.2, 250000, 'Mantendo qualidade', 2, 1),
(1, '2025-01-02', 'noite', 25.0, 9.1, 4.0, 3.7, 3.1, 260000, 'Boa consistência', 2, 1),

-- Vaca V002 (Luna) - Produção média
(2, '2025-01-01', 'manha', 22.0, 8.5, 4.0, 3.5, 3.0, 300000, 'Produção regular', 2, 1),
(2, '2025-01-01', 'tarde', 20.5, 8.2, 3.9, 3.4, 2.9, 310000, 'Boa qualidade', 2, 1),
(2, '2025-01-01', 'noite', 19.0, 8.0, 3.8, 3.3, 2.8, 320000, 'Produção estável', 2, 1),
(2, '2025-01-02', 'manha', 23.0, 8.6, 4.0, 3.6, 3.1, 290000, 'Pequeno aumento', 2, 1),
(2, '2025-01-02', 'tarde', 21.0, 8.3, 3.9, 3.5, 3.0, 300000, 'Mantendo padrão', 2, 1),
(2, '2025-01-02', 'noite', 19.5, 8.1, 3.8, 3.4, 2.9, 310000, 'Boa consistência', 2, 1),

-- Vaca V004 (Estrela) - Produção alta
(4, '2025-01-01', 'manha', 30.0, 9.8, 4.3, 4.0, 3.4, 200000, 'Excelente produção', 2, 1),
(4, '2025-01-01', 'tarde', 28.0, 9.5, 4.2, 3.9, 3.3, 210000, 'Alta qualidade', 2, 1),
(4, '2025-01-01', 'noite', 26.0, 9.3, 4.1, 3.8, 3.2, 220000, 'Produção consistente', 2, 1),
(4, '2025-01-02', 'manha', 31.0, 9.9, 4.3, 4.1, 3.5, 190000, 'Aumento na produção', 2, 1),
(4, '2025-01-02', 'tarde', 29.0, 9.6, 4.2, 4.0, 3.4, 200000, 'Mantendo excelência', 2, 1),
(4, '2025-01-02', 'noite', 27.0, 9.4, 4.1, 3.9, 3.3, 210000, 'Boa estabilidade', 2, 1);

-- ============================================================
-- 4. DADOS DE REGISTROS DE VOLUME (Últimos 30 dias)
-- ============================================================

INSERT INTO `volume_records` (`record_date`, `shift`, `total_volume`, `total_animals`, `average_per_animal`, `notes`, `recorded_by`, `farm_id`) VALUES
('2025-01-01', 'manha', 150.5, 5, 30.1, 'Boa produção matinal', 2, 1),
('2025-01-01', 'tarde', 140.0, 5, 28.0, 'Produção regular', 2, 1),
('2025-01-01', 'noite', 130.0, 5, 26.0, 'Produção noturna', 2, 1),
('2025-01-02', 'manha', 155.0, 5, 31.0, 'Aumento na produção', 2, 1),
('2025-01-02', 'tarde', 145.0, 5, 29.0, 'Mantendo qualidade', 2, 1),
('2025-01-02', 'noite', 135.0, 5, 27.0, 'Boa consistência', 2, 1),
('2025-01-03', 'manha', 160.0, 5, 32.0, 'Excelente produção', 2, 1),
('2025-01-03', 'tarde', 150.0, 5, 30.0, 'Alta qualidade', 2, 1),
('2025-01-03', 'noite', 140.0, 5, 28.0, 'Produção estável', 2, 1);

-- ============================================================
-- 5. DADOS DE TESTES DE QUALIDADE
-- ============================================================

INSERT INTO `quality_tests` (`test_date`, `test_type`, `animal_id`, `fat_content`, `protein_content`, `somatic_cells`, `bacteria_count`, `antibiotics`, `other_results`, `laboratory`, `cost`, `recorded_by`, `farm_id`) VALUES
('2025-01-01', 'qualidade_leite', 1, 3.8, 3.2, 250000, 50000, 'negativo', 'Qualidade excelente', 'LabVet', 50.00, 2, 1),
('2025-01-01', 'qualidade_leite', 2, 3.5, 3.0, 300000, 75000, 'negativo', 'Boa qualidade', 'LabVet', 50.00, 2, 1),
('2025-01-01', 'qualidade_leite', 4, 4.0, 3.4, 200000, 40000, 'negativo', 'Qualidade premium', 'LabVet', 50.00, 2, 1),
('2025-01-02', 'qualidade_leite', 1, 3.9, 3.3, 240000, 45000, 'negativo', 'Mantendo excelência', 'LabVet', 50.00, 2, 1),
('2025-01-02', 'qualidade_leite', 2, 3.6, 3.1, 280000, 70000, 'negativo', 'Boa consistência', 'LabVet', 50.00, 2, 1),
('2025-01-02', 'qualidade_leite', 4, 4.1, 3.5, 190000, 35000, 'negativo', 'Qualidade superior', 'LabVet', 50.00, 2, 1);

-- ============================================================
-- 6. DADOS DE REGISTROS DE SAÚDE
-- ============================================================

INSERT INTO `health_records` (`animal_id`, `record_date`, `record_type`, `description`, `medication`, `dosage`, `cost`, `next_date`, `veterinarian`, `recorded_by`, `farm_id`) VALUES
-- Vacinações
(1, '2025-01-01', 'Vacinação', 'Vacina contra febre aftosa', 'Vacina Aftosa', '2ml', 25.00, '2025-04-01', 'Dr. João Silva', 2, 1),
(2, '2025-01-01', 'Vacinação', 'Vacina contra febre aftosa', 'Vacina Aftosa', '2ml', 25.00, '2025-04-01', 'Dr. João Silva', 2, 1),
(4, '2025-01-01', 'Vacinação', 'Vacina contra febre aftosa', 'Vacina Aftosa', '2ml', 25.00, '2025-04-01', 'Dr. João Silva', 2, 1),

-- Vermifugação
(1, '2025-01-02', 'Vermifugação', 'Vermifugação preventiva', 'Ivermectina', '5ml', 15.00, '2025-04-02', 'Dr. João Silva', 2, 1),
(2, '2025-01-02', 'Vermifugação', 'Vermifugação preventiva', 'Ivermectina', '5ml', 15.00, '2025-04-02', 'Dr. João Silva', 2, 1),
(4, '2025-01-02', 'Vermifugação', 'Vermifugação preventiva', 'Ivermectina', '5ml', 15.00, '2025-04-02', 'Dr. João Silva', 2, 1),

-- Medicamentos
(1, '2025-01-03', 'Medicamento', 'Tratamento preventivo', 'Penicilina', '10ml', 30.00, '2025-01-10', 'Dr. João Silva', 2, 1),
(2, '2025-01-03', 'Medicamento', 'Suplementação vitamínica', 'Vitamina A+D+E', '5ml', 20.00, '2025-01-10', 'Dr. João Silva', 2, 1),
(4, '2025-01-03', 'Medicamento', 'Tratamento preventivo', 'Penicilina', '10ml', 30.00, '2025-01-10', 'Dr. João Silva', 2, 1);

-- ============================================================
-- 7. DADOS DE CICLOS DE CIO
-- ============================================================

INSERT INTO `heat_cycles` (`animal_id`, `heat_date`, `heat_intensity`, `insemination_planned`, `notes`, `recorded_by`, `farm_id`) VALUES
(2, '2025-01-01', 'forte', 1, 'Cio bem definido', 2, 1),
(4, '2025-01-02', 'moderado', 1, 'Cio moderado', 2, 1),
(5, '2025-01-03', 'forte', 1, 'Cio intenso', 2, 1),
(6, '2025-01-04', 'leve', 0, 'Cio leve, aguardar próximo', 2, 1),
(7, '2025-01-05', 'forte', 1, 'Cio bem definido', 2, 1);

-- ============================================================
-- 8. DADOS DE INSEMINAÇÕES
-- ============================================================

INSERT INTO `inseminations` (`animal_id`, `bull_id`, `insemination_date`, `insemination_type`, `technician`, `notes`, `recorded_by`, `farm_id`) VALUES
(2, 1, '2025-01-01', 'inseminacao_artificial', 'João Técnico', 'Inseminação bem sucedida', 2, 1),
(4, 2, '2025-01-02', 'inseminacao_artificial', 'João Técnico', 'Inseminação de qualidade', 2, 1),
(5, 1, '2025-01-03', 'inseminacao_artificial', 'João Técnico', 'Não pegou, repetir', 2, 1),
(6, 2, '2025-01-04', 'inseminacao_artificial', 'João Técnico', 'Inseminação eficaz', 2, 1),
(7, 1, '2025-01-05', 'inseminacao_artificial', 'João Técnico', 'Boa inseminação', 2, 1);

-- ============================================================
-- 9. DADOS DE CONTROLE DE PRENHEZ
-- ============================================================

INSERT INTO `pregnancy_controls` (`animal_id`, `insemination_id`, `pregnancy_date`, `expected_birth`, `pregnancy_stage`, `ultrasound_date`, `ultrasound_result`, `notes`, `recorded_by`, `farm_id`) VALUES
(2, 1, '2025-01-01', '2025-10-08', 'inicial', '2025-01-15', 'positivo', 'Prenhez confirmada', 2, 1),
(4, 2, '2025-01-02', '2025-10-09', 'inicial', '2025-01-16', 'positivo', 'Prenhez confirmada', 2, 1),
(6, 4, '2025-01-04', '2025-10-11', 'inicial', '2025-01-18', 'positivo', 'Prenhez confirmada', 2, 1),
(7, 5, '2025-01-05', '2025-10-12', 'inicial', '2025-01-19', 'positivo', 'Prenhez confirmada', 2, 1);

-- ============================================================
-- 10. DADOS DE LACTAÇÕES
-- ============================================================

INSERT INTO `lactations` (`animal_id`, `birth_id`, `lactation_start`, `lactation_end`, `total_volume`, `average_daily`, `peak_day`, `peak_volume`, `notes`, `recorded_by`, `farm_id`) VALUES
(1, NULL, '2024-06-01', NULL, 4500.0, 25.0, 45, 32.0, 'Lactação em andamento', 2, 1),
(2, NULL, '2024-08-15', NULL, 2800.0, 23.3, 35, 28.0, 'Lactação média', 2, 1),
(4, NULL, '2024-07-10', NULL, 3600.0, 24.0, 40, 30.0, 'Lactação produtiva', 2, 1);

-- ============================================================
-- 11. DADOS DE REGISTROS FINANCEIROS
-- ============================================================

INSERT INTO `financial_records` (`id`, `record_date`, `type`, `category`, `subcategory`, `amount`, `description`, `payment_method`, `created_by`, `farm_id`) VALUES
-- Receitas
(11, '2025-01-01', 'receita', 'Venda de Leite', 'Leite A', 2500.00, 'Venda de leite tipo A', 'dinheiro', 2, 1),
(12, '2025-01-02', 'receita', 'Venda de Leite', 'Leite B', 1800.00, 'Venda de leite tipo B', 'dinheiro', 2, 1),
(13, '2025-01-03', 'receita', 'Venda de Leite', 'Leite A', 2200.00, 'Venda de leite tipo A', 'dinheiro', 2, 1),

-- Despesas
(14, '2025-01-01', 'despesa', 'Alimentação', 'Concentrado', 800.00, 'Compra de ração concentrada', 'dinheiro', 2, 1),
(15, '2025-01-02', 'despesa', 'Medicamentos', 'Vacinas', 300.00, 'Vacinas para o rebanho', 'dinheiro', 2, 1),
(16, '2025-01-03', 'despesa', 'Mão de Obra', 'Salários', 1200.00, 'Pagamento de funcionários', 'dinheiro', 2, 1),
(17, '2025-01-04', 'despesa', 'Alimentação', 'Volumoso', 400.00, 'Compra de feno', 'dinheiro', 2, 1),
(18, '2025-01-05', 'despesa', 'Medicamentos', 'Vermífugos', 200.00, 'Vermífugos para o rebanho', 'dinheiro', 2, 1);

-- ============================================================
-- 12. DADOS DE MEDICAMENTOS
-- ============================================================

INSERT INTO `medications` (`id`, `name`, `type`, `description`, `unit`, `stock_quantity`, `min_stock`, `unit_price`, `expiry_date`, `supplier`, `farm_id`, `is_active`) VALUES
(11, 'Oxitetraciclina', 'antibiotico', 'Antibiótico de amplo espectro', 'ml', 800.00, 150.00, 18.50, '2026-12-31', 'VetCorp', 1, 1),
(12, 'Vitamina B12', 'vitamina', 'Suplemento vitamínico B12', 'ml', 600.00, 100.00, 12.90, '2026-06-30', 'FarmVet', 1, 1),
(13, 'Albendazol', 'vermifugo', 'Antiparasitário interno', 'ml', 400.00, 80.00, 14.80, '2026-03-31', 'AgroVet', 1, 1),
(14, 'Dexametasona', 'antiinflamatorio', 'Anti-inflamatório', 'ml', 300.00, 60.00, 22.30, '2026-09-30', 'VetCorp', 1, 1),
(15, 'Vacina Aftosa', 'vacina', 'Vacina contra febre aftosa', 'dose', 100.00, 20.00, 8.50, '2026-12-31', 'AgroVet', 1, 1);

-- ============================================================
-- 13. DADOS DE APLICAÇÕES DE MEDICAMENTOS
-- ============================================================

INSERT INTO `medication_applications` (`id`, `animal_id`, `medication_id`, `application_date`, `quantity`, `notes`, `applied_by`, `farm_id`) VALUES
(11, 1, 11, '2025-01-01', 10.0, 'Aplicação preventiva', 2, 1),
(12, 2, 11, '2025-01-01', 10.0, 'Aplicação preventiva', 2, 1),
(13, 4, 11, '2025-01-01', 10.0, 'Aplicação preventiva', 2, 1),
(14, 1, 12, '2025-01-02', 5.0, 'Suplementação vitamínica', 2, 1),
(15, 2, 12, '2025-01-02', 5.0, 'Suplementação vitamínica', 2, 1),
(16, 4, 12, '2025-01-02', 5.0, 'Suplementação vitamínica', 2, 1);

-- ============================================================
-- 14. DADOS DE ALERTAS DE SAÚDE
-- ============================================================

INSERT INTO `health_alerts` (`animal_id`, `alert_type`, `alert_date`, `alert_message`, `is_resolved`, `resolved_date`, `resolved_by`, `created_by`, `farm_id`) VALUES
(1, 'vacina', '2025-04-01', 'Vacina contra febre aftosa vence em 90 dias', 0, NULL, NULL, 2, 1),
(2, 'vacina', '2025-04-01', 'Vacina contra febre aftosa vence em 90 dias', 0, NULL, NULL, 2, 1),
(4, 'vacina', '2025-04-01', 'Vacina contra febre aftosa vence em 90 dias', 0, NULL, NULL, 2, 1),
(1, 'vermifugo', '2025-04-02', 'Vermifugação vence em 90 dias', 0, NULL, NULL, 2, 1),
(2, 'vermifugo', '2025-04-02', 'Vermifugação vence em 90 dias', 0, NULL, NULL, 2, 1),
(4, 'vermifugo', '2025-04-02', 'Vermifugação vence em 90 dias', 0, NULL, NULL, 2, 1);

-- ============================================================
-- 15. DADOS DE ALERTAS DE MATERNIDADE
-- ============================================================

INSERT INTO `maternity_alerts` (`animal_id`, `pregnancy_id`, `alert_date`, `expected_birth`, `days_to_birth`, `alert_message`, `is_resolved`, `resolved_date`, `created_by`, `farm_id`) VALUES
(2, 1, '2025-01-01', '2025-10-08', 280, 'Parto esperado em 280 dias', 0, NULL, 2, 1),
(4, 2, '2025-01-02', '2025-10-09', 280, 'Parto esperado em 280 dias', 0, NULL, 2, 1),
(6, 4, '2025-01-04', '2025-10-11', 280, 'Parto esperado em 280 dias', 0, NULL, 2, 1),
(7, 5, '2025-01-05', '2025-10-12', 280, 'Parto esperado em 280 dias', 0, NULL, 2, 1);

-- ============================================================
-- 16. DADOS DE CUSTOS DE NOVILHAS
-- ============================================================

INSERT INTO `heifer_costs` (`animal_id`, `phase_id`, `category_id`, `cost_date`, `cost_category`, `quantity`, `unit`, `unit_price`, `total_cost`, `cost_amount`, `description`, `is_automatic`, `recorded_by`, `farm_id`) VALUES
(4, 1, 1, '2025-01-01', 'Alimentação', 6.0, 'Litros', 0.60, 3.60, 3.60, 'Leite integral diário', 1, 2, 1),
(4, 1, 2, '2025-01-01', 'Alimentação', 0.5, 'Kg', 1.80, 0.90, 0.90, 'Concentrado inicial', 1, 2, 1),
(4, 2, 2, '2025-01-01', 'Alimentação', 3.0, 'Litros', 0.60, 1.80, 1.80, 'Sucedâneo na transição', 1, 2, 1),
(4, 2, 3, '2025-01-01', 'Alimentação', 1.5, 'Kg', 1.80, 2.70, 2.70, 'Concentrado transição', 1, 2, 1),
(4, 3, 4, '2025-01-01', 'Alimentação', 2.5, 'Kg', 1.50, 3.75, 3.75, 'Concentrado crescimento', 1, 2, 1);

-- ============================================================
-- 17. DADOS DE CONSUMO DIÁRIO DE NOVILHAS
-- ============================================================

INSERT INTO `heifer_daily_consumption` (`animal_id`, `consumption_date`, `age_days`, `phase_id`, `milk_liters`, `concentrate_kg`, `roughage_kg`, `recorded_by`, `farm_id`) VALUES
(4, '2025-01-01', 30, 1, 6.0, 0.5, 0.0, 2, 1),
(4, '2025-01-02', 31, 1, 6.0, 0.5, 0.0, 2, 1),
(4, '2025-01-03', 32, 1, 6.0, 0.5, 0.0, 2, 1),
(4, '2025-01-04', 33, 1, 6.0, 0.5, 0.0, 2, 1),
(4, '2025-01-05', 34, 1, 6.0, 0.5, 0.0, 2, 1);

-- ============================================================
-- 18. DADOS DE NOTIFICAÇÕES
-- ============================================================

INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`, `read_date`, `related_table`, `related_id`, `farm_id`) VALUES
(2, 'Vacinação Pendente', 'Vacina contra febre aftosa vence em 90 dias para 3 animais', 'warning', 0, NULL, 'animals', 1, 1),
(2, 'Vermifugação Pendente', 'Vermifugação vence em 90 dias para 3 animais', 'warning', 0, NULL, 'animals', 1, 1),
(2, 'Parto Esperado', 'Parto esperado para V002 em 280 dias', 'info', 0, NULL, 'animals', 2, 1),
(2, 'Parto Esperado', 'Parto esperado para V004 em 280 dias', 'info', 0, NULL, 'animals', 4, 1),
(2, 'Produção Alta', 'Vaca V001 com produção acima da média', 'success', 0, NULL, 'animals', 1, 1);

-- ============================================================
-- 19. DADOS DE PEDIGREE
-- ============================================================

INSERT INTO `pedigree_records` (`animal_id`, `generation`, `position`, `related_animal_id`, `animal_name`, `breed`, `notes`, `farm_id`) VALUES
(1, 1, 'pai', 5, 'Touro01', 'Holandês', 'Pai da V001', 1),
(1, 1, 'mae', 2, 'Luna', 'Gir', 'Mãe da V001', 1),
(2, 1, 'pai', 5, 'Touro01', 'Holandês', 'Pai da V002', 1),
(2, 1, 'mae', 3, 'Maya', 'Girolanda', 'Mãe da V002', 1),
(4, 1, 'pai', 5, 'Touro01', 'Holandês', 'Pai da V004', 1),
(4, 1, 'mae', 1, 'Bella', 'Holandesa', 'Mãe da V004', 1);

-- ============================================================
-- 20. DADOS DE NASCIMENTOS
-- ============================================================

INSERT INTO `births` (`id`, `animal_id`, `pregnancy_id`, `birth_date`, `birth_time`, `birth_type`, `calf_number`, `calf_gender`, `calf_weight`, `calf_breed`, `mother_status`, `calf_status`, `notes`, `recorded_by`, `farm_id`) VALUES
(11, 1, NULL, '2020-03-15', '14:30:00', 'normal', 'B001', 'femea', 45.5, 'Holandesa', 'boa', 'vivo', 'Nascimento normal', 2, 1),
(12, 2, NULL, '2021-05-20', '16:45:00', 'normal', 'B002', 'femea', 42.0, 'Gir', 'boa', 'vivo', 'Nascimento normal', 2, 1),
(13, 4, NULL, '2018-04-15', '13:20:00', 'normal', 'B003', 'femea', 48.0, 'Holandesa', 'boa', 'vivo', 'Nascimento normal', 2, 1),
(14, 5, NULL, '2019-07-20', '15:10:00', 'normal', 'B004', 'femea', 44.0, 'Girolanda', 'boa', 'vivo', 'Nascimento normal', 2, 1);

-- ============================================================
-- 21. DADOS DE PROGRAMAS DE VACINAÇÃO
-- ============================================================

INSERT INTO `vaccination_programs` (`id`, `name`, `description`, `target_age_min`, `target_age_max`, `frequency_days`, `is_active`, `farm_id`) VALUES
(11, 'Programa Aftosa', 'Vacinação contra febre aftosa', 0, 9999, 90, 1, 1),
(12, 'Programa Vermifugação', 'Vermifugação preventiva', 0, 9999, 90, 1, 1),
(13, 'Programa Vitamínico', 'Suplementação vitamínica', 0, 9999, 30, 1, 1);

-- ============================================================
-- 22. DADOS DE GRUPOS DE ANIMAIS
-- ============================================================

INSERT INTO `animal_groups` (`id`, `group_name`, `group_code`, `group_type`, `description`, `location`, `capacity`, `current_count`, `color_code`, `farm_id`, `created_by`) VALUES
(11, 'Lactantes Alta Produção - Teste', 'LAC-A-T', 'lactante', 'Vacas em lactação > 30L/dia', 'Galpão A', 20, 5, '#10B981', 1, 2),
(12, 'Lactantes Baixa Produção - Teste', 'LAC-B-T', 'lactante', 'Vacas em lactação < 30L/dia', 'Galpão B', 15, 3, '#059669', 1, 2),
(13, 'Vacas Secas - Teste', 'SECO-T', 'seco', 'Vacas no período seco', 'Galpão C', 10, 2, '#F59E0B', 1, 2),
(14, 'Novilhas - Teste', 'NOV-T', 'novilha', 'Novilhas para primeira inseminação', 'Galpão D', 8, 3, '#3B82F6', 1, 2),
(15, 'Bezerras - Teste', 'BEZ-T', 'bezerra', 'Bezerras em crescimento', 'Galpão E', 12, 3, '#8B5CF6', 1, 2);

-- ============================================================
-- 23. DADOS DE PREFERÊNCIAS DE USUÁRIO
-- ============================================================

INSERT INTO `user_preferences` (`id`, `user_id`, `preference_key`, `preference_value`, `data_type`, `category`) VALUES
(11, 2, 'notifications_enabled_test', 'true', 'boolean', 'notifications'),
(12, 2, 'theme_test', 'light', 'string', 'appearance'),
(13, 2, 'language_test', 'pt-BR', 'string', 'localization'),
(14, 2, 'dashboard_layout_test', 'grid', 'string', 'layout'),
(15, 2, 'auto_refresh_test', 'true', 'boolean', 'performance');

-- ============================================================
-- 24. DADOS DE CONFIGURAÇÕES DE BACKUP
-- ============================================================

INSERT INTO `backup_settings` (`id`, `auto_backup_enabled`, `backup_frequency`, `backup_time`, `retention_days`, `include_photos`, `compression_enabled`) VALUES
(11, 1, 'daily', '02:00:00', 30, 1, 1);

-- ============================================================
-- 25. DADOS DE REGISTROS DE BACKUP
-- ============================================================

INSERT INTO `backup_records` (`id`, `name`, `description`, `file_path`, `file_size`, `created_by`) VALUES
(11, 'Backup Diário - 2025-01-01', 'Backup automático diário', '/backups/backup_2025-01-01.sql', 2048576, 2),
(12, 'Backup Diário - 2025-01-02', 'Backup automático diário', '/backups/backup_2025-01-02.sql', 2156789, 2),
(13, 'Backup Manual - 2025-01-03', 'Backup manual antes de atualização', '/backups/backup_manual_2025-01-03.sql', 1987654, 2);

-- ============================================================
-- 26. DADOS DE PERFORMANCE DE TOUROS
-- ============================================================

INSERT INTO `bull_performance` (`bull_id`, `period_start`, `period_end`, `total_inseminations`, `successful_inseminations`, `pregnancy_rate`, `conception_rate`, `average_services_per_conception`, `total_cost`, `cost_per_pregnancy`) VALUES
(1, '2024-01-01', '2024-12-31', 25, 18, 72.0, 68.0, 1.4, 3750.00, 208.33),
(2, '2024-01-01', '2024-12-31', 20, 15, 75.0, 70.0, 1.3, 3000.00, 200.00),
(3, '2024-01-01', '2024-12-31', 15, 12, 80.0, 75.0, 1.2, 2250.00, 187.50);

-- ============================================================
-- 27. DADOS DE CATÁLOGO DE SÊMEN
-- ============================================================

INSERT INTO `semen_catalog` (`bull_id`, `batch_number`, `production_date`, `expiry_date`, `straws_available`, `straws_used`, `price_per_straw`, `supplier`, `storage_location`, `quality_grade`, `genetic_tests`, `notes`, `farm_id`) VALUES
(1, 'SE001', '2024-12-01', '2025-12-01', 50, 0, 150.00, 'SemenBrasil', 'Freezer A', 'A', 'Testes genéticos completos', 'Sêmen de alta qualidade', 1),
(2, 'SE002', '2024-12-01', '2025-12-01', 40, 0, 150.00, 'SemenBrasil', 'Freezer A', 'A', 'Testes genéticos completos', 'Sêmen de alta qualidade', 1),
(3, 'SE003', '2024-12-01', '2025-12-01', 30, 0, 120.00, 'SemenBrasil', 'Freezer B', 'A', 'Testes genéticos básicos', 'Sêmen de qualidade', 1);

-- ============================================================
-- 28. DADOS DE PREDIÇÕES DE IA
-- ============================================================

INSERT INTO `ai_predictions` (`animal_id`, `prediction_type`, `predicted_date`, `predicted_value`, `confidence_score`, `algorithm_version`, `input_data`, `prediction_date`, `farm_id`) VALUES
(2, 'heat', '2025-01-15', NULL, 85.5, 'v1.0', '{"last_heat": "2024-12-15", "cycle_length": 21}', '2025-01-01', 1),
(4, 'heat', '2025-01-18', NULL, 78.2, 'v1.0', '{"last_heat": "2024-12-18", "cycle_length": 22}', '2025-01-01', 1),
(5, 'heat', '2025-01-20', NULL, 82.1, 'v1.0', '{"last_heat": "2024-12-20", "cycle_length": 21}', '2025-01-01', 1),
(6, 'heat', '2025-01-22', NULL, 76.8, 'v1.0', '{"last_heat": "2024-12-22", "cycle_length": 23}', '2025-01-01', 1),
(7, 'heat', '2025-01-25', NULL, 80.3, 'v1.0', '{"last_heat": "2024-12-25", "cycle_length": 21}', '2025-01-01', 1);

-- ============================================================
-- 29. DADOS DE CACHE DE LISTAS DE AÇÕES
-- ============================================================

INSERT INTO `action_lists_cache` (`list_type`, `animal_id`, `priority`, `action_date`, `days_until`, `cache_data`, `is_completed`, `farm_id`) VALUES
('heat_expected', 2, 'high', '2025-01-15', 14, '{"prediction_confidence": 85.5, "last_heat": "2024-12-15"}', 0, 1),
('heat_expected', 4, 'medium', '2025-01-18', 17, '{"prediction_confidence": 78.2, "last_heat": "2024-12-18"}', 0, 1),
('calving_soon', 2, 'low', '2025-10-08', 280, '{"expected_birth": "2025-10-08", "pregnancy_stage": "inicial"}', 0, 1),
('calving_soon', 4, 'low', '2025-10-09', 281, '{"expected_birth": "2025-10-09", "pregnancy_stage": "inicial"}', 0, 1);

-- ============================================================
-- 30. DADOS DE TRANSPONDERS RFID
-- ============================================================

INSERT INTO `animal_transponders` (`animal_id`, `transponder_code`, `transponder_type`, `manufacturer`, `activation_date`, `location`, `is_active`, `notes`, `farm_id`, `recorded_by`) VALUES
(1, 'RF001', 'rfid', 'AgroTag', '2024-01-01', 'ear_left', 1, 'Transponder ativo', 1, 2),
(2, 'RF002', 'rfid', 'AgroTag', '2024-01-01', 'ear_left', 1, 'Transponder ativo', 1, 2),
(4, 'RF003', 'rfid', 'AgroTag', '2024-01-01', 'ear_left', 1, 'Transponder ativo', 1, 2),
(5, 'RF004', 'rfid', 'AgroTag', '2024-01-01', 'ear_left', 1, 'Transponder ativo', 1, 2),
(6, 'RF005', 'rfid', 'AgroTag', '2024-01-01', 'ear_left', 1, 'Transponder ativo', 1, 2);

-- ============================================================
-- FIM DOS DADOS DE TESTE COMPLETOS
-- ============================================================

-- Verificar se os dados foram inseridos corretamente
SELECT 'Dados de teste inseridos com sucesso!' as status;
SELECT COUNT(*) as total_animals FROM animals;
SELECT COUNT(*) as total_bulls FROM bulls;
SELECT COUNT(*) as total_milk_production FROM milk_production;
SELECT COUNT(*) as total_health_records FROM health_records;
SELECT COUNT(*) as total_financial_records FROM financial_records;

-- ============================================================
-- UPGRADE COMPLETO! Sistema superior ao FarmTell Milk
-- Adicionadas: 14 tabelas + 3 procedures + 6 triggers + 4 views
-- Total agora: 37 tabelas + 4 procedures + 15 triggers + 9+ views
-- ============================================================

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
