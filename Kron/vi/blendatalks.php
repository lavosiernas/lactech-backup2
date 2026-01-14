<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abre ae!</title>
    <link rel="icon" type="image/png" href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRuoje0mdH6Vcef3Vc0z6kTCpOIQgbb9GiKEQ&s">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap');
        
        :root {
            --rose-gold: #b76e79;
            --deep-charcoal: #1a1a1a;
            --soft-cream: #f9f6f2;
            --accent-gold: #d4af37;
        }

        * {
            font-family: 'Inter', sans-serif;
            scroll-behavior: smooth;
            user-select: none;
            -webkit-user-select: none;
        }
        
        .serif {
            font-family: 'Playfair Display', serif;
        }

        .smooth-fade {
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-img {
            width: 200px;
            height: 200px;
            border-radius: 2px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .photo-grid-item {
            overflow: hidden;
            border-radius: 2px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            aspect-ratio: 1;
            position: relative;
        }

        .photo-grid-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .photo-grid-item:hover img {
            transform: scale(1.02);
        }

        .sigma-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10;
            padding: 8px;
        }

        .sigma-badge img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin-bottom: 4px;
            transition: none;
            transform: none !important;
        }

        .sigma-badge-text {
            font-size: 10px;
            font-weight: 600;
            color: #111827;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .message-card {
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.05);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 4px;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }

        .text-gradient {
            background: linear-gradient(to right, #b76e79, #d4af37);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sparkle-trail {
            position: fixed;
            pointer-events: none;
            z-index: 9999;
            font-size: 14px;
            animation: fadeOutUp 1s forwards;
        }

        @keyframes fadeOutUp {
            0% { transform: translate(0, 0) scale(1); opacity: 1; }
            100% { transform: translate(0, -50px) scale(0); opacity: 0; }
        }

        .cta-cute {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .cta-cute:hover {
            transform: scale(1.1) rotate(2deg);
        }

        .floating-emoji {
            display: inline-block;
            animation: floating 3s infinite ease-in-out;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .message-card:hover {
            border-color: var(--rose-gold);
            box-shadow: 0 12px 35px rgba(183, 110, 121, 0.15);
            transform: translateY(-5px);
        }

        .line-accent {
            width: 80px;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--rose-gold), transparent);
            margin: 0 auto;
        }

        .section-title {
            position: relative;
            padding-bottom: 24px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 1px;
            background: #111827;
        }

        .confetti-piece {
            animation: confetti-fall 3s ease-in forwards;
            position: fixed;
            pointer-events: none;
        }

        @keyframes confetti-fall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }

        /* Fireworks effect */
        .firework {
            position: fixed;
            pointer-events: none;
            z-index: 9999;
        }

        .firework-particle {
            position: absolute;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            animation: firework-explode 1.5s ease-out forwards;
        }

        @keyframes firework-explode {
            0% {
                transform: translate(0, 0) scale(1);
                opacity: 1;
            }
            100% {
                transform: translate(var(--tx), var(--ty)) scale(0);
                opacity: 0;
            }
        }

        /* Hearts effect */
        .heart {
            position: fixed;
            pointer-events: none;
            z-index: 9999;
            font-size: 20px;
            animation: heart-fall 3s ease-in forwards;
        }

        @keyframes heart-fall {
            0% {
                transform: translateY(-20px) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }

        /* Heart shape using CSS */
        .heart-shape {
            position: relative;
            width: 20px;
            height: 18px;
            display: inline-block;
        }

        .heart-shape::before,
        .heart-shape::after {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            width: 10px;
            height: 16px;
            background: #ef4444;
            border-radius: 10px 10px 0 0;
            transform: rotate(-45deg);
            transform-origin: 0 100%;
        }

        .heart-shape::after {
            left: 0;
            transform: rotate(45deg);
            transform-origin: 100% 100%;
        }

        .video-container {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
        }

        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 2px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .music-disc {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 1000;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            overflow: visible;
        }

        .music-disc:hover {
            transform: scale(1.1);
        }

        .music-disc img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .music-disc.playing {
            animation: rotate 3s linear infinite;
        }

        .music-disc.playing::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border-radius: 50%;
            border: 2px solid rgba(17, 24, 39, 0.3);
            animation: pulse-ring 2s ease-out infinite;
        }

        .music-disc.playing::after {
            content: '';
            position: absolute;
            top: -20px;
            left: -20px;
            right: -20px;
            bottom: -20px;
            border-radius: 50%;
            border: 1px solid rgba(17, 24, 39, 0.2);
            animation: pulse-ring 2s ease-out infinite 0.5s;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(0.8);
                opacity: 1;
            }
            100% {
                transform: scale(1.3);
                opacity: 0;
            }
        }

        .music-disc.playing img {
            animation: disc-glow 2s ease-in-out infinite alternate;
        }

        @keyframes disc-glow {
            0% {
                filter: brightness(1);
            }
            100% {
                filter: brightness(1.15);
            }
        }

        .music-player {
            position: fixed;
            top: 90px;
            left: 20px;
            width: 320px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            z-index: 999;
            display: none;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .music-player.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .player-header {
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .player-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .player-song-name {
            font-size: 16px;
            font-weight: 500;
            color: #111827;
            font-family: 'Playfair Display', serif;
        }

        .player-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .player-btn {
            width: 44px;
            height: 44px;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .player-btn:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            transform: scale(1.05);
        }

        .player-btn:active {
            transform: scale(0.95);
        }

        .volume-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
        }

        /* Gift Box Styles */
        body.gift-active {
            overflow: hidden;
        }

        body.gift-active > *:not(.gift-overlay) {
            visibility: hidden;
        }

        .gift-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #000000;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            transition: opacity 0.8s ease;
            overflow: hidden;
        }

        .gift-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .gift-box-container {
            position: relative;
            cursor: pointer;
            transform-style: preserve-3d;
            perspective: 1000px;
        }

        .gift-box {
            width: 250px;
            height: 250px;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .gift-box:hover {
            transform: scale(1.05);
        }

        .gift-box-top {
            position: absolute;
            width: 250px;
            height: 80px;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            top: 0;
            left: 0;
            transform-origin: bottom;
            transition: transform 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border: 3px solid #991b1b;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5), inset 0 2px 4px rgba(255, 255, 255, 0.1);
            border-radius: 4px 4px 0 0;
        }

        .gift-box.opened .gift-box-top {
            transform: rotateX(-140deg) translateZ(20px);
        }

        .gift-box-body {
            position: absolute;
            width: 250px;
            height: 170px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            bottom: 0;
            left: 0;
            border: 3px solid #dc2626;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5), inset 0 -2px 4px rgba(0, 0, 0, 0.2);
            border-radius: 0 0 4px 4px;
        }

        .gift-ribbon {
            position: absolute;
            width: 24px;
            height: 250px;
            background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);
            left: 50%;
            top: 0;
            transform: translateX(-50%);
            border-left: 2px solid #d97706;
            border-right: 2px solid #d97706;
            z-index: 2;
            box-shadow: 0 0 8px rgba(251, 191, 36, 0.3);
            transition: transform 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .gift-box.opened .gift-ribbon {
            transform: translateX(-50%) translateY(-200px) rotate(-10deg);
            opacity: 0;
        }

        .gift-ribbon-horizontal {
            position: absolute;
            width: 250px;
            height: 24px;
            background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            border-top: 2px solid #d97706;
            border-bottom: 2px solid #d97706;
            z-index: 2;
            box-shadow: 0 0 8px rgba(251, 191, 36, 0.3);
            transition: transform 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .gift-box.opened .gift-ribbon-horizontal {
            transform: translateY(-50%) translateY(-200px) rotate(10deg);
            opacity: 0;
        }

        .gift-bow {
            position: absolute;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            border: 3px solid #d97706;
            z-index: 3;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4), 0 0 16px rgba(251, 191, 36, 0.4);
            transition: transform 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55), opacity 0.8s ease;
        }

        .gift-box.opened .gift-bow {
            transform: translate(-50%, -50%) translateY(-200px) rotate(360deg);
            opacity: 0;
        }

        .gift-bow::before {
            content: '';
            position: absolute;
            width: 30px;
            height: 30px;
            background: #f59e0b;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .gift-sparkles {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            transition: opacity 0.6s ease;
            pointer-events: none;
        }

        .gift-box.opened .gift-sparkles {
            opacity: 1;
        }

        .sparkle {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #fbbf24;
            border-radius: 50%;
            animation: sparkle-float 2.5s ease-out infinite;
            box-shadow: 0 0 12px #fbbf24;
        }

        @keyframes sparkle-float {
            0% {
                transform: translateY(0) scale(1) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(-150px) scale(0) rotate(360deg);
                opacity: 0;
            }
        }

        .gift-text {
            color: white;
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            margin-top: 40px;
            text-align: center;
            opacity: 0.95;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
            animation: pulse-text 2s ease-in-out infinite;
        }

        @keyframes pulse-text {
            0%, 100% {
                opacity: 0.95;
            }
            50% {
                opacity: 0.7;
            }
        }

        .volume-slider {
            flex: 1;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            outline: none;
            -webkit-appearance: none;
        }

        .volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 12px;
            height: 12px;
            background: #111827;
            border-radius: 50%;
            cursor: pointer;
        }

        .volume-slider::-moz-range-thumb {
            width: 12px;
            height: 12px;
            background: #111827;
            border-radius: 50%;
            cursor: pointer;
            border: none;
        }
        .comparison-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 40px;
        }

        .comparison-card {
            border-radius: 12px;
            overflow: hidden;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: transform 0.4s ease;
        }

        .comparison-card:hover {
            transform: translateY(-10px);
        }

        .comparison-img-wrapper {
            position: relative;
            padding-bottom: 75%; /* 4:3 Aspect Ratio */
            overflow: hidden;
        }

        .comparison-img-wrapper img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .comparison-label {
            padding: 20px;
            text-align: center;
            font-size: 1.1rem;
            color: #6b7280;
        }

        .comparison-label.active {
            color: var(--rose-gold);
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .comparison-container {
                grid-template-columns: 1fr;
            }
        }
        
        .letter-container {
            perspective: 1000px;
            cursor: pointer;
            margin: 60px auto;
            max-width: 500px;
        }

        .letter-envelope {
            position: relative;
            width: 100%;
            height: 300px;
            background: #fff;
            border: 1px solid rgba(183, 110, 121, 0.2);
            transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
        }

        .letter-envelope.open {
            transform: rotateY(180deg);
        }

        .letter-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #fff;
            backface-visibility: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .letter-message {
            transform: rotateY(180deg);
            backface-visibility: hidden;
            color: #1a1a1a;
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .wax-seal {
            width: 60px;
            height: 60px;
            background: var(--rose-gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: 0 4px 10px rgba(183, 110, 121, 0.4);
            z-index: 2;
        }
    </style>
</head>
<body class="bg-[#f9f6f2] text-[#1a1a1a]">
    <!-- Audio Players -->
    <audio id="backgroundMusic1" loop muted>
        <source src="music/Anjos.mp3" type="audio/mpeg">
    </audio>
    <audio id="backgroundMusic2" loop muted>
        <source src="music/Pearls.mp3" type="audio/mpeg">
    </audio>

    <!-- Gift Box Overlay -->
    <div class="gift-overlay" id="giftOverlay">
        <div class="gift-box-container">
            <div class="gift-box" id="giftBox">
                <div class="gift-box-body"></div>
                <div class="gift-box-top"></div>
                <div class="gift-ribbon"></div>
                <div class="gift-ribbon-horizontal"></div>
                <div class="gift-bow"></div>
                <div class="gift-sparkles" id="giftSparkles"></div>
            </div>
            <div class="gift-text">Clique para abrir</div>
        </div>
    </div>

    <!-- Music Disc -->
    <div class="music-disc" id="musicDisc">
        <div class="w-full h-full bg-rose-200 flex items-center justify-center rounded-full">
            <span class="text-rose-500 text-xs">♫</span>
        </div>
    </div>

    <!-- Music Player -->
    <div class="music-player" id="musicPlayer">
        <div class="player-header">
            <div class="player-title">Tocando Agora</div>
            <div class="player-song-name" id="songName">Música 1</div>
        </div>
        <div class="player-controls">
            <button class="player-btn" id="playPauseBtn">
                <svg id="playIcon" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" style="display: none;">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M6.271 5.055a.5.5 0 0 1 .79-.407l5.5 4a.5.5 0 0 1 0 .816l-5.5 4a.5.5 0 0 1-.79-.407V5.055z"/>
                </svg>
                <svg id="pauseIcon" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M5 6.25a1.25 1.25 0 1 1 2.5 0v3.5a1.25 1.25 0 1 1-2.5 0v-3.5zm3.5 0a1.25 1.25 0 1 1 2.5 0v3.5a1.25 1.25 0 1 1-2.5 0v-3.5z"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="min-h-screen flex flex-col items-center justify-center px-4 pt-20 pb-20">
        <div class="text-center max-w-2xl space-y-14 smooth-fade">
            <!-- Profile Image Container -->
            <div class="flex justify-center">
                <div class="profile-img rounded-sm overflow-hidden bg-rose-50 flex items-center justify-center border border-rose-100">
                    <img src="" alt="Sua Imagem" class="w-full h-full object-cover">
                    <span class="absolute text-rose-200 serif italic">Aguardando Foto...</span>
                </div>
            </div>

            <!-- Main Text -->
            <div class="space-y-8">
                <p class="text-xs font-semibold uppercase tracking-[4px] text-rose-400 letter-spacing">
                    Para o meu presente e meu futuro ✨
                </p>
                <h1 class="serif text-8xl md:text-9xl font-light leading-tight text-gradient">
                    Blenda
                </h1>
                <p class="text-base md:text-lg text-[#b76e79] font-light leading-relaxed max-w-xl mx-auto italic">
                    "Onde o coração encontra seu lar." <span class="floating-emoji">🏠❤️</span>
                </p>
                <p class="text-base md:text-lg text-gray-600 font-light leading-relaxed max-w-xl mx-auto">
                    Blenda, você não é apenas a mulher da minha vida. Você é o motivo de cada um dos meus sorrisos mais bobos. 🥰
                </p>
            </div>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row gap-6 justify-center pt-6">
                <button onclick="createConfetti()" 
                        class="px-12 py-3 border border-gray-900 text-gray-900 font-medium text-sm transition hover:bg-gray-900 hover:text-white cta-cute">
                    Celebrar
                </button>
                <a href="#memories" class="border-b border-gray-400 text-sm font-medium text-gray-600 hover:text-gray-900 transition py-3 self-center cta-cute">
                    Galeria de Memórias
                </a>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-10 animate-bounce opacity-40">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
        </div>
    </div>

    <!-- Video Section Restored -->
    <section id="video-section" class="py-28 px-4 bg-gray-50/50">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-20 space-y-4">
                <h2 class="serif text-5xl md:text-6xl font-light section-title text-gradient">
                    Um Momento Especial
                </h2>
                <p class="text-gray-600 font-light mt-6 text-lg italic">Um frame que diz tudo</p>
            </div>

            <div class="max-w-4xl mx-auto">
                <div class="video-container bg-rose-50 rounded-sm border border-rose-100 flex items-center justify-center">
                    <video id="section-video" loop muted preload="auto">
                        <source src="" type="video/mp4">
                    </video>
                    <span class="text-rose-200 serif italic">Vídeo em breve...</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Quero Ver Me Zoarem: Desempregada Section -->
    <section id="memories" class="py-28 px-4 bg-white">
        <div class="max-w-6xl mx-auto">
            <!-- Section Title -->
            <div class="text-center mb-16 space-y-4">
                <h2 class="serif text-6xl md:text-7xl font-light section-title">
                    O Que Eu Vejo Em Você 🌷
                </h2>
                <p class="text-gray-600 font-light mt-6 italic">Aqueles detalhes que me fazem bobo por você</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 mb-12">
                <div class="photo-grid-item bg-rose-50 flex items-center justify-center border border-rose-100">
                    <img src="" alt="">
                    <span class="text-rose-200 serif italic">Foto 1</span>
                </div>
                <div class="photo-grid-item bg-rose-50 flex items-center justify-center border border-rose-100">
                    <img src="" alt="">
                    <span class="text-rose-200 serif italic">Foto 2</span>
                </div>
            </div>

            <div class="max-w-3xl mx-auto space-y-6 text-center">
                <p class="text-gray-600 leading-relaxed font-light text-lg">
                    Sabe, as brincadeiras que a gente tinha eram só um disfarce para o quanto eu te admiro. Cada risada que compartilhamos foi construindo algo muito mais forte dentro de mim. 
                </p>
                <p class="text-gray-600 leading-relaxed font-light text-lg">
                    Hoje, eu não vejo apenas uma amiga incrível. Eu vejo a mulher com quem eu quero construir uma vida, compartilhar sonhos e enfrentar qualquer desafio. Sua persistência e seu brilho me conquistam todos os dias.
                </p>
            </div>
        </div>
    </section>

    <!-- Combo Mulher Maravilha Section -->
    <section class="py-28 px-4 bg-white/50">
        <div class="max-w-6xl mx-auto">
            <!-- Section Title -->
            <div class="text-center mb-16 space-y-4">
                <h2 class="serif text-6xl md:text-7xl font-light section-title">
                    Uma Força Única
                </h2>
                <p class="text-gray-600 font-light mt-6 italic text-lg">Inspiradora em cada detalhe do dia a dia</p>
            </div>

            <!-- Images Grid Restored -->
            <div class="grid md:grid-cols-2 gap-10 mb-16">
                <div class="space-y-6">
                    <div class="photo-grid-item bg-rose-50 flex items-center justify-center border border-rose-100">
                        <img src="" alt="">
                    </div>
                    <h3 class="serif text-3xl font-light text-rose-400">Dedicação</h3>
                    <p class="text-gray-600 leading-relaxed font-light text-lg">
                        Equilibrar responsabilidades com tanta graça é o que me faz admirar você cada vez mais. Você prova que é possível ser forte e autêntica ao mesmo tempo.
                    </p>
                </div>
                <div class="space-y-6">
                    <div class="photo-grid-item bg-rose-50 flex items-center justify-center border border-rose-100">
                        <img src="" alt="">
                    </div>
                    <h3 class="serif text-3xl font-light text-rose-400">Crescimento</h3>
                    <p class="text-gray-600 leading-relaxed font-light text-lg">
                        Ver você se dedicando aos estudos e ao seu crescimento é uma das coisas que mais me motiva. Seu esforço é a prova da mulher incrível que você se tornou.
                    </p>
                </div>
            </div>

            <p class="text-center text-gray-500 font-light mb-12 text-sm uppercase tracking-widest">
                Você faz o extraordinário parecer simples
            </p>
        </div>
    </section>

    <!-- Hora do Cuscuz Section -->
    <section class="py-28 px-4 bg-white">
        <div class="max-w-6xl mx-auto">
            <!-- Section Title -->
            <div class="text-center mb-16 space-y-4">
                <h2 class="serif text-6xl md:text-7xl font-light section-title">
                    Hora do Cuscuz 🍲✨
                </h2>
                <p class="text-gray-600 font-light mt-6">Os nossos momentos mais doces e simples</p>
            </div>

            <!-- Two Photo Grid Restored -->
            <div class="grid md:grid-cols-2 gap-10 mb-12">
                <div class="photo-grid-item bg-rose-50 flex items-center justify-center border border-rose-100">
                    <img src="" alt="">
                </div>
                <div class="photo-grid-item bg-rose-50 flex items-center justify-center border border-rose-100">
                    <img src="" alt="">
                </div>
            </div>

            <div class="max-w-2xl mx-auto space-y-6 text-center">
                <div class="bg-rose-50/30 border-l-2 border-rose-200 pl-6 py-4 my-6 text-left">
                    <p class="text-gray-700 leading-relaxed font-light italic text-lg">
                        Até nos momentos mais simples, como dividir um cuscuz, eu sinto que estou exatamente onde deveria estar: ao seu lado.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- World Comparison Section -->
    <section class="py-28 px-4 bg-white">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-16 space-y-4">
                <h2 class="serif text-5xl md:text-6xl font-light section-title">
                    O Impacto de Você no Meu Mundo
                </h2>
                <p class="text-gray-600 font-light mt-6 text-lg">Como tudo muda quando você está por perto</p>
            </div>

            <div class="comparison-container">
                <!-- WITHOUT HER -->
                <div class="comparison-card">
                    <div class="comparison-img-wrapper grayscale">
                        <img src="imgs/sem.png" alt="Mundo sem você">
                    </div>
                    <p class="comparison-label serif italic">
                        "O vazio de um mundo sem a sua luz."
                    </p>
                </div>

                <!-- WITH HER -->
                <div class="comparison-card border-2 border-rose-100">
                    <div class="comparison-img-wrapper">
                        <img src="imgs/com.png" alt="Mundo com você">
                    </div>
                    <p class="comparison-label active serif italic">
                        "Onde as cores ganham vida e a alma encontra paz."
                    </p>
                </div>
            </div>

            <div class="mt-20 text-center max-w-2xl mx-auto">
                <p class="text-gray-600 leading-relaxed font-light text-xl italic">
                    "Você não apenas faz parte da minha vida... você dá vida a ela."
                </p>
            </div>
        </div>
    </section>

    <!-- Secret Letter Section -->
    <section class="py-28 px-4 bg-[#fcfaf7]">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="serif text-5xl font-light mb-12">Uma Pequena Promessa</h2>
            <div class="letter-container" onclick="toggleLetter(this)">
                <div class="letter-envelope glass-panel">
                    <div class="letter-content">
                        <div class="wax-seal">❤</div>
                        <p class="mt-6 text-gray-500 font-light">Toque para ler</p>
                    </div>
                    <div class="letter-message text-left serif">
                        <p class="mb-4">Minha querida Blenda,</p>
                        <p class="mb-4">Escrever isso é fácil porque cada palavra vem do fundo do meu coração. Você entrou na minha vida como uma luz e, a cada dia que passa, essa luz brilha mais forte.</p>
                        <p class="mb-4">Eu não quero apenas ser uma parte do seu presente; eu sonho em construir cada segundo do seu futuro. Quero ser o seu porto seguro, o seu maior admirador e, um dia, o seu marido.</p>
                        <p>Com todo o meu amor e uma promessa de eternidade.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Closing Section -->
    <section class="py-32 px-4 bg-white">
        <div class="max-w-3xl mx-auto text-center space-y-12">
            <div class="space-y-8">
                <h2 class="serif text-7xl md:text-8xl font-light leading-tight text-gradient">
                    Nosso Próximo Capítulo
                </h2>
                <div class="flex justify-center pt-4">
                    <div class="line-accent"></div>
                </div>
            </div>

            <p class="text-xl md:text-2xl text-[#b76e79] leading-relaxed font-light max-w-2xl mx-auto serif">
                Blenda, eu escolho você. Hoje, amanhã e para sempre.
            </p>

            <p class="text-base md:text-lg text-gray-600 leading-relaxed font-light max-w-2xl mx-auto">
                Mal posso esperar por todos os momentos que ainda vamos criar, por todos os sorrisos que ainda vamos compartilhar e pela vida que vamos construir juntos. Você é o meu melhor destino.
            </p>

            <button onclick="createConfetti()" 
                    class="px-12 py-3 border border-[#b76e79] text-[#b76e79] font-medium text-sm transition hover:bg-[#b76e79] hover:text-white mt-8 rounded-full cta-cute">
                Celebrar Nosso Amor <span class="floating-emoji">💝</span>
            </button>


            <div class="mt-16 flex flex-col items-center space-y-4">
                <div class="w-48 h-48 md:w-64 md:h-64 bg-rose-50 flex items-center justify-center border border-rose-100 rounded-sm">
                    <img src="" alt="" class="w-full h-full object-cover">
                    <span class="absolute text-rose-200 serif italic">Último Detalhe...</span>
                </div>
            </div>

            <p class="text-xs text-gray-400 font-light pt-12">
                Criado com amor, admiração e algumas risadas
            </p>
        </div>
    </section>

    <script>
        function createConfetti() {
            // Create fireworks from bottom
            createFireworks();
            
            // Create hearts falling from top
            createHearts();
        }

        function createFireworks() {
            const fireworkColors = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#ffffff'];
            const fireworkCount = 6;
            const viewportWidth = window.innerWidth;

            for (let i = 0; i < fireworkCount; i++) {
                setTimeout(() => {
                    const firework = document.createElement('div');
                    firework.className = 'firework';
                    const x = (viewportWidth / (fireworkCount + 1)) * (i + 1);
                    firework.style.left = x + 'px';
                    firework.style.bottom = '0px';

                    // Create particles for each firework
                    const particleCount = 40;
                    for (let j = 0; j < particleCount; j++) {
                        const particle = document.createElement('div');
                        particle.className = 'firework-particle';
                        const color = fireworkColors[Math.floor(Math.random() * fireworkColors.length)];
                        particle.style.backgroundColor = color;
                        particle.style.boxShadow = `0 0 8px ${color}, 0 0 12px ${color}`;

                        // Random direction for particles in circular pattern
                        const angle = (Math.PI * 2 * j) / particleCount + (Math.random() - 0.5) * 0.3;
                        const velocity = 80 + Math.random() * 120;
                        const tx = Math.cos(angle) * velocity;
                        const ty = Math.sin(angle) * velocity;
                        particle.style.setProperty('--tx', tx + 'px');
                        particle.style.setProperty('--ty', ty + 'px');
                        
                        // Random size
                        const size = 3 + Math.random() * 3;
                        particle.style.width = size + 'px';
                        particle.style.height = size + 'px';

                        firework.appendChild(particle);
                    }

                    document.body.appendChild(firework);

                    setTimeout(() => firework.remove(), 2000);
                }, i * 200);
            }
        }

        function createHearts() {
            const heartColors = ['#ef4444', '#f472b6', '#ec4899', '#f43f5e'];
            const heartCount = 30;

            for (let i = 0; i < heartCount; i++) {
                setTimeout(() => {
                    spawnHeart(heartColors);
                }, i * 50);
            }
        }

        function spawnHeart(colors) {
            const heart = document.createElement('div');
            heart.className = 'heart';
            const heartShape = document.createElement('div');
            heartShape.className = 'heart-shape';
            const color = colors[Math.floor(Math.random() * colors.length)];
            
            // Random unique ID for heart style
            const randomId = Math.floor(Math.random() * 1000000);
            const style = document.createElement('style');
            style.textContent = `
                .heart-${randomId} .heart-shape::before,
                .heart-${randomId} .heart-shape::after {
                    background: ${color} !important;
                }
            `;
            document.head.appendChild(style);
            
            heart.classList.add(`heart-${randomId}`);
            heart.appendChild(heartShape);

            heart.style.left = Math.random() * 100 + '%';
            heart.style.top = '-30px';
            heart.style.animationDuration = (3 + Math.random() * 3) + 's';
            heart.style.transform = `scale(${0.6 + Math.random() * 0.6})`;
            heart.style.opacity = '0.8';

            document.body.appendChild(heart);

            setTimeout(() => {
                heart.remove();
                style.remove();
            }, 6000);
        }

        function startAmbientHearts() {
            const heartColors = ['#ef4444', '#f472b6', '#ec4899', '#f43f5e'];
            // Spawn a heart every 800ms for a steady ambient effect
            setInterval(() => {
                spawnHeart(heartColors);
            }, 800);
        }

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Audio control with two songs
        const audio1 = document.getElementById('backgroundMusic1');
        const audio2 = document.getElementById('backgroundMusic2');
        const musicDisc = document.getElementById('musicDisc');
        const musicPlayer = document.getElementById('musicPlayer');
        const playPauseBtn = document.getElementById('playPauseBtn');
        const playIcon = document.getElementById('playIcon');
        const pauseIcon = document.getElementById('pauseIcon');
        const songName = document.getElementById('songName');

        // Music playlist
        const playlist = [
            { audio: audio1, name: 'Anjos' },
            { audio: audio2, name: 'Pearls' }
        ];

        let currentTrack = 0;
        let currentAudio = playlist[currentTrack].audio;
        let musicStarted = false;
        let hasScrolled = false;
        let musicSwitched = false;

        // Set initial volume - Anjos 40%, Pearls 100%
        audio1.volume = 0.4;
        audio2.volume = 1.0;

        // Update song name
        const updateSongName = () => {
            songName.textContent = playlist[currentTrack].name;
        };

        // Update disc rotation based on audio state
        const updateDiscState = () => {
            if (!currentAudio.paused) {
                musicDisc.classList.add('playing');
                playIcon.style.display = 'none';
                pauseIcon.style.display = 'block';
            } else {
                musicDisc.classList.remove('playing');
                playIcon.style.display = 'block';
                pauseIcon.style.display = 'none';
            }
        };

        // Switch to next track
        const switchTrack = () => {
            if (musicSwitched) {
                console.log('Music already switched');
                return;
            }
            
            console.log('Switching track from', currentTrack, 'to 1');
            musicSwitched = true;
            const wasPlaying = !currentAudio.paused;
            
            // Fade out current track
            const fadeOut = setInterval(() => {
                if (currentAudio.volume > 0.05) {
                    currentAudio.volume -= 0.05;
                } else {
                    currentAudio.pause();
                    currentAudio.volume = 0.4; // Reset Anjos to 40%
                    clearInterval(fadeOut);
                    
                    // Switch to next track
                    currentTrack = 1;
                    currentAudio = playlist[currentTrack].audio;
                    updateSongName();
                    console.log('Switched to track:', currentTrack, playlist[currentTrack].name);
                    
                    // Fade in new track (Pearls at 100%)
                    if (wasPlaying) {
                        currentAudio.muted = false;
                        currentAudio.volume = 0;
                        currentAudio.play().then(() => {
                            console.log('Playing new track');
                            const fadeIn = setInterval(() => {
                                if (currentAudio.volume < 1.0) {
                                    currentAudio.volume += 0.05;
                                } else {
                                    currentAudio.volume = 1.0;
                                    clearInterval(fadeIn);
                                    updateDiscState();
                                }
                            }, 50);
                        }).catch(err => {
                            console.log('Could not play audio:', err);
                        });
                    } else {
                        // Even if not playing, prepare the track at full volume
                        currentAudio.muted = false;
                        currentAudio.volume = 1.0;
                    }
                }
            }, 50);
        };

        // Toggle music player visibility
        musicDisc.addEventListener('click', () => {
            musicPlayer.classList.toggle('active');
        });

        // Play/Pause button
        playPauseBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (currentAudio.paused) {
                currentAudio.play().then(() => {
                    updateDiscState();
                }).catch(err => {
                    console.log('Could not play audio:', err);
                });
            } else {
                currentAudio.pause();
                updateDiscState();
            }
        });


        // Update disc when audio state changes
        audio1.addEventListener('play', updateDiscState);
        audio1.addEventListener('pause', updateDiscState);
        audio2.addEventListener('play', updateDiscState);
        audio2.addEventListener('pause', updateDiscState);

        // Start first music automatically
        const startMusic = () => {
            if (!musicStarted) {
                musicStarted = true;
                currentTrack = 0;
                currentAudio = playlist[0].audio;
                updateSongName();
                currentAudio.play().then(() => {
                    updateDiscState();
                    // Add entrance animation
                    musicDisc.style.animation = 'none';
                    setTimeout(() => {
                        musicDisc.style.animation = '';
                    }, 10);
                }).catch(err => {
                    console.log('Could not play audio:', err);
                    musicStarted = false; // Reset to try again
                });
            }
        };

        // Initialize
        currentTrack = 0;
        currentAudio = playlist[0].audio;
        updateSongName();

        // Gift box functionality
        const giftOverlay = document.getElementById('giftOverlay');
        const giftBox = document.getElementById('giftBox');
        const giftSparkles = document.getElementById('giftSparkles');
        
        // Hide body content when gift overlay is active
        document.body.classList.add('gift-active');

        // Create sparkles
        for (let i = 0; i < 20; i++) {
            const sparkle = document.createElement('div');
            sparkle.className = 'sparkle';
            sparkle.style.left = Math.random() * 100 + '%';
            sparkle.style.top = Math.random() * 100 + '%';
            sparkle.style.animationDelay = Math.random() * 2 + 's';
            giftSparkles.appendChild(sparkle);
        }

        // Open gift box and start music
        const openGift = () => {
            giftBox.classList.add('opened');
            
            // Trigger celebration effects automatically
            setTimeout(() => {
                createConfetti();
            }, 400);
            
            // Start music and ambient hearts after animation
            setTimeout(() => {
                audio1.muted = false;
                audio1.play().then(() => {
                    musicStarted = true;
                    currentTrack = 0;
                    currentAudio = audio1;
                    updateSongName();
                    updateDiscState();
                    console.log('Music started from gift box');
                    
                    // Start ambient hearts
                    startAmbientHearts();
                    
                    // Hide overlay after music starts
                    setTimeout(() => {
                        giftOverlay.classList.add('hidden');
                        document.body.classList.remove('gift-active');
                        setTimeout(() => {
                            giftOverlay.style.display = 'none';
                        }, 800);
                    }, 1500);
                }).catch(err => {
                    console.log('Could not play audio:', err);
                });
            }, 300);
        };

        // Click on gift box to open
        giftBox.addEventListener('click', openGift);
        giftOverlay.addEventListener('click', (e) => {
            if (e.target === giftOverlay) {
                openGift();
            }
        });

        // Toggle secret letter
        function toggleLetter(container) {
            const envelope = container.querySelector('.letter-envelope');
            envelope.classList.toggle('open');
        }

        // Detect when reaching "O Que Eu Vejo Em Você" section and switch music
        const memoriesSection = document.getElementById('memories');
        
        // --- Cuteness: Mouse Trail ---
        const trailEmojis = ['✨', '💖', '🌸', '✨', '❤️'];
        let lastX = 0;
        let lastY = 0;

        document.addEventListener('mousemove', (e) => {
            const now = Date.now();
            // Limit spawn rate for performance and cleaner look
            if (Math.abs(e.pageX - lastX) > 40 || Math.abs(e.pageY - lastY) > 40) {
                const sparkle = document.createElement('div');
                sparkle.className = 'sparkle-trail';
                sparkle.textContent = trailEmojis[Math.floor(Math.random() * trailEmojis.length)];
                sparkle.style.left = e.pageX + 'px';
                sparkle.style.top = e.pageY + 'px';
                document.body.appendChild(sparkle);
                
                setTimeout(() => sparkle.remove(), 1000);
                lastX = e.pageX;
                lastY = e.pageY;
            }
        });
        
        if (memoriesSection) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !musicSwitched) {
                        console.log('Section "Quero Ver Me Zoarem" detected, switching music...');
                        switchTrack();
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '100px'
            });
            
            // Start observing after a short delay to ensure DOM is ready
            setTimeout(() => {
                observer.observe(memoriesSection);
                console.log('Observer started for memories section');
            }, 500);
        } else {
            console.log('Memories section not found!');
        }

        // Close player when clicking outside
        document.addEventListener('click', (e) => {
            if (!musicPlayer.contains(e.target) && !musicDisc.contains(e.target) && musicPlayer.classList.contains('active')) {
                musicPlayer.classList.remove('active');
            }
        });

        // Video auto-play/pause based on section visibility
        const videoSection = document.getElementById('video-section');
        const video = document.getElementById('section-video');

        if (videoSection && video) {
            let isPlaying = false;
            let hasUserInteracted = false;

            // Enable video play after first user interaction
            const enableVideoPlay = () => {
                if (!hasUserInteracted) {
                    hasUserInteracted = true;
                    // Try to play if section is visible
                    const rect = videoSection.getBoundingClientRect();
                    const isVisible = rect.top < window.innerHeight && rect.bottom > 0;
                    if (isVisible && !isPlaying) {
                        video.play().then(() => {
                            isPlaying = true;
                        }).catch(err => {
                            console.log('Could not play video:', err);
                        });
                    }
                }
            };

            // Listen for user interaction
            document.addEventListener('click', enableVideoPlay, { once: true });
            document.addEventListener('scroll', enableVideoPlay, { once: true });
            document.addEventListener('touchstart', enableVideoPlay, { once: true });

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Section is visible, play video
                        if (!isPlaying && hasUserInteracted) {
                            video.play().then(() => {
                                isPlaying = true;
                            }).catch(err => {
                                console.log('Could not play video:', err);
                            });
                        }
                    } else {
                        // Section is not visible, pause video
                        if (isPlaying) {
                            video.pause();
                            isPlaying = false;
                        }
                    }
                });
            }, {
                threshold: 0.1, // Trigger when 10% of section is visible
                rootMargin: '50px' // Add margin to trigger earlier
            });

            // Start observing
            observer.observe(videoSection);
        }
    </script>
</body>
</html>
