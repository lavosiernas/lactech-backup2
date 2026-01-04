# Sistema de Inteligência de Alimentação - Documentação

## 📋 Visão Geral

Este sistema adiciona uma camada de **inteligência** sobre o módulo de alimentação existente, transformando-o de um "diário de alimentação" em um "sistema de manejo alimentar".

## 🎯 Funcionalidades Implementadas

### 1. **Estrutura de Banco de Dados**

Criadas as seguintes tabelas:

- **`animal_weights`**: Histórico de pesos dos animais (real, estimado, calculado)
- **`feed_compositions`**: Composição nutricional dos alimentos (MS, proteína, etc)
- **`nutritional_parameters`**: Parâmetros nutricionais por categoria (consumo MS, proteína)
- **`ideal_feed_calculations`**: Cálculos de alimentação ideal realizados
- **`feed_comparisons`**: Comparações entre alimentação real vs ideal

Modificações na tabela existente:

- **`feed_records`**: Adicionados campos `group_id`, `record_type`, `animal_count` para suportar registros coletivos (lotes)

### 2. **Classe PHP: `FeedingIntelligence.class.php`**

Classe principal que contém a lógica de cálculo e comparação:

**Métodos principais:**
- `getAnimalWeight($animal_id)`: Obtém peso mais recente do animal (real ou estimado)
- `getGroupAverageWeight($group_id)`: Obtém peso médio de um grupo
- `calculateIdealFeedForAnimal($animal_id, $date)`: Calcula alimentação ideal para animal individual
- `calculateIdealFeedForGroup($group_id, $date)`: Calcula alimentação ideal para grupo/lote
- `compareRealVsIdeal($feed_record_id)`: Compara registro real com ideal e gera alertas

**Lógica de Cálculo:**
- Usa peso do animal (real ou estimado)
- Aplica parâmetros nutricionais por categoria
- Calcula MS (Matéria Seca) ideal baseado em % do peso vivo
- Distribui entre concentrado, volumoso, silagem e feno
- Compara com valores reais e gera status (ok, abaixo, acima, alerta)

### 3. **API: `api/feed_intelligence.php`**

Endpoints disponíveis:

- `GET/POST ?action=calculate_ideal_animal&animal_id=X`: Calcular ideal para animal
- `GET/POST ?action=calculate_ideal_group&group_id=X`: Calcular ideal para grupo
- `GET/POST ?action=compare&feed_record_id=X`: Comparar real vs ideal
- `GET/POST ?action=get_animal_weight&animal_id=X`: Obter peso do animal
- `GET/POST ?action=get_group_average_weight&group_id=X`: Obter peso médio do grupo

## 🚀 Como Usar

### Passo 1: Executar Migration SQL

Execute o arquivo SQL de migration:
```sql
lactech/includes/migrations/create_feeding_intelligence_tables.sql
```

**IMPORTANTE**: O SQL inclui comandos ALTER TABLE que devem ser executados manualmente ou com verificação prévia se as colunas já existem.

### Passo 2: Integrar na API de Alimentação Existente

Modificar `api/feed.php` para chamar comparação automática após criar registro:

```php
// Após salvar feed_records, chamar comparação
require_once __DIR__ . '/../includes/FeedingIntelligence.class.php';
$fi = new FeedingIntelligence($farm_id);
$comparison = $fi->compareRealVsIdeal($newFeedRecordId);
```

### Passo 3: Integrar na Interface

Adicionar card de "Situação Nutricional" na página de alimentação para mostrar:
- Consumo ideal do dia
- Consumo real
- Status (ok/abaixo/acima)
- Alertas e sugestões

## 📊 Fluxo de Funcionamento

1. **Registro Real** (já existe):
   - Usuário registra alimentação em `feed_records`
   - Pode ser individual (animal_id) ou coletivo (group_id)

2. **Cálculo Ideal** (novo):
   - Sistema busca peso do animal/grupo
   - Aplica parâmetros nutricionais da categoria
   - Calcula MS ideal (% do peso vivo)
   - Distribui entre tipos de alimento
   - Salva em `ideal_feed_calculations`

3. **Comparação** (novo):
   - Sistema compara real vs ideal
   - Calcula diferenças em kg e %
   - Converte valores reais para MS (usando % MS padrão)
   - Determina status e gera alertas
   - Salva em `feed_comparisons`

4. **Interface** (pendente):
   - Mostra card com situação nutricional
   - Exibe comparações nos registros
   - Gera alertas e sugestões

## 🔧 Parâmetros Configuráveis

### Parâmetros Nutricionais (tabela `nutritional_parameters`)

Valores padrão:
- **Lactante**: 3,5% do PV em MS, 16% proteína
- **Seco**: 2% do PV em MS, 12% proteína
- **Novilha**: 2,5% do PV em MS, 14% proteína
- **Bezerra**: 3% do PV em MS, 18% proteína
- **Touro**: 2% do PV em MS, 12% proteína

### Composição de Alimentos (tabela `feed_compositions`)

Valores padrão de MS:
- **Concentrado**: 88% MS
- **Volumoso**: 25% MS
- **Silagem**: 35% MS
- **Feno**: 85% MS

### Distribuição Padrão de Alimentos

- 60% Concentrado
- 25% Volumoso
- 15% Silagem
- 0% Feno

*(Pode ser ajustado conforme necessidade)*

## ⚠️ Próximos Passos

1. **Interface Frontend**: Adicionar card de Situação Nutricional
2. **Integração**: Conectar comparação automática após registro
3. **Alertas**: Implementar sistema de notificações
4. **Pesos**: Criar interface para registrar pesos dos animais
5. **Ajustes**: Refinar cálculos e parâmetros conforme feedback

## 📝 Notas Importantes

- O sistema **NÃO quebra** o funcionamento existente
- Registros individuais continuam funcionando normalmente
- Registros coletivos (lotes) são uma funcionalidade nova e opcional
- Pesos podem ser estimados automaticamente se não houver registro real
- O sistema funciona mesmo com dados incompletos (peso estimado, parâmetros padrão)



