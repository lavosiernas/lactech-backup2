<?php
/**
 * SafeNode Survey Admin - Painel Administrativo
 * Login com senha hashada no banco de dados
 */

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

define('ADMIN_SESSION_KEY', 'safenode_survey_admin_logged_in');

// Função para validar sessão
function isAdminLoggedIn() {
    return isset($_SESSION[ADMIN_SESSION_KEY]) && $_SESSION[ADMIN_SESSION_KEY] === true;
}

// Processar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: survey-admin.php');
    exit;
}

// Processar login
$loginError = '';
$debugInfo = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        $loginError = 'Por favor, informe a senha.';
    } else {
        $db = getSafeNodeDatabase();
        
        if ($db) {
            try {
                // Buscar admin do banco usando prepared statement
                $stmt = $db->prepare("SELECT id, username, password_hash FROM safenode_survey_admin WHERE username = ? LIMIT 1");
                $stmt->execute(['admin']);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $debugInfo['admin_found'] = $admin !== false;
                $debugInfo['admin_id'] = $admin['id'] ?? 'N/A';
                $debugInfo['password_hash_in_db'] = isset($admin['password_hash']) ? substr($admin['password_hash'], 0, 20) . '...' : 'NÃO ENCONTRADO';
                
                if ($admin && isset($admin['password_hash'])) {
                    $passwordMatch = password_verify($password, $admin['password_hash']);
                    $debugInfo['password_match'] = $passwordMatch;
                    $debugInfo['password_received'] = $password;
                    $debugInfo['password_length'] = strlen($password);
                    
                    if ($passwordMatch) {
                        // Login bem-sucedido
                        $_SESSION[ADMIN_SESSION_KEY] = true;
                        $_SESSION['admin_id'] = $admin['id'];
                        header('Location: survey-admin.php');
                        exit;
                    } else {
                        $loginError = 'Senha incorreta.';
                    }
                } else {
                    $loginError = 'Usuário admin não encontrado no banco de dados. Execute o SQL admin-login-table.sql primeiro.';
                }
            } catch (PDOException $e) {
                error_log("Survey Admin Login Error: " . $e->getMessage());
                $debugInfo['error'] = $e->getMessage();
                $loginError = 'Erro ao buscar usuário: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        } else {
            $loginError = 'Erro ao conectar ao banco de dados.';
        }
    }
}

// Se não estiver logado, mostrar tela de login
if (!isAdminLoggedIn()) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR" class="dark">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Admin - Pesquisas | SafeNode</title>
        <link rel="icon" type="image/png" href="assets/img/logos (6).png">
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <script src="https://unpkg.com/lucide@latest"></script>
        <script>
            tailwind.config = { darkMode: 'class', theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } } }
        </script>
        <style>body { font-family: 'Inter', sans-serif; }</style>
    </head>
    <body class="bg-black text-white min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full mx-4">
            <div class="text-center mb-8">
                <img src="assets/img/logos (6).png" alt="SafeNode" class="h-12 w-auto mx-auto mb-4">
                <h1 class="text-3xl font-bold mb-2">Área Administrativa</h1>
                <p class="text-zinc-400">Acesso restrito</p>
            </div>
            <div class="bg-zinc-900/50 border border-zinc-800 rounded-2xl p-8">
                <?php if ($loginError): ?>
                <div class="mb-4 p-4 rounded-xl bg-red-500/20 border border-red-500/50 text-red-400">
                    <div class="flex items-center gap-2 mb-2">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        <span class="font-semibold"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <?php if (!empty($debugInfo)): ?>
                    <div class="mt-3 p-3 bg-red-900/30 rounded-lg text-xs font-mono text-red-200/80 space-y-1">
                        <p class="font-bold mb-2 text-yellow-300">🔍 DEBUG INFO:</p>
                        <?php foreach ($debugInfo as $key => $value): ?>
                        <p><strong><?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>:</strong> 
                           <span class="text-yellow-200"><?php echo is_bool($value) ? ($value ? 'SIM ✅' : 'NÃO ❌') : htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); ?></span>
                        </p>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <form method="POST" id="loginForm">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-zinc-300 mb-2">Senha</label>
                        <div class="relative">
                            <input 
                                type="password" 
                                name="password" 
                                id="passwordInput"
                                required
                                autocomplete="current-password"
                                autofocus
                                class="w-full px-4 py-3 pr-12 bg-zinc-950 border border-zinc-800 rounded-lg text-white placeholder:text-zinc-600 focus:outline-none focus:border-zinc-600 transition-colors"
                                placeholder="Digite a senha"
                            >
                            <button 
                                type="button"
                                id="togglePassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-white transition-colors focus:outline-none"
                                aria-label="Mostrar senha"
                            >
                                <i data-lucide="eye" class="w-5 h-5" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button 
                        type="submit" 
                        name="login"
                        class="w-full px-6 py-3 bg-white text-black rounded-lg font-semibold hover:bg-zinc-100 transition-all flex items-center justify-center gap-2"
                    >
                        <span>Entrar</span>
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
        <script>
            lucide.createIcons();
            
            // Toggle mostrar/ocultar senha
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('passwordInput');
            const eyeIcon = document.getElementById('eyeIcon');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                eyeIcon.setAttribute('data-lucide', type === 'password' ? 'eye' : 'eye-off');
                lucide.createIcons();
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// A partir daqui, usuário está autenticado
$db = getSafeNodeDatabase();
if (!$db) {
    http_response_code(500);
    die('Erro ao conectar ao banco de dados');
}

$message = '';
$messageType = '';

