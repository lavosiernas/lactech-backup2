<?php
/**
 * SafeNode - Conexão com KRON
 */

session_start();

// SEGURANÇA: Carregar helpers e aplicar headers
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/KronConnector.php';

$db = getSafeNodeDatabase();
$userId = $_SESSION['safenode_user_id'] ?? null;
$userEmail = $_SESSION['safenode_user_email'] ?? $_SESSION['safenode_email'] ?? '';

$kronConnector = new KronConnector();
$connectionStatus = $kronConnector->getConnectionStatus($userId, $db);

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
            if ($kronConnector->saveConnection($userId, $result['kron_user_id'], $result['connection_token'], $db)) {
                $message = 'Conectado com KRON com sucesso!';
                $messageType = 'success';
                $connectionStatus = $kronConnector->getConnectionStatus($userId, $db);
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
    if ($kronConnector->disconnect($userId, $db)) {
        $message = 'Desconectado do KRON com sucesso';
        $messageType = 'success';
        $connectionStatus = $kronConnector->getConnectionStatus($userId, $db);
    } else {
        $message = 'Erro ao desconectar';
        $messageType = 'error';
    }
}

$pageTitle = 'Conexão KRON';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://unpkg.com/html5-qrcode@latest"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background-color: #030303;
            color: #a1a1aa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            min-height: 100vh;
        }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: rgba(255,255,255,0.1); 
            border-radius: 3px;
        }
        
        .card {
            background: #0a0a0a;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
        }
        
        .btn {
            font-weight: 500;
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #ffffff;
            color: #000000;
        }
        
        .btn-primary:hover {
            background: #f5f5f5;
        }
        
        .btn-secondary {
            background: #1a1a1a;
            color: #ffffff;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .btn-secondary:hover {
            background: #262626;
            border-color: rgba(255,255,255,0.15);
        }
        
        .btn-danger {
            background: #dc2626;
            color: #ffffff;
        }
        
        .btn-danger:hover {
            background: #b91c1c;
        }
        
        .input {
            width: 100%;
            padding: 12px 16px;
            background: #0f0f0f;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            color: #ffffff;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .input:focus {
            outline: none;
            border-color: rgba(255,255,255,0.2);
            background: #141414;
        }
        
        .input::placeholder {
            color: #52525b;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .status-badge.connected {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .status-badge.disconnected {
            background: rgba(63, 63, 70, 0.2);
            color: #71717a;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        @media (max-width: 640px) {
            .card {
                padding: 20px !important;
            }
            
            h1 {
                font-size: 20px !important;
            }
            
            h2 {
                font-size: 18px !important;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="border-b border-white/5 bg-[#050505] sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="dashboard.php" class="p-2 hover:bg-white/5 rounded-lg transition-colors">
                        <i data-lucide="arrow-left" class="w-5 h-5 text-zinc-400"></i>
                    </a>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-semibold text-white">Conexão KRON</h1>
                        <p class="text-xs sm:text-sm text-zinc-500 mt-0.5 hidden sm:block">Conecte sua conta ao ecossistema KRON</p>
                    </div>
                </div>
                <a href="profile.php" class="text-sm text-zinc-400 hover:text-white transition-colors hidden sm:block">
                    Perfil
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-6 sm:py-12">
        <?php if ($message): ?>
            <div class="card p-4 mb-6 flex items-start gap-3 <?php 
                echo $messageType === 'success' ? 'bg-emerald-500/5 border-emerald-500/20 text-emerald-400' : 
                    'bg-red-500/5 border-red-500/20 text-red-400'; 
            ?>">
                <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                <span class="text-sm font-medium"><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <!-- Status Card -->
        <div class="card p-6 sm:p-8 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="icon-box flex-shrink-0">
                        <i data-lucide="<?php echo $connectionStatus['connected'] ? 'link-2' : 'link-off'; ?>" class="w-5 h-5 text-zinc-400"></i>
                    </div>
                    <div>
                        <h2 class="text-lg sm:text-xl font-semibold text-white mb-1">Status da Conexão</h2>
                        <?php if ($connectionStatus['connected']): ?>
                            <p class="text-sm text-zinc-500">Conectado desde <?= date('d/m/Y H:i', strtotime($connectionStatus['connected_at'])) ?></p>
                        <?php else: ?>
                            <p class="text-sm text-zinc-500">Não conectado ao ecossistema KRON</p>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="status-badge <?php echo $connectionStatus['connected'] ? 'connected' : 'disconnected'; ?>">
                    <i data-lucide="<?php echo $connectionStatus['connected'] ? 'check-circle' : 'x-circle'; ?>" class="w-4 h-4"></i>
                    <?php echo $connectionStatus['connected'] ? 'Conectado' : 'Desconectado'; ?>
                </span>
            </div>
        </div>

        <?php if (!$connectionStatus['connected']): ?>
            <!-- Connection Options -->
            <div class="space-y-4">
                <div class="text-center mb-6">
                    <h2 class="text-xl sm:text-2xl font-semibold text-white mb-2">Conectar com KRON</h2>
                    <p class="text-sm text-zinc-500">Escolha uma das opções abaixo</p>
                </div>

                <!-- QR Code Option -->
                <div class="card p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-4 sm:gap-6">
                        <div class="icon-box flex-shrink-0">
                            <i data-lucide="qrcode" class="w-5 h-5 text-zinc-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base sm:text-lg font-semibold text-white mb-2">Escanear QR Code</h3>
                            <p class="text-sm text-zinc-500 mb-4">Use a câmera do dispositivo para escanear o código QR gerado no dashboard do KRON</p>
                            <button id="qr-scanner-btn" onclick="startQRScanner()" class="btn btn-primary">
                                <i data-lucide="camera" class="w-4 h-4"></i>
                                Abrir Câmera
                            </button>
                            <div id="qr-reader" class="mt-4 hidden rounded-lg overflow-hidden bg-black/20 border border-white/5"></div>
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div class="relative py-4">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-white/5"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="px-3 bg-[#030303] text-xs text-zinc-600 font-medium">OU</span>
                    </div>
                </div>

                <!-- Token Option -->
                <div class="card p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-4 sm:gap-6">
                        <div class="icon-box flex-shrink-0">
                            <i data-lucide="key" class="w-5 h-5 text-zinc-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base sm:text-lg font-semibold text-white mb-2">Inserir Token</h3>
                            <p class="text-sm text-zinc-500 mb-4">Cole o token de conexão gerado no dashboard do KRON</p>
                            <form method="POST" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-300 mb-2">Token de Conexão</label>
                                    <input type="text" name="connection_token" required
                                           class="input"
                                           placeholder="Cole o token aqui">
                                    <p class="mt-2 text-xs text-zinc-500 flex items-center gap-1.5">
                                        <i data-lucide="info" class="w-3.5 h-3.5"></i>
                                        Obtenha o token no dashboard do KRON
                                    </p>
                                </div>
                                <button type="submit" name="connect_kron" class="btn btn-secondary">
                                    Conectar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Disconnect -->
            <div class="card p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-start gap-4 sm:gap-6">
                    <div class="icon-box flex-shrink-0" style="background: rgba(220, 38, 38, 0.1); border-color: rgba(220, 38, 38, 0.2);">
                        <i data-lucide="unlink" class="w-5 h-5 text-red-500"></i>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-lg sm:text-xl font-semibold text-white mb-2">Desconectar do KRON</h2>
                        <p class="text-sm text-zinc-500 mb-6">Ao desconectar, você precisará conectar novamente para usar os recursos do ecossistema KRON.</p>
                        <form method="POST" onsubmit="return confirm('Deseja realmente desconectar do KRON?');">
                            <button type="submit" name="disconnect_kron" class="btn btn-danger">
                                <i data-lucide="unlink" class="w-4 h-4"></i>
                                Desconectar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        let html5QrcodeScanner = null;
        let isScanning = false;

        // Função auxiliar para atualizar ícones de forma segura
        function updateIcons() {
            try {
                lucide.createIcons();
            } catch (e) {
                console.error('Erro ao atualizar ícones:', e);
            }
        }
        
        // Inicializar ícones quando o DOM estiver pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => {
                    updateIcons();
                }, 100);
            });
        } else {
            setTimeout(() => {
                updateIcons();
            }, 100);
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
            qrReader.innerHTML = '<div class="text-center p-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div><p class="mt-4 text-sm text-zinc-400">Iniciando câmera...</p></div>';

            // Atualizar botão
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Iniciando...';
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
                    button.innerHTML = '<i data-lucide="square" class="w-4 h-4"></i> Parar Scanner';
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
                    closeBtn.innerHTML = '<i data-lucide="x" class="w-4 h-4"></i> Fechar Câmera';
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
                // Erros de permissão (verificar primeiro, antes de outros erros)
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
                else if (errName === 'OverconstrainedError' || 
                        errName === 'ConstraintNotSatisfiedError' || 
                        errLower.includes('constraint')) {
                    errorMsg = 'Configurações da câmera não suportadas.';
                    errorDetails = 'Tente usar outro navegador ou verifique as configurações da câmera.';
                }
                else if (errLower.includes('video') || 
                        errLower.includes('stream') ||
                        errLower.includes('getusermedia')) {
                    errorMsg = 'Erro ao acessar o stream de vídeo da câmera.';
                    errorDetails = 'Verifique se a câmera está funcionando e se o navegador tem permissão para acessá-la.';
                }
                else if (errLower.includes('timeout') || 
                        errLower.includes('timed out')) {
                    errorMsg = 'Tempo limite excedido ao acessar a câmera.';
                    errorDetails = 'A câmera demorou muito para responder. Tente novamente.';
                }
                else if (errLower.includes('abort') || 
                        errLower.includes('aborted')) {
                    errorMsg = 'Acesso à câmera foi cancelado.';
                    errorDetails = 'O acesso à câmera foi interrompido. Tente novamente.';
                }
                else if (errLower.includes('notsupported') || 
                        errLower.includes('not supported')) {
                    errorMsg = 'Funcionalidade não suportada pelo navegador.';
                    errorDetails = 'Seu navegador não suporta esta funcionalidade. Tente atualizar o navegador ou usar outro.';
                }
                else if (errLower.includes('security') || 
                        errLower.includes('secure') ||
                        errLower.includes('https')) {
                    errorMsg = 'Erro de segurança ao acessar a câmera.';
                    errorDetails = 'Em produção, é necessário usar HTTPS. Em localhost, verifique as configurações de privacidade do navegador.';
                }
                else if (errLower.includes('failed') || 
                        errLower.includes('error')) {
                    errorMsg = 'Falha ao inicializar a câmera.';
                    errorDetails = 'Verifique se a câmera está funcionando e se o navegador tem permissão para acessá-la.';
                }
                else {
                    // Último recurso: mostrar informações do erro de forma mais clara
                    errorMsg = 'Erro ao acessar a câmera.';
                    errorDetails = `Tipo: ${errName || 'Desconhecido'}. Verifique as permissões da câmera no navegador e tente novamente.`;
                }

                qrReader.innerHTML = `<div class="p-6 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400">
                    <div class="flex items-start gap-3 mb-3">
                        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                        <div class="flex-1">
                            <p class="font-semibold text-base mb-1">${errorMsg}</p>
                            <p class="text-sm text-red-300/90">${errorDetails}</p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-red-500/20">
                        <p class="text-xs text-red-300/70 mb-2 font-medium">Soluções possíveis:</p>
                        <ul class="text-xs text-red-300/70 space-y-1 list-disc list-inside">
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
                    button.innerHTML = '<i data-lucide="camera" class="w-4 h-4"></i> Abrir Câmera';
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
                button.innerHTML = '<i data-lucide="camera" class="w-4 h-4"></i> Abrir Câmera';
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
                    qrReader.innerHTML = '<div class="p-6 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-emerald-400 text-sm text-center"><i data-lucide="check-circle" class="w-6 h-6 mx-auto mb-2"></i><p>QR Code escaneado com sucesso! Conectando...</p></div>';
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
                    qrReader.innerHTML = `<div class="p-6 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm text-center"><i data-lucide="alert-circle" class="w-6 h-6 mx-auto mb-2"></i><p>${err.message || 'Erro ao ler QR Code'}</p><button onclick="startQRScanner()" class="mt-4 btn btn-secondary">Tentar Novamente</button></div>`;
                    updateIcons();
                }
                
                isScanning = false;
            }
        }

    </script>
</body>
</html>
