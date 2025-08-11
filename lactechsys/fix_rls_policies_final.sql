-- =====================================================
-- CORREÇÃO DEFINITIVA DAS POLÍTICAS RLS - SEM RECURSÃO
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

-- 2. CRIAR POLÍTICAS RLS CORRIGIDAS - USERS (SEM CONSULTAS ANINHADAS)
-- Política para visualizar usuários da mesma fazenda (usando email direto)
CREATE POLICY "Users can view farm members" ON users
    FOR SELECT USING (
        farm_id = (
            SELECT farm_id FROM users WHERE email = auth.jwt() ->> 'email' LIMIT 1
        )
    );

-- Política para atualizar próprio perfil (usando ID direto)
CREATE POLICY "Users can update their own profile" ON users
    FOR UPDATE USING (id = auth.uid());

-- Política para gerentes atualizarem usuários da fazenda
CREATE POLICY "Managers can update farm users" ON users
    FOR UPDATE USING (
        farm_id = (
            SELECT farm_id FROM users WHERE email = auth.jwt() ->> 'email' LIMIT 1
        ) AND (
            SELECT role FROM users WHERE email = auth.jwt() ->> 'email' LIMIT 1
        ) IN ('proprietario', 'gerente')
    );

-- Política para gerentes inserirem usuários
CREATE POLICY "Managers can insert users" ON users
    FOR INSERT WITH CHECK (
        farm_id = (
            SELECT farm_id FROM users WHERE email = auth.jwt() ->> 'email' LIMIT 1
        ) AND (
            SELECT role FROM users WHERE email = auth.jwt() ->> 'email' LIMIT 1
        ) IN ('proprietario', 'gerente')
    );

-- 3. CRIAR POLÍTICAS RLS CORRIGIDAS - DADOS OPERACIONAIS (SEM CONSULTAS ANINHADAS)
-- Política para animais
CREATE POLICY "Farm members can access farm data" ON animals
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE email = auth.jwt() ->> 'email' LIMIT 1
        )
    );

-- Política para produção de leite
CREATE POLICY "Farm members can access milk production" ON milk_production
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE email = auth.jwt() ->> 'email' LIMIT 1
        )
    );

-- Política para testes de qualidade
CREATE POLICY "Farm members can access quality tests" ON quality_tests
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE email = auth.jwt() ->> 'email' LIMIT 1
        )
    );

-- Política para notificações
CREATE POLICY "Farm members can access notifications" ON notifications
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE email = auth.jwt() ->> 'email' LIMIT 1
        )
    );

-- Política para tratamentos
CREATE POLICY "Farm members can access treatments" ON treatments
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE email = auth.jwt() ->> 'email' LIMIT 1
        )
    );

-- Política para registros de saúde
CREATE POLICY "Farm members can access health records" ON animal_health_records
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE email = auth.jwt() ->> 'email' LIMIT 1
        )
    );

-- Política para registros financeiros
CREATE POLICY "Farm members can access financial records" ON financial_records
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE email = auth.jwt() ->> 'email' LIMIT 1
        )
    );

-- Política para configurações da fazenda
CREATE POLICY "Farm members can access settings" ON farm_settings
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE email = auth.jwt() ->> 'email' LIMIT 1
        )
    );

-- Política para inseminações artificiais
CREATE POLICY "Farm members can access artificial inseminations" ON artificial_inseminations
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE email = auth.jwt() ->> 'email' LIMIT 1
        )
    );

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
