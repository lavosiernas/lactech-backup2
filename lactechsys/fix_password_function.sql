-- =====================================================
-- CORREÇÃO DA FUNÇÃO CREATE_FARM_USER
-- =====================================================
-- Esta função agora apenas cria o registro na tabela users
-- A criação da conta de autenticação deve ser feita via API
-- =====================================================

-- Remover função RPC se existir
DROP FUNCTION IF EXISTS create_farm_user(TEXT, TEXT, TEXT, TEXT, UUID, TEXT);

-- Função RPC SIMPLIFICADA para criar usuários (sem RLS)
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
    new_user_id UUID;
BEGIN
    -- Verificar se o usuário atual está autenticado
    IF auth.uid() IS NULL THEN
        RAISE EXCEPTION 'Usuário não autenticado';
    END IF;
    
    -- Inserir novo usuário diretamente com o ID do Auth
    INSERT INTO users (
        id,
        email,
        name,
        whatsapp,
        role,
        farm_id,
        profile_photo_url,
        is_active
    ) VALUES (
        p_user_id,
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

-- =====================================================
-- CONFIRMAÇÃO DE CRIAÇÃO
-- =====================================================
SELECT '✅ Função create_farm_user corrigida com sucesso!' as status;
