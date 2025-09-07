# Instruções para Adicionar Coluna new_password

## Problema
O erro "Could not find the 'new_password' column" indica que a coluna `new_password` não existe na tabela `password_requests`.

## Solução

### 1. Executar Script SQL no Supabase

1. Acesse o painel do Supabase
2. Vá para **SQL Editor**
3. Execute o seguinte comando:

```sql
ALTER TABLE password_requests 
ADD COLUMN new_password TEXT;
```

### 2. Verificar se a Coluna foi Adicionada

Execute este comando para verificar:

```sql
SELECT column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'password_requests' 
AND column_name = 'new_password';
```

### 3. Solução Temporária (Já Implementada)

Enquanto a coluna não é adicionada, o sistema está usando o campo `notes` para armazenar a nova senha com o formato:

```
NOVA SENHA: [senha_fornecida]

[observações_do_usuário]
```

## Arquivos Criados

- `execute_sql.sql` - Script SQL para executar no Supabase
- `add_new_password_column.sql` - Script SQL completo com comentários

## Status

✅ **Código JavaScript atualizado** - Funciona com ou sem a coluna `new_password`
⏳ **Aguardando execução do SQL** - Para melhorar a organização dos dados
