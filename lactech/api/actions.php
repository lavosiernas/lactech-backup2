<?php
/**
 * API de Ações - Lactech
 * Endpoint para ações específicas do sistema
 */

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
            $result = $db->addVolumeRecord($data);
            echo json_encode($result);
            break;

        case 'add_volume_by_animal':
            // Registrar volume por animal (milk_production)
            $data = [
                'producer_id' => isset($_POST['animal_id']) ? (int)$_POST['animal_id'] : null,
                'collection_date' => $_POST['collection_date'] ?? date('Y-m-d'),
                'period' => $_POST['period'] ?? 'manha',
                'volume' => isset($_POST['volume']) ? (float)$_POST['volume'] : 0,
                'temperature' => isset($_POST['temperature']) ? (float)$_POST['temperature'] : null,
                'notes' => $_POST['notes'] ?? null,
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
            $result = $db->createUser($userData);
            echo json_encode($result);
            break;

        case 'update_profile':
            // Atualizar perfil do usuário
            $user_id = $_SESSION['user_id'] ?? 1;
            $pdo = $db->getConnection();
            $updates = [];
            $params = [];
            
            // Atualizar campos básicos
            if (isset($_POST['name'])) {
                $updates[] = "name = :name";
                $params[':name'] = $_POST['name'];
            }
            if (isset($_POST['phone'])) {
                $updates[] = "phone = :phone";
                $params[':phone'] = $_POST['phone'];
            }
            
            // Atualizar dados da fazenda (tabela farms)
            if (isset($_POST['farm_name'])) {
                $stmt = $pdo->prepare("UPDATE farms SET name = :name WHERE id = 1");
                $stmt->execute([':name' => $_POST['farm_name']]);
            }
            if (isset($_POST['farm_cnpj'])) {
                $stmt = $pdo->prepare("UPDATE farms SET cnpj = :cnpj WHERE id = 1");
                $stmt->execute([':cnpj' => $_POST['farm_cnpj']]);
            }
            if (isset($_POST['farm_address'])) {
                $stmt = $pdo->prepare("UPDATE farms SET address = :address WHERE id = 1");
                $stmt->execute([':address' => $_POST['farm_address']]);
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
                    $oldPhoto = $stmt->fetch();
                    if (!empty($oldPhoto['profile_photo']) && file_exists(__DIR__ . '/../' . $oldPhoto['profile_photo'])) {
                        @unlink(__DIR__ . '/../' . $oldPhoto['profile_photo']);
                    }
                    
                    $profilePhotoPath = 'uploads/profiles/' . $filename;
                    $updates[] = "profile_photo = :profile_photo";
                    $params[':profile_photo'] = $profilePhotoPath;
                } else {
                    echo json_encode(['success' => false, 'error' => 'Erro ao salvar a foto.']);
                    exit;
                }
            }
            
            // Atualizar senha se fornecida
            if (!empty($_POST['password'])) {
                $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $updates[] = "password = :password";
                $updates[] = "password_changed_at = NOW()";
                $params[':password'] = $passwordHash;
            }
            
            // Executar atualização
            if (!empty($updates)) {
                $params[':id'] = $user_id;
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }
            
            // Buscar dados atualizados
            $stmt = $pdo->prepare("SELECT id, name, email, phone, profile_photo FROM users WHERE id = :id");
            $stmt->execute([':id' => $user_id]);
            $userData = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso!',
                'data' => $userData
            ]);
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
                        last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_user_id (user_id),
                        INDEX idx_session_id (session_id),
                        INDEX idx_last_activity (last_activity)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                // Buscar sessões ativas do usuário (últimas 30 dias)
                $stmt = $pdo->prepare("
                    SELECT id, session_id, ip_address, user_agent, device_type, device_name, location, last_activity, created_at
                    FROM user_sessions
                    WHERE user_id = :user_id
                    AND last_activity >= DATE_SUB(NOW(), INTERVAL 30 DAY)
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
                    $isCurrent = ($session['session_id'] === $currentSessionId);
                    $formattedSessions[] = [
                        'id' => $session['id'],
                        'device' => $session['device_name'] ?: ($session['device_type'] === 'mobile' ? 'Dispositivo Móvel' : 'Computador'),
                        'device_type' => $session['device_type'] ?: 'desktop',
                        'location' => $session['location'] ?: 'Não identificado',
                        'ip' => $session['ip_address'] ?: 'N/A',
                        'lastActive' => $session['last_activity'],
                        'userAgent' => $session['user_agent'] ?: '',
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
                        last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_user_id (user_id),
                        INDEX idx_session_id (session_id),
                        INDEX idx_last_activity (last_activity)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                $session_id = session_id();
                
                // Verificar se o cliente enviou IP público (para ambientes locais)
                $publicIP = $_POST['public_ip'] ?? $_GET['public_ip'] ?? null;
                
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
                
                // Detectar tipo de dispositivo e nome
                $device_type = 'desktop';
                $device_name = 'Computador';
                $user_agent_lower = strtolower($user_agent);
                
                if (preg_match('/(android|iphone|ipad|mobile)/i', $user_agent_lower)) {
                    $device_type = 'mobile';
                    // Detectar dispositivo móvel específico
                    if (preg_match('/android/i', $user_agent)) {
                        // Tentar extrair modelo Android
                        if (preg_match('/android\s+[\d.]+;\s*([^)]+)/i', $user_agent, $matches)) {
                            $device_name = 'Android - ' . trim($matches[1]);
                        } else {
                            $device_name = 'Android';
                        }
                    } elseif (preg_match('/iphone/i', $user_agent)) {
                        // Tentar extrair modelo iPhone
                        if (preg_match('/iphone\s+os\s+([\d_]+)/i', $user_agent, $matches)) {
                            $device_name = 'iPhone iOS ' . str_replace('_', '.', $matches[1]);
                        } else {
                            $device_name = 'iPhone';
                        }
                    } elseif (preg_match('/ipad/i', $user_agent)) {
                        $device_name = 'iPad';
                    } else {
                        $device_name = 'Dispositivo Móvel';
                    }
                } else {
                    // Detectar navegador desktop e sistema operacional
                    $browser = 'Navegador';
                    $os = '';
                    
                    // Detectar navegador
                    if (preg_match('/chrome/i', $user_agent) && !preg_match('/edg/i', $user_agent)) {
                        $browser = 'Chrome';
                    } elseif (preg_match('/firefox/i', $user_agent)) {
                        $browser = 'Firefox';
                    } elseif (preg_match('/safari/i', $user_agent) && !preg_match('/chrome/i', $user_agent)) {
                        $browser = 'Safari';
                    } elseif (preg_match('/edg/i', $user_agent)) {
                        $browser = 'Edge';
                    } elseif (preg_match('/opera|opr/i', $user_agent)) {
                        $browser = 'Opera';
                    }
                    
                    // Detectar sistema operacional
                    if (preg_match('/windows\s+nt\s+([\d.]+)/i', $user_agent, $matches)) {
                        $os = 'Windows';
                        if (isset($matches[1])) {
                            $version = $matches[1];
                            if ($version == '10.0') $os = 'Windows 10/11';
                            elseif ($version == '6.3') $os = 'Windows 8.1';
                            elseif ($version == '6.2') $os = 'Windows 8';
                            elseif ($version == '6.1') $os = 'Windows 7';
                        }
                    } elseif (preg_match('/mac\s+os\s+x/i', $user_agent)) {
                        if (preg_match('/mac\s+os\s+x\s+([\d_]+)/i', $user_agent, $matches)) {
                            $os = 'macOS ' . str_replace('_', '.', $matches[1]);
                        } else {
                            $os = 'macOS';
                        }
                    } elseif (preg_match('/linux/i', $user_agent)) {
                        $os = 'Linux';
                    } elseif (preg_match('/ubuntu/i', $user_agent)) {
                        $os = 'Ubuntu';
                    }
                    
                    // Montar nome completo
                    if ($os) {
                        $device_name = $browser . ' - ' . $os;
                    } else {
                        $device_name = $browser;
                    }
                }
                
                // Buscar localização por IP (usando API pública)
                $location = 'Não identificado';
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
                
                // Se não for IP local, buscar geolocalização
                if (!$isLocalIP && !empty($ip_address)) {
                    try {
                        // Tentar API ip-api.com primeiro (mais rápida e confiável)
                        $context = stream_context_create([
                            'http' => [
                                'timeout' => 3,
                                'user_agent' => 'LacTech/1.0',
                                'ignore_errors' => true
                            ]
                        ]);
                        
                        $apiUrl = "http://ip-api.com/json/{$ip_address}?lang=pt&fields=status,message,country,regionName,city";
                        $locationData = @file_get_contents($apiUrl, false, $context);
                        
                        if ($locationData) {
                            $loc = json_decode($locationData, true);
                            if ($loc && isset($loc['status']) && $loc['status'] === 'success') {
                                $parts = [];
                                if (!empty($loc['city'])) {
                                    $parts[] = $loc['city'];
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
                            } elseif (isset($loc['message'])) {
                                // API retornou erro específico
                                error_log("Erro da API ip-api.com: " . $loc['message']);
                            }
                        }
                        
                        // Se não funcionou, tentar API alternativa ipapi.co
                        if ($location === 'Não identificado') {
                            try {
                                $apiUrl2 = "https://ipapi.co/{$ip_address}/json/";
                                $locationData2 = @file_get_contents($apiUrl2, false, $context);
                                
                                if ($locationData2) {
                                    $loc2 = json_decode($locationData2, true);
                                    if ($loc2 && !isset($loc2['error'])) {
                                        $parts2 = [];
                                        if (!empty($loc2['city'])) {
                                            $parts2[] = $loc2['city'];
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
                        // Em caso de erro, manter "Não identificado"
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
                    $stmt = $pdo->prepare("
                        UPDATE user_sessions 
                        SET ip_address = :ip_address, 
                            user_agent = :user_agent, 
                            device_type = :device_type, 
                            device_name = :device_name, 
                            location = :location,
                            last_activity = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':ip_address' => $ip_address,
                        ':user_agent' => $user_agent,
                        ':device_type' => $device_type,
                        ':device_name' => $device_name,
                        ':location' => $location,
                        ':id' => $existing['id']
                    ]);
                } else {
                    // Criar nova sessão
                    $stmt = $pdo->prepare("
                        INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, device_type, device_name, location)
                        VALUES (:user_id, :session_id, :ip_address, :user_agent, :device_type, :device_name, :location)
                    ");
                    $stmt->execute([
                        ':user_id' => $user_id,
                        ':session_id' => $session_id,
                        ':ip_address' => $ip_address,
                        ':user_agent' => $user_agent,
                        ':device_type' => $device_type,
                        ':device_name' => $device_name,
                        ':location' => $location
                    ]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Sessão registrada']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Erro ao registrar sessão: ' . $e->getMessage()]);
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

