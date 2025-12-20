<?php
/**
 * LacTech - Conexão com KRON
 */

session_start();

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/Database.class.php';
require_once __DIR__ . '/includes/KronConnector.php';

$db = Database::getInstance();
$userId = $_SESSION['user_id'] ?? null;
$userEmail = $_SESSION['email'] ?? '';

$kronConnector = new KronConnector();
$connectionStatus = $kronConnector->getConnectionStatus($userId, $db->getConnection());

$message = '';
$messageType = '';

// Processar conexão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connect_kron'])) {
    $token = trim($_POST['connection_token'] ?? '');
    
    if (empty($token)) {
        $message = 'Token não pode estar vazio';
        $messageType = 'error';
    } else {
        $result = $kronConnector->connectWithToken($token, $userId, $userEmail);
        
        if ($result['valid']) {
            // Salvar conexão
            if ($kronConnector->saveConnection($userId, $result['kron_user_id'], $result['connection_token'], $db->getConnection())) {
                $message = 'Conectado com KRON com sucesso!';
                $messageType = 'success';
                $connectionStatus = $kronConnector->getConnectionStatus($userId, $db->getConnection());
            } else {
                $message = 'Erro ao salvar conexão';
                $messageType = 'error';
            }
        } else {
            $message = $result['error'] ?? 'Erro ao conectar com KRON';
            $messageType = 'error';
        }
    }
}

