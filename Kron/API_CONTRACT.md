# 📋 CONTRATO DE API - SERVIDOR KRON

## 🎯 Visão Geral

Este documento define o contrato formal de comunicação entre o **Servidor Kron** (Core Administrativo Central) e os **Sistemas Governados** (SafeNode, Lactech, e futuros sistemas).

**Versão:** 1.0.0  
**Data:** Dezembro 2024  
**Status:** Ativo

---

## 🔐 1. AUTENTICAÇÃO ENTRE SISTEMAS

### 1.1 Token de Sistema (System Token)

Cada sistema governado recebe um **Token de Sistema** único gerado no Kron, usado para autenticar todas as requisições.

#### Geração do Token
- **Endpoint:** `POST /api/system-tokens/generate`
- **Acesso:** Apenas CEO (Super Admin Global)
- **Formato:** JWT assinado com chave secreta do Kron
- **Validade:** Permanente (pode ser revogado)
- **Escopo:** Por sistema e por comando

#### Estrutura do Token JWT
```json
{
  "iss": "kronx.sbs",
  "sub": "system_token",
  "system_id": "safenode_001",
  "system_name": "safenode",
  "scopes": ["metrics:read", "logs:write", "commands:execute"],
  "iat": 1703123456,
  "exp": null
}
```

### 1.2 Autenticação nas Requisições

Todas as requisições dos sistemas governados devem incluir:

**Header:**
```
Authorization: Bearer {system_token}
X-System-Name: {safenode|lactech|...}
X-System-Version: {version}
```

**Validação:**
- Token JWT válido e não expirado
- Sistema identificado no token corresponde ao header
- IP permitido (quando configurado)
- Escopo suficiente para a operação

---

## 📡 2. ENDPOINTS DO KRON (APIs Expostas)

### 2.1 Endpoint Base

**URL Base:** `https://kronx.sbs/api/v1/kron`

### 2.2 Receber Métricas

**Endpoint:** `POST /api/v1/kron/metrics`

**Descrição:** Sistema governado envia métricas para o Kron.

**Autenticação:** System Token

**Request Body:**
```json
{
  "system_name": "safenode",
  "timestamp": "2024-12-15T10:30:00Z",
  "metrics": [
    {
      "type": "requests_total",
      "value": 125000,
      "metadata": {
        "period": "daily",
        "sites_protected": 45
      }
    },
    {
      "type": "threats_blocked",
      "value": 234,
      "metadata": {
        "severity": "high"
      }
    }
  ]
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Métricas recebidas",
  "received_count": 2,
  "timestamp": "2024-12-15T10:30:01Z"
}
```

**Códigos de Erro:**
- `400` - Dados inválidos
- `401` - Token inválido ou expirado
- `403` - Escopo insuficiente
- `429` - Rate limit excedido
- `500` - Erro interno

### 2.3 Enviar Logs

**Endpoint:** `POST /api/v1/kron/logs`

**Descrição:** Sistema governado envia logs para auditoria central.

**Autenticação:** System Token

**Request Body:**
```json
{
  "system_name": "lactech",
  "timestamp": "2024-12-15T10:30:00Z",
  "logs": [
    {
      "level": "error",
      "message": "Falha na sincronização de dados",
      "context": {
        "user_id": 123,
        "action": "sync_data",
        "error_code": "SYNC_001"
      },
      "stack_trace": "..."
    }
  ]
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Logs recebidos",
  "received_count": 1
}
```

### 2.4 Disparar Alertas

**Endpoint:** `POST /api/v1/kron/alerts`

**Descrição:** Sistema governado dispara alertas críticos.

**Autenticação:** System Token

**Request Body:**
```json
{
  "system_name": "safenode",
  "alert_type": "security_threat",
  "severity": "critical",
  "title": "Ataque DDoS detectado",
  "message": "Taxa de requisições anormal detectada",
  "metadata": {
    "ip_source": "192.168.1.100",
    "requests_per_second": 10000
  },
  "timestamp": "2024-12-15T10:30:00Z"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "alert_id": "alert_123456",
  "notified_users": 5
}
```

### 2.5 Receber Comandos

**Endpoint:** `GET /api/v1/kron/commands/pending`

**Descrição:** Sistema governado consulta comandos pendentes.

**Autenticação:** System Token

**Query Parameters:**
- `limit` (opcional): Número máximo de comandos (padrão: 10)

