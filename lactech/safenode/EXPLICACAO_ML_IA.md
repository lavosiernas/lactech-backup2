# 🧠 Inteligência e Machine Learning no SafeNode

## 📚 Visão Geral

O SafeNode implementa **3 sistemas inteligentes** que trabalham juntos para:
- **Aprender** com dados históricos
- **Prever** ataques futuros
- **Detectar** comportamentos anormais
- **Ajustar** automaticamente os scores de ameaça

---

## 1️⃣ Sistema de Scoring Adaptativo com ML

### 🎯 O que faz?
Ajusta automaticamente o **score de ameaça** baseado em múltiplos fatores, não apenas um valor fixo.

### 🔧 Como Funciona?

#### **Entrada (Features):**
O sistema recebe 5 fatores para analisar:
```php
$features = [
    'threat_score' => 65,        // Score inicial da ameaça (0-100)
    'confidence_score' => 80,     // Confiança na detecção (0-100)
    'ip_reputation' => 30,        // Reputação do IP (0-100, baixo = ruim)
    'behavior_score' => 70,       // Score comportamental (0-100)
    'time_pattern_score' => 50,   // Padrão de horário (0-100)
    'ip_address' => '192.168.1.1',
    'threat_type' => 'sql_injection'
];
```

#### **Processo de Cálculo:**

**1. Normalização (0-100)**
Cada fator é normalizado para escala 0-100:
- `threat_score: 65` → `65`
- `confidence_score: 80` → `80`
- `ip_reputation: 30` → `70` (invertido: reputação baixa = score alto)
- `behavior_score: 70` → `70`
- `time_pattern_score: 50` → `50`

**2. Cálculo Ponderado**
Usa pesos configuráveis (como um modelo ML):
```php
Pesos padrão:
- threat_score: 35% (mais importante)
- confidence_score: 25%
- ip_reputation: 20%
- behavior_pattern: 15%
- time_pattern: 5%

Score = (65 × 0.35) + (80 × 0.25) + (70 × 0.20) + (70 × 0.15) + (50 × 0.05)
Score = 22.75 + 20 + 14 + 10.5 + 2.5
Score = 69.75
```

**3. Ajuste Histórico**
Verifica histórico do IP:
- Se IP teve 5 ataques bloqueados nos últimos 7 dias → **+25% no score**
- Se tipo de ameaça está frequente (10+ em 1h) → **+10% no score**

```php
// Exemplo:
Score base: 69.75
Ajuste histórico: +25% (IP com histórico)
Score final: 69.75 × 1.25 = 87.19
```

**4. Probabilidade de Ataque**
Converte score em probabilidade usando função sigmoide:
```php
// Score 87 → probabilidade ~0.90 (90% de chance de ser ataque)
// Score 50 → probabilidade ~0.50 (50% de chance)
// Score 20 → probabilidade ~0.20 (20% de chance)
```

#### **Saída:**
```php
[
    'adaptive_score' => 87.19,      // Score final ajustado
    'base_threat_score' => 65,      // Score original
    'probability' => 0.90,           // 90% probabilidade de ataque
    'is_attack' => true,             // Probabilidade >= 70%
    'confidence' => 80,
    'factors' => [
        'threat_score' => 65,
        'confidence' => 80,
        'ip_reputation' => 70,
        'behavior' => 70,
        'time_pattern' => 50,
        'historical_adjustment' => 25  // +25% do histórico
    ]
]
```

### 🎓 Treinamento do Modelo

O sistema pode **treinar automaticamente** com dados históricos:

```php
$mlScoring = new MLScoringSystem($db);
$result = $mlScoring->trainModel(30); // 30 dias de dados
```

**O que acontece:**
1. Analisa 10.000 registros históricos
2. Calcula **taxa de acerto** (true positives / total)
3. Calcula **taxa de falsos positivos**
4. Se muitos falsos positivos → **ajusta pesos**:
   - Aumenta peso de `confidence_score`
   - Diminui peso de `threat_score`

**Resultado:**
```php
[
    'accuracy' => 0.85,              // 85% de precisão
    'false_positive_rate' => 0.15,   // 15% falsos positivos
    'weights' => [
        'threat_score' => 0.30,      // Ajustado
        'confidence_score' => 0.30   // Aumentado
        // ...
    ]
]
```

---

## 2️⃣ Detecção de Anomalias Comportamentais

### 🎯 O que faz?
Detecta quando um IP está se comportando de forma **anormal** comparado ao seu padrão histórico.

### 🔧 Como Funciona?

#### **Passo 1: Estabelecer Baseline (Padrão Normal)**

Para cada IP, o sistema analisa **últimos 30 dias** e calcula:
```php
Baseline do IP 192.168.1.1:
- Média de requisições/hora: 10
- Desvio padrão: 5
- Horário médio de acesso: 14h (2 PM)
- Desvio padrão de horário: 4h
- Diversidade de endpoints: 0.1 (acessa poucos endpoints)
- País mais comum: BR
- User-Agents únicos: 1 (sempre o mesmo)
```

