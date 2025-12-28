<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KRON - Kernel for Resilient Operating Nodes</title>
    <meta name="description" content="Construimos software que resolve problemas reais. SafeNode para seguranca web. LacTech para gestao rural.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="asset/kron.png">
    <link rel="apple-touch-icon" href="asset/kron.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                    },
                    letterSpacing: {
                        'tightest': '-0.04em',
                        'tighter': '-0.02em',
                    },
                    transitionTimingFunction: {
                        'apple': 'cubic-bezier(0.25, 0.1, 0.25, 1)',
                        'apple-bounce': 'cubic-bezier(0.34, 1.56, 0.64, 1)',
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                    }
                }
            }
        }
    </script>
    
    <style>
        * {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }
        
        ::selection {
            background: rgba(255, 255, 255, 0.99);
            color: #000;
        }
        
        html {
            scroll-behavior: smooth;
            background: #000;
        }
        
        body {
            background: #000;
            color: #f5f5f7;
            overflow-x: hidden;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Text gradient */
        .text-gradient {
            background: linear-gradient(180deg, #ffffff 0%, rgba(255, 255, 255, 0.7) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .text-gradient-shine {
            background: linear-gradient(90deg, #ffffff 0%, rgba(255, 255, 255, 0.5) 50%, #ffffff 100%);
            background-size: 200% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shine 3s ease-in-out infinite;
        }
        
        @keyframes shine {
            0%, 100% { background-position: 200% center; }
            50% { background-position: 0% center; }
        }
        
        /* Floating animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }
        
        /* Glow animation */
        @keyframes glow {
            from { box-shadow: 0 0 20px rgba(255, 255, 255, 0.1); }
            to { box-shadow: 0 0 40px rgba(255, 255, 255, 0.2); }
        }
        
        /* Reveal animations */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 1s cubic-bezier(0.25, 0.1, 0.25, 1), 
                        transform 1s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }
        
        .reveal-left {
            opacity: 0;
            transform: translateX(-60px);
            transition: opacity 1s cubic-bezier(0.25, 0.1, 0.25, 1), 
                        transform 1s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        .reveal-left.active {
            opacity: 1;
            transform: translateX(0);
        }
        
        .reveal-right {
            opacity: 0;
            transform: translateX(60px);
            transition: opacity 1s cubic-bezier(0.25, 0.1, 0.25, 1), 
                        transform 1s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        .reveal-right.active {
                opacity: 1;
            transform: translateX(0);
        }
        
        .reveal-scale {
            opacity: 0;
            transform: scale(0.9);
            transition: opacity 0.8s cubic-bezier(0.25, 0.1, 0.25, 1), 
                        transform 0.8s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        .reveal-scale.active {
            opacity: 1;
            transform: scale(1);
        }
        
        /* Stagger children */
        .stagger-children > * {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s cubic-bezier(0.25, 0.1, 0.25, 1), 
                        transform 0.6s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        .stagger-children.active > *:nth-child(1) { transition-delay: 0ms; opacity: 1; transform: translateY(0); }
        .stagger-children.active > *:nth-child(2) { transition-delay: 100ms; opacity: 1; transform: translateY(0); }
        .stagger-children.active > *:nth-child(3) { transition-delay: 200ms; opacity: 1; transform: translateY(0); }
        .stagger-children.active > *:nth-child(4) { transition-delay: 300ms; opacity: 1; transform: translateY(0); }
        .stagger-children.active > *:nth-child(5) { transition-delay: 400ms; opacity: 1; transform: translateY(0); }
        .stagger-children.active > *:nth-child(6) { transition-delay: 500ms; opacity: 1; transform: translateY(0); }
        
        /* Glass morphism */
        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        
        .glass-card {
            background: linear-gradient(
                135deg,
                rgba(255, 255, 255, 0.08) 0%,
                rgba(255, 255, 255, 0.02) 50%,
                rgba(255, 255, 255, 0.05) 100%
            );
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        }
        
        /* 3D Card hover effect */
        .card-3d {
            transition: transform 0.6s cubic-bezier(0.25, 0.1, 0.25, 1),
                        box-shadow 0.6s cubic-bezier(0.25, 0.1, 0.25, 1);
            transform-style: preserve-3d;
            perspective: 1000px;
        }
        
        .card-3d:hover {
            transform: translateY(-12px) rotateX(2deg) rotateY(-2deg);
            box-shadow: 
                0 40px 80px -20px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 255, 255, 0.1),
                0 0 60px -10px rgba(255, 255, 255, 0.1);
        }
        
        .card-3d:hover .card-icon {
            transform: scale(1.15) translateZ(20px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .card-3d:hover .card-arrow {
            transform: translate(6px, -6px);
            opacity: 1;
        }
        
        .card-3d:hover .card-shine {
            opacity: 1;
            transform: translateX(100%);
        }
        
        .card-shine {
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            opacity: 0;
            transition: transform 0.8s, opacity 0.3s;
            pointer-events: none;
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(180deg, #ffffff 0%, #e8e8ed 100%);
            color: #0a0a0a;
            box-shadow: 
                0 2px 4px rgba(0, 0, 0, 0.3),
                0 8px 16px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
            transition: all 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
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
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: transform 0.6s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 
                0 4px 8px rgba(0, 0, 0, 0.4),
                0 16px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
        }
        
        .btn-primary:hover::before {
            transform: translateX(200%);
        }
        
        .btn-primary:active {
            transform: translateY(0) scale(0.98);
        }
        
        .btn-outline {
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: transparent;
            transition: all 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-outline::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.1);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        
        .btn-outline:hover {
            border-color: rgba(255, 255, 255, 0.4);
        }
        
        .btn-outline:hover::before {
            transform: scaleX(1);
        }
        
        /* Link underline animation */
        .link-hover {
            position: relative;
        }
        .link-hover::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: currentColor;
            transition: width 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        .link-hover:hover::after {
            width: 100%;
        }
        
        /* Magnetic button effect */
        .magnetic {
            transition: transform 0.3s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        
        /* Marquee */
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .marquee-track {
            animation: marquee 40s linear infinite;
        }
        
        .brands-carousel .marquee-track {
            animation: marquee 50s linear infinite;
        }
        .marquee-track:hover {
            animation-play-state: paused;
        }
        
        /* Footer Letreiro */
        @keyframes letreiro {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
        .letreiro-track {
            animation: letreiro 20s linear infinite;
        }
        .letreiro-track:hover {
            animation-play-state: paused;
        }
        
        /* Brands Carousel Premium Style */
        .brands-carousel {
            font-family: 'Playfair Display', serif;
        }
        
        .brand-name {
            font-family: 'Playfair Display', serif;
            font-weight: 500;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            font-size: 13px;
        }
        
        @media (min-width: 1024px) {
            .brand-name {
                font-size: 15px;
                letter-spacing: 0.08em;
            }
        }
        
        /* Feature item hover */
        .feature-item {
            transition: all 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
            position: relative;
        }
        
        .feature-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, transparent, rgba(255,255,255,0.3), transparent);
            transform: scaleY(0);
            transition: transform 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        
        .feature-item:hover {
            background: rgba(255, 255, 255, 0.02);
            padding-left: 1.5rem;
        }
        
        .feature-item:hover::before {
            transform: scaleY(1);
        }
        
        .feature-item:hover .feature-number {
            color: rgba(255, 255, 255, 0.6);
            transform: scale(1.1);
        }
        
        .feature-item:hover .feature-icon {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 0.1);
        }
        
        /* Hero backgrounds */
        .hero-gradient {
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(ellipse 100% 80% at 50% -30%, rgba(100, 100, 120, 0.15), transparent 70%),
                radial-gradient(ellipse 80% 60% at 80% 60%, rgba(60, 60, 80, 0.1), transparent 60%),
                radial-gradient(ellipse 60% 40% at 20% 80%, rgba(80, 80, 100, 0.08), transparent 50%);
        }
        
        .hero-video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
        }
        
        /* Nebula effect - Enhanced */
        .nebula-effect {
            position: absolute;
            inset: 0;
            overflow: hidden;
            z-index: 1;
        }
        
        .nebula-layer {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            mix-blend-mode: screen;
            animation: nebula-drift 25s ease-in-out infinite;
        }
        
        .nebula-layer-1 {
            width: 1000px;
            height: 800px;
            background: radial-gradient(circle, rgba(120, 180, 255, 0.7) 0%, rgba(100, 150, 255, 0.4) 30%, rgba(80, 120, 200, 0.2) 50%, transparent 75%);
            top: -300px;
            left: -300px;
            animation-duration: 30s;
            animation-delay: 0s;
        }
        
        .nebula-layer-2 {
            width: 900px;
            height: 1000px;
            background: radial-gradient(circle, rgba(180, 120, 255, 0.65) 0%, rgba(150, 100, 255, 0.4) 30%, rgba(120, 80, 200, 0.2) 50%, transparent 75%);
            top: 20%;
            right: -200px;
            animation-duration: 35s;
            animation-delay: -7s;
        }
        
        .nebula-layer-3 {
            width: 1100px;
            height: 700px;
            background: radial-gradient(circle, rgba(255, 180, 120, 0.6) 0%, rgba(255, 150, 100, 0.35) 30%, rgba(220, 120, 80, 0.2) 50%, transparent 75%);
            bottom: -150px;
            left: 15%;
            animation-duration: 40s;
            animation-delay: -12s;
        }
        
        .nebula-layer-4 {
            width: 800px;
            height: 900px;
            background: radial-gradient(circle, rgba(120, 255, 220, 0.55) 0%, rgba(100, 255, 200, 0.3) 30%, rgba(80, 220, 180, 0.15) 50%, transparent 75%);
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-duration: 45s;
            animation-delay: -18s;
        }
        
        .nebula-layer-5 {
            width: 700px;
            height: 600px;
            background: radial-gradient(circle, rgba(200, 150, 255, 0.5) 0%, rgba(180, 120, 255, 0.3) 30%, rgba(150, 100, 220, 0.15) 50%, transparent 75%);
            top: 10%;
            left: 30%;
            animation-duration: 38s;
            animation-delay: -5s;
        }
        
        @keyframes nebula-drift {
            0%, 100% {
                transform: translate(0, 0) scale(1) rotate(0deg);
                opacity: 0.7;
            }
            20% {
                transform: translate(40px, -30px) scale(1.15) rotate(5deg);
                opacity: 0.85;
            }
            40% {
                transform: translate(-30px, 40px) scale(0.95) rotate(-5deg);
                opacity: 0.75;
            }
            60% {
                transform: translate(35px, 25px) scale(1.1) rotate(3deg);
                opacity: 0.8;
            }
            80% {
                transform: translate(-25px, -35px) scale(1.05) rotate(-3deg);
                opacity: 0.78;
            }
        }
        
        /* Subtle stars - refined */
        .stars-layer {
            position: absolute;
            inset: 0;
            background-image: 
                radial-gradient(1.5px 1.5px at 15% 25%, rgba(255,255,255,0.9), transparent),
                radial-gradient(1px 1px at 55% 65%, rgba(255,255,255,0.7), transparent),
                radial-gradient(1px 1px at 75% 15%, rgba(255,255,255,0.6), transparent),
                radial-gradient(1.5px 1.5px at 35% 75%, rgba(255,255,255,0.8), transparent),
                radial-gradient(1px 1px at 85% 45%, rgba(255,255,255,0.5), transparent),
                radial-gradient(1px 1px at 25% 85%, rgba(255,255,255,0.6), transparent),
                radial-gradient(1.5px 1.5px at 65% 35%, rgba(255,255,255,0.7), transparent),
                radial-gradient(1px 1px at 45% 55%, rgba(255,255,255,0.5), transparent);
            background-size: 100% 100%;
            opacity: 0.4;
            animation: stars-pulse 12s ease-in-out infinite;
        }
        
        @keyframes stars-pulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.5; }
        }
        
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                180deg,
                rgba(0, 0, 0, 0.2) 0%,
                rgba(0, 0, 0, 0.4) 40%,
                rgba(0, 0, 0, 0.85) 80%,
                rgba(0, 0, 0, 1) 100%
            );
            z-index: 2;
        }
        
        
        /* Nav states */
        nav {
            transition: all 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        
        nav.scrolled {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: saturate(180%) blur(20px);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        /* Header scroll animation */
        nav.scrolled .nav-container {
            height: 56px;
        }
        
        nav.scrolled .logo-full {
            opacity: 0;
            transform: translateX(-20px);
            pointer-events: none;
            width: 0;
            overflow: hidden;
        }
        
        nav.scrolled .logo-icon {
            opacity: 1;
            transform: translateX(-50%);
        }
        
        .logo-full {
            transition: all 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
            transform: translateX(0);
        }
        
        .logo-icon {
            position: absolute;
            left: 50%;
            transform: translateX(-50%) translateX(20px);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
            pointer-events: none;
        }
        
        nav.scrolled .logo-icon {
            pointer-events: auto;
        }
        
        /* Rotação apenas no ícone (imagem) */
        .logo-full img {
            transition: transform 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
            transform: rotate(0deg);
        }
        
        nav.scrolled .logo-full img {
            transform: rotate(-180deg);
        }
        
        .logo-icon img {
            transition: transform 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
            transform: rotate(180deg);
        }
        
        nav.scrolled .logo-icon img {
            transform: rotate(0deg);
        }
        
        .nav-container {
            transition: height 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        
        nav.scrolled .desktop-menu,
        nav.scrolled .cta-button {
            opacity: 0;
            pointer-events: none;
        }
        
        @media (max-width: 1023px) {
            nav.scrolled .nav-container {
                height: 56px;
            }
            
            nav.scrolled .mobile-btn {
                position: absolute;
                right: 1.5rem;
            }
            
            nav.scrolled .logo-icon {
                left: 50%;
                transform: translateX(-50%);
            }
        }
        
        @media (min-width: 1024px) {
            nav.scrolled .nav-container {
                height: 64px;
            }
        }
        
        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px) scale(0.8);
            transition: all 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }
        
        .back-to-top:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        }
        
        .back-to-top:active {
            transform: translateY(-2px) scale(0.95);
        }
        
        .back-to-top svg {
            width: 22px;
            height: 22px;
            stroke: white;
            stroke-width: 2;
            transition: transform 0.3s;
        }
        
        .back-to-top:hover svg {
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .back-to-top {
                bottom: 1.5rem;
                right: 1.5rem;
                width: 44px;
                height: 44px;
            }
            
            .back-to-top svg {
                width: 20px;
                height: 20px;
            }
        }
        
        /* Mobile menu */
        .mobile-menu {
            clip-path: circle(0% at calc(100% - 2rem) 2rem);
            transition: clip-path 0.6s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        
        .mobile-menu.active {
            clip-path: circle(150% at calc(100% - 2rem) 2rem);
        }
        
        /* Scroll indicator */
        @keyframes scroll-bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(8px); }
        }
        
        .scroll-indicator {
            animation: scroll-bounce 2s ease-in-out infinite;
        }
        
        /* Pulse line */
        @keyframes pulse-line {
            0%, 100% { transform: scaleX(0.3); opacity: 0.3; }
            50% { transform: scaleX(1); opacity: 1; }
        }
        
        .pulse-line {
            animation: pulse-line 2s cubic-bezier(0.25, 0.1, 0.25, 1) infinite;
            transform-origin: left;
        }
        
        /* Stats counter */
        .stat-number {
            font-feature-settings: 'tnum' on, 'lnum' on;
        }
        
        /* Social icons */
        .social-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.02);
            transition: all 0.4s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        
        .social-icon:hover {
            background: #ffffff;
            border-color: #ffffff;
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 10px 30px rgba(255, 255, 255, 0.2);
        }
        
        .social-icon:hover svg {
            stroke: #0a0a0a;
        }
        
        /* Noise texture */
        .noise::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
            opacity: 0.015;
            pointer-events: none;
            z-index: 9998;
        }
        
        /* Grid lines background */
        .grid-bg {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 80px 80px;
            mask-image: radial-gradient(ellipse 80% 50% at 50% 50%, black, transparent);
        }
        
        /* Scrollbar hide */
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        
        /* Cursor glow effect */
        .cursor-glow {
            position: fixed;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.03) 0%, transparent 70%);
            pointer-events: none;
            z-index: 9997;
            transform: translate(-50%, -50%);
            transition: opacity 0.3s;
        }
    </style>
