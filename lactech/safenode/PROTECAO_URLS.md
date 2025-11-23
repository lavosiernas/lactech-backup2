# SafeNode - Proteção de URLs

## Como Funciona

O SafeNode implementa um sistema de proteção de URLs que oculta os caminhos reais dos arquivos quando o usuário está logado.

### URLs Protegidas

**Antes (sem proteção):**
```
https://safenode.cloud/dashboard.php
https://safenode.cloud/sites.php
https://safenode.cloud/logs.php
```

**Depois (com proteção):**
```
https://safenode.cloud/safenode-a1b2c3d4-123456789abc-1234567890
https://safenode.cloud/safenode-e5f6g7h8-987654321def-1234567891
https://safenode.cloud/safenode-i9j0k1l2-456789012ghi-1234567892
```

### Formato da URL Protegida

```
safenode-[hash8]-[id12]-[timestamp]
```

- **hash8**: Hash de 8 caracteres baseado no token de sessão + rota
- **id12**: ID único de 12 caracteres gerado para cada requisição
- **timestamp**: Timestamp Unix para validação de expiração

### Segurança

1. **Tokens de Sessão**: Cada URL é vinculada à sessão do usuário
2. **Expiração**: URLs expiram em 1 hora
3. **Validação**: Verifica se a sessão ainda é válida
4. **Mapeamento Seguro**: URLs são armazenadas apenas na sessão do servidor

### Quando é Ativado

- ✅ **Apenas quando logado**: URLs protegidas só funcionam para usuários autenticados
- ✅ **Arquivos públicos**: `login.php`, `register.php`, `verify-otp.php`, `index.php` continuam acessíveis
- ✅ **Assets**: Arquivos em `/assets/` continuam acessíveis normalmente

### Arquivos Protegidos

- `dashboard.php` → `safenode-xxxx-xxxx-xxxx`
- `sites.php` → `safenode-xxxx-xxxx-xxxx`
- `logs.php` → `safenode-xxxx-xxxx-xxxx`
- `blocked.php` → `safenode-xxxx-xxxx-xxxx`
- `settings.php` → `safenode-xxxx-xxxx-xxxx`

### Como Usar

O sistema funciona automaticamente. Quando você está logado:

1. **Sidebar**: Links são gerados automaticamente com URLs protegidas
2. **Navegação**: Todas as navegações internas usam URLs protegidas
3. **Compatibilidade**: Se não estiver logado, usa URLs normais

### Estrutura de Arquivos

```
safenode/
├── includes/
│   ├── Router.php      # Sistema de rotas e geração de URLs
│   └── init.php        # Inicialização automática
├── router.php          # Processador de rotas protegidas
└── .htaccess          # Regras de rewrite do Apache
```

### Configuração

Não é necessária configuração adicional. O sistema funciona automaticamente quando:
- Usuário está logado
- Arquivo `.htaccess` está configurado
- Módulo `mod_rewrite` do Apache está ativo

### Troubleshooting

**URLs não estão sendo protegidas:**
- Verifique se está logado
- Verifique se o `.htaccess` está funcionando
- Verifique se `mod_rewrite` está ativo no Apache

**Erro 404 nas URLs protegidas:**
- Limpe o cache do navegador
- Verifique se a sessão não expirou
- Faça login novamente

**Acesso direto aos arquivos ainda funciona:**
- Isso é normal durante desenvolvimento
- Em produção, o `.htaccess` bloqueia acesso direto quando logado




