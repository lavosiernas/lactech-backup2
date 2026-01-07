# SafeNode V1 - Checklist de Refatoração

## RESUMO
**SafeNode V1 = Verificação humana real + visibilidade clara do tráfego.**

Tudo que não ajuda nisso: não entra, não aparece, não evolui.

---

## ✅ O QUE MANTER (CORE)

### 1. Verificação Humana
- ✅ Captcha/challenge próprio
- ✅ Detecção humano vs bot
- ✅ Bloqueio real
- ✅ Logs reais
- ✅ Métricas em tempo real
- **Status:** CORAÇÃO DO PRODUTO

### 2. Gerenciar Sites
- ✅ Cadastro de domínio
- ✅ Definição de endpoints protegidos
- ✅ Ativar/desativar verificação humana
- ✅ Status simples do site

### 3. Logs Reais
- ✅ Eventos reais apenas
- ✅ Logs de:
  - humano validado
  - bot bloqueado
  - acesso permitido
- ✅ Linguagem clara e direta

### 4. Dashboard
- ✅ Tráfego humano vs bot
- ✅ Último evento relevante
- ✅ Status geral
- ❌ Sem gráficos inúteis

### 5. IPs Suspeitos
- ✅ Só IPs que falharam verificação humana
- ✅ Histórico real
- ✅ Bloqueio manual opcional

### 6. Configurações
- ✅ Chaves de API
- ✅ Configuração do desafio humano
- ✅ Notificações básicas

### 7. Ajuda
- ✅ Documentação focada na verificação humana
- ✅ Como integrar
- ✅ Como interpretar o painel

---

## ❌ O QUE REMOVER/OCULTAR

### CONGELADAS (código pode existir, produto não)
- ❌ Threat Intelligence
- ❌ Attack Predictor
- ❌ Vulnerability Scanner
- ❌ Security Tests / Pentest
- ❌ Security Advisor
- ❌ Endpoint Protection avançado
- ❌ Behavioral Analysis com ML
- ❌ Anomaly Detector avançado
- ❌ Analytics complexos
- ❌ Revenue Dashboard
- ❌ Updates / changelog visível
- ❌ Mail como produto
- ❌ Relatórios enterprise

### REMOVIDAS DO POSICIONAMENTO (nem citar)
- ❌ "ML avançado"
- ❌ "Proteção enterprise"
- ❌ "Substitui Cloudflare"
- ❌ "Plataforma completa"
- ❌ "Tudo em um"

---

## 📋 CHECKLIST TÉCNICO

### FASE 1: Sidebar e Navegação
- [ ] Remover seção "Análises" (exceto Logs)
  - [ ] Remover: Comportamental
  - [ ] Remover: Analytics
  - [ ] Remover: Alvos Atacados
  - [ ] Manter: Explorar Logs (renomear para "Logs")
  - [ ] Manter: IPs Suspeitos
- [ ] Remover seção "Inteligência" completa
  - [ ] Remover: Threat Intelligence
  - [ ] Remover: Security Advisor
  - [ ] Remover: Vulnerability Scanner
  - [ ] Remover: Anomaly Detector
  - [ ] Remover: Proteção por Endpoint
  - [ ] Remover: Testes de Segurança
- [ ] Remover seção "Sistema" (exceto Configurações e Ajuda)
  - [ ] Remover: Atualizações
  - [ ] Manter: Verificação Humana (mover para Principal)
  - [ ] Manter: Configurações
  - [ ] Manter: Ajuda
- [ ] Remover do menu Principal
  - [ ] Remover: Mail
- [ ] Reorganizar menu Principal
  - [ ] Dashboard
  - [ ] Gerenciar Sites
  - [ ] Verificação Humana
  - [ ] Logs
  - [ ] IPs Suspeitos
  - [ ] Configurações
  - [ ] Ajuda

### FASE 2: Dashboard Principal
- [ ] Remover gráficos complexos
  - [ ] Remover: Gráfico de ameaças (donut)
  - [ ] Remover: Gráfico de anomalias
  - [ ] Remover: Tabela de dispositivos de rede
