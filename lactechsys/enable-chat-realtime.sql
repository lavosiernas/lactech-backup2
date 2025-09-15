-- SQL para habilitar real-time na tabela chat_messages
-- Execute este SQL no seu Supabase principal

-- 1. Habilitar real-time na tabela chat_messages
ALTER PUBLICATION supabase_realtime ADD TABLE chat_messages;

-- 2. Verificar se a publicação está ativa
SELECT * FROM pg_publication WHERE pubname = 'supabase_realtime';

-- 3. Verificar se a tabela está na publicação
SELECT schemaname, tablename 
FROM pg_publication_tables 
WHERE pubname = 'supabase_realtime' 
AND tablename = 'chat_messages';

-- 4. Se necessário, recriar a publicação
-- DROP PUBLICATION IF EXISTS supabase_realtime;
-- CREATE PUBLICATION supabase_realtime FOR ALL TABLES;

-- 5. Verificar configurações de real-time
SELECT 
    schemaname,
    tablename,
    hasindexes,
    hasrules,
    hastriggers
FROM pg_tables 
WHERE tablename = 'chat_messages';

-- 6. Verificar se há triggers na tabela
SELECT 
    trigger_name,
    event_manipulation,
    action_timing,
    action_statement
FROM information_schema.triggers 
WHERE event_object_table = 'chat_messages';

-- 7. Testar inserção para verificar se real-time funciona
-- INSERT INTO chat_messages (farm_id, sender_id, receiver_id, message) 
-- VALUES ('test-farm-id', 'test-sender-id', 'test-receiver-id', 'Teste real-time');

-- 8. Verificar logs de real-time (se disponível)
-- SELECT * FROM pg_stat_replication;
