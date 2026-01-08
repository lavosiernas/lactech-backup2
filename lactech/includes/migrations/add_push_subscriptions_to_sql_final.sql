-- Script FINAL para adicionar tabela push_subscriptions e FOREIGN KEY
-- Execute este script APÓS importar o SQL principal
-- Versão que trata erros caso a constraint já exista

-- ============================================
-- 1. Adicionar tabela push_subscriptions
-- ============================================
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `endpoint` text NOT NULL,
  `p256dh` text NOT NULL,
  `auth` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `endpoint` (`endpoint`(255)),
  CONSTRAINT `push_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. Adicionar FOREIGN KEY para volume_records.recorded_by
-- ============================================
-- IMPORTANTE: Como recorded_by é NOT NULL no SQL fornecido, 
-- usamos RESTRICT (não permite deletar usuário se houver registros)

-- Usar procedimento para tratar erro caso constraint já exista
DELIMITER $$

DROP PROCEDURE IF EXISTS add_volume_records_fk$$

CREATE PROCEDURE add_volume_records_fk()
BEGIN
    DECLARE EXIT HANDLER FOR 1062
    BEGIN
        SELECT 'Constraint fk_volume_records_user already exists, skipping...' AS message;
    END;
    
    DECLARE EXIT HANDLER FOR 1215
    BEGIN
        SELECT 'Error: Foreign key constraint cannot be created. Check if all recorded_by values exist in users table.' AS message;
    END;
    
    ALTER TABLE `volume_records`
      ADD CONSTRAINT `fk_volume_records_user` 
      FOREIGN KEY (`recorded_by`)
      REFERENCES `users` (`id`)
      ON DELETE RESTRICT
      ON UPDATE CASCADE;
      
    SELECT 'Constraint fk_volume_records_user added successfully!' AS message;
END$$

DELIMITER ;

-- Executar o procedimento
CALL add_volume_records_fk();

-- Remover o procedimento após uso
DROP PROCEDURE IF EXISTS add_volume_records_fk;

-- ============================================
-- FIM DO SCRIPT
-- ============================================




















