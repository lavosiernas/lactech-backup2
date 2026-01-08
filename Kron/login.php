<?php
/**
 * KRON - Página de Login
 */

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/GoogleOAuth.php';

// Se já estiver logado, redirecionar
if (isset($_SESSION['kron_logged_in']) && $_SESSION['kron_logged_in'] === true) {
    redirect('dashboard/');
}

$error = '';
$success = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Preencha todos os campos';
    } else {
        $pdo = getKronDatabase();
        
        if ($pdo) {
            $stmt = $pdo->prepare("
                SELECT id, email, name, password, is_active, avatar_url, google_id
                FROM kron_users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && $user['is_active'] == 1) {
                // Verificar senha
                if ($user['password'] && password_verify($password, $user['password'])) {
                    // Login bem-sucedido
                    $_SESSION['kron_logged_in'] = true;
                    $_SESSION['kron_user_id'] = $user['id'];
                    $_SESSION['kron_user_email'] = $user['email'];
                    $_SESSION['kron_user_name'] = $user['name'];
                    $_SESSION['kron_user_avatar'] = $user['avatar_url'];
                    
                    // Atualizar último login
                    $stmt = $pdo->prepare("UPDATE kron_users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    redirect('dashboard/');
                } else {
                    $error = 'Email ou senha incorretos';
                }
            } else {
                $error = 'Email ou senha incorretos';
            }
        } else {
            $error = 'Erro ao conectar ao banco de dados';
        }
    }
}

// Verificar erro do Google OAuth
if (isset($_SESSION['google_error'])) {
    $error = $_SESSION['google_error'];
    unset($_SESSION['google_error']);
}

$googleAuthUrl = '';
try {
    $googleOAuth = new GoogleOAuth();
    $_SESSION['google_oauth_action'] = 'login';
    $googleAuthUrl = $googleOAuth->getAuthUrl('login');
} catch (Exception $e) {
    // Google OAuth não configurado
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KRON</title>
    <link rel="icon" type="image/png" href="asset/kron.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; color: #f5f5f7; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <img src="asset/kron.png" alt="KRON" class="w-16 h-16 mx-auto mb-4">
            <h1 class="text-3xl font-bold mb-2">KRON</h1>
            <p class="text-gray-400">Servidor Administrativo Central</p>
        </div>
        
        <div class="bg-gray-900 rounded-2xl p-8 border border-gray-800">
            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Email</label>
                    <input type="email" name="email" required
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500"
                        placeholder="seu@email.com">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Senha</label>
                    <input type="password" name="password" required
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500"
                        placeholder="••••••••">
                </div>
                
                <button type="submit"
                    class="w-full bg-white text-black font-semibold py-3 rounded-lg hover:bg-gray-100 transition">
                    Entrar
                </button>
            </form>
            
            <?php if ($googleAuthUrl): ?>
                <div class="mt-4">
                    <a href="<?= htmlspecialchars($googleAuthUrl) ?>"
                        class="w-full bg-gray-800 border border-gray-700 text-white font-medium py-3 rounded-lg hover:bg-gray-750 transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Entrar com Google
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <p class="text-center text-gray-500 text-sm mt-6">
            Sistema de governança e orquestração
        </p>
    </div>
</body>
</html>

