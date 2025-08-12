# Solução para o Erro: "Failed to load resource: the server responded with a status of 404"

## Problema Identificado

O erro está ocorrendo porque a função RPC `update_user_report_settings` no banco de dados não aceita todos os 4 parâmetros que o JavaScript está enviando:

**Parâmetros enviados pelo JavaScript:**
- `p_report_farm_name`
- `p_report_farm_logo_base64`
- `p_report_footer_text`
- `p_report_system_logo_base64`

**Parâmetros aceitos pela função (ANTES da correção):**
- `p_report_farm_name`
- `p_report_farm_logo_base64`

## Soluções Aplicadas

### 1. Correção da Função RPC no Banco de Dados

A função `update_user_report_settings` foi atualizada para aceitar todos os 4 parâmetros:

```sql
CREATE OR REPLACE FUNCTION update_user_report_settings(
    p_report_farm_name TEXT DEFAULT NULL,
    p_report_farm_logo_base64 TEXT DEFAULT NULL,
    p_report_footer_text TEXT DEFAULT NULL,
    p_report_system_logo_base64 TEXT DEFAULT NULL
)
RETURNS VOID AS $$
BEGIN
    UPDATE users SET 
        report_farm_name = COALESCE(p_report_farm_name, report_farm_name),
        report_farm_logo_base64 = COALESCE(p_report_farm_logo_base64, report_farm_logo_base64),
        report_footer_text = COALESCE(p_report_footer_text, report_footer_text),
        report_system_logo_base64 = COALESCE(p_report_system_logo_base64, report_system_logo_base64),
        updated_at = NOW()
    WHERE id = auth.uid();
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;
```

### 2. Melhoria na Função de Carregamento de Logo

A função `loadFarmLogo()` no arquivo `pdf-generator.js` foi melhorada para:

1. **Primeiro verificar** se o usuário atual tem logo configurada
2. **Como fallback**, buscar a logo do gerente da fazenda
3. **Melhor tratamento de erros** e logs mais informativos

### 3. Verificação das Colunas do Banco

Confirmado que as colunas necessárias existem na tabela `users`:
- `report_farm_name`
- `report_farm_logo_base64`
- `report_footer_text`
- `report_system_logo_base64`

## Passos para Resolver

### Passo 1: Executar o SQL de Correção

1. Acesse o **Supabase Dashboard**
2. Vá em **SQL Editor**
3. Cole e execute o conteúdo do arquivo `fix_update_user_report_settings.sql`

### Passo 2: Verificar a Aplicação

Após executar o SQL, teste:

1. **Salvar configurações de relatório** na aba de gerente
2. **Gerar um PDF** para verificar se as logos estão carregando
3. **Verificar os logs** no console do navegador

### Passo 3: Verificação dos Logs

Os logs agora devem mostrar:

```
Logo da fazenda carregada do usuário atual com sucesso
Tamanho da logo: [número]
```

Ou:

```
Logo da fazenda carregada do gerente com sucesso
Tamanho da logo: [número]
```

## Arquivos Modificados

1. **`database_complete.sql`** - Função RPC atualizada
2. **`fix_update_user_report_settings.sql`** - Script de correção
3. **`assets/js/pdf-generator.js`** - Função de carregamento de logo melhorada

## Verificação de Funcionamento

### Antes da Correção:
- ❌ Erro 404 na função RPC
- ❌ "Erro ao salvar configurações: Object"
- ❌ "Nenhuma logo da fazenda encontrada"

### Após a Correção:
- ✅ Função RPC aceita todos os parâmetros
- ✅ Configurações salvam com sucesso
- ✅ Logo da fazenda carrega corretamente
- ✅ PDFs gerados com logos apropriadas

## Notas Importantes

1. **Cache do Supabase**: Pode ser necessário aguardar alguns minutos para o cache ser atualizado
2. **Permissões**: A função usa `SECURITY DEFINER`, então executa com privilégios elevados
3. **Fallback**: Se o usuário não tiver logo, o sistema busca a logo do gerente da fazenda
4. **Logs**: Os logs no console agora são mais informativos para debug

## Troubleshooting

Se ainda houver problemas:

1. **Verificar se a função foi criada:**
   ```sql
   SELECT * FROM pg_proc WHERE proname = 'update_user_report_settings';
   ```

2. **Verificar se as colunas existem:**
   ```sql
   SELECT column_name FROM information_schema.columns 
   WHERE table_name = 'users' AND column_name LIKE 'report_%';
   ```

3. **Testar a função diretamente:**
   ```sql
   SELECT update_user_report_settings('Teste', NULL, NULL, NULL);
   ```
