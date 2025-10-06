-- Corrigir políticas RLS da tabela users
-- Adicionar política para permitir inserção de novos usuários

-- Remover políticas existentes que podem estar conflitando
DROP POLICY IF EXISTS "Users can view users from their farm" ON users;
DROP POLICY IF EXISTS "Users can update their own profile" ON users;

-- Recriar políticas com INSERT permitido
CREATE POLICY "Users can view users from their farm" ON users
    FOR SELECT USING (farm_id IN (
        SELECT farm_id FROM users WHERE id = auth.uid()
    ));

CREATE POLICY "Users can update their own profile" ON users
    FOR UPDATE USING (id = auth.uid());

-- Política simples para inserção - qualquer usuário autenticado pode inserir
CREATE POLICY "Authenticated users can insert users" ON users
    FOR INSERT WITH CHECK (auth.uid() IS NOT NULL);

-- Função RPC para criar usuários da fazenda (contorna RLS)
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
    current_user_farm_id UUID;
BEGIN
    -- Verificar se o usuário atual está autenticado
    IF auth.uid() IS NULL THEN
        RAISE EXCEPTION 'Usuário não autenticado';
    END IF;
    
    -- Verificar se o usuário atual pertence à mesma fazenda
    SELECT farm_id INTO current_user_farm_id 
    FROM users 
    WHERE id = auth.uid();
    
    IF current_user_farm_id IS NULL THEN
        RAISE EXCEPTION 'Usuário não pertence a uma fazenda';
    END IF;
    
    IF current_user_farm_id != p_farm_id THEN
        RAISE EXCEPTION 'Não autorizado a criar usuários em outras fazendas';
    END IF;
    
    -- Inserir novo usuário
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
