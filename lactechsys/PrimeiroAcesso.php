<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    redirect(DASHBOARD_URL);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $farmName = sanitizeInput($_POST['farm_name'] ?? '');
    $farmLocation = sanitizeInput($_POST['farm_location'] ?? '');
    
    if (empty($farmName) || empty($farmLocation)) {
        $error = 'Por favor, preencha todos os campos';
    } else {
        $success = 'Fazenda configurada com sucesso!';
    }
}

$notification = getNotification();
if ($notification) {
    if ($notification['type'] === 'success') {
        $success = $notification['message'];
    } else {
        $error = $notification['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração da Fazenda - Sistema de Controle Leiteiro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/js/config_mysql.js"></script>
    <!-- <script src="assets/js/loading-screen.js"></script> DESABILITADO - usando apenas modal de carregamento -->
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- <link href="assets/css/loading-screen.css" rel="stylesheet"> DESABILITADO - usando apenas modal de carregamento -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'forest': {
                            50: '#f0f9f0', 100: '#dcf2dc', 200: '#bce5bc', 300: '#8dd18d',
                            400: '#5bb85b', 500: '#369e36', 600: '#2a7f2a', 700: '#236523',
                            800: '#1f511f', 900: '#1a431a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        .gradient-forest { 
            background: linear-gradient(135deg, #1a431a 0%, #236523 50%, #2a7f2a 100%); 
        }
        
        .setup-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .step-indicator {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }
        
        .step-active {
            background: linear-gradient(135deg, #369e36 0%, #5bb85b 100%);
            color: white;
        }
        
        .step-completed {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #369e36;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }

        .success-message {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Header -->
    <header class="gradient-forest shadow-xl">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <div class="header-logo-container">
                        <img src="assets/img/lactech-logo.png" alt="LacTech Logo" class="header-logo">
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-white tracking-tight">CONFIGURAÇÃO INICIAL</h1>
                        <p class="text-xs text-forest-200">Sistema de Controle Leiteiro</p>
                    </div>
                </div>
                
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center space-x-4">
                <div id="step1" class="step-indicator step-active rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm">
                    1
                </div>
                <div id="progress1" class="w-16 h-1 bg-gray-300 rounded"></div>
                <div id="step2" class="step-indicator rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm">
                    2
                </div>
                <div id="progress2" class="w-16 h-1 bg-gray-300 rounded"></div>
                <div id="step3" class="step-indicator rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm">
                3
            </div>
            <div id="progress3" class="w-16 h-1 bg-gray-300 rounded"></div>
            <div id="step4" class="step-indicator rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm">
                4
            </div>
        </div>
        <div class="flex justify-center mt-2">
            <div class="text-center">
                <p id="stepTitle" class="text-sm font-semibold text-forest-600">Passo 1 de 4</p>
                    <p id="stepSubtitle" class="text-xs text-gray-500">Informações da Fazenda</p>
                </div>
            </div>
        </div>

        <!-- Error/Success Messages -->
        <div id="errorMessage" class="error-message"></div>
        <div id="successMessage" class="success-message"></div>

        <!-- Step 1: Farm Information -->
        <div id="farmInfoStep" class="setup-card rounded-2xl p-8 shadow-xl">
            <div class="text-center mb-8">
                <div class="w-20 h-20 gradient-forest rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Bem-vindo ao Sistema!</h2>
                <p class="text-gray-600 text-lg">Vamos configurar sua fazenda em poucos passos</p>
            </div>

            <form id="farmForm" class="space-y-6">
                <!-- Informações Básicas da Fazenda -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nome da Fazenda *</label>
                        <input type="text" required name="farm_name" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="Ex: Fazenda São João">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Proprietário *</label>
                        <input type="text" required name="owner_name" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="Nome do proprietário">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">CNPJ/CPF</label>
                        <input type="text" name="document" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="00.000.000/0000-00">
                    </div>
                </div>

                <!-- Localização -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Cidade *</label>
                        <input type="text" required name="city" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="Nome da cidade">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Estado *</label>
                        <select required name="state" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none">
                            <option value="">Selecione o estado</option>
                            <option value="AC">Acre</option>
                            <option value="AL">Alagoas</option>
                            <option value="AP">Amapá</option>
                            <option value="AM">Amazonas</option>
                            <option value="BA">Bahia</option>
                            <option value="CE">Ceará</option>
                            <option value="DF">Distrito Federal</option>
                            <option value="ES">Espírito Santo</option>
                            <option value="GO">Goiás</option>
                            <option value="MA">Maranhão</option>
                            <option value="MT">Mato Grosso</option>
                            <option value="MS">Mato Grosso do Sul</option>
                            <option value="MG">Minas Gerais</option>
                            <option value="PA">Pará</option>
                            <option value="PB">Paraíba</option>
                            <option value="PR">Paraná</option>
                            <option value="PE">Pernambuco</option>
                            <option value="PI">Piauí</option>
                            <option value="RJ">Rio de Janeiro</option>
                            <option value="RN">Rio Grande do Norte</option>
                            <option value="RS">Rio Grande do Sul</option>
                            <option value="RO">Rondônia</option>
                            <option value="RR">Roraima</option>
                            <option value="SC">Santa Catarina</option>
                            <option value="SP">São Paulo</option>
                            <option value="SE">Sergipe</option>
                            <option value="TO">Tocantins</option>
                        </select>
                    </div>
                </div>

                <!-- Contato -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Telefone *</label>
                        <input type="tel" required name="phone" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="(00) 00000-0000">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                        <input type="email" required name="email" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="contato@fazenda.com">
                    </div>
                </div>

                <!-- Endereço -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Endereço Completo</label>
                    <textarea name="address" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="Rua, número, bairro..."></textarea>
                </div>

                <!-- Botões -->
                <div class="flex space-x-4 pt-6">
                    <button type="button" onclick="goBack()" class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-all">
                        Voltar
                    </button>
                    <button type="submit" id="farmSubmitBtn" class="flex-1 px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                        Continuar
                    </button>
                </div>
            </form>
        </div>

        <!-- Step 2: Admin Registration -->
        <div id="adminStep" class="setup-card rounded-2xl p-8 shadow-xl hidden">
            <div class="text-center mb-8">
                <div class="w-20 h-20 gradient-forest rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Criar Conta de Administrador</h2>
                <p class="text-gray-600 text-lg">Configure sua conta para gerenciar a fazenda</p>
            </div>

            <form id="adminForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nome Completo *</label>
                        <input type="text" required name="admin_name" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="Seu nome completo">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                        <input type="email" required name="admin_email" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="seu@email.com">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">WhatsApp *</label>
                        <input type="tel" required name="admin_whatsapp" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="(00) 00000-0000">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Usuário *</label>
                        <select required name="admin_role" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none">
                            <option value="">Selecione o tipo</option>
                            <option value="proprietario">Proprietário da Fazenda</option>
                            <option value="gerente">Gerente</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Senha *</label>
                        <div class="relative">
                            <input type="password" required name="admin_password" id="adminPassword" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none pr-12" placeholder="Crie uma senha segura" minlength="6">
                            <button type="button" onclick="toggleAdminPassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg id="adminEyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Mínimo 6 caracteres</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Confirmar Senha *</label>
                        <div class="relative">
                            <input type="password" required name="admin_confirm_password" id="adminConfirmPassword" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none pr-12" placeholder="Confirme sua senha" minlength="6">
                            <button type="button" onclick="toggleAdminConfirmPassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg id="adminConfirmEyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-start">
                    <input type="checkbox" required class="w-4 h-4 text-forest-600 border-gray-300 rounded focus:ring-forest-500 mt-0.5">
                    <span class="ml-2 text-sm text-gray-600">
                        Concordo com os <a href="#" class="text-forest-600 hover:text-forest-700 font-medium">Termos de Uso</a> e 
                        <a href="#" class="text-forest-600 hover:text-forest-700 font-medium">Política de Privacidade</a>
                    </span>
                </div>

                <!-- Botões -->
                <div class="flex space-x-4 pt-6">
                    <button type="button" onclick="goToStep1()" class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-all">
                        Voltar
                    </button>
                    <button type="submit" id="adminSubmitBtn" class="flex-1 px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                        Continuar
                    </button>
                </div>
            </form>
        </div>

        <!-- Step 3: Confirmation -->
        <div id="confirmationStep" class="setup-card rounded-2xl p-8 shadow-xl hidden">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Confirmar Configuração</h2>
                <p class="text-gray-600 text-lg">Revise os dados antes de finalizar</p>
            </div>

            <!-- Resumo da Configuração -->
            <div class="space-y-6">
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-blue-800 mb-4">Dados da Fazenda</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-blue-700 font-medium">Fazenda:</p>
                            <p id="confirmFarmName" class="text-blue-600"></p>
                        </div>
                        <div>
                            <p class="text-blue-700 font-medium">Proprietário:</p>
                            <p id="confirmOwnerName" class="text-blue-600"></p>
                        </div>
                        <div>
                            <p class="text-blue-700 font-medium">Localização:</p>
                            <p id="confirmLocation" class="text-blue-600"></p>
                        </div>
                        <div>
                            <p class="text-blue-700 font-medium">CPF/CNPJ:</p>
                            <p id="confirmDocument" class="text-blue-600"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-green-800 mb-4">Dados do Administrador</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-green-700 font-medium">Nome:</p>
                            <p id="confirmAdminName" class="text-green-600"></p>
                        </div>
                        <div>
                            <p class="text-green-700 font-medium">Email:</p>
                            <p id="confirmAdminEmail" class="text-green-600"></p>
                        </div>
                        <div>
                            <p class="text-green-700 font-medium">Tipo:</p>
                            <p id="confirmAdminRole" class="text-green-600"></p>
                        </div>
                    </div>
                </div>

                <!-- Botões -->
                <div class="flex space-x-4 pt-6">
                    <button type="button" onclick="goToStep2()" class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-all">
                        Voltar
                    </button>
                    <button type="button" onclick="finalizeSetup()" id="finalizeBtn" class="flex-1 px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                        Finalizar Configuração
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 4: Success -->
        <div id="successStep" class="setup-card rounded-2xl p-8 shadow-xl hidden">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-green-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Configuração Concluída!</h2>
                <p class="text-gray-600 text-lg">Sua fazenda foi configurada com sucesso</p>
            </div>

            <!-- Resumo da Configuração -->
            <div class="space-y-6">
                <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-green-800 mb-4">Resumo da Configuração</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-green-700 font-medium">Fazenda:</p>
                            <p id="summaryFarmName" class="text-green-600"></p>
                        </div>
                        <div>
                            <p class="text-green-700 font-medium">Proprietário:</p>
                            <p id="summaryOwnerName" class="text-green-600"></p>
                        </div>
                        <div>
                            <p class="text-green-700 font-medium">Localização:</p>
                            <p id="summaryLocation" class="text-green-600"></p>
                        </div>

                        <div>
                            <p class="text-green-700 font-medium">Administrador:</p>
                            <p id="summaryAdminName" class="text-green-600"></p>
                        </div>
                        <div>
                            <p class="text-green-700 font-medium">Tipo:</p>
                            <p id="summaryAdminRole" class="text-green-600"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-blue-800 mb-3">Próximos Passos</h3>
                    <div class="bg-green-100 border border-green-300 rounded-lg p-3 mb-4">
                        <p class="text-sm text-green-700 font-medium flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Você será redirecionado automaticamente em <span id="redirectCountdown">3</span> segundos...
                        </p>
                    </div>
                    <ul class="space-y-2 text-sm text-blue-700">
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Você já está logado no sistema!
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Cadastre funcionários e veterinários
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Comece a registrar a produção leiteira
                        </li>
                    </ul>
                </div>

                <div class="text-center pt-6">
                    <button onclick="goToLogin()" class="px-8 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                        Acessar o Sistema Agora
                    </button>
                </div>
            </div>
        </div>

        <!-- Features Preview (only show on step 1) -->
        <div id="featuresPreview" class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center p-6 bg-white rounded-xl shadow-sm">
                <div class="w-12 h-12 gradient-forest rounded-lg flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Controle de Produção</h3>
                <p class="text-sm text-gray-600">Registre e monitore a produção leiteira diária</p>
            </div>
            
            <div class="text-center p-6 bg-white rounded-xl shadow-sm">
                <div class="w-12 h-12 gradient-forest rounded-lg flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Gestão de Equipe</h3>
                <p class="text-sm text-gray-600">Gerencie funcionários, veterinários e permissões</p>
            </div>
            
            <div class="text-center p-6 bg-white rounded-xl shadow-sm">
                <div class="w-12 h-12 gradient-forest rounded-lg flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Relatórios</h3>
                <p class="text-sm text-gray-600">Gere relatórios detalhados e análises</p>
            </div>
        </div>
    </main>

    <script>
        // Wait for Supabase to be available
        function waitForSupabase() {
            return new Promise((resolve, reject) => {
                let attempts = 0;
                const maxAttempts = 100; // 10 seconds max
                
                const checkSupabase = () => {
                    attempts++;
                    console.log(`🔄 Tentativa ${attempts}: Verificando Supabase...`);
                    console.log('window.supabase:', window.supabase);
                    console.log('window.supabase.createClient:', window.supabase?.createClient);
                    console.log('SUPABASE_URL:', typeof SUPABASE_URL !== 'undefined' ? 'Definido' : 'Não definido');
                    console.log('SUPABASE_ANON_KEY:', typeof SUPABASE_ANON_KEY !== 'undefined' ? 'Definido' : 'Não definido');
                    
                    // Verificar se o Supabase está disponível
                    if (window.supabase && window.supabase.createClient) {
                        console.log('✅ Supabase disponível, criando cliente...');
                        // Criar cliente Supabase usando as configurações do config.js
                        const supabaseUrl = typeof SUPABASE_URL !== 'undefined' ? SUPABASE_URL : 'https://tmaamwuyucaspqcrhuck.supabase.co';
                        const supabaseKey = typeof SUPABASE_ANON_KEY !== 'undefined' ? SUPABASE_ANON_KEY : 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRtYWFtd3V5dWNhc3BxY3JodWNrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTY2OTY1MzMsImV4cCI6MjA3MjI3MjUzM30.AdDXp0xrX_xKutFHQrJ47LhFdLTtanTSku7fcK1eTB0';
                        
                        const client = window.supabase.createClient(supabaseUrl, supabaseKey);
                        console.log('✅ Cliente Supabase criado com sucesso');
                        resolve(client);
                    } else if (attempts >= maxAttempts) {
                        console.error('❌ Timeout: Supabase não foi inicializado');
                        reject(new Error('Timeout: Supabase não foi inicializado em 10 segundos'));
                    } else {
                        setTimeout(checkSupabase, 100);
                    }
                };
                checkSupabase();
            });
        }

        // Initialize variables
        let farmData = {};
        let adminData = {};
        let isProcessing = false; // Flag para evitar múltiplas submissões
        let supabaseClient = null;

        // Initialize Supabase when page loads
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                console.log('🔄 Inicializando Supabase...');
                
                // Aguardar um pouco para garantir que o config.js seja carregado
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                // Verificar se as configurações estão disponíveis
                if (typeof SUPABASE_URL === 'undefined' || typeof SUPABASE_ANON_KEY === 'undefined') {
                    console.warn('⚠️ Configurações do Supabase não encontradas, usando fallback');
                } else {
                    console.log('✅ Configurações do Supabase carregadas');
                }
                
                supabaseClient = await waitForSupabase();
                console.log('✅ Supabase client initialized successfully');
                
                // Test the connection
                const { data, error } = await supabaseClient.from('users').select('count').limit(1);
                if (error) {
                    console.warn('⚠️ Supabase connection test failed:', error);
                } else {
                    console.log('✅ Supabase connection test successful');
                }
            } catch (error) {
                console.error('❌ Error initializing Supabase:', error);
                showError('Erro ao inicializar conexão com o banco de dados. Por favor, recarregue a página.');
            }
        });

        // Utility functions
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            document.getElementById('successMessage').style.display = 'none';
            
            // Scroll to top to show error
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Hide after 5 seconds
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }

        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            document.getElementById('errorMessage').style.display = 'none';
            
            // Hide after 3 seconds
            setTimeout(() => {
                successDiv.style.display = 'none';
            }, 3000);
        }

        function setLoading(buttonId, isLoading) {
            const button = document.getElementById(buttonId);
            if (isLoading) {
                button.innerHTML = '<div class="spinner"></div>Processando...';
                button.disabled = true;
                button.classList.add('loading');
            } else {
                button.innerHTML = buttonId === 'farmSubmitBtn' ? 'Continuar' : 'Finalizar Configuração';
                button.disabled = false;
                button.classList.remove('loading');
            }
        }

        function validateCNPJ(cnpj) {
            cnpj = cnpj.replace(/[^\d]+/g, '');
            if (cnpj.length !== 14) return false;
            
            // Validação básica de CNPJ (algoritmo completo seria mais extenso)
            if (/^(\d)\1+$/.test(cnpj)) return false;
            
            return true;
        }

        function validateCPF(cpf) {
            cpf = cpf.replace(/[^\d]+/g, '');
            if (cpf.length !== 11) return false;
            
            // Validação básica de CPF
            if (/^(\d)\1+$/.test(cpf)) return false;
            
            return true;
        }

        function formatDocument(value) {
            const numbers = value.replace(/\D/g, '');
            if (numbers.length <= 11) {
                // CPF format
                return numbers.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            } else {
                // CNPJ format
                return numbers.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
            }
        }

        // Step 1: Farm Form
        document.getElementById('farmForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            setLoading('farmSubmitBtn', true);
            
            try {
                const formData = new FormData(this);
                
                // Validate document if provided
                const document = formData.get('document');
                if (document) {
                    const cleanDoc = document.replace(/\D/g, '');
                    if (cleanDoc.length === 11 && !validateCPF(document)) {
                        throw new Error('CPF inválido');
                    } else if (cleanDoc.length === 14 && !validateCNPJ(document)) {
                        throw new Error('CNPJ inválido');
                    }
                }
                
                farmData = {
                    name: formData.get('farm_name'),
                    owner_name: formData.get('owner_name'),
                    cnpj: document || null,
                    city: formData.get('city'),
                    state: formData.get('state'),
                    phone: formData.get('phone'),
                    email: formData.get('email'),
                    address: formData.get('address') || null
                };
                
                // Check if farm with same name or CNPJ exists using secure RPC
                if (!supabaseClient) {
                    throw new Error('Conexão com o banco de dados não inicializada');
                }
                const { data: exists, error } = await supabaseClient
                    .rpc('check_farm_exists', { p_name: farmData.name, p_cnpj: farmData.cnpj });
                
                if (error) throw error;
                
                if (exists) {
                    throw new Error('Já existe uma fazenda com este nome ou CNPJ');
                }
                showSuccess('Informações da fazenda validadas com sucesso!');
                goToStep2();
                
            } catch (error) {
                console.error('Erro na validação da fazenda:', error);
                showError(error.message || 'Erro ao validar informações da fazenda');
            } finally {
                setLoading('farmSubmitBtn', false);
            }
        });

        // Step 2: Admin Form (apenas validação)
        document.getElementById('adminForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            setLoading('adminSubmitBtn', true);
            
            try {
                const formData = new FormData(this);
                const password = formData.get('admin_password');
                const confirmPassword = formData.get('admin_confirm_password');
                
                if (password !== confirmPassword) {
                    throw new Error('As senhas não coincidem!');
                }
                
                if (password.length < 6) {
                    throw new Error('A senha deve ter pelo menos 6 caracteres');
                }
                
                adminData = {
                    name: formData.get('admin_name'),
                    email: formData.get('admin_email'),
                    whatsapp: formData.get('admin_whatsapp'),
                    role: formData.get('admin_role'),
                    password: password
                };
                
                // Check if user with same email exists using secure RPC
                if (!supabaseClient) {
                    throw new Error('Conexão com o banco de dados não inicializada');
                }
                const { data: exists, error: userCheckError } = await supabaseClient
                    .rpc('check_user_exists', { p_email: adminData.email });
                
                if (userCheckError) {
                    throw userCheckError;
                }
                
                if (exists) {
                    throw new Error('Já existe um usuário com este email');
                }
                
                showSuccess('Dados do administrador validados com sucesso!');
                goToStep3();
                
            } catch (error) {
                console.error('Erro na criação da conta:', error);
                showError(error.message || 'Erro ao criar conta de administrador');
            } finally {
                setLoading('adminSubmitBtn', false);
            }
        });

        function goToStep1() {
            document.getElementById('farmInfoStep').classList.remove('hidden');
            document.getElementById('adminStep').classList.add('hidden');
            document.getElementById('successStep').classList.add('hidden');
            document.getElementById('featuresPreview').classList.remove('hidden');
            
            updateStepIndicator(1);
        }

        function goToStep2() {
            document.getElementById('farmInfoStep').classList.add('hidden');
            document.getElementById('adminStep').classList.remove('hidden');
            document.getElementById('successStep').classList.add('hidden');
            document.getElementById('featuresPreview').classList.add('hidden');
            
            updateStepIndicator(2);
        }

        function goToStep3() {
            document.getElementById('farmInfoStep').classList.add('hidden');
            document.getElementById('adminStep').classList.add('hidden');
            document.getElementById('confirmationStep').classList.remove('hidden');
            document.getElementById('successStep').classList.add('hidden');
            document.getElementById('featuresPreview').classList.add('hidden');
            
            updateStepIndicator(3);
            updateConfirmationData();
        }

        function goToStep4() {
            console.log('🎯 Entrando no Step 4...');
            document.getElementById('farmInfoStep').classList.add('hidden');
            document.getElementById('adminStep').classList.add('hidden');
            document.getElementById('confirmationStep').classList.add('hidden');
            document.getElementById('successStep').classList.remove('hidden');
            document.getElementById('featuresPreview').classList.add('hidden');
            
            updateStepIndicator(4);
            updateSummary();
            
            console.log('⏰ Iniciando contador regressivo...');
            // Iniciar contador regressivo e redirecionamento automático
            startRedirectCountdown();
        }

        function updateStepIndicator(currentStep) {
            // Reset all steps
            document.getElementById('step1').className = 'step-indicator rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm';
            document.getElementById('step2').className = 'step-indicator rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm';
            document.getElementById('step3').className = 'step-indicator rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm';
            document.getElementById('step4').className = 'step-indicator rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm';
            
            // Update progress bars
            document.getElementById('progress1').className = 'w-16 h-1 bg-gray-300 rounded';
            document.getElementById('progress2').className = 'w-16 h-1 bg-gray-300 rounded';
            document.getElementById('progress3').className = 'w-16 h-1 bg-gray-300 rounded';
            
            // Set current and completed steps
            if (currentStep >= 1) {
                document.getElementById('step1').className = currentStep === 1 ? 'step-indicator step-active rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm' : 'step-indicator step-completed rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm';
            }
            if (currentStep >= 2) {
                document.getElementById('step2').className = currentStep === 2 ? 'step-indicator step-active rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm' : 'step-indicator step-completed rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm';
                document.getElementById('progress1').className = 'w-16 h-1 bg-forest-500 rounded';
            }
            if (currentStep >= 3) {
                document.getElementById('step3').className = currentStep === 3 ? 'step-indicator step-active rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm' : 'step-indicator step-completed rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm';
                document.getElementById('progress2').className = 'w-16 h-1 bg-forest-500 rounded';
            }
            if (currentStep >= 4) {
                document.getElementById('step4').className = 'step-indicator step-completed rounded-full w-10 h-10 flex items-center justify-center font-bold text-sm';
                document.getElementById('progress3').className = 'w-16 h-1 bg-forest-500 rounded';
            }
            
            // Update step title and subtitle
            const titles = {
                1: { title: 'Passo 1 de 4', subtitle: 'Informações da Fazenda' },
                2: { title: 'Passo 2 de 4', subtitle: 'Criar Conta de Administrador' },
                3: { title: 'Passo 3 de 4', subtitle: 'Confirmar Configuração' },
                4: { title: 'Configuração Concluída', subtitle: 'Resumo da Configuração' }
            };
            
            document.getElementById('stepTitle').textContent = titles[currentStep].title;
            document.getElementById('stepSubtitle').textContent = titles[currentStep].subtitle;
        }

        async function finalizeSetup() {
            const finalizeBtn = document.getElementById('finalizeBtn');
            const originalText = finalizeBtn.textContent;
            
            // Evitar múltiplas submissões
            if (isProcessing) {
                console.log('Já está processando, ignorando nova tentativa');
                return;
            }
            
            try {
                isProcessing = true;
                // Desabilitar botão e mostrar loading
                finalizeBtn.disabled = true;
                finalizeBtn.textContent = 'Criando conta...';
                
                // 1. Verificar se usuário já existe
                if (!supabaseClient) {
                    throw new Error('Conexão com o banco de dados não inicializada');
                }
                
                const { data: existingUser, error: checkError } = await supabaseClient
                    .from('users')
                    .select('id')
                    .eq('email', adminData.email)
                    .single();
                
                let authData;
                
                if (existingUser) {
                    // Usuário já existe, fazer login
                    const { data: loginData, error: loginError } = await supabaseClient.auth.signInWithPassword({
                        email: adminData.email,
                        password: adminData.password
                    });
                    
                    if (loginError) {
                        throw new Error(`Usuário já existe. Erro no login: ${loginError.message}`);
                    }
                    
                    authData = loginData;
                } else {
                    // Criar nova conta
                    const { data: signUpData, error: authError } = await supabaseClient.auth.signUp({
                        email: adminData.email,
                        password: adminData.password
                    });
                    
                    if (authError) {
                        if (authError.message.includes('User already registered')) {
                            // Se o usuário já existe no Auth mas não no banco, fazer login
                            const { data: loginData, error: loginError } = await supabaseClient.auth.signInWithPassword({
                                email: adminData.email,
                                password: adminData.password
                            });
                            
                            if (loginError) {
                                throw new Error(`Usuário já registrado. Erro no login: ${loginError.message}`);
                            }
                            
                            authData = loginData;
                        } else {
                            throw new Error(`Erro na criação da conta: ${authError.message}`);
                        }
                    } else {
                        authData = signUpData;
                    }
                }
                
                finalizeBtn.textContent = 'Verificando fazenda...';
                
                // 2. Verificar novamente se a fazenda já existe
                console.log('Verificando fazenda com dados:', { name: farmData.name, cnpj: farmData.cnpj });
                const { data: farmExists, error: farmCheckError } = await supabaseClient
                    .rpc('check_farm_exists', { p_name: farmData.name, p_cnpj: farmData.cnpj });
                
                console.log('Resultado da verificação:', { farmExists, farmCheckError });
                
                if (farmCheckError) {
                    throw new Error(`Erro na verificação da fazenda: ${farmCheckError.message}`);
                }
                
                if (farmExists) {
                    throw new Error('Já existe uma fazenda com este nome ou CNPJ. Por favor, use dados diferentes.');
                }
                
                finalizeBtn.textContent = 'Criando fazenda...';
                
                // 3. Criar fazenda no banco de dados
                console.log('Criando fazenda com dados:', farmData);
                const { data: farmResult, error: farmError } = await supabaseClient.rpc('create_initial_farm', {
                    p_name: farmData.name,
                    p_owner_name: farmData.owner_name,
                    p_cnpj: farmData.cnpj || '',
                    p_city: farmData.city || '',
                    p_state: farmData.state || '',
                    p_phone: farmData.phone || '',
                    p_email: farmData.email || '',
                    p_address: farmData.address || ''
                });
                
                console.log('Resultado da criação da fazenda:', { farmResult, farmError });
                
                if (farmError) {
                    throw new Error(`Erro na criação da fazenda: ${farmError.message}`);
                }
                
                finalizeBtn.textContent = 'Configurando usuário...';
                
                // 4. Criar usuário no banco de dados
                const { data: userResult, error: userError } = await supabaseClient.rpc('create_initial_user', {
                    p_user_id: authData.user.id,
                    p_farm_id: farmResult,
                    p_name: adminData.name,
                    p_email: adminData.email,
                    p_role: adminData.role,
                    p_whatsapp: adminData.whatsapp || ''
                });
                
                if (userError) {
                    throw new Error(`Erro na criação do usuário: ${userError.message}`);
                }
                
                finalizeBtn.textContent = 'Finalizando...';
                
                // 5. Marcar configuração como completa
                const { error: setupError } = await supabaseClient.rpc('complete_farm_setup', {
                    p_farm_id: farmResult
                });
                
                if (setupError) {
                    throw new Error(`Erro ao finalizar configuração: ${setupError.message}`);
                }
                
                // 6. Fazer login automático
                const { error: loginError } = await supabaseClient.auth.signInWithPassword({
                    email: adminData.email,
                    password: adminData.password
                });
                
                if (loginError) {
                    console.warn('Erro no login automático:', loginError.message);
                }
                
                // 7. Ir para o passo de sucesso
                isProcessing = false;
                goToStep4();
                
            } catch (error) {
                console.error('Erro na finalização:', error);
                alert(`Erro na criação da conta: ${error.message}`);
                
                // Reabilitar botão e resetar flag
                isProcessing = false;
                finalizeBtn.disabled = false;
                finalizeBtn.textContent = originalText;
            }
        }

        function updateConfirmationData() {
            document.getElementById('confirmFarmName').textContent = farmData.name;
            document.getElementById('confirmOwnerName').textContent = farmData.owner_name;
            document.getElementById('confirmLocation').textContent = `${farmData.city}, ${farmData.state}`;
            document.getElementById('confirmDocument').textContent = farmData.cnpj || 'Não informado';
            document.getElementById('confirmAdminName').textContent = adminData.name;
            document.getElementById('confirmAdminEmail').textContent = adminData.email;
            document.getElementById('confirmAdminRole').textContent = adminData.role === 'proprietario' ? 'Proprietário' : 'Gerente';
        }

        function updateSummary() {
            document.getElementById('summaryFarmName').textContent = farmData.name;
            document.getElementById('summaryOwnerName').textContent = farmData.owner_name;
            document.getElementById('summaryLocation').textContent = `${farmData.city}, ${farmData.state}`;
            // Campo de animais removido do formulário
            document.getElementById('summaryAdminName').textContent = adminData.name;
            document.getElementById('summaryAdminRole').textContent = adminData.role === 'proprietario' ? 'Proprietário' : 'Gerente';
        }

        function goBack() {
            window.location.href = '/';
        }

        function goToLogin() {
            redirectToDashboard();
        }
        
        function redirectToDashboard() {
            console.log('🔄 Iniciando redirecionamento...');
            console.log('📊 Dados do admin:', adminData);
            
            // Redirect based on user role
            if (adminData.role === 'proprietario') {
                console.log('👤 Redirecionando para proprietário');
                window.location.href = 'proprietario.php';
            } else if (adminData.role === 'gerente') {
                console.log('👤 Redirecionando para gerente');
                window.location.href = 'gerente.php';
            } else {
                console.log('⚠️ Role não definido, redirecionando para login');
                // Fallback to login page
                window.location.href = 'login.php';
            }
        }

        function toggleAdminPassword() {
            const password = document.getElementById('adminPassword');
            const eyeIcon = document.getElementById('adminEyeIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                password.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        function toggleAdminConfirmPassword() {
            const password = document.getElementById('adminConfirmPassword');
            const eyeIcon = document.getElementById('adminConfirmEyeIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                password.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }
        
        // Input masks
        function setupInputMasks() {
            // Phone mask
            const phoneInputs = document.querySelectorAll('input[name="phone"], input[name="admin_whatsapp"]');
            phoneInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length <= 11) {
                        value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                        if (value.length < 14) {
                            value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                        }
                    }
                    e.target.value = value;
                });
            });
            
            // Document mask
            const documentInput = document.querySelector('input[name="document"]');
            if (documentInput) {
                documentInput.addEventListener('input', function(e) {
                    e.target.value = formatDocument(e.target.value);
                });
            }
        }

        // Initialize masks when page loads
        document.addEventListener('DOMContentLoaded', setupInputMasks);


        // Função para iniciar contador regressivo e redirecionamento automático
        function startRedirectCountdown() {
            console.log('⏰ Iniciando contador regressivo...');
            let countdown = 3;
            const countdownElement = document.getElementById('redirectCountdown');
            
            if (!countdownElement) {
                console.warn('❌ Elemento de contador não encontrado');
                return;
            }
            
            console.log('✅ Elemento de contador encontrado:', countdownElement);
            
            const interval = setInterval(() => {
                countdown--;
                console.log('⏱️ Contador:', countdown);
                countdownElement.textContent = countdown;
                
                if (countdown <= 0) {
                    console.log('🚀 Contador finalizado, iniciando redirecionamento...');
                    clearInterval(interval);
                    redirectToDashboard();
                }
            }, 1000);
        }

        // Check if user is already logged in - DISABLED FOR FIRST ACCESS PAGE
        // document.addEventListener('DOMContentLoaded', async () => {
        //     const { data: { user } } = await supabase.auth.getUser();
        //     if (user) {
        //         // User is already logged in, redirect to dashboard
        //         window.location.href = '/dashboard';
        //     }
        // });
    </script>

</body>
</html>
