# reCAPTCHA como Serviço Gerenciado - SafeNode

## 🎯 Conceito

O SafeNode oferece reCAPTCHA como **serviço gerenciado**. Isso significa:

### Para você (dono do SafeNode):
- ✅ Configura as chaves do Google **uma única vez**
- ✅ Gerencia tudo centralizadamente
- ✅ Clientes não precisam ir no Google

### Para seus clientes:
- ✅ **NÃO precisam** ir no Google pegar chaves
- ✅ Usam apenas a **API Key do SafeNode** (mesma da Verificação Humana)
- ✅ Integração simples: só incluir scripts

## 📊 Como Funciona

```
┌─────────────────────────────────────────────┐
│ VOCÊ (SafeNode)                            │
│ 1. Vai no Google reCAPTCHA Admin           │
│ 2. Pega Site Key + Secret Key              │
│ 3. Configura em recaptcha.php              │
└─────────────────┬───────────────────────────┘
                  │
                  │ (gerencia centralizadamente)
                  ▼
┌─────────────────────────────────────────────┐
│ CLIENTE (Site exemplo.com)                 │
│ 1. Tem API Key do SafeNode                 │
│ 2. Inclui scripts no site                  │
│ 3. Script busca Site Key do SafeNode       │
│ 4. Usuário resolve reCAPTCHA               │
│ 5. Valida via API do SafeNode              │
└─────────────────────────────────────────────┘
```

## 🔑 Diferença: Serviço Gerenciado vs Manual

### ❌ Modelo Manual (NÃO usamos):
```
Cliente → Vai no Google → Pega chaves → Configura no site
```
**Problema:** Se o cliente vai no Google pegar chaves, ele pode configurar direto no site dele. Não há valor agregado.

### ✅ Modelo Gerenciado (SafeNode):
```
Você → Configura chaves uma vez → Clientes usam via API Key
```
**Vantagem:** Cliente não precisa ir no Google, você gerencia tudo.

## 💡 Vantagens do Modelo Gerenciado

1. **Simplicidade para clientes**
   - Só precisa da API Key do SafeNode
   - Não precisa entender configuração do Google

2. **Gerenciamento centralizado**
   - Você controla tudo
   - Fácil de atualizar/configurar

3. **Valor agregado**
   - Cliente paga pela comodidade
   - Você oferece serviço completo

4. **Consistência**
   - Todos usam mesma configuração
   - Mais fácil de dar suporte

## 🚀 Como Cliente Usa

### Passo 1: Obter API Key do SafeNode
- Vai em "Verificação Humana" no SafeNode
- Gera uma API Key
- Copia a chave (formato: `sk_abc123...`)

### Passo 2: Incluir no Site

```html
<!-- Script do Google -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<!-- Script do SafeNode (Site Key obtida automaticamente) -->
<script src="https://safenode.com/api/sdk/recaptcha-script.js"
        data-safenode-recaptcha
        data-api-key="sk_abc123..."
        data-api-url="https://safenode.com/api/sdk"></script>

<!-- Widget (v2) - Site Key será inserida automaticamente -->
<div id="safenode-recaptcha-widget"></div>
```

### Passo 3: Validar no Backend

```php
$token = $_POST['g-recaptcha-response'] ?? '';
$apiKey = 'sk_abc123...'; // API Key do SafeNode

// Validar via SafeNode
$response = file_get_contents('https://safenode.com/api/sdk/recaptcha-validate.php', ...);
$result = json_decode($response, true);

if ($result['success']) {
    // Formulário válido!
}
```

## 📝 Resumo

| Aspecto | Serviço Gerenciado (SafeNode) | Manual |
|---------|-------------------------------|--------|
| **Cliente precisa ir no Google?** | ❌ Não | ✅ Sim |
| **Cliente precisa configurar chaves?** | ❌ Não | ✅ Sim |
| **Cliente usa** | API Key do SafeNode | Chaves do Google |
| **Você gerencia** | ✅ Tudo centralizado | ❌ Cada cliente gerencia |
| **Valor agregado** | ✅ Alto | ❌ Baixo |

## ✅ Conclusão

O modelo gerenciado faz sentido porque:
- Cliente não precisa se preocupar com configuração do Google
- Você oferece um serviço completo e fácil
- Cliente paga pela comodidade
- Você tem controle total

**Por isso o cliente só precisa da API Key do SafeNode, não das chaves do Google!**

