# Tabela de Dados Hardcode - Página do Gerente

## Funções com Dados Hardcode

| # | Função | Localização | Tipo de Hardcode | Valor Hardcode | Status | Arquivo |
|---|--------|-------------|------------------|----------------|--------|---------|
| 1 | **Gestão Sanitária - Mastite** | Modal Saúde | ID Animal | `'123'` | ❌ Hardcode | `modalmore.php:1080` |
| 2 | **Gestão Sanitária - Vacinação** | Modal Saúde | Nome Vacina | `'aftosa'` | ❌ Hardcode | `modalmore.php:1062` |
| 3 | **Gestão Sanitária - Medicamento** | Modal Saúde | Nome Medicamento | `'penicilina'` | ❌ Hardcode | `modalmore.php:1086` |
| 4 | **Reprodução - Parto Iminente** | Modal Reprodução | ID Animal | `'123'` | ❌ Hardcode | `modalmore.php:1263` |
| 5 | **Reprodução - Exame Prenhez** | Modal Reprodução | ID Animal | `'456'` | ❌ Hardcode | `modalmore.php:1275` |
| 6 | **Reprodução - Retorno ao Cio** | Modal Reprodução | ID Animal | `'789'` | ❌ Hardcode | `modalmore.php:1287` |
| 7 | **Central de Ações - Vacinação** | Modal Ações | Nome Vacina | `'aftosa'` | ❌ Hardcode | `modalmore.php:1526` |
| 8 | **Telefone Usuário** | Gerente Completo | Telefone | `'(11) 99999-9999'` | ✅ Corrigido | `gerente-completo.php:119,125,148` |
| 9 | **Endereço Fazenda** | Gerente Completo | Endereço | `'Justiniano de Serpa...'` | ✅ Corrigido | `gerente-completo.php:137,143,152` |
| 10 | **Nome Fazenda** | Gerente Completo | Nome | `'Lagoa Do Mato'` | ⚠️ Parcial | `gerente-completo.php:133,140,149` |
| 11 | **Relatórios - Produção** | Modal Relatórios | Dados | Variáveis PHP comentadas | ❌ Hardcode | `modalmore.php:464-612` |
| 12 | **Dashboard - Estatísticas** | Modal Dashboard | Dados | Estrutura sem dados | ❌ Hardcode | `modalmore.php:1348+` |
| 13 | **Gestão Rebanho - Pedigree** | Função JS | ID Animal | Parâmetro da função | ⚠️ Placeholder | `modalmore.php:3210` |
| 14 | **Gestão Rebanho - Visualizar** | Função JS | ID Animal | Parâmetro da função | ⚠️ Placeholder | `modalmore.php:3235` |
| 15 | **Reprodução - Preparar Parto** | Função JS | ID Animal | Parâmetro da função | ⚠️ Placeholder | `modalmore.php:3376` |
| 16 | **Reprodução - Teste Prenhez** | Função JS | ID Animal | Parâmetro da função | ⚠️ Placeholder | `modalmore.php:3381` |
| 17 | **Reprodução - Monitorar Cio** | Função JS | ID Animal | Parâmetro da função | ⚠️ Placeholder | `modalmore.php:3386` |
| 18 | **Saúde - Tratar Mastite** | Função JS | ID Animal | Parâmetro da função | ⚠️ Placeholder | `modalmore.php:3399` |
| 19 | **Saúde - Agendar Vacinação** | Função JS | Nome Vacina | Parâmetro da função | ⚠️ Placeholder | `modalmore.php:3404` |
| 20 | **Saúde - Repor Medicamento** | Função JS | Nome Medicamento | Parâmetro da função | ⚠️ Placeholder | `modalmore.php:3409` |

---

## Detalhamento por Categoria

### 🔴 **CRÍTICO - Dados Hardcode em HTML**

#### Gestão Sanitária
| Função | Linha | Código | Problema |
|--------|-------|--------|----------|
| Tratar Mastite | 1080 | `onclick="treatMastitis('123')"` | ID animal hardcode |
| Agendar Vacinação | 1062 | `onclick="scheduleVaccination('aftosa')"` | Nome vacina hardcode |
| Repor Medicamento | 1086 | `onclick="reorderMedicine('penicilina')"` | Nome medicamento hardcode |

#### Reprodução
| Função | Linha | Código | Problema |
|--------|-------|--------|----------|
| Preparar Parto | 1263 | `onclick="prepareForBirth('123')"` | ID animal hardcode |
| Agendar Teste Prenhez | 1275 | `onclick="schedulePregnancyTest('456')"` | ID animal hardcode |
| Monitorar Cio | 1287 | `onclick="monitorEstrus('789')"` | ID animal hardcode |

