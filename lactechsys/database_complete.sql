-- =====================================================
-- BANCO DE DADOS COMPLETO DO SISTEMA LACTECH
-- Sistema de Gestão de Fazendas Leiteiras
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

-- 2.1 TABELA FARMS (Base do sistema)
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

-- 2.5 TABELA QUALITY_TESTS (Depende de farms e users)
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

-- Índices para quality_tests
CREATE INDEX IF NOT EXISTS idx_quality_tests_farm_date ON quality_tests(farm_id, test_date DESC);
CREATE INDEX IF NOT EXISTS idx_quality_tests_user ON quality_tests(user_id);

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
-- 8. ROW LEVEL SECURITY (RLS)
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
-- 9. POLÍTICAS RLS - FARMS
-- =====================================================

-- Farms: Usuários podem ver apenas sua própria fazenda
CREATE POLICY "Users can view their own farm" ON farms
    FOR SELECT USING (
        id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

CREATE POLICY "Users can update their own farm" ON farms
    FOR UPDATE USING (
        id IN (
            SELECT farm_id FROM users 
            WHERE id = auth.uid() 
            AND role IN ('proprietario', 'gerente')
        )
    );

CREATE POLICY "Users can insert farms" ON farms
    FOR INSERT WITH CHECK (true);

-- =====================================================
-- 10. POLÍTICAS RLS - USERS
-- =====================================================

-- Users: Usuários podem ver outros usuários da mesma fazenda
CREATE POLICY "Users can view farm members" ON users
    FOR SELECT USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

CREATE POLICY "Users can update their own profile" ON users
    FOR UPDATE USING (id = auth.uid());

CREATE POLICY "Managers can update farm users" ON users
    FOR UPDATE USING (
        farm_id IN (
            SELECT farm_id FROM users 
            WHERE id = auth.uid() 
            AND role IN ('proprietario', 'gerente')
        )
    );

CREATE POLICY "Managers can insert users" ON users
    FOR INSERT WITH CHECK (
        farm_id IN (
            SELECT farm_id FROM users 
            WHERE id = auth.uid() 
            AND role IN ('proprietario', 'gerente')
        )
    );

-- =====================================================
-- 11. POLÍTICAS RLS - DADOS OPERACIONAIS
-- =====================================================

-- Política genérica para tabelas que dependem de farm_id
CREATE POLICY "Farm members can access farm data" ON animals
    FOR ALL USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

CREATE POLICY "Farm members can access milk production" ON milk_production
    FOR ALL USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

CREATE POLICY "Farm members can access quality tests" ON quality_tests
    FOR ALL USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );



CREATE POLICY "Farm members can access notifications" ON notifications
    FOR ALL USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

CREATE POLICY "Farm members can access treatments" ON treatments
    FOR ALL USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

CREATE POLICY "Farm members can access health records" ON animal_health_records
    FOR ALL USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

CREATE POLICY "Farm members can access financial records" ON financial_records
    FOR ALL USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

CREATE POLICY "Farm members can access settings" ON farm_settings
    FOR ALL USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

CREATE POLICY "Farm members can access artificial inseminations" ON artificial_inseminations
    FOR ALL USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
    );

-- =====================================================
-- 12. POLÍTICAS RLS - CONTAS SECUNDÁRIAS
-- =====================================================

CREATE POLICY "Users can manage their secondary accounts" ON secondary_accounts
    FOR ALL USING (
        primary_user_id = auth.uid() OR secondary_user_id = auth.uid()
    );

-- =====================================================
-- 13. POLÍTICAS RLS - SISTEMA DE PAGAMENTOS
-- =====================================================

CREATE POLICY "Users can manage their own payments" ON pix_payments
    FOR ALL USING (user_id = auth.uid());

CREATE POLICY "Users can manage their own subscriptions" ON subscriptions
    FOR ALL USING (user_id = auth.uid());

-- =====================================================
-- 14. FUNÇÕES RPC PERSONALIZADAS
-- =====================================================

-- 14.1 Verificar se fazenda existe
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

-- 14.2 Verificar se usuário existe
CREATE OR REPLACE FUNCTION check_user_exists(p_email TEXT)
RETURNS BOOLEAN AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1 FROM users WHERE email = p_email
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 14.3 Criar fazenda inicial
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

-- 14.4 Criar usuário inicial
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

-- 14.5 Completar configuração da fazenda
CREATE OR REPLACE FUNCTION complete_farm_setup(p_farm_id UUID)
RETURNS VOID AS $$
BEGIN
    UPDATE farms SET is_setup_complete = true WHERE id = p_farm_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 14.6 Obter perfil do usuário
CREATE OR REPLACE FUNCTION get_user_profile()
RETURNS TABLE(
    user_id UUID,
    farm_id UUID,
    farm_name TEXT,
    user_name TEXT,
    user_email TEXT,
    user_role TEXT,
    user_whatsapp TEXT,
    profile_photo_url TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        u.id,
        u.farm_id,
        f.name,
        u.name,
        u.email,
        u.role,
        u.whatsapp,
        u.profile_photo_url
    FROM users u
    JOIN farms f ON u.farm_id = f.id
    WHERE u.id = auth.uid();
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 14.7 Criar usuário secundário
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
BEGIN
    INSERT INTO users (
        id, farm_id, name, email, role, whatsapp, created_by, temp_password, is_active
    ) VALUES (
        p_user_id, p_farm_id, p_name, p_email, p_role, p_whatsapp, p_created_by, p_temp_password, true
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 14.8 Obter estatísticas da fazenda
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

-- 14.8 Registrar produção de leite
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

-- 14.9 Atualizar configurações de relatório do usuário
CREATE OR REPLACE FUNCTION update_user_report_settings(
    p_report_farm_name TEXT DEFAULT NULL,
    p_report_farm_logo_base64 TEXT DEFAULT NULL
)
RETURNS VOID AS $$
BEGIN
    UPDATE users SET 
        report_farm_name = COALESCE(p_report_farm_name, report_farm_name),
        report_farm_logo_base64 = COALESCE(p_report_farm_logo_base64, report_farm_logo_base64),
        updated_at = NOW()
    WHERE id = auth.uid();
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 14.10 Obter assinaturas do usuário
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

-- 14.11 Obter pagamentos do usuário
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

-- 14.12 Registrar inseminação artificial
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

-- 14.13 Confirmar gravidez de inseminação
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

-- =====================================================
-- 15. TRIGGER PARA CALCULAR NOTA DE QUALIDADE
-- =====================================================

CREATE OR REPLACE FUNCTION calculate_quality_score()
RETURNS TRIGGER AS $$
BEGIN
    -- Calcular nota baseada nos parâmetros de qualidade
    -- Fórmula simplificada: (gordura * 0.3) + (proteína * 0.3) + (scc_score * 0.2) + (cbt_score * 0.2)
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
-- 16. DADOS INICIAIS (OPCIONAL)
-- =====================================================

-- Inserir configurações padrão do sistema
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

-- =====================================================
-- 17. COMENTÁRIOS E DOCUMENTAÇÃO
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
-- FIM DO SCRIPT
-- =====================================================

-- Verificar se todas as tabelas foram criadas
SELECT 
    schemaname,
    tablename,
    tableowner
FROM pg_tables 
WHERE schemaname = 'public'
ORDER BY tablename;

-- Verificar se todas as políticas RLS foram aplicadas
SELECT 
    schemaname,
    tablename,
    policyname,
    cmd
FROM pg_policies 
WHERE schemaname = 'public'
ORDER BY tablename, policyname;

SELECT 'Banco de dados LacTech criado com sucesso!' AS status;