-- Corrigir políticas RLS para tabela password_requests
-- Permitir que usuários não autenticados possam criar solicitações de senha

-- 1. Verificar se RLS está habilitado
SELECT schemaname, tablename, rowsecurity 
FROM pg_tables 
WHERE tablename = 'password_requests';

-- 2. Remover políticas existentes (se houver)
DROP POLICY IF EXISTS "Users can view their own password requests" ON password_requests;
DROP POLICY IF EXISTS "Managers can view all password requests" ON password_requests;
DROP POLICY IF EXISTS "Users can insert their own password requests" ON password_requests;
DROP POLICY IF EXISTS "Managers can update password requests" ON password_requests;

-- 3. Criar novas políticas RLS

-- Política para permitir inserção de solicitações (usuários não autenticados podem inserir)
CREATE POLICY "Allow password request creation" ON password_requests
    FOR INSERT 
    WITH CHECK (true);

-- Política para usuários autenticados visualizarem suas próprias solicitações
CREATE POLICY "Users can view their own password requests" ON password_requests
    FOR SELECT 
    USING (auth.uid() = user_id);

-- Política para gerentes visualizarem todas as solicitações da fazenda
CREATE POLICY "Managers can view all password requests" ON password_requests
    FOR SELECT 
    USING (
        EXISTS (
            SELECT 1 FROM users u 
            WHERE u.id = auth.uid() 
            AND u.user_type = 'gerente'
            AND u.farm_id = (
                SELECT farm_id FROM users 
                WHERE id = password_requests.user_id
            )
        )
    );

-- Política para gerentes atualizarem solicitações (aprovar/rejeitar)
CREATE POLICY "Managers can update password requests" ON password_requests
    FOR UPDATE 
    USING (
        EXISTS (
            SELECT 1 FROM users u 
            WHERE u.id = auth.uid() 
            AND u.user_type = 'gerente'
            AND u.farm_id = (
                SELECT farm_id FROM users 
                WHERE id = password_requests.user_id
            )
        )
    );

-- 4. Verificar as políticas criadas
SELECT schemaname, tablename, policyname, permissive, roles, cmd, qual, with_check
FROM pg_policies 
WHERE tablename = 'password_requests';
