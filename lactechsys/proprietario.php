<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Proprietário - Sistema Leiteiro</title>
    
    <!-- PWA Meta Tags -->
    <meta name="description" content="Painel do Proprietário - Sistema completo para gestão de produção leiteira, controle de qualidade e relatórios">
    <meta name="theme-color" content="#166534">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="LacTech Proprietário">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="#166534">
    <meta name="msapplication-config" content="/browserconfig.xml">
    
    <!-- PWA Icons -->
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/x-icon">
    <link rel="apple-touch-icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="72x72" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="96x96" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="128x128" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="144x144" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="152x152" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="192x192" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="384x384" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="512x512" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    
    <!-- PWA Manifest -->

    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="assets/js/pdf-generator.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2.39.0/dist/umd/supabase.min.js"></script>
    <script src="assets/js/loading-screen.js"></script>
    <script src="lactech-api-nova.js"></script>
    <script src="auth_fix.js"></script>
    <script src="pwa-manager.js"></script>
    <script src="assets/js/config.js" defer></script>
    <script src="assets/js/modal-system.js"></script>
    <script src="assets/js/offline-manager.js"></script>
    <script src="assets/js/offline-loading.js"></script>
    <link href="assets/css/offline-loading.css" rel="stylesheet">
    <script>
        // ==================== CACHE SYSTEM ====================
        const CacheManager = {
            cache: new Map(),
            userData: null,
            farmData: null,
            lastUserFetch: 0,
            lastFarmFetch: 0,
            CACHE_DURATION: 5 * 60 * 1000, // 5 minutos
            
            // Cache de dados do usuário
            async getUserData(forceRefresh = false) {
                const now = Date.now();
                if (!forceRefresh && this.userData && (now - this.lastUserFetch) < this.CACHE_DURATION) {
                    console.log('📋 Usando dados do usuário do cache');
                    return this.userData;
                }
                
                console.log('🔄 Buscando dados do usuário no Supabase');
                const supabase = createSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (user) {
                    const { data: userData } = await supabase
                        .from('users')
                        .select('id, name, email, role, farm_id, profile_photo')
                        .eq('id', user.id)
                        .single();
                    
                    this.userData = { ...user, ...userData };
                    this.lastUserFetch = now;
                    console.log('✅ Dados do usuário cacheados');
                }
                
                return this.userData;
            },
            
            // Cache de dados da fazenda
            async getFarmData(forceRefresh = false) {
                const now = Date.now();
                if (!forceRefresh && this.farmData && (now - this.lastFarmFetch) < this.CACHE_DURATION) {
                    console.log('📋 Usando dados da fazenda do cache');
                    return this.farmData;
                }
                
                console.log('🔄 Buscando dados da fazenda no Supabase');
                const userData = await this.getUserData();
                if (userData?.farm_id) {
                    const supabase = createSupabaseClient();
                    const { data: farmData } = await supabase
                        .from('farms')
                        .select('id, name, location')
                        .eq('id', userData.farm_id)
                        .single();
                    
                    this.farmData = farmData;
                    this.lastFarmFetch = now;
                    console.log('✅ Dados da fazenda cacheados');
                }
                
                return this.farmData;
            },
            
            // Cache genérico
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
            
            // Cache para dados de volume
            async getVolumeData(farmId, dateRange, forceRefresh = false) {
                const cacheKey = `volume_${farmId}_${dateRange}`;
                const cachedData = this.get(cacheKey);
                
                if (!forceRefresh && cachedData) {
                    console.log('📋 Usando dados de volume do cache:', cacheKey);
                    return cachedData;
                }
                
                console.log('🔄 Buscando dados de volume no Supabase:', cacheKey);
                const supabase = createSupabaseClient();
                
                let query = supabase
                    .from('volume_records')
                    .select('volume_liters, production_date')
                    .eq('farm_id', farmId);
                
                // Aplicar filtro de data se especificado
                if (dateRange === 'today') {
                    const today = new Date().toISOString().split('T')[0];
                    query = query.eq('production_date', today);
                } else if (dateRange === 'week') {
                    const sevenDaysAgo = new Date();
                    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 6);
                    query = query.gte('production_date', sevenDaysAgo.toISOString().split('T')[0]);
                } else if (dateRange === 'month') {
                    const firstDayOfMonth = new Date();
                    firstDayOfMonth.setDate(1);
                    query = query.gte('production_date', firstDayOfMonth.toISOString().split('T')[0]);
                }
                
                const { data, error } = await query.order('production_date', { ascending: true });
                
                if (error) {
                    console.error('❌ Erro ao buscar dados de volume:', error);
                    return null;
                }
                
                // Cachear por 2 minutos (dados de volume mudam mais frequentemente)
                this.set(cacheKey, data, 2 * 60 * 1000);
                console.log('✅ Dados de volume cacheados:', cacheKey);
                
                return data;
            },
            
            // Limpar cache específico
            clear(key) {
                if (key) {
                    this.cache.delete(key);
                } else {
                    this.cache.clear();
                    this.userData = null;
                    this.farmData = null;
                }
            },
            
            // Invalidar cache de dados críticos
            invalidateUserData() {
                this.userData = null;
                this.farmData = null;
                this.lastUserFetch = 0;
                this.lastFarmFetch = 0;
            }
        };
        
        // Aguardar configuração do Supabase antes de carregar fix_data_sync_complete.js
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const script = document.createElement('script');
                script.src = 'fix_data_sync_complete.js';
                document.head.appendChild(script);
            }, 1000);
        });
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="assets/css/dark-theme-fixes.css?v=2.0" rel="stylesheet">
    <link href="assets/css/loading-screen.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'forest': {
                            50: '#f0f9f0', 100: '#dcf2dc', 200: '#bce5bc', 300: '#8dd18d',
                            400: '#5bb85b', 500: '#369e36', 600: '#2a7f2a', 700: '#236523',
                            800: '#1f511f', 900: '#1a431a',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Gradiente de texto estilo Xandria Store */
        .gradient-text {
            background: linear-gradient(135deg, #01875f, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Card hover effect estilo Xandria Store */
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .dark .card-hover:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6);
        }
    </style>