**Response (200 OK):**
```json
{
  "success": true,
  "commands": [
    {
      "command_id": "cmd_123456",
      "type": "sync_data",
      "parameters": {
        "table": "users",
        "since": "2024-12-14T00:00:00Z"
      },
      "priority": "high",
      "created_at": "2024-12-15T10:25:00Z"
    }
  ]
}
```

### 2.6 Confirmar Execução de Comando

**Endpoint:** `POST /api/v1/kron/commands/{command_id}/result`

**Descrição:** Sistema governado confirma execução e retorna resultado.

**Autenticação:** System Token

**Request Body:**
```json
{
  "status": "success",
  "result": {
    "records_synced": 150,
    "duration_ms": 1250
  },
  "error": null,
  "executed_at": "2024-12-15T10:30:00Z"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Resultado registrado"
}
```

### 2.7 Verificar Status de Saúde

**Endpoint:** `GET /api/v1/kron/health`

**Descrição:** Sistema governado verifica se o Kron está operacional.

**Autenticação:** Opcional (pode ser público)

**Response (200 OK):**
```json
{
  "status": "healthy",
  "version": "1.0.0",
  "timestamp": "2024-12-15T10:30:00Z"
}
```

---

## 🔄 3. ENDPOINTS NOS SISTEMAS GOVERNADOS

### 3.1 Endpoint Base

Cada sistema deve expor uma API exclusiva para o Kron:

- **SafeNode:** `https://api.safenode.com/kron`
- **Lactech:** `https://api.lactech.com/kron`

### 3.2 Receber Comandos

**Endpoint:** `POST /kron/commands/execute`

**Descrição:** Kron envia comando para execução no sistema.

**Autenticação:** Token do Kron (JWT)

**Request Body:**
```json
{
  "command_id": "cmd_123456",
  "type": "sync_data",
  "parameters": {
    "table": "users",
    "since": "2024-12-14T00:00:00Z"
  },
  "priority": "high"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "command_id": "cmd_123456",
  "status": "queued",
  "estimated_completion": "2024-12-15T10:35:00Z"
}
```

### 3.3 Retornar Status

**Endpoint:** `GET /kron/status`

**Descrição:** Kron consulta status do sistema.

**Autenticação:** Token do Kron (JWT)

**Response (200 OK):**
```json
{
  "status": "operational",
  "version": "2.1.0",
  "uptime_seconds": 86400,
  "metrics": {
    "active_users": 1500,
    "requests_today": 50000
  },
  "timestamp": "2024-12-15T10:30:00Z"
}
```

### 3.4 Retornar Métricas

**Endpoint:** `GET /kron/metrics`

**Descrição:** Kron consulta métricas do sistema.

**Autenticação:** Token do Kron (JWT)

**Query Parameters:**
- `period` (opcional): `hour|day|week|month` (padrão: `day`)
- `since` (opcional): Data inicial (ISO 8601)

**Response (200 OK):**
```json
{
  "success": true,
  "period": "day",
  "metrics": [
    {
      "type": "requests_total",
      "value": 125000,
      "timestamp": "2024-12-15T00:00:00Z"
    }
  ]
}
```

---

## 🔒 4. SEGURANÇA

### 4.1 Rate Limiting

- **Métricas:** Máximo 100 requisições/minuto por sistema
- **Logs:** Máximo 500 requisições/minuto por sistema
- **Comandos:** Máximo 50 requisições/minuto por sistema

**Headers de Resposta:**
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1703124000
```

### 4.2 IP Allowlist (Opcional)

Sistemas podem configurar IPs permitidos para comunicação:
- **Kron → Sistema:** IPs do servidor Kron
- **Sistema → Kron:** IPs do servidor do sistema

### 4.3 Validação de Dados

- Todos os dados devem ser validados
- Timestamps em ISO 8601 (UTC)
- Valores numéricos devem ser validados
- Strings devem ter tamanho máximo definido

### 4.4 Logs de Auditoria

Todas as operações são registradas:
- Requisição recebida/enviada
- Token utilizado
- IP de origem
- Timestamp
- Resultado (sucesso/falha)

---

## 📊 5. FORMATO DE DADOS

### 5.1 Timestamps

Sempre usar formato **ISO 8601** com timezone UTC:
```
2024-12-15T10:30:00Z
```

### 5.2 Métricas

Estrutura padrão:
```json
{
  "type": "string_identificador",
  "value": "number|string",
  "metadata": {
    "chave": "valor"
  }
}
```

### 5.3 Logs

Estrutura padrão:
```json
{
  "level": "debug|info|warning|error|critical",
  "message": "string",
  "context": {},
  "stack_trace": "string (opcional)"
}
```

---

## 🚨 6. TRATAMENTO DE ERROS

### 6.1 Códigos HTTP

- `200` - Sucesso
- `400` - Requisição inválida
- `401` - Não autenticado
- `403` - Não autorizado
- `404` - Recurso não encontrado
- `429` - Rate limit excedido
- `500` - Erro interno do servidor
- `503` - Serviço indisponível

### 6.2 Formato de Erro

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Mensagem de erro legível",
    "details": {}
  },
  "timestamp": "2024-12-15T10:30:00Z"
}
```

