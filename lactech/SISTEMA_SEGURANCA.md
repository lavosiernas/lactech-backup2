# Sistema de Segurança - LacTech

## 📋 Visão Geral

Sistema completo de segurança para alteração de senha e vinculação de contas, seguindo as melhores práticas de segurança.

## 🗂️ Estrutura do Banco de Dados

### Tabelas Criadas

1. **email_verifications** - Verificação de e-mail dos usuários
2. **otp_codes** - Códigos OTP (One-Time Password) para ações sensíveis
3. **google_accounts** - Vinculação de contas Google
4. **security_audit_log** - Logs de auditoria de segurança
5. **two_factor_auth** - Autenticação de dois fatores (TOTP)

### Campos Adicionados em `users`

- `email_verified` - Se o e-mail foi verificado
- `email_verified_at` - Data de verificação
- `password_changed_at` - Data da última alteração de senha
- `password_change_required` - Se a alteração é obrigatória
- `last_security_check` - Última verificação de segurança
- `failed_login_attempts` - Tentativas de login falhadas
- `account_locked_until` - Data de desbloqueio da conta

## 🔐 Funcionalidades Implementadas

### 1. Verificação de E-mail

**Requisito**: O usuário deve verificar seu e-mail antes de realizar ações sensíveis.

**Fluxo**:
1. Usuário cadastra/atualiza e-mail
2. Sistema envia e-mail com token de verificação
3. Usuário clica no link do e-mail ou insere o token
4. E-mail é marcado como verificado

**API Endpoints**:
- `POST /api/security.php?action=request_email_verification` - Solicitar verificação
- `GET /api/security.php?action=verify_email` - Verificar com token
- `GET /api/security.php?action=get_verification_status` - Verificar status

### 2. Sistema OTP (One-Time Password)

**Funcionalidade**: Geração e validação de códigos únicos para ações sensíveis.

**Características**:
- Códigos de 6 dígitos
- Validade de 5 minutos
- Uso único (não pode ser reutilizado)
- Enviado por e-mail
- Armazenamento seguro no banco

**Ações que requerem OTP**:
- `password_change` - Alteração de senha
- `email_change` - Alteração de e-mail
- `google_unlink` - Desvinculação de Google
- `2fa_setup` - Configuração de 2FA

**API Endpoints**:
- `POST /api/security.php?action=generate_otp` - Gerar código OTP
- `POST /api/security.php?action=validate_otp` - Validar código OTP

### 3. Alteração Segura de Senha

**Fluxo Completo**:
1. Usuário solicita alteração de senha
2. Sistema verifica se e-mail está verificado
3. Sistema gera e envia OTP por e-mail
4. Usuário insere nova senha, confirmação e código OTP
5. Sistema valida OTP
6. Senha é alterada e hash é gerado
7. Todas as sessões são encerradas (exceto a atual)
8. Notificação de segurança é enviada por e-mail
9. Log de auditoria é registrado

**API Endpoint**:
- `POST /api/security.php?action=change_password`

**Parâmetros**:
```json
{
  "new_password": "novaSenha123",
  "confirm_password": "novaSenha123",
  "otp_code": "123456"
}
```

### 4. Sistema de Auditoria

**Funcionalidade**: Registro de todas as ações sensíveis de segurança.

**Ações Registradas**:
- `otp_generated` - OTP gerado
- `otp_validated` - OTP validado
- `otp_validation_failed` - Falha na validação
- `email_verified` - E-mail verificado
- `password_changed` - Senha alterada
- `google_linked` - Google vinculado
- `google_unlinked` - Google desvinculado
- `2fa_enabled` - 2FA ativado
- `2fa_disabled` - 2FA desativado

**Informações Registradas**:
- ID do usuário
- Ação realizada
- Descrição
- IP address
- User Agent
- Sucesso/Falha
- Metadados (JSON)
- Data/Hora

**API Endpoint**:
- `GET /api/security.php?action=get_security_history` - Buscar histórico

### 5. Notificações de Segurança

**E-mails Enviados**:
- Verificação de e-mail
- Código OTP
- Alteração de senha
- Vinculação/desvinculação Google
- Ativação/desativação 2FA
- Alertas de segurança

**Templates**:
- Verificação de e-mail (HTML)
- Código OTP (HTML)
- Notificações de segurança (HTML)

## 📁 Arquivos Criados

### Backend
- `includes/database_security_tables.sql` - Script SQL das tabelas
- `includes/SecurityService.class.php` - Serviço de segurança
- `includes/EmailService.class.php` - Serviço de e-mail
- `api/security.php` - API de segurança
- `verify-email.php` - Página de verificação de e-mail

## 🚀 Como Usar

### 1. Configurar Banco de Dados

Execute o script SQL para criar as tabelas:
```sql
SOURCE lactech/includes/database_security_tables.sql;
```

### 2. Configurar E-mail (Opcional)

Edite `EmailService.class.php` para configurar SMTP ou integrar com serviço de e-mail (SendGrid, Mailgun, etc).

Por enquanto, usa `mail()` nativo do PHP.

### 3. Fluxo de Alteração de Senha

**Frontend (JavaScript)**:
```javascript
// 1. Solicitar OTP
const otpResponse = await fetch('./api/security.php?action=generate_otp', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ action_type: 'password_change' })
});

// 2. Usuário insere código OTP recebido por e-mail

// 3. Alterar senha com OTP
const changePasswordResponse = await fetch('./api/security.php?action=change_password', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
        new_password: 'novaSenha123',
        confirm_password: 'novaSenha123',
        otp_code: '123456'
    })
});
```

## 🔒 Segurança Implementada

### Proteções

1. **Verificação de E-mail Obrigatória**
   - Ações sensíveis exigem e-mail verificado
   - Token de verificação expira em 24 horas

2. **OTP com Validade Limitada**
   - Códigos expiram em 5 minutos
   - Uso único
   - Não podem ser reutilizados

3. **Encerramento de Sessões**
   - Após alteração de senha, todas as sessões são encerradas
   - Protege contra acesso não autorizado

4. **Auditoria Completa**
   - Todas as ações são registradas
   - IP e User Agent são capturados
   - Facilita rastreamento de tentativas suspeitas

5. **Notificações de Segurança**
   - E-mails são enviados para todas as ações sensíveis
   - Usuário é notificado imediatamente

## 📝 Próximos Passos (Pendentes)

### 1. Vinculação Google (OAuth)
- Implementar OAuth 2.0 com Google
- Permitir login via Google
- Proteger desvinculação com OTP

### 2. Autenticação de Dois Fatores (2FA/TOTP)
- Implementar TOTP (Google Authenticator)
- Permitir códigos de backup
- Opcional mas recomendado

### 3. Interface de Segurança
- Adicionar seção de segurança no perfil
- Mostrar status de verificação
- Histórico de ações de segurança
- Configuração de 2FA

## ⚠️ Importante

- **E-mails**: Por padrão, usa `mail()` do PHP. Em produção, configure SMTP ou integre com serviço de e-mail.
- **Tokens**: Tokens de verificação devem ser armazenados de forma segura.
- **OTPs**: Códigos OTP nunca devem ser logados completamente.
- **Sessões**: Sistema encerra sessões após alteração de senha por segurança.

## 📞 Suporte

Em caso de dúvidas ou problemas, verifique os logs de erro do PHP e os logs de auditoria no banco de dados.



