-- =====================================================
-- SCRIPT COMPLETO PARA RECRIAR O BANCO LACTECH - NOVA CONTA
-- =====================================================
-- URL: https://meczbqmehtolwhactdsv.supabase.co
-- Este script resolve todos os problemas de RLS, funções e estrutura
-- =====================================================

-- 1. HABILITAR EXTENSÕES NECESSÁRIAS
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- 2. LIMPAR BANCO EXISTENTE (CUIDADO!)
-- Remover funções existentes (se existirem)
DROP FUNCTION IF EXISTS update_updated_at_column();
DROP FUNCTION IF EXISTS check_farm_exists(p_name text, p_cnpj text);
DROP FUNCTION IF EXISTS get_user_profile(p_user_id uuid);
DROP FUNCTION IF EXISTS create_farm_with_owner(p_farm_name text, p_farm_cnpj text, p_owner_name text, p_owner_email text, p_owner_password text);
DROP FUNCTION IF EXISTS get_farm_users(p_farm_id uuid);
DROP FUNCTION IF EXISTS get_farm_stats(p_farm_id uuid);
DROP FUNCTION IF EXISTS get_quality_stats(p_farm_id uuid);
DROP FUNCTION IF EXISTS get_volume_stats(p_farm_id uuid);
DROP FUNCTION IF EXISTS check_user_exists(p_email text);
DROP FUNCTION IF EXISTS create_initial_farm(p_name text, p_owner_name text, p_cnpj text, p_city text, p_state text, p_phone text, p_email text, p_address text);
DROP FUNCTION IF EXISTS create_initial_user(p_user_id uuid, p_farm_id uuid, p_name text, p_email text, p_role text, p_whatsapp text);
DROP FUNCTION IF EXISTS complete_farm_setup(p_farm_id uuid);
DROP FUNCTION IF EXISTS register_quality_test(p_test_date date, p_fat_percentage decimal, p_protein_percentage decimal, p_scc integer, p_cbt integer, p_laboratory text, p_observations text);

-- Remover tabelas existentes (se existirem) - ordem correta de dependências
-- (Comentado para banco novo - descomente se necessário)
-- DROP TABLE IF EXISTS pregnancies CASCADE;
-- DROP TABLE IF EXISTS artificial_inseminations CASCADE;
-- DROP TABLE IF EXISTS animal_health_records CASCADE;
-- DROP TABLE IF EXISTS treatments CASCADE;
-- DROP TABLE IF EXISTS animals CASCADE;
-- DROP TABLE IF EXISTS secondary_accounts CASCADE;
-- DROP TABLE IF EXISTS financial_records CASCADE;
-- DROP TABLE IF EXISTS temperature_records CASCADE;
-- DROP TABLE IF EXISTS volume_records CASCADE;
-- DROP TABLE IF EXISTS quality_tests CASCADE;
-- DROP TABLE IF EXISTS users CASCADE;
-- DROP TABLE IF EXISTS farms CASCADE;

-- 3. CRIAR TABELAS PRINCIPAIS

-- Tabela de fazendas
CREATE TABLE farms (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(255) NOT NULL,
    cnpj VARCHAR(18) UNIQUE,
    owner_name VARCHAR(255),
    address TEXT,
    zip_code VARCHAR(10),
    phone VARCHAR(20),
    email VARCHAR(255),
    is_active BOOLEAN DEFAULT true,
    is_setup_complete BOOLEAN DEFAULT false,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de usuários
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'funcionario' CHECK (role IN ('proprietario', 'gerente', 'funcionario', 'veterinario')),
    farm_id UUID REFERENCES farms(id) ON DELETE CASCADE,
    whatsapp VARCHAR(20),
    is_active BOOLEAN DEFAULT true,
    profile_photo_url TEXT,
    password_hash TEXT,
    report_farm_name VARCHAR(255),
    report_farm_logo_base64 TEXT,
    report_footer_text TEXT,
    report_system_logo_base64 TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de animais
CREATE TABLE animals (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    identification VARCHAR(50) NOT NULL,
    name VARCHAR(100),
    breed VARCHAR(100),
    birth_date DATE,
    purchase_date DATE,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'sold', 'deceased')),
    observations TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de testes de qualidade
