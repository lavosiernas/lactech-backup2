-- =====================================================
-- SafeNode: Tabela de Logs do Sistema
-- =====================================================
-- Data: 2025-12-13
-- Descrição: Tabela para armazenar todos os logs de erro do sistema
-- =====================================================

CREATE TABLE IF NOT EXISTS `safenode_system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` enum('info','warning','error','critical','debug') DEFAULT 'error' COMMENT 'Nível do log',
  `message` text NOT NULL COMMENT 'Mensagem do erro',
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Contexto adicional em JSON',
  `file` varchar(500) DEFAULT NULL COMMENT 'Arquivo onde ocorreu o erro',
  `line` int(11) DEFAULT NULL COMMENT 'Linha onde ocorreu o erro',
  `function` varchar(255) DEFAULT NULL COMMENT 'Função/método onde ocorreu',
  `trace` text DEFAULT NULL COMMENT 'Stack trace do erro',
  `user_id` int(11) DEFAULT NULL COMMENT 'ID do usuário (se aplicável)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP do usuário',
  `user_agent` text DEFAULT NULL,
  `request_uri` varchar(500) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_level` (`level`),
  KEY `idx_created` (`created_at`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_message` (`message`(100)),
  KEY `idx_file` (`file`(100)),
  FULLTEXT KEY `ft_message` (`message`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

