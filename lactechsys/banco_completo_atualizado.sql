-- =====================================================
-- LACTECH - BANCO DE DADOS COMPLETO ATUALIZADO
-- =====================================================
-- Execute este arquivo no SQL Editor do Supabase
-- =====================================================

-- Configurações iniciais
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

-- =====================================================
-- EXTENSÕES NECESSÁRIAS
-- =====================================================

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- =====================================================
-- CONFIGURAÇÕES DE STORAGE (PARA FOTOS)
-- =====================================================

-- Bucket para fotos de perfil (se não existir)
INSERT INTO storage.buckets (id, name, public) 
VALUES ('profile-photos', 'profile-photos', true)
ON CONFLICT (id) DO NOTHING;

-- Política para permitir upload de fotos
CREATE POLICY "Users can upload profile photos" ON storage.objects
    FOR INSERT WITH CHECK (
        bucket_id = 'profile-photos' AND 
        auth.uid()::text = (storage.foldername(name))[1]
    );

-- Política para permitir visualização de fotos
CREATE POLICY "Profile photos are publicly accessible" ON storage.objects
    FOR SELECT USING (bucket_id = 'profile-photos');

-- Política para permitir atualização de fotos
CREATE POLICY "Users can update their profile photos" ON storage.objects
    FOR UPDATE USING (
        bucket_id = 'profile-photos' AND 
        auth.uid()::text = (storage.foldername(name))[1]
    );

-- Política para permitir exclusão de fotos
CREATE POLICY "Users can delete their profile photos" ON storage.objects
    FOR DELETE USING (
        bucket_id = 'profile-photos' AND 
        auth.uid()::text = (storage.foldername(name))[1]
    );

-- =====================================================
-- TABELAS PRINCIPAIS
-- =====================================================

