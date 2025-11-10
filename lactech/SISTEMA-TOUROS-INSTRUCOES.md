# Sistema de Touros - Instruções de Instalação e Uso

## 📋 Visão Geral

O Sistema de Touros foi criado conforme as especificações fornecidas, estruturado em cinco partes principais:

1. **Cadastro e Identificação** - Centraliza todas as informações de cada touro
2. **Controle Reprodutivo** - Gerencia coberturas naturais e inseminações
3. **Gestão de Sêmen** - Controla estoque e qualidade do material genético
4. **Histórico Sanitário** - Registra saúde e condição corporal
5. **Análises e Relatórios** - Gera indicadores de desempenho

## 🚀 Instalação

### 1. Aplicar Migração do Banco de Dados

Antes de usar o sistema, é necessário aplicar a migração SQL que expande as tabelas existentes e cria as novas necessárias:

⚠️ **IMPORTANTE**: O script usa TRANSACTION. Se houver qualquer erro, **TUDO será revertido automaticamente** (atomicidade total).

```sql
-- Execute o arquivo SQL de migração
SOURCE lactech/includes/migrations/sistema_touros_migration.sql;
```

**Ou via phpMyAdmin:**
1. Acesse o phpMyAdmin
2. Selecione o banco de dados `lactech_lgmato`
3. Clique em "SQL"
4. Copie e cole o conteúdo do arquivo `lactech/includes/migrations/sistema_touros_migration.sql`
5. Clique em "Executar"
6. **Se ocorrer erro, nada será aplicado** (rollback automático)

**Segurança do Script:**
- ✅ Usa `START TRANSACTION` - tudo ou nada
- ✅ Se houver erro, rollback automático
- ✅ Nada é aplicado parcialmente
- ✅ Verifica existência antes de criar/adicionar

### 2. Verificar Arquivos Criados

Os seguintes arquivos foram criados:

- ✅ `lactech/includes/migrations/sistema_touros_migration.sql` - Script de migração
- ✅ `lactech/api/bulls.php` - API completa do sistema
- ✅ `lactech/sistema-touros.php` - Interface principal
- ✅ `lactech/assets/js/sistema-touros.js` - JavaScript de integração

### 3. Acessar o Sistema

Acesse o sistema através da URL:
```
http://seu-servidor/lactech/sistema-touros.php
```

## 📊 Estrutura do Banco de Dados

### Tabelas Criadas/Expandidas

1. **bulls** (expandida)
   - Campos adicionais: RFID, brinco, peso, escore corporal, genealogia completa, etc.

2. **bull_coatings** (nova)
   - Registro de coberturas naturais
   - Vincula touro, vaca, resultado da cobertura

3. **bull_health_records** (nova)
   - Histórico sanitário completo
   - Vacinas, exames, tratamentos

4. **bull_body_condition** (nova)
   - Controle de peso e escore corporal ao longo do tempo

5. **bull_documents** (nova)
   - Documentos e anexos (certificados, laudos, fotos)

6. **semen_catalog** (expandida)
   - Campos adicionais: código da palheta, parâmetros de qualidade

7. **semen_movements** (nova)
   - Movimentação de sêmen (entrada, saída, uso, descarte)

8. **bull_offspring** (nova)
   - Rastreamento de descendentes

### Views Criadas

- `v_bull_statistics_complete` - Estatísticas completas por touro
- `v_bull_efficiency_ranking` - Ranking de eficiência

### Triggers Criados

- `tr_add_offspring_on_birth` - Adiciona descendentes automaticamente
- `tr_update_bull_weight_score` - Atualiza peso/escore na tabela principal
- `tr_update_semen_stock_on_use` - Atualiza estoque ao usar sêmen

## 🎯 Funcionalidades

### 1. Cadastro e Identificação

- ✅ Cadastro completo de touros
- ✅ Identificação (nome, código, brinco, RFID)
- ✅ Dados físicos (peso, escore corporal)
- ✅ Genealogia completa (pai, mãe, avós)
- ✅ Avaliação genética
- ✅ Status e origem
- ✅ Observações e anexos

### 2. Controle Reprodutivo

- ✅ Registro de coberturas naturais
- ✅ Vinculação com inseminações (já existente)
- ✅ Acompanhamento de resultados
- ✅ Cálculo de eficiência reprodutiva

### 3. Gestão de Sêmen

- ✅ Cadastro de lotes de sêmen
- ✅ Controle de validade
- ✅ Parâmetros de qualidade
- ✅ Movimentação (entrada, saída, uso)
- ✅ Alertas de vencimento

### 4. Histórico Sanitário

- ✅ Registro de vacinas
- ✅ Exames reprodutivos
- ✅ Resultados laboratoriais
- ✅ Tratamentos e medicamentos
- ✅ Controle de peso e escore corporal

### 5. Análises e Relatórios

