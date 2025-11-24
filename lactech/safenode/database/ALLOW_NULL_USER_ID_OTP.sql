-- Script para permitir user_id NULL na tabela safenode_otp_codes
-- Isso permite criar códigos OTP antes de criar o usuário no banco

ALTER TABLE `safenode_otp_codes` 
MODIFY COLUMN `user_id` int(11) NULL;

-- Adicionar índice para melhor performance em buscas por email
ALTER TABLE `safenode_otp_codes`
ADD INDEX `idx_email_action` (`email`, `action`);

