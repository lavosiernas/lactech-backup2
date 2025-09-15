# Configuração do Banco de Dados do Chat

## 📋 Visão Geral

O sistema agora utiliza **dois bancos de dados separados**:
- **Banco Principal**: Sistema de gestão leiteira (produção, usuários, fazendas)
- **Banco do Chat**: Sistema de mensagens e comunicação

## 🗄️ Estrutura do Banco do Chat

### Tabelas Necessárias

#### 1. Tabela `users`
```sql
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name TEXT NOT NULL,
    email TEXT,
    farm_id UUID NOT NULL,
    role TEXT NOT NULL,
    last_login TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Índices para performance
CREATE INDEX idx_users_farm_id ON users(farm_id);
CREATE INDEX idx_users_last_login ON users(last_login);
```

#### 2. Tabela `chat_messages`
```sql
CREATE TABLE chat_messages (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    farm_id UUID NOT NULL,
    sender_id UUID NOT NULL,
    receiver_id UUID NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Índices para performance
CREATE INDEX idx_chat_messages_farm_id ON chat_messages(farm_id);
CREATE INDEX idx_chat_messages_sender_receiver ON chat_messages(sender_id, receiver_id);
CREATE INDEX idx_chat_messages_created_at ON chat_messages(created_at);

-- Foreign Keys
ALTER TABLE chat_messages 
ADD CONSTRAINT fk_chat_messages_sender 
FOREIGN KEY (sender_id) REFERENCES users(id);

ALTER TABLE chat_messages 
ADD CONSTRAINT fk_chat_messages_receiver 
FOREIGN KEY (receiver_id) REFERENCES users(id);
```

## 🔧 Configuração

### 1. Criar o Banco do Chat
1. Acesse o Supabase Dashboard
2. Crie um novo projeto: `lactech-chat`
3. Use a URL: `https://fhuqberspucyysxcreqv.supabase.co`
4. Use a chave: `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZodXFiZXJzcHVjeXlzeGNyZXF2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTc4MDYzOTUsImV4cCI6MjA3MzM4MjM5NX0.DIwjOt1gz32cuXZ8Nk6KvnzJqZtHhDUU5kLZnLM94OA`

### 2. Executar Scripts SQL
Execute os scripts SQL acima no SQL Editor do Supabase.

### 3. Configurar RLS (Row Level Security)
```sql
-- Habilitar RLS
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE chat_messages ENABLE ROW LEVEL SECURITY;

-- Política para users
CREATE POLICY "Users can view farm members" ON users
    FOR SELECT USING (farm_id IN (
        SELECT farm_id FROM users WHERE id = auth.uid()
    ));

-- Política para chat_messages
CREATE POLICY "Users can view farm messages" ON chat_messages
    FOR SELECT USING (farm_id IN (
        SELECT farm_id FROM users WHERE id = auth.uid()
    ));

CREATE POLICY "Users can send messages" ON chat_messages
    FOR INSERT WITH CHECK (sender_id = auth.uid());
```

## 🔄 Sincronização Automática

### Como Funciona
1. **Usuário acessa o chat** → Sistema busca usuários do banco principal
2. **Sincronização automática** → Usuários são copiados para o banco do chat
3. **Mensagens** → Armazenadas apenas no banco do chat
4. **Real-time** → WebSocket conectado ao banco do chat

### Vantagens
- ✅ **Separação de responsabilidades**
- ✅ **Performance otimizada** para chat
- ✅ **Escalabilidade independente**
- ✅ **Backup separado** para mensagens
- ✅ **Sincronização automática** de usuários

## 📊 Monitoramento

### Logs de Sincronização
```javascript
// Verificar logs no console do navegador
console.log('✅ Usuário João sincronizado para o chat');
console.log('✅ 5 usuários sincronizados para o chat');
```

### Métricas Importantes
- **Tempo de sincronização** de usuários
- **Latência** de mensagens
- **Taxa de erro** na sincronização
- **Uso de storage** do banco do chat

## 🚨 Troubleshooting

### Problemas Comuns

#### 1. Erro de Sincronização
```
Erro ao sincronizar usuário para chat: {error}
```
**Solução**: Verificar se as tabelas foram criadas corretamente

#### 2. Mensagens Não Aparecem
**Solução**: Verificar RLS e permissões do banco do chat

#### 3. Usuários Não Carregam
**Solução**: Verificar conexão com banco principal

### Comandos de Diagnóstico
```sql
-- Verificar usuários sincronizados
SELECT COUNT(*) FROM users;

-- Verificar mensagens
SELECT COUNT(*) FROM chat_messages;

-- Verificar última sincronização
SELECT MAX(updated_at) FROM users;
```

## 🔐 Segurança

### Boas Práticas
1. **RLS habilitado** em todas as tabelas
2. **Políticas restritivas** por farm_id
3. **Logs de auditoria** para mensagens
4. **Backup regular** do banco do chat
5. **Monitoramento** de uso de storage

### Políticas de Retenção
- **Mensagens**: Manter por 1 ano
- **Usuários inativos**: Sincronizar a cada login
- **Logs**: Manter por 30 dias

## 📈 Próximos Passos

### Otimizações Futuras
1. **Cache Redis** para usuários frequentes
2. **Paginação** de mensagens antigas
3. **Compressão** de mensagens longas
4. **Arquivamento** automático
5. **Analytics** de uso do chat

### Recursos Avançados
1. **Mensagens em grupo**
2. **Anexos de arquivo**
3. **Notificações push**
4. **Status de leitura**
5. **Busca em mensagens**
