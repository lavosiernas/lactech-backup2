-- =====================================================
-- TABELAS DE GESTÃO DE ESTOQUE/INSUMOS
-- Sistema completo de controle de rações, medicamentos e insumos
-- =====================================================

-- Tabela de Produtos/Insumos
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Nome do produto',
  `type` enum('racao','medicamento','insumo','outro') NOT NULL DEFAULT 'insumo' COMMENT 'Tipo de produto',
  `unit` varchar(50) NOT NULL DEFAULT 'unidade' COMMENT 'Unidade de medida (kg, litro, unidade, etc)',
  `current_stock` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Estoque atual',
  `min_stock` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Estoque mínimo (para alertas)',
  `max_stock` decimal(10,2) DEFAULT NULL COMMENT 'Estoque máximo',
  `cost_per_unit` decimal(10,2) DEFAULT NULL COMMENT 'Custo por unidade',
  `supplier` varchar(255) DEFAULT NULL COMMENT 'Fornecedor',
  `description` text DEFAULT NULL COMMENT 'Descrição do produto',
  `barcode` varchar(100) DEFAULT NULL COMMENT 'Código de barras',
  `category` varchar(100) DEFAULT NULL COMMENT 'Categoria do produto',
  `location` varchar(100) DEFAULT NULL COMMENT 'Localização no estoque',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Produto ativo',
  `farm_id` int(11) NOT NULL DEFAULT 1 COMMENT 'ID da fazenda',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_farm_id` (`farm_id`),
  KEY `idx_type` (`type`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_stock` (`current_stock`, `min_stock`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de produtos/insumos';

-- Tabela de Movimentações de Estoque (Entrada/Saída)
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL COMMENT 'ID do produto',
  `movement_type` enum('entrada','saida','ajuste','transferencia') NOT NULL COMMENT 'Tipo de movimentação',
  `quantity` decimal(10,2) NOT NULL COMMENT 'Quantidade movimentada',
  `unit_price` decimal(10,2) DEFAULT NULL COMMENT 'Preço unitário na movimentação',
  `total_cost` decimal(10,2) DEFAULT NULL COMMENT 'Custo total',
  `stock_before` decimal(10,2) NOT NULL COMMENT 'Estoque antes da movimentação',
  `stock_after` decimal(10,2) NOT NULL COMMENT 'Estoque depois da movimentação',
  `reference` varchar(100) DEFAULT NULL COMMENT 'Número de nota fiscal, pedido, etc',
  `notes` text DEFAULT NULL COMMENT 'Observações',
  `movement_date` date NOT NULL COMMENT 'Data da movimentação',
  `recorded_by` int(11) DEFAULT NULL COMMENT 'ID do usuário que registrou',
  `farm_id` int(11) NOT NULL DEFAULT 1 COMMENT 'ID da fazenda',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_movement_type` (`movement_type`),
  KEY `idx_movement_date` (`movement_date`),
  KEY `idx_farm_id` (`farm_id`),
  KEY `idx_recorded_by` (`recorded_by`),
  CONSTRAINT `fk_stock_movements_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_movements_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de movimentações de estoque';

-- Tabela de Alertas de Estoque
CREATE TABLE IF NOT EXISTS `stock_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL COMMENT 'ID do produto',
  `alert_type` enum('estoque_baixo','estoque_zerado','estoque_excesso') NOT NULL DEFAULT 'estoque_baixo' COMMENT 'Tipo de alerta',
  `current_stock` decimal(10,2) NOT NULL COMMENT 'Estoque atual no momento do alerta',
  `min_stock` decimal(10,2) NOT NULL COMMENT 'Estoque mínimo configurado',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Alerta lido',
  `notified_at` timestamp NULL DEFAULT NULL COMMENT 'Data da notificação',
  `resolved_at` timestamp NULL DEFAULT NULL COMMENT 'Data da resolução',
  `farm_id` int(11) NOT NULL DEFAULT 1 COMMENT 'ID da fazenda',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_farm_id` (`farm_id`),
  CONSTRAINT `fk_stock_alerts_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de alertas de estoque';

-- Índices adicionais para performance
-- Nota: CREATE INDEX IF NOT EXISTS não é suportado no MariaDB 10.4.32, então criamos os índices dentro das tabelas
-- Os índices idx_products_name e idx_products_category já estão criados implicitamente se necessário
-- O índice composto idx_stock_movements_product_date pode ser criado manualmente se necessário:
-- CREATE INDEX `idx_stock_movements_product_date` ON `stock_movements` (`product_id`, `movement_date` DESC);

