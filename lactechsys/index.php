<<<<<<< HEAD
<?php
/**
 * Página inicial - Redirecionamento inteligente
 * Detecta automaticamente se está em localhost ou produção
 */
=======
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xandria - Sistemas para o Agronegócio</title>
    <meta name="description" content="Sistemas completos para gestão do agronegócio brasileiro">
    <link rel="icon" href="https://i.postimg.cc/W17q41wM/lactechpreta.png" type="image/png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
     <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
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
        
        tailwind.config = {
             darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#22c55e',
                         dark: {
                             bg: '#000000',
                             card: '#111111',
                             border: '#333333',
                             text: '#ffffff',
                             'text-secondary': '#cccccc',
                             'text-muted': '#999999'
                         }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    animation: {
                        'scroll': 'scroll 25s linear infinite',
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                        'pulse-subtle': 'pulseSubtle 2s ease-in-out infinite',
                        'float': 'float 3s ease-in-out infinite',
                    },
                    keyframes: {
                        scroll: {
                            '0%': { transform: 'translateX(0)' },
                            '100%': { transform: 'translateX(-100%)' },
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        pulseSubtle: {
                            '0%, 100%': { opacity: '0.5' },
                            '50%': { opacity: '1' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-5px)' },
                        },
                    },
                }
            }
        }
    </script>
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: #ffffff;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
             transition: background-color 0.3s ease, color 0.3s ease;
         }
         
         /* Modo Escuro */
         body.dark {
             background: #000000;
             color: #ffffff;
         }
         
         /* Estilos adicionais para modo escuro */
         body.dark nav {
             background: rgba(0, 0, 0, 0.9) !important;
             border-color: #333333 !important;
         }
         
         body.dark .text-gray-800 {
             color: #ffffff !important;
         }
         
         body.dark .text-gray-600 {
             color: #cccccc !important;
         }
         
         body.dark .text-gray-500 {
             color: #999999 !important;
         }
         
         body.dark .text-gray-700 {
             color: #cccccc !important;
         }
         
         body.dark .bg-white {
             background: #111111 !important;
         }
         
         body.dark .border-gray-100 {
             border-color: #333333 !important;
         }
         
         body.dark .bg-gray-50 {
             background: #111111 !important;
         }
         
         body.dark .bg-gray-100 {
             background: #333333 !important;
         }
         
         body.dark .bg-green-50 {
             background: #111111 !important;
         }
         
         body.dark .border-green-100 {
             border-color: #333333 !important;
         }
         
>>>>>>> parent of 0eb3d2f (.)

// Detectar se está em localhost
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
           strpos($_SERVER['SERVER_NAME'] ?? '', '192.168.') === 0 ||
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

// Detectar protocolo atual
$currentProtocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if ($isLocal && $currentProtocol === 'https') {
    // REDIRECIONAR DE HTTPS PARA HTTP EM LOCALHOST
    $httpUrl = 'http://' . $host . $_SERVER['REQUEST_URI'];
    header("Location: $httpUrl", true, 301);
    exit();
}

// Se chegou até aqui, redirecionar para login
header("Location: login.php", true, 302);
exit();
?>