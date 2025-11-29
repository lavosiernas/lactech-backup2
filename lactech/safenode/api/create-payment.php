<?php
/**
 * SafeNode - API: Criar Pagamento Asaas
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/SecurityHelpers.php';
require_once __DIR__ . '/../includes/AsaasAPI.php';

SecurityHeaders::apply();
header('Content-Type: application/json; charset=utf-8');

// Verificar autenticação
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Validar CSRF
if (!CSRFProtection::validate()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token de segurança inválido']);
    exit;
}

try {
    $pdo = getSafeNodeDatabase();
    if (!$pdo) {
        throw new Exception('Erro ao conectar ao banco de dados');
    }
    
    $userId = $_SESSION['safenode_user_id'] ?? null;
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    // Validar dados
    $required = ['value', 'billingType', 'dueDate'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Campo obrigatório ausente: $field");
        }
    }
    
    $value = (float)$input['value'];
    $billingType = $input['billingType'];
    $dueDate = $input['dueDate'];
    $description = $input['description'] ?? 'Pagamento SafeNode';
    
    // Validar valor
    if ($value <= 0) {
        throw new Exception('Valor deve ser maior que zero');
    }
    
    // Validar tipo de pagamento
    $allowedTypes = ['BOLETO', 'CREDIT_CARD', 'PIX', 'DEBIT_CARD'];
    if (!in_array($billingType, $allowedTypes)) {
        throw new Exception('Tipo de pagamento inválido');
    }
    
    // Validar data
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
        throw new Exception('Data inválida. Use o formato YYYY-MM-DD');
    }
    
    // Buscar ou criar cliente na Asaas
    $asaasAPI = new AsaasAPI($pdo);
    
    // Verificar se já existe cliente para este usuário
    $stmt = $pdo->prepare("SELECT asaas_customer_id FROM safenode_asaas_customers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $customerData = $stmt->fetch();
    
    $asaasCustomerId = null;
    
    if ($customerData && !empty($customerData['asaas_customer_id'])) {
        $asaasCustomerId = $customerData['asaas_customer_id'];
    } else {
        // Buscar dados do usuário
        $stmt = $pdo->prepare("SELECT username, email, full_name FROM safenode_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('Usuário não encontrado');
        }
        
        // Criar cliente na Asaas
        $customerData = [
            'name' => $user['full_name'] ?: $user['username'],
            'email' => $user['email'],
            'externalReference' => (string)$userId
        ];
        
        $customerResult = $asaasAPI->createOrUpdateCustomer($customerData);
        
        if (!$customerResult['success']) {
            throw new Exception('Erro ao criar cliente: ' . $customerResult['error']);
        }
        
        $asaasCustomerId = $customerResult['data']['id'];
        
        // Salvar cliente no banco
        $stmt = $pdo->prepare("
            INSERT INTO safenode_asaas_customers (user_id, asaas_customer_id, name, email)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                asaas_customer_id = VALUES(asaas_customer_id),
                name = VALUES(name),
                email = VALUES(email),
                updated_at = NOW()
        ");
        $stmt->execute([
            $userId,
            $asaasCustomerId,
            $customerData['name'],
            $customerData['email']
        ]);
    }
    
    // Criar pagamento
    $paymentData = [
        'customer' => $asaasCustomerId,
        'billingType' => $billingType,
        'value' => $value,
        'dueDate' => $dueDate,
        'description' => $description,
        'externalReference' => "SN-$userId-" . time()
    ];
    
    // Adicionar dados específicos do tipo de pagamento
    if ($billingType === 'CREDIT_CARD' && isset($input['creditCard'])) {
        $paymentData['creditCard'] = $input['creditCard'];
        if (isset($input['creditCardHolderInfo'])) {
            $paymentData['creditCardHolderInfo'] = $input['creditCardHolderInfo'];
        }
    }
    
    $paymentResult = $asaasAPI->createPayment($paymentData);
    
    if (!$paymentResult['success']) {
        throw new Exception('Erro ao criar pagamento: ' . $paymentResult['error']);
    }
    
    $payment = $paymentResult['data'];
    
    // Salvar transação no banco
    $transactionId = $asaasAPI->saveTransaction([
        'user_id' => $userId,
        'asaas_payment_id' => $payment['id'],
        'asaas_customer_id' => $asaasCustomerId,
        'amount' => $value,
        'billing_type' => $billingType,
        'status' => $payment['status'] ?? 'PENDING',
        'due_date' => $dueDate,
        'description' => $description,
        'external_reference' => $paymentData['externalReference'],
        'metadata' => $payment
    ]);
    
    // Se for PIX, buscar QR Code
    $pixQrCode = null;
    if ($billingType === 'PIX' && isset($payment['id'])) {
        $qrCodeResult = $asaasAPI->getPixQrCode($payment['id']);
        if ($qrCodeResult['success']) {
            $pixQrCode = $qrCodeResult['data'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'payment' => $payment,
            'pixQrCode' => $pixQrCode,
            'transactionId' => $transactionId
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}





/**
 * SafeNode - API: Criar Pagamento Asaas
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/SecurityHelpers.php';
require_once __DIR__ . '/../includes/AsaasAPI.php';

SecurityHeaders::apply();
header('Content-Type: application/json; charset=utf-8');

// Verificar autenticação
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Validar CSRF
if (!CSRFProtection::validate()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token de segurança inválido']);
    exit;
}

try {
    $pdo = getSafeNodeDatabase();
    if (!$pdo) {
        throw new Exception('Erro ao conectar ao banco de dados');
    }
    
    $userId = $_SESSION['safenode_user_id'] ?? null;
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    // Validar dados
    $required = ['value', 'billingType', 'dueDate'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Campo obrigatório ausente: $field");
        }
    }
    
    $value = (float)$input['value'];
    $billingType = $input['billingType'];
    $dueDate = $input['dueDate'];
    $description = $input['description'] ?? 'Pagamento SafeNode';
    
    // Validar valor
    if ($value <= 0) {
        throw new Exception('Valor deve ser maior que zero');
    }
    
    // Validar tipo de pagamento
    $allowedTypes = ['BOLETO', 'CREDIT_CARD', 'PIX', 'DEBIT_CARD'];
    if (!in_array($billingType, $allowedTypes)) {
        throw new Exception('Tipo de pagamento inválido');
    }
    
    // Validar data
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
        throw new Exception('Data inválida. Use o formato YYYY-MM-DD');
    }
    
    // Buscar ou criar cliente na Asaas
    $asaasAPI = new AsaasAPI($pdo);
    
    // Verificar se já existe cliente para este usuário
    $stmt = $pdo->prepare("SELECT asaas_customer_id FROM safenode_asaas_customers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $customerData = $stmt->fetch();
    
    $asaasCustomerId = null;
    
    if ($customerData && !empty($customerData['asaas_customer_id'])) {
        $asaasCustomerId = $customerData['asaas_customer_id'];
    } else {
        // Buscar dados do usuário
        $stmt = $pdo->prepare("SELECT username, email, full_name FROM safenode_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('Usuário não encontrado');
        }
        
        // Criar cliente na Asaas
        $customerData = [
            'name' => $user['full_name'] ?: $user['username'],
            'email' => $user['email'],
            'externalReference' => (string)$userId
        ];
        
        $customerResult = $asaasAPI->createOrUpdateCustomer($customerData);
        
        if (!$customerResult['success']) {
            throw new Exception('Erro ao criar cliente: ' . $customerResult['error']);
        }
        
        $asaasCustomerId = $customerResult['data']['id'];
        
        // Salvar cliente no banco
        $stmt = $pdo->prepare("
            INSERT INTO safenode_asaas_customers (user_id, asaas_customer_id, name, email)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                asaas_customer_id = VALUES(asaas_customer_id),
                name = VALUES(name),
                email = VALUES(email),
                updated_at = NOW()
        ");
        $stmt->execute([
            $userId,
            $asaasCustomerId,
            $customerData['name'],
            $customerData['email']
        ]);
    }
    
    // Criar pagamento
    $paymentData = [
        'customer' => $asaasCustomerId,
        'billingType' => $billingType,
        'value' => $value,
        'dueDate' => $dueDate,
        'description' => $description,
        'externalReference' => "SN-$userId-" . time()
    ];
    
    // Adicionar dados específicos do tipo de pagamento
    if ($billingType === 'CREDIT_CARD' && isset($input['creditCard'])) {
        $paymentData['creditCard'] = $input['creditCard'];
        if (isset($input['creditCardHolderInfo'])) {
            $paymentData['creditCardHolderInfo'] = $input['creditCardHolderInfo'];
        }
    }
    
    $paymentResult = $asaasAPI->createPayment($paymentData);
    
    if (!$paymentResult['success']) {
        throw new Exception('Erro ao criar pagamento: ' . $paymentResult['error']);
    }
    
    $payment = $paymentResult['data'];
    
    // Salvar transação no banco
    $transactionId = $asaasAPI->saveTransaction([
        'user_id' => $userId,
        'asaas_payment_id' => $payment['id'],
        'asaas_customer_id' => $asaasCustomerId,
        'amount' => $value,
        'billing_type' => $billingType,
        'status' => $payment['status'] ?? 'PENDING',
        'due_date' => $dueDate,
        'description' => $description,
        'external_reference' => $paymentData['externalReference'],
        'metadata' => $payment
    ]);
    
    // Se for PIX, buscar QR Code
    $pixQrCode = null;
    if ($billingType === 'PIX' && isset($payment['id'])) {
        $qrCodeResult = $asaasAPI->getPixQrCode($payment['id']);
        if ($qrCodeResult['success']) {
            $pixQrCode = $qrCodeResult['data'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'payment' => $payment,
            'pixQrCode' => $pixQrCode,
            'transactionId' => $transactionId
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}







