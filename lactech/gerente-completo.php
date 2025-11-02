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
    // Usar prepared statement para maior segurança
    $userData = $db->query("SELECT profile_photo, phone FROM users WHERE id = ?", [(int)$current_user_id]);
    
    // Verificar se os dados foram retornados antes de acessar
    if (!empty($userData) && isset($userData[0])) {
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
    } else {
        $current_user_photo = null;
        $current_user_phone = '(11) 99999-9999';
    }
    
    // Buscar dados da fazenda usando prepared statement
    $farmData = $db->query("SELECT name, cnpj, address FROM farms WHERE id = ?", [1]);
    
    // Verificar se os dados foram retornados antes de acessar
    if (!empty($farmData) && isset($farmData[0])) {
        $farm_name = $farmData[0]['name'] ?? 'Lagoa Do Mato';
        $farm_cnpj = $farmData[0]['cnpj'] ?? '12.345.678/0001-90';
        $farm_address = $farmData[0]['address'] ?? 'Fazenda Lagoa Do Mato, Zona Rural, São Paulo - SP';
    } else {
        $farm_name = 'Lagoa Do Mato';
        $farm_cnpj = '12.345.678/0001-90';
        $farm_address = 'Fazenda Lagoa Do Mato, Zona Rural, São Paulo - SP';
    }
} catch (Exception $e) {
    error_log("Erro ao buscar dados do usuário/fazenda: " . $e->getMessage());
    $current_user_photo = null;
    $current_user_phone = '(11) 99999-9999';
    $farm_name = 'Lagoa Do Mato';
    $farm_cnpj = '12.345.678/0001-90';
    $farm_address = 'Fazenda Lagoa Do Mato, Zona Rural, São Paulo - SP';
}

       // Buscar dados para o modal Mais Opções
       try {
           $db = Database::getInstance();
           
           // Buscar dados dos animais com cálculo de idade em meses
           $animals_raw = $db->getAllAnimals();
           // Adicionar age_months e processar dados
           $more_options_animals = array_map(function($animal) {
               $age_days = $animal['age_days'] ?? 0;
               $animal['age_months'] = floor($age_days / 30);
               return $animal;
           }, $animals_raw);
    
    // Buscar dados de produção de leite (últimos 7 dias)
    $more_options_milk_data = $db->query("
        SELECT 
            DATE(production_date) as date,
            SUM(volume) as daily_volume,
            AVG(fat_content) as avg_fat,
            AVG(protein_content) as avg_protein,
            AVG(somatic_cells) as avg_somatic_cells
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(production_date)
        ORDER BY date DESC
        LIMIT 7
    ");
    
    // Calcular estatísticas gerais
    $more_options_total_animals = count($more_options_animals);
    $more_options_lactating_cows = count(array_filter($more_options_animals, function($a) { 
        return ($a['status'] ?? '') === 'Lactante'; 
    }));
    $more_options_pregnant_cows = count(array_filter($more_options_animals, function($a) { 
        return ($a['reproductive_status'] ?? '') === 'prenha'; 
    }));
    
    // Calcular produção total dos últimos 7 dias
    $more_options_production_result = $db->query("
        SELECT SUM(volume) as total_volume
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $more_options_total_production = $more_options_production_result[0]['total_volume'] ?? 0;
    $more_options_avg_daily_production = $more_options_total_production / 7;
    
} catch (Exception $e) {
    error_log("Erro ao buscar dados para Mais Opções: " . $e->getMessage());
    $more_options_animals = [];
    $more_options_milk_data = [];
    $more_options_total_animals = 0;
    $more_options_lactating_cows = 0;
    $more_options_pregnant_cows = 0;
    $more_options_total_production = 0;
    $more_options_avg_daily_production = 0;
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
        
        /* Bottom Navigation Bar (Mobile) */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 40;
            background: linear-gradient(135deg, rgba(31, 122, 90, 0.75), rgba(26, 98, 73, 0.75));
            border-top-left-radius: 1.5rem;
            border-top-right-radius: 1.5rem;
            box-shadow: 0 -8px 20px rgba(0, 0, 0, 0.15), 0 -2px 8px rgba(0, 0, 0, 0.1);
            padding-bottom: env(safe-area-inset-bottom);
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), 
                        opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 1;
        }
        
        .bottom-nav.hidden {
            transform: translateY(100%);
            opacity: 0;
            pointer-events: none;
        }
        
        .bottom-nav-container {
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding: 0.5rem 0.5rem 0.625rem;
            gap: 0.125rem;
            max-width: 100%;
        }
        
        .bottom-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.4rem 0.125rem;
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: pointer;
            min-height: 60px;
            border-radius: 0.75rem;
            position: relative;
            text-decoration: none;
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        
        /* Animação em cascata - entrada */
        .bottom-nav .bottom-nav-item {
            animation: cascadeIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            opacity: 0;
        }
        
        .bottom-nav .bottom-nav-item:nth-child(1) { animation-delay: 0s; }
        .bottom-nav .bottom-nav-item:nth-child(2) { animation-delay: 0.08s; }
        .bottom-nav .bottom-nav-item:nth-child(3) { animation-delay: 0.16s; }
        .bottom-nav .bottom-nav-item:nth-child(4) { animation-delay: 0.24s; }
        .bottom-nav .bottom-nav-item:nth-child(5) { animation-delay: 0.32s; }
        .bottom-nav .bottom-nav-item:nth-child(6) { animation-delay: 0.4s; }
        
        /* Animação em cascata - saída (ordem reversa) */
        .bottom-nav.hidden .bottom-nav-item {
            animation: cascadeOut 0.6s cubic-bezier(0.55, 0.055, 0.675, 0.19) forwards !important;
        }
        
        .bottom-nav.hidden .bottom-nav-item:nth-child(1) { animation-delay: 0.25s !important; }
        .bottom-nav.hidden .bottom-nav-item:nth-child(2) { animation-delay: 0.2s !important; }
        .bottom-nav.hidden .bottom-nav-item:nth-child(3) { animation-delay: 0.15s !important; }
        .bottom-nav.hidden .bottom-nav-item:nth-child(4) { animation-delay: 0.1s !important; }
        .bottom-nav.hidden .bottom-nav-item:nth-child(5) { animation-delay: 0.05s !important; }
        .bottom-nav.hidden .bottom-nav-item:nth-child(6) { animation-delay: 0s !important; }
        
        @keyframes cascadeIn {
            0% {
                transform: translateY(40px) scale(0.6) rotate(-5deg);
                opacity: 0;
                filter: blur(4px);
            }
            50% {
                transform: translateY(-8px) scale(1.08) rotate(2deg);
                opacity: 0.8;
                filter: blur(1px);
            }
            75% {
                transform: translateY(2px) scale(0.98) rotate(-1deg);
                opacity: 0.95;
            }
            100% {
                transform: translateY(0) scale(1) rotate(0deg);
                opacity: 1;
                filter: blur(0);
            }
        }
        
        @keyframes cascadeOut {
            0% {
                transform: translateY(0) scale(1) rotate(0deg);
                opacity: 1;
                filter: blur(0);
            }
            50% {
                transform: translateY(10px) scale(0.95) rotate(3deg);
                opacity: 0.5;
                filter: blur(2px);
            }
            100% {
                transform: translateY(30px) scale(0.7) rotate(5deg);
                opacity: 0;
                filter: blur(6px);
            }
        }
        
        /* Animações para modais */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
        }
        
        .animate-slideUp {
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .bottom-nav-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 0 0 3px 3px;
            transition: width 0.3s ease;
        }
        
        .bottom-nav-item.active {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .bottom-nav-item.active::before {
            width: 40px;
        }
        
        .bottom-nav-item:active {
            transform: scale(0.92);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .bottom-nav-item:hover {
            color: rgba(255, 255, 255, 0.9);
            background: rgba(255, 255, 255, 0.05);
        }
        
        .bottom-nav-icon {
            width: 21px;
            height: 21px;
            margin-bottom: 0.375rem;
            stroke-width: 2;
            transition: transform 0.3s ease;
        }
        
        /* Ícone do financeiro mantém tamanho maior */
        .bottom-nav-item[data-tab="payments"] .bottom-nav-icon,
        .bottom-nav-item[data-tab="payments"] svg.bottom-nav-icon {
            width: 26px;
            height: 26px;
        }
        
        .bottom-nav-item.active .bottom-nav-icon {
            transform: scale(1.1);
        }
        
        .bottom-nav-label {
            font-size: 0.625rem;
            font-weight: 600;
            text-align: center;
            line-height: 1.1;
            letter-spacing: 0.01em;
            transition: font-weight 0.3s ease;
            white-space: nowrap;
        }
        
        .bottom-nav-item.active .bottom-nav-label {
            font-weight: 700;
        }
        
        /* Botão Mais especial */
        .bottom-nav-item.more-item {
            position: relative;
        }
        
        .bottom-nav-item.more-item::after {
            content: '';
            position: absolute;
            top: -2px;
            right: -2px;
            width: 8px;
            height: 8px;
            background: #fbbf24;
            border-radius: 50%;
            border: 2px solid var(--forest-600);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        /* Espaçamento para o menu inferior em mobile */
        @media (max-width: 767px) {
            main {
                padding-bottom: 75px;
            }
        }
        
        /* Estilos para tela de carregamento */
        #loadingMessage {
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .loading-dot {
            animation: bounce 1.4s infinite ease-in-out;
        }
        
        .loading-dot:nth-child(1) {
            animation-delay: 0s;
        }
        
        .loading-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .loading-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
    </style>
</head>
<body class="bg-gray-50 font-inter">
    <!-- Tela de Carregamento -->
    <div id="loadingScreen" class="fixed inset-0 z-[9999] bg-gradient-to-br from-green-50 to-blue-50 flex items-center justify-center">
        <div class="flex flex-col items-center justify-center space-y-8">
            <!-- Círculo de carregamento -->
            <div class="relative">
                <div class="w-24 h-24 border-8 border-green-100 border-t-green-600 rounded-full animate-spin"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Mensagens rotativas -->
            <div class="text-center space-y-4">
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Carregando...</h2>
                <p id="loadingMessage" class="text-lg text-gray-600 font-medium min-h-[32px] transition-all duration-500">
                    Preparando tudo para você! 🚀
                </p>
                <div class="flex items-center justify-center space-x-2 mt-4">
                    <div class="w-2 h-2 bg-green-600 rounded-full animate-bounce" style="animation-delay: 0s;"></div>
                    <div class="w-2 h-2 bg-green-600 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                    <div class="w-2 h-2 bg-green-600 rounded-full animate-bounce" style="animation-delay: 0.4s;"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Header -->
    <header class="gradient-forest text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo e Título -->
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center p-2">
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
                    <button onclick="openMoreOptionsModal()" class="px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 12.5c0 .828-.672 1.5-1.5 1.5s-1.5-.672-1.5-1.5.672-1.5 1.5-1.5 1.5.672 1.5 1.5zM18.5 12.5c0 .828-.672 1.5-1.5 1.5s-1.5-.672-1.5-1.5.672-1.5 1.5-1.5 1.5.672 1.5 1.5zM12 20c-2.5 0-4.5-2-4.5-4.5S9.5 11 12 11s4.5 2 4.5 4.5S14.5 20 12 20zM12 8c-1.5 0-2.5-1-2.5-2.5S10.5 3 12 3s2.5 1 2.5 2.5S13.5 8 12 8z"/>
                        </svg>
                        <span>MAIS</span>
                    </button>
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
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Volume Total (L)</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Animais</th>
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

    <!-- Bottom Navigation Bar (Mobile Only) -->
    <nav class="bottom-nav md:hidden">
        <div class="bottom-nav-container">
            <button class="bottom-nav-item active" data-tab="dashboard" onclick="switchBottomTab('dashboard')">
                <svg class="bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="bottom-nav-label">Dashboard</span>
            </button>
            <button class="bottom-nav-item" data-tab="volume" onclick="switchBottomTab('volume')">
                <svg class="bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                <span class="bottom-nav-label">Volume</span>
            </button>
            <button class="bottom-nav-item" data-tab="quality" onclick="switchBottomTab('quality')">
                <svg class="bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="bottom-nav-label">Qualidade</span>
            </button>
            <button class="bottom-nav-item" data-tab="payments" onclick="switchBottomTab('payments')">
                <svg class="bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                </svg>
                <span class="bottom-nav-label">Financeiro</span>
            </button>
            <button class="bottom-nav-item" data-tab="users" onclick="switchBottomTab('users')">
                <svg class="bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="bottom-nav-label">Usuários</span>
            </button>
            <button onclick="openMoreOptionsModal()" class="bottom-nav-item more-item">
                <svg class="bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                </svg>
                <span class="bottom-nav-label">Mais</span>
            </button>
        </div>
    </nav>

    <!-- Overlays -->
    <!-- Modal Volume Geral - Melhorado -->
    <div id="generalVolumeOverlay" class="fixed inset-0 z-50 hidden animate-fadeIn">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeGeneralVolumeOverlay()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden pointer-events-auto animate-slideUp">
                <!-- Header -->
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13l8 0c1.11 0 2.08-.402 2.599-1M21 13l-8 0c-1.11 0-2.08-.402-2.599-1M16 8V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v3m4 6h.01M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Registrar Volume Geral</h3>
                            <p class="text-sm text-white/90">Registro de produção total</p>
                        </div>
                    </div>
                    <button onclick="closeGeneralVolumeOverlay()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form id="generalVolumeForm" class="overflow-y-auto max-h-[calc(90vh-200px)]">
                    <div class="p-6 space-y-6">
                        <!-- Mensagem de sucesso/erro -->
                        <div id="generalVolumeMessage" class="hidden p-4 rounded-xl border"></div>

                        <!-- Informações da Coleta -->
                        <div class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded-xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <h4 class="text-base font-bold text-slate-800">Informações da Coleta</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            Data da Coleta
                                        </span>
                                    </label>
                                    <input type="date" name="collection_date" class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Período
                                        </span>
                                    </label>
                                    <select name="period" class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white" required>
                                        <option value="manha">Manhã</option>
                                        <option value="tarde">Tarde</option>
                                        <option value="noite">Noite</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Medições -->
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <h4 class="text-base font-bold text-slate-800">Medições</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            Número de Vacas
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="total_animals" id="totalAnimalsInput" step="1" min="1" placeholder="0" class="w-full px-4 py-3 pl-12 border-2 border-green-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white font-semibold text-green-700" required>
                                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">Quantas vacas participaram desta coleta?</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                            Volume Total
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="total_volume" id="totalVolumeInput" step="0.1" min="0" placeholder="0.0" class="w-full px-4 py-3 pl-12 border-2 border-green-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white font-semibold text-green-700" required>
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-green-600 font-bold">L</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1" id="averagePerCowDisplay">Média por vaca: -- L</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1V9a5 5 0 00-10 0v6a4 4 0 004 4zm0-10a2 2 0 112 2"/>
                                            </svg>
                                            Temperatura
                                            <span class="text-xs font-normal text-slate-500">(opcional)</span>
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="temperature" step="0.1" placeholder="0.0" class="w-full px-4 py-3 pl-12 border-2 border-green-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white font-semibold text-green-700">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-green-600 font-bold">°C</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="sticky bottom-0 bg-white border-t border-slate-200 px-6 py-4 flex justify-end gap-3">
                        <button type="button" onclick="closeGeneralVolumeOverlay()" class="px-6 py-3 text-sm font-semibold border-2 border-slate-300 text-slate-700 rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-3 text-sm font-semibold bg-gradient-to-r from-green-600 to-emerald-700 text-white rounded-xl hover:from-green-700 hover:to-emerald-800 transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Registrar Volume
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Volume por Vaca - Melhorado -->
    <div id="volumeOverlay" class="fixed inset-0 z-50 hidden animate-fadeIn">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeVolumeOverlay()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden pointer-events-auto animate-slideUp">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5zM9 16c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm6 0c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Registrar Volume por Vaca</h3>
                            <p class="text-sm text-white/90">Produção individual do animal</p>
                        </div>
                    </div>
                    <button onclick="closeVolumeOverlay()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form id="volumeForm" class="overflow-y-auto max-h-[calc(90vh-200px)]">
                    <div class="p-6 space-y-6">
                        <!-- Mensagem de sucesso/erro -->
                        <div id="volumeMessage" class="hidden p-4 rounded-xl border"></div>

                        <!-- Identificação -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <h4 class="text-base font-bold text-slate-800">Identificação do Animal</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5z"/>
                                            </svg>
                                            Selecionar Vaca
                                        </span>
                                    </label>
                                    <select name="animal_id" id="volumeAnimalSelect" class="w-full px-4 py-3 border-2 border-blue-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white" required>
                                        <option value="">Selecione uma vaca</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            Data da Coleta
                                        </span>
                                    </label>
                                    <input type="date" name="collection_date" class="w-full px-4 py-3 border-2 border-blue-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white" required>
                                </div>
                            </div>
                        </div>

                        <!-- Medições -->
                        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 border-2 border-indigo-200 rounded-xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <h4 class="text-base font-bold text-slate-800">Medições de Produção</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Período
                                        </span>
                                    </label>
                                    <select name="period" class="w-full px-4 py-3 border-2 border-indigo-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white" required>
                                        <option value="manha">Manhã</option>
                                        <option value="tarde">Tarde</option>
                                        <option value="noite">Noite</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                            Volume
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="volume" step="0.1" min="0" placeholder="0.0" class="w-full px-4 py-3 pl-12 border-2 border-indigo-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white font-semibold text-indigo-700" required>
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-indigo-600 font-bold">L</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1V9a5 5 0 00-10 0v6a4 4 0 004 4zm0-10a2 2 0 112 2"/>
                                            </svg>
                                            Temperatura
                                            <span class="text-xs font-normal text-slate-500">(opcional)</span>
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="temperature" step="0.1" placeholder="0.0" class="w-full px-4 py-3 pl-12 border-2 border-indigo-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white font-semibold text-indigo-700">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-indigo-600 font-bold">°C</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="sticky bottom-0 bg-white border-t border-slate-200 px-6 py-4 flex justify-end gap-3">
                        <button type="button" onclick="closeVolumeOverlay()" class="px-6 py-3 text-sm font-semibold border-2 border-slate-300 text-slate-700 rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-3 text-sm font-semibold bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-xl hover:from-blue-700 hover:to-indigo-800 transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Registrar Volume
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Teste de Qualidade - Melhorado -->
    <div id="qualityOverlay" class="fixed inset-0 z-50 hidden animate-fadeIn">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeQualityOverlay()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden pointer-events-auto animate-slideUp">
                <!-- Header -->
                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Registrar Teste de Qualidade</h3>
                            <p class="text-sm text-white/90">Análise laboratorial do leite</p>
                        </div>
                    </div>
                    <button onclick="closeQualityOverlay()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form id="qualityForm" class="overflow-y-auto max-h-[calc(90vh-200px)]">
                    <div class="p-6 space-y-6">
                        <!-- Mensagem de sucesso/erro -->
                        <div id="qualityMessage" class="hidden p-4 rounded-xl border"></div>

                        <!-- Dados do Teste -->
                        <div class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded-xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <h4 class="text-base font-bold text-slate-800">Dados do Teste</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            Data do Teste
                                        </span>
                                    </label>
                                    <input type="date" name="test_date" class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all bg-white" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                            Laboratório
                                            <span class="text-xs font-normal text-slate-500">(opcional)</span>
                                        </span>
                                    </label>
                                    <input type="text" name="laboratory" placeholder="Nome do laboratório" class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all bg-white">
                                </div>
                            </div>
                        </div>

                        <!-- Resultados -->
                        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 border-2 border-emerald-200 rounded-xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <h4 class="text-base font-bold text-slate-800">Resultados da Análise</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                                            </svg>
                                            Gordura
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="fat_content" step="0.01" min="0" max="100" placeholder="0.00" class="w-full px-4 py-3 pl-12 border-2 border-emerald-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all bg-white font-semibold text-emerald-700">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 font-bold">%</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                            </svg>
                                            Proteína
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="protein_content" step="0.01" min="0" max="100" placeholder="0.00" class="w-full px-4 py-3 pl-12 border-2 border-emerald-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all bg-white font-semibold text-emerald-700">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 font-bold">%</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                            Células Somáticas
                                        </span>
                                    </label>
                                    <input type="number" name="somatic_cells" min="0" placeholder="0" class="w-full px-4 py-3 border-2 border-emerald-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all bg-white font-semibold text-emerald-700">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="sticky bottom-0 bg-white border-t border-slate-200 px-6 py-4 flex justify-end gap-3">
                        <button type="button" onclick="closeQualityOverlay()" class="px-6 py-3 text-sm font-semibold border-2 border-slate-300 text-slate-700 rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-3 text-sm font-semibold bg-gradient-to-r from-emerald-600 to-teal-700 text-white rounded-xl hover:from-emerald-700 hover:to-teal-800 transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Registrar Teste
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Registrar Venda - Melhorado -->
    <div id="salesOverlay" class="fixed inset-0 z-50 hidden animate-fadeIn">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeSalesOverlay()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden pointer-events-auto animate-slideUp">
                <!-- Header -->
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Registrar Venda</h3>
                            <p class="text-sm text-white/90">Registro de comercialização</p>
                        </div>
                    </div>
                    <button onclick="closeSalesOverlay()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form id="salesForm" class="overflow-y-auto max-h-[calc(90vh-200px)]">
                    <div class="p-6 space-y-6">
                        <!-- Mensagem de sucesso/erro -->
                        <div id="salesMessage" class="hidden p-4 rounded-xl border"></div>

                        <!-- Detalhes da Venda -->
                        <div class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded-xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <h4 class="text-base font-bold text-slate-800">Detalhes da Venda</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            Data da Venda
                                        </span>
                                    </label>
                                    <input type="date" name="sale_date" class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            Cliente
                                        </span>
                                    </label>
                                    <input type="text" name="customer" placeholder="Nome do cliente" class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white" required>
                                </div>
                            </div>
                        </div>

                        <!-- Valores -->
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h4 class="text-base font-bold text-slate-800">Valores</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                            Volume Vendido
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="volume_sold" step="0.1" min="0" placeholder="0.0" class="w-full px-4 py-3 pl-12 border-2 border-green-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white font-semibold text-green-700" required>
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-green-600 font-bold">L</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Valor Total
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="total_amount" step="0.01" min="0" placeholder="0.00" class="w-full px-4 py-3 pl-10 border-2 border-green-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white font-semibold text-green-700" required>
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-green-600 font-bold">R$</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="sticky bottom-0 bg-white border-t border-slate-200 px-6 py-4 flex justify-end gap-3">
                        <button type="button" onclick="closeSalesOverlay()" class="px-6 py-3 text-sm font-semibold border-2 border-slate-300 text-slate-700 rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-3 text-sm font-semibold bg-gradient-to-r from-green-600 to-emerald-700 text-white rounded-xl hover:from-green-700 hover:to-emerald-800 transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Registrar Venda
                        </button>
                    </div>
                </form>
            </div>
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
                        <button onclick="openLogoutModal()" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Sair do Sistema
                        </button>
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
    <!-- Modal Adicionar Novo Usuário - Melhorado -->
    <div id="addUserModal" class="fixed inset-0 z-50 hidden animate-fadeIn">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeAddUserModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden pointer-events-auto animate-slideUp">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Adicionar Novo Usuário</h3>
                            <p class="text-sm text-white/90">Criar nova conta no sistema</p>
                        </div>
                    </div>
                    <button onclick="closeAddUserModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form id="addUserForm" class="overflow-y-auto max-h-[calc(90vh-200px)]">
                    <div class="p-6 space-y-6">
                        <!-- Mensagem de sucesso/erro -->
                        <div id="addUserMessage" class="hidden p-4 rounded-xl border"></div>

                        <!-- Informações Pessoais -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <h4 class="text-base font-bold text-slate-800">Informações Pessoais</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            Nome Completo
                                        </span>
                                    </label>
                                    <input type="text" name="name" required placeholder="Nome completo do usuário" class="w-full px-4 py-3 border-2 border-blue-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                            Email
                                        </span>
                                    </label>
                                    <input type="email" name="email" required placeholder="usuario@email.com" class="w-full px-4 py-3 border-2 border-blue-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        Telefone
                                        <span class="text-xs font-normal text-slate-500">(opcional)</span>
                                    </span>
                                </label>
                                <input type="tel" name="phone" placeholder="(00) 00000-0000" class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white">
                            </div>
                        </div>

                        <!-- Credenciais -->
                        <div class="bg-gradient-to-br from-slate-50 to-slate-100 border-2 border-slate-200 rounded-xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <h4 class="text-base font-bold text-slate-800">Credenciais de Acesso</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            Senha
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <input type="password" name="password" id="userPassword" required placeholder="Mínimo 6 caracteres" class="w-full px-4 py-3 pr-12 border-2 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white">
                                        <button type="button" onclick="toggleUserPasswordVisibility('userPassword', 'userPasswordToggle')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-500 hover:text-slate-700 transition-colors" id="userPasswordToggle">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                            Confirmar Senha
                                        </span>
                                    </label>
                                    <input type="password" name="confirm_password" required placeholder="Digite a senha novamente" class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white">
                                </div>
                            </div>
                        </div>

                        <!-- Permissões -->
                        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 border-2 border-indigo-200 rounded-xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <h4 class="text-base font-bold text-slate-800">Permissões e Papel</h4>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        Papel no Sistema
                                    </span>
                                </label>
                                <select name="role" required class="w-full px-4 py-3 border-2 border-indigo-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white">
                                    <option value="">Selecione o papel...</option>
                                    <option value="funcionario">Funcionário</option>
                                    <option value="gerente">Gerente</option>
                                    <option value="proprietario">Proprietário</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="sticky bottom-0 bg-white border-t border-slate-200 px-6 py-4 flex justify-end gap-3">
                        <button type="button" onclick="closeAddUserModal()" class="px-6 py-3 text-sm font-semibold border-2 border-slate-300 text-slate-700 rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-3 text-sm font-semibold bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-xl hover:from-blue-700 hover:to-indigo-800 transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Adicionar Usuário
                        </button>
                    </div>
                </form>
            </div>
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

    <!-- Modal de Confirmação de Logout -->
    <div id="logoutModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeLogoutModal()"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
            <!-- Ícone de alerta -->
            <div class="flex justify-center mb-4">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </div>
            </div>
            
            <!-- Título e Mensagem -->
            <div class="text-center mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-2">Confirmar Saída</h3>
                <p class="text-gray-600 text-sm">Tem certeza que deseja sair do sistema? Sua sessão será encerrada.</p>
            </div>
            
            <!-- Botões -->
            <div class="flex gap-3">
                <button onclick="closeLogoutModal()" class="flex-1 px-4 py-2.5 text-sm font-medium border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
                <a href="logout.php" class="flex-1 px-4 py-2.5 text-sm font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-center">
                    Sim, Sair
                </a>
            </div>
        </div>
    </div>

    <!-- Modal Ver Pedigree - Full Screen -->
    <div id="pedigreeModal" class="fixed inset-0 z-[110] hidden">
        <!-- Header Fixo -->
        <div class="absolute top-0 left-0 right-0 h-20 bg-gradient-to-r from-blue-600 to-blue-700 shadow-lg z-10 flex items-center justify-between px-6">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-white" id="pedigreeTitle">Pedigree do Animal</h2>
                    <p class="text-blue-100 text-sm" id="pedigreeSubtitle">Árvore genealógica completa</p>
                </div>
            </div>
            <button onclick="closePedigreeModal()" class="w-12 h-12 flex items-center justify-center bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl transition-all text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Conteúdo Principal (Full Screen) -->
        <div id="pedigreeContent" class="absolute inset-0 pt-20 overflow-auto bg-gradient-to-br from-gray-50 to-gray-100 p-8">
            <!-- Árvore Genealógica será renderizada aqui -->
        </div>
    </div>

    <!-- Modal Informações do Animal no Pedigree -->
    <div id="animalPedigreeInfoModal" class="fixed inset-0 z-[120] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeAnimalPedigreeInfoModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-blue-500 to-blue-600">
                <div class="flex items-center space-x-3">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-xl font-bold text-white" id="animalInfoTitle">Informações do Animal</h3>
                </div>
                <button onclick="closeAnimalPedigreeInfoModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="animalInfoContent" class="flex-1 overflow-y-auto p-6">
                <!-- Carregando -->
                <div id="animalInfoLoading" class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                </div>
                <!-- Conteúdo será preenchido aqui -->
            </div>
        </div>
    </div>

    <!-- Modal Editar Animal -->
    <div id="editAnimalModal" class="fixed inset-0 z-[110] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeEditAnimalModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-gray-500 to-gray-600">
                <div class="flex items-center space-x-3">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    <h3 class="text-xl font-bold text-white">Editar Animal</h3>
                </div>
                <button onclick="closeEditAnimalModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="editAnimalForm" class="flex-1 overflow-y-auto p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nome</label>
                        <input type="text" name="name" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-500 focus:border-gray-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Número do Animal</label>
                        <input type="text" name="animal_number" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-500 focus:border-gray-500" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Raça</label>
                        <select name="breed" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-500 focus:border-gray-500" required>
                            <option value="">Selecione</option>
                            <option value="Holandesa">Holandesa</option>
                            <option value="Gir">Gir</option>
                            <option value="Girolando">Girolando</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Sexo</label>
                        <select name="gender" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-500 focus:border-gray-500" required>
                            <option value="">Selecione</option>
                            <option value="femea">Fêmea</option>
                            <option value="macho">Macho</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-500 focus:border-gray-500" required>
                            <option value="">Selecione</option>
                            <option value="Lactante">Lactante</option>
                            <option value="Seco">Seco</option>
                            <option value="Prenha">Prenha</option>
                            <option value="Novilha">Novilha</option>
                            <option value="Touro">Touro</option>
                            <option value="Bezerra">Bezerra</option>
                            <option value="Bezerro">Bezerro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Data de Nascimento</label>
                        <input type="date" name="birth_date" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-500 focus:border-gray-500" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-500 focus:border-gray-500"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeEditAnimalModal()" class="px-6 py-3 text-sm font-semibold border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-all">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 text-sm font-semibold bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-xl hover:from-gray-700 hover:to-gray-800 transition-all shadow-lg">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ver Animal -->
    <div id="viewAnimalModal" class="fixed inset-0 z-[110] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeViewAnimalModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-green-500 to-green-600">
                <div class="flex items-center space-x-3">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <h3 class="text-xl font-bold text-white">Detalhes do Animal</h3>
                </div>
                <button onclick="closeViewAnimalModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="viewAnimalContent" class="flex-1 overflow-y-auto p-6">
                <!-- Conteúdo será carregado dinamicamente -->
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/gerente-completo.js?v=<?php echo $v; ?>"></script>
    
    <!-- Modal Mais Opções - Fullscreen -->
    <div id="moreOptionsModal" class="fixed inset-0 z-[100] hidden bg-white">
        <style>
            /* Estilos específicos do modal Mais Opções */
            #moreOptionsModal {
                background: white;
            }
            #moreOptionsModal.hidden {
                display: none !important;
            }
            #moreOptionsModal:not(.hidden) {
                display: block !important;
            }
            #moreOptionsModal .app-item {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            #moreOptionsModal .app-item:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            }
            #moreOptionsModal .submodal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 99999;
                display: none;
                opacity: 0;
                transition: opacity 0.2s ease;
            }
            #moreOptionsModal .submodal.show {
                display: flex;
                opacity: 1;
                align-items: center;
                justify-content: center;
            }
            #moreOptionsModal .submodal-content {
                background: white;
                width: 100%;
                height: 100vh;
                overflow-y: auto;
                position: relative;
                padding: 24px;
            }
            @media (max-width: 768px) {
                #moreOptionsModal .submodal-content {
                    padding: 16px;
                }
            }
        </style>
        <div class="w-full h-full bg-white overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 bg-white sticky top-0 z-10 shadow-sm border-b border-gray-200">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5zM9 16c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm6 0c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Mais Opções</h2>
                        <p class="text-sm text-gray-600">Acesse ferramentas e recursos do sistema</p>
                    </div>
                </div>
                <button onclick="closeMoreOptionsModal()" class="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 rounded-xl transition-all duration-200 shadow-sm">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span class="text-gray-700 font-semibold">Voltar ao Dashboard</span>
                </button>
            </div>
            
            <!-- Content -->
            <div class="p-8">
                <div class="max-w-7xl mx-auto">
                    <?php 
                    // Usar variáveis já carregadas
                    $total_production = $more_options_total_production;
                    $avg_daily_production = $more_options_avg_daily_production;
                    $lactating_cows = $more_options_lactating_cows;
                    $pregnant_cows = $more_options_pregnant_cows;
                    $total_animals = $more_options_total_animals;
                    $animals = $more_options_animals;
                    $milk_data = $more_options_milk_data;
                    ?>
                    
                    <!-- Ferramentas Principais -->
                    <div class="mb-10">
                        <h3 class="text-lg font-bold text-gray-800 mb-5 flex items-center">
                            <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            Ferramentas Principais
                        </h3>
                        <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                            <!-- Relatórios -->
                            <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubModal('reports')">
                                <div class="flex flex-col items-center text-center space-y-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">Relatórios</p>
                                        <p class="text-[10px] text-gray-600 mt-0.5">Análises e dados</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Gestão de Rebanho -->
                            <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubModal('animals')">
                                <div class="flex flex-col items-center text-center space-y-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5zM9 16c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm6 0c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">Gestão de Rebanho</p>
                                        <p class="text-[10px] text-gray-600 mt-0.5">Animais e IA</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Gestão Sanitária -->
                            <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubModal('health')">
                                <div class="flex flex-col items-center text-center space-y-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">Gestão Sanitária</p>
                                        <p class="text-[10px] text-gray-600 mt-0.5">Saúde e vacinas</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Reprodução -->
                            <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubModal('reproduction')">
                                <div class="flex flex-col items-center text-center space-y-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">Reprodução</p>
                                        <p class="text-[10px] text-gray-600 mt-0.5">Prenhez e DPP</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Dashboard Analítico -->
                            <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubModal('analytics')">
                                <div class="flex flex-col items-center text-center space-y-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-slate-600 to-slate-700 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">Dashboard Analítico</p>
                                        <p class="text-[10px] text-gray-600 mt-0.5">Indicadores e KPIs</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Central de Ações -->
                            <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubModal('actions')">
                                <div class="flex flex-col items-center text-center space-y-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">Central de Ações</p>
                                        <p class="text-[10px] text-gray-600 mt-0.5">Tarefas prioritárias</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sistema RFID -->
                            <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubModal('rfid')">
                                <div class="flex flex-col items-center text-center space-y-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">Sistema RFID</p>
                                        <p class="text-[10px] text-gray-600 mt-0.5">Transponders</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Condição Corporal -->
                            <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubModal('bcs')">
                                <div class="flex flex-col items-center text-center space-y-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">Condição Corporal</p>
                                        <p class="text-[10px] text-gray-600 mt-0.5">Avaliação BCS</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Grupos e Lotes -->
                            <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubModal('groups')">
                                <div class="flex flex-col items-center text-center space-y-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-violet-500 to-violet-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">Grupos e Lotes</p>
                                        <p class="text-[10px] text-gray-600 mt-0.5">Organização</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Insights de IA -->
                            <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubModal('ai')">
                                <div class="flex flex-col items-center text-center space-y-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">Insights de IA</p>
                                        <p class="text-[10px] text-gray-600 mt-0.5">Previsões</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Suporte -->
                            <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubModal('support')">
                                <div class="flex flex-col items-center text-center space-y-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">Suporte</p>
                                        <p class="text-[10px] text-gray-600 mt-0.5">Ajuda e contato</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Utilitários -->
                    <div class="mb-10">
                        <h3 class="text-lg font-bold text-gray-800 mb-5 flex items-center">
                            <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                </svg>
                            </div>
                            Utilitários
                        </h3>
                        <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                            <!-- Alimentação -->
                            <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubModal('feeding')">
                                <div class="flex flex-col items-center text-center space-y-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-lime-500 to-lime-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">Alimentação</p>
                                        <p class="text-[10px] text-gray-600 mt-0.5">Concentrado e ração</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sistema de Touros -->
                            <a href="sistema-touros.php" class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex flex-col items-center text-center space-y-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5zM9 16c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm6 0c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                                            <circle cx="12" cy="8" r="2" fill="white"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">Sistema de Touros</p>
                                        <p class="text-[10px] text-gray-600 mt-0.5">Touros e inseminações</p>
                                    </div>
                                </div>
                            </a>
                            
                            <!-- Controle de Novilhas -->
                            <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubModal('heifers')">
                                <div class="flex flex-col items-center text-center space-y-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-fuchsia-500 to-fuchsia-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-xs">Controle de Novilhas</p>
                                        <p class="text-[10px] text-gray-600 mt-0.5">Custos de criação</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Submodais (Relatórios, Gestão de Rebanho, etc) -->
        <?php
        // Definir variáveis locais para uso nos modais (elas serão usadas no eval)
        $total_production = $more_options_total_production;
        $avg_daily_production = $more_options_avg_daily_production;
        $lactating_cows = $more_options_lactating_cows;
        $pregnant_cows = $more_options_pregnant_cows;
        $total_animals = $more_options_total_animals;
        $animals = $more_options_animals;
        $milk_data = $more_options_milk_data;
        
        // Ler o arquivo modalmore.php como string (sem executar o PHP)
        $modalmore_file_path = __DIR__ . '/includes/modalmore.php';
        if (!file_exists($modalmore_file_path)) {
            $modalmore_file_path = 'includes/modalmore.php';
        }
        
        if (file_exists($modalmore_file_path)) {
            $modalmore_file = file_get_contents($modalmore_file_path);
            
            // Extrair apenas os modais do modalmore.php (do comentário até antes do script)
            // Procurar por todos os modais - pegar tudo entre "<!-- Modals -->" e "<script"
            if (preg_match('/<!-- Modals -->(.*?)(<script|<\/body>|<\/html>)/s', $modalmore_file, $matches)) {
                $modals_content = $matches[1];
            } elseif (preg_match('/<!-- Added modals for all[^>]*>(.*?)(<script|<\/body>|<\/html>)/s', $modalmore_file, $matches)) {
                $modals_content = $matches[1];
            } elseif (preg_match('/<!-- Modal Relatórios -->(.*?)(<script|<\/body>|<\/html>)/s', $modalmore_file, $matches)) {
                $modals_content = $matches[1];
            } else {
                $modals_content = null;
            }
            
            if ($modals_content) {
                // Substituir class="modal" por class="submodal"
                $modals_content = preg_replace('/class="modal"/', 'class="submodal"', $modals_content);
                // Substituir class="modal-content" por class="submodal-content"
                $modals_content = preg_replace('/class="modal-content"/', 'class="submodal-content"', $modals_content);
                // Substituir closeModal por closeSubModal
                $modals_content = preg_replace('/closeModal\(/', 'closeSubModal(', $modals_content);
                
                // Processar o conteúdo PHP corretamente usando eval com variáveis do escopo atual
                ob_start();
                eval('?>' . $modals_content);
                $processed_modals = ob_get_clean();
                
                echo $processed_modals;
            } else {
                // Fallback: criar modais básicos se não conseguir extrair
                echo '<!-- Modais serão carregados aqui -->';
            }
        } else {
            echo '<!-- Arquivo modalmore.php não encontrado -->';
        }
        ?>
    </div>
    
    <script>
        // Funções para gerenciar submodais dentro do modal Mais Opções
        let currentSubModal = null;
        
        function openSubModal(modalName) {
            console.log('🔓 Abrindo submodal:', modalName);
            if (currentSubModal) {
                currentSubModal.classList.remove('show');
            }
            
            const modal = document.getElementById('modal-' + modalName);
            if (modal) {
                modal.classList.add('show');
                currentSubModal = modal;
                // Não bloquear o scroll do body aqui, pois o modal principal já está aberto
                console.log('✅ Submodal aberto:', modalName);
                
                // Se for o modal de animais, inicializar busca e filtros
                if (modalName === 'animals') {
                    setTimeout(() => {
                        if (typeof window.initAnimalSearchAndFilters === 'function') {
                            if (window.animalFiltersInitialized !== undefined) {
                                window.animalFiltersInitialized = false; // Reset para reinicializar
                            }
                            window.initAnimalSearchAndFilters();
                        } else {
                            console.warn('⚠️ Função initAnimalSearchAndFilters não encontrada');
                        }
                    }, 400);
                }
            } else {
                console.error('❌ Submodal não encontrado: modal-' + modalName);
            }
        }
        
        function closeSubModal(modalName) {
            console.log('🔒 Fechando submodal:', modalName || 'atual');
            const modal = modalName ? document.getElementById('modal-' + modalName) : currentSubModal;
            if (modal) {
                modal.classList.remove('show');
                currentSubModal = null;
                console.log('✅ Submodal fechado');
            }
        }
        
        // Tornar funções globais
        window.openSubModal = openSubModal;
        window.closeSubModal = closeSubModal;
        
        // Fechar submodal ao clicar fora ou pressionar ESC
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('submodal')) {
                closeSubModal();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && currentSubModal) {
                closeSubModal();
            }
        });
        
        // Também disponibilizar openModal e closeModal como aliases para compatibilidade
        window.openModal = openSubModal;
        window.closeModal = closeSubModal;
    </script>
    
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
            const modal = document.getElementById('addUserModal');
            const form = document.getElementById('addUserForm');
            const messageDiv = document.getElementById('addUserMessage');
            
            if (modal) {
                // Resetar formulário e mensagens
                if (form) {
                    form.reset();
                }
                if (messageDiv) {
                    messageDiv.classList.add('hidden');
                    messageDiv.className = 'hidden p-4 rounded-xl border';
                }
                
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeAddUserModal() {
            const modal = document.getElementById('addUserModal');
            const form = document.getElementById('addUserForm');
            const messageDiv = document.getElementById('addUserMessage');
            
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
            
            // Limpar formulário e mensagens
            if (form) {
                form.reset();
            }
            if (messageDiv) {
                messageDiv.classList.add('hidden');
                messageDiv.className = 'hidden p-4 rounded-xl border';
            }
        }
        
        function openLogoutModal() {
            document.getElementById('logoutModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
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
        window.openLogoutModal = openLogoutModal;
        window.closeLogoutModal = closeLogoutModal;
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
                    switchTab(tabName);
                });
            });
            
            console.log('✅ Controle das abas configurado!');
        });
        
        // Função para mudar de aba (usada tanto pelo menu superior quanto inferior)
        function switchTab(tabName) {
            // Selecionar todos os botões de navegação (superior e inferior)
            const navButtons = document.querySelectorAll('.nav-item[data-tab]');
            const bottomNavButtons = document.querySelectorAll('.bottom-nav-item[data-tab]');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // Remover classe active de todos os botões (superior e inferior)
            navButtons.forEach(btn => btn.classList.remove('active'));
            bottomNavButtons.forEach(btn => btn.classList.remove('active'));
            
            // Adicionar classe active aos botões correspondentes
            navButtons.forEach(btn => {
                if (btn.getAttribute('data-tab') === tabName) {
                    btn.classList.add('active');
                }
            });
            bottomNavButtons.forEach(btn => {
                if (btn.getAttribute('data-tab') === tabName) {
                    btn.classList.add('active');
                }
            });
            
            // Esconder todos os conteúdos
            tabContents.forEach(content => {
                content.classList.add('hidden');
                content.classList.remove('active');
            });
            
            // Mostrar o conteúdo da aba selecionada
            const selectedTab = document.getElementById(tabName + '-tab');
            if (selectedTab) {
                selectedTab.classList.remove('hidden');
                selectedTab.classList.add('active');
                
                // Scroll para o topo da página
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
                // Carregar dados específicos da aba
                switch (tabName) {
                    case 'volume':
                        if (typeof loadVolumeData === 'function') loadVolumeData();
                        break;
                    case 'quality':
                        if (typeof loadQualityData === 'function') loadQualityData();
                        break;
                    case 'payments':
                        if (typeof loadFinancialData === 'function') loadFinancialData();
                        break;
                    case 'users':
                        if (typeof loadUsersData === 'function') loadUsersData();
                        break;
                }
            }
        }
        
        // Função específica para o menu inferior
        function switchBottomTab(tabName) {
            switchTab(tabName);
        }
        
        // Função para lidar com o clique no botão "Mais"
        function handleMoreClick(event) {
            // Salvar estado atual antes de navegar
            const state = {
                scrollY: window.pageYOffset,
                timestamp: Date.now(),
                url: window.location.href
            };
            localStorage.setItem('lactech_dashboard_state', JSON.stringify(state));
            
            // Permitir navegação normal
            return true;
        }
        
        // Controle de visibilidade do menu mobile no scroll com animações suaves
        (function() {
            let lastScrollTop = 0;
            let ticking = false;
            const bottomNav = document.querySelector('.bottom-nav');
            
            if (!bottomNav) return;
            
            // Função para forçar animação em cascata
            function forceCascadeAnimation(nav, direction) {
                const items = nav.querySelectorAll('.bottom-nav-item');
                
                // Primeiro, remover todas as animações
                items.forEach((item) => {
                    item.style.animation = 'none';
                    item.style.opacity = direction === 'out' ? '1' : '0';
                });
                
                // Forçar reflow do navegador
                void nav.offsetWidth;
                
                // Restaurar animações após um pequeno delay para garantir o reset
                setTimeout(() => {
                    items.forEach((item, index) => {
                        // Remover estilo inline para permitir que CSS controle
                        item.style.animation = '';
                        item.style.opacity = '';
                        
                        // Se for entrada, garantir que o elemento está visível
                        if (direction === 'in') {
                            item.style.opacity = '0';
                            // Pequeno delay para garantir que a animação CSS seja aplicada
                            requestAnimationFrame(() => {
                                item.style.opacity = '';
                            });
                        }
                    });
                }, 10);
            }
            
            function handleScroll() {
                ticking = false; // Sempre resetar o ticking
                
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                // Só funciona em mobile (largura < 768px)
                if (window.innerWidth >= 768) {
                    bottomNav.classList.remove('hidden');
                    lastScrollTop = scrollTop;
                    return;
                }
                
                // Se estiver no topo, sempre mostrar
                if (scrollTop <= 10) {
                    bottomNav.classList.remove('hidden');
                    lastScrollTop = scrollTop;
                    return;
                }
                
                // Detectar direção do scroll
                const scrollDifference = scrollTop - lastScrollTop;
                
                // Só reagir se o scroll for significativo (mais de 5px)
                if (Math.abs(scrollDifference) >= 5) {
                    if (scrollDifference > 0) {
                        // Rolando para baixo - esconder menu suavemente com animação em cascata
                        if (!bottomNav.classList.contains('hidden')) {
                            bottomNav.classList.add('hidden');
                            // Forçar reanimação em cascata
                            forceCascadeAnimation(bottomNav, 'out');
                        }
                    } else {
                        // Rolando para cima - mostrar menu suavemente com animação em cascata
                        if (bottomNav.classList.contains('hidden')) {
                            bottomNav.classList.remove('hidden');
                            // Forçar reanimação em cascata
                            forceCascadeAnimation(bottomNav, 'in');
                        }
                    }
                }
            }
            
            window.addEventListener('scroll', function() {
                if (!ticking) {
                    window.requestAnimationFrame(handleScroll);
                    ticking = true;
                }
            }, { passive: true });
            
            // Resetar ao redimensionar
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    bottomNav.classList.remove('hidden');
                }
                lastScrollTop = window.pageYOffset || document.documentElement.scrollTop;
            });
            
            // Inicializar lastScrollTop
            lastScrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Inicializar animação de cascata quando a página carregar (mobile)
            if (window.innerWidth < 768 && !bottomNav.classList.contains('hidden')) {
                // Garantir que os itens começam invisíveis
                const items = bottomNav.querySelectorAll('.bottom-nav-item');
                items.forEach((item) => {
                    item.style.opacity = '0';
                });
                
                // Triggerar animação após um pequeno delay
                setTimeout(() => {
                    forceCascadeAnimation(bottomNav, 'in');
                }, 100);
            }
        })();
        
        // Função showTab para compatibilidade
        function showTab(tabName) {
            switchTab(tabName);
        }
        
        window.showTab = showTab;
        window.switchTab = switchTab;
        window.switchBottomTab = switchBottomTab;
        window.handleMoreClick = handleMoreClick;
        
        // Funções para o modal Mais Opções
        function openMoreOptionsModal() {
            console.log('🔓 Tentando abrir modal Mais Opções...');
            const modal = document.getElementById('moreOptionsModal');
            if (!modal) {
                console.error('❌ Modal maisOptionsModal não encontrado!');
                alert('Erro: Modal não encontrado. Verifique o console para mais detalhes.');
                return;
            }
            console.log('✅ Modal encontrado, removendo classe hidden...');
            // Remover hidden e garantir display block
            modal.classList.remove('hidden');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            console.log('✅ Modal Mais Opções aberto!');
        }
        
        function closeMoreOptionsModal() {
            console.log('🔒 Fechando modal Mais Opções...');
            const modal = document.getElementById('moreOptionsModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                console.log('✅ Modal fechado!');
            }
        }
        
        // Tornar funções globais
        window.openMoreOptionsModal = openMoreOptionsModal;
        window.closeMoreOptionsModal = closeMoreOptionsModal;
        
        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('moreOptionsModal');
                if (modal && !modal.classList.contains('hidden')) {
                    closeMoreOptionsModal();
                }
            }
        });
        
        // Verificar se o modal existe ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('moreOptionsModal');
            if (modal) {
                console.log('✅ Modal Mais Opções encontrado e pronto!');
                // Garantir que está oculto inicialmente
                modal.classList.add('hidden');
                modal.style.display = 'none';
            } else {
                console.error('❌ Modal Mais Opções NÃO encontrado no DOM!');
            }
            
            // Testar função
            if (typeof window.openMoreOptionsModal === 'function') {
                console.log('✅ Função openMoreOptionsModal está disponível globalmente');
            } else {
                console.error('❌ Função openMoreOptionsModal NÃO está disponível globalmente!');
            }
        });
    </script>
</body>
</html>
