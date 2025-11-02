<?php
/**
 * Sistema de Touros - LacTech
 * Módulo completo de gerenciamento de touros
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

// Buscar dados da fazenda
try {
    $db = Database::getInstance();
    $farmData = $db->query("SELECT name, cnpj, address FROM farms WHERE id = ?", [$farm_id]);
    
    if (!empty($farmData)) {
        $farm_name = $farmData[0]['name'] ?? 'Lagoa Do Mato';
        $farm_cnpj = $farmData[0]['cnpj'] ?? '';
        $farm_address = $farmData[0]['address'] ?? '';
    } else {
        $farm_name = 'Lagoa Do Mato';
        $farm_cnpj = '';
        $farm_address = '';
    }
} catch (Exception $e) {
    error_log("Erro ao buscar dados da fazenda: " . $e->getMessage());
    $farm_name = 'Lagoa Do Mato';
    $farm_cnpj = '';
    $farm_address = '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Touros - LacTech</title>
    
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
            transform: translateY(-2px);
        }
        
        .bull-card {
            border-left: 4px solid var(--forest-500);
            cursor: pointer;
        }
        
        .bull-card.status-ativo {
            border-left-color: var(--forest-500);
        }
        
        .bull-card.status-em_reproducao {
            border-left-color: #f59e0b;
        }
        
        .bull-card.status-reserva {
            border-left-color: #6b7280;
        }
        
        .bull-card.status-descartado,
        .bull-card.status-falecido {
            border-left-color: #ef4444;
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
            max-width: 900px;
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
        
        .filter-tag {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            background: var(--forest-100);
            color: var(--forest-700);
            border-radius: 9999px;
            font-size: 0.875rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .filter-tag.active {
            background: var(--forest-600);
            color: white;
        }
        
        .toast {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .app-item {
            text-decoration: none;
            display: block;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Header -->
    <header class="gradient-forest text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-4">
                    <a href="gerente-completo.php" class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center p-2">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold">Sistema de Touros</h1>
                            <p class="text-forest-200 text-sm"><?php echo htmlspecialchars($farm_name); ?></p>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button onclick="openCreateModal()" class="btn btn-primary flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Novo Touro</span>
                    </button>
                    
                    <a href="logout.php" class="text-white hover:text-forest-200 p-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Estatísticas Gerais -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Total de Touros</p>
                        <p class="text-2xl font-bold text-gray-900" id="stat-total">-</p>
                    </div>
                    <div class="w-12 h-12 bg-forest-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-forest-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Em Reprodução</p>
                        <p class="text-2xl font-bold text-gray-900" id="stat-breeding">-</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Taxa de Eficiência</p>
                        <p class="text-2xl font-bold text-gray-900" id="stat-efficiency">-</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Sêmen Disponível</p>
                        <p class="text-2xl font-bold text-gray-900" id="stat-semen">-</p>
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
        <div class="card p-6 mb-8">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" 
                           id="search-input" 
                           placeholder="Buscar por nome, código, brinco ou RFID..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent">
                </div>
                
                <select id="filter-breed" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    <option value="">Todas as raças</option>
                </select>
                
                <select id="filter-status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                    <option value="">Todos os status</option>
                    <option value="ativo">Ativo</option>
                    <option value="em_reproducao">Em Reprodução</option>
                    <option value="reserva">Reserva</option>
                    <option value="descartado">Descartado</option>
                    <option value="falecido">Falecido</option>
                </select>
                
                <button onclick="loadBulls()" class="btn btn-primary">
                    Filtrar
                </button>
                
                <button onclick="resetFilters()" class="btn btn-secondary">
                    Limpar
                </button>
            </div>
        </div>
        
        <!-- Lista de Touros -->
        <div id="bulls-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Cards serão carregados via JavaScript -->
        </div>
        
        <!-- Loading -->
        <div id="loading" class="text-center py-12 hidden">
            <div class="loading mx-auto"></div>
            <p class="mt-4 text-gray-600">Carregando touros...</p>
        </div>
        
        <!-- Empty State -->
        <div id="empty-state" class="text-center py-12 hidden">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum touro encontrado</h3>
            <p class="mt-1 text-sm text-gray-500">Comece criando um novo touro.</p>
        </div>
    </main>
    
    <!-- Modal de Cadastro/Edição -->
    <div id="bull-modal" class="modal">
        <div class="modal-content">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900" id="modal-title">Novo Touro</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="bull-form" class="p-6">
                <input type="hidden" id="bull-id" name="id">
                
                <!-- Seção: Dados Básicos -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">Dados Básicos</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número/Código *</label>
                            <input type="text" id="bull-number" name="bull_number" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                            <input type="text" id="bull-name" name="name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Raça *</label>
                            <input type="text" id="bull-breed" name="breed" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento *</label>
                            <input type="date" id="bull-birth-date" name="birth_date" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">RFID</label>
                            <input type="text" id="bull-rfid" name="rfid_code" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nº Brinco</label>
                            <input type="text" id="bull-earring" name="earring_number" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="bull-status" name="status" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
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
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
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
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ativo em Reprodução</label>
                            <select id="bull-breeding-active" name="is_breeding_active" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                                <option value="1">Sim</option>
                                <option value="0">Não</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Peso Inicial (kg)</label>
                            <input type="number" step="0.01" id="bull-weight" name="weight" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Escore Corporal Inicial (1-5)</label>
                            <input type="number" step="0.1" min="1" max="5" id="bull-body-score" name="body_score" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                    </div>
                </div>
                
                <!-- Seção: Genealogia -->
                <div class="mb-6 border-t pt-6">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">Genealogia</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pai (Sire)</label>
                            <input type="text" id="bull-sire" name="sire" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mãe (Dam)</label>
                            <input type="text" id="bull-dam" name="dam" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Avô Paterno (Grandsire Father)</label>
                            <input type="text" id="bull-grandsire-father" name="grandsire_father" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Avó Paterna (Granddam Father)</label>
                            <input type="text" id="bull-granddam-father" name="granddam_father" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Avô Materno (Grandsire Mother)</label>
                            <input type="text" id="bull-grandsire-mother" name="grandsire_mother" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Avó Materna (Granddam Mother)</label>
                            <input type="text" id="bull-granddam-mother" name="granddam_mother" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                    </div>
                </div>
                
                <!-- Seção: Avaliação Genética -->
                <div class="mb-6 border-t pt-6">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">Avaliação Genética</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código Genético</label>
                            <input type="text" id="bull-genetic-code" name="genetic_code" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mérito Genético</label>
                            <input type="number" step="0.01" id="bull-genetic-merit" name="genetic_merit" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Índice de Produção de Leite</label>
                            <input type="number" step="0.01" id="bull-milk-index" name="milk_production_index" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Índice de Produção de Gordura</label>
                            <input type="number" step="0.01" id="bull-fat-index" name="fat_production_index" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Índice de Produção de Proteína</label>
                            <input type="number" step="0.01" id="bull-protein-index" name="protein_production_index" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Índice de Fertilidade</label>
                            <input type="number" step="0.01" id="bull-fertility-index" name="fertility_index" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Índice de Saúde</label>
                            <input type="number" step="0.01" id="bull-health-index" name="health_index" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Avaliação Genética (Texto)</label>
                        <textarea id="bull-genetic-evaluation" name="genetic_evaluation" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500" 
                                  placeholder="Avaliação genética detalhada..."></textarea>
                    </div>
                </div>
                
                <!-- Seção: Observações -->
                <div class="mb-6 border-t pt-6">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">Observações</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observações sobre Comportamento</label>
                            <textarea id="bull-behavior-notes" name="behavior_notes" rows="2" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500" 
                                      placeholder="Observações sobre o comportamento do touro..."></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observações sobre Aptidão</label>
                            <textarea id="bull-aptitude-notes" name="aptitude_notes" rows="2" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500" 
                                      placeholder="Observações sobre aptidão do touro..."></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observações Gerais</label>
                            <textarea id="bull-notes" name="notes" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-forest-500" 
                                      placeholder="Observações gerais..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6 border-t pt-6">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        <span id="submit-text">Salvar</span>
                        <span id="submit-loading" class="loading hidden ml-2"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/sistema-touros.js"></script>
</body>
</html>

