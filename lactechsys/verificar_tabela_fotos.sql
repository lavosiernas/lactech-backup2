-- =====================================================
-- SCRIPT PARA VERIFICAR CONFIGURAÇÃO DA TABELA DE FOTOS
-- =====================================================

-- 1. Verificar se a tabela users existe e tem a coluna profile_photo_url
SELECT 
    'TABELA USERS - VERIFICAÇÃO' as verificacao,
    EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_name = 'users' 
        AND table_schema = 'public'
    ) as tabela_existe,
    EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'users' 
        AND column_name = 'profile_photo_url'
        AND table_schema = 'public'
    ) as coluna_foto_existe;

-- 2. Verificar estrutura da coluna profile_photo_url
SELECT 
    column_name,
    data_type,
    is_nullable,
    column_default
FROM information_schema.columns 
WHERE table_name = 'users' 
AND column_name = 'profile_photo_url'
AND table_schema = 'public';

-- 3. Verificar se há outras colunas relacionadas a imagens na tabela users
SELECT 
    column_name,
    data_type,
    is_nullable
FROM information_schema.columns 
WHERE table_name = 'users' 
AND table_schema = 'public'
AND (
    column_name LIKE '%photo%' 
    OR column_name LIKE '%image%' 
    OR column_name LIKE '%logo%' 
    OR column_name LIKE '%foto%' 
    OR column_name LIKE '%imagem%'
);

-- 4. Verificar se existem outras tabelas que podem armazenar fotos
SELECT 
    table_name,
    'Possível tabela de fotos' as observacao
FROM information_schema.tables 
WHERE table_schema = 'public'
AND (
    table_name LIKE '%photo%' 
    OR table_name LIKE '%image%' 
    OR table_name LIKE '%foto%' 
    OR table_name LIKE '%imagem%'
    OR table_name LIKE '%media%'
    OR table_name LIKE '%file%'
    OR table_name LIKE '%upload%'
);

-- 5. Verificar se há dados de fotos na tabela users
SELECT 
    COUNT(*) as total_usuarios,
    COUNT(profile_photo_url) as usuarios_com_foto,
    COUNT(*) - COUNT(profile_photo_url) as usuarios_sem_foto,
    ROUND(
        (COUNT(profile_photo_url)::DECIMAL / COUNT(*)) * 100, 2
    ) as percentual_com_foto
FROM users;

-- 6. Verificar exemplos de URLs de fotos (primeiros 5)
SELECT 
    id,
    name,
    profile_photo_url,
    CASE 
        WHEN profile_photo_url IS NOT NULL THEN 'Tem foto'
        ELSE 'Sem foto'
    END as status_foto
FROM users 
WHERE profile_photo_url IS NOT NULL
LIMIT 5;

-- 7. Verificar se há RLS (Row Level Security) configurado para a tabela users
SELECT 
    schemaname,
    tablename,
    rowsecurity as rls_ativado
FROM pg_tables 
WHERE tablename = 'users';

-- 8. Verificar políticas RLS relacionadas a fotos
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
WHERE tablename = 'users'
AND (
    policyname LIKE '%photo%' 
    OR policyname LIKE '%image%' 
    OR policyname LIKE '%foto%'
);

-- 9. Verificar se há storage buckets configurados (Supabase)
SELECT 
    'Verificar storage buckets no Supabase Dashboard' as observacao,
    'Acesse: https://app.supabase.com/project/[SEU_PROJETO]/storage/buckets' as link;

-- 10. Resumo da configuração
SELECT 
    'RESUMO DA CONFIGURAÇÃO DE FOTOS' as titulo,
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_name = 'users' 
            AND column_name = 'profile_photo_url'
            AND table_schema = 'public'
        ) THEN '✅ Tabela users tem coluna profile_photo_url'
        ELSE '❌ Tabela users NÃO tem coluna profile_photo_url'
    END as status_tabela_fotos,
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_name = 'users' 
            AND column_name = 'report_farm_logo_base64'
            AND table_schema = 'public'
        ) THEN '✅ Tabela users tem coluna para logo da fazenda'
        ELSE '❌ Tabela users NÃO tem coluna para logo da fazenda'
    END as status_logo_fazenda,
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_name = 'users' 
            AND column_name = 'report_system_logo_base64'
            AND table_schema = 'public'
        ) THEN '✅ Tabela users tem coluna para logo do sistema'
        ELSE '❌ Tabela users NÃO tem coluna para logo do sistema'
    END as status_logo_sistema;
