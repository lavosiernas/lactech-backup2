-- ============================================
-- SafeNode Survey - Tabela completa (CREATE TABLE)
-- ============================================
-- Este script cria a tabela safenode_survey_responses completa
-- com todas as colunas necessárias incluindo thanked_at
-- ============================================

-- Remover tabela se existir (CUIDADO: isso apaga todos os dados!)
-- DROP TABLE IF EXISTS `safenode_survey_responses`;

CREATE TABLE IF NOT EXISTS `safenode_survey_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `uses_hosting` varchar(50) NOT NULL,
  `hosting_type` varchar(255) DEFAULT NULL,
  `biggest_pain` text NOT NULL,
  `pays_for_email` varchar(50) NOT NULL,
  `would_pay_integration` varchar(50) NOT NULL,
  `wants_beta` tinyint(1) DEFAULT 0,
  `additional_info` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `thanked_at` timestamp NULL DEFAULT NULL COMMENT 'Data/hora em que o email de agradecimento foi enviado',
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_thanked_at` (`thanked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

