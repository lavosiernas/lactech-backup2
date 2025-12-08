# SafeNode - Guia de Testes

Este documento explica como executar e escrever testes para o SafeNode.

## Instalação

```bash
cd lactech/safenode
composer install
```

## Executando Testes

### Todos os testes
```bash
composer test
```

### Apenas testes unitários
```bash
vendor/bin/phpunit tests/Unit
```

### Apenas testes de integração
```bash
vendor/bin/phpunit tests/Integration
```

### Com cobertura de código
```bash
composer test-coverage
```

## Estrutura de Testes

```
tests/
├── Unit/              # Testes unitários (mocks)
│   ├── RateLimiterTest.php
│   ├── IPBlockerTest.php
│   └── ActivityLoggerTest.php
├── Integration/       # Testes de integração (banco real)
│   └── DatabaseIntegrationTest.php
└── bootstrap.php      # Configuração inicial
```

## Escrevendo Testes

### Teste Unitário (com Mock)

```php
<?php
use PHPUnit\Framework\TestCase;

class MyClassTest extends TestCase
{
    private $db;
    private $myClass;
    
    protected function setUp(): void
    {
        $this->db = $this->createMock(PDO::class);
        $this->myClass = new MyClass($this->db);
    }
    
    public function testMyMethod()
    {
        $result = $this->myClass->myMethod();
        $this->assertTrue($result);
    }
}
```

### Teste de Integração (com Banco Real)

```php
<?php
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private $db;
    
    protected function setUp(): void
    {
        $this->db = new PDO(
            "mysql:host=localhost;dbname=safenode_test",
            "root",
            ""
        );
    }
    
    public function testDatabaseConnection()
    {
        $this->assertInstanceOf(PDO::class, $this->db);
    }
}
```

## Configuração de Ambiente de Teste

Crie um arquivo `.env.test` ou configure variáveis de ambiente:

```bash
TEST_DB_HOST=localhost
TEST_DB_NAME=safenode_test
TEST_DB_USER=root
TEST_DB_PASS=
```

## Boas Práticas

1. **Testes devem ser independentes**: Cada teste deve poder rodar isoladamente
2. **Use mocks para dependências externas**: Evite dependências de banco em testes unitários
3. **Nomeie testes descritivamente**: `testShouldBlockIPWhenRateLimitExceeded()`
4. **Um teste, uma asserção**: Prefira múltiplos testes a múltiplas asserções
5. **Teste casos de sucesso e falha**: Teste tanto o caminho feliz quanto erros

## CI/CD

Os testes são executados automaticamente no GitHub Actions quando:
- Um PR é aberto
- Código é enviado para `main` ou `develop`

Veja `.github/workflows/ci.yml` para mais detalhes.


