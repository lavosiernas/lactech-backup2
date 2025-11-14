# 🎯 Guia Passo a Passo: Configurar BIMI na Hostinger

## 📋 O que vamos fazer?

Configurar o BIMI para que a logo do LacTech apareça nos emails enviados pelo sistema.

## 🚀 Passo a Passo na Hostinger

### Passo 1: Acessar o Painel da Hostinger

1. Acesse: https://www.hostinger.com.br/
2. Faça login na sua conta
3. Vá em **Painel hPanel** ou **Painel de Controle**

### Passo 2: Localizar Gerenciamento de DNS

1. No painel, procure por **DNS** ou **Zone Editor** ou **Gerenciamento de DNS**
2. Geralmente está em:
   - **Domínios** > **Gerenciar** > **DNS**
   - Ou **Domínios** > **DNS**
   - Ou **Avançado** > **Zone Editor**

### Passo 3: Adicionar Registro TXT

1. Clique em **Adicionar Registro** ou **+ Novo Registro**
2. Selecione o tipo: **TXT**

### Passo 4: Preencher os Campos

**Nome/Host:**
```
default._bimi
```

**Tipo:**
```
TXT
```

**Valor/Conteúdo:**
```
v=BIMI1; l=https://i.postimg.cc/vmrkgDcB/lactech.png;
```

**TTL (opcional):**
```
3600
```
ou deixe o padrão

### Passo 5: Salvar

1. Clique em **Salvar** ou **Adicionar**
2. Aguarde alguns segundos para confirmar que foi adicionado

## 📝 Exemplo Visual dos Campos

```
┌─────────────────────────────────────────────┐
│ Tipo: TXT                                    │
│ Nome: default._bimi                         │
│ Valor: v=BIMI1; l=https://i.postimg.cc/...   │
│ TTL: 3600 (ou padrão)                         │
└─────────────────────────────────────────────┘
```

## ✅ Verificação

Após adicionar, você deve ver algo assim na lista de registros DNS:

```
default._bimi.lactechsys.com    TXT    v=BIMI1; l=https://i.postimg.cc/vmrkgDcB/lactech.png;
```

## ⏰ Tempo de Propagação

- **Mínimo**: 1-2 horas
- **Normal**: 24 horas
- **Máximo**: 48 horas

## 🔍 Como Verificar se Funcionou

1. Aguarde pelo menos 2 horas
2. Acesse: https://bimigroup.org/selectors/
3. Digite: `lactechsys.com`
4. Clique em **Check**
5. Se aparecer o registro, está funcionando!

## 🎨 Alternativa: Hospedar Logo no Seu Servidor

Se preferir hospedar a logo no seu próprio servidor (mais recomendado):

### Passo 1: Fazer Upload da Logo

1. Baixe a logo: `https://i.postimg.cc/vmrkgDcB/lactech.png`
2. Faça upload para: `lactech/assets/images/lactech-logo.svg` (ou .png)
3. Certifique-se de que está acessível via: `https://lactechsys.com/assets/images/lactech-logo.svg`

### Passo 2: Usar URL do Seu Servidor

**Valor para colocar na Hostinger:**
```
v=BIMI1; l=https://lactechsys.com/assets/images/lactech-logo.svg;
```

## 📧 Teste nos Emails

Após a propagação, envie um email OTP do sistema e verifique:
- Gmail: Logo aparece ao lado do remetente
- Yahoo Mail: Logo aparece ao lado do remetente
- Outlook: Pode não aparecer (suporte limitado)

## ⚠️ Observações Importantes

1. **HTTPS obrigatório**: A logo precisa estar em servidor HTTPS
2. **Formato recomendado**: SVG (preferível) ou PNG
3. **Tamanho**: 512x512 pixels recomendado
4. **Não todos os clientes suportam**: Gmail e Yahoo sim, outros podem não

## 🆘 Problemas Comuns

### Registro não aparece após 48h
- Verifique se o nome está correto: `default._bimi` (sem `.lactechsys.com`)
- Verifique se o valor está correto (sem espaços extras)
- Verifique se salvou corretamente

### Logo não aparece nos emails
- Aguarde mais tempo (pode levar até 48h)
- Verifique se o cliente de email suporta BIMI (Gmail, Yahoo sim)
- Verifique se a URL da logo está acessível via HTTPS

### Erro ao adicionar
- Certifique-se de que o tipo é **TXT**
- Certifique-se de que o nome é apenas `default._bimi` (a Hostinger adiciona o domínio automaticamente)

## 📞 Suporte Hostinger

Se tiver dúvidas sobre como acessar o DNS na Hostinger:
1. Acesse o chat de suporte da Hostinger
2. Peça ajuda para "adicionar registro DNS TXT"
3. Mostre este guia para o suporte

---

## 🎯 Resumo: O que copiar e colar na Hostinger

**Nome:**
```
default._bimi
```

**Valor:**
```
v=BIMI1; l=https://i.postimg.cc/vmrkgDcB/lactech.png;
```

Pronto! É só isso! 🎉