</head>
<body class="bg-black text-[#f5f5f7] antialiased">

    <!-- Cursor glow effect -->
    <div id="cursor-glow" class="cursor-glow hidden lg:block opacity-0"></div>

    <!-- Navigation -->
    <nav id="main-nav" class="fixed top-0 w-full z-50">
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20">
            <div class="nav-container flex justify-between items-center h-16 lg:h-[68px] relative">
                
                <!-- Logo Full -->
                <a href="#" class="logo-full relative z-50 flex items-center gap-3 group magnetic">
                    <div class="relative">
                        <img src="asset/kron.png" alt="KRON" class="h-8 w-auto rounded-lg transition-all duration-500 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-white/10">
                        <div class="absolute inset-0 rounded-lg bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <span class="text-[16px] font-semibold tracking-tight transition-all duration-300">KRON</span>
                </a>
                
                <!-- Logo Icon (centered when scrolled) -->
                <a href="#" class="logo-icon z-50 flex items-center justify-center group">
                    <div class="relative">
                        <img src="asset/kron.png" alt="KRON" class="h-10 lg:h-12 w-auto rounded-lg transition-all duration-500 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-white/10">
                        <div class="absolute inset-0 rounded-lg bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                </a>
                
                <!-- Desktop Menu -->
                <div class="desktop-menu hidden lg:flex items-center gap-10 transition-opacity duration-300">
                    <a href="#produtos" class="text-[14px] font-medium text-white/60 hover:text-white transition-colors duration-300 link-hover">Produtos</a>
                    <a href="#sobre" class="text-[14px] font-medium text-white/60 hover:text-white transition-colors duration-300 link-hover">Sobre</a>
                    <a href="#contato" class="text-[14px] font-medium text-white/60 hover:text-white transition-colors duration-300 link-hover">Contato</a>
                </div>
                
                <!-- CTA Button -->
                <div class="cta-button hidden lg:block transition-opacity duration-300">
                    <a href="#contato" class="btn-primary px-5 py-2.5 rounded-full text-[13px] font-semibold inline-flex items-center gap-2">
                        <span>Fale Conosco</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3"/>
                        </svg>
                    </a>
                </div>
                
                <!-- Mobile Button -->
                <button id="menu-btn" class="mobile-btn lg:hidden relative z-50 w-12 h-12 flex flex-col justify-center items-center gap-1.5 rounded-xl hover:bg-white/5 transition-colors" aria-label="Menu">
                    <span class="w-5 h-[2px] bg-white/90 rounded-full transition-all duration-400" id="line1"></span>
                    <span class="w-5 h-[2px] bg-white/90 rounded-full transition-all duration-400" id="line2"></span>
                </button>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Menu -->
    <div id="mobile-menu" class="mobile-menu fixed inset-0 z-40 bg-black lg:hidden">
        <div class="h-full flex flex-col justify-center px-8">
            <nav class="space-y-4 stagger-children" id="mobile-nav">
                <a href="#produtos" class="block text-[48px] font-bold tracking-tight text-white/90 hover:text-white transition-colors">Produtos</a>
                <a href="#sobre" class="block text-[48px] font-bold tracking-tight text-white/90 hover:text-white transition-colors">Sobre</a>
                <a href="#contato" class="block text-[48px] font-bold tracking-tight text-white/90 hover:text-white transition-colors">Contato</a>
            </nav>
            <div class="absolute bottom-20 left-8 right-8">
                <div class="h-px bg-gradient-to-r from-white/20 via-white/10 to-transparent mb-6"></div>
                <a href="mailto:contato@kron.com.br" class="text-[14px] text-white/50 hover:text-white transition-colors">contato@kron.com.br</a>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="min-h-screen flex flex-col relative overflow-hidden">
        
        <!-- Background Video -->
        <video class="hero-video" autoplay muted loop playsinline>
            <source src="asset/Design sem nome (3).mp4" type="video/mp4">
            <!-- Fallback para imagem caso o vídeo não carregue -->
            <div class="absolute inset-0 bg-black"></div>
        </video>
        
        <!-- Overlay escuro para melhorar legibilidade -->
        <div class="absolute inset-0 bg-black/40 z-[2]"></div>
        
        <!-- Container Principal -->
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20 w-full relative z-10 flex-1 flex flex-col justify-between py-32 lg:py-40">
            
            <!-- Center Section: Main Content -->
            <div class="flex-1 flex items-center justify-center">
                <div class="text-center max-w-4xl mx-auto w-full px-4">
                    
                    <!-- Main Headline -->
                    <h1 class="text-[36px] sm:text-[48px] md:text-[56px] lg:text-[64px] xl:text-[72px] font-bold leading-[1.15] tracking-[-0.01em] mb-6 text-white reveal">
                        <span class="whitespace-nowrap">Estamos revolucionando a</span><br><span>tecnologia empresarial</span>
                    </h1>
                    
                    <!-- Subtitle -->
                    <p class="text-[16px] sm:text-[17px] lg:text-[18px] text-white/75 leading-[1.65] mb-8 max-w-2xl mx-auto font-normal reveal" style="transition-delay: 100ms">
                        Nossa missão é fornecer as ferramentas que você precisa para transformar seu negócio com tecnologia de ponta.
                    </p>
                    
                    <!-- CTA Button -->
                    <div class="reveal" style="transition-delay: 200ms">
                        <a href="#produtos" class="group btn-primary px-8 py-3.5 rounded-full text-[15px] font-semibold inline-flex items-center gap-2 tracking-tight shadow-xl shadow-black/30 hover:shadow-2xl hover:shadow-black/40 transition-all duration-500">
                            <span>Conheça Nossos Produtos</span>
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3"/>
                            </svg>
                        </a>
                    </div>
                    
                </div>
            </div>
            
        </div>
        
    </section>
    
    <!-- Brands Carousel -->
    <div class="brands-carousel py-10 lg:py-14 border-y border-white/[0.08] bg-black overflow-hidden">
        <div class="marquee-track flex whitespace-nowrap items-center">
            <?php 
            $brands = ['Cloudflare', 'LacTech', 'SafeNode', 'Prefeitura de Maranguape', 'KRON'];
            // Duplicar para criar loop infinito suave
            for($i = 0; $i < 2; $i++): 
                foreach($brands as $index => $brand):
            ?>
            <span class="brand-name text-white/50 hover:text-white/80 transition-colors duration-300 mx-10 lg:mx-16"><?= $brand ?></span>
            <?php if($index < count($brands) - 1 || $i < 1): ?>
            <span class="w-[2px] h-[2px] rounded-full bg-white/20 mx-6"></span>
            <?php endif; ?>
            <?php 
                endforeach;
            endfor; 
            ?>
        </div>
    </div>
    
    <!-- Products Section -->
    <section id="produtos" class="py-32 lg:py-48 relative overflow-hidden">
        <div class="grid-bg opacity-30"></div>
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20 relative">
            
            <!-- Section Header -->
            <div class="mb-20 lg:mb-32 text-center lg:text-left">
                <div class="flex items-center justify-center lg:justify-start gap-4 mb-6 reveal">
                    <div class="w-12 h-px bg-gradient-to-r from-white/30 to-transparent"></div>
                    <span class="text-[11px] tracking-[0.2em] uppercase text-white/50 font-semibold">Produtos</span>
                    <div class="w-12 h-px bg-gradient-to-l from-white/30 to-transparent"></div>
                </div>
                <h2 class="text-[40px] sm:text-[48px] lg:text-[64px] xl:text-[80px] font-bold leading-[1.05] tracking-tight">
                    <span class="block reveal text-gradient">Dois produtos.</span>
                    <span class="block reveal text-white/20" style="transition-delay: 100ms">Mercados distintos.</span>
                </h2>
                <p class="mt-6 text-[16px] lg:text-[18px] text-white/40 max-w-2xl mx-auto lg:mx-0 reveal" style="transition-delay: 200ms">
                    Soluções especializadas para diferentes necessidades. Tecnologia de ponta, simplicidade e eficiência em cada produto.
                </p>
            </div>
            
            <!-- Products Grid -->
            <div class="flex lg:grid lg:grid-cols-2 gap-8 overflow-x-auto lg:overflow-visible pb-6 lg:pb-0 -mx-6 lg:mx-0 px-6 lg:px-0 scrollbar-hide">
                
                <!-- SafeNode Card -->
                <a href="#safenode" class="group block flex-shrink-0 w-[85vw] sm:w-[75vw] lg:w-auto reveal-scale">
                    <div class="glass-card rounded-3xl p-10 lg:p-12 h-full card-3d border border-white/5 hover:border-white/10 transition-all duration-500">
                        <div class="card-shine"></div>
                        <div class="flex flex-col h-full min-h-[480px] lg:min-h-[560px] relative z-10">
                            
                            <!-- Icon & Badge -->
                            <div class="flex items-start justify-between mb-8">
                                <div class="card-icon w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500/20 via-blue-400/10 to-transparent border border-blue-500/20 flex items-center justify-center transition-all duration-500 group-hover:border-blue-400/40">
                                    <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                                </div>
                                <div class="px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20">
                                    <span class="text-[10px] tracking-[0.15em] uppercase text-emerald-400 font-semibold">Enterprise</span>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-grow">
                                <div class="flex items-center gap-3 mb-5">
                                    <span class="text-[11px] tracking-[0.2em] uppercase text-blue-400/90 font-bold">Segurança Web</span>
                                    <span class="w-2 h-2 rounded-full bg-blue-400/60 animate-pulse"></span>
                                </div>
                                <h3 class="text-[36px] lg:text-[44px] font-bold mb-6 tracking-tight text-gradient group-hover:text-gradient-shine transition-all duration-500">SafeNode</h3>
                                <p class="text-[16px] lg:text-[17px] text-white/50 leading-[1.75] mb-8">
                                    Plataforma completa de segurança web integrada com Cloudflare. Proteção enterprise contra ataques DDoS, firewall de aplicação web, detecção de ameaças em tempo real e bloqueio automático de IPs maliciosos.
                                </p>
                                
                                <!-- Key Features -->
                                <div class="space-y-3 mb-8">
                                    <div class="flex items-center gap-3 text-[14px] text-white/40">
                                        <svg class="w-4 h-4 text-blue-400/60 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Integração nativa com Cloudflare</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-[14px] text-white/40">
                                        <svg class="w-4 h-4 text-blue-400/60 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Bloqueio automático de ameaças</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-[14px] text-white/40">
                                        <svg class="w-4 h-4 text-blue-400/60 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Dashboard de segurança em tempo real</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Features tags -->
                            <div class="flex flex-wrap gap-2 mb-6">
                                <span class="px-3 py-1.5 rounded-full text-[11px] font-medium bg-blue-500/10 text-blue-400/80 border border-blue-500/20">DDoS Protection</span>
                                <span class="px-3 py-1.5 rounded-full text-[11px] font-medium bg-blue-500/10 text-blue-400/80 border border-blue-500/20">WAF</span>
                                <span class="px-3 py-1.5 rounded-full text-[11px] font-medium bg-blue-500/10 text-blue-400/80 border border-blue-500/20">Cloudflare</span>
                            </div>
                            
                            <!-- Footer -->
                            <div class="flex items-center justify-between pt-6 border-t border-white/[0.08]">
                                <span class="text-[14px] text-white/50 font-medium group-hover:text-white transition-colors duration-300">Explorar produto</span>
                                <svg class="card-arrow w-5 h-5 text-white/40 transition-all duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                                </svg>
                            </div>
                            
                        </div>
                    </div>
                </a>
                
                <!-- LacTech Card -->
                <a href="#lactech" class="group block flex-shrink-0 w-[85vw] sm:w-[75vw] lg:w-auto reveal-scale" style="transition-delay: 150ms">
                    <div class="glass-card rounded-3xl p-10 lg:p-12 h-full card-3d border border-white/5 hover:border-white/10 transition-all duration-500">
                        <div class="card-shine"></div>
                        <div class="flex flex-col h-full min-h-[480px] lg:min-h-[560px] relative z-10">
                            
                            <!-- Icon & Badge -->
                            <div class="flex items-start justify-between mb-8">
                                <div class="card-icon w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500/20 via-emerald-400/10 to-transparent border border-emerald-500/20 flex items-center justify-center transition-all duration-500 group-hover:border-emerald-400/40">
                                    <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                                    </svg>
                                </div>
                                <div class="px-3 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20">
                                    <span class="text-[10px] tracking-[0.15em] uppercase text-blue-400 font-semibold">AgroTech</span>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-grow">
                                <div class="flex items-center gap-3 mb-5">
                                    <span class="text-[11px] tracking-[0.2em] uppercase text-emerald-400/90 font-bold">Gestão Rural</span>
                                    <span class="w-2 h-2 rounded-full bg-emerald-400/60 animate-pulse"></span>
                                </div>
                                <h3 class="text-[36px] lg:text-[44px] font-bold mb-6 tracking-tight text-gradient group-hover:text-gradient-shine transition-all duration-500">LacTech</h3>
                                <p class="text-[16px] lg:text-[17px] text-white/50 leading-[1.75] mb-8">
                                    Sistema completo de gestão para fazendas leiteiras. Controle de rebanho, produção de leite, monitoramento sanitário, gestão financeira e relatórios inteligentes para aumentar a produtividade.
                                </p>
                                
                                <!-- Key Features -->
                                <div class="space-y-3 mb-8">
                                    <div class="flex items-center gap-3 text-[14px] text-white/40">
                                        <svg class="w-4 h-4 text-emerald-400/60 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Gestão completa de rebanho</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-[14px] text-white/40">
                                        <svg class="w-4 h-4 text-emerald-400/60 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Controle de produção e qualidade</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-[14px] text-white/40">
                                        <svg class="w-4 h-4 text-emerald-400/60 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Relatórios e analytics avançados</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Features tags -->
                            <div class="flex flex-wrap gap-2 mb-6">
                                <span class="px-3 py-1.5 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-400/80 border border-emerald-500/20">Rebanho</span>
                                <span class="px-3 py-1.5 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-400/80 border border-emerald-500/20">Produção</span>
                                <span class="px-3 py-1.5 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-400/80 border border-emerald-500/20">Analytics</span>
                            </div>
                            
                            <!-- Footer -->
                            <div class="flex items-center justify-between pt-6 border-t border-white/[0.08]">
                                <span class="text-[14px] text-white/50 font-medium group-hover:text-white transition-colors duration-300">Explorar produto</span>
                                <svg class="card-arrow w-5 h-5 text-white/40 transition-all duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                                </svg>
                            </div>
                            
                        </div>
                    </div>
                </a>
                
            </div>
            
        </div>
    </section>
    
    <!-- SafeNode Detail -->
    <section id="safenode" class="py-32 lg:py-48 border-t border-white/[0.05] relative overflow-hidden">
        <div class="absolute top-0 right-0 w-[700px] h-[700px] bg-blue-500/8 rounded-full blur-[180px] pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-blue-400/5 rounded-full blur-[120px] pointer-events-none"></div>
        
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20 relative">
            
            <div class="grid lg:grid-cols-2 gap-20 lg:gap-28">
                
                <!-- Left - Sticky -->
                <div class="lg:sticky lg:top-32 lg:self-start">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/10 border border-blue-500/20 mb-6 reveal">
                        <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                        <span class="text-[11px] tracking-[0.2em] uppercase text-blue-400/90 font-semibold">01 / Segurança</span>
                    </div>
                    <h2 class="text-[52px] lg:text-[72px] xl:text-[84px] font-bold mb-8 tracking-tight leading-[0.95] reveal">
                        <span class="text-gradient">SafeNode</span>
                    </h2>
                    <p class="text-[18px] lg:text-[20px] text-white/50 leading-[1.75] mb-12 max-w-lg reveal" style="transition-delay: 100ms">
                        Plataforma de segurança web integrada com Cloudflare. Proteção enterprise para suas aplicações com firewall inteligente, detecção de ameaças em tempo real e resposta automatizada a ataques.
                    </p>
                    <div class="flex flex-wrap items-center gap-4 mb-8 reveal" style="transition-delay: 150ms">
                        <a href="#contato" class="btn-primary px-6 py-3 rounded-full text-[14px] font-semibold inline-flex items-center gap-2">
                            <span>Solicitar demo</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                            </svg>
                        </a>
                        <a href="#produtos" class="btn-outline px-6 py-3 rounded-full text-[14px] font-semibold text-white/70 hover:text-white">
                            Ver todos os produtos
                        </a>
                    </div>
                </div>
                
                <!-- Right - Features -->
                <div class="space-y-2 stagger-children" id="safenode-features">
                    
                    <?php
                    $safenode_features = [
                        ['num' => '01', 'title' => 'Integração Cloudflare', 'desc' => 'Sincronização automática para proteção DDoS, WAF e gestão de DNS com monitoramento em tempo real de logs e eventos de segurança.'],
                        ['num' => '02', 'title' => 'Bloqueio Automático de IPs', 'desc' => 'Sistema inteligente de detecção e bloqueio automático de IPs maliciosos. Análise de padrões de ataque e resposta imediata a ameaças.'],
                        ['num' => '03', 'title' => 'Dashboard de Segurança', 'desc' => 'Painel completo com métricas de segurança, logs de incidentes, alertas em tempo real e histórico completo de eventos. Visualização clara de ameaças.'],
                        ['num' => '04', 'title' => 'Modo Sob Ataque', 'desc' => 'Ativação manual ou automática do modo de proteção máxima durante ataques. Níveis de segurança configuráveis por site com resposta adaptativa.'],
                        ['num' => '05', 'title' => 'Multi-Site Management', 'desc' => 'Gerencie múltiplos sites e domínios em uma única plataforma. Visão global ou individual por site com configurações personalizadas.'],
                    ];
                    
                    foreach($safenode_features as $feature):
                    ?>
                    <div class="feature-item border border-white/[0.08] bg-white/[0.02] rounded-2xl p-6 hover:bg-white/[0.04] hover:border-blue-500/20 transition-all duration-500 reveal">
                        <div class="flex items-start gap-5">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center flex-shrink-0">
                                <span class="text-[12px] text-blue-400 font-bold"><?= $feature['num'] ?></span>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-[19px] lg:text-[21px] font-semibold mb-3 tracking-tight text-white"><?= $feature['title'] ?></h4>
                                <p class="text-[15px] lg:text-[16px] text-white/45 leading-[1.7]"><?= $feature['desc'] ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                </div>
                
            </div>
            
        </div>
    </section>
    
    <!-- LacTech Detail -->
    <section id="lactech" class="py-32 lg:py-48 border-t border-white/[0.05] relative overflow-hidden">
        <div class="absolute top-0 left-0 w-[700px] h-[700px] bg-emerald-500/8 rounded-full blur-[180px] pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-[500px] h-[500px] bg-emerald-400/5 rounded-full blur-[120px] pointer-events-none"></div>
        
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20 relative">
            
            <div class="grid lg:grid-cols-2 gap-20 lg:gap-28">
                
                <!-- Left - Sticky -->
                <div class="lg:sticky lg:top-32 lg:self-start">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 mb-6 reveal">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-[11px] tracking-[0.2em] uppercase text-emerald-400/90 font-semibold">02 / Agro</span>
                    </div>
                    <h2 class="text-[52px] lg:text-[72px] xl:text-[84px] font-bold mb-8 tracking-tight leading-[0.95] reveal">
                        <span class="text-gradient">LacTech</span>
                    </h2>
                    <p class="text-[18px] lg:text-[20px] text-white/50 leading-[1.75] mb-12 max-w-lg reveal" style="transition-delay: 100ms">
                        Sistema completo de gestão para fazendas leiteiras desenvolvido com tecnologia de ponta. Aumente a produtividade e eficiência através de ferramentas inteligentes e análises precisas do seu rebanho.
                    </p>
                    <div class="flex flex-wrap items-center gap-4 mb-8 reveal" style="transition-delay: 150ms">
                        <a href="#contato" class="btn-primary px-6 py-3 rounded-full text-[14px] font-semibold inline-flex items-center gap-2">
                            <span>Solicitar demo</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                            </svg>
                        </a>
                        <a href="#produtos" class="btn-outline px-6 py-3 rounded-full text-[14px] font-semibold text-white/70 hover:text-white">
                            Ver todos os produtos
                        </a>
                    </div>
                </div>
                
                <!-- Right - Features -->
                <div class="space-y-2 stagger-children" id="lactech-features">
                    
                    <?php
                    $lactech_features = [
                        ['num' => '01', 'title' => 'Gestão Completa de Rebanho', 'desc' => 'Cadastro detalhado de animais com genealogia, histórico reprodutivo, eventos sanitários, vacinações e controle de ciclo estral. Rastreabilidade completa de cada animal.'],
                        ['num' => '02', 'title' => 'Controle de Produção de Leite', 'desc' => 'Registro diário de ordenhas com volume, qualidade do leite (gordura, proteína, CCS), acompanhamento de metas de produção e análise de produtividade por animal ou lote.'],
                        ['num' => '03', 'title' => 'Monitoramento Sanitário', 'desc' => 'Controle de saúde animal com registro de tratamentos, diagnósticos, prevenção de doenças e alertas para vacinações e exames periódicos. Histórico médico completo.'],
                        ['num' => '04', 'title' => 'Gestão Financeira Integrada', 'desc' => 'Controle completo de custos operacionais, receitas, fluxo de caixa, rentabilidade por animal e análise de viabilidade econômica. Relatórios financeiros detalhados.'],
                        ['num' => '05', 'title' => 'Relatórios e Dashboards Inteligentes', 'desc' => 'Dashboards personalizados com métricas em tempo real, relatórios automáticos de produção, análises preditivas e insights para tomada de decisão estratégica.'],
                    ];
                    
                    foreach($lactech_features as $feature):
                    ?>
                    <div class="feature-item border border-white/[0.08] bg-white/[0.02] rounded-2xl p-6 hover:bg-white/[0.04] hover:border-emerald-500/20 transition-all duration-500 reveal">
                        <div class="flex items-start gap-5">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center flex-shrink-0">
                                <span class="text-[12px] text-emerald-400 font-bold"><?= $feature['num'] ?></span>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-[19px] lg:text-[21px] font-semibold mb-3 tracking-tight text-white"><?= $feature['title'] ?></h4>
                                <p class="text-[15px] lg:text-[16px] text-white/45 leading-[1.7]"><?= $feature['desc'] ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                </div>
                
            </div>
            
        </div>
    </section>
    
    <!-- About/Philosophy -->
    <section id="sobre" class="py-28 lg:py-40 border-t border-white/[0.05] relative overflow-hidden">
        <div class="grid-bg opacity-30"></div>
        
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20 relative">
            
            <div class="max-w-4xl">
                
                <div class="text-center lg:text-left mb-16">
                    <div class="flex items-center justify-center lg:justify-start gap-4 mb-8 reveal">
                        <div class="w-12 h-px bg-gradient-to-r from-white/30 to-transparent"></div>
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10">
                            <span class="w-2 h-2 rounded-full bg-white/50 animate-pulse"></span>
                            <span class="text-[11px] tracking-[0.2em] uppercase text-white/60 font-semibold">Filosofia</span>
                        </div>
                        <div class="w-12 h-px bg-gradient-to-l from-white/30 to-transparent"></div>
                    </div>
                    
                    <h2 class="text-[40px] sm:text-[48px] lg:text-[64px] xl:text-[76px] font-bold leading-[1.1] tracking-tight mb-10">
                        <span class="block reveal text-gradient">Acreditamos que software</span>
                        <span class="block reveal text-white/20" style="transition-delay: 100ms">deve ser <span class="text-gradient">invisível.</span></span>
                        <span class="block reveal text-gradient" style="transition-delay: 200ms">Deve simplesmente <span class="text-white/20">funcionar.</span></span>
                    </h2>
                    
                    <p class="text-[18px] lg:text-[20px] text-white/50 leading-[1.75] max-w-3xl mx-auto lg:mx-0 reveal" style="transition-delay: 300ms">
                        Não construímos features por construir. Cada linha de código existe para resolver um problema real. Essa obsessão por simplicidade e eficiência guia tudo que fazemos na KRON.
                    </p>
                </div>
                
                <!-- Philosophy Principles -->
                <div class="grid md:grid-cols-3 gap-6 mb-16">
                    <div class="p-6 rounded-2xl bg-white/[0.02] border border-white/5 hover:border-white/10 transition-all duration-500 reveal" style="transition-delay: 400ms">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <h3 class="text-[18px] font-semibold mb-2 text-white">Simplicidade</h3>
                        <p class="text-[14px] text-white/45 leading-relaxed">Interfaces intuitivas que não precisam de manual. Software que funciona sem complicação.</p>
                    </div>
                    
                    <div class="p-6 rounded-2xl bg-white/[0.02] border border-white/5 hover:border-white/10 transition-all duration-500 reveal" style="transition-delay: 500ms">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-[18px] font-semibold mb-2 text-white">Performance</h3>
                        <p class="text-[14px] text-white/45 leading-relaxed">Código otimizado para velocidade e eficiência. Resultados que fazem a diferença.</p>
                    </div>
                    
                    <div class="p-6 rounded-2xl bg-white/[0.02] border border-white/5 hover:border-white/10 transition-all duration-500 reveal" style="transition-delay: 600ms">
                        <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="text-[18px] font-semibold mb-2 text-white">Confiabilidade</h3>
                        <p class="text-[14px] text-white/45 leading-relaxed">Sistemas robustos e seguros. Você pode confiar que tudo vai funcionar quando precisar.</p>
                    </div>
                </div>
                
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12 mt-24 lg:mt-32 pt-16 lg:pt-20 border-t border-white/[0.08]">
                
                <?php
                $stats = [
                    ['value' => '2', 'label' => 'Produtos', 'suffix' => ''],
                    ['value' => '500', 'label' => 'Clientes', 'suffix' => '+'],
                    ['value' => '99.9', 'label' => 'Uptime', 'suffix' => '%'],
                    ['value' => '24/7', 'label' => 'Suporte', 'suffix' => ''],
                ];
                
                $delay = 0;
                foreach($stats as $stat):
                ?>
                <div class="reveal group" style="transition-delay: <?= $delay ?>ms">
                    <div class="flex items-baseline gap-1">
                        <span class="stat-number text-[48px] lg:text-[64px] font-bold tracking-tight text-gradient group-hover:text-gradient-shine transition-all duration-500" data-value="<?= $stat['value'] ?>"><?= $stat['value'] ?></span>
                        <span class="text-[28px] lg:text-[36px] font-bold text-white/40"><?= $stat['suffix'] ?></span>
                </div>
                    <p class="text-[13px] lg:text-[14px] text-white/40 mt-2 font-medium tracking-wide"><?= $stat['label'] ?></p>
                </div>
                <?php 
                $delay += 100;
                endforeach; 
                ?>
                
            </div>
            
        </div>
    </section>
    
    <!-- Presence Map -->
    <section id="contato" class="py-28 lg:py-40 border-t border-white/[0.05] relative overflow-hidden">
        
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20 relative">
            
            <div class="text-center mb-16 lg:mb-20">
                <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full glass mb-8 reveal">
                    <span class="w-2 h-2 rounded-full bg-white/50 animate-pulse"></span>
                    <span class="text-[12px] tracking-[0.15em] uppercase text-white/60 font-medium">Estamos presentes</span>
                </div>
                
                <h2 class="text-[40px] sm:text-[52px] lg:text-[68px] xl:text-[80px] font-bold mb-6 tracking-tight reveal">
                    <span class="text-gradient">Nossa Presença Global</span>
                </h2>
                
                <p class="text-[17px] lg:text-[19px] text-white/45 max-w-2xl mx-auto reveal" style="transition-delay: 100ms">
                    Atendemos clientes em diversos países ao redor do mundo
                </p>
            </div>
            
            <!-- Map Container -->
            <div class="relative w-full h-[500px] lg:h-[600px] rounded-2xl overflow-hidden glass-card border border-white/10 reveal" style="transition-delay: 200ms">
                <div id="presence-map" class="w-full h-full"></div>
            </div>
            
        </div>
    </section>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Create map with world view
            const map = L.map('presence-map', {
                zoomControl: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false,
                dragging: false,
                touchZoom: false
            }).setView([20, 0], 2);
            
            // Add dark tile layer without attribution
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: false,
                subdomains: 'abcd',
                maxZoom: 19
            }).addTo(map);
            
            // Custom marker icon
            const markerIcon = L.divIcon({
                className: 'custom-marker',
                html: '<div class="marker-pin"></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 30]
            });
            
            // Countries where we are present
            const locations = [
                { name: 'Brasil', lat: -14.2350, lng: -51.9253 },
                { name: 'Chile', lat: -35.6751, lng: -71.5430 }
            ];
            
            // Add markers
            locations.forEach(location => {
                const marker = L.marker([location.lat, location.lng], { icon: markerIcon }).addTo(map);
                marker.bindPopup(`<div class="text-black font-semibold">${location.name}</div>`);
            });
        });
    </script>
    
    <style>
        .custom-marker {
            background: transparent;
            border: none;
        }
        
        .marker-pin {
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border: 3px solid white;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
            animation: pulse-marker 2s ease-in-out infinite;
        }
        
        .marker-pin::before {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(45deg);
        }
        
        @keyframes pulse-marker {
            0%, 100% {
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
            }
            50% {
                box-shadow: 0 4px 20px rgba(59, 130, 246, 0.6);
            }
        }
        
        #presence-map {
            background: #000;
        }
        
        .leaflet-container {
            background: #000 !important;
        }
        
        .leaflet-popup-content-wrapper {
            background: white;
            border-radius: 8px;
            padding: 8px 12px;
        }
        
        /* Hide all controls and attributions */
        .leaflet-control-container {
            display: none !important;
        }
        
        .leaflet-control-attribution {
            display: none !important;
        }
        
        .leaflet-control-zoom {
            display: none !important;
        }
    </style>
    
    <!-- Footer -->
    <footer class="py-16 lg:py-24 border-t border-white/[0.05] bg-gradient-to-b from-black to-black/95 relative">
        <div class="grid-bg opacity-20"></div>
        
        <div class="max-w-[1440px] mx-auto px-6 lg:px-12 xl:px-20 relative">
            
            <!-- Full Name Letreiro -->
            <div class="mb-16 lg:mb-20 py-4 overflow-hidden relative">
                <div class="letreiro-track whitespace-nowrap">
                    <span class="text-[18px] lg:text-[22px] font-bold text-white/[0.08] tracking-tight inline-block" style="font-family: 'Playfair Display', serif;">Kernel for Resilient Operating Nodes</span>
                </div>
            </div>
            
            <!-- Top Section -->
            <div class="grid lg:grid-cols-12 gap-12 lg:gap-8 mb-16 lg:mb-20">
                
                <!-- Brand -->
                <div class="lg:col-span-5">
                    <a href="#" class="inline-flex items-center gap-3 mb-6 group">
                        <img src="asset/kron.png" alt="KRON" class="h-10 w-auto rounded-xl group-hover:scale-105 transition-transform duration-500 shadow-lg">
                        <span class="text-2xl font-bold tracking-tight">KRON</span>
                    </a>
                    <p class="text-[15px] text-white/45 leading-[1.7] max-w-sm mb-6">
                        Kernel for Resilient Operating Nodes. Construímos software que resolve problemas reais com simplicidade e eficiência.
                    </p>
                    <a href="mailto:contato@kron.com.br" class="inline-flex items-center gap-3 text-[14px] text-white/50 hover:text-white transition-colors duration-300 group">
                        <span class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center group-hover:bg-white/10 transition-all duration-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <span>contato@kron.com.br</span>
                    </a>
                </div>
                
                <!-- Products -->
                <div class="lg:col-span-2 lg:col-start-7">
                    <h4 class="text-[11px] tracking-[0.2em] uppercase text-white/40 mb-6 font-bold">Produtos</h4>
                    <ul class="space-y-4">
                        <li><a href="#safenode" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">SafeNode</a></li>
                        <li><a href="#lactech" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">LacTech</a></li>
                    </ul>
                </div>
                
                <!-- Company -->
                <div class="lg:col-span-2">
                    <h4 class="text-[11px] tracking-[0.2em] uppercase text-white/40 mb-6 font-bold">Empresa</h4>
                    <ul class="space-y-4">
                        <li><a href="#sobre" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">Sobre</a></li>
                        <li><a href="#contato" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">Contato</a></li>
                        <li><a href="#produtos" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">Produtos</a></li>
                    </ul>
                </div>
                
                <!-- Legal -->
                <div class="lg:col-span-2">
                    <h4 class="text-[11px] tracking-[0.2em] uppercase text-white/40 mb-6 font-bold">Legal</h4>
                    <ul class="space-y-4">
                        <li><a href="#" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">Privacidade</a></li>
                        <li><a href="#" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">Termos de Uso</a></li>
                        <li><a href="#" class="text-[15px] text-white/55 hover:text-white transition-colors duration-300 link-hover">Cookies</a></li>
                    </ul>
                </div>
                
            </div>
            
            <!-- Divider -->
            <div class="h-px bg-gradient-to-r from-transparent via-white/15 to-transparent mb-10"></div>
            
            <!-- Bottom Section -->
            <div class="flex flex-col lg:flex-row justify-between items-center gap-6">
                
                <!-- Copyright -->
                <div class="flex items-center gap-3 text-[13px] text-white/35">
                    <span>© <?= date('Y') ?> KRON</span>
                    <span class="w-1 h-1 rounded-full bg-white/20"></span>
                    <span>Todos os direitos reservados</span>
                </div>
                
                <!-- Social Icons -->
                <div class="flex items-center gap-3">
                    <a href="https://www.instagram.com/safenode/" target="_blank" rel="noopener noreferrer" class="social-icon" aria-label="Instagram SafeNode">
                        <svg class="w-5 h-5 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke-width="1.5" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37zM17.5 6.5h.01" />
                        </svg>
                    </a>
                    <a href="https://www.instagram.com/lvnas._/" target="_blank" rel="noopener noreferrer" class="social-icon" aria-label="Instagram Lvnas">
                        <svg class="w-5 h-5 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke-width="1.5" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37zM17.5 6.5h.01" />
                        </svg>
                    </a>
                    <a href="https://github.com" target="_blank" rel="noopener noreferrer" class="social-icon" aria-label="GitHub">
                        <svg class="w-5 h-5 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 00-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0020 4.77 5.07 5.07 0 0019.91 1S18.73.65 16 2.48a13.38 13.38 0 00-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 005 4.77a5.44 5.44 0 00-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 009 18.13V22" />
                        </svg>
                    </a>
                </div>
                
            </div>
            
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Cursor glow effect
        const cursorGlow = document.getElementById('cursor-glow');
        let mouseX = 0, mouseY = 0;
        let glowX = 0, glowY = 0;
        
            document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
            cursorGlow.style.opacity = '1';
        });
        
        document.addEventListener('mouseleave', () => {
            cursorGlow.style.opacity = '0';
        });
        
        function animateGlow() {
            glowX += (mouseX - glowX) * 0.1;
            glowY += (mouseY - glowY) * 0.1;
            cursorGlow.style.left = glowX + 'px';
            cursorGlow.style.top = glowY + 'px';
            requestAnimationFrame(animateGlow);
        }
        animateGlow();
        
        // Header scroll effect
        const mainNav = document.getElementById('main-nav');
        
        function handleScroll() {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            mainNav.classList.toggle('scrolled', currentScroll > 80);
        }
        
        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();
        
        // Mobile menu
        const menuBtn = document.getElementById('menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileNav = document.getElementById('mobile-nav');
        const line1 = document.getElementById('line1');
        const line2 = document.getElementById('line2');
        let menuOpen = false;
        
        menuBtn?.addEventListener('click', () => {
            menuOpen = !menuOpen;
            mobileMenu.classList.toggle('active');
            
            if (menuOpen) {
                line1.style.transform = 'rotate(45deg) translate(2px, 2px)';
                line2.style.transform = 'rotate(-45deg) translate(2px, -2px)';
                setTimeout(() => mobileNav.classList.add('active'), 300);
            } else {
                line1.style.transform = '';
                line2.style.transform = '';
                mobileNav.classList.remove('active');
            }
        });
        
        // Close mobile menu on link click
        mobileMenu?.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                menuOpen = false;
                mobileMenu.classList.remove('active');
                mobileNav.classList.remove('active');
                line1.style.transform = '';
                line2.style.transform = '';
            });
        });
        
        // Reveal on scroll
        const reveals = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale, .stagger-children');
        
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '-30px'
        });
        
        reveals.forEach(el => revealObserver.observe(el));
        
        // Magnetic button effect
        document.querySelectorAll('.magnetic').forEach(btn => {
            btn.addEventListener('mousemove', (e) => {
                const rect = btn.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                btn.style.transform = `translate(${x * 0.2}px, ${y * 0.2}px)`;
            });
            
            btn.addEventListener('mouseleave', () => {
                btn.style.transform = '';
            });
        });
        
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
        
        // Counter animation for stats
        const statNumbers = document.querySelectorAll('.stat-number[data-value]');
        
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const value = el.dataset.value;
                    
                    if (value.includes('/') || value.includes('.')) {
                        return; // Skip non-numeric values
                    }
                    
                    let current = 0;
                    const target = parseInt(value);
                    const duration = 2000;
                    const step = target / (duration / 16);
                    
                    const updateCounter = () => {
                        current += step;
                        if (current < target) {
                            el.textContent = Math.floor(current);
                            requestAnimationFrame(updateCounter);
                        } else {
                            el.textContent = target;
                        }
                    };
                    
                    updateCounter();
                    counterObserver.unobserve(el);
                }
            });
        }, { threshold: 0.5 });
        
        statNumbers.forEach(el => counterObserver.observe(el));
        
        // Back to Top Button
        const backToTopBtn = document.getElementById('back-to-top');
        
        if (backToTopBtn) {
            function toggleBackToTop() {
                const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
                if (scrollPosition > 100) {
                    backToTopBtn.classList.add('visible');
                } else {
                    backToTopBtn.classList.remove('visible');
                }
            }
            
            window.addEventListener('scroll', toggleBackToTop, { passive: true });
            // Check on load
            setTimeout(toggleBackToTop, 100);
            
            backToTopBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    </script>
    
    <!-- Back to Top Button -->
    <button id="back-to-top" class="back-to-top" aria-label="Voltar ao topo">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
    </button>
    
    <div class="noise"></div>

</body>
</html>
