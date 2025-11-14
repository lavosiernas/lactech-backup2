<?php
/**
 * Página de Perfil do Usuário - AgroNews360
 */

session_start();

// Verificar se está logado
if (!isset($_SESSION['agronews_logged_in']) || !$_SESSION['agronews_logged_in']) {
    header('Location: login.php');
    exit;
}

$isLoggedIn = true;
$userName = $_SESSION['agronews_user_name'] ?? 'Usuário';
$userEmail = $_SESSION['agronews_user_email'] ?? '';
$userRole = $_SESSION['agronews_user_role'] ?? 'visitante';
$userId = $_SESSION['agronews_user_id'] ?? null;

// Verificar se logou com Lactech
$loggedWithLactech = isset($_SESSION['agronews_lactech_user_id']);
$farmName = $_SESSION['agronews_farm_name'] ?? null;
$farmId = $_SESSION['agronews_farm_id'] ?? null;
$lactechUserId = $_SESSION['agronews_lactech_user_id'] ?? null;

// Buscar dados adicionais se logou com Lactech
$userRoleDisplay = 'Usuário'; // Todos os usuários são tratados igualmente no AgroNews
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - AgroNews360</title>
    <link rel="icon" href="assets/img/agro360.png" type="image/png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'agro-green': '#22c55e',
                        'agro-green-dark': '#16a34a',
                    },
                    fontFamily: {
                        'sans': ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #e2e8f0 100%);
            min-height: 100vh;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }
        
        .lactech-badge {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            animation: pulse-glow 2s ease-in-out infinite;
        }
        
        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(59, 130, 246, 0);
            }
        }
        
        .info-card {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-3">
                        <img src="assets/img/agro360.png" alt="AgroNews360" class="h-10 w-10 object-contain">
                        <span class="text-xl font-display font-bold text-gray-900">AgroNews360</span>
                    </a>
                </div>
                
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="text-gray-700 hover:text-agro-green font-body font-medium text-sm transition-colors">Início</a>
                    <a href="index.php#noticias" class="text-gray-700 hover:text-agro-green font-body font-medium text-sm transition-colors">Notícias</a>
                    <a href="perfil.php" class="text-agro-green font-body font-semibold text-sm">Meu Perfil</a>
                </nav>
                
                <div class="flex items-center space-x-3">
                    <span class="text-sm font-body text-gray-700">Olá, <?php echo htmlspecialchars($userName); ?></span>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Profile Header -->
        <div class="profile-header rounded-2xl p-8 mb-8 text-white">
            <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
                <!-- Avatar -->
                <div class="relative">
                    <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center text-4xl font-bold backdrop-blur-sm">
                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    </div>
                    <?php if ($loggedWithLactech): ?>
                        <div class="absolute -bottom-2 -right-2 lactech-badge w-10 h-10 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- User Info -->
                <div class="flex-1 text-center md:text-left">
                    <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($userName); ?></h1>
                    <p class="text-white/80 mb-4"><?php echo htmlspecialchars($userEmail); ?></p>
                    
                    <?php if ($loggedWithLactech): ?>
                        <div class="inline-flex items-center space-x-2 bg-white/20 backdrop-blur-sm px-4 py-2 rounded-full">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <span class="font-semibold">Logado com conta Lactech</span>
                        </div>
                    <?php else: ?>
                        <div class="inline-flex items-center space-x-2 bg-white/20 backdrop-blur-sm px-4 py-2 rounded-full">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span class="font-semibold">Conta AgroNews360</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <?php if ($loggedWithLactech): ?>
                <!-- Farm Info Card -->
                <div class="info-card rounded-2xl p-6">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Fazenda</h3>
                            <p class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($farmName ?? 'Não informado'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Role Card -->
                <div class="info-card rounded-2xl p-6">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Cargo</h3>
                            <p class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($userRoleDisplay); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Account Type Card -->
            <div class="info-card rounded-2xl p-6">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Tipo de Conta</h3>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo $loggedWithLactech ? 'Lactech' : 'AgroNews360'; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Email Card -->
            <div class="info-card rounded-2xl p-6">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Email</h3>
                        <p class="text-lg font-semibold text-gray-900 break-all"><?php echo htmlspecialchars($userEmail); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($loggedWithLactech): ?>
            <!-- Lactech Integration Info -->
            <div class="info-card rounded-2xl p-6 mb-8 bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-200">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-blue-500 rounded-xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Conta Integrada com Lactech</h3>
                        <p class="text-gray-700 mb-4">
                            Sua conta está vinculada ao sistema Lactech. Você pode acessar todas as funcionalidades do portal AgroNews360 
                            e também gerenciar sua fazenda através do sistema Lactech.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <a href="../gerente-completo.php" class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Acessar Lactech</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="info-card rounded-2xl p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Ações Rápidas</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="index.php" class="flex items-center space-x-3 p-4 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-agro-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="font-semibold text-gray-900">Voltar ao Início</span>
                </a>
                <a href="index.php#noticias" class="flex items-center space-x-3 p-4 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-agro-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                    </svg>
                    <span class="font-semibold text-gray-900">Ver Notícias</span>
                </a>
                <button onclick="openLogoutModal()" class="flex items-center space-x-3 p-4 bg-red-50 hover:bg-red-100 rounded-xl transition-colors w-full text-left">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="font-semibold text-red-700">Sair da Conta</span>
                </button>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center">
                <p class="text-gray-500 text-sm font-body">&copy; <?php echo date('Y'); ?> AgroNews360. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    
    <!-- Modal de Logout -->
    <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center" style="display: none;">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl transform transition-all scale-100">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Confirmar Saída</h3>
                <p class="text-gray-600 mb-6">Tem certeza que deseja sair da sua conta?</p>
                <div class="flex space-x-4">
                    <button onclick="closeLogoutModal()" class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <a href="api/auth.php?action=logout" class="flex-1 px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition-colors text-center">
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function openLogoutModal() {
            const modal = document.getElementById('logoutModal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeLogoutModal() {
            const modal = document.getElementById('logoutModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
        
        // Fechar modal ao clicar fora
        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLogoutModal();
            }
        });
        
        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLogoutModal();
            }
        });
    </script>
</body>
</html>

