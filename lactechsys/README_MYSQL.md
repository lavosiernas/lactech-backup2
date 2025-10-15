# LacTech - Lagoa do Mato (MySQL)

Sistema de gestão leiteira otimizado para a Fazenda Lagoa do Mato usando MySQL e PHPMyAdmin.

## 📋 Pré-requisitos

- XAMPP (Apache + MySQL + PHP)
- PHP 7.4 ou superior
- MySQL 8.0 ou superior
- Navegador web moderno

## 🚀 Instalação

### 1. Configurar XAMPP

1. **Iniciar serviços:**
   - Abra o XAMPP Control Panel
   - Inicie Apache e MySQL

2. **Acessar PHPMyAdmin:**
   - Acesse: `http://localhost/phpmyadmin`

### 2. Criar Banco de Dados

1. **Importar o banco:**
   - No PHPMyAdmin, clique em "Importar"
   - Selecione o arquivo: `database_lagoa_mato_corrected.sql`
   - Clique em "Executar"

2. **Verificar criação:**
   - O banco `lactech_lagoa_mato` deve aparecer na lista
   - Verifique se todas as tabelas foram criadas

### 3. Configurar Conexão

1. **Editar configurações:**
   - Abra: `lactechsys/includes/config_mysql.php`
   - Ajuste as configurações do banco se necessário:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'lactech_lagoa_mato');
define('DB_USER', 'root');
define('DB_PASS', ''); // Sua senha do MySQL
```

### 4. Acessar Sistema

1. **URL do sistema:**
   - Acesse: `http://localhost/lactechsys/`

2. **Login padrão:**
   - **Email:** admin@lagoa.com
   - **Senha:** password

## 📊 Estrutura do Banco

### Tabelas Principais

- **farms** - Dados da fazenda (Lagoa do Mato)
- **users** - Usuários do sistema
- **animals** - Cadastro de animais
- **volume_records** - Produção de leite (compatível com o sistema)
- **quality_tests** - Testes de qualidade do leite
- **treatments** - Tratamentos veterinários
- **artificial_inseminations** - Inseminações artificiais
- **animal_health_records** - Histórico de saúde
- **financial_records** - Registros financeiros
- **notifications** - Sistema de notificações
- **secondary_accounts** - Contas secundárias
- **farm_settings** - Configurações da fazenda

### Dados Iniciais

- 1 usuário administrador (proprietario)
- 4 animais de exemplo
- Configurações padrão da fazenda

### Tipos de Usuários

O sistema possui **3 tipos de usuários**:

1. **Proprietário** - Acesso total ao sistema
2. **Gerente** - Acesso completo incluindo funções veterinárias
3. **Funcionário** - Acesso limitado para operações básicas

> **Nota:** A página do veterinário foi desativada. Todas as funções veterinárias foram centralizadas no painel do gerente.

## 🔧 Funcionalidades

### ✅ Implementadas

- **Gestão de Usuários:** Criar e gerenciar usuários (3 tipos)
- **Cadastro de Animais:** Nome, identificação, raça, status
- **Produção de Leite:** Registro geral e individual por vaca
- **Testes de Qualidade:** Gordura, proteína, CCS, CBT
- **Tratamentos:** Medicamentos, vacinas, vermífugos
- **Inseminações:** Controle reprodutivo
- **Financeiro:** Receitas e despesas
- **Relatórios:** Produção, qualidade, financeiro
- **Notificações:** Sistema interno de alertas

### ❌ Removidas (não necessárias)

- **Sistema de Chat:** Removido para simplificar o sistema
- **Página do Veterinário:** Funções movidas para o gerente
- **Tipo de Usuário Veterinário:** Removido (apenas 3 tipos)

### 🎯 Vantagens do MySQL

- **Simplicidade:** Fácil administração via PHPMyAdmin
- **Performance:** Otimizado para uma fazenda
- **Backup:** Backup simples via PHPMyAdmin
- **Manutenção:** Ferramentas gráficas intuitivas
- **Custo:** Gratuito e open source

## 📈 Relatórios Disponíveis

1. **Produção Diária:** Volume por turno
2. **Produção Individual:** Por vaca específica
3. **Qualidade do Leite:** Análises laboratoriais
4. **Tratamentos:** Histórico veterinário
5. **Inseminações:** Controle reprodutivo
6. **Financeiro:** Receitas e despesas

## 🔐 Segurança

- **Senhas:** Hash com bcrypt
- **Sessões:** Gerenciamento seguro
- **Sanitização:** Proteção contra SQL injection
- **Validação:** Validação de dados de entrada

## 📱 Interface

- **Responsiva:** Funciona em desktop e mobile
- **Intuitiva:** Interface moderna e limpa
- **Rápida:** Otimizada para performance
- **Acessível:** Fácil de usar

## 🛠️ Manutenção

### Backup Regular

1. **Via PHPMyAdmin:**
   - Selecione o banco `lactech_lagoa_mato`
   - Clique em "Exportar"
   - Escolha "Personalizado"
   - Marque "Adicionar DROP TABLE"
   - Execute o backup

2. **Via linha de comando:**
```bash
mysqldump -u root -p lactech_lagoa_mato > backup_$(date +%Y%m%d).sql
```

### Atualizações

- Mantenha o XAMPP atualizado
- Faça backup antes de atualizações
- Teste em ambiente de desenvolvimento

## 🆘 Suporte

### Problemas Comuns

1. **Erro de conexão:**
   - Verifique se MySQL está rodando
   - Confirme credenciais em `config_mysql.php`

2. **Página em branco:**
   - Verifique logs de erro do PHP
   - Confirme permissões dos arquivos

3. **Erro 500:**
   - Verifique configuração do Apache
   - Confirme se todas as extensões PHP estão habilitadas

### Logs

- **Apache:** `xampp/apache/logs/error.log`
- **MySQL:** `xampp/mysql/data/*.err`
- **PHP:** Configurado em `php.ini`

## 📞 Contato

Para suporte técnico ou dúvidas sobre o sistema, consulte a documentação ou entre em contato com o administrador do sistema.

---

**LacTech - Sistema de Gestão Leiteira**  
*Desenvolvido para a Fazenda Lagoa do Mato*
