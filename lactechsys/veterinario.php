<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Veterinário - Sistema Leiteiro</title>
    
    <!-- PWA Meta Tags -->
    <meta name="description" content="Painel do Veterinário - Sistema completo para gestão de produção leiteira, controle de qualidade e relatórios">
    <meta name="theme-color" content="#166534">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="LacTech Veterinário">
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
    
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2.39.0/dist/umd/supabase.min.js"></script>
    <script src="assets/js/config.js"></script>
    <script src="assets/js/modal-system.js"></script>
    <script src="assets/js/offline-manager.js"></script>
    <script src="assets/js/offline-loading.js"></script>
    <script src="assets/js/vet.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <script src="pwa-manager.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="assets/css/dark-theme-fixes.css?v=2.0" rel="stylesheet">
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
                        .select('id, name, email, role, farm_id, profile_photo_url, is_active')
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
        
        // Função para criar cliente Supabase
        function createSupabaseClient() {
            // Configuração direta (mesma do gerente que funciona)
            const supabaseUrl = 'https://tmaamwuyucaspqcrhuck.supabase.co';
            const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInV5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRtYWFtd3V5dWNhc3BxY3JodWNrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTY2OTY1MzMsImV4cCI6MjA3MjI3MjUzM30.AdDXp0xrX_xKutFHQrJ47LhFdLTtanTSku7fcK1eTB0';
            return window.supabase.createClient(supabaseUrl, supabaseKey);
        }
        
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
                        <h1 class="text-lg font-bold text-white tracking-tight">PAINEL VETERINÁRIO</h1>
                        <p class="text-xs text-forest-200" id="farmNameHeader">Carregando...</p>
                    </div>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex space-x-1">
                    <button class="nav-item active relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="dashboard">
                        Dashboard
                    </button>
                    <button class="nav-item relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="animals">
                        Animais
                    </button>
                    <button class="nav-item relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="treatments">
                        Tratamentos
                    </button>
                    <button class="nav-item relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="insemination">
                        Inseminação IA
                    </button>
                    <button class="nav-item relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="reports">
                        Relatórios
                    </button>
                </nav>

                <div class="flex items-center space-x-4">
                    <!-- Botão de retorno à conta do gerente -->
                    <div id="returnToManagerBtn" class="hidden">
                        <button onclick="VetApp.returnToManagerAccount()" class="flex items-center space-x-2 text-white hover:text-forest-200 p-2 rounded-lg transition-all bg-white bg-opacity-10 hover:bg-opacity-20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            <span class="text-sm font-medium">Voltar ao Gerente</span>
                        </button>
                    </div>
                    
                    <!-- Botão Xandria Store -->
                    <button onclick="openXandriaStore()" class="p-2 text-white hover:text-forest-200 transition-colors" title="Acessar Xandria Store">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"></path>
                        </svg>
                    </button>
                    
                    <button onclick="VetApp.openProfileModal()" class="flex items-center space-x-3 text-white hover:text-forest-200 p-2 rounded-lg transition-all">
                        <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="text-left hidden sm:block">
                            <div class="text-sm font-semibold" id="vetName">Carregando...</div>
                            <div class="text-xs text-forest-200">Veterinário</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-24 md:pb-4">
        
        <!-- Dashboard Tab -->
        <div id="dashboard-tab" class="tab-content">
            <!-- Welcome Section -->
            <div class="gradient-forest rounded-2xl p-6 mb-6 text-white shadow-xl relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold mb-2">Olá, Dr(a). <span id="vetWelcome">Carregando...</span>!</h2>
                            <p class="text-forest-200 text-base font-medium mb-3">Monitore a saúde do rebanho</p>
                            <div class="flex items-center space-x-4">
                                <div class="text-xs font-medium">Última atualização: Agora</div>
                            </div>
                        </div>
                        <div class="hidden sm:block">
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Health Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="data-card rounded-2xl p-4 text-center">
                    <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-xl font-bold text-slate-900 mb-1" id="healthyAnimals">--</div>
                    <div class="text-xs text-slate-500 font-medium">Saudáveis</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Animais</div>
                </div>
                
                <div class="data-card rounded-2xl p-4 text-center">
                    <div class="w-12 h-12 bg-yellow-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="text-xl font-bold text-slate-900 mb-1" id="warningAnimals">--</div>
                    <div class="text-xs text-slate-500 font-medium">Atenção</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Animais</div>
                </div>
                
                <div class="data-card rounded-2xl p-4 text-center">
                    <div class="w-12 h-12 bg-red-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-xl font-bold text-slate-900 mb-1" id="criticalAnimals">--</div>
                    <div class="text-xs text-slate-500 font-medium">Críticos</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Animais</div>
                </div>
                
                <div class="data-card rounded-2xl p-4 text-center">
                    <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                    <div class="text-xl font-bold text-slate-900 mb-1" id="activeTreatments">--</div>
                    <div class="text-xs text-slate-500 font-medium">Ativos</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Tratamentos</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Quick Health Check -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Registro Rápido</h3>
                        <div class="w-10 h-10 gradient-forest rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                    </div>
                    <form id="quickHealthForm" class="space-y-4">
                        <div class="form-floating">
                            <select id="quickHealthAnimalSelect" name="animal_id" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" required>
                                <option value="">Selecione o animal...</option>
                                <!-- Lista será preenchida dinamicamente -->
                            </select>
                            <label for="quickHealthAnimalSelect" class="text-slate-600">Animal (Brinco)</label>
                        </div>
                        <div class="form-floating">
                            <select id="quickHealthStatus" name="health_status" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" required>
                                <option value="">Selecione...</option>
                                <option value="Saudável">Saudável</option>
                                <option value="Em Tratamento">Em Tratamento</option>
                                <option value="Doente">Doente</option>
                                <option value="Recuperando">Recuperando</option>
                            </select>
                            <label for="quickHealthStatus" class="text-slate-600">Status de Saúde</label>
                        </div>
                        <button type="submit" class="w-full gradient-forest text-white font-semibold py-3 rounded-xl hover:shadow-lg transition-all">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Registrar Status Rápido
                        </button>
                    </form>
                </div>

                <!-- Recent Alerts -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Alertas Recentes</h3>
                        <button onclick="VetApp.showTab('animals')" class="text-forest-600 hover:text-forest-700 font-semibold text-sm">Ver Todos</button>
                    </div>
                    <div class="space-y-3" id="recentAlerts">
                        <div class="text-center py-8">
                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-5 5v-5zM4.828 4.828A4 4 0 015.5 4H9v1H5.5a3 3 0 00-2.121.879l-.707.707A1 1 0 012 7.414V11H1V7.414a2 2 0 01.586-1.414l.707-.707a5 5 0 013.535-1.465z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500 text-sm">Nenhum alerta recente</p>
                            <p class="text-gray-400 text-xs">Todos os animais estão bem</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Health Trends Chart -->
            <div class="data-card rounded-2xl p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Tendências de Saúde</h3>
                <div class="h-64 flex items-center justify-center">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-500 text-sm">Dados insuficientes para gráfico</p>
                        <p class="text-gray-400 text-xs">Registre mais dados para visualizar tendências</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Animals Tab -->
        <div id="animals-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Gestão de Animais</h2>
                            <p class="text-slate-600 text-sm">Monitore a saúde e histórico dos animais</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <select id="animalFilter" class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none">
                                <option value="all">Todos os animais</option>
                                <option value="healthy">Saudáveis</option>
                                <option value="warning">Atenção</option>
                                <option value="critical">Críticos</option>
                            </select>
                            <button onclick="VetApp.openAddAnimalModal()" class="px-4 py-2 bg-forest-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Adicionar Animal
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Animals List -->
                <div class="data-card rounded-2xl p-6">
                    <div class="space-y-4" id="animalsList">
                        <div class="text-center py-12">
                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum Animal Cadastrado</h3>
                            <p class="text-gray-600 mb-4">Comece adicionando animais ao sistema</p>
                            <button onclick="VetApp.openAddAnimalModal()" class="px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Adicionar Primeiro Animal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Treatments Tab -->
        <div id="treatments-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Tratamentos</h2>
                            <p class="text-slate-600 text-sm">Gerencie tratamentos e medicações</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <select id="treatmentFilter" class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none">
                                <option value="all">Todos os tratamentos</option>
                                <option value="active">Ativos</option>
                                <option value="completed">Concluídos</option>
                                <option value="pending">Pendentes</option>
                            </select>
                            <button onclick="VetApp.openAddTreatmentModal()" class="px-4 py-2 bg-forest-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Novo Tratamento
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Treatment Form -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Registrar Tratamento</h3>
                    <form id="treatmentForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Animal ID -->
                            <div class="form-floating">
                                <input type="text" id="treatmentAnimalId" name="animal_id" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" " required>
                                <label for="treatmentAnimalId" class="text-slate-600">ID do Animal *</label>
                            </div>

                            <!-- Treatment Type -->
                            <div class="form-floating">
                                <select id="treatmentType" name="treatment_type" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" required>
                                    <option value="">Selecione...</option>
                                    <option value="vaccination">Vacinação</option>
                                    <option value="medication">Medicação</option>
                                    <option value="surgery">Cirurgia</option>
                                    <option value="examination">Exame</option>
                                    <option value="other">Outro</option>
                                </select>
                                <label for="treatmentType" class="text-slate-600">Tipo de Tratamento *</label>
                            </div>

                            <!-- Medication -->
                            <div class="form-floating">
                                <input type="text" id="medication" name="medication" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" ">
                                <label for="medication" class="text-slate-600">Medicamento/Vacina</label>
                            </div>

                            <!-- Dosage -->
                            <div class="form-floating">
                                <input type="text" id="dosage" name="dosage" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" ">
                                <label for="dosage" class="text-slate-600">Dosagem</label>
                            </div>

                            <!-- Start Date -->
                            <div class="form-floating">
                                <input type="date" id="startDate" name="treatment_date" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" required>
                                <label for="startDate" class="text-slate-600">Data de Início *</label>
                            </div>

                            <!-- End Date -->
                            <div class="form-floating">
                                <input type="date" id="endDate" name="next_treatment_date" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none">
                                <label for="endDate" class="text-slate-600">Próximo Tratamento</label>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="form-floating">
                            <textarea id="treatmentNotes" name="observations" rows="3" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none resize-none" placeholder=" "></textarea>
                            <label for="treatmentNotes" class="text-slate-600">Observações</label>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex flex-col sm:flex-row gap-4 pt-4">
                            <button type="submit" class="flex-1 gradient-forest text-white font-semibold py-3 px-6 rounded-xl hover:shadow-lg transition-all">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Registrar Tratamento
                            </button>
                            <button type="button" onclick="VetApp.resetTreatmentForm()" class="px-6 py-3 border border-slate-300 text-slate-700 font-semibold rounded-xl hover:bg-slate-50 transition-all">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Limpar Formulário
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Treatments List -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Tratamentos Ativos</h3>
                    <div class="space-y-4" id="treatmentsList">
                        <div class="text-center py-12">
                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum Tratamento Ativo</h3>
                            <p class="text-gray-600 mb-4">Registre tratamentos para acompanhar a saúde dos animais</p>
                            <p class="text-gray-500 text-sm">Use o formulário acima para adicionar um novo tratamento</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Insemination Tab -->
        <div id="insemination-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Inseminação Artificial</h2>
                            <p class="text-slate-600 text-sm">Controle reprodutivo e registro de inseminações</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <select id="inseminationFilter" class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none">
                                <option value="all">Todas as inseminações</option>
                                <option value="confirmed">Gravidez confirmada</option>
                                <option value="pending">Aguardando confirmação</option>
                                <option value="failed">Não confirmada</option>
                            </select>
                            <button onclick="VetApp.openAddInseminationModal()" class="px-4 py-2 bg-forest-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Nova Inseminação
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Insemination Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="data-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="totalInseminations">--</div>
                        <div class="text-xs text-slate-500 font-medium">Total</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Inseminações</div>
                    </div>
                    
                    <div class="data-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="confirmedPregnancies">--</div>
                        <div class="text-xs text-slate-500 font-medium">Confirmadas</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Gestações</div>
                    </div>
                    
                    <div class="data-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-yellow-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="pendingConfirmations">--</div>
                        <div class="text-xs text-slate-500 font-medium">Pendentes</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Confirmações</div>
                    </div>
                    
                    <div class="data-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="successRate">--%</div>
                        <div class="text-xs text-slate-500 font-medium">Taxa de</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Sucesso</div>
                    </div>
                </div>

                <!-- Insemination Form -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Registrar Inseminação Artificial</h3>
                    <form id="inseminationForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Animal ID -->
                            <div class="form-floating">
                                <input type="text" id="inseminationAnimalId" name="animal_id" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" " required>
                                <label for="inseminationAnimalId" class="text-slate-600">ID do Animal *</label>
                            </div>

                            <!-- Insemination Date -->
                            <div class="form-floating">
                                <input type="datetime-local" id="inseminationDate" name="insemination_date" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" required>
                                <label for="inseminationDate" class="text-slate-600">Data e Hora da IA *</label>
                            </div>

                            <!-- Semen Batch -->
                            <div class="form-floating">
                                <input type="text" id="semenBatch" name="semen_batch" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" " required>
                                <label for="semenBatch" class="text-slate-600">Lote do Sêmen *</label>
                            </div>

                            <!-- Bull ID -->
                            <div class="form-floating">
                                <input type="text" id="bullId" name="bull_identification" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" ">
                                <label for="bullId" class="text-slate-600">ID do Touro</label>
                            </div>

                            <!-- Technician -->
                            <div class="form-floating">
                                <input type="text" id="technician" name="technician_name" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" ">
                                <label for="technician" class="text-slate-600">Técnico Responsável</label>
                            </div>

                            <!-- Heat Detection Method -->
                            <div class="form-floating">
                                <select id="heatDetectionMethod" name="technique_used" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none">
                                    <option value="">Selecione...</option>
                                    <option value="visual">Observação Visual</option>
                                    <option value="detector">Detector de Cio</option>
                                    <option value="hormonal">Sincronização Hormonal</option>
                                    <option value="other">Outro</option>
                                </select>
                                <label for="heatDetectionMethod" class="text-slate-600">Método de Detecção do Cio</label>
                            </div>

                            <!-- Body Condition Score -->
                            <div class="form-floating">
                                <select id="bodyConditionScore" name="body_condition_score" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none">
                                    <option value="">Selecione...</option>
                                    <option value="1">1 - Muito Magra</option>
                                    <option value="2">2 - Magra</option>
                                    <option value="3">3 - Ideal</option>
                                    <option value="4">4 - Gorda</option>
                                    <option value="5">5 - Muito Gorda</option>
                                </select>
                                <label for="bodyConditionScore" class="text-slate-600">Escore de Condição Corporal</label>
                            </div>

                            <!-- Expected Birth Date -->
                            <div class="form-floating">
                                <input type="date" id="expectedBirthDate" name="expected_calving_date" class="w-full px-3 py-4 border border-slate-200 rounded-xl bg-slate-50" readonly>
                                <label for="expectedBirthDate" class="text-slate-600">Data Prevista de Parto (calculada)</label>
                            </div>
                        </div>

                        <!-- Observations -->
                        <div class="form-floating">
                            <textarea id="inseminationObservations" name="observations" rows="3" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none resize-none" placeholder=" "></textarea>
                            <label for="inseminationObservations" class="text-slate-600">Observações Técnicas</label>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex flex-col sm:flex-row gap-4 pt-4">
                            <button type="submit" class="flex-1 gradient-forest text-white font-semibold py-3 px-6 rounded-xl hover:shadow-lg transition-all">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Registrar Inseminação
                            </button>
                            <button type="button" onclick="VetApp.resetInseminationForm()" class="px-6 py-3 border border-slate-300 text-slate-700 font-semibold rounded-xl hover:bg-slate-50 transition-all">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Limpar Formulário
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Inseminations List -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Histórico de Inseminações</h3>
                        <button onclick="VetApp.refreshInseminationsList()" class="text-forest-600 hover:text-forest-700 p-2 rounded-lg hover:bg-forest-50 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-4" id="inseminationsList">
                        <div class="text-center py-12">
                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma Inseminação Registrada</h3>
                            <p class="text-gray-600 mb-4">Registre inseminações para controlar o programa reprodutivo</p>
                            <p class="text-gray-500 text-sm">Use o formulário acima para adicionar uma nova inseminação</p>
                        </div>
                    </div>
                </div>

                <!-- Pregnancy Confirmation Section -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Confirmação de Gravidez</h3>
                    <form id="pregnancyConfirmationForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Insemination ID -->
                            <div class="form-floating">
                                <select id="inseminationSelect" name="insemination_id" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" required>
                                    <option value="">Selecione uma inseminação...</option>
                                </select>
                                <label for="inseminationSelect" class="text-slate-600">Inseminação *</label>
                            </div>

                            <!-- Pregnancy Status -->
                            <div class="form-floating">
                                <select id="pregnancyStatus" name="pregnancy_confirmed" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" required>
                                    <option value="">Selecione...</option>
                                    <option value="true">Confirmada</option>
                                    <option value="false">Não Confirmada</option>
                                </select>
                                <label for="pregnancyStatus" class="text-slate-600">Status da Gravidez *</label>
                            </div>

                            <!-- Confirmation Date -->
                            <div class="form-floating">
                                <input type="date" id="confirmationDate" name="pregnancy_confirmation_date" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" required>
                                <label for="confirmationDate" class="text-slate-600">Data da Confirmação *</label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex flex-col sm:flex-row gap-4 pt-4">
                            <button type="submit" class="flex-1 gradient-forest text-white font-semibold py-3 px-6 rounded-xl hover:shadow-lg transition-all">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Confirmar Gravidez
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reports Tab -->
        <div id="reports-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Relatórios Veterinários</h2>
                            <p class="text-slate-600 text-sm">Análises detalhadas da saúde do rebanho</p>
                        </div>
                    </div>
                </div>

                <!-- Report Options -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Health Report -->
                    <div class="data-card rounded-2xl p-6 text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">Relatório de Saúde</h3>
                        <p class="text-slate-600 text-sm mb-4">Status geral do rebanho</p>
                        <button onclick="VetApp.generateHealthReport()" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                            Gerar Relatório
                        </button>
                    </div>

                    <!-- Treatment Report -->
                    <div class="data-card rounded-2xl p-6 text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">Relatório de Tratamentos</h3>
                        <p class="text-slate-600 text-sm mb-4">Histórico de medicações</p>
                        <button onclick="VetApp.generateTreatmentReport()" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                            Gerar Relatório
                        </button>
                    </div>

                    <!-- Vaccination Report -->
                    <div class="data-card rounded-2xl p-6 text-center">
                        <div class="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">Relatório de Vacinação</h3>
                        <p class="text-slate-600 text-sm mb-4">Controle vacinal</p>
                        <button onclick="VetApp.generateVaccinationReport()" class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                            Gerar Relatório
                        </button>
                    </div>
                </div>

                <!-- Report Preview -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Prévia do Relatório</h3>
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Selecione um Relatório</h3>
                        <p class="text-gray-600 mb-4">Escolha uma das opções acima para gerar um relatório</p>
                        <p class="text-gray-500 text-sm">Os relatórios serão gerados com base nos dados veterinários</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Enhanced Mobile Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 md:hidden mobile-nav-enhanced shadow-2xl z-40">
        <div class="grid grid-cols-4 gap-1 p-2">
            <button class="mobile-nav-item active flex flex-col items-center py-3 px-2 transition-all" data-tab="dashboard">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span class="text-xs font-semibold">Dashboard</span>
            </button>
            <button class="mobile-nav-item flex flex-col items-center py-3 px-2 transition-all" data-tab="animals">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
                <span class="text-xs font-semibold">Animais</span>
            </button>
            <button class="mobile-nav-item flex flex-col items-center py-3 px-2 transition-all" data-tab="treatments">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                </svg>
                <span class="text-xs font-semibold">Tratamentos</span>
            </button>
            <button class="mobile-nav-item flex flex-col items-center py-3 px-2 transition-all" data-tab="insemination">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-xs font-semibold">Inseminação IA</span>
            </button>
            <button class="mobile-nav-item flex flex-col items-center py-3 px-2 transition-all" data-tab="reports">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="text-xs font-semibold">Relatórios</span>
            </button>
        </div>
    </nav>

    <!-- Modal de Perfil do Veterinário -->
    <div id="profileModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Header do Modal -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Perfil do Veterinário</h2>
                <button onclick="VetApp.closeProfileModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Conteúdo do Perfil -->
            <div class="p-6 space-y-6">
                <!-- Header do Perfil -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 gradient-forest rounded-2xl flex items-center justify-center shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900" id="profileName">Carregando...</h2>
                            <p class="text-slate-600 text-base">Veterinário</p>
                            <p class="text-sm text-slate-500" id="profileFarmName">Carregando...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Informações Profissionais -->
                <div id="professionalInfoSection" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Informações Profissionais</h4>
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
                            <label class="block text-sm font-medium text-gray-600 mb-1">Especialidade</label>
                            <p class="text-gray-900 font-medium" id="profileSpecialty">Carregando...</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Cargo</label>
                            <p class="text-gray-900 font-medium">Veterinário</p>
                        </div>
                    </div>
                </div>
                
                <!-- Alterar Senha -->
                <div id="changePasswordSection" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
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
                
                <!-- Minhas Solicitações de Senha -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Minhas Solicitações de Senha</h4>
                    <div class="flex items-center justify-between">
                        <div>
                            <h5 class="font-medium text-gray-900">Gerenciar Solicitações</h5>
                            <p class="text-sm text-gray-600">Visualize e cancele suas solicitações de alteração de senha</p>
                        </div>
                        <button onclick="openMyPasswordRequests()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                            Ver Solicitações
                        </button>
                    </div>
                </div>
                
                <!-- Conta Principal -->
                <div id="primaryAccountSection" class="bg-white rounded-2xl p-6 shadow-sm border border-blue-200" style="display: none;">
                    <h4 class="text-lg font-semibold text-blue-900 mb-4">Conta Principal</h4>
                    <div class="flex items-center justify-between">
                        <div>
                            <h5 class="font-medium text-blue-900">Retornar para Gerente</h5>
                            <p class="text-sm text-blue-600">Voltar para sua conta principal de gerente</p>
                        </div>
                        <button id="switchToPrimaryBtn" onclick="VetApp.switchToPrimaryAccount()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            Retornar
                        </button>
                    </div>
                </div>
                
                <!-- Sair do Sistema -->
                <div id="dangerZoneSection" class="bg-white rounded-2xl p-6 shadow-sm border border-red-200">
                    <h4 class="text-lg font-semibold text-red-900 mb-4">Zona de Perigo</h4>
                    <div class="flex items-center justify-between">
                        <div>
                            <h5 class="font-medium text-red-900">Sair do Sistema</h5>
                            <p class="text-sm text-red-600">Encerrar sua sessão atual</p>
                        </div>
                        <button onclick="VetApp.signOut()" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition-all">
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
                    <p class="text-sm font-medium text-gray-900" id="toastMessage">Registro salvo com sucesso!</p>
                </div>
                <div class="ml-auto pl-3">
                    <button onclick="VetApp.hideNotification()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar Animal -->
    <div id="addAnimalModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-forest-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-forest-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Adicionar Novo Animal</h3>
                        <p class="text-sm text-gray-500">Preencha as informações do animal</p>
                    </div>
                </div>
                <button onclick="VetApp.closeModal('addAnimalModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Form -->
            <form id="addAnimalForm" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Identificação *</label>
                        <input type="text" required name="identification" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all" placeholder="Ex: Vaca-001">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Nome</label>
                        <input type="text" name="name" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all" placeholder="Nome do animal">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Raça</label>
                        <input type="text" name="breed" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all" placeholder="Ex: Holandesa">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Data de Nascimento</label>
                        <input type="date" name="birth_date" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Gênero *</label>
                        <select required name="gender" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all">
                            <option value="">Selecione...</option>
                            <option value="Fêmea">Fêmea</option>
                            <option value="Macho">Macho</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Peso (kg)</label>
                        <input type="number" step="0.1" name="weight" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all" placeholder="0.0">
                    </div>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">Status de Saúde</label>
                    <select name="health_status" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all">
                        <option value="Saudável">Saudável</option>
                        <option value="Em Tratamento">Em Tratamento</option>
                        <option value="Doente">Doente</option>
                        <option value="Recuperando">Recuperando</option>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">Observações</label>
                    <textarea name="observations" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all resize-none" placeholder="Observações adicionais..."></textarea>
                </div>
                
                <!-- Footer -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <button type="button" onclick="VetApp.closeModal('addAnimalModal')" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-all font-medium">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-forest-600 hover:bg-forest-700 text-white font-semibold rounded-xl transition-all shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Adicionar Animal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Adicionar Tratamento -->
    <div id="addTreatmentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Adicionar Novo Tratamento</h3>
                        <p class="text-sm text-gray-500">Registre o tratamento do animal</p>
                    </div>
                </div>
                <button onclick="VetApp.closeModal('addTreatmentModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Form -->
            <form id="addTreatmentForm" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Animal *</label>
                        <select required name="animal_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all">
                            <option value="">Selecione o animal...</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Tipo de Tratamento *</label>
                        <select required name="treatment_type" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all">
                            <option value="">Selecione...</option>
                            <option value="Medicamento">Medicamento</option>
                            <option value="Vacina">Vacina</option>
                            <option value="Cirurgia">Cirurgia</option>
                            <option value="Exame">Exame</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Data de Início *</label>
                        <input type="date" required name="start_date" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Data de Término</label>
                        <input type="date" name="end_date" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all">
                    </div>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">Descrição *</label>
                    <textarea required name="description" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all resize-none" placeholder="Descreva o tratamento..."></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Medicamentos</label>
                        <input type="text" name="medications" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all" placeholder="Medicamentos utilizados">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Dosagem</label>
                        <input type="text" name="dosage" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all" placeholder="Ex: 10ml 2x/dia">
                    </div>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">Observações</label>
                    <textarea name="observations" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all resize-none" placeholder="Observações adicionais..."></textarea>
                </div>
                
                <!-- Footer -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <button type="button" onclick="VetApp.closeModal('addTreatmentModal')" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-all font-medium">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Adicionar Tratamento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Adicionar Inseminação -->
    <div id="addInseminationModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Nova Inseminação Artificial</h3>
                        <p class="text-sm text-gray-500">Registre a inseminação do animal</p>
                    </div>
                </div>
                <button onclick="VetApp.closeModal('addInseminationModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Form -->
            <form id="addInseminationForm" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Animal *</label>
                        <select required name="animal_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all">
                            <option value="">Selecione o animal...</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Data da Inseminação *</label>
                        <input type="date" required name="insemination_date" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Tipo de Sêmen</label>
                        <select name="semen_type" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all">
                            <option value="">Selecione...</option>
                            <option value="Fresco">Fresco</option>
                            <option value="Congelado">Congelado</option>
                            <option value="Sexado">Sexado</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Raça do Touro</label>
                        <input type="text" name="bull_breed" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all" placeholder="Ex: Holandês">
                    </div>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">Observações</label>
                    <textarea name="observations" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all resize-none" placeholder="Observações sobre a inseminação..."></textarea>
                </div>
                
                <!-- Footer -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <button type="button" onclick="VetApp.closeModal('addInseminationModal')" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-all font-medium">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl transition-all shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Registrar Inseminação
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Status de Saúde -->
    <div id="healthStatusModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Registrar Status de Saúde</h3>
                        <p class="text-sm text-gray-500">Avalie a saúde do animal</p>
                    </div>
                </div>
                <button onclick="VetApp.closeModal('healthStatusModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Form -->
            <form id="healthStatusForm" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Animal *</label>
                        <select required name="animal_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all">
                            <option value="">Selecione o animal...</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Data da Avaliação *</label>
                        <input type="date" required name="assessment_date" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all">
                    </div>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">Status de Saúde *</label>
                    <select required name="health_status" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all">
                        <option value="">Selecione...</option>
                        <option value="Saudável">Saudável</option>
                        <option value="Em Tratamento">Em Tratamento</option>
                        <option value="Doente">Doente</option>
                        <option value="Recuperando">Recuperando</option>
                        <option value="Crítico">Crítico</option>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">Sintomas</label>
                    <textarea name="symptoms" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all resize-none" placeholder="Descreva os sintomas observados..."></textarea>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">Diagnóstico</label>
                    <textarea name="diagnosis" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all resize-none" placeholder="Diagnóstico realizado..."></textarea>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">Recomendações</label>
                    <textarea name="recommendations" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none transition-all resize-none" placeholder="Recomendações para o animal..."></textarea>
                </div>
                
                <!-- Footer -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <button type="button" onclick="VetApp.closeModal('healthStatusModal')" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-all font-medium">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition-all shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Registrar Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Full Screen de Minhas Solicitações de Senha -->
    <div id="myPasswordRequestsModal" class="fixed inset-0 bg-white z-[99999] hidden flex flex-col" style="display: none !important;">
        <!-- Header -->
        <div class="flex-shrink-0 p-4 sm:p-6 bg-white border-b border-gray-200 shadow-lg">
                <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3 sm:space-x-6">
                    <div class="w-12 h-12 sm:w-16 sm:h-16 bg-forest-100 rounded-2xl sm:rounded-3xl flex items-center justify-center shadow-sm">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-forest-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                        <h2 class="text-lg sm:text-xl font-bold mb-1 text-black">Minhas Solicitações</h2>
                        <p class="text-gray-600 text-xs sm:text-sm">Histórico de suas solicitações de alteração de senha</p>
                        </div>
                    </div>
                <button onclick="closeMyPasswordRequestsModal()" class="w-10 h-10 sm:w-12 sm:h-12 lg:w-14 lg:h-14 flex items-center justify-center hover:bg-gray-100 rounded-xl sm:rounded-2xl transition-all duration-200">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Conteúdo -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <!-- Filtros e Ações -->
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <select id="myRequestsStatusFilter" class="px-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-forest-500 focus:border-transparent">
                            <option value="">Todos os status</option>
                            <option value="approved">Aprovadas</option>
                            <option value="rejected">Rejeitadas</option>
                            <option value="pending">Pendentes</option>
                        </select>
                        <select id="myRequestsDateFilter" class="px-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-forest-500 focus:border-transparent">
                            <option value="7">Últimos 7 dias</option>
                            <option value="15">Últimos 15 dias</option>
                            <option value="30" selected>Últimos 30 dias</option>
                            <option value="90">Últimos 90 dias</option>
                        </select>
                        </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="loadMyPasswordRequests()" id="refreshMyRequestsBtn" class="px-4 py-2 bg-forest-500 hover:bg-forest-600 text-white rounded-lg transition-colors duration-200 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span>Atualizar</span>
                        </button>
                        <button onclick="window.open('solicitar-alteracao-senha.html', '_blank')" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Nova Solicitação</span>
                    </button>
                    </div>
                </div>
                </div>
                
                <!-- Lista de Solicitações -->
            <div id="myPasswordRequestsList" class="space-y-4">
                <!-- Loading -->
                <div id="myRequestsLoading" class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-forest-500"></div>
                    <span class="ml-3 text-gray-600">Carregando solicitações...</span>
                </div>
                
                <!-- Empty State -->
                <div id="emptyMyPasswordRequests" class="hidden text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma solicitação encontrada</h3>
                    <p class="text-gray-500 mb-6">Você ainda não fez nenhuma solicitação de alteração de senha</p>
                    <button onclick="window.open('solicitar-alteracao-senha.html', '_blank')" class="px-6 py-3 bg-forest-600 hover:bg-forest-700 text-white font-medium rounded-lg transition-colors flex items-center space-x-2 mx-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Nova Solicitação</span>
                    </button>
                </div>
                </div>
            </div>
    </div>

    <!-- Script para funcionalidades de solicitações de senha -->
    <script>
        // Funções para gerenciar solicitações de senha do usuário
        async function openMyPasswordRequests() {
            const modal = document.getElementById('myPasswordRequestsModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                modal.style.visibility = 'visible';
                modal.style.opacity = '1';
                modal.style.pointerEvents = 'auto';
                modal.style.zIndex = '99999';
                
                // Carregar solicitações
                await loadMyPasswordRequests();
            }
        }
        
        function closeMyPasswordRequestsModal() {
            const modal = document.getElementById('myPasswordRequestsModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                modal.style.visibility = 'hidden';
                modal.style.opacity = '0';
                modal.style.pointerEvents = 'none';
                modal.style.zIndex = '-1';
            }
        }
        
        // Carregar solicitações do usuário atual
        async function loadMyPasswordRequests() {
            try {
                const btn = document.getElementById('refreshMyRequestsBtn');
                const loadingElement = document.getElementById('myRequestsLoading');
                const emptyElement = document.getElementById('emptyMyPasswordRequests');
                
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = `
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                        <span>Atualizando...</span>
                    `;
                }
                
                if (loadingElement) loadingElement.classList.remove('hidden');
                if (emptyElement) emptyElement.classList.add('hidden');
                
                const supabase = createSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (!user) {
                    showNotification('Usuário não autenticado', 'error');
                    return;
                }
                
                // Obter filtros
                const statusFilter = document.getElementById('myRequestsStatusFilter')?.value || '';
                const daysFilter = parseInt(document.getElementById('myRequestsDateFilter')?.value || '30');
                
                // Calcular data limite
                const dateLimit = new Date();
                dateLimit.setDate(dateLimit.getDate() - daysFilter);
                
                console.log('📋 Carregando minhas solicitações...');
                
                // Construir query com filtros
                let query = supabase
                    .from('password_requests')
                    .select('*')
                    .eq('user_id', user.id)
                    .gte('created_at', dateLimit.toISOString())
                    .order('created_at', { ascending: false });
                
                if (statusFilter) {
                    query = query.eq('status', statusFilter);
                }
                
                const { data: requests, error } = await query;
                
                if (error) {
                    console.error('Erro ao buscar solicitações:', error);
                    showNotification('Erro ao carregar solicitações', 'error');
                    return;
                }
                
                // Ocultar loading
                if (loadingElement) loadingElement.classList.add('hidden');
                
                console.log('📋 Solicitações carregadas:', requests?.length || 0);
                displayMyPasswordRequests(requests || []);
                
            } catch (error) {
                console.error('❌ Erro ao carregar solicitações:', error);
                showNotification('Erro ao carregar solicitações', 'error');
                const loadingElement = document.getElementById('myRequestsLoading');
                if (loadingElement) loadingElement.classList.add('hidden');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Atualizar</span>
                    `;
                }
            }
        }
        
        // Exibir solicitações do usuário
        function displayMyPasswordRequests(requests) {
            const listContainer = document.getElementById('myPasswordRequestsList');
            const emptyState = document.getElementById('emptyMyPasswordRequests');
            const loadingElement = document.getElementById('myRequestsLoading');
            
            if (!listContainer || !emptyState) return;
            
            // Ocultar loading
            if (loadingElement) loadingElement.classList.add('hidden');
            
            if (requests.length === 0) {
                listContainer.innerHTML = '';
                emptyState.classList.remove('hidden');
                return;
            }
            
            emptyState.classList.add('hidden');
            
            listContainer.innerHTML = requests.map(request => createMyRequestCard(request)).join('');
        }
        
        // Função para criar card de solicitação do usuário
        function createMyRequestCard(request) {
            const statusColors = {
                'pending': { bg: 'bg-yellow-50', border: 'border-yellow-200', text: 'text-yellow-800', icon: '⏳' },
                'approved': { bg: 'bg-green-50', border: 'border-green-200', text: 'text-green-800', icon: '✅' },
                'rejected': { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-800', icon: '❌' }
            };
            
            const status = statusColors[request.status] || statusColors['pending'];
            const createdDate = new Date(request.created_at).toLocaleDateString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
                
                return `
                <div class="bg-white rounded-xl border ${status.border} shadow-sm hover:shadow-md transition-all duration-200">
                    <div class="p-4 sm:p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="w-8 h-8 ${status.bg} rounded-lg flex items-center justify-center">
                                        <span class="text-sm">${status.icon}</span>
                                </div>
                                <div>
                                        <h3 class="font-semibold text-gray-900">Solicitação de Alteração de Senha</h3>
                                        <p class="text-sm text-gray-500">${createdDate}</p>
                                </div>
                            </div>
                            
                                <div class="space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-700">Status:</span>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full ${status.bg} ${status.text}">
                                            ${request.status === 'pending' ? 'Pendente' : 
                                              request.status === 'approved' ? 'Aprovada' : 'Rejeitada'}
                                        </span>
                        </div>
                        
                                    ${request.reason ? `
                                        <div class="flex items-start space-x-2">
                                            <span class="text-sm font-medium text-gray-700">Motivo:</span>
                                            <span class="text-sm text-gray-600">${request.reason}</span>
                                </div>
                            ` : ''}
                            
                                    ${request.admin_notes ? `
                                        <div class="flex items-start space-x-2">
                                            <span class="text-sm font-medium text-gray-700">Observações:</span>
                                            <span class="text-sm text-gray-600">${request.admin_notes}</span>
                                </div>
                            ` : ''}
                                </div>
                            </div>
                            
                            <div class="flex flex-col items-end space-y-2">
                                ${request.status === 'pending' ? `
                                    <button onclick="cancelMyRequest('${request.id}')" 
                                            class="px-3 py-1 text-xs font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors">
                                        Cancelar
                                    </button>
                            ` : ''}
                            </div>
                        </div>
                        </div>
                    </div>
                `;
        }
        
        // Função para cancelar solicitação
        async function cancelMyRequest(requestId) {
            if (!confirm('Tem certeza que deseja cancelar esta solicitação?')) return;
            
            try {
                const supabase = createSupabaseClient();
                const { error } = await supabase
                    .from('password_requests')
                    .update({ status: 'cancelled' })
                    .eq('id', requestId);
                
                if (error) throw error;
                
                showNotification('Solicitação cancelada com sucesso', 'success');
                await loadMyPasswordRequests();
            } catch (error) {
                    console.error('Erro ao cancelar solicitação:', error);
                    showNotification('Erro ao cancelar solicitação', 'error');
            }
        }
        
        // Adicionar event listeners para filtros
        document.addEventListener('DOMContentLoaded', function() {
            const statusFilter = document.getElementById('myRequestsStatusFilter');
            const dateFilter = document.getElementById('myRequestsDateFilter');
            
            if (statusFilter) {
                statusFilter.addEventListener('change', loadMyPasswordRequests);
            }
            
            if (dateFilter) {
                dateFilter.addEventListener('change', loadMyPasswordRequests);
            }
        });
        
        // Função para abrir Xandria Store com parâmetros de segurança
        function openXandriaStore() {
            try {
                const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
                const farmData = JSON.parse(localStorage.getItem('farmData') || '{}');
                
                const securityParams = new URLSearchParams({
                    role: 'veterinario',
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
                window.open('xandria-store.php?role=veterinario', '_blank');
            }
        }
        
        // Tornar funções globais
        window.openMyPasswordRequests = openMyPasswordRequests;
        window.closeMyPasswordRequestsModal = closeMyPasswordRequestsModal;
        window.cancelMyRequest = cancelMyRequest;
        window.openXandriaStore = openXandriaStore;
        
        // Sistema de verificação de logout automático por alteração de senha
        async function checkPasswordChangeLogout() {
            try {
                const supabase = createSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (!user) return;
                
                // Verificar sinais de logout no localStorage
                const signals = JSON.parse(localStorage.getItem('password_change_signals') || '[]');
                const userSignal = signals.find(signal => signal.userId === user.id);
                
                if (userSignal) {
                    // Remover sinal do localStorage
                    const updatedSignals = signals.filter(signal => signal.userId !== user.id);
                    localStorage.setItem('password_change_signals', JSON.stringify(updatedSignals));
                    
                    // Descriptografar senha se necessário
                    let displayPassword = userSignal.newPassword;
                    if (userSignal.encrypted) {
                        displayPassword = await decryptPassword(userSignal.newPassword);
                    }
                    
                    // Mostrar notificação e fazer logout
                    showPasswordChangeNotification(displayPassword);
                    
                    // Fazer logout após 5 segundos
                    setTimeout(async () => {
                        await supabase.auth.signOut();
                        window.location.href = 'login.html';
                    }, 5000);
                }
                
            } catch (error) {
                console.error('Erro ao verificar logout automático:', error);
            }
        }
        
        // Mostrar notificação de alteração de senha
        function showPasswordChangeNotification(newPassword) {
            // Criar modal de notificação
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-75 backdrop-blur-sm flex items-center justify-center z-[99999]';
            modal.innerHTML = `
                <div class="bg-white dark:bg-black rounded-2xl shadow-2xl max-w-md w-full mx-4 p-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Senha Alterada com Sucesso!</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Sua senha foi alterada pelo gerente. Por segurança, você será deslogado automaticamente em <span id="countdown">5</span> segundos.
                        </p>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-green-800">
                                <strong>Nova senha:</strong> <span class="font-mono">${newPassword}</span>
                            </p>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Use a nova senha para fazer login novamente.
                        </p>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Contador regressivo
            let countdown = 5;
            const countdownElement = document.getElementById('countdown');
            const interval = setInterval(() => {
                countdown--;
                if (countdownElement) {
                    countdownElement.textContent = countdown;
                }
                if (countdown <= 0) {
                    clearInterval(interval);
                }
            }, 1000);
        }
        
        // Sistema otimizado de verificação de logout automático
        let logoutCheckInterval = null;
        let lastLogoutCheck = 0;
        const LOGOUT_CHECK_INTERVAL = 30000; // 30 segundos em vez de 10
        
        function startLogoutChecker() {
            // Evitar múltiplos intervalos
            if (logoutCheckInterval) {
                clearInterval(logoutCheckInterval);
            }
            
            logoutCheckInterval = setInterval(async () => {
                const now = Date.now();
                // Evitar verificações muito frequentes
                if (now - lastLogoutCheck < LOGOUT_CHECK_INTERVAL) {
                    return;
                }
                lastLogoutCheck = now;
                
                await checkPasswordChangeLogout();
            }, LOGOUT_CHECK_INTERVAL);
        }
        
        function stopLogoutChecker() {
            if (logoutCheckInterval) {
                clearInterval(logoutCheckInterval);
                logoutCheckInterval = null;
            }
        }
        
        // Verificar imediatamente ao carregar a página
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(checkPasswordChangeLogout, 2000);
            startLogoutChecker();
        });
        
        // Limpar intervalos quando a página for descarregada
        window.addEventListener('beforeunload', () => {
            stopLogoutChecker();
        });
        
        // Sistema de criptografia simples para senhas
        async function decryptPassword(encryptedPassword) {
            try {
                if (encryptedPassword.startsWith('enc_')) {
                    const encrypted = encryptedPassword.substring(4);
                    // Para SHA-256, não podemos descriptografar, então retornamos o hash
                    // Em um sistema real, você usaria criptografia simétrica
                    return encrypted;
                }
                return encryptedPassword;
            } catch (error) {
                console.error('Erro ao descriptografar senha:', error);
                return encryptedPassword;
            }
        }
        
        // Tornar função global
        window.decryptPassword = decryptPassword;
        
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
                role: 'veterinario',
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
                            <h1 class="text-xl font-bold tracking-tight text-black dark:text-white">LacTech - Veterinário</h1>
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
                        <!-- Consultas -->
                        <div class="bg-white dark:bg-play-card rounded-2xl p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">Consultas</h4>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs mt-1">Registre consultas</p>
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
