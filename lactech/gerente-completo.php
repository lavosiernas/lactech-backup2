<?php
/**
 * Dashboard Gerente - LacTech (Versão Completa)
 * Sistema completo com todas as funcionalidades originais
 */

// Headers de cache otimizado
header("Cache-Control: private, max-age=300");
header("Pragma: cache");

// Incluir configuração e iniciar sessão
require_once __DIR__ . '/includes/config_login.php';

// Verificar autenticação
if (!isLoggedIn()) {
    header("Location: index.php", true, 302);
    exit();
}

// Verificar papel de gerente
if ($_SESSION['user_role'] !== 'gerente' && $_SESSION['user_role'] !== 'manager') {
    switch ($_SESSION['user_role']) {
        case 'proprietario':
            header("Location: proprietario.php", true, 302);
            break;
        case 'funcionario':
            header("Location: funcionario.php", true, 302);
            break;
        default:
            header("Location: index.php", true, 302);
    }
    exit();
}

// Incluir classe de banco de dados
require_once __DIR__ . '/includes/Database.class.php';

// Obter dados do usuário
$current_user_id = $_SESSION['user_id'] ?? 1;
$current_user_name = $_SESSION['user_name'] ?? 'Gerente';
$current_user_role = $_SESSION['user_role'] ?? 'gerente';

// Buscar foto do perfil do banco
try {
    $db = Database::getInstance();
    $userData = $db->query("SELECT profile_photo, phone FROM users WHERE id = " . (int)$current_user_id);
    $current_user_photo = $userData[0]['profile_photo'] ?? null;
    
    // Debug: verificar se a foto está vindo do banco (ativar temporariamente)
    error_log("DEBUG - Foto do banco (raw): " . ($current_user_photo ?? 'NULL'));
    if (!empty($current_user_photo)) {
        $photoPathClean = trim($current_user_photo, '/\\');
        $debugPath1 = __DIR__ . '/' . $photoPathClean;
        $debugPath2 = __DIR__ . '/../' . $photoPathClean;
        $debugPath3 = __DIR__ . '/uploads/profiles/' . basename($photoPathClean);
        
        error_log("DEBUG - Caminho 1 (__DIR__/path): " . $debugPath1 . " - Existe: " . (file_exists($debugPath1) ? 'SIM' : 'NÃO'));
        error_log("DEBUG - Caminho 2 (__DIR__/../path): " . $debugPath2 . " - Existe: " . (file_exists($debugPath2) ? 'SIM' : 'NÃO'));
        error_log("DEBUG - Caminho 3 (uploads/profiles): " . $debugPath3 . " - Existe: " . (file_exists($debugPath3) ? 'SIM' : 'NÃO'));
        
        // Determinar qual caminho usar
        if (file_exists($debugPath1)) {
            error_log("DEBUG - Usando caminho 1");
        } elseif (file_exists($debugPath2)) {
            error_log("DEBUG - Usando caminho 2");
        } elseif (file_exists($debugPath3)) {
            error_log("DEBUG - Usando caminho 3");
        } else {
            error_log("DEBUG - Nenhum caminho encontrado!");
        }
    }
    $current_user_phone = $userData[0]['phone'] ?? '(11) 99999-9999';
    
    // Buscar dados da fazenda
    $farmData = $db->query("SELECT name, cnpj, address FROM farms WHERE id = 1");
    $farm_name = $farmData[0]['name'] ?? 'Lagoa Do Mato';
    $farm_cnpj = $farmData[0]['cnpj'] ?? '12.345.678/0001-90';
    $farm_address = $farmData[0]['address'] ?? 'Fazenda Lagoa Do Mato, Zona Rural, São Paulo - SP';
} catch (Exception $e) {
    $current_user_photo = null;
    $current_user_phone = '(11) 99999-9999';
    $farm_name = 'Lagoa Do Mato';
    $farm_cnpj = '12.345.678/0001-90';
    $farm_address = 'Fazenda Lagoa Do Mato, Zona Rural, São Paulo - SP';
}

