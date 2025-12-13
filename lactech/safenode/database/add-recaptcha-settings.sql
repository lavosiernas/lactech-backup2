-- Adicionar configurações de reCAPTCHA no SafeNode
-- Execute este SQL no banco de dados
-- 
-- Nota: Este script verifica se cada configuração já existe antes de inserir.
-- Se existir, apenas atualiza a descrição, tipo e categoria (mantém o valor atual).

-- recaptcha_site_key
INSERT INTO safenode_settings (setting_key, setting_value, setting_type, category, description, is_editable)
SELECT 'recaptcha_site_key', '', 'string', 'security', 'Google reCAPTCHA Site Key (obtenha em https://www.google.com/recaptcha/admin)', 1
WHERE NOT EXISTS (SELECT 1 FROM safenode_settings WHERE setting_key = 'recaptcha_site_key');

UPDATE safenode_settings 
SET description = 'Google reCAPTCHA Site Key (obtenha em https://www.google.com/recaptcha/admin)',
    setting_type = 'string',
    category = 'security',
    is_editable = 1
WHERE setting_key = 'recaptcha_site_key';

-- recaptcha_secret_key
INSERT INTO safenode_settings (setting_key, setting_value, setting_type, category, description, is_editable)
SELECT 'recaptcha_secret_key', '', 'string', 'security', 'Google reCAPTCHA Secret Key', 1
WHERE NOT EXISTS (SELECT 1 FROM safenode_settings WHERE setting_key = 'recaptcha_secret_key');

UPDATE safenode_settings 
SET description = 'Google reCAPTCHA Secret Key',
    setting_type = 'string',
    category = 'security',
    is_editable = 1
WHERE setting_key = 'recaptcha_secret_key';

-- recaptcha_version
INSERT INTO safenode_settings (setting_key, setting_value, setting_type, category, description, is_editable)
SELECT 'recaptcha_version', 'v2', 'string', 'security', 'Versão do reCAPTCHA: v2 (checkbox) ou v3 (invisível)', 1
WHERE NOT EXISTS (SELECT 1 FROM safenode_settings WHERE setting_key = 'recaptcha_version');

UPDATE safenode_settings 
SET description = 'Versão do reCAPTCHA: v2 (checkbox) ou v3 (invisível)',
    setting_type = 'string',
    category = 'security',
    is_editable = 1
WHERE setting_key = 'recaptcha_version';

-- recaptcha_action
INSERT INTO safenode_settings (setting_key, setting_value, setting_type, category, description, is_editable)
SELECT 'recaptcha_action', 'submit', 'string', 'security', 'Nome da ação para reCAPTCHA v3 (ex: login, register, submit)', 1
WHERE NOT EXISTS (SELECT 1 FROM safenode_settings WHERE setting_key = 'recaptcha_action');

UPDATE safenode_settings 
SET description = 'Nome da ação para reCAPTCHA v3 (ex: login, register, submit)',
    setting_type = 'string',
    category = 'security',
    is_editable = 1
WHERE setting_key = 'recaptcha_action';

-- recaptcha_score_threshold
INSERT INTO safenode_settings (setting_key, setting_value, setting_type, category, description, is_editable)
SELECT 'recaptcha_score_threshold', '0.5', 'float', 'security', 'Score mínimo para aprovar em v3 (0.0 = bot, 1.0 = humano). Recomendado: 0.5', 1
WHERE NOT EXISTS (SELECT 1 FROM safenode_settings WHERE setting_key = 'recaptcha_score_threshold');

UPDATE safenode_settings 
SET description = 'Score mínimo para aprovar em v3 (0.0 = bot, 1.0 = humano). Recomendado: 0.5',
    setting_type = 'float',
    category = 'security',
    is_editable = 1
WHERE setting_key = 'recaptcha_score_threshold';

-- recaptcha_enabled
INSERT INTO safenode_settings (setting_key, setting_value, setting_type, category, description, is_editable)
SELECT 'recaptcha_enabled', '0', 'boolean', 'security', 'Habilitar reCAPTCHA no login (1 = sim, 0 = não)', 1
WHERE NOT EXISTS (SELECT 1 FROM safenode_settings WHERE setting_key = 'recaptcha_enabled');

UPDATE safenode_settings 
SET description = 'Habilitar reCAPTCHA no login (1 = sim, 0 = não)',
    setting_type = 'boolean',
    category = 'security',
    is_editable = 1
WHERE setting_key = 'recaptcha_enabled';

