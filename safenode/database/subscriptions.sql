-- SafeNode V1 - Tabela de Subscriptions
-- Modelo: R$ 29/mês, 10.000 eventos/mês

CREATE TABLE IF NOT EXISTS `safenode_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_type` enum('free_trial','paid') NOT NULL DEFAULT 'free_trial',
  `events_limit` int(11) NOT NULL DEFAULT 10000,
  `events_used` int(11) NOT NULL DEFAULT 0,
  `billing_cycle_start` date NOT NULL,
  `billing_cycle_end` date NOT NULL,
  `status` enum('active','cancelled','expired','trial_expired') NOT NULL DEFAULT 'active',
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `billing_cycle` (`billing_cycle_start`,`billing_cycle_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir subscription para usuários existentes (free trial de 14 dias)
INSERT INTO `safenode_subscriptions` (`user_id`, `plan_type`, `events_limit`, `events_used`, `billing_cycle_start`, `billing_cycle_end`, `status`)
SELECT 
  `id` as user_id,
  'free_trial' as plan_type,
  10000 as events_limit,
  0 as events_used,
  CURDATE() as billing_cycle_start,
  DATE_ADD(CURDATE(), INTERVAL 14 DAY) as billing_cycle_end,
  'active' as status
FROM `safenode_users`
WHERE `id` NOT IN (SELECT `user_id` FROM `safenode_subscriptions`);



