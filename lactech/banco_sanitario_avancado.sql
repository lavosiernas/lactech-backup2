-- =====================================================
-- BANCO DE DADOS PARA GESTÃO SANITÁRIA PROATIVA
-- Sistema Lactech - Fazenda Lagoa Do Mato
-- =====================================================

-- Tabela de Medicamentos/Vacinas
CREATE TABLE IF NOT EXISTS medications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    name VARCHAR(100) NOT NULL,
    type ENUM('vacina', 'antibiótico', 'vermífugo', 'vitamina', 'suplemento', 'outro') NOT NULL,
    category ENUM('obrigatória', 'recomendada', 'opcional') DEFAULT 'recomendada',
    
    -- Dados do Medicamento
    manufacturer VARCHAR(100), -- Fabricante
    batch_number VARCHAR(50), -- Número do lote
    expiration_date DATE, -- Data de validade
    concentration VARCHAR(50), -- Concentração (ex: 10ml, 500mg)
    
    -- Dados de Aplicação
    application_route ENUM('intramuscular', 'subcutânea', 'oral', 'tópica', 'intravenosa') NOT NULL,
    dosage_per_kg DECIMAL(5,2), -- Dosagem por kg de peso
    min_weight DECIMAL(5,2), -- Peso mínimo para aplicação
    max_weight DECIMAL(5,2), -- Peso máximo para aplicação
    
    -- Controle de Carência
    milk_withdrawal_period INT DEFAULT 0, -- Período de carência para leite (dias)
    meat_withdrawal_period INT DEFAULT 0, -- Período de carência para carne (dias)
    
    -- Dados Financeiros
    unit_cost DECIMAL(10,2) DEFAULT 0, -- Custo por unidade
    stock_quantity INT DEFAULT 0, -- Quantidade em estoque
    min_stock_level INT DEFAULT 5, -- Nível mínimo de estoque
    
    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    
    -- Índices
    INDEX idx_farm_id (farm_id),
    INDEX idx_type (type),
    INDEX idx_category (category),
    INDEX idx_expiration_date (expiration_date),
    
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de Aplicações de Medicamentos
CREATE TABLE IF NOT EXISTS medication_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NOT NULL,
    medication_id INT NOT NULL,
    
    -- Dados da Aplicação
    application_date DATE NOT NULL,
    application_time TIME,
    dosage_used DECIMAL(5,2), -- Dosagem aplicada
    weight_at_application DECIMAL(6,2), -- Peso do animal na aplicação
    veterinarian_name VARCHAR(100), -- Nome do veterinário
    technician_name VARCHAR(100), -- Nome do técnico
    
    -- Dados de Controle
    withdrawal_start_date DATE NOT NULL, -- Data de início da carência
    milk_withdrawal_end_date DATE, -- Data de fim da carência para leite
    meat_withdrawal_end_date DATE, -- Data de fim da carência para carne
    is_milk_blocked BOOLEAN DEFAULT TRUE, -- Leite bloqueado?
    is_meat_blocked BOOLEAN DEFAULT TRUE, -- Carne bloqueada?
    
    -- Observações
    notes TEXT,
    batch_number VARCHAR(50), -- Lote usado
    
    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    
    -- Índices
    INDEX idx_farm_id (farm_id),
    INDEX idx_animal_id (animal_id),
    INDEX idx_medication_id (medication_id),
    INDEX idx_application_date (application_date),
    INDEX idx_milk_withdrawal_end_date (milk_withdrawal_end_date),
    INDEX idx_meat_withdrawal_end_date (meat_withdrawal_end_date),
    INDEX idx_is_milk_blocked (is_milk_blocked),
    INDEX idx_is_meat_blocked (is_meat_blocked),
    
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de Programas de Vacinação
CREATE TABLE IF NOT EXISTS vaccination_programs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    
    -- Dados do Programa
    target_group ENUM('bezerros', 'novilhas', 'vacas', 'todos') NOT NULL,
    min_age_days INT, -- Idade mínima em dias
    max_age_days INT, -- Idade máxima em dias
    
    -- Frequência
    frequency_type ENUM('única', 'anual', 'semestral', 'trimestral', 'mensal', 'personalizada') DEFAULT 'anual',
    frequency_days INT, -- Dias entre aplicações (para personalizada)
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    
    -- Índices
    INDEX idx_farm_id (farm_id),
    INDEX idx_target_group (target_group),
    INDEX idx_is_active (is_active),
    
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de Itens do Programa de Vacinação
CREATE TABLE IF NOT EXISTS vaccination_program_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    program_id INT NOT NULL,
    medication_id INT NOT NULL,
    
    -- Dados da Aplicação
    application_order INT NOT NULL, -- Ordem de aplicação no programa
    days_after_birth INT, -- Dias após o nascimento para aplicar
    dosage_per_kg DECIMAL(5,2), -- Dosagem específica para este programa
    
    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_program_id (program_id),
    INDEX idx_medication_id (medication_id),
    INDEX idx_application_order (application_order),
    
    FOREIGN KEY (program_id) REFERENCES vaccination_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE
);

