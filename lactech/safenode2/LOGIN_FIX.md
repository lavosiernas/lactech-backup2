# Correção do Login - Guia Completo

## Problema Resolvido

O login estava dando erro 404 porque:
1. ❌ O endpoint não estava encontrando o `login.php`
2. ❌ O CSRF token não estava sendo enviado
3. ❌ O caminho do projeto não estava correto

## Soluções Implementadas

### 1. Endpoint de Login (`/app/api/login/route.ts`)
- ✅ Tenta múltiplos caminhos automaticamente
- ✅ Obtém CSRF token fazendo GET primeiro
- ✅ Envia POST com todos os campos necessários
- ✅ Detecta redirects (302) para sucesso
- ✅ Extrai mensagens de erro do HTML

### 2. Caminhos Tentados Automaticamente
O sistema tenta estes caminhos em ordem:
1. `/GitHub/lactech-backup2/lactech/safenode2/login.php`
2. `/lactech/safenode2/login.php`
3. `/safenode2/login.php`
4. `/login.php`

### 3. Configuração

Crie um arquivo `.env.local` na raiz:

```env
# URL base do PHP (sem porta)
PHP_API_BASE_URL=http://localhost

# Caminho do projeto no XAMPP (opcional, tenta detectar automaticamente)
PHP_PROJECT_PATH=/GitHub/lactech-backup2/lactech/safenode2
```

## Como Testar

1. **Verifique se o PHP está rodando:**
   - Abra: `http://localhost/GitHub/lactech-backup2/lactech/safenode2/login.php`
   - Deve mostrar a página de login PHP

2. **Execute o Next.js:**
   ```bash
   npm run dev
   ```

3. **Acesse o login:**
   - Abra: `http://localhost:3000/login`
   - Tente fazer login

4. **Verifique os logs:**
   - No terminal do Next.js, você verá:
     - "Tentando acessar: ..."
     - "✅ Página de login encontrada em: ..."
     - "CSRF Token obtido: ..."
     - "Fazendo POST para: ..."
     - "Status da resposta: ..."

## Debug

Se ainda não funcionar:

1. **Verifique o console do navegador (F12)**
   - Veja se há erros de rede
   - Verifique a resposta da API

2. **Verifique o terminal do Next.js**
   - Veja os logs de tentativas de caminho
   - Veja se o CSRF token foi obtido

3. **Teste manualmente:**
   ```bash
   curl -X GET http://localhost/GitHub/lactech-backup2/lactech/safenode2/login.php
   ```

4. **Ajuste o caminho:**
   - Se o caminho no XAMPP for diferente, ajuste `PHP_PROJECT_PATH` no `.env.local`

## Estrutura de Requisição

```
1. GET /login.php → Obtém CSRF token e cria sessão PHP
2. POST /login.php → Envia credenciais + CSRF token
3. PHP retorna 302 redirect → Login bem-sucedido
4. Next.js detecta redirect → Retorna sucesso
```

## Campos Enviados no POST

- `login=1`
- `email=...`
- `password=...`
- `safenode_csrf_token=...` (obtido do GET)
- `safenode_hv_token=...` (verificação humana)
- `safenode_hv_js=1`

## Próximos Passos

Se o login funcionar, você será redirecionado para `/dashboard` e a sessão PHP será mantida através de cookies.



