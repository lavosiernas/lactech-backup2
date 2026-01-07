-- =====================================================
-- ADICIONAR COLUNAS KRON NO BANCO SAFENODE
-- Banco: safend (ou u311882628_safend na Hostinger)
-- Tabela: safenode_users
-- =====================================================

-- IMPORTANTE: Altere o nome do banco se necessário
-- Para produção Hostinger: USE `u311882628_safend`;
-- Para local: USE `safend`;
USE `safend`;

-- Adicionar colunas de conexão KRON na tabela safenode_users
-- Verifica se a coluna não existe antes de adicionar (evita erros se já existir)
SET @dbname = DATABASE();
SET @tablename = 'safenode_users';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = 'kron_user_id')
  ) > 0,
  'SELECT "Coluna kron_user_id já existe" AS resultado;',
  'ALTER TABLE `safenode_users` ADD COLUMN `kron_user_id` INT(11) NULL COMMENT ''ID do usuário no sistema KRON'' AFTER `id`;'
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
  'ALTER TABLE `safenode_users` ADD COLUMN `kron_connection_token` VARCHAR(255) NULL COMMENT ''Token permanente de conexão com KRON'' AFTER `kron_user_id`;'
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
  'ALTER TABLE `safenode_users` ADD COLUMN `kron_connected_at` TIMESTAMP NULL COMMENT ''Data de conexão com KRON'' AFTER `kron_connection_token`;'
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
  'ALTER TABLE `safenode_users` ADD KEY `idx_kron_user` (`kron_user_id`);'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar se as colunas foram adicionadas corretamente
-- SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = 'safend' AND TABLE_NAME = 'safenode_users' 
-- AND COLUMN_NAME LIKE 'kron%';

