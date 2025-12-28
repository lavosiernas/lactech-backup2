<?php
/**
 * Página: Registrar Volume Geral
 * Subpágina do Dashboard Gerente
 */

// Incluir configuração e autenticação
require_once __DIR__ . '/../includes/config_login.php';
require_once __DIR__ . '/../includes/Database.class.php';

// Verificar autenticação
if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Não autenticado']));
}

// Verificar papel de gerente
if ($_SESSION['user_role'] !== 'gerente' && $_SESSION['user_role'] !== 'manager') {
    http_response_code(403);
    die(json_encode(['error' => 'Acesso negado']));
}

$v = time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Volume Geral - LacTech</title>
    
    <!-- Tailwind CSS -->
    <?php if (file_exists(__DIR__ . '/../assets/css/tailwind.min.css')): ?>
        <link rel="stylesheet" href="../assets/css/tailwind.min.css">
    <?php else: ?>
        <script src="https://cdn.tailwindcss.com"></script>
    <?php endif; ?>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo $v; ?>">
    
    <style>
        :root {
            --forest-50: #f0f9f4;
            --forest-100: #dcf2e4;
            --forest-200: #bce5d0;
            --forest-300: #8dd1b3;
            --forest-400: #56b991;
            --forest-500: #2d9b6f;
            --forest-600: #1f7a5a;
            --forest-700: #1a6249;
            --forest-800: #174f3c;
            --forest-900: #144132;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13l8 0c1.11 0 2.08-.402 2.599-1M21 13l-8 0c-1.11 0-2.08-.402-2.599-1M16 8V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v3m4 6h.01M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Registrar Volume Geral</h3>
                        <p class="text-sm text-white/90">Registro de produção total</p>
                    </div>
                </div>
                <button onclick="window.parent.postMessage({type: 'closeModal'}, '*')" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form id="generalVolumeForm" class="overflow-y-auto max-h-[calc(90vh-200px)]">
                <div class="p-6 space-y-6">
                    <!-- Mensagem de sucesso/erro -->
                    <div id="generalVolumeMessage" class="hidden p-4 rounded-xl border"></div>

                    <!-- Informações da Coleta -->
                    <div class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <h4 class="text-base font-bold text-slate-800">Informações da Coleta</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Data da Coleta
                                    </span>
                                </label>
                                <input type="date" name="collection_date" class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Período
                                    </span>
                                </label>
                                <select name="period" class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white" required>
                                    <option value="manha">Manhã</option>
                                    <option value="tarde">Tarde</option>
                                    <option value="noite">Noite</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Medições -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <h4 class="text-base font-bold text-slate-800">Medições</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        Número de Vacas
                                    </span>
                                </label>
                                <div class="relative">
                                    <input type="number" name="total_animals" id="totalAnimalsInput" step="1" min="1" placeholder="0" class="w-full px-4 py-3 pl-12 border-2 border-green-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white font-semibold text-green-700" required>
                                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Quantas vacas participaram desta coleta?</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        Volume Total
                                    </span>
                                </label>
                                <div class="relative">
                                    <input type="number" name="total_volume" id="totalVolumeInput" step="0.1" min="0" placeholder="0.0" class="w-full px-4 py-3 pl-12 border-2 border-green-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white font-semibold text-green-700" required>
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-green-600 font-bold">L</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1" id="averagePerCowDisplay">Média por vaca: -- L</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1V9a5 5 0 00-10 0v6a4 4 0 004 4zm0-10a2 2 0 112 2"/>
                                        </svg>
                                        Temperatura
                                        <span class="text-xs font-normal text-slate-500">(opcional)</span>
                                    </span>
                                </label>
                                <div class="relative">
                                    <input type="number" name="temperature" step="0.1" placeholder="0.0" class="w-full px-4 py-3 pl-12 border-2 border-green-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white font-semibold text-green-700">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-green-600 font-bold">°C</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="sticky bottom-0 bg-white border-t border-slate-200 px-6 py-4 flex justify-end gap-3">
                    <button type="button" onclick="window.parent.postMessage({type: 'closeModal'}, '*')" class="px-6 py-3 text-sm font-semibold border-2 border-slate-300 text-slate-700 rounded-xl hover:bg-slate-50 transition-all">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 text-sm font-semibold bg-gradient-to-r from-green-600 to-emerald-700 text-white rounded-xl hover:from-green-700 hover:to-emerald-800 transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Registrar Volume
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/toast-notifications.js?v=<?php echo $v; ?>"></script>
    <script>
        // Calcular média por vaca
        document.getElementById('totalAnimalsInput').addEventListener('input', function() {
            calculateAverage();
        });
        
        document.getElementById('totalVolumeInput').addEventListener('input', function() {
            calculateAverage();
        });
        
        function calculateAverage() {
            const animals = parseFloat(document.getElementById('totalAnimalsInput').value) || 0;
            const volume = parseFloat(document.getElementById('totalVolumeInput').value) || 0;
            const avg = animals > 0 ? (volume / animals).toFixed(2) : 0;
            document.getElementById('averagePerCowDisplay').textContent = `Média por vaca: ${avg} L`;
        }
        
        // Submeter formulário
        document.getElementById('generalVolumeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('../api/actions.php?action=register_general_volume', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (window.parent && window.parent.showSuccessToast) {
                        window.parent.showSuccessToast('Volume registrado com sucesso!');
                    }
                    window.parent.postMessage({type: 'volumeRegistered', success: true}, '*');
                    setTimeout(() => {
                        window.parent.postMessage({type: 'closeModal'}, '*');
                    }, 1000);
                } else {
                    const messageDiv = document.getElementById('generalVolumeMessage');
                    messageDiv.className = 'p-4 rounded-xl border border-red-300 bg-red-50 text-red-800';
                    messageDiv.textContent = result.error || 'Erro ao registrar volume';
                    messageDiv.classList.remove('hidden');
                }
            } catch (error) {
                const messageDiv = document.getElementById('generalVolumeMessage');
                messageDiv.className = 'p-4 rounded-xl border border-red-300 bg-red-50 text-red-800';
                messageDiv.textContent = 'Erro ao processar solicitação';
                messageDiv.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>

