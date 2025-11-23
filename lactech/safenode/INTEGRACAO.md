# SafeNode - Guia de Integração

## Como Proteger Seu Site

### Método 1: Inclusão Direta (Recomendado)

Adicione o seguinte código no **início** do seu arquivo PHP principal (antes de qualquer output):

```php
<?php
// Proteção SafeNode - Deve ser a primeira linha
require_once '/caminho/completo/para/safenode/includes/SafeNodeMiddleware.php';
SafeNodeMiddleware::protect();

// Seu código continua aqui...
?>
```

### Método 2: Via .htaccess (Apache)

Adicione no `.htaccess` do seu site:

```apache
php_value auto_prepend_file "/caminho/completo/para/safenode/includes/SafeNodeMiddleware.php"
```

E no início do `SafeNodeMiddleware.php`, adicione:
```php
SafeNodeMiddleware::protect();
```

### Método 3: Via php.ini

No `php.ini`:
```ini
auto_prepend_file = "/caminho/completo/para/safenode/includes/SafeNodeMiddleware.php"
```

## Configuração

### 1. Adicionar Site no SafeNode

1. Acesse o dashboard do SafeNode
2. Vá em "Gerenciar Sites"
3. Adicione seu domínio (ex: `meusite.com`)
4. Configure o Zone ID do Cloudflare (opcional)

### 2. Configurar Cloudflare (Opcional)

Para integração completa com Cloudflare:

1. Obtenha seu API Token no Cloudflare
2. Adicione no `.env` ou configure diretamente:
```env
CLOUDFLARE_API_TOKEN=seu_token_aqui
```

### 3. Verificar Proteção

Após integrar, todas as requisições serão:
- ✅ Analisadas em tempo real
- ✅ Bloqueadas se detectarem ameaças
- ✅ Registradas nos logs
- ✅ Monitoradas no dashboard

## Funcionalidades Ativas

- ✅ Detecção de SQL Injection
- ✅ Detecção de XSS
- ✅ Detecção de Path Traversal
- ✅ Detecção de Command Injection
- ✅ Detecção de Brute Force
- ✅ Detecção de DDoS
- ✅ Rate Limiting
- ✅ Bloqueio Automático de IPs
- ✅ Whitelist/Blacklist
- ✅ Integração Cloudflare (se configurado)
- ✅ Logs em Tempo Real
- ✅ Métricas e Estatísticas

## Troubleshooting

### Site não está sendo protegido

1. Verifique se o site está ativo no dashboard
2. Verifique se o domínio está correto (sem www)
3. Verifique logs de erro do PHP
4. Verifique permissões do arquivo

### IPs legítimos sendo bloqueados

1. Adicione o IP na whitelist
2. Ajuste os padrões de detecção
3. Reduza a sensibilidade nas configurações

### Performance

O SafeNode adiciona ~1-5ms de latência por requisição, dependendo da análise necessária.




