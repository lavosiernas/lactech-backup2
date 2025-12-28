<?php
declare(strict_types=1);

$siteName = 'NordPetro';
$metaDescription = 'NordPetro — Energia e petróleo com excelência operacional, sustentabilidade sólida e presença global.';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES); ?>">
    <link rel="icon" type="image/png" href="assets/img/nordpetrol.png">
    <title><?= htmlspecialchars($siteName, ENT_QUOTES); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        midnight: '#08131F',
                        deepblue: '#102130',
                        slateblue: '#1F2E3A',
                        accent: '#C1272D',
                        cloud: '#F5F6F9',
                        graphite: '#586270',
                        soft: '#EBEEF3',
                    },
                    maxWidth: {
                        'content': '1100px',
                    },
                },
            },
        };
    </script>
    <style>
        html {
            scroll-behavior: smooth;
        }
        .hero-backdrop {
            position: absolute;
            inset: 0;
            overflow: hidden;
        }
        .hero-backdrop video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .hero-backdrop::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(8, 19, 31, 0.92), rgba(8, 19, 31, 0.45));
        }
        .hero-backdrop::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at top right, rgba(8, 19, 31, 0.35) 0%, transparent 60%);
        }
        .section-shell {
            padding: clamp(56px, 8vw, 96px) 0;
        }
        .section-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(193, 39, 45, 0.08);
            color: #C1272D;
            font-size: 0.65rem;
            letter-spacing: 0.28em;
            text-transform: uppercase;
        }
        .feature-grid {
            display: grid;
            gap: 20px;
        }
        @media (min-width: 768px) {
            .feature-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        .feature-card {
            background: #ffffff;
            border: 1px solid rgba(15, 36, 56, 0.08);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 18px 45px -30px rgba(8, 19, 31, 0.35);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 55px -28px rgba(8, 19, 31, 0.45);
        }
        .feature-card h3 {
            color: #102130;
            font-size: 1.15rem;
            margin-bottom: 10px;
        }
        .feature-card p {
            color: #586270;
            line-height: 1.55;
        }
        .kpi-strip {
            display: grid;
            gap: 18px;
            margin-top: 28px;
        }
        @media (min-width: 768px) {
            .kpi-strip {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        .kpi-card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(15, 36, 56, 0.1);
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 14px 34px -25px rgba(8, 19, 31, 0.55);
        }
        .kpi-card h3 {
            font-size: 0.75rem;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: #C1272D;
            margin-bottom: 12px;
        }
        .kpi-card p {
            color: #102130;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .list-ticker {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 20px;
            margin-top: 18px;
            font-size: 0.85rem;
            color: #586270;
        }
        .list-ticker span {
            display: inline-flex;
            align-items: center;
        }
        .list-ticker span::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #C1272D;
            margin-right: 8px;
        }
        .table-shell {
            margin-top: 28px;
            border-radius: 18px;
            border: 1px solid rgba(15, 36, 56, 0.12);
            overflow: hidden;
            box-shadow: 0 22px 55px -25px rgba(8, 19, 31, 0.35);
        }
        .table-shell table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }
        .table-shell thead {
            background: #102130;
            color: #ffffff;
            letter-spacing: 0.28em;
            text-transform: uppercase;
        }
        .table-shell th,
        .table-shell td {
            padding: 16px 22px;
            text-align: left;
        }
        .table-shell tbody tr {
            background: rgba(255, 255, 255, 0.92);
        }
        .table-shell tbody tr + tr {
            border-top: 1px solid rgba(15, 36, 56, 0.08);
        }
        .careers-layout {
            display: grid;
            gap: 24px;
        }
        @media (min-width: 900px) {
            .careers-layout {
                grid-template-columns: 1.2fr 0.8fr;
                align-items: start;
            }
        }
        .career-card {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid rgba(15, 36, 56, 0.08);
            padding: 24px;
            box-shadow: 0 18px 40px -28px rgba(8, 19, 31, 0.35);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .career-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 24px 55px -30px rgba(8, 19, 31, 0.45);
        }
        .offices-grid {
            display: grid;
            gap: 18px;
        }
        @media (min-width: 768px) {
            .offices-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        .offices-grid article {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(15, 36, 56, 0.12);
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 16px 35px -28px rgba(8, 19, 31, 0.28);
        }
        .media-pan {
            position: relative;
            overflow: hidden;
        }
        .media-pan img {
            transition: transform 6s ease, filter 1.2s ease;
        }
        .media-pan:hover img {
            transform: scale(1.05);
            filter: brightness(1.05);
        }
        .media-zoom {
            animation: zoomForward 12s ease-in-out infinite alternate;
        }
        @keyframes zoomForward {
            0% { transform: scale(1); }
            100% { transform: scale(1.08); }
        }
        #mobile-menu {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.35s ease;
        }
        #mobile-menu.open {
            opacity: 1;
            pointer-events: auto;
        }
        #mobile-menu .mobile-menu-panel {
            transform: translateX(100%);
            transition: transform 0.35s ease;
        }
        #mobile-menu.open .mobile-menu-panel {
            transform: translateX(0);
        }
        .slider-track {
            display: grid;
            gap: 18px;
        }
        .operations-slider {
            gap: 24px;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }
        .offices-slider {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }
        @media (max-width: 767px) {
            .slider-track {
                display: flex;
                gap: 16px;
                overflow-x: auto;
                padding-bottom: 1rem;
                scroll-snap-type: x mandatory;
                scrollbar-width: none;
                -webkit-overflow-scrolling: touch;
            }
            .slider-track::-webkit-scrollbar {
                display: none;
            }
            .slider-track .slider-item {
                flex: 0 0 calc(78vw - 2.5rem);
                max-width: calc(78vw - 2.5rem);
                scroll-snap-align: start;
            }
        }
        .video-container {
            position: relative;
            overflow: hidden;
            transition: width 0.75s ease, height 0.75s ease, margin 0.75s ease, border-radius 0.75s ease, box-shadow 0.75s ease;
        }
        .video-container[data-effect="shrink"] {
            width: 100vw;
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            border-radius: 0;
            box-shadow: 0 24px 45px rgba(0, 0, 0, 0.25);
        }
        .video-container[data-effect="shrink"].scrolled {
            width: calc(100% - 2rem);
            margin-left: 1rem;
            margin-right: 1rem;
            border-radius: 20px;
            box-shadow: 0 16px 35px rgba(0, 0, 0, 0.18);
        }
        .video-container[data-effect="grow"] {
            width: calc(100% - 2rem);
            margin-left: 1rem;
            margin-right: 1rem;
            border-radius: 20px;
            box-shadow: 0 16px 35px rgba(0, 0, 0, 0.18);
            height: 48vh;
            min-height: 280px;
        }
        .video-container[data-effect="grow"].scrolled {
            width: 100vw;
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            border-radius: 0;
            box-shadow: 0 24px 45px rgba(0, 0, 0, 0.25);
            height: 64vh;
            min-height: 360px;
        }
        .video-caption {
            position: absolute;
            bottom: 2.5rem;
            left: 2.5rem;
            right: 2.5rem;
            color: #ffffff;
            text-shadow: 0 18px 40px rgba(0, 0, 0, 0.55);
        }
        .video-caption p {
            font-size: 0.75rem;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            margin-bottom: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
        }
        .video-caption h3 {
            font-size: 1.5rem;
            font-weight: 600;
            line-height: 1.35;
        }
    </style>
