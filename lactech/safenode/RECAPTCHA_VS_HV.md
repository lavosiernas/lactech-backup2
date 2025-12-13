# reCAPTCHA vs Verificação Humana - SafeNode

## 🔀 São Sistemas Separados

### Verificação Humana do SafeNode (HV)
- **Arquivo:** `includes/HumanVerification.php`
- **Endpoint:** `api/sdk/init.php` e `api/sdk/validate.php`
- **Página de Config:** `human-verification.php`
- **Sistema próprio** do SafeNode
- Baseado em tokens de sessão e JavaScript
- **Usado no login** do SafeNode atualmente

### reCAPTCHA (Google)
- **Arquivo:** `includes/ReCAPTCHA.php`
- **Endpoint:** `api/sdk/recaptcha-validate.php`
- **Página de Config:** `settings.php` (configurações gerais)
- **Sistema do Google**
- Validação via API do Google
- **Pode ser usado** no login OU em sites clientes

## 📊 Comparação

| Característica | Verificação Humana (HV) | reCAPTCHA |
|----------------|-------------------------|-----------|
| **Fornecedor** | SafeNode (próprio) | Google |
| **Marca visível** | SafeNode | Google |
| **Dependência externa** | ❌ Nenhuma | ✅ Google API |
| **Custo** | ✅ Gratuito | ✅ Gratuito (até 1M/mês) |
| **API Keys** | ✅ Sim (HVAPIKeyManager) | ❌ Não precisa (usa Site Key) |
| **Rate Limiting** | ✅ Próprio | ✅ Google gerencia |
| **Score/Análise** | ⚠️ Básico | ✅ Avançado (v3) |
| **Widget visível** | ⚠️ Não | ✅ Sim (v2) |
| **Invisível** | ✅ Sim | ✅ Sim (v3) |

## 🎯 Quando usar cada um?

### Use Verificação Humana (HV) quando:
- ✅ Quer sistema próprio, sem depender de terceiros
- ✅ Precisa de controle total
- ✅ Quer evitar marca "Google"
- ✅ Sites clientes já usam HV

### Use reCAPTCHA quando:
- ✅ Quer validação mais robusta (especialmente v3)
- ✅ Precisa de análise avançada de comportamento
- ✅ Não se importa com marca "Google"
- ✅ Sites clientes preferem reCAPTCHA conhecido

## 🔄 Podem ser usados juntos?

**Sim!** Você pode:
1. **Usar um OU outro** (escolher qual habilitar)
2. **Usar ambos** (camada dupla de segurança)
3. **Usar HV no SafeNode** e **reCAPTCHA em sites clientes**

## 💡 Recomendação

### Para o Login do SafeNode:
```php
// Opção 1: Só HV (atual)
SafeNodeHumanVerification::validateRequest($_POST);

// Opção 2: Só reCAPTCHA
ReCAPTCHA::validate($_POST['g-recaptcha-response']);

// Opção 3: Ambos (mais seguro)
$hvValid = SafeNodeHumanVerification::validateRequest($_POST);
$recaptchaValid = ReCAPTCHA::validate($_POST['g-recaptcha-response']);
if (!$hvValid || !$recaptchaValid) {
    // Bloquear
}
```

### Para Sites Clientes:
- **HV:** Já existe via `api/sdk/validate.php`
- **reCAPTCHA:** Novo via `api/sdk/recaptcha-validate.php`
- Cliente escolhe qual usar (ou ambos)

## 🚀 Configuração Independente

### Verificação Humana:
- Gerenciado em: `human-verification.php`
- API Keys específicas por cliente
- Rate limits configuráveis

### reCAPTCHA:
- Gerenciado em: `settings.php` > Segurança
- Configuração global (uma para todos)
- Site Key compartilhada (mas validação no SafeNode)

## 📝 Resumo

**São sistemas COMPLETAMENTE SEPARADOS:**
- ✅ Arquivos diferentes
- ✅ Endpoints diferentes
- ✅ Configurações diferentes
- ✅ Pode usar um, outro, ou ambos
- ✅ Nenhum depende do outro

**Escolha baseado nas necessidades:**
- HV = Próprio, sem marca externa
- reCAPTCHA = Mais robusto, marca Google

