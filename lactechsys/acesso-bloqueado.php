<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Esta página não requer autenticação, mas pode verificar se o usuário está bloqueado
session_start();

// Se o usuário não estiver bloqueado, redirecionar para login
if (!isset($_SESSION['user_blocked']) || $_SESSION['user_blocked'] !== true) {
    header('Location: login.php');
    exit;
}

// Limpar sessão de bloqueio se o usuário clicar em logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Bloqueado - Lagoa do Mato</title>
    <!-- Tailwind CSS CDN para um ambiente de desenvolvimento rápido -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Cores exatas da imagem original */
        .text-evercheck-green {
            color: #2ECC71; /* Verde vibrante */
        }
        .bg-evercheck-blue {
            background-color: #3498DB; /* Azul do botão original */
        }
        .text-evercheck-blue {
            color: #3498DB; /* Cor do texto do botão original */
        }
        .text-dark-gray {
            color: #333333; /* Cor do texto principal */
        }
        .text-light-gray {
            color: #666666; /* Cor do texto da mensagem */
        }
        .text-header-gray {
            color: #888888; /* Cor do texto do cabeçalho */
        }
        /* Cor para o botão do WhatsApp (verde da Evercheck) */
        .bg-whatsapp-green {
            background-color: #2ECC71;
        }
        .hover\\:bg-whatsapp-green-dark:hover {
            background-color: #27AE60; /* Um verde um pouco mais escuro para o hover */
        }
    </style>