</head>
<body class="bg-cloud font-sans text-slateblue antialiased">
<header class="border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-content items-center px-10 py-7">
        <div class="flex items-center gap-2 pl-2 pr-4 md:pl-4 md:pr-20">
            <img src="assets/img/nordpetrol.png" alt="NordPetro" class="h-10 w-auto">
            <div class="leading-tight">
                <span class="text-sm font-semibold uppercase tracking-[0.34em] text-slateblue block">NordPetro</span>
                <span class="text-[0.65rem] uppercase tracking-[0.38em] text-accent block">oil & gas</span>
            </div>
        </div>
        <nav class="ml-auto hidden items-center gap-2 text-[0.7rem] font-semibold uppercase tracking-[0.25em] text-slateblue md:flex" aria-label="Navegação principal">
            <a href="#inicio" class="rounded-md px-3 py-1.5 transition hover:bg-soft hover:text-deepblue">Home</a>
            <a href="#sobre" class="rounded-md px-3 py-1.5 transition hover:bg-soft hover:text-deepblue">Sobre</a>
            <a href="#operacoes" class="rounded-md px-3 py-1.5 transition hover:bg-soft hover:text-deepblue">Operações</a>
            <a href="#sustentabilidade" class="rounded-md px-3 py-1.5 transition hover:bg-soft hover:text-deepblue">Sustentabilidade</a>
            <a href="#carreiras" class="rounded-md px-3 py-1.5 transition hover:bg-soft hover:text-deepblue">Carreiras</a>
            <a href="#escritorios" class="rounded-md px-3 py-1.5 transition hover:bg-soft hover:text-deepblue">Escritórios</a>
        </nav>
        <button id="mobile-menu-button" class="ml-auto md:hidden rounded-full border border-slate-300 p-2 text-slateblue transition hover:border-accent hover:text-accent" aria-label="Abrir navegação" aria-expanded="false">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            </svg>
        </button>
    </div>
