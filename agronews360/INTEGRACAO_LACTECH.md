# Integração AgroNews360 ↔ Lactech

## 🌐 Ecossistema Integrado

O AgroNews360 agora está totalmente integrado com o sistema Lactech, criando um ecossistema completo de gestão agropecuária.

## 📊 Estrutura do Banco de Dados

### Banco: `agronews`
- **Independente**: Mantém seu próprio banco de dados
- **Integrado**: Conecta-se ao banco do Lactech quando disponível
- **Flexível**: Funciona mesmo sem o Lactech instalado

## 🔗 Tabelas de Integração

### 1. `agronews_lactech_sync`
Registra todas as sincronizações entre os sistemas:
- `sync_type`: Tipo (user, animal, production, news)
- `lactech_id`: ID no banco Lactech
- `agronews_id`: ID no banco AgroNews
- `sync_status`: Status da sincronização
- `last_sync`: Última sincronização

### 2. `agronews_farm_news`
Vincula notícias a dados da fazenda:
- `article_id`: ID do artigo
- `farm_id`: ID da fazenda no Lactech
- `animal_id`: ID do animal relacionado (opcional)
- `production_id`: ID da produção relacionada (opcional)
- `related_type`: Tipo de relação

### 3. `agronews_farm_stats`
Armazena estatísticas da fazenda sincronizadas:
- `farm_id`: ID da fazenda
- `stat_date`: Data das estatísticas
- `total_animals`: Total de animais
- `total_production`: Produção total
- `daily_production`: Produção do dia
- `active_animals`: Animais ativos
- `pregnant_animals`: Animais prenhes

## 🔄 Funcionalidades de Integração

### 1. Sincronização de Usuários
Sincroniza automaticamente usuários do Lactech para o AgroNews:
- Mapeia roles automaticamente
- Mantém referência ao usuário original
- Atualiza dados quando necessário

**Endpoint**: `?action=sync_lactech_users`

### 2. Sincronização de Estatísticas
Busca estatísticas da fazenda do Lactech:
- Total de animais
- Produção diária e mensal
- Animais ativos e prenhes
- Dados atualizados diariamente

**Endpoint**: `?action=sync_lactech_stats&farm_id=1`

### 3. Notícias da Fazenda
Cria notícias relacionadas a eventos da fazenda:
- Nascimentos de animais
- Produções recordes
- Eventos de saúde
- Reproduções

**Função**: `createFarmNews()`

### 4. Consulta de Estatísticas
Retorna estatísticas mais recentes da fazenda:

**Endpoint**: `?action=get_farm_stats&farm_id=1`

## 🚀 Como Usar

### Configuração Automática
O sistema detecta automaticamente se o Lactech está instalado:
- Verifica arquivo de configuração
- Conecta ao banco do Lactech
- Sincroniza dados quando necessário

### Sincronização Manual

```php
// Sincronizar usuários
$integration = new LactechIntegration();
$result = $integration->syncUsers();

// Sincronizar estatísticas
$result = $integration->syncFarmStats(1);

// Criar notícia da fazenda
$result = $integration->createFarmNews(
    'Nova produção recorde!',
    'A fazenda atingiu uma produção diária de 5000 litros...',
    2, // category_id
    'production',
    123 // production_id
);
```

### Via API

```bash
# Sincronizar usuários
curl http://agronews360.online/api/agronews.php?action=sync_lactech_users

# Sincronizar estatísticas
curl http://agronews360.online/api/agronews.php?action=sync_lactech_stats&farm_id=1

# Obter estatísticas
curl http://agronews360.online/api/agronews.php?action=get_farm_stats&farm_id=1
```

## 📋 Mapeamento de Roles

| Lactech | AgroNews |
|---------|----------|
| admin | admin |
| gerente | admin |
| funcionario | editor |
| viewer | viewer |

## 🔒 Segurança

- Conexão segura entre bancos
- Validação de dados
- Logs de sincronização
- Tratamento de erros

## ⚙️ Configuração

O sistema detecta automaticamente a configuração do Lactech em:
```
lactech/includes/config_mysql.php
```

Se o arquivo existir, a integração é ativada automaticamente.

## 📊 Benefícios da Integração

1. **Usuários Unificados**: Login único entre sistemas
2. **Dados Compartilhados**: Estatísticas sincronizadas
3. **Notícias Contextuais**: Notícias relacionadas à fazenda
4. **Ecossistema Completo**: Gestão + Informação integradas

## 🔄 Fluxo de Sincronização

1. Sistema detecta presença do Lactech
2. Conecta ao banco do Lactech
3. Sincroniza usuários
4. Busca estatísticas
5. Cria notícias relacionadas
6. Registra sincronizações

## 📝 Notas

- A integração é opcional: o AgroNews funciona sem o Lactech
- Sincronizações são registradas para auditoria
- Dados são atualizados automaticamente
- Erros são logados mas não interrompem o sistema

