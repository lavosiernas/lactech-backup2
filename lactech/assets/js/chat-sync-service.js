// Serviço de Sincronização entre Sistema e Chat

class ChatSyncService {
    constructor() {
        this.syncInProgress = new Set();
    }

    // Sincronizar usuário do sistema para o chat
    async syncUserToChat(userData) {
        const userId = userData.id;
        
        // Evitar sincronização duplicada
        if (this.syncInProgress.has(userId)) {
            return;
        }
        
        this.syncInProgress.add(userId);
        
        try {
            const { error } = await window.chatSupabase
                .from('users')
                .upsert({
                    id: userData.id,
                    name: userData.name,
                    email: userData.email,
                    farm_id: userData.farm_id,
                    role: userData.role,
                    last_login: userData.last_login,
                    created_at: userData.created_at,
                    updated_at: new Date().toISOString()
                }, {
                    onConflict: 'id'
                });

            if (error) {
                console.error('Erro ao sincronizar usuário para chat:', error);
                throw error;
            }

            console.log(`✅ Usuário ${userData.name} sincronizado para o chat`);
            
        } catch (error) {
            console.error('Erro na sincronização:', error);
        } finally {
            this.syncInProgress.delete(userId);
        }
    }

    // Sincronizar múltiplos usuários
    async syncUsersToChat(users) {
        if (!users || users.length === 0) return;

        try {
            const usersToSync = users.map(user => ({
                id: user.id,
                name: user.name,
                email: user.email,
                farm_id: user.farm_id,
                role: user.role,
                last_login: user.last_login,
                created_at: user.created_at,
                updated_at: new Date().toISOString()
            }));

            const { error } = await window.chatSupabase
                .from('users')
                .upsert(usersToSync, {
                    onConflict: 'id'
                });

            if (error) {
                console.error('Erro ao sincronizar usuários para chat:', error);
                throw error;
            }

            console.log(`✅ ${users.length} usuários sincronizados para o chat`);
            
        } catch (error) {
            console.error('Erro na sincronização em lote:', error);
        }
    }

    // Buscar usuários da fazenda (usando apenas o banco principal por enquanto)
    async getFarmUsers(farmId) {
        try {
            console.log('🔄 Buscando usuários da fazenda:', farmId);
            
            // Buscar usuários do sistema principal
            const { data: systemUsers, error: systemError } = await window.systemSupabase
                .from('users')
                .select('*')
                .eq('farm_id', farmId);

            if (systemError) {
                console.error('❌ Erro ao buscar usuários do sistema:', systemError);
                throw systemError;
            }

            console.log('✅ Usuários encontrados:', systemUsers?.length || 0);

            // Por enquanto, não sincronizar com banco separado
            // if (systemUsers && systemUsers.length > 0) {
            //     await this.syncUsersToChat(systemUsers);
            // }

            return systemUsers || [];
            
        } catch (error) {
            console.error('❌ Erro ao buscar usuários da fazenda:', error);
            return [];
        }
    }

    // Enviar mensagem no chat (usando banco principal por enquanto)
    async sendChatMessage(messageData) {
        try {
            console.log('💬 Enviando mensagem:', messageData);
            
            let messageContent = messageData.message;
            
            // Se há call_data, armazenar como JSON na mensagem
            if (messageData.call_data) {
                console.log('📞 Adicionando call_data:', messageData.call_data);
                messageContent = JSON.stringify({
                    type: 'call_data',
                    call_data: messageData.call_data,
                    original_message: messageData.message
                });
            }
            
            // Se há file_data, armazenar como JSON na mensagem
            if (messageData.file_data) {
                console.log('📎 Adicionando file_data:', messageData.file_data);
                messageContent = JSON.stringify({
                    type: 'file_data',
                    file_data: messageData.file_data,
                    original_message: messageData.message
                });
            }
            
            const messageToInsert = {
                farm_id: messageData.farm_id,
                sender_id: messageData.sender_id,
                receiver_id: messageData.receiver_id,
                message: messageContent,
                created_at: new Date().toISOString()
            };
            
            const { error } = await window.systemSupabase
                .from('chat_messages')
                .insert([messageToInsert]);

            if (error) {
                console.error('❌ Erro ao enviar mensagem:', error);
                throw error;
            }

            console.log('✅ Mensagem enviada com sucesso');
            return true;
            
        } catch (error) {
            console.error('❌ Erro ao enviar mensagem no chat:', error);
            throw error;
        }
    }

