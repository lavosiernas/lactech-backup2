# Sistema de Solicitações de Senha - LacTech

## Visão Geral

O sistema de solicitações de senha permite que usuários solicitem alterações ou redefinições de senha de forma controlada, com aprovação obrigatória dos gerentes. Isso aumenta a segurança do sistema e dá aos administradores controle total sobre as mudanças de credenciais.

## Funcionalidades

### 🔐 Para Usuários
- **Solicitar alteração de senha** com motivo específico
- **Solicitar redefinição de senha** quando necessário
- **Acompanhar status** da solicitação
- **Receber notificações** sobre aprovação/rejeição

### 👨‍💼 Para Gerentes
- **Visualizar todas as solicitações** da fazenda
- **Analisar detalhes** de cada solicitação
- **Aprovar ou rejeitar** solicitações
- **Gerenciar histórico** de solicitações

## Como Funciona

### 1. Usuário Solicita Alteração
1. Acessa `solicitar-alteracao-senha.html`
2. Preenche formulário com:
   - Tipo de solicitação (alteração ou redefinição)
   - Motivo da solicitação
   - Observações adicionais
3. Envia solicitação (status: "pendente")

### 2. Gerente Analisa
1. Acessa aba "MAIS" → "Solicitações de Senha"
2. Visualiza lista de solicitações pendentes
3. Clica em "Ver Detalhes" para análise completa
4. Toma decisão: Aprovar ou Rejeitar

### 3. Usuário Recebe Resposta
- **Aprovado**: Pode alterar senha normalmente
- **Rejeitado**: Solicitação negada com justificativa

## Estrutura do Banco de Dados

### Tabela: `password_requests`

```sql
CREATE TABLE password_requests (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES auth.users(id),
    type VARCHAR(20) CHECK (type IN ('change', 'reset')),
    reason TEXT NOT NULL,
    notes TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    approved_at TIMESTAMP WITH TIME ZONE,
    approved_by UUID REFERENCES auth.users(id),
    rejected_at TIMESTAMP WITH TIME ZONE,
    rejected_by UUID REFERENCES auth.users(id),
    admin_notes TEXT
);
```

### Campos Principais
- **`type`**: `change` (alteração) ou `reset` (redefinição)
- **`reason`**: Motivo da solicitação
- **`status`**: `pending`, `approved`, ou `rejected`
- **`approved_by`**: ID do gerente que aprovou
- **`rejected_by`**: ID do gerente que rejeitou

## Arquivos do Sistema

### 📁 Arquivos Principais
- **`gerente.html`**: Modal de solicitações na aba MAIS
- **`solicitar-alteracao-senha.html`**: Página para usuários solicitarem
- **`password_requests_table.sql`**: Script de criação da tabela

### 🔧 Funções JavaScript
- `openPasswordRequests()`: Abre modal de solicitações
- `loadPasswordRequests()`: Carrega lista de solicitações
- `approvePasswordRequest()`: Aprova solicitação
- `rejectPasswordRequest()`: Rejeita solicitação
- `viewPasswordRequestDetails()`: Mostra detalhes da solicitação

## Fluxo de Segurança

### 🔒 Políticas RLS (Row Level Security)
- **Usuários**: Veem apenas suas próprias solicitações
- **Gerentes**: Veem todas as solicitações da fazenda
- **Apenas gerentes** podem aprovar/rejeitar solicitações

### 🛡️ Validações
- Tipo de solicitação obrigatório
- Motivo obrigatório
- Status controlado pelo sistema
- Timestamps automáticos de aprovação/rejeição

## Como Implementar

### 1. Criar Tabela no Banco
```bash
# Execute o script SQL
psql -d seu_banco -f password_requests_table.sql
```

### 2. Adicionar Link nas Páginas de Usuário
```html
<a href="solicitar-alteracao-senha.html" class="btn">
    Solicitar Alteração de Senha
</a>
```

### 3. Testar Funcionalidade
1. Faça login como usuário comum
2. Acesse página de solicitação
3. Envie uma solicitação
4. Faça login como gerente
5. Aprove/rejeite a solicitação

## Benefícios

### ✅ Segurança
- Controle total sobre alterações de senha
- Rastreamento de todas as solicitações
- Aprovação obrigatória de gerentes

### ✅ Auditoria
- Histórico completo de solicitações
- Timestamps de aprovação/rejeição
- Identificação de quem aprovou/rejeitou

### ✅ Usabilidade
- Interface intuitiva para usuários
- Painel de gerenciamento para gerentes
- Notificações automáticas

## Personalização

### 🎨 Cores e Estilos
- Modifique as classes CSS para adaptar ao tema
- Altere ícones SVG conforme necessário
- Ajuste tamanhos e espaçamentos

### 🔧 Funcionalidades
- Adicione campos personalizados na tabela
- Implemente notificações por email
- Crie relatórios de solicitações

## Suporte

Para dúvidas ou problemas:
1. Verifique o console do navegador
2. Confirme se a tabela foi criada corretamente
3. Verifique as políticas RLS
4. Teste com usuários de diferentes níveis

## Próximos Passos

### 🚀 Melhorias Futuras
- Notificações push em tempo real
- Integração com WhatsApp/Telegram
- Relatórios automáticos por email
- Dashboard de métricas de solicitações
- Sistema de prioridades para solicitações urgentes