- [ ] Adicionar métricas focadas
  - [ ] Card: Tráfego Humano (total, últimas 24h)
  - [ ] Card: Bots Bloqueados (total, últimas 24h)
  - [ ] Card: Taxa de Bloqueio (%)
  - [ ] Card: Status Geral (operacional/atento/atenção)
- [ ] Adicionar seção "Último Evento Relevante"
  - [ ] Mostrar último evento (humano validado, bot bloqueado, acesso permitido)
  - [ ] Timestamp claro
  - [ ] IP e domínio envolvidos
- [ ] Adicionar gráfico simples (opcional)
  - [ ] Gráfico de linha: Humanos vs Bots (últimas 24h ou 7 dias)
  - [ ] Apenas se realmente útil

### FASE 3: Página de Logs
- [ ] Simplificar filtros
  - [ ] Filtro por tipo: Humano Validado / Bot Bloqueado / Acesso Permitido
  - [ ] Filtro por data
  - [ ] Filtro por domínio (se múltiplos sites)
- [ ] Remover colunas desnecessárias
  - [ ] Manter: Data/Hora
  - [ ] Manter: Tipo de Evento
  - [ ] Manter: IP
  - [ ] Manter: Domínio
  - [ ] Remover: Análises complexas, scores, etc
- [ ] Linguagem clara
  - [ ] "Humano validado" ao invés de "verification_success"
  - [ ] "Bot bloqueado" ao invés de "bot_detected"
  - [ ] "Acesso permitido" ao invés de "access_granted"

### FASE 4: IPs Suspeitos
- [ ] Filtrar apenas IPs que falharam verificação
  - [ ] Remover IPs bloqueados por outros motivos
  - [ ] Mostrar apenas IPs que tentaram passar como humano e falharam
- [ ] Histórico real
  - [ ] Mostrar tentativas de verificação
  - [ ] Mostrar timestamps
  - [ ] Mostrar domínios afetados
- [ ] Bloqueio manual opcional
  - [ ] Botão para bloquear IP manualmente
  - [ ] Botão para desbloquear IP

### FASE 5: Verificação Humana
- [ ] Focar em configuração do desafio
  - [ ] Configuração do captcha/challenge
  - [ ] Nível de dificuldade
  - [ ] Tempo de expiração
- [ ] Chaves de API
  - [ ] Listar chaves
  - [ ] Criar nova chave
  - [ ] Revogar chave
- [ ] Estatísticas básicas
  - [ ] Total de verificações
  - [ ] Taxa de sucesso
  - [ ] Bots detectados

### FASE 6: Configurações
- [ ] Manter apenas essencial
  - [ ] Chaves de API
  - [ ] Configuração do desafio humano
  - [ ] Notificações básicas
- [ ] Remover configurações avançadas
  - [ ] Remover: Configurações de ML
  - [ ] Remover: Configurações enterprise
  - [ ] Remover: Integrações complexas

### FASE 7: Ajuda
- [ ] Reescrever documentação
  - [ ] Focar em verificação humana
  - [ ] Como integrar o SDK
  - [ ] Como interpretar o painel
  - [ ] Exemplos práticos
- [ ] Remover documentação de features não-core

### FASE 8: Limpeza
- [ ] Remover referências no código
  - [ ] Remover links para páginas congeladas
  - [ ] Remover menções a "ML avançado"
  - [ ] Remover menções a "enterprise"
- [ ] Atualizar textos de marketing
  - [ ] Landing page focada em verificação humana
  - [ ] Remover claims de "plataforma completa"
- [ ] Ocultar Revenue Dashboard
  - [ ] Remover do menu
  - [ ] Manter código (pode ser útil internamente)

---

## 🎨 ESTRUTURA DA TELA PRINCIPAL (Dashboard)

### Layout Proposto

