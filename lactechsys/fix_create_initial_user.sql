-- Execute este SQL no Supabase Dashboard para corrigir a função create_initial_user
-- Vá em: Supabase Dashboard > SQL Editor > Cole este código e execute

-- 1. Remover a função se existir para evitar conflitos
DROP FUNCTION IF EXISTS create_initial_user(UUID, UUID, TEXT, TEXT, TEXT, TEXT);

-- 2. Criar a função create_initial_user correta
CREATE OR REPLACE FUNCTION create_initial_user(
    p_user_id UUID,
    p_farm_id UUID,
    p_name TEXT,
    p_email TEXT,
    p_role TEXT,
    p_whatsapp TEXT DEFAULT ''
)
RETURNS VOID AS $$
BEGIN
    INSERT INTO users (
        id, farm_id, name, email, role, whatsapp
    ) VALUES (
        p_user_id, p_farm_id, p_name, p_email, p_role, p_whatsapp
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 3. Verificar se a função foi criada corretamente
SELECT 
    r.routine_name, 
    r.routine_type, 
    p.data_type,
    p.parameter_name,
    p.parameter_mode,
    p.parameter_default
FROM information_schema.routines r
LEFT JOIN information_schema.parameters p ON r.routine_name = p.specific_name
WHERE r.routine_name = 'create_initial_user'
AND r.routine_schema = 'public'
ORDER BY p.ordinal_position;

-- 4. Testar a função (opcional - remova se não quiser testar)
-- SELECT create_initial_user(
--     '00000000-0000-0000-0000-000000000000'::UUID,
--     '00000000-0000-0000-0000-000000000000'::UUID,
--     'Test User',
--     'test@example.com',
--     'admin',
--     '11999999999'
-- );