-- Tabela de Alertas Sanitários
CREATE TABLE IF NOT EXISTS health_alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NULL, -- NULL para alertas gerais
    medication_id INT NULL, -- NULL para alertas não relacionados a medicamentos
    
    -- Dados do Alerta
    alert_type ENUM('vacinação_pendente', 'carência_leite', 'carência_carne', 'medicamento_vencido', 'estoque_baixo', 'reteste_necessário') NOT NULL,
    alert_level ENUM('baixo', 'médio', 'alto', 'crítico') DEFAULT 'médio',
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    
    -- Datas
    alert_date DATE NOT NULL,
    due_date DATE, -- Data limite para resolver o alerta
    resolved_date DATE NULL, -- Data de resolução
    is_resolved BOOLEAN DEFAULT FALSE,
    
    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    
    -- Índices
    INDEX idx_farm_id (farm_id),
    INDEX idx_animal_id (animal_id),
    INDEX idx_alert_type (alert_type),
    INDEX idx_alert_level (alert_level),
    INDEX idx_due_date (due_date),
    INDEX idx_is_resolved (is_resolved),
    
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE SET NULL,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- DADOS INICIAIS DE EXEMPLO
-- =====================================================

-- Inserir medicamentos/vacinas de exemplo
INSERT INTO medications (farm_id, name, type, category, manufacturer, application_route, dosage_per_kg, milk_withdrawal_period, meat_withdrawal_period, unit_cost, stock_quantity, created_by) VALUES
(1, 'Vacina Febre Aftosa', 'vacina', 'obrigatória', 'Ministério da Agricultura', 'subcutânea', 2.0, 0, 0, 0.00, 50, 1),
(1, 'Vacina Brucelose', 'vacina', 'obrigatória', 'Lab. Veterinário', 'subcutânea', 2.0, 0, 0, 15.00, 30, 1),
(1, 'Vacina Clostridiose', 'vacina', 'recomendada', 'Lab. Veterinário', 'intramuscular', 5.0, 0, 0, 25.00, 20, 1),
(1, 'Penicilina G', 'antibiótico', 'recomendada', 'Lab. Veterinário', 'intramuscular', 10.0, 3, 7, 8.50, 15, 1),
(1, 'Oxitetraciclina', 'antibiótico', 'recomendada', 'Lab. Veterinário', 'intramuscular', 20.0, 5, 14, 12.00, 10, 1),
(1, 'Ivermectina', 'vermífugo', 'recomendada', 'Lab. Veterinário', 'subcutânea', 0.2, 5, 14, 18.00, 25, 1),
(1, 'Vitamina B12', 'vitamina', 'opcional', 'Lab. Veterinário', 'intramuscular', 1.0, 0, 0, 5.00, 40, 1);

-- Inserir programas de vacinação de exemplo
INSERT INTO vaccination_programs (farm_id, name, description, target_group, frequency_type, created_by) VALUES
(1, 'Programa Bezerros', 'Vacinação básica para bezerros', 'bezerros', 'personalizada', 1),
(1, 'Programa Vacas Adultas', 'Vacinação anual para vacas adultas', 'vacas', 'anual', 1),
(1, 'Programa Brucelose', 'Vacinação obrigatória contra brucelose', 'novilhas', 'única', 1);

-- Inserir itens dos programas
INSERT INTO vaccination_program_items (program_id, medication_id, application_order, days_after_birth) VALUES
(1, 1, 1, 30), -- Febre Aftosa aos 30 dias
(1, 3, 2, 45), -- Clostridiose aos 45 dias
(1, 6, 3, 60), -- Vermífugo aos 60 dias
(2, 1, 1, NULL), -- Febre Aftosa anual
(2, 3, 2, NULL), -- Clostridiose anual
(3, 2, 1, 120); -- Brucelose aos 120 dias (3-8 meses)

-- =====================================================
-- VIEWS PARA CONSULTAS AVANÇADAS
-- =====================================================

-- View para Controle de Carência
CREATE VIEW withdrawal_control AS
SELECT 
    ma.id,
    ma.application_date,
    ma.animal_id,
    a.animal_number,
    a.name as animal_name,
    m.name as medication_name,
    m.type as medication_type,
    ma.milk_withdrawal_end_date,
    ma.meat_withdrawal_end_date,
    ma.is_milk_blocked,
    ma.is_meat_blocked,
    CASE 
        WHEN ma.milk_withdrawal_end_date > CURDATE() THEN 'Bloqueado'
        ELSE 'Liberado'
    END as milk_status,
    CASE 
        WHEN ma.meat_withdrawal_end_date > CURDATE() THEN 'Bloqueado'
        ELSE 'Liberado'
    END as meat_status,
    DATEDIFF(ma.milk_withdrawal_end_date, CURDATE()) as days_until_milk_clear,
    DATEDIFF(ma.meat_withdrawal_end_date, CURDATE()) as days_until_meat_clear
