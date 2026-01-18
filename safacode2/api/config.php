<?php
/**
 * SafeCode IDE - Database Configuration
 */

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'safecode');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações de segurança
define('JWT_SECRET', 'safecode-secret-key-change-in-production');
define('JWT_EXPIRATION', 86400 * 7); // 7 dias

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Conectar ao banco de dados
 */
function getDatabase() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

/**
 * Resposta JSON padronizada
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Gerar token JWT simples
 */
function generateToken($userId, $email) {
    $payload = [
        'user_id' => $userId,
        'email' => $email,
        'exp' => time() + JWT_EXPIRATION,
        'iat' => time()
    ];
    
    // JWT simples (base64)
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload_encoded = base64_encode(json_encode($payload));
    $signature = base64_encode(hash_hmac('sha256', "$header.$payload_encoded", JWT_SECRET, true));
    
    return "$header.$payload_encoded.$signature";
}

/**
 * Verificar token JWT
 */
function verifyToken($token) {
    try {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        
        [$header, $payload_encoded, $signature] = $parts;
        
        $expected_signature = base64_encode(hash_hmac('sha256', "$header.$payload_encoded", JWT_SECRET, true));
        
        if (!hash_equals($signature, $expected_signature)) {
            return null;
        }
        
        $payload = json_decode(base64_decode($payload_encoded), true);
        
        if ($payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Obter token do header Authorization
 */
function getAuthToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return $matches[1];
    }
    
    return null;
}

