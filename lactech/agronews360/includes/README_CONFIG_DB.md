# Configuração do Banco de Dados - AgroNews360

## 🔒 Segurança

As credenciais do banco de dados estão configuradas para serem escondidas usando variáveis de ambiente, seguindo o mesmo padrão do Lactech.

## 📋 Configuração

### Opção 1: Variáveis de Ambiente (Recomendado)

Crie um arquivo `.env` na raiz do projeto ou configure as variáveis de ambiente no servidor:

```env
# Banco de Dados - AgroNews360 (Produção)
AGRONEWS_DB_HOST_PROD=localhost
AGRONEWS_DB_NAME_PROD=u311882628_agronews
AGRONEWS_DB_USER_PROD=u311882628_agro360
AGRONEWS_DB_PASS_PROD=Lavosier0012!

# Banco de Dados - AgroNews360 (Local)
AGRONEWS_DB_HOST_LOCAL=localhost
AGRONEWS_DB_NAME_LOCAL=agronews
AGRONEWS_DB_USER_LOCAL=root
AGRONEWS_DB_PASS_LOCAL=
```

### Opção 2: Fallback no Arquivo (Não Recomendado para Produção)

O arquivo `config_mysql.php` já contém as credenciais como fallback, mas **NÃO é recomendado** para produção.

## 🔄 Como Funciona

1. **Detecção Automática de Ambiente**: O sistema detecta automaticamente se está em localhost ou produção
2. **Prioridade de Configuração**:
   - Primeiro: Tenta carregar variáveis de ambiente
   - Segundo: Usa valores padrão (fallback) do arquivo
3. **Segurança**: As credenciais nunca devem ser commitadas no Git

## ⚠️ Importante

- O arquivo `config_mysql.php` **NÃO deve ser commitado** no Git
- Use o arquivo `config_mysql.example.php` como template
- Adicione `config_mysql.php` ao `.gitignore`
- Em produção, sempre use variáveis de ambiente

## 📝 Variáveis Disponíveis

### Produção
- `AGRONEWS_DB_HOST_PROD` - Host do banco (padrão: localhost)
- `AGRONEWS_DB_NAME_PROD` - Nome do banco (padrão: u311882628_agronews)
- `AGRONEWS_DB_USER_PROD` - Usuário do banco (padrão: u311882628_agro360)
- `AGRONEWS_DB_PASS_PROD` - Senha do banco (padrão: Lavosier0012!)

### Local
- `AGRONEWS_DB_HOST_LOCAL` - Host do banco (padrão: localhost)
- `AGRONEWS_DB_NAME_LOCAL` - Nome do banco (padrão: agronews)
- `AGRONEWS_DB_USER_LOCAL` - Usuário do banco (padrão: root)
- `AGRONEWS_DB_PASS_LOCAL` - Senha do banco (padrão: vazio)

## 🔧 Configuração no Servidor

### cPanel / Hostinger
1. Acesse o painel de controle
2. Vá em "Variáveis de Ambiente" ou "Environment Variables"
3. Adicione as variáveis listadas acima
4. Reinicie o servidor se necessário

### Via .htaccess (Alternativa)
```apache
SetEnv AGRONEWS_DB_HOST_PROD localhost
SetEnv AGRONEWS_DB_NAME_PROD u311882628_agronews
SetEnv AGRONEWS_DB_USER_PROD u311882628_agro360
SetEnv AGRONEWS_DB_PASS_PROD Lavosier0012!
```

## ✅ Verificação

Para verificar se está funcionando, adicione temporariamente no código:

```php
// Apenas para debug - REMOVER EM PRODUÇÃO
if (defined('ENVIRONMENT')) {
    error_log('Ambiente: ' . ENVIRONMENT);
    error_log('DB Name: ' . DB_NAME);
    error_log('DB User: ' . DB_USER);
}
```

