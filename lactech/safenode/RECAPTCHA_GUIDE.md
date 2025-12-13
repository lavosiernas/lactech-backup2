# Sistema reCAPTCHA - SafeNode

## 📋 O que foi criado

Uma classe completa de integração com Google reCAPTCHA que suporta:
- **reCAPTCHA v2** (checkbox e invisible)
- **reCAPTCHA v3** (score-based, invisível)

## 🚀 Como usar

### 1. Configurar as chaves

Primeiro, você precisa obter as chaves do Google:
1. Acesse: https://www.google.com/recaptcha/admin
2. Registre um novo site
3. Escolha v2 ou v3
4. Copie a **Site Key** e **Secret Key**

### 2. Adicionar no banco de dados

Execute o SQL em `database/add-recaptcha-settings.sql` para adicionar as configurações.

Depois, configure as chaves em **reCAPTCHA** (menu Sistema).

**IMPORTANTE:** Você configura as chaves do Google **uma vez**. Todos os seus clientes usarão essa mesma configuração via API Key do SafeNode.

### 3. Usar no Login

Exemplo de como integrar no `login.php`:

**No PHP (validação):**
```php
require_once __DIR__ . '/includes/ReCAPTCHA.php';

// Se reCAPTCHA estiver habilitado
if (SafeNodeSettings::get('recaptcha_enabled', '0') === '1') {
    ReCAPTCHA::init();
    
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $recaptchaResult = ReCAPTCHA::verify($recaptchaResponse);
    
    if (!$recaptchaResult['success']) {
        $error = 'Falha na verificação reCAPTCHA: ' . ($recaptchaResult['error'] ?? 'Erro desconhecido');
        // Bloquear login
    }
}
```

**No HTML (formulário):**
```html
<!-- Para reCAPTCHA v2 -->
<?php if (ReCAPTCHA::isConfigured() && ReCAPTCHA::getVersion() === 'v2'): ?>
    <?php echo ReCAPTCHA::renderScript('dark', 'normal'); ?>
    <div class="mb-4">
        <?php echo ReCAPTCHA::renderWidget('dark', 'normal'); ?>
    </div>
<?php endif; ?>

<!-- Para reCAPTCHA v3 (invisível) -->
<?php if (ReCAPTCHA::isConfigured() && ReCAPTCHA::getVersion() === 'v3'): ?>
    <?php echo ReCAPTCHA::renderScript(); ?>
    <?php echo ReCAPTCHA::renderV3Script('login'); ?>
    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
<?php endif; ?>
```

### 4. Usar em APIs

```php
require_once __DIR__ . '/includes/ReCAPTCHA.php';

ReCAPTCHA::init();
$response = $_POST['g-recaptcha-response'] ?? '';

if (!ReCAPTCHA::validate($response)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'reCAPTCHA inválido']);
    exit;
}
```

### 5. Usar em qualquer formulário

```php
// Validar
$isValid = ReCAPTCHA::validate($_POST['g-recaptcha-response'] ?? '');

// Ou obter detalhes completos
$result = ReCAPTCHA::verify($_POST['g-recaptcha-response'] ?? '');
if ($result['success']) {
    // Para v3, você pode verificar o score:
    if (isset($result['score'])) {
        echo "Score: " . $result['score'];
    }
}
```

## 🎯 Diferenças entre v2 e v3

### reCAPTCHA v2
- ✅ Widget visível (checkbox "Não sou um robô")
- ✅ Melhor UX conhecida
- ✅ Funciona bem para formulários

### reCAPTCHA v3
- ✅ Invisível (sem interação do usuário)
- ✅ Score de 0.0 a 1.0 (0 = bot, 1 = humano)
- ✅ Melhor para APIs e ações em background
- ⚠️ Requer mais configuração (threshold)

## 📝 Métodos principais

```php
// Inicializar
ReCAPTCHA::init($siteKey, $secretKey, 'v2'); // ou 'v3'

// Verificar se está configurado
ReCAPTCHA::isConfigured(); // bool

// Obter Site Key
ReCAPTCHA::getSiteKey(); // string

// Validar resposta (retorna bool)
ReCAPTCHA::validate($response); // bool

// Verificar com detalhes (retorna array)
ReCAPTCHA::verify($response, $remoteIp); 
// Retorna: ['success' => bool, 'score' => float|null, 'error' => string|null]

// Renderizar scripts/widgets
ReCAPTCHA::renderScript($theme, $size);
ReCAPTCHA::renderWidget($theme, $size);
ReCAPTCHA::renderV3Script($action, $callback);
```

## ⚙️ Configurações recomendadas

**Para reCAPTCHA v2:**
- Theme: `dark` (combina com SafeNode)
- Size: `normal` ou `compact`

**Para reCAPTCHA v3:**
- Score Threshold: `0.5` (padrão)
  - Mais alto (0.7-0.9) = mais restritivo
  - Mais baixo (0.3-0.5) = mais permissivo

## 🔒 Segurança

- ✅ Validação sempre no servidor
- ✅ IP do usuário enviado ao Google
- ✅ Timeout de 10s para requisições
- ✅ Tratamento de erros robusto

