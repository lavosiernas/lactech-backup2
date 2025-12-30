-- Script para adicionar tabela push_subscriptions ao SQL fornecido
-- Execute este script APÓS importar o SQL principal

-- Adicionar tabela push_subscriptions (necessária para Push Notifications)
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

-- Adicionar FOREIGN KEY para recorded_by
-- NOTA: Como recorded_by é NOT NULL no SQL fornecido, usamos RESTRICT em vez de SET NULL
-- Se a constraint já existir, você receberá um erro que pode ser ignorado

-- Opção 1: Se recorded_by permite NULL, usar SET NULL
-- ALTER TABLE `volume_records`
--   MODIFY `recorded_by` int(11) DEFAULT NULL,
--   ADD CONSTRAINT `fk_volume_records_user` 
--   FOREIGN KEY (`recorded_by`) 
--   REFERENCES `users` (`id`) 
--   ON DELETE SET NULL 
--   ON UPDATE CASCADE;

-- Opção 2: Manter NOT NULL e usar RESTRICT (recomendado)
-- Se a constraint já existir, você receberá um erro - pode ignorar
ALTER TABLE `volume_records`
  ADD CONSTRAINT `fk_volume_records_user` 
  FOREIGN KEY (`recorded_by`) 
  REFERENCES `users` (`id`) 
  ON DELETE RESTRICT 
  ON UPDATE CASCADE;

