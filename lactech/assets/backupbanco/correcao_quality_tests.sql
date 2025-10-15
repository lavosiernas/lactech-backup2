-- =====================================================
-- CORREÇÃO PARA TESTES DE QUALIDADE - USER_ID NULL
-- Sistema de Gestão de Fazendas Leiteiras - LacTech
-- =====================================================

-- =====================================================
-- 1. FUNÇÕES PARA TESTES DE QUALIDADE
-- =====================================================

-- 1.1 Registrar teste de qualidade - CORREÇÃO PRINCIPAL
CREATE OR REPLACE FUNCTION register_quality_test(
    p_test_date DATE,
    p_fat_percentage DECIMAL DEFAULT NULL,
    p_protein_percentage DECIMAL DEFAULT NULL,
    p_scc INTEGER DEFAULT NULL,
    p_cbt INTEGER DEFAULT NULL,
    p_laboratory TEXT DEFAULT NULL,
    p_observations TEXT DEFAULT ''
)
RETURNS UUID AS $$
DECLARE
    user_farm_id UUID;
    quality_test_id UUID;
BEGIN
    -- Obter farm_id do usuário atual
    SELECT farm_id INTO user_farm_id FROM users WHERE id = auth.uid();
    
    -- Verificar se o usuário está autenticado
    IF auth.uid() IS NULL THEN
        RAISE EXCEPTION 'Usuário não autenticado';
    END IF;
    
    INSERT INTO quality_tests (
        farm_id, user_id, test_date, fat_percentage, protein_percentage, 
        scc, cbt, laboratory, observations
    ) VALUES (
        user_farm_id, auth.uid(), p_test_date, p_fat_percentage, p_protein_percentage,
        p_scc, p_cbt, p_laboratory, p_observations
    ) RETURNING id INTO quality_test_id;
    
    RETURN quality_test_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 1.2 Atualizar teste de qualidade
CREATE OR REPLACE FUNCTION update_quality_test(
    p_test_id UUID,
    p_test_date DATE DEFAULT NULL,
    p_fat_percentage DECIMAL DEFAULT NULL,
    p_protein_percentage DECIMAL DEFAULT NULL,
    p_scc INTEGER DEFAULT NULL,
    p_cbt INTEGER DEFAULT NULL,
    p_laboratory TEXT DEFAULT NULL,
    p_observations TEXT DEFAULT NULL
)
RETURNS VOID AS $$
DECLARE
    user_farm_id UUID;
BEGIN
    -- Obter farm_id do usuário atual
    SELECT farm_id INTO user_farm_id FROM users WHERE id = auth.uid();
    
    -- Verificar se o teste pertence à fazenda do usuário
    IF NOT EXISTS (
        SELECT 1 FROM quality_tests 
        WHERE id = p_test_id AND farm_id = user_farm_id
    ) THEN
        RAISE EXCEPTION 'Teste de qualidade não encontrado ou não pertence à sua fazenda';
    END IF;
    
    UPDATE quality_tests SET 
        test_date = COALESCE(p_test_date, test_date),
        fat_percentage = COALESCE(p_fat_percentage, fat_percentage),
        protein_percentage = COALESCE(p_protein_percentage, protein_percentage),
        scc = COALESCE(p_scc, scc),
        cbt = COALESCE(p_cbt, cbt),
        laboratory = COALESCE(p_laboratory, laboratory),
        observations = COALESCE(p_observations, observations),
        updated_at = NOW()
    WHERE id = p_test_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 1.3 Excluir teste de qualidade
CREATE OR REPLACE FUNCTION delete_quality_test(p_test_id UUID)
RETURNS VOID AS $$
DECLARE
    user_farm_id UUID;
BEGIN
    -- Obter farm_id do usuário atual
    SELECT farm_id INTO user_farm_id FROM users WHERE id = auth.uid();
    
    -- Verificar se o teste pertence à fazenda do usuário
    IF NOT EXISTS (
        SELECT 1 FROM quality_tests 
        WHERE id = p_test_id AND farm_id = user_farm_id
    ) THEN
        RAISE EXCEPTION 'Teste de qualidade não encontrado ou não pertence à sua fazenda';
    END IF;
    
    DELETE FROM quality_tests WHERE id = p_test_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 1.4 Obter testes de qualidade da fazenda
