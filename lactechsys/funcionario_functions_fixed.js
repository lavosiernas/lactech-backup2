// =====================================================
// FUNÇÕES CORRIGIDAS DO FUNCIONÁRIO - COMPATÍVEL COM SCHEMA
// Sistema de Gestão de Fazendas Leiteiras
// =====================================================

// Initialize report settings with manager's configuration
window.reportSettings = {
    farmName: null,
    farmLogo: null
};

async function loadManagerReportSettings() {
    try {
        const { data: { user: currentUser } } = await supabase.auth.getUser();
        if (!currentUser) return;

        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', currentUser.id)
            .single();

        if (userError || !userData?.farm_id) {
            console.error('Erro ao obter farm_id:', userError);
            return;
        }

        const { data: managerData, error: managerError } = await supabase
            .from('users')
            .select('report_farm_logo_base64, report_farm_name')
            .eq('farm_id', userData.farm_id)
            .eq('role', 'gerente')
            .not('report_farm_logo_base64', 'is', null)
            .maybeSingle();

        if (managerError) {
            console.log('Nenhuma configuração de gerente encontrada:', managerError);
            return;
        }

        if (managerData) {
            window.reportSettings.farmName = managerData.report_farm_name;
            window.reportSettings.farmLogo = managerData.report_farm_logo_base64;
            console.log('Configurações do gerente carregadas:', window.reportSettings);
        }
    } catch (error) {
        console.error('Erro ao carregar configurações do gerente:', error);
    }
}

// Load recent activity - CORRIGIDO para usar farm_id
async function loadRecentActivity() {
    console.log('[DEBUG] loadRecentActivity() iniciado...');
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) {
            console.log('[DEBUG] Usuário não autenticado');
            return;
        }

        console.log('[DEBUG] Usuário autenticado:', user.email);

        // Primeiro obter farm_id do usuário
        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) {
            console.log('[DEBUG] Erro ao obter farm_id:', userError.message);
            return;
        }

        console.log('[DEBUG] Farm ID obtido:', userData.farm_id);

        console.log('[DEBUG] Buscando atividades recentes...');
        const { data: recentData, error } = await supabase
            .from('milk_production')
            .select('volume_liters, production_date, shift, created_at, users(name)')
            .eq('farm_id', userData.farm_id)
            .order('production_date', { ascending: false })
            .order('created_at', { ascending: false })
            .limit(5);

        if (error) {
            console.log('[DEBUG] Erro ao buscar atividades recentes:', error.message);
            return;
        }

        console.log('[DEBUG] Atividades recentes encontradas:', recentData);

        const activityList = document.getElementById('activityList');
        if (!activityList) {
            console.log('[DEBUG] Elemento activityList não encontrado');
            return;
        }

        if (!recentData || recentData.length === 0) {
            console.log('[DEBUG] Nenhuma atividade recente encontrada');
            activityList.innerHTML = `
                <div class="text-center py-8">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500 text-sm">Nenhuma atividade recente</p>
                    <p class="text-gray-400 text-xs">Registre dados para ver o histórico</p>
                </div>
            `;
            console.log('[DEBUG] Mensagem de "nenhuma atividade" exibida');
            return;
        }

        console.log('[DEBUG] Gerando HTML das atividades...');
        activityList.innerHTML = recentData.map(record => {
            const date = new Date(record.created_at);
            const timeAgo = getTimeAgo(date);
            const formattedTime = date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            const shiftText = {
                'manha': 'Manhã',
                'tarde': 'Tarde', 
                'noite': 'Noite'
            }[record.shift] || record.shift;
            
            return `
                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                    <div>
                        <p class="text-sm font-medium text-gray-900">${record.volume_liters}L - ${shiftText}</p>
                        <p class="text-xs text-gray-500">${timeAgo}</p>
                    </div>
                    <div class="text-xs text-gray-400">
                        ${formattedTime}
                    </div>
                </div>
            `;
        }).join('');

        console.log('[DEBUG] HTML das atividades gerado e inserido');
        console.log('[DEBUG] loadRecentActivity() concluído com sucesso');

    } catch (error) {
        console.log('[DEBUG] Erro em loadRecentActivity():', error.message);
        console.error('Error loading recent activity:', error);
    }
}

