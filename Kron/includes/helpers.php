<?php
/**
 * KRON - Funções Helper
 */

/**
 * Obtém o caminho base do projeto
 */
function getBasePath() {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptDir = dirname($scriptName);
    
    // Se estiver na raiz, retornar /
    if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
        return '/';
    }
    
    // Retornar caminho relativo com barra final
    $path = rtrim(str_replace('\\', '/', $scriptDir), '/') . '/';
    return $path;
}

/**
 * Redireciona para uma URL relativa ao projeto
 */
function redirect($path) {
    $basePath = getBasePath();
    $url = $basePath . ltrim($path, '/');
    header('Location: ' . $url);
    exit;
}

