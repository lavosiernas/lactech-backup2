-- Script simples para corrigir RLS da tabela password_requests
-- Execute este script no SQL Editor do Supabase

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
            AND u.profile->>'user_type' = 'gerente'
        )
    );

-- Criar política para gerentes atualizarem solicitações
CREATE POLICY "Managers can update password requests" ON password_requests
    FOR UPDATE 
    USING (
        EXISTS (
            SELECT 1 FROM users u 
            WHERE u.id = auth.uid() 
            AND u.profile->>'user_type' = 'gerente'
        )
    );
