<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xandria Store</title>
    
    <!-- PWA Meta Tags -->
    <meta name="description" content="Xandria Store - Hub de aplicações para o agronegócio">
    <meta name="theme-color" content="#01875f">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Xandria Store">
    
    <!-- PWA Icons -->
    <link rel="icon" type="image/png" href="https://i.postimg.cc/jjsq36Nf/lactechbranca-1.png">
    <link rel="apple-touch-icon" href="https://i.postimg.cc/jjsq36Nf/lactechbranca-1.png">
    <link rel="manifest" href="manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'play-green': '#01875f',
                        'play-blue': '#1976d2',
                        'play-dark': '#000000',
                        'play-card': '#111111',
                    },
                    fontFamily: {
                        'play': ['Google Sans', 'Roboto', 'Arial', 'sans-serif'],
                    },
                                         animation: {
                         'none': 'none',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&display=swap');
        
        .app-icon-shadow {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .dark .app-icon-shadow {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.6);
        }
        
        
         
         .xandria-logo {
             width: 120px;
             height: auto;
         }
        
                 .modal-fullscreen {
             position: fixed;
             top: 0;
             left: 0;
             width: 100%;
             height: 100%;
             background: #ffffff;
             z-index: 9999;
             overflow-y: auto;
         }
         
         .dark .modal-fullscreen {
             background: #000000;
         }

         .profile-modal {
              position: fixed;
              top: 80px;
              right: 20px;
              width: 320px;
              background: #ffffff;
              border: 1px solid #e5e7eb;
              border-radius: 16px;
              z-index: 9998;
              box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
          }
          
          .dark .profile-modal {
              background: #000000;
              border: 1px solid #374151;
              box-shadow: 0 20px 40px rgba(0, 0, 0, 0.8);
          }
         
         .profile-modal.hidden {
             display: none;
         }
         
         /* CSS para dispositivos com baixa DPI */
         @media screen and (max-resolution: 500dpi) {
             .screenshot-image {
                 min-width: 200px !important;
                 min-height: 280px !important;
                 width: 200px !important;
                 height: 280px !important;
             }
         }
         
         /* CSS para dispositivos com DPI muito baixo */
         @media screen and (max-resolution: 300dpi) {
             .screenshot-image {
                 min-width: 240px !important;
                 min-height: 320px !important;
                 width: 240px !important;
                 height: 320px !important;
             }
         }
        
        .gradient-text {
            background: linear-gradient(135deg, #01875f, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
                 .play-button {
             background: linear-gradient(135deg, #01875f, #059669);
         }
        
        .rating-bar {
            background: linear-gradient(90deg, #01875f 0%, #01875f var(--width), #374151 var(--width), #374151 100%);
        }

        .dark .no-border {
            border-color: transparent !important;
        }
        
        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.3);
            border-radius: 2px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.5);
        }

    </style>
</head>
<body class="bg-gray-50 dark:bg-play-dark text-gray-900 dark:text-white font-play transition-colors duration-300">
         <!-- Splash Screen -->
     <div id="splashScreen" class="fixed inset-0 bg-white dark:bg-play-dark flex items-center justify-center z-[9999] splash-screen">
         <div class="text-center">
             <div class="mb-8">
                 <img id="splashLogo" src="https://i.postimg.cc/jjsq36Nf/lactechbranca-1.png" alt="Xandria" class="xandria-logo mx-auto">
             </div>
             <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Xandria Store</h1>
             <p class="text-gray-600 dark:text-gray-400 text-lg">Sistemas para o Agronegócio</p>
         </div>
     </div>

         <!-- Main App -->
     <div id="mainApp" class="hidden">
        <!-- Header -->
        <header class="bg-white dark:bg-play-dark no-border sticky top-0 z-50 backdrop-blur-sm bg-opacity-95 dark:bg-opacity-95">
            <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center space-x-4">
                     <div class="w-10 h-10 flex items-center justify-center">
                         <img id="headerLogo" src="https://i.postimg.cc/jjsq36Nf/lactechbranca-1.png" alt="Xandria" class="w-8 h-8">
            </div>
                     <div>
                         <h1 class="text-xl font-bold tracking-tight gradient-text">Xandria Store</h1>
                         <p class="text-xs text-gray-500 dark:text-gray-400 -mt-1">Sistemas Agro</p>
                         <div id="detectedRoleIndicator" class="hidden flex items-center space-x-2 mt-1">
                             <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Módulo: <span id="detectedRoleText"></span></span>
                             <button onclick="openDetectedModule()" class="text-xs bg-green-600 text-white px-2 py-1 rounded-full hover:bg-green-700 transition-colors">
                                 Abrir
                             </button>
                         </div>
                     </div>
                 </div>
                <div class="flex items-center space-x-3">
                                         <button onclick="toggleSearch()" class="relative p-2 rounded-full">
                         <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"></path>
                    </svg>
                </button>
                     <button class="relative p-2 rounded-full">
                         <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z"></path>
                    </svg>
                </button>
                                         <button onclick="toggleProfileModal()" class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-lg cursor-pointer">
                         X
                </button>
            </div>
        </div>
    </header>

        <!-- Modal de Perfil -->
        <div id="profileModal" class="profile-modal hidden">
            <div class="p-6">
                <div class="flex items-center space-x-4 mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center text-white text-xl font-bold shadow-lg">
                        X
            </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Xandria User</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">xandria@agro.com</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <button onclick="showInstalledApps()" class="w-full flex items-center space-x-3 p-3 rounded-xl text-left hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <span class="text-gray-900 dark:text-white">Apps instalados</span>
                    </button>

                    <button onclick="showModuleSelector()" class="w-full flex items-center space-x-3 p-3 rounded-xl text-left hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"></path>
                        </svg>
                        <span class="text-gray-900 dark:text-white">Selecionar Módulo</span>
                    </button>

                    <button id="installPWAButton" onclick="installPWA()" class="w-full flex items-center space-x-3 p-3 rounded-xl text-left hover:bg-gray-100 dark:hover:bg-gray-800 hidden">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                        </svg>
                        <span class="text-gray-900 dark:text-white">Instalar App</span>
                    </button>

                    <button id="uninstallPWAButton" onclick="uninstallPWA()" class="w-full flex items-center space-x-3 p-3 rounded-xl text-left hover:bg-red-100 dark:hover:bg-red-900 hidden">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"></path>
                        </svg>
                        <span class="text-red-600 dark:text-red-400">Desinstalar App</span>
                    </button>

                    <button id="updatePWAButton" onclick="updatePWA()" class="w-full flex items-center space-x-3 p-3 rounded-xl text-left hover:bg-yellow-100 dark:hover:bg-yellow-900 hidden">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span class="text-yellow-600 dark:text-yellow-400">Atualizar App</span>
                    </button>


                    <button onclick="toggleTheme()" class="w-full flex items-center justify-between p-3 rounded-xl text-left hover:bg-gray-100 dark:hover:bg-gray-800">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"></path>
                            </svg>
                            <span class="text-gray-900 dark:text-white">Tema</span>
                    </div>
                        <div id="themeToggle" class="w-12 h-6 bg-gray-300 dark:bg-gray-700 rounded-full relative cursor-pointer">
                            <div id="themeSlider" class="w-5 h-5 bg-white rounded-full absolute top-0.5 transform translate-x-6"></div>
                </div>
                    </button>

                    <button onclick="openSettings()" class="w-full flex items-center space-x-3 p-3 rounded-xl text-left hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span class="text-gray-900 dark:text-white">Configurações</span>
                    </button>

                    <button onclick="openHelp()" class="w-full flex items-center space-x-3 p-3 rounded-xl text-left hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"></path>
                        </svg>
                        <span class="text-gray-900 dark:text-white">Ajuda e suporte</span>
                    </button>

                    <div class="border-t border-gray-300 dark:border-gray-700 my-4"></div>

                    <button onclick="logout()" class="w-full flex items-center space-x-3 p-3 rounded-xl text-left text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"></path>
                        </svg>
                        <span>Sair da conta</span>
                    </button>
                </div>
                    </div>
                </div>

        <!-- Modal de Seleção de Módulo -->
        <div id="moduleSelectorModal" class="profile-modal hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Selecionar Módulo</h3>
                    <button onclick="document.getElementById('moduleSelectorModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-3">
                    <button onclick="selectModule('gerente')" class="w-full flex items-center space-x-3 p-4 rounded-xl text-left hover:bg-gray-100 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white text-lg font-bold">
                            G
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">Gerente</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Painel administrativo completo</p>
                        </div>
                    </button>

                    <button onclick="selectModule('funcionario')" class="w-full flex items-center space-x-3 p-4 rounded-xl text-left hover:bg-gray-100 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center text-white text-lg font-bold">
                            F
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">Funcionário</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Controle de produção e atividades</p>
                        </div>
                    </button>

                    <button onclick="selectModule('veterinario')" class="w-full flex items-center space-x-3 p-4 rounded-xl text-left hover:bg-gray-100 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center text-white text-lg font-bold">
                            V
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">Veterinário</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Gestão de saúde animal</p>
                        </div>
                    </button>

                    <button onclick="selectModule('proprietario')" class="w-full flex items-center space-x-3 p-4 rounded-xl text-left hover:bg-gray-100 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white text-lg font-bold">
                            P
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">Proprietário</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Visão geral e relatórios</p>
                        </div>
                    </button>
                </div>
                    </div>
                </div>

                 <!-- Search Bar -->
         <div id="searchBar" class="bg-white dark:bg-play-dark px-6 py-3 no-border hidden">
             <div class="relative">
                 <input type="text" id="searchInput" placeholder="Pesquisar apps..." class="w-full px-4 py-3 pl-12 bg-gray-100 dark:bg-gray-800 border-0 rounded-2xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500">
                 <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                     <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"></path>
                        </svg>
                 <button onclick="clearSearch()" class="absolute right-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                     <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                 </button>
                    </div>
                </div>

         <!-- Category Tabs -->
         <div class="bg-white dark:bg-play-dark px-6 py-3 no-border">
             <div class="flex space-x-8 overflow-x-auto">
                 <button class="text-play-green font-semibold text-sm whitespace-nowrap border-b-2 border-play-green pb-3 transition-colors">
                     Para você
                 </button>
                 <button class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 text-sm whitespace-nowrap pb-3 transition-colors">
                     Top gráficos
                 </button>
                 <button class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 text-sm whitespace-nowrap pb-3 transition-colors">
                     Categorias
                 </button>
                 <button class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 text-sm whitespace-nowrap pb-3 transition-colors">
                     Editor's Choice
                 </button>
                    </div>
                </div>

        <!-- Main Content -->
        <main class="pb-8 bg-gray-50 dark:bg-play-dark">
                                                  <!-- Featured Banner -->
             <div class="px-6 py-6">
                 <div class="max-w-4xl mx-auto">
                     <div class="rounded-3xl overflow-hidden shadow-xl card-hover cursor-pointer">
                         <img src="https://i.postimg.cc/7LcySj3K/agroneg-cio.png" alt="Banner Xandria" class="w-full h-48 sm:h-56 md:h-64 lg:h-72 object-cover">
                    </div>
                    </div>
                </div>

            <!-- Sistemas Disponíveis -->
            <section class="px-6 py-4">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Sistemas Disponíveis</h2>
                                     <button class="text-play-green font-medium text-sm">
                     Ver mais
                 </button>
                    </div>

                <div class="space-y-4">
                                         <!-- LacTech App -->
                     <div class="bg-white dark:bg-play-card rounded-3xl p-6 cursor-pointer card-hover" onclick="openAppDetail('LacTech')">
                         <div class="flex items-start space-x-4">
                             <div class="w-20 h-20 bg-gradient-to-br from-white to-white rounded-3xl flex items-center justify-center app-icon-shadow">
                                 <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="LacTech" class="w-16 h-16 rounded-2xl">
                    </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-1">LacTech</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-2">Gestão completa de fazendas leiteiras</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500 mb-3">Xandria Systems • Produção</p>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex items-center">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">5.0</span>
                                            <svg class="w-4 h-4 ml-1 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    </div>
                                        <span class="text-sm text-gray-500 dark:text-gray-500">2.1MB</span>
                                        <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-medium rounded-full">
                                            Gratuito
                                        </span>
                </div>
                                                         <button onclick="installApp('LacTech', event)" class="px-6 py-2 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white font-medium rounded-full text-sm">
                         Instalar
                     </button>
            </div>
                            </div>
                        </div>
                    </div>

                                         <!-- AgroSmart App -->
                     <div class="bg-white dark:bg-play-card rounded-3xl p-6 cursor-pointer card-hover opacity-60">
                         <div class="flex items-start space-x-4">
                             <div class="w-20 h-20 bg-gradient-to-br from-white to-white rounded-3xl flex items-center justify-center app-icon-shadow">
                                 <img src="https://i.postimg.cc/sxTmhCSX/logos.png" alt="AgroSmart" class="w-20 h-20 rounded-3xl">
                             </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-1">AgroSmart</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-2">Agricultura inteligente com IA</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500 mb-3">Xandria Systems • Em desenvolvimento</p>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex items-center">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">--</span>
            </div>
                                        <span class="text-sm text-gray-500 dark:text-gray-500">--</span>
                                        <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-medium rounded-full">
                                            Em breve
                                        </span>
                    </div>
                                    <button disabled class="px-6 py-2 bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 font-medium rounded-full text-sm cursor-not-allowed">
                                        Em breve
                                    </button>
                    </div>
                </div>
                    </div>
                    </div>
                </div>
            </section>
        </main>
                </div>

         <!-- Modal de Apps Instalados -->
     <div id="installedAppsModal" class="modal-fullscreen hidden">
         <div class="p-6">
             <div class="flex items-center justify-between mb-8">
                 <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Apps Instalados</h2>
                 <button onclick="closeInstalledApps()" class="p-2 rounded-full">
                     <svg class="w-6 h-6 text-gray-600 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                     </svg>
                 </button>
                    </div>
             
             <div id="installedAppsList" class="space-y-4">
                 <div class="text-center py-12">
                     <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                     <p class="text-gray-500 dark:text-gray-400 text-lg">Nenhum app instalado ainda</p>
                     <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Instale apps da loja para vê-los aqui</p>
                    </div>
                </div>
                    </div>
     </div>

         <!-- Modal de Detalhes do App -->
     <div id="appDetailModal" class="modal-fullscreen hidden">
         <div class="p-6">
             <!-- Header do modal -->
             <div class="flex items-center justify-between mb-8">
                 <button onclick="closeAppDetail()" class="p-2 rounded-full">
                     <svg class="w-6 h-6 text-gray-600 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                        </svg>
                 </button>
                 <div class="flex space-x-2">
                     <button class="p-2 rounded-full">
                         <svg class="w-6 h-6 text-gray-600 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                             <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                         </svg>
                     </button>
                     <button class="p-2 rounded-full">
                         <svg class="w-6 h-6 text-gray-600 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                             <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z"></path>
                         </svg>
                     </button>
                    </div>
                </div>

             <!-- Conteúdo do app -->
             <div id="appDetailContent">
                 <!-- Conteúdo será preenchido dinamicamente -->
                    </div>
                    </div>
                </div>

     <!-- Modal de Configurações -->
     <div id="settingsModal" class="modal-fullscreen hidden">
         <div class="p-6">
             <div class="flex items-center justify-between mb-8">
                 <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Configurações</h2>
                 <button onclick="closeSettings()" class="p-2 rounded-full">
                     <svg class="w-6 h-6 text-gray-600 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                 </button>
                    </div>
             
             <div class="space-y-6">
                                   <!-- Notificações -->
                  <div class="bg-gray-100 dark:bg-gray-900 rounded-2xl p-6">
                      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notificações</h3>
                      <div class="space-y-4">
                          <div class="flex items-center justify-between">
                              <div>
                                  <p class="text-gray-900 dark:text-white font-medium">Notificações push</p>
                                  <p class="text-gray-600 dark:text-gray-400 text-sm">Receber notificações de novos apps</p>
                </div>
                              <div id="notifications-push" onclick="toggleSetting('notificationsPush')" class="w-12 h-6 bg-gray-300 dark:bg-gray-700 rounded-full relative cursor-pointer transition-colors">
                                  <div class="toggle-slider w-5 h-5 bg-white rounded-full absolute top-0.5 transform translate-x-6 transition-transform"></div>
            </div>
                          </div>
                          <div class="flex items-center justify-between">
                              <div>
                                  <p class="text-gray-900 dark:text-white font-medium">Atualizações automáticas</p>
                                  <p class="text-gray-600 dark:text-gray-400 text-sm">Instalar atualizações automaticamente</p>
                              </div>
                              <div id="notifications-updates" onclick="toggleSetting('notificationsUpdates')" class="w-12 h-6 bg-gray-300 dark:bg-gray-700 rounded-full relative cursor-pointer transition-colors">
                                  <div class="toggle-slider w-5 h-5 bg-white rounded-full absolute top-0.5 transform translate-x-0.5 transition-transform"></div>
                              </div>
                          </div>
                    </div>
                </div>

                  <!-- Privacidade -->
                  <div class="bg-gray-100 dark:bg-gray-900 rounded-2xl p-6">
                      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Privacidade</h3>
                      <div class="space-y-4">
                          <div class="flex items-center justify-between">
                              <div>
                                  <p class="text-gray-900 dark:text-white font-medium">Coleta de dados</p>
                                  <p class="text-gray-600 dark:text-gray-400 text-sm">Permitir coleta de dados de uso</p>
            </div>
                              <div id="privacy-data" onclick="toggleSetting('privacyData')" class="w-12 h-6 bg-gray-300 dark:bg-gray-700 rounded-full relative cursor-pointer transition-colors">
                                  <div class="toggle-slider w-5 h-5 bg-white rounded-full absolute top-0.5 transform translate-x-0.5 transition-transform"></div>
                    </div>
                          </div>
                          <div class="flex items-center justify-between">
                              <div>
                                  <p class="text-gray-900 dark:text-white font-medium">Análise de uso</p>
                                  <p class="text-gray-600 dark:text-gray-400 text-sm">Compartilhar dados de uso anônimos</p>
                              </div>
                              <div id="privacy-analytics" onclick="toggleSetting('privacyAnalytics')" class="w-12 h-6 bg-gray-300 dark:bg-gray-700 rounded-full relative cursor-pointer transition-colors">
                                  <div class="toggle-slider w-5 h-5 bg-white rounded-full absolute top-0.5 transform translate-x-0.5 transition-transform"></div>
                              </div>
                          </div>
                    </div>
                </div>

                                     <!-- Armazenamento -->
                   <div class="bg-gray-100 dark:bg-gray-900 rounded-2xl p-6">
                       <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Armazenamento</h3>
                       <div class="space-y-4">
                           <div class="flex items-center justify-between">
                               <div>
                                   <p class="text-gray-900 dark:text-white font-medium">Limpar cache</p>
                                   <p class="text-gray-600 dark:text-gray-400 text-sm">Liberar espaço de armazenamento</p>
                    </div>
                               <button onclick="clearCache()" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-medium rounded-full text-sm transition-colors">
                                   Limpar
                               </button>
                           </div>
                           <div class="flex items-center justify-between">
                               <div>
                                   <p class="text-gray-900 dark:text-white font-medium">Dados salvos</p>
                                   <p id="storage-info" class="text-gray-600 dark:text-gray-400 text-sm">0.0MB de dados salvos</p>
                               </div>
                           </div>
                    </div>
                </div>

                   <!-- Teste de Notificações -->

                 <!-- Sobre -->
                 <div class="bg-gray-100 dark:bg-gray-900 rounded-2xl p-6">
                     <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sobre</h3>
                     <div class="space-y-3">
                         <div class="flex justify-between">
                             <span class="text-gray-600 dark:text-gray-400">Versão</span>
                             <span class="text-gray-900 dark:text-white">2.4.1</span>
                    </div>
                         <div class="flex justify-between">
                             <span class="text-gray-600 dark:text-gray-400">Desenvolvido por</span>
                             <span class="text-gray-900 dark:text-white">Xandria Systems</span>
                    </div>
                         <div class="flex justify-between">
                             <span class="text-gray-600 dark:text-gray-400">Licença</span>
                             <span class="text-gray-900 dark:text-white">Proprietária</span>
                </div>
            </div>
                 </div>
             </div>
         </div>
     </div>

     <!-- Modal de Ajuda e Suporte -->
     <div id="helpModal" class="modal-fullscreen hidden">
         <div class="p-6">
             <div class="flex items-center justify-between mb-8">
                 <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Ajuda e Suporte</h2>
                 <button onclick="closeHelp()" class="p-2 rounded-full">
                     <svg class="w-6 h-6 text-gray-600 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                 </button>
             </div>
             
             <div class="space-y-6">
                 <!-- Perguntas Frequentes -->
                 <div class="bg-gray-100 dark:bg-gray-900 rounded-2xl p-6">
                     <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Perguntas Frequentes</h3>
                     <div class="space-y-4">
                         <div class="border-b border-gray-300 dark:border-gray-700 pb-4">
                             <h4 class="text-gray-900 dark:text-white font-medium mb-2">Como instalar um app?</h4>
                             <p class="text-gray-600 dark:text-gray-400 text-sm">Clique no botão "Instalar" no card do app desejado. O app será baixado e instalado automaticamente.</p>
            </div>
                         <div class="border-b border-gray-300 dark:border-gray-700 pb-4">
                             <h4 class="text-gray-900 dark:text-white font-medium mb-2">Como gerenciar apps instalados?</h4>
                             <p class="text-gray-600 dark:text-gray-400 text-sm">Acesse o menu de perfil e clique em "Apps instalados" para ver e gerenciar todos os apps.</p>
                    </div>
                         <div class="border-b border-gray-300 dark:border-gray-700 pb-4">
                             <h4 class="text-gray-900 dark:text-white font-medium mb-2">Como alterar o tema?</h4>
                             <p class="text-gray-600 dark:text-gray-400 text-sm">No menu de perfil, use o toggle "Tema" para alternar entre modo claro e escuro.</p>
                         </div>
                         <div>
                             <h4 class="text-gray-900 dark:text-white font-medium mb-2">Como entrar em contato com o suporte?</h4>
                             <p class="text-gray-600 dark:text-gray-400 text-sm">Use os canais de contato listados abaixo ou envie um email para suporte@xandria.com</p>
                         </div>
                    </div>
                </div>

                 <!-- Contato -->
                 <div class="bg-gray-100 dark:bg-gray-900 rounded-2xl p-6">
                     <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contato</h3>
                     <div class="space-y-4">
                         <div class="flex items-center space-x-3">
                             <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                 <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                             </svg>
                             <span class="text-gray-900 dark:text-white">suporte@xandria.com</span>
                    </div>
                         <div class="flex items-center space-x-3">
                             <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                 <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                             <span class="text-gray-900 dark:text-white">(11) 99999-9999</span>
                    </div>
                         <div class="flex items-center space-x-3">
                             <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                 <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                 <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                             </svg>
                             <span class="text-gray-900 dark:text-white">São Paulo, SP - Brasil</span>
                </div>
            </div>
                </div>

                 <!-- Documentação -->
                 <div class="bg-gray-100 dark:bg-gray-900 rounded-2xl p-6">
                     <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Documentação</h3>
                     <div class="space-y-3">
                         <button class="w-full text-left p-3 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-800">
                             <div class="flex items-center justify-between">
                                 <div>
                                     <p class="text-gray-900 dark:text-white font-medium">Manual do usuário</p>
                                     <p class="text-gray-600 dark:text-gray-400 text-sm">Guia completo de uso da plataforma</p>
            </div>
                                 <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                     <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                         </button>
                         <button class="w-full text-left p-3 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-800">
                             <div class="flex items-center justify-between">
                                 <div>
                                     <p class="text-gray-900 dark:text-white font-medium">API Documentation</p>
                                     <p class="text-gray-600 dark:text-gray-400 text-sm">Documentação técnica para desenvolvedores</p>
                                 </div>
                                 <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                     <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                         </button>
                </div>
                    </div>
             </div>
                    </div>
                </div>

     <!-- Modal de Acesso Negado -->
     <div id="accessDeniedModal" class="modal-fullscreen hidden">
         <div class="p-6">
             <div class="max-w-md mx-auto mt-20">
                 <div class="text-center mb-8">
                     <div class="w-20 h-20 bg-gradient-to-br from-red-500 to-red-600 rounded-3xl flex items-center justify-center mx-auto mb-4">
                         <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                             <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                     <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Acesso Negado</h2>
                     <p class="text-gray-600 dark:text-gray-400">Você precisa estar logado em um sistema Xandria</p>
                </div>

                 <div class="bg-gray-100 dark:bg-gray-900 rounded-2xl p-6">
                     <div class="text-center space-y-4">
                         <p class="text-gray-700 dark:text-gray-300">
                             Para acessar a Xandria Store, você precisa estar logado em um dos sistemas Xandria:
                         </p>
                         
                         <div class="space-y-3">
                             <div class="flex items-center space-x-3 p-3 bg-white dark:bg-gray-800 rounded-xl">
                                 <div class="w-10 h-10 bg-green-500 rounded-xl flex items-center justify-center">
                                     <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                         <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                                 <div class="text-left">
                                     <p class="font-medium text-gray-900 dark:text-white">LacTech</p>
                                     <p class="text-sm text-gray-600 dark:text-gray-400">Sistema de gestão leiteira</p>
                </div>
            </div>
                             
                             <div class="flex items-center space-x-3 p-3 bg-white dark:bg-gray-800 rounded-xl opacity-60">
                                 <div class="w-10 h-10 bg-blue-500 rounded-xl flex items-center justify-center">
                                     <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                         <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                                 </div>
                                 <div class="text-left">
                                     <p class="font-medium text-gray-900 dark:text-white">AgroSmart</p>
                                     <p class="text-sm text-gray-600 dark:text-gray-400">Sistema de agricultura inteligente</p>
                                 </div>
                    </div>
                </div>

                         <button onclick="goBack()" class="w-full py-3 bg-gray-600 hover:bg-gray-700 text-white font-bold rounded-xl">
                             Voltar
            </button>
                    </div>
                    </div>
                </div>
            </div>
     </div>

     <!-- Modal de Screenshot em Tela Cheia -->
     <div id="screenshotModal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden">
         <div class="relative w-full h-full flex items-center justify-center">
             <!-- Botão fechar -->
             <button onclick="closeScreenshot()" class="absolute top-4 right-4 z-10 w-12 h-12 bg-black bg-opacity-50 hover:bg-opacity-70 text-white rounded-full flex items-center justify-center">
                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
             
             <!-- Navegação -->
             <button id="prevScreenshot" onclick="changeScreenshot(-1)" class="absolute left-4 top-1/2 transform -translate-y-1/2 z-10 w-12 h-12 bg-black bg-opacity-50 hover:bg-opacity-70 text-white rounded-full flex items-center justify-center">
                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
             
             <button id="nextScreenshot" onclick="changeScreenshot(1)" class="absolute right-4 top-1/2 transform -translate-y-1/2 z-10 w-12 h-12 bg-black bg-opacity-50 hover:bg-opacity-70 text-white rounded-full flex items-center justify-center">
                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
             
             <!-- Imagem -->
             <div class="max-w-full max-h-full p-4">
                 <img id="screenshotImage" src="" alt="Screenshot" class="max-w-full max-h-full object-contain rounded-lg">
        </div>
             
             <!-- Indicador de posição -->
             <div id="screenshotIndicator" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-50 text-white px-4 py-2 rounded-full text-sm">
                 <span id="currentScreenshot">1</span> de <span id="totalScreenshots">4</span>
             </div>
         </div>
     </div>

    <script>
                                                                       // Estado da aplicação
           let isDarkMode = localStorage.getItem('theme') === 'dark' || window.matchMedia('(prefers-color-scheme: dark)').matches;
           let isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
           let currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
          
          // Sistema de detecção de origem e redirecionamento automático
          let currentRole = null;
          let lastUsedRole = localStorage.getItem('lastUsedRole');
          
          // Detectar origem baseada na URL
          function detectOrigin() {
              const urlParams = new URLSearchParams(window.location.search);
              const role = urlParams.get('role');
              const from = urlParams.get('from');
              
              if (role) {
                  currentRole = role;
                  localStorage.setItem('lastUsedRole', role);
                  return role;
              }
              
              if (from) {
                  // Mapear páginas para roles
                  const pageToRole = {
                      'gerente.php': 'gerente',
                      'funcionario.php': 'funcionario',
                      'veterinario.php': 'veterinario',
                      'proprietario.php': 'proprietario'
                  };
                  
                  const role = pageToRole[from];
                  if (role) {
                      currentRole = role;
                      localStorage.setItem('lastUsedRole', role);
                      return role;
                  }
              }
              
              return null;
          }
          
          // Detectar origem e salvar role, mas NÃO redirecionar automaticamente
          function detectAndSaveRole() {
              const detectedRole = detectOrigin();
              
              if (detectedRole) {
                  // Se detectou um role, salvar mas não redirecionar
                  console.log('Role detectado:', detectedRole, '- Salvando mas mantendo na Xandria Store');
                  
                  // Mostrar indicador visual
                  showDetectedRoleIndicator(detectedRole);
                  
                  return detectedRole;
              }
              
              return null;
          }
          
          // Mostrar indicador visual do módulo detectado
          function showDetectedRoleIndicator(role) {
              const indicator = document.getElementById('detectedRoleIndicator');
              const roleText = document.getElementById('detectedRoleText');
              
              if (indicator && roleText) {
                  const roleNames = {
                      'gerente': 'Gerente',
                      'funcionario': 'Funcionário',
                      'veterinario': 'Veterinário',
                      'proprietario': 'Proprietário'
                  };
                  
                  roleText.textContent = roleNames[role] || role;
                  indicator.classList.remove('hidden');
              }
          }
          
          // Redirecionar para módulo específico
          function redirectToModule(role) {
              const moduleUrls = {
                  'gerente': '/gerente.php',
                  'funcionario': '/funcionario.php',
                  'veterinario': '/veterinario.php',
                  'proprietario': '/proprietario.php'
              };
              
              const url = moduleUrls[role];
              if (url) {
                  // Salvar o role atual
                  localStorage.setItem('lastUsedRole', role);
                  
                  // Redirecionar
                  window.location.href = url;
              }
          }
          
          // Função para mostrar menu de seleção de módulo
          function showModuleSelector() {
              const modal = document.getElementById('moduleSelectorModal');
              if (modal) {
                  modal.classList.remove('hidden');
              }
          }
          
          // Função para selecionar módulo manualmente
          function selectModule(role) {
              redirectToModule(role);
          }
          
          // Função para abrir o módulo detectado
          function openDetectedModule() {
              const detectedRole = localStorage.getItem('lastUsedRole');
              if (detectedRole) {
                  redirectToModule(detectedRole);
              }
          }
           
           // Carregar apps instalados do usuário específico
           let installedApps = [];
           if (currentUser && currentUser.id) {
               const userKey = `installedApps_${currentUser.id}`;
               installedApps = JSON.parse(localStorage.getItem(userKey) || '[]');
           } else {
               installedApps = JSON.parse(localStorage.getItem('installedApps') || '[]');
           }

                 // Dados dos apps
         const appsData = {
             'LacTech': {
                 name: 'LacTech',
                 developer: 'Xandria Systems',
                 category: 'Produção',
                 description: 'Sistema completo para gestão de fazendas leiteiras com controle de produção, saúde animal e análise de dados.',
                 longDescription: 'O LacTech é uma solução completa para produtores de leite que desejam otimizar sua operação. Com recursos avançados de monitoramento, você pode acompanhar a produção individual de cada animal, controlar a saúde do rebanho e gerar relatórios detalhados para tomada de decisões estratégicas.',
                 rating: 5.0,
                 reviews: 847,
                 size: '2.1MB',
                 version: '2.4.1',
                 updated: '15 de dezembro de 2024',
                 price: 'Gratuito',
                 features: [
                     'Controle de produção leiteira',
                     'Monitoramento de saúde animal',
                     'Relatórios e análises',
                     'Gestão de custos',
                     'Sincronização em nuvem'
                 ],
                 screenshots: [
                     'https://i.postimg.cc/vmPpgw0S/Captura-de-tela-2025-08-31-131903.png',
                     'https://i.postimg.cc/J7X2mXCF/Captura-de-tela-2025-08-31-132208.png',
                     'https://i.postimg.cc/28m2DXwq/Captura-de-tela-2025-08-31-132336.png',
                     'https://i.postimg.cc/L5mfrGp2/Captura-de-tela-2025-08-31-132453.png'
                 ],
                 icon: 'https://i.postimg.cc/vmrkgDcB/lactech.png'
             },
             'AgroSmart': {
                 name: 'AgroSmart',
                 developer: 'Xandria Systems',
                 category: 'Agricultura',
                 description: 'Agricultura inteligente com IA para otimização de cultivos e monitoramento de plantações.',
                 longDescription: 'O AgroSmart é uma solução inovadora que utiliza inteligência artificial para otimizar o cultivo de plantações. Com sensores avançados e algoritmos de machine learning, você pode monitorar a saúde das plantas, otimizar o uso de água e fertilizantes, e aumentar significativamente a produtividade da sua fazenda.',
                 rating: 0,
                 reviews: 0,
                 size: '--',
                 version: '--',
                 updated: 'Em desenvolvimento',
                 price: 'Em breve',
                 features: [
                     'Monitoramento inteligente de plantações',
                     'Otimização de irrigação',
                     'Análise de saúde das plantas',
                     'Previsão de colheita',
                     'Integração com IoT'
                 ],
                 screenshots: [
                     '/placeholder.svg?height=400&width=300',
                     '/placeholder.svg?height=400&width=300',
                     '/placeholder.svg?height=400&width=300'
                 ],
                 icon: 'https://i.postimg.cc/sxTmhCSX/logos.png'
             }
         };

                 // Aplicar tema imediatamente no carregamento
         (function() {
             const savedTheme = localStorage.getItem('theme');
             const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
             
             if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                 document.documentElement.classList.add('dark');
                 isDarkMode = true;
             } else {
                 document.documentElement.classList.remove('dark');
                 isDarkMode = false;
             }
         })();
         
                              // Inicialização
           document.addEventListener('DOMContentLoaded', function() {
               // Aplicar tema inicial imediatamente
               applyTheme();
               
               // Inicializar sistema de notificações
               initializeNotifications();
               
               // Verificar se está logado em algum sistema Xandria
               const isLoggedInToSystem = localStorage.getItem('isLoggedInToSystem') === 'true';
               
               if (!isLoggedInToSystem) {
                   // Se não estiver logado, mostrar mensagem de acesso negado
                   setTimeout(() => {
                       document.getElementById('splashScreen').style.display = 'none';
                       showAccessDenied();
                   }, 3000);
               } else {
                   // Se estiver logado, mostrar app principal
                   setTimeout(() => {
                       document.getElementById('splashScreen').style.display = 'none';
                       document.getElementById('mainApp').classList.remove('hidden');
                       
                       // Detectar e salvar role, mas manter na Xandria Store
                       detectAndSaveRole();
                   }, 3000);
               }

                          // Fechar modais ao clicar fora
              document.addEventListener('click', function(e) {
                  const profileModal = document.getElementById('profileModal');
                  const profileButton = e.target.closest('button[onclick="toggleProfileModal()"]');
                  
                  if (!profileModal.contains(e.target) && !profileButton && !profileModal.classList.contains('hidden')) {
                      profileModal.classList.add('hidden');
                  }
              });

                            // Event listener para pesquisa
               document.getElementById('searchInput').addEventListener('input', function(e) {
                   performSearch(e.target.value);
               });


           });

        // Função para alternar modal de perfil
        function toggleProfileModal() {
            const modal = document.getElementById('profileModal');
            modal.classList.toggle('hidden');
        }

        // Função para alternar tema
        function toggleTheme() {
            isDarkMode = !isDarkMode;
            localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
            applyTheme();
        }

                 // Aplicar tema
         function applyTheme() {
             const html = document.documentElement;
             const toggle = document.getElementById('themeToggle');
             const slider = document.getElementById('themeSlider');
             const splashLogo = document.getElementById('splashLogo');
             const headerLogo = document.getElementById('headerLogo');
             
             if (isDarkMode) {
                 html.classList.add('dark');
                 if (toggle) {
                     toggle.classList.add('bg-green-600');
                     toggle.classList.remove('bg-gray-700');
                 }
                 if (slider) {
                     slider.classList.add('translate-x-6');
                     slider.classList.remove('translate-x-0.5');
                 }
                 
                 // Logo branca para tema escuro
                 if (splashLogo) splashLogo.src = 'https://i.postimg.cc/jjsq36Nf/lactechbranca-1.png';
                 if (headerLogo) headerLogo.src = 'https://i.postimg.cc/jjsq36Nf/lactechbranca-1.png';
             } else {
                 html.classList.remove('dark');
                 if (toggle) {
                     toggle.classList.remove('bg-green-600');
                     toggle.classList.add('bg-gray-700');
                 }
                 if (slider) {
                     slider.classList.remove('translate-x-6');
                     slider.classList.add('translate-x-0.5');
                 }
                 
                 // Logo preta para tema claro
                 if (splashLogo) splashLogo.src = 'https://i.postimg.cc/W17q41wM/lactechpreta.png';
                 if (headerLogo) headerLogo.src = 'https://i.postimg.cc/W17q41wM/lactechpreta.png';
             }
         }

                 // Função para instalar app
         function installApp(appName, event) {
             event.stopPropagation();
             
             if (!installedApps.includes(appName)) {
                 installedApps.push(appName);
                 
                 // Salvar apps instalados vinculados ao usuário
                 const userKey = currentUser ? `installedApps_${currentUser.id}` : 'installedApps';
                 localStorage.setItem(userKey, JSON.stringify(installedApps));
                 
                 // Feedback visual
                 const button = event.target;
                 const originalText = button.textContent;
                 button.textContent = 'Instalado!';
                 button.classList.add('bg-green-600', 'text-white');
                 button.classList.remove('bg-gray-100', 'dark:bg-gray-700');
                 
                 setTimeout(() => {
                     button.textContent = 'Abrir';
                     button.classList.remove('bg-green-600');
                     button.classList.add('bg-blue-600', 'hover:bg-blue-700');
                 }, 2000);
             }
         }

        // Função para mostrar apps instalados
        function showInstalledApps() {
            const modal = document.getElementById('installedAppsModal');
            const list = document.getElementById('installedAppsList');
            
            // Fechar modal de perfil
            document.getElementById('profileModal').classList.add('hidden');
            
            if (installedApps.length === 0) {
                list.innerHTML = `
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <p class="text-gray-400 text-lg">Nenhum app instalado ainda</p>
                        <p class="text-gray-500 text-sm mt-2">Instale apps da loja para vê-los aqui</p>
                    </div>
                `;
            } else {
                                 list.innerHTML = installedApps.map(appName => {
                     const app = appsData[appName];
                     return `
                         <div class="bg-gray-100 dark:bg-gray-900 rounded-3xl p-6 cursor-pointer card-hover">
                             <div class="flex items-center space-x-4">
                                 <div class="w-16 h-16 bg-gradient-to-br from-white to-white rounded-2xl flex items-center justify-center">
                                     <img src="${app.icon}" alt="${app.name}" class="w-10 h-10 rounded-xl">
                                 </div>
                                 <div class="flex-1">
                                     <h3 class="font-bold text-gray-900 dark:text-white text-lg">${app.name}</h3>
                                     <p class="text-gray-600 dark:text-gray-400">${app.description}</p>
                                     <p class="text-gray-500 dark:text-gray-500 text-sm mt-1">Instalado • ${app.size}</p>
                                 </div>
                                 <button class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-full text-sm transition-colors">
                                     Abrir
                    </button>
                </div>
                         </div>
                     `;
                 }).join('');
            }
            
            modal.classList.remove('hidden');
        }

        // Função para fechar apps instalados
        function closeInstalledApps() {
            document.getElementById('installedAppsModal').classList.add('hidden');
        }

        // Função para abrir detalhes do app
        function openAppDetail(appName) {
            const modal = document.getElementById('appDetailModal');
            const content = document.getElementById('appDetailContent');
            const app = appsData[appName];
            
            if (!app) return;
            
                         content.innerHTML = `
                 <div class="space-y-8">
                     <!-- Header do app -->
                     <div class="flex items-start space-x-6">
                         <div class="w-24 h-24 bg-gradient-to-br from-white to-white rounded-3xl flex items-center justify-center app-icon-shadow">
                             <img src="${app.icon}" alt="${app.name}" class="w-16 h-16 rounded-2xl">
                    </div>
                    <div class="flex-1">
                             <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">${app.name}</h1>
                             <p class="text-gray-600 dark:text-gray-400 mb-2">${app.developer}</p>
                             <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-500 mb-4">
                                 <span>${app.category}</span>
                                 <span>•</span>
                                 <span>${app.size}</span>
                                 <span>•</span>
                                 <span class="text-green-600 dark:text-green-400">${app.price}</span>
                             </div>
                                                                                                                     <button onclick="installApp('${appName}', event)" class="px-20 py-3 play-button text-white font-bold rounded-2xl text-base">
                                   ${installedApps.includes(appName) ? 'Abrir' : 'Instalar'}
                               </button>
                    </div>
                </div>

                     <!-- Screenshots -->
                     <div>
                         <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Capturas de tela</h3>
                         <div class="flex space-x-4 overflow-x-auto pb-4">
                             ${app.screenshots.map((screenshot, index) => `
                                 <div class="flex-shrink-0 cursor-pointer" onclick="openScreenshot('${screenshot}', ${index + 1}, ${app.screenshots.length})">
                                     <img src="${screenshot}" alt="Screenshot ${index + 1}" class="screenshot-image w-40 h-56 sm:w-32 sm:h-44 object-cover rounded-2xl shadow-lg hover:opacity-80">
                                 </div>
                             `).join('')}
                         </div>
                     </div>

                     <!-- Sobre este app -->
                     <div>
                         <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Sobre este app</h3>
                         <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-6">${app.longDescription}</p>
                         
                         <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Principais recursos:</h4>
                         <ul class="space-y-2">
                             ${app.features.map(feature => `
                                 <li class="flex items-center text-gray-700 dark:text-gray-300">
                                     <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                         <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                     </svg>
                                     ${feature}
                                 </li>
                             `).join('')}
                         </ul>
                     </div>

                     <!-- Avaliações -->
                     <div>
                         <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Avaliações e opiniões</h3>
                         <div class="bg-gray-100 dark:bg-gray-900 rounded-2xl p-6">
                             <div class="flex items-center justify-between mb-6">
                                 <div class="text-center">
                                     <div class="text-4xl font-bold text-gray-900 dark:text-white mb-2">${app.rating}</div>
                                     <div class="flex items-center justify-center mb-2">
                                         ${Array(5).fill().map(() => `
                                             <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                 <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                             </svg>
                                         `).join('')}
                                     </div>
                                     <div class="text-sm text-gray-600 dark:text-gray-400">${app.reviews} avaliações</div>
                                 </div>
                                 <div class="flex-1 ml-8 space-y-2">
                                     ${[5,4,3,2,1].map(stars => `
                                         <div class="flex items-center space-x-3">
                                             <span class="text-sm text-gray-600 dark:text-gray-400 w-2">${stars}</span>
                                             <div class="flex-1 h-2 bg-gray-300 dark:bg-gray-700 rounded-full overflow-hidden">
                                                 <div class="h-full bg-green-500 rating-bar" style="--width: ${stars === 5 ? '85%' : stars === 4 ? '10%' : '5%'}"></div>
                                             </div>
                                         </div>
                                     `).join('')}
                                 </div>
                             </div>
                         </div>
                     </div>

                     <!-- Informações adicionais -->
                     <div>
                         <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Informações adicionais</h3>
                         <div class="bg-gray-100 dark:bg-gray-900 rounded-2xl p-6 space-y-4">
                             <div class="flex justify-between">
                                 <span class="text-gray-600 dark:text-gray-400">Atualizado em</span>
                                 <span class="text-gray-900 dark:text-white">${app.updated}</span>
                             </div>
                             <div class="flex justify-between">
                                 <span class="text-gray-600 dark:text-gray-400">Tamanho</span>
                                 <span class="text-gray-900 dark:text-white">${app.size}</span>
                             </div>
                             <div class="flex justify-between">
                                 <span class="text-gray-600 dark:text-gray-400">Versão</span>
                                 <span class="text-gray-900 dark:text-white">${app.version}</span>
                             </div>
                             <div class="flex justify-between">
                                 <span class="text-gray-600 dark:text-gray-400">Desenvolvido por</span>
                                 <span class="text-gray-900 dark:text-white">${app.developer}</span>
                             </div>
                         </div>
                     </div>
                </div>
            `;

            modal.classList.remove('hidden');
        }

        // Função para fechar detalhes do app
        function closeAppDetail() {
            document.getElementById('appDetailModal').classList.add('hidden');
        }

                                                                                                                                               // Função de logout
            function logout() {
                if (confirm('Tem certeza que deseja sair da sua conta?')) {
                    // Limpar dados locais
                    if (currentUser && currentUser.id) {
                        localStorage.removeItem(`installedApps_${currentUser.id}`);
                    }
                    localStorage.removeItem('installedApps');
                    localStorage.removeItem('theme');
                    localStorage.removeItem('isLoggedIn');
                    localStorage.removeItem('currentUser');
                    localStorage.removeItem('isLoggedInToSystem');
                    
                    // Recarregar página
                    window.location.reload();
                }
            }

         // Função para alternar barra de pesquisa
         function toggleSearch() {
             const searchBar = document.getElementById('searchBar');
             searchBar.classList.toggle('hidden');
             
             if (!searchBar.classList.contains('hidden')) {
                 document.getElementById('searchInput').focus();
             }
         }

         // Função para limpar pesquisa
         function clearSearch() {
             document.getElementById('searchInput').value = '';
             performSearch('');
         }

         // Função para realizar pesquisa
         function performSearch(query) {
             const appsContainer = document.querySelector('.space-y-4');
             const apps = appsContainer.querySelectorAll('[onclick*="openAppDetail"]');
             
             query = query.toLowerCase().trim();
             
             apps.forEach(app => {
                 const appName = app.querySelector('h3').textContent.toLowerCase();
                 const appDescription = app.querySelector('p').textContent.toLowerCase();
                 const appCategory = app.querySelector('.text-sm').textContent.toLowerCase();
                 
                 const matches = appName.includes(query) || 
                                appDescription.includes(query) || 
                                appCategory.includes(query);
                 
                 if (matches || query === '') {
                     app.style.display = 'block';
                 } else {
                     app.style.display = 'none';
                 }
             });
         }

                                     // Sem feedback tátil

                     // Função para abrir configurações
           function openSettings() {
               document.getElementById('profileModal').classList.add('hidden');
               document.getElementById('settingsModal').classList.remove('hidden');
               loadSettings();
           }

           // Função para fechar configurações
           function closeSettings() {
               document.getElementById('settingsModal').classList.add('hidden');
           }

           // Função para carregar configurações
           function loadSettings() {
               // Carregar configurações salvas
               const settings = JSON.parse(localStorage.getItem('xandriaStoreSettings') || '{}');
               
               // Aplicar configurações aos toggles
               updateToggle('notifications-push', settings.notificationsPush !== false);
               updateToggle('notifications-updates', settings.notificationsUpdates !== false);
               updateToggle('privacy-data', settings.privacyData !== false);
               updateToggle('privacy-analytics', settings.privacyAnalytics !== false);
               
               // Atualizar informações de armazenamento
               updateStorageInfo();
           }

           // Função para atualizar toggle
           function updateToggle(toggleId, isOn) {
               const toggle = document.getElementById(toggleId);
               const slider = toggle.querySelector('.toggle-slider');
               if (isOn) {
                   toggle.classList.add('bg-green-600');
                   toggle.classList.remove('bg-gray-300', 'dark:bg-gray-700');
                   slider.classList.add('translate-x-6');
                   slider.classList.remove('translate-x-0.5');
               } else {
                   toggle.classList.remove('bg-green-600');
                   toggle.classList.add('bg-gray-300', 'dark:bg-gray-700');
                   slider.classList.remove('translate-x-6');
                   slider.classList.add('translate-x-0.5');
               }
           }

           // Função para alternar configuração
           function toggleSetting(settingName) {
               const settings = JSON.parse(localStorage.getItem('xandriaStoreSettings') || '{}');
               settings[settingName] = !settings[settingName];
               localStorage.setItem('xandriaStoreSettings', JSON.stringify(settings));
               
               // Aplicar mudança visual
               const toggleId = settingName.replace(/([A-Z])/g, '-$1').toLowerCase();
               updateToggle(toggleId, settings[settingName]);
               
               // Lógica específica para notificações push
               if (settingName === 'notificationsPush' && settings[settingName]) {
                   requestNotificationPermission();
               }
               
               // Feedback visual
               showNotification(`Configuração ${settings[settingName] ? 'ativada' : 'desativada'}`, 'success');
           }

           // Função para atualizar informações de armazenamento
           function updateStorageInfo() {
               const storageInfo = document.getElementById('storage-info');
               if (storageInfo) {
                   const installedAppsCount = installedApps.length;
                   const estimatedSize = installedAppsCount * 2.1; // 2.1MB por app
                   storageInfo.textContent = `${estimatedSize.toFixed(1)}MB de dados salvos`;
               }
           }

           // Função para limpar cache
           function clearCache() {
               if (confirm('Tem certeza que deseja limpar o cache? Isso pode melhorar o desempenho.')) {
                   // Limpar dados temporários
                   const keysToKeep = ['theme', 'isLoggedIn', 'currentUser', 'isLoggedInToSystem'];
                   const keysToRemove = [];
                   
                   for (let i = 0; i < localStorage.length; i++) {
                       const key = localStorage.key(i);
                       if (!keysToKeep.includes(key)) {
                           keysToRemove.push(key);
                       }
                   }
                   
                   keysToRemove.forEach(key => localStorage.removeItem(key));
                   
                   showNotification('Cache limpo com sucesso!', 'success');
                   updateStorageInfo();
               }
           }

           // Função para mostrar notificação
           function showNotification(message, type = 'info') {
               // Criar elemento de notificação
               const notification = document.createElement('div');
               notification.className = `fixed top-4 right-4 z-[9999] px-6 py-4 rounded-xl shadow-lg transition-all duration-300 transform translate-x-full`;
               
               // Cores baseadas no tipo
               const colors = {
                   success: 'bg-green-500 text-white',
                   error: 'bg-red-500 text-white',
                   info: 'bg-blue-500 text-white',
                   warning: 'bg-yellow-500 text-white'
               };
               
               notification.className += ` ${colors[type] || colors.info}`;
               notification.textContent = message;
               
               // Adicionar ao DOM
               document.body.appendChild(notification);
               
               // Animar entrada
            setTimeout(() => {
                   notification.classList.remove('translate-x-full');
               }, 100);
                
               // Remover após 3 segundos
                setTimeout(() => {
                   notification.classList.add('translate-x-full');
                   setTimeout(() => {
                       document.body.removeChild(notification);
                   }, 300);
               }, 3000);
           }

           // ==================== SISTEMA DE NOTIFICAÇÕES PUSH ====================

           // Registrar Service Worker
           async function registerServiceWorker() {
               if ('serviceWorker' in navigator) {
                   try {
                       const registration = await navigator.serviceWorker.register('/sw.js');
                       console.log('Service Worker registrado:', registration);
                       return registration;
                   } catch (error) {
                       console.error('Erro ao registrar Service Worker:', error);
                       return null;
                   }
               }
               return null;
           }

           // Solicitar permissão para notificações
           async function requestNotificationPermission() {
               if (!('Notification' in window)) {
                   console.log('Este navegador não suporta notificações');
                   return false;
               }

               if (Notification.permission === 'granted') {
                   console.log('Permissão já concedida');
                   return true;
               }

               if (Notification.permission === 'denied') {
                   console.log('Permissão negada pelo usuário');
                   showNotification('Notificações bloqueadas. Ative nas configurações do navegador.', 'warning');
                   return false;
               }

               const permission = await Notification.requestPermission();
               if (permission === 'granted') {
                   console.log('Permissão concedida');
                   showNotification('Notificações ativadas!', 'success');
                   return true;
               } else {
                   console.log('Permissão negada');
                   showNotification('Notificações desativadas', 'info');
                   return false;
               }
           }

           // Enviar notificação push
           async function sendPushNotification(title, body, data = {}) {
               const settings = JSON.parse(localStorage.getItem('xandriaStoreSettings') || '{}');
               
               // Verificar se notificações estão ativadas
               if (!settings.notificationsPush) {
                   console.log('Notificações push desativadas pelo usuário');
                   return;
               }

               // Verificar permissão
               if (Notification.permission !== 'granted') {
                   console.log('Permissão de notificação não concedida');
                   return;
               }

               // Se o Service Worker está ativo, usar notificação push
               if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                   try {
                       await navigator.serviceWorker.ready;
                       await navigator.serviceWorker.controller.postMessage({
                           type: 'PUSH_NOTIFICATION',
                           payload: {
                               title: title,
                               body: body,
                               icon: '/icon-192x192.png',
                               badge: '/badge-72x72.png',
                               data: data
                           }
                       });
                   } catch (error) {
                       console.error('Erro ao enviar notificação push:', error);
                       // Fallback para notificação local
                       new Notification(title, {
                           body: body,
                           icon: '/icon-192x192.png',
                           badge: '/badge-72x72.png',
                           data: data
                       });
                   }
               } else {
                   // Fallback para notificação local
                   new Notification(title, {
                       body: body,
                       icon: '/icon-192x192.png',
                       badge: '/badge-72x72.png',
                       data: data
                   });
               }
           }


           // Inicializar sistema de notificações
           async function initializeNotifications() {
               const registration = await registerServiceWorker();
               
               if (registration) {
                   // Solicitar permissão na primeira vez
                   const settings = JSON.parse(localStorage.getItem('xandriaStoreSettings') || '{}');
                   if (settings.notificationsPush === undefined) {
                       await requestNotificationPermission();
                   }
               }
           }

           // Listener para mensagens do Service Worker
           if ('serviceWorker' in navigator) {
               navigator.serviceWorker.addEventListener('message', (event) => {
                   if (event.data && event.data.type === 'NOTIFICATION_CLICKED') {
                       console.log('Notificação clicada:', event.data.payload);
                       // Aqui você pode adicionar lógica específica baseada no tipo de notificação
                   }
               });
           }

          // Função para abrir ajuda
          function openHelp() {
              document.getElementById('profileModal').classList.add('hidden');
              document.getElementById('helpModal').classList.remove('hidden');
          }

          // Função para fechar ajuda
          function closeHelp() {
              document.getElementById('helpModal').classList.add('hidden');
          }

          // Função para mostrar acesso negado
          function showAccessDenied() {
              document.getElementById('accessDeniedModal').classList.remove('hidden');
          }

          // Função para voltar
          function goBack() {
              window.history.back();
          }

                                                                                       // Função para atualizar informações do perfil
             function updateProfileInfo() {
                 if (currentUser) {
                     const profileName = document.querySelector('#profileModal h3');
                     const profileEmail = document.querySelector('#profileModal p');
                     
                     if (profileName) profileName.textContent = currentUser.name;
                     if (profileEmail) profileEmail.textContent = currentUser.email;
                     
                     // Atualizar também o botão do perfil no header
                     const profileButton = document.querySelector('button[onclick="toggleProfileModal()"]');
                     if (profileButton) {
                         profileButton.textContent = currentUser.name.charAt(0).toUpperCase();
                     }
                     
                     // Atualizar foto de perfil se existir
                     if (currentUser.profile_photo_url) {
                         const profileAvatar = document.querySelector('#profileModal .w-16.h-16');
                         if (profileAvatar) {
                             // Substituir o div do avatar por uma imagem
                             profileAvatar.innerHTML = `<img src="${currentUser.profile_photo_url}?t=${Date.now()}" alt="Foto de ${currentUser.name}" class="w-full h-full object-cover rounded-full">`;
                         }
                         
                         // Atualizar também o botão do header se tiver foto
                         const headerProfileButton = document.querySelector('button[onclick="toggleProfileModal()"]');
                         if (headerProfileButton) {
                             headerProfileButton.innerHTML = `<img src="${currentUser.profile_photo_url}?t=${Date.now()}" alt="Foto de ${currentUser.name}" class="w-full h-full object-cover rounded-full">`;
                         }
                     }
                 } else {
                     // Se não houver usuário, usar informações padrão
                     const profileName = document.querySelector('#profileModal h3');
                     const profileEmail = document.querySelector('#profileModal p');
                     
                     if (profileName) profileName.textContent = 'Usuário Xandria';
                     if (profileEmail) profileEmail.textContent = 'usuario@xandria.com';
                 }
             }

                     // Atualizar perfil se já estiver logado
           if (isLoggedIn && currentUser) {
               updateProfileInfo();
           } else {
               // Atualizar perfil mesmo sem usuário logado
               updateProfileInfo();
           }

           // ==================== SISTEMA DE INSTALAÇÃO PWA ====================
           
           let deferredPrompt;
           const CURRENT_VERSION = '2.0.0';
           
           // Capturar o evento beforeinstallprompt
           window.addEventListener('beforeinstallprompt', (e) => {
               console.log('PWA pode ser instalada');
               e.preventDefault();
               deferredPrompt = e;
               
               // Verificar se já está instalado
               checkPWAStatus();
           });
           
           // Função para verificar status da PWA
           async function checkPWAStatus() {
               const isInstalled = await isPWAInstalled();
               const installedVersion = localStorage.getItem('pwa_version');
               const hasServiceWorker = await checkServiceWorker();
               const isStandalone = isRunningAsPWA();
               
               console.log('PWA Status:', { 
                   isInstalled, 
                   installedVersion, 
                   currentVersion: CURRENT_VERSION,
                   hasServiceWorker,
                   isStandalone,
                   displayMode: window.matchMedia('(display-mode: standalone)').matches,
                   standalone: window.navigator.standalone
               });
               
               // Se está instalado (tem service worker ou dados de instalação)
               if (isInstalled) {
                   if (installedVersion !== CURRENT_VERSION) {
                       // Versão diferente - mostrar botão de atualização
                       showUpdateButton();
                       showNotification(`Versão ${installedVersion || 'antiga'} detectada. Nova versão ${CURRENT_VERSION} disponível!`, 'warning');
                   } else {
                       // Versão atual - mostrar botão de desinstalação
                       showUninstallButton();
                   }
               } else {
                   // Não instalado - mostrar botão de instalação
                   showInstallButton();
               }
           }
           
           // Função para verificar se tem service worker
           async function checkServiceWorker() {
               if ('serviceWorker' in navigator) {
                   try {
                       const registrations = await navigator.serviceWorker.getRegistrations();
                       return registrations.length > 0;
                   } catch (error) {
                       console.error('Erro ao verificar service worker:', error);
                       return false;
                   }
               }
               return false;
           }
           
           // Função para instalar a PWA
           async function installPWA() {
               if (!deferredPrompt) {
                   showNotification('App já está instalado ou não pode ser instalado', 'error');
                   return;
               }
               
               try {
                   // Mostrar o prompt de instalação
                   deferredPrompt.prompt();
                   
                   // Aguardar a resposta do usuário
                   const { outcome } = await deferredPrompt.userChoice;
                   
                   if (outcome === 'accepted') {
                       console.log('Usuário aceitou a instalação');
                       
                       // Aguardar um pouco para verificar se realmente instalou
                       setTimeout(async () => {
                           const isReallyInstalled = await checkRealInstallation();
                           
                           if (isReallyInstalled) {
                               showNotification('App instalado com sucesso!', 'success');
                               
                               // Salvar versão instalada com dados de segurança
                               const installData = {
                                   version: CURRENT_VERSION,
                                   timestamp: Date.now(),
                                   userInfo: getCurrentUserInfo(),
                                   url: window.location.href
                               };
                               localStorage.setItem('pwa_version', JSON.stringify(installData));
                               
                               // Esconder botão de instalação
                               hideAllPWAButtons();
                           } else {
                               showNotification('Erro: App não foi instalado corretamente', 'error');
                               console.error('Instalação falhou - app não detectado');
                           }
                       }, 2000);
                       
                   } else {
                       console.log('Usuário rejeitou a instalação');
                       showNotification('Instalação cancelada', 'info');
                   }
                   
               } catch (error) {
                   console.error('Erro durante instalação:', error);
                   showNotification('Erro durante instalação: ' + error.message, 'error');
               }
               
               // Limpar a referência
               deferredPrompt = null;
           }
           
           // Verificar se realmente instalou
           async function checkRealInstallation() {
               // Verificar se está em modo standalone
               const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
               
               // Verificar se tem service worker ativo
               const hasServiceWorker = 'serviceWorker' in navigator && 
                   await navigator.serviceWorker.getRegistration() !== null;
               
               // Verificar se tem dados de instalação
               const hasInstallData = localStorage.getItem('pwa_version') !== null;
               
               console.log('Verificação de instalação real:', {
                   isStandalone,
                   hasServiceWorker,
                   hasInstallData
               });
               
               // Considera instalado se estiver em modo standalone
               return isStandalone;
           }
           
           // Obter informações do usuário atual para segurança
           function getCurrentUserInfo() {
               const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
               const urlParams = new URLSearchParams(window.location.search);
               
               return {
                   role: urlParams.get('role') || 'unknown',
                   userId: currentUser?.id || 'unknown',
                   userName: currentUser?.name || 'unknown',
                   farmId: currentUser?.farm_id || 'unknown',
                   timestamp: Date.now()
               };
           }
           
           // Função para desinstalar a PWA
           async function uninstallPWA() {
               if (confirm('Tem certeza que deseja desinstalar o app? Você precisará reinstalá-lo para usar novamente.')) {
                   try {
                       // Limpar dados locais
                       localStorage.removeItem('pwa_version');
                       localStorage.removeItem('installedApps');
                       
                       // Limpar cache do service worker
                       if ('serviceWorker' in navigator) {
                           const registrations = await navigator.serviceWorker.getRegistrations();
                           for (let registration of registrations) {
                               await registration.unregister();
                           }
                       }
                       
                       // Limpar cache
                       if ('caches' in window) {
                           const cacheNames = await caches.keys();
                           await Promise.all(
                               cacheNames.map(cacheName => caches.delete(cacheName))
                           );
                       }
                       
                       showNotification('App desinstalado com sucesso! Recarregue a página.', 'success');
                       
                       // Esconder botão de desinstalação
                       hideAllPWAButtons();
                       
                       // Recarregar página após 2 segundos
                       setTimeout(() => {
                           window.location.reload();
                       }, 2000);
                       
                   } catch (error) {
                       console.error('Erro ao desinstalar:', error);
                       showNotification('Erro ao desinstalar. Tente manualmente nas configurações do navegador.', 'error');
                   }
               }
           }
           
           // Função para atualizar a PWA
           async function updatePWA() {
               if (confirm('Atualizar para a versão mais recente? Isso irá reinstalar o app.')) {
                   try {
                       // Desinstalar versão atual
                       await uninstallPWA();
                       
                       // Aguardar um pouco e tentar instalar novamente
                       setTimeout(() => {
                           if (deferredPrompt) {
                               installPWA();
                           } else {
                               showNotification('Recarregue a página e tente instalar novamente.', 'info');
                           }
                       }, 3000);
                       
                   } catch (error) {
                       console.error('Erro ao atualizar:', error);
                       showNotification('Erro ao atualizar. Tente desinstalar e reinstalar manualmente.', 'error');
                   }
               }
           }
           
           // Funções para mostrar/esconder botões
           function showInstallButton() {
               hideAllPWAButtons();
               const installButton = document.getElementById('installPWAButton');
               if (installButton) {
                   installButton.classList.remove('hidden');
               }
           }
           
           function showUninstallButton() {
               hideAllPWAButtons();
               const uninstallButton = document.getElementById('uninstallPWAButton');
               if (uninstallButton) {
                   uninstallButton.classList.remove('hidden');
               }
           }
           
           function showUpdateButton() {
               hideAllPWAButtons();
               const updateButton = document.getElementById('updatePWAButton');
               if (updateButton) {
                   updateButton.classList.remove('hidden');
               }
           }
           
           function hideAllPWAButtons() {
               const buttons = ['installPWAButton', 'uninstallPWAButton', 'updatePWAButton'];
               buttons.forEach(buttonId => {
                   const button = document.getElementById(buttonId);
                   if (button) {
                       button.classList.add('hidden');
                   }
               });
           }
           
           // Detectar se o app já está instalado
           window.addEventListener('appinstalled', () => {
               console.log('PWA foi instalada');
               showNotification('App instalado com sucesso!', 'success');
               
               // Salvar versão instalada
               localStorage.setItem('pwa_version', CURRENT_VERSION);
               
               // Verificar status
               checkPWAStatus();
           });
           
           // Verificar se está rodando como PWA
           function isRunningAsPWA() {
               return window.matchMedia('(display-mode: standalone)').matches || 
                      window.navigator.standalone === true;
           }
           
           // Verificar se PWA está instalada (mesmo que não esteja rodando)
           async function isPWAInstalled() {
               // Verificar se está em modo standalone (mais confiável)
               const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
               
               // Verificar se tem dados de instalação válidos
               const installData = localStorage.getItem('pwa_version');
               const hasValidInstallData = installData && JSON.parse(installData).version === CURRENT_VERSION;
               
               // Verificar se tem service worker ativo
               const hasServiceWorker = await checkServiceWorker();
               
               console.log('PWA Install Check:', {
                   isStandalone,
                   hasValidInstallData,
                   hasServiceWorker,
                   installData: installData ? JSON.parse(installData) : null
               });
               
               // Considera instalado se estiver em modo standalone
               // OU se tem dados de instalação válidos
               return isStandalone || hasValidInstallData;
           }
           
           
           // Verificar status inicial
           checkPWAStatus();
           
           // Verificar novamente após um delay para garantir detecção
           setTimeout(() => {
               checkPWAStatus();
           }, 2000);

           // Variáveis para o modal de screenshot
           let currentScreenshots = [];
           let currentScreenshotIndex = 0;

           // Função para abrir screenshot em tela cheia
           function openScreenshot(imageSrc, index, total) {
               console.log('openScreenshot chamada:', imageSrc, index, total);
               currentScreenshots = Array.from(document.querySelectorAll('.screenshot-image')).map(img => img.src);
               currentScreenshotIndex = index - 1;
               
               const modal = document.getElementById('screenshotModal');
               const image = document.getElementById('screenshotImage');
               const currentSpan = document.getElementById('currentScreenshot');
               const totalSpan = document.getElementById('totalScreenshots');
               const prevBtn = document.getElementById('prevScreenshot');
               const nextBtn = document.getElementById('nextScreenshot');
               
               console.log('Modal encontrado:', modal);
               console.log('Image encontrada:', image);
               
               image.src = imageSrc;
               currentSpan.textContent = index;
               totalSpan.textContent = total;
               
               // Mostrar/ocultar botões de navegação
               prevBtn.style.display = index === 1 ? 'none' : 'flex';
               nextBtn.style.display = index === total ? 'none' : 'flex';
               
               modal.classList.remove('hidden');
               
               // Adicionar listener para teclas
               document.addEventListener('keydown', handleScreenshotKeys);
           }

           // Função para fechar screenshot
           function closeScreenshot() {
               document.getElementById('screenshotModal').classList.add('hidden');
               document.removeEventListener('keydown', handleScreenshotKeys);
           }

           // Função para navegar entre screenshots
           function changeScreenshot(direction) {
               const newIndex = currentScreenshotIndex + direction;
               
               if (newIndex >= 0 && newIndex < currentScreenshots.length) {
                   currentScreenshotIndex = newIndex;
                   const image = document.getElementById('screenshotImage');
                   const currentSpan = document.getElementById('currentScreenshot');
                   const prevBtn = document.getElementById('prevScreenshot');
                   const nextBtn = document.getElementById('nextScreenshot');
                   
                   image.src = currentScreenshots[currentScreenshotIndex];
                   currentSpan.textContent = currentScreenshotIndex + 1;
                   
                   // Atualizar botões de navegação
                   prevBtn.style.display = currentScreenshotIndex === 0 ? 'none' : 'flex';
                   nextBtn.style.display = currentScreenshotIndex === currentScreenshots.length - 1 ? 'none' : 'flex';
               }
           }

           // Função para lidar com teclas do teclado
           function handleScreenshotKeys(event) {
               switch(event.key) {
                   case 'Escape':
                       closeScreenshot();
                       break;
                   case 'ArrowLeft':
                       changeScreenshot(-1);
                       break;
                   case 'ArrowRight':
                       changeScreenshot(1);
                       break;
               }
           }

           // Fechar modal ao clicar fora da imagem
           document.addEventListener('click', function(e) {
               const screenshotModal = document.getElementById('screenshotModal');
               const screenshotImage = document.getElementById('screenshotImage');
               
               if (e.target === screenshotModal) {
                   closeScreenshot();
               }
           });
    </script>
</body>
</html>