// Processar exclusão de resposta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_response'])) {
    try {
        $responseId = filter_var($_POST['response_id'] ?? 0, FILTER_VALIDATE_INT);
        
        if (!$responseId) {
            throw new Exception('ID de resposta inválido');
        }
        
        // Verificar se a resposta existe
        $stmt = $db->prepare("SELECT id, email FROM safenode_survey_responses WHERE id = ?");
        $stmt->execute([$responseId]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$response) {
            throw new Exception('Resposta não encontrada');
        }
        
        // Excluir resposta
        $stmt = $db->prepare("DELETE FROM safenode_survey_responses WHERE id = ?");
        $stmt->execute([$responseId]);
        
        $message = 'Resposta excluída com sucesso!';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $messageType = 'error';
    }
}

// Processar envio de email de agradecimento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_thanks'])) {
    try {
        $responseId = filter_var($_POST['response_id'] ?? 0, FILTER_VALIDATE_INT);
        
        if (!$responseId) {
            throw new Exception('ID de resposta inválido');
        }
        
        // Buscar resposta
        $stmt = $db->prepare("SELECT email FROM safenode_survey_responses WHERE id = ?");
        $stmt->execute([$responseId]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$response) {
            throw new Exception('Resposta não encontrada');
        }
        
        $to = $response['email'];
        $subject = 'Obrigado pela sua participação!';
        
        // Construir URL base para a imagem
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Obter o diretório base (remover survey-admin.php do SCRIPT_NAME)
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = str_replace('\\', '/', $scriptDir); // Windows fix
        if ($scriptDir === '/' || $scriptDir === '.') {
            $scriptDir = '';
        }
        
        $imageUrl = $protocol . '://' . $host . $scriptDir . '/assets/img/mail-thanks.jpg';
        
        $htmlContent = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 0; }
        .image-container { width: 100%; }
        .image-container img { width: 100%; height: auto; display: block; }
        .content { padding: 30px; background: #f9f9f9; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #888; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="image-container">
            <img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="Obrigado pela sua participação" style="width: 100%; height: auto;">
        </div>
        <div class="content">
            <p>Olá!</p>
            <p>Queremos agradecer muito pela sua participação na nossa pesquisa. Suas respostas são extremamente valiosas para nós e nos ajudam a entender melhor as necessidades dos nossos usuários.</p>
            <p>Com base no seu feedback, estamos trabalhando para melhorar continuamente nossos produtos e serviços. Cada opinião conta e nos inspira a criar soluções ainda melhores.</p>
            <p>Se você tem mais alguma sugestão ou feedback, não hesite em nos contatar. Estamos sempre abertos para ouvir você!</p>
            <p style="margin-top: 30px;">Atenciosamente,<br><strong>Equipe SafeNode</strong></p>
        </div>
        <div class="footer">
            <p>Este é um email automático, por favor não responda.</p>
        </div>
    </div>
</body>
</html>';
        
        $fromEmail = 'safenodemail@safenode.cloud';
        $fromName = 'SafeNode';
        
        $headers = [
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: SafeNode Survey System'
        ];
        
        $mailSent = @mail($to, $subject, $htmlContent, implode("\r\n", $headers));
        
        if ($mailSent) {
            $stmt = $db->prepare("UPDATE safenode_survey_responses SET thanked_at = NOW() WHERE id = ?");
            $stmt->execute([$responseId]);
            
            $message = 'Email de agradecimento enviado com sucesso!';
            $messageType = 'success';
        } else {
            throw new Exception('Erro ao enviar email. Verifique a configuração do servidor.');
        }
    } catch (Exception $e) {
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $messageType = 'error';
    }
}

// Verificar se é para visualizar detalhes de uma resposta
$viewId = isset($_GET['view']) ? filter_var($_GET['view'], FILTER_VALIDATE_INT) : null;
$viewResponse = null;

if ($viewId) {
    try {
        $stmt = $db->prepare("SELECT * FROM safenode_survey_responses WHERE id = ? LIMIT 1");
        $stmt->execute([$viewId]);
        $viewResponse = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Survey Admin View Error: " . $e->getMessage());
        $viewResponse = null;
    }
}

// Se tiver resposta para visualizar, mostrar modal/detalhes
if ($viewResponse) {
    // Decodificar pain_points se existir
    if (!empty($viewResponse['pain_points'])) {
        $painPointsArray = json_decode($viewResponse['pain_points'], true);
        $viewResponse['pain_points_array'] = is_array($painPointsArray) ? $painPointsArray : [];
    } else {
        $viewResponse['pain_points_array'] = [];
    }
}

// Filtros
$filterStatus = $_GET['status'] ?? 'all'; // all, thanked, pending
$filterDevLevel = $_GET['dev_level'] ?? 'all';
$filterStack = $_GET['stack'] ?? 'all';
$currentTab = $_GET['tab'] ?? 'responses'; // responses, statistics, analytics

// Construir query com filtros
$whereConditions = [];
$params = [];

if ($filterStatus === 'thanked') {
    $whereConditions[] = "thanked_at IS NOT NULL";
} elseif ($filterStatus === 'pending') {
    $whereConditions[] = "thanked_at IS NULL";
}

if ($filterDevLevel !== 'all' && !empty($filterDevLevel)) {
    $whereConditions[] = "dev_level = ?";
    $params[] = $filterDevLevel;
}

