<?php
/**
 * SafeCode IDE - Projects API
 * Endpoints: /api/projects.php?action=list|get|create|update|delete
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
        handleListProjects($userId);
        break;
    case 'get':
        handleGetProject($userId);
        break;
    case 'create':
        handleCreateProject($userId);
        break;
    case 'update':
        handleUpdateProject($userId);
        break;
    case 'delete':
        handleDeleteProject($userId);
        break;
    default:
        jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
}

/**
 * Listar projetos do usuário
 */
function handleListProjects($userId) {
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    // Buscar projetos onde o usuário é owner ou colaborador
    $stmt = $db->prepare("
        SELECT DISTINCT p.*, 
               (SELECT COUNT(*) FROM files WHERE project_id = p.id) as file_count,
               (SELECT COUNT(*) FROM project_collaborators WHERE project_id = p.id AND is_active = 1) as collaborator_count
        FROM projects p
        LEFT JOIN project_collaborators pc ON p.id = pc.project_id
        WHERE (p.user_id = ? OR (pc.user_id = ? AND pc.is_active = 1))
        ORDER BY p.updated_at DESC
    ");
    $stmt->execute([$userId, $userId]);
    $projects = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'projects' => $projects
    ]);
}

/**
 * Buscar projeto específico
 */
function handleGetProject($userId) {
    $projectId = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$projectId) {
        jsonResponse(['success' => false, 'error' => 'ID do projeto não fornecido'], 400);
    }
    
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    // Verificar se usuário tem acesso ao projeto
    $stmt = $db->prepare("
        SELECT p.* FROM projects p
        LEFT JOIN project_collaborators pc ON p.id = pc.project_id
        WHERE p.id = ? AND (p.user_id = ? OR (pc.user_id = ? AND pc.is_active = 1))
    ");
    $stmt->execute([$projectId, $userId, $userId]);
    $project = $stmt->fetch();
    
    if (!$project) {
        jsonResponse(['success' => false, 'error' => 'Projeto não encontrado ou sem permissão'], 404);
    }
    
    // Atualizar último acesso
    $updateStmt = $db->prepare("UPDATE projects SET last_accessed_at = NOW() WHERE id = ?");
    $updateStmt->execute([$projectId]);
    
    jsonResponse([
        'success' => true,
        'project' => $project
    ]);
}

/**
 * Criar novo projeto
 */
function handleCreateProject($userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'error' => 'Método não permitido'], 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    $color = $input['color'] ?? null;
    $defaultLanguage = $input['default_language'] ?? null;
    
    if (empty($name)) {
        jsonResponse(['success' => false, 'error' => 'Nome do projeto é obrigatório'], 400);
    }
    
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    // Gerar slug único
    $slug = generateSlug($name);
    $baseSlug = $slug;
    $counter = 1;
    
    while (true) {
        $stmt = $db->prepare("SELECT id FROM projects WHERE slug = ?");
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) {
            break;
        }
        $slug = $baseSlug . '-' . $counter++;
    }
    
    try {
        $db->beginTransaction();
        
        // Criar projeto
        $stmt = $db->prepare("
            INSERT INTO projects (user_id, name, slug, description, color, default_language)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $name, $slug, $description, $color, $defaultLanguage]);
        $projectId = $db->lastInsertId();
        
        // Adicionar owner como colaborador
        $stmt = $db->prepare("
            INSERT INTO project_collaborators (project_id, user_id, role, joined_at)
            VALUES (?, ?, 'owner', NOW())
        ");
        $stmt->execute([$projectId, $userId]);
        
        // Registrar atividade
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, project_id, action_type, action_details)
            VALUES (?, ?, 'create', JSON_OBJECT('type', 'project', 'name', ?))
        ");
        $stmt->execute([$userId, $projectId, $name]);
        
        $db->commit();
        
        // Buscar projeto criado
        $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch();
        
        jsonResponse([
            'success' => true,
            'project' => $project
        ]);
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Error creating project: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Erro ao criar projeto'], 500);
    }
}

/**
 * Atualizar projeto
 */
function handleUpdateProject($userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'error' => 'Método não permitido'], 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $projectId = $input['id'] ?? $_GET['id'] ?? null;
    
    if (!$projectId) {
        jsonResponse(['success' => false, 'error' => 'ID do projeto não fornecido'], 400);
    }
    
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    // Verificar permissão (owner ou admin)
    $stmt = $db->prepare("
        SELECT p.user_id, pc.role 
        FROM projects p
        LEFT JOIN project_collaborators pc ON p.id = pc.project_id AND pc.user_id = ?
        WHERE p.id = ? AND (p.user_id = ? OR (pc.role IN ('owner', 'admin') AND pc.is_active = 1))
    ");
    $stmt->execute([$userId, $projectId, $userId]);
    $access = $stmt->fetch();
    
    if (!$access) {
        jsonResponse(['success' => false, 'error' => 'Sem permissão para editar este projeto'], 403);
    }
    
    // Montar query de atualização
    $fields = [];
    $values = [];
    
    if (isset($input['name'])) {
        $fields[] = "name = ?";
        $values[] = trim($input['name']);
    }
    if (isset($input['description'])) {
        $fields[] = "description = ?";
        $values[] = trim($input['description']);
    }
    if (isset($input['color'])) {
        $fields[] = "color = ?";
        $values[] = $input['color'];
    }
    if (isset($input['default_language'])) {
        $fields[] = "default_language = ?";
        $values[] = $input['default_language'];
    }
    if (isset($input['is_public'])) {
        $fields[] = "is_public = ?";
        $values[] = (bool)$input['is_public'];
    }
    
    if (empty($fields)) {
        jsonResponse(['success' => false, 'error' => 'Nenhum campo para atualizar'], 400);
    }
    
    $values[] = $projectId;
    $sql = "UPDATE projects SET " . implode(', ', $fields) . " WHERE id = ?";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        
        // Registrar atividade
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, project_id, action_type, action_details)
            VALUES (?, ?, 'update', JSON_OBJECT('type', 'project'))
        ");
        $stmt->execute([$userId, $projectId]);
        
        // Buscar projeto atualizado
        $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch();
        
        jsonResponse([
            'success' => true,
            'project' => $project
        ]);
    } catch (PDOException $e) {
        error_log("Error updating project: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Erro ao atualizar projeto'], 500);
    }
}

/**
 * Deletar projeto
 */
function handleDeleteProject($userId) {
    $projectId = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$projectId) {
        jsonResponse(['success' => false, 'error' => 'ID do projeto não fornecido'], 400);
    }
    
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    // Verificar se é owner
    $stmt = $db->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$projectId, $userId]);
    $project = $stmt->fetch();
    
    if (!$project) {
        jsonResponse(['success' => false, 'error' => 'Projeto não encontrado ou sem permissão para deletar'], 403);
    }
    
    try {
        // Foreign keys com CASCADE vão deletar arquivos, colaboradores, etc automaticamente
        $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Projeto deletado com sucesso'
        ]);
    } catch (PDOException $e) {
        error_log("Error deleting project: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Erro ao deletar projeto'], 500);
    }
}

/**
 * Gerar slug a partir do nome
 */
function generateSlug($name) {
    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'project';
}

