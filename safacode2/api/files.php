<?php
/**
 * SafeCode IDE - Files API
 * Endpoints: /api/files.php?action=list|get|create|update|delete|tree
 */

require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Verificar autenticação para todas as ações
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
    case 'list':
        handleListFiles($userId);
        break;
    case 'tree':
        handleGetFileTree($userId);
        break;
    case 'get':
        handleGetFile($userId);
        break;
    case 'create':
        handleCreateFile($userId);
        break;
    case 'update':
        handleUpdateFile($userId);
        break;
    case 'delete':
        handleDeleteFile($userId);
        break;
    default:
        jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
}

/**
 * Listar arquivos de um projeto (flat)
 */
function handleListFiles($userId) {
    $projectId = $_GET['project_id'] ?? $_POST['project_id'] ?? null;
    
    if (!$projectId) {
        jsonResponse(['success' => false, 'error' => 'ID do projeto não fornecido'], 400);
    }
    
    if (!hasProjectAccess($userId, $projectId)) {
        jsonResponse(['success' => false, 'error' => 'Sem permissão para acessar este projeto'], 403);
    }
    
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    $stmt = $db->prepare("SELECT * FROM files WHERE project_id = ? ORDER BY path");
    $stmt->execute([$projectId]);
    $files = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'files' => $files
    ]);
}

/**
 * Buscar árvore de arquivos do projeto
 */
function handleGetFileTree($userId) {
    $projectId = $_GET['project_id'] ?? $_POST['project_id'] ?? null;
    
    if (!$projectId) {
        jsonResponse(['success' => false, 'error' => 'ID do projeto não fornecido'], 400);
    }
    
    if (!hasProjectAccess($userId, $projectId)) {
        jsonResponse(['success' => false, 'error' => 'Sem permissão para acessar este projeto'], 403);
    }
    
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    $stmt = $db->prepare("SELECT * FROM files WHERE project_id = ? ORDER BY type DESC, path");
    $stmt->execute([$projectId]);
    $allFiles = $stmt->fetchAll();
    
    // Construir árvore
    $tree = buildFileTree($allFiles);
    
    jsonResponse([
        'success' => true,
        'tree' => $tree
    ]);
}

/**
 * Buscar arquivo específico
 */
function handleGetFile($userId) {
    $fileId = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$fileId) {
        jsonResponse(['success' => false, 'error' => 'ID do arquivo não fornecido'], 400);
    }
    
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    $stmt = $db->prepare("SELECT f.* FROM files f INNER JOIN projects p ON f.project_id = p.id WHERE f.id = ?");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();
    
    if (!$file) {
        jsonResponse(['success' => false, 'error' => 'Arquivo não encontrado'], 404);
    }
    
    if (!hasProjectAccess($userId, $file['project_id'])) {
        jsonResponse(['success' => false, 'error' => 'Sem permissão para acessar este arquivo'], 403);
    }
    
    jsonResponse([
        'success' => true,
        'file' => $file
    ]);
}

/**
 * Criar arquivo ou pasta
 */