</header>

<div id="mobile-menu" class="fixed inset-0 z-40">
    <div class="absolute inset-0 bg-midnight/70" data-menu-overlay></div>
    <div class="mobile-menu-panel absolute right-0 top-0 flex h-full w-72 flex-col bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <span class="text-xs font-semibold uppercase tracking-[0.32em] text-slateblue">Navegação</span>
            <button id="mobile-menu-close" class="rounded-full border border-slate-300 p-2 text-slateblue transition hover:border-accent hover:text-accent" aria-label="Fechar navegação">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6l-12 12" />
                </svg>
            </button>
        </div>
        <nav class="flex flex-1 flex-col gap-1 px-6 py-6 text-sm font-semibold uppercase tracking-[0.25em] text-slateblue" aria-label="Navegação mobile">
            <a href="#inicio" class="rounded-md px-3 py-3 transition hover:bg-soft">Home</a>
            <a href="#sobre" class="rounded-md px-3 py-3 transition hover:bg-soft">Sobre</a>
            <a href="#operacoes" class="rounded-md px-3 py-3 transition hover:bg-soft">Operações</a>
            <a href="#sustentabilidade" class="rounded-md px-3 py-3 transition hover:bg-soft">Sustentabilidade</a>
            <a href="#carreiras" class="rounded-md px-3 py-3 transition hover:bg-soft">Carreiras</a>
            <a href="#escritorios" class="rounded-md px-3 py-3 transition hover:bg-soft">Escritórios</a>
        </nav>
    </div>
</div>

