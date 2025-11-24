-- Script para remover a constraint UNIQUE do campo username
-- Permite que múltiplos usuários tenham o mesmo nome de usuário
-- O login será feito apenas com email (que continua único)

-- Remover a constraint UNIQUE do username
ALTER TABLE `safenode_users` DROP INDEX `unique_username`;

-- O índice normal do username pode ser mantido para performance em buscas
-- (não precisa fazer nada, o índice idx_username já existe e não é UNIQUE)

