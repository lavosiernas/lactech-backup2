-- Tabela para armazenar pagamentos/transações da Asaas
CREATE TABLE IF NOT EXISTS `safenode_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `asaas_payment_id` varchar(100) DEFAULT NULL COMMENT 'ID do pagamento na Asaas',
  `asaas_customer_id` varchar(100) DEFAULT NULL COMMENT 'ID do cliente na Asaas',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor do pagamento',
  `billing_type` varchar(50) DEFAULT NULL COMMENT 'BOLETO, CREDIT_CARD, PIX, etc',
  `status` varchar(50) NOT NULL DEFAULT 'PENDING' COMMENT 'PENDING, CONFIRMED, RECEIVED, OVERDUE, REFUNDED, etc',
  `due_date` date DEFAULT NULL COMMENT 'Data de vencimento',
  `paid_date` datetime DEFAULT NULL COMMENT 'Data de pagamento',
  `description` text DEFAULT NULL COMMENT 'Descrição do pagamento',
  `external_reference` varchar(255) DEFAULT NULL COMMENT 'Referência externa (ID do usuário, etc)',
  `metadata` json DEFAULT NULL COMMENT 'Metadados adicionais (JSON)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asaas_payment_id` (`asaas_payment_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `due_date` (`due_date`),
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para armazenar clientes da Asaas vinculados aos usuários
CREATE TABLE IF NOT EXISTS `safenode_asaas_customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `asaas_customer_id` varchar(100) NOT NULL COMMENT 'ID do cliente na Asaas',
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `cpf_cnpj` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `asaas_customer_id` (`asaas_customer_id`),
  KEY `email` (`email`),
  CONSTRAINT `fk_asaas_customers_user` FOREIGN KEY (`user_id`) REFERENCES `safenode_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

