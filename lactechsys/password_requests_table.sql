-- Tabela para gerenciar solicitações de alteração/redefinição de senha
-- Esta tabela permite que usuários solicitem alterações de senha e gerentes autorizem

CREATE TABLE IF NOT EXISTS password_requests (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    
    -- Usuário que fez a solicitação
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    
    -- Tipo de solicitação
    type VARCHAR(20) NOT NULL CHECK (type IN ('change', 'reset')),
    
    -- Motivo da solicitação
    reason TEXT NOT NULL,
    
    -- Observações adicionais
    notes TEXT,
    
    -- Status da solicitação
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
    
    -- Timestamps
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    -- Campos de aprovação/rejeição
    approved_at TIMESTAMP WITH TIME ZONE,
    approved_by UUID REFERENCES auth.users(id),
    rejected_at TIMESTAMP WITH TIME ZONE,
    rejected_by UUID REFERENCES auth.users(id),
    
    -- Comentário de aprovação/rejeição
    admin_notes TEXT
);

-- Índices para melhor performance
CREATE INDEX IF NOT EXISTS idx_password_requests_user_id ON password_requests(user_id);
CREATE INDEX IF NOT EXISTS idx_password_requests_status ON password_requests(status);
CREATE INDEX IF NOT EXISTS idx_password_requests_created_at ON password_requests(created_at);
CREATE INDEX IF NOT EXISTS idx_password_requests_farm_id ON password_requests(user_id) INCLUDE (user_id);

-- Função para atualizar o timestamp de atualização
CREATE OR REPLACE FUNCTION update_password_requests_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger para atualizar o timestamp automaticamente
CREATE TRIGGER trigger_update_password_requests_updated_at
    BEFORE UPDATE ON password_requests
    FOR EACH ROW
    EXECUTE FUNCTION update_password_requests_updated_at();

-- Política RLS para permitir que usuários vejam apenas suas próprias solicitações
ALTER TABLE password_requests ENABLE ROW LEVEL SECURITY;

-- Política para usuários verem suas próprias solicitações
CREATE POLICY "Users can view their own password requests" ON password_requests
    FOR SELECT USING (auth.uid() = user_id);

-- Política para usuários criarem suas próprias solicitações
CREATE POLICY "Users can create their own password requests" ON password_requests
    FOR INSERT WITH CHECK (auth.uid() = user_id);

-- Política para usuários atualizarem suas próprias solicitações (apenas se pendente)
CREATE POLICY "Users can update their own pending requests" ON password_requests
    FOR UPDATE USING (auth.uid() = user_id AND status = 'pending');

-- Política para gerentes verem todas as solicitações da fazenda
CREATE POLICY "Managers can view all password requests in their farm" ON password_requests
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users u1
            JOIN users u2 ON u1.farm_id = u2.farm_id
            WHERE u1.id = auth.uid() 
            AND u1.role = 'gerente'
            AND u2.id = password_requests.user_id
        )
    );

-- Política para gerentes aprovarem/rejeitarem solicitações
CREATE POLICY "Managers can update password requests" ON password_requests
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM users u1
            JOIN users u2 ON u1.farm_id = u2.farm_id
            WHERE u1.id = auth.uid() 
            AND u1.role = 'gerente'
            AND u2.id = password_requests.user_id
        )
    );

-- Comentários na tabela
COMMENT ON TABLE password_requests IS 'Tabela para gerenciar solicitações de alteração/redefinição de senha dos usuários';
COMMENT ON COLUMN password_requests.type IS 'Tipo de solicitação: change (alteração) ou reset (redefinição)';
COMMENT ON COLUMN password_requests.reason IS 'Motivo da solicitação de alteração de senha';
COMMENT ON COLUMN password_requests.status IS 'Status da solicitação: pending (pendente), approved (aprovada), rejected (rejeitada)';
COMMENT ON COLUMN password_requests.approved_by IS 'ID do gerente que aprovou a solicitação';
COMMENT ON COLUMN password_requests.rejected_by IS 'ID do gerente que rejeitou a solicitação';
COMMENT ON COLUMN password_requests.admin_notes IS 'Observações do gerente sobre a aprovação/rejeição';
