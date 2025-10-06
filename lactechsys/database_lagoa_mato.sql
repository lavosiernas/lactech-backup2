-- =====================================================
-- BANCO DE DADOS LACTECH - LAGOA DO MATO
-- Sistema de Gestão Leiteira para Fazenda Única
-- MySQL 8.0+ / PHPMyAdmin
-- COMPATÍVEL COM O SISTEMA EXISTENTE
-- =====================================================

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS lactech_lagoa_mato 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE lactech_lagoa_mato;

-- =====================================================
-- 1. TABELA DE USUÁRIOS
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('proprietario', 'gerente', 'funcionario', 'veterinario') NOT NULL DEFAULT 'funcionario',
    whatsapp VARCHAR(20),
    profile_photo_url TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- 2. TABELA DE ANIMAIS
-- =====================================================
CREATE TABLE animals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    identification VARCHAR(100) UNIQUE NOT NULL,
    birth_date DATE,
    breed VARCHAR(100),
    productive_status ENUM('Lactante', 'Seco', 'Novilha', 'Vaca', 'Bezerra', 'Bezerro') DEFAULT 'Bezerra',
    health_status ENUM('Saudável', 'Em Tratamento', 'Doente', 'Quarentena') DEFAULT 'Saudável',
    weight DECIMAL(6,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- 3. TABELA DE PRODUÇÃO DE LEITE (GERAL)
-- =====================================================
CREATE TABLE milk_production (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_date DATE NOT NULL,
    shift ENUM('manha', 'tarde', 'noite') NOT NULL,
    volume_liters DECIMAL(8,2) NOT NULL CHECK (volume_liters >= 0),
    temperature DECIMAL(4,1),
    observations TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_production (production_date, shift)
);

-- =====================================================
-- 4. TABELA DE PRODUÇÃO INDIVIDUAL POR VACA
-- =====================================================
CREATE TABLE individual_milk_production (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    production_date DATE NOT NULL,
    milking_time ENUM('manha', 'tarde', 'noite') NOT NULL,
    volume_liters DECIMAL(8,2) NOT NULL CHECK (volume_liters >= 0),
    temperature DECIMAL(4,1),
    milker VARCHAR(255),
    observations TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 5. TABELA DE TESTES DE QUALIDADE
-- =====================================================
CREATE TABLE quality_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    fat_percentage DECIMAL(5,2) CHECK (fat_percentage >= 0 AND fat_percentage <= 100),
    protein_percentage DECIMAL(5,2) CHECK (protein_percentage >= 0 AND protein_percentage <= 100),
    lactose_percentage DECIMAL(5,2) CHECK (lactose_percentage >= 0 AND lactose_percentage <= 100),
    scc INTEGER CHECK (scc >= 0), -- Contagem de Células Somáticas
    cbt INTEGER CHECK (cbt >= 0), -- Contagem Bacteriana Total
    laboratory VARCHAR(255),
    observations TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 6. TABELA DE TRATAMENTOS
-- =====================================================
CREATE TABLE treatments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    treatment_type ENUM('Medicamento', 'Vacinação', 'Vermifugação', 'Suplementação', 'Cirurgia', 'Outros') NOT NULL,
    medication VARCHAR(255),
    treatment_date DATE NOT NULL,
    observations TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 7. TABELA DE INSEMINAÇÕES
-- =====================================================
CREATE TABLE artificial_inseminations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    insemination_date DATE NOT NULL,
    semen_batch VARCHAR(100) NOT NULL,
    technician VARCHAR(255),
    observations TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 8. TABELA DE REGISTROS FINANCEIROS
-- =====================================================
CREATE TABLE financial_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_date DATE NOT NULL,
    type ENUM('receita', 'despesa') NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('dinheiro', 'cartao', 'transferencia', 'cheque', 'pix') DEFAULT 'dinheiro',
    observations TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 9. TABELA DE REGISTROS DE SAÚDE DOS ANIMAIS
-- =====================================================
CREATE TABLE animal_health_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    health_status ENUM('Saudável', 'Em Tratamento', 'Doente') NOT NULL,
    diagnosis TEXT,
    treatment_notes TEXT,
    record_date DATE NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 10. TABELA DE CONFIGURAÇÕES DA FAZENDA
-- =====================================================
CREATE TABLE farm_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_name VARCHAR(255) DEFAULT 'Lagoa do Mato',
    farm_logo_base64 TEXT,
    report_footer_text TEXT,
    system_logo_base64 TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- ÍNDICES PARA PERFORMANCE
-- =====================================================

-- Índices para tabela animals
CREATE INDEX idx_animals_productive_status ON animals(productive_status);
CREATE INDEX idx_animals_health_status ON animals(health_status);
CREATE INDEX idx_animals_active ON animals(is_active);

-- Índices para tabela milk_production
CREATE INDEX idx_milk_production_date ON milk_production(production_date);
CREATE INDEX idx_milk_production_shift ON milk_production(shift);

-- Índices para tabela individual_milk_production
CREATE INDEX idx_individual_milk_animal ON individual_milk_production(animal_id);
CREATE INDEX idx_individual_milk_date ON individual_milk_production(production_date);

-- Índices para tabela quality_tests
CREATE INDEX idx_quality_tests_date ON quality_tests(test_date);

-- Índices para tabela treatments
CREATE INDEX idx_treatments_animal ON treatments(animal_id);
CREATE INDEX idx_treatments_date ON treatments(treatment_date);

-- Índices para tabela artificial_inseminations
CREATE INDEX idx_inseminations_animal ON artificial_inseminations(animal_id);
CREATE INDEX idx_inseminations_date ON artificial_inseminations(insemination_date);

-- Índices para tabela financial_records
CREATE INDEX idx_financial_date ON financial_records(record_date);
CREATE INDEX idx_financial_type ON financial_records(type);

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Inserir usuário administrador padrão
INSERT INTO users (name, email, password, role) VALUES 
('Administrador', 'admin@lagoa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'proprietario');

-- Inserir configurações padrão da fazenda
INSERT INTO farm_settings (farm_name, report_footer_text) VALUES 
('Lagoa do Mato', 'Sistema de Gestão Leiteira - Fazenda Lagoa do Mato');

-- Inserir alguns animais de exemplo
INSERT INTO animals (name, identification, birth_date, breed, productive_status) VALUES 
('Vaca 001', 'LDM-001', '2020-03-15', 'Holandesa', 'Lactante'),
('Vaca 002', 'LDM-002', '2019-11-22', 'Girolanda', 'Lactante'),
('Novilha 001', 'LDM-003', '2022-05-10', 'Holandesa', 'Novilha'),
('Bezerra 001', 'LDM-004', '2023-08-15', 'Gir', 'Bezerra');

-- =====================================================
-- VIEWS PARA RELATÓRIOS
-- =====================================================

-- View para estatísticas de produção diária
CREATE VIEW daily_production_stats AS
SELECT 
    production_date,
    SUM(CASE WHEN shift = 'manha' THEN volume_liters ELSE 0 END) as manha_liters,
    SUM(CASE WHEN shift = 'tarde' THEN volume_liters ELSE 0 END) as tarde_liters,
    SUM(CASE WHEN shift = 'noite' THEN volume_liters ELSE 0 END) as noite_liters,
    SUM(volume_liters) as total_liters
FROM milk_production 
GROUP BY production_date 
ORDER BY production_date DESC;

-- View para estatísticas de animais
CREATE VIEW animal_stats AS
SELECT 
    COUNT(*) as total_animals,
    SUM(CASE WHEN productive_status = 'Lactante' THEN 1 ELSE 0 END) as lactating_cows,
    SUM(CASE WHEN health_status = 'Saudável' THEN 1 ELSE 0 END) as healthy_animals,
    SUM(CASE WHEN health_status = 'Em Tratamento' THEN 1 ELSE 0 END) as under_treatment,
    SUM(CASE WHEN health_status = 'Doente' THEN 1 ELSE 0 END) as sick_animals
FROM animals 
WHERE is_active = TRUE;

-- =====================================================
-- CONFIRMAÇÃO
-- =====================================================
SELECT 'Banco de dados Lagoa do Mato criado com sucesso!' as status;
SELECT 'Usuário admin criado: admin@lagoa.com / password' as login_info;
SELECT '4 animais de exemplo inseridos' as sample_data;
