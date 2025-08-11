-- =====================================================
-- SCRIPT DE VALIDAÇÃO DO BANCO DE DADOS LACTECH
-- Verificação de Integridade e Prevenção de Recursão
-- =====================================================

-- =====================================================
-- 1. VERIFICAÇÃO DE DEPENDÊNCIAS CIRCULARES
-- =====================================================

-- Função para detectar dependências circulares entre tabelas
CREATE OR REPLACE FUNCTION check_circular_dependencies()
RETURNS TABLE(
    table_name TEXT,
    referenced_table TEXT,
    constraint_name TEXT,
    potential_issue TEXT
) AS $$
BEGIN
    RETURN QUERY
    WITH RECURSIVE dependency_chain AS (
        -- Caso base: todas as foreign keys
        SELECT 
            tc.table_name::TEXT as source_table,
            ccu.table_name::TEXT as target_table,
            tc.constraint_name::TEXT,
            1 as depth,
            ARRAY[tc.table_name::TEXT] as path
        FROM information_schema.table_constraints tc
        JOIN information_schema.key_column_usage kcu 
            ON tc.constraint_name = kcu.constraint_name
        JOIN information_schema.constraint_column_usage ccu 
            ON ccu.constraint_name = tc.constraint_name
        WHERE tc.constraint_type = 'FOREIGN KEY'
        AND tc.table_schema = 'public'
        
        UNION ALL
        
        -- Caso recursivo: seguir a cadeia de dependências
        SELECT 
            dc.source_table,
            tc2.table_name::TEXT,
            tc2.constraint_name::TEXT,
            dc.depth + 1,
            dc.path || tc2.table_name::TEXT
        FROM dependency_chain dc
        JOIN information_schema.table_constraints tc2 
            ON dc.target_table = tc2.table_name::TEXT
        JOIN information_schema.key_column_usage kcu2 
            ON tc2.constraint_name = kcu2.constraint_name
        JOIN information_schema.constraint_column_usage ccu2 
            ON ccu2.constraint_name = tc2.constraint_name
        WHERE tc2.constraint_type = 'FOREIGN KEY'
        AND tc2.table_schema = 'public'
        AND dc.depth < 10  -- Limite para evitar loops infinitos
        AND NOT (tc2.table_name::TEXT = ANY(dc.path))  -- Evitar ciclos
    )
    SELECT 
        dc.source_table,
        dc.target_table,
        dc.constraint_name,
        CASE 
            WHEN dc.source_table = dc.target_table THEN 'SELF-REFERENCE DETECTED'
            WHEN dc.depth > 5 THEN 'DEEP DEPENDENCY CHAIN'
            ELSE 'OK'
        END
    FROM dependency_chain dc
    WHERE dc.source_table = dc.target_table  -- Detectar auto-referências
    OR dc.depth > 5;  -- Detectar cadeias muito profundas
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- 2. VERIFICAÇÃO DE INTEGRIDADE REFERENCIAL
-- =====================================================

-- Função para verificar integridade das foreign keys
CREATE OR REPLACE FUNCTION check_referential_integrity()
RETURNS TABLE(
    table_name TEXT,
    column_name TEXT,
    referenced_table TEXT,
    referenced_column TEXT,
    orphaned_records BIGINT
) AS $$
DECLARE
    rec RECORD;
    sql_query TEXT;
    orphaned_count BIGINT;
BEGIN
    FOR rec IN 
        SELECT 
            tc.table_name,
            kcu.column_name,
            ccu.table_name AS referenced_table,
            ccu.column_name AS referenced_column
        FROM information_schema.table_constraints tc
        JOIN information_schema.key_column_usage kcu 
            ON tc.constraint_name = kcu.constraint_name
        JOIN information_schema.constraint_column_usage ccu 
            ON ccu.constraint_name = tc.constraint_name
        WHERE tc.constraint_type = 'FOREIGN KEY'
        AND tc.table_schema = 'public'
    LOOP
        -- Construir query dinâmica para verificar registros órfãos
        sql_query := format(
            'SELECT COUNT(*) FROM %I t1 LEFT JOIN %I t2 ON t1.%I = t2.%I WHERE t1.%I IS NOT NULL AND t2.%I IS NULL',
            rec.table_name,
            rec.referenced_table,
            rec.column_name,
            rec.referenced_column,
            rec.column_name,
            rec.referenced_column
        );
        
        EXECUTE sql_query INTO orphaned_count;
        
        IF orphaned_count > 0 THEN
            table_name := rec.table_name;
            column_name := rec.column_name;
            referenced_table := rec.referenced_table;
            referenced_column := rec.referenced_column;
            orphaned_records := orphaned_count;
            RETURN NEXT;
        END IF;
    END LOOP;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- 3. VERIFICAÇÃO DE POLÍTICAS RLS
