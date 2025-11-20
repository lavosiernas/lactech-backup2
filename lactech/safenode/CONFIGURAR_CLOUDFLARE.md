# Como Configurar o Cloudflare API Token

## Passo 1: Criar o Token no Cloudflare

1. Acesse: https://dash.cloudflare.com/profile/api-tokens
2. Clique em **"Create Token"**
3. Preencha o formulário:

### Nome do Token:
```
SafeNode Security
```

### Permissões:
- **Primeiro dropdown:** `Zona` (Zone)
- **Segundo dropdown:** `Serviços de firewall` (Firewall Services)
- **Terceiro dropdown:** `Editar` (Edit)

### Recursos de Zona:
- **Primeiro dropdown:** `Incluir` (Include)
- **Segundo dropdown:** `Zona específica` (Specific zone)
- **Terceiro dropdown:** Selecione seu domínio (ex: `safenode.cloud`)

### Filtragem de IP (Opcional):
- Deixe em branco (permite usar de qualquer IP)

### TTL (Opcional):
- Deixe em branco (token não expira)

4. Clique em **"Continue to summary"**
5. Clique em **"Create Token"**
6. **COPIE O TOKEN** (ele só aparece uma vez!)

## Passo 2: Configurar no SafeNode

### Opção 1: Variável de Ambiente (Recomendado)

Crie um arquivo `.env` na raiz do SafeNode com:

```env
CLOUDFLARE_API_TOKEN=seu_token_copiado_aqui
```

### Opção 2: Direto no Código (Não Recomendado)

Edite `lactech/safenode/includes/config.php` e adicione:

```php
define('CLOUDFLARE_API_TOKEN', 'seu_token_copiado_aqui');
```

### Opção 3: Via Hostinger (Produção)

No painel da Hostinger, configure como variável de ambiente do servidor.

## Passo 3: Configurar Zone ID

1. Acesse o dashboard do SafeNode
2. Vá em **"Gerenciar Sites"**
3. Edite ou adicione seu site
4. No campo **"Cloudflare Zone ID"**, cole o Zone ID do seu domínio

**Como encontrar o Zone ID:**
- Acesse: https://dash.cloudflare.com
- Selecione seu domínio
- Na página Overview, o Zone ID aparece no canto direito

## Verificar se Está Funcionando

Após configurar, quando o SafeNode detectar uma ameaça:
- ✅ IP será bloqueado no banco de dados
- ✅ IP será bloqueado automaticamente no Cloudflare
- ✅ Você verá a regra criada em: Cloudflare → Security → WAF → Firewall Rules

## Troubleshooting

**Token não funciona:**
- Verifique se as permissões estão corretas (Firewall Services → Edit)
- Verifique se o Zone ID está correto
- Verifique os logs de erro do PHP

**IP não está sendo bloqueado no Cloudflare:**
- Verifique se o site tem Zone ID configurado
- Verifique se o token tem permissão para a zona específica
- Verifique se o site está ativo no SafeNode

