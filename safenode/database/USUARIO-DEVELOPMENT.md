# 👤 USUÁRIO DE DESENVOLVIMENTO — SAFENODE V1

## CREDENCIAIS PADRÃO

### Usuário de Desenvolvimento:
- **Username:** `dev`
- **Email:** `dev@safenode.local`
- **Senha:** `dev123456`
- **Role:** `user`
- **Status:** Ativo, email verificado

### Subscription:
- **Tipo:** Free Trial
- **Limite:** 10.000 eventos/mês
- **Usado:** 0
- **Status:** Ativo
- **Expira em:** 14 dias

---

## COMO USAR

### Ao importar o banco:
1. Execute o arquivo `safend (11).sql`
2. O usuário será criado automaticamente
3. Faça login com as credenciais acima

### Se o usuário já existe:
- O `ON DUPLICATE KEY UPDATE` garante que não dará erro
- Pode atualizar se necessário

---

## SEGURANÇA

⚠️ **IMPORTANTE:**
- Este usuário é apenas para desenvolvimento
- **NÃO use em produção**
- **NÃO deixe essas credenciais em produção**
- Remova antes de fazer deploy

---

## ALTERAR SENHA

Se precisar alterar a senha:

```sql
-- Gerar novo hash (substitua 'nova_senha' pela senha desejada)
-- Use password_hash('nova_senha', PASSWORD_DEFAULT) no PHP

UPDATE safenode_users 
SET password_hash = '$2y$10$NOVO_HASH_AQUI' 
WHERE username = 'dev';
```

---

**Status**: ✅ Adicionado ao banco  
**Última atualização:** 2024

