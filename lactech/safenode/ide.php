<?php
/**
 * SafeCode IDE - SafeNode Mail
 * Editor de código com preview e assistência de IA
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
}

// Verificar se há dados do projeto para importar da sessão (vindo do mail.php)
if (isset($_SESSION['safefig_project_data']) && !empty($_SESSION['safefig_project_data'])) {
    $projectDataFromMail = $_SESSION['safefig_project_data'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeCode IDE | SafeNode Mail</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    
    <!-- CodeMirror Editor -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.15/lib/codemirror.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.15/theme/monokai.css">
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.15/lib/codemirror.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.15/mode/xml/xml.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.15/mode/htmlmixed/htmlmixed.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.15/mode/css/css.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.15/mode/javascript/javascript.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #000000;
            color: #e4e4e7;
            overflow: hidden;
            height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        
        .ide-container {
            display: flex;
            height: 100vh;
            flex-direction: column;
            background: #000000;
        }
        
        /* Header Minimalista com Melhorias */
        .ide-header {
            background: linear-gradient(180deg, #000000 0%, #0a0a0a 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            position: relative;
            backdrop-filter: blur(10px);
        }
        
        .ide-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(139, 92, 246, 0.3) 50%, 
                transparent 100%);
            opacity: 0.5;
        }
        
        .ide-header-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .ide-header-title img {
            width: 24px;
            height: 24px;
            object-fit: contain;
            filter: drop-shadow(0 0 8px rgba(139, 92, 246, 0.3));
            transition: transform 0.3s ease;
        }
        
        .ide-header-title:hover img {
            transform: scale(1.1) rotate(5deg);
        }
        
        .ide-header-title span {
            font-size: 0.85rem;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            background: linear-gradient(135deg, #ffffff 0%, #a1a1aa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
        }
        
        /* Botões Minimalistas */
        .ide-header button, .btn-primary, .btn-purple {
            transition: all 0.15s ease;
            border-radius: 4px !important;
            padding: 0.5rem 1rem !important;
            font-weight: 500 !important;
            font-size: 0.75rem !important;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            background: transparent !important;
            color: #a1a1aa !important;
        }
        
        .ide-header button:hover {
            background: rgba(255, 255, 255, 0.05) !important;
            color: #ffffff !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ffffff 0%, #f4f4f5 100%) !important;
            color: #000000 !important;
            border: none !important;
            box-shadow: 0 2px 8px rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #f4f4f5 0%, #e4e4e7 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
        }
        
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(255, 255, 255, 0.1);
        }
        
        .btn-purple {
            border-color: rgba(139, 92, 246, 0.3) !important;
            color: #a78bfa !important;
            position: relative;
            overflow: hidden;
        }
        
        .btn-purple::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .btn-purple:hover::before {
            opacity: 1;
        }
        
        .btn-purple:hover {
            background: rgba(139, 92, 246, 0.15) !important;
            border-color: #8b5cf6 !important;
            color: #ffffff !important;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
            transform: translateY(-1px);
        }
        
        .btn-purple:active {
            transform: translateY(0);
        }

        /* Divisores de Painéis */
        .ide-main {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .ide-editor-container, .ide-preview-container, .ide-ai-container {
            border-left: 1px solid rgba(255, 255, 255, 0.05) !important;
            background: #000000 !important;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .ide-editor-container { border-left: none !important; flex: 1; }
        .ide-preview-container { 
            width: 50%; 
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            background: #000000;
            position: relative;
            min-width: 300px;
        }

        .preview-resizer {
            position: absolute;
            left: -5px;
            top: 0;
            bottom: 0;
            width: 10px;
            cursor: col-resize;
            z-index: 10;
            background: transparent;
            transition: background 0.2s ease;
        }

        .preview-resizer:hover {
            background: rgba(139, 92, 246, 0.3);
        }

        .preview-resizer:active {
            background: rgba(139, 92, 246, 0.5);
        }

        .preview-resizer::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 2px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 1px;
            transition: background 0.2s ease;
        }

        .preview-resizer:hover::before {
            background: rgba(139, 92, 246, 0.6);
        }

        .preview-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        /* Frames Mobile */
        .ide-preview-container.is-mobile .preview-body {
            background: #000000;
            padding: 2rem;
            align-items: center;
            overflow-y: auto;
        }

        .device-container {
            flex: none;
            background: #000000;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            display: none;
            flex-direction: column;
            position: relative;
        }

        .ide-preview-container.is-mobile .device-container {
            display: flex;
        }

        .ide-preview-container.is-mobile #preview-frame-mobile {
            width: 100%;
            height: 100%;
            border: none;
            background: #ffffff;
        }

        /* Frame Mobile - Android */
        .ide-preview-container.is-mobile .device-container {
            width: 360px;
            height: 740px;
            border-radius: 24px;
            border: 8px solid #1f1f1f;
            background: linear-gradient(145deg, #2a2a2a 0%, #1a1a1a 100%);
            padding: 0;
            position: relative;
            box-shadow: 
                0 0 0 2px rgba(0, 0, 0, 0.2),
                0 30px 60px -12px rgba(0, 0, 0, 0.6),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        /* Tela do Android */
        .ide-preview-container.is-mobile .device-container::before {
            content: "";
            position: absolute;
            top: 4px;
            left: 4px;
            right: 4px;
            bottom: 4px;
            background: #000000;
            border-radius: 20px;
            z-index: 1;
            box-shadow: 
                inset 0 0 15px rgba(0, 0, 0, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        /* Área de conteúdo Android */
        .ide-preview-container.is-mobile #preview-frame-mobile {
            position: absolute;
            top: 28px;      /* Status bar (24px) + margin (4px) */
            left: 4px;
            right: 4px;
            bottom: 4px;   /* Sem navigation bar, vai até o final */
            width: calc(100% - 8px);
            height: calc(100% - 32px); /* Ajustado: status bar (24px) + margens (8px) */
            border-radius: 0 0 16px 16px;
            border: none;
            background: #ffffff;
            z-index: 2;
            overflow: hidden;
            box-shadow: inset 0 0 1px rgba(0, 0, 0, 0.1);
        }

        .device-camera {
            display: none;
            position: absolute;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            width: 8px;
            height: 8px;
            background: #333;
            border-radius: 50%;
            z-index: 20;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .ide-preview-container.is-mobile .device-camera {
            display: block;
        }
        
        /* Botões Físicos do Celular */
        .device-button {
            position: absolute;
            background: linear-gradient(180deg, #1a1a1a 0%, #0f0f0f 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            z-index: 25;
            box-shadow: 
                inset 0 1px 0 rgba(255, 255, 255, 0.05),
                0 2px 4px rgba(0, 0, 0, 0.3);
            display: none;
        }
        
        .ide-preview-container.is-mobile .device-button {
            display: block;
        }
        
        /* Botão Volume Up (lateral esquerda, superior) */
        .device-button.volume-up {
            left: -3px;
            top: 120px;
            width: 3px;
            height: 32px;
            border-radius: 2px 0 0 2px;
        }
        
        /* Botão Volume Down (lateral esquerda, abaixo do volume up) */
        .device-button.volume-down {
            left: -3px;
            top: 160px;
            width: 3px;
            height: 32px;
            border-radius: 2px 0 0 2px;
        }
        
        /* Botão Power (lateral direita, meio) */
        .device-button.power-button {
            right: -3px;
            top: 140px;
            width: 3px;
            height: 40px;
            border-radius: 0 2px 2px 0;
        }
        
        /* Efeito hover sutil nos botões */
        .device-button:hover {
            background: linear-gradient(180deg, #222222 0%, #1a1a1a 100%);
            border-color: rgba(255, 255, 255, 0.15);
        }
        
        /* Android Status Bar */
        .android-status-bar {
            position: absolute;
            top: 4px;
            left: 4px;
            right: 4px;
            height: 24px;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
            border-radius: 8px 8px 0 0;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 12px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .status-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-time {
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .status-right {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .battery-container {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .battery-level {
            color: #ffffff;
            font-size: 11px;
            font-weight: 500;
        }
        
        .battery-icon {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .battery-icon svg {
            width: 20px;
            height: 10px;
        }
        
        .battery-icon #battery-fill {
            fill: #4ade80;
            transition: all 0.3s ease;
            width: 15px;
            x: 2.5;
        }
        
        /* Menu de Controles Mobile - Minimalista */
        .mobile-controls-menu {
            display: none;
            width: 100%;
            max-width: 360px;
            margin: 1rem auto 0;
            padding: 0;
        }
        
        .ide-preview-container.is-mobile .mobile-controls-menu {
            display: block;
        }
        
        .controls-header {
            display: none;
        }
        
        .controls-grid {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .control-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            padding: 0.5rem 0.75rem;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 6px;
            color: #71717a;
            font-size: 0.65rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
            min-width: auto;
        }
        
        .control-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.15);
            color: #a1a1aa;
        }
        
        .control-btn:active {
            transform: scale(0.95);
        }
        
        .control-btn i {
            width: 14px;
            height: 14px;
        }
        
        .control-btn span {
            white-space: nowrap;
        }
        
        .control-btn.active {
            background: rgba(139, 92, 246, 0.15);
            border-color: rgba(139, 92, 246, 0.3);
            color: #a78bfa;
        }
        
        .control-btn.active:hover {
            background: rgba(139, 92, 246, 0.2);
            border-color: rgba(139, 92, 246, 0.4);
        }

        .preview-frame {
            flex: 1;
            border: none;
            width: 100%;
            background: #ffffff;
            overflow: hidden;
        }

        /* Esconder scrollbars nos frames */
        .preview-frame::-webkit-scrollbar,
        #preview-frame-mobile::-webkit-scrollbar {
            display: none;
            width: 0;
            height: 0;
        }

        .preview-frame,
        #preview-frame-mobile {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .ide-preview-container.is-mobile .preview-frame {
            display: none;
        }

        .device-toggle {
            display: flex;
            gap: 2px;
            background: rgba(255, 255, 255, 0.05);
            padding: 2px;
            border-radius: 6px;
            position: relative;
        }

        .device-toggle button {
            padding: 4px 10px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: #71717a;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            position: relative;
        }
        
        .device-toggle button::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 6px;
            background: rgba(139, 92, 246, 0.1);
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .device-toggle button:hover::before {
            opacity: 1;
        }

        .device-toggle button.active {
            background: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
            box-shadow: 0 0 10px rgba(139, 92, 246, 0.2);
        }
        
        .device-toggle button.active::before {
            opacity: 1;
            background: rgba(139, 92, 246, 0.15);
        }

        .ide-ai-container { width: 380px; }

        /* Barra de Informações Discreta Melhorada */
        .project-info {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(180deg, #0a0a0a 0%, #000000 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.75rem;
            color: #71717a;
            letter-spacing: 0.01em;
            position: relative;
        }
        
        .project-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(139, 92, 246, 0.2) 50%, 
                transparent 100%);
        }
        
        .project-info > div {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        
        .project-info > div > div {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.2s ease;
        }
        
        .project-info > div > div:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }
        
        .project-info strong {
            color: #a78bfa;
            font-weight: 600;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        /* Preview Header Melhorado */
        .preview-header {
            background: linear-gradient(180deg, #0a0a0a 0%, #000000 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 0.625rem 1rem;
            font-size: 0.7rem;
            color: #71717a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        
        .preview-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(139, 92, 246, 0.2) 50%, 
                transparent 100%);
        }
        
        .preview-header > div:first-child {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .preview-header > div:first-child > span {
            font-weight: 600;
            color: #a1a1aa;
            font-size: 0.7rem;
        }
        
        .preview-header button {
            transition: all 0.2s ease;
        }
        
        .preview-header button:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            transform: scale(1.05);
        }

        /* Editor */
        #editor {
            flex: 1;
            background: #000000;
        }

        .CodeMirror {
            height: 100% !important;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            background: #000000 !important;
            color: #d4d4d4;
        }
        
        .CodeMirror-gutters {
            background: #000000 !important;
            border-right: 1px solid rgba(255, 255, 255, 0.05) !important;
        }

        /* Chat IA - Design Moderno e Fluido */
        .ai-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: linear-gradient(180deg, #000000 0%, #0a0a0a 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        
        .ai-header span {
            font-size: 0.875rem;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .ai-header button {
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.2s ease;
            color: #71717a;
            border: none;
            background: transparent;
            cursor: pointer;
        }

        .ai-header button:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }
        
        .ai-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background: #000000;
        }

        /* Scrollbar customizada para mensagens */
        .ai-messages::-webkit-scrollbar {
            width: 6px;
        }
        
        .ai-messages::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .ai-messages::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }
        
        .ai-messages::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .message-user, .message-assistant {
            animation: messageSlideIn 0.3s ease-out;
            opacity: 0;
            animation-fill-mode: forwards;
        }

        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message-user {
            background: rgba(139, 92, 246, 0.1);
            padding: 0.875rem 1.125rem;
            border-radius: 16px 16px 4px 16px;
            margin-left: auto;
            max-width: 80%;
            border: 1px solid rgba(139, 92, 246, 0.2);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .message-assistant {
            background: rgba(255, 255, 255, 0.03);
            padding: 0.875rem 1.125rem;
            border-radius: 16px 16px 16px 4px;
            max-width: 80%;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .message-role {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .role-user { 
            color: #a78bfa;
        }
        
        .role-assistant { 
            color: #8b5cf6;
        }

        .message-content {
            font-size: 0.875rem;
            line-height: 1.7;
            color: #e4e4e7;
            word-wrap: break-word;
        }

        .message-content p {
            margin: 0.5rem 0;
        }

        .message-content p:first-child {
            margin-top: 0;
        }

        .message-content p:last-child {
            margin-bottom: 0;
        }

        .message-content pre {
            background: rgba(0, 0, 0, 0.4) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 8px;
            padding: 1rem;
            margin: 0.75rem 0;
            overflow-x: auto;
            font-size: 0.8125rem;
        }

        .message-content code {
            background: rgba(0, 0, 0, 0.3);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-size: 0.875em;
            color: #f472b6;
        }

        .message-content pre code {
            background: transparent;
            padding: 0;
        }

        /* Input de IA - Componente Uiverse Adaptado */
        .ai-input-container {
            padding: 1rem 1.5rem;
            background: linear-gradient(180deg, #0a0a0a 0%, #000000 100%);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #poda {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 100%;
            position: relative;
        }

        .grid {
            height: 800px;
            width: 800px;
            background-image: linear-gradient(to right, #0f0f10 1px, transparent 1px),
                linear-gradient(to bottom, #0f0f10 1px, transparent 1px);
            background-size: 1rem 1rem;
            background-position: center center;
            position: absolute;
            z-index: -1;
            filter: blur(1px);
        }

        .white,
        .border,
        .darkBorderBg,
        .glow {
            max-height: none;
            height: 100%;
            width: 100%;
            position: absolute;
            overflow: hidden;
            z-index: -1;
            border-radius: 12px;
            filter: blur(3px);
        }

        #aiInput {
            background-color: #010201;
            border: none;
            width: 100%;
            min-height: 44px;
            height: 44px;
            border-radius: 10px;
            color: white;
            padding-inline: 42px 42px;
            padding-top: 12px;
            padding-bottom: 12px;
            font-size: 14px;
            resize: none;
            max-height: 200px;
            overflow-y: auto;
            font-family: 'Inter', sans-serif;
            transition: height 0.2s ease;
            line-height: 1.5;
        }

        #aiInput::placeholder {
            color: #c0b9c0;
        }

        #aiInput:focus {
            outline: none;
        }

        #main:focus-within > #input-mask {
            display: none;
        }

        #input-mask {
            pointer-events: none;
            width: 80px;
            height: 16px;
            position: absolute;
            background: linear-gradient(90deg, transparent, black);
            top: 14px;
            left: 58px;
        }

        #pink-mask {
            pointer-events: none;
            width: 24px;
            height: 16px;
            position: absolute;
            background: #cf30aa;
            top: 8px;
            left: 4px;
            filter: blur(20px);
            opacity: 0.8;
            transition: all 2s;
        }

        #main:hover > #pink-mask {
            opacity: 0;
        }

        #poda:hover > .darkBorderBg::before {
            transform: translate(-50%, -50%) rotate(262deg);
        }

        #poda:hover > .glow::before {
            transform: translate(-50%, -50%) rotate(240deg);
        }

        #poda:hover > .white::before {
            transform: translate(-50%, -50%) rotate(263deg);
        }

        #poda:hover > .border::before {
            transform: translate(-50%, -50%) rotate(250deg);
        }

        #poda:focus-within > .darkBorderBg::before {
            transform: translate(-50%, -50%) rotate(442deg);
            transition: all 4s;
        }

        #poda:focus-within > .glow::before {
            transform: translate(-50%, -50%) rotate(420deg);
            transition: all 4s;
        }

        #poda:focus-within > .white::before {
            transform: translate(-50%, -50%) rotate(443deg);
            transition: all 4s;
        }

        #poda:focus-within > .border::before {
            transform: translate(-50%, -50%) rotate(430deg);
            transition: all 4s;
        }

        .white,
        .border,
        .darkBorderBg,
        .glow {
            max-height: none;
            height: 100%;
            width: 100%;
            position: absolute;
            overflow: hidden;
            z-index: -1;
            border-radius: 12px;
            filter: blur(3px);
            top: 0;
            left: 0;
        }

        .white {
            min-height: 51px;
            border-radius: 10px;
            filter: blur(2px);
        }

        .white::before {
            content: "";
            z-index: -2;
            text-align: center;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(83deg);
            position: absolute;
            width: 600px;
            height: 600px;
            background-repeat: no-repeat;
            background-position: 0 0;
            filter: brightness(1.4);
            background-image: conic-gradient(
                rgba(0, 0, 0, 0) 0%,
                #a099d8,
                rgba(0, 0, 0, 0) 8%,
                rgba(0, 0, 0, 0) 50%,
                #dfa2da,
                rgba(0, 0, 0, 0) 58%
            );
            transition: all 2s;
        }

        .border {
            min-height: 47px;
            border-radius: 11px;
            filter: blur(0.5px);
        }

        .border::before {
            content: "";
            z-index: -2;
            text-align: center;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(70deg);
            position: absolute;
            width: 600px;
            height: 600px;
            filter: brightness(1.3);
            background-repeat: no-repeat;
            background-position: 0 0;
            background-image: conic-gradient(
                #1c191c,
                #402fb5 5%,
                #1c191c 14%,
                #1c191c 50%,
                #cf30aa 60%,
                #1c191c 64%
            );
            transition: all 2s;
        }

        .darkBorderBg {
            min-height: 53px;
        }

        .darkBorderBg::before {
            content: "";
            z-index: -2;
            text-align: center;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(82deg);
            position: absolute;
            width: 600px;
            height: 600px;
            background-repeat: no-repeat;
            background-position: 0 0;
            background-image: conic-gradient(
                rgba(0, 0, 0, 0),
                #18116a,
                rgba(0, 0, 0, 0) 10%,
                rgba(0, 0, 0, 0) 50%,
                #6e1b60,
                rgba(0, 0, 0, 0) 60%
            );
            transition: all 2s;
        }

        .glow {
            overflow: hidden;
            filter: blur(30px);
            opacity: 0.4;
            min-height: 100px;
        }

        .glow:before {
            content: "";
            z-index: -2;
            text-align: center;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(60deg);
            position: absolute;
            width: 999px;
            height: 999px;
            background-repeat: no-repeat;
            background-position: 0 0;
            background-image: conic-gradient(
                #000,
                #402fb5 5%,
                #000 38%,
                #000 50%,
                #cf30aa 60%,
                #000 87%
            );
            transition: all 2s;
        }

        @keyframes rotate {
            100% {
                transform: translate(-50%, -50%) rotate(450deg);
            }
        }

        #main {
            position: relative;
            width: 100%;
            min-height: 44px;
        }

        #send-icon {
            position: absolute;
            top: 6px;
            right: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            height: 32px;
            width: 32px;
            isolation: isolate;
            overflow: hidden;
            border-radius: 8px;
            background: linear-gradient(180deg, #161329, black, #1d1b4b);
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        #send-icon:hover {
            background: linear-gradient(180deg, #1e1a3a, #0a0a0a, #252352);
            transform: scale(1.05);
        }

        #send-icon:active {
            transform: scale(0.95);
        }

        .sendBorder {
            height: 34px;
            width: 34px;
            position: absolute;
            overflow: hidden;
            top: 5px;
            right: 5px;
            border-radius: 8px;
        }

        .sendBorder::before {
            content: "";
            text-align: center;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(90deg);
            position: absolute;
            width: 600px;
            height: 600px;
            background-repeat: no-repeat;
            background-position: 0 0;
            filter: brightness(1.35);
            background-image: conic-gradient(
                rgba(0, 0, 0, 0),
                #3d3a4f,
                rgba(0, 0, 0, 0) 50%,
                rgba(0, 0, 0, 0) 50%,
                #3d3a4f,
                rgba(0, 0, 0, 0) 100%
            );
            animation: rotate 4s linear infinite;
        }

        #search-icon {
            position: absolute;
            left: 14px;
            top: 12px;
            pointer-events: none;
        }

        #search-icon svg {
            width: 20px;
            height: 20px;
        }

        /* Typing Indicator - Moderno */
        #aiTyping {
            padding: 1rem 1.5rem;
            background: #000000;
        }

        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            background: rgba(139, 92, 246, 0.6);
            border-radius: 50%;
            animation: typingDot 1.4s infinite ease-in-out;
        }

        .typing-dot:nth-child(1) {
            animation-delay: -0.32s;
        }

        .typing-dot:nth-child(2) {
            animation-delay: -0.16s;
        }

        @keyframes typingDot {
            0%, 80%, 100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Status Bar Melhorada */
        .ide-status-bar {
            height: 28px;
            background: linear-gradient(180deg, #000000 0%, #0a0a0a 100%);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            padding: 0 1rem;
            font-size: 10px;
            color: #52525b;
            justify-content: space-between;
            position: relative;
        }
        
        .ide-status-bar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(139, 92, 246, 0.2) 50%, 
                transparent 100%);
        }
        
        .ide-status-bar > div {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .ide-status-bar > div > div {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.02);
            transition: all 0.2s ease;
        }
        
        .ide-status-bar > div > div:hover {
            background: rgba(255, 255, 255, 0.04);
        }
        
        .ide-status-bar span {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(0.95);
            }
        }

        /* Scrollbars */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #000000;
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Scrollbar do CodeMirror */
        .CodeMirror-scrollbar-filler {
            background: #000000;
        }

        .btn-icon {
            padding: 0.625rem;
            border-radius: 8px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #a1a1aa;
        }
        
        .btn-icon:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            transform: scale(1.05);
        }
        
        .btn-icon:active {
            transform: scale(0.95);
        }
        
        .project-info {
            padding: 1rem 1.5rem;
            background: #000000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.8125rem;
            color: #a1a1aa;
            line-height: 1.5;
        }
        
        .project-info strong {
            color: #ffffff;
            font-weight: 600;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="ide-container">
        <!-- Header -->
        <div class="ide-header">
            <div class="ide-header-title">
                <button onclick="window.close()" class="btn-icon" style="padding: 4px !important; border: none !important;">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </button>
                <img src="assets/img/logos (6).png" alt="SafeNode">
                <span>SAFECODE IDE</span>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="saveCode()" class="btn-primary">
                    SAVE
                </button>
                <button onclick="toggleAI()" id="toggleAI" class="btn-purple">
                    ASSISTANT
                </button>
            </div>
        </div>
        
        <?php if ($projectDataFromMail): ?>
        <div class="project-info">
            <div class="flex items-center gap-6">
                <div><strong>PROJECT</strong> <?php echo htmlspecialchars($projectDataFromMail['project_name'] ?? 'NONE'); ?></div>
                <div><strong>SENDER</strong> <?php echo htmlspecialchars($projectDataFromMail['sender_email'] ?? 'NONE'); ?></div>
                <div><strong>FUNCTION</strong> <?php echo htmlspecialchars($projectDataFromMail['email_function'] ?? 'NONE'); ?></div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Main Container -->
        <div class="ide-main">
            <!-- Editor -->
            <div class="ide-editor-container">
                <div id="editor"></div>
            </div>
            
            <!-- Preview -->
            <div class="ide-preview-container" id="previewContainer">
                <div class="preview-resizer" id="previewResizer"></div>
                <div class="preview-header">
                    <div class="flex items-center gap-4">
                        <span class="text-xs font-medium">Preview</span>
                        <div class="device-toggle">
                            <button onclick="setPreviewDevice('desktop')" id="btnDesktop" class="active">
                                <i data-lucide="monitor" class="w-3 h-3"></i> DESKTOP
                            </button>
                            <button onclick="setPreviewDevice('mobile')" id="btnMobile">
                                <i data-lucide="smartphone" class="w-3 h-3"></i> MOBILE
                            </button>
                        </div>
                    </div>
                    <button onclick="refreshPreview()" class="text-xs px-2 py-1 rounded hover:bg-white/5 transition-colors inline-flex items-center gap-1">
                        <i data-lucide="refresh-cw" class="w-3 h-3"></i> REFRESH
                    </button>
                </div>
                <div class="preview-body">
                    <iframe id="preview-frame" class="preview-frame"></iframe>
                    <div class="device-container">
                        <div class="device-camera"></div>
                        <!-- Botões Físicos do Celular -->
                        <div class="device-button volume-up"></div>
                        <div class="device-button volume-down"></div>
                        <div class="device-button power-button"></div>
                        <!-- Status Bar Android -->
                        <div class="android-status-bar">
                            <div class="status-left">
                                <span id="mobile-time" class="status-time">00:00</span>
                            </div>
                            <div class="status-right">
                                <div class="battery-container">
                                    <div class="battery-level" id="battery-level">85%</div>
                                    <div class="battery-icon">
                                        <svg width="24" height="12" viewBox="0 0 24 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <rect x="1" y="2" width="18" height="8" rx="1" stroke="white" stroke-width="1.5" fill="none"/>
                                            <rect x="20" y="4.5" width="2" height="3" rx="0.5" fill="white"/>
                                            <rect x="2.5" y="4" width="12.75" height="4" rx="0.5" fill="#4ade80" id="battery-fill"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <iframe id="preview-frame-mobile"></iframe>
                    </div>
                    
                    <!-- Menu de Controles Mobile -->
                    <div class="mobile-controls-menu" id="mobileControlsMenu">
                        <div class="controls-grid">
                            <button class="control-btn" onclick="toggleMobileDarkMode()" id="darkModeBtn">
                                <i data-lucide="moon" class="w-4 h-4"></i>
                                <span>Modo Escuro</span>
                            </button>
                            <button class="control-btn" onclick="rotateDevice()" id="rotateBtn">
                                <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                                <span>Rotacionar</span>
                            </button>
                            <button class="control-btn" onclick="zoomInMobile()" id="zoomInBtn">
                                <i data-lucide="zoom-in" class="w-4 h-4"></i>
                                <span>Zoom In</span>
                            </button>
                            <button class="control-btn" onclick="zoomOutMobile()" id="zoomOutBtn">
                                <i data-lucide="zoom-out" class="w-4 h-4"></i>
                                <span>Zoom Out</span>
                            </button>
                            <button class="control-btn" onclick="resetMobileView()" id="resetBtn">
                                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                <span>Resetar</span>
                            </button>
                            <button class="control-btn" onclick="fullscreenMobile()" id="fullscreenBtn">
                                <i data-lucide="maximize" class="w-4 h-4"></i>
                                <span>Fullscreen</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- AI Assistant Panel -->
            <div class="ide-ai-container" id="aiPanel" style="display: none;">
                <div class="ai-header">
                    <span>AI ASSISTANT</span>
                    <button onclick="toggleAI()">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                <div class="ai-messages" id="aiMessages">
                    <div class="message-assistant">
                        <div class="message-role role-assistant">AI</div>
                        <div class="message-content">Hello. How can I help with your code today?</div>
                    </div>
                </div>
                <div id="aiTyping" style="display: none;">
                    <div class="typing-indicator">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                </div>
                <div class="ai-input-container">
                    <div id="poda">
                        <div class="glow"></div>
                        <div class="darkBorderBg"></div>
                        <div class="darkBorderBg"></div>
                        <div class="darkBorderBg"></div>
                        <div class="white"></div>
                        <div class="border"></div>
                        <div id="main">
                            <textarea 
                                id="aiInput" 
                                rows="1"
                                placeholder="Type your message..."
                                onkeypress="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); sendAIMessage(); }"
                            ></textarea>
                            <div id="input-mask"></div>
                            <div id="pink-mask"></div>
                            <div class="sendBorder"></div>
                            <button onclick="sendAIMessage()" id="send-icon">
                                <i data-lucide="corner-down-left" class="w-5 h-5" style="color: #a78bfa;"></i>
                            </button>
                            <div id="search-icon">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke-linejoin="round"
                                    stroke-linecap="round"
                                    height="24"
                                    fill="none"
                                    class="feather feather-search"
                                >
                                    <circle stroke="url(#search)" r="8" cy="11" cx="11"></circle>
                                    <line
                                        stroke="url(#searchl)"
                                        y2="16.65"
                                        y1="22"
                                        x2="16.65"
                                        x1="22"
                                    ></line>
                                    <defs>
                                        <linearGradient gradientTransform="rotate(50)" id="search">
                                            <stop stop-color="#f8e7f8" offset="0%"></stop>
                                            <stop stop-color="#b6a9b7" offset="50%"></stop>
                                        </linearGradient>
                                        <linearGradient id="searchl">
                                            <stop stop-color="#b6a9b7" offset="0%"></stop>
                                            <stop stop-color="#837484" offset="50%"></stop>
                                        </linearGradient>
                                    </defs>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Bar -->
        <div class="ide-status-bar px-4">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-1">
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                    READY
                </div>
                <div id="cursorPos">LN 1, COL 1</div>
            </div>
            <div class="flex items-center gap-4 uppercase">
                <div class="flex items-center gap-1">
                    <span class="w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                    AI ACTIVE
                </div>
                <div>UTF-8</div>
                <div>HTML</div>
            </div>
        </div>
    </div>

    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        let editor;
        let aiPanelVisible = false;
        
        const projectData = <?php echo $projectDataFromMail ? json_encode($projectDataFromMail, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) : 'null'; ?>;
        const initialTemplate = <?php echo $templateFromMail ? json_encode($templateFromMail, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) : 'null'; ?>;
        
        const defaultTemplate = `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-mail</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2563eb;
            margin-top: 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Olá {{nome}}!</h1>
        <p>Bem-vindo ao nosso serviço. Este é um template de e-mail de exemplo.</p>
        <p>Seu código de verificação é: <strong>{{codigo}}</strong></p>
        <a href="{{link}}" class="button">Clique aqui para confirmar</a>
        <div class="footer">
            <p>Este é um e-mail automático, por favor não responda.</p>
        </div>
    </div>
</body>
</html>`;
            
            // Função para inicializar o editor CodeMirror
            function initializeEditor() {
                const editorElement = document.getElementById('editor');
                if (!editorElement) {
                    console.error('Elemento #editor não encontrado!');
                    setTimeout(initializeEditor, 200);
                    return;
                }
                
                // Verificar se CodeMirror está disponível
                if (typeof CodeMirror === 'undefined') {
                    console.error('CodeMirror não está carregado!');
                    editorElement.innerHTML = '<div style="padding: 20px; color: #ff6b6b;">Erro: CodeMirror não carregou. Recarregue a página.</div>';
                    return;
                }
                
                // Garantir dimensões
                const parentContainer = editorElement.closest('.ide-editor-container');
                if (parentContainer) {
                    parentContainer.style.display = 'flex';
                    parentContainer.style.flexDirection = 'column';
                    parentContainer.style.height = '100%';
                    parentContainer.style.background = '#1e1e1e';
                }
                
                editorElement.style.width = '100%';
                editorElement.style.height = '100%';
                editorElement.style.minHeight = '400px';
                editorElement.style.background = '#1e1e1e';
                
                // Inicializar CodeMirror
                try {
                    // Limpar qualquer conteúdo anterior
                    editorElement.innerHTML = '';
                    editorElement.style.position = 'relative';
                    
                    editor = CodeMirror(editorElement, {
                        value: initialTemplate || defaultTemplate,
                        mode: 'htmlmixed',
                        theme: 'monokai',
                        lineNumbers: true,
                        lineWrapping: true,
                        indentUnit: 2,
                        tabSize: 2,
                        indentWithTabs: false,
                        smartIndent: true,
                        electricChars: true,
                        autoCloseTags: true,
                        matchBrackets: true,
                        autoCloseBrackets: true,
                        showCursorWhenSelecting: true,
                        styleActiveLine: true,
                        cursorBlinkRate: 530,
                        foldGutter: true,
                        gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter']
                    });
                    
                    console.log('CodeMirror Editor criado com sucesso!');
                    
                    // Ajustar tamanho
                    setTimeout(function() {
                        if (editor) {
                            editor.refresh();
                            editor.focus();
                            
                        // Atualizar preview quando o código mudar
                        editor.on('change', function() {
                            updatePreview();
                        });
                        
                        // Atualizar posição do cursor na status bar
                        editor.on('cursorActivity', function() {
                            const pos = editor.getCursor();
                            document.getElementById('cursorPos').textContent = `Lin ${pos.line + 1}, Col ${pos.ch + 1}`;
                        });
                            
                            // Listener para resize
                            let resizeTimeout;
                            window.addEventListener('resize', function() {
                                clearTimeout(resizeTimeout);
                                resizeTimeout = setTimeout(function() {
                                    if (editor) {
                                        editor.refresh();
                                    }
                                }, 100);
                            });
                            
                            // Atualizar preview inicial
                            updatePreview();
                        }
                    }, 100);
                } catch (error) {
                    console.error('Erro ao criar editor CodeMirror:', error);
                    editorElement.innerHTML = '<div style="padding: 20px; color: #ff6b6b;">Erro ao criar editor: ' + error.message + '</div>';
                }
            }
            
            // Aguardar carregamento completo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeEditor);
            } else {
                setTimeout(initializeEditor, 100);
            }
        
        // Atualizar preview
        function updatePreview() {
            if (!editor) return;
            
            const code = editor.getValue();
            const iframes = [
                document.getElementById('preview-frame'),
                document.getElementById('preview-frame-mobile')
            ];
            
            iframes.forEach(iframe => {
                if (!iframe) return;
                try {
                    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    if (iframeDoc) {
                        iframeDoc.open();
                        
                        // Adicionar CSS para esconder scrollbars e ajustar layout
                        const hideScrollbarCSS = `
                            <style>
                                * { 
                                    margin: 0; 
                                    padding: 0; 
                                    box-sizing: border-box; 
                                }
                                html, body { 
                                    width: 100% !important; 
                                    height: 100% !important; 
                                    margin: 0 !important; 
                                    padding: 0 !important;
                                    overflow-x: hidden !important;
                                    -ms-overflow-style: none !important;
                                    scrollbar-width: none !important;
                                }
                                html::-webkit-scrollbar,
                                body::-webkit-scrollbar,
                                *::-webkit-scrollbar {
                                    display: none !important;
                                    width: 0 !important;
                                    height: 0 !important;
                                }
                            </style>
                        `;
                        
                        if (iframe.id === 'preview-frame-mobile') {
                            iframeDoc.write(hideScrollbarCSS + code);
                        } else {
                            iframeDoc.write(hideScrollbarCSS + code);
                        }
                        
                        iframeDoc.close();
                    }
                } catch (e) {
                    console.error("Erro ao atualizar iframe:", e);
                }
            });
        }
        
        // Atualizar preview manualmente
        function refreshPreview() {
            updatePreview();
        }

        // Alterar dispositivo de preview
        function setPreviewDevice(device) {
            const container = document.getElementById('previewContainer');
            const btnDesktop = document.getElementById('btnDesktop');
            const btnMobile = document.getElementById('btnMobile');

            // Remover todas as classes de dispositivo
            container.classList.remove('is-mobile', 'is-ios', 'is-android');
            btnDesktop.classList.remove('active');
            btnMobile.classList.remove('active');

            if (device === 'mobile') {
                container.classList.add('is-mobile');
                btnMobile.classList.add('active');
            } else {
                btnDesktop.classList.add('active');
            }

            // Atualizar ícones
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Forçar atualização do preview no novo dispositivo
            setTimeout(updatePreview, 50);
        }
        
        // Salvar código
        function saveCode() {
            if (!editor) {
                alert('Editor não está disponível');
                return;
            }
            
            const code = editor.getValue();
            
            // Enviar código de volta para o formulário original via sessão
            fetch('api/set-safefig-template.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'html_template=' + encodeURIComponent(code)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar notificação sutil
                    const notification = document.createElement('div');
                    notification.style.cssText = 'position: fixed; top: 1rem; right: 1rem; background: #ffffff; color: #000000; padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; z-index: 100;';
                    notification.textContent = 'CHANGES SAVED';
                    document.body.appendChild(notification);
                    
                    setTimeout(() => notification.remove(), 2000);
                } else {
                    alert('Erro ao salvar: ' + (data.error || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar código');
            });
        }
        
        // Toggle AI Panel
        function toggleAI() {
            aiPanelVisible = !aiPanelVisible;
            const panel = document.getElementById('aiPanel');
            const btn = document.getElementById('toggleAI');
            
            if (aiPanelVisible) {
                panel.style.display = 'flex';
                btn.classList.add('bg-purple-700');
            } else {
                panel.style.display = 'none';
                btn.classList.remove('bg-purple-700');
            }
            
            // Atualizar ícones
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
        
        // Redimensionar preview
        (function() {
            const resizer = document.getElementById('previewResizer');
            const previewContainer = document.getElementById('previewContainer');
            const mainContainer = document.querySelector('.ide-main');
            
            if (!resizer || !previewContainer || !mainContainer) return;
            
            let isResizing = false;
            let startX = 0;
            let startWidth = 0;
            
            resizer.addEventListener('mousedown', function(e) {
                isResizing = true;
                startX = e.clientX;
                startWidth = previewContainer.offsetWidth;
                document.body.style.cursor = 'col-resize';
                document.body.style.userSelect = 'none';
                e.preventDefault();
            });
            
            document.addEventListener('mousemove', function(e) {
                if (!isResizing) return;
                
                const width = mainContainer.offsetWidth;
                const diff = startX - e.clientX;
                const newWidth = startWidth + diff;
                const minWidth = 300;
                const maxWidth = width - 400; // Deixa espaço mínimo para o editor
                
                if (newWidth >= minWidth && newWidth <= maxWidth) {
                    previewContainer.style.width = newWidth + 'px';
                    previewContainer.style.transition = 'none';
                }
            });
            
            document.addEventListener('mouseup', function() {
                if (isResizing) {
                    isResizing = false;
                    document.body.style.cursor = '';
                    document.body.style.userSelect = '';
                    previewContainer.style.transition = 'all 0.3s ease';
                }
            });
        })();
        
        // Enviar mensagem para IA
        function sendAIMessage() {
            const input = document.getElementById('aiInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            // Adicionar mensagem do usuário
            addAIMessage('user', message);
            input.value = '';
            input.rows = 1;
            
            // Mostrar indicador de digitação
            document.getElementById('aiTyping').style.display = 'block';
            const messagesContainer = document.getElementById('aiMessages');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            
            // Obter código atual do editor
            let code = '';
            if (editor) {
                try {
                    code = editor.getValue();
                } catch (error) {
                    console.error('Erro ao obter código do editor:', error);
                    code = '';
                }
            }
            
            // Enviar para API de IA
            fetch('api/ai-assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    code: code,
                    project_data: projectData
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('aiTyping').style.display = 'none';
                if (data.success) {
                    addAIMessage('assistant', data.response);
                } else {
                    addAIMessage('assistant', 'Desculpe, ocorreu um erro: ' + (data.error || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                document.getElementById('aiTyping').style.display = 'none';
                console.error('Erro:', error);
                addAIMessage('assistant', 'Desculpe, não foi possível conectar com o assistente de IA. Verifique sua conexão.');
            });
        }
        
        // Adicionar mensagem no chat de IA
        function addAIMessage(type, content) {
            const messagesContainer = document.getElementById('aiMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = type === 'user' ? 'message-user' : 'message-assistant';
            
            let formattedContent = content;
            if (type === 'assistant') {
                formattedContent = marked.parse(content);
            } else {
                formattedContent = escapeHtml(content);
            }
            
            messageDiv.innerHTML = `
                <div class="message-role ${type === 'user' ? 'role-user' : 'role-assistant'}">
                    ${type === 'user' ? 'YOU' : 'AI'}
                </div>
                <div class="message-content">${formattedContent}</div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            
            if (type === 'assistant') {
                messageDiv.querySelectorAll('pre code').forEach((block) => {
                    hljs.highlightElement(block);
                });
            }
            
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
        
        // Auto-expand textarea
        const aiInput = document.getElementById('aiInput');
        const mainContainer = document.getElementById('main');
        if (aiInput && mainContainer) {
            // Inicializar altura
            mainContainer.style.height = aiInput.scrollHeight + 'px';
            
            aiInput.addEventListener('input', function() {
                this.style.height = 'auto';
                const newHeight = this.scrollHeight;
                this.style.height = newHeight + 'px';
                
                // Sincronizar altura do container
                mainContainer.style.height = newHeight + 'px';
                
                if (newHeight > 200) {
                    this.style.overflowY = 'auto';
                    this.style.height = '200px';
                    mainContainer.style.height = '200px';
                } else {
                    this.style.overflowY = 'hidden';
                }
            });
        }
        
        // Escapar HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML.replace(/\n/g, '<br>');
        }
        
        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('mobileDropdown');
            if (dropdown && !dropdown.contains(event.target)) {
                closeMobileDropdown();
            }
        });

        // Atalhos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl+S ou Cmd+S para salvar
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveCode();
            }
            
            // Ctrl+K ou Cmd+K para toggle AI
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                toggleAI();
            }
        });
        
        // Atualizar horário e bateria do mobile
        function updateMobileStatus() {
            const timeElement = document.getElementById('mobile-time');
            const batteryFill = document.getElementById('battery-fill');
            const batteryLevel = document.getElementById('battery-level');
            
            if (!timeElement) return;
            
            // Atualizar horário
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            timeElement.textContent = `${hours}:${minutes}`;
            
            // Simular nível de bateria (85% como exemplo)
            const batteryPercent = 85;
            const batteryWidth = (batteryPercent / 100) * 15; // 15 é a largura do retângulo da bateria
            
            if (batteryFill) {
                batteryFill.setAttribute('width', batteryWidth);
                batteryFill.setAttribute('x', '2.5');
                
                // Mudar cor baseado no nível
                if (batteryPercent > 50) {
                    batteryFill.setAttribute('fill', '#4ade80'); // Verde
                } else if (batteryPercent > 20) {
                    batteryFill.setAttribute('fill', '#fbbf24'); // Amarelo
                } else {
                    batteryFill.setAttribute('fill', '#ef4444'); // Vermelho
                }
            }
            
            if (batteryLevel) {
                batteryLevel.textContent = `${Math.round(batteryPercent)}%`;
            }
        }
        
        // Atualizar a cada segundo para manter o horário preciso
        updateMobileStatus(); // Atualizar imediatamente
        setInterval(updateMobileStatus, 1000); // Atualizar a cada segundo
        
        // Atualizar também quando o preview mobile for exibido
        const previewContainer = document.getElementById('previewContainer');
        if (previewContainer) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        if (previewContainer.classList.contains('is-mobile')) {
                            updateMobileStatus();
                            // Atualizar ícones quando mudar para mobile
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }
                        }
                    }
                });
            });
            observer.observe(previewContainer, { attributes: true });
        }
        
        // Funções do Menu de Controles Mobile
        let mobileDarkMode = false;
        let mobileZoom = 1;
        let mobileRotated = false;
        
        function toggleMobileDarkMode() {
            mobileDarkMode = !mobileDarkMode;
            const iframe = document.getElementById('preview-frame-mobile');
            const btn = document.getElementById('darkModeBtn');
            
            if (iframe && iframe.contentDocument) {
                const body = iframe.contentDocument.body;
                if (body) {
                    if (mobileDarkMode) {
                        body.style.filter = 'invert(1) hue-rotate(180deg)';
                        body.style.backgroundColor = '#000000';
                        btn.classList.add('active');
                    } else {
                        body.style.filter = '';
                        body.style.backgroundColor = '#ffffff';
                        btn.classList.remove('active');
                    }
                }
            }
            
            // Atualizar ícone
            if (typeof lucide !== 'undefined') {
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.setAttribute('data-lucide', mobileDarkMode ? 'sun' : 'moon');
                    lucide.createIcons();
                }
            }
        }
        
        function rotateDevice() {
            mobileRotated = !mobileRotated;
            const deviceContainer = document.querySelector('.device-container');
            const btn = document.getElementById('rotateBtn');
            
            if (deviceContainer) {
                if (mobileRotated) {
                    // Rotacionar para horizontal (landscape)
                    deviceContainer.style.transform = 'rotate(90deg)';
                    deviceContainer.style.width = '740px';
                    deviceContainer.style.height = '360px';
                    btn.classList.add('active');
                } else {
                    // Voltar para vertical (portrait)
                    deviceContainer.style.transform = 'rotate(0deg)';
                    deviceContainer.style.width = '360px';
                    deviceContainer.style.height = '740px';
                    btn.classList.remove('active');
                }
            }
        }
        
        function zoomInMobile() {
            mobileZoom = Math.min(mobileZoom + 0.1, 2);
            applyMobileZoom();
        }
        
        function zoomOutMobile() {
            mobileZoom = Math.max(mobileZoom - 0.1, 0.5);
            applyMobileZoom();
        }
        
        function applyMobileZoom() {
            const iframe = document.getElementById('preview-frame-mobile');
            if (iframe && iframe.contentDocument) {
                const body = iframe.contentDocument.body;
                if (body) {
                    body.style.transform = `scale(${mobileZoom})`;
                    body.style.transformOrigin = 'top left';
                }
            }
        }
        
        function resetMobileView() {
            mobileDarkMode = false;
            mobileZoom = 1;
            mobileRotated = false;
            
            const deviceContainer = document.querySelector('.device-container');
            const iframe = document.getElementById('preview-frame-mobile');
            const darkBtn = document.getElementById('darkModeBtn');
            const rotateBtn = document.getElementById('rotateBtn');
            
            if (deviceContainer) {
                deviceContainer.style.transform = '';
                deviceContainer.style.width = '';
                deviceContainer.style.height = '';
            }
            
            if (iframe && iframe.contentDocument) {
                const body = iframe.contentDocument.body;
                if (body) {
                    body.style.filter = '';
                    body.style.backgroundColor = '';
                    body.style.transform = '';
                }
            }
            
            if (darkBtn) darkBtn.classList.remove('active');
            if (rotateBtn) rotateBtn.classList.remove('active');
            
            // Atualizar ícones
            if (typeof lucide !== 'undefined') {
                const icon = darkBtn.querySelector('i');
                if (icon) {
                    icon.setAttribute('data-lucide', 'moon');
                    lucide.createIcons();
                }
            }
        }
        
        function fullscreenMobile() {
            const deviceContainer = document.querySelector('.device-container');
            if (!deviceContainer) return;
            
            if (!document.fullscreenElement) {
                deviceContainer.requestFullscreen().catch(err => {
                    console.log('Erro ao entrar em fullscreen:', err);
                });
            } else {
                document.exitFullscreen();
            }
        }
        
        // Atualizar ícones quando o menu for exibido
        const mobileControlsMenu = document.getElementById('mobileControlsMenu');
        if (mobileControlsMenu) {
            const observer = new MutationObserver(function() {
                if (previewContainer && previewContainer.classList.contains('is-mobile')) {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }
            });
            observer.observe(mobileControlsMenu, { attributes: true, childList: true });
        }
    </script>
</body>
</html>


