# Como Funciona a Proteção de URLs - SafeNode

## Comportamento Atual (URLs Diretas)

**Agora:**
```
http://safenode.cloud/dashboard.php
http://safenode.cloud/sites.php
http://safenode.cloud/logs.php
```

✅ Funciona, mas expõe a estrutura de arquivos

---

## Comportamento com Proteção Ativada

**Quando ativado:**
```
http://safenode.cloud/safenode-a1b2c3d4-123456789abc-1763605262
http://safenode.cloud/safenode-e5f6g7h8-987654321def-1763605263
http://safenode.cloud/safenode-i9j0k1l2-456789012ghi-1763605264
```

✅ Não expõe estrutura de arquivos
✅ URLs únicas por sessão
✅ Expiração automática (1 hora)

---

## Como Funciona

### 1. Quando Você Faz Login

- Sistema gera um token único para sua sessão
- Todas as URLs são convertidas para formato protegido
- Mapeamento é salvo na sessão do servidor

### 2. Quando Você Clica em um Link

- Link aponta para: `safenode-xxxx-xxxx-xxxx`
- `.htaccess` intercepta e redireciona para `router.php`
- `router.php` verifica na sessão qual arquivo corresponde
- Arquivo correto é carregado

### 3. Segurança

- URLs são únicas por sessão
- Não podem ser reutilizadas por outros usuários
- Expiração automática após 1 hora
- Validação de sessão em cada requisição

---

## Vantagens

✅ **Ocultação de Estrutura**: Não mostra nomes de arquivos
✅ **Proteção contra Enumeração**: Dificulta descobrir arquivos
✅ **Sessão Segura**: URLs vinculadas à sua sessão
✅ **Expiração Automática**: URLs não funcionam após 1 hora

---

## Desvantagens

⚠️ **Complexidade**: Mais código para manter
⚠️ **Debug**: Mais difícil debugar problemas
⚠️ **Compatibilidade**: Depende de `.htaccess` e `mod_rewrite`

---

## Quando Usar

✅ **Produção**: Recomendado para segurança extra
⚠️ **Desenvolvimento**: Pode complicar o debug
❌ **Localhost**: Geralmente não necessário

---

## Status Atual

🟡 **Desabilitado Temporariamente**: Funcionando com URLs diretas
🔧 **Problema Identificado**: Sessão não mantém mapeamento entre requisições
📝 **Solução**: Ajustar persistência de sessão ou usar banco de dados para mapeamento




