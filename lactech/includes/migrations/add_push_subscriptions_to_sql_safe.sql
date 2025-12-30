-- Script SIMPLIFICADO para adicionar tabela push_subscriptions e FOREIGN KEY
-- Execute este script APÓS importar o SQL principal
-- Esta versão NÃO usa information_schema (não requer permissões especiais)

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
-- ou CASCADE (deleta registros quando usuário é deletado)

-- Versão simples: tenta adicionar a constraint
-- Se já existir, você receberá um erro que pode ser ignorado
-- Erro esperado se já existir: "Duplicate key name 'fk_volume_records_user'"

ALTER TABLE `volume_records`
  ADD CONSTRAINT `fk_volume_records_user` 
  FOREIGN KEY (`recorded_by`)
  REFERENCES `users` (`id`)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;

-- ============================================
-- FIM DO SCRIPT
-- ============================================

