-- SafeNode - Atualização Alternativa (se a ALTER TABLE falhar)
-- Use este script apenas se o update-challenge-support.sql não funcionar

-- Passo 1: Criar tabela temporária com novo ENUM
CREATE TABLE `safenode_human_verification_logs_new` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `event_type` ENUM('human_validated','bot_blocked','access_allowed','challenge_shown') NOT NULL,
  `request_uri` text DEFAULT NULL,
  `request_method` varchar(10) DEFAULT 'GET',
  `user_agent` text DEFAULT NULL,
  `referer` text DEFAULT NULL,
  `country_code` char(2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_site_event_created` (`site_id`,`event_type`,`created_at`),
  KEY `idx_ip_created` (`ip_address`,`created_at`),
  CONSTRAINT `safenode_human_verification_logs_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Passo 2: Copiar dados existentes (apenas registros válidos)
INSERT INTO `safenode_human_verification_logs_new` 
SELECT * FROM `safenode_human_verification_logs`;

-- Passo 3: Remover tabela antiga
DROP TABLE `safenode_human_verification_logs`;

-- Passo 4: Renomear tabela nova
RENAME TABLE `safenode_human_verification_logs_new` TO `safenode_human_verification_logs`;

-- Pronto! A tabela agora suporta 'challenge_shown'

