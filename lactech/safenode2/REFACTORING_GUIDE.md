# SafeNode - Guia de Refatoração

Este documento descreve a estrutura MVC implementada e como refatorar código existente.

## Estrutura MVC

```
safenode/
├── src/
│   ├── Controllers/      # Controllers (lógica de apresentação)
│   ├── Models/          # Models (acesso a dados)
│   └── Services/        # Services (lógica de negócio)
├── views/               # Views (apresentação)
├── includes/            # Classes legadas (em migração)
└── api/                 # Endpoints da API
```

## Princípios

### 1. Separação de Responsabilidades

**Controllers**: Orquestram a requisição, chamam services e renderizam views
```php
class DashboardController extends BaseController
{
    public function index()
    {
        $securityService = new SecurityService($this->db);
        $stats = $securityService->getDashboardStats();
        
        $this->render('dashboard/index', ['stats' => $stats]);
    }
}
```

**Models**: Acesso e manipulação de dados
```php
class SiteModel extends BaseModel
{
    public function findByUserId(int $userId): array
    {
        // Lógica de acesso a dados
    }
}
```

**Services**: Lógica de negócio
```php
class SecurityService
{
    public function shouldBlockRequest(string $ipAddress): array
    {
        // Lógica de negócio complexa
    }
}
```

**Views**: Apenas apresentação
```php
<!-- views/dashboard/index.php -->
<div class="stats">
    <?php foreach ($stats as $stat): ?>
        <div><?= htmlspecialchars($stat['name']) ?></div>
    <?php endforeach; ?>
</div>
```

## Exemplo de Refatoração

### Antes (Código Misturado)

```php
<?php
// dashboard.php
session_start();
require_once 'includes/config.php';

$db = getSafeNodeDatabase();
$userId = $_SESSION['safenode_user_id'];

// Lógica de negócio misturada com apresentação
$stmt = $db->query("SELECT COUNT(*) FROM safenode_security_logs");
$totalRequests = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM safenode_blocked_ips");
$blockedIPs = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html>
<body>
    <h1>Dashboard</h1>
    <p>Total: <?php echo $totalRequests; ?></p>
    <p>Bloqueados: <?php echo $blockedIPs; ?></p>
</body>
</html>
```

### Depois (MVC)

**Controller** (`src/Controllers/DashboardController.php`):
```php
<?php
namespace SafeNode\Controllers;

use SafeNode\Services\SecurityService;

class DashboardController extends BaseController
{
    public function index()
    {
        $securityService = new SecurityService($this->db);
        $stats = $securityService->getDashboardStats();
        
        $this->render('dashboard/index', ['stats' => $stats]);
    }
}
```

**Service** (`src/Services/SecurityService.php`):
```php
<?php
namespace SafeNode\Services;

class SecurityService
{
    public function getDashboardStats(): array
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM safenode_security_logs");
        $totalRequests = $stmt->fetchColumn();
        
        $stmt = $this->db->query("SELECT COUNT(*) FROM safenode_blocked_ips");
        $blockedIPs = $stmt->fetchColumn();
        
        return [
            'totalRequests' => $totalRequests,
            'blockedIPs' => $blockedIPs
        ];
    }
}
```

**View** (`views/dashboard/index.php`):
```php
<!DOCTYPE html>
<html>
<body>
    <h1>Dashboard</h1>
    <p>Total: <?= htmlspecialchars($stats['totalRequests']) ?></p>
    <p>Bloqueados: <?= htmlspecialchars($stats['blockedIPs']) ?></p>
</body>
</html>
```

## Migração Gradual

1. **Fase 1**: Criar estrutura MVC (Controllers, Models, Services, Views)
2. **Fase 2**: Migrar novas funcionalidades para MVC
3. **Fase 3**: Refatorar código existente gradualmente
4. **Fase 4**: Remover código legado

## Benefícios

- ✅ Código mais testável
- ✅ Reutilização de lógica
- ✅ Manutenção facilitada
- ✅ Separação clara de responsabilidades
- ✅ Facilita testes unitários

## Próximos Passos

1. Criar router para mapear URLs para controllers
2. Migrar páginas principais para MVC
3. Criar mais services conforme necessário
4. Adicionar validação de dados nos models


