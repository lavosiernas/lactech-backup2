-- SQL para criar a tabela chat_messages no banco principal
-- Execute este SQL no seu Supabase principal

-- 1. Criar tabela chat_messages
CREATE TABLE IF NOT EXISTS chat_messages (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    farm_id UUID NOT NULL,
    sender_id UUID NOT NULL,
    receiver_id UUID NOT NULL,
    message TEXT NOT NULL,
    message_type TEXT DEFAULT 'text' CHECK (message_type IN ('text', 'image', 'file')),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 2. Criar índices para performance
CREATE INDEX IF NOT EXISTS idx_chat_messages_farm_id ON chat_messages(farm_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_sender_receiver ON chat_messages(sender_id, receiver_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_created_at ON chat_messages(created_at);
CREATE INDEX IF NOT EXISTS idx_chat_messages_is_read ON chat_messages(is_read);

-- 3. Adicionar foreign keys (remover se existirem primeiro)
DO $$ 
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_chat_messages_sender') THEN
        ALTER TABLE chat_messages DROP CONSTRAINT fk_chat_messages_sender;
    END IF;
    
    IF EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'fk_chat_messages_receiver') THEN
        ALTER TABLE chat_messages DROP CONSTRAINT fk_chat_messages_receiver;
    END IF;
END $$;

ALTER TABLE chat_messages 
ADD CONSTRAINT fk_chat_messages_sender 
FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE chat_messages 
ADD CONSTRAINT fk_chat_messages_receiver 
FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE;

-- 4. Habilitar RLS
ALTER TABLE chat_messages ENABLE ROW LEVEL SECURITY;

-- 5. Criar políticas RLS (remover se existirem primeiro)
DO $$ 
BEGIN
    IF EXISTS (SELECT 1 FROM pg_policies WHERE policyname = 'Users can view farm messages' AND tablename = 'chat_messages') THEN
        DROP POLICY "Users can view farm messages" ON chat_messages;
    END IF;
    
    IF EXISTS (SELECT 1 FROM pg_policies WHERE policyname = 'Users can send messages' AND tablename = 'chat_messages') THEN
        DROP POLICY "Users can send messages" ON chat_messages;
    END IF;
    
    IF EXISTS (SELECT 1 FROM pg_policies WHERE policyname = 'Users can update own messages' AND tablename = 'chat_messages') THEN
        DROP POLICY "Users can update own messages" ON chat_messages;
    END IF;
END $$;

CREATE POLICY "Users can view farm messages" ON chat_messages
    FOR SELECT USING (farm_id IN (
        SELECT farm_id FROM users WHERE id = auth.uid()
    ));

CREATE POLICY "Users can send messages" ON chat_messages
    FOR INSERT WITH CHECK (sender_id = auth.uid());

CREATE POLICY "Users can update own messages" ON chat_messages
    FOR UPDATE USING (receiver_id = auth.uid());

-- 6. Adicionar coluna last_login na tabela users se não existir
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP WITH TIME ZONE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_online BOOLEAN DEFAULT FALSE;

-- 7. Criar índices para last_login
CREATE INDEX IF NOT EXISTS idx_users_last_login ON users(last_login);
CREATE INDEX IF NOT EXISTS idx_users_is_online ON users(is_online);

-- 8. Função para marcar mensagens como lidas
CREATE OR REPLACE FUNCTION mark_messages_as_read(sender_uuid UUID, receiver_uuid UUID)
RETURNS INTEGER AS $$
DECLARE
    updated_count INTEGER;
BEGIN
    UPDATE chat_messages 
    SET is_read = TRUE 
    WHERE sender_id = sender_uuid 
    AND receiver_id = receiver_uuid 
    AND is_read = FALSE;
    
    GET DIAGNOSTICS updated_count = ROW_COUNT;
    RETURN updated_count;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 9. Função para obter conversas recentes
CREATE OR REPLACE FUNCTION get_recent_conversations(user_uuid UUID)
RETURNS TABLE (
    other_user_id UUID,
    other_user_name TEXT,
    other_user_role TEXT,
    last_message TEXT,
    last_message_time TIMESTAMP WITH TIME ZONE,
    unread_count BIGINT
) AS $$
BEGIN
    RETURN QUERY
    WITH conversation_partners AS (
        SELECT DISTINCT
            CASE 
                WHEN sender_id = user_uuid THEN receiver_id
                ELSE sender_id
            END as partner_id
        FROM chat_messages
        WHERE sender_id = user_uuid OR receiver_id = user_uuid
    ),
    last_messages AS (
        SELECT 
            cp.partner_id,
            cm.message,
            cm.created_at,
            ROW_NUMBER() OVER (PARTITION BY cp.partner_id ORDER BY cm.created_at DESC) as rn
        FROM conversation_partners cp
        JOIN chat_messages cm ON (
            (cm.sender_id = user_uuid AND cm.receiver_id = cp.partner_id) OR
            (cm.sender_id = cp.partner_id AND cm.receiver_id = user_uuid)
        )
    ),
    unread_counts AS (
        SELECT 
            sender_id,
            COUNT(*) as unread_count
        FROM chat_messages
        WHERE receiver_id = user_uuid AND is_read = FALSE
        GROUP BY sender_id
    )
    SELECT 
        lm.partner_id,
        u.name,
        u.role,
        lm.message,
        lm.created_at,
        COALESCE(uc.unread_count, 0)
    FROM last_messages lm
    JOIN users u ON u.id = lm.partner_id
    LEFT JOIN unread_counts uc ON uc.sender_id = lm.partner_id
    WHERE lm.rn = 1
    ORDER BY lm.created_at DESC;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;
