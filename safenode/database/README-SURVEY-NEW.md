# Atualização do Formulário de Pesquisa - Novas Perguntas

## O que foi alterado

O formulário de pesquisa (`survey.php`) foi completamente reescrito com perguntas detalhadas para validação de produto.

## Estrutura das novas perguntas

### 1. PERFIL DO DEV (segmentação)
- Email (obrigatório)
- Nível como dev: Estudante, Júnior, Pleno, Sênior, Tech Lead/Arquiteto, Fundador
- Tipo de trabalho: Solo/Freelancer, Startup, Empresa média, Empresa grande
- Stack principal: JavaScript/TypeScript, Node.js, PHP, Python, Java, Go, Outra

### 2. DOR REAL (ESSA É OURO)
- Pontos de dor (até 3 seleções): Infraestrutura, Deploy, Configuração de ambientes, E-mails transacionais, Monitoramento, Custos cloud, Documentação ruim, Debug em produção, Organização do projeto, Comunicação entre serviços
- Tempo perdido por semana: Menos de 1h, 1-3h, 3-5h, Mais de 5h

### 3. SAFE NODE (VALIDAÇÃO DE IDEIA)
- Plataforma ajudaria: Muito, Um pouco, Não vejo valor
- Primeira feature: IDE com IA, Infra visual, Automação, E-mail transacional, Monitoramento, Templates
- IA de análise: Sim com certeza, Talvez, Não confio

### 4. PREÇO (SEM MEDO)
- Faixa de preço: US$ 10-20, US$ 20-50, US$ 50-100, US$ 100+, Só usaria se fosse free

### 5. USO PROFISSIONAL
- Usaria em produção: Sim, Talvez, Não
- Indicaria para time: Sim, Não
- Quem decide: Eu, Meu time, A empresa, Diretoria/Arquitetura

### 6. FECHAMENTO (INSIGHT BRUTO)
- O que faria trocar a stack atual (texto livre)
- O que não pode faltar (texto livre)

## Script SQL necessário

Execute o arquivo `survey-new-questions.sql` para adicionar os novos campos ao banco de dados:

```bash
# No phpMyAdmin ou linha de comando
mysql -u u311882628_Kron -p u311882628_safend < survey-new-questions.sql
```

**Nota:** Se você receber erros dizendo que as colunas já existem, você pode:
1. Ignorar esses erros específicos (as colunas já estão criadas)
2. Ou usar a versão segura `survey-new-questions-safe.sql` que trata melhor os erros

Para verificar se os campos foram adicionados:
```sql
DESCRIBE safenode_survey_responses;
```

## Campos do banco de dados

Os seguintes campos foram adicionados:
- `dev_level` - Nível do desenvolvedor
- `work_type` - Tipo de trabalho
- `main_stack` - Stack principal
- `main_stack_other` - Outra stack (se selecionado "Outra")
- `pain_points` - JSON com pontos de dor selecionados
- `time_wasted_per_week` - Tempo perdido por semana
- `platform_help` - Se a plataforma ajudaria
- `first_feature` - Primeira feature que usaria
- `use_ai_analysis` - Se usaria IA de análise
- `price_willing` - Faixa de preço
- `use_in_production` - Se usaria em produção
- `recommend_to_team` - Se indicaria para time
- `decision_maker` - Quem decide
- `switch_reasons` - Motivos para trocar stack
- `must_have_features` - O que não pode faltar

## Notas importantes

- Os campos antigos (uses_hosting, biggest_pain, etc) foram mantidos no banco para compatibilidade
- O campo `pain_points` é armazenado como JSON
- Todas as perguntas obrigatórias estão marcadas com asterisco vermelho
- O formulário tem validação JavaScript para limitar seleção de pontos de dor a 3
- O campo "Outra" stack aparece dinamicamente quando selecionado

