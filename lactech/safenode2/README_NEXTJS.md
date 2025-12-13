# SafeNode - Next.js Frontend

Migração do SafeNode para React/Next.js mantendo o backend PHP.

## 🚀 Estrutura do Projeto

```
safenode2/
├── app/                    # Next.js App Router
│   ├── api/               # API Routes (proxy para PHP)
│   ├── dashboard/         # Página do dashboard
│   ├── login/             # Página de login
│   ├── layout.tsx         # Layout principal
│   ├── page.tsx           # Página inicial
│   └── globals.css        # Estilos globais
├── components/             # Componentes React
│   └── pages/             # Páginas como componentes
├── lib/                   # Utilitários e serviços
│   ├── api.ts             # Cliente API
│   └── auth.ts            # Gerenciamento de autenticação
├── types/                  # TypeScript types
└── api/                   # APIs PHP (mantidas)
```

## 📦 Instalação

1. **Instalar dependências:**
```bash
npm install
# ou
yarn install
```

2. **Configurar variáveis de ambiente:**
Crie um arquivo `.env.local` na raiz do projeto:

```env
NEXT_PUBLIC_API_URL=http://localhost/api/php
NEXT_PUBLIC_BASE_URL=http://localhost:3000
PHP_API_BASE_URL=http://localhost
```

3. **Executar em desenvolvimento:**
```bash
npm run dev
```

4. **Build para produção:**
```bash
npm run build
npm start
```

## 🔧 Configuração

### Proxy para APIs PHP

O Next.js usa um proxy para fazer requisições aos endpoints PHP. O proxy está configurado em:
- `app/api/php-proxy/[...path]/route.ts`

As requisições são redirecionadas automaticamente de `/api/php/*` para os endpoints PHP correspondentes.

### Autenticação

O sistema de autenticação usa cookies HTTP-only para segurança. O token é gerenciado através de:
- `lib/auth.ts` - Funções de autenticação
- Cookies são configurados com `secure` e `sameSite: 'strict'`

## 📝 Páginas Migradas

- ✅ Página inicial (`/`)
- ✅ Login (`/login`)
- ✅ Dashboard (`/dashboard`)

## 🔄 Próximos Passos

1. Migrar página de registro
2. Migrar páginas de configurações
3. Migrar páginas de logs e analytics
4. Migrar páginas de sites
5. Adicionar testes
6. Otimizar performance

## 🛠️ Tecnologias

- **Next.js 14** - Framework React
- **TypeScript** - Tipagem estática
- **Tailwind CSS** - Estilização
- **Axios** - Cliente HTTP
- **Chart.js** - Gráficos
- **Lucide React** - Ícones

## 📚 Estrutura de API

As APIs PHP são mantidas e consumidas através do proxy. Exemplos:

```typescript
import { statsApi, authApi } from '@/lib/api'

// Obter estatísticas
const stats = await statsApi.getIndexStats()

// Fazer login
const response = await authApi.login(email, password, hvToken)
```

## 🔒 Segurança

- Cookies HTTP-only para tokens
- CSRF protection (via PHP backend)
- XSS protection
- Rate limiting (via PHP backend)
- Human Verification (via PHP backend)

## 📄 Licença

MIT






