-- Script para corrigir RLS SEM mexer na tabela users
-- Esta versão não depende de verificações na tabela users

-- Remover todas as políticas existentes da tabela password_requests
DROP POLICY IF EXISTS "Users can view their own password requests" ON password_requests;
DROP POLICY IF EXISTS "Managers can view all password requests" ON password_requests;
DROP POLICY IF EXISTS "Users can insert their own password requests" ON password_requests;
DROP POLICY IF EXISTS "Managers can update password requests" ON password_requests;
DROP POLICY IF EXISTS "Allow password request creation" ON password_requests;
DROP POLICY IF EXISTS "Authenticated users can view all password requests" ON password_requests;
DROP POLICY IF EXISTS "Authenticated users can update password requests" ON password_requests;

-- Criar política para permitir inserção (qualquer pessoa pode criar solicitação)
CREATE POLICY "Allow password request creation" ON password_requests
    FOR INSERT 
    WITH CHECK (true);

-- Criar política para permitir visualização (qualquer pessoa pode ver)
CREATE POLICY "Allow password request viewing" ON password_requests
    FOR SELECT 
    USING (true);

-- Criar política para permitir atualização (qualquer pessoa pode atualizar)
CREATE POLICY "Allow password request updating" ON password_requests
    FOR UPDATE 
    USING (true);
