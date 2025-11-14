# Integração com APIs Externas - AgroNews360

## 📡 APIs Implementadas

### 1. **Notícias do Agronegócio**
- **Fonte**: Feeds RSS gratuitos
  - Canal Rural
  - Globo Rural
  - Notícias Agrícolas
  - AgroLink
- **Funcionalidade**: Busca automaticamente notícias e as categoriza
- **Frequência**: A cada 6 horas (via cron) ou manual

### 2. **Dados Climáticos**
- **API**: OpenWeatherMap (opcional, requer chave)
- **Fallback**: Dados simulados para principais regiões
- **Cidades**: São Paulo, Brasília, Curitiba, Porto Alegre
- **Dados**: Temperatura, umidade, condições climáticas

### 3. **Cotações de Produtos**
- **Produtos**: Milho, Soja, Leite, Boi Gordo, Café
- **Fonte**: Dados simulados baseados em valores de mercado
- **Atualização**: Diária
- **Nota**: Para dados reais, é necessário API paga (CEPEA, etc.)

### 4. **Taxa de Câmbio**
- **API**: ExchangeRate-API (gratuita)
- **Moeda**: USD/BRL
- **Atualização**: Automática

## 🔧 Configuração

### 1. Chaves de API (Opcional)

Edite `agronews360/api/external_apis.php`:

```php
// Para notícias (NewsAPI ou GNews)
define('NEWS_API_KEY', 'SUA_CHAVE_AQUI');

// Para clima (OpenWeatherMap)
define('OPENWEATHER_API_KEY', 'SUA_CHAVE_AQUI');
```

### 2. Obter Chaves

- **NewsAPI**: https://newsapi.org (gratuito limitado)
- **GNews**: https://gnews.io (gratuito limitado)
- **OpenWeatherMap**: https://openweathermap.org/api (gratuito limitado)

## 🚀 Uso

### Sincronização Manual

Via navegador:
```
http://seu-dominio.com/api/agronews.php?action=sync_data
```

Via JavaScript:
```javascript
fetch('api/agronews.php?action=sync_data', { method: 'POST' })
  .then(r => r.json())
  .then(data => console.log(data));
```

### Sincronização Automática (Cron)

Edite o arquivo `cron/sync_data.php` e configure um cron job:

```bash
# Executar a cada 6 horas
0 */6 * * * /usr/bin/php /caminho/para/agronews360/cron/sync_data.php
```

### Endpoints Disponíveis

1. **Sincronizar tudo**: `?action=sync_data` (POST)
2. **Buscar notícias**: `?action=fetch_news&limit=10`
3. **Buscar clima**: `?action=fetch_weather&city=São Paulo&state=SP`
4. **Buscar cotações**: `?action=fetch_quotations`

## 📊 Dados Sincronizados

### Notícias
- Título, resumo, conteúdo completo
- Imagem de destaque (quando disponível)
- Categoria automática
- Link para fonte original

### Clima
- Temperatura atual, mínima e máxima
- Umidade
- Condições climáticas
- Região

### Cotações
- Nome do produto
- Preço atual
- Variação percentual
- Tipo de variação (alta/baixa/estável)
- Mercado e região

## ⚠️ Limitações

1. **Feeds RSS**: Podem ter limitações de taxa
2. **APIs Gratuitas**: Geralmente têm limites de requisições
3. **Cotações**: Dados simulados - para dados reais, use APIs pagas

## 🔄 Atualização Automática

O sistema verifica automaticamente na primeira carga se há artigos. Se não houver, sincroniza automaticamente.

Para desabilitar:
```javascript
// Em index.php, remova ou comente:
checkAndSyncData();
```

## 📝 Notas

- As APIs de cotações brasileiras geralmente são pagas
- Feeds RSS são gratuitos mas podem ter limitações
- Dados climáticos têm fallback para valores simulados
- Todas as APIs têm tratamento de erro






