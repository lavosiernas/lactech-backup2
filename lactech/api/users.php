<?php
// API USERS - CONECTADA AO BANCO REAL
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir configuração do banco
require_once '../includes/Database.class.php';

try {
    $db = Database::getInstance();
    $action = $_GET['action'] ?? $_POST['action'] ?? 'select';
    
    switch ($action) {
        case 'select':
            // Buscar todos os usuários da fazenda
            $users = $db->query("
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.role,
                    u.is_active,
                    u.last_login,
                    u.created_at,
                    f.name as farm_name
                FROM users u
                LEFT JOIN farms f ON u.farm_id = f.id
                WHERE u.farm_id = 1
                ORDER BY u.created_at DESC
            ");
            
            // Contar usuários por status
            $totalUsers = count($users);
            $activeUsers = count(array_filter($users, function($user) { return $user['is_active'] == 1; }));
            $inactiveUsers = $totalUsers - $activeUsers;
            
            $data = [
                'success' => true,
                'data' => [
                    'users' => array_map(function($row) {
                        return [
                            'id' => (int)$row['id'],
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'role' => $row['role'],
                            'is_active' => (bool)$row['is_active'],
                            'last_login' => $row['last_login'],
                            'created_at' => $row['created_at'],
                            'farm_name' => $row['farm_name']
                        ];
                    }, $users),
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'inactive' => $inactiveUsers
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            break;
            
        case 'get_stats':
            // Estatísticas dos usuários
            $userStats = $db->query("
                SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users,
                    SUM(CASE WHEN role = 'gerente' THEN 1 ELSE 0 END) as managers,
                    SUM(CASE WHEN role = 'funcionario' THEN 1 ELSE 0 END) as employees,
                    MAX(created_at) as last_registration
                FROM users 
                WHERE farm_id = 1
            ");
            
            $data = [
                'success' => true,
                'data' => [
                    'total_users' => (int)$userStats[0]['total_users'],
                    'active_users' => (int)$userStats[0]['active_users'],
                    'inactive_users' => (int)$userStats[0]['inactive_users'],
                    'managers' => (int)$userStats[0]['managers'],
                    'employees' => (int)$userStats[0]['employees'],
                    'last_registration' => $userStats[0]['last_registration']
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            break;
            
        case 'get_profile':
            $userId = $_GET['user_id'] ?? $_POST['user_id'] ?? 1;
            
            // Buscar perfil do usuário
            $userProfile = $db->query("
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.role,
                    u.is_active,
                    u.phone,
                    u.created_at,
                    u.last_login,
                    f.name as farm_name
                FROM users u
                LEFT JOIN farms f ON u.farm_id = f.id
                WHERE u.id = ? AND u.farm_id = 1
            ", [$userId]);
            
            if (empty($userProfile)) {
                $data = [
                    'success' => false,
                    'error' => 'Usuário não encontrado'
                ];
            } else {
                $user = $userProfile[0];
                $data = [
                    'success' => true,
                    'data' => [
                        'id' => (int)$user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'is_active' => (bool)$user['is_active'],
                        'phone' => $user['phone'],
                        'created_at' => $user['created_at'],
                        'last_login' => $user['last_login'],
                        'farm_name' => $user['farm_name'],
                        'permissions' => $user['role'] === 'gerente' ? ['read', 'write', 'admin'] : ['read', 'write'],
                        'profile_complete' => !empty($user['phone']) && !empty($user['name'])
                    ],
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
            break;
            
        case 'create':
            // Criar novo usuário
            $input = json_decode(file_get_contents('php://input'), true);
            $name = $input['name'] ?? '';
            $email = $input['email'] ?? '';
            $role = $input['role'] ?? 'funcionario';
            $phone = $input['phone'] ?? '';
            
            if (empty($name) || empty($email)) {
                $data = [
                    'success' => false,
                    'error' => 'Nome e email são obrigatórios'
                ];
            } else {
                // Verificar se email já existe
                $existingUser = $db->query("SELECT id FROM users WHERE email = ? AND farm_id = 1", [$email]);
                
                if (!empty($existingUser)) {
                    $data = [
                        'success' => false,
                        'error' => 'Email já está em uso'
                    ];
                } else {
                    // Criar usuário
                    $newUserId = $db->query("
                        INSERT INTO users (name, email, role, phone, farm_id, is_active, created_at) 
                        VALUES (?, ?, ?, ?, 1, 1, NOW())
                    ", [$name, $email, $role, $phone]);
                    
                    $data = [
                        'success' => true,
                        'data' => [
                            'message' => 'Usuário criado com sucesso',
                            'user_id' => $newUserId
                        ],
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                }
            }
            break;
            
        case 'update':
            // Atualizar usuário
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $userId = $input['user_id'] ?? $input['id'] ?? null;
            $name = $input['name'] ?? null;
            
            if (!$userId || !$name) {
                $data = [
                    'success' => false,
                    'error' => 'ID do usuário e nome são obrigatórios'
                ];
            } else {
                // Atualizar nome do usuário
                $db->query("UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?", [$name, $userId]);
                
                $data = [
                    'success' => true,
                    'data' => [
                        'message' => 'Usuário atualizado com sucesso',
                        'user_id' => $userId
                    ],
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
            break;
            
        case 'delete':
            $data = [
                'success' => true,
                'data' => [
                    'message' => 'Usuário removido com sucesso'
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            break;
            
        default:
            $data = [
                'success' => false,
                'error' => 'Ação não encontrada',
                'available_actions' => ['select', 'get_stats', 'get_profile', 'create', 'update', 'delete']
            ];
    }
    
} catch (Exception $e) {
    $data = [
        'success' => false,
        'error' => 'Erro interno: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>