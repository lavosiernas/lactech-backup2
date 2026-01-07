# 🔄 ATUALIZAÇÃO DO BANCO DE DADOS

## PROBLEMA IDENTIFICADO

A tabela `safenode_human_verification_logs` tem o campo `event_type` como ENUM com apenas 3 valores:
- `'human_validated'`
- `'bot_blocked'`
- `'access_allowed'`

**Mas o código precisa de:**
- `'challenge_shown'` (para registrar quando desafio é mostrado)

---

## SOLUÇÃO

### Opção 1: ALTER TABLE (Recomendado - Mais Simples)

Execute o arquivo:
```
safenode/database/update-challenge-support.sql
```

Este script adiciona `'challenge_shown'` ao ENUM existente.

**Como executar:**
1. Abra phpMyAdmin ou seu cliente MySQL
2. Selecione o banco de dados `safend`
3. Vá em "SQL" ou "Importar"
4. Cole o conteúdo de `update-challenge-support.sql`
5. Execute

---

### Opção 2: Recriar Tabela (Se Opção 1 Falhar)

Se o ALTER TABLE não funcionar (algumas versões do MySQL/MariaDB têm problemas com ENUM), use:

```
safenode/database/update-challenge-support-alternative.sql
```

**⚠️ ATENÇÃO:** Este script:
1. Cria uma nova tabela
2. Copia todos os dados
3. Remove a tabela antiga
4. Renomeia a nova

**Faça backup antes de executar!**

---

## VERIFICAÇÃO

Após executar, verifique se funcionou:

```sql
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'safenode_human_verification_logs' 
AND COLUMN_NAME = 'event_type';
```

Deve retornar algo como:
```
ENUM('human_validated','bot_blocked','access_allowed','challenge_shown')
```

---

## PRÓXIMOS PASSOS

Após atualizar o banco:
1. ✅ Teste o desafio visual
2. ✅ Verifique se os logs estão sendo salvos
3. ✅ Confira o dashboard mostrando desafios

---

**Status**: ⚠️ NECESSÁRIO ANTES DE USAR DESAFIO  
**Prioridade**: ALTA

