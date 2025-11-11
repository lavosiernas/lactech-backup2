-- ==========================================
-- BANCO DE DADOS AGRO NEWS 360 - COMPLETO E CORRIGIDO
-- Nome do Banco: agronews
-- Domínio: agronews360.online
-- Integração com Lactech
-- ==========================================

-- Criar banco de dados (executar apenas uma vez)
CREATE DATABASE IF NOT EXISTS `agronews` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `agronews`;

-- ==========================================
-- TABELAS PRINCIPAIS
-- ==========================================

-- Tabela de usuários (DEVE SER CRIADA PRIMEIRO - outras tabelas dependem dela)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NULL COMMENT 'Pode ser NULL se login for apenas via Google',
  `role` ENUM('admin', 'editor', 'viewer') DEFAULT 'viewer',
  `is_active` TINYINT(1) DEFAULT 1,
  `lactech_user_id` INT(11) DEFAULT NULL COMMENT 'ID do usuário no sistema Lactech (integração)',
  `google_id` VARCHAR(255) NULL COMMENT 'ID único do Google OAuth (AgroNews360 independente)',
  `google_picture` VARCHAR(500) NULL COMMENT 'URL da foto de perfil do Google',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `google_id` (`google_id`),
  KEY `lactech_user_id` (`lactech_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de categorias de notícias
CREATE TABLE IF NOT EXISTS `agronews_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) DEFAULT NULL,
  `color` VARCHAR(20) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir categorias padrão
INSERT IGNORE INTO `agronews_categories` (`name`, `slug`, `icon`, `color`, `description`) VALUES
('Pecuária', 'pecuaria', '🐄', 'blue', 'Notícias sobre pecuária, gado, leite e produção animal'),
('Agricultura', 'agricultura', '🌱', 'green', 'Notícias sobre agricultura, plantio e colheita'),
('Mercado e Economia', 'mercado-economia', '💰', 'yellow', 'Cotações, preços e análises de mercado'),
('Clima e Previsões', 'clima-previsoes', '🌦️', 'cyan', 'Previsões climáticas e alertas meteorológicos'),
('Tecnologia e Inovação', 'tecnologia-inovacao', '🧫', 'purple', 'Tecnologias e inovações no agronegócio'),
('Notícias da Fazenda', 'noticias-fazenda', '📣', 'red', 'Comunicados e notícias internas da fazenda');

