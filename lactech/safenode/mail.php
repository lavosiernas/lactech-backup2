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
        
        /* Estilos para select e opções */
        select[name="email_function"] {
            color-scheme: dark;
            z-index: 50;
            position: relative;
        }
        
        select[name="email_function"] option {
            background-color: #0a0a0a !important;
            color: #ffffff !important;
            padding: 0.75rem 1rem;
        }
        
        select[name="email_function"]:focus {
            z-index: 100;
        }
        
        /* Garantir que o container não corte o dropdown */
        .bg-dark-800 {
            overflow: visible;
        }
        
        /* Ajustar para o grid não cortar */
        .grid {
            overflow: visible;
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
                <!-- Criar Novo Projeto -->
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6 mb-6" style="overflow: visible;">
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
                                    class="w-full bg-dark-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-white/30 focus:ring-2 focus:ring-white/10 transition-all appearance-none cursor-pointer"
                                    style="background-image: url('data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'white\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3E%3Cpolyline points=\'6 9 12 15 18 9\'%3E%3C/polyline%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1.25rem; padding-right: 2.5rem;">
                                    <option value="" class="bg-dark-900 text-white">Selecione uma função</option>
                                    <option value="confirm_signup" class="bg-dark-900 text-white">Confirm sign up - Confirmar cadastro</option>
                                    <option value="invite_user" class="bg-dark-900 text-white">Invite user - Convidar usuário</option>
                                    <option value="magic_link" class="bg-dark-900 text-white">Magic link - Link mágico de login</option>
                                    <option value="change_email" class="bg-dark-900 text-white">Change email address - Alterar e-mail</option>
                                    <option value="reset_password" class="bg-dark-900 text-white">Reset password - Redefinir senha</option>
                                    <option value="reauthentication" class="bg-dark-900 text-white">Reauthentication - Reautenticação</option>
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
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button onclick="openCodeIDE()" class="text-xs text-white px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 transition-colors inline-flex items-center gap-2 font-medium">
                                            <i data-lucide="code" class="w-3.5 h-3.5"></i> IDE de Código
                                        </button>
                                        <button onclick="openVisualEditor()" class="text-xs text-white px-4 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 transition-colors inline-flex items-center gap-2 font-medium">
                                            <i data-lucide="layout" class="w-3.5 h-3.5"></i> Editor Visual
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Área para exibir código (oculta) -->
                                <!-- Template será criado na IDE/Editor Visual -->
                                <textarea name="html_template" id="html-editor" style="display: none;"></textarea>
                                
                                <!-- Área informativa -->
                                <div class="p-8 text-center">
                                    <div class="bg-dark-800 rounded-lg border border-white/5 p-6 max-w-lg mx-auto">
                                        <div class="flex items-center justify-center gap-3 mb-4">
                                            <i data-lucide="code-2" class="w-10 h-10 text-blue-400"></i>
                                            <i data-lucide="layout" class="w-10 h-10 text-purple-400"></i>
                                        </div>
                                        <h4 class="text-white font-semibold mb-2">Escolha seu Editor</h4>
                                        <p class="text-xs text-zinc-400 mb-6">
                                            Você pode criar seu template de duas formas:
                                        </p>
                                        
                                        <!-- Botões dos editores -->
                                        <div class="grid grid-cols-2 gap-3 mb-4">
                                            <button onclick="openCodeIDE()" type="button" class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors inline-flex flex-col items-center gap-2 border-2 border-blue-600 hover:border-blue-500">
                                                <i data-lucide="code" class="w-5 h-5"></i>
                                                <span class="font-semibold">IDE de Código</span>
                                                <span class="text-xs opacity-90">Editor + Preview + IA</span>
                                            </button>
                                            <button onclick="openVisualEditor()" type="button" class="px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition-colors inline-flex flex-col items-center gap-2 border-2 border-purple-600 hover:border-purple-500">
                                                <i data-lucide="layout" class="w-5 h-5"></i>
                                                <span class="font-semibold">Editor Visual</span>
                                                <span class="text-xs opacity-90">Drag & Drop</span>
                                            </button>
                                        </div>
                                        
                                        <div id="code-status" class="mb-4 hidden">
                                            <div class="bg-green-600/20 border border-green-600/30 rounded-lg px-3 py-2 text-xs text-green-400 inline-flex items-center gap-2">
                                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                                <span>Código salvo! Abra o editor novamente para continuar editando.</span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-xs text-zinc-400 mt-4">Use variáveis como {{nome}}, {{codigo}}, {{link}} que serão substituídas automaticamente</p>
                                </div>
                            </div>
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

    <script>
    
    // Inicializar ícones
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Função para abrir o editor visual com o template atual
    function openVisualEditor() {
        // Obter dados do formulário
        const form = document.getElementById('create-project-form');
        if (!form) {
            // Se não estiver na página de criar projeto, apenas abrir o editor
            window.open('safefig.php', '_blank');
            return;
        }
        
        // Validar campos obrigatórios
        const name = form.querySelector('input[name="name"]')?.value?.trim();
        const senderEmail = form.querySelector('input[name="sender_email"]')?.value?.trim();
        const emailFunction = form.querySelector('select[name="email_function"]')?.value?.trim();
        
        // Validar se os campos obrigatórios estão preenchidos
        if (!name || !senderEmail || !emailFunction) {
            alert('⚠️ Por favor, preencha todos os campos obrigatórios do formulário antes de abrir o Editor Visual:\n\n' +
                  '✗ Nome do Projeto\n' +
                  '✗ E-mail Remetente\n' +
                  '✗ Função do E-mail\n\n' +
                  'Após preencher, você poderá criar o template HTML no editor.');
            // Rolar para o topo do formulário
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        
        // Obter HTML do editor (opcional - pode estar vazio)
        let html = '';
        const textarea = document.getElementById('html-editor');
        if (textarea) {
            html = textarea.value || '';
        }
        
        // Obter todos os dados do formulário
        const senderName = form.querySelector('input[name="sender_name"]')?.value?.trim() || '';
        
        // Gerar ID único temporário para o projeto
        const projectId = 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        // Preparar dados para enviar
        const formData = new URLSearchParams();
        formData.append('html_template', html);
        formData.append('project_name', name);
        formData.append('sender_email', senderEmail);
        formData.append('sender_name', senderName);
        formData.append('email_function', emailFunction);
        formData.append('project_id', projectId);
        formData.append('editor_type', 'visual');
        
        // Enviar dados para a sessão via AJAX
        fetch('api/set-safefig-template.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Abrir safefig.php em nova aba
                window.open('safefig.php?project_id=' + projectId, '_blank');
            } else {
                alert('Erro ao preparar template: ' + (data.error || 'Erro desconhecido'));
                // Tentar abrir mesmo assim
                window.open('safefig.php', '_blank');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao conectar com o servidor. Tentando abrir o editor mesmo assim...');
            // Tentar abrir mesmo assim
            window.open('safefig.php', '_blank');
        });
    }
    
    // Função para abrir a IDE de código
    function openCodeIDE() {
        // Obter dados do formulário
        const form = document.getElementById('create-project-form');
        if (!form) {
            // Se não estiver na página de criar projeto, apenas abrir a IDE
            window.open('ide.php', '_blank');
            return;
        }
        
        // Validar campos obrigatórios (exceto html_template que será preenchido na IDE)
        const name = form.querySelector('input[name="name"]')?.value?.trim();
        const senderEmail = form.querySelector('input[name="sender_email"]')?.value?.trim();
        const emailFunction = form.querySelector('select[name="email_function"]')?.value?.trim();
        
        // Validar se os campos obrigatórios estão preenchidos
        if (!name || !senderEmail || !emailFunction) {
            alert('⚠️ Por favor, preencha todos os campos obrigatórios do formulário antes de abrir a IDE:\n\n' +
                  '✗ Nome do Projeto\n' +
                  '✗ E-mail Remetente\n' +
                  '✗ Função do E-mail\n\n' +
                  'Após preencher, você poderá criar o template HTML na IDE.');
            // Rolar para o topo do formulário
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        
        // Obter HTML do editor (se houver)
        let html = '';
        const textarea = document.getElementById('html-editor');
        if (textarea) {
            html = textarea.value || '';
        }
        
        // Obter todos os dados do formulário
        const senderName = form.querySelector('input[name="sender_name"]')?.value?.trim() || '';
        
        // Gerar ID único temporário para o projeto
        const projectId = 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        // Preparar dados para enviar
        const formData = new URLSearchParams();
        formData.append('html_template', html);
        formData.append('project_name', name);
        formData.append('sender_email', senderEmail);
        formData.append('sender_name', senderName);
        formData.append('email_function', emailFunction);
        formData.append('project_id', projectId);
        formData.append('editor_type', 'ide');
        
        // Enviar dados para a sessão via AJAX
        fetch('api/set-safefig-template.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Abrir ide.php em nova aba com referência do projeto
                window.open('ide.php?project_id=' + projectId, '_blank');
            } else {
                alert('Erro ao preparar dados: ' + (data.error || 'Erro desconhecido'));
                // Tentar abrir mesmo assim
                window.open('ide.php', '_blank');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao conectar com o servidor. Tentando abrir a IDE mesmo assim...');
            // Tentar abrir mesmo assim
            window.open('ide.php', '_blank');
        });
    }
    
    // Tornar funções globais IMEDIATAMENTE (ANTES de qualquer outra coisa)
    window.openCodeIDE = openCodeIDE;
    window.openVisualEditor = openVisualEditor;
    
    // Debug: verificar se as funções foram definidas
    console.log('Funções definidas:', {
        openCodeIDE: typeof window.openCodeIDE,
        openVisualEditor: typeof window.openVisualEditor
    });
    
    // Carregar código salvo da sessão (se houver)
    function loadSavedCode() {
        const textarea = document.getElementById('html-editor');
        if (!textarea) return;
        
        fetch('api/get-saved-template.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.html_template && data.html_template.trim() !== '') {
                    textarea.value = data.html_template;
                    // Mostrar indicador de código salvo
                    const codeStatus = document.getElementById('code-status');
                    if (codeStatus) {
                        codeStatus.classList.remove('hidden');
                    }
                    // Atualizar ícones
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }
            })
            .catch(error => {
                console.error('Erro ao carregar código salvo:', error);
            });
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Carregar código salvo se houver
        loadSavedCode();
        
        // Atualizar textarea antes de submeter (o código vem da IDE/Editor Visual)
        const form = document.getElementById('create-project-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // O textarea já terá o valor quando o usuário salvar na IDE ou Editor Visual
                // Não precisa fazer nada aqui, apenas deixar o formulário submeter normalmente
            });
        }
    });
    
    // Preview do email - redireciona para safefig.php
    function previewEmail() {
        window.open('safefig.php', '_blank');
    }
    
    // Tornar previewEmail global para acesso via onclick
    window.previewEmail = previewEmail;
    
    // Função removida - Editor Visual agora é safefig.php
    function initVisualEditor() {
        // Editor visual foi movido para safefig.php
        return;
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

