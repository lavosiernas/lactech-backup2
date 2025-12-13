-- SafeNode reCAPTCHA Próprio - Configurações e Tabelas
-- Execute este SQL no banco de dados
-- 
-- ⚠️ IMPORTANTE: Execute primeiro remove-google-recaptcha-settings.sql
-- para remover as configurações antigas do Google reCAPTCHA

-- Criar tabela para armazenar challenges
CREATE TABLE IF NOT EXISTS safenode_recaptcha_challenges (
    id INT(11) NOT NULL AUTO_INCREMENT,
    challenge_id VARCHAR(64) NOT NULL,
    api_key_id INT(11) DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    action VARCHAR(50) DEFAULT 'submit',
    verified TINYINT(1) DEFAULT 0,
    score DECIMAL(3,2) DEFAULT NULL,
    success TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY challenge_id (challenge_id),
    KEY api_key_id (api_key_id),
    KEY ip_address (ip_address),
    KEY created_at (created_at),
    KEY verified (verified),
    FOREIGN KEY (api_key_id) REFERENCES safenode_hv_api_keys(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurações do SafeNode reCAPTCHA (sem Google)
INSERT INTO safenode_settings (setting_key, setting_value, setting_type, category, description, is_editable)
SELECT 'safenode_recaptcha_enabled', '0', 'boolean', 'security', 'Habilitar reCAPTCHA SafeNode (sistema próprio, sem Google)', 1
WHERE NOT EXISTS (SELECT 1 FROM safenode_settings WHERE setting_key = 'safenode_recaptcha_enabled');

UPDATE safenode_settings 
SET description = 'Habilitar reCAPTCHA SafeNode (sistema próprio, sem Google)',
    setting_type = 'boolean',
    category = 'security',
    is_editable = 1
WHERE setting_key = 'safenode_recaptcha_enabled';

-- Versão (v2 = checkbox, v3 = invisível)
INSERT INTO safenode_settings (setting_key, setting_value, setting_type, category, description, is_editable)
SELECT 'safenode_recaptcha_version', 'v2', 'string', 'security', 'Versão do reCAPTCHA SafeNode: v2 (checkbox) ou v3 (invisível)', 1
WHERE NOT EXISTS (SELECT 1 FROM safenode_settings WHERE setting_key = 'safenode_recaptcha_version');

UPDATE safenode_settings 
SET description = 'Versão do reCAPTCHA SafeNode: v2 (checkbox) ou v3 (invisível)',
    setting_type = 'string',
    category = 'security',
    is_editable = 1
WHERE setting_key = 'safenode_recaptcha_version';

-- Ação (para v3)
INSERT INTO safenode_settings (setting_key, setting_value, setting_type, category, description, is_editable)
SELECT 'safenode_recaptcha_action', 'submit', 'string', 'security', 'Nome da ação para reCAPTCHA v3 (ex: login, register, submit)', 1
WHERE NOT EXISTS (SELECT 1 FROM safenode_settings WHERE setting_key = 'safenode_recaptcha_action');

UPDATE safenode_settings 
SET description = 'Nome da ação para reCAPTCHA v3 (ex: login, register, submit)',
    setting_type = 'string',
    category = 'security',
    is_editable = 1
WHERE setting_key = 'safenode_recaptcha_action';

-- Score threshold (para v3)
INSERT INTO safenode_settings (setting_key, setting_value, setting_type, category, description, is_editable)
SELECT 'safenode_recaptcha_score_threshold', '0.5', 'float', 'security', 'Score mínimo para aprovar em v3 (0.0 = bot, 1.0 = humano). Recomendado: 0.5', 1
WHERE NOT EXISTS (SELECT 1 FROM safenode_settings WHERE setting_key = 'safenode_recaptcha_score_threshold');

UPDATE safenode_settings 
SET description = 'Score mínimo para aprovar em v3 (0.0 = bot, 1.0 = humano). Recomendado: 0.5',
    setting_type = 'float',
    category = 'security',
    is_editable = 1
WHERE setting_key = 'safenode_recaptcha_score_threshold';

