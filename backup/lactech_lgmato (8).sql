-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 01/11/2025 às 15:12
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

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

-- --------------------------------------------------------

--
-- Estrutura para tabela `action_lists_cache`
--

CREATE TABLE `action_lists_cache` (
  `id` int(11) NOT NULL,
  `list_type` enum('heat_expected','calving_soon','pregnancy_check','dry_off','vaccination','medication','bcs_check','group_change') NOT NULL,
  `animal_id` int(11) NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `action_date` date NOT NULL,
  `days_until` int(11) NOT NULL,
  `cache_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cache_data`)),
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `action_lists_cache`
--

INSERT INTO `action_lists_cache` (`id`, `list_type`, `animal_id`, `priority`, `action_date`, `days_until`, `cache_data`, `is_completed`, `completed_at`, `farm_id`, `last_updated`) VALUES
(1, 'heat_expected', 2, 'high', '2025-01-15', 14, '{\"prediction_confidence\": 85.5, \"last_heat\": \"2024-12-15\"}', 0, NULL, 1, '2025-10-27 20:13:08'),
(2, 'heat_expected', 4, 'medium', '2025-01-18', 17, '{\"prediction_confidence\": 78.2, \"last_heat\": \"2024-12-18\"}', 0, NULL, 1, '2025-10-27 20:13:08'),
(3, 'calving_soon', 2, 'low', '2025-10-08', 280, '{\"expected_birth\": \"2025-10-08\", \"pregnancy_stage\": \"inicial\"}', 0, NULL, 1, '2025-10-27 20:13:08'),
(4, 'calving_soon', 4, 'low', '2025-10-09', 281, '{\"expected_birth\": \"2025-10-09\", \"pregnancy_stage\": \"inicial\"}', 0, NULL, 1, '2025-10-27 20:13:08');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ai_predictions`
--

CREATE TABLE `ai_predictions` (
  `id` int(11) NOT NULL,
  `animal_id` int(11) DEFAULT NULL,
  `prediction_type` enum('heat','production','health','calving','dry_off','group_change') NOT NULL,
  `predicted_date` date NOT NULL,
  `predicted_value` decimal(10,2) DEFAULT NULL,
  `confidence_score` decimal(5,2) NOT NULL COMMENT '0-100%',
  `algorithm_version` varchar(20) DEFAULT 'v1.0',
  `input_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`input_data`)),
  `prediction_date` date NOT NULL DEFAULT curdate(),
  `actual_date` date DEFAULT NULL,
  `actual_value` decimal(10,2) DEFAULT NULL,
  `was_accurate` tinyint(1) DEFAULT NULL,
  `error_margin` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `ai_predictions`
--

