<?php
// Página de Download Seguro do APK LacTech
// Obter informações do arquivo APK
$apkPath = __DIR__ . '/lactechapp/LacTech.apk';
$apkSize = file_exists($apkPath) ? filesize($apkPath) : 0;
$apkSizeMB = round($apkSize / 1048576, 2); // Converter para MB
$apkDate = file_exists($apkPath) ? date('d/m/Y H:i', filemtime($apkPath)) : 'N/A';

// Versão do app (pode ser obtida do manifest.json)
$appVersion = '2.2.0';
$appName = 'LacTech - Sistema de Gestão Leiteira';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baixar App LacTech - Download Seguro</title>
    <meta name="description" content="Baixe o aplicativo LacTech para Android. Instale o sistema de gestão leiteira no seu dispositivo móvel.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="./assets/img/lactech-logo.png" type="image/png">
    <link rel="manifest" href="/manifest.json">
    
    <!-- Tailwind CSS -->
    <?php if (file_exists(__DIR__ . '/assets/css/tailwind.min.css')): ?>
        <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <?php else: ?>
        <script src="https://cdn.tailwindcss.com"></script>
    <?php endif; ?>
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .gradient-forest {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .7;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-forest text-white shadow-lg">
        <div class="max-w-4xl mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img src="./assets/img/lactech-logo.png" alt="LacTech Logo" class="w-12 h-12 bg-white rounded-xl p-2">
                    <div>
                        <h1 class="text-2xl font-bold">LacTech</h1>
                        <p class="text-green-100 text-sm">Download Seguro</p>
                    </div>
                </div>
                <a href="index.php" class="text-white hover:text-green-100 transition-colors text-sm font-medium">
                    ← Voltar
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 py-8">
        <!-- App Info Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-6 border border-gray-200">
            <div class="flex flex-col md:flex-row items-center md:items-start gap-6 mb-6">
                <div class="flex-shrink-0">
                    <img src="./assets/img/lactech-logo.png" alt="LacTech" class="w-32 h-32 bg-green-50 rounded-3xl p-4 shadow-lg">
                </div>
                <div class="flex-1 text-center md:text-left">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($appName); ?></h2>
                    <p class="text-gray-600 mb-4">Sistema completo de gestão para fazendas leiteiras</p>
                    <div class="flex flex-wrap gap-4 justify-center md:justify-start text-sm">
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Versão <?php echo htmlspecialchars($appVersion); ?>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                            </svg>
                            <?php echo $apkSizeMB; ?> MB
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Atualizado em <?php echo $apkDate; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Download Button -->
            <div class="border-t border-gray-200 pt-6">
                <?php if (file_exists($apkPath)): ?>
                    <a href="download-apk.php" 
                       class="w-full md:w-auto inline-flex items-center justify-center px-8 py-4 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 text-lg pulse-animation"
                       onclick="trackDownload(); return true;">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Baixar APK (<?php echo $apkSizeMB; ?> MB)
                    </a>
                <?php else: ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-yellow-800">
                        <p class="font-medium">APK não encontrado. Entre em contato com o suporte.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Security Info -->
        <div class="bg-blue-50 border-l-4 border-blue-500 rounded-r-xl p-6 mb-6">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-blue-600 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <div>
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">Download Seguro e Verificado</h3>
                    <ul class="text-blue-800 space-y-1 text-sm">
                        <li>• APK assinado digitalmente e verificado</li>
                        <li>• Arquivo fornecido diretamente pelo desenvolvedor oficial</li>
                        <li>• Sem vírus ou malware - 100% seguro</li>
                        <li>• Compatível com Android 5.0 ou superior</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Installation Instructions -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-6 border border-gray-200">
            <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <svg class="w-7 h-7 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                Como Instalar
            </h3>
            
            <div class="space-y-6">
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center font-bold text-lg">
                        1
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 mb-2">Baixe o APK</h4>
                        <p class="text-gray-600 text-sm">Clique no botão "Baixar APK" acima. O download começará automaticamente.</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center font-bold text-lg">
                        2
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 mb-2">Permitir Fontes Desconhecidas</h4>
                        <p class="text-gray-600 text-sm mb-2">Nas configurações do Android, vá em <strong>Segurança</strong> e ative <strong>"Fontes desconhecidas"</strong> ou <strong>"Instalar apps desconhecidos"</strong>.</p>
                        <div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-600">
                            <strong>Android 8.0+:</strong> Ao tentar instalar, o Android pedirá permissão. Selecione "Permitir desta fonte".
                        </div>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center font-bold text-lg">
                        3
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 mb-2">Instale o APK</h4>
                        <p class="text-gray-600 text-sm">Abra o arquivo baixado e toque em <strong>"Instalar"</strong>. Aguarde a instalação ser concluída.</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center font-bold text-lg">
                        4
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 mb-2">Abra o App</h4>
                        <p class="text-gray-600 text-sm">Após a instalação, toque em <strong>"Abrir"</strong> ou encontre o ícone do LacTech na sua lista de aplicativos.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- App Features -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-6 border border-gray-200">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">Recursos do App</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h4 class="font-semibold text-gray-900">Funciona Offline</h4>
                        <p class="text-sm text-gray-600">Use o app mesmo sem internet. Os dados sincronizam automaticamente quando voltar online.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h4 class="font-semibold text-gray-900">Interface Moderna</h4>
                        <p class="text-sm text-gray-600">Design intuitivo e fácil de usar, otimizado para dispositivos móveis.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h4 class="font-semibold text-gray-900">Gestão Completa</h4>
                        <p class="text-sm text-gray-600">Controle de rebanho, produção, saúde, reprodução e muito mais.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h4 class="font-semibold text-gray-900">Dados Seguros</h4>
                        <p class="text-sm text-gray-600">Seus dados são criptografados e armazenados com segurança.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warning -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <h4 class="font-semibold text-yellow-900 mb-2">Aviso Importante</h4>
                    <p class="text-yellow-800 text-sm mb-2">Este APK está disponível apenas para download direto. Não está disponível na Google Play Store no momento.</p>
                    <p class="text-yellow-800 text-sm">Certifique-se de baixar apenas deste site oficial (<strong>lactechsys.com</strong>) para garantir a segurança do aplicativo.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-12 py-8">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <p class="text-gray-400 text-sm">
                © <?php echo date('Y'); ?> LacTech - Sistema de Gestão Leiteira. Todos os direitos reservados.
            </p>
            <p class="text-gray-500 text-xs mt-2">
                Baixe apenas deste site oficial para garantir a segurança do aplicativo.
            </p>
        </div>
    </footer>

    <script>
        function trackDownload() {
            // Rastrear download (Google Analytics se disponível)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'download_apk', {
                    'event_category': 'App Download',
                    'event_label': 'LacTech APK',
                    'value': 1
                });
            }
            
            // Console log para debug
            console.log('Download do APK iniciado');
        }

        // Detectar se é Android
        const isAndroid = /Android/i.test(navigator.userAgent);
        if (isAndroid) {
            console.log('Dispositivo Android detectado');
        }
    </script>
</body>
</html>

