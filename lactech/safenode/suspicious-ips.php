<?php
session_start();
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'IPs Suspeitos';
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - SafeNode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .glass-card { background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="bg-black text-white min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="flex-1 overflow-y-auto">
            <div class="max-w-7xl mx-auto p-6">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-white mb-2 flex items-center gap-3">
                        <i data-lucide="alert-octagon" class="w-8 h-8 text-red-400"></i>
                        IPs Suspeitos
                    </h1>
                    <p class="text-zinc-400">IPs com múltiplos tipos de ataque detectados</p>
                </div>

                <div id="suspicious-content" class="space-y-6">
                    <div class="text-center py-12 text-zinc-500">
                        <i data-lucide="loader" class="w-12 h-12 mx-auto mb-4 animate-spin"></i>
                        <p class="text-sm">Carregando IPs suspeitos...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let suspiciousData = null;

        async function fetchSuspiciousIPs() {
            try {
                const response = await fetch('api/dashboard-stats.php');
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('HTTP Error:', response.status, errorText);
                    throw new Error(`HTTP ${response.status}: ${errorText.substring(0, 100)}`);
                }
                
                const data = await response.json();
                console.log('API Response:', data);
                
                if (data.success) {
                    suspiciousData = data.data?.analytics?.suspicious_ips || [];
                    console.log('Suspicious IPs Data:', suspiciousData);
                    updateSuspiciousPage();
                } else {
                    throw new Error(data.error || 'Erro desconhecido');
                }
            } catch (error) {
                console.error('Erro ao buscar IPs suspeitos:', error);
                document.getElementById('suspicious-content').innerHTML = `
                    <div class="glass-card rounded-xl p-8 text-center">
                        <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-4 text-red-400"></i>
                        <p class="text-red-400 font-bold mb-2">Erro ao carregar dados</p>
                        <p class="text-zinc-500 text-sm">${error.message}</p>
                        <button onclick="fetchSuspiciousIPs()" class="mt-4 px-4 py-2 bg-blue-500 hover:bg-blue-600 rounded-lg text-sm">Tentar novamente</button>
                    </div>
                `;
                lucide.createIcons();
            }
        }

        function updateSuspiciousPage() {
            if (!suspiciousData) return;
            
            const container = document.getElementById('suspicious-content');
            const ips = suspiciousData || [];
            
            if (ips.length === 0) {
                container.innerHTML = `
                    <div class="glass-card rounded-xl p-8 text-center">
                        <i data-lucide="check-circle" class="w-12 h-12 mx-auto mb-4 text-emerald-400"></i>
                        <p class="text-emerald-400 font-bold mb-2">Nenhum IP suspeito detectado</p>
                        <p class="text-zinc-500 text-sm">Todos os IPs estão com comportamento normal</p>
                        <p class="text-zinc-600 text-xs mt-4">IPs suspeitos aparecerão aqui quando detectados pelo sistema</p>
                    </div>
                `;
                lucide.createIcons();
                return;
            }
            
            container.innerHTML = `
                <div class="glass-card rounded-xl p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-white">${ips.length} IP${ips.length !== 1 ? 's' : ''} Suspeito${ips.length !== 1 ? 's' : ''}</h2>
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-red-500/10 border border-red-500/20">
                            <span class="w-2 h-2 rounded-full bg-red-400"></span>
                            <span class="text-xs font-bold text-red-400">Análise Própria</span>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    ${ips.map(ip => {
                        let suspicionLevel = 'Médio';
                        let suspicionColor = 'amber';
                        if (ip.suspicion_score >= 70) {
                            suspicionLevel = 'Crítico';
                            suspicionColor = 'red';
                        } else if (ip.suspicion_score >= 50) {
                            suspicionLevel = 'Alto';
                            suspicionColor = 'orange';
                        }
                        
                        let suspicionBadgeClass = 'bg-amber-500/10 text-amber-400 border-amber-500/20';
                        if (ip.suspicion_score >= 70) {
                            suspicionBadgeClass = 'bg-red-500/10 text-red-400 border-red-500/20';
                        } else if (ip.suspicion_score >= 50) {
                            suspicionBadgeClass = 'bg-orange-500/10 text-orange-400 border-orange-500/20';
                        }
                        
                        return `
                            <div class="glass-card rounded-xl p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <p class="text-lg font-mono font-bold text-white">${ip.ip_address}</p>
                                            <div class="px-2 py-1 rounded text-xs font-bold ${suspicionBadgeClass}">
                                                ${suspicionLevel}
                                            </div>
                                        </div>
                                        <p class="text-sm text-zinc-400">
                                            Suspicion Score: <span class="text-white font-bold">${ip.suspicion_score}</span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                    <div class="p-3 rounded-lg bg-zinc-900/50 border border-white/5">
                                        <p class="text-xs text-zinc-400 mb-1">Total Ataques</p>
                                        <p class="text-lg font-bold text-red-400">${ip.total_attacks || 0}</p>
                                    </div>
                                    <div class="p-3 rounded-lg bg-zinc-900/50 border border-white/5">
                                        <p class="text-xs text-zinc-400 mb-1">Tipos de Ataque</p>
                                        <p class="text-lg font-bold text-orange-400">${ip.attack_types_count || 0}</p>
                                    </div>
                                    <div class="p-3 rounded-lg bg-zinc-900/50 border border-white/5">
                                        <p class="text-xs text-zinc-400 mb-1">País</p>
                                        <p class="text-lg font-bold text-white">${ip.country_code || 'N/A'}</p>
                                    </div>
                                </div>
                                
                                ${ip.threat_types ? `
                                    <div class="mt-4 pt-4 border-t border-white/5">
                                        <p class="text-xs text-zinc-400 mb-2">Tipos de Ameaça Detectados</p>
                                        <p class="text-sm text-white font-mono">${ip.threat_types}</p>
                                    </div>
                                ` : ''}
                                
                                <div class="mt-4 pt-4 border-t border-white/5">
                                    <p class="text-xs text-zinc-400 mb-2">Última atividade</p>
                                    <p class="text-sm text-white">${new Date(ip.last_seen).toLocaleString('pt-BR')}</p>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
            
            lucide.createIcons();
        }

        // Aguardar DOM carregar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                console.log('DOM carregado, iniciando fetch...');
                fetchSuspiciousIPs();
                setInterval(fetchSuspiciousIPs, 10000);
            });
        } else {
            console.log('DOM já carregado, iniciando fetch...');
            fetchSuspiciousIPs();
            setInterval(fetchSuspiciousIPs, 10000);
        }
    </script>
    <script>
        // Inicializar Lucide Icons ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>

