<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeNode Relay - Editor Visual | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        dark: {
                            950: '#030303',
                            900: '#050505',
                            850: '#080808',
                            800: '#0a0a0a',
                            700: '#0f0f0f',
                            600: '#141414',
                            500: '#1a1a1a',
                            400: '#222222',
                        },
                        accent: {
                            DEFAULT: '#ffffff',
                            light: '#ffffff',
                            dark: '#ffffff',
                            glow: 'rgba(255, 255, 255, 0.15)',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --bg-primary: #030303;
            --bg-secondary: #080808;
            --bg-tertiary: #0f0f0f;
            --bg-card: #0a0a0a;
            --bg-hover: #111111;
            --bg-active: #1a1a1a;
            --border-subtle: rgba(255,255,255,0.04);
            --border-light: rgba(255,255,255,0.08);
            --border-medium: rgba(255,255,255,0.12);
            --accent: #ffffff;
            --accent-glow: rgba(255, 255, 255, 0.2);
            --text-primary: #ffffff;
            --text-secondary: #a1a1aa;
            --text-muted: #52525b;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.6);
        }
        
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow: hidden;
        }
        
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: rgba(255,255,255,0.1); 
            border-radius: 10px;
            transition: background 0.2s ease;
        }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
        
        /* Animações suaves */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }
        .animate-slide-in { animation: slideIn 0.3s ease-out; }
        .animate-scale-in { animation: scaleIn 0.2s ease-out; }
        
        /* Componentes */
        .component-item {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: grab;
            position: relative;
            overflow: hidden;
        }
        
        .component-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
            transition: left 0.5s ease;
        }
        
        .component-item:hover::before {
            left: 100%;
        }
        
        .component-item:active {
            cursor: grabbing;
            transform: scale(0.98);
        }
        
        .component-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .draggable { 
            touch-action: none !important; 
            user-select: none !important; 
            position: relative;
            cursor: move !important;
        }
        
        .draggable:active {
            cursor: grabbing !important;
        }
        
        .drop-zone { 
            min-height: 100px; 
            position: relative;
            transition: all 0.3s ease;
        }
        
        .drop-zone.drag-over {
            background: rgba(255, 255, 255, 0.02);
            border: 2px dashed rgba(255, 255, 255, 0.2);
            box-shadow: inset 0 0 20px rgba(255, 255, 255, 0.05);
        }
        
        .component-hover { 
            transition: all 0.2s ease;
            border-radius: 6px;
        }
        
        .component-hover:hover { 
            outline: 2px solid rgba(255, 255, 255, 0.4); 
            outline-offset: -2px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transform: translateY(-1px);
        }
        
        .component-selected { 
            outline: 2px solid rgba(255, 255, 255, 0.6); 
            outline-offset: -2px; 
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1), 0 4px 12px rgba(0, 0, 0, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }
        
        .toolbar-btn { 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 6px;
            position: relative;
            overflow: hidden;
        }
        
        .toolbar-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.3s ease, height 0.3s ease;
        }
        
        .toolbar-btn:hover::before {
            width: 200%;
            height: 200%;
        }
        
        .toolbar-btn:hover { 
            background: rgba(255,255,255,0.08);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }
        
        .toolbar-btn.active { 
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .toolbar-btn:active {
            transform: translateY(0);
        }
        
        /* Fix para drag bug - usar position ao invés de transform */
        .component-dragging {
            position: relative;
            z-index: 1000;
            opacity: 0.9;
            box-shadow: var(--shadow-xl);
        }
        
        /* Glassmorphism effect */
        .glass {
            background: rgba(10, 10, 10, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
        }
        
        /* Tooltip */
        [data-tooltip] {
            position: relative;
        }
        
        [data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 6px 10px;
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: 6px;
            font-size: 11px;
            white-space: nowrap;
            z-index: 1000;
            margin-bottom: 8px;
            box-shadow: var(--shadow-lg);
            animation: fadeIn 0.2s ease-out;
            pointer-events: none;
        }
        
        [data-tooltip]:hover::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: var(--bg-card);
            margin-bottom: 3px;
            z-index: 1000;
            animation: fadeIn 0.2s ease-out;
            pointer-events: none;
        }
        
        /* Sidebar melhorado */
        .sidebar-section {
            transition: all 0.2s ease;
        }
        
        .sidebar-section:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        
        /* Input melhorado */
        input, select, textarea {
            transition: all 0.2s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
        }
        
        /* Botão primário melhorado */
        .btn-primary {
            background: var(--accent);
            color: var(--bg-primary);
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
            opacity: 0.95;
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        /* Canvas placeholder melhorado */
        #canvas-placeholder {
            animation: pulse 3s ease-in-out infinite;
            user-select: none;
        }
        
        /* Badge/indicador */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-subtle);
        }
        
        /* Property Section */
        .property-section {
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid var(--border-subtle);
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .property-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        /* Garantir que todos os elementos dentro do painel respeitem a largura */
        #propertiesPanel * {
            max-width: 100%;
            box-sizing: border-box;
        }
        
        /* Prevenir overflow horizontal */
        #propertiesPanel {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        /* Inputs não devem ultrapassar o container */
        #propertiesPanel input,
        #propertiesPanel select,
        #propertiesPanel textarea {
            max-width: 100% !important;
            width: 100% !important;
        }
        
        /* Flex containers não devem ultrapassar */
        #propertiesPanel .flex {
            max-width: 100%;
            flex-wrap: wrap;
        }
        
        /* Grid responsivo para dimensões */
        @media (max-width: 400px) {
            .property-section .grid {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }
        }
        
        /* Ajustes adicionais para inputs de dimensões */
        .dimension-input {
            max-width: 100% !important;
        }
        
        /* Garantir que os botões de ação não saiam */
        #propertiesPanel button {
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Scrollbar escondido para properties panel */
        .properties-scroll {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
        }
        
        .properties-scroll::-webkit-scrollbar {
            display: none; /* Chrome/Safari/Opera */
        }
        
        /* Layers Panel */
        #layersList {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
        }
        
        #layersList::-webkit-scrollbar {
            width: 4px;
        }
        
        #layersList::-webkit-scrollbar-track {
            background: transparent;
        }
        
        #layersList::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
        }
        
        #layersList::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .layer-item {
            user-select: none;
        }
        
        .layer-item svg {
            width: 14px;
            height: 14px;
        }
        
        .layer-selected {
            background: rgba(59, 130, 246, 0.15) !important;
            border-color: rgba(59, 130, 246, 0.3) !important;
        }
        
        /* Inputs melhorados */
        .property-input {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid var(--border-subtle) !important;
            color: var(--text-primary) !important;
            transition: all 0.2s ease;
        }
        
        .property-input:focus {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: var(--border-light) !important;
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.05);
        }
        
        .property-input:hover {
            border-color: var(--border-medium);
        }
        
        /* Inputs de dimensões - apenas altura reduzida */
        .dimension-input {
            padding-top: 0.25rem !important; /* py-1 = 4px - altura reduzida */
            padding-bottom: 0.25rem !important;
            padding-left: 0.5rem !important; /* mantém largura */
            padding-right: 0.5rem !important;
            font-size: 0.75rem !important; /* text-xs mantido */
            height: auto !important;
            min-height: 28px !important; /* altura mínima reduzida */
        }
        
        /* Select/Dropdown melhorado */
        .property-select {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid var(--border-subtle) !important;
            color: var(--text-primary) !important;
            transition: all 0.2s ease;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23ffffff' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 6px center;
            background-size: 14px;
            padding-right: 28px !important;
        }
        
        .property-select:focus {
            background-color: rgba(255, 255, 255, 0.08) !important;
            border-color: var(--border-light) !important;
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.05);
        }
        
        .property-select:hover {
            border-color: var(--border-medium);
            background-color: rgba(255, 255, 255, 0.06) !important;
        }
        
        .property-select option {
            background: var(--bg-card);
            color: var(--text-primary);
            padding: 8px;
        }
        
        /* Color picker melhorado */
        .color-picker-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .color-picker-wrapper input[type="color"] {
            width: 36px !important;
            height: 36px !important;
            min-width: 36px !important;
            min-height: 36px !important;
            border: 2px solid var(--border-subtle) !important;
            border-radius: 6px !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            padding: 2px !important;
            flex-shrink: 0 !important;
            background-color: #000000 !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
        }
        
        /* Estilizar o color picker no Chrome/WebKit */
        .color-picker-wrapper input[type="color"]::-webkit-color-swatch-wrapper {
            padding: 0 !important;
            background-color: transparent !important;
            border: none !important;
        }
        
        .color-picker-wrapper input[type="color"]::-webkit-color-swatch {
            border: none !important;
            border-radius: 4px !important;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.1) !important;
        }
        
        /* Estilizar o color picker no Firefox */
        .color-picker-wrapper input[type="color"]::-moz-color-swatch {
            border: none !important;
            border-radius: 4px !important;
            background-color: transparent !important;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.1) !important;
        }
        
        .color-picker-wrapper input[type="color"]:hover {
            border-color: var(--border-light) !important;
            transform: scale(1.05) !important;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1) !important;
        }
        
        .color-picker-wrapper input[type="color"]:focus {
            outline: none !important;
            border-color: var(--border-light) !important;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.15) !important;
        }
        
        /* Eyedropper button */
        .eyedropper-btn {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        
        .eyedropper-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--border-light);
            color: var(--text-primary);
            transform: scale(1.05);
        }
        
        .eyedropper-btn.active {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.5);
            color: #60a5fa;
        }
        
        .eyedropper-btn svg {
            width: 14px;
            height: 14px;
        }
        
        /* Cursor eyedropper */
        body.eyedropper-mode {
            cursor: crosshair !important;
        }
        
        body.eyedropper-mode * {
            cursor: crosshair !important;
        }
        
        /* Label melhorado */
        .property-label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        
        .property-label-small {
            font-size: 9px;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 4px;
        }
        
        /* Section header melhorado */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        
        .section-header-btn {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .section-header-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--border-light);
            color: var(--text-primary);
        }
        
        /* Grid inputs melhorados */
        .grid-input {
            text-align: center;
            font-size: 11px;
        }
        
        /* Value display melhorado */
        .value-display {
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
        }
        
        /* Floating Toolbar - Estilo Figma */
        .floating-toolbar {
            position: fixed;
            display: flex;
            align-items: center;
            gap: 2px;
            padding: 3px;
            background: rgba(30, 30, 30, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            z-index: 10000;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.2s ease, transform 0.2s ease;
            pointer-events: none;
            flex-wrap: nowrap;
        }
        
        .floating-toolbar.visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: all;
        }
        
        .floating-toolbar .toolbar-divider {
            width: 1px;
            height: 16px;
            background: rgba(255, 255, 255, 0.1);
            margin: 0 1px;
            flex-shrink: 0;
        }
        
        .floating-toolbar-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            min-width: 24px;
            min-height: 24px;
            border-radius: 4px;
            border: none;
            background: transparent;
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
            flex-shrink: 0;
            padding: 0;
        }
        
        .floating-toolbar-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }
        
        .floating-toolbar-btn.active {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }
        
        .floating-toolbar-btn svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
        }
        
        .floating-toolbar-btn.danger:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
    </style>
