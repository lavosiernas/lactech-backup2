-- Script para debugar problemas de CNPJ duplicado
-- Execute este script no Supabase Dashboard > SQL Editor

-- 1. Verificar se há CNPJs duplicados na tabela farms
SELECT 
    cnpj, 
    COUNT(*) as count,
    STRING_AGG(name, ', ') as farm_names,
    STRING_AGG(id::text, ', ') as farm_ids
FROM farms 
WHERE cnpj IS NOT NULL 
GROUP BY cnpj 
HAVING COUNT(*) > 1;

-- 2. Verificar todas as fazendas ordenadas por data de criação
SELECT 
    id,
    name,
    cnpj,
    owner_name,
    created_at
FROM farms 
ORDER BY created_at DESC 
LIMIT 20;

-- 3. Testar a função check_farm_exists com um CNPJ específico
-- Substitua '12.345.678/0001-90' pelo CNPJ que está causando problema
SELECT check_farm_exists('Fazenda Teste', '12.345.678/0001-90') as farm_exists;

-- 4. Verificar se há problemas na constraint unique
SELECT 
    conname as constraint_name,
    contype as constraint_type,
    pg_get_constraintdef(oid) as constraint_definition
FROM pg_constraint 
WHERE conrelid = 'farms'::regclass 
AND contype = 'u';

-- 5. Verificar índices na tabela farms
SELECT 
    indexname,
    indexdef
FROM pg_indexes 
WHERE tablename = 'farms';

-- 6. Se necessário, limpar fazendas duplicadas (CUIDADO: só execute se tiver certeza)
-- DELETE FROM farms 
-- WHERE id NOT IN (
--     SELECT MIN(id) 
--     FROM farms 
--     GROUP BY cnpj
-- ) AND cnpj IS NOT NULL;

-- 7. Verificar se a função create_initial_farm está funcionando corretamente
-- (Teste apenas se não houver dados importantes)
-- SELECT create_initial_farm(
--     'Fazenda Debug',
--     'Proprietário Debug', 
--     '99.999.999/0001-99',
--     'Cidade Debug',
--     'SP',
--     'Endereço Debug'
-- );