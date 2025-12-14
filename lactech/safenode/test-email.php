<?php
/**
 * SafeNode - Teste de Email
 * Página para testar o envio de emails
 */

session_start();

require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

// Página de teste - não requer login

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/EmailSender.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $testEmail = $_POST['email'] ?? '';
    
    if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $message = 'Por favor, informe um email válido.';
        $messageType = 'error';
    } else {
        try {
            $emailSender = new EmailSender();
            
            // Criar um token de teste
            $testToken = bin2hex(random_bytes(32));
            $testUrl = 'https://safenode.cloud/reset-password.php?token=' . $testToken;
            
            // Enviar email de teste
            $result = $emailSender->sendPasswordResetEmail($testEmail, $testToken, 'Usuário de Teste');
            
            if ($result) {
                $message = 'Email de teste enviado com sucesso! Verifique sua caixa de entrada.';
                $messageType = 'success';
            } else {
                $message = 'Falha ao enviar email. Verifique os logs do servidor.';
                $messageType = 'error';
            }
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Verificar configuração do PHP mail
$mailConfig = [
    'mail() function exists' => function_exists('mail'),
    'sendmail_path' => ini_get('sendmail_path'),
    'SMTP' => ini_get('SMTP'),
    'smtp_port' => ini_get('smtp_port'),
    'sendmail_from' => ini_get('sendmail_from'),
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Email | SafeNode</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Teste de Envio de Email</h1>
        
        <?php if ($message): ?>
        <div class="mb-4 p-4 rounded <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Configuração do PHP Mail</h2>
            <table class="w-full">
                <tr class="border-b">
                    <td class="py-2 font-medium">Função mail() existe:</td>
                    <td class="py-2"><?php echo $mailConfig['mail() function exists'] ? '✅ Sim' : '❌ Não'; ?></td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-medium">sendmail_path:</td>
                    <td class="py-2"><?php echo $mailConfig['sendmail_path'] ?: 'Não configurado'; ?></td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-medium">SMTP:</td>
                    <td class="py-2"><?php echo $mailConfig['SMTP'] ?: 'Não configurado'; ?></td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-medium">smtp_port:</td>
                    <td class="py-2"><?php echo $mailConfig['smtp_port'] ?: 'Não configurado'; ?></td>
                </tr>
                <tr>
                    <td class="py-2 font-medium">sendmail_from:</td>
                    <td class="py-2"><?php echo $mailConfig['sendmail_from'] ?: 'Não configurado'; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Enviar Email de Teste</h2>
            <form method="POST">
                <div class="mb-4">
                    <label class="block mb-2 font-medium">Email para teste:</label>
                    <input type="email" name="email" required 
                           class="w-full px-4 py-2 border rounded-lg"
                           placeholder="seu@email.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <button type="submit" name="test_email" 
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    Enviar Email de Teste
                </button>
            </form>
        </div>
        
        <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-sm text-yellow-800">
                <strong>Nota:</strong> Se o email não chegar, o servidor pode não ter a função mail() configurada. 
                Nesse caso, será necessário configurar SMTP ou usar um serviço de email externo.
            </p>
        </div>
    </div>
</body>
</html>

