-- Função para restaurar usuário excluído
CREATE OR REPLACE FUNCTION restore_deleted_user(
    p_user_id UUID,
    p_name TEXT,
    p_email TEXT,
    p_whatsapp TEXT,
    p_role TEXT,
    p_farm_id UUID,
    p_profile_photo_url TEXT DEFAULT NULL
)
RETURNS JSON
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_new_user_id UUID;
    v_result JSON;
BEGIN
    -- Verificar se o usuário já existe
    IF EXISTS (SELECT 1 FROM users WHERE email = p_email) THEN
        RETURN json_build_object(
            'success', false,
            'error', 'Usuário com este email já existe'
        );
    END IF;
    
    -- Criar novo usuário com os dados restaurados
    INSERT INTO users (
        name,
        email,
        whatsapp,
        role,
        farm_id,
        profile_photo_url,
        is_active,
        created_at
    ) VALUES (
        p_name,
        p_email,
        p_whatsapp,
        p_role,
        p_farm_id,
        p_profile_photo_url,
        true,
        NOW()
    ) RETURNING id INTO v_new_user_id;
    
    -- Retornar sucesso
    RETURN json_build_object(
        'success', true,
        'user_id', v_new_user_id,
        'message', 'Usuário restaurado com sucesso'
    );
    
EXCEPTION
    WHEN OTHERS THEN
        RETURN json_build_object(
            'success', false,
            'error', SQLERRM
        );
END;
$$;
