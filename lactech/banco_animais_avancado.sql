-- =====================================================
-- BANCO DE DADOS AVANÇADO PARA GESTÃO DE ANIMAIS
-- Sistema Lactech - Fazenda Lagoa Do Mato
-- =====================================================

-- Tabela de Animais (Cadastro Completo)
CREATE TABLE IF NOT EXISTS animals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_number VARCHAR(20) NOT NULL UNIQUE, -- Número do brinco/identificação
    name VARCHAR(100), -- Nome do animal (opcional)
    breed VARCHAR(50) NOT NULL, -- Raça
    birth_date DATE NOT NULL,
    sex ENUM('macho', 'femea') NOT NULL,
    status ENUM('ativo', 'vendido', 'morto', 'transferido') DEFAULT 'ativo',
    
    -- Dados Genealógicos
    father_id INT NULL, -- ID do pai
    mother_id INT NULL, -- ID da mãe
    
    -- Dados Físicos
    weight_at_birth DECIMAL(5,2), -- Peso ao nascer
    current_weight DECIMAL(6,2), -- Peso atual
    color VARCHAR(50), -- Cor do animal
    markings TEXT, -- Marcações especiais
    
    -- Dados Reprodutivos (para fêmeas)
    first_heat_date DATE NULL, -- Primeiro cio
    first_insemination_date DATE NULL, -- Primeira inseminação
    first_birth_date DATE NULL, -- Primeiro parto
    last_heat_date DATE NULL, -- Último cio
    last_insemination_date DATE NULL, -- Última inseminação
    is_pregnant BOOLEAN DEFAULT FALSE, -- Está prenha?
    pregnancy_date DATE NULL, -- Data da prenhez
    expected_birth_date DATE NULL, -- Data prevista do parto
    lactation_days INT DEFAULT 0, -- Dias de lactação
    
    -- Dados de Produção
    daily_milk_average DECIMAL(5,2) DEFAULT 0, -- Média diária de leite
    total_lactation_milk DECIMAL(8,2) DEFAULT 0, -- Total de leite na lactação
    lactation_number INT DEFAULT 0, -- Número da lactação atual
    
    -- Dados Financeiros
    acquisition_cost DECIMAL(10,2) DEFAULT 0, -- Custo de aquisição
    maintenance_cost DECIMAL(10,2) DEFAULT 0, -- Custo de manutenção
    sale_price DECIMAL(10,2) DEFAULT 0, -- Preço de venda
    
    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    
    -- Índices
    INDEX idx_farm_id (farm_id),
    INDEX idx_animal_number (animal_number),
    INDEX idx_birth_date (birth_date),
    INDEX idx_father_id (father_id),
    INDEX idx_mother_id (mother_id),
    INDEX idx_status (status),
    INDEX idx_is_pregnant (is_pregnant),
    
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (father_id) REFERENCES animals(id) ON DELETE SET NULL,
    FOREIGN KEY (mother_id) REFERENCES animals(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de Inseminações
CREATE TABLE IF NOT EXISTS inseminations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NOT NULL,
    bull_id INT NULL, -- ID do touro (se cadastrado)
    bull_name VARCHAR(100), -- Nome do touro
    bull_breed VARCHAR(50), -- Raça do touro
    
    -- Dados da Inseminação
    insemination_date DATE NOT NULL,
    insemination_time TIME, -- Horário da inseminação
    technician_name VARCHAR(100), -- Nome do técnico
    technique VARCHAR(50), -- Técnica utilizada (IA, monta natural)
    
    -- Dados Reprodutivos
    heat_date DATE, -- Data do cio
    heat_signs TEXT, -- Sinais de cio observados
    pregnancy_test_date DATE, -- Data do teste de prenhez
    pregnancy_result ENUM('positivo', 'negativo', 'pendente') DEFAULT 'pendente',
    birth_date DATE, -- Data do parto (se ocorreu)
    
    -- Resultados
    calf_sex ENUM('macho', 'femea') NULL,
    calf_weight DECIMAL(5,2), -- Peso do bezerro
    calf_status ENUM('vivo', 'morto', 'nascido_morto') DEFAULT 'vivo',
    
    -- Observações
    notes TEXT,
    
    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    
    -- Índices
    INDEX idx_farm_id (farm_id),
    INDEX idx_animal_id (animal_id),
    INDEX idx_insemination_date (insemination_date),
    INDEX idx_pregnancy_result (pregnancy_result),
    
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de Lactações
CREATE TABLE IF NOT EXISTS lactations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NOT NULL,
    insemination_id INT NULL, -- ID da inseminação que resultou nesta lactação
    
    -- Dados da Lactação
    lactation_number INT NOT NULL, -- Número da lactação (1ª, 2ª, etc.)
    start_date DATE NOT NULL, -- Data de início da lactação
    end_date DATE NULL, -- Data de fim da lactação
    dry_period_days INT DEFAULT 0, -- Dias de período seco
    
    -- Produção Total
    total_milk DECIMAL(8,2) DEFAULT 0, -- Total de leite produzido
    peak_daily_production DECIMAL(5,2) DEFAULT 0, -- Pico de produção diária
    average_daily_production DECIMAL(5,2) DEFAULT 0, -- Média diária
    
    -- Qualidade do Leite
    average_fat_percentage DECIMAL(4,2) DEFAULT 0,
    average_protein_percentage DECIMAL(4,2) DEFAULT 0,
    average_scc INT DEFAULT 0, -- Contagem de células somáticas
    
    -- Status
    status ENUM('ativa', 'encerrada') DEFAULT 'ativa',
    
    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    
    -- Índices
    INDEX idx_farm_id (farm_id),
    INDEX idx_animal_id (animal_id),
    INDEX idx_lactation_number (lactation_number),
    INDEX idx_start_date (start_date),
    INDEX idx_status (status),
    
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (insemination_id) REFERENCES inseminations(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de Eventos Reprodutivos
CREATE TABLE IF NOT EXISTS reproductive_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    animal_id INT NOT NULL,
    
    -- Tipo de Evento
    event_type ENUM('cio', 'inseminação', 'teste_prenhez', 'parto', 'aborto', 'perda_embrião') NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME,
    
    -- Detalhes do Evento
    description TEXT,
    result VARCHAR(100), -- Resultado do evento
    notes TEXT,
    
    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    
    -- Índices
    INDEX idx_farm_id (farm_id),
    INDEX idx_animal_id (animal_id),
    INDEX idx_event_type (event_type),
    INDEX idx_event_date (event_date),
    
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de Touros (Reprodutores)
CREATE TABLE IF NOT EXISTS bulls (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL DEFAULT 1,
    bull_number VARCHAR(20) NOT NULL,
    name VARCHAR(100),
    breed VARCHAR(50) NOT NULL,
    birth_date DATE,
    
    -- Dados Reprodutivos
    semen_type ENUM('fresco', 'congelado', 'sexado') DEFAULT 'congelado',
    genetic_value DECIMAL(4,2), -- Valor genético
    productivity_index DECIMAL(4,2), -- Índice de produtividade
    
    -- Status
    status ENUM('ativo', 'inativo', 'vendido') DEFAULT 'ativo',
    
    -- Metadados
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    
    -- Índices
    INDEX idx_farm_id (farm_id),
    INDEX idx_bull_number (bull_number),
    INDEX idx_status (status),
    
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- DADOS INICIAIS DE EXEMPLO
-- =====================================================

-- Inserir alguns touros de exemplo
INSERT INTO bulls (farm_id, bull_number, name, breed, birth_date, semen_type, status, created_by) VALUES
(1, 'T001', 'Champion Holstein', 'Holandês', '2020-01-15', 'congelado', 'ativo', 1),
(1, 'T002', 'Elite Jersey', 'Jersey', '2019-05-20', 'congelado', 'ativo', 1),
(1, 'T003', 'Premium Girolando', 'Girolando', '2021-03-10', 'sexado', 'ativo', 1);

-- Inserir alguns animais de exemplo
INSERT INTO animals (farm_id, animal_number, name, breed, birth_date, sex, weight_at_birth, current_weight, color, status, created_by) VALUES
(1, 'V001', 'Bella', 'Holandesa', '2020-03-15', 'femea', 35.5, 580.0, 'Preto e Branco', 'ativo', 1),
(1, 'V002', 'Luna', 'Holandesa', '2019-08-20', 'femea', 38.2, 620.0, 'Preto e Branco', 'ativo', 1),
(1, 'V003', 'Stella', 'Jersey', '2021-01-10', 'femea', 28.0, 420.0, 'Marrom', 'ativo', 1),
(1, 'V004', 'Maya', 'Girolando', '2020-11-05', 'femea', 32.0, 550.0, 'Malhado', 'ativo', 1),
(1, 'V005', 'Nina', 'Holandesa', '2021-06-12', 'femea', 37.0, 480.0, 'Preto e Branco', 'ativo', 1);

-- Inserir algumas inseminações de exemplo
INSERT INTO inseminations (farm_id, animal_id, bull_name, bull_breed, insemination_date, technician_name, technique, heat_date, pregnancy_result, created_by) VALUES
(1, 1, 'Champion Holstein', 'Holandês', '2023-05-15', 'Dr. Silva', 'IA', '2023-05-14', 'positivo', 1),
(1, 2, 'Elite Jersey', 'Jersey', '2023-06-20', 'Dr. Santos', 'IA', '2023-06-19', 'positivo', 1),
(1, 3, 'Premium Girolando', 'Girolando', '2023-07-10', 'Dr. Silva', 'IA', '2023-07-09', 'pendente', 1);

-- Inserir algumas lactações de exemplo
INSERT INTO lactations (farm_id, animal_id, lactation_number, start_date, total_milk, peak_daily_production, average_daily_production, status, created_by) VALUES
(1, 1, 1, '2023-01-15', 8500.0, 45.5, 32.8, 'ativa', 1),
(1, 2, 2, '2023-02-20', 9200.0, 48.2, 35.1, 'ativa', 1),
(1, 3, 1, '2023-03-10', 6800.0, 38.5, 28.9, 'ativa', 1);

-- Inserir alguns eventos reprodutivos
INSERT INTO reproductive_events (farm_id, animal_id, event_type, event_date, description, result, created_by) VALUES
(1, 1, 'cio', '2023-05-14', 'Cio observado pela manhã', 'Sinais claros', 1),
(1, 1, 'inseminação', '2023-05-15', 'Inseminação artificial realizada', 'Sucesso', 1),
(1, 1, 'teste_prenhez', '2023-06-15', 'Teste de prenhez por ultrassom', 'Positivo', 1),
(1, 2, 'cio', '2023-06-19', 'Cio observado no período da tarde', 'Sinais claros', 1),
(1, 2, 'inseminação', '2023-06-20', 'Inseminação artificial realizada', 'Sucesso', 1);

-- =====================================================
-- VIEWS PARA CONSULTAS AVANÇADAS
-- =====================================================

-- View para Árvore Genealógica
CREATE VIEW animal_pedigree AS
SELECT 
    a.id,
    a.animal_number,
    a.name,
    a.breed,
    a.birth_date,
    a.sex,
    -- Pai
    f.animal_number as father_number,
    f.name as father_name,
    f.breed as father_breed,
    -- Mãe
    m.animal_number as mother_number,
    m.name as mother_name,
    m.breed as mother_breed,
    -- Avós (paternos)
    ff.animal_number as grandfather_number,
    ff.name as grandfather_name,
    fm.animal_number as grandmother_father_number,
    fm.name as grandmother_father_name,
    -- Avós (maternos)
    mf.animal_number as grandfather_mother_number,
    mf.name as grandfather_mother_name,
    mm.animal_number as grandmother_mother_number,
    mm.name as grandmother_mother_name
FROM animals a
LEFT JOIN animals f ON a.father_id = f.id
LEFT JOIN animals m ON a.mother_id = m.id
LEFT JOIN animals ff ON f.father_id = ff.id
LEFT JOIN animals fm ON f.mother_id = fm.id
LEFT JOIN animals mf ON m.father_id = mf.id
LEFT JOIN animals mm ON m.mother_id = mm.id;

-- View para Produtividade dos Animais
CREATE VIEW animal_productivity AS
SELECT 
    a.id,
    a.animal_number,
    a.name,
    a.breed,
    a.birth_date,
    a.current_weight,
    a.is_pregnant,
    a.pregnancy_date,
    a.expected_birth_date,
    a.lactation_days,
    a.daily_milk_average,
    a.total_lactation_milk,
    a.lactation_number,
    -- Última inseminação
    i.insemination_date as last_insemination_date,
    i.bull_name as last_bull_used,
    i.pregnancy_result as last_pregnancy_result,
    -- Lactação atual
    l.total_milk as current_lactation_total,
    l.average_daily_production as current_lactation_avg,
    l.peak_daily_production as current_lactation_peak
FROM animals a
LEFT JOIN inseminations i ON a.id = i.animal_id 
    AND i.insemination_date = (
        SELECT MAX(insemination_date) 
        FROM inseminations i2 
        WHERE i2.animal_id = a.id
    )
LEFT JOIN lactations l ON a.id = l.animal_id 
    AND l.status = 'ativa';

-- =====================================================
-- TRIGGERS PARA AUTOMAÇÃO
-- =====================================================

-- Trigger para calcular DPP automaticamente
DELIMITER //
CREATE TRIGGER calculate_expected_birth_date
AFTER INSERT ON inseminations
FOR EACH ROW
BEGIN
    IF NEW.pregnancy_result = 'positivo' THEN
        UPDATE animals 
        SET 
            is_pregnant = TRUE,
            pregnancy_date = NEW.insemination_date,
            expected_birth_date = DATE_ADD(NEW.insemination_date, INTERVAL 280 DAY)
        WHERE id = NEW.animal_id;
    END IF;
END//
DELIMITER ;

-- Trigger para atualizar lactação após parto
DELIMITER //
CREATE TRIGGER update_lactation_after_birth
AFTER UPDATE ON inseminations
FOR EACH ROW
BEGIN
    IF OLD.pregnancy_result != 'positivo' AND NEW.pregnancy_result = 'positivo' AND NEW.birth_date IS NOT NULL THEN
        -- Criar nova lactação
        INSERT INTO lactations (farm_id, animal_id, lactation_number, start_date, status, created_by)
        SELECT 
            NEW.farm_id,
            NEW.animal_id,
            COALESCE(MAX(l.lactation_number), 0) + 1,
            NEW.birth_date,
            'ativa',
            NEW.created_by
        FROM lactations l 
        WHERE l.animal_id = NEW.animal_id;
        
        -- Atualizar animal
        UPDATE animals 
        SET 
            lactation_number = lactation_number + 1,
            is_pregnant = FALSE,
            pregnancy_date = NULL,
            expected_birth_date = NULL
        WHERE id = NEW.animal_id;
    END IF;
END//
DELIMITER ;

