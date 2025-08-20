-- =====================================================
-- BANCO DE DADOS COMPLETO DO SISTEMA LACTECH - VERSÃO CORRIGIDA
-- Sistema de Gestão de Fazendas Leiteiras
-- Inclui todas as correções dos arquivos 2-9
-- =====================================================

-- Limpar banco de dados (se necessário)
-- DROP SCHEMA IF EXISTS public CASCADE;
-- CREATE SCHEMA public;

-- =====================================================
-- 1. EXTENSÕES NECESSÁRIAS
-- =====================================================

-- Extensão para UUID
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Extensão para criptografia
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- =====================================================
-- 2. TABELAS PRINCIPAIS (ORDEM DE DEPENDÊNCIA)
-- =====================================================

-- 2.1 TABELA FARMS (Base do sistema) - CORRIGIDA: Removidas colunas desnecessárias
CREATE TABLE IF NOT EXISTS farms (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    owner_name VARCHAR(255) NOT NULL,
    cnpj VARCHAR(18) UNIQUE,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(2) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    is_setup_complete BOOLEAN DEFAULT false,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 2.2 TABELA USERS (Depende de farms)
CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    role VARCHAR(50) NOT NULL CHECK (role IN ('proprietario', 'gerente', 'funcionario', 'veterinario')),
    whatsapp VARCHAR(20),
    profile_photo_url TEXT,
    report_farm_name VARCHAR(255),
    report_farm_logo_base64 TEXT,
    report_footer_text TEXT,
    report_system_logo_base64 TEXT,
    is_active BOOLEAN DEFAULT true,
    created_by UUID REFERENCES users(id) ON DELETE SET NULL, -- Usuário que criou esta conta (para contas secundárias)
    temp_password VARCHAR(255), -- Senha temporária para contas secundárias
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 2.3 TABELA ANIMALS (Depende de farms e users)
CREATE TABLE IF NOT EXISTS animals (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    name VARCHAR(255) NOT NULL,
    breed VARCHAR(100),
    birth_date DATE,
    weight DECIMAL(6,2),
    health_status VARCHAR(50) DEFAULT 'saudavel' CHECK (health_status IN ('saudavel', 'doente', 'tratamento', 'quarentena')),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 2.4 TABELA MILK_PRODUCTION (Depende de farms e users)
CREATE TABLE IF NOT EXISTS milk_production (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    production_date DATE NOT NULL,
    shift VARCHAR(10) NOT NULL CHECK (shift IN ('manha', 'tarde', 'noite')),
    volume_liters DECIMAL(8,2) NOT NULL CHECK (volume_liters >= 0),
    temperature DECIMAL(4,1),
    observations TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(farm_id, production_date, shift)
);

-- 2.5 TABELA QUALITY_TESTS (Depende de farms e users) - CORRIGIDA: Garantida existência
CREATE TABLE IF NOT EXISTS quality_tests (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    test_date DATE NOT NULL,
    fat_percentage DECIMAL(4,2),
    protein_percentage DECIMAL(4,2),
    scc INTEGER, -- Contagem de Células Somáticas
    cbt INTEGER, -- Contagem Bacteriana Total
    laboratory VARCHAR(255),
    observations TEXT,
    quality_score DECIMAL(4,2), -- Nota calculada automaticamente
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 2.6 TABELA NOTIFICATIONS (Depende de farms e users)
CREATE TABLE IF NOT EXISTS notifications (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info' CHECK (type IN ('info', 'warning', 'error', 'success')),
    is_read BOOLEAN DEFAULT false,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 2.8 TABELA SECONDARY_ACCOUNTS (Depende de users)
CREATE TABLE IF NOT EXISTS secondary_accounts (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    primary_user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    secondary_user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(primary_user_id, secondary_user_id),
    CHECK (primary_user_id != secondary_user_id)
);

-- =====================================================
-- 3. TABELAS DO MÓDULO VETERINÁRIO
-- =====================================================

-- 3.1 TABELA TREATMENTS (Depende de farms, animals e users)
CREATE TABLE IF NOT EXISTS treatments (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
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

-- 3.2 TABELA ANIMAL_HEALTH_RECORDS (Depende de farms, animals e users)
CREATE TABLE IF NOT EXISTS animal_health_records (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
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

-- 3.3 TABELA ARTIFICIAL_INSEMINATIONS (Depende de farms, animals e users)
CREATE TABLE IF NOT EXISTS artificial_inseminations (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
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

-- =====================================================
-- 4. TABELAS DO SISTEMA DE PAGAMENTOS PIX
-- =====================================================

-- 4.1 TABELA PIX_PAYMENTS
CREATE TABLE IF NOT EXISTS pix_payments (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
    txid TEXT UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL CHECK (amount > 0),
    status TEXT CHECK (status IN ('pending', 'confirmed', 'expired', 'cancelled')) DEFAULT 'pending',
    pix_key TEXT NOT NULL,
    pix_key_type TEXT CHECK (pix_key_type IN ('email', 'cpf', 'telefone', 'aleatoria')) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL
);

-- 4.2 TABELA SUBSCRIPTIONS (Depende de pix_payments)
CREATE TABLE IF NOT EXISTS subscriptions (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
    payment_id UUID REFERENCES pix_payments(id) ON DELETE SET NULL,
    plan_type VARCHAR(50) DEFAULT 'basic' CHECK (plan_type IN ('basic', 'premium', 'enterprise')),
    status TEXT CHECK (status IN ('active', 'expired', 'cancelled')) DEFAULT 'active',
    starts_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    expires_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- =====================================================
-- 5. TABELAS DE CONFIGURAÇÃO E GESTÃO
-- =====================================================

-- 5.1 TABELA FINANCIAL_RECORDS (Depende de farms e users)
CREATE TABLE IF NOT EXISTS financial_records (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    record_date DATE NOT NULL,
    type VARCHAR(20) NOT NULL CHECK (type IN ('receita', 'despesa')),
    amount DECIMAL(10,2) NOT NULL CHECK (amount > 0),
    description TEXT NOT NULL,
    category VARCHAR(100),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 5.2 TABELA FARM_SETTINGS (Depende de farms)
CREATE TABLE IF NOT EXISTS farm_settings (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    farm_id UUID NOT NULL REFERENCES farms(id) ON DELETE CASCADE,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(farm_id, setting_key)
);

-- =====================================================
-- 6. ÍNDICES PARA PERFORMANCE
-- =====================================================

-- Índices para farms
CREATE INDEX IF NOT EXISTS idx_farms_cnpj ON farms(cnpj);
CREATE INDEX IF NOT EXISTS idx_farms_name ON farms(name);

-- Índices para users
CREATE INDEX IF NOT EXISTS idx_users_farm_id ON users(farm_id);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_active ON users(is_active);
CREATE INDEX IF NOT EXISTS idx_users_created_by ON users(created_by);

-- Índices para milk_production
CREATE INDEX IF NOT EXISTS idx_milk_production_farm_date ON milk_production(farm_id, production_date DESC);
CREATE INDEX IF NOT EXISTS idx_milk_production_user ON milk_production(user_id);
CREATE INDEX IF NOT EXISTS idx_milk_production_date ON milk_production(production_date DESC);

-- Índices para quality_tests - CORRIGIDOS
CREATE INDEX IF NOT EXISTS idx_quality_tests_farm_date ON quality_tests(farm_id, test_date DESC);
CREATE INDEX IF NOT EXISTS idx_quality_tests_user ON quality_tests(user_id);
CREATE INDEX IF NOT EXISTS idx_quality_tests_farm_id ON quality_tests(farm_id);
CREATE INDEX IF NOT EXISTS idx_quality_tests_test_date ON quality_tests(test_date);

-- Índices para animals
CREATE INDEX IF NOT EXISTS idx_animals_farm_active ON animals(farm_id, is_active);
CREATE INDEX IF NOT EXISTS idx_animals_health ON animals(health_status);

-- Índices para notifications
CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX IF NOT EXISTS idx_notifications_farm ON notifications(farm_id);

-- Índices para artificial_inseminations
CREATE INDEX IF NOT EXISTS idx_artificial_inseminations_farm_date ON artificial_inseminations(farm_id, insemination_date DESC);
CREATE INDEX IF NOT EXISTS idx_artificial_inseminations_animal ON artificial_inseminations(animal_id);
CREATE INDEX IF NOT EXISTS idx_artificial_inseminations_pregnancy ON artificial_inseminations(pregnancy_confirmed, expected_calving_date);

-- =====================================================
-- 7. TRIGGERS PARA UPDATED_AT
-- =====================================================

-- Função para atualizar updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers para todas as tabelas com updated_at
CREATE TRIGGER update_farms_updated_at BEFORE UPDATE ON farms
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_animals_updated_at BEFORE UPDATE ON animals
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_milk_production_updated_at BEFORE UPDATE ON milk_production
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_quality_tests_updated_at BEFORE UPDATE ON quality_tests
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_treatments_updated_at BEFORE UPDATE ON treatments
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_animal_health_records_updated_at BEFORE UPDATE ON animal_health_records
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_pix_payments_updated_at BEFORE UPDATE ON pix_payments
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_subscriptions_updated_at BEFORE UPDATE ON subscriptions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_financial_records_updated_at BEFORE UPDATE ON financial_records
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_farm_settings_updated_at BEFORE UPDATE ON farm_settings
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_artificial_inseminations_updated_at BEFORE UPDATE ON artificial_inseminations
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- =====================================================
-- 8. FUNÇÕES AUXILIARES (ANTES DAS POLÍTICAS RLS)
-- =====================================================

-- Função auxiliar para obter farm_id do usuário atual sem recursão
CREATE OR REPLACE FUNCTION get_current_user_farm_id()
RETURNS UUID AS $$
DECLARE
    user_farm_id UUID;
BEGIN
    -- Usar auth.jwt() para evitar consulta recursiva na tabela users
    SELECT farm_id INTO user_farm_id 
    FROM users 
    WHERE email = auth.jwt() ->> 'email'
    LIMIT 1;
    
    RETURN user_farm_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função auxiliar para verificar se usuário atual é gerente sem recursão
CREATE OR REPLACE FUNCTION is_current_user_manager()
RETURNS BOOLEAN AS $$
DECLARE
    user_role TEXT;
BEGIN
    -- Usar auth.jwt() para evitar consulta recursiva na tabela users
    SELECT role INTO user_role 
    FROM users 
    WHERE email = auth.jwt() ->> 'email'
    LIMIT 1;
    
    RETURN user_role IN ('proprietario', 'gerente');
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- =====================================================
-- 9. ROW LEVEL SECURITY (RLS)
-- =====================================================

-- Habilitar RLS em todas as tabelas
ALTER TABLE farms ENABLE ROW LEVEL SECURITY;
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE animals ENABLE ROW LEVEL SECURITY;
ALTER TABLE milk_production ENABLE ROW LEVEL SECURITY;
ALTER TABLE quality_tests ENABLE ROW LEVEL SECURITY;
ALTER TABLE notifications ENABLE ROW LEVEL SECURITY;
ALTER TABLE secondary_accounts ENABLE ROW LEVEL SECURITY;
ALTER TABLE treatments ENABLE ROW LEVEL SECURITY;
ALTER TABLE animal_health_records ENABLE ROW LEVEL SECURITY;
ALTER TABLE pix_payments ENABLE ROW LEVEL SECURITY;
ALTER TABLE subscriptions ENABLE ROW LEVEL SECURITY;
ALTER TABLE financial_records ENABLE ROW LEVEL SECURITY;
ALTER TABLE farm_settings ENABLE ROW LEVEL SECURITY;
ALTER TABLE artificial_inseminations ENABLE ROW LEVEL SECURITY;

-- =====================================================
-- 10. POLÍTICAS RLS CORRIGIDAS - SEM RECURSÃO INFINITA
-- =====================================================

-- FARMS: Usuários podem ver apenas sua própria fazenda
DROP POLICY IF EXISTS "Users can view their own farm" ON farms;
CREATE POLICY "Users can view their own farm" ON farms
    FOR SELECT USING (
        id IN (SELECT farm_id FROM users WHERE id = auth.uid())
    );

CREATE POLICY "Users can update their own farm" ON farms
    FOR UPDATE USING (
        id IN (SELECT farm_id FROM users WHERE id = auth.uid()) AND
        EXISTS (SELECT 1 FROM users WHERE id = auth.uid() AND role IN ('proprietario', 'gerente'))
    );

CREATE POLICY "Users can insert farms" ON farms
    FOR INSERT WITH CHECK (true);

-- Corrigindo políticas RLS da tabela users para permitir acesso aos próprios dados
-- USERS: Políticas corrigidas sem recursão infinita
DROP POLICY IF EXISTS "Users can view own profile and farm members" ON users;
DROP POLICY IF EXISTS "Users can update their own profile" ON users;
DROP POLICY IF EXISTS "Managers can insert users" ON users;

-- Política mais permissiva para SELECT - permite ver próprio perfil e membros da fazenda
CREATE POLICY "Users can view profiles" ON users
    FOR SELECT USING (
        -- Pode ver seu próprio perfil
        id = auth.uid() OR 
        -- Pode ver usuários da mesma fazenda
        farm_id IN (SELECT farm_id FROM users WHERE id = auth.uid())
    );

-- Política para UPDATE - pode atualizar próprio perfil
CREATE POLICY "Users can update own profile" ON users
    FOR UPDATE USING (id = auth.uid());

-- Política para INSERT - gerentes podem criar usuários
CREATE POLICY "Managers can create users" ON users
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM users 
            WHERE id = auth.uid() 
            AND role IN ('proprietario', 'gerente')
        )
    );

-- =====================================================
-- 11. FUNÇÕES RPC CORRIGIDAS
-- =====================================================

-- 11.1 Verificar se fazenda existe
-- Corrigindo função check_farm_exists com parâmetros opcionais e validações adequadas
CREATE OR REPLACE FUNCTION check_farm_exists(p_name TEXT, p_cnpj TEXT DEFAULT NULL)
RETURNS BOOLEAN AS $$
BEGIN
    IF p_name IS NULL OR TRIM(p_name) = '' THEN
        RETURN FALSE;
    END IF;
    
    IF EXISTS (
        SELECT 1 FROM farms 
        WHERE LOWER(TRIM(name)) = LOWER(TRIM(p_name))
    ) THEN
        RETURN TRUE;
    END IF;
    
    IF p_cnpj IS NOT NULL AND TRIM(p_cnpj) != '' THEN
        IF EXISTS (
            SELECT 1 FROM farms 
            WHERE LOWER(TRIM(cnpj)) = LOWER(TRIM(p_cnpj))
        ) THEN
            RETURN TRUE;
        END IF;
    END IF;
    
    RETURN FALSE;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 11.2 Verificar se usuário existe
CREATE OR REPLACE FUNCTION check_user_exists(p_email TEXT)
RETURNS BOOLEAN AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1 FROM users WHERE email = p_email
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 11.3 Criar fazenda inicial - CORRIGIDA: Sem colunas removidas
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

-- 11.4 Criar usuário inicial - CORRIGIDA
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

-- 11.5 Completar configuração da fazenda
CREATE OR REPLACE FUNCTION complete_farm_setup(p_farm_id UUID)
RETURNS VOID AS $$
BEGIN
    UPDATE farms SET is_setup_complete = true WHERE id = p_farm_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 11.6 Obter perfil do usuário - CORRIGIDA: Tipos explícitos
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
    created_at TIMESTAMPTZ,
    updated_at TIMESTAMPTZ
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        u.id::UUID as user_id,
        u.name::TEXT as user_name,
        u.email::TEXT as user_email,
        u.role::TEXT as user_role,
        u.farm_id::UUID as farm_id,
        f.name::TEXT as farm_name,
        u.is_active::BOOLEAN as is_active,
        u.whatsapp::TEXT as whatsapp,
        u.profile_photo_url::TEXT as profile_photo_url,
        u.created_at::TIMESTAMPTZ as created_at,
        u.updated_at::TIMESTAMPTZ as updated_at
    FROM users u
    LEFT JOIN farms f ON u.farm_id = f.id
    WHERE u.id = auth.uid();
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 11.7 Criar usuário secundário
CREATE OR REPLACE FUNCTION create_secondary_user(
    p_user_id UUID,
    p_farm_id UUID,
    p_name TEXT,
    p_email TEXT,
    p_role TEXT,
    p_whatsapp TEXT DEFAULT '',
    p_created_by UUID DEFAULT NULL,
    p_temp_password TEXT DEFAULT NULL
)
RETURNS VOID AS $$
DECLARE
    current_user_role TEXT;
    current_user_farm_id UUID;
BEGIN
    -- Verificar se o usuário atual tem permissão para criar usuários
    SELECT role, farm_id INTO current_user_role, current_user_farm_id
    FROM users 
    WHERE email = auth.jwt() ->> 'email'
    LIMIT 1;
    
    IF current_user_role NOT IN ('proprietario', 'gerente') THEN
        RAISE EXCEPTION 'Apenas proprietários e gerentes podem criar usuários secundários';
    END IF;
    
    IF current_user_farm_id != p_farm_id THEN
        RAISE EXCEPTION 'Você só pode criar usuários para sua própria fazenda';
    END IF;
    
    -- Verificar se o email já existe
    IF EXISTS (SELECT 1 FROM users WHERE email = p_email) THEN
        RAISE EXCEPTION 'Já existe um usuário com este email';
    END IF;
    
    -- Inserir o novo usuário
    INSERT INTO users (
        id, farm_id, name, email, role, whatsapp, created_by, temp_password, is_active
    ) VALUES (
        p_user_id, p_farm_id, p_name, p_email, p_role, p_whatsapp, 
        COALESCE(p_created_by, auth.uid()), p_temp_password, true
    );
    
    -- Registrar na tabela de contas secundárias se necessário
    IF p_created_by IS NOT NULL THEN
        INSERT INTO secondary_accounts (primary_user_id, secondary_user_id)
        VALUES (p_created_by, p_user_id)
        ON CONFLICT DO NOTHING;
    END IF;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 11.8 Obter estatísticas da fazenda
CREATE OR REPLACE FUNCTION get_farm_statistics()
RETURNS TABLE(
    total_animals INTEGER,
    total_production_today DECIMAL,
    total_production_week DECIMAL,
    total_production_month DECIMAL,
    active_users INTEGER,
    pending_payments INTEGER
) AS $$
DECLARE
    user_farm_id UUID;
BEGIN
    -- Obter farm_id do usuário atual
    SELECT farm_id INTO user_farm_id FROM users WHERE id = auth.uid();
    
    RETURN QUERY
    SELECT 
        (SELECT COUNT(*)::INTEGER FROM animals WHERE farm_id = user_farm_id AND is_active = true),
        (SELECT COALESCE(SUM(volume_liters), 0) FROM milk_production 
         WHERE farm_id = user_farm_id AND production_date = CURRENT_DATE),
        (SELECT COALESCE(SUM(volume_liters), 0) FROM milk_production 
         WHERE farm_id = user_farm_id AND production_date >= CURRENT_DATE - INTERVAL '7 days'),
        (SELECT COALESCE(SUM(volume_liters), 0) FROM milk_production 
         WHERE farm_id = user_farm_id AND production_date >= DATE_TRUNC('month', CURRENT_DATE)),
        (SELECT COUNT(*)::INTEGER FROM users WHERE farm_id = user_farm_id AND is_active = true),
        0; -- Payments removido
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 11.9 Registrar produção de leite
CREATE OR REPLACE FUNCTION register_milk_production(
    p_production_date DATE,
    p_shift TEXT,
    p_volume_liters DECIMAL,
    p_temperature DECIMAL DEFAULT NULL,
    p_observations TEXT DEFAULT ''
)
RETURNS UUID AS $$
DECLARE
    user_farm_id UUID;
    production_id UUID;
BEGIN
    -- Obter farm_id do usuário atual
    SELECT farm_id INTO user_farm_id FROM users WHERE id = auth.uid();
    
    INSERT INTO milk_production (
        farm_id, user_id, production_date, shift, volume_liters, temperature, observations
    ) VALUES (
        user_farm_id, auth.uid(), p_production_date, p_shift, p_volume_liters, p_temperature, p_observations
    ) RETURNING id INTO production_id;
    
    RETURN production_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 11.10 Atualizar configurações de relatório do usuário - CORRIGIDA: Todos os 4 parâmetros
CREATE OR REPLACE FUNCTION update_user_report_settings(
    p_report_farm_name TEXT DEFAULT NULL,
    p_report_farm_logo_base64 TEXT DEFAULT NULL,
    p_report_footer_text TEXT DEFAULT NULL,
    p_report_system_logo_base64 TEXT DEFAULT NULL
)
RETURNS VOID AS $$
BEGIN
    UPDATE users SET 
        report_farm_name = COALESCE(p_report_farm_name, report_farm_name),
        report_farm_logo_base64 = COALESCE(p_report_farm_logo_base64, report_farm_logo_base64),
        report_footer_text = COALESCE(p_report_footer_text, report_footer_text),
        report_system_logo_base64 = COALESCE(p_report_system_logo_base64, report_system_logo_base64),
        updated_at = NOW()
    WHERE id = auth.uid();
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 11.11 Obter assinaturas do usuário
CREATE OR REPLACE FUNCTION get_user_subscriptions()
RETURNS TABLE(
    subscription_id UUID,
    plan_type TEXT,
    status TEXT,
    starts_at TIMESTAMP WITH TIME ZONE,
    expires_at TIMESTAMP WITH TIME ZONE
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        s.id,
        s.plan_type,
        s.status,
        s.starts_at,
        s.expires_at
    FROM subscriptions s
    WHERE s.user_id = auth.uid()
    ORDER BY s.created_at DESC;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 11.12 Obter pagamentos do usuário
CREATE OR REPLACE FUNCTION get_user_payments()
RETURNS TABLE(
    payment_id UUID,
    amount DECIMAL,
    status TEXT,
    created_at TIMESTAMP WITH TIME ZONE
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        p.id,
        p.amount,
        p.status,
        p.created_at
    FROM pix_payments p
    WHERE p.user_id = auth.uid()
    ORDER BY p.created_at DESC;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 11.13 Registrar inseminação artificial
CREATE OR REPLACE FUNCTION register_artificial_insemination(
    p_animal_id UUID,
    p_insemination_date DATE,
    p_semen_batch TEXT,
    p_semen_origin TEXT DEFAULT NULL,
    p_bull_identification TEXT DEFAULT NULL,
    p_technician_name TEXT DEFAULT NULL,
    p_technique_used TEXT DEFAULT 'convencional',
    p_estrus_detection_method TEXT DEFAULT NULL,
    p_body_condition_score DECIMAL DEFAULT NULL,
    p_observations TEXT DEFAULT ''
)
RETURNS UUID AS $$
DECLARE
    user_farm_id UUID;
    insemination_id UUID;
BEGIN
    -- Obter farm_id do usuário atual
    SELECT farm_id INTO user_farm_id FROM users WHERE id = auth.uid();
    
    -- Verificar se o animal pertence à fazenda do usuário
    IF NOT EXISTS (
        SELECT 1 FROM animals 
        WHERE id = p_animal_id AND farm_id = user_farm_id
    ) THEN
        RAISE EXCEPTION 'Animal não encontrado ou não pertence à sua fazenda';
    END IF;
    
    INSERT INTO artificial_inseminations (
        farm_id, animal_id, user_id, insemination_date, semen_batch,
        semen_origin, bull_identification, technician_name, technique_used,
        estrus_detection_method, body_condition_score, observations
    ) VALUES (
        user_farm_id, p_animal_id, auth.uid(), p_insemination_date, p_semen_batch,
        p_semen_origin, p_bull_identification, p_technician_name, p_technique_used,
        p_estrus_detection_method, p_body_condition_score, p_observations
    ) RETURNING id INTO insemination_id;
    
    RETURN insemination_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 11.14 Confirmar gravidez de inseminação
CREATE OR REPLACE FUNCTION confirm_pregnancy(
    p_insemination_id UUID,
    p_pregnancy_confirmed BOOLEAN,
    p_confirmation_date DATE DEFAULT CURRENT_DATE,
    p_confirmation_method TEXT DEFAULT 'palpacao'
)
RETURNS VOID AS $$
DECLARE
    user_farm_id UUID;
BEGIN
    -- Obter farm_id do usuário atual
    SELECT farm_id INTO user_farm_id FROM users WHERE id = auth.uid();
    
    -- Verificar se a inseminação pertence à fazenda do usuário
    IF NOT EXISTS (
        SELECT 1 FROM artificial_inseminations 
        WHERE id = p_insemination_id AND farm_id = user_farm_id
    ) THEN
        RAISE EXCEPTION 'Registro de inseminação não encontrado ou não pertence à sua fazenda';
    END IF;
    
    UPDATE artificial_inseminations SET 
        pregnancy_confirmed = p_pregnancy_confirmed,
        pregnancy_confirmation_date = p_confirmation_date,
        pregnancy_confirmation_method = p_confirmation_method,
        updated_at = NOW()
    WHERE id = p_insemination_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 11.15 FUNÇÃO PARA SINCRONIZAR AUTH.USERS COM PUBLIC.USERS
-- =====================================================

-- Função para sincronizar usuário atual do auth com public.users
CREATE OR REPLACE FUNCTION sync_auth_user()
RETURNS VOID AS $$
DECLARE
    auth_user_id UUID;
    auth_user_email TEXT;
    existing_user_count INTEGER;
BEGIN
    -- Obter dados do usuário autenticado
    auth_user_id := auth.uid();
    auth_user_email := auth.jwt() ->> 'email';
    
    IF auth_user_id IS NULL OR auth_user_email IS NULL THEN
        RAISE EXCEPTION 'Usuário não autenticado';
    END IF;
    
    -- Verificar se usuário já existe na tabela public.users
    SELECT COUNT(*) INTO existing_user_count 
    FROM users 
    WHERE id = auth_user_id OR email = auth_user_email;
    
    -- Se não existe, não podemos criar automaticamente pois precisamos do farm_id
    -- Esta função serve apenas para debug/verificação
    IF existing_user_count = 0 THEN
        RAISE NOTICE 'Usuário % não encontrado na tabela public.users. Necessário cadastro completo.', auth_user_email;
    ELSE
        RAISE NOTICE 'Usuário % encontrado na tabela public.users.', auth_user_email;
    END IF;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Função RPC para ser chamada do frontend
CREATE OR REPLACE FUNCTION sync_current_user()
RETURNS JSON AS $$
DECLARE
    auth_user_id UUID;
    auth_user_email TEXT;
    user_data JSON;
BEGIN
    auth_user_id := auth.uid();
    auth_user_email := auth.jwt() ->> 'email';
    
    IF auth_user_id IS NULL THEN
        RETURN json_build_object('error', 'Usuário não autenticado');
    END IF;
    
    -- Buscar dados do usuário
    SELECT json_build_object(
        'id', u.id,
        'name', u.name,
        'email', u.email,
        'role', u.role,
        'farm_id', u.farm_id,
        'farm_name', f.name,
        'is_active', u.is_active,
        'whatsapp', u.whatsapp,
        'profile_photo_url', u.profile_photo_url
    ) INTO user_data
    FROM users u
    LEFT JOIN farms f ON u.farm_id = f.id
    WHERE u.id = auth_user_id OR u.email = auth_user_email
    LIMIT 1;
    
    IF user_data IS NULL THEN
        RETURN json_build_object(
            'error', 'Usuário não encontrado na tabela public.users',
            'auth_user_id', auth_user_id,
            'auth_user_email', auth_user_email,
            'suggestion', 'Necessário completar cadastro da fazenda'
        );
    END IF;
    
    RETURN json_build_object('success', true, 'user', user_data);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- =====================================================
-- 12. TRIGGER PARA CALCULAR NOTA DE QUALIDADE
-- =====================================================

CREATE OR REPLACE FUNCTION calculate_quality_score()
RETURNS TRIGGER AS $$
BEGIN
    NEW.quality_score := 
        COALESCE(NEW.fat_percentage * 0.3, 0) +
        COALESCE(NEW.protein_percentage * 0.3, 0) +
        CASE 
            WHEN NEW.scc <= 200000 THEN 10 * 0.2
            WHEN NEW.scc <= 400000 THEN 8 * 0.2
            WHEN NEW.scc <= 600000 THEN 6 * 0.2
            ELSE 4 * 0.2
        END +
        CASE 
            WHEN NEW.cbt <= 100000 THEN 10 * 0.2
            WHEN NEW.cbt <= 300000 THEN 8 * 0.2
            WHEN NEW.cbt <= 500000 THEN 6 * 0.2
            ELSE 4 * 0.2
        END;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER calculate_quality_score_trigger
    BEFORE INSERT OR UPDATE ON quality_tests
    FOR EACH ROW EXECUTE FUNCTION calculate_quality_score();

-- =====================================================
-- 13. CONFIGURAÇÕES INICIAIS E DADOS DE TESTE
-- =====================================================

-- Inserir configurações padrão para fazendas existentes
INSERT INTO farm_settings (farm_id, setting_key, setting_value)
SELECT 
    f.id,
    'default_milk_price',
    '2.50'
FROM farms f
WHERE NOT EXISTS (
    SELECT 1 FROM farm_settings fs 
    WHERE fs.farm_id = f.id AND fs.setting_key = 'default_milk_price'
);

-- Inserir dados de teste para quality_tests (se necessário)
DO $$
DECLARE
    test_farm_id UUID;
    test_user_id UUID;
    farm_count INTEGER;
BEGIN
    -- Verificar se há fazendas
    SELECT COUNT(*) INTO farm_count FROM farms;
    
    IF farm_count > 0 THEN
        -- Pegar a primeira fazenda
        SELECT id INTO test_farm_id FROM farms LIMIT 1;
        
        -- Pegar o primeiro usuário da fazenda
        SELECT id INTO test_user_id FROM users WHERE farm_id = test_farm_id LIMIT 1;
        
        -- Verificar se há dados de qualidade
        IF test_user_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM quality_tests WHERE farm_id = test_farm_id) THEN
            -- Inserir dados de teste
            INSERT INTO quality_tests (farm_id, user_id, test_date, fat_percentage, protein_percentage, scc, cbt, laboratory, observations)
            VALUES 
                (test_farm_id, test_user_id, CURRENT_DATE - INTERVAL '7 days', 3.8, 3.2, 150000, 50000, 'Laboratório Central', 'Amostra de teste - excelente'),
                (test_farm_id, test_user_id, CURRENT_DATE - INTERVAL '5 days', 3.5, 3.1, 180000, 60000, 'Laboratório Central', 'Amostra de teste - boa'),
                (test_farm_id, test_user_id, CURRENT_DATE - INTERVAL '3 days', 3.2, 2.9, 220000, 80000, 'Laboratório Central', 'Amostra de teste - regular'),
                (test_farm_id, test_user_id, CURRENT_DATE - INTERVAL '1 day', 3.6, 3.0, 160000, 55000, 'Laboratório Central', 'Amostra de teste - boa'),
                (test_farm_id, test_user_id, CURRENT_DATE, 3.4, 3.3, 170000, 52000, 'Laboratório Central', 'Amostra de teste - boa')
            ON CONFLICT DO NOTHING;
        END IF;
    END IF;
END $$;

-- =====================================================
-- 14. COMENTÁRIOS E DOCUMENTAÇÃO
-- =====================================================

COMMENT ON TABLE farms IS 'Tabela principal das fazendas cadastradas no sistema';
COMMENT ON TABLE users IS 'Usuários do sistema com diferentes níveis de acesso';
COMMENT ON TABLE animals IS 'Cadastro do rebanho de cada fazenda';
COMMENT ON TABLE milk_production IS 'Registros diários de produção de leite por turno';
COMMENT ON TABLE quality_tests IS 'Resultados de análises de qualidade do leite';
COMMENT ON TABLE notifications IS 'Sistema de notificações para usuários';
COMMENT ON TABLE secondary_accounts IS 'Relacionamento entre contas principais e secundárias';
COMMENT ON TABLE treatments IS 'Registros de tratamentos veterinários';
COMMENT ON TABLE animal_health_records IS 'Histórico de saúde dos animais';
COMMENT ON TABLE artificial_inseminations IS 'Registros de inseminação artificial e controle reprodutivo';
COMMENT ON TABLE pix_payments IS 'Pagamentos PIX para assinaturas do sistema';
COMMENT ON TABLE subscriptions IS 'Controle de assinaturas dos usuários';
COMMENT ON TABLE financial_records IS 'Registros financeiros gerais da fazenda';
COMMENT ON TABLE farm_settings IS 'Configurações específicas de cada fazenda';

-- =====================================================
-- 15. VERIFICAÇÕES FINAIS
-- =====================================================

-- Verificar tabelas criadas
SELECT 
    schemaname,
    tablename,
    tableowner
FROM pg_tables 
WHERE schemaname = 'public'
ORDER BY tablename;

-- Verificar políticas RLS
SELECT 
    schemaname,
    tablename,
    policyname,
    cmd
FROM pg_policies 
WHERE schemaname = 'public'
ORDER BY tablename, policyname;

-- Verificar funções criadas
SELECT 
    p.proname as function_name,
    pg_get_function_arguments(p.oid) as arguments
FROM pg_proc p
JOIN pg_namespace n ON p.pronamespace = n.oid
WHERE n.nspname = 'public' 
AND p.proname LIKE '%farm%' OR p.proname LIKE '%user%'
ORDER BY p.proname;

SELECT 'Banco de dados LacTech criado com sucesso - Versão Completa Unificada!' AS status;
