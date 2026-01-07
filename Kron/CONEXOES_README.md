# Sistema de Conexões KRON

## Estrutura Criada

### KRON (kronx.sbs)

#### Páginas:
- **`dashboard/index.php`** - Dashboard principal do KRON
  - Visualiza conexões ativas
  - Botões para conectar SafeNode e LacTech
  - Modal com QR Code e token manual

#### APIs:
- **`api/generate-connection-token.php`** - Gera token temporário de conexão
- **`api/verify-connection-token.php`** - Valida token (chamado pelos sistemas destino)
- **`api/user-connections.php`** - Lista conexões do usuário

#### Classes:
- **`includes/KronConnectionManager.php`** - Gerencia conexões e tokens
- **`includes/KronQRGenerator.php`** - Gera QR Code com logo da KRON

---

### SafeNode (safenode.cloud)

#### Páginas:
- **`kron-connection.php`** - Página de conexão com KRON
  - Escanear QR Code (câmera)
  - Inserir token manualmente
  - Status da conexão
  - Desconectar

#### Classes:
- **`includes/KronConnector.php`** - Conector com API do KRON

#### Menu:
- Link "Conexão KRON" adicionado no perfil (desktop e mobile)

---

### LacTech (lactechsys.com)

#### Páginas:
- **`kron-connection.php`** - Página de conexão com KRON
  - Escanear QR Code (câmera)
  - Inserir token manualmente
  - Status da conexão
  - Desconectar

#### Classes:
- **`includes/KronConnector.php`** - Conector com API do KRON

---

## Fluxo de Conexão

### 1. No KRON:
1. Usuário acessa `dashboard/index.php`
2. Clica em "Conectar SafeNode" ou "Conectar LacTech"
3. Modal abre com:
   - QR Code com logo da KRON
   - Token manual (copiável)
   - Countdown de expiração (10 minutos)

### 2. No Sistema Destino (SafeNode/LacTech):
1. Usuário acessa Perfil → Conexão KRON
2. Duas opções:
   - **QR Code**: Clica em "Abrir Câmera", escaneia o código do KRON
   - **Token Manual**: Copia o token do KRON e cola no campo

### 3. Validação:
1. Sistema destino envia token para `kronx.sbs/api/verify-connection-token.php`
2. KRON valida token (verifica expiração, hash, etc.)
3. Se válido:
   - Cria conexão permanente
   - Retorna `connection_token` permanente
   - Sistema destino salva no banco

---

## Configuração

### URLs da API KRON:
- **Local**: `http://localhost/lactech/kron/api`
- **Produção**: `https://kronx.sbs/api`

As classes `KronConnector` detectam automaticamente o ambiente.

### Banco de Dados:
- **KRON**: Tabelas `kron_connection_tokens`, `kron_user_connections`, `kron_connection_logs`
- **SafeNode**: Colunas `kron_user_id`, `kron_connection_token`, `kron_connected_at` na tabela `safenode_users`
- **LacTech**: Colunas `kron_user_id`, `kron_connection_token`, `kron_connected_at` na tabela `users`

---

## Dependências

### Frontend:
- Tailwind CSS (via CDN)
- Lucide Icons (via CDN)
- html5-qrcode (para leitura de QR Code)

### Backend:
- PHP 7.4+
- PDO MySQL
- cURL (para comunicação entre sistemas)
- GD Library (opcional, para QR Code com logo)

---

## Segurança

- Tokens temporários expiram em 10 minutos
- Hash HMAC para validação de tokens
- Logs de todas as tentativas de conexão
- Validação de origem e dados em todas as APIs
- CORS configurado para APIs públicas

---

## Próximos Passos

1. Implementar desconexão no dashboard do KRON
2. Adicionar sincronização de dados entre sistemas
3. Dashboard unificado com dados de todos os sistemas
4. Notificações de eventos entre sistemas

