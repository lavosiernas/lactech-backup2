-- =====================================================
-- LACTECH - SISTEMA DE GESTÃO LEITEIRA
-- SCHEMA COMPLETO DO BANCO DE DADOS
-- =====================================================
-- 
-- CONFIGURAÇÕES DO SUPABASE:
-- URL: https://igpjdudmgvaecvszcess.supabase.co
-- ANON KEY: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImlncGpkdWRtZ3ZhZWN2c3pjZXNzIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTU5OTY4NTQsImV4cCI6MjA3MTU3Mjg1NH0.Sufxzx9XGpZ3PAv7bG50Qhc14W2eugKJjiqmdZymdgk
-- 
-- IMPORTANTE: 
-- - NÃO HÁ CONFIRMAÇÃO DE EMAIL (desabilitado nas configurações do Supabase)
-- - AUTENTICAÇÃO DIRETA com email/senha
-- - USUÁRIOS CRIADOS PELO GERENTE com acesso imediato
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
    cnpj VARCHAR(18) UNIQUE,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(2),
    zip_code VARCHAR(10),
    phone VARCHAR(20),
    email VARCHAR(255),
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

-- Tabela de contas secundárias
CREATE TABLE IF NOT EXISTS secondary_accounts (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    primary_account_id UUID REFERENCES users(id) ON DELETE CASCADE,
    secondary_account_id UUID REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(primary_account_id, secondary_account_id)
);

