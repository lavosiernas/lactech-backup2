-- DESABILITAR COMPLETAMENTE RLS NA TABELA USERS
-- Solução definitiva para parar a recursão infinita

-- Remover TODAS as políticas existentes
DROP POLICY IF EXISTS "Users can view users from their farm" ON users;
DROP POLICY IF EXISTS "Users can update their own profile" ON users;
DROP POLICY IF EXISTS "Authenticated users can insert users" ON users;

-- Remover função RPC se existir
DROP FUNCTION IF EXISTS create_farm_user(TEXT, TEXT, TEXT, TEXT, UUID, TEXT);

-- DESABILITAR RLS COMPLETAMENTE na tabela users
ALTER TABLE users DISABLE ROW LEVEL SECURITY;

-- Função RPC SIMPLIFICADA para criar usuários (sem RLS)
CREATE OR REPLACE FUNCTION create_farm_user(
    p_email TEXT,
    p_name TEXT,
    p_whatsapp TEXT,
    p_role TEXT,
    p_farm_id UUID,
    p_profile_photo_url TEXT DEFAULT NULL
)
RETURNS JSON AS $$
DECLARE
    new_user_id UUID;
BEGIN
    -- Verificar se o usuário atual está autenticado
    IF auth.uid() IS NULL THEN
        RAISE EXCEPTION 'Usuário não autenticado';
    END IF;
    
    -- Inserir novo usuário diretamente
    INSERT INTO users (
        email,
        name,
        whatsapp,
        role,
        farm_id,
        profile_photo_url,
        is_active
    ) VALUES (
        p_email,
        p_name,
        p_whatsapp,
        p_role,
        p_farm_id,
        p_profile_photo_url,
        true
    ) RETURNING id INTO new_user_id;
    
    RETURN json_build_object(
        'success', true,
        'user_id', new_user_id,
        'message', 'Usuário criado com sucesso'
    );
    
EXCEPTION
    WHEN OTHERS THEN
        RETURN json_build_object(
            'success', false,
            'error', SQLERRM
        );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;