#### **Passo 2: Medir Comportamento Atual**

Compara com **última hora**:
```php
Comportamento atual:
- Requisições/hora: 50 (normalmente 10!)
- Horário: 3h da manhã (normalmente 14h!)
- Endpoints acessados: 20 diferentes (normalmente 2!)
- País: RU (era BR!)
- User-Agent: 3 diferentes (normalmente 1!)
```

#### **Passo 3: Calcular Z-Score**

Z-Score mede quantos desvios padrão acima/abaixo do normal:
```php
// Requisições
Z-score = (50 - 10) / 5 = 8.0
// Isso significa 8 desvios padrão acima! MUITO anormal.

// Horário
Z-score = (3 - 14) / 4 = -2.75
// Horário muito diferente do normal.
```

**Interpretação:**
- `|Z-score| < 1` → Normal
- `|Z-score| = 1-2` → Ligeiramente anormal
- `|Z-score| = 2-3` → Anormal ⚠️
- `|Z-score| > 3` → MUITO anormal 🚨

#### **Passo 4: Detectar Anomalias**

Sistema detecta 5 tipos de anomalias:

**1. Taxa de Requisições Anormal**
```php
Se Z-score > 2:
    Anomalia detectada!
    Severidade: alta (se Z > 3) ou média (se Z = 2-3)
    Score: +80 pontos
```

**2. Horário Incomum**
```php
Se Z-score de horário > 2:
    "Acesso em horário incomum"
    Score: +25 pontos
```

**3. Padrão de Endpoints Diferente**
```php
Se diversidade mudou > 0.5:
    "Padrão de acesso muito diferente"
    Score: +40 pontos
```

**4. User-Agent Mudou**
```php
Se user-agents aumentaram > 1.5x:
    "User-Agent mudou do padrão"
    Score: +10 pontos
```

**5. País Diferente**
```php
Se país mudou:
    "Acesso de país diferente do normal"
    Score: +30 pontos (ALTA SEVERIDADE!)
```

#### **Saída:**
```php
[
    'is_anomaly' => true,           // Anomalia detectada
    'anomaly_score' => 85,          // Score total de anomalia
    'anomalies' => [
        [
            'type' => 'unusual_request_rate',
            'severity' => 'high',
            'z_score' => 8.0,
            'description' => "Taxa de requisições muito acima do normal"
        ],
        [
            'type' => 'country_change',
            'severity' => 'high',
            'description' => "Acesso de país diferente do normal"
        ]
    ],
    'baseline' => [...],            // Baseline histórico
    'current' => [...],             // Comportamento atual
    'z_scores' => [...]
]
```

### 💡 Exemplo Real:

**Cenário:** IP que normalmente acessa 10x/dia durante horário comercial, de repente acessa 100x/hora às 3h da manhã de um país diferente.

**Resultado:** 
- Anomalia detectada com score 95
- Sistema pode bloquear ou aplicar challenge adicional

---

## 3️⃣ Predição de Ataques (Early Warning System)

### 🎯 O que faz?
**Prevê** quando um ataque vai acontecer antes que aconteça, baseado em padrões históricos.

### 🔧 Como Funciona?

O sistema executa **5 análises preditivas**:

#### **1. Detecção de Pico de Ataques (Spike Detection)**

Compara últimas 2 horas vs 2 horas anteriores:
```php
Últimas 2h: 50 ataques bloqueados
2h anteriores: 10 ataques bloqueados
Aumento: (50-10)/10 × 100 = 400%
```

**Se aumento ≥ 100%:**
```php
Alerta gerado:
{
    'type' => 'attack_spike',
    'severity' => 'high',  // Se ≥ 200%
    'message' => "Ataques aumentaram 400% nas últimas 2 horas",
    'recommendation' => "Aumentar nível de segurança e monitorar de perto"
}
```

#### **2. Padrão DDoS**

Detecta padrão similar a DDoS:
```php
Em 1 minuto:
- 100+ requisições
- 20+ IPs diferentes
- Todos com threat_score similar

Alerta:
{
    'type' => 'ddos_pattern',
    'severity' => 'high',
    'message' => "Padrão similar a ataque DDoS detectado",
    'recommendation' => "Ativar modo 'Under Attack'"
}
```

#### **3. Horário de Pico**

Analisa histórico de 7 dias para identificar horários de pico:
```php
Histórico mostra:
- Horário de pico: 22h (10 PM)
- Média de 50 ataques neste horário
- Hora atual: 21h (1h antes do pico)

Alerta:
{
    'type' => 'peak_time_warning',
    'message' => "Horário de pico de ataques detectado (hora 22)",
    'recommendation' => "Aumentar vigilância durante este horário"
}
```

#### **4. Correlação com Eventos Externos**

