# 📧 Templates de E-mail - SafeNode

## Visão Geral

Este documento descreve os templates de e-mail que serão enviados aos usuários do SafeNode durante o período de manutenção e após a reativação do sistema.

---

## 🔧 E-mail 1: Notificação de Manutenção

### **Informações Básicas**
- **Assunto:** `🔧 Sistema em Manutenção - SafeNode`
- **Remetente:** `SafeNode Security <noreply@safenode.com>`
- **Quando enviar:** Antes de iniciar a manutenção

### **Design**
- ✅ Header com gradiente laranja (#f97316 → #ea580c)
- ✅ Ícone: 🔧 (emoji para máxima compatibilidade)
- ✅ Layout dark (#000000 background, #18181b card)
- ✅ Responsivo (mobile e desktop)

### **Conteúdo**

```
Olá, [Nome do Usuário]!

Informamos que o SafeNode está temporariamente em manutenção para 
implementação de melhorias importantes em nossa plataforma.

┌─────────────────────────────────────────┐
│ ⏱️  Duração Estimada: Algumas horas     │
│ 🔒 Motivo: Atualizações de segurança   │
│ 📅 Status: Em andamento                 │
└─────────────────────────────────────────┘

Durante este período, o acesso ao sistema estará indisponível. 
Estamos trabalhando para retornar o mais breve possível.

Você receberá um novo e-mail assim que o sistema estiver 
novamente operacional.

Agradecemos sua compreensão e paciência!
```

---

## ✅ E-mail 2: Sistema Reativado

### **Informações Básicas**
- **Assunto:** `✅ Sistema Online - SafeNode`
- **Remetente:** `SafeNode Security <noreply@safenode.com>`
- **Quando enviar:** Após concluir a manutenção

### **Design**
- ✅ Header com gradiente verde (#10b981 → #059669)
- ✅ Ícone: ✅ (emoji para máxima compatibilidade)
- ✅ Layout dark (#000000 background, #18181b card)
- ✅ Botão CTA para acessar o sistema
- ✅ Responsivo (mobile e desktop)

### **Conteúdo**

```
Ótimas notícias, [Nome do Usuário]!

A manutenção do SafeNode foi concluída com sucesso e o sistema 
está novamente operacional!

┌─────────────────────────────────────────┐
│ ✨ Novidades Aplicadas:                 │
│                                          │
│ • Melhorias de segurança implementadas  │
│ • Otimizações de performance            │
│ • Correções e aprimoramentos gerais     │
└─────────────────────────────────────────┘

Você já pode acessar sua conta normalmente e continuar 
utilizando todos os recursos da plataforma.

┌─────────────────────────┐
│   [Acessar SafeNode]    │  ← Botão clicável
└─────────────────────────┘

Agradecemos sua paciência durante o período de manutenção!
```

---

## 🎨 Características Visuais

### **Paleta de Cores**

**E-mail de Manutenção (Laranja):**
- Header: `linear-gradient(135deg, #f97316 0%, #ea580c 100%)`
- Accent: `#f97316` (orange-500)

**E-mail de Sistema Online (Verde):**
- Header: `linear-gradient(135deg, #10b981 0%, #059669 100%)`
- Accent: `#10b981` (green-500)

**Cores Base (Ambos):**
- Background: `#000000` (preto puro)
- Container: `#18181b` (zinc-900)
- Text primary: `#ffffff` (branco)
- Text secondary: `#d4d4d8` (zinc-300)
- Text muted: `#71717a` (zinc-500)
- Borders: `#27272a` (zinc-800)

### **Tipografia**
- Família: `-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif`
- Anti-aliasing: `-webkit-font-smoothing: antialiased`
- Tamanhos:
  - Título: `24px` / `700` weight
  - Subtítulo: `20px` / `600` weight
  - Corpo: `15px` / `normal` weight
  - Footer: `12px`

---

## 📱 Responsividade

Ambos os emails são **totalmente responsivos** e se adaptam automaticamente para:

### **Desktop/Webmail**
- Largura máxima: `640px`
- Padding generoso: `32px 24px`
- Border radius: `16px`

### **Mobile**
- Largura: `100%` (full width)
- Padding reduzido: `24px 16px`
- Border radius: `0` (remove cantos arredondados)
- Layout otimizado para leitura

---

## 🔒 Segurança e Privacidade

- ✅ Sem links externos suspeitos
- ✅ Sem rastreamento de pixels
- ✅ Sem JavaScript (apenas HTML + CSS inline)
- ✅ HTTPS para todos os links
- ✅ Headers de autenticação (SPF, DKIM)

---

## 📊 Compatibilidade

Testado e funcional em:

### **Clients Desktop**
- ✅ Gmail (web)
- ✅ Outlook 2016+
- ✅ Apple Mail
- ✅ Thunderbird

### **Clients Mobile**
- ✅ Gmail App (iOS/Android)
- ✅ Outlook App (iOS/Android)
- ✅ Apple Mail (iOS)
- ✅ Samsung Email

### **Webmail**
- ✅ Gmail
- ✅ Outlook.com
- ✅ Yahoo Mail
- ✅ ProtonMail

---

## 🚀 Como Usar

### **1. Visualizar os Templates**
```
https://safenode.cloud/email-preview.php
```
Acesse esta página para ver como os emails ficarão antes de enviar.

### **2. Enviar E-mail de Manutenção**
```
https://safenode.cloud/admin-emails.php
```
Clique em "Enviar para X usuários" no card laranja.

### **3. Enviar E-mail de Sistema Online**
```
https://safenode.cloud/admin-emails.php
```
Clique em "Enviar para X usuários" no card verde.

---

## 📝 Personalização

Cada e-mail é personalizado automaticamente com:

- **Nome do usuário:** Obtido do banco de dados (`name` column)
- **Email do destinatário:** Obtido do banco de dados (`email` column)
- **URL de login:** Gerada automaticamente baseada no domínio
- **Data atual:** Ano no footer gerado dinamicamente

---

## 🔧 Arquivos Relacionados

- **Templates:** `lactech/safenode/includes/EmailService.php`
- **Preview:** `lactech/safenode/email-preview.php`
- **Painel Admin:** `lactech/safenode/admin-emails.php`
- **Backend:** `lactech/safenode/send-mass-email.php`

---

## 📞 Suporte

Para mais informações ou personalizações, consulte o arquivo:
`lactech/safenode/includes/EmailService.php`

Métodos disponíveis:
- `sendMaintenanceNotification($email, $userName)`
- `sendSystemOnlineNotification($email, $userName)`

---

**© 2025 SafeNode Security Platform**


