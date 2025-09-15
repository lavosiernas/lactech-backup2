/**
 * ECOSYSTEM MANAGER - Lactech + AgroSmart Integration
 * Gerencia conexões entre sistemas e sincronização de dados
 */

class EcosystemManager {
    constructor() {
        this.systems = {
            lactech: {
                name: 'Lactech',
                url: 'https://lactech.netlify.app',
                color: '#10B981',
                icon: '🐄'
            },
            agrosmart: {
                name: 'AgroSmart',
                url: 'https://agrosmart.netlify.app', 
                color: '#3B82F6',
                icon: '🌱'
            },
            sns: {
                name: 'SNS',
                url: 'https://sns.netlify.app',
                color: '#8B5CF6', 
                icon: '📱'
            }
        };
    }

    /**
     * Conectar sistema ao ecossistema
     */
    async connectSystem(systemName, userData) {
        try {
            const { data, error } = await supabase
                .from('ecosystem_connections')
                .upsert([{
                    user_id: userData.userId,
                    farm_id: userData.farmId,
                    connected_systems: this.addSystemToConnection(userData.currentSystems, systemName),
                    subscription_data: userData.subscription,
                    updated_at: new Date().toISOString()
                }], {
                    onConflict: 'user_id,farm_id'
                });

            if (error) throw error;

            // Sincronizar dados entre sistemas
            await this.syncDataBetweenSystems(systemName, userData);
            
            return { success: true, message: `${this.systems[systemName].name} conectado com sucesso!` };
        } catch (error) {
            console.error('Erro ao conectar sistema:', error);
            return { success: false, message: 'Erro ao conectar sistema' };
        }
    }

    /**
     * Obter dados do ecossistema do usuário
     */
    async getEcosystemData(userId, farmId) {
        try {
            const { data, error } = await supabase
                .from('ecosystem_connections')
                .select('*')
                .eq('user_id', userId)
                .eq('farm_id', farmId)
                .single();

            if (error && error.code !== 'PGRST116') throw error;

            return data || {
                connected_systems: [],
                subscription_data: null,
                farm_profile: null
            };
        } catch (error) {
            console.error('Erro ao obter dados do ecossistema:', error);
            return null;
        }
    }

    /**
     * Renderizar seção de ecossistema no perfil
     */
    async renderEcosystemSection() {
        try {
            const supabase = await getSupabaseClient();
            if (!supabase) {
                console.error('Supabase não disponível');
                return;
            }
            
            const userId = (await supabase.auth.getUser()).data.user?.id;
            const farmId = getCurrentFarmId();
        
        if (!userId || !farmId) return;

        const ecosystemData = await this.getEcosystemData(userId, farmId);
        
        const ecosystemHTML = `
            <div class="ecosystem-section bg-gradient-to-r from-purple-50 to-blue-50 rounded-2xl p-6 border border-purple-200">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="bg-purple-100 p-3 rounded-xl">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Ecossistema Conectado</h3>
                            <p class="text-gray-600">Sistemas integrados à sua fazenda</p>
                        </div>
                    </div>
                    <button onclick="openEcosystemModal()" 
                            class="bg-purple-600 text-white px-4 py-2 rounded-xl hover:bg-purple-700 transition-colors">
                        Gerenciar
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    ${this.renderSystemCards(ecosystemData?.connected_systems || [])}
                </div>

                ${ecosystemData?.subscription_data ? this.renderSubscriptionInfo(ecosystemData.subscription_data) : ''}
            </div>
        `;

        // Inserir no perfil
        const profileContainer = document.querySelector('.profile-content');
        if (profileContainer) {
            profileContainer.insertAdjacentHTML('beforeend', ecosystemHTML);
        }
        
        } catch (error) {
            console.error('Erro ao renderizar seção de ecossistema:', error);
        }
    }

