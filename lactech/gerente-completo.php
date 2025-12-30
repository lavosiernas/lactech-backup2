<?php
/**
 * Dashboard Gerente - LacTech (Versão Completa)
 * Sistema completo com todas as funcionalidades originais
 * 
 * ESTRUTURA DO ARQUIVO:
 * 1. Configuração e Autenticação (linhas 1-70)
 * 2. Busca de Dados do Usuário e Fazenda (linhas 71-215)
 * 3. HTML Head - Meta tags e CSS (linhas 220-1800)
 * 4. HTML Body - Estrutura principal (linhas 1800-4000)
 * 5. JavaScript - Funcionalidades (linhas 4000-8906)
 */

// ============================================
// SEÇÃO 1: CONFIGURAÇÃO E AUTENTICAÇÃO
// ============================================

// Headers de cache - SEM CACHE para páginas dinâmicas
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Incluir configuração e iniciar sessão
require_once __DIR__ . '/includes/config_login.php';

// Verificar autenticação
// SOLUÇÃO DEFINITIVA: Quebrar o loop de redirecionamento
// Se veio do Google callback e não tem sessão, mostrar página de espera em vez de redirecionar
$isFromGoogleCallback = isset($_GET['google_linked']) || isset($_GET['google_error']);

if (!isLoggedIn()) {
    // Se veio do Google callback, mostrar página de espera em vez de redirecionar
    // Isso evita o loop: callback → gerente → login → gerente → loop
    if ($isFromGoogleCallback) {
        // Mostrar página de espera que tenta recarregar a sessão
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Processando...</title>
            <meta http-equiv="refresh" content="2;url=gerente-completo.php">
        </head>
        <body>
            <script>
                // Aguardar 2 segundos e tentar recarregar a página
                // Isso dá tempo para o cookie de sessão ser processado
                setTimeout(function() {
                    // Tentar recarregar a página
                    window.location.reload();
                }, 2000);
            </script>
            <p>Aguarde, processando vinculação...</p>
        </body>
        </html>
        <?php
        exit;
    } else {
        // Não veio do callback - redirecionar normalmente para login
        session_destroy();
        session_start();
        safeRedirect('inicio-login.php');
    }
} else {
    // Limpar flags do callback se estiverem presentes
    unset($_SESSION['from_google_callback']);
    unset($_SESSION['google_callback_time']);
}

// Verificar papel de gerente
if ($_SESSION['user_role'] !== 'gerente' && $_SESSION['user_role'] !== 'manager') {
    switch ($_SESSION['user_role']) {
        case 'proprietario':
            safeRedirect('proprietario.php');
            break;
        case 'funcionario':
            safeRedirect('funcionario.php');
            break;
        default:
            safeRedirect('index.php');
    }
}

// ============================================
// SEÇÃO 2: BUSCA DE DADOS DO USUÁRIO E FAZENDA
// ============================================

// Incluir classe de banco de dados
require_once __DIR__ . '/includes/Database.class.php';

// Obter dados do usuário da sessão
$current_user_id = $_SESSION['user_id'] ?? 1;
$current_user_name = $_SESSION['user_name'] ?? 'Gerente';
$current_user_role = $_SESSION['user_role'] ?? 'gerente';

// Buscar dados COMPLETOS do usuário do banco (não confiar apenas na sessão)
try {
    $db = Database::getInstance();
    // Buscar TODOS os dados do usuário do banco, não apenas da sessão
    $userData = $db->query("SELECT name, email, profile_photo, phone FROM users WHERE id = ?", [(int)$current_user_id]);
    
    // Verificar se os dados foram retornados antes de acessar
    if (!empty($userData) && isset($userData[0])) {
        // USAR DADOS DO BANCO (mais confiável que a sessão)
        $current_user_name = $userData[0]['name'] ?? $_SESSION['user_name'] ?? 'Gerente';
        $current_user_email = $userData[0]['email'] ?? $_SESSION['user_email'] ?? '';
        $current_user_photo = $userData[0]['profile_photo'] ?? null;
        
        // Atualizar sessão com dados do banco (sincronizar)
        $_SESSION['user_name'] = $current_user_name;
        $_SESSION['user_email'] = $current_user_email;
        
        // Telefone do usuário - usar do banco ou deixar vazio (não usar valor hardcode)
        $current_user_phone = $userData[0]['phone'] ?? '';
    } else {
        // Se não encontrar no banco, usar sessão ou padrão
        $current_user_name = $_SESSION['user_name'] ?? 'Gerente';
        $current_user_email = $_SESSION['user_email'] ?? '';
        $current_user_photo = null;
        // Não usar telefone hardcode - deixar vazio se não houver no banco
        $current_user_phone = '';
    }
    
    // Buscar dados da fazenda usando prepared statement
    $farmData = $db->query("SELECT name, phone, cnpj, address FROM farms WHERE id = ?", [1]);
    
    // Verificar se os dados foram retornados antes de acessar
    if (!empty($farmData) && isset($farmData[0])) {
        // Nome da fazenda - usar do banco ou valor padrão apenas se necessário
        $farm_name = $farmData[0]['name'] ?? 'Lagoa Do Mato';
        // Telefone e CNPJ - usar do banco ou deixar vazio (não usar valores hardcode)
        $farm_phone = $farmData[0]['phone'] ?? '';
        $farm_cnpj = $farmData[0]['cnpj'] ?? '';
        // Endereço - usar do banco ou deixar vazio (não usar endereço hardcode)
        $farm_address = $farmData[0]['address'] ?? '';
    } else {
        // Se não encontrar dados da fazenda no banco, usar valores padrão mínimos
        $farm_name = 'Lagoa Do Mato'; // Nome padrão apenas se necessário para exibição
        $farm_phone = ''; // Não usar telefone hardcode
        $farm_cnpj = ''; // Não usar CNPJ hardcode
        $farm_address = ''; // Não usar endereço hardcode - deve ser cadastrado no banco
    }
} catch (Exception $e) {
    error_log("Erro ao buscar dados do usuário/fazenda: " . $e->getMessage());
    $current_user_photo = null;
    // Não usar telefone hardcode em caso de erro - deixar vazio
    $current_user_phone = '';
    $farm_name = 'Lagoa Do Mato'; // Nome padrão apenas se necessário
    $farm_phone = ''; // Não usar telefone hardcode
    $farm_cnpj = ''; // Não usar CNPJ hardcode
    $farm_address = ''; // Não usar endereço hardcode - deve ser cadastrado no banco
}

// Buscar dados para a página Mais Opções (agora é uma página completa)
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

    // Buscar dados de produção de leite (últimos 30 dias, limitado a 7 registros)
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

