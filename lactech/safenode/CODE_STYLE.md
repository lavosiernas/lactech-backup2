# SafeNode - Guia de Estilo de Código

Este documento descreve os padrões de código que devem ser seguidos no projeto SafeNode.

## Padrão PSR-12

O projeto segue o padrão **PSR-12** (Extended Coding Style Guide) do PHP-FIG.

### Principais Regras

#### 1. Indentação e Espaçamento
- Use **4 espaços** para indentação (não tabs)
- Linhas devem ter no máximo **120 caracteres**
- Remova espaços em branco no final das linhas
- Use uma linha em branco no final do arquivo

#### 2. Nomenclatura

**Classes**: PascalCase
```php
class ActivityLogger
class RateLimiter
class IPBlocker
```

**Métodos e Variáveis**: camelCase
```php
public function checkRateLimit()
private $ipAddress
```

**Constantes**: UPPER_SNAKE_CASE
```php
const MAX_RETRY_ATTEMPTS = 3;
const DEFAULT_TIMEOUT = 30;
```

**Arquivos**: PascalCase para classes, lowercase para outros
```
ActivityLogger.php
RateLimiter.php
config.php
```

#### 3. Estrutura de Classes

```php
<?php
/**
 * SafeNode - Nome da Classe
 * Descrição breve
 */

class ClassName
{
    // Constantes
    const CONSTANT_NAME = 'value';
    
    // Propriedades privadas primeiro
    private $privateProperty;
    
    // Propriedades protegidas
    protected $protectedProperty;
    
    // Propriedades públicas (evitar quando possível)
    public $publicProperty;
    
    // Construtor
    public function __construct($param)
    {
        $this->privateProperty = $param;
    }
    
    // Métodos públicos
    public function publicMethod()
    {
        // Implementação
    }
    
    // Métodos protegidos
    protected function protectedMethod()
    {
        // Implementação
    }
    
    // Métodos privados
    private function privateMethod()
    {
        // Implementação
    }
}
```

#### 4. Declarações de Tipo

Sempre use type hints quando possível:

```php
public function checkRateLimit(string $ipAddress, ?string $endpoint = null): array
{
    // Implementação
}
```

#### 5. Comentários e PHPDoc

```php
/**
 * Verifica se um IP excedeu o limite de requisições
 *
 * @param string $ipAddress Endereço IP a verificar
 * @param string|null $endpoint Endpoint específico (opcional)
 * @return array Array com 'allowed' (bool) e 'remaining' (int)
 * @throws PDOException Se houver erro no banco de dados
 */
public function checkRateLimit(string $ipAddress, ?string $endpoint = null): array
{
    // Implementação
}
```

#### 6. Operadores

```php
// Espaços ao redor de operadores
$result = $a + $b;
$condition = $x > 0 && $y < 10;

// Operador ternário
$value = $condition ? $trueValue : $falseValue;
```

#### 7. Arrays

```php
// Use sintaxe curta []
$array = ['key' => 'value'];

// Arrays multilinha
$array = [
    'key1' => 'value1',
    'key2' => 'value2',
    'key3' => 'value3',
];
```

#### 8. Strings

```php
// Prefira aspas simples para strings simples
$message = 'Hello World';

// Use aspas duplas apenas quando necessário (interpolação)
$message = "Hello {$name}";

// Concatenação
$fullName = $firstName . ' ' . $lastName;
```

#### 9. Estruturas de Controle

```php
// if/else
if ($condition) {
    // código
} elseif ($otherCondition) {
    // código
} else {
    // código
}

// foreach
foreach ($items as $item) {
    // código
}

foreach ($items as $key => $value) {
    // código
}

// switch
switch ($value) {
    case 'option1':
        // código
        break;
    
    case 'option2':
        // código
        break;
    
    default:
        // código
        break;
}
```

#### 10. Tratamento de Erros

```php
try {
    // código que pode lançar exceção
} catch (SpecificException $e) {
    // tratamento específico
    error_log("Error: " . $e->getMessage());
    return false;
} catch (Exception $e) {
    // tratamento genérico
    error_log("Unexpected error: " . $e->getMessage());
    return false;
}
```

## Ferramentas

### PHP CS Fixer

Para aplicar automaticamente o padrão PSR-12:

```bash
composer require --dev friendsofphp/php-cs-fixer
vendor/bin/php-cs-fixer fix
```

### PHPStan

Para análise estática de código:

```bash
composer require --dev phpstan/phpstan
composer phpstan
```

## Checklist de Code Review

- [ ] Segue PSR-12
- [ ] Type hints em todos os métodos
- [ ] PHPDoc completo
- [ ] Sem código comentado desnecessário
- [ ] Nomes descritivos
- [ ] Métodos pequenos e focados
- [ ] Tratamento de erros adequado
- [ ] Sem variáveis não utilizadas
- [ ] Testes unitários (quando aplicável)

## Exemplos de Boas Práticas

### ✅ Bom

```php
public function blockIP(string $ipAddress, string $reason, ?string $threatType = null, ?int $duration = null): bool
{
    if (!$this->db) {
        return false;
    }
    
    if ($this->isWhitelisted($ipAddress)) {
        return false;
    }
    
    try {
        $stmt = $this->db->prepare("INSERT INTO ...");
        return $stmt->execute([$ipAddress, $reason]);
    } catch (PDOException $e) {
        error_log("IPBlocker Error: " . $e->getMessage());
        return false;
    }
}
```

### ❌ Ruim

```php
function blockIP($ip, $r, $tt=null, $d=null) {
    if(!$this->db)return false;
    if($this->isWhitelisted($ip))return false;
    try{$stmt=$this->db->prepare("INSERT...");$stmt->execute([$ip,$r]);return true;}catch(Exception $e){return false;}
}
```

## Referências

- [PSR-12: Extended Coding Style Guide](https://www.php-fig.org/psr/psr-12/)
- [PSR-1: Basic Coding Standard](https://www.php-fig.org/psr/psr-1/)
- [PSR-4: Autoloading Standard](https://www.php-fig.org/psr/psr-4/)

