-- ============================================
-- Script para substituir usuário admin
-- ============================================
-- Este script remove o admin antigo e adiciona um novo
-- 
-- IMPORTANTE: 
-- 1. Altere os valores abaixo antes de executar:
--    - novo_email: Seu email para o novo admin
--    - novo_username: Seu username para o novo admin
--    - novo_password_hash: Use o script generate-password-hash.php para gerar
--    - novo_full_name: Nome completo do novo admin
--
-- 2. Execute este script no phpMyAdmin ou via linha de comando MySQL
-- ============================================

-- Remover o usuário admin antigo (admin@safenode.cloud)
DELETE FROM `safenode_users` 
WHERE `email` = 'admin@safenode.cloud';

-- Adicionar novo usuário admin
INSERT INTO `safenode_users` (
    `username`, 
    `email`, 
    `password_hash`, 
    `full_name`, 
    `role`, 
    `is_active`, 
    `email_verified`, 
    `email_verified_at`, 
    `google_id`, 
    `last_login`, 
    `created_at`, 
    `updated_at`
) VALUES (
    'lavosier',                             -- Username: lavosier
    'lavosier@safenode.cloud',              -- Email: lavosier@safenode.cloud
    '$2y$10$D5ZV9Gn.hwmpQkgFKfS2XeCpMCPq8E3D94lPW1KrJjFa/qY/4tBEO',  -- Hash da senha: Lavosier123!
    'Lavosier Silva',                       -- Nome completo: Lavosier Silva
    'admin',                                -- Role: admin
    1,                                      -- is_active: 1 (ativo)
    1,                                      -- email_verified: 1 (verificado)
    NOW(),                                  -- email_verified_at: agora
    NULL,                                   -- google_id: NULL
    NULL,                                   -- last_login: NULL
    NOW(),                                  -- created_at: agora
    NOW()                                   -- updated_at: agora
);

-- Verificar se o novo admin foi criado
SELECT 
    id, 
    username, 
    email, 
    role, 
    is_active, 
    email_verified,
    created_at
FROM `safenode_users` 
WHERE `role` = 'admin';


-- ============================================
-- Este script remove o admin antigo e adiciona um novo
-- 
-- IMPORTANTE: 
-- 1. Altere os valores abaixo antes de executar:
--    - novo_email: Seu email para o novo admin
--    - novo_username: Seu username para o novo admin
--    - novo_password_hash: Use o script generate-password-hash.php para gerar
--    - novo_full_name: Nome completo do novo admin
--
-- 2. Execute este script no phpMyAdmin ou via linha de comando MySQL
-- ============================================

-- Remover o usuário admin antigo (admin@safenode.cloud)
DELETE FROM `safenode_users` 
WHERE `email` = 'admin@safenode.cloud';

-- Adicionar novo usuário admin
INSERT INTO `safenode_users` (
    `username`, 
    `email`, 
    `password_hash`, 
    `full_name`, 
    `role`, 
    `is_active`, 
    `email_verified`, 
    `email_verified_at`, 
    `google_id`, 
    `last_login`, 
    `created_at`, 
    `updated_at`
) VALUES (
    'lavosier',                             -- Username: lavosier
    'lavosier@safenode.cloud',              -- Email: lavosier@safenode.cloud
    '$2y$10$D5ZV9Gn.hwmpQkgFKfS2XeCpMCPq8E3D94lPW1KrJjFa/qY/4tBEO',  -- Hash da senha: Lavosier123!
    'Lavosier Silva',                       -- Nome completo: Lavosier Silva
    'admin',                                -- Role: admin
    1,                                      -- is_active: 1 (ativo)
    1,                                      -- email_verified: 1 (verificado)
    NOW(),                                  -- email_verified_at: agora
    NULL,                                   -- google_id: NULL
    NULL,                                   -- last_login: NULL
    NOW(),                                  -- created_at: agora
    NOW()                                   -- updated_at: agora
);

-- Verificar se o novo admin foi criado
SELECT 
    id, 
    username, 
    email, 
    role, 
    is_active, 
    email_verified,
    created_at
FROM `safenode_users` 
WHERE `role` = 'admin';

