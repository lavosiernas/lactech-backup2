<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();

// Se não estiver logado, redirecionar para login
if (!$auth->isLoggedIn()) {
    redirect(LOGIN_URL);
}

$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Bloqueado - LacTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/x-icon">
    <style>
        .text-evercheck-green {
            color: #2ECC71;
        }
        .bg-evercheck-blue {
            background-color: #3498DB;
        }
        .text-evercheck-blue {
            color: #3498DB;
        }
        .text-dark-gray {
            color: #333333;
        }
        .text-light-gray {
            color: #666666;
        }
        .text-header-gray {
            color: #888888;
        }
        .bg-whatsapp-green {
            background-color: #2ECC71;
        }
        .hover\:bg-whatsapp-green-dark:hover {
            background-color: #27AE60;
        }
    </style>
</head>
<body class="bg-white min-h-screen flex flex-col">
    <!-- Main Content - Centralizado -->
    <main class="flex-grow flex flex-col items-center justify-center text-center p-4">
        <h1 class="text-xl font-medium text-dark-gray mb-8">Acesso Bloqueado</h1>

        <!-- Espaço para a GIF -->
        <div class="mb-8">
            <img src="403.png" alt="Acesso Bloqueado GIF" class="mx-auto" style="max-width: 400px; height: 150px; object-fit: contain;">
        </div>

        <p class="text-sm text-light-gray mt-8 max-w-xs">
            Seu acesso ao sistema LacTech foi temporariamente bloqueado. Por favor, entre em contato com o Gerente da sua Fazenda se isso não deveria ter acontecido.
        </p>

        <!-- Botões de Ação -->
        <div class="mt-8 space-y-3">
            <a href="login.php" 
               class="block bg-evercheck-blue text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-600 transition duration-200">
                Voltar ao Login
            </a>
            
            <a href="https://wa.me/5584999999999?text=Olá, meu acesso ao LacTech foi bloqueado. Usuário: <?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
               target="_blank"
               class="block bg-whatsapp-green text-white px-6 py-3 rounded-lg font-medium hover:bg-whatsapp-green-dark transition duration-200">
                Contatar Suporte
            </a>
        </div>

        <!-- Informações do Usuário -->
        <?php if ($user): ?>
        <div class="mt-8 p-4 bg-gray-50 rounded-lg max-w-sm">
            <h3 class="text-sm font-medium text-dark-gray mb-2">Informações da Conta</h3>
            <p class="text-xs text-light-gray">
                <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?><br>
                <strong>Nome:</strong> <?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?><br>
                <strong>Perfil:</strong> <?php echo htmlspecialchars($user['role'] ?? 'N/A'); ?>
            </p>
        </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="text-center py-4 text-header-gray text-xs">
        <p>&copy; 2024 LacTech. Todos os direitos reservados.</p>
    </footer>

    <script>
        // Auto-redirect após 30 segundos
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 30000);

        // Logout automático
        fetch('includes/logout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        }).catch(function(error) {
            console.log('Erro ao fazer logout:', error);
        });
    </script>
</body>
</html>