Detecta se tipo específico de ataque aumentou (pode indicar vulnerabilidade divulgada):
```php
SQL Injection:
- Normalmente: 5% dos ataques
- Atual: 35% dos ataques (aumento de 600%!)

Alerta:
{
    'type' => 'external_event_correlation',
    'message' => "Ataques de SQL injection aumentaram significativamente",
    'recommendation' => "Verificar se há vulnerabilidades conhecidas divulgadas"
}
```

#### **5. Tendência de Aumento**

Usa **regressão linear** para detectar tendência:
```php
Últimas 6 horas:
Hora 1: 10 ataques
Hora 2: 15 ataques
Hora 3: 20 ataques
Hora 4: 25 ataques
Hora 5: 30 ataques
Hora 6: 35 ataques

Tendência: +5 ataques/hora (crescimento constante)

Alerta:
{
    'type' => 'increasing_trend',
    'message' => "Tendência de aumento de ataques detectada",
    'trend' => 5.0,
    'recommendation' => "Preparar defesas para possível aumento"
}
```

### 📊 Como Usar:

```php
$predictor = new AttackPredictor($db);

// Gerar alertas preditivos
$alerts = $predictor->generatePredictiveAlerts(24); // Últimas 24h

// Resultado:
[
    {
        'type' => 'attack_spike',
        'severity' => 'high',
        'message' => 'Ataques aumentaram 400% nas últimas 2 horas',
        'recommendation' => 'Aumentar nível de segurança'
    },
    {
        'type' => 'increasing_trend',
        'severity' => 'medium',
        'message' => 'Tendência de aumento detectada',
        'recommendation' => 'Preparar defesas'
    }
]
```

---

## 🔄 Como os Sistemas Trabalham Juntos

### Fluxo Completo:

```
1. Requisição chega
   ↓
2. AnomalyDetector analisa comportamento
   → Detecta anomalia? → behavior_score aumenta
   ↓
3. MLScoringSystem calcula score adaptativo
   → Usa behavior_score + histórico + outros fatores
   → Retorna: adaptive_score = 87, probability = 0.90
   ↓
4. Sistema decide:
   - probability >= 0.7 → BLOQUEAR
   - probability 0.5-0.7 → CHALLENGE
   - probability < 0.5 → PERMITIR
   ↓
5. AttackPredictor analisa padrões gerais
   → Se detectar spike/trend → GERA ALERTA
   → Recomenda aumentar segurança
```

### Exemplo Completo:

**Cenário:** IP desconhecido faz requisição suspeita

1. **AnomalyDetector:**
   - IP novo → Sem baseline → Usa padrão genérico
   - Requisição às 3h da manhã → Z-score horário = -2.5
   - **Resultado:** Anomalia detectada, score = 30

2. **MLScoringSystem:**
   - `threat_score`: 65 (SQL injection detectado)
   - `confidence_score`: 80 (alto)
   - `behavior_score`: 30 (anomalia)
   - `ip_reputation`: 50 (desconhecido)
   - `historical_adjustment`: 0 (IP novo)
   - **Cálculo:**
     ```
     Score = (65×0.35) + (80×0.25) + (50×0.20) + (30×0.15) + (50×0.05)
     Score = 58.5
     Probabilidade = 0.65 (65% de chance de ataque)
     ```
   - **Resultado:** `is_attack = false`, mas próximo do threshold

3. **Sistema decide:**
   - Probabilidade 0.65 → Aplica **CHALLENGE** (não bloqueia ainda)
   - Usuário resolve challenge → Permite
   - Se falhar challenge → Bloqueia

4. **AttackPredictor:**
   - Se vários IPs similares começarem ataques → Detecta **SPIKE**
   - Gera alerta: "Aumento de 200% em ataques SQL injection"
   - Recomenda aumentar nível de segurança

---

## 📈 Melhorias Futuras (Pode ser expandido)

### Para Produção:

1. **Biblioteca ML Real:**
   - Substituir modelo simples por **TensorFlow** ou **scikit-learn**
   - Neural Network para scoring
   - Random Forest para classificação

2. **Mais Features:**
   - Geolocalização precisa
   - Padrões de navegação
   - Análise de tráfego de rede
   - Integração com Threat Intelligence feeds

3. **Aprendizado Contínuo:**
   - Retreinar modelo automaticamente semanalmente
   - Feedback loop: aprender com decisões corretas/incorretas
   - A/B testing de diferentes pesos

---

## 🎯 Resumo

| Sistema | Entrada | Processo | Saída |
|---------|---------|----------|-------|
| **ML Scoring** | 5 features + histórico | Cálculo ponderado + ajuste histórico | Score adaptativo (0-100) + probabilidade |
| **Anomaly Detection** | Baseline vs atual | Z-score + desvios | Score de anomalia (0-100) + lista de anomalias |
| **Attack Predictor** | Padrões históricos | Análise de tendências | Alertas preditivos + recomendações |

**Juntos:** Sistema inteligente que **aprende**, **prevê** e **adapta** automaticamente! 🧠✨



