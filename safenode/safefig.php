<?php
/**
 * SafeNode Relay - Editor Visual
 * Editor visual de templates de e-mail
 */

session_start();
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

$userId = $_SESSION['safenode_user_id'] ?? null;
$templateFromMail = null;
$projectDataFromMail = null;

// Verificar se há template para importar da sessão (vindo do mail.php)
if (isset($_SESSION['safefig_import_template']) && !empty($_SESSION['safefig_import_template'])) {
    $templateFromMail = $_SESSION['safefig_import_template'];
    // Limpar a sessão após pegar o template (usar apenas uma vez)
    unset($_SESSION['safefig_import_template']);
}

// Verificar se há dados do projeto para importar da sessão (vindo do mail.php)
if (isset($_SESSION['safefig_project_data']) && !empty($_SESSION['safefig_project_data'])) {
    $projectDataFromMail = $_SESSION['safefig_project_data'];
    // Limpar a sessão após pegar os dados (usar apenas uma vez)
    unset($_SESSION['safefig_project_data']);
}
?>
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
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs2@0.0.2/qrcode.min.js"></script>
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
        
        /* Desabilitar seleção de texto em toda a página */
        body, html {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        /* Permitir seleção apenas em inputs e textareas */
        input, textarea, [contenteditable="true"] {
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
            user-select: text;
        }
        
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
            border: none !important;
        }
        
        /* Garantir que canvas não tenha borda */
        #emailCanvas,
        [id^="emailCanvas-"] {
            border: none !important;
            outline: none !important;
        }
        
        .drop-zone.drag-over {
            background: rgba(255, 255, 255, 0.02);
            box-shadow: inset 0 0 20px rgba(255, 255, 255, 0.05);
            border: none !important;
            outline: none !important;
        }
        
        /* Modo escuro do canvas */
        [data-dark-mode="true"] .drop-zone {
            background-color: #0a0a0a !important;
            color: #ffffff !important;
        }
        
        [data-dark-mode="true"] .drop-zone h1,
        [data-dark-mode="true"] .drop-zone h2,
        [data-dark-mode="true"] .drop-zone h3,
        [data-dark-mode="true"] .drop-zone h4,
        [data-dark-mode="true"] .drop-zone h5,
        [data-dark-mode="true"] .drop-zone h6 {
            color: #ffffff !important;
        }
        
        [data-dark-mode="true"] .drop-zone p,
        [data-dark-mode="true"] .drop-zone span,
        [data-dark-mode="true"] .drop-zone div:not([class*="component"]) {
            color: #e5e7eb !important;
        }
        
        [data-dark-mode="true"] .drop-zone a {
            color: #60a5fa !important;
        }
        
        /* Remover todas as bordas dos frames e wrappers */
        .phone-frame-wrapper,
        .phone-frame,
        .phone-screen {
            border: none !important;
            outline: none !important;
        }
        
        .canvas-frame {
            border: none !important;
            outline: none !important;
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
            box-shadow: none !important;
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
            opacity: 0.95;
            box-shadow: none !important;
            box-shadow: var(--shadow-xl);
            will-change: transform;
            transform: translateZ(0); /* Force GPU acceleration */
            backface-visibility: hidden; /* Melhor performance */
            -webkit-backface-visibility: hidden;
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
        
        /* Canvas Frame Styles */
        .canvas-frame {
            transition: all 0.2s ease;
            cursor: move;
            z-index: 10;
        }
        
        .canvas-frame:hover {
            outline: 2px solid rgba(255, 255, 255, 0.15);
            outline-offset: 4px;
        }
        
        .canvas-frame.selected {
            outline: 3px solid rgba(255, 255, 255, 0.8) !important;
            outline-offset: 6px !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3), 0 0 20px rgba(59, 130, 246, 0.2) !important;
        }
        
        .canvas-frame.dragging {
            opacity: 0.95;
            cursor: grabbing !important;
            z-index: 1000;
            outline: 2px solid rgba(255, 255, 255, 0.6) !important;
            outline-offset: 4px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }
        
        .frame-header {
            pointer-events: auto;
            user-select: none;
            cursor: move;
            padding: 4px 12px;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.2s ease;
        }
        
        .frame-header:hover {
            background: rgba(0, 0, 0, 0.8);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .frame-delete-btn {
            pointer-events: auto;
        }
        
        .frame-delete-btn:active {
            transform: scale(0.9);
        }
        
        /* Infinite Canvas Container */
        #infiniteCanvasContainer {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden; /* Cortar o que sair dos limites */
        }
        
        /* Frames Container - ajustado para não expandir a página */
        #framesContainer {
            display: inline-block;
            position: relative;
            min-width: 0;
            max-width: none;
            transform: translate(0px, 0px);
            will-change: transform;
        }
        
        /* Imagem de fundo quando não há frames */
        #emptyCanvasImage {
            transition: opacity 0.3s ease;
        }
        
        #emptyCanvasImage.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        /* Garantir que o container do canvas não expanda a página */
        .flex-1 {
            min-width: 0;
            overflow: hidden;
        }
        
        #infiniteCanvasContainer::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }
        
        #infiniteCanvasContainer::-webkit-scrollbar-track {
            background: transparent;
        }
        
        #infiniteCanvasContainer::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.1);
            border-radius: 6px;
        }
        
        #infiniteCanvasContainer::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.2);
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
            gap: 4px;
            padding: 6px;
            background: rgba(30, 30, 30, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
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
            height: 20px;
            background: rgba(255, 255, 255, 0.1);
            margin: 0 2px;
            flex-shrink: 0;
        }
        
        .floating-toolbar-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            min-width: 32px;
            min-height: 32px;
            border-radius: 6px;
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
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }
        
        .floating-toolbar-btn.danger:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        
        /* Responsividade para diferentes tamanhos de tela desktop */
        
        /* Telas pequenas (1024px - 1280px) */
        @media (min-width: 1024px) and (max-width: 1280px) {
            /* Reduzir largura dos painéis laterais */
            .w-64 {
                width: 200px !important;
                min-width: 200px !important;
            }
            
            .w-80 {
                width: 240px !important;
                min-width: 240px !important;
                max-width: 240px !important;
            }
            
            /* Ajustar fontes e espaçamentos */
            .text-xs {
                font-size: 10px !important;
            }
            
            .text-[10px] {
                font-size: 9px !important;
            }
            
            .text-[11px] {
                font-size: 10px !important;
            }
            
            /* Reduzir padding */
            .p-3 {
                padding: 0.5rem !important;
            }
            
            .p-2\.5 {
                padding: 0.5rem !important;
            }
            
            /* Ajustar toolbar */
            .toolbar-btn {
                padding: 0.375rem 0.5rem !important;
                font-size: 10px !important;
            }
            
            /* Ajustar ícones */
            .w-4, .h-4 {
                width: 14px !important;
                height: 14px !important;
            }
            
            .w-3\.5, .h-3\.5 {
                width: 12px !important;
                height: 12px !important;
            }
            
            .w-3, .h-3 {
                width: 11px !important;
                height: 11px !important;
            }
        }
        
        /* Telas médias (1280px - 1440px) */
        @media (min-width: 1280px) and (max-width: 1440px) {
            /* Largura dos painéis ligeiramente reduzida */
            .w-64 {
                width: 220px !important;
                min-width: 220px !important;
            }
            
            .w-80 {
                width: 280px !important;
                min-width: 280px !important;
                max-width: 280px !important;
            }
            
            /* Ajustes sutis em fontes */
            .text-[10px] {
                font-size: 9.5px !important;
            }
            
            .text-[11px] {
                font-size: 10.5px !important;
            }
        }
        
        /* Telas muito pequenas (800px - 1023px) - Ainda desktop mas compacto */
        @media (min-width: 800px) and (max-width: 1023px) {
            /* Painéis ainda mais compactos */
            .w-64 {
                width: 180px !important;
                min-width: 180px !important;
            }
            
            .w-80 {
                width: 220px !important;
                min-width: 220px !important;
                max-width: 220px !important;
            }
            
            /* Fontes menores */
            .text-xs {
                font-size: 9px !important;
            }
            
            .text-[10px] {
                font-size: 8.5px !important;
            }
            
            .text-[11px] {
                font-size: 9.5px !important;
            }
            
            /* Padding reduzido */
            .p-3 {
                padding: 0.4rem !important;
            }
            
            .p-2\.5 {
                padding: 0.4rem !important;
            }
            
            /* Toolbar mais compacta */
            .toolbar-btn {
                padding: 0.25rem 0.4rem !important;
                font-size: 9px !important;
            }
            
            /* Ícones menores */
            .w-4, .h-4 {
                width: 12px !important;
                height: 12px !important;
            }
            
            .w-3\.5, .h-3\.5 {
                width: 11px !important;
                height: 11px !important;
            }
            
            .w-3, .h-3 {
                width: 10px !important;
                height: 10px !important;
            }
        }
        
        /* Telas grandes (1440px+) - Tamanho padrão mantido */
        @media (min-width: 1440px) {
            /* Manter tamanhos padrão */
        }
        
        /* Ajustes gerais para todos os tamanhos desktop */
        @media (min-width: 800px) {
            /* Garantir que o container principal ocupe toda a largura */
            .flex.h-screen {
                width: 100vw !important;
                max-width: 100vw !important;
            }
            
            /* Canvas area responsiva */
            .flex-1 {
                min-width: 0 !important;
                flex: 1 1 0% !important;
            }
            
            /* Toolbar responsiva */
            .toolbar-container {
                flex-wrap: wrap;
                gap: 0.25rem;
            }
            
            /* Ajustar toolbar em telas menores */
            @media (max-width: 1280px) {
                .toolbar-container {
                    padding: 0.4rem !important;
                }
                
                .toolbar-container > div {
                    gap: 0.5rem !important;
                }
            }
            
            @media (max-width: 1024px) {
                .toolbar-container {
                    padding: 0.35rem !important;
                }
                
                .toolbar-container > div {
                    gap: 0.4rem !important;
                }
            }
            
            /* Ajustar scrollbars em painéis menores */
            #componentsSidebar,
            #propertiesPanel {
                scrollbar-width: thin;
            }
            
            /* Garantir que frames não quebrem o layout */
            .canvas-frame {
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            
            /* Garantir que painéis laterais não quebrem */
            .w-64, .w-80 {
                flex-shrink: 0 !important;
                overflow: hidden !important;
            }
            
            /* Ajustar floating toolbar em telas menores */
            @media (max-width: 1280px) {
                .floating-toolbar {
                    width: 240px !important;
                }
                
                .floating-toolbar-btn {
                    width: 28px !important;
                    height: 28px !important;
                }
            }
        }
        
        /* Prevenir quebra em telas muito pequenas (menos de 800px não é desktop) */
        @media (max-width: 799px) {
            body {
                overflow: auto !important;
            }
            
            .flex.h-screen {
                flex-direction: column !important;
            }
        }
    </style>
