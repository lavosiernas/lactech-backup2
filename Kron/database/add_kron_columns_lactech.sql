-- =====================================================
-- ADICIONAR COLUNAS KRON NO BANCO LACTECH
-- Banco: lactech_lgmato (ou u311882628_lactech_lgmato na Hostinger)
-- Tabela: users
-- =====================================================

-- IMPORTANTE: Altere o nome do banco se necessário
-- Para produção Hostinger: USE `u311882628_lactech_lgmato`;
-- Para local: USE `lactech_lgmato`;
USE `lactech_lgmato`;

-- Adicionar colunas de conexão KRON na tabela users
-- Verifica se a coluna não existe antes de adicionar (evita erros se já existir)
SET @dbname = DATABASE();
SET @tablename = 'users';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = 'kron_user_id')
  ) > 0,
  'SELECT "Coluna kron_user_id já existe" AS resultado;',
  'ALTER TABLE `users` ADD COLUMN `kron_user_id` INT(11) NULL COMMENT ''ID do usuário no sistema KRON'' AFTER `id`;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = 'kron_connection_token')
  ) > 0,
  'SELECT "Coluna kron_connection_token já existe" AS resultado;',
  'ALTER TABLE `users` ADD COLUMN `kron_connection_token` VARCHAR(255) NULL COMMENT ''Token permanente de conexão com KRON'' AFTER `kron_user_id`;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = 'kron_connected_at')
  ) > 0,
  'SELECT "Coluna kron_connected_at já existe" AS resultado;',
  'ALTER TABLE `users` ADD COLUMN `kron_connected_at` TIMESTAMP NULL COMMENT ''Data de conexão com KRON'' AFTER `kron_connection_token`;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Adicionar índice (verifica se não existe)
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (INDEX_NAME = 'idx_kron_user')
  ) > 0,
  'SELECT "Índice idx_kron_user já existe" AS resultado;',
  'ALTER TABLE `users` ADD KEY `idx_kron_user` (`kron_user_id`);'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar se as colunas foram adicionadas corretamente
-- SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = 'lactech_lgmato' AND TABLE_NAME = 'users' 
-- AND COLUMN_NAME LIKE 'kron%';

