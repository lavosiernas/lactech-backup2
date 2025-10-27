<?php
/**
 * API de Autenticação
 * Login, Logout, Sessões
 */

// Desabilitar exibição de erros em produção
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sempre retornar JSON
header('Content-Type: application/json');

// Verificar se o arquivo existe
$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Erro no servidor: Database.class.php não encontrado em: ' . $dbPath]);
    exit;
}

require_once $dbPath;

$db = Database::getInstance();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

try {
    switch ($action) {
        // ==================== LOGIN ====================
        case 'login':
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'error' => 'Email e senha são obrigatórios']);
                exit;
            }
            
            $result = $db->login($email, $password);
            
            if ($result['success']) {
                // Criar sessão
                $_SESSION['user_id'] = $result['user']['id'];
                $_SESSION['user_email'] = $result['user']['email'];
                $_SESSION['user_name'] = $result['user']['name'];
                $_SESSION['user_role'] = $result['user']['role'];
                // Buscar farm_id e nome da fazenda do usuário
                $stmt = $db->prepare("SELECT farm_id FROM users WHERE id = ?");
                $stmt->execute([$result['user']['id']]);
                $userFarm = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $farmId = $userFarm['farm_id'];
                $_SESSION['farm_id'] = $farmId;
                $_SESSION['logged_in'] = true;
                
                // Buscar nome da fazenda
                $stmt = $db->prepare("SELECT name FROM farms WHERE id = ?");
                $stmt->execute([$farmId]);
                $farmName = $stmt->fetch(PDO::FETCH_ASSOC)['name'];
                
                // Adicionar dados da fazenda
                $result['user']['farm_name'] = $farmName;
                $result['user']['farm_id'] = $farmId;
                
                echo json_encode([
                    'success' => true,
                    'user' => $result['user'],
                    'redirect' => getRedirectByRole($result['user']['role'])
                ]);
            } else {
                echo json_encode($result);
            }
            break;
            
        // ==================== LOGOUT ====================
        case 'logout':
            session_destroy();
            echo json_encode(['success' => true]);
            break;
            
        // ==================== VERIFICAR SESSÃO ====================
        case 'check_session':
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
                $user = $db->getUser($_SESSION['user_id']);
                echo json_encode([
                    'success' => true,
                    'logged_in' => true,
                    'user' => $user
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'logged_in' => false
                ]);
            }
            break;
            
        // ==================== OBTER USUÁRIO ATUAL ====================
        case 'get_current_user':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'error' => 'Não autenticado']);
                exit;
            }
            
            $user = $db->getUser($_SESSION['user_id']);
            echo json_encode(['success' => true, 'user' => $user]);
            break;
            
        // ==================== PRIMEIRO ACESSO / REGISTRO ====================
        case 'register':
            $name = $input['name'] ?? '';
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            $role = $input['role'] ?? 'funcionario';
            $cpf = $input['cpf'] ?? null;
            $phone = $input['phone'] ?? null;
            $farm_id = $input['farm_id'] ?? null;
            
            // Validações
            if (empty($name) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'error' => 'Nome, email e senha são obrigatórios']);
                exit;
            }
            
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'error' => 'Senha deve ter no mínimo 6 caracteres']);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Email inválido']);
                exit;
            }
            
            $result = $db->createUser([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'cpf' => $cpf,
                'phone' => $phone,
                'farm_id' => $farm_id
            ]);
            
            echo json_encode($result);
            break;
            
        // ==================== REGISTRO COMPLETO (COM FAZENDA) ====================
        case 'register_with_farm':
            // Dados do usuário
            $userName = $input['user_name'] ?? '';
            $userEmail = $input['user_email'] ?? '';
            $userPassword = $input['user_password'] ?? '';
            $userCpf = $input['user_cpf'] ?? null;
            $userPhone = $input['user_phone'] ?? null;
            
            // Dados da fazenda
            $farmName = $input['farm_name'] ?? '';
            $farmLocation = $input['farm_location'] ?? null;
            $farmCnpj = $input['farm_cnpj'] ?? null;
            
            // Validações
            if (empty($userName) || empty($userEmail) || empty($userPassword) || empty($farmName)) {
                echo json_encode(['success' => false, 'error' => 'Preencha todos os campos obrigatórios']);
                exit;
            }
            
            // Criar fazenda primeiro
            $farmId = $db->createFarm([
                'name' => $farmName,
                'location' => $farmLocation,
                'cnpj' => $farmCnpj,
                'owner_name' => $userName
            ]);
            
            if (!$farmId) {
                echo json_encode(['success' => false, 'error' => 'Erro ao criar fazenda']);
                exit;
            }
            
            // Criar usuário proprietário
            $result = $db->createUser([
                'name' => $userName,
                'email' => $userEmail,
                'password' => $userPassword,
                'role' => 'proprietario',
                'cpf' => $userCpf,
                'phone' => $userPhone,
                'farm_id' => $farmId
            ]);
            
            if ($result['success']) {
                // Fazer login automático
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['farm_id'] = $farmId;
                $_SESSION['logged_in'] = true;
                
                $result['farm_id'] = $farmId;
                $result['redirect'] = 'proprietario.php';
            }
            
            echo json_encode($result);
            break;
            
        // ==================== ALTERAR SENHA ====================
        case 'change_password':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'error' => 'Não autenticado']);
                exit;
            }
            
            $currentPassword = $input['current_password'] ?? '';
            $newPassword = $input['new_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword)) {
                echo json_encode(['success' => false, 'error' => 'Preencha todos os campos']);
                exit;
            }
            
            // Verificar senha atual
            $user = $db->getUser($_SESSION['user_id']);
            // Nota: precisamos buscar a senha do banco
            $stmt = $db->getConnection()->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $hashedPassword = $stmt->fetchColumn();
            
            if (!password_verify($currentPassword, $hashedPassword)) {
                echo json_encode(['success' => false, 'error' => 'Senha atual incorreta']);
                exit;
            }
            
            // Atualizar senha
            $result = $db->updateUser($_SESSION['user_id'], [
                'password' => $newPassword
            ]);
            
            echo json_encode($result);
            break;
            
        // ==================== SOLICITAR RESET DE SENHA ====================
        case 'request_password_reset':
            $email = $input['email'] ?? '';
            
            if (empty($email)) {
                echo json_encode(['success' => false, 'error' => 'Email é obrigatório']);
                exit;
            }
            
            // Verificar se email existe
            if (!$db->emailExists($email)) {
                // Por segurança, não revelar se o email existe ou não
                echo json_encode(['success' => true, 'message' => 'Se o email existir, você receberá instruções']);
                exit;
            }
            
            // TODO: Implementar envio de email com token
            echo json_encode([
                'success' => true,
                'message' => 'Instruções de reset enviadas para o email'
            ]);
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

/**
 * Determinar página de redirecionamento baseado no role
 */
function getRedirectByRole($role) {
    switch ($role) {
        case 'gerente':
            return 'gerente.php';
        case 'proprietario':
            return 'proprietario.php';
        case 'funcionario':
        default:
            return 'funcionario.php';
    }
}
?>