function handleCreateFile($userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'error' => 'Método não permitido'], 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $projectId = $input['project_id'] ?? null;
    $parentId = $input['parent_id'] ?? null;
    $name = trim($input['name'] ?? '');
    $type = $input['type'] ?? 'file';
    $content = $input['content'] ?? '';
    $language = $input['language'] ?? null;
    
    if (!$projectId || empty($name)) {
        jsonResponse(['success' => false, 'error' => 'Projeto e nome são obrigatórios'], 400);
    }
    
    if (!hasProjectWriteAccess($userId, $projectId)) {
        jsonResponse(['success' => false, 'error' => 'Sem permissão para criar arquivos neste projeto'], 403);
    }
    
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    // Construir path
    $path = '/' . $name;
    if ($parentId) {
        $stmt = $db->prepare("SELECT path FROM files WHERE id = ?");
        $stmt->execute([$parentId]);
        $parent = $stmt->fetch();
        if ($parent) {
            $path = rtrim($parent['path'], '/') . '/' . $name;
        }
    }
    
    // Verificar se já existe
    $stmt = $db->prepare("SELECT id FROM files WHERE project_id = ? AND path = ?");
    $stmt->execute([$projectId, $path]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'error' => 'Arquivo ou pasta já existe neste caminho'], 409);
    }
    
    try {
        $size = $type === 'file' ? strlen($content) : 0;
        
        // Detectar language se não fornecido
        if (!$language && $type === 'file') {
            $language = detectLanguage($name);
        }
        
        $stmt = $db->prepare("
            INSERT INTO files (project_id, parent_id, name, path, type, content, language, size, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $projectId, 
            $parentId, 
            $name, 
            $path, 
            $type, 
            $type === 'file' ? $content : null,
            $language,
            $size,
            $userId
        ]);
        
        $fileId = $db->lastInsertId();
        
        // Criar versão inicial se for arquivo
        if ($type === 'file' && !empty($content)) {
            $stmt = $db->prepare("
                INSERT INTO file_versions (file_id, version_number, content, size, created_by)
                VALUES (?, 1, ?, ?, ?)
            ");
            $stmt->execute([$fileId, $content, $size, $userId]);
        }
        
        // Registrar atividade
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, project_id, file_id, action_type, action_details)
            VALUES (?, ?, ?, 'create', JSON_OBJECT('type', ?, 'name', ?))
        ");
        $stmt->execute([$userId, $projectId, $fileId, $type, $name]);
        
        // Buscar arquivo criado
        $stmt = $db->prepare("SELECT * FROM files WHERE id = ?");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();
        
        jsonResponse([
            'success' => true,
            'file' => $file
        ]);
    } catch (PDOException $e) {
        error_log("Error creating file: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Erro ao criar arquivo'], 500);
    }
}

/**
 * Atualizar arquivo
 */
function handleUpdateFile($userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'error' => 'Método não permitido'], 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $fileId = $input['id'] ?? $_GET['id'] ?? null;
    
    if (!$fileId) {
        jsonResponse(['success' => false, 'error' => 'ID do arquivo não fornecido'], 400);
    }
    
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    // Buscar arquivo
    $stmt = $db->prepare("SELECT * FROM files WHERE id = ?");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();
    
    if (!$file) {
        jsonResponse(['success' => false, 'error' => 'Arquivo não encontrado'], 404);
    }
    
    if (!hasProjectWriteAccess($userId, $file['project_id'])) {
        jsonResponse(['success' => false, 'error' => 'Sem permissão para editar este arquivo'], 403);
    }
    
    if ($file['type'] !== 'file') {
        jsonResponse(['success' => false, 'error' => 'Apenas arquivos podem ser editados'], 400);
    }
    
    try {
        $db->beginTransaction();
        
        // Obter conteúdo antigo para versão
        $oldContent = $file['content'];
        $newContent = $input['content'] ?? $oldContent;
        $newSize = strlen($newContent);
        
        // Atualizar arquivo
        $stmt = $db->prepare("
            UPDATE files 
            SET content = ?, size = ?, updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newContent, $newSize, $userId, $fileId]);
        
        // Criar nova versão se conteúdo mudou
        if ($oldContent !== $newContent) {
            $stmt = $db->prepare("
                SELECT MAX(version_number) as max_version FROM file_versions WHERE file_id = ?
            ");
            $stmt->execute([$fileId]);
            $version = $stmt->fetch();
            $nextVersion = ($version['max_version'] ?? 0) + 1;
            
            $stmt = $db->prepare("
                INSERT INTO file_versions (file_id, version_number, content, size, created_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$fileId, $nextVersion, $newContent, $newSize, $userId]);
        }
        
        // Registrar atividade
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, project_id, file_id, action_type, action_details)
            VALUES (?, ?, ?, 'update', JSON_OBJECT('type', 'file'))
        ");
        $stmt->execute([$userId, $file['project_id'], $fileId]);
        
        $db->commit();
        
        // Buscar arquivo atualizado
        $stmt = $db->prepare("SELECT * FROM files WHERE id = ?");
        $stmt->execute([$fileId]);
        $updatedFile = $stmt->fetch();
        
        jsonResponse([
            'success' => true,
            'file' => $updatedFile
        ]);
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Error updating file: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Erro ao atualizar arquivo'], 500);
    }
}

