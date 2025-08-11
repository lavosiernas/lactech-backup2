// Initialize report settings with manager's configuration
window.reportSettings = {
    farmName: null,
    farmLogo: null
};

// Load manager's report settings for the farm
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

        // Buscar configurações do gerente da fazenda
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

// Load recent activity
async function loadRecentActivity() {
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) return;

        const { data: recentData, error } = await supabase
            .from('milk_production')
            .select('volume_liters, production_date, shift, created_at')
            .eq('user_id', user.id)
            .order('production_date', { ascending: false })
            .order('created_at', { ascending: false })
            .limit(5);

        if (error) {
            console.error('Error loading recent activity:', error);
            return;
        }

        const activityList = document.getElementById('activityList');
        if (!activityList) return;

        if (!recentData || recentData.length === 0) {
            activityList.innerHTML = '<p class="text-gray-500 text-sm">Nenhuma atividade recente</p>';
            return;
        }

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

    } catch (error) {
        console.error('Error loading recent activity:', error);
    }
}

// Load production chart for last 7 days
async function loadProductionChart() {
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) return;

        const sevenDaysAgo = new Date();
        sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 6);
        sevenDaysAgo.setHours(0,0,0,0);

        const { data: chartData, error } = await supabase
            .from('milk_production')
            .select('volume_liters, production_date')
            .eq('user_id', user.id)
            .gte('production_date', formatLocalDate(sevenDaysAgo))
            .order('production_date', { ascending: true });

        if (error) {
            console.error('Error loading chart data:', error);
            return;
        }

        const chartContainer = document.getElementById('productionChart');
        if (!chartContainer) return;

        // Group data by date
        const dailyData = {};
        for (let i = 0; i < 7; i++) {
            const date = new Date();
            date.setDate(date.getDate() - (6 - i));
            const dateStr = formatLocalDate(date);
            dailyData[dateStr] = 0;
        }

        if (chartData) {
            chartData.forEach(record => {
                if (dailyData.hasOwnProperty(record.production_date)) {
                    dailyData[record.production_date] += record.volume_liters;
                }
            });
        }

        const maxValue = Math.max(...Object.values(dailyData), 1);
        
        chartContainer.innerHTML = Object.entries(dailyData).map(([date, volume]) => {
            const dateObj = new Date(date + 'T00:00:00');
            const dayName = dateObj.toLocaleDateString('pt-BR', { weekday: 'short' });
            const height = (volume / maxValue) * 100;
            
            return `
                <div class="flex flex-col items-center">
                    <div class="w-8 bg-gray-200 rounded-t" style="height: 60px; position: relative;">
                        <div class="bg-green-500 rounded-t absolute bottom-0 w-full" style="height: ${height}%;"></div>
                    </div>
                    <span class="text-xs text-gray-600 mt-1">${dayName}</span>
                    <span class="text-xs text-gray-500">${volume.toFixed(1)}L</span>
                </div>
            `;
        }).join('');

    } catch (error) {
        console.error('Error loading production chart:', error);
    }
}

// Get time ago helper function
function getTimeAgo(date) {
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 60) {
        return diffMins <= 1 ? 'Agora' : `${diffMins} min atrás`;
    } else if (diffHours < 24) {
        return `${diffHours}h atrás`;
    } else {
        return `${diffDays}d atrás`;
    }
}