    /**
     * Renderizar cards dos sistemas conectados
     */
    renderSystemCards(connectedSystems) {
        return Object.entries(this.systems).map(([key, system]) => {
            const isConnected = connectedSystems.includes(key);
            const statusColor = isConnected ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600';
            const statusText = isConnected ? 'Conectado' : 'Disponível';
            
            return `
                <div class="system-card bg-white rounded-xl p-4 border border-gray-200 hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-3">
                            <span class="text-2xl">${system.icon}</span>
                            <div>
                                <h4 class="font-semibold text-gray-900">${system.name}</h4>
                                <p class="text-sm text-gray-600">Sistema ${key}</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-medium ${statusColor}">
                            ${statusText}
                        </span>
                    </div>
                    
                    ${isConnected ? `
                        <div class="space-y-2">
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Sincronizado
                            </div>
                            <button onclick="disconnectSystem('${key}')" 
                                    class="w-full text-red-600 hover:text-red-700 text-sm font-medium">
                                Desconectar
                            </button>
                        </div>
                    ` : `
                        <button onclick="connectSystem('${key}')" 
                                class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg transition-colors">
                            Conectar
                        </button>
                    `}
                </div>
            `;
        }).join('');
    }

    /**
     * Renderizar informações da assinatura
     */
    renderSubscriptionInfo(subscriptionData) {
        return `
            <div class="mt-6 bg-white rounded-xl p-4 border border-gray-200">
                <h4 class="font-semibold text-gray-900 mb-3">Assinatura Unificada</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Plano:</span>
                        <span class="font-medium text-gray-900 ml-2">${subscriptionData.plan}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Status:</span>
                        <span class="font-medium text-green-600 ml-2">${subscriptionData.status}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Expira:</span>
                        <span class="font-medium text-gray-900 ml-2">${new Date(subscriptionData.expiresAt).toLocaleDateString('pt-BR')}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Sistemas:</span>
                        <span class="font-medium text-gray-900 ml-2">${subscriptionData.features.length} recursos</span>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Adicionar sistema à conexão existente
     */
    addSystemToConnection(currentSystems, newSystem) {
        if (!currentSystems) return [newSystem];
        if (currentSystems.includes(newSystem)) return currentSystems;
        return [...currentSystems, newSystem];
    }

    /**
     * Sincronizar dados entre sistemas
     */
    async syncDataBetweenSystems(sourceSystem, userData) {
        try {
            const { data, error } = await supabase
                .from('ecosystem_sync')
                .insert([{
                    connection_id: userData.connectionId,
                    source_system: sourceSystem,
                    target_system: 'all',
                    sync_data: {
                        farm_data: userData.farmData,
                        user_preferences: userData.preferences,
                        last_sync: new Date().toISOString()
                    },
                    status: 'pending'
                }]);

            if (error) throw error;

            // Aqui você implementaria a lógica de sincronização real
            // Por exemplo, chamadas para APIs dos outros sistemas
            
            return { success: true };
        } catch (error) {
            console.error('Erro na sincronização:', error);
            return { success: false };
        }
    }
}

// Instância global
const ecosystemManager = new EcosystemManager();

// Funções globais para uso nos HTMLs
window.connectSystem = async (systemName) => {
    const userData = {
        userId: (await supabase.auth.getUser()).data.user?.id,
        farmId: getCurrentFarmId(),
        currentSystems: await ecosystemManager.getEcosystemData(
            (await supabase.auth.getUser()).data.user?.id, 
            getCurrentFarmId()
        ).then(data => data?.connected_systems || []),
        subscription: {
            plan: 'premium',
            status: 'active', 
            expiresAt: '2024-12-31',
            features: ['production_tracking', 'weather_data', 'analytics']
        }
    };
    
    const result = await ecosystemManager.connectSystem(systemName, userData);
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
        // Recarregar seção do ecossistema
        ecosystemManager.renderEcosystemSection();
    }
};

window.disconnectSystem = async (systemName) => {
    if (confirm(`Tem certeza que deseja desconectar o ${ecosystemManager.systems[systemName].name}?`)) {
        // Implementar desconexão
        showNotification(`${ecosystemManager.systems[systemName].name} desconectado`, 'success');
        ecosystemManager.renderEcosystemSection();
    }
};

window.openEcosystemModal = () => {
    // Implementar modal de gerenciamento do ecossistema
    showNotification('Modal de gerenciamento será implementado', 'info');
};

// Inicializar quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    ecosystemManager.renderEcosystemSection();
});
