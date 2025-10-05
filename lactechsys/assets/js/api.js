// =====================================================
// API PRINCIPAL - LACTECH
// =====================================================
// Arquivo 2 de 5 - API unificada
// =====================================================

// Aguardar Supabase estar disponível
async function waitForSupabase() {
    return new Promise((resolve) => {
        const checkSupabase = () => {
            if (window.supabase) {
                resolve(window.supabase);
            } else {
                setTimeout(checkSupabase, 100);
            }
        };
        checkSupabase();
    });
}

// API Principal
async function createAPI() {
    const supabase = await waitForSupabase();
    
    window.LacTechAPI = {
        // =====================================================
        // AUTENTICAÇÃO
        // =====================================================
        auth: {
            getUser: () => supabase.auth.getUser(),
            getSession: () => supabase.auth.getSession(),
            signOut: () => supabase.auth.signOut()
        },
        
        // =====================================================
        // USUÁRIOS (NOVA FUNÇÃO RPC)
        // =====================================================
        users: {
            create: async (userData) => {
                try {
                    const { data, error } = await supabase.rpc('create_user', {
                        p_name: userData.name,
                        p_role: userData.role,
                        p_whatsapp: userData.whatsapp || null,
                        p_password: userData.password,
                        p_profile_photo_url: userData.profile_photo_url || null
                    });
                    
                    if (error) throw error;
                    return { success: true, data };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            },
            
            getFarmUsers: async () => {
                try {
                    const { data: { user } } = await supabase.auth.getUser();
                    if (!user) throw new Error('Usuário não autenticado');
                    
                    const { data: userData } = await supabase
                        .from('users')
                        .select('farm_id')
                        .eq('id', user.id)
                        .single();
                    
                    if (!userData?.farm_id) throw new Error('Fazenda não encontrada');
                    
                    const { data, error } = await supabase
                        .from('users')
                        .select('*')
                        .eq('farm_id', userData.farm_id)
                        .eq('is_active', true)
                        .order('name');
                    
                    if (error) throw error;
                    return { success: true, data };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            },
            
            getProfile: async () => {
                try {
                    const { data, error } = await supabase.rpc('get_user_profile');
                    if (error) throw error;
                    return { success: true, data: data[0] };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            },
            
            update: async (id, data) => {
                try {
                    const { data: result, error } = await supabase
                        .from('users')
                        .update(data)
                        .eq('id', id)
                        .select()
                        .single();
                    
                    if (error) throw error;
                    return { success: true, data: result };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            },
            
            delete: async (id) => {
                try {
                    const { error } = await supabase
                        .from('users')
                        .delete()
                        .eq('id', id);
                    
                    if (error) throw error;
                    return { success: true };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            },
            
            getHistory: async (startDate, endDate) => {
                try {
                    const { data: { user } } = await supabase.auth.getUser();
                    if (!user) throw new Error('Usuário não autenticado');
                    
                    const { data: userData } = await supabase
                        .from('users')
                        .select('farm_id')
                        .eq('id', user.id)
                        .single();
                    
                    if (!userData?.farm_id) throw new Error('Fazenda não encontrada');
                    
                    let query = supabase
                        .from('financial_records')
                        .select('*, users(name, email)')
                        .eq('farm_id', userData.farm_id)
                        .order('record_date', { ascending: false });
                    
                    if (startDate) query = query.gte('record_date', startDate);
                    if (endDate) query = query.lte('record_date', endDate);
                    
                    const { data, error } = await query;
                    
                    if (error) throw error;
                    return { success: true, data };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            }
        },
        
        // =====================================================
        // PRODUÇÃO DE LEITE
        // =====================================================
        production: {
            create: async (data) => {
                try {
                    const { data: { user } } = await supabase.auth.getUser();
                    if (!user) throw new Error('Usuário não autenticado');
                    
                    const { data: userData } = await supabase
                        .from('users')
                        .select('farm_id')
                        .eq('id', user.id)
                        .single();
                    
                    if (!userData?.farm_id) throw new Error('Fazenda não encontrada');
                    
                    const { data: result, error } = await supabase
                        .from('milk_production')
                        .insert({
                            farm_id: userData.farm_id,
                            user_id: user.id,
                            production_date: data.production_date,
                            shift: data.shift,
                            volume_liters: data.volume_liters,
                            temperature: data.temperature,
                            observations: data.observations
                        })
                        .select()
                        .single();
                    
                    if (error) throw error;
                    return { success: true, data: result };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            },
            
            getRecords: async (startDate, endDate) => {
                try {
                    const { data: { user } } = await supabase.auth.getUser();
                    if (!user) throw new Error('Usuário não autenticado');
                    
                    const { data: userData } = await supabase
                        .from('users')
                        .select('farm_id')
                        .eq('id', user.id)
                        .single();
                    
                    if (!userData?.farm_id) throw new Error('Fazenda não encontrada');
                    
                    let query = supabase
                        .from('milk_production')
                        .select('*, users(name)')
                        .eq('farm_id', userData.farm_id)
                        .order('production_date', { ascending: false });
                    
                    if (startDate) query = query.gte('production_date', startDate);
                    if (endDate) query = query.lte('production_date', endDate);
                    
                    const { data, error } = await query;
                    
                    if (error) throw error;
                    return { success: true, data };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            },
            
            getStats: async (startDate, endDate) => {
                try {
                    const { data: { user } } = await supabase.auth.getUser();
                    if (!user) throw new Error('Usuário não autenticado');
                    
                    const { data: userData } = await supabase
                        .from('users')
                        .select('farm_id')
                        .eq('id', user.id)
                        .single();
                    
                    if (!userData?.farm_id) throw new Error('Fazenda não encontrada');
                    
                    const { data, error } = await supabase.rpc('get_production_stats', {
                        p_farm_id: userData.farm_id,
                        p_start_date: startDate,
                        p_end_date: endDate
                    });
                    
                    if (error) throw error;
                    return { success: true, data: data[0] };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            }
        },
        
        // =====================================================
        // TESTES DE QUALIDADE
        // =====================================================
        quality: {
            create: async (data) => {
                try {
                    const { data: result, error } = await supabase.rpc('register_quality_test', {
                        p_test_date: data.test_date,
                        p_fat_percentage: data.fat_percentage,
                        p_protein_percentage: data.protein_percentage,
                        p_scc: data.scc,
                        p_cbt: data.cbt,
                        p_laboratory: data.laboratory,
                        p_observations: data.observations
                    });
                    
                    if (error) throw error;
                    return { success: true, data: result };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            },
            
            getTests: async (startDate, endDate) => {
                try {
                    const { data: { user } } = await supabase.auth.getUser();
                    if (!user) throw new Error('Usuário não autenticado');
                    
                    const { data: userData } = await supabase
                        .from('users')
                        .select('farm_id')
                        .eq('id', user.id)
                        .single();
                    
                    if (!userData?.farm_id) throw new Error('Fazenda não encontrada');
                    
                    let query = supabase
                        .from('quality_tests')
                        .select('*')
                        .eq('farm_id', userData.farm_id)
                        .order('test_date', { ascending: false });
                    
                    if (startDate) query = query.gte('test_date', startDate);
                    if (endDate) query = query.lte('test_date', endDate);
                    
                    const { data, error } = await query;
                    
                    if (error) throw error;
                    return { success: true, data };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            }
        },
        
        // =====================================================
        // REGISTROS FINANCEIROS
        // =====================================================
        financial: {
            create: async (data) => {
                try {
                    const { data: { user } } = await supabase.auth.getUser();
                    if (!user) throw new Error('Usuário não autenticado');
                    
                    const { data: userData } = await supabase
                        .from('users')
                        .select('farm_id')
                        .eq('id', user.id)
                        .single();
                    
                    if (!userData?.farm_id) throw new Error('Fazenda não encontrada');
                    
                    const { data: result, error } = await supabase
                        .from('financial_records')
                        .insert({
                            farm_id: userData.farm_id,
                            record_date: data.record_date,
                            type: data.type,
                            amount: data.amount,
                            description: data.description,
                            category: data.category,
                            status: data.status || 'pending'
                        })
                        .select()
                        .single();
                    
                    if (error) throw error;
                    return { success: true, data: result };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            },
            
            getRecords: async (startDate, endDate) => {
                try {
                    const { data: { user } } = await supabase.auth.getUser();
                    if (!user) throw new Error('Usuário não autenticado');
                    
                    const { data: userData } = await supabase
                        .from('users')
                        .select('farm_id')
                        .eq('id', user.id)
                        .single();
                    
                    if (!userData?.farm_id) throw new Error('Fazenda não encontrada');
                    
                    let query = supabase
                        .from('financial_records')
                        .select('*')
                        .eq('farm_id', userData.farm_id)
                        .order('record_date', { ascending: false });
                    
                    if (startDate) query = query.gte('record_date', startDate);
                    if (endDate) query = query.lte('record_date', endDate);
                    
                    const { data, error } = await query;
                    
                    if (error) throw error;
                    return { success: true, data };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            }
        },
        
        // =====================================================
        // CONFIGURAÇÕES DE RELATÓRIO
        // =====================================================
        settings: {
            update: async (settings) => {
                try {
                    const { data, error } = await supabase.rpc('update_user_report_settings', {
                        p_report_farm_name: settings.report_farm_name,
                        p_report_farm_logo_base64: settings.report_farm_logo_base64,
                        p_report_footer_text: settings.report_footer_text,
                        p_report_system_logo_base64: settings.report_system_logo_base64
                    });
                    if (error) throw error;
                    return { success: true, data };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            }
        },
        
        // =====================================================
        // SUPABASE INSTANCE
        // =====================================================
        supabase: supabase
    };
    
    // Manter compatibilidade
    window.LacTech = window.LacTechAPI;
    
    console.log('✅ API Principal criada com novas configurações');
    console.log('🔗 URL: https://igpjdudmgvaecvszcess.supabase.co');
    console.log('📧 SEM CONFIRMAÇÃO DE EMAIL - Acesso direto habilitado');
    window.dispatchEvent(new CustomEvent('lactechapi-ready'));
}

// Inicializar API
createAPI().catch(console.error);