CREATE TABLE quality_tests (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    test_date DATE NOT NULL,
    fat_percentage DECIMAL(4,2),
    protein_percentage DECIMAL(4,2),
    scc INTEGER, -- Somatic Cell Count
    cbt INTEGER, -- Total Bacterial Count
    laboratory VARCHAR(255),
    observations TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de registros de volume
CREATE TABLE volume_records (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    production_date DATE NOT NULL,
    volume_liters DECIMAL(8,2) NOT NULL,
    milking_type VARCHAR(20) CHECK (milking_type IN ('morning', 'afternoon', 'evening', 'night')),
    temperature DECIMAL(4,2),
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de registros de temperatura
CREATE TABLE temperature_records (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    record_date DATE NOT NULL,
    temperature DECIMAL(4,2),
    humidity DECIMAL(4,2),
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de registros financeiros
CREATE TABLE financial_records (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    record_date DATE NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type VARCHAR(20) CHECK (type IN ('income', 'expense')),
    category VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de tratamentos veterinários
CREATE TABLE treatments (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    animal_id UUID NOT NULL REFERENCES animals(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    treatment_date DATE NOT NULL,
    description TEXT NOT NULL,
    medication VARCHAR(255),
    dosage VARCHAR(100),
    observations TEXT,
    next_treatment_date DATE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de registros de saúde animal
CREATE TABLE animal_health_records (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    animal_id UUID NOT NULL REFERENCES animals(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    record_date DATE NOT NULL,
    health_status VARCHAR(50) NOT NULL CHECK (health_status IN ('saudavel', 'doente', 'tratamento', 'quarentena')),
    weight DECIMAL(6,2),
    temperature DECIMAL(4,1),
    observations TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de inseminações artificiais
CREATE TABLE artificial_inseminations (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    animal_id UUID NOT NULL REFERENCES animals(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    insemination_date DATE NOT NULL,
    semen_batch VARCHAR(100) NOT NULL,
    semen_origin VARCHAR(255),
    bull_identification VARCHAR(100),
    technician_name VARCHAR(255),
    technique_used VARCHAR(50) DEFAULT 'convencional' CHECK (technique_used IN ('convencional', 'timed_ai', 'embryo_transfer')),
    estrus_detection_method VARCHAR(50) CHECK (estrus_detection_method IN ('visual', 'detector', 'hormonal', 'ultrassom')),
    body_condition_score DECIMAL(2,1) CHECK (body_condition_score >= 1.0 AND body_condition_score <= 5.0),
    expected_calving_date DATE GENERATED ALWAYS AS (insemination_date + INTERVAL '280 days') STORED,
    pregnancy_confirmed BOOLEAN DEFAULT NULL,
    pregnancy_confirmation_date DATE,
    pregnancy_confirmation_method VARCHAR(50) CHECK (pregnancy_confirmation_method IN ('palpacao', 'ultrassom', 'exame_sangue')),
    observations TEXT,
    success_rate_notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de prenhezes
CREATE TABLE pregnancies (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    animal_id UUID NOT NULL REFERENCES animals(id) ON DELETE CASCADE,
    confirmation_date DATE NOT NULL,
    expected_calving_date DATE,
    veterinarian_name VARCHAR(255),
    observations TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de contas secundárias
CREATE TABLE secondary_accounts (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    primary_account_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    secondary_account_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(primary_account_id, secondary_account_id)
);

-- 4. CRIAR ÍNDICES PARA PERFORMANCE
CREATE INDEX idx_users_farm_id ON users(farm_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_animals_farm_id ON animals(farm_id);
CREATE INDEX idx_animals_identification ON animals(identification);
CREATE INDEX idx_quality_tests_farm_id ON quality_tests(farm_id);
CREATE INDEX idx_quality_tests_test_date ON quality_tests(test_date);
CREATE INDEX idx_volume_records_farm_id ON volume_records(farm_id);
CREATE INDEX idx_volume_records_production_date ON volume_records(production_date);
CREATE INDEX idx_temperature_records_farm_id ON temperature_records(farm_id);
CREATE INDEX idx_temperature_records_record_date ON temperature_records(record_date);
CREATE INDEX idx_financial_records_farm_id ON financial_records(farm_id);
CREATE INDEX idx_financial_records_record_date ON financial_records(record_date);
CREATE INDEX idx_treatments_farm_id ON treatments(farm_id);
CREATE INDEX idx_treatments_animal_id ON treatments(animal_id);
CREATE INDEX idx_treatments_treatment_date ON treatments(treatment_date);
CREATE INDEX idx_animal_health_records_farm_id ON animal_health_records(farm_id);
CREATE INDEX idx_animal_health_records_animal_id ON animal_health_records(animal_id);
CREATE INDEX idx_animal_health_records_record_date ON animal_health_records(record_date);
CREATE INDEX idx_artificial_inseminations_farm_id ON artificial_inseminations(farm_id);
CREATE INDEX idx_artificial_inseminations_animal_id ON artificial_inseminations(animal_id);
CREATE INDEX idx_artificial_inseminations_insemination_date ON artificial_inseminations(insemination_date);
CREATE INDEX idx_pregnancies_farm_id ON pregnancies(farm_id);
CREATE INDEX idx_pregnancies_animal_id ON pregnancies(animal_id);

-- 5. CRIAR FUNÇÃO PARA ATUALIZAR TIMESTAMP
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- 6. CRIAR TRIGGERS PARA ATUALIZAR TIMESTAMP
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_farms_updated_at BEFORE UPDATE ON farms FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_animals_updated_at BEFORE UPDATE ON animals FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_quality_tests_updated_at BEFORE UPDATE ON quality_tests FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_volume_records_updated_at BEFORE UPDATE ON volume_records FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_temperature_records_updated_at BEFORE UPDATE ON temperature_records FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_financial_records_updated_at BEFORE UPDATE ON financial_records FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_treatments_updated_at BEFORE UPDATE ON treatments FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_animal_health_records_updated_at BEFORE UPDATE ON animal_health_records FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_artificial_inseminations_updated_at BEFORE UPDATE ON artificial_inseminations FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_pregnancies_updated_at BEFORE UPDATE ON pregnancies FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- 7. CRIAR FUNÇÕES RPC

-- Função para verificar se fazenda existe
CREATE OR REPLACE FUNCTION check_farm_exists(p_name text, p_cnpj text)
RETURNS boolean AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1 FROM farms 
        WHERE (name = p_name OR cnpj = p_cnpj) 
        AND is_active = true
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para obter perfil do usuário
CREATE OR REPLACE FUNCTION get_user_profile(p_user_id uuid)
RETURNS TABLE (
    id uuid,
    email varchar,
    name varchar,
    role varchar,
    farm_id uuid,
    whatsapp varchar,
    is_active boolean,
    profile_photo_url text,
    farm_name varchar,
    farm_cnpj varchar
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        u.id,
        u.email,
        u.name,
        u.role,
        u.farm_id,
        u.whatsapp,
        u.is_active,
        u.profile_photo_url,
        f.name as farm_name,
        f.cnpj as farm_cnpj
    FROM users u
    LEFT JOIN farms f ON u.farm_id = f.id
    WHERE u.id = p_user_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para criar fazenda com proprietário
CREATE OR REPLACE FUNCTION create_farm_with_owner(
    p_farm_name text,
    p_farm_cnpj text,
    p_owner_name text,
    p_owner_email text,
    p_owner_password text
)
RETURNS TABLE (
    farm_id uuid,
    user_id uuid,
    success boolean,
    message text
) AS $$
DECLARE
    v_farm_id uuid;
    v_user_id uuid;
BEGIN
    -- Verificar se já existe fazenda com mesmo nome ou CNPJ
    IF check_farm_exists(p_farm_name, p_farm_cnpj) THEN
        RETURN QUERY SELECT NULL::uuid, NULL::uuid, false, 'Fazenda já existe com este nome ou CNPJ';
        RETURN;
    END IF;

    -- Criar fazenda
    INSERT INTO farms (name, cnpj, owner_name, is_active, is_setup_complete)
    VALUES (p_farm_name, p_farm_cnpj, p_owner_name, true, true)
    RETURNING id INTO v_farm_id;

    -- Criar usuário proprietário
    INSERT INTO users (email, name, role, farm_id, is_active)
    VALUES (p_owner_email, p_owner_name, 'proprietario', v_farm_id, true)
    RETURNING id INTO v_user_id;

    RETURN QUERY SELECT v_farm_id, v_user_id, true, 'Fazenda e proprietário criados com sucesso';
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para obter usuários da fazenda
CREATE OR REPLACE FUNCTION get_farm_users(p_farm_id uuid)
RETURNS TABLE (
    id uuid,
    email varchar,
    name varchar,
    role varchar,
    whatsapp varchar,
    is_active boolean,
    profile_photo_url text,
    created_at timestamptz
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        u.id,
        u.email,
        u.name,
        u.role,
        u.whatsapp,
        u.is_active,
        u.profile_photo_url,
        u.created_at
    FROM users u
    WHERE u.farm_id = p_farm_id
    ORDER BY u.created_at DESC;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para obter estatísticas da fazenda
CREATE OR REPLACE FUNCTION get_farm_stats(p_farm_id uuid)
RETURNS TABLE (
    total_users bigint,
    active_users bigint,
    total_volume_records bigint,
    total_quality_tests bigint,
    total_financial_records bigint,
    total_animals bigint,
    total_treatments bigint,
    total_inseminations bigint
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        COUNT(DISTINCT u.id) as total_users,
        COUNT(DISTINCT CASE WHEN u.is_active THEN u.id END) as active_users,
        COUNT(DISTINCT vr.id) as total_volume_records,
        COUNT(DISTINCT qt.id) as total_quality_tests,
        COUNT(DISTINCT fr.id) as total_financial_records,
        COUNT(DISTINCT a.id) as total_animals,
        COUNT(DISTINCT t.id) as total_treatments,
        COUNT(DISTINCT ai.id) as total_inseminations
    FROM farms f
    LEFT JOIN users u ON f.id = u.farm_id
    LEFT JOIN volume_records vr ON f.id = vr.farm_id
    LEFT JOIN quality_tests qt ON f.id = qt.farm_id
    LEFT JOIN financial_records fr ON f.id = fr.farm_id
    LEFT JOIN animals a ON f.id = a.farm_id
    LEFT JOIN treatments t ON f.id = t.farm_id
    LEFT JOIN artificial_inseminations ai ON f.id = ai.farm_id
    WHERE f.id = p_farm_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para obter estatísticas de qualidade
CREATE OR REPLACE FUNCTION get_quality_stats(p_farm_id uuid)
RETURNS TABLE (
    avg_fat decimal,
    avg_protein decimal,
    avg_scc integer,
    avg_cbt integer,
    total_tests bigint
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        AVG(qt.fat_percentage) as avg_fat,
        AVG(qt.protein_percentage) as avg_protein,
        AVG(qt.scc)::integer as avg_scc,
        AVG(qt.cbt)::integer as avg_cbt,
        COUNT(qt.id) as total_tests
    FROM quality_tests qt
    WHERE qt.farm_id = p_farm_id
    AND qt.fat_percentage IS NOT NULL
    AND qt.protein_percentage IS NOT NULL;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para obter estatísticas de volume
CREATE OR REPLACE FUNCTION get_volume_stats(p_farm_id uuid)
RETURNS TABLE (
    total_volume decimal,
    avg_daily_volume decimal,
    total_records bigint,
    last_record_date date
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        SUM(vr.volume_liters) as total_volume,
        AVG(vr.volume_liters) as avg_daily_volume,
        COUNT(vr.id) as total_records,
        MAX(vr.production_date) as last_record_date
    FROM volume_records vr
    WHERE vr.farm_id = p_farm_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para verificar se usuário existe
CREATE OR REPLACE FUNCTION check_user_exists(p_email text)
RETURNS boolean AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1 FROM users 
        WHERE email = p_email 
        AND is_active = true
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para criar fazenda inicial
CREATE OR REPLACE FUNCTION create_initial_farm(
    p_name text,
    p_owner_name text,
    p_cnpj text,
    p_city text,
    p_state text,
    p_phone text,
    p_email text,
    p_address text
)
RETURNS uuid AS $$
DECLARE
    v_farm_id uuid;
BEGIN
    -- Criar fazenda
    INSERT INTO farms (name, cnpj, owner_name, address, phone, email, is_active, is_setup_complete)
    VALUES (p_name, p_cnpj, p_owner_name, p_address, p_phone, p_email, true, true)
    RETURNING id INTO v_farm_id;
    
    RETURN v_farm_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para criar usuário inicial
CREATE OR REPLACE FUNCTION create_initial_user(
    p_user_id uuid,
    p_farm_id uuid,
    p_name text,
    p_email text,
    p_role text,
    p_whatsapp text
)
RETURNS uuid AS $$
DECLARE
    v_user_id uuid;
BEGIN
    -- Criar usuário
    INSERT INTO users (id, email, name, role, farm_id, whatsapp, is_active)
    VALUES (p_user_id, p_email, p_name, p_role, p_farm_id, p_whatsapp, true)
    RETURNING id INTO v_user_id;
    
    RETURN v_user_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para completar configuração da fazenda
CREATE OR REPLACE FUNCTION complete_farm_setup(p_farm_id uuid)
RETURNS void AS $$
BEGIN
    -- Marcar fazenda como configurada
    UPDATE farms 
    SET is_setup_complete = true, updated_at = NOW()
    WHERE id = p_farm_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para registrar teste de qualidade
CREATE OR REPLACE FUNCTION register_quality_test(
    p_test_date date,
    p_fat_percentage decimal,
    p_protein_percentage decimal,
    p_scc integer,
    p_cbt integer,
    p_laboratory text,
    p_observations text
)
RETURNS json AS $$
DECLARE
    v_user_id uuid;
    v_farm_id uuid;
    v_test_id uuid;
BEGIN
    -- Obter usuário atual
    v_user_id := auth.uid();
    IF v_user_id IS NULL THEN
        RETURN json_build_object('success', false, 'error', 'Usuário não autenticado');
    END IF;

    -- Obter farm_id do usuário
    SELECT farm_id INTO v_farm_id
    FROM users
    WHERE id = v_user_id;

    IF v_farm_id IS NULL THEN
        RETURN json_build_object('success', false, 'error', 'Farm não encontrada');
    END IF;

    -- Inserir teste de qualidade
    INSERT INTO quality_tests (
        farm_id,
        test_date,
        fat_percentage,
        protein_percentage,
        scc,
        cbt,
        laboratory,
        observations
    ) VALUES (
        v_farm_id,
        p_test_date,
        p_fat_percentage,
        p_protein_percentage,
        p_scc,
        p_cbt,
        p_laboratory,
        p_observations
    ) RETURNING id INTO v_test_id;

    RETURN json_build_object('success', true, 'test_id', v_test_id);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 8. CONFIGURAR ROW LEVEL SECURITY (RLS)

-- Habilitar RLS em todas as tabelas
ALTER TABLE farms ENABLE ROW LEVEL SECURITY;
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE animals ENABLE ROW LEVEL SECURITY;
ALTER TABLE quality_tests ENABLE ROW LEVEL SECURITY;
ALTER TABLE volume_records ENABLE ROW LEVEL SECURITY;
ALTER TABLE temperature_records ENABLE ROW LEVEL SECURITY;
ALTER TABLE financial_records ENABLE ROW LEVEL SECURITY;
ALTER TABLE treatments ENABLE ROW LEVEL SECURITY;
ALTER TABLE animal_health_records ENABLE ROW LEVEL SECURITY;
ALTER TABLE artificial_inseminations ENABLE ROW LEVEL SECURITY;
ALTER TABLE pregnancies ENABLE ROW LEVEL SECURITY;
ALTER TABLE secondary_accounts ENABLE ROW LEVEL SECURITY;

-- Políticas para tabela farms
CREATE POLICY "Farms are viewable by farm users" ON farms
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = farms.id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Farms are insertable by authenticated users" ON farms
    FOR INSERT WITH CHECK (auth.role() = 'authenticated');

CREATE POLICY "Farms are updatable by farm owners" ON farms
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = farms.id 
            AND users.id = auth.uid()
            AND users.role IN ('proprietario', 'gerente')
        )
    );

-- Políticas para tabela users
CREATE POLICY "Users are viewable by farm users" ON users
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users u2
            WHERE u2.farm_id = users.farm_id 
            AND u2.id = auth.uid()
        )
        OR auth.uid() = users.id
    );

CREATE POLICY "Users are insertable by farm managers" ON users
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM users u2
            WHERE u2.farm_id = users.farm_id 
            AND u2.id = auth.uid()
            AND u2.role IN ('proprietario', 'gerente')
        )
        OR auth.uid() = users.id
    );

CREATE POLICY "Users are updatable by farm managers" ON users
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM users u2
            WHERE u2.farm_id = users.farm_id 
            AND u2.id = auth.uid()
            AND u2.role IN ('proprietario', 'gerente')
        )
        OR auth.uid() = users.id
    );

-- Políticas para tabela animals
CREATE POLICY "Animals are viewable by farm users" ON animals
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = animals.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Animals are insertable by farm users" ON animals
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = animals.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Animals are updatable by farm users" ON animals
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = animals.farm_id 
            AND users.id = auth.uid()
        )
    );

-- Políticas para tabela quality_tests
CREATE POLICY "Quality tests are viewable by farm users" ON quality_tests
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = quality_tests.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Quality tests are insertable by farm users" ON quality_tests
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = quality_tests.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Quality tests are updatable by farm users" ON quality_tests
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = quality_tests.farm_id 
            AND users.id = auth.uid()
        )
    );

-- Políticas para tabela volume_records
CREATE POLICY "Volume records are viewable by farm users" ON volume_records
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = volume_records.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Volume records are insertable by farm users" ON volume_records
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = volume_records.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Volume records are updatable by farm users" ON volume_records
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = volume_records.farm_id 
            AND users.id = auth.uid()
        )
    );

-- Políticas para tabela temperature_records
CREATE POLICY "Temperature records are viewable by farm users" ON temperature_records
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = temperature_records.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Temperature records are insertable by farm users" ON temperature_records
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = temperature_records.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Temperature records are updatable by farm users" ON temperature_records
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = temperature_records.farm_id 
            AND users.id = auth.uid()
        )
    );

-- Políticas para tabela financial_records
CREATE POLICY "Financial records are viewable by farm managers" ON financial_records
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = financial_records.farm_id 
            AND users.id = auth.uid()
            AND users.role IN ('proprietario', 'gerente')
        )
    );

