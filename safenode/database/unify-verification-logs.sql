-- =====================================================
-- UNIFICAÇÃO DE TABELAS DE VERIFICAÇÃO HUMANA
-- SafeNode - Migração para arquitetura unificada
-- =====================================================
-- 
-- OBJETIVO: Unificar safenode_hv_attempts e safenode_human_verification_logs
-- em uma única tabela (safenode_human_verification_logs)
--
-- EXECUTAR EM ORDEM:
-- 1. Fazer backup do banco antes de executar
-- 2. Executar este script em ambiente de desenvolvimento primeiro
-- 3. Testar todas as funcionalidades
-- 4. Executar em produção após validação

-- PASSO 1: Adicionar api_key_id e reason em safenode_human_verification_logs
ALTER TABLE `safenode_human_verification_logs`
ADD COLUMN `api_key_id` INT(11) DEFAULT NULL AFTER `site_id`,
ADD COLUMN `reason` VARCHAR(255) DEFAULT NULL AFTER `country_code`,
ADD INDEX `idx_api_key_id` (`api_key_id`),
ADD INDEX `idx_site_id_api_key` (`site_id`, `api_key_id`);

-- PASSO 2: Tornar site_id opcional (pode ser NULL quando vem do SDK)
ALTER TABLE `safenode_human_verification_logs`
MODIFY COLUMN `site_id` INT(11) DEFAULT NULL;

-- PASSO 3: Migrar dados de safenode_hv_attempts para safenode_human_verification_logs
-- Mapeamento: attempt_type -> event_type
-- 'init' -> 'access_allowed'
-- 'validate' -> 'human_validated'
-- 'failed' -> 'bot_blocked'
-- 'suspicious' -> 'bot_blocked'

INSERT INTO `safenode_human_verification_logs` 
    (`api_key_id`, `site_id`, `ip_address`, `event_type`, `request_uri`, `request_method`, `user_agent`, `referer`, `country_code`, `reason`, `created_at`)
SELECT 
    a.`api_key_id`,
    COALESCE(
        (SELECT s.id 
         FROM safenode_sites s
         INNER JOIN safenode_hv_api_keys k ON s.user_id = k.user_id
         WHERE k.id = a.api_key_id 
           AND a.referer IS NOT NULL
           AND (
               s.domain = SUBSTRING_INDEX(SUBSTRING_INDEX(a.referer, '://', -1), '/', 1)
               OR SUBSTRING_INDEX(SUBSTRING_INDEX(a.referer, '://', -1), '/', 1) LIKE CONCAT('%.', s.domain)
               OR SUBSTRING_INDEX(SUBSTRING_INDEX(a.referer, '://', -1), '/', 1) LIKE CONCAT('www.', s.domain)
           )
         LIMIT 1),
        NULL
    ) as `site_id`,
    a.`ip_address`,
    CASE 
        WHEN a.`attempt_type` = 'init' THEN 'access_allowed'
        WHEN a.`attempt_type` = 'validate' THEN 'human_validated'
        WHEN a.`attempt_type` IN ('failed', 'suspicious') THEN 'bot_blocked'
        ELSE 'access_allowed'
    END as `event_type`,
    COALESCE(
        CASE 
            WHEN a.`referer` IS NOT NULL THEN
                CONCAT(
                    COALESCE(SUBSTRING_INDEX(SUBSTRING_INDEX(a.referer, '://', -1), '/', -1), '/'),
                    CASE WHEN LOCATE('?', a.referer) > 0 THEN CONCAT('?', SUBSTRING_INDEX(a.referer, '?', -1)) ELSE '' END
                )
            ELSE '/'
        END,
        '/'
    ) as `request_uri`,
    'GET' as `request_method`,
    a.`user_agent`,
    a.`referer`,
    COALESCE(a.`country_code`, NULL) as `country_code`,
    a.`reason`,
    a.`created_at`
FROM `safenode_hv_attempts` a
WHERE a.`created_at` >= DATE_SUB(NOW(), INTERVAL 90 DAY) -- Migrar apenas últimos 90 dias
  AND NOT EXISTS (
      -- Evitar duplicatas: verificar se já existe registro similar
      SELECT 1 FROM safenode_human_verification_logs l
      WHERE l.api_key_id = a.api_key_id
        AND l.ip_address = a.ip_address
        AND ABS(TIMESTAMPDIFF(SECOND, l.created_at, a.created_at)) < 2
  );

-- PASSO 4: Criar view de compatibilidade temporária (opcional, para transição suave)
-- Isso permite que código antigo ainda funcione temporariamente
DROP VIEW IF EXISTS `safenode_hv_attempts_view`;
CREATE VIEW `safenode_hv_attempts_view` AS
SELECT 
    `id`,
    `api_key_id`,
    `ip_address`,
    `user_agent`,
    `referer`,
    CASE 
        WHEN `event_type` = 'access_allowed' THEN 'init'
        WHEN `event_type` = 'human_validated' THEN 'validate'
        WHEN `event_type` = 'bot_blocked' THEN 'failed'
        ELSE 'init'
    END as `attempt_type`,
    `reason`,
    `created_at`
FROM `safenode_human_verification_logs`
WHERE `api_key_id` IS NOT NULL;

-- NOTA IMPORTANTE:
-- - A tabela safenode_hv_attempts será mantida como backup por alguns dias
-- - Após validar que tudo funciona, você pode removê-la
-- - Não remova ainda! Mantenha como backup por pelo menos 30 dias
