# Novas Perguntas Adicionadas ao Survey

Este documento descreve as novas perguntas adicionadas ao formulário de pesquisa SafeNode para obter resultados mais ricos.

## Novas Perguntas

### 1. **Motivo da Escolha de Preço** (Opcional)
- **Campo:** `price_reason`
- **Tipo:** TEXT
- **Pergunta:** "Por que você escolheu essa faixa de preço?"
- **Objetivo:** Entender o contexto por trás da escolha de preço (valor percebido, orçamento, custo atual, etc.)

### 2. **Prioridade/Trade-off** (Obrigatório)
- **Campo:** `priority_choice`
- **Tipo:** VARCHAR(100)
- **Pergunta:** "Se tivesse que escolher, qual dessas prioridades importa mais?"
- **Opções:**
  - Redução de tempo com configuração e setup
  - Automação total de processos
  - Redução de custos
  - Facilidade de uso e simplicidade
- **Objetivo:** Entender o que pesa mais na decisão de compra

### 3. **NPS - Net Promoter Score** (Obrigatório)
- **Campo:** `nps_score`
- **Tipo:** INT(2)
- **Pergunta:** "De 0 a 10, o quanto você recomendaria uma solução como a SafeNode?"
- **Formato:** Slider de 0 a 10
- **Objetivo:** Medir predisposição do usuário a recomendar (métrica padrão de produto)

### 4. **Ferramentas Atuais de Infra/Deploy** (Opcional)
- **Campo:** `current_tools`
- **Tipo:** TEXT
- **Pergunta:** "Quais ferramentas você usa hoje para infra e deploy?"
- **Objetivo:** Mapear stacks competidoras e entender onde a SafeNode pode entrar rapidamente

## Como Aplicar

1. Execute o script SQL para adicionar as novas colunas ao banco de dados:
   ```sql
   -- Execute: safenode/database/survey-add-new-questions.sql
   ```

2. O formulário `survey.php` já foi atualizado com as novas perguntas

3. O código PHP de processamento já foi atualizado para salvar os novos campos

## Estrutura das Seções

As novas perguntas foram organizadas em seções:

- **Seção 4 (PREÇO):** Inclui agora também o campo "Motivo da escolha de preço"
- **Nova Seção (PRIORIDADE):** Pergunta sobre prioridades/trade-off
- **Nova Seção (SATISFAÇÃO):** Pergunta NPS
- **Nova Seção (FERRAMENTAS ATUAIS):** Pergunta sobre ferramentas que usa hoje

## Validações

- `priority_choice`: Obrigatório (campo radio)
- `nps_score`: Obrigatório, deve estar entre 0 e 10
- `price_reason`: Opcional (textarea)
- `current_tools`: Opcional (textarea)

