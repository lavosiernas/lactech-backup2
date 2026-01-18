-- SafeCode IDE - Database Schema Completo
-- Banco de dados: safecode
-- Versão: 3.0 - COMPLETO
-- Data: 2024

-- ============================================
-- CRIAR BANCO DE DADOS
-- ============================================
CREATE DATABASE IF NOT EXISTS safecode CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE safecode;

-- ============================================
-- TABELA DE USUÁRIOS
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL COMMENT 'NULL para usuários OAuth',
    name VARCHAR(255) NOT NULL,
    avatar_url VARCHAR(500) NULL,
    provider VARCHAR(50) NULL COMMENT 'google, github, email',
    provider_id VARCHAR(255) NULL COMMENT 'ID do usuário no provider OAuth',
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE COMMENT 'Email verificado',
    theme_preference VARCHAR(20) DEFAULT 'dark' COMMENT 'dark, light, auto',
    language_preference VARCHAR(10) DEFAULT 'en' COMMENT 'en, pt, es, etc',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_active (is_active),
    INDEX idx_provider (provider, provider_id),
    UNIQUE KEY unique_provider_user (provider, provider_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE SESSÕES
-- ============================================
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    refresh_token VARCHAR(255) NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL COMMENT 'IPv4 ou IPv6',
    user_agent TEXT NULL,
    device_info VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE PROJETOS
-- ============================================
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'Dono do projeto',
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'URL-friendly name',
    description TEXT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    is_template BOOLEAN DEFAULT FALSE COMMENT 'Projeto usado como template',
    icon_url VARCHAR(500) NULL,
    color VARCHAR(7) NULL COMMENT 'Cor hexadecimal do projeto',
    default_language VARCHAR(50) NULL COMMENT 'Linguagem padrão do projeto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_accessed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_slug (slug),
    INDEX idx_public (is_public),
    INDEX idx_template (is_template),
    INDEX idx_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE ARQUIVOS
-- ============================================
CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    parent_id INT NULL COMMENT 'ID da pasta pai (NULL = raiz)',
    name VARCHAR(255) NOT NULL,
    path VARCHAR(1000) NOT NULL COMMENT 'Caminho completo do arquivo',
    type ENUM('file', 'folder') NOT NULL,
    content LONGTEXT NULL COMMENT 'Conteúdo do arquivo (NULL para pastas)',
    language VARCHAR(50) NULL COMMENT 'Linguagem de programação',
    size BIGINT DEFAULT 0 COMMENT 'Tamanho em bytes',
    encoding VARCHAR(20) DEFAULT 'utf-8',
    is_binary BOOLEAN DEFAULT FALSE,
    mime_type VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL COMMENT 'ID do usuário que criou',
    updated_by INT NULL COMMENT 'ID do usuário que atualizou',
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES files(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_project_id (project_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_path (path(255)),
    INDEX idx_type (type),
    UNIQUE KEY unique_project_path (project_id, path(500))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE VERSÕES DE ARQUIVOS (Histórico)
-- ============================================
CREATE TABLE IF NOT EXISTS file_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT NOT NULL,
    version_number INT NOT NULL,
    content LONGTEXT NOT NULL,
    size BIGINT DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    change_summary VARCHAR(500) NULL COMMENT 'Resumo das mudanças',
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_file_id (file_id),
    INDEX idx_version (file_id, version_number),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE COLABORADORES DO PROJETO
-- ============================================
CREATE TABLE IF NOT EXISTS project_collaborators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner', 'admin', 'editor', 'viewer') DEFAULT 'viewer',
    permissions JSON NULL COMMENT 'Permissões específicas em JSON',
    invited_by INT NULL,
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    joined_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_project_id (project_id),
    INDEX idx_user_id (user_id),
    INDEX idx_active (is_active),
    UNIQUE KEY unique_project_user (project_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE COMPARTILHAMENTO DE PROJETOS
-- ============================================
CREATE TABLE IF NOT EXISTS project_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    share_token VARCHAR(255) NOT NULL UNIQUE,
    access_level ENUM('view', 'edit', 'admin') DEFAULT 'view',
    expires_at TIMESTAMP NULL,
    max_uses INT NULL COMMENT 'Número máximo de usos (NULL = ilimitado)',
    use_count INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (share_token),
    INDEX idx_project_id (project_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE CONFIGURAÇÕES DO USUÁRIO
-- ============================================
CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    editor_settings JSON NULL COMMENT 'Configurações do editor (font, theme, etc)',
    ide_settings JSON NULL COMMENT 'Configurações da IDE (panels, layout, etc)',
    keybindings JSON NULL COMMENT 'Atalhos de teclado personalizados',
    extensions JSON NULL COMMENT 'Extensões instaladas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE LOGS DE ATIVIDADE
-- ============================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    project_id INT NULL,
    file_id INT NULL,
    action_type VARCHAR(50) NOT NULL COMMENT 'create, update, delete, share, etc',
    action_details JSON NULL COMMENT 'Detalhes da ação em JSON',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_project_id (project_id),
    INDEX idx_file_id (file_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE NOTIFICAÇÕES
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL COMMENT 'success, error, warning, info, invitation',
    title VARCHAR(255) NOT NULL,
    message TEXT NULL,
    action_url VARCHAR(500) NULL,
    action_label VARCHAR(100) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE CONVITES
-- ============================================
CREATE TABLE IF NOT EXISTS invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    role ENUM('admin', 'editor', 'viewer') DEFAULT 'viewer',
    invited_by INT NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    accepted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_project_id (project_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE TEMPLATES DE PROJETO
-- ============================================
CREATE TABLE IF NOT EXISTS project_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL COMMENT 'web, mobile, desktop, etc',
    icon_url VARCHAR(500) NULL,
    template_data LONGTEXT NOT NULL COMMENT 'JSON com estrutura do template',
    is_official BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT TRUE,
    created_by INT NULL,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_public (is_public),
    INDEX idx_official (is_official)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE COMENTÁRIOS EM ARQUIVOS
-- ============================================
CREATE TABLE IF NOT EXISTS file_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT NOT NULL,
    user_id INT NOT NULL,
    line_number INT NULL COMMENT 'Linha do comentário (NULL = comentário geral)',
    content TEXT NOT NULL,
    parent_id INT NULL COMMENT 'ID do comentário pai (para respostas)',
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_by INT NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES file_comments(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_file_id (file_id),
    INDEX idx_user_id (user_id),
    INDEX idx_line (file_id, line_number),
    INDEX idx_resolved (is_resolved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VIEWS ÚTEIS
-- ============================================

-- View de usuários ativos
CREATE OR REPLACE VIEW v_active_users AS
SELECT 
    id,
    email,
    name,
    avatar_url,
    provider,
    theme_preference,
    language_preference,
    created_at,
    last_login
FROM users
WHERE is_active = TRUE;

-- View de sessões ativas
CREATE OR REPLACE VIEW v_active_sessions AS
SELECT 
    s.id,
    s.user_id,
    u.email,
    u.name,
    s.created_at,
    s.expires_at,
    s.ip_address,
    s.device_info
FROM user_sessions s
INNER JOIN users u ON s.user_id = u.id
WHERE s.expires_at > NOW()
ORDER BY s.created_at DESC;

-- View de projetos com estatísticas
CREATE OR REPLACE VIEW v_projects_stats AS
SELECT 
    p.id,
    p.name,
    p.slug,
    p.user_id,
    u.name as owner_name,
    p.is_public,
    p.created_at,
    p.updated_at,
    p.last_accessed_at,
    COUNT(DISTINCT f.id) as file_count,
    COUNT(DISTINCT pc.user_id) as collaborator_count
FROM projects p
LEFT JOIN users u ON p.user_id = u.id
LEFT JOIN files f ON p.id = f.project_id
LEFT JOIN project_collaborators pc ON p.id = pc.project_id AND pc.is_active = TRUE
GROUP BY p.id;

-- View de arquivos recentes
CREATE OR REPLACE VIEW v_recent_files AS
SELECT 
    f.id,
    f.project_id,
    p.name as project_name,
    f.name,
    f.path,
    f.type,
    f.updated_at,
    u.name as updated_by_name
FROM files f
INNER JOIN projects p ON f.project_id = p.id
LEFT JOIN users u ON f.updated_by = u.id
ORDER BY f.updated_at DESC
LIMIT 100;

-- ============================================
-- STORED PROCEDURES
-- ============================================

-- Procedure para limpar sessões expiradas
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_clean_expired_sessions()
BEGIN
    DELETE FROM user_sessions WHERE expires_at < NOW();
    DELETE FROM project_shares WHERE expires_at < NOW() AND expires_at IS NOT NULL;
    DELETE FROM invitations WHERE expires_at < NOW() AND accepted_at IS NULL;
END //
DELIMITER ;

-- Procedure para criar projeto com estrutura inicial
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_create_project(
    IN p_user_id INT,
    IN p_name VARCHAR(255),
    IN p_description TEXT,
    IN p_template_id INT
)
BEGIN
    DECLARE v_project_id INT;
    DECLARE v_slug VARCHAR(255);
    
    -- Gerar slug único
    SET v_slug = LOWER(REPLACE(p_name, ' ', '-'));
    SET v_slug = REPLACE(v_slug, '_', '-');
    
    -- Criar projeto
    INSERT INTO projects (user_id, name, slug, description)
    VALUES (p_user_id, p_name, v_slug, p_description);
    
    SET v_project_id = LAST_INSERT_ID();
    
    -- Adicionar owner como colaborador
    INSERT INTO project_collaborators (project_id, user_id, role, joined_at)
    VALUES (v_project_id, p_user_id, 'owner', NOW());
    
    -- Se houver template, aplicar estrutura (implementar depois)
    
    SELECT v_project_id as project_id;
END //
DELIMITER ;

-- ============================================
-- TRIGGERS
-- ============================================

-- Trigger para criar configurações padrão ao criar usuário
DELIMITER //
CREATE TRIGGER IF NOT EXISTS trg_create_user_settings
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO user_settings (user_id, editor_settings, ide_settings)
    VALUES (
        NEW.id,
        JSON_OBJECT(
            'fontSize', 14,
            'fontFamily', 'Monaco, Consolas, monospace',
            'theme', 'dark',
            'wordWrap', true,
            'lineNumbers', true
        ),
        JSON_OBJECT(
            'sidebarOpen', true,
            'terminalOpen', false,
            'previewOpen', true
        )
    );
END //
DELIMITER ;

-- Trigger para atualizar last_accessed_at do projeto
DELIMITER //
CREATE TRIGGER IF NOT EXISTS trg_update_project_access
AFTER UPDATE ON files
FOR EACH ROW
BEGIN
    UPDATE projects 
    SET last_accessed_at = NOW() 
    WHERE id = NEW.project_id;
END //
DELIMITER ;

-- ============================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ============================================

-- Índices compostos para consultas frequentes
CREATE INDEX IF NOT EXISTS idx_files_project_type ON files(project_id, type);
CREATE INDEX IF NOT EXISTS idx_files_project_parent ON files(project_id, parent_id);
CREATE INDEX IF NOT EXISTS idx_activity_user_project ON activity_logs(user_id, project_id);
CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, is_read);

-- ============================================
-- COMENTÁRIOS FINAIS
-- ============================================
-- 
-- Este schema completo inclui:
-- 
-- 1. AUTENTICAÇÃO E USUÁRIOS
--    - users: Usuários com OAuth
--    - user_sessions: Sessões e tokens
--    - user_settings: Configurações personalizadas
-- 
-- 2. PROJETOS E ARQUIVOS
--    - projects: Projetos dos usuários
--    - files: Arquivos e pastas
--    - file_versions: Histórico de versões
--    - file_comments: Comentários em arquivos
-- 
-- 3. COLABORAÇÃO
--    - project_collaborators: Colaboradores
--    - project_shares: Links de compartilhamento
--    - invitations: Convites por email
-- 
-- 4. SISTEMA
--    - activity_logs: Logs de atividade
--    - notifications: Notificações
--    - project_templates: Templates de projeto
-- 
-- 5. VIEWS E PROCEDURES
--    - Views para consultas otimizadas
--    - Stored procedures para operações comuns
--    - Triggers para automação
-- 
-- IMPORTANTE:
-- - Todos os campos de data usam TIMESTAMP
-- - Campos JSON para flexibilidade
-- - Índices otimizados para performance
-- - Foreign keys com CASCADE para integridade
-- - Suporte completo a OAuth (Google, GitHub)
-- - Sistema de permissões por projeto
-- - Histórico de versões de arquivos
-- - Sistema de comentários em código
-- 
-- PRÓXIMOS PASSOS:
-- 1. Executar este script no MySQL
-- 2. Configurar variáveis de ambiente para OAuth
-- 3. Implementar APIs PHP para todas as tabelas
-- 4. Criar interface React para gerenciar projetos