FROM medication_applications ma
JOIN animals a ON ma.animal_id = a.id
JOIN medications m ON ma.medication_id = m.id
WHERE ma.farm_id = 1
ORDER BY ma.application_date DESC;

-- View para Alertas Ativos
CREATE VIEW active_health_alerts AS
SELECT 
    ha.id,
    ha.alert_type,
    ha.alert_level,
    ha.title,
    ha.message,
    ha.alert_date,
    ha.due_date,
    ha.animal_id,
    a.animal_number,
    a.name as animal_name,
    ha.medication_id,
    m.name as medication_name,
    DATEDIFF(ha.due_date, CURDATE()) as days_until_due
FROM health_alerts ha
LEFT JOIN animals a ON ha.animal_id = a.id
LEFT JOIN medications m ON ha.medication_id = m.id
WHERE ha.farm_id = 1 
    AND ha.is_resolved = FALSE
    AND (ha.due_date IS NULL OR ha.due_date >= CURDATE())
ORDER BY ha.alert_level DESC, ha.due_date ASC;

-- View para Estoque de Medicamentos
CREATE VIEW medication_stock AS
SELECT 
    m.id,
    m.name,
    m.type,
    m.manufacturer,
    m.stock_quantity,
    m.min_stock_level,
    m.expiration_date,
    CASE 
        WHEN m.stock_quantity <= m.min_stock_level THEN 'Baixo'
        WHEN m.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Vencendo'
        ELSE 'Normal'
    END as stock_status,
    DATEDIFF(m.expiration_date, CURDATE()) as days_until_expiration
FROM medications m
WHERE m.farm_id = 1
ORDER BY stock_status DESC, m.name;

-- =====================================================
-- TRIGGERS PARA AUTOMAÇÃO
-- =====================================================

-- Trigger para calcular datas de carência automaticamente
DELIMITER //
CREATE TRIGGER calculate_withdrawal_dates
AFTER INSERT ON medication_applications
FOR EACH ROW
BEGIN
    DECLARE milk_days INT;
    DECLARE meat_days INT;
    
    -- Obter períodos de carência do medicamento
    SELECT milk_withdrawal_period, meat_withdrawal_period 
    INTO milk_days, meat_days
    FROM medications 
    WHERE id = NEW.medication_id;
    
    -- Atualizar datas de carência
    UPDATE medication_applications 
    SET 
        withdrawal_start_date = NEW.application_date,
        milk_withdrawal_end_date = DATE_ADD(NEW.application_date, INTERVAL milk_days DAY),
        meat_withdrawal_end_date = DATE_ADD(NEW.application_date, INTERVAL meat_days DAY),
        is_milk_blocked = (milk_days > 0),
        is_meat_blocked = (meat_days > 0)
    WHERE id = NEW.id;
    
    -- Baixar estoque automaticamente
    UPDATE medications 
    SET stock_quantity = stock_quantity - 1 
    WHERE id = NEW.medication_id AND stock_quantity > 0;
END//
DELIMITER ;

-- Trigger para gerar alertas de estoque baixo
DELIMITER //
CREATE TRIGGER check_medication_stock
AFTER UPDATE ON medications
FOR EACH ROW
BEGIN
    -- Se estoque ficou baixo
    IF NEW.stock_quantity <= NEW.min_stock_level AND OLD.stock_quantity > OLD.min_stock_level THEN
        INSERT INTO health_alerts (farm_id, medication_id, alert_type, alert_level, title, message, alert_date, due_date, created_by)
        VALUES (NEW.farm_id, NEW.id, 'estoque_baixo', 'médio', 
                CONCAT('Estoque baixo: ', NEW.name),
                CONCAT('O medicamento ', NEW.name, ' está com estoque baixo (', NEW.stock_quantity, ' unidades).'),
                CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 1);
    END IF;
    
    -- Se medicamento está vencendo
    IF NEW.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND OLD.expiration_date > DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN
        INSERT INTO health_alerts (farm_id, medication_id, alert_type, alert_level, title, message, alert_date, due_date, created_by)
        VALUES (NEW.farm_id, NEW.id, 'medicamento_vencido', 'alto', 
                CONCAT('Medicamento vencendo: ', NEW.name),
                CONCAT('O medicamento ', NEW.name, ' vence em ', DATEDIFF(NEW.expiration_date, CURDATE()), ' dias.'),
                CURDATE(), NEW.expiration_date, 1);
    END IF;
END//
DELIMITER ;
