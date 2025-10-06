-- =====================================================
-- CRIAR FUNÇÃO RPC get_user_profile
-- =====================================================
-- Função que estava faltando no banco
-- =====================================================

-- Remover função se existir
DROP FUNCTION IF EXISTS get_user_profile();

-- Criar função get_user_profile
CREATE OR REPLACE FUNCTION get_user_profile()
RETURNS TABLE (
    id uuid,
    email varchar,
    name varchar,
    role varchar,
    farm_id uuid,
    whatsapp varchar,
    is_active boolean,
    profile_photo_url text,
    created_at timestamptz,
    updated_at timestamptz
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        u.id,
        u.email,
        u.name,
        u.role,
        u.farm_id,
        u.whatsapp,
        u.is_active,
        u.profile_photo_url,
        u.created_at,
        u.updated_at
    FROM users u
    WHERE u.id = auth.uid();
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Conceder permissões
GRANT EXECUTE ON FUNCTION get_user_profile() TO authenticated;

-- Confirmação
DO $$
BEGIN
    RAISE NOTICE '✅ Função get_user_profile criada com sucesso!';
    RAISE NOTICE '📋 Função retorna perfil do usuário autenticado';
    RAISE NOTICE '🔐 Permissões concedidas para usuários autenticados';
END $$;
