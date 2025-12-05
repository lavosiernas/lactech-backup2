<?php
/**
 * SafeNode - Test Bootstrap
 * Configuração inicial para os testes
 */

// Definir constantes de ambiente de teste
define('TESTING', true);
define('ROOT_PATH', dirname(__DIR__));

// Incluir autoloader do Composer se existir
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Incluir arquivos necessários
require_once __DIR__ . '/../includes/config.php';

// Incluir classes que serão testadas
require_once __DIR__ . '/../includes/RateLimiter.php';
require_once __DIR__ . '/../includes/IPBlocker.php';
require_once __DIR__ . '/../includes/ActivityLogger.php';

// Configurar ambiente de teste
error_reporting(E_ALL);
ini_set('display_errors', '1');

