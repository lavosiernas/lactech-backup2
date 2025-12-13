-- Remover configurações antigas do Google reCAPTCHA
-- Execute este SQL ANTES de executar add-safenode-recaptcha.sql
-- 
-- Este script remove as configurações do Google reCAPTCHA que não são mais usadas

-- Remover recaptcha_site_key
DELETE FROM safenode_settings WHERE setting_key = 'recaptcha_site_key';

-- Remover recaptcha_secret_key
DELETE FROM safenode_settings WHERE setting_key = 'recaptcha_secret_key';

-- Remover recaptcha_version (será substituído por safenode_recaptcha_version)
DELETE FROM safenode_settings WHERE setting_key = 'recaptcha_version';

-- Remover recaptcha_action (será substituído por safenode_recaptcha_action)
DELETE FROM safenode_settings WHERE setting_key = 'recaptcha_action';

-- Remover recaptcha_score_threshold (será substituído por safenode_recaptcha_score_threshold)
DELETE FROM safenode_settings WHERE setting_key = 'recaptcha_score_threshold';

-- Remover recaptcha_enabled (será substituído por safenode_recaptcha_enabled)
DELETE FROM safenode_settings WHERE setting_key = 'recaptcha_enabled';

-- Verificar se removeu tudo
SELECT 'Configurações do Google reCAPTCHA removidas com sucesso!' AS status;