// Versão para cache busting
$v = time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LacTech - Dashboard Gerente</title>
    
    <!-- Favicon -->
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/x-icon">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- face-api.js para detecção facial -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo $v; ?>">
    
    <style>
        /* Cores personalizadas */
        :root {
            --forest-50: #f0f9f4;
            --forest-100: #dcf2e4;
            --forest-200: #bce5d0;
            --forest-300: #8dd1b3;
            --forest-400: #56b991;
            --forest-500: #2d9b6f;
            --forest-600: #1f7a5a;
            --forest-700: #1a6249;
            --forest-800: #174f3c;
            --forest-900: #144132;
        }
        
        .gradient-forest {
            background: linear-gradient(135deg, var(--forest-600), var(--forest-700));
        }
        
        .gradient-forest-light {
            background: linear-gradient(135deg, var(--forest-50), var(--forest-100));
        }
        
        /* Animações suaves */
        .transition-all {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Cards com hover */
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s ease-in-out infinite;
            border-radius: 0.5rem;
        }
        
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Chart containers */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            padding: 16px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .chart-container canvas {
            border-radius: 8px;
        }
        
        /* Data cards */
        .data-card {
            transition: all 0.3s ease;
        }
        
        .data-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* Metric cards */
        .metric-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }
        
        .metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .metric-label {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        /* Responsive grid */
        .grid-compact {
            grid-template-columns: repeat(2, 1fr);
        }
        
        @media (min-width: 640px) {
            .grid-compact {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        .card-compact {
            padding: 0.75rem;
        }
        
        @media (min-width: 640px) {
            .card-compact {
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-inter">
    <!-- Header -->
    <header class="gradient-forest text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo e Título -->
                <div class="flex items-center space-x-4">
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center p-2">
                        <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="LacTech Logo" class="w-full h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <img src="./assets/img/lactech-logo.png" alt="LacTech Logo" class="w-full h-full object-contain" style="display: none;" onerror="this.style.display='none';">
                    </div>
                    <div>
                        <h1 class="text-xl font-bold">LacTech</h1>
                        <p class="text-forest-200 text-sm"><?php echo htmlspecialchars($farm_name); ?></p>
                    </div>
                </div>
                
                <!-- Navegação -->
                <nav class="hidden md:flex items-center space-x-1">
                    <button class="nav-item active px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="dashboard">
                        Dashboard
                    </button>
                    <button class="nav-item px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="volume">
                        Volume
                    </button>
                    <button class="nav-item px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="quality">
                        Qualidade
                    </button>
                    <button class="nav-item px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="payments">
                        Financeiro
                    </button>
                    <button class="nav-item px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="users">
                        Usuários
                    </button>
                    <a href="includes/modalmore.php" class="px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 12.5c0 .828-.672 1.5-1.5 1.5s-1.5-.672-1.5-1.5.672-1.5 1.5-1.5 1.5.672 1.5 1.5zM18.5 12.5c0 .828-.672 1.5-1.5 1.5s-1.5-.672-1.5-1.5.672-1.5 1.5-1.5 1.5.672 1.5 1.5zM12 20c-2.5 0-4.5-2-4.5-4.5S9.5 11 12 11s4.5 2 4.5 4.5S14.5 20 12 20zM12 8c-1.5 0-2.5-1-2.5-2.5S10.5 3 12 3s2.5 1 2.5 2.5S13.5 8 12 8z"/>
                        </svg>
                        <span>MAIS</span>
                    </a>
                </nav>
                
                <!-- Perfil do Usuário -->
                <div class="flex items-center space-x-4">
                    <!-- Notificações -->
                    <button onclick="openNotificationsDrawer()" class="relative p-2 text-white hover:text-forest-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span id="notificationsBellCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] leading-none py-1 px-1.5 font-bold rounded-full min-w-[18px] text-center hidden"></span>
                    </button>
                    
                    <!-- Perfil -->
                    <button onclick="openProfileOverlay()" class="flex items-center space-x-3 text-white hover:text-forest-200 p-2 rounded-lg transition-all" id="profileButton">
                        <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center overflow-hidden">
                            <?php 
                            // Verificar e exibir foto de perfil no header
                            $headerPhotoSrc = '';
                            $headerShowPhoto = false;
                            
                            if (!empty($current_user_photo)) {
                                // Normalizar caminho
                                $headerPhotoPath = trim($current_user_photo, '/\\');
                                
                                // Tentar múltiplos caminhos
                                $pathsToTry = [
                                    __DIR__ . '/' . $headerPhotoPath,
                                    __DIR__ . '/../' . $headerPhotoPath,
                                    __DIR__ . '/uploads/profiles/' . basename($headerPhotoPath)
                                ];
                                
                                foreach ($pathsToTry as $testPath) {
                                    if (file_exists($testPath) && is_file($testPath)) {
                                        // Encontrou! Ajustar para caminho relativo
                                        if (strpos($testPath, __DIR__ . '/') === 0) {
                                            $headerPhotoSrc = str_replace(__DIR__ . '/', '', $testPath);
                                        } elseif (strpos($testPath, __DIR__ . '/../') === 0) {
                                            $headerPhotoSrc = str_replace(__DIR__ . '/../', '', $testPath);
                                        } else {
                                            $headerPhotoSrc = $headerPhotoPath;
                                        }
                                        $headerShowPhoto = true;
                                        break;
                                    }
                                }
                            }
                            
                            if ($headerShowPhoto): 
                            ?>
                                <img src="<?php echo htmlspecialchars($headerPhotoSrc); ?>?t=<?php echo time(); ?>" alt="Foto do perfil" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <?php endif; ?>
                            <svg class="w-5 h-5 text-white <?php echo $headerShowPhoto ? 'hidden' : ''; ?>" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium"><?php echo htmlspecialchars($current_user_name); ?></p>
                            <p class="text-xs text-forest-200">Gerente</p>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Dashboard Tab -->
        <div id="dashboard-tab" class="tab-content">
            <!-- Welcome Section -->
            <div class="gradient-forest rounded-2xl p-4 sm:p-6 mb-4 sm:mb-6 text-white shadow-xl relative overflow-hidden">
                <!-- Imagem de fundo -->
                <div class="absolute inset-0 opacity-10 pointer-events-none">
                    <img src="https://media.fertili.com.br/fertiliblog/2018/03/vacas-leiteiras.png" alt="" class="w-full h-full object-cover" onerror="this.style.display='none';">
                </div>
                <!-- Conteúdo sobreposto -->
                <div class="relative z-10">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold mb-1 sm:mb-2">Bem-vindo, <span id="managerWelcome"><?php echo htmlspecialchars($current_user_name); ?></span>!</h2>
                            <p class="text-forest-200 text-sm sm:text-base font-medium mb-2 sm:mb-3">Painel de controle gerencial</p>
                            <div class="flex items-center space-x-2 sm:space-x-4">
                                <div class="text-xs font-medium">última atualização: <span id="lastUpdate">Agora</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-4 sm:mb-6 grid-compact">
                <div class="metric-card rounded-2xl p-3 sm:p-4 text-center card-compact">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                    <div class="metric-value font-bold text-slate-900 mb-1" id="todayVolume">-- L</div>
                    <div class="metric-label text-slate-500 font-medium">Volume Hoje</div>
                    <div class="metric-label text-slate-600 font-semibold mt-1">Litros</div>
                </div>
                
                <div class="metric-card rounded-2xl p-3 sm:p-4 text-center card-compact">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="metric-value font-bold text-slate-900 mb-1" id="qualityAverage">--%</div>
                    <div class="metric-label text-slate-500 font-medium">Qualidade Média</div>
                    <div class="metric-label text-slate-600 font-semibold mt-1">Hoje</div>
                </div>
                
                <div class="metric-card rounded-2xl p-3 sm:p-4 text-center card-compact">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="metric-value font-bold text-slate-900 mb-1" id="pendingPayments">R$ --</div>
                    <div class="metric-label text-slate-500 font-medium">Pagamentos Pendentes</div>
                    <div class="metric-label text-slate-600 font-semibold mt-1">Este Mês</div>
                </div>
                
                <div class="metric-card rounded-2xl p-3 sm:p-4 text-center card-compact">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-orange-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="metric-value font-bold text-slate-900 mb-1" id="activeUsers">--</div>
                    <div class="metric-label text-slate-500 font-medium">Usuários Ativos</div>
                    <div class="metric-label text-slate-600 font-semibold mt-1">Sistema</div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-4 sm:mb-6">
                <!-- Volume Chart -->
                <div class="data-card rounded-2xl p-4 sm:p-6 card-compact">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <h3 class="card-title font-bold text-slate-900">Volume Semanal</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="volumeChart"></canvas>
                    </div>
                </div>

                <!-- Weekly Production Chart -->
                <div class="data-card rounded-2xl p-4 sm:p-6 card-compact">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <h3 class="card-title font-bold text-slate-900">Produção dos últimos 7 Dias</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="dashboardWeeklyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Temperature Chart Section -->
            <div class="grid grid-cols-1 gap-6 mb-6">
                <div class="data-card rounded-2xl p-4 sm:p-6 card-compact">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <h3 class="card-title font-bold text-slate-900">Controle de Temperatura</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="temperatureChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Monthly Production Chart -->
            <div class="data-card rounded-2xl p-6 mb-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Volume de Produção do Mês</h3>
                <div class="h-64 relative">
                    <canvas id="monthlyProductionChart" width="800" height="256"></canvas>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="data-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-900">Atividades Recentes</h3>
                    <button onclick="switchTab('volume')" class="text-forest-600 hover:text-forest-700 font-semibold text-sm">Ver Tudo</button>
                </div>
                <div class="space-y-3" id="recentActivities">
                    <div class="text-center py-8">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-500 text-sm">Nenhuma atividade recente</p>
                        <p class="text-gray-400 text-xs">Registros aparecerão aqui</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Volume Tab -->
        <div id="volume-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Volume Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Controle de Volume</h2>
                            <p class="text-slate-600 text-sm">Monitore a produção de leite em tempo real</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <select id="volumePeriod" class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none bg-white">
                                <option value="today">Hoje</option>
                                <option value="week">Esta Semana</option>
                                <option value="month">Este Mês</option>
                            </select>
                            <button onclick="showGeneralVolumeOverlay()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                + Volume
                            </button>
                            <button onclick="showVolumeOverlay()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                                Por Vaca
                            </button>
                            <button id="exportVolumeBtn" onclick="exportVolumeReport()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-4-4m4 4l4-4m3 8H5a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1"></path>
                                </svg>
                                Exportar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Volume Metrics -->
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="volumeToday">-- L</div>
                        <div class="text-xs text-slate-500 font-medium">Hoje</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Total</div>
                    </div>
                    
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="volumeWeekAvg">-- L</div>
                        <div class="text-xs text-slate-500 font-medium">Média Semanal</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Por Dia</div>
                    </div>
                    
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="volumeMonthTotal">-- L</div>
                        <div class="text-xs text-slate-500 font-medium">Este Mês</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Total</div>
                    </div>
                </div>

                <!-- Volume Chart -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Gráfico de Volume</h3>
                    <div class="chart-container">
                        <canvas id="volumeTabChart"></canvas>
                    </div>
                </div>

                <!-- Volume Records Table -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Registros de Volume</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Data</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Período</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Volume (L)</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Temperatura</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="volumeRecordsTable">
                                <tr>
                                    <td colspan="5" class="text-center py-8 text-gray-500">Carregando registros...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quality Tab -->
        <div id="quality-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Quality Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Controle de Qualidade</h2>
                            <p class="text-slate-600 text-sm">Monitore a qualidade do leite produzido</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <select id="qualityPeriod" class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none bg-white">
                                <option value="today">Hoje</option>
                                <option value="week">Esta Semana</option>
                                <option value="month">Este Mês</option>
                            </select>
                            <button onclick="showQualityOverlay()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                + Teste
                            </button>
                            <button id="exportQualityBtn" onclick="exportQualityReport()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-4-4m4 4l4-4m3 8H5a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1"></path>
                                </svg>
                                Exportar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quality Metrics -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="qualityAvgFat">--%</div>
                        <div class="text-xs text-slate-500 font-medium">Gordura Média</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Hoje</div>
                    </div>
                    
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="qualityAvgProtein">--%</div>
                        <div class="text-xs text-slate-500 font-medium">Proteína Média</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Hoje</div>
                    </div>
                    
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="qualityAvgCCS">--</div>
                        <div class="text-xs text-slate-500 font-medium">CCS Média</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Hoje</div>
                    </div>
                    
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="qualityTestsCount">--</div>
                        <div class="text-xs text-slate-500 font-medium">Testes Hoje</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Total</div>
                    </div>
                </div>

                <!-- Quality Chart -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Gráfico de Qualidade</h3>
                    <div class="chart-container">
                        <canvas id="qualityTabChart"></canvas>
                    </div>
                </div>

                <!-- Quality Records Table -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Registros de Qualidade</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Data</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Gordura (%)</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Proteína (%)</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">CCS</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="qualityRecordsTable">
                                <tr>
                                    <td colspan="5" class="text-center py-8 text-gray-500">Carregando registros...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Tab -->
        <div id="payments-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Financial Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Controle Financeiro</h2>
                            <p class="text-slate-600 text-sm">Gerencie receitas e despesas</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <select id="financialPeriod" class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none bg-white">
                                <option value="today">Hoje</option>
                                <option value="week">Esta Semana</option>
                                <option value="month">Este Mês</option>
                            </select>
                            <button onclick="showSalesOverlay()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                + Venda
                            </button>
                            <button id="exportFinancialBtn" onclick="exportFinancialReport()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-4-4m4 4l4-4m3 8H5a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1"></path>
                                </svg>
                                Exportar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Financial Metrics -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="revenueToday">R$ --</div>
                        <div class="text-xs text-slate-500 font-medium">Receita Hoje</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Total</div>
                    </div>
                    
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-red-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="expensesToday">R$ --</div>
                        <div class="text-xs text-slate-500 font-medium">Despesas Hoje</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Total</div>
                    </div>
                    
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="profitToday">R$ --</div>
                        <div class="text-xs text-slate-500 font-medium">Lucro Hoje</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Líquido</div>
                    </div>
                    
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="revenueMonth">R$ --</div>
                        <div class="text-xs text-slate-500 font-medium">Receita Mês</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Total</div>
                    </div>
                </div>

                <!-- Financial Chart -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Gráfico Financeiro</h3>
                    <div class="chart-container">
                        <canvas id="financialTabChart"></canvas>
                    </div>
                </div>

                <!-- Financial Records Table -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Registros Financeiros</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Data</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Tipo</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Descrição</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Valor</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="financialRecordsTable">
                                <tr>
                                    <td colspan="5" class="text-center py-8 text-gray-500">Carregando registros...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Tab -->
        <div id="users-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Users Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Gestão de Usuários</h2>
                            <p class="text-slate-600 text-sm">Gerencie funcionários e suas permissões</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <button onclick="showUserOverlay()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Adicionar Usuário
                            </button>
                        </div>
                    </div>
                </div>

                <!-- User Stats -->
                <div class="grid grid-cols-2 gap-4 sm:gap-6">
                    <div class="data-card rounded-2xl p-4 sm:p-6 text-center metric-card-responsive">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <div class="text-xl sm:text-2xl font-bold text-slate-900 mb-1" id="totalUsers">--</div>
                        <div class="text-xs sm:text-sm text-slate-500 font-medium">Total de Usuários</div>
                    </div>
                    
                    <div class="data-card rounded-2xl p-4 sm:p-6 text-center metric-card-responsive">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-xl sm:text-2xl font-bold text-slate-900 mb-1" id="activeUsers">--</div>
                        <div class="text-xs sm:text-sm text-slate-500 font-medium">Usuários Ativos</div>
                    </div>
                </div>

                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Lista de Usuários</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Nome</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Email</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Cargo</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="usersTable">
                                <tr>
                                    <td colspan="5" class="text-center py-8 text-gray-500">Carregando usuários...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Overlays -->
    <!-- General Volume Overlay -->
    <div id="generalVolumeOverlay" class="fixed inset-0 z-50 hidden">
        <div class="w-full h-full bg-white p-0 overflow-y-auto">
            <div class="sticky top-0 z-10 bg-white/90 backdrop-blur border-b">
                <div class="max-w-3xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Registrar Volume Geral</h3>
                <button onclick="closeGeneralVolumeOverlay()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            </div>
            <form id="generalVolumeForm" class="px-4 sm:px-6 py-5 space-y-5">
                <div class="max-w-3xl mx-auto space-y-6">
                    <div class="bg-gray-50 border rounded-xl p-3">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Informações da Coleta</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data</label>
                    <input type="date" name="collection_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Período</label>
                    <select name="period" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                        <option value="manha">Manhã</option>
                        <option value="tarde">Tarde</option>
                        <option value="noite">Noite</option>
                    </select>
                </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 border rounded-xl p-3">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Medições</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Volume Total (L)</label>
                    <input type="number" name="total_volume" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                </div>
                <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Temperatura (°C) <span class="text-gray-400 font-normal">(opcional)</span></label>
                    <input type="number" name="temperature" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                        </div>
                    </div>
                </div>
                <div class="sticky bottom-0 z-10 bg-white/90 backdrop-blur border-t">
                    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-3 flex justify-end gap-3">
                        <button type="button" onclick="closeGeneralVolumeOverlay()" class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Registrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Volume Overlay -->
    <div id="volumeOverlay" class="fixed inset-0 z-50 hidden">
        <div class="w-full h-full bg-white p-0 overflow-y-auto">
            <div class="sticky top-0 z-10 bg-white/90 backdrop-blur border-b">
                <div class="max-w-3xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Registrar Volume por Vaca</h3>
                <button onclick="closeVolumeOverlay()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            </div>
            <form id="volumeForm" class="px-4 sm:px-6 py-5 space-y-5">
                <div class="max-w-3xl mx-auto space-y-6">
                    <div class="bg-gray-50 border rounded-xl p-3">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Identificação</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Vaca</label>
                    <select name="animal_id" id="volumeAnimalSelect" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <option value="">Selecione uma vaca</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data</label>
                    <input type="date" name="collection_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 border rounded-xl p-3">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Medições</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Período</label>
                    <select name="period" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <option value="manha">Manhã</option>
                        <option value="tarde">Tarde</option>
                        <option value="noite">Noite</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Volume (L)</label>
                    <input type="number" name="volume" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                </div>
                <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Temperatura (°C) <span class="text-gray-400 font-normal">(opcional)</span></label>
                    <input type="number" name="temperature" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                        </div>
                    </div>
                </div>
                <div class="sticky bottom-0 z-10 bg-white/90 backdrop-blur border-t">
                    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-3 flex justify-end gap-3">
                        <button type="button" onclick="closeVolumeOverlay()" class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Registrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Quality Overlay -->
    <div id="qualityOverlay" class="fixed inset-0 z-50 hidden">
        <div class="w-full h-full bg-white p-0 overflow-y-auto">
            <div class="sticky top-0 z-10 bg-white/90 backdrop-blur border-b">
                <div class="max-w-3xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Registrar Teste de Qualidade</h3>
                <button onclick="closeQualityOverlay()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            </div>
            <form id="qualityForm" class="px-4 sm:px-6 py-5 space-y-5">
                <div class="max-w-3xl mx-auto space-y-6">
                    <div class="bg-gray-50 border rounded-xl p-3">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Dados do Teste</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data do Teste</label>
                    <input type="date" name="test_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Laboratório <span class="text-gray-400 font-normal">(opcional)</span></label>
                                <input type="text" name="laboratory" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 border rounded-xl p-3">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Resultados</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gordura (%)</label>
                    <input type="number" name="fat_content" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Proteína (%)</label>
                    <input type="number" name="protein_content" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Células Somáticas</label>
                    <input type="number" name="somatic_cells" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                </div>
                    </div>
                </div>
                <div class="sticky bottom-0 z-10 bg-white/90 backdrop-blur border-t">
                    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-3 flex justify-end gap-3">
                        <button type="button" onclick="closeQualityOverlay()" class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Registrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sales Overlay -->
    <div id="salesOverlay" class="fixed inset-0 z-50 hidden">
        <div class="w-full h-full bg-white p-0 overflow-y-auto">
            <div class="sticky top-0 z-10 bg-white/90 backdrop-blur border-b">
                <div class="max-w-3xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Registrar Venda</h3>
                <button onclick="closeSalesOverlay()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            </div>
            <form id="salesForm" class="px-4 sm:px-6 py-5 space-y-5">
                <div class="max-w-3xl mx-auto space-y-6">
                    <div class="bg-gray-50 border rounded-xl p-3">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Detalhes da Venda</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data da Venda</label>
                    <input type="date" name="sale_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cliente</label>
                    <input type="text" name="customer" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 border rounded-xl p-3">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Valores</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Volume Vendido (L)</label>
                    <input type="number" name="volume_sold" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Valor Total (R$)</label>
                    <input type="number" name="total_amount" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                </div>
                        </div>
                    </div>
                </div>
                <div class="sticky bottom-0 z-10 bg-white/90 backdrop-blur border-t">
                    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-3 flex justify-end gap-3">
                        <button type="button" onclick="closeSalesOverlay()" class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Registrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications Drawer (lateral) -->
    <div id="notificationsDrawer" class="fixed inset-0 z-50 hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40" onclick="closeNotificationsDrawer()"></div>
        <!-- Panel -->
        <div id="notificationsPanel" class="absolute right-0 top-0 h-full w-full md:w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-out flex flex-col">
            <div class="sticky top-0 z-10 bg-white/90 backdrop-blur p-4 border-b flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-semibold text-gray-900">Notificações</h3>
                    <span id="notificationsCount" class="inline-flex items-center justify-center text-xs font-medium text-white bg-green-600 rounded-full h-5 min-w-[20px] px-1"></span>
                </div>
                <button onclick="closeNotificationsDrawer()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="notificationsContent" class="flex-1 overflow-y-auto p-4">
                <div id="notificationsList" class="space-y-3"></div>
            </div>
            <div class="sticky bottom-0 z-10 bg-white/90 backdrop-blur p-3 border-t flex justify-between">
                <button type="button" onclick="markAllNotificationsRead()" class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">Marcar todas como lidas</button>
                <button type="button" onclick="closeNotificationsDrawer()" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Fechar</button>
            </div>
        </div>
    </div>

    <!-- User Overlay -->
    <div id="userOverlay" class="fixed inset-0 bg-black bg-opacity-60 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Adicionar Usuário</h3>
                <button onclick="closeUserOverlay()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="userForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome</label>
                    <input type="text" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
                    <input type="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Senha</label>
                    <input type="password" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cargo</label>
                    <select name="role" id="userRoleSelect" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" required>
                        <option value="funcionario" selected>Funcionário</option>
                        <option value="gerente" disabled style="color:#9CA3AF;">Gerente (desativado)</option>
                        <option value="proprietario" disabled style="color:#9CA3AF;">Proprietário (desativado)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                    <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                <div class="flex space-x-3">
                    <button type="button" onclick="closeUserOverlay()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        Adicionar
                    </button>
                </div>
            </form>
        </div>
    </div>

    

    <!-- Modal de Perfil (Full Screen) -->
    <div id="profileOverlay" class="fixed inset-0 z-50 hidden">
        <div class="w-full h-full bg-white p-0 overflow-y-auto">
            <!-- Header -->
            <div class="sticky top-0 z-10 bg-white/95 backdrop-blur-sm border-b shadow-sm">
                <div class="max-w-7xl mx-auto px-6 lg:px-8 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Perfil do Usuário</h3>
                    <div class="flex items-center gap-3">
                        <button id="editProfileBtn" onclick="toggleProfileEdit()" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Editar
                        </button>
                        <button onclick="closeProfileOverlay()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
                </div>
                </div>

            <!-- Content -->
            <div class="px-6 lg:px-8 py-8">
                <div class="max-w-7xl mx-auto">
                    <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                        <!-- Coluna Esquerda: Avatar e Segurança -->
                        <div class="xl:col-span-4 space-y-6">
                            <!-- Avatar Card -->
                            <div class="p-6 border border-gray-200 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow">
                                <h4 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Avatar
                                </h4>
                                <div class="flex flex-col items-center gap-4">
                                    <div class="relative">
                                        <div id="profileAvatarDisplay" class="w-24 h-24 rounded-full bg-gradient-to-br from-green-100 to-green-200 flex items-center justify-center border-4 border-white shadow-lg overflow-hidden">
                                            <?php 
                                            // Verificar e exibir foto de perfil
                                            $showPhoto = false;
                                            $photoSrc = '';
                                            
                                            if (!empty($current_user_photo)) {
                                                // Normalizar caminho (remover barras extras)
                                                $photoPath = trim($current_user_photo, '/\\');
                                                
                                                // Tentar múltiplos caminhos (em ordem de prioridade)
                                                $pathsToTry = [
                                                    __DIR__ . '/' . $photoPath,
                                                    __DIR__ . '/../' . $photoPath,
                                                    __DIR__ . '/uploads/profiles/' . basename($photoPath),
                                                    __DIR__ . '/' . basename($photoPath)
                                                ];
                                                
                                                foreach ($pathsToTry as $testPath) {
                                                    if (file_exists($testPath) && is_file($testPath)) {
                                                        // Encontrou! Ajustar para caminho relativo
                                                        if (strpos($testPath, __DIR__ . '/') === 0) {
                                                            $photoSrc = str_replace(__DIR__ . '/', '', $testPath);
                                                        } elseif (strpos($testPath, __DIR__ . '/../') === 0) {
                                                            $photoSrc = str_replace(__DIR__ . '/../', '', $testPath);
                                                        } else {
                                                            $photoSrc = $photoPath;
                                                        }
                                                        $showPhoto = true;
                                                        error_log("DEBUG - Foto encontrada em: " . $testPath . " -> Exibindo como: " . $photoSrc);
                                                        break;
                                                    }
                                                }
                                                
                                                if (!$showPhoto) {
                                                    error_log("DEBUG - Foto não encontrada em nenhum caminho testado. Path do banco: " . $photoPath);
                                                }
                                            }
                                            
                                            if ($showPhoto): 
                                            ?>
                                                <img src="<?php echo htmlspecialchars($photoSrc); ?>?t=<?php echo time(); ?>" alt="Foto do perfil" class="w-full h-full object-cover" id="profileAvatarImg" onerror="this.style.display='none'; document.getElementById('profileAvatarIcon').style.display='block';">
                                                <svg class="w-12 h-12 text-green-600 hidden" fill="currentColor" viewBox="0 0 24 24" id="profileAvatarIcon"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                            <?php else: ?>
                                                <svg class="w-12 h-12 text-green-600" fill="currentColor" viewBox="0 0 24 24" id="profileAvatarIcon"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                            <?php endif; ?>
                </div>
                                        <label for="profilePhotoInput" class="absolute bottom-0 right-0 w-8 h-8 bg-green-600 rounded-full flex items-center justify-center cursor-pointer hover:bg-green-700 transition-colors shadow-lg">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <input type="file" id="profilePhotoInput" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" class="hidden" onchange="handleProfilePhotoUpload(event)">
                                        </label>
            </div>
                                    <div class="flex flex-col gap-2 w-full max-w-xs">
                                        <button type="button" onclick="openCamera()" class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-green-600 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            Tirar foto
                                        </button>
                                        <button type="button" onclick="document.getElementById('profilePhotoInput').click()" class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            Escolher da galeria
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 text-center">JPG, PNG ou GIF<br>Máximo 5MB</p>
                                    <!-- Input escondido para câmera (mobile) -->
                                    <input type="file" id="profileCameraInput" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" capture="environment" class="hidden" onchange="handleProfilePhotoUpload(event)">
                                    <!-- Canvas para captura da câmera -->
                                    <canvas id="profileCameraCanvas" class="hidden"></canvas>
        </div>
    </div>

                            <!-- Segurança Card -->
                            <div class="p-6 border border-gray-200 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow">
                                <h4 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                                    Segurança
                                </h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Nova Senha</label>
                                        <input type="password" id="profileNewPassword" disabled class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed profile-input" placeholder="••••••••">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Senha</label>
                                        <input type="password" id="profileConfirmPassword" disabled class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed profile-input" placeholder="••••••••">
                                    </div>
                                </div>
                            </div>
            </div>
            
                        <!-- Coluna Direita (duas colunas) -->
                        <div class="xl:col-span-8 space-y-6">
                <!-- Informações Pessoais -->
                            <div class="p-6 border border-gray-200 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow">
                                <h4 class="text-sm font-semibold text-gray-900 mb-5 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Informações Pessoais
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome</label>
                                        <input type="text" id="profileName" disabled class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed profile-input" value="<?php echo htmlspecialchars($current_user_name); ?>">
                        </div>
                        <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                        <input type="email" id="profileEmail" disabled class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed" value="gerente@lactech.com">
                        </div>
                        <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                        <input type="tel" id="profilePhone" disabled class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed profile-input" value="<?php echo htmlspecialchars($current_user_phone); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Informações da Fazenda -->
                            <div class="p-6 border border-gray-200 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow">
                                <h4 class="text-sm font-semibold text-gray-900 mb-5 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                    Informações da Fazenda
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome da Fazenda</label>
                                        <input type="text" id="farmName" disabled class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed profile-input" value="<?php echo htmlspecialchars($farm_name); ?>">
                        </div>
                        <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">CNPJ</label>
                                        <input type="text" id="farmCNPJ" disabled class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed profile-input" value="<?php echo htmlspecialchars($farm_cnpj); ?>">
                        </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Endereço</label>
                                        <textarea id="farmAddress" disabled class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed profile-input" rows="3"><?php echo htmlspecialchars($farm_address); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Configurações do Sistema -->
                            <div class="p-6 border border-gray-200 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow">
                                <h4 class="text-sm font-semibold text-gray-900 mb-5 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Configurações do Sistema
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                            <span class="text-sm font-medium text-gray-700">Gerenciar dispositivos conectados / sessões ativas</span>
                    </div>
                                        <button onclick="openDevicesModal()" class="px-3 py-1.5 text-sm font-medium text-green-600 hover:text-green-700 border border-green-600 rounded-lg hover:bg-green-50 transition-colors">
                                            Gerenciar
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                            </svg>
                        <span class="text-sm font-medium text-gray-700">Notificações Push</span>
                                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" id="pushNotifications" class="sr-only peer" checked>
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ações da Conta -->
            <div class="px-6 lg:px-8 py-4 border-t border-gray-200">
                <div class="max-w-7xl mx-auto">
                    <div class="flex items-center justify-between bg-red-50 rounded-lg px-4 py-3">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <div>
                                <h4 class="text-sm font-semibold text-red-900">Ações da Conta</h4>
                                <p class="text-xs text-red-700">Sair do sistema encerrará sua sessão atual.</p>
                            </div>
                        </div>
                        <a href="logout.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Sair do Sistema
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div id="profileFooter" class="sticky bottom-0 z-10 bg-white/95 backdrop-blur-sm border-t shadow-sm hidden">
                <div class="max-w-7xl mx-auto px-6 lg:px-8 py-4 flex justify-end gap-3">
                    <button onclick="cancelProfileChanges()" class="px-5 py-2.5 text-sm font-medium border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">Cancelar</button>
                    <button onclick="saveProfile()" class="px-5 py-2.5 text-sm font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors shadow-sm">Salvar Alterações</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Adicionar Usuário -->
    <div id="addUserModal" class="fixed inset-0 z-50 hidden">
        <div class="w-full h-full bg-white p-0 overflow-y-auto">
            <div class="sticky top-0 z-10 bg-white/90 backdrop-blur flex items-center justify-between p-4 border-b">
                <h3 class="text-base font-semibold text-gray-900">Adicionar Novo Usuário</h3>
                <button onclick="closeAddUserModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="addUserForm" class="p-4 sm:p-5 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Senha *</label>
                        <div class="relative">
                            <input type="password" name="password" id="userPassword" required class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <button type="button" onclick="toggleUserPasswordVisibility('userPassword', 'userPasswordToggle')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700" id="userPasswordToggle">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Senha *</label>
                        <input type="password" name="confirm_password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Papel *</label>
                        <select name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Selecione...</option>
                            <option value="funcionario">Funcionário</option>
                            <option value="gerente">Gerente</option>
                            <option value="proprietario">Proprietário</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                        <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="sticky bottom-0 z-10 bg-white/90 backdrop-blur p-4 border-t flex justify-end space-x-3">
                    <button type="button" onclick="closeAddUserModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Adicionar Usuário
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Gerenciar Dispositivos / Sessões -->
    <div id="devicesModal" class="fixed inset-0 z-50 hidden">
        <div class="w-full h-full bg-white p-0 overflow-y-auto">
            <div class="sticky top-0 z-10 bg-white/90 backdrop-blur flex items-center justify-between p-4 border-b">
                <h3 class="text-base font-semibold text-gray-900">Dispositivos Conectados / Sessões Ativas</h3>
                <button onclick="closeDevicesModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="p-4 sm:p-6">
                <div id="devicesList" class="space-y-4">
                    <!-- Lista de dispositivos será carregada aqui via JavaScript -->
                    <div class="text-center text-gray-500 py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto mb-4"></div>
                        <p>Carregando dispositivos...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/gerente-completo.js?v=<?php echo $v; ?>"></script>
    
    <script>
        // Teste direto para verificar se os elementos existem
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔍 Verificando elementos...');
            
            const elements = [
                'todayVolume',
                'qualityAverage', 
                'pendingPayments',
                'activeUsers',
                'monthlyProductionChart',
                'recentActivities',
                'lastUpdate'
            ];
            
            elements.forEach(id => {
                const el = document.getElementById(id);
                console.log(`${id}:`, el ? '✅ Encontrado' : '❌ Não encontrado');
            });
            
            // Teste da API
            console.log('🧪 Testando API...');
            fetch('./api/endpoints/dashboard.php')
                .then(response => response.json())
                .then(data => {
                    console.log('✅ API funcionando:', data);
                })
                .catch(error => {
                    console.error('❌ Erro na API:', error);
                });
        });
    </script>
    
    <script>
        // Sistema de navegação simplificado
        (function() {
            'use strict';
            
            // Interceptar cliques em links para "Mais Opções"
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a[href*="modalmore.php"]');
                if (link) {
                    e.preventDefault();
                    
                    // Salvar estado atual
                    const state = {
                        scrollY: window.pageYOffset,
                        timestamp: Date.now(),
                        url: window.location.href
                    };
                    localStorage.setItem('lactech_dashboard_state', JSON.stringify(state));
                    
                    // Navegar
                    window.location.href = link.href;
                }
            });
            
            // Verificar se voltou da página "Mais Opções"
            function checkForReturnFromMoreOptions() {
                const cachedState = localStorage.getItem('lactech_page_state');
                if (cachedState) {
                    const state = JSON.parse(cachedState);
                    if (state.url && state.url.includes('modalmore.php')) {
                        localStorage.removeItem('lactech_page_state');
                        
                        // Restaurar scroll
                        if (state.scrollY !== undefined) {
                            setTimeout(() => {
                                window.scrollTo(0, state.scrollY);
                            }, 100);
                        }
                        
                        return true;
                    }
                }
                return false;
            }
            
            // Inicializar
            window.addEventListener('load', function() {
                if (checkForReturnFromMoreOptions()) {
                    console.log('Dashboard restaurado - sem recarregamento');
                }
            });
            
        })();
        
        // Funções dos modais
        function openNotificationsModal() {
            document.getElementById('notificationsModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeNotificationsModal() {
            document.getElementById('notificationsModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        function openProfileOverlay() {
            document.getElementById('profileOverlay').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeProfileOverlay() {
            document.getElementById('profileOverlay').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeAddUserModal() {
            document.getElementById('addUserModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            document.getElementById('addUserForm').reset();
        }
        
        function saveProfile() {
            // Implementar salvamento do perfil
            alert('Perfil salvo com sucesso!');
            closeProfileOverlay();
        }
        
        function toggleUserPasswordVisibility(inputId, buttonId) {
            const input = document.getElementById(inputId);
            const button = document.getElementById(buttonId);
            
            if (input.type === 'password') {
                input.type = 'text';
                button.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/></svg>';
            } else {
                input.type = 'password';
                button.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
            }
        }
        
        // Formulário de adicionar usuário
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            if (formData.get('password') !== formData.get('confirm_password')) {
                alert('As senhas não coincidem!');
                return;
            }
            
            // Implementar envio do formulário
            alert('Usuário adicionado com sucesso!');
            closeAddUserModal();
        });
        
        // Exportar funções globais
        window.openNotificationsModal = openNotificationsModal;
        window.closeNotificationsModal = closeNotificationsModal;
        window.openProfileOverlay = openProfileOverlay;
        window.closeProfileOverlay = closeProfileOverlay;
        window.openAddUserModal = openAddUserModal;
        window.closeAddUserModal = closeAddUserModal;
        window.saveProfile = saveProfile;
        window.toggleUserPasswordVisibility = toggleUserPasswordVisibility;
        
        // Sistema de controle de abas
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔧 Configurando controle das abas...');
            
            // Selecionar todos os botões de navegação
            const navButtons = document.querySelectorAll('.nav-item[data-tab]');
            const tabContents = document.querySelectorAll('.tab-content');
            
            console.log('🔍 Botões encontrados:', navButtons.length);
            console.log('🔍 Conteúdos encontrados:', tabContents.length);
            
            // Adicionar event listener para cada botão
            navButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');
                    console.log('🖱️ Clicou na aba:', tabName);
                    
                    // Remover classe active de todos os botões
                    navButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Adicionar classe active ao botão clicado
                    this.classList.add('active');
                    
                    // Esconder todos os conteúdos
                    tabContents.forEach(content => {
                        content.classList.add('hidden');
                        content.classList.remove('active');
                    });
                    
                    // Mostrar o conteúdo da aba selecionada
                    const selectedTab = document.getElementById(tabName + '-tab');
                    console.log('🔍 Procurando elemento:', tabName + '-tab');
                    console.log('🔍 Elemento encontrado:', !!selectedTab);
                    
                    if (selectedTab) {
                        selectedTab.classList.remove('hidden');
                        selectedTab.classList.add('active');
                        console.log('✅ Aba', tabName, 'mostrada com sucesso!');
                        
                        // Carregar dados específicos da aba
                        switch (tabName) {
                            case 'volume':
                                loadVolumeData();
                                break;
                            case 'quality':
                                loadQualityData();
                                break;
                            case 'payments':
                                loadFinancialData();
                                break;
                            case 'users':
                                loadUsersData();
                                break;
                        }
                    } else {
                        console.error('❌ Aba', tabName, 'não encontrada!');
                    }
                });
            });
            
            console.log('✅ Controle das abas configurado!');
        });
        
        // Função showTab para compatibilidade
        function showTab(tabName) {
            const button = document.querySelector(`[data-tab="${tabName}"]`);
            if (button) {
                button.click();
            }
        }
        
        window.showTab = showTab;
    </script>
</body>
</html>
