-- =====================================================
-- ESTRUTURA DE GOVERNAÇA - SERVIDOR KRON
-- Adiciona tabelas para RBAC hierárquico e governança
-- =====================================================

-- =====================================================
-- ESTRUTURA DE GOVERNAÇA - SERVIDOR KRON
-- Adiciona tabelas para RBAC hierárquico e governança
-- =====================================================

-- Criar banco de dados se não existir
CREATE DATABASE IF NOT EXISTS `kronserver` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usar banco de dados
USE `kronserver`;

-- =====================================================
-- TABELAS DE SISTEMAS GOVERNADOS
-- =====================================================

-- Tabela de sistemas governados
CREATE TABLE IF NOT EXISTS `kron_systems` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'safenode, lactech, etc',
  `display_name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `api_url` VARCHAR(500) NULL COMMENT 'URL da API do sistema',
  `status` ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
  `version` VARCHAR(50) NULL,
  `system_token` VARCHAR(500) NULL COMMENT 'Token JWT do sistema',
  `token_expires_at` TIMESTAMP NULL,
  `allowed_ips` TEXT NULL COMMENT 'IPs permitidos (JSON array)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de setores
CREATE TABLE IF NOT EXISTS `kron_sectors` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Código único do setor',
  `description` TEXT NULL,
  `parent_sector_id` INT(11) NULL COMMENT 'Setor pai (para hierarquia)',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_code` (`code`),
  KEY `idx_parent` (`parent_sector_id`),
  KEY `idx_active` (`is_active`),
  FOREIGN KEY (`parent_sector_id`) REFERENCES `kron_sectors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELAS DE ROLES E PERMISSÕES (RBAC)
-- =====================================================

-- Tabela de roles (papéis)
CREATE TABLE IF NOT EXISTS `kron_roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'ceo, gerente_central, gerente_setor, funcionario',
  `display_name` VARCHAR(255) NOT NULL,
  `level` INT(11) NOT NULL COMMENT 'Nível hierárquico (1=CEO, 2=Gerente Central, etc)',
  `description` TEXT NULL,
  `is_system` TINYINT(1) DEFAULT 0 COMMENT 'Role do sistema (não pode ser deletada)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`),
  KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de permissões
CREATE TABLE IF NOT EXISTS `kron_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'system.create, user.read, etc',
  `display_name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `category` VARCHAR(50) NULL COMMENT 'system, user, audit, command, etc',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de relacionamento role-permission
