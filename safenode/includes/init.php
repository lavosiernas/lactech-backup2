<?php
/**
 * SafeNode - Inicialização
 * Carrega Router, proteção de URLs e Lógica de Seleção de Site
 */

// Iniciar sessão apenas se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ProtectionStreak.php';

// Remover token da URL se presente (limpeza)
if (isset($_GET['token'])) {
    $currentUrl = $_SERVER['REQUEST_URI'];
    $urlParts = parse_url($currentUrl);
    $path = $urlParts['path'] ?? '';
    $query = $urlParts['query'] ?? '';
    
    if ($query) {
        parse_str($query, $params);
        unset($params['token']);
        if (!empty($params)) {
            $newQuery = http_build_query($params);
            header("Location: $path?$newQuery");
        } else {
            header("Location: $path");
        }
        exit;
    }
}

// Lógica de Troca de Site (Contexto)
if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
    
    // Se o usuário clicou em um site para visualizar
    if (isset($_GET['view_site'])) {
        $siteId = intval($_GET['view_site']);
        
        if ($siteId === 0) {
            // 0 = Visão Global
            $_SESSION['view_site_id'] = 0;
            $_SESSION['view_site_name'] = 'Visão Global';
        } else {
            // Verificar se o site existe
            $db = getSafeNodeDatabase();
            if ($db) {
                // SEGURANÇA: Verificar que o site pertence ao usuário logado
                $userId = $_SESSION['safenode_user_id'] ?? null;
                $stmt = $db->prepare("SELECT id, domain, display_name FROM safenode_sites WHERE id = ? AND user_id = ?");
                $stmt->execute([$siteId, $userId]);
                $site = $stmt->fetch();
                
                if ($site) {
                    $_SESSION['view_site_id'] = $site['id'];
                    $_SESSION['view_site_name'] = $site['display_name'] ?: $site['domain'];
                }
            }
        }
        
        // Redirecionar para limpar a URL (remove o ?view_site=X)
        $redirectUrl = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: $redirectUrl");
        exit;
    }

    // Definir padrão se não existir
    if (!isset($_SESSION['view_site_id'])) {
        $_SESSION['view_site_id'] = 0; // 0 = Global
        $_SESSION['view_site_name'] = 'Visão Global';
    }

    // Registrar proteção do dia (sequência de proteção)
    if (isset($_SESSION['safenode_user_id'])) {
        $userId = $_SESSION['safenode_user_id'];
        $siteId = $_SESSION['view_site_id'] ?? 0;
        
        $streakManager = new ProtectionStreak();
        // Só registrar se estiver habilitado
        if ($streakManager->isEnabled($userId, $siteId)) {
            $streakManager->recordProtection($userId, $siteId);
        }
    }

    // Inicializar Router
    require_once __DIR__ . '/Router.php';
    SafeNodeRouter::init();
}

