-- =====================================================
-- BANCO DE DADOS PARA SISTEMA DE REPRODUÇÃO AVANÇADO
-- Sistema Lactech - Fazenda Lagoa Do Mato
-- =====================================================

-- Tabela de Controle de Cios
CREATE TABLE IF NOT EXISTS heat_cycles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NOT NULL,
    
    -- Dados do Cio
    heat_date DATE NOT NULL,
    heat_time TIME,
    heat_intensity ENUM('baixo', 'médio', 'alto') DEFAULT 'médio',
    heat_signs TEXT, -- Sinais observados
    
    -- Dados Reprodutivos
    days_since_last_heat INT, -- Dias desde o último cio
    days_since_last_birth INT, -- Dias desde o último parto
    is_artificial_insemination BOOLEAN DEFAULT TRUE, -- Será IA?
    
    -- Observações
    notes TEXT,
    
    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    
    -- Índices
    INDEX idx_farm_id (farm_id),
    INDEX idx_animal_id (animal_id),
    INDEX idx_heat_date (heat_date),
    
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de Controle de Prenhez
CREATE TABLE IF NOT EXISTS pregnancy_controls (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NOT NULL,
    insemination_id INT NULL,
    
    -- Dados da Prenhez
    pregnancy_confirmation_date DATE, -- Data da confirmação
    confirmation_method ENUM('ultrassom', 'palpação', 'teste_sangue', 'outro') NOT NULL,
    confirmed_by VARCHAR(100), -- Quem confirmou
    
    -- Dados do Controle
    expected_birth_date DATE NOT NULL, -- DPP
    pregnancy_days INT GENERATED ALWAYS AS (DATEDIFF(expected_birth_date, pregnancy_confirmation_date)) STORED,
    current_pregnancy_day INT GENERATED ALWAYS AS (DATEDIFF(CURDATE(), pregnancy_confirmation_date)) STORED,
    
    -- Status da Prenhez
    pregnancy_status ENUM('ativa', 'abortada', 'parto_realizado', 'perda_embrião') DEFAULT 'ativa',
    pregnancy_notes TEXT,
    
    -- Controle Veterinário
    last_vet_check_date DATE,
    next_vet_check_date DATE,
    vet_check_notes TEXT,
    
    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    
    -- Índices
    INDEX idx_farm_id (farm_id),
    INDEX idx_animal_id (animal_id),
    INDEX idx_insemination_id (insemination_id),
    INDEX idx_expected_birth_date (expected_birth_date),
    INDEX idx_pregnancy_status (pregnancy_status),
    
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (insemination_id) REFERENCES inseminations(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de Alertas de Maternidade
CREATE TABLE IF NOT EXISTS maternity_alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NOT NULL,
    pregnancy_control_id INT NOT NULL,
    
    -- Dados do Alerta
    alert_type ENUM('pré_parto', 'parto_iminente', 'pós_parto', 'cuidados_especiais') NOT NULL,
    alert_level ENUM('baixo', 'médio', 'alto', 'crítico') DEFAULT 'médio',
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    
    -- Datas
    alert_date DATE NOT NULL,
    due_date DATE, -- Data limite
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_date DATE NULL,
    
    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    
    -- Índices
    INDEX idx_farm_id (farm_id),
    INDEX idx_animal_id (animal_id),
    INDEX idx_pregnancy_control_id (pregnancy_control_id),
    INDEX idx_alert_type (alert_type),
    INDEX idx_due_date (due_date),
    INDEX idx_is_resolved (is_resolved),
    
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (pregnancy_control_id) REFERENCES pregnancy_controls(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de Controle de Partos
CREATE TABLE IF NOT EXISTS births (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NOT NULL,
    pregnancy_control_id INT NOT NULL,
    
    -- Dados do Parto
    birth_date DATE NOT NULL,
    birth_time TIME,
    birth_type ENUM('normal', 'cesariana', 'assistido', 'dificil') DEFAULT 'normal',
    birth_assistance VARCHAR(100), -- Quem assistiu
    
    -- Dados dos Bezerros
    calf_count INT DEFAULT 1,
    calf_1_sex ENUM('macho', 'femea') NULL,
    calf_1_weight DECIMAL(5,2) NULL,
    calf_1_status ENUM('vivo', 'morto', 'nascido_morto') DEFAULT 'vivo',
    calf_2_sex ENUM('macho', 'femea') NULL,
    calf_2_weight DECIMAL(5,2) NULL,
    calf_2_status ENUM('vivo', 'morto', 'nascido_morto') DEFAULT 'vivo',
    
    -- Dados da Mãe
    mother_condition ENUM('boa', 'regular', 'ruim') DEFAULT 'boa',
    mother_notes TEXT,
    
    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    
    -- Índices
    INDEX idx_farm_id (farm_id),
    INDEX idx_animal_id (animal_id),
    INDEX idx_pregnancy_control_id (pregnancy_control_id),
    INDEX idx_birth_date (birth_date),
    
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (pregnancy_control_id) REFERENCES pregnancy_controls(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de Indicadores Reprodutivos
CREATE TABLE IF NOT EXISTS reproductive_indicators (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NOT NULL,
    
    -- Período de Análise
    analysis_period_start DATE NOT NULL,
    analysis_period_end DATE NOT NULL,
    
    -- Indicadores Básicos
    total_inseminations INT DEFAULT 0,
    successful_inseminations INT DEFAULT 0,
    pregnancy_rate DECIMAL(5,2) GENERATED ALWAYS AS (
        CASE WHEN total_inseminations > 0 
        THEN (successful_inseminations * 100.0 / total_inseminations) 
        ELSE 0 END
    ) STORED,
    
    -- Intervalo entre Partos
    calving_interval_days INT NULL,
    last_calving_date DATE NULL,
    next_expected_heat_date DATE NULL,
    
    -- Indicadores de Eficiência
    days_open INT NULL, -- Dias abertos (não prenha)
    services_per_conception DECIMAL(3,1) NULL,
    
    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    
    -- Índices
    INDEX idx_farm_id (farm_id),
    INDEX idx_animal_id (animal_id),
    INDEX idx_analysis_period_start (analysis_period_start),
    INDEX idx_analysis_period_end (analysis_period_end),
    
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- DADOS INICIAIS DE EXEMPLO
-- =====================================================

-- Inserir alguns controles de cio de exemplo
INSERT INTO heat_cycles (farm_id, animal_id, heat_date, heat_time, heat_intensity, heat_signs, days_since_last_heat, days_since_last_birth, created_by) VALUES
(1, 1, '2023-08-15', '06:30:00', 'alto', 'Mugidos constantes, inquietação, monta em outras vacas', 21, 45, 1),
(1, 2, '2023-08-20', '14:15:00', 'médio', 'Secreção vaginal, comportamento ativo', 18, 52, 1),
(1, 3, '2023-08-25', '08:45:00', 'alto', 'Mugidos, inquietação, monta', 22, 38, 1);

-- Inserir controles de prenhez de exemplo
INSERT INTO pregnancy_controls (farm_id, animal_id, insemination_id, pregnancy_confirmation_date, confirmation_method, confirmed_by, expected_birth_date, pregnancy_status, created_by) VALUES
(1, 1, 1, '2023-06-15', 'ultrassom', 'Dr. Silva', '2024-03-20', 'ativa', 1),
(1, 2, 2, '2023-07-20', 'palpação', 'Dr. Santos', '2024-04-25', 'ativa', 1),
(1, 3, 3, '2023-08-10', 'ultrassom', 'Dr. Silva', '2024-05-15', 'ativa', 1);

-- Inserir alertas de maternidade de exemplo
INSERT INTO maternity_alerts (farm_id, animal_id, pregnancy_control_id, alert_type, alert_level, title, message, alert_date, due_date, created_by) VALUES
(1, 1, 1, 'pré_parto', 'alto', 'Vaca Bella - Pré-parto em 30 dias', 'A vaca Bella está entrando no período pré-parto. Verificar condições do local e preparar materiais necessários.', '2024-02-19', '2024-03-20', 1),
(1, 2, 2, 'pré_parto', 'médio', 'Vaca Luna - Pré-parto em 45 dias', 'A vaca Luna está se aproximando do período pré-parto. Monitorar condições físicas.', '2024-03-10', '2024-04-25', 1);

-- =====================================================
-- VIEWS PARA CONSULTAS AVANÇADAS
-- =====================================================

-- View para Controle de Prenhez Ativa
CREATE VIEW active_pregnancies AS
SELECT 
    pc.id,
    pc.animal_id,
    a.animal_number,
    a.name as animal_name,
    a.breed,
    pc.pregnancy_confirmation_date,
    pc.expected_birth_date,
    pc.current_pregnancy_day,
    pc.pregnancy_status,
    pc.last_vet_check_date,
    pc.next_vet_check_date,
    DATEDIFF(pc.expected_birth_date, CURDATE()) as days_until_birth,
    CASE 
        WHEN DATEDIFF(pc.expected_birth_date, CURDATE()) <= 30 THEN 'Pré-parto'
        WHEN DATEDIFF(pc.expected_birth_date, CURDATE()) <= 7 THEN 'Parto Iminente'
        WHEN DATEDIFF(pc.expected_birth_date, CURDATE()) <= 0 THEN 'Vencido'
        ELSE 'Normal'
    END as pregnancy_stage
FROM pregnancy_controls pc
JOIN animals a ON pc.animal_id = a.id
WHERE pc.farm_id = 1 AND pc.pregnancy_status = 'ativa'
ORDER BY pc.expected_birth_date ASC;

-- View para Alertas de Maternidade Ativos
CREATE VIEW active_maternity_alerts AS
SELECT 
    ma.id,
    ma.alert_type,
    ma.alert_level,
    ma.title,
    ma.message,
    ma.alert_date,
    ma.due_date,
    ma.animal_id,
    a.animal_number,
    a.name as animal_name,
    pc.expected_birth_date,
    DATEDIFF(ma.due_date, CURDATE()) as days_until_due
FROM maternity_alerts ma
JOIN animals a ON ma.animal_id = a.id
JOIN pregnancy_controls pc ON ma.pregnancy_control_id = pc.id
WHERE ma.farm_id = 1 
    AND ma.is_resolved = FALSE
    AND (ma.due_date IS NULL OR ma.due_date >= CURDATE())
ORDER BY ma.alert_level DESC, ma.due_date ASC;

-- View para Indicadores Reprodutivos por Animal
CREATE VIEW animal_reproductive_performance AS
SELECT 
    a.id,
    a.animal_number,
    a.name as animal_name,
    a.breed,
    a.birth_date,
    
    -- Estatísticas de Inseminação
    COUNT(i.id) as total_inseminations,
    COUNT(CASE WHEN i.pregnancy_result = 'positivo' THEN 1 END) as successful_inseminations,
    CASE 
        WHEN COUNT(i.id) > 0 
        THEN (COUNT(CASE WHEN i.pregnancy_result = 'positivo' THEN 1 END) * 100.0 / COUNT(i.id))
        ELSE 0 
    END as pregnancy_rate,
    
    -- Intervalo entre Partos
    DATEDIFF(
        (SELECT MAX(b.birth_date) FROM births b WHERE b.animal_id = a.id),
        (SELECT MIN(b.birth_date) FROM births b WHERE b.animal_id = a.id)
    ) / GREATEST(COUNT(DISTINCT b.id) - 1, 1) as avg_calving_interval,
    
    -- Último Parto
    (SELECT MAX(b.birth_date) FROM births b WHERE b.animal_id = a.id) as last_birth_date,
    
    -- Status Reprodutivo
    CASE 
        WHEN pc.pregnancy_status = 'ativa' THEN 'Prenha'
        WHEN (SELECT COUNT(*) FROM heat_cycles hc WHERE hc.animal_id = a.id AND hc.heat_date >= DATE_SUB(CURDATE(), INTERVAL 21 DAY)) > 0 THEN 'Em Cio'
        ELSE 'Lactando'
    END as reproductive_status
    
FROM animals a
LEFT JOIN inseminations i ON a.id = i.animal_id
LEFT JOIN births b ON a.id = b.animal_id
LEFT JOIN pregnancy_controls pc ON a.id = pc.animal_id AND pc.pregnancy_status = 'ativa'
WHERE a.farm_id = 1 AND a.sex = 'femea'
GROUP BY a.id
ORDER BY a.animal_number;

-- =====================================================
-- TRIGGERS PARA AUTOMAÇÃO
-- =====================================================

-- Trigger para gerar alertas de maternidade automaticamente
DELIMITER //
CREATE TRIGGER generate_maternity_alerts
AFTER INSERT ON pregnancy_controls
FOR EACH ROW
BEGIN
    -- Alerta de pré-parto (30 dias antes)
    INSERT INTO maternity_alerts (farm_id, animal_id, pregnancy_control_id, alert_type, alert_level, title, message, alert_date, due_date, created_by)
    VALUES (NEW.farm_id, NEW.animal_id, NEW.id, 'pré_parto', 'alto', 
            CONCAT('Pré-parto em 30 dias - ', (SELECT animal_number FROM animals WHERE id = NEW.animal_id)),
            CONCAT('A vaca está entrando no período pré-parto. Verificar condições do local e preparar materiais necessários.'),
            DATE_SUB(NEW.expected_birth_date, INTERVAL 30 DAY),
            NEW.expected_birth_date, 1);
    
    -- Alerta de parto iminente (7 dias antes)
    INSERT INTO maternity_alerts (farm_id, animal_id, pregnancy_control_id, alert_type, alert_level, title, message, alert_date, due_date, created_by)
    VALUES (NEW.farm_id, NEW.animal_id, NEW.id, 'parto_iminente', 'crítico', 
            CONCAT('Parto iminente - ', (SELECT animal_number FROM animals WHERE id = NEW.animal_id)),
            CONCAT('A vaca está próxima do parto. Monitorar 24h e estar preparado para assistência.'),
            DATE_SUB(NEW.expected_birth_date, INTERVAL 7 DAY),
            NEW.expected_birth_date, 1);
END//
DELIMITER ;

-- Trigger para atualizar indicadores após parto
DELIMITER //
CREATE TRIGGER update_reproductive_indicators_after_birth
AFTER INSERT ON births
FOR EACH ROW
BEGIN
    DECLARE animal_age INT;
    DECLARE last_birth DATE;
    DECLARE calving_interval INT;
    
    -- Obter idade do animal
    SELECT DATEDIFF(CURDATE(), birth_date) INTO animal_age
    FROM animals WHERE id = NEW.animal_id;
    
    -- Obter último parto (excluindo o atual)
    SELECT MAX(birth_date) INTO last_birth
    FROM births 
    WHERE animal_id = NEW.animal_id AND id != NEW.id;
    
    -- Calcular intervalo entre partos
    IF last_birth IS NOT NULL THEN
        SET calving_interval = DATEDIFF(NEW.birth_date, last_birth);
    ELSE
        SET calving_interval = animal_age;
    END IF;
    
    -- Atualizar ou inserir indicadores reprodutivos
    INSERT INTO reproductive_indicators (farm_id, animal_id, analysis_period_start, analysis_period_end, total_inseminations, successful_inseminations, calving_interval_days, last_calving_date, created_by)
    VALUES (NEW.farm_id, NEW.animal_id, 
            COALESCE(last_birth, DATE_SUB(NEW.birth_date, INTERVAL 1 YEAR)), 
            NEW.birth_date,
            (SELECT COUNT(*) FROM inseminations WHERE animal_id = NEW.animal_id),
            (SELECT COUNT(*) FROM inseminations WHERE animal_id = NEW.animal_id AND pregnancy_result = 'positivo'),
            calving_interval,
            NEW.birth_date,
            1)
    ON DUPLICATE KEY UPDATE
        calving_interval_days = calving_interval,
        last_calving_date = NEW.birth_date,
        updated_at = NOW();
END//
DELIMITER ;

