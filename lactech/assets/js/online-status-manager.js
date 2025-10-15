// Gerenciador de Status Online
// Mantém o status online atualizado em tempo real

class OnlineStatusManager {
    constructor() {
        this.heartbeatInterval = null;
        this.isActive = false;
        this.currentUserId = null;
        this.heartbeatIntervalMs = 30000; // 30 segundos
    }

    // Iniciar o gerenciador de status online
    async start() {
        try {
            const supabase = await getSupabaseClient();
            const { data: { user } } = await supabase.auth.getUser();
            
            if (!user) {
                console.log('❌ Usuário não autenticado para status online');
                return;
            }

            this.currentUserId = user.id;
            this.isActive = true;

            // Marcar como online imediatamente
            await this.updateOnlineStatus(true);

            // Configurar heartbeat
            this.heartbeatInterval = setInterval(async () => {
                if (this.isActive) {
                    await this.updateOnlineStatus(true);
                }
            }, this.heartbeatIntervalMs);

            console.log('✅ Gerenciador de status online iniciado');

            // Configurar eventos para detectar quando o usuário sai da página
            this.setupPageVisibilityHandlers();

        } catch (error) {
            console.error('❌ Erro ao iniciar gerenciador de status online:', error);
        }
    }

    // Parar o gerenciador de status online
    async stop() {
        this.isActive = false;
        
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }

        // Marcar como offline
        if (this.currentUserId) {
            await this.updateOnlineStatus(false);
        }

        console.log('🔴 Gerenciador de status online parado');
    }

    // Atualizar status online
    async updateOnlineStatus(isOnline) {
        if (!this.currentUserId) return;

        try {
            const supabase = await getSupabaseClient();
            const { error } = await supabase
                .from('users')
                .update({ 
                    is_online: isOnline,
                    last_login: isOnline ? new Date().toISOString() : null
                })
                .eq('id', this.currentUserId);

            if (error) {
                console.error('❌ Erro ao atualizar status online:', error);
            } else {
                console.log(`✅ Status online atualizado: ${isOnline ? 'ONLINE' : 'OFFLINE'}`);
            }

        } catch (error) {
            console.error('❌ Erro ao atualizar status online:', error);
        }
    }

    // Configurar handlers para detectar quando o usuário sai da página
    setupPageVisibilityHandlers() {
        // Quando a página fica visível
        document.addEventListener('visibilitychange', async () => {
            if (!document.hidden && this.isActive) {
                console.log('👁️ Página visível - marcando como online');
                await this.updateOnlineStatus(true);
            }
        });

        // Quando a página é fechada ou recarregada
        window.addEventListener('beforeunload', async () => {
            console.log('🚪 Página sendo fechada - marcando como offline');
            await this.updateOnlineStatus(false);
        });

        // Quando a página perde o foco
        window.addEventListener('blur', async () => {
            if (this.isActive) {
                console.log('👁️ Página perdeu foco - mantendo online por enquanto');
                // Não marcar como offline imediatamente, apenas quando sair da página
            }
        });

        // Quando a página ganha foco
        window.addEventListener('focus', async () => {
            if (this.isActive) {
                console.log('👁️ Página ganhou foco - marcando como online');
                await this.updateOnlineStatus(true);
            }
        });
    }

    // Forçar atualização do status
    async forceUpdate() {
        if (this.currentUserId) {
            await this.updateOnlineStatus(true);
        }
    }
}

// Instância global do gerenciador
const onlineStatusManager = new OnlineStatusManager();

// Funções globais para controle
window.startOnlineStatus = () => onlineStatusManager.start();
window.stopOnlineStatus = () => onlineStatusManager.stop();
window.updateOnlineStatus = (isOnline) => onlineStatusManager.updateOnlineStatus(isOnline);
window.forceUpdateOnlineStatus = () => onlineStatusManager.forceUpdate();

// Iniciar automaticamente quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    // Aguardar um pouco para garantir que o Supabase esteja carregado
    setTimeout(() => {
        onlineStatusManager.start();
    }, 2000);
});

console.log('✅ Gerenciador de Status Online carregado!');
