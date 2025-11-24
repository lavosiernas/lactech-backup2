<?php
/**
 * SafeNode - Integração com Asaas API
 * Classe para gerenciar pagamentos via Asaas
 */

class AsaasAPI {
    private $apiKey;
    private $baseUrl;
    private $pdo;
    
    // URLs da API Asaas
    const PRODUCTION_URL = 'https://api.asaas.com/v3';
    const SANDBOX_URL = 'https://sandbox.asaas.com/api/v3';
    
    public function __construct($pdo, $apiKey = null, $sandbox = false) {
        $this->pdo = $pdo;
        
        // Buscar API key das configurações se não fornecida
        if ($apiKey === null) {
            require_once __DIR__ . '/Settings.php';
            $this->apiKey = SafeNodeSettings::get('asaas_api_key', '');
            $sandbox = SafeNodeSettings::get('asaas_sandbox', '1') === '1';
        } else {
            $this->apiKey = $apiKey;
        }
        
        // Definir URL base
        $this->baseUrl = $sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;
    }
    
    /**
     * Criar uma cobrança (pagamento único)
     * 
     * @param array $data Dados da cobrança
     * @return array Resposta da API
     */
    public function createPayment($data) {
        $required = ['customer', 'billingType', 'value', 'dueDate'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return [
                    'success' => false,
                    'error' => "Campo obrigatório ausente: $field"
                ];
            }
        }
        
        $payload = [
            'customer' => $data['customer'], // ID do cliente na Asaas
            'billingType' => $data['billingType'], // BOLETO, CREDIT_CARD, PIX, etc
            'value' => number_format((float)$data['value'], 2, '.', ''),
            'dueDate' => $data['dueDate'], // YYYY-MM-DD
            'description' => $data['description'] ?? 'Pagamento SafeNode',
            'externalReference' => $data['externalReference'] ?? null,
        ];
        
        // Adicionar campos opcionais
        if (isset($data['installmentValue'])) {
            $payload['installmentValue'] = number_format((float)$data['installmentValue'], 2, '.', '');
        }
        
        if (isset($data['installmentCount'])) {
            $payload['installmentCount'] = (int)$data['installmentCount'];
        }
        
        if (isset($data['discount'])) {
            $payload['discount'] = $data['discount'];
        }
        
        if (isset($data['fine'])) {
            $payload['fine'] = $data['fine'];
        }
        
        if (isset($data['interest'])) {
            $payload['interest'] = $data['interest'];
        }
        
        // Para PIX
        if ($data['billingType'] === 'PIX') {
            $payload['pixAddressKey'] = $data['pixAddressKey'] ?? null;
        }
        
        // Para cartão de crédito
        if ($data['billingType'] === 'CREDIT_CARD') {
            if (isset($data['creditCard'])) {
                $payload['creditCard'] = $data['creditCard'];
            }
            if (isset($data['creditCardHolderInfo'])) {
                $payload['creditCardHolderInfo'] = $data['creditCardHolderInfo'];
            }
            if (isset($data['creditCardToken'])) {
                $payload['creditCardToken'] = $data['creditCardToken'];
            }
        }
        
