-- =====================================================
-- BANCO DE DADOS LACTECH - LAGOA DO MATO
-- Sistema de Gestão Leiteira para Fazenda Única
-- MySQL 8.0+ / PHPMyAdmin
-- COMPATÍVEL 100% COM O SISTEMA EXISTENTE
-- 
-- OBSERVAÇÕES:
-- - Sistema de chat removido (não necessário para Lagoa do Mato)
-- - Apenas 3 tipos de usuários: proprietario, gerente, funcionario
-- - Página do veterinário desativada (funções movidas para gerente)
-- =====================================================

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS lactech_lagoa_mato 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE lactech_lagoa_mato;

-- =====================================================
-- 1. TABELA DE FAZENDAS (para compatibilidade)
-- =====================================================
CREATE TABLE farms (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(255) NOT NULL DEFAULT 'Lagoa do Mato',
    owner_name VARCHAR(255),
    cnpj VARCHAR(18),
    city VARCHAR(100),
    state VARCHAR(2),
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    is_setup_complete BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- 2. TABELA DE USUÁRIOS
-- =====================================================
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    farm_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('proprietario', 'gerente', 'funcionario') NOT NULL DEFAULT 'funcionario',
    whatsapp VARCHAR(20),
    profile_photo_url TEXT,
    report_farm_name VARCHAR(255),
    report_farm_logo_base64 TEXT,
    report_footer_text TEXT,
    report_system_logo_base64 TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_by VARCHAR(36),
    temp_password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 3. TABELA DE ANIMAIS
-- =====================================================
CREATE TABLE animals (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    farm_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36),
    name VARCHAR(255) NOT NULL,
    identification VARCHAR(100) UNIQUE,
    birth_date DATE,
    breed VARCHAR(100),
    weight DECIMAL(6,2),
    health_status ENUM('saudavel', 'doente', 'tratamento', 'quarentena') DEFAULT 'saudavel',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 4. TABELA DE PRODUÇÃO DE LEITE (volume_records)
-- =====================================================
CREATE TABLE volume_records (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    farm_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    production_date DATE NOT NULL,
    shift ENUM('manha', 'tarde', 'noite') NOT NULL,
    volume_liters DECIMAL(8,2) NOT NULL CHECK (volume_liters >= 0),
    temperature DECIMAL(4,1),
    milking_type VARCHAR(50),
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_production (farm_id, production_date, shift)
);

-- =====================================================
-- 5. TABELA DE TESTES DE QUALIDADE
-- =====================================================
CREATE TABLE quality_tests (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    farm_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    test_date DATE NOT NULL,
    fat_percentage DECIMAL(4,2),
    protein_percentage DECIMAL(4,2),
    lactose_percentage DECIMAL(4,2),
    scc INTEGER,
    cbt INTEGER,
    laboratory VARCHAR(255),
    observations TEXT,
    quality_score DECIMAL(4,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 6. TABELA DE TRATAMENTOS
-- =====================================================
CREATE TABLE treatments (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    farm_id VARCHAR(36) NOT NULL,
    animal_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    treatment_date DATE NOT NULL,
    description TEXT NOT NULL,
    medication VARCHAR(255),
    dosage VARCHAR(100),
    observations TEXT,
    next_treatment_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 7. TABELA DE REGISTROS DE SAÚDE DOS ANIMAIS
-- =====================================================
CREATE TABLE animal_health_records (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    farm_id VARCHAR(36) NOT NULL,
    animal_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36),
    record_date DATE NOT NULL,
    health_status ENUM('saudavel', 'doente', 'tratamento', 'quarentena') NOT NULL,
    weight DECIMAL(6,2),
    temperature DECIMAL(4,1),
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 8. TABELA DE INSEMINAÇÕES ARTIFICIAIS
-- =====================================================
CREATE TABLE artificial_inseminations (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    farm_id VARCHAR(36) NOT NULL,
    animal_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    insemination_date DATE NOT NULL,
    semen_batch VARCHAR(100) NOT NULL,
    semen_origin VARCHAR(255),
    bull_identification VARCHAR(100),
    technician_name VARCHAR(255),
    technique_used ENUM('convencional', 'timed_ai', 'embryo_transfer') DEFAULT 'convencional',
    estrus_detection_method ENUM('visual', 'detector', 'hormonal', 'ultrassom'),
    body_condition_score DECIMAL(2,1) CHECK (body_condition_score >= 1.0 AND body_condition_score <= 5.0),
    pregnancy_confirmed BOOLEAN DEFAULT NULL,
    pregnancy_confirmation_date DATE,
    pregnancy_confirmation_method ENUM('palpacao', 'ultrassom', 'exame_sangue'),
    observations TEXT,
    success_rate_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 9. TABELA DE REGISTROS FINANCEIROS
-- =====================================================
CREATE TABLE financial_records (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    farm_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36),
    record_date DATE NOT NULL,
    type ENUM('receita', 'despesa') NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('dinheiro', 'cartao', 'transferencia', 'cheque', 'pix') DEFAULT 'dinheiro',
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 10. TABELA DE NOTIFICAÇÕES (Sistema interno)
-- =====================================================
CREATE TABLE notifications (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    farm_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36),
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 11. TABELA DE CONTAS SECUNDÁRIAS
-- =====================================================
CREATE TABLE secondary_accounts (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    primary_user_id VARCHAR(36) NOT NULL,
    secondary_user_id VARCHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_secondary (primary_user_id, secondary_user_id),
    FOREIGN KEY (primary_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (secondary_user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 12. TABELA DE CONFIGURAÇÕES DA FAZENDA
-- =====================================================
CREATE TABLE farm_settings (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    farm_id VARCHAR(36) NOT NULL,
    farm_name VARCHAR(255) DEFAULT 'Lagoa do Mato',
    farm_logo_base64 TEXT,
    report_footer_text TEXT,
    system_logo_base64 TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
);

-- =====================================================
-- ÍNDICES PARA PERFORMANCE
-- =====================================================

-- Índices para farms
CREATE INDEX idx_farms_name ON farms(name);

-- Índices para users
CREATE INDEX idx_users_farm_id ON users(farm_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_active ON users(is_active);

-- Índices para animals
CREATE INDEX idx_animals_farm_id ON animals(farm_id);
CREATE INDEX idx_animals_health_status ON animals(health_status);
CREATE INDEX idx_animals_active ON animals(is_active);

-- Índices para volume_records
CREATE INDEX idx_volume_records_farm_id ON volume_records(farm_id);
CREATE INDEX idx_volume_records_date ON volume_records(production_date);
CREATE INDEX idx_volume_records_user ON volume_records(user_id);

-- Índices para quality_tests
CREATE INDEX idx_quality_tests_farm_id ON quality_tests(farm_id);
CREATE INDEX idx_quality_tests_date ON quality_tests(test_date);

-- Índices para treatments
CREATE INDEX idx_treatments_farm_id ON treatments(farm_id);
CREATE INDEX idx_treatments_animal_id ON treatments(animal_id);
CREATE INDEX idx_treatments_date ON treatments(treatment_date);

-- Índices para animal_health_records
CREATE INDEX idx_health_records_farm_id ON animal_health_records(farm_id);
CREATE INDEX idx_health_records_animal_id ON animal_health_records(animal_id);
CREATE INDEX idx_health_records_date ON animal_health_records(record_date);

-- Índices para artificial_inseminations
CREATE INDEX idx_inseminations_farm_id ON artificial_inseminations(farm_id);
CREATE INDEX idx_inseminations_animal_id ON artificial_inseminations(animal_id);
CREATE INDEX idx_inseminations_date ON artificial_inseminations(insemination_date);

-- Índices para financial_records
CREATE INDEX idx_financial_farm_id ON financial_records(farm_id);
CREATE INDEX idx_financial_date ON financial_records(record_date);
CREATE INDEX idx_financial_type ON financial_records(type);

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Inserir fazenda Lagoa do Mato
INSERT INTO farms (id, name, owner_name, city, state, email) VALUES 
('farm-lagoa-mato-001', 'Lagoa do Mato', 'Proprietário da Lagoa do Mato', 'São Paulo', 'SP', 'contato@lagoa.com');

-- Inserir usuário administrador padrão
INSERT INTO users (id, farm_id, name, email, password, role) VALUES 
('user-admin-001', 'farm-lagoa-mato-001', 'Administrador', 'admin@lagoa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'proprietario');

-- Inserir configurações da fazenda
INSERT INTO farm_settings (id, farm_id, farm_name, report_footer_text) VALUES 
('settings-001', 'farm-lagoa-mato-001', 'Lagoa do Mato', 'Sistema de Gestão Leiteira - Fazenda Lagoa do Mato');

-- Inserir alguns animais de exemplo
INSERT INTO animals (id, farm_id, name, identification, birth_date, breed, health_status) VALUES 
('animal-001', 'farm-lagoa-mato-001', 'Vaca 001', 'LDM-001', '2020-03-15', 'Holandesa', 'saudavel'),
('animal-002', 'farm-lagoa-mato-001', 'Vaca 002', 'LDM-002', '2019-11-22', 'Girolanda', 'saudavel'),
('animal-003', 'farm-lagoa-mato-001', 'Novilha 001', 'LDM-003', '2022-05-10', 'Holandesa', 'saudavel'),
('animal-004', 'farm-lagoa-mato-001', 'Bezerra 001', 'LDM-004', '2023-08-15', 'Gir', 'saudavel');

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
FROM volume_records 
GROUP BY production_date 
ORDER BY production_date DESC;

-- View para estatísticas de animais
CREATE VIEW animal_stats AS
SELECT 
    COUNT(*) as total_animals,
    SUM(CASE WHEN health_status = 'saudavel' THEN 1 ELSE 0 END) as healthy_animals,
    SUM(CASE WHEN health_status = 'tratamento' THEN 1 ELSE 0 END) as under_treatment,
    SUM(CASE WHEN health_status = 'doente' THEN 1 ELSE 0 END) as sick_animals
FROM animals 
WHERE is_active = TRUE;

-- =====================================================
-- CONFIRMAÇÃO
-- =====================================================
SELECT 'Banco de dados Lagoa do Mato criado com sucesso!' as status;
SELECT 'Fazenda: Lagoa do Mato' as farm_info;
SELECT 'Usuário admin: admin@lagoa.com / password' as login_info;
SELECT '4 animais de exemplo inseridos' as sample_data;
SELECT 'Tabela volume_records criada para compatibilidade' as compatibility_info;
