<?php
/**
 * API de Ações - Lactech
 * Endpoint para ações específicas do sistema
 */

// Configurar timezone para horário local (Brasil)
if (!ini_get('date.timezone')) {
    date_default_timezone_set('America/Sao_Paulo');
}

 // Configurações de segurança
error_reporting(0);
ini_set('display_errors', 0);

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação (modo teste - permitir acesso)
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['success' => false, 'error' => 'Acesso negado']);
//     exit;
// }

// Verificar se Database.class.php existe
$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Erro no servidor: Database.class.php não encontrado']);
    exit;
}

require_once $dbPath;

try {
    $db = Database::getInstance();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    switch ($action) {
        case 'dashboard':
            // Estatísticas do dashboard
            $stats = [];
            
            // Volume de leite hoje
            $results = $db->query("SELECT COALESCE(SUM(total_volume), 0) as total FROM volume_records WHERE DATE(record_date) = CURDATE() AND farm_id = 1");
            $stats['volume_today'] = $results[0]['total'] ?? 0;
            
            // Volume de leite este mês
            $results = $db->query("SELECT COALESCE(SUM(total_volume), 0) as total FROM volume_records WHERE MONTH(record_date) = MONTH(CURDATE()) AND YEAR(record_date) = YEAR(CURDATE()) AND farm_id = 1");
            $stats['volume_month'] = $results[0]['total'] ?? 0;
            
            // Média de gordura
            $results = $db->query("SELECT COALESCE(AVG(fat_content), 0) as avg FROM quality_tests WHERE MONTH(test_date) = MONTH(CURDATE()) AND YEAR(test_date) = YEAR(CURDATE()) AND farm_id = 1");
            $stats['avg_fat'] = round($results[0]['avg'] ?? 0, 2);
            
            // Média de proteína
            $results = $db->query("SELECT COALESCE(AVG(protein_content), 0) as avg FROM quality_tests WHERE MONTH(test_date) = MONTH(CURDATE()) AND YEAR(test_date) = YEAR(CURDATE()) AND farm_id = 1");
            $stats['avg_protein'] = round($results[0]['avg'] ?? 0, 2);
            
            // Pagamentos pendentes
            $results = $db->query("SELECT COUNT(*) as total FROM financial_records WHERE type = 'receita' AND farm_id = 1");
            $stats['pending_payments'] = $results[0]['total'] ?? 0;
            
            // Usuários ativos
            $results = $db->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1 AND farm_id = 1");
            $stats['active_users'] = $results[0]['total'] ?? 0;
            
            // Total de animais
            $results = $db->query("SELECT COUNT(*) as total FROM animals WHERE is_active = 1 AND farm_id = 1");
            $stats['total_animals'] = $results[0]['total'] ?? 0;
            
            // Gestações ativas
            $results = $db->query("SELECT COUNT(*) as total FROM pregnancy_controls WHERE expected_birth >= CURDATE() AND farm_id = 1");
            $stats['active_pregnancies'] = $results[0]['total'] ?? 0;
            
            // Alertas ativos
            $results = $db->query("SELECT COUNT(*) as total FROM health_alerts WHERE is_resolved = 0 AND farm_id = 1");
            $stats['active_alerts'] = $results[0]['total'] ?? 0;
            
            // Buscar nome da fazenda do banco
            $results = $db->query("SELECT name FROM farms WHERE id = 1");
            $farmName = $results[0]['name'] ?? 'Fazenda';
            
            echo json_encode([
                'success' => true,
                'data' => $stats,
                'farm_name' => $farmName
            ]);
            break;
            
        case 'urgent_actions':
            // Ações urgentes
            $urgentActions = [];
            
            // Solicitações de senha pendentes
            $results = $db->query("SELECT COUNT(*) as total FROM password_requests WHERE is_used = 0 AND expires_at > NOW()");
            $passwordRequests = $results[0]['total'] ?? 0;
            
            if ($passwordRequests > 0) {
                $urgentActions[] = [
                    'type' => 'password_request',
                    'message' => "$passwordRequests solicitação(ões) de senha pendente(s)",
                    'priority' => 'high'
                ];
            }
            
            // Testes de qualidade pendentes
            $results = $db->query("SELECT COUNT(*) as total FROM quality_tests WHERE test_type = 'qualidade_leite' AND farm_id = 1");
            $qualityTests = $results[0]['total'] ?? 0;
            
            if ($qualityTests > 0) {
                $urgentActions[] = [
                    'type' => 'quality_test',
                    'message' => "$qualityTests teste(s) de qualidade pendente(s)",
                    'priority' => 'medium'
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $urgentActions
            ]);
            break;
            
        case 'add_volume_general':
            // Registrar volume geral (volume_records)
            $total_volume = isset($_POST['total_volume']) ? (float)$_POST['total_volume'] : 0;
            $total_animals = isset($_POST['total_animals']) ? (int)$_POST['total_animals'] : 1;
            
            // Validar que tem pelo menos 1 vaca
            if ($total_animals < 1) {
                echo json_encode(['success' => false, 'error' => 'Informe o número de vacas participantes']);
                exit;
            }
            
            $data = [
                'collection_date' => $_POST['collection_date'] ?? date('Y-m-d'),
                'period' => $_POST['period'] ?? 'manha',
                'volume' => $total_volume,
                'total_animals' => $total_animals,
                'temperature' => isset($_POST['temperature']) && $_POST['temperature'] !== '' ? (float)$_POST['temperature'] : null,
                'notes' => $_POST['notes'] ?? null,
                'recorded_by' => $_SESSION['user_id'] ?? 1,
            ];
            
            // Salvar registro de volume (isso já cria notificações automaticamente)
            $result = $db->addVolumeRecord($data);
            
            // Retornar resultado incluindo informação sobre notificações
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'id' => $result['id'],
                    'message' => 'Volume registrado com sucesso! Os gerentes foram notificados.'
                ]);
            } else {
                echo json_encode($result);
            }
            break;
            
        case 'delete_all_volume_records':
            // Excluir todos os registros de volume com backup
            $result = $db->deleteAllVolumeRecords();
            echo json_encode($result);
            break;
            
        case 'restore_volume_records':
            // Restaurar registros de volume do backup
            $backupKey = $_POST['backup_key'] ?? $_GET['backup_key'] ?? null;
            if (!$backupKey) {
                echo json_encode(['success' => false, 'error' => 'Chave de backup não fornecida']);
                exit;
            }
            $result = $db->restoreVolumeRecords($backupKey);
            echo json_encode($result);
            break;

        case 'add_volume_by_animal':
            // Registrar volume por animal (milk_production)
            // Validações
            if (empty($_POST['animal_id'])) {
                echo json_encode(['success' => false, 'error' => 'Por favor, selecione uma vaca']);
                exit;
            }
            
            if (empty($_POST['volume']) || (float)$_POST['volume'] <= 0) {
                echo json_encode(['success' => false, 'error' => 'Volume deve ser maior que zero']);
                exit;
            }
            
            $data = [
                'producer_id' => (int)$_POST['animal_id'],
                'collection_date' => $_POST['collection_date'] ?? date('Y-m-d'),
                'period' => $_POST['period'] ?? 'manha',
                'volume' => (float)$_POST['volume'],
                'temperature' => !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null,
                'notes' => !empty($_POST['notes']) ? trim($_POST['notes']) : null,
                'recorded_by' => $_SESSION['user_id'] ?? 1,
            ];
            
            $result = $db->addVolumeRecord($data);
            echo json_encode($result);
            break;

        case 'add_quality_test':
            // Registrar teste de qualidade (quality_tests)
            $data = [
                'test_date' => $_POST['test_date'] ?? date('Y-m-d'),
                'fat_percentage' => isset($_POST['fat_content']) ? (float)$_POST['fat_content'] : null,
                'protein_percentage' => isset($_POST['protein_content']) ? (float)$_POST['protein_content'] : null,
                'ccs' => isset($_POST['somatic_cells']) ? (int)$_POST['somatic_cells'] : null,
                'tested_by' => $_SESSION['user_id'] ?? 1,
            ];
            $result = $db->addQualityTest($data);
            echo json_encode($result);
            break;

        case 'add_financial_record':
            // Registrar receita/despesa (financial_records)
            $data = [
                'due_date' => $_POST['record_date'] ?? date('Y-m-d'),
                'type' => $_POST['type'] ?? 'receita',
                'description' => $_POST['description'] ?? '',
                'amount' => isset($_POST['amount']) ? (float)$_POST['amount'] : 0,
                'created_by' => $_SESSION['user_id'] ?? 1,
            ];
            $result = $db->addFinancialRecord($data);
            echo json_encode($result);
            break;

        case 'create_user':
        case 'add_user':
            // Criar usuário (users)
            $userData = [
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'role' => $_POST['role'] ?? 'funcionario',
                'farm_id' => $_SESSION['farm_id'] ?? 1,
                'cpf' => $_POST['cpf'] ?? null,
                'phone' => $_POST['phone'] ?? null,
            ];
            
            // Processar upload de foto se fornecido
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/profiles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $file = $_FILES['profile_photo'];
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($file['type'], $allowedTypes)) {
                    echo json_encode(['success' => false, 'error' => 'Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WEBP.']);
                    exit;
                }
                
                if ($file['size'] > $maxSize) {
                    echo json_encode(['success' => false, 'error' => 'Arquivo muito grande. Tamanho máximo: 5MB.']);
                    exit;
                }
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                // Usar timestamp para nome único (o ID do usuário será adicionado depois)
                $filename = 'profile_' . time() . '_' . uniqid() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $userData['profile_photo'] = 'uploads/profiles/' . $filename;
                } else {
                    echo json_encode(['success' => false, 'error' => 'Erro ao salvar a foto.']);
                    exit;
                }
            }
            
            $result = $db->createUser($userData);
            
            // Se o usuário foi criado com sucesso e há foto, atualizar o nome do arquivo com o ID do usuário
            if ($result['success'] && isset($result['data']['user_id']) && isset($userData['profile_photo'])) {
                $userId = $result['data']['user_id'];
                $oldPath = __DIR__ . '/../' . $userData['profile_photo'];
                $extension = pathinfo($userData['profile_photo'], PATHINFO_EXTENSION);
                $newFilename = 'profile_' . $userId . '_' . time() . '.' . $extension;
                $newPath = __DIR__ . '/../uploads/profiles/' . $newFilename;
                
                if (file_exists($oldPath) && rename($oldPath, $newPath)) {
                    $newPhotoPath = 'uploads/profiles/' . $newFilename;
                    $pdo = $db->getConnection();
                    $updateStmt = $pdo->prepare("UPDATE users SET profile_photo = :photo WHERE id = :id");
                    $updateStmt->execute([':photo' => $newPhotoPath, ':id' => $userId]);
                    $result['data']['profile_photo'] = $newPhotoPath;
                }
            }
            
            echo json_encode($result);
            break;

        case 'update_profile':
            // Atualizar perfil do usuário
            $user_id = $_SESSION['user_id'] ?? 1;
            $pdo = $db->getConnection();
            $updates = [];
            $params = [];
            
            try {
                // Preparar arrays para UPDATE do usuário
                $userUpdates = [];
                $userParams = [];
                
                // Atualizar campos básicos do usuário (tabela users)
                if (isset($_POST['name'])) {
                    $userUpdates[] = "name = :user_name";
                    $userParams[':user_name'] = trim($_POST['name']);
                }
                if (isset($_POST['phone'])) {
                    $userUpdates[] = "phone = :user_phone";
                    $userParams[':user_phone'] = trim($_POST['phone']);
                }
                
                // Upload de foto
                $profilePhotoPath = null;
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../uploads/profiles/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $file = $_FILES['profile_photo'];
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    $maxSize = 5 * 1024 * 1024; // 5MB
                    
                    if (!in_array($file['type'], $allowedTypes)) {
                        echo json_encode(['success' => false, 'error' => 'Tipo de arquivo não permitido. Use JPG, PNG ou GIF.']);
                        exit;
                    }
                    
                    if ($file['size'] > $maxSize) {
                        echo json_encode(['success' => false, 'error' => 'Arquivo muito grande. Tamanho máximo: 5MB.']);
                        exit;
                    }
                    
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        // Remover foto antiga se existir
                        $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = :id");
                        $stmt->execute([':id' => $user_id]);
                        $oldPhoto = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (!empty($oldPhoto['profile_photo']) && file_exists(__DIR__ . '/../' . $oldPhoto['profile_photo'])) {
                            @unlink(__DIR__ . '/../' . $oldPhoto['profile_photo']);
                        }
                        
                        $profilePhotoPath = 'uploads/profiles/' . $filename;
                        $userUpdates[] = "profile_photo = :user_profile_photo";
                        $userParams[':user_profile_photo'] = $profilePhotoPath;
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Erro ao salvar a foto.']);
                        exit;
                    }
                }
                
                // Atualizar senha se fornecida
                if (!empty($_POST['password'])) {
                    $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $userUpdates[] = "password = :user_password";
                    $userUpdates[] = "password_changed_at = NOW()";
                    $userParams[':user_password'] = $passwordHash;
                }
                
                // Executar UPDATE na tabela users se houver campos para atualizar
                if (!empty($userUpdates)) {
                    $userParams[':user_id'] = $user_id;
                    $sql = "UPDATE users SET " . implode(', ', $userUpdates) . " WHERE id = :user_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($userParams);
                    
                    // ATUALIZAR SESSÃO com os novos valores salvos
                    if (isset($_POST['name'])) {
                        $_SESSION['user_name'] = trim($_POST['name']);
                    }
                }
                
                // Atualizar dados da fazenda (tabela farms) - sempre atualizar se os campos existirem no POST
                // Isso permite tanto atualizar quanto limpar campos (setando NULL ou string vazia)
                if (isset($_POST['farm_name'])) {
                    $stmt = $pdo->prepare("UPDATE farms SET name = :farm_name WHERE id = 1");
                    $stmt->execute([':farm_name' => trim($_POST['farm_name'])]);
                }
                if (isset($_POST['farm_phone'])) {
                    $phone_value = trim($_POST['farm_phone']);
                    // Converter string vazia para NULL se necessário
                    $phone_value = $phone_value === '' ? null : $phone_value;
                    $stmt = $pdo->prepare("UPDATE farms SET phone = :farm_phone WHERE id = 1");
                    $stmt->execute([':farm_phone' => $phone_value]);
                }
                if (isset($_POST['farm_cnpj'])) {
                    $cnpj_value = trim($_POST['farm_cnpj']);
                    // Converter string vazia para NULL se necessário
                    $cnpj_value = $cnpj_value === '' ? null : $cnpj_value;
                    $stmt = $pdo->prepare("UPDATE farms SET cnpj = :farm_cnpj WHERE id = 1");
                    $stmt->execute([':farm_cnpj' => $cnpj_value]);
                }
                if (isset($_POST['farm_address'])) {
                    $stmt = $pdo->prepare("UPDATE farms SET address = :farm_address WHERE id = 1");
                    $stmt->execute([':farm_address' => trim($_POST['farm_address'])]);
                }
                
                // Buscar dados atualizados do usuário
                $stmt = $pdo->prepare("SELECT id, name, email, phone, profile_photo FROM users WHERE id = :id");
                $stmt->execute([':id' => $user_id]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Buscar dados atualizados da fazenda
                $stmt = $pdo->prepare("SELECT name, phone, cnpj, address FROM farms WHERE id = 1");
                $stmt->execute();
                $farmData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Garantir que os valores não sejam null
                if (!$userData) {
                    $userData = ['id' => $user_id, 'name' => '', 'email' => '', 'phone' => '', 'profile_photo' => null];
                }
                if (!$farmData) {
                    $farmData = ['name' => '', 'phone' => '', 'cnpj' => '', 'address' => ''];
                }
                
                // Garantir que valores NULL sejam convertidos para strings vazias
                echo json_encode([
                    'success' => true,
                    'message' => 'Perfil atualizado com sucesso!',
                    'data' => [
                        'user' => [
                            'id' => $userData['id'] ?? $user_id,
                            'name' => $userData['name'] ?? '',
                            'email' => $userData['email'] ?? '',
                            'phone' => $userData['phone'] ?? '',
                            'profile_photo' => $userData['profile_photo'] ?? null
                        ],
                        'farm' => [
                            'name' => $farmData['name'] ?? '',
                            'phone' => $farmData['phone'] ?? '',
                            'cnpj' => $farmData['cnpj'] ?? '',
                            'address' => $farmData['address'] ?? ''
                        ]
                    ]
                ], JSON_UNESCAPED_UNICODE);
                
            } catch (Exception $e) {
                error_log("Erro ao atualizar perfil: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'error' => 'Erro ao atualizar perfil: ' . $e->getMessage()
                ]);
            }
            break;
        
        case 'get_active_sessions':
            // Listar sessões ativas do usuário
            $user_id = $_SESSION['user_id'] ?? null;
            if (!$user_id) {
                echo json_encode(['success' => false, 'error' => 'Não autenticado']);
                exit;
            }
            
            try {
                $pdo = $db->getConnection();
                
                // Criar tabela de sessões se não existir
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS user_sessions (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        session_id VARCHAR(255) NOT NULL,
                        ip_address VARCHAR(45),
                        user_agent TEXT,
                        device_type VARCHAR(20),
                        device_name VARCHAR(255),
                        location VARCHAR(255),
                        latitude DECIMAL(10, 8) NULL,
                        longitude DECIMAL(11, 8) NULL,
                        isp VARCHAR(255) NULL,
                        timezone VARCHAR(100) NULL,
                        country_code VARCHAR(2) NULL,
                        region_code VARCHAR(10) NULL,
                        city VARCHAR(100) NULL,
                        last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_user_id (user_id),
                        INDEX idx_session_id (session_id),
                        INDEX idx_last_activity (last_activity),
                        INDEX idx_ip_device (ip_address, device_name(50))
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                // Adicionar novas colunas se não existirem
                $columnsToAdd = [
                    'latitude' => 'DECIMAL(10, 8) NULL',
                    'longitude' => 'DECIMAL(11, 8) NULL',
                    'isp' => 'VARCHAR(255) NULL',
                    'timezone' => 'VARCHAR(100) NULL',
                    'country_code' => 'VARCHAR(2) NULL',
                    'region_code' => 'VARCHAR(10) NULL',
                    'city' => 'VARCHAR(100) NULL'
                ];
                
                foreach ($columnsToAdd as $column => $definition) {
                    try {
                        $checkColumn = $pdo->query("SHOW COLUMNS FROM user_sessions LIKE '$column'");
                        if ($checkColumn->rowCount() == 0) {
                            $pdo->exec("ALTER TABLE user_sessions ADD COLUMN $column $definition");
                        }
                    } catch (Exception $e) {
                        // Coluna já existe ou erro ao adicionar
                    }
                }
                
                // Buscar sessões ativas do usuário (últimas 30 dias)
                // Agrupar por IP + device_name para evitar duplicatas
                $stmt = $pdo->prepare("
                    SELECT 
                        MAX(id) as id,
                        MAX(session_id) as session_id,
                        ip_address,
                        MAX(user_agent) as user_agent,
                        MAX(device_type) as device_type,
                        MAX(device_name) as device_name,
                        MAX(location) as location,
                        MAX(latitude) as latitude,
                        MAX(longitude) as longitude,
                        MAX(gps_latitude) as gps_latitude,
                        MAX(gps_longitude) as gps_longitude,
                        MAX(gps_accuracy) as gps_accuracy,
                        MAX(isp) as isp,
                        MAX(timezone) as timezone,
                        MAX(country_code) as country_code,
                        MAX(region_code) as region_code,
                        MAX(city) as city,
                        MAX(last_activity) as last_activity,
                        MIN(created_at) as created_at,
                        COUNT(*) as session_count
                    FROM user_sessions
                    WHERE user_id = :user_id
                    AND last_activity >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY ip_address, device_name
                    ORDER BY last_activity DESC
                ");
                $stmt->execute([':user_id' => $user_id]);
                $sessions = $stmt->fetchAll();
                
                // Obter IP atual e session_id atual
                $currentIP = $_SERVER['REMOTE_ADDR'] ?? '';
                $currentSessionId = session_id();
                
                // Processar sessões
                $formattedSessions = [];
                foreach ($sessions as $session) {
                    // Verificar se alguma sessão deste dispositivo é a atual
                    $checkCurrent = $pdo->prepare("
                        SELECT COUNT(*) as count 
                        FROM user_sessions 
                        WHERE user_id = :user_id 
                        AND ip_address = :ip_address 
                        AND device_name = :device_name 
                        AND session_id = :session_id
                    ");
                    $checkCurrent->execute([
                        ':user_id' => $user_id,
                        ':ip_address' => $session['ip_address'],
                        ':device_name' => $session['device_name'],
                        ':session_id' => $currentSessionId
                    ]);
                    $isCurrent = $checkCurrent->fetch()['count'] > 0;
                    
                    // Converter coordenadas para float ou null
                    // Priorizar coordenadas GPS (mais precisas) sobre coordenadas do IP
                    $latitude = null;
                    $longitude = null;
                    $gps_latitude = null;
                    $gps_longitude = null;
                    $gps_accuracy = null;
                    
                    // Primeiro tentar coordenadas GPS (mais precisas)
                    if (!empty($session['gps_latitude']) && is_numeric($session['gps_latitude']) &&
                        !empty($session['gps_longitude']) && is_numeric($session['gps_longitude'])) {
                        $gps_latitude = (float)$session['gps_latitude'];
                        $gps_longitude = (float)$session['gps_longitude'];
                        $latitude = $gps_latitude;
                        $longitude = $gps_longitude;
                        if (!empty($session['gps_accuracy']) && is_numeric($session['gps_accuracy'])) {
                            $gps_accuracy = (float)$session['gps_accuracy'];
                        }
                    } 
                    // Fallback para coordenadas do IP
                    elseif (!empty($session['latitude']) && is_numeric($session['latitude']) &&
                            !empty($session['longitude']) && is_numeric($session['longitude'])) {
                        $latitude = (float)$session['latitude'];
                        $longitude = (float)$session['longitude'];
                    }
                    
                    $formattedSessions[] = [
                        'id' => $session['id'],
                        'device' => $session['device_name'] ?: ($session['device_type'] === 'mobile' ? 'Dispositivo Móvel' : 'Computador'),
                        'device_type' => $session['device_type'] ?: 'desktop',
                        'location' => $session['location'] ?: 'Não identificado',
                        'ip' => $session['ip_address'] ?: 'N/A',
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'gps_latitude' => $gps_latitude,
                        'gps_longitude' => $gps_longitude,
                        'gps_accuracy' => $gps_accuracy,
                        'isp' => $session['isp'] ?: null,
                        'timezone' => $session['timezone'] ?: null,
                        'country_code' => $session['country_code'] ?: null,
                        'region_code' => $session['region_code'] ?: null,
                        'city' => $session['city'] ?: null,
                        'lastActive' => $session['last_activity'],
                        'createdAt' => $session['created_at'],
                        'userAgent' => $session['user_agent'] ?: '',
                        'sessionCount' => $session['session_count'] ?? 1,
                        'current' => $isCurrent
                    ];
                }
                
                echo json_encode([
                    'success' => true,
                    'sessions' => $formattedSessions
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Erro ao buscar sessões: ' . $e->getMessage()]);
            }
            break;
        
        case 'revoke_session':
            // Encerrar uma sessão específica
            $user_id = $_SESSION['user_id'] ?? null;
            if (!$user_id) {
                echo json_encode(['success' => false, 'error' => 'Não autenticado']);
                exit;
            }
            
            $session_id = $_POST['session_id'] ?? $_GET['session_id'] ?? null;
            $device_id = $_POST['device_id'] ?? $_GET['device_id'] ?? null;
            
            try {
                $pdo = $db->getConnection();
                
                if ($device_id) {
                    // Encerrar por ID do dispositivo
                    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE id = :id AND user_id = :user_id");
                    $stmt->execute([':id' => $device_id, ':user_id' => $user_id]);
                } elseif ($session_id) {
                    // Encerrar por session_id
                    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_id = :session_id AND user_id = :user_id");
                    $stmt->execute([':session_id' => $session_id, ':user_id' => $user_id]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'ID da sessão não informado']);
                    exit;
                }
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Sessão encerrada com sucesso']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Sessão não encontrada']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Erro ao encerrar sessão: ' . $e->getMessage()]);
            }
            break;
        
        case 'register_session':
            // Registrar nova sessão (chamado automaticamente no login)
            $user_id = $_SESSION['user_id'] ?? null;
            if (!$user_id) {
                echo json_encode(['success' => false, 'error' => 'Não autenticado']);
                exit;
            }
            
            try {
                $pdo = $db->getConnection();
                
                // Criar tabela se não existir
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS user_sessions (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        session_id VARCHAR(255) NOT NULL,
                        ip_address VARCHAR(45),
                        user_agent TEXT,
                        device_type VARCHAR(20),
                        device_name VARCHAR(255),
                        location VARCHAR(255),
                        latitude DECIMAL(10, 8) NULL,
                        longitude DECIMAL(11, 8) NULL,
                        gps_latitude DECIMAL(10, 8) NULL COMMENT 'Coordenada GPS precisa do navegador',
                        gps_longitude DECIMAL(11, 8) NULL COMMENT 'Coordenada GPS precisa do navegador',
                        gps_accuracy DECIMAL(10, 2) NULL COMMENT 'Precisão em metros',
                        isp VARCHAR(255) NULL,
                        timezone VARCHAR(100) NULL,
                        country_code VARCHAR(2) NULL,
                        region_code VARCHAR(10) NULL,
                        city VARCHAR(100) NULL,
                        last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_user_id (user_id),
                        INDEX idx_session_id (session_id),
                        INDEX idx_last_activity (last_activity),
                        INDEX idx_ip_device (ip_address, device_name(50))
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                // Adicionar novas colunas se não existirem
                $columnsToAdd = [
                    'gps_latitude' => 'DECIMAL(10, 8) NULL COMMENT \'Coordenada GPS precisa do navegador\'',
                    'gps_longitude' => 'DECIMAL(11, 8) NULL COMMENT \'Coordenada GPS precisa do navegador\'',
                    'gps_accuracy' => 'DECIMAL(10, 2) NULL COMMENT \'Precisão em metros\''
                ];
                
                foreach ($columnsToAdd as $column => $definition) {
                    try {
                        $checkColumn = $pdo->query("SHOW COLUMNS FROM user_sessions LIKE '$column'");
                        if ($checkColumn->rowCount() == 0) {
                            $pdo->exec("ALTER TABLE user_sessions ADD COLUMN $column $definition");
                        }
                    } catch (Exception $e) {
                        // Coluna já existe ou erro ao adicionar
                    }
                }
                
                $session_id = session_id();
                
                // Verificar se o cliente enviou IP público (para ambientes locais)
                $publicIP = $_POST['public_ip'] ?? $_GET['public_ip'] ?? null;
                
                // Obter coordenadas GPS precisas do navegador (se disponíveis)
                $gps_latitude = null;
                $gps_longitude = null;
                $gps_accuracy = null;
                
                if (isset($_POST['gps_latitude']) && isset($_POST['gps_longitude'])) {
                    $gps_lat = filter_var($_POST['gps_latitude'], FILTER_VALIDATE_FLOAT);
                    $gps_lng = filter_var($_POST['gps_longitude'], FILTER_VALIDATE_FLOAT);
                    
                    if ($gps_lat !== false && $gps_lng !== false && 
                        $gps_lat >= -90 && $gps_lat <= 90 && 
                        $gps_lng >= -180 && $gps_lng <= 180) {
                        $gps_latitude = (float)$gps_lat;
                        $gps_longitude = (float)$gps_lng;
                        
                        if (isset($_POST['gps_accuracy'])) {
                            $gps_accuracy = filter_var($_POST['gps_accuracy'], FILTER_VALIDATE_FLOAT);
                            if ($gps_accuracy !== false && $gps_accuracy > 0) {
                                $gps_accuracy = (float)$gps_accuracy;
                            } else {
                                $gps_accuracy = null;
                            }
                        }
                    }
                }
                
                // Obter IP real (considerando proxies e headers alternativos)
                $ip_address = '';
                
                // Se foi enviado IP público, usar ele primeiro
                if (!empty($publicIP) && filter_var($publicIP, FILTER_VALIDATE_IP) && 
                    $publicIP !== '127.0.0.1' && $publicIP !== '::1' &&
                    strpos($publicIP, '192.168.') !== 0 &&
                    strpos($publicIP, '10.') !== 0 &&
                    strpos($publicIP, '172.') !== 0) {
                    $ip_address = $publicIP;
                } else {
                    // Verificar headers de proxy primeiro (para produção)
                    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                        $ip_address = trim($ips[0]);
                        // Validar IP
                        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
                            $ip_address = '';
                        }
                    }
                    
                    if (empty($ip_address) && !empty($_SERVER['HTTP_X_REAL_IP'])) {
                        $ip_address = $_SERVER['HTTP_X_REAL_IP'];
                        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
                            $ip_address = '';
                        }
                    }
                    
                    if (empty($ip_address) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
                        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
                        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
                            $ip_address = '';
                        }
                    }
                    
                    // Fallback para REMOTE_ADDR
                    if (empty($ip_address) && !empty($_SERVER['REMOTE_ADDR'])) {
                        $ip_address = $_SERVER['REMOTE_ADDR'];
                    }
                    
                    // Se ainda for localhost, tentar pegar IP da máquina local
                    if (empty($ip_address) || $ip_address === '127.0.0.1' || $ip_address === '::1') {
                        if (function_exists('gethostname')) {
                            $hostname = gethostname();
                            if ($hostname) {
                                $localIP = @gethostbyname($hostname);
                                if ($localIP && $localIP !== $hostname && $localIP !== '127.0.0.1' && $localIP !== '127.0.1.1') {
                                    $ip_address = $localIP;
                                }
                            }
                        }
                        
                        // Se ainda for localhost, manter mas indicar que é local
                        if ($ip_address === '127.0.0.1' || $ip_address === '::1' || empty($ip_address)) {
                            $ip_address = '127.0.0.1'; // Manter para identificar que é local
                        }
                    }
                }
                
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                
                // Detectar tipo de dispositivo e nome com mais precisão
                $device_type = 'desktop';
                $device_name = 'Computador';
                $user_agent_lower = strtolower($user_agent);
                
                // Detectar dispositivos móveis
                if (preg_match('/(android|iphone|ipad|mobile|tablet|kindle|playbook|silk|blackberry|windows phone)/i', $user_agent_lower)) {
                    $device_type = 'mobile';
                    
                    // iPhone
                    if (preg_match('/iphone/i', $user_agent)) {
                        $device_name = 'iPhone';
                        if (preg_match('/iphone\s+os\s+([\d_]+)/i', $user_agent, $matches)) {
                            $ios_version = str_replace('_', '.', $matches[1]);
                            $device_name .= ' (iOS ' . $ios_version . ')';
                        }
                    }
                    // iPad
                    elseif (preg_match('/ipad/i', $user_agent)) {
                        $device_name = 'iPad';
                        if (preg_match('/os\s+([\d_]+)/i', $user_agent, $matches)) {
                            $ios_version = str_replace('_', '.', $matches[1]);
                            $device_name .= ' (iOS ' . $ios_version . ')';
                        }
                    }
                    // Android
                    elseif (preg_match('/android/i', $user_agent)) {
                        // Tentar extrair modelo do dispositivo Android
                        if (preg_match('/android\s+[\d.]+;\s*([^)]+)/i', $user_agent, $matches)) {
                            $model = trim($matches[1]);
                            // Limpar e formatar nome do modelo
                            $model = preg_replace('/\s*Build\/.*/i', '', $model);
                            $model = preg_replace('/\s*\)/i', '', $model);
                            
                            // Tentar identificar marca conhecida
                            if (preg_match('/(samsung|xiaomi|huawei|motorola|lg|sony|oneplus|oppo|vivo|realme|nokia|asus|lenovo)/i', $model, $brandMatch)) {
                                $brand = ucfirst(strtolower($brandMatch[1]));
                                $device_name = $brand . ' ' . preg_replace('/' . $brandMatch[1] . '/i', '', $model, 1);
                            } else {
                                $device_name = 'Android - ' . $model;
                            }
                            
                            // Adicionar versão do Android se disponível
                            if (preg_match('/android\s+([\d.]+)/i', $user_agent, $androidVersion)) {
                                $device_name .= ' (Android ' . $androidVersion[1] . ')';
                            }
                        } else {
                            $device_name = 'Android';
                            if (preg_match('/android\s+([\d.]+)/i', $user_agent, $androidVersion)) {
                                $device_name .= ' ' . $androidVersion[1];
                            }
                        }
                    }
                    // Outros dispositivos móveis
                    elseif (preg_match('/blackberry/i', $user_agent)) {
                        $device_name = 'BlackBerry';
                    }
                    elseif (preg_match('/windows phone/i', $user_agent)) {
                        $device_name = 'Windows Phone';
                    }
                    else {
                        $device_name = 'Dispositivo Móvel';
                    }
                } 
                // Desktop
                else {
                    $browser = '';
                    $os = '';
                    $browserVersion = '';
                    
                    // Detectar navegador e versão
                    if (preg_match('/chrome\/([\d.]+)/i', $user_agent, $matches) && !preg_match('/edg/i', $user_agent)) {
                        $browser = 'Chrome';
                        $browserVersion = $matches[1];
                    } elseif (preg_match('/firefox\/([\d.]+)/i', $user_agent, $matches)) {
                        $browser = 'Firefox';
                        $browserVersion = $matches[1];
                    } elseif (preg_match('/safari\/([\d.]+)/i', $user_agent, $matches) && !preg_match('/chrome/i', $user_agent)) {
                        $browser = 'Safari';
                        $browserVersion = $matches[1];
                    } elseif (preg_match('/edg\/([\d.]+)/i', $user_agent, $matches)) {
                        $browser = 'Edge';
                        $browserVersion = $matches[1];
                    } elseif (preg_match('/(?:opera|opr)\/([\d.]+)/i', $user_agent, $matches)) {
                        $browser = 'Opera';
                        $browserVersion = $matches[1];
                    } else {
                        $browser = 'Navegador';
                    }
                    
                    // Detectar sistema operacional
                    if (preg_match('/windows\s+nt\s+([\d.]+)/i', $user_agent, $matches)) {
                        $version = $matches[1];
                        if ($version == '10.0') $os = 'Windows 10/11';
                        elseif ($version == '6.3') $os = 'Windows 8.1';
                        elseif ($version == '6.2') $os = 'Windows 8';
                        elseif ($version == '6.1') $os = 'Windows 7';
                        elseif ($version == '6.0') $os = 'Windows Vista';
                        else $os = 'Windows';
                    } elseif (preg_match('/mac\s+os\s+x\s+([\d_]+)/i', $user_agent, $matches)) {
                        $macVersion = str_replace('_', '.', $matches[1]);
                        $os = 'macOS ' . $macVersion;
                    } elseif (preg_match('/linux/i', $user_agent)) {
                        if (preg_match('/ubuntu/i', $user_agent)) {
                            $os = 'Ubuntu';
                        } elseif (preg_match('/debian/i', $user_agent)) {
                            $os = 'Debian';
                        } elseif (preg_match('/fedora/i', $user_agent)) {
                            $os = 'Fedora';
                        } else {
                            $os = 'Linux';
                        }
                    } elseif (preg_match('/x11/i', $user_agent)) {
                        $os = 'Unix';
                    }
                    
                    // Montar nome completo
                    $parts = [];
                    if ($browser && $browser !== 'Navegador') {
                        $parts[] = $browser;
                        if ($browserVersion) {
                            $parts[] = $browserVersion;
                        }
                    }
                    if ($os) {
                        $parts[] = $os;
                    }
                    
                    if (!empty($parts)) {
                        $device_name = implode(' - ', $parts);
                    } else {
                        $device_name = 'Computador';
                    }
                }
                
                // Buscar localização por IP (usando API pública)
                $location = 'Não identificado';
                $latitude = null;
                $longitude = null;
                $isp = null;
                $timezone = null;
                $country_code = null;
                $region_code = null;
                $city = null;
                $isLocalIP = false;
                
                // Verificar se é IP local
                if (empty($ip_address) || 
                    $ip_address === '127.0.0.1' || 
                    $ip_address === '::1' || 
                    strpos($ip_address, '192.168.') === 0 ||
                    strpos($ip_address, '10.') === 0 ||
                    strpos($ip_address, '172.') === 0 ||
                    strpos($ip_address, '169.254.') === 0 || // Link-local
                    $ip_address === 'localhost') {
                    $isLocalIP = true;
                    $location = 'Ambiente Local';
                }
                
                // Se não for IP local, buscar geolocalização completa
                if (!$isLocalIP && !empty($ip_address)) {
                    try {
                        // Criar context para file_get_contents
                        $context = stream_context_create([
                            'http' => [
                                'timeout' => 10,
                                'user_agent' => 'LacTech/1.0',
                                'ignore_errors' => true
                            ]
                        ]);
                        
                        // Função auxiliar para fazer requisição HTTP
                        $makeRequest = function($url) use ($context) {
                            // Tentar cURL primeiro (mais confiável)
                            if (function_exists('curl_init')) {
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, $url);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                                curl_setopt($ch, CURLOPT_USERAGENT, 'LacTech/1.0');
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                                $data = curl_exec($ch);
                                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                curl_close($ch);
                                
                                if ($httpCode == 200 && $data) {
                                    return $data;
                                }
                            }
                            
                            // Fallback para file_get_contents
                            return @file_get_contents($url, false, $context);
                        };
                        
                        // Buscar informações completas: status,country,countryCode,region,regionName,city,lat,lon,timezone,isp
                        // Tentar HTTP primeiro (ip-api.com funciona melhor com HTTP)
                        $apiUrl = "http://ip-api.com/json/{$ip_address}?lang=pt&fields=status,message,country,countryCode,region,regionName,city,lat,lon,timezone,isp";
                        $locationData = $makeRequest($apiUrl);
                        
                        // Se falhar com HTTP, tentar HTTPS
                        if (!$locationData) {
                            $apiUrl = "https://ip-api.com/json/{$ip_address}?lang=pt&fields=status,message,country,countryCode,region,regionName,city,lat,lon,timezone,isp";
                            $locationData = $makeRequest($apiUrl);
                        }
                        
                        if ($locationData) {
                            $loc = json_decode($locationData, true);
                            if ($loc && isset($loc['status']) && $loc['status'] === 'success') {
                                $parts = [];
                                if (!empty($loc['city'])) {
                                    $parts[] = $loc['city'];
                                    $city = $loc['city'];
                                }
                                if (!empty($loc['regionName'])) {
                                    $parts[] = $loc['regionName'];
                                }
                                if (!empty($loc['country'])) {
                                    $parts[] = $loc['country'];
                                }
                                if (!empty($parts)) {
                                    $location = implode(', ', $parts);
                                }
                                
                                // Salvar informações adicionais
                                if (isset($loc['lat']) && $loc['lat'] != 0) {
                                    $latitude = $loc['lat'];
                                }
                                if (isset($loc['lon']) && $loc['lon'] != 0) {
                                    $longitude = $loc['lon'];
                                }
                                if (!empty($loc['isp'])) {
                                    $isp = $loc['isp'];
                                }
                                if (!empty($loc['timezone'])) {
                                    $timezone = $loc['timezone'];
                                }
                                if (!empty($loc['countryCode'])) {
                                    $country_code = $loc['countryCode'];
                                }
                                if (!empty($loc['region'])) {
                                    $region_code = $loc['region'];
                                }
                            } elseif (isset($loc['message'])) {
                                error_log("Erro da API ip-api.com: " . $loc['message']);
                            }
                        }
                        
                        // Se não funcionou, tentar API alternativa ipapi.co
                        if ($location === 'Não identificado' || !$latitude || !$longitude) {
                            try {
                                $apiUrl2 = "https://ipapi.co/{$ip_address}/json/";
                                $locationData2 = $makeRequest($apiUrl2);
                                
                                if ($locationData2) {
                                    $loc2 = json_decode($locationData2, true);
                                    if ($loc2 && !isset($loc2['error'])) {
                                        $parts2 = [];
                                        if (!empty($loc2['city'])) {
                                            $parts2[] = $loc2['city'];
                                            $city = $loc2['city'];
                                        }
                                        if (!empty($loc2['region'])) {
                                            $parts2[] = $loc2['region'];
                                        }
                                        if (!empty($loc2['country_name'])) {
                                            $parts2[] = $loc2['country_name'];
                                        }
                                        if (!empty($parts2)) {
                                            $location = implode(', ', $parts2);
                                        }
                                        
                                        // Salvar informações adicionais
                                        if (isset($loc2['latitude']) && $loc2['latitude'] != 0) {
                                            $latitude = $loc2['latitude'];
                                        }
                                        if (isset($loc2['longitude']) && $loc2['longitude'] != 0) {
                                            $longitude = $loc2['longitude'];
                                        }
                                        if (!empty($loc2['org'])) {
                                            $isp = $loc2['org'];
                                        }
                                        if (!empty($loc2['timezone'])) {
                                            $timezone = $loc2['timezone'];
                                        }
                                        if (!empty($loc2['country_code'])) {
                                            $country_code = $loc2['country_code'];
                                        }
                                        if (!empty($loc2['region_code'])) {
                                            $region_code = $loc2['region_code'];
                                        }
                                    }
                                }
                            } catch (Exception $e2) {
                                // Ignorar erro da segunda API
                            }
                        }
                        
                        // Se ainda não funcionou, manter "Não identificado"
                        if ($location === 'Não identificado') {
                            $location = 'Localização não disponível';
                        }
                    } catch (Exception $e) {
                        error_log("Erro ao buscar localização por IP {$ip_address}: " . $e->getMessage());
                        $location = 'Localização não disponível';
                    }
                }
                
                // Verificar se sessão já existe
                $stmt = $pdo->prepare("SELECT id FROM user_sessions WHERE session_id = :session_id AND user_id = :user_id");
                $stmt->execute([':session_id' => $session_id, ':user_id' => $user_id]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Atualizar sessão existente
                    // Se tiver coordenadas GPS, usar elas; senão, usar coordenadas do IP
                    $final_latitude = $gps_latitude ?? $latitude;
                    $final_longitude = $gps_longitude ?? $longitude;
                    
                    $stmt = $pdo->prepare("
                        UPDATE user_sessions 
                        SET ip_address = :ip_address, 
                            user_agent = :user_agent, 
                            device_type = :device_type, 
                            device_name = :device_name, 
                            location = :location,
                            latitude = :latitude,
                            longitude = :longitude,
                            gps_latitude = :gps_latitude,
                            gps_longitude = :gps_longitude,
                            gps_accuracy = :gps_accuracy,
                            isp = :isp,
                            timezone = :timezone,
                            country_code = :country_code,
                            region_code = :region_code,
                            city = :city,
                            last_activity = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':ip_address' => $ip_address,
                        ':user_agent' => $user_agent,
                        ':device_type' => $device_type,
                        ':device_name' => $device_name,
                        ':location' => $location,
                        ':latitude' => $latitude,
                        ':longitude' => $longitude,
                        ':gps_latitude' => $gps_latitude,
                        ':gps_longitude' => $gps_longitude,
                        ':gps_accuracy' => $gps_accuracy,
                        ':isp' => $isp,
                        ':timezone' => $timezone,
                        ':country_code' => $country_code,
                        ':region_code' => $region_code,
                        ':city' => $city,
                        ':id' => $existing['id']
                    ]);
                } else {
                    // Criar nova sessão
                    $stmt = $pdo->prepare("
                        INSERT INTO user_sessions (
                            user_id, session_id, ip_address, user_agent, device_type, device_name, 
                            location, latitude, longitude, gps_latitude, gps_longitude, gps_accuracy,
                            isp, timezone, country_code, region_code, city
                        ) VALUES (
                            :user_id, :session_id, :ip_address, :user_agent, :device_type, :device_name, 
                            :location, :latitude, :longitude, :gps_latitude, :gps_longitude, :gps_accuracy,
                            :isp, :timezone, :country_code, :region_code, :city
                        )
                    ");
                    $stmt->execute([
                        ':user_id' => $user_id,
                        ':session_id' => $session_id,
                        ':ip_address' => $ip_address,
                        ':user_agent' => $user_agent,
                        ':device_type' => $device_type,
                        ':device_name' => $device_name,
                        ':location' => $location,
                        ':latitude' => $latitude,
                        ':longitude' => $longitude,
                        ':gps_latitude' => $gps_latitude,
                        ':gps_longitude' => $gps_longitude,
                        ':gps_accuracy' => $gps_accuracy,
                        ':isp' => $isp,
                        ':timezone' => $timezone,
                        ':country_code' => $country_code,
                        ':region_code' => $region_code,
                        ':city' => $city
                    ]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Sessão registrada']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Erro ao registrar sessão: ' . $e->getMessage()]);
            }
            break;
        
        case 'update_session_location':
            // Atualizar localização de uma sessão existente
            $user_id = $_SESSION['user_id'] ?? null;
            if (!$user_id) {
                echo json_encode(['success' => false, 'error' => 'Não autenticado']);
                exit;
            }
            
            $session_id = $_POST['session_id'] ?? $_GET['session_id'] ?? null;
            $device_id = $_POST['device_id'] ?? $_GET['device_id'] ?? null;
            
            try {
                $pdo = $db->getConnection();
                
                // Buscar sessão
                if ($device_id) {
                    $stmt = $pdo->prepare("SELECT ip_address FROM user_sessions WHERE id = :id AND user_id = :user_id");
                    $stmt->execute([':id' => $device_id, ':user_id' => $user_id]);
                } elseif ($session_id) {
                    $stmt = $pdo->prepare("SELECT ip_address FROM user_sessions WHERE session_id = :session_id AND user_id = :user_id");
                    $stmt->execute([':session_id' => $session_id, ':user_id' => $user_id]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'ID da sessão não informado']);
                    exit;
                }
                
                $session = $stmt->fetch();
                if (!$session) {
                    echo json_encode(['success' => false, 'error' => 'Sessão não encontrada']);
                    exit;
                }
                
                $ip_address = $session['ip_address'];
                
                // Buscar geolocalização
                $location = 'Não identificado';
                $latitude = null;
                $longitude = null;
                $isp = null;
                $timezone = null;
                $country_code = null;
                $region_code = null;
                $city = null;
                
                // Verificar se é IP local
                $isLocalIP = empty($ip_address) || 
                    $ip_address === '127.0.0.1' || 
                    $ip_address === '::1' || 
                    strpos($ip_address, '192.168.') === 0 ||
                    strpos($ip_address, '10.') === 0 ||
                    strpos($ip_address, '172.') === 0 ||
                    strpos($ip_address, '169.254.') === 0;
                
                if (!$isLocalIP && !empty($ip_address)) {
                    $context = stream_context_create([
                        'http' => [
                            'timeout' => 10,
                            'user_agent' => 'LacTech/1.0',
                            'ignore_errors' => true
                        ]
                    ]);
                    
                    // Função auxiliar para fazer requisição HTTP
                    $makeRequest = function($url) use ($context) {
                        // Tentar cURL primeiro (mais confiável)
                        if (function_exists('curl_init')) {
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                            curl_setopt($ch, CURLOPT_USERAGENT, 'LacTech/1.0');
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                            $data = curl_exec($ch);
                            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);
                            
                            if ($httpCode == 200 && $data) {
                                return $data;
                            }
                        }
                        
                        // Fallback para file_get_contents
                        return @file_get_contents($url, false, $context);
                    };
                    
                    // Tentar ip-api.com primeiro (HTTP para evitar problemas de SSL)
                    $apiUrl = "http://ip-api.com/json/{$ip_address}?lang=pt&fields=status,message,country,countryCode,region,regionName,city,lat,lon,timezone,isp";
                    $locationData = $makeRequest($apiUrl);
                    
                    // Se falhar com HTTP, tentar HTTPS
                    if (!$locationData) {
                        $apiUrl = "https://ip-api.com/json/{$ip_address}?lang=pt&fields=status,message,country,countryCode,region,regionName,city,lat,lon,timezone,isp";
                        $locationData = $makeRequest($apiUrl);
                    }
                    
                    if ($locationData) {
                        $loc = json_decode($locationData, true);
                        if ($loc && isset($loc['status']) && $loc['status'] === 'success') {
                            $parts = [];
                            if (!empty($loc['city'])) {
                                $parts[] = $loc['city'];
                                $city = $loc['city'];
                            }
                            if (!empty($loc['regionName'])) {
                                $parts[] = $loc['regionName'];
                            }
                            if (!empty($loc['country'])) {
                                $parts[] = $loc['country'];
                            }
                            if (!empty($parts)) {
                                $location = implode(', ', $parts);
                            }
                            
                            if (isset($loc['lat']) && $loc['lat'] != 0) {
                                $latitude = (float)$loc['lat'];
                            }
                            if (isset($loc['lon']) && $loc['lon'] != 0) {
                                $longitude = (float)$loc['lon'];
                            }
                            if (!empty($loc['isp'])) {
                                $isp = $loc['isp'];
                            }
                            if (!empty($loc['timezone'])) {
                                $timezone = $loc['timezone'];
                            }
                            if (!empty($loc['countryCode'])) {
                                $country_code = $loc['countryCode'];
                            }
                            if (!empty($loc['region'])) {
                                $region_code = $loc['region'];
                            }
                        }
                    }
                    
                    // Se não funcionou, tentar ipapi.co
                    if ($location === 'Não identificado' || !$latitude || !$longitude) {
                        try {
                            $apiUrl2 = "https://ipapi.co/{$ip_address}/json/";
                            $locationData2 = $makeRequest($apiUrl2);
                            
                            if ($locationData2) {
                                $loc2 = json_decode($locationData2, true);
                                if ($loc2 && !isset($loc2['error'])) {
                                    $parts2 = [];
                                    if (!empty($loc2['city'])) {
                                        $parts2[] = $loc2['city'];
                                        $city = $loc2['city'];
                                    }
                                    if (!empty($loc2['region'])) {
                                        $parts2[] = $loc2['region'];
                                    }
                                    if (!empty($loc2['country_name'])) {
                                        $parts2[] = $loc2['country_name'];
                                    }
                                    if (!empty($parts2)) {
                                        $location = implode(', ', $parts2);
                                    }
                                    
                                    if (isset($loc2['latitude']) && $loc2['latitude'] != 0) {
                                        $latitude = (float)$loc2['latitude'];
                                    }
                                    if (isset($loc2['longitude']) && $loc2['longitude'] != 0) {
                                        $longitude = (float)$loc2['longitude'];
                                    }
                                    if (!empty($loc2['org'])) {
                                        $isp = $loc2['org'];
                                    }
                                    if (!empty($loc2['timezone'])) {
                                        $timezone = $loc2['timezone'];
                                    }
                                    if (!empty($loc2['country_code'])) {
                                        $country_code = $loc2['country_code'];
                                    }
                                    if (!empty($loc2['region_code'])) {
                                        $region_code = $loc2['region_code'];
                                    }
                                }
                            }
                        } catch (Exception $e2) {
                            // Ignorar erro
                        }
                    }
                }
                
                // Obter coordenadas GPS se enviadas
                $gps_latitude = null;
                $gps_longitude = null;
                $gps_accuracy = null;
                
                if (isset($_POST['gps_latitude']) && isset($_POST['gps_longitude'])) {
                    $gps_lat = filter_var($_POST['gps_latitude'], FILTER_VALIDATE_FLOAT);
                    $gps_lng = filter_var($_POST['gps_longitude'], FILTER_VALIDATE_FLOAT);
                    
                    if ($gps_lat !== false && $gps_lng !== false && 
                        $gps_lat >= -90 && $gps_lat <= 90 && 
                        $gps_lng >= -180 && $gps_lng <= 180) {
                        $gps_latitude = (float)$gps_lat;
                        $gps_longitude = (float)$gps_lng;
                        
                        if (isset($_POST['gps_accuracy'])) {
                            $gps_accuracy = filter_var($_POST['gps_accuracy'], FILTER_VALIDATE_FLOAT);
                            if ($gps_accuracy !== false && $gps_accuracy > 0) {
                                $gps_accuracy = (float)$gps_accuracy;
                            } else {
                                $gps_accuracy = null;
                            }
                        }
                    }
                }
                
                // Atualizar sessão
                if ($device_id) {
                    $updateStmt = $pdo->prepare("
                        UPDATE user_sessions 
                        SET location = :location,
                            latitude = :latitude,
                            longitude = :longitude,
                            gps_latitude = COALESCE(:gps_latitude, gps_latitude),
                            gps_longitude = COALESCE(:gps_longitude, gps_longitude),
                            gps_accuracy = COALESCE(:gps_accuracy, gps_accuracy),
                            isp = :isp,
                            timezone = :timezone,
                            country_code = :country_code,
                            region_code = :region_code,
                            city = :city
                        WHERE id = :id AND user_id = :user_id
                    ");
                    $updateStmt->execute([
                        ':location' => $location,
                        ':latitude' => $latitude,
                        ':longitude' => $longitude,
                        ':gps_latitude' => $gps_latitude,
                        ':gps_longitude' => $gps_longitude,
                        ':gps_accuracy' => $gps_accuracy,
                        ':isp' => $isp,
                        ':timezone' => $timezone,
                        ':country_code' => $country_code,
                        ':region_code' => $region_code,
                        ':city' => $city,
                        ':id' => $device_id,
                        ':user_id' => $user_id
                    ]);
                } else {
                    $updateStmt = $pdo->prepare("
                        UPDATE user_sessions 
                        SET location = :location,
                            latitude = :latitude,
                            longitude = :longitude,
                            gps_latitude = COALESCE(:gps_latitude, gps_latitude),
                            gps_longitude = COALESCE(:gps_longitude, gps_longitude),
                            gps_accuracy = COALESCE(:gps_accuracy, gps_accuracy),
                            isp = :isp,
                            timezone = :timezone,
                            country_code = :country_code,
                            region_code = :region_code,
                            city = :city
                        WHERE session_id = :session_id AND user_id = :user_id
                    ");
                    $updateStmt->execute([
                        ':location' => $location,
                        ':latitude' => $latitude,
                        ':longitude' => $longitude,
                        ':gps_latitude' => $gps_latitude,
                        ':gps_longitude' => $gps_longitude,
                        ':gps_accuracy' => $gps_accuracy,
                        ':isp' => $isp,
                        ':timezone' => $timezone,
                        ':country_code' => $country_code,
                        ':region_code' => $region_code,
                        ':city' => $city,
                        ':session_id' => $session_id,
                        ':user_id' => $user_id
                    ]);
                }
                
                // Retornar coordenadas finais (GPS se disponível, senão IP)
                $final_latitude = $gps_latitude ?? $latitude;
                $final_longitude = $gps_longitude ?? $longitude;
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Localização atualizada',
                    'data' => [
                        'location' => $location,
                        'latitude' => $final_latitude,
                        'longitude' => $final_longitude,
                        'gps_latitude' => $gps_latitude,
                        'gps_longitude' => $gps_longitude,
                        'gps_accuracy' => $gps_accuracy,
                        'isp' => $isp,
                        'timezone' => $timezone,
                        'city' => $city
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Erro ao atualizar localização: ' . $e->getMessage()]);
            }
            break;

        case 'get_account_actions':
            // Buscar ações da conta (alterações de senha, etc) da tabela security_audit_log
            $user_id = $_SESSION['user_id'] ?? 1;
            $pdo = $db->getConnection();
            
            try {
                // Verificar se a tabela security_audit_log existe
                $checkTable = $pdo->query("SHOW TABLES LIKE 'security_audit_log'");
                $tableExists = $checkTable->rowCount() > 0;
                
                $passwordChanges = [];
                $otherActions = [];
                
                if ($tableExists) {
                    // Buscar todas as ações relacionadas à senha
                    $stmt = $pdo->prepare("
                        SELECT 
                            action,
                            description,
                            ip_address,
                            success,
                            created_at,
                            metadata
                        FROM security_audit_log 
                        WHERE user_id = :user_id 
                        AND action IN ('password_changed', 'email_verified', 'google_linked', 'google_unlinked', '2fa_enabled', '2fa_disabled', 'otp_generated', 'otp_validated')
                        ORDER BY created_at DESC
                        LIMIT 100
                    ");
                    $stmt->execute([':user_id' => $user_id]);
                    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($actions as $action) {
                        $actionData = [
                            'action' => $action['action'],
                            'description' => $action['description'],
                            'ip_address' => $action['ip_address'],
                            'success' => (bool)$action['success'],
                            'created_at' => $action['created_at'],
                            'metadata' => $action['metadata'] ? json_decode($action['metadata'], true) : null
                        ];
                        
                        // Separar por tipo
                        if ($action['action'] === 'password_changed') {
                            $passwordChanges[] = [
                                'password_changed_at' => $action['created_at'],
                                'action' => 'Senha alterada',
                                'ip_address' => $action['ip_address'],
                                'success' => (bool)$action['success']
                            ];
                        } else {
                            $otherActions[] = $actionData;
                        }
                    }
                } else {
                    // Fallback: buscar da tabela users se security_audit_log não existir
                    $stmt = $pdo->prepare("
                        SELECT 
                            password_changed_at,
                            updated_at,
                            created_at
                        FROM users 
                        WHERE id = :user_id
                    ");
                    $stmt->execute([':user_id' => $user_id]);
                    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($userData && !empty($userData['password_changed_at'])) {
                        $passwordChanges[] = [
                            'password_changed_at' => $userData['password_changed_at'],
                            'action' => 'Senha alterada',
                            'ip_address' => null,
                            'success' => true
                        ];
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'password_changes' => $passwordChanges,
                        'other_actions' => $otherActions
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Erro ao buscar ações: ' . $e->getMessage()
                ]);
            }
            break;
            
        // ==================== REDEFINIR SENHA (SEM RESTRIÇÕES) ====================
        case 'reset_password':
            $email = $input['email'] ?? '';
            $newPassword = $input['new_password'] ?? '';
            
            if (empty($email) || empty($newPassword)) {
                echo json_encode(['success' => false, 'error' => 'Email e nova senha são obrigatórios']);
                exit;
            }
            
            try {
                // Buscar usuário pelo email
                $stmt = $db->getConnection()->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    echo json_encode(['success' => false, 'error' => 'Email não encontrado']);
                    exit;
                }
                
                // Atualizar senha diretamente (sem verificar senha atual)
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $db->getConnection()->prepare("UPDATE users SET password = ?, password_changed_at = CURRENT_TIMESTAMP, password_change_required = 0 WHERE id = ?");
                $updateStmt->execute([$hashedPassword, $user['id']]);
                
                // Registrar na tabela de auditoria se existir
                try {
                    $ipAddress = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                    
                    $checkStmt = $db->getConnection()->prepare("SHOW TABLES LIKE 'security_audit_log'");
                    $checkStmt->execute();
                    $tableExists = $checkStmt->rowCount() > 0;
                    
                    if ($tableExists) {
                        $auditStmt = $db->getConnection()->prepare("
                            INSERT INTO security_audit_log (
                                user_id, action, description, ip_address, user_agent, 
                                success, metadata
                            ) VALUES (
                                ?, 'password_reset', 'Senha redefinida via esqueceu senha', 
                                ?, ?, 1, NULL
                            )
                        ");
                        $auditStmt->execute([$user['id'], $ipAddress, $userAgent]);
                    }
                } catch (Exception $e) {
                    // Se a tabela não existir, apenas logar o erro mas não falhar
                    error_log("Aviso: Não foi possível registrar na tabela de auditoria: " . $e->getMessage());
                }
                
                echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso']);
                
            } catch (Exception $e) {
                error_log("Erro ao redefinir senha: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Erro ao alterar senha']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
?>