## 🌐 Integração em Sites Clientes (SERVIÇO GERENCIADO)

### Como sites clientes usam o reCAPTCHA do SafeNode

**IMPORTANTE:** O reCAPTCHA sempre mostra "Google" porque é um serviço do Google. Não é possível personalizar isso.

**VANTAGEM DO MODELO GERENCIADO:**
- ✅ Cliente **NÃO precisa** ir no Google pegar chaves
- ✅ Cliente **só precisa** da API Key do SafeNode (mesma da Verificação Humana)
- ✅ SafeNode gerencia tudo centralizadamente
- ✅ Você configura as chaves do Google **uma vez**, clientes usam via API

### Fluxo Simplificado:

1. **Você (SafeNode):** Configura chaves do Google uma vez em `recaptcha.php`
2. **Cliente:** Usa apenas a API Key do SafeNode (já tem de "Verificação Humana")
3. **Cliente:** Inclui scripts no site
4. **Script:** Busca Site Key automaticamente do SafeNode
5. **Cliente:** Valida via API do SafeNode

### Exemplo de integração no site do cliente:

```html
<!DOCTYPE html>
<html>
<head>
    <!-- 1. Script do Google reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <!-- 2. Script do SafeNode (Site Key é obtida automaticamente) -->
    <script src="https://safenode.example.com/api/sdk/recaptcha-script.js"
            data-safenode-recaptcha
            data-api-key="sk_abc123..." 
            data-api-url="https://safenode.example.com/api/sdk"></script>
</head>
<body>
    <form id="meu-formulario">
        <input type="email" name="email" required>
        <input type="password" name="password" required>
        
        <!-- Widget reCAPTCHA v2 (Site Key será inserida automaticamente pelo script) -->
        <div id="recaptcha-widget"></div>
        
        <button type="submit">Enviar</button>
    </form>
    
    <script>
        // Aguardar Site Key ser carregada do SafeNode
        function initReCAPTCHA() {
            // O script busca a Site Key automaticamente
            // Para v2, você precisa renderizar o widget depois que a Site Key for carregada
            if (SafeNodeReCAPTCHA.siteKeyLoaded && SafeNodeReCAPTCHA.config.recaptchaVersion === 'v2') {
                grecaptcha.ready(function() {
                    grecaptcha.render('recaptcha-widget', {
                        'sitekey': SafeNodeReCAPTCHA.config.recaptchaSiteKey
                    });
                });
            }
        }
        
        // Tentar inicializar quando carregar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initReCAPTCHA, 1000); // Aguardar Site Key carregar
            });
        } else {
            setTimeout(initReCAPTCHA, 1000);
        }
        
        // Validar antes de enviar
        document.getElementById('meu-formulario').addEventListener('submit', function(e) {
            e.preventDefault();
            
            SafeNodeReCAPTCHA.validate().then(function(result) {
                if (result.success) {
                    // Enviar formulário normalmente
                    this.submit();
                } else {
                    alert('Verificação falhou: ' + result.error);
                }
            }.bind(this));
        });
    </script>
</body>
</html>
```

### Backend do site cliente (PHP):

```php
<?php
// No backend do site cliente, validar via API do SafeNode
$recaptchaToken = $_POST['g-recaptcha-response'] ?? $_POST['safenode-recaptcha-token'] ?? '';
$apiKey = 'sk_abc123...'; // API Key do SafeNode (mesma da Verificação Humana)

$response = file_get_contents('https://safenode.example.com/api/sdk/recaptcha-validate.php', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'X-API-Key: ' . $apiKey
        ],
        'content' => json_encode([
            'recaptcha_token' => $recaptchaToken,
            'api_key' => $apiKey
        ])
    ]
]));

$result = json_decode($response, true);

if ($result['success']) {
    // reCAPTCHA válido, processar formulário
    echo "Formulário válido! Score: " . ($result['score'] ?? 'N/A');
} else {
    // reCAPTCHA inválido, bloquear
    die("Verificação falhou: " . $result['error']);
}
?>
```

**Nota:** O cliente usa a **mesma API Key** que já tem do SafeNode (de "Verificação Humana"). Não precisa de chaves do Google!

### Para reCAPTCHA v3 (invisível):

```html
<script>
// Inicializar
SafeNodeReCAPTCHA.init({
    apiKey: 'SUA_API_KEY',
    apiUrl: 'https://safenode.example.com/api/sdk',
    recaptchaVersion: 'v3',
    recaptchaSiteKey: 'SITE_KEY_V3',
    action: 'login'
});

// Validar antes de enviar
document.getElementById('form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    SafeNodeReCAPTCHA.validate().then(function(result) {
        if (result.success && result.score >= 0.5) {
            // Score alto, permitir
            this.submit();
        } else {
            alert('Acesso bloqueado por segurança');
        }
    }.bind(this));
});
</script>
```

## 💡 Próximos passos

1. Adicionar as configurações no banco (SQL acima)
2. Configurar as chaves em Settings
3. Integrar no login do SafeNode (exemplo acima)
4. Testar localmente
5. Opcional: adicionar em outros formulários críticos
6. **Sites clientes:** usar API Key e integrar conforme exemplos acima

