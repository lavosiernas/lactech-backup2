-- Adicionar coluna new_password na tabela password_requests
-- Esta coluna armazenará a nova senha fornecida pelo usuário

ALTER TABLE password_requests 
ADD COLUMN new_password TEXT;

-- Adicionar comentário para documentar a coluna
COMMENT ON COLUMN password_requests.new_password IS 'Nova senha fornecida pelo usuário (será aplicada após aprovação do gerente)';

-- Criar índice para melhorar performance (opcional)
CREATE INDEX IF NOT EXISTS idx_password_requests_new_password ON password_requests(new_password) WHERE new_password IS NOT NULL;