    // Buscar mensagens do chat (usando banco principal por enquanto)
    async getChatMessages(farmId, senderId = null, receiverId = null) {
        try {
            console.log('📨 Buscando mensagens:', { farmId, senderId, receiverId });
            
            let query = window.systemSupabase
                .from('chat_messages')
                .select('*')
                .eq('farm_id', farmId);

            if (senderId && receiverId) {
                // Filtrar mensagens entre dois usuários específicos
                query = query.or(`and(sender_id.eq.${senderId},receiver_id.eq.${receiverId}),and(sender_id.eq.${receiverId},receiver_id.eq.${senderId})`);
            }

            const { data: messages, error } = await query.order('created_at', { ascending: true });

            if (error) {
                console.error('❌ Erro ao buscar mensagens:', error);
                throw error;
            }

            console.log('✅ Mensagens encontradas:', messages?.length || 0);
            
            // Processar mensagens e extrair dados de chamada/arquivo
            if (messages && messages.length > 0) {
                messages.forEach(message => {
                    try {
                        // Tentar parsear como JSON para verificar se é uma mensagem especial
                        const parsedMessage = JSON.parse(message.message);
                        if (parsedMessage.type === 'call_data') {
                            message.call_data = parsedMessage.call_data;
                            message.message = parsedMessage.original_message || '';
                        } else if (parsedMessage.type === 'file_data') {
                            message.file_data = parsedMessage.file_data;
                            message.message = parsedMessage.original_message || '';
                        }
                    } catch (e) {
                        // Se não conseguir parsear, é uma mensagem normal
                        // Manter a mensagem como está
                    }
                });
                
                const userIds = [...new Set(messages.map(msg => msg.sender_id))];
                const { data: users, error: usersError } = await window.systemSupabase
                    .from('users')
                    .select('id, name, role, profile_photo_url')
                    .in('id', userIds);

                if (!usersError && users) {
                    // Adicionar informações do usuário às mensagens
                    messages.forEach(message => {
                        const user = users.find(u => u.id === message.sender_id);
                        if (user) {
                            message.sender_name = user.name;
                            message.sender_role = user.role;
                            message.sender_photo = user.profile_photo_url;
                        }
                    });
                }
            }
            
            return messages || [];
            
        } catch (error) {
            console.error('❌ Erro ao buscar mensagens do chat:', error);
            return [];
        }
    }

    // Atualizar último login do usuário
    async updateUserLastLogin(userId) {
        try {
            console.log('🔄 Atualizando último login para usuário:', userId);
            
            // Atualizar no sistema principal
            const { error: systemError } = await window.systemSupabase
                .from('users')
                .update({ 
                    last_login: new Date().toISOString(),
                    is_online: true
                })
                .eq('id', userId);

            if (systemError) {
                console.error('❌ Erro ao atualizar login no sistema:', systemError);
            } else {
                console.log('✅ Login atualizado com sucesso');
            }

        } catch (error) {
            console.error('❌ Erro ao atualizar último login:', error);
        }
    }

    // Marcar usuário como offline
    async markUserOffline(userId) {
        try {
            console.log('🔴 Marcando usuário como offline:', userId);
            
            const { error } = await window.systemSupabase
                .from('users')
                .update({ is_online: false })
                .eq('id', userId);

            if (error) {
                console.error('❌ Erro ao marcar usuário como offline:', error);
            } else {
                console.log('✅ Usuário marcado como offline');
            }

        } catch (error) {
            console.error('❌ Erro ao marcar usuário como offline:', error);
        }
    }

    // Configurar real-time para mensagens (usando banco principal por enquanto)
    setupRealtimeChat(farmId, onNewMessage) {
        console.log('🔔 Configurando real-time para farm:', farmId);
        
        const channel = window.systemSupabase
            .channel(`chat_messages_${farmId}`)
            .on('postgres_changes', {
                event: 'INSERT',
                schema: 'public',
                table: 'chat_messages',
                filter: `farm_id=eq.${farmId}`
            }, (payload) => {
                console.log('📨 Nova mensagem recebida via real-time:', payload.new);
                if (onNewMessage) {
                    onNewMessage(payload.new);
                }
            })
            .on('postgres_changes', {
                event: 'UPDATE',
                schema: 'public',
                table: 'chat_messages',
                filter: `farm_id=eq.${farmId}`
            }, (payload) => {
                console.log('📨 Mensagem atualizada via real-time:', payload.new);
                if (onNewMessage) {
                    onNewMessage(payload.new);
                }
            })
            .subscribe();

        return channel;
    }

    // Desconectar real-time
    disconnectRealtime(channel) {
        if (channel) {
            window.systemSupabase.removeChannel(channel);
        }
    }
}

// Instância global do serviço
const chatSyncService = new ChatSyncService();

// Funções de conveniência
const syncUserToChat = (userData) => chatSyncService.syncUserToChat(userData);
const getFarmUsers = (farmId) => chatSyncService.getFarmUsers(farmId);
const sendChatMessage = (messageData) => chatSyncService.sendChatMessage(messageData);
const getChatMessages = (farmId, senderId, receiverId) => chatSyncService.getChatMessages(farmId, senderId, receiverId);
const updateUserLastLogin = (userId) => chatSyncService.updateUserLastLogin(userId);
const markUserOffline = (userId) => chatSyncService.markUserOffline(userId);
const setupRealtimeChat = (farmId, onNewMessage) => chatSyncService.setupRealtimeChat(farmId, onNewMessage);
const disconnectRealtime = (channel) => chatSyncService.disconnectRealtime(channel);
const disconnectAllRealtime = () => chatSyncService.disconnectAllRealtime();

// Expor funções globalmente
window.chatSyncService = chatSyncService;
window.syncUserToChat = syncUserToChat;
window.getFarmUsers = getFarmUsers;
window.sendChatMessage = sendChatMessage;
window.getChatMessages = getChatMessages;
window.updateUserLastLogin = updateUserLastLogin;
window.markUserOffline = markUserOffline;
window.setupRealtimeChat = setupRealtimeChat;
window.disconnectRealtime = disconnectRealtime;
window.disconnectAllRealtime = disconnectAllRealtime;