-- =====================================================

-- Função para verificar se todas as tabelas têm RLS habilitado
CREATE OR REPLACE FUNCTION check_rls_policies()
RETURNS TABLE(
    table_name TEXT,
    rls_enabled BOOLEAN,
    policy_count INTEGER,
    status TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        t.tablename::TEXT,
        t.rowsecurity,
        COALESCE(p.policy_count, 0)::INTEGER,
        CASE 
            WHEN NOT t.rowsecurity THEN 'RLS NOT ENABLED'
            WHEN COALESCE(p.policy_count, 0) = 0 THEN 'NO POLICIES DEFINED'
            ELSE 'OK'
        END
    FROM pg_tables t
    LEFT JOIN (
        SELECT 
            tablename,
            COUNT(*) as policy_count
        FROM pg_policies 
        WHERE schemaname = 'public'
        GROUP BY tablename
    ) p ON t.tablename = p.tablename
    WHERE t.schemaname = 'public'
    AND t.tablename NOT LIKE 'pg_%'
    ORDER BY t.tablename;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- 4. VERIFICAÇÃO DE TRIGGERS E FUNÇÕES
-- =====================================================

-- Função para verificar triggers que podem causar recursão
CREATE OR REPLACE FUNCTION check_trigger_recursion()
RETURNS TABLE(
    trigger_name TEXT,
    table_name TEXT,
    function_name TEXT,
    event_manipulation TEXT,
    potential_recursion TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        t.trigger_name::TEXT,
        t.event_object_table::TEXT,
        t.action_statement::TEXT,
        t.event_manipulation::TEXT,
        CASE 
            WHEN t.action_statement LIKE '%UPDATE%' AND t.event_manipulation = 'UPDATE' THEN 'POTENTIAL UPDATE RECURSION'
            WHEN t.action_statement LIKE '%INSERT%' AND t.event_manipulation = 'INSERT' THEN 'POTENTIAL INSERT RECURSION'
            ELSE 'OK'
        END
    FROM information_schema.triggers t
    WHERE t.trigger_schema = 'public'
    ORDER BY t.event_object_table, t.trigger_name;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- 5. VERIFICAÇÃO DE ÍNDICES E PERFORMANCE
-- =====================================================

-- Função para verificar índices em foreign keys
CREATE OR REPLACE FUNCTION check_foreign_key_indexes()
RETURNS TABLE(
    table_name TEXT,
    column_name TEXT,
    has_index BOOLEAN,
    recommendation TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        kcu.table_name::TEXT,
        kcu.column_name::TEXT,
        EXISTS(
            SELECT 1 FROM pg_indexes i 
            WHERE i.tablename = kcu.table_name 
            AND i.indexdef LIKE '%' || kcu.column_name || '%'
        ) as has_index,
        CASE 
            WHEN NOT EXISTS(
                SELECT 1 FROM pg_indexes i 
                WHERE i.tablename = kcu.table_name 
                AND i.indexdef LIKE '%' || kcu.column_name || '%'
            ) THEN 'CREATE INDEX RECOMMENDED'
            ELSE 'OK'
        END
    FROM information_schema.key_column_usage kcu
    JOIN information_schema.table_constraints tc 
        ON kcu.constraint_name = tc.constraint_name
    WHERE tc.constraint_type = 'FOREIGN KEY'
    AND kcu.table_schema = 'public'
    ORDER BY kcu.table_name, kcu.column_name;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- 6. SCRIPT DE VALIDAÇÃO COMPLETA
-- =====================================================

-- Executar todas as verificações
DO $$
DECLARE
    rec RECORD;
    error_count INTEGER := 0;
BEGIN
    RAISE NOTICE '==========================================';
    RAISE NOTICE 'INICIANDO VALIDAÇÃO DO BANCO DE DADOS';
    RAISE NOTICE '==========================================';
    
    -- 1. Verificar dependências circulares
    RAISE NOTICE '';
    RAISE NOTICE '1. VERIFICANDO DEPENDÊNCIAS CIRCULARES...';
    
    FOR rec IN SELECT * FROM check_circular_dependencies() LOOP
        RAISE WARNING 'DEPENDÊNCIA CIRCULAR: % -> % (%)', rec.table_name, rec.referenced_table, rec.potential_issue;
        error_count := error_count + 1;
    END LOOP;
    
    IF NOT FOUND THEN
        RAISE NOTICE '✓ Nenhuma dependência circular detectada';
    END IF;
    
    -- 2. Verificar integridade referencial
    RAISE NOTICE '';
    RAISE NOTICE '2. VERIFICANDO INTEGRIDADE REFERENCIAL...';
    
    FOR rec IN SELECT * FROM check_referential_integrity() LOOP
        RAISE WARNING 'REGISTROS ÓRFÃOS: %.% -> %.% (% registros)', 
            rec.table_name, rec.column_name, rec.referenced_table, rec.referenced_column, rec.orphaned_records;
        error_count := error_count + 1;
    END LOOP;
    
    IF NOT FOUND THEN
        RAISE NOTICE '✓ Integridade referencial OK';
    END IF;
    
    -- 3. Verificar políticas RLS
    RAISE NOTICE '';
    RAISE NOTICE '3. VERIFICANDO POLÍTICAS RLS...';
    
    FOR rec IN SELECT * FROM check_rls_policies() WHERE status != 'OK' LOOP
        RAISE WARNING 'RLS: % - %', rec.table_name, rec.status;
        error_count := error_count + 1;
    END LOOP;
    
    -- 4. Verificar triggers
    RAISE NOTICE '';
    RAISE NOTICE '4. VERIFICANDO TRIGGERS...';
    
    FOR rec IN SELECT * FROM check_trigger_recursion() WHERE potential_recursion != 'OK' LOOP
        RAISE WARNING 'TRIGGER: % em % - %', rec.trigger_name, rec.table_name, rec.potential_recursion;
        error_count := error_count + 1;
    END LOOP;
    
    -- 5. Verificar índices
    RAISE NOTICE '';
    RAISE NOTICE '5. VERIFICANDO ÍNDICES...';
    
    FOR rec IN SELECT * FROM check_foreign_key_indexes() WHERE recommendation != 'OK' LOOP
        RAISE NOTICE 'ÍNDICE: %.% - %', rec.table_name, rec.column_name, rec.recommendation;
    END LOOP;
    
    -- Resultado final
    RAISE NOTICE '';
    RAISE NOTICE '==========================================';
    IF error_count = 0 THEN
        RAISE NOTICE '✓ VALIDAÇÃO CONCLUÍDA COM SUCESSO!';
        RAISE NOTICE '✓ Nenhum erro crítico encontrado';
    ELSE
        RAISE WARNING '⚠ VALIDAÇÃO CONCLUÍDA COM % AVISOS', error_count;
    END IF;
    RAISE NOTICE '==========================================';
END;
$$;

-- =====================================================
-- 7. LIMPEZA DAS FUNÇÕES DE VALIDAÇÃO
-- =====================================================

-- Remover funções de validação após uso (opcional)
-- DROP FUNCTION IF EXISTS check_circular_dependencies();
-- DROP FUNCTION IF EXISTS check_referential_integrity();
-- DROP FUNCTION IF EXISTS check_rls_policies();
-- DROP FUNCTION IF EXISTS check_trigger_recursion();
-- DROP FUNCTION IF EXISTS check_foreign_key_indexes();

SELECT 'Script de validação executado com sucesso!' AS status;