// Versão para cache busting de assets
$v = time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#10b981">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="LacTech">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">
    <meta name="description" content="Sistema completo de gestão para fazendas leiteiras">
    <title>LacTech - Dashboard Gerente</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="./manifest.json">
    
    <!-- Preconnect para recursos externos (melhora velocidade) -->
    <link rel="preconnect" href="https://i.postimg.cc">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://i.postimg.cc">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="./assets/img/lactech-logo.png">
    
    <!-- Apple Touch Startup Images (Splash Screen) - Logo sem fundo preto -->
    <!-- A splash screen do PWA usa o background_color do manifest (verde) e a logo centralizada -->
    <style>
        /* Estilo para splash screen do PWA - App-like experience */
        @media all and (display-mode: standalone) {
            body {
                background-color: #10b981 !important;
                overscroll-behavior-y: contain;
            }
            
            /* Prevenir pull-to-refresh padrão do navegador */
            html {
                overscroll-behavior-y: contain;
            }
            
            /* Melhorar scroll em mobile */
            * {
                -webkit-overflow-scrolling: touch;
            }
        }
        
        /* Indicador de pull-to-refresh customizado */
        #pull-to-refresh-indicator {
            position: fixed;
            top: -60px;
            left: 0;
            right: 0;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(to bottom, #10b981, #059669);
            color: white;
            z-index: 9999;
            transition: transform 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .pull-indicator-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        
        .pull-indicator-icon {
            width: 24px;
            height: 24px;
            transition: transform 0.3s ease;
        }
        
        .pull-indicator-text {
            font-size: 14px;
            font-weight: 600;
        }
        
        /* Melhorar área de toque em mobile */
        @media (hover: none) and (pointer: coarse) {
            button, a, [role="button"], input[type="button"], input[type="submit"] {
                min-height: 44px;
                min-width: 44px;
                touch-action: manipulation;
            }
        }
        
        /* Prevenir zoom duplo toque */
        * {
            touch-action: pan-y pinch-zoom;
        }
        
        input, select, textarea {
            touch-action: manipulation;
        }
        
    </style>
    
    <!-- Favicon -->
    <link rel="icon" href="./assets/img/lactech-logo.png" type="image/x-icon">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS otimizado - usar build local se disponível -->
    <?php if (file_exists(__DIR__ . '/assets/css/tailwind.min.css')): ?>
        <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <?php else: ?>
        <!-- Fallback: CDN com configuração otimizada -->
        <script src="https://cdn.tailwindcss.com"></script>
    <?php endif; ?>
    
    <!-- Chart.js (sem defer para estar disponível quando necessário) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- face-api.js para detecção facial (defer - carrega depois) -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js" defer></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo $v; ?>">
    
    <!-- ============================================ -->
    <!-- ESTILOS PERSONALIZADOS -->
    <!-- ============================================ -->
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
        
        /* Skeleton Loader Styles */
        #skeletonLoader {
            background: #ffffff;
        }
        
        #skeletonLoader [class*="bg-gray"] {
            background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.5s ease-in-out infinite;
        }
        
        @keyframes skeleton-shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        #skeletonLoader.fade-out {
            opacity: 0;
            transition: opacity 0.5s ease-out;
            pointer-events: none;
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
            background: white;
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
        
        /* Garantir que os labels dos indicadores sejam pretos */
        .metric-label {
            color: #111827 !important; /* gray-900 */
        }
        
        /* ============================================
           MODO ESCURO - DESABILITADO (EM DESENVOLVIMENTO)
           Todos os estilos foram comentados
           ============================================ */
        /* MODO ESCURO DESABILITADO
        .dark-mode {
            background-color: #121212 !important;
            color: #e0e0e0 !important;
        }
        
        /* Fundos principais - tons de cinza escuro baseados em #121212 */
        .dark-mode .bg-gray-50,
        .dark-mode .bg-slate-50 {
            background-color: #1a1a1a !important;
        }
        
        .dark-mode .bg-white {
            background-color: #1e1e1e !important;
        }
        
        .dark-mode .bg-gray-100 {
            background-color: #1f1f1f !important;
        }
        
        /* Cards - tons harmoniosos com #121212 */
        .dark-mode .metric-card {
            background-color: #1e1e1e !important;
            border-color: #2a2a2a !important;
            color: #e0e0e0 !important;
        }
        
        .dark-mode .data-card {
            background-color: #1e1e1e !important;
            border-color: #2a2a2a !important;
            color: #e0e0e0 !important;
        }
        
        /* Chart containers - REMOVER FUNDO BRANCO */
        .dark-mode .chart-container {
            background: #1e1e1e !important;
            border-color: #2a2a2a !important;
        }
        
        /* Textos - tons de cinza claro harmoniosos */
        .dark-mode .text-gray-800,
        .dark-mode .text-gray-900,
        .dark-mode .text-slate-900 {
            color: #e0e0e0 !important;
        }
        
        .dark-mode .text-gray-600,
        .dark-mode .text-slate-600 {
            color: #b0b0b0 !important;
        }
        
        .dark-mode .text-gray-500,
        .dark-mode .text-slate-500 {
            color: #9a9a9a !important;
        }
        
        .dark-mode .text-gray-400,
        .dark-mode .text-slate-400 {
            color: #7a7a7a !important;
        }
        
        .dark-mode .text-gray-700,
        .dark-mode .text-slate-700 {
            color: #c0c0c0 !important;
        }
        
        /* Bordas - tons harmoniosos */
        .dark-mode .border-gray-200,
        .dark-mode .border-slate-200 {
            border-color: #2a2a2a !important;
        }
        
        .dark-mode .border-gray-300,
        .dark-mode .border-slate-300 {
            border-color: #3a3a3a !important;
        }
        
        /* Inputs e selects */
        .dark-mode input,
        .dark-mode select,
        .dark-mode textarea {
            background-color: #1e1e1e !important;
            border-color: #2a2a2a !important;
            color: #e0e0e0 !important;
        }
        
        .dark-mode input:focus,
        .dark-mode select:focus,
        .dark-mode textarea:focus {
            border-color: var(--forest-500) !important;
            background-color: #242424 !important;
        }
        
        .dark-mode input::placeholder,
        .dark-mode textarea::placeholder {
            color: #7a7a7a !important;
        }
        
        /* Welcome section - REMOVER GRADIENTE */
        .dark-mode .gradient-forest {
            background: #1e1e1e !important;
        }
        
        /* Botão toggle modo escuro */
        .dark-mode-toggle {
            position: relative;
            width: 50px;
            height: 26px;
            background: #d1d5db;
            border-radius: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
            flex-shrink: 0;
        }
        
        .dark-mode-toggle:hover {
            background: #9ca3af;
            border-color: #d1d5db;
        }
        
        .dark-mode-toggle.active {
            background: var(--forest-600);
            border-color: var(--forest-500);
        }
        
        .dark-mode-toggle.active:hover {
            background: var(--forest-700);
            border-color: var(--forest-600);
        }
        
        .dark-mode-toggle-slider {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 18px;
            height: 18px;
            background: #ffffff;
            border-radius: 50%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .dark-mode-toggle.active .dark-mode-toggle-slider {
            transform: translateX(24px);
            background: #ffffff;
        }
        
        .dark-mode-toggle svg {
            width: 12px;
            height: 12px;
            color: #6b7280;
            transition: color 0.3s ease;
        }
        
        .dark-mode-toggle.active svg {
            color: #ffffff;
        }
        
        /* Modo escuro - ajustes do toggle dentro do card */
        .dark-mode .dark-mode-toggle {
            background: #3a3a3a;
            border-color: #4a4a4a;
        }
        
        .dark-mode .dark-mode-toggle:hover {
            background: #4a4a4a;
            border-color: #5a5a5a;
        }
        
        .dark-mode .dark-mode-toggle.active {
            background: var(--forest-600);
            border-color: var(--forest-500);
        }
        
        .dark-mode .dark-mode-toggle svg {
            color: #9a9a9a;
        }
        
        .dark-mode .dark-mode-toggle.active svg {
            color: #ffffff;
        }
        
        /* Scrollbar modo escuro */
        .dark-mode ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        .dark-mode ::-webkit-scrollbar-track {
            background: #0a0f0a;
        }
        
        .dark-mode ::-webkit-scrollbar-thumb {
            background: #1a3a1a;
            border-radius: 4px;
        }
        
        .dark-mode ::-webkit-scrollbar-thumb:hover {
            background: #2a4a2a;
        }
        
        /* Botões no modo escuro */
        .dark-mode button:not(.gradient-forest):not(.dark-mode-toggle) {
            background-color: #0f1f0f !important;
            border-color: #1a3a1a !important;
            color: #ffffff !important;
        }
        
        .dark-mode button:not(.gradient-forest):not(.dark-mode-toggle):hover {
            background-color: #1a3a1a !important;
            border-color: var(--forest-500) !important;
        }
        
        /* Cards de atividades */
        .dark-mode .bg-gray-100 {
            background-color: #0f1f0f !important;
        }
        
        .dark-mode .text-gray-400 {
            color: #6a906a !important;
        }
        
        .dark-mode .text-gray-500 {
            color: #8fb38f !important;
        }
        
        /* Modais - REMOVER FUNDO BRANCO */
        .dark-mode .modal-content,
        .dark-mode [class*="modal"],
        .dark-mode [id*="Overlay"] > div > div,
        .dark-mode [id*="overlay"] > div > div {
            background-color: #1e1e1e !important;
            border-color: #2a2a2a !important;
            color: #e0e0e0 !important;
        }
        
        /* Overlays específicos */
        .dark-mode #generalVolumeOverlay > div > div,
        .dark-mode #volumeOverlay > div > div,
        .dark-mode [id*="Overlay"] > div > div.bg-white {
            background-color: #1e1e1e !important;
        }
        
        /* Formulários dentro de modais */
        .dark-mode [class*="bg-gradient-to-br"][class*="from-slate"],
        .dark-mode [class*="bg-gradient-to-br"][class*="from-green"],
        .dark-mode [class*="bg-gradient-to-br"][class*="from-blue"] {
            background: #252525 !important;
            border-color: #2a2a2a !important;
        }
        
        /* Footer dos modais */
        .dark-mode [class*="sticky"][class*="bottom"] {
            background-color: #1e1e1e !important;
            border-color: #2a2a2a !important;
        }
        
        /* Dropdowns */
        .dark-mode [class*="dropdown"],
        .dark-mode [class*="menu"] {
            background-color: #1e1e1e !important;
            border-color: #2a2a2a !important;
        }
        
        .dark-mode [class*="dropdown"] a,
        .dark-mode [class*="menu"] a {
            color: #e0e0e0 !important;
        }
        
        .dark-mode [class*="dropdown"] a:hover,
        .dark-mode [class*="menu"] a:hover {
            background-color: #252525 !important;
        }
        
        /* Listas */
        .dark-mode ul,
        .dark-mode ol {
            color: #e0e0e0 !important;
        }
        
        .dark-mode li {
            color: #e0e0e0 !important;
        }
        
        /* Links */
        .dark-mode a:not(.nav-item):not(.gradient-forest) {
            color: var(--forest-400) !important;
        }
        
        .dark-mode a:not(.nav-item):not(.gradient-forest):hover {
            color: var(--forest-300) !important;
        }
        
        /* Badges e labels */
        .dark-mode [class*="badge"],
        .dark-mode [class*="label"] {
            background-color: #252525 !important;
            color: #e0e0e0 !important;
        }
        
        /* Tooltips */
        .dark-mode [class*="tooltip"] {
            background-color: #1e1e1e !important;
            border-color: #2a2a2a !important;
            color: #e0e0e0 !important;
        }
        
        /* Shadow adjustments */
        .dark-mode .shadow-lg,
        .dark-mode .shadow-xl,
        .dark-mode .shadow-2xl {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.8) !important;
        }
        
        /* Overlay backgrounds */
        .dark-mode [class*="overlay"] {
            background-color: rgba(18, 18, 18, 0.95) !important;
        }
        
        /* Bottom nav no modo escuro */
        .dark-mode .bottom-nav {
            background-color: #1a1a1a !important;
            border-top-color: #2a2a2a !important;
        }
        
        .dark-mode .bottom-nav-item {
            color: #9a9a9a !important;
        }
        
        .dark-mode .bottom-nav-item.active {
            color: var(--forest-400) !important;
            background-color: #1e1e1e !important;
        }
        
        /* Tabelas */
        .dark-mode table {
            background-color: #1e1e1e !important;
            color: #e0e0e0 !important;
        }
        
        .dark-mode th {
            background-color: #1a1a1a !important;
            color: #e0e0e0 !important;
            border-color: #2a2a2a !important;
        }
        
        /* Corrigir duplicatas de tabela */
        .dark-mode table th {
            background-color: #1a1a1a !important;
            color: #e0e0e0 !important;
            border-color: #2a2a2a !important;
        }
        
        .dark-mode table td {
            border-color: #2a2a2a !important;
            color: #e0e0e0 !important;
        }
        
        .dark-mode table tr:hover {
            background-color: #252525 !important;
        }
        
        .dark-mode td {
            border-color: #2a2a2a !important;
            color: #e0e0e0 !important;
        }
        
        .dark-mode tr:hover {
            background-color: #252525 !important;
        }
        
        /* Botões no modo escuro */
        .dark-mode button:not(.gradient-forest):not(.dark-mode-toggle):not([class*="bg-green"]):not([class*="bg-blue"]) {
            background-color: #1e1e1e !important;
            border-color: #2a2a2a !important;
            color: #e0e0e0 !important;
        }
        
        .dark-mode button:not(.gradient-forest):not(.dark-mode-toggle):not([class*="bg-green"]):not([class*="bg-blue"]):hover {
            background-color: #252525 !important;
            border-color: #3a3a3a !important;
        }
        
        /* Cards de atividades */
        .dark-mode .bg-gray-100 {
            background-color: #1f1f1f !important;
        }
        
        /* Abas (tabs) */
        .dark-mode .tab-content {
            background-color: transparent !important;
        }
        
        /* Imagens de fundo no banner - esconder no modo escuro */
        .dark-mode .gradient-forest img {
            opacity: 0 !important;
        }
        
        /* Seletores específicos para modais - forçar modo escuro */
        .dark-mode select.bg-white,
        .dark-mode input.bg-white,
        .dark-mode textarea.bg-white {
            background-color: #1e1e1e !important;
            color: #e0e0e0 !important;
            border-color: #2a2a2a !important;
        }
        
        /* Formulários dentro de modais - todos os elementos */
        .dark-mode [id*="Overlay"] input,
        .dark-mode [id*="Overlay"] select,
        .dark-mode [id*="Overlay"] textarea,
        .dark-mode [id*="overlay"] input,
        .dark-mode [id*="overlay"] select,
        .dark-mode [id*="overlay"] textarea {
            background-color: #1e1e1e !important;
            color: #e0e0e0 !important;
            border-color: #2a2a2a !important;
        }
        
        /* Labels e textos dentro de modais */
        .dark-mode [id*="Overlay"] label,
        .dark-mode [id*="overlay"] label,
        .dark-mode [id*="Overlay"] .text-slate-700,
        .dark-mode [id*="overlay"] .text-slate-700,
        .dark-mode [id*="Overlay"] .text-slate-800,
        .dark-mode [id*="overlay"] .text-slate-800 {
            color: #e0e0e0 !important;
        }
        
        /* Textos secundários dentro de modais */
        .dark-mode [id*="Overlay"] .text-slate-500,
        .dark-mode [id*="overlay"] .text-slate-500 {
            color: #9a9a9a !important;
        }
        
        /* Botões de cancelar dentro de modais */
        .dark-mode [id*="Overlay"] button[type="button"]:not([class*="bg-green"]):not([class*="bg-blue"]),
        .dark-mode [id*="overlay"] button[type="button"]:not([class*="bg-green"]):not([class*="bg-blue"]) {
            background-color: #1e1e1e !important;
            border-color: #2a2a2a !important;
            color: #e0e0e0 !important;
        }
        
        .dark-mode [id*="Overlay"] button[type="button"]:not([class*="bg-green"]):not([class*="bg-blue"]):hover,
        .dark-mode [id*="overlay"] button[type="button"]:not([class*="bg-green"]):not([class*="bg-blue"]):hover {
            background-color: #252525 !important;
        }
        
        /* Hover states para inputs e selects */
        .dark-mode [id*="Overlay"] input:hover,
        .dark-mode [id*="Overlay"] select:hover,
        .dark-mode [id*="overlay"] input:hover,
        .dark-mode [id*="overlay"] select:hover {
            border-color: #3a3a3a !important;
        }
        
        /* Scrollbar dentro de modais */
        .dark-mode [id*="Overlay"] ::-webkit-scrollbar,
        .dark-mode [id*="overlay"] ::-webkit-scrollbar {
            width: 8px;
        }
        
        .dark-mode [id*="Overlay"] ::-webkit-scrollbar-track,
        .dark-mode [id*="overlay"] ::-webkit-scrollbar-track {
            background: #1a1a1a;
        }
        
        .dark-mode [id*="Overlay"] ::-webkit-scrollbar-thumb,
        .dark-mode [id*="overlay"] ::-webkit-scrollbar-thumb {
            background: #2a2a2a;
            border-radius: 4px;
        }
        
        .dark-mode [id*="Overlay"] ::-webkit-scrollbar-thumb:hover,
        .dark-mode [id*="overlay"] ::-webkit-scrollbar-thumb:hover {
            background: #3a3a3a;
        }
        */ /* FIM DO MODO ESCURO DESABILITADO */
        
        /* Sistema de Notificações Toast */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-width: 400px;
            pointer-events: none;
        }
        
        .toast {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15), 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            max-width: 400px;
            pointer-events: auto;
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            border-left: 4px solid;
        }
        
        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .toast.success {
            border-left-color: #10b981;
        }
        
        .toast.error {
            border-left-color: #ef4444;
        }
        
        .toast.warning {
            border-left-color: #f59e0b;
        }
        
        .toast.info {
            border-left-color: #3b82f6;
        }
        
        .toast-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
        }
        
        .toast.success .toast-icon {
            color: #10b981;
        }
        
        .toast.error .toast-icon {
            color: #ef4444;
        }
        
        .toast.warning .toast-icon {
            color: #f59e0b;
        }
        
        .toast.info .toast-icon {
            color: #3b82f6;
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-title {
            font-weight: 600;
            font-size: 14px;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .toast-message {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.4;
        }
        
        .toast-close {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            cursor: pointer;
            color: #9ca3af;
            transition: color 0.2s;
            background: none;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .toast-close:hover {
            color: #374151;
        }
        
        @media (max-width: 640px) {
            .toast-container {
                top: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
            
            .toast {
                min-width: auto;
                max-width: none;
            }
        }
        
        /* Sistema de Validação de Formulários */
        .form-group {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .form-input {
            transition: all 0.3s ease;
        }
        
        .form-input.valid {
            border-color: #10b981 !important;
            border-width: 2px;
        }
        
        .form-input.invalid {
            border-color: #ef4444 !important;
            border-width: 2px;
            background-color: #fef2f2;
        }
        
        .form-input:focus.valid {
            ring-color: #10b981;
            border-color: #10b981;
        }
        
        .form-input:focus.invalid {
            ring-color: #ef4444;
            border-color: #ef4444;
        }
        
        .form-error-message {
            display: none;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #ef4444;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            animation: slideDown 0.3s ease;
        }
        
        .form-error-message.show {
            display: flex;
        }
        
        .form-success-message {
            display: none;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            animation: slideDown 0.3s ease;
        }
        
        .form-success-message.show {
            display: flex;
        }
        
        .form-input-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .form-input-icon.show {
            opacity: 1;
        }
        
        .form-input-icon.valid-icon {
            color: #10b981;
        }
        
        .form-input-icon.invalid-icon {
            color: #ef4444;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-group.has-error .form-label {
            color: #ef4444;
        }
        
        .form-group.has-success .form-label {
            color: #10b981;
        }
        
        /* Loading state para botões */
        .btn-loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid currentColor;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Skeleton Loaders */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s ease-in-out infinite;
            border-radius: 0.5rem;
        }
        
        .skeleton-text {
            height: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0.25rem;
        }
        
        .skeleton-title {
            height: 1.5rem;
            width: 60%;
            margin-bottom: 1rem;
            border-radius: 0.25rem;
        }
        
        .skeleton-card {
            height: 200px;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .skeleton-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
        }
        
        .skeleton-button {
            height: 40px;
            width: 120px;
            border-radius: 0.5rem;
        }
        
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Indicadores de Progresso */
        .progress-bar {
            width: 100%;
            height: 4px;
            background-color: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            border-radius: 2px;
            transition: width 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .progress-bar-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            animation: progress-shimmer 1.5s infinite;
        }
        
        @keyframes progress-shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        /* Melhorias de Acessibilidade */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }
        
        /* Focus visível para navegação por teclado */
        *:focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
            border-radius: 4px;
        }
        
        button:focus-visible,
        a:focus-visible,
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
        
        /* Skip to main content link */
        .skip-link {
            position: absolute;
            top: -40px;
            left: 0;
            background: #1f2937;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            z-index: 100;
            border-radius: 0 0 4px 0;
        }
        
        .skip-link:focus {
            top: 0;
        }
        
        /* Melhorias de Responsividade Mobile */
        @media (max-width: 640px) {
            /* Ajustes de espaçamento */
            .container-mobile {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            /* Textos menores em mobile */
            .text-mobile-sm {
                font-size: 0.875rem;
            }
            
            /* Cards mais compactos */
            .card-mobile {
                padding: 1rem;
            }
            
            /* Botões full width em mobile quando necessário */
            .btn-mobile-full {
                width: 100%;
            }
            
            /* Inputs maiores para melhor toque */
            input[type="text"],
            input[type="email"],
            input[type="password"],
            input[type="number"],
            input[type="tel"],
            input[type="date"],
            select,
            textarea {
                font-size: 16px; /* Previne zoom no iOS */
                min-height: 44px; /* Tamanho mínimo recomendado para toque */
            }
        }
        
        /* Melhorias no sistema de notificações - Agrupamento */
        .toast-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .toast-group-header {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 4px 0;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 4px;
        }
        
        /* Animações suaves */
        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        
        .slide-up {
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .scale-in {
            animation: scaleIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* Estados de loading para elementos */
        .loading-overlay {
            position: relative;
        }
        
        .loading-overlay::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: inherit;
        }
        
        .loading-overlay.loading::after {
            content: '';
            background: rgba(255, 255, 255, 0.9);
        }
        
        /* Melhorias de toque para mobile */
        @media (hover: none) and (pointer: coarse) {
            button,
            a,
            [role="button"] {
                min-height: 44px;
                min-width: 44px;
            }
            
            /* Aumentar área de toque */
            .touch-target {
                padding: 12px;
                margin: -12px;
            }
        }
        /* Skeleton Loader Styles */
        #skeletonLoader {
            background: #ffffff;
        }
        
        #skeletonLoader [class*="bg-gray"] {
            background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.5s ease-in-out infinite;
        }
        
        @keyframes skeleton-shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        #skeletonLoader.fade-out {
            opacity: 0;
            transition: opacity 0.5s ease-out;
            pointer-events: none;
        }
    </style>
</head>
<body class="bg-gray-50 font-inter" id="mainBody">
    <!-- Skip to main content link para acessibilidade -->
    <a href="#main-content" class="skip-link">Pular para o conteúdo principal</a>
    
    <!-- Container de Notificações Toast -->
    <div id="toastContainer" class="toast-container"></div>
    
    <!-- Skeleton Loader -->
    <div id="skeletonLoader" class="fixed inset-0 z-[9999] bg-white overflow-y-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Welcome Section Skeleton -->
            <div class="bg-gray-200 rounded-2xl p-6 mb-6" style="height: 120px;"></div>
            
            <!-- Key Metrics Skeleton -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200">
                    <div class="w-12 h-12 bg-gray-300 rounded-xl mx-auto mb-3"></div>
                    <div class="h-6 bg-gray-300 rounded w-20 mx-auto mb-2"></div>
                    <div class="h-4 bg-gray-200 rounded w-24 mx-auto mb-1"></div>
                    <div class="h-3 bg-gray-200 rounded w-16 mx-auto"></div>
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200">
                    <div class="w-12 h-12 bg-gray-300 rounded-xl mx-auto mb-3"></div>
                    <div class="h-6 bg-gray-300 rounded w-20 mx-auto mb-2"></div>
                    <div class="h-4 bg-gray-200 rounded w-24 mx-auto mb-1"></div>
                    <div class="h-3 bg-gray-200 rounded w-16 mx-auto"></div>
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200">
                    <div class="w-12 h-12 bg-gray-300 rounded-xl mx-auto mb-3"></div>
                    <div class="h-6 bg-gray-300 rounded w-20 mx-auto mb-2"></div>
                    <div class="h-4 bg-gray-200 rounded w-24 mx-auto mb-1"></div>
                    <div class="h-3 bg-gray-200 rounded w-16 mx-auto"></div>
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200">
                    <div class="w-12 h-12 bg-gray-300 rounded-xl mx-auto mb-3"></div>
                    <div class="h-6 bg-gray-300 rounded w-20 mx-auto mb-2"></div>
                    <div class="h-4 bg-gray-200 rounded w-24 mx-auto mb-1"></div>
                    <div class="h-3 bg-gray-200 rounded w-16 mx-auto"></div>
                </div>
            </div>
            
            <!-- Charts Section Skeleton -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <div class="h-5 bg-gray-300 rounded w-32 mb-4"></div>
                    <div class="h-64 bg-gray-200 rounded"></div>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <div class="h-5 bg-gray-300 rounded w-40 mb-4"></div>
                    <div class="h-64 bg-gray-200 rounded"></div>
                </div>
            </div>
            
            <!-- Quality Chart Skeleton -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <div class="h-5 bg-gray-300 rounded w-48 mb-4"></div>
                    <div class="h-64 bg-gray-200 rounded"></div>
                </div>
            </div>
            
            <!-- Temperature Chart Skeleton -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 mb-6">
                <div class="h-5 bg-gray-300 rounded w-40 mb-4"></div>
                <div class="h-64 bg-gray-200 rounded"></div>
            </div>
            
            <!-- Monthly Production Chart Skeleton -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 mb-6">
                <div class="h-5 bg-gray-300 rounded w-48 mb-4"></div>
                <div class="h-64 bg-gray-200 rounded"></div>
            </div>
            
            <!-- Recent Activities Skeleton -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="h-5 bg-gray-300 rounded w-40"></div>
                    <div class="h-4 bg-gray-200 rounded w-20"></div>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg">
                        <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
                        <div class="flex-1">
                            <div class="h-4 bg-gray-300 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg">
                        <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
                        <div class="flex-1">
                            <div class="h-4 bg-gray-300 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg">
                        <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
                        <div class="flex-1">
                            <div class="h-4 bg-gray-300 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- HEADER - NAVEGAÇÃO PRINCIPAL -->
    <!-- ============================================ -->
    <header class="gradient-forest text-white shadow-lg" role="banner">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo e Título -->
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center p-2 shadow-lg" aria-hidden="true">
                        <img src="./assets/img/lactech-logo.png" alt="LacTech Logo" class="w-full h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <img src="./assets/video/lactechbranca.png" alt="LacTech Logo" class="w-full h-full object-contain" style="display: none;" onerror="this.style.display='none';">
                    </div>
                    <div>
                        <h1 class="text-xl font-bold">LacTech</h1>
                        <p class="text-forest-200 text-sm"><?php echo htmlspecialchars($farm_name); ?></p>
                    </div>
                </div>
                
                <!-- Navegação -->
                <nav class="hidden md:flex items-center space-x-1" role="navigation" aria-label="Navegação principal">
                    <button class="nav-item active px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="dashboard" aria-label="Ir para Dashboard" aria-current="page">
                        Dashboard
                    </button>
                    <button class="nav-item px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="volume" aria-label="Ir para Volume">
                        Volume
                    </button>
                    <button class="nav-item px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="animals-control" aria-label="Ir para Controle de Animais">
                        Controle de Animais
                    </button>
                    <button class="nav-item px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="quality" aria-label="Ir para Qualidade">
                        Qualidade
                    </button>
                    <button class="nav-item px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="payments" aria-label="Ir para Financeiro">
                        Financeiro
                    </button>
                    <button class="nav-item px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="users" aria-label="Ir para Usuários">
                        Usuários
                    </button>
                    <button onclick="openMoreOptionsModal()" class="px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" aria-label="Abrir menu Mais Opções">
                        <span>MAIS</span>
                    </button>
                </nav>
                
                <!-- Perfil do Usuário -->
                <div class="flex items-center space-x-4">
                    <!-- Notificações -->
                    <button onclick="openNotificationsDrawer()" class="relative p-2 text-white hover:text-forest-200 transition-colors" aria-label="Abrir notificações" aria-describedby="notificationsBellCount">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span id="notificationsBellCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] leading-none py-1 px-1.5 font-bold rounded-full min-w-[18px] text-center hidden" aria-label="Número de notificações não lidas"></span>
                    </button>
                    
                    <!-- Perfil -->
                    <button onclick="openProfileOverlay()" class="flex items-center space-x-3 text-white hover:text-forest-200 p-2 rounded-lg transition-all" id="profileButton">
                        <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center overflow-hidden border-2 border-white border-opacity-30">
                            <?php 
                            $headerPhotoSrc = '';
                            $headerShowPhoto = false;
                            
                            if (!empty($current_user_photo)) {
                                $headerPhotoPath = trim($current_user_photo, '/\\');
                                
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
                                <img src="<?php echo htmlspecialchars($headerPhotoSrc); ?>?t=<?php echo time(); ?>" alt="Foto do perfil" class="w-full h-full object-cover" id="headerProfilePhoto" onerror="this.style.display='none'; document.getElementById('headerProfilePhotoIcon').style.display='flex';">
                            <?php endif; ?>
                            <svg id="headerProfilePhotoIcon" class="w-5 h-5 text-white <?php echo $headerShowPhoto ? 'hidden' : ''; ?>" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium" id="headerProfileName"><?php echo htmlspecialchars($current_user_name); ?></p>
                            <p class="text-xs text-forest-200">Gerente</p>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- ============================================ -->
    <!-- CONTEÚDO PRINCIPAL -->

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" id="main-content">
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
                    <div class="metric-label text-gray-900 font-medium">Volume Hoje</div>
                    <div class="metric-label text-gray-900 font-semibold mt-1">Litros</div>
                </div>
                
                <div class="metric-card rounded-2xl p-3 sm:p-4 text-center card-compact">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="metric-value font-bold text-slate-900 mb-1" id="qualityAverage">--%</div>
                    <div class="metric-label text-gray-900 font-medium">Qualidade Média</div>
                    <div class="metric-label text-gray-900 font-semibold mt-1">Hoje</div>
                </div>
                
                <div class="metric-card rounded-2xl p-3 sm:p-4 text-center card-compact">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="metric-value font-bold text-slate-900 mb-1" id="pendingPayments">R$ --</div>
                    <div class="metric-label text-gray-900 font-medium">Pagamentos Pendentes</div>
                    <div class="metric-label text-gray-900 font-semibold mt-1">Este Mês</div>
                </div>
                
                <div class="metric-card rounded-2xl p-3 sm:p-4 text-center card-compact">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-orange-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="metric-value font-bold text-slate-900 mb-1" id="activeUsers">--</div>
                    <div class="metric-label text-gray-900 font-medium">Usuários Ativos</div>
                    <div class="metric-label text-gray-900 font-semibold mt-1">Sistema</div>
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

            <!-- Quality Chart Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-4 sm:mb-6">
                <!-- Quality Chart -->
                <div class="data-card rounded-2xl p-4 sm:p-6 card-compact">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <h3 class="card-title font-bold text-slate-900">Qualidade do Leite (Últimos 7 dias)</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="qualityWeeklyChart"></canvas>
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
                            <select id="volumePeriod" class="px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none bg-white shadow-sm hover:shadow transition-all">
                                <option value="today">Hoje</option>
                                <option value="week">Esta Semana</option>
                                <option value="month">Este Mês</option>
                            </select>
                            <button onclick="showGeneralVolumeOverlay()" class="px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg hover:from-green-700 hover:to-emerald-700 hover:shadow-xl transition-all duration-200 font-semibold text-sm flex items-center justify-center gap-2 shadow-lg hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                                </svg>
                                Volume Geral
                            </button>
                            <button onclick="showVolumeOverlay()" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 hover:shadow-xl transition-all duration-200 font-semibold text-sm flex items-center justify-center gap-2 shadow-lg hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                Volume por Animal
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
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Registros de Volume</h3>
                        <button onclick="showDeleteAllVolumeModal()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all font-semibold text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Excluir Todos
                        </button>
                    </div>
                    <!-- Cards Mobile (visível apenas em telas < 768px) -->
                    <div id="volumeRecordsCards" class="md:hidden space-y-3">
                        <div class="text-center py-8 text-gray-500">Carregando registros...</div>
                    </div>
                    
                    <!-- Tabela Desktop (visível apenas em telas >= 768px) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Data</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Horário</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Período</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Volume Total (L)</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Animais</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="volumeRecordsTable">
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-500">Carregando registros...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Animals Control Tab -->
        <div id="animals-control-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Animals Control Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Controle de Animais</h2>
                            <p class="text-slate-600 text-sm">Gerencie quais animais estão disponíveis para ordenha</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-200 rounded-lg px-4 py-2">
                                <label class="block text-xs font-semibold text-amber-700 mb-1">Data</label>
                                <p id="animalsControlDate" class="text-base font-bold text-amber-900"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Absent Animals Section -->
                <div class="data-card rounded-2xl p-6">
                    <input type="hidden" id="absentAnimalsInput" value="">
                    <input type="hidden" id="animalsControlDateValue" value="">
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-bold text-slate-900">Animais Ausentes da Ordenha</h3>
                            <div class="flex items-center gap-3">
                                <div class="bg-amber-100 border-2 border-amber-300 rounded-lg px-4 py-2">
                                    <span class="text-sm font-semibold text-amber-800">
                                        <span id="absentAnimalsCount">0</span> de <span id="totalAnimalsCount">0</span> marcados como ausentes
                                    </span>
                                </div>
                                <button onclick="selectAllAbsentAnimals()" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-all font-semibold text-sm flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Selecionar Todos
                                </button>
                                <button onclick="deselectAllAbsentAnimals()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all font-semibold text-sm flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Deselecionar Todos
                                </button>
                            </div>
                        </div>
                        <p class="text-sm text-slate-600 mb-4">Marque os animais que NÃO estão participando da ordenha de hoje. Estes animais não aparecerão no registro de volume por animal.</p>
                    </div>

                    <div id="animalsControlList" class="space-y-3">
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <p>Carregando animais...</p>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t flex justify-end gap-3">
                        <button onclick="clearAbsentAnimals()" class="px-4 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all">
                            Limpar Seleção
                        </button>
                        <button onclick="saveAbsentAnimalsFromControl()" class="px-5 py-2 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-lg hover:from-amber-700 hover:to-orange-700 transition-all font-semibold">
                            Salvar Alterações
                        </button>
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
                            <select id="qualityPeriod" class="px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none bg-white shadow-sm hover:shadow transition-all">
                                <option value="today">Hoje</option>
                                <option value="week">Esta Semana</option>
                                <option value="month">Este Mês</option>
                            </select>
                            <button onclick="showQualityOverlay()" class="px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg hover:from-green-700 hover:to-emerald-700 hover:shadow-xl transition-all duration-200 font-semibold text-sm flex items-center justify-center gap-2 shadow-lg hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Teste
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
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Registros de Qualidade</h3>
                        <button onclick="showDeleteAllQualityModal()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 hover:shadow-lg transition-all font-semibold text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Excluir Tudo
                        </button>
                    </div>
                    <!-- Cards Mobile (visível apenas em telas < 768px) -->
                    <div id="qualityRecordsCards" class="md:hidden space-y-3">
                        <div class="text-center py-8 text-gray-500">Carregando registros...</div>
                    </div>
                    
                    <!-- Tabela Desktop (visível apenas em telas >= 768px) -->
                    <div class="hidden md:block overflow-x-auto">
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
                            <select id="financialPeriod" class="px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none bg-white shadow-sm hover:shadow transition-all">
                                <option value="today">Hoje</option>
                                <option value="week">Esta Semana</option>
                                <option value="month">Este Mês</option>
                            </select>
                            <button onclick="showSalesOverlay()" class="px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg hover:from-green-700 hover:to-emerald-700 hover:shadow-xl transition-all duration-200 font-semibold text-sm flex items-center justify-center gap-2 shadow-lg hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                Venda
                            </button>
                            <button onclick="showExpenseOverlay()" class="px-5 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 hover:shadow-xl transition-all duration-200 font-semibold text-sm flex items-center justify-center gap-2 shadow-lg hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Despesa
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
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Registros Financeiros</h3>
                        <button onclick="showDeleteAllFinancialModal()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 hover:shadow-lg transition-all font-semibold text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Excluir Tudo
                        </button>
                    </div>
                    <!-- Cards Mobile (visível apenas em telas < 768px) -->
                    <div id="financialRecordsCards" class="md:hidden space-y-3">
                        <div class="text-center py-8 text-gray-500">Carregando registros...</div>
                    </div>
                    
                    <!-- Tabela Desktop (visível apenas em telas >= 768px) -->
                    <div class="hidden md:block overflow-x-auto">
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
            <!-- View: Lista de Usuários -->
            <div id="usersListView" class="space-y-6">
                <!-- Users Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Gestão de Usuários</h2>
                            <p class="text-slate-600 text-sm">Gerencie funcionários e suas permissões</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <button onclick="showAddUserFullScreen()" class="px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg hover:from-green-700 hover:to-emerald-700 hover:shadow-xl transition-all duration-200 font-semibold text-sm flex items-center justify-center gap-2 shadow-lg hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
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
                    
                    <!-- Cards Mobile (visível apenas em telas < 768px) -->
                    <div id="usersCards" class="md:hidden space-y-3">
                        <div class="text-center py-8 text-gray-500">Carregando usuários...</div>
                    </div>
                    
                    <!-- Tabela Desktop (visível apenas em telas >= 768px) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Foto</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Nome</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Email</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Cargo</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="usersTable">
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-500">Carregando usuários...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- View: Adicionar Usuário Full Screen -->
            <div id="addUserFullScreen" class="hidden fixed inset-0 z-50 bg-white overflow-y-auto">
                <!-- Header Clean -->
                <div class="sticky top-0 z-10 bg-white border-b border-gray-200">
                    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <button onclick="closeAddUserFullScreen()" class="text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg p-2 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-900">Novo Usuário</h2>
                                    <p class="text-sm text-gray-500 mt-0.5">Criar nova conta no sistema</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <form id="addUserForm" class="space-y-8">
                        <!-- Mensagem de sucesso/erro -->
                        <div id="addUserMessage" class="hidden p-4 rounded-lg border"></div>

                        <!-- Informações Pessoais -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 mb-4">Informações Pessoais</h3>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                                Nome Completo
                                            </label>
                                            <input type="text" name="name" id="userNameInput" required placeholder="Nome completo" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 placeholder-gray-400">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                                Email
                                            </label>
                                            <input type="email" name="email" id="userEmailInput" required placeholder="usuario@lactech.com" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 placeholder-gray-400">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                            Telefone <span class="text-gray-400 font-normal">(opcional)</span>
                                        </label>
                                        <input type="tel" name="phone" placeholder="(00) 00000-0000" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 placeholder-gray-400">
                                    </div>
                                </div>
                            </div>

                            <!-- Credenciais -->
                            <div class="pt-6 border-t border-gray-200">
                                <h3 class="text-base font-semibold text-gray-900 mb-4">Credenciais de Acesso</h3>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                                Senha
                                            </label>
                                            <div class="relative">
                                                <input type="password" name="password" id="userPassword" required placeholder="Mínimo 6 caracteres" class="w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 placeholder-gray-400">
                                                <button type="button" onclick="toggleUserPasswordVisibility('userPassword', 'userPasswordToggle')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" id="userPasswordToggle">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                                Confirmar Senha
                                            </label>
                                            <div class="relative">
                                                <input type="password" name="confirm_password" id="userConfirmPassword" required placeholder="Digite a senha novamente" class="w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 placeholder-gray-400">
                                                <button type="button" onclick="toggleUserPasswordVisibility('userConfirmPassword', 'userConfirmPasswordToggle')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" id="userConfirmPasswordToggle">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Permissões -->
                            <div class="pt-6 border-t border-gray-200">
                                <h3 class="text-base font-semibold text-gray-900 mb-4">Permissões</h3>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                        Papel no Sistema
                                    </label>
                                    <select name="role" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900">
                                        <option value="">Selecione o papel...</option>
                                        <option value="funcionario">Funcionário</option>
                                        <option value="gerente">Gerente</option>
                                        <option value="proprietario">Proprietário</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="sticky bottom-0 bg-white border-t border-gray-200 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-4 flex justify-end gap-3">
                            <button type="button" onclick="closeAddUserFullScreen()" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all">
                                Cancelar
                            </button>
                            <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Adicionar Usuário
                            </button>
                        </div>
                    </form>
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

    <!-- ============================================ -->
    <!-- MODAIS - FORMULÁRIOS E DIÁLOGOS -->
    <!-- ============================================ -->
    <!-- NOTA: Modais principais foram movidos para páginas separadas em subs/ -->
    <!-- O sistema modal-loader.js carrega essas páginas dinamicamente -->
    
    <!-- Modal Volume Geral -->
    <div id="generalVolumeOverlay" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeGeneralVolumeModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md pointer-events-auto">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <div>
                        <h3 class="text-xl font-bold text-white">Registrar Volume Geral</h3>
                        <p class="text-sm text-white/90">Volume total da produção</p>
                    </div>
                    <button type="button" onclick="closeGeneralVolumeModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form id="generalVolumeForm" class="p-6 space-y-4">
                    <div id="generalVolumeMessage" class="hidden p-3 rounded"></div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Data</label>
                            <input type="date" name="collection_date" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Período</label>
                            <select name="period" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                                <option value="manha">Manhã</option>
                                <option value="tarde">Tarde</option>
                                <option value="noite">Noite</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Número de Animais na Ordenha</label>
                        <input type="number" name="total_animals" id="totalAnimalsInput" min="1" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                        <p class="text-xs text-gray-500 mt-1">Quantos animais participaram desta ordenha?</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Volume Total (L)</label>
                        <input type="number" name="total_volume" step="0.1" min="0" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Temperatura (°C) <span class="text-xs font-normal text-gray-500">(opcional)</span></label>
                        <input type="number" name="temperature" step="0.1" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>

                    <input type="hidden" name="absent_animals" id="absentAnimalsInput" value="">

                    <div class="flex gap-3 pt-4 border-t">
                        <button type="button" onclick="closeGeneralVolumeModal()" class="flex-1 px-4 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-700 text-white rounded-lg hover:from-green-700 hover:to-emerald-800 transition-all shadow-lg">
                            Registrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Animais Ausentes -->
    <div id="absentAnimalsOverlay" class="fixed inset-0 z-[60] hidden">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeAbsentAnimalsModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden pointer-events-auto flex flex-col">
                <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-white">Animais Ausentes da Ordenha</h3>
                        <p class="text-sm text-white/90">Marque os animais que NÃO estão participando desta ordenha</p>
                    </div>
                    <button type="button" onclick="closeAbsentAnimalsModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    <div id="absentAnimalsList" class="space-y-2">
                        <p class="text-center text-gray-500 py-8">Carregando animais...</p>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t flex justify-end gap-3">
                    <button type="button" onclick="closeAbsentAnimalsModal()" class="px-4 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all">
                        Cancelar
                    </button>
                    <button type="button" onclick="saveAbsentAnimals()" class="px-4 py-2 bg-gradient-to-r from-amber-600 to-orange-700 text-white rounded-lg hover:from-amber-700 hover:to-orange-800 transition-all">
                        Salvar Seleção
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Volume por Animal -->
    <div id="volumeOverlay" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeVolumeModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md pointer-events-auto">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <div>
                        <h3 class="text-xl font-bold text-white">Registrar Volume por Animal</h3>
                        <p class="text-sm text-white/90">Volume individual da vaca</p>
                    </div>
                    <button type="button" onclick="closeVolumeModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form id="volumeForm" class="p-6 space-y-4">
                    <div id="volumeMessage" class="hidden p-3 rounded"></div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Animal</label>
                        <select name="animal_id" id="volumeAnimalSelect" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="">Selecione uma vaca...</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Data</label>
                            <input type="date" name="collection_date" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Período</label>
                            <select name="period" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="manha">Manhã</option>
                                <option value="tarde">Tarde</option>
                                <option value="noite">Noite</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Volume (L)</label>
                        <input type="number" name="volume" step="0.1" min="0" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Temperatura (°C) <span class="text-xs font-normal text-gray-500">(opcional)</span></label>
                        <input type="number" name="temperature" step="0.1" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="flex gap-3 pt-4 border-t">
                        <button type="button" onclick="closeVolumeModal()" class="flex-1 px-4 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg">
                            Registrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Exclusão de Todos os Registros de Volume -->
    <div id="deleteAllVolumeModal" class="fixed inset-0 z-50 hidden animate-fadeIn">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeDeleteAllVolumeModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md pointer-events-auto animate-slideUp">
                <!-- Header -->
                <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Confirmar Exclusão</h3>
                            <p class="text-sm text-white/90">Atenção: Esta ação não pode ser desfeita facilmente</p>
                        </div>
                    </div>
                    <button onclick="closeDeleteAllVolumeModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-4">
                    <div class="bg-red-50 border-2 border-red-200 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div>
                                <h4 class="font-bold text-red-900 mb-2">Tem certeza que deseja excluir TODOS os registros de volume?</h4>
                                <p class="text-sm text-red-800 mb-2">
                                    Esta ação irá <strong>excluir permanentemente</strong> todos os registros de volume do sistema.
                                </p>
                                <p class="text-sm text-red-800 mb-2">
                                    Um backup será criado automaticamente, mas recomendamos que você tenha certeza antes de prosseguir.
                                </p>
                                <p id="volumeRecordsCount" class="text-sm font-semibold text-red-900 mt-3">
                                    Carregando quantidade de registros...
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-yellow-800">
                                <strong>Importante:</strong> Você poderá desfazer esta ação através do botão "Desfazer" que aparecerá após a exclusão.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                    <button type="button" onclick="closeDeleteAllVolumeModal()" class="px-6 py-3 text-sm font-semibold border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 transition-all">
                        Cancelar
                    </button>
                    <button type="button" onclick="confirmDeleteAllVolume(event)" class="px-6 py-3 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Sim, Excluir Todos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Sucesso Restauração de Registros -->
    <div id="restoreVolumeSuccessModal" class="fixed inset-0 z-50 hidden animate-fadeIn">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeRestoreVolumeSuccessModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md pointer-events-auto animate-slideUp">
                <!-- Header -->
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Sucesso!</h3>
                            <p class="text-sm text-white/90">Registros restaurados</p>
                        </div>
                    </div>
                    <button onclick="closeRestoreVolumeSuccessModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-4">
                    <div class="bg-green-50 border-2 border-green-200 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-green-900 mb-2">Registros Restaurados com Sucesso!</h4>
                                <p id="restoreSuccessMessage" class="text-sm text-green-800 mb-2">
                                    Todos os registros de volume foram restaurados com sucesso.
                                </p>
                                <p id="restoreSuccessCount" class="text-sm font-semibold text-green-900 mt-3">
                                    Carregando informações...
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-blue-800">
                                Os dados foram restaurados e já estão disponíveis na tabela de registros.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                    <button type="button" onclick="closeRestoreVolumeSuccessModal()" class="px-6 py-3 text-sm font-semibold bg-gradient-to-r from-green-600 to-emerald-700 text-white rounded-xl hover:from-green-700 hover:to-emerald-800 transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Entendi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Erro Restauração de Registros -->
    <div id="restoreVolumeErrorModal" class="fixed inset-0 z-50 hidden animate-fadeIn">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeRestoreVolumeErrorModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md pointer-events-auto animate-slideUp">
                <!-- Header -->
                <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Erro ao Restaurar</h3>
                            <p class="text-sm text-white/90">Falha na restauração</p>
                        </div>
                    </div>
                    <button onclick="closeRestoreVolumeErrorModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-4">
                    <div class="bg-red-50 border-2 border-red-200 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-red-900 mb-2">Não foi possível restaurar os registros</h4>
                                <p id="restoreErrorMessage" class="text-sm text-red-800 mb-2">
                                    Ocorreu um erro ao tentar restaurar os registros.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-amber-800">
                                O backup pode ter expirado ou não foi encontrado. Os registros não foram restaurados.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                    <button type="button" onclick="closeRestoreVolumeErrorModal()" class="px-6 py-3 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Entendi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Registrar Despesa -->
    <div id="expenseOverlay" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeExpenseModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md pointer-events-auto">
                <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <div>
                        <h3 class="text-xl font-bold text-white">Registrar Despesa</h3>
                        <p class="text-sm text-white/90">Registro de despesa financeira</p>
                    </div>
                    <button type="button" onclick="closeExpenseModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form id="expenseForm" class="p-6 space-y-4">
                    <div id="expenseMessage" class="hidden p-3 rounded"></div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Data da Despesa</label>
                        <input type="date" name="record_date" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <input type="text" name="description" placeholder="Descrição da despesa" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Valor (R$)</label>
                        <input type="number" name="amount" step="0.01" min="0" placeholder="0.00" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Observações <span class="text-xs font-normal text-gray-500">(opcional)</span></label>
                        <textarea name="notes" rows="3" placeholder="Informações adicionais sobre a despesa" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"></textarea>
                    </div>

                    <div class="flex gap-3 pt-4 border-t">
                        <button type="button" onclick="closeExpenseModal()" class="flex-1 px-4 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all shadow-lg">
                            Registrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Exclusão de Todos os Registros Financeiros -->
    <div id="deleteAllFinancialModal" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeDeleteAllFinancialModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md pointer-events-auto">
                <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <div>
                        <h3 class="text-xl font-bold text-white">Confirmar Exclusão</h3>
                        <p class="text-sm text-white/90">Atenção: Esta ação não pode ser desfeita</p>
                    </div>
                    <button type="button" onclick="closeDeleteAllFinancialModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="bg-red-50 border-2 border-red-200 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <h4 class="font-bold text-red-900 mb-2">Tem certeza que deseja excluir TODOS os registros financeiros?</h4>
                                <p class="text-sm text-red-700">
                                    Esta ação irá <strong>permanentemente</strong> excluir todos os registros de receitas e despesas. 
                                    Esta operação <strong>não pode ser desfeita</strong>.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div id="deleteAllFinancialMessage" class="hidden p-3 rounded"></div>

                    <div class="flex gap-3 pt-4 border-t">
                        <button type="button" onclick="closeDeleteAllFinancialModal()" class="flex-1 px-4 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all">
                            Cancelar
                        </button>
                        <button type="button" onclick="confirmDeleteAllFinancialRecords()" class="flex-1 px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all shadow-lg flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Sim, Excluir Tudo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Exclusão de Todos os Registros de Qualidade -->
    <div id="deleteAllQualityModal" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeDeleteAllQualityModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md pointer-events-auto">
                <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <div>
                        <h3 class="text-xl font-bold text-white">Confirmar Exclusão</h3>
                        <p class="text-sm text-white/90">Atenção: Esta ação não pode ser desfeita</p>
                    </div>
                    <button type="button" onclick="closeDeleteAllQualityModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="bg-red-50 border-2 border-red-200 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <h4 class="font-bold text-red-900 mb-2">Tem certeza que deseja excluir TODOS os registros de qualidade?</h4>
                                <p class="text-sm text-red-700">
                                    Esta ação irá <strong>permanentemente</strong> excluir todos os testes de qualidade (gordura, proteína, CCS, etc.). 
                                    Esta operação <strong>não pode ser desfeita</strong>.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div id="deleteAllQualityMessage" class="hidden p-3 rounded"></div>

                    <div class="flex gap-3 pt-4 border-t">
                        <button type="button" onclick="closeDeleteAllQualityModal()" class="flex-1 px-4 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all">
                            Cancelar
                        </button>
                        <button type="button" onclick="confirmDeleteAllQualityRecords()" class="flex-1 px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all shadow-lg flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Sim, Excluir Tudo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Registrar Venda -->
    <div id="salesOverlay" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeSalesModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md pointer-events-auto">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <div>
                        <h3 class="text-xl font-bold text-white">Registrar Venda</h3>
                        <p class="text-sm text-white/90">Registro de receita financeira</p>
                    </div>
                    <button type="button" onclick="closeSalesModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form id="salesForm" class="p-6 space-y-4">
                    <div id="salesMessage" class="hidden p-3 rounded"></div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Data da Venda</label>
                        <input type="date" name="sale_date" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Cliente</label>
                        <input type="text" name="customer" placeholder="Nome do cliente" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Valor Total (R$)</label>
                        <input type="number" name="total_amount" step="0.01" min="0" placeholder="0.00" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Observações <span class="text-xs font-normal text-gray-500">(opcional)</span></label>
                        <textarea name="notes" rows="3" placeholder="Informações adicionais sobre a venda" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
                    </div>

                    <div class="flex gap-3 pt-4 border-t">
                        <button type="button" onclick="closeSalesModal()" class="flex-1 px-4 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-700 text-white rounded-lg hover:from-green-700 hover:to-emerald-800 transition-all shadow-lg">
                            Registrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Teste de Qualidade -->
    <div id="qualityOverlay" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeQualityModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md pointer-events-auto">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <div>
                        <h3 class="text-xl font-bold text-white">Registrar Teste de Qualidade</h3>
                        <p class="text-sm text-white/90">Análise da qualidade do leite</p>
                    </div>
                    <button type="button" onclick="closeQualityModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form id="qualityForm" class="p-6 space-y-4">
                    <div id="qualityMessage" class="hidden p-3 rounded"></div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Data do Teste</label>
                        <input type="date" name="test_date" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Gordura (%)</label>
                            <input type="number" name="fat_content" step="0.01" min="0" max="100" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Proteína (%)</label>
                            <input type="number" name="protein_content" step="0.01" min="0" max="100" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">CBT (CFU/mL)</label>
                            <input type="number" name="bacteria_count" step="1" min="0" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Células Somáticas (células/mL) <span class="text-xs font-normal text-gray-500">(opcional)</span></label>
                            <input type="number" name="somatic_cells" step="1" min="0" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4 border-t">
                        <button type="button" onclick="closeQualityModal()" class="flex-1 px-4 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-700 text-white rounded-lg hover:from-green-700 hover:to-emerald-800 transition-all shadow-lg">
                            Registrar
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
                                                        break;
                                                    }
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
                                <div class="space-y-5">
                                    <!-- Vinculação Google - ATIVADO -->
                                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                                </svg>
                                                <span class="text-sm font-medium text-gray-900">Conta Google</span>
                                            </div>
                                            <span id="googleAccountStatus" class="text-xs px-2 py-1 rounded-lg bg-gray-200 text-gray-700">
                                                Não vinculada
                                            </span>
                                        </div>
                                        <p id="googleAccountEmail" class="text-xs text-gray-600 mb-3 hidden font-medium">
                                            Email vinculado: <span class="text-green-600"></span>
                                        </p>
                                        <p id="googleAccountNotLinkedText" class="text-xs text-gray-600 mb-3">Vincule sua conta Google para receber códigos OTP por e-mail e alterar sua senha.</p>
                                        <button type="button" onclick="linkGoogleAccount()" id="linkGoogleBtn" class="w-full px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                            </svg>
                                            Vincular Conta Google
                                        </button>
                                        <button type="button" onclick="openGoogleAccountSettings()" id="googleAccountSettingsBtn" class="hidden w-full px-4 py-2 text-sm font-medium bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center gap-2 mt-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            Configurações da Conta
                                        </button>
                                        <button type="button" onclick="unlinkGoogleAccount()" id="unlinkGoogleBtn" class="hidden w-full px-4 py-2 text-sm font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center gap-2 mt-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Desvincular Conta Google
                                        </button>
                                    </div>

                                    <!-- Autenticação de Dois Fatores (2FA) - DESATIVADO -->
                                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 hidden">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                                </svg>
                                                <span class="text-sm font-medium text-gray-900">Autenticação de Dois Fatores (2FA)</span>
                                            </div>
                                            <span id="twoFactorStatus" class="text-xs px-2 py-1 rounded-lg bg-gray-200 text-gray-700">
                                                Desativado
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-600 mb-3">Adicione uma camada extra de segurança com autenticação de dois fatores.</p>
                                        <button type="button" onclick="setup2FA()" id="setup2FABtn" class="w-full px-4 py-2 text-sm font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Configurar 2FA
                                        </button>
                                        <button type="button" onclick="disable2FA()" id="disable2FABtn" class="hidden w-full px-4 py-2 text-sm font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center gap-2 mt-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Desativar 2FA
                                        </button>
                                    </div>

                                    <!-- Alteração de Senha -->
                                    <div class="border-t border-gray-200 pt-4">
                                        <div class="flex items-center gap-2 mb-3">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                            </svg>
                                            <label class="text-sm font-medium text-gray-700">Alterar Senha</label>
                                        </div>
                                        <!-- AVISO DE SENHA - REMOVIDO (não precisa mais de Google) -->
                                        <div id="passwordChangeWarning" class="mb-3 hidden">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Nova Senha</label>
                                            <div class="relative">
                                                <input type="password" id="profileNewPassword" class="w-full px-4 py-2.5 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 profile-input" placeholder="••••••••">
                                                <button type="button" onclick="toggleProfilePasswordVisibility('profileNewPassword', 'profilePasswordToggle')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 transition-colors" id="profilePasswordToggle">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Senha</label>
                                            <input type="password" id="profileConfirmPassword" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 profile-input" placeholder="••••••••">
                                        </div>
                                    </div>
                                    
                                    <!-- Ecossistema - Apps Interligados -->
                                    <div class="border-t border-gray-200 pt-4 mt-4">
                                        <div class="flex items-center gap-2 mb-4">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                            </svg>
                                            <label class="text-sm font-medium text-gray-700">Ecossistema</label>
                                        </div>
                                        <p class="text-xs text-gray-500 mb-4">Apps interligados ao sistema Lactech</p>
                                        
                                        <!-- AgroNews360 -->
                                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 hover:shadow-md transition-all">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-md">
                                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <h5 class="font-semibold text-gray-900 text-sm">AgroNews360</h5>
                                                        <p class="text-xs text-gray-600">Portal de notícias do agronegócio</p>
                                                    </div>
                                                </div>
                                                <a href="agronews360/auto-login.php" target="_blank" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg transition-colors flex items-center gap-2">
                                                    <span>Acessar</span>
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                    </svg>
                                                </a>
                                            </div>
                                            <div class="mt-3 pt-3 border-t border-green-200">
                                                <div class="flex items-center gap-2 text-xs text-gray-600">
                                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span>Conectado ao sistema Lactech</span>
                                                </div>
                                            </div>
                                        </div>
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
                                        <input type="email" id="profileEmail" disabled class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed" value="<?php echo htmlspecialchars($current_user_email ?? $_SESSION['user_email'] ?? ''); ?>">
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
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Telefone da Fazenda</label>
                                        <input type="tel" id="farmPhone" disabled class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed profile-input" value="<?php echo htmlspecialchars($farm_phone); ?>">
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
                                        <div class="flex items-center gap-3 flex-1">
                                            <svg id="location-permission-icon-granted" class="w-5 h-5 text-green-600 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <svg id="location-permission-icon-denied" class="w-5 h-5 text-red-600 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                            <svg id="location-permission-icon-prompt" class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <div class="flex-1">
                                                <span class="text-sm font-medium text-gray-900 block">Permissão de Localização</span>
                                                <span id="location-permission-status" class="text-xs text-gray-600">Verificando...</span>
                                            </div>
                                        </div>
                                        <button id="location-permission-btn" onclick="manageLocationPermission()" class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 border border-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
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
                                            <input type="checkbox" id="pushNotifications" class="sr-only peer" onchange="togglePushNotifications(this.checked)">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                                    </div>
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <span class="text-sm font-medium text-gray-700">Ações na Conta</span>
                                        </div>
                                        <button onclick="openAccountActionsModal()" class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 border border-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                                            Ver Histórico
                                        </button>
                                    </div>
                                    <div id="pwa-install-container" class="hidden flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            <div>
                                                <span class="text-sm font-medium text-gray-900 block">Instalar App</span>
                                                <span class="text-xs text-gray-600">Baixe o aplicativo para acesso offline</span>
                                            </div>
                                        </div>
                                        <button id="pwa-install-btn" onclick="installPWA()" class="px-4 py-2 text-sm font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            Instalar
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                            </svg>
                                            <div>
                                                <span class="text-sm font-medium text-gray-900 block">Modo Escuro</span>
                                                <span class="text-xs text-gray-500">Em desenvolvimento</span>
                                            </div>
                                        </div>
                                        <button id="darkModeToggle" class="dark-mode-toggle opacity-50 cursor-not-allowed" title="Em desenvolvimento" aria-label="Modo escuro em desenvolvimento" disabled>
                                            <div class="dark-mode-toggle-slider">
                                                <svg class="sun-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                                </svg>
                                                <svg class="moon-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                                </svg>
                                            </div>
                                        </button>
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

            <!-- Footer de Proteção -->
            <div class="border-t border-gray-200 bg-gray-50">
                <div class="max-w-7xl mx-auto px-6 lg:px-8 py-4">
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-6">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-medium text-gray-600">Protegido por:</span>
                            <a href="https://www.cloudflare.com" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-2 text-gray-600 hover:text-gray-900 transition-colors">
                                <img src="./assets/video/cloudflare_icon_130969-removebg-preview.png" alt="Cloudflare" class="w-5 h-5 object-contain">
                                <span class="text-xs font-medium">Cloudflare</span>
                            </a>
                            <span class="text-gray-400">|</span>
                            <div class="flex items-center space-x-2 text-gray-600">
                                <img src="./assets/img/lactech-logo.png" alt="LacTech" class="w-5 h-5 object-contain">
                                <span class="text-xs font-medium">SafeNode</span>
                            </div>
                        </div>
                    </div>
                </div>
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

    <!-- Modal de Confirmação de Desvincular Google -->
    <div id="unlinkGoogleModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeUnlinkGoogleModal()"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
            <!-- Ícone de alerta -->
            <div class="flex justify-center mb-4">
                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            
            <!-- Título -->
            <h3 class="text-xl font-bold text-gray-900 text-center mb-2">Desvincular Conta Google</h3>
            <p class="text-sm text-gray-600 text-center mb-6">Tem certeza que deseja desvincular sua conta Google?<br><strong class="text-red-600">Você não poderá mais alterar sua senha.</strong></p>
            
            <!-- Botões -->
            <div class="flex gap-3">
                <button onclick="closeUnlinkGoogleModal()" class="flex-1 px-4 py-2.5 text-sm font-medium border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
                <button onclick="confirmUnlinkGoogle()" class="flex-1 px-4 py-2.5 text-sm font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Sim, Desvincular
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Configurações da Conta Google -->
    <div id="googleSettingsModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeGoogleSettingsModal()"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Configurações da Conta Google</h3>
                </div>
                <button onclick="closeGoogleSettingsModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Conteúdo -->
            <div class="space-y-4">
                <!-- Email vinculado -->
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Email Vinculado</label>
                    <p id="googleSettingsEmail" class="text-sm font-medium text-gray-900">Carregando...</p>
                </div>
                
                <!-- Nome -->
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Nome</label>
                    <p id="googleSettingsName" class="text-sm font-medium text-gray-900">Carregando...</p>
                </div>
                
                <!-- Data de vinculação -->
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Vinculada em</label>
                    <p id="googleSettingsLinkedAt" class="text-sm font-medium text-gray-900">Carregando...</p>
                </div>
                
                <!-- Informações -->
                <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-xs text-yellow-800">
                        <strong>Atenção:</strong> Esta conta será usada para receber códigos OTP e alterar sua senha.
                    </p>
                </div>
            </div>
            
            <!-- Botões -->
            <div class="flex gap-3 mt-6">
                <button onclick="closeGoogleSettingsModal()" class="flex-1 px-4 py-2.5 text-sm font-medium border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Fechar
                </button>
                <button onclick="closeGoogleSettingsModal(); setTimeout(() => unlinkGoogleAccount(), 300);" class="flex-1 px-4 py-2.5 text-sm font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Desvincular Conta
                </button>
            </div>
        </div>
    </div>

    <!-- Modal OTP e Senha -->
    <div id="otpPasswordModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeOtpPasswordModal()"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900" id="otpModalTitle">Verificação de Segurança</h3>
                </div>
                <button onclick="closeOtpPasswordModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Conteúdo -->
            <div class="space-y-4">
                <p class="text-sm text-gray-600 mb-4" id="otpModalMessage">
                    Digite o código OTP enviado para seu e-mail:
                </p>
                
                <!-- OTP Input -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Código OTP</label>
                    <input type="text" id="otpCodeInput" maxlength="6" pattern="[0-9]{6}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-center text-2xl tracking-widest" placeholder="000000" autocomplete="off">
                    <p class="text-xs text-gray-500 mt-1">Digite o código de 6 dígitos</p>
                </div>
                
                <!-- Password Input -->
                <div id="otpPasswordInputContainer" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Senha Atual</label>
                    <div class="relative">
                        <input type="password" id="otpPasswordInput" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Digite sua senha atual" autocomplete="current-password">
                        <button type="button" onclick="toggleOtpPasswordVisibility()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <svg id="otpPasswordEye" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Botões -->
            <div class="flex gap-3 mt-6">
                <button onclick="closeOtpPasswordModal()" class="flex-1 px-4 py-2.5 text-sm font-medium border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
                <button onclick="submitOtpPassword()" id="otpSubmitBtn" class="flex-1 px-4 py-2.5 text-sm font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Ações na Conta -->
    <div id="accountActionsModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeAccountActionsModal()"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto transform transition-all">
            <!-- Header -->
            <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-2xl flex items-center justify-between z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Ações na Conta</h3>
                        <p class="text-blue-100 text-sm">Histórico de alterações e atividades</p>
                    </div>
                </div>
                <button onclick="closeAccountActionsModal()" class="w-10 h-10 flex items-center justify-center bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl transition-all text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <!-- Alterações de Senha -->
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Alterações de Senha
                    </h4>
                    <div id="passwordChangesList" class="space-y-3">
                        <div class="flex items-center justify-center py-8">
                            <div class="text-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
                                <p class="text-sm text-gray-500">Carregando histórico...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Outras Ações -->
                <div class="border-t border-gray-200 pt-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Outras Ações
                    </h4>
                    <div id="otherActionsList" class="space-y-3">
                        <div class="flex items-center justify-center py-4">
                            <div class="text-center">
                                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto mb-2"></div>
                                <p class="text-xs text-gray-500">Carregando...</p>
                            </div>
                        </div>
                    </div>
                </div>
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

    <!-- ============================================ -->
    <!-- SCRIPTS EXTERNOS -->
    <!-- ============================================ -->
    <script src="assets/js/offline-manager.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/gerente-completo.js?v=<?php echo $v; ?>"></script>
    
    <!-- Script inline para garantir que as funções de fechar modais estejam disponíveis -->
    <script>
        // Garantir que as funções estejam disponíveis imediatamente
        if (typeof window.closeGeneralVolumeModal === 'undefined') {
            window.closeGeneralVolumeModal = function() {
                const modal = document.getElementById('generalVolumeOverlay');
                if (modal) {
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }
                const form = document.getElementById('generalVolumeForm');
                if (form) form.reset();
                const msg = document.getElementById('generalVolumeMessage');
                if (msg) {
                    msg.classList.add('hidden');
                    msg.textContent = '';
                }
            };
        }
        
        if (typeof window.closeVolumeModal === 'undefined') {
            window.closeVolumeModal = function() {
                const modal = document.getElementById('volumeOverlay');
                if (modal) {
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }
                const form = document.getElementById('volumeForm');
                if (form) form.reset();
                const msg = document.getElementById('volumeMessage');
                if (msg) {
                    msg.classList.add('hidden');
                    msg.textContent = '';
                }
            };
        }
        
        if (typeof window.closeQualityModal === 'undefined') {
            window.closeQualityModal = function() {
                const modal = document.getElementById('qualityOverlay');
                if (modal) {
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }
                const form = document.getElementById('qualityForm');
                if (form) form.reset();
                const msg = document.getElementById('qualityMessage');
                if (msg) {
                    msg.classList.add('hidden');
                    msg.textContent = '';
                }
            };
        }
        
        if (typeof window.closeSalesModal === 'undefined') {
            window.closeSalesModal = function() {
                const modal = document.getElementById('salesOverlay');
                if (modal) {
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }
                const form = document.getElementById('salesForm');
                if (form) form.reset();
                const msg = document.getElementById('salesMessage');
                if (msg) {
                    msg.classList.add('hidden');
                    msg.textContent = '';
                }
            };
        }
        
        if (typeof window.closeExpenseModal === 'undefined') {
            window.closeExpenseModal = function() {
                const modal = document.getElementById('expenseOverlay');
                if (modal) {
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }
                const form = document.getElementById('expenseForm');
                if (form) form.reset();
                const msg = document.getElementById('expenseMessage');
                if (msg) {
                    msg.classList.add('hidden');
                    msg.textContent = '';
                }
            };
        }
    </script>
    
    <!-- Módulos JavaScript organizados -->
    <script src="assets/js/toast-notifications.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/modal-loader.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/native-features.js?v=<?php echo $v; ?>"></script>
    <script src="assets/js/push-notifications.js?v=<?php echo $v; ?>"></script>
    
    <!-- ============================================ -->
    <!-- JAVASCRIPT INLINE - FUNCIONALIDADES ESPECÍFICAS -->
    <!-- ============================================ -->
    
    <!-- Sistema de Skeleton Loaders -->
    <script>
        // ============================================
        // SISTEMA DE SKELETON LOADERS
        // ============================================
        (function() {
            const toastContainer = document.getElementById('toastContainer');
            const toastGroups = new Map(); // Para agrupamento de notificações similares
            const MAX_TOASTS = 5; // Máximo de toasts visíveis
            const GROUP_TIMEOUT = 1000; // Tempo para agrupar notificações similares
            
            function showToast(message, type = 'info', title = null, duration = 5000, groupKey = null) {
                if (!toastContainer) return;
                
                // Limitar número de toasts
                const existingToasts = toastContainer.querySelectorAll('.toast');
                if (existingToasts.length >= MAX_TOASTS) {
                    // Remover o mais antigo
                    const oldest = existingToasts[0];
                    oldest.classList.remove('show');
                    setTimeout(() => oldest.remove(), 400);
                }
                
                // Agrupamento de notificações similares
                if (groupKey) {
                    const existingGroup = toastGroups.get(groupKey);
                    if (existingGroup && Date.now() - existingGroup.timestamp < GROUP_TIMEOUT) {
                        // Atualizar toast existente no grupo
                        existingGroup.count++;
                        existingGroup.toast.querySelector('.toast-message').textContent = 
                            `${message} (${existingGroup.count}x)`;
                        existingGroup.timestamp = Date.now();
                        return existingGroup.toast;
                    }
                }
                
                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
                toast.setAttribute('aria-atomic', 'true');
                
                // Ícones por tipo
                const icons = {
                    success: '<svg class="toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    error: '<svg class="toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    warning: '<svg class="toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                    info: '<svg class="toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                };
                
                // Títulos padrão por tipo
                const defaultTitles = {
                    success: 'Sucesso',
                    error: 'Erro',
                    warning: 'Atenção',
                    info: 'Informação'
                };
                
                const displayTitle = title || defaultTitles[type] || 'Notificação';
                
                toast.innerHTML = `
                    ${icons[type] || icons.info}
                    <div class="toast-content">
                        <div class="toast-title">${displayTitle}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                    <button class="toast-close" onclick="this.parentElement.remove()" aria-label="Fechar notificação">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                `;
                
                toastContainer.appendChild(toast);
                
                // Trigger animation
                setTimeout(() => toast.classList.add('show'), 10);
                
                // Registrar no grupo se necessário
                if (groupKey) {
                    toastGroups.set(groupKey, {
                        toast: toast,
                        count: 1,
                        timestamp: Date.now()
                    });
                }
                
                // Auto remove
                if (duration > 0) {
                    setTimeout(() => {
                        toast.classList.remove('show');
                        setTimeout(() => {
                            toast.remove();
                            if (groupKey) {
                                toastGroups.delete(groupKey);
                            }
                        }, 400);
                    }, duration);
                }
                
                return toast;
            }
            
            // Exportar funções globais
            window.showToast = showToast;
            window.showSuccessToast = (message, title, groupKey) => showToast(message, 'success', title, 5000, groupKey);
            window.showErrorToast = (message, title, groupKey) => showToast(message, 'error', title, 7000, groupKey);
            window.showWarningToast = (message, title, groupKey) => showToast(message, 'warning', title, 6000, groupKey);
            window.showInfoToast = (message, title, groupKey) => showToast(message, 'info', title, 5000, groupKey);
        })();
        
    </script>
    
    <!-- Sistema de Skeleton Loaders -->
    <script>
        // ============================================
        // SISTEMA DE SKELETON LOADERS
        // ============================================
        (function() {
            // Criar skeleton loader
            function createSkeleton(type = 'text', count = 1) {
                const skeletons = [];
                for (let i = 0; i < count; i++) {
                    const skeleton = document.createElement('div');
                    skeleton.className = `skeleton skeleton-${type}`;
                    skeleton.setAttribute('aria-hidden', 'true');
                    skeletons.push(skeleton);
                }
                return skeletons.length === 1 ? skeletons[0] : skeletons;
            }
            
            // Criar skeleton card
            function createSkeletonCard() {
                const card = document.createElement('div');
                card.className = 'skeleton-card';
                card.setAttribute('aria-hidden', 'true');
                return card;
            }
            
            // Criar skeleton para lista
            function createSkeletonList(count = 3) {
                const container = document.createElement('div');
                container.className = 'space-y-4';
                container.setAttribute('aria-hidden', 'true');
                
                for (let i = 0; i < count; i++) {
                    const item = document.createElement('div');
                    item.className = 'flex items-center space-x-4';
                    item.innerHTML = `
                        <div class="skeleton skeleton-avatar"></div>
                        <div class="flex-1 space-y-2">
                            <div class="skeleton skeleton-text" style="width: 60%;"></div>
                            <div class="skeleton skeleton-text" style="width: 40%;"></div>
                        </div>
                    `;
                    container.appendChild(item);
                }
                
                return container;
            }
            
            // Mostrar skeleton loader em um elemento
            function showSkeleton(element, type = 'text', count = 1) {
                if (!element) return;
                
                const skeletons = createSkeleton(type, count);
                if (Array.isArray(skeletons)) {
                    element.innerHTML = '';
                    skeletons.forEach(s => element.appendChild(s));
                } else {
                    element.innerHTML = '';
                    element.appendChild(skeletons);
                }
            }
            
            // Esconder skeleton loader
            function hideSkeleton(element) {
                if (!element) return;
                const skeletons = element.querySelectorAll('.skeleton');
                skeletons.forEach(s => s.remove());
            }
            
            // Exportar funções
            window.createSkeleton = createSkeleton;
            window.createSkeletonCard = createSkeletonCard;
            window.createSkeletonList = createSkeletonList;
            window.showSkeleton = showSkeleton;
            window.hideSkeleton = hideSkeleton;
        })();
        
        // ============================================
        // MELHORIAS DE ACESSIBILIDADE
        // ============================================
        (function() {
            // Adicionar atributos ARIA em elementos interativos
            function enhanceAccessibility() {
                // Adicionar aria-label em botões sem texto
                document.querySelectorAll('button:not([aria-label]):not([aria-labelledby])').forEach(btn => {
                    if (!btn.textContent.trim() && !btn.querySelector('svg[aria-label]')) {
                        const icon = btn.querySelector('svg');
                        if (icon) {
                            // Tentar inferir do contexto
                            const parent = btn.closest('[aria-label]');
                            if (parent) {
                                btn.setAttribute('aria-label', parent.getAttribute('aria-label'));
                            } else {
                                btn.setAttribute('aria-label', 'Botão');
                            }
                        }
                    }
                });
                
                // Adicionar role em elementos que se comportam como botões
                document.querySelectorAll('[onclick]:not(button):not(a)').forEach(el => {
                    if (!el.getAttribute('role')) {
                        el.setAttribute('role', 'button');
                        el.setAttribute('tabindex', '0');
                    }
                });
                
                // Melhorar navegação por teclado em elementos com role="button"
                document.querySelectorAll('[role="button"]').forEach(el => {
                    el.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            this.click();
                        }
                    });
                });
                
                // Adicionar aria-live em regiões dinâmicas
                const mainContent = document.getElementById('main-content') || document.querySelector('main');
                if (mainContent && !mainContent.getAttribute('aria-live')) {
                    mainContent.setAttribute('aria-live', 'polite');
                    mainContent.setAttribute('aria-atomic', 'false');
                }
            }
            
            // Melhorar navegação por teclado em modais
            function enhanceModalAccessibility(modal) {
                if (!modal) return;
                
                // Focar no primeiro elemento focável ao abrir
                const firstFocusable = modal.querySelector(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                if (firstFocusable) {
                    setTimeout(() => firstFocusable.focus(), 100);
                }
                
                // Trap de foco dentro do modal
                const focusableElements = modal.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];
                
                modal.addEventListener('keydown', function(e) {
                    if (e.key === 'Tab') {
                        if (e.shiftKey) {
                            if (document.activeElement === firstElement) {
                                e.preventDefault();
                                lastElement.focus();
                            }
                        } else {
                            if (document.activeElement === lastElement) {
                                e.preventDefault();
                                firstElement.focus();
                            }
                        }
                    }
                    if (e.key === 'Escape') {
                        const closeBtn = modal.querySelector('[aria-label*="Fechar"], [aria-label*="Close"]');
                        if (closeBtn) closeBtn.click();
                    }
                });
            }
            
            // Adicionar indicadores de progresso
            function createProgressBar(container, value = 0, max = 100) {
                const progressBar = document.createElement('div');
                progressBar.className = 'progress-bar';
                progressBar.setAttribute('role', 'progressbar');
                progressBar.setAttribute('aria-valuenow', value);
                progressBar.setAttribute('aria-valuemin', '0');
                progressBar.setAttribute('aria-valuemax', max);
                progressBar.setAttribute('aria-label', 'Progresso');
                
                const fill = document.createElement('div');
                fill.className = 'progress-bar-fill';
                fill.style.width = `${(value / max) * 100}%`;
                
                progressBar.appendChild(fill);
                if (container) {
                    container.innerHTML = '';
                    container.appendChild(progressBar);
                }
                
                return {
                    element: progressBar,
                    update: function(newValue) {
                        const percentage = (newValue / max) * 100;
                        fill.style.width = `${percentage}%`;
                        progressBar.setAttribute('aria-valuenow', newValue);
                    }
                };
            }
            
            // Inicializar melhorias de acessibilidade
            document.addEventListener('DOMContentLoaded', function() {
                enhanceAccessibility();
                
                // Adicionar id="main-content" se não existir
                if (!document.getElementById('main-content')) {
                    const main = document.querySelector('main') || document.body;
                    main.id = 'main-content';
                    main.setAttribute('role', 'main');
                }
            });
            
            // Exportar funções
            window.enhanceAccessibility = enhanceAccessibility;
            window.enhanceModalAccessibility = enhanceModalAccessibility;
            window.createProgressBar = createProgressBar;
        })();
        
        // ============================================
        // SISTEMA DE VALIDAÇÃO DE FORMULÁRIOS
        // ============================================
        (function() {
            // Validadores
            const validators = {
                required: (value) => {
                    if (typeof value === 'string') {
                        return value.trim().length > 0;
                    }
                    return value !== null && value !== undefined && value !== '';
                },
                
                email: (value) => {
                    if (!value) return true; // Se vazio, não valida (usar required separadamente)
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return emailRegex.test(value);
                },
                
                minLength: (value, min) => {
                    if (!value) return true;
                    return value.length >= min;
                },
                
                maxLength: (value, max) => {
                    if (!value) return true;
                    return value.length <= max;
                },
                
                password: (value) => {
                    if (!value) return true;
                    // Mínimo 6 caracteres
                    return value.length >= 6;
                },
                
                passwordMatch: (value, compareValue) => {
                    if (!value) return true;
                    return value === compareValue;
                },
                
                phone: (value) => {
                    if (!value) return true;
                    // Remove caracteres não numéricos
                    const digits = value.replace(/\D/g, '');
                    return digits.length >= 10 && digits.length <= 11;
                },
                
                number: (value) => {
                    if (!value) return true;
                    return !isNaN(value) && !isNaN(parseFloat(value));
                },
                
                min: (value, min) => {
                    if (!value) return true;
                    const num = parseFloat(value);
                    return !isNaN(num) && num >= min;
                },
                
                max: (value, max) => {
                    if (!value) return true;
                    const num = parseFloat(value);
                    return !isNaN(num) && num <= max;
                },
                
                positive: (value) => {
                    if (!value) return true;
                    const num = parseFloat(value);
                    return !isNaN(num) && num > 0;
                }
            };
            
            // Mensagens de erro padrão
            const errorMessages = {
                required: 'Este campo é obrigatório',
                email: 'Email inválido',
                minLength: (min) => `Mínimo de ${min} caracteres`,
                maxLength: (max) => `Máximo de ${max} caracteres`,
                password: 'Senha deve ter no mínimo 6 caracteres',
                passwordMatch: 'As senhas não coincidem',
                phone: 'Telefone inválido',
                number: 'Digite um número válido',
                min: (min) => `Valor mínimo: ${min}`,
                max: (max) => `Valor máximo: ${max}`,
                positive: 'Digite um valor positivo'
            };
            
            // Validar campo individual
            function validateField(input, rules = {}) {
                const value = input.value;
                const fieldName = input.name || input.id;
                const formGroup = input.closest('.form-group') || input.parentElement;
                let isValid = true;
                let errorMessage = '';
                
                // Remover classes anteriores
                input.classList.remove('valid', 'invalid');
                formGroup?.classList.remove('has-error', 'has-success');
                
                // Remover mensagens anteriores
                const existingError = formGroup?.querySelector('.form-error-message');
                const existingSuccess = formGroup?.querySelector('.form-success-message');
                if (existingError) existingError.remove();
                if (existingSuccess) existingSuccess.remove();
                
                // Remover ícones anteriores
                const existingIcon = formGroup?.querySelector('.form-input-icon');
                if (existingIcon) existingIcon.remove();
                
                // Validar regras
                for (const [rule, ruleValue] of Object.entries(rules)) {
                    if (rule === 'required' && !validators.required(value)) {
                        isValid = false;
                        errorMessage = errorMessages.required;
                        break;
                    } else if (rule === 'email' && value && !validators.email(value)) {
                        isValid = false;
                        errorMessage = errorMessages.email;
                        break;
                    } else if (rule === 'minLength' && value && !validators.minLength(value, ruleValue)) {
                        isValid = false;
                        errorMessage = typeof errorMessages.minLength === 'function' 
                            ? errorMessages.minLength(ruleValue) 
                            : errorMessages.minLength;
                        break;
                    } else if (rule === 'maxLength' && value && !validators.maxLength(value, ruleValue)) {
                        isValid = false;
                        errorMessage = typeof errorMessages.maxLength === 'function' 
                            ? errorMessages.maxLength(ruleValue) 
                            : errorMessages.maxLength;
                        break;
                    } else if (rule === 'password' && value && !validators.password(value)) {
                        isValid = false;
                        errorMessage = errorMessages.password;
                        break;
                    } else if (rule === 'phone' && value && !validators.phone(value)) {
                        isValid = false;
                        errorMessage = errorMessages.phone;
                        break;
                    } else if (rule === 'number' && value && !validators.number(value)) {
                        isValid = false;
                        errorMessage = errorMessages.number;
                        break;
                    } else if (rule === 'min' && value && !validators.min(value, ruleValue)) {
                        isValid = false;
                        errorMessage = typeof errorMessages.min === 'function' 
                            ? errorMessages.min(ruleValue) 
                            : errorMessages.min;
                        break;
                    } else if (rule === 'max' && value && !validators.max(value, ruleValue)) {
                        isValid = false;
                        errorMessage = typeof errorMessages.max === 'function' 
                            ? errorMessages.max(ruleValue) 
                            : errorMessages.max;
                        break;
                    } else if (rule === 'positive' && value && !validators.positive(value)) {
                        isValid = false;
                        errorMessage = errorMessages.positive;
                        break;
                    }
                }
                
                // Aplicar feedback visual
                if (value && isValid) {
                    input.classList.add('valid');
                    formGroup?.classList.add('has-success');
                    
                    // Adicionar ícone de sucesso
                    const successIcon = document.createElement('div');
                    successIcon.className = 'form-input-icon valid-icon show';
                    successIcon.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                    formGroup?.appendChild(successIcon);
                } else if (!isValid) {
                    input.classList.add('invalid');
                    formGroup?.classList.add('has-error');
                    
                    // Adicionar mensagem de erro
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'form-error-message show';
                    errorDiv.innerHTML = `
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>${errorMessage}</span>
                    `;
                    formGroup?.appendChild(errorDiv);
                    
                    // Adicionar ícone de erro
                    const errorIcon = document.createElement('div');
                    errorIcon.className = 'form-input-icon invalid-icon show';
                    errorIcon.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                    formGroup?.appendChild(errorIcon);
                }
                
                return isValid;
            }
            
            // Validar formulário completo
            function validateForm(form, rules = {}) {
                const inputs = form.querySelectorAll('input, select, textarea');
                let isFormValid = true;
                
                inputs.forEach(input => {
                    const fieldRules = rules[input.name] || rules[input.id] || {};
                    
                    // Se o campo tem atributo required, adicionar validação
                    if (input.hasAttribute('required') && !fieldRules.required) {
                        fieldRules.required = true;
                    }
                    
                    // Se o campo tem tipo email, adicionar validação
                    if (input.type === 'email' && !fieldRules.email) {
                        fieldRules.email = true;
                    }
                    
                    if (Object.keys(fieldRules).length > 0) {
                        const isValid = validateField(input, fieldRules);
                        if (!isValid) {
                            isFormValid = false;
                        }
                    }
                });
                
                return isFormValid;
            }
            
            // Inicializar validação em tempo real para um formulário
            function initFormValidation(form, rules = {}, options = {}) {
                const { validateOnBlur = true, validateOnInput = true, validateOnSubmit = true } = options;
                
                const inputs = form.querySelectorAll('input, select, textarea');
                
                inputs.forEach(input => {
                    const fieldRules = rules[input.name] || rules[input.id] || {};
                    
                    // Adicionar classe form-input se não tiver
                    if (!input.classList.contains('form-input')) {
                        input.classList.add('form-input');
                    }
                    
                    // Envolver em form-group se necessário
                    if (!input.closest('.form-group')) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'form-group';
                        input.parentNode.insertBefore(wrapper, input);
                        wrapper.appendChild(input);
                    }
                    
                    if (validateOnInput) {
                        input.addEventListener('input', function() {
                            // Aguardar um pouco para não validar a cada tecla
                            clearTimeout(input.validationTimeout);
                            input.validationTimeout = setTimeout(() => {
                                validateField(input, fieldRules);
                            }, 300);
                        });
                    }
                    
                    if (validateOnBlur) {
                        input.addEventListener('blur', function() {
                            validateField(input, fieldRules);
                        });
                    }
                });
                
                if (validateOnSubmit) {
                    form.addEventListener('submit', function(e) {
                        const isValid = validateForm(form, rules);
                        if (!isValid) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            // Focar no primeiro campo inválido
                            const firstInvalid = form.querySelector('.invalid');
                            if (firstInvalid) {
                                firstInvalid.focus();
                                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                            
                            showErrorToast('Por favor, corrija os erros no formulário', 'Erro de Validação');
                            return false;
                        }
                    });
                }
            }
            
            // Exportar funções globais
            window.validateField = validateField;
            window.validateForm = validateForm;
            window.initFormValidation = initFormValidation;
            window.formValidators = validators;
        })();
        
        // ============================================
        // SISTEMA DE TRATAMENTO DE ERROS MELHORADO
        // ============================================
        (function() {
            // Função para tratar erros de fetch de forma consistente
            async function safeFetch(url, options = {}) {
                try {
                    const response = await fetch(url, {
                        ...options,
                        headers: {
                            'Content-Type': 'application/json',
                            ...options.headers
                        }
                    });
                    
                    // Verificar se a resposta é OK
                    if (!response.ok) {
                        let errorMessage = `Erro ${response.status}: ${response.statusText}`;
                        
                        // Tentar obter mensagem de erro do corpo da resposta
                        try {
                            const errorData = await response.json();
                            if (errorData.error) {
                                errorMessage = errorData.error;
                            } else if (errorData.message) {
                                errorMessage = errorData.message;
                            }
                        } catch (e) {
                            // Se não conseguir parsear JSON, usar mensagem padrão
                        }
                        
                        throw new Error(errorMessage);
                    }
                    
                    // Tentar parsear JSON
                    try {
                        const data = await response.json();
                        return { success: true, data, response };
                    } catch (e) {
                        // Se não for JSON, retornar texto
                        const text = await response.text();
                        return { success: true, data: text, response };
                    }
                } catch (error) {
                    // Tratar diferentes tipos de erro
                    let userMessage = 'Erro ao processar solicitação';
                    
                    if (error.name === 'TypeError' && error.message.includes('fetch')) {
                        userMessage = 'Erro de conexão. Verifique sua internet e tente novamente.';
                    } else if (error.message) {
                        userMessage = error.message;
                    }
                    
                    console.error('Erro na requisição:', error);
                    return { success: false, error: userMessage, originalError: error };
                }
            }
            
            // Função para executar operações assíncronas com tratamento de erro
            async function safeAsyncOperation(operation, errorMessage = 'Erro ao executar operação') {
                try {
                    const result = await operation();
                    return { success: true, data: result };
                } catch (error) {
                    console.error('Erro na operação:', error);
                    
                    let userMessage = errorMessage;
                    if (error.message) {
                        userMessage = error.message;
                    }
                    
                    return { success: false, error: userMessage, originalError: error };
                }
            }
            
            // Exportar funções
            window.safeFetch = safeFetch;
            window.safeAsyncOperation = safeAsyncOperation;
        })();
        
        // Registrar Service Worker com melhorias
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('./sw-manager.js', { scope: './' })
                    .then((registration) => {
                        console.log('✅ Service Worker registrado com sucesso:', registration.scope);
                        
                        // Verificar atualizações periodicamente (otimizado)
                        let updateCheckInterval = setInterval(() => {
                            registration.update().catch(err => {
                                console.warn('Erro ao verificar atualização do SW:', err);
                            });
                        }, 300000); // A cada 5 minutos (reduzido de 1 minuto para economizar recursos)
                        
                        // Ouvir mensagens do Service Worker
                        navigator.serviceWorker.addEventListener('message', (event) => {
                            if (event.data && event.data.type === 'SYNC_REQUESTED') {
                                // Service Worker solicitou sincronização
                                if (typeof offlineManager !== 'undefined' && offlineManager.isOnline && !offlineManager.forceOffline) {
                                    offlineManager.sync();
                                }
                            } else if (event.data && event.data.type === 'SW_UPDATED') {
                                // Service Worker atualizado - notificar usuário
                                if (typeof showToast === 'function') {
                                    showToast('Nova versão disponível! Recarregue a página para atualizar.', 'info', null, 10000);
                                }
                            }
                        });
                        
                        // Verificar se há atualização disponível
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            if (!newWorker) return;
                            
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed') {
                                    if (navigator.serviceWorker.controller) {
                                        // Nova versão disponível - notificar usuário
                                        if (typeof showToast === 'function') {
                                            showToast('Nova versão disponível! Recarregue a página para atualizar.', 'info', null, 10000);
                                        }
                                        
                                        // Notificar Service Worker
                                        newWorker.postMessage({ type: 'SKIP_WAITING' });
                                    } else {
                                        // Primeira instalação
                                        console.log('✅ Service Worker instalado pela primeira vez');
                                    }
                                }
                            });
                        });
                        
                        // Verificar atualização imediatamente ao carregar
                        registration.update().catch(err => {
                            console.warn('Erro ao verificar atualização inicial:', err);
                        });
                    })
                    .catch((error) => {
                        console.error('❌ Erro ao registrar Service Worker:', error);
                        // Tentar novamente após 30 segundos
                        setTimeout(() => {
                            navigator.serviceWorker.register('./sw-manager.js', { scope: './' })
                                .then(() => console.log('✅ Service Worker registrado após retry'))
                                .catch(err => console.error('❌ Erro no retry do Service Worker:', err));
                        }, 30000);
                    });
            });
        }
        
        // Detectar se PWA pode ser instalada (melhorado)
        let deferredPrompt;
        let installButton = null;
        let pwaInstallContainer = null;
        let installPromptShown = false;
        
        // Verificar se já está instalada ao carregar
        function checkIfInstalled() {
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
            const isIOSStandalone = window.navigator.standalone === true;
            const isInWebView = window.navigator.userAgent.includes('wv');
            
            if (isStandalone || isIOSStandalone || isInWebView) {
                hideInstallButton();
                return true;
            }
            return false;
        }
        
        // Verificar imediatamente
        if (checkIfInstalled()) {
            console.log('✅ PWA já está instalada');
        }
        
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevenir o prompt padrão
            e.preventDefault();
            deferredPrompt = e;
            
            // Mostrar botão de instalação no perfil (apenas se não estiver instalada)
            if (!checkIfInstalled()) {
                showInstallButton();
            }
            
            // Analytics de instalação disponível
            if (typeof gtag !== 'undefined') {
                gtag('event', 'pwa_install_prompt_available', {
                    'event_category': 'PWA',
                    'event_label': 'Install Prompt Available'
                });
            }
        });
        
        function showInstallButton() {
            // Mostrar container no perfil
            pwaInstallContainer = document.getElementById('pwa-install-container');
            if (pwaInstallContainer) {
                pwaInstallContainer.classList.remove('hidden');
            }
        }
        
        function hideInstallButton() {
            if (pwaInstallContainer) {
                pwaInstallContainer.classList.add('hidden');
            }
            if (installButton) {
                installButton.style.display = 'none';
            }
        }
        
        window.installPWA = async function installPWA() {
            if (!deferredPrompt) {
                // Se não houver deferredPrompt, pode ser que já esteja instalada
                if (checkIfInstalled()) {
                    if (typeof showToast === 'function') {
                        showToast('O aplicativo já está instalado!', 'info');
                    }
                } else {
                    if (typeof showToast === 'function') {
                        showToast('Instalação não disponível no momento. Tente novamente mais tarde.', 'warning');
                    }
                }
                return;
            }
            
            try {
                // Mostrar prompt de instalação
                deferredPrompt.prompt();
                
                // Aguardar resposta do usuário
                const { outcome } = await deferredPrompt.userChoice;
                
                // Analytics
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'pwa_install_' + outcome, {
                        'event_category': 'PWA',
                        'event_label': 'Install ' + outcome
                    });
                }
                
                if (outcome === 'accepted') {
                    hideInstallButton();
                    if (typeof showToast === 'function') {
                        showToast('Aplicativo instalado com sucesso!', 'success');
                    }
                } else {
                    console.log('Usuário recusou instalação');
                }
                
                deferredPrompt = null;
            } catch (error) {
                console.error('Erro ao instalar PWA:', error);
                if (typeof showToast === 'function') {
                    showToast('Erro ao instalar aplicativo. Tente novamente.', 'error');
                }
            }
        };
        
        // Esconder botão se já estiver instalada
        window.addEventListener('appinstalled', (e) => {
            console.log('✅ PWA instalada com sucesso');
            hideInstallButton();
            deferredPrompt = null;
            
            // Analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'pwa_installed', {
                    'event_category': 'PWA',
                    'event_label': 'PWA Installed'
                });
            }
            
            if (typeof showToast === 'function') {
                showToast('Aplicativo instalado com sucesso!', 'success');
            }
        });
        
        // Verificar periodicamente se foi instalada (para casos onde o evento não dispara)
        setInterval(() => {
            if (!checkIfInstalled() && deferredPrompt) {
                // Ainda não instalada e prompt disponível
            } else if (checkIfInstalled()) {
                hideInstallButton();
            }
        }, 5000);
        
        // Função para toggle de Push Notifications
        window.togglePushNotifications = async function togglePushNotifications(enabled) {
            if (!window.pushNotifications) {
                console.warn('Push Notifications não inicializado');
                if (typeof showToast === 'function') {
                    showToast('Push Notifications não disponível no momento', 'warning');
                }
                // Reverter checkbox
                document.getElementById('pushNotifications').checked = !enabled;
                return;
            }
            
            try {
                if (enabled) {
                    // Solicitar permissão e subscrever
                    const permission = await window.pushNotifications.requestPermission();
                    if (permission) {
                        await window.pushNotifications.subscribe();
                        if (typeof showToast === 'function') {
                            showToast('Notificações Push ativadas! Você receberá notificações mesmo com o app fechado.', 'success');
                        }
                    } else {
                        // Reverter checkbox se permissão negada
                        document.getElementById('pushNotifications').checked = false;
                        if (typeof showToast === 'function') {
                            showToast('Permissão para notificações negada', 'warning');
                        }
                    }
                } else {
                    // Desinscrever
                    await window.pushNotifications.unsubscribe();
                    if (typeof showToast === 'function') {
                        showToast('Notificações Push desativadas', 'info');
                    }
                }
            } catch (error) {
                console.error('Erro ao toggle push notifications:', error);
                // Reverter checkbox em caso de erro
                document.getElementById('pushNotifications').checked = !enabled;
                if (typeof showToast === 'function') {
                    showToast('Erro ao alterar configuração de notificações', 'error');
                }
            }
        };
        
        // Verificar status inicial de push notifications após carregar
        setTimeout(() => {
            if (window.pushNotifications) {
                const status = window.pushNotifications.getSubscriptionStatus();
                const checkbox = document.getElementById('pushNotifications');
                if (checkbox) {
                    checkbox.checked = status.isSubscribed && status.hasPermission;
                }
            }
        }, 2000);
        
        // Função global para toggle modo offline
        
        // Funções para gerenciar permissão de localização
        async function checkLocationPermissionStatus() {
            const statusEl = document.getElementById('location-permission-status');
            const iconGranted = document.getElementById('location-permission-icon-granted');
            const iconDenied = document.getElementById('location-permission-icon-denied');
            const iconPrompt = document.getElementById('location-permission-icon-prompt');
            const btn = document.getElementById('location-permission-btn');
            
            if (!navigator.geolocation) {
                if (statusEl) statusEl.textContent = 'Não suportado pelo navegador';
                if (iconPrompt) iconPrompt.classList.add('hidden');
                if (iconDenied) iconDenied.classList.remove('hidden');
                if (btn) btn.disabled = true;
                return;
            }
            
            if (navigator.permissions) {
                try {
                    const permission = await navigator.permissions.query({ name: 'geolocation' });
                    updateLocationPermissionUI(permission.state, statusEl, iconGranted, iconDenied, iconPrompt, btn);
                    
                    // Listener para mudanças no status
                    permission.onchange = () => {
                        updateLocationPermissionUI(permission.state, statusEl, iconGranted, iconDenied, iconPrompt, btn);
                    };
                } catch (e) {
                    // API de permissões não suportada
                    if (statusEl) statusEl.textContent = 'Status desconhecido';
                    if (iconPrompt) iconPrompt.classList.remove('hidden');
                    if (iconGranted) iconGranted.classList.add('hidden');
                    if (iconDenied) iconDenied.classList.add('hidden');
                }
            } else {
                // API de permissões não suportada
                if (statusEl) statusEl.textContent = 'Status desconhecido';
                if (iconPrompt) iconPrompt.classList.remove('hidden');
                if (iconGranted) iconGranted.classList.add('hidden');
                if (iconDenied) iconDenied.classList.add('hidden');
            }
        }
        
        function updateLocationPermissionUI(state, statusEl, iconGranted, iconDenied, iconPrompt, btn) {
            // Esconder todos os ícones primeiro
            if (iconGranted) iconGranted.classList.add('hidden');
            if (iconDenied) iconDenied.classList.add('hidden');
            if (iconPrompt) iconPrompt.classList.add('hidden');
            
            switch(state) {
                case 'granted':
                    if (statusEl) statusEl.textContent = 'Permissão concedida';
                    if (iconGranted) iconGranted.classList.remove('hidden');
                    if (btn) {
                        btn.textContent = 'Atualizar';
                        btn.onclick = () => requestLocationUpdate();
                    }
                    break;
                case 'denied':
                    if (statusEl) statusEl.textContent = 'Permissão negada';
                    if (iconDenied) iconDenied.classList.remove('hidden');
                    if (btn) {
                        btn.textContent = 'Solicitar';
                        btn.onclick = () => manageLocationPermission();
                    }
                    break;
                case 'prompt':
                default:
                    if (statusEl) statusEl.textContent = 'Não solicitada';
                    if (iconPrompt) iconPrompt.classList.remove('hidden');
                    if (btn) {
                        btn.textContent = 'Solicitar';
                        btn.onclick = () => manageLocationPermission();
                    }
                    break;
            }
        }
        
        async function manageLocationPermission() {
            if (!navigator.geolocation) {
                showNotification('Geolocalização não está disponível no seu navegador', 'error');
                return;
            }
            
            // Verificar status atual
            if (navigator.permissions) {
                try {
                    const permission = await navigator.permissions.query({ name: 'geolocation' });
                    if (permission.state === 'granted') {
                        // Já tem permissão, apenas atualizar localização
                        await requestLocationUpdate();
                        return;
                    } else if (permission.state === 'denied') {
                        showNotification('Permissão negada. Por favor, habilite a localização nas configurações do navegador.', 'error');
                        return;
                    }
                } catch (e) {
                    // Continuar para solicitar permissão
                }
            }
            
            // Mostrar modal explicativo
            openLocationPermissionModal(async (gpsCoords) => {
                if (gpsCoords) {
                    showNotification('Permissão concedida! Localização GPS obtida com sucesso.', 'success');
                    // Atualizar status
                    await checkLocationPermissionStatus();
                } else {
                    // Atualizar status mesmo se não obteve coordenadas
                    await checkLocationPermissionStatus();
                }
            }, 'settings');
        }
        
        async function requestLocationUpdate() {
            if (!navigator.geolocation) {
                showNotification('Geolocalização não está disponível no seu navegador', 'error');
                return;
            }
            
            const btn = document.getElementById('location-permission-btn');
            const originalText = btn ? btn.textContent : '';
            
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Obtendo...';
            }
            
            try {
                const gpsCoords = await new Promise((resolve) => {
                    const timeout = setTimeout(() => resolve(null), 15000);
                    
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            clearTimeout(timeout);
                            resolve({
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude,
                                accuracy: position.coords.accuracy
                            });
                        },
                        (error) => {
                            clearTimeout(timeout);
                            let errorMsg = 'Erro ao obter localização';
                            switch(error.code) {
                                case error.PERMISSION_DENIED:
                                    errorMsg = 'Permissão de localização negada';
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    errorMsg = 'Localização indisponível';
                                    break;
                                case error.TIMEOUT:
                                    errorMsg = 'Tempo esgotado';
                                    break;
                            }
                            showNotification(errorMsg, 'error');
                            resolve(null);
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0
                        }
                    );
                });
                
                if (gpsCoords) {
                    // Atualizar localização da sessão atual
                    try {
                        const formData = new FormData();
                        formData.append('gps_latitude', gpsCoords.latitude);
                        formData.append('gps_longitude', gpsCoords.longitude);
                        if (gpsCoords.accuracy) {
                            formData.append('gps_accuracy', gpsCoords.accuracy);
                        }
                        
                        const response = await fetch('./api/actions.php?action=update_session_location', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        if (result.success) {
                            showNotification(`Localização atualizada! Precisão: ${Math.round(gpsCoords.accuracy)}m`, 'success');
                        }
                    } catch (e) {
                        console.error('Erro ao atualizar localização:', e);
                    }
                }
            } catch (e) {
                console.error('Erro ao obter localização:', e);
                showNotification('Erro ao obter localização', 'error');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            }
        }
        
        // Verificar status ao carregar a página
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', checkLocationPermissionStatus);
        } else {
            checkLocationPermissionStatus();
        }
        
        window.manageLocationPermission = manageLocationPermission;
        window.checkLocationPermissionStatus = checkLocationPermissionStatus;
        
        // Verificar se já está instalada como PWA
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
            if (installButton) {
                installButton.style.display = 'none';
            }
        }
    </script>
    
    <!-- Modal Mais Opções - REMOVIDO: Agora é uma página completa (mais-opcoes.php) -->
    
    
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
                checkForReturnFromMoreOptions();
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
            if (typeof window.showAddUserFullScreen === 'function') {
                window.showAddUserFullScreen();
            }
        }
        
        function closeAddUserModal() {
            if (typeof window.closeAddUserFullScreen === 'function') {
                window.closeAddUserFullScreen();
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
        
        function openUnlinkGoogleModal() {
            document.getElementById('unlinkGoogleModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeUnlinkGoogleModal() {
            document.getElementById('unlinkGoogleModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        // Modal OTP e Senha
        function openOtpPasswordModal(title, message, requirePassword, callback) {
            const modal = document.getElementById('otpPasswordModal');
            if (!modal) return;
            
            // Armazenar callback
            window.otpPasswordCallback = callback;
            window.otpPasswordRequirePassword = requirePassword;
            
            // Atualizar texto
            const titleEl = document.getElementById('otpModalTitle');
            const messageEl = document.getElementById('otpModalMessage');
            const passwordContainer = document.getElementById('otpPasswordInputContainer');
            
            if (titleEl) titleEl.textContent = title || 'Verificação de Segurança';
            if (messageEl) messageEl.textContent = message || 'Digite o código OTP enviado para seu e-mail:';
            
            // Mostrar/esconder campo de senha
            if (passwordContainer) {
                if (requirePassword) {
                    passwordContainer.classList.remove('hidden');
                } else {
                    passwordContainer.classList.add('hidden');
                }
            }
            
            // Limpar campos
            const otpInput = document.getElementById('otpCodeInput');
            const passwordInput = document.getElementById('otpPasswordInput');
            if (otpInput) {
                otpInput.value = '';
                setTimeout(() => otpInput.focus(), 100);
            }
            if (passwordInput) passwordInput.value = '';
            
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeOtpPasswordModal() {
            const modal = document.getElementById('otpPasswordModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
            // Limpar callback
            window.otpPasswordCallback = null;
            window.otpPasswordRequirePassword = false;
        }
        
        function submitOtpPassword() {
            const otpInput = document.getElementById('otpCodeInput');
            const passwordInput = document.getElementById('otpPasswordInput');
            const callback = window.otpPasswordCallback;
            const requirePassword = window.otpPasswordRequirePassword;
            
            if (!otpInput || !callback) return;
            
            const otpCode = otpInput.value.trim().replace(/\D/g, ''); // Remover não-dígitos
            const password = requirePassword && passwordInput ? passwordInput.value : null;
            
            if (!otpCode || otpCode.length !== 6) {
                if (typeof showErrorModal === 'function') {
                    showErrorModal('Código OTP inválido. Deve ter 6 dígitos.');
                }
                return;
            }
            
            if (requirePassword && !password) {
                if (typeof showErrorModal === 'function') {
                    showErrorModal('Senha atual é obrigatória.');
                }
                return;
            }
            
            // Fechar modal
            closeOtpPasswordModal();
            
            // Executar callback
            if (requirePassword) {
                callback(otpCode, password);
            } else {
                callback(otpCode);
            }
        }
        
        function toggleOtpPasswordVisibility() {
            const passwordInput = document.getElementById('otpPasswordInput');
            const eyeIcon = document.getElementById('otpPasswordEye');
            
            if (!passwordInput || !eyeIcon) return;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>';
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
            }
        }
        
        // Permitir Enter para submeter
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.getElementById('otpCodeInput');
            if (otpInput) {
                otpInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        const passwordInput = document.getElementById('otpPasswordInput');
                        const requirePassword = window.otpPasswordRequirePassword;
                        
                        if (requirePassword && passwordInput && !passwordInput.value) {
                            passwordInput.focus();
                        } else {
                            submitOtpPassword();
                        }
                    }
                });
                
                // Permitir apenas números
                otpInput.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6);
                });
            }
            
            const passwordInput = document.getElementById('otpPasswordInput');
            if (passwordInput) {
                passwordInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        submitOtpPassword();
                    }
                });
            }
        });
        
        // Função saveProfile está definida em gerente-completo.js
        // Não definir aqui para não sobrescrever a função correta
        
        function toggleUserPasswordVisibility(inputId, buttonId) {
            const input = document.getElementById(inputId);
            const button = document.getElementById(buttonId);
            
            if (!input || !button) {
                console.error('Elementos não encontrados:', inputId, buttonId);
                return;
            }
            
            const svg = button.querySelector('svg');
            if (!svg) {
                console.error('SVG não encontrado no botão');
                return;
            }
            
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            
            // Atualizar apenas o conteúdo do SVG
            if (isPassword) {
                // Mostrar ícone de olho fechado (senha visível)
                svg.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                `;
            } else {
                // Mostrar ícone de olho aberto (senha oculta)
                svg.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                `;
            }
        }
        
        window.toggleProfilePasswordVisibility = function toggleProfilePasswordVisibility(inputId, buttonId) {
            const input = document.getElementById(inputId);
            const button = document.getElementById(buttonId);
            
            if (!input || !button) return;
            
            // Só funciona quando o campo não está desabilitado
            if (input.disabled) return;
            
            if (input.type === 'password') {
                input.type = 'text';
                button.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/></svg>';
            } else {
                input.type = 'password';
                button.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
            }
        }
        
        window.openAccountActionsModal = function openAccountActionsModal() {
            const modal = document.getElementById('accountActionsModal');
            if (!modal) return;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Carregar histórico de ações
            loadAccountActions();
        };
        
        window.closeAccountActionsModal = function closeAccountActionsModal() {
            const modal = document.getElementById('accountActionsModal');
            if (!modal) return;
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        };
        
        async function loadAccountActions() {
            const passwordChangesList = document.getElementById('passwordChangesList');
            const otherActionsList = document.getElementById('otherActionsList');
            if (!passwordChangesList) return;
            
            try {
                const response = await fetch('./api/actions.php?action=get_account_actions');
                const result = await response.json();
                
                if (result.success && result.data) {
                    const passwordChanges = result.data.password_changes || [];
                    const otherActions = result.data.other_actions || [];
                    
                    // Renderizar alterações de senha
                    if (passwordChanges.length === 0) {
                        passwordChangesList.innerHTML = `
                            <div class="text-center py-8">
                                <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <p class="text-sm text-gray-500">Nenhuma alteração de senha registrada ainda.</p>
                            </div>
                        `;
                    } else {
                        passwordChangesList.innerHTML = passwordChanges.map(change => {
                            const date = new Date(change.password_changed_at);
                            const formattedDate = date.toLocaleDateString('pt-BR', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            
                            const statusClass = change.success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                            const statusText = change.success ? 'Concluída' : 'Falhou';
                            const ipInfo = change.ip_address ? `<p class="text-xs text-gray-400 mt-1">IP: ${change.ip_address}</p>` : '';
                            
                            return `
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200 hover:shadow-sm transition-shadow">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Senha alterada</p>
                                            <p class="text-xs text-gray-500">${formattedDate}</p>
                                            ${ipInfo}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-1 text-xs font-medium ${statusClass} rounded-lg">${statusText}</span>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    }
                    
                    // Renderizar outras ações
                    if (otherActionsList) {
                        if (otherActions.length === 0) {
                            otherActionsList.innerHTML = `
                                <div class="text-center py-6">
                                    <p class="text-sm text-gray-500">Nenhuma outra ação registrada ainda.</p>
                                </div>
                            `;
                        } else {
                            const actionIcons = {
                                'email_verified': `<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>`,
                                'google_linked': `<svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>`,
                                'google_unlinked': `<svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>`,
                                '2fa_enabled': `<svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>`,
                                '2fa_disabled': `<svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>`,
                                'otp_generated': `<svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
                                'otp_validated': `<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
                            };
                            
                            const actionLabels = {
                                'email_verified': 'E-mail verificado',
                                'google_linked': 'Conta Google vinculada',
                                'google_unlinked': 'Conta Google desvinculada',
                                '2fa_enabled': 'Autenticação de dois fatores ativada',
                                '2fa_disabled': 'Autenticação de dois fatores desativada',
                                'otp_generated': 'Código OTP gerado',
                                'otp_validated': 'Código OTP validado'
                            };
                            
                            const actionColors = {
                                'email_verified': 'bg-green-100',
                                'google_linked': 'bg-blue-100',
                                'google_unlinked': 'bg-red-100',
                                '2fa_enabled': 'bg-purple-100',
                                '2fa_disabled': 'bg-gray-100',
                                'otp_generated': 'bg-yellow-100',
                                'otp_validated': 'bg-green-100'
                            };
                            
                            otherActionsList.innerHTML = otherActions.map(action => {
                                const date = new Date(action.created_at);
                                const formattedDate = date.toLocaleDateString('pt-BR', {
                                    day: '2-digit',
                                    month: '2-digit',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });
                                
                                const icon = actionIcons[action.action] || `<svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
                                const label = actionLabels[action.action] || action.description || action.action;
                                const bgColor = actionColors[action.action] || 'bg-gray-100';
                                const statusClass = action.success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                                const statusText = action.success ? 'Sucesso' : 'Falhou';
                                const ipInfo = action.ip_address ? `<p class="text-xs text-gray-400 mt-1">IP: ${action.ip_address}</p>` : '';
                                
                                return `
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200 hover:shadow-sm transition-shadow">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 ${bgColor} rounded-lg flex items-center justify-center">
                                                ${icon}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">${label}</p>
                                                <p class="text-xs text-gray-500">${formattedDate}</p>
                                                ${ipInfo}
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-1 text-xs font-medium ${statusClass} rounded-lg">${statusText}</span>
                                        </div>
                                    </div>
                                `;
                            }).join('');
                        }
                    }
                } else {
                    passwordChangesList.innerHTML = `
                        <div class="text-center py-8">
                            <p class="text-sm text-red-500">Erro ao carregar histórico: ${result.error || 'Erro desconhecido'}</p>
                        </div>
                    `;
                    if (otherActionsList) {
                        otherActionsList.innerHTML = `
                            <div class="text-center py-6">
                                <p class="text-sm text-red-500">Erro ao carregar outras ações.</p>
                            </div>
                        `;
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar ações da conta:', error);
                passwordChangesList.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-sm text-red-500">Erro ao carregar histórico de ações.</p>
                    </div>
                `;
                if (otherActionsList) {
                    otherActionsList.innerHTML = `
                        <div class="text-center py-6">
                            <p class="text-sm text-red-500">Erro ao carregar outras ações.</p>
                        </div>
                    `;
                }
            }
        }
        
        // Formulário de adicionar usuário
        // Inicializar validação do formulário de adicionar usuário
        document.addEventListener('DOMContentLoaded', function() {
            const addUserForm = document.getElementById('addUserForm');
            if (addUserForm) {
                // Função para gerar email automaticamente baseado no nome
                function generateEmailFromName(name) {
                    if (!name || name.trim() === '') {
                        return '';
                    }
                    
                    // Remover acentos e caracteres especiais
                    let email = name.toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '') // Remove acentos
                        .replace(/[^a-z0-9\s]/g, '') // Remove caracteres especiais
                        .trim()
                        .replace(/\s+/g, '.') // Substitui espaços por pontos
                        .replace(/\.+/g, '.') // Remove pontos duplicados
                        .replace(/^\.|\.$/g, ''); // Remove pontos no início/fim
                    
                    // Adicionar domínio
                    return email ? email + '@lactech.com' : '';
                }
                
                // Gerar email automaticamente quando o nome mudar
                const nameInput = document.getElementById('userNameInput');
                const emailInput = document.getElementById('userEmailInput');
                
                if (nameInput && emailInput) {
                    let isUserTypingEmail = false;
                    
                    // Detectar se usuário está editando o email manualmente
                    emailInput.addEventListener('focus', function() {
                        isUserTypingEmail = true;
                    });
                    
                    emailInput.addEventListener('blur', function() {
                        // Se o email estiver vazio ou igual ao gerado, permitir regenerar
                        if (!this.value || this.value === generateEmailFromName(nameInput.value)) {
                            isUserTypingEmail = false;
                        }
                    });
                    
                    // Gerar email quando o nome mudar
                    nameInput.addEventListener('input', function() {
                        if (!isUserTypingEmail) {
                            const generatedEmail = generateEmailFromName(this.value);
                            if (generatedEmail) {
                                emailInput.value = generatedEmail;
                            }
                        }
                    });
                    
                    // Gerar email quando o campo de nome perder o foco (se email estiver vazio)
                    nameInput.addEventListener('blur', function() {
                        if (!isUserTypingEmail && (!emailInput.value || emailInput.value === '')) {
                            const generatedEmail = generateEmailFromName(this.value);
                            if (generatedEmail) {
                                emailInput.value = generatedEmail;
                            }
                        }
                    });
                }
                
                // Regras de validação
                const validationRules = {
                    name: { required: true, minLength: 3 },
                    email: { required: true, email: true },
                    phone: { phone: true },
                    password: { required: true, password: true, minLength: 6 },
                    confirm_password: { 
                        required: true,
                        passwordMatch: true // Validação customizada será feita no submit
                    },
                    role: { required: true }
                };
                
                // Inicializar validação em tempo real
                initFormValidation(addUserForm, validationRules);
                
                // Validação customizada de confirmação de senha
                const passwordInput = addUserForm.querySelector('input[name="password"]');
                const confirmPasswordInput = addUserForm.querySelector('input[name="confirm_password"]');
                
                if (passwordInput && confirmPasswordInput) {
                    confirmPasswordInput.addEventListener('blur', function() {
                        const password = passwordInput.value;
                        const confirmPassword = this.value;
                        
                        if (confirmPassword && password !== confirmPassword) {
                            validateField(this, { passwordMatch: false });
                            // Criar mensagem customizada
                            const formGroup = this.closest('.form-group');
                            const existingError = formGroup?.querySelector('.form-error-message');
                            if (existingError) {
                                existingError.innerHTML = `
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>As senhas não coincidem</span>
                                `;
                            }
                        } else if (confirmPassword && password === confirmPassword) {
                            validateField(this, {});
                        }
                    });
                }
                
                // Submit handler melhorado
                addUserForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    // Validar formulário completo
                    if (!validateForm(addUserForm, validationRules)) {
                        return;
                    }
                    
                    const formData = new FormData(this);
                    
                    // Validação adicional de senhas
                    if (formData.get('password') !== formData.get('confirm_password')) {
                        showErrorToast('As senhas não coincidem!', 'Erro de Validação');
                        if (confirmPasswordInput) {
                            validateField(confirmPasswordInput, { passwordMatch: false });
                            confirmPasswordInput.focus();
                        }
                        return;
                    }
                    
                    // Desabilitar botão e mostrar loading
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn?.textContent;
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('btn-loading');
                        submitBtn.textContent = 'Salvando...';
                    }
                    
                    try {
                        // Preparar dados
                        const data = {
                            name: formData.get('name'),
                            email: formData.get('email'),
                            phone: formData.get('phone') || null,
                            password: formData.get('password'),
                            role: formData.get('role')
                        };
                        
                        // Enviar para API
                        const result = await safeFetch('./api/actions.php?action=add_user', {
                            method: 'POST',
                            body: JSON.stringify(data)
                        });
                        
                        if (result.success && result.data) {
                            showSuccessToast('Usuário adicionado com sucesso!', 'Sucesso');
                            addUserForm.reset();
                            // Remover classes de validação
                            addUserForm.querySelectorAll('.valid, .invalid').forEach(el => {
                                el.classList.remove('valid', 'invalid');
                            });
                            addUserForm.querySelectorAll('.form-group').forEach(el => {
                                el.classList.remove('has-error', 'has-success');
                            });
                            if (typeof window.closeAddUserFullScreen === 'function') window.closeAddUserFullScreen();
                        } else {
                            showErrorToast(result.error || 'Erro ao adicionar usuário', 'Erro');
                        }
                    } catch (error) {
                        console.error('Erro ao adicionar usuário:', error);
                        showErrorToast('Erro ao adicionar usuário. Tente novamente.', 'Erro');
                    } finally {
                        // Reabilitar botão
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('btn-loading');
                            submitBtn.textContent = originalText;
                        }
                    }
                });
            }
        });
        
        // Exportar funções globais
        window.openNotificationsModal = openNotificationsModal;
        window.closeNotificationsModal = closeNotificationsModal;
        window.openProfileOverlay = function openProfileOverlay() {
            document.getElementById('profileOverlay').classList.remove('hidden');
            // Carregar status de segurança ao abrir
            if (typeof loadSecurityStatus === 'function') {
                loadSecurityStatus();
            }
            // Atualizar UI do modo offline quando abrir o perfil
            if (typeof offlineManager !== 'undefined' && offlineManager.updateUI) {
                offlineManager.updateUI();
            }
        };
        
        // Verificar resultados de vinculação Google e carregar status
        document.addEventListener('DOMContentLoaded', function() {
            // Carregar status do Google automaticamente quando a página carregar
            if (typeof loadSecurityStatus === 'function') {
                loadSecurityStatus();
            }
            
            const urlParams = new URLSearchParams(window.location.search);
            const googleLinked = urlParams.get('google_linked');
            const googleError = urlParams.get('google_error');
            
            if (googleLinked === 'success') {
                // Mostrar modal de sucesso e recarregar status
                if (typeof showSuccessModal === 'function') {
                    showSuccessModal('Conta Google vinculada com sucesso!');
                }
                if (typeof loadSecurityStatus === 'function') {
                    setTimeout(() => loadSecurityStatus(), 500); // Recarregar após um delay
                }
                // Limpar URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            
            if (googleError) {
                // Mostrar modal de erro
                if (typeof showErrorModal === 'function') {
                    showErrorModal('Erro ao vincular conta Google: ' + decodeURIComponent(googleError));
                }
                // Limpar URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
        window.closeProfileOverlay = closeProfileOverlay;
        window.openAddUserModal = openAddUserModal;
        window.closeAddUserModal = closeAddUserModal;
        window.openLogoutModal = openLogoutModal;
        window.closeLogoutModal = closeLogoutModal;
        window.openUnlinkGoogleModal = openUnlinkGoogleModal;
        window.closeUnlinkGoogleModal = closeUnlinkGoogleModal;
        window.openGoogleSettingsModal = openGoogleSettingsModal;
        window.closeGoogleSettingsModal = closeGoogleSettingsModal;
        window.openOtpPasswordModal = openOtpPasswordModal;
        window.closeOtpPasswordModal = closeOtpPasswordModal;
        window.submitOtpPassword = submitOtpPassword;
        window.toggleOtpPasswordVisibility = toggleOtpPasswordVisibility;
        // window.saveProfile não é exportado aqui pois está definido em gerente-completo.js
        window.toggleUserPasswordVisibility = toggleUserPasswordVisibility;
        
        // Sistema de controle de abas
        document.addEventListener('DOMContentLoaded', function() {
            // Selecionar todos os botões de navegação
            const navButtons = document.querySelectorAll('.nav-item[data-tab]');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // Adicionar event listener para cada botão
            navButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');
                    switchTab(tabName);
                });
            });
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
                        // Adicionar event listener para o select de período
                        setTimeout(() => {
                            const volumePeriodSelect = document.getElementById('volumePeriod');
                            if (volumePeriodSelect) {
                                volumePeriodSelect.addEventListener('change', function() {
                                    if (typeof loadVolumeData === 'function') {
                                        loadVolumeData();
                                    }
                                });
                            }
                        }, 100);
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
        
        // Função para abrir página Mais Opções (agora é uma página completa, não modal)
        function openMoreOptionsModal() {
            // Redirecionar para a página completa
            window.location.href = 'mais-opcoes.php';
        }
        
        // Tornar função global
        window.openMoreOptionsModal = openMoreOptionsModal;
    </script>
    
    <!-- Modo Escuro - DESABILITADO -->
    <script>
        // Função para remover modo escuro (se estiver ativo)
        function removeDarkMode() {
            const body = document.getElementById('mainBody');
            if (body) {
                body.classList.remove('dark-mode');
            }
            // Limpar preferência salva
            localStorage.setItem('darkMode', 'false');
        }
        
        // Remover modo escuro ao carregar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', removeDarkMode);
        } else {
            removeDarkMode();
        }
    </script>

    <!-- Proteção contra cópia de código no console -->
    <script src="./assets/js/console-protection.js"></script>
    
    <!-- Skeleton Loader Script -->
    <script>
        // Esconder skeleton loader após 1.5 segundos
        (function() {
            function hideSkeletonLoader() {
                const skeletonLoader = document.getElementById('skeletonLoader');
                if (skeletonLoader) {
                    setTimeout(function() {
                        skeletonLoader.classList.add('fade-out');
                        setTimeout(function() {
                            skeletonLoader.style.display = 'none';
                        }, 300);
                    }, 1500); // 1.5 segundos
                }
            }
            
            // Iniciar quando DOM estiver pronto
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', hideSkeletonLoader);
            } else {
                hideSkeletonLoader();
            }
        })();
    </script>

    <!-- Modal Sistema de Touros Full Screen -->
    <div id="bulls-modal-fullscreen" class="fixed inset-0 bg-gray-50 z-[9999] hidden overflow-y-auto">
        <div class="w-full h-full flex flex-col">
            <!-- Header -->
            <header class="bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg sticky top-0 z-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center p-2">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold">Sistema de Touros</h1>
                                <p class="text-red-200 text-sm"><?php echo htmlspecialchars($farm_name ?? 'Lagoa Do Mato'); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <button onclick="openCreateBullModal()" class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg font-semibold transition-colors">
                                Novo Touro
                            </button>
                            
                            <button onclick="closeBullsModal()" class="text-white hover:text-red-200 p-2 flex items-center space-x-2" title="Fechar">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
                <!-- Estatísticas Gerais -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm mb-1">Total de Touros</p>
                                <p class="text-2xl font-bold text-gray-900" id="bulls-stat-total">-</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm mb-1">Em Reprodução</p>
                                <p class="text-2xl font-bold text-gray-900" id="bulls-stat-breeding">-</p>
                            </div>
                            <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm mb-1">Taxa de Eficiência</p>
                                <p class="text-2xl font-bold text-gray-900" id="bulls-stat-efficiency">-</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm mb-1">Sêmen Disponível</p>
                                <p class="text-2xl font-bold text-gray-900" id="bulls-stat-semen">-</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-xl p-6 mb-8 shadow-sm border border-gray-200">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <input type="text" 
                                   id="bulls-search-input" 
                                   placeholder="Buscar por nome, código, brinco ou RFID..." 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>
                        
                        <select id="bulls-filter-breed" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                            <option value="">Todas as raças</option>
                        </select>
                        
                        <select id="bulls-filter-status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                            <option value="">Todos os status</option>
                            <option value="ativo">Ativo</option>
                            <option value="em_reproducao">Em Reprodução</option>
                            <option value="reserva">Reserva</option>
                            <option value="descartado">Descartado</option>
                            <option value="falecido">Falecido</option>
                        </select>
                        
                        <button onclick="bullsLoadBulls()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold transition-colors">
                            Filtrar
                        </button>
                        
                        <button onclick="bullsResetFilters()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold transition-colors">
                            Limpar
                        </button>
                    </div>
                </div>
                
                <!-- Lista de Touros -->
                <div id="bulls-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Cards serão carregados via JavaScript -->
                </div>
                
                <!-- Loading -->
                <div id="bulls-loading" class="text-center py-12 hidden">
                    <div class="inline-block w-8 h-8 border-4 border-red-600 border-t-transparent rounded-full animate-spin"></div>
                    <p class="mt-4 text-gray-600">Carregando touros...</p>
                </div>
                
                <!-- Empty State -->
                <div id="bulls-empty-state" class="text-center py-12 hidden">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum touro encontrado</h3>
                    <p class="mt-1 text-sm text-gray-500">Comece criando um novo touro.</p>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal de Cadastro/Edição de Touro Full Screen -->
    <div id="bull-modal" class="fixed inset-0 bg-gray-50 z-[10000] hidden overflow-y-auto">
        <div class="w-full h-full flex flex-col">
            <!-- Header -->
            <header class="bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg sticky top-0 z-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center p-2">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold" id="bull-modal-title">Novo Touro</h1>
                                <p class="text-red-200 text-sm"><?php echo htmlspecialchars($farm_name ?? 'Lagoa Do Mato'); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <button onclick="closeBullModal()" class="text-white hover:text-red-200 p-2 flex items-center space-x-2" title="Fechar">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
                <form id="bull-form" class="space-y-6">
                    <input type="hidden" id="bull-id" name="id">
                    
                    <!-- Seção: Dados Básicos -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                        <h3 class="text-lg font-bold mb-4 text-gray-900 flex items-center space-x-2">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>Dados Básicos</span>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número/Código *</label>
                            <input type="text" id="bull-number" name="bull_number" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                            <input type="text" id="bull-name" name="name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Raça *</label>
                            <input type="text" id="bull-breed" name="breed" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento *</label>
                            <input type="date" id="bull-birth-date" name="birth_date" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">RFID</label>
                            <input type="text" id="bull-rfid" name="rfid_code" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nº Brinco</label>
                            <input type="text" id="bull-earring" name="earring_number" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="bull-status" name="status" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                <option value="ativo">Ativo</option>
                                <option value="em_reproducao">Em Reprodução</option>
                                <option value="reserva">Reserva</option>
                                <option value="descartado">Descartado</option>
                                <option value="falecido">Falecido</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Origem</label>
                            <select id="bull-source" name="source" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                <option value="proprio">Próprio</option>
                                <option value="comprado">Comprado</option>
                                <option value="arrendado">Arrendado</option>
                                <option value="doador_genetico">Doador Genético</option>
                                <option value="inseminacao">Inseminação</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Localização</label>
                            <input type="text" id="bull-location" name="location" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ativo em Reprodução</label>
                            <select id="bull-breeding-active" name="is_breeding_active" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                <option value="1">Sim</option>
                                <option value="0">Não</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Peso Inicial (kg)</label>
                            <input type="number" step="0.01" id="bull-weight" name="weight" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Escore Corporal Inicial (1-5)</label>
                            <input type="number" step="0.1" min="1" max="5" id="bull-body-score" name="body_score" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        </div>
                    </div>
                    
                    <!-- Seção: Genealogia -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                        <h3 class="text-lg font-bold mb-4 text-gray-900 flex items-center space-x-2">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <span>Genealogia</span>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pai (Sire)</label>
                            <input type="text" id="bull-sire" name="sire" autocomplete="off"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <div id="bull-sire-autocomplete" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mãe (Dam)</label>
                            <input type="text" id="bull-dam" name="dam" autocomplete="off"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <div id="bull-dam-autocomplete" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Avô Paterno (Grandsire Father)</label>
                            <input type="text" id="bull-grandsire-father" name="grandsire_father" autocomplete="off"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <div id="bull-grandsire-father-autocomplete" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Avó Paterna (Granddam Father)</label>
                            <input type="text" id="bull-granddam-father" name="granddam_father" autocomplete="off"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <div id="bull-granddam-father-autocomplete" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Avô Materno (Grandsire Mother)</label>
                            <input type="text" id="bull-grandsire-mother" name="grandsire_mother" autocomplete="off"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <div id="bull-grandsire-mother-autocomplete" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Avó Materna (Granddam Mother)</label>
                            <input type="text" id="bull-granddam-mother" name="granddam_mother" autocomplete="off"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <div id="bull-granddam-mother-autocomplete" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        </div>
                    </div>
                    
                    <!-- Seção: Avaliação Genética -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                        <h3 class="text-lg font-bold mb-4 text-gray-900 flex items-center space-x-2">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span>Avaliação Genética</span>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código Genético</label>
                            <input type="text" id="bull-genetic-code" name="genetic_code" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mérito Genético</label>
                            <input type="number" step="0.01" id="bull-genetic-merit" name="genetic_merit" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Índice de Produção de Leite</label>
                            <input type="number" step="0.01" id="bull-milk-index" name="milk_production_index" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Índice de Produção de Gordura</label>
                            <input type="number" step="0.01" id="bull-fat-index" name="fat_production_index" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Índice de Produção de Proteína</label>
                            <input type="number" step="0.01" id="bull-protein-index" name="protein_production_index" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Índice de Fertilidade</label>
                            <input type="number" step="0.01" id="bull-fertility-index" name="fertility_index" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Índice de Saúde</label>
                            <input type="number" step="0.01" id="bull-health-index" name="health_index" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>
                        </div>
                        
                        <div class="mt-4 md:col-span-2 lg:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Avaliação Genética (Texto)</label>
                            <textarea id="bull-genetic-evaluation" name="genetic_evaluation" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" 
                                      placeholder="Avaliação genética detalhada..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Seção: Observações -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                        <h3 class="text-lg font-bold mb-4 text-gray-900 flex items-center space-x-2">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span>Observações</span>
                            <span class="text-sm font-normal text-gray-500 italic">(OPCIONAL)</span>
                        </h3>
                        <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observações sobre Comportamento</label>
                            <textarea id="bull-behavior-notes" name="behavior_notes" rows="2" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" 
                                      placeholder="Observações sobre o comportamento do touro..."></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observações sobre Aptidão</label>
                            <textarea id="bull-aptitude-notes" name="aptitude_notes" rows="2" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" 
                                      placeholder="Observações sobre aptidão do touro..."></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observações Gerais</label>
                            <textarea id="bull-notes" name="notes" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" 
                                      placeholder="Observações gerais..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Botões de Ação -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeBullModal()" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold transition-colors flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <span>Cancelar</span>
                            </button>
                            <button type="submit" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold transition-colors flex items-center space-x-2" id="bull-submit-btn">
                                <span id="bull-submit-text">Salvar Touro</span>
                                <span id="bull-submit-loading" class="hidden ml-2 inline-block w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            </button>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <!-- Script Sistema de Touros -->
    <script>
        // Funções para abrir/fechar modal
        function openBullsModal() {
            document.getElementById('bulls-modal-fullscreen').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            bullsLoadStatistics();
            bullsLoadBulls();
        }

        function closeBullsModal() {
            const modal = document.getElementById('bulls-modal-fullscreen');
            if (modal) {
                modal.classList.add('hidden');
            }
            // Fechar modal de detalhes se estiver aberto
            const detailsModal = document.getElementById('bull-details-modal-fullscreen');
            if (detailsModal && !detailsModal.classList.contains('hidden')) {
                detailsModal.classList.add('hidden');
            }
            // Restaurar overflow apenas se não houver outros modais abertos
            if (!document.getElementById('bull-modal') || document.getElementById('bull-modal').classList.contains('hidden')) {
                document.body.style.overflow = '';
            }
        }

        function openCreateBullModal() {
            document.getElementById('bull-modal').classList.remove('hidden');
            document.getElementById('bull-modal-title').textContent = 'Novo Touro';
            document.getElementById('bull-form').reset();
            document.getElementById('bull-id').value = '';
            document.body.style.overflow = 'hidden';
        }

        function closeBullModal() {
            const modal = document.getElementById('bull-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
            // Restaurar overflow apenas se não houver outros modais abertos
            if ((!document.getElementById('bulls-modal-fullscreen') || document.getElementById('bulls-modal-fullscreen').classList.contains('hidden')) &&
                (!document.getElementById('bull-details-modal-fullscreen') || document.getElementById('bull-details-modal-fullscreen').classList.contains('hidden'))) {
                document.body.style.overflow = '';
            }
        }

        // Variáveis globais
        const BULLS_API_BASE = 'api/bulls.php';
        let bullsCurrentBullId = null;
        let bullsData = [];
        let bullsFilters = {
            search: '',
            breed: '',
            status: ''
        };

        // Carregar estatísticas
        async function bullsLoadStatistics() {
            try {
                const result = await safeFetch(`${BULLS_API_BASE}?action=statistics`);
                
                if (result.success && result.data) {
                    const stats = result.data;
                    
                    const totalEl = document.getElementById('bulls-stat-total');
                    const breedingEl = document.getElementById('bulls-stat-breeding');
                    const efficiencyEl = document.getElementById('bulls-stat-efficiency');
                    const semenEl = document.getElementById('bulls-stat-semen');
                    
                    if (totalEl) totalEl.textContent = stats.total_bulls || 0;
                    if (breedingEl) breedingEl.textContent = stats.breeding_bulls || 0;
                    if (efficiencyEl) {
                        efficiencyEl.textContent = stats.avg_efficiency 
                            ? stats.avg_efficiency.toFixed(1) + '%' 
                            : '-';
                    }
                    if (semenEl) {
                        semenEl.textContent = (stats.semen && stats.semen.total_available) 
                            ? stats.semen.total_available 
                            : 0;
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar estatísticas:', error);
            }
        }

        // Carregar touros
        async function bullsLoadBulls() {
            const container = document.getElementById('bulls-container');
            const loading = document.getElementById('bulls-loading');
            const emptyState = document.getElementById('bulls-empty-state');
            
            // Mostrar skeleton loader
            if (container) {
                container.innerHTML = '';
                showSkeleton(container, 'card', 6);
            }
            if (loading) loading.classList.remove('hidden');
            if (emptyState) emptyState.classList.add('hidden');
            
            try {
                const params = new URLSearchParams({
                    action: 'list',
                    search: bullsFilters.search || '',
                    breed: bullsFilters.breed || '',
                    status: bullsFilters.status || ''
                });
                
                const result = await safeFetch(`${BULLS_API_BASE}?${params.toString()}`);
                
                if (result.success && result.data) {
                    // A API retorna { data: [...], pagination: {...} }
                    bullsData = Array.isArray(result.data) 
                        ? result.data 
                        : (result.data.data || result.data.bulls || []);
                    
                    // Remover skeleton loaders antes de renderizar
                    if (container) {
                        hideSkeleton(container);
                    }
                    
                    renderBullsCards(bullsData);
                } else {
                    // Remover skeleton loaders
                    if (container) {
                        hideSkeleton(container);
                    }
                    showBullsEmptyState();
                    // Mostrar erro apenas se não for uma busca vazia
                    if (bullsFilters.search || bullsFilters.breed || bullsFilters.status) {
                        showErrorToast(
                            result.error || 'Erro ao carregar touros. Tente novamente.',
                            'Erro'
                        );
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar touros:', error);
                // Remover skeleton loaders em caso de erro
                if (container) {
                    hideSkeleton(container);
                }
                showBullsEmptyState();
                showErrorToast('Erro ao carregar touros. Verifique sua conexão.', 'Erro de Conexão');
            } finally {
                if (loading) loading.classList.add('hidden');
            }
        }

        function renderBullsCards(bulls) {
            const container = document.getElementById('bulls-container');
            const emptyState = document.getElementById('bulls-empty-state');
            
            if (!container) return;
            
            if (bulls.length === 0) {
                showBullsEmptyState();
                return;
            }
            
            emptyState.classList.add('hidden');
            container.innerHTML = bulls.map(bull => createBullCard(bull)).join('');
        }

        function createBullCard(bull) {
            const statusColors = {
                'ativo': 'border-green-500',
                'em_reproducao': 'border-amber-500',
                'reserva': 'border-gray-500',
                'descartado': 'border-red-500',
                'falecido': 'border-red-500'
            };
            
            const statusColor = statusColors[bull.status] || 'border-gray-500';
            
            return `
                <div class="bg-white rounded-xl p-6 shadow-sm border-l-4 ${statusColor} cursor-pointer hover:shadow-md transition-shadow" onclick="viewBullDetails(${bull.id})">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">${bull.name || bull.bull_number}</h3>
                            <p class="text-sm text-gray-600">${bull.bull_number}</p>
                        </div>
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">${bull.status || 'ativo'}</span>
                    </div>
                    <div class="space-y-2">
                        <p class="text-sm text-gray-600"><span class="font-semibold">Raça:</span> ${bull.breed || '-'}</p>
                        ${bull.birth_date ? `<p class="text-sm text-gray-600"><span class="font-semibold">Nascimento:</span> ${new Date(bull.birth_date).toLocaleDateString('pt-BR')}</p>` : ''}
                    </div>
                </div>
            `;
        }

        function showBullsEmptyState() {
            const container = document.getElementById('bulls-container');
            const emptyState = document.getElementById('bulls-empty-state');
            
            if (container) container.innerHTML = '';
            if (emptyState) emptyState.classList.remove('hidden');
        }

        function bullsResetFilters() {
            bullsFilters = { search: '', breed: '', status: '' };
            document.getElementById('bulls-search-input').value = '';
            document.getElementById('bulls-filter-breed').value = '';
            document.getElementById('bulls-filter-status').value = '';
            bullsLoadBulls();
        }

        function viewBullDetails(id) {
            openBullDetailsModal(id);
        }

        function openBullDetailsModal(bullId) {
            document.getElementById('bull-details-modal-fullscreen').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            loadBullDetailsData(bullId);
        }

        function closeBullDetailsModal() {
            const modal = document.getElementById('bull-details-modal-fullscreen');
            if (modal) {
                modal.classList.add('hidden');
            }
            // Restaurar overflow apenas se não houver outros modais abertos
            if (!document.getElementById('bulls-modal-fullscreen') || document.getElementById('bulls-modal-fullscreen').classList.contains('hidden')) {
                document.body.style.overflow = '';
            }
        }

        async function loadBullDetailsData(bullId) {
            if (!bullId) {
                showErrorToast('ID do touro não fornecido', 'Erro');
                return;
            }
            
            // Armazenar ID no modal para uso posterior
            const modal = document.getElementById('bull-details-modal-fullscreen');
            if (modal) {
                modal.dataset.bullId = bullId;
            }
            
            try {
                const result = await safeFetch(`${BULLS_API_BASE}?action=get&id=${bullId}`);
                
                if (result.success && result.data) {
                    const bull = result.data;
                    renderBullDetailsInfo(bull);
                } else {
                    showErrorToast(
                        result.error || 'Erro ao carregar dados do touro. Tente novamente.',
                        'Erro'
                    );
                    closeBullDetailsModal();
                }
            } catch (error) {
                console.error('Erro ao carregar dados do touro:', error);
                showErrorToast(
                    'Erro ao carregar dados do touro. Verifique sua conexão.',
                    'Erro de Conexão'
                );
                closeBullDetailsModal();
            }
        }

        function renderBullDetailsInfo(data) {
            // Header
            const headerName = document.getElementById('bull-details-name-header');
            if (headerName) {
                headerName.textContent = data.name || data.bull_number || 'Sem nome';
            }
            
            // Informações básicas
            setBullDetailsText('bull-details-number', data.bull_number || '-');
            setBullDetailsText('bull-details-breed', data.breed || '-');
            setBullDetailsText('bull-details-age', data.age ? data.age + ' anos' : '-');
            setBullDetailsText('bull-details-weight', data.current_weight ? data.current_weight + ' kg' : data.weight ? data.weight + ' kg' : '-');
            
            // Status
            const statusBadge = document.getElementById('bull-details-status-badge');
            if (statusBadge) {
                statusBadge.textContent = getBullStatusLabel(data.status || 'ativo');
                statusBadge.className = 'px-2 py-1 rounded-full text-xs font-semibold ' + getBullStatusClass(data.status || 'ativo');
            }
            
            // Eficiência
            const totalServices = (parseInt(data.total_inseminations) || 0) + (parseInt(data.total_coatings) || 0);
            const totalSuccessful = (parseInt(data.successful_inseminations) || 0) + (parseInt(data.successful_coatings) || 0);
            const efficiency = totalServices > 0 ? ((totalSuccessful / totalServices) * 100).toFixed(1) : 0;
            setBullDetailsText('bull-details-efficiency', efficiency + '%');
            
            // Aba Informações
            setBullDetailsText('bull-details-info-name', data.name || '-');
            setBullDetailsText('bull-details-info-rfid', data.rfid_code || '-');
            setBullDetailsText('bull-details-info-earring', data.earring_number || '-');
            setBullDetailsText('bull-details-info-birth-date', data.birth_date ? new Date(data.birth_date).toLocaleDateString('pt-BR') : '-');
            setBullDetailsText('bull-details-info-source', getBullSourceLabel(data.source));
            setBullDetailsText('bull-details-info-location', data.location || '-');
            
            // Genealogia
            setBullDetailsText('bull-details-info-sire', data.sire || '-');
            setBullDetailsText('bull-details-info-dam', data.dam || '-');
            setBullDetailsText('bull-details-info-grandsire-father', data.grandsire_father || '-');
            setBullDetailsText('bull-details-info-granddam-father', data.granddam_father || '-');
            
            // Avaliação Genética
            setBullDetailsText('bull-details-info-genetic-merit', data.genetic_merit || '-');
            setBullDetailsText('bull-details-info-milk-index', data.milk_production_index || '-');
            setBullDetailsText('bull-details-info-fat-index', data.fat_production_index || '-');
            setBullDetailsText('bull-details-info-protein-index', data.protein_production_index || '-');
        }

        function setBullDetailsText(id, text) {
            const el = document.getElementById(id);
            if (el) el.textContent = text;
        }

        function getBullStatusLabel(status) {
            const labels = {
                'ativo': 'Ativo',
                'em_reproducao': 'Em Reprodução',
                'reserva': 'Reserva',
                'descartado': 'Descartado',
                'falecido': 'Falecido'
            };
            return labels[status] || status;
        }

        function getBullStatusClass(status) {
            const classes = {
                'ativo': 'bg-green-100 text-green-800',
                'em_reproducao': 'bg-amber-100 text-amber-800',
                'reserva': 'bg-gray-100 text-gray-800',
                'descartado': 'bg-red-100 text-red-800',
                'falecido': 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }

        function getBullSourceLabel(source) {
            const labels = {
                'proprio': 'Próprio',
                'comprado': 'Comprado',
                'arrendado': 'Arrendado',
                'doador_genetico': 'Doador Genético',
                'inseminacao': 'Inseminação'
            };
            return labels[source] || source;
        }

        async function editBullFromDetails() {
            const modal = document.getElementById('bull-details-modal-fullscreen');
            const bullId = modal?.dataset?.bullId;
            
            if (!bullId) {
                showErrorToast('ID do touro não encontrado', 'Erro');
                return;
            }
            
            try {
                const result = await safeFetch(`${BULLS_API_BASE}?action=get&id=${bullId}`);
                
                if (result.success && result.data) {
                    const bull = result.data;
                    
                    // Preencher formulário com verificações de segurança
                    const setValue = (id, value) => {
                        const el = document.getElementById(id);
                        if (el) el.value = value || '';
                    };
                    
                    setValue('bull-id', bull.id);
                    setValue('bull-number', bull.bull_number);
                    setValue('bull-name', bull.name);
                    setValue('bull-breed', bull.breed);
                    setValue('bull-birth-date', bull.birth_date);
                    setValue('bull-rfid', bull.rfid_code);
                    setValue('bull-earring', bull.earring_number);
                    setValue('bull-status', bull.status || 'ativo');
                    setValue('bull-source', bull.source || 'proprio');
                    setValue('bull-location', bull.location);
                    setValue('bull-breeding-active', bull.is_breeding_active ? '1' : '0');
                    setValue('bull-weight', bull.weight);
                    setValue('bull-body-score', bull.body_score);
                    setValue('bull-sire', bull.sire);
                    setValue('bull-dam', bull.dam);
                    setValue('bull-grandsire-father', bull.grandsire_father);
                    setValue('bull-granddam-father', bull.granddam_father);
                    setValue('bull-grandsire-mother', bull.grandsire_mother);
                    setValue('bull-granddam-mother', bull.granddam_mother);
                    setValue('bull-genetic-code', bull.genetic_code);
                    setValue('bull-genetic-merit', bull.genetic_merit);
                    setValue('bull-milk-index', bull.milk_production_index);
                    setValue('bull-fat-index', bull.fat_production_index);
                    setValue('bull-protein-index', bull.protein_production_index);
                    setValue('bull-fertility-index', bull.fertility_index);
                    setValue('bull-health-index', bull.health_index);
                    setValue('bull-genetic-evaluation', bull.genetic_evaluation);
                    setValue('bull-behavior-notes', bull.behavior_notes);
                    setValue('bull-aptitude-notes', bull.aptitude_notes);
                    setValue('bull-notes', bull.notes);
                    
                    // Atualizar título
                    const titleEl = document.getElementById('bull-modal-title');
                    if (titleEl) titleEl.textContent = 'Editar Touro';
                    
                    // Fechar modal de detalhes e abrir modal de edição
                    closeBullDetailsModal();
                    const editModal = document.getElementById('bull-modal');
                    if (editModal) {
                        editModal.classList.remove('hidden');
                        document.body.style.overflow = 'hidden';
                    } else {
                        showErrorToast('Modal de edição não encontrado', 'Erro');
                    }
                } else {
                    showErrorToast(
                        result.error || 'Erro ao carregar dados do touro para edição. Tente novamente.',
                        'Erro'
                    );
                }
            } catch (error) {
                console.error('Erro ao carregar dados do touro:', error);
                showErrorToast(
                    'Erro ao carregar dados do touro para edição. Verifique sua conexão.',
                    'Erro de Conexão'
                );
            }
        }

        // Autocomplete para campos de genealogia
        function initGenealogyAutocomplete(inputId, autocompleteId) {
            const input = document.getElementById(inputId);
            const autocomplete = document.getElementById(autocompleteId);
            let searchTimeout;
            let selectedIndex = -1;
            
            if (!input || !autocomplete) return;
            
            input.addEventListener('input', function(e) {
                const query = e.target.value.trim();
                
                clearTimeout(searchTimeout);
                
                if (query.length < 2) {
                    autocomplete.classList.add('hidden');
                    return;
                }
                
                searchTimeout = setTimeout(async () => {
                    try {
                        const response = await fetch(`${BULLS_API_BASE}?action=search_names&q=${encodeURIComponent(query)}&limit=10`);
                        const result = await response.json();
                        
                        if (result.success && result.data && result.data.length > 0) {
                            autocomplete.innerHTML = result.data.map((item, index) => `
                                <div class="autocomplete-item px-4 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-200 last:border-b-0 ${index === 0 ? 'bg-gray-50' : ''}" 
                                     data-index="${index}" 
                                     data-name="${item.display_name || item.bull_number || ''}">
                                    <div class="font-medium text-gray-900">${item.display_name || item.bull_number || '-'}</div>
                                    <div class="text-xs text-gray-500">${item.bull_number || ''} - ${item.breed || ''} (${item.type === 'touro' ? 'Touro' : 'Animal'})</div>
                                </div>
                            `).join('');
                            
                            autocomplete.classList.remove('hidden');
                            selectedIndex = -1;
                            
                            // Adicionar eventos de clique
                            autocomplete.querySelectorAll('.autocomplete-item').forEach((item, index) => {
                                item.addEventListener('click', function() {
                                    const name = this.getAttribute('data-name');
                                    input.value = name;
                                    autocomplete.classList.add('hidden');
                                });
                                
                                item.addEventListener('mouseenter', function() {
                                    autocomplete.querySelectorAll('.autocomplete-item').forEach(i => i.classList.remove('bg-gray-50'));
                                    this.classList.add('bg-gray-50');
                                    selectedIndex = index;
                                });
                            });
                        } else {
                            autocomplete.classList.add('hidden');
                        }
                    } catch (error) {
                        console.error('Erro ao buscar nomes:', error);
                        autocomplete.classList.add('hidden');
                    }
                }, 300);
            });
            
            input.addEventListener('keydown', function(e) {
                const items = autocomplete.querySelectorAll('.autocomplete-item');
                
                if (items.length === 0) return;
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    items.forEach((item, index) => {
                        item.classList.remove('bg-gray-50');
                        if (index === selectedIndex) {
                            item.classList.add('bg-gray-50');
                            item.scrollIntoView({ block: 'nearest' });
                        }
                    });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    items.forEach((item, index) => {
                        item.classList.remove('bg-gray-50');
                        if (index === selectedIndex) {
                            item.classList.add('bg-gray-50');
                            item.scrollIntoView({ block: 'nearest' });
                        }
                    });
                } else if (e.key === 'Enter' && selectedIndex >= 0) {
                    e.preventDefault();
                    const selectedItem = items[selectedIndex];
                    const name = selectedItem.getAttribute('data-name');
                    input.value = name;
                    autocomplete.classList.add('hidden');
                } else if (e.key === 'Escape') {
                    autocomplete.classList.add('hidden');
                }
            });
            
            // Fechar autocomplete ao clicar fora
            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !autocomplete.contains(e.target)) {
                    autocomplete.classList.add('hidden');
                }
            });
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar autocomplete para campos de genealogia
            initGenealogyAutocomplete('bull-sire', 'bull-sire-autocomplete');
            initGenealogyAutocomplete('bull-dam', 'bull-dam-autocomplete');
            initGenealogyAutocomplete('bull-grandsire-father', 'bull-grandsire-father-autocomplete');
            initGenealogyAutocomplete('bull-granddam-father', 'bull-granddam-father-autocomplete');
            initGenealogyAutocomplete('bull-grandsire-mother', 'bull-grandsire-mother-autocomplete');
            initGenealogyAutocomplete('bull-granddam-mother', 'bull-granddam-mother-autocomplete');
            
            const searchInput = document.getElementById('bulls-search-input');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function(e) {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        bullsFilters.search = e.target.value;
                        bullsLoadBulls();
                    }, 500);
                });
            }
            
            const filterBreed = document.getElementById('bulls-filter-breed');
            const filterStatus = document.getElementById('bulls-filter-status');
            
            if (filterBreed) {
                filterBreed.addEventListener('change', function(e) {
                    bullsFilters.breed = e.target.value;
                    bullsLoadBulls();
                });
            }
            
            if (filterStatus) {
                filterStatus.addEventListener('change', function(e) {
                    bullsFilters.status = e.target.value;
                    bullsLoadBulls();
                });
            }
            
            const form = document.getElementById('bull-form');
            if (form) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const submitBtn = document.getElementById('bull-submit-btn');
                    const submitText = document.getElementById('bull-submit-text');
                    const submitLoading = document.getElementById('bull-submit-loading');
                    
                    // Desabilitar botão e mostrar loading
                    submitBtn.disabled = true;
                    submitText.textContent = 'Salvando...';
                    submitLoading.classList.remove('hidden');
                    
                    try {
                        const formData = new FormData(form);
                        const data = {};
                        
                        // Coletar todos os campos do formulário
                        for (let [key, value] of formData.entries()) {
                            if (value !== '') {
                                data[key] = value;
                            }
                        }
                        
                        // Validações básicas
                        if (!data.bull_number && !data.name) {
                            showErrorToast('Informe pelo menos o número ou nome do touro', 'Erro de Validação');
                            return;
                        }
                        
                        // Converter is_breeding_active para int
                        if (data.is_breeding_active !== undefined) {
                            data.is_breeding_active = data.is_breeding_active === '1' ? 1 : 0;
                        }
                        
                        // Converter campos numéricos com validação
                        try {
                            if (data.weight) data.weight = parseFloat(data.weight);
                            if (data.body_score) data.body_score = parseFloat(data.body_score);
                            if (data.genetic_merit) data.genetic_merit = parseFloat(data.genetic_merit);
                            if (data.milk_production_index) data.milk_production_index = parseFloat(data.milk_production_index);
                            if (data.fat_production_index) data.fat_production_index = parseFloat(data.fat_production_index);
                            if (data.protein_production_index) data.protein_production_index = parseFloat(data.protein_production_index);
                            if (data.fertility_index) data.fertility_index = parseFloat(data.fertility_index);
                            if (data.health_index) data.health_index = parseFloat(data.health_index);
                        } catch (parseError) {
                            showErrorToast('Erro ao processar valores numéricos. Verifique os campos.', 'Erro de Validação');
                            return;
                        }
                        
                        const bullId = data.id;
                        const action = bullId ? 'update' : 'create';
                        const method = bullId ? 'PUT' : 'POST';
                        
                        const result = await safeFetch(`${BULLS_API_BASE}?action=${action}`, {
                            method: method,
                            body: JSON.stringify(data)
                        });
                        
                        if (result.success) {
                            // Fechar modal e recarregar lista
                            closeBullModal();
                            bullsLoadBulls();
                            bullsLoadStatistics();
                            
                            // Mostrar mensagem de sucesso
                            showSuccessToast(
                                bullId ? 'Touro atualizado com sucesso!' : 'Touro cadastrado com sucesso!',
                                'Sucesso'
                            );
                        } else {
                            showErrorToast(
                                result.error || 'Erro ao salvar touro. Verifique os dados e tente novamente.',
                                'Erro'
                            );
                        }
                    } catch (error) {
                        console.error('Erro ao salvar touro:', error);
                        showErrorToast(
                            'Erro ao salvar touro. Verifique sua conexão e tente novamente.',
                            'Erro de Conexão'
                        );
                    } finally {
                        // Reabilitar botão e esconder loading
                        submitBtn.disabled = false;
                        submitText.textContent = 'Salvar Touro';
                        submitLoading.classList.add('hidden');
                    }
                });
            }
        });
    </script>

    <!-- Modal Detalhes do Touro Full Screen -->
    <div id="bull-details-modal-fullscreen" class="fixed inset-0 bg-gray-50 z-[10000] hidden overflow-y-auto">
        <div class="w-full h-full flex flex-col">
            <!-- Header -->
            <header class="bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg sticky top-0 z-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">
                        <div class="flex items-center space-x-4">
                            <button onclick="closeBullDetailsModal()" class="flex items-center space-x-4 text-white hover:opacity-80 transition-opacity">
                                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center p-2">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-xl font-bold">Detalhes do Touro</h1>
                                    <p class="text-red-200 text-sm" id="bull-details-name-header">Carregando...</p>
                                </div>
                            </button>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <button onclick="editBullFromDetails()" class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg font-semibold transition-colors flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                <span>Editar</span>
                            </button>
                            
                            <button onclick="closeBullDetailsModal()" class="text-white hover:text-red-200 p-2 flex items-center space-x-2" title="Fechar">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
                <!-- Informações Básicas -->
                <div class="bg-white rounded-xl p-6 mb-6 shadow-sm border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-gray-600 text-sm mb-1">Número/Código</p>
                            <p class="text-lg font-bold text-gray-900" id="bull-details-number">-</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm mb-1">Raça</p>
                            <p class="text-lg font-bold text-gray-900" id="bull-details-breed">-</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm mb-1">Status</p>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold" id="bull-details-status-badge">-</span>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm mb-1">Idade</p>
                            <p class="text-lg font-bold text-gray-900" id="bull-details-age">-</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm mb-1">Peso Atual</p>
                            <p class="text-lg font-bold text-gray-900" id="bull-details-weight">-</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm mb-1">Eficiência Reprodutiva</p>
                            <p class="text-lg font-bold text-gray-900" id="bull-details-efficiency">-</p>
                        </div>
                    </div>
                </div>
                
                <!-- Informações Detalhadas -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <h3 class="text-lg font-bold mb-4 text-gray-900">Informações Completas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-base font-semibold mb-3 text-gray-800">Dados Básicos</h4>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm text-gray-600">Nome</p>
                                    <p class="text-base font-medium text-gray-900" id="bull-details-info-name">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">RFID</p>
                                    <p class="text-base font-medium text-gray-900" id="bull-details-info-rfid">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Nº Brinco</p>
                                    <p class="text-base font-medium text-gray-900" id="bull-details-info-earring">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Data de Nascimento</p>
                                    <p class="text-base font-medium text-gray-900" id="bull-details-info-birth-date">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Origem</p>
                                    <p class="text-base font-medium text-gray-900" id="bull-details-info-source">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Localização</p>
                                    <p class="text-base font-medium text-gray-900" id="bull-details-info-location">-</p>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="text-base font-semibold mb-3 text-gray-800">Genealogia</h4>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm text-gray-600">Pai (Sire)</p>
                                    <p class="text-base font-medium text-gray-900" id="bull-details-info-sire">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Mãe (Dam)</p>
                                    <p class="text-base font-medium text-gray-900" id="bull-details-info-dam">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Avô Paterno (Grandsire)</p>
                                    <p class="text-base font-medium text-gray-900" id="bull-details-info-grandsire-father">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Avó Paterna (Granddam)</p>
                                    <p class="text-base font-medium text-gray-900" id="bull-details-info-granddam-father">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h4 class="text-base font-semibold mb-3 text-gray-800">Avaliação Genética</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">Mérito Genético</p>
                                <p class="text-xl font-bold text-gray-900" id="bull-details-info-genetic-merit">-</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">Índice Leite</p>
                                <p class="text-xl font-bold text-gray-900" id="bull-details-info-milk-index">-</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">Índice Gordura</p>
                                <p class="text-xl font-bold text-gray-900" id="bull-details-info-fat-index">-</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">Índice Proteína</p>
                                <p class="text-xl font-bold text-gray-900" id="bull-details-info-protein-index">-</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Solicitar Permissão de Localização -->
    <div id="locationPermissionModal" class="fixed inset-0 z-[70] hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeLocationPermissionModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5 rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Permissão de Localização</h3>
                            <p class="text-blue-100 text-sm">Precisamos da sua localização</p>
                        </div>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="px-6 py-6">
                    <div class="mb-6">
                        <p class="text-gray-700 mb-4">
                            Para fornecer uma localização mais precisa no mapa, precisamos da sua permissão para acessar sua localização GPS.
                        </p>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-900 mb-1">Por que precisamos?</p>
                                    <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                                        <li>Localização precisa no mapa</li>
                                        <li>Segurança da sua conta</li>
                                        <li>Detecção de acessos suspeitos</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-gray-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 mb-1">Sua privacidade</p>
                                    <p class="text-sm text-gray-700">
                                        Sua localização é usada apenas para segurança e não é compartilhada com terceiros.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex gap-3">
                        <button 
                            onclick="denyLocationPermission()" 
                            class="flex-1 px-4 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                        >
                            Não Permitir
                        </button>
                        <button 
                            onclick="allowLocationPermission()" 
                            class="flex-1 px-4 py-3 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-lg transition-all shadow-md hover:shadow-lg"
                        >
                            Permitir Localização
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalhes do Dispositivo (Full Screen) -->
    <div id="deviceDetailsModal" class="fixed inset-0 z-[60] hidden bg-white">
        <div class="w-full h-full flex flex-col">
            <!-- Header -->
            <div class="sticky top-0 z-10 bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white" id="deviceDetailsTitle">Detalhes do Dispositivo</h3>
                        <p class="text-blue-100 text-sm" id="deviceDetailsSubtitle">Informações completas e localização</p>
                    </div>
                </div>
                <button onclick="closeDeviceDetailsModal()" class="w-10 h-10 flex items-center justify-center bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl transition-all text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-7xl mx-auto">
                    <!-- Informações do Dispositivo -->
                    <div class="bg-white rounded-xl p-6 mb-6 shadow-sm border border-gray-200">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Informações do Dispositivo</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="deviceInfoContent">
                            <!-- Será preenchido via JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Mapa -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-semibold text-gray-900">Localização</h4>
                            <button id="updateLocationBtn" onclick="updateLocationFromModal()" class="px-3 py-1.5 text-sm font-medium text-green-600 hover:text-green-700 border border-green-600 rounded-lg hover:bg-green-50 transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Atualizar Localização
                            </button>
                        </div>
                        <div id="deviceMap" class="w-full h-96 rounded-lg border border-gray-300" style="min-height: 400px;">
                            <!-- Mapa será renderizado aqui -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir Overlay de Novilhas -->
    <?php include __DIR__ . '/includes/heifer-overlay.html'; ?>

    <script>
        // Funções para gerenciar dispositivos
        window.openDevicesModal = function() {
            const modal = document.getElementById('devicesModal');
            if (modal) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                loadDevices();
            }
        };
        
        window.closeDevicesModal = function() {
            const modal = document.getElementById('devicesModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        };
        
        let currentDeviceData = null;
        
        window.openDeviceDetailsModal = function(deviceData) {
            const modal = document.getElementById('deviceDetailsModal');
            if (modal) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                currentDeviceData = deviceData;
                showDeviceDetails(deviceData);
            }
        };
        
        window.closeDeviceDetailsModal = function() {
            const modal = document.getElementById('deviceDetailsModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
                // Limpar mapa
                const mapContainer = document.getElementById('deviceMap');
                if (mapContainer) {
                    mapContainer.innerHTML = '';
                }
            }
        };
        
        async function loadDevices() {
            const devicesList = document.getElementById('devicesList');
            if (!devicesList) return;
            
            devicesList.innerHTML = `
                <div class="text-center text-gray-500 py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto mb-4"></div>
                    <p>Carregando dispositivos...</p>
                </div>
            `;
            
            try {
                const response = await fetch('./api/actions.php?action=get_active_sessions');
                const result = await response.json();
                
                if (result.success && result.sessions) {
                    const sessions = result.sessions;
                    
                    if (sessions.length === 0) {
                        devicesList.innerHTML = `
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-gray-500">Nenhum dispositivo conectado encontrado.</p>
                            </div>
                        `;
                    } else {
                        devicesList.innerHTML = sessions.map(session => {
                            const date = new Date(session.lastActive);
                            const formattedDate = date.toLocaleDateString('pt-BR', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            
                            const deviceIcon = session.device_type === 'mobile' 
                                ? `<svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>`
                                : `<svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>`;
                            
                            const currentBadge = session.current 
                                ? `<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-lg">Dispositivo Atual</span>`
                                : '';
                            
                            const sessionCountBadge = session.sessionCount > 1
                                ? `<span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded-lg">${session.sessionCount} sessões</span>`
                                : '';
                            
                            return `
                                <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-start gap-4 flex-1">
                                            <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                                ${deviceIcon}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <h4 class="text-base font-semibold text-gray-900">${session.device || 'Dispositivo Desconhecido'}</h4>
                                                    ${currentBadge}
                                                    ${sessionCountBadge}
                                                </div>
                                                <p class="text-sm text-gray-600 mb-2">${session.location || 'Localização não disponível'}</p>
                                                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        </svg>
                                                        ${session.ip || 'N/A'}
                                                    </span>
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        ${formattedDate}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 ml-4">
                                            <button onclick='openDeviceDetailsModal(${JSON.stringify(session).replace(/"/g, '&quot;')})' class="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 border border-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                                                Mais Informações
                                            </button>
                                            ${(!session.latitude || !session.longitude) && session.ip && session.ip !== '127.0.0.1' ? `
                                                <button onclick='updateDeviceLocation(${session.id})' class="px-3 py-2 text-xs font-medium text-green-600 hover:text-green-700 border border-green-600 rounded-lg hover:bg-green-50 transition-colors" title="Atualizar localização">
                                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                    </svg>
                                                </button>
                                            ` : ''}
                                            ${!session.current ? `
                                                <button onclick='revokeDevice(${session.id}, "${(session.device || '').replace(/"/g, '&quot;')}")' class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700 border border-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                                    Encerrar
                                                </button>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    }
                } else {
                    devicesList.innerHTML = `
                        <div class="text-center py-12">
                            <p class="text-red-500">Erro ao carregar dispositivos: ${result.error || 'Erro desconhecido'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Erro ao carregar dispositivos:', error);
                devicesList.innerHTML = `
                    <div class="text-center py-12">
                        <p class="text-red-500">Erro ao carregar dispositivos. Tente novamente.</p>
                    </div>
                `;
            }
        }
        
        function showDeviceDetails(deviceData) {
            const title = document.getElementById('deviceDetailsTitle');
            const subtitle = document.getElementById('deviceDetailsSubtitle');
            const infoContent = document.getElementById('deviceInfoContent');
            
            if (title) title.textContent = deviceData.device || 'Dispositivo Desconhecido';
            if (subtitle) subtitle.textContent = deviceData.location || 'Localização não disponível';
            
            if (infoContent) {
                const date = new Date(deviceData.lastActive);
                const createdDate = deviceData.createdAt ? new Date(deviceData.createdAt) : null;
                
                infoContent.innerHTML = `
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Tipo de Dispositivo</p>
                        <p class="text-base font-medium text-gray-900">${deviceData.device_type === 'mobile' ? 'Móvel' : 'Desktop'}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Endereço IP</p>
                        <p class="text-base font-medium text-gray-900">${deviceData.ip || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Localização</p>
                        <p class="text-base font-medium text-gray-900">${deviceData.location || 'Não disponível'}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Cidade</p>
                        <p class="text-base font-medium text-gray-900">${deviceData.city || 'Não disponível'}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">ISP / Provedor</p>
                        <p class="text-base font-medium text-gray-900">${deviceData.isp || 'Não disponível'}</p>
                    </div>
                    ${deviceData.timezone ? `
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Fuso Horário</p>
                        <p class="text-base font-medium text-gray-900">${deviceData.timezone}</p>
                    </div>
                    ` : ''}
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Última Atividade</p>
                        <p class="text-base font-medium text-gray-900">${date.toLocaleString('pt-BR')}</p>
                    </div>
                    ${createdDate ? `
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Primeiro Acesso</p>
                        <p class="text-base font-medium text-gray-900">${createdDate.toLocaleString('pt-BR')}</p>
                    </div>
                    ` : ''}
                    ${deviceData.sessionCount > 1 ? `
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total de Sessões</p>
                        <p class="text-base font-medium text-gray-900">${deviceData.sessionCount}</p>
                    </div>
                    ` : ''}
                `;
            }
            
            // Priorizar coordenadas GPS (mais precisas) sobre coordenadas do IP
            let mapLat = deviceData.gps_latitude || deviceData.latitude;
            let mapLng = deviceData.gps_longitude || deviceData.longitude;
            let locationName = deviceData.location || deviceData.city || 'Localização';
            
            // Adicionar informação de precisão se for GPS
            if (deviceData.gps_latitude && deviceData.gps_longitude) {
                if (deviceData.gps_accuracy) {
                    locationName += ` (Precisão: ${Math.round(deviceData.gps_accuracy)}m)`;
                } else {
                    locationName += ' (GPS)';
                }
            }
            
            // Carregar mapa se tiver coordenadas válidas
            const hasValidCoords = mapLat != null && 
                                   mapLng != null && 
                                   mapLat != 0 && 
                                   mapLng != 0 &&
                                   !isNaN(mapLat) && 
                                   !isNaN(mapLng);
            
            if (hasValidCoords) {
                loadDeviceMap(
                    parseFloat(mapLat), 
                    parseFloat(mapLng), 
                    locationName,
                    deviceData.gps_accuracy
                );
            } else {
                const mapContainer = document.getElementById('deviceMap');
                if (mapContainer) {
                    mapContainer.innerHTML = `
                        <div class="w-full h-full flex items-center justify-center bg-gray-100 rounded-lg">
                            <div class="text-center">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <p class="text-gray-500 mb-2">Localização não disponível no mapa</p>
                                <p class="text-sm text-gray-400">As coordenadas de geolocalização não foram obtidas para este dispositivo.</p>
                                ${deviceData.ip && deviceData.ip !== '127.0.0.1' ? `
                                    <p class="text-xs text-gray-400 mt-2">IP: ${deviceData.ip}</p>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }
            }
        }
        
        function loadDeviceMap(lat, lng, locationName, accuracy = null) {
            const mapContainer = document.getElementById('deviceMap');
            if (!mapContainer) return;
            
            // Usar Leaflet (OpenStreetMap) - API gratuita
            if (typeof L === 'undefined') {
                // Carregar Leaflet CSS e JS
                const leafletCSS = document.createElement('link');
                leafletCSS.rel = 'stylesheet';
                leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(leafletCSS);
                
                const leafletJS = document.createElement('script');
                leafletJS.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                leafletJS.onload = () => {
                    initMap(lat, lng, locationName, accuracy);
                };
                document.head.appendChild(leafletJS);
            } else {
                initMap(lat, lng, locationName, accuracy);
            }
            
            function initMap(lat, lng, locationName, accuracy) {
                mapContainer.innerHTML = '';
                
                // Zoom baseado na precisão (se GPS) ou zoom padrão
                let zoom = 13;
                if (accuracy) {
                    // Ajustar zoom baseado na precisão
                    if (accuracy < 50) zoom = 16; // Muito preciso
                    else if (accuracy < 200) zoom = 15;
                    else if (accuracy < 500) zoom = 14;
                    else zoom = 13;
                }
                
                const map = L.map(mapContainer).setView([lat, lng], zoom);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(map);
                
                // Criar marcador
                const marker = L.marker([lat, lng]).addTo(map);
                
                // Se tiver precisão (GPS), adicionar círculo de precisão
                if (accuracy && accuracy > 0) {
                    const circle = L.circle([lat, lng], {
                        radius: accuracy,
                        color: '#3388ff',
                        fillColor: '#3388ff',
                        fillOpacity: 0.2,
                        weight: 2
                    }).addTo(map);
                }
                
                // Popup com informações
                let popupContent = `<b>${locationName}</b><br>`;
                popupContent += `Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`;
                if (accuracy) {
                    popupContent += `<br>Precisão: ${Math.round(accuracy)}m`;
                }
                
                marker.bindPopup(popupContent).openPopup();
            }
        }
        
        // Variáveis para controle do modal de permissão
        let locationPermissionCallback = null;
        let requestingLocationFor = null; // 'register' ou 'update'
        
        window.openLocationPermissionModal = function(callback, context = 'update') {
            const modal = document.getElementById('locationPermissionModal');
            if (modal) {
                locationPermissionCallback = callback;
                requestingLocationFor = context;
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        };
        
        window.closeLocationPermissionModal = function() {
            const modal = document.getElementById('locationPermissionModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
                locationPermissionCallback = null;
                requestingLocationFor = null;
            }
        };
        
        window.allowLocationPermission = async function() {
            closeLocationPermissionModal();
            
            if (!navigator.geolocation) {
                showNotification('Geolocalização não está disponível no seu navegador', 'error');
                if (locationPermissionCallback) {
                    locationPermissionCallback(null);
                }
                return;
            }
            
            try {
                const updateBtn = document.getElementById('updateLocationBtn');
                if (updateBtn && requestingLocationFor === 'update') {
                    updateBtn.disabled = true;
                    updateBtn.innerHTML = '<svg class="w-4 h-4 animate-spin inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Obtendo localização...';
                }
                
                const gpsCoords = await new Promise((resolve) => {
                    const timeout = setTimeout(() => {
                        showNotification('Tempo esgotado ao obter localização', 'error');
                        resolve(null);
                    }, 15000); // Timeout de 15 segundos
                    
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            clearTimeout(timeout);
                            resolve({
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude,
                                accuracy: position.coords.accuracy
                            });
                        },
                        (error) => {
                            clearTimeout(timeout);
                            let errorMsg = 'Erro ao obter localização';
                            switch(error.code) {
                                case error.PERMISSION_DENIED:
                                    errorMsg = 'Permissão de localização negada';
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    errorMsg = 'Localização indisponível';
                                    break;
                                case error.TIMEOUT:
                                    errorMsg = 'Tempo esgotado ao obter localização';
                                    break;
                            }
                            showNotification(errorMsg, 'error');
                            resolve(null);
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0
                        }
                    );
                });
                
                if (updateBtn && requestingLocationFor === 'update') {
                    updateBtn.disabled = false;
                    updateBtn.innerHTML = `
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Atualizar Localização
                    `;
                }
                
                if (locationPermissionCallback) {
                    locationPermissionCallback(gpsCoords);
                }
            } catch (e) {
                console.error('Erro ao obter geolocalização:', e);
                showNotification('Erro ao obter localização. Tente novamente.', 'error');
                if (locationPermissionCallback) {
                    locationPermissionCallback(null);
                }
            }
        }
        
        window.denyLocationPermission = function() {
            closeLocationPermissionModal();
            if (requestingLocationFor === 'update') {
                showNotification('Localização não atualizada. Usando localização aproximada por IP.', 'info');
            }
            if (locationPermissionCallback) {
                locationPermissionCallback(null);
            }
        };
        
        async function updateLocationFromModal() {
            if (currentDeviceData && currentDeviceData.id) {
                // Verificar se já temos permissão
                if (navigator.permissions) {
                    try {
                        const permission = await navigator.permissions.query({ name: 'geolocation' });
                        if (permission.state === 'granted') {
                            // Já tem permissão, obter localização diretamente sem modal
                            const updateBtn = document.getElementById('updateLocationBtn');
                            if (updateBtn) {
                                updateBtn.disabled = true;
                                updateBtn.innerHTML = '<svg class="w-4 h-4 animate-spin inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Obtendo localização...';
                            }
                            
                            const gpsCoords = await new Promise((resolve) => {
                                const timeout = setTimeout(() => resolve(null), 15000);
                                
                                navigator.geolocation.getCurrentPosition(
                                    (position) => {
                                        clearTimeout(timeout);
                                        resolve({
                                            latitude: position.coords.latitude,
                                            longitude: position.coords.longitude,
                                            accuracy: position.coords.accuracy
                                        });
                                    },
                                    () => {
                                        clearTimeout(timeout);
                                        resolve(null);
                                    },
                                    {
                                        enableHighAccuracy: true,
                                        timeout: 15000,
                                        maximumAge: 0
                                    }
                                );
                            });
                            
                            if (updateBtn) {
                                updateBtn.disabled = false;
                                updateBtn.innerHTML = `
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Atualizar Localização
                                `;
                            }
                            
                            await updateDeviceLocation(currentDeviceData.id, gpsCoords);
                            return;
                        } else if (permission.state === 'prompt') {
                            // Mostrar modal explicativo antes de solicitar
                            openLocationPermissionModal(async (gpsCoords) => {
                                await updateDeviceLocation(currentDeviceData.id, gpsCoords);
                            }, 'update');
                            return;
                        } else {
                            // Permissão negada, usar IP
                            showNotification('Permissão de localização negada. Usando localização aproximada por IP.', 'info');
                            await updateDeviceLocation(currentDeviceData.id, null);
                            return;
                        }
                    } catch (e) {
                        // API de permissões não suportada, mostrar modal
                        openLocationPermissionModal(async (gpsCoords) => {
                            await updateDeviceLocation(currentDeviceData.id, gpsCoords);
                        }, 'update');
                        return;
                    }
                } else {
                    // API de permissões não suportada, mostrar modal
                    openLocationPermissionModal(async (gpsCoords) => {
                        await updateDeviceLocation(currentDeviceData.id, gpsCoords);
                    }, 'update');
                }
            }
        }
        
        async function updateDeviceLocation(deviceId, gpsCoords = null) {
            try {
                const formData = new FormData();
                formData.append('device_id', deviceId);
                
                // Adicionar coordenadas GPS se disponíveis
                if (gpsCoords) {
                    formData.append('gps_latitude', gpsCoords.latitude);
                    formData.append('gps_longitude', gpsCoords.longitude);
                    if (gpsCoords.accuracy) {
                        formData.append('gps_accuracy', gpsCoords.accuracy);
                    }
                }
                
                const response = await fetch('./api/actions.php?action=update_session_location', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Localização atualizada com sucesso' + (gpsCoords ? ' (GPS)' : ''), 'success');
                    // Atualizar dados locais se o modal estiver aberto
                    if (currentDeviceData && currentDeviceData.id == deviceId && result.data) {
                        currentDeviceData.latitude = result.data.latitude;
                        currentDeviceData.longitude = result.data.longitude;
                        currentDeviceData.gps_latitude = result.data.gps_latitude;
                        currentDeviceData.gps_longitude = result.data.gps_longitude;
                        currentDeviceData.gps_accuracy = result.data.gps_accuracy;
                        currentDeviceData.location = result.data.location;
                        currentDeviceData.isp = result.data.isp;
                        currentDeviceData.timezone = result.data.timezone;
                        currentDeviceData.city = result.data.city;
                        showDeviceDetails(currentDeviceData);
                    }
                    loadDevices();
                } else {
                    showNotification(result.error || 'Erro ao atualizar localização', 'error');
                }
            } catch (error) {
                console.error('Erro ao atualizar localização:', error);
                showNotification('Erro ao atualizar localização. Tente novamente.', 'error');
            }
        }
        
        async function revokeDevice(deviceId, deviceName) {
            if (!confirm(`Tem certeza que deseja encerrar a sessão do dispositivo "${deviceName}"?`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('device_id', deviceId);
                
                const response = await fetch('./api/actions.php?action=revoke_session', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Sessão encerrada com sucesso', 'success');
                    loadDevices();
                } else {
                    showNotification(result.error || 'Erro ao encerrar sessão', 'error');
                }
            } catch (error) {
                console.error('Erro ao encerrar sessão:', error);
                showNotification('Erro ao encerrar sessão. Tente novamente.', 'error');
            }
        }
    </script>

</body>
</html>
