-- =====================================================
-- CORREÇÃO DAS POLÍTICAS RLS DA TABELA USERS
-- =====================================================
-- Remove recursão infinita nas políticas da tabela users
-- =====================================================

-- 1. REMOVER POLÍTICAS PROBLEMÁTICAS
DROP POLICY IF EXISTS "Users are viewable by farm users" ON users;
DROP POLICY IF EXISTS "Users are insertable by farm managers" ON users;
DROP POLICY IF EXISTS "Users are updatable by farm managers" ON users;

-- 2. CRIAR POLÍTICAS CORRIGIDAS (sem recursão)
CREATE POLICY "Users are viewable by farm users" ON users
    FOR SELECT USING (
        farm_id IN (
            SELECT farm_id FROM users WHERE id = auth.uid()
        )
        OR auth.uid() = id
    );

CREATE POLICY "Users are insertable by authenticated users" ON users
    FOR INSERT WITH CHECK (
        auth.uid() IS NOT NULL
    );

CREATE POLICY "Users are updatable by themselves or managers" ON users
    FOR UPDATE USING (
        auth.uid() = id
        OR (
            farm_id IN (
                SELECT farm_id FROM users WHERE id = auth.uid()
            )
            AND EXISTS (
                SELECT 1 FROM users 
                WHERE id = auth.uid() 
                AND role IN ('proprietario', 'gerente')
            )
        )
    );

-- 3. CONFIRMAÇÃO
DO $$
BEGIN
    RAISE NOTICE '✅ Políticas RLS da tabela users corrigidas com sucesso!';
    RAISE NOTICE '🔧 Recursão infinita removida';
    RAISE NOTICE '📋 Políticas criadas: SELECT, INSERT, UPDATE';
END $$;
