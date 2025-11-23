# Guia: Como Criar API Token do Cloudflare

## Permissões Necessárias

O SafeNode precisa de um **API Token Customizado** do Cloudflare com as seguintes permissões:

### Permissões Obrigatórias:

1. **Zone → Zone:Read**
   - Permite ler detalhes da zona/site
   
2. **Zone → DNS:Edit**
   - Permite criar, editar e deletar registros DNS
   - Necessário para o "Gerenciar DNS" funcionar

3. **Zone → Firewall Services:Edit** (Opcional, mas recomendado)
   - Permite criar regras de firewall
   - Necessário para sincronização automática de bloqueios

### Escopo (Account Resources):

- Selecione sua **Conta** do Cloudflare
- Ou selecione **Zona Específica** → Escolha o domínio (ex: `lactechsys.com`)

---

## Passo a Passo para Criar o Token

### 1. Acesse o Painel do Cloudflare

1. Faça login em: https://dash.cloudflare.com/
2. Clique no ícone do perfil (canto superior direito)
3. Selecione **"My Profile"** (Meu Perfil)

### 2. Crie o API Token

1. Na barra lateral esquerda, clique em **"API Tokens"**
2. Clique no botão **"Create Token"** (Criar Token)
3. Você verá alguns templates, mas **não use nenhum deles**
4. Role até o final e clique em **"Create Custom Token"** (Criar Token Customizado)

### 3. Configure as Permissões

**Nome do Token:**
```
SafeNode - Gerenciamento DNS e Firewall
```

**Permissions (Permissões):**

1. **Zone** → **DNS:Edit** → Selecione sua conta ou zona específica
2. **Zone** → **Zone:Read** → Selecione sua conta ou zona específica  
3. **Zone** → **Firewall Services:Edit** → Selecione sua conta ou zona específica (Opcional)

**Account Resources (Recursos da Conta):**

- Escolha **"Include"** (Incluir)
- Selecione sua conta ou zona específica (ex: `lactechsys.com`)

### 4. Continue e Crie

1. Clique em **"Continue to summary"** (Continuar para resumo)
2. Revise as permissões
3. Clique em **"Create Token"** (Criar Token)

### 5. Copie o Token

⚠️ **IMPORTANTE:** Copie o token imediatamente! Ele só será mostrado uma vez.

O token será algo assim:
```
abc123def456ghi789jkl012mno345pqr678stu901vwx234yz
```

---

## Como Configurar no SafeNode

### Opção 1: Configuração Global (Recomendado)

1. No SafeNode, vá em **Configurações** → **Cloudflare**
2. Cole o API Token no campo **"Cloudflare API Token"**
3. Salve as configurações

### Opção 2: Por Site

1. Edite o site específico
2. Cole o API Token no campo **"Cloudflare API Token"** (se disponível)

---

## Onde Encontrar o Zone ID

Se você precisa do **Zone ID** para configurar no site:

1. No Cloudflare, selecione o domínio
2. Role até o final da página inicial da zona
3. No lado direito, você verá **"Zone ID"**
4. Copie e cole no SafeNode em **Configurações do Site → Cloudflare Zone ID**

---

## Verificar se Está Funcionando

Depois de configurar o token:

1. No SafeNode, vá em **Sites**
2. Se o site tiver Zone ID configurado, você verá o botão **"Gerenciar DNS"**
3. Clique nele - deve abrir a página de gerenciamento DNS sem erros

---

## Segurança

- ✅ O token tem permissões limitadas apenas para DNS e Firewall
- ✅ Pode ser limitado a uma zona específica
- ✅ Pode ser revogado a qualquer momento no Cloudflare
- ⚠️ Mantenha o token seguro e não compartilhe

---

## Troubleshooting

**Erro: "API Token ou Zone ID não configurado"**
- Verifique se o token foi copiado corretamente (sem espaços)
- Verifique se o Zone ID está configurado no site
- Verifique se as permissões do token estão corretas

**Erro: "Insufficient permissions"**
- Verifique se o token tem as permissões DNS:Edit e Zone:Read
- Verifique se o token está associado à conta/zona correta

