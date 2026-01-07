-- SafeNode - Atualização para Suporte a Desafios Visuais
-- Data: 2024
-- Descrição: Adiciona suporte para evento 'challenge_shown' na tabela de logs de verificação humana

-- IMPORTANTE: Execute este script no seu banco de dados antes de usar o desafio visual

-- 1. Modificar ENUM da tabela safenode_human_verification_logs para incluir 'challenge_shown'
ALTER TABLE `safenode_human_verification_logs` 
MODIFY COLUMN `event_type` ENUM('human_validated','bot_blocked','access_allowed','challenge_shown') NOT NULL;

-- 2. Verificar se a alteração foi aplicada corretamente
-- Execute esta query para confirmar:
-- SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = DATABASE() 
-- AND TABLE_NAME = 'safenode_human_verification_logs' 
-- AND COLUMN_NAME = 'event_type';

-- Nota: Se você estiver usando MariaDB/MySQL e a alteração falhar com erro de sintaxe,
-- pode ser necessário recriar a tabela. Veja o script alternativo abaixo.