CREATE OR REPLACE FUNCTION get_farm_quality_tests(
    p_limit INTEGER DEFAULT 50,
    p_offset INTEGER DEFAULT 0
)
RETURNS TABLE(
    test_id UUID,
    test_date DATE,
    fat_percentage DECIMAL,
    protein_percentage DECIMAL,
    scc INTEGER,
    cbt INTEGER,
    laboratory TEXT,
    observations TEXT,
    quality_score DECIMAL,
    created_at TIMESTAMP WITH TIME ZONE
) AS $$
DECLARE
    user_farm_id UUID;
BEGIN
    -- Obter farm_id do usuário atual
    SELECT farm_id INTO user_farm_id FROM users WHERE id = auth.uid();
    
    RETURN QUERY
    SELECT 
        qt.id,
        qt.test_date,
        qt.fat_percentage,
        qt.protein_percentage,
        qt.scc,
        qt.cbt,
        qt.laboratory,
        qt.observations,
        qt.quality_score,
        qt.created_at
    FROM quality_tests qt
    WHERE qt.farm_id = user_farm_id
    ORDER BY qt.test_date DESC, qt.created_at DESC
    LIMIT p_limit OFFSET p_offset;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- =====================================================
-- 2. TRIGGER PARA CALCULAR NOTA DE QUALIDADE
-- =====================================================

-- 2.1 Função para calcular nota de qualidade
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

-- 2.2 Criar trigger (se não existir)
DROP TRIGGER IF EXISTS calculate_quality_score_trigger ON quality_tests;
CREATE TRIGGER calculate_quality_score_trigger
    BEFORE INSERT OR UPDATE ON quality_tests
    FOR EACH ROW EXECUTE FUNCTION calculate_quality_score();

-- =====================================================
-- 3. CORREÇÃO DE DADOS EXISTENTES (se necessário)
-- =====================================================

-- 3.1 Verificar se existem registros com user_id NULL
DO $$
DECLARE
    null_user_count INTEGER;
BEGIN
    SELECT COUNT(*) INTO null_user_count FROM quality_tests WHERE user_id IS NULL;
    
    IF null_user_count > 0 THEN
        RAISE NOTICE 'Encontrados % registros com user_id NULL. Corrigindo...', null_user_count;
        
        -- Atualizar registros com user_id NULL usando o primeiro usuário ativo da fazenda
        UPDATE quality_tests 
        SET user_id = (
            SELECT u.id 
            FROM users u 
            WHERE u.farm_id = quality_tests.farm_id 
            AND u.is_active = true 
            LIMIT 1
        )
        WHERE user_id IS NULL;
        
        RAISE NOTICE 'Correção concluída!';
    ELSE
        RAISE NOTICE 'Nenhum registro com user_id NULL encontrado.';
    END IF;
END $$;

-- =====================================================
-- 4. VERIFICAÇÕES
-- =====================================================

-- 4.1 Verificar se as funções foram criadas
SELECT 
    routine_name,
    routine_type
FROM information_schema.routines 
WHERE routine_schema = 'public' 
AND routine_name LIKE '%quality%'
ORDER BY routine_name;

-- 4.2 Verificar se o trigger foi criado
SELECT 
    trigger_name,
    event_manipulation,
    action_statement
FROM information_schema.triggers 
WHERE trigger_name = 'calculate_quality_score_trigger';

-- 4.3 Verificar se não há mais registros com user_id NULL
SELECT 
    COUNT(*) as registros_com_user_id_null
FROM quality_tests 
WHERE user_id IS NULL;

-- =====================================================
-- 5. EXEMPLOS DE USO
-- =====================================================

/*
-- Exemplo 1: Registrar um novo teste de qualidade
SELECT register_quality_test(
    '2024-01-15'::DATE,
    3.8,  -- fat_percentage
    3.2,  -- protein_percentage
    150000,  -- scc
    50000,   -- cbt
    'Laboratório Central',  -- laboratory
    'Amostra excelente'     -- observations
);

-- Exemplo 2: Obter testes da fazenda
SELECT * FROM get_farm_quality_tests(10, 0);

-- Exemplo 3: Atualizar um teste
SELECT update_quality_test(
    'test-id-uuid',
    '2024-01-15'::DATE,
    3.9,  -- novo fat_percentage
    NULL, -- manter protein_percentage atual
    NULL, -- manter scc atual
    NULL, -- manter cbt atual
    'Novo Laboratório',  -- novo laboratory
    'Observação atualizada'  -- nova observations
);

-- Exemplo 4: Excluir um teste
SELECT delete_quality_test('test-id-uuid');
*/

SELECT 'Correção para testes de qualidade aplicada com sucesso!' AS status;
