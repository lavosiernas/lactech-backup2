<?php
/**
 * KRON - Configuração do Banco de Dados
 */

// Detectar ambiente (local ou produção)
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
           strpos($_SERVER['SERVER_NAME'] ?? '', '192.168.') === 0 ||
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

// Configurações do banco de dados
if ($isLocal) {
    // LOCAL
    define('KRON_DB_HOST', 'localhost');
    define('KRON_DB_NAME', 'kronserver');
    define('KRON_DB_USER', 'root');
    define('KRON_DB_PASS', '');
} else {
    // PRODUÇÃO
    define('KRON_DB_HOST', 'localhost');
    define('KRON_DB_NAME', 'kronserver');
    define('KRON_DB_USER', 'u311882628_kronz');
    define('KRON_DB_PASS', 'Lavosier0012!');
}

/**
 * Conectar ao banco de dados KRON
 */
function getKronDatabase() {
    try {
        $pdo = new PDO(
            "mysql:host=" . KRON_DB_HOST . ";dbname=" . KRON_DB_NAME . ";charset=utf8mb4",
            KRON_DB_USER,
            KRON_DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("KRON Database Error: " . $e->getMessage());
        return null;
    }
}