</head>
<body class="h-full m-0 overflow-hidden">

    <!-- Main Container -->
    <div class="flex h-screen" style="overflow: hidden; width: 100vw;">
        
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
            <div class="p-2.5 flex items-center justify-between toolbar-container" style="background: var(--bg-card); border-bottom: 1px solid var(--border-subtle);">
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
                        <div class="relative">
                            <button onclick="showMobileOptions()" id="btn-mobile" class="toolbar-btn px-2.5 py-1 rounded text-[11px] font-medium flex items-center gap-1.5" style="font-size: 11px;">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                            </svg>
                            Mobile
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                </svg>
                        </button>
                            <!-- Dropdown de opções mobile -->
                            <div id="mobileOptionsDropdown" class="hidden absolute top-full left-0 mt-1 rounded" style="background: var(--bg-card); border: 1px solid var(--border-subtle); min-width: 140px; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                                <button onclick="setCanvasSize('mobile', 'ios')" class="w-full px-3 py-2 text-left text-[11px] font-medium flex items-center gap-2 hover:bg-var(--bg-hover)" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-subtle);" onmouseover="this.style.background='var(--bg-hover)'; this.style.color='var(--text-primary)'" onmouseout="this.style.background='transparent'; this.style.color='var(--text-secondary)'">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.05 20.28c-.98.95-2.05.88-3.08.33-1.09-.58-2.21-.6-3.34 0-1.44.62-2.2.44-3.08-.33C1.79 15.25 2.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                                    </svg>
                                    iOS
                                </button>
                                <button onclick="setCanvasSize('mobile', 'android')" class="w-full px-3 py-2 text-left text-[11px] font-medium flex items-center gap-2" style="color: var(--text-secondary);" onmouseover="this.style.background='var(--bg-hover)'; this.style.color='var(--text-primary)'" onmouseout="this.style.background='transparent'; this.style.color='var(--text-secondary)'">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5508 0 .9993.4486.9993.9993.0001.5511-.4485.9997-.9993.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5508 0 .9993.4486.9993.9993 0 .5511-.4485.9997-.9993.9997m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C18.5902 8.2439 17.8533 8.0811 17.0884 8.0811c-.7449 0-1.4547.1622-2.0953.4248l-2.381-4.1228a.4157.4157 0 00-.5676-.1521.4157.4157 0 00-.1521.5676l2.3584 4.0804c-.7449.7449-1.222 1.761-1.222 2.8919 0 .7497.2392 1.4337.6395 2.0223l-2.5257 4.3808a.4164.4164 0 00.1521.5676.4157.4157 0 00.5676-.1521l2.5453-4.4026c.7449.315 1.582.5103 2.4708.5103.8837 0 1.7158-.1934 2.4586-.5103l2.5206 4.3612a.4157.4157 0 00.5676.1521.4164.4164 0 00.1521-.5676l-2.5008-4.3302c.4003-.5886.6395-1.2726.6395-2.0223-.0005-1.131-.477-2.147-1.2219-2.892m-5.762 3.4158h5.2702c-.1848-.5886-.5886-1.0868-1.1397-1.4103l-2.381 4.1228c-.315.1934-.6656.315-1.0353.315-.3696 0-.7202-.1216-1.0353-.315l-2.381-4.1228c-.5511.3235-.9549.8217-1.1397 1.4103"/>
                                    </svg>
                                    Android
                                </button>
                            </div>
                        </div>
                        <button onclick="showCustomSize()" id="btn-custom" class="toolbar-btn px-2.5 py-1 rounded text-[11px] font-medium flex items-center gap-1.5" style="font-size: 11px;">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076-.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Custom
                        </button>
                    </div>

                    <div class="w-px h-5" style="background: var(--border-subtle);"></div>

                    <!-- Add Frame Button -->
                    <button onclick="addNewFrame()" class="px-3 py-1.5 rounded text-[11px] font-medium transition-all flex items-center gap-1.5"
                            style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-subtle); color: var(--text-secondary);"
                            onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.color='var(--text-primary)'" 
                            onmouseout="this.style.background='rgba(255,255,255,0.03)'; this.style.color='var(--text-secondary)'"
                            data-tooltip="Adicionar novo frame">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Novo Frame
                    </button>

                    <div class="w-px h-5" style="background: var(--border-subtle);"></div>

                    <!-- Mobile Preview Button -->
                    <button onclick="generateMobilePreview()" class="px-3 py-1.5 rounded text-[11px] font-medium transition-all flex items-center gap-1.5"
                            style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); color: #60a5fa;"
                            onmouseover="this.style.background='rgba(59, 130, 246, 0.2)'; this.style.borderColor='rgba(59, 130, 246, 0.5)'" 
                            onmouseout="this.style.background='rgba(59, 130, 246, 0.1)'; this.style.borderColor='rgba(59, 130, 246, 0.3)'"
                            data-tooltip="Ver no dispositivo móvel">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                        </svg>
                        Ver no Mobile
                    </button>

                    <div class="w-px h-5" style="background: var(--border-subtle);"></div>

                    <!-- Import Button (mostrar apenas se houver template para importar) -->
                    <?php if ($templateFromMail): ?>
                    <button onclick="importTemplateFromMail()" class="px-3 py-1.5 rounded text-[11px] font-medium transition-all flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white"
                            data-tooltip="Importar template do Mail">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                        </svg>
                        Importar Template
                    </button>
                    <div class="w-px h-5" style="background: var(--border-subtle);"></div>
                    <?php endif; ?>

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

            <!-- Canvas Area - Espaço Infinito com Malha Quadriculada -->
            <div class="flex-1 relative" style="background: var(--bg-primary); overflow: hidden;">
                <!-- Container com scroll independente e malha infinita -->
                <div id="infiniteCanvasContainer" class="h-full" style="background-image: 
                    linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
                    background-size: 20px 20px;
                    background-position: 0px 0px;
                    background-attachment: scroll;
                    position: relative;
                    overflow: hidden;
                    cursor: grab;">
                    <!-- Imagem de fundo quando não há frames - Centralizada na viewport -->
                    <div id="emptyCanvasImage" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.25; pointer-events: none; z-index: 1; transition: left 0.3s ease;">
                        <img src="assets/img/ds.png" alt="Empty Canvas" style="width: 1150px; height: 600px; object-fit: contain; filter: brightness(0.9); display: block;">
                            </div>
                    <!-- Container interno para frames (espaço infinito) -->
                    <div id="framesContainer" style="position: relative; display: inline-block; padding: 100px; box-sizing: border-box;">
                        <!-- Frames serão adicionados aqui dinamicamente -->
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

    <!-- Mobile Preview Modal -->
    <div id="mobilePreviewModal" class="hidden fixed inset-0 flex items-center justify-center z-50" style="background: rgba(0,0,0,0.8); backdrop-filter: blur(4px);">
        <div class="rounded-lg p-6" style="background: var(--bg-card); border: 1px solid var(--border-subtle); max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold" style="color: var(--text-primary);">Visualização Mobile</h3>
                <button onclick="closeMobilePreview()" class="p-1 rounded hover:bg-var(--bg-hover)" style="color: var(--text-secondary);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="text-center mb-4">
                <p class="text-xs mb-4" style="color: var(--text-secondary);">Escaneie o QR code com seu dispositivo móvel para visualizar o template</p>
                <div class="flex justify-center mb-4" style="width: 100%;">
                    <div style="background: #ffffff; border-radius: 12px; padding: 20px; display: inline-block; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                        <div id="qrcode" class="flex justify-center"></div>
                    </div>
                </div>
                <div class="p-3 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-subtle);">
                    <p class="text-[10px] mb-2" style="color: var(--text-muted);">Link temporário (válido por 1 hora):</p>
                    <div class="flex items-center gap-2">
                        <input type="text" id="previewLink" readonly class="flex-1 px-2 py-1.5 rounded text-xs" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-subtle); color: var(--text-primary); font-family: monospace;">
                        <button onclick="copyPreviewLink()" class="px-3 py-1.5 rounded text-xs font-medium transition-all" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-subtle); color: var(--text-secondary);" onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.color='var(--text-primary)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.color='var(--text-secondary)'">
                            Copiar
                        </button>
                    </div>
                </div>
                <p class="text-[10px] mt-3" style="color: var(--text-muted); opacity: 0.7;">⏱️ Este link expira em 1 hora</p>
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
        let selectedElements = []; // Array para múltiplos elementos selecionados
        let componentCounter = 0;
        let history = [];
        let historyIndex = -1;
        const MAX_HISTORY = 50;
        let frameCounter = 0; // Contador de frames
        let selectedFrame = null; // Frame selecionado atualmente

        // Initialize drag from sidebar
        document.querySelectorAll('.component-item').forEach(item => {
            item.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('componentType', this.dataset.type);
            });
        });

        // Função para configurar drop zone do canvas
        function setupCanvasDrop(canvas) {
            if (!canvas) return;
        
        canvas.addEventListener('dragover', function(e) {
            e.preventDefault();
                canvas.classList.add('drag-over');
        });

        canvas.addEventListener('dragleave', function(e) {
                canvas.classList.remove('drag-over');
        });

        canvas.addEventListener('drop', function(e) {
            e.preventDefault();
                canvas.classList.remove('drag-over');
            
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
        
        // Adicionar listener para deselecionar elementos ao clicar no canvas vazio
        canvas.addEventListener('click', function(e) {
            const clickedElement = e.target;
            
            // Verificar se clicou diretamente no canvas ou em um placeholder
            // Não deselecionar se clicou em um componente ou dentro de um componente
            if (clickedElement === canvas || 
                (clickedElement.id && clickedElement.id.startsWith('canvas-placeholder'))) {
                
                // Deselecionar todos os elementos
                document.querySelectorAll('.component-selected').forEach(el => {
                    el.classList.remove('component-selected');
                });
                selectedElements = [];
                selectedElement = null;
                
                // Limpar painel de propriedades
                document.getElementById('propertiesPanel').innerHTML = 
                    '<div class="p-6 text-center"><svg class="w-10 h-10 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076-.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><div class="text-xs font-medium mb-1" style="color: var(--text-muted);">Nenhum elemento selecionado</div><div class="text-[10px]" style="color: var(--text-muted); opacity: 0.6;">Clique em um componente para editar</div></div>';
                
                // Esconder toolbar flutuante quando não há elementos selecionados
                const toolbar = document.getElementById('floatingToolbar');
                if (toolbar) {
                    toolbar.classList.remove('visible');
                }
                if (toolbarUpdateInterval) {
                    clearInterval(toolbarUpdateInterval);
                    toolbarUpdateInterval = null;
                }
                
                updateLayersSelection();
            }
        });
        }
        
        // Função para atualizar visibilidade da imagem de fundo
        function updateEmptyCanvasImage() {
            const emptyImage = document.getElementById('emptyCanvasImage');
            const framesContainer = document.getElementById('framesContainer');
            const infiniteContainer = document.getElementById('infiniteCanvasContainer');
            if (!emptyImage || !framesContainer || !infiniteContainer) return;
            
            const frames = framesContainer.querySelectorAll('.canvas-frame');
            if (frames.length === 0) {
                emptyImage.classList.remove('hidden');
                // Centralizar a imagem no meio da área visível do canvas
                const containerRect = infiniteContainer.getBoundingClientRect();
                const leftOffset = containerRect.left + (containerRect.width / 2);
                emptyImage.style.left = leftOffset + 'px';
                emptyImage.style.top = '50%';
            } else {
                emptyImage.classList.add('hidden');
            }
        }
        
        // Função para adicionar novo frame
        function addNewFrame() {
            frameCounter++;
            const framesContainer = document.getElementById('framesContainer');
            if (!framesContainer) return;
            
            // Calcular posição do novo frame (offset para não sobrepor)
            const existingFrames = framesContainer.querySelectorAll('.canvas-frame');
            const offsetX = 100 + (frameCounter * 50);
            const offsetY = 100 + (frameCounter * 50);
            
            // Criar novo frame
            const newFrame = document.createElement('div');
            newFrame.id = `frame-${frameCounter}`;
            newFrame.className = 'canvas-frame';
            newFrame.setAttribute('data-frame-id', frameCounter);
            newFrame.setAttribute('data-frame-name', `Frame ${frameCounter + 1}`);
            newFrame.style.cssText = `position: absolute; top: ${offsetY}px; left: ${offsetX}px; width: 900px; min-height: 600px; z-index: 10;`;
            
            // Header do frame
            const frameHeader = document.createElement('div');
            frameHeader.className = 'frame-header';
            frameHeader.style.cssText = 'position: absolute; top: -38px; left: 0; height: 32px; display: flex; align-items: center; gap: 8px; color: var(--text-primary); font-size: 11px; font-weight: 500;';
            frameHeader.innerHTML = `
                <svg class="w-3.5 h-3.5" style="opacity: 0.7;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
                <span class="frame-name-display" onclick="renameFrame('frame-${frameCounter}')" style="cursor: pointer; padding: 2px 4px; border-radius: 4px; transition: all 0.2s ease;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'">Frame ${frameCounter + 1}</span>
                <button onclick="toggleFrameDarkMode('frame-${frameCounter}')" class="frame-dark-mode-btn" style="margin-left: auto; padding: 4px; opacity: 0.6; cursor: pointer; border: none; background: none; color: var(--text-secondary); transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; margin-right: 4px;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'" data-tooltip="Alternar modo escuro">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
                    </svg>
                </button>
                <button onclick="deleteFrame('frame-${frameCounter}')" class="frame-delete-btn" style="padding: 4px; opacity: 0.6; cursor: pointer; border: none; background: none; color: var(--text-secondary); transition: all 0.2s ease; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.opacity='1'; this.style.color='#ef4444'" onmouseout="this.style.opacity='0.6'; this.style.color='var(--text-secondary)'" data-tooltip="Excluir frame">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                    </svg>
                </button>
            `;
            
            // Canvas interno do frame
            const canvas = document.createElement('div');
            canvas.id = `emailCanvas-${frameCounter}`;
            canvas.className = 'bg-white drop-zone shadow-2xl';
            canvas.style.cssText = 'width: 100%; min-height: 600px; transition: all 0.3s ease; position: relative; box-shadow: 0 4px 24px rgba(0,0,0,0.15); border-radius: 8px; overflow: visible; border: none;';
            
            // Placeholder
            const placeholder = document.createElement('div');
            placeholder.id = `canvas-placeholder-${frameCounter}`;
            placeholder.className = 'p-12 text-gray-400 text-center';
            placeholder.style.cssText = 'pointer-events: none;';
            placeholder.innerHTML = `
                <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
                <div class="text-sm font-medium mb-1" style="color: var(--text-muted);">Arraste componentes aqui</div>
                <div class="text-xs" style="color: var(--text-muted); opacity: 0.6;">ou use um template pronto</div>
            `;
            
            canvas.appendChild(placeholder);
            newFrame.appendChild(frameHeader);
            newFrame.appendChild(canvas);
            framesContainer.appendChild(newFrame);
            
            // Configurar drop zone para o novo canvas
            setupCanvasDrop(canvas);
            
            // Tornar frame arrastável
            makeFrameDraggable(newFrame);
            
            // Adicionar evento de clique no frame para seleção
            newFrame.addEventListener('click', function(e) {
                // Não fazer nada se clicar no botão de excluir
                if (e.target.closest('.frame-delete-btn')) {
                    return;
                }
                // Não selecionar se clicar em um componente dentro do canvas
                if (e.target.closest('.drop-zone') && e.target !== newFrame && !e.target.closest('.frame-header')) {
                    return;
                }
                if (e.target === newFrame || e.target.closest('.frame-header')) {
                    selectFrame(newFrame);
                }
            });
            
            // Selecionar o novo frame
            selectFrame(newFrame);
            
            // Atualizar visibilidade da imagem de fundo
            updateEmptyCanvasImage();
            
            saveHistory();
        }
        
        // Função para excluir um frame
        function deleteFrame(frameId) {
            const frame = document.getElementById(frameId);
            if (!frame) return;
            
            // Confirmar exclusão
            if (!confirm('Tem certeza que deseja excluir este frame? Todos os componentes dentro dele serão perdidos.')) {
                return;
            }
            
            // Encontrar o canvas dentro do frame e excluir todos os elementos
            // Pode estar dentro de phone-frame-wrapper ou diretamente no frame
            let canvas = frame.querySelector('.drop-zone');
            if (!canvas) {
                // Tentar encontrar dentro do phone-screen
                const phoneScreen = frame.querySelector('.phone-screen');
                if (phoneScreen) {
                    canvas = phoneScreen.querySelector('.drop-zone');
                }
            }
            
            if (canvas) {
                // Encontrar todos os elementos dentro do canvas (componentes)
                const elements = canvas.querySelectorAll('[id^="component-"]');
                
                // Remover interact.js listeners de cada elemento antes de excluir
                elements.forEach(element => {
                    if (element.id) {
                        try {
                            interact(`#${element.id}`).unset();
                        } catch(e) {
                            // Ignorar erros se o elemento já não tiver listeners
                        }
                    }
                });
                
                // Remover todos os elementos do canvas
                elements.forEach(element => {
                    element.remove();
                });
                
                // Limpar seleções se algum elemento excluído estava selecionado
                if (selectedElement && canvas.contains(selectedElement)) {
                    selectedElement = null;
                }
                selectedElements = selectedElements.filter(el => !canvas.contains(el));
            }
            
            // Verificar quantos frames existem
            const allFrames = document.querySelectorAll('.canvas-frame');
            
            // Se o frame sendo excluído está selecionado, selecionar outro
            if (selectedFrame === frame) {
                // Encontrar outro frame para selecionar
                const otherFrames = Array.from(allFrames).filter(f => f !== frame);
                if (otherFrames.length > 0) {
                    selectFrame(otherFrames[0]);
                } else {
                    selectedFrame = null;
                }
            }
            
            // Remover interações do interact.js
            if (typeof interact !== 'undefined') {
                interact(frame).unset();
            }
            
            // Remover o frame do DOM
            frame.remove();
            
            // Limpar propriedades se o frame excluído estava selecionado
            if (!selectedFrame) {
                document.getElementById('propertiesPanel').innerHTML = 
                    '<div class="p-6 text-center"><svg class="w-10 h-10 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076-.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><div class="text-xs font-medium mb-1" style="color: var(--text-muted);">Nenhum elemento selecionado</div><div class="text-[10px]" style="color: var(--text-muted); opacity: 0.6;">Clique em um componente para editar</div></div>';
            }
            
            // Atualizar visibilidade da imagem de fundo
            updateEmptyCanvasImage();
            
            saveHistory();
        }
        
        // Função para alternar modo escuro do frame
        function toggleFrameDarkMode(frameId) {
            const frame = document.getElementById(frameId);
            if (!frame) return;
            
            const canvas = frame.querySelector('.drop-zone');
            if (!canvas) return;
            
            const isDarkMode = frame.getAttribute('data-dark-mode') === 'true';
            const newDarkMode = !isDarkMode;
            
            // Atualizar atributo do frame
            frame.setAttribute('data-dark-mode', newDarkMode);
            
            // Atualizar botão
            const darkModeBtn = frame.querySelector('.frame-dark-mode-btn');
            if (darkModeBtn) {
                const icon = darkModeBtn.querySelector('svg path');
                if (newDarkMode) {
                    // Modo escuro ativo - mostrar ícone de sol
                    icon.setAttribute('d', 'M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z');
                    darkModeBtn.setAttribute('data-tooltip', 'Alternar modo claro');
                } else {
                    // Modo claro ativo - mostrar ícone de lua
                    icon.setAttribute('d', 'M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z');
                    darkModeBtn.setAttribute('data-tooltip', 'Alternar modo escuro');
                }
            }
            
            // Aplicar estilos ao canvas
            if (newDarkMode) {
                // Modo escuro
                canvas.classList.remove('bg-white');
                canvas.classList.add('bg-dark-900');
                canvas.style.backgroundColor = '#0a0a0a';
                canvas.style.color = '#ffffff';
                
                // Atualizar placeholder
                const placeholder = canvas.querySelector('[id^="canvas-placeholder"]');
                if (placeholder) {
                    placeholder.style.color = '#a1a1aa';
                    const svg = placeholder.querySelector('svg');
                    if (svg) svg.style.opacity = '0.5';
                }
                
                // Aplicar modo escuro aos elementos dentro do canvas
                applyDarkModeToElements(canvas, true);
            } else {
                // Modo claro
                canvas.classList.remove('bg-dark-900');
                canvas.classList.add('bg-white');
                canvas.style.backgroundColor = '#ffffff';
                canvas.style.color = '#000000';
                
                // Atualizar placeholder
                const placeholder = canvas.querySelector('[id^="canvas-placeholder"]');
                if (placeholder) {
                    placeholder.style.color = '#9ca3af';
                    const svg = placeholder.querySelector('svg');
                    if (svg) svg.style.opacity = '0.3';
                }
                
                // Remover modo escuro dos elementos
                applyDarkModeToElements(canvas, false);
            }
            
            saveHistory();
        }
        
        // Função auxiliar para aplicar modo escuro aos elementos
        function applyDarkModeToElements(container, isDark) {
            const elements = container.querySelectorAll('*');
            elements.forEach(el => {
                // Ignorar elementos que não devem ser alterados
                if (el.classList.contains('component-selected') || 
                    el.classList.contains('component-hover') ||
                    el.hasAttribute('data-no-dark-mode') ||
                    el.closest('.frame-header')) {
                    return;
                }
                
                // Aplicar estilos baseados no tipo de elemento
                const tagName = el.tagName.toLowerCase();
                const computedStyle = window.getComputedStyle(el);
                const currentColor = computedStyle.color;
                const currentBg = computedStyle.backgroundColor;
                
                if (isDark) {
                    // Modo escuro - aplicar apenas se o elemento não tiver cor definida ou for preto
                    if (tagName === 'h1' || tagName === 'h2' || tagName === 'h3' || 
                        tagName === 'h4' || tagName === 'h5' || tagName === 'h6') {
                        if (!el.style.color || currentColor === 'rgb(0, 0, 0)' || currentColor === 'rgb(17, 24, 39)') {
                            el.style.color = '#ffffff';
                        }
                    } else if (tagName === 'p' || tagName === 'span' || (tagName === 'div' && !el.id.startsWith('component-'))) {
                        if (!el.style.color || currentColor === 'rgb(0, 0, 0)' || currentColor === 'rgb(17, 24, 39)') {
                            el.style.color = '#e5e7eb';
                        }
                    } else if (tagName === 'a') {
                        if (!el.style.color || currentColor === 'rgb(0, 0, 0)' || currentColor === 'rgb(17, 24, 39)') {
                            el.style.color = '#60a5fa';
                        }
                    }
                    
                    // Ajustar fundos claros para escuros
                    if (currentBg === 'rgb(255, 255, 255)' || currentBg === 'white' || 
                        el.style.backgroundColor === '#ffffff' || el.style.backgroundColor === 'white') {
                        if (!el.hasAttribute('data-original-bg')) {
                            el.setAttribute('data-original-bg', el.style.backgroundColor || 'white');
                        }
                        el.style.backgroundColor = '#1a1a1a';
                    }
                } else {
                    // Modo claro - restaurar cores originais
                    if (el.hasAttribute('data-original-bg')) {
                        const originalBg = el.getAttribute('data-original-bg');
                        el.style.backgroundColor = originalBg === 'white' ? '' : originalBg;
                        el.removeAttribute('data-original-bg');
                    }
                    // Remover cores aplicadas pelo modo escuro
                    if (el.style.color === '#ffffff' || el.style.color === '#e5e7eb' || el.style.color === '#60a5fa') {
                        el.style.color = '';
                    }
                }
            });
        }
        
        // Função para aplicar modo escuro a novos elementos adicionados
        function applyDarkModeToNewElement(element) {
            const frame = element.closest('.canvas-frame');
            if (frame && frame.getAttribute('data-dark-mode') === 'true') {
                // Aplicar modo escuro apenas ao novo elemento
                const tagName = element.tagName.toLowerCase();
                const computedStyle = window.getComputedStyle(element);
                const currentColor = computedStyle.color;
                
                if (tagName === 'h1' || tagName === 'h2' || tagName === 'h3' || 
                    tagName === 'h4' || tagName === 'h5' || tagName === 'h6') {
                    if (!element.style.color || currentColor === 'rgb(0, 0, 0)' || currentColor === 'rgb(17, 24, 39)') {
                        element.style.color = '#ffffff';
                    }
                } else if (tagName === 'p' || tagName === 'span' || (tagName === 'div' && !element.id.startsWith('component-'))) {
                    if (!element.style.color || currentColor === 'rgb(0, 0, 0)' || currentColor === 'rgb(17, 24, 39)') {
                        element.style.color = '#e5e7eb';
                    }
                } else if (tagName === 'a') {
                    if (!element.style.color || currentColor === 'rgb(0, 0, 0)' || currentColor === 'rgb(17, 24, 39)') {
                        element.style.color = '#60a5fa';
                    }
                }
                
                // Ajustar fundos claros
                const currentBg = computedStyle.backgroundColor;
                if (currentBg === 'rgb(255, 255, 255)' || currentBg === 'white' || 
                    element.style.backgroundColor === '#ffffff' || element.style.backgroundColor === 'white') {
                    if (!element.hasAttribute('data-original-bg')) {
                        element.setAttribute('data-original-bg', element.style.backgroundColor || 'white');
                    }
                    element.style.backgroundColor = '#1a1a1a';
                }
            }
        }
        
        // Função para selecionar um frame
        function selectFrame(frame) {
            // Remover seleção anterior
            document.querySelectorAll('.canvas-frame').forEach(f => {
                f.classList.remove('selected');
            });
            
            // Selecionar novo frame
            if (frame) {
                frame.classList.add('selected');
                selectedFrame = frame;
            }
        }
        
        // Função para renomear frame
        function renameFrame(frameId) {
            const frame = document.getElementById(frameId);
            if (!frame) return;
            
            const nameDisplay = frame.querySelector('.frame-name-display');
            if (!nameDisplay) return;
            
            const currentName = nameDisplay.textContent.trim();
            const newName = prompt('Digite o novo nome do frame:', currentName);
            
            if (newName !== null && newName.trim() !== '') {
                nameDisplay.textContent = newName.trim();
                frame.setAttribute('data-frame-name', newName.trim());
                saveHistory();
            }
        }
        
        // Função para tornar a malha quadriculada arrastável
        // Função de arrastar malha removida - apenas frames podem ser movidos
        function setupGridDragging() {
            // Função desabilitada - malha não é mais arrastável
            return;
            /*
            const infiniteContainer = document.getElementById('infiniteCanvasContainer');
            const framesContainer = document.getElementById('framesContainer');
            
            if (!infiniteContainer || !framesContainer || typeof interact === 'undefined') return;
            
            let startX = 0;
            let startY = 0;
            
            // Tornar o container arrastável apenas quando clicar em área vazia ou no corpo do frame
            interact(infiniteContainer).draggable({
                inertia: false,
                autoScroll: false,
                allowFrom: null,
                ignoreFrom: '[id^="component-"], .floating-toolbar, button, input, select, textarea, a, [contenteditable="true"], .frame-header',
                listeners: {
                    start: function(event) {
                        // Verificar se o clique foi em um componente ou elemento interativo
                        const target = event.target;
                        const isInteractive = target.closest('[id^="component-"]') ||
                                            target.closest('.floating-toolbar') ||
                                            target.closest('button') ||
                                            target.closest('input') ||
                                            target.closest('select') ||
                                            target.closest('textarea') ||
                                            target.closest('a') ||
                                            target.closest('[contenteditable="true"]') ||
                                            target.closest('.frame-header');
                        
                        // Se clicou em elementos interativos ou no header do frame, não arrastar a malha
                        if (isInteractive) {
                            return;
                        }
                        
                        // Se clicou no corpo de um frame (mas não no header), permitir arrastar a malha
                        // O drag do frame só funciona pelo header, então clicar no corpo deve mover a malha
                        
                        // Se clicou em um frame (mas não no header) ou em área vazia, permitir arrastar a malha
                        // Isso fará com que a malha e todos os frames se movam junto
                        // IMPORTANTE: Mesmo que o clique seja em um frame, vamos arrastar a malha
                        // porque o drag do frame só funciona pelo header (allowFrom: '.frame-header')
                        
                        isDraggingGrid = true;
                        infiniteContainer.style.cursor = 'grabbing';
                        
                        // Salvar posição original de todos os frames para poder movê-los junto com a malha
                        const allFrames = framesContainer.querySelectorAll('.canvas-frame');
                        allFrames.forEach(frame => {
                            const currentTop = parseFloat(frame.style.top) || 0;
                            const currentLeft = parseFloat(frame.style.left) || 0;
                            frame.setAttribute('data-grid-original-top', currentTop);
                            frame.setAttribute('data-grid-original-left', currentLeft);
                        });
                        
                        // Salvar posição inicial do background da malha
                        const computedStyle = window.getComputedStyle(infiniteContainer);
                        const bgPos = infiniteContainer.style.backgroundPosition || computedStyle.backgroundPosition || '0px 0px';
                        const match = bgPos.match(/(-?\d+\.?\d*)px\s+(-?\d+\.?\d*)px/);
                        if (match) {
                            gridOffsetX = parseFloat(match[1]) || 0;
                            gridOffsetY = parseFloat(match[2]) || 0;
                        } else {
                            gridOffsetX = 0;
                            gridOffsetY = 0;
                        }
                        
                        // Salvar posição inicial do framesContainer
                        // Verificar tanto o style inline quanto o computed style
                        let currentTransform = framesContainer.style.transform;
                        if (!currentTransform || currentTransform === 'none') {
                            const computedTransform = window.getComputedStyle(framesContainer).transform;
                            if (computedTransform && computedTransform !== 'none') {
                                // Extrair valores de matrix
                                const matrixMatch = computedTransform.match(/matrix\([^,]+,\s*[^,]+,\s*[^,]+,\s*[^,]+,\s*(-?\d+\.?\d*),\s*(-?\d+\.?\d*)\)/);
                                if (matrixMatch) {
                                    startX = parseFloat(matrixMatch[1]) || 0;
                                    startY = parseFloat(matrixMatch[2]) || 0;
                                } else {
                                    startX = 0;
                                    startY = 0;
                                }
                            } else {
                                startX = 0;
                                startY = 0;
                            }
                        } else {
                            // Extrair de translate
                            const transformMatch = currentTransform.match(/translate\((-?\d+\.?\d*)px,\s*(-?\d+\.?\d*)px\)/) ||
                                                 currentTransform.match(/translate3d\((-?\d+\.?\d*)px,\s*(-?\d+\.?\d*)px/);
                            if (transformMatch) {
                                startX = parseFloat(transformMatch[1]) || 0;
                                startY = parseFloat(transformMatch[2]) || 0;
                            } else {
                                // Tentar extrair de matrix
                                const matrixMatch = currentTransform.match(/matrix\([^,]+,\s*[^,]+,\s*[^,]+,\s*[^,]+,\s*(-?\d+\.?\d*),\s*(-?\d+\.?\d*)\)/);
                                if (matrixMatch) {
                                    startX = parseFloat(matrixMatch[1]) || 0;
                                    startY = parseFloat(matrixMatch[2]) || 0;
                                } else {
                                    startX = 0;
                                    startY = 0;
                                }
                            }
                        }
                    },
                    move: function(event) {
                        if (!isDraggingGrid) return;
                        
                        // Atualizar posição do background da malha (ela se move)
                        gridOffsetX += event.dx;
                        gridOffsetY += event.dy;
                        infiniteContainer.style.backgroundPosition = `${gridOffsetX}px ${gridOffsetY}px`;
                        
                        // Mover o framesContainer junto com a malha
                        // Os frames ficam "parados" dentro do container, mas se movem visualmente porque o container se move
                        // É como estar dentro de um carro - você está parado em relação ao carro, mas o carro se move
                        const newX = startX + event.dx;
                        const newY = startY + event.dy;
                        
                        // Aplicar transform no framesContainer para mover todos os frames junto
                        // IMPORTANTE: Os frames têm position: absolute dentro do framesContainer
                        // Quando o framesContainer se move com transform, os frames se movem junto automaticamente
                        // Usar translate3d para melhor performance e garantir que funcione
                        const transformValue = `translate3d(${newX}px, ${newY}px, 0px)`;
                        
                        // Aplicar transform de múltiplas formas para garantir compatibilidade
                        framesContainer.style.transform = transformValue;
                        framesContainer.style.webkitTransform = transformValue;
                        framesContainer.style.mozTransform = transformValue;
                        framesContainer.style.msTransform = transformValue;
                        framesContainer.style.oTransform = transformValue;
                        framesContainer.style.willChange = 'transform';
                        
                        // Mover os frames diretamente também como fallback
                        // Isso garante que mesmo se o transform do container não funcionar, os frames se movem
                        const allFrames = framesContainer.querySelectorAll('.canvas-frame');
                        allFrames.forEach(frame => {
                            // Só mover frames que não estão sendo arrastados individualmente
                            if (!frame.classList.contains('dragging')) {
                                // Obter posição original do frame (salva no início do drag)
                                const originalTop = parseFloat(frame.getAttribute('data-grid-original-top')) || parseFloat(frame.style.top) || 0;
                                const originalLeft = parseFloat(frame.getAttribute('data-grid-original-left')) || parseFloat(frame.style.left) || 0;
                                
                                // Aplicar offset da malha diretamente no frame
                                // O offset total é a diferença entre a posição inicial e a atual
                                const frameTop = originalTop + (newY - startY);
                                const frameLeft = originalLeft + (newX - startX);
                                
                                frame.style.top = frameTop + 'px';
                                frame.style.left = frameLeft + 'px';
                            }
                        });
                        
                        // Forçar reflow para garantir que o transform seja aplicado
                        void framesContainer.offsetHeight;
                        
                        // Se a malha ou os frames saírem dos limites do container, eles serão cortados pelo overflow: hidden
                    },
                    end: function(event) {
                        if (isDraggingGrid) {
                            isDraggingGrid = false;
                            infiniteContainer.style.cursor = 'grab';
                            if (framesContainer) {
                                framesContainer.style.willChange = '';
                            }
                        }
                    }
                }
            });
            */
        }
        
        // Função para obter o canvas ativo (do frame selecionado)
        function getActiveCanvas() {
            if (selectedFrame) {
                return selectedFrame.querySelector('.drop-zone');
            }
            // Se não houver frame selecionado, retornar null
            return null;
        }
        
        // Função para tornar frame arrastável
        function makeFrameDraggable(frame) {
            if (!frame || typeof interact === 'undefined') return;
            
            // Tornar o frame arrastável APENAS pelo header
            // Usar allowFrom para permitir apenas o header e ignoreFrom para ignorar o resto
            interact(frame).draggable({
                inertia: false,
                autoScroll: false,
                allowFrom: '.frame-header', // Só permitir arrastar pelo header do frame
                ignoreFrom: '.drop-zone, [id^="component-"], button, input, select, textarea, a, [contenteditable="true"]', // Ignorar tudo dentro do frame exceto o header
                listeners: {
                    start: function(event) {
                        // Verificar se o clique foi realmente no header
                        const target = event.target;
                        const clickedHeader = target.closest('.frame-header');
                        
                        // Se não foi no header, cancelar o drag do frame
                        if (!clickedHeader) {
                            event.stopPropagation();
                            return false; // Retornar false cancela o drag do frame
                        }
                        
                        // Se chegou aqui, é um drag pelo header - permitir arrastar o frame individualmente
                        // Prevenir que o container da malha capture este evento
                        event.stopPropagation();
                        frame.classList.add('dragging');
                        selectFrame(frame);
                    },
                    move: function(event) {
                        const target = event.target;
                        const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                        const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;
                        
                        target.style.transform = `translate(${x}px, ${y}px)`;
                        target.setAttribute('data-x', x);
                        target.setAttribute('data-y', y);
                    },
                    end: function(event) {
                        frame.classList.remove('dragging');
                        const target = event.target;
                        const x = parseFloat(target.getAttribute('data-x')) || 0;
                        const y = parseFloat(target.getAttribute('data-y')) || 0;
                        
                        // Atualizar posição absoluta
                        const currentTop = parseFloat(target.style.top) || 0;
                        const currentLeft = parseFloat(target.style.left) || 0;
                        target.style.top = (currentTop + y) + 'px';
                        target.style.left = (currentLeft + x) + 'px';
                        target.style.transform = '';
                        target.setAttribute('data-x', 0);
                        target.setAttribute('data-y', 0);
                        
                        saveHistory();
                    }
                }
            });
        }
        
        // Template para importar (vindo do mail.php)
        const templateToImport = <?php echo $templateFromMail ? json_encode($templateFromMail, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) : 'null'; ?>;
        
        // Dados do projeto para referência (vindo do mail.php)
        const projectDataFromMail = <?php echo $projectDataFromMail ? json_encode($projectDataFromMail, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) : 'null'; ?>;
        
        // Exibir dados do projeto no console para referência (se disponível)
        if (projectDataFromMail) {
            console.log('Dados do projeto disponíveis:', projectDataFromMail);
            // Você pode usar esses dados aqui como referência:
            // - projectDataFromMail.project_name
            // - projectDataFromMail.sender_email
            // - projectDataFromMail.sender_name
            // - projectDataFromMail.email_function
        }
        
        // Função para importar template do Mail
        function importTemplateFromMail() {
            if (!templateToImport) {
                alert('Nenhum template disponível para importar');
                return;
            }
            
            // Criar um novo frame se não houver nenhum
            if (!selectedFrame) {
                addNewFrame();
                // Aguardar frame ser criado
                setTimeout(() => {
                    importTemplateContent();
                }, 300);
            } else {
                importTemplateContent();
            }
        }
        
        // Função para importar o conteúdo do template
        function importTemplateContent() {
            if (!templateToImport) return;
            
            const canvas = getActiveCanvas();
            if (!canvas) {
                alert('Selecione ou crie um frame primeiro');
                return;
            }
            
            // Limpar canvas atual
            canvas.innerHTML = '';
            
            // Criar um elemento temporário para parsear o HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = templateToImport;
            
            // Extrair o body do HTML ou usar o conteúdo direto
            let content = tempDiv.innerHTML;
            
            // Se o template tiver body, pegar apenas o conteúdo interno
            const bodyElement = tempDiv.querySelector('body');
            if (bodyElement) {
                content = bodyElement.innerHTML;
            }
            
            // Adicionar o conteúdo ao canvas
            canvas.innerHTML = content;
            
            // Processar todos os elementos adicionados para torná-los interativos
            const allElements = canvas.querySelectorAll('*');
            allElements.forEach(element => {
                // Remover classes de editor se houver
                element.classList.remove('component-hover', 'component-selected');
                
                // Adicionar classe para tornar interativo
                if (element.tagName !== 'SCRIPT' && element.tagName !== 'STYLE' && !element.closest('script, style')) {
                    element.classList.add('component-hover');
                    setupElementInteractions(element);
                }
            });
            
            // Salvar no histórico
            saveHistory();
            
            // Mostrar mensagem de sucesso
            alert('Template importado com sucesso!');
        }
        
        // Inicialização - sem frame padrão
        document.addEventListener('DOMContentLoaded', function() {
            // Não há frame inicial, usuário deve criar um clicando em "Novo Frame"
            // Atualizar visibilidade da imagem de fundo
            setTimeout(() => {
                updateEmptyCanvasImage();
            }, 100);
            
            // Função de arrastar malha removida - apenas frames podem ser movidos
            // setupGridDragging();
            
            // Adicionar listeners de mudança para atualização em tempo real
            setupRealTimeUpdateListeners();
            
            // Se houver template para importar, mostrar notificação
            if (templateToImport) {
                // Criar notificação visual
                setTimeout(() => {
                    const notification = document.createElement('div');
                    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #3b82f6; color: white; padding: 12px 20px; border-radius: 8px; z-index: 10000; box-shadow: 0 4px 12px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 10px; max-width: 400px;';
                    notification.innerHTML = `
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span style="flex: 1;">Template disponível para importar! Clique em "Importar Template" na barra de ferramentas.</span>
                        <button onclick="this.parentElement.remove()" style="margin-left: 10px; opacity: 0.8; cursor: pointer; background: none; border: none; color: white; font-size: 18px; line-height: 1;">✕</button>
                    `;
                    document.body.appendChild(notification);
                    
                    // Remover notificação após 10 segundos
                    setTimeout(() => {
                        if (notification.parentElement) {
                            notification.remove();
                        }
                    }, 10000);
                }, 500);
            }
            
            // Atualizar posição da imagem ao redimensionar a janela
            window.addEventListener('resize', function() {
                updateEmptyCanvasImage();
            });
            
            // Atalhos de teclado
            document.addEventListener('keydown', function(e) {
                // Prevenir atalhos padrão quando estiver editando texto
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
                    return;
                }
                
                // Ctrl/Cmd + teclas
                if (e.ctrlKey || e.metaKey) {
                    switch(e.key.toLowerCase()) {
                        case 'n':
                            e.preventDefault();
                            addNewFrame();
                            break;
                        case 's':
                            e.preventDefault();
                            saveHistory();
                            break;
                        case 'z':
                            e.preventDefault();
                            if (e.shiftKey) {
                                redo();
                            } else {
                                undo();
                            }
                            break;
                        case 'y':
                            e.preventDefault();
                            redo();
                            break;
                        case 'd':
                            e.preventDefault();
                            if (selectedElement) {
                                const clone = selectedElement.cloneNode(true);
                                selectedElement.parentNode.insertBefore(clone, selectedElement.nextSibling);
                                setupElementInteractions(clone);
                                selectElement(clone);
                                saveHistory();
                            }
                            break;
                        case 'delete':
                        case 'backspace':
                            e.preventDefault();
                            if (selectedElements.length > 0) {
                                deleteSelectedElements();
                            } else if (selectedElement) {
                                selectedElement.remove();
                                selectedElement = null;
                                saveHistory();
                            }
                            break;
                        case 'g':
                            e.preventDefault();
                            if (selectedElements.length >= 2) {
                                groupSelectedElements();
                            }
                            break;
                        case 'a':
                            e.preventDefault();
                            // Selecionar todos os elementos do frame atual
                            const canvas = getActiveCanvas();
                            if (canvas) {
                                const allElements = canvas.querySelectorAll('[id^="component-"]');
                                allElements.forEach(el => {
                                    if (!el.classList.contains('component-selected')) {
                                        selectElement(el, true);
                                    }
                                });
                            }
                            break;
                    }
                } else {
                    // Atalhos sem Ctrl
                    switch(e.key.toLowerCase()) {
                        case 'delete':
                        case 'backspace':
                            if (selectedElements.length > 0) {
                                e.preventDefault();
                                deleteSelectedElements();
                            } else if (selectedElement) {
                                e.preventDefault();
                                selectedElement.remove();
                                selectedElement = null;
                                saveHistory();
                            }
                            break;
                        case 'escape':
                            // Deselecionar todos
                            document.querySelectorAll('.component-selected').forEach(el => {
                                el.classList.remove('component-selected');
                            });
                            selectedElements = [];
                            selectedElement = null;
                            document.getElementById('propertiesPanel').innerHTML = 
                                '<div class="p-6 text-center"><svg class="w-10 h-10 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076-.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><div class="text-xs font-medium mb-1" style="color: var(--text-muted);">Nenhum elemento selecionado</div><div class="text-[10px]" style="color: var(--text-muted); opacity: 0.6;">Clique em um componente para editar</div></div>';
                            break;
                    }
                }
            });
        });

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
                alert('Por favor, crie um frame primeiro clicando em "Novo Frame" na toolbar.');
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
                const html = `<img id="${id}" src="${imageUrl}" alt="Imagem colada" class="component-hover draggable cursor-move rounded-md border border-gray-200" data-type="image" data-x="${startX}" data-y="${startY}" style="position: absolute; left: ${startX}px; top: ${startY}px; display: block; width: ${imgWidth}px; height: ${imgHeight}px; max-width: 100%; touch-action: none; user-select: none; object-fit: cover; object-position: center;">`;
                
                // Remover placeholder se existir
                const placeholder = canvas.querySelector('[id^="canvas-placeholder"]');
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
                        autoScroll: false, // Desabilitar para melhor performance
                        allowFrom: null, // Permitir drag de qualquer parte
                        ignoreFrom: null, // Não ignorar nenhuma parte
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
                                // Otimizações de performance
                                target.style.willChange = 'transform';
                                target.style.zIndex = '1000';
                                target.style.cursor = 'grabbing';
                            },
                            move: dragMoveListener,
                            end: function(event) {
                                const target = event.target;
                                // Limpar transform, cache e cancelar animações pendentes
                                if (rafId !== null) {
                                    cancelAnimationFrame(rafId);
                                    rafId = null;
                                }
                                target.style.transform = '';
                                target.style.willChange = '';
                                target.style.boxShadow = ''; // Remover qualquer sombra residual
                                positionCache.delete(target);
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
                                
                                // Para imagens, garantir que preencham o espaço sem rebarbas
                                if (target.tagName === 'IMG') {
                                    target.style.objectFit = 'cover';
                                    target.style.objectPosition = 'center';
                                }
                                
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
                            // Verificar se Ctrl está pressionado para seleção múltipla
                            const isMultiSelect = e.ctrlKey || e.metaKey;
                            selectElement(this, isMultiSelect);
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
            
            // Auto-save para localStorage
            autoSave();
        }
        
        // Sistema de Auto-Save para proteger o progresso do usuário
        const AUTO_SAVE_KEY = 'safefig_autosave';
        const AUTO_SAVE_INTERVAL = 30000; // 30 segundos
        let autoSaveTimer = null;
        let lastSaveTime = null;
        let isSaving = false;
        
        // Função para salvar o estado atual
        function autoSave() {
            if (isSaving) return; // Evitar múltiplos saves simultâneos
            isSaving = true;
            
            try {
                const desktopCanvas = document.getElementById('emailCanvas');
                const mobileCanvas = document.getElementById('emailCanvasMobile');
                
                if (!desktopCanvas || !mobileCanvas) {
                    isSaving = false;
                    return;
                }
                
                // Verificar se há conteúdo para salvar
                const desktopContent = desktopCanvas.innerHTML.trim();
                const mobileContent = mobileCanvas.innerHTML.trim();
                
                // Remover placeholders do conteúdo
                const cleanDesktop = desktopContent.replace(/id="canvas-placeholder[^"]*"/g, '');
                const cleanMobile = mobileContent.replace(/id="canvas-placeholder-mobile[^"]*"/g, '');
                
                // Só salvar se houver conteúdo real (não apenas placeholders)
                if (cleanDesktop === '' && cleanMobile === '') {
                    isSaving = false;
                    return;
                }
                
                const saveData = {
                    desktop: desktopContent,
                    mobile: mobileContent,
                    componentCounter: componentCounter,
                    timestamp: Date.now(),
                    historyIndex: historyIndex,
                    history: history.slice() // Copiar array de histórico
                };
                
                localStorage.setItem(AUTO_SAVE_KEY, JSON.stringify(saveData));
                lastSaveTime = Date.now();
                
                // Atualizar indicador visual
                updateSaveIndicator(true);
                
                isSaving = false;
            } catch (e) {
                console.warn('Erro ao salvar automaticamente:', e);
                isSaving = false;
            }
        }
        
        // Função para restaurar o estado salvo
        function restoreAutoSave() {
            try {
                const savedData = localStorage.getItem(AUTO_SAVE_KEY);
                if (!savedData) {
                    console.log('Nenhum dado salvo encontrado');
                    return false;
                }
                
                const data = JSON.parse(savedData);
                const desktopCanvas = document.getElementById('emailCanvas');
                const mobileCanvas = document.getElementById('emailCanvasMobile');
                
                if (!desktopCanvas || !mobileCanvas) {
                    console.log('Canvas não encontrado para restaurar');
                    return false;
                }
                
                // Verificar se há dados válidos
                if (!data.desktop && !data.mobile) {
                    console.log('Dados salvos estão vazios');
                    return false;
                }
                
                // Verificar se o canvas atual está vazio (só placeholder)
                const currentDesktop = desktopCanvas.innerHTML.trim();
                const currentMobile = mobileCanvas.innerHTML.trim();
                const hasOnlyPlaceholder = currentDesktop.includes('canvas-placeholder') && 
                                         currentMobile.includes('canvas-placeholder-mobile') &&
                                         desktopCanvas.querySelectorAll('[id^="component-"]').length === 0;
                
                // Só restaurar se o canvas estiver vazio ou se houver dados salvos válidos
                if (!hasOnlyPlaceholder && currentDesktop !== '' && currentMobile !== '') {
                    // Já tem conteúdo, não restaurar automaticamente
                    console.log('Canvas já tem conteúdo, não restaurando automaticamente');
                    return false;
                }
                
                // Restaurar conteúdo
                if (data.desktop) {
                    desktopCanvas.innerHTML = data.desktop;
                }
                if (data.mobile) {
                    mobileCanvas.innerHTML = data.mobile;
                }
                
                // Restaurar contador e histórico
                if (data.componentCounter !== undefined) {
                    componentCounter = data.componentCounter;
                }
                if (data.history && data.history.length > 0) {
                    history = data.history;
                    historyIndex = data.historyIndex !== undefined ? data.historyIndex : history.length - 1;
                } else {
                    // Se não tem histórico, criar um novo
                    history = [];
                    historyIndex = -1;
                    saveHistory();
                }
                
                // Aguardar um pouco antes de reanexar listeners (garantir que DOM está pronto)
                setTimeout(() => {
                    // Reanexar event listeners
                    reattachEventListeners();
                    
                    // Atualizar painel de layers
                    updateLayersPanel();
                    
                    // Mostrar notificação de restauração
                    if (data.timestamp) {
                        showRestoreNotification(data.timestamp);
                    }
                }, 100);
                
                console.log('Progresso restaurado com sucesso');
                return true;
            } catch (e) {
                console.error('Erro ao restaurar salvamento automático:', e);
                return false;
            }
        }
        
        // Função para limpar o auto-save
        function clearAutoSave() {
            try {
                localStorage.removeItem(AUTO_SAVE_KEY);
                updateSaveIndicator(false);
            } catch (e) {
                console.warn('Erro ao limpar salvamento automático:', e);
            }
        }
        
        // Indicador visual de salvamento
        function updateSaveIndicator(saved) {
            let indicator = document.getElementById('autosave-indicator');
            if (!indicator) {
                // Criar indicador se não existir
                indicator = document.createElement('div');
                indicator.id = 'autosave-indicator';
                indicator.style.cssText = 'position: fixed; bottom: 20px; right: 20px; padding: 8px 16px; background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: 6px; font-size: 11px; color: var(--text-secondary); z-index: 10000; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; opacity: 0; pointer-events: none;';
                document.body.appendChild(indicator);
            }
            
            if (saved) {
                indicator.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>Salvo automaticamente</span>';
                indicator.style.color = 'var(--text-secondary)';
                indicator.style.opacity = '1';
                
                // Esconder após 2 segundos
                setTimeout(() => {
                    if (indicator) {
                        indicator.style.opacity = '0';
                    }
                }, 2000);
            } else {
                indicator.style.opacity = '0';
            }
        }
        
        // Notificação de restauração
        function showRestoreNotification(timestamp) {
            const savedTime = new Date(timestamp);
            const timeAgo = Math.floor((Date.now() - timestamp) / 1000 / 60); // minutos
            
            let notification = document.createElement('div');
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 16px 20px; background: var(--bg-card); border: 1px solid var(--border-light); border-radius: 8px; font-size: 12px; color: var(--text-primary); z-index: 10001; max-width: 300px; box-shadow: var(--shadow-lg);';
            notification.innerHTML = `
                <div style="display: flex; align-items: start; gap: 12px;">
                    <svg class="w-5 h-5 flex-shrink-0" style="color: var(--accent); margin-top: 2px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 4px;">Progresso restaurado</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Seu trabalho foi restaurado automaticamente${timeAgo > 0 ? ` (${timeAgo} min atrás)` : ''}</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" style="margin-left: auto; padding: 4px; opacity: 0.6; cursor: pointer; border: none; background: none; color: var(--text-secondary);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Remover após 5 segundos
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.opacity = '0';
                    notification.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 5000);
        }
        
        // Iniciar auto-save periódico
        function startAutoSave() {
            // Limpar timer anterior se existir
            if (autoSaveTimer) {
                clearInterval(autoSaveTimer);
            }
            
            // Salvar a cada X segundos
            autoSaveTimer = setInterval(() => {
                const desktopCanvas = document.getElementById('emailCanvas');
                const mobileCanvas = document.getElementById('emailCanvasMobile');
                
                // Verificar se há conteúdo real (não apenas placeholder)
                if (desktopCanvas && mobileCanvas) {
                    const hasComponents = desktopCanvas.querySelectorAll('[id^="component-"]').length > 0 ||
                                        mobileCanvas.querySelectorAll('[id^="component-"]').length > 0;
                    
                    if (hasComponents) {
                        autoSave();
                    }
                }
            }, AUTO_SAVE_INTERVAL);
        }
        
        // Parar auto-save
        function stopAutoSave() {
            if (autoSaveTimer) {
                clearInterval(autoSaveTimer);
                autoSaveTimer = null;
            }
        }
        
        // Verificar se há alterações não salvas
        let hasUnsavedChanges = false;
        let lastSavedContent = '';
        
        // Função para verificar se há mudanças
        function checkForChanges() {
            const desktopCanvas = document.getElementById('emailCanvas');
            const mobileCanvas = document.getElementById('emailCanvasMobile');
            
            if (!desktopCanvas || !mobileCanvas) return false;
            
            const currentContent = desktopCanvas.innerHTML + mobileCanvas.innerHTML;
            if (currentContent !== lastSavedContent) {
                hasUnsavedChanges = true;
                return true;
            }
            return false;
        }
        
        // Marcar quando há alterações
        const originalSaveHistory = saveHistory;
        saveHistory = function() {
            originalSaveHistory();
            hasUnsavedChanges = true;
            
            // Atualizar conteúdo salvo após salvar
            setTimeout(() => {
                const desktopCanvas = document.getElementById('emailCanvas');
                const mobileCanvas = document.getElementById('emailCanvasMobile');
                if (desktopCanvas && mobileCanvas) {
                    lastSavedContent = desktopCanvas.innerHTML + mobileCanvas.innerHTML;
                    hasUnsavedChanges = false;
                }
            }, 100);
        };
        
        // Salvar antes de fechar/recarregar com confirmação
        window.addEventListener('beforeunload', function(e) {
            // Verificar se há mudanças
            checkForChanges();
            
            // Salvar sempre antes de sair
            autoSave();
            
            // Se houver alterações, perguntar ao usuário
            if (hasUnsavedChanges) {
                // Mensagem padrão do navegador
                e.preventDefault();
                e.returnValue = 'Você tem alterações não salvas. Tem certeza que deseja sair? Seu progresso será salvo automaticamente.';
                return e.returnValue;
            }
        });
        
        // Salvar quando a página perde foco (suspensão)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Página foi suspensa ou minimizada
                autoSave();
            }
        });
        
        // Salvar quando a janela perde foco
        window.addEventListener('blur', function() {
            autoSave();
        });
        
        // Função de inicialização do auto-save
        function initAutoSave() {
            // Aguardar um pouco para garantir que tudo está carregado
            setTimeout(() => {
                // Restaurar salvamento anterior
                const restored = restoreAutoSave();
                
                if (restored) {
                    // Atualizar conteúdo salvo após restaurar
                    const desktopCanvas = document.getElementById('emailCanvas');
                    const mobileCanvas = document.getElementById('emailCanvasMobile');
                    if (desktopCanvas && mobileCanvas) {
                        lastSavedContent = desktopCanvas.innerHTML + mobileCanvas.innerHTML;
                        hasUnsavedChanges = false;
                    }
                }
                
                // Iniciar auto-save periódico
                startAutoSave();
            }, 300);
        }
        
        // Inicializar auto-save quando o DOM estiver pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAutoSave);
        } else {
            // DOM já está pronto, inicializar imediatamente
            initAutoSave();
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
                    autoScroll: false, // Desabilitar para melhor performance
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
                            // Otimizações de performance
                            target.style.willChange = 'transform';
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
                    // Verificar se Ctrl está pressionado para seleção múltipla
                    const isMultiSelect = e.ctrlKey || e.metaKey;
                    selectElement(this, isMultiSelect);
                });
                
                // Re-setup auto font size for text elements
                setupAutoFontSize(el);
            });
        }

        // Variável para armazenar o tipo de mobile selecionado
        let mobileType = 'ios'; // 'ios' ou 'android'
        
        // Função para mostrar opções mobile
        function showMobileOptions() {
            const dropdown = document.getElementById('mobileOptionsDropdown');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }
        
        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('mobileOptionsDropdown');
            const btn = document.getElementById('btn-mobile');
            if (dropdown && btn && !dropdown.contains(e.target) && !btn.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
        
        function setCanvasSize(size, type) {
            let canvas = getActiveCanvas();
            
            // Se não houver canvas, criar um frame automaticamente
            if (!canvas) {
                const framesContainer = document.getElementById('framesContainer');
                const existingFrames = framesContainer ? framesContainer.querySelectorAll('.canvas-frame') : [];
                
                if (existingFrames.length === 0) {
                    // Criar frame automaticamente
                    addNewFrame();
                    // Aguardar um pouco para o frame ser criado
                    setTimeout(() => {
                        canvas = getActiveCanvas();
                        if (canvas) {
                            // Aplicar o tamanho após criar o frame
                            applyCanvasSize(size, type, canvas);
                        }
                    }, 50);
                    return;
                } else {
                    alert('Por favor, selecione um frame primeiro.');
                    return;
                }
            }
            
            applyCanvasSize(size, type, canvas);
        }
        
        // Função auxiliar para aplicar o tamanho do canvas
        function applyCanvasSize(size, type, canvas) {
            // Fechar dropdown mobile se estiver aberto
            const dropdown = document.getElementById('mobileOptionsDropdown');
            if (dropdown) {
                dropdown.classList.add('hidden');
            }
            
            document.querySelectorAll('[id^="btn-"]').forEach(btn => btn.classList.remove('active'));
            
            if (size === 'desktop') {
                // Remover carcaça de celular se existir
                removePhoneFrame();
                canvas.style.width = '900px';
                canvas.style.minWidth = '900px';
                canvas.style.height = 'auto';
                canvas.style.minHeight = '600px';
                document.getElementById('btn-desktop').classList.add('active');
                
                // Atualizar largura do frame
                if (selectedFrame) {
                    selectedFrame.style.width = '900px';
                    selectedFrame.style.maxWidth = '900px';
                    selectedFrame.style.height = 'auto';
                    selectedFrame.style.minHeight = '600px';
                }
            } else if (size === 'mobile') {
                mobileType = type || 'ios';
                // Aplicar carcaça de celular
                applyPhoneFrame(mobileType);
                document.getElementById('btn-mobile').classList.add('active');
                
                // Atualizar largura do frame para mobile
                if (selectedFrame) {
                    selectedFrame.style.width = '435px'; // 375px + padding
                    selectedFrame.style.maxWidth = '435px';
                    selectedFrame.style.height = 'auto';
                    selectedFrame.style.minHeight = '812px';
                }
            }
            
            saveHistory();
        }
        
        // Função para aplicar carcaça de celular
        function applyPhoneFrame(type) {
            if (!selectedFrame) return;
            
            // Remover carcaça anterior se existir
            removePhoneFrame();
            
            const canvas = getActiveCanvas();
            if (!canvas) return;
            
            // Verificar se já tem carcaça
            if (canvas.closest('.phone-frame-wrapper')) {
                return;
            }
            
            // Criar wrapper da carcaça
            const phoneWrapper = document.createElement('div');
            phoneWrapper.className = 'phone-frame-wrapper';
            phoneWrapper.setAttribute('data-phone-type', type);
            phoneWrapper.style.cssText = 'position: relative; width: 375px; max-width: 375px; padding: 30px 20px; margin: 0 auto; border: none; outline: none;';
            
            // Criar carcaça do celular com design realista
            const phoneFrame = document.createElement('div');
            phoneFrame.className = 'phone-frame';
            
            if (type === 'ios') {
                // iPhone com design mais realista
                phoneFrame.style.cssText = `
                    position: relative;
                    width: 375px;
                    height: 812px;
                    margin: 0 auto;
                    border-radius: 3.5rem;
                    padding: 8px;
                    background: linear-gradient(145deg, #1a1a1a 0%, #0a0a0a 30%, #1f1f1f 60%, #0d0d0d 100%);
                    box-shadow: 
                        0 30px 100px rgba(0,0,0,0.9),
                        inset 0 1px 0 rgba(255,255,255,0.08),
                        inset 0 -1px 0 rgba(0,0,0,0.5);
                    border: none;
                `;
            } else {
                // Android com design mais realista
                phoneFrame.style.cssText = `
                    position: relative;
                    width: 375px;
                    height: 812px;
                    margin: 0 auto;
                    border-radius: 2rem;
                    padding: 10px;
                    background: linear-gradient(145deg, #2d2d2d 0%, #1a1a1a 30%, #252525 60%, #1f1f1f 100%);
                    box-shadow: 
                        0 30px 100px rgba(0,0,0,0.9),
                        inset 0 1px 0 rgba(255,255,255,0.08),
                        inset 0 -1px 0 rgba(0,0,0,0.5);
                    border: none;
                `;
            }
            
            // Tela do celular
            const phoneScreen = document.createElement('div');
            phoneScreen.className = 'phone-screen';
            phoneScreen.style.cssText = `
                border-radius: ${type === 'ios' ? '3rem' : '1.75rem'};
                overflow: hidden;
                position: relative;
                width: 100%;
                height: 100%;
                background: #000;
                box-shadow: inset 0 0 30px rgba(0,0,0,0.5);
                border: none;
            `;
            
            // Elementos específicos do iOS
            if (type === 'ios') {
                // Dynamic Island (iPhone 14 Pro style)
                const dynamicIsland = document.createElement('div');
                dynamicIsland.style.cssText = `
                    position: absolute;
                    top: 12px;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 126px;
                    height: 37px;
                    z-index: 30;
                    pointer-events: none;
                `;
                dynamicIsland.innerHTML = `
                    <div style="
                        width: 100%;
                        height: 100%;
                        border-radius: 20px;
                        background: #000;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.5);
                        position: relative;
                    ">
                        <div style="
                            position: absolute;
                            top: 50%;
                            left: 50%;
                            transform: translate(-50%, -50%);
                            width: 60px;
                            height: 6px;
                            border-radius: 999px;
                            background: rgba(255,255,255,0.2);
                        "></div>
                        <div style="
                            position: absolute;
                            top: 50%;
                            right: 16px;
                            transform: translateY(-50%);
                            width: 8px;
                            height: 8px;
                            border-radius: 50%;
                            background: rgba(255,255,255,0.25);
                            border: 1px solid rgba(255,255,255,0.15);
                        "></div>
                    </div>
                `;
                phoneScreen.appendChild(dynamicIsland);
                
                // Home indicator (barra inferior)
                const homeIndicator = document.createElement('div');
                homeIndicator.style.cssText = `
                    position: absolute;
                    bottom: 8px;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 134px;
                    height: 5px;
                    z-index: 20;
                    pointer-events: none;
                `;
                homeIndicator.innerHTML = `
                    <div style="
                        width: 100%;
                        height: 100%;
                        border-radius: 999px;
                        background: rgba(255,255,255,0.5);
                        backdrop-filter: blur(20px);
                        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
                    "></div>
                `;
                phoneScreen.appendChild(homeIndicator);
            } else {
                // Barra de status Android (Material Design)
                const statusBar = document.createElement('div');
                statusBar.style.cssText = `
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 28px;
                    background: linear-gradient(180deg, rgba(0,0,0,0.3) 0%, transparent 100%);
                    z-index: 20;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 0 16px;
                    font-size: 12px;
                    color: rgba(255,255,255,0.95);
                    font-weight: 600;
                    pointer-events: none;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                `;
                statusBar.innerHTML = `
                    <span style="font-weight: 600;">9:41</span>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M5 12.55a11 11 0 0 1 14.08 0M1.42 9a16 16 0 0 1 21.16 0M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01"/>
                        </svg>
                        <div style="
                            width: 24px;
                            height: 12px;
                            border: 2px solid currentColor;
                            border-radius: 3px;
                            padding: 1.5px;
                            display: flex;
                            align-items: center;
                        ">
                            <div style="
                                width: 80%;
                                height: 100%;
                                background: currentColor;
                                border-radius: 1px;
                            "></div>
                        </div>
                    </div>
                `;
                phoneScreen.appendChild(statusBar);
                
                // Barra de navegação Android (opcional, pode ser removida se preferir gestos)
                // Não vou adicionar para manter mais limpo e moderno
            }
            
            // Ajustar estilos do canvas
            canvas.style.width = '100%';
            canvas.style.height = '100%';
            canvas.style.minHeight = '100%';
            canvas.style.maxHeight = '100%';
            canvas.style.margin = '0';
            canvas.style.borderRadius = '0';
            canvas.style.boxShadow = 'none';
            canvas.style.backgroundColor = '#fff';
            canvas.style.position = 'relative';
            canvas.style.zIndex = '1';
            
            // Mover canvas para dentro da tela
            const canvasParent = canvas.parentElement;
            canvasParent.removeChild(canvas);
            phoneScreen.appendChild(canvas);
            
            phoneFrame.appendChild(phoneScreen);
            phoneWrapper.appendChild(phoneFrame);
            
            // Adicionar wrapper ao frame (no lugar onde estava o canvas)
            canvasParent.appendChild(phoneWrapper);
            
            // Ajustar altura do frame para corresponder ao celular
            if (selectedFrame) {
                selectedFrame.style.width = '375px';
                selectedFrame.style.height = '812px';
                selectedFrame.style.minHeight = '812px';
            }
        }
        
        // Função para remover carcaça de celular
        function removePhoneFrame() {
            if (!selectedFrame) return;
            
            const phoneWrapper = selectedFrame.querySelector('.phone-frame-wrapper');
            if (phoneWrapper) {
                const phoneScreen = phoneWrapper.querySelector('.phone-screen');
                const canvas = phoneScreen ? phoneScreen.querySelector('.drop-zone') : null;
                
                if (canvas) {
                    // Restaurar estilos do canvas
                    canvas.style.width = '100%';
                    canvas.style.height = 'auto';
                    canvas.style.minHeight = '600px';
                    canvas.style.maxHeight = 'none';
                    canvas.style.margin = '0';
                    canvas.style.borderRadius = '8px';
                    
                    // Restaurar tamanho do frame para desktop
                    selectedFrame.style.width = '900px';
                    selectedFrame.style.maxWidth = '900px';
                    canvas.style.boxShadow = '0 4px 24px rgba(0,0,0,0.15)';
                    canvas.style.position = 'relative';
                    canvas.style.zIndex = 'auto';
                    canvas.style.backgroundColor = '#fff';
                    
                    // Mover canvas de volta para o frame (onde estava o phoneWrapper)
                    phoneScreen.removeChild(canvas);
                    const frameContent = phoneWrapper.parentElement;
                    if (frameContent) {
                        frameContent.appendChild(canvas);
                    }
                }
                phoneWrapper.remove();
                
                // Restaurar dimensões do frame
                if (selectedFrame) {
                    selectedFrame.style.width = '900px';
                    selectedFrame.style.height = 'auto';
                    selectedFrame.style.minHeight = '600px';
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
            const canvas = getActiveCanvas();
            if (!canvas) {
                closeCustomSize();
                return;
            }
            
            const widthNum = parseInt(width);
            if (isNaN(widthNum) || widthNum < 300 || widthNum > 2000) {
                alert('Por favor, insira uma largura entre 300 e 2000 pixels.');
                return;
            }
            
            canvas.style.width = widthNum + 'px';
            canvas.style.minWidth = widthNum + 'px';
            
            // Atualizar largura do frame também
            if (selectedFrame) {
                selectedFrame.style.width = widthNum + 'px';
            }
            
            document.querySelectorAll('[id^="btn-"]').forEach(btn => btn.classList.remove('active'));
            document.getElementById('btn-custom').classList.add('active');
            
            closeCustomSize();
            saveHistory();
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
                    autoScroll: false, // Desabilitar para melhor performance
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
                            // Otimizações de performance
                            target.style.willChange = 'transform';
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
                            target.style.boxShadow = ''; // Remover qualquer sombra residual
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

        // Sync canvas content (atualizado para trabalhar com frames)
        function syncCanvasContent() {
            // Esta função não é mais necessária com a nova estrutura de frames
            // Cada frame tem seu próprio canvas independente
        }

        // Add component to canvas
        function addComponent(type) {
            const canvas = getActiveCanvas();
            if (!canvas) {
                alert('Por favor, crie um frame primeiro clicando em "Novo Frame" na toolbar.');
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
                    html = `<img id="${id}" src="https://placehold.co/600x300/e2e8f0/64748b?text=Sua+Imagem" alt="Imagem" class="component-hover draggable cursor-move rounded-md border border-gray-200" data-type="image" data-x="${initialX}" data-y="${initialY}" style="position: absolute; left: ${initialX}px; top: ${initialY}px; display: block; width: ${initialWidth}px; height: ${initialWidth * 0.5}px; max-width: 100%; touch-action: none; user-select: none; object-fit: cover; object-position: center;">`;
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
            const placeholder = canvas.querySelector('[id^="canvas-placeholder"]');
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
            
            // Aplicar modo escuro se o frame estiver em modo escuro
            applyDarkModeToNewElement(newElement);
            
            // Make draggable - sem restrição lateral, apenas vertical
            // Make draggable - configuração simplificada sem modificadores que podem interferir
            interact(`#${id}`).draggable({
                inertia: false,
                autoScroll: false, // Desabilitar para melhor performance
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
                        // Otimizações de performance
                        target.style.willChange = 'transform';
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
                        // Limpar transform, cache e cancelar animações pendentes
                        if (rafId !== null) {
                            cancelAnimationFrame(rafId);
                            rafId = null;
                        }
                        target.style.transform = '';
                        target.style.willChange = '';
                        target.style.boxShadow = ''; // Remover qualquer sombra residual
                        positionCache.delete(target);
                        target.classList.remove('component-dragging');
                        target.style.zIndex = '';
                        target.style.boxShadow = ''; // Remover qualquer sombra residual
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

        // Variáveis para otimização de performance
        let lastToolbarUpdate = 0;
        const TOOLBAR_UPDATE_INTERVAL = 100; // Atualizar toolbar a cada 100ms
        let rafId = null;
        let pendingUpdate = null;
        
        // Cache de posições para evitar recálculos
        const positionCache = new WeakMap();
        
        function dragMoveListener(event) {
            const target = event.target;
            
            // Cancelar qualquer atualização pendente
            if (rafId !== null) {
                cancelAnimationFrame(rafId);
            }
            
            // Garantir que está usando position absolute
            if (target.style.position !== 'absolute') {
                target.style.position = 'absolute';
            }
            
            // Obter posição atual do cache ou calcular
            let cached = positionCache.get(target);
            if (!cached) {
                let currentX = parseFloat(target.getAttribute('data-x'));
                let currentY = parseFloat(target.getAttribute('data-y'));
                
                if (isNaN(currentX)) {
                    currentX = parseFloat(target.style.left) || 0;
                }
                if (isNaN(currentY)) {
                    currentY = parseFloat(target.style.top) || 0;
                }
                
                cached = { x: currentX, y: currentY };
                positionCache.set(target, cached);
            }
            
            // Calcular nova posição baseada no movimento
            const x = cached.x + event.dx;
            const y = cached.y + event.dy;
            
            // Usar requestAnimationFrame para atualizações suaves
            pendingUpdate = { target, x, y };
            
            rafId = requestAnimationFrame(() => {
                if (!pendingUpdate) return;
                
                const { target, x, y } = pendingUpdate;
                
                // Obter posição anterior do cache
                const prevX = cached.x;
                const prevY = cached.y;
                
                // Calcular offset para transform suave
                const offsetX = x - prevX;
                const offsetY = y - prevY;
                
                // Sempre usar transform3d para máxima fluidez (GPU acceleration)
                // Usar translate3d com Z=0 força aceleração por hardware
                target.style.transform = `translate3d(${offsetX}px, ${offsetY}px, 0)`;
                target.style.willChange = 'transform';
                
                // Atualizar posição absoluta imediatamente
                target.style.left = x + 'px';
                target.style.top = y + 'px';
                target.setAttribute('data-x', x);
                target.setAttribute('data-y', y);
                
                // Atualizar cache
                cached.x = x;
                cached.y = y;
                positionCache.set(target, cached);
                
                // Adicionar classe durante o drag
                target.classList.add('component-dragging');
                
                // Atualizar toolbar apenas periodicamente (reduz overhead)
                const now = Date.now();
                if (selectedElement === target && (now - lastToolbarUpdate) > TOOLBAR_UPDATE_INTERVAL) {
                    updateFloatingToolbar(target);
                    lastToolbarUpdate = now;
                }
                
                pendingUpdate = null;
                rafId = null;
            });
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
        function selectElement(element, isMultiSelect = false) {
            if (!element || element.classList.contains('drop-zone') || element.classList.contains('canvas-frame') || element.classList.contains('frame-header')) {
                return;
            }
            
            // Se Ctrl não estiver pressionado, limpar seleção anterior
            if (!isMultiSelect) {
            document.querySelectorAll('.component-selected').forEach(el => {
                el.classList.remove('component-selected');
            });
                selectedElements = [];
            }

            // Adicionar ou remover da seleção múltipla
            if (element.classList.contains('component-selected')) {
                element.classList.remove('component-selected');
                selectedElements = selectedElements.filter(el => el !== element);
            } else {
            element.classList.add('component-selected');
                if (!selectedElements.includes(element)) {
                    selectedElements.push(element);
                }
            }
            
            // Atualizar seleção única para compatibilidade
            if (selectedElements.length === 1) {
                selectedElement = selectedElements[0];
                showProperties(selectedElement);
                updateFloatingToolbar(selectedElement);
            } else if (selectedElements.length > 1) {
                selectedElement = selectedElements[0]; // Usar o primeiro para posicionar a toolbar
                showMultipleProperties();
                updateFloatingToolbar(selectedElement);
            } else {
                selectedElement = null;
                document.getElementById('propertiesPanel').innerHTML = 
                    '<div class="p-6 text-center"><svg class="w-10 h-10 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076-.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><div class="text-xs font-medium mb-1" style="color: var(--text-muted);">Nenhum elemento selecionado</div><div class="text-[10px]" style="color: var(--text-muted); opacity: 0.6;">Clique em um componente para editar</div></div>';
                // Esconder toolbar quando não há elementos selecionados
                updateFloatingToolbar(null);
            }
            
            updateLayersSelection();
        }
        
        // Função para mostrar propriedades de múltiplos elementos
        function showMultipleProperties() {
            const panel = document.getElementById('propertiesPanel');
            if (!panel) return;
            
            panel.innerHTML = `
                <div class="p-6">
                    <div class="text-xs font-medium mb-4" style="color: var(--text-primary);">
                        ${selectedElements.length} elementos selecionados
                    </div>
                    <div class="space-y-3">
                        <button onclick="deleteSelectedElements()" class="w-full py-2 px-3 rounded text-xs font-medium transition-all flex items-center justify-center gap-2" 
                                style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2);"
                                onmouseover="this.style.background='rgba(239, 68, 68, 0.2)'" 
                                onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                            </svg>
                            Excluir todos
                        </button>
                        <button onclick="groupSelectedElements()" class="w-full py-2 px-3 rounded text-xs font-medium transition-all flex items-center justify-center gap-2" 
                                style="background: rgba(255, 255, 255, 0.05); color: var(--text-primary); border: 1px solid var(--border-subtle);"
                                onmouseover="this.style.background='rgba(255,255,255,0.08)'" 
                                onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                            </svg>
                            Agrupar elementos
                        </button>
                    </div>
                </div>
            `;
        }
        
        // Função para excluir múltiplos elementos
        function deleteSelectedElements() {
            if (selectedElements.length === 0) return;
            
            if (!confirm(`Tem certeza que deseja excluir ${selectedElements.length} elemento(s)?`)) {
                return;
            }
            
            selectedElements.forEach(element => {
                element.remove();
            });
            
            selectedElements = [];
            selectedElement = null;
            saveHistory();
            document.getElementById('propertiesPanel').innerHTML = 
                '<div class="p-6 text-center"><svg class="w-10 h-10 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076-.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><div class="text-xs font-medium mb-1" style="color: var(--text-muted);">Nenhum elemento selecionado</div><div class="text-[10px]" style="color: var(--text-muted); opacity: 0.6;">Clique em um componente para editar</div></div>';
        }
        
        // Função para configurar interações de um elemento
        function setupElementInteractions(element) {
            if (!element || !element.id) return;
            
            // Configurar drag
            interact(`#${element.id}`).draggable({
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
                        target.style.willChange = 'transform';
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
            
            // Adicionar listener de click
            element.addEventListener('click', function(e) {
                e.stopPropagation();
                const isMultiSelect = e.ctrlKey || e.metaKey;
                selectElement(this, isMultiSelect);
            });
        }
        
        // Função para agrupar elementos (mesclar)
        function groupSelectedElements() {
            if (selectedElements.length < 2) return;
            
            const canvas = getActiveCanvas();
            if (!canvas) return;
            
            // Calcular bounding box dos elementos usando posições absolutas
            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
            const positions = [];
            
            selectedElements.forEach(el => {
                const left = parseFloat(el.style.left) || 0;
                const top = parseFloat(el.style.top) || 0;
                const width = el.offsetWidth || parseFloat(el.style.width) || 0;
                const height = el.offsetHeight || parseFloat(el.style.height) || 0;
                
                positions.push({ el, left, top, width, height });
                
                minX = Math.min(minX, left);
                minY = Math.min(minY, top);
                maxX = Math.max(maxX, left + width);
                maxY = Math.max(maxY, top + height);
            });
            
            // Criar container para agrupar
            const group = document.createElement('div');
            group.className = 'element-group component-hover draggable';
            group.id = `component-${componentCounter++}`;
            group.style.cssText = `
                position: absolute;
                left: ${minX}px;
                top: ${minY}px;
                width: ${maxX - minX}px;
                height: ${maxY - minY}px;
                pointer-events: auto;
            `;
            
            // Mover elementos para dentro do grupo ajustando posições relativas
            positions.forEach(({ el, left, top }) => {
                el.style.position = 'absolute';
                el.style.left = (left - minX) + 'px';
                el.style.top = (top - minY) + 'px';
                group.appendChild(el);
            });
            
            canvas.appendChild(group);
            
            // Configurar interações do grupo
            setupElementInteractions(group);
            selectElement(group);
            
            // Limpar seleção múltipla
            selectedElements = [];
            saveHistory();
        }
        
        // Update floating toolbar position - abaixo do elemento e seguindo em tempo real
        let toolbarUpdateInterval = null;
        
        function updateFloatingToolbar(element) {
            const toolbar = document.getElementById('floatingToolbar');
            
            // Verificar se há elementos selecionados
            const hasSelectedElements = selectedElements && selectedElements.length > 0;
            const hasSelectedElement = selectedElement !== null;
            
            if (!toolbar || !element || (!hasSelectedElements && !hasSelectedElement)) {
                if (toolbar) {
                    toolbar.classList.remove('visible');
                }
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
                const toolbarWidth = 320; // Largura aumentada
                const toolbarHeight = 44; // Altura aumentada
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
            // Verificar se é um frame ou componente
            const isFrame = element.classList.contains('canvas-frame');
            const elementName = element.dataset.frameName || element.dataset.componentName || (isFrame ? 'Frame' : type.replace('_', ' '));
            
            html += `<div class="pb-3 mb-3" style="border-bottom: 1px solid var(--border-subtle);">
                     <div class="flex items-center justify-between mb-2">
                         <label class="property-label">Tipo</label>
                         <span class="badge" style="font-size: 9px; padding: 2px 8px; background: rgba(255,255,255,0.15);">${type || (isFrame ? 'Frame' : 'Component')}</span>
                     </div>
                     <div class="text-sm font-semibold capitalize mb-3" style="color: var(--text-primary);">${isFrame ? 'Frame' : (type ? type.replace('_', ' ') : 'Component')}</div>
                     
                     <!-- Campo de Nome -->
                     <div class="space-y-1">
                         <label class="property-label-small block">Nome</label>
                         <input type="text" id="element-name-input" value="${elementName}" 
                                onchange="updateElementName(selectedElement, this.value); saveHistory();"
                       class="property-input w-full rounded px-2 py-1.5 text-xs" 
                                style="max-width: 100%; background: var(--bg-hover); border: 1px solid var(--border-subtle); color: var(--text-primary);"
                                placeholder="Nome do elemento">
                     </div>
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

        function updateElementName(element, name) {
            if (!element) return;
            
            // Verificar se é um frame
            if (element.classList.contains('canvas-frame')) {
                element.setAttribute('data-frame-name', name);
                // Atualizar o display do nome no header do frame
                const nameDisplay = element.querySelector('.frame-name-display');
                if (nameDisplay) {
                    nameDisplay.textContent = name;
                }
            } else {
                // É um componente
                element.setAttribute('data-component-name', name);
            }
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
            const canvas = getActiveCanvas();
            if (!canvas) {
                alert('Por favor, crie um frame primeiro clicando em "Novo Frame" na toolbar.');
                return;
            }
            
            if (confirm('Tem certeza que deseja limpar o canvas do frame selecionado?')) {
                
                const placeholderHtml = '<div id="canvas-placeholder" class="p-12 text-gray-400 text-center" style="pointer-events: none;"><svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg><div class="text-sm font-medium mb-1" style="color: var(--text-muted);">Arraste componentes aqui</div><div class="text-xs" style="color: var(--text-muted); opacity: 0.6;">ou use um template pronto</div></div>';
                
                canvas.innerHTML = placeholderHtml;
                
                // Resetar histórico
                history = [];
                historyIndex = -1;
                componentCounter = 0;
                
                selectedElement = null;
                document.getElementById('propertiesPanel').innerHTML = 
                    '<div class="p-6 text-center"><svg class="w-10 h-10 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076-.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><div class="text-xs font-medium mb-1" style="color: var(--text-muted);">Nenhum elemento selecionado</div><div class="text-[10px]" style="color: var(--text-muted); opacity: 0.6;">Clique em um componente para editar</div></div>';
                updateLayersPanel();
                
                // Limpar auto-save quando limpar intencionalmente
                clearAutoSave();
                saveHistory();
            }
        }

        // Preview toggle (deprecated, kept for compatibility)
        function togglePreview(mode) {
            setCanvasSize(mode);
        }

        // Export HTML
        // Função para gerar preview mobile com QR code
        // Variável global para armazenar token de preview ativo e frame selecionado
        let activePreviewToken = null;
        let activePreviewFrameId = null;
        let previewUpdateInterval = null;
        
        // Função para detectar frames mobile
        function getMobileFrames() {
            const allFrames = document.querySelectorAll('.canvas-frame');
            const mobileFrames = [];
            
            allFrames.forEach(frame => {
                const canvas = frame.querySelector('.drop-zone');
                if (canvas && canvas.closest('.phone-frame-wrapper')) {
                    const frameId = frame.id;
                    const frameName = frame.getAttribute('data-frame-name') || frameId;
                    mobileFrames.push({
                        id: frameId,
                        name: frameName,
                        frame: frame,
                        canvas: canvas
                    });
                }
            });
            
            return mobileFrames;
        }
        
        // Função para gerar preview de um frame específico
        function generatePreviewForFrame(frameId) {
            const frame = document.getElementById(frameId);
            if (!frame) return null;
            
            const canvas = frame.querySelector('.drop-zone');
            if (!canvas) return null;
            
            // Clonar canvas para limpar classes de edição
            const clonedCanvas = canvas.cloneNode(true);
            
            // Remover classes e atributos de edição
            clonedCanvas.querySelectorAll('.component-hover, .component-selected, .component-dragging').forEach(el => {
                el.classList.remove('component-hover', 'component-selected', 'component-dragging', 'cursor-pointer', 'border', 'border-transparent', 'hover:border-gray-200');
                el.removeAttribute('data-type');
                el.removeAttribute('data-x');
                el.removeAttribute('data-y');
                el.style.transform = '';
                el.style.cursor = '';
                el.style.zIndex = '';
            });
            
            // Remover placeholders
            clonedCanvas.querySelectorAll('[id^="canvas-placeholder"]').forEach(el => {
                el.remove();
            });
            
            // Obter HTML limpo
            const canvasHTML = clonedCanvas.innerHTML;
            
            return canvasHTML;
        }
        
        // Função para enviar preview e mostrar modal
        function sendPreviewAndShowModal(frameId, canvasHTML) {
            // Criar um objeto com os dados do template
            const templateData = {
                html: canvasHTML,
                frameId: frameId,
                timestamp: Date.now()
            };
            
            // Enviar para o servidor para gerar token
            fetch('api/generate-mobile-preview.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(templateData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Armazenar token e frame ID para atualizações em tempo real
                    activePreviewToken = data.token;
                    activePreviewFrameId = frameId;
                    
                    // Construir URL corretamente
                    const currentPath = window.location.pathname;
                    let basePath = currentPath;
                    if (basePath.endsWith('safefig.php')) {
                        basePath = basePath.replace(/safefig\.php$/, '');
                    } else if (basePath.includes('/')) {
                        basePath = basePath.substring(0, basePath.lastIndexOf('/') + 1);
                    } else {
                        basePath = '/';
                    }
                    if (!basePath.endsWith('/')) {
                        basePath += '/';
                    }
                    const baseUrl = window.location.origin + basePath;
                    const previewUrl = baseUrl + 'preview-mobile.php?token=' + data.token;
                    
                    // Limpar QR code anterior
                    const qrContainer = document.getElementById('qrcode');
                    if (qrContainer) {
                        qrContainer.innerHTML = '';
                        
                        // Gerar novo QR code
                        try {
                            new QRCode(qrContainer, {
                                text: previewUrl,
                                width: 180,
                                height: 180,
                                colorDark: '#000000',
                                colorLight: '#ffffff',
                                correctLevel: QRCode.CorrectLevel.H
                            });
                        } catch (error) {
                            console.error('Erro ao gerar QR code:', error);
                            qrContainer.innerHTML = '<p style="color: var(--text-secondary); font-size: 12px;">Erro ao gerar QR code. Use o link abaixo.</p>';
                        }
                    }
                    
                    // Atualizar link
                    const linkInput = document.getElementById('previewLink');
                    if (linkInput) {
                        linkInput.value = previewUrl;
                    }
                    
                    // Mostrar modal
                    const modal = document.getElementById('mobilePreviewModal');
                    if (modal) {
                        modal.classList.remove('hidden');
                    }
                    
                    // Iniciar atualizações em tempo real
                    startRealTimeUpdates();
                } else {
                    alert('Erro ao gerar preview: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao gerar preview. Verifique o console para mais detalhes.');
            });
        }
        
        // Função principal para gerar preview mobile
        function generateMobilePreview() {
            // Buscar todos os frames mobile
            const mobileFrames = getMobileFrames();
            
            if (mobileFrames.length === 0) {
                alert('Nenhum frame mobile encontrado. Aplique um frame mobile primeiro (iPhone ou Android).');
                return;
            }
            
            // Se houver apenas um frame mobile, usar diretamente
            if (mobileFrames.length === 1) {
                const canvasHTML = generatePreviewForFrame(mobileFrames[0].id);
                if (canvasHTML) {
                    sendPreviewAndShowModal(mobileFrames[0].id, canvasHTML);
                }
                return;
            }
            
            // Se houver múltiplos frames mobile, mostrar modal de seleção
            showFrameSelectionModal(mobileFrames);
        }
        
        // Função para mostrar modal de seleção de frame
        function showFrameSelectionModal(mobileFrames) {
            // Criar modal de seleção
            const modal = document.createElement('div');
            modal.id = 'frameSelectionModal';
            modal.className = 'fixed inset-0 flex items-center justify-center z-50';
            modal.style.cssText = 'background: rgba(0,0,0,0.8); backdrop-filter: blur(4px);';
            modal.innerHTML = `
                <div class="rounded-lg p-6" style="background: var(--bg-card); border: 1px solid var(--border-subtle); max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold" style="color: var(--text-primary);">Selecione o Frame Mobile</h3>
                        <button onclick="document.getElementById('frameSelectionModal').remove()" class="p-1 rounded hover:bg-var(--bg-hover)" style="color: var(--text-secondary);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-2">
                        ${mobileFrames.map((frame, index) => `
                            <button onclick="selectFrameForPreview('${frame.id}')" 
                                    class="w-full p-3 rounded text-left transition-all hover:bg-var(--bg-hover)"
                                    style="background: var(--bg-hover); border: 1px solid var(--border-subtle); color: var(--text-primary);">
                                <div class="font-medium">${frame.name}</div>
                                <div class="text-xs mt-1" style="color: var(--text-secondary);">Frame ID: ${frame.id}</div>
                            </button>
                        `).join('')}
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Fechar ao clicar fora
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }
        
        // Função para selecionar frame e gerar preview
        function selectFrameForPreview(frameId) {
            // Remover modal de seleção
            const selectionModal = document.getElementById('frameSelectionModal');
            if (selectionModal) {
                selectionModal.remove();
            }
            
            // Gerar preview para o frame selecionado
            const canvasHTML = generatePreviewForFrame(frameId);
            if (canvasHTML) {
                sendPreviewAndShowModal(frameId, canvasHTML);
            }
        }
        
        // Função para iniciar atualizações em tempo real
        function startRealTimeUpdates() {
            // Parar atualizações anteriores se houver
            if (previewUpdateInterval) {
                clearInterval(previewUpdateInterval);
            }
            
            // Atualizar a cada 2 segundos
            previewUpdateInterval = setInterval(() => {
                if (activePreviewToken && activePreviewFrameId) {
                    updatePreviewContent();
                }
            }, 2000);
        }
        
        // Função para atualizar conteúdo do preview
        function updatePreviewContent() {
            if (!activePreviewToken || !activePreviewFrameId) return;
            
            const frame = document.getElementById(activePreviewFrameId);
            if (!frame) {
                // Frame foi deletado, parar atualizações
                stopRealTimeUpdates();
                return;
            }
            
            const canvasHTML = generatePreviewForFrame(activePreviewFrameId);
            if (!canvasHTML) return;
            
            // Enviar atualização para o servidor
            fetch('api/update-mobile-preview.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: activePreviewToken,
                    html: canvasHTML,
                    timestamp: Date.now()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Erro ao atualizar preview:', data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao atualizar preview:', error);
            });
        }
        
        // Função para parar atualizações em tempo real
        function stopRealTimeUpdates() {
            if (previewUpdateInterval) {
                clearInterval(previewUpdateInterval);
                previewUpdateInterval = null;
            }
            activePreviewToken = null;
            activePreviewFrameId = null;
        }
        
        // Parar atualizações quando fechar o modal
        function closeMobilePreview() {
            stopRealTimeUpdates();
            const modal = document.getElementById('mobilePreviewModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
        
        // Função para configurar listeners de atualização em tempo real
        function setupRealTimeUpdateListeners() {
            // Observar mudanças no DOM dos frames
            const observer = new MutationObserver(function(mutations) {
                if (activePreviewToken && activePreviewFrameId) {
                    // Debounce: atualizar apenas após 500ms sem mudanças
                    clearTimeout(window.previewUpdateTimeout);
                    window.previewUpdateTimeout = setTimeout(() => {
                        updatePreviewContent();
                    }, 500);
                }
            });
            
            // Observar todos os frames
            const framesContainer = document.getElementById('framesContainer');
            if (framesContainer) {
                observer.observe(framesContainer, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['style', 'class']
                });
            }
            
            // Também observar mudanças de conteúdo nos canvases
            document.addEventListener('input', function(e) {
                if (activePreviewToken && activePreviewFrameId) {
                    const frame = document.getElementById(activePreviewFrameId);
                    if (frame && frame.contains(e.target)) {
                        clearTimeout(window.previewUpdateTimeout);
                        window.previewUpdateTimeout = setTimeout(() => {
                            updatePreviewContent();
                        }, 500);
                    }
                }
            });
        }
        
        // Função para copiar link
        function copyPreviewLink() {
            const linkInput = document.getElementById('previewLink');
            if (!linkInput) return;
            
            linkInput.select();
            linkInput.setSelectionRange(0, 99999); // Para mobile
            document.execCommand('copy');
            
            // Feedback visual
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Copiado!';
            button.style.color = '#10b981';
            setTimeout(() => {
                button.textContent = originalText;
                button.style.color = '';
            }, 2000);
        }
        
        // Fechar modal ao clicar fora
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('mobilePreviewModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeMobilePreview();
                    }
                });
            }
        });
        
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
                    html = `<img id="${id}" src="https://placehold.co/600x300/e2e8f0/64748b?text=Sua+Imagem" alt="Imagem" class="component-hover draggable cursor-move rounded-md border border-gray-200" data-type="image" data-x="${x}" data-y="${y}" style="position: absolute; left: ${x}px; top: ${y}px; display: block; width: ${width}px; height: ${width * 0.5}px; max-width: 100%; touch-action: none; user-select: none; object-fit: cover; object-position: center;">`;
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
                            target.style.boxShadow = ''; // Remover qualquer sombra residual
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
