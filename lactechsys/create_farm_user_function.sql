-- =====================================================
-- FUNÇÃO CREATE_FARM_USER PARA CONTAS SECUNDÁRIAS
-- =====================================================
-- Execute este arquivo no SQL Editor do Supabase
-- =====================================================

-- Remover função RPC se existir
DROP FUNCTION IF EXISTS create_farm_user(UUID, TEXT, TEXT, TEXT, TEXT, UUID, TEXT);

-- Função RPC para criar usuários da fazenda (contas secundárias)
-- IMPORTANTE: Esta função só cria o registro na tabela users
-- A conta no Supabase Auth deve ser criada ANTES pelo JavaScript
CREATE OR REPLACE FUNCTION create_farm_user(
    p_user_id UUID,
    p_email TEXT,
    p_name TEXT,
    p_whatsapp TEXT,
    p_role TEXT,
    p_farm_id UUID,
    p_profile_photo_url TEXT DEFAULT NULL
)
RETURNS JSON AS $$
DECLARE
    result JSON;
    auth_user_exists BOOLEAN := false;
BEGIN
    -- Verificar se o usuário existe no auth.users
    SELECT EXISTS(
        SELECT 1 FROM auth.users WHERE id = p_user_id
    ) INTO auth_user_exists;
    
    -- Se o usuário não existe no Auth, retornar erro
    IF NOT auth_user_exists THEN
        result := json_build_object(
            'success', false,
            'error', 'AUTH_USER_NOT_FOUND',
            'message', 'Usuário não encontrado no Supabase Auth. Crie a conta no Auth primeiro.'
        );
        RETURN result;
    END IF;
    
    -- Verificar se já existe na tabela users
    IF EXISTS(SELECT 1 FROM users WHERE id = p_user_id) THEN
        result := json_build_object(
            'success', false,
            'error', 'USER_ALREADY_EXISTS',
            'message', 'Usuário já existe na tabela users'
        );
        RETURN result;
    END IF;
    
    -- Inserir usuário na tabela users (apenas se existir no Auth)
    INSERT INTO users (
        id, 
        farm_id, 
        name, 
        email, 
        role, 
        whatsapp, 
        profile_photo_url,
        is_active,
        created_at,
        updated_at
    ) VALUES (
        p_user_id,
        p_farm_id,
        p_name,
        p_email,
        p_role,
        p_whatsapp,
        p_profile_photo_url,
        true,  -- SEMPRE ativo
        NOW(),
        NOW()
    );
    
    -- Retornar sucesso
    result := json_build_object(
        'success', true,
        'user_id', p_user_id,
        'message', 'Usuário criado com sucesso na tabela users',
        'auth_verified', true
    );
    
    RETURN result;
    
EXCEPTION
    WHEN OTHERS THEN
        -- Retornar erro detalhado
        result := json_build_object(
            'success', false,
            'error', SQLSTATE,
            'error_message', SQLERRM,
            'message', 'Erro ao criar usuário na tabela users'
        );
        
        RETURN result;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- =====================================================
-- FUNÇÃO PARA CORRIGIR USUÁRIOS ÓRFÃOS
-- =====================================================

-- Função para identificar e corrigir usuários que existem na tabela users mas não no auth.users
CREATE OR REPLACE FUNCTION fix_orphaned_users()
RETURNS JSON AS $$
DECLARE
    orphaned_users RECORD;
    fixed_count INTEGER := 0;
    result JSON;
BEGIN
    -- Encontrar usuários órfãos (existem na tabela users mas não no auth.users)
    FOR orphaned_users IN 
        SELECT u.id, u.name, u.email, u.role, u.created_at
        FROM users u
        LEFT JOIN auth.users au ON u.id = au.id
        WHERE au.id IS NULL
    LOOP
        -- Log do usuário órfão encontrado
        RAISE NOTICE 'Usuário órfão encontrado: % (%) - %', orphaned_users.name, orphaned_users.email, orphaned_users.id;
        
        -- Remover usuário órfão da tabela users (será recriado corretamente depois)
        DELETE FROM users WHERE id = orphaned_users.id;
        
        fixed_count := fixed_count + 1;
    END LOOP;
    
    -- Retornar resultado
    result := json_build_object(
        'success', true,
        'fixed_count', fixed_count,
        'message', format('Removidos %s usuários órfãos que não tinham conta no Auth', fixed_count)
    );
    
    RETURN result;
    
EXCEPTION
    WHEN OTHERS THEN
        result := json_build_object(
            'success', false,
            'error', SQLSTATE,
            'error_message', SQLERRM,
            'message', 'Erro ao corrigir usuários órfãos'
        );
        
        RETURN result;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- =====================================================
-- FUNÇÃO PARA LISTAR USUÁRIOS ÓRFÃOS (SEM REMOVER)
-- =====================================================

-- Função para apenas listar usuários órfãos sem removê-los
CREATE OR REPLACE FUNCTION list_orphaned_users()
RETURNS TABLE(
    user_id UUID,
    name TEXT,
    email TEXT,
    role TEXT,
    created_at TIMESTAMP WITH TIME ZONE,
    problem TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        u.id as user_id,
        u.name::TEXT,
        u.email::TEXT,
        u.role::TEXT,
        u.created_at,
        'Existe na tabela users mas não no auth.users'::TEXT as problem
    FROM users u
    LEFT JOIN auth.users au ON u.id = au.id
    WHERE au.id IS NULL;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- =====================================================
-- CONFIRMAÇÃO DE CRIAÇÃO
-- =====================================================
SELECT '✅ Função create_farm_user criada com sucesso!' as status;
SELECT '🔧 Função fix_orphaned_users criada com sucesso!' as status;
SELECT '📋 Função list_orphaned_users criada com sucesso!' as status;
