# Integração Asaas - SafeNode

## 📋 Visão Geral

A integração com a Asaas permite processar pagamentos diretamente no SafeNode, incluindo:
- **PIX** (pagamento instantâneo)
- **Boleto Bancário**
- **Cartão de Crédito**
- **Cartão de Débito**

## 🚀 Configuração Inicial

### 1. Criar Tabelas no Banco de Dados

Execute o script SQL para criar as tabelas necessárias:

```sql
-- Execute o arquivo: database/CREATE_ASAAS_TABLES.sql
```

Ou execute manualmente:

```bash
mysql -u seu_usuario -p safend < database/CREATE_ASAAS_TABLES.sql
```

### 2. Obter API Key da Asaas

1. Acesse [https://www.asaas.com](https://www.asaas.com)
2. Crie uma conta ou faça login
3. Vá em **Configurações > Integrações > API**
4. Gere uma **API Key** (Token de acesso)
5. Copie o token gerado

### 3. Configurar no SafeNode

1. Acesse **Configurações > Asaas** no painel SafeNode
2. Cole sua **API Key** no campo "API Key da Asaas"
3. Se estiver testando, marque "Usar ambiente sandbox"
4. Clique em **Salvar Configurações**

## 📝 Como Usar

### Criar um Pagamento

1. Acesse a página **Pagamentos** no menu lateral
2. Preencha os dados:
   - **Valor**: Valor em R$ (ex: 100.00)
   - **Tipo**: PIX, Boleto ou Cartão de Crédito
   - **Data de Vencimento**: Data limite para pagamento
   - **Descrição**: Descrição opcional do pagamento
3. Clique em **Criar Pagamento**

### Visualizar QR Code PIX

Após criar um pagamento PIX:
1. Na lista de pagamentos, clique em **Ver QR Code**
2. O QR Code será exibido em um modal
3. Você pode copiar o código PIX ou escanear o QR Code

### Webhook (Notificações)

A Asaas enviará notificações automáticas quando:
- Um pagamento for confirmado
- Um pagamento for recebido
- Um pagamento estiver vencido
- Um pagamento for reembolsado

**Configurar Webhook na Asaas:**
1. Acesse **Configurações > Webhooks** na Asaas
2. Adicione a URL: `https://seudominio.com/safenode/api/asaas-webhook.php`
3. Selecione os eventos que deseja receber

## 🔧 Estrutura de Arquivos

```
lactech/safenode/
├── includes/
│   └── AsaasAPI.php          # Classe principal da integração
├── api/
│   ├── create-payment.php     # Endpoint para criar pagamentos
│   ├── asaas-webhook.php      # Webhook para receber notificações
│   └── get-pix-qrcode.php     # Endpoint para buscar QR Code PIX
├── payments.php                # Página de gerenciamento de pagamentos
├── database/
│   └── CREATE_ASAAS_TABLES.sql # Script SQL para criar tabelas
└── ASAAS_INTEGRATION.md        # Esta documentação
```

## 📊 Tabelas do Banco de Dados

### `safenode_payments`
Armazena todas as transações/pagamentos:
- `id`: ID interno
- `user_id`: ID do usuário que criou o pagamento
- `asaas_payment_id`: ID do pagamento na Asaas
- `asaas_customer_id`: ID do cliente na Asaas
- `amount`: Valor do pagamento
- `billing_type`: Tipo (PIX, BOLETO, CREDIT_CARD)
- `status`: Status (PENDING, RECEIVED, CONFIRMED, OVERDUE, etc)
- `due_date`: Data de vencimento
- `paid_date`: Data de pagamento (quando pago)
- `metadata`: JSON com dados adicionais

### `safenode_asaas_customers`
Vincula usuários do SafeNode com clientes na Asaas:
- `id`: ID interno
- `user_id`: ID do usuário no SafeNode
- `asaas_customer_id`: ID do cliente na Asaas
- `name`: Nome do cliente
- `email`: Email do cliente

## 🔐 Segurança

- Todas as requisições são autenticadas via sessão
- CSRF protection em todos os formulários
- Validação de dados em todos os endpoints
- Logs de erros para debug
- Webhook valida dados antes de processar

## 🐛 Troubleshooting

### Erro: "API Key da Asaas não configurada"
- Verifique se a API Key foi configurada em **Configurações > Asaas**
- Certifique-se de que a configuração foi salva

### Erro: "Erro ao criar cliente"
- Verifique se o email do usuário está válido
- Certifique-se de que a API Key está correta
- Verifique se está usando o ambiente correto (sandbox/produção)

### Webhook não está recebendo notificações
- Verifique se a URL do webhook está correta na Asaas
- Certifique-se de que o servidor está acessível publicamente
- Verifique os logs do servidor para erros

### QR Code PIX não aparece
- Verifique se o pagamento foi criado com sucesso
- Certifique-se de que o tipo de pagamento é PIX
- Verifique se o pagamento ainda está pendente

## 📚 API Reference

### AsaasAPI Class

#### Métodos Principais:

```php
// Criar pagamento
$result = $asaasAPI->createPayment([
    'customer' => 'cus_123456',
    'billingType' => 'PIX',
    'value' => 100.00,
    'dueDate' => '2024-12-31',
    'description' => 'Pagamento SafeNode'
]);

// Criar ou atualizar cliente
$result = $asaasAPI->createOrUpdateCustomer([
    'name' => 'João Silva',
    'email' => 'joao@example.com',
    'cpfCnpj' => '12345678900'
]);

// Buscar pagamento
$result = $asaasAPI->getPayment('pay_123456');

// Buscar QR Code PIX
$result = $asaasAPI->getPixQrCode('pay_123456');
```

## 📞 Suporte

Para mais informações sobre a API da Asaas:
- Documentação: [https://docs.asaas.com](https://docs.asaas.com)
- Suporte: [https://www.asaas.com/contato](https://www.asaas.com/contato)


