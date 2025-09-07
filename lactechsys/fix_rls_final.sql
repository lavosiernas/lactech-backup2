-- Script final para corrigir RLS da tabela password_requests
-- Versão simplificada que funciona independente da estrutura da tabela users

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

-- Criar política para permitir que usuários autenticados vejam todas as solicitações
-- (isso permitirá que gerentes vejam as solicitações)
CREATE POLICY "Authenticated users can view all password requests" ON password_requests
    FOR SELECT 
    USING (auth.uid() IS NOT NULL);

-- Criar política para permitir que usuários autenticados atualizem solicitações
CREATE POLICY "Authenticated users can update password requests" ON password_requests
    FOR UPDATE 
    USING (auth.uid() IS NOT NULL);
