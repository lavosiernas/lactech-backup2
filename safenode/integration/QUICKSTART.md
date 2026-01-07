# 🚀 SafeNode Mail - Rodando em 10 minutos

## O que é SafeNode Mail?

SafeNode Mail é a camada de comunicação e automação que se conecta direto à sua hospedagem e aplicação. Sem complicação. Sem configurar SMTP manualmente. Sem dor de cabeça com DNS.

## ✅ O que você vai conseguir

- ✅ E-mails funcionando em qualquer VPS
- ✅ API REST simples e previsível
- ✅ Templates versionados
- ✅ Analytics de entrega
- ✅ Webhooks para eventos
- ✅ Zero configuração de SMTP/DNS

---

## 📦 Instalação Rápida (Linux/Mac)

### Passo 1: Baixar e executar o script

```bash
curl -o setup-safenode.sh https://safenode.cloud/integration/setup-safenode.sh
sudo bash setup-safenode.sh
```

### Passo 2: Configurar variáveis

```bash
cd /opt/safenode-mail
cp .env.example .env
nano .env  # ou vim/vi
```

**Configuração mínima necessária:**

```env
SAFENODE_API_TOKEN=seu_token_aqui
DB_PASS=senha_forte_aqui
```

### Passo 3: Obter seu token da API

1. Acesse: https://safenode.cloud/mail
2. Faça login (ou crie conta grátis)
3. Crie um projeto de e-mail
4. Copie o token gerado
5. Cole no arquivo `.env`

### Passo 4: Iniciar os serviços

```bash
docker-compose up -d
```

### Passo 5: Verificar se está rodando

```bash
docker-compose ps
```

Você deve ver 3 containers rodando:
- `safenode-mail-app`
- `safenode-mail-nginx`
- `safenode-mail-mysql`

---

## 🔌 Integração com sua aplicação

### Node.js / Express

```bash
cd app/nodejs
npm install
npm start
```

**Exemplo de uso:**

```javascript
const axios = require('axios');

async function sendEmail(to, subject, html) {
  const response = await axios.post(
    'https://safenode.cloud/api/mail/send',
    { to, subject, html },
    {
      headers: {
        'Authorization': 'Bearer SEU_TOKEN_AQUI',
        'Content-Type': 'application/json'
      }
    }
  );
  
  return response.data;
}

// Usar
sendEmail(
  'usuario@exemplo.com',
  'Bem-vindo!',
  '<h1>Olá!</h1><p>Seu cadastro foi confirmado.</p>'
);
```

### PHP

```bash
cd app/php
# Já está pronto para usar!
```

Acesse: `http://seu-servidor/app/php/`

**Exemplo de uso:**

```php
<?php
$token = 'SEU_TOKEN_AQUI';
$apiUrl = 'https://safenode.cloud/api/mail/send';

$data = [
    'to' => 'usuario@exemplo.com',
    'subject' => 'Bem-vindo!',
    'html' => '<h1>Olá!</h1><p>Seu cadastro foi confirmado.</p>'
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$result = json_decode($response, true);

if ($result['success']) {
    echo 'E-mail enviado!';
} else {
    echo 'Erro: ' . $result['error'];
}
?>
```

---

## 📧 Enviar e-mail com template

Templates são criados no Relay visual (https://safenode.cloud/safefig) e podem ser reutilizados:

```javascript
// Node.js
await axios.post('https://safenode.cloud/api/mail/send', {
  to: 'usuario@exemplo.com',
  template: 'confirmar-cadastro',
  variables: {
    nome: 'João',
    codigo: '123456'
  }
}, {
  headers: {
    'Authorization': 'Bearer SEU_TOKEN'
  }
});
```

```php
// PHP
$data = [
    'to' => 'usuario@exemplo.com',
    'template' => 'confirmar-cadastro',
    'variables' => [
        'nome' => 'João',
        'codigo' => '123456'
    ]
];
// ... (resto do código curl igual acima)
```

---

## 🎯 Casos de uso comuns

### 1. Confirmação de cadastro

```javascript
await sendEmail(
  user.email,
  'Confirme seu cadastro',
  `<h1>Olá, ${user.name}!</h1>
   <p>Clique no link para confirmar: <a href="${confirmLink}">Confirmar</a></p>`
);
```

### 2. Reset de senha

```javascript
await axios.post('https://safenode.cloud/api/mail/send', {
  to: user.email,
  template: 'reset-password',
  variables: {
    nome: user.name,
    link: resetLink,
    expira_em: '1 hora'
  }
}, {
  headers: { 'Authorization': 'Bearer ' + token }
});
```

### 3. Notificações transacionais

```javascript
await sendEmail(
  order.customer_email,
  `Pedido #${order.id} confirmado`,
  gerarHTMLPedido(order)
);
```

---

## 🔍 Verificar logs e status

### Logs do Docker

```bash
# Ver todos os logs
docker-compose logs -f

# Ver logs de um serviço específico
docker-compose logs -f app
docker-compose logs -f nginx
```

### Status dos e-mails

Acesse o dashboard: https://safenode.cloud/mail

Lá você vê:
- ✅ E-mails enviados
- ❌ E-mails com erro
- 📊 Analytics de entrega
- 📈 Gráficos e métricas

---

## 🐛 Resolução de problemas

### Erro: "Token inválido"

✅ Verifique se o token está correto no `.env`  
✅ Confirme que o token está ativo no dashboard  
✅ Certifique-se de usar `Bearer ` antes do token

### Erro: "Cannot connect to database"

✅ Verifique se o MySQL está rodando: `docker-compose ps`  
✅ Confirme as credenciais no `.env`  
✅ Tente reiniciar: `docker-compose restart mysql`

### E-mails não estão sendo enviados

✅ Verifique os logs: `docker-compose logs app`  
✅ Confirme que o token tem permissão para enviar  
✅ Teste com um e-mail válido

### Porta 80 já está em uso

Edite o `docker-compose.yml` e mude:

```yaml
ports:
  - "8080:80"  # Use 8080 ao invés de 80
```

---

## 📚 Próximos passos

1. **Criar templates no Relay**: https://safenode.cloud/safefig
2. **Ler documentação completa**: https://safenode.cloud/docs/integration
3. **Explorar SDKs**: https://safenode.cloud/sdk
4. **Configurar webhooks**: Dashboard → Projeto → Webhooks

---

## 🆘 Precisa de ajuda?

- 📖 **Documentação**: https://safenode.cloud/docs
- 💬 **Comunidade**: [Link do grupo]
- 📧 **Suporte**: suporte@safenode.cloud

---

## 🎉 Pronto!

Sua integração está funcionando. Agora você pode:

✅ Enviar e-mails de qualquer lugar  
✅ Usar templates reutilizáveis  
✅ Monitorar entregas em tempo real  
✅ Escalar sem se preocupar com infraestrutura

**SafeNode é a camada entre seu código e a infraestrutura.** 🚀
















