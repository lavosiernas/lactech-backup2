-- Tabela para grupos de ordenha
-- Permite salvar grupos de animais que vão para ordenha juntos
CREATE TABLE IF NOT EXISTS `milking_groups` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `farm_id` INT(11) NOT NULL,
    `group_name` VARCHAR(255) NOT NULL COMMENT 'Nome do grupo (ex: Grupo 1, Manhã, etc.)',
    `animal_ids` TEXT NOT NULL COMMENT 'IDs dos animais separados por vírgula',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Se o grupo está ativo',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_farm_id` (`farm_id`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Grupos de animais para ordenha';