// Load production chart - CORRIGIDO para usar farm_id
async function loadProductionChart() {
    console.log('[DEBUG] loadProductionChart() iniciado...');
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) {
            console.log('[DEBUG] Usuário não autenticado');
            return;
        }

        console.log('[DEBUG] Usuário autenticado:', user.email);

        // Primeiro obter farm_id do usuário
        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) {
            console.log('[DEBUG] Erro ao obter farm_id:', userError.message);
            return;
        }

        console.log('[DEBUG] Farm ID obtido:', userData.farm_id);

        // Obter dados dos últimos 7 dias
        const endDate = new Date();
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - 7);

        console.log('[DEBUG] Buscando dados do gráfico...');
        console.log('[DEBUG] Data inicial:', startDate.toISOString().split('T')[0]);
        console.log('[DEBUG] Data final:', endDate.toISOString().split('T')[0]);

        const { data: chartData, error } = await supabase
            .from('milk_production')
            .select('production_date, volume_liters')
            .eq('farm_id', userData.farm_id)
            .gte('production_date', startDate.toISOString().split('T')[0])
            .lte('production_date', endDate.toISOString().split('T')[0])
            .order('production_date', { ascending: true });

        if (error) {
            console.log('[DEBUG] Erro ao buscar dados do gráfico:', error.message);
            return;
        }

        console.log('[DEBUG] Dados do gráfico encontrados:', chartData);

        // Processar dados para o gráfico
        const chartLabels = [];
        const chartDataValues = [];

        // Criar array com todos os dias dos últimos 7 dias
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            const dateStr = date.toISOString().split('T')[0];
            const dayName = date.toLocaleDateString('pt-BR', { weekday: 'short' });
            
            chartLabels.push(dayName);
            
            // Encontrar dados para este dia
            const dayData = chartData?.filter(d => d.production_date === dateStr) || [];
            const totalVolume = dayData.reduce((sum, d) => sum + parseFloat(d.volume_liters || 0), 0);
            chartDataValues.push(totalVolume);
        }

        console.log('[DEBUG] Labels do gráfico:', chartLabels);
        console.log('[DEBUG] Valores do gráfico:', chartDataValues);

        // Criar gráfico
        const ctx = document.getElementById('productionChart');
        if (!ctx) {
            console.log('[DEBUG] Elemento productionChart não encontrado');
            return;
        }

        console.log('[DEBUG] Gerando gráfico...');

        // Destruir gráfico existente se houver
        if (window.productionChartInstance) {
            window.productionChartInstance.destroy();
        }

        window.productionChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Volume (L)',
                    data: chartDataValues,
                    borderColor: '#86d186',
                    backgroundColor: 'rgba(134, 209, 134, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
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
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        console.log('[DEBUG] Gráfico criado com sucesso');
        console.log('[DEBUG] loadProductionChart() concluído com sucesso');

    } catch (error) {
        console.log('[DEBUG] Erro em loadProductionChart():', error.message);
        console.error('Error loading production chart:', error);
    }
}