-- Tabela de produção de leite
CREATE TABLE IF NOT EXISTS milk_production (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID REFERENCES farms(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    production_date DATE NOT NULL,
    shift VARCHAR(20) NOT NULL CHECK (shift IN ('manha', 'tarde', 'noite')),
    volume_liters DECIMAL(8,2) NOT NULL,
    temperature DECIMAL(4,2),
    observations TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de testes de qualidade
CREATE TABLE IF NOT EXISTS quality_tests (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID REFERENCES farms(id) ON DELETE CASCADE,
    test_date DATE NOT NULL,
    fat_percentage DECIMAL(4,2),
    protein_percentage DECIMAL(4,2),
    scc INTEGER, -- Contagem de Células Somáticas
    cbt INTEGER, -- Contagem Bacteriana Total
    laboratory VARCHAR(255),
    observations TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela de registros financeiros
CREATE TABLE IF NOT EXISTS financial_records (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID REFERENCES farms(id) ON DELETE CASCADE,
    record_date DATE NOT NULL,
    type VARCHAR(50) NOT NULL CHECK (type IN ('receita', 'despesa')),
    amount DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100),
    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'overdue')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- =====================================================
-- TABELAS ALTERNATIVAS (USADAS NO CÓDIGO)
-- =====================================================

-- Tabela alternativa de usuários (lactech_users)
CREATE TABLE IF NOT EXISTS lactech_users (
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

-- Tabela alternativa de produção (lactech_production)
CREATE TABLE IF NOT EXISTS lactech_production (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID REFERENCES farms(id) ON DELETE CASCADE,
    user_id UUID REFERENCES lactech_users(id) ON DELETE SET NULL,
    production_date DATE NOT NULL,
    shift VARCHAR(20) NOT NULL CHECK (shift IN ('manha', 'tarde', 'noite')),
    volume_liters DECIMAL(8,2) NOT NULL,
    temperature DECIMAL(4,2),
    observations TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela alternativa de qualidade (lactech_quality)
CREATE TABLE IF NOT EXISTS lactech_quality (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID REFERENCES farms(id) ON DELETE CASCADE,
    test_date DATE NOT NULL,
    fat_percentage DECIMAL(4,2),
    protein_percentage DECIMAL(4,2),
    scc INTEGER,
    cbt INTEGER,
    laboratory VARCHAR(255),
    observations TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabela alternativa de financeiro (lactech_financial)
CREATE TABLE IF NOT EXISTS lactech_financial (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    farm_id UUID REFERENCES farms(id) ON DELETE CASCADE,
    record_date DATE NOT NULL,
    type VARCHAR(50) NOT NULL CHECK (type IN ('receita', 'despesa')),
    amount DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100),
    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'overdue')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- =====================================================
-- ÍNDICES PARA PERFORMANCE
-- =====================================================

-- Índices para users
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_farm_id ON users(farm_id);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_is_active ON users(is_active);

-- Índices para lactech_users
CREATE INDEX IF NOT EXISTS idx_lactech_users_email ON lactech_users(email);
CREATE INDEX IF NOT EXISTS idx_lactech_users_farm_id ON lactech_users(farm_id);
CREATE INDEX IF NOT EXISTS idx_lactech_users_role ON lactech_users(role);
CREATE INDEX IF NOT EXISTS idx_lactech_users_is_active ON lactech_users(is_active);

-- Índices para milk_production
CREATE INDEX IF NOT EXISTS idx_milk_production_farm_id ON milk_production(farm_id);
CREATE INDEX IF NOT EXISTS idx_milk_production_user_id ON milk_production(user_id);
CREATE INDEX IF NOT EXISTS idx_milk_production_date ON milk_production(production_date);
CREATE INDEX IF NOT EXISTS idx_milk_production_shift ON milk_production(shift);
CREATE INDEX IF NOT EXISTS idx_milk_production_farm_date ON milk_production(farm_id, production_date);

-- Índices para lactech_production
CREATE INDEX IF NOT EXISTS idx_lactech_production_farm_id ON lactech_production(farm_id);
CREATE INDEX IF NOT EXISTS idx_lactech_production_user_id ON lactech_production(user_id);
CREATE INDEX IF NOT EXISTS idx_lactech_production_date ON lactech_production(production_date);
CREATE INDEX IF NOT EXISTS idx_lactech_production_shift ON lactech_production(shift);
CREATE INDEX IF NOT EXISTS idx_lactech_production_farm_date ON lactech_production(farm_id, production_date);

-- Índices para quality_tests
CREATE INDEX IF NOT EXISTS idx_quality_tests_farm_id ON quality_tests(farm_id);
CREATE INDEX IF NOT EXISTS idx_quality_tests_date ON quality_tests(test_date);
CREATE INDEX IF NOT EXISTS idx_quality_tests_farm_date ON quality_tests(farm_id, test_date);

-- Índices para lactech_quality
CREATE INDEX IF NOT EXISTS idx_lactech_quality_farm_id ON lactech_quality(farm_id);
CREATE INDEX IF NOT EXISTS idx_lactech_quality_date ON lactech_quality(test_date);
CREATE INDEX IF NOT EXISTS idx_lactech_quality_farm_date ON lactech_quality(farm_id, test_date);

-- Índices para financial_records
CREATE INDEX IF NOT EXISTS idx_financial_records_farm_id ON financial_records(farm_id);
CREATE INDEX IF NOT EXISTS idx_financial_records_date ON financial_records(record_date);
CREATE INDEX IF NOT EXISTS idx_financial_records_type ON financial_records(type);
CREATE INDEX IF NOT EXISTS idx_financial_records_status ON financial_records(status);

-- Índices para lactech_financial
CREATE INDEX IF NOT EXISTS idx_lactech_financial_farm_id ON lactech_financial(farm_id);
CREATE INDEX IF NOT EXISTS idx_lactech_financial_date ON lactech_financial(record_date);
CREATE INDEX IF NOT EXISTS idx_lactech_financial_type ON lactech_financial(type);
CREATE INDEX IF NOT EXISTS idx_lactech_financial_status ON lactech_financial(status);

-- Índices para secondary_accounts
CREATE INDEX IF NOT EXISTS idx_secondary_accounts_primary ON secondary_accounts(primary_account_id);
CREATE INDEX IF NOT EXISTS idx_secondary_accounts_secondary ON secondary_accounts(secondary_account_id);

-- =====================================================
-- FUNÇÕES E PROCEDURES
-- =====================================================

-- Função para atualizar timestamp de updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Função para criar usuário (FLUXO CORRETO - SEM CONFIRMAÇÃO DE EMAIL)
CREATE OR REPLACE FUNCTION create_user(
    p_name VARCHAR(255),
    p_role VARCHAR(50),
    p_whatsapp VARCHAR(20),
    p_password VARCHAR(255),
    p_profile_photo_url TEXT DEFAULT NULL
)
RETURNS JSON AS $$
DECLARE
    v_user_id UUID;
    v_farm_id UUID;
    v_email VARCHAR(255);
    v_first_name VARCHAR(255);
    v_random_suffix VARCHAR(10);
    v_result JSON;
BEGIN
    -- Verificar se o usuário atual é gerente
    IF NOT EXISTS (
        SELECT 1 FROM users 
        WHERE id = auth.uid() 
        AND role = 'gerente' 
        AND is_active = true
    ) THEN
        RETURN json_build_object('success', false, 'error', 'Apenas gerentes podem criar usuários');
    END IF;

    -- Obter farm_id do gerente
    SELECT farm_id INTO v_farm_id
    FROM users
    WHERE id = auth.uid();

    IF v_farm_id IS NULL THEN
        RETURN json_build_object('success', false, 'error', 'Farm not found');
    END IF;

    -- Gerar email automaticamente baseado no nome
    v_first_name := split_part(p_name, ' ', 1);
    v_random_suffix := floor(random() * 900 + 100)::text;
    v_email := lower(v_first_name) || v_random_suffix || '@lactech.com';

    -- Verificar se foto é permitida para o papel
    IF p_role = 'veterinario' AND p_profile_photo_url IS NOT NULL THEN
        RETURN json_build_object('success', false, 'error', 'Veterinários não podem ter foto de perfil');
    END IF;

    -- Criar usuário (ACESSO DIRETO - SEM CONFIRMAÇÃO DE EMAIL)
    INSERT INTO users (
        email,
        name,
        role,
        whatsapp,
        farm_id,
        profile_photo_url,
        password_hash
    ) VALUES (
        v_email,
        p_name,
        p_role,
        p_whatsapp,
        v_farm_id,
        CASE 
            WHEN p_role = 'funcionario' THEN p_profile_photo_url
            ELSE NULL
        END,
        crypt(p_password, gen_salt('bf'))
    ) RETURNING id INTO v_user_id;

    RETURN json_build_object(
        'success', true, 
        'message', 'Usuário criado com sucesso - Acesso direto disponível',
        'user_id', v_user_id,
        'email', v_email,
        'name', p_name,
        'role', p_role,
        'note', 'Usuário pode fazer login imediatamente (sem confirmação de email)'
    );

EXCEPTION WHEN OTHERS THEN
    RETURN json_build_object('success', false, 'error', SQLERRM);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para registrar teste de qualidade
CREATE OR REPLACE FUNCTION register_quality_test(
    p_test_date DATE,
    p_fat_percentage DECIMAL(4,2),
    p_protein_percentage DECIMAL(4,2),
    p_scc INTEGER,
    p_cbt INTEGER,
    p_laboratory VARCHAR(255),
    p_observations TEXT
)
RETURNS JSON AS $$
DECLARE
    v_user_id UUID;
    v_farm_id UUID;
    v_result JSON;
BEGIN
    -- Obter usuário atual
    v_user_id := auth.uid();
    
    -- Obter farm_id do usuário
    SELECT farm_id INTO v_farm_id
    FROM users
    WHERE id = v_user_id;
    
    IF v_farm_id IS NULL THEN
        RETURN json_build_object('success', false, 'error', 'Farm not found');
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
    );
    
    RETURN json_build_object('success', true, 'message', 'Quality test registered successfully');
    
EXCEPTION WHEN OTHERS THEN
    RETURN json_build_object('success', false, 'error', SQLERRM);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para obter perfil do usuário
CREATE OR REPLACE FUNCTION get_user_profile()
RETURNS TABLE (
    id UUID,
    name VARCHAR(255),
    email VARCHAR(255),
    role VARCHAR(50),
    whatsapp VARCHAR(20),
    farm_id UUID,
    farm_name VARCHAR(255),
    profile_photo_url TEXT,
    is_active BOOLEAN,
    report_farm_name VARCHAR(255),
    report_farm_logo_base64 TEXT,
    report_footer_text TEXT,
    report_system_logo_base64 TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        u.id,
        u.name,
        u.email,
        u.role,
        u.whatsapp,
        u.farm_id,
        f.name as farm_name,
        u.profile_photo_url,
        u.is_active,
        u.report_farm_name,
        u.report_farm_logo_base64,
        u.report_footer_text,
        u.report_system_logo_base64
    FROM users u
    LEFT JOIN farms f ON u.farm_id = f.id
    WHERE u.id = auth.uid();
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para atualizar configurações de relatório
CREATE OR REPLACE FUNCTION update_user_report_settings(
    p_report_farm_name VARCHAR(255),
    p_report_farm_logo_base64 TEXT,
    p_report_footer_text TEXT,
    p_report_system_logo_base64 TEXT
)
RETURNS JSON AS $$
DECLARE
    v_user_id UUID;
BEGIN
    v_user_id := auth.uid();
    
    UPDATE users SET
        report_farm_name = p_report_farm_name,
        report_farm_logo_base64 = p_report_farm_logo_base64,
        report_footer_text = p_report_footer_text,
        report_system_logo_base64 = p_report_system_logo_base64,
        updated_at = NOW()
    WHERE id = v_user_id;
    
    IF FOUND THEN
        RETURN json_build_object('success', true, 'message', 'Settings updated successfully');
    ELSE
        RETURN json_build_object('success', false, 'error', 'User not found');
    END IF;
    
EXCEPTION WHEN OTHERS THEN
    RETURN json_build_object('success', false, 'error', SQLERRM);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para gerar estatísticas de produção
CREATE OR REPLACE FUNCTION get_production_stats(
    p_farm_id UUID,
    p_start_date DATE,
    p_end_date DATE
)
RETURNS TABLE (
    total_volume DECIMAL(10,2),
    avg_volume DECIMAL(8,2),
    total_records INTEGER,
    days_with_production INTEGER,
    avg_daily_volume DECIMAL(8,2)
) AS $$
BEGIN
    RETURN QUERY
    WITH daily_totals AS (
        SELECT 
            production_date,
            SUM(volume_liters) as daily_volume,
            COUNT(*) as daily_records
        FROM milk_production
        WHERE farm_id = p_farm_id
        AND production_date BETWEEN p_start_date AND p_end_date
        GROUP BY production_date
    )
    SELECT 
        COALESCE(SUM(daily_volume), 0) as total_volume,
        COALESCE(AVG(daily_volume), 0) as avg_volume,
        COALESCE(SUM(daily_records), 0) as total_records,
        COUNT(*) as days_with_production,
        CASE 
            WHEN COUNT(*) > 0 THEN COALESCE(SUM(daily_volume), 0) / COUNT(*)
            ELSE 0
        END as avg_daily_volume
    FROM daily_totals;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para obter relatório de produção por funcionário
CREATE OR REPLACE FUNCTION get_employee_production_report(
    p_farm_id UUID,
    p_start_date DATE,
    p_end_date DATE,
    p_employee_id UUID DEFAULT NULL
)
RETURNS TABLE (
    employee_name VARCHAR(255),
    total_volume DECIMAL(10,2),
    avg_volume DECIMAL(8,2),
    total_records INTEGER,
    morning_volume DECIMAL(10,2),
    afternoon_volume DECIMAL(10,2),
    night_volume DECIMAL(10,2)
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        u.name as employee_name,
        COALESCE(SUM(mp.volume_liters), 0) as total_volume,
        COALESCE(AVG(mp.volume_liters), 0) as avg_volume,
        COUNT(*) as total_records,
        COALESCE(SUM(CASE WHEN mp.shift = 'manha' THEN mp.volume_liters ELSE 0 END), 0) as morning_volume,
        COALESCE(SUM(CASE WHEN mp.shift = 'tarde' THEN mp.volume_liters ELSE 0 END), 0) as afternoon_volume,
        COALESCE(SUM(CASE WHEN mp.shift = 'noite' THEN mp.volume_liters ELSE 0 END), 0) as night_volume
    FROM users u
    LEFT JOIN milk_production mp ON u.id = mp.user_id 
        AND mp.farm_id = p_farm_id
        AND mp.production_date BETWEEN p_start_date AND p_end_date
    WHERE u.farm_id = p_farm_id
    AND u.role = 'funcionario'
    AND (p_employee_id IS NULL OR u.id = p_employee_id)
    GROUP BY u.id, u.name
    ORDER BY total_volume DESC;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para gerar email baseado no nome
CREATE OR REPLACE FUNCTION generate_email_from_name(p_name VARCHAR(255))
RETURNS VARCHAR(255) AS $$
DECLARE
    v_first_name VARCHAR(255);
    v_random_suffix VARCHAR(10);
    v_email VARCHAR(255);
BEGIN
    -- Extrair primeiro nome
    v_first_name := split_part(p_name, ' ', 1);
    
    -- Gerar sufixo aleatório
    v_random_suffix := floor(random() * 900 + 100)::text;
    
    -- Gerar email
    v_email := lower(v_first_name) || v_random_suffix || '@lactech.com';
    
    RETURN v_email;
END;
$$ LANGUAGE plpgsql;

-- Função para obter nome da fazenda
CREATE OR REPLACE FUNCTION get_farm_name()
RETURNS VARCHAR(255) AS $$
DECLARE
    v_farm_name VARCHAR(255);
BEGIN
    SELECT f.name INTO v_farm_name
    FROM farms f
    JOIN users u ON f.id = u.farm_id
    WHERE u.id = auth.uid();
    
    RETURN COALESCE(v_farm_name, 'Minha Fazenda');
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para obter nome do gerente
CREATE OR REPLACE FUNCTION get_manager_name()
RETURNS VARCHAR(255) AS $$
DECLARE
    v_manager_name VARCHAR(255);
BEGIN
    SELECT name INTO v_manager_name
    FROM users
    WHERE id = auth.uid();
    
    RETURN COALESCE(v_manager_name, 'Gerente');
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função para extrair nome formal
CREATE OR REPLACE FUNCTION extract_formal_name(p_full_name VARCHAR(255))
RETURNS VARCHAR(255) AS $$
DECLARE
    v_first_name VARCHAR(255);
BEGIN
    v_first_name := split_part(p_full_name, ' ', 1);
    RETURN v_first_name;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Triggers para atualizar updated_at automaticamente
CREATE TRIGGER update_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_lactech_users_updated_at
    BEFORE UPDATE ON lactech_users
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_milk_production_updated_at
    BEFORE UPDATE ON milk_production
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_lactech_production_updated_at
    BEFORE UPDATE ON lactech_production
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_quality_tests_updated_at
    BEFORE UPDATE ON quality_tests
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_lactech_quality_updated_at
    BEFORE UPDATE ON lactech_quality
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_financial_records_updated_at
    BEFORE UPDATE ON financial_records
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_lactech_financial_updated_at
    BEFORE UPDATE ON lactech_financial
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- =====================================================
-- POLÍTICAS DE SEGURANÇA (RLS - Row Level Security)
-- =====================================================

-- Habilitar RLS em todas as tabelas
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE lactech_users ENABLE ROW LEVEL SECURITY;
ALTER TABLE farms ENABLE ROW LEVEL SECURITY;
ALTER TABLE milk_production ENABLE ROW LEVEL SECURITY;
ALTER TABLE lactech_production ENABLE ROW LEVEL SECURITY;
ALTER TABLE quality_tests ENABLE ROW LEVEL SECURITY;
ALTER TABLE lactech_quality ENABLE ROW LEVEL SECURITY;
ALTER TABLE financial_records ENABLE ROW LEVEL SECURITY;
ALTER TABLE lactech_financial ENABLE ROW LEVEL SECURITY;
ALTER TABLE secondary_accounts ENABLE ROW LEVEL SECURITY;

-- =====================================================
-- POLÍTICAS RLS ROBUSTAS E SEGURAS
-- =====================================================

-- Políticas para users (PRINCIPAIS)
DROP POLICY IF EXISTS "Users can view their own farm users" ON users;
CREATE POLICY "Users can view their own farm users" ON users
    FOR SELECT USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can update their own profile" ON users;
CREATE POLICY "Users can update their own profile" ON users
    FOR UPDATE USING (id = auth.uid());

DROP POLICY IF EXISTS "Managers can insert users" ON users;
CREATE POLICY "Managers can insert users" ON users
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM users 
            WHERE id = auth.uid() 
            AND role = 'gerente' 
            AND farm_id = users.farm_id
            AND is_active = true
        )
    );

DROP POLICY IF EXISTS "Managers can delete users" ON users;
CREATE POLICY "Managers can delete users" ON users
    FOR DELETE USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE id = auth.uid() 
            AND role = 'gerente' 
            AND farm_id = users.farm_id
            AND is_active = true
        )
        AND id != auth.uid() -- Não pode deletar a si mesmo
    );

-- Políticas para lactech_users (ALTERNATIVAS)
DROP POLICY IF EXISTS "Users can view their own farm users" ON lactech_users;
CREATE POLICY "Users can view their own farm users" ON lactech_users
    FOR SELECT USING (
        farm_id IN (
            SELECT farm_id FROM lactech_users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can update their own profile" ON lactech_users;
CREATE POLICY "Users can update their own profile" ON lactech_users
    FOR UPDATE USING (id = auth.uid());

DROP POLICY IF EXISTS "Managers can insert users" ON lactech_users;
CREATE POLICY "Managers can insert users" ON lactech_users
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM lactech_users 
            WHERE id = auth.uid() 
            AND role = 'gerente' 
            AND farm_id = lactech_users.farm_id
            AND is_active = true
        )
    );

-- Políticas para farms
DROP POLICY IF EXISTS "Users can view their own farm" ON farms;
CREATE POLICY "Users can view their own farm" ON farms
    FOR SELECT USING (
        id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can update their own farm" ON farms;
CREATE POLICY "Users can update their own farm" ON farms
    FOR UPDATE USING (
        id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

-- Políticas para milk_production (PRINCIPAIS)
DROP POLICY IF EXISTS "Users can view their farm production" ON milk_production;
CREATE POLICY "Users can view their farm production" ON milk_production
    FOR SELECT USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can insert production records" ON milk_production;
CREATE POLICY "Users can insert production records" ON milk_production
    FOR INSERT WITH CHECK (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
        AND user_id = auth.uid()
    );

DROP POLICY IF EXISTS "Users can update their own production records" ON milk_production;
CREATE POLICY "Users can update their own production records" ON milk_production
    FOR UPDATE USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
        AND user_id = auth.uid()
    );

DROP POLICY IF EXISTS "Users can delete their own production records" ON milk_production;
CREATE POLICY "Users can delete their own production records" ON milk_production
    FOR DELETE USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
        AND user_id = auth.uid()
    );

-- Políticas para lactech_production (ALTERNATIVAS)
DROP POLICY IF EXISTS "Users can view their farm production" ON lactech_production;
CREATE POLICY "Users can view their farm production" ON lactech_production
    FOR SELECT USING (
        farm_id IN (
            SELECT farm_id FROM lactech_users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can insert production records" ON lactech_production;
CREATE POLICY "Users can insert production records" ON lactech_production
    FOR INSERT WITH CHECK (
        farm_id IN (
            SELECT farm_id FROM lactech_users WHERE id = auth.uid()
        )
        AND user_id = auth.uid()
    );

DROP POLICY IF EXISTS "Users can update their own production records" ON lactech_production;
CREATE POLICY "Users can update their own production records" ON lactech_production
    FOR UPDATE USING (
        farm_id IN (
            SELECT farm_id FROM lactech_users WHERE id = auth.uid()
        )
        AND user_id = auth.uid()
    );

DROP POLICY IF EXISTS "Users can delete their own production records" ON lactech_production;
CREATE POLICY "Users can delete their own production records" ON lactech_production
    FOR DELETE USING (
        farm_id IN (
            SELECT farm_id FROM lactech_users WHERE id = auth.uid()
        )
        AND user_id = auth.uid()
    );

-- Políticas para quality_tests (PRINCIPAIS)
DROP POLICY IF EXISTS "Users can view their farm quality tests" ON quality_tests;
CREATE POLICY "Users can view their farm quality tests" ON quality_tests
    FOR SELECT USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can insert quality tests" ON quality_tests;
CREATE POLICY "Users can insert quality tests" ON quality_tests
    FOR INSERT WITH CHECK (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can update quality tests" ON quality_tests;
CREATE POLICY "Users can update quality tests" ON quality_tests
    FOR UPDATE USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can delete quality tests" ON quality_tests;
CREATE POLICY "Users can delete quality tests" ON quality_tests
    FOR DELETE USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

-- Políticas para lactech_quality (ALTERNATIVAS)
DROP POLICY IF EXISTS "Users can view their farm quality tests" ON lactech_quality;
CREATE POLICY "Users can view their farm quality tests" ON lactech_quality
    FOR SELECT USING (
        farm_id IN (
            SELECT farm_id FROM lactech_users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can insert quality tests" ON lactech_quality;
CREATE POLICY "Users can insert quality tests" ON lactech_quality
    FOR INSERT WITH CHECK (
        farm_id IN (
            SELECT farm_id FROM lactech_users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can update quality tests" ON lactech_quality;
CREATE POLICY "Users can update quality tests" ON lactech_quality
    FOR UPDATE USING (
        farm_id IN (
            SELECT farm_id FROM lactech_users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can delete quality tests" ON lactech_quality;
CREATE POLICY "Users can delete quality tests" ON lactech_quality
    FOR DELETE USING (
        farm_id IN (
            SELECT farm_id FROM lactech_users WHERE id = auth.uid()
        )
    );

-- Políticas para financial_records (PRINCIPAIS)
DROP POLICY IF EXISTS "Users can view their farm financial records" ON financial_records;
CREATE POLICY "Users can view their farm financial records" ON financial_records
    FOR SELECT USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can insert financial records" ON financial_records;
CREATE POLICY "Users can insert financial records" ON financial_records
    FOR INSERT WITH CHECK (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can update financial records" ON financial_records;
CREATE POLICY "Users can update financial records" ON financial_records
    FOR UPDATE USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can delete financial records" ON financial_records;
CREATE POLICY "Users can delete financial records" ON financial_records
    FOR DELETE USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

-- Políticas para lactech_financial (ALTERNATIVAS)
DROP POLICY IF EXISTS "Users can view their farm financial records" ON lactech_financial;
CREATE POLICY "Users can view their farm financial records" ON lactech_financial
    FOR SELECT USING (
        farm_id IN (
            SELECT farm_id FROM lactech_users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can insert financial records" ON lactech_financial;
CREATE POLICY "Users can insert financial records" ON lactech_financial
    FOR INSERT WITH CHECK (
        farm_id IN (
            SELECT farm_id FROM lactech_users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can update financial records" ON lactech_financial;
CREATE POLICY "Users can update financial records" ON lactech_financial
    FOR UPDATE USING (
        farm_id IN (
            SELECT farm_id FROM lactech_users WHERE id = auth.uid()
        )
    );

DROP POLICY IF EXISTS "Users can delete financial records" ON lactech_financial;
CREATE POLICY "Users can delete financial records" ON lactech_financial
    FOR DELETE USING (
        farm_id IN (
            SELECT farm_id FROM lactech_users WHERE id = auth.uid()
        )
    );

-- Políticas para secondary_accounts
DROP POLICY IF EXISTS "Users can view their secondary accounts" ON secondary_accounts;
CREATE POLICY "Users can view their secondary accounts" ON secondary_accounts
    FOR SELECT USING (
        primary_account_id = auth.uid() OR secondary_account_id = auth.uid()
    );

DROP POLICY IF EXISTS "Users can manage their secondary accounts" ON secondary_accounts;
CREATE POLICY "Users can manage their secondary accounts" ON secondary_accounts
    FOR ALL USING (
        primary_account_id = auth.uid()
    );

-- =====================================================
-- DADOS INICIAIS (OPCIONAL)
-- =====================================================

-- Inserir fazenda de exemplo
INSERT INTO farms (name, city, state) 
VALUES ('Fazenda Exemplo', 'São Paulo', 'SP')
ON CONFLICT DO NOTHING;

-- =====================================================
-- COMENTÁRIOS DAS TABELAS
-- =====================================================

COMMENT ON TABLE farms IS 'Tabela de fazendas cadastradas no sistema';
COMMENT ON TABLE users IS 'Tabela de usuários do sistema (integrada com Supabase Auth - SEM CONFIRMAÇÃO DE EMAIL)';
COMMENT ON TABLE lactech_users IS 'Tabela alternativa de usuários do sistema';
COMMENT ON TABLE secondary_accounts IS 'Tabela de relacionamento entre contas primárias e secundárias';
COMMENT ON TABLE milk_production IS 'Tabela de registros de produção de leite';
COMMENT ON TABLE lactech_production IS 'Tabela alternativa de registros de produção de leite';
COMMENT ON TABLE quality_tests IS 'Tabela de testes de qualidade do leite';
COMMENT ON TABLE lactech_quality IS 'Tabela alternativa de testes de qualidade do leite';
COMMENT ON TABLE financial_records IS 'Tabela de registros financeiros (vendas, despesas)';
COMMENT ON TABLE lactech_financial IS 'Tabela alternativa de registros financeiros';

COMMENT ON COLUMN users.role IS 'Papel do usuário: gerente, funcionario, veterinario, proprietario';
COMMENT ON COLUMN users.is_active IS 'Status de ativação da conta do usuário';
COMMENT ON COLUMN users.password_hash IS 'Hash da senha definida pelo gerente (acesso direto - sem confirmação de email)';
COMMENT ON COLUMN users.profile_photo_url IS 'URL da foto de perfil (apenas para funcionários)';
COMMENT ON COLUMN milk_production.shift IS 'Turno: manha, tarde, noite';
COMMENT ON COLUMN milk_production.volume_liters IS 'Volume de leite em litros';
COMMENT ON COLUMN milk_production.temperature IS 'Temperatura do leite em graus Celsius';
COMMENT ON COLUMN quality_tests.scc IS 'Contagem de Células Somáticas';
COMMENT ON COLUMN quality_tests.cbt IS 'Contagem Bacteriana Total';
COMMENT ON COLUMN financial_records.type IS 'Tipo: receita ou despesa';
COMMENT ON COLUMN financial_records.status IS 'Status: pending, completed, overdue';

-- =====================================================
-- FIM DO SCHEMA
-- =====================================================