### 6.3 Códigos de Erro Comuns

- `INVALID_TOKEN` - Token inválido ou expirado
- `INSUFFICIENT_SCOPE` - Escopo insuficiente
- `INVALID_DATA` - Dados inválidos
- `RATE_LIMIT_EXCEEDED` - Rate limit excedido
- `SYSTEM_UNAVAILABLE` - Sistema indisponível
- `COMMAND_NOT_FOUND` - Comando não encontrado

---

## 📝 7. VERSIONAMENTO

### 7.1 Versão da API

A versão atual é **v1**.

**URL:** `/api/v1/kron/...`

### 7.2 Compatibilidade

- Versões anteriores serão mantidas por pelo menos 6 meses
- Mudanças breaking serão anunciadas com 30 dias de antecedência
- Novas versões serão documentadas separadamente

---

## ✅ 8. CHECKLIST DE IMPLEMENTAÇÃO

### 8.1 No Sistema Governado

- [ ] Expor endpoint `/kron/commands/execute`
- [ ] Expor endpoint `/kron/status`
- [ ] Expor endpoint `/kron/metrics`
- [ ] Implementar autenticação via token do Kron
- [ ] Implementar envio periódico de métricas
- [ ] Implementar envio de logs críticos
- [ ] Implementar disparo de alertas
- [ ] Implementar consulta de comandos pendentes
- [ ] Implementar confirmação de execução

### 8.2 No Kron

- [ ] Implementar geração de System Tokens
- [ ] Implementar recepção de métricas
- [ ] Implementar recepção de logs
- [ ] Implementar recepção de alertas
- [ ] Implementar envio de comandos
- [ ] Implementar consulta de status
- [ ] Implementar rate limiting
- [ ] Implementar logs de auditoria
- [ ] Implementar IP allowlist (opcional)

---

## 📚 9. EXEMPLOS DE USO

### 9.1 Enviar Métricas (Sistema → Kron)

```bash
curl -X POST https://kronx.sbs/api/v1/kron/metrics \
  -H "Authorization: Bearer {system_token}" \
  -H "X-System-Name: safenode" \
  -H "Content-Type: application/json" \
  -d '{
    "system_name": "safenode",
    "timestamp": "2024-12-15T10:30:00Z",
    "metrics": [
      {
        "type": "requests_total",
        "value": 125000
      }
    ]
  }'
```

### 9.2 Receber Comando (Sistema ← Kron)

```bash
curl -X POST https://api.safenode.com/kron/commands/execute \
  -H "Authorization: Bearer {kron_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "command_id": "cmd_123456",
    "type": "sync_data",
    "parameters": {}
  }'
```

---

## 🔄 10. FLUXOS DE COMUNICAÇÃO

### 10.1 Fluxo de Métricas

```
[Sistema] --(1) Envia Métricas--> [Kron]
[Sistema] <--(2) Confirmação---- [Kron]
```

### 10.2 Fluxo de Comandos

```
[Kron] --(1) Cria Comando--> [Banco de Dados]
[Sistema] --(2) Consulta Pendentes--> [Kron]
[Kron] <--(3) Retorna Comandos-- [Sistema]
[Sistema] --(4) Executa Comando--> [Sistema]
[Sistema] --(5) Confirma Execução--> [Kron]
```

### 10.3 Fluxo de Alertas

```
[Sistema] --(1) Detecta Alerta--> [Sistema]
[Sistema] --(2) Envia Alerta--> [Kron]
[Kron] --(3) Notifica Usuários--> [Usuários]
```

---

**Última atualização:** Dezembro 2024  
**Mantido por:** Equipe Kron