CREATE TABLE IF NOT EXISTS `kron_role_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_id` INT(11) NOT NULL,
  `permission_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_permission` (`role_id`, `permission_id`),
  KEY `idx_role` (`role_id`),
  KEY `idx_permission` (`permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `kron_roles` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `kron_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de relacionamento user-role
CREATE TABLE IF NOT EXISTS `kron_user_roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `role_id` INT(11) NOT NULL,
  `assigned_by` INT(11) NULL COMMENT 'ID do usuário que atribuiu',
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_role` (`user_id`, `role_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_role` (`role_id`),
  FOREIGN KEY (`user_id`) REFERENCES `kron_users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `kron_roles` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `kron_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE ACESSO SISTEMA-SETOR (CORE DO MODELO)
-- =====================================================

-- Tabela que define acesso de usuário a sistema+setor
CREATE TABLE IF NOT EXISTS `kron_user_system_sector` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `system_id` INT(11) NOT NULL,
  `sector_id` INT(11) NULL COMMENT 'NULL = acesso a todo o sistema',
  `granted_by` INT(11) NULL COMMENT 'ID do usuário que concedeu acesso',
  `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_system_sector` (`user_id`, `system_id`, `sector_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_system` (`system_id`),
  KEY `idx_sector` (`sector_id`),
  KEY `idx_active` (`is_active`),
  FOREIGN KEY (`user_id`) REFERENCES `kron_users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`system_id`) REFERENCES `kron_systems` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sector_id`) REFERENCES `kron_sectors` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`granted_by`) REFERENCES `kron_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELAS DE TOKENS E AUTENTICAÇÃO
-- =====================================================

-- Tabela de tokens de sistema (JWT)
CREATE TABLE IF NOT EXISTS `kron_system_tokens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `system_id` INT(11) NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL COMMENT 'Hash do token JWT',
  `scopes` TEXT NULL COMMENT 'Escopos permitidos (JSON array)',
  `allowed_ips` TEXT NULL COMMENT 'IPs permitidos (JSON array)',
  `is_active` TINYINT(1) DEFAULT 1,
  `last_used_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_system` (`system_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_token_hash` (`token_hash`),
  FOREIGN KEY (`system_id`) REFERENCES `kron_systems` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELAS DE AUDITORIA E LOGS
-- =====================================================

-- Tabela de logs de auditoria (imutáveis)
CREATE TABLE IF NOT EXISTS `kron_audit_logs` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NULL COMMENT 'NULL para ações do sistema',
  `action` VARCHAR(100) NOT NULL COMMENT 'user.create, system.update, etc',
  `entity_type` VARCHAR(50) NULL COMMENT 'user, system, role, etc',
  `entity_id` INT(11) NULL,
  `old_values` TEXT NULL COMMENT 'Valores anteriores (JSON)',
  `new_values` TEXT NULL COMMENT 'Valores novos (JSON)',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `metadata` TEXT NULL COMMENT 'Dados adicionais (JSON)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `kron_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs de sistema (recebidos dos sistemas governados)
CREATE TABLE IF NOT EXISTS `kron_system_logs` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `system_id` INT(11) NOT NULL,
  `level` ENUM('debug', 'info', 'warning', 'error', 'critical') NOT NULL,
  `message` TEXT NOT NULL,
  `context` TEXT NULL COMMENT 'Contexto adicional (JSON)',
  `stack_trace` TEXT NULL,
  `received_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_system` (`system_id`),
  KEY `idx_level` (`level`),
  KEY `idx_received` (`received_at`),
  FOREIGN KEY (`system_id`) REFERENCES `kron_systems` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELAS DE MÉTRICAS E MONITORAMENTO
-- =====================================================

-- Tabela de métricas recebidas dos sistemas
CREATE TABLE IF NOT EXISTS `kron_metrics` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `system_id` INT(11) NOT NULL,
  `metric_type` VARCHAR(100) NOT NULL COMMENT 'requests_total, threats_blocked, etc',
  `metric_value` DECIMAL(20,4) NOT NULL,
  `metric_date` DATE NOT NULL,
  `metric_hour` TINYINT(2) NULL COMMENT 'Hora do dia (0-23)',
  `metadata` TEXT NULL COMMENT 'Dados adicionais (JSON)',
  `received_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_system` (`system_id`),
  KEY `idx_type` (`metric_type`),
  KEY `idx_date` (`metric_date`),
  KEY `idx_system_type_date` (`system_id`, `metric_type`, `metric_date`),
  FOREIGN KEY (`system_id`) REFERENCES `kron_systems` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELAS DE COMANDOS E ORQUESTRAÇÃO
-- =====================================================

-- Tabela de comandos enviados aos sistemas
CREATE TABLE IF NOT EXISTS `kron_commands` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `command_id` VARCHAR(100) NOT NULL UNIQUE COMMENT 'ID único do comando',
  `system_id` INT(11) NOT NULL,
  `type` VARCHAR(100) NOT NULL COMMENT 'sync_data, backup, restart, etc',
  `parameters` TEXT NULL COMMENT 'Parâmetros do comando (JSON)',
  `priority` ENUM('low', 'normal', 'high', 'critical') DEFAULT 'normal',
  `status` ENUM('pending', 'queued', 'executing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
  `created_by` INT(11) NULL COMMENT 'ID do usuário que criou',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `queued_at` TIMESTAMP NULL,
  `executed_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `error_message` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_command_id` (`command_id`),
  KEY `idx_system` (`system_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created` (`created_at`),
  FOREIGN KEY (`system_id`) REFERENCES `kron_systems` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `kron_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de resultados de comandos
CREATE TABLE IF NOT EXISTS `kron_command_results` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `command_id` INT(11) NOT NULL,
  `status` ENUM('success', 'failed', 'partial') NOT NULL,
  `result_data` TEXT NULL COMMENT 'Dados do resultado (JSON)',
  `error` TEXT NULL,
  `execution_time_ms` INT(11) NULL,
  `received_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_command` (`command_id`),
  FOREIGN KEY (`command_id`) REFERENCES `kron_commands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Inserir sistemas padrão
INSERT IGNORE INTO `kron_systems` (`name`, `display_name`, `description`, `status`) VALUES
('safenode', 'SafeNode', 'Sistema de segurança e proteção web', 'active'),
('lactech', 'LacTech', 'Sistema de gestão de produção leiteira', 'active');

-- Inserir roles padrão
INSERT IGNORE INTO `kron_roles` (`name`, `display_name`, `level`, `description`, `is_system`) VALUES
('ceo', 'CEO (Super Admin Global)', 1, 'Acesso total ao sistema, pode criar Gerentes Centrais', 1),
('gerente_central', 'Gerente Central', 2, 'Pode criar Gerentes de Setor e gerenciar múltiplos setores', 1),
('gerente_setor', 'Gerente de Setor', 3, 'Gerencia um setor específico dentro de um sistema', 1),
('funcionario', 'Funcionário', 4, 'Acesso básico conforme permissões atribuídas', 1);

-- Inserir permissões padrão
INSERT IGNORE INTO `kron_permissions` (`name`, `display_name`, `description`, `category`) VALUES
-- Permissões de Sistema
('system.create', 'Criar Sistema', 'Criar novos sistemas governados', 'system'),
('system.read', 'Ver Sistema', 'Visualizar informações de sistemas', 'system'),
('system.update', 'Atualizar Sistema', 'Atualizar configurações de sistemas', 'system'),
('system.delete', 'Deletar Sistema', 'Remover sistemas do Kron', 'system'),
-- Permissões de Usuário
('user.create', 'Criar Usuário', 'Criar novos usuários', 'user'),
('user.read', 'Ver Usuário', 'Visualizar informações de usuários', 'user'),
('user.update', 'Atualizar Usuário', 'Atualizar dados de usuários', 'user'),
('user.delete', 'Deletar Usuário', 'Remover usuários', 'user'),
-- Permissões de Setor
('sector.create', 'Criar Setor', 'Criar novos setores', 'sector'),
('sector.read', 'Ver Setor', 'Visualizar informações de setores', 'sector'),
('sector.update', 'Atualizar Setor', 'Atualizar configurações de setores', 'sector'),
('sector.delete', 'Deletar Setor', 'Remover setores', 'sector'),
-- Permissões de Role
('role.create', 'Criar Role', 'Criar novos papéis', 'role'),
('role.read', 'Ver Role', 'Visualizar informações de papéis', 'role'),
('role.update', 'Atualizar Role', 'Atualizar configurações de papéis', 'role'),
('role.delete', 'Deletar Role', 'Remover papéis', 'role'),
-- Permissões de Comando
('command.create', 'Criar Comando', 'Enviar comandos aos sistemas', 'command'),
('command.read', 'Ver Comando', 'Visualizar comandos e resultados', 'command'),
('command.execute', 'Executar Comando', 'Executar comandos nos sistemas', 'command'),
-- Permissões de Auditoria
('audit.read', 'Ver Auditoria', 'Visualizar logs de auditoria', 'audit'),
('audit.export', 'Exportar Auditoria', 'Exportar logs de auditoria', 'audit'),
-- Permissões de Métricas
('metrics.read', 'Ver Métricas', 'Visualizar métricas dos sistemas', 'metrics'),
('metrics.export', 'Exportar Métricas', 'Exportar métricas dos sistemas', 'metrics');

-- Atribuir permissões ao CEO (todas as permissões)
INSERT IGNORE INTO `kron_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `kron_roles` r
CROSS JOIN `kron_permissions` p
WHERE r.name = 'ceo';

-- Atribuir permissões ao Gerente Central
INSERT IGNORE INTO `kron_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `kron_roles` r
CROSS JOIN `kron_permissions` p
WHERE r.name = 'gerente_central'
AND p.name IN (
  'system.read', 'user.create', 'user.read', 'user.update',
  'sector.create', 'sector.read', 'sector.update',
  'role.read', 'command.create', 'command.read',
  'audit.read', 'metrics.read'
);

-- Atribuir permissões ao Gerente de Setor
INSERT IGNORE INTO `kron_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `kron_roles` r
CROSS JOIN `kron_permissions` p
WHERE r.name = 'gerente_setor'
AND p.name IN (
  'system.read', 'user.read', 'user.update',
  'sector.read', 'command.read',
  'audit.read', 'metrics.read'
);

-- Atribuir permissões ao Funcionário
INSERT IGNORE INTO `kron_role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `kron_roles` r
CROSS JOIN `kron_permissions` p
WHERE r.name = 'funcionario'
AND p.name IN (
  'system.read', 'sector.read', 'metrics.read'
);

-- =====================================================
-- ÍNDICES ADICIONAIS
-- =====================================================

-- Índices compostos para consultas frequentes
ALTER TABLE `kron_user_system_sector` 
ADD INDEX `idx_user_system_active` (`user_id`, `system_id`, `is_active`);

ALTER TABLE `kron_audit_logs` 
ADD INDEX `idx_user_action_created` (`user_id`, `action`, `created_at`);

ALTER TABLE `kron_metrics` 
ADD INDEX `idx_system_type_hour` (`system_id`, `metric_type`, `metric_hour`);

-- =====================================================
-- VIEWS ÚTEIS
-- =====================================================

-- View de usuários com roles
CREATE OR REPLACE VIEW `v_kron_users_with_roles` AS
SELECT 
    u.id,
    u.email,
    u.name,
    u.is_active,
    GROUP_CONCAT(DISTINCT r.name ORDER BY r.level SEPARATOR ', ') as roles,
    MAX(r.level) as highest_role_level
FROM `kron_users` u
LEFT JOIN `kron_user_roles` ur ON u.id = ur.user_id
LEFT JOIN `kron_roles` r ON ur.role_id = r.id
GROUP BY u.id;

-- View de acesso sistema-setor por usuário
CREATE OR REPLACE VIEW `v_kron_user_access` AS
SELECT 
    u.id as user_id,
    u.email,
    u.name as user_name,
    s.id as system_id,
    s.name as system_name,
    s.display_name as system_display_name,
    sec.id as sector_id,
    sec.name as sector_name,
    sec.code as sector_code,
    uss.is_active,
    uss.granted_at
FROM `kron_users` u
INNER JOIN `kron_user_system_sector` uss ON u.id = uss.user_id
INNER JOIN `kron_systems` s ON uss.system_id = s.id
LEFT JOIN `kron_sectors` sec ON uss.sector_id = sec.id
WHERE uss.is_active = 1;

-- View de comandos pendentes
CREATE OR REPLACE VIEW `v_kron_pending_commands` AS
SELECT 
    c.id,
    c.command_id,
    c.system_id,
    s.name as system_name,
    s.display_name as system_display_name,
    c.type,
    c.priority,
    c.status,
    c.created_at,
    TIMESTAMPDIFF(SECOND, c.created_at, NOW()) as seconds_waiting
FROM `kron_commands` c
INNER JOIN `kron_systems` s ON c.system_id = s.id
WHERE c.status IN ('pending', 'queued')
ORDER BY 
    CASE c.priority
        WHEN 'critical' THEN 1
        WHEN 'high' THEN 2
        WHEN 'normal' THEN 3
        WHEN 'low' THEN 4
    END,
    c.created_at ASC;

