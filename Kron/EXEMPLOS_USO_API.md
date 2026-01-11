# 📚 EXEMPLOS DE USO - API KRON

## 🔐 1. Gerar System Token

### No Kron (via código PHP)

```php
require_once 'includes/KronSystemManager.php';

$systemManager = new KronSystemManager();

// Obter sistema
$system = $systemManager->getSystemByName('safenode');
if (!$system) {
    die('Sistema não encontrado');
}

// Gerar token com escopos
$scopes = [
    'metrics:write',
    'logs:write',
    'alerts:write',
    'commands:read',
    'commands:write'
];

$token = $systemManager->generateSystemToken(
    $system['id'],
    $scopes,
    null // Sem expiração
);

echo "Token gerado: " . $token . "\n";
```

---

## 📊 2. Enviar Métricas (Sistema → Kron)

### cURL

```bash
curl -X POST https://kronx.sbs/api/v1/kron/metrics \
  -H "Authorization: Bearer {system_token}" \
  -H "X-System-Name: safenode" \
  -H "X-System-Version: 1.0.0" \
  -H "Content-Type: application/json" \
  -d '{
    "system_name": "safenode",
    "timestamp": "2024-12-15T10:30:00Z",
    "metrics": [
      {
        "type": "requests_total",
        "value": 125000,
        "metadata": {
          "period": "daily",
          "sites_protected": 45
        }
      },
      {
        "type": "threats_blocked",
        "value": 234,
        "metadata": {
          "severity": "high"
        }
      }
    ]
  }'
```

### PHP

```php
$token = 'seu_system_token_aqui';
$apiUrl = 'https://kronx.sbs/api/v1/kron/metrics';

$data = [
    'system_name' => 'safenode',
    'timestamp' => date('c'),
    'metrics' => [
        [
            'type' => 'requests_total',
            'value' => 125000,
            'metadata' => [
                'period' => 'daily',
                'sites_protected' => 45
            ]
        ],
        [
            'type' => 'threats_blocked',
            'value' => 234,
            'metadata' => [
                'severity' => 'high'
            ]
        ]
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'X-System-Name: safenode',
    'X-System-Version: 1.0.0',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $result = json_decode($response, true);
    echo "Métricas enviadas: " . $result['received_count'] . "\n";
} else {
    echo "Erro: " . $response . "\n";
}
```

---

## 📝 3. Enviar Logs (Sistema → Kron)

### PHP

```php
$token = 'seu_system_token_aqui';
$apiUrl = 'https://kronx.sbs/api/v1/kron/logs';

$data = [
    'system_name' => 'lactech',
    'logs' => [
        [
            'level' => 'error',
            'message' => 'Falha na sincronização de dados',
            'context' => [
                'user_id' => 123,
                'action' => 'sync_data',
                'error_code' => 'SYNC_001'
            ],
            'stack_trace' => '...'
        ],
        [
            'level' => 'info',
            'message' => 'Sincronização concluída com sucesso',
            'context' => [
                'records_synced' => 150
            ]
        ]
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'X-System-Name: lactech',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);

echo $response;
```

---

## 🚨 4. Disparar Alerta (Sistema → Kron)

### PHP

```php
$token = 'seu_system_token_aqui';
$apiUrl = 'https://kronx.sbs/api/v1/kron/alerts';

$data = [
    'system_name' => 'safenode',
    'alert_type' => 'security_threat',
    'severity' => 'critical',
    'title' => 'Ataque DDoS detectado',
    'message' => 'Taxa de requisições anormal detectada',
    'metadata' => [
        'ip_source' => '192.168.1.100',
        'requests_per_second' => 10000
    ],
    'timestamp' => date('c')
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'X-System-Name: safenode',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$result = json_decode($response, true);

if ($result['success']) {
    echo "Alerta enviado! ID: " . $result['alert_id'] . "\n";
    echo "Usuários notificados: " . $result['notified_users'] . "\n";
}
```

---

## ⚙️ 5. Consultar Comandos Pendentes (Sistema ← Kron)

### PHP

```php
$token = 'seu_system_token_aqui';
$apiUrl = 'https://kronx.sbs/api/v1/kron/commands/pending?limit=10';

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'X-System-Name: safenode'
]);

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

if ($result['success']) {
    foreach ($result['commands'] as $command) {
        echo "Comando: " . $command['command_id'] . "\n";
        echo "Tipo: " . $command['type'] . "\n";
        echo "Prioridade: " . $command['priority'] . "\n";
        echo "Parâmetros: " . json_encode($command['parameters']) . "\n";
        echo "---\n";
    }
}
```

---

## ✅ 6. Confirmar Execução de Comando (Sistema → Kron)

### PHP

