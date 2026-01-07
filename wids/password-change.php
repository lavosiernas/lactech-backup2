<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha | WidestEye</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>
<body>
    <!-- Header will be loaded via JavaScript -->
    <div id="header-container"></div>

    <main class="container mx-auto px-4 py-8">
        <section class="max-w-lg mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 my-8">
            <h1 class="text-2xl font-bold text-center mb-6 dark:text-white">Alterar Senha</h1>
            
            <form id="password-change-form" class="space-y-4">
                <div class="form-group">
                    <label for="current-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Senha Atual</label>
                    <input type="password" id="current-password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                </div>
                
                <div class="form-group">
                    <label for="new-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nova Senha</label>
                    <input type="password" id="new-password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                    <div class="password-requirements text-xs text-gray-500 dark:text-gray-400 mt-1 hidden">
                        <p>A senha deve ter no mínimo 6 caracteres</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirmar Nova Senha</label>
                    <input type="password" id="confirm-password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                </div>
                
                <div class="flex justify-between items-center mt-6">
                    <a href="account.html" class="text-primary hover:underline">Voltar para a conta</a>
                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors">Alterar Senha</button>
                </div>
            </form>
        </section>
    </main>

    <!-- Notification container -->
    <div id="notification-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>
    
    <!-- Loading overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-lg">
            <div class="loader"></div>
            <p class="text-center mt-2 dark:text-white">Processando...</p>
        </div>
    </div>

    <!-- Footer will be loaded via JavaScript -->
    <div id="footer-container"></div>

    <!-- Scripts -->
    <script src="js/main.js"></script>
    <script src="js/password-change.js"></script>
</body>
</html> 