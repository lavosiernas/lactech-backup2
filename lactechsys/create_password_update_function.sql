-- Função para atualizar senha de usuário via RPC
-- Execute este SQL no Supabase SQL Editor

CREATE OR REPLACE FUNCTION update_user_password(user_id UUID, new_password TEXT)
RETURNS BOOLEAN
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
    -- Verificar se o usuário atual é um gerente
    IF NOT EXISTS (
        SELECT 1 FROM users 
        WHERE id = auth.uid() 
        AND role = 'gerente'
    ) THEN
        RAISE EXCEPTION 'Apenas gerentes podem alterar senhas';
    END IF;
    
    -- Atualizar a senha do usuário
    UPDATE auth.users 
    SET encrypted_password = crypt(new_password, gen_salt('bf'))
    WHERE id = user_id;
    
    -- Verificar se a atualização foi bem-sucedida
    IF FOUND THEN
        RETURN TRUE;
    ELSE
        RAISE EXCEPTION 'Usuário não encontrado';
    END IF;
END;
$$;

-- Conceder permissões para a função
GRANT EXECUTE ON FUNCTION update_user_password(UUID, TEXT) TO authenticated;

-- Comentário explicativo
COMMENT ON FUNCTION update_user_password(UUID, TEXT) IS 'Função para gerentes alterarem senhas de usuários via RPC';