/**
 * Deletar arquivo ou pasta
 */
function handleDeleteFile($userId) {
    $fileId = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$fileId) {
        jsonResponse(['success' => false, 'error' => 'ID do arquivo não fornecido'], 400);
    }
    
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    // Buscar arquivo
    $stmt = $db->prepare("SELECT * FROM files WHERE id = ?");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();
    
    if (!$file) {
        jsonResponse(['success' => false, 'error' => 'Arquivo não encontrado'], 404);
    }
    
    if (!hasProjectWriteAccess($userId, $file['project_id'])) {
        jsonResponse(['success' => false, 'error' => 'Sem permissão para deletar este arquivo'], 403);
    }
    
    try {
        // Registrar atividade antes de deletar
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, project_id, file_id, action_type, action_details)
            VALUES (?, ?, ?, 'delete', JSON_OBJECT('type', ?, 'name', ?))
        ");
        $stmt->execute([$userId, $file['project_id'], $fileId, $file['type'], $file['name']]);
        
        // Deletar (CASCADE vai deletar filhos e versões)
        $stmt = $db->prepare("DELETE FROM files WHERE id = ?");
        $stmt->execute([$fileId]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Arquivo deletado com sucesso'
        ]);
    } catch (PDOException $e) {
        error_log("Error deleting file: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Erro ao deletar arquivo'], 500);
    }
}

/**
 * Verificar se usuário tem acesso ao projeto
 */
function hasProjectAccess($userId, $projectId) {
    $db = getDatabase();
    if (!$db) return false;
    
    $stmt = $db->prepare("
        SELECT 1 FROM projects p
        LEFT JOIN project_collaborators pc ON p.id = pc.project_id
        WHERE p.id = ? AND (p.user_id = ? OR (pc.user_id = ? AND pc.is_active = 1))
        LIMIT 1
    ");
    $stmt->execute([$projectId, $userId, $userId]);
    return (bool)$stmt->fetch();
}

/**
 * Verificar se usuário pode escrever no projeto
 */
function hasProjectWriteAccess($userId, $projectId) {
    $db = getDatabase();
    if (!$db) return false;
    
    $stmt = $db->prepare("
        SELECT 1 FROM projects p
        LEFT JOIN project_collaborators pc ON p.id = pc.project_id AND pc.user_id = ?
        WHERE p.id = ? AND (p.user_id = ? OR (pc.role IN ('owner', 'admin', 'editor') AND pc.is_active = 1))
        LIMIT 1
    ");
    $stmt->execute([$userId, $projectId, $userId]);
    return (bool)$stmt->fetch();
}

/**
 * Construir árvore de arquivos
 */
function buildFileTree($files) {
    $tree = [];
    $map = [];
    
    // Criar mapa de arquivos
    foreach ($files as $file) {
        $map[$file['id']] = [
            'id' => (string)$file['id'],
            'name' => $file['name'],
            'type' => $file['type'],
            'path' => $file['path'],
            'language' => $file['language'],
            'content' => $file['content'],
            'children' => []
        ];
    }
    
    // Construir árvore
    foreach ($files as $file) {
        if ($file['parent_id']) {
            if (isset($map[$file['parent_id']])) {
                $map[$file['parent_id']]['children'][] = &$map[$file['id']];
            }
        } else {
            $tree[] = &$map[$file['id']];
        }
    }
    
    return $tree;
}

/**
 * Detectar linguagem pelo nome do arquivo
 */
function detectLanguage($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $languages = [
        'js' => 'javascript',
        'jsx' => 'javascript',
        'ts' => 'typescript',
        'tsx' => 'typescript',
        'py' => 'python',
        'html' => 'html',
        'css' => 'css',
        'json' => 'json',
        'md' => 'markdown',
        'php' => 'php',
        'java' => 'java',
        'cpp' => 'cpp',
        'c' => 'c',
        'go' => 'go',
        'rs' => 'rust',
        'rb' => 'ruby',
    ];
    
    return $languages[$extension] ?? 'plaintext';
}