#### Central de Ações
| Função | Linha | Código | Problema |
|--------|-------|--------|----------|
| Agendar Vacinação | 1526 | `onclick="scheduleVaccination('aftosa')"` | Nome vacina hardcode |

---

### 🟡 **MÉDIO - Funções com Apenas Alertas**

| Função | Arquivo | Linha | Problema |
|--------|---------|-------|----------|
| `treatMastitis(animalId)` | `modalmore.php` | 3399 | Apenas mostra `alert()` |
| `prepareForBirth(animalId)` | `modalmore.php` | 3376 | Apenas mostra `alert()` |
| `schedulePregnancyTest(animalId)` | `modalmore.php` | 3381 | Apenas mostra `alert()` |
| `monitorEstrus(animalId)` | `modalmore.php` | 3386 | Apenas mostra `alert()` |
| `scheduleVaccination(vaccine)` | `modalmore.php` | 3404 | Apenas mostra `alert()` |
| `reorderMedicine(medicine)` | `modalmore.php` | 3409 | Apenas mostra `alert()` |
| `showPedigreeModal(animalId)` | `modalmore.php` | 3210 | Apenas mostra `alert()` |
| `viewAnimalModal(animalId)` | `modalmore.php` | 3235 | Apenas mostra `alert()` |

---

### 🟢 **CORRIGIDO - Dados Hardcode Removidos**

| Item | Arquivo | Linha | Status |
|------|---------|-------|--------|
| Telefone Usuário | `gerente-completo.php` | 119,125,148 | ✅ Removido hardcode |
| Endereço Fazenda | `gerente-completo.php` | 137,143,152 | ✅ Removido hardcode |
| Variável `$farmId` | `api/manager.php` | 181,184,187 | ✅ Corrigido |

---

## Resumo Estatístico

### Por Status
- 🔴 **Crítico (Hardcode em HTML)**: 7 ocorrências
- 🟡 **Médio (Apenas Alertas)**: 8 ocorrências
- 🟢 **Corrigido**: 3 ocorrências
- ⚠️ **Parcial**: 2 ocorrências

### Por Tipo de Dado
- **IDs de Animais Hardcode**: 5 ocorrências
- **Nomes de Vacinas Hardcode**: 2 ocorrências
- **Nomes de Medicamentos Hardcode**: 1 ocorrência
- **Telefones Hardcode**: 3 ocorrências (corrigidas)
- **Endereços Hardcode**: 3 ocorrências (corrigidas)

### Por Arquivo
- `modalmore.php`: 20 ocorrências
- `gerente-completo.php`: 6 ocorrências (3 corrigidas)
- `api/manager.php`: 3 ocorrências (corrigidas)

---

## Ações Necessárias

### Prioridade ALTA 🔴
1. Remover IDs hardcode ('123', '456', '789') do HTML
2. Implementar carregamento dinâmico de alertas do banco
3. Substituir nomes hardcode ('aftosa', 'penicilina') por dados reais

### Prioridade MÉDIA 🟡
4. Implementar funções reais em vez de `alert()`
5. Conectar funções com APIs existentes
6. Criar modais completos para visualização

### Prioridade BAIXA 🟢
7. Remover código comentado com dados hardcode
8. Limpar placeholders não utilizados

---

## Exemplos de Código com Hardcode

### ❌ ANTES (Com Hardcode)
```html
<button onclick="treatMastitis('123')" class="...">
    Tratar
</button>
```

### ✅ DEPOIS (Dinâmico)
```html
<div id="mastitis-alerts-container">
    <!-- Carregado dinamicamente via JavaScript -->
</div>
```

```javascript
// Carregar alertas reais do banco
async function loadMastitisAlerts() {
    const response = await fetch('api/health.php?action=get_mastitis_alerts');
    const data = await response.json();
    // Renderizar alertas dinamicamente
}
```

---

## Arquivos que Precisam de Correção

1. ✅ `lactech/api/manager.php` - **CORRIGIDO**
2. ✅ `lactech/gerente-completo.php` - **CORRIGIDO** (telefone/endereço)
3. ❌ `lactech/includes/modalmore.php` - **PRECISA CORREÇÃO** (IDs hardcode)
4. ❌ `lactech/includes/modalmore.php` - **PRECISA CORREÇÃO** (funções com alertas)

---

**Última atualização**: 2025-01-27
**Total de ocorrências**: 29
**Corrigidas**: 3
**Pendentes**: 26