</head>
<body class="bg-white min-h-screen flex flex-col">
    <!-- Main Content - Centralizado -->
    <main class="flex-grow flex flex-col items-center justify-center text-center p-4">
        <h1 class="text-xl font-medium text-dark-gray mb-8">Acesso Bloqueado</h1>

        <!-- Espaço para a GIF -->
        <div class="mb-8">
            <img src="403.png" alt="Acesso Bloqueado GIF" class="mx-auto" style="max-width: 400px; height: 150px; object-fit: contain;">
        </div>

        <p class="text-sm text-light-gray mt-8 max-w-xs">
            Seu acesso ao sistema LacTech foi temporariamente bloqueado. Por favor, entre em contato com o Gerente da sua Fazenda se isso não deveria ter acontecido.
        </p>

        <!-- Seção de Diagnóstico do Bloqueio -->
        <div id="blockDiagnostics" class="mt-8 p-4 bg-red-50 border border-red-200 rounded-lg max-w-md">
            <h3 class="font-semibold text-red-800 mb-3">🔍 Diagnóstico do Bloqueio</h3>
            <div id="diagnosticInfo" class="text-sm text-red-700 space-y-2">
                <div class="flex items-center">
                    <div class="w-2 h-2 bg-red-500 rounded-full mr-2"></div>
                    <span>Analisando motivo do bloqueio...</span>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="flex flex-col sm:flex-row gap-4 mt-12">
            <!-- Botão WhatsApp para Falar com o Gerente -->
            <button onclick="contactManager()" class="bg-whatsapp-green hover:bg-whatsapp-green-dark text-white font-semibold py-3 px-8 rounded-md transition-colors duration-200 flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.703"/>
                </svg>
                Falar com o Gerente
            </button>
            
            <!-- Botão para ir ao Login -->
            <button onclick="goToLogin()" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-8 rounded-md transition-colors duration-200 flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Ir para Login
            </button>
        </div>
    </main>

    <!-- Supabase CDN -->
    <script src="https://unpkg.com/@supabase/supabase-js@2"></script>
    
    <!-- JavaScript para diagnóstico e botões -->
    <script>
        // Configuração do Supabase
        const SUPABASE_URL = 'https://tmaamwuyucaspqcrhuck.supabase.co';
        const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRtYWFtd3V5dWNhc3BxY3JodWNrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3MzE2ODk4NjIsImV4cCI6MjA0NzI2NTg2Mn0.XFqXcPDDEoOSxvlU-J_Qyh_Ql8-xPkQgBYFj9n_fRcw';
        
        const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
        
        // Função para diagnóstico do bloqueio
        async function diagnosisBlockReason() {
            const diagnosticInfo = document.getElementById('diagnosticInfo');
            
            try {
                // Obter usuário atual do Supabase Auth
                const { data: { user }, error: authError } = await supabase.auth.getUser();
                
                if (authError || !user) {
                    diagnosticInfo.innerHTML = `
                        <div class="flex items-start">
                            <div class="w-2 h-2 bg-red-500 rounded-full mr-2 mt-1.5"></div>
                            <div>
                                <div class="font-medium">❌ Erro de Autenticação</div>
                                <div class="text-xs mt-1">Usuário não autenticado no Supabase</div>
                                <div class="text-xs text-red-600 mt-1">Erro: ${authError?.message || 'Sessão expirada'}</div>
                            </div>
                        </div>
                    `;
                    return;
                }
                
                // Buscar dados do usuário na tabela users
                const { data: userData, error: userError } = await supabase
                    .from('users')
                    .select('id, name, email, role, is_active, created_at, updated_at, farm_id')
                    .eq('id', user.id)
                    .single();
                
                if (userError || !userData) {
                    diagnosticInfo.innerHTML = `
                        <div class="flex items-start">
                            <div class="w-2 h-2 bg-red-500 rounded-full mr-2 mt-1.5"></div>
                            <div>
                                <div class="font-medium">❌ Usuário Não Encontrado</div>
                                <div class="text-xs mt-1">ID do Auth: ${user.id}</div>
                                <div class="text-xs text-red-600 mt-1">Erro: ${userError?.message || 'Usuário não existe na tabela users'}</div>
                            </div>
                        </div>
                    `;
                    return;
                }
                
                // Buscar solicitações de senha pendentes
                const { data: passwordRequests, error: requestError } = await supabase
                    .from('password_requests')
                    .select('id, status, created_at, reason, approved_at, rejected_at')
                    .eq('user_id', user.id)
                    .order('created_at', { ascending: false })
                    .limit(5);
                
                // Calcular tempo desde criação
                const createdAt = new Date(userData.created_at);
                const now = new Date();
                const hoursOld = Math.floor((now - createdAt) / (1000 * 60 * 60));
                
                // Verificar se é usuário novo (menos de 24h)
                const isNewUser = hoursOld < 24;
                
                // Montar diagnóstico detalhado
                let diagnosticHtml = `
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <div class="w-2 h-2 ${userData.is_active ? 'bg-red-500' : 'bg-red-600'} rounded-full mr-2 mt-1.5"></div>
                            <div>
                                <div class="font-medium">${userData.is_active ? '⚠️' : '❌'} Status da Conta</div>
                                <div class="text-xs mt-1">is_active: ${userData.is_active ? 'true' : 'false'}</div>
                                <div class="text-xs text-red-600 mt-1">${userData.is_active ? 'Conta ativa no banco, mas bloqueada pelo sistema' : 'Conta desativada no banco de dados'}</div>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-2 h-2 bg-blue-500 rounded-full mr-2 mt-1.5"></div>
                            <div>
                                <div class="font-medium">👤 Informações do Usuário</div>
                                <div class="text-xs mt-1">Nome: ${userData.name}</div>
                                <div class="text-xs">Email: ${userData.email}</div>
                                <div class="text-xs">Role: ${userData.role}</div>
                                <div class="text-xs">ID: ${userData.id}</div>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-2 h-2 ${isNewUser ? 'bg-yellow-500' : 'bg-green-500'} rounded-full mr-2 mt-1.5"></div>
                            <div>
                                <div class="font-medium">⏰ Tempo de Conta</div>
                                <div class="text-xs mt-1">Criada: ${createdAt.toLocaleString('pt-BR')}</div>
                                <div class="text-xs">Idade: ${hoursOld}h (${Math.floor(hoursOld/24)} dias)</div>
                                <div class="text-xs ${isNewUser ? 'text-yellow-600' : 'text-green-600'}">
                                    ${isNewUser ? '🆕 Conta nova (menos de 24h)' : '✅ Conta estabelecida'}
                                </div>
                            </div>
                        </div>
                `;
                
                if (passwordRequests && passwordRequests.length > 0) {
                    const pendingRequests = passwordRequests.filter(r => r.status === 'pending');
                    const recentRequest = passwordRequests[0];
                    
                    diagnosticHtml += `
                        <div class="flex items-start">
                            <div class="w-2 h-2 ${pendingRequests.length > 0 ? 'bg-orange-500' : 'bg-blue-500'} rounded-full mr-2 mt-1.5"></div>
                            <div>
                                <div class="font-medium">🔑 Solicitações de Senha</div>
                                <div class="text-xs mt-1">Total: ${passwordRequests.length}</div>
                                <div class="text-xs">Pendentes: ${pendingRequests.length}</div>
                                <div class="text-xs">Última: ${recentRequest.status} (${new Date(recentRequest.created_at).toLocaleString('pt-BR')})</div>
                                ${pendingRequests.length > 0 ? 
                                    '<div class="text-xs text-orange-600">⚠️ Solicitações pendentes podem causar bloqueio</div>' : 
                                    '<div class="text-xs text-blue-600">ℹ️ Nenhuma solicitação pendente</div>'
                                }
                            </div>
                        </div>
                    `;
                } else {
                    diagnosticHtml += `
                        <div class="flex items-start">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2 mt-1.5"></div>
                            <div>
                                <div class="font-medium">🔑 Solicitações de Senha</div>
                                <div class="text-xs mt-1 text-green-600">✅ Nenhuma solicitação encontrada</div>
                            </div>
                        </div>
                    `;
                }
                
                // Verificar dados de sessão local
                const localUserData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
                const localSession = localStorage.getItem('userSession') || sessionStorage.getItem('userSession');
                
                diagnosticHtml += `
                    <div class="flex items-start">
                        <div class="w-2 h-2 ${localUserData ? 'bg-green-500' : 'bg-red-500'} rounded-full mr-2 mt-1.5"></div>
                        <div>
                            <div class="font-medium">💾 Dados Locais</div>
                            <div class="text-xs mt-1">userData: ${localUserData ? '✅ Presente' : '❌ Ausente'}</div>
                            <div class="text-xs">userSession: ${localSession ? '✅ Presente' : '❌ Ausente'}</div>
                        </div>
                    </div>
                `;
                
                // Possíveis causas do bloqueio
                let possibleCauses = [];
                
                if (!userData.is_active) {
                    possibleCauses.push('Conta desativada no banco de dados');
                }
                
                if (isNewUser) {
                    possibleCauses.push('Usuário muito novo (possível bloqueio automático)');
                }
                
                if (passwordRequests && passwordRequests.filter(r => r.status === 'pending').length > 0) {
                    possibleCauses.push('Solicitações de senha pendentes');
                }
                
                if (!localUserData || !localSession) {
                    possibleCauses.push('Dados de sessão local corrompidos');
                }
                
                if (possibleCauses.length === 0 && userData.is_active) {
                    possibleCauses.push('Bloqueio incorreto do sistema (bug)');
                }
                
                diagnosticHtml += `
                    <div class="flex items-start">
                        <div class="w-2 h-2 bg-purple-500 rounded-full mr-2 mt-1.5"></div>
                        <div>
                            <div class="font-medium">🔍 Possíveis Causas</div>
                            ${possibleCauses.map(cause => `<div class="text-xs mt-1">• ${cause}</div>`).join('')}
                        </div>
                    </div>
                `;
                
                diagnosticHtml += `</div>`;
                diagnosticInfo.innerHTML = diagnosticHtml;
                
                // Atualizar mensagem do WhatsApp com diagnóstico
                updateWhatsAppMessage(userData, possibleCauses, isNewUser);
                
            } catch (error) {
                console.error('Erro no diagnóstico:', error);
                diagnosticInfo.innerHTML = `
                    <div class="flex items-start">
                        <div class="w-2 h-2 bg-red-500 rounded-full mr-2 mt-1.5"></div>
                        <div>
                            <div class="font-medium">❌ Erro no Diagnóstico</div>
                            <div class="text-xs mt-1 text-red-600">${error.message}</div>
                        </div>
                    </div>
                `;
            }
        }
        
        function updateWhatsAppMessage(userData, causes, isNewUser) {
            window.diagnosticData = {
                name: userData.name,
                email: userData.email,
                role: userData.role,
                isActive: userData.is_active,
                isNew: isNewUser,
                causes: causes
            };
        }
        
        function contactManager() {
            const managerPhone = '5511999999999'; // Substitua pelo número real do gerente
            
            let message = 'Olá! Meu acesso ao sistema LacTech foi bloqueado.\\n\\n';
            
            if (window.diagnosticData) {
                const data = window.diagnosticData;
                message += `📋 *DIAGNÓSTICO DO BLOQUEIO*\\n`;
                message += `👤 Nome: ${data.name}\\n`;
                message += `📧 Email: ${data.email}\\n`;
                message += `🎭 Role: ${data.role}\\n`;
                message += `✅ Ativo no BD: ${data.isActive ? 'Sim' : 'Não'}\\n`;
                message += `🆕 Conta Nova: ${data.isNew ? 'Sim (menos de 24h)' : 'Não'}\\n\\n`;
                
                if (data.causes.length > 0) {
                    message += `🔍 *POSSÍVEIS CAUSAS:*\\n`;
                    data.causes.forEach(cause => {
                        message += `• ${cause}\\n`;
                    });
                }
                
                message += '\\nPoderia me ajudar a reativar?';
            } else {
                message += 'Poderia me ajudar a reativar?';
            }
            
            const whatsappUrl = `https://wa.me/${managerPhone}?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        }
        
        function goToLogin() {
            // Limpar dados de sessão locais
            localStorage.removeItem('userData');
            sessionStorage.removeItem('userData');
            localStorage.removeItem('userSession');
            sessionStorage.removeItem('userSession');
            
            // Redirecionar para login
            window.location.href = 'login.php';
        }
        
        // Executar diagnóstico ao carregar a página
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(diagnosisBlockReason, 1000);
        });
    </script>
</body>
</html>
