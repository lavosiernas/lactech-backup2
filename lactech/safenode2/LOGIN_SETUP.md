# Configuração do Login

## Problema do 404

O erro 404 ocorre porque o Next.js precisa se comunicar com o PHP que está rodando em outra porta/URL.

## Solução

### 1. Configurar variável de ambiente

Crie um arquivo `.env.local` na raiz do projeto:

```env
PHP_API_BASE_URL=http://localhost
```

**Importante:** 
- Se o PHP está rodando no XAMPP na porta 80, use: `http://localhost`
- Se está em outra porta, use: `http://localhost:PORTA`
- Se está em produção, use a URL completa: `https://seu-dominio.com`

### 2. Verificar se o login.php está acessível

Teste diretamente no navegador:
- `http://localhost/login.php` (deve mostrar a página de login PHP)

### 3. Estrutura esperada

O Next.js faz proxy para:
- `/api/login` → chama `http://localhost/login.php`

### 4. Debug

Se ainda der erro 404, verifique:
1. O PHP está rodando? (XAMPP/WAMP ativo?)
2. A URL em `.env.local` está correta?
3. O arquivo `login.php` existe na raiz do projeto?
4. Verifique o console do navegador e o terminal do Next.js para logs

### 5. Teste manual

Você pode testar o endpoint diretamente:

```bash
curl -X POST http://localhost:3000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"teste@teste.com","password":"senha","hv_token":"token"}'
```

## Estrutura de Arquivos

```
safenode2/
├── login.php          ← Arquivo PHP original (raiz)
├── app/
│   └── api/
│       └── login/
│           └── route.ts  ← Endpoint Next.js que chama login.php
└── .env.local         ← Configuração da URL do PHP
```








