<?php
/**
 * API LacPay - Sistema de Pagamentos PIX
 * Endpoints para gerenciar cobranças e verificação de pagamentos
 */

// Configurações de segurança
error_reporting(0);
ini_set('display_errors', 0);

// Headers CORS e JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir Database e PixGenerator
require_once __DIR__ . '/../includes/Database.class.php';
require_once __DIR__ . '/../includes/config_login.php';
require_once __DIR__ . '/../includes/PixGenerator.class.php';

// Funções de resposta
function jsonResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Verificar se a tabela existe, se não criar
    $db->query("
        CREATE TABLE IF NOT EXISTS pix_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            txid VARCHAR(100) UNIQUE NOT NULL,
            plan_id VARCHAR(50) NOT NULL,
            plan_name VARCHAR(100) NOT NULL,
            plan_value DECIMAL(10,2) NOT NULL,
            pix_code TEXT NOT NULL,
            status ENUM('pendente', 'pago', 'expirado', 'cancelado') DEFAULT 'pendente',
            paid_at DATETIME NULL,
            verified_by INT NULL,
            verified_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_txid (txid),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Configurações PIX (chave do recebedor)
    $PIX_KEY = 'slavosier298@gmail.com';
    $PIX_RECEIVER_NAME = 'LacTech - Sistema de Gestão Leiteira';
    $PIX_RECEIVER_CITY = 'Brasília';
    
    switch ($method) {
        case 'POST':
            // Registrar nova cobrança PIX
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar dados obrigatórios
            if (!isset($data['plan_id']) || !isset($data['plan_value'])) {
                jsonResponse(false, null, 'Dados incompletos: plan_id e plan_value são obrigatórios', 400);
            }
            
            $planId = $data['plan_id'];
            $planName = $data['plan_name'] ?? $planId;
            $planValue = floatval($data['plan_value']);
            
            // Validar valor
            if ($planValue <= 0) {
                jsonResponse(false, null, 'Valor deve ser maior que zero', 400);
            }
            
            // Se for plano básico e já vier com código PIX fixo, usar ele
            if ($planId === 'basico' && isset($data['pix_code'])) {
                $txid = $data['txid'] ?? 'BASICO-FIXO-' . time();
                $pixCode = $data['pix_code'];
                
                // Verificar se TXID já existe
                $existing = $db->query("SELECT id FROM pix_payments WHERE txid = ?", [$txid]);
                if (!empty($existing)) {
                    // Se já existe, retornar o existente
                    $existingPayment = $db->query("SELECT * FROM pix_payments WHERE txid = ?", [$txid]);
                    jsonResponse(true, $existingPayment[0], 'Cobrança já existe', 200);
                }
                
                // Inserir nova cobrança com código fixo
                $db->query("
                    INSERT INTO pix_payments (txid, plan_id, plan_name, plan_value, pix_code, status)
                    VALUES (?, ?, ?, ?, ?, 'pendente')
                ", [$txid, $planId, $planName, $planValue, $pixCode]);
                
                jsonResponse(true, [
                    'txid' => $txid,
                    'pix_code' => $pixCode,
                    'status' => 'pendente',
                    'created_at' => date('Y-m-d H:i:s')
                ], 'Cobrança registrada com sucesso', 201);
                break;
            }
            
            // Para outros planos, gerar código PIX normalmente
            // Gerar TXID se não fornecido
            $txid = $data['txid'] ?? 'LACPIX' . time() . rand(1000, 9999);
            
            // Verificar se TXID já existe
            $existing = $db->query("SELECT id FROM pix_payments WHERE txid = ?", [$txid]);
            if (!empty($existing)) {
                jsonResponse(false, null, 'TXID já existe', 409);
            }
            
            // Gerar payload PIX usando a classe PixGenerator
            try {
                $pixGenerator = new PixGenerator($PIX_KEY, $PIX_RECEIVER_NAME, $PIX_RECEIVER_CITY);
                $pixCode = $pixGenerator->generatePayload($txid, $planValue, $planName);
            } catch (Exception $e) {
                jsonResponse(false, null, 'Erro ao gerar código PIX: ' . $e->getMessage(), 500);
            }
            
            // Inserir nova cobrança
            $db->query("
                INSERT INTO pix_payments (txid, plan_id, plan_name, plan_value, pix_code, status)
                VALUES (?, ?, ?, ?, ?, 'pendente')
            ", [$txid, $planId, $planName, $planValue, $pixCode]);
            
            jsonResponse(true, [
                'txid' => $txid,
                'pix_code' => $pixCode,
                'status' => 'pendente',
                'created_at' => date('Y-m-d H:i:s')
            ], 'Cobrança registrada com sucesso', 201);
            break;
            
        case 'GET':
            // Verificar status de pagamento ou listar pagamentos
            if (isset($_GET['txid'])) {
                // Verificar status de um pagamento específico
                $txid = $_GET['txid'];
                $payment = $db->query("
                    SELECT 
                        id, txid, plan_id, plan_name, plan_value, pix_code, 
                        status, paid_at, verified_by, verified_at, created_at, updated_at
                    FROM pix_payments 
                    WHERE txid = ?
                ", [$txid]);
                
                if (empty($payment)) {
                    jsonResponse(false, null, 'Pagamento não encontrado', 404);
                }
                
                jsonResponse(true, $payment[0], 'Status do pagamento');
                
            } elseif (isset($_GET['action']) && $_GET['action'] === 'list') {
                // Listar todos os pagamentos (admin)
                $status = $_GET['status'] ?? null;
                $limit = intval($_GET['limit'] ?? 50);
                
                $sql = "SELECT * FROM pix_payments WHERE 1=1";
                $params = [];
                
                if ($status) {
                    $sql .= " AND status = ?";
                    $params[] = $status;
                }
                
                $sql .= " ORDER BY created_at DESC LIMIT ?";
                $params[] = $limit;
                
                $payments = $db->query($sql, $params);
                
                jsonResponse(true, $payments, 'Lista de pagamentos');
                
            } else {
                jsonResponse(false, null, 'Parâmetros inválidos', 400);
            }
            break;
            
        case 'PUT':
            // Atualizar status do pagamento (verificação manual ou automática)
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['txid']) || !isset($data['status'])) {
                jsonResponse(false, null, 'Dados incompletos', 400);
            }
            
            $txid = $data['txid'];
            $status = $data['status'];
            $verifiedBy = $data['verified_by'] ?? null;
            
            // Verificar se pagamento existe
            $payment = $db->query("SELECT id FROM pix_payments WHERE txid = ?", [$txid]);
            if (empty($payment)) {
                jsonResponse(false, null, 'Pagamento não encontrado', 404);
            }
            
            // Atualizar status
            if ($status === 'pago') {
                $db->query("
                    UPDATE pix_payments 
                    SET status = ?, 
                        paid_at = NOW(),
                        verified_by = ?,
                        verified_at = NOW(),
                        updated_at = NOW()
                    WHERE txid = ?
                ", [$status, $verifiedBy, $txid]);
            } else {
                $db->query("
                    UPDATE pix_payments 
                    SET status = ?, 
                        updated_at = NOW()
                    WHERE txid = ?
                ", [$status, $txid]);
            }
            
            // Buscar pagamento atualizado
            $updated = $db->query("
                SELECT * FROM pix_payments WHERE txid = ?
            ", [$txid]);
            
            jsonResponse(true, $updated[0], 'Status atualizado com sucesso');
            break;
            
        default:
            jsonResponse(false, null, 'Método não permitido', 405);
    }
    
} catch (Exception $e) {
    jsonResponse(false, null, 'Erro interno: ' . $e->getMessage(), 500);
}
?>