CREATE POLICY "Financial records are insertable by farm managers" ON financial_records
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = financial_records.farm_id 
            AND users.id = auth.uid()
            AND users.role IN ('proprietario', 'gerente')
        )
    );

CREATE POLICY "Financial records are updatable by farm managers" ON financial_records
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = financial_records.farm_id 
            AND users.id = auth.uid()
            AND users.role IN ('proprietario', 'gerente')
        )
    );

-- Políticas para tabela treatments
CREATE POLICY "Treatments are viewable by farm users" ON treatments
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = treatments.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Treatments are insertable by farm users" ON treatments
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = treatments.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Treatments are updatable by farm users" ON treatments
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = treatments.farm_id 
            AND users.id = auth.uid()
        )
    );

-- Políticas para tabela animal_health_records
CREATE POLICY "Animal health records are viewable by farm users" ON animal_health_records
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = animal_health_records.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Animal health records are insertable by farm users" ON animal_health_records
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = animal_health_records.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Animal health records are updatable by farm users" ON animal_health_records
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = animal_health_records.farm_id 
            AND users.id = auth.uid()
        )
    );

-- Políticas para tabela artificial_inseminations
CREATE POLICY "Artificial inseminations are viewable by farm users" ON artificial_inseminations
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = artificial_inseminations.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Artificial inseminations are insertable by farm users" ON artificial_inseminations
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = artificial_inseminations.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Artificial inseminations are updatable by farm users" ON artificial_inseminations
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = artificial_inseminations.farm_id 
            AND users.id = auth.uid()
        )
    );

