<?php
/**
 * SafeNode - Monitoramento em Tempo Real
 * Wrapper PHP que carrega o React App
 */

session_start();

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;

// Verificar se o site pertence ao usuário
if ($currentSiteId > 0) {
    $db = getSafeNodeDatabase();
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT id FROM safenode_sites WHERE id = ? AND user_id = ?");
            $stmt->execute([$currentSiteId, $userId]);
            if (!$stmt->fetch()) {
                $currentSiteId = 0;
            }
        } catch (PDOException $e) {
            $currentSiteId = 0;
        }
    }
}

$selectedSite = null;
if ($currentSiteId > 0) {
    $db = getSafeNodeDatabase();
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM safenode_sites WHERE id = ? AND user_id = ?");
            $stmt->execute([$currentSiteId, $userId]);
            $selectedSite = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Ignorar erro
        }
    }
}

$currentPage = 'security-monitor';

// Verificar se o build do React existe
$reactBuildPath = __DIR__ . '/app/dist/index.html';
$hasReactBuild = file_exists($reactBuildPath);

?>
<!DOCTYPE html>
<html lang="pt-BR" class="<?php echo isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1' ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoramento em Tempo Real - SafeNode</title>
    
    <?php if ($hasReactBuild): ?>
        <!-- React App Build -->
        <?php
        $manifestPath = __DIR__ . '/app/dist/.vite/manifest.json';
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest['index.html'])) {
                foreach ($manifest['index.html']['css'] ?? [] as $css) {
                    echo '<link rel="stylesheet" href="/safenode/app/dist/' . htmlspecialchars($css) . '">';
                }
            }
        }
        ?>
    <?php else: ?>
        <!-- Fallback: Mensagem para build -->
        <style>
            body {
                font-family: system-ui, -apple-system, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                background: #f3f4f6;
            }
            .message {
                text-align: center;
                padding: 2rem;
                background: white;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
        </style>
    <?php endif; ?>
</head>
<body class="bg-white dark:bg-dark-950">
    <?php if (!$hasReactBuild): ?>
        <div class="message">
            <h2>React App não compilado</h2>
            <p>Execute os seguintes comandos para compilar:</p>
            <pre style="background: #f3f4f6; padding: 1rem; border-radius: 4px; margin-top: 1rem;">
cd safenode/app
npm install
npm run build
            </pre>
        </div>
    <?php else: ?>
        <!-- React App será carregado aqui -->
        <div id="root"></div>
        
        <?php
        // Carregar scripts do React
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest['index.html']['file'])) {
                echo '<script type="module" src="/safenode/app/dist/' . htmlspecialchars($manifest['index.html']['file']) . '"></script>';
            }
        }
        ?>
    <?php endif; ?>
</body>
</html>

