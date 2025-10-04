<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';

$auth = new Auth();
$security = Security::getInstance();

// Verificar se está logado
$auth->requireLogin();

$user = $auth->getCurrentUser();
$error = '';
$success = '';

// Processar configuração 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'enable') {
        // Gerar secret 2FA
        $secret = $security->generate2FASecret();
        $qrCodeUrl = $security->generate2FAQRCode($user['email'], $secret);
        
        // Salvar secret temporariamente na sessão
        $_SESSION['temp_2fa_secret'] = $secret;
        $_SESSION['temp_2fa_qr'] = $qrCodeUrl;
        
        $success = 'Secret gerado! Escaneie o QR Code com seu aplicativo autenticador.';
    } elseif ($action === 'verify') {
        $code = sanitizeInput($_POST['code'] ?? '');
        
        if (empty($code)) {
            $error = 'Por favor, digite o código de verificação';
        } elseif (strlen($code) !== 6 || !is_numeric($code)) {
            $error = 'Código deve ter 6 dígitos numéricos';
        } elseif (!isset($_SESSION['temp_2fa_secret'])) {
            $error = 'Sessão 2FA expirada. Tente novamente.';
        } else {
            // Verificar código
            if ($security->verify2FACode($_SESSION['temp_2fa_secret'], $code)) {
                // Salvar secret no banco
                $db = Database::getInstance();
                $result = $db->update('users', [
                    '2fa_secret' => $_SESSION['temp_2fa_secret'],
                    '2fa_enabled' => true,
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['id' => $user['id']]);
                
                if ($result) {
                    unset($_SESSION['temp_2fa_secret']);
                    unset($_SESSION['temp_2fa_qr']);
                    $success = '2FA habilitado com sucesso!';
                } else {
                    $error = 'Erro ao salvar configuração 2FA';
                }
            } else {
                $error = 'Código de verificação inválido';
            }
        }
    } elseif ($action === 'disable') {
        $confirmCode = sanitizeInput($_POST['confirm_code'] ?? '');
        
        if (empty($confirmCode)) {
            $error = 'Por favor, digite o código de confirmação';
        } elseif (strlen($confirmCode) !== 6 || !is_numeric($confirmCode)) {
            $error = 'Código deve ter 6 dígitos numéricos';
        } elseif (empty($user['2fa_secret'])) {
            $error = '2FA não está habilitado';
        } else {
            // Verificar código
            if ($security->verify2FACode($user['2fa_secret'], $confirmCode)) {
                // Desabilitar 2FA
                $db = Database::getInstance();
                $result = $db->update('users', [
                    '2fa_secret' => null,
                    '2fa_enabled' => false,
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['id' => $user['id']]);
                
                if ($result) {
                    $success = '2FA desabilitado com sucesso!';
                    // Recarregar dados do usuário
                    $user = $auth->getCurrentUser();
                } else {
                    $error = 'Erro ao desabilitar 2FA';
                }
            } else {
                $error = 'Código de confirmação inválido';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar 2FA - LacTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/x-icon">
    <style>
        .error-message {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .success-message {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .qr-code {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            background: white;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="LacTech" class="h-16 mx-auto mb-4">
            <h1 class="text-3xl font-bold text-gray-900">Configurar 2FA</h1>
            <p class="text-gray-600 mt-2">Autenticação de dois fatores para maior segurança</p>
        </div>

        <div class="max-w-2xl mx-auto">
            <!-- Status atual -->
            <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Status Atual</h2>
                
                <?php if (!empty($user['2fa_secret'])): ?>
                    <div class="flex items-center space-x-3 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <span class="text-green-800 font-medium">2FA Habilitado</span>
                    </div>
                <?php else: ?>
                    <div class="flex items-center space-x-3 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <span class="text-red-800 font-medium">2FA Desabilitado</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mensagens -->
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Configuração 2FA -->
            <?php if (empty($user['2fa_secret'])): ?>
                <!-- Habilitar 2FA -->
                <?php if (isset($_SESSION['temp_2fa_secret'])): ?>
                    <!-- QR Code e verificação -->
                    <div class="bg-white rounded-2xl shadow-xl p-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-6">Escaneie o QR Code</h2>
                        
                        <div class="text-center mb-6">
                            <div class="qr-code inline-block">
                                <img src="<?php echo htmlspecialchars($_SESSION['temp_2fa_qr']); ?>" 
                                     alt="QR Code 2FA" 
                                     class="mx-auto">
                            </div>
                        </div>
                        
                        <div class="text-center mb-6">
                            <p class="text-sm text-gray-600 mb-2">Ou digite manualmente:</p>
                            <code class="bg-gray-100 px-3 py-2 rounded text-sm font-mono">
                                <?php echo htmlspecialchars($_SESSION['temp_2fa_secret']); ?>
                            </code>
                        </div>
                        
                        <form method="POST" action="" class="space-y-4">
                            <input type="hidden" name="action" value="verify">
                            
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                                    Código de Verificação
                                </label>
                                <input type="text" 
                                       id="code" 
                                       name="code" 
                                       maxlength="6"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 text-center text-xl tracking-widest"
                                       placeholder="000000"
                                       required>
                            </div>
                            
                            <button type="submit" 
                                    class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-200">
                                Verificar e Habilitar 2FA
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Iniciar configuração -->
                    <div class="bg-white rounded-2xl shadow-xl p-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Habilitar 2FA</h2>
                        
                        <div class="mb-6">
                            <h3 class="font-medium text-gray-900 mb-2">Como funciona:</h3>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• Baixe um aplicativo autenticador (Google Authenticator, Authy, etc.)</li>
                                <li>• Escaneie o QR Code que será gerado</li>
                                <li>• Digite o código de 6 dígitos para verificar</li>
                            </ul>
                        </div>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="enable">
                            <button type="submit" 
                                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                                Gerar QR Code
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Desabilitar 2FA -->
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Desabilitar 2FA</h2>
                    
                    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm text-yellow-800">
                            <strong>Atenção:</strong> Desabilitar o 2FA reduzirá a segurança da sua conta.
                        </p>
                    </div>
                    
                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="action" value="disable">
                        
                        <div>
                            <label for="confirm_code" class="block text-sm font-medium text-gray-700 mb-2">
                                Código de Confirmação
                            </label>
                            <input type="text" 
                                   id="confirm_code" 
                                   name="confirm_code" 
                                   maxlength="6"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition duration-200 text-center text-xl tracking-widest"
                                   placeholder="000000"
                                   required>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-red-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition duration-200">
                            Desabilitar 2FA
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Links -->
            <div class="text-center mt-8">
                <a href="dashboard.php" class="text-blue-600 hover:text-blue-800 text-sm">
                    ← Voltar ao Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus no campo de código
        const codeInput = document.getElementById('code') || document.getElementById('confirm_code');
        if (codeInput) {
            codeInput.focus();
            
            // Auto-submit quando 6 dígitos forem digitados
            codeInput.addEventListener('input', function(e) {
                const value = e.target.value.replace(/\D/g, '');
                e.target.value = value;
                
                if (value.length === 6) {
                    document.querySelector('form').submit();
                }
            });
        }
        
        // Auto-hide success message
        <?php if ($success): ?>
        setTimeout(function() {
            const successMsg = document.querySelector('.success-message');
            if (successMsg) {
                successMsg.style.display = 'none';
            }
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>
