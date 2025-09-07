-- EXECUTAR ESTE SCRIPT NO SUPABASE SQL EDITOR
-- Para adicionar a coluna new_password na tabela password_requests

ALTER TABLE password_requests 
ADD COLUMN new_password TEXT;

-- Verificar se a coluna foi adicionada
SELECT column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'password_requests' 
AND column_name = 'new_password';
