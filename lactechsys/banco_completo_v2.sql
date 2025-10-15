-- =====================================================
-- BANCO DE DADOS LACTECH - VERSÃO 2.0
-- Sistema completo e otimizado
-- Compatível: Local e Produção
-- =====================================================

-- Configurações iniciais
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- ESTRUTURA DO BANCO
-- =====================================================

-- Tabela de fazendas
CREATE TABLE IF NOT EXISTS `farms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('proprietario','gerente','funcionario','veterinario') NOT NULL DEFAULT 'funcionario',
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `cpf` varchar(14) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
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

-- Tabela de animais
CREATE TABLE IF NOT EXISTS `animals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_number` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `breed` varchar(100) NOT NULL,
  `gender` enum('femea','macho') NOT NULL,
  `birth_date` date NOT NULL,
  `father_id` int(11) DEFAULT NULL,
  `mother_id` int(11) DEFAULT NULL,
  `status` enum('Lactante','Seco','Novilha','Vaca','Bezerra','Bezerro') NOT NULL DEFAULT 'Bezerra',
  `health_status` enum('saudavel','doente','tratamento','quarentena') NOT NULL DEFAULT 'saudavel',
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
  CONSTRAINT `animals_ibfk_1` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `animals_ibfk_2` FOREIGN KEY (`father_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `animals_ibfk_3` FOREIGN KEY (`mother_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de produção de leite
CREATE TABLE IF NOT EXISTS `milk_production` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `production_date` date NOT NULL,
  `shift` enum('manha','tarde','noite') NOT NULL,
  `volume` decimal(8,2) NOT NULL,
  `quality_score` decimal(3,1) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
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

-- Tabela de saúde dos animais
CREATE TABLE IF NOT EXISTS `health_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `record_type` enum('Medicamento','Vacinação','Vermifugação','Suplementação','Cirurgia','Outros') NOT NULL,
  `description` text NOT NULL,
  `medication` varchar(255) DEFAULT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `next_date` date DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
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

-- Tabela de reprodução
CREATE TABLE IF NOT EXISTS `reproduction_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `event_date` date NOT NULL,
  `event_type` enum('inseminacao','gestacao','parto','aborto','outros') NOT NULL,
  `father_id` int(11) DEFAULT NULL,
  `insemination_type` varchar(100) DEFAULT NULL,
  `pregnancy_date` date DEFAULT NULL,
  `expected_birth` date DEFAULT NULL,
  `actual_birth` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `animal_id` (`animal_id`),
  KEY `event_date` (`event_date`),
  KEY `event_type` (`event_type`),
  KEY `father_id` (`father_id`),
  KEY `recorded_by` (`recorded_by`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `reproduction_records_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reproduction_records_ibfk_2` FOREIGN KEY (`father_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reproduction_records_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reproduction_records_ibfk_4` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de registros financeiros
CREATE TABLE IF NOT EXISTS `financial_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_date` date NOT NULL,
  `type` enum('receita','despesa') NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('dinheiro','cartao','transferencia','cheque','pix') DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `farm_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `record_date` (`record_date`),
  KEY `type` (`type`),
  KEY `category` (`category`),
  KEY `created_by` (`created_by`),
  KEY `farm_id` (`farm_id`),
  CONSTRAINT `financial_records_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `financial_records_ibfk_2` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Inserir fazenda padrão
INSERT IGNORE INTO `farms` (`id`, `name`, `location`, `owner_name`) VALUES
(1, 'Lagoa do Mato', 'MG', 'Proprietário');

-- Inserir usuários padrão (senha: 123456)
INSERT IGNORE INTO `users` (`id`, `email`, `password`, `name`, `role`, `farm_id`, `is_active`) VALUES
(1, 'Fernando@lactech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fernando Silva', 'proprietario', 1, 1),
(2, 'Junior@lactech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Junior Silva', 'gerente', 1, 1);

-- =====================================================
-- VIEWS PARA RELATÓRIOS
-- =====================================================

-- View de produção diária
CREATE OR REPLACE VIEW `v_daily_production` AS
SELECT 
    mp.production_date,
    mp.shift,
    COUNT(DISTINCT mp.animal_id) as total_animals,
    SUM(mp.volume) as total_volume,
    AVG(mp.volume) as avg_volume,
    AVG(mp.quality_score) as avg_quality
FROM milk_production mp
WHERE mp.farm_id = 1
GROUP BY mp.production_date, mp.shift
ORDER BY mp.production_date DESC, mp.shift;

-- View de animais ativos
CREATE OR REPLACE VIEW `v_active_animals` AS
SELECT 
    a.*,
    f.name as father_name,
    m.name as mother_name,
    DATEDIFF(CURDATE(), a.birth_date) as age_days,
    FLOOR(DATEDIFF(CURDATE(), a.birth_date) / 365) as age_years
FROM animals a
LEFT JOIN animals f ON a.father_id = f.id
LEFT JOIN animals m ON a.mother_id = m.id
WHERE a.is_active = 1 AND a.farm_id = 1
ORDER BY a.animal_number;

-- View de usuários ativos
CREATE OR REPLACE VIEW `v_active_users` AS
SELECT 
    u.*,
    f.name as farm_name,
    DATEDIFF(CURDATE(), u.created_at) as days_since_creation,
    DATEDIFF(CURDATE(), u.last_login) as days_since_login
FROM users u
LEFT JOIN farms f ON u.farm_id = f.id
WHERE u.is_active = 1
ORDER BY u.role, u.name;

-- =====================================================
-- TRIGGERS PARA AUDITORIA
-- =====================================================

DELIMITER //

-- Trigger para atualizar timestamp de animais
CREATE TRIGGER IF NOT EXISTS `tr_animals_updated`
    BEFORE UPDATE ON `animals`
    FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

-- Trigger para atualizar timestamp de usuários
CREATE TRIGGER IF NOT EXISTS `tr_users_updated`
    BEFORE UPDATE ON `users`
    FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

-- Trigger para validar idade de animais
CREATE TRIGGER IF NOT EXISTS `tr_validate_animal_age`
    BEFORE INSERT ON `animals`
    FOR EACH ROW
BEGIN
    IF NEW.birth_date > CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data de nascimento não pode ser no futuro';
    END IF;
END//

DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================

-- Índices compostos para consultas frequentes
CREATE INDEX IF NOT EXISTS `idx_production_date_shift` ON `milk_production` (`production_date`, `shift`);
CREATE INDEX IF NOT EXISTS `idx_animal_production` ON `milk_production` (`animal_id`, `production_date`);
CREATE INDEX IF NOT EXISTS `idx_health_animal_date` ON `health_records` (`animal_id`, `record_date`);
CREATE INDEX IF NOT EXISTS `idx_reproduction_animal_date` ON `reproduction_records` (`animal_id`, `event_date`);
CREATE INDEX IF NOT EXISTS `idx_financial_date_type` ON `financial_records` (`record_date`, `type`);

-- =====================================================
-- FINALIZAÇÃO
-- =====================================================

COMMIT;

-- =====================================================
-- VERIFICAÇÃO FINAL
-- =====================================================

-- Verificar se as tabelas foram criadas
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

-- Verificar usuários criados
SELECT 
    id,
    email,
    name,
    role,
    SUBSTRING(password, 1, 10) as password_start,
    LENGTH(password) as password_length,
    is_active
FROM users
ORDER BY role DESC;

-- =====================================================
-- CREDENCIAIS DE ACESSO
-- =====================================================
-- 
-- PROPRIETÁRIO:
-- Email: Fernando@lactech.com
-- Senha: 123456
-- 
-- GERENTE:
-- Email: Junior@lactech.com
-- Senha: 123456
-- 
-- =====================================================
