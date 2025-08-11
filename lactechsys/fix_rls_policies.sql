-- =====================================================
-- CORREÇÃO DAS POLÍTICAS RLS - RESOLVER RECURSÃO INFINITA
-- =====================================================
-- Execute este SQL no Supabase Dashboard para corrigir os erros de recursão infinita
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

-- 2. CRIAR POLÍTICAS RLS CORRIGIDAS - USERS
-- Política para visualizar usuários da mesma fazenda (sem recursão)
CREATE POLICY "Users can view farm members" ON users
    FOR SELECT USING (
        farm_id = (
            SELECT farm_id FROM users WHERE id = auth.uid() LIMIT 1
        )
    );

-- Política para atualizar próprio perfil
CREATE POLICY "Users can update their own profile" ON users
    FOR UPDATE USING (id = auth.uid());

-- Política para gerentes atualizarem usuários da fazenda
CREATE POLICY "Managers can update farm users" ON users
    FOR UPDATE USING (
        farm_id = (
            SELECT farm_id FROM users WHERE id = auth.uid() LIMIT 1
        ) AND (
            SELECT role FROM users WHERE id = auth.uid() LIMIT 1
        ) IN ('proprietario', 'gerente')
    );

-- Política para gerentes inserirem usuários
CREATE POLICY "Managers can insert users" ON users
    FOR INSERT WITH CHECK (
        farm_id = (
            SELECT farm_id FROM users WHERE id = auth.uid() LIMIT 1
        ) AND (
            SELECT role FROM users WHERE id = auth.uid() LIMIT 1
        ) IN ('proprietario', 'gerente')
    );

-- 3. CRIAR POLÍTICAS RLS CORRIGIDAS - DADOS OPERACIONAIS
-- Política para animais
CREATE POLICY "Farm members can access farm data" ON animals
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE id = auth.uid() LIMIT 1
        )
    );

-- Política para produção de leite
CREATE POLICY "Farm members can access milk production" ON milk_production
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE id = auth.uid() LIMIT 1
        )
    );

-- Política para testes de qualidade
CREATE POLICY "Farm members can access quality tests" ON quality_tests
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE id = auth.uid() LIMIT 1
        )
    );

-- Política para notificações
CREATE POLICY "Farm members can access notifications" ON notifications
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE id = auth.uid() LIMIT 1
        )
    );

-- Política para tratamentos
CREATE POLICY "Farm members can access treatments" ON treatments
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE id = auth.uid() LIMIT 1
        )
    );

-- Política para registros de saúde
CREATE POLICY "Farm members can access health records" ON animal_health_records
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE id = auth.uid() LIMIT 1
        )
    );

-- Política para registros financeiros
CREATE POLICY "Farm members can access financial records" ON financial_records
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE id = auth.uid() LIMIT 1
        )
    );

-- Política para configurações da fazenda
CREATE POLICY "Farm members can access settings" ON farm_settings
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE id = auth.uid() LIMIT 1
        )
    );

-- Política para inseminações artificiais
CREATE POLICY "Farm members can access artificial inseminations" ON artificial_inseminations
    FOR ALL USING (
        farm_id = (
            SELECT farm_id FROM users WHERE id = auth.uid() LIMIT 1
        )
    );

-- 4. VERIFICAR SE AS POLÍTICAS FORAM CRIADAS CORRETAMENTE
SELECT 
    schemaname,
    tablename,
    policyname,
    permissive,
    roles,
    cmd,
    qual,
    with_check
FROM pg_policies 
WHERE schemaname = 'public'
ORDER BY tablename, policyname;

-- 5. TESTAR CONSULTA SIMPLES PARA VERIFICAR SE A RECURSÃO FOI RESOLVIDA
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