INSERT INTO `ai_predictions` (`id`, `animal_id`, `prediction_type`, `predicted_date`, `predicted_value`, `confidence_score`, `algorithm_version`, `input_data`, `prediction_date`, `actual_date`, `actual_value`, `was_accurate`, `error_margin`, `notes`, `farm_id`, `created_at`, `updated_at`) VALUES
(1, 2, 'heat', '2025-01-15', NULL, 85.50, 'v1.0', '{\"last_heat\": \"2024-12-15\", \"cycle_length\": 21}', '2025-01-01', NULL, NULL, NULL, NULL, NULL, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(2, 4, 'heat', '2025-01-18', NULL, 78.20, 'v1.0', '{\"last_heat\": \"2024-12-18\", \"cycle_length\": 22}', '2025-01-01', NULL, NULL, NULL, NULL, NULL, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(3, 5, 'heat', '2025-01-20', NULL, 82.10, 'v1.0', '{\"last_heat\": \"2024-12-20\", \"cycle_length\": 21}', '2025-01-01', NULL, NULL, NULL, NULL, NULL, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(4, 6, 'heat', '2025-01-22', NULL, 76.80, 'v1.0', '{\"last_heat\": \"2024-12-22\", \"cycle_length\": 23}', '2025-01-01', NULL, NULL, NULL, NULL, NULL, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(5, 7, 'heat', '2025-01-25', NULL, 80.30, 'v1.0', '{\"last_heat\": \"2024-12-25\", \"cycle_length\": 21}', '2025-01-01', NULL, NULL, NULL, NULL, NULL, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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
  `current_group_id` int(11) DEFAULT NULL COMMENT 'Grupo/lote atual do animal',
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

INSERT INTO `animals` (`id`, `animal_number`, `name`, `breed`, `gender`, `birth_date`, `birth_weight`, `father_id`, `mother_id`, `status`, `current_group_id`, `health_status`, `reproductive_status`, `entry_date`, `exit_date`, `exit_reason`, `farm_id`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'V001', 'Bella', 'Holandesa', 'femea', '2020-03-15', NULL, 5, 2, 'Lactante', NULL, 'saudavel', 'vazia', NULL, NULL, NULL, 1, 'Vaca produtora principal', 1, '2025-10-13 14:21:52', '2025-10-13 17:31:04'),
(2, 'V002', 'Luna', 'Gir', 'femea', '2021-05-20', NULL, NULL, NULL, 'Lactante', NULL, 'saudavel', 'prenha', NULL, NULL, NULL, 1, 'Vaca jovem em produção', 1, '2025-10-13 14:21:52', '2025-10-27 20:13:08'),
(3, 'V003', 'Maya', 'Girolanda', 'femea', '2019-08-10', NULL, NULL, NULL, 'Seco', NULL, 'saudavel', 'vazia', NULL, NULL, NULL, 1, 'Vaca experiente', 1, '2025-10-13 14:21:52', '2025-10-13 14:21:52'),
(4, 'N001', 'Estrela', 'Holandesa', 'femea', '2022-01-15', NULL, NULL, NULL, 'Novilha', NULL, 'saudavel', 'prenha', NULL, NULL, NULL, 1, 'Novilha para primeira inseminação', 1, '2025-10-13 14:21:52', '2025-10-27 20:13:08'),
(5, 'T001', 'Touro01', 'Holandês', 'macho', '2018-12-01', NULL, NULL, NULL, 'Touro', NULL, 'saudavel', 'prenha', NULL, NULL, NULL, 1, 'Touro reprodutor', 1, '2025-10-13 14:21:52', '2025-10-27 20:13:08'),
(11, '223', 'Francisco', 'girolando', 'macho', '2005-07-21', 54.00, NULL, NULL, 'Touro', NULL, 'saudavel', 'outros', '2025-10-19', NULL, NULL, 1, NULL, 1, '2025-10-20 01:56:38', '2025-10-20 01:56:38'),
(12, 'V004', 'Estrela', 'Holandesa', 'femea', '2018-04-15', 45.50, 5, 1, 'Lactante', NULL, 'saudavel', 'lactante', '2018-04-15', NULL, NULL, 1, 'Vaca de alta produção', 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(13, 'V005', 'Lua', 'Girolanda', 'femea', '2019-07-20', 42.00, 5, 2, 'Lactante', NULL, 'saudavel', 'lactante', '2019-07-20', NULL, NULL, 1, 'Vaca produtiva', 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(14, 'V006', 'Sol', 'Holandesa', 'femea', '2017-11-10', 48.00, 5, 1, 'Lactante', NULL, 'saudavel', 'lactante', '2017-11-10', NULL, NULL, 1, 'Vaca experiente', 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(15, 'V007', 'Mar', 'Gir', 'femea', '2020-02-28', 40.50, 5, 2, 'Lactante', NULL, 'saudavel', 'lactante', '2020-02-28', NULL, NULL, 1, 'Vaca jovem', 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(16, 'V008', 'Terra', 'Girolanda', 'femea', '2018-09-05', 46.00, 5, 1, 'Lactante', NULL, 'saudavel', 'lactante', '2018-09-05', NULL, NULL, 1, 'Vaca estável', 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(17, 'V009', 'Céu', 'Holandesa', 'femea', '2016-12-20', 50.00, 5, 1, 'Seco', NULL, 'saudavel', 'vazia', '2016-12-20', NULL, NULL, 1, 'Vaca no período seco', 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(18, 'V010', 'Ar', 'Gir', 'femea', '2017-03-15', 44.00, 5, 2, 'Seco', NULL, 'saudavel', 'vazia', '2017-03-15', NULL, NULL, 1, 'Vaca descansando', 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(19, 'N002', 'Flor', 'Holandesa', 'femea', '2022-06-10', 38.00, 5, 4, 'Novilha', NULL, 'saudavel', 'vazia', '2022-06-10', NULL, NULL, 1, 'Novilha para IA', 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(20, 'N003', 'Rosa', 'Girolanda', 'femea', '2022-08-25', 40.00, 5, 5, 'Novilha', NULL, 'saudavel', 'vazia', '2022-08-25', NULL, NULL, 1, 'Novilha jovem', 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(21, 'N004', 'Lírio', 'Gir', 'femea', '2022-04-12', 36.50, 5, 6, 'Novilha', NULL, 'saudavel', 'vazia', '2022-04-12', NULL, NULL, 1, 'Novilha promissora', 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(22, 'B001', 'Pequena', 'Holandesa', 'femea', '2023-01-15', 35.00, 5, 4, 'Bezerra', NULL, 'saudavel', 'vazia', '2023-01-15', NULL, NULL, 1, 'Bezerra em crescimento', 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(23, 'B002', 'Mini', 'Girolanda', 'femea', '2023-03-20', 32.00, 5, 5, 'Bezerra', NULL, 'saudavel', 'vazia', '2023-03-20', NULL, NULL, 1, 'Bezerra jovem', 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(24, 'B003', 'Tiny', 'Gir', 'femea', '2023-05-10', 34.50, 5, 6, 'Bezerra', NULL, 'saudavel', 'vazia', '2023-05-10', NULL, NULL, 1, 'Bezerra saudável', 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(25, 'M001', 'Forte', 'Holandês', 'macho', '2023-02-10', 42.00, 5, 4, 'Bezerro', NULL, 'saudavel', 'outros', '2023-02-10', NULL, NULL, 1, 'Bezerro para venda', 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(26, 'M002', 'Robusto', 'Girolando', 'macho', '2023-04-15', 40.00, 5, 5, 'Bezerro', NULL, 'saudavel', 'outros', '2023-04-15', NULL, NULL, 1, 'Bezerro forte', 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07');

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
-- Estrutura para tabela `animal_groups`
--

CREATE TABLE `animal_groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `group_code` varchar(20) DEFAULT NULL,
  `group_type` enum('lactante','seco','novilha','pre_parto','pos_parto','hospital','quarentena','pasto','outros') NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `current_count` int(11) NOT NULL DEFAULT 0,
  `feed_protocol` text DEFAULT NULL,
  `milking_order` int(11) DEFAULT NULL,
  `color_code` varchar(7) DEFAULT '#6B7280',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `animal_groups`
--

INSERT INTO `animal_groups` (`id`, `group_name`, `group_code`, `group_type`, `description`, `location`, `capacity`, `current_count`, `feed_protocol`, `milking_order`, `color_code`, `is_active`, `farm_id`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Lactantes Alta Produção', 'LAC-A', 'lactante', 'Vacas em lactação > 30L/dia', NULL, NULL, 0, NULL, NULL, '#10B981', 1, 1, 1, '2025-10-27 20:13:06', '2025-10-27 20:13:06'),
(2, 'Lactantes Baixa Produção', 'LAC-B', 'lactante', 'Vacas em lactação < 30L/dia', NULL, NULL, 0, NULL, NULL, '#059669', 1, 1, 1, '2025-10-27 20:13:06', '2025-10-27 20:13:06'),
(3, 'Vacas Secas', 'SECO', 'seco', 'Vacas no período seco', NULL, NULL, 0, NULL, NULL, '#F59E0B', 1, 1, 1, '2025-10-27 20:13:06', '2025-10-27 20:13:06'),
(4, 'Pré-parto', 'PRE-P', 'pre_parto', 'Vacas a 30 dias do parto', NULL, NULL, 0, NULL, NULL, '#EF4444', 1, 1, 1, '2025-10-27 20:13:06', '2025-10-27 20:13:06'),
(5, 'Pós-parto', 'POS-P', 'pos_parto', 'Vacas até 21 dias pós-parto', NULL, NULL, 0, NULL, NULL, '#EC4899', 1, 1, 1, '2025-10-27 20:13:06', '2025-10-27 20:13:06'),
(6, 'Novilhas', 'NOV', 'novilha', 'Novilhas em crescimento', NULL, NULL, 0, NULL, NULL, '#3B82F6', 1, 1, 1, '2025-10-27 20:13:06', '2025-10-27 20:13:06'),
(7, 'Hospital', 'HOSP', 'hospital', 'Animais em tratamento', NULL, NULL, 0, NULL, NULL, '#DC2626', 1, 1, 1, '2025-10-27 20:13:06', '2025-10-27 20:13:06'),
(8, 'Quarentena', 'QUAR', 'quarentena', 'Animais em quarentena', NULL, NULL, 0, NULL, NULL, '#9CA3AF', 1, 1, 1, '2025-10-27 20:13:06', '2025-10-27 20:13:06'),
(11, 'Lactantes Alta Produção - Teste', 'LAC-A-T', 'lactante', 'Vacas em lactação > 30L/dia', 'Galpão A', 20, 5, NULL, NULL, '#10B981', 1, 1, 2, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(12, 'Lactantes Baixa Produção - Teste', 'LAC-B-T', 'lactante', 'Vacas em lactação < 30L/dia', 'Galpão B', 15, 3, NULL, NULL, '#059669', 1, 1, 2, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(13, 'Vacas Secas - Teste', 'SECO-T', 'seco', 'Vacas no período seco', 'Galpão C', 10, 2, NULL, NULL, '#F59E0B', 1, 1, 2, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(14, 'Novilhas - Teste', 'NOV-T', 'novilha', 'Novilhas para primeira inseminação', 'Galpão D', 8, 3, NULL, NULL, '#3B82F6', 1, 1, 2, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(15, 'Bezerras - Teste', 'BEZ-T', '', 'Bezerras em crescimento', 'Galpão E', 12, 3, NULL, NULL, '#8B5CF6', 1, 1, 2, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

-- --------------------------------------------------------

--
-- Estrutura para tabela `animal_photos`
--

CREATE TABLE `animal_photos` (
  `id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `photo_url` varchar(500) NOT NULL,
  `photo_type` enum('profile','health','event','birth','bcs','injury','other') DEFAULT 'profile',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `taken_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `file_size` int(11) DEFAULT NULL,
  `dimensions` varchar(20) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `animal_transponders`
--

CREATE TABLE `animal_transponders` (
  `id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL COMMENT 'ID do animal',
  `transponder_code` varchar(50) NOT NULL COMMENT 'Código único RFID',
  `transponder_type` enum('rfid','visual','electronic','microchip') NOT NULL DEFAULT 'rfid',
  `manufacturer` varchar(100) DEFAULT NULL,
  `activation_date` date NOT NULL,
  `deactivation_date` date DEFAULT NULL,
  `location` enum('ear_left','ear_right','neck','leg','other') DEFAULT 'ear_left',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `recorded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `animal_transponders`
--

INSERT INTO `animal_transponders` (`id`, `animal_id`, `transponder_code`, `transponder_type`, `manufacturer`, `activation_date`, `deactivation_date`, `location`, `is_active`, `notes`, `farm_id`, `recorded_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'RF001', 'rfid', 'AgroTag', '2024-01-01', NULL, 'ear_left', 1, 'Transponder ativo', 1, 2, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(2, 2, 'RF002', 'rfid', 'AgroTag', '2024-01-01', NULL, 'ear_left', 1, 'Transponder ativo', 1, 2, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(3, 4, 'RF003', 'rfid', 'AgroTag', '2024-01-01', NULL, 'ear_left', 1, 'Transponder ativo', 1, 2, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(4, 5, 'RF004', 'rfid', 'AgroTag', '2024-01-01', NULL, 'ear_left', 1, 'Transponder ativo', 1, 2, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(5, 6, 'RF005', 'rfid', 'AgroTag', '2024-01-01', NULL, 'ear_left', 1, 'Transponder ativo', 1, 2, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

-- --------------------------------------------------------

--
-- Estrutura para tabela `backup_records`
--

CREATE TABLE `backup_records` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Nome do backup',
  `description` text DEFAULT NULL COMMENT 'Descrição do backup',
  `file_path` varchar(500) NOT NULL COMMENT 'Caminho do arquivo',
  `file_size` bigint(20) DEFAULT NULL COMMENT 'Tamanho do arquivo em bytes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data de criação',
  `created_by` int(11) DEFAULT NULL COMMENT 'Usuário que criou'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Registro de backups do sistema';

--
-- Despejando dados para a tabela `backup_records`
--

INSERT INTO `backup_records` (`id`, `name`, `description`, `file_path`, `file_size`, `created_at`, `created_by`) VALUES
(11, 'Backup Diário - 2025-01-01', 'Backup automático diário', '/backups/backup_2025-01-01.sql', 2048576, '2025-10-27 20:13:08', 2),
(12, 'Backup Diário - 2025-01-02', 'Backup automático diário', '/backups/backup_2025-01-02.sql', 2156789, '2025-10-27 20:13:08', 2),
(13, 'Backup Manual - 2025-01-03', 'Backup manual antes de atualização', '/backups/backup_manual_2025-01-03.sql', 1987654, '2025-10-27 20:13:08', 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `backup_settings`
--

CREATE TABLE `backup_settings` (
  `id` int(11) NOT NULL,
  `auto_backup_enabled` tinyint(1) DEFAULT 0 COMMENT 'Backup automático ativado',
  `backup_frequency` enum('daily','weekly','monthly') DEFAULT 'daily' COMMENT 'Frequência do backup',
  `backup_time` time DEFAULT '02:00:00' COMMENT 'Horário do backup',
  `retention_days` int(11) DEFAULT 30 COMMENT 'Dias para manter backups',
  `include_photos` tinyint(1) DEFAULT 1 COMMENT 'Incluir fotos no backup',
  `compression_enabled` tinyint(1) DEFAULT 1 COMMENT 'Compressão ativada',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Configurações de backup automático';

--
-- Despejando dados para a tabela `backup_settings`
--

INSERT INTO `backup_settings` (`id`, `auto_backup_enabled`, `backup_frequency`, `backup_time`, `retention_days`, `include_photos`, `compression_enabled`, `created_at`, `updated_at`) VALUES
(1, 0, 'daily', '02:00:00', 30, 1, 1, '2025-10-27 20:13:05', '2025-10-27 20:13:05'),
(11, 1, 'daily', '02:00:00', 30, 1, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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

--
-- Despejando dados para a tabela `births`
--

INSERT INTO `births` (`id`, `animal_id`, `pregnancy_id`, `birth_date`, `birth_time`, `birth_type`, `calf_number`, `calf_gender`, `calf_weight`, `calf_breed`, `mother_status`, `calf_status`, `notes`, `recorded_by`, `farm_id`, `created_at`, `updated_at`) VALUES
(11, 1, NULL, '2020-03-15', '14:30:00', 'normal', 'B001', 'femea', 45.50, 'Holandesa', 'boa', 'vivo', 'Nascimento normal', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(12, 2, NULL, '2021-05-20', '16:45:00', 'normal', 'B002', 'femea', 42.00, 'Gir', 'boa', 'vivo', 'Nascimento normal', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(13, 4, NULL, '2018-04-15', '13:20:00', 'normal', 'B003', 'femea', 48.00, 'Holandesa', 'boa', 'vivo', 'Nascimento normal', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(14, 5, NULL, '2019-07-20', '15:10:00', 'normal', 'B004', 'femea', 44.00, 'Girolanda', 'boa', 'vivo', 'Nascimento normal', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

-- --------------------------------------------------------

--
-- Estrutura para tabela `body_condition_scores`
--

CREATE TABLE `body_condition_scores` (
  `id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `score` decimal(2,1) NOT NULL COMMENT 'Score de 1.0 a 5.0',
  `evaluation_date` date NOT NULL,
  `evaluation_method` enum('visual','palpacao','automatico','foto_ia') NOT NULL DEFAULT 'visual',
  `lactation_stage` enum('inicio','pico','meio','final','seco') DEFAULT NULL,
  `weight_kg` decimal(6,2) DEFAULT NULL,
  `height_cm` decimal(5,1) DEFAULT NULL,
  `body_measurements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`body_measurements`)),
  `photo_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `evaluated_by` int(11) NOT NULL,
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
  `bull_code` varchar(50) DEFAULT NULL,
  `bull_name` varchar(100) DEFAULT NULL,
  `bull_number` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `breed` varchar(100) NOT NULL,
  `birth_date` date NOT NULL,
  `genetic_code` varchar(100) DEFAULT NULL,
  `sire` varchar(100) DEFAULT NULL,
  `dam` varchar(100) DEFAULT NULL,
  `genetic_merit` decimal(5,2) DEFAULT NULL,
  `milk_production_index` decimal(5,2) DEFAULT NULL,
  `fat_production_index` decimal(5,2) DEFAULT NULL,
  `protein_production_index` decimal(5,2) DEFAULT NULL,
  `fertility_index` decimal(5,2) DEFAULT NULL,
  `health_index` decimal(5,2) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `status` enum('ativo','inativo','vendido','morto') DEFAULT 'ativo',
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `sale_date` date DEFAULT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
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

INSERT INTO `bulls` (`id`, `bull_code`, `bull_name`, `bull_number`, `name`, `breed`, `birth_date`, `genetic_code`, `sire`, `dam`, `genetic_merit`, `milk_production_index`, `fat_production_index`, `protein_production_index`, `fertility_index`, `health_index`, `photo_url`, `status`, `purchase_date`, `purchase_price`, `sale_date`, `sale_price`, `source`, `genetic_value`, `notes`, `farm_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 'B001', 'Touro Elite', 'Holandês', '2018-12-01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ativo', NULL, NULL, NULL, NULL, 'proprio', 'Alto valor genético', NULL, 1, 1, '2025-10-13 14:21:52', '2025-10-13 14:21:52'),
(2, NULL, NULL, 'B002', 'Inseminação Premium', 'Gir', '2017-06-15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ativo', NULL, NULL, NULL, NULL, 'inseminacao', 'Sêmen importado', NULL, 1, 1, '2025-10-13 14:21:52', '2025-10-13 14:21:52'),
(3, NULL, NULL, 'B003', 'Champion Elite', 'Holandês', '2017-03-10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ativo', NULL, NULL, NULL, NULL, 'inseminacao', 'Alto valor genético para produção', 'Sêmen importado da Holanda', 1, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(4, NULL, NULL, 'B004', 'Gir Premium', 'Gir', '2016-08-20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ativo', NULL, NULL, NULL, NULL, 'inseminacao', 'Excelente para adaptação tropical', 'Sêmen de touro premiado', 1, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(5, NULL, NULL, 'B005', 'Girolando Star', 'Girolando', '2018-01-15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ativo', NULL, NULL, NULL, NULL, 'proprio', 'Boa produção e adaptação', 'Touro próprio da fazenda', 1, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07');

-- --------------------------------------------------------

--
-- Estrutura para tabela `bull_performance`
--

CREATE TABLE `bull_performance` (
  `id` int(11) NOT NULL,
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
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `bull_performance`
--

INSERT INTO `bull_performance` (`id`, `bull_id`, `period_start`, `period_end`, `total_inseminations`, `successful_inseminations`, `pregnancy_rate`, `conception_rate`, `average_services_per_conception`, `total_cost`, `cost_per_pregnancy`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, '2024-01-01', '2024-12-31', 25, 18, 72.00, 68.00, 1.40, 3750.00, 208.33, NULL, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(2, 2, '2024-01-01', '2024-12-31', 20, 15, 75.00, 70.00, 1.30, 3000.00, 200.00, NULL, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(3, 3, '2024-01-01', '2024-12-31', 15, 12, 80.00, 75.00, 1.20, 2250.00, 187.50, NULL, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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
-- Estrutura para tabela `feed_records`
--

CREATE TABLE `feed_records` (
  `id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `feed_date` date NOT NULL,
  `shift` enum('manha','tarde','noite','unico') NOT NULL DEFAULT 'unico',
  `concentrate_kg` decimal(6,2) NOT NULL DEFAULT 0.00,
  `roughage_kg` decimal(6,2) DEFAULT NULL,
  `silage_kg` decimal(6,2) DEFAULT NULL,
  `hay_kg` decimal(6,2) DEFAULT NULL,
  `feed_type` varchar(100) DEFAULT NULL,
  `feed_brand` varchar(100) DEFAULT NULL,
  `protein_percentage` decimal(4,2) DEFAULT NULL,
  `cost_per_kg` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `automatic` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `financial_records`
--

CREATE TABLE `financial_records` (
  `id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `type` enum('receita','despesa') NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'completed',
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

--
-- Despejando dados para a tabela `financial_records`
--

INSERT INTO `financial_records` (`id`, `record_date`, `type`, `status`, `category`, `subcategory`, `description`, `amount`, `payment_method`, `reference`, `related_animal_id`, `created_by`, `farm_id`, `created_at`, `updated_at`) VALUES
(11, '2025-01-01', 'receita', 'completed', 'Venda de Leite', 'Leite A', 'Venda de leite tipo A', 2500.00, 'dinheiro', NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(12, '2025-01-02', 'receita', 'completed', 'Venda de Leite', 'Leite B', 'Venda de leite tipo B', 1800.00, 'dinheiro', NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(13, '2025-01-03', 'receita', 'completed', 'Venda de Leite', 'Leite A', 'Venda de leite tipo A', 2200.00, 'dinheiro', NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(14, '2025-01-01', 'despesa', 'completed', 'Alimentação', 'Concentrado', 'Compra de ração concentrada', 800.00, 'dinheiro', NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(15, '2025-01-02', 'despesa', 'completed', 'Medicamentos', 'Vacinas', 'Vacinas para o rebanho', 300.00, 'dinheiro', NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(16, '2025-01-03', 'despesa', 'completed', 'Mão de Obra', 'Salários', 'Pagamento de funcionários', 1200.00, 'dinheiro', NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(17, '2025-01-04', 'despesa', 'completed', 'Alimentação', 'Volumoso', 'Compra de feno', 400.00, 'dinheiro', NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(18, '2025-01-05', 'despesa', 'completed', 'Medicamentos', 'Vermífugos', 'Vermífugos para o rebanho', 200.00, 'dinheiro', NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

-- --------------------------------------------------------

--
-- Estrutura para tabela `group_movements`
--

CREATE TABLE `group_movements` (
  `id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `from_group_id` int(11) DEFAULT NULL,
  `to_group_id` int(11) NOT NULL,
  `movement_date` date NOT NULL,
  `movement_time` time DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `automatic` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `moved_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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

--
-- Despejando dados para a tabela `health_alerts`
--

INSERT INTO `health_alerts` (`id`, `animal_id`, `alert_type`, `alert_date`, `alert_message`, `is_resolved`, `resolved_date`, `resolved_by`, `created_by`, `farm_id`, `created_at`, `updated_at`) VALUES
(0, 1, 'vacina', '2025-04-01', 'Vacina contra febre aftosa vence em 90 dias', 0, NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 2, 'vacina', '2025-04-01', 'Vacina contra febre aftosa vence em 90 dias', 0, NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 4, 'vacina', '2025-04-01', 'Vacina contra febre aftosa vence em 90 dias', 0, NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 1, 'vermifugo', '2025-04-02', 'Vermifugação vence em 90 dias', 0, NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 2, 'vermifugo', '2025-04-02', 'Vermifugação vence em 90 dias', 0, NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 4, 'vermifugo', '2025-04-02', 'Vermifugação vence em 90 dias', 0, NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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
(0, 2, '2025-10-20', 'Vacinação', 'hgd', 'antiviroso', '34', 34.00, '2024-02-03', 'junior', 2, 1, '2025-10-20 02:54:14', '2025-10-20 02:54:14'),
(0, 1, '2025-01-01', 'Vacinação', 'Vacina contra febre aftosa', 'Vacina Aftosa', '2ml', 25.00, '2025-04-01', 'Dr. João Silva', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 2, '2025-01-01', 'Vacinação', 'Vacina contra febre aftosa', 'Vacina Aftosa', '2ml', 25.00, '2025-04-01', 'Dr. João Silva', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 4, '2025-01-01', 'Vacinação', 'Vacina contra febre aftosa', 'Vacina Aftosa', '2ml', 25.00, '2025-04-01', 'Dr. João Silva', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 1, '2025-01-02', 'Vermifugação', 'Vermifugação preventiva', 'Ivermectina', '5ml', 15.00, '2025-04-02', 'Dr. João Silva', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 2, '2025-01-02', 'Vermifugação', 'Vermifugação preventiva', 'Ivermectina', '5ml', 15.00, '2025-04-02', 'Dr. João Silva', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 4, '2025-01-02', 'Vermifugação', 'Vermifugação preventiva', 'Ivermectina', '5ml', 15.00, '2025-04-02', 'Dr. João Silva', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 1, '2025-01-03', 'Medicamento', 'Tratamento preventivo', 'Penicilina', '10ml', 30.00, '2025-01-10', 'Dr. João Silva', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 2, '2025-01-03', 'Medicamento', 'Suplementação vitamínica', 'Vitamina A+D+E', '5ml', 20.00, '2025-01-10', 'Dr. João Silva', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 4, '2025-01-03', 'Medicamento', 'Tratamento preventivo', 'Penicilina', '10ml', 30.00, '2025-01-10', 'Dr. João Silva', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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

--
-- Despejando dados para a tabela `heat_cycles`
--

INSERT INTO `heat_cycles` (`id`, `animal_id`, `heat_date`, `heat_intensity`, `insemination_planned`, `notes`, `recorded_by`, `farm_id`, `created_at`, `updated_at`) VALUES
(0, 2, '2025-01-01', 'forte', 1, 'Cio bem definido', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 4, '2025-01-02', 'moderado', 1, 'Cio moderado', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 5, '2025-01-03', 'forte', 1, 'Cio intenso', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 6, '2025-01-04', 'leve', 0, 'Cio leve, aguardar próximo', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 7, '2025-01-05', 'forte', 1, 'Cio bem definido', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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
(1, 4, NULL, NULL, '2025-10-20', 'Alimentação', 1.000, 'Unidade', 0.00, 0.00, 234.00, 'concentrado', 0, 2, 1, '2025-10-20 22:33:55', '2025-10-20 22:33:55'),
(2, 4, 1, 1, '2025-01-01', 'Alimentação', 6.000, 'Litros', 0.60, 3.60, 3.60, 'Leite integral diário', 1, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(3, 4, 1, 2, '2025-01-01', 'Alimentação', 0.500, 'Kg', 1.80, 0.90, 0.90, 'Concentrado inicial', 1, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(4, 4, 2, 2, '2025-01-01', 'Alimentação', 3.000, 'Litros', 0.60, 1.80, 1.80, 'Sucedâneo na transição', 1, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(5, 4, 2, 3, '2025-01-01', 'Alimentação', 1.500, 'Kg', 1.80, 2.70, 2.70, 'Concentrado transição', 1, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(6, 4, 3, 4, '2025-01-01', 'Alimentação', 2.500, 'Kg', 1.50, 3.75, 3.75, 'Concentrado crescimento', 1, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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

--
-- Despejando dados para a tabela `heifer_daily_consumption`
--

INSERT INTO `heifer_daily_consumption` (`id`, `animal_id`, `consumption_date`, `age_days`, `phase_id`, `milk_liters`, `concentrate_kg`, `roughage_kg`, `weight_kg`, `notes`, `recorded_by`, `farm_id`, `created_at`) VALUES
(1, 4, '2025-01-01', 30, 1, 6.00, 0.50, 0.00, NULL, NULL, 2, 1, '2025-10-27 20:13:08'),
(2, 4, '2025-01-02', 31, 1, 6.00, 0.50, 0.00, NULL, NULL, 2, 1, '2025-10-27 20:13:08'),
(3, 4, '2025-01-03', 32, 1, 6.00, 0.50, 0.00, NULL, NULL, 2, 1, '2025-10-27 20:13:08'),
(4, 4, '2025-01-04', 33, 1, 6.00, 0.50, 0.00, NULL, NULL, 2, 1, '2025-10-27 20:13:08'),
(5, 4, '2025-01-05', 34, 1, 6.00, 0.50, 0.00, NULL, NULL, 2, 1, '2025-10-27 20:13:08');

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
  `insemination_time` time DEFAULT NULL,
  `insemination_type` enum('natural','inseminacao_artificial','transferencia_embriao') NOT NULL DEFAULT 'inseminacao_artificial',
  `technician` varchar(255) DEFAULT NULL,
  `technician_name` varchar(100) DEFAULT NULL,
  `technician_license` varchar(50) DEFAULT NULL,
  `semen_batch` varchar(50) DEFAULT NULL,
  `semen_expiry_date` date DEFAULT NULL,
  `semen_straw_number` varchar(50) DEFAULT NULL,
  `insemination_method` enum('IA','MO','FIV','IATF') DEFAULT 'IA',
  `pregnancy_check_date` date DEFAULT NULL,
  `pregnancy_result` enum('prenha','vazia','pendente','aborto') DEFAULT 'pendente',
  `pregnancy_check_method` enum('palpacao','ultrassom','exame_sangue') DEFAULT 'palpacao',
  `expected_calving_date` date DEFAULT NULL,
  `actual_calving_date` date DEFAULT NULL,
  `calving_result` enum('vivo','morto','natimorto') DEFAULT 'vivo',
  `calf_sex` enum('macho','femea') DEFAULT NULL,
  `calf_weight` decimal(5,2) DEFAULT NULL,
  `complications` text DEFAULT NULL,
  `cost` decimal(8,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `inseminations`
--

INSERT INTO `inseminations` (`id`, `animal_id`, `bull_id`, `insemination_date`, `insemination_time`, `insemination_type`, `technician`, `technician_name`, `technician_license`, `semen_batch`, `semen_expiry_date`, `semen_straw_number`, `insemination_method`, `pregnancy_check_date`, `pregnancy_result`, `pregnancy_check_method`, `expected_calving_date`, `actual_calving_date`, `calving_result`, `calf_sex`, `calf_weight`, `complications`, `cost`, `notes`, `recorded_by`, `farm_id`, `created_at`, `updated_at`) VALUES
(0, 2, 1, '2025-01-01', NULL, 'inseminacao_artificial', 'João Técnico', NULL, NULL, NULL, NULL, NULL, 'IA', NULL, 'pendente', 'palpacao', NULL, NULL, 'vivo', NULL, NULL, NULL, NULL, 'Inseminação bem sucedida', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 4, 2, '2025-01-02', NULL, 'inseminacao_artificial', 'João Técnico', NULL, NULL, NULL, NULL, NULL, 'IA', NULL, 'pendente', 'palpacao', NULL, NULL, 'vivo', NULL, NULL, NULL, NULL, 'Inseminação de qualidade', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 5, 1, '2025-01-03', NULL, 'inseminacao_artificial', 'João Técnico', NULL, NULL, NULL, NULL, NULL, 'IA', NULL, 'pendente', 'palpacao', NULL, NULL, 'vivo', NULL, NULL, NULL, NULL, 'Não pegou, repetir', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 6, 2, '2025-01-04', NULL, 'inseminacao_artificial', 'João Técnico', NULL, NULL, NULL, NULL, NULL, 'IA', NULL, 'pendente', 'palpacao', NULL, NULL, 'vivo', NULL, NULL, NULL, NULL, 'Inseminação eficaz', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 7, 1, '2025-01-05', NULL, 'inseminacao_artificial', 'João Técnico', NULL, NULL, NULL, NULL, NULL, 'IA', NULL, 'pendente', 'palpacao', NULL, NULL, 'vivo', NULL, NULL, NULL, NULL, 'Boa inseminação', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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

--
-- Despejando dados para a tabela `lactations`
--

INSERT INTO `lactations` (`id`, `animal_id`, `birth_id`, `lactation_start`, `lactation_end`, `total_volume`, `average_daily`, `peak_day`, `peak_volume`, `notes`, `recorded_by`, `farm_id`, `created_at`, `updated_at`) VALUES
(0, 1, NULL, '2024-06-01', NULL, 4500.00, 25.00, 45, 32.00, 'Lactação em andamento', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 2, NULL, '2024-08-15', NULL, 2800.00, 23.30, 35, 28.00, 'Lactação média', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 4, NULL, '2024-07-10', NULL, 3600.00, 24.00, 40, 30.00, 'Lactação produtiva', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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

--
-- Despejando dados para a tabela `maternity_alerts`
--

INSERT INTO `maternity_alerts` (`id`, `animal_id`, `pregnancy_id`, `alert_date`, `expected_birth`, `days_to_birth`, `alert_message`, `is_resolved`, `resolved_date`, `created_by`, `farm_id`, `created_at`, `updated_at`) VALUES
(0, 2, 0, '2025-09-08', '2025-10-08', 30, 'Parto previsto em 2025-10-08 - Preparar maternidade', 0, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 4, 0, '2025-09-09', '2025-10-09', 30, 'Parto previsto em 2025-10-09 - Preparar maternidade', 0, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 5, 0, '2025-09-10', '2025-10-10', 30, 'Parto previsto em 2025-10-10 - Preparar maternidade', 0, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 6, 0, '2025-09-11', '2025-10-11', 30, 'Parto previsto em 2025-10-11 - Preparar maternidade', 0, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 7, 0, '2025-09-12', '2025-10-12', 30, 'Parto previsto em 2025-10-12 - Preparar maternidade', 0, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 2, 0, '2025-09-08', '2025-10-08', 30, 'Parto previsto em 2025-10-08 - Preparar maternidade', 0, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 4, 0, '2025-09-09', '2025-10-09', 30, 'Parto previsto em 2025-10-09 - Preparar maternidade', 0, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 6, 0, '2025-09-11', '2025-10-11', 30, 'Parto previsto em 2025-10-11 - Preparar maternidade', 0, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 7, 0, '2025-09-12', '2025-10-12', 30, 'Parto previsto em 2025-10-12 - Preparar maternidade', 0, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 2, 1, '2025-01-01', '2025-10-08', 280, 'Parto esperado em 280 dias', 0, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 4, 2, '2025-01-02', '2025-10-09', 280, 'Parto esperado em 280 dias', 0, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 6, 4, '2025-01-04', '2025-10-11', 280, 'Parto esperado em 280 dias', 0, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 7, 5, '2025-01-05', '2025-10-12', 280, 'Parto esperado em 280 dias', 0, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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
(0, 'dipirona', 'antibiotico', NULL, 'ml', 3.00, 1.00, 23.00, '2029-04-21', 'vatscop', 1, 1, '2025-10-20 02:39:17', '2025-10-20 02:39:17'),
(11, 'Oxitetraciclina', 'antibiotico', 'Antibiótico de amplo espectro', 'ml', 800.00, 150.00, 18.50, '2026-12-31', 'VetCorp', 1, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(12, 'Vitamina B12', 'vitamina', 'Suplemento vitamínico B12', 'ml', 600.00, 100.00, 12.90, '2026-06-30', 'FarmVet', 1, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(13, 'Albendazol', 'vermifugo', 'Antiparasitário interno', 'ml', 400.00, 80.00, 14.80, '2026-03-31', 'AgroVet', 1, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(14, 'Dexametasona', 'antiinflamatorio', 'Anti-inflamatório', 'ml', 300.00, 60.00, 22.30, '2026-09-30', 'VetCorp', 1, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(15, 'Vacina Aftosa', 'vacina', 'Vacina contra febre aftosa', 'dose', 100.00, 20.00, 8.50, '2026-12-31', 'AgroVet', 1, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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

--
-- Despejando dados para a tabela `medication_applications`
--

INSERT INTO `medication_applications` (`id`, `animal_id`, `medication_id`, `application_date`, `quantity`, `notes`, `applied_by`, `farm_id`, `created_at`, `updated_at`) VALUES
(11, 1, 11, '2025-01-01', 10.00, 'Aplicação preventiva', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(12, 2, 11, '2025-01-01', 10.00, 'Aplicação preventiva', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(13, 4, 11, '2025-01-01', 10.00, 'Aplicação preventiva', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(14, 1, 12, '2025-01-02', 5.00, 'Suplementação vitamínica', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(15, 2, 12, '2025-01-02', 5.00, 'Suplementação vitamínica', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(16, 4, 12, '2025-01-02', 5.00, 'Suplementação vitamínica', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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

--
-- Despejando dados para a tabela `milk_production`
--

INSERT INTO `milk_production` (`id`, `animal_id`, `production_date`, `shift`, `volume`, `quality_score`, `temperature`, `fat_content`, `protein_content`, `somatic_cells`, `notes`, `recorded_by`, `farm_id`, `created_at`, `updated_at`) VALUES
(0, 1, '2025-01-01', 'manha', 28.50, 9.5, 4.2, 3.80, 3.20, 250000, 'Produção excelente', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 1, '2025-01-01', 'tarde', 26.00, 9.2, 4.1, 3.70, 3.10, 260000, 'Boa produção', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 1, '2025-01-01', 'noite', 24.00, 9.0, 4.0, 3.60, 3.00, 270000, 'Produção estável', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 1, '2025-01-02', 'manha', 29.00, 9.6, 4.2, 3.90, 3.30, 240000, 'Aumento na produção', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 1, '2025-01-02', 'tarde', 27.50, 9.3, 4.1, 3.80, 3.20, 250000, 'Mantendo qualidade', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 1, '2025-01-02', 'noite', 25.00, 9.1, 4.0, 3.70, 3.10, 260000, 'Boa consistência', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 2, '2025-01-01', 'manha', 22.00, 8.5, 4.0, 3.50, 3.00, 300000, 'Produção regular', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 2, '2025-01-01', 'tarde', 20.50, 8.2, 3.9, 3.40, 2.90, 310000, 'Boa qualidade', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 2, '2025-01-01', 'noite', 19.00, 8.0, 3.8, 3.30, 2.80, 320000, 'Produção estável', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 2, '2025-01-02', 'manha', 23.00, 8.6, 4.0, 3.60, 3.10, 290000, 'Pequeno aumento', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 2, '2025-01-02', 'tarde', 21.00, 8.3, 3.9, 3.50, 3.00, 300000, 'Mantendo padrão', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 2, '2025-01-02', 'noite', 19.50, 8.1, 3.8, 3.40, 2.90, 310000, 'Boa consistência', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 4, '2025-01-01', 'manha', 30.00, 9.8, 4.3, 4.00, 3.40, 200000, 'Excelente produção', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 4, '2025-01-01', 'tarde', 28.00, 9.5, 4.2, 3.90, 3.30, 210000, 'Alta qualidade', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 4, '2025-01-01', 'noite', 26.00, 9.3, 4.1, 3.80, 3.20, 220000, 'Produção consistente', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 4, '2025-01-02', 'manha', 31.00, 9.9, 4.3, 4.10, 3.50, 190000, 'Aumento na produção', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 4, '2025-01-02', 'tarde', 29.00, 9.6, 4.2, 4.00, 3.40, 200000, 'Mantendo excelência', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 4, '2025-01-02', 'noite', 27.00, 9.4, 4.1, 3.90, 3.30, 210000, 'Boa estabilidade', 2, 1, '2025-10-27 20:13:07', '2025-10-27 20:13:07'),
(0, 1, '2025-10-30', 'manha', 25.00, NULL, 4.0, 3.80, 3.20, 250000, NULL, 2, 1, '2025-10-30 16:47:21', '2025-10-30 16:47:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `type` enum('info','warning','error','success') NOT NULL DEFAULT 'info',
  `notification_type` enum('alert','reminder','info','success','warning','critical') NOT NULL DEFAULT 'info',
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_sent` tinyint(1) NOT NULL DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `read_date` timestamp NULL DEFAULT NULL,
  `related_table` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `link`, `type`, `notification_type`, `priority`, `is_read`, `is_sent`, `sent_at`, `expires_at`, `read_date`, `related_table`, `related_id`, `farm_id`, `created_at`, `updated_at`) VALUES
(0, 2, 'Vacinação Pendente', 'Vacina contra febre aftosa vence em 90 dias para 3 animais', NULL, 'warning', 'info', 'medium', 0, 0, NULL, NULL, NULL, 'animals', 1, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 2, 'Vermifugação Pendente', 'Vermifugação vence em 90 dias para 3 animais', NULL, 'warning', 'info', 'medium', 0, 0, NULL, NULL, NULL, 'animals', 1, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 2, 'Parto Esperado', 'Parto esperado para V002 em 280 dias', NULL, 'info', 'info', 'medium', 0, 0, NULL, NULL, NULL, 'animals', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 2, 'Parto Esperado', 'Parto esperado para V004 em 280 dias', NULL, 'info', 'info', 'medium', 0, 0, NULL, NULL, NULL, 'animals', 4, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 2, 'Produção Alta', 'Vaca V001 com produção acima da média', NULL, 'success', 'info', 'medium', 0, 0, NULL, NULL, NULL, 'animals', 1, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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

--
-- Despejando dados para a tabela `pedigree_records`
--

INSERT INTO `pedigree_records` (`id`, `animal_id`, `generation`, `position`, `related_animal_id`, `animal_number`, `animal_name`, `breed`, `notes`, `farm_id`, `created_at`, `updated_at`) VALUES
(0, 1, 1, 'pai', 5, NULL, 'Touro01', 'Holandês', 'Pai da V001', 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 1, 1, 'mae', 2, NULL, 'Luna', 'Gir', 'Mãe da V001', 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 2, 1, 'pai', 5, NULL, 'Touro01', 'Holandês', 'Pai da V002', 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 2, 1, 'mae', 3, NULL, 'Maya', 'Girolanda', 'Mãe da V002', 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 4, 1, 'pai', 5, NULL, 'Touro01', 'Holandês', 'Pai da V004', 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 4, 1, 'mae', 1, NULL, 'Bella', 'Holandesa', 'Mãe da V004', 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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
-- Despejando dados para a tabela `pregnancy_controls`
--

INSERT INTO `pregnancy_controls` (`id`, `animal_id`, `insemination_id`, `pregnancy_date`, `expected_birth`, `pregnancy_stage`, `ultrasound_date`, `ultrasound_result`, `notes`, `recorded_by`, `farm_id`, `created_at`, `updated_at`) VALUES
(0, 2, 0, '2025-01-01', '2025-10-08', 'inicial', NULL, NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 4, 0, '2025-01-02', '2025-10-09', 'inicial', NULL, NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 5, 0, '2025-01-03', '2025-10-10', 'inicial', NULL, NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 6, 0, '2025-01-04', '2025-10-11', 'inicial', NULL, NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 7, 0, '2025-01-05', '2025-10-12', 'inicial', NULL, NULL, NULL, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 2, 1, '2025-01-01', '2025-10-08', 'inicial', '2025-01-15', 'positivo', 'Prenhez confirmada', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 4, 2, '2025-01-02', '2025-10-09', 'inicial', '2025-01-16', 'positivo', 'Prenhez confirmada', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 6, 4, '2025-01-04', '2025-10-11', 'inicial', '2025-01-18', 'positivo', 'Prenhez confirmada', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, 7, 5, '2025-01-05', '2025-10-12', 'inicial', '2025-01-19', 'positivo', 'Prenhez confirmada', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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
  `temperature` decimal(4,1) DEFAULT NULL,
  `ph_level` decimal(3,2) DEFAULT NULL,
  `antibiotics` enum('negativo','positivo','indefinido') DEFAULT NULL,
  `other_results` text DEFAULT NULL,
  `laboratory` varchar(255) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `quality_tests`
--

INSERT INTO `quality_tests` (`id`, `test_date`, `test_type`, `animal_id`, `fat_content`, `protein_content`, `somatic_cells`, `bacteria_count`, `temperature`, `ph_level`, `antibiotics`, `other_results`, `laboratory`, `cost`, `recorded_by`, `farm_id`, `created_at`, `updated_at`) VALUES
(0, '2025-01-01', 'qualidade_leite', 1, 3.80, 3.20, 250000, 50000, NULL, NULL, 'negativo', 'Qualidade excelente', 'LabVet', 50.00, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, '2025-01-01', 'qualidade_leite', 2, 3.50, 3.00, 300000, 75000, NULL, NULL, 'negativo', 'Boa qualidade', 'LabVet', 50.00, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, '2025-01-01', 'qualidade_leite', 4, 4.00, 3.40, 200000, 40000, NULL, NULL, 'negativo', 'Qualidade premium', 'LabVet', 50.00, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, '2025-01-02', 'qualidade_leite', 1, 3.90, 3.30, 240000, 45000, NULL, NULL, 'negativo', 'Mantendo excelência', 'LabVet', 50.00, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, '2025-01-02', 'qualidade_leite', 2, 3.60, 3.10, 280000, 70000, NULL, NULL, 'negativo', 'Boa consistência', 'LabVet', 50.00, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, '2025-01-02', 'qualidade_leite', 4, 4.10, 3.50, 190000, 35000, NULL, NULL, 'negativo', 'Qualidade superior', 'LabVet', 50.00, 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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
-- Estrutura para tabela `semen_catalog`
--

CREATE TABLE `semen_catalog` (
  `id` int(11) NOT NULL,
  `bull_id` int(11) NOT NULL,
  `batch_number` varchar(50) NOT NULL,
  `production_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `straws_available` int(11) DEFAULT 0,
  `straws_used` int(11) DEFAULT 0,
  `price_per_straw` decimal(8,2) NOT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `quality_grade` enum('A','B','C','Premium') DEFAULT 'A',
  `genetic_tests` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `farm_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `semen_catalog`
--

INSERT INTO `semen_catalog` (`id`, `bull_id`, `batch_number`, `production_date`, `expiry_date`, `straws_available`, `straws_used`, `price_per_straw`, `supplier`, `storage_location`, `quality_grade`, `genetic_tests`, `notes`, `farm_id`, `created_at`, `updated_at`) VALUES
(1, 1, 'SE001', '2024-12-01', '2025-12-01', 50, 0, 150.00, 'SemenBrasil', 'Freezer A', 'A', 'Testes genéticos completos', 'Sêmen de alta qualidade', 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(2, 2, 'SE002', '2024-12-01', '2025-12-01', 40, 0, 150.00, 'SemenBrasil', 'Freezer A', 'A', 'Testes genéticos completos', 'Sêmen de alta qualidade', 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(3, 3, 'SE003', '2024-12-01', '2025-12-01', 30, 0, 120.00, 'SemenBrasil', 'Freezer B', 'A', 'Testes genéticos básicos', 'Sêmen de qualidade', 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sync_logs`
--

CREATE TABLE `sync_logs` (
  `id` int(11) NOT NULL,
  `sync_type` enum('backup','restore','export','import','sync') NOT NULL COMMENT 'Tipo de sincronização',
  `status` enum('success','error','warning') NOT NULL COMMENT 'Status da operação',
  `message` text DEFAULT NULL COMMENT 'Mensagem da operação',
  `file_path` varchar(500) DEFAULT NULL COMMENT 'Caminho do arquivo',
  `file_size` bigint(20) DEFAULT NULL COMMENT 'Tamanho do arquivo',
  `duration` int(11) DEFAULT NULL COMMENT 'Duração em segundos',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data da operação',
  `created_by` int(11) DEFAULT NULL COMMENT 'Usuário que executou'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Logs de operações de backup e sincronização';

-- --------------------------------------------------------

--
-- Estrutura para tabela `transponder_readings`
--

CREATE TABLE `transponder_readings` (
  `id` int(11) NOT NULL,
  `transponder_id` int(11) NOT NULL,
  `reading_date` datetime NOT NULL DEFAULT current_timestamp(),
  `reader_id` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `signal_strength` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `report_farm_logo_base64` text DEFAULT NULL COMMENT 'Logo da fazenda em base64 para relatórios PDF',
  `report_farm_name` varchar(100) DEFAULT NULL COMMENT 'Nome da fazenda para exibir nos relatórios PDF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `farm_id`, `cpf`, `phone`, `address`, `hire_date`, `salary`, `profile_photo`, `password_changed_at`, `password_change_required`, `is_active`, `last_login`, `created_at`, `updated_at`, `report_farm_logo_base64`, `report_farm_name`) VALUES
(1, 'Fernando Silva', 'Fernando@lactech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'proprietario', 1, NULL, '(11) 99999-0001', NULL, '2020-01-01', NULL, NULL, '2020-01-01 00:00:00', 0, 1, NULL, '2025-10-13 14:21:52', '2025-10-13 14:21:52', NULL, NULL);
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `farm_id`, `cpf`, `phone`, `address`, `hire_date`, `salary`, `profile_photo`, `password_changed_at`, `password_change_required`, `is_active`, `last_login`, `created_at`, `updated_at`, `report_farm_logo_base64`, `report_farm_name`) VALUES
(2, 'Junior Alves', 'Junior@lactech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gerente', 1, NULL, '(11) 99999-0002', NULL, '2020-01-01', NULL, 'uploads/profiles/profile_2_1761867354.jpg', '2020-01-01 00:00:00', 0, 1, '2025-11-01 10:28:25', '2025-10-13 14:21:52', '2025-11-01 12:50:44', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAfQAAAH0CAYAAADL1t+KAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAEpGlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSfvu78nIGlkPSdXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQnPz4KPHg6eG1wbWV0YSB4bWxuczp4PSdhZG9iZTpuczptZXRhLyc+CjxyZGY6UkRGIHhtbG5zOnJkZj0naHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyc+CgogPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9JycKICB4bWxuczpBdHRyaWI9J2h0dHA6Ly9ucy5hdHRyaWJ1dGlvbi5jb20vYWRzLzEuMC8nPgogIDxBdHRyaWI6QWRzPgogICA8cmRmOlNlcT4KICAgIDxyZGY6bGkgcmRmOnBhcnNlVHlwZT0nUmVzb3VyY2UnPgogICAgIDxBdHRyaWI6Q3JlYXRlZD4yMDI1LTA4LTA2PC9BdHRyaWI6Q3JlYXRlZD4KICAgICA8QXR0cmliOkV4dElkPjUwNzQ0MTVkLTVmMDgtNGY3OS1iNmM5LWIzNmVkNmIwZmIwZjwvQXR0cmliOkV4dElkPgogICAgIDxBdHRyaWI6RmJJZD41MjUyNjU5MTQxNzk1ODA8L0F0dHJpYjpGYklkPgogICAgIDxBdHRyaWI6VG91Y2hUeXBlPjI8L0F0dHJpYjpUb3VjaFR5cGU+CiAgICA8L3JkZjpsaT4KICAgPC9yZGY6U2VxPgogIDwvQXR0cmliOkFkcz4KIDwvcmRmOkRlc2NyaXB0aW9uPgoKIDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PScnCiAgeG1sbnM6ZGM9J2h0dHA6Ly9wdXJsLm9yZy9kYy9lbGVtZW50cy8xLjEvJz4KICA8ZGM6dGl0bGU+CiAgIDxyZGY6QWx0PgogICAgPHJkZjpsaSB4bWw6bGFuZz0neC1kZWZhdWx0Jz5sYWN0ZWNoIC0gNTwvcmRmOmxpPgogICA8L3JkZjpBbHQ+CiAgPC9kYzp0aXRsZT4KIDwvcmRmOkRlc2NyaXB0aW9uPgoKIDxyZGY6RGVzY3JpcHRpb24gcmRmOmFi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', NULL);

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
-- Estrutura para tabela `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` text NOT NULL,
  `data_type` enum('string','number','boolean','json') DEFAULT 'string',
  `category` enum('notifications','interface','reports','privacy','other') DEFAULT 'other',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `user_preferences`
--

INSERT INTO `user_preferences` (`id`, `user_id`, `preference_key`, `preference_value`, `data_type`, `category`, `updated_at`) VALUES
(1, 1, 'notifications_enabled', 'true', 'boolean', 'notifications', '2025-10-27 20:13:06'),
(2, 2, 'notifications_enabled', 'true', 'boolean', 'notifications', '2025-10-27 20:13:06'),
(11, 2, 'notifications_enabled_test', 'true', 'boolean', 'notifications', '2025-10-27 20:13:08'),
(12, 2, 'theme_test', 'light', 'string', '', '2025-10-27 20:13:08'),
(13, 2, 'language_test', 'pt-BR', 'string', '', '2025-10-27 20:13:08'),
(14, 2, 'dashboard_layout_test', 'grid', 'string', '', '2025-10-27 20:13:08'),
(15, 2, 'auto_refresh_test', 'true', 'boolean', '', '2025-10-27 20:13:08');

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` varchar(20) DEFAULT NULL,
  `device_name` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `last_activity` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_id`, `ip_address`, `user_agent`, `device_type`, `device_name`, `location`, `last_activity`, `created_at`) VALUES
(1, 2, 'skq782sf8v09cm1g2p3krstd1i', '170.84.77.254', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop', 'Chrome - Windows 10/11', 'Maranguape, Ceará, Brazil', '2025-10-31 00:21:38', '2025-10-30 20:13:16'),
(2, 2, 'ca5uohkgu0k30a6b5g1tu6fpj7', '170.84.77.254', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'desktop', 'Chrome - Windows 10/11', 'Maranguape, Ceará, Brazil', '2025-11-01 11:11:35', '2025-11-01 07:29:08');

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

--
-- Despejando dados para a tabela `vaccination_programs`
--

INSERT INTO `vaccination_programs` (`id`, `name`, `description`, `target_age_min`, `target_age_max`, `frequency_days`, `is_active`, `farm_id`, `created_at`, `updated_at`) VALUES
(11, 'Programa Aftosa', 'Vacinação contra febre aftosa', 0, 9999, 90, 1, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(12, 'Programa Vermifugação', 'Vermifugação preventiva', 0, 9999, 90, 1, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(13, 'Programa Vitamínico', 'Suplementação vitamínica', 0, 9999, 30, 1, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08');

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
(0, '2025-10-20', 'noite', 123.00, 1, 123.00, NULL, 2, 1, '2025-10-21 01:31:29', '2025-10-21 01:31:29'),
(0, '2025-01-01', 'manha', 150.50, 5, 30.10, 'Boa produção matinal', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, '2025-01-01', 'tarde', 140.00, 5, 28.00, 'Produção regular', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, '2025-01-01', 'noite', 130.00, 5, 26.00, 'Produção noturna', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, '2025-01-02', 'manha', 155.00, 5, 31.00, 'Aumento na produção', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, '2025-01-02', 'tarde', 145.00, 5, 29.00, 'Mantendo qualidade', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, '2025-01-02', 'noite', 135.00, 5, 27.00, 'Boa consistência', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, '2025-01-03', 'manha', 160.00, 5, 32.00, 'Excelente produção', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, '2025-01-03', 'tarde', 150.00, 5, 30.00, 'Alta qualidade', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, '2025-01-03', 'noite', 140.00, 5, 28.00, 'Produção estável', 2, 1, '2025-10-27 20:13:08', '2025-10-27 20:13:08'),
(0, '2025-10-30', 'manha', 150.00, 5, 30.00, NULL, 2, 1, '2025-10-30 16:47:21', '2025-10-30 16:47:21');

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
-- Estrutura stand-in para view `v_animals_with_groups`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_animals_with_groups` (
`id` int(11)
,`animal_number` varchar(50)
,`name` varchar(255)
,`breed` varchar(100)
,`gender` enum('femea','macho')
,`birth_date` date
,`birth_weight` decimal(6,2)
,`father_id` int(11)
,`mother_id` int(11)
,`status` enum('Lactante','Seco','Novilha','Vaca','Bezerra','Bezerro','Touro')
,`current_group_id` int(11)
,`health_status` enum('saudavel','doente','tratamento','quarentena')
,`reproductive_status` enum('vazia','prenha','lactante','seca','outros')
,`entry_date` date
,`exit_date` date
,`exit_reason` varchar(255)
,`farm_id` int(11)
,`notes` text
,`is_active` tinyint(1)
,`created_at` timestamp
,`updated_at` timestamp
,`father_name` varchar(255)
,`mother_name` varchar(255)
,`group_name` varchar(100)
,`group_type` enum('lactante','seco','novilha','pre_parto','pos_parto','hospital','quarentena','pasto','outros')
,`group_color` varchar(7)
,`age_days` int(7)
,`age_years` int(8)
,`transponder_code` varchar(50)
,`transponder_type` enum('rfid','visual','electronic','microchip')
,`latest_bcs` decimal(2,1)
,`primary_photo` varchar(500)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_bull_statistics`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_bull_statistics` (
`id` int(11)
,`bull_name` varchar(255)
,`breed` varchar(100)
,`status` varchar(5)
,`total_inseminations` bigint(21)
,`successful_inseminations` int(1)
,`pregnancy_rate` decimal(3,2)
,`conception_rate` decimal(3,2)
,`avg_services_per_conception` decimal(3,2)
,`total_cost` decimal(3,2)
,`cost_per_pregnancy` decimal(3,2)
,`last_insemination` date
,`first_insemination` date
);

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
-- Estrutura stand-in para view `v_pending_actions_summary`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_pending_actions_summary` (
`action_type` varchar(13)
,`count` bigint(21)
,`description` varchar(25)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_recent_inseminations`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_recent_inseminations` (
`id` int(11)
,`insemination_date` date
,`insemination_time` binary(0)
,`animal_name` varchar(255)
,`bull_name` varchar(255)
,`bull_breed` varchar(100)
,`technician` varchar(255)
,`insemination_type` enum('natural','inseminacao_artificial','transferencia_embriao')
,`pregnancy_result` varchar(8)
,`expected_calving_date` date
,`cost` decimal(3,2)
,`days_since_insemination` int(7)
,`status_description` varchar(17)
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
-- Estrutura para view `v_animals_with_groups`
--
DROP TABLE IF EXISTS `v_animals_with_groups`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_animals_with_groups`  AS SELECT `a`.`id` AS `id`, `a`.`animal_number` AS `animal_number`, `a`.`name` AS `name`, `a`.`breed` AS `breed`, `a`.`gender` AS `gender`, `a`.`birth_date` AS `birth_date`, `a`.`birth_weight` AS `birth_weight`, `a`.`father_id` AS `father_id`, `a`.`mother_id` AS `mother_id`, `a`.`status` AS `status`, `a`.`current_group_id` AS `current_group_id`, `a`.`health_status` AS `health_status`, `a`.`reproductive_status` AS `reproductive_status`, `a`.`entry_date` AS `entry_date`, `a`.`exit_date` AS `exit_date`, `a`.`exit_reason` AS `exit_reason`, `a`.`farm_id` AS `farm_id`, `a`.`notes` AS `notes`, `a`.`is_active` AS `is_active`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at`, `f`.`name` AS `father_name`, `m`.`name` AS `mother_name`, `g`.`group_name` AS `group_name`, `g`.`group_type` AS `group_type`, `g`.`color_code` AS `group_color`, to_days(curdate()) - to_days(`a`.`birth_date`) AS `age_days`, floor((to_days(curdate()) - to_days(`a`.`birth_date`)) / 365) AS `age_years`, `t`.`transponder_code` AS `transponder_code`, `t`.`transponder_type` AS `transponder_type`, (select `bcs`.`score` from `body_condition_scores` `bcs` where `bcs`.`animal_id` = `a`.`id` order by `bcs`.`evaluation_date` desc limit 1) AS `latest_bcs`, (select `ap`.`photo_url` from `animal_photos` `ap` where `ap`.`animal_id` = `a`.`id` and `ap`.`is_primary` = 1 limit 1) AS `primary_photo` FROM ((((`animals` `a` left join `animals` `f` on(`a`.`father_id` = `f`.`id`)) left join `animals` `m` on(`a`.`mother_id` = `m`.`id`)) left join `animal_groups` `g` on(`a`.`current_group_id` = `g`.`id`)) left join `animal_transponders` `t` on(`a`.`id` = `t`.`animal_id` and `t`.`is_active` = 1)) WHERE `a`.`is_active` = 1 ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_bull_statistics`
--
DROP TABLE IF EXISTS `v_bull_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_bull_statistics`  AS SELECT `b`.`id` AS `id`, `b`.`name` AS `bull_name`, `b`.`breed` AS `breed`, 'ativo' AS `status`, count(`i`.`id`) AS `total_inseminations`, 0 AS `successful_inseminations`, 0.00 AS `pregnancy_rate`, 0.00 AS `conception_rate`, 0.00 AS `avg_services_per_conception`, 0.00 AS `total_cost`, 0.00 AS `cost_per_pregnancy`, max(`i`.`insemination_date`) AS `last_insemination`, min(`i`.`insemination_date`) AS `first_insemination` FROM (`bulls` `b` left join `inseminations` `i` on(`b`.`id` = `i`.`bull_id`)) WHERE `b`.`farm_id` = 1 GROUP BY `b`.`id`, `b`.`name`, `b`.`breed` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_heifer_costs_by_category`
--
DROP TABLE IF EXISTS `v_heifer_costs_by_category`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_heifer_costs_by_category`  AS SELECT coalesce(`hcc`.`category_type`,`hc`.`cost_category`) AS `cost_category`, `hcc`.`category_name` AS `category_name`, count(`hc`.`id`) AS `total_records`, sum(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)) AS `total_amount`, avg(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)) AS `average_amount`, min(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)) AS `min_amount`, max(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)) AS `max_amount` FROM (`heifer_costs` `hc` left join `heifer_cost_categories` `hcc` on(`hc`.`category_id` = `hcc`.`id`)) WHERE `hc`.`farm_id` = 1 GROUP BY coalesce(`hcc`.`category_type`,`hc`.`cost_category`), `hcc`.`category_name` ORDER BY sum(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)) DESC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_heifer_costs_by_phase`
--
DROP TABLE IF EXISTS `v_heifer_costs_by_phase`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_heifer_costs_by_phase`  AS SELECT `hc`.`animal_id` AS `animal_id`, `a`.`animal_number` AS `ear_tag`, `a`.`name` AS `name`, `hp`.`phase_name` AS `phase_name`, `hp`.`start_day` AS `start_day`, `hp`.`end_day` AS `end_day`, coalesce(`hcc`.`category_type`,`hc`.`cost_category`) AS `category_type`, sum(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)) AS `phase_cost`, count(`hc`.`id`) AS `cost_records`, avg(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)) AS `avg_cost_per_record` FROM (((`heifer_costs` `hc` join `animals` `a` on(`hc`.`animal_id` = `a`.`id`)) left join `heifer_phases` `hp` on(`hc`.`phase_id` = `hp`.`id`)) left join `heifer_cost_categories` `hcc` on(`hc`.`category_id` = `hcc`.`id`)) GROUP BY `hc`.`animal_id`, `a`.`animal_number`, `a`.`name`, `hp`.`phase_name`, `hp`.`start_day`, `hp`.`end_day`, coalesce(`hcc`.`category_type`,`hc`.`cost_category`) ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_heifer_total_costs`
--
DROP TABLE IF EXISTS `v_heifer_total_costs`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_heifer_total_costs`  AS SELECT `a`.`id` AS `animal_id`, `a`.`animal_number` AS `animal_number`, `a`.`name` AS `animal_name`, `a`.`breed` AS `breed`, `a`.`birth_date` AS `birth_date`, to_days(curdate()) - to_days(`a`.`birth_date`) AS `age_days`, floor((to_days(curdate()) - to_days(`a`.`birth_date`)) / 30) AS `age_months`, count(`hc`.`id`) AS `total_cost_records`, coalesce(sum(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)),0) AS `total_cost`, coalesce(avg(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)),0) AS `average_cost`, coalesce(sum(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)),0) / nullif(to_days(curdate()) - to_days(`a`.`birth_date`),0) AS `avg_daily_cost`, `hp`.`phase_name` AS `current_phase` FROM ((`animals` `a` left join `heifer_costs` `hc` on(`a`.`id` = `hc`.`animal_id`)) left join `heifer_phases` `hp` on(to_days(curdate()) - to_days(`a`.`birth_date`) between `hp`.`start_day` and `hp`.`end_day`)) WHERE (`a`.`status` = 'Novilha' OR `a`.`status` = 'Bezerra' OR `a`.`status` = 'Bezerro') AND `a`.`is_active` = 1 GROUP BY `a`.`id`, `a`.`animal_number`, `a`.`name`, `a`.`breed`, `a`.`birth_date`, `hp`.`phase_name` ORDER BY coalesce(sum(coalesce(`hc`.`total_cost`,`hc`.`cost_amount`)),0) DESC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_low_stock_medications`
--
DROP TABLE IF EXISTS `v_low_stock_medications`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_low_stock_medications`  AS SELECT `m`.`id` AS `id`, `m`.`farm_id` AS `farm_id`, `m`.`name` AS `name`, `m`.`type` AS `type`, `m`.`supplier` AS `supplier`, `m`.`expiry_date` AS `expiry_date`, `m`.`stock_quantity` AS `stock_quantity`, `m`.`unit` AS `unit`, `m`.`min_stock` AS `min_stock`, `m`.`unit_price` AS `unit_price`, `m`.`description` AS `description`, `m`.`is_active` AS `is_active`, `m`.`created_at` AS `created_at`, `m`.`updated_at` AS `updated_at`, CASE WHEN `m`.`stock_quantity` = 0 THEN 'Sem Estoque' WHEN `m`.`stock_quantity` <= `m`.`min_stock` * 0.5 THEN 'Crítico' WHEN `m`.`stock_quantity` <= `m`.`min_stock` THEN 'Baixo' ELSE 'Normal' END AS `stock_status` FROM `medications` AS `m` WHERE `m`.`stock_quantity` <= `m`.`min_stock` AND `m`.`is_active` = 1 ORDER BY `m`.`stock_quantity` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_pending_actions_summary`
--
DROP TABLE IF EXISTS `v_pending_actions_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_pending_actions_summary` AS 
SELECT 'heat_expected' AS `action_type`, count(0) AS `count`, 'Cio previsto (7 dias)' AS `description` 
FROM (`heat_cycles` `hc` join `animals` `a` on(`hc`.`animal_id` = `a`.`id`)) 
WHERE `a`.`is_active` = 1 AND `hc`.`heat_date` between curdate() and curdate() + interval 7 day
UNION ALL
SELECT 'calving_soon' AS `action_type`, count(0) AS `count`, 'Partos próximos (30 dias)' AS `description` 
FROM (`pregnancy_controls` `pc` join `animals` `a` on(`pc`.`animal_id` = `a`.`id`)) 
WHERE `a`.`is_active` = 1 and `pc`.`expected_birth` between curdate() and curdate() + interval 30 day
UNION ALL
SELECT 'low_bcs' AS `action_type`, count(distinct `bcs`.`animal_id`) AS `count`, 'BCS baixo (< 2.5)' AS `description` 
FROM ((`body_condition_scores` `bcs` join (
    select `body_condition_scores`.`animal_id` AS `animal_id`, max(`body_condition_scores`.`evaluation_date`) AS `max_date` 
    from `body_condition_scores` 
    group by `body_condition_scores`.`animal_id`
) `latest` on(`bcs`.`animal_id` = `latest`.`animal_id` and `bcs`.`evaluation_date` = `latest`.`max_date`)) 
join `animals` `a` on(`bcs`.`animal_id` = `a`.`id`)) 
WHERE `bcs`.`score` < 2.5 and `a`.`is_active` = 1;

-- --------------------------------------------------------

--
-- Estrutura para view `v_recent_inseminations`
--
DROP TABLE IF EXISTS `v_recent_inseminations`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_recent_inseminations`  AS SELECT `i`.`id` AS `id`, `i`.`insemination_date` AS `insemination_date`, NULL AS `insemination_time`, `a`.`name` AS `animal_name`, `b`.`name` AS `bull_name`, `b`.`breed` AS `bull_breed`, `i`.`technician` AS `technician`, `i`.`insemination_type` AS `insemination_type`, 'pendente' AS `pregnancy_result`, `i`.`insemination_date`+ interval 280 day AS `expected_calving_date`, 0.00 AS `cost`, to_days(curdate()) - to_days(`i`.`insemination_date`) AS `days_since_insemination`, CASE WHEN to_days(curdate()) - to_days(`i`.`insemination_date`) >= 21 THEN 'Pronto para teste' WHEN to_days(curdate()) - to_days(`i`.`insemination_date`) < 21 THEN 'Aguardando' ELSE 'Indefinido' END AS `status_description` FROM ((`inseminations` `i` join `animals` `a` on(`i`.`animal_id` = `a`.`id`)) join `bulls` `b` on(`i`.`bull_id` = `b`.`id`)) WHERE `i`.`farm_id` = 1 ORDER BY `i`.`insemination_date` DESC ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `action_lists_cache`
--
ALTER TABLE `action_lists_cache`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_list_type` (`list_type`),
  ADD KEY `idx_action_date` (`action_date`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_type_date` (`list_type`,`action_date`,`is_completed`);

--
-- Índices de tabela `ai_predictions`
--
ALTER TABLE `ai_predictions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_animal_type` (`animal_id`,`prediction_type`),
  ADD KEY `idx_predicted_date` (`predicted_date`),
  ADD KEY `idx_confidence` (`confidence_score`);

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
  ADD KEY `birth_date` (`birth_date`),
  ADD KEY `idx_current_group` (`current_group_id`),
  ADD KEY `idx_status_active` (`status`,`is_active`),
  ADD KEY `idx_farm_active_status` (`farm_id`,`is_active`,`status`);

--
-- Índices de tabela `animal_groups`
--
ALTER TABLE `animal_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_group_name_farm` (`group_name`,`farm_id`),
  ADD KEY `idx_type` (`group_type`),
  ADD KEY `idx_active` (`is_active`);

--
-- Índices de tabela `animal_photos`
--
ALTER TABLE `animal_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_animal` (`animal_id`),
  ADD KEY `idx_animal_primary` (`animal_id`,`is_primary`),
  ADD KEY `idx_type` (`photo_type`);

--
-- Índices de tabela `animal_transponders`
--
ALTER TABLE `animal_transponders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transponder_code` (`transponder_code`),
  ADD UNIQUE KEY `unique_transponder_code` (`transponder_code`),
  ADD KEY `idx_animal` (`animal_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_code_lookup` (`transponder_code`,`is_active`);

--
-- Índices de tabela `backup_records`
--
ALTER TABLE `backup_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Índices de tabela `backup_settings`
--
ALTER TABLE `backup_settings`
  ADD PRIMARY KEY (`id`);

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
-- Índices de tabela `body_condition_scores`
--
ALTER TABLE `body_condition_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_animal_date` (`animal_id`,`evaluation_date`),
  ADD KEY `idx_score` (`score`),
  ADD KEY `idx_animal_score` (`animal_id`,`score`);

--
-- Índices de tabela `bulls`
--
ALTER TABLE `bulls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bull_number` (`bull_number`),
  ADD UNIQUE KEY `bull_code` (`bull_code`),
  ADD KEY `farm_id` (`farm_id`),
  ADD KEY `breed` (`breed`),
  ADD KEY `idx_genetic_merit` (`genetic_merit`),
  ADD KEY `idx_fertility_index` (`fertility_index`);

--
-- Índices de tabela `bull_performance`
--
ALTER TABLE `bull_performance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bull` (`bull_id`),
  ADD KEY `idx_period` (`period_start`,`period_end`);

--
-- Índices de tabela `farms`
--
ALTER TABLE `farms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Índices de tabela `feed_records`
--
ALTER TABLE `feed_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_animal_date` (`animal_id`,`feed_date`),
  ADD KEY `idx_feed_date` (`feed_date`);

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
-- Índices de tabela `group_movements`
--
ALTER TABLE `group_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_animal` (`animal_id`),
  ADD KEY `idx_movement_date` (`movement_date`),
  ADD KEY `idx_animal_date` (`animal_id`,`movement_date`);

--
-- Índices de tabela `health_records`
--
ALTER TABLE `health_records`
  ADD KEY `idx_next_date` (`next_date`);

--
-- Índices de tabela `heat_cycles`
--
ALTER TABLE `heat_cycles`
  ADD KEY `idx_animal_date_desc` (`animal_id`,`heat_date`);

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
-- Índices de tabela `inseminations`
--
ALTER TABLE `inseminations`
  ADD KEY `idx_bull_date` (`bull_id`,`insemination_date`),
  ADD KEY `idx_pregnancy_date` (`pregnancy_result`,`insemination_date`);

--
-- Índices de tabela `milk_production`
--
ALTER TABLE `milk_production`
  ADD KEY `idx_date_animal` (`production_date`,`animal_id`);

--
-- Índices de tabela `notifications`
--
ALTER TABLE `notifications`
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_sent` (`is_sent`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Índices de tabela `pregnancy_controls`
--
ALTER TABLE `pregnancy_controls`
  ADD KEY `idx_expected_birth_range` (`expected_birth`,`farm_id`);

--
-- Índices de tabela `semen_catalog`
--
ALTER TABLE `semen_catalog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bull` (`bull_id`),
  ADD KEY `idx_batch` (`batch_number`),
  ADD KEY `idx_expiry` (`expiry_date`),
  ADD KEY `idx_farm` (`farm_id`);

--
-- Índices de tabela `sync_logs`
--
ALTER TABLE `sync_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sync_type` (`sync_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Índices de tabela `transponder_readings`
--
ALTER TABLE `transponder_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transponder_date` (`transponder_id`,`reading_date`),
  ADD KEY `idx_reading_date` (`reading_date`);

--
-- Índices de tabela `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_pref` (`user_id`,`preference_key`);

--
-- Índices de tabela `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- Índices de tabela `volume_records`
--
ALTER TABLE `volume_records`
  ADD KEY `idx_date_shift` (`record_date`,`shift`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `action_lists_cache`
--
ALTER TABLE `action_lists_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `ai_predictions`
--
ALTER TABLE `ai_predictions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `animals`
--
ALTER TABLE `animals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `animal_groups`
--
ALTER TABLE `animal_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `animal_photos`
--
ALTER TABLE `animal_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `animal_transponders`
--
ALTER TABLE `animal_transponders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `backup_records`
--
ALTER TABLE `backup_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `backup_settings`
--
ALTER TABLE `backup_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `body_condition_scores`
--
ALTER TABLE `body_condition_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `bulls`
--
ALTER TABLE `bulls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `bull_performance`
--
ALTER TABLE `bull_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `farms`
--
ALTER TABLE `farms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `feed_records`
--
ALTER TABLE `feed_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `group_movements`
--
ALTER TABLE `group_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `heifer_costs`
--
ALTER TABLE `heifer_costs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `heifer_cost_categories`
--
ALTER TABLE `heifer_cost_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `heifer_daily_consumption`
--
ALTER TABLE `heifer_daily_consumption`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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

--
-- AUTO_INCREMENT de tabela `semen_catalog`
--
ALTER TABLE `semen_catalog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `sync_logs`
--
ALTER TABLE `sync_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `transponder_readings`
--
ALTER TABLE `transponder_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `bull_performance`
--
ALTER TABLE `bull_performance`
  ADD CONSTRAINT `bull_performance_ibfk_1` FOREIGN KEY (`bull_id`) REFERENCES `bulls` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `semen_catalog`
--
ALTER TABLE `semen_catalog`
  ADD CONSTRAINT `semen_catalog_ibfk_1` FOREIGN KEY (`bull_id`) REFERENCES `bulls` (`id`) ON DELETE CASCADE;

-- ============================================================
-- MIGRAÇÃO: SISTEMA DE TOUROS COMPLETO
-- Descrição: Expande e cria todas as tabelas necessárias
-- para o módulo completo de gerenciamento de touros
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

-- Expandir Status
ALTER TABLE `bulls` MODIFY COLUMN `status` ENUM('ativo','reserva','em_reproducao','descartado','falecido','vendido','morto','inativo') DEFAULT 'ativo' COMMENT 'Status do touro';

-- Expandir Source
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
-- TABELA: bull_coatings (Coberturas Naturais)
-- ============================================================

SET @bulls_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'bulls');
SET @animals_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'animals');

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
-- TABELA: bull_health_records (Histórico Sanitário)
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
-- TABELA: bull_body_condition (Controle de Peso e Escore)
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
-- TABELA: bull_documents (Documentos e Anexos)
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
-- EXPANDIR TABELA semen_catalog (Qualidade do Sêmen)
-- ============================================================

SET @tablename = 'semen_catalog';

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'straw_code');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `semen_catalog` ADD COLUMN `straw_code` VARCHAR(50) DEFAULT NULL COMMENT ''Código da palheta'' AFTER `batch_number`', 
    'SELECT ''Campo straw_code já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'collection_date');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `semen_catalog` ADD COLUMN `collection_date` DATE DEFAULT NULL COMMENT ''Data de coleta'' AFTER `production_date`', 
    'SELECT ''Campo collection_date já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'motility');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `semen_catalog` ADD COLUMN `motility` DECIMAL(5,2) DEFAULT NULL COMMENT ''Motilidade (%)'' AFTER `quality_grade`', 
    'SELECT ''Campo motility já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'volume');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `semen_catalog` ADD COLUMN `volume` DECIMAL(6,2) DEFAULT NULL COMMENT ''Volume (ml)'' AFTER `motility`', 
    'SELECT ''Campo volume já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'concentration');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `semen_catalog` ADD COLUMN `concentration` DECIMAL(10,2) DEFAULT NULL COMMENT ''Concentração (milhões/ml)'' AFTER `volume`', 
    'SELECT ''Campo concentration já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'destination');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `semen_catalog` ADD COLUMN `destination` VARCHAR(255) DEFAULT NULL COMMENT ''Destino de uso'' AFTER `storage_location`', 
    'SELECT ''Campo destination já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'alert_sent');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `semen_catalog` ADD COLUMN `alert_sent` TINYINT(1) DEFAULT 0 COMMENT ''Alerta de validade enviado'' AFTER `expiry_date`', 
    'SELECT ''Campo alert_sent já existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- TABELA: semen_movements (Movimentação de Sêmen)
-- ============================================================

-- Verificar se tabelas referenciadas existem
SET @semen_catalog_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'semen_catalog');
SET @animals_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'animals');
SET @inseminations_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'inseminations');

-- Criar tabela sem foreign keys primeiro
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

-- Adicionar foreign key para semen_catalog
-- Verificar se a constraint já existe e se a tabela referenciada existe
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'semen_movements' 
    AND CONSTRAINT_NAME = 'fk_semen_movements_semen_id');
SET @table_check = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'semen_catalog');
SET @col_check = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'semen_catalog' AND COLUMN_NAME = 'id' AND DATA_TYPE = 'int');

SET @sql = IF(@fk_exists = 0 AND @table_check > 0 AND @col_check > 0, 
    'ALTER TABLE `semen_movements` ADD CONSTRAINT `fk_semen_movements_semen_id` FOREIGN KEY (`semen_id`) REFERENCES `semen_catalog`(`id`) ON DELETE CASCADE',
    'SELECT ''Foreign key semen_id já existe, tabela não existe ou coluna incompatível''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar foreign key para animals (se animal_id for usado)
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'semen_movements' 
    AND CONSTRAINT_NAME = 'fk_semen_movements_animal_id');
SET @sql = IF(@fk_exists = 0 AND @animals_exists > 0, 
    'ALTER TABLE `semen_movements` ADD CONSTRAINT `fk_semen_movements_animal_id` FOREIGN KEY (`animal_id`) REFERENCES `animals`(`id`) ON DELETE SET NULL',
    'SELECT ''Foreign key animal_id já existe ou tabela animals não existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar foreign key para inseminations
-- Primeiro verificar se a tabela inseminations tem PRIMARY KEY
SET @pk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'inseminations' 
    AND CONSTRAINT_TYPE = 'PRIMARY KEY');

-- Se não tiver PRIMARY KEY, corrigir IDs duplicados/zerados
-- Obter o máximo ID existente
SET @max_id = (SELECT COALESCE(MAX(id), 0) FROM inseminations WHERE id > 0);
SET @zero_count = (SELECT COUNT(*) FROM inseminations WHERE id = 0);

-- Se houver IDs 0, atualizar para novos valores únicos usando variável de usuário
-- Nota: Isso só funciona se não houver PRIMARY KEY ainda
-- Primeiro, definir a variável de usuário
SET @row_num = IF(@pk_exists = 0 AND @inseminations_exists > 0 AND @zero_count > 0, @max_id, 0);

-- Depois, atualizar os registros com id = 0
SET @sql = IF(@pk_exists = 0 AND @inseminations_exists > 0 AND @zero_count > 0,
    'UPDATE `inseminations` SET `id` = (@row_num := @row_num + 1) WHERE `id` = 0 OR `id` IS NULL ORDER BY `created_at`',
    'SELECT ''PRIMARY KEY já existe, sem IDs 0 ou tabela não existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agora adicionar PRIMARY KEY se não existir
SET @sql = IF(@pk_exists = 0 AND @inseminations_exists > 0, 
    'ALTER TABLE `inseminations` ADD PRIMARY KEY (`id`)',
    'SELECT ''PRIMARY KEY já existe ou tabela inseminations não existe''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar novamente após possível criação
SET @pk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'inseminations' 
    AND CONSTRAINT_TYPE = 'PRIMARY KEY');

-- Agora criar a foreign key
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'semen_movements' 
    AND CONSTRAINT_NAME = 'fk_semen_movements_insemination_id');
SET @sql = IF(@fk_exists = 0 AND @inseminations_exists > 0 AND @pk_exists > 0, 
    'ALTER TABLE `semen_movements` ADD CONSTRAINT `fk_semen_movements_insemination_id` FOREIGN KEY (`insemination_id`) REFERENCES `inseminations`(`id`) ON DELETE SET NULL',
    'SELECT ''Foreign key insemination_id já existe, tabela não existe ou PRIMARY KEY não encontrada''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- TABELA: bull_offspring (Rastreamento de Descendentes)
-- ============================================================

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
SET @pk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'inseminations' 
    AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @sql = IF(@fk_exists = 0 AND @inseminations_exists > 0 AND @pk_exists > 0, 
    'ALTER TABLE `bull_offspring` ADD CONSTRAINT `fk_bull_offspring_insemination_id` FOREIGN KEY (`insemination_id`) REFERENCES `inseminations`(`id`) ON DELETE SET NULL',
    'SELECT ''Foreign key insemination_id já existe, tabela não existe ou PRIMARY KEY não encontrada''');
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
-- VIEWS PARA RELATÓRIOS E ANÁLISES
-- ============================================================

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
    COUNT(DISTINCT i.id) AS total_inseminations,
    COUNT(DISTINCT CASE WHEN i.pregnancy_result = 'prenha' THEN i.id END) AS successful_inseminations,
    COUNT(DISTINCT CASE WHEN i.pregnancy_result = 'vazia' THEN i.id END) AS failed_inseminations,
    CASE 
        WHEN COUNT(DISTINCT i.id) > 0 
        THEN ROUND((COUNT(DISTINCT CASE WHEN i.pregnancy_result = 'prenha' THEN i.id END) / COUNT(DISTINCT i.id)) * 100, 2)
        ELSE 0 
    END AS pregnancy_rate_ia,
    COUNT(DISTINCT c.id) AS total_coatings,
    COUNT(DISTINCT CASE WHEN c.result = 'prenhez' THEN c.id END) AS successful_coatings,
    CASE 
        WHEN COUNT(DISTINCT c.id) > 0 
        THEN ROUND((COUNT(DISTINCT CASE WHEN c.result = 'prenhez' THEN c.id END) / COUNT(DISTINCT c.id)) * 100, 2)
        ELSE 0 
    END AS pregnancy_rate_natural,
    (COUNT(DISTINCT i.id) + COUNT(DISTINCT c.id)) AS total_services,
    (COUNT(DISTINCT CASE WHEN i.pregnancy_result = 'prenha' THEN i.id END) + 
     COUNT(DISTINCT CASE WHEN c.result = 'prenhez' THEN c.id END)) AS total_successful,
    COUNT(DISTINCT o.offspring_id) AS total_offspring,
    COALESCE(SUM(s.straws_available), 0) AS semen_straws_available,
    COALESCE(SUM(s.straws_used), 0) AS semen_straws_used,
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
-- TRIGGERS PARA AUTOMAÇÃO
-- ============================================================

DROP TRIGGER IF EXISTS `tr_add_offspring_on_birth`;

DELIMITER $$
CREATE TRIGGER `tr_add_offspring_on_birth`
AFTER INSERT ON `births`
FOR EACH ROW
BEGIN
    -- Se tiver pregnancy_id, buscar insemination_id através de pregnancy_controls
    IF NEW.pregnancy_id IS NOT NULL THEN
        INSERT INTO bull_offspring (bull_id, offspring_id, offspring_type, insemination_id, birth_date, farm_id)
        SELECT 
            i.bull_id,
            NEW.animal_id,
            'inseminacao',
            pc.insemination_id,
            NEW.birth_date,
            NEW.farm_id
        FROM pregnancy_controls pc
        INNER JOIN inseminations i ON i.id = pc.insemination_id
        WHERE pc.id = NEW.pregnancy_id
        AND i.bull_id IS NOT NULL
        AND pc.insemination_id IS NOT NULL
        ON DUPLICATE KEY UPDATE birth_date = NEW.birth_date;
    END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `tr_update_bull_weight_score`;

DELIMITER $$
CREATE TRIGGER `tr_update_bull_weight_score`
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

DROP TRIGGER IF EXISTS `tr_update_semen_stock_on_use`;

DELIMITER $$
CREATE TRIGGER `tr_update_semen_stock_on_use`
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
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ============================================================

CREATE INDEX IF NOT EXISTS `idx_bulls_search` ON `bulls`(`bull_number`, `name`, `breed`, `status`, `farm_id`);
CREATE INDEX IF NOT EXISTS `idx_bulls_active_breeding` ON `bulls`(`is_active`, `is_breeding_active`, `status`, `farm_id`);
CREATE INDEX IF NOT EXISTS `idx_semen_expiry` ON `semen_catalog`(`expiry_date`, `farm_id`, `straws_available`);
CREATE INDEX IF NOT EXISTS `idx_coatings_bull_date` ON `bull_coatings`(`bull_id`, `coating_date`, `result`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
