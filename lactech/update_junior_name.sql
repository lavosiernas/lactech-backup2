-- Script para atualizar o nome de Junior Silva para Junior Alves
-- Execute este script no phpMyAdmin ou via linha de comando MySQL

UPDATE users 
SET name = 'Junior Alves', 
    updated_at = NOW()
WHERE name = 'Junior Silva' 
   OR email = 'Junior@lactech.com';

-- Verificar se foi atualizado
SELECT id, name, email, updated_at 
FROM users 
WHERE email = 'Junior@lactech.com';