-- Tabela de notícias
CREATE TABLE IF NOT EXISTS `agronews_articles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `summary` TEXT DEFAULT NULL,
  `content` LONGTEXT NOT NULL,
  `featured_image` VARCHAR(500) DEFAULT NULL,
  `category_id` INT(11) DEFAULT NULL,
  `author_id` INT(11) DEFAULT NULL,
  `source` VARCHAR(200) DEFAULT NULL,
  `source_url` VARCHAR(500) DEFAULT NULL,
  `is_featured` TINYINT(1) DEFAULT 0,
  `is_published` TINYINT(1) DEFAULT 1,
  `views_count` INT(11) DEFAULT 0,
  `published_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `author_id` (`author_id`),
  KEY `is_published` (`is_published`),
  KEY `is_featured` (`is_featured`),
  KEY `published_at` (`published_at`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_agronews_category` FOREIGN KEY (`category_id`) REFERENCES `agronews_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_agronews_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de cotações de produtos
CREATE TABLE IF NOT EXISTS `agronews_quotations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_name` VARCHAR(100) NOT NULL,
  `product_type` ENUM('grao', 'leite', 'carne', 'outros') DEFAULT 'outros',
  `unit` VARCHAR(20) DEFAULT 'kg',
  `price` DECIMAL(10,2) NOT NULL,
  `variation` DECIMAL(5,2) DEFAULT 0.00,
  `variation_type` ENUM('up', 'down', 'stable') DEFAULT 'stable',
  `market` VARCHAR(100) DEFAULT NULL,
  `region` VARCHAR(100) DEFAULT NULL,
  `quotation_date` DATE NOT NULL,
  `source` VARCHAR(200) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_quotation` (`product_name`, `quotation_date`),
  KEY `product_name` (`product_name`),
  KEY `quotation_date` (`quotation_date`),
  KEY `product_type` (`product_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de dados climáticos
CREATE TABLE IF NOT EXISTS `agronews_weather` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `region` VARCHAR(100) NOT NULL,
  `temperature` DECIMAL(5,2) DEFAULT NULL,
  `min_temperature` DECIMAL(5,2) DEFAULT NULL,
  `max_temperature` DECIMAL(5,2) DEFAULT NULL,
  `humidity` INT(11) DEFAULT NULL,
  `rain_probability` INT(11) DEFAULT NULL,
  `rain_forecast` DECIMAL(5,2) DEFAULT NULL,
  `wind_speed` DECIMAL(5,2) DEFAULT NULL,
  `condition` VARCHAR(50) DEFAULT NULL,
  `forecast_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_weather` (`region`, `forecast_date`),
  KEY `region` (`region`),
  KEY `forecast_date` (`forecast_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de comentários
CREATE TABLE IF NOT EXISTS `agronews_comments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `article_id` INT(11) NOT NULL,
  `user_id` INT(11) DEFAULT NULL,
  `author_name` VARCHAR(100) DEFAULT NULL,
  `author_email` VARCHAR(255) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `is_approved` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`),
  KEY `user_id` (`user_id`),
  KEY `is_approved` (`is_approved`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_agronews_comment_article` FOREIGN KEY (`article_id`) REFERENCES `agronews_articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_agronews_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de newsletter
CREATE TABLE IF NOT EXISTS `agronews_newsletter` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `unsubscribed_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABELAS DE INTEGRAÇÃO COM LACTECH
-- ==========================================

-- Tabela de sincronização com Lactech
CREATE TABLE IF NOT EXISTS `agronews_lactech_sync` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `sync_type` ENUM('user', 'animal', 'production', 'news') NOT NULL,
  `lactech_id` INT(11) NOT NULL COMMENT 'ID no banco Lactech',
  `agronews_id` INT(11) DEFAULT NULL COMMENT 'ID no banco AgroNews (se aplicável)',
  `sync_data` JSON DEFAULT NULL COMMENT 'Dados sincronizados',
  `last_sync` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` ENUM('success', 'error', 'pending') DEFAULT 'pending',
  `error_message` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sync` (`sync_type`, `lactech_id`),
  KEY `sync_status` (`sync_status`),
  KEY `last_sync` (`last_sync`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de notícias relacionadas à fazenda (do Lactech)
CREATE TABLE IF NOT EXISTS `agronews_farm_news` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `article_id` INT(11) NOT NULL,
  `farm_id` INT(11) DEFAULT NULL COMMENT 'ID da fazenda no Lactech',
  `animal_id` INT(11) DEFAULT NULL COMMENT 'ID do animal relacionado (se aplicável)',
  `production_id` INT(11) DEFAULT NULL COMMENT 'ID da produção relacionada (se aplicável)',
  `related_type` ENUM('animal', 'production', 'health', 'breeding', 'other') DEFAULT 'other',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`),
  KEY `farm_id` (`farm_id`),
  KEY `animal_id` (`animal_id`),
  KEY `production_id` (`production_id`),
  CONSTRAINT `fk_farm_news_article` FOREIGN KEY (`article_id`) REFERENCES `agronews_articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de estatísticas compartilhadas (Lactech -> AgroNews)
CREATE TABLE IF NOT EXISTS `agronews_farm_stats` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `farm_id` INT(11) NOT NULL COMMENT 'ID da fazenda no Lactech',
  `stat_date` DATE NOT NULL,
  `total_animals` INT(11) DEFAULT 0,
  `total_production` DECIMAL(10,2) DEFAULT 0.00,
  `daily_production` DECIMAL(10,2) DEFAULT 0.00,
  `active_animals` INT(11) DEFAULT 0,
  `pregnant_animals` INT(11) DEFAULT 0,
  `stats_data` JSON DEFAULT NULL COMMENT 'Dados adicionais em JSON',
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_farm_date` (`farm_id`, `stat_date`),
  KEY `stat_date` (`stat_date`),
  KEY `last_updated` (`last_updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ==========================================

-- Índices compostos para consultas frequentes
ALTER TABLE `agronews_articles` ADD INDEX `idx_published_featured` (`is_published`, `is_featured`, `published_at`);
ALTER TABLE `agronews_articles` ADD INDEX `idx_category_published` (`category_id`, `is_published`, `published_at`);
ALTER TABLE `agronews_quotations` ADD INDEX `idx_type_date` (`product_type`, `quotation_date`);
ALTER TABLE `agronews_weather` ADD INDEX `idx_date_region` (`forecast_date`, `region`);

-- ==========================================
-- DADOS INICIAIS
-- ==========================================

-- Inserir usuário administrador padrão (senha: admin123 - ALTERAR EM PRODUÇÃO!)
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`, `is_active`) VALUES
('Administrador AgroNews360', 'admin@agronews360.online', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);
-- Senha padrão: admin123 (ALTERAR EM PRODUÇÃO!)

-- ==========================================
-- VIEWS ÚTEIS (OPCIONAL)
-- ==========================================

-- View de artigos publicados com categoria
CREATE OR REPLACE VIEW `v_articles_published` AS
SELECT 
    a.id,
    a.title,
    a.slug,
    a.summary,
    a.featured_image,
    a.views_count,
    a.published_at,
    a.created_at,
    c.name as category_name,
    c.icon as category_icon,
    c.color as category_color,
    u.name as author_name
FROM agronews_articles a
LEFT JOIN agronews_categories c ON a.category_id = c.id
LEFT JOIN users u ON a.author_id = u.id
WHERE a.is_published = 1
ORDER BY a.published_at DESC;

-- View de cotações mais recentes
CREATE OR REPLACE VIEW `v_latest_quotations` AS
SELECT 
    q.*,
    ROW_NUMBER() OVER (PARTITION BY q.product_name ORDER BY q.quotation_date DESC) as rn
FROM agronews_quotations q
WHERE q.quotation_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
ORDER BY q.quotation_date DESC;

-- ==========================================
-- PROCEDURES ÚTEIS (OPCIONAL)
-- ==========================================

DELIMITER //

-- Procedure para limpar dados antigos
CREATE PROCEDURE IF NOT EXISTS `sp_cleanup_old_data`()
BEGIN
    -- Limpar cotações com mais de 90 dias
    DELETE FROM agronews_quotations WHERE quotation_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY);
    
    -- Limpar dados climáticos com mais de 30 dias
    DELETE FROM agronews_weather WHERE forecast_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY);
    
    -- Limpar sincronizações com mais de 30 dias e status de erro
    DELETE FROM agronews_lactech_sync WHERE sync_status = 'error' AND last_sync < DATE_SUB(NOW(), INTERVAL 30 DAY);
END //

DELIMITER ;

-- ==========================================
-- TRIGGERS ÚTEIS (OPCIONAL)
-- ==========================================

DELIMITER //

-- Trigger para atualizar updated_at automaticamente
CREATE TRIGGER IF NOT EXISTS `tr_articles_update` 
BEFORE UPDATE ON `agronews_articles`
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END //

DELIMITER ;

-- ==========================================
-- FIM DO SCRIPT
-- ==========================================