- ✅ Estatísticas gerais
- ✅ Taxa de prenhez por touro
- ✅ Eficiência reprodutiva
- ✅ Ranking de touros
- ✅ Indicadores de sêmen
- ✅ Histórico de descendentes

## 🔌 API Endpoints

A API está disponível em `api/bulls.php` com os seguintes endpoints:

### Cadastro
- `GET ?action=list` - Lista touros (com filtros)
- `GET ?action=get&id={id}` - Busca touro específico
- `POST action=create` - Cria novo touro
- `PUT action=update` - Atualiza touro
- `DELETE action=delete&id={id}` - Remove touro (soft delete)

### Coberturas
- `GET ?action=coatings_list&bull_id={id}` - Lista coberturas
- `POST action=coating_create` - Registra cobertura
- `PUT action=coating_update` - Atualiza cobertura

### Sêmen
- `GET ?action=semen_list&bull_id={id}` - Lista sêmen
- `POST action=semen_create` - Cadastra sêmen
- `GET ?action=semen_movements&semen_id={id}` - Movimentações

### Sanitário
- `GET ?action=health_records&bull_id={id}` - Histórico sanitário
- `POST action=health_record_create` - Novo registro
- `POST action=body_condition_create` - Registro de peso/escore

### Relatórios
- `GET ?action=statistics` - Estatísticas gerais
- `GET ?action=statistics&bull_id={id}` - Estatísticas do touro
- `GET ?action=ranking&limit={n}` - Ranking de eficiência
- `GET ?action=offspring&bull_id={id}` - Descendentes
- `GET ?action=alerts` - Alertas (validade, baixa eficiência)

## 🔗 Integrações

O sistema está preparado para integrar com:

- ✅ **Módulo de Reprodução** - Já integrado via tabela `inseminations`
- ✅ **Módulo Sanitário** - Compartilha dados de saúde
- ✅ **Dashboard Analítico** - Estatísticas disponíveis via API
- ✅ **Sistema RFID** - Campo RFID no cadastro
- ⚠️ **Insights de IA** - Estrutura pronta, aguardando implementação

## 📱 Interface

### Página Principal (`sistema-touros.php`)

- **Estatísticas**: Cards com totais e indicadores
- **Filtros**: Busca, raça, status
- **Cards de Touros**: Visualização resumida de cada touro
- **Modal de Cadastro**: Formulário completo

### Funcionalidades da Interface

- ✅ Listagem com paginação
- ✅ Busca em tempo real
- ✅ Filtros por raça e status
- ✅ Cards interativos
- ✅ Modal de cadastro/edição
- ✅ Visualização de estatísticas

## 🔒 Segurança

- ✅ Autenticação obrigatória (sessão PHP)
- ✅ Verificação de `farm_id` em todas as consultas
- ✅ Sanitização de inputs
- ✅ Prepared statements (PDO)
- ✅ Soft delete para preservar histórico

## 📈 Indicadores Monitorados

- Taxa de prenhez por touro
- Número médio de coberturas por prenhez
- Taxa de aborto
- Taxa de aproveitamento de sêmen
- Número de filhos nascidos vivos
- Índice de fertilidade individual
- Desempenho médio da progênie
- Custo-benefício do reprodutor

## ⚠️ Observações Importantes

1. **Migração**: Execute o script SQL antes de usar o sistema
2. **Backup**: Faça backup do banco antes de aplicar migrações
3. **Transação**: O script usa `START TRANSACTION` - se houver erro, tudo será revertido automaticamente
4. **Rollback**: Se precisar reverter manualmente, use `sistema_touros_migration_manual_rollback.sql`
5. **Permissões**: Ajuste permissões de usuários conforme necessário
6. **Documentos**: Configure diretório `uploads/bulls/` para anexos
7. **Integração**: Alguns módulos podem precisar de ajustes para integração completa

## 🐛 Resolução de Problemas

### Erro: "Tabela não existe"
- **Solução**: Execute a migração SQL completa

### Erro: "Acesso negado"
- **Solução**: Verifique se está logado e tem permissão

### Erro: "ID inválido"
- **Solução**: Verifique se o `farm_id` está configurado corretamente na sessão

### Erro: "Campos não encontrados"
- **Solução**: Verifique se a migração foi aplicada completamente

## 📝 Próximos Passos (Opcional)

- Criar página de detalhes do touro (`sistema-touros-detalhes.php`)
- Implementar upload de documentos/fotos
- Adicionar gráficos de desempenho
- Criar relatórios exportáveis (PDF)
- Integrar com sistema RFID
- Implementar alertas automáticos por email
- Adicionar análises de IA para previsões genéticas

## 📞 Suporte

Em caso de dúvidas ou problemas, verifique:
1. Logs do servidor PHP
2. Console do navegador (F12)
3. Erros do banco de dados no phpMyAdmin

---

**Versão**: 1.0.0  
**Data**: 2025  
**Sistema**: LacTech - Sistema de Touros Completo