// Load dashboard indicators - CORRIGIDO para usar farm_id
async function loadDashboardIndicators() {
    console.log('[DEBUG] loadDashboardIndicators() iniciado...');
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) {
            console.log('[DEBUG] Usuário não autenticado');
            return;
        }

        console.log('[DEBUG] Usuário autenticado:', user.email);

        // Primeiro obter farm_id do usuário
        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) {
            console.log('[DEBUG] Erro ao obter farm_id:', userError.message);
            return;
        }

        console.log('[DEBUG] Farm ID obtido:', userData.farm_id);

        const today = new Date().toISOString().split('T')[0];
        console.log('[DEBUG] Data de hoje:', today);

        // Volume de hoje
        console.log('[DEBUG] Buscando volume de hoje...');
        const { data: todayVolume, error: volumeError } = await supabase
            .from('milk_production')
            .select('volume_liters')
            .eq('farm_id', userData.farm_id)
            .eq('production_date', today);

        if (volumeError) {
            console.log('[DEBUG] Erro ao buscar volume:', volumeError.message);
        } else {
            console.log('[DEBUG] Volume de hoje encontrado:', todayVolume);
            if (todayVolume && todayVolume.length > 0) {
                const totalVolume = todayVolume.reduce((sum, record) => sum + parseFloat(record.volume_liters || 0), 0);
                console.log('[DEBUG] Volume total calculado:', totalVolume);
                const volumeElement = document.getElementById('todayVolume');
                if (volumeElement) {
                    volumeElement.textContent = `${totalVolume.toFixed(1)} L`;
                    console.log('[DEBUG] Volume atualizado no elemento');
                } else {
                    console.log('[DEBUG] Elemento todayVolume não encontrado');
                }
            } else {
                console.log('[DEBUG] Nenhum volume encontrado para hoje');
                const volumeElement = document.getElementById('todayVolume');
                if (volumeElement) {
                    volumeElement.textContent = '0.0 L';
                }
            }
        }

        // Total de registros hoje
        console.log('[DEBUG] Buscando registros de hoje...');
        const { data: todayRecords, error: recordsError } = await supabase
            .from('milk_production')
            .select('id')
            .eq('farm_id', userData.farm_id)
            .eq('production_date', today);

        if (recordsError) {
            console.log('[DEBUG] Erro ao buscar registros:', recordsError.message);
        } else {
            console.log('[DEBUG] Registros de hoje encontrados:', todayRecords);
            const recordsElement = document.getElementById('todayRecords');
            if (recordsElement) {
                recordsElement.textContent = todayRecords ? todayRecords.length : 0;
                console.log('[DEBUG] Registros atualizados no elemento');
            } else {
                console.log('[DEBUG] Elemento todayRecords não encontrado');
            }
        }

        // Média semanal
        console.log('[DEBUG] Calculando média semanal...');
        const weekAgo = new Date();
        weekAgo.setDate(weekAgo.getDate() - 7);
        const weekAgoStr = weekAgo.toISOString().split('T')[0];
        console.log('[DEBUG] Data de 7 dias atrás:', weekAgoStr);

        const { data: weekData, error: weekError } = await supabase
            .from('milk_production')
            .select('volume_liters')
            .eq('farm_id', userData.farm_id)
            .gte('production_date', weekAgoStr);

        if (weekError) {
            console.log('[DEBUG] Erro ao buscar dados semanais:', weekError.message);
        } else {
            console.log('[DEBUG] Dados semanais encontrados:', weekData);
            if (weekData && weekData.length > 0) {
                const totalWeekVolume = weekData.reduce((sum, record) => sum + parseFloat(record.volume_liters || 0), 0);
                const avgWeekVolume = totalWeekVolume / 7;
                console.log('[DEBUG] Média semanal calculada:', avgWeekVolume);
                const avgElement = document.getElementById('weekAverage');
                if (avgElement) {
                    avgElement.textContent = `${avgWeekVolume.toFixed(1)} L`;
                    console.log('[DEBUG] Média semanal atualizada no elemento');
                } else {
                    console.log('[DEBUG] Elemento weekAverage não encontrado');
                }
            } else {
                console.log('[DEBUG] Nenhum dado semanal encontrado');
                const avgElement = document.getElementById('weekAverage');
                if (avgElement) {
                    avgElement.textContent = '0.0 L';
                }
            }
        }

        // Melhor dia do mês
        console.log('[DEBUG] Calculando melhor dia do mês...');
        const monthStart = new Date();
        monthStart.setDate(1);
        const monthStartStr = monthStart.toISOString().split('T')[0];

        const { data: monthData, error: monthError } = await supabase
            .from('milk_production')
            .select('volume_liters, production_date')
            .eq('farm_id', userData.farm_id)
            .gte('production_date', monthStartStr);

        if (monthError) {
            console.log('[DEBUG] Erro ao buscar dados do mês:', monthError.message);
        } else {
            console.log('[DEBUG] Dados do mês encontrados:', monthData);
            if (monthData && monthData.length > 0) {
                // Agrupar por data e calcular total diário
                const dailyTotals = {};
                monthData.forEach(record => {
                    const date = record.production_date;
                    if (!dailyTotals[date]) {
                        dailyTotals[date] = 0;
                    }
                    dailyTotals[date] += parseFloat(record.volume_liters || 0);
                });

                // Encontrar o melhor dia
                const bestDay = Object.values(dailyTotals).reduce((max, total) => Math.max(max, total), 0);
                console.log('[DEBUG] Melhor dia do mês:', bestDay);
                
                const bestDayElement = document.getElementById('bestDay');
                if (bestDayElement) {
                    bestDayElement.textContent = `${bestDay.toFixed(1)} L`;
                    console.log('[DEBUG] Melhor dia atualizado no elemento');
                } else {
                    console.log('[DEBUG] Elemento bestDay não encontrado');
                }
            } else {
                console.log('[DEBUG] Nenhum dado do mês encontrado');
                const bestDayElement = document.getElementById('bestDay');
                if (bestDayElement) {
                    bestDayElement.textContent = '0.0 L';
                }
            }
        }

        console.log('[DEBUG] loadDashboardIndicators() concluído com sucesso');

    } catch (error) {
        console.log('[DEBUG] Erro em loadDashboardIndicators():', error.message);
        console.error('Error loading dashboard indicators:', error);
    }
}

