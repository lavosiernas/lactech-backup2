-- ==========================================
-- BANCO DE DADOS AGRO NEWS 360 - VERSÃO LIMPA
-- Apenas tabelas e funções realmente utilizadas
-- ==========================================

-- NOTA: O banco de dados deve ser criado pelo painel de controle da hospedagem
-- ou já deve existir. Este script apenas cria as tabelas.
-- Se necessário, selecione o banco manualmente antes de executar:
-- USE `u311882628_agronews`; (ou o nome do seu banco)

-- ==========================================
-- TABELAS PRINCIPAIS
-- ==========================================

-- Tabela de usuários (autenticação)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NULL COMMENT 'Pode ser NULL se login for apenas via Google',
  `role` VARCHAR(20) DEFAULT 'viewer' COMMENT 'Todos os usuários são tratados igualmente (sem distinção de admin)',
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
('Notícias Gerais', 'noticias-gerais', '📣', 'red', 'Notícias gerais do agronegócio');

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
  KEY `idx_published_featured` (`is_published`, `is_featured`, `published_at`),
  KEY `idx_category_published` (`category_id`, `is_published`, `published_at`),
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
  KEY `product_type` (`product_type`),
  KEY `idx_type_date` (`product_type`, `quotation_date`)
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
  KEY `forecast_date` (`forecast_date`),
  KEY `idx_date_region` (`forecast_date`, `region`)
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
-- DADOS INICIAIS
-- ==========================================

-- Nota: Não há usuário admin padrão pois o AgroNews é alimentado pela web
-- Todos os usuários são criados via login (Google ou Lactech) com role 'viewer'

-- ==========================================
-- FIM DO SCRIPT
-- ==========================================

