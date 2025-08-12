-- =====================================================
-- CORREÇÃO DOS INDICADORES DE QUALIDADE
-- =====================================================

-- 1. Verificar se a tabela quality_tests existe
SELECT EXISTS (
    SELECT FROM information_schema.tables 
    WHERE table_schema = 'public' 
    AND table_name = 'quality_tests'
) as table_exists;

-- 2. Verificar estrutura da tabela quality_tests
SELECT column_name, data_type, is_nullable
FROM information_schema.columns 
WHERE table_name = 'quality_tests' 
AND table_schema = 'public'
ORDER BY ordinal_position;

-- 3. Inserir dados de teste para qualidade (se não existirem)
-- Primeiro, vamos pegar um farm_id válido
DO $$
DECLARE
    test_farm_id UUID;
    test_user_id UUID;
BEGIN
    -- Pegar o primeiro farm_id disponível
    SELECT id INTO test_farm_id FROM farms LIMIT 1;
    
    -- Pegar o primeiro user_id disponível
    SELECT id INTO test_user_id FROM users LIMIT 1;
    
    -- Inserir dados de teste apenas se não existirem
    IF test_farm_id IS NOT NULL AND test_user_id IS NOT NULL THEN
        INSERT INTO quality_tests (farm_id, user_id, test_date, fat_percentage, protein_percentage, scc, cbt, laboratory, observations)
        VALUES 
            (test_farm_id, test_user_id, CURRENT_DATE - INTERVAL '7 days', 3.8, 3.2, 150000, 50000, 'Laboratório Central', 'Amostra de teste - excelente'),
            (test_farm_id, test_user_id, CURRENT_DATE - INTERVAL '5 days', 3.5, 3.1, 180000, 60000, 'Laboratório Central', 'Amostra de teste - boa'),
            (test_farm_id, test_user_id, CURRENT_DATE - INTERVAL '3 days', 3.2, 2.9, 220000, 80000, 'Laboratório Central', 'Amostra de teste - regular'),
            (test_farm_id, test_user_id, CURRENT_DATE - INTERVAL '1 day', 3.6, 3.0, 160000, 55000, 'Laboratório Central', 'Amostra de teste - boa')
        ON CONFLICT DO NOTHING;
        
        RAISE NOTICE 'Dados de teste inseridos para farm_id: % e user_id: %', test_farm_id, test_user_id;
    ELSE
        RAISE NOTICE 'Não foi possível encontrar farm_id ou user_id válidos para inserir dados de teste';
    END IF;
END $$;

-- 4. Verificar dados inseridos
SELECT 
    test_date,
    fat_percentage,
    protein_percentage,
    scc,
    cbt,
    laboratory,
    observations
FROM quality_tests 
ORDER BY test_date DESC 
LIMIT 10;

-- 5. Calcular qualidade média para verificar se está funcionando
SELECT 
    AVG(fat_percentage) as avg_fat,
    AVG(protein_percentage) as avg_protein,
    AVG(scc) as avg_scc,
    AVG(cbt) as avg_cbt,
    COUNT(*) as total_tests
FROM quality_tests;

-- 6. Verificar se há dados de produção de leite para o indicador de última coleta
SELECT 
    COUNT(*) as total_production_records,
    MAX(created_at) as last_collection_time
FROM milk_production;

-- 7. Inserir dados de teste para produção de leite (se não existirem)
DO $$
DECLARE
    test_farm_id UUID;
    test_user_id UUID;
BEGIN
    -- Pegar o primeiro farm_id disponível
    SELECT id INTO test_farm_id FROM farms LIMIT 1;
    
    -- Pegar o primeiro user_id disponível
    SELECT id INTO test_user_id FROM users LIMIT 1;
    
    -- Inserir dados de teste apenas se não existirem
    IF test_farm_id IS NOT NULL AND test_user_id IS NOT NULL THEN
        INSERT INTO milk_production (farm_id, user_id, production_date, shift, volume_liters, temperature, observations)
        VALUES 
            (test_farm_id, test_user_id, CURRENT_DATE, 'manha', 150.5, 4.2, 'Coleta da manhã'),
            (test_farm_id, test_user_id, CURRENT_DATE, 'tarde', 120.3, 4.0, 'Coleta da tarde'),
            (test_farm_id, test_user_id, CURRENT_DATE - INTERVAL '1 day', 'manha', 145.8, 4.1, 'Coleta da manhã'),
            (test_farm_id, test_user_id, CURRENT_DATE - INTERVAL '1 day', 'tarde', 118.2, 4.3, 'Coleta da tarde')
        ON CONFLICT DO NOTHING;
        
        RAISE NOTICE 'Dados de produção de teste inseridos para farm_id: % e user_id: %', test_farm_id, test_user_id;
    ELSE
        RAISE NOTICE 'Não foi possível encontrar farm_id ou user_id válidos para inserir dados de produção';
    END IF;
END $$;

-- 8. Verificar dados de produção inseridos
SELECT 
    production_date,
    shift,
    volume_liters,
    temperature,
    created_at,
    observations
FROM milk_production 
ORDER BY created_at DESC 
LIMIT 10;