```
┌─────────────────────────────────────────────────────────┐
│  HEADER                                                  │
│  [Menu] Dashboard | [Buscar] | [Notificações] [Perfil] │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  STATUS GERAL                                            │
│  🟢 Operacional | 🟡 Atento | 🔴 Atenção               │
└─────────────────────────────────────────────────────────┘

┌──────────┬──────────┬──────────┬──────────┐
│ HUMANO   │ BOT      │ TAXA     │ EVENTOS  │
│ 12.345   │ 1.234    │ 9.1%     │ 13.579   │
│ +5.2%    │ -12.3%   │          │ +8.1%    │
│ últimas  │ últimas  │ bloqueio │ últimas  │
│ 24h      │ 24h      │          │ 24h      │
└──────────┴──────────┴──────────┴──────────┘

┌─────────────────────────────────────────────────────────┐
│  ÚLTIMO EVENTO RELEVANTE                                │
│  ┌───────────────────────────────────────────────────┐  │
│  │ 🟢 Humano Validado                                │  │
│  │ IP: 192.168.1.100 | Domínio: exemplo.com         │  │
│  │ Há 2 minutos                                      │  │
│  └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  TRÁFEGO: HUMANOS VS BOTS (Últimas 24h)                 │
│  [Gráfico de linha simples]                            │
│  ────────────────────────────────────────────────────  │
│  │     ╱╲                                              │
│  │    ╱  ╲    ╱╲                                       │
│  │   ╱    ╲  ╱  ╲                                      │
│  │  ╱      ╲╱    ╲                                     │
│  └───────────────────────────────────────────────────  │
│  Humanos: ────  |  Bots: ─ ─ ─                         │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  EVENTOS RECENTES (Últimos 10)                          │
│  ┌───────────────────────────────────────────────────┐  │
│  │ 🟢 Humano Validado | 192.168.1.100 | há 2min     │  │
│  │ 🔴 Bot Bloqueado   | 10.0.0.50     | há 5min     │  │
│  │ 🟢 Acesso Permitido| 172.16.0.1    | há 8min     │  │
│  │ ...                                                │  │
│  └───────────────────────────────────────────────────┘  │
│  [Ver todos os logs →]                                  │
└─────────────────────────────────────────────────────────┘
```

### Campos e Métricas

#### Cards Principais (4 cards)
1. **Tráfego Humano**
   - Valor: Total de humanos validados
   - Mudança: % vs período anterior
   - Período: Últimas 24h
   - Cor: Verde

2. **Bots Bloqueados**
   - Valor: Total de bots bloqueados
   - Mudança: % vs período anterior
   - Período: Últimas 24h
   - Cor: Vermelho

3. **Taxa de Bloqueio**
   - Valor: % de requisições bloqueadas
   - Mudança: (opcional)
   - Período: Últimas 24h
   - Cor: Amarelo/Laranja

4. **Total de Eventos**
   - Valor: Total de eventos (humanos + bots)
   - Mudança: % vs período anterior
   - Período: Últimas 24h
   - Cor: Azul/Branco

#### Status Geral
- 🟢 **Operacional**: Tudo funcionando normalmente
- 🟡 **Atento**: Alguma atividade suspeita detectada
- 🔴 **Atenção**: Muitos bots ou atividade anormal

#### Último Evento Relevante
- Tipo: Humano Validado / Bot Bloqueado / Acesso Permitido
- IP: Endereço IP
- Domínio: Domínio afetado
- Timestamp: Tempo relativo (há X minutos)

#### Gráfico (Opcional)
- Tipo: Linha simples
- Eixo X: Horas do dia (últimas 24h)
- Eixo Y: Quantidade
- Linhas: Humanos (verde) e Bots (vermelho)
- Sem animações complexas

#### Eventos Recentes
- Lista dos últimos 10 eventos
- Formato: Tipo | IP | Tempo relativo
- Link para ver todos os logs

---

## 🚀 PRÓXIMOS PASSOS

1. **Atualizar Sidebar** (FASE 1)
2. **Refatorar Dashboard** (FASE 2)
3. **Simplificar Logs** (FASE 3)
4. **Ajustar IPs Suspeitos** (FASE 4)
5. **Focar Verificação Humana** (FASE 5)
6. **Limpar Configurações** (FASE 6)
7. **Reescrever Ajuda** (FASE 7)
8. **Limpeza Final** (FASE 8)

---

## 📝 NOTAS

- Código de features congeladas pode permanecer, mas não deve aparecer na interface
- Foco total em verificação humana
- Linguagem clara e direta
- Sem gráficos inúteis
- Métricas em tempo real
- Eventos reais apenas





