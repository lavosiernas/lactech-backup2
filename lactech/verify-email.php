<?php
/**
 * Página de verificação de e-mail
 * Verifica o token enviado por e-mail e marca o e-mail como verificado
 */

session_start();

require_once __DIR__ . '/includes/Database.class.php';
require_once __DIR__ . '/includes/SecurityService.class.php';

$token = $_GET['token'] ?? null;
$message = '';
$success = false;

if ($token) {
    $security = SecurityService::getInstance();
    $result = $security->verifyEmail($token);
    
    if ($result['success']) {
        $message = 'E-mail verificado com sucesso!';
        $success = true;
    } else {
        $message = $result['error'] ?? 'Erro ao verificar e-mail';
    }
} else {
    $message = 'Token não fornecido';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de E-mail - LacTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full">
        <div class="text-center mb-6">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center <?php echo $success ? 'bg-green-100' : 'bg-red-100'; ?>">
                <?php if ($success): ?>
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                <?php else: ?>
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                <?php endif; ?>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
                <?php echo $success ? 'E-mail Verificado!' : 'Erro na Verificação'; ?>
            </h1>
            <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($message); ?></p>
            
            <?php if ($success): ?>
                <p class="text-sm text-gray-500 mb-6">
                    Seu endereço de e-mail foi verificado com sucesso. Agora você pode realizar ações sensíveis em sua conta.
                </p>
            <?php endif; ?>
            
            <div class="space-y-3">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'gerente-completo.php' : 'login.php'; ?>" 
                   class="block w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-center">
                    <?php echo isset($_SESSION['user_id']) ? 'Ir para o Dashboard' : 'Fazer Login'; ?>
                </a>
                
                <?php if (!$success): ?>
                    <a href="gerente-completo.php" 
                       class="block w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-center">
                        Voltar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

















