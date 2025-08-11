# Solução para o Erro: "Could not find the function public.create_initial_user"

## Problema Identificado

O erro está ocorrendo porque a função `create_initial_user` não está sendo encontrada no cache do schema do Supabase. Isso pode acontecer por alguns motivos:

1. **Função não foi criada no banco de dados**
2. **Parâmetros incorretos na chamada da função**
3. **Cache do Supabase desatualizado**

## Soluções Aplicadas

### 1. Correção dos Parâmetros no JavaScript

Já corrigi os parâmetros no arquivo `PrimeiroAcesso.html`:

**Antes (INCORRETO):**
```javascript
const { data: userResult, error: userError } = await supabase.rpc('create_initial_user', {
    p_farm_id: farmResult,
    p_name: adminData.name,
    p_email: adminData.email,
    p_role: adminData.role,
    p_auth_user_id: authData.user.id  // ❌ Parâmetro incorreto
});
```

**Depois (CORRETO):**
```javascript
const { data: userResult, error: userError } = await supabase.rpc('create_initial_user', {
    p_user_id: authData.user.id,      // ✅ Parâmetro correto
    p_farm_id: farmResult,
    p_name: adminData.name,
    p_email: adminData.email,
    p_role: adminData.role,
    p_whatsapp: adminData.whatsapp || ''  // ✅ Parâmetro adicionado
});
```

### 2. Correção da Função complete_farm_setup

Também corrigi o parâmetro da função `complete_farm_setup`:

**Antes:**
```javascript
const { error: setupError } = await supabase.rpc('complete_farm_setup', {
    farm_id: farmResult  // ❌ Parâmetro incorreto
});
```

**Depois:**
```javascript
const { error: setupError } = await supabase.rpc('complete_farm_setup', {
    p_farm_id: farmResult  // ✅ Parâmetro correto
});
```

## Passos para Resolver

### Passo 1: Executar o SQL de Correção

1. Acesse o **Supabase Dashboard**
2. Vá em **SQL Editor**
3. Cole e execute o conteúdo do arquivo `fix_create_initial_user.sql`

### Passo 2: Verificar se as Funções Existem

Execute este SQL para verificar se todas as funções necessárias estão criadas:

```sql
SELECT 
    routine_name, 
    routine_type, 
    data_type
FROM information_schema.routines 
WHERE routine_schema = 'public' 
AND routine_name IN (
    'create_initial_user',
    'create_initial_farm', 
    'complete_farm_setup',
    'check_farm_exists',
    'check_user_exists'
)
ORDER BY routine_name;
```

### Passo 3: Limpar Cache (se necessário)

Se o problema persistir, pode ser necessário:

1. **Reiniciar o projeto no Supabase Dashboard**
2. **Aguardar alguns minutos** para o cache ser atualizado
3. **Testar novamente** a criação de conta

## Funções Necessárias

Certifique-se de que estas funções estão criadas no banco:

1. `create_initial_user(p_user_id, p_farm_id, p_name, p_email, p_role, p_whatsapp)`
2. `create_initial_farm(p_name, p_owner_name, p_cnpj, p_city, p_state, p_phone, p_email, p_address)`
3. `complete_farm_setup(p_farm_id)`
4. `check_farm_exists(p_name, p_cnpj)`
5. `check_user_exists(p_email)`

## Teste da Solução

Após aplicar as correções:

1. **Acesse** `PrimeiroAcesso.html`
2. **Preencha** os dados da fazenda e administrador
3. **Tente criar** a conta
4. **Verifique** se o erro foi resolvido

## Arquivos Modificados

- ✅ `PrimeiroAcesso.html` - Parâmetros corrigidos
- ✅ `fix_create_initial_user.sql` - SQL de correção criado
- ✅ `SOLUCAO_ERRO_CREATE_INITIAL_USER.md` - Este arquivo de instruções

## Se o Problema Persistir

Se ainda houver problemas após aplicar estas correções:

1. **Verifique os logs** do console do navegador
2. **Confirme** que todas as funções foram criadas no banco
3. **Teste** as funções individualmente no SQL Editor
4. **Verifique** se há erros de sintaxe no SQL
