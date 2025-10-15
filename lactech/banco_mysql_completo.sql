-- =====================================================
-- BANCO DE DADOS LACTECH - LAGOA DO MATO
-- Sistema Completo com Funcionalidades Avançadas
-- 100% Compatível com o Sistema PHP MySQL
-- Data: 11/10/2024
-- Versão: 2.0 - Completa
-- =====================================================

-- Criar banco (já criado na Hostinger como u311882628_lactech_lgmato)
-- CREATE DATABASE IF NOT EXISTS u311882628_lactech_lgmato 
-- CHARACTER SET utf8mb4 
-- COLLATE utf8mb4_unicode_ci;

USE u311882628_lactech_lgmato;

-- =====================================================
-- TABELAS PRINCIPAIS DO SISTEMA
-- =====================================================

-- 1. TABELA FARMS (Fazendas)
CREATE TABLE IF NOT EXISTS farms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL DEFAULT 'Lagoa Do Mato',
    location VARCHAR(255),
    cnpj VARCHAR(18),
    owner_name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABELA USERS (Usuários)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('proprietario', 'gerente', 'funcionario', 'veterinario') NOT NULL DEFAULT 'funcionario',
    cpf VARCHAR(14),
    phone VARCHAR(20),
    profile_photo_url TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    INDEX idx_email (email),
    INDEX idx_farm_id (farm_id),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABELA VOLUME_RECORDS (Coleta de Leite)