if ($filterStack !== 'all' && !empty($filterStack)) {
    $whereConditions[] = "main_stack = ?";
    $params[] = $filterStack;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Buscar todas as respostas
try {
    $stmt = $db->prepare("SELECT * FROM safenode_survey_responses $whereClause ORDER BY created_at DESC LIMIT 1000");
    $stmt->execute($params);
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Survey Admin Error: " . $e->getMessage());
    $responses = [];
    $message = 'Erro ao carregar respostas.';
    $messageType = 'error';
}

// Buscar todas as respostas para estatísticas (sem filtros)
try {
    $stmtAll = $db->prepare("SELECT * FROM safenode_survey_responses");
    $stmtAll->execute();
    $allResponses = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allResponses = $responses;
}

// Estatísticas gerais
$totalResponses = count($allResponses);
$thankedCount = count(array_filter($allResponses, function($r) { return $r['thanked_at'] !== null; }));
$pendingCount = $totalResponses - $thankedCount;

// Análises estatísticas
$stats = [
    'dev_level' => [],
    'work_type' => [],
    'main_stack' => [],
    'pain_points' => [],
    'time_wasted' => [],
    'platform_help' => [],
    'first_feature' => [],
    'use_ai_analysis' => [],
    'price_willing' => [],
    'use_in_production' => [],
    'recommend_to_team' => [],
    'decision_maker' => []
];

foreach ($allResponses as $response) {
    // Dev Level
    if (!empty($response['dev_level'])) {
        $stats['dev_level'][$response['dev_level']] = ($stats['dev_level'][$response['dev_level']] ?? 0) + 1;
    }
    
    // Work Type
    if (!empty($response['work_type'])) {
        $stats['work_type'][$response['work_type']] = ($stats['work_type'][$response['work_type']] ?? 0) + 1;
    }
    
    // Main Stack
    if (!empty($response['main_stack'])) {
        $stats['main_stack'][$response['main_stack']] = ($stats['main_stack'][$response['main_stack']] ?? 0) + 1;
    }
    
    // Pain Points (JSON array)
    if (!empty($response['pain_points'])) {
        $painPoints = json_decode($response['pain_points'], true);
        if (is_array($painPoints)) {
            foreach ($painPoints as $point) {
                $stats['pain_points'][$point] = ($stats['pain_points'][$point] ?? 0) + 1;
            }
        }
    }
    
    // Time Wasted
    if (!empty($response['time_wasted_per_week'])) {
        $stats['time_wasted'][$response['time_wasted_per_week']] = ($stats['time_wasted'][$response['time_wasted_per_week']] ?? 0) + 1;
    }
    
    // Platform Help
    if (!empty($response['platform_help'])) {
        $stats['platform_help'][$response['platform_help']] = ($stats['platform_help'][$response['platform_help']] ?? 0) + 1;
    }
    
    // First Feature
    if (!empty($response['first_feature'])) {
        $stats['first_feature'][$response['first_feature']] = ($stats['first_feature'][$response['first_feature']] ?? 0) + 1;
    }
    
    // Use AI Analysis
    if (!empty($response['use_ai_analysis'])) {
        $stats['use_ai_analysis'][$response['use_ai_analysis']] = ($stats['use_ai_analysis'][$response['use_ai_analysis']] ?? 0) + 1;
    }
    
    // Price Willing
    if (!empty($response['price_willing'])) {
        $stats['price_willing'][$response['price_willing']] = ($stats['price_willing'][$response['price_willing']] ?? 0) + 1;
    }
    
    // Use in Production
    if (!empty($response['use_in_production'])) {
        $stats['use_in_production'][$response['use_in_production']] = ($stats['use_in_production'][$response['use_in_production']] ?? 0) + 1;
    }
    
    // Recommend to Team
    if (!empty($response['recommend_to_team'])) {
        $stats['recommend_to_team'][$response['recommend_to_team']] = ($stats['recommend_to_team'][$response['recommend_to_team']] ?? 0) + 1;
    }
    
    // Decision Maker
    if (!empty($response['decision_maker'])) {
        $stats['decision_maker'][$response['decision_maker']] = ($stats['decision_maker'][$response['decision_maker']] ?? 0) + 1;
    }
}

// Ordenar estatísticas por frequência (maior para menor)
foreach ($stats as $key => $values) {
    arsort($stats[$key]);
}

// Valores únicos para filtros
$uniqueDevLevels = array_unique(array_column($allResponses, 'dev_level'));
$uniqueDevLevels = array_filter($uniqueDevLevels);
sort($uniqueDevLevels);

$uniqueStacks = array_unique(array_column($allResponses, 'main_stack'));
$uniqueStacks = array_filter($uniqueStacks);
sort($uniqueStacks);

// Inicializar timestamp de última verificação se não existir
if (!isset($_SESSION['survey_last_check'])) {
    $_SESSION['survey_last_check'] = time();
}

?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Pesquisas | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } } }
    </script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
        }
        .tab-content { 
            display: none; 
        }
        .tab-content.active { 
            display: block; 
        }
        .tab-button.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-bottom: 2px solid white;
        }
        .bar-chart-bar {
            transition: width 0.3s ease;
        }
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
</head>
<body class="bg-black text-white min-h-screen">
    
    <!-- Navbar -->
    <nav class="border-b border-zinc-900 bg-black sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-14">
                <div class="flex items-center gap-3">
                    <img src="assets/img/logos (6).png" alt="SafeNode" class="h-6 w-auto">
                    <span class="font-semibold text-base text-white hidden sm:inline">Survey Admin</span>
                </div>
                <a href="survey-admin.php?logout=1" class="text-sm text-zinc-400 hover:text-white transition-colors">
                    Sair
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        
        <!-- Header Section -->
        <div class="mb-6 sm:mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold mb-1 text-white">Painel de Pesquisas</h1>
            <p class="text-sm sm:text-base text-zinc-400">Análise completa das respostas</p>
        </div>

        <!-- Estatísticas Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 sm:mb-8">
            <div class="bg-gradient-to-br from-blue-500/10 to-blue-600/5 border border-blue-500/20 rounded-xl p-4 sm:p-5 hover:border-blue-500/40 transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-zinc-400 text-xs sm:text-sm mb-1.5 font-medium">Total</p>
                        <p class="text-2xl sm:text-3xl font-bold text-blue-400"><?php echo htmlspecialchars($totalResponses, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-500/20 rounded-lg flex items-center justify-center">
                        <i data-lucide="file-text" class="w-5 h-5 sm:w-6 sm:h-6 text-blue-400"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 border border-emerald-500/20 rounded-xl p-4 sm:p-5 hover:border-emerald-500/40 transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-zinc-400 text-xs sm:text-sm mb-1.5 font-medium">Agradecidos</p>
                        <p class="text-2xl sm:text-3xl font-bold text-emerald-400"><?php echo htmlspecialchars($thankedCount, ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="text-xs text-emerald-400/70 mt-0.5"><?php echo $totalResponses > 0 ? round(($thankedCount / $totalResponses) * 100, 1) : 0; ?>% do total</p>
                    </div>
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-emerald-500/20 rounded-lg flex items-center justify-center">
                        <i data-lucide="mail" class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-400"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-amber-500/10 to-amber-600/5 border border-amber-500/20 rounded-xl p-4 sm:p-5 hover:border-amber-500/40 transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-zinc-400 text-xs sm:text-sm mb-1.5 font-medium">Pendentes</p>
                        <p class="text-2xl sm:text-3xl font-bold text-amber-400"><?php echo htmlspecialchars($pendingCount, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-amber-500/20 rounded-lg flex items-center justify-center">
                        <i data-lucide="clock" class="w-5 h-5 sm:w-6 sm:h-6 text-amber-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message -->
        <?php if ($message): ?>
        <div class="mb-4 sm:mb-6 p-3 sm:p-4 rounded-xl <?php echo $messageType === 'success' ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-400' : 'bg-red-500/10 border border-red-500/30 text-red-400'; ?>">
            <div class="flex items-center gap-2.5 text-sm">
                <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-4 h-4 flex-shrink-0"></i>
                <span class="font-medium"><?php echo $message; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Abas de Navegação -->
        <div class="mb-4 sm:mb-6">
            <div class="flex gap-1 border-b border-zinc-800 overflow-x-auto">
                <button onclick="switchTab('responses')" class="tab-button px-4 sm:px-6 py-2.5 sm:py-3 text-xs sm:text-sm font-medium rounded-t-lg transition-all whitespace-nowrap border-b-2 border-transparent <?php echo $currentTab === 'responses' ? 'active' : 'text-zinc-400 hover:text-zinc-300 hover:bg-zinc-900/50'; ?>">
                    <span class="hidden sm:inline">Respostas</span>
                    <span class="sm:hidden">Lista</span>
                </button>
                <button onclick="switchTab('statistics')" class="tab-button px-4 sm:px-6 py-2.5 sm:py-3 text-xs sm:text-sm font-medium rounded-t-lg transition-all whitespace-nowrap border-b-2 border-transparent <?php echo $currentTab === 'statistics' ? 'active' : 'text-zinc-400 hover:text-zinc-300 hover:bg-zinc-900/50'; ?>">
                    <span class="hidden sm:inline">Estatísticas</span>
                    <span class="sm:hidden">Stats</span>
                </button>
                <button onclick="switchTab('analytics')" class="tab-button px-4 sm:px-6 py-2.5 sm:py-3 text-xs sm:text-sm font-medium rounded-t-lg transition-all whitespace-nowrap border-b-2 border-transparent <?php echo $currentTab === 'analytics' ? 'active' : 'text-zinc-400 hover:text-zinc-300 hover:bg-zinc-900/50'; ?>">
                    <span class="hidden sm:inline">Análises</span>
                    <span class="sm:hidden">Análise</span>
                </button>
            </div>
        </div>

        <!-- Tab: Respostas -->
        <div id="tab-responses" class="tab-content <?php echo $currentTab === 'responses' ? 'active' : ''; ?>">
            <!-- Filtros -->
            <div class="bg-black border border-zinc-800 rounded-lg p-4 mb-4 sm:mb-6">
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <input type="hidden" name="tab" value="responses">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-zinc-400 mb-1.5">Status</label>
                        <select name="status" class="w-full px-3 py-2 text-sm bg-black border border-zinc-800 rounded-lg text-white focus:outline-none focus:border-zinc-700">
                            <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>Todos</option>
                            <option value="thanked" <?php echo $filterStatus === 'thanked' ? 'selected' : ''; ?>>Agradecidos</option>
                            <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pendentes</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-zinc-400 mb-1.5">Nível Dev</label>
                        <select name="dev_level" class="w-full px-3 py-2 text-sm bg-black border border-zinc-800 rounded-lg text-white focus:outline-none focus:border-zinc-700">
                            <option value="all">Todos</option>
                            <?php foreach ($uniqueDevLevels as $level): ?>
                            <option value="<?php echo htmlspecialchars($level, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $filterDevLevel === $level ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($level, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-zinc-400 mb-1.5">Stack</label>
                        <select name="stack" class="w-full px-3 py-2 text-sm bg-black border border-zinc-800 rounded-lg text-white focus:outline-none focus:border-zinc-700">
                            <option value="all">Todos</option>
                            <?php foreach ($uniqueStacks as $stack): ?>
                            <option value="<?php echo htmlspecialchars($stack, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $filterStack === $stack ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($stack, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabela de Respostas -->
            <div class="bg-black border border-zinc-800 rounded-lg overflow-hidden">
                <?php if (empty($responses)): ?>
                <div class="p-8 sm:p-12 text-center text-zinc-400">
                    <i data-lucide="inbox" class="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-3 sm:mb-4 opacity-50"></i>
                    <p class="text-sm sm:text-base">Nenhuma resposta encontrada.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-950 border-b border-zinc-800">
                            <tr>
                                <th class="px-3 sm:px-4 lg:px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">ID</th>
                                <th class="px-3 sm:px-4 lg:px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">Email</th>
                                <th class="hidden sm:table-cell px-4 lg:px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">Nível</th>
                                <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">Stack</th>
                                <th class="hidden md:table-cell px-4 lg:px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">Data</th>
                                <th class="px-3 sm:px-4 lg:px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">Status</th>
                                <th class="px-3 sm:px-4 lg:px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <?php foreach ($responses as $response): ?>
                            <tr class="hover:bg-zinc-800/50 transition-colors">
                                <td class="px-3 sm:px-4 lg:px-6 py-3 whitespace-nowrap text-xs sm:text-sm text-zinc-400 font-mono">#<?php echo htmlspecialchars($response['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-3 sm:px-4 lg:px-6 py-3 text-xs sm:text-sm text-zinc-300">
                                    <div class="max-w-[150px] sm:max-w-none truncate">
                                        <?php echo htmlspecialchars($response['email'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="sm:hidden text-xs text-zinc-500 mt-0.5">
                                        <?php echo htmlspecialchars($response['dev_level'] ?? '-', ENT_QUOTES, 'UTF-8'); ?> • <?php echo date('d/m/Y', strtotime($response['created_at'])); ?>
                                    </div>
                                </td>
                                <td class="hidden sm:table-cell px-4 lg:px-6 py-3 whitespace-nowrap text-xs sm:text-sm text-zinc-400"><?php echo htmlspecialchars($response['dev_level'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="hidden lg:table-cell px-6 py-3 whitespace-nowrap text-xs sm:text-sm text-zinc-400"><?php echo htmlspecialchars($response['main_stack'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="hidden md:table-cell px-4 lg:px-6 py-3 whitespace-nowrap text-xs sm:text-sm text-zinc-400">
                                    <?php echo date('d/m/Y H:i', strtotime($response['created_at'])); ?>
                                </td>
                                <td class="px-3 sm:px-4 lg:px-6 py-3 whitespace-nowrap">
                                    <?php if ($response['thanked_at']): ?>
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                        OK
                                    </span>
                                    <?php else: ?>
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                        Pendente
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 sm:px-4 lg:px-6 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 sm:gap-2">
                                        <button onclick="viewResponse(<?php echo $response['id']; ?>)" class="px-2.5 sm:px-3 py-1 text-xs bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 border border-blue-600/30 rounded-lg transition-colors font-medium cursor-pointer">
                                            Ver
                                        </button>
                                        <?php if (!$response['thanked_at']): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Enviar email?');">
                                            <input type="hidden" name="response_id" value="<?php echo $response['id']; ?>">
                                            <button type="submit" name="send_thanks" class="px-2.5 sm:px-3 py-1 text-xs bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-600/30 rounded-lg transition-colors font-medium">
                                                Email
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Excluir?');">
                                            <input type="hidden" name="response_id" value="<?php echo $response['id']; ?>">
                                            <button type="submit" name="delete_response" class="px-2.5 sm:px-3 py-1 text-xs bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-600/30 rounded-lg transition-colors font-medium">
                                                Del
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab: Estatísticas -->
        <div id="tab-statistics" class="tab-content <?php echo $currentTab === 'statistics' ? 'active' : ''; ?>">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                <?php
                function renderBarChart($title, $data, $maxValue) {
                    if (empty($data)) return;
                    echo '<div class="bg-black border border-zinc-800 rounded-xl p-4 sm:p-6">';
                    if (!empty($title)) {
                        echo '<h3 class="text-base sm:text-lg font-semibold mb-5 text-white">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3>';
                    }
                    echo '<div class="space-y-4">';
                    foreach ($data as $label => $count) {
                        $percentage = $maxValue > 0 ? ($count / $maxValue) * 100 : 0;
                        echo '<div>';
                        echo '<div class="flex justify-between items-center mb-2">';
                        echo '<span class="text-xs sm:text-sm text-zinc-300 font-medium">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
                        echo '<span class="text-xs sm:text-sm font-semibold text-zinc-400">' . $count . '</span>';
                        echo '</div>';
                        echo '<div class="w-full bg-zinc-950/50 rounded-full h-2 overflow-hidden">';
                        echo '<div class="bar-chart-bar bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full" style="width: ' . $percentage . '%"></div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
                
                if (!empty($stats['dev_level'])) {
                    $maxDevLevel = max($stats['dev_level']);
                    renderBarChart('Nível de Desenvolvedor', array_slice($stats['dev_level'], 0, 10), $maxDevLevel);
                }
                
                if (!empty($stats['work_type'])) {
                    $maxWorkType = max($stats['work_type']);
                    renderBarChart('Tipo de Trabalho', array_slice($stats['work_type'], 0, 10), $maxWorkType);
                }
                
                if (!empty($stats['main_stack'])) {
                    $maxStack = max($stats['main_stack']);
                    renderBarChart('Stack Principal', array_slice($stats['main_stack'], 0, 10), $maxStack);
                }
                
                if (!empty($stats['time_wasted'])) {
                    $maxTime = max($stats['time_wasted']);
                    renderBarChart('Tempo Perdido por Semana', array_slice($stats['time_wasted'], 0, 10), $maxTime);
                }
                
                if (!empty($stats['platform_help'])) {
                    $maxHelp = max($stats['platform_help']);
                    renderBarChart('Plataforma Ajudaria?', array_slice($stats['platform_help'], 0, 10), $maxHelp);
                }
                
                if (!empty($stats['first_feature'])) {
                    $maxFeature = max($stats['first_feature']);
                    renderBarChart('Primeira Feature a Usar', array_slice($stats['first_feature'], 0, 10), $maxFeature);
                }
                ?>
            </div>
        </div>

        <!-- Tab: Análises -->
        <div id="tab-analytics" class="tab-content <?php echo $currentTab === 'analytics' ? 'active' : ''; ?>">
            <div class="space-y-4 sm:space-y-6">
                <!-- Top Pain Points -->
                <?php if (!empty($stats['pain_points'])): ?>
                <div class="bg-black border border-zinc-800 rounded-lg p-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-semibold mb-4 text-white">Top Pontos de Dor</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                        <?php 
                        $topPainPoints = array_slice($stats['pain_points'], 0, 9, true);
                        $maxPain = max($stats['pain_points']);
                        foreach ($topPainPoints as $point => $count):
                            $percentage = ($count / $maxPain) * 100;
                        ?>
                        <div class="bg-zinc-950/50 border border-red-500/20 rounded-xl p-3 sm:p-4 hover:border-red-500/40 transition-all">
                            <div class="flex justify-between items-start mb-2.5">
                                <span class="text-xs sm:text-sm font-medium text-zinc-300 flex-1 pr-2"><?php echo htmlspecialchars($point, ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="text-base sm:text-lg font-bold text-red-400 flex-shrink-0"><?php echo $count; ?></span>
                            </div>
                            <div class="w-full bg-zinc-950/50 rounded-full h-2 mb-1.5">
                                <div class="bg-gradient-to-r from-red-500 to-red-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <p class="text-xs text-red-400/70 font-medium"><?php echo round(($count / $totalResponses) * 100, 1); ?>% das respostas</p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Preço Disposto a Pagar -->
                <?php if (!empty($stats['price_willing'])): ?>
                <div class="bg-black border border-zinc-800 rounded-lg p-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-semibold mb-4 text-white">Preço Disposto a Pagar</h3>
                    <?php 
                    $maxPrice = max($stats['price_willing']);
                    renderBarChart('', $stats['price_willing'], $maxPrice);
                    ?>
                </div>
                <?php endif; ?>

                <!-- Uso em Produção -->
                <?php if (!empty($stats['use_in_production'])): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                    <div class="bg-black border border-zinc-800 rounded-lg p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-semibold mb-4 text-white">Uso em Produção</h3>
                        <?php 
                        $maxProd = max($stats['use_in_production']);
                        renderBarChart('', $stats['use_in_production'], $maxProd);
                        ?>
                    </div>
                    
                    <div class="bg-black border border-zinc-800 rounded-lg p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-semibold mb-4 text-white">Recomendaria para Time</h3>
                        <?php 
                        $maxRec = max($stats['recommend_to_team']);
                        renderBarChart('', $stats['recommend_to_team'], $maxRec);
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Análise de IA -->
                <?php if (!empty($stats['use_ai_analysis'])): ?>
                <div class="bg-black border border-zinc-800 rounded-lg p-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-semibold mb-4 text-white">Análise de IA</h3>
                    <?php 
                    $maxAI = max($stats['use_ai_analysis']);
                    renderBarChart('', $stats['use_ai_analysis'], $maxAI);
                    ?>
                </div>
                <?php endif; ?>

                <!-- Decision Maker -->
                <?php if (!empty($stats['decision_maker'])): ?>
                <div class="bg-black border border-zinc-800 rounded-lg p-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-semibold mb-4 text-white">Quem Decide Usar a Ferramenta</h3>
                    <?php 
                    $maxDecision = max($stats['decision_maker']);
                    renderBarChart('', $stats['decision_maker'], $maxDecision);
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <!-- Modal de Visualização de Resposta -->
    <?php if ($viewResponse): ?>
    <div id="responseModal" class="fixed inset-0 bg-black z-50" style="display: flex;">
        <div class="w-full h-full flex flex-col bg-black">
            <div class="flex-shrink-0 bg-black border-b border-zinc-800 px-6 py-4 flex items-center justify-between">
                <h2 class="text-xl font-bold text-white">Detalhes da Resposta #<?php echo htmlspecialchars($viewResponse['id'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <a href="survey-admin.php?tab=responses" class="text-zinc-400 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </a>
            </div>
            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-5xl mx-auto space-y-4">
                <?php
                // Mapeamento de valores do banco para formato legível
                $valueMap = [
                    // Dev Level
                    'estudante' => 'Estudante',
                    'júnior' => 'Júnior',
                    'pleno' => 'Pleno',
                    'sênior' => 'Sênior',
                    'tech_lead-/_arquiteto' => 'Tech Lead / Arquiteto',
                    'fundador' => 'Fundador',
                    // Work Type
                    'dev_solo-/_freelancer' => 'Dev solo / freelancer',
                    'startup' => 'Startup',
                    'empresa_média' => 'Empresa média',
                    'empresa_grande' => 'Empresa grande',
                    // Stack
                    'javascript-/_typescript' => 'JavaScript / TypeScript',
                    'node.js' => 'Node.js',
                    'php' => 'PHP',
                    'python' => 'Python',
                    'java' => 'Java',
                    'go' => 'Go',
                    'outra' => 'Outra',
                    // Time Wasted
                    'menos_de_1h' => 'Menos de 1h',
                    '1-3h' => '1–3h',
                    '3-5h' => '3–5h',
                    'mais_de_5h' => 'Mais de 5h',
                    // Platform Help
                    'muito' => 'Muito',
                    'um_pouco' => 'Um pouco',
                    'não_vejo_valor' => 'Não vejo valor',
                    // First Feature (valores reais do formulário)
                    'api_rest_de_e_mails_transacionais' => 'API REST de e-mails transacionais',
                    'integra_o_automática_com_hospedagem_vps' => 'Integração automática com hospedagem/VPS',
                    'editor_visual_de_templates_relay' => 'Editor visual de templates (Relay)',
                    'ide_safecode_para_desenvolvimento' => 'IDE SafeCode para desenvolvimento',
                    'dashboard_com_analytics_e_monitoramento' => 'Dashboard com analytics e monitoramento',
                    'prote_o_e_seguran_a_ddos_waf' => 'Proteção e segurança (DDoS, WAF)',
                    // Use AI Analysis
                    'sim_com_certeza' => 'Sim, com certeza',
                    'talvez' => 'Talvez',
                    'não_preciso_disso' => 'Não preciso disso',
                    // Price Willing (valores reais do formulário)
                    '10-20' => 'US$ 10–20',
                    '20-50' => 'US$ 20–50',
                    '50-100' => 'US$ 50–100',
                    '100plus' => 'US$ 100+',
                    'só_usaria_se_fosse_free' => 'Só usaria se fosse free',
                    // Priority Choice (valores reais do formulário)
                    'reducao_de_tempo_com_configuracao_e_setup' => 'Redução de tempo com configuração e setup',
                    'automacao_total_de_processos' => 'Automação total de processos',
                    'reducao_de_custos' => 'Redução de custos',
                    'facilidade_de_uso_e_simplicidade' => 'Facilidade de uso e simplicidade',
                    // Use in Production
                    'sim' => 'Sim',
                    'não' => 'Não',
                    // Recommend to Team
                    // Decision Maker
                    'eu' => 'Eu',
                    'meu_time' => 'Meu time',
                    'a_empresa' => 'A empresa',
                    'diretoria-/_arquitetura' => 'Diretoria / Arquitetura',
                    // Pain Points
                    'configurar_e_mails_transacionais_smtp_dns_spf_dkim' => 'Configurar e-mails transacionais (SMTP, DNS, SPF, DKIM)',
                    'configurar_e_gerenciar_infraestrutura' => 'Configurar e gerenciar infraestrutura',
                    'deploy_e_configura_o_de_ambientes' => 'Deploy e configuração de ambientes',
                    'integra_o_de_serviços_com_hospedagem_vps' => 'Integração de serviços com hospedagem/VPS',
                    'seguran_a_e_prote_o_ddos_waf' => 'Segurança e proteção (DDoS, WAF)',
                    'monitoramento_e_analytics' => 'Monitoramento e analytics',
                    'custos_de_serviços_cloud' => 'Custos de serviços cloud',
                    'documenta_o_e_manuten_o' => 'Documentação e manutenção',
                    'debug_em_produ_o' => 'Debug em produção',
                    'comunica_o_entre_serviços' => 'Comunicação entre serviços',
                ];
                
                // Helper para formatar valores de select/radio
                function formatValue($value, $valueMap = []) {
                    if (empty($value)) return '';
                    
                    // Tentar encontrar no mapa primeiro
                    $key = strtolower(trim($value));
                    if (isset($valueMap[$key])) {
                        return $valueMap[$key];
                    }
                    
                    // Se não encontrar, fazer formatação básica
                    $value = str_replace(['_', '-'], ' ', $value);
                    // Capitalizar primeira letra de cada palavra, mas manter acentos
                    $value = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
                    
                    // Corrigir casos especiais
                    $value = str_replace(['Ddos', 'Waf', 'Smtp', 'Dns', 'Spf', 'Dkim', 'Vps'], ['DDoS', 'WAF', 'SMTP', 'DNS', 'SPF', 'DKIM', 'VPS'], $value);
                    
                    return $value;
                }
                
                // Email
                if (!empty($viewResponse['email'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Email</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . htmlspecialchars($viewResponse['email'], ENT_QUOTES, 'UTF-8') . '</div></div>';
                }
                
                // Perfil
                if (!empty($viewResponse['dev_level'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Nível de Desenvolvedor</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . htmlspecialchars(formatValue($viewResponse['dev_level'], $valueMap), ENT_QUOTES, 'UTF-8') . '</div></div>';
                }
                if (!empty($viewResponse['work_type'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Tipo de Trabalho</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . htmlspecialchars(formatValue($viewResponse['work_type'], $valueMap), ENT_QUOTES, 'UTF-8') . '</div></div>';
                }
                if (!empty($viewResponse['main_stack'])) {
                    $stack = htmlspecialchars(formatValue($viewResponse['main_stack'], $valueMap), ENT_QUOTES, 'UTF-8');
                    if (!empty($viewResponse['main_stack_other'])) {
                        $stack .= ' - ' . htmlspecialchars($viewResponse['main_stack_other'], ENT_QUOTES, 'UTF-8');
                    }
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Stack Principal</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . $stack . '</div></div>';
                }
                
                // Pontos de Dor
                if (!empty($viewResponse['pain_points_array'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Pontos de Dor</label><div class="space-y-1">';
                    foreach ($viewResponse['pain_points_array'] as $point) {
                        $formattedPoint = formatValue($point, $valueMap);
                        echo '<div class="px-3 py-2 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . htmlspecialchars($formattedPoint, ENT_QUOTES, 'UTF-8') . '</div>';
                    }
                    echo '</div></div>';
                }
                
                // Tempo
                if (!empty($viewResponse['time_wasted_per_week'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Tempo Perdido por Semana</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . htmlspecialchars(formatValue($viewResponse['time_wasted_per_week'], $valueMap), ENT_QUOTES, 'UTF-8') . '</div></div>';
                }
                
                // SafeNode
                if (!empty($viewResponse['platform_help'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Plataforma Ajudaria?</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . htmlspecialchars(formatValue($viewResponse['platform_help'], $valueMap), ENT_QUOTES, 'UTF-8') . '</div></div>';
                }
                if (!empty($viewResponse['first_feature'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Primeira Feature</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . htmlspecialchars(formatValue($viewResponse['first_feature'], $valueMap), ENT_QUOTES, 'UTF-8') . '</div></div>';
                }
                if (!empty($viewResponse['use_ai_analysis'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Usaria IA/Análise</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . htmlspecialchars(formatValue($viewResponse['use_ai_analysis'], $valueMap), ENT_QUOTES, 'UTF-8') . '</div></div>';
                }
                
                // Preço
                if (!empty($viewResponse['price_willing'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Preço Disposto a Pagar</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . htmlspecialchars(formatValue($viewResponse['price_willing'], $valueMap), ENT_QUOTES, 'UTF-8') . '</div></div>';
                }
                if (!empty($viewResponse['price_reason'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Motivo da Escolha de Preço</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300 whitespace-pre-wrap">' . nl2br(htmlspecialchars($viewResponse['price_reason'], ENT_QUOTES, 'UTF-8')) . '</div></div>';
                }
                
                // Prioridade
                if (!empty($viewResponse['priority_choice'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Prioridade</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . htmlspecialchars(formatValue($viewResponse['priority_choice'], $valueMap), ENT_QUOTES, 'UTF-8') . '</div></div>';
                }
                
                // NPS
                if (isset($viewResponse['nps_score']) && $viewResponse['nps_score'] !== null) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">NPS Score</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . htmlspecialchars($viewResponse['nps_score'], ENT_QUOTES, 'UTF-8') . '/10</div></div>';
                }
                
                // Ferramentas
                if (!empty($viewResponse['current_tools'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Ferramentas Atuais</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300 whitespace-pre-wrap">' . nl2br(htmlspecialchars($viewResponse['current_tools'], ENT_QUOTES, 'UTF-8')) . '</div></div>';
                }
                
                // Uso Profissional
                if (!empty($viewResponse['use_in_production'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Usaria em Produção</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . htmlspecialchars(formatValue($viewResponse['use_in_production'], $valueMap), ENT_QUOTES, 'UTF-8') . '</div></div>';
                }
                if (!empty($viewResponse['recommend_to_team'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Recomendaria para Time</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . htmlspecialchars(formatValue($viewResponse['recommend_to_team'], $valueMap), ENT_QUOTES, 'UTF-8') . '</div></div>';
                }
                if (!empty($viewResponse['decision_maker'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Quem Decide</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . htmlspecialchars(formatValue($viewResponse['decision_maker'], $valueMap), ENT_QUOTES, 'UTF-8') . '</div></div>';
                }
                
                // Fechamento
                if (!empty($viewResponse['switch_reasons'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Motivos para Trocar</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300 whitespace-pre-wrap">' . nl2br(htmlspecialchars($viewResponse['switch_reasons'], ENT_QUOTES, 'UTF-8')) . '</div></div>';
                }
                if (!empty($viewResponse['must_have_features'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Features Essenciais</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300 whitespace-pre-wrap">' . nl2br(htmlspecialchars($viewResponse['must_have_features'], ENT_QUOTES, 'UTF-8')) . '</div></div>';
                }
                
                // Datas
                echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Data de Criação</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . date('d/m/Y H:i:s', strtotime($viewResponse['created_at'])) . '</div></div>';
                if (!empty($viewResponse['thanked_at'])) {
                    echo '<div class="mb-4"><label class="block text-sm font-semibold text-zinc-400 mb-2">Agradecimento Enviado em</label><div class="px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-zinc-300">' . date('d/m/Y H:i:s', strtotime($viewResponse['thanked_at'])) . '</div></div>';
                }
                ?>
                </div>
            </div>
            <div class="flex-shrink-0 bg-black border-t border-zinc-800 px-6 py-4 flex justify-end gap-3">
                <a href="survey-admin.php?tab=responses" class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-white rounded-lg text-sm font-medium transition-colors">
                    Fechar
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        lucide.createIcons();
        
        function viewResponse(id) {
            window.location.href = 'survey-admin.php?tab=responses&view=' + id;
        }
        
        function switchTab(tabName) {
            // Ocultar todas as abas
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remover active de todos os botões
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
                btn.classList.add('text-zinc-400', 'hover:text-zinc-300', 'hover:bg-zinc-900/50');
            });
            
            // Mostrar aba selecionada
            const targetTab = document.getElementById('tab-' + tabName);
            if (targetTab) {
                targetTab.classList.add('active');
            }
            
            // Ativar botão selecionado
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(btn => {
                const onclick = btn.getAttribute('onclick');
                if (onclick && onclick.includes("'" + tabName + "'")) {
                    btn.classList.add('active');
                    btn.classList.remove('text-zinc-400', 'hover:text-zinc-300', 'hover:bg-zinc-900/50');
                }
            });
            
            // Atualizar URL sem recarregar
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url.toString());
            
            // Recriar ícones (pode ser necessário se houver novos elementos)
            lucide.createIcons();
        }
    </script>

</body>
</html>