<main>
    <section id="inicio" class="relative bg-midnight pb-64 pt-56 md:pt-64">
        <div class="hero-backdrop absolute inset-0 z-0">
            <video autoplay muted loop playsinline>
                <source src="assets/img/nord.mp4" type="video/mp4">
            </video>
        </div>
        <div class="relative z-10 mx-auto max-w-content px-6">
            <div class="flex-1 space-y-8 text-white">
                <p class="text-xs font-semibold uppercase tracking-[0.32em] text-accent">Visão corporativa</p>
                <h1 class="text-4xl font-semibold md:text-5xl">Energia confiável, decisões rápidas e presença global para mover o amanhã.</h1>
                <p class="max-w-xl text-base leading-relaxed text-white/80">A NordPetro combina exploração, refino, energia renovável e inteligência de dados para abastecer mercados estratégicos com segurança e governança.</p>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="#operacoes" class="inline-flex items-center justify-center rounded-md border border-accent bg-accent px-6 py-3 text-xs font-semibold uppercase tracking-[0.22em] text-white transition hover:bg-transparent hover:text-accent">Conheça as operações</a>
                    <a href="#carreiras" class="inline-flex items-center justify-center rounded-md border border-white/30 px-6 py-3 text-xs font-semibold uppercase tracking-[0.22em] text-white transition hover:border-accent hover:text-accent">Explorar carreiras</a>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-content px-6 py-16">
        <div class="flex flex-col gap-12 md:flex-row md:items-center">
            <div class="flex-1 space-y-6">
                <h2 class="text-3xl font-semibold text-deepblue md:text-4xl" id="sobre">Quem somos</h2>
                <p class="text-base leading-relaxed text-graphite">Fundada em Fortaleza, a NordPetro integra engenharia, capital humano e inovação para garantir energia segura e sustentável. Operamos plataformas offshore e onshore, refinarias com controle digital e parques híbridos em mercados estratégicos.</p>
                <p class="text-base leading-relaxed text-graphite">Nossa governança é orientada por indicadores auditados, comitês especializados e investimentos constantes em automação, IA e projetos ESG globais.</p>
                <div class="grid gap-5 sm:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-white p-5">
                        <p class="text-3xl font-semibold text-deepblue">30+</p>
                        <p class="mt-2 text-xs uppercase tracking-[0.25em] text-graphite">anos de atuação</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-5">
                        <p class="text-3xl font-semibold text-deepblue">10+</p>
                        <p class="mt-2 text-xs uppercase tracking-[0.25em] text-graphite">países atendidos</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-5">
                        <p class="text-3xl font-semibold text-deepblue">100%</p>
                        <p class="mt-2 text-xs uppercase tracking-[0.25em] text-graphite">energia responsável</p>
                    </div>
                </div>
            </div>
            <div class="flex-1 rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                <h3 class="text-lg font-semibold text-deepblue">Direcionadores estratégicos</h3>
                <ul class="mt-5 space-y-4 text-sm text-graphite">
                    <li class="flex items-start gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-accent"></span>Integridade, segurança e excelência em toda a cadeia de valor energético.</li>
                    <li class="flex items-start gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-accent"></span>Investimento contínuo em automação, análises preditivas e manutenção inteligente.</li>
                    <li class="flex items-start gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-accent"></span>Agenda ESG com indicadores auditados e transparência nos relatórios anuais.</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="bg-cloud py-12">
        <div class="mx-auto w-full px-4 md:px-8 lg:px-10">
            <div class="video-container relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl" data-effect="shrink" data-static-mobile style="height: 58vh; min-height: 340px;">
                <img src="assets/img/refinarianord.jpg" alt="Complexo industrial NordPetro" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-midnight/80 via-transparent to-transparent"></div>
                <div class="absolute bottom-10 left-10 right-10 text-white">
                    <p class="text-xs uppercase tracking-[0.32em] text-white">Infraestrutura crítica</p>
                    <h3 class="mt-3 text-2xl font-semibold max-w-xl">Hubs integrados conectando operações offshore, refinarias e centros de inovação.</h3>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-cloud">
        <div class="mx-auto grid max-w-content gap-8 px-6 py-16 md:grid-cols-3">
            <article class="rounded-lg border border-slate-200 p-8">
                <h3 class="text-xl font-semibold text-deepblue">Tecnologia aplicada</h3>
                <p class="mt-3 text-sm leading-relaxed text-graphite">Centros de controle integrados, digital twins e sensores inteligentes asseguram disponibilidade e segurança em operações complexas.</p>
            </article>
            <article class="rounded-lg border border-slate-200 p-8">
                <h3 class="text-xl font-semibold text-deepblue">Sustentabilidade mensurável</h3>
                <p class="mt-3 text-sm leading-relaxed text-graphite">Indicadores ambientais monitorados, uso racional de recursos e projetos sociais estruturados em todas as regiões.</p>
            </article>
            <article class="rounded-lg border border-slate-200 p-8">
                <h3 class="text-xl font-semibold text-deepblue">Presença global</h3>
                <p class="mt-3 text-sm leading-relaxed text-graphite">Equipes multinacionais e hubs corporativos coordenando operações e trading nos principais mercados energéticos.</p>
            </article>
        </div>
    </section>

    <section id="operacoes" class="bg-cloud">
        <div class="w-full px-0 pt-16 pb-12">
            <div class="relative mb-12 overflow-hidden">
                <img src="assets/img/menord.jpg" alt="Operações NordPetro" class="w-full object-cover" style="height: 80vh; min-height: 420px; object-position: center top;">
                <div class="absolute inset-x-8 bottom-8 text-white drop-shadow-[0_12px_24px_rgba(0,0,0,0.45)]">
                    <p class="text-xs uppercase tracking-[0.28em] text-white/85">Operações em campo</p>
                    <h3 class="mt-2 text-2xl font-semibold max-w-2xl">Especialistas garantindo segurança operacional, monitoramento contínuo e integração com refino e logística.</h3>
                </div>
            </div>
        </div>
        <div class="section-shell pt-12 pb-12 lg:pb-24">
             <div class="section-inner">
                 <span class="badge">Operações NordPetro</span>
                 <h2 class="mt-5 text-3xl font-semibold text-deepblue md:text-4xl">Da exploração à distribuição com engenharia de precisão.</h2>
                 <p class="mt-4 max-w-3xl text-base leading-relaxed text-graphite">Integramos exploração offshore e onshore, refinarias digitais, centros de energia renovável e laboratórios de inovação para garantir abastecimento contínuo e seguro.</p>
                <div class="slider-track operations-slider mt-10 md:grid-cols-2" data-slider="operations">
                    <article class="feature-card slider-item">
                        <h3>Exploração &amp; Produção</h3>
                        <p>Plataformas com robótica submarina, análise sísmica avançada e monitoramento preditivo 24/7.</p>
                    </article>
                    <article class="feature-card slider-item">
                        <h3>Refino &amp; Logística</h3>
                        <p>Refinarias conectadas a redes inteligentes, laboratórios em linha e logística multimodal sincronizada.</p>
                    </article>
                    <article class="feature-card slider-item">
                        <h3>Energia Sustentável</h3>
                        <p>Centrais solares, eólicas e projetos de captura de carbono integrados ao portfólio tradicional.</p>
                    </article>
                    <article class="feature-card slider-item">
                        <h3>Pesquisa &amp; Inovação</h3>
                        <p>O NordPetro Tech Lab desenvolve IA aplicada, materiais avançados e automação industrial de próxima geração.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section id="sustentabilidade" class="bg-cloud">
        <div class="w-full px-0">
            <div class="relative overflow-hidden">
                <img src="assets/img/sustentabilidade.jpg" alt="Equipe NordPetro em parque eólico" class="w-full object-cover" style="height: 72vh; min-height: 400px; object-position: center;">
                <div class="absolute inset-x-8 bottom-10 max-w-3xl text-white drop-shadow-[0_12px_28px_rgba(0,0,0,0.45)]">
                    <p class="text-xs uppercase tracking-[0.28em] text-white/85">Sustentabilidade em campo</p>
                    <h3 class="mt-3 text-2xl font-semibold">Times dedicados à transição energética, monitoramento ambiental e governança socioambiental integrada.</h3>
                </div>
            </div>
        </div>
        <div class="section-shell pt-20">
            <div class="section-inner">
                <span class="badge">Sustentabilidade e ESG</span>
                <h2 class="mt-5 text-3xl font-semibold text-deepblue md:text-4xl">Governança responsável, compromissos climáticos e impacto social mensurável.</h2>
                <p class="mt-4 max-w-3xl text-base leading-relaxed text-graphite">Metas auditadas, parcerias socioambientais e inteligência energética orientam nossa estratégia para acelerar a transição energética com responsabilidade.</p>
                <div class="kpi-strip">
                    <div class="kpi-card">
                        <h3>Meta climática</h3>
                        <p>Redução de 45% nas emissões diretas até 2030 e neutralidade de carbono planejada para 2045.</p>
                    </div>
                    <div class="kpi-card">
                        <h3>Parcerias socioambientais</h3>
                        <p>Projetos com ONGs e universidades fortalecendo preservação de ecossistemas e desenvolvimento local.</p>
                    </div>
                    <div class="kpi-card">
                        <h3>Eficiência energética</h3>
                        <p>IA aplicada ao controle de consumo, certificações ISO e programas de captura e reutilização de calor.</p>
                    </div>
                </div>
                <div class="list-ticker">
                    <span>Inventário de emissões auditado anualmente</span>
                    <span>Programas sociais em 12 comunidades</span>
                    <span>Transparência em relatórios ESG com dados abertos</span>
                </div>
            </div>
        </div>
    </section>

    <section id="carreiras" class="bg-cloud">
        <div class="section-shell">
            <div class="section-inner careers-layout">
                <div>
                    <span class="badge">Carreiras NordPetro</span>
                    <h2 class="mt-5 text-3xl font-semibold text-deepblue md:text-4xl">Ambiente global, projetos desafiadores e desenvolvimento contínuo.</h2>
                    <p class="mt-4 text-base leading-relaxed text-graphite">Profissionais de engenharia, tecnologia, ESG, finanças e trading encontram na NordPetro um ecossistema orientado a resultados, inovação e crescimento.</p>
                    <div class="feature-grid mt-10">
                        <article class="career-card">
                            <h3>Engenheiro de Operações Offshore</h3>
                            <p class="mt-2 text-sm leading-relaxed text-graphite">Gestão de ativos e performance de plataformas com foco em segurança e eficiência.</p>
                            <a href="#" class="mt-4 inline-flex text-xs font-semibold uppercase tracking-[0.2em] text-accent hover:underline">Ver detalhes</a>
                        </article>
                        <article class="career-card">
                            <h3>Especialista em Transição Energética</h3>
                            <p class="mt-2 text-sm leading-relaxed text-graphite">Estruturação de projetos híbridos e contratos corporativos de energia renovável.</p>
                            <a href="#" class="mt-4 inline-flex text-xs font-semibold uppercase tracking-[0.2em] text-accent hover:underline">Ver detalhes</a>
                        </article>
                        <article class="career-card">
                            <h3>Analista de ESG &amp; Compliance</h3>
                            <p class="mt-2 text-sm leading-relaxed text-graphite">Monitoramento de indicadores, reporting regulatório e integração de metas ESG.</p>
                            <a href="#" class="mt-4 inline-flex text-xs font-semibold uppercase tracking-[0.2em] text-accent hover:underline">Ver detalhes</a>
                        </article>
                        <article class="career-card">
                            <h3>Cientista de Dados Industriais</h3>
                            <p class="mt-2 text-sm leading-relaxed text-graphite">Modelagem preditiva e automação aplicada a operações críticas em grande escala.</p>
                            <a href="#" class="mt-4 inline-flex text-xs font-semibold uppercase tracking-[0.2em] text-accent hover:underline">Ver detalhes</a>
                        </article>
                    </div>
                </div>
                <div class="flex flex-col gap-4">
                    <div class="career-card">
                        <h3 class="text-lg font-semibold text-deepblue">Nossa cultura</h3>
                        <p class="mt-3 text-sm leading-relaxed text-graphite">Segurança, cooperação global, diversidade e aprendizado contínuo sustentam nossos times. Programas de rotação internacional, academias técnicas e trilhas de liderança aceleram carreiras.</p>
                        <div class="list-ticker mt-4">
                            <span>Academia técnica NordPetro</span>
                            <span>Mobilidade global</span>
                            <span>Programas de liderança</span>
                        </div>
                    </div>
                    <div class="career-card overflow-hidden p-0">
                        <img src="assets/img/men.jpg" alt="Equipes NordPetro" class="h-full w-full object-cover" style="height: 428px;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-midnight">
        <div class="w-full px-0">
            <div class="relative overflow-hidden">
                <video class="h-[70vh] min-h-[420px] w-full object-cover" autoplay muted loop playsinline>
                    <source src="assets/img/plataformanord.mp4" type="video/mp4">
                </video>
                <div class="absolute inset-x-8 bottom-10 max-w-3xl text-white drop-shadow-[0_18px_32px_rgba(0,0,0,0.5)]">
                    <p class="text-xs uppercase tracking-[0.28em] text-white/80">Inovação contínua</p>
                    <h3 class="mt-3 text-2xl font-semibold">Plataformas híbridas integrando IA, automação e energia renovável para ampliar a performance operacional.</h3>
                </div>
            </div>
        </div>
    </section>

    <section id="escritorios" class="bg-cloud">
        <div class="section-shell">
            <div class="section-inner">
                <span class="badge">Escritórios globais</span>
                <h2 class="mt-5 text-3xl font-semibold text-deepblue md:text-4xl">Presença estratégica conectando hubs de energia, tecnologia e trading.</h2>
                <p class="mt-4 max-w-3xl text-base leading-relaxed text-graphite">Coordenação central em Fortaleza e operações distribuídas em mercados-chave garantem resposta rápida e proximidade com clientes e parceiros.</p>
                <div class="offices-grid slider-track offices-slider mt-10 md:grid-cols-3" data-slider="offices">
                    <?php
                    $offices = [
                        ['Fortaleza (Sede Global)', 'Avenida Beira Mar, 2800 — Meireles'],
                        ['Montreal', '1200 René-Lévesque Blvd — Quebec'],
                        ['Nova Zelândia', '123 Queens Wharf — Wellington'],
                        ['Austrália', 'Barangaroo Avenue, 300 — Sydney'],
                        ['San Francisco', 'Ferry Building, Suite 500 — Califórnia'],
                        ['Chile', 'Avenida Apoquindo, 4500 — Santiago'],
                        ['Rio de Janeiro', 'Avenida Niemeyer, 250 — Leblon'],
                        ['Noruega', 'Stortingsgata, 15 — Oslo'],
                        ['Suécia', 'Vasagatan, 11 — Estocolmo'],
                        ['Finlândia', 'Mannerheimintie, 20 — Helsinque'],
                        ['Rússia', 'Prospekt Mira, 102 — Moscou'],
                        ['Alemanha', 'Friedrichstraße, 43 — Berlim'],
                        ['Inglaterra', 'One Canada Square — Londres'],
                        ['Espanha', 'Paseo de la Castellana, 81 — Madri'],
                        ['Nova York', '230 Park Avenue — Manhattan'],
                        ['África do Sul', 'Sandton Drive, 75 — Joanesburgo'],
                        ['Suíça', 'Rue du Rhône, 92 — Genebra'],
                        ['Holanda', 'Gustav Mahlerplein, 2 — Amsterdã'],
                    ];
                    foreach ($offices as $office): ?>
                        <article class="slider-item">
                            <h3 class="text-base font-semibold text-deepblue"><?= htmlspecialchars($office[0], ENT_QUOTES); ?></h3>
                            <p class="mt-2 text-sm leading-relaxed text-graphite"><?= htmlspecialchars($office[1], ENT_QUOTES); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="relative bg-midnight text-white">
    <div class="absolute inset-0">
        <img src="assets/img/rionord.jpg" alt="Mar industrial NordPetro" class="h-full w-full object-cover">
        <div class="absolute inset-0 bg-midnight/75"></div>
    </div>
    <div class="relative">
        <div class="mx-auto grid max-w-content gap-10 px-6 py-12 md:grid-cols-4">
            <div>
                <img src="assets/img/nordpetrol.png" alt="NordPetro" class="h-9 w-auto">
                <p class="mt-4 text-sm leading-relaxed text-white/70">Energia que move o futuro com rigor técnico, sustentabilidade e presença global.</p>
            </div>
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-[0.22em] text-accent">Institucional</h4>
                <ul class="mt-4 space-y-2 text-sm text-white/70">
                    <li><a href="#sobre" class="transition hover:text-accent">Sobre</a></li>
                    <li><a href="#operacoes" class="transition hover:text-accent">Operações</a></li>
                    <li><a href="#sustentabilidade" class="transition hover:text-accent">Sustentabilidade</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-[0.22em] text-accent">Investidores</h4>
                <ul class="mt-4 space-y-2 text-sm text-white/70">
                    <li><a href="#investidores" class="transition hover:text-accent">Relatórios</a></li>
                    <li><a href="#investidores" class="transition hover:text-accent">Governança</a></li>
                    <li><a href="#investidores" class="transition hover:text-accent">Notícias corporativas</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-[0.22em] text-accent">Conecte-se</h4>
                <div class="mt-4 flex flex-col gap-2 text-sm text-white/70">
                    <a href="#" class="transition hover:text-accent">LinkedIn</a>
                    <a href="#" class="transition hover:text-accent">YouTube</a>
                    <a href="#" class="transition hover:text-accent">Sala de imprensa</a>
                </div>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="mx-auto flex max-w-content flex-col items-center justify-between gap-3 px-6 py-6 text-xs text-white/60 md:flex-row">
                <p class="tracking-[0.28em] uppercase text-white">NordPetro — a força do amanhã em movimento hoje.</p>
                <p>© <?= date('Y'); ?> NordPetro. Todos os direitos reservados.</p>
            </div>
        </div>
    </div>
