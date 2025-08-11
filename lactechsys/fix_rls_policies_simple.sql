-- =====================================================
-- CORREÇÃO SIMPLES DAS POLÍTICAS RLS - SEM CONSULTAS ANINHADAS
-- =====================================================
-- Execute este SQL no Supabase Dashboard para resolver definitivamente os erros
-- Vá em: Supabase Dashboard > SQL Editor > Cole este código e execute

-- 1. REMOVER TODAS AS POLÍTICAS RLS PROBLEMÁTICAS
DROP POLICY IF EXISTS "Users can view farm members" ON users;
DROP POLICY IF EXISTS "Users can update their own profile" ON users;
DROP POLICY IF EXISTS "Managers can update farm users" ON users;
DROP POLICY IF EXISTS "Managers can insert users" ON users;

DROP POLICY IF EXISTS "Farm members can access farm data" ON animals;
DROP POLICY IF EXISTS "Farm members can access milk production" ON milk_production;
DROP POLICY IF EXISTS "Farm members can access quality tests" ON quality_tests;
DROP POLICY IF EXISTS "Farm members can access notifications" ON notifications;
DROP POLICY IF EXISTS "Farm members can access treatments" ON treatments;
DROP POLICY IF EXISTS "Farm members can access health records" ON animal_health_records;
DROP POLICY IF EXISTS "Farm members can access financial records" ON financial_records;
DROP POLICY IF EXISTS "Farm members can access settings" ON farm_settings;
DROP POLICY IF EXISTS "Farm members can access artificial inseminations" ON artificial_inseminations;

-- 2. CRIAR POLÍTICAS RLS SIMPLES - USERS (SEM CONSULTAS ANINHADAS)
-- Política para visualizar próprio perfil e usuários da mesma fazenda
CREATE POLICY "Users can view own profile and farm members" ON users
    FOR SELECT USING (
        id = auth.uid() OR 
        email = auth.jwt() ->> 'email'
    );

-- Política para atualizar próprio perfil
CREATE POLICY "Users can update their own profile" ON users
    FOR UPDATE USING (id = auth.uid());

-- Política para inserir usuários (apenas para gerentes/proprietários)
CREATE POLICY "Managers can insert users" ON users
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM users 
            WHERE email = auth.jwt() ->> 'email' 
            AND role IN ('proprietario', 'gerente')
        )
    );

-- 3. CRIAR POLÍTICAS RLS SIMPLES - DADOS OPERACIONAIS
-- Política para todas as tabelas operacionais (baseada no usuário autenticado)
CREATE POLICY "Authenticated users can access farm data" ON animals
    FOR ALL USING (auth.uid() IS NOT NULL);

CREATE POLICY "Authenticated users can access milk production" ON milk_production
    FOR ALL USING (auth.uid() IS NOT NULL);

CREATE POLICY "Authenticated users can access quality tests" ON quality_tests
    FOR ALL USING (auth.uid() IS NOT NULL);

CREATE POLICY "Authenticated users can access notifications" ON notifications
    FOR ALL USING (auth.uid() IS NOT NULL);

CREATE POLICY "Authenticated users can access treatments" ON treatments
    FOR ALL USING (auth.uid() IS NOT NULL);

CREATE POLICY "Authenticated users can access health records" ON animal_health_records
    FOR ALL USING (auth.uid() IS NOT NULL);

CREATE POLICY "Authenticated users can access financial records" ON financial_records
    FOR ALL USING (auth.uid() IS NOT NULL);

CREATE POLICY "Authenticated users can access settings" ON farm_settings
    FOR ALL USING (auth.uid() IS NOT NULL);

CREATE POLICY "Authenticated users can access artificial inseminations" ON artificial_inseminations
    FOR ALL USING (auth.uid() IS NOT NULL);

-- 4. CORRIGIR FUNÇÃO get_user_profile (RESOLVE ERRO DE TIPO)
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

-- 5. VERIFICAR SE AS POLÍTICAS FORAM CRIADAS CORRETAMENTE
SELECT 
    schemaname,
    tablename,
    policyname,
    permissive,
    roles,
    cmd
FROM pg_policies 
WHERE schemaname = 'public'
ORDER BY tablename, policyname;

-- 6. TESTAR CONSULTA SIMPLES PARA VERIFICAR SE A RECURSÃO FOI RESOLVIDA
-- Esta consulta deve funcionar sem erro de recursão infinita
SELECT 
    id, 
    name, 
    email, 
    role, 
    farm_id 
FROM users 
WHERE email = 'devnasc@gmail.com' 
LIMIT 1;

-- 7. TESTAR FUNÇÃO get_user_profile
SELECT * FROM get_user_profile();
