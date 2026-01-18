<?php
/**
 * SafeCode IDE - User Settings API
 * Endpoints: /api/settings.php?action=get|update
 */

require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Verificar autenticação
$token = getAuthToken();
if (!$token) {
    jsonResponse(['success' => false, 'error' => 'Token não fornecido'], 401);
}

$payload = verifyToken($token);
if (!$payload) {
    jsonResponse(['success' => false, 'error' => 'Token inválido ou expirado'], 401);
}

$userId = $payload['user_id'];

switch ($action) {
    case 'get':
        handleGetSettings($userId);
        break;
    case 'update':
        handleUpdateSettings($userId);
        break;
    default:
        jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
}

/**
 * Buscar configurações do usuário
 */
function handleGetSettings($userId) {
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    $stmt = $db->prepare("SELECT * FROM user_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $settings = $stmt->fetch();
    
    // Se não existir, criar com valores padrão
    if (!$settings) {
        $defaultSettings = [
            'editor_settings' => json_encode([
                'fontSize' => 14,
                'fontFamily' => 'Monaco, Consolas, monospace',
                'theme' => 'dark',
                'wordWrap' => true,
                'lineNumbers' => true,
                'tabSize' => 2,
                'minimap' => true,
                'autoSave' => true
            ]),
            'ide_settings' => json_encode([
                'sidebarOpen' => true,
                'terminalOpen' => false,
                'previewOpen' => true
            ]),
            'keybindings' => json_encode([]),
            'extensions' => json_encode([])
        ];
        
        $stmt = $db->prepare("
            INSERT INTO user_settings (user_id, editor_settings, ide_settings, keybindings, extensions)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $defaultSettings['editor_settings'],
            $defaultSettings['ide_settings'],
            $defaultSettings['keybindings'],
            $defaultSettings['extensions']
        ]);
        
        $settings = [
            'user_id' => $userId,
            'editor_settings' => json_decode($defaultSettings['editor_settings'], true),
            'ide_settings' => json_decode($defaultSettings['ide_settings'], true),
            'keybindings' => json_decode($defaultSettings['keybindings'], true),
            'extensions' => json_decode($defaultSettings['extensions'], true)
        ];
    } else {
        // Decodificar JSON
        $settings['editor_settings'] = json_decode($settings['editor_settings'], true) ?? [];
        $settings['ide_settings'] = json_decode($settings['ide_settings'], true) ?? [];
        $settings['keybindings'] = json_decode($settings['keybindings'], true) ?? [];
        $settings['extensions'] = json_decode($settings['extensions'], true) ?? [];
    }
    
    jsonResponse([
        'success' => true,
        'settings' => $settings
    ]);
}

/**
 * Atualizar configurações do usuário
 */
function handleUpdateSettings($userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'error' => 'Método não permitido'], 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    // Verificar se settings já existem
    $stmt = $db->prepare("SELECT id FROM user_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $exists = $stmt->fetch();
    
    $fields = [];
    $values = [];
    
    if (isset($input['editor_settings'])) {
        $fields[] = "editor_settings = ?";
        $values[] = json_encode($input['editor_settings']);
    }
    if (isset($input['ide_settings'])) {
        $fields[] = "ide_settings = ?";
        $values[] = json_encode($input['ide_settings']);
    }
    if (isset($input['keybindings'])) {
        $fields[] = "keybindings = ?";
        $values[] = json_encode($input['keybindings']);
    }
    if (isset($input['extensions'])) {
        $fields[] = "extensions = ?";
        $values[] = json_encode($input['extensions']);
    }
    
    if (empty($fields)) {
        jsonResponse(['success' => false, 'error' => 'Nenhum campo para atualizar'], 400);
    }
    
    try {
        if ($exists) {
            // Atualizar
            $values[] = $userId;
            $sql = "UPDATE user_settings SET " . implode(', ', $fields) . " WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($values);
        } else {
            // Criar
            $defaultEditor = json_encode($input['editor_settings'] ?? [
                'fontSize' => 14,
                'fontFamily' => 'Monaco, Consolas, monospace',
                'theme' => 'dark'
            ]);
            $defaultIDE = json_encode($input['ide_settings'] ?? []);
            $defaultKeybindings = json_encode($input['keybindings'] ?? []);
            $defaultExtensions = json_encode($input['extensions'] ?? []);
            
            $stmt = $db->prepare("
                INSERT INTO user_settings (user_id, editor_settings, ide_settings, keybindings, extensions)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $defaultEditor,
                $defaultIDE,
                $defaultKeybindings,
                $defaultExtensions
            ]);
        }
        
        // Buscar settings atualizadas
        $stmt = $db->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $settings = $stmt->fetch();
        
        // Decodificar JSON
        $settings['editor_settings'] = json_decode($settings['editor_settings'], true) ?? [];
        $settings['ide_settings'] = json_decode($settings['ide_settings'], true) ?? [];
        $settings['keybindings'] = json_decode($settings['keybindings'], true) ?? [];
        $settings['extensions'] = json_decode($settings['extensions'], true) ?? [];
        
        jsonResponse([
            'success' => true,
            'settings' => $settings
        ]);
    } catch (PDOException $e) {
        error_log("Error updating settings: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Erro ao atualizar configurações'], 500);
    }
}

