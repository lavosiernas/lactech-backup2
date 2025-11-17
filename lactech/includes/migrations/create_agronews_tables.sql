-- ==========================================
-- TABELAS PARA SISTEMA AGRO NEWS 360
-- ==========================================

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
INSERT INTO `agronews_categories` (`name`, `slug`, `icon`, `color`, `description`) VALUES
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
  KEY `region` (`region`),
  KEY `forecast_date` (`forecast_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de comentários (opcional)
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
  CONSTRAINT `fk_agronews_comment_article` FOREIGN KEY (`article_id`) REFERENCES `agronews_articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_agronews_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de newsletter (opcional)
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










