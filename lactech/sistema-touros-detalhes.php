<?php
/**
 * Sistema de Touros - Detalhes do Touro
 * Página completa com todas as funcionalidades do sistema de touros
 */

require_once __DIR__ . '/includes/config_login.php';

if (!isLoggedIn()) {
    header("Location: index.php", true, 302);
    exit();
}

require_once __DIR__ . '/includes/Database.class.php';

$current_user_id = $_SESSION['user_id'] ?? 1;
$current_user_name = $_SESSION['user_name'] ?? 'Usuário';
$current_user_role = $_SESSION['user_role'] ?? 'funcionario';
$farm_id = $_SESSION['farm_id'] ?? 1;
$bull_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($bull_id <= 0) {
    header("Location: sistema-touros.php", true, 302);
    exit();
}

// Buscar dados da fazenda
try {
    $db = Database::getInstance();
    $farmData = $db->query("SELECT name, cnpj, address FROM farms WHERE id = ?", [$farm_id]);
    
    if (!empty($farmData)) {
        $farm_name = $farmData[0]['name'] ?? 'Lagoa Do Mato';
    } else {
        $farm_name = 'Lagoa Do Mato';
    }
} catch (Exception $e) {
    error_log("Erro ao buscar dados da fazenda: " . $e->getMessage());
    $farm_name = 'Lagoa Do Mato';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Touro - Sistema de Touros</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        :root {
            --forest-50: #f0fdf4;
            --forest-100: #dcfce7;
            --forest-200: #bbf7d0;
            --forest-300: #86efac;
            --forest-400: #4ade80;
            --forest-500: #22c55e;
            --forest-600: #16a34a;
            --forest-700: #15803d;
            --forest-800: #166534;
            --forest-900: #14532d;
        }
        
        .gradient-forest {
            background: linear-gradient(135deg, var(--forest-600) 0%, var(--forest-700) 100%);
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background-color: var(--forest-100);
            color: var(--forest-700);
        }
        
        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .tab-button {
            padding: 0.75rem 1.5rem;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .tab-button.active {
            border-bottom-color: var(--forest-600);
            color: var(--forest-600);
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: var(--forest-600);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--forest-700);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th,
        table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        table th {
            font-weight: 600;
            color: #374151;
            background: #f9fafb;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Header -->
    <header class="gradient-forest text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-4">
                    <button onclick="goBack()" class="flex items-center space-x-4 text-white hover:opacity-80 transition-opacity">
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center p-2">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold">Detalhes do Touro</h1>
                            <p class="text-forest-200 text-sm" id="bull-name-header">Carregando...</p>
                        </div>
                    </button>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="gerente-completo.php" class="text-white hover:text-forest-200 p-2 flex items-center space-x-2" title="Voltar para o painel do gerente">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="text-sm font-medium">Voltar</span>
                    </a>
                    <button onclick="editBull()" class="btn btn-primary flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Editar</span>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Informações Básicas -->
        <div class="card p-6 mb-6" id="bull-info-card">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <p class="text-gray-600 text-sm mb-1">Número/Código</p>
                    <p class="text-lg font-bold text-gray-900" id="bull-number">-</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm mb-1">Raça</p>
                    <p class="text-lg font-bold text-gray-900" id="bull-breed">-</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm mb-1">Status</p>
                    <span class="badge" id="bull-status-badge">-</span>
                </div>
                <div>
                    <p class="text-gray-600 text-sm mb-1">Idade</p>
                    <p class="text-lg font-bold text-gray-900" id="bull-age">-</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm mb-1">Peso Atual</p>
                    <p class="text-lg font-bold text-gray-900" id="bull-weight">-</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm mb-1">Eficiência Reprodutiva</p>
                    <p class="text-lg font-bold text-gray-900" id="bull-efficiency">-</p>
                </div>
            </div>
        </div>
        
        <!-- Abas -->
        <div class="card mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex space-x-4 px-6">
                    <button onclick="switchTab('info')" class="tab-button active" data-tab="info">
                        Informações
                    </button>
                    <button onclick="switchTab('coatings')" class="tab-button" data-tab="coatings">
                        Coberturas
                    </button>
                    <button onclick="switchTab('semen')" class="tab-button" data-tab="semen">
                        Sêmen
                    </button>
                    <button onclick="switchTab('health')" class="tab-button" data-tab="health">
                        Saúde
                    </button>
                    <button onclick="switchTab('weight')" class="tab-button" data-tab="weight">
                        Peso/Escore
                    </button>
                    <button onclick="switchTab('documents')" class="tab-button" data-tab="documents">
                        Documentos
                    </button>
                    <button onclick="switchTab('offspring')" class="tab-button" data-tab="offspring">
                        Descendentes
                    </button>
                    <button onclick="switchTab('reports')" class="tab-button" data-tab="reports">
                        Relatórios
                    </button>
                </nav>
            </div>
            
            <!-- Conteúdo das Abas -->
            <div class="p-6">
                <!-- Aba: Informações -->
                <div id="tab-info" class="tab-content active">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-bold mb-4">Dados Básicos</h3>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm text-gray-600">Nome</p>
                                    <p class="text-base font-medium" id="info-name">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">RFID</p>
                                    <p class="text-base font-medium" id="info-rfid">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Nº Brinco</p>
                                    <p class="text-base font-medium" id="info-earring">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Data de Nascimento</p>
                                    <p class="text-base font-medium" id="info-birth-date">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Origem</p>
                                    <p class="text-base font-medium" id="info-source">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Localização</p>
                                    <p class="text-base font-medium" id="info-location">-</p>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-bold mb-4">Genealogia</h3>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm text-gray-600">Pai (Sire)</p>
                                    <p class="text-base font-medium" id="info-sire">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Mãe (Dam)</p>
                                    <p class="text-base font-medium" id="info-dam">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Avo Paterno (Grandsire)</p>
                                    <p class="text-base font-medium" id="info-grandsire-father">-</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Avó Paterna (Granddam)</p>
                                    <p class="text-base font-medium" id="info-granddam-father">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h3 class="text-lg font-bold mb-4">Avaliação Genética</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">Mérito Genético</p>
                                <p class="text-xl font-bold" id="info-genetic-merit">-</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">Índice Leite</p>
                                <p class="text-xl font-bold" id="info-milk-index">-</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">Índice Gordura</p>
                                <p class="text-xl font-bold" id="info-fat-index">-</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">Índice Proteína</p>
                                <p class="text-xl font-bold" id="info-protein-index">-</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aba: Coberturas -->
                <div id="tab-coatings" class="tab-content">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">Coberturas Naturais</h3>
                        <button onclick="openCoatingModal()" class="btn btn-primary">Nova Cobertura</button>
                    </div>
                    <div id="coatings-list" class="overflow-x-auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Vaca</th>
                                    <th>Tipo</th>
                                    <th>Resultado</th>
                                    <th>Técnico</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="coatings-table-body">
                                <!-- Será preenchido via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Aba: Sêmen -->
                <div id="tab-semen" class="tab-content">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">Gestão de Sêmen</h3>
                        <button onclick="openSemenModal()" class="btn btn-primary">Novo Lote</button>
                    </div>
                    <div id="semen-list" class="overflow-x-auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>Lote</th>
                                    <th>Data Coleta</th>
                                    <th>Validade</th>
                                    <th>Disponível</th>
                                    <th>Usado</th>
                                    <th>Qualidade</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="semen-table-body">
                                <!-- Será preenchido via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Aba: Saúde -->
                <div id="tab-health" class="tab-content">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">Histórico Sanitário</h3>
                        <button onclick="openHealthModal()" class="btn btn-primary">Novo Registro</button>
                    </div>
                    <div id="health-list" class="overflow-x-auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Procedimento</th>
                                    <th>Veterinário</th>
                                    <th>Próxima Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="health-table-body">
                                <!-- Será preenchido via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Aba: Peso/Escore -->
                <div id="tab-weight" class="tab-content">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">Histórico de Peso e Escore</h3>
                        <button onclick="openWeightModal()" class="btn btn-primary">Novo Registro</button>
                    </div>
                    <div id="weight-chart-container" class="mb-6" style="height: 300px;">
                        <canvas id="weight-chart"></canvas>
                    </div>
                    <div id="weight-list" class="overflow-x-auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Peso (kg)</th>
                                    <th>Escore</th>
                                    <th>Observações</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="weight-table-body">
                                <!-- Será preenchido via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Aba: Documentos -->
                <div id="tab-documents" class="tab-content">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">Documentos e Anexos</h3>
                        <button onclick="openDocumentModal()" class="btn btn-primary">Novo Documento</button>
                    </div>
                    <div id="documents-list" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Será preenchido via JavaScript -->
                    </div>
                </div>
                
                <!-- Aba: Descendentes -->
                <div id="tab-offspring" class="tab-content">
                    <h3 class="text-lg font-bold mb-4">Descendentes</h3>
                    <div id="offspring-list" class="overflow-x-auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Nome</th>
                                    <th>Raça</th>
                                    <th>Data Nascimento</th>
                                    <th>Sexo</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="offspring-table-body">
                                <!-- Será preenchido via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Aba: Relatórios -->
                <div id="tab-reports" class="tab-content">
                    <h3 class="text-lg font-bold mb-4">Relatórios e Estatísticas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="card p-6">
                            <h4 class="font-bold mb-4">Desempenho Reprodutivo</h4>
                            <div style="height: 300px; position: relative;">
                                <canvas id="reproduction-chart"></canvas>
                            </div>
                        </div>
                        <div class="card p-6">
                            <h4 class="font-bold mb-4">Eficiência Reprodutiva</h4>
                            <div style="height: 300px; position: relative;">
                                <canvas id="efficiency-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="card p-6">
                        <h4 class="font-bold mb-4">Resumo Estatístico</h4>
                        <div id="statistics-summary" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <!-- Será preenchido via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- ============================================================
         MODAIS CRUD - TODAS AS FUNCIONALIDADES
         ============================================================ -->
    
    <!-- Modal: Cobertura -->
    <div id="modal-coating" class="modal">
        <div class="modal-content">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900" id="coating-modal-title">Nova Cobertura</h2>
                <button onclick="closeModal('modal-coating')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="coating-form" class="p-6">
                <input type="hidden" id="coating-id" name="id">
                <input type="hidden" id="coating-bull-id" name="bull_id" value="<?php echo $bull_id; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data da Cobertura *</label>
                        <input type="date" id="coating-date" name="coating_date" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
                        <input type="time" id="coating-time" name="coating_time" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vaca *</label>
                        <select id="coating-cow-id" name="cow_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                            <option value="">Selecione uma vaca</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select id="coating-type" name="coating_type" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                            <option value="natural">Natural</option>
                            <option value="inseminacao_artificial">Inseminação Artificial</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Resultado</label>
                        <select id="coating-result" name="result" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                            <option value="pendente">Pendente</option>
                            <option value="prenhez">Prenhez</option>
                            <option value="vazia">Vazia</option>
                            <option value="aborto">Aborto</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Verificação</label>
                        <input type="date" id="coating-check-date" name="pregnancy_check_date" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Técnico</label>
                        <input type="text" id="coating-technician" name="technician_name" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea id="coating-notes" name="notes" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500"></textarea>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeModal('modal-coating')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="coating-submit-btn">
                        <span id="coating-submit-text">Salvar</span>
                        <span id="coating-submit-loading" class="loading hidden ml-2"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal: Sêmen -->
    <div id="modal-semen" class="modal">
        <div class="modal-content">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900" id="semen-modal-title">Novo Lote de Sêmen</h2>
                <button onclick="closeModal('modal-semen')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="semen-form" class="p-6">
                <input type="hidden" id="semen-id" name="id">
                <input type="hidden" id="semen-bull-id" name="bull_id" value="<?php echo $bull_id; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Número do Lote *</label>
                        <input type="text" id="semen-batch" name="batch_number" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código da Palheta</label>
                        <input type="text" id="semen-straw-code" name="straw_code" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Produção *</label>
                        <input type="date" id="semen-production-date" name="production_date" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Coleta</label>
                        <input type="date" id="semen-collection-date" name="collection_date" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Validade *</label>
                        <input type="date" id="semen-expiry-date" name="expiry_date" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade Disponível *</label>
                        <input type="number" id="semen-straws-available" name="straws_available" min="0" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preço por Palheta *</label>
                        <input type="number" step="0.01" id="semen-price" name="price_per_straw" min="0" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Local de Armazenamento</label>
                        <input type="text" id="semen-storage" name="storage_location" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Qualidade</label>
                        <select id="semen-quality" name="quality_grade" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Motilidade (%)</label>
                        <input type="number" step="0.01" id="semen-motility" name="motility" min="0" max="100" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Volume (ml)</label>
                        <input type="number" step="0.01" id="semen-volume" name="volume" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Concentração (milhões/ml)</label>
                        <input type="number" step="0.01" id="semen-concentration" name="concentration" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea id="semen-notes" name="notes" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500"></textarea>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeModal('modal-semen')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="semen-submit-btn">
                        <span id="semen-submit-text">Salvar</span>
                        <span id="semen-submit-loading" class="loading hidden ml-2"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal: Saúde -->
    <div id="modal-health" class="modal">
        <div class="modal-content">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900" id="health-modal-title">Novo Registro Sanitário</h2>
                <button onclick="closeModal('modal-health')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="health-form" class="p-6">
                <input type="hidden" id="health-id" name="id">
                <input type="hidden" id="health-bull-id" name="bull_id" value="<?php echo $bull_id; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data *</label>
                        <input type="date" id="health-date" name="record_date" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                        <select id="health-type" name="record_type" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                            <option value="">Selecione o tipo</option>
                            <option value="vacina">Vacina</option>
                            <option value="exame_reprodutivo">Exame Reprodutivo</option>
                            <option value="exame_laboratorial">Exame Laboratorial</option>
                            <option value="tratamento">Tratamento</option>
                            <option value="medicamento">Medicamento</option>
                            <option value="consulta_veterinaria">Consulta Veterinária</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Procedimento/Exame *</label>
                        <input type="text" id="health-name" name="record_name" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Veterinário</label>
                        <input type="text" id="health-veterinarian" name="veterinarian_name" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CRMV</label>
                        <input type="text" id="health-license" name="veterinarian_license" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Medicamento</label>
                        <input type="text" id="health-medication" name="medication_name" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dosagem</label>
                        <input type="text" id="health-dosage" name="medication_dosage" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Período de Aplicação</label>
                        <input type="text" id="health-period" name="medication_period" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Próxima Data Prevista</label>
                        <input type="date" id="health-next-date" name="next_due_date" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Custo (R$)</label>
                        <input type="number" step="0.01" id="health-cost" name="cost" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Resultados/Laudo</label>
                    <textarea id="health-results" name="results" rows="4" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea id="health-notes" name="notes" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500"></textarea>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeModal('modal-health')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="health-submit-btn">
                        <span id="health-submit-text">Salvar</span>
                        <span id="health-submit-loading" class="loading hidden ml-2"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal: Peso/Escore -->
    <div id="modal-weight" class="modal">
        <div class="modal-content">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900" id="weight-modal-title">Novo Registro de Peso/Escore</h2>
                <button onclick="closeModal('modal-weight')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="weight-form" class="p-6">
                <input type="hidden" id="weight-id" name="id">
                <input type="hidden" id="weight-bull-id" name="bull_id" value="<?php echo $bull_id; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data *</label>
                        <input type="date" id="weight-date" name="record_date" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Peso (kg) *</label>
                        <input type="number" step="0.01" id="weight-value" name="weight" min="0" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Escore Corporal (1-5) *</label>
                        <input type="number" step="0.1" id="weight-score" name="body_score" min="1" max="5" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações do Escore</label>
                    <textarea id="weight-notes" name="body_score_notes" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500"></textarea>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeModal('modal-weight')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="weight-submit-btn">
                        <span id="weight-submit-text">Salvar</span>
                        <span id="weight-submit-loading" class="loading hidden ml-2"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal: Documento -->
    <div id="modal-document" class="modal">
        <div class="modal-content">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900" id="document-modal-title">Novo Documento</h2>
                <button onclick="closeModal('modal-document')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="document-form" class="p-6" enctype="multipart/form-data">
                <input type="hidden" id="document-id" name="id">
                <input type="hidden" id="document-bull-id" name="bull_id" value="<?php echo $bull_id; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento *</label>
                        <select id="document-type" name="document_type" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                            <option value="">Selecione o tipo</option>
                            <option value="certificado">Certificado</option>
                            <option value="laudo">Laudo</option>
                            <option value="foto">Foto</option>
                            <option value="pedigree">Pedigree</option>
                            <option value="teste_genetico">Teste Genético</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Documento *</label>
                        <input type="text" id="document-name" name="document_name" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Emissão</label>
                        <input type="date" id="document-issue-date" name="issue_date" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Validade</label>
                        <input type="date" id="document-expiry-date" name="expiry_date" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Arquivo *</label>
                        <input type="file" id="document-file" name="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        <p class="text-xs text-gray-500 mt-1">Formatos aceitos: PDF, JPG, PNG, DOC, DOCX</p>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea id="document-description" name="description" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500"></textarea>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeModal('modal-document')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="document-submit-btn">
                        <span id="document-submit-text">Salvar</span>
                        <span id="document-submit-loading" class="loading hidden ml-2"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Scripts -->
    <script>
        const BULL_ID = <?php echo $bull_id; ?>;
        const API_BASE = 'api/bulls.php';
        const ANIMALS_API = 'api/animals.php';
        
        // Debug
        console.log('Página carregada - BULL_ID:', BULL_ID);
        console.log('Página carregada - API_BASE:', API_BASE);
    </script>
    <script src="assets/js/sistema-touros-detalhes.js"></script>
</body>
</html>