        return $this->makeRequest('POST', '/payments', $payload);
    }
    
    /**
     * Criar ou atualizar um cliente na Asaas
     * 
     * @param array $data Dados do cliente
     * @return array Resposta da API
     */
    public function createOrUpdateCustomer($data) {
        $required = ['name', 'email'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return [
                    'success' => false,
                    'error' => "Campo obrigatório ausente: $field"
                ];
            }
        }
        
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'mobilePhone' => $data['mobilePhone'] ?? null,
            'cpfCnpj' => $data['cpfCnpj'] ?? null,
            'postalCode' => $data['postalCode'] ?? null,
            'address' => $data['address'] ?? null,
            'addressNumber' => $data['addressNumber'] ?? null,
            'complement' => $data['complement'] ?? null,
            'province' => $data['province'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? 'Brasil',
            'externalReference' => $data['externalReference'] ?? null, // ID do usuário no SafeNode
        ];
        
        // Se tiver ID do cliente, atualizar
        if (isset($data['id']) && !empty($data['id'])) {
            return $this->makeRequest('PUT', '/customers/' . $data['id'], $payload);
        }
        
        // Caso contrário, criar novo
        return $this->makeRequest('POST', '/customers', $payload);
    }
    
    /**
     * Buscar um pagamento pelo ID
     * 
     * @param string $paymentId ID do pagamento na Asaas
     * @return array Resposta da API
     */
    public function getPayment($paymentId) {
        return $this->makeRequest('GET', "/payments/$paymentId");
    }
    
    /**
     * Cancelar um pagamento
     * 
     * @param string $paymentId ID do pagamento na Asaas
     * @return array Resposta da API
     */
    public function cancelPayment($paymentId) {
        return $this->makeRequest('DELETE', "/payments/$paymentId");
    }
    
    /**
     * Buscar cliente pelo ID
     * 
     * @param string $customerId ID do cliente na Asaas
     * @return array Resposta da API
     */
    public function getCustomer($customerId) {
        return $this->makeRequest('GET', "/customers/$customerId");
    }
    
    /**
     * Buscar cliente por email ou CPF/CNPJ
     * 
     * @param string $search Email ou CPF/CNPJ
     * @return array Resposta da API
     */
    public function searchCustomer($search) {
        $params = http_build_query(['name' => $search, 'email' => $search, 'cpfCnpj' => $search]);
        return $this->makeRequest('GET', "/customers?$params");
    }
    
    /**
     * Gerar QR Code PIX para pagamento
     * 
     * @param string $paymentId ID do pagamento na Asaas
     * @return array Resposta da API
     */
    public function getPixQrCode($paymentId) {
        return $this->makeRequest('GET', "/payments/$paymentId/pixQrCode");
    }
    
    /**
     * Fazer requisição à API da Asaas
     * 
     * @param string $method Método HTTP (GET, POST, PUT, DELETE)
     * @param string $endpoint Endpoint da API
     * @param array $data Dados para enviar (opcional)
     * @return array Resposta da API
     */
    private function makeRequest($method, $endpoint, $data = null) {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'API Key da Asaas não configurada'
            ];
        }
        
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init($url);
        
        $headers = [
            'access_token: ' . $this->apiKey,
            'Content-Type: application/json',
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => "Erro na requisição: $error"
            ];
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $responseData,
                'httpCode' => $httpCode
            ];
        } else {
            $errorMsg = $responseData['errors'][0]['description'] ?? $responseData['message'] ?? 'Erro desconhecido';
            return [
                'success' => false,
                'error' => $errorMsg,
                'httpCode' => $httpCode,
                'response' => $responseData
            ];
        }
    }
    
    /**
     * Salvar transação no banco de dados
     * 
     * @param array $transactionData Dados da transação
     * @return int|false ID da transação ou false em caso de erro
     */
    public function saveTransaction($transactionData) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO safenode_payments (
                    user_id, asaas_payment_id, asaas_customer_id, 
                    amount, billing_type, status, due_date, 
                    description, external_reference, metadata, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $metadata = json_encode($transactionData['metadata'] ?? []);
            
            $stmt->execute([
                $transactionData['user_id'] ?? null,
                $transactionData['asaas_payment_id'] ?? null,
                $transactionData['asaas_customer_id'] ?? null,
                $transactionData['amount'] ?? 0,
                $transactionData['billing_type'] ?? null,
                $transactionData['status'] ?? 'PENDING',
                $transactionData['due_date'] ?? null,
                $transactionData['description'] ?? null,
                $transactionData['external_reference'] ?? null,
                $metadata
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("AsaasAPI: Erro ao salvar transação - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualizar status da transação
     * 
     * @param string $asaasPaymentId ID do pagamento na Asaas
     * @param string $status Novo status
     * @param array $metadata Metadados adicionais
     * @return bool
     */
    public function updateTransactionStatus($asaasPaymentId, $status, $metadata = []) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE safenode_payments 
                SET status = ?, 
                    updated_at = NOW(),
                    metadata = JSON_MERGE_PATCH(COALESCE(metadata, '{}'), ?)
                WHERE asaas_payment_id = ?
            ");
            
            $metadataJson = json_encode($metadata);
            
            return $stmt->execute([$status, $metadataJson, $asaasPaymentId]);
        } catch (PDOException $e) {
            error_log("AsaasAPI: Erro ao atualizar transação - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar transação pelo ID do pagamento Asaas
     * 
     * @param string $asaasPaymentId ID do pagamento na Asaas
     * @return array|false
     */
    public function getTransactionByAsaasId($asaasPaymentId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM safenode_payments 
                WHERE asaas_payment_id = ?
            ");
            $stmt->execute([$asaasPaymentId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("AsaasAPI: Erro ao buscar transação - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Criar assinatura recorrente
     * 
     * @param array $data Dados da assinatura
     * @return array Resposta da API
     */
    public function createSubscription($data) {
        $required = ['customer', 'billingType', 'value', 'cycle'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return [
                    'success' => false,
                    'error' => "Campo obrigatório ausente: $field"
                ];
            }
        }
        
        $payload = [
            'customer' => $data['customer'],
            'billingType' => $data['billingType'],
            'value' => number_format((float)$data['value'], 2, '.', ''),
            'cycle' => $data['cycle'], // WEEKLY, BIWEEKLY, MONTHLY, QUARTERLY, SEMIANNUALLY, YEARLY
            'description' => $data['description'] ?? 'Assinatura SafeNode',
            'externalReference' => $data['externalReference'] ?? null,
        ];
        
        // Data de início (opcional)
        if (isset($data['nextDueDate'])) {
            $payload['nextDueDate'] = $data['nextDueDate'];
        }
        
        // Desconto (opcional)
        if (isset($data['discount'])) {
            $payload['discount'] = $data['discount'];
        }
        
        // Para cartão de crédito
        if ($data['billingType'] === 'CREDIT_CARD') {
            if (isset($data['creditCard'])) {
                $payload['creditCard'] = $data['creditCard'];
            }
            if (isset($data['creditCardHolderInfo'])) {
                $payload['creditCardHolderInfo'] = $data['creditCardHolderInfo'];
            }
            if (isset($data['creditCardToken'])) {
                $payload['creditCardToken'] = $data['creditCardToken'];
            }
        }
        
        return $this->makeRequest('POST', '/subscriptions', $payload);
    }
    
    /**
     * Buscar assinatura pelo ID
     * 
     * @param string $subscriptionId ID da assinatura na Asaas
     * @return array Resposta da API
     */
    public function getSubscription($subscriptionId) {
        return $this->makeRequest('GET', "/subscriptions/$subscriptionId");
    }
    
    /**
     * Cancelar assinatura
     * 
     * @param string $subscriptionId ID da assinatura na Asaas
     * @return array Resposta da API
     */
    public function cancelSubscription($subscriptionId) {
        return $this->makeRequest('DELETE', "/subscriptions/$subscriptionId");
    }
}