// Processar desconexão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disconnect_kron'])) {
    if ($kronConnector->disconnect($userId, $db->getConnection())) {
        $message = 'Desconectado do KRON com sucesso';
        $messageType = 'success';
        $connectionStatus = $kronConnector->getConnectionStatus($userId, $db->getConnection());
    } else {
        $message = 'Erro ao desconectar';
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conexão KRON - LacTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://unpkg.com/html5-qrcode@latest"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 20px 60px -20px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(0, 0, 0, 0.02);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card:hover {
            box-shadow: 0 25px 70px -25px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.03);
            transform: translateY(-2px);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .status-badge.connected {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .status-badge.disconnected {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }
        
        .option-card {
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            border: 2px solid #e5e7eb;
            border-radius: 20px;
            padding: 32px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .option-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, rgba(59, 130, 246, 0.5) 50%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .option-card:hover {
            border-color: #3b82f6;
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -15px rgba(59, 130, 246, 0.2);
        }
        
        .option-card:hover::before {
            opacity: 1;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            font-weight: 600;
            padding: 14px 28px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }
        
        .btn-primary:hover::before {
            opacity: 1;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            color: white;
            font-weight: 600;
            padding: 14px 28px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            font-weight: 600;
            padding: 14px 28px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }
        
        .input-field {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
            color: #111827;
        }
        
        .input-field:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        
        .divider {
            position: relative;
            text-align: center;
            margin: 32px 0;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, #e5e7eb 50%, transparent 100%);
        }
        
        .divider span {
            position: relative;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 0 20px;
            color: #6b7280;
            font-weight: 500;
            font-size: 14px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        .icon-wrapper {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1px solid #bfdbfe;
        }
        
        .icon-wrapper svg {
            color: #3b82f6;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-xl border-b border-gray-200/50 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-6 py-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="dashboard.php" class="p-2 hover:bg-gray-100 rounded-xl transition-colors">
                        <i data-lucide="arrow-left" class="w-5 h-5 text-gray-600"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Conexão com KRON</h1>
                        <p class="text-sm text-gray-500 mt-0.5">Conecte sua conta ao ecossistema KRON</p>
                    </div>
                </div>
                <a href="gerente-completo.php" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                    Voltar ao Perfil
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-6 py-12">
        <?php if ($message): ?>
            <div class="mb-8 p-5 rounded-2xl font-semibold animate-fade-in <?php 
                echo $messageType === 'success' ? 'bg-emerald-50 text-emerald-800 border-2 border-emerald-200' : 
                    'bg-red-50 text-red-800 border-2 border-red-200'; 
            ?> flex items-center gap-3 shadow-lg">
                <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-6 h-6 flex-shrink-0"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <!-- Status da Conexão -->
        <div class="glass-card rounded-3xl p-8 mb-8 animate-fade-in">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="icon-wrapper">
                            <i data-lucide="<?php echo $connectionStatus['connected'] ? 'link-2' : 'link-off'; ?>" class="w-6 h-6"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900">Status da Conexão</h2>
                    </div>
                    <?php if ($connectionStatus['connected']): ?>
                        <p class="text-gray-600 ml-20">Conectado ao KRON desde <span class="font-semibold text-gray-900"><?= date('d/m/Y \à\s H:i', strtotime($connectionStatus['connected_at'])) ?></span></p>
                    <?php else: ?>
                        <p class="text-gray-600 ml-20">Sua conta não está conectada ao ecossistema KRON</p>
                    <?php endif; ?>
                </div>
                <span class="status-badge <?php echo $connectionStatus['connected'] ? 'connected' : 'disconnected'; ?>">
                    <i data-lucide="<?php echo $connectionStatus['connected'] ? 'check-circle' : 'x-circle'; ?>" class="w-5 h-5"></i>
                    <?php echo $connectionStatus['connected'] ? 'Conectado' : 'Não conectado'; ?>
                </span>
            </div>
        </div>

        <?php if (!$connectionStatus['connected']): ?>
            <!-- Opções de Conexão -->
            <div class="space-y-6">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Conectar com KRON</h2>
                    <p class="text-gray-600">Escolha uma das opções abaixo para conectar sua conta</p>
                </div>

                <!-- Opção 1: QR Code -->
                <div class="option-card animate-fade-in" style="animation-delay: 0.1s">
                    <div class="flex items-start gap-6">
                        <div class="icon-wrapper flex-shrink-0">
                            <i data-lucide="qrcode" class="w-7 h-7"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Escanear QR Code</h3>
                            <p class="text-gray-600 mb-6">Use a câmera do seu dispositivo para escanear o código QR gerado no dashboard do KRON</p>
                            <button id="qr-scanner-btn" onclick="startQRScanner()" class="btn-primary w-full sm:w-auto flex items-center justify-center gap-2">
                                <i data-lucide="camera" class="w-5 h-5"></i>
                                Abrir Câmera para Escanear
                            </button>
                            
                            <div id="qr-reader" class="mt-6 hidden rounded-2xl overflow-hidden bg-black"></div>
                        </div>
                    </div>
                </div>

                <div class="divider">
                    <span>OU</span>
                </div>

                <!-- Opção 2: Token Manual -->
                <div class="option-card animate-fade-in" style="animation-delay: 0.2s">
                    <div class="flex items-start gap-6">
                        <div class="icon-wrapper flex-shrink-0">
                            <i data-lucide="key" class="w-7 h-7"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Inserir Token Manualmente</h3>
                            <p class="text-gray-600 mb-6">Cole o token de conexão gerado no dashboard do KRON</p>
                            <form method="POST" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Token de Conexão</label>
                                    <input type="text" name="connection_token" required
                                           class="input-field"
                                           placeholder="Cole o token aqui">
                                    <p class="mt-2 text-xs text-gray-500 flex items-center gap-1">
                                        <i data-lucide="info" class="w-4 h-4"></i>
                                        Obtenha o token no dashboard do KRON
                                    </p>
                                </div>
                                <button type="submit" name="connect_kron" class="btn-secondary w-full sm:w-auto">
                                    Conectar com Token
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Desconectar -->
            <div class="glass-card rounded-3xl p-8 animate-fade-in">
                <div class="flex items-start gap-6">
                    <div class="icon-wrapper" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border-color: #fecaca;">
                        <i data-lucide="unlink" class="w-7 h-7" style="color: #ef4444;"></i>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Desconectar do KRON</h2>
                        <p class="text-gray-600 mb-6">Ao desconectar, você precisará conectar novamente para usar os recursos do ecossistema KRON.</p>
                        <form method="POST" onsubmit="return confirm('Deseja realmente desconectar do KRON?');">
                            <button type="submit" name="disconnect_kron" class="btn-danger">
                                <i data-lucide="unlink" class="w-5 h-5 inline mr-2"></i>
                                Desconectar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        lucide.createIcons();

        let html5QrcodeScanner = null;
        let isScanning = false;

        function updateIcons() {
            lucide.createIcons();
        }

        async function startQRScanner() {
            const qrReader = document.getElementById('qr-reader');
            const button = document.getElementById('qr-scanner-btn');
            
            // Verificar se a biblioteca está carregada
            if (typeof Html5Qrcode === 'undefined') {
                alert('Erro: Biblioteca de QR Code não foi carregada. Recarregue a página.');
                return;
            }
            
            if (isScanning) {
                // Se já está escaneando, parar
                stopQRScanner();
                return;
            }

            if (!qrReader) {
                alert('Erro: Elemento do scanner não encontrado');
                return;
            }

            // Mostrar container do scanner
            qrReader.classList.remove('hidden');
            qrReader.innerHTML = '<div class="text-center p-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div><p class="mt-4 text-sm text-gray-400">Iniciando câmera...</p></div>';

            // Atualizar botão
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Iniciando...';
                updateIcons();
            }

            try {
                // Verificar se já existe um scanner ativo
                if (html5QrcodeScanner) {
                    await html5QrcodeScanner.stop();
                    html5QrcodeScanner.clear();
                }

                // Criar novo scanner
                html5QrcodeScanner = new Html5Qrcode("qr-reader");
                
                // Limpar container
                qrReader.innerHTML = '';

                // Configuração do QR box
                const qrboxConfig = {
                    fps: 10,
                    qrbox: function(viewfinderWidth, viewfinderHeight) {
                        // Ajustar tamanho do QR box para mobile
                        const minEdgePercentage = 0.7;
                        const minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
                        const qrboxSize = Math.floor(minEdgeSize * minEdgePercentage);
                        return {
                            width: qrboxSize,
                            height: qrboxSize
                        };
                    },
                    aspectRatio: 1.0
                };

                // Callback de sucesso
                const onScanSuccess = (decodedText) => {
                    handleQRCodeScanned(decodedText);
                };

                // Callback de erro (ignorar erros normais de leitura)
                const onScanError = (errorMessage) => {
                    // Ignorar erros de leitura contínua (são normais)
                };

                // Função auxiliar para verificar se é erro de permissão
                function isPermissionError(err) {
                    if (!err) return false;
                    const errName = err.name || '';
                    const errMsg = (err.message || err.toString() || '').toLowerCase();
                    return errName === 'NotAllowedError' || 
                           errName === 'PermissionDeniedError' || 
                           errMsg.includes('permission') ||
                           errMsg.includes('not allowed') ||
                           errMsg.includes('permission denied') ||
                           errMsg.includes('user denied');
                }
                
                // Tentar iniciar com câmera traseira primeiro
                let cameraError = null;
                try {
                    await html5QrcodeScanner.start(
                        { facingMode: "environment" },
                        qrboxConfig,
                        onScanSuccess,
                        onScanError
                    );
                } catch (firstErr) {
                    cameraError = firstErr;
                    
                    // Se não for erro de permissão, tentar câmera frontal
                    if (!isPermissionError(firstErr)) {
                        try {
                            await html5QrcodeScanner.stop();
                            await html5QrcodeScanner.start(
                                { facingMode: "user" },
                                qrboxConfig,
                                onScanSuccess,
                                onScanError
                            );
                            cameraError = null; // Sucesso com câmera frontal
                        } catch (secondErr) {
                            // Se ambas falharem, usar o primeiro erro
                            cameraError = firstErr;
                        }
                    }
                }
                
                // Se houve erro, lançar
                if (cameraError) {
                    throw cameraError;
                }

                isScanning = true;

                // Atualizar botão para "Parar"
                if (button) {
                    button.disabled = false;
                    button.innerHTML = '<i data-lucide="square" class="w-5 h-5"></i> Parar Scanner';
                    button.onclick = stopQRScanner;
                    button.setAttribute('onclick', 'stopQRScanner()');
                    updateIcons();
                }

                // Adicionar botão de fechar se não existir
                let closeBtn = qrReader.parentElement.querySelector('#qr-close-btn');
                if (!closeBtn) {
                    closeBtn = document.createElement('button');
                    closeBtn.id = 'qr-close-btn';
                    closeBtn.className = 'mt-4 btn btn-secondary w-full sm:w-auto';
                    closeBtn.innerHTML = '<i data-lucide="x" class="w-5 h-5"></i> Fechar Câmera';
                    closeBtn.onclick = stopQRScanner;
                    qrReader.parentElement.appendChild(closeBtn);
                }
                updateIcons();

            } catch (err) {
                // Normalizar o erro para análise
                const errName = err.name || '';
                const errMessage = err.message || err.toString() || '';
                const errStr = errName + ' ' + errMessage;
                const errLower = errStr.toLowerCase();
                
                let errorMsg = '';
                let errorDetails = '';
                
                // Verificar se a biblioteca está carregada
                if (typeof Html5Qrcode === 'undefined') {
                    errorMsg = 'Biblioteca de QR Code não carregada.';
                    errorDetails = 'Recarregue a página. Se o problema persistir, verifique sua conexão com a internet.';
                }
                // Verificar suporte do navegador
                else if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    errorMsg = 'Navegador não suporta acesso à câmera.';
                    errorDetails = 'Use um navegador moderno como Chrome, Firefox, Edge ou Safari.';
                }
                // Erros de permissão
                else if (errName === 'NotAllowedError' || 
                        errName === 'PermissionDeniedError' || 
                        errLower.includes('permission') ||
                        errLower.includes('not allowed') ||
                        errLower.includes('permission denied')) {
                    errorMsg = 'Permissão de câmera negada.';
                    errorDetails = 'Clique no ícone de cadeado na barra de endereço e permita o acesso à câmera. Em seguida, recarregue a página e tente novamente.';
                }
                else if (errName === 'NotFoundError' || 
                        errName === 'DevicesNotFoundError' || 
                        errLower.includes('device') || 
                        errLower.includes('camera') ||
                        errLower.includes('no camera')) {
                    errorMsg = 'Nenhuma câmera encontrada.';
                    errorDetails = 'Verifique se há uma câmera conectada ao dispositivo e se está funcionando.';
                }
                else if (errName === 'NotReadableError' || 
                        errName === 'TrackStartError' || 
                        errLower.includes('busy') || 
                        errLower.includes('in use') ||
                        errLower.includes('already in use')) {
                    errorMsg = 'Câmera está sendo usada por outro aplicativo.';
                    errorDetails = 'Feche outros aplicativos que possam estar usando a câmera (Zoom, Teams, etc.) e tente novamente.';
                }
                else if (errLower.includes('security') || 
                        errLower.includes('secure') ||
                        errLower.includes('https')) {
                    errorMsg = 'Erro de segurança ao acessar a câmera.';
                    errorDetails = 'Em produção, é necessário usar HTTPS. Em localhost, verifique as configurações de privacidade do navegador.';
                }
                else {
                    errorMsg = 'Erro ao acessar a câmera.';
                    errorDetails = `Tipo: ${errName || 'Desconhecido'}. Verifique as permissões da câmera no navegador e tente novamente.`;
                }

                qrReader.innerHTML = `<div class="p-6 bg-red-50 border-2 border-red-200 rounded-lg text-red-800">
                    <div class="flex items-start gap-3 mb-3">
                        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                        <div class="flex-1">
                            <p class="font-semibold text-base mb-1">${errorMsg}</p>
                            <p class="text-sm text-red-700">${errorDetails}</p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-red-200">
                        <p class="text-xs text-red-600 mb-2 font-medium">Soluções possíveis:</p>
                        <ul class="text-xs text-red-600 space-y-1 list-disc list-inside">
                            <li>Verifique se o navegador tem permissão para acessar a câmera</li>
                            <li>Feche outros aplicativos que possam estar usando a câmera</li>
                            <li>Tente recarregar a página e permitir o acesso quando solicitado</li>
                            <li>Verifique se está usando HTTPS (necessário em produção)</li>
                        </ul>
                    </div>
                </div>`;
                updateIcons();
                
                if (button) {
                    button.disabled = false;
                    button.innerHTML = '<i data-lucide="camera" class="w-5 h-5"></i> Abrir Câmera para Escanear';
                    button.onclick = startQRScanner;
                    button.setAttribute('onclick', 'startQRScanner()');
                    updateIcons();
                }

                isScanning = false;
            }
        }

        async function stopQRScanner() {
            if (html5QrcodeScanner && isScanning) {
                try {
                    await html5QrcodeScanner.stop();
                    html5QrcodeScanner.clear();
                } catch (err) {
                    console.error('Erro ao parar scanner:', err);
                }
                html5QrcodeScanner = null;
                isScanning = false;
            }

            const qrReader = document.getElementById('qr-reader');
            if (qrReader) {
                qrReader.classList.add('hidden');
                qrReader.innerHTML = '';
            }

            // Restaurar botão principal
            const button = document.getElementById('qr-scanner-btn');
            if (button) {
                button.disabled = false;
                button.innerHTML = '<i data-lucide="camera" class="w-5 h-5"></i> Abrir Câmera para Escanear';
                button.onclick = startQRScanner;
                button.setAttribute('onclick', 'startQRScanner()');
            }

            // Remover botão de fechar se existir
            const closeBtn = document.getElementById('qr-close-btn');
            if (closeBtn) {
                closeBtn.remove();
            }

            updateIcons();
        }

        function handleQRCodeScanned(qrData) {
            try {
                // Tentar parsear como JSON
                let data;
                try {
                    data = JSON.parse(qrData);
                } catch (e) {
                    // Se não for JSON, pode ser apenas o token
                    data = { token: qrData };
                }
                
                if (!data.token) {
                    throw new Error('QR Code inválido: token não encontrado');
                }

                // Parar scanner
                stopQRScanner();

                // Mostrar feedback
                const qrReader = document.getElementById('qr-reader');
                if (qrReader) {
                    qrReader.classList.remove('hidden');
                    qrReader.innerHTML = '<div class="p-6 bg-emerald-50 border-2 border-emerald-200 rounded-lg text-emerald-800 text-sm text-center"><i data-lucide="check-circle" class="w-6 h-6 mx-auto mb-2"></i><p>QR Code escaneado com sucesso! Conectando...</p></div>';
                    updateIcons();
                }

                // Enviar token para conexão
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="connection_token" value="${data.token}">
                    <input type="hidden" name="connect_kron" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
                
            } catch (err) {
                console.error('Erro ao processar QR Code:', err);
                
                const qrReader = document.getElementById('qr-reader');
                if (qrReader) {
                    qrReader.innerHTML = `<div class="p-6 bg-red-50 border-2 border-red-200 rounded-lg text-red-800 text-sm text-center"><i data-lucide="alert-circle" class="w-6 h-6 mx-auto mb-2"></i><p>${err.message || 'Erro ao ler QR Code'}</p><button onclick="startQRScanner()" class="mt-4 btn btn-secondary">Tentar Novamente</button></div>`;
                    updateIcons();
                }
                
                isScanning = false;
            }
        }
    </script>
</body>
</html>
