-- =====================================================
-- CORRIGIR SENHA DO ADMINISTRADOR
-- Execute este script se o login não estiver funcionando
-- =====================================================

USE `kron`;

-- Atualizar senha do admin (senha: admin123)
UPDATE `kron_users` 
SET `password` = '$2y$10$98zWMIufXE/lFi5t07.Wc.x0G86AaTsN9mzpMGbhUX0WIqKVtv/qi'
WHERE `email` = 'admin@kronx.sbs';

-- Verificar se foi atualizado
SELECT `email`, `name`, `is_active`, `email_verified` 
FROM `kron_users` 
WHERE `email` = 'admin@kronx.sbs';

