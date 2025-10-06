-- =====================================================
-- CORREÇÃO DEFINITIVA DAS POLÍTICAS RLS DA TABELA USERS
-- =====================================================
-- Versão 2: Políticas simples sem recursão
-- =====================================================

-- 1. REMOVER TODAS AS POLÍTICAS EXISTENTES
DROP POLICY IF EXISTS "Users are viewable by farm users" ON users;
DROP POLICY IF EXISTS "Users are insertable by authenticated users" ON users;
DROP POLICY IF EXISTS "Users are updatable by themselves or managers" ON users;
DROP POLICY IF EXISTS "Users are insertable by farm managers" ON users;
DROP POLICY IF EXISTS "Users are updatable by farm managers" ON users;

-- 2. CRIAR POLÍTICAS SIMPLES (sem recursão)
-- Política para SELECT: usuário pode ver seu próprio perfil
CREATE POLICY "Users can view own profile" ON users
    FOR SELECT USING (
        auth.uid() = id
    );

-- Política para INSERT: qualquer usuário autenticado pode inserir
CREATE POLICY "Users can insert if authenticated" ON users
    FOR INSERT WITH CHECK (
        auth.uid() IS NOT NULL
    );

-- Política para UPDATE: usuário pode atualizar seu próprio perfil
CREATE POLICY "Users can update own profile" ON users
    FOR UPDATE USING (
        auth.uid() = id
    );

-- 3. CONFIRMAÇÃO
DO $$
BEGIN
    RAISE NOTICE '✅ Políticas RLS da tabela users corrigidas (versão 2)!';
    RAISE NOTICE '🔧 Recursão infinita completamente removida';
    RAISE NOTICE '📋 Políticas simples criadas: SELECT, INSERT, UPDATE';
    RAISE NOTICE '⚠️ ATENÇÃO: Políticas básicas - ajuste conforme necessário';
END $$;