-- Políticas para tabela pregnancies
CREATE POLICY "Pregnancies are viewable by farm users" ON pregnancies
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = pregnancies.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Pregnancies are insertable by farm users" ON pregnancies
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = pregnancies.farm_id 
            AND users.id = auth.uid()
        )
    );

CREATE POLICY "Pregnancies are updatable by farm users" ON pregnancies
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE users.farm_id = pregnancies.farm_id 
            AND users.id = auth.uid()
        )
    );

-- Políticas para tabela secondary_accounts
CREATE POLICY "Secondary accounts are viewable by primary account" ON secondary_accounts
    FOR SELECT USING (
        primary_account_id = auth.uid() OR secondary_account_id = auth.uid()
    );

CREATE POLICY "Secondary accounts are insertable by primary account" ON secondary_accounts
    FOR INSERT WITH CHECK (primary_account_id = auth.uid());

CREATE POLICY "Secondary accounts are updatable by primary account" ON secondary_accounts
    FOR UPDATE USING (primary_account_id = auth.uid());

-- 9. CONFIGURAR STORAGE PARA FOTOS DE PERFIL
INSERT INTO storage.buckets (id, name, public) VALUES ('profile-photos', 'profile-photos', true);

CREATE POLICY "Profile photos are publicly accessible" ON storage.objects
    FOR SELECT USING (bucket_id = 'profile-photos');

