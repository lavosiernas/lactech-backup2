-- Script para testar e verificar o funcionamento das solicitações de senha

-- 1. Verificar se a tabela password_requests existe e sua estrutura
SELECT 
    column_name, 
    data_type, 
    is_nullable,
    column_default
FROM information_schema.columns 
WHERE table_name = 'password_requests' 
ORDER BY ordinal_position;

-- 2. Verificar políticas RLS ativas na tabela
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
WHERE tablename = 'password_requests';

-- 3. Verificar se RLS está habilitado
SELECT 
    schemaname,
    tablename,
    rowsecurity
FROM pg_tables 
WHERE tablename = 'password_requests';

-- 4. Contar registros na tabela
SELECT 
    COUNT(*) as total_requests,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_requests,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_requests,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_requests
FROM password_requests;

-- 5. Mostrar as últimas 5 solicitações (se existirem)
SELECT 
    id,
    user_id,
    type,
    reason,
    status,
    created_at,
    CASE 
        WHEN new_password IS NOT NULL THEN 'Tem nova senha'
        WHEN notes LIKE '%NOVA SENHA:%' THEN 'Senha nas notas'
        ELSE 'Sem nova senha'
    END as password_info
FROM password_requests 
ORDER BY created_at DESC 
LIMIT 5;

-- 6. Verificar se existem usuários na tabela users
SELECT COUNT(*) as total_users FROM users;
