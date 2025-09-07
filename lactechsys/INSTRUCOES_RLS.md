# Instruções para Corrigir Erro de RLS

## Problema
Erro: "new row violates row-level security policy for table 'password_requests'"

Este erro indica que as políticas de Row Level Security (RLS) estão impedindo a inserção de novas solicitações de senha.

## Solução

### 1. Executar Script SQL no Supabase

1. Acesse o painel do Supabase
2. Vá para **SQL Editor**
3. Execute o script `fix_rls_simple.sql`:

```sql
-- Remover todas as políticas existentes
DROP POLICY IF EXISTS "Users can view their own password requests" ON password_requests;
DROP POLICY IF EXISTS "Managers can view all password requests" ON password_requests;
DROP POLICY IF EXISTS "Users can insert their own password requests" ON password_requests;
DROP POLICY IF EXISTS "Managers can update password requests" ON password_requests;
DROP POLICY IF EXISTS "Allow password request creation" ON password_requests;

-- Criar política para permitir inserção (qualquer pessoa pode criar solicitação)
CREATE POLICY "Allow password request creation" ON password_requests
    FOR INSERT 
    WITH CHECK (true);

-- Criar política para usuários visualizarem suas próprias solicitações
CREATE POLICY "Users can view their own password requests" ON password_requests
    FOR SELECT 
    USING (auth.uid() = user_id);

-- Criar política para gerentes visualizarem todas as solicitações
CREATE POLICY "Managers can view all password requests" ON password_requests
    FOR SELECT 
    USING (
        EXISTS (
            SELECT 1 FROM users u 
            WHERE u.id = auth.uid() 
            AND u.user_type = 'gerente'
        )
    );

-- Criar política para gerentes atualizarem solicitações
CREATE POLICY "Managers can update password requests" ON password_requests
    FOR UPDATE 
    USING (
        EXISTS (
            SELECT 1 FROM users u 
            WHERE u.id = auth.uid() 
            AND u.user_type = 'gerente'
        )
    );
```

### 2. Verificar se as Políticas Foram Criadas

Execute este comando para verificar:

```sql
SELECT policyname, cmd, permissive 
FROM pg_policies 
WHERE tablename = 'password_requests';
```

## O que as Políticas Fazem

1. **Allow password request creation**: Permite que qualquer pessoa (autenticada ou não) crie uma solicitação de senha
2. **Users can view their own password requests**: Usuários autenticados podem ver apenas suas próprias solicitações
3. **Managers can view all password requests**: Gerentes podem ver todas as solicitações
4. **Managers can update password requests**: Gerentes podem aprovar/rejeitar solicitações

## Arquivos Criados

- `fix_rls_simple.sql` - Script simples para executar
- `fix_rls_password_requests.sql` - Script completo com verificações
- `INSTRUCOES_RLS.md` - Este arquivo de instruções

## Status

✅ **Código JavaScript atualizado** - Detecta erros de RLS e mostra mensagem clara
⏳ **Aguardando execução do SQL** - Para corrigir as políticas RLS
