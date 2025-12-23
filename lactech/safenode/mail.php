<?php
/**
 * SafeNode Mail - Dashboard
 * Sistema de envio de e-mails simples e previsível
 */

session_start();
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/MailService.php';

$db = getSafeNodeDatabase();
$userId = $_SESSION['safenode_user_id'] ?? null;
$pageTitle = 'SafeNode Mail';
$message = '';
$messageType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_project') {
        $name = trim($_POST['name'] ?? '');
        $senderEmail = trim($_POST['sender_email'] ?? '');
        $senderName = trim($_POST['sender_name'] ?? '');
        $emailFunction = trim($_POST['email_function'] ?? '');
        $htmlTemplate = trim($_POST['html_template'] ?? '');
        
        if (empty($name) || empty($senderEmail)) {
            $message = 'Nome do projeto e e-mail remetente são obrigatórios';
            $messageType = 'error';
        } else if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            $message = 'E-mail remetente inválido';
            $messageType = 'error';
        } else if (empty($emailFunction)) {
            $message = 'Função do e-mail é obrigatória';
            $messageType = 'error';
        } else if (empty($htmlTemplate)) {
            $message = 'Template HTML é obrigatório';
            $messageType = 'error';
        } else {
            $result = MailService::createProject($db, $userId, $name, $senderEmail, $senderName, $emailFunction, $htmlTemplate);
            if ($result['success']) {
                $message = 'Projeto criado com sucesso! Token: ' . $result['token'];
                $messageType = 'success';
            } else {
                $message = $result['error'] ?? 'Erro ao criar projeto';
                $messageType = 'error';
            }
        }
    } else if ($action === 'test_send') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $testEmail = trim($_POST['test_email'] ?? '');
        
        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $message = 'E-mail de teste inválido';
            $messageType = 'error';
        } else {
            $mailService = new MailService($db, $projectId);
            $result = $mailService->send(
                $testEmail,
                'Teste SafeNode Mail',
                '<h1>Teste de Envio</h1><p>Este é um e-mail de teste do SafeNode Mail.</p>',
                'Teste de Envio\n\nEste é um e-mail de teste do SafeNode Mail.'
            );
            
            if ($result['success']) {
                $message = 'E-mail de teste enviado com sucesso!';
                $messageType = 'success';
            } else {
                $message = $result['error'] ?? 'Erro ao enviar e-mail de teste';
                $messageType = 'error';
            }
        }
    } else if ($action === 'delete_project') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $projectName = trim($_POST['project_name'] ?? '');
        $confirmName = trim($_POST['confirm_name'] ?? '');
        
        if (empty($projectId) || empty($projectName) || empty($confirmName)) {
            $message = 'Dados inválidos para exclusão';
            $messageType = 'error';
        } else if ($projectName !== $confirmName) {
            $message = 'O nome digitado não corresponde ao nome do projeto';
            $messageType = 'error';
        } else {
            // Verificar se o projeto pertence ao usuário
            try {
                $stmt = $db->prepare("SELECT id, name FROM safenode_mail_projects WHERE id = ? AND user_id = ?");
                $stmt->execute([$projectId, $userId]);
                $project = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$project) {
                    $message = 'Projeto não encontrado ou você não tem permissão para excluí-lo';
                    $messageType = 'error';
                } else if ($project['name'] !== $projectName) {
                    $message = 'Nome do projeto não corresponde';
                    $messageType = 'error';
                } else {
                    // Excluir projeto (CASCADE vai excluir logs, templates, etc)
                    $stmt = $db->prepare("DELETE FROM safenode_mail_projects WHERE id = ? AND user_id = ?");
                    $stmt->execute([$projectId, $userId]);
                    
                    $message = 'Projeto excluído com sucesso!';
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                error_log("SafeNode Mail Delete Error: " . $e->getMessage());
                $message = 'Erro ao excluir projeto: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Buscar projetos do usuário
$projects = [];
$totalStats = [
    'total_projects' => 0,
    'total_sent' => 0,
    'total_errors' => 0,
    'total_this_month' => 0,
    'total_limit' => 0
];
if ($db && $userId) {
    try {
        $stmt = $db->prepare("
            SELECT p.*, 
                   COUNT(l.id) as total_logs,
                   SUM(CASE WHEN l.status = 'sent' THEN 1 ELSE 0 END) as sent_count,
                   SUM(CASE WHEN l.status = 'error' THEN 1 ELSE 0 END) as error_count
            FROM safenode_mail_projects p
            LEFT JOIN safenode_mail_logs l ON p.id = l.project_id
            WHERE p.user_id = ?
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$userId]);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Adicionar estatísticas e calcular totais
        foreach ($projects as &$project) {
            $mailService = new MailService($db, $project['id']);
            $project['stats'] = $mailService->getStats();
            
            // Acumular totais
            $totalStats['total_projects']++;
            $totalStats['total_sent'] += $project['stats']['total_sent'] ?? 0;
            $totalStats['total_errors'] += $project['stats']['total_errors'] ?? 0;
            $totalStats['total_this_month'] += $project['stats']['emails_sent_this_month'] ?? 0;
            $totalStats['total_limit'] += $project['stats']['monthly_limit'] ?? 0;
        }
        
        // Buscar dados para gráfico mensal (últimos 30 dias)
        $projectIds = array_column($projects, 'id');
        if (!empty($projectIds)) {
            $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
            $stmt = $db->prepare("
                SELECT DATE(created_at) as date, 
                       COUNT(*) as total,
                       SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                       SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
                FROM safenode_mail_logs
                WHERE project_id IN ($placeholders)
                  AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");
            $stmt->execute($projectIds);
            $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $monthlyData = [];
        }
    } catch (PDOException $e) {
        error_log("SafeNode Mail Error: " . $e->getMessage());
        $monthlyData = [];
    }
} else {
    $monthlyData = [];
}

// Buscar últimos logs
$recentLogs = [];
if ($db && $userId && !empty($projects)) {
    try {
        $projectIds = array_column($projects, 'id');
        $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
        $stmt = $db->prepare("
            SELECT l.*, p.name as project_name
            FROM safenode_mail_logs l
            INNER JOIN safenode_mail_projects p ON l.project_id = p.id
            WHERE l.project_id IN ($placeholders)
            ORDER BY l.created_at DESC
            LIMIT 50
        ");
        $stmt->execute($projectIds);
        $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SafeNode Mail Logs Error: " . $e->getMessage());
    }
}

// Buscar templates dos projetos
$templates = [];
if ($db && $userId && !empty($projects)) {
    try {
        $projectIds = array_column($projects, 'id');
        $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
        $stmt = $db->prepare("
            SELECT t.*, p.name as project_name
            FROM safenode_mail_templates t
            INNER JOIN safenode_mail_projects p ON t.project_id = p.id
            WHERE t.project_id IN ($placeholders)
            ORDER BY t.created_at DESC
        ");
        $stmt->execute($projectIds);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SafeNode Mail Templates Error: " . $e->getMessage());
    }
}

$currentPage = 'mail';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        dark: {
                            950: '#030303',
                            900: '#050505',
                            850: '#080808',
                            800: '#0a0a0a',
                            700: '#0f0f0f',
                            600: '#141414',
                            500: '#1a1a1a',
                            400: '#222222',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        [x-cloak] { display: none !important; }
        
        :root {
            --bg-primary: #030303;
            --bg-secondary: #080808;
            --bg-tertiary: #0f0f0f;
            --bg-card: #0a0a0a;
            --bg-hover: #111111;
            --border-subtle: rgba(255,255,255,0.04);
            --border-light: rgba(255,255,255,0.08);
            --accent: #ffffff;
            --accent-glow: rgba(255, 255, 255, 0.2);
            --text-primary: #ffffff;
            --text-secondary: #a1a1aa;
            --text-muted: #52525b;
        }
        
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">
    <div class="flex h-full">
        <?php
        // Código da Sidebar (copiado de includes/sidebar.php)
        // Iniciar sessão se necessário
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Carregar Router se estiver logado
        $useProtectedUrls = false;
        if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
            require_once __DIR__ . '/includes/Router.php';
            SafeNodeRouter::init();
            $useProtectedUrls = true;
        }

        // Função helper para gerar URLs (sem token)
        if (!function_exists('getSafeNodeUrl')) {
            function getSafeNodeUrl($route, $siteId = null) {
                $pagePath = strpos($route, '.php') !== false ? $route : $route . '.php';
                return $pagePath;
            }
        }

        // Detectar página atual
        $currentPage = basename($_SERVER['PHP_SELF'], '.php');
        if (isset($_GET['route'])) {
            $currentPage = 'dashboard';
        }

        // Buscar sequência de proteção
        $protectionStreak = null;
        if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
            $userId = $_SESSION['safenode_user_id'] ?? null;
            $siteId = $_SESSION['view_site_id'] ?? 0;
            
            if ($userId) {
                require_once __DIR__ . '/includes/ProtectionStreak.php';
                $streakManager = new ProtectionStreak();
                $protectionStreak = $streakManager->getStreak($userId, $siteId);
            }
        }
        ?>
        <?php include __DIR__ . '/includes/sidebar-content.php'; ?>
        
        <!-- Backdrop para mobile -->
        <div id="safenode-sidebar-backdrop" class="fixed inset-0 bg-black/60 z-40 hidden lg:hidden"></div>
        
        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-full overflow-hidden bg-dark-950">
            <!-- Header -->
            <header class="h-20 bg-dark-900/50 backdrop-blur-xl border-b border-white/5 px-4 md:px-8 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3 md:gap-6">
                    <button data-sidebar-toggle class="lg:hidden text-zinc-400 hover:text-white transition-colors">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <div>
                        <h2 class="text-xl md:text-2xl font-bold text-white tracking-tight">SafeNode Mail</h2>
                        <p class="text-sm text-zinc-500">Envio de e-mails simples e previsível</p>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-4 md:p-8" x-data="{ activeTab: 'metrics', showMessage: <?php echo $message ? 'true' : 'false'; ?> }">
                <?php if ($message): ?>
                <div x-show="showMessage"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 translate-y-2"
                     x-init="setTimeout(() => showMessage = false, 4000)"
                     class="bg-dark-800 border border-white/10 rounded-xl p-4 mb-6 <?php echo $messageType === 'success' ? 'border-green-500/30 bg-green-500/10' : 'border-red-500/30 bg-red-500/10'; ?>">
                    <p class="text-white"><?php echo htmlspecialchars($message); ?></p>
                </div>
                <?php endif; ?>

                <!-- Tabs -->
                <div class="mb-6 border-b border-white/10">
                    <div class="flex gap-1">
                        <button @click="activeTab = 'metrics'" 
                                :class="activeTab === 'metrics' ? 'border-b-2 border-white text-white' : 'text-zinc-400 hover:text-white'"
                                class="px-6 py-3 font-semibold transition-colors">
                            <i data-lucide="bar-chart-3" class="w-4 h-4 inline mr-2"></i>
                            Métricas
                        </button>
                        <button @click="activeTab = 'create'" 
                                :class="activeTab === 'create' ? 'border-b-2 border-white text-white' : 'text-zinc-400 hover:text-white'"
                                class="px-6 py-3 font-semibold transition-colors">
                            <i data-lucide="plus-circle" class="w-4 h-4 inline mr-2"></i>
                            Criar Projeto
                        </button>
                    </div>
                </div>

                <!-- Tab: Métricas -->
                <div x-show="activeTab === 'metrics'" x-transition>
                <!-- Métricas Gerais -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                    <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-zinc-400">Total de Projetos</div>
                            <i data-lucide="folder" class="w-5 h-5 text-zinc-500"></i>
                        </div>
                        <div class="text-3xl font-bold text-white"><?php echo $totalStats['total_projects']; ?></div>
                    </div>
                    <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-zinc-400">Enviados Hoje</div>
                            <i data-lucide="send" class="w-5 h-5 text-blue-500"></i>
                        </div>
                        <div class="text-3xl font-bold text-white"><?php 
                        // Buscar envios de hoje (todos os projetos do usuário)
                        $todaySent = 0;
                        if ($db && $userId) {
                            try {
                                $stmt = $db->prepare("
                                    SELECT COUNT(*) as total
                                    FROM safenode_mail_logs l
                                    INNER JOIN safenode_mail_projects p ON l.project_id = p.id
                                    WHERE p.user_id = ?
                                      AND DATE(l.created_at) = CURDATE()
                                      AND l.status = 'sent'
                                ");
                                $stmt->execute([$userId]);
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $todaySent = $result['total'] ?? 0;
                            } catch (PDOException $e) {
                                error_log("SafeNode Mail Today Error: " . $e->getMessage());
                            }
                        }
                        echo $todaySent; 
                        ?></div>
                        <div class="text-xs text-zinc-500 mt-1">de 500 disponíveis (limite diário global)</div>
                    </div>
                    <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-zinc-400">Total Enviados</div>
                            <i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
                        </div>
                        <div class="text-3xl font-bold text-green-400"><?php echo number_format($totalStats['total_sent']); ?></div>
                    </div>
                    <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-zinc-400">Taxa de Sucesso</div>
                            <i data-lucide="trending-up" class="w-5 h-5 text-green-500"></i>
                        </div>
                        <div class="text-3xl font-bold text-white">
                            <?php 
                            $total = $totalStats['total_sent'] + $totalStats['total_errors'];
                            $rate = $total > 0 ? round(($totalStats['total_sent'] / $total) * 100) : 0;
                            echo $rate . '%';
                            ?>
                        </div>
                    </div>
                    <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm text-zinc-400">Restantes Hoje</div>
                            <i data-lucide="clock" class="w-5 h-5 text-blue-500"></i>
                        </div>
                        <div class="text-3xl font-bold text-green-400"><?php echo max(0, 500 - $todaySent); ?></div>
                        <div class="text-xs text-zinc-500 mt-1">Limite diário: 500 e-mails</div>
                    </div>
                </div>

                <!-- Gráfico de Uso Diário -->
                <?php if (!empty($monthlyData)): ?>
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6 mb-6">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-3">
                        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                        Uso Diário (Últimos 30 dias)
                    </h3>
                    <div class="h-64 flex items-end gap-2">
                        <?php 
                        $maxValue = max(array_column($monthlyData, 'total'));
                        $maxValue = $maxValue > 0 ? $maxValue : 1;
                        foreach ($monthlyData as $day): 
                            $height = ($day['total'] / $maxValue) * 100;
                            $sentHeight = $day['total'] > 0 ? ($day['sent'] / $day['total']) * 100 : 0;
                            $errorHeight = $day['total'] > 0 ? ($day['errors'] / $day['total']) * 100 : 0;
                        ?>
                        <div class="flex-1 flex flex-col items-center group relative" x-data="{ showTooltip: false }">
                            <div class="w-full flex flex-col-reverse gap-0.5 h-full">
                                <?php if ($day['errors'] > 0): ?>
                                <div class="bg-red-500/50 rounded-t" style="height: <?php echo $errorHeight; ?>%"></div>
                                <?php endif; ?>
                                <div class="bg-green-500 rounded-t" style="height: <?php echo $sentHeight; ?>%"></div>
                            </div>
                            <div class="text-xs text-zinc-500 mt-2 transform -rotate-45 origin-left whitespace-nowrap" style="writing-mode: vertical-rl;">
                                <?php echo date('d/m', strtotime($day['date'])); ?>
                            </div>
                            <div class="absolute bottom-full mb-2 hidden group-hover:block bg-dark-900 border border-white/10 rounded-lg p-2 text-xs whitespace-nowrap z-10">
                                <div class="text-white font-semibold mb-1"><?php echo date('d/m/Y', strtotime($day['date'])); ?></div>
                                <div class="text-green-400">✓ <?php echo $day['sent']; ?> enviados</div>
                                <?php if ($day['errors'] > 0): ?>
                                <div class="text-red-400">✗ <?php echo $day['errors']; ?> erros</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Lista de Projetos -->
                <div class="space-y-4 mt-6">
                    <?php foreach ($projects as $project): ?>
                    <div class="bg-dark-800 border border-white/10 rounded-xl p-6" data-project="<?php echo $project['id']; ?>">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-1"><?php echo htmlspecialchars($project['name']); ?></h3>
                                <p class="text-sm text-zinc-400"><?php echo htmlspecialchars($project['sender_email']); ?></p>
                                <?php if (!empty($project['email_function'])): 
                                    $functionNames = [
                                        'confirm_signup' => 'Confirmar Cadastro',
                                        'invite_user' => 'Convidar Usuário',
                                        'magic_link' => 'Link Mágico',
                                        'change_email' => 'Alterar E-mail',
                                        'reset_password' => 'Redefinir Senha',
                                        'reauthentication' => 'Reautenticação'
                                    ];
                                    $functionName = $functionNames[$project['email_function']] ?? $project['email_function'];
                                ?>
                                <p class="text-xs text-zinc-500 mt-1">
                                    <i data-lucide="mail" class="w-3 h-3 inline"></i> <?php echo htmlspecialchars($functionName); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <span class="px-3 py-1 rounded-lg text-xs font-semibold <?php echo $project['is_active'] ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'; ?>">
                                <?php echo $project['is_active'] ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </div>
                        
                        <!-- Estatísticas -->
                        <?php if (isset($project['stats'])): 
                            $total = $project['stats']['total_sent'] + $project['stats']['total_errors'];
                            $rate = $total > 0 ? round(($project['stats']['total_sent'] / $total) * 100) : 0;
                            // Buscar envios de hoje para este projeto
                            $todaySent = 0;
                            if ($db) {
                                try {
                                    $stmt = $db->prepare("
                                        SELECT COUNT(*) as total
                                        FROM safenode_mail_logs
                                        WHERE project_id = ?
                                          AND DATE(created_at) = CURDATE()
                                          AND status = 'sent'
                                    ");
                                    $stmt->execute([$project['id']]);
                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $todaySent = $result['total'] ?? 0;
                                } catch (PDOException $e) {
                                    error_log("Mail Today Error: " . $e->getMessage());
                                }
                            }
                        ?>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                            <div class="bg-dark-900/50 rounded-lg p-3">
                                <div class="text-xs text-zinc-400 mb-1">Limite Diário</div>
                                <div class="text-lg font-bold text-white">500</div>
                            </div>
                            <div class="bg-dark-900/50 rounded-lg p-3">
                                <div class="text-xs text-zinc-400 mb-1">Enviados Hoje</div>
                                <div class="text-lg font-bold text-white"><?php echo $todaySent; ?></div>
                                <div class="mt-2 w-full bg-dark-950 rounded-full h-1.5">
                                    <div class="bg-blue-500 h-1.5 rounded-full transition-all" style="width: <?php echo min(($todaySent / 500) * 100, 100); ?>%"></div>
                                </div>
                            </div>
                            <div class="bg-dark-900/50 rounded-lg p-3">
                                <div class="text-xs text-zinc-400 mb-1">Restantes Hoje</div>
                                <div class="text-lg font-bold text-green-400"><?php echo max(0, 500 - $todaySent); ?></div>
                            </div>
                            <div class="bg-dark-900/50 rounded-lg p-3">
                                <div class="text-xs text-zinc-400 mb-1">Taxa de Sucesso</div>
                                <div class="text-lg font-bold text-white"><?php echo $rate; ?>%</div>
                                <div class="text-xs text-zinc-500 mt-1">
                                    <?php echo $project['stats']['total_sent']; ?> enviados / <?php echo $project['stats']['total_errors']; ?> erros
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Token -->
                        <div class="bg-dark-900/50 rounded-lg p-4 mb-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="text-xs text-zinc-400 mb-1">Token de Autenticação</div>
                                    <code class="text-sm text-white font-mono break-all" data-token="<?php echo htmlspecialchars($project['token']); ?>"><?php echo htmlspecialchars($project['token']); ?></code>
                                </div>
                                <button onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($project['token']); ?>'); alert('Token copiado!')" 
                                        class="ml-4 px-3 py-1.5 bg-white/10 hover:bg-white/20 rounded-lg text-sm transition-colors">
                                    <i data-lucide="copy" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Ações e SDK -->
                        <div class="flex flex-wrap gap-3 items-center">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="test_send">
                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                <input type="email" name="test_email" placeholder="E-mail de teste" required
                                    class="bg-white/5 border border-white/10 rounded-lg px-3 py-1.5 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 mr-2">
                                <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-colors">
                                    Testar Envio
                                </button>
                            </form>
                            <button onclick="showSDKCode('<?php echo $project['id']; ?>')" 
                                    class="px-4 py-1.5 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                                <i data-lucide="code" class="w-4 h-4"></i>
                                Ver Código SDK
                            </button>
                            <button onclick="openDeleteModal('<?php echo $project['id']; ?>', '<?php echo htmlspecialchars($project['name'], ENT_QUOTES); ?>')" 
                                    class="px-4 py-1.5 bg-red-600/20 hover:bg-red-600/30 text-red-400 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                Excluir
                            </button>
                        </div>
                        
                        <!-- Código SDK (oculto por padrão) -->
                        <div id="sdk-code-<?php echo $project['id']; ?>" class="hidden mt-4 bg-dark-900/50 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-semibold text-white">Código de Integração</h4>
                                <button onclick="hideSDKCode('<?php echo $project['id']; ?>')" class="text-zinc-400 hover:text-white">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <div class="text-xs text-zinc-400 mb-1">PHP</div>
                                    <pre class="bg-black rounded-lg p-3 text-xs text-green-400 overflow-x-auto"><code><?php echo htmlspecialchars("<?php
require_once 'sdk/mail/SafeNodeMail.php';

\$mail = new SafeNodeMail(
    'https://safenode.cloud/api/mail',
    '" . htmlspecialchars($project['token']) . "'
);

\$result = \$mail->send(
    'usuario@email.com',
    'Assunto',
    '<h1>Conteúdo HTML</h1>'
);"); ?></code></pre>
                                </div>
                                <div>
                                    <div class="text-xs text-zinc-400 mb-1">JavaScript</div>
                                    <pre class="bg-black rounded-lg p-3 text-xs text-green-400 overflow-x-auto"><code><?php echo htmlspecialchars("const mail = new SafeNodeMail(
    'https://safenode.cloud/api/mail',
    '" . htmlspecialchars($project['token']) . "'
);

await mail.send(
    'usuario@email.com',
    'Assunto',
    '<h1>Conteúdo HTML</h1>'
);"); ?></code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($projects)): ?>
                    <div class="bg-dark-800 border border-white/10 rounded-xl p-12 text-center">
                        <i data-lucide="mail" class="w-16 h-16 text-zinc-600 mx-auto mb-4"></i>
                        <h3 class="text-lg font-semibold text-white mb-2">Nenhum projeto criado</h3>
                        <p class="text-zinc-400 mb-6">Crie seu primeiro projeto na aba "Criar Projeto"</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Templates Disponíveis -->
                    <?php if (!empty($templates)): ?>
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-3">
                            <i data-lucide="file-code" class="w-5 h-5"></i>
                            Templates Disponíveis
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($templates as $template): ?>
                            <div class="bg-dark-800 border border-white/10 rounded-xl p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h4 class="text-sm font-semibold text-white"><?php echo htmlspecialchars($template['name']); ?></h4>
                                        <p class="text-xs text-zinc-400"><?php echo htmlspecialchars($template['project_name']); ?></p>
                                    </div>
                                    <?php if ($template['is_default']): ?>
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-500/20 text-blue-400">Padrão</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-zinc-500 mb-3 line-clamp-2"><?php echo htmlspecialchars($template['subject']); ?></p>
                                <?php if ($template['variables']): 
                                    $vars = json_decode($template['variables'], true);
                                    if (is_array($vars) && !empty($vars)):
                                ?>
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($vars as $var): ?>
                                    <span class="px-2 py-0.5 bg-dark-900/50 rounded text-xs text-zinc-400 font-mono">{{<?php echo htmlspecialchars($var); ?>}}</span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Últimos Envios -->
                    <?php if (!empty($recentLogs)): ?>
                    <div class="mt-8">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-white flex items-center gap-3">
                                <i data-lucide="list" class="w-5 h-5"></i>
                                Últimos Envios
                            </h3>
                            <button onclick="document.getElementById('logs-table').classList.toggle('hidden')" 
                                    class="text-sm text-zinc-400 hover:text-white transition-colors">
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <div id="logs-table" class="bg-dark-800 border border-white/10 rounded-xl overflow-hidden">
                            <table class="w-full">
                                <thead class="bg-dark-900/50 border-b border-white/10">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-400">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-400">Projeto</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-400">Destinatário</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-400">Assunto</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-400">Template</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-400">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentLogs as $log): ?>
                                    <tr class="border-b border-white/5 hover:bg-dark-900/30 transition-colors">
                                        <td class="px-4 py-3 text-sm text-zinc-400">
                                            <?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-white">
                                            <?php echo htmlspecialchars($log['project_name']); ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-zinc-300">
                                            <?php echo htmlspecialchars($log['to_email']); ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-zinc-300">
                                            <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($log['subject']); ?>">
                                                <?php echo htmlspecialchars($log['subject']); ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-zinc-400">
                                            <?php if ($log['template_name']): ?>
                                            <span class="px-2 py-0.5 bg-dark-900/50 rounded text-xs font-mono">
                                                <?php echo htmlspecialchars($log['template_name']); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-zinc-600">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-1 rounded text-xs font-semibold <?php 
                                                    echo $log['status'] === 'sent' ? 'bg-green-500/20 text-green-400' : 
                                                        ($log['status'] === 'error' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400'); 
                                                ?>">
                                                    <?php 
                                                    echo $log['status'] === 'sent' ? 'Enviado' : 
                                                        ($log['status'] === 'error' ? 'Erro' : 'Pendente'); 
                                                    ?>
                                                </span>
                                                <?php if ($log['status'] === 'error' && $log['error_message']): ?>
                                                <button onclick="alert('<?php echo htmlspecialchars(addslashes($log['error_message'])); ?>')" 
                                                        class="text-red-400 hover:text-red-300" title="Ver erro">
                                                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                </div>

                <!-- Tab: Criar Projeto -->
                <div x-show="activeTab === 'create'" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                <div x-data="{ selectedTemplate: null }">
                
                <!-- Templates Prontos -->
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6 mb-6">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-3">
                        <i data-lucide="file-code" class="w-5 h-5"></i>
                        Templates Prontos
                    </h3>
                    <p class="text-sm text-zinc-400 mb-4">Escolha um template para começar rapidamente ou crie um do zero</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Template: Confirm Sign Up -->
                        <div @click="selectedTemplate = 'confirm_signup'; loadTemplate('confirm_signup')" 
                             :class="selectedTemplate === 'confirm_signup' ? 'border-blue-500 bg-blue-500/10' : 'border-white/10 hover:border-white/20'"
                             class="bg-dark-900 border rounded-xl p-4 cursor-pointer transition-all">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h4 class="text-sm font-semibold text-white mb-1">Confirm Sign Up</h4>
                                    <p class="text-xs text-zinc-400">Confirmar cadastro</p>
                                </div>
                                <i data-lucide="check-circle" class="w-5 h-5 text-blue-500" x-show="selectedTemplate === 'confirm_signup'"></i>
                            </div>
                            <p class="text-xs text-zinc-500 mt-2">Template para confirmação de e-mail após cadastro</p>
                        </div>
                        
                        <!-- Template: Invite User -->
                        <div @click="selectedTemplate = 'invite_user'; loadTemplate('invite_user')" 
                             :class="selectedTemplate === 'invite_user' ? 'border-blue-500 bg-blue-500/10' : 'border-white/10 hover:border-white/20'"
                             class="bg-dark-900 border rounded-xl p-4 cursor-pointer transition-all">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h4 class="text-sm font-semibold text-white mb-1">Invite User</h4>
                                    <p class="text-xs text-zinc-400">Convidar usuário</p>
                                </div>
                                <i data-lucide="check-circle" class="w-5 h-5 text-blue-500" x-show="selectedTemplate === 'invite_user'"></i>
                            </div>
                            <p class="text-xs text-zinc-500 mt-2">Template para convidar novos usuários</p>
                        </div>
                        
                        <!-- Template: Magic Link -->
                        <div @click="selectedTemplate = 'magic_link'; loadTemplate('magic_link')" 
                             :class="selectedTemplate === 'magic_link' ? 'border-blue-500 bg-blue-500/10' : 'border-white/10 hover:border-white/20'"
                             class="bg-dark-900 border rounded-xl p-4 cursor-pointer transition-all">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h4 class="text-sm font-semibold text-white mb-1">Magic Link</h4>
                                    <p class="text-xs text-zinc-400">Link mágico de login</p>
                                </div>
                                <i data-lucide="check-circle" class="w-5 h-5 text-blue-500" x-show="selectedTemplate === 'magic_link'"></i>
                            </div>
                            <p class="text-xs text-zinc-500 mt-2">Template para login sem senha</p>
                        </div>
                        
                        <!-- Template: Change Email -->
                        <div @click="selectedTemplate = 'change_email'; loadTemplate('change_email')" 
                             :class="selectedTemplate === 'change_email' ? 'border-blue-500 bg-blue-500/10' : 'border-white/10 hover:border-white/20'"
                             class="bg-dark-900 border rounded-xl p-4 cursor-pointer transition-all">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h4 class="text-sm font-semibold text-white mb-1">Change Email</h4>
                                    <p class="text-xs text-zinc-400">Alterar e-mail</p>
                                </div>
                                <i data-lucide="check-circle" class="w-5 h-5 text-blue-500" x-show="selectedTemplate === 'change_email'"></i>
                            </div>
                            <p class="text-xs text-zinc-500 mt-2">Template para confirmar nova alteração de e-mail</p>
                        </div>
                        
                        <!-- Template: Reset Password -->
                        <div @click="selectedTemplate = 'reset_password'; loadTemplate('reset_password')" 
                             :class="selectedTemplate === 'reset_password' ? 'border-blue-500 bg-blue-500/10' : 'border-white/10 hover:border-white/20'"
                             class="bg-dark-900 border rounded-xl p-4 cursor-pointer transition-all">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h4 class="text-sm font-semibold text-white mb-1">Reset Password</h4>
                                    <p class="text-xs text-zinc-400">Redefinir senha</p>
                                </div>
                                <i data-lucide="check-circle" class="w-5 h-5 text-blue-500" x-show="selectedTemplate === 'reset_password'"></i>
                            </div>
                            <p class="text-xs text-zinc-500 mt-2">Template para recuperação de senha</p>
                        </div>
                        
                        <!-- Template: Reauthentication -->
                        <div @click="selectedTemplate = 'reauthentication'; loadTemplate('reauthentication')" 
                             :class="selectedTemplate === 'reauthentication' ? 'border-blue-500 bg-blue-500/10' : 'border-white/10 hover:border-white/20'"
                             class="bg-dark-900 border rounded-xl p-4 cursor-pointer transition-all">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h4 class="text-sm font-semibold text-white mb-1">Reauthentication</h4>
                                    <p class="text-xs text-zinc-400">Reautenticação</p>
                                </div>
                                <i data-lucide="check-circle" class="w-5 h-5 text-blue-500" x-show="selectedTemplate === 'reauthentication'"></i>
                            </div>
                            <p class="text-xs text-zinc-500 mt-2">Template para reautenticação em ações sensíveis</p>
                        </div>
                    </div>
                </div>
                
                <!-- Criar Novo Projeto -->
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6 mb-6">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-3">
                        <i data-lucide="plus-circle" class="w-5 h-5"></i>
                        Criar Novo Projeto
                    </h3>
                    <form method="POST" class="space-y-6" id="create-project-form">
                        <input type="hidden" name="action" value="create_project">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">Nome do Projeto *</label>
                                <input type="text" name="name" required 
                                    placeholder="Meu Projeto" 
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 focus:ring-2 focus:ring-white/10 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">E-mail Remetente *</label>
                                <input type="email" name="sender_email" required 
                                    placeholder="noreply@exemplo.com" 
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 focus:ring-2 focus:ring-white/10 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">Nome do Remetente</label>
                                <input type="text" name="sender_name" 
                                    placeholder="Meu Sistema" 
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 focus:ring-2 focus:ring-white/10 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">Função do E-mail *</label>
                                <select name="email_function" required 
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-white/30 focus:ring-2 focus:ring-white/10 transition-all">
                                    <option value="">Selecione uma função</option>
                                    <option value="confirm_signup">Confirm sign up - Confirmar cadastro</option>
                                    <option value="invite_user">Invite user - Convidar usuário</option>
                                    <option value="magic_link">Magic link - Link mágico de login</option>
                                    <option value="change_email">Change email address - Alterar e-mail</option>
                                    <option value="reset_password">Reset password - Redefinir senha</option>
                                    <option value="reauthentication">Reauthentication - Reautenticação</option>
                                </select>
                                <p class="text-xs text-zinc-400 mt-2">Escolha a função que este e-mail vai cumprir</p>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">Template HTML do E-mail *</label>
                            <div class="bg-dark-900 rounded-xl border border-white/10 overflow-hidden mb-2">
                                <div class="bg-dark-950 px-4 py-2 border-b border-white/10 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs text-zinc-400 font-mono">email-template.html</span>
                                        <div class="flex items-center gap-2">
                                            <button type="button" 
                                                    onclick="switchEditorMode('visual')" 
                                                    id="btn-visual-mode"
                                                    class="text-xs px-3 py-1 rounded transition-colors bg-blue-600 text-white">
                                                <i data-lucide="layout" class="w-3 h-3 inline mr-1"></i> Visual
                                            </button>
                                            <button type="button" 
                                                    onclick="switchEditorMode('code')" 
                                                    id="btn-code-mode"
                                                    class="text-xs px-3 py-1 rounded transition-colors bg-white/5 text-zinc-400 hover:text-white">
                                                <i data-lucide="code" class="w-3 h-3 inline mr-1"></i> Código
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" onclick="previewEmail()" class="text-xs text-zinc-400 hover:text-white px-3 py-1 rounded bg-white/5 hover:bg-white/10 transition-colors">
                                        <i data-lucide="eye" class="w-3 h-3 inline mr-1"></i> Preview
                                    </button>
                                </div>
                                
                                <!-- Editor Visual (GrapesJS) -->
                                <div id="visual-editor-container" class="hidden">
                                    <div style="display: flex; height: 600px; background: #0a0a0a; border-radius: 8px; overflow: hidden;">
                                        <!-- Painel de Blocos (Esquerda) -->
                                        <div class="blocks-container" style="width: 200px; overflow-y: auto; border-right: 1px solid #1f1f1f; background: #0a0a0a;"></div>
                                        
                                        <!-- Editor Central -->
                                        <div style="flex: 1; display: flex; flex-direction: column; background: #0a0a0a;">
                                            <!-- Barra de Ferramentas -->
                                            <div style="display: flex; align-items: center; gap: 8px; padding: 8px; background: #1a1a1a; border-bottom: 1px solid #1f1f1f;">
                                                <div class="panel__devices" style="display: flex; gap: 4px;"></div>
                                                <div class="panel__switcher" style="display: flex; gap: 4px; margin-left: auto;"></div>
                                            </div>
                                            
                                            <!-- Canvas -->
                                            <div id="gjs-editor" style="flex: 1; overflow: auto; background: #1a1a1a;"></div>
                                        </div>
                                        
                                        <!-- Painéis Laterais (Direita) -->
                                        <div style="width: 300px; display: flex; flex-direction: column; border-left: 1px solid #1f1f1f; background: #0a0a0a;">
                                            <div class="styles-container" style="flex: 1; overflow-y: auto; min-height: 200px;"></div>
                                            <div class="layers-container" style="flex: 1; overflow-y: auto; border-top: 1px solid #1f1f1f; min-height: 150px;"></div>
                                            <div class="traits-container" style="overflow-y: auto; border-top: 1px solid #1f1f1f; min-height: 100px;"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Editor de Código (CodeMirror) -->
                                <div id="code-editor-container">
                                    <textarea name="html_template" id="html-editor" required 
                                        class="w-full bg-dark-900 text-white font-mono text-sm p-4 min-h-[400px] focus:outline-none"
                                        placeholder="<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; }
    </style>
</head>
<body>
    <h1>Olá {{nome}}!</h1>
    <p>Seu código de verificação é: {{codigo}}</p>
    <a href='{{link}}'>Clique aqui para confirmar</a>
</body>
</html>"></textarea>
                                </div>
                            </div>
                            <p class="text-xs text-zinc-400 mt-2">Use variáveis como {{nome}}, {{codigo}}, {{link}} que serão substituídas automaticamente</p>
                        </div>
                        
                        <button type="submit" class="px-6 py-2.5 bg-white text-black rounded-xl font-semibold hover:bg-zinc-200 transition-colors">
                            Criar Projeto
                        </button>
                    </form>
                </div>
                </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de Exclusão -->
    <div id="delete-modal" 
         x-data="{ open: false, projectId: null, projectName: '', confirmName: '' }"
         x-show="open"
         x-cloak
         @click.away="open = false; confirmName = ''"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
         style="display: none;">
        <div @click.stop class="bg-dark-800 border border-white/10 rounded-xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500"></i>
                    Excluir Projeto
                </h3>
                <button @click="open = false; confirmName = ''" class="text-zinc-400 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <div class="mb-4">
                <p class="text-sm text-zinc-300 mb-4">
                    Esta ação não pode ser desfeita. Todos os dados do projeto serão permanentemente excluídos, incluindo:
                </p>
                <ul class="text-xs text-zinc-400 space-y-1 mb-4 ml-4 list-disc">
                    <li>Configurações do projeto</li>
                    <li>Histórico de envios</li>
                    <li>Templates criados</li>
                    <li>Token de autenticação</li>
                </ul>
                <p class="text-sm text-white font-semibold mb-2">
                    Para confirmar, digite o nome do projeto: <span class="text-red-400" x-text="projectName"></span>
                </p>
                <input type="text" 
                       x-model="confirmName"
                       placeholder="Digite o nome do projeto"
                       class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-red-500/50 focus:ring-2 focus:ring-red-500/20 transition-all">
            </div>
            
            <div class="flex gap-3 justify-end">
                <button @click="open = false; confirmName = ''" 
                        class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold text-white transition-colors">
                    Cancelar
                </button>
                <form method="POST" @submit.prevent="if(confirmName === projectName) { $el.submit(); } else { alert('O nome digitado não corresponde ao nome do projeto'); }">
                    <input type="hidden" name="action" value="delete_project">
                    <input type="hidden" name="project_id" :value="projectId">
                    <input type="hidden" name="project_name" :value="projectName">
                    <input type="hidden" name="confirm_name" :value="confirmName">
                    <button type="submit" 
                            :disabled="confirmName !== projectName"
                            :class="confirmName === projectName ? 'bg-red-600 hover:bg-red-700' : 'bg-red-600/30 cursor-not-allowed'"
                            class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition-colors">
                        Excluir Projeto
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- GrapesJS CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/grapesjs@0.21.5/dist/css/grapes.min.css">
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CodeMirror CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    
    <!-- GrapesJS JS -->
    <script src="https://cdn.jsdelivr.net/npm/grapesjs@0.21.5/dist/grapes.min.js"></script>
    
    <!-- CodeMirror JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    
    <script>
    // Inicializar ícones
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Variáveis globais
    let codeEditor;
    let codeEditorInitialized = false;
    let visualEditor;
    let editorMode = 'code'; // 'code' ou 'visual'
    
    function initCodeEditor() {
        if (codeEditorInitialized) return;
        
        const textarea = document.getElementById('html-editor');
        if (!textarea) return;
        
        // Se CodeMirror não estiver carregado, aguardar
        if (typeof CodeMirror === 'undefined') {
            setTimeout(initCodeEditor, 100);
            return;
        }
        
        try {
            codeEditor = CodeMirror.fromTextArea(textarea, {
                mode: 'htmlmixed',
                theme: 'monokai',
                lineNumbers: true,
                lineWrapping: true,
                indentUnit: 2,
                tabSize: 2,
                autoCloseTags: true,
                matchBrackets: true,
                autoCloseBrackets: true,
                foldGutter: true,
                gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter']
            });
            
            codeEditor.setSize('100%', '400px');
            codeEditorInitialized = true;
            
            // Atualizar ícones após inicializar
            if (typeof lucide !== 'undefined') {
                setTimeout(() => lucide.createIcons(), 100);
            }
        } catch (error) {
            console.error('Erro ao inicializar CodeMirror:', error);
        }
    }
    
    // Tornar initCodeEditor global
    window.initCodeEditor = initCodeEditor;
    
    document.addEventListener('DOMContentLoaded', function() {
        // Tentar inicializar CodeMirror após um delay para garantir que esteja carregado
        setTimeout(initCodeEditor, 300);
        
        // Tentar inicializar GrapesJS quando necessário
        setTimeout(() => {
            const visualContainer = document.getElementById('visual-editor-container');
            if (visualContainer && !visualContainer.classList.contains('hidden')) {
                initVisualEditor();
            }
        }, 500);
        
        // Observar mudanças na tab para inicializar quando necessário
        if (window.Alpine) {
            // Aguardar Alpine estar pronto
            if (document.readyState === 'loading') {
                document.addEventListener('alpine:init', () => {
                    setTimeout(() => {
                        const contentDiv = document.querySelector('[x-data*="activeTab"]');
                        if (contentDiv) {
                            const data = Alpine.$data(contentDiv);
                            if (data && data.activeTab === 'create' && !codeEditorInitialized) {
                                setTimeout(initCodeEditor, 300);
                            }
                        }
                    }, 200);
                });
            } else {
                // Alpine já está pronto
                setTimeout(() => {
                    const contentDiv = document.querySelector('[x-data*="activeTab"]');
                    if (contentDiv) {
                        const data = Alpine.$data(contentDiv);
                        if (data && data.activeTab === 'create' && !codeEditorInitialized) {
                            setTimeout(initCodeEditor, 300);
                        }
                    }
                }, 200);
            }
        }
        
        // Observar mudanças na tab manualmente (fallback)
        const checkTabInterval = setInterval(() => {
            const contentDiv = document.querySelector('[x-data*="activeTab"]');
            if (contentDiv && window.Alpine) {
                const data = Alpine.$data(contentDiv);
                if (data && data.activeTab === 'create' && !codeEditorInitialized) {
                    setTimeout(initCodeEditor, 300);
                }
            }
            // Parar de verificar após inicializar
            if (codeEditorInitialized) {
                clearInterval(checkTabInterval);
            }
        }, 500);
        
        // Limpar intervalo após 10 segundos
        setTimeout(() => clearInterval(checkTabInterval), 10000);
    });
    
    // Preview do email
    function previewEmail() {
        let html = '';
        
        // Se estiver no modo visual, obter do GrapesJS
        if (editorMode === 'visual' && visualEditor) {
            html = visualEditor.getHtml();
            const css = visualEditor.getCss();
            html = html + '<style>' + css + '</style>';
        } else if (codeEditor && typeof codeEditor.getValue === 'function') {
            // Tentar obter do CodeMirror
            html = codeEditor.getValue();
        } else {
            // Se CodeMirror não estiver disponível, usar o textarea diretamente
            const textarea = document.getElementById('html-editor');
            if (textarea) {
                html = textarea.value;
            }
        }
        
        if (!html || html.trim() === '') {
            alert('Adicione conteúdo HTML primeiro');
            return;
        }
        
        // Criar janela de preview
        const previewWindow = window.open('', 'email-preview', 'width=800,height=600,scrollbars=yes,resizable=yes');
        if (!previewWindow) {
            alert('Por favor, permita pop-ups para visualizar o preview');
            return;
        }
        
        previewWindow.document.open();
        previewWindow.document.write(html);
        previewWindow.document.close();
        
        // Focar na janela de preview
        previewWindow.focus();
    }
    
    // Tornar previewEmail global para acesso via onclick
    window.previewEmail = previewEmail;
    
    // Inicializar Editor Visual (GrapesJS)
    function initVisualEditor() {
        if (visualEditor) return;
        
        const container = document.getElementById('gjs-editor');
        if (!container || typeof grapesjs === 'undefined') {
            setTimeout(initVisualEditor, 200);
            return;
        }
        
        try {
            visualEditor = grapesjs.init({
                container: container,
                height: '600px',
                width: '100%',
                storageManager: false,
                fromElement: false,
                noticeOnUnload: false,
                canvas: {
                    styles: []
                },
                panels: {
                    defaults: [
                        {
                            id: 'layers',
                            el: '.panel__right',
                            resizable: {
                                maxDim: 350,
                                minDim: 200,
                                tc: 0,
                                cl: 1,
                                cr: 0,
                                bc: 0,
                                keyWidth: 'flex-basis',
                            },
                        },
                        {
                            id: 'panel-devices',
                            el: '.panel__devices',
                            buttons: [
                                {
                                    id: 'device-desktop',
                                    label: '<i class="fa fa-desktop"></i>',
                                    command: 'set-device-desktop',
                                    active: true,
                                    togglable: false,
                                },
                                {
                                    id: 'device-tablet',
                                    label: '<i class="fa fa-tablet"></i>',
                                    command: 'set-device-tablet',
                                    togglable: false,
                                },
                                {
                                    id: 'device-mobile',
                                    label: '<i class="fa fa-mobile"></i>',
                                    command: 'set-device-mobile',
                                    togglable: false,
                                }
                            ],
                        },
                        {
                            id: 'panel-switcher',
                            el: '.panel__switcher',
                            buttons: [
                                {
                                    id: 'show-layers',
                                    active: true,
                                    label: '<i class="fa fa-bars"></i>',
                                    command: 'show-layers',
                                    togglable: false,
                                },
                                {
                                    id: 'show-style',
                                    active: true,
                                    label: '<i class="fa fa-paint-brush"></i>',
                                    command: 'show-styles',
                                    togglable: false,
                                },
                                {
                                    id: 'show-traits',
                                    active: true,
                                    label: '<i class="fa fa-cog"></i>',
                                    command: 'show-traits',
                                    togglable: false,
                                }
                            ],
                        }
                    ]
                },
                deviceManager: {
                    devices: [
                        {
                            name: 'Desktop',
                            width: ''
                        },
                        {
                            name: 'Tablet',
                            width: '768px',
                            widthMedia: '992px'
                        },
                        {
                            name: 'Mobile',
                            width: '320px',
                            widthMedia: '768px'
                        }
                    ]
                },
                blockManager: {
                    appendTo: '.blocks-container',
                    blocks: [
                        {
                            id: 'section',
                            label: '<div style="padding: 8px; text-align: center;"><i class="fa fa-square" style="font-size: 24px; color: #3b82f6;"></i><div style="margin-top: 4px; font-size: 11px; color: #fff;">Seção</div></div>',
                            attributes: { class: 'gjs-block-section' },
                            content: `<section style="padding: 30px; background-color: #ffffff; max-width: 600px; margin: 0 auto;">
                                <div>Conteúdo aqui</div>
                            </section>`
                        },
                        {
                            id: 'text',
                            label: '<div style="padding: 8px; text-align: center;"><i class="fa fa-font" style="font-size: 24px; color: #3b82f6;"></i><div style="margin-top: 4px; font-size: 11px; color: #fff;">Texto</div></div>',
                            content: '<div style="padding: 10px; color: #333;">Insira seu texto aqui</div>'
                        },
                        {
                            id: 'heading',
                            label: '<div style="padding: 8px; text-align: center;"><i class="fa fa-heading" style="font-size: 24px; color: #3b82f6;"></i><div style="margin-top: 4px; font-size: 11px; color: #fff;">Título</div></div>',
                            content: '<h1 style="margin: 0; padding: 15px; color: #1f2937; font-size: 24px; font-weight: bold;">Título</h1>'
                        },
                        {
                            id: 'button',
                            label: '<div style="padding: 8px; text-align: center;"><i class="fa fa-hand-pointer" style="font-size: 24px; color: #3b82f6;"></i><div style="margin-top: 4px; font-size: 11px; color: #fff;">Botão</div></div>',
                            content: '<a href="#" style="display: inline-block; padding: 12px 30px; background-color: #2563eb; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">Clique aqui</a>'
                        },
                        {
                            id: 'image',
                            label: '<div style="padding: 8px; text-align: center;"><i class="fa fa-image" style="font-size: 24px; color: #3b82f6;"></i><div style="margin-top: 4px; font-size: 11px; color: #fff;">Imagem</div></div>',
                            content: { type: 'image', src: 'https://via.placeholder.com/600x300', style: 'max-width: 100%; height: auto; display: block;' }
                        },
                        {
                            id: 'divider',
                            label: '<div style="padding: 8px; text-align: center;"><i class="fa fa-minus" style="font-size: 24px; color: #3b82f6;"></i><div style="margin-top: 4px; font-size: 11px; color: #fff;">Divisor</div></div>',
                            content: '<hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">'
                        },
                        {
                            id: 'variable',
                            label: '<div style="padding: 8px; text-align: center;"><i class="fa fa-code" style="font-size: 24px; color: #3b82f6;"></i><div style="margin-top: 4px; font-size: 11px; color: #fff;">Variável</div></div>',
                            content: '<span style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-family: monospace; color: #1f2937;">{{nome}}</span>'
                        }
                    ]
                },
                styleManager: {
                    appendTo: '.styles-container',
                    sectors: [
                        {
                            name: 'Dimensões',
                            open: false,
                            buildProps: ['width', 'min-height', 'padding'],
                        },
                        {
                            name: 'Tipografia',
                            open: false,
                            buildProps: ['font-family', 'font-size', 'font-weight', 'letter-spacing', 'color', 'line-height', 'text-align', 'text-decoration'],
                        },
                        {
                            name: 'Decoração',
                            open: false,
                            buildProps: ['opacity', 'border-radius', 'border', 'box-shadow', 'background'],
                        }
                    ]
                },
                traitManager: {
                    appendTo: '.traits-container',
                },
                layerManager: {
                    appendTo: '.layers-container',
                }
            });
            
            // Adicionar estilos customizados para o editor dark
            const style = document.createElement('style');
            style.textContent = `
                .gjs-editor {
                    background: #0a0a0a !important;
                    border: 1px solid #1f1f1f !important;
                    border-radius: 8px !important;
                }
                .gjs-cv-canvas {
                    background: #1a1a1a !important;
                }
                .gjs-frame {
                    background: #ffffff !important;
                    border-radius: 4px !important;
                }
                .gjs-block {
                    background: #1a1a1a !important;
                    border: 1px solid #333 !important;
                    color: #fff !important;
                    border-radius: 6px !important;
                    margin: 4px !important;
                    padding: 8px !important;
                    transition: all 0.2s !important;
                }
                .gjs-block:hover {
                    background: #2a2a2a !important;
                    border-color: #3b82f6 !important;
                    transform: translateY(-2px) !important;
                }
                .gjs-pn-panels {
                    background: #0a0a0a !important;
                    border-color: #1f1f1f !important;
                }
                .gjs-pn-btn {
                    color: #fff !important;
                    border-radius: 4px !important;
                }
                .gjs-pn-btn:hover {
                    background: #1a1a1a !important;
                }
                .gjs-pn-active {
                    background: #3b82f6 !important;
                    color: #fff !important;
                }
                .gjs-sm-sector {
                    background: #1a1a1a !important;
                    border-color: #333 !important;
                    border-radius: 6px !important;
                    margin-bottom: 8px !important;
                }
                .gjs-sm-label {
                    color: #fff !important;
                    font-weight: 600 !important;
                }
                .gjs-field {
                    background: #0a0a0a !important;
                    border-color: #333 !important;
                    color: #fff !important;
                    border-radius: 4px !important;
                }
                .gjs-field:focus {
                    border-color: #3b82f6 !important;
                    outline: none !important;
                }
                .gjs-layer {
                    color: #fff !important;
                }
                .gjs-layer-title {
                    color: #fff !important;
                }
                .gjs-selected {
                    outline: 2px solid #3b82f6 !important;
                }
                .panel__devices {
                    background: #1a1a1a !important;
                    border-radius: 6px !important;
                    padding: 8px !important;
                    margin-bottom: 10px !important;
                }
                .panel__switcher {
                    background: #1a1a1a !important;
                    border-radius: 6px !important;
                    padding: 8px !important;
                    margin-bottom: 10px !important;
                }
                .blocks-container {
                    background: #0a0a0a !important;
                    border-right: 1px solid #1f1f1f !important;
                    padding: 10px !important;
                }
                .styles-container {
                    background: #0a0a0a !important;
                    border-left: 1px solid #1f1f1f !important;
                    padding: 10px !important;
                }
                .layers-container {
                    background: #0a0a0a !important;
                    border-left: 1px solid #1f1f1f !important;
                    padding: 10px !important;
                }
                .traits-container {
                    background: #0a0a0a !important;
                    border-left: 1px solid #1f1f1f !important;
                    padding: 10px !important;
                }
            `;
            document.head.appendChild(style);
            
            // Carregar template inicial se houver
            const textarea = document.getElementById('html-editor');
            if (textarea && textarea.value) {
                visualEditor.setComponents(textarea.value);
            }
            
        } catch (error) {
            console.error('Erro ao inicializar GrapesJS:', error);
        }
    }
    
    // Alternar entre modo visual e código
    function switchEditorMode(mode) {
        editorMode = mode;
        const visualContainer = document.getElementById('visual-editor-container');
        const codeContainer = document.getElementById('code-editor-container');
        const btnVisual = document.getElementById('btn-visual-mode');
        const btnCode = document.getElementById('btn-code-mode');
        
        if (mode === 'visual') {
            visualContainer.classList.remove('hidden');
            codeContainer.classList.add('hidden');
            btnVisual.classList.add('bg-blue-600', 'text-white');
            btnVisual.classList.remove('bg-white/5', 'text-zinc-400');
            btnCode.classList.remove('bg-blue-600', 'text-white');
            btnCode.classList.add('bg-white/5', 'text-zinc-400');
            
            // Inicializar editor visual se ainda não foi
            if (!visualEditor) {
                setTimeout(initVisualEditor, 100);
            }
            
            // Sincronizar código para visual
            if (codeEditor) {
                const html = codeEditor.getValue();
                if (html && visualEditor) {
                    visualEditor.setComponents(html);
                }
            } else {
                const textarea = document.getElementById('html-editor');
                if (textarea && textarea.value && visualEditor) {
                    visualEditor.setComponents(textarea.value);
                }
            }
        } else {
            visualContainer.classList.add('hidden');
            codeContainer.classList.remove('hidden');
            btnCode.classList.add('bg-blue-600', 'text-white');
            btnCode.classList.remove('bg-white/5', 'text-zinc-400');
            btnVisual.classList.remove('bg-blue-600', 'text-white');
            btnVisual.classList.add('bg-white/5', 'text-zinc-400');
            
            // Sincronizar visual para código
            if (visualEditor) {
                const html = visualEditor.getHtml();
                const css = visualEditor.getCss();
                const fullHtml = html + '<style>' + css + '</style>';
                
                if (codeEditor) {
                    codeEditor.setValue(fullHtml);
                } else {
                    const textarea = document.getElementById('html-editor');
                    if (textarea) {
                        textarea.value = fullHtml;
                    }
                }
            }
        }
    }
    
    // Tornar switchEditorMode global
    window.switchEditorMode = switchEditorMode;
    
    // Templates prontos de emails
    const emailTemplates = {
        confirm_signup: {
            name: 'Confirm Sign Up',
            html: `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        h1 {
            color: #1f2937;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin: 30px 0;
        }
        p {
            color: #4b5563;
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{nome_sistema}}</div>
            <h1>Confirme seu cadastro</h1>
        </div>
        <div class="content">
            <p>Olá <strong>{{nome}}</strong>,</p>
            <p>Obrigado por se cadastrar! Para completar seu cadastro, confirme seu endereço de e-mail clicando no botão abaixo:</p>
            <div style="text-align: center;">
                <a href="{{link}}" class="button">Confirmar E-mail</a>
            </div>
            <p>Ou copie e cole este link no seu navegador:</p>
            <p style="word-break: break-all; color: #2563eb;">{{link}}</p>
            <p>Este link expira em 24 horas.</p>
            <p>Se você não se cadastrou, pode ignorar este e-mail.</p>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático, por favor não responda.</p>
            <p>&copy; {{ano}} {{nome_sistema}}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>`
        },
        invite_user: {
            name: 'Invite User',
            html: `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        h1 {
            color: #1f2937;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin: 30px 0;
        }
        p {
            color: #4b5563;
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{nome_sistema}}</div>
            <h1>Você foi convidado!</h1>
        </div>
        <div class="content">
            <p>Olá,</p>
            <p><strong>{{nome_remetente}}</strong> convidou você para fazer parte do <strong>{{nome_sistema}}</strong>.</p>
            <p>Para aceitar o convite e criar sua conta, clique no botão abaixo:</p>
            <div style="text-align: center;">
                <a href="{{link}}" class="button">Aceitar Convite</a>
            </div>
            <p>Ou copie e cole este link no seu navegador:</p>
            <p style="word-break: break-all; color: #2563eb;">{{link}}</p>
            <p>Este convite expira em 7 dias.</p>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático, por favor não responda.</p>
            <p>&copy; {{ano}} {{nome_sistema}}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>`
        },
        magic_link: {
            name: 'Magic Link',
            html: `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        h1 {
            color: #1f2937;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin: 30px 0;
        }
        p {
            color: #4b5563;
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{nome_sistema}}</div>
            <h1>Link de acesso</h1>
        </div>
        <div class="content">
            <p>Olá <strong>{{nome}}</strong>,</p>
            <p>Você solicitou um link de acesso para entrar na sua conta. Clique no botão abaixo para fazer login:</p>
            <div style="text-align: center;">
                <a href="{{link}}" class="button">Fazer Login</a>
            </div>
            <p>Ou copie e cole este link no seu navegador:</p>
            <p style="word-break: break-all; color: #2563eb;">{{link}}</p>
            <p><strong>Este link expira em 15 minutos e só pode ser usado uma vez.</strong></p>
            <p>Se você não solicitou este link, ignore este e-mail. Sua conta permanece segura.</p>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático, por favor não responda.</p>
            <p>&copy; {{ano}} {{nome_sistema}}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>`
        },
        change_email: {
            name: 'Change Email',
            html: `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        h1 {
            color: #1f2937;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin: 30px 0;
        }
        p {
            color: #4b5563;
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{nome_sistema}}</div>
            <h1>Confirmar alteração de e-mail</h1>
        </div>
        <div class="content">
            <p>Olá <strong>{{nome}}</strong>,</p>
            <p>Você solicitou alterar seu endereço de e-mail de <strong>{{email_antigo}}</strong> para <strong>{{email_novo}}</strong>.</p>
            <p>Para confirmar esta alteração, clique no botão abaixo:</p>
            <div style="text-align: center;">
                <a href="{{link}}" class="button">Confirmar Alteração</a>
            </div>
            <p>Ou copie e cole este link no seu navegador:</p>
            <p style="word-break: break-all; color: #2563eb;">{{link}}</p>
            <p>Este link expira em 24 horas.</p>
            <p>Se você não solicitou esta alteração, ignore este e-mail e entre em contato conosco imediatamente.</p>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático, por favor não responda.</p>
            <p>&copy; {{ano}} {{nome_sistema}}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>`
        },
        reset_password: {
            name: 'Reset Password',
            html: `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        h1 {
            color: #1f2937;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin: 30px 0;
        }
        p {
            color: #4b5563;
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{nome_sistema}}</div>
            <h1>Redefinir senha</h1>
        </div>
        <div class="content">
            <p>Olá <strong>{{nome}}</strong>,</p>
            <p>Você solicitou a redefinição da sua senha. Clique no botão abaixo para criar uma nova senha:</p>
            <div style="text-align: center;">
                <a href="{{link}}" class="button">Redefinir Senha</a>
            </div>
            <p>Ou copie e cole este link no seu navegador:</p>
            <p style="word-break: break-all; color: #2563eb;">{{link}}</p>
            <p><strong>Este link expira em 1 hora e só pode ser usado uma vez.</strong></p>
            <p>Se você não solicitou a redefinição de senha, ignore este e-mail. Sua senha permanecerá a mesma.</p>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático, por favor não responda.</p>
            <p>&copy; {{ano}} {{nome_sistema}}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>`
        },
        reauthentication: {
            name: 'Reauthentication',
            html: `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        h1 {
            color: #1f2937;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin: 30px 0;
        }
        p {
            color: #4b5563;
            margin: 15px 0;
        }
        .code {
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 15px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            letter-spacing: 2px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{nome_sistema}}</div>
            <h1>Reautenticação necessária</h1>
        </div>
        <div class="content">
            <p>Olá <strong>{{nome}}</strong>,</p>
            <p>Você está tentando realizar uma ação sensível que requer reautenticação.</p>
            <p>Use o código abaixo para confirmar sua identidade:</p>
            <div class="code">{{codigo}}</div>
            <p>Este código expira em <strong>10 minutos</strong>.</p>
            <p>Se você não solicitou esta ação, ignore este e-mail e entre em contato conosco imediatamente.</p>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático, por favor não responda.</p>
            <p>&copy; {{ano}} {{nome_sistema}}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>`
        }
    };
    
    // Função para carregar template no editor
    function loadTemplate(templateKey) {
        const template = emailTemplates[templateKey];
        if (!template) return;
        
        // Atualizar o select de função
        const functionSelect = document.querySelector('select[name="email_function"]');
        if (functionSelect) {
            functionSelect.value = templateKey;
        }
        
        // Carregar HTML no editor baseado no modo atual
        if (editorMode === 'visual' && visualEditor) {
            visualEditor.setComponents(template.html);
        } else if (codeEditor && typeof codeEditor.setValue === 'function') {
            codeEditor.setValue(template.html);
        } else {
            const textarea = document.getElementById('html-editor');
            if (textarea) {
                textarea.value = template.html;
            }
            // Tentar inicializar CodeMirror se ainda não foi inicializado
            setTimeout(initCodeEditor, 200);
        }
        
        // Atualizar ícones
        if (typeof lucide !== 'undefined') {
            setTimeout(() => lucide.createIcons(), 100);
        }
    }
    
    // Tornar loadTemplate global
    window.loadTemplate = loadTemplate;
    
    // Atualizar textarea antes de submeter
    const form = document.getElementById('create-project-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Se estiver no modo visual, sincronizar para código primeiro
            if (editorMode === 'visual' && visualEditor) {
                const html = visualEditor.getHtml();
                const css = visualEditor.getCss();
                const fullHtml = html + '<style>' + css + '</style>';
                
                if (codeEditor) {
                    codeEditor.setValue(fullHtml);
                    codeEditor.save();
                } else {
                    const textarea = document.getElementById('html-editor');
                    if (textarea) {
                        textarea.value = fullHtml;
                    }
                }
            } else if (codeEditor) {
                codeEditor.save();
            }
        });
    }
    
    // Função para abrir modal de exclusão
    function openDeleteModal(projectId, projectName) {
        if (window.Alpine) {
            const modal = document.getElementById('delete-modal');
            if (modal) {
                const modalData = Alpine.$data(modal);
                modalData.projectId = projectId;
                modalData.projectName = projectName;
                modalData.confirmName = '';
                modalData.open = true;
                // Forçar exibição
                modal.style.display = 'flex';
                // Atualizar ícones
                setTimeout(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }, 100);
            }
        }
    }
    
    // Funções para mostrar/ocultar código SDK
    function showSDKCode(projectId) {
        const codeDiv = document.getElementById('sdk-code-' + projectId);
        if (codeDiv) {
            codeDiv.classList.remove('hidden');
            setTimeout(() => {
                codeDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
        }
    }
    
    function hideSDKCode(projectId) {
        const codeDiv = document.getElementById('sdk-code-' + projectId);
        if (codeDiv) {
            codeDiv.classList.add('hidden');
        }
    }
    
    // JavaScript da Sidebar (mesmo código de sites.php)
    (function() {
        const sidebar = document.getElementById('safenode-sidebar');
        const backdrop = document.getElementById('safenode-sidebar-backdrop');
        
        function openSidebar() {
            if (sidebar) sidebar.classList.remove('-translate-x-full');
            if (backdrop) backdrop.classList.remove('hidden');
            const bodyData = window.Alpine?.$data(document.body);
            if (bodyData?.sidebarOpen !== undefined) bodyData.sidebarOpen = true;
        }
        
        function closeSidebar() {
            if (sidebar) sidebar.classList.add('-translate-x-full');
            if (backdrop) backdrop.classList.add('hidden');
            const bodyData = window.Alpine?.$data(document.body);
            if (bodyData?.sidebarOpen !== undefined) bodyData.sidebarOpen = false;
        }
        
        function toggleSidebar() {
            const bodyData = window.Alpine?.$data(document.body);
            const isOpen = bodyData?.sidebarOpen ?? !sidebar?.classList.contains('-translate-x-full');
            if (isOpen) closeSidebar(); else openSidebar();
        }
        
        document.addEventListener('click', function(e) {
            if (e.target.closest('[data-sidebar-toggle]')) {
                e.preventDefault();
                setTimeout(toggleSidebar, 10);
            }
        });
        
        if (backdrop) backdrop.addEventListener('click', closeSidebar);
        
        // Swipe gesture para mobile
        let touchStartX = 0, touchStartY = 0, touchEndX = 0, touchEndY = 0, isSwiping = false;
        let sidebarElement = null, isDraggingSidebar = false, wasSidebarPartiallyVisible = false;
        
        document.body.addEventListener('touchstart', function(e) {
            if (window.innerWidth >= 1024) return;
            const touchX = e.touches[0].clientX;
            const touchY = e.touches[0].clientY;
            sidebarElement = document.querySelector('aside[x-show*="sidebarOpen"]');
            if (!sidebarElement) return;
            const sidebarRect = sidebarElement.getBoundingClientRect();
            wasSidebarPartiallyVisible = sidebarRect.left > -288 && sidebarRect.left < 0;
            const isTouchingSidebar = touchX >= sidebarRect.left && touchX <= sidebarRect.right &&
                                     touchY >= sidebarRect.top && touchY <= sidebarRect.bottom;
            if (isTouchingSidebar || touchX <= 20) {
                touchStartX = touchX;
                touchStartY = touchY;
                isSwiping = true;
                isDraggingSidebar = isTouchingSidebar;
            }
        }, { passive: true });
        
        document.body.addEventListener('touchmove', function(e) {
            if (!isSwiping || !sidebarElement) return;
            touchEndX = e.touches[0].clientX;
            touchEndY = e.touches[0].clientY;
            const deltaX = touchEndX - touchStartX;
            const deltaY = Math.abs(touchEndY - touchStartY);
            if (deltaY > 50) { isSwiping = false; return; }
            if (deltaX > 0) {
                const sidebarWidth = 288;
                let currentProgress = isDraggingSidebar ? 
                    Math.max(0, (sidebarElement.getBoundingClientRect().left + sidebarWidth) / sidebarWidth) + (deltaX / sidebarWidth) :
                    Math.min(deltaX / sidebarWidth, 1);
                sidebarElement.style.transform = `translateX(${-100 + (currentProgress * 100)}%)`;
                sidebarElement.style.transition = 'none';
                sidebarElement.style.display = 'flex';
                sidebarElement.removeAttribute('x-cloak');
                if (backdrop) {
                    backdrop.style.opacity = (currentProgress * 0.8).toString();
                    backdrop.classList.remove('hidden');
                }
            }
        }, { passive: true });
        
        document.body.addEventListener('touchend', function(e) {
            if (!isSwiping) return;
            const deltaX = touchEndX - touchStartX;
            const deltaY = Math.abs(touchEndY - touchStartY);
            if (deltaX > 0) {
                if (wasSidebarPartiallyVisible || isDraggingSidebar) {
                    openSidebar();
                } else if (deltaX > 30 && deltaY < 50) {
                    openSidebar();
                } else {
                    if (sidebarElement) {
                        sidebarElement.style.transition = '';
                        sidebarElement.style.transform = 'translateX(-100%) !important';
                    }
                    if (backdrop) {
                        backdrop.style.opacity = '';
                        backdrop.classList.add('hidden');
                    }
                }
            } else {
                if (sidebarElement) {
                    sidebarElement.style.transition = '';
                    sidebarElement.style.transform = 'translateX(-100%) !important';
                }
                if (backdrop) {
                    backdrop.style.opacity = '';
                    backdrop.classList.add('hidden');
                }
            }
            isSwiping = false;
            isDraggingSidebar = false;
            wasSidebarPartiallyVisible = false;
        }, { passive: true });
    })();
    </script>

    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false; $dispatch('safenode-sidebar-toggle', { isOpen: false })"
         @safenode-sidebar-toggle.window="sidebarOpen = $event.detail.isOpen"
         class="fixed inset-0 bg-black/80 z-[60] lg:hidden"
         style="pointer-events: auto;"
         x-cloak></div>

    <!-- Mobile Sidebar -->
    <aside x-show="sidebarOpen"
           x-init="$watch('sidebarOpen', value => { 
               if (value) { 
                   $el.style.display = 'flex'; 
                   $el.removeAttribute('x-cloak');
               } else {
                   $el.style.display = 'none';
               }
           })"
           x-transition:enter="transition ease-out duration-300 transform"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-300 transform"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           @click.away="sidebarOpen = false; $dispatch('safenode-sidebar-toggle', { isOpen: false })"
           @safenode-sidebar-toggle.window="sidebarOpen = $event.detail.isOpen"
           class="fixed inset-y-0 left-0 w-72 sidebar h-full flex flex-col z-[70] lg:hidden overflow-y-auto mobile-sidebar"
           style="position: fixed !important; transform: translateX(-100%); will-change: transform;"
           x-cloak>
        <!-- Logo -->
        <div class="p-4 border-b border-white/5 flex-shrink-0 relative">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-8 h-8 object-contain flex-shrink-0">
                    <div class="overflow-hidden whitespace-nowrap">
                        <h1 class="font-bold text-white text-xl tracking-tight">SafeNode</h1>
                        <p class="text-xs text-zinc-500 font-medium">Security Platform</p>
                    </div>
                </div>
                <button @click="sidebarOpen = false; $dispatch('safenode-sidebar-toggle', { isOpen: false })" class="text-zinc-600 hover:text-zinc-400 transition-colors flex-shrink-0">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto overflow-x-hidden">
            <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Principal</p>
            
            <a href="<?php echo getSafeNodeUrl('dashboard'); ?>" class="nav-item <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Dashboard</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('sites'); ?>" class="nav-item <?php echo $currentPage == 'sites' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="globe" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Gerenciar Sites</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('mail'); ?>" class="nav-item <?php echo $currentPage == 'mail' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="mail" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Mail</span>
            </a>
            
            <div class="pt-4 mt-4 border-t border-white/5">
                <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Análises</p>
                <a href="<?php echo getSafeNodeUrl('logs'); ?>" class="nav-item <?php echo $currentPage == 'logs' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="file-text" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Explorar Logs</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('behavior-analysis'); ?>" class="nav-item <?php echo $currentPage == 'behavior-analysis' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="brain" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Comportamental</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('security-analytics'); ?>" class="nav-item <?php echo $currentPage == 'security-analytics' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="lightbulb" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Analytics</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('suspicious-ips'); ?>" class="nav-item <?php echo $currentPage == 'suspicious-ips' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="alert-octagon" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">IPs Suspeitos</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('attacked-targets'); ?>" class="nav-item <?php echo $currentPage == 'attacked-targets' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="target" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Alvos Atacados</span>
                </a>
            </div>
            
            <div class="pt-4 mt-4 border-t border-white/5">
                <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Inteligência</p>
                <a href="<?php echo getSafeNodeUrl('threat-intelligence'); ?>" class="nav-item <?php echo $currentPage == 'threat-intelligence' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="shield-alert" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Threat Intelligence</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('security-advisor'); ?>" class="nav-item <?php echo $currentPage == 'security-advisor' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Security Advisor</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('vulnerability-scanner'); ?>" class="nav-item <?php echo $currentPage == 'vulnerability-scanner' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="scan-search" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Vulnerability Scanner</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('anomaly-detector'); ?>" class="nav-item <?php echo $currentPage == 'anomaly-detector' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="radar" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Anomaly Detector</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('endpoint-protection'); ?>" class="nav-item <?php echo $currentPage == 'endpoint-protection' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="route" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Proteção por Endpoint</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('security-tests'); ?>" class="nav-item <?php echo $currentPage == 'security-tests' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="test-tube" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Testes de Segurança</span>
                </a>
            </div>
            
            <div class="pt-4 mt-4 border-t border-white/5">
                <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Sistema</p>
                <a href="<?php echo getSafeNodeUrl('updates'); ?>" class="nav-item <?php echo $currentPage == 'updates' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="sparkles" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Atualizações</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('human-verification'); ?>" class="nav-item <?php echo $currentPage == 'human-verification' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Verificação Humana</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('settings'); ?>" class="nav-item <?php echo $currentPage == 'settings' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="settings-2" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Configurações</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('help'); ?>" class="nav-item <?php echo $currentPage == 'help' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="life-buoy" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Ajuda</span>
                </a>
            </div>
        </nav>
        
        <!-- Upgrade Card -->
        <div class="p-4 flex-shrink-0">
            <div class="upgrade-card">
                <h3 class="font-semibold text-white text-sm mb-3">Ativar Pro</h3>
                <button class="w-full btn-primary py-2.5 text-sm">
                    Upgrade Agora
                </button>
            </div>
        </div>
    </aside>
</body>
</html>

