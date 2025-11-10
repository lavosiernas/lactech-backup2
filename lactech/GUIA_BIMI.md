# 🎨 Guia de Configuração BIMI para LacTech

## 📋 O que é BIMI?

BIMI (Brand Indicators for Message Identification) é um padrão que permite que empresas exibam seu logo em emails enviados. Isso melhora a identidade visual e a confiança dos emails.

## ⚠️ É Obrigatório?

**NÃO!** BIMI é totalmente opcional. O sistema funciona perfeitamente sem ele. É apenas uma funcionalidade de branding para melhorar a aparência dos emails.

## 🚀 Quando Configurar BIMI?

Configure BIMI se você:
- ✅ Quer mostrar a logo do LacTech nos emails
- ✅ Quer melhorar a identidade visual dos emails
- ✅ Quer passar mais confiança aos usuários
- ✅ Já tem certificado VMC (opcional, mas recomendado)

**NÃO precisa configurar se:**
- ❌ Os emails já estão funcionando bem
- ❌ Não se importa com branding nos emails
- ❌ Não quer fazer configurações adicionais no DNS

## 📝 Como Configurar na Hostinger

### Passo 1: Preparar o Logo

1. Baixe a logo do sistema: `https://i.postimg.cc/vmrkgDcB/lactech.png`
2. Ou use outra logo do LacTech
3. A logo deve estar em formato SVG (preferível) ou PNG
4. Tamanho recomendado: 512x512 pixels

### Passo 2: Hospedar o Logo

1. Faça upload da logo para um local acessível via HTTPS
2. Exemplo: `https://lactechsys.com/assets/images/lactech-logo.svg`
3. Certifique-se de que o arquivo está acessível publicamente

### Passo 3: Configurar DNS na Hostinger

1. Acesse o painel da Hostinger
2. Vá em **DNS** ou **Zone Editor**
3. Adicione um novo registro **TXT**:

**Tipo**: `TXT`  
**Nome/Host**: `default._bimi`  
**Valor**: `v=BIMI1; l=https://lactechsys.com/assets/images/lactech-logo.svg;`

**Exemplo completo:**
```
default._bimi.lactechsys.com    TXT    v=BIMI1; l=https://lactechsys.com/assets/images/lactech-logo.svg;
```

### Passo 4: Aguardar Propagação

- Aguarde de 24 a 48 horas para o DNS propagar
- Verifique se está funcionando em: https://bimigroup.org/selectors/

## 🔐 BIMI com VMC (Opcional - Avançado)

Para máxima compatibilidade, você pode usar um certificado VMC (Verified Mark Certificate):

```
default._bimi.lactechsys.com    TXT    v=BIMI1; l=https://lactechsys.com/assets/images/lactech-logo.svg; a=https://lactechsys.com/.well-known/bimi/lactech-logo.svg;
```

**Nota**: VMC requer certificado pago (aproximadamente $200-500/ano)

## ✅ Verificação

Após configurar, verifique se está funcionando:

1. **Ferramenta BIMI**: https://bimigroup.org/selectors/
2. **Digite**: `lactechsys.com`
3. **Verifique**: Se o registro aparece corretamente

## 📧 Teste

1. Envie um email OTP ou notificação do sistema
2. Verifique no cliente de email (Gmail, Outlook, etc.)
3. A logo deve aparecer ao lado do remetente (se suportado)

## ⚠️ Limitações

- **Não todos os clientes de email suportam BIMI**: Gmail e Yahoo Mail suportam, mas outros podem não
- **Requer HTTPS**: O logo deve estar em servidor HTTPS
- **Propagação DNS**: Pode levar até 48 horas
- **VMC opcional**: Para máxima compatibilidade, mas custa dinheiro

## 🎯 Recomendação

**Para começar (gratuito):**
```
default._bimi.lactechsys.com    TXT    v=BIMI1; l=https://i.postimg.cc/vmrkgDcB/lactech.png;
```

**Para produção (com VMC):**
```
default._bimi.lactechsys.com    TXT    v=BIMI1; l=https://lactechsys.com/assets/images/lactech-logo.svg; a=https://lactechsys.com/.well-known/bimi/lactech-logo.svg;
```

## 📚 Recursos

- Documentação BIMI: https://bimigroup.org/
- Verificador BIMI: https://bimigroup.org/selectors/
- Guia Google: https://support.google.com/a/answer/10949050

---

**Resumo**: BIMI é opcional. Configure se quiser melhorar o branding dos emails. Não é necessário para o sistema funcionar.


