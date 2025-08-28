-- =====================================================
-- LACTECH - CORREÇÕES DO BANCO DE DADOS
-- =====================================================
-- Execute este arquivo no SQL Editor do Supabase para corrigir as colunas faltantes
-- =====================================================

-- 1. Adicionar coluna owner_name na tabela farms (se não existir)
ALTER TABLE farms ADD COLUMN IF NOT EXISTS owner_name VARCHAR(255);

-- 2. Adicionar coluna address na tabela farms (se não existir)
ALTER TABLE farms ADD COLUMN IF NOT EXISTS address TEXT;

-- 3. Adicionar coluna zip_code na tabela farms (se não existir)
ALTER TABLE farms ADD COLUMN IF NOT EXISTS zip_code VARCHAR(10);

-- 4. Adicionar coluna is_setup_complete na tabela farms (se não existir)
ALTER TABLE farms ADD COLUMN IF NOT EXISTS is_setup_complete BOOLEAN DEFAULT false;

-- 5. Verificar se a coluna password_hash existe na tabela users
ALTER TABLE users ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255);

-- 6. Verificar se as colunas de relatório existem na tabela users
ALTER TABLE users ADD COLUMN IF NOT EXISTS report_farm_name VARCHAR(255);
ALTER TABLE users ADD COLUMN IF NOT EXISTS report_farm_logo_base64 TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS report_footer_text TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS report_system_logo_base64 TEXT;

-- 7. Verificar se as colunas de timestamp existem
ALTER TABLE farms ADD COLUMN IF NOT EXISTS created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW();
ALTER TABLE farms ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW();

ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW();
ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW();

-- 8. Recriar as funções RPC com as colunas corretas
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

-- 9. Recriar função check_farm_exists
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

-- 10. Recriar função check_user_exists
DROP FUNCTION IF EXISTS check_user_exists(TEXT);
CREATE OR REPLACE FUNCTION check_user_exists(p_email TEXT)
RETURNS BOOLEAN AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1 FROM users WHERE email = p_email
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 11. Recriar função create_initial_user
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

-- 12. Recriar função complete_farm_setup
DROP FUNCTION IF EXISTS complete_farm_setup(UUID);
CREATE OR REPLACE FUNCTION complete_farm_setup(p_farm_id UUID)
RETURNS VOID AS $$
BEGIN
    UPDATE farms SET is_setup_complete = true WHERE id = p_farm_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 13. Recriar função get_user_profile
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

-- =====================================================
-- CONFIRMAÇÃO DE CORREÇÃO
-- =====================================================
SELECT '✅ Correções aplicadas com sucesso!' as status;
SELECT '📊 Colunas adicionadas na tabela farms: owner_name, address, zip_code, is_setup_complete' as farms_columns;
SELECT '👤 Colunas adicionadas na tabela users: password_hash, report_*, created_at, updated_at' as users_columns;
SELECT '🔧 Funções RPC recriadas com as colunas corretas' as functions;
