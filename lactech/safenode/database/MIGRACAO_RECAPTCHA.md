# Migração: Google reCAPTCHA → SafeNode reCAPTCHA

## ⚠️ IMPORTANTE: Ordem de Execução

Execute os scripts SQL nesta ordem:

### 1. Remover configurações antigas do Google
```sql
-- Execute primeiro:
SOURCE remove-google-recaptcha-settings.sql;
-- Ou execute o conteúdo do arquivo diretamente
```

### 2. Adicionar novo sistema do SafeNode
```sql
-- Execute depois:
SOURCE add-safenode-recaptcha.sql;
-- Ou execute o conteúdo do arquivo diretamente
```

## 📋 O que será removido

- `recaptcha_site_key` (Google)
- `recaptcha_secret_key` (Google)
- `recaptcha_version` (Google)
- `recaptcha_action` (Google)
- `recaptcha_score_threshold` (Google)
- `recaptcha_enabled` (Google)

## ✅ O que será adicionado

- `safenode_recaptcha_enabled` (Sistema próprio)
- `safenode_recaptcha_version` (Sistema próprio)
- `safenode_recaptcha_action` (Sistema próprio)
- `safenode_recaptcha_score_threshold` (Sistema próprio)
- Tabela `safenode_recaptcha_challenges`

## 🔄 Diferenças

| Antigo (Google) | Novo (SafeNode) |
|----------------|-----------------|
| `recaptcha_site_key` | ❌ Removido (não precisa mais) |
| `recaptcha_secret_key` | ❌ Removido (não precisa mais) |
| `recaptcha_version` | `safenode_recaptcha_version` |
| `recaptcha_action` | `safenode_recaptcha_action` |
| `recaptcha_score_threshold` | `safenode_recaptcha_score_threshold` |
| `recaptcha_enabled` | `safenode_recaptcha_enabled` |

## ⚡ Vantagens do Novo Sistema

1. **100% SafeNode** - Sem dependência do Google
2. **Sem chaves externas** - Não precisa mais de Site Key/Secret Key
3. **Análise comportamental** - Usa ML e análise própria
4. **Mesma API Key** - Clientes usam a mesma API Key da Verificação Humana

## 🚨 Aviso

Após a migração, o sistema antigo do Google reCAPTCHA não funcionará mais. Certifique-se de que:

1. Todos os sites clientes foram atualizados para usar o novo script
2. A nova página `recaptcha.php` está configurada
3. O sistema está testado antes de aplicar em produção

