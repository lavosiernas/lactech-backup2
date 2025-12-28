<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>SafeNode Mail - E-mails Transacionais</title>
    <meta name="description" content="A API de e-mails mais simples e confiável para desenvolvedores. Integre em minutos.">
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    
    <!-- Added Instrument Serif for elegant headings -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --bg: #000;
            --bg-subtle: #0a0a0a;
            --border: #1a1a1a;
            --border-hover: #262626;
            --text: #fafafa;
            --text-secondary: #a1a1a1;
            --text-tertiary: #666;
            --accent: #fff;
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --font-serif: 'Instrument Serif', Georgia, serif;
        }
        
        html {
            scroll-behavior: smooth;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body {
            font-family: var(--font-sans);
            background: var(--bg);
            color: var(--text-secondary);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        @media (max-width: 640px) {
            .container { padding: 0 16px; }
        }
        
        /* Typography */
        .serif {
            font-family: var(--font-serif);
        }
        
        .serif-italic {
            font-family: var(--font-serif);
            font-style: italic;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            font-size: 15px;
            font-weight: 500;
            border-radius: 9999px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            font-family: var(--font-sans);
        }
        
        .btn-primary {
            background: var(--text);
            color: var(--bg);
        }
        
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--border);
        }
        
        .btn-secondary:hover {
            border-color: var(--border-hover);
            background: var(--bg-subtle);
        }

        /* Header */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 16px 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid transparent;
            transition: border-color 0.3s;
        }
        
        header.scrolled {
            border-bottom-color: var(--border);
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 17px;
            color: var(--text);
            text-decoration: none;
        }
        
        .logo-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        nav {
            display: flex;
            align-items: center;
            gap: 32px;
        }
        
        nav a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }
        
        nav a:hover {
            color: var(--text);
        }
        
        .header-buttons {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-buttons .btn {
            padding: 10px 20px;
            font-size: 14px;
        }

        /* Mobile menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text);
            cursor: pointer;
            padding: 8px;
        }

        @media (max-width: 900px) {
            nav, .header-buttons { display: none; }
            .mobile-menu-btn { display: block; }
        }

        .mobile-menu {
            display: none;
            position: fixed;
            top: 65px;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--bg);
            padding: 24px;
            flex-direction: column;
            gap: 8px;
            z-index: 99;
        }

        .mobile-menu.active { display: flex; }

        .mobile-menu a {
            color: var(--text);
            text-decoration: none;
            font-size: 18px;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
        }

        .mobile-menu .btn {
            margin-top: 16px;
            width: 100%;
        }

        /* New Aurora effect for hero background */
        .hero {
            position: relative;
            padding: 180px 0 120px;
            text-align: center;
            overflow: hidden;
        }

        @media (max-width: 768px) {
            .hero { padding: 140px 0 80px; }
        }

        @media (max-width: 480px) {
            .hero { padding: 120px 0 60px; }
        }

        /* Aurora Background */
        .aurora {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            opacity: 0.5;
        }

        .aurora-beam {
            position: absolute;
            width: 60%;
            height: 600px;
            background: radial-gradient(ellipse at center, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.05) 30%, transparent 70%);
            filter: blur(60px);
            animation: aurora-float 8s ease-in-out infinite;
        }

        .aurora-beam:nth-child(1) {
            top: -200px;
            left: 10%;
            animation-delay: 0s;
        }

        .aurora-beam:nth-child(2) {
            top: -100px;
            right: 10%;
            width: 50%;
            animation-delay: -2s;
            animation-duration: 10s;
        }

        .aurora-beam:nth-child(3) {
            top: 0;
            left: 30%;
            width: 40%;
            height: 400px;
            animation-delay: -4s;
            animation-duration: 12s;
        }

        @keyframes aurora-float {
            0%, 100% {
                transform: translateY(0) rotate(-5deg) scale(1);
                opacity: 0.4;
            }
            25% {
                transform: translateY(20px) rotate(0deg) scale(1.05);
                opacity: 0.6;
            }
            50% {
                transform: translateY(-10px) rotate(5deg) scale(1);
                opacity: 0.5;
            }
            75% {
                transform: translateY(15px) rotate(-2deg) scale(1.02);
                opacity: 0.7;
            }
        }

        /* Grid pattern overlay */
        .hero-grid {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse 80% 50% at 50% 0%, black, transparent);
            -webkit-mask-image: radial-gradient(ellipse 80% 50% at 50% 0%, black, transparent);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 100px;
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 40px;
            backdrop-filter: blur(10px);
        }

        .hero-badge-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            box-shadow: 0 0 12px rgba(34, 197, 94, 0.5);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(0.9); }
        }

        /* Elegant serif heading */
        .hero h1 {
            font-family: var(--font-serif);
            font-size: clamp(48px, 12vw, 100px);
            font-weight: 400;
            color: var(--text);
            line-height: 1.05;
            letter-spacing: -0.02em;
            margin-bottom: 28px;
        }

        .hero h1 em {
            font-style: italic;
            background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,0.7) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: clamp(16px, 2.5vw, 19px);
            color: var(--text-secondary);
            max-width: 460px;
            margin: 0 auto 48px;
            line-height: 1.7;
            font-weight: 400;
        }

        .hero-buttons {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }

        @media (max-width: 480px) {
            .hero-buttons {
                flex-direction: column;
                gap: 12px;
            }
            .hero-buttons .btn {
                width: 100%;
            }
        }

        .hero-buttons .btn {
            padding: 14px 32px;
            font-size: 15px;
        }

        /* Clients/Trust */
        .hero-clients {
            margin-top: 80px;
            padding-top: 48px;
            border-top: 1px solid var(--border);
        }

        .hero-clients p {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-tertiary);
            margin-bottom: 24px;
        }

        .hero-clients-logos {
            display: flex;
            align-items: center;
            gap: 48px;
            opacity: 0.5;
            overflow: hidden;
            width: 100%;
            position: relative;
        }

        .hero-clients-logos-wrapper {
            display: flex;
            align-items: center;
            gap: 48px;
            animation: scroll-logos 30s linear infinite;
            will-change: transform;
        }

        .hero-clients-logos:hover .hero-clients-logos-wrapper {
            animation-play-state: paused;
        }

        @keyframes scroll-logos {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(calc(-50% - 24px));
            }
        }

        @media (max-width: 640px) {
            .hero-clients-logos { gap: 32px; }
            .hero-clients-logos-wrapper { gap: 32px; }
        }

        .client-logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 32px;
            font-weight: 600;
            color: var(--text-secondary);
            letter-spacing: -0.02em;
            flex-shrink: 0;
        }

        .client-logo img {
            width: 120px;
            height: 120px;
            object-fit: contain;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .client-logo:hover img {
            opacity: 1;
        }

        /* Stats Bar */
        .stats-bar {
            padding: 80px 0;
            border-bottom: 1px solid var(--border);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 32px;
        }

        @media (max-width: 900px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; gap: 24px; }
        }

        .stat-item {
            text-align: center;
            padding: 24px;
        }

        .stat-value {
            font-family: var(--font-serif);
            font-size: clamp(36px, 5vw, 48px);
            font-weight: 400;
            color: var(--text);
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* Features Section */
        .features {
            padding: 120px 0;
        }

        @media (max-width: 768px) {
            .features { padding: 80px 0; }
        }

        .section-header {
            text-align: center;
            max-width: 600px;
            margin: 0 auto 80px;
        }

        @media (max-width: 768px) {
            .section-header { margin-bottom: 48px; }
        }

        .section-label {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--text-tertiary);
            margin-bottom: 16px;
        }

        .section-title {
            font-family: var(--font-serif);
            font-size: clamp(32px, 5vw, 48px);
            font-weight: 400;
            color: var(--text);
            line-height: 1.15;
            margin-bottom: 20px;
        }

        .section-title em {
            font-style: italic;
        }

        .section-desc {
            font-size: 17px;
            color: var(--text-secondary);
            line-height: 1.7;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            background: var(--border);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        @media (max-width: 900px) {
            .features-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 600px) {
            .features-grid { grid-template-columns: 1fr; }
        }

        .feature-card {
            background: var(--bg);
            padding: 40px 32px;
            transition: background 0.3s;
        }

        .feature-card:hover {
            background: var(--bg-subtle);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
        }

        .feature-icon svg {
            width: 22px;
            height: 22px;
            color: var(--text);
        }

        .feature-card h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 12px;
        }

        .feature-card p {
            font-size: 15px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* How it Works */
        .how-it-works {
            padding: 120px 0;
            border-top: 1px solid var(--border);
            background: var(--bg);
        }

        @media (max-width: 768px) {
            .how-it-works { padding: 80px 0; }
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 48px;
        }

        @media (max-width: 900px) {
            .steps-grid { gap: 32px; }
        }

        @media (max-width: 640px) {
            .steps-grid { grid-template-columns: 1fr; gap: 48px; }
        }

        .step {
            text-align: center;
        }

        .step-number {
            font-family: var(--font-serif);
            font-size: 72px;
            font-weight: 400;
            color: var(--border-hover);
            line-height: 1;
            margin-bottom: 24px;
        }

        .step h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 12px;
        }

        .step p {
            font-size: 15px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Network Visualization Section - Design Incrível e Limpo */
        .network-section {
            padding: 140px 0;
            background: #000000;
            position: relative;
            overflow: hidden;
        }

        .network-container {
            position: relative;
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .network-visual {
            position: relative;
            width: 100%;
            margin-top: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            padding: 60px 0;
        }

        .network-node {
            flex-shrink: 0;
            width: 180px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .network-node-icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            position: relative;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .network-node-icon-wrapper::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 20px;
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: -1;
        }

        .network-node.node-app .network-node-icon-wrapper {
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.15);
        }

        .network-node.node-app .network-node-icon-wrapper::before {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), transparent);
        }

        .network-node.node-safenode .network-node-icon-wrapper {
            background: rgba(139, 92, 246, 0.08);
            border: 1px solid rgba(139, 92, 246, 0.15);
        }

        .network-node.node-safenode .network-node-icon-wrapper::before {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), transparent);
        }

        .network-node.node-dest .network-node-icon-wrapper {
            background: rgba(34, 197, 94, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.15);
        }

        .network-node.node-dest .network-node-icon-wrapper::before {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), transparent);
        }

        .network-node:hover .network-node-icon-wrapper {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .network-node:hover .network-node-icon-wrapper::before {
            opacity: 1;
        }

        .network-node-icon-wrapper i {
            width: 32px;
            height: 32px;
        }

        .network-node.node-app .network-node-icon-wrapper i {
            color: #60a5fa;
        }

        .network-node.node-safenode .network-node-icon-wrapper i {
            color: #a78bfa;
        }

        .network-node.node-dest .network-node-icon-wrapper i {
            color: #4ade80;
        }

        .network-node-title {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 6px;
            text-align: center;
        }

        .network-node-desc {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.4);
            text-align: center;
            font-weight: 400;
        }

        .network-connection {
            flex: 1;
            height: 2px;
            position: relative;
            margin: 0 20px;
            max-width: 200px;
        }

        .connection-line {
            width: 100%;
            height: 2px;
            background: rgba(255, 255, 255, 0.08);
            position: relative;
            border-radius: 1px;
        }

        .connection-line::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent,
                rgba(255, 255, 255, 0.2) 50%,
                transparent
            );
            border-radius: 1px;
        }

        .data-flow {
            position: absolute;
            top: 50%;
            left: 0;
            width: 8px;
            height: 8px;
            background: #ffffff;
            border-radius: 50%;
            transform: translateY(-50%);
            box-shadow: 0 0 16px rgba(255, 255, 255, 0.6);
            animation: flow 3s infinite linear;
        }

        .data-flow:nth-child(2) {
            animation-delay: 1s;
            width: 6px;
            height: 6px;
        }

        .data-flow:nth-child(3) {
            animation-delay: 2s;
            width: 4px;
            height: 4px;
        }

        @keyframes flow {
            0% {
                left: 0;
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                left: 100%;
                opacity: 0;
            }
        }

        .connection-label {
            position: absolute;
            top: -32px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.3);
            white-space: nowrap;
            font-weight: 500;
        }

        .network-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-top: 100px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .network-stat {
            text-align: center;
            padding: 24px;
            background: rgba(10, 10, 10, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            transition: all 0.3s ease;
        }

        .network-stat:hover {
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(10, 10, 10, 0.6);
        }

        .network-stat-value {
            font-family: var(--font-serif);
            font-size: 36px;
            font-weight: 400;
            color: #ffffff;
            margin-bottom: 8px;
            line-height: 1;
        }

        .network-stat-label {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 500;
        }

        @media (max-width: 900px) {
            .network-visual {
                flex-direction: column;
                gap: 60px;
                padding: 40px 0;
            }

            .network-connection {
                width: 2px;
                height: 60px;
                margin: 0;
                max-width: none;
            }

            .connection-line {
                width: 2px;
                height: 100%;
            }

            .connection-line::after {
                background: linear-gradient(180deg, 
                    transparent,
                    rgba(255, 255, 255, 0.2) 50%,
                    transparent
                );
            }

            .data-flow {
                top: 0;
                left: 50%;
                transform: translateX(-50%);
                animation: flowVertical 3s infinite linear;
            }

            @keyframes flowVertical {
                0% {
                    top: 0;
                    opacity: 0;
                }
                10% {
                    opacity: 1;
                }
                90% {
                    opacity: 1;
                }
                100% {
                    top: 100%;
                    opacity: 0;
                }
            }

            .connection-label {
                top: 50%;
                left: -120px;
                transform: translateY(-50%);
                white-space: nowrap;
            }

            .network-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
        }

        @media (max-width: 480px) {
            .network-stats {
                grid-template-columns: 1fr;
            }

            .network-node {
                width: 160px;
            }
        }


        /* API Section */
        .api-section {
            padding: 120px 0;
            border-top: 1px solid var(--border);
        }

        @media (max-width: 768px) {
            .api-section { padding: 80px 0; }
        }

        .api-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }

        @media (max-width: 900px) {
            .api-content { 
                grid-template-columns: 1fr;
                gap: 48px;
            }
        }

        .api-text .section-title {
            text-align: left;
            margin-bottom: 24px;
        }

        .api-text p {
            font-size: 17px;
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 24px;
        }

        .api-list {
            list-style: none;
            margin-bottom: 32px;
        }

        .api-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            color: var(--text-secondary);
            padding: 10px 0;
        }

        .api-list li svg {
            width: 18px;
            height: 18px;
            color: #22c55e;
            flex-shrink: 0;
        }

        /* Code Window */
        .code-window {
            background: var(--bg-subtle);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .code-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 16px 20px;
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid var(--border);
        }

        .code-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--border);
        }

        .code-dot:nth-child(1) { background: #ef4444; }
        .code-dot:nth-child(2) { background: #eab308; }
        .code-dot:nth-child(3) { background: #22c55e; }

        .code-title {
            margin-left: 12px;
            font-size: 13px;
            color: var(--text-tertiary);
            font-family: 'SF Mono', 'Fira Code', monospace;
        }

        .code-body {
            padding: 24px;
            font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
            font-size: 14px;
            line-height: 1.8;
            color: var(--text-secondary);
            overflow-x: auto;
        }

        @media (max-width: 480px) {
            .code-body {
                font-size: 12px;
                padding: 16px;
            }
        }

        .code-body .kw { color: #c084fc; }
        .code-body .fn { color: #60a5fa; }
        .code-body .str { color: #4ade80; }
        .code-body .cm { color: var(--text-tertiary); }

        /* CTA Section */
        .cta-section {
            padding: 120px 0;
            border-top: 1px solid var(--border);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        @media (max-width: 768px) {
            .cta-section { padding: 80px 0; }
        }

        .cta-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,255,255,0.03) 0%, transparent 70%);
            pointer-events: none;
        }

        .cta-content {
            position: relative;
            z-index: 1;
        }

        .cta-title {
            font-family: var(--font-serif);
            font-size: clamp(36px, 6vw, 56px);
            font-weight: 400;
            color: var(--text);
            line-height: 1.15;
            margin-bottom: 20px;
        }

        .cta-title em {
            font-style: italic;
        }

        .cta-subtitle {
            font-size: 18px;
            color: var(--text-secondary);
            max-width: 460px;
            margin: 0 auto 40px;
        }

        /* Footer */
        footer {
            padding: 80px 0 40px;
            border-top: 1px solid var(--border);
        }

        @media (max-width: 768px) {
            footer { padding: 60px 0 32px; }
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr repeat(3, 1fr);
            gap: 64px;
            margin-bottom: 64px;
        }

        @media (max-width: 900px) {
            .footer-grid { 
                grid-template-columns: repeat(2, 1fr);
                gap: 48px 32px;
            }
        }

        @media (max-width: 480px) {
            .footer-grid { 
                grid-template-columns: 1fr;
                gap: 40px;
            }
        }

        .footer-brand p {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.7;
            margin-top: 16px;
            max-width: 280px;
        }

        .footer-col h4 {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 20px;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col li {
            margin-bottom: 12px;
        }

        .footer-col a {
            font-size: 14px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-col a:hover {
            color: var(--text);
        }

        .footer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 32px;
            border-top: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 16px;
        }

        .footer-bottom p {
            font-size: 13px;
            color: var(--text-tertiary);
        }

        .footer-links {
            display: flex;
            gap: 24px;
        }

        .footer-links a {
            font-size: 13px;
            color: var(--text-tertiary);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: var(--text);
        }

        /* Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 0.8s ease forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in:nth-child(1) { animation-delay: 0.1s; }
        .fade-in:nth-child(2) { animation-delay: 0.2s; }
        .fade-in:nth-child(3) { animation-delay: 0.3s; }
        .fade-in:nth-child(4) { animation-delay: 0.4s; }
        .fade-in:nth-child(5) { animation-delay: 0.5s; }
    </style>
</head>
<body>
    <header id="header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <div class="logo-icon">
                        <img src="assets/img/logos (6).png" alt="SafeNode">
                    </div>
                    SafeNode
                </a>
                
                <nav>
                    <a href="#features">Recursos</a>
                    <a href="#api">API</a>
                    <a href="docs.php">Docs</a>
                </nav>
                
                <div class="header-buttons">
                    <a href="login.php" class="btn btn-secondary">Entrar</a>
                    <a href="mail.php" class="btn btn-primary">Começar</a>
                </div>

                <button class="mobile-menu-btn" onclick="toggleMenu()">
                    <i data-lucide="menu" style="width: 24px; height: 24px;"></i>
                </button>
            </div>
        </div>
    </header>

    <div class="mobile-menu" id="mobileMenu">
        <a href="#features">Recursos</a>
        <a href="#api">API</a>
        <a href="docs.php">Documentação</a>
        <a href="login.php" class="btn btn-secondary">Entrar</a>
        <a href="mail.php" class="btn btn-primary">Começar grátis</a>
    </div>

    <!-- Hero Section with Aurora -->
    <section class="hero">
        <div class="aurora">
            <div class="aurora-beam"></div>
            <div class="aurora-beam"></div>
            <div class="aurora-beam"></div>
        </div>
        <div class="hero-grid"></div>
        
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge fade-in">
                    <span class="hero-badge-dot"></span>
                    v2.0 disponível agora
                </div>
                
                <h1 class="fade-in">
                    E-mails que<br><em>sempre chegam</em>
                </h1>
                
                <p class="hero-subtitle fade-in">
                    A API de e-mails transacionais para desenvolvedores que valorizam simplicidade e confiabilidade.
                </p>
                
                <div class="hero-buttons fade-in">
                    <a href="mail.php" class="btn btn-primary">
                        Começar grátis
                        <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
                    </a>
                    <a href="#api" class="btn btn-secondary">
                        Ver documentação
                    </a>
                </div>

                <div class="hero-clients fade-in">
                    <p>Empresas que confiam na SafeNode</p>
                    <div class="hero-clients-logos">
                        <div class="hero-clients-logos-wrapper">
                            <span class="client-logo">
                                <img src="assets/img/stone.png" alt="Stone">
                            </span>
                            <span class="client-logo">
                                <img src="assets/img/creditas.png" alt="Creditas">
                            </span>
                            <span class="client-logo">
                                <img src="assets/img/contazul.png" alt="ContaAzul">
                            </span>
                            <span class="client-logo">
                                <img src="assets/img/agrosmart.png" alt="agroSmart">
                            </span>
                            <span class="client-logo">
                                LacTech
                            </span>
                            <!-- Duplicar para scroll infinito -->
                            <span class="client-logo">
                                <img src="assets/img/stone.png" alt="Stone">
                            </span>
                            <span class="client-logo">
                                <img src="assets/img/creditas.png" alt="Creditas">
                            </span>
                            <span class="client-logo">
                                <img src="assets/img/contazul.png" alt="ContaAzul">
                            </span>
                            <span class="client-logo">
                                <img src="assets/img/agrosmart.png" alt="agroSmart">
                            </span>
                            <span class="client-logo">
                                LacTech
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Bar -->
    <section class="stats-bar">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value">99.9%</div>
                    <div class="stat-label">Taxa de entrega</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">&lt;50ms</div>
                    <div class="stat-label">Latência média</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">10M+</div>
                    <div class="stat-label">E-mails por dia</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">2.5k+</div>
                    <div class="stat-label">Desenvolvedores</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <p class="section-label">Recursos</p>
                <h2 class="section-title">Tudo que você precisa para <em>escalar</em></h2>
                <p class="section-desc">
                    Infraestrutura robusta para garantir que seus e-mails cheguem sempre.
                </p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i data-lucide="zap"></i>
                    </div>
                    <h3>Entrega Instantânea</h3>
                    <p>E-mails enviados em milissegundos com nossa infraestrutura global distribuída.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i data-lucide="shield-check"></i>
                    </div>
                    <h3>SPF, DKIM & DMARC</h3>
                    <p>Autenticação completa configurada automaticamente para máxima entregabilidade.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i data-lucide="webhook"></i>
                    </div>
                    <h3>Webhooks em Tempo Real</h3>
                    <p>Receba notificações instantâneas de entregas, aberturas e cliques.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i data-lucide="code-2"></i>
                    </div>
                    <h3>SDKs Oficiais</h3>
                    <p>Bibliotecas para Node.js, Python, PHP, Ruby, Go e mais linguagens.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i data-lucide="layout-template"></i>
                    </div>
                    <h3>Templates Dinâmicos</h3>
                    <p>Crie templates responsivos com variáveis e lógica condicional.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i data-lucide="bar-chart-3"></i>
                    </div>
                    <h3>Analytics Detalhado</h3>
                    <p>Dashboard completo com métricas de engajamento e performance.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section class="how-it-works">
        <div class="container">
            <div class="section-header">
                <p class="section-label">Como Funciona</p>
                <h2 class="section-title">Três passos para <em>começar</em></h2>
            </div>
            
            <div class="steps-grid">
                <div class="step">
                    <div class="step-number">01</div>
                    <h3>Crie sua conta</h3>
                    <p>Cadastre-se gratuitamente e configure seu domínio em menos de 5 minutos.</p>
                </div>
                
                <div class="step">
                    <div class="step-number">02</div>
                    <h3>Integre a API</h3>
                    <p>Use nossos SDKs ou API REST para enviar seu primeiro e-mail.</p>
                </div>
                
                <div class="step">
                    <div class="step-number">03</div>
                    <h3>Escale com confiança</h3>
                    <p>Monitore métricas e escale para milhões de envios sem preocupação.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Network Visualization Section -->
    <section class="network-section">
        <div class="container">
            <div class="network-container">
                <div class="section-header">
                    <p class="section-label">Infraestrutura Global</p>
                    <h2 class="section-title">Fluxo de dados em <em>tempo real</em></h2>
                    <p class="section-desc">
                        Nossa rede distribuída garante latência mínima e entrega instantânea em qualquer lugar do mundo.
                    </p>
                </div>

                <div class="network-visual">
                    <div class="network-node node-app">
                        <div class="network-node-icon-wrapper">
                            <i data-lucide="code-2"></i>
                        </div>
                        <div class="network-node-title">Sua Aplicação</div>
                        <div class="network-node-desc">API Request</div>
                    </div>

                    <div class="network-connection">
                        <div class="connection-line">
                            <div class="data-flow"></div>
                            <div class="data-flow"></div>
                            <div class="data-flow"></div>
                        </div>
                        <span class="connection-label">TLS 1.3</span>
                    </div>

                    <div class="network-node node-safenode">
                        <div class="network-node-icon-wrapper">
                            <i data-lucide="shield-check"></i>
                        </div>
                        <div class="network-node-title">SafeNode Edge</div>
                        <div class="network-node-desc">Processamento</div>
                    </div>

                    <div class="network-connection">
                        <div class="connection-line">
                            <div class="data-flow"></div>
                            <div class="data-flow"></div>
                        </div>
                        <span class="connection-label">SPF/DKIM</span>
                    </div>

                    <div class="network-node node-dest">
                        <div class="network-node-icon-wrapper">
                            <i data-lucide="mail"></i>
                        </div>
                        <div class="network-node-title">Destinatário</div>
                        <div class="network-node-desc">Entregue</div>
                    </div>
                </div>
                
                <div class="network-stats">
                    <div class="network-stat">
                        <div class="network-stat-value">&lt;50ms</div>
                        <div class="network-stat-label">Latência</div>
                    </div>
                    <div class="network-stat">
                        <div class="network-stat-value">99.9%</div>
                        <div class="network-stat-label">Taxa Entrega</div>
                    </div>
                    <div class="network-stat">
                        <div class="network-stat-value">24/7</div>
                        <div class="network-stat-label">Monitoramento</div>
                    </div>
                    <div class="network-stat">
                        <div class="network-stat-value">Global</div>
                        <div class="network-stat-label">Rede CDN</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- API Section -->
    <section class="api-section" id="api">
        <div class="container">
            <div class="api-content">
                <div class="api-text">
                    <p class="section-label">API Simples</p>
                    <h2 class="section-title">Código limpo,<br><em>resultados reais</em></h2>
                    <p>
                        Nossa API foi projetada para desenvolvedores. Integre em minutos e foque no que realmente importa.
                    </p>
                    
                    <ul class="api-list">
                        <li>
                            <i data-lucide="check"></i>
                            Documentação completa com exemplos práticos
                        </li>
                        <li>
                            <i data-lucide="check"></i>
                            Suporte a envios em lote de até 1000 e-mails
                        </li>
                        <li>
                            <i data-lucide="check"></i>
                            Sandbox para testes sem custos
                        </li>
                        <li>
                            <i data-lucide="check"></i>
                            Rate limiting inteligente e retry automático
                        </li>
                    </ul>
                    
                    <a href="docs.php" class="btn btn-secondary">
                        Ver documentação completa
                        <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                    </a>
                </div>
                
                <div class="code-window">
                    <div class="code-header">
                        <span class="code-dot"></span>
                        <span class="code-dot"></span>
                        <span class="code-dot"></span>
                        <span class="code-title">send.js</span>
                    </div>
                    <div class="code-body">
                        <div><span class="cm">// Envie seu primeiro e-mail</span></div>
                        <div><span class="kw">import</span> { SafeNode } <span class="kw">from</span> <span class="str">'@safenode/mail'</span></div>
                        <div style="margin-top: 16px;"><span class="kw">const</span> safenode = <span class="kw">new</span> <span class="fn">SafeNode</span>()</div>
                        <div style="margin-top: 16px;"><span class="kw">await</span> safenode.emails.<span class="fn">send</span>({</div>
                        <div style="padding-left: 24px;">from: <span class="str">'oi@empresa.com'</span>,</div>
                        <div style="padding-left: 24px;">to: <span class="str">'cliente@email.com'</span>,</div>
                        <div style="padding-left: 24px;">subject: <span class="str">'Bem-vindo!'</span>,</div>
                        <div style="padding-left: 24px;">html: <span class="str">'&lt;h1&gt;Olá!&lt;/h1&gt;'</span></div>
                        <div>})</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-glow"></div>
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Pronto para <em>começar</em>?</h2>
                <p class="cta-subtitle">
                    Crie sua conta gratuitamente e envie seu primeiro e-mail em menos de 5 minutos.
                </p>
                <div class="hero-buttons">
                    <a href="mail.php" class="btn btn-primary">
                        Criar conta grátis
                        <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="index.php" class="logo">
                        <div class="logo-icon">
                            <img src="assets/img/logos (6).png" alt="SafeNode">
                        </div>
                        SafeNode
                    </a>
                    <p>A infraestrutura de e-mail transacional para desenvolvedores modernos.</p>
                </div>
                
                <div class="footer-col">
                    <h4>Produto</h4>
                    <ul>
                        <li><a href="#features">Recursos</a></li>
                        <li><a href="docs.php">Documentação</a></li>
                        <li><a href="changelog.php">Changelog</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Empresa</h4>
                    <ul>
                        <li><a href="sobre.php">Sobre</a></li>
                        <li><a href="blog.php">Blog</a></li>
                        <li><a href="carreiras.php">Carreiras</a></li>
                        <li><a href="contato.php">Contato</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="privacidade.php">Privacidade</a></li>
                        <li><a href="termos.php">Termos</a></li>
                        <li><a href="lgpd.php">LGPD</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 SafeNode. Todos os direitos reservados.</p>
                <div class="footer-links">
                    <a href="https://twitter.com/safenode">Twitter</a>
                    <a href="https://github.com/safenode">GitHub</a>
                    <a href="https://linkedin.com/company/safenode">LinkedIn</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
            
            // Re-inicializar após carregar a seção de rede
            setTimeout(() => {
                lucide.createIcons();
            }, 500);
        }

        // Mobile menu toggle
        function toggleMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('active');
        }

        // Header scroll effect
        window.addEventListener('scroll', () => {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Close mobile menu on link click
        document.querySelectorAll('.mobile-menu a').forEach(link => {
            link.addEventListener('click', () => {
                document.getElementById('mobileMenu').classList.remove('active');
            });
        });
    </script>
</body>
</html>
