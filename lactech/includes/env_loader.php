<?php
/**
 * Carregador de Variáveis de Ambiente
 * Carrega variáveis do arquivo .env ou usa valores padrão
 */

// Função para carregar variáveis de ambiente do arquivo .env
function loadEnvFile($envPath) {
    if (!file_exists($envPath)) {
        return false;
    }
    
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Ignorar comentários
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Ignorar linhas vazias
        if (empty(trim($line))) {
            continue;
        }
        
        // Separar chave e valor
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remover aspas se existirem
            $value = trim($value, '"\'');
            
            // Definir variável de ambiente se não estiver definida
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
    
    return true;
}

// Detectar ambiente
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
           strpos($_SERVER['DOCUMENT_ROOT'] ?? '', 'xampp') !== false ||
           strpos($_SERVER['DOCUMENT_ROOT'] ?? '', 'htdocs') !== false;

// Carregar arquivos .env na ordem de prioridade:
// 1. .env (geral)
// 2. .env.production ou .env.local (específico do ambiente)
$envPaths = [];
$basePath = __DIR__ . '/..';

// Sempre tentar carregar .env primeiro
$envPaths[] = $basePath . '/.env';

// Depois carregar arquivo específico do ambiente
if ($isLocal) {
    $envPaths[] = $basePath . '/.env.local';
} else {
    $envPaths[] = $basePath . '/.env.production';
}

// Carregar todos os arquivos encontrados (os últimos sobrescrevem os anteriores)
foreach ($envPaths as $envPath) {
    if (file_exists($envPath)) {
        loadEnvFile($envPath);
    }
}

// Função auxiliar para obter variável de ambiente com fallback
function env($key, $default = null) {
    $value = getenv($key);
    
    if ($value === false) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }
    
    return $value !== null ? $value : $default;
}






