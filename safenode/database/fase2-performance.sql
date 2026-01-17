-- =====================================================
-- SafeNode - Fase 2: Performance Monitoring
-- SQL para criar tabela de logs de performance
-- =====================================================

-- Criar tabela se não existir
CREATE TABLE IF NOT EXISTS `safenode_performance_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `site_id` INT(11) NOT NULL,
  `endpoint` VARCHAR(500) NOT NULL DEFAULT '/',
  `response_time` INT(11) NOT NULL COMMENT 'Tempo de resposta em milissegundos',
  `memory_usage` BIGINT(20) NOT NULL DEFAULT 0 COMMENT 'Uso de memória em bytes',
  `request_method` VARCHAR(10) NOT NULL DEFAULT 'GET',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_endpoint` (`endpoint`(255)),
  KEY `idx_response_time` (`response_time`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_site_created` (`site_id`, `created_at`),
  KEY `idx_endpoint_method` (`endpoint`(255), `request_method`),
  CONSTRAINT `fk_performance_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Logs de performance de requisições';

-- Tabela criada com sucesso!
-- Para verificar: DESCRIBE safenode_performance_logs;
-- Para ver índices: SHOW INDEX FROM safenode_performance_logs;