</footer>
<script>
(() => {
    const containers = Array.from(document.querySelectorAll('.video-container'));
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenuClose = document.getElementById('mobile-menu-close');
    const mobileMenuOverlay = mobileMenu?.querySelector('[data-menu-overlay]');
    const mobileMenuLinks = mobileMenu ? Array.from(mobileMenu.querySelectorAll('a')) : [];
    const sliders = Array.from(document.querySelectorAll('[data-slider]'));

    const updateContainers = () => {
        const windowHeight = window.innerHeight;

        containers.forEach((container) => {
            const rect = container.getBoundingClientRect();
            const effect = container.dataset.effect || 'shrink';
            const isStaticMobile = container.hasAttribute('data-static-mobile');
            const isMobileView = window.innerWidth < 768;

            if (isStaticMobile && isMobileView) {
                // For static-mobile containers, we don't apply the effect on scroll
                // They will only be affected by the initial data-effect attribute.
                return;
            }

            if (effect === 'shrink') {
                if (isStaticMobile && isMobileView) {
                    container.classList.remove('scrolled');
                } else {
                    if (rect.top < windowHeight * 0.25) {
                        container.classList.add('scrolled');
                    } else if (rect.top > windowHeight * 0.6) {
                        container.classList.remove('scrolled');
                    }
                }
            } else if (effect === 'grow') {
                const inFocus = rect.top < windowHeight * 0.45 && rect.bottom > windowHeight * 0.4;
                if (inFocus) {
                    container.classList.add('scrolled');
                } else if (rect.top > windowHeight * 0.7 || rect.bottom < windowHeight * 0.3) {
                    container.classList.remove('scrolled');
                }
            }

            const video = container.querySelector('video');
            if (video) {
                const inView = rect.top < windowHeight * 0.75 && rect.bottom > windowHeight * 0.25;
                if (inView && video.paused) {
                    video.play().catch(() => {});
                } else if (!inView && !video.paused) {
                    video.pause();
                }
            }
        });
    };

    let ticking = false;
    const onScroll = () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                updateContainers();
                ticking = false;
            });
            ticking = true;
        }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    updateContainers();

    if (mobileMenu && mobileMenuButton && mobileMenuClose) {
        const openMenu = () => {
            mobileMenu.classList.add('open');
            mobileMenuButton.setAttribute('aria-expanded', 'true');
            document.body.classList.add('overflow-hidden');
        };

        const closeMenu = () => {
            mobileMenu.classList.remove('open');
            mobileMenuButton.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('overflow-hidden');
        };

        mobileMenuButton.addEventListener('click', openMenu);
        mobileMenuClose.addEventListener('click', closeMenu);
        mobileMenuOverlay?.addEventListener('click', closeMenu);
        mobileMenuLinks.forEach((link) => link.addEventListener('click', closeMenu));
    }

    const initSlider = (slider) => {
        const items = Array.from(slider.children);
        if (items.length <= 1) return;

        let autoplayId;
        let resumeTimeout;
        const AUTOPLAY_DELAY = 6000;

        const isDesktop = () => window.innerWidth >= 768;

        const scrollToIndex = (index) => {
            const target = items[index];
            if (!target) return;
            const offset = target.offsetLeft - slider.offsetLeft;
            slider.scrollTo({ left: offset, behavior: 'smooth' });
        };

        const getCurrentIndex = () => {
            const left = slider.scrollLeft;
            let closestIndex = 0;
            let minDist = Infinity;
            items.forEach((item, idx) => {
                const pos = item.offsetLeft - slider.offsetLeft;
                const dist = Math.abs(left - pos);
                if (dist < minDist) {
                    minDist = dist;
                    closestIndex = idx;
                }
            });
            return closestIndex;
        };

        const advanceSlide = () => {
            const next = (getCurrentIndex() + 1) % items.length;
            scrollToIndex(next);
        };

        const stopAutoplay = () => {
            if (autoplayId) {
                window.clearInterval(autoplayId);
                autoplayId = undefined;
            }
            if (resumeTimeout) {
                window.clearTimeout(resumeTimeout);
                resumeTimeout = undefined;
            }
        };

        const scheduleAutoplay = () => {
            stopAutoplay();
            if (isDesktop()) return;
            resumeTimeout = window.setTimeout(() => {
                advanceSlide();
                autoplayId = window.setInterval(advanceSlide, AUTOPLAY_DELAY);
            }, AUTOPLAY_DELAY);
        };

        const handleResize = () => {
            if (isDesktop()) {
                stopAutoplay();
                slider.scrollLeft = 0;
            } else {
                scheduleAutoplay();
            }
        };

        const pauseAutoplay = () => stopAutoplay();
        const resumeAutoplay = () => scheduleAutoplay();

        slider.addEventListener('wheel', pauseAutoplay, { passive: true });
        slider.addEventListener('touchstart', pauseAutoplay, { passive: true });
        slider.addEventListener('mousedown', pauseAutoplay);
        slider.addEventListener('touchend', resumeAutoplay, { passive: true });
        slider.addEventListener('mouseup', resumeAutoplay);
        slider.addEventListener('mouseleave', resumeAutoplay);
        slider.addEventListener('scroll', () => {
            if (!isDesktop()) {
                pauseAutoplay();
                resumeAutoplay();
            }
        }, { passive: true });

        window.addEventListener('resize', handleResize, { passive: true });
        handleResize();
    };

    sliders.forEach(initSlider);
})();
</script>
</body>
</html>

