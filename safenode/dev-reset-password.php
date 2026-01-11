<?php
/**
 * SafeNode - Reset de Senha (DESENVOLVIMENTO APENAS)
 */
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/SecurityHelpers.php';

// SEGURANÇA: Verificar se está em ambiente local
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = in_array($host, ['localhost', '127.0.0.1', '::1']) || strpos($host, '192.168.') === 0;

if (!$isLocal) {
    die("Acesso negado: Esta ferramenta está disponível apenas em ambiente de desenvolvimento local.");
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $email = trim($_POST['email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    
    if (empty($email) || empty($newPassword)) {
        $error = "Preencha todos os campos.";
    } else {
        $pdo = getSafeNodeDatabase();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT id FROM safenode_users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $update = $pdo->prepare("UPDATE safenode_users SET password_hash = ? WHERE id = ?");
                $update->execute([$hash, $user['id']]);
                $message = "Senha alterada com sucesso para o usuário: " . htmlspecialchars($email);
            } else {
                $error = "Usuário não encontrado.";
            }
        } else {
            $error = "Erro na conexão com o banco de dados.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dev Reset - SafeNode</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full bg-slate-800 p-8 rounded-2xl shadow-2xl border border-slate-700">
        <h1 class="text-2xl font-bold mb-2">ESC Local</h1>
        <p class="text-slate-400 text-sm mb-6">Ferramenta de desenvolvimento para reset rápido de senha.</p>
        
        <?php if($message): ?>
            <div class="bg-emerald-500/20 border border-emerald-500 text-emerald-400 p-3 rounded-lg mb-4 text-sm">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-400 p-3 rounded-lg mb-4 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Email do Usuário</label>
                <input type="email" name="email" required placeholder="dev@safenode.local"
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-white outline-none transition-all">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Nova Senha</label>
                <input type="password" name="new_password" required placeholder="Digite a nova senha"
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-white outline-none transition-all">
            </div>
            <button type="submit" name="reset_password" 
                class="w-full bg-white text-black font-bold py-2 rounded-lg hover:bg-slate-200 transition-colors">
                Alterar Senha
            </button>
            <a href="login.php" class="block text-center text-xs text-slate-500 hover:text-white mt-4">
                Voltar para o Login
            </a>
        </form>
    </div>
</body>
</html>
