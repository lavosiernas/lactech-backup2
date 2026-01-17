-- =====================================================
-- SafeNode - Fase 3: Sistema de Alertas e Notificações
-- SQL para criar tabelas de alertas
-- =====================================================

-- Criar tabela de alertas
CREATE TABLE IF NOT EXISTS `safenode_alerts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `site_id` INT(11) NOT NULL,
  `alert_type` VARCHAR(50) NOT NULL COMMENT 'critical_threat, suspicious_ip, performance_issue, security_recommendation',
  `severity` VARCHAR(20) NOT NULL DEFAULT 'medium' COMMENT 'critical, high, medium, low',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `data` JSON DEFAULT NULL COMMENT 'Dados adicionais do alerta (IP, endpoint, etc)',
  `read` TINYINT(1) NOT NULL DEFAULT 0,
  `email_sent` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_read` (`read`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_site_read` (`site_id`, `read`),
  CONSTRAINT `fk_alerts_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Alertas e notificações do sistema';

-- Criar tabela de preferências de alerta
CREATE TABLE IF NOT EXISTS `safenode_alert_preferences` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `site_id` INT(11) NOT NULL,
  `alert_type` VARCHAR(50) NOT NULL,
  `email_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `dashboard_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `threshold` INT(11) DEFAULT NULL COMMENT 'Valor mínimo para disparar alerta (ex: 10 tentativas)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_site_alert_type` (`site_id`, `alert_type`),
  KEY `idx_site_id` (`site_id`),
  CONSTRAINT `fk_alert_preferences_site` FOREIGN KEY (`site_id`) REFERENCES `safenode_sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Preferências de alertas por site';

-- Inserir preferências padrão para sites existentes
INSERT IGNORE INTO `safenode_alert_preferences` (`site_id`, `alert_type`, `email_enabled`, `dashboard_enabled`, `threshold`)
SELECT 
    id,
    'critical_threat',
    1,
    1,
    1
FROM `safenode_sites`;

INSERT IGNORE INTO `safenode_alert_preferences` (`site_id`, `alert_type`, `email_enabled`, `dashboard_enabled`, `threshold`)
SELECT 
    id,
    'suspicious_ip',
    1,
    1,
    5
FROM `safenode_sites`;

INSERT IGNORE INTO `safenode_alert_preferences` (`site_id`, `alert_type`, `email_enabled`, `dashboard_enabled`, `threshold`)
SELECT 
    id,
    'performance_issue',
    0,
    1,
    3
FROM `safenode_sites`;

INSERT IGNORE INTO `safenode_alert_preferences` (`site_id`, `alert_type`, `email_enabled`, `dashboard_enabled`, `threshold`)
SELECT 
    id,
    'security_recommendation',
    0,
    1,
    NULL
FROM `safenode_sites`;

-- Tabelas criadas com sucesso!