</head>
<body class="gradient-mesh antialiased">

    <!-- Header -->
    <header class="gradient-forest shadow-xl sticky top-0 z-40 border-b border-forest-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <div class="header-logo-container">
                        <img src="assets/img/lactech-logo.png" alt="LacTech Logo" class="header-logo">
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-white tracking-tight">PAINEL DO PROPRIETÁRIO</h1>
                        <p class="text-xs text-forest-200" id="farmNameHeader">Carregando...</p>
                    </div>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex space-x-1">
                    <button class="nav-item active relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="overview">
                        Visão Geral
                    </button>
                    <button class="nav-item relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="financial">
                        Financeiro
                    </button>
                    <button class="nav-item relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="production">
                        Produção
                    </button>
                    <button class="nav-item relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="team">
                        Equipe
                    </button>
                    <button class="nav-item relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="settings">
                        Configurações
                    </button>
                </nav>

                <div class="flex items-center space-x-4">
                    <!-- Botão Xandria Store -->
                    <button onclick="openXandriaStore()" class="p-2 text-white hover:text-forest-200 transition-colors" title="Acessar Xandria Store">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"></path>
                        </svg>
                    </button>
                    
                    <button onclick="openProfileModal()" class="flex items-center space-x-3 text-white hover:text-forest-200 p-2 rounded-lg transition-all">
                        <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="text-left hidden sm:block">
                            <div class="text-sm font-semibold" id="ownerName">Carregando...</div>
                            <div class="text-xs text-forest-200">Proprietário</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-24 md:pb-4">
        
        <!-- Overview Tab -->
        <div id="overview-tab" class="tab-content">
            <!-- Welcome Section -->
            <div class="gradient-forest rounded-2xl p-6 mb-6 text-white shadow-xl relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold mb-2">Bem-vindo, <span id="ownerWelcome">Carregando...</span>!</h2>
                            <p class="text-forest-200 text-base font-medium mb-3">Visão completa do seu negócio</p>
                            <div class="flex items-center space-x-4">
                                <div class="text-xs font-medium">Última atualização: Agora</div>
                            </div>
                        </div>
                        <div class="hidden sm:block">
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="metric-card rounded-2xl p-4 text-center">
                    <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="text-xl font-bold text-slate-900 mb-1" id="monthlyRevenue">R$ --</div>
                    <div class="text-xs text-slate-500 font-medium">Receita Mensal</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Este Mês</div>
                </div>
                
                <div class="metric-card rounded-2xl p-4 text-center">
                    <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="text-xl font-bold text-slate-900 mb-1" id="dailyProduction">-- L</div>
                    <div class="text-xs text-slate-500 font-medium">Produção Diária</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Média</div>
                </div>
                
                <div class="metric-card rounded-2xl p-4 text-center">
                    <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="text-xl font-bold text-slate-900 mb-1" id="totalEmployees">--</div>
                    <div class="text-xs text-slate-500 font-medium">Funcionários</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Ativos</div>
                </div>
                
                <div class="metric-card rounded-2xl p-4 text-center">
                    <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <div class="text-xl font-bold text-slate-900 mb-1" id="totalAnimals">--</div>
                    <div class="text-xs text-slate-500 font-medium">Animais</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Rebanho</div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Revenue Chart -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Receita dos Últimos 6 Meses</h3>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Production Chart -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Produção de Leite</h3>
                    <div class="chart-container">
                        <canvas id="productionChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="data-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-900">Atividades Recentes</h3>
                    <button class="text-forest-600 hover:text-forest-700 font-semibold text-sm">Ver Todas</button>
                </div>
                <div class="space-y-4" id="recentActivities">
                    <div class="text-center py-8">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-500 text-sm">Nenhuma atividade recente</p>
                        <p class="text-gray-400 text-xs">As atividades aparecerão aqui</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Tab -->
        <div id="financial-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Financial Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Gestão Financeira</h2>
                            <p class="text-slate-600 text-sm">Controle completo das finanças da fazenda</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <select id="financialPeriod" class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none">
                                <option value="month">Este Mês</option>
                                <option value="quarter">Trimestre</option>
                                <option value="year">Ano</option>
                            </select>
                            <button onclick="generateFinancialReport()" class="px-4 py-2 bg-forest-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Gerar Relatório
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="data-card rounded-2xl p-6 text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-slate-900 mb-2" id="totalRevenue">R$ --</div>
                        <div class="text-sm text-slate-600 font-medium">Receita Total</div>
                        <div class="text-xs text-green-600 font-semibold mt-2" id="revenueChange">-</div>
                    </div>

                    <div class="data-card rounded-2xl p-6 text-center">
                        <div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-slate-900 mb-2" id="totalExpenses">R$ --</div>
                        <div class="text-sm text-slate-600 font-medium">Despesas Totais</div>
                        <div class="text-xs text-red-600 font-semibold mt-2" id="expensesChange">-</div>
                    </div>

                    <div class="data-card rounded-2xl p-6 text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-slate-900 mb-2" id="netProfit">R$ --</div>
                        <div class="text-sm text-slate-600 font-medium">Lucro Líquido</div>
                        <div class="text-xs text-blue-600 font-semibold mt-2" id="profitChange">-</div>
                    </div>
                </div>

                <!-- Financial Chart -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Fluxo de Caixa</h3>
                    <div class="chart-container">
                        <canvas id="cashFlowChart"></canvas>
                    </div>
                </div>

                <!-- Expense Categories -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Categorias de Despesas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-gray-50 rounded-xl">
                            <div class="text-lg font-bold text-slate-900" id="feedExpenses">R$ --</div>
                            <div class="text-sm text-slate-600">Alimentação</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-xl">
                            <div class="text-lg font-bold text-slate-900" id="vetExpenses">R$ --</div>
                            <div class="text-sm text-slate-600">Veterinário</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-xl">
                            <div class="text-lg font-bold text-slate-900" id="maintenanceExpenses">R$ --</div>
                            <div class="text-sm text-slate-600">Manutenção</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-xl">
                            <div class="text-lg font-bold text-slate-900" id="otherExpenses">R$ --</div>
                            <div class="text-sm text-slate-600">Outros</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Production Tab -->
        <div id="production-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Production Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Controle de Produção</h2>
                            <p class="text-slate-600 text-sm">Acompanhe a produção e qualidade do leite</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <select id="productionPeriod" class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none">
                                <option value="week">Esta Semana</option>
                                <option value="month">Este Mês</option>
                                <option value="quarter">Trimestre</option>
                            </select>
                            <button onclick="generateProductionReport()" class="px-4 py-2 bg-forest-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Relatório de Produção
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Production Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="todayProduction">-- L</div>
                        <div class="text-xs text-slate-500 font-medium">Produção Hoje</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Litros</div>
                    </div>
                    
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="weeklyAverage">-- L</div>
                        <div class="text-xs text-slate-500 font-medium">Média Semanal</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Por Dia</div>
                    </div>
                    
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="qualityScore">--%</div>
                        <div class="text-xs text-slate-500 font-medium">Qualidade</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Média</div>
                    </div>
                    
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="productiveAnimals">--</div>
                        <div class="text-xs text-slate-500 font-medium">Produtivos</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Animais</div>
                    </div>
                </div>

                <!-- Production Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Daily Production -->
                    <div class="data-card rounded-2xl p-6">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Produção Diária</h3>
                        <div class="chart-container">
                            <canvas id="dailyProductionChart"></canvas>
                        </div>
                    </div>

                    <!-- Quality Metrics -->
                    <div class="data-card rounded-2xl p-6">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Indicadores de Qualidade</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-sm font-medium text-slate-700">Gordura</span>
                                <span class="text-sm font-bold text-slate-900" id="fatContent">--%</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-sm font-medium text-slate-700">Proteína</span>
                                <span class="text-sm font-bold text-slate-900" id="proteinContent">--%</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-sm font-medium text-slate-700">CCS</span>
                                <span class="text-sm font-bold text-slate-900" id="sccCount">-- mil/mL</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-sm font-medium text-slate-700">CBT</span>
                                <span class="text-sm font-bold text-slate-900" id="tbc">-- mil/mL</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Tab -->
        <div id="team-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Team Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Gestão de Equipe</h2>
                            <p class="text-slate-600 text-sm">Gerencie funcionários e suas permissões</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <select id="teamFilter" class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none">
                                <option value="all">Todos os funcionários</option>
                                <option value="gerente">Gerentes</option>
                                <option value="funcionario">Funcionários</option>
                                <option value="veterinario">Veterinários</option>
                            </select>
                            <!-- Proprietário apenas visualiza - sem botões de adicionar -->
                        </div>
                    </div>
                </div>

                <!-- Team Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="data-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="totalUsers">--</div>
                        <div class="text-xs text-slate-500 font-medium">Total</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Usuários</div>
                    </div>
                    
                    <div class="data-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="managersCount">--</div>
                        <div class="text-xs text-slate-500 font-medium">Gerentes</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Ativos</div>
                    </div>
                    
                    <div class="data-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="employeesCount">--</div>
                        <div class="text-xs text-slate-500 font-medium">Funcionários</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Ativos</div>
                    </div>
                    
                    <div class="data-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="veterinariansCount">--</div>
                        <div class="text-xs text-slate-500 font-medium">Veterinários</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Ativos</div>
                    </div>
                </div>

                <!-- Team List -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Lista de Funcionários</h3>
                    <div class="space-y-4" id="teamList">
                        <div class="text-center py-12">
                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum Funcionário Cadastrado</h3>
                            <p class="text-gray-600 mb-4">Adicione funcionários para gerenciar sua equipe</p>
                            <p class="text-gray-500 text-sm">Entre em contato com o gerente para adicionar funcionários</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Tab -->
        <div id="settings-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Settings Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Configurações</h2>
                            <p class="text-slate-600 text-sm">Gerencie as configurações da fazenda e do sistema</p>
                        </div>
                    </div>
                </div>

                <!-- Farm Settings -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Informações da Fazenda</h3>
                    <form id="farmSettingsForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-floating">
                                <input type="text" id="farmName" name="farm_name" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" " required>
                                <label for="farmName" class="text-slate-600">Nome da Fazenda *</label>
                            </div>

                            <div class="form-floating">
                                <input type="text" id="farmOwner" name="owner_name" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" " required>
                                <label for="farmOwner" class="text-slate-600">Nome do Proprietário *</label>
                            </div>

                            <div class="form-floating">
                                <input type="email" id="farmEmail" name="email" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" " required>
                                <label for="farmEmail" class="text-slate-600">Email *</label>
                            </div>

                            <div class="form-floating">
                                <input type="tel" id="farmPhone" name="phone" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" ">
                                <label for="farmPhone" class="text-slate-600">Telefone</label>
                            </div>
                        </div>

                        <div class="form-floating">
                            <textarea id="farmAddress" name="address" rows="3" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none resize-none" placeholder=" "></textarea>
                            <label for="farmAddress" class="text-slate-600">Endereço</label>
                        </div>

                        <button type="submit" class="px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Salvar Configurações
                        </button>
                    </form>
                </div>

                <!-- System Settings -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Configurações do Sistema</h3>
                    <div class="space-y-6">
                        <!-- Notifications -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div>
                                <h4 class="font-semibold text-slate-900">Notificações por Email</h4>
                                <p class="text-sm text-slate-600">Receber alertas importantes por email</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" id="emailNotifications" checked>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-forest-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-forest-600"></div>
                            </label>
                        </div>

                        <!-- Auto Backup -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div>
                                <h4 class="font-semibold text-slate-900">Backup Automático</h4>
                                <p class="text-sm text-slate-600">Backup diário dos dados</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" id="autoBackup" checked>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-forest-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-forest-600"></div>
                            </label>
                        </div>

                        <!-- Data Retention -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div>
                                <h4 class="font-semibold text-slate-900">Retenção de Dados</h4>
                                <p class="text-sm text-slate-600">Manter dados por</p>
                            </div>
                            <select class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none">
                                <option value="1">1 ano</option>
                                <option value="2" selected>2 anos</option>
                                <option value="5">5 anos</option>
                                <option value="forever">Sempre</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="data-card rounded-2xl p-6 border-red-200">
                    <h3 class="text-lg font-bold text-red-900 mb-4">Zona de Perigo</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-red-50 rounded-xl">
                            <div>
                                <h4 class="font-semibold text-red-900">Exportar Dados</h4>
                                <p class="text-sm text-red-600">Baixar todos os dados da fazenda</p>
                            </div>
                            <button onclick="exportData()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-all">
                                Exportar
                            </button>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-red-50 rounded-xl">
                            <div>
                                <h4 class="font-semibold text-red-900">Resetar Sistema</h4>
                                <p class="text-sm text-red-600">Apagar todos os dados (irreversível)</p>
                            </div>
                            <button onclick="resetSystem()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-all">
                                Resetar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Enhanced Mobile Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 md:hidden mobile-nav-enhanced shadow-2xl z-40">
        <div class="grid grid-cols-5 gap-1 p-2">
            <button class="mobile-nav-item active flex flex-col items-center py-3 px-2 transition-all" data-tab="overview">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span class="text-xs font-semibold">Visão Geral</span>
            </button>
            <button class="mobile-nav-item flex flex-col items-center py-3 px-2 transition-all" data-tab="financial">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
                <span class="text-xs font-semibold">Financeiro</span>
            </button>
            <button class="mobile-nav-item flex flex-col items-center py-3 px-2 transition-all" data-tab="production">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                </svg>
                <span class="text-xs font-semibold">Produção</span>
            </button>
            <button class="mobile-nav-item flex flex-col items-center py-3 px-2 transition-all" data-tab="team">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span class="text-xs font-semibold">Equipe</span>
            </button>
            <button class="mobile-nav-item flex flex-col items-center py-3 px-2 transition-all" data-tab="settings">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="text-xs font-semibold">Config</span>
            </button>
        </div>
    </nav>

    <!-- Modal de Perfil do Proprietário -->
    <div id="profileModal" class="fullscreen-modal">
        <div class="modal-content">
            <!-- Header do Modal -->
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 z-10">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900">Perfil do Proprietário</h2>
                    <button onclick="closeProfileModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-all">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Conteúdo do Perfil -->
            <div class="p-6 space-y-6">
                <!-- Header do Perfil -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 gradient-forest rounded-2xl flex items-center justify-center shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900" id="profileName">Carregando...</h2>
                            <p class="text-slate-600 text-base">Proprietário</p>
                            <p class="text-sm text-slate-500" id="profileFarmName">Carregando...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Informações Pessoais -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Informações Pessoais</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Nome Completo</label>
                            <p class="text-gray-900 font-medium" id="profileFullName">Carregando...</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
                            <p class="text-gray-900 font-medium" id="profileEmail2">Carregando...</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Telefone</label>
                            <p class="text-gray-900 font-medium" id="profilePhone">Carregando...</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Cargo</label>
                            <p class="text-gray-900 font-medium">Proprietário</p>
                        </div>
                    </div>
                </div>
                

                                Pré-visualizar
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Alterar Senha -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Alterar Senha</h4>
                    <form id="changePasswordForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Senha Atual</label>
                            <input type="password" required name="current_password" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="Digite sua senha atual">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Nova Senha</label>
                                <input type="password" required name="new_password" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="Nova senha">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Confirmar Nova Senha</label>
                                <input type="password" required name="confirm_password" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="Confirme a nova senha">
                            </div>
                        </div>
                        <button type="submit" class="px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                            Alterar Senha
                        </button>
                    </form>
                </div>
                
                <!-- Sair do Sistema -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-red-200">
                    <h4 class="text-lg font-semibold text-red-900 mb-4">Zona de Perigo</h4>
                    <div class="flex items-center justify-between">
                        <div>
                            <h5 class="font-medium text-red-900">Sair do Sistema</h5>
                            <p class="text-sm text-red-600">Encerrar sua sessão atual</p>
                        </div>
                        <button onclick="signOut()" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition-all">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Sair
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Proprietário não tem modal de adicionar usuário - apenas visualização -->

    <!-- Notification Toast -->
    <div id="notificationToast" class="notification-toast">
        <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 max-w-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900" id="toastMessage">Configurações salvas com sucesso!</p>
                </div>
                <div class="ml-auto pl-3">
                    <button onclick="hideNotification()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Supabase client is already initialized in supabase_config_fixed.js
        // Using the global supabase instance from supabase_config_fixed.js

        // Check authentication
        async function checkAuthentication() {
            // Usar a versão corrigida que sincroniza com Supabase
            return await window.authFix.checkAuthenticationFixed();
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', async function() {
            const isAuthenticated = await checkAuthentication();
            if (!isAuthenticated) {
                return; // Stop execution if not authenticated
            }
            await initializePage();
            setupEventListeners();
            initializeCharts();
        });

        async function initializePage() {
            // Verificar se o usuário está ativo
            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (user) {
                    const { data: userData, error } = await supabase
                        .from('users')
                        .select('is_active')
                        .eq('id', user.id)
                        .single();
                    
                    if (!error && userData && userData.is_active === false) {
                        window.location.href = 'acesso-bloqueado.php';
                        return;
                    }
                }
            } catch (error) {
                console.error('Erro ao verificar status do usuário:', error);
            }
            
            await setFarmName();
            await setOwnerName();
            await loadDashboardData();
            await loadFarmSettings();
            await loadTeamData();
            
            await checkFirstAccess();
        }

        // Check if it's first access and show add manager button
        async function checkFirstAccess() {
            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) return;

                // Check if there are any managers in the system
                const { data: managers, error } = await supabase
                    .from('users')
                    .select('id')
                    .eq('role', 'gerente');

                if (error) {
                    console.error('Error checking managers:', error);
                    return;
                }

                // If no managers exist, show the add manager button
                if (!managers || managers.length === 0) {
                    const addManagerBtn = document.getElementById('addManagerBtn');
                    if (addManagerBtn) {
                        addManagerBtn.style.display = 'inline-flex';
                    }
                }
            } catch (error) {
                console.error('Error in checkFirstAccess:', error);
            }
        }

        // Function to get farm name from Supabase
        async function getFarmName() {
            try {
                const { data, error } = await supabase
                    .from('farms')
                    .select('name')
                    .single();
                
                if (error) throw error;
                return data?.name || 'Nome da Fazenda';
            } catch (error) {
                console.error('Error fetching farm name:', error);
                return 'Nome da Fazenda';
            }
        }

        // Function to get owner name from Supabase
        async function getOwnerName() {
            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) return { name: 'Nome do Proprietário', profile_photo_url: null };

                const { data, error } = await supabase
                    .from('users')
                    .select('name, profile_photo_url')
                    .eq('id', user.id)
                    .single();
                
                if (error) throw error;
                return {
                    name: data?.name || 'Nome do Proprietário',
                    profile_photo_url: data?.profile_photo_url || null
                };
            } catch (error) {
                console.error('Error fetching owner name:', error);
                return { name: 'Nome do Proprietário', profile_photo_url: null };
            }
        }

        // Function to set farm name in header
        async function setFarmName() {
            const farmName = await getFarmName();
            document.getElementById('farmNameHeader').textContent = farmName;
        }

        // Function to extract formal name (second name)
        function extractFormalName(fullName) {
            if (!fullName || typeof fullName !== 'string') {
                return 'Proprietário';
            }
            
            // Remove extra spaces and split
            const names = fullName.trim().split(/\s+/);
            
            // If only one name, return it
            if (names.length === 1) {
                return names[0];
            }
            
            // If two names, return the second
            if (names.length === 2) {
                return names[1];
            }
            
            // For 3 or more names, try to find the most formal name
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
                
                // Get the second meaningful name
                if (nameCount === 2) {
                    formalName = names[i];
                    break;
                }
            }
            
            // If we didn't find a second meaningful name, use the second name overall
            if (!formalName && names.length >= 2) {
                formalName = names[1];
            }
            
            // If still no formal name, use the first name
            if (!formalName) {
                formalName = names[0];
            }
            
            // Capitalize first letter
            return formalName.charAt(0).toUpperCase() + formalName.slice(1).toLowerCase();
        }

        // Function to set owner name in profile
        async function setOwnerName() {
            const ownerData = await getOwnerName();
            const farmName = await getFarmName();
            
            // Extract formal name for welcome message
            const formalName = extractFormalName(ownerData.name);
            
            document.getElementById('ownerName').textContent = formalName;
            document.getElementById('ownerWelcome').textContent = formalName;
            document.getElementById('profileName').textContent = ownerData.name;
            document.getElementById('profileFullName').textContent = ownerData.name;
            document.getElementById('profileFarmName').textContent = farmName;
            
            // Update profile photo display
            updateProfilePhotoDisplay(ownerData.profile_photo_url);
        }

        // Function to update profile photo display
        function updateProfilePhotoDisplay(photoUrl) {
            try {
                // Add timestamp to prevent cache issues
                const photoUrlWithTimestamp = photoUrl ? photoUrl + '?t=' + Date.now() : null;
                
                // Update header profile photo
                const headerPhoto = document.getElementById('headerProfilePhoto');
                const headerIcon = document.getElementById('headerProfileIcon');
                
                if (headerPhoto && headerIcon) {
                    if (photoUrlWithTimestamp) {
                        headerPhoto.src = photoUrlWithTimestamp;
                        headerPhoto.classList.remove('hidden');
                        headerIcon.classList.add('hidden');
                    } else {
                        headerPhoto.classList.add('hidden');
                        headerIcon.classList.remove('hidden');
                    }
                }
                
                // Update modal profile photo
                const modalPhoto = document.getElementById('modalProfilePhoto');
                const modalIcon = document.getElementById('modalProfileIcon');
                
                if (modalPhoto && modalIcon) {
                    if (photoUrlWithTimestamp) {
                        modalPhoto.src = photoUrlWithTimestamp;
                        modalPhoto.classList.remove('hidden');
                        modalIcon.classList.add('hidden');
                    } else {
                        modalPhoto.classList.add('hidden');
                        modalIcon.classList.remove('hidden');
                    }
                }
            } catch (error) {
                console.error('Erro ao atualizar exibição da foto de perfil:', error);
            }
        }

        // Load dashboard data from Supabase
        async function loadDashboardData() {
            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) return;

                // Load financial data
                const { data: financialData, error: financialError } = await supabase
                    .from('financial_records')
                    .select('*')
                    .gte('date', new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString());

                if (!financialError && financialData) {
                    const monthlyRevenue = financialData
                        .filter(record => record.type === 'revenue')
                        .reduce((sum, record) => sum + record.amount, 0);
                    
                    document.getElementById('monthlyRevenue').textContent = `R$ ${monthlyRevenue.toLocaleString('pt-BR')}`;
                }

                // Load production data
                const { data: productionData, error: productionError } = await supabase
                    .from('volume_records')
                    .select('volume_liters')
                    .gte('production_date', new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString());

                if (!productionError && productionData) {
                    const avgProduction = productionData.reduce((sum, record) => sum + record.volume_liters, 0) / productionData.length;
                    document.getElementById('dailyProduction').textContent = `${Math.round(avgProduction)} L`;
                }

                // Load team data
                const { data: teamData, error: teamError } = await supabase
                    .from('users')
                    .select('role')
                    .neq('role', 'proprietario');

                if (!teamError && teamData) {
                    document.getElementById('totalEmployees').textContent = teamData.length;
                }

                // Load animals data
                const { data: animalsData, error: animalsError } = await supabase
                    .from('animals')
                    .select('id');

                if (!animalsError && animalsData) {
                    document.getElementById('totalAnimals').textContent = animalsData.length;
                }

            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }

        // Load farm settings
        async function loadFarmSettings() {
            try {
                const { data, error } = await supabase
                    .from('farms')
                    .select('*')
                    .single();

                if (!error && data) {
                    document.getElementById('farmName').value = data.name || '';
                    document.getElementById('farmOwner').value = data.owner_name || '';
                    document.getElementById('farmEmail').value = data.email || '';
                    document.getElementById('farmPhone').value = data.phone || '';
                    document.getElementById('farmAddress').value = data.address || '';
                }
            } catch (error) {
                console.error('Error loading farm settings:', error);
            }
        }

        // Setup event listeners
        function setupEventListeners() {
            // Tab switching
            const navItems = document.querySelectorAll('.nav-item, .mobile-nav-item');
            const tabContents = document.querySelectorAll('.tab-content');

            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    
                    // Remove active class from all nav items
                    navItems.forEach(nav => nav.classList.remove('active'));
                    
                    // Add active class to clicked item
                    this.classList.add('active');
                    
                    // Hide all tab contents
                    tabContents.forEach(content => content.classList.add('hidden'));
                    
                    // Show target tab content
                    document.getElementById(targetTab + '-tab').classList.remove('hidden');
                });
            });

            // Form submissions
            document.getElementById('farmSettingsForm').addEventListener('submit', handleFarmSettings);
            document.getElementById('changePasswordForm').addEventListener('submit', handlePasswordChange);
            // Proprietário não possui formulário de adicionar usuário
            
            // Team filter
            document.getElementById('teamFilter').addEventListener('change', function() {
                filterTeamList(this.value);
            });
            
            // Email preview update
            document.getElementById('userName').addEventListener('input', function() {
                updateEmailPreview(this.value);
            });
        }

        // Handle farm settings form
        async function handleFarmSettings(e) {
            e.preventDefault();
            const formData = new FormData(e.target);

            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) throw new Error('User not authenticated');

                const farmData = {
                    name: formData.get('farm_name'),
                    owner_name: formData.get('owner_name'),
                    email: formData.get('email'),
                    phone: formData.get('phone'),
                    address: formData.get('address'),
                    updated_at: new Date().toISOString()
                };

                const { error } = await supabase
                    .from('farms')
                    .upsert([farmData]);

                if (error) throw error;

                showNotification('Configurações salvas com sucesso!', 'success');
                await setFarmName();

            } catch (error) {
                console.error('Error saving farm settings:', error);
                showNotification('Erro ao salvar configurações', 'error');
            }
        }

        // Handle password change
        async function handlePasswordChange(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const currentPassword = formData.get('current_password');
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');

            if (newPassword !== confirmPassword) {
                showNotification('As senhas não coincidem', 'error');
                return;
            }

            try {
                const { error } = await supabase.auth.updateUser({
                    password: newPassword
                });

                if (error) throw error;

                showNotification('Senha alterada com sucesso!', 'success');
                e.target.reset();

            } catch (error) {
                console.error('Error changing password:', error);
                showNotification('Erro ao alterar senha', 'error');
            }
        }

        // Handle add user
        // Generate email based on name and farm
        async function generateUserEmail(name, farmId) {
            try {
                // Get farm name
                const { data: farmData, error: farmError } = await supabase
                    .from('farms')
                    .select('name')
                    .eq('id', farmId)
                    .single();

                if (farmError) throw farmError;
                
                // Sanitizar o nome da fazenda (tudo minúsculo, sem espaço, sem acento)
                const farmName = farmData.name
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '') // Remove acentos
                    .replace(/\s+/g, '') // Remove espaços
                    .replace(/[^a-z0-9]/g, ''); // Remove caracteres especiais
                
                // Extrair o primeiro nome do usuário
                const firstName = name.trim().split(' ')[0]
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '') // Remove acentos
                    .replace(/[^a-z0-9]/g, ''); // Remove caracteres especiais
                
                let finalEmail;
                let attempts = 0;
                const maxAttempts = 50; // Evitar loop infinito
                
                // Verificar se o e-mail já existe no banco
                while (attempts < maxAttempts) {
                    // Gerar dois números aleatórios entre 10 e 99
                    const num1 = Math.floor(Math.random() * 90) + 10; // 10-99
                    const num2 = Math.floor(Math.random() * 90) + 10; // 10-99
                    
                    finalEmail = `${firstName}${num1}${num2}@${farmName}.lactech.com`;
                    
                    // Verificar se email já existe
                    const { data: existingUser, error } = await supabase
                        .from('users')
                        .select('id')
                        .eq('email', finalEmail)
                        .maybeSingle(); // Use maybeSingle() em vez de single()
                    
                    if (error) {
                        console.error('Error checking email:', error);
                        throw error;
                    }
                    
                    if (!existingUser) {
                        // Email disponível, sair do loop
                        break;
                    }
                    
                    attempts++;
                }
                
                if (attempts >= maxAttempts) {
                    throw new Error('Não foi possível gerar um email único após várias tentativas');
                }
                
                return finalEmail;
            } catch (error) {
                console.error('Error generating email:', error);
                throw error;
            }
        }
        
        // Update email preview
        async function updateEmailPreview(name) {
            const emailPreview = document.getElementById('emailPreview');
            
            if (!name || name.trim() === '') {
                emailPreview.textContent = 'Digite o nome para ver o email';
                return;
            }
            
            try {
                const { data: { user: currentUser } } = await supabase.auth.getUser();
                if (!currentUser) return;

                const { data: ownerData, error: ownerError } = await supabase
                    .from('users')
                    .select('farm_id')
                    .eq('id', currentUser.id)
                    .single();

                if (ownerError) throw ownerError;
                
                const email = await generateUserEmail(name, ownerData.farm_id);
                emailPreview.textContent = email;
            } catch (error) {
                emailPreview.textContent = 'Erro ao gerar email';
            }
        }
        
        // Send WhatsApp message with credentials
        async function sendWhatsAppCredentials(whatsapp, name, email, password) {
            try {
                // Format WhatsApp number (remove non-digits and add country code if needed)
                let formattedNumber = whatsapp.replace(/\D/g, '');
                if (!formattedNumber.startsWith('55')) {
                    formattedNumber = '55' + formattedNumber;
                }
                
                // Criar mensagem de credenciais
                 const message = `🌱 *LACTECH - Sistema de Gestão Leiteira* 🥛\n\n` +
                     `🎉 *Olá ${name}!*\n\n` +
                     `Suas credenciais de acesso foram criadas com sucesso:\n\n` +
                     `📧 *Email:* ${email}\n` +
                     `🔑 *Senha:* ${password}\n\n` +
                     `⚠️ *INSTRUÇÕES IMPORTANTES:*\n` +
                     `✅ Mantenha suas credenciais seguras\n` +
                     `✅ Não compartilhe com terceiros\n\n` +
                     `🌐 *Acesse o sistema:*\n` +
                     `http://localhost:8000/login\n\n` +
                     `📱 *Suporte técnico disponível*\n` +
                     `Em caso de dúvidas, entre em contato\n\n` +
                     `🚀 *Bem-vindo(a) à equipe LacTech!*\n` +
                     `Juntos, vamos revolucionar a gestão leiteira! 🐄💚`;
                
                // Copiar mensagem para área de transferência
                try {
                    await navigator.clipboard.writeText(message);
                    
                    // Mostrar modal com instruções
                    showWhatsAppInstructions(formattedNumber, name, message);
                    
                    return true;
                } catch (clipboardError) {
                    console.error('Erro ao copiar para área de transferência:', clipboardError);
                    // Fallback: mostrar modal mesmo sem copiar
                    showWhatsAppInstructions(formattedNumber, name, message);
                    return true;
                }
                
            } catch (error) {
                console.error('Error sending WhatsApp message:', error);
                return false;
            }
        }

        // Mostrar modal com instruções para envio manual
        function showWhatsAppInstructions(phoneNumber, userName, message) {
            // Criar modal se não existir
            let modal = document.getElementById('whatsappInstructionsModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'whatsappInstructionsModal';
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                modal.innerHTML = `
                    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">📱 Enviar Credenciais via WhatsApp</h3>
                            <button onclick="closeWhatsAppInstructions()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <p class="text-sm text-green-800">
                                    ✅ <strong>Mensagem copiada!</strong><br>
                                    As credenciais foram copiadas para sua área de transferência.
                                </p>
                            </div>
                            
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-gray-700">Para enviar as credenciais:</p>
                                <ol class="text-sm text-gray-600 space-y-1 list-decimal list-inside">
                                    <li>Abra o WhatsApp no seu celular ou computador</li>
                                    <li>Procure pelo contato: <strong>${phoneNumber}</strong></li>
                                    <li>Cole a mensagem (Ctrl+V) e envie</li>
                                </ol>
                            </div>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <p class="text-xs text-blue-800">
                                    💡 <strong>Dica:</strong> Você também pode clicar no botão abaixo para abrir o WhatsApp Web automaticamente.
                                </p>
                            </div>
                            
                            <div class="flex space-x-3">
                                <button onclick="openWhatsAppWeb('${phoneNumber}', '${encodeURIComponent(message)}')" 
                                        class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm">
                                    🌐 Abrir WhatsApp Web
                                </button>
                                <button onclick="copyMessageAgain('${encodeURIComponent(message)}')" 
                                        class="flex-1 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors text-sm">
                                    📋 Copiar Novamente
                                </button>
                            </div>
                            
                            <button onclick="closeWhatsAppInstructions()" 
                                    class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                                Fechar
                            </button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }
            
            // Atualizar conteúdo do modal
            const phoneElement = modal.querySelector('strong');
            if (phoneElement) {
                phoneElement.textContent = phoneNumber;
            }
            
            // Mostrar modal
            modal.style.display = 'flex';
        }

        // Fechar modal de instruções
        function closeWhatsAppInstructions() {
            const modal = document.getElementById('whatsappInstructionsModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Abrir WhatsApp Web (opção alternativa)
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

        // Proprietário não possui função de adicionar usuário - apenas visualização

        // Load team data
        async function loadTeamData() {
            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) return;

                // Get current user's farm_id
                const { data: ownerData, error: ownerError } = await supabase
                    .from('users')
                    .select('farm_id')
                    .eq('id', user.id)
                    .single();

                if (ownerError) throw ownerError;

                // Get all users from the same farm
                const { data: users, error: usersError } = await supabase
                    .from('users')
                    .select('*')
                    .eq('farm_id', ownerData.farm_id)
                    .order('created_at', { ascending: false });

                if (usersError) throw usersError;

                // Update team stats
                const totalUsers = users.length;
                const managersCount = users.filter(u => u.role === 'gerente').length;
                const employeesCount = users.filter(u => u.role === 'funcionario').length;
                const veterinariansCount = users.filter(u => u.role === 'veterinario').length;

                document.getElementById('totalUsers').textContent = totalUsers;
                document.getElementById('managersCount').textContent = managersCount;
                document.getElementById('employeesCount').textContent = employeesCount;
                document.getElementById('veterinariansCount').textContent = veterinariansCount;

                // Update team list
                displayTeamList(users);

            } catch (error) {
                console.error('Error loading team data:', error);
            }
        }

        // Display team list
        function displayTeamList(users) {
            const teamList = document.getElementById('teamList');
            
            if (users.length === 0) {
                teamList.innerHTML = `
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum Funcionário Cadastrado</h3>
                        <p class="text-gray-600 mb-4">Adicione funcionários para gerenciar sua equipe</p>
                        <button onclick="openAddUserModal()" class="px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Adicionar Primeiro Funcionário
                        </button>
                    </div>
                `;
                return;
            }

            const userCards = users.map(user => {
                const roleColors = {
                    'proprietario': 'bg-purple-100 text-purple-800',
                    'gerente': 'bg-blue-100 text-blue-800',
                    'funcionario': 'bg-green-100 text-green-800',
                    'veterinario': 'bg-orange-100 text-orange-800'
                };

                const roleIcons = {
                    'proprietario': 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    'gerente': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'funcionario': 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                    'veterinario': 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'
                };

                return `
                    <div class="bg-white border border-gray-200 rounded-xl p-4 hover:shadow-md transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 ${roleColors[user.role] || 'bg-gray-100 text-gray-800'} rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="${roleIcons[user.role] || roleIcons.funcionario}"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">${user.name}</h4>
                                    <p class="text-sm text-gray-600">${user.email}</p>
                                    <p class="text-xs text-gray-500">${user.whatsapp || 'WhatsApp não informado'}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${roleColors[user.role] || 'bg-gray-100 text-gray-800'}">
                                    ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                                </span>
                                <p class="text-xs text-gray-500 mt-1">
                                    ${new Date(user.created_at).toLocaleDateString('pt-BR')}
                                </p>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            teamList.innerHTML = userCards;
         }

         // Filter team list
         function filterTeamList(filterValue) {
             const teamCards = document.querySelectorAll('#teamList > div');
             
             teamCards.forEach(card => {
                 if (filterValue === 'all') {
                     card.style.display = 'block';
                 } else {
                     const roleSpan = card.querySelector('span');
                     if (roleSpan) {
                         const roleText = roleSpan.textContent.toLowerCase();
                         if (roleText.includes(filterValue)) {
                             card.style.display = 'block';
                         } else {
                             card.style.display = 'none';
                         }
                     }
                 }
             });
         }

        // Initialize charts
        function initializeCharts() {
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Receita (R$)',
                            data: [],
                            borderColor: '#369e36',
                            backgroundColor: 'rgba(54, 158, 54, 0.1)',
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
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Production Chart
            const productionCtx = document.getElementById('productionChart');
            if (productionCtx) {
                new Chart(productionCtx, {
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
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        }

        // Show notification
        function showNotification(message, type = 'success') {
            const toast = document.getElementById('notificationToast');
            const messageElement = document.getElementById('toastMessage');
            const iconElement = toast.querySelector('svg');
            
            messageElement.textContent = message;
            
            // Update icon and colors based on type
            if (type === 'error') {
                iconElement.classList.remove('text-green-400');
                iconElement.classList.add('text-red-400');
                iconElement.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>';
            } else {
                iconElement.classList.remove('text-red-400');
                iconElement.classList.add('text-green-400');
                iconElement.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
            }
            
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 5000);
        }

        // Hide notification
        function hideNotification() {
            document.getElementById('notificationToast').classList.remove('show');
        }

        // Profile modal functions
        function openProfileModal() {
            document.getElementById('profileModal').classList.add('show');
        }

        function closeProfileModal() {
            document.getElementById('profileModal').classList.remove('show');
        }

        // Proprietário não possui funções de modal de adicionar usuário - apenas visualização

        // Report generation functions
        async function generateFinancialReport() {
            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) throw new Error('User not authenticated');

                // Buscar dados financeiros
                const { data: financialData, error: financialError } = await supabase
                    .from('financial_records')
                    .select('*')
                    .order('created_at', { ascending: false });

                if (financialError) throw financialError;

                // Buscar dados de produção para calcular receita
                const { data: productionData, error: productionError } = await supabase
                    .from('volume_records')
                    .select('*')
                    .order('production_date', { ascending: false });

                if (productionError) throw productionError;

                // Gerar relatório financeiro em formato PDF
                await generateFinancialPDF(paymentsData, productionData);
                
                showNotification('Relatório Financeiro gerado com sucesso!', 'success');
            } catch (error) {
                console.error('Error generating financial report:', error);
                showNotification('Erro ao gerar relatório financeiro', 'error');
            }
        }

        async function generateProductionReport() {
            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) throw new Error('User not authenticated');

                // Buscar dados de produção
                const { data: productionData, error: productionError } = await supabase
                    .from('volume_records')
                    .select(`
                        *,
                        users(name, email)
                    `)
                    .order('production_date', { ascending: false });

                if (productionError) throw productionError;

                // Buscar dados de qualidade
                const { data: qualityData, error: qualityError } = await supabase
                    .from('quality_tests')
                    .select('*')
                    .order('created_at', { ascending: false });

                if (qualityError) throw qualityError;

                // Gerar relatório de produção em formato PDF
                await generateProductionReportPDF(productionData, qualityData);
                
                showNotification('Relatório de Produção gerado com sucesso!', 'success');
            } catch (error) {
                console.error('Error generating production report:', error);
                showNotification('Erro ao gerar relatório de produção', 'error');
            }
        }

        // Helper functions for PDF generation
        async function generateFinancialPDF(paymentsData, productionData) {
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();

                // Calcular receita total baseada na produção
                const totalProduction = productionData.reduce((sum, record) => sum + (parseFloat(record.volume_liters) || 0), 0);
                const pricePerLiter = 2.50; // Preço médio por litro
                const totalRevenue = totalProduction * pricePerLiter;

                // Calcular total de registros financeiros (receitas)
                const totalPayments = paymentsData.reduce((sum, payment) => sum + (parseFloat(payment.amount) || 0), 0);
                const pendingPayments = 0; // Não há status pendente em financial_records
                const completedPayments = totalPayments; // Todas as receitas são consideradas realizadas

                // Configurações
                const pageWidth = doc.internal.pageSize.getWidth();
                const pageHeight = doc.internal.pageSize.getHeight();
                const margin = 20;
                let yPosition = margin;

                // Título
                doc.setFontSize(18);
                doc.setFont('helvetica', 'bold');
                const titleText = window.reportSettings?.farmName
                    ? `RELATÓRIO FINANCEIRO - ${window.reportSettings.farmName}`
                    : 'RELATÓRIO FINANCEIRO';
                doc.text(titleText, margin, yPosition);
                yPosition += 20;

                // Data do relatório
                doc.setFontSize(12);
                doc.setFont('helvetica', 'normal');
                const today = new Date().toLocaleDateString('pt-BR');
                doc.text(`Relatório gerado em: ${today}`, margin, yPosition);
                yPosition += 20;

                // Resumo Financeiro
                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                doc.text('RESUMO FINANCEIRO', margin, yPosition);
                yPosition += 15;

                doc.setFontSize(12);
                doc.setFont('helvetica', 'normal');
                doc.text(`Receita Total: R$ ${totalRevenue.toFixed(2)}`, margin, yPosition);
                yPosition += 8;
                doc.text(`Pagamentos Realizados: R$ ${completedPayments.toFixed(2)}`, margin, yPosition);
                yPosition += 8;
                doc.text(`Pagamentos Pendentes: R$ ${pendingPayments.toFixed(2)}`, margin, yPosition);
                yPosition += 8;
                doc.text(`Saldo Líquido: R$ ${(totalRevenue - completedPayments).toFixed(2)}`, margin, yPosition);
                yPosition += 20;

                // Detalhamento dos Pagamentos
                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                doc.text('DETALHAMENTO DOS PAGAMENTOS', margin, yPosition);
                yPosition += 15;

                // Cabeçalho da tabela
                doc.setFontSize(10);
                doc.setFont('helvetica', 'bold');
                const headers = ['Data', 'Descrição', 'Valor (R$)', 'Status'];
                const colWidths = [30, 70, 30, 30];
                let xPosition = margin;

                headers.forEach((header, index) => {
                    doc.text(header, xPosition, yPosition);
                    xPosition += colWidths[index];
                });
                yPosition += 8;

                // Linha separadora
                doc.line(margin, yPosition, pageWidth - margin, yPosition);
                yPosition += 5;

                // Dados da tabela
                doc.setFont('helvetica', 'normal');
                paymentsData.forEach((payment) => {
                    if (yPosition > pageHeight - 30) {
                        doc.addPage();
                        yPosition = margin;
                    }

                    xPosition = margin;
                    const rowData = [
                        new Date(payment.created_at).toLocaleDateString('pt-BR'),
                        (payment.description || 'Receita').substring(0, 25),
                        `R$ ${payment.amount || '0'}`,
                        'Realizado' // Todas as receitas em financial_records são consideradas realizadas
                    ];

                    rowData.forEach((cell, cellIndex) => {
                        doc.text(String(cell), xPosition, yPosition);
                        xPosition += colWidths[cellIndex];
                    });
                    yPosition += 6;
                });

                // Download do PDF
                doc.save(`relatorio_financeiro_${new Date().toISOString().split('T')[0]}.pdf`);
            } catch (error) {
                console.error('Erro ao gerar PDF financeiro:', error);
                throw error;
            }
        }

        async function generateProductionReportPDF(productionData, qualityData) {
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();

                // Configurações
                const pageWidth = doc.internal.pageSize.getWidth();
                const pageHeight = doc.internal.pageSize.getHeight();
                const margin = 20;
                let yPosition = margin;

                // Título
                doc.setFontSize(18);
                doc.setFont('helvetica', 'bold');
                const titleText = window.reportSettings?.farmName
                    ? `RELATÓRIO DE PRODUÇÃO - ${window.reportSettings.farmName}`
                    : 'RELATÓRIO DE PRODUÇÃO';
                doc.text(titleText, margin, yPosition);
                yPosition += 20;

                // Data do relatório
                doc.setFontSize(12);
                doc.setFont('helvetica', 'normal');
                const today = new Date().toLocaleDateString('pt-BR');
                doc.text(`Relatório gerado em: ${today}`, margin, yPosition);
                yPosition += 20;

                // Resumo da Produção
                const totalVolume = productionData.reduce((sum, record) => sum + (parseFloat(record.volume_liters) || 0), 0);
                const avgQuality = qualityData.length > 0 ? {
                    fat: (qualityData.reduce((sum, q) => sum + (parseFloat(q.fat_percentage) || 0), 0) / qualityData.length).toFixed(2),
                    protein: (qualityData.reduce((sum, q) => sum + (parseFloat(q.protein_percentage) || 0), 0) / qualityData.length).toFixed(2)
                } : { fat: 'N/A', protein: 'N/A' };

                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                doc.text('RESUMO DA PRODUÇÃO', margin, yPosition);
                yPosition += 15;

                doc.setFontSize(12);
                doc.setFont('helvetica', 'normal');
                doc.text(`Volume Total: ${totalVolume.toFixed(2)} L`, margin, yPosition);
                yPosition += 8;
                doc.text(`Registros de Produção: ${productionData.length}`, margin, yPosition);
                yPosition += 8;
                doc.text(`Qualidade Média - Gordura: ${avgQuality.fat}% | Proteína: ${avgQuality.protein}%`, margin, yPosition);
                yPosition += 20;

                // Detalhamento da Produção
                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                doc.text('DETALHAMENTO DA PRODUÇÃO', margin, yPosition);
                yPosition += 15;

                // Cabeçalho da tabela
                doc.setFontSize(10);
                doc.setFont('helvetica', 'bold');
                const headers = ['Data', 'Funcionário', 'Volume (L)', 'Turno'];
                const colWidths = [30, 50, 30, 30];
                let xPosition = margin;

                headers.forEach((header, index) => {
                    doc.text(header, xPosition, yPosition);
                    xPosition += colWidths[index];
                });
                yPosition += 8;

                // Linha separadora
                doc.line(margin, yPosition, pageWidth - margin, yPosition);
                yPosition += 5;

                // Dados da tabela
                doc.setFont('helvetica', 'normal');
                productionData.forEach((record) => {
                    if (yPosition > pageHeight - 30) {
                        doc.addPage();
                        yPosition = margin;
                    }

                    xPosition = margin;
                    const rowData = [
                        new Date(record.production_date).toLocaleDateString('pt-BR'),
                        (record.users?.name || 'N/A').substring(0, 20),
                        record.volume_liters || 'N/A',
                        record.shift === 'manha' ? 'Manhã' : record.shift === 'tarde' ? 'Tarde' : 'Noite'
                    ];

                    rowData.forEach((cell, cellIndex) => {
                        doc.text(String(cell), xPosition, yPosition);
                        xPosition += colWidths[cellIndex];
                    });
                    yPosition += 6;
                });

                // Download do PDF
                doc.save(`relatorio_producao_${new Date().toISOString().split('T')[0]}.pdf`);
            } catch (error) {
                console.error('Erro ao gerar PDF de produção:', error);
                throw error;
            }
        }

        // Settings functions
        async function exportData() {
            if (confirm('Tem certeza que deseja exportar todos os dados?')) {
                try {
                    const { data: { user } } = await supabase.auth.getUser();
                    if (!user) throw new Error('User not authenticated');

                    // Buscar todos os dados da fazenda
                    const { data: userData, error: userError } = await supabase
                        .from('users')
                        .select('farm_id')
                        .eq('id', user.id)
                        .single();

                    if (userError) throw userError;
                    if (!userData?.farm_id) throw new Error('Farm not found');

                    // Buscar dados de produção
                    const { data: productionData, error: productionError } = await supabase
                        .from('volume_records')
                        .select(`
                            *,
                            users(name, email)
                        `)
                        .eq('farm_id', userData.farm_id)
                        .order('created_at', { ascending: false });

                    if (productionError) throw productionError;

                    // Buscar dados de qualidade
                    const { data: qualityData, error: qualityError } = await supabase
                        .from('quality_tests')
                        .select('*')
                        .eq('farm_id', userData.farm_id)
                        .order('created_at', { ascending: false });

                    if (qualityError) throw qualityError;

                    // Buscar dados financeiros
                    const { data: financialData, error: financialError } = await supabase
                        .from('financial_records')
                        .select('*')
                        .eq('farm_id', userData.farm_id)
                        .order('created_at', { ascending: false });

                    if (financialError) throw financialError;

                    // Buscar dados de usuários
                    const { data: usersData, error: usersError } = await supabase
                        .from('users')
                        .select('id, name, email, role, created_at')
                        .eq('farm_id', userData.farm_id)
                        .order('created_at', { ascending: false });

                    if (usersError) throw usersError;

                    // Buscar dados da fazenda
                    const { data: farmData, error: farmError } = await supabase
                        .from('farms')
                        .select('*')
                        .eq('id', userData.farm_id)
                        .single();

                    if (farmError) throw farmError;

                    // Criar arquivo JSON com todos os dados
                    const exportData = {
                        export_date: new Date().toISOString(),
                        farm: farmData,
                        users: usersData,
                        production: productionData,
                        quality_tests: qualityData,
                        financial_records: financialData
                    };

                    // Download do arquivo JSON
                    const jsonContent = JSON.stringify(exportData, null, 2);
                    const blob = new Blob([jsonContent], { type: 'application/json' });
                    const link = document.createElement('a');
                    const url = URL.createObjectURL(blob);
                    const today = new Date().toISOString().split('T')[0];
                    
                    link.setAttribute('href', url);
                    link.setAttribute('download', `backup_completo_${farmData.name.replace(/\s+/g, '_')}_${today}.json`);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    showNotification('Dados exportados com sucesso!', 'success');
                } catch (error) {
                    console.error('Error exporting data:', error);
                    showNotification('Erro ao exportar dados: ' + error.message, 'error');
                }
            }
        }

        async function resetSystem() {
            if (confirm('ATENÇÃO: Esta ação irá apagar todos os dados permanentemente. Tem certeza?')) {
                if (confirm('Esta ação é IRREVERSÍVEL. Confirma a exclusão de todos os dados?')) {
                    const confirmText = prompt('Digite "CONFIRMAR RESET" para prosseguir:');
                    if (confirmText === 'CONFIRMAR RESET') {
                        try {
                            const { data: { user } } = await supabase.auth.getUser();
                            if (!user) throw new Error('User not authenticated');

                            // Buscar farm_id do usuário
                            const { data: userData, error: userError } = await supabase
                                .from('users')
                                .select('farm_id')
                                .eq('id', user.id)
                                .single();

                            if (userError) throw userError;
                            if (!userData?.farm_id) throw new Error('Farm not found');

                            // Deletar dados em ordem (devido às foreign keys)
                            // 1. Deletar testes de qualidade
                            const { error: qualityError } = await supabase
                                .from('quality_tests')
                                .delete()
                                .eq('farm_id', userData.farm_id);

                            if (qualityError) throw qualityError;

                            // 2. Deletar produção de leite
                            const { error: productionError } = await supabase
                                .from('volume_records')
                                .delete()
                                .eq('farm_id', userData.farm_id);

                            if (productionError) throw productionError;

                            // 3. Deletar registros financeiros
                            const { error: financialError } = await supabase
                                .from('financial_records')
                                .delete()
                                .eq('farm_id', userData.farm_id);

                            if (financialError) throw financialError;

                            // 4. Deletar usuários (exceto o proprietário atual)
                            const { error: usersError } = await supabase
                                .from('users')
                                .delete()
                                .eq('farm_id', userData.farm_id)
                                .neq('id', user.id);

                            if (usersError) throw usersError;

                            showNotification('Sistema resetado com sucesso! Todos os dados foram removidos.', 'success');
                            
                            // Recarregar a página após 2 segundos
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                            
                        } catch (error) {
                            console.error('Error resetting system:', error);
                            showNotification('Erro ao resetar sistema: ' + error.message, 'error');
                        }
                    } else {
                        showNotification('Reset cancelado. Texto de confirmação incorreto.', 'info');
                    }
                }
            }
        }



        // Sign out function
        async function signOut() {
            if (confirm('Tem certeza que deseja sair?')) {
                await supabase.auth.signOut();
                window.location.href = 'index.php';
            }
        }
        
                    // App Version Display
        document.addEventListener('DOMContentLoaded', function() {
            // Adiciona versão do app no perfil do usuário
            const appVersion = '1.0.0';
            
            // Função para adicionar versão em elementos de perfil
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
            
            // Função para adicionar versão no modal de perfil
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
            
            // Adicionar versão quando o modal de perfil for aberto
            const originalOpenProfileModal = window.openProfileModal;
            window.openProfileModal = function() {
                if (originalOpenProfileModal) {
                    originalOpenProfileModal();
                }
                setTimeout(addVersionToProfileModal, 100);
            };
        });
        
        // Função para abrir Xandria Store com parâmetros de segurança
        function openXandriaStore() {
            try {
                const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
                const farmData = JSON.parse(localStorage.getItem('farmData') || '{}');
                
                const securityParams = new URLSearchParams({
                    role: 'proprietario',
                    userId: currentUser.id || 'unknown',
                    userName: encodeURIComponent(currentUser.name || 'unknown'),
                    farmId: currentUser.farm_id || 'unknown',
                    farmName: encodeURIComponent(farmData.name || 'unknown'),
                    timestamp: Date.now().toString()
                });
                
                window.open(`xandria-store.php?${securityParams.toString()}`, '_blank');
            } catch (error) {
                console.error('Erro ao abrir Xandria Store:', error);
                // Fallback para URL simples
                window.open('xandria-store.php?role=proprietario', '_blank');
            }
        }
        
        // Tornar função global
        window.openXandriaStore = openXandriaStore;
        
        // ==================== SISTEMA PWA ====================
        
        let deferredPrompt;
        const CURRENT_VERSION = '2.0.1';
        
        // Capturar o evento beforeinstallprompt
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA pode ser instalada');
            e.preventDefault();
            deferredPrompt = e;
        });
        
        // Detectar quando o app é instalado
        window.addEventListener('appinstalled', () => {
            console.log('PWA foi instalada');
            deferredPrompt = null;
        });
        
        // Função para instalar PWA
        async function installPWA() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`User response to the install prompt: ${outcome}`);
                deferredPrompt = null;
                
                // Verificar se realmente foi instalado
                setTimeout(() => {
                    if (checkRealInstallation()) {
                        showNotification('✅ App instalado com sucesso!', 'success');
                        const installData = {
                            version: CURRENT_VERSION,
                            timestamp: Date.now(),
                            userInfo: getCurrentUserInfo(),
                            url: window.location.href
                        };
                        localStorage.setItem('pwa_version', JSON.stringify(installData));
                    } else {
                        showNotification('❌ Falha na instalação. Tente novamente.', 'error');
                    }
                }, 2000);
            }
        }
        
        // Função para desinstalar PWA
        async function uninstallPWA() {
            if ('serviceWorker' in navigator) {
                const registrations = await navigator.serviceWorker.getRegistrations();
                for (let registration of registrations) {
                    await registration.unregister();
                }
            }
            localStorage.removeItem('pwa_version');
            showNotification('App desinstalado com sucesso!', 'success');
        }
        
        // Verificar se PWA está instalado
        function isPWAInstalled() {
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
            const installData = localStorage.getItem('pwa_version');
            const hasValidInstallData = installData && JSON.parse(installData).version === CURRENT_VERSION;
            
            return isStandalone || hasValidInstallData;
        }
        
        // Verificação real de instalação
        function checkRealInstallation() {
            return window.matchMedia('(display-mode: standalone)').matches;
        }
        
        // Obter informações do usuário atual
        function getCurrentUserInfo() {
            return {
                role: 'proprietario',
                timestamp: Date.now()
            };
        }
        
        // Verificar status PWA
        function checkPWAStatus() {
            const isInstalled = isPWAInstalled();
            const installData = localStorage.getItem('pwa_version');
            const installedVersion = installData ? JSON.parse(installData).version : null;
            
            return {
                isInstalled, 
                installedVersion, 
                currentVersion: CURRENT_VERSION,
                displayMode: window.matchMedia('(display-mode: standalone)').matches
            };
        }
        
        // ==================== FUNÇÕES DO MODAL PWA ====================
        
        // Abrir modal PWA
        function openPWAModal() {
            const modal = document.getElementById('pwaModal');
            modal.classList.remove('hidden');
            updatePWAStatusInModal();
        }
        
        // Fechar modal PWA
        function closePWAModal() {
            const modal = document.getElementById('pwaModal');
            modal.classList.add('hidden');
        }
        
        // Atualizar status no modal
        function updatePWAStatusInModal() {
            const status = checkPWAStatus();
            const statusContainer = document.getElementById('pwaStatus');
            
            if (status.isInstalled) {
                statusContainer.innerHTML = `
                    <div class="bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl p-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-green-800 dark:text-green-200">App Instalado</h4>
                                <p class="text-sm text-green-600 dark:text-green-400">Versão ${status.installedVersion || CURRENT_VERSION}</p>
                            </div>
                        </div>
                    </div>
                `;
                document.getElementById('modalInstallButton').classList.add('hidden');
                document.getElementById('modalUninstallButton').classList.remove('hidden');
            } else {
                statusContainer.innerHTML = `
                    <div class="bg-blue-100 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-blue-800 dark:text-blue-200">Pronto para Instalar</h4>
                                <p class="text-sm text-blue-600 dark:text-blue-400">Clique em "Instalar App" para adicionar à tela inicial</p>
                            </div>
                        </div>
                    </div>
                `;
                document.getElementById('modalInstallButton').classList.remove('hidden');
                document.getElementById('modalUninstallButton').classList.add('hidden');
            }
        }
        
        // Instalar PWA do modal
        async function installPWAFromModal() {
            await installPWA();
            setTimeout(() => {
                updatePWAStatusInModal();
            }, 1000);
        }
        
        // Desinstalar PWA do modal
        async function uninstallPWAFromModal() {
            await uninstallPWA();
            setTimeout(() => {
                updatePWAStatusInModal();
            }, 1000);
        }
        
        // Tornar funções PWA globais
        window.installPWA = installPWA;
        window.uninstallPWA = uninstallPWA;
        window.openPWAModal = openPWAModal;
        window.closePWAModal = closePWAModal;
        window.installPWAFromModal = installPWAFromModal;
        window.uninstallPWAFromModal = uninstallPWAFromModal;
        
    </script>

    <!-- Modal PWA Full Screen - Estilo Xandria Store -->
    <div id="pwaModal" class="fixed inset-0 bg-gray-50 dark:bg-play-dark z-[9999] hidden dark">
        <div class="w-full h-full flex flex-col overflow-y-auto">
            <!-- Header do Modal - Estilo Xandria Store -->
            <header class="bg-white dark:bg-play-dark no-border sticky top-0 z-50 backdrop-blur-sm bg-opacity-95 dark:bg-opacity-95">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 flex items-center justify-center">
                            <img src="assets/img/xandria-preta.png" alt="LacTech" class="w-8 h-8">
                        </div>
                        <div>
                            <h1 class="text-xl font-bold tracking-tight text-black dark:text-white">LacTech - Proprietário</h1>
                            <p class="text-xs text-gray-500 dark:text-gray-400 -mt-1">Sistema Agropecuário</p>
                        </div>
                    </div>
                    <button onclick="closePWAModal()" class="w-10 h-10 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl flex items-center justify-center transition-colors">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </header>

            <!-- Featured Banner -->
            <div class="px-6 py-6">
                <div class="max-w-4xl mx-auto">
                    <div class="rounded-3xl overflow-hidden shadow-xl card-hover cursor-pointer">
                        <img src="https://i.postimg.cc/7LcySj3K/agroneg-cio.png" alt="Banner LacTech" class="w-full h-48 sm:h-56 md:h-64 lg:h-72 object-cover">
                    </div>
                </div>
            </div>

            <!-- Conteúdo do Modal -->
            <div class="flex-1 px-6 pb-6">
                <div class="max-w-4xl mx-auto">
                    <!-- Status da Instalação - Layout Melhorado -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 rounded-2xl p-6 mb-6 border border-green-200 dark:border-green-800">
                        <div class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-4 mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Instalar App LacTech</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Acesso rápido ao sistema</p>
                            </div>
                        </div>
                        
                        <div id="pwaStatus" class="space-y-3">
                            <!-- Status será preenchido dinamicamente -->
                        </div>
                    </div>

                    <!-- Informações do Sistema - Layout 2 Colunas -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <!-- Gestão -->
                        <div class="bg-white dark:bg-play-card rounded-2xl p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">Gestão</h4>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs mt-1">Controle total</p>
                                </div>
                            </div>
                        </div>

                        <!-- Acesso Rápido -->
                        <div class="bg-white dark:bg-play-card rounded-2xl p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">Acesso Rápido</h4>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs mt-1">Da tela inicial</p>
                                </div>
                            </div>
                        </div>

                        <!-- Relatórios -->
                        <div class="bg-white dark:bg-play-card rounded-2xl p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">Relatórios</h4>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs mt-1">Gráficos e dados</p>
                                </div>
                            </div>
                        </div>

                        <!-- Interface Nativa -->
                        <div class="bg-white dark:bg-play-card rounded-2xl p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">Interface Nativa</h4>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs mt-1">Experiência nativa</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botões de Ação - Layout Melhorado -->
                    <div class="flex flex-col gap-3">
                        <button id="modalInstallButton" onclick="installPWAFromModal()" class="w-full px-6 py-4 bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold rounded-xl hover:from-green-600 hover:to-green-700 transition-all shadow-lg hidden">
                            <div class="flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                                </svg>
                                <span>Instalar App</span>
                            </div>
                        </button>
                        
                        <button id="modalUninstallButton" onclick="uninstallPWAFromModal()" class="w-full px-6 py-4 bg-gradient-to-r from-gray-500 to-gray-600 text-white font-semibold rounded-xl hover:from-gray-600 hover:to-gray-700 transition-all shadow-lg hidden">
                            <div class="flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"></path>
                                </svg>
                                <span>Desinstalar App</span>
                            </div>
                        </button>
                        
                        <button onclick="closePWAModal()" class="w-full px-6 py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all border border-gray-200 dark:border-gray-600">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>