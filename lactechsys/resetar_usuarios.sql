-- =====================================================
-- RESETAR USUÁRIOS - LACTECH
-- Execute no phpMyAdmin da Hostinger
-- =====================================================

USE u311882628_lactech_lgmato;

-- Atualizar usuário Junior (Gerente)
UPDATE users 
SET 
    name = 'Junior',
    email = 'Junior@lactech.com',
    password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: gerente123
    role = 'gerente',
    farm_id = 1,
    is_active = 1,
    updated_at = NOW()
WHERE email = 'Junior@lactech.com';

-- Se não existir, criar
INSERT IGNORE INTO users (name, email, password, role, farm_id, is_active, created_at, updated_at)
VALUES (
    'Junior',
    'Junior@lactech.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: gerente123
    'gerente',
    1,
    1,
    NOW(),
    NOW()
);

-- Atualizar usuário Fernando (Proprietário)
UPDATE users 
SET 
    name = 'Fernando',
    email = 'fernando@lactech.com',
    password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: proprietario123
    role = 'proprietario',
    farm_id = 1,
    is_active = 1,
    updated_at = NOW()
WHERE email = 'fernando@lactech.com';

-- Se não existir, criar
INSERT IGNORE INTO users (name, email, password, role, farm_id, is_active, created_at, updated_at)
VALUES (
    'Fernando',
    'fernando@lactech.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: proprietario123
    'proprietario',
    1,
    1,
    NOW(),
    NOW()
);

-- Verificar resultado
SELECT 
    id,
    name,
    email,
    role,
    is_active,
    created_at
FROM users
ORDER BY id;

