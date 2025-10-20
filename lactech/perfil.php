<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verificar se tem o papel correto
if ($_SESSION['user_role'] !== 'gerente' && $_SESSION['user_role'] !== 'manager') {
    // Papel incorreto, redirecionar para o dashboard correto
    switch ($_SESSION['user_role']) {
        case 'funcionario':
            header('Location: funcionario.php');
            break;
        case 'veterinario':
            header('Location: veterinario.php');
            break;
        case 'proprietario':
            header('Location: proprietario.php');
            break;
        default:
            header('Location: login.php');
            break;
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Gerente - LacTech</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/img/lactech-logo.png">
    <style>
        .gradient-forest {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        .gradient-forest:hover {
            background: linear-gradient(135deg, #047857 0%, #065f46 100%);
        }
    </style>
</head>
<body class="h-full bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo e Nome -->
                <div class="flex items-center space-x-4">
                    <a href="gerente.php" class="flex items-center space-x-3">
                        <img src="assets/img/lactech-logo.png" alt="LacTech" class="w-8 h-8">
                        <span class="text-xl font-bold text-gray-900">LacTech</span>
                    </a>
                </div>
                
                <!-- Botão Voltar -->
                <div class="flex items-center space-x-4">
                    <button onclick="goBack()" class="flex items-center space-x-2 text-gray-600 hover:text-gray-900 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>Voltar ao Dashboard</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Cabeçalho do Perfil -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center space-x-6">
                <!-- Foto do Usuário -->
                <div class="relative">
                    <img id="profilePhoto" src="" alt="Foto de Perfil" class="w-24 h-24 object-cover rounded-2xl shadow-lg hidden">
                    <div id="profileIcon" class="w-24 h-24 bg-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </div>
                </div>
                
                <!-- Informações Básicas -->
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-900" id="profileName">Carregando...</h1>
                    <p class="text-xl text-gray-600 mb-2" id="profileRole">Carregando...</p>
                    <p class="text-gray-500" id="profileFarmName">Carregando...</p>
                </div>
                
                <!-- Botão de Ação -->
                <div class="flex space-x-3">
                    <button onclick="openManagerPhotoModal()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Alterar Foto
                    </button>
                </div>
            </div>
        </div>

        <!-- Informações Pessoais -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Informações Pessoais</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Nome Completo</label>
                    <p class="text-gray-900 font-medium text-lg" id="profileFullName">Carregando...</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Email</label>
                    <p class="text-gray-900 text-lg" id="profileEmail">Carregando...</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">WhatsApp</label>
                    <p class="text-gray-900 text-lg" id="profileWhatsApp">Carregando...</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Cargo</label>
                    <p class="text-gray-900 text-lg">Gerente</p>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Ações Rápidas</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button onclick="window.location.href='alterar-senha.php'" class="flex items-center space-x-4 p-4 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-colors">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-gray-900">Alterar Senha</p>
                        <p class="text-sm text-gray-500">Manter conta segura</p>
                    </div>
                </button>
                
                <button onclick="signOut()" class="flex items-center space-x-4 p-4 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl transition-colors">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-gray-900">Sair do Sistema</p>
                        <p class="text-sm text-gray-500">Encerrar sessão</p>
                    </div>
                </button>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Estatísticas da Conta</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900" id="accountStatus">Ativo</p>
                    <p class="text-sm text-gray-500">Status da Conta</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900" id="lastLogin">Hoje</p>
                    <p class="text-sm text-gray-500">Último Acesso</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">Gerente</p>
                    <p class="text-sm text-gray-500">Nível de Acesso</p>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script>
        // Cache de dados do usuário para navegação mais rápida
        let userDataCache = null;

        // Função para carregar dados do usuário (com cache)
        function loadUserData() {
            try {
                console.log('📊 Carregando dados do usuário...');
                
                // Verificar cache primeiro
                if (userDataCache) {
                    console.log('⚡ Usando cache de dados do usuário');
                    updateProfileUI(userDataCache);
                    return;
                }
                
                // Buscar dados do usuário
                const userData = localStorage.getItem('user_data') || 
                                sessionStorage.getItem('user_data') || 
                                localStorage.getItem('userData') || 
                                sessionStorage.getItem('userData');
                
                if (userData) {
                    const user = JSON.parse(userData);
                    console.log('👤 Dados encontrados:', user);
                    
                    // Salvar no cache
                    userDataCache = user;
                    
                    // Atualizar interface
                    updateProfileUI(user);
                    
                    console.log('✅ Dados carregados com sucesso!');
                } else {
                    console.log('⚠️ Nenhum dado de usuário encontrado');
                }
                
            } catch (error) {
                console.error('❌ Erro ao carregar dados:', error);
            }
        }

        // Função para atualizar a interface com os dados do usuário
        function updateProfileUI(user) {
            // Atualizar nome
            const nameElement = document.getElementById('profileName');
            if (nameElement) {
                nameElement.textContent = user.name || user.nome || 'Usuário';
            }
            
            // Atualizar cargo
            const roleElement = document.getElementById('profileRole');
            if (roleElement) {
                roleElement.textContent = user.role || user.cargo || 'Gerente';
            }
            
            // Atualizar fazenda
            const farmElement = document.getElementById('profileFarmName');
            if (farmElement) {
                farmElement.textContent = user.farm_name || user.fazenda || 'Fazenda';
            }
            
            // Atualizar nome completo
            const fullNameElement = document.getElementById('profileFullName');
            if (fullNameElement) {
                fullNameElement.textContent = user.name || user.nome || 'Usuário';
            }
            
            // Atualizar email
            const emailElement = document.getElementById('profileEmail');
            if (emailElement) {
                emailElement.textContent = user.email || 'Não informado';
            }
            
            // Atualizar WhatsApp
            const whatsappElement = document.getElementById('profileWhatsApp');
            if (whatsappElement) {
                whatsappElement.textContent = user.whatsapp || user.phone || 'Não informado';
            }
        }

        // Função para carregar foto do usuário
        function loadUserPhoto() {
            try {
                // Buscar dados do usuário
                const userData = localStorage.getItem('user_data') || 
                                sessionStorage.getItem('user_data') || 
                                localStorage.getItem('userData') || 
                                sessionStorage.getItem('userData');
                
                if (userData) {
                    const user = JSON.parse(userData);
                    
                    // Verificar se tem foto
                    if (user.profile_photo_url && user.profile_photo_url !== 'null' && user.profile_photo_url !== '') {
                        const photoElement = document.getElementById('profilePhoto');
                        const iconElement = document.getElementById('profileIcon');
                        
                        if (photoElement && iconElement) {
                            photoElement.src = user.profile_photo_url;
                            photoElement.classList.remove('hidden');
                            iconElement.classList.add('hidden');
                        }
                    }
                }
            } catch (error) {
                console.error('❌ Erro ao carregar foto:', error);
            }
        }

        // Função para voltar ao dashboard (otimizada)
        function goBack() {
            // Verificar se há histórico de navegação
            if (document.referrer && document.referrer.includes('gerente.php')) {
                // Se veio do gerente.php, usar history.back() para voltar mais rápido
                window.history.back();
            } else {
                // Se não, redirecionar normalmente
                window.location.href = 'gerente.php';
            }
        }

        // Função para sair do sistema
        function signOut() {
            if (confirm('Tem certeza que deseja sair do sistema?')) {
                // Limpar dados de sessão
                localStorage.clear();
                sessionStorage.clear();
                
                // Redirecionar para login
                window.location.href = 'login.php';
            }
        }

        // Preload da página gerente.php para navegação mais rápida
        function preloadGerentePage() {
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = 'gerente.php';
            document.head.appendChild(link);
        }

        // Carregar dados quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Página de perfil carregada!');
            
            // Mostrar indicador de carregamento sutil
            document.body.style.opacity = '0.8';
            document.body.style.transition = 'opacity 0.3s ease';
            
            // Carregar dados
            loadUserData();
            loadUserPhoto();
            preloadGerentePage(); // Preload para navegação mais rápida
            
            // Esconder indicador após carregamento
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 200);
        });
    </script>
</body>
</html>