</head>
<body class="h-full m-0 overflow-hidden">

    <!-- Main Container -->
    <div class="flex h-screen">
        
        <!-- Left Sidebar - Components -->
        <div class="w-64 flex flex-col" style="background: var(--bg-card); border-right: 1px solid var(--border-subtle);">
            <!-- Logo SafeNode -->
            <div class="p-3" style="border-bottom: 1px solid var(--border-subtle); background: rgba(10, 10, 10, 0.8);">
                <div class="flex items-center gap-2.5">
                    <img src="assets/img/logos (6).png" alt="SafeNode" class="w-7 h-7 rounded" style="object-fit: contain;">
                    <div>
                        <div class="text-xs font-bold" style="color: var(--text-primary); letter-spacing: 0.5px;">SafeNode Relay</div>
                        <div class="text-[10px]" style="color: var(--text-muted);">Editor Visual</div>
                    </div>
                </div>
            </div>
            
            <!-- Componentes Header -->
            <div class="p-3" style="border-bottom: 1px solid var(--border-subtle); background: rgba(10, 10, 10, 0.5);">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted); letter-spacing: 1px;">Componentes</h2>
                    <span class="badge" id="component-count">7</span>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto p-2.5" id="componentsSidebar" style="scrollbar-width: thin;">
                <!-- Replaced emoji icons with clean SVG icons -->
                <!-- Heading Component -->
                <div class="component-item p-2.5 rounded cursor-move" 
                     style="background: transparent; border: 1px solid transparent; margin-bottom: 2px;"
                     onmouseover="this.style.background='var(--bg-hover)'; this.style.borderColor='var(--border-subtle)'"
                     onmouseout="this.style.background='transparent'; this.style.borderColor='transparent'"
                     data-type="heading" draggable="true"
                     data-tooltip="Arraste para adicionar um título">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/>
                        </svg>
                        <span class="text-xs font-medium flex-1" style="color: var(--text-secondary);">Título</span>
                    </div>
                </div>

                <!-- Text Component -->
                <div class="component-item p-2.5 rounded cursor-move" 
                     style="background: transparent; border: 1px solid transparent; margin-bottom: 2px;"
                     onmouseover="this.style.background='var(--bg-hover)'; this.style.borderColor='var(--border-subtle)'"
                     onmouseout="this.style.background='transparent'; this.style.borderColor='transparent'"
                     data-type="text" draggable="true"
                     data-tooltip="Arraste para adicionar texto">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
                        </svg>
                        <span class="text-xs font-medium flex-1" style="color: var(--text-secondary);">Texto</span>
                    </div>
                </div>

                <!-- Button Component -->
                <div class="component-item p-2.5 rounded cursor-move" 
                     style="background: transparent; border: 1px solid transparent; margin-bottom: 2px;"
                     onmouseover="this.style.background='var(--bg-hover)'; this.style.borderColor='var(--border-subtle)'"
                     onmouseout="this.style.background='transparent'; this.style.borderColor='transparent'"
                     data-type="button" draggable="true"
                     data-tooltip="Arraste para adicionar um botão">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6"/>
                        </svg>
                        <span class="text-xs font-medium flex-1" style="color: var(--text-secondary);">Botão</span>
                    </div>
                </div>

                <!-- Image Component -->
                <div class="component-item p-2.5 rounded cursor-move" 
                     style="background: transparent; border: 1px solid transparent; margin-bottom: 2px;"
                     onmouseover="this.style.background='var(--bg-hover)'; this.style.borderColor='var(--border-subtle)'"
                     onmouseout="this.style.background='transparent'; this.style.borderColor='transparent'"
                     data-type="image" draggable="true"
                     data-tooltip="Arraste para adicionar uma imagem">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                        </svg>
                        <span class="text-xs font-medium flex-1" style="color: var(--text-secondary);">Imagem</span>
                    </div>
                </div>

                <!-- Divider Component -->
                <div class="component-item p-2.5 rounded cursor-move" 
                     style="background: transparent; border: 1px solid transparent; margin-bottom: 2px;"
                     onmouseover="this.style.background='var(--bg-hover)'; this.style.borderColor='var(--border-subtle)'"
                     onmouseout="this.style.background='transparent'; this.style.borderColor='transparent'"
                     data-type="divider" draggable="true"
                     data-tooltip="Arraste para adicionar uma divisória">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                        </svg>
                        <span class="text-xs font-medium flex-1" style="color: var(--text-secondary);">Divisória</span>
                    </div>
                </div>

                <!-- Spacer Component -->
                <div class="component-item p-2.5 rounded cursor-move" 
                     style="background: transparent; border: 1px solid transparent; margin-bottom: 2px;"
                     onmouseover="this.style.background='var(--bg-hover)'; this.style.borderColor='var(--border-subtle)'"
                     onmouseout="this.style.background='transparent'; this.style.borderColor='transparent'"
                     data-type="spacer" draggable="true"
                     data-tooltip="Arraste para adicionar espaçamento">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/>
                        </svg>
                        <span class="text-xs font-medium flex-1" style="color: var(--text-secondary);">Espaçador</span>
                    </div>
                </div>

                <!-- Container Component -->
                <div class="component-item p-2.5 rounded cursor-move" 
                     style="background: transparent; border: 1px solid transparent; margin-bottom: 2px;"
                     onmouseover="this.style.background='var(--bg-hover)'; this.style.borderColor='var(--border-subtle)'"
                     onmouseout="this.style.background='transparent'; this.style.borderColor='transparent'"
                     data-type="container" draggable="true"
                     data-tooltip="Arraste para adicionar um container">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                        </svg>
                        <span class="text-xs font-medium flex-1" style="color: var(--text-secondary);">Container</span>
                    </div>
                </div>
            </div>
            
            <!-- Layers Panel - Adicionados -->
            <div style="border-top: 1px solid var(--border-subtle); background: rgba(10, 10, 10, 0.5);" class="p-3">
                <div class="flex items-center justify-between mb-2.5">
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider" style="color: var(--text-muted); letter-spacing: 1.2px;">Adicionados</h3>
                    <span class="badge" id="layers-count" style="font-size: 9px; padding: 1px 6px;">0</span>
                </div>
                <div id="layersList" class="space-y-0.5 max-h-64 overflow-y-auto" style="scrollbar-width: thin;">
                    <div class="text-[10px] text-center py-4" style="color: var(--text-muted); opacity: 0.5;" id="layers-empty">
                        Nenhum componente adicionado
                    </div>
                </div>
            </div>

            <!-- Templates Section -->
            <div style="border-top: 1px solid var(--border-subtle); background: rgba(10, 10, 10, 0.5);" class="p-3">
                <div class="flex items-center justify-between mb-2.5">
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider" style="color: var(--text-muted); letter-spacing: 1.2px;">Templates</h3>
                    <span class="badge" style="font-size: 9px; padding: 1px 6px;">3</span>
                </div>
                <div class="space-y-1">
                    <button onclick="loadTemplate('welcome')" class="w-full p-2 rounded text-[11px] text-left transition-all flex items-center gap-2"
                            style="background: transparent; border: 1px solid transparent; color: var(--text-secondary);"
                            onmouseover="this.style.background='var(--bg-hover)'; this.style.borderColor='var(--border-subtle)'"
                            onmouseout="this.style.background='transparent'; this.style.borderColor='transparent'"
                            data-tooltip="Carregar template de boas-vindas">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" style="color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                        </svg>
                        <span class="text-xs font-medium flex-1" style="color: var(--text-secondary);">Boas-vindas</span>
                    </button>
                    <button onclick="loadTemplate('newsletter')" class="w-full p-2 rounded text-[11px] text-left transition-all flex items-center gap-2"
                            style="background: transparent; border: 1px solid transparent; color: var(--text-secondary);"
                            onmouseover="this.style.background='var(--bg-hover)'; this.style.borderColor='var(--border-subtle)'"
                            onmouseout="this.style.background='transparent'; this.style.borderColor='transparent'"
                            data-tooltip="Carregar template de newsletter">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" style="color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/>
                        </svg>
                        <span class="text-xs font-medium flex-1" style="color: var(--text-secondary);">Newsletter</span>
                    </button>
                    <button onclick="loadTemplate('promo')" class="w-full p-2 rounded text-[11px] text-left transition-all flex items-center gap-2"
                            style="background: transparent; border: 1px solid transparent; color: var(--text-secondary);"
                            onmouseover="this.style.background='var(--bg-hover)'; this.style.borderColor='var(--border-subtle)'"
                            onmouseout="this.style.background='transparent'; this.style.borderColor='transparent'"
                            data-tooltip="Carregar template de promoção">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" style="color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
                        </svg>
                        <span class="text-xs font-medium flex-1" style="color: var(--text-secondary);">Promoção</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Center Canvas -->
        <div class="flex-1 flex flex-col" style="background: var(--bg-primary);">
            <!-- Top Toolbar - Estilo Figma -->
            <div class="p-2.5 flex items-center justify-between" style="background: var(--bg-card); border-bottom: 1px solid var(--border-subtle);">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 px-2 py-1 rounded" style="background: rgba(255, 255, 255, 0.03);">
                        <svg class="w-4 h-4" style="color: var(--text-secondary);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                        </svg>
                        <span class="text-xs font-medium" style="color: var(--text-primary);">Email Builder</span>
                    </div>
                </div>

                <div class="flex items-center gap-1.5">
                    <!-- Undo/Redo - Estilo Figma -->
                    <div class="flex items-center rounded" style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-subtle); overflow: hidden;">
                        <button onclick="undo()" class="toolbar-btn px-2.5 py-1.5" style="color: var(--text-secondary);" 
                                onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.color='var(--text-primary)'" 
                                onmouseout="this.style.background='transparent'; this.style.color='var(--text-secondary)'"
                                data-tooltip="Desfazer (Ctrl+Z)">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
                            </svg>
                        </button>
                        <div class="w-px h-4" style="background: var(--border-subtle);"></div>
                        <button onclick="redo()" class="toolbar-btn px-2.5 py-1.5" style="color: var(--text-secondary);"
                                onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.color='var(--text-primary)'" 
                                onmouseout="this.style.background='transparent'; this.style.color='var(--text-secondary)'"
                                data-tooltip="Refazer (Ctrl+Shift+Z)">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l6-6m0 0l-6-6m6 6H9a6 6 0 000 12h3"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Duplicate/Delete -->
                    <div class="flex items-center rounded" style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-subtle); overflow: hidden;">
                        <button onclick="duplicateSelected()" class="toolbar-btn px-2.5 py-1.5" style="color: var(--text-secondary);"
                                onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.color='var(--text-primary)'" 
                                onmouseout="this.style.background='transparent'; this.style.color='var(--text-secondary)'"
                                data-tooltip="Duplicar (Ctrl+D)">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/>
                            </svg>
                        </button>
                        <div class="w-px h-4" style="background: var(--border-subtle);"></div>
                        <button onclick="deleteSelected()" class="toolbar-btn px-2.5 py-1.5" style="color: var(--text-secondary);"
                                onmouseover="this.style.background='rgba(239,68,68,0.1)'; this.style.color='#ef4444'" 
                                onmouseout="this.style.background='transparent'; this.style.color='var(--text-secondary)'"
                                data-tooltip="Deletar (Delete)">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                            </svg>
                        </button>
                    </div>

                    <div class="w-px h-5" style="background: var(--border-subtle);"></div>

                    <!-- Canvas Size Controls - Estilo Figma -->
                    <div class="flex items-center gap-0.5 rounded" style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-subtle); padding: 2px;">
                        <button onclick="setCanvasSize('desktop')" id="btn-desktop" class="toolbar-btn active px-2.5 py-1 rounded text-[11px] font-medium flex items-center gap-1.5" style="font-size: 11px;">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
                            </svg>
                            Desktop
                        </button>
                        <button onclick="setCanvasSize('mobile')" id="btn-mobile" class="toolbar-btn px-2.5 py-1 rounded text-[11px] font-medium flex items-center gap-1.5" style="font-size: 11px;">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                            </svg>
                            Mobile
                        </button>
                        <button onclick="showCustomSize()" id="btn-custom" class="toolbar-btn px-2.5 py-1 rounded text-[11px] font-medium flex items-center gap-1.5" style="font-size: 11px;">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076-.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Custom
                        </button>
                    </div>

                    <div class="w-px h-5" style="background: var(--border-subtle);"></div>

                    <!-- Export Button - Estilo Figma -->
                    <button onclick="exportHTML()" class="px-3 py-1.5 rounded text-[11px] font-medium transition-all flex items-center gap-1.5 btn-primary"
                            data-tooltip="Exportar HTML do template">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        Exportar
                    </button>
                </div>
            </div>

            <!-- Floating Toolbar - Estilo Figma -->
            <div id="floatingToolbar" class="floating-toolbar">
                <!-- Cursor/Select Tool -->
                <button class="floating-toolbar-btn active" id="tool-cursor" data-tooltip="Selecionar (V)" onclick="setTool('cursor')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.143l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59"/>
                    </svg>
                </button>
                
                <div class="toolbar-divider"></div>
                
                <!-- Move Tool -->
                <button class="floating-toolbar-btn" id="tool-move" data-tooltip="Mover (M)" onclick="setTool('move')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776"/>
                    </svg>
                </button>
                
                <div class="toolbar-divider"></div>
                
                <!-- Duplicate -->
                <button class="floating-toolbar-btn" data-tooltip="Duplicar (Ctrl+D)" onclick="duplicateSelected()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/>
                    </svg>
                </button>
                
                <!-- Delete -->
                <button class="floating-toolbar-btn danger" data-tooltip="Deletar (Delete)" onclick="deleteSelected()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                    </svg>
                </button>
                
                <div class="toolbar-divider"></div>
                
                <!-- Align Left -->
                <button class="floating-toolbar-btn" data-tooltip="Alinhar à esquerda" onclick="alignElement('left')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h8.5m-8.5 5.25h16.5"/>
                    </svg>
                </button>
                
                <!-- Align Center -->
                <button class="floating-toolbar-btn" data-tooltip="Alinhar ao centro" onclick="alignElement('center')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M6 12h12m-12 5.25h16.5"/>
                    </svg>
                </button>
                
                <!-- Align Right -->
                <button class="floating-toolbar-btn" data-tooltip="Alinhar à direita" onclick="alignElement('right')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M12.25 12h8.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>
                
                <div class="toolbar-divider"></div>
                
                <!-- Bring to Front -->
                <button class="floating-toolbar-btn" data-tooltip="Trazer para frente" onclick="changeZIndex('front')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                </button>
                
                <!-- Send to Back -->
                <button class="floating-toolbar-btn" data-tooltip="Enviar para trás" onclick="changeZIndex('back')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                    </svg>
                </button>
                    </div>

            <!-- Canvas Area - Estilo Figma -->
            <div class="flex-1 overflow-auto flex items-start justify-center" style="background: var(--bg-primary); background-image: 
                linear-gradient(rgba(255,255,255,0.01) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.01) 1px, transparent 1px);
                background-size: 20px 20px; overflow-x: auto; overflow-y: auto;">
                <div class="p-8" id="canvasContainer" style="min-width: 100%;">
                    <!-- Desktop Canvas -->
                    <div id="desktopCanvasWrapper" class="mx-auto">
                        <div id="emailCanvas" class="mx-auto bg-white drop-zone shadow-2xl border-2" style="width: 900px; min-height: 600px; transition: all 0.3s ease; position: relative; border-color: rgba(0,0,0,0.1); box-shadow: 0 4px 24px rgba(0,0,0,0.15); border-radius: 8px; overflow: visible;">
                            <div id="canvas-placeholder" class="p-12 text-gray-400 text-center" style="pointer-events: none;">
                                <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                </svg>
                                <div class="text-sm font-medium mb-1" style="color: var(--text-muted);">Arraste componentes aqui</div>
                                <div class="text-xs" style="color: var(--text-muted); opacity: 0.6;">ou use um template pronto</div>
                </div>
            </div>
        </div>

                    <!-- Mobile Canvas with Device Mockup -->
                    <div id="mobileCanvasWrapper" class="mx-auto hidden">
                        <div class="relative" style="padding: 40px 20px;">
                            <!-- Phone Frame -->
                            <div class="relative mx-auto" style="width: 480px; max-width: 100%;">
                                <!-- Phone Body -->
                                <div class="relative rounded-[3rem] p-2" style="background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%); box-shadow: 0 20px 60px rgba(0,0,0,0.5), inset 0 0 0 2px rgba(255,255,255,0.1);">
                                    <!-- Screen Bezel -->
                                    <div class="rounded-[2.5rem] overflow-hidden" style="background: #000;">
                                        <!-- Notch -->
                                        <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-32 h-6 rounded-b-2xl z-10" style="background: #000;"></div>
                                        
                                        <!-- Status Bar Area -->
                                        <div class="h-8 flex items-center justify-between px-6 pt-1 text-white text-xs" style="background: rgba(255,255,255,0.05);">
                                            <span class="font-medium">9:41</span>
                                            <div class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                                                </svg>
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M17.778 8.222c-4.296-4.296-11.26-4.296-15.556 0A1 1 0 01.808 6.808c5.076-5.076 13.308-5.076 18.384 0a1 1 0 01-1.414 1.414zM14.95 11.05a7 7 0 00-9.9 0 1 1 0 01-1.414-1.414 9 9 0 0112.728 0 1 1 0 01-1.414 1.414zM12.12 13.88a3 3 0 00-4.242 0 1 1 0 01-1.415-1.415 5 5 0 017.072 0 1 1 0 01-1.415 1.415zM9 16a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
            </div>
            
                                        <!-- Canvas Content -->
                                        <div id="emailCanvasMobile" class="bg-white drop-zone" style="width: 100%; min-height: 700px; position: relative; overflow: visible;">
                                            <div id="canvas-placeholder-mobile" class="p-12 text-gray-400 text-center" style="pointer-events: none;">
                                                <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                                </svg>
                                                <div class="text-xs font-medium mb-1" style="color: var(--text-muted);">Arraste componentes aqui</div>
                                                <div class="text-[10px]" style="color: var(--text-muted); opacity: 0.6;">ou use um template pronto</div>
                                            </div>
                                        </div>
                                        
                                        <!-- Home Indicator -->
                                        <div class="absolute bottom-2 left-1/2 transform -translate-x-1/2 w-32 h-1 rounded-full" style="background: rgba(255,255,255,0.3);"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar - Properties - Estilo Figma -->
        <div class="w-80 flex flex-col" style="background: var(--bg-card); border-left: 1px solid var(--border-subtle); min-width: 280px; max-width: 320px; width: 100%; max-height: 100vh; overflow: hidden;">
            <!-- Header -->
            <div class="p-3 flex-shrink-0" style="border-bottom: 1px solid var(--border-subtle); background: rgba(10, 10, 10, 0.5);">
                <h2 class="text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted); letter-spacing: 1px;">Propriedades</h2>
            </div>
            
            <!-- Properties Panel -->
            <div id="propertiesPanel" class="flex-1 overflow-y-auto overflow-x-hidden properties-scroll" style="min-height: 0; max-width: 100%; padding: 0 16px;">
                <div class="p-6 text-center">
                    <svg class="w-10 h-10 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076-.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <div class="text-xs font-medium mb-1" style="color: var(--text-muted);">Nenhum elemento selecionado</div>
                    <div class="text-[10px]" style="color: var(--text-muted); opacity: 0.6;">Clique em um componente para editar</div>
                </div>
            </div>

            <!-- Actions -->
            <div style="border-top: 1px solid var(--border-subtle); background: rgba(10, 10, 10, 0.3);" class="p-3">
                <button onclick="clearCanvas()" class="w-full p-2 rounded text-xs transition-all flex items-center justify-center gap-2"
                        style="background: transparent; border: 1px solid var(--border-subtle); color: var(--text-secondary);"
                        onmouseover="this.style.background='var(--bg-hover)'; this.style.borderColor='var(--border-light)'"
                        onmouseout="this.style.background='transparent'; this.style.borderColor='var(--border-subtle)'"
                        data-tooltip="Limpar todo o canvas">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    Limpar Canvas
                </button>
            </div>
        </div>
    </div>

    <!-- Custom Size Modal -->
    <div id="customSizeModal" class="hidden fixed inset-0 flex items-center justify-center z-50" style="background: rgba(0,0,0,0.7);">
        <div class="rounded-lg p-6 w-96" style="background: var(--bg-card); border: 1px solid var(--border-subtle);">
            <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Tamanho Personalizado</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium mb-1.5" style="color: var(--text-secondary);">Largura (px)</label>
                    <input type="number" id="customWidth" value="600" min="300" max="1200" 
                           class="w-full rounded px-3 py-2 text-sm focus:outline-none transition-colors"
                           style="background: var(--bg-hover); border: 1px solid var(--border-subtle); color: var(--text-primary);"
                           onfocus="this.style.borderColor='var(--border-light)'; this.style.background='var(--bg-tertiary)'"
                           onblur="this.style.borderColor='var(--border-subtle)'; this.style.background='var(--bg-hover)'">
                </div>
                <div class="flex gap-2 pt-2">
                    <button onclick="applyCustomSize()" class="flex-1 py-2 rounded text-sm font-medium transition-all"
                            style="background: var(--accent); color: var(--bg-primary);"
                            onmouseover="this.style.opacity='0.9'"
                            onmouseout="this.style.opacity='1'">
                        Aplicar
                    </button>
                    <button onclick="closeCustomSize()" class="flex-1 py-2 rounded text-sm font-medium transition-all"
                            style="background: var(--bg-hover); border: 1px solid var(--border-subtle); color: var(--text-secondary);"
                            onmouseover="this.style.background='var(--bg-tertiary)'"
                            onmouseout="this.style.background='var(--bg-hover)'">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedElement = null;
        let componentCounter = 0;
        let history = [];
        let historyIndex = -1;
        const MAX_HISTORY = 50;

        // Initialize drag from sidebar
        document.querySelectorAll('.component-item').forEach(item => {
            item.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('componentType', this.dataset.type);
            });
        });

        // Canvas drop zone - both desktop and mobile
        const desktopCanvas = document.getElementById('emailCanvas');
        const mobileCanvas = document.getElementById('emailCanvasMobile');
        
        function setupCanvasDrop(canvas) {
            if (!canvas) return;
        
        canvas.addEventListener('dragover', function(e) {
            e.preventDefault();
                canvas.classList.add('drag-over');
                canvas.style.borderColor = 'rgba(255, 255, 255, 0.3)';
        });

        canvas.addEventListener('dragleave', function(e) {
                canvas.classList.remove('drag-over');
                canvas.style.borderColor = 'rgba(0,0,0,0.1)';
        });

        canvas.addEventListener('drop', function(e) {
            e.preventDefault();
                canvas.classList.remove('drag-over');
                canvas.style.borderColor = 'rgba(0,0,0,0.1)';
            
            // Verificar se é uma imagem sendo arrastada
            const files = e.dataTransfer.files;
            if (files && files.length > 0) {
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    if (file.type.indexOf('image') !== -1) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            const imageUrl = event.target.result;
                            // Obter posição do drop
                            const rect = canvas.getBoundingClientRect();
                            const x = e.clientX - rect.left;
                            const y = e.clientY - rect.top;
                            pasteImageAtPosition(imageUrl, x, y);
                        };
                        reader.readAsDataURL(file);
                        return; // Processar apenas a primeira imagem
                    }
                }
            }
            
            // Verificar se é um componente da sidebar
            const componentType = e.dataTransfer.getData('componentType');
            if (componentType) {
                addComponent(componentType);
                saveHistory();
            }
        });
        }
        
        setupCanvasDrop(desktopCanvas);
        setupCanvasDrop(mobileCanvas);

        // Suporte para colar imagens
        document.addEventListener('paste', function(e) {
            // Verificar se há imagens no clipboard
            const items = e.clipboardData.items;
            
            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                
                // Verificar se é uma imagem
                if (item.type.indexOf('image') !== -1) {
                    e.preventDefault();
                    
                    const blob = item.getAsFile();
                    const reader = new FileReader();
                    
                    reader.onload = function(event) {
                        const imageUrl = event.target.result;
                        pasteImage(imageUrl);
                    };
                    
                    reader.readAsDataURL(blob);
                    break;
                }
            }
        });
        
        // Função para adicionar imagem colada (centralizada)
        function pasteImage(imageUrl) {
            const canvas = getActiveCanvas();
            if (!canvas) {
                console.error('Canvas não encontrado');
                return;
            }
            
            const canvasWidth = canvas.offsetWidth || 600;
            const canvasHeight = canvas.offsetHeight || 800;
            
            // Centralizar a imagem no canvas
            const startX = canvasWidth / 2;
            const startY = canvasHeight / 2;
            
            pasteImageAtPosition(imageUrl, startX, startY);
        }
        
        // Função para adicionar imagem em posição específica
        function pasteImageAtPosition(imageUrl, x, y) {
            const canvas = getActiveCanvas();
            if (!canvas) {
                console.error('Canvas não encontrado');
                return;
            }
            
            const id = `component-${componentCounter++}`;
            const canvasWidth = canvas.offsetWidth || 600;
            const canvasHeight = canvas.offsetHeight || 800;
            
            // Criar imagem para obter dimensões
            const img = new Image();
            img.onload = function() {
                let imgWidth = this.width;
                let imgHeight = this.height;
                
                // Redimensionar se for muito grande
                const maxWidth = Math.min(600, canvasWidth * 0.9); // 90% do canvas ou máximo 600px
                const maxHeight = canvasHeight * 0.6; // 60% da altura do canvas
                
                if (imgWidth > maxWidth) {
                    const ratio = maxWidth / imgWidth;
                    imgWidth = maxWidth;
                    imgHeight = imgHeight * ratio;
                }
                
                if (imgHeight > maxHeight) {
                    const ratio = maxHeight / imgHeight;
                    imgHeight = maxHeight;
                    imgWidth = imgWidth * ratio;
                }
                
                // Ajustar posição (centralizar na posição do clique/drop)
                const startX = Math.max(0, Math.min(x - imgWidth / 2, canvasWidth - imgWidth));
                const startY = Math.max(0, Math.min(y - imgHeight / 2, canvasHeight - imgHeight));
                
                // Criar elemento de imagem
                const html = `<img id="${id}" src="${imageUrl}" alt="Imagem colada" class="component-hover draggable cursor-move h-auto rounded-md border border-gray-200" data-type="image" data-x="${startX}" data-y="${startY}" style="position: absolute; left: ${startX}px; top: ${startY}px; display: block; width: ${imgWidth}px; height: ${imgHeight}px; max-width: 100%; touch-action: none; user-select: none; object-fit: contain;">`;
                
                // Remover placeholder se existir
                const placeholder = canvas.querySelector('#canvas-placeholder, #canvas-placeholder-mobile');
                if (placeholder) {
                    placeholder.remove();
                }
                
                canvas.insertAdjacentHTML('beforeend', html);
                
                const newElement = canvas.querySelector(`#${id}`);
                if (newElement) {
                    newElement.style.display = 'block';
                    newElement.style.visibility = 'visible';
                    newElement.style.opacity = '1';
                    
                    // Adicionar ao painel de layers
                    updateLayersPanel();
                    
                    // Make draggable
                    interact(`#${id}`).draggable({
                        inertia: false,
                        autoScroll: true,
                        listeners: {
                            start: function(event) {
                                const target = event.target;
                                const currentPos = target.style.position || window.getComputedStyle(target).position;
                                if (currentPos !== 'absolute') {
                                    const rect = target.getBoundingClientRect();
                                    const canvasRect = canvas.getBoundingClientRect();
                                    const left = rect.left - canvasRect.left;
                                    const top = rect.top - canvasRect.top;
                                    
                                    target.style.setProperty('position', 'absolute', 'important');
                                    target.style.left = left + 'px';
                                    target.style.top = top + 'px';
                                    target.setAttribute('data-x', left);
                                    target.setAttribute('data-y', top);
                                }
                                target.style.zIndex = '1000';
                                target.style.cursor = 'grabbing';
                            },
                            move: dragMoveListener,
                            end: function(event) {
                                const target = event.target;
                                target.style.cursor = 'move';
                                target.style.zIndex = '';
                                saveHistory();
                            }
                        }
                    });
                    
                    // Make resizable - importante para ajustar imagens grandes
                    // Salvar dimensões originais para referência
                    const originalWidth = imgWidth;
                    const originalHeight = imgHeight;
                    
                    interact(`#${id}`).resizable({
                        edges: { left: true, right: true, bottom: true, top: true },
                        listeners: {
                            move: function(event) {
                                const target = event.target;
                                let x = (parseFloat(target.getAttribute('data-x')) || 0);
                                let y = (parseFloat(target.getAttribute('data-y')) || 0);
                                
                                // Atualizar largura e altura
                                let newWidth = event.rect.width;
                                let newHeight = event.rect.height;
                                
                                // Garantir tamanho mínimo
                                if (newWidth < 50) newWidth = 50;
                                if (newHeight < 50) newHeight = 50;
                                
                                target.style.width = newWidth + 'px';
                                target.style.height = newHeight + 'px';
                                
                                // Ajustar posição quando redimensionar
                                x += event.deltaRect.left;
                                y += event.deltaRect.top;
                                
                                target.style.left = x + 'px';
                                target.style.top = y + 'px';
                                target.setAttribute('data-x', x);
                                target.setAttribute('data-y', y);
                                
                                // Garantir que não ultrapasse os limites do canvas
                                const canvasRect = canvas.getBoundingClientRect();
                                const maxX = canvasRect.width - newWidth;
                                const maxY = canvasRect.height - newHeight;
                                
                                if (x < 0) {
                                    target.style.left = '0px';
                                    target.setAttribute('data-x', 0);
                                }
                                if (x > maxX) {
                                    target.style.left = maxX + 'px';
                                    target.setAttribute('data-x', maxX);
                                }
                                if (y < 0) {
                                    target.style.top = '0px';
                                    target.setAttribute('data-y', 0);
                                }
                                if (y > maxY) {
                                    target.style.top = maxY + 'px';
                                    target.setAttribute('data-y', maxY);
                                }
                                
                                saveHistory();
                            }
                        },
                        modifiers: [
                            // Limitar ao tamanho mínimo e máximo
                            interact.modifiers.restrictSize({
                                min: { width: 50, height: 50 },
                                max: { width: canvasWidth * 1.5, height: canvasHeight * 1.5 }
                            })
                        ]
                    });
                    
                    // Select on click
                    let clickStartTime = 0;
                    let clickStartPos = { x: 0, y: 0 };
                    
                    newElement.addEventListener('mousedown', function(e) {
                        clickStartTime = Date.now();
                        clickStartPos = { x: e.clientX, y: e.clientY };
                    });
                    
                    newElement.addEventListener('click', function(e) {
                        const clickDuration = Date.now() - clickStartTime;
                        const clickDistance = Math.sqrt(
                            Math.pow(e.clientX - clickStartPos.x, 2) + 
                            Math.pow(e.clientY - clickStartPos.y, 2)
                        );
                        
                        if (clickDuration < 300 && clickDistance < 5) {
                            e.stopPropagation();
                            selectElement(this);
                        }
                    });
                    
                    // Selecionar a imagem após adicionar
                    setTimeout(() => {
                        selectElement(newElement);
                        syncCanvasContent();
                        saveHistory();
                    }, 100);
                }
            };
            
            img.onerror = function() {
                console.error('Erro ao carregar imagem');
            };
            
            img.src = imageUrl;
        }

        function saveHistory() {
            const activeCanvas = getActiveCanvas();
            const state = activeCanvas.innerHTML;
            if (historyIndex < history.length - 1) {
                history = history.slice(0, historyIndex + 1);
            }
            history.push(state);
            if (history.length > MAX_HISTORY) {
                history.shift();
            } else {
                historyIndex++;
            }
        }

        function undo() {
            if (historyIndex > 0) {
                historyIndex--;
                const desktopCanvas = document.getElementById('emailCanvas');
                const mobileCanvas = document.getElementById('emailCanvasMobile');
                const state = history[historyIndex];
                if (desktopCanvas) desktopCanvas.innerHTML = state;
                if (mobileCanvas) mobileCanvas.innerHTML = state;
                reattachEventListeners();
                selectedElement = null;
                document.getElementById('propertiesPanel').innerHTML = 
                    '<div class="p-6 text-center"><svg class="w-10 h-10 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076-.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><div class="text-xs font-medium mb-1" style="color: var(--text-muted);">Nenhum elemento selecionado</div><div class="text-[10px]" style="color: var(--text-muted); opacity: 0.6;">Clique em um componente para editar</div></div>';
            }
        }

        function redo() {
            if (historyIndex < history.length - 1) {
                historyIndex++;
                const desktopCanvas = document.getElementById('emailCanvas');
                const mobileCanvas = document.getElementById('emailCanvasMobile');
                const state = history[historyIndex];
                if (desktopCanvas) desktopCanvas.innerHTML = state;
                if (mobileCanvas) mobileCanvas.innerHTML = state;
                reattachEventListeners();
                selectedElement = null;
                document.getElementById('propertiesPanel').innerHTML = 
                    '<div class="p-6 text-center"><svg class="w-10 h-10 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076-.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><div class="text-xs font-medium mb-1" style="color: var(--text-muted);">Nenhum elemento selecionado</div><div class="text-[10px]" style="color: var(--text-muted); opacity: 0.6;">Clique em um componente para editar</div></div>';
            }
        }

        function reattachEventListeners() {
            const activeCanvas = getActiveCanvas();
            activeCanvas.querySelectorAll('[id^="component-"]').forEach(el => {
                // Garantir que elementos existentes tenham position absolute
                if (el.style.position !== 'absolute') {
                    el.style.position = 'absolute';
                    if (!el.style.left && !el.getAttribute('data-x')) {
                        el.style.left = '0px';
                        el.setAttribute('data-x', '0');
                    }
                    if (!el.style.top && !el.getAttribute('data-y')) {
                        el.style.top = '0px';
                        el.setAttribute('data-y', '0');
                    }
                }
                
                interact(`#${el.id}`).draggable({
                    inertia: false,
                    autoScroll: true,
                    listeners: {
                        start: function(event) {
                            const target = event.target;
                            if (target.hasAttribute('contenteditable')) {
                                target.setAttribute('contenteditable', 'false');
                                target._wasContentEditable = true;
                            }
                            // Garantir position absolute
                            const currentPos = target.style.position || window.getComputedStyle(target).position;
                            if (currentPos !== 'absolute') {
                                const rect = target.getBoundingClientRect();
                                const canvas = getActiveCanvas();
                                const canvasRect = canvas.getBoundingClientRect();
                                const left = rect.left - canvasRect.left;
                                const top = rect.top - canvasRect.top;
                                
                                target.style.setProperty('position', 'absolute', 'important');
                                target.style.left = left + 'px';
                                target.style.top = top + 'px';
                                target.setAttribute('data-x', left);
                                target.setAttribute('data-y', top);
                            }
                            target.style.zIndex = '1000';
                            target.style.cursor = 'grabbing';
                        },
                        move: dragMoveListener,
                        end: function(event) {
                            const target = event.target;
                            if (target._wasContentEditable) {
                                target.setAttribute('contenteditable', 'true');
                                target._wasContentEditable = false;
                            }
                            target.classList.remove('component-dragging');
                            target.style.zIndex = '';
                            target.style.cursor = 'move';
                        }
                    }
                });

                el.addEventListener('click', function(e) {
                    e.stopPropagation();
                    selectElement(this);
                });
                
                // Re-setup auto font size for text elements
                setupAutoFontSize(el);
            });
        }

        function setCanvasSize(size) {
            const desktopCanvas = document.getElementById('emailCanvas');
            const mobileCanvas = document.getElementById('emailCanvasMobile');
            const desktopWrapper = document.getElementById('desktopCanvasWrapper');
            const mobileWrapper = document.getElementById('mobileCanvasWrapper');
            
            document.querySelectorAll('[id^="btn-"]').forEach(btn => btn.classList.remove('active'));
            
            if (size === 'desktop') {
                desktopWrapper.classList.remove('hidden');
                mobileWrapper.classList.add('hidden');
                desktopCanvas.style.width = '1400px';
                document.getElementById('btn-desktop').classList.add('active');
                // Sincronizar conteúdo
                if (mobileCanvas && mobileCanvas.innerHTML !== desktopCanvas.innerHTML) {
                    mobileCanvas.innerHTML = desktopCanvas.innerHTML;
                    reattachEventListeners();
                }
            } else if (size === 'mobile') {
                desktopWrapper.classList.add('hidden');
                mobileWrapper.classList.remove('hidden');
                document.getElementById('btn-mobile').classList.add('active');
                // Sincronizar conteúdo
                if (desktopCanvas && desktopCanvas.innerHTML !== mobileCanvas.innerHTML) {
                    mobileCanvas.innerHTML = desktopCanvas.innerHTML;
                    reattachEventListeners();
                }
            }
        }

        function showCustomSize() {
            document.getElementById('customSizeModal').classList.remove('hidden');
        }

        function closeCustomSize() {
            document.getElementById('customSizeModal').classList.add('hidden');
        }

        function applyCustomSize() {
            const width = document.getElementById('customWidth').value;
            const desktopCanvas = document.getElementById('emailCanvas');
            const desktopWrapper = document.getElementById('desktopCanvasWrapper');
            const mobileWrapper = document.getElementById('mobileCanvasWrapper');
            
            desktopWrapper.classList.remove('hidden');
            mobileWrapper.classList.add('hidden');
            desktopCanvas.style.width = width + 'px';
            
            document.querySelectorAll('[id^="btn-"]').forEach(btn => btn.classList.remove('active'));
            document.getElementById('btn-custom').classList.add('active');
            
            closeCustomSize();
        }

        function duplicateSelected() {
            if (selectedElement) {
                const clone = selectedElement.cloneNode(true);
                const newId = `component-${componentCounter++}`;
                clone.id = newId;
                clone.classList.remove('component-selected');
                
                selectedElement.parentNode.insertBefore(clone, selectedElement.nextSibling);
                
                interact(`#${newId}`).draggable({
                    inertia: false,
                    autoScroll: true,
                    listeners: {
                        start: function(event) {
                            const target = event.target;
                            if (target.hasAttribute('contenteditable')) {
                                target.setAttribute('contenteditable', 'false');
                                target._wasContentEditable = true;
                            }
                            // Garantir position absolute
                            const currentPos = target.style.position || window.getComputedStyle(target).position;
                            if (currentPos !== 'absolute') {
                                const rect = target.getBoundingClientRect();
                                const canvas = getActiveCanvas();
                                const canvasRect = canvas.getBoundingClientRect();
                                const left = rect.left - canvasRect.left;
                                const top = rect.top - canvasRect.top;
                                
                                target.style.setProperty('position', 'absolute', 'important');
                                target.style.left = left + 'px';
                                target.style.top = top + 'px';
                                target.setAttribute('data-x', left);
                                target.setAttribute('data-y', top);
                            }
                            target.style.zIndex = '1000';
                            target.style.cursor = 'grabbing';
                        },
                        move: dragMoveListener,
                        end: function(event) {
                            const target = event.target;
                            if (target._wasContentEditable) {
                                target.setAttribute('contenteditable', 'true');
                                target._wasContentEditable = false;
                            }
                            target.classList.remove('component-dragging');
                            target.style.zIndex = '';
                            target.style.cursor = 'move';
                        }
                    }
                });

                clone.addEventListener('click', function(e) {
                    e.stopPropagation();
                    selectElement(this);
                });

                updateLayersPanel();
                saveHistory();
            }
        }

        // Get active canvas (desktop or mobile)
        function getActiveCanvas() {
            const desktopWrapper = document.getElementById('desktopCanvasWrapper');
            if (desktopWrapper && !desktopWrapper.classList.contains('hidden')) {
                return document.getElementById('emailCanvas');
            }
            return document.getElementById('emailCanvasMobile');
        }
        
        // Sync canvas content
        function syncCanvasContent() {
            const desktopCanvas = document.getElementById('emailCanvas');
            const mobileCanvas = document.getElementById('emailCanvasMobile');
            if (desktopCanvas && mobileCanvas) {
                const activeCanvas = getActiveCanvas();
                const otherCanvas = activeCanvas === desktopCanvas ? mobileCanvas : desktopCanvas;
                otherCanvas.innerHTML = activeCanvas.innerHTML;
                reattachEventListeners();
            }
        }

        // Add component to canvas
        function addComponent(type) {
            const canvas = getActiveCanvas();
            if (!canvas) {
                console.error('[v0] Canvas element not found');
                return;
            }

            const id = `component-${componentCounter++}`;
            let html = '';

            // Get canvas width to calculate initial sizes
            const canvasWidth = canvas.offsetWidth || 1400;
            const initialWidth = Math.min(400, canvasWidth * 0.3); // 30% do canvas ou máximo 400px
            
            // Posição inicial aleatória para não ficar tudo no mesmo lugar
            const initialX = Math.random() * Math.max(50, canvas.offsetWidth * 0.3);
            const initialY = Math.random() * Math.max(50, canvas.offsetHeight * 0.3);

            switch(type) {
                case 'heading':
                    html = `<h1 id="${id}" class="component-hover draggable cursor-move font-bold text-gray-900" data-type="heading" contenteditable="true" data-x="${initialX}" data-y="${initialY}" style="position: absolute; left: ${initialX}px; top: ${initialY}px; display: inline-block; width: ${initialWidth}px; max-width: 100%; padding: 16px; font-size: clamp(18px, 4vw, 32px); line-height: 1.2; touch-action: none; user-select: none;">Seu Título Aqui</h1>`;
                    break;
                case 'text':
                    html = `<p id="${id}" class="component-hover draggable cursor-move text-gray-700 leading-relaxed" data-type="text" contenteditable="true" data-x="${initialX}" data-y="${initialY}" style="position: absolute; left: ${initialX}px; top: ${initialY}px; display: inline-block; width: ${initialWidth}px; max-width: 100%; padding: 16px; font-size: clamp(14px, 2vw, 18px); line-height: 1.6; touch-action: none; user-select: none;">Escreva seu texto aqui. Você pode editar diretamente clicando.</p>`;
                    break;
                case 'button':
                    html = `<a id="${id}" href="#" class="component-hover draggable cursor-move bg-blue-600 text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 border border-blue-700" data-type="button" contenteditable="true" data-x="${initialX}" data-y="${initialY}" style="position: absolute; left: ${initialX}px; top: ${initialY}px; display: inline-block; width: fit-content; min-width: 120px; max-width: none; font-size: clamp(14px, 2vw, 16px); touch-action: none; user-select: none;">Clique Aqui</a>`;
                    break;
                case 'image':
                    html = `<img id="${id}" src="https://placehold.co/600x300/e2e8f0/64748b?text=Sua+Imagem" alt="Imagem" class="component-hover draggable cursor-move h-auto rounded-md border border-gray-200" data-type="image" data-x="${initialX}" data-y="${initialY}" style="position: absolute; left: ${initialX}px; top: ${initialY}px; display: block; width: ${initialWidth}px; max-width: 100%; touch-action: none; user-select: none;">`;
                    break;
                case 'divider':
                    html = `<hr id="${id}" class="component-hover draggable cursor-move border-t border-gray-300" data-type="divider" data-x="${initialX}" data-y="${initialY}" style="position: absolute; left: ${initialX}px; top: ${initialY}px; width: ${initialWidth}px; max-width: 100%; margin: 16px 0; touch-action: none; user-select: none;">`;
                    break;
                case 'spacer':
                    html = `<div id="${id}" class="component-hover draggable cursor-move bg-gray-50 border border-dashed border-gray-300 flex items-center justify-center text-gray-400 text-xs" data-type="spacer" data-x="${initialX}" data-y="${initialY}" style="position: absolute; left: ${initialX}px; top: ${initialY}px; height: 40px; width: ${Math.min(initialWidth, 150)}px; display: flex; touch-action: none; user-select: none;">Espaçador</div>`;
                    break;
                case 'container':
                    html = `<div id="${id}" class="component-hover draggable cursor-move border-2 border-dashed border-gray-300 bg-gray-50" data-type="container" data-x="${initialX}" data-y="${initialY}" style="position: absolute; left: ${initialX}px; top: ${initialY}px; width: ${initialWidth}px; max-width: 100%; min-height: 100px; padding: 16px; display: inline-block; touch-action: none; user-select: none;">
                        <div class="text-gray-500 text-sm text-center">Container - Arraste componentes aqui</div>
                    </div>`;
                    break;
            }

            // Remove placeholder text if exists
            const placeholder = canvas.querySelector('#canvas-placeholder, #canvas-placeholder-mobile');
            if (placeholder) {
                placeholder.remove();
            }

            canvas.insertAdjacentHTML('beforeend', html);
            
            const newElement = canvas.querySelector(`#${id}`);
            
            if (!newElement) {
                console.error('[SafeNode Relay] New element not found after insertion');
                return;
            }
            
            // Garantir que o elemento está visível
            newElement.style.display = 'block';
            newElement.style.visibility = 'visible';
            newElement.style.opacity = '1';
            
            // Adicionar ao painel de layers
            updateLayersPanel();
            
            // Make draggable - sem restrição lateral, apenas vertical
            // Make draggable - configuração simplificada sem modificadores que podem interferir
            interact(`#${id}`).draggable({
                inertia: false,
                autoScroll: true,
                listeners: {
                    start: function(event) {
                        const target = event.target;
                        // Desabilitar contenteditable durante o drag
                        if (target.hasAttribute('contenteditable')) {
                            target.setAttribute('contenteditable', 'false');
                            target._wasContentEditable = true;
                        }
                        // Garantir position absolute para drag funcionar
                        const currentPos = target.style.position || window.getComputedStyle(target).position;
                        if (currentPos !== 'absolute') {
                            const rect = target.getBoundingClientRect();
                            const canvas = getActiveCanvas();
                            const canvasRect = canvas.getBoundingClientRect();
                            const left = rect.left - canvasRect.left;
                            const top = rect.top - canvasRect.top;
                            
                            target.style.setProperty('position', 'absolute', 'important');
                            target.style.left = left + 'px';
                            target.style.top = top + 'px';
                            target.setAttribute('data-x', left);
                            target.setAttribute('data-y', top);
                        }
                        target.style.zIndex = '1000';
                        target.style.cursor = 'grabbing';
                    },
                    move: dragMoveListener,
                    end: function(event) {
                        const target = event.target;
                        // Reabilitar contenteditable após o drag
                        if (target._wasContentEditable) {
                            target.setAttribute('contenteditable', 'true');
                            target._wasContentEditable = false;
                        }
                        target.classList.remove('component-dragging');
                        target.style.zIndex = '';
                        target.style.cursor = 'move';
                    }
                }
            });

            // Select on click (but allow drag)
            let clickStartTime = 0;
            let clickStartPos = { x: 0, y: 0 };
            
            newElement.addEventListener('mousedown', function(e) {
                clickStartTime = Date.now();
                clickStartPos = { x: e.clientX, y: e.clientY };
            });
            
            newElement.addEventListener('click', function(e) {
                const clickDuration = Date.now() - clickStartTime;
                const clickDistance = Math.sqrt(
                    Math.pow(e.clientX - clickStartPos.x, 2) + 
                    Math.pow(e.clientY - clickStartPos.y, 2)
                );
                
                // Só selecionar se foi um clique rápido e sem movimento significativo
                if (clickDuration < 300 && clickDistance < 5) {
                e.stopPropagation();
                selectElement(this);
                }
            });
            
            // Auto-adjust font size based on element size
            setupAutoFontSize(newElement);
        }
        
        // Auto-adjust font size based on element dimensions
        function setupAutoFontSize(element) {
            const type = element.dataset.type;
            
            // Only apply to text elements
            if (type !== 'heading' && type !== 'text' && type !== 'button') {
                return;
            }
            
            // Function to adjust font size
            const adjustFontSize = () => {
                const width = element.offsetWidth;
                const height = element.offsetHeight;
                
                if (!width || !height) return;
                
                // Calculate font size based on element dimensions
                let fontSize;
                
                if (type === 'heading') {
                    // Heading: base on width, but consider height too
                    fontSize = Math.min(width * 0.08, height * 0.4);
                    fontSize = Math.max(18, Math.min(fontSize, 48)); // Min 18px, Max 48px
                } else if (type === 'text') {
                    // Text: base on width
                    fontSize = Math.min(width * 0.04, height * 0.25);
                    fontSize = Math.max(12, Math.min(fontSize, 20)); // Min 12px, Max 20px
                } else if (type === 'button') {
                    // Button: base on height
                    fontSize = height * 0.35;
                    fontSize = Math.max(12, Math.min(fontSize, 18)); // Min 12px, Max 18px
                }
                
                // Apply font size
                element.style.fontSize = fontSize + 'px';
            };
            
            // Initial adjustment
            setTimeout(adjustFontSize, 100);
            
            // Use ResizeObserver to watch for size changes
            if (window.ResizeObserver) {
                const resizeObserver = new ResizeObserver(entries => {
                    for (let entry of entries) {
                        adjustFontSize();
                    }
                });
                
                resizeObserver.observe(element);
                
                // Store observer for cleanup if needed
                element._resizeObserver = resizeObserver;
            } else {
                // Fallback: listen to window resize
                window.addEventListener('resize', adjustFontSize);
            }
        }

        function dragMoveListener(event) {
            const target = event.target;
            
            // Garantir que está usando position absolute
            if (target.style.position !== 'absolute') {
                target.style.position = 'absolute';
            }
            
            // Obter posição atual do elemento
            let currentX = parseFloat(target.getAttribute('data-x'));
            let currentY = parseFloat(target.getAttribute('data-y'));
            
            // Se não tiver data-x/data-y, pegar do estilo atual
            if (isNaN(currentX)) {
                currentX = parseFloat(target.style.left) || 0;
            }
            if (isNaN(currentY)) {
                currentY = parseFloat(target.style.top) || 0;
            }
            
            // Calcular nova posição baseada no movimento
            const x = currentX + event.dx;
            const y = currentY + event.dy;

            // Aplicar nova posição
            target.style.left = x + 'px';
            target.style.top = y + 'px';
            target.setAttribute('data-x', x);
            target.setAttribute('data-y', y);
            
            // Adicionar classe durante o drag
            target.classList.add('component-dragging');
            
            // Atualizar toolbar em tempo real durante o drag
            if (selectedElement === target) {
                updateFloatingToolbar(target);
            }
        }
        
        // Resize move listener
        function resizeMoveListener(event) {
            const target = event.target;
            let x = (parseFloat(target.getAttribute('data-x')) || 0);
            let y = (parseFloat(target.getAttribute('data-y')) || 0);

            // Update width and height
            target.style.width = event.rect.width + 'px';
            target.style.height = event.rect.height + 'px';

            // Translate when resizing from top or left edges
            x += event.deltaRect.left;
            y += event.deltaRect.top;

            target.style.left = x + 'px';
            target.style.top = y + 'px';
            target.setAttribute('data-x', x);
            target.setAttribute('data-y', y);
            
            // Auto-adjust font size if it's a text element
            const type = target.dataset.type;
            if (type === 'heading' || type === 'text' || type === 'button') {
                setupAutoFontSize(target);
            }
        }

        // Update Layers Panel
        function updateLayersPanel() {
            const canvas = getActiveCanvas();
            if (!canvas) return;
            
            const layersList = document.getElementById('layersList');
            const layersEmpty = document.getElementById('layers-empty');
            const layersCount = document.getElementById('layers-count');
            
            if (!layersList) return;
            
            // Get all components
            const components = canvas.querySelectorAll('[id^="component-"]');
            
            // Update count
            if (layersCount) {
                layersCount.textContent = components.length;
            }
            
            // Clear list
            layersList.innerHTML = '';
            
            if (components.length === 0) {
                if (layersEmpty) {
                    layersList.appendChild(layersEmpty);
                }
                return;
            }
            
            // Add each component to layers
            components.forEach((component, index) => {
                const type = component.dataset.type || 'unknown';
                const id = component.id;
                
                // Get component name/label
                let label = getComponentLabel(component, type);
                
                // Get icon for type
                const icon = getComponentIcon(type);
                
                // Create layer item
                const layerItem = document.createElement('div');
                layerItem.className = 'layer-item';
                layerItem.dataset.componentId = id;
                layerItem.style.cssText = `
                    padding: 6px 8px;
                    border-radius: 4px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.15s ease;
                    background: transparent;
                    border: 1px solid transparent;
                `;
                
                // Hover effects
                layerItem.onmouseover = function() {
                    this.style.background = 'var(--bg-hover)';
                    this.style.borderColor = 'var(--border-subtle)';
                };
                layerItem.onmouseout = function() {
                    if (!this.classList.contains('layer-selected')) {
                        this.style.background = 'transparent';
                        this.style.borderColor = 'transparent';
                    }
                };
                
                // Click to select
                layerItem.onclick = function(e) {
                    e.stopPropagation();
                    const element = document.getElementById(id);
                    if (element) {
                        selectElement(element);
                        updateLayersSelection();
                    }
                };
                
                // Icon
                layerItem.innerHTML = `
                    <div style="width: 14px; height: 14px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                        ${icon}
                    </div>
                    <span class="text-[10px] font-medium flex-1 truncate" style="color: var(--text-secondary);">${label}</span>
                    <span class="text-[9px] opacity-50" style="color: var(--text-muted);">#${index + 1}</span>
                `;
                
                layersList.appendChild(layerItem);
            });
        }
        
        // Get component label
        function getComponentLabel(element, type) {
            switch(type) {
                case 'heading':
                    const h1 = element.tagName === 'H1' ? element : element.querySelector('h1');
                    return h1 ? (h1.textContent || 'Título').substring(0, 20) : 'Título';
                case 'text':
                    const p = element.tagName === 'P' ? element : element.querySelector('p');
                    return p ? (p.textContent || 'Texto').substring(0, 20) : 'Texto';
                case 'button':
                    const a = element.tagName === 'A' ? element : element.querySelector('a');
                    return a ? (a.textContent || 'Botão').substring(0, 20) : 'Botão';
                case 'image':
                    return 'Imagem';
                case 'divider':
                    return 'Divisória';
                case 'spacer':
                    return 'Espaçador';
                case 'container':
                    return 'Container';
                default:
                    return type.charAt(0).toUpperCase() + type.slice(1);
            }
        }
        
        // Get component icon SVG
        function getComponentIcon(type) {
            const icons = {
                'heading': '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/></svg>',
                'text': '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>',
                'button': '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6"/></svg>',
                'image': '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>',
                'divider': '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>',
                'spacer': '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/></svg>',
                'container': '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>'
            };
            return icons[type] || '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/></svg>';
        }
        
        // Update layers selection highlight
        function updateLayersSelection() {
            document.querySelectorAll('.layer-item').forEach(item => {
                item.classList.remove('layer-selected');
                const componentId = item.dataset.componentId;
                if (selectedElement && selectedElement.id === componentId) {
                    item.classList.add('layer-selected');
                    item.style.background = 'rgba(59, 130, 246, 0.15)';
                    item.style.borderColor = 'rgba(59, 130, 246, 0.3)';
                } else {
                    item.style.background = 'transparent';
                    item.style.borderColor = 'transparent';
                }
            });
        }

        // Select element
        function selectElement(element) {
            // Remove previous selection
            document.querySelectorAll('.component-selected').forEach(el => {
                el.classList.remove('component-selected');
            });

            element.classList.add('component-selected');
            selectedElement = element;
            showProperties(element);
            updateFloatingToolbar(element);
            updateLayersSelection();
        }
        
        // Update floating toolbar position - abaixo do elemento e seguindo em tempo real
        let toolbarUpdateInterval = null;
        
        function updateFloatingToolbar(element) {
            const toolbar = document.getElementById('floatingToolbar');
            if (!toolbar || !element) {
                if (toolbar) toolbar.classList.remove('visible');
                if (toolbarUpdateInterval) {
                    clearInterval(toolbarUpdateInterval);
                    toolbarUpdateInterval = null;
                }
                return;
            }
            
            const updatePosition = () => {
                if (!element || !toolbar) return;
                
                const rect = element.getBoundingClientRect();
                const canvasRect = getActiveCanvas().getBoundingClientRect();
                
                // Position toolbar below element, centered
                const toolbarWidth = 280; // Largura reduzida
                const toolbarHeight = 32; // Altura reduzida
                const offset = 8;
                
                let left = rect.left + (rect.width / 2) - (toolbarWidth / 2);
                let top = rect.bottom + offset;
                
                // Keep toolbar within viewport
                if (left < 10) left = 10;
                if (left + toolbarWidth > window.innerWidth - 10) {
                    left = window.innerWidth - toolbarWidth - 10;
                }
                
                // Se não houver espaço abaixo, mostrar acima
                if (top + toolbarHeight > window.innerHeight - 10) {
                    top = rect.top - toolbarHeight - offset;
                }
                
                toolbar.style.left = left + 'px';
                toolbar.style.top = top + 'px';
            };
            
            // Atualizar posição imediatamente
            updatePosition();
            toolbar.classList.add('visible');
            
            // Limpar intervalo anterior se existir
            if (toolbarUpdateInterval) {
                clearInterval(toolbarUpdateInterval);
            }
            
            // Atualizar em tempo real (a cada 50ms)
            toolbarUpdateInterval = setInterval(updatePosition, 50);
        }
        
        // Hide floating toolbar
        function hideFloatingToolbar() {
            const toolbar = document.getElementById('floatingToolbar');
            if (toolbar) {
                toolbar.classList.remove('visible');
            }
            if (toolbarUpdateInterval) {
                clearInterval(toolbarUpdateInterval);
                toolbarUpdateInterval = null;
            }
        }
        
        // Set tool mode
        function setTool(tool) {
            document.querySelectorAll('.floating-toolbar-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById('tool-' + tool).classList.add('active');
        }
        
        // Align element
        function alignElement(alignment) {
            if (!selectedElement) return;
            
            const canvas = getActiveCanvas();
            const canvasWidth = canvas.offsetWidth;
            const elementWidth = selectedElement.offsetWidth;
            
            if (alignment === 'left') {
                selectedElement.style.left = '0px';
            } else if (alignment === 'center') {
                selectedElement.style.left = ((canvasWidth - elementWidth) / 2) + 'px';
            } else if (alignment === 'right') {
                selectedElement.style.left = (canvasWidth - elementWidth) + 'px';
            }
            
            saveHistory();
            updateFloatingToolbar(selectedElement);
        }
        
        // Change z-index
        function changeZIndex(direction) {
            if (!selectedElement) return;
            
            const canvas = getActiveCanvas();
            const allElements = Array.from(canvas.querySelectorAll('[id^="component-"]'));
            const currentIndex = allElements.indexOf(selectedElement);
            
            if (direction === 'front' && currentIndex < allElements.length - 1) {
                canvas.appendChild(selectedElement);
            } else if (direction === 'back' && currentIndex > 0) {
                canvas.insertBefore(selectedElement, allElements[0]);
            }
            
            saveHistory();
            updateFloatingToolbar(selectedElement);
        }
        
        // Update toolbar on scroll/resize
        window.addEventListener('scroll', function() {
            if (selectedElement) {
                updateFloatingToolbar(selectedElement);
            }
        });
        
        window.addEventListener('resize', function() {
            if (selectedElement) {
                updateFloatingToolbar(selectedElement);
            }
        });

        // Show properties panel - Estilo Figma Completo
        function showProperties(element) {
            const type = element.dataset.type;
            const panel = document.getElementById('propertiesPanel');
            
            // Get computed styles
            const computed = window.getComputedStyle(element);
            const bgColor = computed.backgroundColor !== 'rgba(0, 0, 0, 0)' ? rgbToHex(computed.backgroundColor) : '#ffffff';
            const borderWidth = parseInt(computed.borderWidth) || 0;
            const borderColor = computed.borderColor !== 'rgba(0, 0, 0, 0)' ? rgbToHex(computed.borderColor) : '#000000';
            const borderRadius = parseInt(computed.borderRadius) || 0;
            const opacity = Math.round(parseFloat(computed.opacity) * 100);
            const width = element.offsetWidth || 0;
            const height = element.offsetHeight || 0;
            const padding = parseInt(computed.paddingTop) || 0;
            
            let html = `<div class="py-4 space-y-4" style="max-width: 100%; box-sizing: border-box;">`;
            
            // Header do elemento - Melhorado
            html += `<div class="pb-3 mb-3" style="border-bottom: 1px solid var(--border-subtle);">
                     <div class="flex items-center justify-between mb-2">
                         <label class="property-label">Tipo</label>
                         <span class="badge" style="font-size: 9px; padding: 2px 8px; background: rgba(255,255,255,0.15);">${type}</span>
                     </div>
                     <div class="text-sm font-semibold capitalize" style="color: var(--text-primary);">${type.replace('_', ' ')}</div>
            </div>`;

            // Position Section - Melhorado com responsividade
            // Ler position do estilo inline primeiro, depois do computed
            const inlinePosition = element.style.position;
            const currentPosition = inlinePosition || computed.position || 'absolute';
            
            html += `<div class="property-section">
                <div class="section-header">
                    <label class="property-label">Position</label>
                </div>
                <select id="position-select-${element.id}" onchange="if(selectedElement && selectedElement.id === '${element.id}') { const val = this.value; selectedElement.style.setProperty('position', val, 'important'); selectedElement.style.position = val; saveHistory(); }" 
                        class="property-select w-full rounded px-2 py-1.5 text-xs" style="max-width: 100%;">
                    <option value="relative" ${currentPosition === 'relative' ? 'selected' : ''}>Relative</option>
                    <option value="absolute" ${currentPosition === 'absolute' ? 'selected' : ''}>Absolute</option>
                    <option value="static" ${currentPosition === 'static' ? 'selected' : ''}>Static</option>
                </select>
            </div>`;

            // Dimensions Section - Melhorado com mais espaçamento e responsividade
            html += `<div class="property-section">
                <label class="property-label mb-2">Dimensões</label>
                <div class="grid grid-cols-2 gap-3" style="max-width: 100%;">
                    <div class="space-y-1" style="min-width: 0;">
                        <label class="property-label-small block">Largura</label>
                        <div class="flex gap-1.5 items-center" style="max-width: 100%;">
                            <input type="number" value="${width}" onchange="updateStyle(selectedElement, 'width', this.value + 'px'); saveHistory();" 
                                   class="property-input dimension-input flex-1 rounded" style="max-width: calc(100% - 30px); min-width: 0;">
                            <span class="value-display text-[10px] min-w-[18px] flex-shrink-0">px</span>
                        </div>
                    </div>
                    <div class="space-y-1" style="min-width: 0;">
                        <label class="property-label-small block">Altura</label>
                        <div class="flex gap-1.5 items-center" style="max-width: 100%;">
                            <input type="number" value="${height}" onchange="updateStyle(selectedElement, 'height', this.value + 'px'); saveHistory();" 
                                   class="property-input dimension-input flex-1 rounded" style="max-width: calc(100% - 30px); min-width: 0;">
                            <span class="value-display text-[10px] min-w-[18px] flex-shrink-0">px</span>
                        </div>
                    </div>
                </div>
                </div>`;
                
            // Fill Section - Melhorado com Eyedropper e responsividade
            html += `<div class="property-section">
                <div class="section-header">
                    <label class="property-label">Fill</label>
                    <button class="section-header-btn" data-tooltip="Adicionar fill">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                    </button>
                </div>
                <div class="flex gap-2 items-center" style="max-width: 100%; flex-wrap: wrap;">
                    <div class="color-picker-wrapper" style="flex-shrink: 0;">
                        <input type="color" value="${bgColor}" onchange="const hex = this.value; updateFill(hex); document.getElementById('fill-color-input').value = hex; saveHistory();" 
                               id="fill-color-picker">
                    </div>
                    <input type="text" id="fill-color-input" value="${bgColor}" onchange="updateFill(this.value); document.getElementById('fill-color-picker').value = this.value; saveHistory();" 
                           class="property-input flex-1 rounded px-2 py-1.5 text-xs font-mono" style="min-width: 80px; max-width: calc(100% - 60px);">
                    <div class="flex items-center gap-1" style="flex-shrink: 0;">
                        <input type="number" value="100" min="0" max="100" onchange="updateFillOpacity(this.value); saveHistory();" 
                               class="property-input w-12 rounded px-2 py-1.5 text-xs text-center">
                        <span class="value-display text-[10px]">%</span>
                    </div>
                </div>
            </div>`;

            // Border Section - Melhorado com responsividade
            html += `<div class="property-section">
                <label class="property-label mb-2">Border</label>
                <div class="space-y-2">
                    <div class="flex gap-2 items-center" style="max-width: 100%; flex-wrap: wrap;">
                        <div class="flex items-center gap-1.5" style="flex-shrink: 0;">
                            <input type="number" value="${borderWidth}" min="0" onchange="updateBorderWidth(this.value); saveHistory();" 
                                   class="property-input w-14 rounded px-2 py-1.5 text-xs text-center">
                            <span class="value-display text-[10px]">px</span>
                        </div>
                        <div class="color-picker-wrapper" style="flex-shrink: 0;">
                            <input type="color" value="${borderColor}" onchange="const hex = this.value; updateBorderColor(hex); document.getElementById('border-color-input').value = hex; saveHistory();" 
                                   id="border-color-picker">
                        </div>
                        <input type="text" id="border-color-input" value="${borderColor}" onchange="updateBorderColor(this.value); document.getElementById('border-color-picker').value = this.value; saveHistory();" 
                               class="property-input flex-1 rounded px-2 py-1.5 text-xs font-mono" style="min-width: 80px; max-width: calc(100% - 60px);">
                    </div>
                    <div class="flex gap-2 items-center">
                        <label class="property-label-small w-14 flex-shrink-0">Radius</label>
                        <div class="flex items-center gap-1.5 flex-1" style="min-width: 0;">
                            <input type="number" value="${borderRadius}" min="0" onchange="updateBorderRadius(this.value); saveHistory();" 
                                   class="property-input flex-1 rounded px-2 py-1.5 text-xs" style="max-width: 100%;">
                            <span class="value-display text-[10px] flex-shrink-0">px</span>
                        </div>
                    </div>
                </div>
            </div>`;

            // Appearance Section - Melhorado com responsividade
            html += `<div class="property-section">
                <div class="section-header">
                    <label class="property-label">Appearance</label>
                    <button class="section-header-btn" data-tooltip="Visibilidade">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>
                </div>
                <div>
                    <label class="property-label-small">Opacity</label>
                    <div class="flex gap-1.5 items-center" style="max-width: 100%;">
                        <input type="number" value="${opacity}" min="0" max="100" onchange="updateOpacity(this.value); saveHistory();" 
                               class="property-input flex-1 rounded px-2 py-1.5 text-xs" style="max-width: calc(100% - 30px);">
                        <span class="value-display text-[10px] flex-shrink-0">%</span>
                    </div>
                </div>
            </div>`;

            // Padding Section - Melhorado com responsividade
            html += `<div class="property-section">
                <label class="property-label mb-2">Padding</label>
                <div class="grid grid-cols-4 gap-1.5" style="max-width: 100%;">
                    <div style="min-width: 0;">
                        <label class="property-label-small text-center block mb-1">T</label>
                        <input type="number" value="${padding}" onchange="updatePadding('top', this.value); saveHistory();" 
                               class="property-input grid-input w-full rounded px-1.5 py-1.5 text-xs"
                               placeholder="0" data-tooltip="Top" style="max-width: 100%;">
                    </div>
                    <div style="min-width: 0;">
                        <label class="property-label-small text-center block mb-1">R</label>
                        <input type="number" value="${padding}" onchange="updatePadding('right', this.value); saveHistory();" 
                               class="property-input grid-input w-full rounded px-1.5 py-1.5 text-xs"
                               placeholder="0" data-tooltip="Right" style="max-width: 100%;">
                    </div>
                    <div style="min-width: 0;">
                        <label class="property-label-small text-center block mb-1">B</label>
                        <input type="number" value="${padding}" onchange="updatePadding('bottom', this.value); saveHistory();" 
                               class="property-input grid-input w-full rounded px-1.5 py-1.5 text-xs"
                               placeholder="0" data-tooltip="Bottom" style="max-width: 100%;">
                    </div>
                    <div style="min-width: 0;">
                        <label class="property-label-small text-center block mb-1">L</label>
                        <input type="number" value="${padding}" onchange="updatePadding('left', this.value); saveHistory();" 
                               class="property-input grid-input w-full rounded px-1.5 py-1.5 text-xs"
                               placeholder="0" data-tooltip="Left" style="max-width: 100%;">
                    </div>
                </div>
            </div>`;

            // Typography Section (for text elements)
            if (type === 'heading' || type === 'text') {
                const textEl = (element.tagName === 'H1' || element.tagName === 'P') 
                    ? element 
                    : element.querySelector(type === 'heading' ? 'h1' : 'p');
                const textComputed = window.getComputedStyle(textEl || element);
                const currentColor = textComputed.color !== 'rgba(0, 0, 0, 0)' ? rgbToHex(textComputed.color) : '#000000';
                const currentSize = parseInt(textComputed.fontSize) || 16;
                const fontWeight = textComputed.fontWeight || '400';
                const lineHeight = parseFloat(textComputed.lineHeight) || 1.5;
                const letterSpacing = parseFloat(textComputed.letterSpacing) || 0;
                
                html += `<div class="property-section">
                    <div class="section-header">
                        <label class="property-label">Typography</label>
                    </div>
                    <div class="space-y-2">
                        <div class="flex gap-2 items-center" style="max-width: 100%; flex-wrap: wrap;">
                            <div class="color-picker-wrapper" style="flex-shrink: 0;">
                                <input type="color" value="${currentColor}" onchange="const hex = this.value; updateTextColor(hex); document.getElementById('text-color-input').value = hex; saveHistory();" 
                                       id="text-color-picker">
                            </div>
                            <input type="text" id="text-color-input" value="${currentColor}" onchange="updateTextColor(this.value); document.getElementById('text-color-picker').value = this.value; saveHistory();" 
                                   class="property-input flex-1 rounded px-2 py-1.5 text-xs font-mono" style="min-width: 80px; max-width: calc(100% - 60px);">
                        </div>
                        <div class="grid grid-cols-2 gap-2" style="max-width: 100%;">
                            <div style="min-width: 0;">
                                <label class="property-label-small">Tamanho</label>
                                <div class="flex gap-1.5 items-center">
                                    <input type="number" value="${currentSize}" onchange="updateTextSize(this.value + 'px'); saveHistory();" 
                                           class="property-input flex-1 rounded px-2 py-1.5 text-xs" style="max-width: calc(100% - 25px);">
                                    <span class="value-display text-[10px] flex-shrink-0">px</span>
                                </div>
                            </div>
                            <div style="min-width: 0;">
                                <label class="property-label-small">Peso</label>
                                <select onchange="updateFontWeight(this.value); saveHistory();" 
                                        class="property-select w-full rounded px-2 py-1.5 text-xs" style="max-width: 100%;">
                                    <option value="300" ${fontWeight === '300' ? 'selected' : ''}>Light</option>
                                    <option value="400" ${fontWeight === '400' ? 'selected' : ''}>Regular</option>
                                    <option value="500" ${fontWeight === '500' ? 'selected' : ''}>Medium</option>
                                    <option value="600" ${fontWeight === '600' ? 'selected' : ''}>Semibold</option>
                                    <option value="700" ${fontWeight === '700' ? 'selected' : ''}>Bold</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2" style="max-width: 100%;">
                            <div style="min-width: 0;">
                                <label class="property-label-small">Line Height</label>
                                <input type="number" value="${lineHeight}" step="0.1" onchange="updateLineHeight(this.value); saveHistory();" 
                                       class="property-input w-full rounded px-2 py-1.5 text-xs" style="max-width: 100%;">
                            </div>
                            <div style="min-width: 0;">
                                <label class="property-label-small">Letter Spacing</label>
                                <div class="flex gap-1.5 items-center">
                                    <input type="number" value="${letterSpacing}" step="0.1" onchange="updateLetterSpacing(this.value + 'px'); saveHistory();" 
                                           class="property-input flex-1 rounded px-2 py-1.5 text-xs" style="max-width: calc(100% - 25px);">
                                    <span class="value-display text-[10px] flex-shrink-0">px</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            }

            if (type === 'button') {
                const btnEl = element.tagName === 'A' ? element : element.querySelector('a');
                const btnComputed = window.getComputedStyle(btnEl || element);
                const btnBg = btnComputed.backgroundColor && btnComputed.backgroundColor !== 'rgba(0, 0, 0, 0)' 
                    ? rgbToHex(btnComputed.backgroundColor) 
                    : '#2563eb';
                const btnUrl = btnEl ? btnEl.href : '#';
                
                html += `<div class="property-section">
                    <label class="property-label mb-2">Cor de Fundo</label>
                    <div class="flex gap-2 items-center" style="max-width: 100%; flex-wrap: wrap;">
                        <div class="color-picker-wrapper" style="flex-shrink: 0;">
                            <input type="color" value="${btnBg}" onchange="const hex = this.value; updateButtonBg(hex); document.getElementById('button-bg-input').value = hex; saveHistory();" 
                                   id="button-bg-picker">
                        </div>
                        <input type="text" id="button-bg-input" value="${btnBg}" onchange="updateButtonBg(this.value); document.getElementById('button-bg-picker').value = this.value; saveHistory();" 
                               class="property-input flex-1 rounded px-2 py-1.5 text-xs font-mono" style="min-width: 80px; max-width: calc(100% - 60px);">
                    </div>
                </div>`;
                
                html += `<div class="property-section">
                    <label class="property-label mb-2">URL do Link</label>
                    <input type="url" value="${btnUrl}" onchange="updateButtonUrl(this.value); saveHistory();" 
                           class="property-input w-full rounded px-2 py-1.5 text-xs font-mono"
                           placeholder="https://..." style="max-width: 100%;">
                </div>`;
            }

            if (type === 'image') {
                const imgEl = element.tagName === 'IMG' ? element : element.querySelector('img');
                html += `<div class="property-section">
                    <label class="property-label mb-2">URL da Imagem</label>
                    <input type="url" value="${imgEl ? imgEl.src : ''}" onchange="updateImageSrc(this.value); saveHistory();" 
                           class="property-input w-full rounded px-2 py-1.5 text-xs font-mono"
                           placeholder="https://..." style="max-width: 100%;">
                </div>`;
            }

            if (type === 'spacer') {
                const spacerHeight = parseInt(computed.height) || 40;
                html += `<div class="property-section">
                    <label class="property-label mb-2">Altura</label>
                    <div class="flex gap-1.5 items-center" style="max-width: 100%;">
                        <input type="number" value="${spacerHeight}" onchange="updateStyle(selectedElement, 'height', this.value + 'px'); saveHistory();" 
                               class="property-input flex-1 rounded px-2 py-1.5 text-xs" style="max-width: calc(100% - 25px);">
                        <span class="value-display text-[10px] flex-shrink-0">px</span>
                    </div>
                </div>`;
            }

            html += `</div>`;
            panel.innerHTML = html;
        }

        // Helper: RGB to Hex
        function rgbToHex(rgb) {
            if (!rgb || rgb === 'transparent' || rgb === 'rgba(0, 0, 0, 0)') return '#ffffff';
            if (rgb.startsWith('#')) return rgb;
            
            // Handle rgb() format
            let match = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
            if (match) {
                return '#' + [1, 2, 3].map(i => ('0' + parseInt(match[i]).toString(16)).slice(-2)).join('');
            }
            
            // Handle rgba() format
            match = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
            if (match) {
                return '#' + [1, 2, 3].map(i => ('0' + parseInt(match[i]).toString(16)).slice(-2)).join('');
            }
            
            return '#ffffff';
        }

        // Update functions
        function updateStyle(element, property, value) {
            if (!element) return;
            
            // Atualizar o estilo diretamente - usar setProperty para garantir que aplica
            if (property === 'position') {
                element.style.position = value;
                element.style.setProperty('position', value, 'important');
                
                // Se mudou para absolute, garantir que tem left/top
                if (value === 'absolute') {
                    if (!element.style.left && !element.getAttribute('data-x')) {
                        const rect = element.getBoundingClientRect();
                        const canvas = getActiveCanvas();
                        const canvasRect = canvas.getBoundingClientRect();
                        const left = rect.left - canvasRect.left;
                        const top = rect.top - canvasRect.top;
                        element.style.left = left + 'px';
                        element.style.top = top + 'px';
                        element.setAttribute('data-x', left);
                        element.setAttribute('data-y', top);
                    }
                }
                // Atualizar o select no painel
                const positionSelect = document.getElementById('position-select-' + element.id);
                if (positionSelect) {
                    positionSelect.value = value;
                }
            } else {
                element.style[property] = value;
            }
            
            // If width or height changed, adjust font size for text elements
            if ((property === 'width' || property === 'height') && 
                (element.dataset.type === 'heading' || element.dataset.type === 'text' || element.dataset.type === 'button')) {
                setTimeout(() => {
                    const type = element.dataset.type;
                    const width = element.offsetWidth;
                    const height = element.offsetHeight;
                    
                    if (!width || !height) return;
                    
                    let fontSize;
                    if (type === 'heading') {
                        fontSize = Math.min(width * 0.08, height * 0.4);
                        fontSize = Math.max(18, Math.min(fontSize, 48));
                    } else if (type === 'text') {
                        fontSize = Math.min(width * 0.04, height * 0.25);
                        fontSize = Math.max(12, Math.min(fontSize, 20));
                    } else if (type === 'button') {
                        fontSize = height * 0.35;
                        fontSize = Math.max(12, Math.min(fontSize, 18));
                    }
                    
                    element.style.fontSize = fontSize + 'px';
                }, 50);
            }
            
            syncCanvasContent();
        }

        function updateFill(color) {
            if (!selectedElement) return;
            selectedElement.style.backgroundColor = color;
            syncCanvasContent();
        }

        function updateFillOpacity(value) {
            if (!selectedElement) return;
            const currentColor = window.getComputedStyle(selectedElement).backgroundColor;
            const opacity = parseFloat(value) / 100;
            
            // Convert RGB to RGBA
            if (currentColor.startsWith('rgb(')) {
                const rgb = currentColor.match(/\d+/g);
                if (rgb && rgb.length >= 3) {
                    selectedElement.style.backgroundColor = `rgba(${rgb[0]}, ${rgb[1]}, ${rgb[2]}, ${opacity})`;
                }
            } else if (currentColor.startsWith('rgba(')) {
                const rgba = currentColor.match(/[\d.]+/g);
                if (rgba && rgba.length >= 3) {
                    selectedElement.style.backgroundColor = `rgba(${rgba[0]}, ${rgba[1]}, ${rgba[2]}, ${opacity})`;
                }
            }
            syncCanvasContent();
        }

        function updateBorderWidth(value) {
            if (!selectedElement) return;
            selectedElement.style.borderWidth = value + 'px';
            selectedElement.style.borderStyle = value > 0 ? 'solid' : 'none';
            syncCanvasContent();
        }

        function updateBorderColor(color) {
            if (!selectedElement) return;
            selectedElement.style.borderColor = color;
            syncCanvasContent();
        }

        function updateBorderRadius(value) {
            if (!selectedElement) return;
            selectedElement.style.borderRadius = value + 'px';
            syncCanvasContent();
        }

        function updateOpacity(value) {
            if (!selectedElement) return;
            selectedElement.style.opacity = (value / 100);
            syncCanvasContent();
        }

        function updatePadding(side, value) {
            if (!selectedElement) return;
            selectedElement.style['padding' + side.charAt(0).toUpperCase() + side.slice(1)] = value + 'px';
            syncCanvasContent();
        }

        function updateTextColor(color) {
            if (!selectedElement) return;
            // Elemento pode ser diretamente h1, p, a ou estar dentro de uma div
            const textEl = selectedElement.tagName === 'H1' || selectedElement.tagName === 'P' || selectedElement.tagName === 'A' 
                ? selectedElement 
                : selectedElement.querySelector('h1, p, a');
            if (textEl) {
                textEl.style.color = color;
                syncCanvasContent();
            }
        }

        function updateTextSize(size) {
            if (!selectedElement) return;
            const textEl = selectedElement.tagName === 'H1' || selectedElement.tagName === 'P' || selectedElement.tagName === 'A' 
                ? selectedElement 
                : selectedElement.querySelector('h1, p, a');
            if (textEl) {
                textEl.style.fontSize = size;
                syncCanvasContent();
            }
        }

        function updateFontWeight(weight) {
            if (!selectedElement) return;
            const textEl = selectedElement.tagName === 'H1' || selectedElement.tagName === 'P' || selectedElement.tagName === 'A' 
                ? selectedElement 
                : selectedElement.querySelector('h1, p, a');
            if (textEl) {
                textEl.style.fontWeight = weight;
                syncCanvasContent();
            }
        }

        function updateLineHeight(height) {
            if (!selectedElement) return;
            const textEl = selectedElement.tagName === 'H1' || selectedElement.tagName === 'P' || selectedElement.tagName === 'A' 
                ? selectedElement 
                : selectedElement.querySelector('h1, p, a');
            if (textEl) {
                textEl.style.lineHeight = height;
                syncCanvasContent();
            }
        }

        function updateLetterSpacing(spacing) {
            if (!selectedElement) return;
            const textEl = selectedElement.tagName === 'H1' || selectedElement.tagName === 'P' || selectedElement.tagName === 'A' 
                ? selectedElement 
                : selectedElement.querySelector('h1, p, a');
            if (textEl) {
                textEl.style.letterSpacing = spacing;
                syncCanvasContent();
            }
        }

        function updateButtonBg(color) {
            if (!selectedElement) return;
            const btnEl = selectedElement.tagName === 'A' ? selectedElement : selectedElement.querySelector('a');
            if (btnEl) {
                btnEl.style.backgroundColor = color;
                syncCanvasContent();
            }
        }

        function updateButtonUrl(url) {
            if (!selectedElement) return;
            const btnEl = selectedElement.tagName === 'A' ? selectedElement : selectedElement.querySelector('a');
            if (btnEl) {
                btnEl.href = url;
                syncCanvasContent();
            }
        }

        function updateImageSrc(src) {
            if (!selectedElement) return;
            const imgEl = selectedElement.tagName === 'IMG' ? selectedElement : selectedElement.querySelector('img');
            if (imgEl) {
                imgEl.src = src;
                syncCanvasContent();
            }
        }

        // Delete selected
        function deleteSelected() {
            if (selectedElement) {
                selectedElement.remove();
                selectedElement = null;
                hideFloatingToolbar();
                updateLayersPanel();
                document.getElementById('propertiesPanel').innerHTML = 
                    '<div class="p-6 text-center"><svg class="w-10 h-10 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076-.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><div class="text-xs font-medium mb-1" style="color: var(--text-muted);">Nenhum elemento selecionado</div><div class="text-[10px]" style="color: var(--text-muted); opacity: 0.6;">Clique em um componente para editar</div></div>';
                saveHistory();
            }
        }

        // Clear canvas
        function clearCanvas() {
            if (confirm('Tem certeza que deseja limpar tudo?')) {
                const desktopCanvas = document.getElementById('emailCanvas');
                const mobileCanvas = document.getElementById('emailCanvasMobile');
                const placeholderHtml = '<div id="canvas-placeholder" class="p-12 text-gray-400 text-center" style="pointer-events: none;"><svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg><div class="text-sm font-medium mb-1" style="color: var(--text-muted);">Arraste componentes aqui</div><div class="text-xs" style="color: var(--text-muted); opacity: 0.6;">ou use um template pronto</div></div>';
                const placeholderMobileHtml = '<div id="canvas-placeholder-mobile" class="p-12 text-gray-400 text-center" style="pointer-events: none;"><svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg><div class="text-xs font-medium mb-1" style="color: var(--text-muted);">Arraste componentes aqui</div><div class="text-[10px]" style="color: var(--text-muted); opacity: 0.6;">ou use um template pronto</div></div>';
                
                if (desktopCanvas) desktopCanvas.innerHTML = placeholderHtml;
                if (mobileCanvas) mobileCanvas.innerHTML = placeholderMobileHtml;
                
                selectedElement = null;
                document.getElementById('propertiesPanel').innerHTML = 
                    '<div class="p-6 text-center"><svg class="w-10 h-10 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076-.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><div class="text-xs font-medium mb-1" style="color: var(--text-muted);">Nenhum elemento selecionado</div><div class="text-[10px]" style="color: var(--text-muted); opacity: 0.6;">Clique em um componente para editar</div></div>';
                updateLayersPanel();
                saveHistory();
            }
        }

        // Preview toggle (deprecated, kept for compatibility)
        function togglePreview(mode) {
            setCanvasSize(mode);
        }

        // Export HTML
        function exportHTML() {
            const activeCanvas = getActiveCanvas();
            const clonedCanvas = activeCanvas.cloneNode(true);
            
            // Remove editor classes and attributes
            clonedCanvas.querySelectorAll('.component-hover, .component-selected').forEach(el => {
                el.classList.remove('component-hover', 'component-selected', 'cursor-pointer', 'border', 'border-transparent', 'hover:border-gray-200', 'component-dragging');
                el.removeAttribute('id');
                el.removeAttribute('data-type');
                el.removeAttribute('data-x');
                el.removeAttribute('data-y');
                el.style.transform = '';
                el.style.position = '';
                el.style.left = '';
                el.style.top = '';
                el.style.zIndex = '';
            });

            // Inline Tailwind to regular CSS for email compatibility
            const htmlContent = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        table { border-collapse: collapse; }
    </style>
</head>
<body>
    ${clonedCanvas.innerHTML}
</body>
</html>`;

            // Download
            const blob = new Blob([htmlContent], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'email-template.html';
            a.click();
            URL.revokeObjectURL(url);
        }

        // Função auxiliar para adicionar componente organizado
        function addOrganizedComponent(type, x, y, width, canvas) {
            const id = `component-${componentCounter++}`;
            let html = '';

            switch(type) {
                case 'heading':
                    html = `<h1 id="${id}" class="component-hover draggable cursor-move font-bold text-gray-900" data-type="heading" contenteditable="true" data-x="${x}" data-y="${y}" style="position: absolute; left: ${x}px; top: ${y}px; display: inline-block; width: ${width}px; max-width: 100%; padding: 16px; font-size: clamp(18px, 4vw, 32px); line-height: 1.2; touch-action: none; user-select: none;">Seu Título Aqui</h1>`;
                    break;
                case 'text':
                    html = `<p id="${id}" class="component-hover draggable cursor-move text-gray-700 leading-relaxed" data-type="text" contenteditable="true" data-x="${x}" data-y="${y}" style="position: absolute; left: ${x}px; top: ${y}px; display: inline-block; width: ${width}px; max-width: 100%; padding: 16px; font-size: clamp(14px, 2vw, 18px); line-height: 1.6; touch-action: none; user-select: none;">Escreva seu texto aqui. Você pode editar diretamente clicando.</p>`;
                    break;
                case 'button':
                    html = `<a id="${id}" href="#" class="component-hover draggable cursor-move bg-blue-600 text-white px-6 py-3 rounded-md font-medium hover:bg-blue-700 border border-blue-700" data-type="button" contenteditable="true" data-x="${x}" data-y="${y}" style="position: absolute; left: ${x}px; top: ${y}px; display: inline-block; width: fit-content; min-width: 120px; max-width: none; font-size: clamp(14px, 2vw, 16px); touch-action: none; user-select: none;">Clique Aqui</a>`;
                    break;
                case 'image':
                    html = `<img id="${id}" src="https://placehold.co/600x300/e2e8f0/64748b?text=Sua+Imagem" alt="Imagem" class="component-hover draggable cursor-move h-auto rounded-md border border-gray-200" data-type="image" data-x="${x}" data-y="${y}" style="position: absolute; left: ${x}px; top: ${y}px; display: block; width: ${width}px; max-width: 100%; touch-action: none; user-select: none;">`;
                    break;
                case 'divider':
                    html = `<hr id="${id}" class="component-hover draggable cursor-move border-t border-gray-300" data-type="divider" data-x="${x}" data-y="${y}" style="position: absolute; left: ${x}px; top: ${y}px; width: ${width}px; max-width: 100%; margin: 16px 0; touch-action: none; user-select: none;">`;
                    break;
                case 'spacer':
                    html = `<div id="${id}" class="component-hover draggable cursor-move bg-gray-50 border border-dashed border-gray-300 flex items-center justify-center text-gray-400 text-xs" data-type="spacer" data-x="${x}" data-y="${y}" style="position: absolute; left: ${x}px; top: ${y}px; height: 40px; width: ${Math.min(width, 150)}px; display: flex; touch-action: none; user-select: none;">Espaçador</div>`;
                    break;
            }

            canvas.insertAdjacentHTML('beforeend', html);
            
            const newElement = canvas.querySelector(`#${id}`);
            if (newElement) {
                newElement.style.display = 'block';
                newElement.style.visibility = 'visible';
                newElement.style.opacity = '1';
                
                // Make draggable
                interact(`#${id}`).draggable({
                    inertia: false,
                    autoScroll: true,
                    listeners: {
                        start: function(event) {
                            const target = event.target;
                            if (target.hasAttribute('contenteditable')) {
                                target.setAttribute('contenteditable', 'false');
                                target._wasContentEditable = true;
                            }
                            const currentPos = target.style.position || window.getComputedStyle(target).position;
                            if (currentPos !== 'absolute') {
                                const rect = target.getBoundingClientRect();
                                const canvasRect = canvas.getBoundingClientRect();
                                const left = rect.left - canvasRect.left;
                                const top = rect.top - canvasRect.top;
                                target.style.setProperty('position', 'absolute', 'important');
                                target.style.left = left + 'px';
                                target.style.top = top + 'px';
                                target.setAttribute('data-x', left);
                                target.setAttribute('data-y', top);
                            }
                            target.style.zIndex = '1000';
                            target.style.cursor = 'grabbing';
                        },
                        move: dragMoveListener,
                        end: function(event) {
                            const target = event.target;
                            if (target._wasContentEditable) {
                                target.setAttribute('contenteditable', 'true');
                                delete target._wasContentEditable;
                            }
                            target.style.cursor = 'move';
                            target.style.zIndex = '';
                            saveHistory();
                        }
                    }
                });
                
                // Make resizable
                interact(`#${id}`).resizable({
                    edges: { left: false, right: true, bottom: true, top: false },
                    listeners: {
                        move: resizeMoveListener
                    }
                });
                
                // Select on click (but allow drag)
                let clickStartTime = 0;
                let clickStartPos = { x: 0, y: 0 };
                
                newElement.addEventListener('mousedown', function(e) {
                    clickStartTime = Date.now();
                    clickStartPos = { x: e.clientX, y: e.clientY };
                });
                
                newElement.addEventListener('click', function(e) {
                    const clickDuration = Date.now() - clickStartTime;
                    const clickDistance = Math.sqrt(
                        Math.pow(e.clientX - clickStartPos.x, 2) + 
                        Math.pow(e.clientY - clickStartPos.y, 2)
                    );
                    
                    // Só selecionar se foi um clique rápido e sem movimento significativo
                    if (clickDuration < 300 && clickDistance < 5) {
                        e.stopPropagation();
                        selectElement(this);
                    }
                });
                
                // Auto-adjust font size based on element size
                setupAutoFontSize(newElement);
            }
            
            return newElement;
        }

        // Load templates
        function loadTemplate(type) {
            const desktopCanvas = document.getElementById('emailCanvas');
            const mobileCanvas = document.getElementById('emailCanvasMobile');
            
            // Limpar ambos os canvases
            if (desktopCanvas) {
                desktopCanvas.innerHTML = '';
            }
            if (mobileCanvas) {
                mobileCanvas.innerHTML = '';
            }
            
            // Resetar contador de componentes
            componentCounter = 0;
            
            const activeCanvas = getActiveCanvas();
            if (!activeCanvas) {
                console.error('Canvas não encontrado');
                return;
            }
            
            const canvasWidth = activeCanvas.offsetWidth || 600;
            const canvasHeight = activeCanvas.offsetHeight || 800;
            const contentWidth = Math.min(500, canvasWidth * 0.85); // 85% da largura ou máximo 500px
            const startX = (canvasWidth - contentWidth) / 2; // Centralizado
            let currentY = 40; // Começar a 40px do topo
            const spacing = 20; // Espaçamento entre elementos

            switch(type) {
                case 'welcome':
                    // Heading
                    const h1El = addOrganizedComponent('heading', startX, currentY, contentWidth, activeCanvas);
                    currentY += 60 + spacing;
                    
                    // Text
                    const p1El = addOrganizedComponent('text', startX, currentY, contentWidth, activeCanvas);
                    currentY += 80 + spacing;
                    
                    // Button (centralizado)
                    const btnWidth = 180;
                    const btnX = startX + (contentWidth - btnWidth) / 2;
                    const btnEl = addOrganizedComponent('button', btnX, currentY, btnWidth, activeCanvas);
                    currentY += 50 + spacing * 2;
                    
                    // Divider
                    const divEl = addOrganizedComponent('divider', startX, currentY, contentWidth, activeCanvas);
                    currentY += 20 + spacing;
                    
                    // Text final
                    addOrganizedComponent('text', startX, currentY, contentWidth, activeCanvas);
                    
                    setTimeout(() => {
                        if (h1El) h1El.textContent = 'Bem-vindo!';
                        if (p1El) p1El.textContent = 'Obrigado por se cadastrar. Estamos felizes em ter você conosco!';
                        if (btnEl) btnEl.textContent = 'Começar Agora';
                        const paragraphs = activeCanvas.querySelectorAll('p');
                        if (paragraphs[1]) paragraphs[1].textContent = 'Se precisar de ajuda, estamos aqui para você.';
                        
                        updateLayersPanel();
                        syncCanvasContent();
                        saveHistory();
                    }, 100);
                    break;

                case 'newsletter':
                    // Image
                    const imgWidth = Math.min(600, contentWidth);
                    const imgX = startX + (contentWidth - imgWidth) / 2;
                    const imgEl = addOrganizedComponent('image', imgX, currentY, imgWidth, activeCanvas);
                    currentY += 200 + spacing * 2;
                    
                    // Heading
                    const h1El2 = addOrganizedComponent('heading', startX, currentY, contentWidth, activeCanvas);
                    currentY += 60 + spacing;
                    
                    // Text
                    const p1El2 = addOrganizedComponent('text', startX, currentY, contentWidth, activeCanvas);
                    currentY += 80 + spacing;
                    
                    // Button (centralizado)
                    const btnWidth2 = 180;
                    const btnX2 = startX + (contentWidth - btnWidth2) / 2;
                    const btnEl2 = addOrganizedComponent('button', btnX2, currentY, btnWidth2, activeCanvas);
                    currentY += 50 + spacing * 2;
                    
                    // Spacer
                    const spacerEl = addOrganizedComponent('spacer', startX, currentY, contentWidth, activeCanvas);
                    currentY += 40 + spacing;
                    
                    // Text final
                    addOrganizedComponent('text', startX, currentY, contentWidth, activeCanvas);
                    
                    setTimeout(() => {
                        if (h1El2) h1El2.textContent = 'Newsletter Semanal';
                        if (p1El2) p1El2.textContent = 'Confira as últimas novidades e atualizações desta semana.';
                        if (btnEl2) btnEl2.textContent = 'Ler Mais';
                        const paragraphs = activeCanvas.querySelectorAll('p');
                        if (paragraphs[1]) paragraphs[1].textContent = 'Obrigado por assinar nossa newsletter!';
                        
                        updateLayersPanel();
                        syncCanvasContent();
                        saveHistory();
                    }, 100);
                    break;

                case 'promo':
                    // Heading
                    const h1El3 = addOrganizedComponent('heading', startX, currentY, contentWidth, activeCanvas);
                    currentY += 60 + spacing;
                    
                    // Text
                    const p1El3 = addOrganizedComponent('text', startX, currentY, contentWidth, activeCanvas);
                    currentY += 80 + spacing;
                    
                    // Button (centralizado)
                    const btnWidth3 = 200;
                    const btnX3 = startX + (contentWidth - btnWidth3) / 2;
                    const btnEl3 = addOrganizedComponent('button', btnX3, currentY, btnWidth3, activeCanvas);
                    currentY += 50 + spacing * 2;
                    
                    // Divider
                    const divEl2 = addOrganizedComponent('divider', startX, currentY, contentWidth, activeCanvas);
                    currentY += 20 + spacing;
                    
                    // Text final
                    addOrganizedComponent('text', startX, currentY, contentWidth, activeCanvas);
                    
                    setTimeout(() => {
                        if (h1El3) h1El3.textContent = '50% de Desconto!';
                        if (p1El3) p1El3.textContent = 'Promoção especial por tempo limitado. Não perca esta oportunidade!';
                        if (btnEl3) btnEl3.textContent = 'Aproveitar Oferta';
                        const paragraphs = activeCanvas.querySelectorAll('p');
                        if (paragraphs[1]) paragraphs[1].textContent = 'Válido até o final do mês.';
                        
                        updateLayersPanel();
                        syncCanvasContent();
                        saveHistory();
                    }, 100);
                    break;
            }
        }

        // Deselect when clicking canvas background - both canvases
        function setupCanvasClick(canvas) {
            if (!canvas) return;
        canvas.addEventListener('click', function(e) {
                if (e.target === canvas || e.target.id === 'canvas-placeholder' || e.target.id === 'canvas-placeholder-mobile') {
                document.querySelectorAll('.component-selected').forEach(el => {
                    el.classList.remove('component-selected');
                });
                selectedElement = null;
                    hideFloatingToolbar();
                document.getElementById('propertiesPanel').innerHTML = 
                        '<div class="p-6 text-center"><svg class="w-10 h-10 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076-.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><div class="text-xs font-medium mb-1" style="color: var(--text-muted);">Nenhum elemento selecionado</div><div class="text-[10px]" style="color: var(--text-muted); opacity: 0.6;">Clique em um componente para editar</div></div>';
                }
            });
        }
        
        setupCanvasClick(desktopCanvas);
        setupCanvasClick(mobileCanvas);

        // Eyedropper/Pincel functionality
        let eyedropperMode = false;
        let eyedropperTarget = null;
        let activeEyedropperBtn = null;
        
        function activateEyedropper(target, btnElement) {
            eyedropperMode = true;
            eyedropperTarget = target;
            activeEyedropperBtn = btnElement;
            document.body.classList.add('eyedropper-mode');
            
            // Update button states
            document.querySelectorAll('.eyedropper-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            if (btnElement) {
                btnElement.classList.add('active');
            }
            
            // Add click listener
            document.addEventListener('click', handleEyedropperClick, { once: true, capture: true });
        }
        
        function handleEyedropperClick(e) {
            if (!eyedropperMode) return;
            
            e.preventDefault();
            e.stopPropagation();
            
            // Skip if clicked on UI elements
            if (e.target.closest('#propertiesPanel') || e.target.closest('#floatingToolbar') || 
                e.target.closest('.color-picker-wrapper') || e.target.closest('.eyedropper-btn') ||
                e.target.closest('.property-section') || e.target.closest('.section-header')) {
                deactivateEyedropper();
                return;
            }
            
            // Get element at click position
            const element = document.elementFromPoint(e.clientX, e.clientY);
            if (!element) {
                deactivateEyedropper();
                return;
            }
            
            // Skip UI elements
            if (element.closest('#propertiesPanel') || element.closest('#floatingToolbar') || 
                element.closest('.color-picker-wrapper') || element.closest('.eyedropper-btn')) {
                deactivateEyedropper();
                return;
            }
            
            // Get color from element - try multiple sources
            const computed = window.getComputedStyle(element);
            let color = null;
            
            // Try background color first
            if (computed.backgroundColor && computed.backgroundColor !== 'transparent' && computed.backgroundColor !== 'rgba(0, 0, 0, 0)') {
                color = computed.backgroundColor;
            }
            // Then try text color
            else if (computed.color && computed.color !== 'transparent' && computed.color !== 'rgba(0, 0, 0, 0)') {
                color = computed.color;
            }
            // Then try border color
            else if (computed.borderColor && computed.borderColor !== 'transparent' && computed.borderColor !== 'rgba(0, 0, 0, 0)') {
                color = computed.borderColor;
            }
            
            // Try to get from parent if current element has no color
            if (!color || color === 'transparent' || color === 'rgba(0, 0, 0, 0)') {
                let parent = element.parentElement;
                let attempts = 0;
                while (parent && attempts < 5) {
                    const parentComputed = window.getComputedStyle(parent);
                    if (parentComputed.backgroundColor && parentComputed.backgroundColor !== 'transparent' && parentComputed.backgroundColor !== 'rgba(0, 0, 0, 0)') {
                        color = parentComputed.backgroundColor;
                        break;
                    }
                    parent = parent.parentElement;
                    attempts++;
                }
            }
            
            if (color && color !== 'transparent' && color !== 'rgba(0, 0, 0, 0)') {
                const hex = rgbToHex(color);
                applyEyedropperColor(hex);
            }
            
            deactivateEyedropper();
        }
        
        function applyEyedropperColor(hex) {
            if (!eyedropperTarget) return;
            
            switch(eyedropperTarget) {
                case 'fill':
                    updateFill(hex);
                    const fillPicker = document.getElementById('fill-color-picker');
                    const fillInput = fillPicker?.nextElementSibling;
                    if (fillPicker) fillPicker.value = hex;
                    if (fillInput) fillInput.value = hex;
                    break;
                case 'border':
                    updateBorderColor(hex);
                    const borderPicker = document.getElementById('border-color-picker');
                    const borderInput = borderPicker?.nextElementSibling;
                    if (borderPicker) borderPicker.value = hex;
                    if (borderInput) borderInput.value = hex;
                    break;
                case 'text':
                    updateTextColor(hex);
                    const textPicker = document.getElementById('text-color-picker');
                    const textInput = textPicker?.nextElementSibling;
                    if (textPicker) textPicker.value = hex;
                    if (textInput) textInput.value = hex;
                    break;
                case 'button':
                    updateButtonBg(hex);
                    const buttonPicker = document.getElementById('button-bg-picker');
                    const buttonInput = buttonPicker?.nextElementSibling;
                    if (buttonPicker) buttonPicker.value = hex;
                    if (buttonInput) buttonInput.value = hex;
                    break;
            }
            
            saveHistory();
        }
        
        function deactivateEyedropper() {
            eyedropperMode = false;
            eyedropperTarget = null;
            document.body.classList.remove('eyedropper-mode');
            document.querySelectorAll('.eyedropper-btn').forEach(btn => {
                btn.classList.remove('active');
            });
        }
        
        // Desabilitar interações padrão do mouse
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            // Pode adicionar menu customizado aqui se necessário
        });
        
        // Desabilitar seleção de texto acidental (mas permitir em elementos editáveis quando não estiver arrastando)
        let isDraggingElement = false;
        document.addEventListener('selectstart', function(e) {
            // Se estiver arrastando, bloquear seleção
            if (isDraggingElement) {
                e.preventDefault();
                return;
            }
            // Permitir seleção apenas em inputs e textareas
            if (!e.target.matches('input, textarea')) {
                // Para elementos contenteditable, só permitir se não estiver arrastando
                if (e.target.hasAttribute('contenteditable')) {
                    // Verificar se é um componente
                    if (e.target.closest('[id^="component-"]')) {
                        e.preventDefault();
                    }
                } else {
                    e.preventDefault();
                }
            }
        });
        
        // Detectar quando está arrastando
        document.addEventListener('mousedown', function(e) {
            if (e.target.closest('[id^="component-"]')) {
                isDraggingElement = true;
            }
        });
        
        document.addEventListener('mouseup', function(e) {
            setTimeout(() => {
                isDraggingElement = false;
            }, 100);
        });
        
        // Desabilitar drag padrão de imagens
        document.addEventListener('dragstart', function(e) {
            if (e.target.tagName === 'IMG' && !e.target.closest('#emailCanvas, #emailCanvasMobile')) {
                e.preventDefault();
            }
        });
        
        // Prevenir zoom com Ctrl+Scroll
        document.addEventListener('wheel', function(e) {
            if (e.ctrlKey) {
                e.preventDefault();
            }
        }, { passive: false });
        
        // Prevenir atalhos padrão que podem interferir
        document.addEventListener('keydown', function(e) {
            // Desabilitar F5, Ctrl+R, etc apenas em certos contextos
            if (e.key === 'F5' || (e.ctrlKey && e.key === 'r')) {
                // Permitir refresh normal, mas pode customizar se necessário
            }
            
            // Atalho para eyedropper
            if (e.key === 'i' || e.key === 'I') {
                if (document.activeElement && document.activeElement.closest('#propertiesPanel')) {
                    e.preventDefault();
                    const firstEyedropper = document.querySelector('.eyedropper-btn');
                    if (firstEyedropper) {
                        firstEyedropper.click();
                    }
                }
            }
            
            // ESC para sair do eyedropper
            if (e.key === 'Escape' && eyedropperMode) {
                deactivateEyedropper();
            }
        });

        // Initialize history with empty canvas
        saveHistory();
        
        // Initialize layers panel
        updateLayersPanel();
    </script>
</body>
</html>
