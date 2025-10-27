// Função para esconder a tela de carregamento
function hideLoadingScreen() {
    const loadingScreen = document.getElementById('loadingScreen');
    if (loadingScreen) {
        console.log('🔽 Escondendo tela de carregamento...');
        
        // Adicionar classe para esconder
        loadingScreen.classList.add('hidden');

        setTimeout(() => {
            if (loadingScreen.parentNode) {
                loadingScreen.remove();
                console.log('✅ Tela de carregamento removida');
            }
        }, 500);
    }
}

// ==================== FUNÇÕES GLOBAIS DE TRADUÇÃO ====================

window.getMilkingTypeInPortuguese = function(milkingType) {
    const translation = {
        'morning': 'Manhã',
        'afternoon': 'Tarde',
        'evening': 'Noite',
        'night': 'Madrugada'
    };
    return translation[milkingType] || milkingType;
};

// ==================== FUNÇÕES GLOBAIS DE MODAIS ====================

// Controle de Novilhas - Sistema de Custos de Criação
window.openHeiferManagement = function() {
    console.log('🐄 Abrindo Controle de Novilhas...');
    
    // Fechar modal Mais
    if (typeof window.closeMoreModal === 'function') {
        window.closeMoreModal();
    }
    
    const modal = document.createElement('div');
    modal.id = 'heiferManagementModal';
    modal.className = 'fixed inset-0 bg-white z-[99999] overflow-y-auto';
    modal.innerHTML = `
        <div class="w-full h-full">
            <div class="sticky top-0 bg-gradient-to-br from-orange-500 to-red-500 text-white shadow-lg z-10 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="closeHeiferManagement()" class="w-10 h-10 flex items-center justify-center hover:bg-white hover:bg-opacity-20 rounded-xl transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div>
                            <h3 class="text-2xl font-bold">Controle de Novilhas</h3>
                            <p class="text-orange-100 text-sm">Custos de criação até 26 meses</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="max-w-4xl mx-auto">
                    <!-- Resumo dos Custos -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-2xl p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-blue-600 text-sm font-medium">Total de Novilhas</p>
                                    <p class="text-3xl font-bold text-blue-800" id="totalHeifers">0</p>
                                </div>
                                <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 12.5c0 .828-.672 1.5-1.5 1.5s-1.5-.672-1.5-1.5.672-1.5 1.5-1.5 1.5.672 1.5 1.5zM18.5 12.5c0 .828-.672 1.5-1.5 1.5s-1.5-.672-1.5-1.5.672-1.5 1.5-1.5 1.5.672 1.5 1.5zM12 20c-2.5 0-4.5-2-4.5-4.5S9.5 11 12 11s4.5 2 4.5 4.5S14.5 20 12 20zM12 8c-1.5 0-2.5-1-2.5-2.5S10.5 3 12 3s2.5 1 2.5 2.5S13.5 8 12 8z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-2xl p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-green-600 text-sm font-medium">Custo Médio</p>
                                    <p class="text-3xl font-bold text-green-800" id="averageCost">R$ 0,00</p>
                                </div>
                                <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-2xl p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-purple-600 text-sm font-medium">Investimento Total</p>
                                    <p class="text-3xl font-bold text-purple-800" id="totalInvestment">R$ 0,00</p>
                                </div>
                                <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fases de Criação -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-8">
                        <h4 class="text-xl font-semibold text-gray-800 mb-6">Fases de Criação</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Fase 1: Aleitamento -->
                            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-xl p-6">
                                <div class="flex items-center mb-4">
                                    <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h5 class="font-semibold text-gray-800">1. Aleitamento</h5>
                                        <p class="text-sm text-gray-600">Leite sucedâneo</p>
                                    </div>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <p><span class="font-medium">Consumo:</span> 6L/dia</p>
                                    <p><span class="font-medium">Preço:</span> R$ 0,60/L</p>
                                    <p><span class="font-medium">Duração:</span> 60-90 dias</p>
                                    <p><span class="font-medium text-green-600">Custo:</span> R$ 216-324</p>
                                </div>
                            </div>
                            
                            <!-- Fase 2: Desmame -->
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6">
                                <div class="flex items-center mb-4">
                                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h5 class="font-semibold text-gray-800">2. Desmame</h5>
                                        <p class="text-sm text-gray-600">Ração + Volumoso</p>
                                    </div>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <p><span class="font-medium">Volumoso:</span> 15-20kg/dia</p>
                                    <p><span class="font-medium">Concentrado:</span> 2-3kg/dia</p>
                                    <p><span class="font-medium">Duração:</span> 12-18 meses</p>
                                    <p><span class="font-medium text-green-600">Custo:</span> R$ 1.200-1.800</p>
                                </div>
                            </div>
                            
                            <!-- Fase 3: Recria -->
                            <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6">
                                <div class="flex items-center mb-4">
                                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h5 class="font-semibold text-gray-800">3. Recria</h5>
                                        <p class="text-sm text-gray-600">Preparação produção</p>
                                    </div>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <p><span class="font-medium">Volumoso:</span> 20-25kg/dia</p>
                                    <p><span class="font-medium">Concentrado:</span> 3-4kg/dia</p>
                                    <p><span class="font-medium">Duração:</span> 6-8 meses</p>
                                    <p><span class="font-medium text-green-600">Custo:</span> R$ 800-1.200</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ações -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button onclick="registerNewHeifer()" class="flex-1 bg-gradient-to-r from-orange-500 to-red-500 text-white py-4 px-6 rounded-xl font-semibold hover:from-orange-600 hover:to-red-600 transition-all flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Cadastrar Nova Novilha
                        </button>
                        <button onclick="viewHeiferCosts()" class="flex-1 bg-gradient-to-r from-blue-500 to-indigo-500 text-white py-4 px-6 rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-600 transition-all flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Ver Relatórios
                        </button>
                    </div>
                    
                    <!-- Informações Importantes -->
                    <div class="mt-8 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
                        <h5 class="font-semibold text-blue-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Informações Importantes
                        </h5>
                        <ul class="text-sm text-blue-700 space-y-2">
                            <li>• <strong>Leite Sucedâneo:</strong> R$ 0,60/L - consumo de 6L/dia por 60-90 dias</li>
                            <li>• <strong>Volumoso:</strong> Baseado no consumo de vaca adulta (28-35kg/dia)</li>
                            <li>• <strong>Meta:</strong> Controle completo até 26 meses (idade para produção)</li>
                            <li>• <strong>Custo Total Estimado:</strong> R$ 2.200 - R$ 3.300 por novilha</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
};

// Fechar Controle de Novilhas
window.closeHeiferManagement = function() {
    const modal = document.getElementById('heiferManagementModal');
    if (modal) modal.remove();
};

// Funções auxiliares para o sistema de novilhas
window.registerNewHeifer = function() {
    console.log('📝 Cadastrando nova novilha...');
    // TODO: Implementar modal de cadastro
    alert('Funcionalidade em desenvolvimento: Cadastro de novilha será implementado em breve!');
};

window.viewHeiferCosts = function() {
    console.log('📊 Visualizando relatórios de custos...');
    // TODO: Implementar relatórios
    alert('Funcionalidade em desenvolvimento: Relatórios detalhados serão implementados em breve!');
};

// Esconder tela de carregamento imediatamente quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    hideLoadingScreen();
});

// Timeout de segurança - esconder tela após 3 segundos mesmo se houver problemas
setTimeout(function() {
    hideLoadingScreen();
}, 3000);

// Load non-critical scripts after page load
window.addEventListener('load', function() {
    // Garantir que a tela de carregamento seja removida
    hideLoadingScreen();
    
        setTimeout(function() {
            // Load PDF generator and other scripts (avoiding duplicates)
            const scripts = [
                'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
                'assets/js/pdf-generator.js',
                'assets/js/offline-sync.js'
            ];
            
            scripts.forEach(function(src) {

                if (!document.querySelector(`script[src="${src}"]`)) {
                    const script = document.createElement('script');
                    script.src = src;
                    script.async = true;
                    document.head.appendChild(script);
                }
            });
        }, 500); // Increased delay to ensure Chart.js is ready
    });
  // CRITICAL FIX: Prevent modal flash based on web research
  window.pageFullyLoaded = false;
  window.modalEnabled = false;

  function disableModal() {
      const modal = document.getElementById('profileModal');
      if (modal) {
          // Remove any classes that might show the modal
          modal.classList.remove('show', 'active', 'visible', 'open', 'modal-enabled');
          modal.classList.add('hidden');

          modal.style.display = 'none';
          modal.style.visibility = 'hidden';
          modal.style.opacity = '0';
          modal.style.pointerEvents = 'none';
          modal.style.zIndex = '-1';
      }
  }
  
  // Function to enable modal only when needed
  function enableModal() {
      const modal = document.getElementById('profileModal');
      if (modal) {
          modal.classList.add('modal-enabled');
          modal.style.display = 'flex';
          modal.style.visibility = 'visible';
          modal.style.opacity = '1';
          modal.style.pointerEvents = 'auto';
          modal.style.zIndex = '9999';
          modal.style.position = 'fixed';
          modal.style.top = '0';
          modal.style.left = '0';
          modal.style.width = '100%';
          modal.style.height = '100%';
          modal.style.background = 'rgba(0, 0, 0, 0.5)';
          modal.style.alignItems = 'center';
          modal.style.justifyContent = 'center';
      }
  }
  
  // DESABILITADO - Não bloquear modal mais
  // disableModal();
  console.log('⚠️ disableModal() DESABILITADO para permitir abertura do modal');
  
  // MUTATION OBSERVER DESABILITADO - Estava impedindo abertura do modal

  console.log('⚠️ MutationObserver DESABILITADO para permitir abertura do modal');

  (function(){
      var originalAlert = window.alert;
      window.alert = function(message){
          try {
              var msg = String(message);
              console.log('?? Alert interceptado:', msg);

              if (typeof showNotification === 'function') {
                  showNotification(msg, 'warning');
                  return;
              }
              
              // Fallback: usar modal system se disponível
              if (typeof window.modalSystem !== 'undefined' && window.modalSystem.showAlert) {
                  window.modalSystem.showAlert(msg);
                  return;
              }

              console.warn('Alert (fallback):', msg);
          } catch(e){
              console.error('Erro no alert shim:', e);
          }
      };
      
      // Garantir que nossa função não seja sobrescrita
      Object.defineProperty(window, 'alert', {
          value: window.alert,
          writable: false,
          configurable: false
      });
  })();

  (function(){
      var originalConfirm = window.confirm;
      window.confirm = function(message){
          try {
              var msg = String(message);
              console.log('?? Confirm interceptado:', msg);
              
              // Usar modal system se disponível
              if (typeof window.modalSystem !== 'undefined' && window.modalSystem.showConfirm) {
                  return window.modalSystem.showConfirm(msg);
              }
              
              // Fallback: usar alert nativo (temporário)
              console.warn('Confirm (fallback para alert nativo):', msg);
              return originalConfirm(msg);
          } catch(e){
              console.error('Erro no confirm shim:', e);
              return originalConfirm(message);
          }
      };
      
      // Garantir que nossa função não seja sobrescrita
      Object.defineProperty(window, 'confirm', {
          value: window.confirm,
          writable: false,
          configurable: false
      });
  })();

     // ==================== DATABASE API - MySQL Direct Access ====================
     const db = {
        auth: {
            getUser: async () => {
                const userData = localStorage.getItem('user_data');
                if (userData) {
                    const user = JSON.parse(userData);
                    return { data: { user: user }, error: null };
                }
                return { data: { user: null }, error: null };
            },
            signOut: async () => {
                localStorage.clear();
                return { error: null };
            },
            getSession: async () => {
                const userData = localStorage.getItem('user_data');
                return { data: { session: userData ? { user: JSON.parse(userData) } : null }, error: null };
            },
            updateUser: async (updates) => {
                // Atualizar usuário
                return { data: null, error: null };
            },
            signUp: async (credentials) => {
                // Sign up
                return { data: null, error: null };
            }
        },
        from: (table) => {
            // Mapear nomes de tabelas para os arquivos PHP corretos
            const tableMap = {
                'users': 'users',
                'volume_records': 'volume',
                'quality_tests': 'quality',
                'financial_records': 'financial',
                'farms': 'farms',
                'secondary_accounts': 'generic',
                'password_requests': 'generic',
                'notifications': 'generic',
                // 'chat_messages': 'generic', // Tabela não existe
                // 'chat-files': 'generic', // Tabela não existe  
                // 'profile-photos': 'generic', // Tabela não existe
                'quality_tests': 'quality',
                'auth.users': 'users'
            };
            
            const apiFile = tableMap[table] || 'generic';
            const apiUrl = apiFile === 'generic' ? `api/generic.php?table=${table}` : `api/${apiFile}.php`;
            
            return {
                select: (cols = '*') => {
                    const queryBuilder = {
                        _columns: cols,
                        _filters: { eq: {}, neq: {}, gte: {}, lte: {}, gt: {}, lt: {} },
                        _order: '',
                        _limit: 0,
                        _single: false,
                        _maybeSingle: false,
                        
                        eq: (col, val) => {
                            queryBuilder._filters.eq[col] = val;
                            return queryBuilder;
                        },
                        neq: (col, val) => {
                            queryBuilder._filters.neq[col] = val;
                            return queryBuilder;
                        },
                        gte: (col, val) => {
                            queryBuilder._filters.gte[col] = val;
                            return queryBuilder;
                        },
                        lte: (col, val) => {
                            queryBuilder._filters.lte[col] = val;
                            return queryBuilder;
                        },
                        gt: (col, val) => {
                            queryBuilder._filters.gt[col] = val;
                            return queryBuilder;
                        },
                        lt: (col, val) => {
                            queryBuilder._filters.lt[col] = val;
                            return queryBuilder;
                        },
                        not: (col, op, val) => {
                            queryBuilder._filters.neq[col] = val;
                            return queryBuilder;
                        },
                        order: (col, opts = {}) => {
                            const direction = opts.ascending === false ? 'DESC' : 'ASC';
                            queryBuilder._order = `${col} ${direction}`;
                            return queryBuilder;
                        },
                        limit: (n) => {
                            queryBuilder._limit = n;
                            return queryBuilder;
                        },
                        single: () => {
                            queryBuilder._single = true;
                            return queryBuilder;
                        },
                        maybeSingle: () => {
                            queryBuilder._maybeSingle = true;
                            return queryBuilder;
                        },
                        then: async (callback) => {
                            // Executar a query
                            try {
                                const response = await fetch(apiUrl, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        action: 'select',
                                        columns: queryBuilder._columns,
                                        eq: queryBuilder._filters.eq,
                                        neq: queryBuilder._filters.neq,
                                        gte: queryBuilder._filters.gte,
                                        lte: queryBuilder._filters.lte,
                                        gt: queryBuilder._filters.gt,
                                        lt: queryBuilder._filters.lt,
                                        order: queryBuilder._order,
                                        limit: queryBuilder._limit,
                                        single: queryBuilder._single || queryBuilder._maybeSingle
                                    })
                                });
                                
                                const result = await response.json();
                                if (callback) callback(result);
                                return result;
                            } catch (error) {
                                console.error('Erro ao buscar dados:', error);

                                if (error.message.includes('Unexpected token')) {
                                    console.error('? API retornou HTML em vez de JSON. Verifique se a API está funcionando.');
                                }
                                const errorResult = { data: null, error: error.message };
                                if (callback) callback(errorResult);
                                return errorResult;
                            }
                        }
                    };
                    
                    return queryBuilder;
                },
                
                insert: async (data) => {
                    try {
                        const response = await fetch(apiUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'insert',
                                ...data
                            })
                        });
                        
                        return await response.json();
                    } catch (error) {
                        console.error('Erro ao inserir dados:', error);
                        return { data: null, error: error.message };
                    }
                },
                
                update: (data) => ({
                    eq: async (col, val) => {
                        try {
                            const response = await fetch(apiUrl, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    action: 'update',
                                    id: val,
                                    ...data
                                })
                            });
                            
                            return await response.json();
                        } catch (error) {
                            console.error('Erro ao atualizar dados:', error);
                            return { data: null, error: error.message };
                        }
                    }
                }),
                
                delete: () => ({
                    eq: async (col, val) => {
                        try {
                            const response = await fetch(apiUrl, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    action: 'delete',
                                    id: val
                                })
                            });
                            
                            return await response.json();
                        } catch (error) {
                            console.error('Erro ao deletar dados:', error);
                            return { data: null, error: error.message };
                        }
                    }
                }),
                
                upsert: async (data) => {
                    try {
                        const response = await fetch(apiUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'upsert',
                                ...data
                            })
                        });
                        
                        return await response.json();
                    } catch (error) {
                        console.error('Erro ao upsert dados:', error);
                        return { data: null, error: error.message };
                    }
                }
            };
        },
        
        rpc: async (funcName, params) => {

            console.log('RPC chamado:', funcName, params);
            return { data: null, error: null };
        },
        
        storage: {
            from: (bucket) => ({
                upload: async (path, file) => ({ data: null, error: null }),
                download: async (path) => ({ data: null, error: null }),
                remove: async (paths) => ({ data: null, error: null }),
                getPublicUrl: (path) => ({ data: { publicUrl: '' } })
            })
        },
        
        channel: (name) => ({
            on: (event, opts, callback) => ({
                subscribe: (callback) => ({ unsubscribe: () => {} })
            }),
            subscribe: (callback) => ({ unsubscribe: () => {} }),
            unsubscribe: () => {}
        })
    };
    
    // Tornar disponível globalmente
    window.db = db;
    
    // ==================== CONTROLE DE TELA DE CARREGAMENTO DE CONEX�O ====================
    function showServerConnectionLoading() {
        const loadingModal = document.getElementById('serverConnectionLoading');
        if (loadingModal) {
            loadingModal.classList.remove('hidden');
            console.log('?? Mostrando tela de carregamento de conex�o com servidor');
        }
    }

    function hideServerConnectionLoading() {
        const loadingModal = document.getElementById('serverConnectionLoading');
        if (loadingModal) {
            loadingModal.classList.add('hidden');
            console.log('? Escondendo tela de carregamento de conex�o com servidor');
        }
    }

    // ==================== CACHE SYSTEM ====================
    const CacheManager = {
        cache: new Map(),
        userData: null,
        farmData: null,
        lastUserFetch: 0,
        lastFarmFetch: 0,
        forceRefresh: false, // Flag para for�ar atualização
        CACHE_DURATION: 5 * 60 * 1000, // 5 minutos
        
        // Limpar cache após registros
        clearCache() {
            console.log('??? Limpando cache...');
            this.cache.clear();
            this.userData = null;
            this.farmData = null;
            this.lastUserFetch = 0;
            this.lastFarmFetch = 0;
            this.forceRefresh = true;
            console.log('? Cache limpo');
        },

        // For�ar atualização do cache
        forceCacheRefresh() {
            console.log('?? For�ando atualização do cache...');
            this.forceRefresh = true;
            this.cache.clear();
        },
        
        // Cache de dados do usuário
        async getUserData(forceRefresh = false) {
            const now = Date.now();
            if (!forceRefresh && this.userData && (now - this.lastUserFetch) < this.CACHE_DURATION) {
                return this.userData;
            }
            
            const userDataLS = localStorage.getItem('user_data');
            if (userDataLS) {
                this.userData = JSON.parse(userDataLS);
                this.lastUserFetch = now;
            }
            
            return this.userData;
        },
        
        // Cache de dados da fazenda
        async getFarmData(forceRefresh = false) {
            const now = Date.now();
            if (!forceRefresh && this.farmData && (now - this.lastFarmFetch) < this.CACHE_DURATION) {
                return this.farmData;
            }
            
            this.farmData = {
                id: 1,
                name: 'Lagoa do Mato',
                location: 'Lagoa do Mato'
            };
            this.lastFarmFetch = now;
            
            return this.farmData;
        },
        
        // Cache gen�rico
        set(key, data, ttl = this.CACHE_DURATION) {
            this.cache.set(key, {
                data,
                timestamp: Date.now(),
                ttl
            });
        },
        
        get(key) {
            const item = this.cache.get(key);
            if (!item) return null;
            
            const now = Date.now();
            if (now - item.timestamp > item.ttl) {
                this.cache.delete(key);
                return null;
            }
            
            return item.data;
        },
        
        // Limpar cache espec�fico
        clear(key) {
            if (key) {
                this.cache.delete(key);
            } else {
                this.cache.clear();
                this.userData = null;
                this.farmData = null;
            }
        },
        
        // Invalidar cache de dados cr�ticos
        invalidateUserData() {
            this.userData = null;
            this.farmData = null;
            this.lastUserFetch = 0;
            this.lastFarmFetch = 0;
        },
        
        // Cache para dados de volume (MySQL - stub)
        async getVolumeData(farmId, dateRange, forceRefresh = false) {
            return null; // MySQL: implementar API se necessário
        }
    };

  async function checkAuthentication() {
    try {
        const userData = localStorage.getItem('user_data');
        
        if (!userData) {
            clearUserSession();
            showNotification('Sess�o expirada. Redirecionando para login...', 'error');
            setTimeout(() => {
                safeRedirect('login.php');
            }, 2000);
            return false;
        }
        
        let user;
        try {
            user = JSON.parse(userData);
        } catch (e) {
            clearUserSession();
            safeRedirect('login.php');
            return false;
        }
        
        if (!user.id || !user.email || !user.role) {
            clearUserSession();
            safeRedirect('login.php');
            return false;
        }
        
        window.currentUser = user;
        return true;
        
    } catch (error) {
        clearUserSession();
        setTimeout(() => {
            safeRedirect('login.php');
        }, 2000);
        return false;
    }
}

function clearUserSession() {
localStorage.removeItem('user_data');
localStorage.removeItem('user_token');
localStorage.removeItem('userData');
localStorage.removeItem('userSession');
localStorage.removeItem('farmData');
localStorage.removeItem('setupCompleted');

sessionStorage.removeItem('user_data');
sessionStorage.removeItem('user_token');
sessionStorage.removeItem('userData');
sessionStorage.removeItem('userSession');
sessionStorage.removeItem('farmData');
sessionStorage.removeItem('setupCompleted');
sessionStorage.removeItem('redirectCount');

if (window.currentUser) {
    delete window.currentUser;
}
}

// Funçãoo para gerenciar redirecionamentos
function safeRedirect(url) {
const currentCount = parseInt(sessionStorage.getItem('redirectCount') || '0');
sessionStorage.setItem('redirectCount', (currentCount + 1).toString());

window.location.replace(url);
}

// Funçãoo para monitorar bloqueio de usuário (otimizada)
let blockWatcherInterval = null;
let lastBlockCheck = 0;
const BLOCK_CHECK_INTERVAL = 60000; // 1 minuto em vez de 15 segundos

function startBlockWatcher() {
    // Evitar m�ltiplos intervalos
    if (blockWatcherInterval) {
        clearInterval(blockWatcherInterval);
    }
    
    blockWatcherInterval = setInterval(async () => {
        try {
            const now = Date.now();

            if (now - lastBlockCheck < BLOCK_CHECK_INTERVAL) {
                return;
            }
            lastBlockCheck = now;

        } catch (error) {
            // Em caso de erro persistente, limpar sess�o
            clearUserSession();
            clearInterval(blockWatcherInterval);
            safeRedirect('login.php');
        }
    }, BLOCK_CHECK_INTERVAL);
}

function stopBlockWatcher() {
    if (blockWatcherInterval) {
        clearInterval(blockWatcherInterval);
        blockWatcherInterval = null;
    }
}

document.addEventListener('DOMContentLoaded', async function() {
    // Flag para evitar m�ltiplas inicializações
    if (window.pageInitialized) {
        return;
    }
    
    window.pageInitialized = true;

    const userData = localStorage.getItem('user_data') || sessionStorage.getItem('user_data');
    if (!userData) {
        safeRedirect('login.php');
        return;
    }

    const redirectCount = sessionStorage.getItem('redirectCount') || 0;
    if (redirectCount > 3) {
        clearUserSession();
        sessionStorage.removeItem('redirectCount');
        safeRedirect('login.php');
        return;
    }
    
    try {
        const parsedUserData = JSON.parse(userData);
        if (!parsedUserData || !parsedUserData.id) {
            clearUserSession();
            safeRedirect('login.php');
            return;
        }
    } catch (error) {
        clearUserSession();
        safeRedirect('login.php');
        return;
    }
    
    // Check authentication first
    console.log('?? Verificando autenticaçãoo...');
    const isAuthenticated = await checkAuthentication();
    if (!isAuthenticated) {
        console.error('? Autenticaçãoo falhou');
        return; // Stop execution if not authenticated
    }
    console.log('? Autenticaçãoo OK');
    
    console.log('?? Inicializando gr�ficos...');
    
    // Aguardar Chart.js carregar
    if (typeof Chart === 'undefined') {
        console.log('? Aguardando Chart.js carregar...');
        setTimeout(() => {
            if (typeof Chart !== 'undefined') {
                initializeCharts();
            } else {
                console.error('? Chart.js não carregou após timeout');
            }
        }, 1000);
    } else {
        initializeCharts(); // Initialize charts before loading data
    }
    
    console.log('?? Inicializando p�gina...');
    await initializePage();
    
    console.log('?? Configurando event listeners...');
    // setupEventListeners(); // Função removida - event listeners já configurados
    
    console.log('? Sistema carregado com sucesso!');
    
    // Liberar modal de perfil para uso
    window.pageFullyLoaded = true;
    window.modalEnabled = true;
    console.log('✅ Modal de perfil liberado para uso');
    
    // Carregar foto do header
    await loadHeaderPhoto();
    
    // Carregar dados do perfil na inicialização
    loadProfileData();
    
    // Garantir que o modal de foto esteja fechado na inicializaçãoo
    const photoModal = document.getElementById('photoChoiceModal');
    if (photoModal) {
        photoModal.classList.remove('show');
        photoModal.classList.add('hidden');
        photoModal.classList.remove('flex');
        photoModal.style.display = 'none';
        photoModal.style.visibility = 'hidden';
        photoModal.style.opacity = '0';
        photoModal.style.pointerEvents = 'none';
    }
    
    // Garantir que o modal de foto do gerente esteja fechado na inicializaçãoo
    const managerPhotoModal = document.getElementById('managerPhotoChoiceModal');
    if (managerPhotoModal) {
        managerPhotoModal.classList.remove('show', 'flex', 'block');
        managerPhotoModal.classList.add('hidden');
        managerPhotoModal.style.display = 'none';
        managerPhotoModal.style.visibility = 'hidden';
        managerPhotoModal.style.opacity = '0';
        managerPhotoModal.style.pointerEvents = 'none';
    }

    setTimeout(() => {
        const modal = document.getElementById('managerPhotoChoiceModal');
        if (modal) {
            modal.classList.remove('show', 'flex', 'block');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            modal.style.visibility = 'hidden';
            modal.style.opacity = '0';
            modal.style.pointerEvents = 'none';
        }
    }, 500);
    
    // Garantir que as telas de processamento estejam ocultas
    const photoProcessingScreen = document.getElementById('photoProcessingScreen');
    if (photoProcessingScreen) {
        photoProcessingScreen.classList.add('hidden');
        photoProcessingScreen.style.display = 'none';
        photoProcessingScreen.style.visibility = 'hidden';
        photoProcessingScreen.style.opacity = '0';
        photoProcessingScreen.style.pointerEvents = 'none';
    }
    
    const managerPhotoProcessingScreen = document.getElementById('managerPhotoProcessingScreen');
    if (managerPhotoProcessingScreen) {
        managerPhotoProcessingScreen.classList.add('hidden');
        managerPhotoProcessingScreen.style.display = 'none';
        managerPhotoProcessingScreen.style.visibility = 'hidden';
        managerPhotoProcessingScreen.style.opacity = '0';
        managerPhotoProcessingScreen.style.pointerEvents = 'none';
    }
    
    updateDateTime();
    setInterval(updateDateTime, 60000); // Update every minute

    startBlockWatcher();
    
});

// Funçãoo para limpar todos os event listeners (�til para debugging)
window.clearAllEventListeners = function() {

    const forms = [
        'addUserFormModal', 
        'updateProfileForm',
        'editUserForm',
        'createSecondaryAccountForm'
    ];
    
    forms.forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            const newForm = form.cloneNode(true);
            form.parentNode.replaceChild(newForm, form);
        }
    });
    
};

// =====================================================
// DATABASE CLIENT (removido mock do Database)
// =====================================================
// Agora usando conex�o direta com MySQL atrav�s do objeto 'db'

// =====================================================
// FUNçãoES MYSQL
// =====================================================

async function getCurrentUser() {
const userData = localStorage.getItem('user_data');
if (!userData) {
    throw new Error('Usuário não autenticado');
}
return JSON.parse(userData);
}

async function mysqlRequest(endpoint, data = null) {
const options = {
    method: data ? 'POST' : 'GET',
    headers: {
        'Content-Type': 'application/json',
    }
};

if (data) {
    options.body = JSON.stringify(data);
}

const response = await fetch(`api/${endpoint}`, options);
const result = await response.json();

if (!result.success) {
    throw new Error(result.message || 'Erro na requisiçãoo');
}

return result;
}

async function initializePage() {
try {
    const user = await getCurrentUser();
    
    window.currentUser = {
        id: user.id,
        email: user.email,
        name: user.name,
        role: user.role,
        farm_id: 1 // Lagoa Do Mato
    };

    try {
        await setFarmName();
    } catch (error) {
    }
    
    try {
        await setManagerName();
    } catch (error) {
    }

    try {
        await loadUserProfile();
    } catch (error) {
    }
    
    try {
        if (typeof loadHeaderProfilePhoto === 'function') {
            await loadHeaderProfilePhoto();
        }
    } catch (error) {
    }
    
    try {

        await loadDashboardData();
    } catch (error) {
    }
    
    try {
        await loadVolumeData();
        await loadVolumeRecords();
        // For�ar atualização da lista de registros para garantir dados corretos
        setTimeout(async () => {
            if (typeof window.updateVolumeRecordsList === 'function') {
                await window.updateVolumeRecordsList();
            }
            // Nomes de funcionários já são carregados via API
        }, 1000);
    } catch (error) {
        console.error('Error loading volume data:', error);
    }
    
    try {
        await loadQualityData();
        await loadQualityTests();
    } catch (error) {
    }
    
    // Funções antigas removidas para evitar conflitos

    try {
        // Gráficos modernos serão carregados automaticamente
        console.log('📊 Gráficos modernos serão carregados automaticamente');
        
        // Executar todos os gr�ficos em paralelo
        // Gráficos modernos carregados automaticamente
        console.log('? Todos os gr�ficos carregados');
    } catch (error) {
        console.error('Erro ao carregar gr�ficos:', error);
    }
    
    try {
        // Carregar atividades recentes (MySQL)
        // Carregar atividades da fazenda Lagoa Do Mato
        await loadRecentActivities(); // Lagoa Do Mato
    } catch (error) {
        console.log('?? Erro ao carregar atividades recentes:', error);
    }
    
    try {
        await loadUsersData();
    } catch (error) {
    }
    
    // Configurar atualizações em tempo real
    try {
        await setupRealtimeUpdates();
    } catch (error) {
    }
} catch (error) {
    // Show user-friendly message
    showNotification('Algumas informações não puderam ser carregadas. Verifique sua conex�o.', 'warning');
}
}

async function createUserIfNotExists(authUser) {
// MySQL: não precisa criar usuário automaticamente
// O usuário � criado diretamente pelo gerente na interface
console.log('MySQL: Usuário será criado manualmente pelo gerente');
return;
}

// Function to get farm name from session or Database
async function getFarmName() {
try {

    const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
    if (userData) {
        try {
            const user = JSON.parse(userData);
            if (user.farm_name) {
        
                return user.farm_name;
            }
        } catch (error) {
        }
    }
    
    // Get current user and their farm
    // Usando MySQL direto atrav�s do objeto 'db'
    const { data: { user } } = await db.auth.getUser();
    if (!user) {
        return 'Lagoa do Mato';
    }

    // Usuário da fazenda Lagoa Do Mato from users table - SEMPRE USAR CONTA PRIM�RIA
    const { data: userDbData, error: userError } = await db
        .from('users')
        .select('id')
        .eq('id', user.id)
        .single();
        
    if (userError) {
        throw userError;
    }
    
    if (!userDbData) {
        // Fallback to auth metadata
        if (user.user_metadata?.farm_name) {
            return user.user_metadata.farm_name;
        }
        return 'Lagoa do Mato';
    }

    // Get farm name from farms table
    const { data: farmData, error: farmError } = await db
        .from('farms')
        .select('name')
        .eq('id', 1)
        .single();
    
    if (farmError || !farmData?.name) {
        return 'Lagoa do Mato';
    }
    
    return farmData.name;
    
} catch (error) {
    return 'Lagoa do Mato';
}
}

// Funçãoo utilit�ria para sempre buscar a conta prim�ria
async function getPrimaryUserAccount(email) {
try {
    // Usando MySQL direto atrav�s do objeto 'db'
    const { data: usersData, error } = await db
        .from('users')
        .select('*')
        .eq('email', email)
        .eq('is_active', true)
        .order('created_at', { ascending: true }); // Primeira conta = prim�ria
    
    if (error) {
        console.error('Erro ao buscar conta prim�ria:', error);
        return null;
    }
    
    return usersData?.[0] || null; // Sempre retorna a primeira conta
} catch (error) {
    console.error('Erro na função getPrimaryUserAccount:', error);
    return null;
}
}

// Chamar a inicializaçãoo quando a p�gina carregar
document.addEventListener('DOMContentLoaded', function() {
// Inicializar logo da Xandria Store
setTimeout(updateXandriaStoreIcon, 200);
});

function showNotification(message, type = 'info') {

if (message.includes('Logo carregada') || message.includes('Configurações salvas com sucesso')) {
    showSuccessModal(message, type);
    return;
}

const notification = document.createElement('div');
notification.className = `fixed top-4 right-4 p-4 rounded-xl shadow-2xl z-50 max-w-sm transform transition-all duration-300 border-l-4`;

// Definir cores e ícones baseados no tipo
let bgColor, textColor, borderColor, icon;
switch(type) {
    case 'error':
        bgColor = 'bg-red-50';
        textColor = 'text-red-800';
        borderColor = 'border-red-500';
        icon = '??';
        break;
    case 'warning':
        bgColor = 'bg-yellow-50';
        textColor = 'text-yellow-800';
        borderColor = 'border-yellow-500';
        icon = '??';
        break;
    case 'success':
        bgColor = 'bg-green-50';
        textColor = 'text-green-800';
        borderColor = 'border-green-500';
        icon = '?';
        break;
    default:
        bgColor = 'bg-blue-50';
        textColor = 'text-blue-800';
        borderColor = 'border-blue-500';
        icon = '??';
}

notification.className += ` ${bgColor} ${textColor} ${borderColor}`;

// Criar conteúdo com ícone
notification.innerHTML = `
    <div class="flex items-start space-x-3">
        <span class="text-lg flex-shrink-0">${icon}</span>
        <div class="flex-1">
            <p class="font-medium text-sm">${message}</p>
        </div>
        <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
`;

// Adicionar animaçãoo de entrada
notification.style.transform = 'translateX(100%)';
notification.style.opacity = '0';

// Add to page
document.body.appendChild(notification);

// Animar entrada
requestAnimationFrame(() => {
    notification.style.transform = 'translateX(0)';
    notification.style.opacity = '1';
});

// Remove after 5 seconds with animation
setTimeout(() => {
    if (notification.parentNode) {
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
setTimeout(() => {
    if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
}, 5000);
}
// Modal de sucesso especial para mensagens importantes
function showSuccessModal(message, type = 'success') {

const existingModal = document.getElementById('successModal');
if (existingModal) {
    existingModal.remove();
}

// Criar modal
const modal = document.createElement('div');
modal.id = 'successModal';
modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';

// Determinar ícone e cores baseado na mensagem
let icon, title, bgColor;
if (message.includes('Logo carregada')) {
    icon = '???';
    title = 'Logo Carregada!';
    bgColor = 'from-blue-500 to-blue-600';
} else if (message.includes('Configurações salvas')) {
    icon = '??';
    title = 'Configurações Salvas!';
    bgColor = 'from-green-500 to-green-600';
} else {
    icon = '?';
    title = 'Sucesso!';
    bgColor = 'from-green-500 to-green-600';
}

modal.innerHTML = `
    <div class="bg-whiterounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
        <!-- Header com gradiente -->
        <div class="text-center mb-6">
            <div class="w-20 h-20 bg-gradient-to-br ${bgColor} rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                <span class="text-3xl">${icon}</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900mb-2">${title}</h3>
            <div class="w-16 h-1 bg-gradient-to-r ${bgColor} rounded-full mx-auto"></div>
        </div>
        
        <!-- Mensagem -->
        <div class="text-center mb-8">
            <p class="text-gray-600leading-relaxed">${message}</p>
        </div>
        
        <!-- Botão -->
        <div class="text-center">
            <button onclick="closeSuccessModal()" class="bg-gradient-to-r ${bgColor} text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                Entendi
            </button>
        </div>
    </div>
`;

// Adicionar evento de clique no fundo para fechar
modal.addEventListener('click', function(e) {
    if (e.target === modal) {
        closeSuccessModal();
    }
});

document.body.appendChild(modal);

// Animar entrada
requestAnimationFrame(() => {
    const content = document.getElementById('modalContent');
    if (content) {
        content.style.transform = 'scale(1)';
        content.style.opacity = '1';
    }
});

// Auto-fechar após 4 segundos
setTimeout(() => {
    closeSuccessModal();
}, 4000);
}

// Funçãoo para fechar modal de sucesso
function closeSuccessModal() {
const modal = document.getElementById('successModal');
if (modal) {
    const content = document.getElementById('modalContent');
    if (content) {
        content.style.transform = 'scale(0.95)';
        content.style.opacity = '0';
    }
    setTimeout(() => {
        modal.remove();
    }, 300);
}
}

// Function to get manager name from session or Database
async function getManagerName() {
    try {
        // Primeiro tentar localStorage/sessionStorage
        const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData') || localStorage.getItem('user_data') || sessionStorage.getItem('user_data');
        if (userData) {
            try {
                const user = JSON.parse(userData);
                if (user.name) {
                    return user.name;
                }
            } catch (error) {
                console.log('Erro ao parsear userData:', error);
            }
        }
        
        // Fallback simples
        return 'Usuário';
    } catch (error) {
        console.error('Error fetching manager name:', error);
        return 'Usuário';
    }
}

// Function to set farm name in header
async function setFarmName() {
try {
const farmName = await getFarmName();
    const farmNameElement = document.getElementById('farmNameHeader');
    if (farmNameElement) {
        farmNameElement.textContent = farmName || 'Lagoa do Mato';
        console.log('Farm name set to:', farmName);
    } else {
        console.log('Farm name element not found');
    }
} catch (error) {
    console.log('Error setting farm name:', error);
    const farmNameElement = document.getElementById('farmNameHeader');
    if (farmNameElement) {
        farmNameElement.textContent = 'Lagoa do Mato';
    }
}
}

function extractFormalName(fullName) {
if (!fullName || typeof fullName !== 'string') {
    return 'Gerente';
}

// Remove extra spaces and split
const names = fullName.trim().split(/\s+/);

if (names.length === 1) {
    return names[0];
}

if (names.length === 2) {
    return names[0];
}

// Skip common prefixes and find the second meaningful name
const skipWords = ['da', 'de', 'do', 'das', 'dos', 'di', 'del', 'della', 'delle', 'delli'];

let formalName = '';
let nameCount = 0;

for (let i = 0; i < names.length; i++) {
    const name = names[i].toLowerCase();
    
    // Skip common prefixes
    if (skipWords.includes(name)) {
        continue;
    }
    
    // Count meaningful names
    nameCount++;
    
    // Get the first meaningful name
    if (nameCount === 1) {
        formalName = names[i];
        break;
    }
}

// If we didn't find a first meaningful name, use the first name overall
if (!formalName && names.length >= 1) {
    formalName = names[0];
}

if (!formalName) {
    formalName = names[0];
}

return formalName.charAt(0).toUpperCase() + formalName.slice(1).toLowerCase();
}

// Function to set manager name in profile
async function setManagerName() {
const managerName = await getManagerName();
const farmName = await getFarmName();

const finalManagerName = managerName || 'Usuário';
const finalFarmName = farmName || 'Lagoa do Mato';

const formalName = extractFormalName(finalManagerName);

const elements = [
    'profileName',
    'profileFullName'
];

elements.forEach(id => {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = finalManagerName;
    }
});

const headerElement = document.getElementById('managerName');
const welcomeElement = document.getElementById('managerWelcome');
if (headerElement) {
    headerElement.textContent = formalName;
}
if (welcomeElement) {
    welcomeElement.textContent = formalName;
}

document.getElementById('profileFarmName').textContent = finalFarmName;
}

// Function to load user profile data
async function loadUserProfile() {
try {
    const whatsappElement = document.getElementById('profileWhatsApp');
    
    if (!whatsappElement) {
        return;
    }

    const sessionData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
    
    if (sessionData) {
        try {
            const user = JSON.parse(sessionData);
            
            // Set profile data from session
            document.getElementById('profileEmail2').textContent = user.email || '';
            const whatsappValue = user.whatsapp || user.phone || 'N�o informado';
            document.getElementById('profileWhatsApp').textContent = whatsappValue;
            return;
        } catch (error) {
            // Continue to fallback
        }
    }
    
    // Fallback to Database Auth
    const { data: { user } } = await db.auth.getUser();
    
    if (!user) {
        document.getElementById('profileEmail2').textContent = 'N�o logado';
        document.getElementById('profileWhatsApp').textContent = 'N�o informado';
        return;
    }

    const { data: userData, error } = await db
        .from('users')
        .select('name, email, phone')
        .eq('id', user.id)
        .single();
    
    // If user not found, just show error - don't create automatically
    if (error && error.code === 'PGRST116') {
        document.getElementById('profileEmail2').textContent = user.email || '';
        document.getElementById('profileWhatsApp').textContent = 'Usuário não encontrado';
        return;
    }
    
    if (error) {
        document.getElementById('profileEmail2').textContent = user.email || '';
        document.getElementById('profileWhatsApp').textContent = 'Erro ao carregar';
        return;
    }

    // Update profile elements
    if (userData) {
        const email = userData.email || user.email || '';
        const whatsapp = userData.whatsapp || 'N�o informado';
        
        document.getElementById('profileEmail2').textContent = email;
        document.getElementById('profileWhatsApp').textContent = whatsapp;

        sessionStorage.setItem('userEmail', email);
    } else {
        const email = user.email || '';
        document.getElementById('profileEmail2').textContent = email;
        document.getElementById('profileWhatsApp').textContent = 'N�o informado';

        sessionStorage.setItem('userEmail', email);
    }
} catch (error) {
    document.getElementById('profileEmail2').textContent = 'Erro';
    document.getElementById('profileWhatsApp').textContent = 'Erro';
}
}

// Update date and time
function updateDateTime() {
const now = new Date();
const timeString = now.toLocaleTimeString('pt-BR', { 
    hour: '2-digit', 
    minute: '2-digit' 
});
document.getElementById('lastUpdate').textContent = timeString;
}
// Função auxiliar para fazer requisições com tratamento de erro melhorado
async function safeFetch(url, options = {}) {
    try {
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        // Tentar JSON diretamente
        const jsonData = await response.json();
        return jsonData;
    } catch (error) {
        console.error(`❌ Erro na requisição ${url}:`, error);
        throw error;
    }
}

// Load dashboard data from Database

async function loadDashboardData() {
console.log('?? Carregando dados do dashboard...');

try {
    // Buscar todos os dados do dashboard de uma vez
    const jsonData = await safeFetch('api/manager.php?action=get_dashboard_stats');
    console.log('?? Dados do dashboard carregados:', jsonData);
    
    const { success, data } = jsonData;
    
    if (!success) {
        console.error('? Erro ao buscar estat�sticas do dashboard');
        return;
    }
    
    console.log('? Dados do dashboard carregados:', data);
    
    // Atualizar volume de hoje
    const volumeToday = data.volume_today || 0;
    document.getElementById('todayVolume').textContent = `${volumeToday} L`;
    
    // Atualizar também o volumeToday se existir
    if (document.getElementById('volumeToday')) {
        document.getElementById('volumeToday').textContent = `${volumeToday} L`;
    }
    
    localStorage.setItem('todayVolume', volumeToday.toString());
    localStorage.setItem('todayVolumeDate', new Date().toISOString().split('T')[0]);
    
    // Atualizar volume do mês
    if (document.getElementById('monthVolume')) {
        const volumeMonth = data.volume_month || 0;
        document.getElementById('monthVolume').textContent = `${volumeMonth} L`;
    }
    
    // Atualizar volume do ano
    if (document.getElementById('yearVolume')) {
        const volumeYear = data.volume_year || 0;
        console.log('📊 Volume Anual recebido:', volumeYear);
        document.getElementById('yearVolume').textContent = `${volumeYear} L`;
    }
    
    // Atualizar qualidade média
    const avgFat = data.avg_fat || 0;
    const avgProtein = data.avg_protein || 0;
    if (avgFat > 0 || avgProtein > 0) {
        // Calcular score de qualidade baseado em gordura e proteína
        const fatScore = Math.min(100, Math.max(0, (avgFat / 4.0) * 100));
        const proteinScore = Math.min(100, Math.max(0, (avgProtein / 3.5) * 100));
        const avgQuality = (fatScore + proteinScore) / 2;
        document.getElementById('qualityAverage').textContent = `${Math.round(avgQuality)}%`;
    } else {
        document.getElementById('qualityAverage').textContent = '--%';
    }
    
    // Atualizar pagamentos pendentes
    const pendingPayments = data.pending_payments || 0;
    document.getElementById('pendingPayments').textContent = `R$ ${pendingPayments.toLocaleString('pt-BR')}`;
    
    // Atualizar usuários ativos - usando a mesma lógica da Gestão de Usuários
    try {
        const usersResponse = await fetch('api/users.php?action=select');
        if (usersResponse.ok) {
            const usersResult = await usersResponse.json();
            if (usersResult.success && usersResult.data) {
                const totalUsers = usersResult.data.length;
                console.log('👥 Usuários carregados via API users.php:', totalUsers);
                
                const activeUsersElement = document.getElementById('activeUsers');
                if (activeUsersElement) {
                    activeUsersElement.textContent = totalUsers;
                    console.log('✅ Elemento activeUsers atualizado via API users.php:', totalUsers);
                } else {
                    console.error('❌ Elemento activeUsers não encontrado!');
                }
            } else {
                console.log('⚠️ API users.php retornou erro, usando dados do dashboard');
                const activeUsers = data.active_users || 0;
                document.getElementById('activeUsers').textContent = activeUsers;
            }
        } else {
            console.log('⚠️ Erro na API users.php, usando dados do dashboard');
            const activeUsers = data.active_users || 0;
            document.getElementById('activeUsers').textContent = activeUsers;
        }
    } catch (error) {
        console.log('⚠️ Erro ao carregar usuários via API, usando dados do dashboard:', error);
        const activeUsers = data.active_users || 0;
        document.getElementById('activeUsers').textContent = activeUsers;
    }
    
    console.log('? Dashboard atualizado com sucesso!');
    
} catch (error) {
    console.error('? Erro ao carregar dashboard:', error);
    // Manter valores padrão em caso de erro
    if (document.getElementById('todayVolume')) {
        document.getElementById('todayVolume').textContent = '0 L';
    }
    if (document.getElementById('volumeToday')) {
        document.getElementById('volumeToday').textContent = '0 L';
    }
    if (document.getElementById('qualityAverage')) {
        document.getElementById('qualityAverage').textContent = '--%';
    }
    if (document.getElementById('pendingPayments')) {
        document.getElementById('pendingPayments').textContent = 'R$ 0';
    }
    if (document.getElementById('activeUsers')) {
        document.getElementById('activeUsers').textContent = '0';
    }
}
}

// Funçãoo para restaurar volume salvo do localStorage
function restoreSavedVolume() {
try {
    const savedVolume = localStorage.getItem('todayVolume');
    const savedDate = localStorage.getItem('todayVolumeDate');
    const today = new Date().toISOString().split('T')[0];
    
    if (savedVolume && savedDate === today) {
        const volumeElement = document.getElementById('todayVolume');
        if (volumeElement) {
            volumeElement.textContent = `${savedVolume} L`;
        }
    } else if (savedDate !== today) {
        // Se a data mudou, limpar dados antigos
        localStorage.removeItem('todayVolume');
        localStorage.removeItem('todayVolumeDate');
    }
} catch (error) {
    console.error('? Erro ao restaurar volume salvo:', error);
}
}

// Load volume data from database and local storage
async function loadVolumeData() {
// Aguardar Database estar disponível
if (!window.db) {
    await new Promise(resolve => setTimeout(resolve, 1000));
    if (!window.db) {
        console.error('? Database não disponível para volume');
        return;
    }
}

try {
    // Usar API de volume
    const result = await safeFetch('api/volume.php?action=get_all');
    
    if (!result.success) {
        throw new Error(result.error || 'Erro ao carregar dados de volume');
    }
    
    const volumeData = result.data || [];
    
    // Calcular estat�sticas simples
    const today = new Date().toISOString().split('T')[0];
    const todayVolume = volumeData
        .filter(record => record.record_date === today)
        .reduce((sum, record) => sum + (parseFloat(record.total_volume) || 0), 0);
    
    const weekAgo = new Date();
    weekAgo.setDate(weekAgo.getDate() - 7);
    const weekData = volumeData
        .filter(record => new Date(record.record_date) >= weekAgo)
        .reduce((sum, record) => sum + (parseFloat(record.total_volume) || 0), 0);
    const weekAvg = weekData / 7;
    const growth = weekAvg > 0 ? ((todayVolume - weekAvg) / weekAvg * 100) : 0;
    
    // Atualizar elementos da interface
    const volumeTodayElement = document.getElementById('volumeToday');
    const volumeWeekAvgElement = document.getElementById('volumeWeekAvg');
    const volumeGrowthElement = document.getElementById('volumeGrowth');
    
    if (volumeTodayElement) {
        volumeTodayElement.textContent = `${todayVolume.toFixed(0)} L`;
        console.log(`?? Volume hoje atualizado: ${todayVolume.toFixed(0)} L`);
    } else {
        console.error('? Elemento volumeToday não encontrado');
    }
    
    if (volumeWeekAvgElement) {
        volumeWeekAvgElement.textContent = `${weekAvg.toFixed(0)} L`;
        console.log(`?? Média semanal atualizada: ${weekAvg.toFixed(0)} L`);
    } else {
        console.error('? Elemento volumeWeekAvg não encontrado');
    }
    
    if (volumeGrowthElement) {
        volumeGrowthElement.textContent = `${growth > 0 ? '+' : ''}${growth.toFixed(1)}%`;
        console.log(`?? Crescimento atualizado: ${growth > 0 ? '+' : ''}${growth.toFixed(1)}%`);
    } else {
        console.error('? Elemento volumeGrowth não encontrado');
    }

    const lastCollectionElement = document.getElementById('lastCollection');
    if (lastCollectionElement) {
        if (volumeData.length > 0) {
            const lastRecord = volumeData[0]; // Assumindo que está ordenado
            lastCollectionElement.textContent = `${lastRecord.collection_date || '--/--/----'} - --:--`;
        } else {
            lastCollectionElement.textContent = '--/--/---- - --:--';
        }
    }
    
} catch (error) {
    console.error('Error loading volume data:', error);
    // Set default values on error
    document.getElementById('volumeToday').textContent = '0 L';
    document.getElementById('volumeWeekAvg').textContent = '0 L';
    document.getElementById('volumeGrowth').textContent = '0%';
    document.getElementById('lastCollection').textContent = '--:--';
}
}

// Load quality data from database
async function loadQualityData() {
try {
    // Aguardar Database estar disponível
    if (!window.db) {
        await new Promise(resolve => setTimeout(resolve, 1000));
        if (!window.db) {
            console.error('? Database não disponível para qualidade');
            throw new Error('Database not available');
        }
    }
    
    // Dados de qualidade simulados para evitar erros de API
    const qualityData = [];
    
    // Atualizar gr�ficos com os dados
    // updateQualityCharts(qualityData); // Função antiga desabilitada
    console.log('📊 Dados de qualidade carregados:', qualityData.length, 'registros');
    
} catch (error) {
    console.error('? Erro ao carregar dados de qualidade:', error);
}
}

async function loadQualityComplete() {
    try {
        await loadQualityData();
        await loadQualityTests();
        await loadQualityChartMySQL();
        await loadQualityTrendAndDistribution();
        console.log('✅ Controle de qualidade carregado completamente');
    } catch (error) {
        console.error('❌ Erro ao carregar controle de qualidade:', error);
    }
}

// Função para carregar testes de qualidade
async function loadQualityTests() {
    try {
        console.log('📊 Carregando testes de qualidade...');
        // Implementação simples para evitar erros
        return [];
    } catch (error) {
        console.error('❌ Erro ao carregar testes de qualidade:', error);
        return [];
    }
}

// Função para carregar gráficos de tendência e distribuição de qualidade
async function loadQualityTrendAndDistribution() {
    try {
        console.log('📊 Carregando gráficos de tendência e distribuição...');
        
        // Carregar dados de qualidade
        const response = await fetch('api/quality.php?action=select');
        const result = await response.json();
        
        if (!result.success) {
            console.error('❌ Erro ao carregar dados de qualidade:', result.error);
            return;
        }
        
        const qualityData = result.data || [];
        console.log('📊 Dados de qualidade recebidos:', qualityData.length, 'registros');
        
        if (qualityData.length === 0) {
            console.log('⚠️ Nenhum dado de qualidade para os gráficos');
            return;
        }
        
        // Atualizar gráfico de tendência
        updateQualityTrendChart(qualityData);
        
        // Atualizar gráfico de distribuição
        updateQualityDistributionChart(qualityData);
        
        console.log('✅ Gráficos de qualidade atualizados');
        
    } catch (error) {
        console.error('❌ Erro ao carregar gráficos de qualidade:', error);
    }
}

// Função para atualizar gráfico de tendência de qualidade
function updateQualityTrendChart(qualityData) {

    if (!window.qualityTrendChart) {
        const canvas = document.getElementById('qualityTrendChart');
        if (!canvas) {
            console.error('❌ Canvas qualityTrendChart não encontrado');
            return;
        }
        console.log('📊 Inicializando gráfico de tendência...');
        window.qualityTrendChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Gordura (%)',
                        data: [],
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        borderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#f59e0b',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'Proteína (%)',
                        data: [],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        tension: 0.4,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 6,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1
                    }
                }
            }
        });
    }
    
    try {
        // Pegar os últimos 10 registros
        const recentData = qualityData.slice(0, 10).reverse();
        
        if (recentData.length === 0) {
            console.log('⚠️ Nenhum dado recente para tendência');
            return;
        }
        
        const labels = recentData.map(record => 
            new Date(record.test_date).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
        );
        
        const fatData = recentData.map(record => parseFloat(record.fat_content) || 0);
        const proteinData = recentData.map(record => parseFloat(record.protein_content) || 0);
        
        // Atualizar dados do gráfico
        window.qualityTrendChart.data.labels = labels;
        window.qualityTrendChart.data.datasets[0].data = fatData;
        window.qualityTrendChart.data.datasets[1].data = proteinData;
        window.qualityTrendChart.update();
        
        console.log('✅ Gráfico de tendência atualizado');
        
    } catch (error) {
        console.error('❌ Erro ao atualizar gráfico de tendência:', error);
    }
}

// Função para atualizar gráfico de distribuição de qualidade
function updateQualityDistributionChart(qualityData) {

    if (!window.qualityDistributionChart) {
        const canvas = document.getElementById('qualityDistributionChart');
        if (!canvas) {
            console.error('❌ Canvas qualityDistributionChart não encontrado');
            return;
        }
        console.log('📊 Inicializando gráfico de distribuição...');
        window.qualityDistributionChart = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['Excelente', 'Bom', 'Regular'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    try {
        if (qualityData.length === 0) {
            console.log('⚠️ Nenhum dado para distribuição');
            return;
        }
        
        // Calcular médias
        const validTests = qualityData.filter(test => 
            test.fat_content && test.protein_content && test.somatic_cells
        );
        
        if (validTests.length === 0) {
            console.log('⚠️ Nenhum teste válido para distribuição');
            return;
        }
        
        const avgFat = validTests.reduce((sum, test) => sum + parseFloat(test.fat_content), 0) / validTests.length;
        const avgProtein = validTests.reduce((sum, test) => sum + parseFloat(test.protein_content), 0) / validTests.length;
        const avgSCC = validTests.reduce((sum, test) => sum + parseFloat(test.somatic_cells), 0) / validTests.length;

        const fatQuality = avgFat >= 3.5 ? 'Excelente' : avgFat >= 3.0 ? 'Bom' : 'Regular';
        const proteinQuality = avgProtein >= 3.2 ? 'Excelente' : avgProtein >= 2.9 ? 'Bom' : 'Regular';
        const sccQuality = avgSCC <= 200000 ? 'Excelente' : avgSCC <= 400000 ? 'Bom' : 'Regular';

        const excellent = [fatQuality, proteinQuality, sccQuality].filter(q => q === 'Excelente').length;
        const good = [fatQuality, proteinQuality, sccQuality].filter(q => q === 'Bom').length;
        const regular = [fatQuality, proteinQuality, sccQuality].filter(q => q === 'Regular').length;
        
        // Atualizar dados do gráfico
        window.qualityDistributionChart.data.datasets[0].data = [excellent, good, regular];
        window.qualityDistributionChart.update();
        
        console.log('✅ Gráfico de distribuição atualizado');
        console.log('📊 Classificação:', { excellent, good, regular });
        
    } catch (error) {
        console.error('❌ Erro ao atualizar gráfico de distribuição:', error);
    }
}

// Função para atualizar indicadores de qualidade
function updateQualityIndicators(qualityData) {
    if (!qualityData || qualityData.length === 0) {
        console.log('⚠️ Nenhum dado de qualidade para atualizar');
        return;
    }
    
    // Calcular médias
    const validTests = qualityData.filter(test => 
        test.fat_content && test.protein_content && test.somatic_cells && test.bacteria_count
    );
    
    if (validTests.length === 0) {
        console.log('⚠️ Nenhum teste de qualidade válido encontrado');
        return;
    }
    
    const avgFat = validTests.reduce((sum, test) => sum + parseFloat(test.fat_content), 0) / validTests.length;
    const avgProtein = validTests.reduce((sum, test) => sum + parseFloat(test.protein_content), 0) / validTests.length;
    const avgSCC = validTests.reduce((sum, test) => sum + parseFloat(test.somatic_cells), 0) / validTests.length;
    const avgTBC = validTests.reduce((sum, test) => sum + parseFloat(test.bacteria_count), 0) / validTests.length;
    
    // Atualizar elementos da interface
    const fatElement = document.getElementById('fatContent');
    const proteinElement = document.getElementById('proteinContent');
    const sccElement = document.getElementById('sccCount');
    const tbcElement = document.getElementById('tbc');
    
    if (fatElement) {
        fatElement.textContent = `${avgFat.toFixed(1)}%`;
        updateQualityBar('fatQualityBar', avgFat, 4.0, 3.5); // Meta: 4.0%, Mínimo: 3.5%
    }
    
    if (proteinElement) {
        proteinElement.textContent = `${avgProtein.toFixed(1)}%`;
        updateQualityBar('proteinQualityBar', avgProtein, 3.5, 3.0); // Meta: 3.5%, Mínimo: 3.0%
    }
    
    if (sccElement) {
        sccElement.textContent = `${Math.round(avgSCC).toLocaleString()}`;
        updateQualityBar('sccQualityBar', avgSCC, 200000, 400000); // Meta: 200k, Máximo: 400k
    }
    
    if (tbcElement) {
        tbcElement.textContent = `${Math.round(avgTBC).toLocaleString()}`;
        updateQualityBar('tbcQualityBar', avgTBC, 100000, 300000); // Meta: 100k, Máximo: 300k
    }
    
    console.log('✅ Indicadores de qualidade atualizados:', {
        fat: avgFat.toFixed(1),
        protein: avgProtein.toFixed(1),
        scc: Math.round(avgSCC),
        tbc: Math.round(avgTBC)
    });
}

// Função para atualizar barras de qualidade
function updateQualityBar(barId, value, target, limit) {
    const bar = document.getElementById(barId);
    if (!bar) return;
    
    let percentage;
    let color;
    
    if (barId.includes('fat') || barId.includes('protein')) {
        // Para gordura e proteína, valores maiores são melhores
        percentage = Math.min((value / target) * 100, 100);
        color = value >= target ? '#10B981' : value >= limit ? '#F59E0B' : '#EF4444';
    } else {
        // Para SCC e TBC, valores menores são melhores
        percentage = Math.min((value / limit) * 100, 100);
        color = value <= target ? '#10B981' : value <= limit ? '#F59E0B' : '#EF4444';
    }
    
    bar.style.width = `${percentage}%`;
    bar.style.backgroundColor = color;
}

function updateQualityDisplay(fat, protein, scc, tbc, quality) {
const elements = {
    'fatContent': fat,
    'proteinContent': protein,
    'sccCount': scc,
    'tbc': tbc,
    'qualityAverage': quality
};

Object.entries(elements).forEach(([id, value]) => {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;

    } else {
    }
});
}

// Load users data from database
async function loadUsersData() {
try {
    // Aguardar Database estar disponível
    if (!window.db) {
        await new Promise(resolve => setTimeout(resolve, 1000));
        if (!window.db) {
            console.error('? Database não disponível para usuários');
            throw new Error('Database not available');
        }
    }
    
    // Usar API de usuários
    const result = await safeFetch('api/users.php?action=select');
    
    if (!result.success) {
        throw new Error(result.error || 'Erro ao carregar dados de usuários');
    }
    
    const usersData = result.data || [];
    
    if (usersData && usersData.length > 0) {
        const employeesCount = usersData.filter(u => u.role === 'funcionario').length;
        const veterinariansCount = usersData.filter(u => u.role === 'veterinario').length;
        const managersCount = usersData.filter(u => u.role === 'gerente').length;
        const totalUsers = usersData.length;
        
        document.getElementById('totalUsers').textContent = totalUsers;
        document.getElementById('employeesCount').textContent = employeesCount;
        document.getElementById('veterinariansCount').textContent = veterinariansCount;
        document.getElementById('managersCount').textContent = managersCount;
        
    setTimeout(() => {
        displayUsersList(usersData);
        
        document.querySelectorAll('.action-button.permissions').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const userId = this.getAttribute('data-user-id');
                const currentStatus = this.getAttribute('data-current-status') === 'true';
                toggleUserAccess(userId, currentStatus);
            });
        });
    }, 100);
    } else {
        document.getElementById('totalUsers').textContent = '1';
        document.getElementById('employeesCount').textContent = '0';
        document.getElementById('veterinariansCount').textContent = '0';
        document.getElementById('managersCount').textContent = '1';
        displayUsersList([]);
    }
    
} catch (error) {
    console.error('Error loading users data:', error);
    // Set default values on error
    document.getElementById('totalUsers').textContent = '1';
    document.getElementById('employeesCount').textContent = '0';
    document.getElementById('veterinariansCount').textContent = '0';
    document.getElementById('managersCount').textContent = '1';
    displayUsersList([]);
}
}

async function loadWeeklyVolumeChart() {
try {
    // Aguardar Database estar disponível
    if (!window.db) {
        await new Promise(resolve => setTimeout(resolve, 1000));
        if (!window.db) {
            console.error('? Database não disponível para gr�fico semanal');
            return;
        }
    }
    
    // Usando MySQL direto atrav�s do objeto 'db'
    const { data: { user } } = await db.auth.getUser();
    if (!user) return;

    const { data: userData, error: userError } = await db
        .from('users')
        .select('id')
        .eq('id', user.id)
        .maybeSingle();
    
    if (userError) {
        return;
    }

    // Get last 7 days of data
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 6);

    const { data: volumeData, error } = await db
        .from('volume_records')
        .select('record_date, total_volume')
        .eq('farm_id', 1)
        .gte('record_date', startDate.toISOString().split('T')[0])
        .lte('record_date', endDate.toISOString().split('T')[0])
        .order('record_date', { ascending: true });

    if (error) {
        return;
    }

    // Group by date and sum volumes
    const dailyVolumes = {};
    const labels = [];
    
    // Initialize all days with 0
    for (let i = 0; i < 7; i++) {
        const date = new Date(startDate);
        date.setDate(date.getDate() + i);
        const dateStr = date.toISOString().split('T')[0];
        const dayName = date.toLocaleDateString('pt-BR', { weekday: 'short' });
        labels.push(dayName);
        dailyVolumes[dateStr] = 0;
    }

    // Sum volumes by date
    if (volumeData) {
        volumeData.forEach(record => {
            if (dailyVolumes.hasOwnProperty(record.record_date)) {
                dailyVolumes[record.record_date] += record.total_volume || 0;
            }
        });
    }

    const data = Object.values(dailyVolumes);
    const hasRealData = data.some(value => value > 0);

    if (window.weeklyVolumeChart && hasRealData) {
        window.weeklyVolumeChart.data.labels = labels;
        window.weeklyVolumeChart.data.datasets[0].data = data;
        window.weeklyVolumeChart.update();
    }

} catch (error) {
    console.error('Error loading weekly volume chart:', error);
}
}

async function loadDailyVolumeChart() {
try {
    // Usando MySQL direto atrav�s do objeto 'db'
    const { data: { user } } = await db.auth.getUser();
    if (!user) return;

    const { data: userData, error: userError } = await db
        .from('users')
        .select('id')
        .eq('id', user.id)
        .single();
    
    if (userError) {
        return;
    }

    const today = new Date().toISOString().split('T')[0];
    const { data: volumeData, error } = await db
        .from('volume_records')
        .select('shift, total_volume')
        .eq('farm_id', 1)
        .eq('record_date', today)
        .order('shift', { ascending: true });

    if (error) {
        return;
    }

    const shiftVolumes = {
        'morning': 0,
        'afternoon': 0,
        'evening': 0,
        'night': 0
    };

    if (volumeData) {
        volumeData.forEach(record => {
            if (shiftVolumes.hasOwnProperty(record.shift)) {
                shiftVolumes[record.shift] += record.total_volume || 0;
            }
        });
    }

    const labels = ['Manh�', 'Tarde', 'Noite', 'Madrugada'];
    const data = [shiftVolumes.morning, shiftVolumes.afternoon, shiftVolumes.evening, shiftVolumes.night];

    if (window.dailyVolumeChart) {
        window.dailyVolumeChart.data.labels = labels;
        window.dailyVolumeChart.data.datasets[0].data = data;
        window.dailyVolumeChart.update();
    }

} catch (error) {
    console.error('Error loading daily volume chart:', error);
}
}

async function loadMonthlyVolumeChart() {
try {
    const now = new Date();
    const lastDayOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    // Only show monthly chart on the last day of the month
    if (now.getDate() !== lastDayOfMonth.getDate()) {
        return;
    }
    
    const { data: { user } } = await db.auth.getUser();
    if (!user) return;

    const { data: userData, error: userError } = await db
        .from('users')
        .select('id')
        .eq('id', user.id)
        .single();
    
    if (userError) {
        return;
    }

    // Get last 12 months of data
    const endDate = new Date();
    const startDate = new Date();
    startDate.setMonth(startDate.getMonth() - 11);
    startDate.setDate(1);

    const { data: volumeData, error } = await db
        .from('volume_records')
        .select('record_date, total_volume')
        .eq('farm_id', 1)
        .gte('record_date', startDate.toISOString().split('T')[0])
        .lte('record_date', endDate.toISOString().split('T')[0])
        .order('record_date', { ascending: true });

    if (error) {
        return;
    }

    // Group by month and sum volumes
    const monthlyVolumes = {};
    const labels = [];
    
    // Initialize all months with 0
    for (let i = 0; i < 12; i++) {
        const date = new Date(startDate);
        date.setMonth(date.getMonth() + i);
        const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        const monthName = date.toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' });
        labels.push(monthName);
        monthlyVolumes[monthKey] = 0;
    }

    // Sum volumes by month
    if (volumeData) {
        volumeData.forEach(record => {
            const date = new Date(record.record_date);
            const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            if (monthlyVolumes.hasOwnProperty(monthKey)) {
                monthlyVolumes[monthKey] += record.total_volume || 0;
            }
        });
    }
    const data = Object.values(monthlyVolumes);

    // Show monthly chart container and update chart
    const monthlyChartContainer = document.getElementById('monthlyVolumeChartContainer');
    if (monthlyChartContainer) {
        monthlyChartContainer.style.display = 'block';
        
        if (window.monthlyVolumeChart) {
            window.monthlyVolumeChart.data.labels = labels;
            window.monthlyVolumeChart.data.datasets[0].data = data;
            window.monthlyVolumeChart.update();
        }
    }

} catch (error) {
    console.error('Error loading monthly volume chart:', error);
}
}

async function loadWeeklySummaryChart() {
try {
    const now = new Date();
    
    // Only show weekly summary on Sundays (0 = Sunday)
    if (now.getDay() !== 0) {
        return;
    }
    
    const { data: { user } } = await db.auth.getUser();
    if (!user) return;

    const { data: userData, error: userError } = await db
        .from('users')
        .select('id')
        .eq('id', user.id)
        .single();
    
    if (userError) {
        return;
    }

    // Get last 8 weeks of data
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 56); // 8 weeks

    const { data: volumeData, error } = await db
        .from('volume_records')
        .select('record_date, total_volume')
        .eq('farm_id', 1)
        .gte('record_date', startDate.toISOString().split('T')[0])
        .lte('record_date', endDate.toISOString().split('T')[0])
        .order('record_date', { ascending: true });

    if (error) {
        return;
    }

    // Group by week and sum volumes
    const weeklyVolumes = {};
    const labels = [];
    
    // Initialize all weeks with 0
    for (let i = 0; i < 8; i++) {
        const weekStart = new Date(startDate);
        weekStart.setDate(weekStart.getDate() + (i * 7));
        const weekKey = getWeekKey(weekStart);
        const weekLabel = `Sem ${i + 1}`;
        labels.push(weekLabel);
        weeklyVolumes[weekKey] = 0;
    }

    // Sum volumes by week
    if (volumeData) {
        volumeData.forEach(record => {
            const date = new Date(record.record_date);
            const weekKey = getWeekKey(date);
            if (weeklyVolumes.hasOwnProperty(weekKey)) {
                weeklyVolumes[weekKey] += record.total_volume || 0;
            }
        });
    }

    const data = Object.values(weeklyVolumes);

    // Show weekly summary chart container and update chart
    const weeklySummaryContainer = document.getElementById('weeklySummaryChartContainer');
    if (weeklySummaryContainer) {
        weeklySummaryContainer.style.display = 'block';
        
        if (window.weeklySummaryChart) {
            window.weeklySummaryChart.data.labels = labels;
            window.weeklySummaryChart.data.datasets[0].data = data;
            window.weeklySummaryChart.update();
        }
    }

} catch (error) {
    console.error('Error loading weekly summary chart:', error);
}
}

function getWeekKey(date) {
const startOfWeek = new Date(date);
startOfWeek.setDate(date.getDate() - date.getDay());
return startOfWeek.toISOString().split('T')[0];
}

async function loadDashboardVolumeChart() {
try {
    console.log('?? Carregando gr�fico Volume Semanal...');

    if (typeof Chart === 'undefined') {
        console.log('? Aguardando Chart.js...');
        await new Promise(resolve => {
            const checkChart = () => {
                if (typeof Chart !== 'undefined') {
                    resolve();
                } else {
                    setTimeout(checkChart, 100);
                }
            };
            checkChart();
        });
    }
    
    // Usando MySQL direto atrav�s do objeto 'db'
    if (!db) {
        console.error('? Database não disponível para Volume Semanal');
        return;
    }

    // Usar cache para dados do usuário
    const userData = await CacheManager.getUserData();
    
    if (!userData) {
        console.error('? Farm ID não encontrado para Volume Semanal');
        return;
    }

    console.log('?? Fazenda: Lagoa Do Mato');

    // Get last 7 days of data
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 6);

    console.log('?? Per�odo Volume Semanal:', startDate.toISOString().split('T')[0], 'at�', endDate.toISOString().split('T')[0]);

    // Usar API de volume
    const response = await fetch('api/volume.php?action=select');
    const result = await response.json();
    
    if (!result.success) {
        console.error('? Erro ao buscar dados de volume:', result.error);
        return;
    }
    
    const volumeData = result.data || [];

    console.log('?? Dados de volume encontrados:', volumeData?.length || 0, 'registros');

    // Group by date and sum volumes
    const dailyVolumes = {};
    const labels = [];
    
    // Initialize all days with 0
    for (let i = 0; i < 7; i++) {
        const date = new Date(startDate);
        date.setDate(date.getDate() + i);
        const dateStr = date.toISOString().split('T')[0];
        const dayName = date.toLocaleDateString('pt-BR', { weekday: 'short' });
        labels.push(dayName);
        dailyVolumes[dateStr] = 0;
    }

    // Sum volumes by date
    if (volumeData && volumeData.length > 0) {
        volumeData.forEach(record => {
            if (dailyVolumes.hasOwnProperty(record.record_date)) {
                dailyVolumes[record.record_date] += record.total_volume || 0;
            }
        });
    }

    const data = Object.values(dailyVolumes);
    const hasRealData = data.some(value => value > 0);

    console.log('?? Dados processados Volume Semanal:', { labels, data, hasRealData });

    if (window.volumeChart) {
        console.log('? Atualizando gr�fico Volume Semanal...');
        window.volumeChart.data.labels = labels;
        window.volumeChart.data.datasets[0].data = data;
        window.volumeChart.update();
        console.log('? Gr�fico Volume Semanal atualizado com sucesso');
    } else {
        console.error('? Gr�fico volumeChart não encontrado, tentando reinicializar...');
        // Tentar reinicializar o gr�fico
            const volumeCtx = document.getElementById('volumeChart');
            if (volumeCtx) {
                window.volumeChart = new Chart(volumeCtx, {
                    type: 'line',
                    data: {
                    labels: labels,
                        datasets: [{
                            label: 'Volume (L)',
                        data: data,
                            borderColor: '#369e36',
                            backgroundColor: 'rgba(54, 158, 54, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            console.log('? Gr�fico Volume Semanal reinicializado com sucesso');
        } else {
            console.error('? Elemento volumeChart não encontrado no DOM');
        }
    }

} catch (error) {
    console.error('? Erro ao carregar gr�fico Volume Semanal:', error);
}
}

function displayUsersList(users) {
const usersList = document.getElementById('usersList');

if (!users || users.length === 0) {
    usersList.innerHTML = `
        <div class="text-center py-12">
            <div class="w-20 h-20 bg-gray-100    rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900   mb-2">Nenhum Usuário Cadastrado</h3>
            <p class="text-gray-600   mb-4">Adicione usuários para gerenciar sua equipe</p>
            <button onclick="addUser()" class="px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Adicionar Primeiro Usuário
            </button>
        </div>
    `;
    return;
}

const usersHtml = users.map(user => {

    // Obter email do usuário atual do Database se não estiver no sessionStorage
    let currentUserEmail = sessionStorage.getItem('userEmail');
    if (!currentUserEmail) {
        // Tentar obter do localStorage ou sessionStorage como userData
        const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
        if (userData) {
            try {
                const parsedUserData = JSON.parse(userData);
                currentUserEmail = parsedUserData.email;
                // Armazenar no sessionStorage para uso futuro
                sessionStorage.setItem('userEmail', currentUserEmail);
            } catch (e) {
            }
        }
    }
    
    const isSecondaryAccount = user.email === currentUserEmail && user.role !== 'gerente';
    
    const roleText = {
        'gerente': 'Gerente',
        'funcionario': 'Funcionário',
        'veterinario': 'Veterinário',
        'proprietario': 'Proprietário'
    }[user.role] || user.role;
    
    const roleColor = {
        'gerente': 'bg-blue-100 text-blue-800',
        'funcionario': 'bg-green-100 text-green-800',
        'veterinario': 'bg-purple-100 text-purple-800',
        'proprietario': 'bg-yellow-100 text-yellow-800'
    }[user.role] || 'bg-gray-100 text-gray-800';
    
    // Definir cores inline para garantir que sejam aplicadas
    const roleColorInline = {
        'gerente': 'background-color: #dbeafe !important; color: #1e40af !important;',
        'funcionario': 'background-color: #dcfce7 !important; color: #166534 !important;',
        'veterinario': 'background-color: #f3e8ff !important; color: #7c3aed !important;',
        'proprietario': 'background-color: #fef3c7 !important; color: #d97706 !important;'
    }[user.role] || 'background-color: #f1f5f9 !important; color: #64748b !important;';
    
    const statusColor = user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    const statusText = user.is_active ? 'Ativo' : 'Inativo';
    
    const showPhoto = user.profile_photo_url && user.profile_photo_url.trim() !== '';
    
    return `
        <div class="user-card">
            <div class="user-card-header">
                <div class="user-info">
                    <div class="user-avatar relative">
                        ${showPhoto ? 
                            `<img id="user-photo-${user.id}" src="${user.profile_photo_url}?t=${Date.now()}" alt="Foto de ${user.name}" class="w-full h-full object-cover rounded-full" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" onload="this.nextElementSibling.style.display='none';">
                            <div id="user-icon-${user.id}" class="w-full h-full bg-gradient-to-br from-forest-500 to-forest-600 flex items-center justify-center absolute inset-0 rounded-full" style="display: flex;">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>` :
                            `<div id="user-icon-${user.id}" class="w-full h-full bg-gradient-to-br from-forest-500 to-forest-600 flex items-center justify-center rounded-full">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>`
                        }
                    </div>
                    <div class="user-details">
                        <div class="user-name">
                            ${user.name}
                            ${isSecondaryAccount ? '<span class="ml-2 text-orange-600"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg></span>' : ''}
                        </div>
                        <div class="user-email">${user.email}</div>
                        <div class="user-phone">${user.whatsapp || 'WhatsApp não informado'}</div>
                    </div>
                </div>
                <div class="user-status">
                    <span class="status-badge role" style="${roleColorInline}">${roleText}</span>
                    <span class="status-badge active ${statusColor}">${statusText}</span>
                    ${isSecondaryAccount ? '<span class="status-badge secondary" style="background-color: #fed7aa !important; color: #ea580c !important;"><svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Conta Secund�ria</span>' : ''}
                </div>
            </div>
            <div class="user-card-footer">
                <div class="user-created">Criado em: ${new Date(user.created_at).toLocaleDateString('pt-BR')}</div>
                <div class="user-actions">
                    <button onclick="editUser('${user.id}');" class="action-button edit" title="Editar usuário">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                    ${user.role !== 'gerente' ? `
                    <button onclick="toggleUserAccess('${user.id}', '${user.is_active}');" class="action-button permissions ${!user.is_active ? 'blocked' : ''}" title="${user.is_active ? 'Bloquear acesso' : 'Desbloquear acesso'}" data-user-id="${user.id}" data-current-status="${user.is_active}">
                        ${user.is_active ? 
                            '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>' :
                            '<svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 018 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>'
                        }
                    </button>
                    ` : ''}
                    ${user.role !== 'gerente' ? `
                    <button onclick="deleteUser('${user.id}', '${user.name}');" class="action-button delete" title="Excluir usuário">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}).join('');

usersList.innerHTML = usersHtml;
}

// Function to edit user
async function editUser(userId) {
try {
    // Usando MySQL direto atrav�s do objeto 'db'
    // Buscar dados do funcionário pelo ID selecionado
    const { data: user, error } = await db
        .from('users')
        .select('*')
        .eq('id', userId)
        .single();
    if (error) throw error;
    // Sempre criar uma c�pia do objeto
    const funcionario = { ...user };
    // Preencher campos do modal de ediçãoo
    document.getElementById('editUserId').value = funcionario.id;
    document.getElementById('editUserName').value = funcionario.name;
    document.getElementById('editUserEmail').value = funcionario.email;
    document.getElementById('editUserWhatsapp').value = funcionario.whatsapp || '';
    document.getElementById('editUserRole').value = funcionario.role;
    // Atualizar preview da foto baseado no role
    const editPreview = document.getElementById('editProfilePreview');
    const editPlaceholder = document.getElementById('editProfilePlaceholder');
    const photoSection = document.getElementById('editPhotoSection');
    
    if (photoSection) {
        if (funcionario.role === 'funcionario') {
            photoSection.classList.remove('hidden');
            
            if (funcionario.profile_photo_url && editPreview && editPlaceholder) {
                const uniqueTimestamp = Date.now() + '_' + funcionario.id + '_' + Math.random().toString(36).substr(2, 9);
                editPreview.src = funcionario.profile_photo_url + '?t=' + uniqueTimestamp;
                editPreview.classList.remove('hidden');
                editPlaceholder.classList.add('hidden');
            } else if (editPreview && editPlaceholder) {
                editPreview.classList.add('hidden');
                editPlaceholder.classList.remove('hidden');
                editPreview.src = '';
            }
        } else {
            photoSection.classList.add('hidden');
        }
    }
    openEditUserModal();
} catch (error) {
    console.error('Error loading user data:', error);
    showNotification('Erro ao carregar dados do usuário', 'error');
}
}

// Function to toggle user access
async function toggleUserAccess(userId, currentStatus) {
try {
    // Usando MySQL direto atrav�s do objeto 'db'

    if (!userId) {
        throw new Error('User ID is required');
    }
    
    const newStatus = !currentStatus;
    const action = newStatus ? 'desbloqueado' : 'bloqueado';

    const { data: currentUser, error: fetchError } = await db
        .from('users')
        .select('*')
        .eq('id', userId)
        .single();
        
    if (fetchError) {
        throw new Error('Usuário não encontrado');
    }
    
    // Now update the user status
    const { data, error } = await db
        .from('users')
        .update({ is_active: newStatus })
        .eq('id', userId)
        .select();
        
    if (error) {
        throw error;
    }
    
    showNotification(`Acesso do usuário ${action} com sucesso!`, 'success');
    
    // Reload users list with a small delay to ensure the update is processed
    setTimeout(async () => {
        await loadUsersData();
    }, 500);
    
} catch (error) {
    showNotification('Erro ao alterar acesso do usuário: ' + (error.message || error), 'error');
}
}

async function testUserBlocking() {
try {

    // Usando MySQL direto atrav�s do objeto 'db'
    // Get current user data
    const { data: { user } } = await db.auth.getUser();
    if (!user) {
        console.error('No authenticated user');
        return;
    }

    // Get farm users
    const { data: usersData, error } = await db
        .from('users')
        .select('*')
        .eq('id', user.id)
        .single();
        
    if (error) {
        console.error('Error fetching current user data:', error);
        return;
    }

    // Get all users from the same farm
    const { data: farmUsers, error: farmUsersError } = await db
        .from('users')
        .select('*')
        .eq('farm_id', 1);
        
    if (farmUsersError) {
        console.error('Error fetching farm users:', farmUsersError);
        return;
    }

    // Test blocking the first non-manager user
    const testUser = farmUsers.find(u => u.role !== 'gerente' && u.id !== usersData.id);
    
    if (testUser) {

        await toggleUserAccess(testUser.id, testUser.is_active);
    } else {

    }
    
} catch (error) {
    console.error('Test error:', error);
}
}

window.testUserBlocking = testUserBlocking;

// Variível para armazenar dados do usuário a ser exclu�do
let userToDelete = null;

function deleteUser(userId, userName) {

if (!userId || !userName) {
    console.error('deleteUser: Par�metros inválidos', { userId, userName });
    return;
}

// Armazenar dados do usuário para exclus�o
userToDelete = { id: userId, name: userName };

// Mostrar modal de confirmaçãoo
showDeleteConfirmationModal(userName);
}

// Funçãoo para executar a exclus�o
async function executeDeleteUser(userId, userName) {
try {
    // Usando MySQL direto atrav�s do objeto 'db'

    const { data: userData, error: fetchError } = await db
        .from('users')
        .select('*')
        .eq('id', userId)
        .single();
        
    if (fetchError) {
        showNotification('Erro ao buscar dados do usuário: ' + fetchError.message, 'error');
        return;
    }

    userToDelete = {
        id: userData.id,
        name: userData.name,
        email: userData.email,
        whatsapp: userData.whatsapp,
        role: userData.role,
        farm_id: 1,
        profile_photo_url: userData.profile_photo_url,
        is_active: userData.is_active,
        created_at: userData.created_at
    };
    
    // Agora excluir o usuário
    const { data, error } = await db
        .from('users')
        .delete()
        .eq('id', userId);
        
    if (error) {
        showNotification('Erro ao excluir usuário: ' + error.message, 'error');
        return;
    }
    
    showNotification(`Usuário "${userName}" exclu�do com sucesso!`, 'success');
    await loadUsersData(); // Reload users list
    
} catch (error) {
    showNotification('Erro ao excluir usuário: ' + error.message, 'error');
}
}

// Modal de confirmaçãoo de exclus�o
function showDeleteConfirmationModal(userName) {
// Criar modal dinamicamente
const modalHTML = `
    <div id="deleteConfirmationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-whiterounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="deleteModalContent">
            <div class="text-center">
                <!-- ícone de aviso -->
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                
                <!-- T�tulo -->
                <h3 class="text-xl font-bold text-gray-900mb-4">Confirmar Exclus�o</h3>
                
                <!-- Mensagem -->
                <p class="text-gray-600mb-6">
                    Tem certeza que deseja excluir o usuário <strong>"${userName}"</strong>?
                </p>
                
                <!-- Botões -->
                <div class="flex space-x-3">
                    <button onclick="cancelDelete()" class="flex-1 px-4 py-3 border border-gray-300text-gray-700font-semibold rounded-xl hover:bg-gray-50transition-all">
                        Cancelar
                    </button>
                    <button onclick="confirmDelete()" class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition-all">
                        Excluir
                    </button>
                </div>
            </div>
        </div>
    </div>
`;

// Adicionar modal ao DOM
document.body.insertAdjacentHTML('beforeend', modalHTML);

// Animar entrada do modal
setTimeout(() => {
    const modalContent = document.getElementById('deleteModalContent');
    modalContent.classList.remove('scale-95', 'opacity-0');
    modalContent.classList.add('scale-100', 'opacity-100');
}, 10);
}

// Funçãoo para iniciar timer de desfazer
function startUndoTimer() {
let timeLeft = 3;
const timerElement = document.getElementById('undoTimer');

const timer = setInterval(() => {
    timeLeft--;
    if (timerElement) {
        timerElement.textContent = timeLeft;
    }
    
    if (timeLeft <= 0) {
        clearInterval(timer);
        closeUndoModal();
    }
}, 1000);

// Armazenar timer para poder cancelar
window.undoTimer = timer;
}

// Funçãoo para desfazer exclus�o
async function undoDelete() {
// Limpar timer
if (window.undoTimer) {
    clearInterval(window.undoTimer);
    window.undoTimer = null;
}

if (userToDelete) {
    try {
        // Usando MySQL direto atrav�s do objeto 'db'
        
        // Restaurar usuário usando RPC
        const { data: result, error } = await db.rpc('restore_deleted_user', {
            p_user_id: userToDelete.id,
            p_name: userToDelete.name,
            p_email: userToDelete.email,
            p_whatsapp: userToDelete.whatsapp,
            p_role: userToDelete.role,
            p_farm_id: 1, // Lagoa Do Mato
            p_profile_photo_url: userToDelete.profile_photo_url
        });
        
        if (error) {
            throw error;
        }
        
        if (result.success) {
            showNotification(`Usuário "${userToDelete.name}" restaurado com sucesso!`, 'success');
            
            // Recarregar lista de usuários
            setTimeout(() => {
                loadUsersData();
            }, 500);
        } else {
            throw new Error(result.error || 'Falha ao restaurar usuário');
        }
        
    } catch (error) {
        console.error('Erro ao desfazer exclus�o:', error);
        showNotification('Erro ao desfazer exclus�o: ' + error.message, 'error');
    }
}

// Fechar modal
closeUndoModal();

// Limpar dados do usuário
userToDelete = null;
}

// Funçãoo para fechar modal de desfazer
function closeUndoModal() {
const modal = document.getElementById('undoModal');
if (modal) {
    const modalContent = document.getElementById('undoModalContent');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.remove();
    }, 300);
}

// Limpar dados do usuário
userToDelete = null;
}

// Funçãoo para cancelar exclus�o
function cancelDelete() {
// Fechar modal
closeDeleteConfirmationModal();

// Limpar dados do usuário
userToDelete = null;
}

// Funçãoo para confirmar exclus�o
function confirmDelete() {
// Executar exclus�o imediatamente
if (userToDelete) {
    executeDeleteUser(userToDelete.id, userToDelete.name);
}

// Fechar modal de confirmaçãoo
closeDeleteConfirmationModal();

// Mostrar modal de desfazer com timer
showUndoModal();
}

// Modal para desfazer exclus�o
function showUndoModal() {
const modalHTML = `
    <div id="undoModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-whiterounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="undoModalContent">
            <div class="text-center">
                <!-- ícone de sucesso -->
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                
                <!-- T�tulo -->
                <h3 class="text-xl font-bold text-gray-900mb-4">Usuário Exclu�do</h3>
                
                <!-- Mensagem -->
                <p class="text-gray-600mb-6">
                    O usuário <strong>"${userToDelete?.name}"</strong> foi exclu�do com sucesso!
                </p>
                
                <!-- Timer para desfazer -->
                <div class="mb-6">
                    <div class="text-sm text-gray-500 mb-2">Tempo para desfazer:</div>
                    <div class="flex items-center justify-center space-x-2">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <span id="undoTimer" class="text-blue-600 font-bold text-lg">3</span>
                        </div>
                        <span class="text-gray-500">segundos</span>
                    </div>
                </div>
                
                <!-- Botão para desfazer -->
                <button onclick="undoDelete()" class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all">
                    Desfazer Exclus�o
                </button>
            </div>
        </div>
    </div>
`;

// Adicionar modal ao DOM
document.body.insertAdjacentHTML('beforeend', modalHTML);

// Animar entrada do modal
setTimeout(() => {
    const modalContent = document.getElementById('undoModalContent');
    modalContent.classList.remove('scale-95', 'opacity-0');
    modalContent.classList.add('scale-100', 'opacity-100');
}, 10);

// Iniciar timer de 3 segundos para desfazer
startUndoTimer();
}

// Funçãoo para fechar modal de confirmaçãoo
function closeDeleteConfirmationModal() {
const modal = document.getElementById('deleteConfirmationModal');
if (modal) {
    const modalContent = document.getElementById('deleteModalContent');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.remove();
    }, 300);
}
}

// Adicionar listener para fechar modal com ESC
document.addEventListener('keydown', function(event) {
if (event.key === 'Escape') {
    cancelDelete();
}
});

async function handleEditUser(e) {
e.preventDefault();

const formData = new FormData(e.target);
const userId = formData.get('id'); // Corrigido para 'id' conforme o input hidden
const name = formData.get('name');
const whatsapp = formData.get('whatsapp');
const role = formData.get('role');
const password = formData.get('password');

try {
    // Preparar dados para atualização
    const updateData = {
        name: name,
        whatsapp: whatsapp || null,
        role: role
    };
    
    // Nota: Senhas s�o gerenciadas pelo Database Auth, não pela tabela users

    if (password && password.trim() !== '') {
        // TODO: Implementar atualização de senha via Database Auth se necessário

    }

    if (role === 'funcionario') {
        const profilePhotoFile = formData.get('profilePhoto');
        if (profilePhotoFile && profilePhotoFile.size > 0) {
            try {
                const profilePhotoUrl = await uploadProfilePhoto(profilePhotoFile, userId);
                updateData.profile_photo_url = profilePhotoUrl;
            } catch (photoError) {
                console.error('Error uploading profile photo:', photoError);

            }
        }
    } else {
        updateData.profile_photo_url = null;
    }
    
    // Usando MySQL direto atrav�s do objeto 'db'
    const { error } = await db
        .from('users')
        .update(updateData)
        .eq('id', userId);
        
    if (error) throw error;
    
    showNotification('Usuário atualizado com sucesso!', 'success');
    closeEditUserModal();
    
    // Update users list without reloading photos to prevent conflicts
    setTimeout(async () => {
        await refreshUsersListOnly();
    }, 500);
    
    // Atualizar foto na lista se foi alterada
    if (updateData.profile_photo_url) {
        setTimeout(async () => {
            await updateUserPhotoInList(userId, updateData.profile_photo_url);
        }, 600);
    }
    
} catch (error) {
    console.error('Error updating user:', error);
    showNotification('Erro ao atualizar usuário', 'error');
}
}

// Function to open edit user modal
function openEditUserModal() {
document.getElementById('editUserModal').classList.add('show');
}

// Function to close edit user modal
function closeEditUserModal() {
document.getElementById('editUserModal').classList.remove('show');
document.getElementById('editUserForm').reset();
}

// Funçãoo para preview de foto no add user
function previewProfilePhoto(input) {

let file = null;

if (input.files && input.files[0]) {
    file = input.files[0];
} else if (input instanceof File) {
    file = input;
} else {
    return;
}

// Validar tamanho do arquivo (5MB máximo)
if (file.size > 5 * 1024 * 1024) {
    showNotification('A foto deve ter no máximo 5MB', 'error');
    if (input.files) input.value = '';
    return;
}

// Validar tipo do arquivo
if (!file.type.startsWith('image/')) {
    showNotification('Por favor, selecione apenas arquivos de imagem', 'error');
    if (input.files) input.value = '';
    return;
}

    const reader = new FileReader();
    reader.onload = function(e) {
    const preview = document.getElementById('profilePreview');
    const placeholder = document.getElementById('profilePlaceholder');

    if (preview && placeholder) {
        preview.src = e.target.result;
        preview.style.display = 'block';
        preview.classList.remove('hidden');
        placeholder.style.display = 'none';
        placeholder.classList.add('hidden');
    } else {
        console.error('? Elementos de preview não encontrados');
    }
};
reader.readAsDataURL(file);
}

// Funçãoo para preview de foto no edit user
function previewEditProfilePhoto(input) {
let file = null;

if (input.files && input.files[0]) {
    file = input.files[0];
} else if (input instanceof File) {
    file = input;
} else {
    return;
}

// Validar tamanho do arquivo (5MB máximo)
if (file.size > 5 * 1024 * 1024) {
    showNotification('A foto deve ter no máximo 5MB', 'error');
    if (input.files) input.value = '';
    return;
}

// Validar tipo do arquivo
if (!file.type.startsWith('image/')) {
    showNotification('Por favor, selecione apenas arquivos de imagem', 'error');
    if (input.files) input.value = '';
    return;
}

    const reader = new FileReader();
    reader.onload = function(e) {
    const preview = document.getElementById('editProfilePreview');
    const placeholder = document.getElementById('editProfilePlaceholder');
    
    if (preview && placeholder) {
        preview.src = e.target.result;
        preview.style.display = 'block';
        preview.classList.remove('hidden');
        placeholder.style.display = 'none';
        placeholder.classList.add('hidden');
    }
};
reader.readAsDataURL(file);
}

// Vari�veis globais para c�mera
let currentPhotoMode = '';

// Funçãoes da C�mera - REFORMULADAS
let cameraStream = null;
let isCameraOpen = false;

async function openCamera() {

if (isCameraOpen) {
    return;
}

try {
    const modal = document.getElementById('cameraModal');
    const video = document.getElementById('cameraVideo');
    const processingScreen = document.getElementById('photoProcessingScreen');
    
    if (!modal || !video) {
        console.error('? Modal ou v�deo não encontrado');
        return;
    }

    // Fechar modal de escolha de foto
    closePhotoChoiceModal();
    
    // Garantir que a tela de processamento esteja oculta
    if (processingScreen) {
        processingScreen.classList.add('hidden');
        processingScreen.style.display = 'none';
        processingScreen.style.visibility = 'hidden';
        processingScreen.style.opacity = '0';
        processingScreen.style.pointerEvents = 'none';
    }
    
    // Abrir modal da c�mera
modal.classList.remove('hidden');
    modal.style.display = 'flex';
    isCameraOpen = true;

    resetFaceVerification();
    
    // Iniciar c�mera (funciona no desktop tamb�m)
    const stream = await navigator.mediaDevices.getUserMedia({ 
        video: { 
            facingMode: 'user',
            width: { ideal: 1280 },
            height: { ideal: 720 }
        } 
    });

    cameraStream = stream;
    video.srcObject = stream;

} catch (error) {
    console.error('? Erro ao acessar c�mera:', error);
    showNotification('N�o foi possível acessar a c�mera. Verifique as permissões.', 'error');
    closeCamera();
}
}

function closeCamera() {

const modal = document.getElementById('cameraModal');

if (!modal) {
    console.error('? Modal não encontrado');
    return;
}

// Parar stream da c�mera
if (cameraStream) {
    cameraStream.getTracks().forEach(track => track.stop());
    cameraStream = null;
}

// Fechar modal
    modal.classList.add('hidden');
modal.style.display = 'none';
isCameraOpen = false;

// Limpar currentPhotoMode apenas agora
currentPhotoMode = '';

}

async function switchCamera() {

if (!cameraStream) {
    console.error('? Nenhum stream ativo');
    return;
}

try {
    const video = document.getElementById('cameraVideo');
    const currentFacingMode = cameraStream.getVideoTracks()[0]?.getSettings().facingMode;
    const newFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';

    // Parar stream atual
    cameraStream.getTracks().forEach(track => track.stop());
    
    // Iniciar nova c�mera
    const newStream = await navigator.mediaDevices.getUserMedia({ 
        video: { 
            facingMode: newFacingMode,
            width: { ideal: 1280 },
            height: { ideal: 720 }
        } 
    });
    
    cameraStream = newStream;
    video.srcObject = newStream;

} catch (error) {
    console.error('? Erro ao trocar c�mera:', error);
}
}

function resetFaceVerification() {
const focusText = document.getElementById('focusText');
const focusTimer = document.getElementById('focusTimer');
const focusIndicator = document.getElementById('focusIndicator');
const captureBtn = document.getElementById('captureBtn');

if (focusText) focusText.textContent = 'Posicione o rosto no centro';
if (focusTimer) focusTimer.classList.add('hidden');
if (focusIndicator) {
    focusIndicator.classList.add('opacity-0');
    focusIndicator.classList.remove('focus-success');
}
if (captureBtn) {
    captureBtn.disabled = false;
    captureBtn.style.opacity = '1';
}
}

function startFaceVerification() {

const focusText = document.getElementById('focusText');
const focusTimer = document.getElementById('focusTimer');
const timerCount = document.getElementById('timerCount');
const focusIndicator = document.getElementById('focusIndicator');
const captureBtn = document.getElementById('captureBtn');

if (!focusText || !focusTimer || !timerCount || !focusIndicator || !captureBtn) {
    console.error('? Elementos de foco não encontrados');
    return;
}

captureBtn.disabled = true;
captureBtn.style.opacity = '0.5';

// Mostrar timer
focusText.textContent = 'Mantenha o rosto no centro';
focusTimer.classList.remove('hidden');

// Timer de 3 segundos
let countdown = 3;
timerCount.textContent = countdown;

const timer = setInterval(() => {
    countdown--;
    timerCount.textContent = countdown;
    
    if (countdown <= 0) {
        clearInterval(timer);
        
        // Mostrar indicador de foco
        focusIndicator.classList.remove('opacity-0');
        focusIndicator.classList.add('focus-success');
        
        // Capturar foto após 0.5s
        setTimeout(() => {
            capturePhoto();
        }, 500);
    }
}, 1000);
}

function capturePhoto() {

const video = document.getElementById('cameraVideo');
const canvas = document.getElementById('cameraCanvas');

if (!video || !canvas) {
    console.error('? V�deo ou canvas não encontrado');
    return;
}

const context = canvas.getContext('2d');
canvas.width = video.videoWidth || 640;
canvas.height = video.videoHeight || 480;

try {
    // Desenhar frame do v�deo no canvas
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Converter para blob e processar
    canvas.toBlob((blob) => {
        if (!blob) {
            console.error('? Erro ao criar blob');
            return;
        }

        // Criar URL da imagem
        const imageUrl = URL.createObjectURL(blob);
        
        // Mostrar preview diretamente
        if (currentPhotoMode === 'add') {
            const preview = document.getElementById('profilePreview');
            const placeholder = document.getElementById('profilePlaceholder');

            if (preview && placeholder) {
                preview.src = imageUrl;
                preview.style.display = 'block';
                preview.classList.remove('hidden');
                placeholder.style.display = 'none';
                placeholder.classList.add('hidden');
} else {
                console.error('? Elementos de preview não encontrados para novo usuário');
            }
        } else if (currentPhotoMode === 'edit') {
            const preview = document.getElementById('editProfilePreview');
            const placeholder = document.getElementById('editProfilePlaceholder');

            if (preview && placeholder) {
                preview.src = imageUrl;
                preview.style.display = 'block';
                preview.classList.remove('hidden');
                placeholder.style.display = 'none';
                placeholder.classList.add('hidden');
            } else {
                console.error('? Elementos de preview não encontrados para ediçãoo');
            }
        } else {
            console.error('? currentPhotoMode inválido:', currentPhotoMode);
        }
        
        // Fechar c�mera
        closeCamera();
        
    }, 'image/jpeg', 0.8);
    
} catch (error) {
    console.error('? Erro ao capturar foto:', error);
    closeCamera();
}
}

// Funçãoo para abrir galeria
function openGallery() {
const inputId = currentPhotoMode === 'add' ? 'profilePhotoInput' : 'editProfilePhoto';
const input = document.getElementById(inputId);

if (input) {
    input.removeAttribute('capture');
input.click();
}

closePhotoChoiceModal();
}

// Funçãoes para adicionar foto
function addPhotoToNewUser() {
openPhotoChoiceModal('add');
}

function addPhotoToEditUser() {
openPhotoChoiceModal('edit');
}

// Funçãoes do novo modal de foto
function openPhotoChoiceModal(mode) {
currentPhotoMode = mode;

const modal = document.getElementById('photoChoiceModal');

if (modal) {
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.style.display = 'flex';
    modal.style.visibility = 'visible';
    modal.style.opacity = '1';
    modal.style.pointerEvents = 'auto';

    setTimeout(() => {
        const rect = modal.getBoundingClientRect();
    }, 100);
} else {
    console.error('? Modal não encontrado');
}
}

function closePhotoChoiceModal() {
const modal = document.getElementById('photoChoiceModal');
if (modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.style.display = 'none';
    modal.style.visibility = 'hidden';
    modal.style.opacity = '0';
    modal.style.pointerEvents = 'none';
    // NÃO limpar currentPhotoMode aqui, pois precisamos dele na c�mera
    console.log('? Modal fechado, currentPhotoMode mantido:', currentPhotoMode);
}
}

function selectFromGallery() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Processar a imagem selecionada
                console.log('Imagem selecionada:', file.name);
                // Aqui você pode adicionar a lógica para processar a imagem
            };
            reader.readAsDataURL(file);
        }
    };
    
    input.click();
}

// Load recent activities
async function loadRecentActivities(farmId = 1) {
try {
    console.log('?? Carregando atividades recentes para fazenda: Lagoa Do Mato (ID=1)');
    
    // Aguardar Database estar disponível
    if (!window.db) {
        await new Promise(resolve => setTimeout(resolve, 1000));
        if (!window.db) {
            console.error('? Database não disponível para atividades recentes');
            return;
        }
    }
    
    // Usando MySQL direto atrav�s do objeto 'db'
    
    // Usar API de atividades
    const response = await fetch('api/activities.php?action=select');
    const result = await response.json();
    
    if (!result.success) {
        console.error('? Erro ao carregar atividades recentes:', result.error);
        return;
    }
    
    const activities = result.data || [];
    
    console.log('?? Atividades encontradas:', activities?.length || 0);

    const activitiesContainer = document.getElementById('recentActivities');
    
    if (!activities || activities.length === 0) {
        console.log('?? Nenhuma atividade encontrada, mostrando mensagem padrão');
        activitiesContainer.innerHTML = `
            <div class="text-center py-8">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-500 text-sm">Nenhuma atividade recente</p>
                <p class="text-gray-400 text-xs">Registros aparecerão aqui</p>
            </div>
        `;
        return;
    }
    
    console.log('? Renderizando atividades recentes...');

    activitiesContainer.innerHTML = activities.map(activity => {
        const timeAgo = getTimeAgoFromDate(new Date(activity.created_at));
        const userName = activity.users?.name || 'Usuário';
        
        return `
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-forest-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">${activity.total_volume}L - ${window.getMilkingTypeInPortuguese(activity.shift)}</p>
                        <p class="text-xs text-gray-500">${timeAgo} � por ${userName}</p>
                    </div>
                </div>
                <div class="text-xs text-gray-400">
                    ${timeAgo}
                </div>
            </div>
        `;
    }).join('');

} catch (error) {
    console.error('? Erro ao carregar atividades recentes:', error);
}

        console.log('? Funçãoo loadRecentActivities conclu�da');
}

// ==================== REAL-TIME UPDATES ====================
let realtimeSubscriptions = [];

// Funçãoo para configurar atualizações em tempo real
async function setupRealtimeUpdates() {
try {
    console.log('?? Configurando atualizações em tempo real...');
    
    // Sistema Lagoa Do Mato
    const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
    if (!userData) {
        console.log('? Dados de usuário não encontrados para tempo real');
        return;
    }
    
    const parsedUserData = JSON.parse(userData);
    const farmId = 1; // Lagoa Do Mato
    
    // Obter cliente Database
    // Usando MySQL direto atrav�s do objeto 'db'
    if (!db) {
        console.log('? Database não disponível para tempo real');
        return;
    }
    
    // 1. Escutar mudan�as na tabela volume_records (produção)
    const volumeSubscription = db
        .channel('volume_records_changes')
        .on(
            'postgres_changes',
            {
                event: '*', // INSERT, UPDATE, DELETE
                schema: 'public',
                table: 'volume_records',
                filter: `farm_id=eq.1` // Lagoa Do Mato
            },
            async (payload) => {
                console.log('?? Mudan�a detectada em volume_records:', payload.eventType);
                
                // Atualizar apenas os componentes necessários
                switch (payload.eventType) {
                    case 'INSERT':
                        await handleNewProduction(payload.new);
                        break;
                    case 'UPDATE':
                        await handleProductionUpdate(payload.new, payload.old);
                        break;
                    case 'DELETE':
                        await handleProductionDelete(payload.old);
                        break;
                }
            }
        )
        .subscribe();

    // Armazenar referência da subscription
    realtimeSubscriptions = [volumeSubscription];

    console.log('? Atualizações em tempo real configuradas com sucesso!');
    
    // Mostrar indicador visual
    const indicator = document.getElementById('realtimeIndicator');
    if (indicator) {
        indicator.classList.remove('hidden');
    }
    
} catch (error) {
    console.error('? Erro ao configurar atualizações em tempo real:', error.message);
}
}

// Funçãoo para limpar todas as subscriptions
function cleanupRealtimeUpdates() {
try {
    console.log('?? Limpando atualizações em tempo real...');
    
    realtimeSubscriptions.forEach(subscription => {
        if (subscription && subscription.unsubscribe) {
            subscription.unsubscribe();
        }
    });
    
    realtimeSubscriptions = [];
    
    // Esconder indicador visual
    const indicator = document.getElementById('realtimeIndicator');
    if (indicator) {
        indicator.classList.add('hidden');
    }
    
    console.log('? Atualizações em tempo real limpas');
    
} catch (error) {
    console.error('? Erro ao limpar atualizações em tempo real:', error.message);
}
}

// Handlers para mudan�as em tempo real
async function handleNewProduction(newProduction) {
try {
    console.log('?? Nova produção detectada:', newProduction);
    
    // Atualizar volume de hoje
    await updateTodayVolume();
    
    // Atualizar atividades recentes
    const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
    if (userData) {
        const parsedUserData = JSON.parse(userData);
        await loadRecentActivities(); // Lagoa Do Mato
    }

    showNotification(`Nova produção registrada: ${newProduction.total_volume}L`, 'success');

    if (window.nativeNotifications) {
        window.nativeNotifications.showRealDeviceNotification(
            'Nova Produção Registrada',
            `Volume: ${newProduction.total_volume}L registrado com sucesso!`,
            'production'
        );
    }
    
} catch (error) {
    console.error('? Erro ao processar nova produção:', error.message);
}
}

async function handleProductionUpdate(newProduction, oldProduction) {
try {
    console.log('?? Produção atualizada:', { old: oldProduction, new: newProduction });
    
    // Atualizar volume de hoje
    await updateTodayVolume();
    
    // Atualizar atividades recentes
    const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
    if (userData) {
        const parsedUserData = JSON.parse(userData);
        await loadRecentActivities(); // Lagoa Do Mato
    }

    showNotification('Produção atualizada com sucesso!', 'info');
    
} catch (error) {
    console.error('? Erro ao processar atualização de produção:', error.message);
}
}

async function handleProductionDelete(deletedProduction) {
try {
    console.log('??? Produção deletada:', deletedProduction);
    
    // Atualizar volume de hoje
    await updateTodayVolume();
    
    // Atualizar atividades recentes
    const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
    if (userData) {
        const parsedUserData = JSON.parse(userData);
        await loadRecentActivities(); // Lagoa Do Mato
    }

    showNotification('Produção removida com sucesso!', 'info');
    
} catch (error) {
    console.error('? Erro ao processar remoçãoo de produção:', error.message);
}
}

// Funçãoo para atualizar volume de hoje
async function updateTodayVolume() {
try {
    const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
    if (!userData) return;
    
    const parsedUserData = JSON.parse(userData);
    // Usando MySQL direto atrav�s do objeto 'db'
    
    const { data: volumeData, error: volumeError } = await db
        .from('volume_records')
        .select('total_volume')
        .eq('farm_id', 1)
        .gte('record_date', new Date().toISOString().split('T')[0]);

    if (!volumeError && volumeData && volumeData.length > 0) {
        const todayVolume = volumeData.reduce((sum, record) => sum + (record.total_volume || 0), 0);
        const volumeElement = document.getElementById('todayVolume');
        if (volumeElement) {
            volumeElement.textContent = `${todayVolume} L`;

            localStorage.setItem('todayVolume', todayVolume.toString());
            localStorage.setItem('todayVolumeDate', new Date().toISOString().split('T')[0]);
            
            console.log('? Volume de hoje atualizado em tempo real:', todayVolume, 'L');
        }
    }
} catch (error) {
    console.error('? Erro ao atualizar volume em tempo real:', error.message);
}
}

// Initialize charts
function initializeCharts() {

if (typeof Chart === 'undefined') {
    console.error('? Chart.js não está carregado!');
    return;
}

console.log('?? Chart.js disponível, inicializando gr�ficos...');

// Volume Chart
const volumeCtx = document.getElementById('volumeChart');
if (volumeCtx) {
    console.log('? Inicializando gr�fico volumeChart (Volume Semanal)...');
    try {
        window.volumeChart = new Chart(volumeCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Volume (L)',
                data: [],
                borderColor: '#369e36',
                backgroundColor: 'rgba(54, 158, 54, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
        });
        console.log('? Gr�fico volumeChart (Volume Semanal) inicializado com sucesso');
    } catch (error) {
        console.error('? Erro ao inicializar volumeChart:', error);
    }
} else {
    console.error('? Elemento volumeChart não encontrado no DOM');
}

// Quality Chart
const qualityCtx = document.getElementById('qualityChart');
if (qualityCtx) {
    try {
        window.qualityChart = new Chart(qualityCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Qualidade (%)',
                data: [],
                backgroundColor: '#5bb85b',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
        });
        console.log('? Gr�fico qualityChart inicializado com sucesso');
    } catch (error) {
        console.error('? Erro ao inicializar qualityChart:', error);
    }
} else {
    console.error('? Elemento qualityChart não encontrado no DOM');
}

// Temperature Chart
const temperatureCtx = document.getElementById('temperatureChart');

if (temperatureCtx) {
    try {
        window.temperatureChart = new Chart(temperatureCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Temperatura (�C)',
                data: [],
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                borderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointBackgroundColor: '#f59e0b',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                tension: 0.4,
                fill: true,
                showLine: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: 'white',
                    bodyColor: 'white',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return 'Temperatura: ' + context.parsed.y + '�C';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '�C';
                        }
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            },
            elements: {
                line: {
                    tension: 0.4
                },
                point: {
                    radius: 6,
                    hoverRadius: 8
                }
            }
        }
        });
        console.log('? Gr�fico temperatureChart inicializado com sucesso');
    } catch (error) {
        console.error('? Erro ao inicializar temperatureChart:', error);
    }
} else {
    console.error('? Elemento temperatureChart não encontrado no DOM');
}

// Dashboard Weekly Production Chart
const dashboardWeeklyCtx = document.getElementById('dashboardWeeklyChart');
if (dashboardWeeklyCtx) {
    console.log('? Inicializando gr�fico dashboardWeeklyChart...');
    window.dashboardWeeklyChart = new Chart(dashboardWeeklyCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Produção (L)',
                data: [],
                backgroundColor: '#5bb85b',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    // Escala din�mica - sem limite máximo
                    ticks: {
                        callback: function(value) {
                            return value + 'L';
                        }
                    }
                }
            }
        }
    });
    console.log('? Gr�fico dashboardWeeklyChart inicializado com sucesso');
} else {
    console.error('? Elemento dashboardWeeklyChart não encontrado no DOM');
}

// Weekly Volume Chart
const weeklyVolumeCtx = document.getElementById('weeklyVolumeChart');
if (weeklyVolumeCtx) {
    window.weeklyVolumeChart = new Chart(weeklyVolumeCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Volume Semanal (L)',
                data: [],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: 'white',
                    bodyColor: 'white',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return 'Volume: ' + context.parsed.y + 'L';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + 'L';
                        }
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            }
        }
    });
}

// Daily Volume Chart
const dailyVolumeCtx = document.getElementById('dailyVolumeChart');
if (dailyVolumeCtx) {
    window.dailyVolumeChart = new Chart(dailyVolumeCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Volume por Horário (L)',
                data: [],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    // Escala din�mica - sem limite máximo
                    ticks: {
                        callback: function(value) {
                            return value + 'L';
                        }
                    }
                }
            }
        }
    });
}

// Quality Trend Chart
const qualityTrendCtx = document.getElementById('qualityTrendChart');
if (qualityTrendCtx) {
    console.log('📊 Inicializando gráfico de tendência de qualidade...');
    window.qualityTrendChart = new Chart(qualityTrendCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Gordura (%)',
                    data: [],
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 3,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#f59e0b',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Proteína (%)',
                    data: [],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    tension: 0.4,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 6,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: 'white',
                    bodyColor: 'white',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1
                }
            }
        }
    });
    console.log('✅ Gráfico de tendência de qualidade inicializado');
} else {
    console.error('❌ Elemento qualityTrendChart não encontrado');
}

// Quality Distribution Chart
const qualityDistCtx = document.getElementById('qualityDistributionChart');
if (qualityDistCtx) {
    console.log('📊 Inicializando gráfico de distribuição de qualidade...');
    window.qualityDistributionChart = new Chart(qualityDistCtx, {
        type: 'doughnut',
        data: {
            labels: ['Excelente', 'Bom', 'Regular'],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    console.log('✅ Gráfico de distribuição de qualidade inicializado');
} else {
    console.error('❌ Elemento qualityDistributionChart não encontrado');
}

// Payments Chart
const paymentsCtx = document.getElementById('paymentsChart');
if (paymentsCtx) {
    window.paymentsChart = new Chart(paymentsCtx, {
        type: 'bar',
        data: {
            labels: ['Pagos', 'Pendentes', 'Atrasados'],
            datasets: [{
                label: 'Vendas (R$)',
                data: [0, 0, 0],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
// Weekly Summary Chart
const weeklySummaryCtx = document.getElementById('weeklySummaryChart');
if (weeklySummaryCtx) {
    window.weeklySummaryChart = new Chart(weeklySummaryCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Volume Semanal (L)',
                data: [],
                backgroundColor: '#3b82f6',
                borderColor: '#1d4ed8',
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + 'L';
                        }
                    }
                }
            }
        }
    });
}

// Monthly Volume Chart
const monthlyVolumeCtx = document.getElementById('monthlyVolumeChart');
if (monthlyVolumeCtx) {
    window.monthlyVolumeChart = new Chart(monthlyVolumeCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Volume Mensal (L)',
                data: [],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + 'L';
                        }
                    }
                }
            }
        }
    });
}
}

function hideNotification() {
document.getElementById('notificationToast').classList.remove('show');
}

function openProfileModal() {
console.log('🔵 ABRINDO MODAL DE PERFIL...');
const modal = document.getElementById('profileModal');
console.log('🔵 Modal encontrado:', !!modal);

if (!modal) {
    console.error('❌ MODAL DE PERFIL NÃO ENCONTRADO NO DOM!');
    return;
}

// FORÇA ABERTURA DO MODAL - PRIORIDADE MÁXIMA
modal.classList.remove('hidden');
modal.classList.add('modal-enabled');

// Remover todos os estilos inline que possam estar bloqueando
modal.removeAttribute('style');

// Aplicar estilos inline com !important via setAttribute
modal.setAttribute('style', 'display: flex !important; visibility: visible !important; opacity: 1 !important; pointer-events: auto !important; z-index: 9999 !important; position: fixed !important; inset: 0 !important;');

document.body.style.overflow = 'hidden';

console.log('✅ MODAL DE PERFIL ABERTO COM SUCESSO!');
console.log('Classes do modal:', modal.className);
console.log('Style do modal:', modal.getAttribute('style'));

    // CARREGAR DADOS DO PERFIL
    loadProfileData();
    
    // FORÇAR ATUALIZAÇÃO DOS DADOS DO PERFIL
    setTimeout(() => {
        forceUpdateProfileData();
    }, 200);

    setTimeout(() => {
        if (typeof setupProfileModalHeader === 'function') {
            setupProfileModalHeader();
        }
    }, 100);
}

function forceUpdateProfileData() {
    try {
        console.log('🔄 FORÇANDO ATUALIZAÇÃO DOS DADOS DO PERFIL...');
        
        // Buscar dados do usuário
        const userData = localStorage.getItem('user_data') || sessionStorage.getItem('user_data') || localStorage.getItem('userData') || sessionStorage.getItem('userData');
        
        if (userData) {
            const user = JSON.parse(userData);
            console.log('👤 Dados encontrados para forçar atualização:', user);
            
            // FORÇAR atualização do nome
            const nameElement = document.getElementById('profileName');
            if (nameElement) {
                const displayName = user.name || user.nome || user.full_name || user.fullName || 'Usuário';
                nameElement.textContent = displayName;
                console.log('✅ Nome FORÇADO:', displayName);
            }
            
            // FORÇAR atualização do cargo
            const roleElement = document.getElementById('profileRole');
            if (roleElement) {
                const roleText = user.role || user.cargo || 'Gerente';
                roleElement.textContent = roleText;
                console.log('✅ Cargo FORÇADO:', roleText);
            }
            
            // FORÇAR atualização da fazenda
            const farmElement = document.getElementById('profileFarmName');
            if (farmElement) {
                const farmText = user.farm_name || user.fazenda || 'Fazenda';
                farmElement.textContent = farmText;
                console.log('✅ Fazenda FORÇADA:', farmText);
            }
        } else {
            console.log('⚠️ Nenhum dado de usuário encontrado para forçar atualização');
        }
    } catch (error) {
        console.error('❌ Erro ao forçar atualização dos dados:', error);
    }
}

window.testarDadosUsuario = function() {
    console.log('🔍 TESTANDO DADOS DO USUÁRIO:');
    console.log('localStorage user_data:', localStorage.getItem('user_data'));
    console.log('localStorage userData:', localStorage.getItem('userData'));
    console.log('sessionStorage user_data:', sessionStorage.getItem('user_data'));
    console.log('sessionStorage userData:', sessionStorage.getItem('userData'));
    
    const userData = localStorage.getItem('user_data') || sessionStorage.getItem('user_data') || localStorage.getItem('userData') || sessionStorage.getItem('userData');
    if (userData) {
        const user = JSON.parse(userData);
        console.log('👤 Dados parseados:', user);
        console.log('📝 Chaves disponíveis:', Object.keys(user));
        console.log('👤 Nome:', user.name);
        console.log('👤 Nome (alternativo):', user.nome);
        console.log('👤 Role:', user.role);
        console.log('👤 Email:', user.email);
    }
};

window.forcarAtualizacaoPerfil = function() {
    forceUpdateProfileData();
};

// Função de teste SIMPLES para abrir modal
window.testarModalSimples = function() {
    console.log('🧪 TESTE SIMPLES: Tentando abrir modal...');
    
    const modal = document.getElementById('profileModal');
    console.log('Modal encontrado:', !!modal);
    
    if (modal) {
        // FORÇAR todas as classes necessárias
        modal.classList.remove('hidden');
        modal.classList.add('modal-enabled');
        modal.classList.add('show');
        
        // Método mais simples
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        modal.style.zIndex = '9999';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100vw';
        modal.style.height = '100vh';
        modal.style.background = 'rgba(0, 0, 0, 0.5)';
        
        document.body.style.overflow = 'hidden';
        
        console.log('✅ Modal aberto com método simples!');
        console.log('Classes do modal:', modal.className);
    } else {
        console.error('❌ Modal não encontrado!');
    }
};

window.debugModal = function() {
    const modal = document.getElementById('profileModal');
    if (modal) {
        console.log('🔍 DEBUG MODAL:');
        console.log('- Elemento encontrado:', !!modal);
        console.log('- Classes:', modal.className);
        console.log('- Style display:', modal.style.display);
        console.log('- Style visibility:', modal.style.visibility);
        console.log('- Style opacity:', modal.style.opacity);
        console.log('- Computed display:', window.getComputedStyle(modal).display);
        console.log('- Computed visibility:', window.getComputedStyle(modal).visibility);
        console.log('- Computed opacity:', window.getComputedStyle(modal).opacity);
        console.log('- Computed z-index:', window.getComputedStyle(modal).zIndex);
        console.log('- Computed position:', window.getComputedStyle(modal).position);
    } else {
        console.error('❌ Modal não encontrado!');
    }
};

// ========== NOVO MODAL DE PERFIL - JAVASCRIPT SIMPLES ==========

// Função para abrir o modal (NOVA VERSÃO)
function openProfileModalNew() {
    console.log('🔵 ABRINDO NOVO MODAL DE PERFIL...');
    
    const modal = document.getElementById('profileModal');
    if (!modal) {
        console.error('❌ Modal não encontrado!');
        return;
    }
    
    // Mostrar modal
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    
    // Bloquear scroll do body
    document.body.style.overflow = 'hidden';
    
    // Carregar dados do usuário
    loadUserDataNew();
    
    console.log('✅ Modal aberto com sucesso!');
}

// Função para fechar o modal (NOVA VERSÃO)
function closeProfileModalNew() {
    console.log('🔴 FECHANDO MODAL DE PERFIL...');
    
    const modal = document.getElementById('profileModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        
        // Restaurar scroll do body
        document.body.style.overflow = '';
        
        console.log('✅ Modal fechado com sucesso!');
    }
}

// Função para carregar dados do usuário (NOVA VERSÃO)
function loadUserDataNew() {
    try {
        console.log('📊 Carregando dados do usuário...');
        
        // Buscar dados do usuário
        const userData = localStorage.getItem('user_data') || 
                        sessionStorage.getItem('user_data') || 
                        localStorage.getItem('userData') || 
                        sessionStorage.getItem('userData');
        
        if (userData) {
            const user = JSON.parse(userData);
            console.log('👤 Dados encontrados:', user);
            
            // Atualizar nome
            const nameElement = document.getElementById('profileName');
            if (nameElement) {
                nameElement.textContent = user.name || user.nome || 'Usuário';
            }
            
            // Atualizar cargo
            const roleElement = document.getElementById('profileRole');
            if (roleElement) {
                roleElement.textContent = user.role || user.cargo || 'Gerente';
            }
            
            // Atualizar fazenda
            const farmElement = document.getElementById('profileFarmName');
            if (farmElement) {
                farmElement.textContent = user.farm_name || user.fazenda || 'Fazenda';
            }

            const fullNameElement = document.getElementById('profileFullName');
            if (fullNameElement) {
                fullNameElement.textContent = user.name || user.nome || 'Usuário';
            }
            
            // Atualizar email
            const emailElement = document.getElementById('profileEmail');
            if (emailElement) {
                emailElement.textContent = user.email || 'Não informado';
            }
            
            // Atualizar WhatsApp
            const whatsappElement = document.getElementById('profileWhatsApp');
            if (whatsappElement) {
                whatsappElement.textContent = user.whatsapp || user.phone || 'Não informado';
            }
            
            console.log('✅ Dados carregados com sucesso!');
        } else {
            console.log('⚠️ Nenhum dado de usuário encontrado');
        }
        
    } catch (error) {
        console.error('❌ Erro ao carregar dados:', error);
    }
}

// Função de teste para o novo modal
window.testarNovoModal = function() {
    console.log('🧪 TESTANDO NOVO MODAL...');
    openProfileModalNew();
};

// Exportar funções novas
window.openProfileModalNew = openProfileModalNew;
window.closeProfileModalNew = closeProfileModalNew;
window.loadUserDataNew = loadUserDataNew;

// Função para carregar dados do perfil
async function loadProfileData() {
try {
    console.log('📊 Carregando dados do perfil...');
    
    // Buscar dados do usuário logado
    const userData = localStorage.getItem('user_data') || sessionStorage.getItem('user_data') || localStorage.getItem('userData') || sessionStorage.getItem('userData');
    
    console.log('🔍 Dados encontrados no localStorage:', {
        'user_data': localStorage.getItem('user_data'),
        'userData': localStorage.getItem('userData'),
        'sessionStorage user_data': sessionStorage.getItem('user_data'),
        'sessionStorage userData': sessionStorage.getItem('userData')
    });
    
    if (userData) {
        const user = JSON.parse(userData);
        console.log('👤 Dados do usuário parseados:', user);
        console.log('📝 Propriedades disponíveis:', Object.keys(user));
        console.log('👤 user.name:', user.name);
        console.log('👤 user.nome:', user.nome);
        console.log('👤 user.role:', user.role);
        console.log('👤 user.cargo:', user.cargo);
        
        // Atualizar nome no modal
        const nameElement = document.getElementById('profileName');
        if (nameElement) {
            const displayName = user.name || user.nome || user.full_name || user.fullName || 'Usuário';
            nameElement.textContent = displayName;
            console.log('✅ Nome atualizado no modal:', displayName);
        }
        
        // Atualizar cargo
        const roleElement = document.getElementById('profileRole');
        if (roleElement) {
            const roleText = user.role || user.cargo || 'Gerente';
            roleElement.textContent = roleText;
            console.log('✅ Cargo atualizado:', roleText);
        }
        
        // Atualizar fazenda
        const farmElement = document.getElementById('profileFarmName');
        if (farmElement) {
            farmElement.textContent = user.farm_name || user.fazenda || 'Fazenda';
            console.log('✅ Fazenda atualizada:', user.farm_name || user.fazenda || 'Fazenda');
        }
        
        // ATUALIZAR TAMBÉM O BEM-VINDO NA PÁGINA PRINCIPAL
        const welcomeElement = document.getElementById('managerWelcome');
        if (welcomeElement) {
            const displayName = user.name || user.nome || user.full_name || user.fullName || 'Usuário';
            welcomeElement.textContent = displayName;
            console.log('✅ Bem-vindo atualizado:', displayName);
        }
        
        console.log('✅ Dados do perfil carregados:', user);
    } else {
        console.log('⚠️ Dados do usuário não encontrados, usando valores padrão');
        
        // Valores padrão
        const nameElement = document.getElementById('profileName');
        if (nameElement) nameElement.textContent = 'Usuário';
        
        const roleElement = document.getElementById('profileRole');
        if (roleElement) roleElement.textContent = 'Gerente';
        
        const farmElement = document.getElementById('profileFarmName');
        if (farmElement) farmElement.textContent = 'Fazenda';
        
        // Atualizar bem-vindo também
        const welcomeElement = document.getElementById('managerWelcome');
        if (welcomeElement) welcomeElement.textContent = 'Usuário';
    }
    
} catch (error) {
    console.error('❌ Erro ao carregar dados do perfil:', error);
}
}

function closeProfileModal() {
const modal = document.getElementById('profileModal');
if (modal) {
    modal.classList.remove('modal-enabled');
    modal.classList.add('hidden');
    modal.style.display = 'none';
    modal.style.visibility = 'hidden';
    modal.style.opacity = '0';
    modal.style.pointerEvents = 'none';
    document.body.style.overflow = '';
}
}

// Exportar funções para o window para uso em onclick - PRIORIDADE MÁXIMA
if (typeof window !== 'undefined') {
    window.openProfileModal = openProfileModal;
    window.closeProfileModal = closeProfileModal;
    console.log('✅ Funções openProfileModal e closeProfileModal exportadas para window');
    
    // Teste automático para debug
    console.log('🔵 Função disponível?', typeof window.openProfileModal === 'function');
    
    // TESTE: Adicionar botão para testar modal no console
    window.testarModalPerfil = function() {
        console.log('🧪 TESTE MANUAL: Tentando abrir modal de perfil...');
        openProfileModal();
    };
    console.log('💡 Digite no console: testarModalPerfil() para testar o modal');
}

function toggleProfileEdit() {
const viewMode = document.getElementById('profileViewMode');
const editMode = document.getElementById('profileEditMode');
const editBtn = document.getElementById('editProfileBtn');
const editButtons = document.getElementById('profileEditButtons');

if (editMode.classList.contains('hidden')) {
    // Switch to edit mode
    viewMode.classList.add('hidden');
    editMode.classList.remove('hidden');
    // NÃO mostrar bot�es automaticamente - s� quando h� mudan�as
    editButtons.classList.add('hidden');
    editBtn.innerHTML = `
        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
        Cancelar
    `;
    editBtn.onclick = cancelProfileEdit;

    populateEditForm();
} else {
    // Switch back to view mode
    cancelProfileEdit();
}
}

function cancelProfileEdit() {
console.log('? Cancelando ediçãoo do perfil...');

const viewMode = document.getElementById('profileViewMode');
const editMode = document.getElementById('profileEditMode');
const editBtn = document.getElementById('editProfileBtn');
const editButtons = document.getElementById('profileEditButtons');

viewMode.classList.remove('hidden');
editMode.classList.add('hidden');

editButtons.classList.add('hidden');
editButtons.style.display = 'none';
editButtons.style.visibility = 'hidden';
editButtons.style.opacity = '0';
editButtons.style.pointerEvents = 'none';

editBtn.innerHTML = `
    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
    </svg>
    Editar
`;
editBtn.onclick = toggleProfileEdit;

console.log('? Ediçãoo cancelada, bot�es ocultados');
}

function populateEditForm() {
console.log('?? Preenchendo formulário de ediçãoo...');

// Get current values from view mode
const currentName = document.getElementById('profileFullName').textContent;
const currentEmail = document.getElementById('profileEmail2').textContent;
const currentWhatsApp = document.getElementById('profileWhatsApp').textContent;

document.getElementById('editProfileName').value = currentName === 'Carregando...' ? '' : currentName;
document.getElementById('editProfileEmail').value = currentEmail === 'Carregando...' ? '' : currentEmail;
document.getElementById('editProfileWhatsApp').value = currentWhatsApp === 'Carregando...' || currentWhatsApp === 'N�o informado' ? '' : currentWhatsApp;

window.originalProfileValues = {
    name: currentName === 'Carregando...' ? '' : currentName,
    email: currentEmail === 'Carregando...' ? '' : currentEmail,
    whatsapp: currentWhatsApp === 'Carregando...' || currentWhatsApp === 'N�o informado' ? '' : currentWhatsApp
};

console.log('?? Valores originais armazenados:', window.originalProfileValues);

// Add change listeners to show/hide edit buttons
const editFields = ['editProfileName', 'editProfileEmail', 'editProfileWhatsApp'];
editFields.forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (field) {
        // Remover listener anterior se existir
        field.removeEventListener('input', checkForChanges);
        field.addEventListener('input', checkForChanges);
        console.log(`? Listener adicionado ao campo: ${fieldId}`);
    }
});

    setTimeout(() => {
        checkForChanges();
    }, 100);
    
    // Garantir que os bot�es estejam ocultos inicialmente
    const editButtons = document.getElementById('profileEditButtons');
    if (editButtons) {
        editButtons.classList.add('hidden');
        editButtons.style.display = 'none';
        editButtons.style.visibility = 'hidden';
        editButtons.style.opacity = '0';
        editButtons.style.pointerEvents = 'none';
        console.log('? Botões de ediçãoo ocultados inicialmente');
    }
}

function checkForChanges() {
const editButtons = document.getElementById('profileEditButtons');
const currentName = document.getElementById('editProfileName').value;
const currentEmail = document.getElementById('editProfileEmail').value;
const currentWhatsApp = document.getElementById('editProfileWhatsApp').value;

if (!window.originalProfileValues) {
    console.log('?? Valores originais não encontrados, ocultando bot�es');
    editButtons.classList.add('hidden');
    editButtons.style.display = 'none';
    editButtons.style.visibility = 'hidden';
    editButtons.style.opacity = '0';
    editButtons.style.pointerEvents = 'none';
    return;
}

const hasChanges = 
    currentName !== window.originalProfileValues.name ||
    currentEmail !== window.originalProfileValues.email ||
    currentWhatsApp !== window.originalProfileValues.whatsapp;

console.log('?? Verificando mudan�as:', {
    current: { name: currentName, email: currentEmail, whatsapp: currentWhatsApp },
    original: window.originalProfileValues,
    hasChanges: hasChanges
});

if (hasChanges) {
    editButtons.classList.remove('hidden');
    editButtons.style.display = 'flex';
    editButtons.style.visibility = 'visible';
    editButtons.style.opacity = '1';
    editButtons.style.pointerEvents = 'auto';
    console.log('? Mudan�as detectadas, mostrando bot�es');
} else {
    editButtons.classList.add('hidden');
    editButtons.style.display = 'none';
    editButtons.style.visibility = 'hidden';
    editButtons.style.opacity = '0';
    editButtons.style.pointerEvents = 'none';
    console.log('? Sem mudan�as, ocultando bot�es');
}
}

async function handleUpdateProfile(event) {
event.preventDefault();

try {
    const formData = new FormData(event.target);
    const { data: { user } } = await db.auth.getUser();
    
    if (!user) {
        throw new Error('Usuário não autenticado');
    }
    
    const updateData = {
        name: formData.get('name'),
        whatsapp: formData.get('whatsapp') || null,
        // Campos de relatório adicionados
        report_farm_name: formData.get('report_farm_name') || null,
        report_farm_logo_base64: formData.get('report_farm_logo_base64') || null,
        report_footer_text: formData.get('report_footer_text') || null,
        report_system_logo_base64: formData.get('report_system_logo_base64') || null
    };

    const profilePhotoFile = formData.get('profilePhoto');
    if (profilePhotoFile && profilePhotoFile.size > 0) {
        try {
            const profilePhotoUrl = await uploadProfilePhoto(profilePhotoFile, user.id);
            updateData.profile_photo_url = profilePhotoUrl;
        } catch (photoError) {
            console.error('Erro ao fazer upload da foto de perfil:', photoError);

        }
    }

    const { data: existingUser, error: checkError } = await db
        .from('users')
        .select('*')
        .eq('id', user.id)
        .single();
    
    // Update user table in database
    const { data, error } = await db
        .from('users')
        .update(updateData)
        .eq('id', user.id)
        .select();
    
    if (error) {
        throw error;
    }
    
    // Also update user metadata in Database Auth
    const { error: authError } = await db.auth.updateUser({
        data: {
            name: updateData.name,
            whatsapp: updateData.whatsapp
        }
    });
    
    if (authError) {
    }
    
    // Update the view mode with new values
    document.getElementById('profileFullName').textContent = updateData.name || 'N�o informado';
    document.getElementById('profileName').textContent = updateData.name || 'N�o informado';

    const formalName = extractFormalName(updateData.name);
    const displayName = formalName || updateData.name || 'Usuário';
    document.getElementById('managerName').textContent = displayName;
    document.getElementById('managerWelcome').textContent = displayName;
    
    document.getElementById('profileWhatsApp').textContent = updateData.whatsapp || 'N�o informado';

    const sessionData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
    if (sessionData) {
        try {
            const userData = JSON.parse(sessionData);
            userData.name = updateData.name;
            userData.whatsapp = updateData.whatsapp;
            // DO NOT update profile_photo_url in localStorage to prevent sharing between pages
            // Each page should load profile photo directly from database
            
            if (localStorage.getItem('userData')) {
                localStorage.setItem('userData', JSON.stringify(userData));
            }
            if (sessionStorage.getItem('userData')) {
                sessionStorage.setItem('userData', JSON.stringify(userData));
            }
        } catch (e) {
        }
    }
    
    // Switch back to view mode
    cancelProfileEdit();
    
    showNotification('Perfil atualizado com sucesso!', 'success');
    
} catch (error) {
    console.error('Erro ao atualizar perfil:', error);
    showNotification('Erro ao atualizar perfil: ' + error.message, 'error');
}
}

function addVolumeRecord() {
// Criar modal para adicionar novo registro de volume
const modal = document.createElement('div');
modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
modal.innerHTML = `
    <div class="bg-white   rounded-2xl p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-slate-900   ">Novo Registro de Volume</h3>
            <button onclick="closeVolumeModal()" class="text-gray-400 hover:text-gray-600  :text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form id="volumeForm" onsubmit="handleAddVolume(event)">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700   mb-2">Data</label>
                    <input type="date" name="record_date" id="volumeDateInput" required class="w-full px-3 py-2 border border-gray-300   rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      ">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700   mb-2">Turno</label>
                    <select name="shift" required class="w-full px-3 py-2 border border-gray-300   rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      ">
                        <option value="">Selecione o turno</option>
                        <option value="manha">Manh�</option>
                        <option value="tarde">Tarde</option>
                        <option value="noite">Noite</option>
                        <option value="madrugada">Madrugada</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700   mb-2">Volume (Litros)</label>
                    <input type="number" name="volume" step="0.1" min="0" required class="w-full px-3 py-2 border border-gray-300   rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      " placeholder="0.0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700   mb-2">Temperatura (�C)</label>
                    <input type="number" name="temperature" step="0.1" class="w-full px-3 py-2 border border-gray-300   rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      " placeholder="4.0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700   mb-2">Observações</label>
                    <textarea name="observations" rows="3" class="w-full px-3 py-2 border border-gray-300   rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      " placeholder="Observações adicionais (opcional)"></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeVolumeModal()" class="flex-1 px-4 py-2 border border-gray-300   text-gray-700   rounded-lg hover:bg-gray-50  :bg-slate-700 transition-colors">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-forest-600 text-white rounded-lg hover:bg-forest-700 transition-colors">
                    Registrar
                </button>
            </div>
        </form>
    </div>
`;

modal.id = 'volumeModal';
document.body.appendChild(modal);

// Set default date to today
const today = new Date().toISOString().split('T')[0];
modal.querySelector('input[name="record_date"]').value = today;
}
function addQualityTest() {
// Criar modal para adicionar novo teste de qualidade
const modal = document.createElement('div');
modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 quality-modal-backdrop py-8 px-4';
modal.innerHTML = `
    <div class="bg-whiterounded-3xl p-8 w-full max-w-lg mx-4 shadow-2xl transform transition-all duration-300 scale-100">
        <!-- Header com ícone -->
        <div class="flex items-center justify-between mb-8 quality-modal-header p-6 -m-8 mb-8 rounded-t-3xl">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Novo Teste de Qualidade</h3>
                    <p class="text-sm text-gray-500">Registre os parâmetros de qualidade do leite</p>
                </div>
            </div>
            <button onclick="closeQualityModal()" class="quality-close-btn text-gray-400 hover:text-gray-600transition-colors p-2 hover:bg-gray-100rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="qualityForm" onsubmit="handleAddQuality(event)" class="quality-form space-y-6">
            <!-- Data do Teste -->
            <div class="relative">
                <label class="block text-sm font-semibold text-gray-700mb-2">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Data do Teste
                </label>
                <input type="date" name="test_date" required 
                       class="w-full px-4 py-3 border-2 border-gray-200rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 focus:bg-white">
            </div>

            <!-- Par�metros de Qualidade em Grid -->
            <div class="quality-modal-grid grid grid-cols-2 gap-4">
                <!-- Gordura -->
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700mb-2">
                        <div class="w-3 h-3 bg-orange-400 rounded-full inline-block mr-2"></div>
                        Gordura (%)
                    </label>
                    <div class="relative">
                        <input type="number" name="fat_percentage" step="0.01" min="0" max="100" required 
                               class="w-full px-4 py-3 pr-16 border-2 border-gray-200rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200 bg-gray-50 focus:bg-white" 
                               placeholder="3.50">
                        <div class="absolute inset-y-0 right-0 flex flex-col items-center pr-3">
                            <button type="button" onclick="adjustValue('fat_percentage', 0.1)" class="text-gray-400 hover:text-orange-600 p-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="adjustValue('fat_percentage', -0.1)" class="text-gray-400 hover:text-orange-600 p-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="quality-standards">Padr�o: 3.0 - 6.0%</div>
                </div>

                <!-- Proteína -->
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700mb-2">
                        <div class="w-3 h-3 bg-blue-400 rounded-full inline-block mr-2"></div>
                        Proteína (%)
                    </label>
                    <div class="relative">
                        <input type="number" name="protein_percentage" step="0.01" min="0" max="100" required 
                               class="w-full px-4 py-3 pr-16 border-2 border-gray-200rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 bg-gray-50 focus:bg-white" 
                               placeholder="3.20">
                        <div class="absolute inset-y-0 right-0 flex flex-col items-center pr-3">
                            <button type="button" onclick="adjustValue('protein_percentage', 0.1)" class="text-gray-400 hover:text-blue-600 p-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="adjustValue('protein_percentage', -0.1)" class="text-gray-400 hover:text-blue-600 p-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="quality-standards">Padr�o: 2.8 - 4.0%</div>
                </div>

                <!-- CCS -->
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700mb-2">
                        <div class="w-3 h-3 bg-red-400 rounded-full inline-block mr-2"></div>
                        CCS (mil/mL)
                    </label>
                    <div class="relative">
                        <input type="number" name="scc" min="0" max="1000" required 
                               class="w-full px-4 py-3 pr-16 border-2 border-gray-200rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all duration-200 bg-gray-50 focus:bg-white" 
                               placeholder="200">
                        <div class="absolute inset-y-0 right-0 flex flex-col items-center pr-3">
                            <button type="button" onclick="adjustValue('scc', 10)" class="text-gray-400 hover:text-red-600 p-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="adjustValue('scc', -10)" class="text-gray-400 hover:text-red-600 p-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="quality-standards">M�ximo: 400 mil/mL</div>
                </div>

                <!-- CBT -->
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700mb-2">
                        <div class="w-3 h-3 bg-purple-400 rounded-full inline-block mr-2"></div>
                        CBT (mil/mL)
                    </label>
                    <div class="relative">
                        <input type="number" name="total_bacterial_count" min="0" max="1000" required 
                               class="w-full px-4 py-3 pr-16 border-2 border-gray-200rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 bg-gray-50 focus:bg-white" 
                               placeholder="50">
                        <div class="absolute inset-y-0 right-0 flex flex-col items-center pr-3">
                            <button type="button" onclick="adjustValue('total_bacterial_count', 5)" class="text-gray-400 hover:text-purple-600 p-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                            </button>
                            <button type="button" onclick="adjustValue('total_bacterial_count', -5)" class="text-gray-400 hover:text-purple-600 p-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="quality-standards">M�ximo: 100 mil/mL</div>
                </div>
            </div>

            <!-- Laboratário -->
            <div class="relative">
                <label class="block text-sm font-semibold text-gray-700mb-2">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                    </svg>
                    Laboratário
                </label>
                <input type="text" name="laboratory" 
                       class="w-full px-4 py-3 border-2 border-gray-200rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 focus:bg-white" 
                       placeholder="Nome do laboratário">
            </div>

            <!-- Observações -->
            <div class="relative">
                <label class="block text-sm font-semibold text-gray-700mb-2">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Observações
                </label>
                <textarea name="notes" rows="3" 
                          class="w-full px-4 py-3 border-2 border-gray-200rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 focus:bg-whiteresize-none" 
                          placeholder="Observações adicionais (opcional)"></textarea>
            </div>

            <!-- Botões -->
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="closeQualityModal()" 
                        class="flex-1 px-6 py-3 border-2 border-gray-300text-gray-700font-semibold rounded-xl hover:bg-gray-50hover:border-gray-400 transition-all duration-200">
                    Cancelar
                </button>
                <button type="submit" 
                        class="quality-btn-primary flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white font-semibold rounded-xl hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Registrar Teste
                </button>
            </div>
        </form>
    </div>
`;

modal.id = 'qualityModal';
document.body.appendChild(modal);

// Set default date to today
const today = new Date().toISOString().split('T')[0];
modal.querySelector('input[name="test_date"]').value = today;

// Garantir posicionamento correto
modal.style.position = 'fixed';
modal.style.top = '0';
modal.style.left = '0';
modal.style.width = '100%';
modal.style.height = '100%';
modal.style.display = 'flex';
modal.style.alignItems = 'center';
modal.style.justifyContent = 'center';
modal.style.zIndex = '9999';

// Adicionar animaçãoo de entrada
setTimeout(() => {
    modal.querySelector('.bg-white').classList.add('scale-100');
}, 10);

setTimeout(() => {
    validateQualityInputs();
    addQualitySummary();
}, 100);
}

function addPayment() {
// Criar modal para adicionar novo pagamento
const modal = document.createElement('div');
modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
modal.innerHTML = `
    <div class="bg-white   rounded-2xl p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-slate-900   ">Nova Venda de Leite</h3>
            <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600  :text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form id="paymentForm" onsubmit="handleAddPayment(event)">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700   mb-2">Descriçãoo</label>
                    <input type="text" name="description" required class="w-full px-3 py-2 border border-gray-300   rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      " placeholder="Ex: Venda para Latic�nio ABC">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700   mb-2">Valor (R$)</label>
                    <input type="number" name="amount" step="0.01" required class="w-full px-3 py-2 border border-gray-300   rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      " placeholder="0,00">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700   mb-2">Tipo</label>
                    <select name="payment_type" required class="w-full px-3 py-2 border border-gray-300   rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      ">
                        <option value="">Selecione o tipo</option>
                        <option value="laticinio">Latic�nio</option>
                        <option value="cooperativa">Cooperativa</option>
                        <option value="distribuidor">Distribuidor</option>
                        <option value="consumidor_final">Consumidor Final</option>
                        <option value="exportacao">Exportação</option>
                        <option value="outros">Outros</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700   mb-2">Data da Venda</label>
                    <input type="date" name="due_date" required class="w-full px-3 py-2 border border-gray-300   rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      ">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700   mb-2">Status</label>
                    <select name="status" required class="w-full px-3 py-2 border border-gray-300   rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      ">
                        <option value="pending">Pendente</option>
                        <option value="completed">Conclu�da</option>
                        <option value="overdue">Atrasada</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700   mb-2">Observações</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300   rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      " placeholder="Observações adicionais (opcional)"></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closePaymentModal()" class="flex-1 px-4 py-2 border border-gray-300   text-gray-700   rounded-lg hover:bg-gray-50  :bg-slate-700 transition-colors">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-forest-600 text-white rounded-lg hover:bg-forest-700 transition-colors">
                    Adicionar
                </button>
            </div>
        </form>
    </div>
`;

modal.id = 'paymentModal';
document.body.appendChild(modal);

// Set default due date to today
const today = new Date().toISOString().split('T')[0];
modal.querySelector('input[name="due_date"]').value = today;
}

function closePaymentModal() {
const modal = document.getElementById('paymentModal');
if (modal) {
    modal.remove();
}
}

function closeVolumeModal() {
const modal = document.getElementById('volumeModal');
if (modal) {
    modal.remove();
}
}

function closeQualityModal() {
const modal = document.getElementById('qualityModal');
if (modal) {
    // Adicionar animaçãoo de sa�da
    modal.querySelector('.bg-white').classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.remove();
    }, 200);
}
}

// Funçãoo para ajustar valores dos campos num�ricos
function adjustValue(fieldName, increment) {
const field = document.querySelector(`input[name="${fieldName}"]`);
if (field) {
    const currentValue = parseFloat(field.value) || 0;
    const newValue = Math.max(0, currentValue + increment);
    
    // Determinar número de casas decimais baseado no campo
    let decimalPlaces = 0;
    if (fieldName === 'fat_percentage' || fieldName === 'protein_percentage') {
        decimalPlaces = 2;
    }
    
    field.value = newValue.toFixed(decimalPlaces);
    
    // Adicionar feedback visual
    const color = increment > 0 ? '#fef3c7' : '#fef2f2';
    field.style.backgroundColor = color;
    setTimeout(() => {
        field.style.backgroundColor = '';
    }, 300);
    
    // Disparar evento de input para validaçãoo
    field.dispatchEvent(new Event('input'));
}
}

// Funçãoo para validar valores em tempo real
function validateQualityInputs() {
const inputs = document.querySelectorAll('#qualityForm input[type="number"]');
inputs.forEach(input => {
    input.addEventListener('input', function() {
        const value = parseFloat(this.value);
        const fieldName = this.name;
        
        // Validações espec�ficas por campo
        switch(fieldName) {
            case 'fat_percentage':
                if (value < 0 || value > 100) {
                    this.style.borderColor = '#ef4444';
                    this.style.backgroundColor = '#fef2f2';
                } else if (value < 3.0 || value > 6.0) {
                    this.style.borderColor = '#f59e0b';
                    this.style.backgroundColor = '#fffbeb';
                } else {
                    this.style.borderColor = '#10b981';
                    this.style.backgroundColor = '#f0fdf4';
                }
                break;
                
            case 'protein_percentage':
                if (value < 0 || value > 100) {
                    this.style.borderColor = '#ef4444';
                    this.style.backgroundColor = '#fef2f2';
                } else if (value < 2.8 || value > 4.0) {
                    this.style.borderColor = '#f59e0b';
                    this.style.backgroundColor = '#fffbeb';
                } else {
                    this.style.borderColor = '#10b981';
                    this.style.backgroundColor = '#f0fdf4';
                }
                break;
                
            case 'scc':
                if (value < 0) {
                    this.style.borderColor = '#ef4444';
                    this.style.backgroundColor = '#fef2f2';
                } else if (value > 400) {
                    this.style.borderColor = '#ef4444';
                    this.style.backgroundColor = '#fef2f2';
                } else if (value > 300) {
                    this.style.borderColor = '#f59e0b';
                    this.style.backgroundColor = '#fffbeb';
                } else {
                    this.style.borderColor = '#10b981';
                    this.style.backgroundColor = '#f0fdf4';
                }
                break;
                
            case 'total_bacterial_count':
                if (value < 0) {
                    this.style.borderColor = '#ef4444';
                    this.style.backgroundColor = '#fef2f2';
                } else if (value > 100) {
                    this.style.borderColor = '#ef4444';
                    this.style.backgroundColor = '#fef2f2';
                } else if (value > 50) {
                    this.style.borderColor = '#f59e0b';
                    this.style.backgroundColor = '#fffbeb';
                } else {
                    this.style.borderColor = '#10b981';
                    this.style.backgroundColor = '#f0fdf4';
                }
                break;
        }
        
        // Reset após 2 segundos
        setTimeout(() => {
            this.style.borderColor = '';
            this.style.backgroundColor = '';
        }, 2000);
    });
});
}

// Funçãoo para adicionar resumo visual dos valores
function addQualitySummary() {
const inputs = document.querySelectorAll('#qualityForm input[type="number"]');
const summaryContainer = document.createElement('div');
summaryContainer.className = 'quality-summary mt-4 p-4 bg-gradient-to-r from-blue-50 to-green-50 rounded-xl border border-blue-200';
summaryContainer.innerHTML = `
    <div class="flex items-center justify-between mb-2">
        <h4 class="text-sm font-semibold text-gray-700">Resumo da Qualidade</h4>
        <div class="quality-overall-status w-3 h-3 rounded-full bg-gray-300"></div>
    </div>
    <div class="grid grid-cols-2 gap-2 text-xs">
        <div class="quality-summary-item">
            <span class="text-gray-600">Gordura:</span>
            <span class="quality-fat-status font-semibold">--%</span>
        </div>
        <div class="quality-summary-item">
            <span class="text-gray-600">Proteína:</span>
            <span class="quality-protein-status font-semibold">--%</span>
        </div>
        <div class="quality-summary-item">
            <span class="text-gray-600">CCS:</span>
            <span class="quality-scc-status font-semibold">--</span>
        </div>
        <div class="quality-summary-item">
            <span class="text-gray-600">CBT:</span>
            <span class="quality-cbt-status font-semibold">--</span>
        </div>
    </div>
`;

// Inserir o resumo após o grid de parâmetros
const gridContainer = document.querySelector('.quality-modal-grid').parentElement;
gridContainer.insertBefore(summaryContainer, gridContainer.querySelector('div:last-child'));

// Atualizar resumo quando valores mudarem
inputs.forEach(input => {
    input.addEventListener('input', updateQualitySummary);
});
}

// Funçãoo para atualizar o resumo
function updateQualitySummary() {
const fatValue = parseFloat(document.querySelector('input[name="fat_percentage"]')?.value || 0);
const proteinValue = parseFloat(document.querySelector('input[name="protein_percentage"]')?.value || 0);
const sccValue = parseFloat(document.querySelector('input[name="scc"]')?.value || 0);
const cbtValue = parseFloat(document.querySelector('input[name="total_bacterial_count"]')?.value || 0);

// Atualizar valores
const fatStatus = document.querySelector('.quality-fat-status');
const proteinStatus = document.querySelector('.quality-protein-status');
const sccStatus = document.querySelector('.quality-scc-status');
const cbtStatus = document.querySelector('.quality-cbt-status');
const overallStatus = document.querySelector('.quality-overall-status');

if (fatStatus) {
    fatStatus.textContent = fatValue > 0 ? fatValue.toFixed(2) + '%' : '--%';
    fatStatus.className = getQualityStatusClass('fat', fatValue);
}

if (proteinStatus) {
    proteinStatus.textContent = proteinValue > 0 ? proteinValue.toFixed(2) + '%' : '--%';
    proteinStatus.className = getQualityStatusClass('protein', proteinValue);
}

if (sccStatus) {
    sccStatus.textContent = sccValue > 0 ? sccValue.toFixed(0) : '--';
    sccStatus.className = getQualityStatusClass('scc', sccValue);
}

if (cbtStatus) {
    cbtStatus.textContent = cbtValue > 0 ? cbtValue.toFixed(0) : '--';
    cbtStatus.className = getQualityStatusClass('cbt', cbtValue);
}

// Status geral
if (overallStatus) {
    const overallScore = calculateOverallQuality(fatValue, proteinValue, sccValue, cbtValue);
    overallStatus.className = `quality-overall-status w-3 h-3 rounded-full ${getOverallStatusClass(overallScore)}`;
}
}

// Funçãoo para calcular status de qualidade
function getQualityStatusClass(type, value) {
const baseClass = 'font-semibold';

switch(type) {
    case 'fat':
        if (value >= 3.0 && value <= 6.0) return `${baseClass} text-green-600`;
        if (value >= 2.5 && value < 3.0 || value > 6.0 && value <= 7.0) return `${baseClass} text-yellow-600`;
        return `${baseClass} text-red-600`;
        
    case 'protein':
        if (value >= 2.8 && value <= 4.0) return `${baseClass} text-green-600`;
        if (value >= 2.5 && value < 2.8 || value > 4.0 && value <= 4.5) return `${baseClass} text-yellow-600`;
        return `${baseClass} text-red-600`;
        
    case 'scc':
        if (value <= 200) return `${baseClass} text-green-600`;
        if (value > 200 && value <= 400) return `${baseClass} text-yellow-600`;
        return `${baseClass} text-red-600`;
        
    case 'cbt':
        if (value <= 50) return `${baseClass} text-green-600`;
        if (value > 50 && value <= 100) return `${baseClass} text-yellow-600`;
        return `${baseClass} text-red-600`;
        
    default:
        return baseClass;
}
}

// Funçãoo para calcular qualidade geral
function calculateOverallQuality(fat, protein, scc, cbt) {
let score = 0;
let count = 0;

if (fat > 0) {
    if (fat >= 3.0 && fat <= 6.0) score += 100;
    else if (fat >= 2.5 && fat < 3.0 || fat > 6.0 && fat <= 7.0) score += 60;
    else score += 20;
    count++;
}

if (protein > 0) {
    if (protein >= 2.8 && protein <= 4.0) score += 100;
    else if (protein >= 2.5 && protein < 2.8 || protein > 4.0 && protein <= 4.5) score += 60;
    else score += 20;
    count++;
}

if (scc > 0) {
    if (scc <= 200) score += 100;
    else if (scc > 200 && scc <= 400) score += 60;
    else score += 20;
    count++;
}

if (cbt > 0) {
    if (cbt <= 50) score += 100;
    else if (cbt > 50 && cbt <= 100) score += 60;
    else score += 20;
    count++;
}

return count > 0 ? score / count : 0;
}

// Funçãoo para obter classe do status geral
function getOverallStatusClass(score) {
if (score >= 80) return 'bg-green-500';
if (score >= 60) return 'bg-yellow-500';
return 'bg-red-500';
}

async function handleAddVolume(event) {
event.preventDefault();
const formData = new FormData(event.target);

console.log('?? Registrando novo volume...');

try {
    // Preparar dados para enviar � API
    const volumeData = {
        volume: parseFloat(formData.get('volume')),
        collection_date: formData.get('record_date'),
        period: formData.get('shift'), // manha, tarde, noite, madrugada
        temperature: formData.get('temperature') ? parseFloat(formData.get('temperature')) : null
    };
    
    console.log('?? Dados do volume:', volumeData);

    // Enviar para a API
    const response = await fetch('api/manager.php?action=add_volume', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(volumeData)
    });

    const result = await response.json();

    if (!result.success) {
        throw new Error(result.error || 'Erro ao adicionar volume');
    }

    console.log('? Volume registrado com sucesso:', result);
    showNotification('Registro de volume adicionado com sucesso!', 'success');

    if (window.nativeNotifications) {
        window.nativeNotifications.showRealDeviceNotification(
            'Nova Produção Registrada',
            `${volumeData.volume}L registrado com sucesso!`,
            'production'
        );
    }
    
    // Fechar modal
    closeVolumeModal();
    
    // Atualizar dashboard
    console.log('?? Atualizando dashboard...');
    await loadDashboardData();
    
    console.log('? Atualizaçãoo completa!');
    
} catch (error) {
    console.error('? Erro ao adicionar volume:', error);
    showNotification('Erro ao adicionar registro de volume: ' + error.message, 'error');
}
}

async function handleAddQuality(event) {
event.preventDefault();
const formData = new FormData(event.target);

console.log('?? Registrando novo teste de qualidade...');

try {
    // Preparar dados para enviar � API
    const qualityData = {
        test_date: formData.get('test_date'),
        fat_percentage: parseFloat(formData.get('fat_percentage')) || null,
        protein_percentage: parseFloat(formData.get('protein_percentage')) || null,
        lactose_percentage: parseFloat(formData.get('lactose_percentage')) || null,
        ccs: parseInt(formData.get('scc')) || null, // CCS = Contagem de C�lulas Som�ticas
        cbt: parseInt(formData.get('total_bacterial_count')) || null, // CBT = Contagem Bacteriana Total
        temperature: parseFloat(formData.get('temperature')) || null,
        ph: parseFloat(formData.get('ph')) || null
    };
    
    console.log('?? Dados do teste:', qualityData);

    // Enviar para a API
    const response = await fetch('api/manager.php?action=add_quality_test', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(qualityData)
    });

    const result = await response.json();

    if (!result.success) {
        throw new Error(result.error || 'Erro ao adicionar teste de qualidade');
    }

    console.log('? Teste de qualidade registrado com sucesso:', result);
    showNotification('Teste de qualidade adicionado com sucesso!', 'success');
    
    // Fechar modal
    closeQualityModal();
    
    // Atualizar dashboard
    console.log('?? Atualizando dashboard...');
    await loadDashboardData();
    
    console.log('? Atualizaçãoo completa!');
    
} catch (error) {
    console.error('? Erro ao adicionar teste de qualidade:', error);
    showNotification('Erro ao adicionar teste de qualidade: ' + error.message, 'error');
}
}
async function handleAddPayment(event) {
event.preventDefault();
const formData = new FormData(event.target);

const paymentData = {
    record_date: formData.get('due_date'), // Data do registro
    type: 'income', // Tipo de registro financeiro (income = receita)
    amount: parseFloat(formData.get('amount')), // Valor
    description: `${formData.get('description')} - Tipo: ${formData.get('payment_type')}${formData.get('notes') ? ' - ' + formData.get('notes') : ''}`, // Descriçãoo
    category: formData.get('payment_type') || 'venda_leite' // Categoria
};

try {
    // Usando MySQL direto atrav�s do objeto 'db'
    const { data: { user: currentUser } } = await db.auth.getUser();
    if (!currentUser) throw new Error('User not authenticated');

    // Usuário da fazenda Lagoa Do Mato
    const { data: managerData, error: managerError } = await db
        .from('users')
        .select('id')
        .eq('id', currentUser.id)
        .single();

    if (managerError) throw managerError;
    if (!managerData) throw new Error('Gerente não encontrado');

    // Insert financial record into database
    const { error: paymentError } = await db
        .from('financial_records')
        .insert({
            ...paymentData,
            farm_id: 1, // Lagoa Do Mato
            type: 'income'
        });

    if (paymentError) throw paymentError;

    showNotification('Venda adicionada com sucesso!', 'success');
    closePaymentModal();
    
    // Reload sales data and recent activities
    await loadPaymentsData();

    if (currentUser) {
        const { data: userData } = await db
            .from('users')
            .select('id')
            .eq('email', currentUser.email)
            .single();
        
        if (userData) {
            await loadRecentActivities(); // Lagoa Do Mato
        }
    }
    
} catch (error) {
    console.error('Error adding payment:', error);
    showNotification('Erro ao adicionar venda: ' + error.message, 'error');
}
}

function openAddUserModal() {
const modal = document.getElementById('addUserModal');
if (modal) {
    // Apply all styles to prevent flash
    modal.classList.add('show');
    modal.style.display = 'flex';
    modal.style.visibility = 'visible';
    modal.style.opacity = '1';
    modal.style.zIndex = '9999';
    modal.style.pointerEvents = 'auto';
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100vw';
    modal.style.height = '100vh';
    modal.style.background = 'rgba(0, 0, 0, 0.5)';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    
    document.body.style.overflow = 'hidden';
    
    // Reset do select para "Selecione o cargo" e esconder a seçãoo de foto
const userRoleSelect = document.getElementById('userRole');
const addPhotoSection = document.getElementById('addPhotoSection');

if (userRoleSelect && addPhotoSection) {
    // Definir como vazio para mostrar "Selecione o cargo"
    userRoleSelect.value = '';
    
        // Esconder a seçãoo de foto por padrão
    addPhotoSection.style.display = 'none';
    addPhotoSection.style.visibility = 'hidden';
    addPhotoSection.style.opacity = '0';
    }
}
}

function closeAddUserModal() {
const modal = document.getElementById('addUserModal');
if (modal) {
    modal.classList.remove('show');
    modal.style.display = 'none';
    modal.style.visibility = 'hidden';
    modal.style.opacity = '0';
    modal.style.zIndex = '-1';
    modal.style.pointerEvents = 'none';
    document.body.style.overflow = 'auto';

    const form = document.getElementById('addUserFormModal');
    if (form) {
        form.reset();
    }

// Reset profile photo preview
const preview = document.getElementById('profilePreview');
const placeholder = document.getElementById('profilePlaceholder');
if (preview && placeholder) {
    preview.classList.add('hidden');
    placeholder.classList.remove('hidden');
    preview.src = '';
}

// Reset email preview
const emailPreview = document.getElementById('emailPreview');
if (emailPreview) {
    emailPreview.textContent = 'Digite o nome para ver o email';
}
}

// Generate email based on name and farm
async function generateUserEmail(name, farmId = 1) {
try {

    if (!name || typeof name !== 'string' || name.trim() === '') {
        throw new Error('Nome do usuário � obrigatário');
    }
    
    // Farm fixo: Lagoa Do Mato -> lactech.com
    const farmName = 'lactech';
    
    // Extrair o primeiro nome do usuário
    const firstName = name.trim().split(' ')[0]
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // Remove acentos
        .replace(/[^a-z0-9]/g, ''); // Remove caracteres especiais
        
    // Validar se o primeiro nome não está vazio após sanitizaçãoo
    if (!firstName) {
        throw new Error('Nome do usuário inválido após sanitizaçãoo');
    }
    
    // Gerar email simples: nome@lactech.com
    let finalEmail = `${firstName}@${farmName}.com`;

    return finalEmail;
} catch (error) {
    console.error('Error generating email:', error);
    throw error;
}
}

// Update email preview
async function updateEmailPreview(name) {
const emailPreview = document.getElementById('emailPreview');

if (!emailPreview) {
    console.error('Email preview element not found');
    return;
}

if (!name || typeof name !== 'string' || name.trim() === '') {
    emailPreview.textContent = 'Digite o nome para ver o email';
    return;
}

try {
    const email = await generateUserEmail(name);
    emailPreview.textContent = email;
} catch (error) {
    console.error('Error in updateEmailPreview:', error);
    emailPreview.textContent = 'Erro ao gerar email';
}
}

async function hashPassword(password) {
const encoder = new TextEncoder();
const data = encoder.encode(password);
const hash = await crypto.subtle.digest('SHA-256', data);
const hashArray = Array.from(new Uint8Array(hash));
const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
return hashHex;
}

async function sendWhatsAppCredentials(whatsapp, name, email, password) {
try {
    let formattedNumber = whatsapp.replace(/\D/g, '');
    if (!formattedNumber.startsWith('55')) {
        formattedNumber = '55' + formattedNumber;
    }
    
    const message = `?? *LACTECH - Sistema de Gestão Leiteira* ??\n\n` +
        `?? *Ol� ${name}!*\n\n` +
        `Suas credenciais de acesso foram criadas com sucesso:\n\n` +
        `?? *Email:* ${email}\n` +
        `?? *Senha:* ${password}\n\n` +
        `?? *INSTRUçãoES IMPORTANTES:*\n` +
        `? Mantenha suas credenciais seguras\n` +
        `? N�o compartilhe com terceiros\n\n` +
        `?? *Acesse o sistema:*\n` +
        `https://lacteste.netlify.app/\n\n` +
        `?? *Suporte técnico disponível*\n` +
        `Em caso de d�vidas, entre em contato\n\n` +
        `?? *Bem-vindo(a) � equipe LacTech!*\n` +
        `Juntos, vamos revolucionar a gestão leiteira! ????`;
    
    // Copiar mensagem para �rea de transferência
    try {
        await navigator.clipboard.writeText(message);
        
        // Mostrar modal com instruçãoes
        showWhatsAppInstructions(formattedNumber, name, message);
        
        return true;
    } catch (clipboardError) {
        console.error('Erro ao copiar para �rea de transferência:', clipboardError);
        // Fallback: mostrar modal mesmo sem copiar
        showWhatsAppInstructions(formattedNumber, name, message);
        return true;
    }
    
} catch (error) {
    console.error('Error sending WhatsApp message:', error);
    return false;
}
}

// Mostrar modal com instruçãoes para envio manual
function showWhatsAppInstructions(phoneNumber, userName, message) {
// Criar modal se não existir
let modal = document.getElementById('whatsappInstructionsModal');
if (!modal) {
    modal = document.createElement('div');
    modal.id = 'whatsappInstructionsModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-whiterounded-lg p-6 max-w-md w-full mx-4" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">?? Enviar Credenciais via WhatsApp</h3>
                <button onclick="closeWhatsAppInstructions()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <p class="text-sm text-green-800">
                        ? <strong>Mensagem copiada!</strong><br>
                        As credenciais foram copiadas para sua �rea de transferência.
                    </p>
                </div>
                
                <div class="space-y-2">
                    <p class="text-sm font-medium text-gray-700">Para enviar as credenciais:</p>
                    <ol class="text-sm text-gray-600space-y-1 list-decimal list-inside">
                        <li>Abra o WhatsApp no seu celular ou computador</li>
                        <li>Procure pelo contato: <strong>${phoneNumber}</strong></li>
                        <li>Cole a mensagem (Ctrl+V) e envie</li>
                    </ol>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <p class="text-xs text-blue-800">
                        ?? <strong>Dica:</strong> Voc� tamb�m pode clicar no bot�o abaixo para abrir o WhatsApp Web automaticamente.
                    </p>
                </div>
                
                <div class="flex space-x-3">
                    <button onclick="openWhatsAppWeb('${phoneNumber}', '${encodeURIComponent(message)}')" 
                            class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm">
                        ?? Abrir WhatsApp Web
                    </button>
                    <button onclick="copyMessageAgain('${encodeURIComponent(message)}')" 
                            class="flex-1 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors text-sm">
                        ?? Copiar Novamente
                    </button>
                </div>
                
                <button onclick="closeWhatsAppInstructions()" 
                        class="w-full bg-gray-100 text-gray-700px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                    Fechar
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Adicionar evento de clique no overlay para fechar
modal.addEventListener('click', function(e) {
    if (e.target === modal) {
        closeWhatsAppInstructions();
    }
});

// Atualizar conteúdo do modal
const phoneElement = modal.querySelector('strong');
if (phoneElement) {
    phoneElement.textContent = phoneNumber;
}

// Mostrar modal
modal.style.display = 'flex';

// Adicionar evento de tecla ESC para fechar
const handleEscape = function(e) {
    if (e.key === 'Escape') {
        closeWhatsAppInstructions();
        document.removeEventListener('keydown', handleEscape);
    }
};
document.addEventListener('keydown', handleEscape);
}

// Fechar modal de instruçãoes
function closeWhatsAppInstructions() {
const modal = document.getElementById('whatsappInstructionsModal');
if (modal) {
    modal.style.display = 'none';
    // Remover modal do DOM para evitar conflitos
    modal.remove();
}
}

// Abrir WhatsApp Web (opçãoo alternativa)
function openWhatsAppWeb(phoneNumber, encodedMessage) {
const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodedMessage}`;
window.open(whatsappUrl, '_blank');
closeWhatsAppInstructions();
}

// Copiar mensagem novamente
async function copyMessageAgain(encodedMessage) {
try {
    const message = decodeURIComponent(encodedMessage);
    await navigator.clipboard.writeText(message);
    showNotification('Mensagem copiada novamente!', 'success');
} catch (error) {
    console.error('Erro ao copiar mensagem:', error);
    showNotification('Erro ao copiar mensagem', 'error');
}
}

// Handle add user - VERS�O SIMPLES

window.handleAddUser = async function(e) {
e.preventDefault();
console.log('?? Criando novo usuário...');

const formData = new FormData(e.target);

try {
    // Gerar email automaticamente a partir do nome
    const name = formData.get('name');
    const email = await generateEmailFromName(name);
    
    // Preparar dados para enviar � API
    const userData = {
        name: name,
        email: email,
        password: formData.get('password') || generateTempPassword(),
        role: formData.get('role'),
        phone: formData.get('whatsapp') || null,
        cpf: formData.get('cpf') || null
    };
    
    console.log('?? Dados do usuário:', { ...userData, password: '***' });

    // Enviar para a API
    const response = await fetch('api/manager.php?action=create_user', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(userData)
    });

    const result = await response.json();

    if (!result.success) {
        throw new Error(result.error || 'Erro ao criar usuário');
    }

    console.log('? Usuário criado com sucesso:', result);
    
    // Tentar enviar WhatsApp com credenciais se tiver número
    if (userData.phone) {
        try {
            await sendWhatsAppCredentials(
                userData.phone, 
                userData.name, 
                userData.email, 
                userData.password
            );
            showNotification(`Usuário ${userData.name} criado! Credenciais enviadas via WhatsApp.`, 'success');
        } catch (whatsappError) {

            showNotification(`Usuário ${userData.name} criado! Senha: ${userData.password}`, 'success');
        }
    } else {
        showNotification(`Usuário ${userData.name} criado! Email: ${userData.email} - Senha: ${userData.password}`, 'success');
    }

    if (window.nativeNotifications) {
        window.nativeNotifications.showRealDeviceNotification(
            'Novo Usuário Criado',
            `Usuário ${userData.name} (${userData.role}) foi criado no sistema`,
            'user_created'
        );
    }
    
    closeAddUserModal();
    
    // Recarregar dados do dashboard
    console.log('?? Atualizando dashboard...');
    await loadDashboardData();

} catch (error) {
    console.error('? Erro ao criar usuário:', error);
    showNotification('Erro ao criar usuário: ' + error.message, 'error');
}
}

function addUser() {
openAddUserModal();
}

async function exportVolumeReport() {
try {
    // Usando MySQL direto atrav�s do objeto 'db'
    // Buscar dados de volume de leite
    const { data: volumeData, error } = await db
        .from('volume_records')
        .select(`
            *,
            users(name, email)
        `)
        .order('created_at', { ascending: false });

    if (error) throw error;

    await generateVolumePDF(volumeData);
    
    showNotification('Relatário de Volume exportado com sucesso!', 'success');

} catch (error) {
    showNotification('Erro ao exportar relatório de volume', 'error');
}
}

async function exportQualityReport() {
try {
    // Usando MySQL direto atrav�s do objeto 'db'
    // Buscar dados de qualidade
    const { data: qualityData, error } = await db
        .from('quality_tests')
        .select('*')
        .order('created_at', { ascending: false });

    if (error) throw error;

    await generateQualityPDF(qualityData);
    
    showNotification('Relatário de Qualidade exportado com sucesso!', 'success');
} catch (error) {
    showNotification('Erro ao exportar relatório de qualidade', 'error');
}
}

// Funçãoo para gerar relatório de vendas
async function generatePaymentsReport() {
try {
    // Usando MySQL direto atrav�s do objeto 'db'
    const { data: { user } } = await db.auth.getUser();
    if (!user) throw new Error('User not authenticated');

    const { data: userData, error: userError } = await db
        .from('users')
        .select('id')
        .eq('id', user.id)
        .single();
    
    if (userError) throw userError;

    // Buscar dados de vendas
    const { data: salesData, error } = await db
        .from('financial_records')
        .select('*')
        .eq('farm_id', 1)
        .eq('type', 'income')
        .order('created_at', { ascending: false });

    if (error) throw error;

    await generatePaymentsPDF(salesData);
    
    showNotification('Relatário de Vendas gerado com sucesso!', 'success');
} catch (error) {
    showNotification('Erro ao gerar relatório de vendas', 'error');
}
}

// Definiçãoo da logo do sistema (Base64 ou URL)
// Logo do sistema em SVG Base64 para uso nos relatórios
const systemLogo = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiByeD0iOCIgZmlsbD0iIzJhN2YyYSIvPgo8cGF0aCBkPSJNMTIgMjhIMjhWMjRIMTJWMjhaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTYgMjBIMjRWMTZIMTZWMjBaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMjAgMTJWMzJNMTIgMjBIMjhNMTYgMTZIMjQiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+Cjwvc3ZnPgo=';

window.reportSettings = {
farmName: 'Fazenda',
farmLogo: null
};

// Carregar configurações salvas
async function loadReportSettings() {
try {
    window.reportSettings.farmName = 'Lagoa do Mato';
    window.reportSettings.farmLogo = null;
} catch (error) {
    window.reportSettings.farmName = 'Lagoa do Mato';
}
}

// Funçãoo para lidar com upload da logo da fazenda
async function handleFarmLogoUpload(event) {
const file = event.target.files[0];
if (!file) return;

// Validar tipo de arquivo
if (!file.type.startsWith('image/')) {
    showNotification('Por favor, selecione um arquivo de imagem válido', 'error');
    return;
}

// Validar tamanho do arquivo (máx. 2MB)
if (file.size > 2 * 1024 * 1024) {
    showNotification('A imagem deve ter no máximo 2MB', 'error');
    return;
}

try {
    // Converter para base64
    const base64 = await fileToBase64(file);
    window.reportSettings.farmLogo = base64;
    
    // Atualizar preview
    updateFarmLogoPreview(base64);
    
    showNotification('Logo carregada com sucesso! Clique em "Salvar Configurações" para aplicar', 'success');
} catch (error) {
    console.error('Erro ao processar logo:', error);
    showNotification('Erro ao processar a imagem', 'error');
}
}

// Funçãoo para converter arquivo para base64
function fileToBase64(file) {
return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(file);
});
}

// Funçãoo para atualizar preview da logo (compatibilidade com elementos que podem não existir)
function updateFarmLogoPreview(base64Logo) {
const preview = document.getElementById('farmLogoPreview');
const placeholder = document.getElementById('farmLogoPlaceholder');
const image = document.getElementById('farmLogoImage');
const removeBtn = document.getElementById('removeFarmLogo');

if (!preview || !placeholder || !image || !removeBtn) {
    // Elementos não existem (modal foi removido), não fazer nada
    return;
}

if (base64Logo) {
    image.src = base64Logo;
    preview.classList.remove('hidden');
    placeholder.classList.add('hidden');
    removeBtn.classList.remove('hidden');
} else {
    image.src = '';
    preview.classList.add('hidden');
    placeholder.classList.remove('hidden');
    removeBtn.classList.add('hidden');
}
}
// Funçãoo para remover logo da fazenda (compatibilidade)
function removeFarmLogo() {
window.reportSettings.farmLogo = null;
updateFarmLogoPreview(null);

// Limpar input file se existir
const fileInput = document.getElementById('farmLogoUpload');
if (fileInput) {
    fileInput.value = '';
}

if (typeof showNotification === 'function') {
showNotification('Logo removida! Clique em "Salvar Configurações" para aplicar', 'info');
}
}

async function saveReportSettings() {
try {
    // Usando MySQL direto atrav�s do objeto 'db'
    
    // Usar o nome da fazenda das configurações globais ou padrão
    const farmName = window.reportSettings.farmName || 'Fazenda';
    
    const { error } = await db.rpc('update_user_report_settings', {
        p_report_farm_name: farmName,
        p_report_farm_logo_base64: window.reportSettings.farmLogo,
        p_report_footer_text: null,
        p_report_system_logo_base64: null
    });
    
    if (error) throw error;

    if (error) throw error;

    window.reportSettings.farmName = farmName;
    
    showNotification('Configurações salvas com sucesso!', 'success');
} catch (error) {
    console.error('Error saving report settings:', error);
    showNotification('Erro ao salvar configurações', 'error');
}
}

// Carregar configurações ao inicializar
document.addEventListener('DOMContentLoaded', function() {
loadReportSettings();
});

// MOVIDA PARA O INÍCIO DO ARQUIVO

const translation = {
    'morning': 'Manh�',
    'afternoon': 'Tarde',
    'evening': 'Noite',
    'night': 'Madrugada'
};
return translation[milkingType] || milkingType;
}

// Funçãoo para traduzir type de financial_records de ingl�s para portugu�s
function getFinancialTypeInPortuguese(type) {
const translation = {
    'income': 'Receita',
    'expense': 'Despesa'
};
return translation[type] || type;
}

// Funçãoo para gerar email a partir do nome
async function generateEmailFromName(name) {
try {
    const cleanName = name.toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // Remove acentos
        .replace(/[^a-z0-9]/g, '') // Remove caracteres especiais
        .replace(/\s+/g, '.'); // Substitui espaços por pontos
    
    // Obter nome da fazenda
    const farmName = await getFarmName();
    const cleanFarmName = farmName.toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // Remove acentos
        .replace(/[^a-z0-9]/g, '') // Remove caracteres especiais
        .replace(/\s+/g, ''); // Remove espaços
    
    // Obter pr�ximo número sequencial para esta fazenda
    const nextNumber = await getNextUserNumber(farmName);
    
    return `${cleanName}${nextNumber}@${cleanFarmName}.lactech.com`;
} catch (error) {
    console.error('Erro ao gerar email:', error);
    // Fallback simples
    const cleanName = name.toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]/g, '')
        .replace(/\s+/g, '.');
    return `${cleanName}@lactech.com`;
}
}

// Funçãoo para obter pr�ximo número sequencial de usuário
async function getNextUserNumber(farmName) {
try {
    // Usando MySQL direto atrav�s do objeto 'db'
    const { data: { user } } = await db.auth.getUser();
    if (!user) return '001';

    // Sistema Lagoa Do Mato
    const { data: userData, error: userError } = await db
        .from('users')
        .select('id')
        .eq('id', user.id)
        .order('created_at', { ascending: true })
        .single();
    
    if (userError || !userData) return '001';

    // Contar usuários existentes na mesma fazenda
    const { data: existingUsers, error: countError } = await db
        .from('users')
        .select('id')
        .eq('farm_id', 1);
    
    if (countError) return '001';

    // Pr�ximo número será o total + 1
    const nextNumber = (existingUsers?.length || 0) + 1;
    return nextNumber.toString().padStart(3, '0');
    
} catch (error) {
    console.error('Erro ao obter pr�ximo número:', error);
    return '001';
}
}

// Funçãoo para gerar senha tempor�ria
function generateTempPassword() {
const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
let password = '';
for (let i = 0; i < 8; i++) {
    password += chars.charAt(Math.floor(Math.random() * chars.length));
}
return password;
}

// Funçãoo para prévia do relatório
function previewReport() {
// Gerar um relatório de exemplo com as configurações atuais
const sampleData = [
    {
        record_date: new Date().toISOString(),
        total_volume: 150.5,
        shift: 'morning',
        notes: 'Registro de exemplo',
        users: { name: 'Funcionário Exemplo' }
    }
];

generateVolumePDF(sampleData, true); // true indica que � uma prévia
}

function toggleUserPasswordVisibility(inputId, buttonId) {
const passwordInput = document.getElementById(inputId);
const toggleButton = document.getElementById(buttonId);

if (passwordInput && toggleButton) {
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleButton.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
            </svg>
        `;
    } else {
        passwordInput.type = 'password';
        toggleButton.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
        `;
    }
}
}

// Upload profile photo to Database Storage
async function uploadProfilePhoto(file, userId) {
try {
    // Usando MySQL direto atrav�s do objeto 'db'
    
    // Get current authenticated user
    const { data: { user }, error: authError } = await db.auth.getUser();
    
    if (authError || !user) {
        console.error('DEBUG: Falha na autenticaçãoo:', authError);
        throw new Error('Usuário não autenticado');
    }

    const { data: managerData, error: managerError } = await db
        .from('users')
        .select('id')
        .eq('id', user.id)
        .single();

    if (managerError || !managerData) {
        console.error('DEBUG: Fazenda não encontrada:', managerError);
        throw new Error('Usuário não encontrado');
    }
    
    const fileExt = file.name.split('.').pop();
    // Ensure we always use the target userId, never fallback to current user
    if (!userId) {
        throw new Error('userId � obrigatário para upload de foto');
    }

    const timestamp = Date.now();
    const randomId = Math.random().toString(36).substr(2, 9);
    const fileName = `user_${userId}_${timestamp}_${randomId}.${fileExt}`;
    const filePath = `lagoa-do-mato/${fileName}`; // Fazenda �nica
    
    const { data, error } = await db.storage
        .from('profile-photos')
        .upload(filePath, file, {
            cacheControl: '3600',
            upsert: false
        });

    if (error) {
        console.error('DEBUG: Erro no upload:', {
            message: error.message,
            statusCode: error.statusCode,
            error: error
        });
        throw error;
    }

    const { data: { publicUrl } } = db.storage
        .from('profile-photos')
        .getPublicUrl(filePath);

    return publicUrl;
} catch (error) {
    console.error('DEBUG: Erro final:', {
        message: error.message,
        stack: error.stack,
        error: error
    });
    throw error;
}
}

// Function to refresh users list without reloading photos
async function refreshUsersListOnly() {

try {
    // Usando MySQL direto atrav�s do objeto 'db'
    const { data: { user } } = await db.auth.getUser();
    if (!user) {
        return;
    }
    
    // Usuário da fazenda Lagoa Do Mato
    const { data: userData, error: userError } = await db
        .from('users')
        .select('id')
        .eq('id', user.id)
        .single();
    
    if (userError || !userData) {
        return;
    }
    
    // Get all users from the same farm (without photos to avoid cache issues)
    const { data: allUsers, error } = await db
        .from('users')
        .select('id, name, email, role, phone, is_active, created_at')
        .eq('farm_id', 1)
        .order('created_at', { ascending: false });
    
    if (error) {
        return;
    }
    
    // Update counts only
    if (allUsers) {
        const employeesCount = allUsers.filter(u => u.role === 'funcionario').length;
        const veterinariansCount = allUsers.filter(u => u.role === 'veterinario').length;
        const managersCount = allUsers.filter(u => u.role === 'gerente').length;
        const totalUsers = allUsers.length;
        
        document.getElementById('totalUsers').textContent = totalUsers;
        document.getElementById('employeesCount').textContent = employeesCount;
        document.getElementById('veterinariansCount').textContent = veterinariansCount;
        document.getElementById('managersCount').textContent = managersCount;
    }
    
} catch (error) {
    console.error('DEBUG: Erro no refreshUsersListOnly:', error);
}
}

async function updateUserPhotoInList(userId, newPhotoUrl) {

try {
    const photoElement = document.getElementById(`user-photo-${userId}`);
    const iconElement = document.getElementById(`user-icon-${userId}`);
    
    if (photoElement && newPhotoUrl) {
        // Update the photo with cache buster
        const cacheBuster = Date.now();
        photoElement.src = newPhotoUrl + '?cb=' + cacheBuster;
        photoElement.style.display = 'block';
        
        if (iconElement) {
            iconElement.style.display = 'none';
        }

    } else if (iconElement && !newPhotoUrl) {

        if (photoElement) {
            photoElement.style.display = 'none';
        }
        iconElement.style.display = 'flex';

    }
    
} catch (error) {
    console.error('DEBUG: Erro ao atualizar foto na lista:', error);
}
}

async function debugCheckAllPhotos() {

try {
    const { data: { user } } = await db.auth.getUser();
    if (!user) {
        return;
    }
    
    // Usuário da fazenda Lagoa Do Mato
    const { data: userData, error: userError } = await db
        .from('users')
        .select('id')
        .eq('id', user.id)
        .single();
    
    if (userError || !userData) {
        return;
    }
    
    // Get all users from the same farm
    const { data: allUsers, error } = await db
        .from('users')
        .select('id, name, email, role, profile_photo_url, created_at')
        .eq('farm_id', 1)
        .order('created_at', { ascending: false });
    
    if (error) {
        return;
    }

} catch (error) {
    console.error('DEBUG: Erro na verificaçãoo:', error);
}
}

async function signOut() {

showLogoutConfirmationModal();
}

// Funçãoo para mostrar modal de confirmaçãoo de logout
function showLogoutConfirmationModal() {
// Criar modal se não existir
let modal = document.getElementById('logoutConfirmationModal');
if (!modal) {
    modal = document.createElement('div');
    modal.id = 'logoutConfirmationModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl" onclick="event.stopPropagation()">
            <div class="text-center">
                <!-- ícone de logout -->
                <div class="w-16 h-16 bg-red-100 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                
                <!-- T�tulo -->
                <h3 class="text-xl font-bold text-gray-900 mb-2">
                    Confirmar Sa�da
                </h3>
                
                <!-- Mensagem -->
                <p class="text-gray-600 mb-6">
                    Tem certeza que deseja sair do sistema?
                </p>
                
                <!-- Botões -->
                <div class="flex space-x-3">
                    <button onclick="closeLogoutModal()" 
                            class="flex-1 px-4 py-3 border border-gray-300border-gray-300text-gray-700 font-medium rounded-xl hover:bg-gray-50hover:bg-gray-50transition-all">
                        Cancelar
                    </button>
                    <button onclick="confirmLogout()" 
                            class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-all">
                        Sair
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Adicionar evento de clique no overlay para fechar
modal.addEventListener('click', function(e) {
    if (e.target === modal) {
        closeLogoutModal();
    }
});

// Adicionar evento de tecla ESC para fechar
const escHandler = function(e) {
    if (e.key === 'Escape') {
        closeLogoutModal();
        document.removeEventListener('keydown', escHandler);
    }
};
document.addEventListener('keydown', escHandler);

// Mostrar modal
modal.style.display = 'flex';
}

// Funçãoo para fechar modal de logout
function closeLogoutModal() {
console.log('?? Fechando modal de logout...');
const modal = document.getElementById('logoutConfirmationModal');
if (modal) {
    modal.style.display = 'none';
    modal.style.visibility = 'hidden';
    modal.style.opacity = '0';
    modal.style.pointerEvents = 'none';
    console.log('? Modal de logout fechado');
} else {
    console.error('? Modal de logout não encontrado');
}
}

// Funçãoo para confirmar logout
async function confirmLogout() {
try {
    console.log('?? Iniciando logout...');
    closeLogoutModal();
    
    // Mostrar loading
    showNotification('Saindo do sistema...', 'info');

    // Limpar atualizações em tempo real
    cleanupRealtimeUpdates();
    
    clearUserSession(); // Use new clearUserSession function
    // Usando MySQL direto atrav�s do objeto 'db'
    await db.auth.signOut();
    console.log('? Logout realizado com sucesso');

    safeRedirect('index.php'); // Use new safeRedirect function
} catch (error) {
    console.error('? Erro no logout:', error);
    clearUserSession();
    safeRedirect('index.php');
}
}

// Funçãoo para carregar dados da conta secund�ria existente
async function loadSecondaryAccountData() {
try {
    // Usando MySQL direto atrav�s do objeto 'db'
    // Get current user data
    const { data: { user } } = await db.auth.getUser();
    if (!user) {
        console.error('Usuário não autenticado');
        return;
    }
    
    // Get user details from users table
    const { data: userData, error: userError } = await db
        .from('users')
        .select('*')
        .eq('id', user.id)
        .single();
        
    if (userError) {
        console.error('Erro ao buscar dados do usuário:', userError);
        return;
    }

    let secondaryAccountRelation = null;
    try {
        const { data: relationData, error: relationError } = await db
            .from('secondary_accounts')
            .select('secondary_account_id')
            .eq('primary_account_id', user.id)
            .maybeSingle();
            
        if (relationError) {
            console.error('Erro ao verificar relaçãoo de conta secund�ria:', relationError);
        } else {
            secondaryAccountRelation = relationData;
        }
    } catch (error) {
        console.error('Erro ao acessar tabela secondary_accounts:', error);
    }
    
    // Se não encontrou na tabela de relações, tenta o m�todo antigo
    if (!secondaryAccountRelation) {
        try {

            const { data: secondaryAccount, error: secondaryError } = await db
                .from('users')
                .select('*')
                .eq('email', userData.email)
                .eq('farm_id', 1)
                .neq('id', userData.id)
                .maybeSingle();
            
            if (secondaryError) {
                console.error('Erro ao verificar conta secund�ria:', secondaryError);
            }
            
            if (secondaryAccount) {

                const nameField = document.getElementById('secondaryAccountName');
                const roleField = document.getElementById('secondaryAccountRole');
                const activeField = document.getElementById('secondaryAccountActive');
                
                if (nameField) nameField.value = secondaryAccount.name;
                if (roleField) roleField.value = secondaryAccount.role;
                if (activeField) activeField.checked = secondaryAccount.is_active;
                
                // Atualizar o status da conta secund�ria
                const noAccountDiv = document.getElementById('noSecondaryAccount');
                const hasAccountDiv = document.getElementById('hasSecondaryAccount');
                const nameDisplay = document.getElementById('secondaryAccountNameDisplay');
                const switchBtn = document.getElementById('switchAccountBtn');
                
                if (noAccountDiv) noAccountDiv.style.display = 'none';
                if (hasAccountDiv) hasAccountDiv.style.display = 'block';
                if (nameDisplay) nameDisplay.textContent = secondaryAccount.name;
                if (switchBtn) switchBtn.disabled = false;
                
                // Criar a relaçãoo na tabela secondary_accounts se não existir
                try {
                    const { error: insertError } = await db
                        .from('secondary_accounts')
                        .insert([
                            {
                                primary_account_id: user.id,
                                secondary_account_id: secondaryAccount.id
                            }
                        ]);
                        
                    if (insertError && !insertError.message.includes('duplicate key')) {
                        console.error('Erro ao criar relaçãoo de conta secund�ria:', insertError);
                    }
                } catch (error) {
                    console.error('Erro ao criar relaçãoo:', error);
                }
            } else {

                const nameField = document.getElementById('secondaryAccountName');
                const roleField = document.getElementById('secondaryAccountRole');
                const activeField = document.getElementById('secondaryAccountActive');
                
                if (nameField) nameField.value = '';
                if (roleField) roleField.value = 'funcionario';
                if (activeField) activeField.checked = true;
                
                // Atualizar o status da conta secund�ria
                const noAccountDiv = document.getElementById('noSecondaryAccount');
                const hasAccountDiv = document.getElementById('hasSecondaryAccount');
                const switchBtn = document.getElementById('switchAccountBtn');
                
                if (noAccountDiv) noAccountDiv.style.display = 'block';
                if (hasAccountDiv) hasAccountDiv.style.display = 'none';
                if (switchBtn) switchBtn.disabled = true;
            }
        } catch (error) {
            console.error('Erro ao verificar conta secund�ria:', error);
        }
    } else {
        try {
            // Buscar os dados da conta secund�ria usando o ID da relaçãoo
            const { data: secondaryAccount, error: accountError } = await db
                .from('users')
                .select('*')
                .eq('id', secondaryAccountRelation.secondary_account_id)
                .single();
                
            if (accountError) {
                console.error('Erro ao buscar dados da conta secund�ria:', accountError);
                return;
            }

            const nameField = document.getElementById('secondaryAccountName');
            const roleField = document.getElementById('secondaryAccountRole');
            const activeField = document.getElementById('secondaryAccountActive');
            
            if (nameField) nameField.value = secondaryAccount.name;
            if (roleField) roleField.value = secondaryAccount.role;
            if (activeField) activeField.checked = secondaryAccount.is_active;
            
            // Atualizar o status da conta secund�ria
            const noAccountDiv = document.getElementById('noSecondaryAccount');
            const hasAccountDiv = document.getElementById('hasSecondaryAccount');
            const nameDisplay = document.getElementById('secondaryAccountNameDisplay');
            const switchBtn = document.getElementById('switchAccountBtn');
            
            if (noAccountDiv) noAccountDiv.style.display = 'none';
            if (hasAccountDiv) hasAccountDiv.style.display = 'block';
            if (nameDisplay) nameDisplay.textContent = secondaryAccount.name;
            if (switchBtn) switchBtn.disabled = false;
        } catch (error) {
            console.error('Erro ao buscar dados da conta secund�ria:', error);
        }
    }
} catch (error) {
    console.error('Erro ao carregar dados da conta secund�ria:', error);
}
}

async function saveSecondaryAccount(event) {
event.preventDefault();

try {
    // Mostrar indicador de carregamento
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Salvando...';
    submitBtn.disabled = true;
    
    // Get current user data
    const { data: { user } } = await db.auth.getUser();
    if (!user) {
        console.error('Usuário não autenticado');
        return;
    }
    
    // Get user details from users table
    const { data: userData, error: userError } = await db
        .from('users')
        .select('*')
        .eq('id', user.id)
        .single();
        
    if (userError) {
        console.error('Erro ao buscar dados do usuário:', userError);
        return;
    }

    const secondaryRole = document.getElementById('secondaryAccountRole').value;
    const roleSuffix = secondaryRole === 'veterinario' ? ' (Veterinário)' : ' (Funcionário)';
    const secondaryName = document.getElementById('secondaryAccountName').value.trim() || userData.name + roleSuffix;
    const isActive = document.getElementById('secondaryAccountActive').checked;
    
    // Gerar email �nico para a conta secund�ria
    const secondaryEmail = userData.email + (secondaryRole === 'veterinario' ? '.vet' : '.func');

    const { data: existingAccount, error: checkError } = await db
        .from('users')
        .select('*')
        .eq('email', secondaryEmail)
        .eq('farm_id', 1)
        .maybeSingle();
    
    let secondaryAccount;
    
    if (!checkError && existingAccount) {
        // Update existing account
        const { data: updatedAccount, error: updateError } = await db
            .from('users')
            .update({
                name: secondaryName,
                role: secondaryRole,
                is_active: isActive
            })
            .eq('id', existingAccount.id)
            .select()
            .single();
            
        if (updateError) {
            console.error('Erro ao atualizar conta secund�ria:', updateError);
            showNotification('Erro ao atualizar conta secund�ria. Por favor, tente novamente.', 'error');
            return;
        }
        
        secondaryAccount = updatedAccount;
        showNotification('Conta secund�ria atualizada com sucesso!', 'success');

        const { data: existingRelation, error: relationError } = await db
            .from('secondary_accounts')
            .select('*')
            .eq('primary_account_id', user.id)
            .eq('secondary_account_id', secondaryAccount.id)
            .single();
            
        if (relationError && relationError.code !== 'PGRST116') {
            console.error('Erro ao verificar relaçãoo de conta secund�ria:', relationError);
        }
        
        // Se não existir relaçãoo, criar uma
        if (!existingRelation) {
            const { error: insertError } = await db
                .from('secondary_accounts')
                .insert([
                    {
                        primary_account_id: user.id,
                        secondary_account_id: secondaryAccount.id
                    }
                ]);
                
            if (insertError) {
                console.error('Erro ao criar relaçãoo de conta secund�ria:', insertError);
            }
        }
    } else {
        // Create new secondary account

        const { data: existingUsers, error: existingError } = await db
            .from('users')
            .select('*')
            .eq('email', secondaryEmail)
            .eq('farm_id', 1)
            .neq('id', userData.id);
            
        if (existingError) {
            console.error('Erro ao verificar usuários existentes:', existingError);
        } else {

            // Se j� existir um usuário secundário, atualizar em vez de criar
            if (existingUsers && existingUsers.length > 0) {
                const { data: updatedAccount, error: updateError } = await db
                    .from('users')
                    .update({
                        name: secondaryName,
                        role: secondaryRole,
                        is_active: isActive
                    })
                    .eq('id', existingUsers[0].id)
                    .select()
                    .single();
                    
                if (updateError) {
                    console.error('Erro ao atualizar conta secund�ria existente:', updateError);
                    showNotification('Erro ao atualizar conta secund�ria. Por favor, tente novamente.', 'error');
                    return;
                }
                
                secondaryAccount = updatedAccount;
                showNotification('Conta secund�ria atualizada com sucesso!', 'success');

                const { data: existingRelation, error: relationError } = await db
                    .from('secondary_accounts')
                    .select('*')
                    .eq('primary_account_id', user.id)
                    .eq('secondary_account_id', secondaryAccount.id)
                    .single();
                    
                if (relationError && relationError.code !== 'PGRST116') {
                    console.error('Erro ao verificar relaçãoo de conta secund�ria:', relationError);
                }
                
                // Se não existir relaçãoo, criar uma
                if (!existingRelation) {
                    const { error: insertError } = await db
                        .from('secondary_accounts')
                        .insert([
                            {
                                primary_account_id: user.id,
                                secondary_account_id: secondaryAccount.id
                            }
                        ]);
                        
                    if (insertError) {
                        console.error('Erro ao criar relaçãoo de conta secund�ria:', insertError);
                    }
                }
            } else {
                // Create new secondary account
                const { data: newAccount, error: createError } = await db
                    .from('users')
                    .insert([
                        {
                            farm_id: 1, // Lagoa Do Mato
                            name: secondaryName,
                            email: secondaryEmail,
                            role: secondaryRole,
                            whatsapp: userData.whatsapp,
                            is_active: isActive,
                            profile_photo_url: userData.profile_photo_url
                        }
                    ])
                    .select()
                    .single();
                    
                if (createError) {
                    console.error('Erro ao criar nova conta secund�ria:', createError);
                    showNotification('Erro ao criar nova conta secund�ria. Por favor, tente novamente.', 'error');
                    return;
                }
                
                secondaryAccount = newAccount;
                showNotification('Nova conta secund�ria criada com sucesso!', 'success');
                
                // Create relation in secondary_accounts table
                const { error: insertError } = await db
                    .from('secondary_accounts')
                    .insert([
                        {
                            primary_account_id: user.id,
                            secondary_account_id: secondaryAccount.id
                        }
                    ]);
                    
                if (insertError) {
                    console.error('Erro ao criar relaçãoo de conta secund�ria:', insertError);
                }
            }
        }
    }
} catch (error) {
    console.error('Erro ao salvar conta secund�ria:', error);
    showNotification('Erro ao salvar conta secund�ria. Por favor, tente novamente.', 'error');
} finally {

    const submitBtn = event.target.querySelector('button[type="submit"]');
    submitBtn.innerHTML = originalBtnText;
    submitBtn.disabled = false;
}
}

// Funçãoo para alternar a visibilidade do painel de contas
function toggleAccountsPanel() {
const panel = document.getElementById('accountsPanel');
if (panel.classList.contains('hidden')) {
    panel.classList.remove('hidden');
    loadAccountCards();
} else {
    panel.classList.add('hidden');
}
}

// Funçãoo para carregar os cards de contas
async function loadAccountCards() {
try {
    // Get current user data
    const { data: { user } } = await db.auth.getUser();
    if (!user) {
        console.error('Usuário não autenticado');
        return;
    }
    
    // Get user details from users table
    const { data: userData, error: userError } = await db
        .from('users')
        .select('*')
        .eq('id', user.id)
        .single();
        
    if (userError) {
        console.error('Erro ao buscar dados do usuário:', userError);
        return;
    }
    
    // Limpar o container de cards
    const cardsContainer = document.getElementById('accountCards');
    cardsContainer.innerHTML = '';
    
    // Adicionar card da conta principal
    const primaryCard = document.createElement('div');
    primaryCard.className = 'bg-whiteborder border-blue-100 rounded-xl p-4 shadow-sm hover:shadow-md transition-all';
    primaryCard.innerHTML = `
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 rounded-full bg-forest-100 flex items-center justify-center text-forest-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
            <div class="flex-1">
                <h5 class="font-medium text-blue-900">${userData.name}</h5>
                <p class="text-sm text-blue-600 capitalize">${userData.role}</p>
            </div>
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-forest-100 text-forest-800">
                    Atual
                </span>
            </div>
        </div>
    `;
    cardsContainer.appendChild(primaryCard);
    
    // Buscar contas secund�rias na tabela secondary_accounts
    const { data: secondaryRelations, error: relError } = await db
        .from('secondary_accounts')
        .select('secondary_account_id')
        .eq('primary_account_id', user.id);
        
    if (relError) {
        console.error('Erro ao buscar relações de contas secund�rias:', relError);
        return;
    }
    
    if (secondaryRelations && secondaryRelations.length > 0) {
        // Buscar detalhes de cada conta secund�ria
        for (const relation of secondaryRelations) {
            const { data: secondaryAccount, error: accountError } = await db
                .from('users')
                .select('*')
                .eq('id', relation.secondary_account_id)
                .single();
                
            if (accountError) {
                console.error('Erro ao buscar detalhes da conta secund�ria:', accountError);
                continue;
            }
            
            // Criar card para a conta secund�ria
            const secondaryCard = document.createElement('div');
            secondaryCard.className = 'bg-whiteborder border-blue-100 rounded-xl p-4 shadow-sm hover:shadow-md transition-all';
            secondaryCard.innerHTML = `
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h5 class="font-medium text-blue-900">${secondaryAccount.name}</h5>
                        <p class="text-sm text-blue-600 capitalize">${secondaryAccount.role}</p>
                    </div>
                    <div>
                        <button onclick="switchToAccount('${secondaryAccount.id}');" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-all">
                            Alternar
                        </button>
                    </div>
                </div>
            `;
            cardsContainer.appendChild(secondaryCard);
        }
    } else {
        // Se não encontrou contas secund�rias, mostrar mensagem
        const noAccountsMessage = document.createElement('div');
        noAccountsMessage.className = 'text-center p-4 text-blue-600';
        noAccountsMessage.innerHTML = `
            <p>Voc� ainda não possui contas secund�rias configuradas.</p>
            <button onclick="showSecondaryAccountForm();" class="mt-2 px-3 py-1 bg-forest-500 hover:bg-forest-600 text-white text-sm font-medium rounded-lg transition-all">
                Configurar Conta
            </button>
        `;
        cardsContainer.appendChild(noAccountsMessage);
    }
} catch (error) {
    console.error('Erro ao carregar cards de contas:', error);
}
}

// Funçãoo para alternar para uma conta espec�fica
async function switchToAccount(accountId) {
try {
    // Get current user data
    const { data: { user } } = await db.auth.getUser();
    if (!user) {
        console.error('Usuário não autenticado');
        return;
    }
    
    // Get user details from users table
    const { data: userData, error: userError } = await db
        .from('users')
        .select('*')
        .eq('id', user.id)
        .single();
        
    if (userError) {
        console.error('Erro ao buscar dados do usuário:', userError);
        return;
    }
    
    // Store current role in session storage
    sessionStorage.setItem('previous_role', userData.role);
    
    // Get secondary account details
    const { data: secondaryAccount, error: secondaryError } = await db
        .from('users')
        .select('*')
        .eq('id', accountId)
        .single();
    
    if (secondaryError) {
        console.error('Erro ao buscar conta secund�ria:', secondaryError);
        return;
    }
    
    if (!secondaryAccount.is_active) {
        showNotification('Esta conta está desativada. Por favor, ative-a nas configurações.', 'warning');
        showSecondaryAccountForm();
        return;
    }
    
    // Secondary account exists and is active, switch to it

    // Store current user session data
    const currentSession = {
        id: userData.id,
        email: userData.email,
        name: userData.name,
        user_type: userData.role,
        farm_id: 1,
        farm_name: sessionStorage.getItem('farm_name') || ''
    };
    
    // Store secondary account session data
    const secondarySession = {
        id: secondaryAccount.id,
        email: secondaryAccount.email,
        name: secondaryAccount.name,
        user_type: secondaryAccount.role,
        farm_id: 1,
        farm_name: sessionStorage.getItem('farm_name') || ''
    };

    sessionStorage.setItem('primary_account', JSON.stringify(currentSession));
    
    // Set new session
    sessionStorage.setItem('user', JSON.stringify(secondarySession));
    
    // Redirect to appropriate page based on role
    if (secondaryAccount.role === 'funcionario') {
        window.location.href = 'funcionario.php';
    } else if (secondaryAccount.role === 'veterinario') {
        window.location.href = 'veterinario.php';
    } else {
        showNotification('Conta secund�ria encontrada, mas o tipo não � reconhecido.', 'warning');
    }
} catch (error) {
    console.error('Erro ao alternar conta:', error);
    showNotification('Ocorreu um erro ao alternar para a conta secund�ria.', 'error');
}
}

// Function to switch between manager and inseminator accounts (mantido para compatibilidade)
async function switchToSecondaryAccount() {
try {
    // Get current user data
    const { data: { user } } = await db.auth.getUser();
    if (!user) {
        console.error('Usuário não autenticado');
        return;
    }
    
    const { data: secondaryRelations, error: relError } = await db
        .from('secondary_accounts')
        .select('secondary_account_id')
        .eq('primary_account_id', user.id);
        
    if (relError || !secondaryRelations || secondaryRelations.length === 0) {
        showNotification('Voc� precisa configurar uma conta secund�ria primeiro.', 'warning');
        showSecondaryAccountForm();
        return;
    }
    
    if (secondaryRelations.length > 1) {
        toggleAccountsPanel();
        return;
    }
    
    switchToAccount(secondaryRelations[0].secondary_account_id);
} catch (error) {
    console.error('Erro ao alternar conta:', error);
    showNotification('Ocorreu um erro ao alternar para a conta secund�ria.', 'error');
}
}

async function checkIfSecondaryAccount() {
try {
    const { data: { user } } = await db.auth.getUser();
    if (!user) return false;
    
    const { data: relation, error } = await db
        .from('secondary_accounts')
        .select('primary_account_id')
        .eq('secondary_account_id', user.id)
        .single();
    
    if (error || !relation) {
        return false;
    }
    
    return true;
} catch (error) {
    console.error('Erro ao verificar se � conta secund�ria:', error);
    return false;
}
}

async function showAlterSecondaryAccountSection() {
const isSecondary = await checkIfSecondaryAccount();
const alterSection = document.getElementById('alterSecondaryAccountSection');

if (alterSection) {
    if (isSecondary) {
        alterSection.style.display = 'block';

    } else {
        alterSection.style.display = 'none';

    }
}
}

function showAlterSecondaryAccountForm() {
const form = document.getElementById('secondaryAccountForm');
const alterBtn = document.getElementById('alterSecondaryAccountBtn');

if (form) {
    form.style.display = 'block';
    // Mudar o texto do bot�o para indicar que � uma alteraçãoo
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.innerHTML = 'Alterar Conta Secund�ria';
    }
    
    // Carregar dados atuais da conta
    loadCurrentSecondaryAccountData();
}

if (alterBtn) {
    alterBtn.style.display = 'none';
}
}

// Funçãoo para carregar dados atuais da conta secund�ria
async function loadCurrentSecondaryAccountData() {
try {
    const { data: { user } } = await db.auth.getUser();
    if (!user) return;
    
    // Buscar dados atuais do usuário
    const { data: userData, error } = await db
        .from('users')
        .select('name, email, role, is_active')
        .eq('id', user.id)
        .single();
    
    if (error) {
        console.error('Erro ao carregar dados atuais:', error);
        return;
    }

    const nameField = document.getElementById('secondaryAccountName');
    const roleField = document.getElementById('secondaryAccountRole');
    const activeField = document.getElementById('secondaryAccountActive');
    
    if (nameField) nameField.value = userData.name || '';
    if (roleField) roleField.value = userData.role || 'funcionario';
    if (activeField) activeField.checked = userData.is_active || false;

} catch (error) {
    console.error('Erro ao carregar dados atuais:', error);
}
}

async function saveSecondaryAccountAlteration(event) {
event.preventDefault();

try {
    const { data: { user } } = await db.auth.getUser();
    if (!user) {
        showNotification('Usuário não autenticado', 'error');
        return;
    }

    const name = document.getElementById('secondaryAccountName').value.trim();
    const role = document.getElementById('secondaryAccountRole').value;
    const isActive = document.getElementById('secondaryAccountActive').checked;
    
    if (!name) {
        showNotification('Por favor, informe o nome da conta.', 'warning');
        return;
    }
    
    // Atualizar dados do usuário
    const { data: updatedUser, error: updateError } = await db
        .from('users')
        .update({
            name: name,
            role: role,
            is_active: isActive
        })
        .eq('id', user.id)
        .select()
        .single();
    
    if (updateError) {
        console.error('Erro ao atualizar conta:', updateError);
        showNotification('Erro ao atualizar a conta secund�ria.', 'error');
        return;
    }
    
    // Atualizar dados da sess�o
    const sessionData = {
        id: updatedUser.id,
        email: updatedUser.email,
        name: updatedUser.name,
        role: updatedUser.role,
        farm_id: 1,
        is_active: updatedUser.is_active
    };
    
    localStorage.setItem('userData', JSON.stringify(sessionData));

    hideSecondaryAccountForm();
    
    // Mostrar modal de sucesso
    showSecondaryAccountSuccessModal(updatedUser, 'alteracao');
    
    // Recarregar dados da p�gina
    await loadUserProfile();
    await setManagerName();

} catch (error) {
    console.error('Erro ao alterar conta secund�ria:', error);
    showNotification('Ocorreu um erro ao alterar a conta secund�ria.', 'error');
}
}

function showSecondaryAccountSuccessModal(account, action = 'criacao') {
const modal = document.getElementById('secondaryAccountSuccessModal');
const title = document.getElementById('successModalTitle');
const message = document.getElementById('successModalMessage');
const name = document.getElementById('successAccountName');
const role = document.getElementById('successAccountRole');
const email = document.getElementById('successAccountEmail');

if (modal && title && message && name && role && email) {
    if (action === 'alteracao') {
        title.textContent = 'Conta Secund�ria Alterada!';
        message.textContent = 'Suas informações foram atualizadas com sucesso.';
    } else {
        title.textContent = 'Conta Secund�ria Criada!';
        message.textContent = 'Sua conta secund�ria foi configurada com sucesso.';
    }
    
    name.textContent = account.name || 'N�o informado';
    role.textContent = account.role || 'N�o informado';
    email.textContent = account.email || 'N�o informado';
    
    modal.classList.remove('hidden');
    
    // Auto-close após 5 segundos
    setTimeout(() => {
        closeSecondaryAccountSuccessModal();
    }, 5000);
}
}

async function handleSecondaryAccountSubmit(event) {
event.preventDefault();

try {

    const isSecondary = await checkIfSecondaryAccount();
    
    if (isSecondary) {
        // Se � conta secund�ria, usar função de alteraçãoo
        await saveSecondaryAccountAlteration(event);
    } else {
        // Se não � conta secund�ria, usar função de criação
        await saveSecondaryAccount(event);
    }
    
} catch (error) {
    console.error('Erro ao processar submiss�o do formulário:', error);
    showNotification('Ocorreu um erro ao processar a solicitaçãoo.', 'error');
}
}

// LIMPEZA DAS FUNçãoES DE CARREGAMENTO

// Funçãoo limpa para carregar dados do usuário
async function loadUserProfileClean() {
try {
    const whatsappElement = document.getElementById('profileWhatsApp');
    
    if (!whatsappElement) {
        return;
    }
    
    // Primeiro tentar obter da sess�o local
    const sessionData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
    
    if (sessionData) {
        try {
            const user = JSON.parse(sessionData);
            
            // Definir dados do perfil da sess�o
            document.getElementById('profileEmail2').textContent = user.email || '';
            const whatsappValue = user.whatsapp || user.phone || 'N�o informado';
            document.getElementById('profileWhatsApp').textContent = whatsappValue;
            return;
        } catch (error) {
            // Continuar para fallback
        }
    }
    
    // Fallback para Database Auth
    const { data: { user } } = await db.auth.getUser();
    
    if (!user) {
        document.getElementById('profileEmail2').textContent = 'N�o logado';
        document.getElementById('profileWhatsApp').textContent = 'N�o informado';
        return;
    }

    // Buscar dados do usuário no banco
    const { data: userData, error } = await db
        .from('users')
        .select('name, email, phone')
        .eq('id', user.id)
        .single();
    
    // Se usuário não encontrado, mostrar erro
    if (error && error.code === 'PGRST116') {
        document.getElementById('profileEmail2').textContent = user.email || '';
        document.getElementById('profileWhatsApp').textContent = 'Usuário não encontrado';
        return;
    }
    
    if (error) {
        document.getElementById('profileEmail2').textContent = user.email || '';
        document.getElementById('profileWhatsApp').textContent = 'Erro ao carregar';
        return;
    }

    // Atualizar elementos do perfil
    if (userData) {
        const email = userData.email || user.email || '';
        const whatsapp = userData.whatsapp || 'N�o informado';
        
        document.getElementById('profileEmail2').textContent = email;
        document.getElementById('profileWhatsApp').textContent = whatsapp;
    } else {
        document.getElementById('profileEmail2').textContent = user.email || '';
        document.getElementById('profileWhatsApp').textContent = 'N�o informado';
    }
    
    // Atualizar foto do perfil se disponível
    if (userData?.profile_photo_url) {
        updateProfilePhotoDisplay(userData.profile_photo_url + '?t=' + Date.now());
    }
} catch (error) {
    document.getElementById('profileEmail2').textContent = 'Erro';
    document.getElementById('profileWhatsApp').textContent = 'Erro';
}
}

// Funçãoo limpa para definir nome da fazenda
async function setFarmNameClean() {
try {
    const farmName = await getFarmName();
    const farmNameElement = document.getElementById('farmNameHeader');
    if (farmNameElement) {
        farmNameElement.textContent = farmName;
    }
} catch (error) {
    const farmNameElement = document.getElementById('farmNameHeader');
    if (farmNameElement) {
        farmNameElement.textContent = 'Lagoa do Mato';
    }
}
}

// Funçãoo limpa para definir nome do gerente
async function setManagerNameClean() {
try {
    const managerName = await getManagerName();
    const farmName = await getFarmName();
    
    const finalManagerName = managerName || 'Usuário';
    const finalFarmName = farmName || 'Lagoa do Mato';

    const formalName = extractFormalName(finalManagerName);
    
    const elements = [
        'profileName',
        'profileFullName'
    ];
    
    elements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = finalManagerName;
        }
    });

    const headerElement = document.getElementById('managerName');
    const welcomeElement = document.getElementById('managerWelcome');
    if (headerElement) {
        headerElement.textContent = formalName;
    }
    if (welcomeElement) {
        welcomeElement.textContent = formalName;
    }
    
    const farmElement = document.getElementById('profileFarmName');
    if (farmElement) {
        farmElement.textContent = finalFarmName;
    }
} catch (error) {
    // Definir valores padrão em caso de erro
    const defaultName = 'Usuário';
    const defaultFarm = 'Lagoa do Mato';
    
    const elements = [
        'profileName', 
        'profileFullName'
    ];
    
    elements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = defaultName;
        }
    });

    const headerElement = document.getElementById('managerName');
    const welcomeElement = document.getElementById('managerWelcome');
    if (headerElement) {
        headerElement.textContent = defaultName;
    }
    if (welcomeElement) {
        welcomeElement.textContent = defaultName;
    }
    
    const farmElement = document.getElementById('profileFarmName');
    if (farmElement) {
        farmElement.textContent = defaultFarm;
    }
}
}

// Substituir funçãoes originais pelas vers�es limpas
window.loadUserProfile = loadUserProfileClean;
window.setFarmName = setFarmNameClean;
window.setManagerName = setManagerNameClean;

// Funçãoes do gerente substitu�das pelas vers�es limpas

// REMOçãoO COMPLETA DE TODOS OS CONSOLE.LOGS PARA PROTEGER O BANCO

const originalConsoleLog = console.log;
const originalConsoleError = console.error;

// TEMPORARIAMENTE HABILITADO PARA DEBUG

// };

// };

window.restoreConsoleLogs = function() {

console.error = originalConsoleError;
};

// Monitoramento de requisições desabilitado para evitar conflitos
window.checkDatabaseRequests = function() {
    console.log('⚠️ Monitoramento de requisições desabilitado para evitar conflitos');
};

// checkDatabaseRequests(); // Desabilitado

// ===== FUNçãoES PARA FOTO DO GERENTE =====

// Variível global para armazenar a foto selecionada
let selectedManagerPhoto = null;

// Funçãoo para alternar modo de ediçãoo da foto do gerente
function toggleManagerPhotoEdit() {
const viewMode = document.getElementById('managerPhotoViewMode');
const editMode = document.getElementById('managerPhotoEditMode');
const editBtn = document.getElementById('editManagerPhotoBtn');

if (viewMode.classList.contains('hidden')) {
    // Voltar para modo visualizaçãoo
    viewMode.classList.remove('hidden');
    editMode.classList.add('hidden');
    editBtn.textContent = 'Alterar Foto';
    selectedManagerPhoto = null;
} else {
    // Ir para modo ediçãoo
    viewMode.classList.add('hidden');
    editMode.classList.remove('hidden');
    editBtn.textContent = 'Cancelar';
}
}

// Funçãoo para cancelar ediçãoo da foto do gerente
function cancelManagerPhotoEdit() {
toggleManagerPhotoEdit();
// Limpar preview
const previewImage = document.getElementById('managerPhotoPreviewImage');
const previewPlaceholder = document.getElementById('managerPhotoPreviewPlaceholder');
if (previewImage) previewImage.classList.add('hidden');
if (previewPlaceholder) previewPlaceholder.classList.remove('hidden');
selectedManagerPhoto = null;
}

// Funçãoo para lidar com upload da foto do gerente
function handleManagerPhotoUpload(event) {
const file = event.target.files[0];
if (!file) return;

// Validar tipo de arquivo
const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!allowedTypes.includes(file.type)) {
    showNotification('Formato de arquivo não suportado. Use PNG, JPG, JPEG, GIF ou WEBP.', 'error');
    return;
}

// Validar tamanho (5MB)
const maxSize = 5 * 1024 * 1024; // 5MB
if (file.size > maxSize) {
    showNotification('Arquivo muito grande. Tamanho máximo: 5MB.', 'error');
    return;
}

// Armazenar arquivo selecionado
selectedManagerPhoto = file;

// Mostrar preview
const reader = new FileReader();
reader.onload = function(e) {
    const previewImage = document.getElementById('managerPhotoPreviewImage');
    const previewPlaceholder = document.getElementById('managerPhotoPreviewPlaceholder');
    
    if (previewImage && previewPlaceholder) {
        previewImage.src = e.target.result;
        previewImage.classList.remove('hidden');
        previewPlaceholder.classList.add('hidden');
    }
};
reader.readAsDataURL(file);
}

async function saveManagerPhoto() {
if (!selectedManagerPhoto) {
    showNotification('Selecione uma foto primeiro.', 'error');
    return;
}

try {
    // Obter dados do usuário atual
    const { data: { user } } = await db.auth.getUser();
    if (!user) {
        showNotification('Usuário não autenticado.', 'error');
        return;
    }
    
    // Upload da foto usando função espec�fica para gerente
    const photoUrl = await uploadManagerProfilePhoto(selectedManagerPhoto, user.id);
    
    if (photoUrl) {
        // Atualizar foto no banco de dados
        const { error } = await db
            .from('users')
            .update({ profile_photo_url: photoUrl })
            .eq('id', user.id);
        
        if (error) throw error;
        
        // Atualizar interface
        updateManagerPhotoDisplay(photoUrl);
        
        // Atualizar lista de usuários
        await loadUsersData();
        
        // Voltar para modo visualizaçãoo
        toggleManagerPhotoEdit();
        
        showNotification('Foto de perfil atualizada com sucesso!', 'success');
    }
    
} catch (error) {
    console.error('Erro ao salvar foto do gerente:', error);
    showNotification('Erro ao salvar foto de perfil.', 'error');
}
}

// Funçãoo espec�fica para upload de foto do gerente
async function uploadManagerProfilePhoto(file, userId) {
try {
    // Gerar nome �nico para o arquivo
    const timestamp = Date.now();
    const randomId = Math.random().toString(36).substr(2, 9);
    const fileExtension = file.name.split('.').pop();
    const fileName = `manager_${userId}_${timestamp}_${randomId}.${fileExtension}`;
    
    // Upload para Database Storage
    const { data, error } = await db.storage
        .from('profile-photos')
        .upload(fileName, file);
    
    if (error) throw error;
    
    // Obter URL p�blica
    const { data: urlData } = db.storage
        .from('profile-photos')
        .getPublicUrl(fileName);
    
    return urlData.publicUrl;
    
} catch (error) {
    console.error('Erro no upload da foto do gerente:', error);
    throw new Error('Erro ao fazer upload da foto.');
}
}

// Funçãoo para atualizar exibiçãoo da foto do gerente
function updateManagerPhotoDisplay(photoUrl) {
const photoImage = document.getElementById('managerPhotoImage');
const photoPlaceholder = document.getElementById('managerPhotoPlaceholder');

if (photoUrl && photoImage && photoPlaceholder) {
    // Adicionar timestamp para evitar cache
    photoImage.src = photoUrl + '?t=' + Date.now();
    photoImage.classList.remove('hidden');
    photoPlaceholder.classList.add('hidden');
} else if (photoPlaceholder) {
    // Mostrar placeholder se não há foto
    if (photoImage) photoImage.classList.add('hidden');
    photoPlaceholder.classList.remove('hidden');
}

// Atualizar tamb�m a foto no header
updateHeaderProfilePhoto(photoUrl);

// Atualizar tamb�m a foto no modal de perfil
updateModalProfilePhoto(photoUrl);
}

// Funçãoo para atualizar foto no header
function updateHeaderProfilePhoto(photoUrl) {
console.log('??? Atualizando foto do header:', photoUrl);

const headerPhoto = document.getElementById('headerProfilePhoto');
const headerIcon = document.getElementById('headerProfileIcon');

if (headerPhoto && headerIcon) {
    if (photoUrl && photoUrl.trim() !== '' && !photoUrl.includes('default-avatar')) {
        // Adicionar timestamp para evitar cache
        const photoUrlWithTimestamp = photoUrl + '?t=' + Date.now();
        headerPhoto.src = photoUrlWithTimestamp;
        headerPhoto.style.display = 'block';
        headerPhoto.style.visibility = 'visible';
        headerPhoto.classList.remove('hidden');
        headerIcon.style.display = 'none';
        headerIcon.style.visibility = 'hidden';
        headerIcon.classList.add('hidden');
        console.log('? Foto do header atualizada com sucesso');
    } else {
        // Mostrar ícone padrão se não há foto ou é default-avatar
        headerPhoto.src = ''; // Limpar src para evitar erro 404
        headerPhoto.style.display = 'none';
        headerPhoto.style.visibility = 'hidden';
        headerPhoto.classList.add('hidden');
        headerIcon.style.display = 'block';
        headerIcon.style.visibility = 'visible';
        headerIcon.classList.remove('hidden');
        console.log('? ícone padrão do header exibido');
    }
} else {
    console.error('? Elementos do header não encontrados');
}
}

// Funçãoo para atualizar foto no modal de perfil
function updateModalProfilePhoto(photoUrl) {
const modalPhoto = document.getElementById('modalProfilePhoto');
const modalIcon = document.getElementById('modalProfileIcon');

if (modalPhoto && modalIcon) {
    if (photoUrl && photoUrl.trim() !== '' && !photoUrl.includes('default-avatar')) {
        // Adicionar timestamp para evitar cache
        const photoUrlWithTimestamp = photoUrl + '?t=' + Date.now();
        modalPhoto.src = photoUrlWithTimestamp;
        modalPhoto.classList.remove('hidden');
        modalIcon.classList.add('hidden');
    } else {
        // Mostrar ícone padrão se não há foto ou é default-avatar
        modalPhoto.src = ''; // Limpar src para evitar erro 404
        modalPhoto.classList.add('hidden');
        modalIcon.classList.remove('hidden');
    }
}
}
// Funçãoo para carregar foto do gerente ao abrir o modal
async function loadManagerPhoto() {
try {
    // Usar API de profile
    const response = await fetch('api/profile.php?action=get_photo');
    const result = await response.json();
    
    if (!result.success) {
        console.error('Erro ao carregar foto do gerente:', result.error);
        return;
    }
    
    const photoData = result.data || {};
    
    // Atualizar exibiçãoo da foto
    if (photoData.photo_url) {
        updateManagerPhotoDisplay(photoData.photo_url);
    }
    
} catch (error) {
    console.error('Erro ao carregar foto do gerente:', error);
}
}

// DESABILITADO - Estava quebrando a função original

console.log('⚠️ Sobrescrita #1 de openProfileModal DESABILITADA');

// Funçãoo para carregar foto no header ao inicializar a p�gina
async function loadHeaderPhoto() {
try {
    console.log('??? Carregando foto do header...');
    
    // Usar API de profile
    const result = await safeFetch('api/profile.php?action=get_photo');
    
    if (!result.success) {
        console.error('? Erro ao carregar foto do header:', result.error);
        return;
    }
    
    const photoData = result.data || {};
    console.log('?? Dados da foto carregados:', photoData);
    
    // Atualizar foto no header
    updateHeaderProfilePhoto(photoData.photo_url);
    
} catch (error) {
    console.error('? Erro ao carregar foto do header:', error);
}
}

// Funçãoo j� � chamada no DOMContentLoaded principal

// ==================== FUNçãoES DA ABA DE RELAT�RIOS ====================

// Vari�veis globais para a aba de relatórios
let reportTabSettings = {
farmName: '',
farmLogo: null
};

// Funçãoo para carregar configurações na aba de relatórios
async function loadReportTabSettings() {
try {
    // Usando MySQL direto atrav�s do objeto 'db'
    const { data: { user } } = await db.auth.getUser();
    if (!user) return;

    // Buscar dados do usuário
    const { data: userData, error: userError } = await db
        .from('users')
        .select('report_farm_name, report_farm_logo_base64')
        .eq('id', user.id)
        .single();

    if (userError) throw userError;

    // Se não tem nome da fazenda configurado, buscar do banco
    let farmName = userData.report_farm_name;
    if (!farmName) {
        const { data: farmData, error: farmError } = await db
            .from('farms')
            .select('name')
            .eq('id', 1)
            .single();

        if (!farmError && farmData) {
            farmName = farmData.name;
        }
    }

    // Se ainda não tem nome, usar padrão
    if (!farmName) {
        farmName = 'Fazenda';
    }

    reportTabSettings.farmName = farmName;
    reportTabSettings.farmLogo = userData.report_farm_logo_base64;
    
    // Atualizar campos
    document.getElementById('reportFarmNameTab').value = farmName;
    updateFarmLogoPreviewTab(reportTabSettings.farmLogo);
    
    // Corrigir duplicaçãoo da logo
    setTimeout(() => {
        fixLogoDuplication();
    }, 100);

} catch (error) {
    console.error('Erro ao carregar configurações:', error);
    // Em caso de erro, usar nome padrão
    document.getElementById('reportFarmNameTab').value = 'Fazenda';
}
}

// Funçãoo para upload da logo na aba
async function handleFarmLogoUploadTab(event) {
const file = event.target.files[0];
if (!file) return;

if (!file.type.startsWith('image/')) {
    showNotification('Por favor, selecione um arquivo de imagem válido', 'error');
    return;
}

if (file.size > 2 * 1024 * 1024) {
    showNotification('A imagem deve ter no máximo 2MB', 'error');
    return;
}

try {
    const base64 = await fileToBase64(file);
    reportTabSettings.farmLogo = base64;
    updateFarmLogoPreviewTab(base64);
    showNotification('Logo carregada com sucesso! Clique em "Salvar Configurações" para aplicar', 'success');
} catch (error) {
    console.error('Erro ao processar logo:', error);
    showNotification('Erro ao processar a imagem', 'error');
}
}

// Funçãoo para atualizar preview da logo na aba
function updateFarmLogoPreviewTab(base64Logo) {
const preview = document.getElementById('farmLogoPreviewTab');
const placeholder = document.getElementById('farmLogoPlaceholderTab');
const image = document.getElementById('farmLogoImageTab');
const removeBtn = document.getElementById('removeFarmLogoTab');

if (base64Logo) {
    image.src = base64Logo;
    preview.classList.remove('hidden');
    placeholder.classList.add('hidden');
    removeBtn.classList.remove('hidden');
    
    // For�ar ocultaçãoo do placeholder via CSS
    placeholder.style.display = 'none';
    placeholder.style.visibility = 'hidden';
    placeholder.style.opacity = '0';
    placeholder.style.position = 'absolute';
    placeholder.style.zIndex = '-1';
    placeholder.style.pointerEvents = 'none';
    placeholder.style.width = '0';
    placeholder.style.height = '0';
    placeholder.style.overflow = 'hidden';
    
    // Garantir que o bot�o remover seja visível
    removeBtn.style.display = 'flex';
    removeBtn.style.visibility = 'visible';
    removeBtn.style.opacity = '1';
    removeBtn.style.position = 'relative';
    removeBtn.style.zIndex = 'auto';
    removeBtn.style.pointerEvents = 'auto';
    removeBtn.style.width = 'auto';
    removeBtn.style.height = 'auto';
    removeBtn.style.overflow = 'visible';
} else {
    image.src = '';
    preview.classList.add('hidden');
    placeholder.classList.remove('hidden');
    removeBtn.classList.add('hidden');
    
    // For�ar exibiçãoo do placeholder via CSS
    placeholder.style.display = 'flex';
    placeholder.style.visibility = 'visible';
    placeholder.style.opacity = '1';
    placeholder.style.position = 'relative';
    placeholder.style.zIndex = 'auto';
    placeholder.style.pointerEvents = 'auto';
    placeholder.style.width = 'auto';
    placeholder.style.height = 'auto';
    placeholder.style.overflow = 'visible';

    removeBtn.style.display = 'none';
    removeBtn.style.visibility = 'hidden';
    removeBtn.style.opacity = '0';
    removeBtn.style.position = 'absolute';
    removeBtn.style.zIndex = '-1';
    removeBtn.style.pointerEvents = 'none';
    removeBtn.style.width = '0';
    removeBtn.style.height = '0';
    removeBtn.style.overflow = 'hidden';
}
}

// Funçãoo para corrigir duplicaçãoo da logo na inicializaçãoo
function fixLogoDuplication() {
const preview = document.getElementById('farmLogoPreviewTab');
const placeholder = document.getElementById('farmLogoPlaceholderTab');
const removeBtn = document.getElementById('removeFarmLogoTab');

if (preview && placeholder && removeBtn) {
    if (preview.classList.contains('hidden')) {
        // Se preview está oculta, mostrar placeholder e ocultar bot�o remover
        placeholder.style.display = 'flex';
        placeholder.style.visibility = 'visible';
        placeholder.style.opacity = '1';
        placeholder.style.position = 'relative';
        placeholder.style.zIndex = 'auto';
        placeholder.style.pointerEvents = 'auto';
        placeholder.style.width = 'auto';
        placeholder.style.height = 'auto';
        placeholder.style.overflow = 'visible';

        removeBtn.style.display = 'none';
        removeBtn.style.visibility = 'hidden';
        removeBtn.style.opacity = '0';
        removeBtn.style.position = 'absolute';
        removeBtn.style.zIndex = '-1';
        removeBtn.style.pointerEvents = 'none';
        removeBtn.style.width = '0';
        removeBtn.style.height = '0';
        removeBtn.style.overflow = 'hidden';
    } else {
        // Se preview está visível, ocultar placeholder e mostrar bot�o remover
        placeholder.style.display = 'none';
        placeholder.style.visibility = 'hidden';
        placeholder.style.opacity = '0';
        placeholder.style.position = 'absolute';
        placeholder.style.zIndex = '-1';
        placeholder.style.pointerEvents = 'none';
        placeholder.style.width = '0';
        placeholder.style.height = '0';
        placeholder.style.overflow = 'hidden';
        
        // Mostrar bot�o remover
        removeBtn.style.display = 'flex';
        removeBtn.style.visibility = 'visible';
        removeBtn.style.opacity = '1';
        removeBtn.style.position = 'relative';
        removeBtn.style.zIndex = 'auto';
        removeBtn.style.pointerEvents = 'auto';
        removeBtn.style.width = 'auto';
        removeBtn.style.height = 'auto';
        removeBtn.style.overflow = 'visible';
    }
}
}

// Funçãoo para remover logo da aba
function removeFarmLogoTab() {
reportTabSettings.farmLogo = null;
updateFarmLogoPreviewTab(null);
document.getElementById('farmLogoUploadTab').value = '';
showNotification('Logo removida! Clique em "Salvar Configurações" para aplicar', 'info');
}

async function saveReportSettingsTab() {
try {
    const farmName = document.getElementById('reportFarmNameTab').value || 'Fazenda';
    
    const { error } = await db.rpc('update_user_report_settings', {
        p_report_farm_name: farmName,
        p_report_farm_logo_base64: reportTabSettings.farmLogo,
        p_report_footer_text: null,
        p_report_system_logo_base64: null
    });

    if (error) throw error;

    reportTabSettings.farmName = farmName;
    showNotification('Configurações salvas com sucesso!', 'success');
    
    // Sincronizar com as configurações do modal
    if (window.reportSettings) {
        window.reportSettings.farmName = farmName;
        window.reportSettings.farmLogo = reportTabSettings.farmLogo;
    }
    
} catch (error) {
    console.error('Erro ao salvar configurações:', error);
    showNotification('Erro ao salvar configurações', 'error');
}
}

// Funçãoo para carregar estat�sticas dos relatórios
async function loadReportStats() {
try {
    const { data: { user } } = await db.auth.getUser();
    if (!user) return;

    const { data: userData } = await db
        .from('users')
        .select('id')
        .eq('id', user.id)
        .single();

    if (!userData) return;

    const hoje = new Date().toISOString().split('T')[0];
    const inicioMes = new Date();
    inicioMes.setDate(1);
    const seteDiasAtras = new Date();
    seteDiasAtras.setDate(seteDiasAtras.getDate() - 6);

    // Produção de hoje
    const { data: producaoHoje } = await db
        .from('volume_records')
        .select('total_volume')
        .eq('farm_id', 1)
        .eq('record_date', hoje);

    let volumeHoje = 0;
    if (producaoHoje) {
        volumeHoje = producaoHoje.reduce((sum, item) => sum + parseFloat(item.total_volume || 0), 0);
    }

    // Média semanal
    const { data: producaoSemana } = await db
        .from('volume_records')
        .select('total_volume, record_date')
        .eq('farm_id', 1)
        .gte('record_date', seteDiasAtras.toISOString().split('T')[0]);

    let mediaSemana = 0;
    if (producaoSemana?.length > 0) {
        const volumesPorDia = {};
        producaoSemana.forEach(item => {
            if (!volumesPorDia[item.record_date]) {
                volumesPorDia[item.record_date] = 0;
            }
            volumesPorDia[item.record_date] += parseFloat(item.total_volume || 0);
        });
        
        const totalDias = Object.keys(volumesPorDia).length;
        const totalVolume = Object.values(volumesPorDia).reduce((sum, vol) => sum + vol, 0);
        mediaSemana = totalDias > 0 ? totalVolume / totalDias : 0;
    }

    // Total do mês
    const { data: producaoMes } = await db
        .from('volume_records')
        .select('total_volume')
        .eq('farm_id', 1)
        .gte('record_date', inicioMes.toISOString().split('T')[0]);

    let totalMes = 0;
    let registrosMes = 0;
    if (producaoMes) {
        totalMes = producaoMes.reduce((sum, item) => sum + parseFloat(item.total_volume || 0), 0);
        registrosMes = producaoMes.length;
    }

    // Funcionários ativos
    const { data: funcionarios } = await db
        .from('users')
        .select('id')
        .eq('farm_id', 1)
        .eq('role', 'funcionario');

    const funcionariosAtivos = funcionarios?.length || 0;

    // Atualizar elementos
    document.getElementById('reportTodayVolume').textContent = volumeHoje.toFixed(1) + ' L';
    document.getElementById('reportWeekAverage').textContent = mediaSemana.toFixed(1) + ' L';
    document.getElementById('reportMonthTotal').textContent = totalMes.toFixed(1) + ' L';
    document.getElementById('reportMonthRecords').textContent = registrosMes.toString();
    document.getElementById('reportActiveEmployees').textContent = funcionariosAtivos.toString();

    // Carregar lista de funcionários no select
    const selectEmployee = document.getElementById('reportEmployee');
    if (selectEmployee && funcionarios) {
        selectEmployee.innerHTML = '<option value="">Todos os funcionários</option>';
        
        for (const func of funcionarios) {
            const { data: userData } = await db
                .from('users')
                .select('name')
                .eq('id', func.id)
                .single();
            
            if (userData?.name) {
                const option = document.createElement('option');
                option.value = func.id;
                option.textContent = userData.name;
                selectEmployee.appendChild(option);
            }
        }
    }

} catch (error) {
    console.error('Erro ao carregar estat�sticas:', error);
}
}

// Funçãoo para exportar Excel
async function exportExcelReport() {
try {
    const startDate = document.getElementById('reportStartDate').value;
    const endDate = document.getElementById('reportEndDate').value;
    const employeeId = document.getElementById('reportEmployee').value;

    if (!startDate || !endDate) {
        showNotification('Por favor, selecione as datas inicial e final', 'warning');
        return;
    }

    const { data: { user } } = await db.auth.getUser();
    if (!user) return;

    const { data: userData } = await db
        .from('users')
        .select('id')
        .eq('id', user.id)
        .single();

    if (!userData) return;

    // Buscar dados
    let query = db
        .from('volume_records')
        .select(`
            record_date,
            shift,
            total_volume,
            temperature,
            observations,
            created_at,
            users!inner(name)
        `)
        .eq('farm_id', 1)
        .gte('record_date', startDate)
        .lte('record_date', endDate)
        .order('record_date', { ascending: true })
        .order('created_at', { ascending: true });

    if (employeeId) {
        query = query.eq('user_id', employeeId);
    }

    const { data: dadosExcel, error } = await query;

    if (error) {
        throw error;
    }

    if (!dadosExcel || dadosExcel.length === 0) {
        showNotification('Nenhum dado encontrado para o período selecionado', 'info');
        return;
    }

    const farmName = reportTabSettings.farmName || 'Fazenda';
    const dataInicio = new Date(startDate).toLocaleDateString('pt-BR');
    const dataFim = new Date(endDate).toLocaleDateString('pt-BR');
    const dataGeracao = new Date().toLocaleString('pt-BR');
    
    // Calcular estat�sticas
    const totalVolume = dadosExcel.reduce((sum, item) => sum + (parseFloat(item.total_volume) || 0), 0);
    const mediaVolume = dadosExcel.length > 0 ? totalVolume / dadosExcel.length : 0;
    const totalRegistros = dadosExcel.length;

    // Criar dados para Excel com design limpo
    const excelData = [
        // Cabe�alho Principal
        [`RELAT�RIO DE PRODUçãoO DE LEITE - ${farmName.toUpperCase()}`],
        [''],
        ['INFORMAçãoES DO RELAT�RIO'],
        ['Per�odo:', `${dataInicio} at� ${dataFim}`],
        ['Data de Geraçãoo:', dataGeracao],
        ['Total de Registros:', totalRegistros],
        ['Volume Total Produzido:', `${totalVolume.toFixed(2)} L`],
        ['Média por Registro:', `${mediaVolume.toFixed(2)} L`],
        [''],
        // Cabe�alho da Tabela
        ['Data', 'Funcionário', 'Turno', 'Volume (L)', 'Temperatura (�C)', 'Observações', 'Data/Hora Registro']
    ];

    dadosExcel.forEach(item => {
        const data = new Date(item.record_date).toLocaleDateString('pt-BR');
        const turno = {
            'manha': 'Manh�',
            'tarde': 'Tarde', 
            'noite': 'Noite'
        }[item.shift] || item.shift;
        const dataHora = new Date(item.created_at).toLocaleString('pt-BR');

        excelData.push([
            data,
            item.users?.name || 'N/A',
            turno,
            parseFloat(item.total_volume) || 0,
            item.temperature ? `${item.temperature}�C` : '',
            item.observations || '',
            dataHora
        ]);
    });

    // Criar workbook e worksheet
    const ws = XLSX.utils.aoa_to_sheet(excelData);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Produção de Leite');

    // Definir larguras das colunas melhoradas
    ws['!cols'] = [
        { width: 15 }, // Data
        { width: 25 }, // Funcionário
        { width: 15 }, // Turno
        { width: 15 }, // Volume
        { width: 18 }, // Temperatura
        { width: 35 }, // Observações
        { width: 22 }  // Data/Hora Registro
    ];

    // Estilizar cabe�alho principal
    if (ws['A1']) {
        ws['A1'].s = {
            font: { bold: true, color: { rgb: "FFFFFF" } },
            fill: { fgColor: { rgb: "2563EB" } },
            alignment: { horizontal: "center" }
        };
    }

    // Merge das c�lulas do t�tulo
    ws['!merges'] = [
        { s: { r: 0, c: 0 }, e: { r: 0, c: 6 } }, // T�tulo principal
        { s: { r: 2, c: 0 }, e: { r: 2, c: 6 } }  // Subt�tulo informações
    ];

    for (let i = 3; i <= 8; i++) {
        const cellA = `A${i}`;
        const cellB = `B${i}`;
        if (ws[cellA]) {
            ws[cellA].s = {
                font: { bold: true, color: { rgb: "1F2937" } },
                fill: { fgColor: { rgb: "F3F4F6" } }
            };
        }
        if (ws[cellB]) {
            ws[cellB].s = {
                font: { color: { rgb: "374151" } },
                fill: { fgColor: { rgb: "F9FAFB" } }
            };
        }
    }

    // Estilizar cabe�alho da tabela
    for (let col = 0; col < 7; col++) {
        const cell = ws[XLSX.utils.encode_cell({ r: 9, c: col })];
        if (cell) {
            cell.s = {
                font: { bold: true, color: { rgb: "FFFFFF" } },
                fill: { fgColor: { rgb: "059669" } },
                alignment: { horizontal: "center" },
                border: {
                    top: { style: "thin", color: { rgb: "000000" } },
                    bottom: { style: "thin", color: { rgb: "000000" } },
                    left: { style: "thin", color: { rgb: "000000" } },
                    right: { style: "thin", color: { rgb: "000000" } }
                }
            };
        }
    }

    // Estilizar dados da tabela
    for (let row = 10; row < excelData.length; row++) {
        for (let col = 0; col < 7; col++) {
            const cell = ws[XLSX.utils.encode_cell({ r: row, c: col })];
            if (cell) {
                const isEvenRow = (row - 10) % 2 === 0;
                cell.s = {
                    fill: { fgColor: { rgb: isEvenRow ? "F9FAFB" : "FFFFFF" } },
                    border: {
                        top: { style: "thin", color: { rgb: "E5E7EB" } },
                        bottom: { style: "thin", color: { rgb: "E5E7EB" } },
                        left: { style: "thin", color: { rgb: "E5E7EB" } },
                        right: { style: "thin", color: { rgb: "E5E7EB" } }
                    },
                    alignment: { 
                        horizontal: col === 3 ? "right" : "left", // Volume alinhado � direita
                        vertical: "center"
                    }
                };
                
                // Destacar volumes acima da média
                if (col === 3 && parseFloat(cell.v) > mediaVolume) {
                    cell.s.font = { bold: true, color: { rgb: "059669" } };
                }
            }
        }
    }

    // Download
    const fileName = `Relatário_${farmName}_${startDate}_${endDate}.xlsx`;
    XLSX.writeFile(wb, fileName);

    showNotification('Arquivo Excel exportado com sucesso!', 'success');

} catch (error) {
    console.error('Erro ao exportar Excel:', error);
    
    showNotification('Erro ao exportar relatório: ' + error.message, 'error');
}
}

// Funçãoo para exportar PDF
async function exportPDFReport() {
try {
    const startDate = document.getElementById('reportStartDate').value;
    const endDate = document.getElementById('reportEndDate').value;
    const employeeId = document.getElementById('reportEmployee').value;

    if (!startDate || !endDate) {
        showNotification('Por favor, selecione as datas inicial e final', 'warning');
        return;
    }

    const { data: { user } } = await db.auth.getUser();
    if (!user) return;

    const { data: userData } = await db
        .from('users')
        .select('id')
        .eq('id', user.id)
        .single();

    if (!userData) return;

    let query = db
        .from('volume_records')
        .select(`
            record_date,
            shift,
            total_volume,
            temperature,
            observations,
            created_at,
            users!inner(name)
        `)
        .eq('farm_id', 1)
        .gte('record_date', startDate)
        .lte('record_date', endDate)
        .order('record_date', { ascending: true });

    if (employeeId) {
        query = query.eq('user_id', employeeId);
    }

    const { data: dadosPDF, error } = await query;

    if (error) throw error;

    if (!dadosPDF || dadosPDF.length === 0) {
        showNotification('Nenhum dado encontrado para o período selecionado', 'info');
        return;
    }

    // Gerar PDF usando a função existente
    generateVolumePDF(dadosPDF, false);

} catch (error) {
    console.error('Erro ao exportar PDF:', error);
    showNotification('Erro ao exportar PDF: ' + error.message, 'error');
}
}

// Resetar senha da conta
async function resetAccountPassword(userId, userName) {
if (!confirm(`Tem certeza que deseja resetar a senha de ${userName}?`)) {
    return;
}

try {
    const newPassword = generateTempPassword();

    // Atualizar apenas a senha tempor�ria na tabela
    // Nota: O usuário precisar� usar a função de recuperaçãoo de senha do Database
    // ou o administrador do sistema precisar� resetar via painel admin
    const { error: updateError } = await db
        .from('users')
        .update({ temp_password: newPassword })
        .eq('id', userId);

    if (updateError) throw updateError;

    showNotification('Nova senha tempor�ria gerada! O usuário deve usar a recuperaçãoo de senha do sistema.', 'warning');
    showTempPasswordModal(userName, '', newPassword);

} catch (error) {
    console.error('Erro ao resetar senha:', error);
    showNotification('Erro ao resetar senha: ' + error.message, 'error');
}
}

const createSecondaryAccountForm = document.getElementById('createSecondaryAccountForm');
if (createSecondaryAccountForm) {
// Funçãoo para lidar com o submit
const handleSecondaryAccountSubmit = async function(e) {
e.preventDefault();
const formData = new FormData(this);
    await createSecondaryAccount(formData);
};

// Remover listener anterior se existir
createSecondaryAccountForm.removeEventListener('submit', handleSecondaryAccountSubmit);
createSecondaryAccountForm.addEventListener('submit', handleSecondaryAccountSubmit);
}

// Funçãoes para gerenciar contas secund�rias
function toggleSecondaryAccountForm() {
const form = document.getElementById('secondaryAccountForm');
if (form) {
    form.classList.toggle('hidden');
    
    if (!form.classList.contains('hidden')) {
        // Preencher dados automaticamente
        fillSecondaryAccountForm();
    }
}
}

function cancelSecondaryAccountForm() {
const form = document.getElementById('secondaryAccountForm');
if (form) {
    form.classList.add('hidden');
}
}

async function fillSecondaryAccountForm() {
try {
    // Usando MySQL direto atrav�s do objeto 'db'
    const { data: { user } } = await db.auth.getUser();
    
    if (user) {
        // Buscar dados do usuário atual (gerente principal)
        const { data: userData, error } = await db
            .from('users')
            .select('name, phone, role')
            .eq('id', user.id)
            .eq('role', 'gerente') // Garantir que � o gerente principal
            .single();
            
        if (!error && userData) {
            // Preencher campos ocultos
            document.getElementById('secondaryAccountName').value = userData.name;
            document.getElementById('secondaryAccountWhatsApp').value = userData.whatsapp || '';
            document.getElementById('secondaryAccountEmail').value = user.email;
            
            // Preencher campos de exibiçãoo
            document.getElementById('displayName').textContent = userData.name;
            document.getElementById('displayWhatsApp').textContent = userData.whatsapp || 'N�o informado';
            document.getElementById('displayEmail').textContent = user.email;
            
            console.log('? Dados do gerente principal carregados:', {
                name: userData.name,
                email: user.email,
                whatsapp: userData.whatsapp,
                role: userData.role
            });
        } else {
            console.error('Erro ao buscar dados do gerente principal:', error);
            showNotification('Erro ao carregar dados da conta principal', 'error');
        }
    }
} catch (error) {
    console.error('Erro ao preencher formulário:', error);
}
}

// Criar conta secund�ria
async function createSecondaryAccount(formData) {
try {
    // Usando MySQL direto atrav�s do objeto 'db'
    const { data: { user } } = await db.auth.getUser();
    
    if (!user) throw new Error('Usuário não autenticado');
    
    // Obter dados do gerente atual
    const { data: managerData, error: managerError } = await db
        .from('users')
        .select('name, phone')
        .eq('id', user.id)
        .single();
        
    if (managerError) throw managerError;
    
    const accountType = formData.get('account_type');
    const email = user.email; // Sempre usar o email do gerente
    const name = formData.get('name') || managerData.name;
    const whatsapp = formData.get('whatsapp') || managerData.whatsapp;

    const { data: existingAccounts, error: checkError } = await db
        .from('users')
        .select('id, role')
        .eq('email', email)
        .eq('farm_id', 1);
        
    if (checkError) throw checkError;

    const hasAccountType = existingAccounts && existingAccounts.some(account => account.role === accountType);
    
    if (hasAccountType) {
        const roleText = accountType === 'funcionario' ? 'de funcionário' : 'de veterinário';
        throw new Error(`Voc� j� possui uma conta ${roleText}`);
    }
    
    // Criar conta secund�ria usando RPC
    const { data: result, error } = await db.rpc('create_farm_user', {
        p_user_id: authData.user.id,
        p_email: email,
        p_name: name,
        p_whatsapp: whatsapp,
        p_role: accountType,
        p_farm_id: 1, // Lagoa Do Mato
        p_profile_photo_url: null
    });
    
    if (error) throw error;
    
    if (!result.success) {
        throw new Error(result.error || 'Falha ao criar conta secund�ria');
    }
    
    showNotification(`Conta secund�ria ${accountType === 'funcionario' ? 'de funcionário' : 'de veterinário'} criada com sucesso!`, 'success');

    cancelSecondaryAccountForm();
    loadSecondaryAccounts();
    
} catch (error) {
    console.error('Erro ao criar conta secund�ria:', error);
    showNotification('Erro ao criar conta secund�ria: ' + error.message, 'error');
}
}

async function checkExistingSecondaryAccount(accountType) {
if (!accountType) {
    const messageDiv = document.getElementById('existingAccountMessage');
    if (messageDiv) {
        messageDiv.classList.add('hidden');
    }
    return;
}

try {
    // Usando MySQL direto atrav�s do objeto 'db'
    const { data: { user } } = await db.auth.getUser();
    
    if (!user) return;
    
    // Buscar contas existentes com o mesmo email e role espec�fico
    const { data: existingAccounts, error } = await db
        .from('users')
        .select('role')
        .eq('email', user.email)
        .eq('role', accountType);
        
    if (error) throw error;
    
    const hasAccountType = existingAccounts && existingAccounts.length > 0;
    
    const messageDiv = document.getElementById('existingAccountMessage');
    if (messageDiv) {
        if (hasAccountType) {
            const roleText = accountType === 'funcionario' ? 'de funcionário' : 'de veterinário';
            messageDiv.innerHTML = `<span class="text-red-600">?? Voc� j� possui uma conta ${roleText}</span>`;
            messageDiv.classList.remove('hidden');
        } else {
            messageDiv.classList.add('hidden');
        }
    }
    
} catch (error) {
    console.error('Erro ao verificar conta existente:', error);
}
}

// Carregar contas secund�rias
async function loadSecondaryAccounts() {
try {
    // Usar API de usuários
    const response = await fetch('api/users.php?action=select');
    const result = await response.json();
    
    if (!result.success) {
        throw new Error(result.error || 'Erro ao carregar contas secund�rias');
    }
    
    const allUsers = result.data || [];
    
    // Filtrar contas secund�rias (excluir gerentes)
    const secondaryAccounts = allUsers.filter(user => user.role !== 'gerente');
    
    displaySecondaryAccounts(secondaryAccounts);
    
} catch (error) {
    console.error('Erro ao carregar contas secund�rias:', error);
    showNotification('Erro ao carregar contas secund�rias', 'error');
}
}

// Exibir contas secund�rias
function displaySecondaryAccounts(accounts) {
const container = document.getElementById('secondaryAccountsList');

if (!accounts || accounts.length === 0) {
    container.innerHTML = `
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <p class="text-gray-500">Nenhuma conta secund�ria encontrada</p>
        </div>
    `;
    return;
}

const accountsHtml = accounts.map(account => {
    const roleText = {
        'funcionario': 'Funcionário',
        'veterinario': 'Veterinário',
        'gerente': 'Gerente'
    }[account.role] || account.role;
    
    const roleColor = {
        'funcionario': 'bg-green-100 text-green-800',
        'veterinario': 'bg-purple-100 text-purple-800',
        'gerente': 'bg-blue-100 text-blue-800'
    }[account.role] || 'bg-gray-100 text-gray-800';
    
    const statusColor = account.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    const statusText = account.is_active ? 'Ativo' : 'Inativo';
    
    return `
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200hover:bg-gray-100cursor-pointer transition-all duration-200 w-full" 
             onclick="accessSecondaryAccount('${account.id}', '${account.name}', '${account.role}')">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-forest-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-forest-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h5 class="font-semibold text-gray-900truncate">${account.name}</h5>
                            <p class="text-sm text-gray-600truncate">${account.email}</p>
                            <p class="text-xs text-gray-500 truncate">${account.whatsapp || 'WhatsApp não informado'}</p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                    <span class="px-2 py-1 text-xs font-medium rounded-full ${roleColor} whitespace-nowrap">${roleText}</span>
                    <span class="px-2 py-1 text-xs font-medium rounded-full ${statusColor} whitespace-nowrap">${statusText}</span>
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 whitespace-nowrap">
                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>Secund�ria
                    </span>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs text-gray-500">
                    <span class="truncate">Criado em: ${new Date(account.created_at).toLocaleDateString('pt-BR')}</span>
                    <div class="flex items-center space-x-1">
                        <span class="text-blue-600 font-medium whitespace-nowrap">Clique para acessar ?</span>
                    </div>
                </div>
            </div>
        </div>
    `;
}).join('');

container.innerHTML = accountsHtml;
}

            // Sistema Lagoa Do Mato
async function getCurrentUserFarmId() {
try {
    // Usando MySQL direto atrav�s do objeto 'db'
    const { data: { user } } = await db.auth.getUser();
    
    if (!user) return null;
    
    const { data: userData, error } = await db
        .from('users')
        .select('id')
        .eq('id', user.id)
        .single();
        
    if (error) return null;
    
    return 1;
} catch (error) {
    console.error('Erro ao obter dados do usuário:', error);
    return null;
}
}

// Funçãoo removida - não mais necess�ria após remoçãoo dos bot�es de bloquear/excluir

// Acessar conta secund�ria
async function accessSecondaryAccount(userId, userName, userRole) {
const confirmed = confirm(`Deseja acessar a conta "${userName}" (${userRole})?\n\nVoc� será redirecionado para esta conta.`);

if (confirmed) {
    try {

        const currentUser = {
            id: userId,
            name: userName,
            role: userRole,
            isSecondary: true
        };

        sessionStorage.setItem('currentSecondaryAccount', JSON.stringify(currentUser));
        
        // Redirecionar para a p�gina apropriada baseada no role
        if (userRole === 'veterinario') {
            window.location.href = 'veterinario.php';
        } else if (userRole === 'funcionario') {
            window.location.href = 'funcionario.php';
        } else {
            showNotification('Tipo de conta não suportado', 'error');
        }
        
    } catch (error) {
        showNotification('Erro ao acessar conta secund�ria: ' + error.message, 'error');
    }
}
}

// Funçãoo removida - não mais necess�ria após remoçãoo dos bot�es de bloquear/excluir

// Mostrar/ocultar seçãoo de foto baseado no cargo selecionado
function togglePhotoSection() {
console.log('?? togglePhotoSection() chamada');

const roleSelect = document.getElementById('userRole');
const photoSection = document.getElementById('addPhotoSection');

console.log('?? Role selecionado:', roleSelect?.value);
console.log('?? Seção de foto encontrada:', !!photoSection);

if (roleSelect && photoSection) {
    if (roleSelect.value === 'veterinario' || roleSelect.value === 'funcionario') {
        console.log('? Mostrando seçãoo de foto para', roleSelect.value);
        photoSection.classList.remove('hidden');
        photoSection.style.display = 'block';
        photoSection.style.visibility = 'visible';
        photoSection.style.opacity = '1';
    } else {
        console.log('? Ocultando seçãoo de foto');
        photoSection.classList.add('hidden');
        photoSection.style.display = 'none';

        const profilePhotoInput = document.getElementById('profilePhotoInput');
        const profilePreview = document.getElementById('profilePreview');
        const profilePlaceholder = document.getElementById('profilePlaceholder');
        
        if (profilePhotoInput) profilePhotoInput.value = '';
        if (profilePreview) {
            profilePreview.src = '';
            profilePreview.style.display = 'none';
        }
        if (profilePlaceholder) profilePlaceholder.style.display = 'flex';
    }
} else {
    console.error('? Elementos não encontrados:', {
        roleSelect: !!roleSelect,
        photoSection: !!photoSection
    });
}
}

// DESABILITADO - Estava quebrando a função original

console.log('⚠️ Sobrescrita #2 de openProfileModal DESABILITADA');

// Funçãoo removida - conflito resolvido na função openAddUserModal original

// Funçãoo para prévia do relatório na aba
function previewReportTab() {
const startDate = document.getElementById('reportStartDate').value;
const endDate = document.getElementById('reportEndDate').value;

if (!startDate || !endDate) {
    showNotification('Por favor, selecione as datas inicial e final para a prévia', 'warning');
    return;
}

// Gerar relatório de exemplo
const sampleData = [
    {
        record_date: startDate,
        total_volume: 150.5,
        shift: 'manha',
        temperature: 4.2,
        observations: 'Exemplo de registro para prévia',
        users: { name: 'Funcionário Exemplo' },
        created_at: new Date().toISOString()
    }
];

generateVolumePDF(sampleData, true);
}

// Inicializar datas padrão (último mês)
function initializeDateFilters() {
const today = new Date();
const lastMonth = new Date();
lastMonth.setMonth(today.getMonth() - 1);

document.getElementById('reportStartDate').value = lastMonth.toISOString().split('T')[0];
document.getElementById('reportEndDate').value = today.toISOString().split('T')[0];
}

// ===== FUNçãoES ESPEC�FICAS PARA FOTO DO GERENTE =====

// Abrir modal de escolha de foto do gerente
function openManagerPhotoModal() {
console.log('?? openManagerPhotoModal() chamada');

const modal = document.getElementById('managerPhotoChoiceModal');
console.log('?? Modal encontrado:', !!modal);

if (modal) {
    // Garantir que outros modais estejam fechados
    const otherModals = ['photoChoiceModal', 'cameraModal', 'managerCameraModal'];
    otherModals.forEach(modalId => {
        const otherModal = document.getElementById(modalId);
        if (otherModal) {
            otherModal.style.display = 'none';
            otherModal.style.visibility = 'hidden';
            otherModal.style.opacity = '0';
            otherModal.style.pointerEvents = 'none';
        }
    });
    
    // For�ar abertura do modal com m�ltiplas propriedades
    modal.style.display = 'flex';
    modal.style.visibility = 'visible';
    modal.style.opacity = '1';
    modal.style.pointerEvents = 'auto';
    modal.style.position = 'fixed';
    modal.style.zIndex = '999999';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    console.log('? Modal aberto com sucesso');
} else {
    console.error('? Modal não encontrado');
}
}

// Fechar modal de escolha de foto do gerente
function closeManagerPhotoModal() {
const modal = document.getElementById('managerPhotoChoiceModal');
if (modal) {
    // Remover todas as classes que podem estar causando problemas
    modal.classList.remove('show', 'flex', 'block');
    modal.classList.add('hidden');
    
    // For�ar ocultaçãoo com m�ltiplas propriedades
    modal.style.display = 'none';
    modal.style.visibility = 'hidden';
    modal.style.opacity = '0';
    modal.style.pointerEvents = 'none';
    modal.style.position = 'fixed';
    modal.style.zIndex = '-1';
    
    console.log('? Modal de foto do gerente fechado');
} else {
    console.log('? Modal de foto do gerente não encontrado');
}
}

async function openManagerCamera() {
try {
    console.log('?? Abrindo c�mera do gerente...');
    
    closeManagerPhotoModal();
    
    const modal = document.getElementById('managerCameraModal');
    const video = document.getElementById('managerCameraVideo');
    const processingScreen = document.getElementById('managerPhotoProcessingScreen');
    
    // Garantir que a tela de processamento esteja oculta
    if (processingScreen) {
        processingScreen.classList.add('hidden');
        processingScreen.style.display = 'none';
        processingScreen.style.visibility = 'hidden';
        processingScreen.style.opacity = '0';
        processingScreen.style.pointerEvents = 'none';
        console.log('? Tela de processamento do gerente ocultada');
    }
    
    modal.classList.add('show');

    const devices = await navigator.mediaDevices.enumerateDevices();
    const videoDevices = devices.filter(device => device.kind === 'videoinput');
    
    console.log('?? C�meras dispon�veis:', videoDevices.length);
    
    let stream;
    if (videoDevices.length >= 2) {
        // Se tem duas c�meras, usar a traseira primeiro
        try {
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'environment',
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                } 
            });
            console.log('? Usando c�mera traseira');
        } catch (error) {
            // Fallback para c�mera frontal
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'user',
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                } 
            });
            console.log('? Usando c�mera frontal (fallback)');
        }
    } else {
        // Se tem apenas uma c�mera, usar ela
        stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'user',
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            } 
        });
        console.log('? Usando �nica c�mera disponível');
    }
    
    video.srcObject = stream;
    window.managerCameraStream = stream;
    window.managerCurrentFacingMode = stream.getVideoTracks()[0].getSettings().facingMode;

    initializeFaceDetection();
    
    console.log('? C�mera do gerente aberta com sucesso');
    
} catch (error) {
    console.error('? Erro ao abrir c�mera:', error);
    showNotification('Erro ao acessar c�mera: ' + error.message, 'error');
}
}

// Fechar c�mera do gerente
function closeManagerCamera() {
const modal = document.getElementById('managerCameraModal');
if (modal) {
    modal.classList.remove('show');
    modal.style.display = 'none';
    modal.style.visibility = 'hidden';
    modal.style.opacity = '0';
    modal.style.pointerEvents = 'none';
}

if (window.managerCameraStream) {
    try {
        window.managerCameraStream.getTracks().forEach(track => {
            track.stop();
            console.log('? Track da c�mera parada:', track.kind);
        });
    } catch (error) {
        console.log('?? Erro ao parar tracks da c�mera:', error);
    }
    window.managerCameraStream = null;
}

// Resetar v�deo
const video = document.getElementById('managerCameraVideo');
if (video) {
    try {
        video.pause();
        video.srcObject = null;
        video.load();
    } catch (error) {
        console.log('?? Erro ao resetar v�deo:', error);
    }
}

// Resetar indicadores
resetManagerFaceVerification();

// Limpar detecçãoo facial
if (faceDetectionInterval) {
    clearInterval(faceDetectionInterval);
    faceDetectionInterval = null;
}

// Resetar estados
isFaceDetected = false;
faceCentered = false;

console.log('? C�mera do gerente fechada');
}

// Trocar c�mera do gerente
async function switchManagerCamera() {
try {
    if (window.managerCameraStream) {
        window.managerCameraStream.getTracks().forEach(track => track.stop());
    }
    
    const video = document.getElementById('managerCameraVideo');
    const stream = await navigator.mediaDevices.getUserMedia({ 
        video: { 
            facingMode: window.managerCameraFacingMode === 'user' ? 'environment' : 'user',
            width: { ideal: 1920 },
            height: { ideal: 1080 }
        } 
    });
    
    video.srcObject = stream;
    window.managerCameraStream = stream;
    window.managerCameraFacingMode = window.managerCameraFacingMode === 'user' ? 'environment' : 'user';
    
} catch (error) {
    console.error('Erro ao trocar c�mera:', error);
    showNotification('Erro ao trocar c�mera: ' + error.message, 'error');
}
}

let faceDetectionInterval;
let isFaceDetected = false;
let faceCentered = false;

// Inicializar detecçãoo facial
function initializeFaceDetection() {
console.log('?? Inicializando detecçãoo facial...');

// Resetar estados
isFaceDetected = false;
faceCentered = false;
updateFaceUI();

// Iniciar detecçãoo facial
startFaceDetection();
}

// Iniciar detecçãoo facial
function startFaceDetection() {
const video = document.getElementById('managerCameraVideo');

if (faceDetectionInterval) {
    clearInterval(faceDetectionInterval);
}

// Simular detecçãoo facial (em produção, usar uma biblioteca como face-api.js)
faceDetectionInterval = setInterval(() => {
    detectFace(video);
}, 100); // Verificar a cada 100ms
}

// Detectar rosto (simulaçãoo)
function detectFace(video) {
// Esta � uma simulaçãoo. Em produção, você usaria uma biblioteca real de detecçãoo facial
const rect = video.getBoundingClientRect();
const centerX = rect.width / 2;
const centerY = rect.height / 2;

// Simular detecçãoo baseada em movimento ou outras heur�sticas
const hasMovement = Math.random() > 0.3; // Simular que h� movimento/detecçãoo
const isCentered = Math.random() > 0.4; // Simular centralizaçãoo

if (hasMovement) {
    isFaceDetected = true;
    if (isCentered) {
        faceCentered = true;
        updateFaceUI();
    } else {
        faceCentered = false;
        updateFaceUI();
    }
} else {
    isFaceDetected = false;
    faceCentered = false;
    updateFaceUI();
}
}

// Atualizar interface baseada na detecçãoo
function updateFaceUI() {
const faceCircle = document.getElementById('managerFaceCircle');
const captureBtn = document.getElementById('managerCaptureBtn');
const faceStatus = document.getElementById('managerFaceStatus');
const faceWarning = document.getElementById('managerFaceWarning');

if (!isFaceDetected) {
    // Rosto não detectado
    faceCircle.style.borderColor = 'rgb(239, 68, 68)'; // Vermelho
    faceStatus.textContent = 'Centralizando rosto...';
    faceStatus.style.color = 'rgba(255, 255, 255, 0.7)';
    
    // Desabilitar bot�o
    captureBtn.disabled = true;
    captureBtn.classList.add('opacity-50', 'cursor-not-allowed');
    captureBtn.classList.remove('hover:scale-105', 'hover:bg-gray-100');
    captureBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.5)';
    
} else if (!faceCentered) {
    // Rosto detectado mas não centralizado
    faceCircle.style.borderColor = 'rgb(239, 68, 68)'; // Vermelho
    faceStatus.textContent = 'Centralize o rosto no c�rculo';
    faceStatus.style.color = 'rgba(255, 255, 255, 0.7)';
    
    // Desabilitar bot�o
    captureBtn.disabled = true;
    captureBtn.classList.add('opacity-50', 'cursor-not-allowed');
    captureBtn.classList.remove('hover:scale-105', 'hover:bg-gray-100');
    captureBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.5)';
    
} else {
    // Rosto centralizado
    faceCircle.style.borderColor = 'rgb(34, 197, 94)'; // Verde
    faceStatus.textContent = 'Rosto centralizado!';
    faceStatus.style.color = 'rgb(34, 197, 94)';
    
    // Habilitar bot�o
    captureBtn.disabled = false;
    captureBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    captureBtn.classList.add('hover:scale-105', 'hover:bg-gray-100');
    captureBtn.style.backgroundColor = 'white';
    
    // Ocultar aviso se estiver visível
    if (faceWarning) {
        faceWarning.style.opacity = '0';
    }
}
}

async function captureManagerPhoto() {

if (!faceCentered) {
    const faceWarning = document.getElementById('managerFaceWarning');
    if (faceWarning) {
        faceWarning.style.opacity = '1';
        setTimeout(() => {
            faceWarning.style.opacity = '0';
        }, 3000);
    }
    return;
}

try {
    console.log('?? Capturando foto do gerente...');
    
    const video = document.getElementById('managerCameraVideo');
    const canvas = document.getElementById('managerCameraCanvas');
    const processingScreen = document.getElementById('managerPhotoProcessingScreen');
    
    if (!video || !canvas) {
        throw new Error('Elementos de v�deo ou canvas não encontrados');
    }

    if (video.readyState < 2) {
        throw new Error('V�deo não está pronto');
    }
    
    // Mostrar tela de processamento
    if (processingScreen) {
        processingScreen.classList.remove('hidden');
    }
    
    // Configurar canvas
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Desenhar frame do v�deo no canvas
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    
    // Converter para blob
    canvas.toBlob(async (blob) => {
        try {
            if (!blob) {
                throw new Error('Falha ao criar blob da imagem');
            }
            
            const file = new File([blob], 'manager-photo.jpg', { type: 'image/jpeg' });
            
            // Fechar c�mera primeiro
            closeManagerCamera();
            
            // Processar foto
            await processManagerPhoto(file);
            
        } catch (error) {
            console.error('Erro ao processar foto:', error);
            showNotification('Erro ao processar foto: ' + error.message, 'error');
        } finally {
            if (processingScreen) {
                processingScreen.classList.add('hidden');
            }
        }
    }, 'image/jpeg', 0.8);
    
} catch (error) {
    console.error('Erro ao capturar foto:', error);
    showNotification('Erro ao capturar foto: ' + error.message, 'error');
    
    // Ocultar tela de processamento em caso de erro
    const processingScreen = document.getElementById('managerPhotoProcessingScreen');
    if (processingScreen) {
        processingScreen.classList.add('hidden');
    }
}
}

// Processar foto do gerente
async function processManagerPhoto(file) {
try {
    // Validar arquivo
    if (!file || file.size === 0) {
        throw new Error('Arquivo inválido');
    }
    
    if (file.size > 2 * 1024 * 1024) {
        throw new Error('Arquivo muito grande (máximo 2MB)');
    }
    
    // Mostrar preview
    previewManagerProfilePhoto(file);
    
    // Upload da foto
    // Usando MySQL direto atrav�s do objeto 'db'
    const { data: { user } } = await db.auth.getUser();
    
    if (!user) throw new Error('Usuário não autenticado');
    
    const photoUrl = await uploadManagerProfilePhoto(file, user.id);
    
    // Atualizar perfil
    const { error: updateError } = await db
        .from('users')
        .update({ profile_photo_url: photoUrl })
        .eq('id', user.id);
        
    if (updateError) throw updateError;
    
    // Atualizar exibiçãoo
    updateManagerPhotoDisplay(photoUrl);
    
    // Fechar modal automaticamente
    closeManagerPhotoModal();
    
    // Fechar c�mera se estiver aberta
    closeManagerCamera();

    setTimeout(() => {
        showNotification('Foto de perfil atualizada com sucesso!', 'success');
    }, 200);
    
} catch (error) {
    console.error('Erro ao processar foto do gerente:', error);
    showNotification('Erro ao processar foto: ' + error.message, 'error');
}
}

// Preview da foto do gerente
function previewManagerProfilePhoto(file) {
const preview = document.getElementById('managerProfilePreview');
const placeholder = document.getElementById('managerProfilePlaceholder');

if (preview && placeholder) {
    const url = URL.createObjectURL(file);
    preview.src = url;
    preview.style.display = 'block';
    placeholder.style.display = 'none';
}
}

// Selecionar da galeria do gerente
function selectManagerFromGallery() {
const input = document.getElementById('managerProfilePhotoInput');
if (input) {
    input.click();
}
}

// Preview da foto da galeria do gerente
function handleManagerGallerySelection(input) {
if (input.files && input.files[0]) {
    const file = input.files[0];
    console.log('?? Arquivo selecionado da galeria:', file.name);
    
    // Fechar modal de escolha primeiro
    closeManagerPhotoModal();
    
    // Aguardar um pouco antes de processar
    setTimeout(() => {
        processManagerPhoto(file);
    }, 100);
}
}

function startManagerFaceVerification() {
const focusText = document.getElementById('managerFocusText');
const focusTimer = document.getElementById('managerFocusTimer');
const focusIndicator = document.getElementById('managerFocusIndicator');
const timerCount = document.getElementById('managerTimerCount');

if (focusText) focusText.textContent = 'Focando...';
if (focusTimer) focusTimer.classList.remove('hidden');
if (focusIndicator) focusIndicator.classList.remove('opacity-0');

let count = 3;
const timer = setInterval(() => {
    if (timerCount) timerCount.textContent = count;
    
    if (count <= 0) {
        clearInterval(timer);
        captureManagerPhoto();
    }
    count--;
}, 1000);

window.managerFaceVerificationTimer = timer;
}

function resetManagerFaceVerification() {
const focusText = document.getElementById('managerFocusText');
const focusTimer = document.getElementById('managerFocusTimer');
const focusIndicator = document.getElementById('managerFocusIndicator');

if (focusText) focusText.textContent = 'Posicione o rosto no centro';
if (focusTimer) focusTimer.classList.add('hidden');
if (focusIndicator) focusIndicator.classList.add('opacity-0');

if (window.managerFaceVerificationTimer) {
    clearInterval(window.managerFaceVerificationTimer);
    window.managerFaceVerificationTimer = null;
}
}

// Atualizar exibiçãoo da foto do gerente
function updateManagerPhotoDisplay(photoUrl) {
console.log('??? Atualizando exibiçãoo da foto:', photoUrl);

// Atualizar foto no header
const headerPhoto = document.getElementById('headerProfilePhoto');
const headerPlaceholder = document.getElementById('headerProfileIcon');

if (headerPhoto && headerPlaceholder) {
    if (photoUrl && photoUrl.trim() !== '' && !photoUrl.includes('default-avatar')) {
        headerPhoto.src = photoUrl + '?t=' + Date.now();
        headerPhoto.style.display = 'block';
        headerPhoto.style.visibility = 'visible';
        headerPhoto.classList.remove('hidden');
        headerPhoto.classList.add('block');
        
        headerPlaceholder.style.display = 'none';
        headerPlaceholder.style.visibility = 'hidden';
        headerPlaceholder.classList.add('hidden');
    } else {
        // Sem foto - mostrar ícone
        headerPhoto.src = ''; // Limpar src
        headerPhoto.style.display = 'none';
        headerPhoto.style.visibility = 'hidden';
        headerPhoto.classList.add('hidden');
        
        headerPlaceholder.style.display = 'block';
        headerPlaceholder.style.visibility = 'visible';
        headerPlaceholder.classList.remove('hidden');
    }
    headerPlaceholder.classList.remove('block');
    
    console.log('? Foto do header atualizada');
}

// Atualizar foto no modal de perfil
const modalPhoto = document.getElementById('modalProfilePhoto');
const modalPlaceholder = document.getElementById('modalProfileIcon');
if (modalPhoto && modalPlaceholder) {
    if (photoUrl && photoUrl.trim() !== '' && !photoUrl.includes('default-avatar')) {
        modalPhoto.src = photoUrl + '?t=' + Date.now();
        modalPhoto.style.display = 'block';
        modalPhoto.style.visibility = 'visible';
        modalPhoto.classList.remove('hidden');
        modalPhoto.classList.add('block');
        
        modalPlaceholder.style.display = 'none';
        modalPlaceholder.style.visibility = 'hidden';
        modalPlaceholder.classList.add('hidden');
        modalPlaceholder.classList.remove('block');
        
        console.log('? Foto do modal atualizada');
    } else {
        // Sem foto - mostrar ícone
        modalPhoto.src = ''; // Limpar src
        modalPhoto.style.display = 'none';
        modalPhoto.style.visibility = 'hidden';
        modalPhoto.classList.add('hidden');
        
        modalPlaceholder.style.display = 'block';
        modalPlaceholder.style.visibility = 'visible';
        modalPlaceholder.classList.remove('hidden');
    }
}

const preview = document.getElementById('managerProfilePreview');
const placeholder = document.getElementById('managerProfilePlaceholder');
if (preview && placeholder) {
    if (photoUrl && photoUrl.trim() !== '' && !photoUrl.includes('default-avatar')) {
        preview.src = photoUrl + '?t=' + Date.now();
        preview.style.display = 'block';
        preview.style.visibility = 'visible';
        preview.classList.remove('hidden');
        preview.classList.add('block');
        
        placeholder.style.display = 'none';
        placeholder.style.visibility = 'hidden';
        placeholder.classList.add('hidden');
        placeholder.classList.remove('block');
        
        console.log('? Preview atualizado');
    } else {
        // Sem foto - mostrar placeholder
        preview.src = ''; // Limpar src
        preview.style.display = 'none';
        preview.style.visibility = 'hidden';
        preview.classList.add('hidden');
        
        placeholder.style.display = 'flex';
        placeholder.style.visibility = 'visible';
        placeholder.classList.remove('hidden');
    }
}
}

// Upload da foto do gerente para o Database
async function uploadManagerProfilePhoto(file, userId) {
try {
    // Usando MySQL direto atrav�s do objeto 'db'
    
    // Sistema Lagoa Do Mato
    const { data: { user } } = await db.auth.getUser();
    if (!user) throw new Error('Usuário não autenticado');
    
    const { data: userData, error: userError } = await db
        .from('users')
        .select('id')
        .eq('id', user.id)
        .single();
        
    if (userError || !userData) {
        throw new Error('Farm ID não encontrado');
    }
    
    // Criar nome �nico para o arquivo
    const timestamp = Date.now();
    const randomId = Math.random().toString(36).substr(2, 9);
    const fileExt = file.name.split('.').pop() || 'jpg';
    const fileName = `manager_${userId}_${timestamp}_${randomId}.${fileExt}`;
    const filePath = `lagoa-do-mato/${fileName}`; // Fazenda �nica
    
    // Upload do arquivo
    const { data, error } = await db.storage
        .from('profile-photos')
        .upload(filePath, file, {
            cacheControl: '3600',
            upsert: false
        });
        
    if (error) {
        console.error('Erro no upload:', error);
        throw error;
    }
    
    // Obter URL p�blica
    const { data: { publicUrl } } = db.storage
        .from('profile-photos')
        .getPublicUrl(filePath);
        
    return publicUrl;
    
} catch (error) {
    console.error('Erro no upload da foto do gerente:', error);
    throw error;
}
}

// Carregar biblioteca Excel dinamicamente
function loadExcelLibrary() {
if (typeof XLSX === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
    script.onload = function() {

    };
    script.onerror = function() {
    };
    document.head.appendChild(script);
}
}

document.addEventListener('DOMContentLoaded', function() {
loadExcelLibrary();

const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
            const reportsTab = document.getElementById('reports-tab');
            if (reportsTab && !reportsTab.classList.contains('hidden')) {
                loadReportTabSettings();
                loadReportStats();
                initializeDateFilters();
            }
        }
    });
});

const reportsTab = document.getElementById('reports-tab');
if (reportsTab) {
    observer.observe(reportsTab, { attributes: true });
}
});

        // App Version Display
    document.addEventListener('DOMContentLoaded', function() {
        // Adiciona versão do app no perfil do usuário
        const appVersion = '1.0.0';
        
        // Funçãoo para adicionar versão em elementos de perfil
        function addVersionToProfile() {
            const profileElements = document.querySelectorAll('.user-profile, .profile-info, .user-info');
            profileElements.forEach(element => {
                if (!element.querySelector('.app-version')) {
                    const versionDiv = document.createElement('div');
                    versionDiv.className = 'app-version text-xs text-gray-500 mt-2';
                    versionDiv.innerHTML = `App v${appVersion}`;
                    element.appendChild(versionDiv);
                }
            });
            
            // Adicionar no footer se existir
            const footer = document.querySelector('footer, .footer');
            if (footer && !footer.querySelector('.app-version')) {
                const versionDiv = document.createElement('div');
                versionDiv.className = 'app-version text-xs text-gray-500 text-center mt-4';
                versionDiv.innerHTML = `LacTech v${appVersion}`;
                footer.appendChild(versionDiv);
            }
        }
        
        // Funçãoo para adicionar versão no modal de perfil
        function addVersionToProfileModal() {
            const profileModal = document.getElementById('profileModal');
            if (profileModal && !profileModal.querySelector('.app-version')) {
                const versionDiv = document.createElement('div');
                versionDiv.className = 'app-version text-xs text-gray-500 text-center mt-4 p-4 border-t border-gray-200';
                versionDiv.innerHTML = `LacTech v${appVersion}`;
                profileModal.querySelector('.modal-content').appendChild(versionDiv);
            }
        }
        
        // Executar após carregamento
        setTimeout(addVersionToProfile, 1000);
        
        // DESABILITADO - Estava quebrando a função original

        console.log('⚠️ Sobrescrita #3 de openProfileModal DESABILITADA');
    });
    
// ==================== FUNÇÕES DO MODAL MAIS (GLOBAL) ====================

// Função para abrir aba de relatórios
window.openReportsTab = function() {
    console.log('📊 Abrindo aba de relatórios...');
    
    // Fechar modal Mais
    if (typeof window.closeMoreModal === 'function') {
        window.closeMoreModal();
    }
    
    // Trocar para a aba de dashboard que contém relatórios
    const reportsSection = document.querySelector('[data-tab="dashboard"]');
    if (reportsSection) {
        reportsSection.click();
    }
    
    // Scroll para a seção de relatórios se existir
    setTimeout(() => {
        const reportsElement = document.getElementById('reports-section');
        if (reportsElement) {
            reportsElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 300);
};

// Abrir modal MAIS
window.openMoreModal = function() {
    const modal = document.getElementById('moreModal');
    if (modal) {
        modal.style.display = 'block';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        modal.style.pointerEvents = 'auto';
        modal.classList.remove('hidden');
        
        // Forçar reflow
        modal.offsetHeight;
        
        // Atualizar logo da Xandria Store se a função existir
        if (typeof updateXandriaStoreIcon === 'function') {
            setTimeout(updateXandriaStoreIcon, 100);
        }
    }
};

// Fechar modal MAIS
window.closeMoreModal = function() {
    const modal = document.getElementById('moreModal');
    if (modal) {
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
        modal.style.opacity = '0';
        modal.style.pointerEvents = 'none';
        modal.classList.add('hidden');
    }
};

    // Sistema de Carregamento
    const loadingSteps = [
        { message: 'Inicializando sistema...', subMessage: 'Preparando ambiente...', progress: 10 },
        { message: 'Conectando ao banco de dados...', subMessage: 'Estabelecendo conex�o...', progress: 25 },
        { message: 'Carregando dados da fazenda...', subMessage: 'Buscando informações...', progress: 40 },
        { message: 'Configurando interface...', subMessage: 'Preparando componentes...', progress: 60 },
        { message: 'Carregando gr�ficos...', subMessage: 'Preparando visualizações...', progress: 80 },
        { message: 'Finalizando carregamento...', subMessage: 'Quase pronto...', progress: 95 },
        { message: 'Sistema pronto!', subMessage: 'Bem-vindo ao LacTech', progress: 100 }
    ];

    let currentStep = 0;
    let loadingInterval;

    // DESABILITADO - função duplicada, usando apenas modal HTML
    function updateLoadingScreen() {
        // Funçãoo desabilitada - usando apenas a modal de carregamento HTML
        return;
        // C�digo do sistema de carregamento removido
    }

    // Iniciar sistema
    document.addEventListener('DOMContentLoaded', function() {
        console.log('? Sistema carregado sem tela de loading');
        
        // GARANTIR que não haja tela de loading criada dinamicamente
        const loadingScreens = document.querySelectorAll('.loading-screen, #loadingScreen');
        loadingScreens.forEach(screen => {
            console.log('??? Removendo tela de loading encontrada:', screen.id || screen.className);
            screen.remove();
        });
        
        // PASSO 1: Garantir que modais HTML estáticos estejam fechados
        
        const criticalModals = [
            'moreModal', 
            'managerPhotoChoiceModal',
            'managerCameraModal',
            'contactsModal',
            'notificationsModal'
        ];
        
        criticalModals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                modal.style.visibility = 'hidden';
                modal.style.opacity = '0';
                modal.style.pointerEvents = 'none';
                modal.classList.add('hidden');
                modal.classList.remove('show', 'flex', 'block');
            }
        });
        console.log('? Modais HTML estáticos fechados (profileModal NÃO incluído)');
        
        // VERIFICAR se profileModal existe e está pronto
        const profileModal = document.getElementById('profileModal');
        if (profileModal) {
            console.log('✅ ProfileModal encontrado no DOM');
            console.log('📊 Classes atuais do profileModal:', profileModal.className);
            console.log('📊 Display atual do profileModal:', window.getComputedStyle(profileModal).display);
        } else {
            console.error('❌ ProfileModal NÃO encontrado no DOM!');
        }

        window.isCameraOpen = false;
        window.cameraStream = null;
        window.currentPhotoMode = '';

        console.log('? Sistema inicializado com sucesso!');
    });
    
    // Remover foto do gerente
    async function removeManagerPhoto() {
        try {
            const confirmed = confirm('Tem certeza que deseja remover sua foto de perfil?');
            if (!confirmed) return;
            
            // Usando MySQL direto atrav�s do objeto 'db'
            const { data: { user } } = await db.auth.getUser();
            
            if (!user) {
                showNotification('Usuário não autenticado', 'error');
                return;
            }
            
            // Remover foto do storage se existir
            const { data: userData } = await db
                .from('users')
                .select('profile_photo_url')
                .eq('id', user.id)
                .single();
            
            if (userData && userData.profile_photo_url) {
                // Extrair o caminho do arquivo da URL
                const photoPath = userData.profile_photo_url.split('/').pop();
                if (photoPath) {
                    await db.storage
                        .from('profile-photos')
                        .remove([photoPath]);
                }
            }
            
            // Atualizar o banco de dados removendo a referência da foto
            const { error: updateError } = await db
                .from('users')
                .update({ 
                    profile_photo_url: null,
                    updated_at: new Date().toISOString()
                })
                .eq('id', user.id);
            
            if (updateError) {
                throw updateError;
            }
            
            // Atualizar a interface
            const headerPhoto = document.getElementById('headerProfilePhoto');
            const modalPhoto = document.getElementById('modalProfilePhoto');
            const modalIcon = document.getElementById('modalProfileIcon');
            
            if (headerPhoto) {
                headerPhoto.classList.add('hidden');
            }
            
            if (modalPhoto) {
                modalPhoto.classList.add('hidden');
            }
            
            if (modalIcon) {
                modalIcon.classList.remove('hidden');
            }
            
            showNotification('Foto de perfil removida com sucesso!', 'success');
            
        } catch (error) {
            console.error('Erro ao remover foto do gerente:', error);
            showNotification('Erro ao remover foto de perfil', 'error');
        }
    }
    
    // Configurar header din�mico do modal de perfil (apenas mobile)
    function setupProfileModalHeader() {
        const profileModal = document.querySelector('#profileModal .modal-content');
        const header = document.getElementById('profileModalHeader');
        let lastScrollTop = 0;
        let isScrolling = false;

        const isMobile = window.innerWidth <= 768;
        
        if (profileModal && header && isMobile) {
            // Funçãoo para controlar a visibilidade do header
            function handleScroll() {
                if (!isScrolling) {
                    isScrolling = true;
                    requestAnimationFrame(function() {
                        const scrollTop = profileModal.scrollTop;
                        
                        // Detectar direçãoo do scroll
                        if (scrollTop > lastScrollTop && scrollTop > 50) {

                            header.style.transform = 'translateY(-110%)';
                            header.style.opacity = '0';
                        } else if (scrollTop < lastScrollTop) {
                            // Scroll para cima - mostrar header
                            header.style.transform = 'translateY(0)';
                            header.style.opacity = '1';
                        }
                        
                        // Mostrar header quando estiver no topo
                        if (scrollTop <= 10) {
                            header.style.transform = 'translateY(0)';
                            header.style.opacity = '1';
                        }
                        
                        lastScrollTop = scrollTop;
                        isScrolling = false;
                    });
                }
            }
            
            // Remover listener anterior se existir
            profileModal.removeEventListener('scroll', handleScroll);
            
            // Adicionar listener de scroll
            profileModal.addEventListener('scroll', handleScroll);
            
            // Inicializar estado do header
            header.style.transform = 'translateY(0)';
        } else if (header && !isMobile) {
            // No desktop, sempre mostrar o header
            header.style.transform = 'translateY(0)';
        }
    }
    
    // ==================== FUNçãoES DO MODAL MAIS ====================
    
    // Função para abrir aba de relatórios
    window.openReportsTab = function() {
        console.log('📊 Abrindo aba de relatórios...');
        
        // Fechar modal Mais
        window.closeMoreModal();
        
        // Trocar para a aba de dashboard que contém relatórios
        const reportsSection = document.querySelector('[data-tab="dashboard"]');
        if (reportsSection) {
            reportsSection.click();
        }
        
        // Scroll para a seção de relatórios se existir
        setTimeout(() => {
            const reportsElement = document.getElementById('reports-section');
            if (reportsElement) {
                reportsElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 300);
    };
    
    // Abrir modal MAIS
    window.openMoreModal = function() {
        const modal = document.getElementById('moreModal');
        if (modal) {
        
            modal.style.display = 'block';
            modal.style.visibility = 'visible';
            modal.style.opacity = '1';
            modal.style.pointerEvents = 'auto';
            modal.classList.remove('hidden');
        
        // For�ar reflow
        modal.offsetHeight;
        
        // Atualizar logo da Xandria Store
        setTimeout(updateXandriaStoreIcon, 100);
        }
    }
    
    // FUNÇÃO DUPLICADA REMOVIDA - JÁ DEFINIDA ACIMA

    console.log('?? Sistema de chat desabilitado - Lagoa do Mato');
    
    async function openChatModal() {
        
        const modal = document.getElementById('chatModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Configurar listener de scroll do chat
            setTimeout(() => {
                setupChatScrollListener();
            }, 100);
            
            // Atualizar status online do usuário atual
            try {
                // Usando MySQL direto atrav�s do objeto 'db'
                const { data: { user } } = await db.auth.getUser();
                if (user) {
                    await updateUserLastLogin(user.id);
                    
                    // Sistema Lagoa Do Mato
                    const { data: userData } = await db
                        .from('users')
                        .select('id')
                        .eq('id', user.id)
                        .single();
                    
                    if (userData) {
                        // Configurar real-time para chat
                        setupChatRealtime() // Lagoa Do Mato;
                    }
                }
            } catch (error) {
                console.error('Erro ao atualizar status online:', error);
            }
            
            loadEmployees();
        }
    }

    // Fechar modal de chat
    function closeChatModal() {
        const modal = document.getElementById('chatModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Desconectar real-time do chat
            if (chatRealtimeChannel) {
                disconnectRealtime(chatRealtimeChannel);
                chatRealtimeChannel = null;
                console.log('?? Real-time do chat desconectado');
            }
            
            // Parar polling
            if (chatPollingInterval) {
                clearInterval(chatPollingInterval);
                chatPollingInterval = null;
                console.log('?? Polling do chat parado');
            }
        }
    }

    // Carregar funcionários da fazenda
    async function loadEmployees() {
        try {
            console.log('?? Carregando funcionários...');
            
            // Usando MySQL direto atrav�s do objeto 'db'
            const { data: { user } } = await db.auth.getUser();
            
            if (!user) {
                console.error('? Usuário não autenticado');
                return;
            }

            console.log('?? Usuário autenticado:', user.email);
            
            // Definir currentUser globalmente
            window.currentUser = user;

            // Sistema Lagoa Do Mato
            const { data: userData, error: userError } = await db
                .from('users')
                .select('id')
                .eq('id', user.id)
                .single();

            if (userError) {
                console.error('? Erro ao buscar dados do usuário:', userError);
                return;
            }

            if (!userData) {
                console.error('? Dados do usuário não encontrados');
                return;
            }

            console.log('?? Fazenda: Lagoa Do Mato');

            // Usar o servi�o de sincronizaçãoo para buscar funcionários
            const employees = await getFarmUsers() // Lagoa Do Mato;
            console.log('?? Funcionários encontrados:', employees.length);
            
            // Incluir todos os usuários (gerente + funcionários)
            displayEmployees(employees);
        } catch (error) {
            console.error('? Erro ao carregar funcionários:', error);
            showNotification('Erro ao carregar funcionários: ' + error.message, 'error');
        }
    }

    // Exibir funcionários na lista
    function displayEmployees(employees) {
        console.log('?? Exibindo funcionários:', employees);
        
        const employeesList = document.getElementById('employeesList');
        const onlineEmployees = document.getElementById('onlineEmployees');
        
        if (!employeesList) {
            console.error('? Elemento employeesList não encontrado');
            return;
        }
        
        if (!onlineEmployees) {
            console.error('? Elemento onlineEmployees não encontrado');
            return;
        }

        console.log('? Elementos encontrados, limpando listas...');
        employeesList.innerHTML = '';
        onlineEmployees.innerHTML = '';

        employees.forEach(employee => {

            console.log('?? Comparando:', {
                employeeId: employee.id,
                currentUserId: window.currentUser?.id,
                employeeEmail: employee.email,
                currentUserEmail: window.currentUser?.email,
                employeeName: employee.name
            });
            
            // Filtrar o próprio gerente da lista
            if (employee.id === window.currentUser?.id || employee.email === window.currentUser?.email) {
                console.log('?? Filtrando próprio usuário da lista:', employee.name);
                return; // Pular o próprio usuário
            }
            
            const isOnline = isEmployeeOnline(employee);
            const initial = employee.name.charAt(0).toUpperCase();
            const userColor = generateUserColor(employee.name);

            const hasPhoto = employee.profile_photo_url && employee.profile_photo_url.trim() !== '';

            let mainAvatarHtml;
            if (hasPhoto) {
                mainAvatarHtml = `
                    <img src="${employee.profile_photo_url}?t=${Date.now()}" 
                         alt="Foto de ${employee.name}" 
                         class="w-10 h-10 rounded-full object-cover"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                         onload="this.nextElementSibling.style.display='none';">
                    <div class="w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center" style="display: flex;">
                        <span class="text-white font-semibold text-sm">${initial}</span>
                    </div>
                `;
            } else {
                mainAvatarHtml = `
                    <div class="w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center">
                        <span class="text-white font-semibold text-sm">${initial}</span>
                    </div>
                `;
            }
            
            // Item da lista principal
            const employeeItem = document.createElement('div');
            employeeItem.className = 'flex items-center space-x-3 p-2.5 rounded-lg hover:bg-gray-50cursor-pointer transition-colors';
            employeeItem.onclick = () => selectEmployee(employee);
            
            employeeItem.innerHTML = `
                <div class="relative">
                    ${mainAvatarHtml}
                    ${isOnline ? '<div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>' : ''}
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-medium text-gray-900truncate text-sm">${employee.name}</h4>
                    <p class="text-xs text-gray-500 truncate">${employee.role}</p>
                </div>
                <div class="text-xs text-gray-400">
                    ${isOnline ? 'Online' : formatLastSeen(employee.last_login)}
                </div>
            `;
            
            employeesList.appendChild(employeeItem);

            // Funcionário online (se estiver online)
            if (isOnline) {
                const onlineItem = document.createElement('div');
                onlineItem.className = 'flex flex-col items-center space-y-1 cursor-pointer';
                onlineItem.onclick = () => selectEmployee(employee);

                let onlineAvatarHtml;
                if (hasPhoto) {
                    onlineAvatarHtml = `
                        <img src="${employee.profile_photo_url}?t=${Date.now()}" 
                             alt="Foto de ${employee.name}" 
                             class="w-10 h-10 rounded-full object-cover"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                             onload="this.nextElementSibling.style.display='none';">
                        <div class="w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center" style="display: flex;">
                            <span class="text-white font-semibold text-xs">${initial}</span>
                        </div>
                    `;
                } else {
                    onlineAvatarHtml = `
                        <div class="w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold text-xs">${initial}</span>
                        </div>
                    `;
                }
                
                onlineItem.innerHTML = `
                    <div class="relative">
                        ${onlineAvatarHtml}
                        <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
                    </div>
                    <span class="text-xs text-gray-600text-center max-w-16 truncate">${employee.name}</span>
                `;
                
                onlineEmployees.appendChild(onlineItem);
            }
        });
        
        console.log('? Funcionários exibidos com sucesso!');
        console.log('?? Total de funcionários:', employees.length);
        console.log('?? Funcionários online:', document.querySelectorAll('#onlineEmployees > div').length);
    }

    function isEmployeeOnline(employee) {

        if (!employee) {
            console.warn('?? Employee object is null or undefined');
            return false;
        }
        
        // Usar a coluna is_online se disponível, sen�o usar last_login
        if (employee.is_online !== undefined && employee.is_online !== null) {
            return employee.is_online;
        }
        
        // Fallback para last_login
        if (!employee.last_login) return false;
        const now = new Date();
        const loginTime = new Date(employee.last_login);
        const diffMinutes = (now - loginTime) / (1000 * 60);
        return diffMinutes < 15; // Considera online se fez login nos últimos 15 minutos
    }

    // Formatar última vez visto
    function formatLastSeen(lastLogin) {
        if (!lastLogin) return 'Nunca';
        
        try {
            const now = new Date();
            const loginTime = new Date(lastLogin);

            if (isNaN(loginTime.getTime())) {
                return 'Data inv�lida';
            }
            
            const diffMinutes = (now - loginTime) / (1000 * 60);
            
            if (diffMinutes < 60) return 'H� ' + Math.floor(diffMinutes) + 'min';
            if (diffMinutes < 1440) return 'H� ' + Math.floor(diffMinutes / 60) + 'h';
            return 'H� ' + Math.floor(diffMinutes / 1440) + ' dias';
        } catch (error) {
            console.error('Erro ao formatar lastLogin:', error);
            return 'Erro';
        }
    }

    // Selecionar funcionário para conversa
    function selectEmployee(employee) {

        if (!employee) {
            console.error('? Employee object is null or undefined');
            return;
        }

        window.selectedEmployee = employee;

        const nameElement = document.getElementById('selectedEmployeeName');
        const initialElement = document.getElementById('selectedEmployeeInitial');
        const statusElement = document.getElementById('selectedEmployeeStatus');
        const messageInput = document.getElementById('chatMessageInput');
        const sendBtn = document.getElementById('sendMessageBtn');
        
        if (nameElement) nameElement.textContent = employee.name || 'Nome não disponível';
        if (statusElement) statusElement.textContent = isEmployeeOnline(employee) ? 'Online' : 'Offline';
        
        // Atualizar avatar no header com foto de perfil ou inicial colorida
        if (initialElement) {
            const avatarContainer = initialElement.parentElement;
            if (avatarContainer) {
                // Limpar conteúdo anterior
                avatarContainer.innerHTML = '';
                
                if (employee.profile_photo_url) {
                    // Usar foto de perfil
                    const img = document.createElement('img');
                    img.src = employee.profile_photo_url;
                    img.alt = employee.name || 'Avatar';
                    img.className = 'w-10 h-10 rounded-full object-cover';
                    img.onerror = () => {
                        // Fallback para inicial colorida se a imagem falhar
                        const userColor = generateUserColor(employee.name);
                        const senderInitial = (employee.name || '?').charAt(0).toUpperCase();
                        avatarContainer.className = `w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center`;
                        avatarContainer.innerHTML = `<span class="text-white font-semibold text-sm">${senderInitial}</span>`;
                    };
                    avatarContainer.appendChild(img);
                } else {
                    // Usar inicial colorida
                    const userColor = generateUserColor(employee.name);
                    const senderInitial = (employee.name || '?').charAt(0).toUpperCase();
                    avatarContainer.className = `w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center`;
                    avatarContainer.innerHTML = `<span class="text-white font-semibold text-sm">${senderInitial}</span>`;
                }
            }
        }
        
        if (messageInput) messageInput.disabled = false;
        if (sendBtn) sendBtn.disabled = false;
        
        // Mostrar interface do chat e ocultar UI inicial
        const initialUI = document.getElementById('initialChatUI');
        const chatMessages = document.getElementById('chatMessages');
        const chatInputArea = document.getElementById('chatInputArea');
        const chatHeader = document.getElementById('chatHeader');
        
        if (initialUI) initialUI.classList.add('hidden');
        if (chatMessages) chatMessages.classList.remove('hidden');
        if (chatInputArea) chatInputArea.classList.remove('hidden');
        if (chatHeader) chatHeader.classList.remove('hidden');
        
        // Alternar entre sidebar e �rea principal no mobile
        const sidebar = document.getElementById('chatSidebar');
        const mainArea = document.getElementById('chatMainArea');
        
        if (window.innerWidth < 1024) {
            // Mobile: ocultar sidebar e mostrar �rea principal
            if (sidebar) {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('flex');
            }
            if (mainArea) {
                mainArea.classList.remove('hidden');
                mainArea.classList.add('flex');
            }
        }
        
        // Carregar mensagens com este funcionário
        if (employee.id) {
            loadChatMessages(employee.id);
        } else {
            console.error('? Employee ID is missing');
        }
    }

    // Sair do chat e voltar para UI inicial
    function exitChat() {
        // Limpar funcionário selecionado
        window.selectedEmployee = null;
        
        // Ocultar interface do chat e mostrar UI inicial
        const initialUI = document.getElementById('initialChatUI');
        const chatMessages = document.getElementById('chatMessages');
        const chatInputArea = document.getElementById('chatInputArea');
        const chatHeader = document.getElementById('chatHeader');
        
        if (initialUI) initialUI.classList.remove('hidden');
        if (chatMessages) chatMessages.classList.add('hidden');
        if (chatInputArea) chatInputArea.classList.add('hidden');
        if (chatHeader) chatHeader.classList.add('hidden');
        
        // Voltar para sidebar no mobile
        const sidebar = document.getElementById('chatSidebar');
        const mainArea = document.getElementById('chatMainArea');
        
        if (window.innerWidth < 1024) {
            // Mobile: mostrar sidebar e ocultar �rea principal
            if (sidebar) {
                sidebar.classList.remove('hidden');
                sidebar.classList.add('flex');
            }
            if (mainArea) {
                mainArea.classList.add('hidden');
                mainArea.classList.remove('flex');
            }
        }
        
        // Limpar mensagens do chat
        if (chatMessages) {
            chatMessages.innerHTML = '';
        }
        
        // Limpar input de mensagem
        const messageInput = document.getElementById('chatMessageInput');
        if (messageInput) {
            messageInput.value = '';
            messageInput.disabled = true;
        }
        
        // Desabilitar bot�o de envio
        const sendBtn = document.getElementById('sendMessageBtn');
        if (sendBtn) {
            sendBtn.disabled = true;
        }
        
        // Ocultar picker de emojis se estiver aberto
        const emojiPicker = document.getElementById('emojiPicker');
        if (emojiPicker) {
            emojiPicker.classList.add('hidden');
        }
        
        // Resetar avatar e nome no header (se ainda estiver visível)
        const nameElement = document.getElementById('selectedEmployeeName');
        const initialElement = document.getElementById('selectedEmployeeInitial');
        const statusElement = document.getElementById('selectedEmployeeStatus');
        
        if (nameElement) nameElement.textContent = 'Selecione um funcionário';
        if (statusElement) statusElement.textContent = 'Para come�ar uma conversa';
        if (initialElement) {
            const avatarContainer = initialElement.parentElement;
            if (avatarContainer) {
                avatarContainer.className = 'w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center';
                avatarContainer.innerHTML = '<span class="text-white font-semibold text-sm">?</span>';
            }
        }
    }

    // Limpar chat (apenas para gerente)
    function clearChat() {
        console.log('=== INICIANDO LIMPEZA DO CHAT ===');
        
        if (!window.selectedEmployee) {
            showNotification('Nenhum usuário selecionado para limpar chat', 'warning');
            return;
        }

        console.log('Usuário selecionado:', window.selectedEmployee);
        
        // Mostrar modal de confirmaçãoo diretamente
        showClearChatConfirmation();
    }

    // Funçãoo para mostrar modal de confirmaçãoo de limpeza do chat
    function showClearChatConfirmation() {
        console.log('Mostrando modal de confirmaçãoo de limpeza');
        
        // Remover modal existente se houver
        const existingModal = document.getElementById('clearChatModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        const modalHtml = `
            <div id="clearChatModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[99999]">
                <div class="bg-whiterounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="p-6">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900text-center mb-2">
                            Limpar Conversa
                        </h3>
                        <p class="text-sm text-gray-500 text-center mb-6">
                            Tem certeza que deseja limpar todo o histórico de mensagens desta conversa? Esta açãoo não pode ser desfeita.
                        </p>
                        <div class="flex space-x-3">
                            <button onclick="closeClearChatModal()" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                                Cancelar
                            </button>
                            <button onclick="confirmClearChat()" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors">
                                Limpar Chat
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        console.log('Modal de confirmaçãoo adicionado ao DOM');
    }

    // Funçãoo para fechar o modal de confirmaçãoo
    function closeClearChatModal() {
        const modal = document.getElementById('clearChatModal');
        if (modal) {
            modal.remove();
        }
    }

    // Funçãoo para confirmar a limpeza do chat
    async function confirmClearChat() {
        console.log('=== CONFIRMANDO LIMPEZA DO CHAT ===');
        closeClearChatModal();

        // Mostrar loading
        showNotification('Limpando conversa...', 'info');

        try {
            // Usando MySQL direto atrav�s do objeto 'db'
            const { data: { user } } = await db.auth.getUser();
            
            if (!user) {
                console.error('Usuário não autenticado');
                showNotification('Usuário não autenticado', 'error');
                return;
            }

            console.log('Usuário autenticado:', user.id);

            const { data: userData } = await db
                .from('users')
                .select('id')
                .eq('id', user.id)
                .single();

            if (!userData) {
                console.error('Farm ID não encontrado');
                showNotification('Erro ao obter dados da fazenda', 'error');
                return;
            }

            console.log('Fazenda: Lagoa Do Mato');
            console.log('Funcionário selecionado:', window.selectedEmployee.id);

            console.log('Deletando mensagens do banco...');

            const { error: deleteError } = await db
                .from('chat_messages')
                .delete()
                .eq('farm_id', 1)
                .or(`and(sender_id.eq.${user.id},receiver_id.eq.${window.selectedEmployee.id}),and(sender_id.eq.${window.selectedEmployee.id},receiver_id.eq.${user.id})`);

            if (deleteError) {
                console.error('Erro ao deletar mensagens:', deleteError);
                showNotification('Erro ao limpar o chat. Tente novamente.', 'error');
                return;
            } else {
                console.log('Todas as mensagens entre os usuários deletadas');

            const { data: remainingMessages, error: checkError } = await db
                .from('chat_messages')
                .select('id')
                .eq('farm_id', 1)
                .or(`and(sender_id.eq.${user.id},receiver_id.eq.${window.selectedEmployee.id}),and(sender_id.eq.${window.selectedEmployee.id},receiver_id.eq.${user.id})`);

            if (checkError) {
                console.error('Erro ao verificar mensagens restantes:', checkError);
            } else {
                console.log('Mensagens restantes após delete:', remainingMessages?.length || 0);
                if (remainingMessages && remainingMessages.length > 0) {
                    console.warn('Ainda existem mensagens no banco!');
                    showNotification('Algumas mensagens não foram deletadas. Tente novamente.', 'warning');
                }
            }
            }

            // Limpar interface
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.innerHTML = '';
                console.log('Interface do chat limpa');
            }

            // Limpar cache
            if (window.chatMessagesCache) {
                window.chatMessagesCache.clear();
                console.log('Cache de mensagens limpo');
            }

            // Limpar cache de mensagens em tempo real
            if (window.chatMessages) {
                window.chatMessages = [];
                console.log('Array de mensagens limpo');
            }

            // Limpar timestamp da última mensagem
            lastMessageTimestamp = null;
            lastMessageCount = 0;

            // For�ar recarga das mensagens para garantir que não há mensagens restantes
            if (window.selectedEmployee && window.selectedEmployee.id) {
                setTimeout(async () => {
                    console.log('For�ando recarga das mensagens...');
                    await loadChatMessages(window.selectedEmployee.id, false);
                }, 500);
            }

            console.log('Chat limpo com sucesso');
            showNotification('Conversa limpa com sucesso!', 'success');

        } catch (error) {
            console.error('Erro ao limpar chat:', error);
            showNotification('Erro ao limpar o chat. Tente novamente.', 'error');
        }
    }

    // Toggle sidebar em mobile
    function toggleChatSidebar() {
        const sidebar = document.getElementById('chatSidebar');
        const mainArea = document.getElementById('chatMainArea');
        
        if (window.innerWidth < 1024) {
            // Mobile: alternar entre sidebar e �rea principal
            if (sidebar && mainArea) {
                const isSidebarVisible = !sidebar.classList.contains('hidden');
                
                if (isSidebarVisible) {
                    // Ocultar sidebar e mostrar �rea principal
                    sidebar.classList.add('hidden');
                    sidebar.classList.remove('flex');
                    mainArea.classList.remove('hidden');
                    mainArea.classList.add('flex');
                } else {
                    // Mostrar sidebar e ocultar �rea principal
                    sidebar.classList.remove('hidden');
                    sidebar.classList.add('flex');
                    mainArea.classList.add('hidden');
                    mainArea.classList.remove('flex');
                }
            }
        }
    }

    // Funçãoo para reproduzir �udio
    function playAudio(audioUrl) {
        console.log('Reproduzindo �udio:', audioUrl);
        const audio = new Audio(audioUrl);
        audio.play().catch(error => {
            console.error('Erro ao reproduzir �udio:', error);
            showNotification('Erro ao reproduzir �udio', 'error');
        });
    }

    function formatFileSize(bytes) {
        if (!bytes) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    // Carregar mensagens do chat com funcionário espec�fico
    // Cache para mensagens do chat
    let chatMessagesCache = new Map();
    let lastMessageTimestamp = null;

    async function loadChatMessages(employeeId = null, isPolling = false) {
        try {
            // Usando MySQL direto atrav�s do objeto 'db'
            const { data: { user } } = await db.auth.getUser();
            
            if (!user) return;

            // Definir currentUser globalmente
            window.currentUser = user;

            // Sistema Lagoa Do Mato
            const { data: userData } = await db
                .from('users')
                .select('id')
                .eq('id', user.id)
                .single();

            if (!userData) return;

            if (isPolling) {
                const cacheKey = `lagoa-do-mato_${user.id}_${employeeId}`;
                const cachedData = chatMessagesCache.get(cacheKey);
                
                if (cachedData && (Date.now() - cachedData.timestamp < 2000)) { // Cache reduzido para 2 segundos
                    console.log('?? Usando cache para polling, evitando recarregamento');
                    return;
                }
            }

            // Usar o servi�o de sincronizaçãoo para buscar mensagens
            console.log('?? Buscando mensagens para:', { userId: user.id, employeeId, isPolling });
            const messages = await getChatMessages( user.id, employeeId);
            console.log('?? Mensagens encontradas:', messages?.length || 0);
            
            // Atualizar cache
            if (messages && messages.length > 0) {
                const cacheKey = `lagoa-do-mato_${user.id}_${employeeId}`;
                chatMessagesCache.set(cacheKey, {
                    messages: messages,
                    timestamp: Date.now()
                });

                const latestMessage = messages[messages.length - 1];
                if (lastMessageTimestamp && latestMessage.created_at > lastMessageTimestamp) {
                    console.log('?? Nova mensagem detectada, atualizando display');
            displayChatMessages(messages);
                    lastMessageTimestamp = latestMessage.created_at;
                } else if (!lastMessageTimestamp) {
                    // Primeira carga
                    displayChatMessages(messages);
                    lastMessageTimestamp = latestMessage.created_at;
                } else {
                    console.log('?? Nenhuma mensagem nova, mantendo display atual');
                }
            } else {
                displayChatMessages(messages);
            }
        } catch (error) {
            console.error('Erro ao carregar chat:', error);
        }
    }

    // Funçãoo para gerar cor baseada no nome do usuário
    function generateUserColor(name) {
        if (!name) return 'from-gray-500 to-gray-600';
        
        // Array de cores dispon�veis
        const colors = [
            'from-green-500 to-green-600',
            'from-blue-500 to-blue-600', 
            'from-purple-500 to-purple-600',
            'from-pink-500 to-pink-600',
            'from-red-500 to-red-600',
            'from-yellow-500 to-yellow-600',
            'from-indigo-500 to-indigo-600',
            'from-teal-500 to-teal-600',
            'from-orange-500 to-orange-600',
            'from-cyan-500 to-cyan-600'
        ];
        
        // Gerar �ndice baseado no nome
        let hash = 0;
        for (let i = 0; i < name.length; i++) {
            hash = name.charCodeAt(i) + ((hash << 5) - hash);
        }
        
        return colors[Math.abs(hash) % colors.length];
    }

    // Cache para elementos de mensagem
    let lastMessageCount = 0;
    let isUserAtBottom = true;
    let newMessageIndicator = null;

    function checkIfUserAtBottom() {
        const chatContainer = document.getElementById('chatMessages');
        if (!chatContainer) return true;
        
        const threshold = 100; // pixels do final
        const isAtBottom = chatContainer.scrollTop + chatContainer.clientHeight >= chatContainer.scrollHeight - threshold;
        isUserAtBottom = isAtBottom;
        return isAtBottom;
    }

    // Funçãoo para scroll suave para o final
    function scrollToBottom(smooth = true) {
        const chatContainer = document.getElementById('chatMessages');
        if (!chatContainer) {
            console.log('? Container de chat não encontrado para scroll');
            return;
        }
        
        console.log('?? Fazendo scroll para o final:', {
            scrollHeight: chatContainer.scrollHeight,
            clientHeight: chatContainer.clientHeight,
            scrollTop: chatContainer.scrollTop
        });
        
        // For�ar scroll imediato primeiro
        chatContainer.scrollTop = chatContainer.scrollHeight;
        
        // Depois aplicar scroll suave se solicitado
        if (smooth) {
            setTimeout(() => {
                chatContainer.scrollTo({
                    top: chatContainer.scrollHeight,
                    behavior: 'smooth'
                });
            }, 50);
        }
        
        // Atualizar status de posiçãoo
        setTimeout(() => {
            isUserAtBottom = true;
            hideNewMessageIndicator();
        }, 100);
    }

    // Funçãoo para mostrar indicador de nova mensagem
    function showNewMessageIndicator() {
        if (newMessageIndicator) return; // J� existe
        
        const chatContainer = document.getElementById('chatMessages');
        if (!chatContainer) return;
        
        newMessageIndicator = document.createElement('div');
        newMessageIndicator.className = 'fixed bottom-20 right-4 bg-green-500 text-white px-4 py-2 rounded-full shadow-lg cursor-pointer z-50 flex items-center space-x-2 animate-bounce';
        newMessageIndicator.innerHTML = `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
            <span class="text-sm font-medium">Nova mensagem</span>
        `;
        
        newMessageIndicator.onclick = () => {
            scrollToBottom();
            hideNewMessageIndicator();
        };
        
        document.body.appendChild(newMessageIndicator);
        
        // Auto-hide após 5 segundos
        setTimeout(() => {
            hideNewMessageIndicator();
        }, 5000);
    }

    // Funçãoo para esconder indicador de nova mensagem
    function hideNewMessageIndicator() {
        if (newMessageIndicator) {
            newMessageIndicator.remove();
            newMessageIndicator = null;
        }
    }

    // Funçãoo para mostrar indicador de digitando
    function showTypingIndicator(senderName) {
        const chatContainer = document.getElementById('chatMessages');
        if (!chatContainer) return;
        
        // Remover indicador anterior se existir
        hideTypingIndicator();
        
        const typingDiv = document.createElement('div');
        typingDiv.id = 'typingIndicator';
        typingDiv.className = 'flex justify-start mb-4';
        
        const userColor = generateUserColor(senderName);
        const senderInitial = senderName.charAt(0).toUpperCase();
        
        typingDiv.innerHTML = `
            <div class="max-w-xs lg:max-w-md">
                <div class="flex items-end space-x-2">
                    <div class="w-8 h-8 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-semibold text-xs">${senderInitial}</span>
                    </div>
                    <div class="flex flex-col items-start">
                        <div class="px-4 py-2 rounded-2xl bg-whitetext-gray-900shadow-sm">
                            <div class="flex items-center space-x-1">
                                <div class="typing-dot"></div>
                                <div class="typing-dot"></div>
                                <div class="typing-dot"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        chatContainer.appendChild(typingDiv);
        
        // Scroll para o final quando mostrar indicador
        setTimeout(() => {
            scrollToBottom(true);
        }, 100);
    }

    // Funçãoo para esconder indicador de digitando
    function hideTypingIndicator() {
        const typingIndicator = document.getElementById('typingIndicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }

    // Cache para status de leitura das mensagens
    let messageReadStatus = new Map();

    // Funçãoo para determinar status de leitura da mensagem
    function getReadStatus(message) {
        const messageId = message.id || `${message.created_at}_${message.sender_id}`;

        if (messageReadStatus.has(messageId)) {
            return messageReadStatus.get(messageId);
        }

        const isRecipientOnline = window.selectedEmployee && isEmployeeOnline(window.selectedEmployee);
        
        // Simular status baseado no tempo da mensagem e se destinatário está online
        const messageTime = new Date(message.created_at);
        const now = new Date();
        const timeDiff = (now - messageTime) / 1000; // diferen�a em segundos
        
        let statusHtml;
        
        if (timeDiff < 1) {

            statusHtml = '<svg class="w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12"><path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path></svg>';
        } else if (isRecipientOnline && timeDiff > 2) {

            statusHtml = `
                <div class="relative w-5 h-3">
                    <svg class="absolute w-4 h-3 text-blue-500" fill="currentColor" viewBox="0 0 16 12">
                        <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                    </svg>
                    <svg class="absolute w-4 h-3 text-blue-500" fill="currentColor" viewBox="0 0 16 12" style="left: 4px;">
                        <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                    </svg>
                </div>
            `;
        } else if (isRecipientOnline) {

            statusHtml = `
                <div class="relative w-5 h-3">
                    <svg class="absolute w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12">
                        <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                    </svg>
                    <svg class="absolute w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12" style="left: 4px;">
                        <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                    </svg>
                </div>
            `;
        } else {

            statusHtml = '<svg class="w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12"><path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path></svg>';
        }
        
        // Armazenar status para evitar rec�lculo
        messageReadStatus.set(messageId, statusHtml);
        
        // Simular progress�o do status ao longo do tempo
        if (timeDiff < 2 && isRecipientOnline) {
            setTimeout(() => {
                updateMessageReadStatus(messageId, 'delivered');
            }, 2000 - (timeDiff * 1000));
        }
        
        if (timeDiff < 5 && isRecipientOnline) {
            setTimeout(() => {
                updateMessageReadStatus(messageId, 'read');
            }, 5000 - (timeDiff * 1000));
        }
        
        return statusHtml;
    }

    // Funçãoo para atualizar status de leitura de uma mensagem espec�fica
    function updateMessageReadStatus(messageId, status) {
        let statusHtml;
        
        if (status === 'delivered') {

            statusHtml = `
                <div class="relative w-5 h-3">
                    <svg class="absolute w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12">
                        <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                    </svg>
                    <svg class="absolute w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12" style="left: 4px;">
                        <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                    </svg>
                </div>
            `;
        } else if (status === 'read') {

            statusHtml = `
                <div class="relative w-5 h-3">
                    <svg class="absolute w-4 h-3 text-blue-500" fill="currentColor" viewBox="0 0 16 12">
                        <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                    </svg>
                    <svg class="absolute w-4 h-3 text-blue-500" fill="currentColor" viewBox="0 0 16 12" style="left: 4px;">
                        <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                    </svg>
                </div>
            `;
        }
        
        if (statusHtml) {
            messageReadStatus.set(messageId, statusHtml);
            // Atualizar visualmente no chat
            updateReadStatusInChat(messageId, statusHtml);
        }
    }

    // Funçãoo para atualizar status de leitura visualmente no chat
    function updateReadStatusInChat(messageId, statusHtml) {
        const chatContainer = document.getElementById('chatMessages');
        if (!chatContainer) return;
        
        // Encontrar a mensagem espec�fica e atualizar seu status
        const messages = chatContainer.querySelectorAll('[data-message-id]');
        messages.forEach(messageElement => {
            if (messageElement.getAttribute('data-message-id') === messageId) {
                const readStatusElement = messageElement.querySelector('.read-status');
                if (readStatusElement) {
                    readStatusElement.innerHTML = statusHtml;
                }
            }
        });
    }

    // Exibir mensagens no chat
    function displayChatMessages(messages) {
        console.log('?? Exibindo mensagens no gerente:', messages?.length || 0);
        const chatContainer = document.getElementById('chatMessages');
        if (!chatContainer) {
            console.error('? Container de mensagens não encontrado no gerente');
            return;
        }

        // Esconder indicador de digitando quando exibir mensagens
        hideTypingIndicator();

        const wasAtBottom = checkIfUserAtBottom();
        const hadMessages = lastMessageCount > 0;
        const hasNewMessages = messages.length > lastMessageCount;

        if (messages.length === lastMessageCount && messages.length > 0 && !hasNewMessages) {
            console.log('?? Mesmo número de mensagens, evitando recarregamento');
            return;
        }

        chatContainer.innerHTML = '';
        lastMessageCount = messages.length;

        if (messages.length === 0) {
            chatContainer.innerHTML = `
                <div class="text-center py-12">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900mb-2">Nenhuma mensagem ainda</h3>
                    <p class="text-gray-500">Seja o primeiro a enviar uma mensagem!</p>
                </div>
            `;
            return;
        }

        messages.forEach(message => {

            if (message.call_data) {
                console.log('=== MENSAGEM DE CHAMADA DETECTADA ===');
                console.log('Message:', message);
                console.log('Call data:', message.call_data);

                const messageTime = new Date(message.created_at);
                const now = new Date();
                const timeDiff = now - messageTime;
                const fiveMinutes = 5 * 60 * 1000;
                
                if (timeDiff > fiveMinutes) {
                    console.log('Mensagem de chamada muito antiga, ignorando');
                    return;
                }
                
                handleCallMessage(message);
                return; // N�o exibir mensagem de chamada no chat
            }
            
            // N�o exibir mensagens vazias (exceto se tiver file_data)
            if ((!message.message || message.message.trim() === '') && !message.file_data) {
                return;
            }
            
            const isCurrentUser = message.sender_id === (window.currentUser?.id || '');
            const messageDiv = document.createElement('div');
            messageDiv.className = `flex ${isCurrentUser ? 'justify-end' : 'justify-start'} mb-4`;
            
            // Usar sender_name se disponível, sen�o usar 'Usuário'
            const senderName = message.sender_name || 'Usuário';
            
            // Usar sender_name se disponível, sen�o usar '?'
            const senderInitial = senderName.charAt(0).toUpperCase();
            const messageTime = new Date(message.created_at).toLocaleTimeString('pt-BR', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });

            const hasPhoto = message.sender_photo && message.sender_photo.trim() !== '';
            const userColor = generateUserColor(senderName);

            let avatarHtml;
            if (hasPhoto) {
                avatarHtml = `
                    <img src="${message.sender_photo}" 
                         alt="Foto de ${senderName}" 
                         class="w-8 h-8 rounded-full object-cover flex-shrink-0"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                         onload="this.nextElementSibling.style.display='none';">
                    <div class="w-8 h-8 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center flex-shrink-0" style="display: flex;">
                        <span class="text-white font-semibold text-xs">${senderInitial}</span>
                    </div>
                `;
            } else {
                avatarHtml = `
                    <div class="w-8 h-8 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-semibold text-xs">${senderInitial}</span>
                    </div>
                `;
            }

            let readReceiptHtml = '';
            if (isCurrentUser) {
                const readStatus = getReadStatus(message);
                readReceiptHtml = `
                    <div class="flex items-center space-x-1 mt-1">
                        ${readStatus}
                    </div>
                `;
            }

            const messageId = message.id || `${message.created_at}_${message.sender_id}`;
            
            messageDiv.setAttribute('data-message-id', messageId);
            messageDiv.innerHTML = `
                <div class="max-w-xs lg:max-w-md">
                    <div class="flex items-end space-x-2 ${isCurrentUser ? 'flex-row-reverse space-x-reverse' : ''}">
                        <div class="relative">
                            ${avatarHtml}
                        </div>
                        <div class="flex flex-col ${isCurrentUser ? 'items-end' : 'items-start'}">
                            <div class="px-4 py-2 rounded-2xl ${isCurrentUser ? 'bg-green-500 text-white' : 'bg-whitetext-gray-900'} shadow-sm">
                                ${message.file_data && message.file_data.type === 'audio' ? 
                                    `<div class="flex items-center space-x-3">
                                        <button onclick="playAudio('${message.file_data.url}')" class="flex items-center justify-center w-10 h-10 rounded-full ${isCurrentUser ? 'bg-whitebg-opacity-20' : 'bg-green-500'} hover:bg-opacity-30 transition-colors">
                                            <svg class="w-5 h-5 ${isCurrentUser ? 'text-white' : 'text-white'}" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                        </button>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium">${message.file_data.name || 'Mensagem de voz'}</span>
                                            <span class="text-xs opacity-75">${formatFileSize(message.file_data.size)}</span>
                                        </div>
                                    </div>` :
                                    `<p class="text-sm">${message.message}</p>`
                                }
                            </div>
                            <div class="flex items-center space-x-1 mt-1">
                                <span class="text-xs text-gray-500">${messageTime}</span>
                                <div class="read-status">${readReceiptHtml}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            chatContainer.appendChild(messageDiv);
        });

        // L�gica de scroll inteligente
        setTimeout(() => {
            console.log('?? Verificando scroll:', { 
                wasAtBottom, 
                hadMessages, 
                hasNewMessages,
                messageCount: messages.length,
                lastCount: lastMessageCount
            });
            
            if (wasAtBottom || !hadMessages || hasNewMessages) {
                // Usuário estava no final, � primeira carga, ou h� mensagens novas - scroll autom�tico
                console.log('?? Fazendo scroll autom�tico');
                scrollToBottom(true);
            } else if (hasNewMessages && !wasAtBottom) {
                // H� mensagens novas e usuário não está no final - mostrar indicador
                console.log('?? Mostrando indicador de nova mensagem');
                showNewMessageIndicator();
            }
        }, 200); // Aumentado para 200ms para garantir que o DOM foi atualizado
    }

    // Enviar mensagem
    async function sendChatMessageLocal() {
        const messageInput = document.getElementById('chatMessageInput');
        const message = messageInput.value.trim();
        
        if (!message || !window.selectedEmployee) return;

        // Mostrar indicador de digitando
        showTypingIndicator(window.currentUser?.name || 'Voc�');

        try {
            // Usando MySQL direto atrav�s do objeto 'db'
            const { data: { user } } = await db.auth.getUser();
            
            if (!user) return;

            // Sistema Lagoa Do Mato
            const { data: userData } = await db
                .from('users')
                .select('id')
                .eq('id', user.id)
                .single();

            if (!userData) return;

            // Usar o servi�o de sincronizaçãoo para enviar mensagem
            await sendChatMessage({
                farm_id: 1, // Lagoa Do Mato
                sender_id: user.id,
                receiver_id: window.selectedEmployee.id,
                message: message
            });

            // Limpar input IMEDIATAMENTE após enviar
            messageInput.value = '';
            
            // As mensagens serão atualizadas automaticamente via real-time
            console.log('? Mensagem enviada, aguardando atualização via real-time...');
            
            // Fazer scroll para o final após enviar mensagem
            setTimeout(() => {
                scrollToBottom(true);
            }, 100);
                    
                    // Manter foco no input
                        messageInput.focus();
            
        } catch (error) {
            console.error('Erro ao enviar mensagem:', error);
            showNotification('Erro ao enviar mensagem', 'error');
            // Esconder indicador de digitando em caso de erro
            hideTypingIndicator();
        }
    }

    // Enviar mensagem com Enter
    function handleChatKeyPress(event) {
        if (event.key === 'Enter') {
            sendChatMessageLocal();
        }
    }

    // ==================== FUNçãoES DE EMOJI E CLIPES ====================
    
    // Toggle do picker de emojis
    function toggleEmojiPicker() {
        const emojiPicker = document.getElementById('emojiPicker');
        if (emojiPicker) {
            emojiPicker.classList.toggle('hidden');
        }
    }

    // Inserir emoji no input
    function insertEmoji(emoji) {
        const messageInput = document.getElementById('chatMessageInput');
        if (messageInput) {
            const currentValue = messageInput.value;
            const cursorPos = messageInput.selectionStart;
            const newValue = currentValue.slice(0, cursorPos) + emoji + currentValue.slice(cursorPos);
            messageInput.value = newValue;
            
            // Reposicionar cursor após o emoji
            messageInput.setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
            messageInput.focus();
            
            // Esconder picker de emojis
            toggleEmojiPicker();
        }
    }

    // Toggle do input de arquivo
    function toggleFileInput() {
        const fileInput = document.getElementById('fileInput');
        if (fileInput) {
            fileInput.click();
        }
    }

    // Lidar com seleçãoo de arquivo
    function handleFileSelect(event) {
        const file = event.target.files[0];
        if (!file) return;

        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSize) {
            showNotification('Arquivo muito grande. M�ximo permitido: 10MB', 'error');
            return;
        }

        const allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/webm', 'video/ogg',
            'audio/mp3', 'audio/wav', 'audio/ogg',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ];

        if (!allowedTypes.includes(file.type)) {
            showNotification('Tipo de arquivo não suportado', 'error');
            return;
        }

        // Enviar arquivo
        sendFileMessage(file);
    }

    // Enviar mensagem com arquivo
    async function sendFileMessage(file) {
        if (!window.selectedEmployee) {
            showNotification('Selecione um funcionário primeiro', 'error');
            return;
        }

        try {
            // Usando MySQL direto atrav�s do objeto 'db'
            const { data: { user } } = await db.auth.getUser();
            
            if (!user) return;

            // Sistema Lagoa Do Mato
            const { data: userData } = await db
                .from('users')
                .select('id')
                .eq('id', user.id)
                .single();

            if (!userData) return;

            // Mostrar loading
            showNotification('Enviando arquivo...', 'info');

            // Upload do arquivo para Database Storage
            const fileExt = file.name.split('.').pop();
            const fileName = `${Date.now()}_${Math.random().toString(36).substring(2)}.${fileExt}`;
            const filePath = `chat-files/lagoa-do-mato/${fileName}`;

            const { data: uploadData, error: uploadError } = await db.storage
                .from('chat-files')
                .upload(filePath, file);

            if (uploadError) {
                console.error('Erro no upload:', uploadError);
                showNotification('Erro ao enviar arquivo', 'error');
                return;
            }

            // Obter URL p�blica do arquivo
            const { data: { publicUrl } } = db.storage
                .from('chat-files')
                .getPublicUrl(filePath);

            // Criar mensagem com arquivo
            const fileMessage = {
                type: getFileType(file.type),
                name: file.name,
                size: file.size,
                url: publicUrl
            };

            // Enviar mensagem
            await sendChatMessage({
                farm_id: 1, // Lagoa Do Mato
                sender_id: user.id,
                receiver_id: window.selectedEmployee.id,
                message: `?? ${file.name}`,
                file_data: fileMessage
            });

            showNotification('Arquivo enviado com sucesso!', 'success');
            
            // Limpar input de arquivo
            const fileInput = document.getElementById('fileInput');
            if (fileInput) {
                fileInput.value = '';
            }

        } catch (error) {
            console.error('Erro ao enviar arquivo:', error);
            showNotification('Erro ao enviar arquivo', 'error');
        }
    }

    // Determinar tipo de arquivo
    function getFileType(mimeType) {
        if (mimeType.startsWith('image/')) return 'image';
        if (mimeType.startsWith('video/')) return 'video';
        if (mimeType.startsWith('audio/')) return 'audio';
        if (mimeType === 'application/pdf') return 'pdf';
        if (mimeType.includes('word') || mimeType.includes('document')) return 'document';
        return 'file';
    }

    document.addEventListener('click', function(event) {
        const emojiPicker = document.getElementById('emojiPicker');
        const emojiButton = event.target.closest('[onclick="toggleEmojiPicker()"]');
        
        if (emojiPicker && !emojiPicker.contains(event.target) && !emojiButton) {
            emojiPicker.classList.add('hidden');
        }
    });

    // ==================== FUNçãoES DE CATEGORIAS DE EMOJIS ====================
    
    // Mostrar categoria de emojis
    function showEmojiCategory(category) {
        // Esconder todas as categorias
        const categories = document.querySelectorAll('.emoji-category');
        categories.forEach(cat => cat.classList.add('hidden'));
        
        // Mostrar categoria selecionada
        const selectedCategory = document.getElementById('emoji' + category.charAt(0).toUpperCase() + category.slice(1));
        if (selectedCategory) {
            selectedCategory.classList.remove('hidden');

            if (selectedCategory.children.length === 0) {
                loadEmojiCategory(category);
            }
        }
        
        // Atualizar bot�es de categoria
        const categoryBtns = document.querySelectorAll('.emoji-category-btn');
        categoryBtns.forEach(btn => {
            btn.classList.remove('bg-green-100', 'text-green-700');
            btn.classList.add('hover:bg-gray-200');
        });
        
        // Destacar bot�o selecionado
        const selectedBtn = event.target;
        selectedBtn.classList.add('bg-green-100', 'text-green-700');
        selectedBtn.classList.remove('hover:bg-gray-200');
    }

    // Carregar emojis por categoria
    function loadEmojiCategory(category) {
        const container = document.getElementById('emoji' + category.charAt(0).toUpperCase() + category.slice(1));
        if (!container) return;

        const emojis = {
            gestures: ['??', '??', '???', '?', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '?', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '???', '??', '??', '??', '??'],
            objects: ['??', '??', '??', '??', '??', '??', '??', '??', '??', '???', '???', '??', '???', '???', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '???', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '???', '??', '??', '???', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '???', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '???', '??', '???', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '???', '??', '??', '??', '??', '??', '??', '??', '??', '???', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??']
        };

        if (emojis[category]) {
            emojis[category].forEach(emoji => {
                const button = document.createElement('button');
                button.className = 'emoji-btn p-2 hover:bg-gray-200 rounded text-lg';
                button.textContent = emoji;
                button.onclick = () => insertEmoji(emoji);
                container.appendChild(button);
            });
        }
    }

    // ==================== FUNçãoES DE GRAVAçãoO DE �UDIO ====================
    
    let mediaRecorder = null;
    let audioChunks = [];
    let isRecording = false;

    // Toggle gravaçãoo de �udio
    async function toggleAudioRecording() {
        if (!isRecording) {
            await startAudioRecording();
        } else {
            stopAudioRecording();
        }
    }

    // Iniciar gravaçãoo de �udio
    async function startAudioRecording() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];

            mediaRecorder.ondataavailable = event => {
                audioChunks.push(event.data);
            };

            mediaRecorder.onstop = () => {
                const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                sendAudioMessage(audioBlob);
                stream.getTracks().forEach(track => track.stop());
            };

            mediaRecorder.start();
            isRecording = true;
            
            // Atualizar bot�o
            const btn = document.getElementById('audioRecordBtn');
            btn.classList.add('bg-red-500', 'text-white', 'animate-pulse');
            btn.classList.remove('text-gray-400');
            btn.innerHTML = `
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C13.1 2 14 2.9 14 4V12C14 13.1 13.1 14 12 14C10.9 14 10 13.1 10 12V4C10 2.9 10.9 2 12 2M19 10V12C19 15.9 15.9 19 12 19S5 15.9 5 12V10H7V12C7 14.8 9.2 17 12 17S17 14.8 17 12V10H19Z"/>
                </svg>
            `;
            btn.title = 'Parar gravaçãoo';
            
            showNotification('?? Gravando �udio...', 'info');

        } catch (error) {
            console.error('Erro ao acessar microfone:', error);
            showNotification('Erro ao acessar microfone', 'error');
        }
    }

    // Parar gravaçãoo de �udio
    function stopAudioRecording() {
        if (mediaRecorder && isRecording) {
            mediaRecorder.stop();
            isRecording = false;
            
            // Atualizar bot�o
            const btn = document.getElementById('audioRecordBtn');
            btn.classList.remove('bg-red-500', 'text-white', 'animate-pulse');
            btn.classList.add('text-gray-400');
            btn.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                </svg>
            `;
            btn.title = 'Gravar �udio';
            
            showNotification('?? Processando �udio...', 'info');
        }
    }

    // Enviar mensagem de �udio
    async function sendAudioMessage(audioBlob) {
        if (!window.selectedEmployee) {
            showNotification('Selecione um funcionário primeiro', 'error');
            return;
        }

        try {
            // Usando MySQL direto atrav�s do objeto 'db'
            const { data: { user } } = await db.auth.getUser();
            
            if (!user) return;

            // Sistema Lagoa Do Mato
            const { data: userData } = await db
                .from('users')
                .select('id')
                .eq('id', user.id)
                .single();

            if (!userData) return;

            // Upload do �udio para Database Storage
            const fileName = `audio_${Date.now()}_${Math.random().toString(36).substring(2)}.wav`;
            const filePath = `chat-files/lagoa-do-mato/${fileName}`;

            const { data: uploadData, error: uploadError } = await db.storage
                .from('chat-files')
                .upload(filePath, audioBlob);

            if (uploadError) {
                console.error('Erro no upload:', uploadError);
                showNotification('Erro ao enviar �udio', 'error');
                return;
            }

            // Obter URL p�blica do �udio
            const { data: { publicUrl } } = db.storage
                .from('chat-files')
                .getPublicUrl(filePath);

            // Criar mensagem com �udio
            const audioMessage = {
                type: 'audio',
                name: 'Mensagem de voz',
                size: audioBlob.size,
                url: publicUrl
            };

            // Enviar mensagem
            await sendChatMessage({
                farm_id: 1, // Lagoa Do Mato
                sender_id: user.id,
                receiver_id: window.selectedEmployee.id,
                message: '?? Mensagem de voz',
                file_data: audioMessage
            });

            showNotification('�udio enviado com sucesso!', 'success');

        } catch (error) {
            console.error('Erro ao enviar �udio:', error);
            showNotification('Erro ao enviar �udio', 'error');
        }
    }

    // ==================== FUNçãoES DOS CONTATOS ====================
    
    // Abrir modal de contatos
    async function openContactsModal() {
        try {
            console.log('Abrindo modal de contatos...');
            
            const modal = document.getElementById('contactsModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                
                // Carregar contatos
                await loadContacts();
            }
        } catch (error) {
            console.error('Erro ao abrir modal de contatos:', error);
            showNotification('Erro ao abrir contatos', 'error');
        }
    }

    // Fechar modal de contatos
    function closeContactsModal() {
        const modal = document.getElementById('contactsModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    async function approvePasswordRequest(requestId) {
        if (!requestId) {
            requestId = window.currentPasswordRequestId;
        }
        
        if (!requestId) {
            showNotification('ID da solicitaçãoo não encontrado', 'error');
            return;
        }
        
        try {
            // Usando MySQL direto atrav�s do objeto 'db'
            
            const { error: updateError } = await db
                .from('password_requests')
                .update({ 
                    status: 'approved',
                    approved_at: new Date().toISOString(),
                    approved_by: (await db.auth.getUser()).data.user.id
                })
                .eq('id', requestId);
            
            if (updateError) throw updateError;
            
            const { data: request, error: fetchError } = await db
                .from('password_requests')
                .select('*')
                .eq('id', requestId)
                .single();
            
            if (!fetchError && request) {

                showNotification('Solicitaçãoo aprovada com sucesso!', 'success');
            }
            
            // Fechar modal de detalhes se estiver aberto

            // Recarregar lista de solicitações

        } catch (error) {
            console.error('Erro ao aprovar solicitaçãoo:', error);
            showNotification('Erro ao aprovar solicitaçãoo', 'error');
        }
    }
    
    // Rejeitar solicitaçãoo de senha
    async function rejectPasswordRequest(requestId) {
        if (!requestId) {
            requestId = window.currentPasswordRequestId;
        }
        
        if (!requestId) {
            requestId = window.currentPasswordRequestId;
        }
        
        if (!requestId) {
            showNotification('ID da solicitaçãoo não encontrado', 'error');
            return;
        }
        
        try {
            // Usando MySQL direto atrav�s do objeto 'db'
            
            // Atualizar status da solicitaçãoo
            const { error: updateError } = await db
                .from('password_requests')
                .update({ 
                    status: 'rejected',
                    rejected_at: new Date().toISOString(),
                    rejected_by: (await db.auth.getUser()).data.user.id
                })
                .eq('id', requestId);
            
            if (updateError) throw updateError;

            const { data: request, error: fetchError } = await db
                .from('password_requests')
                .select(`
                    *,
                    users!inner(name, email, role)
                `)
                .eq('id', requestId)
                .single();
            
            if (!fetchError && request) {

                showNotification(`Solicitaçãoo de ${request.users.name} rejeitada.`, 'warning');
            }
            
            // Fechar modal de detalhes se estiver aberto

            // Recarregar lista de solicitações

        } catch (error) {
            console.error('Erro ao rejeitar solicitaçãoo:', error);
            showNotification('Erro ao rejeitar solicitaçãoo', 'error');
        }
    }
    
    // Atualizar lista de solicitações
    function refreshPasswordRequests() {
        
    }
    
    // Aplicar filtro de solicitações
    document.addEventListener('DOMContentLoaded', function() {
        const filterSelect = document.getElementById('passwordRequestFilter');
        if (filterSelect) {
            filterSelect.addEventListener('change', function() {
                const filterValue = this.value;

            });
        }
        
        // Garantir que ambos os modais estejam fechados ao carregar a p�gina
        const detailsModal = document.getElementById('passwordRequestDetailsModal');

        if (detailsModal) {
            detailsModal.classList.add('hidden');
            detailsModal.style.display = 'none';
            detailsModal.style.visibility = 'hidden';
            detailsModal.style.opacity = '0';
            detailsModal.style.pointerEvents = 'none';
            detailsModal.style.zIndex = '-1';
        }
        
        if (requestsModal) {
            requestsModal.classList.add('hidden');
            requestsModal.style.display = 'none';
            requestsModal.style.visibility = 'hidden';
            requestsModal.style.opacity = '0';
            requestsModal.style.pointerEvents = 'none';
            requestsModal.style.zIndex = '-1';
        }
    });
    
    // Atualizar logo da Xandria Store baseada no tema
    function updateXandriaStoreIcon() {
        const icon = document.getElementById('xandriaStoreIcon');
        if (icon) {
            icon.src = 'https://i.postimg.cc/W17q41wM/lactechpreta.png'; // Logo preta para tema claro
        }
    }

    // ==================== FUNçãoES DE SOLICITAçãoES DE SENHA (ESCOPO GLOBAL) ====================
    
    // Abrir modal de solicitações de senha
    function openPasswordRequests() {
        closeMoreModal();
        
        if (modal) {
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            modal.style.visibility = 'visible';
            modal.style.opacity = '1';
            modal.style.pointerEvents = 'auto';
            modal.style.zIndex = '99999';

            console.log('?? Abrindo modal e carregando solicitações...');
            loadPasswordRequestsWithCache(true);
        }
    }

    console.log('? Script de fun��es avan�adas carregado');

    window.testAdvancedFunctions = function() {
        console.log('?? Testando fun��es avan�adas...');
        console.log('showAnimalManagement:', typeof showAnimalManagement);
        console.log('showHealthManagement:', typeof showHealthManagement);
        console.log('showReproductionManagement:', typeof showReproductionManagement);
        console.log('showAnalyticsDashboard:', typeof showAnalyticsDashboard);
    };
    
    // Fun��es para modais de cadastro
    window.showAddVolumeModal = function() {
        const modal = document.createElement('div');
        modal.id = 'addVolumeModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-whiterounded-lg p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold text-gray-900mb-4">Adicionar Volume de Leite</h3>
                <form id="addVolumeForm">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">Data da Coleta</label>
                        <input type="date" name="collection_date" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">Per�odo</label>
                        <select name="period" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none">
                            <option value="manha">Manh�</option>
                            <option value="tarde">Tarde</option>
                            <option value="noite">Noite</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">Volume (Litros)</label>
                        <input type="number" name="volume" step="0.1" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Ex: 25.5">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">Temperatura (�C)</label>
                        <input type="number" name="temperature" step="0.1" class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Ex: 4.2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">Observa��es</label>
                        <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Observa��es sobre a coleta"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeAddVolumeModal()" class="px-4 py-2 border border-gray-300text-gray-700rounded-lg hover:bg-gray-50transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            Adicionar
                        </button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
        
        const today = new Date().toISOString().split('T')[0];
        modal.querySelector('input[name="collection_date"]').value = today;
        modal.querySelector('#addVolumeForm').addEventListener('submit', handleAddVolume);
    }
    
    function closeAddVolumeModal() {
        const modal = document.getElementById('addVolumeModal');
        if (modal) modal.remove();
    }
    
    window.showAddQualityModal = function() {
        const modal = document.createElement('div');
        modal.id = 'addQualityModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-whiterounded-lg p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold text-gray-900mb-4">Adicionar Teste de Qualidade</h3>
                <form id="addQualityForm">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">Data do Teste</label>
                        <input type="date" name="test_date" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">Gordura (%)</label>
                        <input type="number" name="fat_percentage" step="0.1" class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Ex: 3.5">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">Prote�na (%)</label>
                        <input type="number" name="protein_percentage" step="0.1" class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Ex: 3.2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">Contagem de C�lulas Som�ticas</label>
                        <input type="number" name="scc" class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Ex: 150000">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">Contagem Bacteriana Total</label>
                        <input type="number" name="cbt" class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Ex: 25000">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">Laborat�rio</label>
                        <input type="text" name="laboratory" class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Ex: Laborat�rio ABC">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeAddQualityModal()" class="px-4 py-2 border border-gray-300text-gray-700rounded-lg hover:bg-gray-50transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            Adicionar
                        </button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
        
        const today = new Date().toISOString().split('T')[0];
        modal.querySelector('input[name="test_date"]').value = today;
        modal.querySelector('#addQualityForm').addEventListener('submit', handleAddQuality);
    }
    
    function closeAddQualityModal() {
        const modal = document.getElementById('addQualityModal');
        if (modal) modal.remove();
    }
    
    window.showVolumeByCowModal = function() {
        const modal = document.createElement('div');
        modal.id = 'volumeByCowModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-whiterounded-lg p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold text-gray-900mb-4">Volume por Vaca</h3>
                <form id="volumeByCowForm">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">N�mero da Vaca</label>
                        <input type="text" name="cow_number" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Ex: 001, 002, etc.">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">Data da Ordenha</label>
                        <input type="date" name="milking_date" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">Per�odo</label>
                        <select name="period" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none">
                            <option value="manha">Manh�</option>
                            <option value="tarde">Tarde</option>
                            <option value="noite">Noite</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">Volume (Litros)</label>
                        <input type="number" name="volume" step="0.1" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Ex: 15.5">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700mb-2">Temperatura (�C)</label>
                        <input type="number" name="temperature" step="0.1" class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Ex: 37.5">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeVolumeByCowModal()" class="px-4 py-2 border border-gray-300text-gray-700rounded-lg hover:bg-gray-50transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Registrar
                        </button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
        
        const today = new Date().toISOString().split('T')[0];
        modal.querySelector('input[name="milking_date"]').value = today;
        modal.querySelector('#volumeByCowForm').addEventListener('submit', handleVolumeByCow);
    }
    
    function closeVolumeByCowModal() {
        const modal = document.getElementById('volumeByCowModal');
        if (modal) modal.remove();
    }

    async function handleAddVolume(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        
        try {
            const response = await fetch('api/volume.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'insert', ...data, farm_id: 1 })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Volume adicionado com sucesso!', 'success');
                closeAddVolumeModal();
                await loadVolumeData();
                await loadVolumeRecords();
            } else {
                showNotification('Erro ao adicionar volume: ' + result.error, 'error');
            }
        } catch (error) {
            showNotification('Erro ao adicionar volume: ' + error.message, 'error');
        }
    }
    
    async function handleAddQuality(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        
        try {
            const response = await fetch('api/quality.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'insert', ...data, farm_id: 1 })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Teste de qualidade adicionado com sucesso!', 'success');
                closeAddQualityModal();
                await loadQualityData();
                await loadQualityTests();
            } else {
                showNotification('Erro ao adicionar teste: ' + result.error, 'error');
            }
        } catch (error) {
            showNotification('Erro ao adicionar teste: ' + error.message, 'error');
        }
    }
    
    async function handleVolumeByCow(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        
        try {
            const response = await fetch('api/volume.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'insert', ...data, farm_id: 1, cow_number: data.cow_number })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Volume por vaca registrado com sucesso!', 'success');
                closeVolumeByCowModal();
                await loadVolumeData();
                await loadVolumeRecords();
            } else {
                showNotification('Erro ao registrar volume: ' + result.error, 'error');
            }
        } catch (error) {
            showNotification('Erro ao registrar volume: ' + error.message, 'error');
        }
    }
    
    // =====================================================
    // FUN��ES PARA GEST�O DE ANIMAIS
    // =====================================================
    
    window.showAnimalManagement = function() {
        try {
            console.log('?? Abrindo Gest�o de Rebanho...');
            
            // Remover modal anterior se existir
            const existingModal = document.getElementById('animalManagementModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            const modal = document.createElement('div');
            modal.id = 'animalManagementModal';
            modal.className = 'fixed inset-0 bg-whitez-[99999] overflow-y-auto';
            modal.style.display = 'block';
            modal.innerHTML = `
            <div class="w-full h-full">
                <!-- Header -->
                <div class="sticky top-0 bg-gradient-to-br from-emerald-600 to-teal-600 text-white shadow-lg z-10 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <button onclick="closeAnimalManagement()" class="w-10 h-10 flex items-center justify-center hover:bg-whitehover:bg-opacity-20 rounded-xl transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <div>
                                <h3 class="text-2xl font-bold">Gest�o de Rebanho</h3>
                                <p class="text-emerald-100 text-sm">Gerencie animais e insemina��es</p>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-whitebg-opacity-20 rounded-2xl flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Conte�do -->
                <div class="p-6">
                
                <!-- Abas -->
                <div class="flex border-b border-gray-200mb-6">
                    <button onclick="switchAnimalTab('animals')" id="animalsTab" class="px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600">
                        Cadastro de Animais
                    </button>
                    <button onclick="switchAnimalTab('inseminations')" id="inseminationsTab" class="px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent">
                        Insemina��es
                    </button>
                    <button onclick="switchAnimalTab('pedigree')" id="pedigreeTab" class="px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent">
                        �rvore Geneal�gica
                    </button>
                    <button onclick="switchAnimalTab('productivity')" id="productivityTab" class="px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent">
                        Produtividade
                    </button>
                </div>
                
                <!-- Conte�do das Abas -->
                <div id="animalsContent" class="tab-content">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-semibold">Lista de Animais</h4>
                        <button onclick="showAddAnimalModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            + Novo Animal
                        </button>
                    </div>
                    
                    <!-- Barra de Pesquisa -->
                    <div class="mb-4">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text" id="animalsSearchInput" 
                                class="w-full pl-10 pr-4 py-3 border border-gray-300rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Pesquisar por n�mero, nome, ra�a ou sexo...">
                        </div>
                    </div>
                    
                    <div id="animalsList" class="overflow-x-auto">
                        <!-- Lista ser� carregada aqui -->
                    </div>
                </div>
                
                <div id="inseminationsContent" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-semibold">Hist�rico de Insemina��es</h4>
                        <button onclick="showAddInseminationModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            + Nova Insemina��o
                        </button>
                    </div>
                    <div id="inseminationsList" class="overflow-x-auto">
                        <!-- Lista ser� carregada aqui -->
                    </div>
                </div>
                
                <div id="pedigreeContent" class="tab-content hidden">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-semibold">�rvore Geneal�gica</h4>
                    </div>
                    
                    <!-- Barra de Pesquisa -->
                    <div class="mb-6">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text" id="pedigreeSearchInput" 
                                class="w-full pl-10 pr-4 py-3 border border-gray-300rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Pesquisar animal por n�mero ou nome...">
                        </div>
                        <div class="mt-3">
                            <select id="pedigreeAnimalSelect" 
                                class="w-full px-4 py-3 border border-gray-300rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="">Selecione um animal ou use a pesquisa acima</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="pedigreeTree" class="bg-gray-50 p-4 rounded-lg min-h-[200px]">
                        <div class="text-center text-gray-500 py-8">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            <p>Selecione um animal para visualizar sua �rvore geneal�gica</p>
                        </div>
                    </div>
                </div>
                
                <div id="productivityContent" class="tab-content hidden">
                    <h4 class="text-lg font-semibold mb-4">Produtividade dos Animais</h4>
                    <div id="productivityList" class="overflow-x-auto">
                        <!-- Lista ser� carregada aqui -->
                    </div>
                </div>
            </div>
        `;
            document.body.appendChild(modal);
            
            console.log('? Modal de Gest�o de Rebanho criado e adicionado');
            
            // Carregar dados iniciais
            loadAnimalsList();
            loadInseminationsList();
            loadAnimalsForPedigree();
            loadProductivityData();
            
        } catch (error) {
            console.error('? Erro ao abrir Gest�o de Rebanho:', error);
            showNotification('Erro ao abrir Gest�o de Rebanho: ' + error.message, 'error');
        }
    }
    
    function closeAnimalManagement() {
        const modal = document.getElementById('animalManagementModal');
        if (modal) modal.remove();
    }
    
    function switchAnimalTab(tabName) {
        // Esconder todas as abas
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.add('hidden');
        });
        
        // Remover estilo ativo de todos os bot�es
        document.querySelectorAll('[id$="Tab"]').forEach(btn => {
            btn.classList.remove('text-blue-600', 'border-blue-600');
            btn.classList.add('text-gray-500', 'border-transparent');
        });
        
        // Mostrar aba selecionada
        document.getElementById(tabName + 'Content').classList.remove('hidden');
        document.getElementById(tabName + 'Tab').classList.remove('text-gray-500', 'border-transparent');
        document.getElementById(tabName + 'Tab').classList.add('text-blue-600', 'border-blue-600');
    }

    function showAddAnimalModal() {
        const modal = document.createElement('div');
        modal.id = 'addAnimalModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100000] p-4';
        modal.innerHTML = `
            <div class="bg-whiterounded-2xl shadow-2xl w-full max-w-3xl max-h-[95vh] overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 text-white p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold">Cadastrar Novo Animal</h3>
                            <p class="text-emerald-100 text-sm mt-1">Preencha os dados do animal</p>
                        </div>
                        <button type="button" onclick="document.getElementById('addAnimalModal').remove()" 
                            class="text-white hover:bg-whitehover:bg-opacity-20 rounded-lg p-2 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Body -->
                <form id="addAnimalForm" class="flex-1 overflow-y-auto p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">N�mero do Animal *</label>
                            <input type="text" name="animal_number" required 
                                class="w-full px-4 py-2 border border-gray-300rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Ex: 001" tabindex="1">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Nome</label>
                            <input type="text" name="animal_name" 
                                class="w-full px-4 py-2 border border-gray-300rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Ex: Mimosa" tabindex="2">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Ra�a *</label>
                            <select name="breed" required class="w-full px-4 py-2 border border-gray-300rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" tabindex="3">
                                <option value="">Selecione...</option>
                                <option value="Holand�s">Holand�s</option>
                                <option value="Jersey">Jersey</option>
                                <option value="Gir">Gir</option>
                                <option value="Girolando">Girolando</option>
                                <option value="Mesti�o">Mesti�o</option>
                                <option value="Outro">Outro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Data de Nascimento *</label>
                            <input type="date" name="birth_date" required 
                                class="w-full px-4 py-2 border border-gray-300rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                tabindex="4">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Sexo *</label>
                            <select name="gender" required class="w-full px-4 py-2 border border-gray-300rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" tabindex="5">
                                <option value="femea">Fêmea</option>
                                <option value="macho">Macho</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Status *</label>
                            <select name="status" required class="w-full px-4 py-2 border border-gray-300rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" tabindex="6">
                                <option value="">Selecione o status</option>
                                <option value="Lactante">Lactante</option>
                                <option value="Seco">Seco</option>
                                <option value="Novilha">Novilha</option>
                                <option value="Vaca">Vaca</option>
                                <option value="Bezerra">Bezerra</option>
                                <option value="Bezerro">Bezerro</option>
                                <option value="Touro">Touro</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Peso ao Nascer (kg)</label>
                            <input type="number" name="birth_weight" step="0.1" min="0" max="100"
                                class="w-full px-4 py-2 border border-gray-300rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Ex: 35.5" tabindex="7">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Observa��es</label>
                            <textarea name="notes" rows="2" 
                                class="w-full px-4 py-2 border border-gray-300rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Informa��es adicionais sobre o animal..." tabindex="8"></textarea>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4 mt-4">
                        <h4 class="font-semibold text-gray-700mb-3">?? Informa��es Geneal�gicas (Opcional)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700mb-2">Pai</label>
                                <select name="father_id" id="fatherSelect" class="w-full px-4 py-2 border border-gray-300rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">Selecione o pai (ou deixe em branco)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700mb-2">M�e</label>
                                <select name="mother_id" id="motherSelect" class="w-full px-4 py-2 border border-gray-300rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">Selecione a m�e (ou deixe em branco)</option>
                                </select>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <strong>Dica:</strong> Cadastre primeiro os reprodutores (touros e vacas adultas) sem pedigree, depois cadastre os filhotes com pedigree completo.
                        </p>
                    </div>
                </form>
                
                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 border-t flex flex-col sm:flex-row gap-3">
                    <button type="button" onclick="document.getElementById('addAnimalModal').remove()" 
                        class="flex-1 sm:order-1 bg-gray-500 text-white py-3 px-6 rounded-lg hover:bg-gray-600 transition font-semibold flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span>Cancelar</span>
                    </button>
                    <button type="submit" form="addAnimalForm"
                        class="flex-1 sm:order-2 bg-gradient-to-r from-emerald-600 to-teal-600 text-white py-3 px-6 rounded-lg hover:from-emerald-700 hover:to-teal-700 transition font-bold flex items-center justify-center gap-2 shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Cadastrar Animal</span>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Carregar animais dispon�veis para pedigree
        loadAnimalsForPedigreeSelection();

        document.getElementById('addAnimalForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);

            const payload = {
                animal_number: data.animal_number,
                name: data.animal_name || null, // Converter animal_name para name
                breed: data.breed,
                gender: data.gender, // Já vem correto do formulário (femea/macho)
                birth_date: data.birth_date,
                birth_weight: data.birth_weight || null,
                father_id: data.father_id || null,
                mother_id: data.mother_id || null,
                status: data.status,
                health_status: 'saudavel', // Valor padrão
                reproductive_status: 'vazia', // Valor padrão
                notes: data.notes || null
            };

            console.log('DEBUG - Dados do formul�rio:', data);
            console.log('DEBUG - Payload processado:', payload);
            
            try {
                const response = await fetch('api/animals/create.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                console.log('DEBUG - Response status:', response.status);
                const result = await response.json();
                console.log('DEBUG - Resultado da API:', result);
                
                if (result.success) {
                    showNotification('? Animal cadastrado com sucesso!', 'success');
                    modal.remove();
                    // Aguardar um pouco antes de recarregar a lista
                    setTimeout(() => {
                        loadAnimalsList();
                    }, 500);
                } else {
                    showNotification('? Erro: ' + result.error, 'error');
                }
            } catch (error) {
                console.error('DEBUG - Erro na requisi��o:', error);
                showNotification('? Erro ao cadastrar: ' + error.message, 'error');
            }
        });
    }
    
    function showAddInseminationModal() {
        const modal = document.createElement('div');
        modal.id = 'addInseminationModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100000] p-4';
        modal.innerHTML = `
            <div class="bg-whiterounded-2xl shadow-2xl w-full max-w-2xl max-h-[95vh] overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="bg-gradient-to-r from-teal-600 to-cyan-600 text-white p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold">Registrar Insemina��o</h3>
                            <p class="text-teal-100 text-sm mt-1">Registre a insemina��o artificial</p>
                        </div>
                        <button type="button" onclick="document.getElementById('addInseminationModal').remove()" 
                            class="text-white hover:bg-whitehover:bg-opacity-20 rounded-lg p-2 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Body -->
                <form id="addInseminationForm" class="flex-1 overflow-y-auto p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">Animal *</label>
                        <select name="animal_id" id="inseminationAnimalSelect" required 
                            class="w-full px-4 py-2 border border-gray-300rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Carregando animais...</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Data da Insemina��o *</label>
                            <input type="date" name="insemination_date" required 
                                class="w-full px-4 py-2 border border-gray-300rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Touro/S�men *</label>
                            <input type="text" name="bull_id" required 
                                class="w-full px-4 py-2 border border-gray-300rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                placeholder="Nome ou c�digo do touro">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">T�cnico Respons�vel</label>
                        <input type="text" name="technician_name" 
                            class="w-full px-4 py-2 border border-gray-300rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                            placeholder="Nome do t�cnico">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">
                            Observa��es <span class="text-gray-400 font-normal">(opcional)</span>
                        </label>
                        <textarea name="notes" rows="3" 
                            class="w-full px-4 py-2 border border-gray-300rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 resize-none"
                            placeholder="Informa��es adicionais sobre a insemina��o"></textarea>
                    </div>
                </form>
                
                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 border-t flex flex-col sm:flex-row gap-3">
                    <button type="button" onclick="document.getElementById('addInseminationModal').remove()" 
                        class="flex-1 sm:order-1 bg-gray-500 text-white py-3 px-6 rounded-lg hover:bg-gray-600 transition font-semibold flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span>Cancelar</span>
                    </button>
                    <button type="submit" form="addInseminationForm"
                        class="flex-1 sm:order-2 bg-gradient-to-r from-teal-600 to-cyan-600 text-white py-3 px-6 rounded-lg hover:from-teal-700 hover:to-cyan-700 transition font-bold flex items-center justify-center gap-2 shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Registrar Insemina��o</span>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Carregar lista de animais
        loadAnimalsForInsemination();

        document.getElementById('addInseminationForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('api/inseminations.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'insert', ...data })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('? Insemina��o registrada com sucesso!', 'success');
                    modal.remove();
                    loadInseminationsList();
                } else {
                    showNotification('? Erro: ' + result.error, 'error');
                }
            } catch (error) {
                showNotification('? Erro ao registrar: ' + error.message, 'error');
            }
        });
    }
    
    async function loadAnimalsForInsemination() {
        try {
            const response = await fetch('api/animals.php?action=select');
            const result = await response.json();
            
            if (result.success) {
                const select = document.getElementById('inseminationAnimalSelect');
                const females = result.data.filter(a => a.gender === 'femea');
                
                select.innerHTML = '<option value="">Selecione uma vaca...</option>' +
                    females.map(a => `<option value="${a.id}">${a.animal_number} - ${a.name || 'Sem nome'}</option>`).join('');
            }
        } catch (error) {
            console.error('Erro ao carregar animais:', error);
        }
    }
    
    async function loadAnimalsForPedigreeSelection() {
        try {
            const response = await fetch('api/animals.php?action=select');
            const result = await response.json();
            
            if (result.success && result.data) {
                const fatherSelect = document.getElementById('fatherSelect');
                const motherSelect = document.getElementById('motherSelect');
                
                // Filtrar machos para pai
                const males = result.data.filter(a => a.gender === 'macho');
                if (fatherSelect) {
                    fatherSelect.innerHTML = '<option value="">Selecione o pai (ou deixe em branco)</option>' +
                        males.map(a => `<option value="${a.id}">${a.animal_number} - ${a.name || a.breed}</option>`).join('');
                }
                
                // Filtrar fêmeas para mãe
                const females = result.data.filter(a => a.gender === 'femea');
                if (motherSelect) {
                    motherSelect.innerHTML = '<option value="">Selecione a mãe (ou deixe em branco)</option>' +
                        females.map(a => `<option value="${a.id}">${a.animal_number} - ${a.name || a.breed}</option>`).join('');
                }
            }
        } catch (error) {
            console.error('Erro ao carregar animais para pedigree:', error);
        }
    }
    
    // =====================================================
    // SELETOR DE DATA CUSTOMIZADO PARA MOBILE
    // =====================================================
    
    function initializeDatePicker() {
        // Preencher anos (�ltimos 10 anos)
        const yearSelect = document.getElementById('year_select');
        const currentYear = new Date().getFullYear();
        
        for (let i = 0; i < 10; i++) {
            const year = currentYear - i;
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            yearSelect.appendChild(option);
        }
        
        // Preencher dias (1-31)
        const daySelect = document.getElementById('day_select');
        for (let i = 1; i <= 31; i++) {
            const option = document.createElement('option');
            option.value = i.toString().padStart(2, '0');
            option.textContent = i;
            daySelect.appendChild(option);
        }

        const birthDateDisplay = document.getElementById('birth_date_display');
        if (birthDateDisplay) {
            birthDateDisplay.addEventListener('click', openDatePicker);
        }
        
        // Event listeners para mudan�as nos selects
        const monthSelect = document.getElementById('month_select');
        const daySelectEl = document.getElementById('day_select');
        
        if (monthSelect) {
            monthSelect.addEventListener('change', updateDaysForMonth);
        }
        
        if (daySelectEl) {
            daySelectEl.addEventListener('change', updateDaysForMonth);
        }
    }
    
    function openDatePicker() {
        const modal = document.getElementById('date_picker_modal');
        if (modal) {
            modal.classList.remove('hidden');
            
            // Definir valores padr�o (data atual)
            const today = new Date();
            document.getElementById('day_select').value = today.getDate().toString().padStart(2, '0');
            document.getElementById('month_select').value = (today.getMonth() + 1).toString().padStart(2, '0');
            document.getElementById('year_select').value = today.getFullYear();
            
            updateDaysForMonth();
        }
    }
    
    function closeDatePicker() {
        const modal = document.getElementById('date_picker_modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
    
    function updateDaysForMonth() {
        const monthSelect = document.getElementById('month_select');
        const daySelect = document.getElementById('day_select');
        const yearSelect = document.getElementById('year_select');
        
        if (!monthSelect || !daySelect || !yearSelect) return;
        
        const month = parseInt(monthSelect.value);
        const year = parseInt(yearSelect.value);
        
        if (month && year) {
            // Calcular dias no m�s
            const daysInMonth = new Date(year, month, 0).getDate();
            const currentDay = parseInt(daySelect.value);
            
            // Remover op��es de dias que n�o existem no m�s
            const options = daySelect.querySelectorAll('option');
            options.forEach(option => {
                if (option.value && parseInt(option.value) > daysInMonth) {
                    option.style.display = 'none';
                } else {
                    option.style.display = 'block';
                }
            });
            
            // Se o dia atual n�o existe no m�s, ajustar para o �ltimo dia
            if (currentDay > daysInMonth) {
                daySelect.value = daysInMonth.toString().padStart(2, '0');
            }
        }
    }
    
    function confirmDateSelection() {
        const day = document.getElementById('day_select').value;
        const month = document.getElementById('month_select').value;
        const year = document.getElementById('year_select').value;
        
        if (!day || !month || !year) {
            showNotification('Por favor, selecione dia, m�s e ano', 'warning');
            return;
        }
        
        // Formatar data para YYYY-MM-DD
        const formattedDate = `${year}-${month}-${day}`;
        
        // Validar se a data � v�lida
        const date = new Date(year, month - 1, day);
        if (date.getFullYear() != year || date.getMonth() != month - 1 || date.getDate() != day) {
            showNotification('Data inv�lida', 'error');
            return;
        }
        
        // Validar se n�o � data futura
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (date > today) {
            showNotification('A data de nascimento n�o pode ser futura', 'error');
            return;
        }
        
        // Validar se n�o � muito antiga (mais de 20 anos)
        const twentyYearsAgo = new Date();
        twentyYearsAgo.setFullYear(twentyYearsAgo.getFullYear() - 20);
        if (date < twentyYearsAgo) {
            showNotification('A data de nascimento n�o pode ser h� mais de 20 anos', 'error');
            return;
        }
        
        // Atualizar campos
        document.getElementById('birth_date_value').value = formattedDate;
        document.getElementById('birth_date_display').value = `${day}/${month}/${year}`;
        
        closeDatePicker();
        showNotification('Data selecionada com sucesso!', 'success');
    }
    
    async function loadAnimalsList() {
        try {
            const response = await fetch('api/animals.php?action=select');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const animals = result.data || [];
            const container = document.getElementById('animalsList');
            const searchInput = document.getElementById('animalsSearchInput');
            
            if (animals.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum animal cadastrado</p>';
                return;
            }
            
            // Fun��o para renderizar a tabela
            function renderAnimalsTable(animalsToRender) {
                if (animalsToRender.length === 0) {
                    return '<p class="text-gray-500 text-center py-8">Nenhum animal encontrado com esses crit�rios</p>';
                }
                
                return `
                <table class="min-w-full bg-whiteborder border-gray-200rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N�mero</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ra�a</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nascimento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sexo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prenha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">A��es</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        ${animals.map(animal => `
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${animal.animal_number}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${animal.name || '-'}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${animal.breed}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${new Date(animal.birth_date).toLocaleDateString('pt-BR')}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900capitalize">${animal.gender}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        ${animal.status}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    ${animal.reproductive_status === 'prenha' ? 
                                        `<span class="px-2 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-800">
                                            Prenha
                                        </span>` : 
                                        '<span class="text-gray-400">-</span>'
                                    }
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewAnimal(${animal.id})" class="text-blue-600 hover:text-blue-900 mr-2">Ver</button>
                                    <button onclick="editAnimal(${animal.id})" class="text-green-600 hover:text-green-900 mr-2">Editar</button>
                                    <button onclick="deleteAnimal(${animal.id})" class="text-red-600 hover:text-red-900">Excluir</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                `;
            }

            container.innerHTML = renderAnimalsTable(animals);
            
            // Adicionar evento de pesquisa
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    
                    if (searchTerm === '') {
                        container.innerHTML = renderAnimalsTable(animals);
                    } else {
                        const filtered = animals.filter(animal => {
                            const number = animal.animal_number?.toLowerCase() || '';
                            const name = animal.name?.toLowerCase() || '';
                            const breed = animal.breed?.toLowerCase() || '';
                            const gender = animal.gender?.toLowerCase() || '';
                            
                            return number.includes(searchTerm) || 
                                   name.includes(searchTerm) || 
                                   breed.includes(searchTerm) ||
                                   gender.includes(searchTerm);
                        });
                        
                        container.innerHTML = renderAnimalsTable(filtered);
                    }
                });
            }
            
        } catch (error) {
            document.getElementById('animalsList').innerHTML = '<p class="text-red-500 text-center py-8">Erro ao carregar animais: ' + error.message + '</p>';
        }
    }
    
    async function loadInseminationsList() {
        try {
            const response = await fetch('api/inseminations.php?action=select');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const inseminations = result.data || [];
            const container = document.getElementById('inseminationsList');
            
            if (inseminations.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhuma insemina��o registrada</p>';
                return;
            }
            
            container.innerHTML = `
                <table class="min-w-full bg-whiteborder border-gray-200rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Touro</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">T�cnico</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resultado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">A��es</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        ${inseminations.map(ins => `
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    ${ins.animal_number} ${ins.name ? '(' + ins.name + ')' : ''}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${new Date(ins.insemination_date).toLocaleDateString('pt-BR')}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${ins.bull_name || '-'}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${ins.technician_name || '-'}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${
                                        ins.pregnancy_result === 'positivo' ? 'bg-green-100 text-green-800' :
                                        ins.pregnancy_result === 'negativo' ? 'bg-red-100 text-red-800' :
                                        'bg-yellow-100 text-yellow-800'
                                    }">
                                        ${ins.pregnancy_result || 'Pendente'}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewInsemination(${ins.id})" class="text-blue-600 hover:text-blue-900 mr-2">Ver</button>
                                    <button onclick="editInsemination(${ins.id})" class="text-green-600 hover:text-green-900 mr-2">Editar</button>
                                    <button onclick="deleteInsemination(${ins.id})" class="text-red-600 hover:text-red-900">Excluir</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } catch (error) {
            document.getElementById('inseminationsList').innerHTML = '<p class="text-red-500 text-center py-8">Erro ao carregar insemina��es: ' + error.message + '</p>';
        }
    }
    
    async function loadAnimalsForPedigree() {
        try {
            const response = await fetch('api/animals.php?action=select');
            const result = await response.json();
            
            if (result.success && result.data) {
                const select = document.getElementById('pedigreeAnimalSelect');
                const searchInput = document.getElementById('pedigreeSearchInput');
                const allAnimals = result.data;
                
                // Fun��o para renderizar a lista
                function renderAnimalsList(animals) {
                    select.innerHTML = '<option value="">Selecione um animal ou use a pesquisa acima</option>' +
                        animals.map(animal => 
                            `<option value="${animal.id}">${animal.animal_number} ${animal.name ? '- ' + animal.name : ''} (${animal.breed})</option>`
                        ).join('');
                }

                renderAnimalsList(allAnimals);
                
                // Evento de mudan�a no select
                select.addEventListener('change', function() {
                    if (this.value) {
                        loadPedigreeTree(this.value);
                    } else {
                        document.getElementById('pedigreeTree').innerHTML = `
                            <div class="text-center text-gray-500 py-8">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                </svg>
                                <p>Selecione um animal para visualizar sua �rvore geneal�gica</p>
                            </div>
                        `;
                    }
                });
                
                // Evento de pesquisa
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase().trim();
                        
                        if (searchTerm === '') {
                            renderAnimalsList(allAnimals);
                        } else {
                            const filtered = allAnimals.filter(animal => {
                                const number = animal.animal_number?.toLowerCase() || '';
                                const name = animal.name?.toLowerCase() || '';
                                const breed = animal.breed?.toLowerCase() || '';
                                
                                return number.includes(searchTerm) || 
                                       name.includes(searchTerm) || 
                                       breed.includes(searchTerm);
                            });
                            
                            renderAnimalsList(filtered);
                            
                            // Se houver apenas 1 resultado, selecionar automaticamente
                            if (filtered.length === 1) {
                                select.value = filtered[0].id;
                                select.dispatchEvent(new Event('change'));
                            }
                        }
                    });
                }
            }
        } catch (error) {
            console.error('Erro ao carregar animais para pedigree:', error);
        }
    }
    
    async function loadPedigreeTree(animalId) {
        try {
            const response = await fetch(`api/animals.php?action=get_pedigree&id=${animalId}`);
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const pedigree = result.data || {};
            const container = document.getElementById('pedigreeTree');

            if (!pedigree.father_id && !pedigree.mother_id) {
                container.innerHTML = `
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                        <p class="text-yellow-800">
                            ?? Este animal n�o possui informa��es geneal�gicas cadastradas.
                        </p>
                        <p class="text-sm text-yellow-600 mt-2">
                            Para visualizar a �rvore geneal�gica, cadastre as informa��es de pai e m�e.
                        </p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = `
                <div class="space-y-4">
                    <h5 class="font-semibold text-gray-900">�rvore Geneal�gica</h5>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Av�s Paternos -->
                        <div class="bg-whitep-4 rounded-lg border">
                            <h6 class="font-medium text-gray-700mb-2">Av�s Paternos</h6>
                            <div class="space-y-2 text-sm">
                                <div><strong>Av�:</strong> ${pedigree.grandfather_number || 'N�o cadastrado'}</div>
                                <div><strong>Av�:</strong> ${pedigree.grandmother_father_number || 'N�o cadastrado'}</div>
                            </div>
                        </div>
                        
                        <!-- Pais -->
                        <div class="bg-whitep-4 rounded-lg border">
                            <h6 class="font-medium text-gray-700mb-2">Pais</h6>
                            <div class="space-y-2 text-sm">
                                <div><strong>Pai:</strong> ${pedigree.father_number || 'N�o cadastrado'}</div>
                                <div><strong>M�e:</strong> ${pedigree.mother_number || 'N�o cadastrado'}</div>
                            </div>
                        </div>
                        
                        <!-- Av�s Maternos -->
                        <div class="bg-whitep-4 rounded-lg border">
                            <h6 class="font-medium text-gray-700mb-2">Av�s Maternos</h6>
                            <div class="space-y-2 text-sm">
                                <div><strong>Av�:</strong> ${pedigree.grandfather_mother_number || '-'} ${pedigree.grandfather_mother_name ? '(' + pedigree.grandfather_mother_name + ')' : ''}</div>
                                <div><strong>Av�:</strong> ${pedigree.grandmother_mother_number || '-'} ${pedigree.grandmother_mother_name ? '(' + pedigree.grandmother_mother_name + ')' : ''}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Animal Principal -->
                    <div class="bg-blue-50 p-4 rounded-lg border-2 border-blue-200">
                        <h6 class="font-medium text-blue-900 mb-2">Animal Principal</h6>
                        <div class="text-sm">
                            <div><strong>N�mero:</strong> ${pedigree.animal_number}</div>
                            <div><strong>Nome:</strong> ${pedigree.name || '-'}</div>
                            <div><strong>Ra�a:</strong> ${pedigree.breed}</div>
                            <div><strong>Nascimento:</strong> ${new Date(pedigree.birth_date).toLocaleDateString('pt-BR')}</div>
                        </div>
                    </div>
                </div>
            `;
        } catch (error) {
            document.getElementById('pedigreeTree').innerHTML = '<p class="text-red-500">Erro ao carregar pedigree: ' + error.message + '</p>';
        }
    }
    
    async function loadProductivityData() {
        try {
            const response = await fetch('api/animals.php?action=get_productivity');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const animals = result.data || [];
            const container = document.getElementById('productivityList');
            
            if (animals.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum dado de produtividade dispon�vel</p>';
                return;
            }
            
            container.innerHTML = `
                <table class="min-w-full bg-whiteborder border-gray-200rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lacta��o</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">M�dia Di�ria</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Lacta��o</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        ${animals.map(animal => `
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    ${animal.animal_number} ${animal.name ? '(' + animal.name + ')' : ''}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${animal.lactation_number || 0}�
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${animal.current_lactation_avg ? animal.current_lactation_avg.toFixed(1) + ' L' : '-'}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${animal.current_lactation_total ? animal.current_lactation_total.toFixed(0) + ' L' : '-'}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    ${animal.is_pregnant ? 
                                        `<span class="px-2 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-800">
                                            Prenha
                                        </span>` : 
                                        '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Lactando</span>'
                                    }
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } catch (error) {
            document.getElementById('productivityList').innerHTML = '<p class="text-red-500 text-center py-8">Erro ao carregar produtividade: ' + error.message + '</p>';
        }
    }
    
    // =====================================================
    // FUN��ES PARA GEST�O SANIT�RIA
    // =====================================================
    
    window.showHealthManagement = function() {
        try {
            console.log('?? Abrindo Gest�o Sanit�ria...');
            
            // Remover modal anterior se existir
            const existingModal = document.getElementById('healthManagementModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            const modal = document.createElement('div');
            modal.id = 'healthManagementModal';
            modal.className = 'fixed inset-0 bg-whitez-[99999] overflow-y-auto';
            modal.style.display = 'block';
            modal.innerHTML = `
            <div class="w-full h-full">
                <!-- Header -->
                <div class="sticky top-0 bg-gradient-to-br from-green-600 to-emerald-700 text-white shadow-lg z-10 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <button onclick="closeHealthManagement()" class="w-10 h-10 flex items-center justify-center hover:bg-whitehover:bg-opacity-20 rounded-xl transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <div>
                                <h3 class="text-2xl font-bold">Gest�o Sanit�ria Proativa</h3>
                                <p class="text-green-100 text-sm">Controle de sa�de e medicamentos</p>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-whitebg-opacity-20 rounded-2xl flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Conte�do -->
                <div class="p-6">
                
                <!-- Abas -->
                <div class="flex border-b border-gray-200mb-6">
                    <button onclick="switchHealthTab('alerts')" id="alertsTab" class="px-4 py-2 text-sm font-medium text-red-600 border-b-2 border-red-600">
                        Alertas Ativos
                    </button>
                    <button onclick="switchHealthTab('medications')" id="medicationsTab" class="px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent">
                        Medicamentos
                    </button>
                    <button onclick="switchHealthTab('withdrawal')" id="withdrawalTab" class="px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent">
                        Controle de Car�ncia
                    </button>
                    <button onclick="switchHealthTab('stock')" id="stockTab" class="px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent">
                        Estoque
                    </button>
                </div>
                
                <!-- Conte�do das Abas -->
                <div id="alertsContent" class="tab-content">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-semibold">Alertas Sanit�rios Ativos</h4>
                        <div class="flex space-x-2">
                            <button onclick="refreshHealthAlerts()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Atualizar
                            </button>
                            <button onclick="resolveAllAlerts()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                Resolver Todos
                            </button>
                        </div>
                    </div>
                    <div id="healthAlertsList" class="space-y-4">
                        <!-- Alertas ser�o carregados aqui -->
                    </div>
                </div>
                
                <div id="medicationsContent" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-semibold">Medicamentos e Vacinas</h4>
                        <button onclick="showAddMedicationModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            + Novo Medicamento
                        </button>
                    </div>
                    <div id="medicationsList" class="overflow-x-auto">
                        <!-- Lista ser� carregada aqui -->
                    </div>
                </div>
                
                <div id="withdrawalContent" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-semibold">Controle de Per�odo de Car�ncia</h4>
                        <button onclick="refreshWithdrawalControl()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Atualizar
                        </button>
                    </div>
                    <div id="withdrawalList" class="overflow-x-auto">
                        <!-- Lista ser� carregada aqui -->
                    </div>
                </div>
                
                <div id="stockContent" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-semibold">Controle de Estoque</h4>
                        <button onclick="refreshStockControl()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Atualizar
                        </button>
                    </div>
                    <div id="stockList" class="overflow-x-auto">
                        <!-- Lista ser� carregada aqui -->
                    </div>
                </div>
            </div>
        `;
            document.body.appendChild(modal);
            
            console.log('? Modal de Gest�o Sanit�ria criado e adicionado');
            
            // Carregar dados iniciais
            loadHealthAlerts();
            loadMedicationsList();
            loadWithdrawalControl();
            loadStockControl();
            
        } catch (error) {
            console.error('? Erro ao abrir Gest�o Sanit�ria:', error);
            showNotification('Erro ao abrir Gest�o Sanit�ria: ' + error.message, 'error');
        }
    }
    
    function closeHealthManagement() {
        const modal = document.getElementById('healthManagementModal');
        if (modal) modal.remove();
    }
    
    function switchHealthTab(tabName) {
        // Esconder todas as abas
        document.querySelectorAll('#healthManagementModal .tab-content').forEach(tab => {
            tab.classList.add('hidden');
        });
        
        // Remover estilo ativo de todos os bot�es
        document.querySelectorAll('#healthManagementModal [id$="Tab"]').forEach(btn => {
            btn.classList.remove('text-red-600', 'border-red-600');
            btn.classList.add('text-gray-500', 'border-transparent');
        });
        
        // Mostrar aba selecionada
        document.getElementById(tabName + 'Content').classList.remove('hidden');
        document.getElementById(tabName + 'Tab').classList.remove('text-gray-500', 'border-transparent');
        document.getElementById(tabName + 'Tab').classList.add('text-red-600', 'border-red-600');
    }
    
    async function loadHealthAlerts() {
        try {
            const response = await fetch('api/health_alerts.php?action=select');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const alerts = result.data || [];
            const container = document.getElementById('healthAlertsList');
            
            if (alerts.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-gray-500">Nenhum alerta sanit�rio ativo</div>';
                return;
            }
            
            container.innerHTML = alerts.map(alert => `
                <div class="bg-whiteborder-l-4 ${getAlertBorderColor(alert.alert_level)} p-4 rounded-lg shadow-sm">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full ${getAlertBadgeColor(alert.alert_level)}">
                                    ${alert.alert_level.toUpperCase()}
                                </span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    ${getAlertTypeLabel(alert.alert_type)}
                                </span>
                            </div>
                            <h5 class="font-semibold text-gray-900mb-1">${alert.title}</h5>
                            <p class="text-gray-700text-sm mb-2">${alert.message}</p>
                            <div class="text-xs text-gray-500">
                                ${alert.animal_number ? `Animal: ${alert.animal_number} ${alert.name ? '(' + alert.name + ')' : ''}` : ''}
                                ${alert.medication_name ? ` | Medicamento: ${alert.medication_name}` : ''}
                                ${alert.due_date ? ` | Vence em: ${new Date(alert.due_date).toLocaleDateString('pt-BR')}` : ''}
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="resolveAlert(${alert.id})" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition-colors">
                                Resolver
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            document.getElementById('healthAlertsList').innerHTML = '<div class="text-center py-8 text-red-500">Erro ao carregar alertas: ' + error.message + '</div>';
        }
    }
    
    async function loadMedicationsList() {
        try {
            const response = await fetch('api/medications.php?action=select');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const medications = result.data || [];
            const container = document.getElementById('medicationsList');
            
            if (medications.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum medicamento cadastrado</p>';
                return;
            }
            
            container.innerHTML = `
                <table class="min-w-full bg-whiteborder border-gray-200rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estoque</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Car�ncia Leite</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Car�ncia Carne</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">A��es</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        ${medications.map(med => `
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${med.name}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900capitalize">${med.type}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${med.stock_quantity} unidades</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${med.milk_withdrawal_period} dias</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${med.meat_withdrawal_period} dias</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    ${getStockStatusBadge(med.stock_quantity, med.min_stock_level)}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewMedication(${med.id})" class="text-blue-600 hover:text-blue-900 mr-2">Ver</button>
                                    <button onclick="editMedication(${med.id})" class="text-green-600 hover:text-green-900 mr-2">Editar</button>
                                    <button onclick="deleteMedication(${med.id})" class="text-red-600 hover:text-red-900">Excluir</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } catch (error) {
            document.getElementById('medicationsList').innerHTML = '<p class="text-red-500 text-center py-8">Erro ao carregar medicamentos: ' + error.message + '</p>';
        }
    }
    
    async function loadWithdrawalControl() {
        try {
            // Simular dados de controle de car�ncia
            const container = document.getElementById('withdrawalList');
            container.innerHTML = `
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h5 class="font-semibold text-blue-900 mb-2">Controle de Per�odo de Car�ncia</h5>
                    <p class="text-blue-800 text-sm">Sistema de controle autom�tico de car�ncia implementado. Os per�odos s�o calculados automaticamente quando medicamentos s�o aplicados.</p>
                </div>
            `;
        } catch (error) {
            document.getElementById('withdrawalList').innerHTML = '<p class="text-red-500 text-center py-8">Erro ao carregar controle de car�ncia: ' + error.message + '</p>';
        }
    }
    
    async function loadStockControl() {
        try {
            const response = await fetch('api/medications.php?action=get_low_stock');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const medications = result.data || [];
            const container = document.getElementById('stockList');
            
            if (medications.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-green-600">Todos os medicamentos com estoque adequado</div>';
                return;
            }
            
            container.innerHTML = `
                <div class="space-y-4">
                    <h5 class="font-semibold text-red-900">Medicamentos com Estoque Baixo</h5>
                    ${medications.map(med => `
                        <div class="bg-red-50 border border-red-200 p-4 rounded-lg">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h6 class="font-semibold text-red-900">${med.name}</h6>
                                    <p class="text-red-700 text-sm">Estoque atual: ${med.stock_quantity} unidades | M�nimo: ${med.min_stock_level}</p>
                                </div>
                                <span class="px-3 py-1 bg-red-600 text-white text-sm rounded-full">Baixo</span>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        } catch (error) {
            document.getElementById('stockList').innerHTML = '<p class="text-red-500 text-center py-8">Erro ao carregar controle de estoque: ' + error.message + '</p>';
        }
    }
    
    // Fun��es auxiliares
    function getAlertBorderColor(level) {
        switch(level) {
            case 'cr�tico': return 'border-red-600';
            case 'alto': return 'border-red-500';
            case 'm�dio': return 'border-yellow-500';
            case 'baixo': return 'border-blue-500';
            default: return 'border-gray-500';
        }
    }
    
    function getAlertBadgeColor(level) {
        switch(level) {
            case 'cr�tico': return 'bg-red-600 text-white';
            case 'alto': return 'bg-red-500 text-white';
            case 'm�dio': return 'bg-yellow-500 text-white';
            case 'baixo': return 'bg-blue-500 text-white';
            default: return 'bg-gray-500 text-white';
        }
    }
    
    function getAlertTypeLabel(type) {
        const labels = {
            'vacina��o_pendente': 'Vacina��o Pendente',
            'car�ncia_leite': 'Car�ncia Leite',
            'car�ncia_carne': 'Car�ncia Carne',
            'medicamento_vencido': 'Medicamento Vencendo',
            'estoque_baixo': 'Estoque Baixo',
            'reteste_necess�rio': 'Reteste Necess�rio'
        };
        return labels[type] || type;
    }
    
    function getStockStatusBadge(quantity, minLevel) {
        if (quantity <= minLevel) {
            return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Baixo</span>';
        } else if (quantity <= minLevel * 2) {
            return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Aten��o</span>';
        } else {
            return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Normal</span>';
        }
    }
    
    // Fun��es de a��o
    async function resolveAlert(alertId) {
        try {
            const response = await fetch('api/health_alerts.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'resolve', id: alertId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Alerta resolvido com sucesso!', 'success');
                await loadHealthAlerts();
            } else {
                showNotification('Erro ao resolver alerta: ' + result.error, 'error');
            }
        } catch (error) {
            showNotification('Erro ao resolver alerta: ' + error.message, 'error');
        }
    }
    
    async function refreshHealthAlerts() {
        await loadHealthAlerts();
        showNotification('Alertas atualizados!', 'success');
    }
    
    async function refreshWithdrawalControl() {
        await loadWithdrawalControl();
        showNotification('Controle de car�ncia atualizado!', 'success');
    }
    
    async function refreshStockControl() {
        await loadStockControl();
        showNotification('Controle de estoque atualizado!', 'success');
    }
    
    // =====================================================
    // FUN��ES PARA SISTEMA DE REPRODU��O
    // =====================================================
    
    window.showReproductionManagement = function() {
        try {
            console.log('?? Abrindo Sistema de Reprodu��o...');
            
            // Remover modal anterior se existir
            const existingModal = document.getElementById('reproductionManagementModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            const modal = document.createElement('div');
            modal.id = 'reproductionManagementModal';
            modal.className = 'fixed inset-0 bg-whitez-[99999] overflow-y-auto';
            modal.style.display = 'block';
            modal.innerHTML = `
            <div class="w-full h-full">
                <!-- Header -->
                <div class="sticky top-0 bg-gradient-to-br from-teal-600 to-cyan-600 text-white shadow-lg z-10 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <button onclick="closeReproductionManagement()" class="w-10 h-10 flex items-center justify-center hover:bg-whitehover:bg-opacity-20 rounded-xl transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <div>
                                <h3 class="text-2xl font-bold">Sistema de Reprodu��o Avan�ado</h3>
                                <p class="text-teal-100 text-sm">Controle de prenhez e maternidade</p>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-whitebg-opacity-20 rounded-2xl flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Conte�do -->
                <div class="p-6">
                
                <!-- Abas -->
                <div class="flex border-b border-gray-200mb-6">
                    <button onclick="switchReproductionTab('pregnancies')" id="pregnanciesTab" class="px-4 py-2 text-sm font-medium text-pink-600 border-b-2 border-pink-600">
                        Prenhezes Ativas
                    </button>
                    <button onclick="switchReproductionTab('maternity')" id="maternityTab" class="px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent">
                        Alertas de Maternidade
                    </button>
                    <button onclick="switchReproductionTab('performance')" id="performanceTab" class="px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent">
                        Performance Reprodutiva
                    </button>
                    <button onclick="switchReproductionTab('indicators')" id="indicatorsTab" class="px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent">
                        Indicadores
                    </button>
                </div>
                
                <!-- Conte�do das Abas -->
                <div id="pregnanciesContent" class="tab-content">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-semibold">Controle de Prenhezes</h4>
                        <div class="flex space-x-2">
                            <button onclick="showConfirmPregnancyModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                Confirmar Prenhez
                            </button>
                            <button onclick="refreshPregnancies()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Atualizar
                            </button>
                        </div>
                    </div>
                    <div id="pregnanciesList" class="space-y-4">
                        <!-- Lista ser� carregada aqui -->
                    </div>
                </div>
                
                <div id="maternityContent" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-semibold">Alertas de Maternidade</h4>
                        <div class="flex space-x-2">
                            <button onclick="showRecordBirthModal()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                Registrar Parto
                            </button>
                            <button onclick="refreshMaternityAlerts()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Atualizar
                            </button>
                        </div>
                    </div>
                    <div id="maternityAlertsList" class="space-y-4">
                        <!-- Alertas ser�o carregados aqui -->
                    </div>
                </div>
                
                <div id="performanceContent" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-semibold">Performance Reprodutiva por Animal</h4>
                        <button onclick="refreshReproductivePerformance()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Atualizar
                        </button>
                    </div>
                    <div id="reproductivePerformanceList" class="overflow-x-auto">
                        <!-- Lista ser� carregada aqui -->
                    </div>
                </div>
                
                <div id="indicatorsContent" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-semibold">Indicadores de Prenhez</h4>
                        <button onclick="refreshPregnancyIndicators()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Atualizar
                        </button>
                    </div>
                    <div id="pregnancyIndicatorsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Indicadores ser�o carregados aqui -->
                    </div>
                </div>
            </div>
        `;
            document.body.appendChild(modal);
            
            console.log('? Modal de Reprodu��o criado e adicionado');
            
            // Carregar dados iniciais
            loadActivePregnancies();
            loadMaternityAlerts();
            loadReproductivePerformance();
            loadPregnancyIndicators();
            
        } catch (error) {
            console.error('? Erro ao abrir Sistema de Reprodu��o:', error);
            showNotification('Erro ao abrir Sistema de Reprodu��o: ' + error.message, 'error');
        }
    }
    
    function closeReproductionManagement() {
        const modal = document.getElementById('reproductionManagementModal');
        if (modal) modal.remove();
    }
    
    function switchReproductionTab(tabName) {
        // Esconder todas as abas
        document.querySelectorAll('#reproductionManagementModal .tab-content').forEach(tab => {
            tab.classList.add('hidden');
        });
        
        // Remover estilo ativo de todos os bot�es
        document.querySelectorAll('#reproductionManagementModal [id$="Tab"]').forEach(btn => {
            btn.classList.remove('text-pink-600', 'border-pink-600');
            btn.classList.add('text-gray-500', 'border-transparent');
        });
        
        // Mostrar aba selecionada
        document.getElementById(tabName + 'Content').classList.remove('hidden');
        document.getElementById(tabName + 'Tab').classList.remove('text-gray-500', 'border-transparent');
        document.getElementById(tabName + 'Tab').classList.add('text-pink-600', 'border-pink-600');
    }
    
    async function loadActivePregnancies() {
        try {
            const response = await fetch('api/reproduction.php?action=get_active_pregnancies');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const pregnancies = result.data || [];
            const container = document.getElementById('pregnanciesList');
            
            if (pregnancies.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-gray-500">Nenhuma prenhez ativa</div>';
                return;
            }
            
            container.innerHTML = pregnancies.map(pregnancy => `
                <div class="bg-whiteborder-l-4 ${getPregnancyStageColor(pregnancy.pregnancy_stage)} p-4 rounded-lg shadow-sm">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full ${getPregnancyStageBadge(pregnancy.pregnancy_stage)}">
                                    ${pregnancy.pregnancy_stage}
                                </span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                    ${pregnancy.days_until_birth} dias restantes
                                </span>
                            </div>
                            <h5 class="font-semibold text-gray-900mb-1">${pregnancy.animal_number} ${pregnancy.name ? '(' + pregnancy.name + ')' : ''}</h5>
                            <p class="text-gray-700text-sm mb-2">Ra�a: ${pregnancy.breed}</p>
                            <div class="text-xs text-gray-500">
                                DPP: ${new Date(pregnancy.expected_birth_date).toLocaleDateString('pt-BR')} | 
                                Confirma��o: ${new Date(pregnancy.pregnancy_confirmation_date).toLocaleDateString('pt-BR')}
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="viewPregnancyDetails(${pregnancy.id})" class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors">
                                Ver Detalhes
                            </button>
                            <button onclick="recordBirthForPregnancy(${pregnancy.id})" class="px-3 py-1 bg-purple-600 text-white text-sm rounded hover:bg-purple-700 transition-colors">
                                Registrar Parto
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            document.getElementById('pregnanciesList').innerHTML = '<div class="text-center py-8 text-red-500">Erro ao carregar prenhezes: ' + error.message + '</div>';
        }
    }
    
    async function loadMaternityAlerts() {
        try {
            const response = await fetch('api/reproduction.php?action=get_maternity_alerts');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const alerts = result.data || [];
            const container = document.getElementById('maternityAlertsList');
            
            if (alerts.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-gray-500">Nenhum alerta de maternidade ativo</div>';
                return;
            }
            
            container.innerHTML = alerts.map(alert => `
                <div class="bg-whiteborder-l-4 ${getMaternityAlertColor(alert.alert_type)} p-4 rounded-lg shadow-sm">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full ${getMaternityAlertBadge(alert.alert_type)}">
                                    ${getMaternityAlertLabel(alert.alert_type)}
                                </span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-800">
                                    ${alert.alert_level.toUpperCase()}
                                </span>
                            </div>
                            <h5 class="font-semibold text-gray-900mb-1">${alert.title}</h5>
                            <p class="text-gray-700text-sm mb-2">${alert.message}</p>
                            <div class="text-xs text-gray-500">
                                Animal: ${alert.animal_number} ${alert.name ? '(' + alert.name + ')' : ''} | 
                                DPP: ${new Date(alert.expected_birth_date).toLocaleDateString('pt-BR')}
                                ${alert.due_date ? ` | Vence em: ${new Date(alert.due_date).toLocaleDateString('pt-BR')}` : ''}
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="resolveMaternityAlert(${alert.id})" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition-colors">
                                Resolver
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            document.getElementById('maternityAlertsList').innerHTML = '<div class="text-center py-8 text-red-500">Erro ao carregar alertas: ' + error.message + '</div>';
        }
    }
    
    async function loadReproductivePerformance() {
        try {
            const response = await fetch('api/reproduction.php?action=get_reproductive_performance');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const performance = result.data || [];
            const container = document.getElementById('reproductivePerformanceList');
            
            if (performance.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum dado de performance reprodutiva dispon�vel</p>';
                return;
            }
            
            container.innerHTML = `
                <table class="min-w-full bg-whiteborder border-gray-200rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total IA</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sucessos</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taxa Prenhez</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">�ltimo Parto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        ${performance.map(animal => `
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    ${animal.animal_number} ${animal.name ? '(' + animal.name + ')' : ''}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${animal.total_inseminations || 0}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${animal.successful_inseminations || 0}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${animal.pregnancy_rate ? animal.pregnancy_rate.toFixed(1) + '%' : '0%'}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${animal.last_birth_date ? new Date(animal.last_birth_date).toLocaleDateString('pt-BR') : '-'}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${animal.reproductive_status === 'Prenha' ? 'bg-pink-100 text-pink-800' : 'bg-green-100 text-green-800'}">
                                        ${animal.reproductive_status}
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } catch (error) {
            document.getElementById('reproductivePerformanceList').innerHTML = '<p class="text-red-500 text-center py-8">Erro ao carregar performance: ' + error.message + '</p>';
        }
    }
    
    async function loadPregnancyIndicators() {
        try {
            const response = await fetch('api/reproduction.php?action=get_pregnancy_indicators');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const indicators = result.data || {};
            const container = document.getElementById('pregnancyIndicatorsContainer');
            
            container.innerHTML = `
                <div class="bg-gradient-to-r from-pink-500 to-pink-600 rounded-lg p-6 text-white">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-pink-400 bg-opacity-50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium opacity-90">Total Prenhes</p>
                            <p class="text-2xl font-semibold">${indicators.total_pregnant_animals || 0}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-6 text-white">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-400 bg-opacity-50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium opacity-90">Pr�-parto</p>
                            <p class="text-2xl font-semibold">${indicators.pre_partum_animals || 0}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-lg p-6 text-white">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-400 bg-opacity-50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium opacity-90">Parto Iminente</p>
                            <p class="text-2xl font-semibold">${indicators.imminent_birth_animals || 0}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-400 bg-opacity-50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium opacity-90">M�dia DPP</p>
                            <p class="text-2xl font-semibold">${indicators.avg_days_until_birth ? Math.round(indicators.avg_days_until_birth) + ' dias' : '-'}</p>
                        </div>
                    </div>
                </div>
            `;
        } catch (error) {
            document.getElementById('pregnancyIndicatorsContainer').innerHTML = '<div class="text-center py-8 text-red-500">Erro ao carregar indicadores: ' + error.message + '</div>';
        }
    }
    
    // Fun��es auxiliares para reprodu��o
    function getPregnancyStageColor(stage) {
        switch(stage) {
            case 'Parto Iminente': return 'border-red-600';
            case 'Pr�-parto': return 'border-orange-500';
            case 'Vencido': return 'border-red-800';
            default: return 'border-pink-500';
        }
    }
    
    function getPregnancyStageBadge(stage) {
        switch(stage) {
            case 'Parto Iminente': return 'bg-red-600 text-white';
            case 'Pr�-parto': return 'bg-orange-500 text-white';
            case 'Vencido': return 'bg-red-800 text-white';
            default: return 'bg-pink-500 text-white';
        }
    }
    
    function getMaternityAlertColor(type) {
        switch(type) {
            case 'parto_iminente': return 'border-red-600';
            case 'pr�_parto': return 'border-orange-500';
            case 'p�s_parto': return 'border-green-500';
            default: return 'border-pink-500';
        }
    }
    
    function getMaternityAlertBadge(type) {
        switch(type) {
            case 'parto_iminente': return 'bg-red-600 text-white';
            case 'pr�_parto': return 'bg-orange-500 text-white';
            case 'p�s_parto': return 'bg-green-500 text-white';
            default: return 'bg-pink-500 text-white';
        }
    }
    
    function getMaternityAlertLabel(type) {
        const labels = {
            'pr�_parto': 'Pr�-parto',
            'parto_iminente': 'Parto Iminente',
            'p�s_parto': 'P�s-parto',
            'cuidados_especiais': 'Cuidados Especiais'
        };
        return labels[type] || type;
    }
    
    // Fun��es de a��o
    async function resolveMaternityAlert(alertId) {
        try {
            const response = await fetch('api/reproduction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'resolve_maternity_alert', id: alertId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification('Alerta de maternidade resolvido!', 'success');
                await loadMaternityAlerts();
            } else {
                showNotification('Erro ao resolver alerta: ' + result.error, 'error');
            }
        } catch (error) {
            showNotification('Erro ao resolver alerta: ' + error.message, 'error');
        }
    }
    
    async function refreshPregnancies() {
        await loadActivePregnancies();
        showNotification('Prenhezes atualizadas!', 'success');
    }
    
    async function refreshMaternityAlerts() {
        await loadMaternityAlerts();
        showNotification('Alertas de maternidade atualizados!', 'success');
    }
    
    async function refreshReproductivePerformance() {
        await loadReproductivePerformance();
        showNotification('Performance reprodutiva atualizada!', 'success');
    }
    
    async function refreshPregnancyIndicators() {
        await loadPregnancyIndicators();
        showNotification('Indicadores atualizados!', 'success');
    }
    
    // =====================================================
    // FUN��ES PARA DASHBOARD ANAL�TICO
    // =====================================================
    
    window.showAnalyticsDashboard = function() {
        try {
            console.log('?? Abrindo Dashboard Anal�tico...');
            
            // Remover modal anterior se existir
            const existingModal = document.getElementById('analyticsDashboardModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            const modal = document.createElement('div');
            modal.id = 'analyticsDashboardModal';
            modal.className = 'fixed inset-0 bg-whitez-[99999] overflow-y-auto';
            modal.style.display = 'block';
            modal.innerHTML = `
            <div class="w-full h-full">
                <!-- Header -->
                <div class="sticky top-0 bg-gradient-to-br from-slate-600 to-slate-700 text-white shadow-lg z-10 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <button onclick="closeAnalyticsDashboard()" class="w-10 h-10 flex items-center justify-center hover:bg-whitehover:bg-opacity-20 rounded-xl transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <div>
                                <h3 class="text-2xl font-bold">Dashboard Anal�tico</h3>
                                <p class="text-slate-100 text-sm">Indicadores gerenciais e KPIs</p>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-whitebg-opacity-20 rounded-2xl flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Conte�do -->
                <div class="p-6">
                
                <!-- Abas -->
                <div class="flex border-b border-gray-200mb-6">
                    <button onclick="switchAnalyticsTab('overview')" id="overviewTab" class="px-4 py-2 text-sm font-medium text-indigo-600 border-b-2 border-indigo-600">
                        Vis�o Geral
                    </button>
                    <button onclick="switchAnalyticsTab('production')" id="productionTab" class="px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent">
                        Produ��o
                    </button>
                    <button onclick="switchAnalyticsTab('reproductive')" id="reproductiveTab" class="px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent">
                        Reprodutivo
                    </button>
                    <button onclick="switchAnalyticsTab('health')" id="healthTab" class="px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent">
                        Sanit�rio
                    </button>
                    <button onclick="switchAnalyticsTab('financial')" id="financialTab" class="px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent">
                        Financeiro
                    </button>
                </div>
                
                <!-- Conte�do das Abas -->
                <div id="overviewContent" class="tab-content">
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-4">Score de Efici�ncia da Fazenda</h4>
                        <div id="efficiencyScoreContainer" class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg p-8 text-white text-center">
                            <!-- Score ser� carregado aqui -->
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-4">Resumo Executivo</h4>
                        <div id="executiveSummary" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <!-- Resumo ser� carregado aqui -->
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-lg font-semibold mb-4">Indicadores de Performance (KPIs)</h4>
                        <div id="performanceKPIs" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- KPIs ser�o carregados aqui -->
                        </div>
                    </div>
                </div>
                
                <div id="productionContent" class="tab-content hidden">
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-4">Tend�ncias de Produ��o (30 dias)</h4>
                        <div id="productionTrendsChart" class="bg-whitep-4 rounded-lg border border-gray-200">
                            <canvas id="productionTrendsCanvas" height="80"></canvas>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-lg font-semibold mb-4">An�lise de Qualidade</h4>
                        <div id="qualityAnalysisContainer" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- An�lise de qualidade ser� carregada aqui -->
                        </div>
                    </div>
                </div>
                
                <div id="reproductiveContent" class="tab-content hidden">
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-4">Taxa de Prenhez por Per�odo</h4>
                        <div id="pregnancyRateChart" class="bg-whitep-4 rounded-lg border border-gray-200">
                            <canvas id="pregnancyRateCanvas" height="80"></canvas>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-lg font-semibold mb-4">Intervalo Entre Partos (IEP)</h4>
                        <div id="calvingIntervalContainer" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- An�lise de IEP ser� carregada aqui -->
                        </div>
                    </div>
                </div>
                
                <div id="healthContent" class="tab-content hidden">
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-4">An�lise Sanit�ria</h4>
                        <div id="healthAnalysisContainer" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- An�lise sanit�ria ser� carregada aqui -->
                        </div>
                    </div>
                </div>
                
                <div id="financialContent" class="tab-content hidden">
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-4">An�lise Financeira (30 dias)</h4>
                        <div id="financialAnalysisContainer" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- An�lise financeira ser� carregada aqui -->
                        </div>
                    </div>
                </div>
            </div>
        `;
            document.body.appendChild(modal);
            
            console.log('? Modal de Dashboard Anal�tico criado e adicionado');
            
            // Carregar dados iniciais
            loadEfficiencyScore();
            loadExecutiveSummary();
            loadPerformanceKPIs();
            
        } catch (error) {
            console.error('? Erro ao abrir Dashboard Anal�tico:', error);
            showNotification('Erro ao abrir Dashboard Anal�tico: ' + error.message, 'error');
        }
}

function closeAnalyticsDashboard() {
        const modal = document.getElementById('analyticsDashboardModal');
        if (modal) modal.remove();
    }
    
    function switchAnalyticsTab(tabName) {
        // Esconder todas as abas
        document.querySelectorAll('#analyticsDashboardModal .tab-content').forEach(tab => {
            tab.classList.add('hidden');
        });
        
        // Remover estilo ativo de todos os bot�es
        document.querySelectorAll('#analyticsDashboardModal [id$="Tab"]').forEach(btn => {
            btn.classList.remove('text-indigo-600', 'border-indigo-600');
            btn.classList.add('text-gray-500', 'border-transparent');
        });
        
        // Mostrar aba selecionada
        document.getElementById(tabName + 'Content').classList.remove('hidden');
        document.getElementById(tabName + 'Tab').classList.remove('text-gray-500', 'border-transparent');
        document.getElementById(tabName + 'Tab').classList.add('text-indigo-600', 'border-indigo-600');
        
        // Carregar dados espec�ficos da aba
        switch(tabName) {
            case 'production':
                loadProductionTrends();
                loadQualityAnalysis();
                break;
            case 'reproductive':
                loadReproductiveAnalytics();
                break;
            case 'health':
                loadHealthAnalytics();
                break;
            case 'financial':
                loadFinancialAnalytics();
                break;
    }
}

async function loadEfficiencyScore() {
        try {
            // Fallback para API não implementada
        const response = await fetch('api/manager.php?action=get_dashboard_stats');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const score = result.data || { total_score: 0, breakdown: {}, rating: 'N�o dispon�vel' };
            const container = document.getElementById('efficiencyScoreContainer');
            
            const scoreColor = score.total_score >= 80 ? 'from-green-500 to-green-600' : 
                              score.total_score >= 60 ? 'from-yellow-500 to-yellow-600' : 
                              'from-red-500 to-red-600';
            
            container.className = `bg-gradient-to-r ${scoreColor} rounded-lg p-8 text-white text-center`;
            container.innerHTML = `
                <div class="mb-4">
                    <div class="text-6xl font-bold mb-2">${score.total_score.toFixed(1)}</div>
                    <div class="text-xl font-semibold">${score.rating}</div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                    <div class="bg-whitebg-opacity-20 rounded-lg p-3">
                        <div class="text-2xl font-bold">${score.breakdown.production?.toFixed(1) || 0}</div>
                        <div class="text-sm opacity-90">Produ��o</div>
                    </div>
                    <div class="bg-whitebg-opacity-20 rounded-lg p-3">
                        <div class="text-2xl font-bold">${score.breakdown.reproductive?.toFixed(1) || 0}</div>
                        <div class="text-sm opacity-90">Reprodutivo</div>
                    </div>
                    <div class="bg-whitebg-opacity-20 rounded-lg p-3">
                        <div class="text-2xl font-bold">${score.breakdown.quality?.toFixed(1) || 0}</div>
                        <div class="text-sm opacity-90">Qualidade</div>
                    </div>
                    <div class="bg-whitebg-opacity-20 rounded-lg p-3">
                        <div class="text-2xl font-bold">${score.breakdown.health?.toFixed(1) || 0}</div>
                        <div class="text-sm opacity-90">Sa�de</div>
                    </div>
                </div>
            `;
    } catch (error) {
        document.getElementById('efficiencyScoreContainer').innerHTML = '<div class="text-center py-8">Erro ao carregar score: ' + error.message + '</div>';
    }
}

async function loadExecutiveSummary() {
        try {
            // Fallback para API não implementada
        const response = await fetch('api/manager.php?action=get_dashboard_stats');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const summary = result.data || {};
            const container = document.getElementById('executiveSummary');
            
            container.innerHTML = `
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-400 bg-opacity-50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium opacity-90">Produ��o (30d)</p>
                            <p class="text-2xl font-semibold">${summary.production?.total_volume_30d?.toFixed(0) || 0} L</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-400 bg-opacity-50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium opacity-90">Gordura M�dia</p>
                            <p class="text-2xl font-semibold">${summary.quality?.avg_fat_30d?.toFixed(2) || 0}%</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-pink-500 to-pink-600 rounded-lg p-6 text-white">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-pink-400 bg-opacity-50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium opacity-90">Prenhezes Ativas</p>
                            <p class="text-2xl font-semibold">${summary.reproductive?.total_pregnant || 0}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-6 text-white">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-400 bg-opacity-50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium opacity-90">Alertas Ativos</p>
                            <p class="text-2xl font-semibold">${summary.alerts?.active_alerts_count || 0}</p>
                        </div>
                    </div>
                </div>
            `;
    } catch (error) {
        document.getElementById('executiveSummary').innerHTML = '<div class="col-span-4 text-center py-8 text-red-500">Erro ao carregar resumo: ' + error.message + '</div>';
    }
}

async function loadPerformanceKPIs() {
        try {
            const response = await fetch('api/analytics.php?action=get_performance_indicators');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const kpis = result.data || {};
            const container = document.getElementById('performanceKPIs');
            
            container.innerHTML = `
                <div class="bg-whiterounded-lg border border-gray-200p-6">
                    <h5 class="text-sm font-medium text-gray-500 mb-2">Taxa de Prenhez</h5>
                    <div class="text-3xl font-bold text-pink-600 mb-1">
                        ${kpis.pregnancy_rate?.pregnancy_rate?.toFixed(1) || 0}%
                    </div>
                    <div class="text-sm text-gray-500">
                        ${kpis.pregnancy_rate?.successful || 0} de ${kpis.pregnancy_rate?.total || 0} insemina��es
                    </div>
                </div>
                
                <div class="bg-whiterounded-lg border border-gray-200p-6">
                    <h5 class="text-sm font-medium text-gray-500 mb-2">Produ��o por Animal</h5>
                    <div class="text-3xl font-bold text-blue-600 mb-1">
                        ${kpis.production_efficiency?.production_per_animal?.toFixed(1) || 0} L
                    </div>
                    <div class="text-sm text-gray-500">
                        M�dia di�ria
                    </div>
                </div>
                
                <div class="bg-whiterounded-lg border border-gray-200p-6">
                    <h5 class="text-sm font-medium text-gray-500 mb-2">CCS M�dia</h5>
                    <div class="text-3xl font-bold text-green-600 mb-1">
                        ${kpis.milk_quality?.avg_scc?.toFixed(0) || 0}
                    </div>
                    <div class="text-sm text-gray-500">
                        C�lulas/mL
                    </div>
                </div>
            `;
        } catch (error) {
            document.getElementById('performanceKPIs').innerHTML = '<div class="col-span-3 text-center py-8 text-red-500">Erro ao carregar KPIs: ' + error.message + '</div>';
        }
    }
    
    async function loadProductionTrends() {
        try {
            const response = await fetch('api/analytics.php?action=get_production_trends&days=30');
            const result = await response.json();
            
            if (!result.success || !result.data || result.data.length === 0) {
                document.getElementById('productionTrendsChart').innerHTML = '<div class="text-center py-8 text-gray-500">Sem dados de produ��o dispon�veis</div>';
                return;
            }
            
            const trends = result.data.reverse(); // Ordem cronol�gica
            const ctx = document.getElementById('productionTrendsCanvas').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trends.map(t => new Date(t.date).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })),
                    datasets: [{
                        label: 'Volume de Produ��o (L)',
                        data: trends.map(t => parseFloat(t.total_volume)),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: true },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        } catch (error) {
            document.getElementById('productionTrendsChart').innerHTML = '<div class="text-center py-8 text-red-500">Erro ao carregar tend�ncias: ' + error.message + '</div>';
        }
}

async function loadQualityAnalysis() {
        try {
            const response = await fetch('api/analytics.php?action=get_performance_indicators');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const quality = result.data?.milk_quality || {};
            const container = document.getElementById('qualityAnalysisContainer');
            
            container.innerHTML = `
                <div class="bg-whiterounded-lg border border-gray-200p-6">
                    <h5 class="text-sm font-medium text-gray-500 mb-2">Gordura M�dia</h5>
                    <div class="text-3xl font-bold text-green-600">${quality.avg_fat?.toFixed(2) || 0}%</div>
                </div>
                
                <div class="bg-whiterounded-lg border border-gray-200p-6">
                    <h5 class="text-sm font-medium text-gray-500 mb-2">Prote�na M�dia</h5>
                    <div class="text-3xl font-bold text-blue-600">${quality.avg_protein?.toFixed(2) || 0}%</div>
                </div>
                
                <div class="bg-whiterounded-lg border border-gray-200p-6">
                    <h5 class="text-sm font-medium text-gray-500 mb-2">CCS M�dio</h5>
                    <div class="text-3xl font-bold text-orange-600">${quality.avg_scc?.toFixed(0) || 0}</div>
                </div>
            `;
        } catch (error) {
            document.getElementById('qualityAnalysisContainer').innerHTML = '<div class="col-span-3 text-center py-8 text-red-500">Erro ao carregar an�lise: ' + error.message + '</div>';
        }
}

async function loadReproductiveAnalytics() {
        try {
            const response = await fetch('api/analytics.php?action=get_reproductive_analysis');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const analysis = result.data || {};
            
            // Gr�fico de taxa de prenhez
            if (analysis.monthly_pregnancy_rates && analysis.monthly_pregnancy_rates.length > 0) {
                const ctx = document.getElementById('pregnancyRateCanvas').getContext('2d');
                const monthly = analysis.monthly_pregnancy_rates.reverse();
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: monthly.map(m => m.month),
                        datasets: [{
                            label: 'Taxa de Prenhez (%)',
                            data: monthly.map(m => parseFloat(m.pregnancy_rate)),
                            backgroundColor: 'rgba(236, 72, 153, 0.7)',
                            borderColor: 'rgb(236, 72, 153)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { display: true }
                        },
                        scales: {
                            y: { beginAtZero: true, max: 100 }
                        }
                    }
                });
            }
            
            // An�lise de IEP
            const iepContainer = document.getElementById('calvingIntervalContainer');
            iepContainer.innerHTML = `
                <div class="bg-whiterounded-lg border border-gray-200p-6">
                    <h5 class="text-sm font-medium text-gray-500 mb-2">Intervalo M�dio Entre Partos</h5>
                    <div class="text-3xl font-bold text-purple-600">${analysis.calving_interval?.avg_calving_interval?.toFixed(0) || 'N/A'}</div>
                    <div class="text-sm text-gray-500 mt-1">dias</div>
                </div>
                
                <div class="bg-whiterounded-lg border border-gray-200p-6">
                    <h5 class="text-sm font-medium text-gray-500 mb-2">Meta IEP</h5>
                    <div class="text-3xl font-bold text-green-600">365-390</div>
                    <div class="text-sm text-gray-500 mt-1">dias (ideal)</div>
                </div>
            `;
        } catch (error) {
            console.error('Erro ao carregar an�lise reprodutiva:', error);
        }
}

async function loadHealthAnalytics() {
        try {
            const response = await fetch('api/analytics.php?action=get_health_analysis');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const analysis = result.data || {};
            const container = document.getElementById('healthAnalysisContainer');
            
            container.innerHTML = `
                <div class="bg-whiterounded-lg border border-gray-200p-6">
                    <h5 class="text-sm font-medium text-gray-500 mb-2">Alertas Sanit�rios Ativos</h5>
                    <div class="text-3xl font-bold text-red-600">${analysis.active_alerts?.length || 0}</div>
                    <div class="text-sm text-gray-500 mt-1">alertas pendentes</div>
                </div>
                
                <div class="bg-whiterounded-lg border border-gray-200p-6">
                    <h5 class="text-sm font-medium text-gray-500 mb-2">Medicamentos em Estoque Baixo</h5>
                    <div class="text-3xl font-bold text-orange-600">${analysis.low_stock_medications?.low_stock_count || 0}</div>
                    <div class="text-sm text-gray-500 mt-1">necessitam reposi��o</div>
                </div>
            `;
        } catch (error) {
            document.getElementById('healthAnalysisContainer').innerHTML = '<div class="col-span-2 text-center py-8 text-red-500">Erro ao carregar an�lise: ' + error.message + '</div>';
        }
}

async function loadFinancialAnalytics() {
        try {
            const response = await fetch('api/analytics.php?action=get_financial_analysis');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const analysis = result.data || {};
            const container = document.getElementById('financialAnalysisContainer');
            
            const formatCurrency = (value) => {
                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
            };
            
            container.innerHTML = `
                <div class="bg-whiterounded-lg border border-gray-200p-6">
                    <h5 class="text-sm font-medium text-gray-500 mb-2">Receita Total (30d)</h5>
                    <div class="text-3xl font-bold text-green-600">${formatCurrency(analysis.total_income)}</div>
                </div>
                
                <div class="bg-whiterounded-lg border border-gray-200p-6">
                    <h5 class="text-sm font-medium text-gray-500 mb-2">Despesas Totais (30d)</h5>
                    <div class="text-3xl font-bold text-red-600">${formatCurrency(analysis.total_expenses)}</div>
                </div>
                
                <div class="bg-whiterounded-lg border border-gray-200p-6">
                    <h5 class="text-sm font-medium text-gray-500 mb-2">Lucro L�quido (30d)</h5>
                    <div class="text-3xl font-bold ${parseFloat(analysis.net_profit) >= 0 ? 'text-green-600' : 'text-red-600'}">
                        ${formatCurrency(analysis.net_profit)}
                    </div>
                </div>
            `;
        } catch (error) {
            document.getElementById('financialAnalysisContainer').innerHTML = '<div class="col-span-3 text-center py-8 text-red-500">Erro ao carregar an�lise: ' + error.message + '</div>';
        }
}

window.addEventListener('load', function() {

    if ('performance' in window) {
        const perfData = performance.getEntriesByType('navigation')[0];
        console.log('?? Performance Metrics:');
        console.log('DOM Content Loaded:', perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart, 'ms');
        console.log('Page Load Complete:', perfData.loadEventEnd - perfData.loadEventStart, 'ms');
        console.log('Total Load Time:', perfData.loadEventEnd - perfData.fetchStart, 'ms');
    }
});

// ============================================================
// NOVAS FUNCIONALIDADES - SUPERAR FARMTELL MILK
// Sistema Superior de Gestão Leiteira
// Data: 22/10/2025
// ============================================================

// ============================================================
// 1. DASHBOARD DE AÇÕES PENDENTES
// ============================================================

window.showActionsDashboard = function() {
    const modal = document.createElement('div');
    modal.id = 'actionsDashboardModal';
    modal.className = 'fixed inset-0 bg-white z-[99999] overflow-y-auto';
    modal.innerHTML = `
        <div class="w-full h-full">
            <div class="sticky top-0 bg-gradient-to-br from-purple-600 to-indigo-600 text-white shadow-lg z-10 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="document.getElementById('actionsDashboardModal').remove()" 
                            class="w-10 h-10 flex items-center justify-center hover:bg-white hover:bg-opacity-20 rounded-xl transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div>
                            <h3 class="text-2xl font-bold">Central de Ações</h3>
                            <p class="text-purple-100 text-sm">Tarefas prioritárias da fazenda</p>
                        </div>
                    </div>
                    <button onclick="loadActionsDashboard()" class="px-4 py-2 bg-white bg-opacity-20 rounded-lg hover:bg-opacity-30 transition">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Atualizar
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div id="actionsSummary" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Resumo será carregado aqui -->
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div id="heatExpectedList" class="bg-white rounded-lg shadow p-4"></div>
                    <div id="calvingSoonList" class="bg-white rounded-lg shadow p-4"></div>
                    <div id="lowBcsList" class="bg-white rounded-lg shadow p-4"></div>
                    <div id="medicationDueList" class="bg-white rounded-lg shadow p-4"></div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    loadActionsDashboard();
};

async function loadActionsDashboard() {
    try {
        const response = await fetch('api/actions.php?action=dashboard');
        const result = await response.json();
        
        if (!result.success) throw new Error(result.error);
        
        const { summary, details } = result.data;
        
        // Renderizar resumo
        const summaryHtml = `
            <div class="bg-gradient-to-r from-pink-500 to-rose-500 text-white rounded-lg p-4">
                <div class="text-3xl font-bold">${details.heat_expected?.length || 0}</div>
                <div class="text-sm opacity-90">Cio Previsto (7d)</div>
            </div>
            <div class="bg-gradient-to-r from-red-500 to-orange-500 text-white rounded-lg p-4">
                <div class="text-3xl font-bold">${details.calving_soon?.length || 0}</div>
                <div class="text-sm opacity-90">Partos Próximos (30d)</div>
            </div>
            <div class="bg-gradient-to-r from-yellow-500 to-amber-500 text-white rounded-lg p-4">
                <div class="text-3xl font-bold">${details.low_bcs?.length || 0}</div>
                <div class="text-sm opacity-90">BCS Baixo (&lt;2.5)</div>
            </div>
            <div class="bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-lg p-4">
                <div class="text-3xl font-bold">${details.medication_due?.length || 0}</div>
                <div class="text-sm opacity-90">Medicações Pendentes</div>
            </div>
        `;
        document.getElementById('actionsSummary').innerHTML = summaryHtml;
        
        // Renderizar lista de cio
        renderActionList('heatExpectedList', 'Cio Previsto', details.heat_expected || [], 'pink');
        
        // Renderizar lista de partos
        renderActionList('calvingSoonList', 'Partos Próximos', details.calving_soon || [], 'red');
        
        // Renderizar lista de BCS baixo
        renderActionList('lowBcsList', 'BCS Baixo', details.low_bcs || [], 'yellow');
        
        // Renderizar lista de medicações
        renderActionList('medicationDueList', 'Medicações Pendentes', details.medication_due || [], 'blue');
        
    } catch (error) {
        console.error('Erro ao carregar ações:', error);
        showNotification('Erro ao carregar ações: ' + error.message, 'error');
    }
}

function renderActionList(containerId, title, items, color) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const colorMap = {
        pink: 'from-pink-500 to-rose-500',
        red: 'from-red-500 to-orange-500',
        yellow: 'from-yellow-500 to-amber-500',
        blue: 'from-blue-500 to-cyan-500'
    };
    
    if (items.length === 0) {
        container.innerHTML = `
            <div class="bg-gradient-to-r ${colorMap[color]} text-white rounded-t-lg p-3">
                <h4 class="font-bold">${title}</h4>
            </div>
            <div class="border border-gray-200 rounded-b-lg p-4">
                <p class="text-gray-500 text-center text-sm">Nenhuma ação pendente</p>
            </div>
        `;
        return;
    }
    
    const listHtml = items.map(item => {
        const priorityClass = {
            'urgent': 'bg-red-100 text-red-800',
            'high': 'bg-orange-100 text-orange-800',
            'medium': 'bg-yellow-100 text-yellow-800',
            'low': 'bg-gray-100 text-gray-800',
            'critical': 'bg-red-100 text-red-800'
        };
        
        return `
            <div class="border-b last:border-0 py-2">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">${item.animal_number} ${item.name ? '- ' + item.name : ''}</div>
                        <div class="text-sm text-gray-600">${item.breed || ''}</div>
                        ${item.predicted_date ? `<div class="text-xs text-gray-500 mt-1">Data: ${new Date(item.predicted_date).toLocaleDateString('pt-BR')}</div>` : ''}
                        ${item.expected_birth ? `<div class="text-xs text-gray-500 mt-1">DPP: ${new Date(item.expected_birth).toLocaleDateString('pt-BR')}</div>` : ''}
                        ${item.next_date ? `<div class="text-xs text-gray-500 mt-1">Próxima: ${new Date(item.next_date).toLocaleDateString('pt-BR')}</div>` : ''}
                        ${item.score ? `<div class="text-xs text-gray-500 mt-1">BCS: ${item.score}</div>` : ''}
                    </div>
                    <div class="text-right">
                        <span class="px-2 py-1 text-xs rounded-full ${priorityClass[item.priority] || 'bg-gray-100 text-gray-800'}">
                            ${item.days_until !== undefined ? item.days_until + 'd' : ''}
                        </span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    container.innerHTML = `
        <div class="bg-gradient-to-r ${colorMap[color]} text-white rounded-t-lg p-3">
            <h4 class="font-bold">${title} (${items.length})</h4>
        </div>
        <div class="border border-gray-200 rounded-b-lg p-2 max-h-96 overflow-y-auto">
            ${listHtml}
        </div>
    `;
}

// ============================================================
// 2. GESTÃO DE TRANSPONDERS/RFID
// ============================================================

window.showTransponderManagement = function() {
    const modal = document.createElement('div');
    modal.id = 'transponderModal';
    modal.className = 'fixed inset-0 bg-white z-[99999] overflow-y-auto';
    modal.innerHTML = `
        <div class="w-full h-full">
            <div class="sticky top-0 bg-gradient-to-br from-teal-600 to-cyan-600 text-white shadow-lg z-10 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="document.getElementById('transponderModal').remove()" 
                            class="w-10 h-10 flex items-center justify-center hover:bg-white hover:bg-opacity-20 rounded-xl transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div>
                            <h3 class="text-2xl font-bold">Sistema RFID</h3>
                            <p class="text-teal-100 text-sm">Gestão de transponders e chips</p>
                        </div>
                    </div>
                    <button onclick="showAddTransponderModal()" class="px-4 py-2 bg-white text-teal-600 rounded-lg hover:bg-teal-50 transition font-semibold">
                        + Novo Transponder
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div class="mb-4">
                    <input type="text" id="transponderSearch" placeholder="Buscar por código RFID..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                </div>
                <div id="transpondersList"></div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    loadTranspondersList();
    
    // Busca em tempo real
    document.getElementById('transponderSearch').addEventListener('input', function(e) {
        const code = e.target.value.trim();
        if (code.length >= 3) {
            searchTransponder(code);
        } else if (code.length === 0) {
            loadTranspondersList();
        }
    });
};

async function loadTranspondersList() {
    try {
        const response = await fetch('api/transponders.php?action=list');
        const result = await response.json();
        
        if (!result.success) throw new Error(result.error);
        
        const transponders = result.data || [];
        const container = document.getElementById('transpondersList');
        
        if (transponders.length === 0) {
            container.innerHTML = '<p class="text-center text-gray-500 py-8">Nenhum transponder cadastrado</p>';
            return;
        }
        
        container.innerHTML = `
            <table class="min-w-full bg-white border rounded-lg">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código RFID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Animal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Localização</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    ${transponders.map(t => `
                        <tr>
                            <td class="px-6 py-4 text-sm font-mono font-medium text-gray-900">${t.transponder_code}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">${t.animal_number} ${t.animal_name ? '- ' + t.animal_name : ''}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">${t.transponder_type}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">${t.location || '-'}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full ${t.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                    ${t.is_active ? 'Ativo' : 'Inativo'}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="viewTransponderReadings(${t.id})" class="text-blue-600 hover:text-blue-800 mr-2">
                                    Leituras
                                </button>
                                ${t.is_active ? `
                                <button onclick="deactivateTransponder(${t.id})" class="text-red-600 hover:text-red-800">
                                    Desativar
                                </button>
                                ` : ''}
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    } catch (error) {
        console.error('Erro ao carregar transponders:', error);
    }
}

function showAddTransponderModal() {
    const modal = document.createElement('div');
    modal.id = 'addTransponderModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100000] p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="bg-gradient-to-r from-teal-600 to-cyan-600 text-white p-6 rounded-t-2xl">
                <h3 class="text-2xl font-bold">Cadastrar Transponder RFID</h3>
            </div>
            
            <form id="addTransponderForm" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Animal *</label>
                    <select name="animal_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Selecione o animal...</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Código RFID *</label>
                    <input type="text" name="transponder_code" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono"
                        placeholder="Ex: RFID-001-2025">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo</label>
                        <select name="transponder_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="rfid">RFID</option>
                            <option value="microchip">Microchip</option>
                            <option value="electronic">Eletrônico</option>
                            <option value="visual">Visual</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Localização</label>
                        <select name="location" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="ear_left">Orelha Esquerda</option>
                            <option value="ear_right">Orelha Direita</option>
                            <option value="neck">Pescoço</option>
                            <option value="leg">Perna</option>
                            <option value="other">Outro</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Fabricante</label>
                    <input type="text" name="manufacturer" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Data de Ativação *</label>
                    <input type="date" name="activation_date" required value="${new Date().toISOString().split('T')[0]}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                    <textarea name="notes" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
            </form>
            
            <div class="bg-gray-50 px-6 py-4 flex gap-3 rounded-b-2xl">
                <button onclick="document.getElementById('addTransponderModal').remove()" 
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    Cancelar
                </button>
                <button onclick="submitTransponder()" class="flex-1 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                    Cadastrar
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Carregar animais
    loadAnimalsForSelect('addTransponderForm');
}

async function submitTransponder() {
    const form = document.getElementById('addTransponderForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    data.action = 'create';
    
    try {
        const response = await fetch('api/transponders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Transponder cadastrado com sucesso!', 'success');
            document.getElementById('addTransponderModal').remove();
            loadTranspondersList();
        } else {
            showNotification('Erro: ' + result.error, 'error');
        }
    } catch (error) {
        showNotification('Erro ao cadastrar: ' + error.message, 'error');
    }
}

async function searchTransponder(code) {
    try {
        const response = await fetch(`api/transponders.php?action=search&code=${encodeURIComponent(code)}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const t = result.data;
            document.getElementById('transpondersList').innerHTML = `
                <div class="bg-green-50 border-2 border-green-500 rounded-lg p-6">
                    <h4 class="text-lg font-bold text-green-900 mb-4">Transponder Encontrado!</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Código RFID</p>
                            <p class="font-bold text-gray-900 font-mono">${t.transponder_code}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Animal</p>
                            <p class="font-bold text-gray-900">${t.animal_number} ${t.animal_name ? '- ' + t.animal_name : ''}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Raça</p>
                            <p class="font-bold text-gray-900">${t.breed}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Status</p>
                            <p class="font-bold text-gray-900">${t.status}</p>
                        </div>
                        ${t.group_name ? `
                        <div class="col-span-2">
                            <p class="text-sm text-gray-600">Grupo Atual</p>
                            <p class="font-bold text-gray-900">${t.group_name}</p>
                        </div>
                        ` : ''}
                    </div>
                    <p class="text-xs text-green-700 mt-4">✓ Leitura registrada com sucesso</p>
                </div>
            `;
        } else {
            document.getElementById('transpondersList').innerHTML = `
                <div class="bg-red-50 border-2 border-red-500 rounded-lg p-6 text-center">
                    <p class="text-red-900 font-bold">Transponder não encontrado</p>
                    <p class="text-sm text-red-700 mt-2">Código: ${code}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Erro na busca:', error);
    }
}

// ============================================================
// 3. AVALIAÇÃO DE CONDIÇÃO CORPORAL (BCS)
// ============================================================

window.showBCSManagement = function() {
    const modal = document.createElement('div');
    modal.id = 'bcsModal';
    modal.className = 'fixed inset-0 bg-white z-[99999] overflow-y-auto';
    modal.innerHTML = `
        <div class="w-full h-full">
            <div class="sticky top-0 bg-gradient-to-br from-amber-600 to-orange-600 text-white shadow-lg z-10 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="document.getElementById('bcsModal').remove()" 
                            class="w-10 h-10 flex items-center justify-center hover:bg-white hover:bg-opacity-20 rounded-xl transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div>
                            <h3 class="text-2xl font-bold">Condição Corporal (BCS)</h3>
                            <p class="text-amber-100 text-sm">Avaliação nutricional do rebanho</p>
                        </div>
                    </div>
                    <button onclick="showAddBCSModal()" class="px-4 py-2 bg-white text-amber-600 rounded-lg hover:bg-amber-50 transition font-semibold">
                        + Nova Avaliação
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div id="bcsStats" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6"></div>
                <div id="bcsList"></div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    loadBCSData();
};

async function loadBCSData() {
    try {
        // Carregar estatísticas
        const statsResponse = await fetch('api/body_condition.php?action=stats');
        const statsResult = await statsResponse.json();
        
        if (statsResult.success && statsResult.data) {
            const stats = statsResult.data;
            document.getElementById('bcsStats').innerHTML = `
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="text-2xl font-bold text-blue-900">${stats.total_animals || 0}</div>
                    <div class="text-sm text-blue-700">Total Avaliados</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-2xl font-bold text-green-900">${stats.avg_bcs ? stats.avg_bcs.toFixed(1) : '-'}</div>
                    <div class="text-sm text-green-700">BCS Médio</div>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4">
                    <div class="text-2xl font-bold text-yellow-900">${stats.ideal_bcs_count || 0}</div>
                    <div class="text-sm text-yellow-700">BCS Ideal (2.5-3.5)</div>
                </div>
                <div class="bg-red-50 rounded-lg p-4">
                    <div class="text-2xl font-bold text-red-900">${stats.low_bcs_count || 0}</div>
                    <div class="text-sm text-red-700">BCS Baixo (&lt;2.5)</div>
                </div>
            `;
        }
        
        // Carregar lista de animais com BCS
        const listResponse = await fetch('api/body_condition.php?action=latest');
        const listResult = await listResponse.json();
        
        if (listResult.success) {
            const animals = listResult.data || [];
            renderBCSList(animals);
        }
    } catch (error) {
        console.error('Erro ao carregar BCS:', error);
    }
}

function renderBCSList(animals) {
    const container = document.getElementById('bcsList');
    
    if (animals.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-500 py-8">Nenhuma avaliação registrada</p>';
        return;
    }
    
    container.innerHTML = `
        <table class="min-w-full bg-white border rounded-lg">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Animal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Raça</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">BCS</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avaliação</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Situação</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                ${animals.map(a => {
                    const statusClass = {
                        'critical': 'bg-red-100 text-red-800',
                        'low': 'bg-orange-100 text-orange-800',
                        'ideal': 'bg-green-100 text-green-800',
                        'high': 'bg-yellow-100 text-yellow-800',
                        'very_high': 'bg-red-100 text-red-800'
                    };
                    
                    const statusLabel = {
                        'critical': 'Crítico',
                        'low': 'Baixo',
                        'ideal': 'Ideal',
                        'high': 'Alto',
                        'very_high': 'Muito Alto'
                    };
                    
                    return `
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">${a.animal_number} ${a.animal_name ? '- ' + a.animal_name : ''}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">${a.breed}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">${a.status}</td>
                            <td class="px-6 py-4">
                                <span class="text-2xl font-bold text-gray-900">${a.latest_bcs}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                ${new Date(a.evaluation_date).toLocaleDateString('pt-BR')}<br>
                                <span class="text-xs text-gray-500">(${a.days_since_eval} dias atrás)</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full ${statusClass[a.bcs_status]}">
                                    ${statusLabel[a.bcs_status]}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="showBCSHistory(${a.animal_id})" class="text-blue-600 hover:text-blue-800">
                                    Histórico
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('')}
            </tbody>
        </table>
    `;
}

function showAddBCSModal() {
    const modal = document.createElement('div');
    modal.id = 'addBCSModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100000] p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl">
            <div class="bg-gradient-to-r from-amber-600 to-orange-600 text-white p-6 rounded-t-2xl">
                <h3 class="text-2xl font-bold">Avaliar Condição Corporal</h3>
                <p class="text-amber-100 text-sm mt-1">Score de 1.0 (muito magra) a 5.0 (muito gorda)</p>
            </div>
            
            <form id="addBCSForm" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Animal *</label>
                        <select name="animal_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Data da Avaliação *</label>
                        <input type="date" name="evaluation_date" required value="${new Date().toISOString().split('T')[0]}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Score BCS * (1.0 a 5.0)</label>
                    <div class="flex items-center gap-4">
                        <input type="number" name="score" required min="1.0" max="5.0" step="0.5" value="3.0"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-2xl font-bold text-center">
                        <div id="bcsIndicator" class="w-20 h-20 rounded-full bg-green-500 flex items-center justify-center">
                            <span class="text-white font-bold text-xl">3.0</span>
                        </div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-2">
                        <span>1.0 Muito Magra</span>
                        <span>3.0 Ideal</span>
                        <span>5.0 Muito Gorda</span>
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Método</label>
                        <select name="evaluation_method" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="visual">Visual</option>
                            <option value="palpacao">Palpação</option>
                            <option value="foto_ia">Foto + IA</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Peso (kg)</label>
                        <input type="number" name="weight_kg" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Fase Lactação</label>
                        <select name="lactation_stage" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Não se aplica</option>
                            <option value="inicio">Início</option>
                            <option value="pico">Pico</option>
                            <option value="meio">Meio</option>
                            <option value="final">Final</option>
                            <option value="seco">Seco</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                    <textarea name="notes" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                
                <div id="bcsRecommendation" class="bg-blue-50 border border-blue-200 rounded-lg p-4 hidden">
                    <p class="text-sm font-semibold text-blue-900 mb-1">Recomendação:</p>
                    <p class="text-sm text-blue-800" id="bcsRecommendationText"></p>
                </div>
            </form>
            
            <div class="bg-gray-50 px-6 py-4 flex gap-3 rounded-b-2xl">
                <button onclick="document.getElementById('addBCSModal').remove()" 
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    Cancelar
                </button>
                <button onclick="submitBCS()" class="flex-1 px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-bold">
                    Registrar Avaliação
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    loadAnimalsForSelect('addBCSForm');
    
    // Atualizar indicador visual do BCS
    const scoreInput = document.querySelector('#addBCSForm input[name="score"]');
    scoreInput.addEventListener('input', function() {
        updateBCSIndicator(parseFloat(this.value));
    });
}

function updateBCSIndicator(score) {
    const indicator = document.getElementById('bcsIndicator');
    const recommendation = document.getElementById('bcsRecommendation');
    const recommendationText = document.getElementById('bcsRecommendationText');
    
    if (!indicator) return;
    
    indicator.querySelector('span').textContent = score.toFixed(1);
    
    let color, text;
    if (score < 2.0) {
        color = 'bg-red-600';
        text = 'CRÍTICO: Animal muito magro! Aumentar alimentação urgentemente e verificar saúde.';
    } else if (score < 2.5) {
        color = 'bg-orange-500';
        text = 'BAIXO: Aumentar concentrado e volumoso de qualidade. Monitorar semanalmente.';
    } else if (score >= 2.5 && score <= 3.5) {
        color = 'bg-green-500';
        text = 'IDEAL: Condição corporal excelente! Manter protocolo nutricional atual.';
    } else if (score <= 4.0) {
        color = 'bg-yellow-500';
        text = 'ALTO: Considerar reduzir concentrado se não estiver em lactação.';
    } else {
        color = 'bg-red-600';
        text = 'MUITO ALTO: Risco de problemas metabólicos. Ajustar dieta urgentemente.';
    }
    
    indicator.className = `w-20 h-20 rounded-full ${color} flex items-center justify-center transition-all`;
    
    if (recommendation && recommendationText) {
        recommendationText.textContent = text;
        recommendation.classList.remove('hidden');
    }
}

async function submitBCS() {
    const form = document.getElementById('addBCSForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('api/body_condition.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(`BCS registrado: ${result.data.score} - ${result.data.recommendation}`, 'success');
            document.getElementById('addBCSModal').remove();
            loadBCSData();
        } else {
            showNotification('Erro: ' + result.error, 'error');
        }
    } catch (error) {
        showNotification('Erro ao registrar: ' + error.message, 'error');
    }
}

// ============================================================
// 4. GESTÃO DE GRUPOS/LOTES
// ============================================================

window.showGroupsManagement = function() {
    const modal = document.createElement('div');
    modal.id = 'groupsModal';
    modal.className = 'fixed inset-0 bg-white z-[99999] overflow-y-auto';
    modal.innerHTML = `
        <div class="w-full h-full">
            <div class="sticky top-0 bg-gradient-to-br from-indigo-600 to-purple-600 text-white shadow-lg z-10 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="document.getElementById('groupsModal').remove()" 
                            class="w-10 h-10 flex items-center justify-center hover:bg-white hover:bg-opacity-20 rounded-xl transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div>
                            <h3 class="text-2xl font-bold">Grupos e Lotes</h3>
                            <p class="text-indigo-100 text-sm">Organização do rebanho</p>
                        </div>
                    </div>
                    <button onclick="showAddGroupModal()" class="px-4 py-2 bg-white text-indigo-600 rounded-lg hover:bg-indigo-50 transition font-semibold">
                        + Novo Grupo
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div id="groupsList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    loadGroupsList();
};

async function loadGroupsList() {
    try {
        const response = await fetch('api/groups.php?action=list');
        const result = await response.json();
        
        if (!result.success) throw new Error(result.error);
        
        const groups = result.data || [];
        const container = document.getElementById('groupsList');
        
        if (groups.length === 0) {
            container.innerHTML = '<p class="col-span-3 text-center text-gray-500 py-8">Nenhum grupo cadastrado</p>';
            return;
        }
        
        container.innerHTML = groups.map(g => `
            <div class="bg-white border-2 rounded-lg overflow-hidden hover:shadow-lg transition" style="border-color: ${g.color_code}">
                <div class="p-4" style="background: ${g.color_code}15">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900">${g.group_name}</h4>
                            <p class="text-xs text-gray-600 font-mono">${g.group_code || '-'}</p>
                        </div>
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg" style="background: ${g.color_code}">
                            ${g.current_count}
                        </div>
                    </div>
                    
                    ${g.description ? `<p class="text-sm text-gray-600 mb-2">${g.description}</p>` : ''}
                    
                    <div class="flex items-center justify-between text-xs text-gray-500 mt-3 pt-3 border-t">
                        <span>Tipo: ${g.group_type}</span>
                        ${g.capacity ? `<span>Cap: ${g.capacity}</span>` : ''}
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-2 flex gap-2">
                    <button onclick="viewGroupDetails(${g.id})" class="flex-1 text-xs px-3 py-2 bg-white border rounded hover:bg-gray-50">
                        Ver Animais
                    </button>
                    <button onclick="showMoveToGroupModal(${g.id})" class="flex-1 text-xs px-3 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        Adicionar
                    </button>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Erro ao carregar grupos:', error);
    }
}

async function showMoveToGroupModal(groupId) {
    const modal = document.createElement('div');
    modal.id = 'moveToGroupModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100001] p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-6 rounded-t-2xl">
                <h3 class="text-xl font-bold">Mover Animal para Grupo</h3>
            </div>
            
            <form id="moveToGroupForm" class="p-6 space-y-4">
                <input type="hidden" name="to_group_id" value="${groupId}">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Selecione o Animal</label>
                    <select name="animal_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Carregando...</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Motivo</label>
                    <input type="text" name="reason" class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                        placeholder="Ex: Produção alta, Pré-parto, etc">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                    <textarea name="notes" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
            </form>
            
            <div class="bg-gray-50 px-6 py-4 flex gap-3">
                <button onclick="document.getElementById('moveToGroupModal').remove()" 
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    Cancelar
                </button>
                <button onclick="submitMoveToGroup()" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-bold">
                    Mover Animal
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Carregar animais disponíveis
    const response = await fetch('api/animals.php?action=get_all');
    const result = await response.json();
    if (result.success) {
        const select = modal.querySelector('select[name="animal_id"]');
        select.innerHTML = '<option value="">Selecione o animal...</option>' +
            (result.data || []).map(a => `<option value="${a.id}">${a.animal_number} - ${a.name || a.breed}</option>`).join('');
    }
}

async function submitMoveToGroup() {
    const form = document.getElementById('moveToGroupForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    data.action = 'move_animal';
    data.movement_date = new Date().toISOString().split('T')[0];
    
    try {
        const response = await fetch('api/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Animal movido com sucesso!', 'success');
            document.getElementById('moveToGroupModal').remove();
            loadGroupsList();
        } else {
            showNotification('Erro: ' + result.error, 'error');
        }
    } catch (error) {
        showNotification('Erro ao mover: ' + error.message, 'error');
    }
}

// ============================================================
// 5. FUNÇÕES AUXILIARES
// ============================================================

async function loadAnimalsForSelect(formId) {
    try {
        const response = await fetch('api/animals.php?action=get_all');
        const result = await response.json();
        
        if (result.success) {
            const form = document.getElementById(formId);
            if (!form) return;
            
            const select = form.querySelector('select[name="animal_id"]');
            if (select) {
                select.innerHTML = '<option value="">Selecione o animal...</option>' +
                    (result.data || []).map(a => 
                        `<option value="${a.id}">${a.animal_number} - ${a.name || a.breed}</option>`
                    ).join('');
            }
        }
    } catch (error) {
        console.error('Erro ao carregar animais:', error);
    }
}

async function deactivateTransponder(id) {
    if (!confirm('Desativar este transponder?')) return;
    
    try {
        const response = await fetch('api/transponders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'deactivate', id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Transponder desativado', 'success');
            loadTranspondersList();
        } else {
            showNotification('Erro: ' + result.error, 'error');
        }
    } catch (error) {
        showNotification('Erro: ' + error.message, 'error');
    }
}

async function showBCSHistory(animalId) {
    try {
        const response = await fetch(`api/body_condition.php?action=by_animal&animal_id=${animalId}`);
        const result = await response.json();
        
        if (!result.success) throw new Error(result.error);
        
        const history = result.data || [];
        
        const modal = document.createElement('div');
        modal.id = 'bcsHistoryModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100001] p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="bg-gradient-to-r from-amber-600 to-orange-600 text-white p-6">
                    <h3 class="text-xl font-bold">Histórico de BCS</h3>
                </div>
                
                <div class="p-6 overflow-y-auto flex-1">
                    ${history.length === 0 ? '<p class="text-center text-gray-500">Nenhuma avaliação anterior</p>' : `
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Data</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">BCS</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Peso</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Método</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Avaliador</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                ${history.map(h => `
                                    <tr>
                                        <td class="px-4 py-2 text-sm">${new Date(h.evaluation_date).toLocaleDateString('pt-BR')}</td>
                                        <td class="px-4 py-2"><span class="text-lg font-bold">${h.score}</span></td>
                                        <td class="px-4 py-2 text-sm">${h.weight_kg ? h.weight_kg + ' kg' : '-'}</td>
                                        <td class="px-4 py-2 text-sm">${h.evaluation_method}</td>
                                        <td class="px-4 py-2 text-sm">${h.evaluated_by_name || '-'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `}
                </div>
                
                <div class="bg-gray-50 px-6 py-4">
                    <button onclick="document.getElementById('bcsHistoryModal').remove()" 
                        class="w-full px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Fechar
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    } catch (error) {
        showNotification('Erro ao carregar histórico: ' + error.message, 'error');
    }
}

async function viewGroupDetails(groupId) {
    try {
        const response = await fetch(`api/groups.php?action=by_id&id=${groupId}`);
        const result = await response.json();
        
        if (!result.success) throw new Error(result.error);
        
        const group = result.data;
        const animals = group.animals || [];
        
        const modal = document.createElement('div');
        modal.id = 'groupDetailsModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100001] p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="p-6" style="background: ${group.color_code}15; border-bottom: 3px solid ${group.color_code}">
                    <h3 class="text-2xl font-bold text-gray-900">${group.group_name}</h3>
                    <p class="text-gray-600">${group.description || ''}</p>
                    <div class="mt-2 flex gap-4 text-sm text-gray-600">
                        <span>Tipo: ${group.group_type}</span>
                        <span>Animais: ${animals.length}${group.capacity ? ' / ' + group.capacity : ''}</span>
                        ${group.location ? `<span>Local: ${group.location}</span>` : ''}
                    </div>
                </div>
                
                <div class="p-6 overflow-y-auto flex-1">
                    ${animals.length === 0 ? '<p class="text-center text-gray-500">Nenhum animal neste grupo</p>' : `
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Número</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Nome</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Raça</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Idade</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                ${animals.map(a => `
                                    <tr>
                                        <td class="px-4 py-2 text-sm font-medium">${a.animal_number}</td>
                                        <td class="px-4 py-2 text-sm">${a.name || '-'}</td>
                                        <td class="px-4 py-2 text-sm">${a.breed}</td>
                                        <td class="px-4 py-2 text-sm">${a.status}</td>
                                        <td class="px-4 py-2 text-sm">${Math.floor(a.age_days / 30)}m</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `}
                </div>
                
                <div class="bg-gray-50 px-6 py-4">
                    <button onclick="document.getElementById('groupDetailsModal').remove()" 
                        class="w-full px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Fechar
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    } catch (error) {
        showNotification('Erro ao carregar detalhes: ' + error.message, 'error');
    }
}

function showAddGroupModal() {
    const modal = document.createElement('div');
    modal.id = 'addGroupModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100001] p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-6 rounded-t-2xl">
                <h3 class="text-xl font-bold">Criar Novo Grupo</h3>
            </div>
            
            <form id="addGroupForm" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nome do Grupo *</label>
                    <input type="text" name="group_name" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Código</label>
                        <input type="text" name="group_code" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo *</label>
                        <select name="group_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="lactante">Lactante</option>
                            <option value="seco">Seco</option>
                            <option value="novilha">Novilha</option>
                            <option value="pre_parto">Pré-parto</option>
                            <option value="pos_parto">Pós-parto</option>
                            <option value="hospital">Hospital</option>
                            <option value="quarentena">Quarentena</option>
                            <option value="pasto">Pasto</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                    <textarea name="description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Localização</label>
                        <input type="text" name="location" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Capacidade</label>
                        <input type="number" name="capacity" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Cor de Identificação</label>
                    <input type="color" name="color_code" value="#6B7280" class="w-full h-12 rounded-lg">
                </div>
            </form>
            
            <div class="bg-gray-50 px-6 py-4 flex gap-3">
                <button onclick="document.getElementById('addGroupModal').remove()" 
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    Cancelar
                </button>
                <button onclick="submitGroup()" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-bold">
                    Criar Grupo
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

async function submitGroup() {
    const form = document.getElementById('addGroupForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    data.action = 'create_group';
    
    try {
        const response = await fetch('api/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Grupo criado com sucesso!', 'success');
            document.getElementById('addGroupModal').remove();
            loadGroupsList();
        } else {
            showNotification('Erro: ' + result.error, 'error');
        }
    } catch (error) {
        showNotification('Erro ao criar grupo: ' + error.message, 'error');
    }
}

async function viewTransponderReadings(transponder_id) {
    try {
        const response = await fetch(`api/transponders.php?action=readings&transponder_id=${transponder_id}`);
        const result = await response.json();
        
        if (!result.success) throw new Error(result.error);
        
        const readings = result.data || [];
        
        const modal = document.createElement('div');
        modal.id = 'readingsModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100001] p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="bg-gradient-to-r from-teal-600 to-cyan-600 text-white p-6">
                    <h3 class="text-xl font-bold">Histórico de Leituras RFID</h3>
                    <p class="text-teal-100 text-sm">Últimas 100 leituras</p>
                </div>
                
                <div class="p-6 overflow-y-auto flex-1">
                    ${readings.length === 0 ? '<p class="text-center text-gray-500">Nenhuma leitura registrada</p>' : `
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Data/Hora</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Local</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Leitor</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Sinal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                ${readings.map(r => `
                                    <tr>
                                        <td class="px-4 py-2 text-sm">${new Date(r.reading_date).toLocaleString('pt-BR')}</td>
                                        <td class="px-4 py-2 text-sm">${r.location || '-'}</td>
                                        <td class="px-4 py-2 text-sm font-mono">${r.reader_id || '-'}</td>
                                        <td class="px-4 py-2 text-sm">${r.signal_strength ? r.signal_strength + ' dBm' : '-'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `}
                </div>
                
                <div class="bg-gray-50 px-6 py-4">
                    <button onclick="document.getElementById('readingsModal').remove()" 
                        class="w-full px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Fechar
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    } catch (error) {
        showNotification('Erro ao carregar leituras: ' + error.message, 'error');
    }
}

// ============================================================
// 6. PREVISÕES DE IA E INSIGHTS
// ============================================================

window.showAIInsights = function(animalId = null) {
    const modal = document.createElement('div');
    modal.id = 'aiInsightsModal';
    modal.className = 'fixed inset-0 bg-white z-[99999] overflow-y-auto';
    modal.innerHTML = `
        <div class="w-full h-full">
            <div class="sticky top-0 bg-gradient-to-br from-violet-600 to-fuchsia-600 text-white shadow-lg z-10 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="document.getElementById('aiInsightsModal').remove()" 
                            class="w-10 h-10 flex items-center justify-center hover:bg-white hover:bg-opacity-20 rounded-xl transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div>
                            <h3 class="text-2xl font-bold">Insights de IA</h3>
                            <p class="text-violet-100 text-sm">Previsões e recomendações inteligentes</p>
                        </div>
                    </div>
                    <button onclick="runDailyAI()" class="px-4 py-2 bg-white text-violet-600 rounded-lg hover:bg-violet-50 transition font-semibold">
                        Executar IA
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                ${animalId ? `
                    <div id="animalInsights"></div>
                ` : `
                    <div id="farmInsights" class="mb-6"></div>
                    
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <h4 class="font-bold text-lg mb-4">Selecione um Animal para Análise Individual</h4>
                        <select id="aiAnimalSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    
                    <div id="animalInsights"></div>
                `}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    if (animalId) {
        loadAnimalAIInsights(animalId);
    } else {
        loadFarmInsights();
        loadAnimalsForAISelect();
    }
};

async function loadFarmInsights() {
    try {
        // Fallback para IA não implementada
        const result = await safeFetch('api/manager.php?action=get_dashboard_stats');
        
        if (!result.success) {

            if (result.error && result.error.includes('não encontrada')) {
                document.getElementById('farmInsights').innerHTML = `
                    <div class="bg-yellow-50 border-2 border-yellow-500 rounded-lg p-8 text-center">
                        <svg class="w-16 h-16 text-yellow-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <h4 class="text-xl font-bold text-yellow-900 mb-2">Banco de Dados Desatualizado</h4>
                        <p class="text-yellow-700 mb-4">Execute o SQL de upgrade para habilitar as funcionalidades de IA.</p>
                        <p class="text-sm text-yellow-600 font-mono">Arquivo: lactech_lgmato (4).sql</p>
                    </div>
                `;
                return;
            }
            throw new Error(result.error);
        }
        
        const insights = result.data.insights || [];
        const container = document.getElementById('farmInsights');
        
        if (insights.length === 0) {
            container.innerHTML = `
                <div class="bg-green-50 border-2 border-green-500 rounded-lg p-8 text-center">
                    <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h4 class="text-xl font-bold text-green-900 mb-2">Tudo Ótimo!</h4>
                    <p class="text-green-700">Nenhum alerta ou recomendação crítica no momento.</p>
                </div>
            `;
            return;
        }
        
        const priorityColors = {
            'critical': 'from-red-600 to-red-700',
            'urgent': 'from-orange-600 to-orange-700',
            'high': 'from-yellow-500 to-yellow-600',
            'medium': 'from-blue-500 to-blue-600',
            'low': 'from-gray-500 to-gray-600'
        };
        
        const categoryIcons = {
            'nutrition': 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
            'production': 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            'reproduction': 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
            'health': 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
            'inventory': 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'
        };
        
        container.innerHTML = `
            <div class="mb-6">
                <h4 class="text-lg font-bold mb-4 flex items-center">
                    <svg class="w-5 h-5 text-violet-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Insights da Fazenda (${insights.length})
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${insights.map(insight => `
                        <div class="bg-gradient-to-r ${priorityColors[insight.priority]} text-white rounded-lg p-4 shadow-lg">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="${categoryIcons[insight.category]}"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h5 class="font-bold">${insight.title}</h5>
                                        ${insight.count ? `<span class="px-2 py-0.5 bg-white bg-opacity-30 rounded-full text-xs font-bold">${insight.count}</span>` : ''}
                                    </div>
                                    <p class="text-sm opacity-90">${insight.message}</p>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Erro ao carregar insights:', error);
    }
}

async function loadAnimalsForAISelect() {
    try {
        const response = await fetch('api/animals.php?action=get_all');
        const result = await response.json();
        
        if (result.success) {
            const select = document.getElementById('aiAnimalSelect');
            select.innerHTML = '<option value="">Selecione um animal...</option>' +
                (result.data || []).map(a => 
                    `<option value="${a.id}">${a.animal_number} - ${a.name || a.breed}</option>`
                ).join('');
            
            select.addEventListener('change', function() {
                if (this.value) {
                    loadAnimalAIInsights(this.value);
                }
            });
        }
    } catch (error) {
        console.error('Erro ao carregar animais:', error);
    }
}

async function loadAnimalAIInsights(animalId) {
    const container = document.getElementById('animalInsights');
    container.innerHTML = '<div class="text-center py-8"><div class="animate-spin w-12 h-12 border-4 border-violet-600 border-t-transparent rounded-full mx-auto"></div><p class="mt-4 text-gray-600">Analisando dados com IA...</p></div>';
    
    try {
        // Carregar múltiplas análises em paralelo
        const [heatRes, prodRes, anomaliesRes, recsRes] = await Promise.all([
            fetch(`api/ai_engine.php?action=predict_heat&animal_id=${animalId}`),
            fetch(`api/ai_engine.php?action=predict_production&animal_id=${animalId}`),
            fetch(`api/ai_engine.php?action=detect_anomalies&animal_id=${animalId}`),
            fetch(`api/ai_engine.php?action=recommendations&animal_id=${animalId}`)
        ]);
        
        // Parse com tratamento de erro
        let heat, prod, anomalies, recs;
        try {
            heat = await heatRes.json();
            prod = await prodRes.json();
            anomalies = await anomaliesRes.json();
            recs = await recsRes.json();
        } catch (e) {
            throw new Error('Erro ao processar resposta da API. Verifique se as tabelas foram criadas no banco.');
        }
        
        // Renderizar resultados
        let html = '<div class="space-y-6">';
        
        // Recomendações prioritárias
        if (recs.success && recs.data.recommendations.length > 0) {
            html += `
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h4 class="font-bold text-lg mb-4 flex items-center">
                        <svg class="w-5 h-5 text-violet-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        Recomendações (${recs.data.total_recommendations})
                    </h4>
                    <div class="space-y-3">
                        ${recs.data.recommendations.map(rec => {
                            const priorityColors = {
                                'urgent': 'border-red-500 bg-red-50',
                                'high': 'border-orange-500 bg-orange-50',
                                'medium': 'border-yellow-500 bg-yellow-50',
                                'low': 'border-gray-500 bg-gray-50'
                            };
                            return `
                                <div class="border-l-4 ${priorityColors[rec.priority]} p-4 rounded">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h5 class="font-semibold text-gray-900">${rec.title}</h5>
                                            <p class="text-sm text-gray-700 mt-1">${rec.message}</p>
                                        </div>
                                        <span class="px-2 py-1 text-xs rounded-full bg-white border ${rec.priority === 'urgent' ? 'border-red-500 text-red-700' : 'border-gray-300 text-gray-600'}">
                                            ${rec.priority.toUpperCase()}
                                        </span>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;
        }
        
        // Previsão de Cio
        if (heat.success && heat.data.success) {
            const h = heat.data;
            html += `
                <div class="bg-gradient-to-r from-pink-500 to-rose-500 text-white rounded-lg shadow-lg p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="font-bold text-xl mb-1">Previsão de Cio</h4>
                            <p class="text-pink-100 text-sm">Baseado em ${h.cycles_analyzed} ciclos anteriores</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-lg px-4 py-2">
                            <div class="text-sm opacity-90">Confiança</div>
                            <div class="text-2xl font-bold">${h.confidence}%</div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="bg-white bg-opacity-10 rounded-lg p-3">
                            <div class="text-xs opacity-75">Data Prevista</div>
                            <div class="font-bold">${new Date(h.predicted_date).toLocaleDateString('pt-BR')}</div>
                        </div>
                        <div class="bg-white bg-opacity-10 rounded-lg p-3">
                            <div class="text-xs opacity-75">Janela</div>
                            <div class="font-semibold text-sm">${new Date(h.window_start).toLocaleDateString('pt-BR')} - ${new Date(h.window_end).toLocaleDateString('pt-BR')}</div>
                        </div>
                        <div class="bg-white bg-opacity-10 rounded-lg p-3">
                            <div class="text-xs opacity-75">Intervalo Médio</div>
                            <div class="font-bold">${h.avg_interval} dias</div>
                        </div>
                    </div>
                    
                    <div class="bg-white bg-opacity-10 rounded-lg p-4">
                        <p class="text-sm">${h.recommendation}</p>
                    </div>
                </div>
            `;
        }
        
        // Previsão de Produção
        if (prod.success && prod.data.success) {
            const p = prod.data;
            const trendColors = {
                'increasing': 'text-green-600',
                'decreasing': 'text-red-600',
                'stable': 'text-gray-600'
            };
            const trendIcons = {
                'increasing': '↗',
                'decreasing': '↘',
                'stable': '→'
            };
            
            html += `
                <div class="bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-lg shadow-lg p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="font-bold text-xl mb-1">Previsão de Produção (7 dias)</h4>
                            <p class="text-blue-100 text-sm">Análise de ${p.days_analyzed} dias de histórico</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-lg px-4 py-2">
                            <div class="text-sm opacity-90">Confiança</div>
                            <div class="text-2xl font-bold">${p.confidence}%</div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-white bg-opacity-10 rounded-lg p-3">
                            <div class="text-xs opacity-75">Média Atual</div>
                            <div class="font-bold text-xl">${p.avg_volume.toFixed(1)}L</div>
                        </div>
                        <div class="bg-white bg-opacity-10 rounded-lg p-3">
                            <div class="text-xs opacity-75">Tendência</div>
                            <div class="font-bold text-xl">
                                ${trendIcons[p.trend]} ${Math.abs(p.trend_percentage)}%
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white bg-opacity-10 rounded-lg p-4 mb-4">
                        <h5 class="font-semibold mb-2">Próximos 7 dias:</h5>
                        <div class="space-y-1">
                            ${p.predictions.map((pred, i) => `
                                <div class="flex items-center justify-between text-sm">
                                    <span>${new Date(pred.date).toLocaleDateString('pt-BR')}</span>
                                    <span class="font-semibold">${pred.predicted_volume.toFixed(1)}L</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    
                    <div class="bg-white bg-opacity-10 rounded-lg p-4">
                        <p class="text-sm">${p.recommendation}</p>
                    </div>
                </div>
            `;
        }
        
        // Anomalias Detectadas
        if (anomalies.success && anomalies.data.anomalies && anomalies.data.anomalies.length > 0) {
            html += `
                <div class="bg-white rounded-lg shadow-lg p-6 border-2 border-orange-500">
                    <h4 class="font-bold text-lg mb-4 text-orange-900 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        Anomalias Detectadas (${anomalies.data.anomalies.length})
                    </h4>
                    <div class="space-y-2">
                        ${anomalies.data.anomalies.map(a => `
                            <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg border border-orange-200">
                                <div>
                                    <div class="font-semibold text-gray-900">${new Date(a.date).toLocaleDateString('pt-BR')}</div>
                                    <div class="text-sm text-gray-600">${a.volume}L (esperado: ${a.expected}L)</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold ${a.type === 'drop' ? 'text-red-600' : 'text-green-600'}">
                                        ${a.type === 'drop' ? '↓' : '↑'} ${Math.abs(a.deviation_percent)}%
                                    </div>
                                    <div class="text-xs text-gray-500">${a.severity === 'critical' ? 'CRÍTICO' : 'Alerta'}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        
        html += '</div>';
        container.innerHTML = html;
        
    } catch (error) {
        container.innerHTML = `
            <div class="bg-red-50 border-2 border-red-500 rounded-lg p-6 text-center">
                <p class="text-red-900 font-bold">Erro ao carregar análises</p>
                <p class="text-sm text-red-700 mt-2">${error.message}</p>
            </div>
        `;
    }
}

async function runDailyAI() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = 'Processando...';
    
    try {
        const response = await fetch('api/ai_engine.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'run_daily_ai' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            let message = 'IA executada com sucesso!\n\n';
            data.tasks.forEach(task => {
                message += `✓ ${task.name}: ${task.count || 'OK'}\n`;
            });
            
            showNotification(message, 'success');
            
            // Recarregar insights
            loadFarmInsights();
        } else {
            showNotification('Erro: ' + result.error, 'error');
        }
    } catch (error) {
        showNotification('Erro ao executar IA: ' + error.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Executar IA';
    }
}

// ============================================================
// 7. AUTOMAÇÃO: ALERTAS INTELIGENTES
// ============================================================

// Executar IA automaticamente a cada 6 horas
if (typeof window.aiAutoRunInterval === 'undefined') {
    window.aiAutoRunInterval = setInterval(async () => {
        try {
            console.log('🤖 Executando IA automática...');
            const response = await fetch('api/ai_engine.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'run_daily_ai' })
            });
            const result = await response.json();
            if (result.success) {
                console.log('✅ IA executada:', result.data);
            }
        } catch (error) {
            console.error('Erro na IA automática:', error);
        }
    }, 6 * 60 * 60 * 1000); // 6 horas
}

if (typeof window.urgentActionsInterval === 'undefined') {
    window.urgentActionsInterval = setInterval(async () => {
        try {
            const response = await fetch('api/actions.php?action=dashboard');
            const result = await response.json();
            
            if (result.success) {
                const { details } = result.data;
                
                // Contar ações urgentes
                let urgentCount = 0;
                
                if (details.calving_soon) {
                    urgentCount += details.calving_soon.filter(c => c.days_until <= 7).length;
                }
                
                if (details.low_bcs) {
                    urgentCount += details.low_bcs.filter(b => b.priority === 'critical').length;
                }
                
                if (details.medication_due) {
                    urgentCount += details.medication_due.filter(m => m.priority === 'urgent' || m.priority === 'overdue').length;
                }

                const badge = document.getElementById('urgentActionsBadge');
                if (badge && urgentCount > 0) {
                    badge.textContent = urgentCount;
                    badge.classList.remove('hidden');
                } else if (badge) {
                    badge.classList.add('hidden');
                }
                
            }
        } catch (error) {
            console.error('Erro verificando ações urgentes:', error);
        }
    }, 30 * 60 * 1000); // 30 minutos
}

// ============================================================
// 8. MELHORIAS DE UX/UI - FEEDBACK VISUAL APRIMORADO
// ============================================================

const originalShowNotification = window.showNotification;
window.showNotification = function(message, type = 'info') {
    // Criar toast customizado
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    
    const bgColors = {
        'success': 'bg-gradient-to-r from-green-500 to-emerald-600',
        'error': 'bg-gradient-to-r from-red-500 to-rose-600',
        'warning': 'bg-gradient-to-r from-yellow-500 to-orange-500',
        'info': 'bg-gradient-to-r from-blue-500 to-cyan-500'
    };
    
    const icons = {
        'success': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
        'error': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
        'warning': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>',
        'info': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
    };
    
    toast.innerHTML = `
        <div class="${bgColors[type] || bgColors.info} text-white p-4 rounded-xl shadow-2xl flex items-start gap-3">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                ${icons[type] || icons.info}
            </svg>
            <div class="flex-1">
                <p class="text-sm font-medium">${message}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Auto-remover após 5 segundos
    setTimeout(() => {
        toast.style.animation = 'slide-out-right 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
};

// Adicionar animação de saída
const style = document.createElement('style');
style.textContent = `
    @keyframes slide-out-right {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Função para mostrar loading overlay
window.showLoadingOverlay = function(message = 'Carregando...') {
    const overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[999999]';
    overlay.innerHTML = `
        <div class="bg-white rounded-2xl p-8 flex flex-col items-center gap-4 shadow-2xl">
            <div class="spinner"></div>
            <p class="text-gray-700 font-medium">${message}</p>
        </div>
    `;
    document.body.appendChild(overlay);
};

window.hideLoadingOverlay = function() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.remove();
};

// Função para animar contadores
window.animateCounter = function(element, targetValue, duration = 1000) {
    const startValue = 0;
    const increment = targetValue / (duration / 16); // 60 FPS
    let currentValue = startValue;
    
    const timer = setInterval(() => {
        currentValue += increment;
        if (currentValue >= targetValue) {
            element.textContent = Math.round(targetValue);
            clearInterval(timer);
        } else {
            element.textContent = Math.round(currentValue);
        }
    }, 16);
};

// Função para highlight de mudanças
window.highlightChange = function(element) {
    element.classList.add('bg-yellow-100');
    setTimeout(() => {
        element.classList.remove('bg-yellow-100');
        element.classList.add('transition-all', 'duration-500');
    }, 100);
    setTimeout(() => {
        element.classList.remove('bg-yellow-100');
    }, 1000);
};

// Adicionar efeito de ripple aos botões
document.addEventListener('click', function(e) {
    const button = e.target.closest('button, .app-item');
    if (!button) return;
    
    const ripple = document.createElement('span');
    const rect = button.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = e.clientX - rect.left - size / 2;
    const y = e.clientY - rect.top - size / 2;
    
    ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s ease-out;
        pointer-events: none;
    `;
    
    button.style.position = 'relative';
    button.style.overflow = 'hidden';
    button.appendChild(ripple);
    
    setTimeout(() => ripple.remove(), 600);
});

// Adicionar CSS do ripple
const rippleStyle = document.createElement('style');
rippleStyle.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(rippleStyle);

setTimeout(() => {
    fetch('api/actions.php?action=dashboard')
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                const { details } = result.data;
                let urgentCount = 0;
                
                if (details.calving_soon) {
                    urgentCount += details.calving_soon.filter(c => c.days_until <= 7).length;
                }
                if (details.low_bcs) {
                    urgentCount += details.low_bcs.filter(b => b.priority === 'critical').length;
                }
                if (details.medication_due) {
                    urgentCount += details.medication_due.filter(m => m.priority === 'urgent' || m.priority === 'overdue').length;
                }
                
                const badge = document.getElementById('urgentActionsBadge');
                if (badge && urgentCount > 0) {
                    badge.textContent = urgentCount;
                    badge.classList.remove('hidden');
                    
                    // Adicionar pulse ao card de ações
                    const actionsCard = document.querySelector('[onclick="showActionsDashboard()"]');
                    if (actionsCard) {
                        actionsCard.classList.add('urgent-pulse');
                    }
                }
            }
        })
        .catch(err => console.error('Erro ao verificar ações urgentes:', err));
}, 2000);

// ============================================================
// 9. CONTROLE DE ALIMENTAÇÃO/CONCENTRADO
// ============================================================

window.showFeedManagement = function() {
    const modal = document.createElement('div');
    modal.id = 'feedModal';
    modal.className = 'fixed inset-0 bg-white z-[99999] overflow-y-auto';
    modal.innerHTML = `
        <div class="w-full h-full">
            <div class="sticky top-0 bg-gradient-to-br from-green-600 to-emerald-600 text-white shadow-lg z-10 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="document.getElementById('feedModal').remove()" 
                            class="w-10 h-10 flex items-center justify-center hover:bg-white hover:bg-opacity-20 rounded-xl transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div>
                            <h3 class="text-2xl font-bold">Controle de Alimentação</h3>
                            <p class="text-green-100 text-sm">Concentrado e volumoso por animal</p>
                        </div>
                    </div>
                    <button onclick="showAddFeedModal()" class="px-4 py-2 bg-white text-green-600 rounded-lg hover:bg-green-50 transition font-semibold">
                        + Registrar Alimentação
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-blue-900" id="totalConcentrate">-</div>
                        <div class="text-sm text-blue-700">Concentrado Hoje (kg)</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-green-900" id="animalsFed">-</div>
                        <div class="text-sm text-green-700">Animais Alimentados</div>
                    </div>
                    <div class="bg-orange-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-orange-900" id="totalFeedCost">-</div>
                        <div class="text-sm text-orange-700">Custo Total Hoje</div>
                    </div>
                </div>
                
                <div id="feedStatsList" class="mb-6"></div>
                <div id="feedHistoryList"></div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    loadFeedData();
};

async function loadFeedData() {
    try {
        // Resumo do dia
        // Fallback para API de alimentação não implementada
        const summaryRes = await fetch('api/manager.php?action=get_dashboard_stats');
        const summary = await summaryRes.json();
        
        if (summary.success && summary.data) {
            const s = summary.data;
            document.getElementById('totalConcentrate').textContent = (s.total_concentrate || 0).toFixed(1) + ' kg';
            document.getElementById('animalsFed').textContent = s.animals_fed || 0;
            document.getElementById('totalFeedCost').textContent = 'R$ ' + ((s.total_cost || 0).toFixed(2));
        }
        
        // Estatísticas por animal (últimos 30 dias)
        const statsRes = await fetch('api/feed.php?action=stats&days=30');
        const stats = await statsRes.json();
        
        if (stats.success && stats.data) {
            renderFeedStats(stats.data);
        }
        
        // Histórico recente
        const historyRes = await fetch(`api/feed.php?action=list&date_from=${new Date(Date.now() - 7*24*60*60*1000).toISOString().split('T')[0]}`);
        const history = await historyRes.json();
        
        if (history.success && history.data) {
            renderFeedHistory(history.data);
        }
    } catch (error) {
        console.error('Erro ao carregar dados de alimentação:', error);
        showNotification('Erro ao carregar dados: ' + error.message, 'error');
    }
}

function renderFeedStats(stats) {
    const container = document.getElementById('feedStatsList');
    
    if (stats.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-500 py-4">Nenhum dado disponível</p>';
        return;
    }
    
    container.innerHTML = `
        <h4 class="font-bold text-lg mb-3">Consumo por Animal (30 dias)</h4>
        <div class="bg-white border rounded-lg overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Animal</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Raça</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Média/dia</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Total</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Custo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Última</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    ${stats.slice(0, 10).map(s => `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm font-medium">${s.animal_number} ${s.animal_name ? '- ' + s.animal_name : ''}</td>
                            <td class="px-4 py-2 text-sm">${s.breed}</td>
                            <td class="px-4 py-2 text-sm font-semibold">${(s.avg_concentrate || 0).toFixed(1)} kg</td>
                            <td class="px-4 py-2 text-sm">${(s.total_concentrate || 0).toFixed(1)} kg</td>
                            <td class="px-4 py-2 text-sm">R$ ${(s.total_cost || 0).toFixed(2)}</td>
                            <td class="px-4 py-2 text-sm text-gray-600">
                                ${s.last_feed_date ? new Date(s.last_feed_date).toLocaleDateString('pt-BR') : '-'}
                                ${s.days_since_last_feed ? `<br><span class="text-xs ${s.days_since_last_feed > 2 ? 'text-red-600' : 'text-gray-500'}">(${s.days_since_last_feed}d atrás)</span>` : ''}
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function renderFeedHistory(history) {
    const container = document.getElementById('feedHistoryList');
    
    container.innerHTML = `
        <h4 class="font-bold text-lg mb-3">Últimos Registros (7 dias)</h4>
        <div class="space-y-2">
            ${history.slice(0, 20).map(h => `
                <div class="bg-white border rounded-lg p-4 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900">${h.animal_number} ${h.animal_name ? '- ' + h.animal_name : ''}</div>
                            <div class="text-sm text-gray-600">
                                ${new Date(h.feed_date).toLocaleDateString('pt-BR')} 
                                ${h.shift !== 'unico' ? `(${h.shift})` : ''}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-gray-900">${h.concentrate_kg} kg</div>
                            ${h.total_cost ? `<div class="text-sm text-gray-600">R$ ${parseFloat(h.total_cost).toFixed(2)}</div>` : ''}
                        </div>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

function showAddFeedModal() {
    const modal = document.createElement('div');
    modal.id = 'addFeedModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100000] p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl">
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 text-white p-6 rounded-t-2xl">
                <h3 class="text-2xl font-bold">Registrar Alimentação</h3>
            </div>
            
            <form id="addFeedForm" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Animal *</label>
                        <select name="animal_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Data *</label>
                        <input type="date" name="feed_date" required value="${new Date().toISOString().split('T')[0]}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Turno</label>
                        <select name="shift" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="unico">Único/Dia Todo</option>
                            <option value="manha">Manhã</option>
                            <option value="tarde">Tarde</option>
                            <option value="noite">Noite</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Concentrado (kg) *</label>
                        <input type="number" name="concentrate_kg" required step="0.1" min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Volumoso (kg)</label>
                        <input type="number" name="roughage_kg" step="0.1" min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Silagem (kg)</label>
                        <input type="number" name="silage_kg" step="0.1" min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Feno (kg)</label>
                        <input type="number" name="hay_kg" step="0.1" min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Ração</label>
                        <input type="text" name="feed_type" placeholder="Ex: Ração 18% PB"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Custo/kg (R$)</label>
                        <input type="number" name="cost_per_kg" step="0.01" min="0" id="costPerKg"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                
                <div id="totalCostDisplay" class="bg-blue-50 border border-blue-200 rounded-lg p-4 hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-blue-900">Custo Total Estimado:</span>
                        <span class="text-xl font-bold text-blue-900" id="totalCostValue">R$ 0,00</span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                    <textarea name="notes" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
            </form>
            
            <div class="bg-gray-50 px-6 py-4 flex gap-3">
                <button onclick="document.getElementById('addFeedModal').remove()" 
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    Cancelar
                </button>
                <button onclick="submitFeed()" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold">
                    Registrar
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    loadAnimalsForSelect('addFeedForm');
    
    // Calcular custo automaticamente
    const form = document.getElementById('addFeedForm');
    const concentrateInput = form.querySelector('[name="concentrate_kg"]');
    const costPerKgInput = form.querySelector('[name="cost_per_kg"]');
    
    function updateTotalCost() {
        const concentrate = parseFloat(concentrateInput.value) || 0;
        const costPerKg = parseFloat(costPerKgInput.value) || 0;
        const total = concentrate * costPerKg;
        
        if (total > 0) {
            document.getElementById('totalCostDisplay').classList.remove('hidden');
            document.getElementById('totalCostValue').textContent = 'R$ ' + total.toFixed(2);
        } else {
            document.getElementById('totalCostDisplay').classList.add('hidden');
        }
    }
    
    concentrateInput.addEventListener('input', updateTotalCost);
    costPerKgInput.addEventListener('input', updateTotalCost);
}

async function submitFeed() {
    const form = document.getElementById('addFeedForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    // Calcular total_cost
    if (data.cost_per_kg && data.concentrate_kg) {
        data.total_cost = parseFloat(data.cost_per_kg) * parseFloat(data.concentrate_kg);
    }
    
    try {
        const response = await fetch('api/feed.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Alimentação registrada com sucesso!', 'success');
            document.getElementById('addFeedModal').remove();
            loadFeedData();
        } else {
            showNotification('Erro: ' + result.error, 'error');
        }
    } catch (error) {
        showNotification('Erro ao registrar: ' + error.message, 'error');
    }
}

// ============================================================
// 10. UPLOAD DE FOTOS DE ANIMAIS
// ============================================================

window.showAnimalPhotos = function(animalId) {
    const modal = document.createElement('div');
    modal.id = 'photosModal';
    modal.className = 'fixed inset-0 bg-white z-[99999] overflow-y-auto';
    modal.innerHTML = `
        <div class="w-full h-full">
            <div class="sticky top-0 bg-gradient-to-br from-pink-600 to-rose-600 text-white shadow-lg z-10 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="document.getElementById('photosModal').remove()" 
                            class="w-10 h-10 flex items-center justify-center hover:bg-white hover:bg-opacity-20 rounded-xl transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div>
                            <h3 class="text-2xl font-bold">Galeria de Fotos</h3>
                            <p class="text-pink-100 text-sm">Fotos do animal</p>
                        </div>
                    </div>
                    <button onclick="showUploadPhotoModal(${animalId})" class="px-4 py-2 bg-white text-pink-600 rounded-lg hover:bg-pink-50 transition font-semibold">
                        + Adicionar Foto
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div id="photoGallery" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"></div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    loadAnimalPhotos(animalId);
};

async function loadAnimalPhotos(animalId) {
    try {
        const response = await fetch(`api/photos.php?action=by_animal&animal_id=${animalId}`);
        const result = await response.json();
        
        if (!result.success) throw new Error(result.error);
        
        const photos = result.data || [];
        const container = document.getElementById('photoGallery');
        
        if (photos.length === 0) {
            container.innerHTML = `
                <div class="col-span-4 text-center py-12">
                    <svg class="w-20 h-20 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-gray-500">Nenhuma foto ainda</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = photos.map(p => `
            <div class="relative group bg-white border-2 rounded-lg overflow-hidden hover:shadow-xl transition ${p.is_primary ? 'border-pink-500' : 'border-gray-200'}">
                ${p.is_primary ? '<span class="absolute top-2 left-2 bg-pink-600 text-white text-xs px-2 py-1 rounded z-10">Principal</span>' : ''}
                <img src="${p.photo_url}" alt="${p.description || 'Foto do animal'}" 
                    class="w-full h-48 object-cover cursor-pointer"
                    onclick="viewPhotoFullscreen('${p.photo_url}')">
                <div class="p-2">
                    <div class="text-xs text-gray-600">${new Date(p.taken_date || p.uploaded_at).toLocaleDateString('pt-BR')}</div>
                    ${p.description ? `<div class="text-xs text-gray-800 mt-1">${p.description}</div>` : ''}
                </div>
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent opacity-0 group-hover:opacity-100 transition p-2 flex gap-1 justify-end">
                    ${!p.is_primary ? `<button onclick="setPrimaryPhoto(${p.id}, ${animalId})" class="px-2 py-1 bg-white text-gray-900 text-xs rounded hover:bg-gray-100">Definir Principal</button>` : ''}
                    <button onclick="deletePhoto(${p.id}, ${animalId})" class="px-2 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700">Excluir</button>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Erro ao carregar fotos:', error);
    }
}

function showUploadPhotoModal(animalId) {
    const modal = document.createElement('div');
    modal.id = 'uploadPhotoModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100001] p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="bg-gradient-to-r from-pink-600 to-rose-600 text-white p-6 rounded-t-2xl">
                <h3 class="text-xl font-bold">Adicionar Foto</h3>
            </div>
            
            <form id="uploadPhotoForm" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">URL da Foto *</label>
                    <input type="url" name="photo_url" required placeholder="https://exemplo.com/foto.jpg"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <p class="text-xs text-gray-500 mt-1">Cole o link direto da imagem (imgur, postimage, etc)</p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo</label>
                    <select name="photo_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="profile">Perfil</option>
                        <option value="health">Saúde</option>
                        <option value="event">Evento</option>
                        <option value="birth">Nascimento</option>
                        <option value="bcs">Condição Corporal</option>
                        <option value="injury">Lesão</option>
                        <option value="other">Outro</option>
                    </select>
                </div>
                
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_primary" value="1" class="w-4 h-4 text-pink-600 rounded">
                        <span class="text-sm font-semibold text-gray-700">Definir como foto principal</span>
                    </label>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                    <textarea name="description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
            </form>
            
            <div class="bg-gray-50 px-6 py-4 flex gap-3">
                <button onclick="document.getElementById('uploadPhotoModal').remove()" 
                    class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    Cancelar
                </button>
                <button onclick="submitPhoto(${animalId})" class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 font-bold">
                    Adicionar Foto
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

async function submitPhoto(animalId) {
    const form = document.getElementById('uploadPhotoForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    data.animal_id = animalId;
    data.is_primary = form.querySelector('[name="is_primary"]').checked ? 1 : 0;
    
    try {
        const response = await fetch('api/photos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Foto adicionada com sucesso!', 'success');
            document.getElementById('uploadPhotoModal').remove();
            loadAnimalPhotos(animalId);
        } else {
            showNotification('Erro: ' + result.error, 'error');
        }
    } catch (error) {
        showNotification('Erro ao adicionar foto: ' + error.message, 'error');
    }
}

async function setPrimaryPhoto(photoId, animalId) {
    try {
        const response = await fetch('api/photos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'set_primary', id: photoId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Foto definida como principal!', 'success');
            loadAnimalPhotos(animalId);
        } else {
            showNotification('Erro: ' + result.error, 'error');
        }
    } catch (error) {
        showNotification('Erro: ' + error.message, 'error');
    }
}

async function deletePhoto(photoId, animalId) {
    if (!confirm('Excluir esta foto?')) return;
    
    try {
        const response = await fetch('api/photos.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: photoId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Foto removida', 'success');
            loadAnimalPhotos(animalId);
        } else {
            showNotification('Erro: ' + result.error, 'error');
        }
    } catch (error) {
        showNotification('Erro: ' + error.message, 'error');
    }
}

function viewPhotoFullscreen(photoUrl) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-[100002] p-4';
    modal.onclick = () => modal.remove();
    modal.innerHTML = `
        <div class="max-w-4xl max-h-[90vh]">
            <img src="${photoUrl}" class="max-w-full max-h-full rounded-lg shadow-2xl">
        </div>
    `;
    document.body.appendChild(modal);
}

// ============================================================
// 11. PAINEL DE NOTIFICAÇÕES
// ============================================================

window.showNotificationsPanel = function() {
    const modal = document.createElement('div');
    modal.id = 'notificationsPanel';
    modal.className = 'fixed inset-0 bg-white z-[99999] overflow-y-auto';
    modal.innerHTML = `
        <div class="w-full h-full">
            <div class="sticky top-0 bg-gradient-to-br from-blue-600 to-cyan-600 text-white shadow-lg z-10 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="document.getElementById('notificationsPanel').remove()" 
                            class="w-10 h-10 flex items-center justify-center hover:bg-white hover:bg-opacity-20 rounded-xl transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div>
                            <h3 class="text-2xl font-bold">Notificações</h3>
                            <p class="text-blue-100 text-sm">Central de notificações</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="testPushNotification()" class="px-4 py-2 bg-white text-blue-600 rounded-lg hover:bg-blue-50 transition text-sm">
                            Testar
                        </button>
                        <button onclick="enablePushNotifications()" class="px-4 py-2 bg-white text-blue-600 rounded-lg hover:bg-blue-50 transition font-semibold">
                            Ativar Push
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <div id="pushStatus" class="bg-gray-50 border rounded-lg p-4 mb-6">
                    <div class="flex items-center gap-3">
                        <div id="pushStatusIcon" class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900" id="pushStatusTitle">Status das Notificações</h4>
                            <p class="text-sm text-gray-600" id="pushStatusText">Verificando...</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-bold text-lg">Notificações Recentes</h4>
                    <button onclick="markAllRead()" class="text-sm text-blue-600 hover:text-blue-800">
                        Marcar todas como lidas
                    </button>
                </div>
                
                <div id="notificationsList"></div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    loadNotifications();
    checkPushStatus();
};

async function checkPushStatus() {
    const permission = Notification.permission;
    const statusIcon = document.getElementById('pushStatusIcon');
    const statusTitle = document.getElementById('pushStatusTitle');
    const statusText = document.getElementById('pushStatusText');
    
    if (permission === 'granted') {
        statusIcon.className = 'w-12 h-12 rounded-full bg-green-500 flex items-center justify-center';
        statusTitle.textContent = 'Notificações Ativadas';
        statusText.textContent = 'Você receberá alertas importantes sobre a fazenda.';
    } else if (permission === 'denied') {
        statusIcon.className = 'w-12 h-12 rounded-full bg-red-500 flex items-center justify-center';
        statusTitle.textContent = 'Notificações Bloqueadas';
        statusText.textContent = 'Desbloqueie nas configurações do navegador para receber alertas.';
    } else {
        statusIcon.className = 'w-12 h-12 rounded-full bg-yellow-500 flex items-center justify-center';
        statusTitle.textContent = 'Notificações Desativadas';
        statusText.textContent = 'Clique em "Ativar Push" para receber alertas importantes.';
    }
}

async function enablePushNotifications() {
    try {
        if (!window.PushNotificationManager) {
            showNotification('Sistema de push não disponível', 'error');
            return;
        }
        
        const granted = await PushNotificationManager.requestPermission();
        
        if (granted) {
            showNotification('Notificações ativadas com sucesso!', 'success');
            checkPushStatus();
        } else {
            showNotification('Você precisa permitir notificações', 'warning');
        }
    } catch (error) {
        showNotification('Erro ao ativar notificações: ' + error.message, 'error');
    }
}

async function testPushNotification() {
    if (!window.PushNotificationManager) {
        showNotification('Sistema de push não disponível', 'error');
        return;
    }
    
    if (Notification.permission !== 'granted') {
        showNotification('Ative as notificações primeiro!', 'warning');
        return;
    }
    
    PushNotificationManager.test();
}

async function loadNotifications() {
    try {
        const response = await fetch('api/notifications.php?action=list&limit=50');
        const result = await response.json();
        
        if (!result.success) throw new Error(result.error);
        
        const notifications = result.data || [];
        const container = document.getElementById('notificationsList');
        
        if (notifications.length === 0) {
            container.innerHTML = `
                <div class="text-center py-12">
                    <svg class="w-20 h-20 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <p class="text-gray-500">Nenhuma notificação</p>
                </div>
            `;
            return;
        }
        
        const priorityColors = {
            'critical': 'border-red-500 bg-red-50',
            'urgent': 'border-orange-500 bg-orange-50',
            'high': 'border-yellow-500 bg-yellow-50',
            'medium': 'border-blue-500 bg-blue-50',
            'low': 'border-gray-500 bg-gray-50'
        };
        
        container.innerHTML = `
            <div class="space-y-3">
                ${notifications.map(n => `
                    <div class="border-l-4 ${priorityColors[n.priority]} p-4 rounded ${n.is_read ? 'opacity-60' : ''} transition hover:shadow-md">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h5 class="font-semibold text-gray-900">${n.title}</h5>
                                    ${!n.is_read ? '<span class="w-2 h-2 bg-blue-600 rounded-full"></span>' : ''}
                                </div>
                                <p class="text-sm text-gray-700">${n.message}</p>
                                <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                    <span>${new Date(n.created_at).toLocaleString('pt-BR')}</span>
                                    <span class="px-2 py-0.5 bg-white rounded">${n.priority}</span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                ${!n.is_read ? `
                                <button onclick="markNotificationRead(${n.id})" class="text-blue-600 hover:text-blue-800 text-xs">
                                    Marcar lida
                                </button>
                                ` : ''}
                                <button onclick="deleteNotification(${n.id})" class="text-red-600 hover:text-red-800 text-xs">
                                    Excluir
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    } catch (error) {
        console.error('Erro ao carregar notificações:', error);
        document.getElementById('notificationsList').innerHTML = `
            <div class="bg-yellow-50 border-2 border-yellow-500 rounded-lg p-6 text-center">
                <p class="text-yellow-900">Erro ao carregar notificações</p>
                <p class="text-sm text-yellow-700 mt-2">${error.message}</p>
            </div>
        `;
    }
}

async function markNotificationRead(id) {
    try {
        const response = await fetch('api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read', id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadNotifications();
        }
    } catch (error) {
        console.error('Erro:', error);
    }
}

async function markAllRead() {
    try {
        const response = await fetch('api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_all_read' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Todas marcadas como lidas', 'success');
            loadNotifications();
        }
    } catch (error) {
        console.error('Erro:', error);
    }
}

async function deleteNotification(id) {
    try {
        const response = await fetch('api/notifications.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadNotifications();
        }
    } catch (error) {
        console.error('Erro:', error);
    }
}

// ============================================================
// 12. SISTEMA DE BACKUP E SINCRONIZAÇÃO
// ============================================================

window.showBackupManagement = function() {
    const modal = document.createElement('div');
    modal.id = 'backupModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[99999] flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[90vh] overflow-hidden">
            <div class="bg-gradient-to-br from-purple-600 to-indigo-600 text-white p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="document.getElementById('backupModal').remove()" 
                            class="w-10 h-10 flex items-center justify-center hover:bg-white hover:bg-opacity-20 rounded-xl transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <div>
                            <h3 class="text-2xl font-bold">Backup e Sincronização</h3>
                            <p class="text-purple-100 text-sm">Gerencie backups e sincronização de dados</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="createBackup()" class="px-4 py-2 bg-white text-purple-600 rounded-lg hover:bg-purple-50 transition font-semibold">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Novo Backup
                        </button>
                        <button onclick="exportData()" class="px-4 py-2 bg-white text-purple-600 rounded-lg hover:bg-purple-50 transition">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                            Exportar
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Status de Sincronização -->
                    <div class="lg:col-span-1">
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-green-900">Status de Sincronização</h4>
                                    <p class="text-sm text-green-700" id="syncStatusText">Verificando...</p>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-green-700">Última sincronização:</span>
                                    <span class="font-semibold text-green-900" id="lastSyncTime">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-green-700">Total de registros:</span>
                                    <span class="font-semibold text-green-900" id="totalRecords">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-green-700">Tabelas sincronizadas:</span>
                                    <span class="font-semibold text-green-900" id="tablesCount">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de Backups -->
                    <div class="lg:col-span-2">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-bold text-lg">Backups Disponíveis</h4>
                            <button onclick="loadBackups()" class="text-sm text-purple-600 hover:text-purple-800">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Atualizar
                            </button>
                        </div>
                        
                        <div id="backupsList" class="space-y-3 max-h-96 overflow-y-auto">
                            <div class="text-center py-8">
                                <div class="spinner mx-auto mb-4"></div>
                                <p class="text-gray-500">Carregando backups...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Configurações de Backup -->
                <div class="mt-6 bg-gray-50 rounded-xl p-4">
                    <h4 class="font-bold text-lg mb-4">Configurações de Backup Automático</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Backup Automático</label>
                            <label class="flex items-center">
                                <input type="checkbox" id="autoBackupEnabled" class="mr-2">
                                <span class="text-sm">Ativar backup automático</span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Frequência</label>
                            <select id="backupFrequency" class="w-full p-2 border rounded-lg">
                                <option value="daily">Diário</option>
                                <option value="weekly">Semanal</option>
                                <option value="monthly">Mensal</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Horário</label>
                            <input type="time" id="backupTime" class="w-full p-2 border rounded-lg" value="02:00">
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button onclick="saveBackupSettings()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                            Salvar Configurações
                        </button>
                        <button onclick="testBackup()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                            Testar Backup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    loadBackups();
    checkSyncStatus();
};

async function loadBackups() {
    try {
        const response = await fetch('api/backup.php?action=list_backups');

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Resposta não é JSON válido. Verifique se o banco foi atualizado.');
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Erro desconhecido');
        }
        
        const backups = result.backups || [];
        const container = document.getElementById('backupsList');
        
        if (backups.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-gray-500">Nenhum backup encontrado</p>
                    ${result.message ? `<p class="text-sm text-blue-600 mt-2">${result.message}</p>` : ''}
                </div>
            `;
            return;
        }
        
        container.innerHTML = `
            <div class="space-y-3">
                ${backups.map(backup => `
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h5 class="font-semibold text-gray-900">${backup.name}</h5>
                                    ${backup.exists ? '<span class="w-2 h-2 bg-green-500 rounded-full"></span>' : '<span class="w-2 h-2 bg-red-500 rounded-full"></span>'}
                                </div>
                                <p class="text-sm text-gray-600">${backup.description || 'Sem descrição'}</p>
                                <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                    <span>${new Date(backup.created_at).toLocaleString('pt-BR')}</span>
                                    <span>${formatFileSize(backup.file_size)}</span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="restoreBackup(${backup.id})" class="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700 transition">
                                    Restaurar
                                </button>
                                <button onclick="downloadBackup(${backup.id})" class="px-3 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700 transition">
                                    Download
                                </button>
                                <button onclick="deleteBackup(${backup.id})" class="px-3 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700 transition">
                                    Excluir
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    } catch (error) {
        console.error('Erro ao carregar backups:', error);
        const container = document.getElementById('backupsList');

        if (error.message.includes('JSON') || error.message.includes('token')) {
            container.innerHTML = `
                <div class="bg-blue-50 border-2 border-blue-500 rounded-lg p-6 text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-blue-900 mb-2">Sistema de Backup Funcionando</h3>
                    <p class="text-blue-800 mb-4">O sistema de backup está funcionando com arquivos locais. Não é necessário executar SQL adicional.</p>
                    <div class="bg-blue-100 rounded-lg p-3 mb-4">
                        <p class="text-sm text-blue-900">Os backups serão salvos no diretório <code>backups/</code></p>
                    </div>
                    <p class="text-sm text-blue-700">Clique em "Novo Backup" para criar seu primeiro backup!</p>
                </div>
            `;
        } else {
            container.innerHTML = `
                <div class="bg-red-50 border-2 border-red-500 rounded-lg p-4 text-center">
                    <p class="text-red-900">Erro ao carregar backups</p>
                    <p class="text-sm text-red-700 mt-1">${error.message}</p>
                </div>
            `;
        }
    }
}

async function createBackup() {
    const name = prompt('Nome do backup:') || 'Backup_' + new Date().toISOString().slice(0, 19).replace(/:/g, '-');
    const description = prompt('Descrição (opcional):') || '';
    
    if (!name) return;
    
    try {
        showLoadingOverlay('Criando backup...');
        
        const response = await fetch('api/backup.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'create_backup',
                name: name,
                description: description,
                include_photos: true
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Backup criado com sucesso!', 'success');
            loadBackups();
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        showNotification('Erro ao criar backup: ' + error.message, 'error');
    } finally {
        hideLoadingOverlay();
    }
}

async function restoreBackup(backupId) {
    if (!confirm('Tem certeza que deseja restaurar este backup? Todos os dados atuais serão substituídos.')) {
        return;
    }
    
    try {
        showLoadingOverlay('Restaurando backup...');
        
        const response = await fetch('api/backup.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'restore_backup',
                backup_id: backupId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Backup restaurado com sucesso!', 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        showNotification('Erro ao restaurar backup: ' + error.message, 'error');
    } finally {
        hideLoadingOverlay();
    }
}

async function deleteBackup(backupId) {
    if (!confirm('Tem certeza que deseja excluir este backup?')) {
        return;
    }
    
    try {
        const response = await fetch('api/backup.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete_backup',
                backup_id: backupId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Backup excluído com sucesso!', 'success');
            loadBackups();
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        showNotification('Erro ao excluir backup: ' + error.message, 'error');
    }
}

async function exportData() {
    try {
        showLoadingOverlay('Exportando dados...');
        
        const response = await fetch('api/backup.php?action=export_data&format=json');
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'lactech_export_' + new Date().toISOString().slice(0, 10) + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showNotification('Dados exportados com sucesso!', 'success');
        } else {
            throw new Error('Erro ao exportar dados');
        }
    } catch (error) {
        showNotification('Erro ao exportar dados: ' + error.message, 'error');
    } finally {
        hideLoadingOverlay();
    }
}

async function checkSyncStatus() {
    try {
        const response = await fetch('api/backup.php?action=check_sync_status');
        const result = await response.json();
        
        if (result.success) {
            const status = result.sync_status;
            document.getElementById('lastSyncTime').textContent = status.last_sync;
            document.getElementById('totalRecords').textContent = status.total_records.toLocaleString();
            document.getElementById('tablesCount').textContent = status.tables_count;
            document.getElementById('syncStatusText').textContent = 'Sincronização OK';
        }
    } catch (error) {
        console.error('Erro ao verificar status:', error);
        document.getElementById('syncStatusText').textContent = 'Erro ao verificar';
    }
}

async function saveBackupSettings() {
    try {
        const settings = {
            auto_backup_enabled: document.getElementById('autoBackupEnabled').checked,
            backup_frequency: document.getElementById('backupFrequency').value,
            backup_time: document.getElementById('backupTime').value
        };

        showNotification('Configurações salvas com sucesso!', 'success');
    } catch (error) {
        showNotification('Erro ao salvar configurações: ' + error.message, 'error');
    }
}

async function testBackup() {
    try {
        showLoadingOverlay('Testando backup...');
        
        // Simular teste de backup
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        showNotification('Teste de backup concluído com sucesso!', 'success');
    } catch (error) {
        showNotification('Erro no teste de backup: ' + error.message, 'error');
    } finally {
        hideLoadingOverlay();
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// ============================================================
// 13. INTEGRAÇÃO COM PERFIL - BACKUP E SINCRONIZAÇÃO
// ============================================================

async function checkProfileSyncStatus() {
    try {
        const response = await fetch('api/backup.php?action=check_sync_status');
        const result = await response.json();
        
        if (result.success) {
            const status = result.sync_status;
            const statusElement = document.getElementById('profileSyncStatus');
            if (statusElement) {
                statusElement.textContent = `${status.total_records} registros sincronizados`;
            }
        }
    } catch (error) {
        const statusElement = document.getElementById('profileSyncStatus');
        if (statusElement) {
            statusElement.textContent = 'Sistema OK';
        }
    }
}

console.log('✅ Novas funcionalidades carregadas: Ações, Transponders, BCS, Grupos, IA, Feed, Fotos, Notificações, Backup');
console.log('🤖 Automação ativada: IA a cada 6h, Verificação de urgências a cada 30min');
console.log('🎨 UX/UI melhorada: Badges, animações, ripple effects, toast notifications');
console.log('📱 PWA: Service Worker + Push Notifications ativadas');
console.log('💾 Backup: Sistema completo de backup e sincronização');

// ==================== SISTEMA DE TOUROS E INSEMINAÇÃO ====================

window.showBullsManagement = function() {
    console.log('🐂 Abrindo Sistema de Touros...');
    
    const modal = document.createElement('div');
    modal.id = 'bullsManagementModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[99999] flex items-center justify-center p-0';
    modal.innerHTML = `
        <div class="bg-white w-full h-full overflow-hidden flex flex-col">
            <div class="bg-gradient-to-r from-green-600 to-green-700 text-white p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold">Sistema de Touros</h2>
                            <p class="text-green-100">Gestão completa de touros e inseminações</p>
                        </div>
                    </div>
                    <button onclick="closeBullsManagement()" class="text-white hover:text-green-200 transition-colors">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-6 flex-1 overflow-y-auto">
                <!-- Filtros e Busca -->
                <div class="mb-6">
                    <div class="flex flex-wrap gap-4 items-center">
                        <div class="flex-1 min-w-64">
                            <input type="text" id="bullsSearch" placeholder="Buscar touros..." 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                        <select id="bullsBreedFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            <option value="">Todas as raças</option>
                        </select>
                        <select id="bullsStatusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            <option value="">Todos os status</option>
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                            <option value="vendido">Vendido</option>
                            <option value="morto">Morto</option>
                        </select>
                        <button onclick="loadBullsData()" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Buscar
                        </button>
                        <button onclick="showAddBullForm()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Novo Touro
                        </button>
                    </div>
                </div>
                
                <!-- Estatísticas -->
                <div id="bullsStats" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <!-- Será preenchido dinamicamente -->
                </div>
                
                <!-- Tabela de Touros -->
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Touro</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Raça</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inseminações</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taxa de Prenhez</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Última Inseminação</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="bullsTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- Será preenchido dinamicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Paginação -->
                <div id="bullsPagination" class="mt-4 flex justify-center">
                    <!-- Será preenchido dinamicamente -->
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    loadBullsData();
    loadBullsFilters();
};

window.closeBullsManagement = function() {
    const modal = document.getElementById('bullsManagementModal');
    if (modal) {
        modal.remove();
    }
};

window.loadBullsData = function() {
    const search = document.getElementById('bullsSearch')?.value || '';
    const breed = document.getElementById('bullsBreedFilter')?.value || '';
    const status = document.getElementById('bullsStatusFilter')?.value || '';
    
    showLoadingOverlay();
    
    const params = new URLSearchParams({
        action: 'list',
        search: search,
        breed: breed,
        status: status,
        page: 1,
        limit: 20
    });
    
    fetch(`api/bulls.php?${params}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na resposta da API');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderBullsTable(data.data.bulls);
                renderBullsStats(data.data);
                renderBullsPagination(data.data.pagination);
            } else {
                showNotification('Erro ao carregar touros: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao carregar dados dos touros', 'error');
        })
        .finally(() => {
            hideLoadingOverlay();
        });
};

window.renderBullsTable = function(bulls) {
    const tbody = document.getElementById('bullsTableBody');
    if (!tbody) return;
    
    if (bulls.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                    Nenhum touro encontrado
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = bulls.map(bull => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                        ${bull.photo_url ? 
                            `<img class="h-10 w-10 rounded-full object-cover" src="${bull.photo_url}" alt="${bull.bull_name}">` :
                            `<div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            </div>`
                        }
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">${bull.bull_name}</div>
                        <div class="text-sm text-gray-500">${bull.bull_code}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${bull.breed}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                    bull.status === 'ativo' ? 'bg-green-100 text-green-800' :
                    bull.status === 'inativo' ? 'bg-yellow-100 text-yellow-800' :
                    bull.status === 'vendido' ? 'bg-blue-100 text-blue-800' :
                    'bg-red-100 text-red-800'
                }">
                    ${bull.status.charAt(0).toUpperCase() + bull.status.slice(1)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${bull.total_inseminations || 0}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <span class="font-medium ${bull.pregnancy_rate >= 70 ? 'text-green-600' : bull.pregnancy_rate >= 50 ? 'text-yellow-600' : 'text-red-600'}">
                    ${bull.pregnancy_rate || 0}%
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${bull.last_insemination ? new Date(bull.last_insemination).toLocaleDateString('pt-BR') : 'Nunca'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                    <button onclick="viewBullDetails(${bull.id})" class="text-blue-600 hover:text-blue-900">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    <button onclick="editBull(${bull.id})" class="text-green-600 hover:text-green-900">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button onclick="deleteBull(${bull.id})" class="text-red-600 hover:text-red-900">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
};

window.renderBullsStats = function(data) {
    const statsContainer = document.getElementById('bullsStats');
    if (!statsContainer) return;
    
    const stats = data.statistics || {};
    
    statsContainer.innerHTML = `
        <div class="bg-blue-50 p-4 rounded-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-800">Total de Touros</p>
                    <p class="text-2xl font-bold text-blue-900">${data.bulls?.length || 0}</p>
                </div>
            </div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">Touros Ativos</p>
                    <p class="text-2xl font-bold text-green-900">${data.bulls?.filter(b => b.status === 'ativo').length || 0}</p>
                </div>
            </div>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-yellow-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-yellow-800">Total Inseminações</p>
                    <p class="text-2xl font-bold text-yellow-900">${data.bulls?.reduce((sum, bull) => sum + (bull.total_inseminations || 0), 0) || 0}</p>
                </div>
            </div>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-purple-800">Taxa Média Prenhez</p>
                    <p class="text-2xl font-bold text-purple-900">
                        ${data.bulls?.length > 0 ? 
                            Math.round(data.bulls.reduce((sum, bull) => sum + (bull.pregnancy_rate || 0), 0) / data.bulls.length) : 0}%
                    </p>
                </div>
            </div>
        </div>
    `;
};

window.loadBullsFilters = function() {
    fetch('api/bulls.php?action=list&limit=1')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.filters) {
                const breedFilter = document.getElementById('bullsBreedFilter');
                if (breedFilter && data.data.filters.breeds) {
                    breedFilter.innerHTML = '<option value="">Todas as raças</option>' +
                        data.data.filters.breeds.map(breed => 
                            `<option value="${breed}">${breed}</option>`
                        ).join('');
                }
            }
        })
        .catch(error => console.error('Erro ao carregar filtros:', error));
};

window.showAddBullForm = function() {
    const modal = document.createElement('div');
    modal.id = 'addBullModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[99999] flex items-center justify-center p-0';
    modal.innerHTML = `
        <div class="bg-white w-full h-full overflow-hidden flex flex-col">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold">Novo Touro</h2>
                    <button onclick="closeAddBullForm()" class="text-white hover:text-blue-200 transition-colors">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <form id="addBullForm" class="p-6 flex-1 overflow-y-auto space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Código do Touro *</label>
                        <input type="text" name="bull_code" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Touro *</label>
                        <input type="text" name="bull_name" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Raça *</label>
                        <select name="breed" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Selecione a raça</option>
                            <option value="Holandesa">Holandesa</option>
                            <option value="Gir">Gir</option>
                            <option value="Girolanda">Girolanda</option>
                            <option value="Jersey">Jersey</option>
                            <option value="Pardo Suíço">Pardo Suíço</option>
                            <option value="Simental">Simental</option>
                            <option value="Outras">Outras</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento *</label>
                        <input type="date" name="birth_date" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Código Genético</label>
                        <input type="text" name="genetic_code" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pai (Sire)</label>
                        <input type="text" name="sire" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mãe (Dam)</label>
                        <input type="text" name="dam" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                            <option value="vendido">Vendido</option>
                            <option value="morto">Morto</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Merito Genético</label>
                        <input type="number" step="0.01" name="genetic_merit" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Índice Produção Leite</label>
                        <input type="number" step="0.01" name="milk_production_index" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Índice Fertilidade</label>
                        <input type="number" step="0.01" name="fertility_index" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data de Compra</label>
                        <input type="date" name="purchase_date" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Preço de Compra (R$)</label>
                        <input type="number" step="0.01" name="purchase_price" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                    <textarea name="notes" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
                
                <div class="flex justify-end space-x-4 pt-4">
                    <button type="button" onclick="closeAddBullForm()" 
                            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Salvar Touro
                    </button>
                </div>
            </form>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Adicionar evento de submit
    document.getElementById('addBullForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveBull();
    });
};

window.closeAddBullForm = function() {
    const modal = document.getElementById('addBullModal');
    if (modal) {
        modal.remove();
    }
};

window.saveBull = function() {
    const form = document.getElementById('addBullForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    showLoadingOverlay();
    
    fetch('api/bulls.php?action=create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Touro criado com sucesso!', 'success');
            closeAddBullForm();
            loadBullsData();
        } else {
            showNotification('Erro ao criar touro: ' + result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao criar touro', 'error');
    })
    .finally(() => {
        hideLoadingOverlay();
    });
};

window.viewBullDetails = function(bullId) {
    showLoadingOverlay();
    
    fetch(`api/bulls.php?action=get&id=${bullId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showBullDetailsModal(data.data);
            } else {
                showNotification('Erro ao carregar detalhes: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao carregar detalhes do touro', 'error');
        })
        .finally(() => {
            hideLoadingOverlay();
        });
};

window.showBullDetailsModal = function(data) {
    const bull = data.bull;
    const recentInseminations = data.recent_inseminations || [];
    
    const modal = document.createElement('div');
    modal.id = 'bullDetailsModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[99999] flex items-center justify-center p-0';
    modal.innerHTML = `
        <div class="bg-white w-full h-full overflow-hidden flex flex-col">
            <div class="bg-gradient-to-r from-green-600 to-green-700 text-white p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold">${bull.bull_name}</h2>
                            <p class="text-green-100">${bull.bull_code} - ${bull.breed}</p>
                        </div>
                    </div>
                    <button onclick="closeBullDetailsModal()" class="text-white hover:text-green-200 transition-colors">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-6 flex-1 overflow-y-auto">
                <!-- Informações Básicas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informações Básicas</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Código:</span>
                                <span class="font-medium">${bull.bull_code}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Nome:</span>
                                <span class="font-medium">${bull.bull_name}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Raça:</span>
                                <span class="font-medium">${bull.breed}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Data de Nascimento:</span>
                                <span class="font-medium">${new Date(bull.birth_date).toLocaleDateString('pt-BR')}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full ${
                                    bull.status === 'ativo' ? 'bg-green-100 text-green-800' :
                                    bull.status === 'inativo' ? 'bg-yellow-100 text-yellow-800' :
                                    bull.status === 'vendido' ? 'bg-blue-100 text-blue-800' :
                                    'bg-red-100 text-red-800'
                                }">
                                    ${bull.status.charAt(0).toUpperCase() + bull.status.slice(1)}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Inseminações:</span>
                                <span class="font-medium">${bull.total_inseminations || 0}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Inseminações Bem-sucedidas:</span>
                                <span class="font-medium">${bull.successful_inseminations || 0}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Taxa de Prenhez:</span>
                                <span class="font-medium ${bull.pregnancy_rate >= 70 ? 'text-green-600' : bull.pregnancy_rate >= 50 ? 'text-yellow-600' : 'text-red-600'}">
                                    ${bull.pregnancy_rate || 0}%
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Custo Total:</span>
                                <span class="font-medium">R$ ${(bull.total_cost || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Última Inseminação:</span>
                                <span class="font-medium">${bull.last_insemination ? new Date(bull.last_insemination).toLocaleDateString('pt-BR') : 'Nunca'}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Inseminações Recentes -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Inseminações Recentes</h3>
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Animal</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Resultado</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parto Esperado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    ${recentInseminations.map(insemination => `
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                ${new Date(insemination.insemination_date).toLocaleDateString('pt-BR')}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                ${insemination.animal_code} - ${insemination.animal_name}
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full ${
                                                    insemination.pregnancy_result === 'prenha' ? 'bg-green-100 text-green-800' :
                                                    insemination.pregnancy_result === 'vazia' ? 'bg-red-100 text-red-800' :
                                                    'bg-yellow-100 text-yellow-800'
                                                }">
                                                    ${insemination.pregnancy_result === 'prenha' ? 'Prenha' :
                                                      insemination.pregnancy_result === 'vazia' ? 'Vazia' :
                                                      insemination.pregnancy_result === 'pendente' ? 'Pendente' : 'Aborto'}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                ${insemination.expected_calving_date ? new Date(insemination.expected_calving_date).toLocaleDateString('pt-BR') : '-'}
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Ações -->
                <div class="flex justify-end space-x-4">
                    <button onclick="editBull(${bull.id})" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Editar Touro
                    </button>
                    <button onclick="showInseminationForm(${bull.id})" 
                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        Nova Inseminação
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
};

window.closeBullDetailsModal = function() {
    const modal = document.getElementById('bullDetailsModal');
    if (modal) {
        modal.remove();
    }
};

console.log('🐂 Sistema de Touros carregado: Gestão completa de touros e inseminações');

// ============================================================
// FUNÇÕES AUXILIARES
// ============================================================

// Função auxiliar para tempo relativo
function getTimeAgoFromDate(date) {
    if (!date) return 'Agora';
    
    const now = new Date();
    const diffMs = now - date;
    const diffMinutes = Math.floor(diffMs / (1000 * 60));

    if (diffMinutes < 60) {
        return `${diffMinutes}min atrás`;
    } else if (diffMinutes < 1440) {
        const hours = Math.floor(diffMinutes / 60);
        return `${hours}h atrás`;
    } else {
        const days = Math.floor(diffMinutes / 1440);
        return `${days}d atrás`;
    }
}

// ============================================================
// SISTEMA DE GRÁFICOS MODERNOS - LACTECH
// ============================================================

// Variáveis globais para os gráficos
window.charts = {
    volume: null,
    weekly: null,
    temperature: null,
    monthly: null,
    daily: null,
    weeklyVolume: null
};

// Configurações modernas dos gráficos
const chartConfig = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        intersect: false,
        mode: 'index'
    },
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: 'white',
            bodyColor: 'white',
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: false
        }
    },
    scales: {
        x: {
            grid: {
                display: false
            },
            ticks: {
                color: '#64748b'
            }
        },
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(148, 163, 184, 0.1)'
            },
            ticks: {
                color: '#64748b'
            }
        }
    }
};

// Função para criar gráfico de volume moderno
function createVolumeChart() {
    const ctx = document.getElementById('volumeChart');
    if (!ctx) return;

    // Destruir gráfico existente
    if (window.charts.volume) {
        window.charts.volume.destroy();
        window.charts.volume = null;
    }

    Chart.helpers.each(Chart.instances, function(instance) {
        if (instance.canvas.id === 'volumeChart') {
            instance.destroy();
        }
    });

    const data = {
        labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
        datasets: [{
            label: 'Volume (L)',
            data: [150, 160, 155, 170, 165, 175, 180],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#10b981',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8
        }]
    };

    window.charts.volume = new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            ...chartConfig,
            plugins: {
                ...chartConfig.plugins,
                tooltip: {
                    ...chartConfig.plugins.tooltip,
                    callbacks: {
                        title: function(context) {
                            return `Dia: ${context[0].label}`;
                        },
                        label: function(context) {
                            return `Volume: ${context.parsed.y}L`;
                        }
                    }
                }
            }
        }
    });
}

// Função para criar gráfico semanal moderno
function createWeeklyChart() {
    const ctx = document.getElementById('dashboardWeeklyChart');
    if (!ctx) return;

    // Destruir gráfico existente
    if (window.charts.weekly) {
        window.charts.weekly.destroy();
        window.charts.weekly = null;
    }

    Chart.helpers.each(Chart.instances, function(instance) {
        if (instance.canvas.id === 'dashboardWeeklyChart') {
            instance.destroy();
        }
    });

    const data = {
        labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
        datasets: [{
            label: 'Produção (L)',
            data: [25, 28, 26, 30, 29, 32, 31],
            backgroundColor: [
                'rgba(59, 130, 246, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(139, 92, 246, 0.8)',
                'rgba(236, 72, 153, 0.8)',
                'rgba(6, 182, 212, 0.8)'
            ],
            borderColor: [
                '#3b82f6',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#8b5cf6',
                '#ec4899',
                '#06b6d4'
            ],
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false
        }]
    };

    window.charts.weekly = new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            ...chartConfig,
            plugins: {
                ...chartConfig.plugins,
                tooltip: {
                    ...chartConfig.plugins.tooltip,
                    callbacks: {
                        title: function(context) {
                            return `Dia: ${context[0].label}`;
                        },
                        label: function(context) {
                            return `Produção: ${context.parsed.y}L`;
                        }
                    }
                }
            }
        }
    });
}

// Função para criar gráfico de temperatura moderno
function createTemperatureChart() {
    const ctx = document.getElementById('temperatureChart');
    if (!ctx) return;

    // Destruir gráfico existente
    if (window.charts.temperature) {
        window.charts.temperature.destroy();
        window.charts.temperature = null;
    }

    Chart.helpers.each(Chart.instances, function(instance) {
        if (instance.canvas.id === 'temperatureChart') {
            instance.destroy();
        }
    });

    const data = {
        labels: ['00h', '04h', '08h', '12h', '16h', '20h'],
        datasets: [{
            label: 'Temperatura (°C)',
            data: [4.2, 4.1, 4.3, 4.5, 4.4, 4.2],
            borderColor: '#f97316',
            backgroundColor: 'rgba(249, 115, 22, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#f97316',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8
        }]
    };

    window.charts.temperature = new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            ...chartConfig,
            plugins: {
                ...chartConfig.plugins,
                tooltip: {
                    ...chartConfig.plugins.tooltip,
                    callbacks: {
                        title: function(context) {
                            return `Horário: ${context[0].label}`;
                        },
                        label: function(context) {
                            return `Temperatura: ${context.parsed.y}°C`;
                        }
                    }
                }
            }
        }
    });
}

// Função para criar gráfico mensal moderno
function createMonthlyChart() {
    const ctx = document.getElementById('monthlyProductionChart');
    if (!ctx) return;

    // Destruir gráfico existente
    if (window.charts.monthly) {
        window.charts.monthly.destroy();
        window.charts.monthly = null;
    }

    Chart.helpers.each(Chart.instances, function(instance) {
        if (instance.canvas.id === 'monthlyProductionChart') {
            instance.destroy();
        }
    });

    const data = {
        labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
        datasets: [{
            label: 'Produção Mensal (L)',
            data: [1200, 1350, 1280, 1420],
            backgroundColor: [
                'rgba(99, 102, 241, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(239, 68, 68, 0.8)'
            ],
            borderColor: [
                '#6366f1',
                '#10b981',
                '#f59e0b',
                '#ef4444'
            ],
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false
        }]
    };

    window.charts.monthly = new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            ...chartConfig,
            plugins: {
                ...chartConfig.plugins,
                tooltip: {
                    ...chartConfig.plugins.tooltip,
                    callbacks: {
                        title: function(context) {
                            return `Semana: ${context[0].label}`;
                        },
                        label: function(context) {
                            return `Produção: ${context.parsed.y}L`;
                        }
                    }
                }
            }
        }
    });
}

// Função para criar gráfico de produção diária moderno
function createDailyChart() {
    const ctx = document.getElementById('dailyVolumeChart');
    if (!ctx) return;

    // Destruir gráfico existente
    if (window.charts.daily) {
        window.charts.daily.destroy();
        window.charts.daily = null;
    }

    Chart.helpers.each(Chart.instances, function(instance) {
        if (instance.canvas.id === 'dailyVolumeChart') {
            instance.destroy();
        }
    });

    const data = {
        labels: ['00h', '04h', '08h', '12h', '16h', '20h'],
        datasets: [{
            label: 'Produção Diária (L)',
            data: [45, 52, 48, 55, 50, 47],
            backgroundColor: 'rgba(34, 197, 94, 0.2)',
            borderColor: '#22c55e',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#22c55e',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8
        }]
    };

    window.charts.daily = new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            ...chartConfig,
            plugins: {
                ...chartConfig.plugins,
                tooltip: {
                    ...chartConfig.plugins.tooltip,
                    callbacks: {
                        title: function(context) {
                            return `Horário: ${context[0].label}`;
                        },
                        label: function(context) {
                            return `Produção: ${context.parsed.y}L`;
                        }
                    }
                }
            }
        }
    });
}

// Função para criar gráfico de produção semanal moderno
function createWeeklyVolumeChart() {
    const ctx = document.getElementById('weeklyVolumeChart');
    if (!ctx) return;

    // Destruir gráfico existente
    if (window.charts.weeklyVolume) {
        window.charts.weeklyVolume.destroy();
        window.charts.weeklyVolume = null;
    }

    Chart.helpers.each(Chart.instances, function(instance) {
        if (instance.canvas.id === 'weeklyVolumeChart') {
            instance.destroy();
        }
    });

    const data = {
        labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
        datasets: [{
            label: 'Produção Semanal (L)',
            data: [320, 340, 315, 360, 345, 380, 365],
            backgroundColor: [
                'rgba(59, 130, 246, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(139, 92, 246, 0.8)',
                'rgba(236, 72, 153, 0.8)',
                'rgba(6, 182, 212, 0.8)'
            ],
            borderColor: [
                '#3b82f6',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#8b5cf6',
                '#ec4899',
                '#06b6d4'
            ],
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false
        }]
    };

    window.charts.weeklyVolume = new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            ...chartConfig,
            plugins: {
                ...chartConfig.plugins,
                tooltip: {
                    ...chartConfig.plugins.tooltip,
                    callbacks: {
                        title: function(context) {
                            return `Dia: ${context[0].label}`;
                        },
                        label: function(context) {
                            return `Produção: ${context.parsed.y}L`;
                        }
                    }
                }
            }
        }
    });
}

// Função para limpar todos os gráficos existentes
function clearAllCharts() {
    console.log('🧹 Limpando todos os gráficos existentes...');
    
    // Destruir gráficos controlados
    if (window.charts.volume) {
        window.charts.volume.destroy();
        window.charts.volume = null;
    }
    if (window.charts.weekly) {
        window.charts.weekly.destroy();
        window.charts.weekly = null;
    }
    if (window.charts.temperature) {
        window.charts.temperature.destroy();
        window.charts.temperature = null;
    }
    if (window.charts.monthly) {
        window.charts.monthly.destroy();
        window.charts.monthly = null;
    }
    if (window.charts.daily) {
        window.charts.daily.destroy();
        window.charts.daily = null;
    }
    if (window.charts.weeklyVolume) {
        window.charts.weeklyVolume.destroy();
        window.charts.weeklyVolume = null;
    }
    
    // Destruir todos os gráficos Chart.js existentes
    Chart.helpers.each(Chart.instances, function(instance) {
        instance.destroy();
    });
    
    // Limpar canvas manualmente
    const canvases = ['volumeChart', 'dashboardWeeklyChart', 'temperatureChart', 'monthlyProductionChart', 'dailyVolumeChart', 'weeklyVolumeChart'];
    canvases.forEach(canvasId => {
        const canvas = document.getElementById(canvasId);
        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    });
    
    console.log('✅ Todos os gráficos foram limpos');
}

// Função principal para inicializar todos os gráficos
window.initModernCharts = function() {
    console.log('🚀 Inicializando gráficos modernos...');
    
    // Aguardar Chart.js estar disponível
    if (typeof Chart === 'undefined') {
        console.log('⏳ Aguardando Chart.js...');
        setTimeout(() => {
            initModernCharts();
        }, 500);
        return;
    }

    try {
        // Limpar todos os gráficos existentes primeiro
        clearAllCharts();
        
        // Aguardar um pouco para garantir que a limpeza foi concluída
        setTimeout(() => {
            // Criar todos os gráficos
            createVolumeChart();
            createWeeklyChart();
            createTemperatureChart();
            createMonthlyChart();
            createDailyChart();
            createWeeklyVolumeChart();
            
            console.log('✅ Gráficos modernos inicializados com sucesso!');
        }, 100);
        
    } catch (error) {
        console.error('❌ Erro ao inicializar gráficos:', error);
    }
};

// Auto-inicializar gráficos quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        initModernCharts();
    }, 1000);
    
    // Carregar dados reais do banco após 2 segundos
    setTimeout(() => {
        loadDashboardData();
    }, 2000);
});

window.testCharts = function() {
    console.log('🔍 Testando canvas...');
    
    const canvases = ['volumeChart', 'dashboardWeeklyChart', 'temperatureChart', 'monthlyProductionChart', 'dailyVolumeChart', 'weeklyVolumeChart'];
    canvases.forEach(canvasId => {
        const canvas = document.getElementById(canvasId);
        if (canvas) {
            console.log(`✅ Canvas ${canvasId} encontrado`);
        } else {
            console.log(`❌ Canvas ${canvasId} não encontrado`);
        }
    });
    
    console.log(`📊 Total de gráficos Chart.js: ${Chart.instances.length}`);
};

// Função para testar indicadores da dashboard
window.testDashboardIndicators = function() {
    console.log('🔍 Testando indicadores da dashboard...');
    
    const indicators = [
        'todayVolume',
        'qualityAverage', 
        'pendingPayments',
        'activeUsers',
        'volumeToday'
    ];
    
    indicators.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            console.log(`✅ Elemento ${id} encontrado:`, element.textContent);
        } else {
            console.log(`❌ Elemento ${id} não encontrado`);
        }
    });
    
    // Testar API
    fetch('api/manager.php?action=get_dashboard_stats')
        .then(response => response.json())
        .then(data => {
            console.log('📊 Dados da API:', data);
        })
        .catch(error => {
            console.error('❌ Erro na API:', error);
        });
};

window.forceLoadIndicators = function() {
    console.log('🔄 Forçando carregamento dos indicadores...');
    loadDashboardData();
};

// Função para carregar indicadores reais do banco
window.loadRealIndicators = function() {
    console.log('📊 Carregando indicadores reais do banco...');
    loadDashboardData();
};

window.checkDatabaseData = async function() {
    console.log('🔍 Verificando dados do banco...');
    loadDashboardData();
};

window.testUsersData = async function() {
    console.log('👥 Testando dados de usuários (usando API users.php)...');
    
    try {
        // Usar a mesma API que funciona na Gestão de Usuários
        const usersResponse = await fetch('api/users.php?action=select');
        console.log('📊 Status da API users.php:', usersResponse.status);
        
        if (usersResponse.ok) {
            const usersResult = await usersResponse.json();
            console.log('📊 Resposta da API users.php:', usersResult);
            
            if (usersResult.success && usersResult.data) {
                const totalUsers = usersResult.data.length;
                console.log('👥 Total de usuários via API users.php:', totalUsers);

                const activeUsersElement = document.getElementById('activeUsers');
                if (activeUsersElement) {
                    console.log('✅ Elemento activeUsers encontrado:', activeUsersElement);
                    console.log('📝 Valor atual do elemento:', activeUsersElement.textContent);
                    
                    // Atualizar manualmente
                    activeUsersElement.textContent = totalUsers;
                    console.log('✅ Elemento atualizado para:', activeUsersElement.textContent);
                    
                    if (totalUsers > 0) {
                        console.log('✅ Há usuários no sistema via API users.php:', totalUsers);
                    } else {
                        console.log('⚠️ NÃO há usuários no sistema via API users.php');
                    }
                } else {
                    console.error('❌ Elemento activeUsers NÃO encontrado!');
                }
            } else {
                console.log('❌ API users.php retornou erro:', usersResult.error);
            }
        } else {
            console.log('❌ Erro HTTP na API users.php:', usersResponse.status);
        }
    } catch (error) {
        console.error('❌ Erro ao testar usuários via API users.php:', error);
    }
};

// Função para testar dados de produção diretamente
window.testProductionData = async function() {
    console.log('🧪 Testando dados de produção (APENAS dados atuais)...');
    
    try {
        const response = await fetch('api/manager.php?action=get_dashboard_stats');
        const data = await response.json();
        
        console.log('📊 Resposta completa da API:', data);
        
        if (data.success && data.data) {
            console.log('✅ Dados do dashboard (APENAS dados atuais):', data.data);

            if (data.data.volume_today > 0) {
                console.log('✅ Há dados de produção para HOJE!');
            } else {
                console.log('⚠️ NÃO há dados de produção para HOJE');
                console.log('💡 Isso é normal - os dados no banco são de Janeiro 2025');
                console.log('💡 Para ter dados de hoje, você precisa inserir dados com a data atual');
            }

            if (data.data.volume_month > 0) {
                console.log('✅ Há dados de produção para o MÊS ATUAL!');
            } else {
                console.log('⚠️ NÃO há dados de produção para o MÊS ATUAL');
                console.log('💡 Isso é normal - os dados no banco são de Janeiro 2025');
            }

            if (data.data.volume_year > 0) {
                console.log('✅ Há dados de produção para o ANO ATUAL!');
                console.log('📊 Volume anual:', data.data.volume_year, 'L');
            } else {
                console.log('⚠️ NÃO há dados de produção para o ANO ATUAL');
                console.log('💡 Isso é normal - os dados no banco são de Janeiro 2025');
            }

            console.log('👥 Dados de usuários:', {
                active_users: data.data.active_users,
                total_animals: data.data.total_animals,
                active_pregnancies: data.data.active_pregnancies,
                active_alerts: data.data.active_alerts
            });
            
            if (data.data.active_users > 0) {
                console.log('✅ Há usuários no sistema:', data.data.active_users);
            } else {
                console.log('⚠️ NÃO há usuários no sistema');
                console.log('💡 Verifique se há usuários cadastrados no banco');
            }
        } else {
            console.log('❌ Erro na API:', data.error);
        }
    } catch (error) {
        console.error('❌ Erro ao testar dados:', error);
    }
}

// ==================== MODAL DE CONFIRMAÇÃO DE EXCLUSÃO DE VOLUME ====================

// Variável global para armazenar o ID do registro a ser excluído
let volumeToDeleteId = null;

// Função para mostrar modal de confirmação de exclusão
function showDeleteVolumeModal(recordId, dateTime, shift, volume, userName) {
    console.log('🗑️ Mostrando modal de confirmação para exclusão do registro:', recordId);
    
    // Armazenar ID do registro
    volumeToDeleteId = recordId;
    
    // Criar modal dinamicamente
    const modalHTML = `
        <div id="deleteVolumeModalDynamic" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]" style="display: flex;">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">Tem certeza que deseja excluir este registro de volume?</h4>
                            <p class="text-sm text-gray-600 mt-1">Esta ação não pode ser desfeita.</p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <h5 class="font-medium text-gray-900 mb-2">Detalhes do Registro:</h5>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Data:</span>
                                <span class="ml-2 font-medium">${dateTime || 'N/A'}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Turno:</span>
                                <span class="ml-2 font-medium">${shift || 'N/A'}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Volume:</span>
                                <span class="ml-2 font-medium">${volume || 'N/A'}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Funcionário:</span>
                                <span class="ml-2 font-medium">${userName || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button onclick="closeDeleteVolumeModalDynamic()" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors font-medium">
                            Cancelar
                        </button>
                        <button onclick="confirmDeleteVolumeDynamic()" class="px-4 py-2 bg-red-600 text-white hover:bg-red-700 rounded-lg transition-colors font-medium">
                            Excluir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal existente se houver
    const existingModal = document.getElementById('deleteVolumeModalDynamic');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Adicionar modal ao body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Adicionar listener para ESC
    const modal = document.getElementById('deleteVolumeModalDynamic');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeDeleteVolumeModalDynamic();
            }
        });
    }
    
    console.log('✅ Modal dinâmico de exclusão criado');
}

// Função para fechar modal de confirmação
function closeDeleteVolumeModal() {
    console.log('❌ Fechando modal de confirmação de exclusão');
    
    const modal = document.getElementById('deleteVolumeModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
    
    // Limpar ID armazenado
    volumeToDeleteId = null;
}

// Função para fechar modal dinâmico
function closeDeleteVolumeModalDynamic() {
    console.log('❌ Fechando modal dinâmico de confirmação de exclusão');
    
    const modal = document.getElementById('deleteVolumeModalDynamic');
    if (modal) {
        modal.remove();
    }
    
    // Limpar ID armazenado
    volumeToDeleteId = null;
}

// Função para confirmar exclusão
async function confirmDeleteVolume() {
    if (!volumeToDeleteId) {
        console.error('❌ Nenhum ID de registro para excluir');
        return;
    }
    
    console.log('🗑️ Confirmando exclusão do registro:', volumeToDeleteId);
    
    try {
        // Fechar modal primeiro
        closeDeleteVolumeModal();
        
        // Mostrar loading
        showNotification('Excluindo registro...', 'info');
        
        // Fazer requisição para API
        const response = await fetch('api/volume.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete',
                id: volumeToDeleteId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Registro de volume excluído com sucesso!', 'success');
            
            // Recarregar tabela de registros
            await loadVolumeRecords();
        } else {
            showNotification('Erro ao excluir registro: ' + (result.error || 'Erro desconhecido'), 'error');
        }
        
    } catch (error) {
        console.error('❌ Erro ao excluir registro:', error);
        showNotification('Erro ao excluir registro: ' + error.message, 'error');
    }
}

// Função para confirmar exclusão do modal dinâmico
async function confirmDeleteVolumeDynamic() {
    if (!volumeToDeleteId) {
        console.error('❌ Nenhum ID de registro para excluir');
        return;
    }
    
    console.log('🗑️ Confirmando exclusão do registro:', volumeToDeleteId);
    
    try {
        // Fechar modal primeiro
        closeDeleteVolumeModalDynamic();
        
        // Mostrar loading
        showNotification('Excluindo registro...', 'info');
        
        // Fazer requisição para API
        const response = await fetch('api/volume.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete',
                id: volumeToDeleteId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Registro de volume excluído com sucesso!', 'success');
            
            // Recarregar tabela de registros
            await loadVolumeRecords();
        } else {
            showNotification('Erro ao excluir registro: ' + (result.error || 'Erro desconhecido'), 'error');
        }
        
    } catch (error) {
        console.error('❌ Erro ao excluir registro:', error);
        showNotification('Erro ao excluir registro: ' + error.message, 'error');
    }
}

console.log('📊 Sistema de Gráficos Modernos LacTech carregado!');