// Register milk production - CORRIGIDO para usar farm_id
async function registerMilkProduction(productionData) {
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) {
            throw new Error('Usuário não autenticado');
        }

        // Primeiro obter farm_id do usuário
        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) {
            throw new Error('Erro ao obter dados da fazenda');
        }

        const { data, error } = await supabase
            .from('milk_production')
            .insert({
                farm_id: userData.farm_id,  // CORREÇÃO: incluir farm_id
                user_id: user.id,
                production_date: productionData.date,
                shift: productionData.shift,
                volume_liters: productionData.volume,
                temperature: productionData.temperature || null,
                observations: productionData.observations || ''
            })
            .select()
            .single();

        if (error) throw error;

        return {
            success: true,
            productionId: data.id,
            message: 'Produção registrada com sucesso!'
        };

    } catch (error) {
        console.error('Erro ao registrar produção:', error);
        return {
            success: false,
            error: error.message || 'Erro ao registrar produção'
        };
    }
}

// Get milk production history - CORRIGIDO para usar farm_id
async function getMilkProductionHistory(startDate, endDate, limit = 50) {
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');

        // Primeiro obter farm_id do usuário
        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) throw userError;

        let query = supabase
            .from('milk_production')
            .select(`
                *,
                users(name)
            `)
            .eq('farm_id', userData.farm_id)  // CORREÇÃO: filtrar por farm_id
            .order('production_date', { ascending: false })
            .order('created_at', { ascending: false })
            .limit(limit);
        
        if (startDate) {
            query = query.gte('production_date', startDate);
        }
        
        if (endDate) {
            query = query.lte('production_date', endDate);
        }
        
        const { data, error } = await query;
        
        if (error) throw error;
        
        return {
            success: true,
            productions: data || []
        };
        
    } catch (error) {
        console.error('Erro ao obter histórico:', error);
        return {
            success: false,
            error: error.message || 'Erro ao obter histórico'
        };
    }
}

// Utility function to get time ago
function getTimeAgo(date) {
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'Agora mesmo';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} min atrás`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours}h atrás`;
    } else {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days} dias atrás`;
    }
}

// Export functions globally
window.funcionarioAPI = {
    loadManagerReportSettings,
    loadRecentActivity,
    loadProductionChart,
    loadDashboardIndicators,
    registerMilkProduction,
    getMilkProductionHistory,
    getTimeAgo
};

console.log('Funções do funcionário corrigidas carregadas!'); 