CREATE POLICY "Users can upload profile photos" ON storage.objects
    FOR INSERT WITH CHECK (
        bucket_id = 'profile-photos' 
        AND auth.role() = 'authenticated'
    );

CREATE POLICY "Users can update their own profile photos" ON storage.objects
    FOR UPDATE USING (
        bucket_id = 'profile-photos' 
        AND auth.uid()::text = (storage.foldername(name))[1]
    );

-- 10. MENSAGEM DE CONFIRMAÇÃO
DO $$
BEGIN
    RAISE NOTICE '✅ Banco de dados LacTech criado com sucesso na nova conta!';
    RAISE NOTICE '🔗 URL: https://meczbqmehtolwhactdsv.supabase.co';
    RAISE NOTICE '📊 Tabelas criadas: farms, users, animals, quality_tests, volume_records, temperature_records, financial_records, treatments, animal_health_records, artificial_inseminations, pregnancies, secondary_accounts';
    RAISE NOTICE '🔧 Funções RPC criadas: check_farm_exists, get_user_profile, create_farm_with_owner, get_farm_users, get_farm_stats, get_quality_stats, get_volume_stats, check_user_exists, create_initial_farm, create_initial_user, complete_farm_setup, register_quality_test';
    RAISE NOTICE '🔒 RLS configurado para todas as tabelas';
    RAISE NOTICE '📁 Storage configurado para fotos de perfil';
    RAISE NOTICE '🎯 Sistema pronto para uso!';
    RAISE NOTICE '📋 Páginas suportadas: PrimeiroAcesso, acesso-bloqueado, login, gerente, funcionario, veterinario, proprietario';
END $$;