-- Tabela de fazendas
CREATE TABLE IF NOT EXISTS farms (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(255) NOT NULL,
    owner_name VARCHAR(255),
    cnpj VARCHAR(18) UNIQUE,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(2),
    zip_code VARCHAR(10),
    phone VARCHAR(20),
    email VARCHAR(255),
    is_setup_complete BOOLEAN DEFAULT false,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de usuários (integrada com Supabase Auth - SEM CONFIRMAÇÃO DE EMAIL)
CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'funcionario' CHECK (role IN ('gerente', 'funcionario', 'veterinario', 'proprietario')),
    whatsapp VARCHAR(20),
    farm_id UUID REFERENCES farms(id) ON DELETE CASCADE,
    profile_photo_url TEXT,
    is_active BOOLEAN DEFAULT true,
    password_hash VARCHAR(255), -- Senha definida pelo gerente (acesso direto)
    report_farm_name VARCHAR(255),
    report_farm_logo_base64 TEXT,
    report_footer_text TEXT,
    report_system_logo_base64 TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de produção de leite
CREATE TABLE IF NOT EXISTS milk_production (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID REFERENCES farms(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    production_date DATE NOT NULL,
    shift VARCHAR(20) CHECK (shift IN ('manhã', 'tarde', 'noite')),
    volume_liters NUMERIC(8,2) NOT NULL,
    temperature NUMERIC(4,1),
    observations TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de testes de qualidade
CREATE TABLE IF NOT EXISTS quality_tests (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID REFERENCES farms(id) ON DELETE CASCADE,
    test_date DATE NOT NULL,
    fat_percentage NUMERIC(4,2),
    protein_percentage NUMERIC(4,2),
    scc INTEGER, -- Contagem de células somáticas
    cbt INTEGER, -- Contagem bacteriana total
    laboratory VARCHAR(255),
    observations TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de vacas
CREATE TABLE IF NOT EXISTS cows (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID REFERENCES farms(id) ON DELETE CASCADE,
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

-- Tabela de inseminações artificiais
CREATE TABLE IF NOT EXISTS artificial_inseminations (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID REFERENCES farms(id) ON DELETE CASCADE,
    cow_id UUID REFERENCES cows(id) ON DELETE CASCADE,
    insemination_date DATE NOT NULL,
    technician_name VARCHAR(255),
    semen_brand VARCHAR(255),
    observations TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de prenhezes
CREATE TABLE IF NOT EXISTS pregnancies (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID REFERENCES farms(id) ON DELETE CASCADE,
    cow_id UUID REFERENCES cows(id) ON DELETE CASCADE,
    confirmation_date DATE NOT NULL,
    expected_calving_date DATE,
    veterinarian_name VARCHAR(255),
    observations TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de registros financeiros
CREATE TABLE IF NOT EXISTS financial_records (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID REFERENCES farms(id) ON DELETE CASCADE,
    record_date DATE NOT NULL,
    type VARCHAR(20) NOT NULL CHECK (type IN ('income', 'expense')),
    amount NUMERIC(10,2) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100),
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'cancelled')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- =====================================================
-- ÍNDICES PARA PERFORMANCE
-- =====================================================

-- Índices para farms
CREATE INDEX IF NOT EXISTS idx_farms_name ON farms(name);
CREATE INDEX IF NOT EXISTS idx_farms_cnpj ON farms(cnpj);

-- Índices para users
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_farm_id ON users(farm_id);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

-- Índices para milk_production
CREATE INDEX IF NOT EXISTS idx_milk_production_farm_id ON milk_production(farm_id);
CREATE INDEX IF NOT EXISTS idx_milk_production_date ON milk_production(production_date);
CREATE INDEX IF NOT EXISTS idx_milk_production_user_id ON milk_production(user_id);

-- Índices para quality_tests
CREATE INDEX IF NOT EXISTS idx_quality_tests_farm_id ON quality_tests(farm_id);
CREATE INDEX IF NOT EXISTS idx_quality_tests_date ON quality_tests(test_date);

-- Índices para cows
CREATE INDEX IF NOT EXISTS idx_cows_farm_id ON cows(farm_id);
CREATE INDEX IF NOT EXISTS idx_cows_identification ON cows(identification);

-- Índices para artificial_inseminations
CREATE INDEX IF NOT EXISTS idx_inseminations_farm_id ON artificial_inseminations(farm_id);
CREATE INDEX IF NOT EXISTS idx_inseminations_cow_id ON artificial_inseminations(cow_id);
CREATE INDEX IF NOT EXISTS idx_inseminations_date ON artificial_inseminations(insemination_date);

-- Índices para pregnancies
CREATE INDEX IF NOT EXISTS idx_pregnancies_farm_id ON pregnancies(farm_id);
CREATE INDEX IF NOT EXISTS idx_pregnancies_cow_id ON pregnancies(cow_id);

-- Índices para financial_records
CREATE INDEX IF NOT EXISTS idx_financial_farm_id ON financial_records(farm_id);
CREATE INDEX IF NOT EXISTS idx_financial_date ON financial_records(record_date);
CREATE INDEX IF NOT EXISTS idx_financial_type ON financial_records(type);

-- =====================================================
-- POLÍTICAS DE SEGURANÇA (RLS)
-- =====================================================

-- Habilitar RLS em todas as tabelas
ALTER TABLE farms ENABLE ROW LEVEL SECURITY;
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE milk_production ENABLE ROW LEVEL SECURITY;
ALTER TABLE quality_tests ENABLE ROW LEVEL SECURITY;
ALTER TABLE cows ENABLE ROW LEVEL SECURITY;
ALTER TABLE artificial_inseminations ENABLE ROW LEVEL SECURITY;
ALTER TABLE pregnancies ENABLE ROW LEVEL SECURITY;
ALTER TABLE financial_records ENABLE ROW LEVEL SECURITY;

-- Políticas para farms
CREATE POLICY "Users can view their own farm" ON farms
    FOR SELECT USING (id IN (
        SELECT farm_id FROM users WHERE id = auth.uid()
    ));

CREATE POLICY "Users can update their own farm" ON farms
    FOR UPDATE USING (id IN (
        SELECT farm_id FROM users WHERE id = auth.uid()
    ));

-- Políticas para users
CREATE POLICY "Users can view users from their farm" ON users
    FOR SELECT USING (farm_id IN (
        SELECT farm_id FROM users WHERE id = auth.uid()
    ));

CREATE POLICY "Users can update their own profile" ON users
    FOR UPDATE USING (id = auth.uid());

-- Políticas para milk_production
CREATE POLICY "Users can manage milk production from their farm" ON milk_production
    FOR ALL USING (farm_id IN (
        SELECT farm_id FROM users WHERE id = auth.uid()
    ));

-- Políticas para quality_tests
CREATE POLICY "Users can manage quality tests from their farm" ON quality_tests
    FOR ALL USING (farm_id IN (
        SELECT farm_id FROM users WHERE id = auth.uid()
    ));

-- Políticas para cows
CREATE POLICY "Users can manage cows from their farm" ON cows
    FOR ALL USING (farm_id IN (
        SELECT farm_id FROM users WHERE id = auth.uid()
    ));

-- Políticas para artificial_inseminations
CREATE POLICY "Users can manage inseminations from their farm" ON artificial_inseminations
    FOR ALL USING (farm_id IN (
        SELECT farm_id FROM users WHERE id = auth.uid()
    ));

-- Políticas para pregnancies
CREATE POLICY "Users can manage pregnancies from their farm" ON pregnancies
    FOR ALL USING (farm_id IN (
        SELECT farm_id FROM users WHERE id = auth.uid()
    ));

-- Políticas para financial_records
CREATE POLICY "Users can manage financial records from their farm" ON financial_records
    FOR ALL USING (farm_id IN (
        SELECT farm_id FROM users WHERE id = auth.uid()
    ));

-- =====================================================
-- FUNÇÕES RPC PERSONALIZADAS
-- =====================================================

-- 1. Verificar se fazenda existe
DROP FUNCTION IF EXISTS check_farm_exists(TEXT, TEXT);
CREATE OR REPLACE FUNCTION check_farm_exists(p_name TEXT, p_cnpj TEXT DEFAULT NULL)
RETURNS BOOLEAN AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1 FROM farms 
        WHERE name = p_name 
        OR (p_cnpj IS NOT NULL AND cnpj = p_cnpj)
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 2. Verificar se usuário existe
DROP FUNCTION IF EXISTS check_user_exists(TEXT);
CREATE OR REPLACE FUNCTION check_user_exists(p_email TEXT)
RETURNS BOOLEAN AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1 FROM users WHERE email = p_email
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 3. Criar fazenda inicial
DROP FUNCTION IF EXISTS create_initial_farm(TEXT, TEXT, TEXT, TEXT, TEXT, TEXT, TEXT, TEXT);
CREATE OR REPLACE FUNCTION create_initial_farm(
    p_name TEXT,
    p_owner_name TEXT,
    p_cnpj TEXT DEFAULT '',
    p_city TEXT DEFAULT '',
    p_state TEXT DEFAULT '',
    p_phone TEXT DEFAULT '',
    p_email TEXT DEFAULT '',
    p_address TEXT DEFAULT ''
)
RETURNS UUID AS $$
DECLARE
    farm_id UUID;
BEGIN
    INSERT INTO farms (
        name, owner_name, cnpj, city, state, phone, email, address
    ) VALUES (
        p_name, p_owner_name, p_cnpj, p_city, p_state, p_phone, p_email, p_address
    ) RETURNING id INTO farm_id;
    
    RETURN farm_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 4. Criar usuário inicial
DROP FUNCTION IF EXISTS create_initial_user(UUID, UUID, TEXT, TEXT, TEXT, TEXT);
CREATE OR REPLACE FUNCTION create_initial_user(
    p_user_id UUID,
    p_farm_id UUID,
    p_name TEXT,
    p_email TEXT,
    p_role TEXT,
    p_whatsapp TEXT DEFAULT ''
)
RETURNS VOID AS $$
BEGIN
    INSERT INTO users (
        id, farm_id, name, email, role, whatsapp
    ) VALUES (
        p_user_id, p_farm_id, p_name, p_email, p_role, p_whatsapp
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 5. Completar configuração da fazenda
DROP FUNCTION IF EXISTS complete_farm_setup(UUID);
CREATE OR REPLACE FUNCTION complete_farm_setup(p_farm_id UUID)
RETURNS VOID AS $$
BEGIN
    UPDATE farms SET is_setup_complete = true WHERE id = p_farm_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 6. Obter perfil do usuário
DROP FUNCTION IF EXISTS get_user_profile();
CREATE OR REPLACE FUNCTION get_user_profile()
RETURNS TABLE (
    user_id UUID,
    user_name TEXT,
    user_email TEXT,
    user_role TEXT,
    farm_id UUID,
    farm_name TEXT,
    is_active BOOLEAN,
    whatsapp TEXT,
    profile_photo_url TEXT,
    report_farm_name TEXT,
    report_farm_logo_base64 TEXT,
    report_footer_text TEXT,
    report_system_logo_base64 TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        u.id as user_id,
        u.name as user_name,
        u.email as user_email,
        u.role as user_role,
        u.farm_id,
        f.name as farm_name,
        u.is_active,
        u.whatsapp,
        u.profile_photo_url,
        u.report_farm_name,
        u.report_farm_logo_base64,
        u.report_footer_text,
        u.report_system_logo_base64
    FROM users u
    LEFT JOIN farms f ON u.farm_id = f.id
    WHERE u.id = auth.uid();
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 7. Registrar teste de qualidade
DROP FUNCTION IF EXISTS register_quality_test(DATE, NUMERIC, NUMERIC, INTEGER, INTEGER, TEXT, TEXT);
CREATE OR REPLACE FUNCTION register_quality_test(
    p_test_date DATE,
    p_fat_percentage NUMERIC DEFAULT NULL,
    p_protein_percentage NUMERIC DEFAULT NULL,
    p_scc INTEGER DEFAULT NULL,
    p_cbt INTEGER DEFAULT NULL,
    p_laboratory TEXT DEFAULT NULL,
    p_observations TEXT DEFAULT NULL
)
RETURNS UUID AS $$
DECLARE
    test_id UUID;
    user_farm_id UUID;
BEGIN
    -- Obter farm_id do usuário autenticado
    SELECT farm_id INTO user_farm_id 
    FROM users 
    WHERE id = auth.uid();
    
    IF user_farm_id IS NULL THEN
        RAISE EXCEPTION 'Usuário não está associado a uma fazenda';
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
        user_farm_id,
        p_test_date,
        p_fat_percentage,
        p_protein_percentage,
        p_scc,
        p_cbt,
        p_laboratory,
        p_observations
    ) RETURNING id INTO test_id;
    
    RETURN test_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 8. Atualizar configurações de relatório do usuário
DROP FUNCTION IF EXISTS update_user_report_settings(TEXT, TEXT, TEXT, TEXT);
CREATE OR REPLACE FUNCTION update_user_report_settings(
    p_report_farm_name TEXT DEFAULT NULL,
    p_report_farm_logo_base64 TEXT DEFAULT NULL,
    p_report_footer_text TEXT DEFAULT NULL,
    p_report_system_logo_base64 TEXT DEFAULT NULL
)
RETURNS VOID AS $$
BEGIN
    UPDATE users 
    SET 
        report_farm_name = COALESCE(p_report_farm_name, report_farm_name),
        report_farm_logo_base64 = COALESCE(p_report_farm_logo_base64, report_farm_logo_base64),
        report_footer_text = COALESCE(p_report_footer_text, report_footer_text),
        report_system_logo_base64 = COALESCE(p_report_system_logo_base64, report_system_logo_base64),
        updated_at = NOW()
    WHERE id = auth.uid();
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 9. Obter estatísticas de produção
DROP FUNCTION IF EXISTS get_production_stats(UUID, DATE, DATE);
CREATE OR REPLACE FUNCTION get_production_stats(
    p_farm_id UUID,
    p_start_date DATE DEFAULT NULL,
    p_end_date DATE DEFAULT NULL
)
RETURNS TABLE (
    total_liters NUMERIC,
    avg_liters_per_day NUMERIC,
    total_days INTEGER,
    max_liters NUMERIC,
    min_liters NUMERIC
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        COALESCE(SUM(volume_liters), 0) as total_liters,
        COALESCE(AVG(volume_liters), 0) as avg_liters_per_day,
        COUNT(DISTINCT production_date) as total_days,
        COALESCE(MAX(volume_liters), 0) as max_liters,
        COALESCE(MIN(volume_liters), 0) as min_liters
    FROM milk_production
    WHERE farm_id = p_farm_id
    AND (p_start_date IS NULL OR production_date >= p_start_date)
    AND (p_end_date IS NULL OR production_date <= p_end_date);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 10. Registrar inseminação artificial
DROP FUNCTION IF EXISTS register_artificial_insemination(UUID, DATE, TEXT, TEXT, TEXT);
CREATE OR REPLACE FUNCTION register_artificial_insemination(
    p_cow_id UUID,
    p_insemination_date DATE,
    p_technician_name TEXT,
    p_semen_brand TEXT DEFAULT NULL,
    p_observations TEXT DEFAULT NULL
)
RETURNS UUID AS $$
DECLARE
    insemination_id UUID;
    user_farm_id UUID;
BEGIN
    -- Obter farm_id do usuário autenticado
    SELECT farm_id INTO user_farm_id 
    FROM users 
    WHERE id = auth.uid();
    
    IF user_farm_id IS NULL THEN
        RAISE EXCEPTION 'Usuário não está associado a uma fazenda';
    END IF;
    
    -- Inserir registro de inseminação
    INSERT INTO artificial_inseminations (
        farm_id,
        cow_id,
        insemination_date,
        technician_name,
        semen_brand,
        observations
    ) VALUES (
        user_farm_id,
        p_cow_id,
        p_insemination_date,
        p_technician_name,
        p_semen_brand,
        p_observations
    ) RETURNING id INTO insemination_id;
    
    RETURN insemination_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 11. Confirmar prenhez
DROP FUNCTION IF EXISTS confirm_pregnancy(UUID, DATE, DATE, TEXT, TEXT);
CREATE OR REPLACE FUNCTION confirm_pregnancy(
    p_cow_id UUID,
    p_confirmation_date DATE,
    p_expected_calving_date DATE,
    p_veterinarian_name TEXT,
    p_observations TEXT DEFAULT NULL
)
RETURNS UUID AS $$
DECLARE
    pregnancy_id UUID;
    user_farm_id UUID;
BEGIN
    -- Obter farm_id do usuário autenticado
    SELECT farm_id INTO user_farm_id 
    FROM users 
    WHERE id = auth.uid();
    
    IF user_farm_id IS NULL THEN
        RAISE EXCEPTION 'Usuário não está associado a uma fazenda';
    END IF;
    
    -- Inserir confirmação de prenhez
    INSERT INTO pregnancies (
        farm_id,
        cow_id,
        confirmation_date,
        expected_calving_date,
        veterinarian_name,
        observations
    ) VALUES (
        user_farm_id,
        p_cow_id,
        p_confirmation_date,
        p_expected_calving_date,
        p_veterinarian_name,
        p_observations
    ) RETURNING id INTO pregnancy_id;
    
    RETURN pregnancy_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- =====================================================
-- TRIGGERS PARA ATUALIZAR updated_at
-- =====================================================

-- Função para atualizar updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Triggers para todas as tabelas
CREATE TRIGGER update_farms_updated_at BEFORE UPDATE ON farms
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_milk_production_updated_at BEFORE UPDATE ON milk_production
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_quality_tests_updated_at BEFORE UPDATE ON quality_tests
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_cows_updated_at BEFORE UPDATE ON cows
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_artificial_inseminations_updated_at BEFORE UPDATE ON artificial_inseminations
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_pregnancies_updated_at BEFORE UPDATE ON pregnancies
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_financial_records_updated_at BEFORE UPDATE ON financial_records
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- =====================================================
-- CONFIRMAÇÃO DE CRIAÇÃO
-- =====================================================
SELECT '✅ Banco de dados LACTECH criado com sucesso!' as status;
SELECT '📊 Tabelas criadas: farms, users, milk_production, quality_tests, cows, artificial_inseminations, pregnancies, financial_records' as info;
SELECT '🔧 Funções RPC criadas: check_farm_exists, check_user_exists, create_initial_farm, create_initial_user, complete_farm_setup, get_user_profile, register_quality_test, update_user_report_settings, get_production_stats, register_artificial_insemination, confirm_pregnancy' as functions;
SELECT '🔒 Políticas de segurança (RLS) configuradas' as security;
SELECT '📈 Índices de performance criados' as performance;