```php
$token = 'seu_system_token_aqui';
$commandId = 'cmd_123456';
$apiUrl = "https://kronx.sbs/api/v1/kron/commands/result";

$data = [
    'command_id' => $commandId,
    'status' => 'success',
    'result' => [
        'records_synced' => 150,
        'duration_ms' => 1250
    ],
    'error' => null,
    'executed_at' => date('c'),
    'execution_time_ms' => 1250
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'X-System-Name: safenode',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

if ($result['success']) {
    echo "Resultado registrado com sucesso!\n";
}
```

---

## 🔍 7. Verificar Health Check

### cURL

```bash
curl https://kronx.sbs/api/v1/kron/health
```

### Resposta

```json
{
  "status": "healthy",
  "version": "1.0.0",
  "database": "connected",
  "timestamp": "2024-12-15T10:30:00Z"
}
```

---

## 🔄 8. Fluxo Completo: Enviar Comando (Kron → Sistema)

### No Kron (PHP)

```php
require_once 'includes/KronCommandManager.php';
require_once 'includes/KronSystemManager.php';

$commandManager = new KronCommandManager();
$systemManager = new KronSystemManager();

// Obter sistema
$system = $systemManager->getSystemByName('safenode');

// Criar comando
$result = $commandManager->createCommand(
    $system['id'],
    'sync_data',
    [
        'table' => 'users',
        'since' => '2024-12-14T00:00:00Z'
    ],
    'high',
    $_SESSION['kron_user_id'] // ID do usuário que criou
);

if ($result) {
    echo "Comando criado: " . $result['command_id'] . "\n";
    echo "O sistema SafeNode irá consultar este comando em breve.\n";
}
```

---

## 📋 9. Classe Helper para Sistemas Governados

### PHP (para usar nos sistemas)

```php
class KronAPIClient {
    private $token;
    private $systemName;
    private $baseUrl = 'https://kronx.sbs/api/v1/kron';
    
    public function __construct($token, $systemName) {
        $this->token = $token;
        $this->systemName = $systemName;
    }
    
    private function request($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'X-System-Name: ' . $this->systemName,
            'Content-Type: application/json'
        ]);
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'code' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }
    
    public function sendMetrics($metrics) {
        return $this->request('POST', '/metrics', [
            'system_name' => $this->systemName,
            'timestamp' => date('c'),
            'metrics' => $metrics
        ]);
    }
    
    public function sendLogs($logs) {
        return $this->request('POST', '/logs', [
            'system_name' => $this->systemName,
            'logs' => $logs
        ]);
    }
    
    public function sendAlert($alertType, $severity, $title, $message, $metadata = null) {
        return $this->request('POST', '/alerts', [
            'system_name' => $this->systemName,
            'alert_type' => $alertType,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'metadata' => $metadata,
            'timestamp' => date('c')
        ]);
    }
    
    public function getPendingCommands($limit = 10) {
        return $this->request('GET', '/commands/pending?limit=' . $limit);
    }
    
    public function confirmCommand($commandId, $status, $result = null, $error = null, $executionTimeMs = null) {
        return $this->request('POST', '/commands/result', [
            'command_id' => $commandId,
            'status' => $status,
            'result' => $result,
            'error' => $error,
            'executed_at' => date('c'),
            'execution_time_ms' => $executionTimeMs
        ]);
    }
}

// Uso
$kron = new KronAPIClient('seu_token', 'safenode');

// Enviar métricas
$kron->sendMetrics([
    ['type' => 'requests_total', 'value' => 125000]
]);

// Consultar comandos
$response = $kron->getPendingCommands();
if ($response['code'] === 200 && !empty($response['data']['commands'])) {
    foreach ($response['data']['commands'] as $command) {
        // Executar comando...
        
        // Confirmar execução
        $kron->confirmCommand(
            $command['command_id'],
            'success',
            ['result' => 'ok'],
            null,
            1250
        );
    }
}
```

---

## ⚠️ Tratamento de Erros

### Exemplo de Resposta de Erro

```json
{
  "success": false,
  "error": {
    "code": "INVALID_TOKEN",
    "message": "Token inválido ou expirado"
  },
  "timestamp": "2024-12-15T10:30:00Z"
}
```

### Códigos de Erro Comuns

- `INVALID_TOKEN` - Token inválido ou expirado
- `INSUFFICIENT_SCOPE` - Escopo insuficiente
- `INVALID_DATA` - Dados inválidos
- `SYSTEM_MISMATCH` - Sistema não corresponde ao token
- `COMMAND_NOT_FOUND` - Comando não encontrado
- `INTERNAL_ERROR` - Erro interno do servidor

---

**Última atualização:** Dezembro 2024



