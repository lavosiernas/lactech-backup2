# Instruções para Configurar o Banco de Dados - Sistema de Pesquisas

## Arquivos SQL Criados

1. **`add-survey-thanked-at.sql`** - Script simples para adicionar a coluna `thanked_at` (RECOMENDADO)
2. **`survey-thanked-at-column.sql`** - Versão com verificações de segurança
3. **`survey-table-complete.sql`** - Script completo para criar a tabela do zero

## Como Executar

### Opção 1: phpMyAdmin (Recomendado)

1. Acesse o phpMyAdmin do seu servidor
2. Selecione o banco de dados: `u311882628_safend`
3. Clique na aba **SQL**
4. Copie e cole o conteúdo do arquivo **`add-survey-thanked-at.sql`**
5. Clique em **Executar**

### Opção 2: Linha de Comando MySQL

```bash
mysql -u u311882628_Kron -p u311882628_safend < add-survey-thanked-at.sql
```

Quando solicitado, digite a senha do banco de dados.

### Opção 3: Executar SQL Direto no MySQL

```sql
USE u311882628_safend;

ALTER TABLE `safenode_survey_responses` 
ADD COLUMN `thanked_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Data/hora em que o email de agradecimento foi enviado' AFTER `created_at`;

ALTER TABLE `safenode_survey_responses` 
ADD INDEX `idx_thanked_at` (`thanked_at`);
```

## Verificar se Funcionou

Execute esta query para verificar se a coluna foi adicionada:

```sql
DESCRIBE safenode_survey_responses;
```

Você deve ver a coluna `thanked_at` na lista.

## Estrutura Final da Tabela

A tabela `safenode_survey_responses` deve ter as seguintes colunas:

- `id` - INT AUTO_INCREMENT PRIMARY KEY
- `email` - VARCHAR(255) NOT NULL
- `uses_hosting` - VARCHAR(50) NOT NULL
- `hosting_type` - VARCHAR(255) NULL
- `biggest_pain` - TEXT NOT NULL
- `pays_for_email` - VARCHAR(50) NOT NULL
- `would_pay_integration` - VARCHAR(50) NOT NULL
- `wants_beta` - TINYINT(1) DEFAULT 0
- `additional_info` - TEXT NULL
- `created_at` - TIMESTAMP DEFAULT CURRENT_TIMESTAMP
- `thanked_at` - TIMESTAMP NULL (NOVA COLUNA)

## Configurações Aplicadas

- **Senha do Admin**: `lnassfnd017852`
- **Email Remetente**: `safenodemail@safenode.cloud`

Essas configurações já foram aplicadas no arquivo `survey-admin.php`.

## Notas Importantes

- Se a coluna `thanked_at` já existir, o script pode dar erro. Nesse caso, ignore o erro.
- Os dados existentes na tabela não serão afetados.
- A coluna `thanked_at` será NULL para todas as respostas antigas (o que é esperado).






