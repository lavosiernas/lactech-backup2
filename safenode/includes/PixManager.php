<?php
/**
 * SafeNode - Pix Payment Manager (EFI Pay Integration)
 * Gerencia a comunicação com a API de Pix do EFI Pay (Gerencianet)
 */

class PixManager {
    private $db;
    private $clientId;
    private $clientSecret;
    private $certificatePath;
    private $sandbox;
    private $baseUrl;

    public function __construct($database) {
        $this->db = $database;
        
        // Configurações (Devem ser preenchidas pelo usuário no config.php)
        $this->clientId = defined('EFI_CLIENT_ID') ? EFI_CLIENT_ID : '';
        $this->clientSecret = defined('EFI_CLIENT_SECRET') ? EFI_CLIENT_SECRET : '';
        $this->certificatePath = defined('EFI_CERTIFICATE_PATH') ? EFI_CERTIFICATE_PATH : '';
        $this->sandbox = defined('EFI_SANDBOX') ? EFI_SANDBOX : false;
        
        $this->baseUrl = $this->sandbox 
            ? 'https://pix-h.api.efipay.com.br' 
            : 'https://pix.api.efipay.com.br';
    }

    /**
     * Obtém Token de Acesso OAuth2
     */
    private function getAccessToken() {
        $url = $this->baseUrl . '/oauth/token';
        $auth = base64_encode($this->clientId . ':' . $this->clientSecret);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->certificatePath);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['access_token'] ?? null;
        }

        error_log("PixManager OAuth Error ($httpCode): " . $response);
        return null;
    }

    /**
     * Cria uma cobrança Pix imediata
     */
    public function createImmediateCharge($userId, $amount, $description = 'SafeNode Premium Plan') {
        $token = $this->getAccessToken();
        if (!$token) return null;

        $txid = bin2hex(random_bytes(16)); // Gerar um TXID único
        $url = $this->baseUrl . '/v2/cob/' . $txid;

        $payload = [
            'calendario' => [
                'expiracao' => 3600 // 1 hora
            ],
            'valor' => [
                'original' => number_format($amount, 2, '.', '')
            ],
            'chave' => defined('EFI_PIX_KEY') ? EFI_PIX_KEY : '', // Chave Pix cadastrada na EFI
            'solicitacaoPagador' => $description
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->certificatePath);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 201) {
            $data = json_decode($response, true);
            $locId = $data['loc']['id'] ?? null;
            
            // Salvar no banco de dados
            $this->savePayment($userId, $txid, $locId, $amount);
            
            // Obter QR Code
            return $this->generateQRCode($locId, $token, $txid);
        }

        error_log("PixManager Create Charge Error ($httpCode): " . $response);
        return null;
    }

    /**
     * Gera o QR Code para uma localização
     */
    private function generateQRCode($locId, $token, $txid) {
        $url = $this->baseUrl . '/v2/loc/' . $locId . '/qrcode';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_GET, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->certificatePath);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return [
                'txid' => $txid,
                'qrcode' => $data['qrcode'] ?? '', // Texto do Copia e Cola
                'imagem' => $data['imagem_base64'] ?? '' // Imagem em Base64
            ];
        }

        return null;
    }

    /**
     * Salva o pagamento pendente no banco
     */
    private function savePayment($userId, $txid, $locId, $amount) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO safenode_payments (user_id, txid, loc_id, amount, status)
                VALUES (?, ?, ?, ?, 'PENDING')
            ");
            $stmt->execute([$userId, $txid, $locId, $amount]);
        } catch (PDOException $e) {
            error_log("PixManager DB Save Error: " . $e->getMessage());
        }
    }

    /**
     * Consulta status de um pagamento
     */
    public function checkStatus($txid) {
        // Primeiro verificar no nosso banco
        $stmt = $this->db->prepare("SELECT status FROM safenode_payments WHERE txid = ?");
        $stmt->execute([$txid]);
        $localStatus = $stmt->fetchColumn();

        if ($localStatus === 'COMPLETED') return 'COMPLETED';

        // Se não estiver completo localmente, consultar a API (Polling fallback)
        $token = $this->getAccessToken();
        if (!$token) return null;

        $url = $this->baseUrl . '/v2/cob/' . $txid;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->certificatePath);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        $apiStatus = $data['status'] ?? '';

        if ($apiStatus === 'CONCLUIDA') {
            $this->completePayment($txid);
            return 'COMPLETED';
        }

        return $apiStatus;
    }

    /**
     * Finaliza o pagamento e faz o upgrade do usuário
     */
    public function completePayment($txid) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT user_id, status FROM safenode_payments WHERE txid = ?");
            $stmt->execute([$txid]);
            $payment = $stmt->fetch();

            if ($payment && $payment['status'] !== 'COMPLETED') {
                // Atualizar status do pagamento
                $stmt = $this->db->prepare("UPDATE safenode_payments SET status = 'COMPLETED' WHERE txid = ?");
                $stmt->execute([$txid]);

                // Atualizar assinatura do usuário
                require_once __DIR__ . '/SubscriptionManager.php';
                $subManager = new SubscriptionManager($this->db);
                $subManager->upgradeToPaid($payment['user_id'], 'PIX-' . $txid, 'PIX-SUB-' . $txid);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("PixManager Complete Payment Error: " . $e->getMessage());
            return false;
        }
    }
}
