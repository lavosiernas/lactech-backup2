-- =====================================================
-- LACTECH - FUNÇÕES RPC NECESSÁRIAS
-- =====================================================
-- Execute este arquivo no SQL Editor do Supabase
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
    -- Adicionar coluna se não existir
    ALTER TABLE farms ADD COLUMN IF NOT EXISTS is_setup_complete BOOLEAN DEFAULT false;
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
-- CONFIRMAÇÃO DE CRIAÇÃO
-- =====================================================
SELECT '✅ Funções RPC criadas com sucesso!' as status;
