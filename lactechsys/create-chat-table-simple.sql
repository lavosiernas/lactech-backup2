-- SQL Simplificado para criar a tabela chat_messages
-- Execute este SQL no seu Supabase principal

-- 1. Criar tabela chat_messages
CREATE TABLE IF NOT EXISTS chat_messages (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    farm_id UUID NOT NULL,
    sender_id UUID NOT NULL,
    receiver_id UUID NOT NULL,
    message TEXT NOT NULL,
    message_type TEXT DEFAULT 'text',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 2. Criar índices
CREATE INDEX IF NOT EXISTS idx_chat_messages_farm_id ON chat_messages(farm_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_sender_receiver ON chat_messages(sender_id, receiver_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_created_at ON chat_messages(created_at);
CREATE INDEX IF NOT EXISTS idx_chat_messages_is_read ON chat_messages(is_read);

-- 3. Adicionar colunas na tabela users
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP WITH TIME ZONE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_online BOOLEAN DEFAULT FALSE;

-- 4. Criar índices para as novas colunas
CREATE INDEX IF NOT EXISTS idx_users_last_login ON users(last_login);
CREATE INDEX IF NOT EXISTS idx_users_is_online ON users(is_online);

-- 5. Habilitar RLS na tabela chat_messages
ALTER TABLE chat_messages ENABLE ROW LEVEL SECURITY;

-- 6. Criar políticas RLS básicas
CREATE POLICY "Enable read access for users in same farm" ON chat_messages
    FOR SELECT USING (farm_id IN (
        SELECT farm_id FROM users WHERE id = auth.uid()
    ));

CREATE POLICY "Enable insert for authenticated users" ON chat_messages
    FOR INSERT WITH CHECK (sender_id = auth.uid());

CREATE POLICY "Enable update for message receivers" ON chat_messages
    FOR UPDATE USING (receiver_id = auth.uid());