CREATE TABLE IF NOT EXISTS volume_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NULL,
    producer_id INT NULL,
    volume DECIMAL(10,2) NOT NULL,
    collection_date DATE NOT NULL,
    period ENUM('manha', 'tarde', 'noite', 'madrugada') NOT NULL DEFAULT 'manha',
    temperature DECIMAL(4,1),
    recorded_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_farm_date (farm_id, collection_date),
    INDEX idx_recorded_by (recorded_by),
    INDEX idx_animal_id (animal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABELA QUALITY_TESTS (Testes de Qualidade)
CREATE TABLE IF NOT EXISTS quality_tests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    producer_id INT NULL,
    test_date DATE NOT NULL,
    fat_percentage DECIMAL(5,2),
    protein_percentage DECIMAL(5,2),
    lactose_percentage DECIMAL(5,2),
    scc INT,
    cbt INT,
    temperature DECIMAL(4,1),
    ph DECIMAL(3,2),
    tested_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (tested_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_farm_date (farm_id, test_date),
    INDEX idx_tested_by (tested_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABELA FINANCIAL_RECORDS (Registros Financeiros)
CREATE TABLE IF NOT EXISTS financial_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    type ENUM('income', 'expense') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    due_date DATE,
    payment_date DATE,
    status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_farm_type (farm_id, type),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABELA SECONDARY_ACCOUNTS (Contas Secundárias)
CREATE TABLE IF NOT EXISTS secondary_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    primary_user_id INT NOT NULL,
    secondary_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_secondary (primary_user_id, secondary_user_id),
    FOREIGN KEY (primary_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (secondary_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABELA PASSWORD_REQUESTS (Solicitações de Senha)
CREATE TABLE IF NOT EXISTS password_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. TABELA NOTIFICATIONS (Notificações)
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    user_id INT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO DE GESTÃO DE ANIMAIS
-- =====================================================

-- 9. TABELA ANIMALS (Animais do Rebanho)
CREATE TABLE IF NOT EXISTS animals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_number VARCHAR(50) NOT NULL,
    animal_name VARCHAR(100),
    breed VARCHAR(100),
    birth_date DATE,
    sex ENUM('Macho', 'Fêmea') NOT NULL,
    sire_number VARCHAR(50),
    dam_number VARCHAR(50),
    weight DECIMAL(6,2),
    reproductive_status ENUM('Vazia', 'Prenha', 'Lactante', 'Seca') DEFAULT 'Vazia',
    health_status ENUM('Saudável', 'Em Tratamento', 'Quarentena') DEFAULT 'Saudável',
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_animal_number (farm_id, animal_number),
    INDEX idx_farm_id (farm_id),
    INDEX idx_reproductive_status (reproductive_status),
    INDEX idx_health_status (health_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. TABELA BULLS (Touros/Reprodutores)
CREATE TABLE IF NOT EXISTS bulls (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    bull_number VARCHAR(50) NOT NULL,
    bull_name VARCHAR(100),
    breed VARCHAR(100),
    semen_type ENUM('Convencional', 'Sexado') DEFAULT 'Convencional',
    genetic_evaluation TEXT,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bull_number (farm_id, bull_number),
    INDEX idx_farm_id (farm_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. TABELA INSEMINATIONS (Inseminações)
CREATE TABLE IF NOT EXISTS inseminations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NOT NULL,
    bull_id INT NOT NULL,
    insemination_date DATE NOT NULL,
    technician_name VARCHAR(100),
    success BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (bull_id) REFERENCES bulls(id) ON DELETE CASCADE,
    INDEX idx_farm_animal (farm_id, animal_id),
    INDEX idx_insemination_date (insemination_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. TABELA LACTATIONS (Lactações)
CREATE TABLE IF NOT EXISTS lactations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    animal_id INT NOT NULL,
    lactation_number INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    peak_production DECIMAL(8,2),
    average_production DECIMAL(8,2),
    total_production DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    INDEX idx_animal_id (animal_id),
    INDEX idx_start_date (start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO DE GESTÃO SANITÁRIA
-- =====================================================

-- 13. TABELA MEDICATIONS (Medicamentos)
CREATE TABLE IF NOT EXISTS medications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    name VARCHAR(200) NOT NULL,
    type ENUM('Vacina', 'Antibiótico', 'Anti-inflamatório', 'Vermífugo', 'Vitamina', 'Outro') NOT NULL,
    manufacturer VARCHAR(200),
    batch_number VARCHAR(100),
    expiry_date DATE,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit VARCHAR(50),
    min_quantity DECIMAL(10,2) DEFAULT 0,
    withdrawal_period_milk INT DEFAULT 0,
    withdrawal_period_meat INT DEFAULT 0,
    storage_location VARCHAR(200),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    INDEX idx_farm_id (farm_id),
    INDEX idx_type (type),
    INDEX idx_expiry_date (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. TABELA MEDICATION_APPLICATIONS (Aplicações de Medicamentos)
CREATE TABLE IF NOT EXISTS medication_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NOT NULL,
    medication_id INT NOT NULL,
    application_date DATE NOT NULL,
    dosage DECIMAL(10,2) NOT NULL,
    dosage_unit VARCHAR(50),
    applied_by INT NOT NULL,
    withdrawal_end_date_milk DATE,
    withdrawal_end_date_meat DATE,
    reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE,
    FOREIGN KEY (applied_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_farm_animal (farm_id, animal_id),
    INDEX idx_application_date (application_date),
    INDEX idx_withdrawal_milk (withdrawal_end_date_milk),
    INDEX idx_withdrawal_meat (withdrawal_end_date_meat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. TABELA VACCINATION_PROGRAMS (Programas de Vacinação)
CREATE TABLE IF NOT EXISTS vaccination_programs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    name VARCHAR(200) NOT NULL,
    medication_id INT NOT NULL,
    description TEXT,
    schedule_type ENUM('Anual', 'Semestral', 'Trimestral', 'Mensal', 'Único') NOT NULL,
    target_animals VARCHAR(200),
    next_application_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE,
    INDEX idx_farm_id (farm_id),
    INDEX idx_next_date (next_application_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. TABELA HEALTH_ALERTS (Alertas Sanitários)
CREATE TABLE IF NOT EXISTS health_alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NULL,
    alert_type ENUM('vacinacao', 'carencia', 'tratamento', 'estoque_baixo', 'medicamento_vencido') NOT NULL,
    alert_level ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    due_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    resolved_at TIMESTAMP NULL,
    resolved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_farm_active (farm_id, is_active),
    INDEX idx_alert_type (alert_type),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MÓDULO DE REPRODUÇÃO AVANÇADO
-- =====================================================

-- 17. TABELA PREGNANCY_CONTROLS (Controle de Prenhez)
CREATE TABLE IF NOT EXISTS pregnancy_controls (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NOT NULL,
    insemination_id INT NULL,
    pregnancy_confirmation_date DATE NOT NULL,
    expected_birth_date DATE NOT NULL,
    pregnancy_stage VARCHAR(50) DEFAULT 'Normal',
    confirmed_by VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (insemination_id) REFERENCES inseminations(id) ON DELETE SET NULL,
    INDEX idx_farm_animal (farm_id, animal_id),
    INDEX idx_expected_date (expected_birth_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. TABELA BIRTHS (Partos)
CREATE TABLE IF NOT EXISTS births (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NOT NULL,
    pregnancy_control_id INT NULL,
    birth_date DATE NOT NULL,
    birth_type ENUM('Normal', 'Cesariana', 'Assistido') DEFAULT 'Normal',
    calf_sex ENUM('Macho', 'Fêmea'),
    calf_weight DECIMAL(6,2),
    calf_number VARCHAR(50),
    sire_id INT NULL,
    complications TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (pregnancy_control_id) REFERENCES pregnancy_controls(id) ON DELETE SET NULL,
    FOREIGN KEY (sire_id) REFERENCES bulls(id) ON DELETE SET NULL,
    INDEX idx_farm_animal (farm_id, animal_id),
    INDEX idx_birth_date (birth_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. TABELA HEAT_CYCLES (Ciclos de Cio)
CREATE TABLE IF NOT EXISTS heat_cycles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NOT NULL,
    heat_date DATE NOT NULL,
    heat_duration_hours INT,
    intensity ENUM('Baixo', 'Médio', 'Alto') DEFAULT 'Médio',
    observed_by VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    INDEX idx_farm_animal (farm_id, animal_id),
    INDEX idx_heat_date (heat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. TABELA MATERNITY_ALERTS (Alertas de Maternidade)
CREATE TABLE IF NOT EXISTS maternity_alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NOT NULL,
    pregnancy_control_id INT NULL,
    alert_type ENUM('pré_parto', 'parto_iminente', 'pós_parto', 'cuidados_especiais') NOT NULL,
    alert_level ENUM('low', 'medium', 'high') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    expected_birth_date DATE,
    due_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (pregnancy_control_id) REFERENCES pregnancy_controls(id) ON DELETE SET NULL,
    INDEX idx_farm_active (farm_id, is_active),
    INDEX idx_alert_type (alert_type),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VIEWS PARA RELATÓRIOS E CONSULTAS
-- =====================================================

-- View: Animais com Informações Completas
CREATE OR REPLACE VIEW v_animals_complete AS
SELECT 
    a.*,
    TIMESTAMPDIFF(MONTH, a.birth_date, CURDATE()) as age_months,
    TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) as age_years,
    (SELECT COUNT(*) FROM inseminations i WHERE i.animal_id = a.id) as total_inseminations,
    (SELECT COUNT(*) FROM births b WHERE b.animal_id = a.id) as total_births,
    (SELECT AVG(v.volume) FROM volume_records v WHERE v.animal_id = a.id AND v.collection_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as avg_production_30d
FROM animals a
WHERE a.is_active = TRUE;

-- View: Prenhezes Ativas com DPP e Estágio
CREATE OR REPLACE VIEW v_active_pregnancies AS
SELECT 
    pc.id,
    pc.farm_id,
    pc.animal_id,
    pc.insemination_id,
    pc.pregnancy_confirmation_date,
    pc.expected_birth_date,
    pc.confirmed_by,
    pc.notes,
    pc.created_at,
    pc.updated_at,
    a.animal_number,
    a.animal_name,
    a.breed,
    DATEDIFF(pc.expected_birth_date, CURDATE()) as days_until_birth,
    CASE 
        WHEN DATEDIFF(pc.expected_birth_date, CURDATE()) <= 0 THEN 'Vencido'
        WHEN DATEDIFF(pc.expected_birth_date, CURDATE()) <= 7 THEN 'Parto Iminente'
        WHEN DATEDIFF(pc.expected_birth_date, CURDATE()) <= 21 THEN 'Pré-parto'
        ELSE 'Normal'
    END as pregnancy_stage
FROM pregnancy_controls pc
JOIN animals a ON pc.animal_id = a.id
WHERE NOT EXISTS (
    SELECT 1 FROM births b 
    WHERE b.pregnancy_control_id = pc.id
);

-- View: Medicamentos em Estoque Baixo
CREATE OR REPLACE VIEW v_low_stock_medications AS
SELECT 
    m.*,
    CASE 
        WHEN m.quantity = 0 THEN 'Sem Estoque'
        WHEN m.quantity <= m.min_quantity * 0.5 THEN 'Crítico'
        WHEN m.quantity <= m.min_quantity THEN 'Baixo'
        ELSE 'Normal'
    END as stock_status
FROM medications m
WHERE m.quantity <= m.min_quantity
ORDER BY m.quantity ASC;

-- =====================================================
-- TRIGGERS PARA AUTOMAÇÃO
-- =====================================================

DELIMITER //

-- Trigger: Atualizar status reprodutivo ao confirmar prenhez
CREATE TRIGGER tr_update_reproductive_status_pregnancy
AFTER INSERT ON pregnancy_controls
FOR EACH ROW
BEGIN
    UPDATE animals 
    SET reproductive_status = 'Prenha'
    WHERE id = NEW.animal_id;
END//

-- Trigger: Atualizar status reprodutivo após parto
CREATE TRIGGER tr_update_reproductive_status_birth
AFTER INSERT ON births
FOR EACH ROW
BEGIN
    UPDATE animals 
    SET reproductive_status = 'Lactante'
    WHERE id = NEW.animal_id;
END//

-- Trigger: Criar alertas de maternidade automaticamente
CREATE TRIGGER tr_create_maternity_alerts
AFTER INSERT ON pregnancy_controls
FOR EACH ROW
BEGIN
    -- Alerta de pré-parto (21 dias antes)
    INSERT INTO maternity_alerts (farm_id, animal_id, pregnancy_control_id, alert_type, alert_level, title, message, expected_birth_date, due_date, is_active)
    VALUES (
        NEW.farm_id,
        NEW.animal_id,
        NEW.id,
        'pré_parto',
        'medium',
        'Preparação para parto',
        'Animal entrando no período de pré-parto. Preparar local de parto e intensificar monitoramento.',
        NEW.expected_birth_date,
        DATE_SUB(NEW.expected_birth_date, INTERVAL 21 DAY),
        TRUE
    );
    
    -- Alerta de parto iminente (7 dias antes)
    INSERT INTO maternity_alerts (farm_id, animal_id, pregnancy_control_id, alert_type, alert_level, title, message, expected_birth_date, due_date, is_active)
    VALUES (
        NEW.farm_id,
        NEW.animal_id,
        NEW.id,
        'parto_iminente',
        'high',
        'Parto iminente',
        'Animal com parto previsto para os próximos 7 dias. Monitoramento intensivo necessário.',
        NEW.expected_birth_date,
        DATE_SUB(NEW.expected_birth_date, INTERVAL 7 DAY),
        TRUE
    );
END//

-- Trigger: Reduzir estoque de medicamento após aplicação
CREATE TRIGGER tr_reduce_medication_stock
AFTER INSERT ON medication_applications
FOR EACH ROW
BEGIN
    UPDATE medications 
    SET quantity = quantity - NEW.dosage
    WHERE id = NEW.medication_id;
    
    -- Criar alerta se estoque ficar baixo
    IF (SELECT quantity FROM medications WHERE id = NEW.medication_id) <= (SELECT min_quantity FROM medications WHERE id = NEW.medication_id) THEN
        INSERT INTO health_alerts (farm_id, alert_type, alert_level, title, message, is_active)
        SELECT 
            NEW.farm_id,
            'estoque_baixo',
            'medium',
            CONCAT('Estoque baixo: ', m.name),
            CONCAT('O medicamento ', m.name, ' está com estoque baixo. Quantidade atual: ', m.quantity, ' ', m.unit),
            TRUE
        FROM medications m
        WHERE m.id = NEW.medication_id;
    END IF;
END//

-- Trigger: Criar alerta de período de carência
CREATE TRIGGER tr_create_withdrawal_alert
AFTER INSERT ON medication_applications
FOR EACH ROW
BEGIN
    IF NEW.withdrawal_end_date_milk IS NOT NULL THEN
        INSERT INTO health_alerts (farm_id, animal_id, alert_type, alert_level, title, message, due_date, is_active)
        VALUES (
            NEW.farm_id,
            NEW.animal_id,
            'carencia',
            'high',
            'Período de carência ativo',
            CONCAT('Animal em período de carência até ', DATE_FORMAT(NEW.withdrawal_end_date_milk, '%d/%m/%Y'), '. Não utilizar leite para consumo.'),
            NEW.withdrawal_end_date_milk,
            TRUE
        );
    END IF;
END//

DELIMITER ;

-- =====================================================
-- DADOS INICIAIS - FAZENDA LAGOA DO MATO
-- =====================================================

-- Inserir fazenda Lagoa Do Mato (ID = 1)
INSERT INTO farms (id, name, location, owner_name, email, phone) VALUES 
(1, 'Lagoa Do Mato', 'Aquiraz - Ceará', 'Fernando', NULL, NULL);

-- =====================================================
-- USUÁRIOS DO SISTEMA - LAGOA DO MATO
-- =====================================================

-- 1. PROPRIETÁRIO - Fernando
INSERT INTO users (id, farm_id, name, email, password, role, cpf, phone, is_active) VALUES 
(1, 1, 'Fernando', 'Fernando@lactech.com', '$2y$10$BHoSYqpCUish3yO3o/0E1uklR7u.ANKUbmxCQsDaOOJHvLkbRKVza', 'proprietario', NULL, NULL, 1);

-- 2. GERENTE - Antonio Junior
INSERT INTO users (id, farm_id, name, email, password, role, cpf, phone, is_active) VALUES 
(2, 1, 'Antonio Junior', 'Junior@lactech.com', '$2y$10$pgJEXHQw862Wzk0tReZvxO2Q4aj4ZmU4RP8fg6qYF54SIeImowyjG', 'gerente', NULL, NULL, 1);

-- =====================================================
-- DADOS DE EXEMPLO (ÚLTIMOS 7 DIAS)
-- =====================================================

-- Coletas de leite (últimos 7 dias)
INSERT INTO volume_records (farm_id, volume, collection_date, period, temperature, recorded_by, notes) VALUES 
-- Hoje
(1, 520.00, CURDATE(), 'manha', 4.5, 2, 'Coleta normal - manhã'),
(1, 480.00, CURDATE(), 'tarde', 4.8, 2, 'Coleta normal - tarde'),
-- Ontem
(1, 510.00, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'manha', 4.3, 2, 'Boa produção'),
(1, 490.00, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'tarde', 4.6, 2, 'Coleta regular'),
-- 2 dias atrás
(1, 505.00, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'manha', 4.4, 2, 'Produção estável'),
(1, 495.00, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'tarde', 4.7, 2, 'Temperatura OK'),
-- 3 dias atrás
(1, 530.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'manha', 4.2, 2, 'Excelente produção'),
(1, 485.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'tarde', 4.5, 2, 'Normal');

-- Testes de qualidade (últimos 7 dias)
INSERT INTO quality_tests (farm_id, test_date, fat_percentage, protein_percentage, lactose_percentage, scc, cbt, temperature, ph, tested_by, notes) VALUES 
(1, CURDATE(), 3.5, 3.2, 4.5, 195000, 48000, 4.5, 6.7, 2, 'Qualidade excelente'),
(1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 3.6, 3.3, 4.6, 180000, 45000, 4.4, 6.8, 2, 'Dentro dos padrões'),
(1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 3.4, 3.1, 4.4, 205000, 52000, 4.6, 6.6, 2, 'Qualidade boa');

-- Registros financeiros (último mês)
INSERT INTO financial_records (farm_id, type, amount, description, category, due_date, payment_date, status, created_by) VALUES 
-- Receitas
(1, 'income', 8500.00, 'Venda de leite - Semana 1', 'Leite', CURDATE(), CURDATE(), 'paid', 1),
(1, 'income', 8200.00, 'Venda de leite - Semana 2', 'Leite', DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'paid', 1),
(1, 'income', 8800.00, 'Venda de leite - Semana 3', 'Leite', DATE_SUB(CURDATE(), INTERVAL 14 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY), 'paid', 1),
-- Despesas pagas
(1, 'expense', 2500.00, 'Ração e suplementos', 'Alimentação', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'paid', 2),
(1, 'expense', 1200.00, 'Medicamentos veterinários', 'Saúde', DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'paid', 2),
(1, 'expense', 3500.00, 'Manutenção de equipamentos', 'Manutenção', DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'paid', 2),
-- Despesas pendentes
(1, 'expense', 1800.00, 'Energia elétrica', 'Utilidades', DATE_ADD(CURDATE(), INTERVAL 5 DAY), NULL, 'pending', 2),
(1, 'expense', 950.00, 'Materiais de limpeza', 'Limpeza', DATE_ADD(CURDATE(), INTERVAL 10 DAY), NULL, 'pending', 2);

-- =====================================================
-- DADOS DE EXEMPLO - ANIMAIS
-- =====================================================

-- Inserir alguns animais de exemplo
INSERT INTO animals (animal_number, animal_name, breed, birth_date, sex, sire_number, dam_number, farm_id, reproductive_status, health_status) VALUES 
('001', 'Bela', 'Holandesa', '2020-03-15', 'Fêmea', 'S001', 'D001', 1, 'Lactante', 'Saudável'),
('002', 'Luna', 'Holandesa', '2019-08-20', 'Fêmea', 'S002', 'D002', 1, 'Prenha', 'Saudável'),
('003', 'Mimosa', 'Holandesa', '2021-01-10', 'Fêmea', 'S003', 'D003', 1, 'Vazia', 'Saudável'),
('004', 'Flor', 'Holandesa', '2020-11-05', 'Fêmea', 'S001', 'D004', 1, 'Prenha', 'Saudável'),
('005', 'Estrela', 'Holandesa', '2019-05-30', 'Fêmea', 'S004', 'D005', 1, 'Prenha', 'Saudável');

-- Inserir touros
INSERT INTO bulls (bull_number, bull_name, breed, semen_type, farm_id) VALUES 
('S001', 'Champion', 'Holandesa', 'Convencional', 1),
('S002', 'Thunder', 'Holandesa', 'Sexado', 1),
('S003', 'King', 'Holandesa', 'Convencional', 1),
('S004', 'Storm', 'Holandesa', 'Sexado', 1);

-- Inserir medicamentos
INSERT INTO medications (farm_id, name, type, manufacturer, quantity, unit, min_quantity, withdrawal_period_milk, withdrawal_period_meat) VALUES 
(1, 'Vacina Raiva', 'Vacina', 'Zoetis', 50, 'doses', 10, 0, 0),
(1, 'Vacina Brucelose', 'Vacina', 'Zoetis', 30, 'doses', 10, 0, 0),
(1, 'Penicilina Benzatina', 'Antibiótico', 'Ourofino', 25, 'frascos', 5, 96, 30),
(1, 'Ivermectina', 'Vermífugo', 'MSD', 15, 'frascos', 5, 0, 35),
(1, 'Complexo B', 'Vitamina', 'Vallée', 40, 'frascos', 10, 0, 0);

-- =====================================================
-- VERIFICAÇÃO FINAL
-- =====================================================

SELECT '✅ BANCO DE DADOS COMPLETO CRIADO COM SUCESSO!' as '══════════════════════════════════════';
SELECT '' as '';
SELECT '📊 INFORMAÇÕES DO SISTEMA' as '══════════════════════════════════════';
SELECT 'lactech_lagoa_mato' as 'Nome do Banco';
SELECT 'Lagoa Do Mato (ID = 1)' as 'Fazenda';
SELECT '20 tabelas + 3 views + triggers' as 'Estrutura Completa';
SELECT 'UTF-8 (utf8mb4_unicode_ci)' as 'Charset';
SELECT '' as ' ';

SELECT '👥 USUÁRIOS CRIADOS' as '══════════════════════════════════════';
SELECT 'Fernando@lactech.com' as 'Email', 'Fernando' as 'Nome', 'Proprietário' as 'Função', 'Fernando123' as 'Senha';
SELECT 'Junior@lactech.com' as 'Email', 'Antonio Junior' as 'Nome', 'Gerente' as 'Função', 'Junior123' as 'Senha';
SELECT '' as '  ';

SELECT '📈 MÓDULOS IMPLEMENTADOS' as '══════════════════════════════════════';
SELECT '✅ Gestão Básica (Volume, Qualidade, Financeiro)' as 'Módulo 1';
SELECT '✅ Gestão de Animais (Rebanho, Inseminação, Genealogia)' as 'Módulo 2';
SELECT '✅ Gestão Sanitária (Medicamentos, Vacinas, Alertas)' as 'Módulo 3';
SELECT '✅ Reprodução Avançada (DPP, Prenhez, Alertas Maternidade)' as 'Módulo 4';
SELECT '✅ Sistema Analítico (Indicadores, Performance)' as 'Módulo 5';
SELECT '' as '   ';

SELECT '🚀 SISTEMA PRONTO PARA USO!' as '══════════════════════════════════════';
SELECT 'Acesse: http://localhost/seu-projeto/lactechsys/login.php' as 'URL de Acesso';
SELECT 'Login com os emails acima e suas respectivas senhas' as 'Como Entrar';
